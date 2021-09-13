<?php
namespace App\EofficeApp\System\Department\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Department\Entities\DepartmentDeptEntity;
/**
 * @部门负责人资源库类
 *
 * @author 李志军
 */
class DepartmentDeptRepository extends BaseRepository
{
	private $foreignKey		= 'dept_id'; //外键
	/**
	 *
	 * @param \App\EofficeApp\Entities\DepartmentDirectorEntity $departmentDirectorEntity
	 */
	public function __construct(DepartmentDeptEntity $departmentDeptEntity) {
		parent::__construct($departmentDeptEntity);
    }
	
	public function truncateDept()
	{
		return $this->entity->truncate();
	}
}
