<?php


namespace App\EofficeApp\Elastic\Services\ServiceManagementPlatform;


use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Lang\Services\LangService;
use App\EofficeApp\Menu\Services\UserMenuService;
use App\Jobs\Elasticsearch\ElasticsearchJob;
use App\Jobs\Elasticsearch\ElasticsearchQueueStatusJob;
use Elasticsearch\ClientBuilder as ESClientBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/**
 * Class ManagementPlatformService
 * @package App\EofficeApp\Elastic\Services\ServiceManagementPlatform
 */
class ManagementPlatformService
{
    public function menuRegister()
    {
        /** @var UserMenuService $menuService */
        $menuService = app('App\EofficeApp\Menu\Services\UserMenuService');
        //添加菜单
        $menuService->addSystemMenuFunc(
            297,
            "全文索引",
            1,
            280,
            0,
            280,
            27
        );
        $menuService->checkUserMenu();
        //清空菜单缓存
        $menuService->clearCache();

        /** @var LangService $langService */
        $langService = app('App\EofficeApp\Lang\Services\LangService');
        $menu = DB::table("menu")->where('menu_id', 297)->first();
        if (substr($menu->menu_name, 0, 5) != "menu_") {
            $langKey = "menu_297";
            $enMenuName = $langService->eofficeTranslate($menu->menu_name);

            $this->addMenuLang($langKey, $menu->menu_name, 'lang_zh_cn');
            $this->addMenuLang($langKey, $enMenuName, 'lang_en');

            $this->clearTokenCache();

            $this->addGlobalSearchItem();

            return DB::table("menu")->where('menu_id', 297)->update(["menu_name" => $langKey]);
        }

        //移动端不展示 更新排序
        DB::table('menu')->where('menu_id', 297)->update(['in_mobile' => 2, "menu_order" => 27]);

        $this->clearTokenCache();

        $this->addGlobalSearchItem();

        return true;
    }

    /**
     * 添加导航栏中全站搜索选项
     */
    private function addGlobalSearchItem()
    {
        // 默认升级后暂不开启全站搜索, 需在系统管理->导航栏设置->全局搜索 中改动
        if($data = get_system_param('global_search_item', null)) {
            $data = json_decode($data, true);
            if (isset($data['all'])) {
                if (!in_array('website', $data['all'])) {
                    array_unshift($data['all'], 'website');
                }
            }

            if (isset($data['checked'])) {
                if (in_array('website', $data['checked'])) {
                    $key = array_search('website' ,$data['checked']);
                    if ($key === 0) {
                        array_shift($data['checked']);
                    }
                }
            }

            set_system_param('global_search_item', json_encode($data));
            Cache::forget('global_search_item');
        }
    }

    /**
     * 开启导航栏中全站搜索选项
     */
    public function openGlobalSearchItem()
    {
        // 默认升级后暂不开启全站搜索, 需在系统管理->导航栏设置->全局搜索 中改动
        if($data = get_system_param('global_search_item', null)) {
            $data = json_decode($data, true);
            if (isset($data['all'])) {
                if (!in_array('website', $data['all'])) {
                    array_unshift($data['all'], 'website');
                }
            }

            if (isset($data['checked'])) {
                if (!in_array('website', $data['checked'])) {
                    array_unshift($data['checked'], 'website');
                }
            }

            set_system_param('global_search_item', json_encode($data));
            Cache::forget('global_search_item');

            $this->clearGlobalSearchItemCache();
        }
    }

    /**
     * 清除searchItem相关缓存
     */
    public function clearGlobalSearchItemCache()
    {
        ecache('Portal:GlobalSearchItem')->delAll();
    }
    /**
     * 关闭导航栏中全部搜索选项
     */
    private function removeGlobalSearchItem()
    {
        if($data = get_system_param('global_search_item', null)) {
            $data = json_decode($data, true);
            if (isset($data['all'])) {
                if (in_array('website', $data['all'])) {
                    $key = array_search('website' ,$data['all']);
                    if ($key === 0) {
                        array_shift($data['all']);
                    }
                    set_system_param('global_search_item', json_encode($data));
                    Cache::forget('global_search_item');
                }
            }

            if (isset($data['checked'])) {
                if (in_array('website', $data['checked'])) {
                    $key = array_search('website' ,$data['checked']);
                    if ($key === 0) {
                        array_shift($data['checked']);
                    }
                    set_system_param('global_search_item', json_encode($data));
                    Cache::forget('global_search_item');
                }
            }
        }

        $this->clearGlobalSearchItemCache();
    }

