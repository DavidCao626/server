<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowProcessControlOperationDetailEntity;

/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessControlOperationDetailRepository extends BaseRepository
{
    public function __construct(FlowProcessControlOperationDetailEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    public function getList($param)
    {
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['operation_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by']);
        return $query->get();
    }
    /**
     * 获取可编辑字段，保存流程用
     */
    public function getEditControls($nodeId)
    {
        $query = $this->entity->select('flow_process_control_operation.control_id')
        ->leftJoin('flow_process_control_operation', 'flow_process_control_operation_detail.operation_id', '=', 'flow_process_control_operation.operation_id')
        ->where('flow_process_control_operation.node_id',$nodeId)
        ->where(function ($query) {
             $query->where('flow_process_control_operation_detail.operation_type','edit')->orWhere('flow_process_control_operation_detail.operation_type','attachmentUpload')->orWhere('flow_process_control_operation_detail.operation_type','attachmentEdit')->orWhere('flow_process_control_operation_detail.operation_type','attachmentDelete')->orWhere('flow_process_control_operation_detail.operation_type','isempty')->orWhere('flow_process_control_operation_detail.operation_type','always');
        });
        return $query->get();
    }
    /**
     * 获取不可更新字段，保存流程用
     */
    public function getNotUpdateControls($nodeId)
    {
        $query = $this->entity->select('flow_process_control_operation.control_id')
        ->leftJoin('flow_process_control_operation', 'flow_process_control_operation_detail.operation_id', '=', 'flow_process_control_operation.operation_id')
        ->where('flow_process_control_operation.node_id',$nodeId)
        ->where('flow_process_control_operation_detail.operation_type','notUpdate');
        return $query->get();
    }

}
