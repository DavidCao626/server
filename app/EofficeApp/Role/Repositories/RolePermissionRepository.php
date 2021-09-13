<?php

namespace App\EofficeApp\Role\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Role\Entities\RolePermissionEntity;

/**
 * 角色权限Repository类:提供角色权限表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RolePermissionRepository extends BaseRepository
{
    public function __construct(RolePermissionEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取权限角色
	 *
	 * @param  array $role_id
	 *
	 * @return array
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function getRolePermission($roleId)
	{
		return $this->entity
			->distinct()
			->whereIn('role_id',$roleId)
			->pluck('function_id')
			->toArray();
	}
}