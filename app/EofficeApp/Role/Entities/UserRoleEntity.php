<?php

namespace App\EofficeApp\Role\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 用户角色Entity类:提供用户角色表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class UserRoleEntity extends BaseEntity
{
    /**
     * 用户角色表
     *
     * @var string
     */
	protected $table = 'user_role';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 角色权限关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-30 创建
     */
    public function hasManyPermission()
    {
        return  $this->hasMany('App\EofficeApp\Role\Entities\RolePermissionEntity', 'role_id', 'role_id');
    }

    /**
     * 对应关系获取角色名称
     *
     * @author dingpeng
     *
     * @return boolean    [description]
     */
    public function hasOneRole()
    {
        return  $this->hasOne('App\EofficeApp\Role\Entities\RoleEntity', 'role_id', 'role_id');
    }
     /**
     * 用户对应多个流程
     *
     * @return object
     */
    public function userHasManyFlowRunProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity', 'user_id', 'user_id');
    } 

}
