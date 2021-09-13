<?php

namespace App\EofficeApp\Performance\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * performance_personnel数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 * 
 */
class PerformancePersonalEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'performance_personal';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['perform_user', 'plan_id', 'temp_id', 'perform_points', 'perform_point', 'performer_user', 'perform_month', 'perform_appraise'];
}
