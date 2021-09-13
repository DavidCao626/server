<?php

namespace App\EofficeApp\LogCenter\Services;

use Illuminate\Support\Facades\Redis;
use App\Jobs\SyncLogToElasticSearchJob;
use Queue;
use DB;

use App\EofficeApp\LogCenter\Facades\LogScheme;
use App\EofficeApp\LogCenter\Traits\LogTrait;

class ElasticService extends BaseElasticService
{
    const LOG_ADD_NUMBER = 200;
    use LogTrait;
    public function __construct()
    {
        //todo 判断es有没有开启！！！
        parent::__construct();

    }

    /**
     * 查询日志数据
     * @param $params
     * @return array
     */
    public function search($params)
    {
        if (!isset($params['search'])) {
            return ['code' => ['0x0103001', 'systemlog']];
        }
        $search = $this->handleEsSearch($params);
        $result = $this->searchLogData($search);
        return $result;
    }

    /**
     * 单条日志添加
     * @param $data
     * @return array|bool
     */
    public function addOneLog($data)
    {
        if (!$this->isElasticSearchRun()){
            return false;
        }
        if (empty($data['module_key'])) {
            return false;
        }
        return $this->addOneLogData($data);
    }


    /**
     * 多条日志批量添加
     * @param $data
     *
     */
    public function addModuleLog($data)
    {

        if ($this->isElasticSearchRun() && $this->exists(config('elastic.logCenter.index'))) {
            //redis里 logCenter:logList 这个key大于10000条时应该是es出问题应该删除数据，避免数据累积
            if(Redis::lLen("logCenter:logList") >= 10000){
                Redis::lTrim("logCenter:logList", -1, 0);
                return;
            }
            //判断 key 是否存在
            Redis::rPush("logCenter:logList" , json_encode($data));
            $logLength = Redis::lLen("logCenter:logList");
            $logTotal = self::LOG_ADD_NUMBER;
            if($logLength >= $logTotal){
                $this->insertLog($logTotal);
            }
        }

    }

    public function addManyLog($data)
    {

        if ($this->isElasticSearchRun() && $this->exists(config('elastic.logCenter.index')) && count($data) != 0){
            try{
                $this->addManyLogData($data);
            }catch (\Exception $e){
                \Log::info("logCenterAddLog" . $e->getMessage());
            }
        }



    }

    public function logSync()
    {
        if ($this->isElasticSearchRun() && $this->exists(config('elastic.logCenter.index'))) {
            //判断 key 是否存在
            $logLength = Redis::lLen("logCenter:logList");
            if($logLength > 0){
               $this->insertLog($logLength);
            }
        }
    }

    public function insertLog($total){
        if(!Redis::get('logCenter:lock')){
            $logInfo = Redis::lRange("logCenter:logList",0, $total);
            Redis::ltrim("logCenter:logList",$total,-1);
            foreach ($logInfo as &$value){
                $value = json_decode($value,true);
            }
            $this->addManyLog($logInfo);
        }


    }


    //todo 这块后期要去掉，开启es才会调这个方法
    public function setLogMapping()
    {
        $this->setMapping();
    }

    /**
     * 同步mysql数据到es
     */

    public function syncLogToElasticSearch()
    {

        $index = config('elastic.logCenter.index');
        if ($this->isElasticSearchRun()) {
            $res = $this->exists($index);

            Redis::set('logCenter:lock', 1, 'EX', 86400);
            if (!$res) {
                $response = $this->setMapping();
                if ($response) {
                    dispatch(new SyncLogToElasticSearchJob());
                }
                $this->setMaxResultWindow();
            } else {
                dispatch(new SyncLogToElasticSearchJob());
            }

        }


    }

//    public function syncMysqlToEs()
//    {
//
//        $logModules = LogScheme::getAllLogModules();
//        $tableConfig = [];
//        foreach ($logModules as $k => $value) {
//            $tableConfig[] = config('elastic.logCenter.tablePrefix') . $value['module_key']; //todo 通过makelog方法获取表前缀
//        }
//        //获取当前每个模块最大id ['system' =>232]
//        $syncPosition = $this->searchModuleMaxId();
//        foreach ($tableConfig as $table) {  //todo 每次同步几条合适？
//            DB::table($table)->orderBy('log_id')->where('log_id', '>', $syncPosition[substr($table, 7)])->chunk(1000, function ($list) use ($table) {
//                $data = json_decode(json_encode($list), true);
//                foreach ($data as $k => $v) {
//                    $data[$k]['module_key'] = substr($table, 7);
//                }
//                $this->addManyLog($data);
//                // todo 后期注释掉
//                print_r(date("Y-m-d H:i:s", time()));
//
//            });
//        }
//
//    }

    /**
     * 用户活动轨迹
     * @param $params
     * @return array
     */
    public function userActivityTrack($params)
    {
        if (!isset($params['search'])) {
            return ['code' => ['0x0103001', 'systemlog']];
        }
        $search = $this->handleEsSearch($params);
        $result = $this->searchUserActivityTrack($search);
        return $result;
    }

