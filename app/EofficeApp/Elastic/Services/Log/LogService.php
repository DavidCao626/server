<?php


namespace App\EofficeApp\Elastic\Services\Log;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Repositories\ElasticSearchOperationLogRepository;
use App\EofficeApp\Elastic\Repositories\ElasticSearchUpdateLogRepository;
use Illuminate\Http\Request;

class LogService extends BaseService
{
    /**
     * 更新日志模型操作库
     *
     * @var ElasticSearchUpdateLogRepository $updateLogRepository
     */
    private $updateLogRepository;

    /**
     * 操作日志模型操作库
     *
     * @var ElasticSearchOperationLogRepository $operationLogRepository
     */
    private $operationLogRepository;

    public function __construct()
    {
        parent::__construct();
        $this->updateLogRepository = app('App\EofficeApp\Elastic\Repositories\ElasticSearchUpdateLogRepository');
        $this->operationLogRepository = app('App\EofficeApp\Elastic\Repositories\ElasticSearchOperationLogRepository');
    }

    /**
     * 增加操作日志
     */
    public function addOperationLog($operator, $configType, $operation, $logContent)
    {
        $logData = [
            'log_content' => $logContent,
            'operator' => $operator,
            'log_ip' => getClientIp(),
            'config_type' => $configType,
            'operation' => $operation,
        ];

        // 完善日志信息
        $logData = $this->completeLogInfo($logData);

        // 写入日志
        $this->operationLogRepository->createLog($logData);
    }

    /**
     * 增加更新日志
     *
     * @param string $operator
     * @param string $category
     */
    public function addUpdateLog($operator, $category): void
    {
        // $logContent = $this->getUpdateLogContent($category);
        $logData = [
            'log_content' => $category,
            'operator' => $operator,
            'log_ip' => getClientIp(),
            'category' => $category
        ];

        // 完善日志信息
        $logData = $this->completeLogInfo($logData);

        // 写入日志
        $this->updateLogRepository->createLog($logData);
    }

    /**
     * 补充日志信息, 判断是否传了log_time,log_ip，没传，给默认值
     *
     * @param array $data
     *
     * @return array
     */
    private function completeLogInfo($data)
    {
        if (!isset($data['log_time'])) {
            $data['log_time'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['log_ip'])) {
            $data['log_ip'] = getClientIp();
        }

        // 是否为本地
        if ($data['log_ip'] == '127.0.0.1') {
            $data['ip_area'] = trans('systemlog.local');
        } else {
            $data['ip_area'] = '';
        }

        return $data;
    }

    /**
     * 生成ES更新记录日志备注
     *
     * @param string $category  索引分类
     */
    private function getUpdateLogContent($category)
    {
        $message = trans('elastic.update_index');

        if ($category === 'all') {
            $category = trans('elastic.all');
        } else {
            $category = trans('elastic.'.$category);
        }

        $message = $message.':'.$category;

        return $message;
    }

    /**
     * 生成 全站搜索 定时任务 开启状态 操作日志
     *
     * @param string $userId
     * @param bool $oldStatus
     * @param bool $newStatus
     */
    public function addGlobalSearchScheduleStatusLog($userId, $oldStatus, $newStatus)
    {
        $logContent = $this->getLogContentByScheduleStatus($oldStatus, $newStatus);
        $this->addOperationLog($userId, 'schedule', 'edit', $logContent);
    }

    /**
     * 获取全站搜索 定时任务 开启/关闭 状态 日志备注
     *
     * @param bool $oldStatus
     * @param bool $newStatus
     *
     * @return string
     */
    private function getLogContentByScheduleStatus($oldStatus, $newStatus)
    {
        $from = $oldStatus ? trans('elastic.open'): trans('elastic.close');
        $to = $newStatus ? trans('elastic.open'): trans('elastic.close');
        $logContent = trans('elastic.global_search').':'.trans('elastic.schedule').':'.$from.'->'.$to;

        return $logContent;
    }

