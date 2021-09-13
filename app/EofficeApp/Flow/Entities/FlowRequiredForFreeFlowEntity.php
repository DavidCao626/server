<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 自由流程必填设置
 */
class FlowRequiredForFreeFlowEntity extends BaseEntity
{
    /**
     * 流程分表
     *
     * @var string
     */
	public $table = 'flow_required_for_free_flow';
	public $primaryKey = 'auto_id';
    public $timestamps = false;
}
