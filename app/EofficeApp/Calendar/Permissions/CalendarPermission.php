<?php
namespace App\EofficeApp\Calendar\Permissions;

use DB;
class CalendarPermission
{
    private $calendarRepository;
    private $calendarAttentionRepository;
    private $calendarService;
    public function __construct()
    {
        $this->calendarRepository = 'App\EofficeApp\Calendar\Repositories\CalendarRepository';
        $this->calendarAttentionRepository = 'App\EofficeApp\Calendar\Repositories\CalendarAttentionRepository';
        $this->calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';
        $this->calendarRecordService = 'App\EofficeApp\Calendar\Services\CalendarRecordService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
    }
    /**
     * 验证删除日程权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteCalendar($own, $data, $urlData)
    {
        $currentUserId = $own['user_id'];

        if(!isset($data['calendar_id']) || empty($data['calendar_id'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $calendarId = $data['calendar_id'];

        $detail = app($this->calendarRepository)->getDetail($calendarId);

        $creator = $detail->creator;
        if($currentUserId == $creator) {
            return true;
        }
        return false;
    }

    /**
     * 验证日程删除权限
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type]
     */
    public function removeRepeatCalendar($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if(!isset($data['data']) || empty($data['data'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $calendarParentId = $data['calendarParentId'];
        $detail = app($this->calendarRepository)->getDetail($calendarParentId);
        if ($detail) {
            $creator = $detail->create_id;
            $handerUser = explode(',', $detail->user_id);
            if($currentUserId == $creator || in_array($currentUserId, $handerUser)) {
                return true;
            }
        }
        return true;;
    }

    /**
     * 验证重复日程删除权限
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type]
     */
    public function deleteAllRepeatCalendar($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if(!isset($data['calendar_id']) || empty($data['calendar_id'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $calendarId = $data['calendar_id'];

        $detail = app($this->calendarRepository)->getDetail($calendarId);
        if ($detail) {
            $creator = $detail->create_id;
            $handerUser = explode(',', $detail->user_id);
            if($currentUserId == $creator || in_array($currentUserId, $handerUser)) {
                return true;
            }
        }

        return true;
    }

    public function deleteCalendars($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if(!isset($data['calendar_id']) || empty($data['calendar_id'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $calendarId = $data['calendar_id'];
        $calendarIdArray = explode(',',$calendarId);
        $creator = [];
        $handerUser = [];
        foreach ($calendarIdArray as $value) {
            if($value){
                $result = app($this->calendarRecordService)->getCalendarOne($value,$currentUserId, []);
                $calendarInfo = $result ?? [];
                if (empty($calendarInfo)) {
                    return false;
                }
                $creator = $calendarInfo['creator'];
                $handerUser = $calendarInfo['handle_user'];
                if($currentUserId == $creator || in_array($currentUserId, $handerUser)) {
                    return true;
                    continue;
                }else{
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * 删除关注人权限判定
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function deleteDiaryAttention($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if(!isset($urlData['attentionIds']) || empty($urlData['attentionIds'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $attentionIds = array_filter(explode(',', $urlData['attentionIds']));

        if (!is_string($attentionIds[0])) {
            $detail = app($this->calendarAttentionRepository)->getDetail($attentionIds[0]);
            
            if ($detail->attention_status == 1) {
                $attentiontoUser = isset($detail->attention_to_person) ? $detail->attention_to_person : '';
                $attentionUser = isset($detail->attention_person) ? $detail->attention_person : '';
                if ($currentUserId == $attentiontoUser || $currentUserId == $attentionUser) {
                    return true;
                }
            }
            if ($detail->attention_status == 2) {
                $attentiontoUser = isset($detail->attention_to_person) ? $detail->attention_to_person : '';
                $attentionUser = isset($detail->attention_person) ? $detail->attention_person : '';
                if ($currentUserId == $attentionUser || $currentUserId == $attentiontoUser) {
                    return true;
                }
            }
        } else {
            $details = app($this->calendarAttentionRepository)->getAttentionById($currentUserId, $attentionIds[0]);
            if (empty($details)) {
                return false;
            } 
            return true;
        }
        return false;
    }
    /**
     * 更新日程关注人状态
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function updateDiaryAttention($own, $data, $urlData) {
        if(!isset($urlData[0]) || empty($urlData[0])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $urlData['id'] = $urlData[0];
        return $this->deleteDiaryAttention($own, $data, $urlData);
    }

    /**
     * 查看单条详情,创建人,办理人,分享人可查看详情
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function getCalendarOne($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if(!isset($urlData['calendar_id']) || empty($urlData['calendar_id'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $calendarId = $urlData['calendar_id'];
        $detail = DB::table('calendar')->where('calendar_id', $calendarId)->get();
        if ($detail->isEmpty()) {
            return ['code' => ['0x053007', 'calendar']];
        }
        //我关注的人
        $myAttention = app($this->calendarRecordService)->getAttentionUserList($currentUserId);
        //我的下属
        $userParams['returntype'] = "list";
        $userParams['all_subordinate'] = true;
        $subordinates = app($this->userService)->getSubordinateArrayByUserId($currentUserId,$userParams);
        $allAttentionUser = array_unique(array_merge($myAttention,array_column($subordinates, 'user_id')));
        array_push($allAttentionUser, $currentUserId);
        $param['type'] = 'all';
        $param['initdate'] = $allAttentionUser;
        $result = app($this->calendarRepository)->getCalendarAllIdList($param,$currentUserId);
        $allCalendarIds = array_column($result, 'calendar_id');
        if (in_array($calendarId, $allCalendarIds)) {
            return true;
        }
        return false;
    }

    /**
     * 获取重复列表
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function getRepeatCalendarList($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if(!isset($urlData['calendar_id']) || empty($urlData['calendar_id'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $urlData['calendar_id'] = $urlData['calendar_id'];
        $calendarId = $urlData['calendar_id'];
        $detail = app($this->calendarRepository)->getCalendarOne($calendarId);
        $info = $detail[0] ?? [];
        $shareUser = [];
        $handerUser = [];
        // 需要判断下级的权限
        $creator = $info['creator'] ?? '';
        if (isset($info['calendar_has_many_handle']) && !empty($info['calendar_has_many_handle'])) {
            $handerUser = array_column($info['calendar_has_many_handle'], 'user_id');
        }
        if (isset($info['calendar_has_many_share']) && !empty($info['calendar_has_many_share'])) {
            $shareUser = array_column($info['calendar_has_many_share'], 'user_id');
        }
        $accessUserIds = app($this->calendarRecordService)->getAllAccessUserIds($currentUserId, true);
        // 办理人, 共享人交集
        $handerArr = array_intersect($handerUser, $accessUserIds);
        $shareArr = array_intersect($shareUser, $accessUserIds);
        $groupUserId = app($this->calendarRecordService)->getgroupUserIds($own);
        $handerArr = array_intersect($handerUser, $groupUserId);
        if($currentUserId == $creator || in_array($currentUserId, $accessUserIds) || in_array($currentUserId, $handerUser) || in_array($currentUserId, $shareUser) || $handerArr || $shareArr ) {
            return true;
        }
        return false;
    }

    /**
     * 中间列取消关注
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function cancelCalendarAttention($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['user_id']) && empty($urlData['user_id'])) {
            return ['code' => ['0x053006', 'calendar']];
        }
        $attentionToUser = $urlData['user_id'];
        $details = app($this->calendarAttentionRepository)->getAttentionByWhere($currentUserId, $attentionToUser);
        $detail = $details[0] ?? '';
        if ($currentUserId == $detail['attention_person'] && $attentionToUser == $detail['attention_to_person']) {
            return true;
        }
        return false;
    }
}
