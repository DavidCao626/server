<?php
namespace App\EofficeApp\Cooperation\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 协作区分类权限表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSortMemberRoleEntity extends BaseEntity
{
    /**
     * 协作区分类权限表
     *
     * @var string
     */
	public $table = 'cooperation_sort_member_role';

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
