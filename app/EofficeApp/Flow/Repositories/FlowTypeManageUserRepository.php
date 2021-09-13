<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowTypeManageUserEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowTypeManageUserRepository extends BaseRepository
{
    public function __construct(FlowTypeManageUserEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $where [description]
     *
     * @return [type]          [description]
     */
    function getList($where)
    {
        return $this->entity
                    ->wheres($where)
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
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_type_manage_user.user_id');
        // $query = $query->leftJoin('flow_type', 'flow_type.flow_id', '=', 'flow_type_manage_user.flow_id');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id')
        ->select(['user.user_name','flow_type_manage_user.user_id',DB::raw('COUNT(flow_type_manage_user.flow_id) as count_manage_user')])// ,DB::raw('GROUP_CONCAT(flow_type.flow_name) as count_manage_user_all')
        ->where("flow_type_manage_user.flow_id",$id)
        ->where("user_system_info.user_status",2)
        ->groupBy('flow_type_manage_user.user_id');
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
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_type_manage_user.user_id')
        ->select(['user.user_name','flow_type_manage_user.user_id',DB::raw('COUNT(flow_type_manage_user.flow_id) as count_manage_user')])
        ->where("flow_type_manage_user.flow_id",$id)
        ->groupBy('flow_type_manage_user.user_id');

        return $query->get()->toArray();
    }
    /**
     * 获取流程监控人列表
     *
     * @param  [type]  $param [查询条件]
     *
     * @return [type]          [description]
     */
    function getFlowTypeManageUserList($param)
    {
        $query = $this->entity;
        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->get()->toArray();
    }
    /**
     * 获取办理人
     *
     */
    function getManageUserList($flowId,$all_quit_user_ids) {
        $query = $this->entity;
        if($flowId == 'all') {
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
            return $query->get();
        }
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
        if (is_array($flowId) && count($flowId) > 1000) {
            $chunks = array_chunk($flowId, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $_ch) {
                    $query = $query->orWhereIn('flow_id',$_ch);
                }
            });
            unset($chunks);
            unset($flowId);
        }else{
            $query = $query->whereIn('flow_id',$flowId);
        }
        return $query->get();
    }
}
