<?php 

namespace App\EofficeApp\Role\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 路由访问记录Entity类:提供路由访问记录表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RouteVisitRecordEntity extends BaseEntity
{
    /**
     * 路由访问记录表
     *
     * @var string
     */
	protected $table = 'route_visit_record';	
}