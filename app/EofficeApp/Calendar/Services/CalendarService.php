<?php
namespace App\EofficeApp\Calendar\Services;

use Eoffice;
use App\Jobs\syncCalendarAttentionJob;
use Queue;
/**
 * @日程模块服务类
 */
class CalendarService extends CalendarBaseService 
{

    private $calendarRepository;
    private $calendarHandleUserRelationRepository;
    private $calendarShareUserRelationRepository;
    private $userService;
    private $attachmentService;
    private $calendarSettingService;
    private $calendarOuterRepository;
    /**
     * @注册日程相关的资源库对象
     * 
     * @param \App\EofficeApp\Repositories\CalendarRepository $calendarRepository
     */
    public function __construct() {
        parent::__construct();
        $this->calendarRepository = 'App\EofficeApp\Calendar\Repositories\CalendarRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->calendarSettingService = 'App\EofficeApp\Calendar\Services\CalendarSettingService';
        $this->calendarHandleUserRelationRepository = 'App\EofficeApp\Calendar\Repositories\CalendarHandleUserRelationRepository';
        $this->calendarShareUserRelationRepository = 'App\EofficeApp\Calendar\Repositories\CalendarShareUserRelationRepository';
        $this->calendarOuterRepository = 'App\EofficeApp\Calendar\Repositories\CalendarOuterRepository';
        $this->calendarAttentionRepository = 'App\EofficeApp\Calendar\Repositories\CalendarAttentionRepository';
    }
    
