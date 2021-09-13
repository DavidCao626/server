<?php

namespace App\EofficeApp\System\Security\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * system_params表实体
 *
 * @author  朱从玺
 *
 * @since  2015-10-28
 */
class SystemParamsEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     * 
     * @var string
     */
    protected $table = 'system_params';

    /**
     * [$table 数据表主键]
     * 
     * @var string
     */
    protected $primaryKey = 'param_key';
    public $casts = ['param_key' => 'string'];

    /**
     * [$timestamps 禁用时间戳字段]
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * [$fillable 允许被赋值的字段]
     * 
     * @var [array]
     */
    protected $fillable = ['param_key', 'param_value'];
}