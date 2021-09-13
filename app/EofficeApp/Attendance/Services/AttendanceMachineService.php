<?php
namespace App\EofficeApp\Attendance\Services;
use App\Jobs\SyncAttendanceMachineJob;
use DB;
use Eoffice;
use Queue;
use Schema;
use Illuminate\Support\Facades\Redis;

class AttendanceMachineService extends AttendanceBaseService
{
    private $attendanceService;

    public function __construct()
    {
        parent::__construct();
        $this->attendanceService = 'App\EofficeApp\Attendance\Services\AttendanceService';
    }

    /**
     * 考勤机接入
     * excel考勤数据导入同步
     * @param type $data
     * @return type
     */
    public function attendMachineAccess($data)
    {
        $signDate = $this->format($this->defaultValue('sign_date', $data, $this->currentDate), 'Y-m-d');
        $userId = $data['user_id'];
        $signTimes = $this->defaultValue('sign_nubmer', $data, 1); //第几次考勤（正常班只有一次考勤，交换班可能有多次）
        //如果签到签退是时间格式还需拼上日期
        $signInTime = $this->makeFullDatetime($this->defaultValue('sign_in_time', $data, date('Y-m-d H:i:s')), $signDate); //签到时间
        $signOutTime = $this->makeFullDatetime($this->defaultValue('sign_out_time', $data, date('Y-m-d H:i:s')), $signDate); //签到时间
        $platform = $this->defaultValue('platform', $data, 8);
        $signData = [];
        $signData[$signDate][] = [
            'sign_date' => $signDate,
            'sign_nubmer' => $signTimes,
            'sign_in_time' => $signInTime,
            'sign_out_time' => $signOutTime,
            'platform' => $platform
        ];
        $result = app($this->attendanceService)->batchSign([$userId => $signData], $signDate,$signDate);
        if($result) {
            app($this->attendanceService)->addSimpleRecords($userId, $signDate, $signInTime, 1, 8, '', '', '', '', '');
            app($this->attendanceService)->addSimpleRecords($userId, $signDate, $signOutTime, 2, 8, '', '', '', '', '');
            return $result;
        }
        return false;
    }
    /**
     * 获取考勤机数据库表
     * @return [type] [description]
     */
    public function getMachineDatabasesTables()
    {
        $databaseInfo = $this->getMachineDatabase();

        $externalDatabaseInfo = [
            'driver'    => 'mysql',
            'host'      => $databaseInfo->host,
            'port'      => $databaseInfo->port,
            'database'  => $databaseInfo->database,
            'username'  => $databaseInfo->username,
            'password'  => $databaseInfo->password,
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'timezone'  => '+00:00',
            'strict'    => false,
        ];

        config(['database.connections.machine_database' => $externalDatabaseInfo]);

        try {
            $result = [];
            $query  = DB::connection('machine_database');
            $rows   = $query->select('show tables;');
            $i      = 0;
            foreach ($rows as $key => $value) {
                $tbaleNalem              = 'Tables_in_' . strtolower($databaseInfo->database);
                $result[$i]['tablename'] = $value->$tbaleNalem;
                $i++;
            }
            return $result;
        } catch (\Exception $e) {
            return ['code' => ['0x015019', 'system']];
        };
    }

    /**
     * 获取考勤机数据库字段
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getMachineDatabaseFields($param)
    {

        if (!isset($param['table_name'])) {
            return [];
        }
        $databaseInfo = $this->getMachineDatabase();

        $externalDatabaseInfo = [
            'driver'    => 'mysql',
            'host'      => $databaseInfo->host,
            'port'      => $databaseInfo->port,
            'database'  => $databaseInfo->database,
            'username'  => $databaseInfo->username,
            'password'  => $databaseInfo->password,
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'timezone'  => '+00:00',
            'strict'    => false,
        ];
        config(['database.connections.machine_database' => $externalDatabaseInfo]);
        try {
            $result  = [];
            $columns = Schema::connection('machine_database')->getColumnListing($param['table_name']);
            foreach ($columns as $key => $value) {
                $result[$key]['COLUMN_NAME'] = $value;
            }
            return $result;
        } catch (\Exception $e) {
            return ['code' => ['0x015019', 'system']];
        };
    }

    /**
     * 保存考勤机配置
     */
    public function saveMachineConfig($data)
    {
        $machine_type = isset($data['machine_type']) ? $data['machine_type'] : '';
        $insert                 = $this->defaultMachineconfig($data);
        $insert['update_time']  = isset($data['update_time']) ? implode(",", $data['update_time']) : '';
        $insert['machine_type'] = $machine_type == 'multi' ? 1 : 0;
        $insert['sync_user']    = isset($data['sync_user']) ? json_encode($data['sync_user']) : '';
        $insert['tabs_title']   = !empty($data['tabs_title']) ? $data['tabs_title'] : '考勤机';
        if (isset($data['id'])) {
            app($this->attendanceMachineConfigRepository)->entity->where('id', $data['id'])->update($insert);
        } else {
            if($machine_type == 'multi'){
                // 取出已有的生效的自动同步数据加进去
                $config = app($this->attendanceMachineConfigRepository)->entity->where('machine_type', '=' , 1)->first();
                if(!empty($config)){
                    $insert['update_time'] = $config->update_time;
                    $insert['is_auto']     = $config->is_auto;
                }
            }
            $id = app($this->attendanceMachineConfigRepository)->entity->insertGetId($insert);
            $data['id'] = $id;
        }
        // 如果是多考勤机，且设置了自动同步时间。则共用一起更新
        // if($machine_type == 'multi'){
            // 将所有多考勤的配置都改成一致的时间，不单单只是已生效的考勤机
            // app($this->attendanceMachineConfigRepository)->entity->where('machine_type','1')->update(['is_auto' => $data['is_auto'] , 'update_time' => $insert['update_time']]);
        // }
        // if (isset($single['update_time']) && is_array($single['update_time'])) {
        //     $single['update_time'] = implode(",", $single['update_time']);
        // }
        // $insert['update_time'] = isset($single['update_time']) ? $single['update_time'] : '';
        return $data;
    }

    /**
     * 返回所有考勤机配置案例的列表
     */
    public function getMachineCase($data)
    {
        $brandName = $data['brand_Name'] ?? '';
        $field = $data['fields'] ?? '';
        if($brandName){
            return app($this->attendanceMachineCaseRepository)->getAttendanceCaseList(['machine_brand' => $brandName]);
        }
        return app($this->attendanceMachineCaseRepository)->getAttendanceCaseList('',true,$field);
    }

    /**
     * 默认的考勤机配置选项
     */
    public function defaultMachineconfig($data)
    {
        $insert['database_id']  = isset($data['database_id']) ? $data['database_id'] : '';
        $insert['record_table'] = isset($data['record_table']) ? $data['record_table'] : '';
        $insert['sign_in']      = isset($data['sign_in']) ? $data['sign_in'] : '';
        $insert['sign_out']     = isset($data['sign_out']) ? $data['sign_out'] : '';
        $insert['sign_date']    = isset($data['sign_date']) ? $data['sign_date'] : '';
        $insert['machine_type'] = isset($data['machine_type']) ? $data['machine_type'] : '';
        $insert['user']         = isset($data['user']) ? $data['user'] : '';
        $insert['record_table_source']         = isset($data['record_table_source']) ? $data['record_table_source'] : 'table';
        $insert['record_table_sql']         = isset($data['record_table_sql']) ? $data['record_table_sql'] : '';
        $insert['schedule_model'] = isset($data['schedule_model']) ? $data['schedule_model'] : '';
        $insert['is_auto']        = isset($data['is_auto']) ? $data['is_auto'] : 0;
        return $insert;
    }

