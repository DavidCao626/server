<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程表单分类权限部门表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormSortDepartmentEntity extends BaseEntity
{
    /**
     * 流程表单分类权限部门表
     *
     * @var string
     */
	public $table = 'flow_form_sort_department';
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
        return  $this->HasOne('App\EofficeApp\System\Department\Entities\DepartmentEntity','dept_id','dept_id');
    }
}
