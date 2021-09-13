<?php 

namespace App\EofficeApp\Role\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Role\Entities\PermissionEntity;

/**
 * 权限Repository类:提供权限表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class PermissionRepository extends BaseRepository
{
    public function __construct(PermissionEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取权限角色列表
	 *
	 * @param  int $roleId 角色id
	 * 
	 * @return array
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function getPermissionRole($roleId)
	{
		$fields = ['function_id', 'menu_id', 'function_name', 'function_parent'];

		return $this->entity
			->select($fields)
			->with(['permissionToRole' => function ($query) use ($roleId) {
					    $query->select("function_id")->distinct()->where('role_id', $roleId);
					}])
			->get()
			->toArray();
	}		

}