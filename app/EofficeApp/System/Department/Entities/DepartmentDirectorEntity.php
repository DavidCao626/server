<?php
namespace App\EofficeApp\System\Department\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @部门负责人类实体
 *
 * @author 李志军
 */
class DepartmentDirectorEntity extends BaseEntity
{
	protected $table            = 'department_director';

	public $foreignKey			= 'dept_id';

	public function department()
	{
		return $this->belongsTo('App\EofficeApp\System\Department\Entities\DepartmentEntity', $this->foreignKey);
	}

    public function directorHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
    }
}
