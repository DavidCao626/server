<?php

namespace App\EofficeApp\LogCenter\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\LogCenter\Traits\LogTrait;
use App\Jobs\IpTransAreaJob;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\LogCenter\Facades\LogScheme;
use App\Jobs\SyncLogToMysql;
use Queue;
use DB;
/**
 * Description of LogRecordsService
 *
 * 用户获取日志记录，日志记录分析，相关数据变更记录等。
 *
 * @author lizhijun
 */
class LogRecordsService extends BaseService
{
    use LogTrait;
    private $elasticService;
    private $logRepository;
    private $userService;
    private $logModuleConfigRepository;
    private $userSystemInfoRepository;
    private $userRepository;
    private $userSuperiorRepository;
    private $userMenuService;
    public function __construct()
    {

        $this->logRepository = 'App\EofficeApp\LogCenter\Repositories\LogRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->logModuleConfigRepository = 'App\EofficeApp\LogCenter\Repositories\LogModuleConfigRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userSuperiorRepository = 'App\EofficeApp\User\Repositories\UserSuperiorRepository';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        //进入日志中心立即同步日志
        $this->elasticService = app('App\EofficeApp\LogCenter\Services\ElasticService');
        $this->elasticService->logSync();
    }
    /**
     * 获取日志列表
     *
     * @param type $params
     * 如果值数组第一个参数不是数组，则第二个参数没传，默认=；如果值数组第一个参数是数组，则第二个参数没传，默认in；
     * [
     *       'page' => 1,
     *       'limit' => 10,
     *       'order_by' => ['log_time' => 'desc']
     *       'search' => [
     *           'log_time' => [['2020-01-01 00:00:00','2020-03-01 00:00:00'], 'between'],
     *           'creator' => [['admin','WV000001'], 'in'],
     *           'log_level' => [1, '='],
     *           'log_operate' => ['delete', '='],
     *           'log_operate' => [['delete', 'add'], 'in'],
     *           'rel_id' => [1, '='],
     *           'rel_table' => ['090908080808080', '='],
     *           'log_id' => [1, '='],
     *           'log_category' => ['user', '='],
     *           'log_module' => ['system', '=']
     *       ]
     *  ];
     * @param type $moduleKey
     *
     * @return array
     */
    public function lists($params , $userInfo)
    {

        $params = $this->handleParams($params , $userInfo);
        //为了提升速度，只有点系统管理才会进行日志同步
        $this->syncLogOnJob($params);

        $moduleKey = $params['search']['module_key'][0] ?? '';
        if ($moduleKey === '') {
            return ['code' => ['0x0103003', 'systemlog']];
        }
        if(!isset($params['limit']) || !isset($params['page'])){
            return ['code' => ['0x0103008', 'systemlog']];
        }
        $size = $params['limit'] * $params['page'];
        if ($this->isElasticSearchRun() && $this->elasticService->exists(config('elastic.logCenter.index')) && $size <= config('elastic.logCenter.max_result_window')) { //todo 写一个service 来判断 后期换成厉旭提供的service
            $esResult = $this->handleResult($this->elasticService->search($params), $moduleKey);
            return $esResult;
        }

        unset($params['search']['module_key']);
        if ($moduleKey === 'all') {
            return ['code' => ['0x0103007', 'systemlog']];
        }
        $logRepository = app($this->logRepository);
        $total = $logRepository->getLogTotal($params, $moduleKey);

        $list = $total > 0 ? $logRepository->getLogList($params, $moduleKey) : [];
        $mysqlResult = $this->handleResult(['total' => $total, 'list' => json_decode(json_encode($list), true)], $moduleKey);
        return $mysqlResult;
    }
    public function getOneDataLogs($params)
    {
        $params = $this->parseParams($params);
        $moduleKey = $params['module_key'] ?? null;
        $category = $params['category'] ?? null;
        $dataId = $params['data_id'] ?? null;
        $dataTable = $params['data_table'] ?? null;
        if ($moduleKey) {
            if($category){
                $params['search']['log_category'] = [$category];
            }
            if($dataId){
               $params['search']['relation_id'] = [$dataId];
            }
            if($dataTable){
                $params['search']['relation_table'] = [$dataTable];
            }
            $extra = isset($params['extra']) &&  $params['extra']? json_decode($params['extra'], true) : [];
            if (is_array($extra) && !empty($extra)) {
                foreach ($extra as $key => $value) {
                    $params['search'][$key] = [$value];
                }
            }
            $params = $this->handleParams($params);
            $logRepository = app($this->logRepository);
            $total = $logRepository->getLogTotal($params, $moduleKey);
            $list = $total > 0 ? json_decode(json_encode($logRepository->getLogList($params, $moduleKey)), true) : [];
            return $this->handleResult(compact('total', 'list'), $moduleKey);
        }
        return ['total' => 0, 'list' => []];
    }
    public function getLogDetail($params)
    {
        $this->isFastIp = true;
        $logId = $params['log_id'] ?? null;
        $moduleKey = $params['module_key'] ?? null;
        if (!$logId || !$moduleKey) {
            return ['code' => ['0x0103005', 'systemlog']];
        }
        $log = app($this->logRepository)->getLogsByLogId($moduleKey, $logId);

        if (!$log->relation_table || !$log->relation_id) {
            $log->changes = [];
        } else {
            $changes = app($this->logRepository)->getChangeDataByLogId($moduleKey, $log->relation_table, $logId);

            if (!$changes->isEmpty() &&  $change = $this->makeChange($log->relation_table) ) {
                foreach ($changes as &$item) {
                    $transField = $change->getField($item->field);
                    if (!$transField && method_exists($change, 'dynamicFields')) {
                        $transField = $change->getDynamicField($item->field, $log);
                    }
                    $item->field = $transField;
                }
            }
            $log->changes = $changes;
        }

        $log->log_operate_name = $this->getLogOperate($moduleKey, $log->log_category, $log->log_operate);
        $log->log_category_name = $this->getLogCategory($moduleKey, $log->log_category);
        $log->ip_area = $this->ipArea($log->ip, 'addr');
        $log->creator_name = get_user_simple_attr($log->creator);
        $log->module_key = $moduleKey;
        $category = LogScheme::getAllLogModules(); //这个方法放到trait里面？
        foreach ($category as $key => $val){
            if($val['module_key'] === $moduleKey){
                $log->module_name = $val['module_name'];
                break;
            }
        }
        $log = json_decode(json_encode($log),true);
        //json格式进行特殊处理
        if($this->isJson($log['log_content'])){
            $class = $this->makeParser($log['module_key']);
            if($class){
                $class->parseContentData($log);
            }
        }
        return $log;
    }

