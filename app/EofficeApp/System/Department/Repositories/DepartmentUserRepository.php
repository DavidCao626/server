<?php
namespace App\EofficeApp\System\Department\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Department\Entities\DepartmentUserEntity;
/**
 * @部门负责人资源库类
 *
 * @author 李志军
 */
class DepartmentUserRepository extends BaseRepository
{
	private $foreignKey		= 'dept_id'; //外键
	/**
	 *
	 * @param 
	 */
	public function __construct(DepartmentUserEntity $departmentUserEntity) {
		parent::__construct($departmentUserEntity);
    }
	
	public function truncateUser()
	{
		return $this->entity->truncate();
	}
}