    /**
     * 生成 全站搜索 定时任务 相关配置 操作日志
     *
     * @param string $userId
     * @param array $oldConfig
     * @param array $newConfig
     */
    public function addGlobalSearchScheduleConfigLog($userId, $oldConfig, $newConfig)
    {
        $logContent = $this->getLogContentByScheduleChange($oldConfig, $newConfig);
        $logContent && $this->addOperationLog($userId, 'schedule', 'edit', $logContent);
    }

    /**
     * 获取全站搜索定时任务开启状态日志备注
     *
     *  eg: 全站搜索:定时任务:每周更新,周一,7:00->每天更新,12:00
     *
     * @param array $oldConfig
     * @param array $newConfig
     *
     * @return  string
     */
    private function getLogContentByScheduleChange($oldConfig, $newConfig)
    {
        $from = $this->getScheduleConfigMessage($oldConfig);
        $to = $this->getScheduleConfigMessage($newConfig);

        if (!$from || !$to) {
            return '';
        }

        $logContent = trans('elastic.global_search').':'.trans('elastic.schedule').':'.$from.'->'.$to;

        return $logContent;
    }

    /**
     * 根据配置获取配置信息
     *
     * @param  array $config
     *
     * @return string
     */
    private function getScheduleConfigMessage($config)
    {
        $message = '';

        // 是否存在定时更新周期
        if (isset($config[ConfigOptions::SCHEDULE_UPDATE_PERIOD])) {
            $isUpdateByDay = $config[ConfigOptions::SCHEDULE_UPDATE_PERIOD] == ConfigOptions::SCHEDULE_UPDATE_PERIOD_DAY;

            if ($isUpdateByDay) {
                $message = trans('elastic.update_daily').','.$config[ConfigOptions::SCHEDULE_UPDATE_DAY_TIME];
            } else {
                $dayKey = array_search($config[ConfigOptions::SCHEDULE_UPDATE_WEEK_TIME], ConfigOptions::SCHEDULE_VALID_UPDATE_WEEK);
                $timeKey = $config[ConfigOptions::SCHEDULE_UPDATE_DAY_TIME];
                if ($timeKey && $dayKey) {
                    $day = trans('common.'.$dayKey);
                    $message = trans('elastic.update_weekly').','.$day.','.$timeKey;
                }
            }
        }

        return $message;
    }

    /**
     * 生成 全站搜索 消息队列 开启状态 操作日志
     *
     * @param string $userId
     * @param bool $oldStatus
     * @param bool $newStatus
     */
    public function addGlobalSearchQueueStatusLog($userId, $oldStatus, $newStatus)
    {
        $logContent = $this->getLogContentByQueueStatus($oldStatus, $newStatus);
        $this->addOperationLog($userId, 'queue', 'edit', $logContent);
    }

    /**
     * 获取全站搜索 消息队列 开启/关闭 状态 日志备注
     *
     * @param bool $oldStatus
     * @param bool $newStatus
     *
     * @return string
     */
    private function getLogContentByQueueStatus($oldStatus, $newStatus)
    {
        $from = $oldStatus ? trans('elastic.open'): trans('elastic.close');
        $to = $newStatus ? trans('elastic.open'): trans('elastic.close');
        $logContent = trans('elastic.global_search').':'.trans('elastic.queue').':'.$from.'->'.$to;

        return $logContent;
    }

    /**
     * 生成 全站搜索 消息队列 相关配置 操作日志
     *
     * @param string $userId
     * @param array $oldConfig
     * @param array $newConfig
     */
    public function addGlobalSearchQueueConfigLog($userId, $oldConfig, $newConfig)
    {
        // 写入操作日志
        $logContent = $this->getLogContentByQueueChange($oldConfig, $newConfig);
        $this->addOperationLog($userId, 'queue', 'edit', $logContent);
    }