    public function getChangeDataByRelationId($params)
    {
        //todo 支持多个id查询，单一id 项目有问题。
        $moduleKey = $params['module_key'] ?? null;
        $relationId = $params['relation_id'] ?? null;
        $relationTable = $params['relation_table'] ?? null;
        if (!$moduleKey || !$relationId || !$relationTable) {
            return ['code' => ['0x0103006', 'systemlog']];
        }

        $changes = app($this->logRepository)->getChangeDataByRelationId($moduleKey, $relationTable, $relationId)->toArray();
        $changesGroup = [];
        foreach ($changes as $item) {
            $changesGroup[$item->log_id][] = $item;
        }
        $logIds = array_keys($changesGroup);
        $logs = app($this->logRepository)->getLogsByLogId($moduleKey, $logIds, ['log_id', 'creator', 'log_time']);
        $logsMap = $logs->mapWithKeys(function($item) {
            return [$item->log_id => $item];
        });
        $response = [];
        $change = $this->makeChange($relationTable);
        foreach ($changesGroup as $logId => $items) {
            foreach ($items as &$item) {
                $transField = $change->getField($item->field);
                if (!$transField && method_exists($change, 'dynamicFields')) {
                    $transField = $change->getDynamicField($item->field, $relationId);
                }
                $item->field = $transField;
            }
            $log = $logsMap[$logId] ?? [];
            $response[] = [
                'log_id' => $logId,
                'creator' => get_user_simple_attr($log->creator) ?? '',
                'log_time' => $log->log_time ?? '',
                'items' => $items
            ];
        }
        return $response;
    }

    public function moduleUseRank($params){
        if($this->isElasticSearchRun() && $this->elasticService->exists(config('elastic.logCenter.index'))){
           return  $this->elasticService->moduleRank($params);
        }

        return $this->moduleUseCount($params);

    }

