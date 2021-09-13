<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingApplyEntity;
use App\EofficeApp\Meeting\Entities\MeetingAttenceEntity;
use DB;
/**
 * @会议申请资源库类
 *
 * @author 李志军
 */
class MeetingApplyRepository extends BaseRepository
{
	private $primaryKey = 'meeting_apply.meeting_apply_id';//主键

	private $limit		= 20;//默认列表条数

	private $page		= 0;

	private $orderBy	= ['meeting_begin_time' => 'desc'];//默认排序

	/**
	 * @注册会议申请实体
	 * @param \App\EofficeApp\Entities\MeetingApplyEntity $entity
	 */
	public function __construct(MeetingApplyEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * @获取我的会议申请列表
	 * @param type $param
	 * @return 会议申请列表 | array
	 */
	public function getMyMeetingList($param)
	{
        $default = [
            'fields' => ['meeting_apply.*', 'meeting_rooms.*', 'user.user_name', 'meeting_attendance.meeting_attence_id',
            'meeting_attendance.meeting_attence_user','meeting_attendance.meeting_attence_status','meeting_attendance.meeting_attence_remark','meeting_attendance.meeting_refuse_remark','meeting_attendance.meeting_apply_id as attence_meeting_apply_id','meeting_attendance.meeting_sign_type','meeting_attendance.meeting_attence_time','meeting_attendance.meeting_refuse_time','meeting_attendance.meeting_sign_time'

            ],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];
        if (isset($param['search']['meeting_apply_id'])) {
            $param['search']['meeting_apply.meeting_apply_id'] = $param['search']['meeting_apply_id'];
            unset($param['search']['meeting_apply_id']);
        }
        //排除已假删除的(已拒绝的会议+结束的会议)
       	$param['search']['false_delete'] = ['1','!='];
        $time = date("Y-m-d H:i:s", time());
        $param = array_merge($default, array_filter($param));
        $query = $this->entity->select($param['fields'])->leftJoin('meeting_rooms', function($join) {
                    $join->on("meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id');
                })->where(function($query) use ($param) {
                    $query->WhereRaw("(FIND_IN_SET('" . $this->filterUserId($param['meeting_apply_user']) . "', meeting_apply.meeting_apply_user)) and meeting_apply.meeting_status in (1,2,3,4,5)")
                    ->orwhere(function ($query) use ($param) {
                        $query->orWhereRaw("(FIND_IN_SET('" . $this->filterUserId($param['meeting_apply_user']) . "', meeting_apply.meeting_sign_user)) and meeting_apply.meeting_status in (2,4,5)")
                              ->orWhereRaw("(FIND_IN_SET('" . $this->filterUserId($param['meeting_apply_user']) . "', meeting_join_member) or meeting_apply.meeting_join_member = 'all') and meeting_apply.meeting_status in (2,4,5)");
                    });
                })->leftJoin('user', function($join) {
                	$join->on("user.user_id", '=', 'meeting_apply.meeting_apply_user');
            	})->leftJoin('meeting_attendance', function($join) use ($param) {
                    $join->on('meeting_apply.meeting_apply_id', "=","meeting_attendance.meeting_apply_id")->where('meeting_attendance.meeting_attence_user', "=", $param['user_id']);
                });

        // $query = $query->whereIn('meeting_apply.meeting_status',[2,4,5]);
        if(isset($param['search']['meeting_join_member']) && $param['search']['meeting_join_member']) {
        	$query = $query->whereRaw("(FIND_IN_SET('" . $this->filterUserId($param['search']['meeting_join_member'][0]) . "', meeting_join_member) or meeting_apply.meeting_join_member = 'all')");
            unset($param['search']['meeting_join_member']);
        }
        if (isset($param['search']['meeting_status'])) {
            if ($param['search']['meeting_status'][0] == 2) {
                
                $query = $query->where(function($query) use ($param, $time) {
                    $query->wheres($param['search'])->where('meeting_begin_time', '>', $time);
                });
                unset($param['search']['meeting_status']);
            }
        }
        if (isset($param['search']['meeting_status'])) {
            if ($param['search']['meeting_status'][0] == 4) {
                $query = $query->where(function($query) use ($time) {
                        $query->where('meeting_end_time', '>', $time)
                              ->where('meeting_begin_time', '<=', $time)
                              ->whereRaw('meeting_apply.meeting_status in (2,4)');
                });
                unset($param['search']['meeting_status']);
            }
        }
        if (isset($param['search']['meeting_status'])) {
            if ($param['search']['meeting_status'][0] == 5) {
                        $query = 
                        $query = $query->where(function($query) use ($time) {
                            $query->where('meeting_end_time', '<=', $time)
                                  ->whereRaw('meeting_apply.meeting_status in (2,4,5)');
                        });
                unset($param['search']['meeting_status']);
            }
        }
        $query =  $query->wheres($param['search'])
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
	 * @获取我的会议申请数量
	 * @param type $param
	 * @return 申请数量 | int
	 */
	public function getMyMeetingCount($param)
	{
        $param["page"]   = 0;
        $param["returntype"] = "count";
        return $this->getMyMeetingList($param);
	}
	/**
	 * @获取审批会议列表
	 * @param type $param
	 * @return 会议申请列表 | array
	 */
	public function getApproveMeetingList($param)
	{
        $default = [
            'fields' => ['meeting_apply.*', 'meeting_rooms.*','user.user_name','user.user_id', 'meeting_sort.meeting_approvel_user'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];

        // 排除已假删除的(已拒绝的会议+结束的会议)
       	$param['search']['false_delete'] = ['2','!='];
        $time = date("Y-m-d H:i:s", time());
        $param = array_merge($default, array_filter($param));
        // 验证权限的时候，用户id必填！
        $userId     = isset($param["user_id"]) ? $param["user_id"]:"";
        if($userId == "") {
            return $this->entity;
        }
        $roleId        = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId        = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $query = $this->entity->select($param['fields'])->leftJoin('meeting_rooms', function($join) {
                    $join->on("meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id');
                })->leftJoin('user', function($join) {
                    $join->on("user.user_id", '=', 'meeting_apply.meeting_apply_user');
                })->leftjoin('meeting_sort', function($join) {
                    $join->on("meeting_sort.meeting_sort_id", "=", "meeting_rooms.meeting_sort_id");
                })->whereRaw("(FIND_IN_SET(?, meeting_approvel_user) or meeting_sort.meeting_approvel_user = 'all')",[$userId]);
        $query = $query->where('meeting_apply.meeting_status', '!=', 8);
        if(isset($param['search']['meeting_join_member']) && $param['search']['meeting_join_member']) {
        	$query = $query->whereRaw("(FIND_IN_SET('" . $this->filterUserId($param['search']['meeting_join_member'][0]) . "', meeting_join_member) or meeting_apply.meeting_join_member = 'all')");
            unset($param['search']['meeting_join_member']);
        }
        if (isset($param['search']['meeting_status'])) {
            if ($param['search']['meeting_status'][0] == 2) {
                $query = $query->wheres($param['search'])->where('meeting_begin_time', '>', $time);
                unset($param['search']['meeting_status']);
            }
        }
        if (isset($param['search']['meeting_approval_status'])) {
                $query = $query->where('meeting_status', '<', 2);
                unset($param['search']['meeting_approval_status']);
        }
        if (isset($param['search']['meeting_status'])) {
            if ($param['search']['meeting_status'][0] == 4) {
                        $query = $query->where('meeting_end_time', '>', $time)
                        ->where('meeting_begin_time', '<=', $time)
                        ->whereRaw('meeting_apply.meeting_status in (2,4)');
                unset($param['search']['meeting_status']);
            }
        }
        if (isset($param['search']['meeting_status'])) {
            if ($param['search']['meeting_status'][0] == 5) {
                        $query = $query->where('meeting_end_time', '<=', $time)
                        ->whereRaw('meeting_apply.meeting_status in (2,4,5)');
                unset($param['search']['meeting_status']);
            }
        }
        $query = $query->wheres($param['search'])
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
        private function filterUserId($userId)
        {
            static $userCaches = [];
            if (isset($userCaches[$userId])) {
                return $userCaches[$userId];
            }
            if ($userId === 'admin') {
                return $userCaches[$userId] = $userId;
            }
            $len = strlen($userId);
            if ($len !== 10) {
                return $userCaches[$userId] = '';
            }
            $isUser = true;
            for ($i = 0; $i < $len; $i++) {
                $code = $userId[$i];
                if ($i === 0) {
                    if ($code !== 'W') {
                        $isUser = false;
                        break;
                    }
                } else if ($i === 1) {
                    if ($code !== 'V') {
                        $isUser = false;
                        break;
                    }
                } else {
                    if (!is_numeric($code)) {
                        $isUser = false;
                    }
                }
            }
            if ($isUser) {
                return $userCaches[$userId] = $userId;
            }
            return $userCaches[$userId] = '';
        }
	/**
	 * @获取审批会议数量
	 * @param type $param
	 * @return 申请数量 | int
	 */
	public function getApproveMeetingCount($param)
	{
        $param["page"]   = 0;
        $param["returntype"] = "count";
        return $this->getApproveMeetingList($param);
	}

	/**
	 * @获取某个会议室的所有会议
	 * @param type $param
	 * @return 会议申请列表 | array
	 */
	public function getMeetingListByRoomId($meetingRoomId)
	{
        return $this->entity->select()->leftJoin('meeting_rooms', function($join) {
                    $join->on("meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id');
                })->leftJoin('user', function($join) {
                    $join->on("meeting_apply.meeting_apply_user", '=', 'user.user_id');
                })
                ->where("meeting_room_id", $meetingRoomId)
				->get()
				->toArray();
	}

	/**
	 * @获取某个会议室所有会议数量
	 * @param type $param
	 * @return 申请数量 | int
	 */
	public function getMeetingCountByRoomId($param)
	{
        return $this->entity->where("meeting_room_id", $param)->get()->count();
	}
    /**
     * @获取某个会议室所有会议
     * @param type $param
     * @return 申请数量 | int
     */
    public function getMeetingIdsByRoomId($param)
    {
        return $this->entity->select(['meeting_apply_id'])->where("meeting_room_id", $param)->where('meeting_status', '!=', 8)->get()->toArray();
    }

	/**
	 * @新建会议申请
	 * @param type $data
	 * @return 会议Id | int
	 */
	public function addMeeting($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * @编辑会议申请
	 * @param type $data
	 * @param type $mApplyId
	 * @return boolean
	 */
	public function editMeeting($data, $mApplyId)
	{
		return $this->entity->where($this->primaryKey, $mApplyId)->update($data);
	}
	/**
	 * @删除会议申请
	 * @param type $mApplyId
	 * @return boolean
	 */
	public function deleteMeeting($mApplyId)
	{
		return $this->entity->destroy($mApplyId);
	}
	/**
	 * @获取会议详情
	 * @param type $mApplyId
	 * @return 会议详情 | object
	 */
	public function showMeeting($mApplyId, $from = '')
    {
        $query = $this->entity
        ->select('meeting_apply.*', 'meeting_rooms.*', 'meeting_sort.*')
        ->leftJoin('meeting_rooms', 'meeting_apply.meeting_room_id', '=', 'meeting_rooms.room_id')
        ->leftJoin('meeting_sort', 'meeting_rooms.meeting_sort_id', '=', 'meeting_sort.meeting_sort_id')
        ->where($this->primaryKey, $mApplyId)->whereIn('meeting_status', [1,2,3,4,5]);
        if ($from && $from == 'approval-delete') {
            $query = $query->whereIn('false_delete',  [0,2]);
        } else if ($from && $from = "mine-delete") {
            $query = $query->whereIn('false_delete',  [0,1]);
        }  else if ($from && $from == 'approval') {
            $query = $query->whereIn('false_delete',  [0,1]);
        }
        return $query->first();
    }
	public function showAttenceUser($mApplyId) {
        $default = [
            'fields' => ['meeting_attendance.meeting_attence_id','meeting_attendance.meeting_attence_user','meeting_attendance.meeting_attence_status','meeting_attendance.meeting_attence_remark','meeting_attendance.meeting_refuse_remark','meeting_attendance.meeting_apply_id as attence_meeting_apply_id','meeting_attendance.meeting_sign_type','meeting_attendance.meeting_attence_time','meeting_attendance.meeting_refuse_time','meeting_attendance.meeting_sign_time'
            ],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];
        return $data = $this->entity->select($default['fields'])->leftJoin('meeting_attendance', 'meeting_attendance.meeting_apply_id', "=", $this->primaryKey)->where($this->primaryKey, $mApplyId)->where("meeting_attendance.meeting_attence_status","=",1)->get()->toArray();
    }

    public function showRefuseUser($mApplyId) {
         $default = [
            'fields' => ['meeting_attendance.meeting_attence_id','meeting_attendance.meeting_attence_user'
            ],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];
        return $data = $this->entity->select($default['fields'])->leftJoin('meeting_attendance', 'meeting_attendance.meeting_apply_id', "=", $this->primaryKey)->where($this->primaryKey, $mApplyId)->where("meeting_attendance.meeting_attence_status","=",0)->get()->toArray();
    }
    public function getMeetingAttenceStatus($mApplyId,$userId) {
         $default = [
            'fields' => ['meeting_attendance.meeting_attence_status','meeting_attendance.meeting_sign_status','meeting_attendance.meeting_attence_id','meeting_attendance.meeting_sign_time'
            ],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];
        return $this->entity->select($default['fields'])->leftJoin('meeting_attendance', 'meeting_attendance.meeting_apply_id', "=", $this->primaryKey)->where($this->primaryKey, $mApplyId)->where("meeting_attendance.meeting_attence_user","=",$userId)->get()->toArray();
    }

	/**
	 * @获取冲突会议列表
	 * @param type $search
	 * @return 冲突会议列表 | array
	 */
	public function getConflictMeeting($search)
	{
		return $this->entity
			->select(['meeting_apply_id','conflict'])
			->wheres($search)
			->get();
	}

    /**
     * 设置待审核申请的审批查看时间
     * @param  $meeting_apply_id
     * @return boolean
     */
    public function setMeetingApply($meeting_apply_id) {
        return $this->entity->where("meeting_apply_id", $meeting_apply_id)
                            ->update(['meeting_apply_time' => date("Y-m_d H:i:s", time())]);
    }

    /**
     * 新建会议申请前判断申请时间段是否与现有会议有冲突
     * @param array
     * @return boolean
     */
    public function getNewMeetingDateWhetherConflict($param) {
        if(empty($param['startDate']) || empty($param['endDate']) || empty($param['meetingRoomId'])) {
            return '0';
        }
    	$search = array();
    	$search['meeting_begin_time'] = [$param['endDate'], '<'];
    	$search['meeting_end_time']   = [$param['startDate'], '>'];
    	$search['meeting_room_id']    = [$param['meetingRoomId']];
        $search['meeting_status']     = [[1,2,4], 'in'];
    	$search['meeting_apply_id']     = [$param['applyId'], '!='];
    	$result = $this->entity->wheres($search)->where('false_delete', '!=', 1)->get()->toArray();
    	if($result) {
    		return '1';
    	}else{
    		return '0';
    	}
    }

    /**
     * 获取日期时间范围内所有的会议列表
     * @param array
     * @return array
     */
    public function getAllMeetingList($param) {
        $default = [
            'fields' => ['meeting_apply.*', 'meeting_rooms.room_name', 'user.user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_begin_time' => 'asc', 'meeting_apply.meeting_end_time' => 'asc'],
            'returntype' => 'array',
            'limit' =>  10
        ];
    	$param = array_merge($default, array_filter($param));
        $time = date("Y-m-d H:i:s", time());
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";

    	if(isset($param['start']) && isset($param['end'])) {
	    	$param['search']['meeting_begin_time'] = [$param['end'], '<'];
	    	$param['search']['meeting_end_time']   = [$param['start'], '>'];
    	}
        if(!empty($param['room_name'])) {
            $param['search']['meeting_rooms.room_name'] = [$param['room_name']];
        }
        // 不包含已拒绝和已结束的
        $param['search']['meeting_status'] = [[1, 2,4], 'in'];
        $query = $this->entity->select($param['fields'])
							->leftJoin('meeting_rooms', "meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id')
							->leftJoin('user', "meeting_apply.meeting_apply_user", '=', 'user.user_id')
                            ->leftjoin("meeting_sort", "meeting_sort.meeting_sort_id", "=", "meeting_rooms.meeting_sort_id");
        $roleId = explode(',', $roleId);
        $query = $query->where(function($query) use($userId, $deptId, $roleId) {
            $query = $query->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_user) or meeting_sort.member_user = 'all'",[$userId])
                ->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_dept) or meeting_sort.member_dept = 'all'",[$deptId]);
            if(!empty($roleId)){
                foreach($roleId as $v){
                    $query->orWhereRaw("find_in_set(?,meeting_sort.member_role) or meeting_sort.member_role = 'all'",[$v]);
                }
            }
        });
        $query = $query->where(function($query) use($param, $time) {
            $query = $query->whereIn('meeting_status', [2,4])
                  ->where('meeting_end_time', '>', $time)
                  ->where('meeting_begin_time', '<', $param['end']);
            $query = $query->orWhere(function($query) use($param) {
                    $query->where('meeting_status', '=', 1)
                          ->where('meeting_begin_time', '<', $param['end'])
                          ->where('meeting_end_time', '>', $param['start']);
            });
        });
        if (isset($param['platform']) && $param['platform'] == 'mobile') {
            $query = $query->orWhere('meeting_room_id', 0);
        }
        $query = $query->orders($param['order_by'])
                ->wheres($param['search'])
				->parsePage($param['page'], $param['limit']);
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->get()->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    public function getAllMeetingListCount($param) {
        $default = [
            'fields' => ['meeting_apply.*', 'meeting_rooms.room_name', 'user.user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_begin_time' => 'asc', 'meeting_apply.meeting_end_time' => 'asc'],
            'returntype' => 'count',
            'limit' =>  10
        ];
        $param = array_merge($default, array_filter($param));
        $time = date("Y-m-d H:i:s", time());
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";

        if(isset($param['start']) && isset($param['end'])) {
            $param['search']['meeting_begin_time'] = [$param['end'], '<'];
            $param['search']['meeting_end_time']   = [$param['start'], '>'];
        }
        if(!empty($param['room_name'])) {
            $param['search']['meeting_rooms.room_name'] = [$param['room_name']];
        }
        // 不包含已拒绝和已结束的
        // $param['search']['meeting_status'] = [[2,4], 'in'];
        $query = $this->entity->select($param['fields'])
                            ->leftJoin('meeting_rooms', "meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id')
                            ->leftJoin('user', "meeting_apply.meeting_apply_user", '=', 'user.user_id')
                            ->leftjoin("meeting_sort", "meeting_sort.meeting_sort_id", "=", "meeting_rooms.meeting_sort_id");
        $roleId = explode(',', $roleId);

        $query = $query->where(function($query) use($userId, $deptId, $roleId) {
            $query = $query->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_user) or meeting_sort.member_user = 'all'",[$userId])
                ->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_dept) or meeting_sort.member_dept = 'all'",[$deptId]);
            if(!empty($roleId)){
                foreach($roleId as $v){
                    $query->orWhereRaw("find_in_set(?,meeting_sort.member_role) or meeting_sort.member_role = 'all'",[$v]);
                }
            }
        });

        $query = $query->where(function($query) use($param, $time) {
            $query = $query->whereIn('meeting_status', [2,4])
                  ->where('meeting_end_time', '>', $time)
                  ->where('meeting_begin_time', '<', $param['end']);
            $query = $query->orWhere(function($query) use($param) {
                    $query->where('meeting_status', '=', 1)
                          ->where('meeting_begin_time', '<', $param['end'])
                          ->where('meeting_end_time', '>', $param['start']);
            });
        });

        $query = $query->wheres($param['search'])
        ->orders($param['order_by']);

        return $query->get()->count();
    }

    /**
     * @获取单条会议
     * @param type $param 会议id
     * @return  | array
     */
    public function getMeetingOne($mApplyId)
    {
        $query = $this->entity;
        $query = $query->where('meeting_apply_id','=',$mApplyId);

        return $query->get()->toArray();
    }
    /**
     * 获取即将开始的会议列表
     *
     * @param
     *
     * @return array 即将开始的会议列表
     *
     */
    public function listBeginMeeting($begin,$end)
    {
        $param['fields']    = isset($param['fields']) ? $param['fields'] : ['*'];
        $param['order_by']  = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        $query = $this->entity
            ->select($param['fields'])
            ->where('meeting_status', '=', 2)
            ->whereBetween('meeting_reminder_time', [$begin,$end])
            ->where('meeting_end_time', '>=' ,date('Y-m-d').' 00:00:00');
        return $query->orders($param['order_by'])
                ->get()->toArray();
    }
    public function deleteMeetingBySortId($subjectId) {
        return $this->entity->wheres('meeting_sort_id', $subjectId)->delete();
    }
    function showNotAttenceUser($mApplyId) {
        $default = [
            'fields' => ['meeting_attendance.meeting_attence_id','meeting_attendance.meeting_attence_user','meeting_attendance.meeting_attence_status','meeting_attendance.meeting_attence_remark','meeting_attendance.meeting_refuse_remark','meeting_attendance.meeting_apply_id as attence_meeting_apply_id','meeting_attendance.meeting_sign_type','meeting_attendance.meeting_attence_time','meeting_attendance.meeting_refuse_time','meeting_attendance.meeting_sign_time'
            ],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];
        return $data = $this->entity->select($default['fields'])->leftJoin('meeting_attendance', 'meeting_attendance.meeting_apply_id', "=", $this->primaryKey)->where($this->primaryKey, $mApplyId)->where("meeting_attendance.meeting_attence_status","=",0)->get()->toArray();
    }
    function getMeetingJoinMember($mApplyId) {
        return $this->entity
                    ->select('meeting_join_member')
                    ->where("meeting_apply_id", $mApplyId)
                    ->get()
                    ->toArray();
    }
    function getMeetingCalendarList($userId, $param) {
        $endTime = isset($param['calendar_end']) ? $param['calendar_end'] : '';
        $beginTime = isset($param['calendar_begin']) ? $param['calendar_begin'] : '';
        $search['meeting_begin_time'] = [$endTime, '<'];
        $search['meeting_end_time']   = [$beginTime, '>'];
        $search['meeting_status']     = [[2, 4], 'in'];
        $calendar_day = isset($param['calendar_day']) ? $param['calendar_day'] : '';
        $query = $this->entity;
        $query = $query->select(['*'])
                ->where(function ($query) use ($userId) {
                   $query->whereRaw("find_in_set(?,meeting_apply.meeting_join_member) or meeting_join_member = 'all'",[$userId])
                        ->orWhere('meeting_apply.meeting_apply_user', $userId);
                })
                ->leftJoin('meeting_rooms', function($join) {
                    $join->on("meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id');
                })->leftJoin('meeting_attendance', function($join) use ($userId) {
                    $join->on('meeting_apply.meeting_apply_id', "=","meeting_attendance.meeting_apply_id");
                })
                ->where('meeting_attendance.meeting_attence_user', "=", $userId)->where('meeting_attendance.meeting_attence_status', "=", 1)
                ->where('meeting_apply.false_delete', '!=', 1);
        if ($calendar_day) {
            $this->temp = date("Y-m-d", strtotime($calendar_day));
            $query = $query->where(function ($query) {
                $query->whereRaw("date_format(meeting_end_time, '%Y-%m-%d') = ?" ,['0000-00-00'])->whereRaw("date_format(`meeting_begin_time`, '%Y-%m-%d') <= ?", [$this->temp])
                    ->orWhere(function ($query) {
                        $query->whereRaw("date_format(meeting_end_time, '%Y-%m-%d')>=?", [$this->temp])->whereRaw("date_format(`meeting_begin_time`, '%Y-%m-%d')<= ?", [$this->temp])
                        ->whereIn('meeting_status', [2, 4]);
                    });
            });
        }
        if ($beginTime && $endTime) {
            $query = $query->wheres($search);
        }
        $data = $query->get()->toArray();

        return $data;
    }
    /**
     * @判断某一天有无日程
     * @param  string  $date 日期 2019-09
     *
     * @param  string  $userId 用户id
     * @return boolean
     */
    public function getMeetingMonthHasDate($date,$userId)
    {

        $day_start_end =[ date("Y-m-d 00:00:00",strtotime($date)),date("Y-m-d 23:59:59",strtotime($date))];
        $query = $this->entity;
        $query = $query->select(['*']);
        $query = $query->where("meeting_end_time",">=",$day_start_end[0])
        ->where("meeting_begin_time","<=",$day_start_end[1]);

        $query = $query->where(function($query) use($userId){
            $query = $query->whereRaw('find_in_set(?,meeting_join_member) or meeting_join_member = "all"',[$userId]);
        });
        return $query->get()->toArray();
    }
    /**
     * @获取门户审批会议列表
     * @param type $param
     * @return 会议申请列表 | array
     */
    public function getPortalApproveMeetingList($date, $param)
    {
        $default = [
            'fields' => ['meeting_apply.*', 'meeting_rooms.*','user.user_name','user.user_id', 'meeting_sort.meeting_approvel_user'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];

        //排除已假删除的(已拒绝的会议+结束的会议)
        $param['search']['false_delete'] = ['2','!='];
        $time = date("Y-m-d H:i:s", time());
        $param = array_merge($default, array_filter($param));
        $day_start_end =[ date("Y-m-d 00:00:00",strtotime($date)),date("Y-m-d 23:59:59",strtotime($date))];
        // 验证权限的时候，用户id必填！
        $userId     = isset($param["user_id"]) ? $param["user_id"]:"";
        if($userId == "") {
            return $this->entity;
        }
        $roleId        = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId        = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $query = $this->entity->select($param['fields'])->leftJoin('meeting_rooms', function($join) {
                    $join->on("meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id');
                })->leftJoin('user', function($join) {
                    $join->on("user.user_id", '=', 'meeting_apply.meeting_apply_user');
                })->leftjoin('meeting_sort', function($join) {
                    $join->on("meeting_sort.meeting_sort_id", "=", "meeting_rooms.meeting_sort_id");
                })->whereRaw("(FIND_IN_SET(?, meeting_approvel_user) or meeting_sort.meeting_approvel_user = 'all')  and meeting_apply.meeting_status in (1) ",[$userId]);
        $query = $query->where("meeting_end_time",">=",$day_start_end[0])
            ->where("meeting_begin_time","<=",$day_start_end[1]);
        $query = $query->wheres($param['search'])
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

    // 获取门户我参加的会议
    public function getPortalJoinMeetingList($date, $param) {
        $default = [
            'fields' => ['meeting_apply.*', 'meeting_rooms.*', 'user.user_name', 'meeting_attendance.meeting_attence_id',
            'meeting_attendance.meeting_attence_user','meeting_attendance.meeting_attence_status','meeting_attendance.meeting_attence_remark','meeting_attendance.meeting_refuse_remark','meeting_attendance.meeting_apply_id as attence_meeting_apply_id','meeting_attendance.meeting_sign_type','meeting_attendance.meeting_attence_time','meeting_attendance.meeting_refuse_time','meeting_attendance.meeting_sign_time'

            ],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['meeting_apply.meeting_apply_id' => 'desc'],
            'returntype' => 'array',
        ];
        //排除已假删除的(已拒绝的会议+结束的会议)
        
        $day_start_end = [ date("Y-m-d 00:00:00",strtotime($date)),date("Y-m-d 23:59:59",strtotime($date))];
        $time = date("Y-m-d H:i:s", time());
        $param['search']['false_delete'] = ['1','!='];
        $param['search']['meeting_attence_status'] = ['1','='];
        $param['meeting_apply_user'] = isset($param['user_id']) ? $param['user_id'] : '';
        $param = array_merge($default, array_filter($param));
        $query = $this->entity->select($param['fields'])->leftJoin('meeting_rooms', function($join) {
                    $join->on("meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id');
                })->where(function ($query) use ($param) {
                    $query->WhereRaw("(FIND_IN_SET(?, meeting_apply.meeting_apply_user)) and meeting_apply.meeting_status in (1,2,3,4,5)",[$param['meeting_apply_user']])
                    ->orwhere(function ($query) use ($param) {
                        $query->orWhereRaw("(FIND_IN_SET(?, meeting_apply.meeting_sign_user)) and meeting_apply.meeting_status in (2,4,5)",[$param['meeting_apply_user']])
                              ->orWhereRaw("(FIND_IN_SET(?, meeting_join_member) or meeting_apply.meeting_join_member = 'all') and meeting_apply.meeting_status in (2,4,5)",[$param['meeting_apply_user']]);
                    });
                })->leftJoin('user', function($join) {
                    $join->on("user.user_id", '=', 'meeting_apply.meeting_apply_user');
                })->leftJoin('meeting_attendance', function($join) use ($param) {
                    $join->on('meeting_apply.meeting_apply_id', "=","meeting_attendance.meeting_apply_id")->where('meeting_attendance.meeting_attence_user', "=", $param['user_id']);
                });

        $query = $query->where("meeting_end_time",">=",$day_start_end[0])
            ->where("meeting_begin_time","<=",$day_start_end[1]);
        $query =  $query->wheres($param['search'])
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

    // 判断是否有查看权限
    public function checkViewPermission($mApplyId, $userId) {
        $fields = ['meeting_apply.*', 'meeting_rooms.*', 'meeting_sort.*'];
        $query = $this->entity->leftJoin('meeting_rooms', 'meeting_apply.meeting_room_id', '=', 'meeting_rooms.room_id')
        ->leftJoin('meeting_sort', 'meeting_rooms.meeting_sort_id', '=', 'meeting_sort.meeting_sort_id');
        $query = $query->select($fields)->where(function ($query) use ($userId) {
                    $query->where('meeting_apply.meeting_apply_user', $userId)
                          ->orWhere('meeting_apply.meeting_sign_user', $userId)
                          ->orWhereRaw("(FIND_IN_SET(?, meeting_join_member) or meeting_apply.meeting_join_member = 'all')",[$userId])
                          ->orWhereRaw("(FIND_IN_SET(?, meeting_sort.meeting_approvel_user) or meeting_sort.meeting_approvel_user = 'all')",[$userId]);
                });
        return $query->where('false_delete', '!=', 1)->where('meeting_apply_id', $mApplyId)->get()->toArray();
    }

    // 判断是否有查看权限
    public function checkCancelViewPermission($mApplyId, $userId) {
        $fields = ['meeting_apply.*', 'meeting_rooms.*'];
        $query = $this->entity->select($fields)->leftJoin('meeting_rooms', function($join) {
                    $join->on("meeting_rooms.room_id", '=', 'meeting_apply.meeting_room_id');
                })->where(function ($query) use ($userId) {
                    $query->where('meeting_apply.meeting_apply_user', $userId)
                          ->orWhere('meeting_apply.meeting_sign_user', $userId)
                          ->orWhereRaw("(FIND_IN_SET(?, meeting_join_member) or meeting_apply.meeting_join_member = 'all')",[$userId]);
                });
        return $query->where('meeting_status', 8)->where('meeting_apply_id', $mApplyId)->get()->toArray();
    }
    // 判断是否有审批权限
    public function checkApprovePermission($mApplyId, $userId) {
        $fields = ['meeting_apply.*'];
        $query = $this->entity->select($fields);
        return $query->where('false_delete', '!=', 2)->where('meeting_apply_id', $mApplyId)->get()->toArray();
    }
    public function showCancelMeeting($mApplyId, $from = '')
    {
        $query = $this->entity
        ->select('meeting_apply.*', 'meeting_rooms.*', 'meeting_sort.*')
        ->leftJoin('meeting_rooms', 'meeting_apply.meeting_room_id', '=', 'meeting_rooms.room_id')
        ->leftJoin('meeting_sort', 'meeting_rooms.meeting_sort_id', '=', 'meeting_sort.meeting_sort_id')
        ->where($this->primaryKey, $mApplyId)->whereIn('meeting_status', [8]);
        return $query->first();
    }

    public function getAllEndMeeting($time)
    {
        $query = $this->entity
        ->select('meeting_apply.*')
        ->where('meeting_end_time', '<', $time)->whereIn('meeting_status', [2,4]);
        return $query->get()->toArray();
    }
    public function getOccupationMeeting($param)
    {
        $fields = [
            'meeting_apply_user',
            'meeting_apply_id',
            'meeting_begin_time',
            'meeting_end_time',
            'meeting_subject',
            'meeting_status'
        ];
        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : ['meeting_begin_time' => 'desc'];
        $query = $this->entity
        ->select($fields)->whereIn('meeting_apply_id', $param['conflictId'])->where('meeting_status', '!=', 8);
        return $query->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get()->toArray();
    }
    public function getOccupationMeetingTotal($param)
    {
        $query = $this->entity->whereIn('meeting_apply_id', $param['conflictId'])->where('meeting_status', '!=', 8);
        return $query->count();
    }
    public function getRoomsRecordData($roomId)
    {
        return $this->entity->where('meeting_room_id', $roomId)->where('meeting_status', '!=', 8)->get()->toArray();
    }
}