    /**
     * 获取全站搜索消息队列更新开启索引日志备注
     *
     *  eg: 全站搜索:即时更新:索引 xxx,xxx -> xxx,xxx,xxx
     *
     * @param array $oldConfig
     * @param array $newConfig
     *
     * @return  string
     */
    private function getLogContentByQueueChange($oldConfig, $newConfig)
    {
        $from = $this->getQueueConfigMessage($oldConfig);
        $to = $this->getQueueConfigMessage($newConfig);

        if (!$from) {
            $from = trans('elastic.none');
        }

        if (!$to) {
            $to = trans('elastic.none');
        }

        $logContent = trans('elastic.global_search').':'.trans('elastic.queue').':'.trans('elastic.index').':'.$from.'->'.$to;

        return $logContent;
    }

    /**
     * 根据消息队列配置获取配置信息
     *
     * @param  array $config
     *
     * @return string
     */
    private function getQueueConfigMessage($config)
    {
        array_walk($config, function (&$item) {
            $item = trans('elastic.'.$item);
        });
        $message = implode(',', $config);

        return $message;
    }

    /**
     * 生成 新增扩展词 操作日志
     *
     * @param string $userId
     * @param string $newWords
     */
    public function addNewExtensionLog($userId, $newWords)
    {
        // 写入操作日志
        $logContent = $this->getLogContentByAddExtension($newWords);
        $this->addOperationLog($userId, 'extension_words', 'create', $logContent);
    }

    /**
     * 生成 移除扩展词 操作日志
     *
     * @param string $userId
     * @param string $oldWords
     */
    public function addRemoveExtensionLog($userId, $oldWords)
    {
        // 写入操作日志
        $logContent = $this->getLogContentByRemoveExtension($oldWords);
        $this->addOperationLog($userId, 'extension_words', 'delete', $logContent);
    }

    /**
     * 生成 编辑扩展词 操作日志
     *
     * @param string $userId
     * @param string $oldWords
     * @param string $newWords
     */
    public function addEditExtensionLog($userId, $oldWords, $newWords)
    {
        $logContent = $this->getLogContentByEditExtension($oldWords, $newWords);
        $this->addOperationLog($userId, 'extension_words', 'edit', $logContent);
    }
    /**
     * 生成 新增同义词 操作日志
     *
     * @param string $userId
     * @param string $newWords
     */
    public function addNewSynonymLog($userId, $newWords)
    {
        // 写入操作日志
        $logContent = $this->getLogContentByAddSynonym($newWords);
        $this->addOperationLog($userId, 'synonym_words', 'create', $logContent);
    }

    /**
     * 生成 移除同义词 操作日志
     *
     * @param string $userId
     * @param string $oldWords
     */
    public function addRemoveSynonymLog($userId, $oldWords)
    {
        // 写入操作日志
        $logContent = $this->getLogContentByRemoveSynonym($oldWords);
        $this->addOperationLog($userId, 'synonym_words', 'delete', $logContent);
    }

    /**
     * 生成 编辑同义词 操作日志
     *
     * @param string $userId
     * @param string $oldWords
     * @param string $newWords
     */
    public function addEditSynonymLog($userId, $oldWords, $newWords)
    {
        $logContent = $this->getLogContentByEditSynonym($oldWords, $newWords);
        $this->addOperationLog($userId, 'synonym_words', 'edit', $logContent);
    }

    /**
     * 获取修改扩展词操作备注
     *
     * eg: 扩展词:编辑: oldWords->newWords
     *
     * @param string $oldWords
     * @param string $newWords
     *
     * @return string
     */
    private function getLogContentByEditExtension($oldWords, $newWords): string
    {
        return trans('elastic.extension_words').':'.trans('elastic.edit').':'.$oldWords.'->'.$newWords;
    }

    /**
     * 获取新增扩展词操作备注
     *
     * eg: 扩展词:新增: newWords
     *
     * @param string $newWords
     *
     * @return string
     */
    private function getLogContentByAddExtension($newWords): string
    {
        return trans('elastic.extension_words').':'.trans('elastic.create').':'.$newWords;
    }