    public function moduleUseCount($params){

        $params = $this->parseParams($params);
        $category = LogScheme::getAllLogModules();
        $count = [];
        foreach ($category as $k => $v){
           $result = app($this->logRepository)->getModuleUse($v['module_key'] , $params['search']['log_time'][0]);
           $count[] = ['module' => $v['module_key'] , 'count' => $result , ];
        }
        foreach ($count as $k =>&$v){
            foreach ($category as $key => $val){
                if($val['module_key'] === $v['module']){
                    $v['module_name'] = $val['module_name'];
                    break;
                }
            }
        }

        $count = $this->arraySort($count,'count',SORT_DESC,SORT_REGULAR);
        return ['list' => $count];
    }


    /**
     * 默认关注组织树
     *
     * @param  [type] $userId
     * @param  [type] $params
     * @param  [type] $own
     *
     * @return [type]  array
     */
    public function getSubordinate($userId, $own)
    {
        if (!$userId) {
            $userId = $own['user_id'] ?? '';
        }
        // 获取当前用户的下属
        $userParams['returntype'] = "list";
        $userParams['all_subordinate'] = false;
        $userParams['include_supervisor'] = true;
        $subUsers = app($this->userService)->getSubordinateArrayByUserId($userId, $userParams);
        if ($subUsers) {
            foreach ($subUsers as $key => $user) {
                if (isset($user['user_has_many_subordinate']) && !empty($user['user_has_many_subordinate'])) {
                    foreach ($user['user_has_many_subordinate'] as $k => $v) {
                        $userStatus = app($this->userSystemInfoRepository)->getUserStatus($v['user_id']);
                        if ($userStatus != 2) {
                            $subUsers[$key]['has_children'] = 1;
                        }
                    }
                }
            }
        }

        return $subUsers;
    }

    public function handleResult($result , $moduleKey)
    {
        $this->isFastIp = true;
        if ($result['total'] == 0 || $moduleKey == '') {
            return $result;
        }
        if($moduleKey != 'all'){
            foreach ($result['list'] as $k => &$v){
                $v['module_key'] = $moduleKey;
            }
            unset($v);
        }

        $category = LogScheme::getAllLogModules(); //这个方法放到trait里面？
        foreach ($result['list'] as $k => &$v) {
            //todo 不存在的时候就不去拼 ,流程那边同步数据格式处理
            $v['log_operate_name'] = $this->getLogOperate($v['module_key'], $v['log_category'], $v['log_operate']);
            $v['log_category_name'] = $this->getLogCategory($v['module_key'], $v['log_category']);
            $v['ip_area'] = $this->ipArea($v['ip'], 'addr');
            $v['user_name'] = get_user_simple_attr($v['creator']);//todo 用static来减少数据库请求
            foreach ($category as $key =>$val){
                if($val['module_key'] === $v['module_key']){
                    $v['module_name'] = $val['module_name'];
                    break;
                }
            }
            //日志内容是json格式走这边解析
            if($this->isJson($v['log_content'])){
                $class = $this->makeParser($v['module_key']);
                if($class){
                    $class->parseContentData($v);
                }
            }

        }

        unset($v);
        return $result;
    }


    public function handleParams($params, $userInfo = ''){
        $params =  $this->parseParams($params);
        if(isset($params['date_filter'])  && $params['date_filter'] != 'custom'){
            $params['search']['log_time'] = $this->timeTransform($params['date_filter']);
        }

        //todo 前端用户只有一个也是用 in ，改不了就后端兼容
        if(isset($params['user_filter']) && $params['user_filter'] != 'customUser'){
            $params['search']['creator'] = $this->userTransform($params, $userInfo);
        }

        return $params;
    }

    public function timeTransform($time){
       if($time == 'yesterday'){
            return [[date('Y-m-d 00:00:00', strtotime(' -1 day')),date('Y-m-d 23:59:59', strtotime(' -1 day'))] , 'between'];

        }else if($time == 'thisWeek'){
           return [[date('Y-m-d 00:00:00', strtotime('this week monday')),date('Y-m-d 23:59:59', strtotime('this week sunday'))] , 'between'];

        }else if($time == 'lastWeek'){
           return [[date('Y-m-d 00:00:00', strtotime('last week monday')),date('Y-m-d 23:59:59', strtotime('last week sunday'))] , 'between'];

        }else if($time == 'thisMonth'){
           return [[date('Y-m-01 00:00:00'),date('Y-m-d 23:59:59', strtotime('Last day of this month'))] , 'between'];

        }else if($time == 'lastMonth'){
           return [[date('Y-m-01 00:00:00', strtotime('last month')),date('Y-m-d 23:59:59', strtotime('Last day of last month'))] , 'between'];

       }else{
           return [[date('Y-m-d 00:00:00', time()),date('Y-m-d 23:59:59', time())] , 'between'];

       }

    }