    /**
     * 清空全部用户缓存
     */
    private function clearTokenCache()
    {
        ecache('Auth:RefreshToken')->delAll();
        ecache('Auth:RefreshTokenGenerateTime')->delAll();
    }

    /**
     * @see Update::addMenuLang() TODO 时间来不及 后续考虑优化 updateOrInsert
     * @param $langKey
     * @param $langValue
     * @param string $langTable
     * @return bool|int
     */
    private function addMenuLang($langKey, $langValue, $langTable = 'lang_zh_cn')
    {
        $item = DB::table($langTable)->where('table', 'menu')
                                     ->where('column', 'menu_name')
                                     ->where('option', 'menu_name')
                                     ->where("lang_key", $langKey)
                                     ->first();
        if (!$item) {
            $data = [
                "lang_key" => $langKey,
                "lang_value" => $langValue,
                "table" => "menu",
                "column" => "menu_name",
                "option" => "menu_name",
            ];

            return DB::table($langTable)->insert($data);
        }

        return DB::table($langTable)->where('table', 'menu')
                                    ->where('column', 'menu_name')
                                    ->where('option', 'menu_name')
                                    ->where("lang_key", $langKey)
                                    ->update(['lang_value' => $langValue]);
    }

    /**
     * 是否为可注册版本
     *
     * @return bool
     */
    public function isAcceptableVersion(): bool
    {
        // 已运行升级脚本即可注册
        return Schema::hasTable(ElasticTables::ELASTIC_CONFIG_TABLE);
    }

    /**
     * 是否已注册es菜单
     *
     * @return bool
     */
    public function hasRegistered(): bool
    {
        // 拥有菜单即为注册
        return (bool) DB::table('menu')->where('menu_id', 297)->first();
    }

    /**
     * 移除es菜单
     *
     * @return bool
     */
    public function removeMenu(): bool
    {
        // 清空相关菜单信息
        app('App\EofficeApp\Menu\Repositories\MenuRepository')->deleteByWhere(['menu_id' => [297]]);
        app('App\EofficeApp\Menu\Repositories\UserMenuRepository')->deleteByWhere(['menu_id' => [297]]);
        app('App\EofficeApp\Menu\Repositories\RoleMenuRepository')->deleteByWhere(['menu_id' => [297]]);

        //清空菜单缓存
        /** @var UserMenuService $userService */
        $userService = app('App\EofficeApp\Menu\Services\UserMenuService');
        $userService->clearCache();

        $this->removeGlobalSearchItem();

        return true;
    }

    /**
     * es数据迁移
     *
     * @throws \Exception
     */
    public function migrationData()
    {
        $redisKey = RedisKey::REDIS_UPDATE;

        $updateInfo = Redis::get($redisKey);
        $updateInfo = json_decode($updateInfo, true);
        $structure = $this->getElasticUpdateByQueueStructure();

        if ($updateInfo && isset($updateInfo['all']['status']) && $updateInfo['all']['status'] !== 'prepared') {
            // 已经经过或正在数据迁移，无法重复执行
            if ($updateInfo['all']['status'] === 'finished') {
                $this->afterDataMigration();
            }
            return;
        } else if($this->isDataMigrated()) {
            $finishedCategory = array_values(Constant::$allIndices);
            // 更新redis数据
            $structure['all'] = [
                'status' => 'finished',
                'finished' => $finishedCategory,
                'prepared' => [],
            ];
            $redisValue = json_encode($structure);
            Redis::set($redisKey, $redisValue);

            $this->afterDataMigration();

            return;
        }

        $structure['running'] = true;
        $structure['all']['status'] = 'running';
        $structure['all']['prepared'] = Constant::$allIndices;

        $redisValue = json_encode($structure);
        Redis::set($redisKey, $redisValue);

        // 添加各分类索引数量
        $this->getAllIndicesCount();

        dispatch(new ElasticsearchJob([
            'type' => ConfigOptions::QUEUE_UPDATE_FUNCTION_REINDEX,
            'data' => [
                'type' => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
            ],
        ]));
    }

