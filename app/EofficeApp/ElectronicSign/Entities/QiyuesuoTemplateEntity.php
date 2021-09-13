<?php

namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 契约锁模板列表同步数据模型
 *
 * @author yuanmenglin
 * @since 
 */
class QiyuesuoTemplateEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_template';

    /**
     * 主键
     *
     * @var string
     */
    // public $primaryKey = 'templateId';

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
