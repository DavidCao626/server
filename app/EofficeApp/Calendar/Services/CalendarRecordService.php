<?php
namespace App\EofficeApp\Calendar\Services;
/**
 * @日程模块服务类
 */
class CalendarRecordService extends CalendarBaseService 
{

    private $calendarRepository;
    private $userRepository;
    private $calendarAttentionRepository;
    private $attachmentService;
    private $userService;
    private $userSuperiorRepository;
    private $calendarHandleUserRelationRepository;
    private $calendarSettingService;
    private $calendarShareUserRelationRepository;
    /**
     * @注册日程相关的资源库对象
     * @param \App\EofficeApp\Repositories\CalendarRepository $calendarRepository
     */
    public function __construct() 
    {
        parent::__construct();
        $this->calendarSettingService = 'App\EofficeApp\Calendar\Services\CalendarSettingService';
        $this->calendarRepository = 'App\EofficeApp\Calendar\Repositories\CalendarRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->calendarAttentionRepository = 'App\EofficeApp\Calendar\Repositories\CalendarAttentionRepository';
        $this->userSuperiorRepository = 'App\EofficeApp\User\Repositories\UserSuperiorRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->calendarHandleUserRelationRepository = 'App\EofficeApp\Calendar\Repositories\CalendarHandleUserRelationRepository';
        $this->calendarShareUserRelationRepository = 'App\EofficeApp\Calendar\Repositories\CalendarShareUserRelationRepository';
        $this->calendarTypeRepository = 'App\EofficeApp\Calendar\Repositories\CalendarTypeRepository';
        $this->calendarSaveFilterRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSaveFilterRepository';
        $this->roleService = 'App\EofficeApp\Role\Services\RoleService';
    }
    /**
     * @获取我的日程日历初始日程列表
     * 
     * @param type $param
     * @param type $userId
     * 
     * @return 列表 | array
     */
    public function getInitList($param, $userId) 
    {
        $param = $this->parseParams($param);
        if (isset($param['calendar_type_id']) && !empty($param['calendar_type_id'])) {
            $param['calendar_type_id'] = explode(',', $param['calendar_type_id']);
        } else {
            unset($param['calendar_type_id']);
        }
        if (isset($param['user_id']) && $param['user_id'] === 0) {
            $param['user_id'] = $userId;
        }
        if (isset($param['type']) && $param['type'] == 'other') {
            $user_id = $this->defaultValue('user_id', $param, $userId);
            $param['user_scope'] = [];
            switch ($user_id) {
                case 'my':
                    $param['user_scope'] = $this->getAllAccessUserIds($userId);
                    // $groupUserId = $this->getgroupUserIds(own());
                    // $param['user_scope'] = array_unique(array_merge($param['user_scope'], $groupUserId));
                    array_push($param['user_scope'], $userId);
                    break;
                case 'attention_all':
                    //我关注的人
                    $param['user_scope'] = $this->getAttentionUserIds($userId);
                    $groupUserId = $this->getgroupUserIds(own());
                    $param['user_scope'] = array_unique(array_merge($param['user_scope'], $groupUserId));
                    app($this->calendarAttentionRepository)->updateAttentionUserRead($userId, $param['user_scope']);
                    break;
                case 'subordinate_all':
                    //我的下属
                    $param['user_scope'] = $this->getSubordinateUserIds($userId, true);
                    break;
            }
        }
        $calendarList = [];
        if (isset($param['group_id'])) {
            $calendarList = $this->getGroupAllCalendar($param, $userId);
        } else if (isset($param['id']) && $param['id']) {
            $groupUserId = $this->getGroupShareUserIds($param['id']);
            $calendarList = app($this->calendarRepository)->getGroupCalendarList($groupUserId, $param);
            // $param['user_scope'] = array_unique(array_merge($param['user_scope'], $groupUserId));
            // $calendarList = app($this->calendarRepository)->getInitList($param, $userId)->toArray();
            // dd($calendarList);
        } else {
            $calendarList = app($this->calendarRepository)->getInitList($param, $userId)->toArray();
        }
        if ($calendarList) {
            // 获取配置
            $calendarLevel = app($this->calendarSettingService)->getBaseSetInfo('calendar_level_key');
            $userIds = array_column($calendarList, 'creator');
            $userNameMap = $this->getUserNameMapById($userIds);
            foreach ($calendarList as $key => $value) {
                // 状态
                $handleStatus = app($this->calendarHandleUserRelationRepository)->getHandleDetail($value['calendar_id'], $userId);
                $calendarList[$key]['calendar_status'] = isset($handleStatus->calendar_status) ? $handleStatus->calendar_status : 0;
                $calendarList[$key]['creator_name'] = $userNameMap[$value['creator']] ?? '';
                $calendarList[$key]['type_name'] =  mulit_trans_dynamic('calendar_type.type_name.'. $value['type_name'] ?? '');;
                $calendarList[$key]['calendar_level_detail'] = $this->parseCalendarLevel($value['calendar_level']);
                $calendarList[$key]['calendar_end'] = ($value['calendar_end'] == '0000-00-00 00:00:00' || $value['calendar_end'] == '') ? $value['calendar_end'] = '' : $value['calendar_end'];
                
                if ($calendarLevel == 1) {
                    $calendarList[$key]['calendar_contents'] = strip_tags($value['calendar_content']);
                    $calendarList[$key]['calendar_level_set'] = $calendarLevel;
                    $calendarList[$key]['calendar_content'] = '[' .  $calendarList[$key]['calendar_level_detail'] . ']' . strip_tags($value['calendar_content']);
                } else {
                    $calendarList[$key]['calendar_content'] = strip_tags($value['calendar_content']);
                }
            }
        }
        
        return ['total' => count($calendarList), 'list' => $calendarList];
    }
    /**
     * 获取关注人id
     * 
     * @param type $userId
     * 
     * @return type
     */
    public function getAttentionUserIds($userId)
    {
        $attentionsParam = [
            'search' => [
                'attention_person' => [$userId],
                'attention_status' => [2]
            ],
            'getAll' => 1
        ];
        
        $myAttentions = app($this->calendarAttentionRepository)->calendarAttentionList($attentionsParam);
        
        return empty($myAttentions) ? [] : array_column($myAttentions, 'attention_to_person');
    }
    /**
     * 获取下属用户
     * 
     * @staticvar array $allUserId
     * @param type $userId
     * 
     * @return array
     */
    private function getAllSubordinateUsers($userId)
    {
        static $allUserId = [];
        $users = app($this->userSuperiorRepository)->getSuperiorUsers($userId);
        if(count($users) > 0) {
            $userId = array_column($users, 'user_id');
            $allUserId = array_merge($allUserId, $userId);
            $this->getAllSubordinateUsers($userId);
        }
        return array_unique($allUserId);
    }
    /**
     * 获取下属用户ID
     * 
     * @param type $userId
     * @param type $allSub
     * @param type $includeLeave
     * 
     * @return array
     */
    public function getSubordinateUserIds($userId, $allSub = false, $includeLeave = false)
    {
        $subUserIds = [];
        $attentionKey = app($this->calendarSettingService)->getBaseSetInfo('calendar_default_attention');
        if($attentionKey && $allSub) {
            $subUserIds = $this->getAllSubordinateUsers($userId);
        } else {
            $users = app($this->userSuperiorRepository)->getSuperiorUsers($userId);
            if(count($users) > 0) {
                $subUserIds = array_column($users, 'user_id');
            }
        }
        if(!$includeLeave) {
            if(!empty($subUserIds)) {
                $users = app($this->userRepository)->getNoLeaveUsersByUserId($subUserIds);
                
                $subUserIds = $users->isEmpty() ? [] : array_column($users->toArray(), 'user_id');
            }
        }
        return $subUserIds;
    }
    /**
     * @获取日程管理列表
     * 
     * @param type $param
     * @param type $userId
     * 
     * @return 列表 | array
     */
    public function getCalendarList($param, $userId) 
    {
        $param = $this->parseParams($param);
        $type = $this->defaultValue('type', $param, 'all');
        
        switch ($type) {
            case 'all':case 'mycreate':
                $param['user_scope'] = $this->getAllAccessUserIds($userId);
                $groupUserId = $this->getgroupUserIds(own());
                $param['user_scope'] = array_unique(array_merge($param['user_scope'], $groupUserId));
                array_push($param['user_scope'], $userId);
                break;
            case 'attention':
                //我关注的人
                $param['user_scope'] = $this->getAttentionUserIds($userId);
                $groupUserId = $this->getgroupUserIds(own());
                $param['user_scope'] = array_unique(array_merge($param['user_scope'], $groupUserId));
                app($this->calendarAttentionRepository)->updateAttentionUserRead($userId, $param['user_scope']);
                break;
            case 'subordinate':
                //我的下属
                $param['user_scope'] = $this->getSubordinateUserIds($userId, true);
                break;
        }
        if ($type == 'mycreate' && (isset($param['create_id']) && $param['create_id'] != $userId)) {
            $param['user_scope']  = $this->getAllAccessUserIds($userId);
            $groupUserId = $this->getgroupUserIds(own());
            $param['user_scope'] = array_unique(array_merge($param['user_scope'], $groupUserId));
            $param['user_scope'] = array_unique(array_merge($param['user_scope'], [$userId]));
        }
        $total = app($this->calendarRepository)->getCalendarTotal($param, $userId, $type);

        if($total == 0){
            return ['total' => $total, 'list' => []];
        }
        if (!isset($param['response']) || $param['response'] == 'data') {
            if (isset($param['autoFixPage']) && $total && isset($param['limit']) && isset($param['page']) && $param['page'] > 1) {
                if ($total) {
                    $param['page'] = 1;
                }

                $totalPage = ceil($total/$param['limit']);
                if ($totalPage < $param['page']) {
                    $param['page'] = $totalPage;
                }
            }
        }
        $list = app($this->calendarRepository)->getCalendarList($param, $userId, $type);

        return ['total' => $total, 'list' => $this->handleCalendarList($list)];
    }