    /**
     * 获取全部索引数量
     */
    public function getAllIndicesCount()
    {
        $redisKey = RedisKey::REDIS_UPDATE;
        $updateInfo = Redis::get($redisKey);
        if ($updateInfo) {
            $updateInfo = json_decode($updateInfo, true);

            $categories = Constant::$allIndices;
            $totalCount = 0;
            foreach ($categories as $category) {
                $baseBuilder = app('App\EofficeApp\Elastic\Services\Search\BaseBuilder');
                $builder = $baseBuilder->getBuilder($category);
                $entity = app($builder->entity);
                $query = $entity->newQuery();
                $count = $query->count();
                $totalCount += $count;
                $updateInfo['category'][$category]['totalCount'] = $count;
            }

            foreach ($categories as $category) {
                $proportion = $updateInfo['category'][$category]['totalCount'] / $totalCount;
                $proportion = round($proportion, 5);
                $updateInfo['category'][$category]['proportion'] = $proportion;
            }

            $redisValue = json_encode($updateInfo);
            Redis::set($redisKey, $redisValue);
        }
    }

    /**
     * 获取es队列更新结构体
     *
     * @return array
     */
    static public function getElasticUpdateByQueueStructure()
    {
        // todo 对应es通过消息队列更新, 暂定结构为下(后续优化) 定义为常量并优化结构
        $elasticQueueInfo = [
            'running' => false,  // 队列更新是否进行中, 同一时间只允许存在一个进行中的更新
            'category' => [],   // 更新的分类
            'runningCategory' => '',    // 更新中的索引分类
            'all' => [          // 全部跟新时使用 只有管理平台/手动存在
                'status' => 'prepared',  // 全部更新状态  prepared/running/finished
                'finished' => [],   // 已完成的分类
                'prepared' => [],   // 待更新的分类
            ],
        ];

        $allCategories = Constant::$allIndices;

        // 获取全部索引分类
        foreach ($allCategories as $category) {
            $elasticQueueInfo['category'][$category] = [
                'totalCount' => 0,      // 单分类总数
                'updateCount' => 0,     // 单分类已更新数
                'proportion' => 0,      // 单分类总数占全部分类的比例
            ];
        }

        return $elasticQueueInfo;
    }

    /**
     * 获取es数据迁移状态
     *
     * @throws \Exception
     */
    public function getMigrationData()
    {
        $redisKey = RedisKey::REDIS_UPDATE;
        $updateInfo = Redis::get($redisKey);
        if (!$updateInfo) {
            $updateInfo = self::getElasticUpdateByQueueStructure();
        } else {
            $updateInfo = json_decode($updateInfo, true);;
        }

        /**
         * 处理获取迁移数据
         *  从redis中获取相关迁移数据
         *  1.1 获取正在迁移中可直接返回
         *  1.2 获取迁移结束可正常返回
         *  1.3 redis被清空, 则获取所有分类, 整理相关数据形式存入redis后返回
         */
        $category = $updateInfo['runningCategory'];
        $finishedCategory = array_values($updateInfo['all']['finished']);
        $preparedCategory = array_values($updateInfo['all']['prepared']);
        if ($category && $updateInfo['category'][$category]['totalCount']) {
            $rate = intval(($updateInfo['category'][$category]['updateCount'] / $updateInfo['category'][$category]['totalCount']) * 100);
            // $currentCategoryUpdateProcess = intval(($updateInfo['category'][$category]['updateCount'] / $updateInfo['category'][$category]['totalCount']) * 100);
            $currentCategoryUpdateProcess = $updateInfo['category'][$category]['updateCount'];
        } else {
            $currentCategoryUpdateProcess = 0;
            $rate = 0;
        }

        // 如果没有正在进行中的更新, 则为未开始或已结束
        if (!$updateInfo['running'] && !$finishedCategory && !$preparedCategory) {
            if ($this->isDataMigrated()) {
                $finishedCategory = array_keys(Constant::$allIndices);
                // 更新redis数据
                $updateInfo['all'] = [
                    'status' => 'finished',
                    'finished' => $finishedCategory,
                    'prepared' => [],
                ];
                $redisValue = json_encode($updateInfo);
                Redis::set($redisKey, $redisValue);
            }
        }

        // 获取全部分类迁移进度
        $allCategoriesUpdateProcess = $this->getAllCategoriesUpdateProcess(
            $updateInfo['running'],
            $finishedCategory,
            $category,
            $preparedCategory
        );

        // 获取全部分类百分比进度
        $allCategoriesPercentageProcess = $this->getAllCategoriesPercentageProcess(
            $category,
            $preparedCategory,
            $finishedCategory,
            $rate
        );
        $allCategoriesPercentageProcess = round($allCategoriesPercentageProcess, 4);
        $allCategoriesPercentageProcess = $allCategoriesPercentageProcess > 1 ? 1 : $allCategoriesPercentageProcess;
        $allCategoriesPercentageProcess = $allCategoriesPercentageProcess * 100;

        return [
            'isRunning' => $updateInfo['running'],
            'runningCategory' => $category,
            'finishedCategory' => $finishedCategory,
            'preparedCategory' => $preparedCategory,
            'currentCategoryUpdateProcess' => $currentCategoryUpdateProcess,
            'allCategoriesUpdateProcess' => $allCategoriesUpdateProcess,
            'allCategoriesPercentageProcess' => $allCategoriesPercentageProcess,
        ];
    }

