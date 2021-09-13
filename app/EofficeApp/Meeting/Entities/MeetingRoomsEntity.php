<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议室实体
 *
 * @author 李志军
 */
class MeetingRoomsEntity extends BaseEntity
{
    /**
     * 会议分类表
     *
     * @var string
     */
	public $table = 'meeting_rooms';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'room_id';

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
    public function roomHasManySubject()
    {
        return $this->hasMany('App\EofficeApp\Meeting\Entities\MeetingRoomsEntity','room_id','room_id');
    }

	/**
	 * 一个会议类别下有多条会议list
	 *
	 * @return object
	 */
	public function roomHasManySubjectList()
	{
		return $this->hasMany('App\EofficeApp\Meeting\Entities\MeetingRoomsEntity','room_id','room_id');
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
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','meeting_room_creater');
    }
}
