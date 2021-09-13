<?php
namespace App\EofficeApp\Attendance\Services;

use App\EofficeApp\Attendance\Traits\AttendanceTrait;
use App\EofficeApp\Base\BaseService;
use Cache;
class AttendanceBaseService extends BaseService{
    use AttendanceTrait;
    protected $attendanceSettingService;
    protected $userRepository;
    protected $departmentRepository;
    protected $userRoleRepository;
    protected $roleRepository;
    protected $attendanceSimpleRecordsRepository;
    protected $attendanceLeaveRepository;
    protected $vacationRepository;
    protected $attendanceShiftsRepository;
    protected $attendanceShiftsRestTimeRepository;
    protected $attendanceShiftsSignTimeRepository;
    protected $attendanceSchedulingShiftRepository;
    protected $attendanceSchedulingRepository;
    protected $attendanceSchedulingDateRepository;
    protected $attendanceSchedulingUserRepository;
    protected $attendanceRecordsRepository;
    protected $attendancePointsRepository;
    protected $attendanceCommonPurviewGroupRepository;
    protected $attendancePcSignRepository;
    protected $attendanceCalibrationDeptRepository;
    protected $attendanceCalibrationRoleRepository;
    protected $attendanceCalibrationUserRepository;
    protected $attendanceRestRepository;
    protected $attendanceRestSchemeRepository;
    protected $attendanceSchedulingRestRepository;
    protected $attendanceTripRepository;
    protected $attendanceOutRepository;
    protected $attendanceRepairRepository;
    protected $attendanceRepairLogRepository;
    protected $attendanceOvertimeRepository;
    protected $attendanceOvertimeRuleRepository;
    protected $attendanceOvertimeRuleSchedulingRepository;
    protected $attendanceMobileRecordsRepository;
    protected $attendanceStatRepository;
    protected $attendanceOutStatRepository;
    protected $attendanceLeaveStatRepository;
    protected $attendanceLeaveDiffStatRepository;
    protected $attendanceOvertimeStatRepository;
    protected $attendanceTripStatRepository;
    protected $attendanceSchedulingModifyRecordRepository;
    protected $attendanceUserDaysHoursRepository;
    protected $attendanceSetBaseRepository;
    protected $attendanceWifiRepository;
    protected $attendanceImportLogsRepository;
    protected $attendanceCalibrationLogRepository;
    protected $attendanceOvertimeLogRepository;
    protected $attendanceOvertimeTimeLogRepository;
    protected $externalDatabaseService;
    protected $userService;
    protected $langService;
    protected $currentDate;
    protected $vacationService;
    protected $hourPrecision = 2;
    protected $dayPrecision = 3;
    protected $statUnitAsDay = true;
    protected $statUnitAsHours = true;
    protected $statLeaveAsRealAttend = false;
    protected $attendanceMachineConfigRepository;
    protected $attendanceMachineCaseRepository;
    public function __construct()
    {
        parent::__construct();
        $this->attendanceSettingService = 'App\EofficeApp\Attendance\Services\AttendanceSettingService';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->departmentRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->userRoleRepository = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->roleRepository = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->attendanceSimpleRecordsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSimpleRecordsRepository';
        $this->attendanceBackLeaveRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceBackLeaveRepository';
        $this->attendanceBackLeaveLogRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceBackLeaveLogRepository';
        $this->attendanceLeaveRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceLeaveRepository';
        $this->vacationRepository = 'App\EofficeApp\Vacation\Repositories\VacationRepository';
        $this->attendanceShiftsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceShiftsRepository';
        $this->attendanceShiftsRestTimeRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceShiftsRestTimeRepository';
        $this->attendanceShiftsSignTimeRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceShiftsSignTimeRepository';
        $this->attendanceSchedulingShiftRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSchedulingShiftRepository';
        $this->attendanceSchedulingRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSchedulingRepository';
        $this->attendanceSchedulingDateRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSchedulingDateRepository';
        $this->attendanceSchedulingUserRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSchedulingUserRepository';
        $this->attendanceRecordsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRecordsRepository';
        $this->attendancePointsRepository = 'App\EofficeApp\Attendance\Repositories\AttendancePointsRepository';
        $this->attendanceCommonPurviewGroupRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceCommonPurviewGroupRepository';
        $this->attendancePcSignRepository = 'App\EofficeApp\Attendance\Repositories\AttendancePcSignRepository';
        $this->attendanceCalibrationDeptRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceCalibrationDeptRepository';
        $this->attendanceCalibrationRoleRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceCalibrationRoleRepository';
        $this->attendanceCalibrationUserRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceCalibrationUserRepository';
        $this->attendanceRestRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRestRepository';
        $this->attendanceRestSchemeRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRestSchemeRepository';
        $this->attendanceSchedulingRestRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSchedulingRestRepository';
        $this->attendanceTripRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceTripRepository';
        $this->attendanceOutRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOutRepository';
        $this->attendanceOvertimeRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOvertimeRepository';
        $this->attendanceRepairRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRepairRepository';
        $this->attendanceRepairLogRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRepairLogRepository';
        $this->attendanceMobileRecordsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceMobileRecordsRepository';
        $this->attendanceStatRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceStatRepository';
        $this->attendanceOutStatRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOutStatRepository';
        $this->attendanceLeaveStatRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceLeaveStatRepository';
        $this->attendanceLeaveDiffStatRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceLeaveDiffStatRepository';
        $this->attendanceOvertimeStatRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOvertimeStatRepository';
        $this->attendanceOvertimeRuleRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOvertimeRuleRepository';
        $this->attendanceOvertimeRuleSchedulingRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOvertimeRuleSchedulingRepository';
        $this->attendanceTripStatRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceTripStatRepository';
        $this->attendanceSchedulingModifyRecordRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSchedulingModifyRecordRepository';
        $this->attendanceUserDaysHoursRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceUserDaysHoursRepository';
        $this->attendanceSetBaseRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSetBaseRepository';
        $this->attendanceWifiRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceWifiRepository';
        $this->attendanceImportLogsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceImportLogsRepository';
        $this->attendanceCalibrationLogRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceCalibrationLogRepository';
        $this->attendanceOvertimeLogRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOvertimeLogRepository';
        $this->attendanceOvertimeTimeLogRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceOvertimeTimeLogRepository';
        $this->externalDatabaseService = 'App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->vacationService = 'App\EofficeApp\Vacation\Services\VacationService';
        $this->attendanceMachineConfigRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceMachineConfigRepository';
        $this->attendanceMachineCaseRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceMachineCaseRepository';
        $this->currentDate = date('Y-m-d');
        $this->setStatUnit();
    }
    private function setStatUnit()
    {
        $result = ecache('Attendance:AttendanceStatUnitConfig')->get();
        $this->statUnitAsDay = $result['stat_unit_day'] ?? true;
        $this->statUnitAsHours = $result['stat_unit_hours'] ?? true;
        $this->dayPrecision = $result['day_precision'] ?? 3;
        $this->hourPrecision = $result['hours_precision'] ?? 2;
        $this->statLeaveAsRealAttend = $result['stat_leave_as_real_attend'] ?? 0;
    }
    protected function throwException($error, $dynamic = null)
    {
        if (isset($error['code'])) {
            echo json_encode(error_response($error['code'][0], $error['code'][1], $dynamic), 200);
            exit;
        }
    }
     /*
     * 获取请假表中所有的请假类型
     */
    protected function getLeaveVacation()
    {
        static $vacationArray = null;

        if($vacationArray) {
            return $vacationArray;
        }

        $vacationIds = app($this->attendanceLeaveRepository)->getAttendAllLeaveIds();
        $vacation = app($this->vacationRepository)->getVacationById($vacationIds, true);

        if ($vacation === false || $vacation->isEmpty()) {
            $vacationArray = [[], 0];
        } else {
            $vacationMap = $vacation->mapWithKeys(function($item) {
                return [$item->vacation_id => $item->vacation_name];
            });
            $vacationArray = [$vacationMap, count($vacationMap)];
        }

        return $vacationArray;
    }
    protected function combineMulitOrderBy($param, $default = [])
    {
        // 多字段排序处理
        if (isset($param['order_by']) && is_array($param['order_by']) && count($param['order_by']) > 1) {
            $orderByParam = array();
            foreach ($param['order_by'] as $key => $value) {
                if (!empty($value) && is_array($value)) {
                    $orderByParam[key($value)] = current($value);
                }
            }
            $param['order_by'] = $orderByParam;
        } else {
            if(!empty($default)) {
                $param['order_by'] = $default;
            }
        }
        return $param;
    }
     /**
     * 获取需要抵消的时间段数组
     * @param type $startDate
     * @param type $endDate
     * @param type $userIds
     * @param type $onlyOffsetGroup
     * @return type
     */
    public function getOffsetDatetimeGroup($startDate, $endDate, $userIds, $onlyOffsetGroup = true)
    {
        $array = [
            [$this->attendanceLeaveRepository, 'getLeaveRecordsByDateScopeAndUserIds', 'leave'], // 获取按用户分组的请假记录,按日期拆分请假记录
            [$this->attendanceOutRepository, 'getOutRecordsByDateScopeAndUserIds', 'out'], // 获取按用户分组的外出记录,按日期拆分外出记录
            [$this->attendanceTripRepository, 'getTripRecordsByDateScopeAndUserIds', 'trip']// 合并请假，外出时间，主要用于考勤抵消
        ];
        $groups = [];
        foreach ($array as $item) {
            $groups[] = $this->getOutSendDataGroupByDateRange($item[0], $item[1], $startDate, $endDate, $userIds, $item[2]);
        }
        // 合并请假，外出时间，主要用于考勤抵消
        $offsetDatetimeGroup = $this->combileLeaveOutTripByDateMapWithUserIds($groups[0], $groups[1], $groups[2], $userIds);

        if ($onlyOffsetGroup) {
            return $offsetDatetimeGroup;
        }
        $groups[] = $offsetDatetimeGroup;
        return $groups;
    }
    /**
     * 获取按日期时间分组后的外发数据
     * @param type $repository
     * @param type $method
     * @param type $startDate
     * @param type $endDate
     * @param type $userIds
     * @param type $type
     * @return type
     */
    public function getOutSendDataGroupByDateRange($repository, $method, $startDate, $endDate, $userIds, $type = 'leave')
    {
        list($startKey, $endKey) = $this->outSendTimeKeys[$type];
        
        $group = $this->arrayGroupWithKeys(app($repository)->{$method}($startDate, $endDate, $userIds));
        
        return $this->splitDatetimeByDateFromLeaveOrOutGroup($group, $startKey, $endKey);
    }
    /**
     * 按用户ID组装合并请假，外出时间
     *
     * @param type $leaves
     * @param type $outs
     * @param type $userIds
     *
     * @return type
     */
    public function combileLeaveOutTripByDateMapWithUserIds($leaves, $outs, $trips, $userIds)
    {
        if (empty($userIds)) {
            return [];
        }
        $map = [];
        foreach ($userIds as $userId) {
            $oneUserLeaves = $leaves[$userId] ?? [];
            $oneUserOuts = $outs[$userId] ?? [];
            $oneUserTrips = $trips[$userId] ?? [];
            $outTripGroup = $this->mergeTwoDatetimeRange($oneUserOuts, $oneUserTrips);
            $outTripLeaveGroup = $this->mergeTwoDatetimeRange($oneUserLeaves, $outTripGroup);
            if(!empty($outTripLeaveGroup)){
                $map[$userId] = [$outTripGroup, $outTripLeaveGroup];
            }
        }

        return $map;
    }
    /**
     * 分割请假和外出的时间，按日期
     *
     * @param array $group
     * @param string $startKey
     * @param string $endKey
     *
     * @return array
     */
    public function splitDatetimeByDateFromLeaveOrOutGroup($group, $startKey, $endKey)
    {
        if (empty($group)) {
            return [];
        }
        $dateSplitGroup = [];
        foreach ($group as $userId => $items) {
            $dateItemsMap = [];
            foreach ($items as $item) {
                $startTime = $item->$startKey;
                $endTime = $item->$endKey;
                $startDate = $this->format($startTime, 'Y-m-d');
                $endDate = $this->format($endTime, 'Y-m-d');
                if ($startDate == $endDate) {
                    $dateItemsMap[$startDate][] = [$startTime, $endTime];
                } else {
                    $dateRange = $this->getDateFromRange($startDate, $endDate);
                    foreach ($dateRange as $date) {
                        if ($date == $startDate) {
                            $newStartTime = $startTime;
                            $newEndTime = $this->getNextDate($date) . ' 00:00:00';
                        } else if ($date == $endDate) {
                            $newStartTime = $date . ' 00:00:00';
                            $newEndTime = $endTime;
                        } else {
                            $newStartTime = $date . ' 00:00:00';
                            $newEndTime = $this->getNextDate($date) . ' 00:00:00';
                        }
                        if ($newStartTime < $newEndTime) {
                            $dateItemsMap[$date][] = [$newStartTime, $newEndTime];
                        }
                    }
                }
            }
            $dateSplitGroup[$userId] = $dateItemsMap;
        }
        return $dateSplitGroup;
    }
    /**
     * 判断是否是跨天班次
     *
     * @param type $shiftTimes
     *
     * @return boolean
     */
    protected function isOverDateShift($shiftTimes)
    {
        $overDate = false;
        foreach ($shiftTimes as $item) {
            if($item->sign_in_time > $item->sign_out_time) {
                $overDate = true;
            }
        }
        return $overDate;
    }
    /**
     * 获取有效的抵消时间段
     *
     * @param type $offsetDatetimes
     * @param type $signDate
     * @param type $shiftTimes
     * @param int $number 0表示获取外出，出差合并的时间段数组，1表示获取出差，外出，请假合并的时间段数组
     *
     * @return array
     */
    protected function getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes, $number = 1, $handle = null)
    {
        if(is_callable($handle)) {
            $nextDate = $handle();
        } else {
            $nextDate = $this->isOverDateShift($shiftTimes) ? $this->getNextDate($signDate) : false;
        }
        $realOffsetTimes = $offsetDatetimes[$number] ?? [];
        if($nextDate) {
            $current = (isset($realOffsetTimes[$signDate]) && !empty($realOffsetTimes[$signDate])) ? $realOffsetTimes[$signDate] : [];
            $next = (isset($realOffsetTimes[$nextDate]) && !empty($realOffsetTimes[$nextDate])) ? $realOffsetTimes[$nextDate]: [];
            return array_merge($current, $next);
        }
        return (isset($realOffsetTimes[$signDate]) && !empty($realOffsetTimes[$signDate])) ? $realOffsetTimes[$signDate] : [];
    }
    protected function mergeTwoDatetimeRange($one, $two) {
        $group = array_merge_recursive($one, $two);
        if (!empty($group)) {
            $newGroup = [];
            foreach ($group as $date => $items) {
                if (!empty($items)) {
                    $newGroup[$date] = $this->combineDatetimeRange($items);
                }
            }
            return $newGroup;
        }
        return [];
    }
    /**
     * 合并时间范围，有交集的合并
     *
     * @param array $range
     *
     * @return array
     */
    protected function combineDatetimeRange($range)
    {
        if(count($range) == 1) {
            return $range;
        }
        foreach ($range as $item) {
            $begin[] = $item[0];
            $end[] = $item[1];
        }

        array_multisort($begin, SORT_ASC, $end, SORT_ASC, $range);

        $combineRange = [$range[0]];

        for ($i = 1; $i < count($range); $i++) {
            $key = count($combineRange) - 1;
            if ($range[$i][0] <= $combineRange[$key][1]) {
                if ($range[$i][1] > $combineRange[$key][1]) {
                    $combineRange[$key][1] = $range[$i][1];
                }
            } else {
                $combineRange[] = $range[$i];
            }
        }
        return $combineRange;
    }
    protected function schedulingIdMapWithUserIds($userIds = [])
    {
        if(empty($userIds)) {
            return [];
        }
        $schedulings = app($this->attendanceSchedulingUserRepository)->getSchedulingIdsByUserIds($userIds);
        $map = [];
        foreach ($userIds as $userId) {
            $map[$userId] = 0;
        }
        if (count($schedulings) > 0) {
            foreach ($schedulings as $scheduling) {
                $map[$scheduling['user_id']] = $scheduling['scheduling_id'];
            }
        }
        return $map;
    }
    /**
     * 获取多个用户某个日期范围的排班
     * @param type $startDate
     * @param type $endDate
     * @param type $userIds
     * @return array
     */
    protected function schedulingMapWithUserIdsByDateScope($startDate, $endDate, $userIds = [],$type='shift')
    {
        if(empty($userIds)) {
            return [];
        }
        $currentSchedulingIdMap = $this->schedulingIdMapWithUserIds($userIds);

        $historySchedulingIdMap = $this->historySchedulingIdMapWithUserIds($startDate, $endDate, $userIds);
        $schedulingMap = [];

        foreach ($userIds as $userId) {
            $schedulings = [];
            // 判断日期区间内是否有修改过排班的历史记录，如果有则需要获取历史排班
            if (isset($historySchedulingIdMap[$userId])) {
                ksort($historySchedulingIdMap[$userId]);
                $scopeBeginDate = $startDate;
                foreach ($historySchedulingIdMap[$userId] as $modifyDate => $schedulingId) {
                    $scopeEndDate = $modifyDate > $endDate ? $endDate : $modifyDate;
                    $schedulings = array_merge($schedulings, $this->getSchedulingsBySchedulingIdAndDateScope($schedulingId, $scopeBeginDate, $scopeEndDate,$type));
                    $scopeBeginDate = $this->getNextDate($modifyDate);
                }
                if ($scopeBeginDate <= $endDate) {
                    if (isset($currentSchedulingIdMap[$userId])) {
                        $schedulings = array_merge($schedulings, $this->getSchedulingsBySchedulingIdAndDateScope($currentSchedulingIdMap[$userId], $scopeBeginDate, $endDate,$type));
                    }
                }
            } else {
                if (isset($currentSchedulingIdMap[$userId])) {
                    $schedulings = $this->getSchedulingsBySchedulingIdAndDateScope($currentSchedulingIdMap[$userId], $startDate, $endDate,$type);
                }
            }
            $schedulingMap[$userId] = $schedulings;
        }

        return $schedulingMap;
    }
    protected  function attendTimeCountMapWithUserIds($schedulingMap)
    {
        if (empty($schedulingMap)){
            return [];
        }
        $attendTimeCountMap = [];
        foreach ($schedulingMap as $userId => $schedulingItem) {
            $days = 0;
            $attendTimeCount = 0;
            foreach ($schedulingItem as $value) {
                $days++;
                $attendTimeCount += $this->getShiftById($value['shift_id'])->attend_time;
            }
            $hours = $this->calcHours($attendTimeCount);
            $attendTimeCountMap[$userId] = [
                'days' => $days,
                'hours' => $hours,
            ];
        }
        return $attendTimeCountMap;
    }
    protected function getOneArrayMapWithKeysFromGroup($stats, $groupKey, $keyColumn, $valueColumn)
    {
        $stat = $stats[$groupKey] ?? [];

        return $this->arrayMapWithKeys($stat, $keyColumn, $valueColumn);
    }
    protected function arrayMapWithKeys($array, $keyColumn, $valueColumn = null)
    {
        $map = [];

        if (count($array) > 0) {
            foreach ($array as $item) {
                if($valueColumn) {
                    if(is_object($item)) {
                        $map[$item->{$keyColumn}] = $item->{$valueColumn};
                    } else {
                        $map[$item[$keyColumn]] = $item[$valueColumn];
                    }
                } else {
                    if(is_object($item)) {
                        $map[$item->{$keyColumn}] = $item;
                    } else {
                        $map[$item[$keyColumn]] = $item;
                    }
                }
            }
        }

        return $map;
    }
    protected function makeEmptyArrayMap($data)
    {
        $map = [];
        if (count($data) > 0) {
            foreach ($data as $item) {
                $map[$item] = [];
            }
        }
        return $map;
    }
    /**
     * 获取排班的日期时间格式
     * @param type $signDate
     * @param type $signInNormal
     * @param type $signOutNormal
     * @return type
     */
    protected function getSignNormalDatetime($signDate, $signInNormal, $signOutNormal)
    {
        $fullSignInNormal = $this->timeStrToTime($signInNormal, 0, true);
        $fullSignOutNormal = $this->timeStrToTime($signOutNormal, 0, true);
        if ($fullSignInNormal > $fullSignOutNormal) {
            return [$this->combineDatetime($signDate, $fullSignInNormal), $this->combineDatetime($this->getNextDate($signDate), $fullSignOutNormal)];
        }
        return [$this->combineDatetime($signDate, $fullSignInNormal), $this->combineDatetime($signDate, $fullSignOutNormal)];
    }
    /**
     * 获取两个时间段的交集
     *
     * @param type $start1
     * @param type $end1
     * @param type $start2
     * @param type $end2
     *
     * @return int
     */
    protected function getIntersetTime($start1, $end1, $start2, $end2, $array = false)
    {
        $intersetTime = 0;
        $intersetArray = array();
        if ($end2 > $start1 && $start2 < $end1) {
            $beginTime = max($start1, $start2);
            $endTime = min($end2, $end1);
            $intersetTime = strtotime($endTime) - strtotime($beginTime);
            $intersetArray = [$beginTime, $endTime];
        }
        if ($array) {
            return $intersetArray;
        } else {
            return $intersetTime;
        }
    }

    /**
     * 获取多个时间段与多个时间段的相交时间段
     * @param $timesOne
     * @param $timesTwo
     * @return array
     */
    protected function getMultIntersetTimes($timesOne, $timesTwo)
    {
        $res = array();
        foreach ($timesOne as $timeOne) {
            foreach ($timesTwo as $timeTwo) {
                $a = $timeOne[0];
                $b = $timeOne[1];
                $c = $timeTwo[0];
                $d = $timeTwo[1];
                //大小排序
                if ($a > $c) {
                    list($a, $b, $c, $d) = [$c, $d, $a, $b];
                }
                //有交集
                if ($c < $b) {
                    $start = $c;
                    $end = $d >= $b ? $b : $d;
                    $res[] = [$start, $end];
                }
            }
        }
        if (!$res) {
            return $res;
        }
        return $this->combineDatetimeRange($res);
    }

    /**
     * 获取完整的休息时间数组
     *
     * @param type $shiftId
     * @param type $signInNormal
     * @param type $signOutNormal
     * @param type $signDate
     *
     * @return array
     */
    protected function getFullRestTime($shiftId, $signInNormal, $signOutNormal, $signDate)
    {
        $restTime = $this->getRestTime($shiftId, true);
        if ($restTime && count($restTime) > 0) {
            $fullSignTimes = [];
            $begin = [];
            foreach ($restTime as $item) {
                list($restBegin, $restEnd) = $this->getRestDatetime($item->rest_begin, $item->rest_end, $signInNormal, $signOutNormal, $signDate);
                $begin[] = $restBegin;
                $fullSignTimes[] = [$restBegin, $restEnd];
            }
            array_multisort($begin, SORT_ASC, $fullSignTimes);
            return $fullSignTimes;
        }
        return [];
    }
    protected function getRestDatetime($restBegin, $restEnd, $signInNormal, $signOutNormal, $signDate)
    {
        $fullRestBegin = $this->timeStrToTime($restBegin, 0, true);
        $fullRestEnd = $this->timeStrToTime($restEnd, 0, true);
        $fullSignInNormal = $this->timeStrToTime($signInNormal, 0, true);
        $fullSignOutNormal = $this->timeStrToTime($signOutNormal, 0, true);
        if ($fullSignInNormal > $fullSignOutNormal) {
            if ($fullRestBegin > $fullRestEnd) {
                return [$this->combineDatetime($signDate, $fullRestBegin), $this->combineDatetime($this->getNextDate($signDate), $fullRestEnd)];
            }
            if ($fullRestEnd < $fullSignOutNormal) {
                $nextDate = $this->getNextDate($signDate);
                return [$this->combineDatetime($nextDate, $fullRestBegin), $this->combineDatetime($nextDate, $fullRestEnd)];
            }
        }
        return [$this->combineDatetime($signDate, $fullRestBegin), $this->combineDatetime($signDate, $fullRestEnd)];
    }
    /**
     * 获取真实的出勤时间
     *
     * @param type $record
     * @param type $signDate
     * @param type $attendTime
     * @param type $before
     * @param type $isNormal
     *
     * @return array
     */
    protected function getRealAttendTime($record, $signDate, $attendTime,$before, $isNormal = true, $offsetDatetimes = null)
    {
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
        list($signInTime, $signOutTime) = $before($record, $signOutNormal);
        if($offsetDatetimes) {
            $number = $this->statLeaveAsRealAttend ? 1 : 0;
            $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, null, $number, function() use($record, $signDate){
                if($record->sign_in_normal > $record->sign_out_normal) {
                    return $this->getNextDate($signDate);
                }
                return false;
            });
            array_push($effectOffsetDateTimes,[$signInTime, $signOutTime]);
            $effectOffsetDateTimes = $this->combineDatetimeRange($effectOffsetDateTimes);
            $intersetTime = $this->oneMoreDatetimeIntersetTime($signInNormal, $signOutNormal, $effectOffsetDateTimes);
        } else {
            $intersetTime = $this->getIntersetTime($signInTime, $signOutTime, $signInNormal, $signOutNormal);
        }

        if ($intersetTime) {
            if($isNormal) {
                // 正常班排除休息时间
                $restTime = $this->getRestTime($record->shift_id, true);
                if($offsetDatetimes) {
                    $restTimeTotal = 0;
                    foreach ($effectOffsetDateTimes as $item) {
                        $restTimeTotal += $this->getRestTotal($restTime, $item[0], $item[1], $record->sign_in_normal, $record->sign_out_normal, $signDate);
                    }
                } else {
                    $restTimeTotal = $this->getRestTotal($restTime, $signInTime, $signOutTime, $record->sign_in_normal, $record->sign_out_normal, $signDate);
                }
                $oneRealAttendTime = $intersetTime - $restTimeTotal;
                $oneRealAttendDay = $this->calcDay($oneRealAttendTime, $attendTime);
                return [$oneRealAttendTime, $oneRealAttendDay];
            } else {
                // 交换班
                $oneRealAttendDay = $this->calcDay($intersetTime, $attendTime);
                return [$intersetTime, $oneRealAttendDay];
            }
        }
        return [0, 0];
    }
    /**
     * 去掉无效的时间段，取有效时间段
     *
     * @param type $range
     * @param type $startTime
     * @param type $endTime
     *
     * @return type
     */
    protected function getSortDatetimeDiff($range, $startTime, $endTime)
    {
        if (empty($range)) {
            return [[$startTime, $endTime]];
        }
        $diffRange = [];
        $start = $startTime;
        foreach ($range as $item) {
            if ($start < $item[0]) {
                if ($endTime < $item[0]) {
                    if ($start < $endTime) {
                        $diffRange[] = [$start, $endTime];
                    }
                    $start = $item[1];
                    break;
                }
                $diffRange[] = [$start, $item[0]];
                $start = $item[1];
            } else {
                if ($start < $item[1]) {
                    $start = $item[1];
                }
            }
        }
        if ($start < $endTime) {
            $diffRange[] = [$start, $endTime];
        }
        return $diffRange;
    }
    protected function getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, $shiftTimes, $shiftId, $isNormal = true)
    {
        $number = $this->statLeaveAsRealAttend ? 1 : 0;
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes, $number);
        if(empty($effectOffsetDateTimes)) {
            return [0, 0];
        }
        if(!empty($shiftTimes)) {
            $intersetTime = 0;
            foreach($shiftTimes as $item) {
                list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $item->sign_in_time, $item->sign_out_time);
                $intersetTime += $this->oneMoreDatetimeIntersetTime($signInNormal, $signOutNormal, $effectOffsetDateTimes);
            }
            if($isNormal) {
                $restTime = $this->getRestTime($shiftId, true);
                $restTimeTotal = 0;
                foreach($effectOffsetDateTimes as $item) {
                    $restTimeTotal += $this->getRestTotal($restTime, $item[0], $item[1], $signInNormal, $signOutNormal, $signDate);
                }
                $realAttendTime = $intersetTime - $restTimeTotal;
                $realAttendDay = $this->calcDay($realAttendTime, $attendTime);
                return [$realAttendTime, $realAttendDay];
            }
            $realAttendDay = $this->calcDay($intersetTime, $attendTime);
            return [$intersetTime, $realAttendDay];
        }

        return [0, 0];
    }
    /**
     * 获取休息时间总和
     * @param type $restTime
     * @param type $record
     * @param type $noSignOut
     * @return type
     */
    protected function getRestTotal($restTime, $startTime, $endTime, $signInNormal, $signOutNormal, $signDate)
    {
        if (!$restTime || count($restTime) == 0) {
            return 0;
        }
        $restTotal = 0;

        foreach ($restTime as $oneRestTime) {
            list($restBegin, $restEnd) = $this->getRestDatetime($oneRestTime->rest_begin, $oneRestTime->rest_end, $signInNormal, $signOutNormal, $signDate);
            $restTotal += $this->getIntersetTime($startTime, $endTime, $restBegin, $restEnd);
        }

        return $restTotal;
    }
    protected function getMoreDatetimeIntersetTime($source, $desc)
    {
        if(empty($source) || empty($desc)) {
            return 0;
        }
        $total = 0;
        foreach ($source as $item) {
            $total += $this->oneMoreDatetimeIntersetTime($item[0], $item[1], $desc);
        }
        return $total;
    }
    protected function oneMoreDatetimeIntersetTime($start, $end, $dateTimeScope)
    {
        $total = 0;
        foreach ($dateTimeScope as $item) {
            $total += $this->getIntersetTime($start, $end, $item[0], $item[1]);
        }
        return $total;
    }
    protected function statDatetimeRange($datetimeRange)
    {
        $total = 0;
        if(!empty($datetimeRange)) {
            foreach ($datetimeRange as $item) {
                $total += strtotime($item[1]) - strtotime($item[0]);
            }
        }
        return $total;
    }
    public function getShiftById($shiftId)
    {
        static $array;
        if (isset($array[$shiftId])) {
            return $array[$shiftId];
        }
        return $array[$shiftId] = ecache('Attendance:AttendShift')->get($shiftId);
    }
    protected function getHolidayById($holidayId)
    {
        static $array;

        if (isset($array[$holidayId])) {
            return $array[$holidayId];
        }

        return $array[$holidayId] = app($this->attendanceRestRepository)->getDetail($holidayId);
    }
    public function getShiftTimeById($shiftId)
    {
        static $array;

        if (isset($array[$shiftId])) {
            return $array[$shiftId];
        }
        return $array[$shiftId] = ecache('Attendance:AttendShiftTimes')->get($shiftId);
    }
    /**
     * 获取中间休息时间数组
     * @staticvar type $array
     * @param type $shiftId
     * @return type
     */
    protected function getRestTime($shiftId, $isNormalShift = null)
    {
        static $array;

        if (isset($array[$shiftId])) {
            return $array[$shiftId];
        }
        if (!$isNormalShift) {
            $shift = $this->getShiftById($shiftId);
            if (!$shift || $shift->shift_type != 1) {
                return $array[$shiftId] = false;
            }
        }
        return $array[$shiftId] = ecache('Attendance:AttendRestTimes')->get($shiftId);
    }
    /**
     * 获取多个用户某一天的排班
     * @param type $date
     * @param type $userIds
     */
    public function schedulingMapWithUserIdsByDate($date, $userIds = [])
    {
        if (empty($userIds)) {
            return [];
        }
        $currentSchedulingIdMap = $this->schedulingIdMapWithUserIds($userIds);
        $nearestModifyRecords = app($this->attendanceSchedulingModifyRecordRepository)->getNearestModifyRecordByDateAndUserIds($date, $userIds);
        $historySchedulingIdMap = $this->arrayGroupWithKeys($nearestModifyRecords);
        $schedulingMap = [];

        foreach ($userIds as $userId) {
            // 判断日期区间内是否有修改过排班的历史记录，如果有则需要获取历史排班
            if (isset($historySchedulingIdMap[$userId])) {
                $historyScheduling = $historySchedulingIdMap[$userId][0];
                $schedulingMap[$userId] = $this->getSchedulingBySchedulingIdAndDate($historyScheduling->scheduling_id, $date);
            } else {
                if (isset($currentSchedulingIdMap[$userId])) {
                    $schedulingMap[$userId] = $this->getSchedulingBySchedulingIdAndDate($currentSchedulingIdMap[$userId], $date);
                }
            }
        }

        return $schedulingMap;
    }
    protected function historySchedulingIdMapWithUserIds($startDate, $endDate, $userIds)
    {
        $allRecords = [];

        if($startDate != $endDate) {
            $scopeModifyRecords = app($this->attendanceSchedulingModifyRecordRepository)->getModifyRecordsByDateScopeAndUserIds($startDate, $endDate, $userIds);
            $this->combineHistorySchedulingIdMap($scopeModifyRecords, $allRecords);
        }

        $nearestModifyRecords = app($this->attendanceSchedulingModifyRecordRepository)->getNearestModifyRecordByDateAndUserIds($endDate, $userIds);
        $this->combineHistorySchedulingIdMap($nearestModifyRecords, $allRecords);

        return $allRecords;
    }
    private function combineHistorySchedulingIdMap($data, &$allRecords)
    {
        if(!$data->isEmpty()){
            foreach($data as $item) {
                $allRecords[$item->user_id][$item->modify_date] = $item->scheduling_id;
            }
        }
    }
    protected function getSchedulingBySchedulingIdAndDate($schedulingId, $date)
    {
        static $array;

        if (isset($array[$schedulingId . $date])) {
            return $array[$schedulingId . $date];
        }

        $result = app($this->attendanceSchedulingDateRepository)->getSchedulingShiftBySchedulingId($schedulingId, $date);
        if($result) {
            return $array[$schedulingId . $date] = $result->toArray();
        }
        return $array[$schedulingId . $date] =  null;
    }

    private function getSchedulingsBySchedulingIdAndDateScope($schedulingId, $startDate, $endDate, $type = 'shift')
    {
        static $array;

        if (isset($array[$type . $schedulingId . $startDate . $endDate])) {
            return $array[$type . $schedulingId . $startDate . $endDate];
        }
        switch ($type) {
            case 'shift':
                $data = app($this->attendanceSchedulingDateRepository)->getSchedulingDateByDateScope($schedulingId, $startDate, $endDate)->toArray();
                break;
            case 'holiday':
                $data = app($this->attendanceSchedulingRestRepository)->getSchedulingRestByDateScope($schedulingId, $startDate, $endDate)->toArray();
                break;
            default:
                $data = array();
        }
        return $array[$type . $schedulingId . $startDate . $endDate] = $data;
    }

    /**
     * 获取有效的排班时间
     *
     * @param type $shiftTimes
     * @param type $shift
     *
     * @return array
     */
    public function getEffectShiftTimes($shiftTimes, $shift, $signDate)
    {
        // 交换班
        if ($shift->shift_type == 2) {
            $fullSignTimes = [];
            foreach ($shiftTimes as $item) {
                $fullSignTimes[] = $this->getSignNormalDatetime($signDate, $item->sign_in_time, $item->sign_out_time);
            }
            return $fullSignTimes;
        }
        //正常班
        $shiftTime = $shiftTimes[0];

        list($fullSignInNormal, $fullSignOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
        
        $fullRestTime = $this->getFullRestTime($shift->shift_id, $shiftTime->sign_in_time, $shiftTime->sign_out_time, $signDate);
        
        return $this->getSortDatetimeDiff($fullRestTime, $fullSignInNormal, $fullSignOutNormal);
    }
    /**
     * 统计旷工
     *
     * @param type $offsetDatetimes
     * @param type $signDate
     * @param type $attendTime
     * @param type $getShiftTime
     * @param type $getEffectShiftTime
     * @param type $getShiftDay
     *
     * @return array
     */
    public function isAbsenteeism($offsetDatetimes, $signDate, $shiftTimes, $effectShiftTimes)
    {
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);

        if (empty($effectOffsetDateTimes) || empty($effectShiftTimes)) {
            return 0;
        }
        return $this->getMoreDatetimeIntersetTime($effectShiftTimes, $effectOffsetDateTimes);
    }
    /**
     * 统计早退
     *
     * @param type $record
     * @param type $attendTime
     * @param type $handle
     *
     * @return array
     */
    public function isLeaveEarly($record, $handle = false)
    {
        if ($record->is_leave_early) {
            if(is_callable($handle)){
                $earlyTime = $handle($record);
            } else {
                $earlyTime = $record->leave_early_time;
            }
            if($earlyTime > 0) {
                return $earlyTime;
            }
        }
        return 0;
    }
    /**
     * 统计迟到
     *
     * @param type $record
     * @param type $attendTime
     * @param type $handle
     *
     * @return array
     */
    public function isLag($record, $handle = false)
    {
        if ($record->is_lag) {
            if (is_callable($handle)) {
                $lagTime = $handle($record);
            } else {
                $lagTime = $record->lag_time;
            }
            if ($lagTime > 0) {
                return $lagTime;
            }
        }
        return 0;
    }
    public function arrayGroupByDatetimeScope($data, $startKey, $endKey)
    {
        if(count($data) === 0) {
            return [];
        }
        $result = [];
        foreach ($data as $item) {
            $startTime = $item->{$startKey};
            $endTime = $item->{$endKey};
            $dates = $this->getDateFromRange($this->format($startTime, 'Y-m-d'), $this->format($endTime, 'Y-m-d'));
            foreach($dates as $date) {
                if($date . ' 00:00:00' < $endTime) {
                    $result[$date][] = $item;
                }
            }
        }
        return $result;
    }
    /**
     * 获取用户时间范围内的班次信息
     *
     * @param type $startTime
     * @param type $endTime
     * @param type $userId
     * @return type
     */
    public function getShiftTimesGroupByDate($startTime, $endTime, $userId) 
    {
        //获取前一天的日期，防止跨天班的情况
        $startDate = $this->getPrvDate($this->format($startTime, 'Y-m-d'));
        $endDate = $this->format($endTime, 'Y-m-d');
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId])[$userId] ?? [];
        $res = array();
        foreach ($schedulings as $scheduling) {
            $shiftId = $scheduling['shift_id'];
            $shift = $this->getShiftById($shiftId);
            if ($shift) {
                $shift->times = $this->getShiftTimeById($shiftId);
            }
            $res[$scheduling['scheduling_date']] = $shift;
        }
        return $res;
    }

    /**
     * 获取用户时间范围内的节假日信息
     *
     * @param type $startTime
     * @param type $endTime
     * @param type $userId
     * @return type
     */
    public function getHolidayGroupByDate($startTime, $endTime, $userId)
    {
        $startDate = $this->format($startTime, 'Y-m-d');
        $endDate = $this->format($endTime, 'Y-m-d');
        $holidays = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId], 'holiday')[$userId] ?? [];
        $res = array();
        foreach ($holidays as $holiday) {
            $res[$holiday['scheduling_date']] = $this->getHolidayById($holiday['rest_id']);
        }
        return $res;
    }

    /**
     * 获取某个用户某个月的用户排班，微博等地方调用
     * @param $year
     * @param $month
     * @param $userId
     * @return array
     */
    public function getUserSchedulingDateByMonth($year, $month, $userId)
    {
        list($startDate,$endDate)=$this->getMonthStartEnd($year,$month);
        return $this->schedulingMapWithUserIdsByDateScope($startDate,$endDate,[$userId])[$userId] ?? [];
    }
}