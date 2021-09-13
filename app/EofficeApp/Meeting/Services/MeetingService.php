<?php
namespace App\EofficeApp\Meeting\Services;

use Eoffice;
use App\EofficeApp\Base\BaseService;
use Lang;
use QrCode;
use Queue;
use App\Jobs\EmailJob;
use App\Jobs\syncMeetingJob;
use App\Jobs\syncMeetingExternalJob;
use App\Jobs\syncMeetingExternalSendJob;
use Illuminate\Support\Arr;
/**
 * @会议模块服务类
 *
 * @author 李志军
 */
class MeetingService extends BaseService
{
    public $meetingRoomsRepository ;
    public $meetingApplyRepository ;
    public $meetingRecordsRepository ;
    public $meetingEquipmentRepository ;
    public $userRepository ;
    public $systemComboboxService ;
    public $attachmentService ;
    public $userService ;
    public $calendarService ;
    public $vedioConferenceService ;
    public $userMenuService ;
    public $meetingSortRepository ;
    public $meetingSubjectManageRepository ;
    public $meetingSubjectDepartmentRepository ;
    public $meetingSubjectRoleRepository ;
    public $meetingSubjectUserRepository ;
    public $langService ;
    public $meetingSortMemberDepartmentRepository ;
    public $meetingSortMemberRoleRepository ;
    public $meetingSortMemberUserRepository ;
    public $meetingAttenceRepository ;
    public $meetingSignWifiRepository ;
    public $meetingSignTypeRepository ;
    public $meetingExternalUserRepository ;
    public $meetingExternalTypeRepository ;
    public $meetingExternalUserTypeInfoRepository ;
    public $meetingExternalAttenceRepository ;
    public $meetingExternalRemindRepository ;
    public $meetingSetRepository ;
    public $shortMessageService ;
    public $webmailEmailboxService ;
    public $empowerService ;
    public $meetingSelectField ;
    public function __construct() {
        parent::__construct();
        $this->meetingRoomsRepository                = 'App\EofficeApp\Meeting\Repositories\MeetingRoomsRepository';
        $this->meetingApplyRepository                = 'App\EofficeApp\Meeting\Repositories\MeetingApplyRepository';
        $this->meetingRecordsRepository              = 'App\EofficeApp\Meeting\Repositories\MeetingRecordsRepository';
        $this->meetingEquipmentRepository            = 'App\EofficeApp\Meeting\Repositories\MeetingEquipmentRepository';
        $this->userRepository                        = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->systemComboboxService                 = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->attachmentService                     = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userService                           = 'App\EofficeApp\User\Services\UserService';
        $this->calendarService                       = 'App\EofficeApp\Calendar\Services\CalendarService';
        $this->vedioConferenceService                = 'App\EofficeApp\IntegrationCenter\Services\VideoconferenceService';
        $this->userMenuService                       = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->meetingSortRepository                 = 'App\EofficeApp\Meeting\Repositories\MeetingSortRepository';
        $this->meetingSubjectManageRepository        = 'App\EofficeApp\Meeting\Repositories\MeetingSubjectManageRepository';
        $this->meetingSubjectDepartmentRepository    = 'App\EofficeApp\Meeting\Repositories\MeetingSubjectDepartmentRepository';
        $this->meetingSubjectRoleRepository          = 'App\EofficeApp\Meeting\Repositories\MeetingSubjectRoleRepository';
        $this->meetingSubjectUserRepository          = 'App\EofficeApp\Meeting\Repositories\MeetingSubjectUserRepository';
        $this->langService                           = 'App\EofficeApp\Lang\Services\LangService';
        $this->meetingSortMemberDepartmentRepository = 'App\EofficeApp\Meeting\Repositories\MeetingSortMemberDepartmentRepository';
        $this->meetingSortMemberRoleRepository       = 'App\EofficeApp\Meeting\Repositories\MeetingSortMemberRoleRepository';
        $this->meetingSortMemberUserRepository       = 'App\EofficeApp\Meeting\Repositories\MeetingSortMemberUserRepository';
        $this->meetingAttenceRepository              = 'App\EofficeApp\Meeting\Repositories\MeetingAttenceRepository';
        $this->meetingSignWifiRepository             = 'App\EofficeApp\Meeting\Repositories\MeetingSignWifiRepository';
        $this->meetingSignTypeRepository             = 'App\EofficeApp\Meeting\Repositories\MeetingSignTypeRepository';
        $this->meetingExternalUserRepository         = 'App\EofficeApp\Meeting\Repositories\MeetingExternalUserRepository';
        $this->meetingExternalTypeRepository         = 'App\EofficeApp\Meeting\Repositories\MeetingExternalTypeRepository';
        $this->meetingExternalUserTypeInfoRepository = 'App\EofficeApp\Meeting\Repositories\MeetingExternalUserTypeInfoRepository';
        $this->meetingExternalAttenceRepository      = 'App\EofficeApp\Meeting\Repositories\MeetingExternalAttenceRepository';
        $this->meetingExternalRemindRepository       = 'App\EofficeApp\Meeting\Repositories\MeetingExternalRemindRepository';
        $this->meetingSetRepository = 'App\EofficeApp\Meeting\Repositories\MeetingSetRepository';


        $this->shortMessageService                   = 'App\EofficeApp\System\ShortMessage\Services\ShortMessageService';
        $this->webmailEmailboxService                = 'App\EofficeApp\System\SystemMailbox\Services\WebmailEmailboxService';
        $this->empowerService                        = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->attachmentService                         = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->meetingSelectField                    = [
                                                        'meeting_type'    => 'MEETING_TYPE'
                                                    ];
    }
    /**
     * @获取会议设备列表
     * @param type $params
     * @param type $response
     * @return 会议设备列表 | array
     */
    public function listEquipment($param)
    {
        $param      = $this->parseParams($param);

        $response   = isset($param['response']) ? $param['response'] : 'both';

        if ($response == 'both' || $response == 'count') {
            $count = app($this->meetingEquipmentRepository)->getEquipmentCount($param);
        }

        $list = [];

        if (($response == 'both' && $count > 0) || $response == 'data') {
            $list = app($this->meetingEquipmentRepository)->listEquipment($param);
        }

        return $response == 'both'
                        ? ['total_count' => $count, 'list' => $list]
                        : ($response == 'data' ? $list : $count);
    }
    /**
     * @新建会议设备
     * @param type $data
     * @return 会议设备Id | array
     */
    public function addEquipment($data)
    {
        $equipmentData = [
            'equipment_name'    => $this->defaultValue('equipment_name', $data, ''),
            'equipment_model'   => $this->defaultValue('equipment_model', $data, ''),
            'equipment_use'     => $this->defaultValue('equipment_use', $data, ''),
            'equipment_creator' => $this->defaultValue('equipment_creator', $data, ''),
            'equipment_remark'  => $this->defaultValue('equipment_remark', $data, ''),
        ];

        if ($this->equipmentNameExists($data['equipment_name'])) {
            return ['code' => ['0x017003', 'meeting']];
        }

        if (!$result = app($this->meetingEquipmentRepository)->addEquipment($equipmentData)) {
            return ['code' => ['0x000003', 'common']];
        }

        return ['equipment_id' => $result->equipment_id];
    }
    /**
     * @编辑会议设备
     * @param type $data
     * @param type $equipmentId
     * @return 成功与否 | array
     */
    public function editEquipment($data, $equipmentId)
    {
        if ($equipmentId == 0) {
            return ['code' => ['0x017002', 'meeting']];
        }

        $equipmentData = [
            'equipment_name'    => $this->defaultValue('equipment_name', $data, ''),
            'equipment_model'   => $this->defaultValue('equipment_model', $data, ''),
            'equipment_use'     => $this->defaultValue('equipment_use', $data, ''),
            'equipment_remark'  => $this->defaultValue('equipment_remark', $data, ''),
        ];

        if ($this->equipmentNameExists($equipmentData['equipment_name'], $equipmentId)) {
            return ['code' => ['0x017003', 'meeting']];
        }

        return app($this->meetingEquipmentRepository)->editEquipment($equipmentData, $equipmentId);

        return true;
    }
    /**
     * @获取会议设备详情
     * @param type $equipmentId
     * @return 会议设备详情 | array
     */
    public function showEquipment($equipmentId)
    {
        if ($equipmentId == 0) {
            return ['code' => ['0x017002', 'meeting']];
        }

        return app($this->meetingEquipmentRepository)->showEquipment($equipmentId);
    }
    /**
     * @删除会议设备
     * @param type $equipmentId
     * @return 成功与否 | array
     */
    public function deleteEquipment($equipmentId)
    {
        if ($equipmentId == 0) {
            return ['code' => ['0x017002', 'meeting']];
        }

        if (!app($this->meetingEquipmentRepository)->deleteEquipment(explode(',', $equipmentId))) {
            return ['code' => ['0x000003', 'common']];
        }

        return true;
    }
    /**
     * @获取会议室列表
     * @param type $params
     * @param type $response
     * @return 会议室列表 | array
     */
    public function listRoom($param, $loginUserInfo)
    {
        if ((isset($param['user_id']) && !empty($param['user_id']) && ($param['user_id'] != '{user_id}')) && (isset($param['type']) && ($param['type'] == 'form'))) {
            $param['user_id'] = isset($param["user_id"]) ? $param["user_id"]:"";
            // 获取表单中用户id传过来用户的角色和部门信息(处理表单有父级时的数据)
            $data = app($this->userService)->getUserAllData($param['user_id']);
            if (!empty($data)) {
                $data = $data->toArray();
                $param['role_id'] = array_column($data['user_has_many_role'], 'role_id');
                $param['dept_id'] = isset($data['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id']) ? $data['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id'] : '';
            }
        }else{
            $param['user_id'] = isset($loginUserInfo["user_id"]) ? $loginUserInfo["user_id"]:"";
            $param['role_id'] = isset($loginUserInfo["role_id"]) ? $loginUserInfo["role_id"]:"";
            $param['dept_id'] = isset($loginUserInfo["dept_id"]) ? $loginUserInfo["dept_id"]:"";
        }

        if ((isset($param['user_id']) && ($param['user_id'] == "{user_id}"))){
            $param['user_id'] = isset($loginUserInfo["user_id"]) ? $loginUserInfo["user_id"]:"";
            $param['role_id'] = isset($loginUserInfo["role_id"]) ? $loginUserInfo["role_id"]:"";
            $param['dept_id'] = isset($loginUserInfo["dept_id"]) ? $loginUserInfo["dept_id"]:"";
        }

        $roomListResult = $this->response(app($this->meetingRoomsRepository), 'getRoomCount', 'listRoom', $this->parseParams($param));
        if (!isset($param['fields']) && empty($param['fields'])) {
            foreach($roomListResult['list'] as $key => $value) {
                $roomTitleStr = '';
                if(!empty($value['room_space'])) {
                    $roomTitleStr .= trans('meeting.number_of_people').'：'.$value['room_space'].'；';
                }else{
                    $roomTitleStr .= trans('meeting.number_of_people').trans('meeting.not_set');
                }
                if(!empty($value['room_phone'])) {
                    $roomTitleStr .= trans('meeting.telephone').'：'.$value['room_phone'].'；';
                }else{
                    $roomTitleStr .= trans('meeting.telephone').trans('meeting.not_set');
                }
                if(!empty($value['equipment_id'])) {
                    $equipmentNameString = $this->getEquipmentNameStringByRoomId($value['equipment_id']);
                    if(!empty($equipmentNameString)){
                        $roomTitleStr .= trans('meeting.equipment').'：'.$equipmentNameString.'；';
                    }else{
                        $roomTitleStr .= trans('meeting.equipment').trans('meeting.not_set');
                    }
                }else{
                    $roomTitleStr .= trans('meeting.equipment').trans('meeting.not_set');
                }
                if(!empty($value['room_remark'])) {
                    $roomTitleStr .= trans('meeting.remark').'：'.$value['room_remark'].'；';
                }else{
                    $roomTitleStr .= trans('meeting.remark').trans('meeting.not_set');
                }
                $roomListResult['list'][$key]['room_title'] = $roomTitleStr;
            }
        }

        return $roomListResult;
    }
    /**
     * @获取会议室列表不包含权限(只用于后台设置层面)
     * @param type $params
     * @param type $response
     * @return 会议室列表 | array
     */
    public function getlistRooms($param)
    {
        $roomListResult = $this->response(app($this->meetingRoomsRepository), 'getRoomCounts', 'listRooms', $this->parseParams($param));
        foreach($roomListResult['list'] as $key => $value) {
            $roomTitleStr = '';
            if(!empty($value['room_space'])) {
                $roomTitleStr .= trans('meeting.number_of_people').'：'.$value['room_space'].'；';
            }else{
                $roomTitleStr .= trans('meeting.number_of_people').trans("meeting.not_set");
            }
            if(!empty($value['room_phone'])) {
                $roomTitleStr .= trans('meeting.telephone').'：'.$value['room_phone'].'；';
            }else{
                $roomTitleStr .= trans('meeting.telephone').trans("meeting.not_set");
            }
            if(!empty($value['equipment_id'])) {
                $equipmentNameString = $this->getEquipmentNameStringByRoomId($value['equipment_id']);
                if(!empty($equipmentNameString)){
                    $roomTitleStr .= trans('meeting.equipment').'：'.$equipmentNameString.'；';
                }else{
                    $roomTitleStr .= trans('meeting.equipment').trans("meeting.not_set");
                }
            }else{
                $roomTitleStr .= trans('meeting.equipment').trans("meeting.not_set");
            }
            if(!empty($value['room_remark'])) {
                $roomTitleStr .= trans('meeting.remark').'：'.$value['room_remark'].'；';
            }else{
                $roomTitleStr .= trans('meeting.remark').trans("meeting.not_set");
            }
            $roomListResult['list'][$key]['room_title'] = $roomTitleStr;
        }
        return $roomListResult;
    }
    /**
     * @获取签到方式列表
     *
     * @return 签到方式列表 | array
     */
    public function listSignType($param) {

        $result = $this->response(app($this->meetingSignTypeRepository),'getSignTypeCount', 'listSignType', $this->parseParams($param));
        if (isset($result['list']) && !empty($result['list'])) {
            foreach($result['list'] as $key => $value) {
                $result['list'][$key]['sign_name'] = mulit_trans_dynamic('meeting_sign.sign_name.' . $value['sign_name']);
            }
        }
        return $result;
    }
    public function listExternalRemindType($param) {

        $result = $this->response(app($this->meetingExternalRemindRepository),'getExternalRemindTypeCount', 'listExternalRemindType', $this->parseParams($param));
        if (isset($result['list']) && !empty($result['list'])) {
            foreach($result['list'] as $key => $value) {
                $result['list'][$key]['external_remind_name'] = mulit_trans_dynamic('meeting_external_remind.external_remind_name.' . $value['external_remind_name']);
                $permission = $this->checkPermission();
                if (!$permission) {
                    if (isset($value['external_remind_type']) && $value['external_remind_type'] == 1) {
                        unset($result['list'][$key]);
                    }
                }
            }
            $result['list'] = array_values($result['list']);
            $result['total'] = count($result['list']);

        }
        return $result;
    }


    /**
     * @根据设备ID字符串获取设备名称字符串
     * @param type $equipmentIdString
     * @return 设备名称字符串 | string
     */
    public function getEquipmentNameStringByRoomId($equipmentIdString) {
        $roomEquipmentNameString = '';
        if(!empty($equipmentIdString)) {
            if($equipmentIdString != 'all') {
                foreach (explode(',', $equipmentIdString) as $key => $value) {
                    $equipmentDetail = app($this->meetingEquipmentRepository)->showEquipment($value);
                    if($equipmentDetail) {
                        $roomEquipmentNameString .= $equipmentDetail->equipment_name.',';
                    }
                }
            }else{
                $allEquipmentList = app($this->meetingEquipmentRepository)->listEquipment([]);
                foreach ($allEquipmentList as $key => $value) {
                    $roomEquipmentNameString .= $value['equipment_name'].',';
                }
            }
            $roomEquipmentNameString = trim($roomEquipmentNameString, ',');
        }
        return $roomEquipmentNameString;
    }

