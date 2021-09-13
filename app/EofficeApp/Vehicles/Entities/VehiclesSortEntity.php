<?php
namespace App\EofficeApp\Vehicles\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 车辆分类实体
 *
 * @author 李旭
 *
 * @since  2018-01-25 创建
 */
class VehiclesSortEntity extends BaseEntity
{
    /**
     * 分类表
     *
     * @var string
     */
	public $table = 'vehicles_sort';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'vehicles_sort_id';

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'desc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;

	/**
	 * 一个车辆类别下有多条车辆list
	 *
	 * @return object
	 */
	public function sortHasManySubjectList()
	{
		return $this->hasMany('App\EofficeApp\Vehicles\Entities\VehiclesEntity','vehicles_sort_id');
	}

    /**
     * 对应创建人
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','vehicles_sort_creater');
    }

    /**
     * 车辆类别有多条用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Vehicles\Entities\VehiclesSortMemberUserEntity','vehicles_sort_id');
    }

    /**
     * 车辆类别有多条角色权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Vehicles\Entities\VehiclesSortMemberRoleEntity','vehicles_sort_id');
    }

    /**
     * 车辆类别有多条部门权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Vehicles\Entities\VehiclesSortMemberDepartmentEntity','vehicles_sort_id');
    }

    public function sortHasManySubject()
    {
        return $this->hasMany('App\EofficeApp\Vehicles\Entities\VehiclesEntity','vehicles_sort_id', 'vehicles_sort_id');
    }


}
