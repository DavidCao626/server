<?php
namespace App\EofficeApp\System\Department\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Department\Entities\DepartmentDirectorEntity;
/**
 * @部门负责人资源库类
 *
 * @author 李志军
 */
class DepartmentDirectorRepository extends BaseRepository
{
	private $foreignKey		= 'dept_id'; //外键
	/**
	 *
	 * @param \App\EofficeApp\Entities\DepartmentDirectorEntity $departmentDirectorEntity
	 */
	public function __construct(DepartmentDirectorEntity $departmentDirectorEntity) {
		parent::__construct($departmentDirectorEntity);
    }
	/**
	 * @更新部门负责人
	 * @param type $deptId
	 * @param type $userIds
	 * @return boolean | id
	 */
	public function updateDirector($deptId,$userIds)
    {
        if (empty($userIds)) {
			$this->entity->where($this->foreignKey, $deptId)->delete();

			return true;
		}
		$directors = $this->entity->where($this->foreignKey, $deptId)->get();
		$data = ['dept_id' => $deptId];
		if ($directors) {
			$dbUserId = [];
			foreach ($directors as $director) {
				$dbUserId[] = $director->user_id;
			}
			$diffOldUser = array_diff($dbUserId, $userIds);
			$this->entity->where($this->foreignKey, $deptId)->whereIn('user_id', $diffOldUser)->delete();
			$diffNewUser = array_diff($userIds, $dbUserId);
			foreach ($diffNewUser as $userId) {
				$data['user_id'] = $userId;
				$this->entity->insertGetId($data);
			}
		} else {
			foreach ($userIds as $userId) {
				$data['user_id'] = $userId;
				$this->entity->insertGetId($data);
			}
		}
		return true;
	}
	/**
	 * @添加部门负责人
	 * @param type $deptId
	 * @param type $userIds
	 * @return boolean | id
	 */
	public function addDirector($deptId,$userIds)
    {
        $data = ['dept_id' => $deptId];
		foreach ($userIds as $userId) {
			$data['user_id'] = $userId;
			$this->entity->insertGetId($data);
		}
		return true;
	}
	/**
	 * @删除部门负责人
	 * @param type $deptId
	 * @return 成功失败信息
	 */
	public function deleteDirector($deptId)
	{
		return $this->entity->where($this->foreignKey, $deptId)->delete();
	}
	public function getManageDeptByUser($userId) {
		return $this->entity->where('user_id', $userId)->get();
    }
    public function getDirectorsByDeptIds($deptIds) {
        return $this->entity->whereIn('dept_id', $deptIds)->get();
    }
}
