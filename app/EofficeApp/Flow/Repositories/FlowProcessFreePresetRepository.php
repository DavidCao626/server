<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessFreePresetEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 定义流程自由节点表知识库
 *
 */
class FlowProcessFreePresetRepository extends BaseRepository
{
    public function __construct(FlowProcessFreePresetEntity $entity) {
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
    function getList($id)
    {
        return $this->entity->where("node_id",$id)->orderBy('id','asc')->get();
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
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_process_free_preset.handle_user');
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_free_preset.node_id')
        ->select(['user.user_name', 'flow_process_free_preset.handle_user as user_id','flow_process_free_preset.node_id'])
        ->where("flow_process.flow_id",$flowId);
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
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_process_free_preset.handle_user');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_process_free_preset.handle_user');
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_free_preset.node_id')
        ->select(['user.user_name', 'flow_process_free_preset.handle_user as user_id','flow_process_free_preset.node_id'])
        ->where("flow_process.flow_id",$flowId)->where("user_system_info.user_status",2);
        return $query->get()->toArray();
    }
}
