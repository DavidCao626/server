<?php

namespace App\EofficeApp\System\Log\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Log\Entities\LogEntity;
use Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
/**
 * 系统日志表资源库
 *
 * @author  齐少博
 *
 * @since  2016-07-01 创建
 */
class LogRepository extends BaseRepository
{
    public function __construct(LogEntity $entity)
    {
        parent::__construct($entity);
    }


    /**
     * 获取系统日志列表
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2016-07-01
     */
    public function getLogList($param)
    {
        $default = [
            'fields' => ['*'],
            'page' => 1,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['log_id' => 'desc', 'log_time' => 'desc'],
        ];
        $param = array_filter($param);
        $param = array_merge($default, $param);
//        $query = $this->entity->select($param['fields']);
//        $query = $this->parseGetLogWhere($query, $param['search']);
        if (isset($param['search']['log_relation_table'])) {
            $tableKey = $this->getLogTypeModule($param['search']['log_relation_table'][0]);
        } else {
            $tableKey = $this->getLogTypeModule(isset($param['search']['log_type'][0]) ? $param['search']['log_type'][0] : '');   //如果没有传参数，则为默认值：电脑端登录loginPC
            $param['search']['log_type'][0] = $this->getSearchType(isset($param['search']['log_type'][0]) ? $param['search']['log_type'][0] : 'loginPC');
        }

        if (!$tableKey) {
            return false;
        }
        // if ($tableKey) {
           // $this->createSubEntity($tableKey, 'System\Log');
        // } else {
        //     return false;
        // }
        $query = $this->SelectDbLogList($tableKey, $param);
        if ($query === false) {
            return false;
        }
//        $query = $query->with(['hasOneUser' => function ($query) {
//        $query->select(['user_id', 'user_name']);
//    }]);
        $orderparam = $this->getOrderParam($param['order_by']);
        // 由于导出不需要limit，当limit为*时，删除limit条件
        if (isset($param['limit']) && $param['limit'] === '*') {
            $data = $query->orderBy($orderparam['name'], $orderparam['orderby'])
            ->get()
            ->toArray();
        } else {
            $data = $query->orderBy($orderparam['name'], $orderparam['orderby'])
            ->forPage($param['page'], $param['limit'])
            ->get()
            ->toArray();
        }

        $data = json_decode(json_encode($data), true);
        // 判断是否搜索关联表，如果是，则传入用户名
        if (isset($param['search']['log_relation_table'])) {
            $data = $this->getUserName($data);
        }
        return $data;
    }

    /**
     *  获取关联表名和id，查询出用户名称
     * @param array 关联表和关联id
     * @return string 客户名
     */
    public function getUserName($data)
    {
        if (isset($data) && !empty($data)) {
            foreach ($data as $k => $v) {
                if (isset($v['log_creator'])){
                   $customerName = DB::table('user')->select('user_name')->where('user_id',$v['log_creator'])->first();
                   $data[$k]['log_creator_name'] = isset($customerName->user_name)?$customerName->user_name : '';
                   if (!$data[$k]['log_creator_name']) {
                        unset($data[$k]);
                   }
                }
            }
        }
        return  $data;
    }


    /**
     * 解析log_type分成模块和操作
     * @value string 搜索条件
     * @return 搜索条件的操作log_type
     */
    public function getSearchType($value)
    {
        $logTypes = config('eoffice.systemLogType');
        foreach ($logTypes as $k => $v) {
            if (is_array($v)) {
                if (strpos($value, $k) !== false) {
                    $value = substr($value, strlen($k));
                }
            }
        }
        return $value;
    }

    /**
     * 解析日志列表排序方式
     * @param  排序数组
     * @return array 排序数组的排序字段和排序方向
     */
    function getOrderParam($param)
    {
        foreach ($param as $k => $v) {
            $orderparam = [];
            $orderparam['name'] = $k;
            $orderparam['orderby'] = $v;
        }
        return $orderparam;
    }