    public function userTransform($params , $own){

//        'myDept','sub' ,'allSub',customDept;
        if($params['user_filter'] == 'myDept' || $params['user_filter'] == 'customDept'){
            if($params['user_filter'] == 'customDept'){
                if(isset($params['dept_id']) && !empty(json_decode($params['dept_id'],true))){
                    $dept = app($this->userRepository)->getUserByAllDepartment(json_decode($params['dept_id'],true));
                }else{
                    return [[] , 'in'];
                }
            }else{
                $dept = app($this->userRepository)->getUserByDepartment($own['dept_id'], ['user.user_accounts' => ['', '!=']]);
            }
            $dept = array_column($dept->toArray(), 'user_id');
            return $this->handleUser($dept);
        } else if($params['user_filter'] == 'allSub'){
            $allSub = $this->getSubordinateUserIds($own['user_id'], true); //有脏数据，没有出口条件。。
            return $this->handleUser($allSub);
        }else{
            $sub = $this->getSubordinateUserIds($own['user_id']);
            return $this->handleUser($sub);
        }

    }

    public function handleUser($user){
        if(empty($user)){
            return ['','=']; //todo 没有下属时后期查看
        }
        if(count($user) > 1){
            return [$user , 'in'];
        }
        return [$user[0] , '='];
    }




    /**
     * 获取下属用户
     *
     * @staticvar array $allUserId
     * @param type $userId
     *
     * @return array
     */
    private function getAllSubordinateUsers($userId)
    {
        static $allUserId = [];
        $users = app($this->userSuperiorRepository)->getSuperiorUsers($userId);
        if(count($users) > 0) {
            $userId = array_column($users, 'user_id');
            $allUserId = array_merge($allUserId, $userId);
            $this->getAllSubordinateUsers($userId);
        }
        return array_unique($allUserId);
    }
    /**
     * 获取下属用户ID
     *
     * @param type $userId
     * @param type $allSub
     * @param type $includeLeave
     *
     * @return array
     */
    public function getSubordinateUserIds($userId, $allSub = false, $includeLeave = false)
    {
        $subUserIds = [];
        if( $allSub) {
            $subUserIds = $this->getAllSubordinateUsers($userId);
        } else {
            $users = app($this->userSuperiorRepository)->getSuperiorUsers($userId);
            if(count($users) > 0) {
                $subUserIds = array_column($users, 'user_id');
            }
        }
        if(!$includeLeave) {
            if(!empty($subUserIds)) {
                $users = app($this->userRepository)->getNoLeaveUsersByUserId($subUserIds);

                $subUserIds = $users->isEmpty() ? [] : array_column($users->toArray(), 'user_id');
            }
        }
        return $subUserIds;
    }

    public function syncLogOnJob($params){
        if(isset($params['search']['module_key'][0]) && $params['search']['module_key'][0] == 'system' ){
            //防止数据重复同步
            $status = DB::table('eo_log_sync_status')->where('type', 3)->first();//优化点再放一份在redis
            if (!isset($status->id)){
                Redis::incr('logCenter:job_lock');
                $end =  strtotime(date('Y-m-d',strtotime('+1 day')));
                $time = $end - time();
                if(Redis::get('logCenter:job_lock') == 1){
                    dispatch(new SyncLogToMysql($time));
                }


            }


        }
    }

    public function ipTrans(){
        $ipStatus = DB::table('eo_log_sync_status')->where('type', 4)->first();//优化点再放一份在redis
        if (!isset($ipStatus->id)){
            DB::table('eo_log_sync_status')->insert(['type' => 4, 'created_at' => date('Y-m-d h:i:s', time())]);
            dispatch(new IpTransAreaJob());
        }

    }
}
