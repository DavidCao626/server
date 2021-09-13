<?php
namespace App\EofficeApp\Vehicles\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 车辆分类权限表
 *
 */
class VehiclesSortMemberDepartmentEntity extends BaseEntity
{
    /**
     * 车辆分类权限表
     *
     * @var string
     */
	public $table = 'vehicles_sort_member_department';

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
