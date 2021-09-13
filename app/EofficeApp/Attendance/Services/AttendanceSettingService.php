<?php
namespace App\EofficeApp\Attendance\Services;

use Illuminate\Support\Facades\Lang;
use Cache;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\Attendance\Traits\AttendanceParamsTrait;
use App\EofficeApp\Attendance\Traits\AttendanceSettingTrait;
/**
 * 考勤设置Service
 *
 * @author  李志军
 *
 * @since  2017-06-26 创建
 */
class AttendanceSettingService extends AttendanceBaseService
{
    use AttendanceParamsTrait;
    use AttendanceSettingTrait;
    private $menus = [
        'attend_record' => 52,
        'out_record' => 53,
        'attend_track' => 68,
        'attend_statistics' => 247,
        'attend_calibrate' => 248,
        'attend_user_scheduling' => 253,
        'attend_log' => 254
    ];
    private $repositoryNamespace = 'App\EofficeApp\Attendance\Repositories\\';
    private $attendancePurviewDeptRepository;
    private $attendancePurviewGroupRepository;
    private $attendancePurviewOwnerRepository;
    private $attendancePurviewRoleRepository;
    private $attendancePurviewUserRepository;
    private $departmentDirectorRepository;
    private $userSystemInfoRepository;
    private $attachmentService;
    private $commonSettingKeysMap = [
        'base' => ['stat_unit_day', 'day_precision', 'stat_unit_hours', 'hours_precision', 'stat_leave_as_real_attend'],
        'no_sign_out' => ['allow_no_sign_out', 'hide_sign_out_btn', 'allow_auto_sign_out'],
        'calibration' => ['allow_calibration', 'limit_calibration_record_count', 'calibration_record_count', 'limit_calibration_count_pre_record', 'calibration_count_pre_record', 'limit_apply_calibration_time', 'apply_calibration_time'],
        'leave' => ['limit_leave_count', 'leave_count', 'limit_leave_time', 'leave_time'],
        'back_leave' => ['allow_back_leave', 'limit_back_leave_count', 'back_leave_count', 'limit_back_leave_time', 'back_leave_time'],
        'repair_sign' => ['allow_repair_sign', 'limit_repair_sign_count', 'repair_sign_count', 'limit_repair_sign_time', 'repair_sign_time'],
        'sign_remind' => ['open_calibration_remind', 'open_sign_remind', 'sign_in_remind_start_time', 'sign_in_remind_count', 'sign_in_remind_interval', 'sign_out_remind_start_time', 'sign_out_remind_count', 'sign_out_remind_interval', 'allow_user_no_sign_remind'],
        'workflow' => ['leave_flow_id', 'out_flow_id', 'overtime_flow_id', 'trip_flow_id', 'repair_flow_id', 'back_leave_flow_id']
    ];

    public function __construct()
    {
        parent::__construct();
        $this->attendancePurviewDeptRepository = $this->repositoryNamespace . 'AttendancePurviewDeptRepository';
        $this->attendancePurviewGroupRepository = $this->repositoryNamespace . 'AttendancePurviewGroupRepository';
        $this->attendancePurviewOwnerRepository = $this->repositoryNamespace . 'AttendancePurviewOwnerRepository';
        $this->attendancePurviewRoleRepository = $this->repositoryNamespace . 'AttendancePurviewRoleRepository';
        $this->attendancePurviewUserRepository = $this->repositoryNamespace . 'AttendancePurviewUserRepository';
        $this->departmentDirectorRepository = "App\EofficeApp\System\Department\Repositories\DepartmentDirectorRepository";
        $this->departmentService = "App\EofficeApp\System\Department\Services\DepartmentService";
        $this->departmentDirectorRepository = "App\EofficeApp\System\Department\Repositories\DepartmentDirectorRepository";
        $this->userSystemInfoRepository = "App\EofficeApp\User\Repositories\UserSystemInfoRepository";
        $this->attachmentService = "App\EofficeApp\Attachment\Services\AttachmentService";
    }

    /**
     * 新建班次
     * @param $data
     * @param $userId
     * @param bool $edit
     * @return array
     */
    public function insertShift($data, $userId, $edit = false)
    {
        //判断名称是否重复
        if (!$edit && app($this->attendanceShiftsRepository)->shiftNameIsRepeat($data['shift_name'])) {
            return ['code' => ['0x044007', 'attendance']];
        }

        $shiftData = [
            'shift_name' => $data['shift_name'],
            'shift_type' => sizeof($data['sign_time']) > 1 ? 2 : 1,
            'attend_time' => $this->getAttendTime($data),
            'middle_rest' => empty($this->defaultValue('middle_rest', $data, [])) ? 0 : 1,
            'shift_status' => $data['shift_status'] ?? 1,
            'is_default' => $this->defaultValue('is_default', $data, 0),
            'creator' => $userId,
            'modify_user' => $userId,
            'mark_color' => $data['mark_color'] ?? $this->randomColor(),
            'auto_sign' => $this->defaultValue('auto_sign', $data, 0),
            'sign_in_limit' => $this->defaultValue('sign_in_limit', $data, 0),
            'sign_in_begin_time' => $this->transTimeArrayToMinutes('sign_in_begin_time', $data),
            'sign_in_end_time' => $this->transTimeArrayToMinutes('sign_in_end_time', $data),
            'sign_out_limit' => $this->defaultValue('sign_out_limit', $data, 0),
            'sign_out_end_time' => $this->transTimeArrayToMinutes('sign_out_end_time', $data),
            'sign_out_now_limit' => $this->defaultValue('sign_out_now_limit', $data, 0),
            'sign_out_now_limit_time' => $this->transTimeArrayToMinutes('sign_out_now_limit_time', $data),
            'allow_late_to_late' => $this->defaultValue('allow_late_to_late', $data, 0),
            'late_to_late_time' => $this->transTimeArrayToMinutes('late_to_late_time', $data),
            'allow_early_to_early' => $this->defaultValue('allow_early_to_early', $data, 0),
            'early_to_early_time' => $this->transTimeArrayToMinutes('early_to_early_time', $data),
            'allow_late' => $this->defaultValue('allow_late', $data, 0),
            'allow_late_time' => $this->transTimeArrayToMinutes('allow_late_time', $data),
            'allow_leave_early' => $this->defaultValue('allow_leave_early', $data, 0),
            'leave_early_time' => $this->transTimeArrayToMinutes('leave_early_time', $data),
            'seriously_lag' => $this->defaultValue('seriously_lag', $data, 0),
            'seriously_lag_time' => $this->transTimeArrayToMinutes('seriously_lag_time', $data),
            'absenteeism_lag' => $this->defaultValue('absenteeism_lag', $data, 0),
            'absenteeism_lag_time' => $this->transTimeArrayToMinutes('absenteeism_lag_time', $data)
        ];
        if ($result = app($this->attendanceShiftsRepository)->insertData($shiftData)) {
            //新增对应班次的考勤时间
            if (!$this->addShiftTime($data, $result->shift_id)) {
                return ['code' => ['0x044002', 'attendance']];
            }
            //新增对应班次的休息时间
            if ($data['middle_rest'] == 1) {
                if (!$this->addShiftTime($data, $result->shift_id, 'rest_time')) {
                    return ['code' => ['0x044003', 'attendance']];
                }
            }
            return $result;
        }

        return ['code' => ['0x044001', 'attendance']];
    }
    
    private function transTimeArrayToMinutes($key, $data)
    {
        $times = $this->defaultValue($key, $data, null);
        
        if (!$times || !is_array($times)) {
            return 0;
        }
        return $times[0] * 60 + ($times[1] ?? 0);
    }
    private function transMinutesToTimeArray($minutes = null)
    {
        if ($minutes){
            $hours = floor($minutes / 60);
            $_minutes = $minutes % 60;
            return [$hours, $_minutes];
        }
        return null;
    }
    /**
     * 新增对应班次的考勤时间|休息时间
     *
     * @param array $data
     * @param int $shiftId
     * @param string $timeType
     *
     * @return boolean
     */
    private function addShiftTime($data, $shiftId, $timeType = 'sign_time')
    {
        if (!isset($data[$timeType])) {
            return false;
        }

        for ($i = 0; $i < sizeof($data[$timeType]); $i++) {
            $data[$timeType][$i]['shift_id'] = $shiftId;
        }

        $shiftTimeRepository = $timeType == 'sign_time' ? app($this->attendanceShiftsSignTimeRepository) : app($this->attendanceShiftsRestTimeRepository);

        return $shiftTimeRepository->insertMultipleData($data[$timeType]);
    }
    public function copyShift($data, $userId)
    {
        if (!isset($data['shift_id'])) {
            return false;
        }
        $shiftData = json_decode(json_encode($this->shiftDetail($data['shift_id'])), true);
        unset($shiftData['shift_id']);
        if (isset($data['shift_name']) && !empty($data['shift_name'])) {
            $shiftData['shift_name'] = $data['shift_name'];
        }
        if (isset($data['mark_color']) && !empty($data['mark_color'])) {
            $shiftData['mark_color'] = $data['mark_color'];
        }
        $shiftData['is_default'] = 0;
        return $this->insertShift($shiftData, $userId);
    }
    /**
     * 获取应出勤时间
     *
     * @param array $signInTime
     * @param array $signOutTime
     *
     * @return int
     */
    private function getAttendTime($data)
    {
        $attendTime = 0;
        //循环计算应出勤时间
        foreach ($data['sign_time'] as $value) {
            $attendTime += $this->timeDiff($value['sign_in_time'], $value['sign_out_time']);
        }
        //判断是否有中间休息
        if ($data['middle_rest'] == 1) {
            $restTime = 0;

            foreach ($data['rest_time'] as $value) {
                $restTime += $this->timeDiff($value['rest_begin'], $value['rest_end']);
            }

            $attendTime = $attendTime - $restTime; //出勤时间 - 休息时间
        }

        return $attendTime;
    }

    /**
     * 更新班次
     *
     * @param type $data
     * @param type $shiftId
     *
     * @return boolean
     */
    public function editShift($data, $userId, $shiftId)
    {
        //判断名称是否重复
        if (app($this->attendanceShiftsRepository)->shiftNameIsRepeat($data['shift_name'], $shiftId)) {
            return ['code' => ['0x044007', 'attendance']];
        }

        $shift = app($this->attendanceShiftsRepository)->getDetail($shiftId);
        $data['is_default'] = $shift->is_default;
        $result = $this->insertShift($data, $userId, true); //插入新的班次
        if (isset($result['code'])) {
            return ['code' => ['0x044004', 'attendance']];
        }
        app($this->attendanceShiftsRepository)->updateData(['shift_status' => 0, 'modify_user' => $userId], ['shift_id' => $shiftId]); //将旧班次更新为历史状态

        app($this->attendanceSchedulingDateRepository)->updateData(['shift_id' => $result->shift_id], ['shift_id' => [$shiftId], 'scheduling_date' => [date('Y-m-d'), '>']]); //将班次日期关系表里对应的旧班次更新为新班次

        return true;
    }
    /**
     * 设置用户排班（用户管理可用）
     *
     * @param type $schedulingId
     * @param type $userId
     * @return boolean
     */
    public function setUserScheduling($schedulingId, $userId)
    {
        $schedulingUserRepository  = app($this->attendanceSchedulingUserRepository);
        $schedulingModifyRecordRepository = app($this->attendanceSchedulingModifyRecordRepository);
        if ($oldSchedulingUser = $schedulingUserRepository->getSchedulingIdByUser($userId)) {
            // 更新用户班组，并设置新用户数,顺序不能错
            $schedulingUserRepository->updateData(['scheduling_id' => $schedulingId], ['user_id' => [$userId]]);
            // 更新排班变更记录
            $alreadyModifyUser = array_column($schedulingModifyRecordRepository->getCrurentDateModifyRecords(['user_id'])->toArray(), 'user_id');
            if (!in_array($userId, $alreadyModifyUser)) {
                $schedulingModifyData = $this->combineSchedulingModifyData($userId, $oldSchedulingUser->scheduling_id);
                $schedulingModifyRecordRepository->insertData($schedulingModifyData);
            }
        } else {
            // 更新用户班组，并设置新用户数
            $schedulingUserRepository->insertData(['scheduling_id' => $schedulingId, 'user_id' => $userId]);
            // 更新排班变更记录
            $schedulingModifyData = $this->combineSchedulingModifyData($userId, 0, $this->getPrvDate());
            $schedulingModifyRecordRepository->insertData($schedulingModifyData);
        }
        $this->getSchedulingUserCount($schedulingId, true);
        return true;
    }
   
