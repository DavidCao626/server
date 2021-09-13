<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

class MeetingSubjectRoleEntity extends BaseEntity
{
    /**
     * 协作区权限表
     *
     * @var string
     */
	public $table = 'meeting_apply_member_role';

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