    /**
     * @新建会议室
     * @param type $data
     * @return 会议室id | array
     */
    public function addRoom($data)
    {
        // 新建会议的权限范围为全体的处理
        $roomData = [
            'equipment_id'      => $this->defaultValue('equipment_id', $data, ''),
            'room_name'         => $this->defaultValue('room_name', $data, ''),
            'room_phone'        => $this->defaultValue('room_phone', $data, ''),
            'room_space'        => $this->defaultValue('room_space', $data, ''),
            'room_remark'       => $this->defaultValue('room_remark', $data, ''),
            'meeting_sort_id'   => $this->defaultValue('meeting_sort_id', $data, null)
        ];
        $meetingSortObject = app($this->meetingRoomsRepository)->addRoom($roomData);
        $subjectId         = $meetingSortObject->room_id;
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("meeting_rooms", $meetingSortObject->room_id, $data['attachment_id']);
        }
        return ['room_id' => $meetingSortObject->room_id];
    }
    /**
     * @编辑会议室
     * @param type $data
     * @param type $roomId
     * @return 成功与否 | array
     */
    public function editRoom($data, $roomId)
    {
        if ($roomId == 0) {
            return ['code' => ['0x017006', 'meeting']];
        }

        $roomData = [
            'equipment_id'      => $this->defaultValue('equipment_id', $data, ''),
            'room_name'         => $this->defaultValue('room_name', $data, ''),
            'room_phone'        => $this->defaultValue('room_phone', $data, ''),
            'room_space'        => $this->defaultValue('room_space', $data, ''),
            'room_remark'       => $this->defaultValue('room_remark', $data, ''),
            'meeting_sort_id'   => $this->defaultValue('meeting_sort_id', $data, 1)
        ];
        app($this->meetingRoomsRepository)->editRoom($roomData, $roomId);

        if(isset($data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("meeting_rooms", $roomId, $data['attachment_id']);
        }
        return true;
    }
    /**
     * @获取会议室详情
     * @param type $roomId
     * @return 会议室详情 | array
     */
    public function showRoom($roomId)
    {
        if ($roomId == 0) {
            return ['code' => ['0x017006', 'meeting']];
        }
        $roomDetail = app($this->meetingRoomsRepository)->showRoom($roomId)->toArray();
        $equipmentList = app($this->meetingEquipmentRepository)->listEquipment([]);
        $equipmentIdsArray = array();
        if (!empty($equipmentList)) {
            foreach ($equipmentList as $key => $value) {
                $equipmentIdsArray[$value['equipment_id']] = $value['equipment_name'];
            }
        }
        $roomDetail['equipment_name'] = '';
        if (!empty($roomDetail['equipment_id']) && $roomDetail['equipment_id'] != 'all') {
            $roomDetail['equipment_id']   = explode(',', $roomDetail['equipment_id']);
            foreach ($roomDetail['equipment_id'] as $key => $value) {
                $value = intval($value);
                $roomDetail['equipment_id'][$key] = $value;
                if (!empty($equipmentIdsArray) && isset($equipmentIdsArray[$value])) {
                    $roomDetail['equipment_name'] .= $equipmentIdsArray[$value].',';
                }
            }
            $roomDetail['equipment_name'] = trim($roomDetail['equipment_name'], ',');
        } else if ($roomDetail['equipment_id'] == 'all') {
            $roomDetail['equipment_name'] = trans('meeting.all');
        }
        $roomDetail['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_rooms', 'entity_id'=>$roomId]);
        return $roomDetail;
    }
    /**
     * @删除会议室
     * @param type $roomId
     * @return 成功与否 | array
     */
    public function deleteRoom($roomId)
    {
        if ($roomId == 0) {
            return ['code' => ['0x017006', 'meeting']];
        }

        if ($meetingIds = app($this->meetingApplyRepository)->getMeetingIdsByRoomId($roomId)) {
            return ['meeting_apply_id' => array_column($meetingIds, 'meeting_apply_id')];
        }

        if (!app($this->meetingRoomsRepository)->deleteRoom(explode(',', $roomId))) {
            return ['code' => ['0x000003', 'common']];
        }else{
            $where = ['room_id' => [$roomId]];
            // $this->meetingRoomsMemberUserRepository->deleteByWhere($where);
            // $this->meetingRoomsMemberRoleRepository->deleteByWhere($where);
            // $this->meetingRoomsMemberDepartmentRepository->deleteByWhere($where);
        }

        $roomAttachmentData = ['entity_table' => 'meeting_rooms', 'entity_id' => $roomId];
        app($this->attachmentService)->deleteAttachmentByEntityId($roomAttachmentData);

        return true;
    }
    /**
     * @获取审批会议的会议列表
     * @param array $param
     * @param type $response
     * @return 审批会议的会议列表 | array
     */
    public function listApproveMeeting($param)
    {
        $data = $this->response(app($this->meetingApplyRepository), 'getApproveMeetingCount', 'getApproveMeetingList', $this->parseParams($param));
        $result = [];
        $result = [
                'total' => isset($data['total']) ? $data['total'] : 0,
                'list' => []
            ];
        if (isset($data['list']) && !empty($data['list'])) {
            $temp    = [];
            $results = [];
            $time = date("Y-m-d H:i:s", time());
            //获取当前维护项目的类别
            foreach ($data['list'] as $temp) {
                if (isset($temp['meeting_create_time']) && $temp['meeting_create_time'] == '0000-00-00 00:00:00') {
                    $temp['meeting_create_time'] = '';
                }
                if (($temp['meeting_end_time'] <= $time) && ($temp['meeting_status'] == 2 || $temp['meeting_status'] == 4)) {
                    $temp['meeting_status'] = 5;
                } else if (($temp['meeting_begin_time'] <= $time) && ($temp['meeting_end_time'] > $time) && ($temp['meeting_status'] == 2)) {
                    $temp['meeting_status'] = 4;
                }
                array_push($results, $temp);
            }
            $result = [
                'total' => $data['total'],
                'list' => $results
            ];
        }
        return $result;
    }
    /**
     * @获取我的会议的会议列表
     * @param array $param
     * @param type $response
     * @return 我的会议的会议列表 | array
     */
    public function listOwnMeeting($param ,$loginUserInfo)
    {

        $param['user_id'] = $loginUserInfo['user_id'];
        $data = $this->response(app($this->meetingApplyRepository), 'getMyMeetingCount', 'getMyMeetingList', $this->parseParams($param));
        $allUser = app($this->userService)->getAllUserIdString();
        // $allUserName = get_user_simple_attr($allUser);
        $result = [];
        $result = [
            'total' => isset($data['total']) ? $data['total'] : 0,
            'list' => []
        ];
        if (isset($data['list']) && !empty($data['list'])) {
            $temp    = [];
            $results = [];
            $time = date("Y-m-d H:i:s", time());
            //获取当前维护项目的类别
            foreach ($data['list'] as $temp) {
                if (($temp['meeting_end_time'] <= $time) && ($temp['meeting_status'] == 2 || $temp['meeting_status'] == 4)) {
                    $temp['meeting_status'] = 5;
                } else if (($temp['meeting_begin_time'] <= $time) && ($temp['meeting_end_time'] > $time) && ($temp['meeting_status'] == 2)) {
                    $temp['meeting_status'] = 4;
                }
                if ($temp['meeting_join_member'] == 'all') {
                    $temp['meeting_join_member'] = $allUser;
                }
                array_push($results, $temp);
            }
            if (isset($param['response']) && ($param['response'] == 'data')) {
                return ['list' => $results];
            }
            $result = [
                'total' => $data['total'] ?? 0,
                'list' => $results
            ];
        }
        return $result;
    }

    /**
     * @获取某个会议的参加人员的签到信息
     * @param array $param
     * @param type $response
     * @return 会议的参加人员签到信息列表 | array
     */
    public function getMyMeetingDetail($mApplyId){
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $showMeeting      = app($this->meetingApplyRepository)->showMeeting($mApplyId);

        if(!$showMeeting) {
            return ['code' => ['0x000006','common']];
        }
        $attenceUser = app($this->meetingApplyRepository)->showAttenceUser($mApplyId);

        $refuseUser = app($this->meetingApplyRepository)->showRefuseUser($mApplyId);
        // 参加人员
        if(!empty($attenceUser)) {
            foreach($attenceUser as $key => $value) {
                $attenceUsers[] = $value['meeting_attence_user'];
                $showMeeting->meeting_attence_status = $value['meeting_attence_status'];
                $showMeeting->meeting_sign_type      = $value['meeting_sign_type'];
                $showMeeting->meeting_attence_time   = $value['meeting_attence_time'];
                $showMeeting->meeting_refuse_time    = $value['meeting_refuse_time'];
                $showMeeting->meeting_sign_time      = $value['meeting_sign_time'];
            }
        }

        if(!empty($attenceUsers) && isset($attenceUsers)) {
            $showMeeting->meeting_attence_user = $attenceUsers;
        }else{
            $showMeeting->meeting_attence_user = '';
        }

        //拒绝参加人员
        if(!empty($refuseUser)) {
            foreach($refuseUser as $key => $value) {
                    $refuseUsers[] = $value['meeting_attence_user'];
            }
        }
        if(!empty($refuseUsers) && isset($refuseUsers)) {
            $showMeeting->meeting_refuse_user = $refuseUsers;
        }else {
            $showMeeting->meeting_refuse_user = "";
        }

        $room                                   = app($this->meetingRoomsRepository)->getDetail($showMeeting->meeting_room_id);
        $signType                               = app($this->meetingRoomsRepository)->getDetail($showMeeting->sign_type);

        $showMeeting->meeting_room_name         = $room ? $room->room_name : '';
        $showMeeting->meeting_apply_user_name   = get_user_simple_attr($showMeeting->meeting_apply_user);

        if(!empty($showMeeting->meeting_join_member) && $showMeeting->meeting_join_member != 'all') {
            $showMeeting->meeting_join_member   = explode(',', $showMeeting->meeting_join_member);
        }
        $showMeeting->attachment_id             = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$mApplyId]);
        return $showMeeting;
    }

    /**
     * @获取某个会议的参加人员
     * @param array $param
     * @param type $response
     * @return 会议的参加人员列表 | array
     */

    public function getMeetingMyAttenceUsers($param, $mApplyId) {
        $param['meeting_apply_id'] = $mApplyId;
        return $this->response(app($this->meetingAttenceRepository), 'getMineMeetingDetailCount', 'getMineMeetingDetail', $this->parseParams($param));
    }

    /**
     * @会议签到
     * @param array $param
     * @param type $response
     * @return 会议的参加人员列表 | boolean
     */
    public function signMeeting($data, $mApplyId) {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $signData = [
            'meeting_sign_status'  => 1,
            'meeting_sign_time'    => date('Y-m-d H:i:s'),
        ];
        $hasSign = app($this->meetingAttenceRepository)->getHasSignUser($mApplyId);
        if(isset($hasSign[0]['meeting_sign_time']) && $hasSign[0]['meeting_sign_time'] != null ) {
            return ['code' => ['0x017027', 'meeting']];
        }
        if (!app($this->meetingAttenceRepository)->editSignMeeting($mApplyId, $signData)) {
            return ['code' => ['0x000003', 'common']];
        }
        return true;
    }
    /**
     * @获取某个会议室所有的会议列表
     * @param array $param
     * @param type $response
     * @return 我的会议的会议列表 | array
     */
    public function getMeetingListByRoomId($param)
    {
        return $this->response(app($this->meetingApplyRepository), 'getMeetingCountByRoomId', 'getMeetingListByRoomId', $this->parseParams($param));
    }
    public function addMeetingByFlowOutSend($data) {
        if (empty($data)) {
            return ['code' => ['0x017035', 'meeting']];
        }
        // 获取方式
        $setting = $this->getBaseSetting();
        if (isset($setting['meeting_video_set']) && $setting['meeting_video_set'] == 0 && isset($data['attence_type']) && $data['attence_type'] == 1) {
            return ['code' => ['0x017036', 'meeting']];
        }
        $attenceType = 1;
        if (isset($setting['meeting_video_set']) && $setting['meeting_video_set'] == 1) {
            if (isset($data['attence_type']) && $data['attence_type'] == 0) {
                $attenceType = 1;
            } else if (isset($data['attence_type']) && $data['attence_type'] == 1) {
                $attenceType = 2;
            }
        } else {
            $attenceType = 1;
        }

        
        switch ($attenceType) {
            case 1:
                return $this->addCommonMeetingByFlowOutSend($data);
                break;
            case 2:
                return $this->addVideoMeetingByFlowOutSend($data);
                break;
            default:
                return $this->addCommonMeetingByFlowOutSend($data);
                break;
        }
    }

    /**
     * @通过流程外发的会议申请
     * @param array $data
     * @param type $remindData
     * @param type $uploadFile
     * @return 会议申请id | array
     */
    public function addCommonMeetingByFlowOutSend($data)
    {
        if (!isset($data['meeting_subject']) || empty($data['meeting_subject'])) {
            return ['code' => ['0x017009', 'meeting']];
        }
        if (!isset($data['meeting_apply_user']) || empty($data['meeting_apply_user'])) {
            return ['code' => ['0x017025', 'meeting']];
        }
        if (!isset($data['meeting_begin_time']) || empty($data['meeting_begin_time'])) {
            return ['code' => ['0x017010', 'meeting']];
        }
        if (!isset($data['meeting_end_time']) || empty($data['meeting_end_time'])) {
            return ['code' => ['0x017011', 'meeting']];
        }
        if (!isset($data['meeting_room_id']) || empty($data['meeting_room_id'])) {
            return ['code' => ['0x017007', 'meeting']];
        }
        // if (!isset($data['meeting_type'])) {
        //     return ['code' => ['0x017024', 'meeting']];
        // }
        if ($data['meeting_begin_time'] >= $data['meeting_end_time']) {
            return ['code' => ['0x017012', 'meeting']];
        }
        // 设置外部人员提醒方式
        $externalReminder = null;
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 0) && $data['external_reminder_type'] != '') {
            $externalReminder = 0;
        }
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 1)) {
            $externalReminder = 1;
        }
        if ((isset($data['external_reminder_type']) && count(explode(',', $data['external_reminder_type'])) == 2)) {
            $externalReminder = 2;
        }
        if (isset($data['meeting_reminder_timing_h']) && isset($data['meeting_reminder_timing_m'])) {
            $data['reminder_timing'] = ((int)$data['meeting_reminder_timing_h'] * 60) + ((int)$data['meeting_reminder_timing_m']);
            $data['reminder_time'] = strtotime($data['meeting_begin_time']) - ($data['reminder_timing'] * 60);
            $data['reminder_time'] = date("Y-m-d H:i:s", $data['reminder_time']);
        }
        $meetingJoinMember = $this->defaultValue('meeting_join_member', $data, null);
        if(!empty($meetingJoinMember)) {
            if(is_array($meetingJoinMember)) {
                $meetingJoinMember = join(',', $meetingJoinMember);
            }
        }else{
            $meetingJoinMember = '';
        }
        $currentUser = isset($data['current_user_id']) ? $data['current_user_id'] : '';
        // 判断是否选择了签到
        $sign = 0;
        $signUser = '';
        if (isset($data['sign']) && $data['sign'] == 1) {
            $sign = $data['sign'];
            $signUser = isset($data['meeting_sign_user']) ? $data['meeting_sign_user'] : $currentUser;
        }
        $signType = 1;
        $sign_user = '';
        $sign_wifi = '';
        if (isset($data['sign_type']) && $data['sign_type'] == 0) {
            $signType = 1;
            $sign_user = isset($data['meeting_sign_user']) ? $data['meeting_sign_user'] : $currentUser;
        }
        if (isset($data['sign_type']) && $data['sign_type'] == 1) {
            $signType = 2;
        }
        if (isset($data['sign_type']) && $data['sign_type'] == 2) {
            $signType = 3;
            $sign_wifi = isset($data['meeting_sign_wifi']) ? $data['meeting_sign_wifi'] : null;
            if (isset($data['sign']) && $data['sign'] == 1 && !$sign_wifi) {
                return ['code' => ['0x017032', 'meeting']];
            }
        }

        $meetingData = [
            'meeting_room_id'               => $data['meeting_room_id'],
            'meeting_subject'               => $data['meeting_subject'],
            'meeting_apply_user'            => $data['meeting_apply_user'],
            'meeting_join_member'           => $meetingJoinMember,
            'meeting_other_join_member'     => $this->defaultValue('meeting_other_join_member', $data, null),
            'meeting_type'                  => $this->defaultValue('meeting_type', $data, null),
            'meeting_begin_time'            => $data['meeting_begin_time'],
            'meeting_end_time'              => $data['meeting_end_time'],
            'meeting_remark'                => $this->defaultValue('meeting_remark', $data, null),
            'meeting_status'                => 2,
            'meeting_approval_opinion'      => $this->defaultValue('meeting_approval_opinion', $data, null),
            'meeting_apply_time'            => date('Y-m-d H:i:s'),
            'meeting_reminder_timing'       => $this->defaultValue('reminder_timing', $data, 0),
            'meeting_reminder_time'         => $this->defaultValue('reminder_time', $data, $data['meeting_begin_time']),
            'external_reminder_type'        => $externalReminder,
            'sign'                          => isset($data['sign']) ? $data['sign'] : 0,
            'meeting_response'              => isset($data['meeting_response']) ? $data['meeting_response'] : 0,
            'meeting_sign_user'             => $signUser,
            'meeting_external_user'         => isset($data['meeting_external_user']) ? $data['meeting_external_user'] : null,
            'meeting_sign_wifi'             => $sign_wifi,
            'sign_type'                     => $signType,
            'meeting_approval_user'         => $this->defaultValue('current_user_id', $data, ''),
            'meeting_create_time'           => date('Y-m-d H:i:s', time()),
            'attence_type'                  => 1

        ];

        $meetingData['conflict'] = $this->getConflictMeeting($meetingData['meeting_room_id'], $meetingData['meeting_begin_time'], $meetingData['meeting_end_time']); //获取冲突会议id
        // 检查配置
        $setInfo = $this->getBaseSetting([]);
        if (isset($setInfo['meeting_apply_set']) && $setInfo['meeting_apply_set'] == 0) {
            $meetingData['meeting_status'] = 2;
        }
        if (!empty($meetingData['conflict']) && isset($setInfo['meeting_set_useed']) && $setInfo['meeting_set_useed'] == 1) {
            return ['code' => ['0x017031', 'meeting']];
        }
        if (!$result = app($this->meetingApplyRepository)->addMeeting($meetingData)) {
            return ['code' => ['0x000003', 'common']];
        }
        if($result->meeting_external_user == 'all') {
            $externalUserInfo = app($this->meetingExternalUserRepository)->getExternalUserId();
            $externalUserInfo = array_column($externalUserInfo,"external_user_id");
        }else {
            $externalUserInfo = explode(',', $result->meeting_external_user);
        }
        $externalUserList = app($this->meetingExternalUserRepository)->getDetail($externalUserInfo)->toArray();
        $meeting_begin_time = isset($data['meeting_begin_time']) ? $data['meeting_begin_time'] : '';
        $meeting_end_time = isset($data['meeting_end_time']) ? $data['meeting_end_time'] : '';
        $meeting_subject = isset($data['meeting_subject']) ? $data['meeting_subject'] : '';
        $meeting_room_id = isset($data['meeting_room_id']) ? $data['meeting_room_id'] : '';
        $room = app($this->meetingRoomsRepository)->getDetail($meeting_room_id);
        $meeting_room_name  = $room ? $room->room_name : '';
        $mApplyId = isset($result->meeting_apply_id) ? $result->meeting_apply_id : '';
        $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
        if (isset($result->external_reminder_type) && $result->external_reminder_type !== null) {
            $param['externalUserList'] = $externalUserList;
            $param['meeting_begin_time'] = $meeting_begin_time;
            $param['meeting_end_time'] = $meeting_end_time;
            $param['meeting_end_time'] = $meeting_end_time;
            $param['meeting_room_name'] = $meeting_room_name;
            $param['meeting_apply_id'] = $mApplyId;
            $param['approveResult'] = true;
            $param['oldMeeting'] = $result;
            $param['meeting_subject'] = $meeting_subject;
            $param['domain'] = $domain;
            Queue::push(new SyncMeetingExternalSendJob($param));
        }
        if ((isset($data['meeting_response']) && $data['meeting_response'] == 0) || !isset($data['meeting_response'])) {
            $userJoinList = [];
            if (isset($data['meeting_join_member']) && !empty($data['meeting_join_member'])) {
                if ($data['meeting_join_member'] != 'all') {
                    $userJoinList = explode(',', $data['meeting_join_member']);
                    if (is_array($userJoinList)) {
                        foreach ($userJoinList as $key => $value) {
                            $userData = [
                                'meeting_attence_user'  => $value,
                                'meeting_apply_id'      => $result->meeting_apply_id,
                                'meeting_sign_type'     => $result->sign_type,
                                'meeting_attence_status'=> 1,
                                'meeting_sign_status'   => 0,
                                'meeting_attence_time'  => date("Y-m-d H:i:s", time())

                            ];
                            app($this->meetingAttenceRepository)->insertData($userData);
                        }
                    }
                }else if($data['meeting_join_member'] == 'all') {
                    $joinUser = app($this->userService)->getAllUserIdString([]);
                    if (!empty($joinUser)) {
                        $userJoinList = explode(',', $joinUser);
                        if (is_array($userJoinList)) {
                            foreach ($userJoinList as $key => $value) {
                                $userData = [
                                    'meeting_attence_user'  => $value,
                                    'meeting_apply_id'      => $result->meeting_apply_id,
                                    'meeting_sign_type'     => $result->sign_type,
                                    'meeting_attence_status'=> 1,
                                    'meeting_sign_status'   => 0,
                                    'meeting_attence_time'  => date("Y-m-d H:i:s", time())

                                ];
                                app($this->meetingAttenceRepository)->insertData($userData);
                            }
                        }
                    }
                }
            }
            // 外发到日程模块 --开始--
            $calendarData = [
                'calendar_content' => $data['meeting_subject'],
                'handle_user'      => $userJoinList,
                'calendar_begin'   => $result->meeting_begin_time,
                'calendar_end'     => $result->meeting_end_time,
                'calendar_remark'  => $data['meeting_remark'] ?? '',
                'attachment_id'    => $data['attachment_id'] ?? ''
            ];
            $relationData = [
                'source_id'     => $result->meeting_apply_id,
                'source_from'   => 'meeting-join',
                'source_title'  => $data['meeting_subject'],
                'source_params' => ['meeting_apply_id' => $result->meeting_apply_id]
            ];
            app($this->calendarService)->emit($calendarData, $relationData, $data['meeting_apply_user']);
        }
        if (isset($data['meeting_external_user']) && !empty($data['meeting_external_user'])) {
            if ($data['meeting_external_user'] != 'all') {
                $externalUserList = explode(',', $data['meeting_external_user']);
                if (is_array($externalUserList)) {
                    foreach ($externalUserList as $key => $value) {
                        $externalData = [
                            'meeting_external_user' => $value,
                            'meeting_apply_id' => $result->meeting_apply_id,
                            'meeting_sign_status' => 0,

                        ];
                        app($this->meetingExternalAttenceRepository)->insertData($externalData);
                    }
                }

            }elseif($data['meeting_external_user'] == 'all'){
                $externalUserListString = app($this->meetingExternalUserRepository)->getExternalUserIdString();
                if (!empty($externalUserListString)) {
                    foreach ($externalUserListString as $key => $value) {
                        $externalData = [
                            'meeting_external_user' => $value,
                            'meeting_apply_id' => $result->meeting_apply_id,
                            'meeting_sign_status' => 0,

                        ];
                        app($this->meetingExternalAttenceRepository)->insertData($externalData);
                    }
                }
            }
        }


        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("meeting_apply", $result->meeting_apply_id, $data['attachment_id']);
        }

        if ($meetingData['conflict']) {
            $this->updateMeetingConflictId($meetingData['conflict'], $result->meeting_apply_id); //更新相应的冲突会议
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'meeting_apply',
                    'field_to' => 'meeting_apply_id',
                    'id_to'    => $result->meeting_apply_id
                ]
            ]
        ];
    }
    public function addVideoMeetingByFlowOutSend($data)
    {
        if (!isset($data['meeting_subject']) || empty($data['meeting_subject'])) {
            return ['code' => ['0x017009', 'meeting']];
        }
        if (!isset($data['meeting_apply_user']) || empty($data['meeting_apply_user'])) {
            return ['code' => ['0x017025', 'meeting']];
        }
        if (!isset($data['meeting_begin_time']) || empty($data['meeting_begin_time'])) {
            return ['code' => ['0x017010', 'meeting']];
        }
        if (!isset($data['meeting_end_time']) || empty($data['meeting_end_time'])) {
            return ['code' => ['0x017011', 'meeting']];
        }
        if (!isset($data['interface_id']) || empty($data['interface_id'])) {
            return ['code' => ['0x017034', 'meeting']];
        }
        if ($data['meeting_begin_time'] >= $data['meeting_end_time']) {
            return ['code' => ['0x017012', 'meeting']];
        }
        // 设置外部人员提醒方式
        $externalReminder = null;
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 0) && $data['external_reminder_type'] != '') {
            $externalReminder = 0;
        }
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 1)) {
            $externalReminder = 1;
        }
        if ((isset($data['external_reminder_type']) && count(explode(',', $data['external_reminder_type'])) == 2)) {
            $externalReminder = 2;
        }
        if (isset($data['meeting_reminder_timing_h']) && isset($data['meeting_reminder_timing_m'])) {
            $data['reminder_timing'] = ((int)$data['meeting_reminder_timing_h'] * 60) + ((int)$data['meeting_reminder_timing_m']);
            $data['reminder_time'] = strtotime($data['meeting_begin_time']) - ($data['reminder_timing'] * 60);
            $data['reminder_time'] = date("Y-m-d H:i:s", $data['reminder_time']);
        }
        $meetingJoinMember = $this->defaultValue('meeting_join_member', $data, null);
        if(!empty($meetingJoinMember)) {
            if(is_array($meetingJoinMember)) {
                $meetingJoinMember = join(',', $meetingJoinMember);
            }
        }else{
            $meetingJoinMember = '';
        }
        $currentUser = isset($data['current_user_id']) ? $data['current_user_id'] : '';
        
        $meetingData = [
            'meeting_room_id'               => 0,
            'meeting_subject'               => $data['meeting_subject'],
            'meeting_apply_user'            => $data['meeting_apply_user'],
            'meeting_join_member'           => $meetingJoinMember,
            'meeting_other_join_member'     => $this->defaultValue('meeting_other_join_member', $data, null),
            'meeting_type'                  => $this->defaultValue('meeting_type', $data, null),
            'meeting_begin_time'            => $data['meeting_begin_time'],
            'meeting_end_time'              => $data['meeting_end_time'],
            'meeting_remark'                => $this->defaultValue('meeting_remark', $data, null),
            'meeting_status'                => 2,
            'meeting_approval_opinion'      => $this->defaultValue('meeting_approval_opinion', $data, null),
            'meeting_apply_time'            => date('Y-m-d H:i:s'),
            'meeting_reminder_timing'       => $this->defaultValue('reminder_timing', $data, 0),
            'meeting_reminder_time'         => $this->defaultValue('reminder_time', $data, $data['meeting_begin_time']),
            'external_reminder_type'        => $externalReminder,
            
            'meeting_external_user'         => isset($data['meeting_external_user']) ? $data['meeting_external_user'] : null,
            'meeting_approval_user'         => $this->defaultValue('current_user_id', $data, ''),
            'meeting_create_time'           => date('Y-m-d H:i:s', time()),
            'attence_type'                  => 2,
            'interface_id'                  => $data['interface_id'] ?? ''
        ];

        $video = app($this->vedioConferenceService)->createVideoconference(['interface_id' => $data['interface_id']], ['meeting_name' => $data['meeting_subject'], 'meeting_begin_time' => $data['meeting_begin_time'], 'meeting_end_time' => $data['meeting_end_time'], 'meeting_apply_user' => $data['meeting_apply_user']]);
        if (isset($video['code'])) {
            return $video;
        }
        if ($video) {
            $meetingData['meeting_video_info'] = $video;
        }
        if (!$result = app($this->meetingApplyRepository)->addMeeting($meetingData)) {
            return ['code' => ['0x000003', 'common']];
        }
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("meeting_apply", $result->meeting_apply_id, $data['attachment_id']);
        }
        if($result->meeting_external_user == 'all') {
            $externalUserInfo = app($this->meetingExternalUserRepository)->getExternalUserId();
            $externalUserInfo = array_column($externalUserInfo,"external_user_id");
        }else {
            $externalUserInfo = explode(',', $result->meeting_external_user);
        }
        $meetingInfo = app($this->meetingApplyRepository)->getMeetingOne($result->meeting_apply_id);
        $meetingInfo = $meetingInfo[0] ?? [];
        $meetingInfo['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$result->meeting_apply_id]);
        if (isset($data['meeting_join_member']) && !empty($data['meeting_join_member'])) {
            $param = [];
            $param['meeting_join_member'] = $data['meeting_join_member'];
            $param['meeting_apply_id'] = $result->meeting_apply_id;
            $param['sign_type'] = $result->sign_type;
            $param['meeting_attence_status'] = 1;
            Queue::push(new SyncMeetingJob($param));
        }
        if (isset($meetingData['meeting_external_user'])) {
            $external = [];
            $external['meeting_external_user'] = $meetingData['meeting_external_user'];
            $external['meeting_apply_id'] = $result->meeting_apply_id;
            Queue::push(new SyncMeetingExternalJob($external));
        }
        if (isset($meetingInfo['meeting_join_member']) && $meetingInfo['meeting_join_member'] == 'all') {
            $joinMember = app($this->userService)->getAllUserIdString();
        } else {
           $joinMember = $meetingInfo['meeting_join_member'];
        }
        $userId = array_unique(array_filter(array_merge(explode(',', $joinMember), [$meetingInfo['meeting_apply_user']])));
        $meetingTime   = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        $video = json_decode($video, true);
        $meetingUrl = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['join_url'] : '';
        $meetingID = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['meeting_id'] : '';
        $meetingCode = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['meeting_code'] : '';
        // 参加消息发送给参加人
        $sendDataForJoin = [];
        $sendDataForJoin = [
            'remindMark'    => 'meeting-video',
            'fromUser'      => $currentUser,
            'toUser'        => $userId,
            'contentParam'  => [
                'meetingSubject'=>$meetingInfo['meeting_subject'],
                'meetingTime'=>$meetingTime,
                'meetingUrl'=> $meetingUrl,
                'meetingID'=> $meetingID,
                'meetingCode'=> $meetingCode,
            ],
            'stateParams'   => ['meeting_apply_id' => $result->meeting_apply_id, 'meeting_response' => $meetingInfo['meeting_response']]
        ];
        Eoffice::sendMessage($sendDataForJoin);

        $this->emitCalendar($meetingInfo, 'add');
        if (isset($meetingInfo['external_reminder_type']) && $meetingInfo['external_reminder_type'] !== null) {
            $this->parseExternalRemind($meetingInfo['meeting_apply_id']);
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'meeting_apply',
                    'field_to' => 'meeting_apply_id',
                    'id_to'    => $result->meeting_apply_id
                ]
            ]
        ];
    }
    public function addMeeting($data, $loginUserInfo=[]) {
        if (empty($data)) {
            return ['code' => ['0x017035', 'meeting']];
        }
        // 获取方式
        $setting = $this->getBaseSetting();
        if (isset($setting['meeting_video_set']) && $setting['meeting_video_set'] == 1) {
            $attenceType = $data['attence_type'] ?? 1;
        } else {
            $attenceType = 1;
        }
        
        switch ($attenceType) {
            case 1:
                return $this->addCommonMeeting($data, $loginUserInfo);
                break;
            case 2:
                return $this->addVideoMeeting($data, $loginUserInfo);
                break;
            default:
                return $this->addCommonMeeting($data, $loginUserInfo);
                break;
        }
    }
    /**
     * @新建会议申请
     * @param array $data
     * @param type $remindData
     * @param type $uploadFile
     * @return 会议申请id | array
     */
    public function addCommonMeeting($data, $loginUserInfo=[]) {
        if ($data['meeting_begin_time'] >= $data['meeting_end_time']) {
            return ['code' => ['0x017012', 'meeting']];
        }
        // 设置外部人员提醒方式
        if (isset($data['email']) && ($data['email'] == 0)) {
            $data['external_reminder_type'] = 0;
        }
        if (isset($data['phone']) && ($data['phone'] == 1)) {
            $data['external_reminder_type'] = 1;
        }
        if ((isset($data['phone']) && ($data['phone'] == 1)) && (isset($data['email']) && ($data['email'] == 0))) {
            $data['external_reminder_type'] = 2;
        }
        // 人员签到,不选人员默认为当前申请人
        if (isset($data['sign'])&& $data['sign'] && isset($data['sign_type']) && $data['sign_type'] == 1 && !isset($data['meeting_sign_user'])) {
                $data['meeting_sign_user'] = $data['meeting_apply_user'];
        }

        $meetingJoinMember = $this->defaultValue('meeting_join_member', $data, null);
        $meetingexternaluser = '';
        if(isset($data['meeting_external_user']) && !empty($data['meeting_external_user'])) {
            $meetingexternaluser = $this->defaultValue('meeting_external_user', $data, null);
        }else{
            $data['meeting_external_user'] = '';
        }
        if(isset($data['external_user_id']) && !empty($data['external_user_id'])) {
            $meetingexternaluser = $this->defaultValue('external_user_id', $data, null);
        }else{
            $data['meeting_external_user'] = '';
        }

        if(!empty($meetingJoinMember)) {
            if(is_array($meetingJoinMember)) {
                $meetingJoinMember = join(',', $meetingJoinMember);
            }
        }else{
            $meetingJoinMember = '';
        }

        if(!empty($meetingexternaluser)) {
            if(is_array($meetingexternaluser)) {
                $meetingexternaluser = join(',', $meetingexternaluser);
            }else{
                $meetingexternaluser = $meetingexternaluser;
            }
        }else{
            $meetingexternaluser = '';
        }
        // 外部人员提醒方式解析处理
        if (isset($data['external_reminder_type']) && is_array($data['external_reminder_type'])) {
            if (in_array('0', $data['external_reminder_type']) && count($data['external_reminder_type']) == 1) {
                $data['external_reminder_type'] = 0;
            }elseif (in_array('1', $data['external_reminder_type']) && count($data['external_reminder_type']) == 1) {
                $data['external_reminder_type'] = 1;
            }elseif(count($data['external_reminder_type']) == 2){
                $data['external_reminder_type'] = 2;
            }else{
                $data['external_reminder_type'] = null;
            }
        }
        if (isset($data['meeting_sign_wifi'])) {
            $data['meeting_wifi_name'] = $data['meeting_sign_wifi'];
        }
        $meetingData = [
            'meeting_room_id'               => $data['meeting_room_id'],
            'meeting_subject'               => $data['meeting_subject'],
            'meeting_apply_user'            => $data['meeting_apply_user'],
            'meeting_join_member'           => $meetingJoinMember,
            'meeting_other_join_member'     => $this->defaultValue('meeting_other_join_member', $data, null),
            'meeting_type'                  => $this->defaultValue('meeting_type', $data, null),
            'meeting_begin_time'            => $data['meeting_begin_time'],
            'meeting_end_time'              => $data['meeting_end_time'],
            'meeting_remark'                => $this->defaultValue('meeting_remark', $data, null),
            'meeting_status'                => 1,
            'sign_type'                     => $this->defaultValue('sign_type', $data, null),
            'meeting_sign_wifi'             => $this->defaultValue('meeting_wifi_name',$data, null),
            'meeting_external_user'         => isset($meetingexternaluser) ? $meetingexternaluser : '',
            'meeting_reminder_time'         => $this->defaultValue('reminder_time', $data, null),
            'meeting_reminder_timing'       => $this->defaultValue('reminder_timing', $data, null),
            'external_reminder_type'        => $this->defaultValue('external_reminder_type', $data, null),
            'sign'                          => isset($data['sign']) ? $data['sign'] : 0,
            'meeting_response'              => isset($data['meeting_response']) ? $data['meeting_response'] : 0,
            'meeting_create_time'           => date('Y-m-d H:i:s', time()),
            'attence_type'                  => $data['attence_type'] ?? 1
        ];

        if (isset($data['meeting_sign_user']) && empty($data['meeting_sign_user'])) {
            $meetingData['meeting_sign_user'] = $data['meeting_apply_user'];
        }else {
            $meetingData['meeting_sign_user'] = $data['meeting_sign_user'] ?? "";
        }
        $meetingData['conflict'] = $this->getConflictMeeting($meetingData['meeting_room_id'], $meetingData['meeting_begin_time'], $meetingData['meeting_end_time']); //获取冲突会议id
        // 检查配置
        $setInfo = $this->getBaseSetting([]);
        if (isset($setInfo['meeting_apply_set']) && $setInfo['meeting_apply_set'] == 0) {
            $meetingData['meeting_status'] = 2;
        }

        // 会议添加成功后对会议外部人员进行插入到外部人员签到表中
        if (!$result = app($this->meetingApplyRepository)->addMeeting($meetingData)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($data['meeting_response']) || (isset($data['meeting_response']) && $data['meeting_response'] == 0)) {
            if (isset($data['meeting_join_member']) && !empty($data['meeting_join_member'])) {
                $param = [];
                $param['meeting_join_member'] = $data['meeting_join_member'];
                $param['meeting_apply_id'] = $result->meeting_apply_id;
                $param['sign_type'] = $result->sign_type;
                Queue::push(new SyncMeetingJob($param));
            }
        }
        if (isset($meetingData['meeting_external_user'])) {
            $external = [];
            $external['meeting_external_user'] = $meetingData['meeting_external_user'];
            $external['meeting_apply_id'] = $result->meeting_apply_id;
            Queue::push(new SyncMeetingExternalJob($external));
        }

        if ($meetingData['conflict']) {
            $this->updateMeetingConflictId($meetingData['conflict'], $result->meeting_apply_id); //更新相应的冲突会议
        }

        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("meeting_apply", $result->meeting_apply_id, $data['attachment_id']);
        }
        $meetingInfo = app($this->meetingApplyRepository)->getMeetingOne($result->meeting_apply_id);
        $meetingInfo = $meetingInfo[0] ?? [];
        $meetingInfo['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$result->meeting_apply_id]);
        $userName           = $loginUserInfo['user_name'];
        $getMeetingRoomInfo = $this->showRoom($result->meeting_room_id);
        $roomName           = $getMeetingRoomInfo['room_name'];
        $applyTime          = date("Y-m-d H:i", time());
        $meetingTime   = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        $shareusers = explode(',',$meetingInfo['meeting_apply_user']);
        $signUser = explode(',',$meetingInfo['meeting_sign_user']);
        if (isset($setInfo['meeting_apply_set']) && $setInfo['meeting_apply_set'] == 1) {
            // 获取具有该会议分类审批权限的人员
            $meetingSortId = $result->meeting_room_id;
            $approvalUser = app($this->meetingRoomsRepository)->getApprovalUser($meetingSortId);
            $approvalUser = $approvalUser[0]['meeting_approvel_user'];
            $meetingInfo = app($this->meetingApplyRepository)->getMeetingOne($result->meeting_apply_id);
            $toUser             = $approvalUser;
            $sendData['remindMark']     = 'meeting-submit';
            $sendData['fromUser']       = $loginUserInfo['user_id'];
            $sendData['toUser']         = $toUser;
            $sendData['contentParam']   = ['meetingSubject'=>$data['meeting_subject'], 'applyUser'=>$userName, 'meetingRoom'=>$roomName, 'applyTime'=>$applyTime];
            $sendData['stateParams']    = ['meeting_apply_id' => $result->meeting_apply_id];
            Eoffice::sendMessage($sendData);
        } else {
            // 参加开始提醒
            if (!empty($meetingInfo['meeting_join_member'])) {

                if($meetingInfo['meeting_join_member'] == 'all') {
                    $toJoinUser = app($this->userService)->getAllUserIdString([]);
                }else{
                    $toJoinUser = $meetingInfo['meeting_join_member'];
                }
                $handelusers = explode(',',$toJoinUser);
                $meetingUsers = array_unique(array_merge($shareusers,$handelusers));
                if (!empty($signUser)) {
                    $meetingUsers = array_unique(array_merge($signUser,$meetingUsers));
                }
                $notAttenceUserArr = [];
                $notAttenceUser = app($this->meetingApplyRepository)->showNotAttenceUser($meetingInfo['meeting_apply_id']);
                if (!empty($notAttenceUser)) {
                    $notAttenceUserArr = array_column($notAttenceUser, 'meeting_attence_user');
                }
                $arr = array_diff(array_filter($meetingUsers), $notAttenceUserArr);
                $userId = implode(',',$arr);
                // 参加消息发送给参加人
                $sendDataForJoin['remindMark']   = 'meeting-join';
                $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                $sendDataForJoin['toUser']       = $userId;
                $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$roomName];
                $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $result->meeting_apply_id, 'meeting_response' => $meetingInfo['meeting_response']];
                Eoffice::sendMessage($sendDataForJoin);
            } else {
                $userId = implode(',', array_unique(array_merge($shareusers, $signUser)));
                // 参加消息发送给参加人
                $sendDataForJoin['remindMark']   = 'meeting-join';
                $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                $sendDataForJoin['toUser']       = $userId;
                $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$roomName];
                $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $result->meeting_apply_id, 'meeting_response' => $meetingInfo['meeting_response']];
                // $sendDataForJoin['meetingResponse']       = ['meeting_response' => $meetingInfo['meeting_response']];
                Eoffice::sendMessage($sendDataForJoin);
            }
            // 审批通过后开始提醒
            if(strtotime($meetingInfo['meeting_reminder_time']) < strtotime(date('Y-m-d H:i:s',time()))){

                if(!empty($meetingInfo['meeting_join_member'])) {
                    if($meetingInfo['meeting_join_member'] == 'all') {
                        $toJoinUser = app($this->userService)->getAllUserIdString([]);
                    }else{
                        $toJoinUser = $meetingInfo['meeting_join_member'];
                    }

                    $handelusers = explode(',',$toJoinUser);
                    $meetingUsers = array_unique(array_merge($shareusers,$handelusers));
                    if (!empty($signUser)) {
                        $meetingUsers = array_unique(array_merge($signUser,$meetingUsers));
                    }
                    $notAttenceUserArr = [];
                    $notAttenceUser = app($this->meetingApplyRepository)->showNotAttenceUser($meetingInfo['meeting_apply_id']);
                    if (!empty($notAttenceUser)) {
                        $notAttenceUserArr = array_column($notAttenceUser, 'meeting_attence_user');
                    }
                    $arr = array_diff(array_filter($meetingUsers), $notAttenceUserArr);
                    $userId = implode(',',$arr);
                    $roomInfo      = app($this->meetingRoomsRepository)->getDetail($meetingInfo['meeting_room_id']);
                    $meetingRoom   = $roomInfo->room_name;
                    // 参加消息发送给参加人
                    $sendDataForJoin['remindMark']   = 'meeting-start';
                    $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                    $sendDataForJoin['toUser']       = $userId;
                    $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingInfo['meeting_begin_time'], 'meetingRoom'=>$roomName];
                    $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $meetingInfo['meeting_apply_id']];
                    Eoffice::sendMessage($sendDataForJoin);
                } else {

                    $userId = implode(',', array_unique(array_merge($shareusers, $signUser)));
                    // 参加消息发送给参加人
                    $sendDataForJoin['remindMark']   = 'meeting-start';
                    $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                    $sendDataForJoin['toUser']       = $userId;
                    $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingInfo['meeting_begin_time'], 'meetingRoom'=>$roomName];
                    $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $meetingInfo['meeting_apply_id']];
                    Eoffice::sendMessage($sendDataForJoin);
                }
            }
            if (isset($meetingInfo['external_reminder_type']) && $meetingInfo['external_reminder_type'] !== null) {
                $this->parseExternalRemind($meetingInfo['meeting_apply_id']);
            }
            if (isset($meetingInfo['meeting_response']) && $meetingInfo['meeting_response'] != 1) {
                // 外发到日程模块 --开始--
                $calendarData = [
                    'calendar_content' => $meetingInfo['meeting_subject'],
                    'handle_user'      => explode(',', $userId),
                    'calendar_begin'   => $result->meeting_begin_time,
                    'calendar_end'     => $result->meeting_end_time,
                    'calendar_remark'  => $meetingInfo['meeting_remark'] ?? '',
                    'attachment_id'    => $meetingInfo['attachment_id'] ?? ''
                ];
                $relationData = [
                    'source_id'     => $result->meeting_apply_id,
                    'source_from'   => 'meeting-join',
                    'source_title'  => $meetingInfo['meeting_subject'],
                    'source_params' => ['meeting_apply_id' => $result->meeting_apply_id]
                ];
                app($this->calendarService)->emit($calendarData, $relationData, $meetingInfo['meeting_apply_user']);
                // 外发到日程模块 --结束--
            }
        }
        return $result;
    }

    public function addVideoMeeting($data, $loginUserInfo=[]) {

        if ($data['meeting_begin_time'] >= $data['meeting_end_time']) {
            return ['code' => ['0x017012', 'meeting']];
        }
         // 设置外部人员提醒方式
        if (isset($data['email']) && ($data['email'] == 0)) {
            $data['external_reminder_type'] = 0;
        }
        if (isset($data['phone']) && ($data['phone'] == 1)) {
            $data['external_reminder_type'] = 1;
        }
        if ((isset($data['phone']) && ($data['phone'] == 1)) && (isset($data['email']) && ($data['email'] == 0))) {
            $data['external_reminder_type'] = 2;
        }
        // 外部人员提醒方式解析处理
        if (isset($data['external_reminder_type']) && is_array($data['external_reminder_type'])) {
            if (in_array('0', $data['external_reminder_type']) && count($data['external_reminder_type']) == 1) {
                $data['external_reminder_type'] = 0;
            }elseif (in_array('1', $data['external_reminder_type']) && count($data['external_reminder_type']) == 1) {
                $data['external_reminder_type'] = 1;
            }elseif(count($data['external_reminder_type']) == 2){
                $data['external_reminder_type'] = 2;
            }else{
                $data['external_reminder_type'] = null;
            }
        }
        $meetingexternaluser = '';
        if(isset($data['meeting_external_user']) && !empty($data['meeting_external_user'])) {
            $meetingexternaluser = $this->defaultValue('meeting_external_user', $data, null);
        }else{
            $data['meeting_external_user'] = '';
        }
        if(isset($data['external_user_id']) && !empty($data['external_user_id'])) {
            $meetingexternaluser = $this->defaultValue('external_user_id', $data, null);
        }else{
            $data['meeting_external_user'] = '';
        }
        if(!empty($meetingexternaluser)) {
            if(is_array($meetingexternaluser)) {
                $meetingexternaluser = join(',', $meetingexternaluser);
            }else{
                $meetingexternaluser = $meetingexternaluser;
            }
        }else{
            $meetingexternaluser = '';
        }
        $meetingData = [
            'meeting_room_id'               => $data['meeting_room_id'] ?? '',
            'meeting_subject'               => $data['meeting_subject'],
            'meeting_apply_user'            => $data['meeting_apply_user'],
            'meeting_join_member'           => isset($data['meeting_join_member']) && is_array($data['meeting_join_member']) ? implode(',', $data['meeting_join_member']) : '',
            'meeting_other_join_member'     => $this->defaultValue('meeting_other_join_member', $data, null),
            'meeting_type'                  => $this->defaultValue('meeting_type', $data, null),
            'meeting_begin_time'            => $data['meeting_begin_time'],
            'meeting_end_time'              => $data['meeting_end_time'],
            'meeting_remark'                => $this->defaultValue('meeting_remark', $data, null),
            'meeting_status'                => 2,
            'sign_type'                     => '',
            'meeting_sign_wifi'             => '',
            'meeting_external_user'         => $meetingexternaluser,
            'meeting_reminder_time'         => $this->defaultValue('reminder_time', $data, null),
            'meeting_reminder_timing'       => $this->defaultValue('reminder_timing', $data, null),
            'external_reminder_type'        => $this->defaultValue('external_reminder_type', $data, null),
            'sign'                          => 0,
            'meeting_response'              => isset($data['meeting_response']) ? $data['meeting_response'] : 0,
            'meeting_create_time'           => date('Y-m-d H:i:s', time()),
            'attence_type'                  => $data['attence_type'] ?? 2,
            'interface_id'                  => $data['interface_id'] ?? ''
        ];
        if (isset($data['meeting_join_member']) && $data['meeting_join_member'] == 'all') {
            $meetingData['meeting_join_member'] = 'all';
        }
        $meetingData['conflict'] = $this->getConflictMeeting($meetingData['meeting_room_id'], $meetingData['meeting_begin_time'], $meetingData['meeting_end_time']); //获取冲突会议id
        // 测试调用视频会议
        $video = app($this->vedioConferenceService)->createVideoconference(['interface_id' => $data['interface_id']], ['meeting_name' => $data['meeting_subject'], 'meeting_begin_time' => $data['meeting_begin_time'], 'meeting_end_time' => $data['meeting_end_time'], 'meeting_apply_user' => $data['meeting_apply_user']]);
        if (isset($video['code'])) {
            return $video;
        }
        if ($video) {
            $meetingData['meeting_video_info'] = $video;
        }
        if (!$result = app($this->meetingApplyRepository)->addMeeting($meetingData)) {
            return ['code' => ['0x000003', 'common']];
        }
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("meeting_apply", $result->meeting_apply_id, $data['attachment_id']);
        }
        $meetingInfo = app($this->meetingApplyRepository)->getMeetingOne($result->meeting_apply_id);
        $meetingInfo = $meetingInfo[0] ?? [];
        $meetingInfo['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$result->meeting_apply_id]);
        if (isset($data['meeting_join_member']) && !empty($data['meeting_join_member'])) {
            $param = [];
            $param['meeting_join_member'] = $data['meeting_join_member'];
            $param['meeting_apply_id'] = $result->meeting_apply_id;
            $param['sign_type'] = $result->sign_type;
            $param['meeting_attence_status'] = 1;
            Queue::push(new SyncMeetingJob($param));
        }
        if (isset($meetingData['meeting_external_user'])) {
            $external = [];
            $external['meeting_external_user'] = $meetingData['meeting_external_user'];
            $external['meeting_apply_id'] = $result->meeting_apply_id;
            Queue::push(new SyncMeetingExternalJob($external));
        }
        if (isset($meetingInfo['meeting_join_member']) && $meetingInfo['meeting_join_member'] == 'all') {
            $joinMember = app($this->userService)->getAllUserIdString();
        } else {
           $joinMember = $meetingInfo['meeting_join_member'];
        }
        $userId = array_unique(array_filter(array_merge(explode(',', $joinMember), [$meetingInfo['meeting_apply_user']])));
        $meetingTime   = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        $video = json_decode($video, true);
        $meetingUrl = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['join_url'] : '';
        $meetingID = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['meeting_id'] : '';
        $meetingCode = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['meeting_code'] : '';
        // 参加消息发送给参加人
        $sendDataForJoin = [];
        $sendDataForJoin = [
            'remindMark'    => 'meeting-video',
            'fromUser'      => $loginUserInfo['user_id'],
            'toUser'        => $userId,
            'contentParam'  => [
                'meetingSubject'=>$meetingInfo['meeting_subject'],
                'meetingTime'=>$meetingTime,
                'meetingUrl'=> $meetingUrl,
                'meetingID'=> $meetingID,
                'meetingCode'=> $meetingCode,
            ],
            'stateParams'   => ['meeting_apply_id' => $result->meeting_apply_id, 'meeting_response' => $meetingInfo['meeting_response']]
        ];
        Eoffice::sendMessage($sendDataForJoin);

        $this->emitCalendar($meetingInfo);
        if (isset($meetingInfo['external_reminder_type']) && $meetingInfo['external_reminder_type'] !== null) {
            $this->parseExternalRemind($meetingInfo['meeting_apply_id']);
        }
        return $result;
    }

    private function emitCalendar($meetingInfo, $type = 'add') {
        if (isset($meetingInfo['meeting_join_member']) && $meetingInfo['meeting_join_member'] == 'all') {
            $joinMember = app($this->userService)->getAllUserIdString();
        } else {
           $joinMember = $meetingInfo['meeting_join_member'];
        }
        $userId = array_unique(array_filter(array_merge(explode(',', $joinMember), [$meetingInfo['meeting_apply_user']])));
        $calendarData = [
            'calendar_content' => $meetingInfo['meeting_subject'],
            'handle_user'      => $userId,
            'calendar_begin'   => $meetingInfo['meeting_begin_time'],
            'calendar_end'     => $meetingInfo['meeting_end_time'],
            'calendar_remark'  => $meetingInfo['meeting_remark'],
            'attachment_id'    => $meetingInfo['attachment_id'] ?? ''
        ];
        $relationData = [
            'source_id'     => $meetingInfo['meeting_apply_id'],
            'source_from'   => 'meeting-join',
            'source_title'  => $meetingInfo['meeting_subject'],
            'source_params' => ['meeting_apply_id' => $meetingInfo['meeting_apply_id']]
        ];
        if (isset($meetingInfo['attence_type']) && $meetingInfo['attence_type'] == 2) {
            $relationData['source_from'] = 'meeting-video';
        }
        if ($type == 'add') {
            return app($this->calendarService)->emit($calendarData, $relationData, $meetingInfo['meeting_apply_user']);
        } else {
            return app($this->calendarService)->emitUpdate($calendarData, $relationData, $meetingInfo['meeting_apply_user']);
        }
        
        // 外发到日程模块 --结束--
    }
    /**
     * /异步插入会议参加人员
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function parseMeetingJoin($param) {
        if (!isset($param['meeting_apply_id'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if (isset($param['meeting_join_member']) && ($param['meeting_join_member'] != 'all')) {
            if (is_array($param['meeting_join_member'])) {
                foreach ($param['meeting_join_member'] as $key => $value) {
                    $userData = [
                        'meeting_attence_user'  => $value,
                        'meeting_apply_id'      => $param['meeting_apply_id'],
                        'meeting_sign_type'     => $param['sign_type'],
                        'meeting_attence_status'=> 1,
                        'meeting_sign_status'   => 0,
                        'meeting_attence_time'  => date("Y-m-d H:i:s", time())

                    ];
                    app($this->meetingAttenceRepository)->insertData($userData);
                }
            }
        }else if(isset($param['meeting_join_member']) && ($param['meeting_join_member'] == 'all')) {
            $joinUser = app($this->userService)->getAllUserIdString([]);
            if (!empty($joinUser)) {
                $userJoinList = explode(',', $joinUser);
                if (is_array($userJoinList)) {
                    foreach ($userJoinList as $key => $value) {
                        $userData = [
                            'meeting_attence_user'  => $value,
                            'meeting_apply_id'      => $param['meeting_apply_id'],
                            'meeting_sign_type'     => $param['sign_type'],
                            'meeting_attence_status'=> 1,
                            'meeting_sign_status'   => 0,
                            'meeting_attence_time'  => date("Y-m-d H:i:s", time())

                        ];
                        app($this->meetingAttenceRepository)->insertData($userData);
                    }
                }
            }
        }
    }
    /**
     * /异步插入外部人员
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function parseMeetingExternalJoin($param) {
        if (!isset($param['meeting_apply_id'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if (isset($param['meeting_external_user']) && ($param['meeting_external_user'] != 'all')) {
            $externalUserList = explode(',', $param['meeting_external_user']);
            if (is_array($externalUserList)) {
                foreach ($externalUserList as $key => $value) {
                    $externalData = [
                        'meeting_external_user' => $value,
                        'meeting_apply_id' => $param['meeting_apply_id'],
                        'meeting_sign_status' => 0,

                    ];
                    app($this->meetingExternalAttenceRepository)->insertData($externalData);
                }
            }
        }else if(isset($param['meeting_external_user']) && ($param['meeting_external_user'] == 'all')) {
            $externalUserListString = app($this->meetingExternalUserRepository)->getExternalUserIdString();
            if (!empty($externalUserListString)) {
                foreach ($externalUserListString as $key => $value) {
                    $externalData = [
                        'meeting_external_user' => $value,
                        'meeting_apply_id' => $param['meeting_apply_id'],
                        'meeting_sign_status' => 0,

                    ];
                    app($this->meetingExternalAttenceRepository)->insertData($externalData);
                }
            }
        }
    }
    //流程外发更新会议
    public function flowOutSendToUpdateMeeting($data) {
        if (empty($data)) {
            return ['code' => ['0x021003', 'vehicles']];
        }
        $meetingApplyId = $data['unique_id'] ?? '';
        //获取编辑前的申请详情
        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($meetingApplyId); //获取编辑前的申请会议
        $meetingInfo = $oldMeeting->toArray();
        if(empty($meetingInfo) || (isset($meetingInfo['false_delete']) && $meetingInfo['false_delete'] == 1)) {
            return ['code' => ['0x017035', 'meeting']];
        } else if(($oldMeeting->meeting_status !== 1 || $oldMeeting->meeting_apply_time != '') && $oldMeeting->attence_type == 1) {
            //待审核会议才可以被编辑
            return ['code' => ['0x017033', 'meeting']];
        }
        if ($oldMeeting->attence_type == 2 && ($oldMeeting->meeting_status !== 2 || $oldMeeting->meeting_begin_time <= date('Y-m-d H:i:s'))) {
             return ['code' => ['0x017033', 'meeting']];
        }

        $updateData = $data['data'] ?? [];
        if ($updateData) {
            // 获取方式
            $setting = $this->getBaseSetting();
            if (isset($setting['meeting_video_set']) && $setting['meeting_video_set'] == 0 && isset($updateData['attence_type']) && $updateData['attence_type'] == 1) {
                return ['code' => ['0x017036', 'meeting']];
            }
            if (isset($setting['meeting_video_set']) && $setting['meeting_video_set'] == 1) {
                 if (isset($updateData['attence_type']) && $updateData['attence_type'] == 0) {
                    $attenceType = 1;
                } else if (isset($updateData['attence_type']) && $updateData['attence_type'] == 1) {
                    $attenceType = 2;
                } else {
                    $attenceType = $oldMeeting->attence_type;
                }
            } else {
                $attenceType = 1;
            }
           
            if (isset($updateData['meeting_subject']) && empty($updateData['meeting_subject'])) {
                return ['code' => ['0x017009', 'meeting']];
            }
            if (isset($updateData['meeting_apply_user']) && empty($updateData['meeting_apply_user'])) {
                return ['code' => ['0x017025', 'meeting']];
            }
            if ($updateData['current_user_id'] != $oldMeeting->meeting_apply_user) {
                return ['code' => ['0x017022', 'meeting']];
            }
            if (isset($updateData['meeting_begin_time']) && empty($updateData['meeting_begin_time'])) {
                return ['code' => ['0x017010', 'meeting']];
            }
            if (isset($updateData['meeting_end_time']) && empty($updateData['meeting_end_time'])) {
                return ['code' => ['0x017011', 'meeting']];
            }
            if ($attenceType == 1 && $oldMeeting->attence_type == 1) {
                if (isset($updateData['meeting_room_id']) && empty($updateData['meeting_room_id'])) {
                    return ['code' => ['0x017007', 'meeting']];
                }
            } else if ($attenceType == 1 && $oldMeeting->attence_type != 1) {
                if ((isset($updateData['meeting_room_id']) && empty($updateData['meeting_room_id'])) || !isset($updateData['meeting_room_id'])) {
                    return ['code' => ['0x017007', 'meeting']];
                }
            }
            if ($attenceType == 2 && $oldMeeting->attence_type != 2) {
                if ((isset($updateData['interface_id']) && empty($updateData['interface_id'])) || !isset($updateData['interface_id'])) {
                    return ['code' => ['0x017034', 'meeting']];
                }
            } else if ($attenceType == 2 && $oldMeeting->attence_type == 2) {
                if (isset($updateData['interface_id']) && empty($updateData['interface_id'])) {
                    return ['code' => ['0x017034', 'meeting']];
                }
            }
            $updateData['meeting_apply_user'] = $updateData['current_user_id'];
            $updateData['meeting_begin_time'] = isset($updateData['meeting_begin_time']) && !empty($updateData['meeting_begin_time']) ? $updateData['meeting_begin_time'] : $oldMeeting->meeting_begin_time;
            $updateData['meeting_end_time'] = isset($updateData['meeting_end_time']) && !empty($updateData['meeting_end_time']) ? $updateData['meeting_end_time'] : $oldMeeting->meeting_end_time;
            $updateData['meeting_apply_user'] = isset($updateData['meeting_apply_user']) && !empty($updateData['meeting_apply_user']) ? $updateData['meeting_apply_user'] : $oldMeeting->meeting_apply_user;
            $updateData['meeting_subject'] = isset($updateData['meeting_subject']) && !empty($updateData['meeting_subject']) ? $updateData['meeting_subject'] : $oldMeeting->meeting_subject;
            $updateData['meeting_room_id'] = isset($updateData['meeting_room_id']) && !empty($updateData['meeting_room_id']) ? $updateData['meeting_room_id'] : $oldMeeting->meeting_room_id;
            $updateData['interface_id'] = isset($updateData['interface_id']) && !empty($updateData['interface_id']) ? $updateData['interface_id'] : $oldMeeting->interface_id;
            $updateData['interface_id'] = isset($updateData['interface_id']) && !empty($updateData['interface_id']) ? $updateData['interface_id'] : $oldMeeting->interface_id;
            $updateData['meeting_sign_wifi'] = isset($updateData['meeting_sign_wifi']) ? $updateData['meeting_sign_wifi'] : $oldMeeting->meeting_sign_wifi;
            
            $updateData['attence_type'] = $attenceType;

            unset($updateData['current_user_id']);
            $own = own();

            $return = $this->editMeeting($updateData, $meetingApplyId, $own, 'flow');
            if (($return && isset($return['code'])) || !$return) {
                return $return;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'meeting_apply',
                        'field_to' => 'meeting_apply_id',
                        'id_to'    => $meetingApplyId
                    ]
                ]
            ];
        } else {
            return ['code' => ['0x000003', 'common']];
        }
    }
    public function flowOutSendToDeleteMeeting($data) {
        if (empty($data)) {
            return ['code' => ['0x021003', 'vehicles']];
        }
        $meetingApplyId = $data['unique_id'] ?? '';
        $deleteData = $data['data'] ?? [];
        //获取编辑前的申请详情
        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($meetingApplyId); //获取编辑前的申请会议
        $meetingInfo = $oldMeeting->toArray();
        if(empty($meetingInfo) || (isset($meetingInfo['false_delete']) && $meetingInfo['false_delete'] == 1)) {
            return ['code' => ['0x017035', 'meeting']];
        }
        if ((isset($oldMeeting->meeting_apply_user) && $oldMeeting->meeting_apply_user != $deleteData['current_user_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        $own = own();
        $return = $this->parseMeetingDeletePermission($own, [], ['m_apply_id' => $meetingApplyId]);
        if ($return && isset($return['code'])) {
            return $return;
        }
        $deleteResult = $this->deleteOwnMeeting($meetingApplyId, $own['user_id']);
        if ($deleteResult && isset($deleteResult['code'])) {
            return $deleteResult;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'meeting_apply',
                    'field_to' => 'meeting_apply_id',
                    'id_to'    => $meetingApplyId
                ]
            ]
        ];
    }

    public function editMeeting($data, $mApplyId, $loginUserInfo, $from = '') {
        if (empty($data)) {
            return ['code' => ['0x017035', 'meeting']];
        }
        // 获取方式
        $setting = $this->getBaseSetting();
        if (isset($setting['meeting_video_set']) && $setting['meeting_video_set'] == 1) {
            $attenceType = $data['attence_type'] ?? 1;
        } else {
            $attenceType = 1;
        }
        switch ($attenceType) {
            case 1:
                $result = $this->editCommonMeeting($data, $mApplyId, $loginUserInfo, $from);
                break;
            case 2:
                $result = $this->editVideoMeeting($data, $mApplyId, $loginUserInfo);
                break;
            default:
                $result = $this->editCommonMeeting($data, $mApplyId, $loginUserInfo, $from);
                break;
        }
        return $result;
    }
    /**
     * @编辑会议申请
     * @param array $data
     * @param type $remindData
     * @param type $uploadFile
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function editCommonMeeting($data, $mApplyId, $loginUserInfo, $from)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }

        if ($data['meeting_begin_time'] >= $data['meeting_end_time']) {
            return ['code' => ['0x017012', 'meeting']];
        }
        $meetingJoinMember = $this->defaultValue('meeting_join_member', $data, null);

        $meetingexternaluser = '';
        if(isset($data['meeting_external_user']) && !empty($data['meeting_external_user'])) {
            $meetingexternaluser = $this->defaultValue('meeting_external_user', $data, null);
        }
        if(isset($data['external_user_id']) && !empty($data['external_user_id'])) {
            $meetingexternaluser = $this->defaultValue('external_user_id', $data, null);
        }
        $reminder = null;
        // 设置外部人员提醒方式
        if (isset($data['email']) && ($data['email'] == 0)) {
            $reminder = 0;
        }
        if (isset($data['phone']) && ($data['phone'] == 1)) {
            $reminder = 1;
        }
        if ((isset($data['phone']) && ($data['phone'] == 1)) && (isset($data['email']) && ($data['email'] == 0))) {
            $reminder = 2;
        }
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 0) && $data['external_reminder_type'] != '') {
            $reminder = 0;
        }
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 1)) {
            $reminder = 1;
        }
        if (isset($data['external_reminder_type']) && is_array($data['external_reminder_type'])) {
            if (count($data['external_reminder_type']) == 2) {
                $reminder = 2;
            } else {
               $reminder = $data['external_reminder_type'][0]; 
            }
        } else if (isset($data['external_reminder_type']) && count(explode(',', $data['external_reminder_type'])) == 2){
            $reminder = 2;
        }
        if(!empty($meetingJoinMember)) {
            if(is_array($meetingJoinMember)) {
                $meetingJoinMember = join(',', $meetingJoinMember);
            }else{
                $meetingJoinMember = $meetingJoinMember;
            }
        }else {
            $meetingJoinMember = '';
        }
        if(!empty($meetingexternaluser)) {
            if(is_array($meetingexternaluser)) {
                $meetingExternalUsers = join(',', $meetingexternaluser);
            }else{
                $meetingExternalUsers = $meetingexternaluser;
            }
        }else{
            $meetingExternalUsers = '';
        }
    
        if (isset($data['meeting_reminder_timing_h']) && isset($data['meeting_reminder_timing_m'])) {
            $data['reminder_timing'] = ((int)$data['meeting_reminder_timing_h'] * 60) + ((int)$data['meeting_reminder_timing_m']);
            $data['reminder_time'] = strtotime($data['meeting_begin_time']) - ($data['reminder_timing'] * 60);
            $data['reminder_time'] = date("Y-m-d H:i:s", $data['reminder_time']);
        }
        $meetingJoinMember = $this->defaultValue('meeting_join_member', $data, null);
        if(!empty($meetingJoinMember)) {
            if(is_array($meetingJoinMember)) {
                $meetingJoinMember = join(',', $meetingJoinMember);
            }
        }else{
            $meetingJoinMember = '';
        }
        $currentUser = isset($data['current_user_id']) ? $data['current_user_id'] : '';
        // 判断是否选择了签到
        $sign = 0;
        $signUser = '';
        if (isset($data['sign']) && $data['sign'] == 1) {
            $sign = $data['sign'];
            $signUser = isset($data['meeting_sign_user']) ? $data['meeting_sign_user'] : $currentUser;
        }
        $signType = 1;
        $sign_user = '';
        $sign_wifi = '';
        if ($from == 'flow') {
            if (isset($data['sign_type']) && $data['sign_type'] == 0) {
                $signType = 1;
                $sign_user = isset($data['meeting_sign_user']) ? $data['meeting_sign_user'] : $currentUser;
            }
            if (isset($data['sign_type']) && $data['sign_type'] == 1) {
                $signType = 2;
            }
            if (isset($data['sign_type']) && $data['sign_type'] == 2) {
                $signType = 3;
                $sign_wifi = isset($data['meeting_sign_wifi']) ? $data['meeting_sign_wifi'] : null;
                if (isset($data['sign']) && $data['sign'] == 1 && !$sign_wifi) {
                    return ['code' => ['0x017032', 'meeting']];
                }
            }
        } else {
            $signType = $data['sign_type'] ?? 1;
        }
        
        $meetingData = [
            'meeting_room_id'               => $data['meeting_room_id'],
            'meeting_subject'               => $data['meeting_subject'],
            'meeting_join_member'           => $meetingJoinMember,
            'meeting_other_join_member'     => $this->defaultValue('meeting_other_join_member', $data, null),
            'meeting_type'                  => $this->defaultValue('meeting_type', $data, null),
            'meeting_begin_time'            => $data['meeting_begin_time'],
            'meeting_end_time'              => $data['meeting_end_time'],
            'meeting_remark'                => $this->defaultValue('meeting_remark', $data, null),
            'sign_type'                     => $signType,
            'meeting_sign_wifi'             => isset($data['meeting_sign_wifi']) ? $data['meeting_sign_wifi'] : '',
            'meeting_external_user'         => $meetingExternalUsers,
            'meeting_reminder_time'         => $this->defaultValue('reminder_time', $data, null),
            'meeting_reminder_timing'       => $this->defaultValue('reminder_timing', $data, null),
            'external_reminder_type'        => $reminder,
            'sign'                          => isset($data['sign']) ? $data['sign'] : 0,
            'meeting_response'              => isset($data['meeting_response']) ? $data['meeting_response'] : 0,
            'meeting_sign_wifi'             => isset($data['meeting_sign_wifi']) ? $data['meeting_sign_wifi'] : null,
            'attence_type'                  => 1,
            'interface_id'                  => '',
            'meeting_status'                => 1,
            'meeting_apply_time'            => null
        ];
        if ((isset($data['meeting_sign_user']) && empty($data['meeting_sign_user'])) || !isset($data['meeting_sign_user'])) {
            $meetingData['meeting_sign_user'] = $data['meeting_apply_user'];
        }else {
            $meetingData['meeting_sign_user'] = $data['meeting_sign_user'];
        }

        $meetingData['conflict'] = str_replace([',' . $mApplyId, $mApplyId], '', $this->getConflictMeeting($meetingData['meeting_room_id'], $meetingData['meeting_begin_time'], $meetingData['meeting_end_time'])); // 检查配置
        $setInfo = $this->getBaseSetting([]);
        if (isset($setInfo['meeting_apply_set']) && $setInfo['meeting_apply_set'] == 0) {
            $meetingData['meeting_status'] = 2;
        }

        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId); //获取编辑前的申请会议

        if (!app($this->meetingApplyRepository)->editMeeting($meetingData, $mApplyId)) {
            return ['code' => ['0x000003', 'common']];
        }
        // 更新签到数据
        app($this->meetingExternalAttenceRepository)->deleteById($mApplyId);
        app($this->meetingAttenceRepository)->deleteDataById($mApplyId);

        if (isset($meetingData['meeting_external_user'])) {
            $external = [];
            $external['meeting_external_user'] = $meetingData['meeting_external_user'];
            $external['meeting_apply_id'] = $mApplyId;
            Queue::push(new SyncMeetingExternalJob($external));
        }
        if (!isset($data['meeting_response']) || (isset($data['meeting_response']) && $data['meeting_response'] == 0)) {
            if (isset($data['meeting_join_member']) && !empty($data['meeting_join_member'])) {
                $param = [];
                $param['meeting_join_member'] = $data['meeting_join_member'];
                $param['meeting_apply_id'] = $mApplyId;
                $param['sign_type'] = $oldMeeting->sign_type;
                Queue::push(new SyncMeetingJob($param));
            }
        }
        /**
         * 更新未编辑的冲突会议
         */
        if ($oldMeeting->meeting_room_id != $meetingData['meeting_room_id'] || $oldMeeting->meeting_begin_time != $meetingData['meeting_begin_time'] || $oldMeeting->meeting_end_time != $meetingData['meeting_end_time']) {
            $this->replaceOldMeetingConflictId($oldMeeting->meeting_room_id, $oldMeeting->meeting_begin_time, $oldMeeting->meeting_end_time, $mApplyId);
        }

        if ($meetingData['conflict']) {
            $this->updateMeetingConflictId($meetingData['conflict'], $mApplyId); //更新相应的冲突会议
        }

        if(isset($data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("meeting_apply", $mApplyId, $data['attachment_id']);
        }
        $meetingSortId = $meetingData['meeting_room_id'];
        $approvalUser = app($this->meetingRoomsRepository)->getApprovalUser($meetingSortId);
        $approvalUser = isset($approvalUser[0]['meeting_approvel_user']) ? $approvalUser[0]['meeting_approvel_user'] : '';
        $meetingInfo = app($this->meetingApplyRepository)->getMeetingOne($mApplyId);
        $meetingInfo = $meetingInfo[0] ?? [];
        $userName           = $loginUserInfo['user_name'];
        $getMeetingRoomInfo = $this->showRoom($meetingInfo['meeting_room_id']);
        $roomName           = $getMeetingRoomInfo['room_name'] ?? '';
        $applyTime          = date("Y-m-d H:i", time());
        $meetingTime   = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        $shareusers = explode(',',$meetingInfo['meeting_apply_user']);
        $signUser = explode(',',$meetingInfo['meeting_sign_user']);
        if (isset($setInfo['meeting_apply_set']) && $setInfo['meeting_apply_set'] == 1) {
            //消息提醒
            $userName           = $loginUserInfo['user_name'];
            $getMeetingRoomInfo = $this->showRoom($data['meeting_room_id']);
            $roomName           = $getMeetingRoomInfo['room_name'] ??'';
            $applyTime          = date("Y-m-d H:i", time());
            $toUser             = $approvalUser;
            $sendData['remindMark']     = 'meeting-submit';
            $sendData['fromUser']       = $loginUserInfo['user_id'];
            $sendData['toUser']         = $toUser;
            $sendData['contentParam']   = ['meetingSubject'=>$data['meeting_subject'], 'applyUser'=>$userName, 'meetingRoom'=>$roomName, 'applyTime'=>$applyTime];
            $sendData['stateParams']    = ['meeting_apply_id' => $mApplyId];
            Eoffice::sendMessage($sendData);
        } else {
            // 参加开始提醒
            if (!empty($meetingInfo['meeting_join_member'])) {

                if($meetingInfo['meeting_join_member'] == 'all') {
                    $toJoinUser = app($this->userService)->getAllUserIdString([]);
                }else{
                    $toJoinUser = $meetingInfo['meeting_join_member'];
                }
                $handelusers = explode(',',$toJoinUser);
                $meetingUsers = array_unique(array_merge($shareusers,$handelusers));
                if (!empty($signUser)) {
                    $meetingUsers = array_unique(array_merge($signUser,$meetingUsers));
                }
                $notAttenceUserArr = [];
                $notAttenceUser = app($this->meetingApplyRepository)->showNotAttenceUser($meetingInfo['meeting_apply_id']);
                if (!empty($notAttenceUser)) {
                    $notAttenceUserArr = array_column($notAttenceUser, 'meeting_attence_user');
                }
                $arr = array_diff(array_filter($meetingUsers), $notAttenceUserArr);
                $userId = implode(',',$arr);
                // 参加消息发送给参加人
                $sendDataForJoin['remindMark']   = 'meeting-join';
                $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                $sendDataForJoin['toUser']       = $userId;
                $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$roomName];
                $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $mApplyId, 'meeting_response' => $meetingInfo['meeting_response']];
                Eoffice::sendMessage($sendDataForJoin);
            } else {
                $userId = implode(',', array_unique(array_merge($shareusers, $signUser)));
                // 参加消息发送给参加人
                $sendDataForJoin['remindMark']   = 'meeting-join';
                $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                $sendDataForJoin['toUser']       = $userId;
                $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$roomName];
                $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $mApplyId, 'meeting_response' => $meetingInfo['meeting_response']];
                // $sendDataForJoin['meetingResponse']       = ['meeting_response' => $meetingInfo['meeting_response']];
                Eoffice::sendMessage($sendDataForJoin);
            }
            // 审批通过后开始提醒
            if(strtotime($meetingInfo['meeting_reminder_time']) < strtotime(date('Y-m-d H:i:s',time()))){

                if(!empty($meetingInfo['meeting_join_member'])) {
                    if($meetingInfo['meeting_join_member'] == 'all') {
                        $toJoinUser = app($this->userService)->getAllUserIdString([]);
                    }else{
                        $toJoinUser = $meetingInfo['meeting_join_member'];
                    }

                    $handelusers = explode(',',$toJoinUser);
                    $meetingUsers = array_unique(array_merge($shareusers,$handelusers));
                    if (!empty($signUser)) {
                        $meetingUsers = array_unique(array_merge($signUser,$meetingUsers));
                    }
                    $notAttenceUserArr = [];
                    $notAttenceUser = app($this->meetingApplyRepository)->showNotAttenceUser($meetingInfo['meeting_apply_id']);
                    if (!empty($notAttenceUser)) {
                        $notAttenceUserArr = array_column($notAttenceUser, 'meeting_attence_user');
                    }
                    $arr = array_diff(array_filter($meetingUsers), $notAttenceUserArr);
                    $userId = implode(',',$arr);
                    $roomInfo      = app($this->meetingRoomsRepository)->getDetail($meetingInfo['meeting_room_id']);
                    $meetingRoom   = $roomInfo->room_name;
                    // 参加消息发送给参加人
                    $sendDataForJoin['remindMark']   = 'meeting-start';
                    $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                    $sendDataForJoin['toUser']       = $userId;
                    $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingInfo['meeting_begin_time'], 'meetingRoom'=>$roomName];
                    $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $meetingInfo['meeting_apply_id']];
                    Eoffice::sendMessage($sendDataForJoin);
                } else {

                    $userId = implode(',', array_unique(array_merge($shareusers, $signUser)));
                    // 参加消息发送给参加人
                    $sendDataForJoin['remindMark']   = 'meeting-start';
                    $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                    $sendDataForJoin['toUser']       = $userId;
                    $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingInfo['meeting_begin_time'], 'meetingRoom'=>$roomName];
                    $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $meetingInfo['meeting_apply_id']];
                    Eoffice::sendMessage($sendDataForJoin);
                }
            }
            if (isset($meetingInfo['external_reminder_type']) && $meetingInfo['external_reminder_type'] !== null) {
                $this->parseExternalRemind($mApplyId);
            }
        }
        return true;
    }

    public function editVideoMeeting($data, $mApplyId, $loginUserInfo) {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }

        if ($data['meeting_begin_time'] >= $data['meeting_end_time']) {
            return ['code' => ['0x017012', 'meeting']];
        }
        $meetingJoinMember = $this->defaultValue('meeting_join_member', $data, null);

        $meetingexternaluser = '';
        if(isset($data['meeting_external_user']) && !empty($data['meeting_external_user'])) {
            $meetingexternaluser = $this->defaultValue('meeting_external_user', $data, null);
        }
        if(isset($data['external_user_id']) && !empty($data['external_user_id'])) {
            $meetingexternaluser = $this->defaultValue('external_user_id', $data, null);
        }
        $reminder = null;
        // 设置外部人员提醒方式
        if (isset($data['email']) && ($data['email'] == 0)) {
            $reminder = 0;
        }
        if (isset($data['phone']) && ($data['phone'] == 1)) {
            $reminder = 1;
        }
        if ((isset($data['phone']) && ($data['phone'] == 1)) && (isset($data['email']) && ($data['email'] == 0))) {
            $reminder = 2;
        }
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 0) && $data['external_reminder_type'] != '') {
            $reminder = 0;
        }
        if (isset($data['external_reminder_type']) && ($data['external_reminder_type'] == 1)) {
            $reminder = 1;
        }

        if (isset($data['external_reminder_type']) && is_array($data['external_reminder_type'])) {
            if (count($data['external_reminder_type']) == 2) {
                $reminder = 2;
            } else {
               $reminder = $data['external_reminder_type'][0]; 
            }
        } else if (isset($data['external_reminder_type']) && count(explode(',', $data['external_reminder_type'])) == 2){
            $reminder = 2;
        }
        if(!empty($meetingJoinMember)) {
            if(is_array($meetingJoinMember)) {
                $meetingJoinMember = join(',', $meetingJoinMember);
            }else{
                $meetingJoinMember = $meetingJoinMember;
            }
        }else {
            $meetingJoinMember = '';
        }
        if(!empty($meetingexternaluser)) {
            if(is_array($meetingexternaluser)) {
                $meetingExternalUsers = join(',', $meetingexternaluser);
            }else{
                $meetingExternalUsers = $meetingexternaluser;
            }
        }else{
            $meetingExternalUsers = '';
        }
        if (isset($data['meeting_reminder_timing_h']) && isset($data['meeting_reminder_timing_m'])) {
            $data['reminder_timing'] = ((int)$data['meeting_reminder_timing_h'] * 60) + ((int)$data['meeting_reminder_timing_m']);
            $data['reminder_time'] = strtotime($data['meeting_begin_time']) - ($data['reminder_timing'] * 60);
            $data['reminder_time'] = date("Y-m-d H:i:s", $data['reminder_time']);
        }
        $meetingData = [
            'meeting_room_id'               => 0,
            'meeting_subject'               => $data['meeting_subject'],
            'meeting_join_member'           => $meetingJoinMember,
            'meeting_other_join_member'     => $this->defaultValue('meeting_other_join_member', $data, null),
            'meeting_type'                  => $this->defaultValue('meeting_type', $data, null),
            'meeting_begin_time'            => $data['meeting_begin_time'],
            'meeting_end_time'              => $data['meeting_end_time'],
            'meeting_remark'                => $this->defaultValue('meeting_remark', $data, null),
            'sign_type'                     => '',
            'meeting_sign_wifi'             => '',
            'meeting_external_user'         => $meetingExternalUsers,
            'meeting_reminder_time'         => $this->defaultValue('reminder_time', $data, null),
            'meeting_reminder_timing'       => $this->defaultValue('reminder_timing', $data, null),
            'external_reminder_type'        => $reminder,
            'sign'                          => 0,
            'meeting_response'              => isset($data['meeting_response']) ? $data['meeting_response'] : 0,
            'meeting_status'                => 2,
            'attence_type'                  => $data['attence_type'] ?? 2,
            'interface_id'                  => $data['interface_id'] ?? '',
            'meeting_sign_user'             => '',
            'meeting_apply_time'            => null
        ];

        $oldMeeting = app($this->meetingApplyRepository)->getDetail($mApplyId); //获取编辑前的申请会议

        $oldMeetingInfo = json_decode($oldMeeting->meeting_video_info ?? '', true);
        
        $meetingInfoList = $oldMeetingInfo['meeting_info_list'] ?? [];
        $meetingId = $meetingInfoList[0]['meeting_id'] ?? '';
        
        
        if ($oldMeetingInfo) {
            $conferenceParams = [
                'meeting_id' => $meetingId,
                'meeting_name' => $data['meeting_subject'],
                'meeting_begin_time' => $data['meeting_begin_time'],
                'meeting_end_time' => $data['meeting_end_time'],
                'meeting_apply_user' => $data['meeting_apply_user']
            ];
            // 编辑视频会议
            $video = app($this->vedioConferenceService)->modifyVideoconference(['interface_id' => $data['interface_id']], $conferenceParams);
        } else {
            // 调用新建视频会议
            $video = app($this->vedioConferenceService)->createVideoconference(['interface_id' => $data['interface_id']], ['meeting_name' => $data['meeting_subject'], 'meeting_begin_time' => $data['meeting_begin_time'], 'meeting_end_time' => $data['meeting_end_time'], 'meeting_apply_user' => $data['meeting_apply_user']]);
        }
        
        if (isset($video['code'])) {
            return $video;
        }
        if ($oldMeetingInfo) {
            if ($video) {
                // 解析视频会议信息
                $newInfo = $this->parseVideoInfo($video, $oldMeetingInfo);
                if ($newInfo) {
                    $meetingData['meeting_video_info'] = $newInfo;
                }
            } else {
                $meetingData['meeting_video_info'] = $oldMeeting->meeting_video_info ?? '';
            }
        } else {
            $meetingData['meeting_video_info'] = $video;
        }
        
        if (!app($this->meetingApplyRepository)->editMeeting($meetingData, $mApplyId)) {
            return ['code' => ['0x000003', 'common']];
        }
        if(isset($data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("meeting_apply", $mApplyId, $data['attachment_id']);
        }
        $meetingData['meeting_apply_user'] = $loginUserInfo['user_id'];
        $meetingData['meeting_apply_id'] = $mApplyId;
        $meetingData['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$mApplyId]);

        if ($oldMeeting->attence_type != $data['attence_type']) {
            $this->emitCalendar($meetingData, 'add');
        } else {
            $this->emitCalendar($meetingData, 'update');
        }
        
        // 更新签到数据
        app($this->meetingExternalAttenceRepository)->deleteById($mApplyId);
        app($this->meetingAttenceRepository)->deleteDataById($mApplyId);

        if (isset($meetingData['meeting_external_user'])) {
            $external = [];
            $external['meeting_external_user'] = $meetingData['meeting_external_user'];
            $external['meeting_apply_id'] = $mApplyId;
            Queue::push(new SyncMeetingExternalJob($external));
        }
        if (isset($data['meeting_join_member']) && !empty($data['meeting_join_member'])) {
            $param = [];
            $param['meeting_join_member'] = $data['meeting_join_member'];
            $param['meeting_apply_id'] = $mApplyId;
            $param['sign_type'] = $oldMeeting->sign_type;
            Queue::push(new SyncMeetingJob($param));
        }

        
        $meetingInfo = app($this->meetingApplyRepository)->getMeetingOne($mApplyId);
        $meetingInfo = $meetingInfo[0] ?? [];
        $applyTime          = date("Y-m-d H:i", time());
        $meetingTime   = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        if (isset($meetingInfo['meeting_join_member']) && $meetingInfo['meeting_join_member'] == 'all') {
            $joinMember = app($this->userService)->getAllUserIdString();
        } else {
           $joinMember = $meetingInfo['meeting_join_member'];
        }
        $userId = array_unique(array_filter(array_merge(explode(',', $joinMember), [$meetingInfo['meeting_apply_user']])));
        $meetingTime   = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        $video = json_decode($meetingData['meeting_video_info'], true);
        $meetingUrl = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['join_url'] : '';
        $meetingID = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['meeting_id'] : '';
        $meetingCode = $video['meeting_info_list'] ? $video['meeting_info_list'][0]['meeting_code'] : '';
        // 参加消息发送给参加人
        $sendDataForJoin = [];
        $sendDataForJoin = [
            'remindMark'    => 'meeting-video',
            'fromUser'      => $loginUserInfo['user_id'],
            'toUser'        => $userId,
            'contentParam'  => [
                'meetingSubject'=>$meetingInfo['meeting_subject'],
                'meetingTime'=>$meetingTime,
                'meetingUrl'=> $meetingUrl,
                'meetingCode'=> $meetingCode,
            ],
            'stateParams'   => ['meeting_apply_id' => $mApplyId, 'meeting_response' => $meetingInfo['meeting_response']]
        ];
        Eoffice::sendMessage($sendDataForJoin);
        if (isset($meetingInfo['external_reminder_type']) && $meetingInfo['external_reminder_type'] !== null) {
            $this->parseExternalRemind($meetingInfo['meeting_apply_id']);
        }
        return true;
    }
    private function parseVideoInfo($video, $oldVideoInfo) {
        $videoArr = json_decode($video, true);
        if ($videoArr) {
            $meetingInfoList = $oldVideoInfo['meeting_info_list'] ?? [];
            $newVideoArr = $videoArr['meeting_info_list'] ?? [];
            $meetingInfoList[0]['meeting_id'] = isset($newVideoArr[0]['meeting_id']) ? $newVideoArr[0]['meeting_id'] : $meetingInfoList['meeting_id'];
            $meetingInfoList[0]['meeting_code'] = isset($newVideoArr[0]['meeting_code']) ? $newVideoArr[0]['meeting_code'] : $meetingInfoList[0]['meeting_code'];
            $meetingInfoList['meeting_number'] = isset($videoArr['meeting_number']) ? $videoArr['meeting_number'] : $oldVideoInfo['meeting_member'];
            $oldVideoInfo['meeting_info_list'] = $meetingInfoList;
            return json_encode($oldVideoInfo);
        }
        return '';
    }

    private function parseExternalRemind($mApplyId) {
        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        $meeting_subject = $oldMeeting->meeting_subject;
        $room = app($this->meetingRoomsRepository)->getDetail($oldMeeting->meeting_room_id);
        $meeting_room_name  = $room ? $room->room_name : '';
        $meeting_begin_time = $oldMeeting->meeting_begin_time;
        $meeting_end_time   = $oldMeeting->meeting_end_time;
        if($oldMeeting->meeting_external_user == 'all') {
            $externalUserInfo = app($this->meetingExternalUserRepository)->getExternalUserId();
            $externalUserInfo = array_column($externalUserInfo,"external_user_id");
        }else {
            $externalUserInfo = explode(',', $oldMeeting->meeting_external_user);
        }
        // 获取外部人员的手机号
        $externalUserList = app($this->meetingExternalUserRepository)->getDetail($externalUserInfo)->toArray();

        $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
        $param['externalUserList'] = $externalUserList;
        $param['meeting_begin_time'] = $meeting_begin_time;
        $param['meeting_end_time'] = $meeting_end_time;
        $param['meeting_room_name'] = $meeting_room_name;
        $param['meeting_apply_id'] = $mApplyId;
        $param['approveResult'] = true;
        $param['oldMeeting'] = $oldMeeting;
        $param['meeting_subject'] = $meeting_subject;
        $param['domain'] = $domain;
        Queue::push(new SyncMeetingExternalSendJob($param));
    }
    /**
     * @获取我的会议的会议详情
     * @param type $mApplyId
     * @return 我的会议的会议详情 | array
     */
    public function showOwnMeeting($from, $mApplyId, $loginUserInfo)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $from = $from['from'] ?? '';
        $userId = $loginUserInfo['user_id'];
        $permission = [];
        // 判断有没有查看权限
        if ($from && $from == 'approve') {
            $permission = app($this->meetingApplyRepository)->checkApprovePermission($mApplyId, $loginUserInfo['user_id']);
        } else if ($from == 'cancel') {
            $permission = app($this->meetingApplyRepository)->checkCancelViewPermission($mApplyId, $userId);
        } else {
            $permission = app($this->meetingApplyRepository)->checkViewPermission($mApplyId, $userId);
        }
        if (empty($permission)) {
            return ['code' => ['0x000006','common']];
        }
        // 获取是否审批删除
        $detail = app($this->meetingApplyRepository)->getDetail($mApplyId);
        if (!$detail) {
            return ['code' => ['0x000006','common']];
        }
        // 判断有没有权限看详情
        $permission = app($this->meetingApplyRepository)->checkViewPermission($mApplyId, $userId);
        // 判断有没有批准条件
        if (isset($detail->attence_type) && $detail->attence_type == 1) {
            $roomInfo = app($this->meetingRoomsRepository)->getDetail($detail->meeting_room_id);
            if (!$roomInfo) {
                return ['code' => ['0x000006','common']];
            }
            $sortId = $roomInfo->meeting_sort_id ?? '';
            $sortInfo = app($this->meetingSortRepository)->getDetail($sortId);
            $approveUser = $sortInfo->meeting_approvel_user ?? '';
            if ($approveUser == 'all') {
                $approveUser = app($this->userService)->getAllUserIdString();
            }
            if (count($permission) <= 0 && !in_array($loginUserInfo['user_id'], explode(',', $approveUser))) {
                return ['code' => ['0x000006','common']];
            }
        }

        if ($detail->false_delete == 2) {
            $from = 'approval-delete';
        }
        if ($detail->false_delete == 1) {
            $from = 'mine-delete';
        }
        if ($from && $from == 'cancel') {
            $showMeeting = app($this->meetingApplyRepository)->showCancelMeeting($mApplyId, $from);
        } else {
            $showMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId, $from);
        }

        if(!$showMeeting) {
            return ['code' => ['0x000006','common']];
        }
        $time = date("Y-m-d H:i:s", time());
        // 判断时间自动结束和开始
        if (isset($showMeeting->meeting_end_time) && $showMeeting->meeting_end_time <= $time && ($showMeeting->meeting_status == 2|| $showMeeting->meeting_status == 4)) {
            $showMeeting->meeting_status = 5;
        }else if (isset($showMeeting->meeting_begin_time) && ($showMeeting->meeting_begin_time <= $time && $showMeeting->meeting_end_time > $time)  && $showMeeting->meeting_status == 2) {
            $showMeeting->meeting_status = 4;
        }
        $attenceUser = app($this->meetingApplyRepository)->showAttenceUser($mApplyId);
        $meetingStatus = app($this->meetingApplyRepository)->getMeetingAttenceStatus($mApplyId,$userId);
        $meetingSignInUser = app($this->meetingAttenceRepository)->getSignInUser($mApplyId);
        $meetingNotSignUser = app($this->meetingAttenceRepository)->getNotSignInUser($mApplyId);
        $showMeeting->meeting_attence_status = isset($meetingStatus[0]['meeting_attence_status']) ? $meetingStatus[0]['meeting_attence_status'] : '';
        $showMeeting->meeting_sign_status = isset($meetingStatus[0]['meeting_sign_status']) ? $meetingStatus[0]['meeting_sign_status'] : '';
        $showMeeting->meeting_attence_id  = isset($meetingStatus[0]['meeting_attence_id']) ? $meetingStatus[0]['meeting_attence_id'] : '';
        $showMeeting->meeting_sign_time   = isset($meetingStatus[0]['meeting_sign_time']) ? $meetingStatus[0]['meeting_sign_time'] : '';
        $showMeeting->attence_type_name = $this->transField('attence_type', $showMeeting->attence_type);
        $meetingVideoInfo = json_decode($showMeeting->meeting_video_info ?? '', true);
        $showMeeting->join_url = isset($meetingVideoInfo['meeting_info_list']) ? $meetingVideoInfo['meeting_info_list'][0]['join_url'] : '';
        $showMeeting->meeting_code = isset($meetingVideoInfo['meeting_info_list']) ? $meetingVideoInfo['meeting_info_list'][0]['meeting_code'] : '';
        $attenceUsers = [];
        // 参加人员
        if(!empty($attenceUser)) {
            foreach($attenceUser as $key => $value) {
                    $attenceUsers[] = $value['meeting_attence_user'];
            }
        }
        if(!empty($attenceUsers) && isset($attenceUsers)) {
            $showMeeting->meeting_attence_user = $attenceUsers;
        }else{
            $showMeeting->meeting_attence_user = '';

        }
        $joinMember = [];
        if (isset($showMeeting->meeting_join_member) && $showMeeting->meeting_join_member != 'all') {
            $joinMember = explode(',', $showMeeting->meeting_join_member);
        }else if (isset($showMeeting->meeting_join_member) && $showMeeting->meeting_join_member == 'all') {
            if ($attenceUsers) {
                $allUserIdString = app($this->userService)->getAllUserIdString([]);
                $joinMember = explode(',', $allUserIdString);
            } else {
                $joinMember = 'all';
            }
        }
        if ($joinMember != 'all') {
            $refuseUsers = array_values(array_diff($joinMember, $attenceUsers));
        }else{
            $refuseUsers = 'all';
        }


        //拒绝参加人员
        if(!empty($refuseUsers) && isset($refuseUsers)) {
            $showMeeting->meeting_refuse_user = $refuseUsers;
        }else {
            $showMeeting->meeting_refuse_user = "";
        }

        //已签到人员
        if(!empty($meetingSignInUser)) {
            foreach($meetingSignInUser as $key => $value) {
                    $meetingSignInUsers[] = $value->meeting_attence_user;
            }
        }
        if (isset($meetingSignInUsers) && !empty($meetingSignInUsers)) {
            $showMeeting->meeting_sign_in_user = $meetingSignInUsers;
        }else{
            $showMeeting->meeting_sign_in_user = '';
        }

        // 未签到人员
        if(!empty($meetingNotSignUser)) {
            foreach($meetingNotSignUser as $key => $value) {
                    $meetingNotSignUsers[] = $value->meeting_attence_user;
            }
        }
        if (!empty($meetingNotSignUsers) && isset($meetingNotSignUsers)) {
            $showMeeting->meeting_not_sign_user = $meetingNotSignUsers;
        }else{
            $showMeeting->meeting_not_sign_user = '';
        }
        $selectField = $this->meetingSelectField;
        foreach ($selectField as $field => $id) {
            $showMeeting->meeting_type_name     = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id,$showMeeting->meeting_type);
        }
        $room                                   = app($this->meetingRoomsRepository)->getDetail($showMeeting->meeting_room_id);
        $signType                               = app($this->meetingRoomsRepository)->getDetail($showMeeting->sign_type);
        $wifiName                               = app($this->meetingSignWifiRepository)->getDetail($showMeeting->meeting_sign_wifi);
        $sort                                   = app($this->meetingSortRepository)->getDetail($showMeeting->meeting_sort_id);
        $showMeeting->meeting_room_name         = $room ? $room->room_name : '';
        $showMeeting->meeting_sort_name         = $sort ? $sort->meeting_sort_name : '';
        $showMeeting->meeting_apply_user_name   = get_user_simple_attr($showMeeting->meeting_apply_user);
        $showMeeting->meeting_attence_user_name = get_user_simple_attr($showMeeting->meeting_attence_user);
        $showMeeting->meeting_refuse_user_name  = get_user_simple_attr($showMeeting->meeting_refuse_user);
        $showMeeting->meeting_sign_in_user_name = get_user_simple_attr($showMeeting->meeting_sign_in_user);
        $showMeeting->meeting_not_sign_user_name    = get_user_simple_attr($showMeeting->meeting_not_sign_user);
        $showMeeting->meeting_sign_user_name    = get_user_simple_attr($showMeeting->meeting_sign_user);
        $showMeeting->sign_wifi_name            = $wifiName ? $wifiName->meeting_wifi_name : '';
        $showMeeting->sign_wifi_mac             = $wifiName ? $wifiName->meeting_wifi_mac : '';
        // 获取未签到外部人员,和已签到外部人员,随后展示信息
        $showMeeting->meeting_external_not_sign = app($this->meetingExternalUserRepository)->getNotSignInExternalUser($mApplyId);
        // 已签到的外部人员
        $showMeeting->meeting_external_have_sign = app($this->meetingExternalUserRepository)->getHaveSignInExternalUser($mApplyId);
        if (($showMeeting->meeting_external_user != 'all') && !empty($showMeeting->meeting_external_user)) {
            $showMeeting->external_user_name  = app($this->meetingExternalUserRepository)->getExternalUserName($showMeeting->meeting_external_user);
        }
        if(!empty($showMeeting->meeting_join_member) && $showMeeting->meeting_join_member != 'all') {
            $showMeeting->meeting_join_member   = explode(',', $showMeeting->meeting_join_member);
        }
        if(!empty($showMeeting->meeting_approvel_user) && $showMeeting->meeting_approvel_user != 'all') {
            $showMeeting->meeting_approvel_user   = explode(',', $showMeeting->meeting_approvel_user);
        }
        if(!empty($showMeeting->meeting_external_not_sign) && $showMeeting->meeting_external_not_sign != 'all') {
            $showMeeting->meeting_external_not_sign   = explode(',', $showMeeting->meeting_external_not_sign);
        }
        if(!empty($showMeeting->meeting_external_have_sign) && $showMeeting->meeting_external_have_sign != 'all') {
            $showMeeting->meeting_external_have_sign   = explode(',', $showMeeting->meeting_external_have_sign);
        }
        $showMeeting->attachment_id             = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$mApplyId]);

        switch($showMeeting->sign_type) {
            case 1;
                $showMeeting->sign_type_name = trans('meeting.personal_sign');
                break;
            case 2;
                $showMeeting->sign_type_name = trans('meeting.qr_sign');
                break;
            case 3;
                $showMeeting->sign_type_name = trans('meeting.wifi_sign');
                break;
        }
        switch($showMeeting->meeting_sign_status) {
            case 1;
                $showMeeting->meeting_sign_status_name = trans('meeting.has_sign');
                break;
            case 0;
                $showMeeting->meeting_sign_status_name = trans('meeting.not_sign');
                break;
        }
        $qrCodeInfo = [

            'meeting_apply_id' => $mApplyId,
            'user_id' => $userId
        ];
        $showMeeting->qrCodeInfo = $qrCodeInfo;
        $meeting_external_user = $showMeeting->meeting_external_user;
        if ($meeting_external_user != 'all') {
            $showMeeting->external_user_id = explode(",", $meeting_external_user);
            $externalCount = count(array_filter($showMeeting->external_user_id));
        }else{
            $showMeeting->external_user_id =$meeting_external_user;
            $externalUser = array_filter(app($this->meetingExternalUserRepository)->getExternalUserIdString());
            $externalCount = count($externalUser);
        }

        $showMeeting->meeting_external_user_name = explode(",", $showMeeting->external_user_name);
        if (isset($showMeeting->meeting_join_member) && $showMeeting->meeting_join_member != "all") {
            if (!empty($showMeeting->meeting_join_member)) {
                $showMeeting->meeting_join_member_count = count($showMeeting->meeting_join_member);
            }else{
                $showMeeting->meeting_join_member_count = 0;
            }
        } elseif (isset($showMeeting->meeting_join_member) && $showMeeting->meeting_join_member == "all") {
            $allJoinMember = app($this->userService)->getAllUserIdString();
            $showMeeting->meeting_join_member_count = count(explode(',', $allJoinMember));
        }
        $showMeeting->meeting_join_member_count += $externalCount;
        return $showMeeting;

    }
    /**
     * @获取审批会议的会议详情
     * @param type $mApplyId
     * @return 审批会议的会议详情 | array
     */
    public function showApproveMeeting($mApplyId, $loginUserInfo)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $loginUserInfo['flag'] = 2;

        // 判断是否具有审批权限
        $permission = app($this->meetingApplyRepository)->checkApprovePermission($mApplyId, $loginUserInfo['user_id']);
        if (empty($permission)) {
            return ['code' => ['0x000006','common']];
        }
        // 获取是否审批删除
        $detail = app($this->meetingApplyRepository)->getDetail($mApplyId);
        if (!$detail) {
            return ['code' => ['0x000006','common']];
        }
        // 判断有没有批准条件
        $roomInfo = app($this->meetingRoomsRepository)->getDetail($detail->meeting_room_id);
        if (!$roomInfo) {
            return ['code' => ['0x000006','common']];
        }
        $sortId = $roomInfo->meeting_sort_id ?? '';
        $sortInfo = app($this->meetingSortRepository)->getDetail($sortId);
        $approveUser = $sortInfo->meeting_approvel_user ?? '';
        if ($approveUser == 'all') {
            $approveUser = app($this->userService)->getAllUserIdString();
        }
        if (!in_array($loginUserInfo['user_id'], explode(',', $approveUser))) {
            return ['code' => ['0x000006','common']];
        }
        $loginUserInfo['flag'] = 2;

        $from = '';
        if ($detail->false_delete == 2) {
            $from = 'approval';
        } else if ($detail->false_delete == 1) {
            $from = 'mine-delete';
        }

        $showMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId, $from);
        if (!$showMeeting) {
            return ['code' => ['0x000006','common']];
        }
        $selectField = $this->meetingSelectField;
        foreach ($selectField as $field => $id) {
            $showMeeting->meeting_type_name     = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id,$showMeeting->meeting_type);
        }
        $room                                   = app($this->meetingRoomsRepository)->getDetail($showMeeting->meeting_room_id);
        $sort                                   = app($this->meetingSortRepository)->getDetail($showMeeting->meeting_sort_id);
        $showMeeting->meeting_room_name         = $room ? $room->room_name : '';
        $showMeeting->meeting_sort_name         = $sort ? $sort->meeting_sort_name : '';
        $showMeeting->meeting_apply_user_name   = get_user_simple_attr($showMeeting->meeting_apply_user);
        if(!empty($showMeeting->meeting_join_member) && $showMeeting->meeting_join_member != 'all') {
            $showMeeting->meeting_join_member       = explode(',', $showMeeting->meeting_join_member);
        }
        $showMeeting->attachment_id             = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$mApplyId]);
        $meeting_external_user = $showMeeting->meeting_external_user;
        $showMeeting->meeting_external_user = $meeting_external_user != 'all' ? explode(",", $meeting_external_user) : $meeting_external_user;
        return $showMeeting;
    }

    /**
     * 调用新浪接口将长链接转为短链接
     * @param  string        $source    申请应用的AppKey
     * @param  array|string  $url_long  长链接，支持多个转换（需要先执行urlencode)
     * @return array
     */
    public function getSinaShortUrl($source, $url_long){
        // 参数检查
        if(empty($source) || !$url_long){
            return false;
        }
        // 参数处理，字符串转为数组
        if(!is_array($url_long)){
            $url_long = array($url_long);
        }
        // 拼接url_long参数请求格式
        $url_param = array_map(function($value){
            return '&url_long='.urlencode($value);
        }, $url_long);
        $url_param = implode('', $url_param);
        // 新浪生成短链接接口
        $api = 'http://api.t.sina.com.cn/short_url/shorten.json';
        // 请求url
        $request_url = sprintf($api.'?source=%s%s', $source, $url_param);
        $result = array();
        // 执行请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $request_url);
        $data = curl_exec($ch);
        if($error=curl_errno($ch)){
            return false;
        }
        curl_close($ch);
        $result = json_decode($data, true);
        return $result;
    }

    /**
     * @批准会议
     * @param type $data
     * @param type $remindData
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function approveMeeting($data, $mApplyId, $loginUserInfo)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }

        $approveData = [
            'meeting_approval_opinion'  => $this->defaultValue('meeting_approval_opinion',$data,''),
            'meeting_status'            => 2,
            'meeting_approval_user'         => $loginUserInfo['user_id']
        ];

        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        $meeting_subject = $oldMeeting->meeting_subject;
        $room = app($this->meetingRoomsRepository)->getDetail($oldMeeting->meeting_room_id);
        $meeting_room_name  = $room ? $room->room_name : '';
        $meeting_begin_time = $oldMeeting->meeting_begin_time;
        $meeting_end_time   = $oldMeeting->meeting_end_time;
        if (!($oldMeeting->meeting_status == 2 && $oldMeeting->conflict != '') && $oldMeeting->meeting_status != 1) {
            return ['code' => ['0x017015', 'meeting']];
        }
        if($oldMeeting->meeting_external_user == 'all') {
            $externalUserInfo = app($this->meetingExternalUserRepository)->getExternalUserId();
            $externalUserInfo = array_column($externalUserInfo,"external_user_id");
        }else {
            $externalUserInfo = explode(',', $oldMeeting->meeting_external_user);
        }
        // 获取外部人员的手机号
        $externalUserList = app($this->meetingExternalUserRepository)->getDetail($externalUserInfo)->toArray();

        $approveResult = app($this->meetingApplyRepository)->editMeeting($approveData, $mApplyId);
        if (!$approveResult) {
            return ['code' => ['0x000003', 'common']];
        }
        $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
        $param = [];
        if (isset($oldMeeting->external_reminder_type) && $oldMeeting->external_reminder_type !== null) {
            $param['externalUserList'] = $externalUserList;
            $param['approveResult'] = $approveResult;
            $param['meeting_begin_time'] = $meeting_begin_time;
            $param['meeting_end_time'] = $meeting_end_time;
            $param['meeting_room_name'] = $meeting_room_name;
            $param['meeting_apply_id'] = $mApplyId;
            $param['approveResult'] = $approveResult;
            $param['oldMeeting'] = $oldMeeting;
            $param['meeting_subject'] = $meeting_subject;
            $param['domain'] = $domain;
            Queue::push(new SyncMeetingExternalSendJob($param));
        }

        //发送消息提醒
        $meetingInfo   = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        $meetingInfo['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$mApplyId]);
        $meetingName   = $meetingInfo['meeting_subject'] ?? '';
        $applyUserName = get_user_simple_attr($meetingInfo['meeting_apply_user']);
        $roomInfo      = app($this->meetingRoomsRepository)->getDetail($meetingInfo['meeting_room_id']);
        $meetingRoom   = $roomInfo->room_name;
        $userName      = $loginUserInfo['user_name'];
        $applyUserId   = $meetingInfo['meeting_apply_user'];
        $meetingTime   = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        $shareusers = explode(',',$meetingInfo['meeting_apply_user']);
        $signUser = explode(',',$meetingInfo['meeting_sign_user']);
        if(!empty($meetingInfo['meeting_join_member'])) {
            if($meetingInfo['meeting_join_member'] == 'all') {
                $toJoinUser = app($this->userService)->getAllUserIdString([]);
            }else{
                $toJoinUser = $meetingInfo['meeting_join_member'];
            }
            $handelusers = explode(',',$toJoinUser);
            $meetingUsers = array_unique(array_merge($shareusers,$handelusers));
            if (!empty($signUser)) {
                $meetingUsers = array_unique(array_merge($signUser,$meetingUsers));
            }
            $notAttenceUserArr = [];
            $notAttenceUser = app($this->meetingApplyRepository)->showNotAttenceUser($meetingInfo['meeting_apply_id']);
            if (!empty($notAttenceUser)) {
                $notAttenceUserArr = array_column($notAttenceUser, 'meeting_attence_user');
            }
            $arr = array_diff(array_filter($meetingUsers), $notAttenceUserArr);
            $userId = implode(',',$arr);
            // 参加消息发送给参加人
            $sendDataForJoin['remindMark']   = 'meeting-join';
            $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
            $sendDataForJoin['toUser']       = $userId;
            $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$meetingRoom];
            $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $mApplyId, 'meeting_response' => $meetingInfo['meeting_response']];
            // $sendDataForJoin['meetingResponse']       = ['meeting_response' => $meetingInfo['meeting_response']];
            Eoffice::sendMessage($sendDataForJoin);
        } else {
            $userId = implode(',', array_unique(array_merge($shareusers, $signUser)));
            // 参加消息发送给参加人
            $sendDataForJoin['remindMark']   = 'meeting-join';
            $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
            $sendDataForJoin['toUser']       = $userId;
            $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$meetingRoom];
            $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $mApplyId, 'meeting_response' => $meetingInfo['meeting_response']];
            // $sendDataForJoin['meetingResponse']       = ['meeting_response' => $meetingInfo['meeting_response']];
            Eoffice::sendMessage($sendDataForJoin);
        }
        // 审批通过后开始提醒
        if(strtotime($meetingInfo['meeting_reminder_time']) < strtotime(date('Y-m-d H:i:s',time()))){

            if(!empty($meetingInfo['meeting_join_member'])) {
                if($meetingInfo['meeting_join_member'] == 'all') {
                    $toJoinUser = app($this->userService)->getAllUserIdString([]);
                }else{
                    $toJoinUser = $meetingInfo['meeting_join_member'];
                }

                $handelusers = explode(',',$toJoinUser);
                $meetingUsers = array_unique(array_merge($shareusers,$handelusers));
                if (!empty($signUser)) {
                    $meetingUsers = array_unique(array_merge($signUser,$meetingUsers));
                }
                $notAttenceUserArr = [];
                $notAttenceUser = app($this->meetingApplyRepository)->showNotAttenceUser($meetingInfo['meeting_apply_id']);
                if (!empty($notAttenceUser)) {
                    $notAttenceUserArr = array_column($notAttenceUser, 'meeting_attence_user');
                }
                $arr = array_diff(array_filter($meetingUsers), $notAttenceUserArr);
                $userId = implode(',',$arr);
                $roomInfo      = app($this->meetingRoomsRepository)->getDetail($meetingInfo['meeting_room_id']);
                $meetingRoom   = $roomInfo->room_name;
                // 参加消息发送给参加人
                $sendDataForJoin['remindMark']   = 'meeting-start';
                $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                $sendDataForJoin['toUser']       = $userId;
                $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingInfo['meeting_begin_time'], 'meetingRoom'=>$meetingRoom];
                $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $meetingInfo['meeting_apply_id']];
                Eoffice::sendMessage($sendDataForJoin);
            } else {
                $userId = implode(',', array_unique(array_merge($shareusers, $signUser)));
                // 参加消息发送给参加人
                $sendDataForJoin['remindMark']   = 'meeting-start';
                $sendDataForJoin['fromUser']     = $loginUserInfo['user_id'];
                $sendDataForJoin['toUser']       = $userId;
                $sendDataForJoin['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingInfo['meeting_begin_time'], 'meetingRoom'=>$meetingRoom];
                $sendDataForJoin['stateParams']  = ['meeting_apply_id' => $meetingInfo['meeting_apply_id']];
                Eoffice::sendMessage($sendDataForJoin);
            }
        }

        // 批准消息发送给申请人
        $sendData['remindMark']   = 'meeting-pass';
        $sendData['fromUser']     = $loginUserInfo['user_id'];
        $sendData['toUser']       = $applyUserId;
        $sendData['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$meetingRoom, 'userName'=>$userName];
        $sendData['stateParams']  = ['meeting_apply_id' => $mApplyId, 'pass'=>1];
        Eoffice::sendMessage($sendData);
        if (isset($meetingInfo['meeting_response']) && $meetingInfo['meeting_response'] != 1) {
            // 外发到日程模块 --开始--
            $calendarData = [
                'calendar_content' => $meetingInfo['meeting_subject'],
                'handle_user'      => explode(',', $userId),
                'calendar_begin'   => $meeting_begin_time,
                'calendar_end'     => $meeting_end_time,
                'calendar_remark'  => $meetingInfo['meeting_remark'] ?? '',
                'attachment_id'    => $meetingInfo['attachment_id'] ?? ''
            ];
            $relationData = [
                'source_id'     => $mApplyId,
                'source_from'   => 'meeting-join',
                'source_title'  => $meetingInfo['meeting_subject'],
                'source_params' => ['meeting_apply_id' => $mApplyId]
            ];
            app($this->calendarService)->emit($calendarData, $relationData, $meetingInfo['meeting_apply_user']);
            // 外发到日程模块 --结束--
        }
        return true;
    }

    /**
     * 异步发送邮件短信通知
     * @param  [type] $param [description]
     * @return [type] bool   [true]
     */
    public function parseMeetingExternalSend($param) {
        $externalUserList = isset($param['externalUserList']) ? $param['externalUserList'] : '';
        $meeting_begin_time = isset($param['meeting_begin_time']) ? $param['meeting_begin_time'] : '';
        $meeting_end_time = isset($param['meeting_end_time']) ? $param['meeting_end_time'] : '';
        $meeting_room_name = isset($param['meeting_room_name']) ? $param['meeting_room_name'] : '';
        $mApplyId = isset($param['meeting_apply_id']) ? $param['meeting_apply_id'] : '';
        $approveResult = isset($param['approveResult']) ? $param['approveResult'] : '';
        $oldMeeting = isset($param['oldMeeting']) ? $param['oldMeeting'] : '';
        $domain = isset($param['domain']) ? $param['domain'] : '';
        $meeting_subject = isset($param['meeting_subject']) ? $param['meeting_subject'] : '';
        $meetingVideoInfo = json_decode($oldMeeting->meeting_video_info ?? '', true);
        $join_url = isset($meetingVideoInfo['meeting_info_list']) ? $meetingVideoInfo['meeting_info_list'][0]['join_url'] : '';
        $meeting_code = isset($meetingVideoInfo['meeting_info_list']) ? $meetingVideoInfo['meeting_info_list'][0]['meeting_code'] : '';
        // 判断外部人员提醒类型
        if(!empty($externalUserList)) {
            foreach($externalUserList as $key => $value) {
                if (isset($oldMeeting->attence_type) && $oldMeeting->attence_type == 2) {
                    $url = "<a href=" .$join_url. " target='_blank'".">".$join_url."</a>";
                    $externalUserPhone[$key]['mobile_to'] = $value['external_user_phone'];
                    $externalUserPhone[$key]['message'] = trans('meeting.dear') . $value['external_user_name'] . trans('meeting.new_meeting_remind_subject') .'【'.$meeting_subject.'】'. trans('meeting.meeting_time') .$meeting_begin_time."----".$meeting_end_time.';' . trans('meeting.meeting_code'). ':' . $meeting_code . ';' . trans('meeting.video_meeting') . $url;

                    $externalUserPhone[$key]['external_user_email'] = $value['external_user_email'];
                } else {
                    $url = $domain.'/eoffice10/server/ext/meeting/details.php?applyId='.$mApplyId.'&room='.$meeting_room_name.'&userId='.$value['external_user_id'];
                    $url = "<a href=" .$url." target='_blank'".">".$url."</a>";
                    // $source = 1681459862; // // AppKey 以下是公用API，暂时可用。如失效，注册新浪开发者帐号即可
                    // $shortUrl = $this->getSinaShortUrl($source, $url);
                    $externalUserPhone[$key]['mobile_to'] = $value['external_user_phone'];
                    $externalUserPhone[$key]['message'] = trans('meeting.dear') . $value['external_user_name'] . trans('meeting.new_meeting_remind_subject') .'【'.$meeting_subject.'】'. trans('meeting.meeting_address') .$meeting_room_name. trans('meeting.meeting_time') .$meeting_begin_time."----".$meeting_end_time. trans('meeting.link_to_detail') . trans('meeting.click'). $url;
                    $externalUserPhone[$key]['external_user_email'] = $value['external_user_email'];
                }
            }
            $phone = implode(",", array_column($externalUserPhone, "mobile_to"));
            // 此时进行调用接口给外部人员发送短信.
            if ($approveResult && $externalUserPhone) {
                foreach($externalUserPhone as $k => $v) {
                    // 判断提醒方式
                    if (isset($oldMeeting->external_reminder_type) && $oldMeeting->external_reminder_type !== null) {
                        switch($oldMeeting->external_reminder_type) {
                            case 0;
                                $email = $this->MeetingExternalMailRreminder($v); // 邮件通知
                                break;
                            case 1;
                                $result = app($this->shortMessageService)->sendSMS($v); // 短信通知
                                break;
                            case 2;
                                $this->MeetingExternalMailRreminder($v); // 邮件和短信均通知
                                try {
                                    app($this->shortMessageService)->sendSMS($v); // 短信通知
                                } catch (\Exception $e) {
                                    return ['code' => $e->getMessage()];
                                } // 短信通知
                                break;
                            default:
                                try {
                                    app($this->shortMessageService)->sendSMS($v); // 短信通知
                                } catch (\Exception $e) {
                                    return ['code' => $e->getMessage()];
                                }
                                break;
                        }
                    }
                }
            }
        }
        return true;
    }
    /**
     * 实现外部人员邮件通知会议
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function MeetingExternalMailRreminder($data)
    {
        // 获取系统邮箱设置
        $systemEmailSetInfo = app($this->webmailEmailboxService)->getDefaultSystemMailboxDetail();
        if ($systemEmailSetInfo) {
            $systemEmailSetInfo = $systemEmailSetInfo->toArray();
        } else {
            // 邮件发送失败，请联系系统管理员设置系统邮箱
            return ['code' => ['0x030025', 'flow']];
        }
        $data['subject'] = trans('meeting.new_meeting_remind');
        $mail = [
            'host' => $systemEmailSetInfo['smtp_server'],
            'username' => $systemEmailSetInfo['email_address'],
            'password' => $systemEmailSetInfo['password'],
            'port' => $systemEmailSetInfo['smtp_port'],
            'smtp_ssl' => $systemEmailSetInfo['is_ssl_auth'],
            'from' => trim($systemEmailSetInfo['email_address']),
            'to' => $data['external_user_email'],
            'cc' => empty($data['cc']) ? '' : $data['cc'],
            'bcc' => empty($data['bcc']) ? '' : $data['bcc'],
            'addAttachment' => empty($data['attachments']) ? '' : $data['attachments'],
            'subject' => $data['subject'],
            'body' => $data['message'],
            'isHTML' => true,
        ];
        $param = [
            'handle' => 'Send',
            'param' => $mail
        ];
        Queue::push(new EmailJob($param));
        return true;
    }
    /**
     * @拒绝会议
     * @param type $data
     * @param type $remindData
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function refuseMeeting($data, $mApplyId, $loginUserInfo)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }

        $refuseData = [
            'meeting_approval_opinion'  => $this->defaultValue('meeting_approval_opinion',$data,''),
            'meeting_status'            => 3,
            'conflict'                  => '',
            'meeting_approval_user'         => $loginUserInfo['user_id']
        ];

        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);

        if (!($oldMeeting->meeting_status == 2 && $oldMeeting->conflict != '') && $oldMeeting->meeting_status != 1) {
            return ['code' => ['0x017018', 'meeting']];
        }

        if (!app($this->meetingApplyRepository)->editMeeting($refuseData, $mApplyId)) {
            return ['code' => ['0x000003', 'common']];
        }

        $this->replaceOldMeetingConflictId($oldMeeting->meeting_room_id, $oldMeeting->meeting_begin_time, $oldMeeting->meeting_end_time, $mApplyId);

        //发送消息提醒
        $meetingInfo = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        $roomInfo    = app($this->meetingRoomsRepository)->getDetail($meetingInfo['meeting_room_id']);
        $meetingRoom = $roomInfo->room_name;
        $meetingTime = date('Y-m-d H:i', strtotime($meetingInfo['meeting_begin_time'])).' ~ '.date('Y-m-d H:i', strtotime($meetingInfo['meeting_end_time']));
        $meetingName = $meetingInfo['meeting_subject'];
        $applyUser   = $meetingInfo['meeting_apply_user'];
        $userName    = $loginUserInfo['user_name'];
        $sendData['remindMark']   = 'meeting-refuse';
        $sendData['fromUser']     = $loginUserInfo['user_id'];
        $sendData['toUser']       = $applyUser;
        $sendData['contentParam'] = ['meetingSubject'=>$meetingInfo['meeting_subject'], 'meetingTime'=>$meetingTime, 'meetingRoom'=>$meetingRoom, 'userName'=>$userName];
        $sendData['stateParams']  = ['meeting_apply_id' => $mApplyId];
        Eoffice::sendMessage($sendData);

        return true;
    }
    /**
     * @开始会议
     * @param type $data
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function startMeeting($data, $mApplyId)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }

        $startData = [
            'meeting_begin_remark'  => $this->defaultValue('meeting_begin_remark',$data,''),
            'meeting_status'        => 4,
            'meeting_begin_time'    => date('Y-m-d H:i').':00'
        ];

        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);

        if ($oldMeeting->meeting_status != 2) {
            return ['code' => ['0x017016', 'meeting']];
        }
        if (isset($oldMeeting->attence_type) && $oldMeeting->attence_type == 2) {
            $oldMeetingInfo = json_decode($oldMeeting->meeting_video_info ?? '', true);
            $meetingInfoList = $oldMeetingInfo['meeting_info_list'] ?? [];
            $meetingId = $meetingInfoList[0]['meeting_id'] ?? '';
            // 调用编辑视频会议
            $conferenceParams = [
                'meeting_id' => $meetingId,
                'meeting_name' => $oldMeeting->meeting_subject ?? '',
                'meeting_begin_time' => $startData['meeting_begin_time'],
                'meeting_end_time' => $oldMeeting->meeting_end_time ?? '',
                'meeting_apply_user' => $oldMeeting->meeting_apply_user ?? ''
            ];
            // 编辑视频会议
            $video = app($this->vedioConferenceService)->modifyVideoconference(['interface_id' => $oldMeeting->interface_id ?? ''], $conferenceParams);
            if (isset($video['code'])) {
                return $video;
            }
        }

        if (!app($this->meetingApplyRepository)->editMeeting($startData, $mApplyId)) {
            return ['code' => ['0x000003', 'common']];
        }
        
        return true;
    }
    /**
     * @参加会议
     * @param type $data
     * @param type $mApplyId
     * @return 成功与否 | boolean
     */
    public function attenceMeeting ($data, $mApplyId, $loginUserInfo) {

        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $meetingInfo = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        $meetingInfo['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_apply', 'entity_id'=>$mApplyId]);
        $attenceData = [
            'meeting_attence_status' => 1,
            'meeting_attence_time'   => date('Y-m-d H:i').':00',
            'meeting_attence_remark' => $this->defaultValue('meeting_attence_remark',$data,''),
            'meeting_attence_user'   => $loginUserInfo['user_id'],
            'meeting_apply_id'       => $mApplyId,
            'meeting_sign_type'      => $meetingInfo->sign_type,
            'meeting_sign_status'    => 0
        ];

        if(!app($this->meetingAttenceRepository)->attenceMeeting($attenceData)) {
            return ['code' => ['0x000003', 'common']];
        }
        $calendarData = [
            'calendar_content' => $meetingInfo['meeting_subject'],
            'handle_user'      => explode(',', $loginUserInfo['user_id']),
            'calendar_begin'   => $meetingInfo['meeting_begin_time'],
            'calendar_end'     => $meetingInfo['meeting_end_time'],
            'calendar_remark'  => $meetingInfo['meeting_remark'] ?? '',
            'attachment_id'    => $meetingInfo['attachment_id'] ?? ''
        ];
        $relationData = [
            'source_id'     => $mApplyId,
            'source_from'   => 'meeting-join',
            'source_title'  => $meetingInfo['meeting_subject'],
            'source_params' => ['meeting_apply_id' => $mApplyId]
        ];
        app($this->calendarService)->emit($calendarData, $relationData, $meetingInfo['meeting_apply_user']);
        return true;
    }

    /**
     * @外部人员参加会议
     * @param type $data
     * @param type $mApplyId
     * @return 成功与否 | boolean
     */
    public function externalAttenceMeeting ($data, $mApplyId) {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $signType = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        $attenceData = [
            'meeting_attence_status' => 1,
            'meeting_attence_time'   => date('Y-m-d H:i').':00',
            'meeting_attence_remark' => $this->defaultValue('meeting_attence_remark',$data,''),
            // 'meeting_attence_user'    => $loginUserInfo['user_id'],
            'meeting_apply_id'       => $mApplyId,
            'meeting_sign_type'      => $signType->sign_type,
            'meeting_sign_status'    => 0
        ];

        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        if ($oldMeeting->meeting_status != 2) {
            return ['code' => ['0x017017', 'meeting']];
        }

        if(!app($this->meetingAttenceRepository)->attenceMeeting($attenceData)) {
            return ['code' => ['0x000003', 'common']];
        }
        return true;
    }
    /**
     * @拒绝参加会议
     * @param type $data
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function refuseAttenceMeeting($data, $mApplyId, $loginUserInfo) {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }

        $attenceData = [
            'meeting_attence_status' => 0,
            'meeting_refuse_time'    => date('y-m-d H:i').':00',
            'meeting_attence_remark'  => $this->defaultValue('meeting_refuse_remark',$data,''),
            'meeting_attence_user'   => $loginUserInfo['user_id'],
            'meeting_apply_id'       => $mApplyId
        ];
        $attenceapplyData = ['meeting_status' => 7];

        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        // if ($oldMeeting->meeting_status != 2) {
        //  return ['code' => ['0x017017', 'meeting']];
        // }
        if(!app($this->meetingAttenceRepository)->refuseAttenceMeeting($attenceData)) {
            return ['code' => ['0x000003', 'common']];
        }
        return true;
    }

    /**
     * @结束会议
     * @param type $data
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function endMeeting($data, $mApplyId, $loginUserInfo)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }

        $endData = [
            'meeting_status'        => 5,
            'conflict'              => '',
            'meeting_end_time'    => date('Y-m-d H:i').':00'
        ];

        $oldMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        if (isset($oldMeeting->attence_type) && $oldMeeting->attence_type == 2) {
            $oldMeetingInfo = json_decode($oldMeeting->meeting_video_info ?? '', true);
            $meetingInfoList = $oldMeetingInfo['meeting_info_list'] ?? [];
            $meetingId = $meetingInfoList[0]['meeting_id'] ?? '';
            // 调用编辑视频会议
            $conferenceParams = [
                'meeting_id' => $meetingId,
                'meeting_name' => $oldMeeting->meeting_subject ?? '',
                'meeting_begin_time' => $oldMeeting->meeting_begin_time,
                'meeting_end_time' => $endData['meeting_end_time'] ?? '',
                'meeting_apply_user' => $oldMeeting->meeting_apply_user ?? ''
            ];
            
            // 编辑视频会议
            $video = app($this->vedioConferenceService)->modifyVideoconference(['interface_id' => $oldMeeting->interface_id ?? ''], $conferenceParams);
            if (isset($video['code'])) {
                return $video;
            }
        }
        if (!app($this->meetingApplyRepository)->editMeeting($endData, $mApplyId)) {
            return ['code' => ['0x000003', 'common']];
        }
        $this->emitCalendarComplete($mApplyId, $loginUserInfo['user_id'], $oldMeeting->attence_type, 'complete');

        $this->replaceOldMeetingConflictId($oldMeeting->meeting_room_id, $oldMeeting->meeting_begin_time, $oldMeeting->meeting_end_time, $mApplyId);

        $data['meeting_apply_id'] = $mApplyId;
        $this->addMeetingRecord($data, $loginUserInfo);
        
        return true;
    }
    private function emitCalendarComplete($sourceId, $userId, $attenceType, $type) {
        $relationData = [
            'source_id'     => $sourceId,
            'source_from'   => 'meeting-join'
        ];
        if ($attenceType == 2) {
            $relationData['source_from'] = 'meeting-video';
        }
        if ($type == 'complete') {
            $result = app($this->calendarService)->emitComplete($relationData);
        } else {
            $result = app($this->calendarService)->emitDelete($relationData, $userId);
        }
        return $result;
    }
    /**
     * @删除审批会议下的会议
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function deleteApproveMeeting($mApplyId)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $mApplyIdArray = explode(',', $mApplyId);
        $time = date("Y-m-d H:i:s", time());
        foreach($mApplyIdArray as $key => $value) {
            $from = 'approval';
            $currentMeeting = app($this->meetingApplyRepository)->getDetail($value);
            // 判断时间自动结束和开始
            if (isset($currentMeeting->meeting_end_time) && $currentMeeting->meeting_end_time <= $time && ($currentMeeting->meeting_status == 2 || $currentMeeting->meeting_status == 4)) {
                $currentMeeting->meeting_status = 5;
            }else if (isset($currentMeeting->meeting_begin_time) && ($currentMeeting->meeting_begin_time <= $time && $currentMeeting->meeting_end_time > $time)  && $currentMeeting->meeting_status == 2) {
                $currentMeeting->meeting_status = 4;
            }
            if ($currentMeeting->meeting_status != 5 && $currentMeeting->meeting_status != 3) {
                return ['code' => ['0x017014', 'meeting']];
            }
            if ($currentMeeting->false_delete == 0) {
                //已拒绝的会议+结束的会议删除，不影响我的会议的记录
                if (!app($this->meetingApplyRepository)->editMeeting(['false_delete' => 2], $value)) {
                    return ['code' => ['0x000003', 'common']];
                }
            } else {
                if (!app($this->meetingApplyRepository)->deleteMeeting($value)) {
                    return ['code' => ['0x000003', 'common']];
                }
                $meetingApplyAttachmentData = ['entity_table' => 'meeting_apply', 'entity_id' => $value];
                app($this->attachmentService)->deleteAttachmentByEntityId($meetingApplyAttachmentData);
            }
        }
        return true;
    }
    /**
     * @删除我的会议下的会议
     * @param type $mApplyId
     * @return 成功与否 | array
     */
    public function deleteOwnMeeting($mApplyId, $userId)
    {
        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $time = date("Y-m-d H:i:s", time());
        $mApplyIdArray = explode(',', $mApplyId);
        foreach($mApplyIdArray as $key => $value) {
            $currentMeeting = app($this->meetingApplyRepository)->showMeeting($value);
            if ($currentMeeting->false_delete == 2) {
                if (!app($this->meetingApplyRepository)->deleteMeeting($value)) {
                    return ['code' => ['0x000003', 'common']];
                } else {
                    return true;
                }
            }
            if (($currentMeeting->meeting_status == 4 || ($currentMeeting->meeting_status == 2) && $currentMeeting->meeting_begin_time < $time) && $currentMeeting->meeting_end_time > $time && $currentMeeting->meeting_apply_user) {
                return ['code' => ['0x017014', 'meeting']];
            }
            if (($currentMeeting->meeting_status == 1 && $currentMeeting->meeting_apply_time == null)) {
                //待审批的删除和审批页面删除的
                if (!app($this->meetingApplyRepository)->deleteMeeting($value)) {
                    return ['code' => ['0x000003', 'common']];
                }
                $this->emitCalendarComplete($value, $userId, $currentMeeting->attence_type,  'delete');
                $meetingApplyAttachmentData = ['entity_table' => 'meeting_apply', 'entity_id' => $value];
                app($this->attachmentService)->deleteAttachmentByEntityId($meetingApplyAttachmentData);
                if ($currentMeeting->meeting_status == 1) {
                    $this->replaceOldMeetingConflictId($currentMeeting->meeting_room_id, $currentMeeting->meeting_begin_time, $currentMeeting->meeting_end_time, $value);
                }
            } else if ($currentMeeting->meeting_status == 2 && $currentMeeting->meeting_begin_time > $time) {
                // //已拒绝的会议+结束的会议删除，不影响审批页面的记录
                if (isset($currentMeeting->attence_type) && $currentMeeting->attence_type == 2) {
                    $oldMeetingInfo = json_decode($currentMeeting->meeting_video_info ?? '', true);
                    $meetingInfoList = $oldMeetingInfo['meeting_info_list'] ?? [];
                    $meetingId = $meetingInfoList[0]['meeting_id'] ?? '';
                    // 取消会议
                    $video = app($this->vedioConferenceService)->cancelVideoconference(['interface_id' => $currentMeeting->interface_id ?? ''], ['meeting_id' => $meetingId, 'meeting_apply_user' => $currentMeeting->meeting_apply_user ?? '']);
                    if (isset($video['code'])) {
                        return $video;
                    }
                }
                if (!app($this->meetingApplyRepository)->editMeeting(['meeting_status' => 8], $value)) {
                    return ['code' => ['0x000003', 'common']];
                }
                $this->emitCalendarComplete($value, $userId, $currentMeeting->attence_type, 'delete');
                $signUser = explode(',', $currentMeeting->meeting_sign_user ?? '');
                $joinMember = [];
                if (isset($currentMeeting->meeting_join_member) && $currentMeeting->meeting_join_member == 'all') {
                    $joinMember = explode(',', app($this->userService)->getAllUserIdString());
                } else {
                    $joinMember = explode(',', $currentMeeting->meeting_join_member);
                }
                $toUser = array_filter(array_unique(array_merge($joinMember, $signUser)));
                array_push($toUser, $currentMeeting->meeting_apply_user);
                $roomInfo      = app($this->meetingRoomsRepository)->getDetail($currentMeeting->meeting_room_id);
                $meetingRoom   = $roomInfo->room_name ?? '';
                if (!$meetingRoom) {
                    $video = json_decode($currentMeeting['meeting_video_info'] ?? '', true);
                    $meetingUrl = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['join_url'] : '';
                    $meetingID = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['meeting_id'] : '';
                    $meetingCode = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['meeting_code'] : '';
                    $meetingRoom = $meetingUrl. ';'. trans('meeting.meeting_id') .': '. $meetingCode;
                }
                $meetingTime   = date('Y-m-d H:i', strtotime($currentMeeting->meeting_begin_time)).' ~ '.date('Y-m-d H:i', strtotime($currentMeeting->meeting_end_time));
                $this->sendNotify('meeting-cancel', array_unique($toUser), ['meetingTime'=> $meetingTime,'meetingSubject'=>$currentMeeting->meeting_subject, 'meetingRoom'=> $meetingRoom], ['meeting_apply_id'=>$currentMeeting->meeting_apply_id]);
            } else {
                if (!app($this->meetingApplyRepository)->editMeeting(['false_delete' => 1], $value)) {
                    return ['code' => ['0x000003', 'common']];
                }
            }
        }
        return true;
    }

    //会议消息发送统一入口
    private function sendNotify($remindMark, $toUser, $contentParam, $stateParams)
    {
        $sendData = compact('remindMark', 'toUser', 'contentParam', 'stateParams');

        return Eoffice::sendMessage($sendData);
    }
    /**
     * @获取会议记录列表
     * @param type $param
     * @param type $response
     * @return 会议记录列表 | array
     */
    public function listMeetingRecords($data, $loginUserInfo)
    {
        $mApplyId   = $this->defaultValue('meeting_apply_id', $data, 0);

        if ($mApplyId == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $param = [
            'page'  => $this->defaultValue('page', $data, 0),
            'limit' => $this->defaultValue('limit', $data, 0),
            'search'=> [
                'meeting_records.meeting_apply_id' => [$mApplyId]
            ]
        ];

        $response   = $this->defaultValue('response', $data, 'both');

        if ($response == 'both' || $response == 'count') {
            $count = app($this->meetingRecordsRepository)->getMeetingRecordsCount($param);
        }

        $list = [];

        if (($response == 'both' && $count > 0) || $response == 'data') {
            $list = app($this->meetingRecordsRepository)->listMeetingRecords($param);
            foreach($list as $key => $value) {
                $list[$key]['record_creator_name'] = app($this->userRepository)->getUserName($value['record_creator']);
                $list[$key]['attachment_id']       = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_records', 'entity_id'=>$value['record_id']]);
            }
        }

        return $response == 'both'
                        ? ['total' => $count, 'list' => $list]
                        : ($response == 'data' ? $list : $count);
    }
    /**
     * @获取会议记录详情
     * @param type $recordId
     * @return array
     */
    public function getMeetingRecordDetailById($recordId)
    {
        $recordDetail = app($this->meetingRecordsRepository)->findRecordById($recordId);
        $recordDetail['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'meeting_records', 'entity_id'=>$recordId]);
        return $recordDetail;
    }
    /**
     * @新建会议记录
     * @param type $data
     * @param type $uploadFile
     * @return 会议记录id | array
     */
    public function addMeetingRecord($data, $loginUserInfo)
    {
        if ((!isset($data['record_content']) || empty($data['record_content'])) && (!isset($data['attachment_id']) || empty($data['attachment_id']))) {
            return ['code' => ['0x017019', 'meeting']];
        }
        if(isset($data['record_content'])) {
            $oldRecordInfo = app($this->meetingRecordsRepository)->findRecordByUserIdAndMeetingApplyId($loginUserInfo['user_id'], $data['meeting_apply_id']);

            $currentTime = date('Y-m-d H:i:s');
            if(!empty($oldRecordInfo)) {
                $recordData = [
                    'record_time'       => $currentTime,
                    'record_content'    => $oldRecordInfo->record_content.$data['record_content'],
                ];
                $recordId = $oldRecordInfo->record_id;
                if(!app($this->meetingRecordsRepository)->editMeetingRecord($recordData, $recordId)) {
                    return ['code' => ['0x000003', 'common']];
                }
                
                if(isset($data['attachment_id']) && $data['attachment_id']) {
                    app($this->attachmentService)->attachmentRelation("meeting_records", $recordId, $data['attachment_id'], 'add');
                }
            }else{
                $recordData = [
                    'meeting_apply_id'  => $data['meeting_apply_id'],
                    'record_creator'    => $loginUserInfo['user_id'],
                    'record_time'       => $currentTime,
                    'record_content'    => $data['record_content'],
                ];
                if (!$result = app($this->meetingRecordsRepository)->addMeetingRecord($recordData)) {
                    return ['code' => ['0x000003', 'common']];
                }
                $recordId = $result->record_id;
                if(isset($data['attachment_id']) && $data['attachment_id']) {
                    app($this->attachmentService)->attachmentRelation("meeting_records", $recordId, $data['attachment_id']);
                }
            }
            return ['record_id' => $recordId];
        }
    }
    /**
     * @编辑会议记录
     * @param type $data
     * @param type $uploadFile
     * @param type $recordId
     * @return 成功与否 | array
     */
    public function editMeetingRecord($data, $recordId, $loginUserInfo)
    {
        if ($recordId == 0) {
            return ['code' => ['0x017020', 'meeting']];
        }
        if ((!isset($data['record_content']) || empty($data['record_content'])) && (!isset($data['attachment_id']) || empty($data['attachment_id']))) {
            return ['code' => ['0x017019', 'meeting']];
        }
        $recordData = [
            'meeting_apply_id'  => $data['meeting_apply_id'],
            'record_time'       => date('Y-m-d H:i:s'),
            'record_content'    => $data['record_content'],
        ];

        $recordInfo = app($this->meetingRecordsRepository)->findRecordById($recordId);
       if (!$recordInfo) {
            return ['code' => ['0x017037', 'meeting']];
        }
        if ($recordInfo->record_creator != $loginUserInfo['user_id']) {
            return ['code' => ['0x017022', 'meeting']];
        }

        if (!app($this->meetingRecordsRepository)->editMeetingRecord($recordData, $recordId)) {
            return ['code' => ['0x000003', 'common']];
        }

        if(isset($data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("meeting_records", $recordId, $data['attachment_id']);
        }

        return true;
    }
    /**
     * @删除会议记录
     * @param type $recordId
     * @return 成功与否 | array
     */
    public function deleteMeetingRecord($recordId)
    {
        if ($recordId == 0) {
            return ['code' => ['0x017020', 'meeting']];
        }

        if (!app($this->meetingRecordsRepository)->deleteMeetingRecord($recordId)) {
            return ['code' => ['0x000003', 'common']];
        }

        $meetingRecordsAttachmentData = ['entity_table' => 'meeting_records', 'entity_id' => $recordId];
        app($this->attachmentService)->deleteAttachmentByEntityId($meetingRecordsAttachmentData);

        return true;
    }
    /**
     * @判断设备名称是否存在
     * @param type $equipmentName
     * @param type $equipmentId
     * @return boolean
     */
    private function equipmentNameExists($equipmentName, $equipmentId = null)
    {
        $params['search']['equipment_name'] = [$equipmentName];

        if ($equipmentId) {
            $params['search']['equipment_id'] = [$equipmentId, '!='];
        }

        if (app($this->meetingEquipmentRepository)->getEquipmentCount($params) > 0) {
            return true;
        }

        return false;
    }
    /**
     * @返回会议室名称是否存在
     * @param type $roomName
     * @param type $roomId
     * @return boolean
     */
    private function roomNameExists($roomName, $roomId = null)
    {
        $params['search']['room_name'] = [$roomName];

        if ($roomId) {
            $params['search']['room_id'] = [$roomId, '!='];
        }

        if (app($this->meetingRoomsRepository)->getRoomCount($params) > 0) {
            return true;
        }

        return false;
    }
    /**
     * @获取相应的冲突会议申请
     * @param type $mRoomId
     * @param type $mBeginTime
     * @param type $mEndTime
     * @return 冲突会议申请id
     */
    public function getConflictMeeting($mRoomId, $mBeginTime, $mEndTime)
    {
        $search = [
            'meeting_room_id'       => [$mRoomId],
            'meeting_begin_time'    => [$mEndTime, '<'],
            'meeting_end_time'      => [$mBeginTime, '>'],
            'meeting_status'        => [[0,1, 2, 4], 'in'],
            'false_delete'          => [0]
        ];

        $result = app($this->meetingApplyRepository)->getConflictMeeting($search);

        if (count($result) == 0) {
            return '';
        }
        $conflictId = '';

        foreach ($result as $value) {
            $conflictId .= $value->meeting_apply_id . ',';
        }

        return substr($conflictId, 0, strrpos($conflictId, ','));
    }
    /**
     * @将非冲突的id替换为空
     * @param type $mRoomId
     * @param type $mBeginTime
     * @param type $mEndTime
     * @param type $mApplyId
     * @return string
     */
    private function replaceOldMeetingConflictId($mRoomId, $mBeginTime, $mEndTime, $mApplyId)
    {
        $search = [
            'meeting_room_id'       => [$mRoomId],
            'meeting_begin_time'    => [$mEndTime, '<'],
            'meeting_end_time'  => [$mBeginTime, '>'],
            'meeting_status'        => [[0, 1, 2, 4], 'in'],
            'false_delete'  => [0]
        ];

        $result = app($this->meetingApplyRepository)->getConflictMeeting($search);

        if (count($result) == 0) {
            return '';
        }

        foreach ($result as $value) {
            $tempConflictId = str_replace([$mApplyId . ',', ',' . $mApplyId, $mApplyId], '', $value->conflict);

            app($this->meetingApplyRepository)->editMeeting(['conflict' => $tempConflictId], $value->meeting_apply_id);
        }
    }
    /**
     * @更新会议冲突id
     * @param type $conflictId
     * @param type $mApplyId
     */
    private function updateMeetingConflictId($conflictId, $mApplyId)
    {
        foreach (explode(',', $conflictId) as $key => $value) {
            $currentMeeting = app($this->meetingApplyRepository)->showMeeting($value);

            if ($currentMeeting['conflict'] == "") {
                $tempConflictId = $mApplyId;
            } else {
                $tempConflictId = $currentMeeting->conflict . ',' . $mApplyId;
            }

            app($this->meetingApplyRepository)->editMeeting(['conflict' => $tempConflictId], $value);
        }
    }
    /**
     * @处理会议搜索条件
     * @param type $search
     * @param type $type
     * @return 会议搜索条件 | array
     */
    private function handleSearch($search, $type = "own")
    {
        if ($type == 'own') {
            $search['false_delete'] = [1, '!='];

            $search['meeting_apply_user'] = [$this->loginUserId];
        } else {
            $search['false_delete'] = [2, '!='];
        }

        return $search;
    }

    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @获取冲突会议的数量用于冲突标签徽章
     * @param type $param
     * @return 冲突数量 | json
     */
    public function getMeetingConflictEmblem($param)
    {
        $param['search'] = json_encode(["conflict"=>["","!="]]);
        $result = $this->response(app($this->meetingApplyRepository), 'getApproveMeetingCount', 'getApproveMeetingList', $this->parseParams($param));
        $data['data'][0]['fieldKey'] = 'conflict';
        $data['data'][0]['total']    = $result['total'];
        return json_encode($data);
    }

    /**
     * 设置待审核申请的审批查看时间
     * @param string $meeting_apply_id
     * @return boolean
     */
    public function setMeetingApply($meeting_apply_id) {
        return app($this->meetingApplyRepository)->setMeetingApply($meeting_apply_id);
    }

    /**
     * 判断是否有会议详情的查看权限
     * @param string $meeting_apply_id
     * @return boolean
     */
    public function getMeetingDetailPermissions($mApplyId, $loginUserInfo) {
        // 获取是否审批删除
        $detail = app($this->meetingApplyRepository)->getDetail($mApplyId);
        if (!$detail) {
            return ['code' => ['0x000006','common']];
        }
        if ($detail->false_delete == 2) {
            $from = 'approval';
        } else {
            $from = 'sms';
        }
        $showMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId, $from);
        if(isset($loginUserInfo['flag']) && $loginUserInfo['flag'] == 2) {
            $user_id = $loginUserInfo['user_id'];
            $role_id = $loginUserInfo['role_id'];
            $dept_id = $loginUserInfo['dept_id'];
            $showMeeting = app($this->meetingApplyRepository)->showMeeting($mApplyId);
            //  进行判断是否具有当前会议的会议室的分类的审批权限,如果有则返回1, 否则返回0;
            //  获取会议室的分类iD
            if((!isset($showMeeting->room_id) || empty($showMeeting->room_id)) && $showMeeting->attence_type == 1) {
                return ['code' => ['0x000000','common']];
            }
            $roomDetail =  app($this->meetingRoomsRepository)->getDetail($showMeeting->room_id);
            //  获取分类中部门, 角色, 用户的ID 用于和当前用户进行比较
            $sortDetail =  app($this->meetingSortRepository)->getDetail($roomDetail->meeting_sort_id);
            $approvalUser = $sortDetail->meeting_approvel_user;
            if ($approvalUser == 'all') {
                return '1';
            } else if ($approvalUser != 'all' && !empty($approvalUser)) {
                $approvalUser = explode(',', $approvalUser);

                if(!in_array($user_id, $approvalUser)) {
                    return '0';
                }else{
                    return '1';
                }
            }

        }
        if((!isset($showMeeting->room_id) || empty($showMeeting->room_id)) && $showMeeting->attence_type == 1) {
                return ['code' => ['0x000000','common']];
            }
        // 判断 1. 是否具有审批权限, 2.判断是否是申请人员, 3. 判断是否是参加人员, 4.判断是否是签到人员
        $approvalUser = app($this->meetingRoomsRepository)->getApprovalUser($showMeeting->room_id);
        $approvalUser = isset($approvalUser[0]['meeting_approvel_user']) ? $approvalUser[0]['meeting_approvel_user'] : '';
        if ($approvalUser == 'all') {
            return '1';
        }
        $signUser = isset($showMeeting->meeting_sign_user) ? $showMeeting->meeting_sign_user : '';
        // 判断是否有会议审批菜单权限，如果有可查看所有审批会议详情
        // 判断详情页查看权限
        // if(!$approvePermissions || empty($showMeeting)) {
        if((!in_array($loginUserInfo['user_id'], explode(',', $approvalUser)) && ($loginUserInfo['user_id'] != $signUser)) || empty($showMeeting)) {
            if(empty($showMeeting)) {
                return '0';
            }
            $meetingApplyUser = $showMeeting->meeting_apply_user;
            $meetingStatus    = $showMeeting->meeting_status;
            // 判断参与人员是否有权限查看详情页，已批准、已开始和已结束的可以查看
            if(!empty($showMeeting->meeting_join_member)) {
                if($showMeeting->meeting_join_member == 'all') {
                    $toJoinUser = app($this->userService)->getAllUserIdString([]);
                    $meetingJoinMemberArray = explode(',', $toJoinUser);
                    $judgeMeetingJoinMember = in_array($loginUserInfo['user_id'], $meetingJoinMemberArray) && ($meetingStatus == '2' || $meetingStatus == '4' || $meetingStatus == '5');
                }else{
                    $meetingJoinMemberArray = explode(',', $showMeeting->meeting_join_member);
                    $judgeMeetingJoinMember = in_array($loginUserInfo['user_id'], $meetingJoinMemberArray) && ($meetingStatus == '2' || $meetingStatus == '4' || $meetingStatus == '5');
                }
            }else{
                $judgeMeetingJoinMember = false;
            }
            // 判断是否有我的会议菜单权限，如果有可查看本人所有会议详情
            $approvePermissions = in_array('702', $loginUserInfo['menus']['menu']);
            // 判断详情页查看权限
            if(!$approvePermissions || empty($showMeeting) || !(($loginUserInfo['user_id'] == $meetingApplyUser) || $judgeMeetingJoinMember)) {
                return '0';
            }else{
                return '1';
            }
        }else{
            return '1';
        }
    }

    /**
     * 新建会议申请前判断申请时间段是否与现有会议有冲突
     * @param array
     * @return boolean
     */
    public function getNewMeetingDateWhetherConflict($param) {
        $result = app($this->meetingApplyRepository)->getNewMeetingDateWhetherConflict($param);
        // 检查配置
        $setInfo = $this->getBaseSetting($param);
        if (isset($setInfo['meeting_set_useed']) && $setInfo['meeting_set_useed'] == 1) {
            if ($result == 1) {
                return ['code' => ['0x017031', 'meeting']];
            }
        }

        return $result;
    }

    /**
     * 获取日期时间范围内所有的会议列表
     * @param array
     * @return array
     */
    public function getAllMeetingList($param) {

        $data = $this->response(app($this->meetingApplyRepository), 'getAllMeetingListCount', 'getAllMeetingList', $this->parseParams($param));
        $time = date("Y-m-d H:i:s", time());
        if (isset($data['list']) && !empty($data['list'])) {
            foreach($data['list'] as $key => $value) {
                if (isset($value['meeting_type'])) {
                    $selectField = $this->meetingSelectField;
                    foreach ($selectField as $field => $id) {
                        $data['list'][$key]['meeting_type_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $value['meeting_type']);
                    }
                }
                if (($value['meeting_end_time'] <= $time) && ($value['meeting_status'] == 2 || $value['meeting_status'] == 4)) {
                    unset($data['list'][$key]);
                    continue;
                } else if (($value['meeting_begin_time'] <= $time) && ($value['meeting_end_time'] > $time) && ($value['meeting_status'] == 2)) {
                    $data['list'][$key]['meeting_status'] = 4;
                }
            }
        }
        $data['list'] = array_values($data['list']);
        return $data;
    }

    /**
     * 获取会议室使用情况表格
     * @param array
     * @return string
     */
    public function getMeetingRoomUsageTable($param) {
        $tableList          = array();
        $roomList           = app($this->meetingRoomsRepository)->listRoom($param);
        $meetingApplyResult = app($this->meetingApplyRepository)->getAllMeetingList($param);

        $time = date("Y-m-d H:i:s", time());
        if (isset($meetingApplyResult) && !empty($meetingApplyResult)) {
            foreach($meetingApplyResult as $key => $value) {
                if (isset($value['meeting_type'])) {
                    $selectField = $this->meetingSelectField;
                    foreach ($selectField as $field => $id) {
                        $meetingApplyResult[$key]['meeting_type_name']        = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $value['meeting_type']);
                    }
                }
                if (($value['meeting_end_time'] <= $time) && ($value['meeting_status'] == 2 || $value['meeting_status'] == 4)) {
                    unset($meetingApplyResult[$key]);
                    continue;
                } else if (($value['meeting_begin_time'] <= $time) && ($value['meeting_end_time'] > $time) && ($value['meeting_status'] == 2)) {
                    $meetingApplyResult[$key]['meeting_status'] = 4;
                }
            }
        }
        $meetingApplyResult = array_values($meetingApplyResult);
        if($param['type'] == 'day') {
            $currentDay = $param['currentDay'];
            if(!empty($roomList)) {
                foreach($roomList as $roomKey => $roomValue) {
                    for($i=0;$i<=23;$i++) {
                        $count = "";
                        $currentTime = sprintf("%02d", $i);
                        $hourStart = $currentDay.' '.$currentTime.':00:00';
                        if($i == 23) {
                            $hourEnd = date('Y-m-d',strtotime('+1 day',strtotime($currentDay))).' 00:00:00';
                        }else{
                            $endTime = sprintf("%02d", $i + 1);
                            $hourEnd = $currentDay.' '.$endTime.':00:00';
                        }
                        $tempArray = $this->getMeetingListArray($meetingApplyResult, $roomValue, $hourStart, $hourEnd, $i, $tableList, $count);
                        //$tableList = array_merge($tableList, $tempArray);
                        //在getMeetingListArray方法中已经将值写入$tableList中并返回，所以不需要array_merge，而且array_merge会将相同键名的覆盖掉
                        $tableList = $tempArray;
                    }
                }
            }
        }elseif($param['type'] == 'week') {
            $firstDay = $param['currentDay'];
            if(!empty($roomList)) {
                foreach($roomList as $roomKey => $roomValue) {
                    for($j=0;$j<=6;$j++) {
                        $count = "";
                        if($j == 0) {
                            $hourStart = $firstDay.' 00:00:00';
                            $hourEnd   = $firstDay.' 23:59:59';
                            $currentDate = $firstDay;
                        }else{
                            $timeStr = $firstDay.' +'.$j.' day';
                            $hourStart = date('Y-m-d',strtotime($timeStr)).' 00:00:00';
                            $hourEnd   = date('Y-m-d',strtotime($timeStr)).' 23:59:59';
                            $currentDate = date('Y-m-d',strtotime($timeStr));
                        }
                        $tempArray = $this->getMeetingListArray($meetingApplyResult, $roomValue, $hourStart, $hourEnd, $currentDate, $tableList, $count);
//                      $tableList = array_merge($tableList, $tempArray);
                        $tableList = $tempArray;
                    }
                }
            }
        }elseif($param['type'] == 'month') {
            $firstDay      = $param['currentDay'];
            $dateArray     = explode('-', $firstDay);
            $currentMonth  = $dateArray[1];
            $currentYear   = $dateArray[0];
            $thisMonthDays = cal_days_in_month(CAL_EASTER_DEFAULT, $currentMonth, $currentYear);
            foreach($roomList as $roomKey => $roomValue) {
                for($j=1;$j<=$thisMonthDays;$j++) {
                    $count = "";
                    $currentDay = sprintf("%02d", $j);
                    $hourStart = $currentYear.'-'.$currentMonth.'-'.$currentDay.' 00:00:00';
                    $hourEnd   = $currentYear.'-'.$currentMonth.'-'.$currentDay.' 23:59:59';
                    $tempArray = $this->getMeetingListArray($meetingApplyResult, $roomValue, $hourStart, $hourEnd, $j, $tableList, $count);
//                  $tableList = array_merge($tableList, $tempArray);
                    $tableList = $tempArray;
                }
            }
        }

        return $tableList;
    }
    public function getMeetingRoomUsageTableV2($param) {
        $tableList          = array();
        $roomList           = app($this->meetingRoomsRepository)->listRoom($param);
        $meetingApplyResult = app($this->meetingApplyRepository)->getAllMeetingList($param);

        $time = date("Y-m-d H:i:s", time());
        if (isset($meetingApplyResult) && !empty($meetingApplyResult)) {
            foreach($meetingApplyResult as $key => $value) {
                if (isset($value['meeting_type'])) {
                    $selectField = $this->meetingSelectField;
                    foreach ($selectField as $field => $id) {
                        $meetingApplyResult[$key]['meeting_type_name']        = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $value['meeting_type']);
                    }
                }
                if (($value['meeting_end_time'] <= $time) && ($value['meeting_status'] == 2 || $value['meeting_status'] == 4)) {
                    unset($meetingApplyResult[$key]);
                    continue;
                } else if (($value['meeting_begin_time'] <= $time) && ($value['meeting_end_time'] > $time) && ($value['meeting_status'] == 2)) {
                    $meetingApplyResult[$key]['meeting_status'] = 4;
                }
            }
        }
        $meetingApplyResult = array_values($meetingApplyResult);
        if($param['type'] == 'day') {
            $currentDay = $param['currentDay'];
            if(!empty($roomList)) {
                foreach($roomList as $roomKey => $roomValue) {
                    for($i=0;$i<=23;$i++) {
                        $count = "";
                        $currentTime = sprintf("%02d", $i);
                        $hourStart = $currentDay.' '.$currentTime.':00:00';
                        if($i == 23) {
                            $hourEnd = date('Y-m-d',strtotime('+1 day',strtotime($currentDay))).' 00:00:00';
                        }else{
                            $endTime = sprintf("%02d", $i + 1);
                            $hourEnd = $currentDay.' '.$endTime.':00:00';
                        }
                        $tempArray = $this->getMeetingListArrayV2($meetingApplyResult, $roomValue, $hourStart, $hourEnd, $i, $tableList, $count ,$i);
                        //$tableList = array_merge($tableList, $tempArray);
                        //在getMeetingListArray方法中已经将值写入$tableList中并返回，所以不需要array_merge，而且array_merge会将相同键名的覆盖掉
                        $tableList = $tempArray;
                    }
                }
            }
        }elseif($param['type'] == 'week') {
            $firstDay = $param['currentDay'];
            if(!empty($roomList)) {
                foreach($roomList as $roomKey => $roomValue) {
                    for($j=0;$j<=6;$j++) {
                        $count = "";
                        if($j == 0) {
                            $hourStart = $firstDay.' 00:00:00';
                            $hourEnd   = $firstDay.' 23:59:59';
                            $currentDate = $firstDay;
                        }else{
                            $timeStr = $firstDay.' +'.$j.' day';
                            $hourStart = date('Y-m-d',strtotime($timeStr)).' 00:00:00';
                            $hourEnd   = date('Y-m-d',strtotime($timeStr)).' 23:59:59';
                            $currentDate = date('Y-m-d',strtotime($timeStr));
                        }
                        $tempArray = $this->getMeetingListArrayV2($meetingApplyResult, $roomValue, $hourStart, $hourEnd, $currentDate, $tableList, $count,$j);
//                      $tableList = array_merge($tableList, $tempArray);
                        $tableList = $tempArray;
                    }
                }
            }
        }elseif($param['type'] == 'month') {
            $firstDay      = $param['currentDay'];
            $dateArray     = explode('-', $firstDay);
            $currentMonth  = $dateArray[1];
            $currentYear   = $dateArray[0];
            $thisMonthDays = cal_days_in_month(CAL_EASTER_DEFAULT, $currentMonth, $currentYear);
            foreach($roomList as $roomKey => $roomValue) {
                for($j=1;$j<=$thisMonthDays;$j++) {
                    $count = "";
                    $currentDay = sprintf("%02d", $j);
                    $hourStart = $currentYear.'-'.$currentMonth.'-'.$currentDay.' 00:00:00';
                    $hourEnd   = $currentYear.'-'.$currentMonth.'-'.$currentDay.' 23:59:59';
                    $tempArray = $this->getMeetingListArrayV2($meetingApplyResult, $roomValue, $hourStart, $hourEnd, $j, $tableList, $count ,$j-1);
//                  $tableList = array_merge($tableList, $tempArray);
                    $tableList = $tempArray;
                }
            }
        }
        return $this->mergeMeetingInfo($tableList,$roomList);

    }
    public function mergeMeetingInfo($tableList,$roomList){
        $meetingInfo = [];
        $i = 0;
        foreach ($roomList as $k => $v){
            $meetingInfo[$i]['room_info'] = $v;
            $meetingInfo[$i]['count_info'] = $tableList[$v['room_id']];
            $i++;
        }
        return $meetingInfo;
    }
    public function getMeetingListArray($meetingApplyResult, $roomValue, $start, $end, $currentFlag , $tableList, $count) {
        $time = date("Y-m-d H:i:s", time());
        if(!empty($meetingApplyResult)) {
            foreach($meetingApplyResult as $applyKey => $applyValue) {
                if($applyValue['meeting_room_id'] == $roomValue['room_id']) {
                    if(($applyValue['meeting_begin_time'] < $end) && ($applyValue['meeting_end_time'] > $start)) {
                        if (($applyValue['meeting_end_time'] <= $time) && ($applyValue['meeting_status'] == 2 || $applyValue['meeting_status'] == 4)) {
                            $meetingApplyResult[$applyKey]['meeting_status'] = 5;
                            unset($meetingApplyResult[$applyKey]);continue;
                        } else if (($applyValue['meeting_begin_time'] <= $time) && ($applyValue['meeting_end_time'] > $time) && ($applyValue['meeting_status'] == 2)) {
                            $meetingApplyResult[$applyKey]['meeting_status'] = 4;
                        }
                        $count++;
                        $tableList[$roomValue['room_name']][$currentFlag]['list'][] = $meetingApplyResult[$applyKey];
                    }else{
                        continue;
                    }
                }else{
                    continue;
                }
            }
            $tableList[$roomValue['room_name']][$currentFlag]['count'] = $count;
        }else{
            $tableList[$roomValue['room_name']][$currentFlag]['list']  = "";
            $tableList[$roomValue['room_name']][$currentFlag]['count'] = "";
        }
        return $tableList;
    }
    public function getMeetingListArrayV2($meetingApplyResult, $roomValue, $start, $end, $currentFlag, $tableList, $count ,$sign) {

        $time = date("Y-m-d H:i:s", time());
        if(!empty($meetingApplyResult)) {
            foreach($meetingApplyResult as $applyKey => $applyValue) {
                if($applyValue['meeting_room_id'] == $roomValue['room_id']) {
                    if(($applyValue['meeting_begin_time'] < $end) && ($applyValue['meeting_end_time'] > $start)) {
                        if (($applyValue['meeting_end_time'] <= $time) && ($applyValue['meeting_status'] == 2 || $applyValue['meeting_status'] == 4)) {
                            $meetingApplyResult[$applyKey]['meeting_status'] = 5;
                            unset($meetingApplyResult[$applyKey]);continue;
                        } else if (($applyValue['meeting_begin_time'] <= $time) && ($applyValue['meeting_end_time'] > $time) && ($applyValue['meeting_status'] == 2)) {
                            $meetingApplyResult[$applyKey]['meeting_status'] = 4;
                        }
                        $count++;
                        $tableList[$roomValue['room_id']][$sign]['list'][] = $meetingApplyResult[$applyKey];
                    }else{
                        continue;
                    }
                }else{
                    continue;
                }
            }

            $tableList[$roomValue['room_id']][$sign]['count'] = $count;

        }else{
            $tableList[$roomValue['room_id']][$sign]['list']  = "";
            $tableList[$roomValue['room_id']][$sign]['count'] = "";
        }

        return $tableList;
    }

    /**
     * 根据会议室名称获取会议室ID
     * @param array
     * @return string
     */
    public function getRoomIdByRoomName($param) {
        return app($this->meetingRoomsRepository)->getRoomIdByRoomName($this->parseParams($param));
    }

    /**
     * 获取签到WiFi列表
     * @param array
     * @return array
     */
    public function getSignWifiList($param){
        return $this->response(app($this->meetingSignWifiRepository), 'getWifiTotal', 'getWifiList', $this->parseParams($param));
    }

    /**
     * 添加和修改WiFi
     * @param array
     * @return int
     */
    public function addSignWifiList($data) {
        if(app($this->meetingSignWifiRepository)->wifiMacExists($data['meeting_wifi_mac'])){
            return ['code' => ['0x017028', 'meeting']];
        }
        if (isset($data['meeting_wifi_mac']) && !empty($data['meeting_wifi_mac'])) {
            $data['meeting_wifi_mac'] = strtolower($data['meeting_wifi_mac']);
        }
        return app($this->meetingSignWifiRepository)->insertData($data);
    }
    public function getWifiInfo($wifiId)
    {
        $wifiInfo = app($this->meetingSignWifiRepository)->getDetail($wifiId);

        return $wifiInfo;
    }
    public function editSignWifi($wifiId, $data) {

        if(app($this->meetingSignWifiRepository)->wifiMacExists($data['meeting_wifi_mac'], $wifiId)){
            return ['code' => ['0x017028', 'meeting']];
        }
        if (isset($data['meeting_wifi_mac']) && !empty($data['meeting_wifi_mac'])) {
            $data['meeting_wifi_mac'] = strtolower($data['meeting_wifi_mac']);
        }
        return app($this->meetingSignWifiRepository)->updateData($data, ['meeting_wifi_id' => $wifiId]);
    }

    public function deleteSignWifi($wifiId)
    {
        $wifiId = explode(',', $wifiId);
        foreach($wifiId as $key => $value) {
            $userId = app($this->meetingSignWifiRepository)->getDetail($value);
            if(!app($this->meetingSignWifiRepository)->deleteById($value)) {
                return ['code' => ['0x000003', 'common']];
            }
        }
        return true;
    }

    /*
    *添加外部人员
    * @param array
    * @return int
    *
    *
    */
    public function addExternalUser($data) {
        $userNamePyArray = convert_pinyin($data["external_user_name"]);
        $data["external_name_py"] = $userNamePyArray[0];
        $data["external_name_zm"] = $userNamePyArray[1];
        $data["external_user_name"] = $data["external_user_name"];
        $data["external_user_type"] = isset($data["external_user_type"]) && !empty($data['external_user_type']) ? $data['external_user_type'] : 1;
        $data["external_user_remark"] = isset($data["external_user_remark"]) ? $data["external_user_remark"] : '';
        $data["external_user_phone"] = isset($data["external_user_phone"]) ? $data["external_user_phone"] : null;
        if(app($this->meetingExternalUserRepository)->userPhoneExists($data['external_user_phone'])){
            return ['code' => ['0x017026', 'meeting']];
          }
        if($result = app($this->meetingExternalUserRepository)->insertData($data)) {
            return ['external_user_id' => $result->external_user_id];
        }else{
            return  ['code' => ['0x000003', 'common']];
        }

   }

   public function getExternalUser($param) {

        $data = $this->response(app($this->meetingExternalUserRepository), 'getExternalUserTotal', 'getExternalUserList', $this->parseParams($param));
        if (isset($data['list']) && !empty($data['list'])) {
            foreach ($data['list'] as $key => $value) {
                $data['list'][$key]->external_user_type_name =  mulit_trans_dynamic("meeting_external_user_type.external_user_type_name." . $value->external_user_type_name);
            }
        }
        return $data;
   }

   public function getExternalUserInfo($userId) {
        $externalUserInfo = app($this->meetingExternalUserRepository)->getDetail($userId);
        return $externalUserInfo;
   }
   public function getTypeInfo($typeId) {
        $externalUserTypeInfo = app($this->meetingExternalUserTypeInfoRepository)->getDetail($typeId);
        if ($externalUserTypeInfo) {
            $externalUserTypeInfo->external_name_lang = app($this->langService)->transEffectLangs("meeting_external_user_type.external_user_type_name." . $externalUserTypeInfo->external_user_type_name);
            $externalUserTypeInfo->external_user_type_name = mulit_trans_dynamic("meeting_external_user_type.external_user_type_name." . $externalUserTypeInfo->external_user_type_name);
        }
        return $externalUserTypeInfo;
   }
   public function editExternalUserInfo($userId, $data) {
    $userNamePyArray = convert_pinyin($data["external_user_name"]);
        $data["external_name_py"] = $userNamePyArray[0];
        $data["external_name_zm"] = $userNamePyArray[1];
        $data["external_user_name"] = $data["external_user_name"];
    $userInfo = [
        'external_user_name'    => $data['external_user_name'],
        'external_name_py'      => $data['external_name_py'],
        'external_name_zm'      => $data['external_name_zm'],
        'external_name_company' => $data['external_name_company'],
        'external_user_phone'   => $data['external_user_phone'],
        'external_user_type'    => isset($data["external_user_type"]) && !empty($data['external_user_type']) ? $data['external_user_type'] : 1,
        'external_user_email'   => $this->defaultValue('external_user_email', $data , null),
        'external_user_remark'  => $this->defaultValue('external_user_remark', $data , null)
    ];
    if(app($this->meetingExternalUserRepository)->userPhoneExists($data['external_user_phone'], $userId)){
            return ['code' => ['0x017026', 'meeting']];
       }

    if (!app($this->meetingExternalUserRepository)->updateData($userInfo, ['external_user_id' => $userId])) {
            return ['code' => ['0x000003', 'common']];
        }
        return true;

   }
   public function editExternalUserType($typeId, $data) {
        $user_name = isset($data['external_user_type_name']) ? $data['external_user_type_name'] : '';
        $newData = [
            'external_user_type_name' => $user_name
        ];
        $combobox_lang = isset($data['external_name_lang']) ? $data['external_name_lang'] : '';
        if (app($this->meetingExternalUserTypeInfoRepository)->updateData($newData, ['external_user_type_id' => $typeId])) {
            if (!empty($combobox_lang) && is_array($combobox_lang)) {
                foreach ($combobox_lang as $key => $value) {
                    $langData = [
                        'table'      => 'meeting_external_user_type',
                        'column'     => 'external_user_type_name',
                        'lang_key'   => "external_user_type_name_" . $typeId,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            }else{
                $langData = [
                        'table'      => 'meeting_external_user_type',
                        'column'     => 'external_user_type_name',
                        'lang_key'   => "external_user_type_name_" . $typeId,
                        'lang_value' => $data['external_user_type_name'],
                    ];

                    app($this->langService)->addDynamicLang($langData);
            }
            $newsData = [
            'external_user_type_name' => "external_user_type_name_" . $typeId
        ];
            if (app($this->meetingExternalUserTypeInfoRepository)->updateData($newsData, ['external_user_type_id' => $typeId])) {

                return true;
            }
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

   public function deleteExternalUser($userId) {
        $userId = explode(',', $userId);
        foreach($userId as $key => $value) {
            $userId = app($this->meetingExternalUserRepository)->getDetail($value);
            if(!app($this->meetingExternalUserRepository)->deleteById($value)) {
                return ['code' => ['0x000003', 'common']];
            }
        }
        return true;
   }
   /**
    * 获取签到二维码
    * @param  [type] $mApplyId      [description]
    * @param  [type] $loginUserInfo [description]
    * @return [type] base64         [description]
    */
   public function getMeetingQRCodeInfo($mApplyId, $loginUserInfo) {

//        $qrInfo = [
//            'user_id' => $loginUserInfo['user_id'],
//            'meeting_apply_id' => $mApplyId,
//            'qrcodeFrom' => 'qrcMeetingSign'
//        ];
       
        $qrInfo = [
            'mode' => 'function',
            'body' => [
                'function_name' => 'qrcMeetingSign',
                'params' => [
                    'user_id' => $loginUserInfo['user_id'],
                    'meeting_apply_id' => $mApplyId
                ]
            ],
            'timestamp' => time(),
            'ttl' => 0
        ];
        
        $ip = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        $host = $ip . ':' . $port;
        $base_path = base_path();
        $filePath = base_path('public/meeting');
        if(!is_dir($filePath)){
            mkdir($filePath, 0777);
        }
        $filePath1 = base_path('public/meeting/qrcode');
        if(!is_dir($filePath1)){
            mkdir($filePath1, 0777);
        }
        $qrPath = base_path('public/meeting/qrcode/qrcode.png');
        QrCode::format('png')->size(200)->generate(json_encode($qrInfo), $qrPath);

        if(!file_exists($qrPath)) {
            return ['code' => ['0x003021', 'auth']];
        }
        return ['data' => @imageToBase64($qrPath)];
   }
   public function downloadCode($mApplyId, $loginUserInfo) {
        if (!$mApplyId) {
            return ['code' => ['0x000003', 'common']];
        }
        $qrPath = $this->getMeetingQRCodeInfo($mApplyId, $loginUserInfo);
        if ($qrPath) {
            $param = [];
            $param['operate'] = 'download';
            $data['image_file'] = $qrPath['data'];
            $attachment = app($this->attachmentService)->base64Attachment($data, $loginUserInfo['user_id']);
            $attachmentId = isset($attachment['attachment_id']) ? $attachment['attachment_id'] : '';
            $result = app($this->attachmentService)->loadAttachment($attachmentId, $param, $loginUserInfo, false);
            return $attachment;
        }
        return false;

   }
   /**
    * 会议二维码签到
    * @param  [type] $mApplyId [description]
    * @param  [type] $userId   [description]
    * @return [type]   int     [description]
    */
   public function doQRSign($userId, $mApplyId) {
        $signData = [
            'meeting_sign_status'  => 1,
            'meeting_sign_time'    => date('Y-m-d H:i:s'),
        ];
        $hasSign = app($this->meetingAttenceRepository)->getHasSignUserList($mApplyId, $userId);
        if (empty($hasSign)) {
            return 3;
        }
        if(isset($hasSign[0]['meeting_sign_time']) && $hasSign[0]['meeting_sign_time'] != null ) {
            return 2;
        }
        if($result = app($this->meetingAttenceRepository)->updateSignMeeting($mApplyId, $userId, $signData)){
            return $result;
        }
        return 0;
   }
   /**
    * 会议外部人员二维码签到
    * @param  [type] $mApplyId [description]
    * @param  [type] $userId   [description]
    * @return [type]   int     [description]
    */
   public function doExternalSign($userId,$mApplyId, $loginId) {

        if (!$loginId) {
            return ['code' => ['0x000003', 'common']];
        }
        // 判断是否有已经签到过
        $hasSign = app($this->meetingExternalAttenceRepository)->getHasSignExterUser($userId, $mApplyId);
        if(isset($hasSign[0]['meeting_sign_time']) && $hasSign[0]['meeting_sign_time'] != null ) {
            return ['code' => ['0x017027', 'meeting']];
        }
        $loginUserId = $loginId['user_id'];
        $meetingDetail = app($this->meetingApplyRepository)->getDetail($mApplyId);
        if (!$meetingDetail) {
            return '4';
        }
        $meetingApplyUser = [];
        $shareusers = [];
        if (isset($meetingDetail->meeting_apply_user) && !empty($meetingDetail->meeting_apply_user)) {
            $shareusers = explode(',',$meetingDetail->meeting_apply_user);
        }
        if (isset($meetingDetail->meeting_join_member) && !empty($meetingDetail->meeting_join_member) && $meetingDetail->meeting_join_member != 'all') {
            $meetingApplyUser = $meetingDetail->meeting_join_member;
            if (!empty($meetingApplyUser)) {
                $meetingApplyUser = explode(',', $meetingApplyUser);
            }
        }else if($meetingDetail->meeting_join_member == 'all') {
            $meetingApplyUser = app($this->userService)->getAllUserIdString([]);
            if ($meetingApplyUser) {
                $meetingApplyUser = explode(',', $meetingApplyUser);
            }
        }
        $meetingApplyUser = array_unique(array_merge($meetingApplyUser,$shareusers));
        if (!in_array($loginUserId, $meetingApplyUser)) {
            return 3;
        }
        $signData = [
            'meeting_sign_status'  => 1,
            'meeting_sign_time'    => date('Y-m-d H:i:s'),
        ];
        if($result = app($this->meetingExternalAttenceRepository)->updateSignMeeting($userId, $mApplyId, $signData)){
            return $result;
        }
        return 0;
   }
   /**
    * 导出会议记录
    *
    */
   public function exportMeetingRecord($param)
    {
        $header = [
            'user_name'             => trans('meeting.user_name'),
            'record_content'        => trans('meeting.meeting_record')
        ];
        $recordData = $this->getMeetingRecordDetails($param['meeting_apply_id']);
        $data = [];
        foreach ($recordData as $value) {
            $value['record_content'] = str_replace("<p>", '', $value['record_content']);
            $value['record_content'] = str_replace("</p>", ';', $value['record_content']);
            $value['record_content'] = str_replace("<br />", ';', $value['record_content']);
            $value['record_content'] = str_replace("<br/>", ';', $value['record_content']);
            $value['record_content'] = str_replace("&nbsp", '', $value['record_content']);
            $value['record_content'] = strip_tags($value['record_content']);
            $value['record_content'] = ltrim($value['record_content'], ';');
            $data[] = Arr::dot($value);
        }

        return compact('header', 'data');
    }

   public function getMeetingRecordDetails($mApplyId) {
        $recordDetail = app($this->meetingRecordsRepository)->getMeetingRecordDetails($mApplyId);
        return $recordDetail;
   }

   /**
    * 导出会议详情!
    */

   public function exportMeetingDetailInfo ($param)
   {
        $header = [
            "meeting_subject"       => trans('meeting.meeting_subject'),
            "meeting_apply_user_name" => trans('meeting.applicant'),
            "meeting_begin_time"    => trans('meeting.meeting_begin_time'),
            "meeting_end_time"      => trans('meeting.meeting_end_time'),
            "attence_type"          => trans('meeting.attence_type'),
            "meeting_join_member"   => trans('meeting.meeting_join_member')
        ];
        $mApplyId = $param['meeting_apply_id'];
        $showMeeting      = app($this->meetingApplyRepository)->showMeeting($mApplyId);
        if (isset($showMeeting->attence_type) && $showMeeting->attence_type == 1) {
            $header = [
                "meeting_subject"       => trans('meeting.meeting_subject'),
                "meeting_room_name"     => trans('meeting.meeting_room_id'),
                "meeting_apply_user_name" => trans('meeting.applicant'),
                "meeting_begin_time"    => trans('meeting.meeting_begin_time'),
                "meeting_end_time"      => trans('meeting.meeting_end_time'),
                "attence_type"          => trans('meeting.attence_type'),
                "meeting_join_member"   => trans('meeting.meeting_join_member'),
                "meeting_attence_user"  => trans('meeting.meeting_attence_user'),
                "meeting_refuse_user"   => trans('meeting.meeting_refuse_user'),
                "meeting_sign_in_user"  => trans('meeting.meeting_sign_in_user'),
                "meeting_not_sign_user" => trans('meeting.meeting_not_sign_user'),
            ];
        }
        $attenceUser = app($this->meetingApplyRepository)->showAttenceUser($mApplyId);
        $notAttenceUser = $this->showNotAttenceUser($mApplyId);
        $refuseUser = app($this->meetingApplyRepository)->showRefuseUser($mApplyId);
        $meetingSignInUser = app($this->meetingAttenceRepository)->getSignInUser($mApplyId);
        $meetingNotSignUser = app($this->meetingAttenceRepository)->getNotSignInUser($mApplyId);
        // 参加人员
        if(!empty($attenceUser)) {
            foreach($attenceUser as $key => $value) {
                    $attenceUsers[] = $value['meeting_attence_user'];
            }
        }

        if(!empty($attenceUsers) && isset($attenceUsers)) {
            $showMeeting->meeting_attence_user = $attenceUsers;
        }else{
            $showMeeting->meeting_attence_user = '';
        }

        if(!empty($notAttenceUser)) {
            $showMeeting->meeting_not_attence_user = $notAttenceUser;
        }else{
            $showMeeting->meeting_not_attence_user = [];
        }
        //拒绝参加人员
        if(!empty($refuseUser)) {
            foreach($refuseUser as $key => $value) {
                    $refuseUsers[] = $value['meeting_attence_user'];
            }
        }
        if(!empty($refuseUsers) && isset($refuseUsers)) {
            $showMeeting->meeting_refuse_user = $refuseUsers;
        }else {
            $showMeeting->meeting_refuse_user = [];
        }

        //已签到人员
        if(!empty($meetingSignInUser)) {
            foreach($meetingSignInUser as $key => $value) {
                    $meetingSignInUsers[] = $value->meeting_attence_user;
            }
        }
        if (!empty($meetingSignInUsers) && isset($meetingSignInUsers)) {
            $showMeeting->meeting_sign_in_user = $meetingSignInUsers;
        }else{
            $showMeeting->meeting_sign_in_user = '';
        }

        // 未签到人员
        if(!empty($meetingNotSignUser)) {
            foreach($meetingNotSignUser as $key => $value) {
                    $meetingNotSignUsers[] = $value->meeting_attence_user;
            }
        }
        // 判断是否开启了签到
        if ((isset($showMeeting->sign) && $showMeeting->sign == 1) && (!empty($meetingNotSignUsers) && isset($meetingNotSignUsers))) {
            $showMeeting->meeting_not_sign_user = $meetingNotSignUsers;
        }else{
            $showMeeting->meeting_not_sign_user = '';
        }
        if ($showMeeting->meeting_join_member == 'all') {
            $showMeeting->meeting_join_member = app($this->userService)->getAllUserIdString();
        }
        $room                                   = app($this->meetingRoomsRepository)->getDetail($showMeeting->meeting_room_id);
        $signType                               = app($this->meetingRoomsRepository)->getDetail($showMeeting->sign_type);
        $wifiName                               = app($this->meetingSignWifiRepository)->getDetail($showMeeting->meeting_sign_wifi);

        $showMeeting->meeting_room_name         = $room ? $room->room_name : '';
        $showMeeting->meeting_apply_user_name   = get_user_simple_attr($showMeeting->meeting_apply_user);
        $showMeeting->meeting_attence_user_name = get_user_simple_attr($showMeeting->meeting_attence_user);
        $showMeeting->meeting_not_attence_user_name = get_user_simple_attr($showMeeting->meeting_not_attence_user);
        $showMeeting->meeting_refuse_user_name  = get_user_simple_attr($showMeeting->meeting_refuse_user);
        $showMeeting->meeting_sign_in_user_name = get_user_simple_attr($showMeeting->meeting_sign_in_user);
        $showMeeting->meeting_not_sign_user_name    = get_user_simple_attr($showMeeting->meeting_not_sign_user);
        $showMeeting->meeting_join_member_name  = get_user_simple_attr($showMeeting->meeting_join_member);
        $showMeeting->sign_wifi_name            = $wifiName ? $wifiName->meeting_wifi_name : '';
        $showMeeting->sign_wifi_mac             = $wifiName ? $wifiName->meeting_wifi_mac : '';
        if(!$showMeeting) {
            return ['code' => ['0x000006','common']];
        }
        $showMeeting->meeting_refuse_user_name = implode(',', array_unique(array_merge(explode(',', $showMeeting->meeting_not_attence_user_name), explode(',', $showMeeting->meeting_refuse_user_name))));
        if (isset($showMeeting->attence_type)) {
            switch ($showMeeting->attence_type) {
                case '1':
                    $showMeeting->attence_type = trans('meeting.offline');
                    break;
                case '2':
                    $video = json_decode($showMeeting->meeting_video_info ?? '', true);
                    $meetingUrl = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['join_url'] : '';
                    $meetingID = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['meeting_id'] : '';
                    $meetingCode = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['meeting_code'] : '';
                    $meetingRoom = trans('meeting.video_meeting').$meetingUrl. ';'. trans('meeting.meeting_id') .': '. $meetingCode;
                    $showMeeting->attence_type = $meetingRoom;
                    break;
                default:
                    # code...
                    break;
            }
        }

        $list['meeting_subject']            = $showMeeting->meeting_subject;
        $list['meeting_apply_user_name']    = $showMeeting->meeting_apply_user_name;
        $list['meeting_room_name']          = $showMeeting->meeting_room_name;
        $list['meeting_begin_time']         = $showMeeting->meeting_begin_time;
        $list['meeting_end_time']           = $showMeeting->meeting_end_time;
        $list['meeting_join_member']        = $showMeeting->meeting_join_member_name;
        $list['meeting_attence_user']       = $showMeeting->meeting_attence_user_name;
        $list['meeting_refuse_user']        = $showMeeting->meeting_refuse_user_name;
        $list['meeting_sign_in_user']       = $showMeeting->meeting_sign_in_user_name;
        $list['meeting_not_sign_user']      = $showMeeting->meeting_not_sign_user_name;
        $list['attence_type']               = $showMeeting->attence_type;

        $data[] = Arr::dot($list);
        return compact('header', 'data');
   }

   /**
    * 会议审批列表导出
    */
    public function exportMeetingMyApproval($param) {
        $header = [
                "meeting_subject"       => trans('meeting.meeting_subject'),
                "meeting_room_name"     => trans('meeting.meeting_room_id'),
                "meeting_apply_user_name" => trans('meeting.applicant'),
                'meeting_create_time'   => trans('meeting.create_time'),
                "meeting_begin_time"    => trans('meeting.meeting_begin_time'),
                "meeting_end_time"      => trans('meeting.meeting_end_time'),
                "meeting_join_member"   => trans('meeting.meeting_join_member'),
                'meeting_approval_user' => trans('meeting.approval_user'),
                'meeting_status'        => trans('meeting.meeting_status'),
                'meeting_approval_opinion' => trans('meeting.meeting_approval_opinion'),
                'meeting_remark'        => trans('meeting.meeting_remark'),
                'meeting_begin_remark'  => trans('meeting.meeting_begin_remark'),
            ];
        $data = app($this->meetingApplyRepository)->getApproveMeetingList($param);
        $time = date("Y-m-d H:i:s", time());
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $showMeeting      = app($this->meetingApplyRepository)->showMeeting($value['meeting_apply_id']);
                // 判断时间自动结束和开始
                if (isset($value['meeting_end_time']) && $value['meeting_end_time'] <= $time && ($value['meeting_status'] == 2 || $value['meeting_status'] == 4)) {
                    $value['meeting_status'] = 5;
                }else if (isset($value['meeting_begin_time']) && ($value['meeting_begin_time'] <= $time && $value['meeting_end_time'] > $time)  && $value['meeting_status'] == 2) {
                    $value['meeting_status'] = 4;
                }
                $data[$key]['meeting_subject'] = $value['meeting_subject'];
                $data[$key]['meeting_room_name'] = $value['room_name'];
                $data[$key]['meeting_apply_user_name'] = $value['user_name'];
                $data[$key]['meeting_begin_time'] = $value['meeting_begin_time'];
                $data[$key]['meeting_end_time'] = $value['meeting_end_time'];
                if (isset($value['meeting_join_member']) && $value['meeting_join_member'] == 'all') {
                    $data[$key]['meeting_join_member'] = get_user_simple_attr(app($this->userService)->getAllUserIdString([]));
                }else{
                    $data[$key]['meeting_join_member'] = get_user_simple_attr($value['meeting_join_member']);
                }
                if (isset($value['meeting_status']) && $value['meeting_status'] == 1) {
                    if (isset($showMeeting->meeting_approvel_user) && $showMeeting->meeting_approvel_user == 'all') {
                        $data[$key]['meeting_approval_user'] = get_user_simple_attr(app($this->userService)->getAllUserIdString([]));
                    }else{
                        $data[$key]['meeting_approval_user'] = get_user_simple_attr($showMeeting->meeting_approvel_user);
                    }
                }else{
                    $data[$key]['meeting_approval_user'] = get_user_simple_attr($value['meeting_approval_user']);
                }

                $data[$key]['meeting_status'] = $this->parseMeetingStatus($value['meeting_status'], $value['meeting_apply_time']);
                $data[$key]['meeting_approval_opinion'] = $value['meeting_approval_opinion'];
                $data[$key]['meeting_remark'] = $value['meeting_remark'];
                $data[$key]['meeting_begin_remark'] = $value['meeting_begin_remark'];
                if ($value['meeting_create_time'] == '0000-00-00 00:00:00') {
                    $data[$key]['meeting_create_time'] = '';
                } else {
                    $data[$key]['meeting_create_time'] = $value['meeting_create_time'];
                }
            }
        }
        return compact('header', 'data');
    }
    /**
     * 解析会议状态
     * @param  [type] $status [description]
     * @return [type]         [description]
     */
    public function parseMeetingStatus($status, $applyTime) {
        if (!$status) {
            return '';
        }
        switch($status) {
            case 1:
                if ($applyTime != null) {
                    return trans("meeting.approving");
                }else{
                    return trans("meeting.pending");
                }
            break;
            case 2:
                return trans("meeting.has_approved");
            break;
            case 3:
                return trans("meeting.has_refused");
            break;
            case 4:
                return trans("meeting.has_begun");
            break;
            case 5:
                return trans("meeting.has_ended");
            break;
            default:
            break;
        }
    }
   /**
     * 获取即将开始的会议
     *
     * @return array 处理后的消息数组
     */
    public function meetingBeginRemind($interval = 1)
    {
        $start  = date("Y-m-d H:i:s");
        $end    = date("Y-m-d H:i:s", strtotime("+$interval minutes -1 seconds"));
        $list = app($this->meetingApplyRepository)->listBeginMeeting($start,$end);
        $messages = [];
        $handelusers = [];
        $shareusers = [];
        $users = [];
        $signUser = [];
        foreach ($list as $key => $value) {
            $shareusers = explode(',',$value['meeting_apply_user']);
            $signUser = explode(',',$value['meeting_sign_user']);
            if ($value['meeting_join_member'] == 'all') {
                $meetingJoinMemberList = app($this->userService)->getAllUserIdString();
            }else{
                $meetingJoinMemberList = $value['meeting_join_member'];
            }
            $handelusers = explode(',', $meetingJoinMemberList);
            $meetingUsers = array_unique(array_merge($shareusers,$handelusers));
            if (!empty($signUser)) {
                $meetingUsers = array_unique(array_merge($signUser,$meetingUsers));
            }
            $notAttenceUserArr = [];
            $notAttenceUser = app($this->meetingApplyRepository)->showNotAttenceUser($value['meeting_apply_id']);
            if (!empty($notAttenceUser)) {
                $notAttenceUserArr = array_column($notAttenceUser, 'meeting_attence_user');
            }
            $arr = array_diff(array_filter($meetingUsers), $notAttenceUserArr);
            $userId = implode(',',$arr);
            $roomInfo      = app($this->meetingRoomsRepository)->getDetail($value['meeting_room_id']);
            $meetingRoom   = $roomInfo->room_name ?? '';
            if ($meetingRoom) {
                $messages[$key]=[
                    'remindMark'  => 'meeting-start',
                    'toUser'      => $userId,
                    'contentParam'=> ['meetingTime'=>$value['meeting_begin_time'],'meetingSubject'=>$value['meeting_subject'], 'meetingRoom'=>$meetingRoom],
                    'stateParams' => ['meeting_apply_id'=>$value['meeting_apply_id']]
                ];
            } else {
                $video = json_decode($value['meeting_video_info'] ?? '', true);
                $meetingUrl = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['join_url'] : '';
                $meetingID = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['meeting_id'] : '';
                $meetingCode = isset($video['meeting_info_list']) ? $video['meeting_info_list'][0]['meeting_code'] : '';
                $sendInfo = $meetingUrl. ';'. trans('meeting.meeting_id') . ': '. $meetingCode;
                $messages[$key]=[
                    'remindMark'  => 'meeting-start',
                    'toUser'      => $userId,
                    'contentParam'=> ['meetingTime'=>$value['meeting_begin_time'],'meetingSubject'=>$value['meeting_subject'], 'meetingRoom'=>$sendInfo],
                    'stateParams' => ['meeting_apply_id'=>$value['meeting_apply_id']]
                ];
            }

        }
        return $messages;
    }
    /**
     * 添加会议类别
     * @param [type] $data [description]
     */
    public function addMeetingSort ($data) {
        $sort_lang = isset($data['sort_name_lang']) ? $data['sort_name_lang'] : '';
        $updateData                           = $data;
        $updateData["meeting_sort_order"] = isset($data["meeting_sort_order"]) ? $data["meeting_sort_order"]:0;
        $updateData["meeting_sort_time"]  = date('Y-m-d H:i:s');
        $updateData["meeting_approvel_user"] = isset($data['member_manage']) ? $data['member_manage'] : '';
        // 协作分类的权限范围为全体的处理
        if(isset($data["member_user"]) && $data["member_user"] == 'all') {
            $updateData['member_user'] = "all";
            unset($data['member_user']);
        } else {
            $updateData['member_user'] = isset($data["member_user"]) ? $data["member_user"] : '';
        }
        if(isset($data["member_dept"]) && $data["member_dept"] == 'all') {
            $updateData['member_dept'] = "all";
            unset($data['member_dept']);
        } else {
            $updateData['member_dept'] = isset($data["member_dept"]) ? $data["member_dept"] : '';
        }
        if(isset($data["member_role"]) && $data["member_role"] == 'all') {
            $updateData['member_role'] = "all";
            unset($data['member_role']);
        } else {
            $updateData['member_role'] = isset($data["member_role"]) ? $data["member_role"] : '';
        }
        $sortData              = array_intersect_key($updateData,array_flip(app($this->meetingSortRepository)->getTableColumns()));
        $meetingSortObject = app($this->meetingSortRepository)->insertData($sortData);
        $sortId                = $meetingSortObject->meeting_sort_id;
        $member_user           = isset($data["member_user"]) ? $data["member_user"]:"";
        $member_role           = isset($data["member_role"]) ? $data["member_role"]:"";
        $member_dept           = isset($data["member_dept"]) ? $data["member_dept"]:"";
        // 此处进行多语言测插入操作
        if ($meetingSortObject) {
            if (!empty($sort_lang) && is_array($sort_lang)) {
                foreach ($sort_lang as $key => $value) {
                    $langData = [
                        'table'      => 'meeting_sort',
                        'column'     => 'meeting_sort_name',
                        'lang_key'   => "meeting_sort_" . $sortId,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);
                }
            }else{
                $langData = [
                        'table'      => 'meeting_sort',
                        'column'     => 'meeting_sort_name',
                        'lang_key'   => "meeting_sort_" . $sortId,
                        'lang_value' => $data['meeting_sort_name'],
                    ];

                    app($this->langService)->addDynamicLang($langData);
            }
        }
        $newData['meeting_sort_name'] = 'meeting_sort_'. $sortId;
        // 插入分类权限数据
        if (!empty($member_user)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($member_user,","))) as $v) {
                $userData[] = ['meeting_sort_id' => $sortId, 'user_id' => $v];
            }
            app($this->meetingSortMemberUserRepository)->insertMultipleData($userData);
        }
        if (!empty($member_role)) {
            $roleData = [];
            foreach (array_filter(explode(',', trim($member_role,","))) as $v) {
                $roleData[] = ['meeting_sort_id' => $sortId, 'role_id' => $v];
            }
            app($this->meetingSortMemberRoleRepository)->insertMultipleData($roleData);
        }
        if (!empty($member_dept)) {
            $deptData = [];
            foreach (array_filter(explode(',', trim($member_dept,","))) as $v) {
                $deptData[] = ['meeting_sort_id' => $sortId, 'dept_id' => $v];
            }
            app($this->meetingSortMemberDepartmentRepository)->insertMultipleData($deptData);
        }
        if(app($this->meetingSortRepository)->updateData($newData, ['meeting_sort_id' => $sortId])) {
            return $sortId;
        }

        return ['code' => ['0x000003','common']];
    }
    /**
     * 获取会议类型
     *
     * @since   [2018-01-25]
     */
    public function getMeetingSort ($param) {
        $param      = $this->parseParams($param);
        $returnData = $this->response(app($this->meetingSortRepository), 'getTotal', 'getMeetingSortListRepository', $param);
        $list       = $returnData["list"];
        if($returnData["total"]) {
            foreach ($list as $key => $value) {
                if($value->memberDept != "all" && $value->memberUser != "all" && $value->memberRole != "all") {
                    if(count($value->sortHasManyUser)) {
                        $hasUserName = "";
                        $userCollect = $value->sortHasManyUser->toArray();
                        foreach ($userCollect as $userKey => $userItem) {

                            if(isset($userItem["has_one_user"]) && isset($userItem["has_one_user"]["user_name"])) {
                                $hasUserName .= $userItem["has_one_user"]["user_name"].",";
                            }
                        }
                        $hasUserName = trim($hasUserName,",");
                        if($hasUserName) {
                            $list[$key]["sortHasManyUserName"] = $hasUserName;
                        }
                    }
                    if(count($value->sortHasManyRole)) {
                        $hasRoleName = "";
                        $roleCollect = $value->sortHasManyRole->toArray();
                        foreach ($roleCollect as $roleKey => $roleItem) {
                            if(isset($roleItem["has_one_role"]) && count($roleItem["has_one_role"]) && $roleItem["has_one_role"]["role_name"]) {
                                $hasRoleName .= $roleItem["has_one_role"]["role_name"].",";
                            }
                        }
                        $hasRoleName = trim($hasRoleName,",");
                        if($hasRoleName) {
                            $list[$key]["sortHasManyRoleName"] = $hasRoleName;
                        }
                    }
                    if(count($value->sortHasManyDept)) {
                        $hasDeptName = "";
                        $deptCollect = $value->sortHasManyDept->toArray();
                        foreach ($deptCollect as $deptKey => $deptItem) {
                            if( isset($deptItem["has_one_dept"]) && count($deptItem["has_one_dept"]) && $deptItem["has_one_dept"]["dept_name"]) {
                                $hasDeptName .= $deptItem["has_one_dept"]["dept_name"].",";
                            }
                        }
                        $hasDeptName = trim($hasDeptName,",");
                        if($hasDeptName) {
                            $list[$key]["sortHasManyDeptName"] = $hasDeptName;
                        }
                    }
                }
            }
        }
        $returnData["list"] = $list;
        if (isset($returnData["list"]) && !empty($returnData["list"])) {
            foreach ($returnData["list"] as $key => $value) {
                if (isset($value['meeting_sort_name']) && !empty($value['meeting_sort_name'])) {
                        $returnData["list"][$key]['meeting_sort_name'] = mulit_trans_dynamic("meeting_sort.meeting_sort_name.meeting_sort_" .$value['meeting_sort_id']);
                        }
            }
        }
        return $returnData;
    }

    public function getMeetingSortDetail($sortId) {
        if($result = app($this->meetingSortRepository)->meetingSortData($sortId)) {
            if(count($result->sortHasManyUser)){
                $sort_user = $result->sortHasManyUser->pluck("user_id");
            }
            if(count($result->sortHasManyRole)){
                $sort_role = $result->sortHasManyRole->pluck("role_id");
            }
            if(count($result->sortHasManyDept)){
                $sort_dept = $result->sortHasManyDept->pluck("dept_id");
            }
            $result = $result->toArray();
            if(isset($sort_user))
                $result["user_id"] = $sort_user;
            if(isset($sort_role))
                $result["role_id"] = $sort_role;
            if(isset($sort_dept))
                $result["dept_id"] = $sort_dept;
            $result['manage_id'] = array_filter(explode(",", $result['meeting_approvel_user']));
            $result['meeting_sort_name'] = mulit_trans_dynamic("meeting_sort.meeting_sort_name.meeting_sort_" . $sortId);
            $result['sort_name_lang'] = app($this->langService)->transEffectLangs("meeting_sort.meeting_sort_name.meeting_sort_" . $sortId);
            return $result;
        }
        return ['code' => ['0x017030','meeting']];
    }
    public function editMeetingSortDetail($data,$sortId) {
        $sort_lang = isset($data['sort_name_lang']) ? $data['sort_name_lang'] : '';
        $updateData = $data;
        $updateData["meeting_approvel_user"] = isset($data['member_manage']) ? $data['member_manage'] : '';
        // 协作分类的权限范围为全体的处理
        if(isset($data["member_user"]) && $data["member_user"] == 'all') {
            $updateData['member_user'] = "all";
            unset($data['member_user']);
        } else {
            $updateData['member_user'] = $data["member_user"];
        }
        if(isset($data["member_dept"]) && $data["member_dept"] == 'all') {
            $updateData['member_dept'] = "all";
            unset($data['member_dept']);
        } else {
            $updateData['member_dept'] = $data["member_dept"];
        }
        if(isset($data["member_role"]) && $data["member_role"] == 'all') {
            $updateData['member_role'] = "all";
            unset($data['member_role']);
        } else {
            $updateData['member_role'] = $data["member_role"];
        }
        $sortData = array_intersect_key($updateData,array_flip(app($this->meetingSortRepository)->getTableColumns()));
        app($this->meetingSortRepository)->updateData($sortData, ['meeting_sort_id' => $sortId]);
        // 先删除已有协作分类权限数据
        $where = ['meeting_sort_id' => [$sortId]];
        app($this->meetingSortMemberUserRepository)->deleteByWhere($where);
        app($this->meetingSortMemberRoleRepository)->deleteByWhere($where);
        app($this->meetingSortMemberDepartmentRepository)->deleteByWhere($where);
        $member_user = isset($data["member_user"]) ? $data["member_user"]:"";
        $member_role = isset($data["member_role"]) ? $data["member_role"]:"";
        $member_dept = isset($data["member_dept"]) ? $data["member_dept"]:"";
        //  多语言插入
         if (!empty($sort_lang) && is_array($sort_lang)) {
                foreach ($sort_lang as $key => $value) {
                    $langData = [
                        'table'      => 'meeting_sort',
                        'column'     => 'meeting_sort_name',
                        'lang_key'   => "meeting_sort_" . $sortId,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);
                }
            }else{
                $langData = [
                        'table'      => 'meeting_sort',
                        'column'     => 'meeting_sort_name',
                        'lang_key'   => "meeting_sort_" . $sortId,
                        'lang_value' => $data['meeting_sort_name'],
                    ];

                    app($this->langService)->addDynamicLang($langData);
            }
        $newData['meeting_sort_name'] = 'meeting_sort_'. $sortId;
        // 插入协作分类权限数据
        if (!empty($member_user)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($member_user,","))) as $v) {
                $userData[] = ['meeting_sort_id' => $sortId, 'user_id' => $v];
            }
            app($this->meetingSortMemberUserRepository)->insertMultipleData($userData);
        }
        if (!empty($member_role)) {
            $roleData = [];
            foreach (array_filter(explode(',', trim($member_role,","))) as $v) {
                $roleData[] = ['meeting_sort_id' => $sortId, 'role_id' => $v];
            }
            app($this->meetingSortMemberRoleRepository)->insertMultipleData($roleData);
        }
        if (!empty($member_dept)) {
            $deptData = [];
            foreach (array_filter(explode(',', trim($member_dept,","))) as $v) {
                $deptData[] = ['meeting_sort_id' => $sortId, 'dept_id' => $v];
            }
            app($this->meetingSortMemberDepartmentRepository)->insertMultipleData($deptData);
        }
        if(app($this->meetingSortRepository)->updateData($newData, ['meeting_sort_id' => $sortId])) {
            return $sortId;
        }
        return "1";
    }
    /**
     * 删除会议类型
     */
    public function deleteMeetingSort ($sortIdString) {
        foreach (explode(',', trim($sortIdString,",")) as $key=>$sortId) {
            if($sortDataObject = app($this->meetingSortRepository)->meetingSortData($sortId)) {
                // 删除分类权限
                $where = ['meeting_sort_id' => [$sortId]];
                app($this->meetingSortMemberUserRepository)->deleteByWhere($where);
                app($this->meetingSortMemberRoleRepository)->deleteByWhere($where);
                app($this->meetingSortMemberDepartmentRepository)->deleteByWhere($where);
                app($this->meetingSortRepository)->deleteById($sortId);
                remove_dynamic_langs('meeting_sort.meeting_sort_name.meeting_sort_'.$sortId);
                if(count($sortDataObject->sortHasManySubjectList)) {
                    $subjectList = $sortDataObject->sortHasManySubjectList;
                    foreach ($subjectList as $key => $value) {
                        $this->deleteMeetingSubjectRealize($value->meeting_apply_id);
                    }
                }
            }
        }
        return "1";
    }
    /**
     * 实现删除会议的数据库操作
     * @param  [type] $subjectId [description]
     * @return [type]            [description]
     */
    function deleteMeetingSubjectRealize($subjectId) {
        app($this->meetingApplyRepository)->deleteById($subjectId);
        return "1";
    }

    /**
     * 获取有权限的会议类别列表
     *
     * @return [type] [description]
     */
    function getPermessionMeetingSort($data) {
        return app($this->meetingSortRepository)->getPermissionMeetingSortList($data);
    }
    /**
     *
     * 获取类型分类
     */
    public function getExternalUserType ($param) {
        $data = $this->response(app($this->meetingExternalTypeRepository), 'getExternalTypeTotal', 'getExternalTypeList', $this->parseParams($param));
        if (isset($data['list']) && !empty($data['list'])) {
            foreach($data['list'] as $k => $v) {
                if (isset($v['external_user_type_name']) && !empty($v['external_user_type_name'])) {
                    $data['list'][$k]['external_user_type_name'] = mulit_trans_dynamic("meeting_external_user_type.external_user_type_name." .$v['external_user_type_name']);
                }
                $data['list'][$k]['external_name_lang'] = app($this->langService)->transEffectLangs("meeting_external_user_type.external_user_type_name." .$v['external_user_type_name'], true);
            }
        }
        return $data;
    }
    /**
     * 获取外部人员选择器分类
     * @param  [type] $param [description]
     * @return [array]        [description]
     */
    public function getExternalUserTypeForList($param) {
        $param = $this->parseParams($param);
        $data = app($this->meetingExternalTypeRepository)->getExternalTypeList($param);
        if (!empty($data)) {
            foreach($data as $k => $v) {
                if (isset($v['external_user_type_name']) && !empty($v['external_user_type_name'])) {
                    $data[$k]['external_user_type_name'] = mulit_trans_dynamic("meeting_external_user_type.external_user_type_name." .$v['external_user_type_name']);
                }
                $data[$k]['external_name_lang'] = app($this->langService)->transEffectLangs("meeting_external_user_type.external_user_type_name." .$v['external_user_type_name'], true);
            }
        }
        return $data;
    }

    public function getTypeById ($param) {
        $params['search']['external_user_type'] = $param;
        return $this->response(app($this->meetingExternalUserRepository), 'getExternalUserTotal', 'getExternalUserList', $this->parseParams($params));
    }
    public function deleteUserType($typeId) {
        if(!app($this->meetingExternalUserTypeInfoRepository)->deleteById($typeId)) {
                return ['code' => ['0x000003', 'common']];
            }
        return true;
    }
    public function oneKeySignMeeting($mApplyId) {
        $data = [
            'meeting_sign_time'   => date("Y-m-d H:i:s"),
            'meeting_sign_status' => 1
        ];
        $temp = app($this->meetingAttenceRepository)->getMineMeetingAttenceDetail($mApplyId);
        if (!empty($temp)) {
            if(!app($this->meetingAttenceRepository)->editAttenceUserSignStatus($mApplyId, $data)) {
                return ['code' => ['0x017027', 'meeting']];
            }
            return true;
        } else {
            return ['code' => ['0x017029', 'meeting']];
        }

    }
    public function addExternalUserType ($data) {
        $user_name = isset($data['external_user_type_name']) ? $data['external_user_type_name'] : '';
        $userNamePyArray = convert_pinyin($user_name);
        $userType = [
            'external_user_type_name'      => $user_name,
            'external_name_py'             => $userNamePyArray[0],
            'external_name_zm'             => $userNamePyArray[1],
        ];
        $combobox_lang = isset($data['external_name_lang']) ? $data['external_name_lang'] : '';
        if ($typeObj = app($this->meetingExternalTypeRepository)->addExternalUserType($userType)) {
            $typeId = $typeObj->external_user_type_id;
            if (!empty($combobox_lang) && is_array($combobox_lang)) {
                foreach ($combobox_lang as $key => $value) {
                    $langData = [
                        'table'      => 'meeting_external_user_type',
                        'column'     => 'external_user_type_name',
                        'lang_key'   => "external_user_type_name_" . $typeId,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            }else{
                $langData = [
                        'table'      => 'meeting_external_user_type',
                        'column'     => 'external_user_type_name',
                        'lang_key'   => "external_user_type_name_" . $typeId,
                        'lang_value' => isset($data['external_user_type_name']) ? $data['external_user_type_name'] : '',
                    ];

                    app($this->langService)->addDynamicLang($langData);
            }
            $list     = app($this->meetingExternalTypeRepository)->getDetail($typeId);
            $newData = [];
            $newData = [
                'external_user_type_name' => 'external_user_type_name_'. $typeId
            ];
            if ($common_id = app($this->meetingExternalTypeRepository)->updateData($newData, ['external_user_type_id' => $typeId])) {
                return $typeId;
            }
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * @获取未参加也未拒绝的人员
     * @param  [type] $mApplyId [会议ID]
     * @return [type] $diffList [未参加人员数组]
     */
    function showNotAttenceUser($mApplyId) {
        $attenceUser = app($this->meetingApplyRepository)->showAttenceUser($mApplyId);
        $attenceUserArray = [];
        if ($attenceUser) {
            $attenceUserArray = array_column($attenceUser, 'meeting_attence_user');
        }
        $meetingJoinMember = app($this->meetingApplyRepository)->getMeetingJoinMember($mApplyId);
        if (isset($meetingJoinMember[0]) && (isset($meetingJoinMember[0]['meeting_join_member']) && !empty($meetingJoinMember[0]['meeting_join_member']))) {
            if ($meetingJoinMember[0]['meeting_join_member'] == 'all') {
                $meetingJoinMemberList = app($this->userService)->getAllUserIdString();
            }else{
                $meetingJoinMemberList = $meetingJoinMember[0]['meeting_join_member'];
            }
        }else{
            $meetingJoinMemberList = '';
        }

        $notAttenceUserArray = [];
        $meetingJoinMemberArray = [];

        if ($meetingJoinMemberList) {
            $meetingJoinMemberArray = explode(',', $meetingJoinMemberList);
        }
        $diff = array_diff($meetingJoinMemberArray, $attenceUserArray);
        if (is_array($diff) && !empty($diff)) {
            $diffList = implode(',', $diff);
        }else{
            $diffList = '';
        }
        return $diffList;
    }

    /**
     * 获取短信模块授权
     */
    public function checkPermission() {
        $phoneModule = 43;
        $module = app($this->empowerService)->getPermissionModules();
        $hasPermission = app($this->shortMessageService)->hasSMSSet();
        if ($module && in_array($phoneModule, $module) && $hasPermission) {
            return '1';
        }
        return '0';
    }
    /**
     * 获取手机APP授权
     */
    public function checkMobilePower() {
        $module = app($this->empowerService)->checkMobileEmpowerAvailability();
        $moduleEmpower = app($this->empowerService)->checkModuleWhetherExpired(192);
        if ($module && $moduleEmpower) {
            return '1';
        }
        return '0';
    }
    /**
     * 会议转日程
     *
     */
    public function meetingCalendarList($userId, $param) {
        $temp = app($this->meetingApplyRepository)->getMeetingCalendarList($userId, $param);
        $result       = [];
        $t2           = [];
        if (!empty($temp)) {
            foreach ($temp as $key => $v) {

                if (($v['meeting_status'] == 2 || $v['meeting_status'] == 4) && ($v["meeting_end_time"] < date('Y-m-d H:i:s'))) {
                    continue;
                }
                $t2["calendar_id"]      = $v["meeting_apply_id"];
                $t2["calendar_content"] = $v["meeting_subject"].'('.$v["room_name"].')';
                $t2["calendar_level"]   = 0;
                $t2["create_id"]        = $v["meeting_apply_user"];
                $t2["user_id"]          = $v["meeting_apply_user"]; //负责人
                $t2["calendar_begin"]   = ($v["meeting_begin_time"] && $v["meeting_begin_time"] != '0000-00-00 00:00:00') ? $v["meeting_begin_time"] : $v["created_at"];
                $t2["calendar_end"]     = $v["meeting_end_time"] && $v["meeting_end_time"] == "0000-00-00 00:00:00" ? '' : $v["meeting_end_time"];
                $t2["share_user"]       = $v["meeting_apply_user"]; //共享人
                $t2["join_user"]        = $v["meeting_join_member"] ? $v["meeting_join_member"] : '';
                $t2["type"]             = "meeting";
                array_push($result, $t2);
            }
        }
        return $result;
    }
    public function getAttenceStatus($mApplyId, $param) {
        $userId = isset($param['userId']) ? $param['userId'] : '';
        $result = app($this->meetingAttenceRepository)->getAttenceStatus($mApplyId, $userId);
        if (!empty($result)) {
            return '1';
        }else{
            return '2';
        }
    }
    public function getAttenceRemark($mApplyId, $param) {
        $userId = isset($param['userId']) ? $param['userId'] : '';
        $result = app($this->meetingAttenceRepository)->getAttenceRemark($mApplyId, $userId);
        $result = isset($result[0]) ? $result[0] : '';
        return $result;
    }

    /**
     * 获取某月日程的日期
     * @param  string  $date 日期 2019-09
     *
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    function getMeetingMonthHasDate($date,$userId)
    {
        $currentMonth=date("Y-m");
        if(!(preg_match("/^\d{4}-\d{2}$/",$date))){
            $date = $currentMonth;
        }

        $start_day = date("Y-m-01",strtotime($date));
        $days = date('t', strtotime($date));
        $datelist=array();
        for($i=0;$i<$days;$i++)
        {
            $datelist[]=date('Y-m-d',strtotime($start_day)+$i*24*60*60);
        }
        $newdatelist=[];
        $param['user_id'] = $userId;
        foreach ($datelist as $key => $value) {
            if(!empty(app($this->meetingApplyRepository)->getPortalApproveMeetingList($value,$param))){
                array_push($newdatelist,$value);
            }

        }
        return $newdatelist;
    }
    /**
     * 获取某月日程的日期
     * @param  string  $date 日期 2019-09
     *
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    function getMeetingMonthHasDateJoin($date,$userId)
    {
        $currentMonth=date("Y-m");
        if(!(preg_match("/^\d{4}-\d{2}$/",$date))){
            $date = $currentMonth;
        }

        $start_day = date("Y-m-01",strtotime($date));
        $days = date('t', strtotime($date));
        $datelist=array();
        for($i=0;$i<$days;$i++)
        {
            $datelist[]=date('Y-m-d',strtotime($start_day)+$i*24*60*60);
        }
        $newdatelist=[];
        $param['user_id'] = $userId;
        foreach ($datelist as $key => $value) {
            if(!empty(app($this->meetingApplyRepository)->getPortalJoinMeetingList($value,$param))){
                array_push($newdatelist,$value);
            }

        }
        return $newdatelist;
    }

    /**
     * 获取，门户我审批的会议
     * @param  [type] $date
     * @param  [type] $loginUserInfo
     * @return [type] array
     */
    public function getPortalApprovalMeeting($date, $loginUserInfo) {
        $param = [];
        $param['user_id'] = $loginUserInfo['user_id'];
        $param['role_id'] = $loginUserInfo['role_id'];
        $param['dept_id'] = $loginUserInfo['dept_id'];
        $data = app($this->meetingApplyRepository)->getPortalApproveMeetingList($date, $param);
        $results = [];
        if (!empty($data)) {
            $temp    = [];
            $begin = [];
            $time = date("Y-m-d H:i:s", time());
            //获取当前维护项目的类别
            foreach ($data as $temp) {
                $begin[] = $temp['meeting_begin_time'];
                if (($temp['meeting_end_time'] <= $time) && ($temp['meeting_status'] == 2 || $temp['meeting_status'] == 4)) {
                    $temp['meeting_status'] = 5;
                } else if (($temp['meeting_begin_time'] <= $time) && ($temp['meeting_end_time'] > $time) && ($temp['meeting_status'] == 2)) {
                    $temp['meeting_status'] = 4;
                }
                array_push($results, $temp);
            }
            array_multisort($begin, SORT_ASC, $results);
        }

        return $results;
    }

    /**
     * 门户获取我参加的会议
     * @param  [type] $date
     * @param  [type] $loginUserInfo
     * @return [type]  array
     */
    public function getPortalJoinMeeting($date, $loginUserInfo) {
        $param = [];
        $param['user_id'] = $loginUserInfo['user_id'];
        $param['role_id'] = $loginUserInfo['role_id'];
        $param['dept_id'] = $loginUserInfo['dept_id'];
        $data = app($this->meetingApplyRepository)->getPortalJoinMeetingList($date, $param);
        $results = [];
        if (!empty($data)) {
            $temp    = [];
            $begin = [];
            $time = date("Y-m-d H:i:s", time());
            //获取当前维护项目的类别
            foreach ($data as $temp) {
                $begin[] = $temp['meeting_begin_time'];
                if (($temp['meeting_end_time'] <= $time) && ($temp['meeting_status'] == 2 || $temp['meeting_status'] == 4)) {
                    $temp['meeting_status'] = 5;
                } else if (($temp['meeting_begin_time'] <= $time) && ($temp['meeting_end_time'] > $time) && ($temp['meeting_status'] == 2)) {
                    $temp['meeting_status'] = 4;
                }
                array_push($results, $temp);
            }
            array_multisort($begin, SORT_ASC, $results);
        }

        return $results;
    }
    /**
     * 获取会议设置信息
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getBaseSetting($param = []) {
        $param = $this->parseParams($param);
        $setData = app($this->meetingSetRepository)->getCalendarSetInfo($param);
        $data = [];
        if (count($setData)) {
            foreach ($setData as $key => $value) {
                $data[$value['meeting_set_key']] = $value['meeting_set_value'];
            }
        }

        return $data;
    }

    /**
     * 会议设置
     * @param [type] $data [description]
     */
    public function meetingBaseSetting($data) {
        $where = [];
        $updateData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $where = array('meeting_set_key' => array($key));
                $updateData = array('meeting_set_key' => $key, 'meeting_set_value' => $value);
                app($this->meetingSetRepository)->updateSetData($updateData, $where);
            }
        }
        return true;
    }

    public function parseMeetingDeletePermission ($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if ($urlData['m_apply_id'] == 0) {
            return ['code' => ['0x017013', 'meeting']];
        }
        $mApplyIdArray = explode(',', $urlData['m_apply_id']);
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
            if ($joinMemberArr != 'all' && in_array($currentUserId, $joinMemberArr) && $currentUserId == 'admin') {
                $arr = true;
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
           return ['code' => ['0x000006', 'common']];
        }
        return true;
    }

    private function transField($field, $value) {
        switch ($field) {
            case 'attence_type':
                $attenceType = [
                    1 => trans('meeting.offline'),
                    2 => trans('meeting.online')
                ];
                $value = $attenceType[$value] ?? '';
                break;
            default:
                # code...
                break;
        }
        return $value;
    }

    // 会议自动结束的会议定时任务同步更新日程
    public function emitEndMeetingToUpdateCalendar($interval = 1) {
        $time = date('Y-m-d H:i:s');
        // 获取全部会议状态是2和4,结束时间小于当前时间的会议
        $allMeeting = app($this->meetingApplyRepository)->getAllEndMeeting($time);
        if ($allMeeting) {
            foreach ($allMeeting as $key => $value) {
                $relationData = [
                    'source_id'     => $value['meeting_apply_id'],
                    'source_from'   => 'meeting-join'
                ];
                if ($value['attence_type'] == 2) {
                    $relationData['source_from'] = 'meeting-video';
                }
                
                app($this->calendarService)->emitComplete($relationData);
            }
        }
        return true;
    }
    public function getOccupationMeeting($applyIds) {
        $applyIds = $this->parseParams($applyIds);
        $conflictId = isset($applyIds['conflictId']) ? json_decode($applyIds['conflictId'], true) : [];
        if (empty($conflictId)) {
            return ['list' => [], 'total' => 0];
        }
        $applyIds['conflictId'] = $conflictId;
        $allMeetingList = $this->response(app($this->meetingApplyRepository), 'getOccupationMeetingTotal', 'getOccupationMeeting', $applyIds);
        $time = date("Y-m-d H:i:s");
        $result = [];
        if (isset($allMeetingList['list']) && $allMeetingList['list']) {
            //获取当前维护项目的类别
            foreach ($allMeetingList['list'] as $key => $temp) {
                if (($temp['meeting_end_time'] <= $time) && ($temp['meeting_status'] == 2 || $temp['meeting_status'] == 4)) {
                    $allMeetingList['list'][$key]['meeting_status'] = 5;
                } else if (($temp['meeting_begin_time'] <= $time) && ($temp['meeting_end_time'] > $time) && ($temp['meeting_status'] == 2)) {
                    $allMeetingList['list'][$key]['meeting_status'] = 4;
                }
                $allMeetingList['list'][$key]['meeting_apply_user_name'] = get_user_simple_attr($temp['meeting_apply_user']);
            }
        }
        return $allMeetingList;
    }
    public function getRoomsRecordData($roomId)
    {
        return app($this->meetingApplyRepository)->getRoomsRecordData($roomId);
    }
}