    /**
     * 获取考勤机所有配置
     * return machine_type 0表示单考勤机，1表示多考勤机
     */
    // 此函数在集成中心分支换成卡片式布局时有修改返回格式。
    public function getMachineConfig($orderBy = 0)
    {
        $result = [];
        if (Schema::hasTable('attendance_machine_config') && Schema::hasColumn('attendance_machine_config', 'is_use')) {
            // 此处定时任务加载的时候会执行到
            if($orderBy != 0){
                $single = DB::table("attendance_machine_config")->where("machine_type", 0)->orderBy("id", "ASC")->get();
            }else{
                $single = DB::table("attendance_machine_config")->where("machine_type", 0)->orderBy("is_use", "desc")->get();
            }
            if (!empty($single)) {
                // $single->update_time = explode(",", $single->update_time);
                $update_time = [];
                foreach ($single as $value) {
                    // 单考勤机没有同步用户的字段默认是全部用户
                    // $value->sync_user   = json_decode($value->sync_user);
                    $value->update_time = explode(",", $value->update_time);
                    // 判断该配置是否完整
                    $value->complete = $this->checkConfigComplete((array)$value);
                }
            }
            $multi = DB::table("attendance_machine_config")->where("machine_type", 1)->orderBy("id", "ASC")->get();
            if (!empty($multi)) {
                $update_time = [];
                foreach ($multi as $value) {
                    $value->sync_user   = json_decode($value->sync_user);
                    $value->update_time = explode(",", $value->update_time);
                    // $update_time        = $value->update_time;
                    // 判断该配置是否完整
                    $value->complete = $this->checkConfigComplete((array)$value);
                }
                // $result['multi_update_time'] = $update_time;
            }
            $index_model            = DB::table('system_params')->where("param_key", "machine_type")->first();
            $result['machine_type'] = isset($index_model->param_value) ? $index_model->param_value : 'single';
            $result['single']       = $single;
            $result['multi']        = $multi;

            return $result;
        }else{
            return '';
        }

    }
    // 按id获取考勤机配置
    public function getMachineConfigById($id)
    {
        $result = [];
        if (Schema::hasTable('attendance_machine_config') && !empty($id)) {
            $config = app($this->attendanceMachineConfigRepository)->entity->where('id', $id)->first();
            if(!empty($config->update_time)){
                $config->update_time = explode(",", $config->update_time);
                $config->sync_user   = empty($config->sync_user) ? $config->sync_user : json_decode($config->sync_user);
            }
            return $config;
        };
    }
    // 切换单双考勤机配置
    public function switchAttendanceType($type)
    {
        if (!empty($type)) {
            // 这里可能会有param_key还不存在的问题
            $indexModel  = DB::table('system_params')->where("param_key", "machine_type")->first();
            if (!empty($indexModel)) {
                $config = DB::table('system_params')->where("param_key", "machine_type")->update(['param_value' => $type]);
            } else {
                $config = DB::table('system_params')->insert(['param_key' => "machine_type", 'param_value' => $type]);
            }
            if($config){
                return ['type' => $type];
            }

        };
    }
    // 获取多考勤时的自动同步配置信息
    public function getMultiIsAuto(){
        // 获取的时候不需要限制它只返回启用的
        $config = app($this->attendanceMachineConfigRepository)->entity->where('machine_type', '=' , 1)->first();
        if(!empty($config)){
            $config->update_time = explode(",", $config->update_time);
            return $config;
        }
    }

    // 设置多考勤时的自动同步配置信息
    public function setMultiIsAuto($data){
        // 将所有的配置都保持一直
        if(isset($data['is_auto']) && isset($data['update_time'])){
            $data['update_time'] = implode(",", $data['update_time']);
            app($this->attendanceMachineConfigRepository)->entity->where('machine_type','1')->update(['is_auto' => $data['is_auto'] , 'update_time' => $data['update_time']]);
            return $data;
        }
        $data['update'] = 0;
        return $data;

    }

    // 按id设置考勤机生效配置
    public function useMachineConfigById($id)
    {
        if (!empty($id)) {
            $config = app($this->attendanceMachineConfigRepository)->entity->where('id', $id)->first();
            if(isset($config->machine_type)){
                // if($config->is_template == 1){
                    // 是考勤机配置模板，需要校验数据完整性.改为全部都校验
                    if($config->database_id == 0){
                        // 需要先配置外部数据库
                        return ['code' => ['no_database_id', 'attendance']];
                    }
                // }
                if($config->machine_type == 0){
                    // 单考勤机要把所有设置为不生效
                    app($this->attendanceMachineConfigRepository)->entity->where('machine_type', 0)->update(['is_use' => 0]);
                    $uesMachineType = 'single';
                }else{
                    $uesMachineType = 'multi';
                }

                $indexModel  = DB::table('system_params')->where("param_key", "machine_type")->first();
                if (!empty($indexModel)) {
                    DB::table('system_params')->where("param_key", "machine_type")->update(['param_value' => $uesMachineType]);
                } else {
                    DB::table('system_params')->insert(['param_key' => "machine_type", 'param_value' => $uesMachineType]);
                }

                $config->is_use = $config->is_use == 0 ? 1 : 0;
                $resultSet['set'] = $config->is_use;
                $config = $config->save();
                return $resultSet;
            }
            return $config;
        }

    }

    // 删除考勤机配置
    public function deleteMachineConfig($data)
    {
        if (!empty($data['id'])) {
            return DB::table('attendance_machine_config')->where("id", $data['id'])->delete();
        }
    }
//  考勤机同步任务队列执行的函数
    public function syncQueueAttendance($data = '', $user_id = '')
    {
        $config = $this->getMachineConfig();
        if (!empty($config)) {
            $machine_type = $config['machine_type'];
            if ($machine_type == "single") {
                // 单考勤机默认启用的是第一条
                $this->syncAttendance($config['single'][0], $data, $user_id);
            } else {
                $multis = $config['multi'];
                // 多考勤机取出每一条配置分别同步
                foreach ($multis as $multi) {
                    // 此处要加上是否启用的判断
                    if(isset($multi->is_use) && $multi->is_use == 0){
                        continue;
                    }
                    $sync_user        = $multi->sync_user; // 配置下生效的系统用户ids
                    $userIds          = $this->transferUser($sync_user);
                    $multi->sync_user = $userIds;
                    $this->syncAttendance($multi, $data, $user_id);
                }
            }
//            推送放在此处
            if ($user_id) {
                $sendData = [
                    'toUser'      => $user_id,
                    'remindState' => 'attendance.manage.records',
                    'remindMark'  => 'attendancemachine-complete',
//                    'sendMethod'  => ['sms'],
//                    'isHand'      => true,
                    'content'     => trans("attendance.successfully_synchronized"),
                    'stateParams' => [],
                ];
                Eoffice::sendMessage($sendData);
            }
        }
    }

    /**
     * @param definite 生效的系统用户ids，也可能是部门id或者角色id
     * @return 综合的系统用户ids
     */
    private function transferUser($definite)
    {
        $dept_user = [];
        $roles_user = [];
        $user = [];
        if (isset($definite->dept_id) && !empty($definite->dept_id)) {
            $dept = $definite->dept_id;
            $param['search'] = ["dept_id" => [$dept, "in"]];
            $res = app($this->userService)->getAllUserIdString($param);
            $dept_user = explode(",", $res);
        }
        if (isset($definite->role_id) && !empty($definite->role_id)) {
            $roles = $definite->role_id;
            $param['search'] = ["role_id" => [$roles, "in"]];
            $res = app($this->userService)->getAllUserIdString($param);
            $roles_user = explode(",", $res);
        }

        if (isset($definite->user_id) && !empty($definite->user_id)) {
            $user = $definite->user_id;
        }

        $users = array_merge($dept_user, $roles_user, $user);
        $users = array_unique($users);
        return $users;
    }

//    考勤机同步任务队列
    public function synchronousAttendance($data = '', $user_id = '')
    {
        Queue::push(new SyncAttendanceMachineJob($data, $user_id));
    //   $this->syncQueueAttendance($data, $user_id);
        return true;

    }

