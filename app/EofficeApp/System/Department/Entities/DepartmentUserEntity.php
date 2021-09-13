<?php
namespace App\EofficeApp\System\Department\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @部门负责人类实体
 *
 * @author 李志军
 */
class DepartmentUserEntity extends BaseEntity
{
	protected $table            = 'department_user';

	public $foreignKey			= 'dept_id';

	public function department()
	{
		return $this->belongsTo('App\EofficeApp\System\Department\Entities\DepartmentEntity', $this->foreignKey);
	}

}
