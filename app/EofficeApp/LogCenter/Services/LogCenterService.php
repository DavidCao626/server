<?php

namespace App\EofficeApp\LogCenter\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\AddLogJob;
use App\Jobs\IpTransAreaJob;
use Queue;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\LogCenter\Traits\LogTrait;
/**
 * Description of LogCenterService
 *
 * 用户添加日志，以及各个模块服务端日志交互等
 *
 * @author lizhijun
 */
class LogCenterService extends BaseService
{
    use LogTrait;
    private $logRepository;
    private $elasticService;
    private $logStatisticsRepository;
    private $LogModuleConfigRepository;
    public function __construct()
    {
        $this->logRepository = 'App\EofficeApp\LogCenter\Repositories\LogRepository';
        $this->elasticService = 'App\EofficeApp\LogCenter\Services\ElasticService';
        $this->logStatisticsRepository = 'App\EofficeApp\LogCenter\Repositories\LogStatisticsRepository';
        $this->LogModuleConfigRepository = 'App\EofficeApp\LogCenter\Repositories\LogModuleConfigRepository';

        parent::__construct();
    }

    /**
     * 添加信息类日志，异步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function info($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 1);
    }

    /**
     * 添加错误类日志，异步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function error($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 3);
    }

    /**
     * 添加警告类日志，异步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function warning($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 2);
    }

    /**
     * 添加重大事件类日志，异步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function important($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 4);
    }

    /**
     * 添加信息类日志，同步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function syncInfo($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 1, false);
    }


    /**
     * 添加错误类日志，同步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function syncError($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 3, false);
    }

    /**
     * 添加警告类日志，同步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function syncWarning($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 2, false);
    }


    /**
     * 添加重大事件类日志，同步
     * @param $identifier
     * @param $data
     *
     * @param $historyData
     * @param $currentData
     * @return type
     */
    public function syncImportant($identifier, $data, $historyData = [], $currentData = [])
    {
        return $this->addLog($identifier, $data, $historyData, $currentData, 4, false);
    }


    /**
     * 添加日志逻辑处理函数
     * @param $identifier
     * @param $logData
     *
     * @param $historyData
     * @param $currentData
     * @param int $level
     * @param bool $async
     * @return type
     */
    public function addLog($identifier, $logData, $historyData, $currentData, $level = 1, $async = true)
    {

        list($moduleKey, $categoryKey, $operateKey) = explode('.', $identifier);
        if (isset($logData['content']) && is_array($logData['content'])) {
            $logData['content'] = json_encode($logData['content'], JSON_UNESCAPED_UNICODE);
            $contentType = 2;
        } else {
            $contentType = 1;
        }
        $platform = app('App\EofficeApp\Auth\Services\AuthService')->isMobile() ? 2 : 1;
        if(isset($logData['platform'])){
            $platform = $logData['platform'];
        }
        $extraData = [
            'log_category' => $categoryKey,
            'log_operate' => $operateKey,
            'log_level' => $level,
            'ip' => getClientIp() ?? '127.0.0.1', //获取为空的时候默认 127.0.0.1 ？
            'log_content' => $logData['content'],
            'log_content_type' => $contentType,
            'platform' =>  $platform,
            'log_time' => date('Y-m-d H:i:s'),
            'operate_path' => $logData['operate_path'] ?? '',
        ];
        $data = array_merge($logData, $extraData);//将日志数据组装
        unset($data['content']);
        if ($async) {
            $params = [
                'module_key' => $moduleKey,
                'log_data' => $data,
                'history_data' => $historyData,
                'current_data' => $currentData
            ];
            return Queue::push(new AddLogJob($params));
        }
        return $this->addLogTerminal($moduleKey, $data, $historyData, $currentData);
    }

    /**
     * 往数据和es添加日志数据
     * @param $moduleKey
     * @param $logData
     * @param $historyData
     * @param $currentData
     * @return type|bool
     */
    public function addLogTerminal($moduleKey, $logData, $historyData, $currentData)
    {
        $relationTable = $logData['relation_table'] ?? null;
        $relationId = $logData['relation_id'] ?? null;
        if ($relationTable && $relationId && ($historyData || $currentData)) {
            $logData['has_change'] = 1;
        } else {
            $logData['has_change'] = 0;
        }
        $logId = app($this->logRepository)->addLogData($moduleKey, $logData);
        if (!$logId) {
            return $logId;
        }


        // 往Elastic添加日志
        $logData['module_key'] = $moduleKey;
        $logData['log_id'] = $logId;
        app($this->elasticService)->addModuleLog($logData);

        // 添加关联数据的历史变更记录
        if ($relationTable && $relationId) {
            return $this->addChangeData($logData, $relationId, $historyData, $currentData);
        }
        return true;
    }