    /**
     * 模块排行
     * @param $params
     * @return array
     */
    public function moduleRank($params)
    {
//        $this->syncLogToElasticSearch();print_r(3333);exit;

        $search = $this->handleEsSearch($params);
        $logModules = LogScheme::getAllLogModules();
        $result = $this->searchModuleRank($search , count($logModules));
        $module = $searchModule = [];
        $module = array_column($logModules, 'module_key');
        $searchModule = array_column($result['list'], 'module_key');
        $diff = array_diff($module, $searchModule);
        foreach ($diff as $k => $v) {
            array_push($result['list'], ['module_key' => $v, 'count' => 0]);
        }

        foreach ($result['list'] as $k =>&$v){
            foreach ($logModules as $key => $val){
                if($val['module_key'] === $v['module_key']){
                    $v['module_name'] = $val['module_name'];
                    break;
                }
            }
        }

        return $result;
    }

    /**删除索引
     * @param $index
     * @return bool
     */
    public function deleteEsIndex($index)
    {
        if (empty($index)) {
            return false;
        }
        $result = $this->deleteIndex($index);
        return $result;
    }

    /**
     * 获取所有模块最大id，用于日志同步
     * @return array
     */
    public function searchModuleMaxId()
    {
        $logModules = LogScheme::getAllLogModules();
        $tableConfig = [];
        foreach ($logModules as $k => $value) {
            $tableConfig[] = $value['module_key'];
        }
        return $this->searchModuleMax($tableConfig);
    }

    public function setMaxSize(){
       $this->setMaxResultWindow();
    }
    /**
     * 统计es数据大小
     * @return mixed
     */
    public function countEsData()
    {
        return $this->getEsDataCount();
    }


    //todo 测试导入功能后期删除
//    protected function createdAllInSql($data){
//        $sql = '';
//        $prefix = 'eo_log_notify';
//        $sql = "INSERT INTO `".$prefix."` (`log_category`, `log_operate`, `log_level`, `creator`, `ip`, `relation_table`, `relation_id`, `log_content`, `log_content_type`,`platform`,`log_time`) VALUES";
//        foreach ($data as $k => $v){
//            $log_category = $v['log_category'];
//            $log_operate = $v['log_operate'];
//            $log_level = $v['log_level'];
//            $creator = $v['creator'];
//            $ip = '127.0.0.1';
//            $rel_table='notify_content';
//            $rel_id=1;
//            $log_content=$v['log_content'];
//            $log_content_type = $v['log_content_type'];
//            $platform=$v['platform'];
//            $log_time=$v['log_time'];
//
//            $sql .= "('$log_category', '$log_operate', '$log_level', '$creator','$ip', '$rel_table', '$rel_id','$log_content', '$log_content_type','$platform','$log_time'),";
//        }
//        $sql = substr($sql,0,strlen($sql)-1);
//        return $sql;
//    }

    public function  testData(){

        $log_category= ['login','user','system','dept'];
        $log_operate=['login','add','edit','delete'];
        $log_level=[1,2,3,4];
        $creator=['admin', 'WV00000001' ,'WV00000002' , 'WV00000003' ,
            'WV00000004' , 'WV00000005' ,'WV00000006','WV00000007',
            'WV00000008' ,  'WV00000009', 'WV00000010','WV00000011'
        ];
        $log_content_type=[1,2];
        $log_content=['edit其他盘机→ 更改设置→ 选选）→ 点击确认结束。@echo off echo 正在清除系统垃圾文件，请稍等.',
            'add我的明度热的祸。',
            '百度新闻delete是包含海量编辑,快速了解它们的最新进展。
news.baidu.com/',
            '上海本view地宝、教育资讯、房地产新闻、旅游资讯等上海资讯...'];
        $platform = [1,2,3,4,5];
        $log_time=['2015-01-25 06:05:45','2016-03-25 04:05:45','2017-02-25 08:05:45',
            '2018-04-25 04:05:45','2019-05-25 04:05:45','2020-05-25 09:05:45'
            ,'2021-07-25 04:05:45','2014-09-25 10:05:45','2013-06-25 04:05:45','2012-01-25 04:05:45',
            '2011-05-25 06:05:45','2010-06-25 09:05:45','2009-07-25 04:05:45','2008-08-25 02:05:45',
        ];
        $data=[];
        for($j=0;$j<100;$j++){
            for($i=0 ;$i<10000; $i++){
                shuffle($log_category);
                shuffle($log_operate);
                shuffle($log_level);
                shuffle($creator);
                shuffle($log_content_type);
                shuffle($log_content);
                shuffle($platform);
                shuffle($log_time);
                $data[]=[
                    'log_category' => $log_category[0],
                    'log_operate' => $log_operate[0],
                    'log_level' => $log_level[0],
                    'creator' =>$creator[0],
                    'ip' => '127.0.0.1',
                    'relation_table' => 'notify_content',
                    'relation_id' => 1,
                    'log_content' => $log_content[0],
                    'log_content_type' => $log_content_type[0],
                    'platform' => $platform[0],
                    'log_time' => $log_time[0],
                ];
            }
            $res=$this->createdAllInSql($data);
            unset($data);
            DB::insert($res);
            $data=[];
        }
        print_r('complete');

    }

}