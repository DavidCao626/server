<?php

namespace App\EofficeApp\Performance\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * performance_plan数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 * 
 */
class PerformancePlanEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'performance_plan';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['plan_start_type', 'plan_end_type', 'plan_start_day', 'plan_end_day', 'plan_is_useed', 'plan_is_remind', 'plan_note'];

    public function performanceTemp()
    {
        return $this->hasMany('App\EofficeApp\Performance\Entities\PerformanceTempEntity', 'plan_id');
    }
}