    /**
     * 添加关联数据的历史变更记录
     * @param $logData
     * @param $relationId
     * @param $historyData
     * @param $currentData
     * @return bool
     */
    public function addChangeData($logData, $relationId, $historyData, $currentData)
    {
        $moduleKey = $logData['module_key'];
        $logId = $logData['log_id'];
        $relationTable = $logData['relation_table'];
        $change = $this->makeChange($relationTable);
        if ($change && !empty($historyData)) {
            $diffData = $this->arrayCompare($historyData, $currentData); // update_time 字段变更就不需要解析吧
            if (empty($diffData)) {
                return true;
            }
            $parseData = $change->parseData($diffData , $logData);
            $saveData = $this->combineDiffData($diffData, $parseData, $logId, $relationId, $relationTable);
            return app($this->logRepository)->addChangeData($moduleKey, $saveData);
        }
        return true;
    }

    public function getLogCount($moduleKey ,$relationId ,$relationTable ='', $logParams = []){
        if(empty($moduleKey) || empty($relationId)){
            return false;
        }
        $logs = [];
        foreach ($relationId as $k => $v){
            $params['search']['relation_id'] = [strval($v), '='];
            if(!empty($relationTable)){
                $params['search']['relation_table'] = [$relationTable, '='];
            }
            if(isset($logParams['userId']) && !empty($logParams['userId'])) {
                $params['search']['creator'] = [$logParams['userId'], '='];
            }
            if(isset($logParams['operate']) && !empty($logParams['operate'])) {
                $params['search']['log_operate'] = [$logParams['operate'], 'in'];
            }
            $logs[$v] = app($this->logRepository)->getLogCount($params ,$moduleKey);

        }
        return $logs;
    }

    public function getLogs($moduleKey ,$relationId ,$relationTable ='', $logParams = []){
        if(empty($moduleKey) || empty($relationId)){
            return false;
        }
        $logs = [];
        foreach ($relationId as $k => $v){
            $params['search']['relation_id'] = [strval($v), '='];
            if(!empty($relationTable)){
                $params['search']['relation_table'] = [$relationTable, '='];
            }
            if(isset($logParams['userId']) && !empty($logParams['userId'])) {
                $params['search']['creator'] = [$logParams['userId'], '='];
            }
            if(isset($logParams['operate']) && !empty($logParams['operate'])) {
                $params['search']['log_operate'] = [$logParams['operate'], 'in'];
            }
            $logs[$v] = app($this->logRepository)->getLogs($params ,$moduleKey);
        }
        return $logs;
    }

    public function getRead($moduleKey ,$userIds ,$relationTable = '', $logParams = []){
        if(empty($moduleKey) || empty($userIds)){
            return false;
        }
        $logs = [];
        foreach ($userIds as $k => $v){
            $params['search']['creator'] = [strval($v), '='];
            $params['search']['log_operate'] = [['view', 'edit'], 'in'];
            if(!empty($relationTable)){
                $params['search']['relation_table'] = [$relationTable, '='];
            }
            if(isset($logParams['date_range'])){
                $dateRange = explode(',', $logParams['date_range']);
                if (isset($dateRange[0]) && !empty($dateRange[0])) {
                    $params['search']['log_time'] = [$dateRange[0] . ' 00:00:00', '>='];
                }
                if (isset($dateRange[1]) && !empty($dateRange[1])) {
                    $params['search']['log_time'] = [$dateRange[1] . ' 23:59:59', '<='];
                }
                if (isset($dateRange[0]) && !empty($dateRange[0]) && isset($dateRange[1]) && !empty($dateRange[1])) {
                    $params['search']['log_time'] = [[$dateRange[0] . ' 00:00:00', $dateRange[1] . ' 23:59:59'], 'between'];
                }
            }
            $logs[$v] = app($this->logRepository)->getLogCount($params ,$moduleKey);
        }
        return $logs;
    }

