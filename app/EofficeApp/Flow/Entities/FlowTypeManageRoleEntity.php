<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程分表 监控人员指定角色表
 *
 * @author 缪晨晨
 *
 * @since  2018-04-16 创建
 */
class FlowTypeManageRoleEntity extends BaseEntity
{
    /**
     * 流程分表 监控人员指定角色表
     *
     * @var string
     */
	public $table = 'flow_type_manage_role';
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
