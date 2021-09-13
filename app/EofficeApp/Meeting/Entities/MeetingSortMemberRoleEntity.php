<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

class MeetingSortMemberRoleEntity extends BaseEntity
{
    /**
     * 协作区分类权限表
     *
     * @var string
     */
	public $table = 'meeting_sort_member_role';

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
