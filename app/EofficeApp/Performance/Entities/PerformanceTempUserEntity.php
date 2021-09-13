<?php

namespace App\EofficeApp\Performance\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * performance_temp_user数据表实体
 *
 * @author  朱从玺
 *
 * @since   2016-05-04
 * 
 */
class PerformanceTempUserEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'performance_temp_user';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['user_id', 'temp_id', 'plan_id'];
}