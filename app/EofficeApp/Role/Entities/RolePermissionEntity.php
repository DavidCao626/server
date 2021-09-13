<?php 

namespace App\EofficeApp\Role\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 角色权限Entity类:提供角色权限表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RolePermissionEntity extends BaseEntity
{
    /**
     * 角色权限数据表
     *
     * @var string
     */
	protected $table = 'role_permission';	
}