<?php

namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 契约锁业务分类同步数据模型
 *
 * @author yuanmenglin
 * @since 
 */
class AttachmentRelataionQiyuesuoSealApplyImageEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'attachment_relataion_qiyuesuo_seal_apply_image';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'relation_id';
    
    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
}
