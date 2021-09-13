<?php

namespace App\EofficeApp\LogCenter\Repositories;
use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\LogCenter\Entities\LogSystemLoginEntity;

Class LogStatisticsRepository extends BaseRepository
{
    public function __construct(LogSystemLoginEntity $entity)
    {
        parent::__construct($entity);
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
        $where = $this->addSearch($where);
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
        $where = $this->addSearch($where);
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

        $where = $this->addSearch($where);
        $thisYear = date('Y-01-01 00:00:00');
        return $this->entity
            ->selectRaw("ip,COUNT(*) AS num")
            ->wheres($where)
            ->where('log_time', '>', $thisYear)  //时间要自动获取
            ->groupBy("ip")
            ->get();
        
    }

    private function addSearch($where){
        $where['log_category']= ['login','='];
        $where['log_operate']= ['login','='];
        $where['log_level'] = [1,'='];
        return $where;
    }
}