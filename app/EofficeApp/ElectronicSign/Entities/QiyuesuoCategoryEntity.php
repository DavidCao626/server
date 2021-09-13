<?php

namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 契约锁业务分类同步数据模型
 *
 * @author yuanmenglin
 * @since 
 */
class QiyuesuoCategoryEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_category';

    /**
     * 主键
     *
     * @var string
     */
    // public $primaryKey = 'categoryId';

    /**
     * 执行模型是否自动维护时间戳.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
}
