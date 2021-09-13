<?php
namespace App\EofficeApp\Cooperation\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 协作区分类实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSortEntity extends BaseEntity
{
    /**
     * 协作区分类表
     *
     * @var string
     */
	public $table = 'cooperation_sort';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'cooperation_sort_id';

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
     * 一个协作类别下有多条协作，用来计算下属协作数量
     *
     * @return object
     */
    public function sortHasManySubject()
    {
        return $this->hasMany('App\EofficeApp\Cooperation\Entities\CooperationSubjectEntity','cooperation_sort_id','cooperation_sort_id');
    }

	/**
	 * 一个协作类别下有多条协作list
	 *
	 * @return object
	 */
	public function sortHasManySubjectList()
	{
		return $this->hasMany('App\EofficeApp\Cooperation\Entities\CooperationSubjectEntity','cooperation_sort_id','cooperation_sort_id');
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
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','cooperation_sort_creater');
    }

    /**
     * 协作类别有多条用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSortMemberUserEntity','cooperation_sort_id','cooperation_sort_id');
    }

    /**
     * 协作类别有多条角色权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSortMemberRoleEntity','cooperation_sort_id','cooperation_sort_id');
    }

    /**
     * 协作类别有多条部门权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSortMemberDepartmentEntity','cooperation_sort_id','cooperation_sort_id');
    }

}
