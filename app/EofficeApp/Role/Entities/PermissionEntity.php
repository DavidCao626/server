<?php 

namespace App\EofficeApp\Role\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 权限Entity类:提供权限表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class PermissionEntity extends BaseEntity
{
	/**
	 * sys_function 权限表
	 * @var string
	 */
	protected $table = 'sys_function';

	/**
	 * 主键
	 * @var string
	 */
	public $primaryKey = 'function_id';	

	/**
	 * 权限角色关系
	 *
	 * @return object
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function permissionToRole() 
	{
		return  $this->hasMany('App\EofficeApp\Role\Entities\RolePermissionEntity','function_id','function_id');
	}

}