    //  解析数据库字段，连接外部数据库获取外部考勤数据， 并插入到考勤系统
    public function syncAttendance($config, $data, $user_id = '')
    {
        if (!empty($config)) {
            if (empty($config->sign_in) || empty($config->sign_out) || empty($config->database_id) || empty($config->user)) {
                return ['code' => ['0x044039', 'attendance']];
            }
            if(empty($config->record_table_source) || ($config->record_table_source == 'table' && empty($config->record_table))){
                return ['code' => ['0x044039', 'attendance']];
            }
            $sign      = $config->sign_in;  //签到字段
            $out       = $config->sign_out; //签退字段
            $sign_date = isset($config->sign_date) ? $config->sign_date : '';   //考勤的日期字段
            $today     = date("Y-m-d", time());
            $source      = $config->record_table_source; // 数据来源

            $param     = [
                'database_id' => $config->database_id, //外部数据库id 必填
                'table_name'  => $config->record_table, //表名 必填
                'returntype'  => 'array', //array count object
            ];
            if (!empty($sign_date) && $sign != $sign_date) {
//                考勤的日期字段不为空并且和签到字段不相同
//                $start = $end = $today;
//                此处没有拼日期
                $start = $today;
                $end   = $today;
                // $start = $today . " 00:00:00";
                // $end   = $today . " 23:59:59";
                if (isset($data['type']) && $data['type'] == "month") {
                    $month_first = date('Y-m-01', strtotime($today));
                    $month_last  = date('Y-m-d', strtotime("$month_first +1 month -1 day"));
//                    添加日期因为取不到数据
                    $start       = $month_first;
                    $end         = $month_last;
                    // $start       = $month_first . " 00:00:00";
                    // $end         = $month_last . " 23:59:59";
                } else if (isset($data['type']) && $data['type'] == "last") {
                    $month_first = date('Y-m-01', strtotime('-1month'));
                    $month_last  = date('Y-m-t', strtotime('-1month'));
                    $start       = $month_first;
                    $end         = $month_last;
                    // $start       = $month_first . " 00:00:00";
                    // $end         = $month_last . " 23:59:59";
                }
                $param['search'] = ["$sign_date" => [[$start, $end], 'between']];
            } else {
                $start = $today . " 00:00:00";
                $end   = $today . " 23:59:59";
                if (isset($data['type']) && $data['type'] == "month") {
                    $month_first = date('Y-m-01', strtotime($today));
                    $month_last  = date('Y-m-d', strtotime("$month_first +1 month -1 day"));
                    $start       = $month_first . " 00:00:00";
                    $end         = $month_last . " 23:59:59";
                } else if (isset($data['type']) && $data['type'] == "last") {
                    $month_first = date('Y-m-01', strtotime('-1month'));
                    $month_last  = date('Y-m-t', strtotime('-1month'));
//                  需要拼上时间，否则会导致同步上月无法同步数据
                    $start       = $month_first . " 00:00:00";
                    $end         = $month_last. " 23:59:59";
                }
                $param['search'] = ["$sign" => [[$start, $end], 'between']];
            }
            $param['page']  = 1;
            $param['limit'] = 1000;
            $param['order_by']= [$config->user=>"asc"];//排序 选填
            if($sign_date){
                $param['order_by']= [$config->user=>"asc" ,$sign_date=>"asc"];
            }
            // 根据来源获取封装参数获取数据
            if($source == 'sql'){
                $param['sql'] = $config->record_table_sql;
                // 判断sql中是否有别名存在
                if(!preg_match("/as /i",$param['sql'])){
                    // 首先根据关键字解析出sql的主表用于拼接
                    if(preg_match("/left join/i",$param['sql'],$matches,PREG_OFFSET_CAPTURE)){
                        // left join
                        //dd(11);
                        $matchOffset = $matches[0][1] ?? 0;
                        if($matchOffset){
                            // 截取前部sql
                            $sqlstring = substr($param['sql'],0,$matchOffset-1);
                            $tableNameArr = explode(' ',$sqlstring);
                            $tableName= end($tableNameArr);
                        }
                    }elseif(preg_match("/right join/i",$param['sql'],$matches,PREG_OFFSET_CAPTURE)){
                        // right join
                        //dd(22);
                        $matchOffset = $matches[0][1] ?? 0;
                        if($matchOffset){
                            // 截取后部sql
                            $sqlstring = substr($param['sql'],$matchOffset);
                            $tableNameArr = explode(' ',$sqlstring);
                            $tableName= $tableNameArr[2];
                        }
                    }elseif(preg_match("/join/i",$param['sql'])){
                        // join
                        $matchOffset = stripos($param['sql'],'from ');
                        $sqlstring = substr($param['sql'],$matchOffset);
                        $tableNameArr = explode(' ',$sqlstring);
                        $tableName= $tableNameArr[1];
                    }else{
                        // from
                        $matchOffset = stripos($param['sql'],'from ');
                        $sqlstring = substr($param['sql'],$matchOffset);
                        $tableNameArr = explode(' ',$sqlstring);
                        $tableName= $tableNameArr[1];
                    }
                }
                // 此处得要拼上时间
                // $param['sql'].=' where';
                // $attendance     = app($this->externalDatabaseService)->getExternalDatabasesDataBySql($param);
                $param['type'] = 'sql';
                $param['returntype'] = 'object'; // 此处不加会返回真的二维数组，而以前返回的是一位数组里面是orm对象
                // 自己支持解析
                if(preg_match("/ where /",$param['sql'])){
                    $param['sql'] = $param['sql'].' and';
                }else{
                    $param['sql'] = $param['sql'].' where';
                }
                $analysis = '';
                foreach ($param['search'] as $key => $value) {
                    if(($value[1]=='between' || $value[1]=='not_between') && is_array($value[0]) && count($value[0]) == 2){
                        $analysis .=($analysis?' and ':'').' '. $key .' '.$value[1].' \''.$value[0][0].'\' and \''.$value[0][1].'\'';
                    }
                }
                $param['sql'] = $param['sql'].$analysis;
                // 拼接分页，排序
                if(!empty($param['order_by'])){
                    $orderCount = 0 ;
                    foreach($param['order_by'] as $orderKey => $orderRule){
                        if($orderCount < 1){
                            // 首次
                            if(!empty($tableName)){
                                // 拼上表名
                                $param['sql'] = $param['sql']." order by ".$tableName.'.'.$orderKey." $orderRule";
                            }else{
                                $param['sql'] = $param['sql']." order by ".$orderKey." $orderRule";
                            }
                            $orderCount++;
                        }else{
                            if(!empty($tableName)){
                                // 拼上表名
                                $param['sql'] = $param['sql'].",".$tableName.'.'.$orderKey." $orderRule";
                            }else{
                                $param['sql'] = $param['sql'].",".$orderKey." $orderRule";
                            }
                            
                        }
                    }
                }
                // $param['sql'] = $param['sql']." limit ".$param['page'] * $param['limit'].",".($param['page']+1) * $param['limit'];
                // dd($config);
                $attendance     = app($this->externalDatabaseService)->getExternalDatabasesTableData($param);
            }else{
                // 从外部数据库取考勤数据(取出规定时间范围内的考勤记录)
                $attendance     = app($this->externalDatabaseService)->getExternalDatabasesTableData($param);
            }
            if (isset($attendance['code'])) {
                return ['code' => ['0x044040', 'attendance']];
            }
            // dd($param);
            // dd(strtotime('2020-10-19 12:00:00',strtotime('2020-10-18')));
            // 增加起始日期到config

            $config->startToEnd = [$start,$end];
//            插入考勤数据到考勤系统内
            $this->insertAttendance($attendance, $config, $user_id);
//            多次取数据
            while (!empty($attendance) && count($attendance) >= $param['limit']-1 && $param['page'] < 200) {
                // 此处由于sql不支持分页，导致死循环
                // var_dump(22);
                //无论如何这边都会执行，也就是说即使是少于1000条数据这边都会执行，只不过第二次返回为空数组。
                $param['page']++;
                $attendance = app($this->externalDatabaseService)->getExternalDatabasesTableData($param);
                $this->insertAttendance($attendance, $config, $user_id);
            }
//            此处的发送通知改到外侧解决多考勤机多次推送

        }
    }


    /**
     * 插入考勤数据到考勤系统
     *
     */
    public function insertAttendance($attendance, $config, $user_id)
    {
        $sign      = $config->sign_in;
        $out       = $config->sign_out;
        $sign_date = isset($config->sign_date) ? $config->sign_date : "";
//        查找用户考勤机用户id与OA用户id的对应表数据
        $matchUser = DB::table('attendance_macth_user')->get();
        $user      = [];
        foreach ($matchUser as $key => $value) {
//            sync_user是多考勤机的该考勤机下的同步用户id
            if ($config->machine_type == "1" && in_array($value->user_id, $config->sync_user)) {
                $user[$value->attendance_id] = $value->user_id;
            } else if ($config->machine_type == "0") {
                $user[$value->attendance_id] = $value->user_id;
            }
        }
        $result      = [];
        $all_records = [];
        foreach ($attendance as $key => $value) {
//            循环1000条记录
            if (!empty($sign_date) && $sign != $sign_date) {
//                sign_date（考勤日期）不为空且不等于签到日期
                $date = date("Y-m-d", strtotime($value->$sign_date));
            } else {
                $date = date("Y-m-d", strtotime($value->$sign));
            }
            $index_user = $config->user;
//            user是考勤数据中的用户id字段。
            $userId = trim($value->$index_user);//取到考勤机的userid
            if (!isset($user[$userId])) {   //不存在考勤机id为key的数组下标则跳过此次循环（用于判断考勤机用户与OA用户是否绑定）
                continue;
            }
            $userId = $user[$userId]; //取到OA的用户id
            if ($sign != $out) {
//                签到签退不是同一个字段
//                一般不会存在只有$sign或$out,因为两条记录的话一般都是同一个字段，否则会放在一条记录两个字段。
                $sign_in_time = '';
                $sign_out_time = '';

                if(!empty($value->$sign) && strtotime($value->$sign)>0){
                    $sign_in_time  = date("Y-m-d H:i:s", strtotime($value->$sign,strtotime($date)));
                    // $sign_in_time  = date("Y-m-d H:i:s", strtotime($value->$sign)); 测试正常后删除
                }
                if(!empty($value->$out) && strtotime($value->$out)>0){
                    $sign_out_time = date("Y-m-d H:i:s", strtotime($value->$out,strtotime($date)));
                    // $sign_out_time = date("Y-m-d H:i:s", strtotime($value->$out)); 测试正常后删除
                }

                if (!$sign_in_time) {   //包含了排除既没签到又没签退的数据与没签到有签退的数据
                    continue;
                }
                $result[] = [
                    "user_id"       => $userId,
                    "sign_in_time"  => $sign_in_time,
                    "sign_out_time" => $sign_out_time,
                    "sign_date"     => $date,
                    'platform' => 8,
                    'form_type' => 'diff'
                ];
            } else {
//              签到签退为同一个字段
                if (isset($result[$userId . $date])) {
//                    判断是否有同一个user下同一天的考勤记录，有就覆盖签退记录
                    $result[$userId . $date]['sign_out_time'] = date("Y-m-d H:i:s", strtotime($value->$out,strtotime($date)));
                    $result[$userId . $date]['check_list'][] = date("Y-m-d H:i:s", strtotime($value->$out,strtotime($date)));
                    // $result[$userId . $date]['sign_out_time'] = date("Y-m-d H:i:s", strtotime($value->$out)); 测试正常后删除
                    // $result[$userId . $date]['check_list'][] = date("Y-m-d H:i:s", strtotime($value->$out)); 测试正常后删除
                } else {
                    $result[$userId . $date] = [
                        "user_id"      => $userId,
                        "sign_in_time" => date("Y-m-d H:i:s", strtotime($value->$sign,strtotime($date))),
                        // "sign_in_time" => date("Y-m-d H:i:s", strtotime($value->$sign)), 测试正常后删除
                        "sign_date"    => $date,
                        'platform' => 8,
                        'form_type' => 'same',
                        'check_list' => [date("Y-m-d H:i:s", strtotime($value->$sign,strtotime($date)))]
                        // 'check_list' => [date("Y-m-d H:i:s", strtotime($value->$sign))] 测试正常后删除
                    ];
                }
                if (!isset($result[$userId . $date]['sign_out_time'])) {
                    $result[$userId . $date]['sign_out_time'] = '';
                }
            }

        }
//        将解析后的考勤数据同步到考勤系统中
        $startToEnd = $config->startToEnd;
        $resRecordHand = $this->attendMachineImport2($result,$startToEnd);
        // $this->attendMachineImport($result);
        if(!empty($resRecordHand)){
        	foreach ($resRecordHand as $key => $value) {
        		if($value == 'handle'){
	            // 对于算法处理的多排班已经记录日志了故退出
        			// $logHandled[$data['user_id'].$data['sign_date']] = 'handle';
	            	unset($result[$key]);
        		}
        	}
        }
//        插入同步记录
        if (!empty($result)) {
            array_map(function ($record) {
                if (isset($record['sign_in_time']) && !empty($record['sign_in_time'])) {
                    $recordLog = [
                        'checktime' => $record['sign_in_time'],
                        'user_id' => $record['user_id'] ?? '',
                        'sign_date' => $record['sign_date'],
                        'type' => 1,
                        'platform' => 8,
                    ];
                    $this->checkAndInsertSimpleRecord($recordLog);
                    // 判断该记录是否在redis中
                    // $redisKey = $record['user_id'].$record['sign_in_time'];
                    // // 获取年月
                    // $dataMonth = substr($record['sign_date'],0,7);
                    // $hashKey = 'attendance01-'.$dataMonth;
                    // if(!Redis::hExists($hashKey,$redisKey)){
                    //     // 查找在签到哈希表里查
                    //     Redis::hSet($hashKey,$redisKey,1);
                    //     Redis::expire($hashKey,5356800);
                    //     app($this->attendanceService)->addSimpleRecords($record['user_id'], $record['sign_date'], $record['sign_in_time'], 1, 8, '', '', '', '', '');
                    // }
                    
                }
            }, $result);
        }
        if (!empty($result)) {
            array_map(function ($record) {
                if (isset($record['sign_out_time']) && !empty($record['sign_out_time'])) {
                    // 判断该记录是否在redis中
                    $recordLog = [
                        'checktime' => $record['sign_out_time'],
                        'user_id' => $record['user_id'] ?? '',
                        'sign_date' => $record['sign_date'],
                        'type' => 2,
                        'platform' => 8,
                    ];
                    $this->checkAndInsertSimpleRecord($recordLog);
                    
                }
            }, $result);
        }

    }

