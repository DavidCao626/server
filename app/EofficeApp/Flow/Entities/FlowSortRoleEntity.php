<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程分类权限角色表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowSortRoleEntity extends BaseEntity
{
    /**
     * 流程表单分类权限角色表
     *
     * @var string
     */
	public $table = 'flow_sort_role';
    public $timestamps = false;
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
