<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 *
 * 微博设置Entity类:提供微博设置选项数据。
 *
 * Class DiaryPermissionEntity
 * @package App\EofficeApp\Diary\Entities
 */
class DiaryPermissionEntity extends BaseEntity
{
    /**
     * 微博便签数据表
     *
     * @var string
     */
    public $table = 'diary_permission';
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
