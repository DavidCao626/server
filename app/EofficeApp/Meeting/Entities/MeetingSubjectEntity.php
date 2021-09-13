<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 实体
 *
 */
class MeetingSubjectEntity extends BaseEntity
{
   
	public $table = 'meeting_apply';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'meeting_apply_id';

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
		return $this->hasMany('App\EofficeApp\Meeting\Entities\MeetingPurviewEntity','subject_id','subject_id');
	}

	

    /**
     * subject表从属于一个类别
     * @return object
     */
    public function subjectBelongsToSort() {
        return $this->belongsTo('App\EofficeApp\Meeting\Entities\MeetingSortEntity','meeting_sort_id','meeting_sort_id');
    }

    /**
     * 会议有多条用户权限
     *
     * @return object
     */
    public function subjectHasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSubjectUserEntity','room_id','room_id');
    }

    /**
     * 会议有多条角色权限
     */
    public function subjectHasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSubjectRoleEntity','room_id','room_id');
    }

    /**
     * 会议有多条部门权限
     */
    public function subjectHasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSubjectDepartmentEntity','room_id','room_id');
    }

    /**
     * 会议有多条管理用户权限
     *
     */
    public function subjectHasManyManage()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSubjectManageEntity','room_id','room_id');
    }

    /**
     * 会议有多条审批用户权限，新关系用来判断管理权限
     */
    public function subjectHasManyManageForPower()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSubjectManageEntity','room_id','room_id');
    }
}
