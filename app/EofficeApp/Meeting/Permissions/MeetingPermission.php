<?php
namespace App\EofficeApp\Meeting\Permissions;

use DB;

class MeetingPermission
{
    private $meetingApplyRepository;
    private $meetingRecordsRepository;
    private $meetingRoomsRepository;
    private $meetingAttenceRepository;
    private $userService;
    public function __construct() {
        $this->meetingApplyRepository = "App\EofficeApp\Meeting\Repositories\MeetingApplyRepository";
        $this->meetingRecordsRepository = "App\EofficeApp\Meeting\Repositories\MeetingRecordsRepository";
        $this->meetingRoomsRepository = "App\EofficeApp\Meeting\Repositories\MeetingRoomsRepository";
        $this->meetingAttenceRepository = "App\EofficeApp\Meeting\Repositories\MeetingAttenceRepository";
        $this->meetingService = "App\EofficeApp\Meeting\Services\MeetingService";
        $this->userService = 'App\EofficeApp\User\Services\UserService';
    }

    public function addMeetingRecord($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($data['meeting_apply_id']) || empty($data['meeting_apply_id'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $result = $this->getMeetingDetail($currentUserId, $data['meeting_apply_id']);
        $detail = app($this->meetingApplyRepository)->getDetail($data['meeting_apply_id']);
        $status = $detail->meeting_status;
        if (!$result) {
            return false;
        }
        if ($status == 1 || $status == 3) {
            return false;
        }
        return true;
    }

    public function deleteMeetingRecord($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['recordId']) || empty($urlData['recordId'])) {
            return ['code' => ['0x017020', 'meeting']];
        }
        $detailRecord = app($this->meetingRecordsRepository)->getDetail($urlData['recordId']);
        if (!$detailRecord) {
            return ['code' => ['0x017037', 'meeting']];
        }
        $revertTime = $detailRecord->record_time;
        $applyId = $detailRecord->meeting_apply_id;
        $detail = app($this->meetingApplyRepository)->getDetail($applyId);
        // $currentTime = date('Y-m-d H:i:s', time());
        // $lastTime = date("Y-m-d H:i:s", (strtotime($revertTime)+600));
        $applyUser = $detail->meeting_apply_user;
        $joinMember = explode(',', $detail->meeting_join_member);
        $record_creator = $detailRecord->record_creator;
        // if ($lastTime <= $currentTime) {
        //     return false;
        // }
        if ($currentUserId == $record_creator) {
            return true;
        }
        return false;
    }

