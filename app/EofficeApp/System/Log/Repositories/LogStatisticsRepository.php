<?php

namespace App\EofficeApp\System\Log\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Log\Entities\SystemLoginLogEntity;
use Schema;
use Illuminate\Support\Facades\DB;

Class LogStatisticsRepository extends BaseRepository
{
    public function __construct(SystemLoginLogEntity $entity)
    {
        parent::__construct($entity);
    }
    /**
     * 新建子表实体类（new）
     *
     * @param type $tableKey
     * @param type $module
     * @return boolean
     */

    public function createSubEntity($tableKey, $module, $param = '')
    {
//        $moduleName = $this->getModuleFolderName($module);
        if ($param == 'system_log') {
            $module[1] = isset($module[1]) ? $module[1] : "System/Log";
            $module[0] = isset($module[0]) ? $module[0] : "System\Log";
            $entityPath = base_path('app/EofficeApp/'.$module[1].'/Entities/');
            $module = $module[0];
        } else {
            $entityPath = base_path('app/EofficeApp/'.$module.'/Entities/');
        }
        $entityName = $this->getEntityName($tableKey);
        $fullEntityName = $entityPath . $entityName . '.php';
        if (file_exists($fullEntityName)) {
            return true;
        }

        $handle = fopen($fullEntityName, 'w+');
        $a = fwrite($handle, "<?php
        namespace App\EofficeApp\\".$module."\Entities;
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
        if ($a === false){
            return false;
        }
        fclose($handle);
        return true;
    }

    /**
     * 获取系统日志统计
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2016-07-07
     */
    public function getLogStatistics($where)
    {
        $tableKey = 'system_login_log';
        if (!Schema::hasTable($tableKey)){
            return false;
        }
        $module = [];
        $module[0]   = 'System\Log';
        $module[1]   = 'System/Log';
        if (!$this->createSubEntity($tableKey, $module, 'system_log')){
            return false;
        };
        return $this->entity->wheres($where)->count();
    }


    /**
     * 获取系统日志统计 周
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2016-07-08
     */
    public function getLogStatisticsWeek($where)
    {
//       $query = $this->parseLogStatisticWhere($where)->get();
//       return $query;
        $tableKey = 'system_login_log';
        if (!Schema::hasTable($tableKey)){
            return false;
        }
        $module = [];
        $module[0]   = 'System\Log';
        $module[1]   = 'System/Log';
        if (!$this->createSubEntity($tableKey, $module, 'system_log')){
            return false;
        };
        return $this->entity
            ->selectRaw("LEFT(log_time, 10),COUNT(*) AS num")
            ->wheres($where)
            ->groupBy("LEFT(log_time, 10)")
            ->get();
    }

    /**
     * 获取系统日志统计 年
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2016-07-08
     */
    public function getLogStatisticsYear($where)
    {
        $where = $this->addSearch($where);
        return $this->entity
            ->selectRaw("LEFT(log_time, 7),COUNT(*) AS num")
            ->wheres($where)
            ->groupBy("LEFT(log_time, 7)")
            ->get();
    }

    /**
     * 获取系统日志统计
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2016-07-08
     */
    public function getLogIpArea($where = [])
    {   
        return $this->entity
            ->selectRaw("ip_area,COUNT(*) AS num")
            ->wheres($where)
            ->groupBy("ip_area")
            ->get();
        
    }


    /**
     * @获取实体名称
     * @return string 实体名称
     */
    private function getEntityName($tableKey)
    {
        $entityName	= '';

        foreach (explode('_', $tableKey) as $value) {
            $entityName .= ucfirst($value);
        }
//        return $entityName . 'SubEntity';
        return $entityName.'Entity';
    }

    private function addSearch($where){
        $where['log_category']= ['login','='];
        $where['log_operate']= ['login','='];
        $where['log_level'] = [1,'='];
        return $where;
    }
}