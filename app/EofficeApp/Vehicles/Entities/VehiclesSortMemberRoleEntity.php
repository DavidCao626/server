<?php
namespace App\EofficeApp\Vehicles\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 车辆分类权限表
 *
 */
class VehiclesSortMemberRoleEntity extends BaseEntity
{
    /**
     * 车辆分类权限表
     *
     * @var string
     */
	public $table = 'vehicles_sort_member_role';

    /**
     * 对应角色
     *
     * @method hasOneRole
     *
     * @return boolean    [description]
     */
    public function hasOneRole()
    {
        return  $this->HasOne('App\EofficeApp\Role\Entities\RoleEntity','role_id','role_id');
    }

}