    /**
     * 获取数据库中数据
     * @param $tableKey 表名
     * @param $param 搜索条件
     * @return 返回$query
     */
    function SelectDbLogList($tableKey, $param)
    {
        if (!Schema::hasTable($tableKey)) {
            return false;
        };
        $query = DB::table($tableKey)->select('*');
        //仅系统登录日志中的密码错误会出现用户名为空的情况，这句话严重影响查询速度
        if ($tableKey == 'system_login_log' && Arr::get($param, 'search.log_type.0') == 'loginpwderror') {
            $query->where('log_creator', '<>', '');
        }
        $query = $this->parsesQueryConditions($query, $param);
        return $query;
    }

    /**
     * [获取配置文件中的日志表名]
     * @param  $type 查询种类
     * @return string    表名
     * @return string     没有表名返回false
     */
    function getLogTypeModule($type)
    {
        if (empty($type)) {
            return false;
        }
        $logTypes = config('eoffice.systemLogType');
        foreach ($logTypes as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $r => $s) {
                    if ($type == $k . $s) {
                        $tablename = 'system_' . $k . '_log';
                        return $tablename;
                    }
                }
            } else {
                if ($type == $k) {
                    $tablename = 'system_log';
                    return $tablename;
                }
            }
        }
    }

    /**
     *解析where条件语句
     * @query $query  查询语句
     * @param  array $param
     * @return $query
     */
    function parsesQueryConditions($query, $param)
    {
        if (isset($param['search']['log_relation_table'])) {
            foreach ($param['search'] as $k => $v) {
                $finds[] = [$k, '=', $v[0]];
            }
            $where = 'where';
            return $query = $query->$where($finds);
        }

        $operators = [
            'between' => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in' => 'whereIn',
            'not_in' => 'whereNotIn'
        ];
        if (empty($param['search'])) {
            return $query;
        } else {
            $finds = [];
            foreach ($param['search'] as $field => $where) {
                $operator = isset($where[1]) ? $where[1] : '=';
                $operator = strtolower($operator);
                if (isset($operators[$operator])) {
                    $whereOp = $operators[$operator];
                    $query = $query->$whereOp($field, $where[0]);
                } else {
                    $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                    $value = $this->getSearchType($value);
//                    $value = $this->getLogConfigNum($value);
                    $where = 'where';
                    $query = $query->$where($field, $operator, $value);
                }
            }
            return $query;
        }
    }

    /**
     * 解析其他模块查询日志的条件
     *
     *
     */

    function parsesOtherConditions($query, $param)
    {
        if (empty($param['search'])) {
            return false;
        } else {
            $value = [];
            foreach ($param['search'] as $field => $where) {
                $value[] = ["$field", "=", "$where[0]"];
            }
            $where = 'where';
            $query = $query->$where($value);
        }
        return $query;
    }


    /**
     * 解析where条件语句中的type
     * @value string 配置文件中的日志种类名称
     * @return string 配置文件中日志种类对应的Key
     */

    public function getLogConfigNum($value)
    {
        $logTypes = config('eoffice.systemLogType');
        foreach ($logTypes as $k => $v) {
            if (is_array($v)) {
                if ($value == $v['type']) {
                    return $k;
                }
            } else {
                if ($value == $k) {
                    return $k;
                }
            }
        }
    }

    /**
     * 获取系统日志列表数量
     *
     * @param  array $param 查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-07-01
     */
    public function getLogTotal(array $param = [])
    {
        if (isset($param['search']['log_relation_table'][0])) {
            $tableName = $this->getLogTypeModule($param['search']['log_relation_table'][0]);
        } else {
            $tableName = $this->getLogTypeModule(isset($param['search']['log_type'][0]) ? $param['search']['log_type'][0] : '');
        }
//        $where = isset($param['search']) ? $param['search'] : [];
        if (!$tableName) {
            return false;
        }
//        $param['search']['log_type'][0] = $this->getSearchType(isset($param['search']['log_type'][0]) ? $param['search']['log_type'][0] : 'loginPC');
        $query = $this->SelectDbLogList($tableName, $param);
        if ($query === false) {
            return false;
        }
        $Total = $query->count();
        return $Total;
//        return $this->parseGetLogWhere($this->entity, $where)->count();
    }

    /**
     * 获取系统日志where条件解析
     *
     * @param  array $where 查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-07-01
     */
    public function parseGetLogWhere($query, array $where = [])
    {
        return $query->has('hasOneUser')->wheres($where);
    }

