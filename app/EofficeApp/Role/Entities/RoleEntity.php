<?php 

namespace App\EofficeApp\Role\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 角色Entity类:提供角色表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RoleEntity extends BaseEntity
{
    /**
     * 角色数据表
     *
     * @var string
     */
	public $table = 'role';

    /**
     * 主键
     *
     * @var string
     */    
    public $primaryKey = 'role_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */    
    public $dates = ['deleted_at'];

	/**
	 * 角色权限多对多关系
	 *
	 * @return object
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function permissions() 
	{
		return  $this->belongsToMany('App\EofficeApp\Role\Entities\PermissionEntity','role_permission','role_id','function_id');
	}

	/**
	 * 角色权限关系
	 *
	 * @return object
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function roleHaspermission() 
	{
		return  $this->hasMany('App\EofficeApp\Role\Entities\RolePermissionEntity');
	}
	/**
	 * 角色权限关系
	 *
	 * @return object
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function roleHasManyUser() 
	{
		return  $this->hasMany('App\EofficeApp\Role\Entities\UserRoleEntity' , 'role_id' , 'role_id');
	}

	 /*
     * 对应关系获取用户角色
     *
     *
     * @return boolean    [description]
     */
    public function hasManyRole()
    {
        return  $this->hasMany('App\EofficeApp\Role\Entities\UserRoleEntity', 'role_id', 'role_id');
    }

}
