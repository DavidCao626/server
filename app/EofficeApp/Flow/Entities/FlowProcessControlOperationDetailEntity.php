<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 流程分表
 *
 * @author 丁鹏
 *
 * @since  2018-04-25 创建
 */
class FlowProcessControlOperationDetailEntity extends BaseEntity
{
    /**
     * 流程分表
     *
     * @var string
     */
	public $table = 'flow_process_control_operation_detail';
	public $primaryKey = 'auto_detail_id';
	public $timestamps = false;
}
