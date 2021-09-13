<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessCopyRoleEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessCopyRoleRepository extends BaseRepository
{
    public function __construct(FlowProcessCopyRoleEntity $entity)
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
     * 获取一条流程抄送角色数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowHandleRoleList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_copy_role.id');
        $query = $query->leftJoin('role', 'role.role_id', '=', 'flow_process_copy_role.role_id')
        ->select(['role.role_name','flow_process_copy_role.role_id','flow_process_copy_role.id as node_id'])
        ->where("flow_process.flow_id",$id);

        return $query->get()->toArray();         
    }
}