    /**
     * 获取所有的班次数组，以id作为键值
     * @param string $field
     * @return array
     */
    public function getAllShiftMap($field = '')
    {
        //todo
        $shifts = app($this->attendanceShiftsRepository)->getAllShifts();

        $shiftsMap = [];

        if (count($shifts) > 0) {
            foreach ($shifts as $shift) {
                if ($field) {
                    $shiftsMap[$shift->shift_id] = $shift->$field;
                } else {
                    $shiftsMap[$shift->shift_id] = $shift;
                }
            }
        }

        return $shiftsMap;
    }
    /**
     * 获取班次列表
     * @param $param
     * @return array
     */
    public function shiftList($param)
    {
        $data = $this->response(app($this->attendanceShiftsRepository), 'getShiftTotal', 'getShiftList', $this->parseParams($param));

        if (!empty($data['list'])) {
            foreach ($data['list'] as $key => $value) {
                $data['list'][$key]['cut_shift_name'] = mb_substr($value->shift_name, 0, 1, 'utf-8');
                $data['list'][$key]['sign_time'] = app($this->attendanceShiftsSignTimeRepository)->getSignTime($value->shift_id);
                $data['list'][$key]['rest_time'] = $value->middle_rest == 1 ? app($this->attendanceShiftsRestTimeRepository)->getRestTime($value->shift_id) : [];
            }
        }

        return $data;
    }

    /**
     * 删除班次|班次一旦删除，排班里的班次则更新为默认排班
     * @param $shiftId
     * @param $userId
     * @return array|bool
     */
    public function deleteShift($shiftId, $userId)
    {
        if (!$defaultShift = app($this->attendanceShiftsRepository)->getOneShift(['shift_status' => [1], 'is_default' => [1]], ['shift_id'])) {
            return ['code' => ['0x044006', 'attendance']];
        }

        if (app($this->attendanceShiftsRepository)->updateData(['shift_status' => 0, 'modify_user' => $userId], ['shift_id' => [$shiftId]])) {

            app($this->attendanceSchedulingDateRepository)->updateData(['shift_id' => $defaultShift->shift_id], ['shift_id' => [$shiftId]]);

            return true;
        }

        return ['code' => ['0x044005', 'attendance']];
    }

    /**
     * 获取班次详情
     * @param $shiftId
     * @return mixed
     */
    public function shiftDetail($shiftId)
    {
        //todo
        $shift = app($this->attendanceShiftsRepository)->getDetail($shiftId);
        $items = [
            'sign_in_begin_time', 'sign_in_end_time', 'sign_out_end_time',
            'sign_out_now_limit_time', 'late_to_late_time', 'early_to_early_time',
            'allow_late_time', 'leave_early_time', 'seriously_lag_time', 'absenteeism_lag_time'
        ];
        foreach ($items as $item) {
            $shift->{$item} = $this->transMinutesToTimeArray($shift->{$item});
        }
        $shift->sign_time = app($this->attendanceShiftsSignTimeRepository)->getSignTime($shiftId, ['sign_in_time', 'sign_out_time']);
        $shift->rest_time = $shift->middle_rest == 1 ? app($this->attendanceShiftsRestTimeRepository)->getRestTime($shiftId, ['rest_begin', 'rest_end']) : [];
        return $shift;
    }

    /**
     * 新建班组
     * @param $data
     * @return array|bool
     */
    public function addScheduling($data)
    {
        $sechdulingData = $this->getSchedulingFormData($data);
        
        if ($result = app($this->attendanceSchedulingRepository)->insertData($sechdulingData)) {
            if (isset($data['user_id']) && !empty($data['user_id'])) {
                $this->updateSchedulingUser($data['user_id'], $result->scheduling_id);
            }
            $this->setSchedulingDate($data['schedulings'], $result->scheduling_id);
            return true;
        }
        return ['code' => ['0x044009', 'attendance']];
    }

