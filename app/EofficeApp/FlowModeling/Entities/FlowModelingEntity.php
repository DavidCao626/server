<?php

namespace App\EofficeApp\FlowModeling\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程建模实体
 *
 * @author:缪晨晨
 *
 * @since：2018-02-28
 *
 */
class FlowModelingEntity extends BaseEntity
{
    // 实体表
    public $table = 'flow_module_factory';

    // 实体表主键
    public $primaryKey = 'module_id';
}
