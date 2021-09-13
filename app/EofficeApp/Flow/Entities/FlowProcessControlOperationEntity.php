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
class FlowProcessControlOperationEntity extends BaseEntity
{
    /**
     * 流程分表
     *
     * @var string
     */
	public $table = 'flow_process_control_operation';
	public $primaryKey = 'operation_id';
	public $timestamps = false;

    /**
     * 一条固定流程节点的控件操作，关联“控件操作明细[operation_id => operation_id]”
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function controlOperationDetail()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessControlOperationDetailEntity','operation_id','operation_id');
    }
}