    /**
     * 日程外发
     * 
     * @param array $data
     * 
     * @return array
     */
    public function flowOutSendToCalendar($data)
    {
        if (!$this->validateRequired($data, 'calendar_content')) {
            return ['code' => ['0x053001', 'calendar']];
        }
        if (!$this->validateRequired($data, 'calendar_begin')) {
            return ['code' => ['0x053002', 'calendar']];
        }
        if (!$this->validateRequired($data, 'calendar_end')) {
            return ['code' => ['0x053003', 'calendar']];
        }
        if (!$this->validateRequired($data, 'handle_user')) {
            return ['code' => ['0x053004', 'calendar']];
        }
        if ($data['calendar_begin'] >= $data['calendar_end']) {
            return ['code' => ['0x053011', 'calendar']];
        }
        // 日程重复判断
        if (isset($data['repeat']) && $data['repeat']) {
            if (isset($data['repeat_end_type']) && $data['repeat_end_type'] == 1) {
                if (!isset($data['repeat_end_number']) || $data['repeat_end_number'] <= 0) {
                   return ['code' => ['0x053012', 'calendar']]; 
                }
            } else if (isset($data['repeat_end_type']) && $data['repeat_end_type'] == 2) {
                if (!isset($data['repeat_end_date']) || !$data['repeat_end_date']) {
                   return ['code' => ['0x053010', 'calendar']]; 
                }
            }
        }
        if (isset($data['allow_remind']) && $data['allow_remind']) {
            $data['remind_now'] = $this->defaultValue('remind_now', $data, 0);
            $data['start_remind'] = $this->defaultValue('start_remind', $data, 0);
            $data['end_remind'] = $this->defaultValue('end_remind', $data, 0);
            $hours = 0;
            $minutes = 0;
            $endhours = 0;
            $endminutes = 0;
            if (isset($data['start_remind_h']) && $data['start_remind_h'] > 0) {
                $hours = $data['start_remind_h'];
            } else {
                $hours = 0;
            }
            if (isset($data['start_remind_m']) && $data['start_remind_m'] > 0) {
                $minutes = $data['start_remind_m'];
            } else {
                $minutes = 0;
            }
            if (isset($data['end_remind_h']) && $data['end_remind_h'] > 0) {
                $endhours = $data['end_remind_h'];
            } else {
                $endhours = 0;
            }
            if (isset($data['end_remind_m']) && $data['end_remind_m'] > 0) {
                $endminutes = $data['end_remind_m'];
            } else {
                $endminutes = 0;
            }
            $data['start_remind_h'] = $hours;
            $data['start_remind_m'] = $minutes;
            $data['end_remind_h'] = $endhours;
            $data['end_remind_m'] = $endminutes;
        }
        $data['repeat_interval'] = $data['repeat_circle'] ?? 1;
        $currentUserId = $this->defaultValue('current_user_id', $data, 'admin');
        $data['handle_user'] = $this->defaultValue('handle_user', $data, '');
        $data['handle_user'] = $data['handle_user'] ? explode(',', rtrim($data['handle_user'], ',')) : [$currentUserId];
        $data['share_user'] = $this->defaultValue('share_user', $data, '');
        $data['share_user'] = $data['share_user'] ? explode(',', rtrim($data['share_user'], ',')) : [];
        $data['repeat_end_date'] = $this->defaultValue('repeat_end_date', $data, '0000-00-00 00:00:00');
        
        if (isset($data['calendar_type_id']) && !empty($data['calendar_type_id'])) {
            $data['calendar_type_id'] = $data['calendar_type_id'];
        } else {
            $result = app($this->calendarSettingService)->getDefaultCalendarType();
            $data['calendar_type_id'] = $result->type_id ?? 1;
        }
        if(isset($data['flow_id']) && isset($data['run_id']) && isset($data['flow_name'])) {
            $moduleConfig = app($this->calendarSettingService)->getModuleConfigByFrom('flow-submit');
            $relationData['module_id'] = $moduleConfig->module_id ?? 0;
            $relationData['source_id'] = $data['run_id'] ?? 0;
            $relationData['source_from'] = 'flow-end';
            $relationData['source_params'] = json_encode(['flow_id' => $data['flow_id'] ?? 0, 'run_id' => $data['run_id'] ?? 0]);
            $relationData['source_title'] =  $data['flow_name'] ?? $result['calendar_content'];
            $data['relationData'] = $relationData;
        }
        // 判断是否冲突
        $conflictCalendar = $this->checkCalendarConflict($data['calendar_begin'], $data['calendar_end'], $data['handle_user']);
        $conflictId = [];
        if ($conflictCalendar) {
            $conflictId = array_column($conflictCalendar, 'calendar_id');
        }
        // 检测日程冲突
        if ($conflictId) {
            return ['code' => ['0x053014', 'calendar']]; 
        }
        $result = $this->addCalendar($data, $currentUserId);
        if ($result && isset($result['code'])) {
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'calendar',
                    'field_to' => 'calendar_id',
                    'id_to' => $result['calendar_id'] ?? ''
                ]
            ]
        ];
    }
    /**
     * /日程外发更新
     * @param  [array] $data
     * @return [boolean]      
     */
    public function flowOutSendToUpdateCalendar($data) {
        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        $calendarUpdateData = $data['data'] ?? [];
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        $calendarId =$data['unique_id'] ?? '';
        $userId = $calendarUpdateData['current_user_id'] ?? '';
        unset($calendarUpdateData['current_user_id']);
        if (isset($calendarUpdateData['handle_user'])) {
            $calendarUpdateData['handle_user'] = explode(',', $calendarUpdateData['handle_user']);
        } else {
            $handleUserId = app($this->calendarHandleUserRelationRepository)->getListById($calendarId);
            $calendarUpdateData['handle_user'] = array_column($handleUserId, 'user_id');
        }
        if (isset($calendarUpdateData['share_user'])) {
            $calendarUpdateData['share_user'] = explode(',', $calendarUpdateData['share_user']);
        } else {
            $shareUserId = app($this->calendarShareUserRelationRepository)->getListById($calendarId);
            $calendarUpdateData['share_user'] = array_column($shareUserId, 'user_id');
        }
        $calendarData = $calendarUpdateData ?? [];
        // 获取详情, 是否重复, 重复走另外一个方法
        $detail = app($this->calendarRepository)->getCalendarDetail($calendarId, $userId, []);

        if (empty($detail)) {
            return ['code' => ['0x000006', 'common']];
        }
        $calendarDetail = $detail[0] ?? [];
        $calendarDetail['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'calendar', 'entity_id' => $calendarId]);
        $calendarBegin = isset($calendarData['calendar_begin']) ? $calendarData['calendar_begin'] : $calendarDetail['calendar_begin'];
        $calendarEnd = isset($calendarData['calendar_end']) ? $calendarData['calendar_end'] : $calendarDetail['calendar_end'];
        // 判断是否冲突
        $conflictCalendar = $this->checkCalendarConflict($calendarBegin, $calendarEnd, $calendarData['handle_user'], $calendarId);
        $conflictId = [];
        if ($conflictCalendar) {
            $conflictId = array_column($conflictCalendar, 'calendar_id');
        }
        // 检测日程冲突
        if ($conflictId) {
            return ['code' => ['0x053014', 'calendar']]; 
        }
        $calendarData = [
            'creator' => $calendarDetail['creator'],
            'calendar_type_id' => $this->defaultValue('calendar_type_id', $calendarData, $calendarDetail['calendar_type_id']),
            'calendar_level' => $this->defaultValue('calendar_level', $calendarData, $calendarDetail['calendar_level']),
            'calendar_content' => $this->defaultValue('calendar_content', $calendarData, $calendarDetail['calendar_content']),
            'calendar_begin' => $this->defaultValue('calendar_begin', $calendarData, $calendarDetail['calendar_begin']),
            'calendar_end' => $this->defaultValue('calendar_end', $calendarData, $calendarDetail['calendar_end']),
            'calendar_address' => $this->defaultValue('calendar_address', $calendarData, $calendarDetail['calendar_address']),
            'calendar_lat' => $this->defaultValue('calendar_lat', $calendarData, $calendarDetail['calendar_lat']),
            'calendar_long' => $this->defaultValue('calendar_long', $calendarData, $calendarDetail['calendar_long']),
            'repeat' => $this->defaultValue('calendar_long', $calendarData, $calendarDetail['calendar_long']),
            'repeat_type' => $this->defaultValue('repeat_type', $calendarData, $calendarDetail['repeat_type']),
            'repeat_interval' => $this->defaultValue('repeat_interval', $calendarData, $calendarDetail['repeat_interval']),
            'repeat_end_type' => $this->defaultValue('repeat_end_type', $calendarData, $calendarDetail['repeat_end_number']),
            'repeat_end_number' => $this->defaultValue('repeat_end_number', $calendarData, $calendarDetail['repeat_end_number']),
            'repeat_end_date' => $this->defaultValue('repeat_end_date', $calendarData, $calendarDetail['repeat_end_date']),
            'allow_remind' => $this->defaultValue('allow_remind', $calendarData, $calendarDetail['allow_remind']),
            'remind_now' => $this->defaultValue('remind_now', $calendarData, $calendarDetail['remind_now']),
            'start_remind' => $this->defaultValue('start_remind', $calendarData, $calendarDetail['start_remind']),
            'end_remind' => $this->defaultValue('end_remind', $calendarData, $calendarDetail['end_remind']),
            'remind_start_time' => $this->defaultValue('remind_start_time', $calendarData, $calendarDetail['remind_start_time']),
            'remind_start_datetime' => $this->defaultValue('remind_start_datetime', $calendarData, $calendarDetail['remind_start_datetime']),
            'remind_end_time' => $this->defaultValue('remind_end_time', $calendarData, $calendarDetail['remind_end_time']),
            'remind_end_datetime' => $this->defaultValue('remind_end_datetime', $calendarData, $calendarDetail['remind_end_datetime']),
            'calendar_remark' => $this->defaultValue('calendar_remark', $calendarData, $calendarDetail['calendar_remark']),
            'handle_user' => $calendarUpdateData['handle_user'],
            'share_user' => $calendarUpdateData['share_user'],
            'attachment_id' => $this->defaultValue('calendar_remark', $calendarData, $calendarDetail['attachment_id'])
        ];
        $relationData['module_id'] = $calendarDetail['module_id'] ?? 0;
        $relationData['source_id'] = $calendarDetail['source_id'] ?? 0;
        $relationData['source_from'] = $calendarDetail['source_from'] ?? '';
        $relationData['source_params'] = $calendarDetail['source_params'] ?? '';
        $relationData['source_title'] =  $calendarDetail['source_title'] ?? '';
        $calendarData['relationData'] = $relationData;
        if (isset($calendarDetail['repeat']) && $calendarDetail['repeat'] && $calendarDetail['calendar_parent_id'] == 0) {
            $return = $this->editAllCalendar($calendarId, $calendarData, $userId);
        } else if (isset($calendarUpdateData['repeat']) && $calendarUpdateData['repeat'] && $calendarDetail['calendar_parent_id'] != 0) {
            return ['code' => ['0x053013', 'calendar']];
        } else {
            $return = $this->editCalendar($calendarId, $calendarData, $userId);
        }
        if ($return && isset($return['calendar_id'])) {
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'calendar',
                        'field_to' => 'calendar_id',
                        'id_to' => $calendarId
                    ]
                ]
            ];
        } else {
            return $return;
        }
    }
    // 日程外发删除
    public function flowOutSendToDeleteCalendar($data) {
        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        $calendarUpdateData = $data['data'] ?? [];
        
        $userId = $calendarUpdateData['current_user_id'] ?? '';
        $calendarId = $data['unique_id'] ?? '';
        $param = ['calendar_id' => $calendarId];
        // 判断是否有重复
        $detail = app($this->calendarRepository)->getCalendarDetail($calendarId, $userId, []);
        $calendarDetail = $detail[0] ?? [];
        // 判断当前用户和创建人
        if ($userId != $calendarDetail['creator']) {
            return ['code' => ['0x053015', 'calendar']];
        }
        if (isset($calendarDetail['repeat']) && $calendarDetail['repeat'] && $calendarDetail['calendar_parent_id'] == 0) {
            $return =  $this->deleteAllRepeatCalendar($param);
        } else {
            $return =  $this->deleteCalendar($param, $userId);
         }
        if ($return && !isset($return['code'])) {
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'calendar',
                        'field_to' => 'calendar_id',
                        'id_to' => $calendarId
                    ]
                ]
            ];
        } else {
            return ['code' => ['0x000006', 'common']];
        }
    }
    /**
     * 日程外发
     * 
     * @param array $data 
     * @param array $relationData 关联数据
     * @param string $currentUserId 当前用户
     * @example 
     * $data = [
     *      'calendar_content' => '9点到10参加会议', 
     *      'handle_user' => ['admin', 'WV000001'], 
     *      'calendar_begin' => '2019-10-11 09:00:00',
     *      'calendar_end' => '2019-10-11 10:00:00', 
     * ];
     * $relationData = [
     *       'source_id' => $result->meeting_apply_id,
     *       'source_from' => 'add_meeting',
     *       'source_title' => $data['meeting_subject'],
     *       'source_params' => ['meeting_apply_id' => $result->meeting_apply_id]
     *   ];
     * @return boolean
     */
    public function emit(array $data, array $relationData, string $currentUserId)
    {
        if (empty($relationData)) {
            return 'Emit realtion_data empty'; 
        }
        if (empty($relationData['source_from'])) {
            return 'source_from empty'; 
        }
        $moduleConfig = app($this->calendarSettingService)->getModuleConfigByFrom($relationData['source_from']);
        if($moduleConfig->allow_auto_join == 0) {
            return 'Auto join not allowed';
        }
        $calendarTypeId = $moduleConfig->calendar_type_id;
        $moduleId = $moduleConfig->module_id;
        if(empty($data)) {
            return 'Emit data empty';
        }
        $requiredKeys = ['calendar_content', 'handle_user', 'calendar_begin', 'calendar_end'];
        
        foreach ($requiredKeys as $key) {
            if(!$this->validateRequired($data, $key)){
                return $key . ' is required from data';
            }
        }
        
        $relationRequiredKeys = ['source_id', 'source_from', 'source_title'];
        if ($relationData['source_from'] != 'performance-end') {
            foreach ($relationRequiredKeys as $key) {
                if(!$this->validateRequired($relationData, $key)){
                    return $key . ' is required from relation data';
                }
            }
        }
        $defaultValues = app($this->calendarSettingService)->getDefalutValueByTypeId($calendarTypeId);
        $level = $defaultValues->calendar_level ?? 0;
        $data['calendar_type_id'] = $calendarTypeId;
        $data['calendar_level'] = isset($data['calendar_level']) ? $data['calendar_level'] : $level;
        $data['allow_remind'] = 1;
        $data['remind_now'] = 1;
        $data['start_remind'] = 0;
        $data['end_remind'] = 0;
        $data['start_remind_h'] = 0;
        $data['start_remind_m'] = 0;
        $data['end_remind_h'] = 0;
        $data['end_remind_m'] = 0;
        if($defaultValues) {
            $data['calendar_level'] = isset($data['calendar_level']) ? $data['calendar_level'] : $defaultValues->calendar_level;
            $remindConfig = $defaultValues->remind_config;
            $data['allow_remind'] = $remindConfig->allow_remind ?? 1;
            $data['remind_now'] = $remindConfig->remind_now ?? 1;
            $data['start_remind'] = $remindConfig->start_remind->allow ?? 0;
            $data['end_remind'] = $remindConfig->end_remind->allow ?? 0;
            $data['start_remind_h'] = $remindConfig->start_remind->hours ?? 0;
            $data['start_remind_m'] = $remindConfig->start_remind->minutes ?? 0;
            $data['end_remind_h'] = $remindConfig->end_remind->hours ?? 0;
            $data['end_remind_m'] = $remindConfig->end_remind->minutes ?? 0;
        }
        $result = $this->addCalendar($data, $currentUserId);
        if($result && !isset($result['code'])) {
            $relationData['module_id'] = $moduleId;
            $relationData['calendar_id'] = $result['calendar_id'] ?? '';
            $relationData['source_params'] = json_encode($relationData['source_params']);
            app($this->calendarOuterRepository)->insertData($relationData);
        }
        return $result;
    }
    public function emitUpdate(array $data, array $relationData, string $currentUserId)
    {
        if (empty($relationData)) {
            return 'Emit realtion_data empty'; 
        }
        if (empty($relationData['source_from'])) {
            return 'source_from empty'; 
        }
        if (!isset($relationData['source_id']) || empty($relationData['source_id'])) {
            return 'source_id empty'; 
        }
        $moduleConfig = app($this->calendarSettingService)->getModuleConfigByFrom($relationData['source_from']);
        if($moduleConfig->allow_auto_join == 0) {
            return 'Auto join not allowed';
        }
        $calendarRealtion = app($this->calendarSettingService)->getOuterBySourceId($relationData['source_id'], $relationData['source_from']);
        $calendarId = $calendarRealtion['calendar_id'] ?? '';
        if (!$calendarId) {
            return 'calendar_id empty';
        }
        $calendarTypeId = $moduleConfig->calendar_type_id;
        $moduleId = $moduleConfig->module_id;
        if(empty($data)) {
            return 'Emit data empty';
        }
        $requiredKeys = ['calendar_content', 'handle_user', 'calendar_begin', 'calendar_end'];
        
        foreach ($requiredKeys as $key) {
            if(!$this->validateRequired($data, $key)){
                return $key . ' is required from data';
            }
        }
        
        $relationRequiredKeys = ['source_id', 'source_from', 'source_title'];
        if ($relationData['source_from'] != 'performance-end') {
            foreach ($relationRequiredKeys as $key) {
                if(!$this->validateRequired($relationData, $key)){
                    return $key . ' is required from relation data';
                }
            }
        }
        $defaultValues = app($this->calendarSettingService)->getDefalutValueByTypeId($calendarTypeId);
        $level = $defaultValues->calendar_level ?? 0;
        $data['calendar_type_id'] = $calendarTypeId;
        $data['calendar_level'] = isset($data['calendar_level']) ? $data['calendar_level'] : $level;
        $data['allow_remind'] = 1;
        $data['remind_now'] = 1;
        $data['start_remind'] = 0;
        $data['end_remind'] = 0;
        $data['start_remind_h'] = 0;
        $data['start_remind_m'] = 0;
        $data['end_remind_h'] = 0;
        $data['end_remind_m'] = 0;
        if($defaultValues) {
            $data['calendar_level'] = isset($data['calendar_level']) ? $data['calendar_level'] : $defaultValues->calendar_level;
            $remindConfig = $defaultValues->remind_config;
            $data['allow_remind'] = $remindConfig->allow_remind ?? 1;
            $data['remind_now'] = $remindConfig->remind_now ?? 1;
            $data['start_remind'] = $remindConfig->start_remind->allow ?? 0;
            $data['end_remind'] = $remindConfig->end_remind->allow ?? 0;
            $data['start_remind_h'] = $remindConfig->start_remind->hours ?? 0;
            $data['start_remind_m'] = $remindConfig->start_remind->minutes ?? 0;
            $data['end_remind_h'] = $remindConfig->end_remind->hours ?? 0;
            $data['end_remind_m'] = $remindConfig->end_remind->minutes ?? 0;
        }
        
        $result = $this->editAllCalendar($calendarId, $data, $currentUserId);

        if($result && !isset($result['code'])) {
            $relationData['module_id'] = $moduleId;
            $relationData['calendar_id'] = $calendarId;
            $relationData['source_params'] = json_encode($relationData['source_params']);
            app($this->calendarOuterRepository)->updateData($relationData, ['source_id' => $relationData['source_id'], 'calendar_id' => $calendarId]);
        }
        return $result;
    }
    public function emitComplete(array $relationData)
    {
        if (empty($relationData)) {
            return 'Emit realtion_data empty'; 
        }
        if (empty($relationData['source_from'])) {
            return 'source_from empty'; 
        }
        if (!isset($relationData['source_id']) || empty($relationData['source_id'])) {
            return 'source_id empty'; 
        }
       if ($relationData['source_from'] == 'meeting-join' || $relationData['source_from'] == 'meeting-video') {
            $calendarRealtion = app($this->calendarOuterRepository)->getOuterBySourceId($relationData['source_id'], $relationData['source_from']);
            $sourceIdArr = array_column($calendarRealtion, 'calendar_id');
            if ($sourceIdArr) {
                foreach ($sourceIdArr as $key => $value) {
                    $result = $this->multipleCompleteCalendar(['calendar_id' => $value]);
                }
            }
            return true;
        } else {
            $calendarRealtion = app($this->calendarSettingService)->getOuterBySourceId($relationData['source_id'], $relationData['source_from']);
            $calendarId = $calendarRealtion['calendar_id'] ?? '';
            if (!$calendarId) {
                return 'calendar_id empty';
            }
            return $result = $this->multipleCompleteCalendar(['calendar_id' => $calendarId]);
        }
    }
    public function emitDelete(array $relationData, string $currentUserId)
    {
        if (empty($relationData)) {
            return 'Emit realtion_data empty'; 
        }
        if (empty($relationData['source_from'])) {
            return 'source_from empty'; 
        }
        if (!isset($relationData['source_id']) || empty($relationData['source_id'])) {
            return 'source_id empty'; 
        }
        $sourceFrom = ['meeting-join','meeting-video','customer-payable'];
        if (in_array($relationData['source_from'],$sourceFrom)) {
            $calendarRealtion = app($this->calendarOuterRepository)->getOuterBySourceId($relationData['source_id'], $relationData['source_from']);
            $sourceIdArr = array_column($calendarRealtion, 'calendar_id');
            if ($sourceIdArr) {
                foreach ($sourceIdArr as $key => $value) {
                    $this->deleteCalendar(['calendar_id' => $value], $currentUserId);
                }
            }
            return true;
        } else {
            $calendarRealtion = app($this->calendarSettingService)->getOuterBySourceId($relationData['source_id'], $relationData['source_from']);
            $calendarId = $calendarRealtion['calendar_id'] ?? '';
            if (!$calendarId) {
                return 'calendar_id empty';
            }
            return $this->deleteCalendar(['calendar_id' => $calendarId], $currentUserId);
        }
        
        return true;
    }
    /**
     * @完成日程
     * 
     * @param type $param
     * 
     * @return 日程Id | array
     */
    public function multipleCompleteCalendar($param) 
    {
        $param = $this->parseParams($param);
        
        if (!isset($param['calendar_id']) || empty($param['calendar_id'])) {
            return ['code' => ['0x000003', 'common']];
        }
        $allCalendarHandler = app($this->calendarHandleUserRelationRepository)->getCalendarHanderUserRelationByIds([$param['calendar_id']]);
        $allHandlerUserIds = array_column($allCalendarHandler, 'user_id');
        if (app($this->calendarHandleUserRelationRepository)->multipleCompleteCalendar($param, $allHandlerUserIds, ['calendar_status' => 1])) {
            return true;
        }
        
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @新建日程
     * 
     * @param array $data
     * @param string $currentUserId
     * 
     * @return 日程Id | array
     */
    public function addCalendar($data, $currentUserId) 
    {
        return $this->handleCalendar($data, $currentUserId, function($calendarData, $relationData) {
            if (!$result = app($this->calendarRepository)->insertData($calendarData)) {
                return false;
            } 
            if (!empty($relationData)) {
                $relationData['calendar_id'] = $result->calendar_id;
                $relationData['source_title'] = $relationData['source_title'] ?? $result->calendar_content;
                app($this->calendarOuterRepository)->insertData($relationData);
            }
            return $result->calendar_id;
        });
    }
    /**
     * @编辑日程及所有重复日程
     * @param type $data
     * @return 日程Id | array
     */
    public function editAllCalendar($calendarId, $data, $currentUserId) {
        if (!$this->hasEditPermission($currentUserId, $calendarId)) {
            return ['code' => ['0x000006', 'common']];
        }
        return $this->handleCalendar($data, $currentUserId, function($calendarData, $relationData) use ($calendarId) {
            $calendarIds = app($this->calendarRepository)->getAllRepeatCalendarIdById($calendarId);
            $allCalendarId = array_column($calendarIds, 'calendar_id');
            //删除旧重复数据
            app($this->calendarRepository)->deleteAllRepeatByParentId($calendarId);
            app($this->calendarHandleUserRelationRepository)->deleteByWhere(['calendar_id' => [$calendarId, '=']]);
            app($this->calendarShareUserRelationRepository)->deleteByWhere(['calendar_id' => [$calendarId, '=']]);
            // 清除办理人和分享人表
            app($this->calendarHandleUserRelationRepository)->removeUserByCalendarId($allCalendarId);
            app($this->calendarShareUserRelationRepository)->removeUserByCalendarId($allCalendarId);
            app($this->calendarOuterRepository)->removeOuterByCalendarId($allCalendarId);
            //更新主日程
            if (!app($this->calendarRepository)->updateData($calendarData, ['calendar_id' => $calendarId])) {
                return false;
            }
            return $calendarId;
        }, $calendarId);
    }

    /**
     * @编辑日程
     * @param type $data
     * @return 日程Id | array
     */
    public function editCalendar($calendarId, $data, $currentUserId)
    {
        if (!$this->hasEditPermission($currentUserId, $calendarId)) {
            return ['code' => ['0x000006', 'common']];
        }
        return $this->handleCalendar($data, $currentUserId, function($calendarData, $relationData) use ($calendarId) {
            // 删除关联表数据
            app($this->calendarHandleUserRelationRepository)->deleteByWhere(['calendar_id' => [$calendarId, '=']]);
            app($this->calendarShareUserRelationRepository)->deleteByWhere(['calendar_id' => [$calendarId, '=']]);
            // app($this->calendarOuterRepository)->deleteByWhere(['calendar_id' => [$calendarId, '=']]);
            //更新主日程
            if (!app($this->calendarRepository)->updateData($calendarData, ['calendar_id' => $calendarId])) {
                return false;
            }
            return $calendarId;
        }, $calendarId);
    }
    /**
     * 日期范围转换为日期时间范围
     *
     * @param type $startDate
     * @param type $endDate
     * @return type
     */
    private function dateRangeToDatetimeRange($startDate, $endDate)
    {
        if ($this->isDate($startDate)) {
            $startDate = $this->combineDatetime($startDate, '00:00:00');
        }
        if ($this->isDate($endDate)) {
            $endDate = $this->combineDatetime($endDate, '23:59:59');
        }
        return [$this->format($startDate), $this->format($endDate)];
    }
    private function isDate($time)
    {
        return $this->format($time, 'Y-m-d') == $time;
    }
    /**
     * 格式化时间，默认格式：Y-m-d H:i:s
     * @param $time
     * @param string $format
     * @return false|string
     */
    protected function format($time, $format = 'Y-m-d H:i:s')
    {
        return date($format, strtotime($time));
    }
    public function combineDatetime($date, $time, $split = ' ')
    {
        return $date . $split . $time;
    }
        /**
     * 处理日程（新建或编辑的公共部分）
     * 
     * @param type $data
     * @param type $currentUserId
     * @param type $beforeCallback
     * @param type $calendarId
     * 
     * @return array
     */
    private function handleCalendar($data, $currentUserId, $beforeCallback, $calendarId = null)
    {
        $handleUser =  $this->defaultValue('handle_user', $data, []);
        if (empty($handleUser)) {
            return ['code' => ['0x053004', 'calendar']];
        }
        $allUserId = [];
        if($handleUser == 'all') {
            $allUserId = $handleUser = array_filter(explode(',', app($this->userService)->getAllUserIdString()));
        }
        $calendarBegin = $this->defaultValue('calendar_begin', $data, date('Y-m-d H:i:s'));
        $calendarEnd = $this->defaultValue('calendar_end', $data, date('Y-m-d H:i:s'));
        list($calendarBegin, $calendarEnd) = $this->dateRangeToDatetimeRange($calendarBegin, $calendarEnd);
        if ($calendarEnd != '0000-00-00') {
            list($calendarEndDate, $calendarEndTime) = explode(' ', $calendarEnd);
            $calendarEnd = $calendarEndTime === '23:59:00' ? $calendarEndDate . ' 23:59:59' : $calendarEnd;
        } else {
            $calendarEnd = '0000-00-00 00:00:00';
        }
        $handleUser =  $this->defaultValue('handle_user', $data, []);
        if (empty($handleUser)) {
            return ['code' => ['0x053004', 'calendar']];
        }
        $allUserId = [];
        if($handleUser == 'all') {
            $allUserId = $handleUser = array_filter(explode(',', app($this->userService)->getAllUserIdString()));
        }
        $conflictCalendar = $this->checkCalendarConflict($calendarBegin, $calendarEnd, $handleUser, $calendarId);
        $conflictId = [];
        if ($conflictCalendar) {
            $conflictId = array_column($conflictCalendar, 'calendar_id');
        }
        // 检测日程冲突
        if ($conflictId) {
            return ['conflict' => true, 'calendarList' => $conflictId];
        }
        $shareUser = $this->defaultValue('share_user', $data, []);
        if($shareUser == 'all') {
            $shareUser = !empty($allUserId) ? $allUserId : array_filter(explode(',', app($this->userService)->getAllUserIdString()));
        }
        // 处理未读, 把班里人在关注人的都设置成未读
        $attentionUser = array_unique(array_merge($handleUser, $shareUser));
        $this->SetAttentionUserUnread($calendarId, $attentionUser);
    
        $calendarRepeat = $this->defaultValue('repeat', $data, 0);
        list($remindStartTime, $remindStartDatetime) = $this->parseRemindDatetime($calendarBegin, (int)$this->defaultValue('start_remind_h', $data, 0), (int)$this->defaultValue('start_remind_m', $data, 0));
        list($remindEndTime, $remindEndDatetime) = $this->parseRemindDatetime($calendarEnd, (int)$this->defaultValue('end_remind_h', $data, 0), (int)$this->defaultValue('end_remind_m', $data, 0));
        $allowRemind = $this->defaultValue('allow_remind', $data, 0);
        $remindNow = $this->defaultValue('remind_now', $data, 0);
        $calendarContent = $this->defaultValue('calendar_content', $data, '');
        $attachmentId = $this->defaultValue('attachment_id', $data, []);
        $calendarData = [
            'creator' => $currentUserId,
            'calendar_type_id' => $this->defaultValue('calendar_type_id', $data, 1),
            'calendar_level' => $this->defaultValue('calendar_level', $data, 0),
            'calendar_content' => $calendarContent,
            'calendar_begin' => $calendarBegin,
            'calendar_end' => $calendarEnd,
            'calendar_address' => $this->defaultValue('calendar_address', $data, ''),
            'calendar_lat' => $this->defaultValue('calendar_lat', $data, ''),
            'calendar_long' => $this->defaultValue('calendar_long', $data, ''),
            'repeat' => $calendarRepeat,
            'repeat_type' => $this->defaultValue('repeat_type', $data, 1),
            'repeat_interval' => $this->defaultValue('repeat_interval', $data, 1),
            'repeat_end_type' => $this->defaultValue('repeat_end_type', $data, 0),
            'repeat_end_number' => $this->defaultValue('repeat_end_number', $data, 0),
            'repeat_end_date' => $this->defaultValue('repeat_end_date', $data, '0000-00-00 00:00:00'),
            'allow_remind' => $allowRemind,
            'remind_now' => $remindNow,
            'start_remind' => $this->defaultValue('start_remind', $data, 0),
            'end_remind' => $this->defaultValue('end_remind', $data, 0),
            'remind_start_time' => $remindStartTime,
            'remind_start_datetime' => $remindStartDatetime,
            'remind_end_time' => $remindEndTime,
            'remind_end_datetime' => $remindEndDatetime,
            'calendar_remark' => $this->defaultValue('calendar_remark', $data, '')
        ];
        $relationData = $this->defaultValue('relationData', $data, []);
        if(!$calendarId) {
            $calendarData['calendar_parent_id'] = 0;
        }
        if($calendarId) {
            unset($calendarData['creator']);
        }
        if($calendarId = $beforeCallback($calendarData, $relationData)) {
            $this->handleCalendarTerminal($calendarId, $attachmentId, $handleUser, $shareUser);
             // 判断是否开启立即提醒 
            if ($allowRemind && $remindNow) {
                // 日程新建提醒
                $sendData['remindMark'] = 'schedule-create';
                $sendData['toUser'] = $handleUser;
                $sendData['contentParam'] = ['scheduleTime' => $calendarBegin, 'scheduleContent' => strip_tags($calendarContent)];
                $sendData['stateParams'] = ['calendar_id' => $calendarId];
                Eoffice::sendMessage($sendData);
            }
            // 提醒关注人
            $this->SendAttentionMessage($handleUser, $calendarContent, $calendarId);
            // 插入重复日程
            if ($calendarRepeat) {
                $calendarData['creator'] = $currentUserId;
                $calendarData['calendar_parent_id'] = $calendarId;
                if (!$returnData = $this->insertCalendarRepeat($calendarData, $attachmentId, $handleUser, $shareUser, $relationData)) {
                    return ['code' => ['0x000003', 'common']];
                }
            }
            return ['calendar_id' => $calendarId];
        }
        return ['code' => ['0x000003', 'common']];
    }

    private function SendAttentionMessage($handleUser, $calendarContent, $calendarId) {
        $messageData = [
            'handle_user' => $handleUser,
            'calendar_content' => $calendarContent,
            'calendar_id' => $calendarId
        ];
        Queue::push(new syncCalendarAttentionJob($messageData));
    }

    public function sendMessageToAttention($param) {
        $userIds = $param['handle_user'] ?? [];
        $calendarData = $param['calendar_content'] ?? '';
        $calendarId = $param['calendar_id'] ?? '';
        if (!$calendarId) {
            return ['code' => ['0x000003', 'common']];
        }
        // 获取全部办理人的关注人
        $allAttentionUser = app($this->calendarAttentionRepository)->getAllAttentionUser($userIds);
        if ($allAttentionUser) {
            foreach ($allAttentionUser as $key => $value) {
                $sendData['toUser'] = $value['attention_person'];
                $sendData['contentParam'] = ['userName' => get_user_simple_attr($value['attention_to_person']), 'scheduleContent' => strip_tags($calendarData)];
                $sendData['remindMark'] = 'schedule-attention';
        
                $sendData['stateParams'] = ['calendar_id' => $calendarId];
                Eoffice::sendMessage($sendData);
            }
            
        }
        
    }

    private function SetAttentionUserUnread($calendarId, $userIds) {
        if ($calendarId) {
            //编辑吧之前的班里人和共享人设为已读
            $handleUser = app($this->calendarHandleUserRelationRepository)->getCalendarHanderUserRelationByIds([$calendarId]);
            $handleUserIds = array_column($handleUser, 'user_id');
            $shareUser = app($this->calendarShareUserRelationRepository)->getCalendarShareUserRelationByIds([$calendarId]);
            $shareUserIds = array_column($shareUser, 'user_id');
            $calendarBeforeUser = array_unique(array_merge($handleUserIds, $shareUserIds));
            // 设为已读
            app($this->calendarAttentionRepository)->SetAttentionUserUnread($calendarBeforeUser, ['is_read' => 0]);
        }
        

        return app($this->calendarAttentionRepository)->SetAttentionUserUnread($userIds, ['is_read' => 1]);
    }
    /**
     * 处理日程结束函数
     * 
     * @param type $calendarId
     * @param type $attachmentId
     * @param type $handleUser
     * @param type $shareUser
     * 
     * @return type
     */
    private function handleCalendarTerminal($calendarId, $attachmentId, $handleUser, $shareUser)
    {  
        // 保存附件
        app($this->attachmentService)->attachmentRelation("calendar", $calendarId, $attachmentId);
        
        // 保存办理人关系表
        $handleUserData = array_map(function($userId) use ($calendarId) {
            return [
                'calendar_id' => $calendarId,
                'user_id' => $userId,
                'calendar_status' => 0
            ];
        }, $handleUser);
        app($this->calendarHandleUserRelationRepository)->insertMultipleData($handleUserData);
        
        // 保存共享人关系表
        $shareUserData = array_map(function($userId) use ($calendarId) {
            return [
                'calendar_id' => $calendarId,
                'user_id' => $userId
            ];
        }, $shareUser);
        app($this->calendarShareUserRelationRepository)->insertMultipleData($shareUserData);
        
        return $calendarId;
    }
    /**
     * 解析提醒时间
     * 
     * @param string $consultDatetime
     * @param int $hours
     * @param int $minutes
     * 
     * @return array
     */
    private function parseRemindDatetime(string $consultDatetime, int $hours = 0, int $minutes = 0): array
    {
        if(!$consultDatetime) {
            $consultDatetime = date('Y-m-d H:i:s');
        }
        
        $remindTime = $hours * 60 + $minutes;
        $remindTime = $remindTime < 0 ? 0 : $remindTime;
        
        $remindDatetime = date('Y-m-d H:i:s', strtotime($consultDatetime) - $remindTime * 60);
        
        return [$remindTime, $remindDatetime];
    }
    /**
     * @插入重复日程
     * @param type $param
     * @return
     */
    private function insertCalendarRepeat($calendarData, $attachmentId, $handleUser, $shareUser, $relationData = []) 
    {
        $calendarData['repeat'] = 0;
        $repeatType = $calendarData['repeat_type'] ?? 0;
        $repeatInterval = $calendarData['repeat_interval'] ?? 1;
        $repeatEndType = $calendarData['repeat_end_type'] ?? 0;
        $sonCalendarDataArray = [];
        $repeatUnits = [ 0 => 'day', 1 => 'week', 2 => 'month' ];
        $unit = $repeatUnits[$repeatType] ?? 'day';
        if ($repeatEndType == 1) {
            $repeatEndNumber = $calendarData['repeat_end_number'];
            $parseData = $this->parseSonCalendarData($repeatInterval, $unit, $calendarData['calendar_begin'], $calendarData['calendar_end'], $calendarData['remind_start_datetime'], $calendarData['remind_end_datetime']);
            $key = 0;
            while ($key < $repeatEndNumber) {
                $this->combineSonCalendarData($sonCalendarDataArray, $key, $calendarData, $parseData, $handleUser);
                $parseData = $this->parseSonCalendarData($repeatInterval, $unit, $parseData[0], $parseData[1], $parseData[2], $parseData[3]);
                $key++;
            }
        } else {
            $calendarBeginEnd = $repeatEndType == 0 ? date('Y-m-d H:i:s', strtotime("+365 day", strtotime($calendarData['calendar_begin']))) : $calendarData['repeat_end_date'];
            $parseData = $this->parseSonCalendarData($repeatInterval, $unit, $calendarData['calendar_begin'], $calendarData['calendar_end'], $calendarData['remind_start_datetime'], $calendarData['remind_end_datetime']);
            $key = 0;
            while ($parseData[0] < $calendarBeginEnd) {
                $this->combineSonCalendarData($sonCalendarDataArray, $key, $calendarData, $parseData, $handleUser);
                $parseData = $this->parseSonCalendarData($repeatInterval, $unit, $parseData[0], $parseData[1], $parseData[2], $parseData[3]);
                $key++;
            }
        }
        
        $result = true;
        if (!empty($sonCalendarDataArray)) {
            foreach ($sonCalendarDataArray as $key => $data) {
                if (!$res = app($this->calendarRepository)->insertData($data)) {
                    $result = false;
                    break;
                } 
                if (!empty($relationData)) {
                    $relationData['calendar_id'] = $res->calendar_id;
                    $relationData['source_title'] = $relationData['source_title'] ?? $res->calendar_content;
                    app($this->calendarOuterRepository)->insertData($relationData);
                }
                $this->handleCalendarTerminal($res->calendar_id, $attachmentId, $handleUser, $shareUser);
            }
        }
        return $result;
    }
    /**
     * 组装子日程函数
     * 
     * @param type $sonCalendarDataArray
     * @param type $key
     * @param type $calendarData
     * @param type $parseData
     */
    private function combineSonCalendarData(&$sonCalendarDataArray, $key, $calendarData, $parseData, $handleUser)
    {
        // 先判断冲突
        $conflictResult = $this->checkCalendarConflict($parseData[0], $parseData[1], $handleUser);
        if (!$conflictResult) {
            $sonCalendarDataArray[$key] = $calendarData;
            $sonCalendarDataArray[$key]['calendar_begin'] = $parseData[0];
            $sonCalendarDataArray[$key]['remind_start_datetime'] = $parseData[2];
            $sonCalendarDataArray[$key]['calendar_end'] = $parseData[1];
            $sonCalendarDataArray[$key]['remind_end_datetime'] = $parseData[3];
        }
    }
    /**
     * 解析子日程开始结束时间和提醒时间函数
     * 
     * @param type $repeatInterval
     * @param type $unit
     * @param type $_calendarBegin
     * @param type $_calendarEnd
     * @param type $_remindStartDatetime
     * @param type $_remindEndDatetime
     * 
     * @return array
     */
    private function parseSonCalendarData($repeatInterval, $unit, $_calendarBegin, $_calendarEnd, $_remindStartDatetime, $_remindEndDatetime)
    {
        $calendarBegin = date('Y-m-d H:i:s', strtotime("+" . $repeatInterval . " $unit", strtotime($_calendarBegin)));
        $calendarEnd = date('Y-m-d H:i:s', strtotime("+" . $repeatInterval . " $unit", strtotime($_calendarEnd)));
        $remindStartDatetime = date('Y-m-d H:i:s', strtotime("+" . $repeatInterval . " $unit", strtotime($_remindStartDatetime)));
        $remindEndDatetime = date('Y-m-d H:i:s', strtotime("+" . $repeatInterval . " $unit", strtotime($_remindEndDatetime)));
        
        return [$calendarBegin, $calendarEnd, $remindStartDatetime, $remindEndDatetime];
    }
    /**
     * 判断是否有日程编辑权限
     * 
     * @param type $userId
     * @param type $calendarId
     * 
     * @return boolean
     */
    private function hasEditPermission($userId, $calendarId)
    {
        if (app($this->calendarRepository)->isCalendarCreator($userId, $calendarId)) {
            return true;
        }
        // if (app($this->calendarHandleUserRelationRepository)->isCalendarHandleUser($userId, $calendarId)) {
        //     return true;
        // }
        return false;
    }

    private function hasDeletePermission($userId, $calendarId)
    {
        if (app($this->calendarRepository)->isCalendarCreator($userId, $calendarId)) {
            return true;
        }
        return false;
    }
    /**
     * @删除日程
     * 
     * @param type $param
     * 
     * @return 日程Id | array
     */
    public function deleteCalendar($param, $userId) 
    {
        $param = $this->parseParams($param);
	// 判断有没有删除权限
        if (!$this->hasDeletePermission($userId, $param['calendar_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        $allHandlesUser = app($this->calendarHandleUserRelationRepository)->getCalendarHanderUserRelationByIds([$param['calendar_id']]);
        $handleUserIds = array_unique(array_column($allHandlesUser, 'user_id'));
        $shareUser = app($this->calendarShareUserRelationRepository)->getCalendarShareUserRelationByIds([$param['calendar_id']]);
        $shareUserIds = array_column($shareUser, 'user_id');
        $calendarBeforeUser = array_unique(array_merge($handleUserIds, $shareUserIds));
        if (app($this->calendarRepository)->deleteById($param['calendar_id'])) {
            // 清除办理人和分享人表
            app($this->calendarHandleUserRelationRepository)->deleteByWhere(['calendar_id' => [$param['calendar_id'], '='], 'user_id' => [$userId, '=']]);
            app($this->calendarShareUserRelationRepository)->deleteByWhere(['calendar_id' => [$param['calendar_id'], '='], 'user_id' => [$userId, '=']]);
            app($this->calendarOuterRepository)->deleteByWhere(['calendar_id' => [$param['calendar_id'], '=']]);
            // 把办理人已读去除
            app($this->calendarAttentionRepository)->updateData(['is_read' => 0], ['attention_to_person' => [$calendarBeforeUser, 'in']]);
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @完成日程
     * 
     * @param type $param
     * 
     * @return 日程Id | array
     */
    public function completeCalendar($param, $userId) 
    {
        $param = $this->parseParams($param);
        
        if (!isset($param['calendar_id']) || empty($param['calendar_id'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if (app($this->calendarHandleUserRelationRepository)->completeCalendar($param, $userId, ['calendar_status' => 1])) {
            // 把办理人已读去除
            app($this->calendarAttentionRepository)->updateData(['is_read' => 0], ['attention_to_person' => [$userId]]);
            return true;
        }
        
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * @删除日程及所有子日程
     * 
     * @param type $param
     * 
     * @return 日程Id | array
     */
    public function deleteAllRepeatCalendar($param) 
    {
        $param = $this->parseParams($param);
        // 获取主日程或者重复日程
        $calendarIds = app($this->calendarRepository)->getAllRepeatById($param['calendar_id']);
        $allCalendarId = array_column($calendarIds, 'calendar_id');

        $allHandlesUser = app($this->calendarHandleUserRelationRepository)->getCalendarHanderUserRelationByIds($allCalendarId);
        $handleUserIds = array_unique(array_column($allHandlesUser, 'user_id'));
        $shareUser = app($this->calendarShareUserRelationRepository)->getCalendarShareUserRelationByIds([$allCalendarId]);
        $shareUserIds = array_column($shareUser, 'user_id');
        $calendarBeforeUser = array_unique(array_merge($handleUserIds, $shareUserIds));
        if (app($this->calendarRepository)->deleteAllRepeatById($param['calendar_id'])) {
            // 清除办理人和分享人表
            app($this->calendarHandleUserRelationRepository)->removeUserByCalendarId($allCalendarId);
            app($this->calendarShareUserRelationRepository)->removeUserByCalendarId($allCalendarId);
            app($this->calendarOuterRepository)->deleteByWhere(['calendar_id' => [$param['calendar_id'], '=']]);

            // 把办理人已读去除
            app($this->calendarAttentionRepository)->updateData(['is_read' => 0], ['attention_to_person' => [$calendarBeforeUser, 'in']]);
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * @批量删除日程
     * 
     * @param type $param
     * 
     * @return 日程Id | array
     */
    public function deleteCalendars($param) 
    {
        $param = $this->parseParams($param);

        $calendarIdArray = explode(',', $param['calendar_id']);
        
        return array_walk($calendarIdArray, function($calendarId) {
            // 获取主日程或者重复日程
            $calendarIds = app($this->calendarRepository)->getAllRepeatById($calendarId);
            $allCalendarId = array_column($calendarIds, 'calendar_id');
            $this->setAllUserRead($allCalendarId);
            // 清除办理人和分享人表
            app($this->calendarHandleUserRelationRepository)->removeUserByCalendarId($allCalendarId);
            app($this->calendarShareUserRelationRepository)->removeUserByCalendarId($allCalendarId);
            app($this->calendarOuterRepository)->deleteByWhere(['calendar_id' => [$calendarId, '=']]);
            if(app($this->calendarRepository)->deleteById($calendarId)){
                return $calendarId;
            }
            return false;
        });
    }


    /**
     * @批量移除重复日程
     * 
     * @param type $param
     * 
     * @return 日程Id | array
     */
    public function removeRepeatCalendar($param) 
    {
        $calendarIdArray = explode(',', $param['data']);
        $this->setAllUserRead($calendarIdArray);
        $result = app($this->calendarRepository)->updateData(['repeat_remove' => 1], ['calendar_id' => [$calendarIdArray, 'in']]);
        //判断是否全部移除 删除整个重复日程
        $getRepeatList = app($this->calendarRepository)->getRepeatCalendarList($param["calendarParentId"], ['search' => ['repeat_remove' => [0, '=']]]);
        if (empty($getRepeatList)) {
            $allCalendarId = array_merge($getRepeatList, array($param["calendarParentId"]));
            $this->setAllUserRead($allCalendarId);
            app($this->calendarRepository)->deleteAllRepeatById($param["calendarParentId"]);
            
            // 清除办理人和分享人表
            app($this->calendarHandleUserRelationRepository)->removeUserByCalendarId($allCalendarId);
            app($this->calendarShareUserRelationRepository)->removeUserByCalendarId($allCalendarId);
        }
        return true;
    }

    private function setAllUserRead($allCalendarId) {
        
        $allHandlesUser = app($this->calendarHandleUserRelationRepository)->getCalendarHanderUserRelationByIds($allCalendarId);
        $handleUserIds = array_unique(array_column($allHandlesUser, 'user_id'));
        $shareUser = app($this->calendarShareUserRelationRepository)->getCalendarShareUserRelationByIds([$allCalendarId]);
        $shareUserIds = array_column($shareUser, 'user_id');
        $calendarBeforeUser = array_unique(array_merge($handleUserIds, $shareUserIds));
        return app($this->calendarAttentionRepository)->updateData(['is_read' => 0], ['attention_to_person' => [$calendarBeforeUser, 'in']]);
    }
    /**
     * 检测日程冲突
     * 
     * @param type $calendarBegin
     * @param type $calendarEnd
     * @param type $userIds
     * @param type $calendarId
     * 
     * @return boolean
     */
    private function checkCalendarConflict($calendarBegin, $calendarEnd, $userIds, $calendarId = null) 
    {
        $calendarSetUsed = app($this->calendarSettingService)->getBaseSetInfo('calendar_set_useed');
        
        if ($calendarSetUsed != 1) {
            // 获取当前时间段的所有日程
            $allCalendar = app($this->calendarRepository)->getCalendarsByDateScrope($calendarBegin, $calendarEnd);
            if($allCalendar->isEmpty()) {
                return false;
            }
            
            $calendarIds = array_column($allCalendar->toArray(), 'calendar_id');
            if($calendarId) {
                $calendarIds = array_diff($calendarIds, [$calendarId]);
            }
            return app($this->calendarHandleUserRelationRepository)->checkCalendarConflict($calendarIds, $userIds);
        }
        return false;
    }

    /**
     * @定时自动插入日程
     * 
     * @return [bool] [description]
     */
    public function insertCalendarRepeatNever()
    {
        $time = date("Y-m-d H:i:s");

        $list = app($this->calendarRepository)->listCalendarRepeatNever();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if (isset($value['repeat_type'])) {
                    switch ($value['repeat_type']) {
                        case 0:// 按天
                            $data = app($this->calendarRepository)->getRepeatMaxId($value['calendar_id']);
                            $newData = [];
                            if (!empty($data) && isset($data[0])) {
                                $newData = $data[0];
                                // 办理人共享人
                                $handleUser = $this->parseCalendarInfoArr($value['calendar_has_many_handle'] ?? []);
                                $shareUser = $this->parseCalendarInfoArr($value['calendar_has_many_share'] ?? []);
                                $attachmentId = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'calendar', 'entity_id' => $value['calendar_id']]);
                                $start = isset($newData['calendar_begin']) ? $newData['calendar_begin'] : '';
                                $end = isset($newData['calendar_end']) ? $newData['calendar_end'] : '';
                                $newStart = date('Y-m-d H:i:s', strtotime("+0 day", strtotime($start)));
                                $newEnd = date('Y-m-d H:i:s', strtotime("+0 day", strtotime($end)));
                                $value['calendar_begin'] = $newStart;
                                $value['calendar_end'] = $newEnd;
                                if ($end < $time) {
                                    $this->insertCalendarRepeat($value, $attachmentId, $handleUser, $shareUser);
                                }
                            }
                            break;
                        case 1:// 按周
                            $data = app($this->calendarRepository)->getRepeatMaxId($value['calendar_id']);
                            $newData = [];
                            if (!empty($data) && isset($data[0])) {
                                $newData = $data[0];
                                $handleUser = $this->parseCalendarInfoArr($value['calendar_has_many_handle'] ?? []);
                                $shareUser = $this->parseCalendarInfoArr($value['calendar_has_many_share'] ?? []);
                                $attachmentId = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'calendar', 'entity_id' => $value['calendar_id']]);
                                $start = isset($newData['calendar_begin']) ? $newData['calendar_begin'] : '';
                                $end = isset($newData['calendar_end']) ? $newData['calendar_end'] : '';
                                $newStart = date('Y-m-d H:i:s', strtotime("+0 week", strtotime($start)));
                                $newEnd = date('Y-m-d H:i:s', strtotime("+0 week", strtotime($end)));
                                $value['calendar_begin'] = $newStart;
                                $value['calendar_end'] = $newEnd;
                                
                                if ($end < $time) {
                                    $this->insertCalendarRepeat($value, $attachmentId, $handleUser, $shareUser);
                                }
                            }
                            break;
                        case 2:// 按月
                            $data = app($this->calendarRepository)->getRepeatMaxId($value['calendar_id']);
                            $newData = [];
                            if (!empty($data) && isset($data[0])) {
                                $newData = $data[0];
                                $handleUser = $this->parseCalendarInfoArr($value['calendar_has_many_handle'] ?? []);
                                $shareUser = $this->parseCalendarInfoArr($value['calendar_has_many_share'] ?? []);
                                $attachmentId = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'calendar', 'entity_id' => $value['calendar_id']]);
                                $start = isset($newData['calendar_begin']) ? $newData['calendar_begin'] : '';
                                $end = isset($newData['calendar_end']) ? $newData['calendar_end'] : '';
                                $newStart = date('Y-m-d H:i:s', strtotime("+0 month", strtotime($start)));
                                $newEnd = date('Y-m-d H:i:s', strtotime("+0 month", strtotime($end)));
                                $value['calendar_begin'] = $newStart;
                                $value['calendar_end'] = $newEnd;
                                if ($end < $time) {
                                    $this->insertCalendarRepeat($value, $attachmentId, $handleUser, $shareUser);
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        return true;
    }
    // 数组转为一位数组
    private function parseCalendarInfoArr($calendarInfo) {
        if (empty($calendarInfo)) {
            return [];
        }
        return $userIds = array_column($calendarInfo, 'user_id');

    }
}
