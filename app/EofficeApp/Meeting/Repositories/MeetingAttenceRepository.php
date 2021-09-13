<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingAttenceEntity;
/**
 * @会议申请资源库类
 *
 * @author 李志军
 */
class MeetingAttenceRepository extends BaseRepository
{
	private $primaryKey = 'meeting_attence_id';//主键

	private $limit		= 20;//默认列表条数

	private $page		= 0;

	/**
	 * @注册会议申请实体
	 * @param \App\EofficeApp\Entities\MeetingApplyEntity $entity
	 */
	public function __construct(MeetingAttenceEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * @参加会议
	 * @param type $data
	 * @return 会议Id | int
	 */
	public function attenceMeeting($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * @拒绝参加会议
	 * @param type $data
	 * @param type $mApplyId
	 * @return boolean
	 */
	public function refuseAttenceMeeting($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * @获取某条会议的参会人员
	 * @param type $data
	 * @param type $mApplyId
	 * @return object
	 */
	public function getMineMeetingDetail($param) {
        $mApplyId = isset($param['meeting_apply_id']) ?$param['meeting_apply_id'] : '';
        $default = [
            'fields' => ['meeting_apply.meeting_subject','meeting_apply.meeting_begin_time','meeting_apply.meeting_end_time','user.user_name', 'user_system_info.user_status', 'meeting_attendance.*'
                        ],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['user.user_id' => 'desc'],
            'search' => [],
            'returntype' => 'array'
        ];
        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select($param['fields'])->leftJoin('user', function($join) {
                    $join->on('user.user_id','=','meeting_attendance.meeting_attence_user');
                })->leftJoin('user_system_info', function($join) use ($param) {
                    $join->on('user_system_info.user_id', "=","meeting_attendance.meeting_attence_user");
                })->where(function ($query) use ($mApplyId) {
                    $query->where('meeting_attendance.meeting_apply_id','=',$mApplyId)
                          ->where('meeting_attendance.meeting_attence_status','=',1);
                })->leftJoin('meeting_apply', function($join) use ($param) {
                    $join->on('meeting_apply.meeting_apply_id', "=","meeting_attendance.meeting_apply_id");
                });
        $query->wheres($param['search'])
                ->orders($param['order_by'])
                ->parsePage($param['page'], $param['limit']);
                    // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->get()->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
	 * @获取某条会议的参会人员的总数
	 * @param type $data
	 * @param type $mApplyId
	 * @return object
	 */
    public function getMineMeetingDetailCount($param) {
        $mApplyId = isset($param['meeting_apply_id']) ?$param['meeting_apply_id'] : '';
        $param["page"]   = 0;
        $param["returntype"] = "count";
        return $this->getMineMeetingDetail($param);
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
	public function updateSignMeeting($mApplyId, $userId,$data) {
		return $this->entity->where('meeting_apply_id', $mApplyId)->where('meeting_attence_user',$userId)->where('meeting_attence_status', '=', 1)->update($data);
	}
	/**
	 * 获取摸个会议的签到人员
	 *
	 *
	 *
	 */
	public function getSignInUser($mApplyId) {
		return $this->entity->select("meeting_attence_user","meeting_sign_time","user.user_name")->leftJoin('user','user.user_id','=','meeting_attendance.meeting_attence_user')->where("meeting_apply_id", '=' ,$mApplyId)->where('meeting_sign_status', '=', 1)->get();
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
    public function getMineMeetingAttenceDetail($mApplyId) {

        $default = [
            'fields' => ['meeting_apply.meeting_subject','meeting_apply.meeting_begin_time','meeting_apply.meeting_end_time','user.user_name', 'meeting_attendance.*'
                        ]
        ];

        $query = $this->entity->select($default['fields'])->leftJoin('user', function($join) {
                    $join->on('user.user_id','=','meeting_attendance.meeting_attence_user');
                })->where(function ($query) use ($mApplyId) {
                    $query->where('meeting_attendance.meeting_apply_id','=',$mApplyId)
                          ->where('meeting_attendance.meeting_attence_status','=',1);
                })->leftJoin('meeting_apply', function($join) use ($mApplyId) {
                    $join->on('meeting_apply.meeting_apply_id', "=","meeting_attendance.meeting_apply_id");
                });
            return $query->get()->toArray();
    }
    /**
     * /
     * @param  [type] $mApplyId [description]
     * @param  [type] $userId   [description]
     * @return [type]  int      [description]
     */
    public function getHasSignUserList($mApplyId, $userId) {
        return $this->entity->select('*')->where('meeting_apply_id', $mApplyId)->where('meeting_attence_user', $userId)->get()->toArray();
    }
    // 更新人员签到信息
    public function deleteDataById($mApplyId) {
        return $this->entity->where('meeting_apply_id', $mApplyId)->delete();
    }
    public function getAttenceStatus($mApplyId, $userId) {
        return $this->entity
                    ->select(['meeting_attence_status', 'meeting_attence_id'])
                    ->where('meeting_apply_id', $mApplyId)
                    ->where('meeting_attence_user', $userId)
                    ->get()->toArray();
    }
    public function getAttenceRemark($mApplyId, $userId) {
        return $this->entity
                    ->select(['meeting_attence_remark', 'meeting_attence_id'])
                    ->where('meeting_apply_id', $mApplyId)
                    ->where('meeting_attence_user', $userId)
                    ->get()->toArray();
    }

}