    /**
     * 获取全部分类百分比进度
     *
     * @return string
     */
    public function getAllCategoriesPercentageProcess(
        $runningCategory,
        $preparedCategory,
        $finishedCategory,
        $currentCategoryUpdateProcess
    ) {
        $redisKey = RedisKey::REDIS_UPDATE;
        $updateInfo = Redis::get($redisKey);
        if ($updateInfo) {
            $updateInfo = json_decode($updateInfo, true);;
            /**
             * 1. 若无正在进行的分类且无待处理的分类， 则已完成， 进度为 100%
             * 2. 若无正在进行的分类且无已完成的分类， 则未开始， 进度为 0%
             * 3. 若有正在进行的分类 则根据各模块获取相关进度比例
             */
            if (!$runningCategory) {
                if (count($finishedCategory) === 0) {
                    return 0;
                }

                if (count($preparedCategory) === 0) {
                    return 1;
                }
            }

            $categories = $updateInfo['category'];
            $total = 0;

            foreach ($categories as $name => $category) {
                if (in_array($name, $finishedCategory)) {
                    $total += $category['proportion'];
                }

                if ($name === $runningCategory) {
                    $total += $category['proportion'] * $currentCategoryUpdateProcess / 100;
                }
            }

            return $total;
        }

        return 0;
    }

    /**
     * 获取全部分类迁移进度
     *
     * @param $isRunning
     * @param $finishedCategory
     * @param $category
     * @param $preparedCategory
     * @return string
     */
    public function getAllCategoriesUpdateProcess($isRunning, $finishedCategory, $category, $preparedCategory)
    {
        /**
         * 获取全部分类迁移进度
         *  1. 若正在进行迁移，则进度为 已完成/全部[准备数量+已完成数量+1(这里未正在进行)]
         *  2. 若未正在进行，且准备为空，则进度为 全部/全部
         *  3. 若未正在进行，且准备不为空，则进度未 0/全部
         */
        if ($isRunning) {
            $finishedCategoryCount = count($finishedCategory);
            if ($category) {
                $allCategoryCount = $finishedCategoryCount + count($preparedCategory) + 1;
            } else {
                $allCategoryCount = $finishedCategoryCount + count($preparedCategory);
            }

            $allCategoriesUpdateProcess = $finishedCategoryCount.'/'.$allCategoryCount;
        } else {
            if (count($preparedCategory)) {
                dd($finishedCategory);
                $allCategoryCount = count($finishedCategory);
                $allCategoriesUpdateProcess = '0/'.$allCategoryCount;
            } else {
                $allCategoryCount = count($finishedCategory);
                $allCategoriesUpdateProcess = $allCategoryCount.'/'.$allCategoryCount;
            }
        }

        return $allCategoriesUpdateProcess;
    }

