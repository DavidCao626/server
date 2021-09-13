<?php
namespace App\EofficeApp\System\Department\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @部门实体类
 * 
 * @author 李志军
 */
class DepartmentEntity extends BaseEntity
{
    protected $table            = 'department';
	
    public $primaryKey          = 'dept_id';

	public function directors()
    {
        return $this->hasMany('App\EofficeApp\System\Department\Entities\DepartmentDirectorEntity',$this->primaryKey);
    }

	public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }
    //部门对应u多个用户
    public function departmentHasManyUser()
    {
        return $this->hasMany('App\EofficeApp\User\Entities\UserSystemInfoEntity','dept_id', 'dept_id');
    }
    public function departmentHasManyPermissionUser()
    {
        return $this->hasMany('App\EofficeApp\System\Department\Entities\DepartmentUserEntity','dept_id', 'dept_id');
    }
    public function departmentHasManyRole()
    {
        return $this->hasMany('App\EofficeApp\System\Department\Entities\DepartmentRoleEntity','dept_id', 'dept_id');
    }
    public function departmentHasManyDept()
    {
        return $this->hasMany('App\EofficeApp\System\Department\Entities\DepartmentDeptEntity','dept_id', 'dept_id');
    }
}