    private function combineDiffData($originalDiffData, $diffData, $logId, $dataId, $relationTable)
    {
        $saveData = [];
        foreach ($diffData as $key => $value) {
            list($from, $to) = $value;
            list($originalFrom, $originalTo) = $originalDiffData[$key];
            $saveData[] = [
                'field' => $key,
                'original_from' => is_array($originalFrom) ? json_encode($originalFrom) : $originalFrom,
                'from' => is_array($from) ? json_encode($from) : $from,
                'original_to' => is_array($originalTo) ? json_encode($originalTo) : $originalTo,
                'to' => is_array($to) ? json_encode($to) : $to,
                'log_id' => $logId,
                'relation_id' => $dataId,
                'relation_table' => $relationTable,
            ];
        }
        return $saveData;
    }

    /**
     * 两个数组的值进行比较
     *
     * @param array $consult
     * @param array $target
     *
     * @return array
     */
    private function arrayCompare($consult, $target)
    {
        if (empty($consult)) {
            return [];
        }
        $diff = [];
        $jsonKeys = [];
        foreach ($consult as $key => $value) {
            if (!isset($target[$key])) {
                continue;
            }
            $targetValue = $target[$key];
            $this->arrayToJson($targetValue, $jsonKeys, $key);
            $this->arrayToJson($value, $jsonKeys, $key);
            if ($value != $targetValue) {
                $diff[$key] = [$value, $targetValue];
            }
        }
        array_walk($diff, function (&$item, $key) use ($jsonKeys) {
            if (in_array($key, $jsonKeys)) {
                $item[0] = json_decode($item[0], true);
                $item[1] = json_decode($item[1], true);
            }
        });
        return $diff;
    }

    /**
     *
     * @param array $data
     *
     * @return string
     */
    private function arrayToJson(&$data, &$jsonKeys, $key)
    {
        if (is_array($data)) {
            $data = json_encode($data);
            if (!in_array($key, $jsonKeys)) {
                $jsonKeys[] = $key;
            }
        }
    }


    /**
     * 获取系统日志统计入口
     * @param  array $param 查询条件
     * @return bool
     */
    public function getLogStatistics($param = [])
    {
        ini_set("max_execution_time", "3600");
        $function = 'parseStatistics' . ucwords($param['getBy']);
        if (method_exists($this, $function)) {
            return $this->$function();
        }
        return false;
    }


    /**
     * 获取系统日志统计时间
     *
     * @param  array $param 查询条件
     *
     * @return bool
     */
    public function parseStatisticsTime()
    {
        $date = date('N');
        $weekStart = $date == 1 ? date('Y-m-d 00:00:00', strtotime('this monday')) : date('Y-m-d 00:00:00', strtotime('last monday'));
        $end = date('Y-m-d 23:59:59');

        $statistics = [
            'day' => ['log_time' => [[date('Y-m-d 00:00:00'), $end], 'between']],
            'week' => ['log_time' => [[$weekStart, $end], 'between']],
            'month' => ['log_time' => [[date('Y-m-01 00:00:00'), $end], 'between']],
            'year' => ['log_time' => [[date('Y-01-01 00:00:00'), $end], 'between']],
        ];

        $data = [];
        foreach ($statistics as $k => $v) {
            $data[$k] = app($this->logStatisticsRepository)->getLogStatistics($v);
        }
        return $data;
    }

    /**
     * 获取系统日志统计周
     *
     * @param  array $param 查询条件
     *
     * @return array 查询数量
     */
    public function parseStatisticsWeek()
    {
        $date = date('N');
        $weekStart = $date == 1 ? date('Y-m-d', strtotime('this monday')) : date('Y-m-d', strtotime('last monday'));
        $where = ['log_time' => [[$weekStart . ' 00:00:00', date('Y-m-d 23:59:59')], 'between']];
        $result = app($this->logStatisticsRepository)->getLogStatisticsWeek($where)->toArray();
        $data = array_column($result, 'num', 'LEFT(log_time, 10)');
        $week = [];

        for ($i = 0; $i < 7; $i++) {
            $weekDate = date('Y-m-d', strtotime("$weekStart + $i day"));
            $week[$i + 1] = isset($data[$weekDate]) ? $data[$weekDate] : 0;
        }
        return $week;
    }