    /**
     * 判断数据是否已迁移成功
     *
     * @return bool
     */
    public function isDataMigrated()
    {
        // 从配置文件读取 Elasticsearch 服务器列表
        $config = config('elastic.elasticsearch.hosts');
        if (is_string($config)) {
            $config = explode(',', $config);
        }
        $builder = ESClientBuilder::create()->setHosts($config);

        $client = $builder->build();
        $indices = array_keys(Constant::$allIndices);

        try {
            $response = $client->count([
                'index' => $indices
            ]);
        } catch (\Exception $exception){
            Log::error($exception->getTraceAsString());

            return false;
        }

        if (isset($response['count']) && $response['count']) {
            return true;
        }

        return false;
    }

    /**
     * 处理redis测试数据
     *
     */
    public function dealTestDataInRedis($isEmptyData)
    {
        if ($isEmptyData === 'false') {
            $isEmptyData = false;
        }

        $redisKey = RedisKey::REDIS_UPDATE;

        if ($isEmptyData) {
            Redis::del($redisKey);

            return [];
        }

        $data = Redis::get($redisKey);

        if (!$data) {
            return [];
        }

        return json_decode($data, true);
    }

    /**
     * 开始手动更新时判断队列是否开启
     *
     * @return int
     */
    public function getQueueStatusBeforeUpdateByManual()
    {
        $queueName = 'queues:'.Constant::ELASTIC_QUEUE;  // 待处理任务的队列的redis的key
        $reservedQueueName = $queueName.':reserved';    // 正在处理任务的队列的redis的key
        /**
         * 获取es队列状态
         *  1. 当待处理任务的队列key存在且正在处理队列的key不存在，则队列未启动
         *  2. 当正在处理任务的队列的key存在则队列正在进行中
         */
        $queue = Redis::keys($queueName);
        if (!$queue) {
            dispatch(new ElasticsearchQueueStatusJob());
        }

        $queue = Redis::keys($queueName);
        $reservedQueue = Redis::keys($reservedQueueName);
        if ($queue && !$reservedQueue) {
            usleep(500000); // 延迟0.5s处理， 以便job进入处理队列
            $queue = Redis::keys($queueName);
            $reservedQueue = Redis::keys($reservedQueueName);
            if ($queue && !$reservedQueue) {
                return 1;   // 未开启
            }
        }

        return 0;   // 已开启
    }

    /**
     * 判断正在进行手动更新的队列是否因意外停止
     *
     * @return array[]
     */
    public function getQueueStatusInUpdateByManual()
    {
        $queueName = 'queues:'.Constant::ELASTIC_QUEUE;  // 待处理任务的队列的redis的key
        $reservedQueueName = $queueName.':reserved';    // 正在处理任务的队列的redis的key
        try {
            /**
             *  1. 正在elasticsearch队列的reserved中存在job
             *  2. 根据job参数无法请求es或者生成文档数不变化
             */
            $queue = Redis::keys($reservedQueueName);

            if (!$queue) {
                return ['status' => 'ok', 'count' => 0];
            }
            $queue = Redis::zRange ($reservedQueueName,0,-1);
            if (!$queue) {
                return ['status' => 'ok', 'count' => 0];
            }
            // 如果有值 目前只有一个处理中的job
            $jsonStr = $queue[0];
            $jsonArr = json_decode($jsonStr, true);
            $data = $jsonArr['data'];
            $command = $data['command'];
            $job = unserialize($command);

            if ($job instanceof ElasticsearchJob) {
                $jobParams = $job->params;
                // 手动更新类型均为按类型索引 且 存在分类
                if (($jobParams['type'] === 'typeReindex') && $jobParams['data']['category']) {
                    // 从配置文件读取 Elasticsearch 服务器列表
                    $config = config('elastic.elasticsearch.hosts');
                    if (is_string($config)) {
                        $config = explode(',', $config);
                    }
                    $builder = ESClientBuilder::create()->setHosts($config);
                    $client = $builder->build();

                    /**
                     * 1. 获取所有指定分类开头的索引
                     * 2. 获取该分类下最新生成的
                     * 3. 获取生成文档数量，若持续未变化，则任务队列已关闭（再比较正常分类相差是否很大）
                     */
                    $params = ['index' => 'eoffice_'.$jobParams['data']['category'].'_*'];
                    $response = $client->cat()->indices($params);

                    $indices = array_column($response, 'index');

                    $lastedIndex = '';
                    $lastedTimestamp = 0;
                    foreach ($indices as $index) {
                        $indexParams = explode('_', $index);
                        $version = array_pop($indexParams);
                        $timestamp = str_replace('v', '', $version);
                        if ($timestamp > $lastedTimestamp) {
                            $lastedTimestamp = $timestamp;
                            $lastedIndex = $index;
                        }
                    }
                    if ($lastedIndex) {
                        $response = $client->count(['index' => $lastedIndex]);
                        $count = $response['count'];
                        return ['status' => 'update', 'count' => $count];
                    }
                }
            }

            return ['status' => 'ok', 'count' => 0];
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            if ($message === 'No alive nodes found in your cluster') {
                return ['status' => 'stop', 'count' => 0];
            } else {
                return ['status' => 'error', 'count' => 0];
            }
        }
    }

