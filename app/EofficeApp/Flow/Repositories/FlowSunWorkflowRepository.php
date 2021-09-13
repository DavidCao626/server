<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowSunWorkflowEntity;
use App\EofficeApp\Base\BaseRepository;
/**
 * 流程数据外发子流程数据表知识库
 *
 * @author 史瑶
 *
 * @since  2016-01-11 创建
 */
class FlowSunWorkflowRepository extends BaseRepository
{
    public function __construct(FlowSunWorkflowEntity $entity)
    {
        parent::__construct($entity);
    }
    function getSunflowInfo($nodeId, $returnType='array') {
        $query = $this->entity->leftJoin('flow_type', 'flow_type.flow_id', '=', 'flow_sun_workflow.receive_flow_id');
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_sun_workflow.node_id');
        $query = $query->select(['flow_sun_workflow.receive_flow_id','flow_sun_workflow.id','flow_sun_workflow.premise','flow_type.flow_name']);
        $query->where('flow_process.sun_flow_toggle',1);
        $query->where('flow_sun_workflow.node_id',$nodeId);
        $query->orderBy('flow_sun_workflow.id', 'asc');
        if ($returnType == 'array') {
            return $query->get()->toArray();
        } else if ($returnType == 'count') {
            return $query->count();
        }
    }
    function getUnfinishedSunflowList($sunflow_run_ids,$nodeId) {
    	$query = $this->entity->leftJoin('flow_run', 'flow_run.flow_id', '=', 'flow_sun_workflow.receive_flow_id');
    	$query = $query->select(['flow_run.run_name','flow_run.run_id']);
        $query -> whereIn('flow_run.run_id',$sunflow_run_ids);
        $query -> where('flow_run.current_step','!=',0);
        $query -> where('flow_sun_workflow.run_ways',1);
        $query -> where('flow_sun_workflow.node_id',$nodeId);
        $query -> whereRaw('flow_run.deleted_at is NULL');
    	return $query->get()->toArray();
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
    function getList($id)
    {
        return $this->entity
                    ->where("node_id",$id)
                    ->get();
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
    function getLists($param)
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => 10,
            'search'    => [],
            'order_by'  => ['id'=>'asc'],
        ];
        $param = array_merge($default, $param);

        $query = $this->entity->select($param['fields']);
        if(!empty($param['search'])) {
            $query = $query->multiwheres($param['search']);
        }
        $query = $query->orders($param['order_by']);

        if($param['page']>0) {
            $query = $query->forPage($param['page'], $param['limit']);
        }
        return $query->get()->toArray();
    }
}
