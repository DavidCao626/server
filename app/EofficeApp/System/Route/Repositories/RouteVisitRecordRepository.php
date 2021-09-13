<?php

namespace App\EofficeApp\System\Route\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Route\Entities\RouteVisitRecordEntity;

/**
 * 路由访问记录表资源库
 *
 * @author  齐少博
 *
 * @since  2016-02-18 创建
 */
class RouteVisitRecordRepository extends BaseRepository
{
	public function __construct(RouteVisitRecordEntity $entity)
	{
		parent::__construct($entity);
	}

    /**
     * 获取路由访问数量
     *
     * @param array $where 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-02-18 创建
     */
    public function getRouteVisitRecord($where)
    {
    	$result = $this->entity->wheres($where)->first();
    	return $result ? $result->toArray() : '';
    }

    /**
     * 添加路由访问数量
     *
     * @param array $where 查询条件
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-02-18 创建
     */
    public function addRouteVisitRecordTimes($where)
    {
    	return $this->entity->wheres($where)->increment('visit_times');
    }
}