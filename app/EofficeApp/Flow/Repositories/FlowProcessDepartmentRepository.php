<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessDepartmentEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessDepartmentRepository extends BaseRepository
{
    public function __construct(FlowProcessDepartmentEntity $entity)
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
     * 获取一条流程办理部门数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowHandleDeptList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('flow_process', 'flow_process.node_id', '=', 'flow_process_department.id');
        $query = $query->leftJoin('department', 'department.dept_id', '=', 'flow_process_department.dept_id')
        ->select(['department.dept_name','flow_process_department.dept_id','flow_process_department.id as node_id'])
        ->where("flow_process.flow_id",$id);

        return $query->get()->toArray();         
    }
}
