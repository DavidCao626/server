<?php

namespace App\EofficeApp\System\Log\Services;

use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use DB;
use Illuminate\Support\Facades\Log;
use Schema;
use App\EofficeApp\Base\BaseService;
use Illuminate\Database\Schema\Blueprint;
use Queue;
use App\Jobs\getUserIpArea;
use Illuminate\Support\Facades\Redis;
/**
 * 系统日志service
 *
 * @author  齐少博
 *
 * @since  2016-07-01 创建
 */
class LogService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->logRepository = 'App\EofficeApp\System\Log\Repositories\LogRepository';
        $this->logStatisticsRepository = 'App\EofficeApp\System\Log\Repositories\LogStatisticsRepository';
        $this->newlogStatisticsRepository= 'App\EofficeApp\LogCenter\Repositories\LogStatisticsRepository';

    }

    /**
     * 添加日志
     *
     * @param  array $data 日志信息
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-07-01 创建
     */
    public function createLog($data)
    {
        // 判断是否传了log_time,log_ip，没传，给默认值
        if (!isset($data['log_time'])) {
            $data['log_time'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['log_ip'])) {
            $data['log_ip'] = getClientIp();
        }
        
        $data['log_ip'] = empty($data['log_ip']) ? '127.0.0.1' : $data['log_ip'];
        if ($data['log_ip'] == '127.0.0.1') {
            $data['ip_area'] = trans('systemlog.local');
        } else {
            // 获取用户id 和ip
            $ip = $data['log_ip'];
            $id = $data['log_creator'];
            $data['ip_area'] = trans('systemlog.local');
            // 如何是登录(手机,微信等)，则不进行ip地址查询
            // if ($data['log_type'] != 'PC' && $data['log_type'] != 'mobile' && $data['log_type'] != 'pwderror' && $data['log_type'] != 'ilip' && $data['log_type'] != 'erroruname') {
            //     try {
            //         $address = $this->GetIpLookup($data['log_ip']);
            //         if (!empty($address['province'])) {
            //             $prefix = 'China ';
            //             $province = convert_pinyin($address['province']);
            //             $data['ip_area'] = $prefix . ucfirst($province[0]);
            //         } else {
            //             $data['ip_area'] = '局域网';
            //         }
            //     } catch (\Exception $e) {
            //         $data['ip_area'] = '局域网';
            //     }
            // };
        }
        if (empty($data['log_time'])) {
            $data['log_time'] = date('Y-m-d H:i:s');
        }
        if (isset($data['module']) && !empty($data['module'])) {
            $tableName = 'system_' . $data['module'] . '_log';
            unset($data['module']);
            //判断表是否存在
            if (!Schema::hasTable($tableName)) {
                //新建新表
                Schema::create($tableName, function (Blueprint $table) {
                    $table->increments('log_id')->comment('日志id');
                    $table->char('log_creator', 12)->comment('记录人');
                    $table->char('log_type', 30)->comment('日志类型');
                    $table->dateTime('log_time')->comment('记录时间');
                    $table->char('log_ip', 16)->comment('记录ip');
                    $table->string('ip_area', 100)->comment('ip区域')->nullable();
                    $table->string('log_relation_table', 50)->comment('关联表');
                    $table->string('log_relation_id', 10)->comment('关联表主键');
                    $table->string('log_relation_field', 50)->comment('关联表字段');
                    $table->text('log_content')->comment('备注');
                });
            }

            if (Schema::hasTable($tableName)) {
                // 如果为登录表，则先查出iparea地址，再把登录整体数据存入redis，一个小时后，所有登录数据集体插入数据库
                if ($tableName == 'system_login_log') {
                    $this->getAndSaveIpArea($data,$data['log_ip'],'login');
                    return true;
                } else {
                    $autoIncreaseId = DB::table($tableName)->insertGetId($data,'log_id'); // 获取自增id
                }
            //app($this->logRepository)->createSubEntity($tableName,'System\Log');
            }
        } else {
            $tableName = 'system_log';
            //重写实体文件，方便列表页的删除操作
            // app($this->logRepository)->createSubEntity($tableName, 'System\Log');
            // $comboboxObj = app($this->logRepository)->insertData($data);
            $autoIncreaseId = DB::table($tableName)->insertGetId($data,'log_id'); // 获取自增id
        }
        // if (isset($autoIncreaseId) && !empty($autoIncreaseId)) {
        //     // 请求api，更新日志ip_area字段
        //     if (isset($ip) && isset($id)) {
        //         $this->getAndSaveIpArea($id,$ip,$autoIncreaseId);
        //     }
        // }

        // 通过消息队列更新日志索引
        if (isset($tableName) && isset($autoIncreaseId)) {
            try {
                ElasticsearchProducer::sendGlobalSearchSystemLogMessage($tableName, $autoIncreaseId);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
            }
        }

        return true;
    }

    /**
     * 查询系统日志列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-01
     */
    function getLogList($param)
    {
        $param = $this->parseParams($param);
        return $this->getSystemLogList($param);
    }

    /**
     * 更新日志
     *
     * @param  array $data 更新数据
     * @param  array $where 查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-10-20
     */
    function updateLog($data, $where)
    {
        return app($this->logRepository)->updateData($data, $where);
    }

    /**
     * 查询系统日志列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-01
     */
    function getSystemLogList($param,$pageFrom="")
    {
        $data = $this->response(app($this->logRepository), 'getLogTotal', 'getLogList', $param);
        // 客户信息关联
        if (isset($param['search']['log_relation_table'])) {
            return $data;
        }

        $provinces = trans('province');
        if (!empty($data['list'])) {
            $logTypes = config('eoffice.systemLogType');
            $logTypeNames = trans('systemlog');
            foreach ($data['list'] as $k => $v) {
                // 定义流程日志，解析
                if(isset($v['log_type']) && $v['log_type'] == "definedFlow" && $pageFrom == "export") {
                    $logContent = isset($v["log_content"]) ? $v["log_content"] : "";
                    if($logContent) {
                        $logContentArray     = json_decode($logContent,true);
                        $logContentTitle     = isset($logContentArray["title"]) ? $logContentArray["title"] : "";
                        $logContentOperation = isset($logContentArray["operation"]) ? $logContentArray["operation"] : "";
                        $logContentData      = isset($logContentArray["data"]) ? $logContentArray["data"] : "";
                        $logContentString    = $logContentTitle.$logContentOperation.trans("systemlog.log_data")."：".json_encode($logContentData);
                        $data['list'][$k]["log_content"] = $logContentString;
                    }
                }

                // 20200108,zyx,旧的流程流水号初始化日志形式为json，需要解析
                if (isset($v['log_type']) && $v['log_type'] == "initFlowRunSeq") {
                    $logContentArray = isset($v["log_content"]) ? json_decode($v["log_content"], TRUE) : "";
                    if ($logContentArray) {
                        $logContent = (isset($logContentArray['run_id']) ? ("run_id: " . $logContentArray['run_id'] . ", ") : "") . (isset($logContentArray['run_seq']) ? ("run_seq: " . $logContentArray['run_seq']) : "");
                        $data['list'][$k]["log_content"] = $logContent;
                    }
                }

                // 如果用户名错误,则log_creator里面直接显示用户名,如果不是用户名错误，替换log_creator为用户名
                if ($v['log_type'] != 'erroruname') {
                    //根据用户id选出用户名
                    $userId = $v['log_creator'];
                    // 判断能否查出用户名
                    if (isset($userId)) {
                        $name = DB::table('user')->select('user_name')->where('user_id', $userId)->first();
                        if (isset($name)) {
                            $data['list'][$k]['log_creator'] = $name->user_name;
                        };
                    }
                }

                // 如果没有设置搜素条件，则默认为电脑端登录
                if (!isset($param['search']['log_type'][0])) {
                    $data['list'][$k]['log_type_name'] = $logTypeNames['loginPC'];
                } else {
                    $data['list'][$k]['log_type_name'] = isset($logTypeNames[$param['search']['log_type'][0]]) ? $logTypeNames[$param['search']['log_type'][0]] : '';
                }

                // 判断ip_area中是否有地址，没有则调用接口查询地域,并存入数据库
                if (!isset($v['ip_area']) || empty($v['ip_area'])) {
                    // 127.0.0.1
                    if ($v['log_ip'] != '127.0.0.1') {
                        $v['ip_area'] = $this->getIpArea($v['log_ip']);
                         $data['list'][$k]['ip_area'] = $v['ip_area'];
                    } else {
                        $v['ip_area'] = trans('systemlog.local');
                        $data['list'][$k]['ip_area'] = trans('systemlog.local');
                    }
                    $where = [];
                    $logId = $v['log_id'];
                    $where = [ 'log_id' => $logId ];
                    $updateNum = $this->updateIpArea($v, $where, $param['search']['log_type'][0]);
                }

                foreach ($provinces as $key => $val) {
                    if (strpos($v['ip_area'], $key) !== false) {
                        $area = explode(' ',$v['ip_area']);
                        if (isset($area['2'])) {
                            $city = $area['2'];
                            $data['list'][$k]['ip_area'] = $val. ' ' .$city;
                        }else {
                            $data['list'][$k]['ip_area'] = $val;
                        }
                    } else if ($v['ip_area'] == 'localhost' || $v['ip_area'] == '127.0.0.1') {
                        $data[$k]['ip_area'] = $logTypeNames["localhost"];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 更新日志中的用户登录模块地址
     * @$data 更新的数据
     * @$where 更新的条件
     * @return bool
     */
    public function updateIpArea($data, $where, $module)
    {
        $data = ['ip_area' => $data['ip_area']];
        $tableName = $this->IpGetArea($module);
        $result = DB::table($tableName)
                    ->where($where)
                    ->update($data);
        return $result;
    }

    /**
     * 根据传入的搜索条件获取表名
     * $module 更新的数据
     * @return bool
     */
    public function IpGetArea($module)
    {
        $logTypes = config('eoffice.systemLogType');
        foreach ($logTypes as $k => $v) {
            if (is_array($v)) {
                if (strpos($module, $k) !== false) {
                    return $tableName = 'system_' . $k . '_log';
                }
            } else {
                if ($k === $module) {
                    return $tableName = 'system_log';
                }
            }
        }
    }
    /**
     *获取ip对应的地址名称
     * @ip  获取ip
     * @return 返回ip地址对应的城市
     *
     */
    public function getIpArea($ip = '')
    {
        if (!$ip) {
            return '';
        }
        $data = [];
        try {
            $address = $this->GetIpLookup($ip);
            // if (!empty($address['province'])) {
            if (!empty($address['pro'])) {
                $prefix = 'China ';
                // $province = convert_pinyin($address['province']);
                //陕西省为 shaanxisheng,其他为正常拼音
                if(isset($address['pro']) && strpos($address['pro'],"陕西") !== false ){
                    $province = array(
                        "shaanxisheng",
                        "sxs"
                    );
                }else{
                    $province = convert_pinyin($address['pro']);
                }
                $city = $address['city'];
                // $city = $this->transEncoding($city,'UTF-8');
                $data['ip_area'] = $prefix . ucfirst($province[0]) . ' ' . $city;
                // $data['ip_area'] = $prefix . ucfirst($province[0]) ;
            } else {
                $data['ip_area'] = trans('systemlog.local_network');
            }
        } catch (\Exception $e) {
            $data['ip_area'] = trans('systemlog.local_network');
        }
        return $data['ip_area'];
    }

    /**
     * 字符串编码转换
     * @param  [string] $string [要转换的内容]
     * @param  [string] $target [要转换的格式]
     * @return [string]         [description]
     */
    private function transEncoding($string, $target)
    {
        $encoding = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);

        return iconv($encoding, $target, $string);
    }

    /**
     * 查询系统日志类型列表
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-01
     */
    public function getLogTypeList()
    {
        $logTypes = config('eoffice.systemLogType');
        $logTypeNames = trans('systemlog');
        $data = [];
        foreach ($logTypes as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $s) {
                    $data[] = [
//                    'log_type'  => $v['type'],
                        'log_type' => $k . $s,
                        'log_type_name' => isset($logTypeNames[$k . $s]) ? $logTypeNames[$k . $s] : $k . $s,
//                    'log_action'=> $s
                    ];
                }
            } else {
                $data[] = [
                    'log_type' => $k,
                    'log_type_name' => isset($logTypeNames[$v]) ? $logTypeNames[$v] : $v
                ];
            }
        }
        return $data;
    }

    /**
     * 删除系统日志
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-07-01
     */
    function deleteLog($params)
    {
        $tableName = isset($params['tableName'][0]) ? $params['tableName'][0] : '';
        $id = isset($params['id']) ? $params['id'] : '';
        if (!$tableName) {
            return false;
        }
        $tableName = app($this->logRepository)->getLogTypeModule($tableName);
        $logIds = array_filter(explode(',', $id));
        if (!empty($logIds)) {
            $deleteResult = "";
            foreach ($logIds as $k => $v) {
                $deleteResult = DB::table($tableName)->where('log_id', $v)->delete();
            }
            return $deleteResult;
            // return app($this->logRepository)->deleteById($logIds);
        }

        return false;
    }

    /**
     * 获取系统日志数量
     *
     * @param  array $param 查询条件
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-07-05
     */
    function getLogTotal($param)
    {
        return app($this->logRepository)->getLogTotal($param);
    }

    /**
     * 获取系统日志统计入口
     *
     * @param  array $param 查询条件
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-07-07
     */
    function getLogStatistics($param = [])
    {
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
     *
     * @author qishaobo
     *
     * @since  2016-07-08
     */
    function parseStatisticsTime()
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
     *
     * @author qishaobo
     *
     * @since  2016-07-08
     */
    function parseStatisticsWeek()
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
     *
     * @author qishaobo
     *
     * @since  2016-07-07
     */
    function parseStatisticsYear()
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
     * 获取系统访问人数
     * @param $param
     * @return array
     */
    function getSystemVisitors($param)
    {
        $type = $param['type'] ?? 0;
        $year = $param['year'] ?? date('Y');
        $data = [];

        $dataMonth = [];
        $where = ['log_time' => [[date($year.'-01-01 00:00:00'), date($year.'-m-d 23:59:59')], 'between']];
        $result = app($this->newlogStatisticsRepository)->getLogStatisticsYear($where)->toArray();
        $resultArr = array_column($result, 'num', 'LEFT(log_time, 7)');
        for ($i = 1; $i <= 12; $i++) {
            $yearMonth = $year .'-' . str_pad($i, 2, "0", STR_PAD_LEFT);
            $dataMonth[$i] = isset($resultArr[$yearMonth]) ? $resultArr[$yearMonth] : 0;
        }
        $count = array_sum($dataMonth);

        //type：0、周 1、季度 2、年度
        if($type == 0){
            $date = date('N');
            $weekStart = $date == 1 ? date($year.'-m-d', strtotime('this monday')) : date($year.'-m-d', strtotime('last monday'));
            $weekWhere = ['log_time' => [[$weekStart . ' 00:00:00', date($year.'-m-d 23:59:59')], 'between']];
            $weekResult = app($this->newlogStatisticsRepository)->getLogStatisticsWeek($weekWhere)->toArray();
            $weekResultArr = array_column($weekResult, 'num', 'LEFT(log_time, 10)');
            for ($i = 0; $i < 7; $i++) {
                $weekDate = date($year.'-m-d', strtotime("$weekStart + $i day"));
                $data[$i + 1] = isset($weekResultArr[$weekDate]) ? $weekResultArr[$weekDate] : 0;
            }
        }else{
            if($type == 1){
                $dataQuarterly = [0,0,0,0];
                for ($i = 1; $i <= 12; $i++) {
                    $yearMonth = $year .'-'. str_pad($i, 2, "0", STR_PAD_LEFT);
                    $dataMonth[$i] = isset($resultArr[$yearMonth]) ? $resultArr[$yearMonth] : 0;
                    if($i <= 3){
                        $dataQuarterly[0] = $dataQuarterly[0] + $dataMonth[$i];
                    }else if($i <= 6 && $i > 3){
                        $dataQuarterly[1] = $dataQuarterly[1] + $dataMonth[$i];
                    }else if($i <= 9 && $i > 6){
                        $dataQuarterly[2] = $dataQuarterly[2] + $dataMonth[$i];
                    }else{
                        $dataQuarterly[3] = $dataQuarterly[3] + $dataMonth[$i];
                    }
                }
                $data = $dataQuarterly;
            }else{
                $data = $dataMonth;
            }
        }
        return ['value' => $data, 'count' => $count];
    }

    /**
     * 获取系统日志统计
     *
     * @param  array $param 查询条件
     *
     * @return array 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-07-08
     */
    function parseStatisticsArea()
    {
        // 每当请求系统日志界面时，取出redis中的数据插入system_login_log表中
        $datas = Redis::hGetAll('system_login_log');
        if (isset($datas) && !empty($datas)) {
            foreach ($datas as $k => $v) {
                $data = unserialize($v);
                $result = DB::table('system_login_log')->insert($data);
            }
        }
        Redis::del('system_login_log');
        $result = app($this->logStatisticsRepository)->getLogIpArea()->toArray();
        $provinces = trans('province');
        $data = [];
        foreach ($provinces as $k => $v) {
            $data[$k] = 0;

            if (!empty($result)) {
                $data[$k] = 0;
                foreach ($result as $val) {
                    if (strpos($val['ip_area'], $k) !== false) {
                        $data[$k] += $val['num'];
                    }
                }
            }
        }
        $data['SouthChinaSeaIslands'] = 0; // 配合前端显示，默认写入南海诸岛为0的数据，服务端应该暂未统计南海诸岛
        return $data;
    }

    public function GetIpLookup($ip = '')
    {
        if (empty($ip)) {
            return false;
        }
        // $url = $this->getAddressFromIpUrl() . '?format=js&ip=' . $ip;
        $url = $this->getAddressFromIpUrl() . '?ip=' . $ip;

        $res = @file_get_contents($url);
        $res = $this->transEncoding($res,'UTF-8');
        $res = str_replace([ 'if(window.IPCallBack) {IPCallBack(' , ');}' ], ['',''],  $res);
        $res = trim($res,' ');
        $res = json_decode($res,true);
        if (empty($res)) {
            return false;
        }
        // $jsonMatches = array();
        // preg_match('#\{.+?\}#', $res, $jsonMatches);
        // if (!isset($jsonMatches[0])) {
        //     return false;
        // }
        // $json = json_decode($jsonMatches[0], true);
        // if (isset($json['ret']) && $json['ret'] == 1) {
        //     $json['ip'] = $ip;
        //     unset($json['ret']);
        // } else {
        //     return false;
        // }

        // return $json;
        return $res;
    }

    /**
     * 登录后异步请求，获取并保存iparea
     *
     * @return bool
     */
    public function getAndSaveIpArea($data,$ip='',$param)
    {
        if (!$data) {
            return '';
        }
        if (!$ip) {
            return '';
        }
        if (!$param) {
            return '';
        }
        $param = [
            'data' => $data,
            'ip' => $ip,
            'login' => $param  // 是否是登录
        ];
        Queue::push(
            new getUserIpArea(['handle' => 'IpArea', 'param' => $param]) //请求ip地址任务
        );
        // 测试用
        // $this->queueIpArea($param);
        return '';
    }

    /**
     * 更新登录表中的ip_area的地址
     * @param  array $param 传入的id和ip数组
     * @return ''
     */
    public function queueIpArea($param)
    {
        $data = isset($param['data'])? $param['data'] : '';
        $ip = isset($param['ip'])? $param['ip'] : '';
        $isLogin = isset($param['login'])? $param['login']: '' ; // 是否是登录表
        if (!$data || !$isLogin) {
            return false;
        }
        // 替换ip_area的地址信息，
        if ($ip != '127.0.0.1') {
            $data['ip_area'] = $this->getIpArea($ip);
        }

        // 判断是否为登录表
        if ($isLogin == 'login') {
            // 存入redis,此时$data就是存入数据库的数据

            // 生成不重复的key
            $key = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $data = serialize($data);
            // 存入redis
            $result = Redis::hSet('system_login_log',$key,$data);
            // 测试用:是否操作redis成功
            // $datas = Redis::hGetAll('system_login_log');
            // if (isset($datas) && !empty($datas)) {
            //     foreach ($datas as $k => $v) {
            //     $data = unserialize($v);
            //     $result = DB::table('system_login_log')->insert($data);
            //     }
            // }
            // Redis::del('system_login_log');

            return true;
        }
        // $update = ['ip_area' => $ipArea];
        // $result = DB::table('system_login_log')
        //     // ->where([
        //     //     ['log_creator', '=', $id],
        //     //     ['log_ip'     , '=', $ip],
        //     //     ['ip_area'    , '=', '']
        //     //     ])
        //     ->where([
        //         ['log_id', '=', $autoIncreaseId]
        //         ])
        //     ->update($update);
        return '';
    }

    public function getAddressFromIpUrl()
    {
        return config('eoffice.getAddressFromIpUrl');
    }

    /**
     * 导出系统日志
     * @param  [array] $params [导出条件]
     * @return [array]         [导出数据]
     * @author longmiao
     */
    public function exportSystemLog($params)
    {
        if (!isset($params['search']) && empty($params['search']) && !isset($params['search']['log_type'])) {
            return false;
        }
        $header = [
            'log_type'    => ['data' => trans('systemlog.log_type'),'style' => ['width' => '15']],
            'log_time'    => ['data' => trans('systemlog.operate_time'),'style' => ['width' => '25']],
            'log_creator' => ['data' => trans('systemlog.operate_user'),'style' => ['width' => '15']],
            'log_ip'    =>   ['data' => trans('systemlog.operate_address'),  'style' => ['width' => '15']],
            'ip_area'    =>  ['data' => trans('systemlog.ip_area'),  'style' => ['width' => '15']],
            'log_content' => ['data' => trans('systemlog.remark'),'style' => ['width' => '65']],
        ];
        $data = [];
        $params['order_by'] =  ['log_id' => 'desc'];
        $params['limit'] =  '*';
        // 解析搜索条件的log_type
        $logType = $params['search']['log_type'][0];
        $logTypeNames = trans('systemlog');
        if (isset($logTypeNames[$logType])) {
            $logType = $logTypeNames[$logType];
        }
        $result = $this->getSystemLogList($params,"export");
        if ($result && !empty($result['list'])) {
            $result = $result['list'];
            foreach ($result as $k => $v) {
                $v['log_content'] = preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($v['log_content'])));
                foreach ($header as $_k => $_v) {
                    if ($_k == 'log_type') {
                        $data[$k][$_k] = $logType;
                    } else {
                        $data[$k][$_k] = $v[$_k];
                    }
                }
            }
        }
        return compact('header', 'data');
    }

}