<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessDefaultUserEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessDefaultUserRepository extends BaseRepository
{
    public function __construct(FlowProcessDefaultUserEntity $entity)
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
    function getList($id)
    {
        return $this->entity
                    ->where("id",$id)
                    ->get();
    }
    /**
     * 获取一条流程默认办理人人员中离职人员数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowQuitUserList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_default_user.id');
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_process_default_user.user_id');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id')
        ->select(['user.user_name','flow_process_default_user.user_id','flow_process_default_user.id as node_id'])
        ->where("flow_process.flow_id",$id)
        ->where("user_system_info.user_status",2);
        return $query->get()->toArray();
    }
    /**
     * 获取一条流程默认办理人数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowHandleUserList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_default_user.id');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_process_default_user.user_id')
        ->select(['user.user_name','flow_process_default_user.user_id','flow_process_default_user.id as node_id'])
        ->where("flow_process.flow_id",$id);

        return $query->get()->toArray();
    }
    /**
     * 获取节点默认办理人
     *
     */
    function getProcessDefaultUserInfo($allNodeIds,$all_quit_user_ids) {
        $query = $this->entity;
        if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
            $_chunks = array_chunk($all_quit_user_ids, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $_ch) {
                    $query = $query->orWhereIn('user_id',$_ch);
                }
            });
            unset($_chunks);
            unset($all_quit_user_ids);
        }else{
            $query = $query->whereIn('user_id',$all_quit_user_ids);
        }
        if (is_array($allNodeIds) && count($allNodeIds) > 1000) {
            $chunks = array_chunk($allNodeIds, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $_ch) {
                    $query = $query->orWhereIn('id',$_ch);
                }
            });
            unset($chunks);
            unset($allNodeIds);
        }else{
            $query = $query->whereIn('id',$allNodeIds);
        }
        return $query->get();
    }
}
