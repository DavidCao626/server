<?php


namespace App\EofficeApp\Elastic\Services\Config;

use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository;
use App\EofficeApp\Elastic\Services\BaseService;
use App\EofficeApp\Elastic\Services\Log\LogService;
use App\Jobs\Elasticsearch\ElasticsearchIndexSyncJob;
use Illuminate\Http\Request;
use DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;
/**
 * es搜索配置管理
 *  1. 数据库处理部分repository中实现
 *  2. es数据处理部分SearchConfigService中实现
 *  3. 文件处理部此类中实现
 */
class SearchConfigManager extends BaseService
{
    /**
     * @var SearchConfigService $configService
     */
    private $configService;

    /**
     * @var ElasticSearchConfigRepository $repository
     */
    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigService');
        $this->repository = app('App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository');
    }

    /**
     * 参数过滤
     *
     * @param string $type 参数类型
     * @param array $params 参数部分
     */
    private function paramsFilter($type, $params)
    {
        switch ($type) {
            case ConfigOptions::CONFIG_TYPE_RUN:
                $configs = ConfigOptions::ALL_RUN_CONFIGS;
                break;
            case ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH:
                $configs = ConfigOptions::ALL_GLOBAL_SEARCH_CONFIGS;
                break;
            default:
                return [];
        }
        $data = array_intersect_key($params, $configs);

        return $data;
    }

    /**
     * 处理配置参数, 将以英文逗号隔开的字符串转为数组
     *
     * @param array
     */
    private function handleParams($params)
    {
        array_walk($params, function (&$value) {
            $value = $this->handleArrayValue($value);
        });

        return $params;
    }

    /**
     * 处理value中数组值
     *
     * @param string|array $params
     *
     * @return string
     */
    private function handleArrayValue($params)
    {
        if (is_array($params)) {
            $data = json_encode($params);
        } else {
            if (strpos($params, ',')) {
                $toArr = explode(',',$params);
                $data = $this->handleArrayValue($toArr);
            } else {
                $data = $params;
            }
        }

        return $data;
    }

    /**
     * 更新指定类型的配置
     *
     * @param string    $type
     * @param array     $input
     */
    private function updateConfigByType($type, $input)
    {
        // 获取请求配置
        $params = Arr::pluck($input, 'value', 'key');
        // 参数格式处理
        $params = $this->handleParams($params);

        // 过滤有效参数
        $params = $this->paramsFilter($type, $params);

        // 获取该分类配置
        $originalAllConfigs = $this->repository->getAllConfigsByType($type, false);
        $originalConfigs = Arr::pluck($originalAllConfigs, 'value', 'key');

        // TODO 需移入repository
        if ($originalConfigs) {
            try {
                DB::beginTransaction();
                // 更新变动配置
                $diffConfigs = array_diff_assoc($params, $originalConfigs);
                array_walk($diffConfigs, function ($value, $key) use ($type) {
                    $this->repository->setConfigValueByKey($key, $value, $type);
                });

                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();

                return [];
            }
        }

        return $diffConfigs;
    }
    /**
     * 更新运行配置
     *
     *  1. 运行内存
     *  2. 日志位置
     */
    public function updateRunConfig(Request $request)
    {
        $input = $request->request->get('runConfigs');
        $isRestarted = $request->request->getBoolean('isRestarted');
        $type = ConfigOptions::CONFIG_TYPE_RUN;
        $result = $this->updateConfigByType($type, $input);
        $counter = 0;

        foreach ($result as $key => $value) {
            $this->updateConfigFile($key, $value);
            $counter++;
        }

        if ($counter) {
            $path = $this->getEsBasePathInfo();
            $esPathInfo = $path['esPath'];

            // 重启es服务
            $isRestarted && $this->restartService($esPathInfo);
        }
    }

    /**
     * 更新指定类型的配置文件
     *
     * @param   string $key
     * @param   string $value
     * @param   bool $isRestarted    是否重启服务
     *
     */
    private function updateConfigFile($key, $value, $isRestarted = false)
    {
        $path = $this->getEsBasePathInfo();
        $basePathInfo = $path['basePath'];
        $esPathInfo = $path['esPath'];

        switch ($key) {
            case ConfigOptions::JVM_MEMORY:
                // 建议内存n取值范围:2 - 32, 2n <= 系统内存
                $memory = (int)$value;
                $systemInfo = $this->getWindowsSystemMemory();
                $systemAllMemory = $systemInfo['TotalVisibleMemorySize'] / (1024 * 1024);

                if ($esPathInfo && $memory && ($memory < $systemAllMemory)) {
                    $this->configJvmMemory($esPathInfo, $memory);
                }
                break;
            case ConfigOptions::LOG_PATH:
                if ($esPathInfo && $value && $basePathInfo) {
                    $logsPathInfo = $this->getLogsDir($basePathInfo, $value);
                    $this->configLogsPath($esPathInfo, $logsPathInfo);
                }
                break;
            default:
                // do nothing
        }

        // 重启es服务
        $isRestarted && $this->restartService($esPathInfo);
    }

    /**
     * 更改ES日志目录
     *
     * @param string $pathInfo es目录
     * @param string $logsPathInfo 日志目录
     */
    private function configLogsPath($esPathInfo, $logsPathInfo)
    {
        $esConfigFile = $esPathInfo.'config/elasticsearch.yml';

        if (file_exists($esConfigFile)) {
            $esConfigContent = file_get_contents($esConfigFile);

            // 根据配置文件中的 #path.logs: 判断是否已启用该配置项
            if (preg_match('/#path.logs:\s/', $esConfigContent)) {
                $esConfigContent = preg_replace(
                    '/#(path.logs:)\s([A-Z]:)?([\\\|\/]{1,2}\w+)+[\\\|\/]?/i',
                    "$1 $logsPathInfo",
                    $esConfigContent
                );
            } else {
                $esConfigContent = preg_replace(
                    '#(path.logs:)\s([A-Z]:)?([\\\|/]{1,2}\w+)+[\\\|/]?#i',
                    "$1 $logsPathInfo",
                    $esConfigContent
                );
            }

            file_put_contents($esConfigFile, $esConfigContent);
        }
    }

    /**
     * 处理运行内存
     *
     * @param string $esPathInfo elasticsearch目录
     * @param int   $memory 运行内存, 单位G
     */
    private function configJvmMemory($esPathInfo, $memory)
    {
        $memoryLimit = $memory.'g';
        $jvmConfigFile = $esPathInfo.'config/jvm.options';

        if (file_exists($jvmConfigFile)) {
            $jvmConfigContent = file_get_contents($jvmConfigFile);

            // 为减少系统消耗, 建议堆内存最大最小值保持一致
            $jvmConfigContent = preg_replace(
                '/(-Xms|-Xmx)[1-9]+[m|g]/i',
                "$01$memoryLimit",
                $jvmConfigContent
            );

            file_put_contents($jvmConfigFile, $jvmConfigContent);
        }
    }

    /**
     * 重启ES服务
     *  // TODO: 无论ping restart 还是reinstall响应均太长 优化实现方式
     * @param string $basePathInfo
     * @param bool  $retry  重启失败时默认重新尝试一次
     */
    public function restartService($esPathInfo, $retry = true)
    {
        $commandBasePathInfo = $esPathInfo.'bin/';

        try {
            // 若es运行中则需要停止, 否则直接开启
            if ($this->configService->isElasticSearchRun()) {
                $this->managerEsService($commandBasePathInfo, 'stop');
            }

            $this->managerEsService($commandBasePathInfo, 'start');
        } catch (\Exception $exception) {
            if ($retry) {
                // 尝试重启前重新安装服务
                $this->reinstallService($esPathInfo);
                $this->restartService($esPathInfo, false);
            }
        }
    }

    /**
     * 重新安装ES服务
     *
     * @param string $basePathInfo
     */
    public function reinstallService($esPathInfo)
    {
        $commandBasePathInfo = $esPathInfo.'bin/';
        // TODO 判断service安装状态
        $this->managerEsService($commandBasePathInfo, 'remove');
        $this->managerEsService($commandBasePathInfo, 'install');
    }

    /**
     * ES服务, type的 4 种类型
     *  1. install
     *  2. remove
     *  3. start
     *  4. stop
     */
    private function managerEsService($esBinPathInfo, $type)
    {
        $command = $esBinPathInfo.'elasticsearch-service '.$type;
        exec($command);
    }

    /**
     * 版本更新时初始化
     */
    public function serviceConfigInit()
    {
        // 数据库初始化基本配置
        $this->repository->initConfig();

        // 初始化本地文件配置
        $path = $this->getEsBasePathInfo();
        $basePathInfo = $path['basePath'];
        $esPathInfo = $path['esPath'];
        // ES服务相关命令需要权限, 暂时无法初始化
        $this->configJvmMemory($esPathInfo, ConfigOptions::DEFAULT_JVM_MEMORY);
        $logsPathInfo = $this->getLogsDir($basePathInfo, ConfigOptions::DEFAULT_LOGS_PATH);
        $this->configLogsPath($esPathInfo, $logsPathInfo);

        // 安装ES服务
//        $this->reinstallService($esPathInfo.'bin/');

        // 添加 JAVA_HOME 环境变量
        $this->registerEnv();
    }

    /**
     * 注册环境部变量
     */
    private function registerEnv()
    {
        // 安全问题
//        $modulePath = __DIR__.'/../../';
//        $resourcePath = $modulePath.'./Resource';
//        $script = $resourcePath.'/script/registerEnv.bat';
//        if (file_exists($script)) {
//            exec($script);
//        }
    }

    /**
     * 获取配置接口
     */
    public function getConfigsByType(Request $request)
    {
        $type = $request->query->get('type');
        switch ($type) {
            case ConfigOptions::CONFIG_TYPE_RUN:
                return $this->repository->getRunConfigInfo(true);
            default:
                return[];
        }
    }

    /**
     * 获取全站搜索配置
     *
     * @param Request $request
     *
     * @return array
     */
    public function getGlobalSearchConfigInfo(Request $request)
    {
        $globalSearchConfigType = $request->query->get('globalSearchConfigType');
        $config = [];

        // 获取定时任务更新是否开启
        if ($globalSearchConfigType === ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE) {
            $config = $this->repository->getGlobalSearchUpdateConfig(
                ConfigOptions::UPDATE_BY_SCHEDULE,
                ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE
            );
        } elseif($globalSearchConfigType === ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE) {
            $config = $this->repository->getGlobalSearchUpdateConfig(
                ConfigOptions::UPDATE_BY_QUEUE,
                ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE
            );
        }

        return $config;
    }

    /**
     * 更新全站搜索配置
     *
     * @param Request $request
     * @param array $own
     *
     * @return array
     */
    public function setGlobalSearchConfig(Request $request, $own): array
    {
        $globalSearchConfigType = $request->request->get('globalSearchConfigType');
        $globalSearch = $request->request->get('globalSearchEnable');
        $userId = $own['user_id']  ?? 0;

        // 是否为修改全站定时任务配置
        if ($globalSearchConfigType === ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE) {

            $updatePeriod = $request->request->get('updatePeriod');
            $updateTime = $request->request->get('update_time');
            $updateByWeek = $request->request->get('updateByWeek');
            // 更新时间不能为空
            if (!$updateTime) {
                return ['code' => ['0x055001', 'elastic']]; // 系统异常
            }
            // 验证定时任务参数的有效性
            $extra = $this->getValidateParamsInSchedule($updatePeriod, $updateTime, $updateByWeek);
            $this->setGlobalSearchConfigSchedule($globalSearch,$userId, $extra);

        } elseif($globalSearchConfigType === ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE) {

            $extra = $request->request->get('extra');
            // 验证定时任务参数的有效性
            $extra = $this->getValidateParamsInQueue($extra);
            $this->setGlobalSearchConfigQueue($globalSearch, $userId, $extra);
        }

        return [];
    }

    /**
     * 全站搜索定时任务配置更新
     *
     * @param bool $globalSearch 是否开启
     * @param string|int $userId
     * @param array $extra
     */
    public function setGlobalSearchConfigSchedule($globalSearch, $userId, $extra): void
    {
        // 获取当前全站搜索定时任务相关配置
        $currentScheduleStatus = (bool)$this->repository->getConfigValue(
            ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
            ConfigOptions::UPDATE_BY_SCHEDULE
        );

        /** @var LogService $logService */
        $logService = app('App\EofficeApp\Elastic\Services\Log\LogService');

        // 若配置变更则进行更新
        if ($currentScheduleStatus !== $globalSearch) {
            $this->repository->updateGlobalSearchScheduleStatus($globalSearch);

            // 生成操作日志
            $logService->addGlobalSearchScheduleStatusLog($userId, $currentScheduleStatus, $globalSearch);
        }

        // 全站搜索允许配置则更新
        if ($globalSearch) {
            // 获取当前更新周期配置
            $currentParams[ConfigOptions::SCHEDULE_UPDATE_PERIOD] = $this->repository->getConfigValue(
                ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE,
                ConfigOptions::SCHEDULE_UPDATE_PERIOD
            );
            // 获取当前更新时间配置
            $currentParams[ConfigOptions::SCHEDULE_UPDATE_DAY_TIME] = $this->repository->getConfigValue(
                ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE,
                ConfigOptions::SCHEDULE_UPDATE_DAY_TIME
            );
            // 获取当前更新日期配置
            $currentParams[ConfigOptions::SCHEDULE_UPDATE_WEEK_TIME] = $this->repository->getConfigValue(
                ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE,
                ConfigOptions::SCHEDULE_UPDATE_WEEK_TIME
            );

            if ($currentParams !== $extra) {
                $this->repository->updateGlobalSearchScheduleConfig($extra);
                // 生成操作日志
                $logService->addGlobalSearchScheduleConfigLog($userId, $currentParams, $extra);
            }
        }

        // 删除缓存
        Redis::del(RedisKey::REDIS_SCHEDULE_CONFIG);
    }

    /**
     * 获取全站搜索定时任务有效参数
     *
     * @param string $updatePeriod 更新周期
     * @param string $updateByDay 更新时间
     * @param string $updateByWeek 更新日期
     *
     * @return array
     */
    private function getValidateParamsInSchedule($updatePeriod, $updateByDay, $updateByWeek): array
    {
        $extra = [];
        // 验证是否有为有效值
        $validPeriod = ConfigOptions::SCHEDULE_VALID_PERIOD;
        $validWeekTime = ConfigOptions::SCHEDULE_VALID_UPDATE_WEEK;

        if (in_array($updatePeriod, $validPeriod)) {
            $extra[ConfigOptions::SCHEDULE_UPDATE_PERIOD] = $updatePeriod;
        }
        if ($updateByDay) {

            // TODO 验证日期格式
            $extra[ConfigOptions::SCHEDULE_UPDATE_DAY_TIME] = $updateByDay;
        }
        if (in_array($updateByWeek, $validWeekTime)) {
            $extra[ConfigOptions::SCHEDULE_UPDATE_WEEK_TIME] = $updateByWeek;
        }

        return $extra;
    }

    /**
     * 获取全站搜索消息队列有效参数
     *
     * @param array $extra
     *
     * @return array
     */
    private function getValidateParamsInQueue($extra): array
    {
        $validParams = [];
        // 验证是否有为有效值
        foreach ($extra as $item) {
            if (in_array($item['key'], Constant::$allIndices)) {
                $validParams[] = $item;
            }
        }

        return $validParams;
    }

    /**
     * 全站搜索消息队列配置更新
     *
     * @param bool $globalSearch 是否开启
     * @param string|int $userId
     * @param array $extra 开启索引分类
     */
    public function setGlobalSearchConfigQueue($globalSearch, $userId, $extra): void
    {
        /** @var LogService $logService */
        $logService = app('App\EofficeApp\Elastic\Services\Log\LogService');
        // 获取当前配置, 若配置不一致则更新数据库
        $currentQueueStatus = (bool)$this->repository->getConfigValue(
            ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
            ConfigOptions::UPDATE_BY_QUEUE
        );

        if ($currentQueueStatus !== $globalSearch) {
            $this->repository->updateGlobalSearchQueueStatus($globalSearch);

            // 生成操作日志
            $logService->addGlobalSearchQueueStatusLog($userId, $currentQueueStatus, $globalSearch);
        }

        if ($globalSearch) {
            // 获取当前已开启队列更新的分类数组
            $currentQueueParams = $this->repository->getGlobalSearchQueueStartedCategory();
            // 获取请求中需要开启的数组
            $filterExtra = Arr::where($extra, function ($value, $key) {
                return $value['value'];
            });
            // 获取分类数组
            $queueParams = array_column($filterExtra, 'key');
            // 获取需要更新的分类(数据库中已开启的和请求中需开启的不一致的全部分类)
            $needToClose = array_diff($currentQueueParams, $queueParams); // 新关闭的
            $needToOpen = array_diff($queueParams, $currentQueueParams);   // 新开启的
            $diff = array_merge($needToClose, $needToOpen);

            if ($diff) {
                $neededChangeConfigs = [];
                foreach ($extra as $config) {
                    if (in_array($config['key'], $diff)) {
                        $neededChangeConfigs[] = $config;
                    }
                }

                $this->repository->updateGlobalSearchQueueConfig($neededChangeConfigs);

                // 写入操作日志
                $logService->addGlobalSearchQueueConfigLog($userId, $currentQueueParams, $queueParams);
            }

            // 处理开启的全部分类索引
            dispatch(new ElasticsearchIndexSyncJob($queueParams));
        }
    }

    /**
     * 根据分类获取索引
     *
     * @param Request $request
     *
     * @return  array
     */
    public function getSearchIndicesByCategory(Request $request)
    {
        $category = $request->query->get('category');
        $allIndices = $this->configService->getAllIndicesByCategory($category);
        $currentIndex = $this->configService->getIndexByAlias(Constant::ALIAS_PREFIX.$category);

        // TODO 删除 disable 和 enable
        $runIndices = array_fill_keys($allIndices, 'disable');

        if (isset($runIndices[$currentIndex])) {
            $runIndices[$currentIndex] = 'enable';
        }

        $tmp = [];

        foreach ($runIndices as $key => $value) {
            
            $tmp[]= ['key' => $key, 'value' => $value];
        }

        return ['allIndices' => $allIndices, 'currentIndex' => $currentIndex, 'runIndices' => $tmp];
    }

    /**
     * 别名切换
     *
     * @param Request $request
     *
     * @return array
     */
    public function switchIndicesVersion(Request $request)
    {
        // 搜索关键字
        $index = $request->request->get('index', '');
        $alias = $request->request->get('alias', '');

        $this->configService->switchAliasByIndex($index, $alias);

        return [];
    }

    /**
     * 获取指定分析器的分词
     *
     * @param Request $request
     *
     * @return string
     */
    public function getTokensByAnalyzer(Request $request): string
    {
        $analyzer = $request->query->get('analyzer', 'ik_smart');
        $text = $request->query->get('text', '');

        $content = '';

        if (in_array($analyzer, ['ik_smart', 'ik_max_word'])) {
            $result = $this->configService->getTokensByAnalyzer($analyzer, $text);

            if (isset($result['tokens'])) {
                $tokens = array_column($result['tokens'], 'token');

                $content = implode('，', $tokens);
            }
        }

        return $content;
    }

    /**
     * 获取热更新扩展词
     *
     * @param Request $request
     * @param $data
     */
    public function getDictWordsData(Request $request, $data)
    {
        $updated =  '2018-01-01 00:00:01';

        $lastModified = new \DateTime();
        $etag = md5($updated);

        $headers = $request->server->getHeaders();

        // test
        $synonymList = ['王者荣耀', '震荡检测载波侦听多路访问', '我爱北京天安门'];

        $response = new Response();

        if (isset($headers['IF_NONE_MATCH']) && $headers['IF_NONE_MATCH'] == 'W/' . '"' . $etag . '"') {
            return $response->setStatusCode(Response::HTTP_NOT_MODIFIED);
        }

        if (isset($headers['IF_MODIFIED_SINCE']) && $headers['IF_MODIFIED_SINCE'] == $updated) {
            return $response->setStatusCode(Response::HTTP_NOT_MODIFIED);
        }

        $response->setLastModified($lastModified);
        $response->setEtag($etag, true);
        $response->headers->set('Content-Type', 'text/plain');

        $result = implode(PHP_EOL, $synonymList);

        $response->setContent($result);

        return $response;
    }

    /**
     *  更新schema的analyzer
     *
     * @param Request $request
     */
    public function updateAnalyzer(Request $request): void
    {
        $analyzer = $request->request->get('analyzer');

        if ($analyzer === 'ik_smart') {
            $this->repository->updateAnalyzer(ConfigOptions::SCHEMA_ANALYZER_IK_SMART);
        } elseif($analyzer === 'ik_max_word') {
            $this->repository->updateAnalyzer(ConfigOptions::SCHEMA_ANALYZER_IK_MAX_WORD);
        }
    }

    /**
     * 获取schema的analyzer
     *
     * @return string
     */
    public function getAnalyzer(): string
    {
        return $this->repository->getAnalyzer();
    }

    /**
     * 获取索引更新进度
     *
     * @param Request $request
     *
     * @return array
     */
    public function getIndexUpdateProcess(Request $request): array
    {
        $result = [
            'process' => 0,
            'status' => '',
            'runningCategory' => '',
        ];

        $redisKey = RedisKey::REDIS_UPDATE;
        $data = Redis::get($redisKey);
        if ($data) {
            $data = json_decode($data, true);

            $result['status'] = $data['running'] ? 'running' : 'finished';
            if ($data['running']) {
                $result['runningCategory'] = $data['runningCategory'];
                $category = $data['runningCategory'];
                if ($data['category'][$category]['totalCount']) {
                    $process = intval(($data['category'][$category]['updateCount'] / $data['category'][$category]['totalCount']) * 100);
                    $result['process'] = $process;
                }
            }
        }

        return $result;
    }

    /**
     * 删除索引
     *
     * @param Request $request
     */
    public function deleteIndex(Request $request): void
    {
        $category = $request->request->get('category');
        $id = $request->request->get('id');

        $this->configService->deleteIndex($category, $id);
    }
}