    /**
     * 更新班组用户关系表
     *
     * @param type $insertData
     * @param type $schedulingId
     *
     * @return boolean
     */
    private function updateSchedulingUser($insertData, $schedulingId)
    {
        $oldUserId = array_column(app($this->attendanceSchedulingUserRepository)->getSchedulingUser(['scheduling_id' => [$schedulingId]], ['user_id'])->toArray(), 'user_id');

        $deleteUserId = array_diff($oldUserId, $insertData);

        $addUserId = array_diff($insertData, $oldUserId);

        $schedulingModifyRecordData = $this->getSchedulingModifyRecordData($deleteUserId, $schedulingId, $addUserId); //获取更新班组历史记录数据

        $deleteAllUserId = array_merge($deleteUserId, $addUserId);

        if (sizeof($deleteAllUserId) > 0) {
            //更新班组更新历史记录表
            app($this->attendanceSchedulingModifyRecordRepository)->insertMultipleData($schedulingModifyRecordData);

            app($this->attendanceSchedulingUserRepository)->deleteByWhere(['user_id' => [$deleteAllUserId, 'in']]); //删除当前用户对应的之前的班组关系
        }
        if (!empty($addUserId)) {
            $data = [];
            foreach ($addUserId as $value) {
                $data[] = [
                    'scheduling_id' => $schedulingId,
                    'user_id' => $value,
                ];
            }
            app($this->attendanceSchedulingUserRepository)->insertMultipleData($data); //插入班组关系数据
        }
        
        return $this->getSchedulingUserCount($schedulingId, true);
    }
    /**
     * 获取用户排班更新记录数据
     * 
     * @param type $deleteUserId
     * @param type $schedulingId
     * @param type $addUserId
     * @return array
     */
    private function getSchedulingModifyRecordData($deleteUserId, $schedulingId, $addUserId = [])
    {
        // 获取当天已经更新过的记录，只保留第一次更新那条
        $alreadyModifyUser = array_column(app($this->attendanceSchedulingModifyRecordRepository)->getCrurentDateModifyRecords(['user_id'])->toArray(), 'user_id');
        $insertData = [];
        foreach ($deleteUserId as $userId) {
            if (!in_array($userId, $alreadyModifyUser)) {
                $insertData[] = $this->combineSchedulingModifyData($userId, $schedulingId);
            }
        }
        if (sizeof($addUserId) > 0) {
            $modifyUser = app($this->attendanceSchedulingUserRepository)->getSchedulingUser(['user_id' => [$addUserId, 'in']], ['scheduling_id', 'user_id']);

            $allModifyUserId = []; //所有更新新用户ID 
            foreach ($modifyUser as $user) {
                if (!in_array($user->user_id, $alreadyModifyUser)) {
                    $insertData[] = $this->combineSchedulingModifyData($user->user_id, $user->scheduling_id);
                }
                $allModifyUserId[] = $user->user_id;
            }
            // 新添加的用户
            $newUserId = array_diff($addUserId, $allModifyUserId);
            foreach ($newUserId as $uId) {
                $insertData[] = $this->combineSchedulingModifyData($uId, 0, $this->getPrvDate());
            }
        }
        return $insertData;
    }
    /**
     * 组装数据
     * 
     * @param type $insertRecordData
     * @param type $userId
     * @param type $alreadyModifyUser
     * @param type $schedulingId
     */
    private function combineSchedulingModifyData($userId, $schedulingId = 0, $_date = null)
    {
        $date = $_date ? $_date : date('Y-m-d');
        
        return [
            'user_id' => $userId,
            'year' => date('Y', strtotime($date)),
            'month' => date('m', strtotime($date)),
            'day' => date('d', strtotime($date)),
            'modify_date' => date('Y-m-d', strtotime($date)),
            'scheduling_id' => $schedulingId
        ];
    }
    /**
     * 编辑班组
     * @param $data
     * @param $schedulingId
     * @return array|bool
     */
    public function editScheduling($data, $schedulingId)
    {
        $sechdulingData = $this->getSchedulingFormData($data);
       
        app($this->attendanceSchedulingRepository)->updateData($sechdulingData, ['scheduling_id' => $schedulingId]);
        if (isset($data['user_id']) && !empty($data['user_id'])) {
            $this->updateSchedulingUser($data['user_id'], $schedulingId);
        }
        $this->setSchedulingDate($data['schedulings'], $schedulingId);
        return true;
    }
    public function exportScheduling($builder, $param)
    {
        if(!isset($param['scheduling_id']) || !$param['scheduling_id']) {
            return false;
        }
        $schedulingId = $param['scheduling_id'];
        $schedulingInfo = app($this->attendanceSchedulingRepository)->getDetail($schedulingId);
        $schedulingDates = app($this->attendanceSchedulingDateRepository)->getSchedulingDateById($schedulingId)->toArray();
        $schedulingRests = app($this->attendanceSchedulingRestRepository)->getSchedulingRest(['scheduling_id' => [$schedulingId]])->toArray();
        $dateData = $restData = [];
        $shiftIds = [];
        if(!empty($schedulingDates)) {
            foreach ($schedulingDates as $item) {
                $shiftIds[$item['shift_id']] = true;
                $dateData[] = [$item['scheduling_date'], $item['shift_id']];
            }
        }
        if(!empty($schedulingRests)) {
            foreach ($schedulingRests as $item) {
                $restData[] = [$item['scheduling_date'], $item['rest_id']];
            }
        }
        $shifts = [];
        if(!empty($shiftIds)) {
            $shifts = app($this->attendanceShiftsRepository)->getShiftsById(array_keys($shiftIds));
            foreach ($shifts as $shift) {
                $shift->shift_times = $this->getShiftTimeById($shift->shift_id);
                if($shift->shift_type == 1) {
                    $shift->rest_times = $this->getRestTime($shift->shift_id, true);
                }
            }
        }
        if ($schedulingInfo->holiday_scheme_id) {
            $scheme = app($this->attendanceRestSchemeRepository)->getOneScheme(['scheme_id' => [$schedulingInfo->holiday_scheme_id]], true);
        } else {
            $scheme = app($this->attendanceRestSchemeRepository)->getOneScheme(['status' => ['1']], true);
        }   
        if(count($scheme->rest) > 0) {
            foreach ($scheme->rest as $key => &$rest) {
                $rest['lang_key'] = $rest->rest_name;
                $langKeys = 'attend_rest.rest_name.rest_name.' . $rest->rest_name;
                $rest->rest_name = mulit_trans_dynamic($langKeys);
            }
        }
        
        $exportData = [
            'version' => version(),
            'shifts' => $shifts,
            'holiday_scheme' => $scheme,
            'scheduling' => [
                'info' => $schedulingInfo,
                'shift_date_map' => $dateData,
                'rest_date_map' => $restData
            ]
        ];
        return $builder->setTitle($schedulingInfo['scheduling_name'])
                ->setData(json_encode($exportData))
                ->generate();
    }
    public function importHoliday($data) 
    {
        $fileContent = $this->getImportContent($data);
        if(!$fileContent || !is_array($fileContent)) {
            return false;
        }
        $version = $fileContent['version'] ?? null;
        if(!$version || $version > version()) {
            return ['code' => ['0x044096', 'attendance'], 'dynamic' => ['【'.$fileContent['scheme_name'].'】'.trans('attendance.0x044096')]];
        }
        $items = [];
        if(!empty($fileContent['rest'])) {
            foreach ($fileContent['rest'] as $item) {
                $items[] = [
                    'rest_name' => $item['rest_name'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                ];
            }
        }
        unset($fileContent['rest']);
        $fileContent['items'] = $items;
        $fileContent['status'] = 0;
        $fileContent['scheme_name'] = $fileContent['scheme_name'] . '-' . date('YmdHis');
        return $this->addScheme($fileContent);
    }
    private function getImportContent($data) 
    {
        $from = $data['from'] ?? '';
        if ($from == 'online') {
            return json_decode($data['content'], true);
        }
        $attachmentFile = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
        $fileContent = '';
        if (isset($attachmentFile['temp_src_file'])) {
            $fileContent = convert_to_utf8(file_get_contents($attachmentFile['temp_src_file']));
        }
        return json_decode($fileContent, true);
    }
    public function exportHoliday($param) 
    {
        if(!isset($param['scheme_id']) || !$param['scheme_id']) {
            return false;
        }
        $schemeId = $param['scheme_id'];
        
        $scheme = app($this->attendanceRestSchemeRepository)->getOneScheme(['scheme_id' => [$schemeId]], true);
          
        if(count($scheme->rest) > 0) {
            foreach ($scheme->rest as $key => &$rest) {
                $rest['lang_key'] = $rest->rest_name;
                $rest->rest_name = mulit_trans_dynamic('attend_rest.rest_name.rest_name.' . $rest->rest_name);
            }
        }
        $scheme->version = version();
        return [
            'export_title' => $scheme->scheme_name,
            'export_data' => json_encode($scheme)
        ];
    }
    public function importScheduling($data, $userId)
    {
        $fileContent = $this->getImportContent($data);
        if(!$fileContent || !is_array($fileContent)) {
            return false;
        }
        if(!isset($fileContent['shifts']) || !isset($fileContent['scheduling'])) {
            return false;
        }
        $version = $fileContent['version'] ?? null;
        $schedulingInfo = $fileContent['scheduling']['info'];
        if(!$version || $version > version()) {
            return ['code' => ['0x044097', 'attendance'], 'dynamic' => ['【'.$schedulingInfo['scheduling_name'].'】'.trans('attendance.0x044097')]];
        }
        $schedulingName = $schedulingInfo['scheduling_name'] . '-' . date('YmdHis');
        $schedulingInfo['scheduling_name'] = $schedulingName;
        $shifts = $fileContent['shifts'];
        $shiftIdMap = [];
        if(!empty($shifts)) {
            foreach ($shifts as $key => $shift) {
                $shift['sign_time'] = $shift['shift_times'];
                if($shift['shift_type'] == 1) {
                    $shift['rest_time'] = $shift['rest_times'];
                }
                $shift['is_default'] = 0;
                $shift['shift_name'] = $shift['shift_name'] . '-' . date('YmdHis');
                $shiftId = $shift['shift_id'];
                unset($shift['shift_id'],$shift['shift_times'],$shift['rest_times'], $shift['created_at'], $shift['updated_at']);
                $result = $this->insertShift($shift, $userId, true);
                if ($result && is_object($result)) {
                    $shiftIdMap[$shiftId] = $result->shift_id;
                }
            }
        }
        $restMap = [];
        if(isset($fileContent['holiday_scheme'])) {
            $holidaySchemeData = $fileContent['holiday_scheme'];
            $holidaySchemeData['scheme_name'] = $holidaySchemeData['scheme_name'] . '-' . date('YmdHis');
            $holidaySchemeData['status'] = 0;
            $scheme = app($this->attendanceRestSchemeRepository)->insertData($holidaySchemeData);
            if ($scheme) {
                $schemeId = $scheme->scheme_id;
                $schedulingInfo['holiday_scheme_id'] = $schemeId;
                $restData = $holidaySchemeData['rest'];
                if(!empty($restData)) {
                    $local = Lang::getLocale();
                    foreach ($restData as $key => $value) {
                        $restData[$key]['rest_name_lang'][$local] = $value['rest_name'];
                    }
                    $langServie = app($this->langService);
                    //新增的节假日记录
                    foreach ($restData as $rest) {
                        $restId = $rest['rest_id'];
                        $rest['scheme_id'] = $schemeId;
                        unset($rest['rest_id'], $rest['lang_key']);
                        $newRest = app($this->attendanceRestRepository)->insertData($rest);
                        if ($newRest) {
                            $newRestId = $newRest->rest_id;
                            $langKey = 'attend_rest_' . $newRestId;
                            $restMap[$restId] = $newRestId;
                            app($this->attendanceRestRepository)->updateData(['rest_name' => $langKey], ['rest_id' => [$newRestId]]);
                            $this->modifyRestNameLang($langServie, $rest['rest_name_lang'], $langKey);
                        }
                        
                    }
                }
            }
        }
        $shiftDataMap = $fileContent['scheduling']['shift_date_map'];
        $restDataMap = $fileContent['scheduling']['rest_date_map'];
        
        unset($schedulingInfo['scheduling_id'],$schedulingInfo['created_at'],$schedulingInfo['updated_at']);
        if ($schedulingResult = app($this->attendanceSchedulingRepository)->insertData($schedulingInfo)) {
            // 保存上班排班数据
            $this->saveImportantSchedulingSubData($shiftDataMap, $shiftIdMap, $schedulingResult->scheduling_id, $this->attendanceSchedulingDateRepository);
            // 保存休息排班数据
            $this->saveImportantSchedulingSubData($restDataMap, $restMap, $schedulingResult->scheduling_id, $this->attendanceSchedulingRestRepository, 'rest_id');
        }
        return true;
    }
    private function saveImportantSchedulingSubData($saveData, $map, $schedulingId, $repository, $idKey = 'shift_id')
    {
        if(!empty($saveData)) {
            $restData = $this->combineImportSchedulingData($saveData, $map, $schedulingId, function(&$data, $item) use ($idKey) {
                $data[$idKey] = $item;
            });
            app($repository)->insertMultipleData($restData);
        }
    }
    private function combineImportSchedulingData($data, $map, $schedulingId, $handle)
    {
        $combineData = [];
        foreach ($data as $item) {
            $date = $item[0];
            list($year, $month, $_) = explode('-', $date);
            $id = $map[$item[1]] ?? null;
            if ($id) {
                $combineData[] = $this->combineSchedulingDataItem($schedulingId, $date, $month, $year, $id, $handle);
            }
        }
        return $combineData;
    }
    public function copyScheduling($schedulingId) 
    {
        $schedulingInfo = app($this->attendanceSchedulingRepository)->getDetail($schedulingId)->toArray();
        $schedulingDates = app($this->attendanceSchedulingDateRepository)->getSchedulingDateById($schedulingId)->toArray();
        $rests = app($this->attendanceSchedulingRestRepository)->getSchedulingRest(['scheduling_id' => [$schedulingId]])->toArray();
        $schedulingInfo['scheme_id'] = $schedulingInfo['holiday_scheme_id'];
        $mainData = $this->getSchedulingFormData($schedulingInfo);
        $mainData['scheduling_name'] = $mainData['scheduling_name'] . '_copy';
        if ($result = app($this->attendanceSchedulingRepository)->insertData($mainData)) {
            $this->saveCopySchedulingSubData($schedulingDates, $result->scheduling_id, $this->attendanceSchedulingDateRepository); // 保存上班排班数据
            $this->saveCopySchedulingSubData($rests, $result->scheduling_id, $this->attendanceSchedulingRestRepository, 'rest_id'); // 保存休息排班数据
            return true;
        }
        return ['code' => ['0x044009', 'attendance']];
    }
    private function saveCopySchedulingSubData($saveData, $schedulingId, $repository, $idKey = 'shift_id')
    {
        if(!empty($saveData)) {
            $restData = $this->combineCopySchedulingData($saveData, $schedulingId, function(&$data, $item) use ($idKey) {
                $data[$idKey] = $item[$idKey];
            });
            app($repository)->insertMultipleData($restData);
        }
    }
    
    private function combineCopySchedulingData($data, $schedulingId, $handle)
    {
        $combineData = [];
        foreach ($data as $item) {
            $date = $item['scheduling_date'];
            list($year, $month, $_) = explode('-', $date);
            $combineData[] = $this->combineSchedulingDataItem($schedulingId, $date, $month, $year, $item, $handle);
        }
        return $combineData;
    }
    private function combineSchedulingDataItem($schedulingId, $date, $month, $year, $item, $handle)
    {
        $itemData = [
            'scheduling_id' => $schedulingId,
            'scheduling_date' => $date,
            'month' => $month,
            'year' => $year
        ];
        $handle($itemData, $item);
        return $itemData;
    }
    /**
     * 获取班组的表单信息
     * @param $data
     * @return array
     */
    private function getSchedulingFormData($data)
    {
        return [
            'scheduling_name' => $data['scheduling_name'],
            'status' => $this->defaultValue('status', $data, 1),
            'allow_sign_out_next_day' => $this->defaultValue('allow_sign_out_next_day', $data, 0),
            'allow_sign_holiday' => $this->defaultValue('allow_sign_holiday', $data, 0),
            'holiday_scheme_id' => $this->defaultValue('scheme_id', $data, 0)
        ];
    }

    /**
     * 获取班组列表
     * @param $param
     * @return array
     */
    public function schedulingList($param)
    {
        $param = $this->parseParams($param);
        $param['order_by'] = $param['order_by'] ?? ['scheduling_id' => 'desc'];
        $lists = $this->response(app($this->attendanceSchedulingRepository), 'getSchedulingTotal', 'getSchedulingList', $this->parseParams($param));
        if (isset($lists['total']) && $lists['total'] > 0) {
            $shiftId = [];
            $schemeId = [];
            $attendanceSchedulingDateRepository = app($this->attendanceSchedulingDateRepository);
            foreach ($lists['list'] as $key => &$item) {
                $shift = $attendanceSchedulingDateRepository->getAllShiftIdBySchedulingId($item->scheduling_id);
                if (!empty($shift)) {
                    $shiftId = array_merge($shiftId, array_column($shift, 'shift_id'));
                }
                if($item->holiday_scheme_id) {
                    $schemeId[] = $item->holiday_scheme_id;
                }
                $item['shift'] = $shift;
                $item['user_count'] = $this->getSchedulingUserCount($item->scheduling_id);
            }
            if (!empty($shiftId)) {
                $shifts = app($this->attendanceShiftsRepository)->getShiftsById(array_unique($shiftId), ['*'],true)->toArray();
                $shiftsMap = $this->arrayMapWithKeys($shifts, 'shift_id');
            }
            $scheme = app($this->attendanceRestSchemeRepository)->getOneScheme(['status' => ['1']], true)->toArray();
            if (!empty($schemeId)) {
                $schemes = app($this->attendanceRestSchemeRepository)->getSchemesByIds($schemeId)->toArray();
                $schemesMap = $this->arrayMapWithKeys($schemes, 'scheme_id');
            }
            foreach ($lists['list'] as $key => &$item) {
                if (isset($item['shift']) && !empty($item['shift'])) {
                    $shiftTemp = [];
                    foreach ($item['shift'] as $_key => $shiftItem) {
                        if (isset($shiftsMap[$shiftItem['shift_id']]) && !empty($shiftsMap[$shiftItem['shift_id']])) {
                            $shiftTemp[] = $shiftsMap[$shiftItem['shift_id']];
                        }
                    }
                    $item['shift'] = $shiftTemp;
                }
                $item['scheme'] = $item->holiday_scheme_id == 0 ? $scheme : ($schemesMap[$item->holiday_scheme_id] ?? null);
            }
        }
        return $lists;
    }
/**
     * 获取班组列表
     * @param $param
     * @return array
     */
    public function getAllScheduling()
    {
        return app($this->attendanceSchedulingRepository)->getAllSchedulings();
    }
    /**
     * 获取班组详细信息
     * @param $schedulingId
     * @return mixed
     */
    public function schedulingDetail($schedulingId)
    {
        $scheduling = app($this->attendanceSchedulingRepository)->getDetail($schedulingId);
        $users = app($this->attendanceSchedulingUserRepository)->getSchedulingUser(['scheduling_id' => [$schedulingId]], ['user_id'])->toArray();
        $scheduling->user_id = empty($users) ? [] : array_column($users, 'user_id');
        return $scheduling;
    }

    /**
     * 删除班组
     * @param $schedulingId
     * @return array|bool
     */
    public function deleteScheduling($schedulingId)
    {
        // 判断是否存在，不存在直接返回true
        if (!app($this->attendanceSchedulingRepository)->getDetail($schedulingId)) {
            return true;
        }
        //删除班组主表信息
        if (app($this->attendanceSchedulingRepository)->deleteByWhere(['scheduling_id' => [$schedulingId]])) {
            //更新班组变动记录
            $userId = array_column(app($this->attendanceSchedulingUserRepository)->getSchedulingUser(['scheduling_id' => [$schedulingId]], ['user_id'])->toArray(), 'user_id');
            app($this->attendanceSchedulingModifyRecordRepository)->insertMultipleData($this->getSchedulingModifyRecordData($userId, $schedulingId));
            //删除用户班组关系
            if (!$this->deleteSubScheduling(app($this->attendanceSchedulingUserRepository), $schedulingId)) {
                return ['code' => ['0x044014', 'attendance']];
            }
            //删除该班组的班次日期关系
            if (app($this->attendanceSchedulingDateRepository)->getTotal(['search' => ['scheduling_id' => [$schedulingId]]]) > 0) {
                app($this->attendanceSchedulingDateRepository)->deleteByWhere(['scheduling_id' => [$schedulingId], 'scheduling_date' => [date('Y-m-d'), '>']]);
            }
            return true;
        }

        return ['code' => ['0x044013', 'attendance']];
    }

    /**
     * 删除班组对应的子表
     * @param $repository
     * @param $schedulingId
     * @return bool
     */
    private function deleteSubScheduling($repository, $schedulingId)
    {
        if ($repository->getTotal(['search' => ['scheduling_id' => [$schedulingId]]]) > 0) {
            return $repository->deleteByWhere(['scheduling_id' => [$schedulingId]]);
        }
        return true;
    }

    /**
     * 快速编辑班组信息
     * @param $data
     * @param $schedulingId
     * @return bool
     */
    public function quickEditScheduling($data, $schedulingId)
    {
        $key = $data['data_key']; //需要编辑的对应的班组字段
        if ($key == 'user_id') {
            if($result = $this->updateSchedulingUser($data['data_value'], $schedulingId)) {
                return $result;
            }
            return false;
        }
        $scheduling = app($this->attendanceSchedulingRepository)->getDetail($schedulingId);
        if ($scheduling->{$key} == $data['data_value']) {
            return true;
        }
        
        return app($this->attendanceSchedulingRepository)->updateData([$key => $data['data_value']], ['scheduling_id' => $schedulingId]);
        
    }
    private function getSchedulingUserCount($schedulingId, $clear = false)
    {
        $prefix = 'eoffice:schedule_user_count:';
        if ($clear) {
            $keys = Redis::keys(config('cache.prefix') . ':' . $prefix . '*');
            if (count($keys) > 0) {
                Redis::del($keys);
            }
        }
        return Cache::rememberForever($prefix . $schedulingId, function () use($schedulingId) {
                    return app($this->attendanceSchedulingUserRepository)->getUserCountBySchedulingId($schedulingId);
                });
    }
    /**
     * 移动考勤基础设置
     * @param $data
     * @return bool
     */
    public function setMobileBase($data)
    {
        $this->setSystemParam('attendance_mobile_useed', $this->defaultValue('attendance_mobile_useed', $data, 0));
        $this->setSystemParam('attend_mobile_type_location', $this->defaultValue('attend_mobile_type_location', $data, 0));
        $this->setSystemParam('attend_mobile_type_wifi', $this->defaultValue('attend_mobile_type_wifi', $data, 0));
        $this->setSystemParam('attendance_points_adjust_useed', $this->defaultValue('attendance_points_adjust_useed', $data, 0));
        return true;
    }

    /**
     * 获取移动考勤基础设置信息
     * @return array
     */
    public function getMobileBase()
    {
        return [
            'attendance_mobile_useed' => $this->getSystemParam('attendance_mobile_useed', 0),
            'attend_mobile_type_location' => $this->getSystemParam('attend_mobile_type_location', 0),
            'attend_mobile_type_wifi' => $this->getSystemParam('attend_mobile_type_wifi', 0),
            'attendance_points_adjust_useed' => $this->getSystemParam('attendance_points_adjust_useed', 0)
        ];
    }

    /**
     * 新建考勤点
     * @param $data
     * @return array
     */
    public function addMobilePoints($data)
    {
        if (app($this->attendancePointsRepository)->pointNameIsExists($data['point_name'])) {
            return ['code' => ['0x044021', 'attendance']];
        }
        
        $this->combineMobilePointData($data);
        
        return app($this->attendancePointsRepository)->insertData($data);
    }

    /**
     * 编辑考勤点
     * @param $data
     * @param $id
     * @return array
     */
    public function editMobilePoints($data, $id)
    {
        if (!app($this->attendancePointsRepository)->pointIsExists($id)) {
            return ['code' => ['0x044022', 'attendance']];
        }
        if (app($this->attendancePointsRepository)->pointNameIsExists($data['point_name'], $id)) {
            return ['code' => ['0x044021', 'attendance']];
        }
        
        $this->combineMobilePointData($data);
        
        return app($this->attendancePointsRepository)->updateData($data, ['id' => $id]);
    }
    private function combineMobilePointData(&$data)
    {
        $data['all_member'] = $this->defaultValue('all_member', $data, 0);
        $data['dept_id'] = $this->arrayToStr($this->defaultValue('dept_id', $data, []));
        $data['role_id'] = $this->arrayToStr($this->defaultValue('role_id', $data, []));
        $data['user_id'] = $this->arrayToStr($this->defaultValue('user_id', $data, []));
    }
    /**
     * 删除考勤点
     * @param $id
     * @return array
     */
    public function deleteMobilePoints($id)
    {
        if (!app($this->attendancePointsRepository)->pointIsExists($id)) {
            return ['code' => ['0x044022', 'attendance']];
        }
        return app($this->attendancePointsRepository)->deleteById($id);
    }

    /**
     * 获取考勤点详情
     * @param $id
     * @return array
     */
    public function mobilePointsDetail($id)
    {
        if (!app($this->attendancePointsRepository)->pointIsExists($id)) {
            return ['code' => ['0x044022', 'attendance']];
        }
        $point = app($this->attendancePointsRepository)->getDetail($id);
        $point->dept_id = explode(',', $point->dept_id);
        $point->user_id = explode(',', $point->user_id);
        $point->role_id = explode(',', $point->role_id);
        return $point;
    }

    /**
     * 获取考勤点列表
     * @param $param
     * @return array
     */
    public function mobilePointsList($param)
    {
        return $this->response(app($this->attendancePointsRepository), 'getPointsTotal', 'getPointsList', $this->parseParams($param));
    }

    /**
     * 设置非定点考勤
     * @param $data
     * @return mixed
     */
    public function setMobileNoPoint($data)
    {
        return $this->setCommonPurviewGroup($data, $data['type'] ?? 1);
    }
    /**
     * 获取非定点考勤信息
     * @return mixed
     */
    public function getMobileNoPoint()
    {
        return [
            'sign_in' => $this->getCommonPurviewGroup(1),
            'sign_out' => $this->getCommonPurviewGroup(2)
        ];
    }
    /**
     * 设置通用权限组 
     * @param array $data 
     * @param int $type 1 外勤签到成员，2外勤签退，3允许漏签成员，4无需打卡提醒成员，5允许自动签退成员
     * 
     * @return array
     */
    private function setCommonPurviewGroup($data, $type = 1)
    {
        $saveData = [
            'all_member' => $this->defaultValue('all_member', $data, 0),
            'dept_id' => $this->arrayToStr($this->defaultValue('dept_id', $data, [])),
            'role_id' => $this->arrayToStr($this->defaultValue('role_id', $data, [])),
            'user_id' => $this->arrayToStr($this->defaultValue('user_id', $data, [])),
            'type' => $type
        ];
        ecache('Attendance:CommonPurviewGroup')->clear($type);
        $commonPurviewGroupRepository = app($this->attendanceCommonPurviewGroupRepository);
        if ($commonPurviewGroupRepository->purviewGroupExists($type)) {
            return $commonPurviewGroupRepository->updatePurviewGroup($saveData, $type);
        }
        return $commonPurviewGroupRepository->insertData($saveData);
    }
    
    private function getCommonPurviewGroup($type)
    {
        return ecache('Attendance:CommonPurviewGroup')->get($type);
    }
    
    public function getOutAttendancePriv($own)
    {
        return [
            'sign_in' => $this->inMembers($this->getCommonPurviewGroup(1), $own['user_id'], $own['dept_id'], $own['role_id']),
            'sign_out' => $this->inMembers($this->getCommonPurviewGroup(2), $own['user_id'], $own['dept_id'], $own['role_id'])
        ];
    }
    
    private function inMembers($members, $userId, $deptId, $roleIds)
    {
        if (!$members) {
            return false;
        }
        if($members['all_member']) {
            return true;
        }
        if($members['user_id'] && in_array($userId, $members['user_id'])) {
            return true;
        }
        if($members['dept_id'] && in_array($deptId, $members['dept_id'])) {
            return true;
        }
        if($members['role_id'] && !empty(array_intersect($roleIds, $members['role_id']))) {
            return true;
        }
        return false;
    }
    /**
     * 设置pc端打卡
     * @param $data
     */
    public function setPcSign($data)
    {
        $this->setSystemParam('attendance_web_useed', $this->defaultValue('used', $data, 0));
        unset($data['used']);

        $data['all_member'] = $this->defaultValue('all_member', $data, 0);
        $data['dept_id'] = $this->arrayToStr($this->defaultValue('dept_id', $data, []));
        $data['role_id'] = $this->arrayToStr($this->defaultValue('role_id', $data, []));
        $data['user_id'] = $this->arrayToStr($this->defaultValue('user_id', $data, []));

        if (app($this->attendancePcSignRepository)->dataIsExists()) {
            return app($this->attendancePcSignRepository)->updateScope($data);
        }

        return app($this->attendancePcSignRepository)->insertData($data);
    }

    /**
     * 获取pc端打卡配置
     */
    public function getPcSign()
    {
        $data = app($this->attendancePcSignRepository)->getOneScopeInfo();
        if ($data) {
            $data->dept_id = explode(',', $data->dept_id);
            $data->user_id = explode(',', $data->user_id);
            $data->role_id = explode(',', $data->role_id);
        }
        $data->used = $this->getSystemParam('attendance_web_useed');
        return $data;
    }
    private function getOneSchedulingShiftsMap($schedulingId)
    {
        $shiftRel = app($this->attendanceSchedulingDateRepository)->getAllShiftIdBySchedulingId($schedulingId);

        if (!empty($shiftRel)) {
            $shifts = app($this->attendanceShiftsRepository)->getShiftsById(array_column($shiftRel, 'shift_id'))->toArray();
            
            return $this->arrayMapWithKeys($shifts, 'shift_id');
        }
        
        return [];
    }
    /**
     * 获取排班日期
     * @param $schedulingId
     * @param $year
     * @return array
     */
    public function getSchedulingDate($schedulingId, $year)
    {
        $scheduling = $this->schedulingDetail($schedulingId);
        //获取当前排班的班次信息
        $shiftsMap = $this->getOneSchedulingShiftsMap($schedulingId);
        //获取日期班次关联数据
        $shiftDates = app($this->attendanceSchedulingDateRepository)->getSchedulingDateBySchedulingId($schedulingId, $year);
        
        $schedulingArray = [];
        if (count($shiftDates) > 0) {
            foreach ($shiftDates as $dateItem) {
                $shiftId = $dateItem->shift_id;
                $shift = $shiftsMap[$shiftId] ?? null;
                if($shift) {
                    $schedulingArray[$dateItem->scheduling_date] = [
                        'type' => 'shift',
                        'shift_id' => $shiftId,
                        'shift_name' => $shift['shift_name'],
                        'mark_color' => $shift['mark_color']
                    ];
                }
            }
        }
        if(!$scheduling->holiday_scheme_id) {
            $scheme = app($this->attendanceRestSchemeRepository)->getOneScheme(['status' => ['1']], true);
        } else {
            $scheme = app($this->attendanceRestSchemeRepository)->getOneSchemeById($scheduling->holiday_scheme_id);
        }
        //获取所有假期日期信息
        $restDates = app($this->attendanceSchedulingRestRepository)->getSchedulingRestDate($schedulingId, $year);
        if (count($restDates) > 0) {
            $restId = array_column($restDates->toArray(), 'rest_id');
            $rests = app($this->attendanceRestRepository)->getRestsByRestIds(array_unique($restId));
            $restMap = [];
            if($rests && count($rests) > 0 ) {   
                foreach ($rests as $key => $rest) {
                    $langKeys = 'attend_rest.rest_name.rest_name.' . $rest->rest_name;
                    $restMap[$rest->rest_id] = mulit_trans_dynamic($langKeys);
                }
            }
            
            foreach ($restDates as $dateItem) {
                $schedulingArray[$dateItem->scheduling_date] = [
                    'type' => 'rest',
                    'rest_id' => $dateItem->rest_id,
                    'rest_name' => $restMap[$dateItem->rest_id] ?? '已废弃'
                ];
            }
        }
        $scheduling->schedulings = $schedulingArray;
        $scheduling->scheme = $scheme;
        return $scheduling;
    }

    /**
     * 设置排班
     * @param $data
     * @param $year
     * @param $schedulingId
     * @return bool
     */
    public function setSchedulingDate($data, $schedulingId)
    {
        $deleteDate =  [];

        $updateRest = $this->combineSchedulingData($data['rests'],$deleteDate, function($date, $year, $month, $id) use($schedulingId) {
            if ($id != 0) {
                return [
                    'scheduling_id' => $schedulingId,
                    'scheduling_date' => $date,
                    'rest_id' => $id,
                    'month' => $month,
                    'year' => $year
                ];
            }
            return false;
        });
        $updateScheduling = $this->combineSchedulingData($data['shifts'],$deleteDate, function($date, $year, $month, $id) use($schedulingId) {
            return [
                'scheduling_id' => $schedulingId,
                'scheduling_date' => $date,
                'shift_id' => $id,
                'month' => $month,
                'year' => $year
            ];
        });
        $deleteWheres = ['scheduling_id' => [$schedulingId], 'scheduling_date' => [$deleteDate, 'in']];
        $result1 = $this->batchDeleteThenInsertData(app($this->attendanceSchedulingDateRepository),$deleteWheres, $updateScheduling);
        $result2 = $this->batchDeleteThenInsertData(app($this->attendanceSchedulingRestRepository),$deleteWheres, $updateRest);
        return $result1 && $result2;
    }
    private function combineSchedulingData($data, &$deleteDate, $callback) 
    {
        $handleData = [];
        foreach ($data as $item) {
            list ($date, $id) = $item;
            if ($date <= $this->currentDate) {
                //当前天和历史排班都不可以修改。
                continue;
            }
            $deleteDate[] = $date;
            list($year, $month, $_) = explode('-', $date);
            $handleItem = $callback($date, $year, $month, $id);
            if($handleItem) {
                $handleData[] = $handleItem;
            }
        }
        return $handleData;
    }
    /**
     * 批量删除数据后批量插入数据
     * @param type $repository
     * @param type $deleteWheres
     * @param type $insertData
     * @return boolean
     */
    private function batchDeleteThenInsertData($repository, $deleteWheres, $insertData) 
    {
        $repository->deleteByWhere($deleteWheres);
        if (!empty($insertData)) {
            return $repository->insertMultipleData($insertData);
        }
        return true;
    }
    /**
     * 获取wifi列表
     *
     * @param array $param
     * @return array
     */
    public function getWifiList($param)
    {
        return $this->response(app($this->attendanceWifiRepository), 'getWifiTotal', 'getWifiList', $this->parseParams($param));
    }

    /**
     * 新建wifi信息
     *
     * @param array $data
     * @return object
     */
    public function addWifi($data)
    {
        return $this->handleWifi($data, function ($data) {
            return app($this->attendanceWifiRepository)->insertData($data);
        });
    }

    /**
     * 编辑wifi信息
     * @param $data
     * @param $wifiId
     * @return mixed
     */
    public function editWifi($data, $wifiId)
    {
        return $this->handleWifi($data, function ($data) use ($wifiId) {
            return app($this->attendanceWifiRepository)->updateData($data, ['attend_wifi_id' => $wifiId]);
        }, $wifiId);
    }

    /**
     * 处理wifi信息后保存
     *
     * @param array $data
     * @param Function $terminal
     * @param int $wifiId
     * @return object|boolean
     */
    private function handleWifi($data, $terminal, $wifiId = false)
    {
        foreach (['attend_user', 'attend_dept', 'attend_role'] as $dataKey) {
            $data[$dataKey] = isset($data[$dataKey]) && $data[$dataKey] ? json_encode($data[$dataKey]) : '';
        }
        $data['attend_wifi_mac'] = trim($data['attend_wifi_mac']);// 除去左右空格
        if (app($this->attendanceWifiRepository)->wifiMacExists($data['attend_wifi_mac'], $wifiId)) {
            return ['code' => ['0x044036', 'attendance']];
        }
        return $terminal($data);
    }

    /**
     * 获取wifi信息
     *
     * @param int $wifiId
     * @return object
     */
    public function getWifiDetail($wifiId)
    {
        $wifiInfo = app($this->attendanceWifiRepository)->getDetail($wifiId);

        foreach (['attend_user', 'attend_dept', 'attend_role'] as $dataKey) {
            $wifiInfo->{$dataKey} = json_decode($wifiInfo->{$dataKey});
        }

        return $wifiInfo;
    }

    /**
     * 删除wifi
     *
     * @param int $wifiId
     * @return boolean
     */
    public function deleteWifiInfo($wifiId)
    {
        return app($this->attendanceWifiRepository)->deleteByWhere(['attend_wifi_id' => [$wifiId]]);
    }

    /**
     * 获取节假日方案列表
     */
    public function getSchemeList($params)
    {
        $params = $this->parseParams($params);

        return $this->response(app($this->attendanceRestSchemeRepository), 'getRestSchemeTotal', 'getRestSchemeList', $params);
    }

    /**
     * 添加节假日方案
     */
    public function addScheme($data)
    {
        $data['status'] = $this->defaultValue('status', $data, 0);
        $scheme = app($this->attendanceRestSchemeRepository)->insertData($data);
        if ($scheme) {
            $insertId = $scheme->scheme_id;
            //判断是否启用了当前的方案
            if ($data['status'] == 1) {     
                app($this->attendanceRestSchemeRepository)->updateData(['status' => '0'], [
                    'scheme_id' => [$insertId, '!='],
                ]);
            }
            if(isset($data['items']) && !empty($data['items'])) {
                $this->editSchemeRest($data['items'], $insertId);
            }
            
            return $scheme;
        }
        return ['code' => ['0x044046', 'attendance']];
    }

    /**
     * 删除节假日方案（软删除，历史记录要用到）
     */
    public function deleteScheme($scheme_id)
    {
        if (app($this->attendanceSchedulingRepository)->getOneScheduling(['holiday_scheme_id' => [$scheme_id]])) {
            return ['code' => ['0x044092', 'attendance']];
        }
        $scheme = app($this->attendanceRestSchemeRepository)->getOneSchemeById($scheme_id);
        if ($scheme) {
            //判断该方案是否启用
            if ($scheme->status == 1) {
                return ['code' => ['0x044047', 'attendance']];
            }
            app($this->attendanceRestSchemeRepository)->deleteByWhere(['scheme_id' => [$scheme_id]]);
            app($this->attendanceRestRepository)->deleteByWhere(['scheme_id' => [$scheme_id]]);
            return true;
        }
        return true;
    }

    /**
     * 快速编辑方案状态
     * @param $data
     * @param $scheme_id
     * @return bool
     */
    public function quickEditSchemeStatus($data, $scheme_id)
    {
        $scheme = app($this->attendanceRestSchemeRepository)->getOneSchemeById($scheme_id);
        if ($scheme) {
            //关闭已启用的方案失败，最少启用一种方案
            if ($scheme->status == 1 && $data['data_value'] == 0) {
                return ['code' => ['0x044048', 'attendance']];
            }
            //快速编辑状态只能是开启操作,开启当前编辑的，关闭其他的
            if ($scheme->status == 0 && $data['data_value'] == 1) {
                app($this->attendanceRestSchemeRepository)->updateData(['status' => '1'], ['scheme_id' => [$scheme_id, '=']]);
                app($this->attendanceRestSchemeRepository)->updateData(['status' => '0'], ['scheme_id' => [$scheme_id, '!=']]);
                return true;
            }
        }
    }

    /**
     * 获取某个方案详情
     * @param $scheme_id
     * @return mixed
     */
    public function getOneSchemeDetail($scheme_id)
    {
        $scheme = app($this->attendanceRestSchemeRepository)->getOneSchemeById($scheme_id);
        $scheme->items = $this->getSchemeDetailByID($scheme_id);
        
        return $scheme;
    }

    /**
     * 编辑节假日方案
     * @param $data
     * @param $scheme_id
     * @return array
     */
    public function editScheme($data, $scheme_id)
    {
        $scheme = app($this->attendanceRestSchemeRepository)->getOneSchemeById($scheme_id);
        if ($scheme) {
            //关闭已启用的方案失败，最少启用一种方案
            if ($scheme->status == 1 && $data['status'] == 0) {
                return ['code' => ['0x044048', 'attendance']];
            }
            //如果设置了默认开启该方案，则关闭其他方法
            if ($scheme->status == 0 && $data['status'] == 1) {
                app($this->attendanceRestSchemeRepository)->updateData(['status' => '0'], ['scheme_id' => [$scheme_id, '!=']]);
            }
            //更新
            $result = app($this->attendanceRestSchemeRepository)->updateData($data, ['scheme_id' => [$scheme_id, '=']]);
            
            if(isset($data['items']) && !empty($data['items'])) {
                $this->editSchemeRest($data['items'], $scheme_id);
            }
            return $result;
        }
    }

    /**
     * 获取节假日方案下的所有节假日
     */
    public function getSchemeDetailByID($scheme_id)
    {
        $rests = app($this->attendanceRestRepository)->getSchemeDetailById($scheme_id);
        foreach ($rests as $key => $rest) {
            $rest['lang_key'] = $rest->rest_name;
            $langKeys = 'attend_rest.rest_name.rest_name.' . $rest->rest_name;
            $rests[$key]['rest_name'] = mulit_trans_dynamic($langKeys);
            $rests[$key]['rest_name_lang'] = app($this->langService)->transEffectLangs($langKeys, true);
            $rests[$key]['rest_range'] = ['startDate' => $this->formatEmptyDate($rest->start_date), 'endDate' => $this->formatEmptyDate($rest->end_date)];
            $rests[$key]['rest_dates'] = $this->getDateFromRange($rest->start_date, $rest->end_date);
        }
        return $rests;
    }
    private function formatEmptyDate($date)
    {
        return $date == '0000-00-00' ? '' : $date;
    }

    /**
     * 获取正在使用的节假日方案的所有节假日信息
     */
    public function getUsedSchemeDetail()
    {
        $scheme = app($this->attendanceRestSchemeRepository)->getOneScheme(['status' => ['1']], true)->toArray();
        foreach ($scheme['rest'] as $key => $value) {
            $langKeys = 'attend_rest.rest_name.rest_name.' . $scheme['rest'][$key]['rest_name'];
            $scheme['rest'][$key]['rest_name'] = mulit_trans_dynamic($langKeys);
        }
        return $scheme;
    }

    /**
     * 编辑某节假日方案下的节假日
     * @param $data
     * @param $scheme_id
     */
    public function editSchemeRest($data, $scheme_id)
    {
        //处理下前端多语言保存的bug
        $local = Lang::getLocale();
        foreach ($data as $key => $value) {
            $data[$key]['rest_name_lang'][$local] = $value['rest_name'];
        }

        $oldRest = $this->getSchemeDetailByID($scheme_id)->toArray();
        $oldRestId = array_column($oldRest, 'rest_id');
        $newRestId = array_column($data, 'rest_id');
        //删除节假日的id
        $delRestId = array_diff($oldRestId, $newRestId);
        if ($delRestId) {
            app($this->attendanceRestRepository)->deleteByWhere(['rest_id' => [$delRestId, 'in']]);
        }
        $langServie = app($this->langService);
        //新增的节假日记录
        foreach ($data as $rest) {
            if (!isset($rest['rest_id'])) {
                list($startDate, $endDate) = $this->parseRestDate($rest);
                $newRest = app($this->attendanceRestRepository)->insertData(['scheme_id' => $scheme_id, 'start_date' => $startDate, 'end_date' => $endDate]);
                if ($newRest) {
                    $langKey = 'attend_rest_' . $newRest->rest_id;
                    app($this->attendanceRestRepository)->updateData(['rest_name' => $langKey], ['rest_id' => [$newRest->rest_id]]);
                    $this->modifyRestNameLang($langServie, $rest['rest_name_lang'], $langKey);
                }
            }
        }
        //更新的节假日记录
        $keyOldRest = [];
        foreach ($oldRest as $rest) {
            $keyOldRest[$rest['rest_id']] = $rest;
        }
        foreach ($data as $rest) {
            if (isset($rest['rest_id']) && !empty($rest['rest_id']) && isset($keyOldRest[$rest['rest_id']])) {
                list($startDate, $endDate) = $this->parseRestDate($rest);
                app($this->attendanceRestRepository)->updateData(['start_date' => $startDate, 'end_date' => $endDate], ['rest_id' => [$rest['rest_id']]]);
                $oldMultLang = $keyOldRest[$rest['rest_id']]['rest_name_lang'];

                $diffMultLang = array_diff($rest['rest_name_lang'], $oldMultLang);
                //修改了多语言
                $this->modifyRestNameLang($langServie, $diffMultLang, $rest['lang_key']);
            }
        }
        return true;
    }
    private function parseRestDate($rest)
    {
        $startDate = $rest['rest_range']['startDate'] ?? ($rest['start_date']??'');
        $endDate = $rest['rest_range']['endDate'] ?? ($rest['end_date']??'');
        
        return [$startDate, $endDate];
    }
    private function modifyRestNameLang($langServie, $langs, $langKey)
    {
        if(!empty($langs)) {
            foreach ($langs as $local => $langValue) {
                $langServie->addDynamicLang(['table' => 'attend_rest', 'column' => 'rest_name', 'lang_key' => $langKey, 'lang_value' => $langValue], $local);
            }
        }
    }
    /**
     * 同步节假日
     * @param $data
     */
    public function syncHoliday($data)
    {
        if (!$this->hasAll($data, ['scheduling_id', 'current_scheduling_id'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        $schedulingIds = $data['scheduling_id'];
        $currentSchedulingId = $data['current_scheduling_id'];
        $wheres = [
            'scheduling_id' => [$currentSchedulingId],
            'scheduling_date' => [$this->currentDate, '>']
        ];
        $schedulingRest = app($this->attendanceSchedulingRestRepository)->getSchedulingRest($wheres)->toArray();
        $syncDate = array();
        $syncData = array();
        //组装同步数据
        if ($schedulingRest) {
            foreach ($schedulingRest as $holiday) {
                $date = $holiday['scheduling_date'];
                $syncDate[] = $date;
                foreach ($schedulingIds as $schedulingId) {
                    $syncData[$schedulingId][] = [
                        'scheduling_id' => $schedulingId,
                        'scheduling_date' => $date,
                        'rest_id' => $holiday['rest_id'],
                        'year' => $holiday['year'],
                        'month' => $holiday['month']
                    ];
                }
            }
        }
        $scheduling = app($this->attendanceSchedulingRepository)->getDetail($currentSchedulingId);
        //开始同步数据
        foreach ($schedulingIds as $schedulingId) {
            app($this->attendanceSchedulingRepository)->updateData(['holiday_scheme_id' => $scheduling->holiday_scheme_id], ['scheduling_id' => [$schedulingId]]);
            app($this->attendanceSchedulingRestRepository)->deleteByWhere(['scheduling_id' => [$schedulingId], 'scheduling_date' => [$this->currentDate, '>']]);
            if (isset($syncData[$schedulingId])) {
                $holidayData = $syncData[$schedulingId];
                app($this->attendanceSchedulingDateRepository)->deleteByWhere(['scheduling_id' => [$schedulingId], 'scheduling_date' => [$syncDate, 'in']]);
                app($this->attendanceSchedulingRestRepository)->insertMultipleData($holidayData);
            }
        }
        return true;
    }

    public function addPurviewGroup($data)
    {
        $this->handleGroupData($this->getPurviewGroupData($data), $data, '', false);

        return true;
    }

    public function editPurviewGroup($data, $groupId)
    {
        $updateData = $this->handleGroupData($this->getPurviewGroupData($data), $data, $groupId);

        return app($this->attendancePurviewGroupRepository)->updateData($updateData, ['group_id' => $groupId]);
    }
    private function getPurviewGroupData($data)
    {
        return [
            'group_name' => $data['group_name'],
            'purview_type' => $data['purview_type'],
            'all_department' => $data['all_department'] ?? 1,
            'all_superior' => $data['all_superior'] ?? 1,
            'all_sub_dept' => $data['all_sub_dept'] ?? 1,
            'all_sub' => $data['all_sub'] ?? 1,
            'all_staff' => $data['all_staff'] ?? 1,
            'remark' => $data['remark'],
        ];
    }
    public function handleGroupData($groupData, $data, $groupId = "", $type = true)
    {
        $data['all_department'] = $data['all_department'] ?? 1;
        $data['all_superior'] = $data['all_superior'] ?? 1;

        if ($data['purview_type'] == 1 && $data['all_department'] != 1) {
            $groupData['dept_ids'] = is_string($data['dept_ids']) ? $data['dept_ids'] : implode(',', $data['dept_ids']);
        }
        if ($data['purview_type'] == 2 && $data['all_superior'] != 1) {
            $groupData['superiors'] = is_string($data['superiors']) ? $data['superiors'] : implode(',', $data['superiors']);
        }

        if ($type) {
            $where = ['group_id' => [$groupId]];
        } else {
            $groupId = app($this->attendancePurviewGroupRepository)->insertGetId($groupData);
        }

        if ($data['purview_type'] == 3) {
            $userData = $deptData = $roleData = [];
            if ($type) {
                app($this->attendancePurviewUserRepository)->deleteBywhere($where);
                app($this->attendancePurviewDeptRepository)->deleteBywhere($where);
                app($this->attendancePurviewRoleRepository)->deleteBywhere($where);
            }

            foreach ($data['manager'] as $value) {
                if ($data['all_staff'] != 1) {
                    if (!empty($data['user_id'])) {
                        $userIds = $data['user_id'] == 'all' ? 'all' : implode(',', $data['user_id']);
                        $userData[] = ['group_id' => $groupId, 'manager' => $value, 'user_id' => $userIds];
                    }
                    if (!empty($data['dept_id'])) {
                        $deptIds = $data['dept_id'] == 'all' ? 'all' : implode(',', $data['dept_id']);
                        $deptData[] = ['group_id' => $groupId, 'manager' => $value, 'dept_id' => $deptIds];
                    }
                    if (!empty($data['role_id'])) {
                        $roleIds = $data['role_id'] == 'all' ? 'all' : implode(',', $data['role_id']);
                        $roleData[] = ['group_id' => $groupId, 'manager' => $value, 'role_id' => $roleIds];
                    }
                } else {
                    $userData[] = ['group_id' => $groupId, 'manager' => $value, 'user_id' => 'all'];
                    $deptData[] = ['group_id' => $groupId, 'manager' => $value, 'dept_id' => 'all'];
                    $roleData[] = ['group_id' => $groupId, 'manager' => $value, 'role_id' => 'all'];
                }
            }

            if (!empty($userData)) {
                app($this->attendancePurviewUserRepository)->insertMultipleData($userData);
            }
            if (!empty($deptData)) {
                app($this->attendancePurviewDeptRepository)->insertMultipleData($deptData);
            }
            if (!empty($roleData)) {
                app($this->attendancePurviewRoleRepository)->insertMultipleData($roleData);
            }
        }

        if ($type) {
            app($this->attendancePurviewOwnerRepository)->deleteBywhere($where);
        }
        foreach ($this->menus as $key => $value) {
            if (isset($data[$key]) && $data[$key] == 1) {
                app($this->attendancePurviewOwnerRepository)->insertData(['group_id' => $groupId, 'menu_id' => $value]);
            }
        }

        return $groupData;
    }

    public function getPurviewGroupList($params)
    {
        return $this->response(app($this->attendancePurviewGroupRepository), 'getPurviewGroupTotal', 'getPurviewGroupList', $this->parseParams($params));
    }

    public function getPurviewGroupDetail($groupId)
    {
        $data = app($this->attendancePurviewGroupRepository)->getDetail($groupId)->toArray();

        if (isset($data['purview_type'])) {
            $data['dept_ids'] = empty($data['dept_ids']) ? [] : explode(',', $data['dept_ids']);
            $data['superiors'] = empty($data['superiors']) ? [] : explode(',', $data['superiors']);

            $where = ['group_id' => [$groupId]];
            if ($data['purview_type'] == 3) {
                $userObj = app($this->attendancePurviewUserRepository);
                $deptObj = app($this->attendancePurviewDeptRepository);
                $roleObj = app($this->attendancePurviewRoleRepository);

                $users = $userObj->getPurviewByWhere($where)->pluck('manager')->toArray();
                $depts = $deptObj->getPurviewByWhere($where)->pluck('manager')->toArray();
                $roles = $roleObj->getPurviewByWhere($where)->pluck('manager')->toArray();
                $data['manager'] = array_values(array_unique(array_merge($users, $depts, $roles)));

                if ($data['all_staff'] != 1) {
                    $tempUser = $userObj->getOnePurview($where);
                    $tempDept = $deptObj->getOnePurview($where);
                    $tempRole = $roleObj->getOnePurview($where);
                    $data['user_id'] = isset($tempUser->user_id) ? ($tempUser->user_id == 'all' ? 'all' : explode(',', $tempUser->user_id)) : [];
                    $data['dept_id'] = isset($tempDept->dept_id) ? ($tempDept->dept_id == 'all' ? 'all' : explode(',', $tempDept->dept_id)) : [];
                    $data['role_id'] = isset($tempRole->role_id) ? ($tempRole->role_id == 'all' ? 'all' : explode(',', $tempRole->role_id)) : [];
                } else {
                    $data['user_id'] = $data['dept_id'] = $data['role_id'] = 'all';
                }
            }

            $menuIds = app($this->attendancePurviewOwnerRepository)->getOwnerList($where)->pluck('menu_id')->toArray();
            $menus = array_flip($this->menus);
            if (!empty($menuIds)) {
                foreach ($menuIds as $key => $value) {
                    if (isset($menus[$value])) {
                        $data[$menus[$value]] = 1;
                    }
                }
            }
        }

        return $data;
    }

    public function getManageUserByUserId($own, $menuId)
    {
        $groups = app($this->attendancePurviewOwnerRepository)->getOwnerByMenu($menuId);
        if (count($groups) == 0) {
            return [];
        }
        $userIds = [];
        foreach ($groups as $key => $group) {
            if (!isset($group->purview_type)) {
                continue;
            }
            if ($group->purview_type == 1) {
                $userIds = array_merge($userIds, $this->getManageUserIdAsDept($own, $group));
            } else if ($group->purview_type == 2) {
                $userIds = array_merge($userIds, $this->getManageUserIdAsSub($own, $group));
            } else if ($group->purview_type == 3) {
                $userIds = array_merge($userIds, $this->getManageUserIdAsCustom($own, $group));
            }
        }
        return array_unique($userIds);
    }

    private function getManageUserIdAsDept($own, $group)
    {
        if ($group->all_sub_dept == 1) {
            $department = app($this->departmentRepository)->getDepartmentInfo($own['dept_id']);
            $arrParentIds = explode(',', $department->arr_parent_id);
            array_push($arrParentIds, $own['dept_id']);
            $directors = app($this->departmentDirectorRepository)->getDirectorsByDeptIds($arrParentIds)->pluck('user_id')->toArray();
        } else {
            $directors = app($this->departmentDirectorRepository)->getDirectorsByDeptIds([$own['dept_id']])->pluck('user_id')->toArray();
        }
        if ($group->all_department) {
            return $directors;
        }
        $customDeptIds = $group->dept_ids ? explode(',', $group->dept_ids) : [];
        if (empty($customDeptIds)) {
            return [];
        }
        $customDirectors = app($this->departmentDirectorRepository)->getDirectorsByDeptIds($customDeptIds)->pluck('user_id')->toArray();
        return array_intersect($directors, $customDirectors);
    }

    private function getManageUserIdAsSub($own, $group)
    {
        
        if ($group->all_sub !== 1) {
            $superior = app($this->userService)->getSuperiorArrayByUserId($own['user_id'], ['include_leave' => true]);
        } else {
            $superior = app($this->userService)->getSuperiorArrayByUserId($own['user_id'], ['all_superior' => 1, 'include_leave' => true]);
        }
        $superior = isset($superior['id']) ? $superior['id'] : [];

        if ($group->all_superior) {
            return $superior;
        }
        $customSuperiors = $group->superiors ? explode(',', $group->superiors) : [];
        return array_intersect($superior, $customSuperiors);
    }

    private function getManageUserIdAsCustom($own, $group)
    {
        $where = ['group_id' => [$group->group_id]];
        // 管理的用户
        $manageUser = app($this->attendancePurviewUserRepository)->getPurviewByWhere($where);
        $manageDept = app($this->attendancePurviewDeptRepository)->getPurviewByWhere($where);
        $manageRole = app($this->attendancePurviewRoleRepository)->getPurviewByWhere($where);
        $manageUserIds = [];
        if (!$manageUser->isEmpty()) {
            foreach ($manageUser as $item) {
                if ($item->user_id == 'all') {
                    $manageUserIds[] = $item->manager;
                } else {
                    $userIds = $item->user_id ? explode(',', $item->user_id) : [];
                    if (!empty($userIds) && in_array($own['user_id'], $userIds)) {
                        $manageUserIds[] = $item->manager;
                    }
                }
            }
        }
        if (!$manageDept->isEmpty()) {
            foreach ($manageDept as $item) {
                if ($item->dept_id == 'all') {
                    $manageUserIds[] = $item->manager;
                } else {
                    $deptIds = $item->dept_id ? explode(',', $item->dept_id) : [];
                    if (!empty($deptIds) && in_array($own['dept_id'], $deptIds)) {
                        $manageUserIds[] = $item->manager;
                    }
                }
            }
        }
        if (!$manageRole->isEmpty()) {
            foreach ($manageRole as $item) {
                if ($item->role_id == 'all') {
                    $manageUserIds[] = $item->manager;
                } else {
                    $roleIds = $item->role_id ? explode(',', $item->role_id) : [];
                    if (!empty($roleIds)) {
                        $intersectRoleId = array_intersect($roleIds, $own['role_id']);
                        if (!empty($intersectRoleId)) {
                            $manageUserIds[] = $item->manager;
                        }
                    }
                }
            }
        }
        return array_unique($manageUserIds);
    }

    public function getPurviewUser($own, $menuId)
    {
        $group = app($this->attendancePurviewOwnerRepository)->getOwnerByMenu($menuId);
        $user = [];
        if (count($group) > 0) {
            foreach ($group as $key => $value) {
                if (!isset($value->purview_type)) {
                    continue;
                }
                $tempUser = $this->parsePurview($value, $own);
                if ($tempUser != 'all') {
                    $user = array_values(array_unique(array_merge($user, $tempUser)));
                } else {
                    return 'all';
                }
            }

            return $user;
        }

        return [];
    }

    /**
     * 获取有权限管理的用户，加入过滤条件
     * @param $own
     * @param $menuId
     * @param $fliter
     * @return mixed
     */
    public function filterPurviewUser($own, $menuId, $fliter = [])
    {
        $viewUser = app($this->attendanceSettingService)->getPurviewUser($own, $menuId);
        $params = ['noPage' => true];
        if (isset($fliter['user_id'])) {
            if ($viewUser !== 'all') {
                if (!is_array($fliter['user_id'])) {
                    $fliter['user_id'] = [$fliter['user_id']];
                }
                $userIds = array_intersect($viewUser, $fliter['user_id']);
                $params['user_id'] = $userIds;
            } else {
                $params['user_id'] = $fliter['user_id'];
            }
        } else if ($viewUser !== 'all') {
            $params['user_id'] = $viewUser;
        }
        if (isset($fliter['dept_id'])) {
            $params['dept_id'] = $fliter['dept_id'];
        }
        if (isset($fliter['role_id'])) {
            $params['role_id'] = $fliter['role_id'];
        }
        if ((isset($params['user_id']) && !$params['user_id'])
            || (isset($params['dept_id']) && !$params['dept_id'])
            || (isset($params['role_id']) && !$params['role_id'])) {
            return [];
        }
        $users = app($this->userRepository)->getSimpleUserList($params);
        return $users;
    }

    public function parsePurview($group, $own)
    {
        $user = [];
        switch ($group->purview_type) {
            case 1: // 部门负责人
                $director = app($this->departmentDirectorRepository)->getManageDeptByUser($own['user_id'])->pluck('dept_id')->toArray();
                // 不是部门负责人
                if (empty($director)) {
                    return [];
                }
                if ($group->all_department == 0) {
                    // 指定部门的部门负责人
                    $director = array_intersect($director, explode(',', $group->dept_ids));
                    if (empty($director)) {
                        return [];
                    }
                }
                if ($group->all_sub_dept == 1) {
                    //管理本部门和所有下级部门
                    foreach ($director as $key => $value) {
                        $allChildren = app($this->departmentService)->allChildren($value);
                        $deptIds = explode(',', $allChildren);
                        $tempUser = app($this->userRepository)->getUserByAllDepartment($deptIds)->pluck('user_id')->toArray();
                        $user = array_values(array_unique(array_merge($user, $tempUser)));
                    }
                } else {
                    // 默认管理本部门
                    $user = app($this->userRepository)->getUserByAllDepartment($director)->pluck('user_id')->toArray();
                }
                break;
            case 2: //上下级
                $sub = app($this->userService)->getSubordinateArrayByUserId($own['user_id'], ['include_leave' => true]);
                // 没有下级返回[]
                if (!isset($sub['id'])) {
                    return [];
                }
                if ($group->all_superior == 0) {
                    // 指定上级
                    $purviewUser = explode(',', $group->superiors);
                    if (!in_array($own['user_id'], $purviewUser)) {
                        return [];
                    }
                }
                if ($group->all_sub == 1) {
                    $allSub = app($this->userService)->getSubordinateArrayByUserId($own['user_id'], ['all_subordinate' => 1, 'include_leave' => true]);
                    $user = isset($allSub['id']) ? $allSub['id'] : [];
                } else {
                    $user = isset($sub['id']) ? $sub['id'] : [];
                }
                break;
            case 3://自定义
                $user = $this->getCustomPurview($group->group_id, $own);
                break;
            default:
                break;
        }
        return $user;
    }

    public function getCustomPurview($groupId, $own)
    {
        $where = ['group_id' => [$groupId], 'manager' => [$own['user_id']]];
        // 管理的用户
        $manageUser = app($this->attendancePurviewUserRepository)->getOnePurview($where);
        $manageUserId = [];
        if (isset($manageUser->user_id)) {
            if ($manageUser->user_id == 'all') {
                return 'all';
            }
            $manageUserId = explode(',', $manageUser->user_id);
        }
        // 管理的部门
        $manageDept = app($this->attendancePurviewDeptRepository)->getOnePurview($where);
        $manageDeptId = [];
        if (isset($manageDept->dept_id)) {
            if ($manageDept->dept_id == 'all') {
                return 'all';
            }
            $manageDeptId = explode(',', $manageDept->dept_id);
        }
        $manageUserByDept = empty($manageDeptId) ? [] : app($this->userRepository)->getUserByAllDepartment($manageDeptId)->pluck("user_id")->toArray();
        // 管理的角色
        $manageRole = app($this->attendancePurviewRoleRepository)->getOnePurview($where);
        $manageRoleId = [];
        if (isset($manageRole->role_id)) {
            if ($manageRole->role_id == 'all') {
                return 'all';
            }
            $manageRoleId = explode(',', $manageRole->role_id);
        }
        $manageUserByRole = empty($manageRoleId) ? [] : app($this->userRepository)->getUserListByRoleId($manageRoleId)->pluck("user_id")->toArray();

        return array_values(array_unique(array_merge($manageUserId, $manageUserByDept, $manageUserByRole)));
    }

    // 删除权限组
    public function deletePurviewGroup($groupIds)
    {
        $groupIds = explode(',', $groupIds);
        if (sizeof($groupIds) == 0) {
            return ['code' => ['0x044049', 'attendance']];
        }

        app($this->attendancePurviewUserRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);
        app($this->attendancePurviewDeptRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);
        app($this->attendancePurviewRoleRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);
        app($this->attendancePurviewOwnerRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);
        app($this->attendancePurviewGroupRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);

        return true;
    }
    // 获取考勤导出时的部门、用户、角色权限
    public function getExportPurview($own, $menuId)
    {
        $user = $this->getPurviewUser($own, $menuId);

        if ($user == 'all') {
            return [
                'user' => $user,
                'dept' => 'all',
                'role' => 'all'
            ];
        }

        if (empty($user)) {
            return $user;
        }

        $dept = [];
        $role = [];

        $deptArr = app($this->userSystemInfoRepository)->getInfoByWhere(['user_id' => [$user, 'in']], ['dept_id']);
        if (!empty($deptArr)) {
            $dept = array_values(array_unique(array_column($deptArr, 'dept_id')));
        }

        $roleArr = app($this->userRoleRepository)->getUserRole(['user_id' => [$user, 'in']], 1);
        if (!empty($roleArr)) {
            $role = array_values(array_unique(array_column($roleArr, 'role_id')));
        }

        return [
            'user' => $user,
            'dept' => $dept,
            'role' => $role
        ];
    }

    /**
     * 获取加班配置
     * @return array
     */
    public function getOvertimeConfig($userId)
    {
        $schedulingId = app($this->attendanceSchedulingUserRepository)->getSchedulingIdByUser($userId)->scheduling_id ?? false;
        if (!$schedulingId) {
            return false;
        }
        $rule = app($this->attendanceOvertimeRuleSchedulingRepository)->getRuleByScheduling($schedulingId);
        return $rule;
    }

    /**
     * 获取加班规则列表
     */
    public function getOvertimeRuleList($params)
    {
        $params = $this->parseParams($params);
        //搜索班组名称单独处理下
        if (isset($params['search']['schedulings_name']) && $params['search']['schedulings_name']) {
            $searchScheduling = $params['search']['schedulings_name'];
            unset($params['search']['schedulings_name']);
            $params['search']['id'] = [[], 'in'];
            $schedulings = app($this->attendanceSchedulingRepository)->getSchedulingList([
                'search' => [
                    'scheduling_name' => $searchScheduling
                ],
                'page' => 0
            ])->toArray();
            if ($schedulings) {
                $schedulingIds = array_column($schedulings, 'scheduling_id');
                $ruleSchedulings = app($this->attendanceOvertimeRuleSchedulingRepository)->getRuleByIds($schedulingIds)->toArray();
                if ($ruleSchedulings) {
                    $ruleIds = array_unique(array_column($ruleSchedulings, 'rule_id'));
                    $params['search']['id'] = [$ruleIds, 'in'];
                }
            }
        }
        $list = app($this->attendanceOvertimeRuleRepository)->getRules($params, true)->toArray();
        $list = array_map(function ($row) {
            $schedulingsName = '';
            $schedulingIds = array_column($row['scheduling'], 'scheduling_id');
            if ($schedulingIds) {
                $schedulings = app($this->attendanceSchedulingRepository)->getSchedulingBySchedulingIds($schedulingIds)->toArray();
                if ($schedulings) {
                    $schedulingsName = implode('、', array_column($schedulings, 'scheduling_name'));
                }
            }
            $row['schedulings_name'] = $schedulingsName;
            return $row;
        }, $list);
        $total = app($this->attendanceOvertimeRuleRepository)->getRulesTotal($params);
        return ['list' => $list, 'total' => $total];
    }

    /**
     * 新建加班规则
     * @param $data
     */
    public function addOvertimeRule($data)
    {
        $data['rest_diff_time'] = json_encode($data['rest_diff_time']);
        $data['holiday_diff_time'] = json_encode($data['holiday_diff_time']);
        if ($res = app($this->attendanceOvertimeRuleRepository)->insertData($data)) {
            if (isset($data['scheduling_ids']) && $data['scheduling_ids']) {
                $rule_id = $res->id;
                $insert = array();
                foreach ($data['scheduling_ids'] as $schedulingId) {
                    $insert[] = ['rule_id' => $rule_id, 'scheduling_id' => $schedulingId];
                }
                //删除这些班组在其他规则下的记录
                $wheres = ['scheduling_id' => [$data['scheduling_ids'], 'in']];
                app($this->attendanceOvertimeRuleSchedulingRepository)->deleteByWhere($wheres);
                app($this->attendanceOvertimeRuleSchedulingRepository)->insertMultipleData($insert);
            }
        }
        return $res;
    }

    /**
     * 获取规则详情
     */
    public function getOvertimeRuleDetail($ruleId)
    {
        $wheres = ['search' => ['id' => [$ruleId]]];
        $list = app($this->attendanceOvertimeRuleRepository)->getRules($wheres, true)->toArray();
        if (!$list) {
            return ['code' => ['0x044052', 'attendance']];
        }
        $data = $list[0];
        $data['scheduling_ids'] = array_column($data['scheduling'], 'scheduling_id');
        $data['rest_diff_time'] = json_decode($data['rest_diff_time'], true);
        $data['holiday_diff_time'] = json_decode($data['holiday_diff_time'], true);
        return $data;
    }

    /**
     * 更新加班规则
     * @param $data
     * @param $ruleId
     * @return array|bool
     */
    public function updateOvertimeRule($data, $ruleId)
    {
        $wheres = ['search' => ['id' => [$ruleId]]];
        $records = app($this->attendanceOvertimeRuleRepository)->getRules($wheres, true)->toArray();
        if (!$records) {
            return ['code' => ['0x044052', 'attendance']];
        }
        $record = $records[0];
        $oldSchedulingIds = array_column($record['scheduling'], 'scheduling_id');
        $newSchedulingIds = $data['scheduling_ids'] ?? [];
        $deleteSchedulingIds = array_diff($oldSchedulingIds, $newSchedulingIds);
        $addSchedulingIds = array_diff($newSchedulingIds, $oldSchedulingIds);
        //更新
        $data['rest_diff_time'] = json_encode($data['rest_diff_time']);
        $data['holiday_diff_time'] = json_encode($data['holiday_diff_time']);
        if (app($this->attendanceOvertimeRuleRepository)->updateData($data, ['id' => $ruleId])) {
            if ($deleteSchedulingIds) {
                app($this->attendanceOvertimeRuleSchedulingRepository)->deleteByWhere(['scheduling_id' => [$deleteSchedulingIds, 'in']]);
            }
            if ($addSchedulingIds) {
                app($this->attendanceOvertimeRuleSchedulingRepository)->deleteByWhere(['scheduling_id' => [$addSchedulingIds, 'in']]);
                $insert = [];
                foreach ($addSchedulingIds as $schedulingId) {
                    $insert[] = ['rule_id' => $ruleId, 'scheduling_id' => $schedulingId];
                }
                app($this->attendanceOvertimeRuleSchedulingRepository)->insertMultipleData($insert);
            }
        }
        return true;
    }

    /**
     * 删除加班规则
     * @param $ruleId
     */
    public function deleteOvertimeRule($ruleId)
    {
        if (app($this->attendanceOvertimeRuleRepository)->deleteByWhere(['id' => [$ruleId]])) {
            return app($this->attendanceOvertimeRuleSchedulingRepository)->deleteByWhere(['rule_id' => [$ruleId]]);
        }
    }
    /**
     * 开关修改状态
     * @param $data
     * @return mixed
     */
    public function editOvertimeRuleOpenStatus($data)
    {
        return app($this->attendanceOvertimeRuleRepository)->updateData([$data['field'] => $data['value']], ['id' => $data['id']]);
    }

    public function editCommonSetting($data, $type)
    {
        if (!empty($data)) {
            $setBaseRepository = app($this->attendanceSetBaseRepository);
            foreach ($data as $key => $value) {
                if ($setBaseRepository->paramExists($key)) {
                    $setBaseRepository->updateData(['param_value' => $value], ['param_key' => $key]);
                } else {
                    $setBaseRepository->insertData(['param_key' => $key, 'param_value' => $value]);
                }
            }
            if ($type == 'no_sign_out') {
                $this->setCommonPurviewGroup($data, 3);
                $data['all_member'] = $data['auto_all_member'] ?? 0;
                $data['user_id'] = $data['auto_user_id'] ?? [];
                $data['dept_id'] = $data['auto_dept_id'] ?? [];
                $data['role_id'] = $data['auto_role_id'] ?? [];
                $this->setCommonPurviewGroup($data, 5);
            } else if($type == 'sign_remind') {
                $this->setCommonPurviewGroup($data, 4);
            }
        }
        if ($type == 'base') {
            ecache('Attendance:AttendanceStatUnitConfig')->clear();
        }
        ecache('Attendance:AttendanceCommonSetting')->clear($type);
        return true;
    }
    public function getAllowNoSignOutUserIdsMap($userIds)
    {
        $config = $this->getCommonSetting('no_sign_out');
        
        if (!$config['allow_no_sign_out']) {
            return [];
        }
        
        if ($config['all_member']) {
            return $this->arrayToMapAsKey($userIds, true);
        }
        
        $allowUserIds = $this->getUserIdByUserRoleDeptConfig($config);
        
        $allowNoSignOutUserIds = array_intersect($userIds, $allowUserIds);

        return $this->arrayToMapAsKey($allowNoSignOutUserIds, true);
    }
    /**
     * 判断是否隐藏签退按钮
     * @param type $own
     * @return type
     */
    public function isHideSignOutButton($own) 
    {
        $config = $this->getCommonSetting('no_sign_out');
        if (!$config['allow_no_sign_out']) {
            return ['hide_sign_out_btn' => 0];
        }
        if (isset($config['hide_sign_out_btn']) && $config['hide_sign_out_btn']) {
            if ($this->inMembers($config, $own['user_id'], $own['dept_id'], $own['role_id'])) {
                return ['hide_sign_out_btn' => 1];
            }
        }
        return ['hide_sign_out_btn' => 0];
    }

    public function getCommonSetting($type)
    {
        $result = ecache('Attendance:AttendanceCommonSetting')->get($type);
        if(!$result){
            $keys = $this->commonSettingKeysMap[$type] ?? [];
            $params = app($this->attendanceSetBaseRepository)->getParamsByKeys($keys);
            if ($params->isEmpty()) {
                $result = $this->arrayToMapAsKey($keys, 0);
            } else {
                $result = $params->mapWithKeys(function ($item) {
                    return [$item->param_key => intval($item->param_value)];
                });
                if ($type == 'no_sign_out') { // 3免签退成员，5自动签退，4免打卡提醒成员
                    $result = $this->mergeSettingMember($result, 3, $result['allow_no_sign_out'] == 1);
                    $result['allow_auto_sign_out'] = $result['allow_auto_sign_out'] ?? 0;
                    if ($result['allow_auto_sign_out'] == 1) {
                        $purviewGroup = $this->getCommonPurviewGroup(5);
                        $result['auto_all_member'] = $purviewGroup['all_member'];
                        $result['auto_user_id'] = $purviewGroup['user_id'];
                        $result['auto_dept_id'] = $purviewGroup['dept_id'];
                        $result['auto_role_id'] = $purviewGroup['role_id'];
                    } else {
                        $result['auto_all_member'] = 0;
                        $result['auto_user_id'] = [];
                        $result['auto_dept_id'] = [];
                        $result['auto_role_id'] = [];
                    }
                } else if($type == 'sign_remind') {
                    $result = $this->mergeSettingMember($result, 4, $result['open_sign_remind'] == 1);
                }
            }
            ecache('Attendance:AttendanceCommonSetting')->set($type, $result);
        }
        return $result;
    }
    private function mergeSettingMember($result, $type, $getMember = false)
    {
        if ($getMember) {
            $purviewGroup = $this->getCommonPurviewGroup($type);
            $result['all_member'] = $purviewGroup['all_member'];
            $result['user_id'] = $purviewGroup['user_id'];
            $result['dept_id'] = $purviewGroup['dept_id'];
            $result['role_id'] = $purviewGroup['role_id'];
        } else {
            $result['all_member'] = 0;
            $result['user_id'] = [];
            $result['dept_id'] = [];
            $result['role_id'] = [];
        }
        return $result;
    }
    private function arrayToMapAsKey($data, $value = null) 
    {
        $map = [];
        if (!empty($data)) {
            foreach ($data as $key) {
                $map[$key] = $value;
            }
        }
        return $map;
    }
    public function userLeaveDeleteUserAttendanceScheduling($userId)
    {
        // 获取考勤排版id
        $scheduling = app($this->attendanceSchedulingUserRepository)->getSchedulingIdByUser($userId);
        if (!$scheduling || !$scheduling->scheduling_id ){
            return true;
        }
        $deleteAllUserId = [$userId];
        $schedulingModifyRecordData = $this->getSchedulingModifyRecordData($deleteAllUserId, $scheduling->scheduling_id, []); //获取更新班组历史记录数据
        if (sizeof($deleteAllUserId) > 0) {
            //更新班组更新历史记录表
            app($this->attendanceSchedulingModifyRecordRepository)->insertMultipleData($schedulingModifyRecordData);

            app($this->attendanceSchedulingUserRepository)->deleteByWhere(['user_id' => [$deleteAllUserId, 'in']]); //删除当前用户对应的之前的班组关系
        }
        // 重新生成缓存
        return $this->getSchedulingUserCount($scheduling->scheduling_id, true);
    }
}