    public function editMeetingRecord($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($data['meeting_apply_id']) || empty($data['meeting_apply_id'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $result = $this->getMeetingDetail($currentUserId, $data['meeting_apply_id']);
        $detail = app($this->meetingApplyRepository)->getDetail($data['meeting_apply_id']);
        $status = $detail->meeting_status;
        if (!$result) {
            return false;
        }
        if ($status == 1 || $status == 3) {
            return false;
        }
        return true;
    }

    public function getMeetingRecordDetailById($own, $data, $urlData) {
        return $this->deleteMeetingRecord($own, $data, $urlData);
    }

    private function getMeetingDetail($currentUserId, $ApplyId) {
        $detail = app($this->meetingApplyRepository)->getDetail($ApplyId);
        $applyUser = $detail->meeting_apply_user;
        $joinMember = [];
        if ($detail->meeting_join_member == 'all') {
            $joinMember = explode(',', app($this->userService)->getAllUserIdString([]));
        }else{
            $joinMember = explode(',', $detail->meeting_join_member);
        }
        $signUser = $detail->meeting_sign_user;
        $appvalUser = $detail->meeting_approval_user;
        if ($currentUserId == $applyUser || in_array($currentUserId, $joinMember) || $currentUserId == $signUser || $currentUserId == $appvalUser) {
            return true;
        }
        return false;
    }

    // 编辑会议
    public function editMeeting($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }

        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $signUser = $detail->meeting_sign_user;
        $applyUser = $detail->meeting_apply_user;
        if ( ($applyUser != $data['meeting_apply_user']) && $data['meeting_apply_user'] != $signUser) {
            return false;
        }

        $meeting_apply_time = $detail->meeting_apply_time;
        $status = $detail->meeting_status;
        $roomId = $data['meeting_room_id'];
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        // 获取有权限的会议室Id
        $roomIdList = app($this->meetingRoomsRepository)->listRoom($param);
        $roomIdArr = array_column($roomIdList, 'room_id');
        if (isset($data->attence_type) && $data->attence_type == 1) {
            if (($currentUserId == $applyUser || $currentUserId == $signUser) && ($meeting_apply_time === null && $status == 1) && in_array($roomId, $roomIdArr)) {
                return true;
            }
        } else {
           if (($currentUserId == $applyUser || $currentUserId == $signUser) && ($status == 1 || $status == 2)) {
                return true;
            } 
        }
        
        return false;
    }
    // 审批会议公共方式
    public function approveMyMeeting($own, $data, $urlData) {
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $approvalList = app($this->meetingApplyRepository)->getApproveMeetingList($param);
        $approvalListId = array_column($approvalList, 'meeting_apply_id');
        if (in_array($urlData['mApplyId'], $approvalListId)) {
            return true;
        }
        return false;
    }
    // 审批会议
    public function approveMeeting($own, $data, $urlData) {
        $result = $this->approveMyMeeting($own, $data, $urlData);
        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if ($result && $detail && $detail->meeting_status == 1) {
            return true;
        }
        return false;
    }
    // 我的会议
    public function myMeeting($own, $data, $urlData) {
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $param['meeting_apply_user'] = $own['user_id'];
        $approvalList = app($this->meetingApplyRepository)->getMyMeetingList($param);
        $approvalListId = array_column($approvalList, 'meeting_apply_id');
        if (in_array($urlData['mApplyId'], $approvalListId)) {
            return true;
        }
        return false;
    }

    // 拒绝会议
    public function refuseMeeting($own, $data, $urlData) {
        $result = $this->approveMyMeeting($own, $data, $urlData);
        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        if ($result && $detail && $detail->meeting_status == 1) {
            return true;
        }
        return false;
    }
    // 开始会议
    public function startMeeting($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        $time = date('Y-m-d H:i:s');
        $result = $this->myMeeting($own, $data, $urlData);
        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $joinMember = [];
        if ($detail->meeting_join_member == 'all') {
            $joinMember = explode(',', app($this->userService)->getAllUserIdString([]));
        }else{
            $joinMember = explode(',', $detail->meeting_join_member);
        }
        if ($result && $detail && ($detail->meeting_status == 2 && $time < $detail->meeting_begin_time) && ($currentUserId == $detail->meeting_sign_user || in_array($currentUserId, $joinMember) || $currentUserId == $detail->meeting_apply_user)) {
            return true;
        }
        return false;
    }
    // 结束会议
    public function endMeeting($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        $time = date('Y-m-d H:i:s');
        $result = $this->myMeeting($own, $data, $urlData);
        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $joinMember = [];
        if ($detail->meeting_join_member == 'all') {
            $joinMember = explode(',', app($this->userService)->getAllUserIdString([]));
        }else{
            $joinMember = explode(',', $detail->meeting_join_member);
        }
        if ($result && $detail && (($detail->meeting_status == 2 && $time > $detail->meeting_begin_time) || $detail->meeting_status == 4) && ($currentUserId == $detail->meeting_sign_user || in_array($currentUserId, $joinMember) || $currentUserId == $detail->meeting_apply_user)) {
            return true;
        }
        return false;
    }
    //参加会议
    public function attenceMeeting($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $joinMember = [];
        if ($detail->meeting_join_member == 'all') {
            $joinMember = explode(',', app($this->userService)->getAllUserIdString([]));
        }else{
            $joinMember = explode(',', $detail->meeting_join_member);
        }
        $applyUser = $detail->meeting_apply_user;
        $signUser = $detail->meeting_sign_user;
        $joinMembers = array_merge($joinMember, (array)$applyUser);
        $joinMemberArr = array_merge($joinMembers, (array)$signUser);
        $status = $detail->meeting_status;
        $response = $detail->meeting_response;
        $time = date('Y-m-d H:i:s', time());
        $exist = DB::table('meeting_attendance')
                ->where('meeting_attence_user', $own['user_id'])
                ->where('meeting_apply_id', $urlData['mApplyId'])
                ->exists();
        if ($exist) {
           return false;
        }
        if (in_array($currentUserId, $joinMemberArr) && (($status==2 || $status == 4) && $detail->meeting_end_time > $time) && $response) {
            return true;
        }
        return false;
    }
    // 拒绝参加会议
    public function refuseAttenceMeeting($own, $data, $urlData) {
        $result = $this->attenceMeeting($own, $data, $urlData);
        $exist = DB::table('meeting_attendance')
                ->where('meeting_attence_user', $own['user_id'])
                ->where('meeting_apply_id', $urlData['mApplyId'])
                ->exists();
        if ($result && !$exist) {
            return true;
        }
        return false;
    }
    // 会议签到
    public function signMeeting($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $detail = app($this->meetingAttenceRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $applyId = $detail->meeting_apply_id ?? 0;
        $meetingDetail = app($this->meetingApplyRepository)->getDetail($applyId);
        if (!$meetingDetail) {
            return false;
        }
        $signType = $meetingDetail->sign_type;
        $status = $meetingDetail->meeting_status;
        $signUser = $meetingDetail->meeting_sign_user;
        $applyUser = $meetingDetail->meeting_apply_user;
        $users = [];

        if ($status != 2 && $status !=4) {
            return false;
        }
        if ($signType == 1 && $currentUserId != $signUser && $currentUserId != $applyUser) {
            return false;
        }
        $attence = DB::table('meeting_attendance')
                            ->where('meeting_attence_id', $urlData['mApplyId'])
                            ->where('meeting_attence_status', 1)
                            ->whereNotNull('meeting_sign_type')
                            ->get()->toArray();
        if (!empty($attence)) {
            return true;
        }
        return false;
    }
    // 删除会议

    public function deleteOwnMeeting ($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if ($urlData['mApplyId'] == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $mApplyIdArray = explode(',', $urlData['mApplyId']);
        $result = [];
        $time = date("Y-m-d H:i:s", time());
        foreach ($mApplyIdArray as $key => $value) {
            $detail = app($this->meetingApplyRepository)->getDetail($value);
            $joinMember = $detail->meeting_join_member;
            $status = $detail->meeting_status;
            $approval_user = $detail->meeting_approval_user;
            $sign_user = $detail->meeting_sign_user;
            $apply_time = $detail->meeting_apply_time;
            $applyUser = $detail->meeting_apply_user;
            $begin = $detail->meeting_begin_time;
            $end = $detail->meeting_end_time;
            $approval = false;
            $arr = false;
            $joinMemberArr = [];
            if ($joinMember != 'all') {
                $joinMemberArr = explode(',', $joinMember);
            }
            
            if ($joinMemberArr != 'all' && in_array($approval_user, $joinMemberArr) && $currentUserId == $approval_user) {
                $approval = true;
            }
            // 审批人是签到人得时候
            $signUser = false;
            if ($currentUserId == $approval_user && $approval_user == $sign_user) {
                $signUser = true;
            }
            if (($status == 3 || $status == 5 || (($status == 2 || $status == 4) && $time>$end)) || ($status == 1 && $apply_time === null && ($currentUserId==$applyUser)) || ($status == 2 && $begin > $time && $currentUserId==$applyUser)) {
                $result = [];
            }else{
                $result[] = $value;
            }

        }
        if (count($result)) {
            return false;
        }
        return true;
    }
    // 删除审批会议
    public function deleteApproveMeeting($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if ($urlData['mApplyId'] == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $mApplyIdArray = explode(',', $urlData['mApplyId']);
        $allow = [];
        $notallow = [];
        foreach ($mApplyIdArray as $key => $value) {
            $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
            $status = $detail->meeting_status;
            $approval_user = $detail->meeting_approval_user;
            if (($status != 1) && ($currentUserId == 'admin') || $currentUserId == $approval_user) {
                $allow[] = $value;
            }else{
                $notallow[] = $value;
            }
        }
        if (count($notallow) > 0) {
            return false;
        }
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $approvalList = app($this->meetingApplyRepository)->getApproveMeetingList($param);
        $approvalListId = array_column($approvalList, 'meeting_apply_id');
        $result = array_intersect($mApplyIdArray, $approvalListId);
        if ($result) {
            return true;
        }
        return false;
    }
    //会议审批设置查看时间
    public function setMeetingApply($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $approvalList = app($this->meetingApplyRepository)->getApproveMeetingList($param);
        $approvalListId = array_column($approvalList, 'meeting_apply_id');
        if (in_array($urlData['mApplyId'], $approvalListId)) {
            return true;
        }
        return false;
    }
    // 一键签到
    public function oneKeySignMeeting($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }

        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $status = $detail->meeting_status;
        $time = date('Y-m-d H:i:s', time());

        if (($status != 2 && $status !=4) || (($status == 2 || $status ==4) && $detail->meeting_end_time < $time)) {
            return false;
        }
        $signUser = $detail->meeting_sign_user;
        $applyUser = $detail->meeting_apply_user;
        $signType = $detail->sign_type;
        if (($currentUserId == $signUser || $applyUser == $currentUserId) && $signType == 1) {
            return true;
        }
        return false;
    }
    // 会议室分类
    public function deleteMeetingSort($own, $data, $urlData) {
        if (!isset($urlData['sortId']) || empty($urlData['sortId'])) {
            return ['code' => ['0x017030', 'meeting']];
        }
        $result = DB::table('meeting_rooms')
            ->where('meeting_sort_id', $urlData['sortId'])
            ->get()->toArray();
        if (!empty($result)) {
            return false;
        }
        return true;
    }
    // 外部人员分类
    public function deleteUserType($own, $data, $urlData) {
        if (!isset($urlData['typeId']) || empty($urlData['typeId'])) {
            return ['code' => ['0x017030', 'meeting']];
        }
        if ($urlData['typeId'] == 1) {
            return false;
        }
        $result = DB::table('meeting_external_user')
            ->where('external_user_type', $urlData['typeId'])
            ->get()->toArray();
        if (!empty($result)) {
            return false;
        }
        return true;
    }
    public function addMeeting($own, $data, $urlData) {

        $currentUserId = $own['user_id'];
        if (isset($data['attence_type']) && $data['attence_type'] == 1) {
            if (!isset($data['meeting_room_id']) && empty($data['meeting_room_id'])) {
                return ['code' => ['0x017006', 'meeting']];
            }
            $roomId = $data['meeting_room_id'];
            $param['user_id'] = $own['user_id'];
            $param['dept_id'] = $own['dept_id'];
            $param['role_id'] = implode(',', $own['role_id']);
            // 获取有权限的会议室Id
            $roomIdList = app($this->meetingRoomsRepository)->listRoom($param);
            $roomIdArr = array_column($roomIdList, 'room_id');
            if (in_array($roomId, $roomIdArr) && $currentUserId == $data['meeting_apply_user']) {
                return true;
            }
            return false;
        }
        return true;
    }

    public function getAttenceRemark($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $joinMember = [];
        if ($detail) {
            if ($detail->meeting_join_member == 'all') {
                $joinMember = explode(',', app($this->userService)->getAllUserIdString([]));
            }else{
                $joinMember = explode(',', $detail->meeting_join_member);
            }
            $applyUser = $detail->meeting_apply_user;
            $signUser = $detail->meeting_sign_user;
            $joinMembers = array_merge($joinMember, (array)$applyUser);
            $joinMemberArr = array_merge($joinMembers, (array)$signUser);
            $response = $detail->meeting_response;
            if (!in_array($currentUserId, $joinMemberArr) || $response != 1) {
                return false;
            }
            if ($currentUserId != $data['userId']) {
                return false;
            }
        }
        return true;

    }
    public function getAttenceStatus($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['mApplyId']) || empty($urlData['mApplyId'])) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $detail = app($this->meetingApplyRepository)->getDetail($urlData['mApplyId']);
        if (!$detail) {
            return false;
        }
        $joinMember = [];
        if ($detail) {
            if ($detail->meeting_join_member == 'all') {
                $joinMember = explode(',', app($this->userService)->getAllUserIdString([]));
            }else{
                $joinMember = explode(',', $detail->meeting_join_member);
            }
            $applyUser = $detail->meeting_apply_user;
            $signUser = $detail->meeting_sign_user;
            $joinMembers = array_merge($joinMember, (array)$applyUser);
            $joinMemberArr = array_merge($joinMembers, (array)$signUser);
            $response = $detail->meeting_response;
            if (!in_array($currentUserId, $joinMemberArr) || $response != 1) {
                return false;
            }
            if ($currentUserId != $data['userId']) {
                return false;
            }
        }
        return true;

    }
    // 获取ID
    public function getRoomIdByRoomName($own, $data, $urlData) {
        if (!isset($data['room_name']) && empty($data['room_name'])) {
            return ['code' => ['0x017007', 'meeting']];
        }
        $roomId = app($this->meetingService)->getRoomIdByRoomName($data);
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $roomIdList = app($this->meetingRoomsRepository)->listRoom($param);
        $roomIdLists = array_column($roomIdList, 'room_id');
        if (in_array($roomId, $roomIdLists)) {
            return true;
        }
        return false;
    }
}