    // 目前仅用于前端用户关联页面筛选返回列表
    public function getUserList($param)
    {
        if(!empty($param['search'])){
            $attendanceIdParam = json_decode($param['search'],true);
            if(!empty($attendanceIdParam['attendance_id'][0])){
                $countAttendance = DB::table("attendance_macth_user")->where('attendance_id','like','%'.$attendanceIdParam['attendance_id'][0].'%')->count();
                if($countAttendance == 0){
                    return ['total' => 0, 'list' => []];
                }
                $matchUser = DB::table("attendance_macth_user")->where('attendance_id','like','%'.$attendanceIdParam['attendance_id'][0].'%')->get();
                unset($param['search']);
            }else{
                $matchUser = DB::table("attendance_macth_user")->get();
            }
        }else{
            $matchUser = DB::table("attendance_macth_user")->get();
        }
        if(!empty($matchUser)){
            $matchUser = $matchUser->toArray();
        }
        
        $param     = $this->parseParams($param);
        if(isset($countAttendance)){
            $userTotal = $countAttendance;
            $userIds = '';
            foreach($matchUser as $match => $user){
                if(empty($userIds)){
                    $userIds = $user->user_id;
                }else{
                    $userIds = $userIds.','.$user->user_id;
                }
            }
            $param['search']['user_id'] = [$userIds];
        }else{
            $userTotal = app($this->userRepository)->getUserListTotal($param);
        }
        $userLists = app($this->userRepository)->getUserList($param);
        foreach ($userLists as $key => $value) {
            $userLists[$key]['attendance_id'] = '';
        }
        foreach ($userLists as $key => $value) {
            foreach ($matchUser as $k => $v) {
                if ($v->user_id == $value['user_id']) {
                    $userLists[$key]['attendance_id'] = $v->attendance_id;
                }
            }
        }
        return ['total' => $userTotal, 'list' => $userLists];

    }
    // 关联考勤机用户
    public function matchUser($data)
    {
        $insert['user_id']       = isset($data['user_id']) ? $data['user_id'] : '';
        $insert['attendance_id'] = isset($data['attendance_id']) ? trim($data['attendance_id']) : '';
        // 这边操作的是如果这个考勤机用户id已经有被用了就清空
        // $attendance              = DB::table("attendance_macth_user")->where("attendance_id", $insert['attendance_id'])->first();
        // if (!empty($attendance)) {
        //     DB::table("attendance_macth_user")->where("attendance_id", $insert['attendance_id'])->update(["attendance_id" => '']);
        // }
        $matchUser = DB::table("attendance_macth_user")->where("user_id", $insert['user_id'])->first();
        if (empty($matchUser)) {
            return DB::table("attendance_macth_user")->insertGetId($insert);
        } else {
            return DB::table("attendance_macth_user")->where("user_id", $insert['user_id'])->update($insert);
        }

    }

    public function ImportAttendanceMachineFields()
    {
        $user = app($this->userRepository)->getAttendanceUser();
        $config = $this->getMachineConfig();
        $machineList = [];
        $userMachineList = []; // userid => '考勤机ID'
        // 获取考勤机列表
        // if(!empty($config['single'])){
        //     foreach($config['single'] as $single){
        //         $machineList[] = trans("attendance.single_machine").':id-'.$single->id.'-'.$single->tabs_title;
        //         // 处理该考勤机下的用户
        //         if(!empty($single->sync_user)){
        //             $transUserList = $this->transferUser($single->sync_user);
        //             if(!empty($transUserList)){
        //                 foreach($transUserList as $machineUserId){
        //                     if(isset($userMachineList[$machineUserId])){
        //                         $userMachineList[$machineUserId] = $userMachineList[$machineUserId].','.$single->id;
        //                     }else{
        //                         $userMachineList[$machineUserId] = $single->id;
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }
        if(!empty($config['multi'])){
            foreach($config['multi'] as $multi){
                $machineList[] = 
                [
                    'attendance_machine_id' => $multi->id,
                    'attendance_machine_name' => $multi->tabs_title,
                ];
                // 处理该考勤机下的用户
                if(!empty($multi->sync_user)){
                    $transUserList = $this->transferUser($multi->sync_user);
                    if(!empty($transUserList)){
                        foreach($transUserList as $machineUserId){
                            if(isset($userMachineList[$machineUserId])){
                                $userMachineList[$machineUserId] = $userMachineList[$machineUserId].','.$multi->id;
                            }else{
                                $userMachineList[$machineUserId] = $multi->id;
                            }
                        }
                    }
                }
            }
        }
        
        // 将数据拼接上用户数组
        foreach($user as $index => $userinfo){
            $user[$index]['user_attendance_machine_id'] = $userMachineList[$userinfo['user_id']] ?? '';
            // $user[$index]['attendance_machine_id'] = $machineList[$index] ?? '';
        }

        return [
            [
                'sheetName' => trans("attendance.import_template"),
                'header'    => [
                    'user_id'                    => trans("attendance.user_id"),
                    'user_name'                  => trans("attendance.user_name"), //user_accounts改成了user_name
                    'user_accounts'              => trans("attendance.user_accounts"),
                    'attendance_id'              => trans("attendance.attendance_user_id"),
                    'user_attendance_machine_id' => trans("attendance.user_attendance_machine_id"),
                ],
                'data'      => $user,
            ],
            [
                'sheetName' => trans("attendance.multi_machine_in_system"),
                'header'    => [
                    'attendance_machine_id'   => trans("attendance.attendance_machine_id"),
                    'attendance_machine_name' => trans("attendance.machine_name"),
                ],
                'data'      => $machineList,
            ],
        ];
    }
    // 定时任务获取定时任务执行时间函数
    public function getAttendanceTime()
    {
        $config = $this->getMachineConfig();
        if (!empty($config)) {
            $machine_type = $config['machine_type'];
            if ($machine_type == "single") {
                // 现在这个方法返回的也是数组，默认返回第一个是生效
                if(!empty($config['single'][0])){
                    $attendance = $config['single'][0];
                }else{
                    $attendance = '';
                }

                if (!empty($attendance) && !empty($attendance->update_time) && $attendance->is_auto == 1) {
                    return $attendance->update_time;
                }
            } else {
                if (isset($config['multi'])) {
                    $multis = $config['multi'];
                    foreach ($multis as $multi) {
                        if ($multi->is_auto == 1) {
                            // 多考勤机的时间同步时间是共用的
                            return $multi->update_time;
                        }
                    }
                }
            }
        }
        // 未发现之前的脚本问题，添加一个返回空
        return '';
    }
