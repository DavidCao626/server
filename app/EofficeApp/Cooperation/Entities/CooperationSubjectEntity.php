<?php
namespace App\EofficeApp\Cooperation\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 协作区主题实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSubjectEntity extends BaseEntity
{
    /**
     * 协作区主题表
     *
     * @var string
     */
	public $table = 'cooperation_subject';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'subject_id';

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
     * subject拥有一个创建人
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function subjectHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','subject_creater');
    }

	/**
	 * subject表拥有多条权限
	 * @return object
	 */
	public function subjectHasManyPurview() {
		return $this->hasMany('App\EofficeApp\Cooperation\Entities\CooperationPurviewEntity','subject_id','subject_id');
	}

	/**
	 * subject表拥有多条回复
	 * @return object
	 */
	public function subjectHasManyRevert() {
		return $this->hasMany('App\EofficeApp\Cooperation\Entities\CooperationRevertEntity','subject_id','subject_id');
	}

    /**
     * subject表从属于一个类别
     * @return object
     */
    public function subjectBelongsToSort() {
        return $this->belongsTo('App\EofficeApp\Cooperation\Entities\CooperationSortEntity','cooperation_sort_id','cooperation_sort_id');
    }

    /**
     * 协作主题有多条用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function subjectHasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSubjectUserEntity','subject_id','subject_id');
    }

    /**
     * 协作主题有多条角色权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function subjectHasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSubjectRoleEntity','subject_id','subject_id');
    }

    /**
     * 协作主题有多条部门权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function subjectHasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSubjectDepartmentEntity','subject_id','subject_id');
    }

    /**
     * 协作主题有多条管理用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function subjectHasManyManage()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSubjectManageEntity','subject_id','subject_id');
    }

    /**
     * 协作主题有多条管理用户权限，新关系用来判断管理权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function subjectHasManyManageForPower()
    {
        return  $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationSubjectManageEntity','subject_id','subject_id');
    }
}