    /**
     * 获取系统日志统计年
     *
     * @param  array $param 查询条件
     *
     * @return array 查询数量
     */
    public function parseStatisticsYear()
    {
        $where = ['log_time' => [[date('Y-01-01 00:00:00'), date('Y-m-d 23:59:59')], 'between']];
        $result = app($this->logStatisticsRepository)->getLogStatisticsYear($where)->toArray();
        $data = array_column($result, 'num', 'LEFT(log_time, 7)');
        $month = [];

        for ($i = 1; $i <= 12; $i++) {
            $yearMonth = date('Y-') . str_pad($i, 2, "0", STR_PAD_LEFT);
            $month[$i] = isset($data[$yearMonth]) ? $data[$yearMonth] : 0;
        }
        return $month;
    }

    /**
     * 获取系统日志统计
     *
     * @param  array $param 查询条件
     *
     * @return array 查询数量
     *
     */
    public function parseStatisticsArea()
    {
        $result = app($this->logStatisticsRepository)->getLogIpArea()->toArray();

        $count = [];
//        if (!Redis::exists('ip_list')) {
//            foreach ($result as $k => $v) {
//                $res = $this->getIpLookup($v['ip']);
//                Redis::hSet('ip_list', $res['ip'], empty($res['pro']) ? '本地' : $res['pro']);
//            }
//        }
        $localhostProvince = envOverload('SERVER_PROVINCE');

        foreach ($result as $k => $v) {
            $name = $this->ipArea($v['ip'] , 'pro');
            if ($name) {
                if (isset($count[$name])) {
                    $count[$name] += $v['num'];
                } else {
                    $count[$name] = $v['num'];
                }
            } else {
                //$res = $this->ipArea($v['ip']);
//                Redis::hSet('ip_list', $v['ip'], empty($res['pro']) ? '本地' : $res['pro']);
                if (isset($count[$localhostProvince])) {
                    $count[$localhostProvince] += $v['num'];
                } else {
                    $count[$localhostProvince] = $v['num'];
                }
            }

        }

        $provinces = $this->getProvince();
        $data = [];
        foreach ($provinces as $key => $value) {
            foreach ($count as $k => $v) {
                if (mb_substr($value, 0, 2) !== mb_substr($k, 0, 2)) {
                    $data[$key] = 0;
                } else {
                    $data[$key] = $v;
                    break;
                }
            }
        }
        $data['SouthChinaSeaIslands'] = 0; // 配合前端显示，默认写入南海诸岛为0的数据，服务端应该暂未统计南海诸岛
        return $data;
    }



    public function getAddressFromIpUrl()
    {
        return config('eoffice.getAddressFromIpUrl');
    }

    /**
     * 开始es时进行数据同步
     */
    public function syncLog(){
        $elasticService = app($this->elasticService);
        $index = config('elastic.logCenter.index');
        if($elasticService->exists($index)){
            $elasticService->deleteIndex($index);
        }
        $elasticService->syncLogToElasticSearch();
    }

    private function getProvince(){
        return [
            'Beijing'           =>      '北京',
            'Tianjin'           =>      '天津',
            'Shanghai'          =>      '上海',
            'Chongqing'         =>      '重庆',
            'Hebei'             =>      '河北',
            'Henan'             =>      '河南',
            'Yunnan'            =>      '云南',
            'Liaoning'          =>      '辽宁',
            'Heilongjiang'      =>      '黑龙江',
            'Hunan'             =>      '湖南',
            'Anhui'             =>      '安徽',
            'Shandong'          =>      '山东',
            'Xinjiang'          =>      '新疆',
            'Jiangsu'           =>      '江苏',
            'Zhejiang'          =>      '浙江',
            'Jiangxi'           =>      '江西',
            'Hubei'             =>      '湖北',
            'Guangxi'           =>      '广西',
            'Gansu'             =>      '甘肃',
            'Shanxi'            =>      '山西',
            // 'Inner Mongolia'    =>      '内蒙古',
            'Neimenggu'    		=>      '内蒙古',
            'Shaanxi'           =>      '陕西',
            'Jilin'             =>      '吉林',
            'Fujian'            =>      '福建',
            'Guizhou'           =>      '贵州',
            'Guangdong'         =>      '广东',
            'Qinghai'           =>      '青海',
            'Tibet'             =>      '西藏',
            'Sichuan'           =>      '四川',
            'Ningxia'           =>      '宁夏',
            'Hainan'            =>      '海南',
            'Taiwan'            =>      '台湾',
            // 'Hong Kong'         =>      '香港',
            'Xianggang'         =>      '香港',
            'Macau'             =>      '澳门',
        ];

    }
}