//        [
//            'admin' => [
//                '2019-01-02' => [
//                    ['sign_date' => '2019-01-02', 'sign_times' => 1, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8],
//                    ['sign_date' => '2019-01-02', 'sign_times' => 2, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8]
//                ],
//                '2019-01-02' => [
//                    ['sign_date' => '2019-01-02', 'sign_times' => 1, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8],
//                    ['sign_date' => '2019-01-02', 'sign_times' => 2, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8]
//                ]
//            ]
//        ];
    /**
     * 新的考勤机
     */
    public function attendMachineImport2($datas,$startToEnd)
    {
        $batchSignData = [];
        $countNumber = [];
        foreach ($datas as $data) {
            $signDate = date('Y-m-d', strtotime($data['sign_date']));  //考勤数据的日期
            $userId   = $data['user_id'];
            // $shiftInfo = $this->schedulingMapWithUserIdsByDate('2019-08-30',[$userId]);
            $shiftInfo = $this->schedulingMapWithUserIdsByDate($signDate,[$userId]);
            if($shiftInfo[$userId] != null){
                $shift = $this->getShiftById($shiftInfo[$userId]['shift_id'])->toArray();
                $shiftTime = $this->getShiftTimeById($shiftInfo[$userId]['shift_id'])->toArray();
                // shift_type等于1表示是单排班
                if($shift['shift_type'] == 1){
                    // 判断因为分页获取导致签到签退分开获取到，可能会变成分开2条签到数据
                    if(empty($data['sign_out_time'])){
                        // 查询该人是否有签到
                        $existAttendanceRecord = app($this->attendanceRecordsRepository)->getOneAttendRecord(['sign_date' => [$signDate], 'user_id' => [$userId], 'sign_times' => [1]]);
                        if($existAttendanceRecord){
                            $existAttendanceRecord = $existAttendanceRecord->toArray();
                            if(!empty($existAttendanceRecord['sign_in_time']) && $data['sign_in_time'] > $existAttendanceRecord['sign_in_time']){
                                $data['sign_out_time'] = $data['sign_in_time'];
                                $data['sign_in_time'] = $existAttendanceRecord['sign_in_time'];
                                $data['in_address'] = $existAttendanceRecord['in_address'];
                            }
                        }
                    }
                    $batchSignData[$userId][$signDate][] = [
                        'sign_date' => $signDate,
                        'sign_nubmer' => 1,
                        'sign_in_time' => $data['sign_in_time'],
                        'sign_out_time' => $data['sign_out_time'],
                        'platform' => $data['platform']
                    ];
                }
                // shift_type等于2表示是多排班
                if($shift['shift_type'] == 2){
                    if($data['platform'] == 9){
                        // xls导入支持多排班
                        $batchSignData[$userId][$signDate][] = [
                            'sign_date' => $signDate,
                            'sign_nubmer' => $data['sign_nubmer'],
                            'sign_in_time' => $data['sign_in_time'],
                            'sign_out_time' => $data['sign_out_time'],
                            'platform' => $data['platform']
                        ];
                    }
                    if($data['platform'] == 8){
                        // 考勤机导入处理多排班
                        // 判断数据源的类型
                        if(isset($data['form_type']) && $data['form_type'] == 'same' && !empty($data['check_list'])){
                            // 打卡一次就是一条记录得处理
                            // 获取多排班
                            // dd($data['check_list']);
                            $shiftData = app($this->attendanceService)->getShiftTimesGroupByDate($data['sign_date'],$data['sign_date'],$data['user_id']);
                            $shiftTime = $shiftData[$data['sign_date']]->times->toArray();
                            // 将多排班的时间转化为日期时间格式
                            $shiftTime = $this->parseShiftTime($shiftTime,$data['sign_date']);
                            // 先插入日志，全部都不标识签到签退
                            foreach($data['check_list'] as $logTime){
                                $recordLog = [
                                    'checktime' => $logTime,
                                    'user_id' => $data['user_id'] ?? '',
                                    'sign_date' => $data['sign_date'] ?? '',
                                    'type' => 4,
                                    'platform' => 8,
                                ];
                                $this->checkAndInsertSimpleRecord($recordLog);
                                // app($this->attendanceService)->addSimpleRecords($data['user_id'], $data['sign_date'], $logTime, 4, 8, '', '', '', '', '');
                            }
                            $logHandled[$data['user_id'].$data['sign_date']] = 'handle';
                            // 处理多排班的时间区间逻辑算法
                            $timeList = $this->handleShiftTime($shiftTime,$data['check_list']);
                            // dd($timeList);
                            // 拼接数据传入到考勤模块
                            if(is_array($timeList) && count($timeList)>0 ){
                                foreach($timeList as $index => $checkTime){
                                    if(!empty($checkTime['sign_in_time']) || !empty($checkTime['sign_out_time'])){
                                        $batchSignData[$userId][$signDate][] = [
                                            'sign_date' => $signDate,
                                            'sign_nubmer' => $index + 1,
                                            'sign_in_time' => $checkTime['sign_in_time'] ?? '',
                                            'sign_out_time' => $checkTime['sign_out_time'] ?? '',
                                            'platform' => $data['platform'],
                                            'source' => 'same',
                                        ];
                                    }
                                }
                            }
                        }
                        if(isset($data['form_type']) && $data['form_type'] == 'diff'){
                            // 一般考勤机已经处理无效数据
                            // 此处数据一次只来一条需要记录当前的多排班次数
                            if(isset($countNumber[$signDate])){
                                $countNumber[$signDate]++;
                                if($countNumber[$signDate] > count($shiftTime)){
                                    continue;
                                }
                            }else{
                                $countNumber[$signDate] = 1;
                            }
                            if(empty($data['sign_in_time']) && empty($data['sign_in_time'])){
                                continue;
                            }
                            $batchSignData[$userId][$signDate][] = [
                                'sign_date' => $signDate,
                                'sign_nubmer' => $countNumber[$signDate],
                                'sign_in_time' => $data['sign_in_time'] ?? '',
                                'sign_out_time' => $data['sign_out_time'] ?? '',
                                'platform' => $data['platform']
                            ];
                        }
                        // 测试数据
                        // $batchSignData[$userId][$signDate][] = [
                        //     'sign_date' => '2020-03-06',
                        //     'sign_nubmer' => 1,
                        //     'sign_in_time' => '2020-03-06 07:59:57',
                        //     'sign_out_time' => '2020-03-06 09:59:57',
                        //     'platform' => $data['platform']
                        // ];
                        // $batchSignData[$userId][$signDate][] = [
                        //     'sign_date' => '2020-03-06',
                        //     'sign_nubmer' => 2,
                        //     'sign_in_time' => '2020-03-06 12:59:57',
                        //     'sign_out_time' => '2020-03-06 18:59:57',
                        //     'platform' => $data['platform']
                        // ];
                    }
                    
                }
            }else{
                // 非工作日考勤导入
                $batchSignData[$userId][$signDate][] = [
                    'sign_date' => $signDate,
                    'sign_nubmer' => $data['sign_nubmer'] ?? 1,
                    'sign_in_time' => $data['sign_in_time'],
                    'sign_out_time' => $data['sign_out_time'],
                    'platform' => $data['platform']
                ];
            }
        }
        $batchSignData = $this->parseRecordExist($batchSignData);
        // 将两个时间转化为日期格式
        // $startToEnd[0] = date_format(date_create($startToEnd[0]),'Y-m-d');
        // $startToEnd[1] = date_format(date_create($startToEnd[1]),'Y-m-d');
        $res = app($this->attendanceService)->batchSign($batchSignData,$startToEnd[0],$startToEnd[1]);
        if(!empty($logHandled)){
            return $logHandled;
        }
    }

    /**
     * 解析排班日期格式为带上日期时间
     * @param shiftTime 排班时间的数组
     * @param date 日期
     */
    public function parseShiftTime($shiftTime,$date){
        if(is_array($shiftTime) && count($shiftTime)>0){
            foreach($shiftTime as $index => $times){
                if(is_array($times) && count($times) > 0){
                    foreach($shiftTime[$index] as $type => $time){
                        $stamp = strtotime($date.' '.$time);
                        $shiftTime[$index][$type] = date('Y-m-d H:i:s',$stamp);
                    }
                }
            }
        }
        return $shiftTime;
        
    }

    /**
     * 多排班下的区间算法
     * @param shiftTime 排班的时间
     * @param times 实际上的考勤时间
     */
    public function handleShiftTime($shiftTime,$times){
        $checkList = [];
        $shiftCount = count($shiftTime);
        $timeCount = count($times);
        $num = 0;
        foreach($times as $index => $time){
            // if($timeCount <= $shiftCount * 2){
            //     // 考勤时间少与排班时间则按顺序
            //     $parseIndex = (int)floor($index/2);
            //     if($index / 2 == 0){
            //         // 复数
            //         $checkList[$parseIndex]['sign_in_time'] = $time;
            //     }else{
            //         // 单数
            //         $checkList[$parseIndex]['sign_out_time'] = $time;
            //     }
            // }else{
                start:
                //新简化逻辑
                if($num + 1 >= $shiftCount){
                    if(empty($checkList[$num]['sign_in_time'])){
                        $checkList[$num]['sign_in_time'] = $time;
                        continue;
                    }else{
                        $checkList[$num]['sign_out_time'] = $time;
                        continue;
                    }
                }
                if($time > $shiftTime[$num]['sign_out_time'] && empty($checkList[$num]['sign_in_time'])){
                    // 当前班没有签到但是打卡时间又大与当前班的签退时间直接跳过当前班次,
                    $num++;
                    goto start;
                }
                if(empty($checkList[$num]['sign_in_time'])){ 
                    // 如果没有签到，则为签到
                    $checkList[$num]['sign_in_time'] = $time;
                    continue;
                }else{
                    if($time > $shiftTime[$num]['sign_in_time'] && $time <= $shiftTime[$num]['sign_out_time']){
                        // 在排班区间内
                        $checkList[$num]['sign_out_time'] = $time;
                        continue;
                    }
                    if($time > $shiftTime[$num]['sign_out_time']){
                        // 大于排班的签退时间了
                        if(!empty($checkList[$num]['sign_out_time']) && $checkList[$num]['sign_out_time'] >= $shiftTime[$num]['sign_out_time']){
                            $num++;
                            goto start;
                        }else{
                            $checkList[$num]['sign_out_time'] = $time;
                            continue;
                        }
                    }
                }


                // 考勤时间大于排班应有的时间
                // if($index == 0){
                //     // 第一条记录肯定是签到时间
                //     $checkList[$num]['sign_in_time'] = $time;
                //     continue;
                // }
                // if(!empty($checkList[0]['sign_in_time']) && $time <= $shiftTime[0]['sign_in_time']){
                //     // 第一个排班已经有签到并且时间不超过签到日期则不更新
                //     continue;
                // }
                // if($shiftCount <= 1){
                //     // 多排班却只有一个组，只要更新签退时间
                //     $checkList[0]['sign_out_time'] = $time;
                // }
                // if(!empty($checkList[0]['sign_in_time']) && $time > $shiftTime[0]['sign_in_time'] && $time < $shiftTime[0]['sign_out_time'] ){
                //     // 在第一班的区间内
                //     $checkList[0]['sign_out_time'] = $time;
                //     continue;
                // }

                // // 开始处理下一班
                // if($time >= $shiftTime[$num]['sign_out_time']){
                //     if($num >= $shiftCount){
                //         // 没有下一班了则都是本班次的签退
                //         $shiftTime[$num]['sign_out_time'] = $time;
                //         continue;
                //     }
                //     // 还有下一班
                //     // 当前班有签退并且时间大于当前班的排班签退时间，则为下一班的签到
                //     if(!empty($checkList[$num]['sign_out_time']) ){}
                //     if(empty($checkList[$num+1]['sign_in_time'])){
                //         // 下一班还没签到
                //         $num++;
                //         $checkList[$num]['sign_in_time'] = $num;
                //         continue;
                //     }
                    
                //     // 第二条就暂时是签退，等后续更新
                //     $checkList[$num]['sign_out_time'] = $time;
                //     continue;
                // }
            // }
        }
        return $checkList;
    }



    public function getAttendanceRecords($data)
    {
        $user_id     = isset($data['user_id']) ? $data['user_id'] : '';
        $sign_date   = isset($data['sign_date']) ? $data['sign_date'] : '';
        $recordList  = app($this->attendanceRecordsRepository)->getAttendanceRecord($user_id, $sign_date, 'get');
        $recordTotal = app($this->attendanceRecordsRepository)->getAttendanceRecord($user_id, $sign_date, 'count');
        return ['total' => $recordTotal, 'list' => $recordList];

    }

    /**
     * excel导入同步OA用户与考勤机用户的关联关系
     */
    public function importAttendanceMachine($data, $params)
    {
        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];
        $updateMachineUser = [];
        foreach ($data as $key => $value) {
            if (empty($value['user_id'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                continue;
            }
            $success                 = false;
//            此处添加过滤两侧空字符串的操作
//            var_dump($value['attendance_id']);
            $insert['user_id']       = trim($value['user_id'],' ');
            $insert['attendance_id'] = trim($value['attendance_id'],' ');
            $noData                  = DB::table("attendance_macth_user")->where("user_id", $value['user_id'])->first();
//            var_dump($insert['attendance_id']);
            if ($noData) {
//                有数据就更新关联的id
                $success = DB::table("attendance_macth_user")->where("user_id", $value['user_id'])->update($insert);
                // 此处处理考勤机与用户的关联
                $result = $this->handleMachineUser($value,$updateMachineUser);
                if($result){
                    $info['success']++;
                    $data[$key]['importResult'] = importDataSuccess();
                }else{
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                }
                
            } else {
//                没数据就去查找user_id是否在用户表中存在就插入到关联表
                $import_user_name = app($this->userService)->getUserName($value['user_id']);
                if($import_user_name){
////                    插入用户到考勤机用户关联表
                    $insert_match_id = DB::table("attendance_macth_user")->insert($insert);
//                    dd($insert_match_id);
//                    之前用insertGetId但是这张表没有自增id所以插入成功也返回了0.
                    if($insert_match_id){
                        // 此处处理考勤机与用户的关联
                        $result = $this->handleMachineUser($value,$updateMachineUser);
                        if($result){
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                        }else{
                            $info['error']++;
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                        }
                    }else{
                        $info['error']++;
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                    }
                }else{
//                    返回插入失败
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                }

            }
            $this->updateToMachine($updateMachineUser);

        }
        return compact('data', 'info');
    }

    /**
     * 处理导入用户时的用户与考勤机的关联
     */
    public function handleMachineUser($data,&$updateMachineUser)
    {
        // 传入用户id与考勤机ID
        if(!empty($data['user_attendance_machine_id']) && !empty($data['user_id'])){
            // 去除关联考勤机id的两侧空格
            $data['user_attendance_machine_id'] = trim($data['user_attendance_machine_id'],' ');
            $machineIds = explode(',',$data['user_attendance_machine_id']);
            foreach($machineIds as $machineId){
                // 判断该考勤机id是否存在不存在则返回错误
                $result = app($this->attendanceMachineConfigRepository)->checkId($machineId);
                if($result){
                    if(empty($updateMachineUser[$machineId])){
                        $updateMachineUser[$machineId]['sync_user'] = '{"user_id":["'.$data['user_id'].'"]}';
                    }else{
                        // 解析
                        $updateArr = json_decode($updateMachineUser[$machineId]['sync_user'],true);
                        if(!in_array($data['user_id'],$updateArr['user_id'])){
                            array_push($updateArr['user_id'],$data['user_id']);
                        }
                        $updateMachineUser[$machineId]['sync_user'] = json_encode($updateArr);
                    }
                }else{
                    // 该id不正确
                    return false;
                }
            }
        }
        return true;
    }

    // 将解析好的考勤机与用户关系更新到数据库
    public function updateToMachine($data){
        if(!$data){
            return true;
        }
        foreach($data as $userId => $updateData){
            app($this->attendanceMachineConfigRepository)->entity->where('id',$userId)->where('machine_type',1)->update($updateData);
        }
        
    }

    public function getImportAttendanceFields()
    {
        return [
            'header' => [
                'attendance_id' => trans('attendance.attendance_machine_user_id'),
                'sign_date'     => trans('attendance.date_of_attendance'),
                'sign_time'     => trans('attendance.time_of_attendance'),
                'scheduling_id' => trans("attendance.scheduling_id"),
//                'sign_status' => '签到签退标志（可为空）'
            ],
            'data'   => [
                [
                    'attendance_id' => '00001',
                    'sign_date'     => '2018-01-01',
                    'sign_time'     => '18:18:18',
                    'scheduling_id' => '1',
                ],
            ],
        ];
    }

    /**
     * 考勤数据xls导入的入口，excel导入考勤数据处理函数
     */
    public function importAttendance($data, $params)
    {
        if (empty($data)) {
            return [];
        }
        $records   = $allRecords   = [];
        $matchUser = DB::table('attendance_macth_user')->get();
        $user      = [];
        foreach ($matchUser as $key => $value) {
            $attendId = trim($value->attendance_id);
            $user[$attendId] = trim($value->user_id);
        }
        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];
        foreach ($data as $key => $item) {
            // 根据用户关联表获取考勤机id
            $attendId = $item['attendance_id'] ? trim($item['attendance_id']) : '';
            if (!(isset($user[$attendId]) && $user[$attendId])) {
                // 该考勤机id下没有系统用户id表示没有关联
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('attendance.un_sysc_user'));
                continue;
            }
            // 临时处理，如果四个字段都是为null的话就跳过
            if(empty($item['attendance_id']) && empty($item['sign_date']) && empty($item['sign_time']) && empty($item['scheduling_id'])){
                continue;
            }
            if (!(isset($item['sign_date']) && $item['sign_date'])) {
                // dd(1);
                // 缺少数据
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('attendance.attendance_date_is_empty'));
                continue;
            }
            if (!(isset($item['sign_time']) && $item['sign_time'])) {
                // 缺少数据
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('attendance.attendance_time_is_empty'));
                continue;
            }
            $userId       = $user[$attendId];
            $signDate     = trim($item['sign_date']);
            $signTime     = trim($item['sign_time']);
            $schedulingId = trim(isset($item['scheduling_id']) ? $item['scheduling_id'] : "1");
            $signTime = $this->makeFullDatetime($signTime, $signDate); // 拼接完整的日期格式
            $allRecords[] = [   //用于后续的写日志
                "user_id"      => $userId,
                "sign_date"    => $signDate,
                "sign_in_time" => $signTime,
                "in_platform"  => 9,
            ];
            $records[$userId . '.' . $signDate][$schedulingId][] = $signTime; //生成一条key数据
            $info['success']++;
            $data[$key]['importResult'] = importDataSuccess();
        }
        if (!empty($records)) {
            // 封装datas一次性传入新的方法
            $transDatas = [];
            $minDate = '';
            $maxDate = '';
            foreach ($records as $key => $signArray) {
                // signNumber考勤次数，excel填入的，signTimeArray是该考勤次数下的时间数组。
                foreach ($signArray as $signNumber => $signTimeArray) {
                    // 将时间数组进行排序
                    sort($signTimeArray);
                    list($userId, $signDate) = explode('.', $key);// 从key中解析出userId与signDate
                    $signInTime              = $signTimeArray[0];
                    if (sizeof($signTimeArray) >= 2) {
                        $signOutTime = $signTimeArray[sizeof($signTimeArray) - 1];// 取最后一个时间
                    } else {
                        $signOutTime = '';
                    }

                    $signData = [
                        'user_id'       => $userId,
                        'sign_date'     => $signDate,
                        'sign_in_time'  => $signInTime ? $signInTime : $signOutTime,
                        'sign_out_time' => $signOutTime,
                        'platform'      => 9,
                        'sign_nubmer'   =>  $signNumber,
                    ];
                    // $this->attendMachineAccess($signData);
                    $transDatas[] = $signData;
                    // 处理日期起始
                    if(empty($minDate)){
                        $minDate = $signDate;
                    }else{
                        $minDate = $minDate<$signDate ? $minDate : $signDate;
                    }
                    if(empty($maxDate)){
                        $maxDate = $signDate;
                    }else{
                        $maxDate = $maxDate>$signDate ? $maxDate : $signDate;
                    }

                }
            }
            $startToEnd = [$minDate,$maxDate];
            $this->attendMachineImport2($transDatas,$startToEnd);
        }
        // if (!empty($allRecords)) {
        //     array_map(function ($record) {
        //         app($this->attendanceService)->addSimpleRecords($record['user_id'], $record['sign_date'], $record['sign_in_time'], 3, 9, '', '', '', '', '');
        //     }, $allRecords);
        // }

        // 插入同步记录
        if (!empty($transDatas)) {
            array_map(function ($record) {
                if (isset($record['sign_in_time']) && !empty($record['sign_in_time'])) {
                    $recordLog = [
                        'checktime' => $record['sign_in_time'],
                        'user_id' => $record['user_id'] ?? '',
                        'sign_date' => $record['sign_date'] ?? '',
                        'type' => 1,
                        'platform' => 9,
                    ];
                    $this->checkAndInsertSimpleRecord($recordLog);
                    // app($this->attendanceService)->addSimpleRecords($record['user_id'], $record['sign_date'], $record['sign_in_time'], 1, 9, '', '', '', '', '');
                }
            }, $transDatas);
        }
        if (!empty($transDatas)) {
            array_map(function ($record) {
                if (isset($record['sign_out_time']) && !empty($record['sign_out_time'])) {
                    $recordLog = [
                        'checktime' => $record['sign_out_time'],
                        'user_id' => $record['user_id'] ?? '',
                        'sign_date' => $record['sign_date'] ?? '',
                        'type' => 2,
                        'platform' => 9,
                    ];
                    $this->checkAndInsertSimpleRecord($recordLog);
                    // app($this->attendanceService)->addSimpleRecords($record['user_id'], $record['sign_date'], $record['sign_out_time'], 2, 9, '', '', '', '', '');
                }
            }, $transDatas);
        }

        return compact('data', 'info');
    }




    public function addImportLog($userId)
    {
        $data = [
            'creator'         => $userId,
            'import_datetime' => date('Y-m-d H:i:s'),
        ];
        return app($this->attendanceImportLogsRepository)->insertData($data);
    }

    public function getImportLogs($params)
    {
        return $this->response(app($this->attendanceImportLogsRepository), 'getImportLogsCount', 'getImportLogsList', $this->parseParams($params));
    }

    /**
     * 旧的考勤机同步导入数据接口
     */
    public function attendMachineImport($datas)
    {
        static $userSchedule     = [];
        static $userScheduleInfo = [];
        static $signTimeArray    = [];

        foreach ($datas as $data) {
            // 下面是判断单条考勤记录是否满足排班要求以及处理时间
            $signDate = date('Y-m-d', strtotime($this->defaultValue('sign_date', $data, date('Y-m-d'))));
            $userId   = $data['user_id'];

            $shift = app($this->attendanceService)->checkUserScheduling($signDate, $userId, function($scheduling, $shift){
                //当天未设置排班，并且不允许非工作日考勤
                if (!$shift && $scheduling->allow_sign_holiday == 0) {
                    return ['code' => ['0x044017', 'attendance']];
                }
                return null;
            });
            if(isset($shift['code'])){
                continue;
            }
            // 以下要插入数据表的字段变量
            $signTimes      = $this->defaultValue('sign_nubmer', $data, 1); //第几次考勤（正常班只有一次考勤，交换班可能有多次）
            //如果签到签退是时间格式还需拼上日期
            $signInTime     = $this->makeFullDatetime($this->defaultValue('sign_in_time', $data, date('Y-m-d H:i:s'))); //签到时间
            $signOutTime    = $this->makeFullDatetime($this->defaultValue('sign_out_time', $data, date('Y-m-d H:i:s'))); //签到时间
            $platform       = $this->defaultValue('platform', $data, 8);
            $signInNormal   = '';
            $signOutNormal  = '';
            $mustAttendTime = 0;
            $shiftId        = 0;
            $lagTime        = 0;
            $leaveEarlyTime = 0;
            $attendType     = 2;
            if ($shift) {
                $attendType = 1;
                $shiftId    = $shift->shift_id;

                if (isset($signTimeArray[$shiftId])) {
                    $signTime = $signTimeArray[$shiftId];
                } else {
                    $signTime                = app($this->attendanceShiftsSignTimeRepository)->getSignTime($shiftId, ['sign_in_time', 'sign_out_time']); //获取排班考勤时间
                    $signTimeArray[$shiftId] = $signTime;
                }
                //signTime为排班考勤时间的多维数组
                //判断考勤时间是否为空，正常情况不会出现这种问题，这里为了增强代码的健壮性
                if (count($signTime) == 0) {
                    return ['code' => ['0x044019', 'attendance']];
                }
                $signTimeNormal  = $signTime[$signTimes - 1];
                $signInNormal    = $signTimeNormal->sign_in_time;
                $signOutNormal   = $signTimeNormal->sign_out_time;
                $mustAttendTime  = $shift->shift_type == 1 ? $shift->attend_time : $this->timeDiff($signInNormal, $signOutNormal); //本次应出勤的工时
//                $leaveEarlyTime  = $this->getEarlyTime($shift, $signTime, $signOutTime,$signDate,$signTimes);//早退时间
//                $lagTime         = $this->getLagTime($shift, $signTime, $signInTime,$signDate,$signTimes); //获取迟到时间
//                var_dump('lagTime:'.$lagTime.'----leaveEarlyTime:'.$leaveEarlyTime);
//                list($lagTime, $signInTime) = $this->restLagTimeAndSignInTime($shift, $signDate, $lagTime, $leaveEarlyTime, $signInTime, $signOutTime, $signTimeNormal);
            }
            $signData = [
                'sign_date'        => $signDate,
                // 'sign_in_time'     => $signInTime,
                // 'sign_out_time'    => $signOutTime,
                'sign_in_normal'   => $signInNormal,
                'sign_out_normal'  => $signOutNormal,
                'user_id'          => $userId,
                'sign_times'       => $signTimes,
                'must_attend_time' => $mustAttendTime,
//                'lag_time'         => $lagTime,// 迟到判断依据放在下面
//                'is_lag'           => $lagTime > 0 ? 1 : 0,//迟到的标识放在下面
//                'leave_early_time' => $leaveEarlyTime,// 早退判断依据放在下面
//                'is_leave_early'   => $leaveEarlyTime > 0 ? 1 : 0,//早退的标识放在下面
//                'in_ip'            => getClientIp(),
//                'in_long'          => '',
//                'in_lat'           => '',
//                'in_address'       => '',
//                'in_platform'      => $platform,
//                'out_ip'           => getClientIp(),
//                'out_long'         => '',
//                'out_lat'          => '',
//                'out_address'      => '',
//                'out_platform'     => $platform,
                'shift_id'         => $shiftId,
                'attend_type'      => $attendType,
            ];
            if (!empty($signInTime)) {
//                不更新数据也就不存在改变平台，地址经纬度的概念
                $signData['sign_in_time'] = $signInTime;
                $signData['in_platform'] = $platform;
                $signData['in_ip'] = getClientIp();
                $signData['in_address'] = '';
                $signData['in_long'] = '';
                $signData['in_lat'] = '';
                $signData['lag_time'] = app($this->attendanceService)->getLagTime($shift, $signTime, $signInTime,$signDate,$signTimes);
                $signData['is_lag'] = $signData['lag_time'] > 0 ? 1 : 0;
//                以下方法是允许一定时间内忽略迟到的设置
                list($signData['lag_time'], $signData['sign_in_time']) = app($this->attendanceService)->restLagTimeAndSignInTime($shift, $signDate, $signData['lag_time'], $leaveEarlyTime, $signData['sign_in_time'], $signOutTime, $signTimeNormal);
            }
            if (!empty($signOutTime)) {
                $signData['sign_out_time'] = $signOutTime;
                $signData['out_platform'] = $platform;
                $signData['out_ip'] = getClientIp();
                $signData['out_address'] = '';
                $signData['out_long'] = '';
                $signData['out_lat'] = '';
                $signData['leave_early_time'] = app($this->attendanceService)->getEarlyTime($shift, $signTime, $signOutTime,$signDate,$signTimes);
                $signData['is_leave_early'] =$signData['leave_early_time'] > 0 ? 1 : 0;
            }
            $where = [
                'sign_date'  => [$signDate],
                'user_id'    => [$userId],
                'sign_times' => [$signTimes],

            ];
            if (app($this->attendanceRecordsRepository)->recordIsExists($where)) {
//                更新时判断签到签退时间早晚的功能暂时延后
//                判断签到时间取最早签退时间取最迟，这一块代码其实是要放在封装$signData数据前。因为还会影响到延伸的地址经纬度平台等的更新
//                $attend_exist_recode = app($this->attendanceRecordsRepository)->getOneAttendRecord($where);
//                if(isset($signData['sign_in_time']) && strtotime($signData['sign_in_time']) > strtotime($attend_exist_recode->sign_in_time)){
//                    插入的签到时间大于本来的时间,还需要判断时间是否是当天的（正常情况下不需要考虑测试可能会有这样的问题，下同。不作处理）$signData['sign_in_time']已经做了时间戳小于0的筛选
//                    $signData['sign_in_time'] = $attend_exist_recode->sign_in_time;
//                }
//                if(isset($signData['sign_out_time']) && strtotime($signData['sign_out_time']) < strtotime($attend_exist_recode->sign_out_time)){
//                    插入的签退时间小于本来的时间
//                    $signData['sign_out_time'] = $attend_exist_recode->sign_out_time;
//                }
                $result = app($this->attendanceRecordsRepository)->updateData($signData, $where);
            } else {
                // $result = app($this->attendanceRecordsRepository)->insertData($signData);
                if (!empty($signData) && isset($signData['sign_in_time'])) {
                    if (!isset($signData['sign_out_time'])) {
                        $signData['sign_out_time'] = '';
                    }
                    if (!empty($signData)) {
                        $sinDataArray[] = $signData;
                    }

                }

            }
        }
        if (isset($sinDataArray) && !empty($sinDataArray)) {
            $result = app($this->attendanceRecordsRepository)->insertMultipleData($sinDataArray);
        }

        foreach ($datas as $value) {
            $signDate = date('Y-m-d', strtotime($this->defaultValue('sign_date', $data, date('Y-m-d'))));
            $userId   = $value['user_id'];
        }

    }

    // 校验考勤机配置是否配置完整
    public function checkConfigComplete($config)
    {
        if(empty($config['database_id']) || empty($config['tabs_title']) || empty($config['sign_in']) || empty($config['sign_out']) || empty($config['sign_date']) || empty($config['user'])){
            return false;
        }
        if(!empty($config['record_table_source']) && $config['record_table_source'] == 'table' && !empty($config['record_table'])){
            return true;

        }
        if(!empty($config['record_table_source']) && $config['record_table_source'] == 'sql' && !empty($config['record_table_sql'])){
            // sql 
            return true;
        }
        return false;
        
    }

    // 统一的插入考勤打卡数据表
    /**
     * record 数据结构
     * [
     *      'checktime' => '' 打卡时间
     *      'user_id' => '' 用户id
     *      'sign_date' => '' 考勤日期
     *      'type' => '' 打卡类型  1签到 2签退 4打卡（多排班）
     *      'platform' => '' 平台
     * ]
     */
    public function checkAndInsertSimpleRecord($record,$pre='attendance')
    {
        if (isset($record['checktime']) && !empty($record['user_id']) && !empty($record['sign_date']) && !empty($record['type']) && !empty($record['platform'])) {
            // 判断该记录是否在redis中
            $redisKey = $record['user_id'].$record['checktime'];
            // 获取年月
            $dataMonth = substr($record['sign_date'],0,7);
            $hashKey = $pre.$record['type'].'-'.$dataMonth;
            if(!Redis::hExists($hashKey,$redisKey)){
                // 查找在签到哈希表里查
                Redis::hSet($hashKey,$redisKey,1);
                Redis::expire($hashKey,5356800);
                app($this->attendanceService)->addSimpleRecords($record['user_id'], $record['sign_date'], $record['checktime'], $record['type'], $record['platform'], '', '', '', $record['address'] ?? '', '');
            }
            
        }
    }

    public function parseRecordExist($batchSignData)
    {
        foreach($batchSignData as $recordUserId => &$recordList){
            foreach($recordList as $recordDate => &$recordDetails){
                foreach($recordDetails as &$recordDetail){
                    if(isset($recordDetail['source']) && $recordDetail['source'] == 'same'){
                        // 判断系统内是否已有考勤数据，作为兼容插入
                        $where = [
                            'sign_date'  => [$recordDate],
                            'user_id'    => [$recordUserId],
                            'sign_times' => [$recordDetail['sign_nubmer']],
                        ];
                        // dd($batchSignData);
                        if (app($this->attendanceRecordsRepository)->recordIsExists($where)) {
                            //    判断签到时间取最早签退时间取最迟，这一块代码其实是要放在封装$recordDetail数据前。因为还会影响到延伸的地址经纬度平台等的更新
                            $attend_exist_recode = app($this->attendanceRecordsRepository)->getOneAttendRecord($where);
                            // 此处需要处理三种特殊情况(签到签退不分开)
                            // 1.外部与内部数据都只有签到情况下，需要将晚的数据变为签退
                            // 2.外部只有签到，内部有签到签退数据，需要判断外部的签到时间更新为内部的签到还是签退

                            if(!empty(strtotime($attend_exist_recode->sign_in_time)) && empty(strtotime($attend_exist_recode->sign_out_time)) && !empty($recordDetail['sign_in_time']) && empty($recordDetail['sign_out_time'])){
                                if(strtotime($attend_exist_recode->sign_in_time) < strtotime($recordDetail['sign_in_time'])){
                                    $recordDetail['sign_out_time'] = $recordDetail['sign_in_time'];
                                    $recordDetail['sign_in_time'] = '';
                                    // $tempData = [
                                    //     'sign_date' => $recordDate,
                                    //     'sign_times' => $recordDetail['sign_nubmer'],
                                    //     'platform' => $recordDetail['platform'],
                                    //     'sign_out_time' => $recordDetail['sign_in_time'],
                                    // ];
                                    // app($this->attendanceService)->signOut($tempData,['user_id' => $recordUserId]);
                                    // unset($recordDetail);
                                }
                            }
                            if(!empty(strtotime($attend_exist_recode->sign_in_time)) && !empty(strtotime($attend_exist_recode->sign_out_time)) && !empty($recordDetail['sign_in_time']) && empty($recordDetail['sign_out_time'])){
                                if(strtotime($attend_exist_recode->sign_out_time) < strtotime($recordDetail['sign_in_time'])){
                                    $recordDetail['sign_out_time'] = $recordDetail['sign_in_time'];
                                    $recordDetail['sign_in_time'] = '';
                                }
                            }


                            // 只要判断大小即可(签到签退分开)(不需要了 batch已经支持)
                            // if(isset($recordDetail['sign_in_time']) && strtotime($recordDetail['sign_in_time']) > strtotime($attend_exist_recode->sign_in_time)){
                            //     //    插入的签到时间大于本来的时间,还需要判断时间是否是当天的（正常情况下不需要考虑测试可能会有这样的问题，下同。不作处理）$recordDetail['sign_in_time']已经做了时间戳小于0的筛选
                            //     $recordDetail['sign_in_time'] = $attend_exist_recode->sign_in_time;
                            // }
                            // if(isset($recordDetail['sign_out_time']) && strtotime($recordDetail['sign_out_time']) < strtotime($attend_exist_recode->sign_out_time)){
                            //     //    插入的签退时间小于本来的时间
                            //     $recordDetail['sign_out_time'] = $attend_exist_recode->sign_out_time;
                            // }
                            // dd($recordDetail);
                        }
                        unset($recordDetail['source']);
                    }
                    
                }
            }
        }
        unset($recordList);
        unset($recordDetails);
        unset($recordDetail);
        return $batchSignData;
    }

}