    /**
     * @获取单条日程
     * 
     * @param type $params 日程id
     * @param type $response
     * 
     * @return 列表 | array
     */
    public function getCalendarOne($calendarId, $userId, $params) 
    {

        if (!$calendarId) {
            return ['code' => ['0x000003', 'common']];
        }
        if (isset($params['type'])) {
            $params['user_scope'] = [];
            switch ($params['type']) {
                case 'my':case 'mine':
                    $params['user_scope'] = $this->getAllAccessUserIds($userId);
                    $groupUserId = $this->getgroupUserIds(own());
                    $params['user_scope'] = array_unique(array_merge($params['user_scope'], $groupUserId));
                    array_push($params['user_scope'], $userId);
                    break;
                case 'myAttention':
                    //我关注的人
                    $params['user_scope'] = $this->getAttentionUserIds($userId);
                    $groupUserId = $this->getgroupUserIds(own());
                    $params['user_scope'] = array_unique(array_merge($params['user_scope'], $groupUserId));
                    break;
                case 'mySubordinate':
                    //我的下属
                    $params['user_scope'] = $this->getSubordinateUserIds($userId, true);
                    break;
            }
        
        }
        $calendarDetail = app($this->calendarRepository)->getCalendarDetail($calendarId, $userId, $params);
        $detail = $calendarDetail[0] ?? [];
        if (!$detail) {
            return ['code' => ['0x053007', 'calendar']];
        }
        // 查班里人状态
        $handleStatus = app($this->calendarHandleUserRelationRepository)->getHandleDetail($calendarId, $userId);
        $detail['calendar_status'] = isset($handleStatus->calendar_status) ? $handleStatus->calendar_status : 0;
        if (!app($this->calendarRepository)->hasViewPermission($userId, $calendarId, $params) && (isset($params['type']) && $params['type'] != 'mine')) {
            return ['code' => ['0x000006', 'common']];
        }
        $typeId = $detail['calendar_type_id'] ?? 0;
        $handleUserId = app($this->calendarHandleUserRelationRepository)->getListById($calendarId);
        $shareUserId = app($this->calendarShareUserRelationRepository)->getListById($calendarId);
        $typeName = app($this->calendarTypeRepository)->getDetail($typeId);
        $typeDetail = $typeName['type_name'] ?? '';
        $detail['handle_user'] = array_column($handleUserId, 'user_id');
        $detail['share_user'] = array_column($shareUserId, 'user_id');
        $detail['calendar_content'] = strip_tags($detail['calendar_content']);
        $detail['calendar_remark'] = strip_tags($detail['calendar_remark']);
        $detail['type_name'] = mulit_trans_dynamic('calendar_type.type_name.'. $typeDetail);
        $detail['relation_name'] = trans('calendar.relation') . trans('calendar.relation_name_' . $detail['module_id']);
        $detail['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'calendar', 'entity_id' => $calendarId]);
        // 设为已读
        // 把办理人已读去除
        $allReadUserIds = array_unique(array_merge($detail['handle_user'], $detail['share_user']));
        app($this->calendarAttentionRepository)->updateAttentionUserRead($userId, $allReadUserIds);
        return $detail;
    }
    /**
     * @获取重复日程列表
     * 
     * @param type $calendarId
     * @param type $param
     * 
     * @return 列表 | array
     */
    public function getRepeatCalendarList($calendarId, $param, $userId) 
    {
        if (!$calendarId) {
            return ['code' => ['0x000003', 'common']];
        }

        $param = $this->parseParams($param);
        if (!app($this->calendarRepository)->hasViewPermission($userId, $calendarId, $param)) {
            return ['code' => ['0x000006', 'common']];
        }
        $total = app($this->calendarRepository)->getRepeatCalendarTotal($calendarId, $param);
        if($total == 0){
            return ['total' => $total, 'list' => []];
        }
        
        $list = app($this->calendarRepository)->getRepeatCalendarList($calendarId, $param);
        
        return ['total' => $total, 'list' => $this->handleCalendarList($list)];
    }
    /**
     * 处理日程列表
     * 
     * @param type $list
     * 
     * @return array
     */
    private function handleCalendarList($list)
    {
        if(empty($list)){
            return [];
        }
        $userIds = array_reduce($list, function($carry, $item) {
            $uIds = array_column($item['calendar_has_many_handle'], 'user_id');
            array_push($uIds, $item['creator']);
            return array_merge($carry, $uIds);
        }, []);

        $userNameMap = $this->getUserNameMapById(array_unique($userIds));
        foreach ($list as $k => $item) {
            $handleUser = array_reduce($item['calendar_has_many_handle'], function($carry, $item) use($userNameMap) {
                $carry .= ($userNameMap[$item['user_id']] ?? '') . ",";
                return $carry;
            });
            $list[$k]['handle_user'] = rtrim($handleUser, ',');
            $list[$k]['calendar_content'] = strip_tags($item['calendar_content']);
            $list[$k]['calendar_end'] = $item['calendar_end'] == '0000-00-00 00:00:00' ? '' : $item['calendar_end'];
        }
        return $list;
    }
    /**
     * 获取某月日程的日期
     * 
     * @param  string  $date 日期 2019-09
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    public function getCalendarMonthHasDate($date, $userId) 
    {
        $dateArray = $this->getDateArrayByMonth($date);
        $start = date("Y-m-01", strtotime($date));
        $end = $date . '-' . date('t', strtotime($date));
        $lists = app($this->calendarRepository)->getCalendarsByDateRange($start, $end, $userId);
        if($lists->isEmpty()) {
            return [];
        }
        $map = [];
        foreach ($lists as $item) {
            $calendarBeginDate = date('Y-m-d',strtotime($item->calendar_begin));
            $calendarEndDate = date('Y-m-d',strtotime($item->calendar_end));
            foreach ($dateArray as $key => $date){
                if($date >= $calendarBeginDate && $date <= $calendarEndDate) {
                    $map[$date] = true;
                    unset($dateArray[$key]);
                }
            }
        }
        return array_keys($map);
    }
    /**
     * 获取某天日程
     * 
     * @param  string  $date 日期 2019-09
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     */
    public function getOneDayCalendar($date, $userId, $params) 
    {

        $currentMonth = date("Y-m-d");
        if (!(preg_match("/^\d{4}-\d{2}-\d{2}$/", $date))) {
            $date = $currentMonth;
        }
        $calendars = app($this->calendarRepository)->getCalendarMonthHasDate($date, $userId);
        if($calendars->isEmpty()) {
            return [];
        }
        $calendarTypeMap = app($this->calendarSettingService)->getCalendarTypeMap();
        $calendarLevel = app($this->calendarSettingService)->getBaseSetInfo('calendar_level_key');
        foreach ($calendars as &$calendar) {
            $calendar->mark_color = isset($calendarTypeMap[$calendar->calendar_type_id]) ? $calendarTypeMap[$calendar->calendar_type_id]['mark_color'] : '';
            $calendar->set_level_prefix = $calendarLevel;
        }
        return $calendars;
    }
    /**
     * 默认关注组织树
     * 
     * @param  [type] $userId
     * @param  [type] $params
     * @param  [type] $own   
     * 
     * @return [type]  array      
     */
    public function getAttentionTree($userId, $own)
    {
        if (!$userId) {
            $userId = $own['user_id'] ?? '';
        }
        // 获取当前用户的下属
        $userParams['returntype'] = "list";
        $userParams['all_subordinate'] = false;
        $userParams['include_supervisor'] = true;
        $subUsers = app($this->userService)->getSubordinateArrayByUserId($userId, $userParams);
        // 获取设置信息
        $calendarDefaultAttention = app($this->calendarSettingService)->getBaseSetInfo('calendar_default_attention');
        if ($calendarDefaultAttention == 1) {
            if ($subUsers) {
                foreach ($subUsers as $key => $user) {
                    if (isset($user['user_has_many_subordinate']) && !empty($user['user_has_many_subordinate'])) {
                        foreach ($user['user_has_many_subordinate'] as $k => $v) {
                            $userStatus = app($this->userSystemInfoRepository)->getUserStatus($v['user_id']);
                            if ($userStatus != 2) {
                                $subUsers[$key]['has_children'] = 1;
                            }
                        }
                    }
                }
            }
        }
        
        return $subUsers;
    }
    public function exportScheduleViewList($params)
    {
        $own = $params['user_info'];
        unset($params['user_info']);
        list($title, $header) = $this->getExportScheduleViewListHeader($params);

        $body = $this->getExportScheduleViewListBody($params, $own);

        $dateUnit = $this->defaultValue('date_unit', $params, 'day');
        if($dateUnit == 'day') {
            $width = 500;
        } else if($dateUnit == 'week') {
            $width = 200;
        } else {
            $width = 100;
        }
        $columns = count($header) + 1;
        $table = '<table border="1px" style="border-color:#333;">';
        //标题
        $table .= '<tr style="height:30px;font-size:16px;"><td colspan="' . $columns . '" style="border-color:#333;background:#eee;">' . $title . '</td></tr>';
        // 表格头
        $table .= '<tr style="height:25px;"><td style="border-color:#333;width:100px;background:#FCD3C6;">办理人</td>';
        foreach ($header as $item) {
            $table .= '<td style="border-color:#333;width:'.$width.'px;background:#FCD3C6;">' . $item . '</td>';
        }
        $table .= '</tr>';
        $calendarLevel = app($this->calendarSettingService)->getBaseSetInfo('calendar_level_key');
        // 表格体
        if (count($body) > 0) {
            foreach ($body as $user) {
                $table .= '<tr style="height:25px;"><td style="border-color:#333;">' . $user['user_name'] . '</td>';
                if (empty($user['calendars'])) {
                    for ($i = 0; $i < $columns - 1; $i++) {
                        $table .= '<td style="border-color:#333;"></td>';
                    }
                } else {
                    foreach ($user['calendars'] as $column) {
                        if (empty($column)) {
                            $table .= '<td style="border-color:#333;"></td>';
                        } else {
                            $table .= '<td style="border-color:#333;">';
                            foreach ($column as $item) {
                                if ($calendarLevel) {
                                    $item['calendar_level_detail'] = $this->parseCalendarLevel($item['calendar_level']);
                                    $table .= '<span style="color:'.$item['mark_color'].'">'. "[".$item['calendar_level_detail']."]".$item['content'] . '</span><br>';
                                } else {
                                    $table .= '<span style="color:'.$item['mark_color'].'">'. $item['content'] . '</span><br>';
                                }
                            }
                            $table .= '</td>';
                        }
                    }
                }
                $table .= '</tr>';
            }
        }
        $table .= '</table>';
        return $table;
    }
    private function getExportScheduleViewListHeader($params)
    {
        $params = $this->parseParams($params);
        $dateUnit = $this->defaultValue('date_unit', $params, 'day');
        $start = $this->defaultValue('start', $params, date('Y-m-d') . ' 00:00:00');
        $end = $this->defaultValue('end', $params, date('Y-m-d') . ' 23:59:59');
        $title = trans('calendar.calendar_view_list').'(';
        $header = [];
        if($dateUnit == 'month') {
            $title .= date('Y', strtotime($start)) . trans('calendar.year') . date('m', strtotime($start)) . '月)';
            $header = $this->getDateArrayByMonth(date('Y-m', strtotime($start)));
        } else if($dateUnit == 'week') {
            $title .= date('Y-m-d', strtotime($start)) . '~' . date('Y-m-d', strtotime($end)) . ')';
            $header = $this->getWeekDateArray(date('Y-m-d', strtotime($start)));
        } else {
            $title .= date('Y-m-d', strtotime($start)) . ')';
            $header = [trans('calendar.morning'), trans('calendar.afternoon')];
        }
        return [$title, $header];
    }
    private function getExportScheduleViewListBody($params, $own)
    {
        return $this->handleScheduleViewList($params, $own, function($handleUserRelation, $calendarMap, $calendarTypeMap, $start, $end, $dateUnit) {
            if ($dateUnit == 'month') {
                $size = date('t', strtotime(date('Y-m', strtotime($start))));
            } else if ($dateUnit == 'week') {
                $size = 7;
            } else {
                $size = 2;
            }
            $tempItems = [];
            for ($i = 0; $i < $size; $i++) {
                $tempItems[] = [];
            }

            $startDate = date('Y-m-d', strtotime($start));
            $newCalendarMap = [];
            foreach ($handleUserRelation as $userId => $oneUserCalendar) {
                $items = $tempItems;
                foreach ($oneUserCalendar as $item) {
                    if (isset($calendarMap[$item['calendar_id']])) {
                        $calendar = $calendarMap[$item['calendar_id']];
                        $calendarBegin = max($calendar['calendar_begin'], $start);
                        $calendarEnd = min($calendar['calendar_end'], $end);
                        if ($dateUnit == 'week') {
                            list($itemStart, $itemEnd) = $this->weekTimeToOffset($calendarBegin, $calendarEnd, $startDate, true);
                        } else if ($dateUnit == 'month') {
                            list($itemStart, $itemEnd) = $this->monthTimeToOffset($calendarBegin, $calendarEnd);
                        } else if ($dateUnit == 'day') {
                            list($itemStart, $itemEnd) = $this->dayTimeToOffset($calendarBegin, $calendarEnd, true);
                        }
                        for ($j = $itemStart; $j <= $itemEnd; $j ++) {
                            $items[$j][] = [
                                'content' => $calendar['calendar_content'],
                                'mark_color' => isset($calendarTypeMap[$calendar['calendar_type_id']]) ? $calendarTypeMap[$calendar['calendar_type_id']]['mark_color']: '#999',
                                'calendar_level' => $calendar['calendar_level']
                            ];
                        }
                    }
                }
                $newCalendarMap[$userId] = $items;
            }
            return $newCalendarMap;
        }, function($userIds, $calendarsMap, $userNameMap) {
            $newCalendarArray = [];
            foreach ($userIds as $userId) {
                $calendars = $calendarsMap[$userId] ?? [];
                $newCalendarArray[] = [
                    'user_name' => $userNameMap[$userId] ?? '',
                    'calendars' => $calendars
                ];
            }
            return $newCalendarArray;
        });
    }
    private function handleScheduleViewList($params, $own, $handle, $combine)
    {
        $params = $this->parseParams($params);
        $dateUnit = $this->defaultValue('date_unit', $params, 'day');
        $filterType = $this->defaultValue('filter_type', $params, 'fixed');
        $calendarTypeId = $this->defaultValue('calendar_type_id', $params, 'all');
        $start = $this->defaultValue('start', $params, date('Y-m-d') . ' 00:00:00');
        $end = $this->defaultValue('end', $params, date('Y-m-d') . ' 23:59:59');
        $userIds = [];
        if ($filterType == 'fixed') {
            $filterId = $this->defaultValue('filter_id', $params, 1);
            $userIds = $this->getUserIdsByFilterId($filterId, $own);
            if ($filterId == 1 || $filterId == 4) {
                $accessUserIds = $this->getAllAccessUserIds($own['user_id']);
            } else {
                $accessUserIds = $userIds;
            }
        } else {
            $accessUserIds = $this->getAllAccessUserIds($own['user_id']);
            $customFilterType = $this->defaultValue('custom_filter_type', $params, 'user');
            if ($customFilterType == 'user') {
                $userIds = json_decode($this->defaultValue('user_id', $params, '[]'));
            } else {
                $deptId = json_decode($this->defaultValue('dept_id', $params, '[]'));
                if (!empty($deptId)) {
                    $users = app($this->userRepository)->getUserByAllDepartment($deptId);
                    $userIds = array_column($users->toArray(), 'user_id');
                }
            }
        }
        $search = [
            'calendar_begin' => [$end, '<='],
            'calendar_end' => [$start, '>=']
        ];
       
        if ($calendarTypeId != 'all') {
            $search['calendar_type_id'] = [json_decode($calendarTypeId), 'in'];
        }
        if (!empty($userIds)) {
            $lists = app($this->calendarRepository)->getScheduleViewList($search, $accessUserIds);
            $newCalendarMap = [];
            if (count($lists) > 0) {
                $calendarTypeMap = app($this->calendarSettingService)->getCalendarTypeMap();
                $handleUserRelation = $this->getHandleUserMapByCalendarIds(array_column($lists, 'calendar_id'));
                $calendarMap = $this->arrayItemMapWithKey($lists);
                $newCalendarMap = $handle($handleUserRelation, $calendarMap, $calendarTypeMap,$start, $end, $dateUnit);
            }
            
            return $combine($userIds, $newCalendarMap, $this->getUserNameMapById($userIds));
        }
        return [];
    }
    /**
     * 获取日程一览表数据
     * 
     * @param type $params
     * @param type $own
     * 
     * return array
     */
    public function getScheduleViewList($params, $own)
    {
        return $this->handleScheduleViewList($params, $own, function($handleUserRelation, $calendarMap, $calendarTypeMap, $start, $end, $dateUnit){
            $newCalendarMap = [];
            $startDate = date('Y-m-d', strtotime($start));
            $calendarLevel = app($this->calendarSettingService)->getBaseSetInfo('calendar_level_key');
            foreach ($handleUserRelation as $userId => $oneUserCalendar) {
                $items = [];
                foreach ($oneUserCalendar as $item) {
                    if (isset($calendarMap[$item['calendar_id']])) {
                        $calendar = $calendarMap[$item['calendar_id']];
                        $calendarBegin = max($calendar['calendar_begin'], $start);
                        $calendarEnd = min($calendar['calendar_end'], $end);
                        if ($dateUnit == 'week') {
                            list($itemStart, $itemEnd) = $this->weekTimeToOffset($calendarBegin, $calendarEnd, $startDate);
                            $style = $this->getTimelineStyle($itemStart, $itemEnd, 100 / 14);
                        } else if ($dateUnit == 'month') {
                            list($itemStart, $itemEnd) = $this->monthTimeToOffset($calendarBegin, $calendarEnd);
                            $style = $this->getTimelineStyle($itemStart, $itemEnd, 121, 'px');
                        } else if ($dateUnit == 'day') {
                            list($itemStart, $itemEnd) = $this->dayTimeToOffset($calendarBegin, $calendarEnd);
                            $style = $this->getTimelineStyle($itemStart, $itemEnd, 100 / 24);
                        }
                        if ($calendarLevel) {
                            $calendarMap[$item['calendar_id']]['calendar_level_detail'] = $this->parseCalendarLevel($calendar['calendar_level']);
                            $items[] = [
                                'calendar_id' => $item['calendar_id'],
                                'calendar_level' => $calendar['calendar_level'],
                                'mark_color' => isset($calendarTypeMap[$calendar['calendar_type_id']]) ? $calendarTypeMap[$calendar['calendar_type_id']]['mark_color']: '#999',
                                'calendar_content' => '[' .  $calendarMap[$item['calendar_id']]['calendar_level_detail'] . ']' . strip_tags($calendar['calendar_content']),
                                'start' => $itemStart,
                                'end' => $itemEnd,
                                'style' => $style
                            ];
                        } else {
                            $items[] = [
                                'calendar_id' => $item['calendar_id'],
                                'calendar_type_id' => $calendar['calendar_type_id'],
                                'mark_color' => isset($calendarTypeMap[$calendar['calendar_type_id']]) ? $calendarTypeMap[$calendar['calendar_type_id']]['mark_color']: '#999',
                                'calendar_content' => $calendar['calendar_content'],
                                'start' => $itemStart,
                                'end' => $itemEnd,
                                'style' => $style
                            ];
                        }
                        
                    }
                }
                $newCalendarMap[$userId] = $this->parseScheduleViewTimelineLevel($items);
            }
            return $newCalendarMap;
        }, function($userIds, $calendarsMap, $userNameMap){
            $newCalendarArray = [];
            foreach ($userIds as $userId) {
                $calendars = $calendarsMap[$userId] ?? [];
                $count = count($calendars);
                $showCalendar = [];
                if ($count > 10) {
                    $height = 10 * 21 + 2;
                    $showCalendar = array_slice($calendars, 0, 10);
                } else {
                    $height = $count > 1 ? ($count * 21 + 2) : 30;
                    $showCalendar = $calendars;
                }
                $newCalendarArray[] = [
                    'user_id' => $userId,
                    'user_name' => $userNameMap[$userId] ?? '',
                    'height' => $height,
                    'calendars' => $calendars,
                    'show_calendars' => $showCalendar
                ];
            }
            return $newCalendarArray;
        });
    }
    /**
     * 根据用户ID获取用户名称
     * 
     * @param array $userIds
     * 
     * @return array
     */
    private function getUserNameMapById(array $userIds)
    {
        if(empty($userIds)) {
            return [];
        }
        
        $users = app($this->userRepository)->getUserNames($userIds);
        
        return $users->mapWithKeys(function($user) {
            return [$user->user_id => $user->user_name];
        });
    }
    /**
     * 获取日程一览表时间线样式
     * 
     * @param type $start
     * @param type $end
     * @param type $size
     * @param type $unit
     * 
     * @return array
     */
    private function getTimelineStyle($start, $end, $size, $unit = '%')
    {
        $left = $start * $size;
        $width = ($end - $start + 1 ) * $size;
        return ['left' => $left . $unit, 'width' => $width . $unit];
    }
    /**
     * 获取办理人和日程的关系数组
     * 
     * @param type $allCalenarId
     * 
     * @return array
     */
    private function getHandleUserMapByCalendarIds($allCalenarId)
    {
        $handleUserRel = app($this->calendarHandleUserRelationRepository)->getCalendarHanderUserRelationByIds($allCalenarId);
        
        return $this->arrayItemsMapWithKey($handleUserRel);
    }
    /**
     * 获取有权限访问的所有用户
     * 
     * @param type $userId
     * @param type $includeSelf
     * 
     * @return array
     */
    public function getAllAccessUserIds($userId, $includeSelf = true)
    {
        //我关注的人
        $attentionUserIds = $this->getAttentionUserIds($userId);
        //我的下属
        $subUserIds = $this->getSubordinateUserIds($userId, true, true);
        $userIds = array_unique(array_merge($attentionUserIds, $subUserIds));
        if ($includeSelf) {
            array_push($userIds, $userId);
        }

        return $userIds;
    }
    /**
     * 将开始结束时间解析为偏移量（月）
     * 
     * @param type $calendarBegin
     * @param type $calendarEnd
     * 
     * @return array
     */
    private function monthTimeToOffset($calendarBegin, $calendarEnd)
    {
        $startDay = intval(date('d', strtotime($calendarBegin)));
        $endDay = intval(date('d', strtotime($calendarEnd)));
        return [$startDay - 1, $endDay - 1];
    }
    /**
     * 将开始结束时间解析为偏移量（天）
     * 
     * @param type $calendarBegin
     * @param type $calendarEnd
     * 
     * @return array
     */
    private function dayTimeToOffset($calendarBegin, $calendarEnd, $export = false)
    {
        if($export) {
            $start = 0;
            $end = 1;
            if($calendarBegin >= date('Y-m-d', strtotime($calendarBegin)) . ' 12:00:00') {
                $start = 1;
            }
            if($calendarEnd <= date('Y-m-d', strtotime($calendarEnd)) . ' 12:00:00') {
                $end = 0;
            }
            return [$start, $end];
        }
        $startHour = intval(date('H', strtotime($calendarBegin)));
        $endHour = intval(date('H', strtotime($calendarEnd)));
        return [$startHour, $endHour];
    }
    /**
     * 将开始结束时间解析为偏移量（周）
     * 
     * @param type $calendarBegin
     * @param type $calendarEnd
     * @param type $startDate
     * 
     * @return array
     */
    private function weekTimeToOffset($calendarBegin, $calendarEnd, $startDate, $export = false)
    {
        $itemStartDate = date_create(date('Y-m-d', strtotime($calendarBegin)));      
        $interval = date_diff($itemStartDate, date_create(date('Y-m-d', strtotime($calendarEnd))));
        $firstInterval = date_diff($itemStartDate, date_create($startDate));
        if($export){
            return [$firstInterval->days, $firstInterval->days + $interval->days];
        }
        $itemStart = $firstInterval->days * 2;
        $size = ($interval->days + 1) * 2;
        if($calendarBegin >= date('Y-m-d', strtotime($calendarBegin)) . ' 12:00:00') {
            $size --;
            $itemStart ++;
        }
        if($calendarEnd <= date('Y-m-d', strtotime($calendarEnd)) . ' 12:00:00') {
            $size --;
        }
        return [$itemStart, $itemStart + $size - 1];
    }
    /**
     * 为日程一览表展示分层
     * 
     * @param type $items
     * 
     * @return array
     */
    private function parseScheduleViewTimelineLevel($items)
    {
        $result = [];
        foreach ($items as $item) {
            if (empty($result)) {
                $result[0][] = $item;
            } else {
                $findLeavel = false;
                foreach ($result as $level => $levelItems) {
                    $mixed = false;
                    foreach ($levelItems as $levelItem) {
                        if ($levelItem['start'] <= $item['end'] && $levelItem['end'] >= $item['start']) {
                            $mixed = true;
                            break;
                        }
                    }
                    if (!$mixed) {
                        $result[$level][] = $item;
                        $findLeavel = true;
                        break;
                    }
                }
                if (!$findLeavel) {
                    $findLeavel = count($result);
                    $result[$findLeavel][] = $item;
                }
            }
        }
        return $result;
    }
    /**
     * 按筛选ID获取对应的用户
     * 
     * @param type $filterId
     * @param type $own
     * 
     * @return array
     */
    private function getUserIdsByFilterId($filterId, $own)
    {
        $userIds = [];
        switch ($filterId){
            case 1:
                $users = app($this->userRepository)->getUserByDepartment($own['dept_id'], ['user.user_accounts' => ['', '!=']]);
                if(!$users->isEmpty()){
                    $userIds = array_column($users->toArray(), 'user_id');
                }
                break;
            case 2:
                $userIds = $this->getSubordinateUserIds($own['user_id'], false, false);
                break;
            case 3:
                $userIds = $this->getSubordinateUserIds($own['user_id'], true, false);
                break;
            case 4:
                $users = app($this->userService)->getSuperiorArrayByUserId($own['user_id'], ['all_superior' => true]);
                $userIds = $users['id'];
                break;
            case 5:
                $userIds = $this->getAttentionUserIds($own['user_id']);
                $groupUserIds = $this->getgroupUserIds($own);
                $userIds = array_unique(array_merge($userIds, $groupUserIds));
                break;
        }
        return $userIds;
    }
    public function getgroupUserIds($own)
    {
        $shareGroup = app($this->calendarSettingService)->getMyCalendarPurview([], $own);
        $groupUserIds = array_column($shareGroup, 'users');

        $result = array_reduce($groupUserIds, function ($result, $value) {
            return array_merge($result, array_values($value));
        }, array());

        return  array_unique(array_column($result, 'user_id'));
    }
    public function getGroupShareUserIds($groupId)
    {
        $shareGroup = app($this->calendarSettingService)->getCalendarPurviewDetail($groupId);
        
        return  $shareGroup['manager'] ?? [];
    }
    /**
     * 按关注分组获取日程列表
     * 
     * @param type $param
     * @param type $userId
     * 
     * @return array
     */
    public function getGroupAllCalendar($param, $userId)
    {
        $groupId = $param['group_id'] ?? 0;
        $users = app($this->calendarAttentionRepository)->getAllGroupUser($groupId, $userId);
        $userIds = array_column($users, 'attention_to_person');
        if (!$groupId) {
            array_push($userIds, $userId);
        }
        // 获取日程列表
        return app($this->calendarRepository)->getGroupCalendarList($userIds, $param);
    }
    /**
     * 获取即将开始的日程
     * 
     * @param type $interval
     * 
     * @return array 处理后的消息数组
     */
    public function calendarBeginRemind($interval) 
    {
        return $this->handleCalendarRemind($interval, function($start, $end) {
                    return app($this->calendarRepository)->listBeginCalendar($start, $end);
                }, function($userIds, $data) {
                    return [
                        'remindMark' => 'schedule-start',
                        'toUser' => $userIds,
                        'contentParam' => ['scheduleTime' => $data['calendar_begin'], 'scheduleContent' => $data['calendar_content']],
                        'stateParams' => ['calendar_id' => $data['calendar_id']]
                    ];
                });
    }
    /**
     * 获取即将结束的日程
     *
     * @param type $interval
     * 
     * @return array 处理后的消息数组
     */
    public function calendarEndRemind($interval) 
    {
        return $this->handleCalendarRemind($interval, function($start, $end) {
                    return app($this->calendarRepository)->listEndCalendar($start, $end);
                }, function($userIds, $data) {
                    return [
                        'remindMark' => 'schedule-end',
                        'toUser' => $userIds,
                        'contentParam' => ['scheduleTime' => $data['calendar_end'], 'scheduleContent' => $data['calendar_content']],
                        'stateParams' => ['calendar_id' => $data['calendar_id']]
                    ];
                });
    }
    /**
     * 处理日程提醒
     * 
     * @param type $interval
     * @param type $before
     * @param type $handle
     * 
     * @return array
     */
    private function handleCalendarRemind($interval, $before, $handle)
    {
        $start = date("Y-m-d H:i:s");
        $end = date("Y-m-d H:i:s", strtotime("+$interval minutes -1 seconds"));

        $list = $before($start, $end);

        $messages = [];
        foreach ($list as $item) {
            if (isset($item['allow_remind']) && $item['allow_remind'] == 1) {
                $handelusers = (isset($item['calendar_has_many_handle']) && !empty($item['calendar_has_many_handle'])) ? array_column($item['calendar_has_many_handle'], 'user_id') : [];
                $shareusers = (isset($item['calendar_has_many_share']) && !empty($item['calendar_has_many_share'])) ? array_column($item['calendar_has_many_share'], 'user_id') : [];
                $toUser = implode(',', array_unique(array_merge($handelusers, $shareusers)));
                $messages[] = $handle($toUser, $item);
            }
        }

        return $messages;
    }
    /**
     * 获取我的关注
     *
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    public function getMyAttention($userId, $params, $own) 
    {
        $params = $this->parseParams($params);
        $param = [
            'search' => [
                'attention_person' => [$userId],
                'attention_status' => [2]
            ],
            'getAll' => 1,
            'withDept' => 1
        ];
        $allMyAttendtionUsers = [];

        if (isset($params['search'])) {
            $param['search'] = array_merge($param['search'], $params['search']);
        } else {
            $allMyAttendtionUsers[] = ["user_id" => $own['user_id'], "user_name" => $own['user_name'], "attention_id" => 0, "dept_name" => $own['dept_name'], 'from' => '2'];
        }
        $myAttentions = app($this->calendarAttentionRepository)->calendarAttentionList($param);
        $shareGroupUser = [];
        $allShareGroupUser = [];
        if (isset($params['search'])) {
            // 获取我的关注id
            $attentionUserId = array_column($myAttentions, 'attention_to_person');
            // 查询所有组,和相似的用户id
            $groupUserId = $this->getgroupUserIds($own);
            $groupUserinsectId=array_intersect($groupUserId,$attentionUserId);
            $groupUserId=array_diff($groupUserId,$groupUserinsectId);
            $shareGroupUser = app($this->userRepository)->getUsersInfoByIds($groupUserId, $params['search']);
            
        }
        
        if (!empty($myAttentions)) {
            foreach ($myAttentions as $user) {
                $allMyAttendtionUsers[] = [
                    'attention_to_person' => $user['attention_to_person'],
                    'user_name' => $user['user_attention_to_person'] ? $user['user_attention_to_person']['user_name'] : '',
                    'dept_name' => !empty($user['user_attention_to_person']['user_to_dept']) ? $user['user_attention_to_person']['user_to_dept'][0]['dept_name'] : '',
                    'from' => '2',
                    'attention_id' => $user['attention_id'],
                    'is_read' => $user['is_read']
                ];
            }
        }
        if (!empty($shareGroupUser)) {
            foreach ($shareGroupUser as $user) {
                $allShareGroupUser[] = [
                    'attention_to_person' => $user['user_id'],
                    'user_name' => $user['user_name'] ?? '',
                    'dept_name' => isset($user['user_to_dept']) && !empty($user['user_to_dept']) ? $user['user_to_dept'][0]['dept_name'] : '',
                    'from' => '1',
                    // 'attention_id' => $user['attention_id'],
                    'is_read' => $user['is_read'] ?? 0
                ];
            }
        }
        return array_merge($allMyAttendtionUsers, $allShareGroupUser);
    }

    /**
     * 获取我的下属
     *
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    public function getMySubordinate($userId, $params) 
    {
        $params = $this->parseParams($params);
        
        $userParams = [
            'search' => [
                'superior_user_id' => [$userId],
            ],
            'withUserDept' => 1,
            'attention_person' => [$userId],
            'calendarSet' => '{"dimission": 0}'
        ];
        if (isset($params['search'])) {
            $userParams['search'] = array_merge($userParams['search'], $params['search']);
        }
        $userParams['search'] = json_encode($userParams['search']);
        // 判断是否穿透下级
        $attentionKey = app($this->calendarSettingService)->getBaseSetInfo('calendar_default_attention');
        if ($attentionKey) {
            // $allSubs = $this->getSubordinateUserIds($userId, true, false);
            return app($this->userService)->getAllUserCalendarSuperior($userId, $userParams);
            
        } else {
            $subUsers = app($this->roleService)->getUserSuperiorList($userParams);
        }
        
        if (empty($subUsers)) {
            return [];
        }
        
        return array_map(function($user) {
            return [
                'user_id' => $user['user_id'],
                'user_name' => !empty($user['subordinate_has_one_user']) ? $user['subordinate_has_one_user']['user_name'] : '',
                'dept_name' => !empty($user['subordinate_has_one_user']['user_to_dept']) ? $user['subordinate_has_one_user']['user_to_dept'][0]['dept_name'] : '',
                'from' => '1'
            ];
        }, $subUsers);
    }

    // 解析日程紧急程度
    private function parseCalendarLevel($calendarLevel) 
    {
        $levels = ['common', 'important', 'urge'];
        
        $level = $levels[intval($calendarLevel)] ?? 'common';
        
        return trans('calendar.' . $level);
    }
    
    
    public function saveFilter($userId, $params) {
        if (!$userId) {
            return ['code' => ['0x000006', 'common']];
        }
        app($this->calendarSaveFilterRepository)->deleteByWhere(['user_id' => [$userId]]);
        return app($this->calendarSaveFilterRepository)->insertData(['user_id' => $userId, 'save_filter' => json_encode($params)]);
    }

    public function getFilter($userId) {
        if (!$userId) {
            return ['code' => ['0x000006', 'common']];
        }
        $saveData = app($this->calendarSaveFilterRepository)->getData($userId);
        if ($saveData) {
            $saveFilter = json_decode($saveData->save_filter, true);
            $saveFilter['user_id'] = json_decode($saveFilter['user_id'] ?? '', true);
            $saveFilter['dept_id'] = json_decode($saveFilter['dept_id'] ?? '', true);
            $saveData->save_filter = $saveFilter;
        }
        return $saveData;
    }
    /**
     * /门户获取我的日程
     * @param  [type] $param 
     * @param  [type] $userId
     * @return [type] array       
     */
    public function getPortalCalendarList($param, $userId) 
    {
        $param = $this->parseParams($param);
        
        $calendarList = [];
        $calendarcount = app($this->calendarRepository)->getPortalCalendarCount($param, $userId);
        if ($calendarcount == 0) {
            return ['total' => 0, 'list' => []];
        }
        $calendarList = app($this->calendarRepository)->getPortalCalendarList($param, $userId)->toArray();
        if ($calendarList) {
            // 获取配置
            $calendarLevel = app($this->calendarSettingService)->getBaseSetInfo('calendar_level_key');
            $userIds = array_column($calendarList, 'creator');
            $userNameMap = $this->getUserNameMapById($userIds);
            foreach ($calendarList as $key => $value) {
                $calendarList[$key]['creator_name'] = $userNameMap[$value['creator']] ?? '';
                // $calendarList[$key]['type_name'] =  mulit_trans_dynamic('calendar_type.type_name.'. $value['type_name'] ?? '');;
                $calendarList[$key]['calendar_level_detail'] = $this->parseCalendarLevel($value['calendar_level']);
                if ($calendarLevel == 1) {
                    $calendarList[$key]['calendar_contents'] = strip_tags($value['calendar_content']);
                    $calendarList[$key]['calendar_level_set'] = $calendarLevel;
                    $calendarList[$key]['calendar_content'] = '[' .  $calendarList[$key]['calendar_level_detail'] . ']' . strip_tags($value['calendar_content']);
                } else {
                    $calendarList[$key]['calendar_content'] = strip_tags($value['calendar_content']);
                }
            }
        }
        
        return ['total' => $calendarcount, 'list' => $this->handleCalendarList($calendarList)];
    }
    /**
     * @获取我的日程用于选择器
     * 
     * @param type $param
     * @param type $userId
     * 
     * @return 列表 | array
     */
    public function getInitListSelector($param, $userId, $own) 
    {
        $param = $this->parseParams($param);
        $param['returntype'] = 'array';
        $calendarList = [];
        $userId = $this->getAllAccessUserIds($userId);
        $groupUserIds = $this->getgroupUserIds($own);
        $userId = array_unique(array_merge($userId, $groupUserIds));
        $calendarList = app($this->calendarRepository)->getInitListSelector($param, $userId)->toArray();
        $param['returntype'] = 'count';
        $calendarListTotal = app($this->calendarRepository)->getInitListSelector($param, $userId);
        if ($calendarList) {
            // 获取配置
            $calendarLevel = app($this->calendarSettingService)->getBaseSetInfo('calendar_level_key');
            $userIds = array_column($calendarList, 'creator');
            $userNameMap = $this->getUserNameMapById($userIds);
            foreach ($calendarList as $key => $value) {
                $calendarList[$key]['creator_name'] = $userNameMap[$value['creator']] ?? '';
                $calendarList[$key]['type_name'] =  mulit_trans_dynamic('calendar_type.type_name.'. $value['type_name'] ?? '');;
                $calendarList[$key]['calendar_level_detail'] = $this->parseCalendarLevel($value['calendar_level']);
                if ($calendarLevel == 1) {
                    $calendarList[$key]['calendar_contents'] = strip_tags($value['calendar_content']);
                    $calendarList[$key]['calendar_level_set'] = $calendarLevel;
                    $calendarList[$key]['calendar_content'] = '[' .  $calendarList[$key]['calendar_level_detail'] . ']' . strip_tags($value['calendar_content']);
                } else {
                    $calendarList[$key]['calendar_content'] = strip_tags($value['calendar_content']);
                }
            }
        }
        return ['total' => $calendarListTotal, 'list' => $this->handleCalendarList($calendarList)];
    }
    public function getConflictCalendar($param) {
        $param = $this->parseParams($param);
        $conflictId = isset($param['conflictId']) ? json_decode($param['conflictId'], true) : [];
        $conflictInfo = app($this->calendarRepository)->getConflictCalendarList($conflictId);
        $conflict = array_map(function($item) {
            return $item = [
                'calendar_id' => $item['calendar_id'],
                'calendar_content' => $item['calendar_content'],
                'calendar_begin' => $item['calendar_begin'],
                'calendar_end' => $item['calendar_end'],
                'handle_user' => get_user_simple_attr(array_column($item['calendar_has_many_handle'], 'user_id')),
            ]; 
        }, $conflictInfo);
        return ['total' => count($conflict), 'list' => $conflict];
    }
}
