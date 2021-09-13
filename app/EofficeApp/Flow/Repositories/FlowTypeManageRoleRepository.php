<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowTypeManageRoleEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 流程分表知识库 监控人员指定角色表
 *
 * @author 缪晨晨
 *
 * @since  2018-04-16 创建
 */
class FlowTypeManageRoleRepository extends BaseRepository
{
    public function __construct(FlowTypeManageRoleEntity $entity)
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
     * 获取一条流程办理角色数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowHandleRoleList($id)
    {
        $query = $this->entity;
        //$query = $query->leftJoin('flow_type', 'flow_type.flow_id', '=', 'flow_type_manage_role.flow_id');
        $query = $query->leftJoin('role', 'role.role_id', '=', 'flow_type_manage_role.role_id')
        ->select(['role.role_name','flow_type_manage_role.role_id','flow_type_manage_role.flow_id'])
        ->where("flow_type_manage_role.flow_id",$id);

        return $query->get()->toArray();
    }
}
