<?php

namespace App\EofficeApp\System\ExternalDatabase\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Schema;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
/**
 * 外部数据库Service类:提供外部数据库相关服务
 *
 * @author qishaobo
 *
 * @since  2016-08-25 创建
 */
class ExternalDatabaseService extends BaseService
{
    /**
     * 外部数据库资源
     * @var object
     */
    private $externalDatabaseRepository;

    public function __construct(
    ) {
        $this->externalDatabaseRepository = 'App\EofficeApp\System\ExternalDatabase\Repositories\ExternalDatabaseRepository';
        $this->voucherIntergrationBaseInfoRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationBaseInfoRepository';
    }

    /**
     * 新建外部数据库
     *
     * @param  array $data 新建数据
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function createExternalDatabase($data)
    {
        if ($externalDatabaseObj = app($this->externalDatabaseRepository)->insertData($data)) {
            return $externalDatabaseObj->getKey();
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除外部数据库
     *
     * @param  int|string $databaseId 外部数据库id,多个用逗号隔开
     *
     * @return array 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function deleteExternalDatabase($databaseId)
    {

        $databaseIds = array_filter(explode(',', $databaseId));

        if (empty($databaseIds)) {
            return false;
        }
        $check = app($this->voucherIntergrationBaseInfoRepository)->getInfoByDatabaseConfig($databaseIds);
        if (!empty($check)) {
            return ['code' => ['database_use_by_u8', 'system']];
        }
        $where = [
            'database_id' => [$databaseIds, 'in'],
        ];

        return app($this->externalDatabaseRepository)->deleteByWhere($where);
    }

    /**
     * 编辑外部数据库
     *
     * @param  int $databaseId 外部数据库id
     * @param  array $data 编辑数据
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since 2016-08-25
     */
    public function updateExternalDatabase($databaseId, $data)
    {
        $where = [
            'database_id' => [$databaseId],
        ];
        if (app($this->externalDatabaseRepository)->updateData($data, $where)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 查询外部数据库详情
     *
     * @param  int $databaseId 外部数据库id
     *
     * @return array 外部数据库详情
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabase($databaseId)
    {
        if ($externalDatabaseObj = app($this->externalDatabaseRepository)->getDetail($databaseId)) {
            return $externalDatabaseObj->toArray();
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 查询外部数据库列表
     * 20201224-改为只返回database_id,name，不再返回配置详情，控制安全性
     *
     * @param  array $param 查询条件
     *
     * @return array 外部数据库列表
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabases($param)
    {
        $param = $this->parseParams($param);
        $param['fields'] = ['database_id','name'];
        return $this->response(app($this->externalDatabaseRepository), 'getExternalDatabasesTotal', 'getExternalDatabases', $param);
    }

    /**
     * 测试外部数据库
     *
     * @param  array $param 查询条件
     *
     * @return array 测试结果
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function testExternalDatabases($param)
    {
        $outsend = false;
        if (isset($param['select_database_id'])) {
            $outsend = true;
            if (!isset($param['select_database_id']) || empty($param['select_database_id'])) {
                return ['code' => ['0x015021', 'system']];
            }
            //获取外部数据库配置
            $databaseInfo = $this->getExternalDatabase($param['select_database_id']);

            if (empty($databaseInfo['driver'])) {
                return ['code' => ['0x015007', 'system']];
            }

            $method = $databaseInfo['driver'] . 'Test';

            if (!method_exists($this, $method)) {
                return ['code' => ['0x015008', 'system']];
            }
            if (!isset($databaseInfo['host']) || $databaseInfo['host'] == "") {
                return ['code' => ['0x015015', 'system']];
            }
            if (!isset($databaseInfo['port']) || $databaseInfo['port'] == "") {
                return ['code' => ['0x015016', 'system']];
            }
            if (!isset($databaseInfo['username']) || $databaseInfo['username'] == "") {
                return ['code' => ['0x015017', 'system']];
            }
            // u8集成数据库名不确定 手动传入
            if (isset($param['database'])) {
                $databaseInfo['database'] = $param['database'];
            }
            if (!isset($databaseInfo['database']) || $databaseInfo['database'] == "") {
                return ['code' => ['0x015018', 'system']];
            }
            $param = $databaseInfo;
        } else {
            if (empty($param['driver'])) {
                return ['code' => ['0x015007', 'system']];
            }

            $method = $param['driver'] . 'Test';

            if (!method_exists($this, $method)) {
                return ['code' => ['0x015008', 'system']];
            }
            if (!isset($param['host']) || $param['host'] == "") {
                return ['code' => ['0x015015', 'system']];
            }
            if (!isset($param['port']) || $param['port'] == "") {
                return ['code' => ['0x015016', 'system']];
            }
            if (!isset($param['username']) || $param['username'] == "") {
                return ['code' => ['0x015017', 'system']];
            }

            if (!isset($param['database']) || $param['database'] == "") {
                return ['code' => ['0x015018', 'system']];
            }
        }

        $externalDatabaseInfo = $this->$method($param);
        config(['database.connections.external_database' => $externalDatabaseInfo]);

        try {
            $r = DB::connection('external_database')->getPdo();
            return 1;
        } catch (\Exception $e) {
            if (json_encode($e->getMessage()) === false) {
                $error = iconv('gbk', 'utf-8', $e->getMessage());
            } else {
                $error = $e->getMessage();
            }
            $this->addExternalDatabaseLog('test', $param['host'], $error);
            return trans('system.connect_error') . $error;
            //$this->addExternalDatabaseLog('test',$param['host'],$e->getMessage());
            //return ['code' => ['0x015019', 'system']];
        };
    }
    /**
     * 获取外部数据库表
     *
     * @param  array $param 查询条件
     *
     * @return array 测试结果
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesTables($param)
    {
        if (!isset($param['database_id']) || empty($param['database_id'])) {
            return [];
        } else if ($param['database_id'] == 0) {
            return [];
        }
        //获取外部数据库配置
        $databaseInfo = $this->getExternalDatabase($param['database_id']);

        if (empty($databaseInfo['driver'])) {
            return ['code' => ['0x015007', 'system']];
        }

        $method = $databaseInfo['driver'] . 'Test';

        if (!method_exists($this, $method)) {
            return ['code' => ['0x015008', 'system']];
        }
        if (!isset($databaseInfo['host']) || $databaseInfo['host'] == "") {
            return ['code' => ['0x015015', 'system']];
        }
        if (!isset($databaseInfo['port']) || $databaseInfo['port'] == "") {
            return ['code' => ['0x015016', 'system']];
        }
        if (!isset($databaseInfo['username']) || $databaseInfo['username'] == "") {
            return ['code' => ['0x015017', 'system']];
        }
        // 20190930-dingpeng修改-可以从外部的param传递[库]参数database
        if (isset($param['database']) && !empty($param['database'])) {
            $databaseInfo['database'] = $param['database'];
        }
        if (!isset($databaseInfo['database']) || empty($databaseInfo['database'])) {
            return ['code' => ['0x015018', 'system']];
        }

        // $config = new \Doctrine\DBAL\Configuration();
        // $connectionParams = $this->$method($databaseInfo);

        // $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        // //vendor\doctrine\dbal\lib\Doctrine\DBAL\Connection.php

        $externalDatabaseInfo = $this->$method($databaseInfo);

        config(['database.connections.external_database_'.$param['database_id'] => $externalDatabaseInfo]);

        try {
            $result = [];
            $query  = DB::connection('external_database_'.$param['database_id']);
            if ($databaseInfo['driver'] == 'mysql') {
                $rows = $query->select("select table_name from information_schema.tables where table_schema='" . $databaseInfo['database'] . "' and table_type='base table'");
                $i    = 0;
                foreach ($rows as $key => $value) {
                    $result[$i]['tablename'] = $value->table_name;
                    $i++;
                }
            } else if ($databaseInfo['driver'] == 'sqlsrv') {
                $result = [];
                $query  = DB::connection('external_database_'.$param['database_id']);
                $rows   = $query->select('select name from sys.tables go order by name asc');
                $i      = 0;

                foreach ($rows as $key => $value) {
                    $result[$i]['tablename'] = $value->name;
                    $i++;
                }
            }else if($databaseInfo['driver'] == 'oracle'){
                $result = [];
                $query = DB::connection('external_database_'.$param['database_id']);
                $rows = $query->select('select * from USER_TABLES');
                $i = 0;
                foreach ($rows as $key => $value) {
                    $result[$i]['tablename'] = $value->table_name;
                    $i++;
                }
            }
            return $result;
        } catch (\Exception $e) {
            $this->addExternalDatabaseLog('getTable', $databaseInfo['host'], $e->getMessage());
            return ['code' => ['0x015019', 'system']];
        };
    }
    /**
     * 通过sql语句获取数据
     *
     * @param  array $param 查询条件
     *
     * @return array 测试结果
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesDataBySql($param)
    {
        $system = false;
        if (!isset($param['database_id']) || empty($param['database_id'])) {
            $system = true;
        } else if ($param['database_id'] == 0) {
            $system = true;
        }
        if (!isset($param['sql']) || empty($param['sql'])) {
            return [];
        }
        if(!$system) {
            //获取外部数据库配置
            $databaseInfo = $this->getExternalDatabase($param['database_id']);

            if (empty($databaseInfo['driver'])) {
                return false;
            }

            $method = $databaseInfo['driver'] . 'Test';

            if (!method_exists($this, $method)) {
                return false;
            }
            if (!isset($databaseInfo['host']) || $databaseInfo['host'] == "") {
                return false;
            }
            if (!isset($databaseInfo['port']) || $databaseInfo['port'] == "") {
                return false;
            }
            if (!isset($databaseInfo['username']) || $databaseInfo['username'] == "") {
                return false;
            }
            // U8集成数据库名手动传入
            if (isset($param['database'])) {
                $databaseInfo['database'] = $param['database'];
            }
            if (!isset($databaseInfo['database']) || $databaseInfo['database'] == "") {
                return false;
            }

            $externalDatabaseInfo = $this->$method($databaseInfo);

            config(['database.connections.external_database' => $externalDatabaseInfo]);
        }


        try {
            if($system) {
                $query = DB::connection('mysql');
            }else{
                $query = DB::connection('external_database');
            }
            $default = [
                'page'  => 0,
                'limit' => 10,
            ];
	        ini_set('memory_limit', '2024M');
            if(isset($param['search']) && !empty($param['search'])) {
                //解析条件，拼接到sql语句中
                $param['sql'] = $this->analysisSql($param['sql'],$param['search']);
            }
            $param = array_merge($default, $param);
            $start = $param['page'] * $param['limit'] - $param['limit'];
            $query = $query->select($param['sql']);
	    if ($param['page'] > 0) {
            $total = count($query);
            return ['list' => array_slice($query, $start, $param['limit']), 'total' => $total];
	    }
	    return ['list' => $query];
        } catch (\Exception $e) {
            $this->addExternalDatabaseLog('getDataBySql',$databaseInfo['host'],$e->getMessage());
            return false;
        } catch (\Error $error) {
            return ['code' => ['0x015019', 'system']];
        };
    }
    /**
     * 解析条件，拼接到sql语句中
     */
    public function analysisSql($sql,$search){
        $analysis = '';
        foreach ($search as $key => $value) {
            if(is_array($value)) {
                if(count($value) == 1) {
                    $analysis .= ($analysis?' and ':''). ' '. $key .' = '.$value[0].' ';
                }elseif($value[1]=='in' || $value[1]=='not_in'){
                    if(is_array($value[0])) {
                        $in = '';
                        foreach ($value[0] as  $v) {
                            $in .= ($in?',':'') . $v;
                        }
                        $analysis .=($analysis?' and ':'').' '. $key .' '.$value[1].' ('.$in.')';
                    }
                }elseif($value[1]=='like'){
                    $analysis .= ($analysis?' and ':'').' '. $key .' like "%'.$value[0].'%" ';
                }elseif(($value[1]=='between' || $value[1]=='not_between') && is_array($value[0]) && count($value[0]) == 2){
                    $analysis .=($analysis?' and ':'').' '. $key .' '.$value[1].' "'.$value[0][0].'" and "'.$value[0][1].'"';
                }else{
                    $analysis .= ($analysis?' and ':'').' '. $key .$value[1].$value[0].' ';
                }
            }
        }
        // 20200218-丁鹏-转成小写导致查询的字段里面的DATA_1变成data_1导致错误，去掉此转换，然后下面的strrpos改成strripos
        // $sql = strtolower($sql);
        if($analysis) {
            if(strripos($sql,'where') !== false) {
                $start = strripos($sql,'where');
                $sqlone = substr($sql,0,$start+5);
                $sqltwo = substr($sql,$start+5);
                $sql = $sqlone.$analysis.' and '.$sqltwo;
            }
        }
        return $sql;
    }
    /**
     * 获取外部数据库表字段
     *
     * @param  array $param 查询条件
     *
     * @return array 测试结果
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesTableFieldList($param)
    {
        if (!isset($param['database_id']) || !isset($param['table_name']) || empty($param['database_id']) || empty($param['table_name'])) {
            return [];
        }

        //获取外部数据库配置
        $databaseInfo = $this->getExternalDatabase($param['database_id']);

        if (empty($databaseInfo['driver'])) {
            return ['code' => ['0x015007', 'system']];
        }

        $method = $databaseInfo['driver'] . 'Test';

        if (!method_exists($this, $method)) {
            return ['code' => ['0x015008', 'system']];
        }
        if (!isset($databaseInfo['host']) || $databaseInfo['host'] == "") {
            return ['code' => ['0x015015', 'system']];
        }
        if (!isset($databaseInfo['port']) || $databaseInfo['port'] == "") {
            return ['code' => ['0x015016', 'system']];
        }
        if (!isset($databaseInfo['username']) || $databaseInfo['username'] == "") {
            return ['code' => ['0x015017', 'system']];
        }

        // 20190930-dingpeng修改-可以从外部的param传递[库]参数database
        if (isset($param['database']) && !empty($param['database'])) {
            $databaseInfo['database'] = $param['database'];
        }
        if (!isset($databaseInfo['database']) || empty($databaseInfo['database'])) {
            return ['code' => ['0x015018', 'system']];
        }

        $externalDatabaseInfo = $this->$method($databaseInfo);

        config(['database.connections.external_database_'.$param['database_id'] => $externalDatabaseInfo]);
        try {
            $result  = [];
            $default = [
                'page'  => 0,
                'limit' => 10,
                'search' => []
            ];
            $param = array_merge($default, $param);
            $columns = Schema::connection('external_database_'.$param['database_id'])->getColumnListing($param['table_name']);
            foreach ($columns as $key => $value) {
                // oracle不处理成小写，正常返回前端处理
                // if ($databaseInfo['driver'] == 'oracle') {
                //     $value = strtolower($value);
                // }
                if(!empty($param['search'])) {
                    if(isset($param['search']['field_like']) && is_array($param['search']['field_like']) && isset($param['search']['field_like'][0])) {
                        if(strpos(strtolower($value),strtolower($param['search']['field_like'][0])) !== false) {
                            $result[$key]['COLUMN_NAME'] = $value;
                        }
                    }
                }else {
                    $result[$key]['COLUMN_NAME'] = $value;
                }
            }

            $start = $param['page'] * $param['limit'] - $param['limit'];
            if ($param['page'] > 0) {
                $total = count($result);
                return ['list' => array_slice($result, $start, $param['limit']), 'total' => $total];
            }
            return $result;
        } catch (\Exception $e) {
            $this->addExternalDatabaseLog('getField', $databaseInfo['host'], $e->getMessage());
            return ['code' => ['0x015019', 'system']];
        };
    }
    /**
     * 获取外部系统表数据
     *
     * @param  array $param 查询条件
     *
     * @return array 查询条件
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesTableData($param)
    {
        // $param = [
        //     'database_id'=>9,//外部数据库id 必填
        //     'table_name'=>'activity',//表名 必填
        //     "fields"=>"ACTIVITY_ID,xxxxx",//查询字段 选填
        //     'search'=>[
        //          "ACTIVITY_CONTENT"=>["1","like"],
        //          "ACTIVITY_TYPE" => [["1"], 'not_in']],//普通条件 选填
        //          'multiSearch'=>[//多级条件 选填
        //              "ACTIVITY_CONTENT" => [["1"], 'in'],
        //              "ACTIVITY_TYPE" => [["1"], 'not_in'],
        //              "multiSearch" => [
        //                  "ACTIVITY_TYPE" => [["1"], 'not_in'],
        //                  "ACTIVITY_CONTENT" => [["1"], 'in'],
        //              ],
        //              "ACTIVITY_ADDRESS" => [["1","5"], 'not_between'],
        //              "ACTIVITY_BEGINTIME" => [["1","5"], 'between'],
        //              '__relation__' => 'or'
        //          ],
        //     "order_by"=>["ACTIVITY_ID"=>"asc"],//排序 选填
        //     'page'      => 0,//0 不分页
        //     'limit'     => 10,// 默认10
        //     'returntype' => 'data'//array count object
        // ];

        $param = $this->parseParams($param);
        if (!isset($param['database_id']) || empty($param['database_id'])) {
            return '';
            // return ['code' => ['0x015021', 'system']];
        }
        $type = isset($param['type']) && $param['type'] = 'sql' ? 'sql' : 'json';

        //获取外部数据库配置
        $databaseInfo = $this->getExternalDatabase($param['database_id']);

        $config = new \Doctrine\DBAL\Configuration();
        if (empty($databaseInfo['driver'])) {
            return '';
            // return ['code' => ['0x015007', 'system']];
        }

        $method = $databaseInfo['driver'] . 'Test';

        if (!method_exists($this, $method)) {
            return '';
            // return ['code' => ['0x015008', 'system']];
        }

        if (isset($databaseInfo['host']) && $databaseInfo['host'] != "") {
            $host = $databaseInfo['host'];
        } else {
            return '';
            // return ['code' => ['0x015015', 'system']];
        }
        if (isset($databaseInfo['port']) && $databaseInfo['port'] != "") {
            $port = $databaseInfo['port'];
        } else {
            return '';
            // return ['code' => ['0x015016', 'system']];
        }
        if (isset($databaseInfo['username']) && $databaseInfo['username'] != "") {
            $username = $databaseInfo['username'];
        } else {
            return '';
            // return ['code' => ['0x015017', 'system']];
        }
        if (isset($databaseInfo['password']) && $databaseInfo['password'] != "") {
            $password = $databaseInfo['password'];
        } else {
            $password = "";
        }
        // U8集成 传入
        if (isset($param['database']) && !empty($param['database'])) {
            $databaseInfo['database'] = $param['database'];
            $database_name = $databaseInfo['database'];
        }
        if (isset($databaseInfo['database']) && $databaseInfo['database'] != "") {
            $database_name = $databaseInfo['database'];
        } else {
            return '';
            // return ['code' => ['0x015018', 'system']];
        }

        $externalDatabaseInfo = $this->$method($databaseInfo);
        config(['database.connections.external_database_'.$param['database_id'] => $externalDatabaseInfo]);

        try {
            header('Content-Type:text/html; charset=utf-8');
            $query  = DB::connection('external_database_'.$param['database_id']);
            $table = '';
            $sql = '';
            if($type == 'sql' ){
                $param['returntype'] = $param['returntype'] ?? 'data';
                if(!isset($param['sql']) || empty($param['sql'])){
                    return '';
                }
                $sql = $param['sql'];
                $query = $query->select($sql);
                $total = count($query);
                $list = json_decode(json_encode($query), true) ?? [];
                // 返回值类型判断
                if ($param["returntype"] == "array") {
                    return $list;
                } else if ($param["returntype"] == "count") {
                    return $total;
                } else if ($param["returntype"] == "object") {
                    return $query;
                } else if ($param["returntype"] == "data") {
                    return ['total' => $total, 'list' => $list];
                }
            }else{
                if ( !isset($param['table_name']) || empty($param['table_name'])) {
                    return '';
                }
                $table = $param['table_name'];
                $default = [
                    'fields'      => ['*'],
                    'page'        => 0,
                    'limit'       => 10,
                    'search'      => [],
                    'multiSearch' => [],
                    'order_by'    => [],
                    'returntype'  => 'data',
                ];
                $param = array_merge($default, $param);

                $query = $query->table($table)->select($param['fields']);
                if (!empty($param['search'])) {
                    //多条件筛选
                    if (isset($param['search']['multiSearch']) && !empty($param['search']['multiSearch'])) {
                        $query = $this->scopeMultiWheres($query, $param['search']['multiSearch']);
                        unset($param['search']['multiSearch']);
                    }
                    //正常条件
                    $query = $this->scopeWheres($query, $param['search']);
                }
                //应刘老师要求$param['multiSearch'] 整体移至$param['search']下
                // if(!empty($param['multiSearch'])) {
                //     $query = $this->scopeMultiWheres($query,$param['multiSearch']);
                // }

                if (!empty(array_filter($param['order_by']))) {
                    $query = $this->scopeOrders($query, array_filter($param['order_by']));
                }
                $total = count($query->get()->toArray());
                if ($param['page'] > 0) {
                    $query = $query->forPage($param['page'], $param['limit']);
                }
                // 返回值类型判断
                if ($param["returntype"] == "array") {
                    return $query->get()->toArray();
                } else if ($param["returntype"] == "count") {
                    return $total;
                } else if ($param["returntype"] == "object") {
                    return $query->get();
                } else if ($param["returntype"] == "data") {
                    $list = $query->get()->toArray();
                    return ['total' => $total, 'list' => $list];
                }
            }
         } catch (\Exception $e) {
             $this->addExternalDatabaseLog('getData',$databaseInfo['host'],$e->getMessage());
             return [];
             //return ['code' => ['0x015019','system']];
         } catch (\Error $error) {
            return [];
        };

     }
     /**
      * 编码转换
      * @param  [type] &$input [description]
      * @return [type]         [description]
      */
     function utf8_encode_deep(&$input) {
         if (is_string($input)) {
             $code = mb_detect_encoding($input, ['ASCII','GB2312','GBK','UTF-8']);

             if($code != 'UTF-8') {
                 $input = mb_convert_encoding($input, "UTF-8",$code);
             }
         } else if (is_array($input)) {
             foreach ($input as &$value) {
                 $this->utf8_encode_deep($value);
             }
             unset($value);
         } else if (is_object($input)) {
             $vars = array_keys(get_object_vars($input));
             foreach ($vars as $var) {
                 $this->utf8_encode_deep($input->$var);
             }
         }
         return $input;
     }
    /**
     * 通过外部系统id 获取外部系统链接
     */
    public function getExternalDatabasesConnect($databaseId)
    {
        if (!$databaseId) {
            return false;
        }
        //获取外部数据库配置
        $databaseInfo = $this->getExternalDatabase($databaseId);

        try {
            if (empty($databaseInfo['driver'])) {
                return false;
            }
            $method = $databaseInfo['driver'] . 'Test';

            $externalDatabaseInfo = $this->$method($databaseInfo);
            config(['database.connections.external_database_'.$databaseId => $externalDatabaseInfo]);

            return DB::connection('external_database_'.$databaseId);
        } catch (\Exception $e) {
            return false;
        } catch (\Error $error) {
            return false;
        };

    }
    public function sendDataToExternalDatabase($param, $data)
    {
        if (!$param['database_id'] || !$param['table_name']) {
            return ['code' => ['0x000010', 'outsend']];
        }
        //获取外部数据库配置
        $databaseInfo = $this->getExternalDatabase($param['database_id']);
        if (!$databaseInfo) {
            return ['code' => ['0x000011', 'outsend']];
        }
        $databaseInfo['table_name'] = $param['table_name'];
        // u8集成手动传入database
        if (isset($param['database']) && !empty($param['database'])) {
            $databaseInfo['database'] = $param['database'];
        }
        if (empty($databaseInfo['host']) || empty($databaseInfo['driver']) || empty($databaseInfo['port']) || empty($databaseInfo['username']) || empty($databaseInfo['database'])) {
            return ['code' => ['0x000006', 'outsend']];
        }
        $method = $databaseInfo['driver'] . 'Test';

        $externalDatabaseInfo = $this->$method($databaseInfo);
        config(['database.connections.external_database_'.$param['database_id'] => $externalDatabaseInfo]);
        // 遇到问题---sqlserver编码为gbk时转码插入数据库失败，数据未转码插入成功 --- 20200103
        // 增加字段是否转码，没有此字段时使用之前的逻辑
        if (isset($databaseInfo['is_trans'])) {
            if ($databaseInfo['is_trans'] == 1) {
                $charSet = $databaseInfo['charset'] ?? ''; // 字符编码
                if (!empty($charSet) && $charSet != 'utf8') {
                    // 转码
                    foreach ($data as $key => $value) {
                        $data[$key] = transEncoding($value, $charSet);
                    }
                }
            }
        } else {
            $transEncodeClass = ucfirst($databaseInfo['driver']). 'TransEncode';
            $transEncodeFile = base_path().DS.'ext'.DS.'external_database_trans'.DS.$transEncodeClass.'.php';
            require_once $transEncodeFile;
            $trans = new $transEncodeClass($databaseInfo);
            $data = $trans->transEncoding($data);
            // if($externalDatabaseInfo['charset'] != 'utf8') {
            //     foreach ($data as $key => $value) {
            //         $data[$key] = transEncoding($value, $externalDatabaseInfo['charset']);
            //     }
            // }
        }
        try {
            DB::purge('external_database_'.$param['database_id']);
            $query = DB::reconnect('external_database_'.$param['database_id'])->table($databaseInfo['table_name'])->insertGetId($data);
            return $query;
        } catch (\Exception $e) {
            $errorInfo = $this->transErrorInfoForShow(transEncoding($e->getMessage(), 'UTF-8'));
            $this->addExternalDatabaseLog('sendData', $databaseInfo['host'], transEncoding($e->getMessage(), 'UTF-8'));
            return ['code' => $errorInfo];
        };

    }
    /**
     * 验证sql语句
     *
     */
    public function externalDatabaseTestSql($param)
    {
        if (!isset($param['database_id']) || empty($param['database_id'])) {
            return [];
        } else if ($param['database_id'] == 0) {
            return [];
        }
        if (!isset($param['sql']) || empty($param['sql'])) {
            return [];
        }
        static $baseInfo       = [];
        static $exDatabaseInfo = [];
        if (!isset($baseInfo[$param['database_id']])) {
            $baseInfo[$param['database_id']] = $this->getExternalDatabase($param['database_id']);
        }
        //获取外部数据库配置
        $databaseInfo = $baseInfo[$param['database_id']];
        if (empty($databaseInfo['driver'])) {
            return false;
        }
        $method = $databaseInfo['driver'] . 'Test';

        if (!method_exists($this, $method)) {
            return false;
        }
        if (!isset($databaseInfo['host']) || $databaseInfo['host'] == "") {
            return false;
        }
        if (!isset($databaseInfo['port']) || $databaseInfo['port'] == "") {
            return false;
        }
        if (!isset($databaseInfo['username']) || $databaseInfo['username'] == "") {
            return false;
        }

        if (!isset($databaseInfo['database']) || $databaseInfo['database'] == "") {
            return false;
        }
        if (!isset($exDatabaseInfo[$param['database_id']])) {
            $exDatabaseInfo[$param['database_id']] = $this->$method($databaseInfo);
        }
        $externalDatabaseInfo = $exDatabaseInfo[$param['database_id']];

        config(['database.connections.external_database_'.$param['database_id'] => $externalDatabaseInfo]);

        try {
            DB::connection('external_database_'.$param['database_id'])->disableQueryLog();
            $db = DB::connection('external_database_'.$param['database_id']);
            ini_set('memory_limit', '2024M');
            $db->select($param['sql']);
            return "success";
        } catch (\Exception $e) {
            $this->addExternalDatabaseLog('testSql', $databaseInfo['host'], $e->getMessage());
            return "error";
        };
    }
    /**
     * 测试msyql数据库
     *
     * @param  array $param 查询条件
     *
     * @return array 查询条件
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function mysqlTest($param)
    {
        return [
            'driver'   => 'mysql',
            'host'     => $param['host'],
            'port'     => $param['port'],
            'username' => $param['username'],
            'password' => isset($param['password']) ? $param['password'] : '',
            'database' => $param['database'],
            'charset'  => $param['charset'],
            'options' => [
                // mysql连接3s超时设置
                \PDO::ATTR_TIMEOUT => envOverload('TIMEOUT', 5)
            ]
        ];
    }

    /**
     * 测试sqlserver数据库
     *
     * @param  array $param 查询条件
     *
     * @return array 查询条件
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function sqlsrvTest($param)
    {
        return [
            'driver'   => 'sqlsrv',
            'host'     => $param['host'],
            'port'     => $param['port'],
            'username' => $param['username'],
            'password' => isset($param['password']) ? $param['password'] : '',
            'database' => $param['database'] . ';LoginTimeout='. envOverload('TIMEOUT', 5),
            'charset'  => $param['charset'],
        ];
    }

    /**
     * 测试oracle数据库
     *
     * @param  array $param 查询条件
     *
     * @return array 查询条件
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function oracleTest($param)
    {
        return [
            'driver'   => 'oracle',
            'host'     => $param['host'],
            'port'     => $param['port'],
            'username' => $param['username'],
            'password' => isset($param['password']) ? $param['password'] : '',
            'database' => $param['database'],
            'charset'  => $param['charset']
//             'tns'           => "(DESCRIPTION =
// (CONNECT_TIMEOUT=". envOverload('TIMEOUT', 5) .")(RETRY_COUNT=1)
// (ADDRESS = (PROTOCOL = TCP)(HOST = ". $param['host'] .")(PORT = ". $param['port'] ."))
// (CONNECT_DATA =
// (SERVER = DEDICATED)
// (SERVICE_NAME = '')
// )
// )",
        ];//(SERVICE_NAME = '')
    }
    /**
     * [scopeMultiWheres 多级查询条件传入测试方法]
     *
     * @method 朱从玺
     *
     * @param  [object]           $query  [builder对象]
     * @param  [array]            $wheres [查询条件]
     *
     * @return [object]                   [组装查询条件后的builder对象]
     */
    public function scopeMultiWheres($query, $wheres)
    {
        if (empty($wheres)) {
            return $query;
        }

        //初始属性,and关系
        $whereString = 'where';
        $whereHas    = 'whereHas';

        $operators = [
            'between'     => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in'          => 'whereIn',
            'not_in'      => 'whereNotIn',
        ];

        $orOperators = [
            'between'     => 'orWhereBetween',
            'not_between' => 'orWhereNotBetween',
            'in'          => 'orWhereIn',
            'not_in'      => 'orWhereNotIn',
        ];

        //or关系
        if (isset($wheres['__relation__']) && $wheres['__relation__'] == 'or') {
            $operators   = $orOperators;
            $whereString = 'orWhere';
            $whereHas    = 'orWhereHas';
        }

        //删除__relation__
        if (isset($wheres['__relation__'])) {
            unset($wheres['__relation__']);
        }

        //判断是不是整体都是关联查询
        $searchFields = array_keys($wheres);
        if (isset($this->allFields) && isset($this->allFields[$searchFields[0]])) {
            $firstRelation = $this->allFields[$searchFields[0]][0];
        } else {
            $firstRelation = '';
        }

        if ($firstRelation && empty(array_diff($searchFields, $this->relationFields[$firstRelation]))) {
            $relationStatus = true;
        } else {
            $relationStatus = false;
        }

        //整体关联查询,即这一层的所有查询都在同一个关联关系下
        if ($relationStatus) {
            $query = $query->$whereHas($firstRelation, function ($query) use ($wheres, $operators, $whereString) {
                foreach ($wheres as $field => $where) {
                    $field    = $this->allFields[$field][1];
                    $operator = isset($where[1]) ? $where[1] : '=';
                    $operator = strtolower($operator);

                    if (isset($operators[$operator])) {
                        $whereOp = $operators[$operator];
                        $query   = $query->$whereOp($field, $where[0]);
                    } else {
                        $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                        $query = $query->$whereString($field, $operator, $value);
                    }
                }
            });
            //不是整体关联查询,则这一层查询条件为并列关系,同一个关联关系下的参数也是
        } else {
            foreach ($wheres as $field => $where) {
                $operator = isset($where[1]) ? $where[1] : '=';
                $operator = strtolower($operator);

                if (isset($this->allFields) && isset($this->allFields[$field])) {
                    $query = $query->$whereHas($this->allFields[$field][0], function ($query) use ($where, $operators, $operator, $field, $whereString) {
                        $field = $this->allFields[$field][1];

                        if (isset($operators[$operator])) {
                            $whereOp = $operators[$operator];
                            $query   = $query->$whereOp($field, $where[0]);
                        } else {
                            $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                            $query = $query->$whereString($field, $operator, $value);
                        }
                    });
                    //键值包含multiSearch为下一层,递归调用本函数
                } elseif (strpos($field, 'multiSearch') !== false) {
                    $query = $query->$whereString(function ($query) use ($where) {
                        $this->scopeMultiWheres($query, $where);
                    });
                } else {
                    if (isset($operators[$operator])) {
                        $whereOp = $operators[$operator];
                        $query   = $query->$whereOp($field, $where[0]);
                    } else {
                        $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                        $query = $query->$whereString($field, $operator, $value);
                    }
                }
            }
        }

        return $query;
    }
    /**
     * 查询条件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $wheres 查询条件
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWheres($query, $wheres)
    {
        $operators = [
            'between'     => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in'          => 'whereIn',
            'not_in'      => 'whereNotIn',
        ];

        if (empty($wheres)) {
            return $query;
        }

        foreach ($wheres as $field => $where) {
            if(!empty($field)) {
                $operator = isset($where[1]) ? $where[1] : '=';
                $operator = strtolower($operator);
                if (isset($operators[$operator])) {
                    $whereOp = $operators[$operator]; //兼容PHP7写法
                    $query   = $query->$whereOp($field, $where[0]);
                } else {
                    $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                    $query = $query->where($field, $operator, $value);
                }
            }
        }

        return $query;
    }
    /**
     * 查询排序
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $orders 排序
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrders($query, $orders)
    {
        if (!empty($orders)) {
            foreach ($orders as $field => $order) {
                $query = $query->orderBy($field, $order);
            }
        }
        return $query;
    }

    public function savePortalConfig($data)
    {
        if (isset($data['database']['multi']) && !empty($data['database']['multi']) && isset($data['database']['multi']['type'])) {
            $multis = $data['database']['multi'];
            foreach ($multis as $key => $multi) {
                $insert              = $this->defaultConfigValue($multi);
                $insert['tabs_type'] = 1;
                if (isset($multi['id'])) {
                    DB::table('portal_external_databse')->where('id', $multi['id'])->update($insert);
                } else {
                    $id                                    = DB::table('portal_external_databse')->insertGetId($insert);
                    $data['database']['multi'][$key]['id'] = $id;
                }
            }
        } else if (isset($data['database']['single']) && !empty($data['database']['single']) && isset($data['database']['single']['type'])) {
            $single              = $data['database']['single'];
            $insert              = $this->defaultConfigValue($single);
            $insert['tabs_type'] = 0;
            if (isset($single['id'])) {
                DB::table('portal_external_databse')->where('id', $single['id'])->update($insert);
            } else {
                $id                               = DB::table('portal_external_databse')->insertGetId($insert);
                $data['database']['single']['id'] = $id;
            }
        }
        return $data;
    }
    public function defaultConfigValue($data)
    {
        $insert         = [];
        $insert['type'] = isset($data['type']) ? $data['type'] : '';
        if (isset($data['fields']) && !empty($data['fields'])) {
            foreach ($data['fields'] as $key => $value) {
                $field = [];
                if (!empty($value)) {
                    $field[] = [
                        'key'   => $value['COLUMN_NAME'],
                        'value' => isset($value['field_name']) ? $value['field_name'] : "",
                    ];
                }

            }
            $data['fields'] = json_encode($field);
        }
        $insert['database_id'] = isset($data['database_id']) ? $data['database_id'] : '';
        $insert['sql']         = isset($data['sql']) ? $data['sql'] : '';
        $insert['table']       = isset($data['table']) ? $data['table'] : '';
        $insert['fields']      = isset($data['fields']) ? $data['fields'] : '';
        $insert['tabs_title']  = isset($data['tabs_title']) ? $data['tabs_title'] : '';
        return $insert;
    }
    public function getPortalConfig($data)
    {
        $multi = DB::table('portal_external_databse')->where("tabs_type", 1)->get();
        foreach ($multi as $config) {
            $fields = $config->fields;
            if (!empty($fields)) {
                $fields   = json_decode($config->fields);
                $columns  = (object) [];
                $fieldArr = [];
                foreach ($fields as $key => $value) {
                    $fieldArr[]             = ['COLUMN_NAME' => $value->key, 'field_name' => $value->value];
                    $columns->{$value->key} = ['title' => $value->value];
                }
            }
            $config->fields  = $fieldArr;
            $config->columns = $columns;
        }
        $single = DB::table('portal_external_databse')->where("tabs_type", 0)->first();
        if ($single) {
            $fields = $single->fields;
            if (!empty($fields)) {
                $columns  = (object) [];
                $fieldArr = [];
                $fields   = json_decode($single->fields);
                foreach ($fields as $k => $field) {
                    $fieldArr[]             = ['COLUMN_NAME' => $field->key, 'field_name' => $field->value];
                    $columns->{$field->key} = ['title' => $field->value];

                }
            }
            $single->fields  = $fieldArr;
            $single->columns = $columns;
        }
        $result                       = [];
        $result['database']['single'] = $single;
        $result['database']['multi']  = $multi;
        return $result;
    }
    public function deletePortaltab($param)
    {
        if (isset($param['id'])) {
            return DB::table('portal_external_databse')->where("id", $param['id'])->delete();
        }
    }
    public function addExternalDatabaseLog($type = 'null', $host = 'null', $content = '')
    {
        $file = $this->getStorageDirPath() . 'externalDatabase.txt';
        file_put_contents($file, date('Y-m-d H:i:s') . ' -' . $type . '- ' . $host . '  ' . $content . "\r\n", FILE_APPEND | LOCK_EX);
    }
    // 返回storage目录路径
    public function getStorageDirPath()
    {
        $logDir = base_path('/storage/');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777);
        }
        $logDir .= 'logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777);
        }
        return $logDir;
    }
    /**
     * 执行sql
     *
     * @param [type] $param
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function externalDatabaseExcuteSql($param)
    {
        if (!isset($param['database_id']) || empty($param['database_id'])) {
            return [];
        } elseif ($param['database_id'] == 0) {
            return [];
        }
        if (!isset($param['sql']) || empty($param['sql'])) {
            return [];
        }
        $exceMethod = substr($param['sql'], 0, 6);
        if (!in_array(strtolower($exceMethod), ['select', 'update', 'delete'])) {
            return false;
        }
        static $baseInfo       = [];
        static $exDatabaseInfo = [];
        if (!isset($baseInfo[$param['database_id']])) {
            $baseInfo[$param['database_id']] = $this->getExternalDatabase($param['database_id']);
        }
        //获取外部数据库配置
        $databaseInfo = $baseInfo[$param['database_id']];
        if (empty($databaseInfo['driver'])) {
            return false;
        }
        $method = $databaseInfo['driver'] . 'Test';

        if (!method_exists($this, $method)) {
            return false;
        }
        if (!isset($databaseInfo['host']) || $databaseInfo['host'] == "") {
            return false;
        }
        if (!isset($databaseInfo['port']) || $databaseInfo['port'] == "") {
            return false;
        }
        if (!isset($databaseInfo['username']) || $databaseInfo['username'] == "") {
            return false;
        }
        if (isset($param['database']) && !empty($param['database'])) {
            $databaseInfo['database'] = $param['database'];
        }
        if (!isset($databaseInfo['database']) || $databaseInfo['database'] == "") {
            return false;
        }
        if (!isset($exDatabaseInfo[$param['database_id']])) {
            $exDatabaseInfo[$param['database_id']] = $this->$method($databaseInfo);
        }
        $externalDatabaseInfo = $exDatabaseInfo[$param['database_id']];

        config(['database.connections.external_database_'.$param['database_id'] => $externalDatabaseInfo]);

        try {
            DB::connection('external_database_'.$param['database_id'])->disableQueryLog();
            $db = DB::connection('external_database_'.$param['database_id']);
            ini_set('memory_limit', '2024M');
            $res = $db->$exceMethod($param['sql']);
            $needAll = $param['all'] ?? 0;
            if ($res && isset($res[0]) && !$needAll){
                return $res[0];
            }
            return $res;
        } catch (\Exception $e) {
            $this->addExternalDatabaseLog('exceSql', $databaseInfo['host'], $e->getMessage());
            return [];
        };
    }
    /**
     * 插入数据
     *
     * @param [type] $param
     * @param [type] $data
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function sendU8DataToExternalDatabase($param, $data)
    {
        if (!$param['database_id'] || !$param['table_name']) {
            return ['code' => ['0x000010', 'outsend']];
        }
        //获取外部数据库配置
        $databaseInfo = $this->getExternalDatabase($param['database_id']);
        if (!$databaseInfo) {
            return ['code' => ['0x000011', 'outsend']];
        }
        $databaseInfo['table_name'] = $param['table_name'];
        // u8集成手动传入database
        if (isset($param['database']) && !empty($param['database'])) {
            $databaseInfo['database'] = $param['database'];
        }
        if (empty($databaseInfo['host']) || empty($databaseInfo['driver']) || empty($databaseInfo['port']) || empty($databaseInfo['username']) || empty($databaseInfo['database'])) {
            return ['code' => ['0x000006', 'outsend']];
        }
        $method = $databaseInfo['driver'] . 'Test';

        $externalDatabaseInfo = $this->$method($databaseInfo);
        config(['database.connections.external_database_'.$param['database_id'] => $externalDatabaseInfo]);
        try {
            $res = DB::connection('external_database_'.$param['database_id'])->table($databaseInfo['table_name'])->insert($data);
            return $res;
        } catch (\Exception $e) {
            $this->addExternalDatabaseLog('sendData', $databaseInfo['host'],$e->getMessage());
            return ['code' => ['0x015019', 'system']];
        };

    }

    /**
     * 解析数据库连接返回值错误信息，简化展示
     *
     * @param [type] $error
     *
     * @author zyx
     *
     * @return void
     */
    public function transErrorInfoForShow($error) {
        // 列不存在
        if (strpos($error, 'Column not found: 1054')) {
            $start = strpos($error, 'Unknown column ') + 16;
            $end = strpos($error, "' in 'field list'");
            $errorPart = ' ' . trans('system.unknown_column');
            $errorRes = substr($error, $start, $end - $start) . $errorPart;
            return $errorRes;
        }
        return $error;
    }
}
