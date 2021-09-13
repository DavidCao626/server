<?php

namespace App\EofficeApp\System\Route\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Route\Repositories\RouteVisitRecordRepository;

/**
 * 路由访问记录service
 *
 * @author  齐少博
 *
 * @since  2016-02-18
 */
class RouteVisitRecordService extends BaseService
{
	/**
	 * [$routeVisitRecordRepository 路由访问记录表资源库]
	 *
	 * @var [object]
	 */
	protected $routeVisitRecordRepository;

	public function __construct(
		RouteVisitRecordRepository $routeVisitRecordRepository
	)
	{
		parent::__construct();

		$this->routeVisitRecordRepository = $routeVisitRecordRepository;
	}

    /**
     * 获取路由访问数量
     *
     * @param array $where 查询条件
     *
     * @return int  查询结果
     *
     * @author qishaobo
     *
     * @since  2016-02-18 创建
     */
    public function getRouteVisitRecordTimes($where)
    {
    	$result = $this->routeVisitRecordRepository->getRouteVisitRecord($where);
    	return isset($result['visit_times']) ? $result['visit_times'] : 0;
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
    	return $this->routeVisitRecordRepository->addRouteVisitRecordTimes($where);
    }


    /**
     * 新建路由访问
     *
     * @param array $data 新建数据
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-02-18 创建
     */
    public function addRouteVisitRecord($data)
    {
    	return $this->routeVisitRecordRepository->insertData($data);
    }

}