    /**
     * 获取移除扩展词操作备注
     *
     * eg: 扩展词:移除: newWords
     *
     * @param string $oldWords
     *
     * @return string
     */
    private function getLogContentByRemoveExtension($oldWords): string
    {
        return trans('elastic.extension_words').':'.trans('elastic.remove').':'.$oldWords;
    }


    /**
     * 获取修改同义词操作备注
     *
     * eg: 扩展词:编辑: oldWords->newWords
     *
     * @param string $oldWords
     * @param string $newWords
     *
     * @return string
     */
    private function getLogContentByEditSynonym($oldWords, $newWords): string
    {
        return trans('elastic.synonym_words').':'.trans('elastic.edit').':'.$oldWords.'->'.$newWords;
    }

    /**
     * 获取新增同义词操作备注
     *
     * eg: 扩展词:新增: newWords
     *
     * @param string $newWords
     *
     * @return string
     */
    private function getLogContentByAddSynonym($newWords): string
    {
        return trans('elastic.synonym_words').':'.trans('elastic.create').':'.$newWords;
    }

    /**
     * 获取移除同义词操作备注
     *
     * eg: 扩展词:移除: newWords
     *
     * @param string $oldWords
     *
     * @return string
     */
    private function getLogContentByRemoveSynonym($oldWords): string
    {
        return trans('elastic.synonym_words').':'.trans('elastic.remove').':'.$oldWords;
    }

    /**
     * 获取更新日志列表
     *
     * @param Request $request
     *
     * @return array
     */
    public function getUpdateRecordList(Request $request): array
    {
        $params = $request->all();
        $params = $this->parseParams($params);
        $count = $this->updateLogRepository->getUpdateRecordLogCount($params);

        $list = [];
        if ($count > 0) {
            $list = $this->updateLogRepository->getUpdateRecordLogList($params);
            $list = $this->formatUpdateRecordList($list);
        }

        return ['total' => $count, 'list' => $list];
    }

    /**
     * 获取操作日志列表
     *
     * @param Request $request
     *
     * @return array
     */
    public function getOperationRecordList(Request $request)
    {
        $params = $request->all();
        $params = $this->parseParams($params);
        $count = $this->operationLogRepository->getOperationLogCount($params);

        $list = [];
        if ($count > 0) {
            $list = $this->operationLogRepository->getOperationLogList($params);
            $list = $this->formatOperationRecordList($list);
        }

        return ['total' => $count, 'list' => $list];
    }

    /**
     * 格式化更新日志记录列表
     *
     * @param array $list
     *
     * @return array
     */
    private function formatUpdateRecordList($list): array
    {
        array_walk($list, function (&$item) {
            if ($item['operator'] === 'schedule') {
                $item['operator'] = trans('elastic.'.$item['operator']);
            }

            if (!empty($item['user_name'])) {
                $item['operator'] = $item['user_name'];
            }
            $item['log_content'] = trans('elastic.update_index').':'.trans('elastic.'.$item['category']);
            $item['category'] = trans('elastic.'.$item['category']);
        });

        return $list;
    }

    /**
     * 格式化操作日志记录列表
     *
     * @param array $list
     *
     * @return array
     */
    private function formatOperationRecordList($list): array
    {
        array_walk($list, function (&$item) {
            $item['config_type'] = trans('elastic.'.$item['config_type']);
            if ($item['operator'] === 'schedule') {
                $item['operator'] = trans('elastic.'.$item['operator']);
            }

            if (!empty($item['user_name'])) {
                $item['operator'] = $item['user_name'];
            }
        });

        return $list;
    }

    /**
     * 获取各类型索引手动更新时间
     */
    public function getIndexUpdateTimeByManual(): array
    {
        $repository = $this->updateLogRepository;
        $data = $repository->getIndexUpdateTimeByManual();

        return $data;
    }
}