    /**
     * 清除es队列相关缓存
     */
    public function clearUpdateProcess()
    {
        $redisKey = RedisKey::REDIS_UPDATE;
        $queueName = 'queues:'.Constant::ELASTIC_QUEUE;  // 待处理任务的队列的redis的key
        $reservedQueueName = $queueName.':reserved';    // 正在处理任务的队列的redis的key
        Redis::del($redisKey, $reservedQueueName);
    }

    /**
     * 获取插件开启状态
     *
     * @return int   0:关闭 1:开启
     */
    public function getPlugInStatus()
    {
        // 根据服务管理平台接口获取服务状态
        $basePath    = base_path();
        $installPath = dirname(dirname(dirname($basePath)));
        $exePath     = $installPath . DS . "bin" . DS . "systemservice". DS . "systemservice.exe";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (file_exists($exePath)) {
                // system函数调用底层程序
                system($exePath. ' ' . '-u' . ' '.'eoffice_search'. ' '. '-n'. ' '. '-1', $elasticsearchStatusOutput);
                if ($elasticsearchStatusOutput > 0) {
                    return 1;
                }
            }
        } else {
            // linux 平台上通过ps -aux | grep elasticsearch 判断是否启动队列服务
            system('ps aux |grep elasticsearch > /tmp/temp_run_elasticsearch.txt');
            if (file_exists('/tmp/temp_run_elasticsearch.txt')) {
                $contentArr = file('/tmp/temp_run_elasticsearch.txt');
                // 可能存在文件写入进程，过滤写进程
                array_walk($contentArr, function (&$str) {
                    $match = preg_match('/(temp_run_elasticsearch)|(grep)/', $str);
                    if($match) {
                        $str = '';
                    }
                });
                $contentArr = array_filter($contentArr);

                if (count($contentArr)) {
                    return 1;
                }
            }
        }

        return 0;

        $cacheKey = RedisKey::REDIS_PLUG_IN_STATUS;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $status = get_system_param(Constant::PLUG_STATUS, Constant::PLUG_STATUS_OFF);
        Cache::forever($cacheKey, $status);

        return $status;
    }

    /**
     * 设置插件开启状态
     *
     * @param int $status  0:关闭 1:开启
     *
     * @return void
     */
    public function setPlugInStatus($status = Constant::PLUG_STATUS_OFF)
    {
        $cacheKey = RedisKey::REDIS_PLUG_IN_STATUS;
        set_system_param(Constant::PLUG_STATUS, $status);
        Cache::forget($cacheKey);
    }

    /**
     * 开启插件时数据迁移，成功后相关逻辑
     */
    public function afterDataMigration()
    {
        $this->openGlobalSearchItem();
        $this->setPlugInStatus(1);
        app('App\EofficeApp\LogCenter\Facades\LogCenter')::syncLog();
    }
}