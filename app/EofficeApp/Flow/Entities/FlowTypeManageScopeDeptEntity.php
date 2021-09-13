<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程分表 监控范围指定部门表
 *
 * @author 缪晨晨
 *
 * @since  2018-04-16 创建
 */
class FlowTypeManageScopeDeptEntity extends BaseEntity
{
    /**
     * 流程分表 监控范围指定部门表
     *
     * @var string
     */
	public $table = 'flow_type_manage_scope_dept';
    public $timestamps = false;
    /**
     * 对应部门
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function hasOneDept()
    {
        return  $this->HasOne('App\EofficeApp\Department\Entities\DepartmentEntity','dept_id','dept_id');
    }
}
