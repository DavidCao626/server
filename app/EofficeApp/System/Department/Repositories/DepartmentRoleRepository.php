<?php
namespace App\EofficeApp\System\Department\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Department\Entities\DepartmentRoleEntity;
/**
 * @部门负责人资源库类
 *
 * @author 李志军
 */
class DepartmentRoleRepository extends BaseRepository
{
	private $foreignKey		= 'dept_id'; //外键
	/**
	 *
	 * @param \App\EofficeApp\Entities\DepartmentDirectorEntity $departmentDirectorEntity
	 */
	public function __construct(DepartmentRoleEntity $departmentRoleEntity) {
		parent::__construct($departmentRoleEntity);
    }
	
	public function truncateRole()
	{
		return $this->entity->truncate();
	}
}