//    /**
//     * 获取系统日志统计
//     *
//     * @param  array $param 查询条件
//     *
//     * @return array
//     *
//     * @author qishaobo
//     *
//     * @since  2016-07-07
//     */
//    public function getLogStatistics($where)
//    {
//        if (!Schema::hasTable('system_login_log')){
//            return false;
//        };
//        return $this->entity->wheres($where)->count();
//        return $count;
//    }

//    /**
//     * 获取系统日志统计
//     *
//     * @param  array $param 查询条件
//     *
//     * @return array
//     *
//     * @author qishaobo
//     *
//     * @since  2016-07-08
//     */
//    public function getLogStatisticsWeek($where)
//    {
////       $query = $this->parseLogStatisticWhere($where)->get();
////       return $query;
//        $tableKey = 'system_login_log';
//        if (!Schema::hasTable($tableKey)){
//            return false;
//        }
//        $module   = 'System\Log';
//        if (!$this->createSubEntity($tableKey, $module)){
//            return false;
//        };
//        return $this->entity
//        ->selectRaw("LEFT(log_time, 10),COUNT(*) AS num")
//        ->wheres($where)
//        ->groupBy("LEFT(log_time, 10)")
//        ->get();
//    }

    /**
     * 解析系统统计的where条件
     * $param where条件
     * @return $query
     */
    public function parseLogStatisticWhere($param)
    {
        if (!Schema::hasTable('system_login_log')) {
            return false;
        };
        $query = DB::table('system_login_log')
            ->select('log_time')
            ->whereBetween('log_time', $param['log_time'][0]);
        return $query;
    }


//    /**
//     * 获取系统日志统计
//     *
//     * @param  array $param 查询条件
//     *
//     * @return array
//     *
//     * @author qishaobo
//     *
//     * @since  2016-07-08
//     */
//    public function getLogStatisticsYear($where)
//    {
//        return $this->entity
//        ->selectRaw("LEFT(log_time, 7),COUNT(*) AS num")
//        ->wheres($where)
//        ->groupBy("LEFT(log_time, 7)")
//        ->get();
//    }

//    /**
//     * 获取系统日志统计
//     *
//     * @param  array $param 查询条件
//     *
//     * @return array
//     *
//     * @author qishaobo
//     *
//     * @since  2016-07-08
//     */
//    public function getLogIpArea($where = [])
//    {
//        return $this->entity
//            ->selectRaw("ip_area,COUNT(*) AS num")
//            ->wheres($where)
//            ->groupBy("ip_area")
//            ->get();
//    }

    /**
     * 新建子表实体类（new）
     *
     * @param type $tableKey
     * @param type $module
     * @return boolean
     */
    public function createSubEntity($tableKey, $module)
    {
//        $moduleName = $this->getModuleFolderName($module);

        $entityPath = base_path('app/EofficeApp/System/Log/Entities/');

//        $entityName = $this->getEntityName($tableKey);
        $entityName = 'LogEntity';
//        $entityName = 'LogEntity';

        $fullEntityName = $entityPath . $entityName . '.php';
//        if (file_exists($fullEntityName)) {
//            return true;
//        }
        $handle = fopen($fullEntityName, 'w+');
        $a = fwrite($handle, "<?php
        namespace App\EofficeApp\System\Log\Entities;
        use App\EofficeApp\Base\BaseEntity;
        class " . $entityName . "  extends BaseEntity
        {
            public \$table 	    = '" . $tableKey . "';
            public \$primaryKey = 'log_id';
            public \$timestamps = false;
            public function hasOneUser()
    {
        return  \$this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'log_creator');
    }
        }");

        fclose($handle);

        return true;
    }

    /**
     * 获取驼峰模块名称
     * @return 驼峰模块名称
     */
    private function getModuleFolderName($module)
    {
        $moduleName = '';

        foreach (explode('_', $module) as $value) {
            $moduleName .= ucfirst($value);
        }

        return $moduleName;
    }

    /**
     * @获取实体名称
     * @return string 实体名称
     */
    private function getEntityName($tableKey)
    {
        $entityName = '';

        foreach (explode('_', $tableKey) as $value) {
            $entityName .= ucfirst($value);
        }
//        return $entityName . 'SubEntity';
        return $entityName;
    }

}