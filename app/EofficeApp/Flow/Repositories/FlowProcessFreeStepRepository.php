<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessFreeStepEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 定义流程自由节点表知识库
 *
 */
class FlowProcessFreeStepRepository extends BaseRepository
{
    public function __construct(FlowProcessFreeStepEntity $entity) {
        parent::__construct($entity);
    }
    public function getMaxStep($runId,$nodeId) {
        return $this->entity->select('step_id')->where('run_id',$runId)->where('node_id',$nodeId)->orderBy('step_id','desc')->first();
    }
    public function getFreeNodeStepInfo($runId,$nodeId,$stepId)
    {
        $query = $this->entity;
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_free_step.node_id');
        $query = $query->select('flow_process_free_step.*','flow_process.overtime_except_nonwork','flow_process.overtime_handle_required','flow_process.press_add_hour','flow_process.press_add_hour_remind','flow_process.press_add_hour_turn','flow_process.process_type');
        $query = $query->where('flow_process_free_step.step_id',$stepId)->where('flow_process_free_step.run_id',$runId)->where('flow_process_free_step.node_id',$nodeId);
        return $query = $query->first();
    }
    public function getFreeNodeStepList($runId,$nodeId,$search = [])
    {
        $query = $this->entity->where('run_id',$runId)->where('node_id',$nodeId);
        if (!empty($search)) {
            $query = $query->wheres($search);
        }
        $query = $query->orderBy('step_id','asc');
        return $query->get();
    }
    /**
     * [getFreeProcessUser 获取自由节点中设置的用户统计]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFreeProcessUser($param)
    {
        $flowId = $param['flow_id'] ?? 0;
        $query = $this->entity;
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_process_free_step.user_id');
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_free_step.node_id')
        ->select(['user.user_name', 'flow_process_free_step.user_id', 'flow_process_free_step.node_id'])
        ->where("flow_process.flow_id",$flowId)->where("flow_process_free_step.is_superior","0");
        return $query->get()->toArray();
    }
    /**
     * [getFreeProcessUser 获取自由节点中设置的用户统计]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFreeProcessQuitUser($param)
    {
        $flowId = $param['flow_id'] ?? 0;
        $query = $this->entity;
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_process_free_step.user_id');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_process_free_step.user_id');
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_free_step.node_id')
        ->select(['user.user_name', 'flow_process_free_step.user_id', 'flow_process_free_step.node_id'])
        ->where("flow_process.flow_id",$flowId)->where("flow_process_free_step.is_superior","0")->where("user_system_info.user_status",2);
        return $query->get()->toArray();
    }
    /**
     * [getFreeProcessUser 获取自由节点中设置的用户替换]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function replaceUser($param)
    {
        $nodeId = $param['node_id'] ?? [];
        $replaceUser = $param['replace_user'];
        $user = $param['user'];
        return DB::update('update flow_process_free_step set user_id = replace(`user_id`,"'.$user.'","'.$replaceUser.'") where node_id in ("'.implode(',',$nodeId).'")');
    }
    function insertGetId($param = [])
    {
        $query = $this->entity->insertGetId($param);
        return $query;
    }
}