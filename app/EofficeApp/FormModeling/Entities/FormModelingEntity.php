<?php

namespace App\EofficeApp\FormModeling\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程建模实体
 *
 * @author:白锦
 *
 * @since：2019-03-22
 *
 */
class FormModelingEntity extends BaseEntity
{
    // 实体表
    public $table = 'custom_fields_table';

    // 实体表主键
    public $primaryKey = 'field_id';
}
