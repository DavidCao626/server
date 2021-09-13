<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingExternalAttenceEntity;
/**
 * @会议申请资源库类
 *
 * @author 李志军
 */
class MeetingExternalAttenceRepository extends BaseRepository
{
	private $primaryKey = 'meeting_attence_id';//主键

	private $limit		= 20;//默认列表条数

	private $page		= 0;

	/**
	 * @注册会议申请实体
	 * @param \App\EofficeApp\Entities\MeetingApplyEntity $entity
	 */
	public function __construct(MeetingExternalAttenceEntity $entity)
	{
		parent::__construct($entity);
	}


    /**
	 * @对某个会议的某个人员进行会议签到
	 * @param type $data
	 * @param type $mApplyId
	 * @return object
	 */
	public function editSignMeeting($mApplyId, $data) {

		return $this->entity->where($this->primaryKey, $mApplyId)->update($data);
	}
	public function updateSignMeeting($userId, $mApplyId, $data) {
		return $this->entity->where('meeting_apply_id', $mApplyId)->where('meeting_external_user',$userId)->update($data);
	}

	/**
	 * /获取未签到人员
	 * @param  [type] $mApplyId [description]
	 * @param  [type] $userId   [description]
	 * @return [type]           [description]
	 */
	public function getNotSignInUser($mApplyId) {
		return $this->entity->select("meeting_attence_user","meeting_sign_time","user.user_name")->leftJoin('user','user.user_id','=','meeting_attendance.meeting_attence_user')->where("meeting_apply_id", '=' ,$mApplyId)->where('meeting_sign_status', '=', 0)->get();
	}

	public function getHasSignUser($mApplyId) {
		return $this->entity->select('*')->where($this->primaryKey, $mApplyId)->get()->toArray();
	}
	public function editAttenceUserSignStatus($mApplyId,$data) {
		return $this->entity->where('meeting_apply_id', '=', $mApplyId)->where('meeting_attence_status', '=', 1)->where('meeting_sign_status', '=', 0)->update($data);
	}
	// 获取是否已经签到
	public function getHasSignExterUser($userId, $mApplyId) {
		return $this->entity->where('meeting_apply_id', $mApplyId)->where('meeting_external_user', $userId)->get()->toArray();
	}
	// 更新外部人员签到信息
	public function deleteById($mApplyId) {
		return $this->entity->where('meeting_apply_id', $mApplyId)->delete();
	}
}
