<?php

namespace App\EofficeApp\Performance\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * performance_temp数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 * 
 */
class PerformanceTempEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'performance_temp';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['temp_name', 'temp_score', 'temp_content', 'plan_id', 'temp_note'];
}