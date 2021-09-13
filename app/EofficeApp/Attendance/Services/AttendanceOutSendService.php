<?php

namespace App\EofficeApp\Attendance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\EofficeApp\Attendance\Traits\AttendanceOvertimeTrait;
/**
 * 考勤外发相关
 * Class AttendanceOutSendService
 * @package App\EofficeApp\Attendance\Services
 */
class AttendanceOutSendService extends AttendanceBaseService
{
    use AttendanceOvertimeTrait;

    private $attendanceRecordsService;

    /**
     * 加班相关的全局参数
     * @var array
     */
    public static $overtime;

    public function __construct()
    {
        parent::__construct();
        $this->attendanceRecordsService = 'App\EofficeApp\Attendance\Services\AttendanceRecordsService';
        $this->calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';

    }

    /**
     * 请假外发
     * @param $data
     * @return array|bool
     */
    public function leave($data)
    {
        if (!$this->hasAll($data, ['user_id', 'vacation_id', 'leave_start_time', 'leave_end_time'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        $userId = $data['user_id'];
        if (!$this->userExists($userId)){
            return ['code' => ['0x044110', 'attendance']];
        }
        $vacationId = $data['vacation_id'];
        if (!in_array($vacationId, $this->getVacationIdsByUserId($userId))) {
            return ['code' => ['0x044090', 'attendance']];
        }
        if ($data['leave_start_time'] == $data['leave_end_time']) {
            return ['code' => ['0x044111', 'attendance']];
        }
        if ($data['leave_start_time'] > $data['leave_end_time']) {
            return ['code' => ['0x044112', 'attendance']];
        }
        $result = $this->handleOutSend($data, $this->attendanceLeaveRepository, $this->attendanceLeaveDiffStatRepository, 'leave', function ($startTime, $endTime) use ($userId, $vacationId, $data) {
            $effectTime = $this->parseEffectTime($startTime, $endTime, $userId, $data);
            //用户自己传了请假天数或者小时
            if (!isset($data['leave_days']) && !isset($data['leave_hours'])) {
                $leaveDays = app($this->vacationService)->parseLeaveDays($effectTime, $startTime, $endTime, $vacationId);
            }else{
                $leaveDays = $effectTime;
            }
            return $this->handleStatData($leaveDays, $userId, 'leave');
        });
        if (!$result) {
            return ['code' => ['0x044051', 'attendance']];
        }
        // 发送到日程
        list($data['leave_start_time'], $data['leave_end_time']) = $this->dateRangeToDatetimeRange($data['leave_start_time'], $data['leave_end_time']);
        $this->emitCalendar($data, 'leave', '请假申请');
        // 请假扣除天数如果前端传了就用前端传的
        $days = $data['leave_days'] ?? ($result[0] ?? 0);
        $hours = $data['leave_hours'] ?? ($result[1] ?? 0);
        if (!$days) {
            return true;
        }
        //扣除假期
        $vacationData = [
            'user_id' => $userId,
            'vacation_type' => $vacationId,
            'days' => $days,
            'hours' => $hours
        ];
        return app($this->vacationService)->leaveDataout($vacationData);
    }
    /**
     * 外出外发
     * @param $data
     * @return array|bool
     */
    public function out($data)
    {
        if (!$this->hasAll($data, ['user_id', 'out_start_time', 'out_end_time'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        if (!$this->userExists($data['user_id'])){
            return ['code' => ['0x044110', 'attendance']];
        }
        $result = $this->handleOutSend($data, $this->attendanceOutRepository, $this->attendanceOutStatRepository, 'out');
        if (!$result) {
            return ['code' => ['0x044051', 'attendance']];
        }
        // 发送到日程
        list($data['out_start_time'], $data['out_end_time']) = $this->dateRangeToDatetimeRange($data['out_start_time'], $data['out_end_time']);
        $this->emitCalendar($data, 'out', '外出申请');
        return true;
    }

    /**
     * 出差外发
     * @param $data
     * @return array|bool
     */
    public function trip($data)
    {
        if (!$this->hasAll($data, ['user_id', 'trip_start_date', 'trip_end_date'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        
        $mulitResult = $this->mulitSendData($data, function($handleData) {
            return $this->trip($handleData);
        });
        if ($mulitResult) {
            return $mulitResult;
        }
        if (!$this->userExists($data['user_id'])){
            return ['code' => ['0x044110', 'attendance']];
        }
        $result = $this->handleOutSend($data, $this->attendanceTripRepository, $this->attendanceTripStatRepository, 'trip');
        if (!$result) {
            return ['code' => ['0x044051', 'attendance']];
        }
        // 发送到日程
        list($data['trip_start_date'], $data['trip_end_date']) = $this->dateRangeToDatetimeRange($data['trip_start_date'], $data['trip_end_date']);
        $this->emitCalendar($data, 'trip', '出差申请');
        return true;
    }
    private function userExists($userId)
    {
        return get_user_simple_attr($userId) ? true : false;
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
    /**
     * 外发数据到日程模块
     * 
     * @param type $data
     * @param type $type
     * @param type $defaultRunName
     */
    private function emitCalendar($data, $type, $defaultRunName)
    {
        list ($startKey, $endKey) = $this->outSendTimeKeys[$type];
        $startTime = $this->getFullTime($data[$startKey]);
        $endTime = $this->getFullTime($data[$endKey]);
        $calendarData = [
            'calendar_content' => $data['run_name'] ?? $defaultRunName,
            'handle_user' => [$data['user_id']],
            'calendar_begin' => $startTime,
            'calendar_end' => $endTime
        ];
        $relationData = [
            'source_id' => $data['run_id'] ?? 0,
            'source_from' => 'flowouter-' . $type,
            'source_title' => $data['run_name'] ?? $defaultRunName,
            'source_params' => ['flow_id' => $data['flow_id'] ?? 0, 'run_id' => $data['run_id'] ?? 0]
        ];
        app($this->calendarService)->emit($calendarData, $relationData, $data['user_id']);
    }
    /**
     * 销假外发
     * @param $data
     */
    public function backLeave($data)
    {
        if (!$this->hasAll($data, ['user_id', 'leave_id', 'current_user_id'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        $leaveId = $data['leave_id'];
        $userId = $data['user_id'];
        $leave = app($this->attendanceLeaveRepository)->getDetail($leaveId);
        if (!$leave) {
            return ['code' => ['0x044065', 'attendance']];
        }
        //软删除请假记录
        if (!app($this->attendanceLeaveRepository)->deleteById($leaveId)) {
            return ['code' => ['0x044061', 'attendance']];
        }
        $record = [
            'leave_id' => $leave->leave_id,
            'user_id' => $userId,
            'vacation_id' => $leave->vacation_id,
            'back_leave_start_time' => $leave->leave_start_time,
            'back_leave_end_time' => $leave->leave_end_time,
            'back_leave_days' => $leave->leave_days,
            'back_leave_hours' => $leave->leave_hours,
            'back_leave_reason' => $data['back_leave_reason'] ?? '',
            'back_leave_extra' => $this->getExtraInfoFromFlow($data)
        ];
        //添加销假记录
        if (!$backLeave = app($this->attendanceBackLeaveRepository)->insertData($record)) {
            return ['code' => ['0x044061', 'attendance']];
        }
        //添加销假日志
        $log = [
            'leave_id' => $leave->leave_id,
            'back_leave_id' => $backLeave->back_leave_id,
            'user_id' => $userId,
            'vacation_id' => $leave->vacation_id,
            'back_leave_start_time' => $leave->leave_start_time,
            'back_leave_end_time' => $leave->leave_end_time,
            'back_leave_days' => $leave->leave_days,
            'back_leave_hours' => $leave->leave_hours,
            'approver_id' => $data['current_user_id']
        ];
        if (!app($this->attendanceBackLeaveLogRepository)->insertData($log)) {
            return ['code' => ['0x044061', 'attendance']];
        }
        //计算本次销假的天数，数据库没按天存，重新计算，要更新汇总表,也方便后期部门销假可拓展
        $leaveDaysHours = $this->parseEffectTime($record['back_leave_start_time'], $record['back_leave_end_time'], $userId);
        if (!$leaveDaysHours) {
            return true;
        }
        //更新汇总表
        foreach ($leaveDaysHours as $date => $time) {
            $leaveDays = $time[0];
            $leaveHours = $time[1];
            $wheres = [
                'date' => [$date],
                'user_id' => [$userId],
                'vacation_id' => [$leave->vacation_id]
            ];
            $result = app($this->attendanceLeaveDiffStatRepository)->getOneRecord($wheres)->toArray();
            if ($result) {
                $days = max($result['leave_days'] - $leaveDays, 0);
                $hours = max($result['leave_hours'] - $leaveHours, 0);
                app($this->attendanceLeaveDiffStatRepository)->updateData(['leave_days' => $days, 'leave_hours' => $hours], $wheres);
            }
        }
        $days = array_sum(array_column($leaveDaysHours, 0));
        $hours = array_sum(array_column($leaveDaysHours, 1));
        //销假把余额增加回去
        $overtimeData = [
            'user_id' => $userId,
            'days' => $days,
            'hours' => $hours,
            'vacation_id' => $leave->vacation_id
        ];
        return app($this->vacationService)->overtimeDataout($overtimeData);
    }

    /**
     * 补卡外发外发
     * @param $data
     */
    public function repair($data)
    {
        if (!$this->hasAll($data, ['current_user_id'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        if (!$this->hasAll($data, ['user_id'])) {
            return ['code' => ['0x044093', 'attendance']];
        }
        if (!$this->hasAll($data, ['repair_date'])) {
            return ['code' => ['0x044094', 'attendance']];
        }
        if (!$this->hasAll($data, ['sign_times'])) {
            return ['code' => ['0x044095', 'attendance']];
        }
        $date = $data['repair_date'];
        $userId = $data['user_id'];
        if (!$this->userExists($userId)){
            return ['code' => ['0x044110', 'attendance']];
        }
        $repairType = $data['repair_type'] ?? 0;//补卡类型
        $signTimes = explode(',', $data['sign_times']);
        $scheduling = $this->schedulingMapWithUserIdsByDate($date, [$userId])[$userId] ?? null;
        //休息日不可考勤
        if (!$scheduling) {
            return ['code' => ['0x044017', 'attendance']];
        }
        $shiftId = $scheduling['shift_id'];
        $shift = $this->getShiftById($shiftId);
        $shiftTimes = $this->getShiftTimeById($shiftId)->toArray();
        $normalCount = 0;
        foreach ($signTimes as $signTimesNumber) {
            //当前排班里面不存在该打卡时间段，提示班次异常
            if (!isset($shiftTimes[$signTimesNumber - 1])) {
                return ['code' => ['0x044019', 'attendance']];
            }
            $shiftTime = $shiftTimes[$signTimesNumber - 1];
            $signInNormal = $shiftTime['sign_in_time'];
            $signOutNormal = $shiftTime['sign_out_time'];
            list($signInTime, $signOutTime) = $this->getSignNormalDatetime($date, $signInNormal, $signOutNormal);
            //本次应出勤的工时
            $mustAttendTime = $shift->shift_type == 1 ? $shift->attend_time : strtotime($signOutTime) - strtotime($signInTime);
            $newSignInTime = $signInTime;
            $newSignOuttime = $signOutTime;
            //先判断下是否有打卡记录，有打卡记录则进行校准操作否则执行插入操作
            $wheres = [
                'user_id' => [$userId],
                'sign_date' => [$date],
                'sign_times' => [$signTimesNumber]
            ];
            $userRecord = app($this->attendanceRecordsRepository)->getOneAttendRecord($wheres);
            if ($userRecord) {
                $time = 0;
                $update = array();
                if(empty($repairType) || $repairType == 1){
                    if ($userRecord->is_lag == 1) {
                        $update['sign_in_time'] = $signInTime;
                        $update['original_sign_in_time'] = $signInTime;
                        $update['lag_time'] = 0;
                        $update['is_lag'] = 0;
                        $time += $userRecord->lag_time;
                    } else {
                        $newSignInTime = $userRecord->sign_in_time;
                    }
                }
                if(empty($repairType) || $repairType == 2){
                    if ($userRecord->is_leave_early == 1) {
                        $update['sign_out_time'] = $signOutTime;
                        $update['original_sign_out_time'] = $signOutTime;
                        $update['leave_early_time'] = 0;
                        $update['is_leave_early'] = 0;
                        $time += $userRecord->leave_early_time;
                    } else {
                        if ($userRecord->sign_out_time == '') {
                            $update['sign_out_time'] = $signOutTime;
                            $update['original_sign_out_time'] = $signOutTime;
                            $time = $mustAttendTime;
                        } else {
                            $newSignOuttime = $userRecord->sign_out_time;
                        }
                    }
                }
                //数据都正常无需执行补卡流程
                if (!$update) {
                    //统计无需补卡的班次
                    $normalCount++;
                    continue;
                }
                $update['is_repair'] = 2;
                $update['repair_time'] = $time;
                if (!app($this->attendanceRecordsRepository)->updateData($update, ['record_id' => [$userRecord->record_id]])) {
                    return ['code' => ['0x044061', 'attendance']];
                }
            } else {
                $record = [
                    'user_id' => $userId,
                    'sign_date' => $date,
                    'sign_in_normal' => $signInNormal,
                    'sign_out_normal' => $signOutNormal,
                    'must_attend_time' => $shift->attend_time,
                    'in_ip' => getClientIp(),
                    'out_ip' => getClientIp(),
                    'in_platform' => 1,
                    'out_platform' => 1,
                    'calibration_status' => 0,
                    'sign_times' => $signTimesNumber,
                    'shift_id' => $shiftId,
                    'is_offset' => 0,
                    'attend_type' => 1,
                    'is_repair' => 1,
                    'sign_in_time' => $signInTime,
                    'original_sign_in_time' => $signInTime,
                    'lag_time' => 0,
                    'is_lag' => 0,
                    'sign_out_time' => $signOutTime,
                    'original_sign_out_time' => $signOutTime,
                    'leave_early_time' => 0,
                    'is_leave_early' => 0,
                    'repair_time' => $mustAttendTime
                ];
                if (!app($this->attendanceRecordsRepository)->insertData($record)) {
                    return ['code' => ['0x044061', 'attendance']];
                }
            }
            //考勤记录已经修正了，记录下外发记录和补卡日志
            $repairRecordData = [
                'user_id' => $userId,
                'repair_date' => $date,
                'repair_type' => $repairType,
                'sign_times' => $signTimesNumber,
                'repair_reason' => $data['repair_reason'] ?? null,
                'repair_extra' => $this->getExtraInfoFromFlow($data),
            ];
            if (!$repairRecord = app($this->attendanceRepairRepository)->insertData($repairRecordData)) {
                return ['code' => ['0x044061', 'attendance']];
            }
            $repairLog = [
                'repair_id' => $repairRecord->repair_id,
                'user_id' => $userId,
                'approver_id' => $data['current_user_id'],
                'repair_date' => $date,
                'sign_times' => $signTimesNumber,
                'new_sign_in_time' => '',
                'new_sign_out_time' => '',
                'repair_type' => $repairType,
                'old_sign_in_time' => isset($userRecord->sign_in_time) && $userRecord->sign_in_time ? $userRecord->sign_in_time : null,
                'old_sign_out_time' => isset($userRecord->sign_out_time) && $userRecord->sign_out_time ? $userRecord->sign_out_time : null,
            ];

            if(empty($repairType) || $repairType == 1 || !$userRecord){
                $newSignInTime = $newSignInTime === '0000-00-00 00:00:00' ? '' : $newSignInTime;
                $repairLog['new_sign_in_time'] = $newSignInTime;
            }
            if(empty($repairType) || $repairType == 2 || !$userRecord){
                $newSignOuttime = $newSignOuttime === '0000-00-00 00:00:00' ? '' : $newSignOuttime;
                $repairLog['new_sign_out_time'] = $newSignOuttime;
            }
            if (!app($this->attendanceRepairLogRepository)->insertData($repairLog)) {
                return ['code' => ['0x044061', 'attendance']];
            }
        }
        if ($normalCount == count($signTimes)) {
            return ['code' => ['0x044060', 'attendance']];
        }
        return true;
    }

    /**
     * 加班外发
     * @param $data
     * @param bool $stat 是否仅仅是用来计算加班天数的
     * @return array|bool
     */
    public function overtime($data, $stat = false)
    {
        if (!$this->hasAll($data, ['user_id', 'overtime_start_time', 'overtime_end_time'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        
        //处理下批量加班的情况
        $mulitResult = $this->mulitSendData($data, function($handleData){
            return $this->overtime($handleData);
        });
        if ($mulitResult) {
            return $mulitResult;
        }
        
        $userId = $data['user_id'];
        if (!$this->userExists($userId)){
            return ['code' => ['0x044110', 'attendance']];
        }
        if ($data['overtime_start_time'] == $data['overtime_end_time']) {
            return ['code' => ['0x044113', 'attendance']];
        }
        if ($data['overtime_start_time'] > $data['overtime_end_time']) {
            return ['code' => ['0x044114', 'attendance']];
        }
        //加班补偿可能员工自选
        if (isset($data['overtime_to']) && in_array($data['overtime_to'], [1, 2])) {
            $customTo = $data['overtime_to'];
        } else {
            $customTo = 1;//自选没选默认为1
        }
        //加班配置
        $config = $this->getStaticOvertimeConfig($userId);
        self::$overtime['config'] = $config;
        //不能通过流程申请加班
        if (empty($config['method']) || !in_array($config['method'], [1, 2, 4])) {
            return ['code' => ['overtime_method_forbidden', 'attendance']];
        }
        list($startTime, $endTime) = $this->dateRangeToDatetimeRange($data['overtime_start_time'], $data['overtime_end_time']);
        $shiftTimes = $this->getShiftTimesGroupByDate($startTime, $endTime, $userId);
        $holidays = $this->getHolidayGroupByDate($startTime, $endTime, $userId);
        //后面方法里面会用到的班次信息
        self::$overtime['shiftTimes'] = $shiftTimes;
        self::$overtime['holidays'] = $holidays;
        //判断是否开启对应的加班
        if ($this->isForbidden($startTime, $endTime)) {
            return ['code' => ['not_allow_overtime', 'attendance']];
        }
        switch ($config['method']) {
            //需审批，以申请时间为准
            case 1:
                list($totalStat, $addStat, $detail, $oldRecords) = $this->overtimeByApply($startTime, $endTime, $userId);
                break;
            //需审批，以申请时间为准，但排除排班时间
            case 2:
                list($totalStat, $addStat, $detail, $oldRecords) = $this->overtimeByApplyAndScheduling($startTime, $endTime, $userId);
                break;
            //需审批，以打卡记录为准，但不超过申请时长
            case 4:
                list($totalStat, $addStat, $detail, $oldRecords) = $this->overtimeByApplyAndRecord($startTime, $endTime, $userId);
                break;
            default:
                return ['code' => ['overtime_method_forbidden', 'attendance']];
        }
        //如果仅仅是用来计算天数的后面的不用执行了
        if ($stat) {
            return $totalStat;
        }
        //用户自己传了天数或者小时
        if ($customOvertime = $this->getCustomOvertimeStat($data)) {
            $totalStat = $addStat = $customOvertime;
            $detail = array();
            $date = $this->format($data['overtime_start_time'], 'Y-m-d');
            $detail[] = [
                'date' => $date,
                'times' => [
                    [$data['overtime_start_time'], $data['overtime_end_time']]
                ],
                'days' => $customOvertime[$date][0],
                'hours' => $customOvertime[$date][1]
            ];
        }
        $log = $this->getOvertimeLogs($data, $detail, $oldRecords, $addStat);//生成加班的详细日志
        //本次新增的加班天数，要插入汇总表的记录
        $checkVacation = [];
        $defaultVacationId = app($this->vacationRepository)->getVacationId('调休假');
        $statData = $this->handleStatData($addStat, $userId, 'overtime', function ($date) use ($customTo, $userId, $defaultVacationId, &$checkVacation) {
            $key = $this->whatDay($date);
            $to = self::$overtime['config']->{$key . '_to'};
            //员工自选
            if ($to == 3) {
//                $existTo = app($this->attendanceOvertimeStatRepository)->getOvertimeTo($date, $userId);
//                $to = $existTo ? $existTo : $customTo;
                $to = $customTo;
            }
            $ratio = self::$overtime['config']->{$key . '_ratio'};
            //加班补偿转假期余额，转什么假期
            $vacationId = null;
            if ($to == 1) {
                $vacationId = self::$overtime['config']->{$key . '_vacation'} ?: $defaultVacationId;
            }
            //需要转余额的假期类型
            if ($vacationId) {
                $checkVacation[] = $vacationId;
            }
            return ['to' => $to, 'ratio' => $ratio, 'vacation' => $vacationId];
        });
        if ($checkVacation) {
            $checkVacation = array_unique($checkVacation);
            $ownVacationIds = $this->getVacationIdsByUserId($userId);
            if (array_intersect($checkVacation, $ownVacationIds) != $checkVacation) {
                //有些假期没有不在范围内
                return ['code' => ['0x044091', 'attendance']];
            }
        }
        $result = $this->handleOutSend($data, $this->attendanceOvertimeRepository, $this->attendanceOvertimeStatRepository, 'overtime', function () use ($statData, $totalStat) {
            //加班记录表的天数计算不排除历史记录，汇总表更新排除历史申请记录,修改天数存在加班记录表中
            $statData[0] = $this->getStatTotal($totalStat, 0, $this->dayPrecision);
            $statData[1] = $this->getStatTotal($totalStat, 1, $this->hourPrecision);

            return $statData;
        });
        if (!$result) {
            return ['code' => ['0x044051', 'attendance']];
        }
        $vacationDays = $this->getAddVacation($statData[2]);
        $vacationHours = $this->getAddVacation($statData[2], true);
        if ($vacationDays) {
            foreach ($vacationDays as $vacationId => $days) {
                $data = [
                    'user_id' => $userId,
                    'days' => $days,
                    'hours' => $vacationHours[$vacationId],
                    'vacation_id' => $vacationId
                ];
                app($this->vacationService)->overtimeDataout($data);
            }
        }
        return $this->addOvertimeLog($log);
    }

    /**
     * 修复加班数据脚本专用
     * @author yangxingqiang
     * @param $data
     * @return array|bool
     */
    public function updateOvertimeData($data)
    {
        if (!$this->hasAll($data, ['user_id', 'overtime_start_time', 'overtime_end_time'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        $userId = $data['user_id'];
        //加班补偿可能员工自选
        if (isset($data['overtime_to']) && in_array($data['overtime_to'], [1, 2])) {
            $customTo = $data['overtime_to'];
        } else {
            $customTo = 1;//自选没选默认为1
        }
        //加班配置
        $config = $this->getStaticOvertimeConfig($userId);
        self::$overtime['config'] = $config;
        list($startTime, $endTime) = $this->dateRangeToDatetimeRange($data['overtime_start_time'], $data['overtime_end_time']);
        $shiftTimes = $this->getShiftTimesGroupByDate($startTime, $endTime, $userId);
        $holidays = $this->getHolidayGroupByDate($startTime, $endTime, $userId);
        //后面方法里面会用到的班次信息
        self::$overtime['shiftTimes'] = $shiftTimes;
        self::$overtime['holidays'] = $holidays;
        switch ($config['method']) {
            //需审批，以申请时间为准
            case 1:
                list($totalStat, $addStat, $detail, $oldRecords) = $this->overtimeByApply($startTime, $endTime, $userId);
                break;
            //需审批，以申请时间为准，但排除排班时间
            case 2:
                list($totalStat, $addStat, $detail, $oldRecords) = $this->overtimeByApplyAndScheduling($startTime, $endTime, $userId);
                break;
            //需审批，以打卡记录为准，但不超过申请时长
            case 4:
                list($totalStat, $addStat, $detail, $oldRecords) = $this->overtimeByApplyAndRecord($startTime, $endTime, $userId);
                break;
            default:
                return ['code' => ['overtime_method_forbidden', 'attendance']];
        }
        //用户自己传了天数或者小时
        if ($customOvertime = $this->getCustomOvertimeStat($data)) {
            $totalStat = $addStat = $customOvertime;
            $detail = array();
            $date = $this->format($data['overtime_start_time'], 'Y-m-d');
            $detail[] = [
                'date' => $date,
                'times' => [
                    [$data['overtime_start_time'], $data['overtime_end_time']]
                ],
                'days' => $customOvertime[$date][0],
                'hours' => $customOvertime[$date][1]
            ];
        }
        $log = $this->getOvertimeLogs($data, $detail, $oldRecords, $addStat);//生成加班的详细日志
        //本次新增的加班天数，要插入汇总表的记录
        $checkVacation = [];
        $defaultVacationId = app($this->vacationRepository)->getVacationId('调休假');
        $statData = $this->handleStatData($addStat, $userId, 'overtime', function ($date) use ($customTo, $userId, $defaultVacationId, &$checkVacation) {
            $key = $this->whatDay($date);
            $to = self::$overtime['config']->{$key . '_to'};
            //员工自选
            if ($to == 3) {
//                $existTo = app($this->attendanceOvertimeStatRepository)->getOvertimeTo($date, $userId);
//                $to = $existTo ? $existTo : $customTo;
                $to = $customTo;
            }
            $ratio = self::$overtime['config']->{$key . '_ratio'};
            //加班补偿转假期余额，转什么假期
            $vacationId = null;
            if ($to == 1) {
                $vacationId = self::$overtime['config']->{$key . '_vacation'} ?: $defaultVacationId;
            }
            //需要转余额的假期类型
            if ($vacationId) {
                $checkVacation[] = $vacationId;
            }
            return ['to' => $to, 'ratio' => $ratio, 'vacation' => $vacationId];
        });

        $result = $this->handleUpdateOutSend($data, $this->attendanceOvertimeRepository, $this->attendanceOvertimeStatRepository, 'overtime', function () use ($statData, $totalStat) {
            //加班记录表的天数计算不排除历史记录，汇总表更新排除历史申请记录,修改天数存在加班记录表中
            $statData[0] = $this->getStatTotal($totalStat, 0, $this->dayPrecision);
            $statData[1] = $this->getStatTotal($totalStat, 1, $this->hourPrecision);
            return $statData;
        });
        if (!$result) {
            return ['code' => ['0x044051', 'attendance']];
        }
        $vacationDays = $this->getAddVacation($statData[2]);
        $vacationHours = $this->getAddVacation($statData[2], true);
        if ($vacationDays) {
            foreach ($vacationDays as $vacationId => $days) {
                $data = [
                    'user_id' => $userId,
                    'days' => $days,
                    'hours' => $vacationHours[$vacationId],
                    'vacation_id' => $vacationId
                ];
                app($this->vacationService)->overtimeDataout($data);
            }
        }
        return $this->addOvertimeLog($log);
    }

    private function getVacationIdsByUserId($userId)
    {
        return app($this->vacationService)->getUserHasVacationIds($userId);
    }
    /**
     * 批量加班处理下
     * @param $data
     * @return array|bool
     */
    private function mulitSendData($data, $handle)
    {
        $userId = $data['user_id'];
        //这段代码如果是多个用户加班递归处理下
        if (is_array($userId) || count(explode(',', $userId)) > 1) {
            $userIds = is_array($userId) ? $userId : explode(',', $userId);
            //先判断下这批人是不是在同一个班组里面
            $schedulingIds = $this->schedulingIdMapWithUserIds($userIds);
            if (!$schedulingIds) {
                //加班人员没有班组
                return ['code' => ['0x044071', 'attendance']];
            }
            $schedulingId = current($schedulingIds);
            foreach ($userIds as $userId) {
                if ($schedulingId != $schedulingIds[$userId]) {
                    return ['code' => ['0x044072', 'attendance']];
                }
            }
            $errors = [];
            foreach ($userIds as $uid) {
                $data['user_id'] = $uid;
                $result = $handle($data);
                if (isset($result['code'])) {
                    $code = $result['code'];
                    $errors[$code[1] . '.' . $code[0]][] = $uid;
                }
            }
            if ($errors) {
                $msg = '';
                foreach ($errors as $errorTrans => $uids) {
                    $users = app($this->userRepository)->getUserNames($uids)->toArray();
                    $userNames = implode(',', array_column($users, 'user_name'));
                    if ($userNames) {
                        $msg .= $userNames . trans($errorTrans);
                    }
                }
                return ['code' => ['0x044070', 'attendance'], 'dynamic' => $msg];
            }
            return true;
        }
        return false;
    }

    /**
     * 判断时间段内加班是否允许的
     */
    private function isForbidden($startTime, $endTime)
    {
        $isForbidden = true;
        $starDate = $this->format($startTime, 'Y-m-d');
        $endDate = $this->format($endTime, 'Y-m-d');
        $dateRange = $this->getDateFromRange($starDate, $endDate);
        foreach ($dateRange as $date) {
            $key = $this->whatDay($date);
            $open = self::$overtime['config']->{$key . '_open'} ?? false;
            if ($open) {
                $isForbidden = false;
                break;
            }
        }
        return $isForbidden;
    }

    private function getOvertimeLogs($data, $detail, $oldRecords, $addStat)
    {
        $log = array();
        if ($oldRecords) {
            $historyOvertime = implode(',', array_column($oldRecords, 'overtime_id'));
        } else {
            $historyOvertime = null;
        }
        $days = $this->getStatTotal($addStat, 0, $this->dayPrecision);
        $hours = $this->getStatTotal($addStat, 1, $this->hourPrecision);
        $config = self::$overtime['config']->toArray();
        list($startTime, $endTime) = $this->dateRangeToDatetimeRange($data['overtime_start_time'], $data['overtime_end_time']);
        $log['user_id'] = $data['user_id'];
        $log['overtime_start_time'] = $startTime;
        $log['overtime_end_time'] = $endTime;
        $log['approver_id'] = $data['current_user_id'] ?? '';
        $log['from'] = 1;//加班日志来源，1流程，2打卡
        $log['days'] = $days;
        $log['hours'] = $hours;
        $log['run_id'] = $data['run_id'] ?? null;
        $log['overtime_config'] = json_encode($config);//记录下当时的加班配置
        $log['overtime_flow'] = $this->getExtraInfoFromFlow($data, ['run_id', 'flow_id', 'run_name', 'overtime_reason']);//本次加班相关流程
        $log['history_overtime'] = $historyOvertime;//该时间历史加班流程
        $log['detail'] = $detail;
        return $log;
    }

    /**
     * 用户自定义加班天数的计算
     * 只有同一天，且用户传入了加班天数和小时才用用户的天数和小时计算
     */
    private function getCustomOvertimeStat($data)
    {
        $startDate = $this->format($data['overtime_start_time'], 'Y-m-d');
//        $endDate = $this->format($data['overtime_end_time'], 'Y-m-d');
//        if ($startDate != $endDate) {
//            return false;
//        }
        if ((!isset($data['overtime_days']) || !$data['overtime_days']) && (!isset($data['overtime_hours']) || !$data['overtime_hours'])) {
            return false;
        }
        $key = $this->whatDay($startDate);
        $config = self::$overtime['config'];
        $shiftTimes = self::$overtime['shiftTimes'];
        $attendTime = $key == 'work' ? $shiftTimes[$startDate]->attend_time : $config->{$key . '_convert'} * 3600;
        if(isset($data['overtime_days'])){
            $days = $this->roundDays($data['overtime_days']);
            if(isset($data['overtime_hours'])){
                $hours = $this->roundHours($data['overtime_hours']);
            }else{
                $hours = $this->roundHours($days * $this->calcRealHours($attendTime));
            }
        }else{
            if(isset($data['overtime_hours'])){
                $hours = $this->roundHours($data['overtime_hours']);
                if($this->calcRealHours($attendTime)){
                    $days = $this->roundDays($hours / $this->calcRealHours($attendTime));
                }else{
                    $days = 0;
                }
            }else{
                return false;
            }
        }
        $stat[$startDate] = [$days, $hours];
        return $stat;
    }

    /**
     * 判断某天是工作日，休息日，还是节假日
     */
    private function whatDay($date)
    {
        $shiftTimes = self::$overtime['shiftTimes'];
        $holidays = self::$overtime['holidays'];
        if (isset($shiftTimes[$date])) {
            //工作日
            $type = 'work';
        } elseif (isset($holidays[$date])) {
            //节假日
            $type = 'holiday';
        } else {
            //休息日
            $type = 'rest';
        }
        return $type;
    }

    /**
     * 根据统计的汇总数据获取增加的假期余额
     * @param $stats
     * @return array
     */
    private function getAddVacation($stats, $hours = false)
    {
        $vacationDays = array();
        if ($stats) {
            foreach ($stats as $stat) {
                //转假期余额的
                if ($stat['to'] == 1 && $stat['vacation']) {
                    $vacationId = $stat['vacation'];
                    $ratio = $stat['ratio'];
                    if (isset($vacationDays[$vacationId])) {
                        $vacationDays[$vacationId] += ($hours ? $stat['overtime_hours'] : $stat['overtime_days']) * $ratio;
                    } else {
                        $vacationDays[$vacationId] = ($hours ? $stat['overtime_hours'] : $stat['overtime_days']) * $ratio;
                    }
                }
            }
        }
        return $vacationDays;
    }

    /**
     * 以申请为主,直接计算时间
     * @param $startTime
     * @param $endTime
     * @param $userId
     */
    private function overtimeByApply($startTime, $endTime, $userId)
    {
        $records = app($this->attendanceOvertimeRepository)->getAttendOvertime($startTime, $endTime, $userId)->toArray();
        list($totalStat, $detail) = $this->getOvertimeDateStat([[$startTime, $endTime]]);
        if ($records) {
            $addtimes = $this->getIncrementTime($records, $startTime, $endTime);
            list($addStat, $detail) = $this->getOvertimeDateStat($addtimes);
        } else {
            $addStat = $totalStat;
        }
        return [$totalStat, $addStat, $detail, $records];
    }

    /**
     * 以申为主，排除排班时间
     * @param $startTime
     * @param $endTime
     * @param $userId
     */
    private function overtimeByApplyAndScheduling($startTime, $endTime, $userId)
    {
        $records = app($this->attendanceOvertimeRepository)->getAttendOvertime($startTime, $endTime, $userId)->toArray();
        $shiftTimes = self::$overtime['shiftTimes'];
        $overtimeSchedulingTime = $this->getOvertimeSchedulingTime($shiftTimes); // 获取排班时间段
        $restDiffTime = $this->getRestAndHolidayDiffTime($startTime, $endTime); // 获取周末或节假日休息时间段
        $invalidTimes = array_merge($overtimeSchedulingTime, $restDiffTime);
        $totalTimes = [[$startTime, $endTime]];
        $times = $this->getDateTimeRemoveInvalid($totalTimes, $invalidTimes);
        
        list($totalStat, $detail) = $this->getOvertimeDateStat($times);
        //排除历史申请记录
        if ($records) {
            $addtimes = $this->getIncrementTime($records, $startTime, $endTime);
            $times = $this->getDateTimeRemoveInvalid($addtimes, $overtimeSchedulingTime);
            list($addStat, $detail) = $this->getOvertimeDateStat($times);
        } else {
            $addStat = $totalStat;
        }
        return [$totalStat, $addStat, $detail, $records];
    }
    private function getRestAndHolidayDiffTime($startTime, $endTime)
    {
        $dates = $this->getDateFromRange($startTime, $endTime);
        $restTimes = [];
        foreach ($dates as $date) {
            $dateType = $this->whatDay($date);
            if ($dateType === 'work') {
                continue;
            }
            if ($dateType === 'holiday') {
                $isDiff = self::$overtime['config']->holiday_diff;
                $diffTime = self::$overtime['config']->holiday_diff_time;
            } else if ($dateType === 'rest') {
                $isDiff = self::$overtime['config']->rest_diff;
                $diffTime = self::$overtime['config']->rest_diff_time;
            }
            if ($isDiff) {
                $diffTimes = $diffTime ? json_decode($diffTime, true) : [];
                if (!empty($diffTimes)) {
                    foreach ($diffTimes as $item) {
                        $begin = $this->combineDatetime($date, $this->getFullTime($item['begin'], false));
                        $end = $this->combineDatetime($date, $this->getFullTime($item['end'], false));
                        $restTimes[] = [$begin , $end];
                    }
                }
            }
        }
        return $restTimes;
    }
    /**
     * 需审批，以打开时间为准，但不超过申请时长
     * @param $startTime
     * @param $endTime
     * @param $userId
     */
    private function overtimeByApplyAndRecord($startTime, $endTime, $userId)
    {
        $records = app($this->attendanceOvertimeRepository)->getAttendOvertime($startTime, $endTime, $userId)->toArray();
        $recordTimes = $this->getEffectiveOvertimeByRecords($startTime, $endTime, $userId);
        $totalTimes = [[$startTime, $endTime]];
        $times = $this->getMultIntersetTimes($totalTimes, $recordTimes);
        list($totalStat, $detail) = $this->getOvertimeDateStat($times);
        //排除历史申请
        if ($records) {
            $addTimes = $this->getIncrementTime($records, $startTime, $endTime);
            $times = $this->getMultIntersetTimes($addTimes, $recordTimes);
            list($addStat, $detail) = $this->getOvertimeDateStat($times);
        } else {
            $addStat = $totalStat;
        }
        return [$totalStat, $addStat, $detail, $records];
    }

    private function getOvertimeDateStat($times)
    {
        $stat = array();
        $detail = array();
        if (!$times) {
            return [$stat, []];
        }
        $dateTimes = $this->splitTime($times, true);
        foreach ($dateTimes as $date => $times) {
            $seconds = 0;
            foreach ($times as $time) {
                $seconds += strtotime($time[1]) - strtotime($time[0]);
            }
            $daysHours = $this->getOvetimeDaysHours($seconds, $date);
            //加班是有效的
            if ($daysHours !== false) {
                $stat[$date] = $daysHours;
                $detail[] = [
                    'date' => $date,
                    'times' => $times,
                    'days' => $daysHours[0],
                    'hours' => $daysHours[1]
                ];
            }
        }
        return [$stat, $detail];
    }

    /**
     * 获取加班的排班时间
     * @param $shiftTimes
     */
    private function getOvertimeSchedulingTime($shiftTimes)
    {
        $res = array();
        if (!$shiftTimes) {
            return $res;
        }
        foreach ($shiftTimes as $date => $shiftTime) {
            $times = $shiftTime->times->toArray();
            foreach ($times as $time) {
                list($starTime, $endTime) = $this->getSignNormalDatetime($date, $time['sign_in_time'], $time['sign_out_time']);
                //下班后多久开始算加班, 排班的结束时间
                $overtimeBeginTime = $this->getOvertimeBeginTime($endTime, self::$overtime['config']);
                $res[] = [$starTime, $overtimeBeginTime];
            }
        }
        return $res;
    }

    private function getEffectiveOvertimeByRecords($startTime, $endTime, $userId)
    {
        $res = array();
        $startDate = $this->format($startTime, 'Y-m-d');
        $endDate = $this->format($endTime, 'Y-m-d');
        //查询这段时间的打卡记录用来取有效的加班时间，考虑跨天班的情况，记录需多取一天的
        $params = ['sign_date' => [[$this->getPrvDate($startDate), $endDate], 'between'], 'user_id' => [$userId], 'sign_out_time' => ['', '!=']];
        $orderBy = ['sign_date' => 'asc', 'sign_times' => 'asc'];
        $records = app($this->attendanceRecordsRepository)->getRecords($params, ['*'], $orderBy)->toArray();
        if (!$records) {
            return $res;
        }
        //根据签退时间取有效的加班时间
        $config = self::$overtime['config'];
        foreach ($records as $record) {
            $signInTime = $record['original_sign_in_time'];
            $signOutTime = $record['original_sign_out_time'];
            $signDate = $record['sign_date'];
            if ($record['shift_id'] == 0) {
                //休息日
                $effectTimes = $this->getOvertimeEffectTimes($signDate, $signInTime, $signOutTime, $config, $this->whatDay($signDate));
                $res = array_merge($res, $effectTimes);
            } else {
                //工作日
                list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $record['sign_in_normal'], $record['sign_out_normal']);
                //下班后多久算加班
                $overtimeBeginTime = $this->getOvertimeBeginTime($signOutNormal, $config);
                //特殊情况，下班后签到加班
                $overtimeBeginTime = max($signInTime, $overtimeBeginTime);
                if ($signOutTime > $overtimeBeginTime) {
                    $res[] = [$overtimeBeginTime, $signOutTime];
                }
            }
        }
        return $res;
    }
    /**
     * 获取本次加班申请的增量时间，如果重复的时间与之前的重复了，取出非重复的部分
     * @param $records
     * @param $startTime
     * @param $endTime
     */
    private function getIncrementTime($records, $startTime, $endTime)
    {
        $res = array();
        if (!$records) {
            $res[] = [$startTime, $endTime];
            return $res;
        }
        $range = array();
        foreach ($records as $record) {
            $range[] = [$record['overtime_start_time'], $record['overtime_end_time']];
        }
        $range = $this->combineDatetimeRange($range);
        return $this->getSortDatetimeDiff($range, $startTime, $endTime);
    }

    /**
     *根据秒数和出勤时间计算出加班天数和小时
     * @param $seconds
     * @param $date
     */
    private function getOvetimeDaysHours($seconds, $date)
    {
        $shiftTimes = self::$overtime['shiftTimes'];
        $key = $this->whatDay($date);
        $config = self::$overtime['config'];
        //未开启加班，有时间段无效
        if (!$config->{$key . '_open'}) {
            return false;
        }
        $attendTime = isset($shiftTimes[$date]) ? $shiftTimes[$date]->attend_time : $config->{$key . '_convert'} * 3600;
        //最小有效加班时间
        $min = $config->{$key . '_min_time'} * 60;
        if ($seconds < $min) {
            return false;
        }
        $hours = 0;
        $days = 0;
        //加班最小单位
        if ($config->{$key . '_min_unit'}) {
            switch ($config->{$key . '_min_unit'}){
                case 1:  //无
                    $hours = $this->calcHours($seconds);
                    $days = $this->calcDay($seconds, $attendTime);
                    break;
                case 2: //半小时
                    $hours = $this->floor($this->calcHours($seconds), 0.5);
                    $days = $this->calcDayByHours($hours, $attendTime/3600);
                    break;
                case 3: //一小时
                    $hours = $this->floor($this->calcHours($seconds), 1);
                    $days = $this->calcDayByHours($hours, $attendTime/3600);
                    break;
                case 4: //半天
                    $hours = $this->floor($this->calcHours($seconds), $attendTime/3600/2);
                    $days = $this->calcDayByHours($hours, $attendTime/3600);
                    break;
                default:
                    $hours = $this->calcHours($seconds);
                    $days = $this->calcDay($seconds, $attendTime);
            }
        }
        //限制天数不超过一天
        if ($config->{$key . '_limit_days'} && $days > 1) {
            $days = 1;
        }

        return [$days, $hours];
    }

    private function handleOutSend($data, $recordRepository, $statRepository, $type, $callback = false)
    {
        $data[$type . '_extra'] = $this->getExtraInfoFromFlow($data);
        list($startKey, $endKey) = $this->outSendTimeKeys[$type];
        list($startTime, $endTime) = $this->dateRangeToDatetimeRange($data[$startKey], $data[$endKey]);
        if ($callback) {
            list($days, $hours, $statRecords) = $callback($startTime, $endTime);
        } else {
            $effectDays = $this->parseEffectTime($startTime, $endTime, $data['user_id']);
            list($days, $hours, $statRecords) = $this->handleStatData($effectDays, $data['user_id'], $type);
        }
        $data[$type . '_days'] = $days;
        $data[$type . '_hours'] = $hours;
        $data[$startKey] = $startTime;
        $data[$endKey] = $endTime;
        if (!$result = app($recordRepository)->insertData($data)) {
            return false;
        }
        if (empty($statRecords)) {
            return true;
        }
        //更新外发汇总表
        foreach ($statRecords as $record) {
            $wheres = [
                'date' => [$record['date']],
                'user_id' => [$data['user_id']]
            ];
            if ($type == 'leave') {
                $wheres['vacation_id'] = [$data['vacation_id']];
                $record['vacation_id'] = $data['vacation_id'];
            }
            if ($type == 'overtime') {
                $wheres['to'] = [$record['to']];
            }
            $result = app($statRepository)->getOneRecord($wheres);
            if (!$result) {
                app($statRepository)->insertData($record);
            } else {
                $dayKey = $type . '_days';
                $hourKey = $type . '_hours';
                app($statRepository)->updateData([$dayKey => $result->{$dayKey} + $record[$dayKey], $hourKey => $result->{$hourKey} + $record[$hourKey]], $wheres);
            }
        }
        //返回统计的具体信息，外面可能会用到
        return [$days, $hours, $statRecords];
    }

    /**
     * 修复加班数据脚本专用
     * @author yangxingqiang
     * @param $data
     * @param $recordRepository
     * @param $statRepository
     * @param $type
     * @param bool $callback
     * @return array|bool
     */
    private function handleUpdateOutSend($data, $recordRepository, $statRepository, $type, $callback = false)
    {
        $data[$type . '_extra'] = $this->getExtraInfoFromFlow($data);
        list($startKey, $endKey) = $this->outSendTimeKeys[$type];
        list($startTime, $endTime) = $this->dateRangeToDatetimeRange($data[$startKey], $data[$endKey]);
        if ($callback) {
            list($days, $hours, $statRecords) = $callback($startTime, $endTime);
        } else {
            $effectDays = $this->parseEffectTime($startTime, $endTime, $data['user_id']);
            list($days, $hours, $statRecords) = $this->handleStatData($effectDays, $data['user_id'], $type);
        }
        $data[$type . '_days'] = $days;
        $data[$type . '_hours'] = $hours;
        $data[$startKey] = $startTime;
        $data[$endKey] = $endTime;
        if (empty($statRecords)) {
            return true;
        }
        //更新外发汇总表
        foreach ($statRecords as $record) {
            $wheres = [
                'date' => [$record['date']],
                'user_id' => [$data['user_id']]
            ];
            if ($type == 'leave') {
                $wheres['vacation_id'] = [$data['vacation_id']];
                $record['vacation_id'] = $data['vacation_id'];
            }
            if ($type == 'overtime') {
                $wheres['to'] = [$record['to']];
            }
            $result = app($statRepository)->getOneRecord($wheres);
            if (!$result) {
                app($statRepository)->insertData($record);
            } else {
                $dayKey = $type . '_days';
                $hourKey = $type . '_hours';
                app($statRepository)->updateData([$dayKey => $result->{$dayKey} + $record[$dayKey], $hourKey => $result->{$hourKey} + $record[$hourKey]], $wheres);
            }
        }
        //返回统计的具体信息，外面可能会用到
        return [$days, $hours, $statRecords];
    }

    /**
     * 获取外发额外字段
     * @param $data
     * @param array $extraFields
     * @return false|string
     */
    private function getExtraInfoFromFlow($data, $extraFields = ['run_id', 'flow_id', 'run_name'])
    {
        $extra = [];
        foreach ($extraFields as $field) {
            $extra[$field] = $this->defaultValue($field, $data);
        }
        return json_encode($extra);
    }
    
    /**
     * 解析天数，与排班时间取交集
     * @author yangxingqiang
     * @param $startTime
     * @param $endTime
     * @param $userId
     * @param array $data
     * @return array
     */
    public function parseEffectTime($startTime, $endTime, $userId, $data = [])
    {
        $startDate = $this->format($startTime, 'Y-m-d');
        $endDate = $this->format($endTime, 'Y-m-d');
        //开始日期要多取一天，有可能时间属于在前一天的排班时间内，比如跨天班
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($this->getPrvDate($startDate), $endDate, [$userId])[$userId] ?? [];
        $res = array();
        //用户自己传了请假天数或者小时
        if (isset($data['leave_days']) || isset($data['leave_hours'])) {
            $key = 0;
            foreach ($schedulings as $scheduling) {
                $shiftId = $scheduling['shift_id'];
                $date = $scheduling['scheduling_date'];
                $shift = $this->getShiftById($shiftId);
                $shiftTimes = $this->getShiftTimeById($shiftId);
                $effectTimes = $this->getEffectShiftTimes($shiftTimes, $shift, $date);
                $count = count($effectTimes);
                $firstTime = $effectTimes[0][0];
                $lastTime = $effectTimes[$count - 1][1];
                // 如果请假的时间包含排班时间，则请假时长用排班时长，无需取交集。
                if ($startTime <= $firstTime && $endTime >= $lastTime) {
                    $seconds = $shift->attend_time;
                } else {
                    $seconds = $this->oneMoreDatetimeIntersetTime($startTime, $endTime, $effectTimes);
                }
                if ($seconds > 0) {
                    if($key == 0){
                        if(isset($data['leave_days'])){
                            $days = $this->roundDays($data['leave_days']);
                            if(isset($data['leave_hours'])){
                                $hours = $this->roundHours($data['leave_hours']);
                            }else{
                                $hours = $this->roundHours($days * $this->calcRealHours($shift->attend_time));
                            }
                        }else{
                            if(isset($data['leave_hours'])){
                                $hours = $this->roundHours($data['leave_hours']);
                                if($this->calcRealHours($shift->attend_time)){
                                    $days = $this->roundDays($hours / $this->calcRealHours($shift->attend_time));
                                }else{
                                    $days = 0;
                                }
                            }else{
                                $days = 0;
                                $hours = 0;
                            }
                        }
                        $key++;
                    }else{
                        $days = 0;
                        $hours = 0;
                    }
                    $res[$date] = [
                        $days,
                        $hours,
                        $this->calcRealHours($shift->attend_time)
                    ];
                }
            }
        }else{
            foreach ($schedulings as $scheduling) {
                $shiftId = $scheduling['shift_id'];
                $date = $scheduling['scheduling_date'];
                $shift = $this->getShiftById($shiftId);
                $shiftTimes = $this->getShiftTimeById($shiftId);
                $effectTimes = $this->getEffectShiftTimes($shiftTimes, $shift, $date);
                $count = count($effectTimes);
                $firstTime = $effectTimes[0][0];
                $lastTime = $effectTimes[$count - 1][1];
                // 如果请假的时间包含排班时间，则请假时长用排班时长，无需取交集。
                if ($startTime <= $firstTime && $endTime >= $lastTime) {
                    $seconds = $shift->attend_time;
                } else {
                    $seconds = $this->oneMoreDatetimeIntersetTime($startTime, $endTime, $effectTimes);
                }
                if ($seconds > 0) {
                    $res[$date] = [
                        $this->calcDay($seconds ,$shift->attend_time),
                        $this->calcHours($seconds),
                        $this->calcRealHours($shift->attend_time)
                    ];
                }
            }
        }
        return $res;
    }

    /**
     * 计算总天数以及按user_id,year,month,day区分的数组
     * @param $stats
     * @param $userId
     * @param string $type
     * @return array
     */
    private function handleStatData($stats, $userId, $type, $extra = [])
    {
        if (empty($stats)) {
            return [0, 0, []];
        }
        $days = 0;
        $hours = 0;
        $statArray = [];
        foreach ($stats as $date => $stat) {
            $days += $stat[0];
            $hours += $stat[1];
            list($year, $month, $day) = explode('-', $date);
            $item = [
                'year' => $year,
                'month' => intval($month),
                'day' => intval($day),
                'user_id' => $userId,
                'date' => $date
            ];
            $item[$type . '_days'] = $stat[0];
            $item[$type . '_hours'] = $stat[1];
            if ($extra) {
                if (is_callable($extra)) {
                    $item = array_merge($item, call_user_func($extra, $date));
                } else if (is_array($extra)) {
                    $item = array_merge($item, $extra);
                }
            }
            $statArray[] = $item;
        }

        return [$days, $hours, $statArray];
    }

    /**
     * 多个时间段上去除多个无效的时间段
     * @param $times
     * @param $invalids
     */
    private function getDateTimeRemoveInvalid($times, $invalids)
    {
        $res = array();
        if (!$times) {
            return $res;
        }
        if (!$invalids) {
            return $times;
        }
        foreach ($times as $time) {
            $res = array_merge($res, $this->getSortDatetimeDiff($invalids, $time[0], $time[1]));
        }
        return $res;
    }

    private function isDate($time)
    {
        return $this->format($time, 'Y-m-d') == $time;
    }

    /**
     * 按日期拆分出一段一段的时间
     * @param $startTime
     * @param $endTime
     * @param $key
     */
    private function splitTime($times, $dateKey = true)
    {
        $res = array();
        foreach ($times as $time) {
            $startTime = $time[0];
            $endTime = $time[1];
            $startDate = $this->format($startTime, 'Y-m-d');
            $endDate = $this->format($endTime, 'Y-m-d');
            $dates = $this->getDateFromRange($startDate, $endDate);
            //同一天
            if ($startDate == $endDate) {
                $time = [$startTime, $endTime];
                $dateKey ? $res[$startDate][] = $time : $res[] = $time;
                continue;
            }
            foreach ($dates as $date) {
                if ($date == $startDate) {
                    $newStartTime = $startTime;
                    $newEndTime = $date . ' 23:59:59';
                } else if ($date == $endDate) {
                    $newStartTime = $date . ' 00:00:00';
                    $newEndTime = $endTime;
                } else {
                    $newStartTime = $date . ' 00:00:00';
                    $newEndTime = $date . ' 23:59:59';
                }
                $time = [$newStartTime, $newEndTime];
                $dateKey ? $res[$date][] = $time : $res[] = $time;
            }
        }
        return $res;
    }

    /**
     * 获取总天数
     * @param $statss
     * @return float|int
     */
    private function getStatTotal($stats, $index, $precision)
    {
        return round(array_sum(array_column($stats, $index)), $precision);
    }

    /**
     * 验证流程外发
     * @param $data
     * @param $type
     * @return array|bool
     */
    public function outSendValidate($data, $type)
    {
        $data = $this->transFlowDataToModuleData($data, $type);
        //里面已经产生验证的结果了不需要继续验证了
        if (is_bool($data)) {
            return $data;
        }
        //用户id可能是个数组
        if (isset($data['user_id']) && is_array($data['user_id'])) {
            $data['user_id'] = $data['user_id'][0];
        }
        switch ($type) {
            case 'repair':
                $validate = $this->validateRepair($data);
                break;
            case 'leave':
                $validate = $this->validateLeave($data);
                break;
            case 'backLeave':
                $validate = $this->validateBackLeave($data);
                break;
            default:
                $validate = true;
        }
        if (isset($validate['code'])) {
            $userId = $data['user_id'] ?? '';
            $message = trans($validate['code'][1] . '.' . $validate['code'][0]);
            Log::error($userId . '-' . $type . '-' . $message);
            return false;
            //return trans($validate['code'][1] . '.' . $validate['code'][0]);
        }
        return true;
    }

    /**
     * 验证是否可以发起请求流程
     * 1.验证每月的请假天数是某超过设置的天数
     * 2.验证请假的开始时间已经超过设置的补交时间
     */
    public function validateLeave($data)
    {
        if (!$this->hasAll($data, ['leave_start_time', 'leave_end_time', 'user_id'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        $startTime = $this->format($data['leave_start_time']);
        $endTime = $this->format($data['leave_end_time']);
        $userId = $data['user_id'];
        $leaveDays = $this->parseEffectTime($startTime, $endTime, $userId);
        $rule = app($this->attendanceSettingService)->getCommonSetting('leave')->toArray();
        //验证请假的开始时间已经超过设置的补交时间
        $limitLeaveTime = $rule['limit_leave_time'] ?? false;
        if ($limitLeaveTime) {
            $startDate = $this->format($startTime, 'Y-m-d');
            $latestDate = date('Y-m-d', strtotime($startDate) + 24 * 3600 * $rule['leave_time']);
            if ($latestDate < $this->currentDate) {
                return ['code' => ['0x044054', 'attendance']];
            }
        }
        //验证每月的请假天数是某超过设置的天数
        $limitLeaveCount = $rule['limit_leave_count'] ?? false;
        if ($limitLeaveCount) {
            $months = $this->getMonthByDate($startTime, $endTime);
            foreach ($months as $yearMonth) {
                $thisLeaveDays = 0;
                $year = intval($yearMonth['year']);
                $month = intval($yearMonth['month']);
                $currntMonthDays = app($this->attendanceLeaveDiffStatRepository)->getUserLeaveDaysByMonth($year, $month, $userId);
                foreach ($leaveDays as $date => $daysHours) {
                    $leaveYear = intval(date('Y', strtotime($date)));
                    $leaveMonth = intval(date('m', strtotime($date)));
                    if ($year == $leaveYear && $month == $leaveMonth) {
                        $thisLeaveDays += $daysHours[0];
                    }
                }
                if ($thisLeaveDays + $currntMonthDays > $rule['leave_count']) {
                    return ['code' => ['0x044055', 'attendance']];
                }
            }
        }
        return true;
    }

    /**
     * 验证是否可以发起补卡流程
     */
    public function validateRepair($data)
    {
        if (!$this->hasAll($data, ['user_id'])) {
            return ['code' => ['0x044093', 'attendance']];
        }
        if (!$this->hasAll($data, ['repair_date'])) {
            return ['code' => ['0x044094', 'attendance']];
        }
        $date = $data['repair_date'];
        $userId = $data['user_id'];
        $rule = app($this->attendanceSettingService)->getCommonSetting('repair_sign')->toArray();
        //未开启补卡功能
        if (!$rule['allow_repair_sign']) {
            return ['code' => ['0x044062', 'attendance']];
        }
        //限制补卡次数
        if ($rule['limit_repair_sign_count']) {
            $count = $rule['repair_sign_count'];
            $startDate = date('Y-m-01', strtotime($date));
            $endDate = date('Y-m-t', strtotime($date));
            $wheres = [
                'user_id' => [$userId],
                'repair_date' => [[$startDate, $endDate], 'between']
            ];
            $total = app($this->attendanceRepairLogRepository)->getTotal(['search' => $wheres]);
            if ($total + 1 > $count) {
                return ['code' => ['0x044064', 'attendance']];
            }
        }
        //限制补卡时间
        if ($rule['limit_repair_sign_time']) {
            $time = $rule['repair_sign_time'];
            $latestDate = date('Y-m-d', strtotime($date) + 24 * 3600 * $time);
            if ($latestDate < $this->currentDate) {
                return ['code' => ['0x044063', 'attendance']];
            }
        }
        return true;
    }

    /**
     * 验证是否可以发起销假流程
     */
    public function validateBackLeave($data)
    {
        if (!$this->hasAll($data, ['leave_id', 'user_id'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        $leaveId = $data['leave_id'];
        $leave = app($this->attendanceLeaveRepository)->getDetail($leaveId);
        if (!$leave) {
            return ['code' => ['0x044065', 'attendance']];
        }
        $userId = $data['user_id'];
        $rule = app($this->attendanceSettingService)->getCommonSetting('back_leave')->toArray();
        //未开启销假功能
        if (!$rule['allow_back_leave']) {
            return ['code' => ['0x044066', 'attendance']];
        }
        //限制销假次数
        if ($rule['limit_back_leave_count']) {
            $count = $rule['back_leave_count'];
            $startTime = date('Y-m-01', strtotime($this->currentDate)) . ' 00:00:00';
            $endTime = date('Y-m-t', strtotime($this->currentDate)) . ' 23:59:59';
            $wheres = [
                'user_id' => [$userId],
                'created_at' => [[$startTime, $endTime], 'between']
            ];
            $total = app($this->attendanceBackLeaveLogRepository)->getTotal(['search' => $wheres]);
            if ($total + 1 > $count) {
                return ['code' => ['0x044067', 'attendance']];
            }
        }
        //限制销假时间
        if ($rule['limit_back_leave_time']) {
            $time = $rule['back_leave_time'];
            $leave = $leave->toArray();
            $date = date('Y-m-d', strtotime($leave['created_at']));
            $latestDate = date('Y-m-d', strtotime($date) + 24 * 3600 * $time);
            if ($latestDate < $this->currentDate) {
                return ['code' => ['0x044068', 'attendance']];
            }
        }
        return true;
    }


    /**
     * 将流程字段转换成外发匹配的模块字段
     * @param $data
     * @param $type
     * @return array|bool
     */
    private function transFlowDataToModuleData($data, $type)
    {
        // 流程数据验证时，会将日期时间类型的数据和_TEXT类型的数据特殊处理，所以另外传一份原始数据过来，考勤这里用原始数据作为判断依据
        if (isset($data['original_data'])) {
            $data = $data['original_data'];
        }
        //缺少验证的数据验证不通过
        if (!isset($data['flow_id'])) {
            return false;
        }
        //不知道类型验证不生效
        if (!$type) {
            return true;
        }
        $flowId = $data['flow_id'];
        $fieldMap = $this->transFlowFieldToModuleField($flowId, $type);
        if (is_bool($fieldMap)) {
            return $fieldMap;
        }
        if (!$fieldMap) {
            return $data;
        }
        foreach ($fieldMap as $flowField => $moduleField) {
            if (isset($data[$flowField])) {
                $data[$moduleField] = $data[$flowField];
            }
        }
        return $data;
    }

    /**
     * 将流程表单字段转换成模块外发对应的字段,如DATA_16=>user_id
     */
    private function transFlowFieldToModuleField($flowId, $type)
    {
        $flowOutsendId = DB::table('flow_process')->select('flow_outsend.id')
                ->join('flow_outsend', 'flow_process.node_id', '=', 'flow_outsend.node_id')
                ->where('flow_process.flow_id', $flowId)
                ->where('flow_process.flow_outsend_toggle', '=', 1)
                ->where('flow_outsend.custom_module_menu', $type)
                ->first()->id ?? null;
        //该流程没有配置对应的外发，就不用验证
        if (!$flowOutsendId) {
            return true;
        }
        $map = array();
        $data = DB::table('flow_outsend_fields')->select('*')
            ->where('flow_outsend_id', $flowOutsendId)->get()->toArray();
        if (!$data) {
            return $map;
        }
        foreach ($data as $value) {
            $porcessField = $value->porcess_fields;
            $receiveFields = $value->receive_fields;
            $map[$porcessField] = $receiveFields;
        }
        return $map;
    }

    /**
     * 解析请假天数
     *
     * @param type $startTime
     * @param type $endTime
     * @param type $userId
     * @return type
     */
    public function getLeaveOrOutDays($startTime, $endTime, $userId, $vacationId = null)
    {
        list($startTime, $endTime) = $this->dateRangeToDatetimeRange($startTime, $endTime);
        $effectDays = $this->parseEffectTime($startTime, $endTime, $userId);
        if (is_numeric($vacationId)) {
            $effectDays = app($this->vacationService)->parseLeaveDays($effectDays, $startTime, $endTime, $vacationId);
        }
        if (!$effectDays) {
            return ["days" => 0, "hours" => 0];
        }
        $days = $hours = 0;
        foreach ($effectDays as $item) {
            $days += $item[0];
            $hours += $item[1];
        }
        return ['days' => $this->roundDays($days), 'hours' => $this->roundHours($hours)];
    }

    /**
     * 获取加班天数
     * @param $startTime
     * @param $endTime
     * @param $userId
     * @return array
     */
    public function getOvertimeDays($startTime, $endTime, $userId)
    {
        list($startTime, $endTime) = $this->dateRangeToDatetimeRange($startTime, $endTime);
        $data = [
            'user_id' => $userId,
            'overtime_start_time' => $startTime,
            'overtime_end_time' => $endTime,
        ];
        $effectDays = $this->overtime($data, true);
        if (isset($effectDays['code']) || !$effectDays) {
            return ["days" => 0, "hours" => 0];
        }
        $days = $hours = 0;
        foreach ($effectDays as $item) {
            $days += $item[0];
            $hours += $item[1];
        }
        return ['days' => $this->roundDays($days), 'hours' => $this->roundHours($hours)];
    }

    /**
     * 获取某个用户某天的打卡时间段
     * @param $userId
     * @param $signDate
     * @return array
     */
    public function getUserShiftTime($data)
    {
        if (!$this->hasAll($data, ['user_id', 'sign_date'])) {
            return [];
        }
        $userId = $data['user_id'];
        $signDate = $data['sign_date'];
        $scheduling = $this->schedulingMapWithUserIdsByDate($signDate, [$userId])[$userId] ?? null;
        if (!$scheduling) {
            return [];
        }
        $shiftId = $scheduling['shift_id'];
        $shiftTimes = $this->getShiftTimeById($shiftId)->toArray();
        $res = array();
        if (!$shiftTimes) {
            return [];
        }
        foreach ($shiftTimes as $index => $time) {
            $res[] = [
                'sign_times' => $index + 1,
                'time' => $time['sign_in_time'] . '~' . $time['sign_out_time']
            ];
        }
        return $res;
    }

    /**
     * 获取某个用户的请假，表单系统数据源
     * @param $data
     */
    public function getUserLeaveRecords($data)
    {
        if (!$this->hasAll($data, ['user_id'])) {
            return [];
        }
        $userId = $data['user_id'];
        $rule = app($this->attendanceSettingService)->getCommonSetting('back_leave')->toArray();
        //未开启销假功能
        if (!$rule['allow_back_leave']) {
            return [];
        }
        //限制销假时间
        if ($rule['limit_back_leave_time']) {
            $time = $rule['back_leave_time'];
            $startTime = date('Y-m-d', time() - 24 * 3600 * $time) . ' 00:00:00';
            if (isset($data['search']) && $data['search'] && is_string($data['search'])) {
                $data['search'] = json_decode($data['search'], true);
            }
            $data['search']['created_at'] = [$startTime, '>'];
        }
        $records = app($this->attendanceRecordsService)->getMyLeaveRecords($data, $userId);
        return $records['list'];
    }

    /**
     * 获取请假记录的详情，表单系统数据源
     * @param $leaveId
     */
    public function getLeaveRecordsDetail($leaveId)
    {
        $record = app($this->attendanceLeaveRepository)->getDetail($leaveId);
        if (!$record) {
            return [];
        }
        $extra = json_decode($record->leave_extra, true);
        $record['run_name'] = $extra['run_name'];
        return $record;
    }

    /**
     * 加班补偿方式，用于表单用户自选
     */
    public function getOvertimeTo($param = null)
    {
        if ($param) {
            $id = $param['search']['id'][0][0] ?? null;
            if ($id == 1) {
                return [['id' => 1, 'name' => trans('attendance.vacation_balance')]];
            } else if($id == 2){
                return [['id' => 2, 'name' => trans('attendance.salary')]];
            } else {
                return [];
            }
        }
        return [
            ['id' => 1, 'name' => trans('attendance.vacation_balance')],
            ['id' => 2, 'name' => trans('attendance.salary')]
        ];
    }

    public function getRepairType()
    {
        return [
            ['id' => 0, 'name' => trans('attendance.all_day')],
            ['id' => 1, 'name' => trans('attendance.sign_in')],
            ['id' => 2, 'name' => trans('attendance.sign_out')]
        ];
    }
}
