<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 会议分类实体
 *
 * @author 李旭
 *
 * @since  2018-01-25 创建
 */
class MeetingSortEntity extends BaseEntity
{
    /**
     * 会议分类表
     *
     * @var string
     */
	public $table = 'meeting_sort';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'meeting_sort_id';

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
     * 一个会议类别下有多条会议，用来计算下属会议数量
     *
     * @return object
     */
    public function sortHasManySubject()
    {
        return $this->hasMany('App\EofficeApp\Meeting\Entities\MeetingRoomsEntity','meeting_sort_id','meeting_sort_id');
    }

	/**
	 * 一个会议类别下有多条会议list
	 *
	 * @return object
	 */
	public function sortHasManySubjectList()
	{
		return $this->hasMany('App\EofficeApp\Meeting\Entities\MeetingRoomsEntity','meeting_sort_id','meeting_sort_id');
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
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','meeting_sort_creater');
    }

    /**
     * 会议类别有多条用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSortMemberUserEntity','meeting_sort_id','meeting_sort_id');
    }

    /**
     * 会议类别有多条角色权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSortMemberRoleEntity','meeting_sort_id','meeting_sort_id');
    }

    /**
     * 会议类别有多条部门权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function sortHasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Meeting\Entities\MeetingSortMemberDepartmentEntity','meeting_sort_id','meeting_sort_id');
    }

}
