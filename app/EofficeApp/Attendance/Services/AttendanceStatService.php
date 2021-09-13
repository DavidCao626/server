<?php
namespace App\EofficeApp\Attendance\Services;

use App\EofficeApp\Attendance\Traits\{AttendanceTransTrait, AttendanceOrgTrait, AttendanceExportTrait, AttendanceStatTrait};

class AttendanceStatService extends AttendanceBaseService
{
    use AttendanceTransTrait;
    use AttendanceOrgTrait;
    use AttendanceExportTrait;
    use AttendanceStatTrait;
    private $orderBy = [
        'department.dept_id' => 'asc',
        'user.user_id' => 'asc',
    ];
    private $br = "\r\n";
    private $userSuperiorRepository;
    public function __construct()
    {
        parent::__construct();
        $this->userSuperiorRepository = 'App\EofficeApp\User\Repositories\UserSuperiorRepository';
    }
    public function abnormalDetailStat($param, $type)
    {
        $startDate = $this->defaultValue('start_date', $param, '');
        $endDate = $this->defaultValue('end_date', $param, '');
        $userIds = $this->defaultValue('user_id', $param, []);
        if (empty($userIds) || !$startDate || !$endDate || $startDate > $endDate) {
            return false;
        }
        $userParam = [
            'noPage' => true,
            'user_id' => $userIds
        ];
        $users = app($this->userRepository)->getSimpleUserList($userParam);
        if (!in_array($type, ['leave', 'out', 'trip', 'overtime'])) {
            $schedulingDates = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, $userIds);
            $recordsStats = $this->recordsStatMapWithUserIds($schedulingDates, $startDate, $endDate, $userIds, false);
        }
        switch ($type) {
            case 'leave':
                $leaveDiffStatRepository = app($this->attendanceLeaveDiffStatRepository);
                $leaveTotalDays = $leaveDiffStatRepository->getMoreUserTotalAttendLeaveStatByDate($startDate, $endDate, $userIds);
                $leaveTotalHours = $leaveDiffStatRepository->getMoreUserTotalAttendLeaveHoursStatByDate($startDate, $endDate, $userIds);
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($leaveTotalDays, $leaveTotalHours) {
                            $oneLeaveTotalDays = $this->defaultValue($userId, $leaveTotalDays, null);
                            $oneLeaveTotalHours = $this->defaultValue($userId, $leaveTotalHours, null);
                            $row['leave_days'] = $oneLeaveTotalDays ? $this->roundDays($oneLeaveTotalDays->days) : 0;
                            $row['leave_hours'] = $oneLeaveTotalHours ? $this->roundHours($oneLeaveTotalHours->hours) : 0;
                            return $row;
                        });
            case 'out':
                $outStatRepository = app($this->attendanceOutStatRepository);
                $outDays = $outStatRepository->getMoreUserAttendOutStatByDate($startDate, $endDate, $userIds);
                $outHours = $outStatRepository->getMoreUserAttendOutHoursStatByDate($startDate, $endDate, $userIds);
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($outDays, $outHours) {
                            $oneOutDays = $this->defaultValue($userId, $outDays, null);
                            $oneOutHours = $this->defaultValue($userId, $outHours, null);
                            $row['out_days'] = $oneOutDays ? $this->roundDays($oneOutDays[0]->days) : 0;
                            $row['out_hours'] = $oneOutHours ? $this->roundHours($oneOutHours[0]->hours) : 0;
                            return $row;
                        });
            case 'overtime':
                $overtimeStatRepository = app($this->attendanceOvertimeStatRepository);
                $overtimeDays = $overtimeStatRepository->getMoreUserAttendOvertimeStatByDate($startDate, $endDate, $userIds);
                $overtimeHours = $overtimeStatRepository->getMoreUserAttendOvertimeHoursStatByDate($startDate, $endDate, $userIds);
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($overtimeDays, $overtimeHours) {
                            $oneOvertimeDays = $this->defaultValue($userId, $overtimeDays, null);
                            $oneOvertimeHours = $this->defaultValue($userId, $overtimeHours, null);
                            $row['overtime_days'] = $oneOvertimeDays ? $this->roundDays($oneOvertimeDays[0]->days) : 0;
                            $row['overtime_hours'] = $oneOvertimeHours ? $this->roundHours($oneOvertimeHours[0]->hours) : 0;
                            return $row;
                        });
            case 'trip':
                $tripStatRepository = app($this->attendanceTripStatRepository);
                $tripDays = $tripStatRepository->getMoreUserAttendTripStatByDate($startDate, $endDate, $userIds);
                $tripHours = $tripStatRepository->getMoreUserAttendTripHoursStatByDate($startDate, $endDate, $userIds);
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($tripDays, $tripHours) {
                            $oneTripDays = $this->defaultValue($userId, $tripDays, null);
                            $oneTripHours = $this->defaultValue($userId, $tripHours, null);
                            $row['trip_days'] = $oneTripDays ? $this->roundDays($oneTripDays[0]->days) : 0;
                            $row['trip_hours'] = $oneTripHours ? $this->roundHours($oneTripHours[0]->hours) : 0;
                            return $row;
                        });
            case 'lag':
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($recordsStats) {
                            $oneStat = $recordsStats[$userId] ?? [];
                            $row['lag_count'] = strval($oneStat['lag_count'] ?? 0);
                            $row['lag_days'] = strval($oneStat['lag_days'] ?? 0);
                            $row['lag_hours'] = strval($oneStat['lag_hours'] ?? 0);
                            return $row;
                        });
            case 'seriously_lag':
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($recordsStats) {
                            $oneStat = $recordsStats[$userId] ?? [];
                            $row['seriously_lag_count'] = strval($oneStat['seriously_lag_count'] ?? 0);
                            $row['seriously_lag_days'] = strval($oneStat['seriously_lag_days'] ?? 0);
                            $row['seriously_lag_hours'] = strval($oneStat['seriously_lag_hours'] ?? 0);
                            return $row;
                        });
            case 'leave_early':
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($recordsStats) {
                            $oneStat = $recordsStats[$userId] ?? [];
                            $row['leave_early_count'] = strval($oneStat['leave_early_count'] ?? 0);
                            $row['leave_early_days'] = strval($oneStat['leave_early_days'] ?? 0);
                            $row['leave_early_hours'] = strval($oneStat['leave_early_hours'] ?? 0);
                            return $row;
                        });
            case 'absenteeism':
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($recordsStats) {
                            $oneStat = $recordsStats[$userId] ?? [];
                            $row['absenteeism_days'] = strval($oneStat['absenteeism_days'] ?? 0);
                            $row['absenteeism_hours'] = strval($oneStat['absenteeism_hours'] ?? 0);
                            return $row;
                        });
            case 'no_sign_out':
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($recordsStats) {
                            $oneStat = $recordsStats[$userId] ?? [];
                            $row['no_sign_out'] = strval($oneStat['no_sign_out'] ?? 0);
                            return $row;
                        });
            case 'calibration':
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($recordsStats) {
                            $oneStat = $recordsStats[$userId] ?? [];
                            $row['calibration_count'] = strval($oneStat['calibration_count'] ?? 0);
                            $row['calibration_days'] = strval($oneStat['calibration_days'] ?? 0);
                            $row['calibration_hours'] = strval($oneStat['calibration_hours'] ?? 0);
                            return $row;
                        });
            case 'repair':
                return $this->combineAbnormalDetailStat($users, function($row, $userId) use($recordsStats) {
                            $oneStat = $recordsStats[$userId] ?? [];
                            $row['repair_count'] = strval($oneStat['repair_count'] ?? 0);
                            $row['repair_days'] = strval($oneStat['repair_days'] ?? 0);
                            $row['repair_hours'] = strval($oneStat['repair_hours'] ?? 0);
                            return $row;
                        });
        }
    }
    private function combineAbnormalDetailStat($users, $combine)
    {
        $stat = [];
        foreach ($users as $user) {
           $userId = $user['user_id'];
           $row = [
               'user_id' => $userId,
               'user_name' => $user['user_name'],
               'dept_name' => $user['dept_name'],
           ];
           $stat[] = $combine($row, $userId);
        }
        return $stat;
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
        if ($allSub) {
            $subUserIds = $this->getAllSubordinateUsers($userId);
        } else {
            $users = app($this->userSuperiorRepository)->getSuperiorUsers($userId);
            if (count($users) > 0) {
                $subUserIds = array_column($users, 'user_id');
            }
        }
        if (!$includeLeave) {
            if (!empty($subUserIds)) {
                $users = app($this->userRepository)->getNoLeaveUsersByUserId($subUserIds);

                $subUserIds = $users->isEmpty() ? [] : array_column($users->toArray(), 'user_id');
            }
        }
        return $subUserIds;
    }
    /**
     * 异常考勤数据统计
     * @param type $param
     * @return boolean
     */
    public function abnormalUserStat($param, $own)
    {
        $startDate = $this->defaultValue('start_date', $param, '');
        $endDate = $this->defaultValue('end_date', $param, '');
        if (!$startDate || !$endDate || $startDate > $endDate) {
            return false;
        }
        $startTime = $this->combineDatetime($startDate, '00:00:00');
        $endTime = $this->combineDatetime($endDate, '23:59:59');
        $userFilter = $this->defaultValue('user_filter', $param, 'all');
        $userParam = ['noPage' => true];
        switch($userFilter) {
            case 'custom':
                $userParam['dept_id'] = json_decode($param['dept_id']);
                break;
            case 'directSub':
                $userParam['user_id'] = $this->getSubordinateUserIds($own['user_id']);
                break;
            case 'allSub':
                $userParam['user_id'] = $this->getSubordinateUserIds($own['user_id'], true);
                break;
            case 'myDept':
                $userParam['dept_id'] = $own['dept_id'];
                break;
        }
        $users = app($this->userRepository)->getSimpleUserList($userParam);
        $userIds = array_column($users, 'user_id');
       
        list($leaves, $outs, $trips, $overtimes) = array_map(function($type) use($startTime, $endTime, $userIds){
            $users = app($this->{'attendance' . ucfirst($type) . 'Repository'})->getUserIdByDateRange($startTime, $endTime);
            
            return count($users) > 0 ? array_intersect(array_column($users, 'user_id'), $userIds) : [];
        }, ['leave', 'out', 'trip', 'overtime']);
        $abnormalUsers = [
            'leave' => $leaves,
            'out' => $outs,
            'trip' => $trips,
            'overtime' => $overtimes,
            'absenteeism' => [],
            'calibration' => [],
            'lag' => [],
            'seriously_lag' => [],
            'leave_early' => [],
            'no_sign_out' => [],
            'repair' => []
        ];
        $attendRecordsMap = $this->getMoreUsersAttendRecordsMap($startDate, $endDate, $userIds);
        // 获取需要抵消的时间段数组
        $offsetDatetimeGroup = $this->getOffsetDatetimeGroup($startDate, $this->getNextDate($endDate), $userIds); // getNextDate兼容跨天班，结束日期往后推一天
        // 获取排班
        $schedulingDates = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, $userIds);
        foreach ($attendRecordsMap as $userId => $items) {
            $oneSchudulingDates = $schedulingDates[$userId] ?? [];
            $offsetDatetimes = $offsetDatetimeGroup[$userId] ?? [];
            $checks = [
                'absenteeism' => false,
                'calibration' => false,
                'lag' => false,
                'seriously_lag' => false,
                'leave_early' => false,
                'no_sign_out' => false,
                'repair' => false
            ];
            if (count($oneSchudulingDates) > 0) {
                foreach ($oneSchudulingDates as $schedulingItem) {
                    $signDate = $schedulingItem['scheduling_date'];
                    $shiftId = $schedulingItem['shift_id'];
                    // 大于当前日期的不统计
                    if ($signDate > $this->currentDate) {
                        continue;
                    }
                    if (isset($items[$signDate])) {
                        $recordGroup = $items[$signDate];
                        if ($recordGroup['type'] === 'swap') {
                            // 交换班
                            $records = $recordGroup['items'];
                            $shiftTimes = $recordGroup['attend_time'][0];
                            foreach ($shiftTimes as $key => $shiftTime) {
                                if (isset($records[$key + 1])) {
                                    $record = $records[$key + 1];
                                    $this->checkOtherAbnormal($checks, $record);
                                    // 是否迟到、早退
                                    $this->checkLagAndLeaveEarly($checks, $record, $offsetDatetimes, $signDate, $shiftId, [$shiftTime]);
                                } else {
                                    //是否旷工
                                    if (!$checks['absenteeism']) {
                                        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                                        $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, [$shiftTime], [[$signInNormal, $signOutNormal]]);
                                        if ($absOffsetTime == 0) {
                                            $checks['absenteeism'] = true;
                                        }
                                    }
                                }
                            }
                        } else {
                            // 普通班
                            $record = $recordGroup['items'];
                            // 是否迟到、早退
                            $this->checkLagAndLeaveEarly($checks, $record, $offsetDatetimes, $signDate, $shiftId, function($shiftId) {
                                return $this->getShiftTimeById($shiftId);
                            }, function($signDate, $signInNormal, $signOutNormal, $shiftId) {
                                $this->getFullRestTime($shiftId, $signInNormal, $signOutNormal, $signDate);
                            });
                            $this->checkOtherAbnormal($checks, $record);
                        }
                    } else {
                        //没有打卡记录,判断是否旷工
                        if (!$checks['absenteeism']) {
                            $shift = $this->getShiftById($shiftId);
                            if ($shift) {
                                //矿工时间计算
                                list($absDay, $absTime) = $this->statAbsenteeism($offsetDatetimes, $signDate, $shift->attend_time, function() use($shiftId) {
                                    return $this->getShiftTimeById($shiftId);
                                }, function($shiftTimes) use($shift, $signDate) {
                                    return $this->getEffectShiftTimes($shiftTimes, $shift, $signDate);
                                });
                                if ($absTime > 0) {
                                    $checks['absenteeism'] = true;
                                }
                            }
                        }
                    }
                }
            }
            foreach ($checks as $key => $value) {
                if ($value) {
                    $abnormalUsers[$key][] = $userId;
                }
            }
        }
        return array_map(function($item) {
            return [
                'users' => $item,
                'count' => count($item)
            ];
        }, $abnormalUsers);
    }
    private function checkOtherAbnormal(&$checks, $record)
    {
        // 是否漏签
        if ($record->sign_out_time == '') {
            $checks['no_sign_out'] = true;
        }
        // 是否校准
        if ($record->calibration_status == 2) {
            $checks['calibration'] = true;
        }
        // 是否补卡
        if ($record->is_repair) {
            $checks['repair'] = true;
        }
    }
    private function checkLagAndLeaveEarly(&$checks, $record, $offsetDatetimes, $signDate, $shiftId, $getShiftTimes, $getFullRestTime = null)
    {
        if (!($checks['lag'] && $checks['seriously_lag'] && $checks['absenteeism']) || !$checks['leave_early']) {
            $shiftTimes = is_callable($getShiftTimes) ? $getShiftTimes($shiftId) : $getShiftTimes;
            $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);
            if ($record->is_offset || empty($effectOffsetDateTimes)) {
                if (!($checks['lag'] && $checks['seriously_lag'] && $checks['absenteeism'])) {
                    $lagTime = $this->isLag($record);
                    $this->checkLagLevel($checks, $lagTime, $shiftId);
                }
                if (!$checks['leave_early']) {
                    $checks['leave_early'] = $this->isLeaveEarly($record) > 0;
                }
            } else {
                $fullRestTimes = is_callable($getFullRestTime) ? $getFullRestTime($signDate, $record->sign_in_normal, $record->sign_out_normal, $shiftId) : [];
                if (!($checks['lag'] && $checks['seriously_lag'] && $checks['absenteeism'])) {
                    $lagTime = $this->getLagTimeHasOffsetTime($record, $effectOffsetDateTimes, $fullRestTimes);
                    $this->checkLagLevel($checks, $lagTime, $shiftId);
                }
                if (!$checks['leave_early']) {
                    $checks['leave_early'] = $this->isLeaveEarlyHasOffsetTime($record, $effectOffsetDateTimes, $fullRestTimes);
                }
            }
        }
    }
    private function checkLagLevel(&$checks, $lagTime, $shiftId)
    {
        if ($lagTime > 0) {
            $shift = $this->getShiftById($shiftId);
            if ($this->isAbsenteeismLag($shift, $lagTime)) {
                $checks['absenteeism'] = true;
            } else if($this->isSeriouslyLag($shift, $lagTime)) {
                $checks['seriously_lag'] = true;
            }else {
                $checks['lag'] = true;
            }
        }
    }
    private function isLeaveEarlyHasOffsetTime($record, $effectOffsetDateTimes, $fullRestTimes = [])
    {
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
        $earlyTime = $this->isLeaveEarly($record, function($record) use($fullRestTimes, $signInNormal, $signOutNormal, $effectOffsetDateTimes) {
            $startTime = max($signInNormal, $record->sign_out_time);
            $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $startTime, $signOutNormal);
            $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
            return $record->leave_early_time - $intersetTime;
        });
        return $earlyTime > 0;
    }
    private function getLagTimeHasOffsetTime($record, $effectOffsetDateTimes, $fullRestTimes = [])
    {
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
        $lagTime = $this->isLag($record, function($record) use($fullRestTimes, $signInNormal, $signOutNormal, $effectOffsetDateTimes) {
            $endTime = min($record->sign_in_time, $signOutNormal);
            $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $signInNormal, $endTime);
            $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
            return $record->lag_time - $intersetTime;
        });
        return $lagTime > 0;
    }
     /**
     * 获取一个用户有一个月的考勤统计
     *
     * @param array $param
     * @param string $userId
     *
     * @return array
     */
    public function oneAttendStat($param, $userId)
    {
         return $this->getOneAttendStat($param, $userId, function($startDate, $endDate, $userId, $schedulingDates) {
                    $records = app($this->attendanceOvertimeStatRepository)->getMoreUserAttendOvertimeRecordsByDate($startDate, $endDate, [$userId]);
                    return $this->splitOvertime($startDate, $endDate, $userId, $records[$userId] ?? [], $schedulingDates);
                });
    }
    /**
     * 获取只有加班转薪酬的考勤统计
     * @param array $param
     * @param string $userId
     *
     * @return array
     */
    public function oneAttendStatOnlySalaryOvertime($param, $userId)
    {
        return $this->getOneAttendStat($param, $userId, function($startDate, $endDate, $userId, $schedulingDates) {
                    $records = app($this->attendanceOvertimeStatRepository)->getMoreUserAttendOvertimeRecordsByDate($startDate, $endDate, [$userId], 2);
                    return $this->splitOvertime($startDate, $endDate, $userId, $records[$userId] ?? [], $schedulingDates, true);
                });
    }
    private function getOneAttendStat($param, $userId, $handleOvertimeStat)
    {
        $param = $this->parseParams($param);
        $year = $this->defaultValue('year', $param, date('Y'));
        $month = intval($this->defaultValue('month', $param, date('m')));
        list($startDate, $endDate) = $this->getStatMonthStartEnd($year, $month);
        // 获取排班
        $schedulingDates = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId]);
        // 创建相关资源库对象
        $leaveDiffStatRepository = app($this->attendanceLeaveDiffStatRepository);
        $outStatRepository = app($this->attendanceOutStatRepository);
        $tripStatRepository = app($this->attendanceTripStatRepository);
        $mobileRecordsRepository = app($this->attendanceMobileRecordsRepository);
        // 统计加班明细项
        $overtimeStat = $handleOvertimeStat($startDate, $endDate, $userId, array_column($schedulingDates[$userId], 'scheduling_date'));
        // 统计请假明细项
        $leaveDays = $leaveDiffStatRepository->getMoreUserAttendLeaveStatByDate($startDate, $endDate, [$userId]);
        $leaveHours = $leaveDiffStatRepository->getMoreUserAttendLeaveHoursStatByDate($startDate, $endDate, [$userId]);
        $leaveItems = [];
        $userLeaveDays = $leaveDays[$userId] ?? [];
        $userLeaveHours = $leaveHours[$userId] ?? [];
        list($vacation, $count) = $this->getLeaveVacation();
        foreach ($vacation as $key => $name) {
            $leaveItems[] = [
                'vacation_id' => $key,
                'vacation_name' => $name,
                'days' => strval($this->roundDays($userLeaveDays[$key] ?? 0)),
                'hours' => strval($this->roundHours($userLeaveHours[$key] ?? 0))
            ];
        }

        $stat = [
            'stat_unit_day' => $this->statUnitAsDay,
            'stat_unit_hours' => $this->statUnitAsHours,
            'leave_days' => $leaveDiffStatRepository->getUserLeaveDaysByMonth($year, $month, $userId),
            'leave_money_days' => $leaveDiffStatRepository->getUserLeaveDaysByMonth($year, $month, $userId, true),
            'leave_hours' => $leaveDiffStatRepository->getUserLeaveHoursByMonth($year, $month, $userId),
            'leave_money_hours' => $leaveDiffStatRepository->getUserLeaveHoursByMonth($year, $month, $userId, true),
            'leave_items' => $leaveItems,
            'out_days' => $outStatRepository->getAttendOutStatByMonth($year, $month, $userId),
            'out_hours' => $outStatRepository->getAttendOutHoursStatByMonth($year, $month, $userId),
            'overtime_days' => $overtimeStat[0] + $overtimeStat[2] + $overtimeStat[4],
            'overtime_hours' => $overtimeStat[1] + $overtimeStat[3] + $overtimeStat[5],
            'normal_overtime_days' => $overtimeStat[0], // 工作日加班
            'normal_overtime_hours' => $overtimeStat[1], // 工作日加班
            'rest_overtime_days' => $overtimeStat[2], // 周末加班
            'rest_overtime_hours' => $overtimeStat[3], // 周末加班
            'holiday_overtime_days' => $overtimeStat[4], // 节假日加班
            'holiday_overtime_hours' => $overtimeStat[5], // 节假日加班
            'trip_days' => $tripStatRepository->getAttendTripStatByMonth($year, $month, $userId),
            'trip_hours' => $tripStatRepository->getAttendTripHoursStatByMonth($year, $month, $userId),
            'report_location_count' => $mobileRecordsRepository->getRecordsTotal(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId], 'sign_type' => [0]]) //位置上报次数统计
        ];

        $recordsStats = $this->recordsStatMapWithUserIds($schedulingDates, $startDate, $endDate, [$userId], false);

        $stats = array_merge($stat, $recordsStats[$userId]);

        array_walk($stats, function (&$value, $key) {
            if (in_array($key, ['leave_days', 'out_days', 'overtime_days', 'trip_days'])) {
                $value = $this->roundDays($value);
            } elseif (in_array($key, ['leave_hours', 'out_hours', 'overtime_hours', 'trip_hours'])) {
                $value = $this->roundHours($value);
            }
            if (is_array($value)) {
                return $value;
            }
            return strval($value);
        });
        return $stats;
    }
    public function moreAttendStatNoHeader($param, $own)
    {
        return $this->moreAttendStat($param, $own, false);
    }
    /**
     * 统计多人一个月的考勤数据
     *
     * @param type $param
     * @param type $own
     *
     * @return array
     */
    public function moreAttendStat($param, $own, $hasHeader = true,$simple=true)
    {
        $viewUser = app($this->attendanceSettingService)->getPurviewUser($own, 247);
        if (empty($viewUser)) {
            return ['total' => 0, 'list' => []];
        }

        $param = $this->parseParams($param);
        $param = $this->combineMulitOrderBy($param, $this->orderBy);// 多字段排序处理
        $year = $this->defaultValue('year', $param, date('Y'));
        $month = intval($this->defaultValue('month', $param, date('m')));
        list($startDate, $endDate) = $this->getMonthStartEnd($year, $month);
        $type = $this->defaultValue('type', $param, 'dept');

        if ($type == 'dept') {
            if ($viewUser != 'all') {
                $param['user_id'] = $viewUser;
            }
            if (isset($param['dept_id'])) {
                $param['dept_id'] = json_decode($param['dept_id']);
            }
            if (isset($param['search']) && isset($param['search']['user_name'])) {
                $param['user_name'] = $param['search']['user_name'][0];
            }
        } else {
            unset($param['dept_id']);
            if (empty(json_decode($param['user_id']))) {
                $param['user_id'] = $viewUser == 'all' ? [] : $viewUser;
            } else {
                $param['user_id'] = json_decode($param['user_id']);
            }
        }
        $total = app($this->userRepository)->getSimpleUserTotal($param);
        $users = app($this->userRepository)->getSimpleUserList($param);
        if(!$hasHeader) {
           return ['total' => $total, 'list' => $this->getNoHeaderStatBody($startDate, $endDate, $users,$simple)];
        }
        $header = $this->getStatTableHeader($startDate, $endDate);
        $bodyYield = $this->getStatTableBody($startDate, $endDate, $users);
        $body = [];
        foreach ($bodyYield as $item) {
            array_push($body, $item);
        }
        return ['total' => $total, 'list' => ['header' => $header, 'body' => $body]];
    }
    public function getStatTableHeader($startDate, $endDate, $isExport = false)
    {
        if ($this->statUnitAsDay && $this->statUnitAsHours) {
            $leaves = $this->getLeaveColumns(function($item) {
                    unset($item['style']);
                    $item['children'] = $this->getColumnNodes(['zhcn_day', 'min_hour'], 40, 'leave');
                    return $item;
                });
            $header = [
                ['data' => $this->trans('dept'), 'style' => ['width' => '100px'], 'type' => 'dept'],
                ['data' => $this->trans('user'), 'style' => ['width' => '80px'], 'type' => 'user'],
                [
                    'data' => $this->trans('must_attend'), 'type' => 'must_attend',
                    'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'must_attend')
                ],[
                    'data' => $this->trans('real_attend'), 'type' => 'real_attend',
                    'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'real_attend')
                ],[
                    'data' => $this->trans('no_attend'), 'type' => 'no_attend',
                    'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'no_attend')
                ],[
                    'data' => $this->trans('attend_ratio'), 'type' => 'attend_ratio',
                    'children' => $this->getColumnNodes(['personage', 'average'], 60, 'attend_ratio')
                ],[
                    'data' => $this->trans('lag'), 'type' => 'lag',
                    'children' => $this->getColumnNodes(['next', 'zhcn_day', 'min_hour'], 60, 'lag')
                ],[
                    'data' => $this->trans('seriously_lag'), 'type' => 'seriously_lag',
                    'children' => $this->getColumnNodes(['next', 'zhcn_day', 'min_hour'], 60, 'seriously_lag')
                ],[
                    'data' => $this->trans('leave_early'), 'type' => 'leave_early',
                    'children' => $this->getColumnNodes(['next', 'zhcn_day', 'min_hour'], 60, 'leave_early')
                ],[
                    'data' => $this->trans('absenteeism'), 'type' => 'absenteeism',
                    'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'absenteeism')
                ],
                ['data' => $this->trans('no_sign_out') . '(' . $this->trans('next') . ')', 'style' => ['width' => '60px'], 'type' => 'no_sign_out'],
                ['data' => $this->trans('leave'), 'type' => 'leave', 'children' => $leaves],
                [
                    'data' => $this->trans('trip'), 'type' => 'trip',
                    'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'trip')
                ],[
                    'data' => $this->trans('out'), 'type' => 'out',
                    'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'out')
                ],[
                    'data' => $this->trans('overtime'), 'type' => 'overtime',
                    'children' => [
                        [
                            'data' => $this->trans('all'), 'type' => 'overtime',
                            'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'overtime')
                        ], [
                            'data' => $this->trans('working_day'), 'type' => 'overtime',
                            'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'overtime')
                        ], [
                            'data' => $this->trans('rest_day'), 'type' => 'overtime',
                            'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'overtime')
                        ], [
                            'data' => $this->trans('holiday'), 'type' => 'overtime',
                            'children' => $this->getColumnNodes(['zhcn_day', 'min_hour'], 60, 'overtime')
                        ]
                    ]
                ]
            ];
        } else {
            $unitType  = $this->statUnitAsDay ? 'zhcn_day' : 'min_hour';
            $unit = '(' .$this->trans($unitType). ')';
            $header = [
                ['data' => $this->trans('dept'), 'style' => ['width' => '100px'], 'type' => 'dept'],
                ['data' => $this->trans('user'), 'style' => ['width' => '80px'], 'type' => 'user'],
                ['data' => $this->trans('must_attend').$this->br . $unit, 'style' => ['width' => '60px'], 'type' => 'must_attend'],
                ['data' => $this->trans('real_attend') .$this->br. $unit, 'style' => ['width' => '60px'], 'type' => 'real_attend'],
                ['data' => $this->trans('no_attend') .$this->br. $unit, 'style' => ['width' => '40px'], 'type' => 'no_attend'],
                [
                    'data' => $this->trans('attend_ratio'), 'type' => 'attend_ratio',
                    'children' => $this->getColumnNodes(['personage', 'average'], 60, 'attend_ratio')
                ],[
                    'data' => $this->trans('lag'), 'type' => 'lag',
                    'children' => $this->getColumnNodes(['next', $unitType], 60, 'lag')
                ],[
                    'data' => $this->trans('seriously_lag'), 'type' => 'seriously_lag',
                    'children' => $this->getColumnNodes(['next', $unitType], 60, 'seriously_lag')
                ],[
                    'data' => $this->trans('leave_early'), 'type' => 'leave_early',
                    'children' => $this->getColumnNodes(['next', $unitType], 60, 'leave_early')
                ],
                ['data' => $this->trans('absenteeism') .$this->br. $unit, 'style' => ['width' => '60px'], 'type' => 'absenteeism'],
                ['data' => $this->trans('no_sign_out') . $this->br . '(' . $this->trans('next') . ')', 'style' => ['width' => '60px'], 'type' => 'no_sign_out'],
                ['data' => $this->trans('leave') . $unit, 'type' => 'leave', 'children' => $this->getLeaveColumns()],
                ['data' => $this->trans('trip') . $unit, 'style' => ['width' => '60px'], 'type' => 'trip'],
                ['data' => $this->trans('out') . $unit, 'style' => ['width' => '60px'], 'type' => 'out'],
                [
                    'data' => $this->trans('overtime') . $unit, 'type' => 'overtime',
                    'children' => $this->getColumnNodes(['all', 'working_day', 'rest_day', 'holiday'], 60, 'overtime')
                ]
            ];
        }
        $header[] = $this->getCalendarColumn($startDate, $endDate, $isExport ? 'Y/m/d' : 'd');
        if ($isExport) {
            return $header;
        }
        return $this->getColumnsTitleRows($header);
    }
    public function getColumnsTitleRows($header)
    {
        list($rowNumber, $columnNumber) = $this->parseAndCombineColumns($header);
        
        return $this->combineColumnRows($header, $rowNumber);
    }
      private function combineColumnRows($columns, $rowNumber)
    {
        $rows = [];
        for ($i = 1; $i <= $rowNumber; $i++) {
            $rows[] = [];
        }
        $this->_combineColumnRows($rows, $columns, $rowNumber);
        return $rows;
    }
    private function _combineColumnRows(&$rows, $columns, $currentRowNumber,$level = 0) 
    {
        foreach ($columns as $column) {
            if (isset($column['children'])) {
                $copyColumn = $column;
                unset($copyColumn['children']);

                $this->_combineColumnRows($rows, $column['children'], $currentRowNumber - 1, $level + 1);
                $rows[$level][] = $copyColumn;
            } else {
                if ($currentRowNumber > 1) {
                    $column['rowspan'] = $currentRowNumber;
                }
                $rows[$level][] = $column;
            }
        }
    }
    private function parseAndCombineColumns(&$columns) 
    {
        $maxRowNumber = 1;
        $columnNumber = 0;
        foreach ($columns as &$column) {
            if (isset($column['children'])) {
                list($_rowNumber, $_colNumber) = $this->parseAndCombineColumns($column['children']);
                $rowNumber = $_rowNumber + 1;
                if($rowNumber > $maxRowNumber){
                    $maxRowNumber = $rowNumber;
                }
                $column['colspan'] = $_colNumber;
                $columnNumber += $_colNumber;
            } else {
                $columnNumber += 1;
            }
        }
        return [$maxRowNumber, $columnNumber];
    }
    private function getCalendarColumn($startDate, $endDate, $format) 
    {
        $dates = $this->getDateFromRange($startDate, $endDate);
        $calendar = [];
        foreach ($dates as $date) {
            list($weekKey, $week) = $this->getWeekByDate($date);
            $style = in_array($weekKey, [0, 6]) ? ['width' => '80px', 'color' => '#f92323'] : ['width' => '80px'];
            $calendar[] = [
                'data' => $this->format($date, $format), 'style' => $style, 'type' => 'calendar', 'dataType' => 'string',
                'children' => [['data' => $week, 'style' => $style, 'type' => 'calendar']]
            ];
        }
        return ['data' => trans('attendance.attendance_calendar'), 'type' => 'calendar', 'children' => $calendar];
    }
    private function getColumnNodes($nodes, $width, $type)
    {
        if (is_array($nodes)) {
            $columns = [];
            foreach ($nodes as $node) {
                $columns[] = ['data' => $this->trans($node), 'style' => ['width' => $width . 'px'], 'type' => $type];
            }
            return $columns;
        } else {
            return ['data' => $this->trans($node), 'style' => ['width' => $width . 'px'], 'type' => $type];
        }
    }
    private function getLeaveColumns($callback = null)
    {
        list($vacation, $count) = $this->getLeaveVacation();
        $leaves = [];
        $first = ['data' => $this->trans('all'), 'type' => 'leave', 'style' => ['width' => '40px']];
        $leaves[] = $callback ? $callback($first) : $first;
        foreach ($vacation as $name) {
            $item = ['data' => $name, 'type' => 'leave', 'style' => ['width' => '40px']];
            $leaves[] = $callback ? $callback($item) : $item;
        }
        return $leaves;
    }
    public function getNoHeaderStatBody($startDate, $endDate, $users,$simple=true)
    {
        $userIds = array_column($users, 'user_id');
        // 获取排班
        $schedulingDates = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, $userIds);

        $recordsStats = $this->recordsStatMapWithUserIds($schedulingDates, $startDate, $endDate, $userIds);
        $list = [];
        if (count($users) > 0) {
            foreach ($users as $user) {
                $userId = $user['user_id'];
                $deptId = $user['dept_id'];
                $stat = $recordsStats[$userId];
                $item = [
                    'user_id' => $userId,
                    'dept_id' => $deptId,
                    'user_name' => $user['user_name'],
                    'dept_name' => $user['dept_name'],
                    'lag_count' => $stat['lag_count'],
                    'leave_early_count' => $stat['leave_early_count'],
                    'absenteeism_days' => $stat['absenteeism_days'],
                    'calibration_count' => $stat['calibration_count']
                ];
                if (!$simple) {
                    $item = array_merge($item, $stat);
                }
                $list[] = $item;
            }
        }
        return $list;
    }
    /**
     * √
     * 获取统计数据
     *
     * @param type $startDate
     * @param type $endDate
     * @param type $users
     * @param type $export
     *
     * @return array
     */
    public function getStatTableBody($startDate, $endDate, $users, $isExport = false)
    {
        $this->clearStaticDepartment();
        $this->isExportStat = $isExport;
        $userIds = array_column($users, 'user_id');
        // 获取排班
        $schedulingDates = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, $userIds);
        // 创建资源库对象
        $leaveDiffStatRepository = app($this->attendanceLeaveDiffStatRepository);
        $outStatRepository = app($this->attendanceOutStatRepository);
        $overtimeStatRepository = app($this->attendanceOvertimeStatRepository);
        $tripStatRepository = app($this->attendanceTripStatRepository);
        // 获取请假，外出，出差，加班的天数和小时数
        $leaveDays = $leaveDiffStatRepository->getMoreUserAttendLeaveStatByDate($startDate, $endDate, $userIds);
        $leaveHours = $leaveDiffStatRepository->getMoreUserAttendLeaveHoursStatByDate($startDate, $endDate, $userIds);
        $leaveTotalDays = $leaveDiffStatRepository->getMoreUserTotalAttendLeaveStatByDate($startDate, $endDate, $userIds);
        $leaveTotalHours = $leaveDiffStatRepository->getMoreUserTotalAttendLeaveHoursStatByDate($startDate, $endDate, $userIds);
        $outDays = $outStatRepository->getMoreUserAttendOutStatByDate($startDate, $endDate, $userIds);
        $outHours = $outStatRepository->getMoreUserAttendOutHoursStatByDate($startDate, $endDate, $userIds);
        $overtimeDays = $overtimeStatRepository->getMoreUserAttendOvertimeStatByDate($startDate, $endDate, $userIds);
        $overtimeHours = $overtimeStatRepository->getMoreUserAttendOvertimeHoursStatByDate($startDate, $endDate, $userIds);
        $tripDays = $tripStatRepository->getMoreUserAttendTripStatByDate($startDate, $endDate, $userIds);
        $tripHours = $tripStatRepository->getMoreUserAttendTripHoursStatByDate($startDate, $endDate, $userIds);
        $overtimeRecords = $overtimeStatRepository->getMoreUserAttendOvertimeRecordsByDate($startDate, $endDate, $userIds);
        $recordsStats = $this->recordsStatMapWithUserIds($schedulingDates, $startDate, $endDate, $userIds);
        //部门分组，部门开始的行索引以及长度（占有的行数）
        $userGroupWidthDetp = [];
        foreach ($users as $key => $user) {
            $deptId = $user['dept_id'];
            $stat = $recordsStats[$user['user_id']];
            if(isset($userGroupWidthDetp[$deptId])) {
                $userGroupWidthDetp[$deptId]['length'] += 1;
                $userGroupWidthDetp[$deptId]['ratio'] += $this->calculateRatio($stat['attend_hours'], $stat['real_attend_hours']);
            } else {
                $userGroupWidthDetp[$deptId]['start'] = $key;
                $userGroupWidthDetp[$deptId]['dept_name'] = $user['dept_name'];
                $userGroupWidthDetp[$deptId]['length'] = 1;
                $userGroupWidthDetp[$deptId]['ratio'] = $this->calculateRatio($stat['attend_hours'], $stat['real_attend_hours']);
            }
        }
        foreach ($userGroupWidthDetp as $deptId => $item) {
            $userGroupWidthDetp[$deptId]['ratio'] = round($userGroupWidthDetp[$deptId]['ratio'] / $item['length'], $this->hourPrecision). '%' ;
        }
        list($vacation, $count) = $this->getLeaveVacation();
        $rows = [];
        if (count($users) > 0) {
            foreach ($users as $rowIndex => $user) {
                $userId = $user['user_id'];
                $deptId = $user['dept_id'];
                $stat = $recordsStats[$userId];
                //拆分加班
                $overtimeStat = $this->splitOvertime($startDate, $endDate, $userId, $overtimeRecords[$userId] ?? [], array_column($schedulingDates[$userId], 'scheduling_date'));
                list($normalOvertimeDays, $normalOvertimeHours, $restOvertimeDays, $restOvertimeHours, $holidayOvertimeDays, $holidayOvertimeHours) = $overtimeStat;
                // 获取rowspan信息
                list($isRowspanIndex, $deptRatio, $rowspan) = $this->getRowspan($deptId, $rowIndex, $userGroupWidthDetp);
                $oneLeaveTotalDays = $this->defaultValue($userId, $leaveTotalDays, null);
                $oneLeaveTotalHours = $this->defaultValue($userId, $leaveTotalHours, null);
                $oneTripDays = $this->defaultValue($userId, $tripDays, null);
                $oneTripHours = $this->defaultValue($userId, $tripHours, null);
                $oneOutDays = $this->defaultValue($userId, $outDays, null);
                $oneOutHours = $this->defaultValue($userId, $outHours, null);
                $oneOvertimeDays = $this->defaultValue($userId, $overtimeDays, null);
                $oneOvertimeHours = $this->defaultValue($userId, $overtimeHours, null);
                if($this->statUnitAsDay && $this->statUnitAsHours){
                    //该用户请假的列值
                    $leaveColumns = [];
                    $userLeaveDays = $leaveDays[$userId] ?? [];
                    $userLeaveHours = $leaveHours[$userId] ?? [];
                    foreach ($vacation as $key => $name) {
                        $leaveColumns[]['data'] = $this->roundDays($userLeaveDays[$key] ?? 0);
                        $leaveColumns[]['data'] = $this->roundHours($userLeaveHours[$key] ?? 0);
                    }
                    $row = [
                        ['data' => $user['user_name']],
                        ['data' => $stat['attend_days']], // 应出勤天数
                        ['data' => $stat['attend_hours']], //应出勤小时
                        ['data' => $stat['real_attend_days']], //实际出勤天数
                        ['data' => $stat['real_attend_hours']], //实际出勤小时
                        ['data' => $stat['no_attend_days'], 'style' => $this->getDangerStyle($stat['no_attend_days'])], // 未出勤
                        ['data' => $stat['no_attend_hours'], 'style' => $this->getDangerStyle($stat['no_attend_hours'])], // 未出勤
                        ['data' => $this->calculateRatio($stat['attend_hours'], $stat['real_attend_hours']) . '%'], //个人出勤率
                        ['data' => $stat['lag_count'], 'style' => $this->getDangerStyle($stat['lag_count'])], // 迟到次数
                        ['data' => $stat['lag_days'], 'style' => $this->getDangerStyle($stat['lag_count'])], // 迟到天数
                        ['data' => $stat['lag_hours'], 'style' => $this->getDangerStyle($stat['lag_count'])], // 迟到天数
                        ['data' => $stat['seriously_lag_count'], 'style' => $this->getDangerStyle($stat['seriously_lag_count'])], // 迟到次数
                        ['data' => $stat['seriously_lag_days'], 'style' => $this->getDangerStyle($stat['seriously_lag_count'])], // 迟到天数
                        ['data' => $stat['seriously_lag_hours'], 'style' => $this->getDangerStyle($stat['seriously_lag_count'])], // 迟到天数
                        ['data' => $stat['leave_early_count'], 'style' => $this->getDangerStyle($stat['leave_early_count'])], //早退次数
                        ['data' => $stat['leave_early_days'], 'style' => $this->getDangerStyle($stat['leave_early_count'])], //早退天数
                        ['data' => $stat['leave_early_hours'], 'style' => $this->getDangerStyle($stat['leave_early_count'])], //早退天数
                        ['data' => $stat['absenteeism_days'], 'style' => $this->getDangerStyle($stat['absenteeism_days'])], // 矿工
                        ['data' => $stat['absenteeism_hours'], 'style' => $this->getDangerStyle($stat['absenteeism_days'])], // 矿工
                        ['data' => $stat['no_sign_out']], //未签退
                        ['data' => $oneLeaveTotalDays ? $this->roundDays($oneLeaveTotalDays->days) : 0],
                        ['data' => $oneLeaveTotalHours ? $this->roundHours($oneLeaveTotalHours->hours) : 0],
                        ['data' => $oneTripDays ? $this->roundDays($oneTripDays[0]->days) : 0],
                        ['data' => $oneTripHours ? $this->roundHours($oneTripHours[0]->hours) : 0],
                        ['data' => $oneOutDays ? $this->roundDays($oneOutDays[0]->days) : 0],
                        ['data' => $oneOutHours ? $this->roundHours($oneOutHours[0]->hours) : 0],
                        ['data' => $oneOvertimeDays ? $this->roundDays($oneOvertimeDays[0]->days) : 0],
                        ['data' => $oneOvertimeHours ? $this->roundHours($oneOvertimeHours[0]->hours) : 0],
                        ['data' => $this->roundDays($normalOvertimeDays)],
                        ['data' => $this->roundHours($normalOvertimeHours)],
                        ['data' => $this->roundDays($restOvertimeDays)],
                        ['data' => $this->roundHours($restOvertimeHours)],
                        ['data' => $this->roundDays($holidayOvertimeDays)],
                        ['data' => $this->roundHours($holidayOvertimeHours)]
                    ];
                    $leaveIndex = 22;
                    $ratioIndex = 9;
                    $isOneStat = false;
                } else {
                    if($this->statUnitAsDay) {
                        //该用户请假的列值
                        $leaveColumns = [];
                        $userLeaveDays = $leaveDays[$userId] ?? [];
                        foreach ($vacation as $key => $name) {
                            $leaveColumns[]['data'] = round($userLeaveDays[$key] ?? 0,$this->dayPrecision);
                        }
                        $row = [
                            ['data' => $user['user_name']],
                            ['data' => $stat['attend_days']], // 应出勤天数
                            ['data' => $stat['real_attend_days']], //实际出勤天数
                            ['data' => $stat['no_attend_days'], 'style' => $this->getDangerStyle($stat['no_attend_days'])], // 未出勤
                            ['data' => $this->calculateRatio($stat['attend_hours'], $stat['real_attend_hours']) . '%' ], //个人出勤率
                            ['data' => $stat['lag_count'], 'style' => $this->getDangerStyle($stat['lag_count'])], // 迟到次数
                            ['data' => $stat['lag_days'], 'style' => $this->getDangerStyle($stat['lag_count'])], // 迟到天数
                            ['data' => $stat['seriously_lag_count'], 'style' => $this->getDangerStyle($stat['seriously_lag_count'])], // 迟到次数
                            ['data' => $stat['seriously_lag_days'], 'style' => $this->getDangerStyle($stat['seriously_lag_count'])], // 迟到天数
                            ['data' => $stat['leave_early_count'], 'style' => $this->getDangerStyle($stat['leave_early_count'])], //早退次数
                            ['data' => $stat['leave_early_days'], 'style' => $this->getDangerStyle($stat['leave_early_count'])], //早退天数
                            ['data' => $stat['absenteeism_days'], 'style' => $this->getDangerStyle($stat['absenteeism_days'])], // 矿工
                            ['data' => $stat['no_sign_out']], //未签退
                            ['data' => $oneLeaveTotalDays ? $this->roundDays($oneLeaveTotalDays->days) : 0],
                            ['data' => $oneTripDays ? $this->roundDays($oneTripDays[0]->days) : 0],
                            ['data' => $oneOutDays ? $this->roundDays($oneOutDays[0]->days) : 0],
                            ['data' => $oneOvertimeDays ? $this->roundDays($oneOvertimeDays[0]->days) : 0],
                            ['data' => $this->roundDays($normalOvertimeDays)],
                            ['data' => $this->roundDays($restOvertimeDays)],
                            ['data' => $this->roundDays($holidayOvertimeDays)]
                        ];
                    } else {
                        //该用户请假的列值
                        $leaveColumns = [];
                        $userLeaveHours = $leaveHours[$userId] ?? [];
                        foreach ($vacation as $key => $name) {
                            $leaveColumns[]['data'] = round($userLeaveHours[$key] ?? 0,$this->hourPrecision);
                        }
                        $row = [
                            ['data' => $user['user_name']],
                            ['data' => $stat['attend_hours']], //应出勤小时
                            ['data' => $stat['real_attend_hours']], //实际出勤小时
                            ['data' => $stat['no_attend_hours'], 'style' => $this->getDangerStyle($stat['no_attend_hours'])], // 未出勤
                            ['data' => $this->calculateRatio($stat['attend_hours'], $stat['real_attend_hours']). '%' ], //个人出勤率
                            ['data' => $stat['lag_count'], 'style' => $this->getDangerStyle($stat['lag_count'])], // 迟到次数
                            ['data' => $stat['lag_hours'], 'style' => $this->getDangerStyle($stat['lag_count'])], // 迟到天数
                            ['data' => $stat['seriously_lag_count'], 'style' => $this->getDangerStyle($stat['seriously_lag_count'])], // 迟到次数
                            ['data' => $stat['seriously_lag_hours'], 'style' => $this->getDangerStyle($stat['seriously_lag_count'])], // 迟到天数
                            ['data' => $stat['leave_early_count'], 'style' => $this->getDangerStyle($stat['leave_early_count'])], //早退次数
                            ['data' => $stat['leave_early_hours'], 'style' => $this->getDangerStyle($stat['leave_early_count'])], //早退天数
                            ['data' => $stat['absenteeism_hours'], 'style' => $this->getDangerStyle($stat['absenteeism_days'])], // 矿工
                            ['data' => $stat['no_sign_out']], //未签退
                            ['data' => $oneLeaveTotalHours ? $this->roundHours($oneLeaveTotalHours->hours) : 0],
                            ['data' => $oneTripHours ? $this->roundHours($oneTripHours[0]->hours) : 0],
                            ['data' => $oneOutHours ? $this->roundHours($oneOutHours[0]->hours) : 0],
                            ['data' => $oneOvertimeHours ? $this->roundHours($oneOvertimeHours[0]->hours) : 0],
                            ['data' => $this->roundHours($normalOvertimeHours)],
                            ['data' => $this->roundHours($restOvertimeHours)],
                            ['data' => $this->roundHours($holidayOvertimeHours)]
                        ];
                    }
                    $leaveIndex = 14;
                    $ratioIndex = 6;
                    $isOneStat = true;
                }
                //重新组合数组
                array_splice($row, $leaveIndex, 0, $leaveColumns); // 第14列插入请假明细列
                if ($isRowspanIndex) {
                    $deptName = $this->getFullDepartmentName($user['dept_id']);
                    array_unshift($row, ['data' => $deptName, 'rowspan' => $rowspan]);
                    array_splice($row, $ratioIndex, 0, [['data' => $deptRatio, 'rowspan' => $rowspan]]);
                }
                if(!$isExport) {
                    $options = $this->getStatOptions($userId, $deptId, $vacation, $isRowspanIndex, $isOneStat);
                    foreach($row as $columnKey => $column){
                        $row[$columnKey]['option'] = $options[$columnKey] ?? [];
                    }
                }
                foreach ($stat['calendar'] as $key =>  $item) {
                    array_push($row, ['data' => $this->getAttendResultString($item), 'option' => ['user_id' => $userId, 'date' => $key ,'type' => 'calendar', 'click' => false]]);
                }
                
                $rows[] = $isExport ? $row : $this->arrayValueToString($row, 'data');
            }
        }
        return $rows;
    }
    private function arrayValueToString($array, $key)
    {
        return array_map(function($item) use($key) {
            $item[$key] = strval($item[$key]);
            return $item;
        }, $array);
    }
    private function getStatOptions($userId, $deptId, $vacation, $isRowspanIndex, $isOneStat = false)
    {
        $leaveOption = [];
        if($isOneStat) {
            foreach ($vacation as $vacationId => $name) {
                $leaveOption[] = ['user_id' => $userId, 'type' => 'leave','leave_type' => $vacationId, 'click' => true];
            }
            $options = [
                ['user_id' => $userId, 'type' => 'user', 'click' => true],
                ['user_id' => $userId, 'type' => 'must_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'real_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'no_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'attend_ratio', 'click' => false],
                ['user_id' => $userId, 'type' => 'lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'seriously_lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'seriously_lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave_early', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave_early', 'click' => true],
                ['user_id' => $userId, 'type' => 'absenteeism', 'click' => true],
                ['user_id' => $userId, 'type' => 'no_sign_out', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave','leave_type' => 'all', 'click' => true],
                ['user_id' => $userId, 'type' => 'trip', 'click' => true],
                ['user_id' => $userId, 'type' => 'out', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'all', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'normal', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'rest', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'holiday', 'click' => true]
            ];
            array_splice($options, 14, 0, $leaveOption); // 第14列插入请假明细列
        } else {
            foreach ($vacation as $vacationId => $name) {
                $leaveOption[] = ['user_id' => $userId, 'type' => 'leave','leave_type' => $vacationId, 'click' => true];
                $leaveOption[] = ['user_id' => $userId, 'type' => 'leave','leave_type' => $vacationId, 'click' => true];
            }
            $options = [
                ['user_id' => $userId, 'type' => 'user', 'click' => true],
                ['user_id' => $userId, 'type' => 'must_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'must_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'real_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'real_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'no_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'no_attend', 'click' => true],
                ['user_id' => $userId, 'type' => 'attend_ratio', 'click' => false],
                ['user_id' => $userId, 'type' => 'lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'seriously_lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'seriously_lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'seriously_lag', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave_early', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave_early', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave_early', 'click' => true],
                ['user_id' => $userId, 'type' => 'absenteeism', 'click' => true],
                ['user_id' => $userId, 'type' => 'absenteeism', 'click' => true],
                ['user_id' => $userId, 'type' => 'no_sign_out', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave','leave_type' => 'all', 'click' => true],
                ['user_id' => $userId, 'type' => 'leave','leave_type' => 'all', 'click' => true],
                ['user_id' => $userId, 'type' => 'trip', 'click' => true],
                ['user_id' => $userId, 'type' => 'trip', 'click' => true],
                ['user_id' => $userId, 'type' => 'out', 'click' => true],
                ['user_id' => $userId, 'type' => 'out', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'all', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'all', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'normal', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'normal', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'rest', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'rest', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'holiday', 'click' => true],
                ['user_id' => $userId, 'type' => 'overtime','overtime_type'=>'holiday', 'click' => true]
            ];
            array_splice($options, 22, 0, $leaveOption); // 第18列插入请假明细列
        }

        if ($isRowspanIndex) {
            array_unshift($options, ['dept_id' => $deptId, 'type' => 'dept', 'click' => false]);
            if($isOneStat) {
                array_splice($options, 6, 0, [['dept_id' => $deptId, 'type' => 'attend_ratio', 'click' => false]]);
            } else {
                array_splice($options, 9, 0, [['dept_id' => $deptId, 'type' => 'attend_ratio', 'click' => false]]);
            }
        }
        return $options;
    }
    private function getDangerStyle($number)
    {
        if($this->isExportStat) {
            $dangerStyle = ['color' => '#ffffff', 'background' => '#F04134'];
        } else {
            $dangerStyle = ['color' => '#ffffff', 'background' => '#F04134', 'fontWeight' => 'bold'];
        }

        return $number > 0 ? $dangerStyle : [];
    }
    private function calculateRatio($total, $number)
    {
        return ($total > 0 ? round(($number / $total) * 100, $this->hourPrecision): 0);
    }
    private function splitOvertime($startDate, $endDate, $userId, $overtimeRecords, $schedulingDates, $isSalary = false)
    {
        $restDate = array_column($this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId], 'holiday')[$userId], 'scheduling_date');
        if (count($overtimeRecords) === 0) {
            return [0, 0, 0, 0, 0, 0];
        }
        $normalDays = $normalHours = $restDays = $restHours = $holidayDays = $holidayHours = 0;
        foreach ($overtimeRecords as $item) {
            $date = $this->formatDate($item->year, $item->month, $item->day);
            $ratio = $isSalary ? $item->ratio : 1;
            if (in_array($date, $schedulingDates)) {
                $normalDays += $item->overtime_days * $ratio;
                $normalHours += $item->overtime_hours * $ratio;
            } else if (in_array($date, $restDate)) {
                $holidayDays += $item->overtime_days * $ratio;
                $holidayHours += $item->overtime_hours * $ratio;
            } else {
                $restDays += $item->overtime_days * $ratio;
                $restHours += $item->overtime_hours * $ratio;
            }
        }

        return [$normalDays, $normalHours, $restDays, $restHours, $holidayDays, $holidayHours];
    }

    private function getRowspan($deptId, $rowIndex, $userGroupWidthDetp)
    {
        $rowspanTemp = $userGroupWidthDetp[$deptId];
        $isRowspanIndex = $rowspanTemp['start'] == $rowIndex;
        return [$isRowspanIndex, $rowspanTemp['ratio'], $isRowspanIndex ? $rowspanTemp['length'] : 1];
    }
    /**
     * 以考勤日期为键组装考勤记录
     *
     * @param type $records
     * @param type $userId
     *
     * @return string
     */
    private function combileAttendRecordsMapWidthSignDate($records, $userId)
    {
        $mapWithDate = [];
        foreach ($records as $record) {
            $shift = $this->getShiftById($record->shift_id);
            if ($shift) {
                if ($shift->shift_type == 2) {
                    $mapWithDate[$record->sign_date]['attend_time'] = $this->getSwapShiftTime($record->sign_date, $record->shift_id, $shift->attend_time, $userId);
                    $mapWithDate[$record->sign_date]['items'][$record->sign_times] = $record;
                    $mapWithDate[$record->sign_date]['type'] = 'swap';
                } else {
                    $mapWithDate[$record->sign_date]['attend_time'] = $shift->attend_time;
                    $mapWithDate[$record->sign_date]['items'] = $record;
                    $mapWithDate[$record->sign_date]['type'] = 'normal';
                }
            }
        }
        return $mapWithDate;
    }
    /**
     * 考勤记录计算统计，结果按用户id映射
     *
     * @param type $startDate
     * @param type $endDate
     * @param type $userIds
     * @param type $leaveStats
     * @param type $outStats
     * @param type $tripStats
     * @param type $realAttendDate
     *
     * @return array
     */
    public function recordsStatMapWithUserIds($schedulingDates, $startDate, $endDate, $userIds, $statCalendar = true)
    {
        $allowNoSignOutUserIdsMap = app($this->attendanceSettingService)->getAllowNoSignOutUserIdsMap($userIds);
        // 兼容跨天班，结束日期往后推一天
        $nextEndDate = $this->getNextDate($endDate);

        list($leavesGroup, $outsGroup, $tripsGroup, $offsetDatetimeGroup) = $this->getOffsetDatetimeGroup($startDate, $nextEndDate, $userIds, false);

        $overtimeGroup = $this->getOutSendDataGroupByDateRange($this->attendanceOvertimeRepository, 'getOvertimeRecordsByDateScopeAndUserIds', $startDate, $nextEndDate, $userIds, 'overtime');

        $attendRecordsMap = $this->getMoreUsersAttendRecordsMap($startDate, $endDate, $userIds);
        $dates = $this->getDateFromRange($startDate, $endDate);
        $statMap = [];
        foreach ($attendRecordsMap as $userId => $items) {
            $oneSchudulingDates = $schedulingDates[$userId] ?? [];
            $offsetDatetimes = $offsetDatetimeGroup[$userId] ?? [];
            $outSendData = [
                'leave' => $leavesGroup[$userId] ?? [],
                'out' => $outsGroup[$userId] ?? [],
                'trip' => $tripsGroup[$userId] ?? [],
                'overtime' => $overtimeGroup[$userId] ?? []
            ];
            $allowNoSignOut = $allowNoSignOutUserIdsMap[$userId] ?? false;
            $statMap[$userId] = $this->handleOneUserStat($oneSchudulingDates, $items, $offsetDatetimes, $outSendData, $dates, $allowNoSignOut, $statCalendar);
        }

        return $statMap;
    }
    private function getMoreUsersAttendRecordsMap($startDate, $endDate, $userIds)
    {
        // 获取按用户分组的考勤记录
        $recordFields = [
            'user_id', 'sign_date', 'sign_in_time', 'sign_in_normal', 'sign_out_time', 
            'sign_out_normal', 'lag_time', 'leave_early_time', 'is_lag', 'is_leave_early', 'sign_times',
            'shift_id', 'calibration_status', 'calibration_time','is_repair','repair_time'
        ];
        $recordParams = ['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userIds, 'in'], 'attend_type' => [1]];
        $recordsGroup = $this->arrayGroupWithKeys(app($this->attendanceRecordsRepository)->getRecords($recordParams, $recordFields));
        $map = $this->makeEmptyArrayMap($userIds);
        if (!empty($recordsGroup)) {
            // 按考勤日历再次将考勤记录分组
            foreach ($recordsGroup as $userId => $records) {
                $map[$userId] = $this->combileAttendRecordsMapWidthSignDate($records, $userId);
            }
        }
        return $map;
    }
    /**
     * 处理一个用户的统计
     *
     * @param type $oneSchudulingDates
     * @param type $items
     * @param type $offsetDatetimes
     * @param type $allowNoSignOut
     *
     * @return array
     */
    private function handleOneUserStat($schudulingDates, $recordsGroup, $offsetDatetimes, $outSendData, $dates, $allowNoSignOut = false, $statCalendar = true)
    {
        $stat = [
            'attend_days'  => 0,// 应出勤天数
            'attend_time' => 0,//应出勤时间
            'real_attend_days' => 0,//真实出勤天数
            'real_attend_time' => 0,//真实出勤时间
            'absenteeism_count' => 0,
            'absenteeism_days' => 0, // 旷工天数
            'absenteeism_time' => 0, // 旷工时间
            'calibration_count' => 0,
            'calibration_days' => 0,
            'calibration_time' => 0, 
            'lag_count' => 0,
            'lag_days' => 0,
            'lag_time' => 0,
            'seriously_lag_count' => 0,
            'seriously_lag_days' => 0,
            'seriously_lag_time' => 0,
            'leave_early_count' => 0,
            'leave_early_days' => 0,
            'leave_early_time' => 0,
            'repair_count' => 0,
            'repair_days' => 0,
            'repair_time' => 0,
            'no_sign_out' => 0
        ];
        $calendar = [];
        if (count($schudulingDates) > 0) {
            foreach ($schudulingDates as $schedulingItem) {
                $signDate = $schedulingItem['scheduling_date'];
                $shiftId = $schedulingItem['shift_id'];
                // 大于当前日期的不统计
                if ($signDate > $this->currentDate) {
                    $calendar[$signDate] = ['-'];
                    continue;
                }
                $this->sumTotal($stat['attend_days']);//应出勤天数
                $calendarItem = [];
                if ($statCalendar) {
                    foreach (['leave', 'out', 'trip', 'overtime'] as $type) {
                        if (isset($outSendData[$type][$signDate])) {
                            $calendarItem[] = $type;
                        }
                    }
                }
                if (isset($recordsGroup[$signDate])) {
                    $recordGroup = $recordsGroup[$signDate];
                    if ($recordGroup['type'] === 'swap') {
                        // 交换班
                        $records = $recordGroup['items'];
                        list ($shiftTimes, $attendTime) = $recordGroup['attend_time'];
                        $this->sumTotal($stat['attend_time'], $attendTime);
                        $swapRealAttendTime = 0; //交换班所有时间段的出勤时间
                        $absCount = 0;
                        $absDayTotal = 0;
                        foreach ($shiftTimes as $key => $shiftTime) {
                            if (isset($records[$key + 1])) {
                                $record = $records[$key + 1];
                                $oneStat = $this->statOneShiftAttend($record, $attendTime, $shiftId, $signDate, $offsetDatetimes, $allowNoSignOut, false, $shiftTime);
                                $this->combineOneShiftStatResult($stat, $calendarItem, $oneStat, $record, $attendTime, $shiftId, $shiftTime, $signDate, $swapRealAttendTime, function($absDay) use (&$absCount, &$absDayTotal){
                                    $absDayTotal += $absDay;// 统计多排版旷工天数。
                                    $absCount += 1; // 统计多排版旷工次数。
                                });
                            } else {
                                //矿工时间计算
                                list($absDay, $absTime) = $this->statAbsenteeism($offsetDatetimes, $signDate, $attendTime, function() use($shiftTime) {
                                    return [$shiftTime];
                                }, function($shiftTimes, $signDate) {
                                    $shiftTime = $shiftTimes[0];
                                    $signTime = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                                    return [$signTime];
                                }, function($attendTime) use($shiftTime, $signDate) {
                                    list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                                    return $this->calcDay(strtotime($signOutNormal) - strtotime($signInNormal), $attendTime);
                                }, function($signInNormal, $signOutNormal) {
                                    return strtotime($signOutNormal) - strtotime($signInNormal);
                                });
                                
                                if ($absTime == 0) {
                                    list($oneRealAttendTime, $oneRealAttendDay) = $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, [$shiftTime], null, false);
                                    $this->sumTotal($swapRealAttendTime, $oneRealAttendTime);
                                } else {
                                    $calendarItem[] = 'abs';
                                    list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                                    $absTime = strtotime($signOutNormal) - strtotime($signInNormal);
                                    $absDayTotal += $absDay;// 统计多排版旷工天数。
                                    $absCount += 1; // 统计多排版旷工次数。
                                    $this->sumTotal($stat['absenteeism_time'], $absTime);// 统计矿工时长
                                }
                            }
                        }
                        if ($absDayTotal) {
                            if ($absCount === sizeof($shiftTimes)) {
                                $this->sumTotal($stat['absenteeism_days'], 1); // 统计矿工天数
                            } else {
                                $this->sumTotal($stat['absenteeism_days'], $absDayTotal); // 统计矿工天数
                            }
                        }
                        $this->sumTotal($stat['real_attend_time'], $swapRealAttendTime);
                        $this->sumTotal($stat['real_attend_days'], $this->calcDay($swapRealAttendTime, $attendTime)); // 统计真实出勤天数
                    } else {
                        // 普通班
                        $record = $recordGroup['items'];
                        $attendTime = $recordGroup['attend_time'];
                        $this->sumTotal($stat['attend_time'], $attendTime);// 应出勤时间
                        $oneStat = $this->statOneShiftAttend($record, $attendTime, $shiftId, $signDate, $offsetDatetimes, $allowNoSignOut);
                        $this->combineOneShiftStatResult($stat, $calendarItem, $oneStat, $record, $attendTime, $shiftId);
                    }
                } else {
                    //没有打卡记录
                    $shift = $this->getShiftById($shiftId);
                    $attendTime = $shift ? $shift->attend_time : 0;
                    $this->sumTotal($stat['attend_time'], $attendTime);// 应出勤时间
                    //矿工时间计算
                    list($absDay, $absTime) = $this->statAbsenteeism($offsetDatetimes, $signDate, $attendTime, function() use($shiftId) {
                        return $this->getShiftTimeById($shiftId);
                    }, function($shiftTimes) use($shift, $signDate) {
                        return $this->getEffectShiftTimes($shiftTimes, $shift, $signDate);
                    });
                    $this->sumTotal($stat['absenteeism_days'], $absDay); // 统计矿工天数
                    $this->sumTotal($stat['absenteeism_time'], $absTime);// 统计矿工时长
                    if ($absTime == 0) {
                        // 统计实际出勤时间
                        list($oneRealAttendTime, $oneRealAttendDay) = $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, $this->getShiftTimeById($shiftId), $shiftId, $shift && $shift->shift_type == 1);
                        $this->sumTotal($stat['real_attend_time'], $oneRealAttendTime);
                        $this->sumTotal($stat['real_attend_days'], $oneRealAttendDay);
                    } else {
                        $calendarItem[] = 'abs';
                    }
                }
                if ($statCalendar) {
                    $calendar[$signDate] = array_unique($calendarItem);
                }
            }
        }
        // 天精度设置
        $daysStat = [
            'attend_days', 'real_attend_days', 'absenteeism_days', 'calibration_days', 
            'lag_days', 'leave_early_days', 'repair_days','seriously_lag_days'
        ];
        array_walk($stat, function(&$item, $key) use ($daysStat) {
            if (in_array($key, $daysStat)) {
                $item = $this->roundDays($item);
            }
        });
        // 计算未出勤天数，时长
        $stat['no_attend_days'] = $this->roundDays($stat['attend_days'] - $stat['real_attend_days']);
        $stat['no_attend_time'] = $stat['attend_time'] - $stat['real_attend_time'];
        // 计算相关考勤数据的小时数
        $hoursStat = [
            'attend_hours' => 'attend_time',
            'real_attend_hours' => 'real_attend_time',
            'absenteeism_hours' => 'absenteeism_time',
            'calibration_hours' => 'calibration_time',
            'lag_hours' => 'lag_time',
            'seriously_lag_hours' => 'seriously_lag_time',
            'leave_early_hours' => 'leave_early_time',
            'repair_hours' => 'repair_time',
            'no_attend_hours' => 'no_attend_time'
        ];
        // 小时数精度设置
        foreach ($hoursStat as $hourKey => $timeKey) {
            $stat[$hourKey] = $this->calcHours($stat[$timeKey]);
        }
        if ($statCalendar) {
            $allCalendar = [];
            foreach ($dates as $date) {
                if (isset($calendar[$date])) {
                    $allCalendar[$date] = empty($calendar[$date]) ? ['ok'] : $calendar[$date];
                    continue;
                } 
                if ($date > $this->currentDate) {
                    $allCalendar[$date] = ['-'];
                    continue;
                }
                $allCalendar[$date] = isset($outSendData['overtime'][$date]) ? ['overtime'] : ['rest'];
                
            }
            $stat['calendar'] = $allCalendar;
        }
        return $stat;
    }
    private function combineOneShiftStatResult(&$stat, &$calendarItem, $oneStat, $record, $attendTime, $shiftId, $shiftTime = null, $signDate = null, &$swapTime = null,$absCallback=null)
    {
        if ($record->is_repair) {
            $calendarItem[] = 'repair';
            $this->sumTotal($stat['repair_time'], $record->repair_time);
            $this->sumTotal($stat['repair_days'], $this->calcDay($record->repair_time, $attendTime));
            $this->sumTotal($stat['repair_count']);
        }
        if ($swapTime !== null){
            $swapTime += $oneStat[11];
        } else {
            $this->sumTotal($stat['real_attend_time'], $oneStat[11]);
            $this->sumTotal($stat['real_attend_days'], $oneStat[10]);
        }
        if($oneStat[7]) {
            $this->sumTotal($stat['calibration_count'], $oneStat[7]);
            $this->sumTotal($stat['calibration_days'], $oneStat[8]);
            $this->sumTotal($stat['calibration_time'], $oneStat[9]);
            $calendarItem[] = 'calibration';
        }
        if($oneStat[1]) {
            $shift = $this->getShiftById($shiftId);
            if ($this->isAbsenteeismLag($shift, $oneStat[3])) {
                $this->sumTotal($stat['absenteeism_count']);
                if ($shiftTime) {
                    list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                    $absTime  = strtotime($signOutNormal) - strtotime($signInNormal);
                    $absDay = $this->calcDay($absTime, $attendTime);
                } else {
                    $absTime = $attendTime;
                    $absDay = 1;
                }
                if ($absCallback) {
                    $absCallback($absDay);
                } else {
                    $this->sumTotal($stat['absenteeism_days'], $absDay); // 统计矿工天数
                }
                $this->sumTotal($stat['absenteeism_time'], $absTime);// 统计矿工时长
                if ($swapTime !== null){
                    $swapTime -= $oneStat[11];
                } else {
                    $stat['real_attend_time'] -=$oneStat[11];
                    $stat['real_attend_days'] -=$oneStat[10];
                }
                $calendarItem[] = 'abs';
            } else if($this->isSeriouslyLag($shift, $oneStat[3])) {
                $this->sumTotal($stat['seriously_lag_count']);
                $this->sumTotal($stat['seriously_lag_days'], $oneStat[2]);
                $this->sumTotal($stat['seriously_lag_time'], $oneStat[3]);
                $calendarItem[] = 'seriously_lag';
            } else {
                $this->sumTotal($stat['lag_count'], $oneStat[1]);
                $this->sumTotal($stat['lag_days'], $oneStat[2]);
                $this->sumTotal($stat['lag_time'], $oneStat[3]);
                $calendarItem[] = 'lag';
            }
        }
        if($oneStat[4]) {
            $this->sumTotal($stat['leave_early_count'], $oneStat[4]);
            $this->sumTotal($stat['leave_early_days'], $oneStat[5]);
            $this->sumTotal($stat['leave_early_time'], $oneStat[6]);
            $calendarItem[] = 'early';
        }
        if($oneStat[0]) {
            $this->sumTotal($stat['no_sign_out'], $oneStat[0]);
            $calendarItem[] = 'no_sign_out';
        }
    }
    /**
     * 统计一个班次的考勤
     *
     * @param type $record
     * @param type $attendTime
     * @param type $shiftId
     * @param type $signDate
     * @param type $offsetDatetimes
     * @param type $allowNoSignOut
     * @param type $isNormal
     * @param type $shiftTime
     *
     * @return array
     */
    private function statOneShiftAttend($record, $attendTime, $shiftId, $signDate,  $offsetDatetimes, $allowNoSignOut = false, $isNormal = true, $shiftTime = null)
    {
        $noSignOut = 0;
        $lagTotal = 0;
        $lagDay = 0;
        $lagTime = 0;
        $realAttendTime = 0;
        $realAttendDay = 0;
        $earlyTotal = 0;
        $leaveEarlyTime = 0;
        $leaveEarlyDay = 0;
        $calibrationTotal = 0;
        $calibrationTime = 0;
        $calibrationDay = 0;
        $shiftTimes = $isNormal ? null : [$shiftTime];
        if ($record->is_offset) {
            // 没有签退的情况
            if ($record->sign_out_time == '') {
                $noSignOut = 1;
                list($lagTotal, $lagDay, $lagTime, $realAttendTime, $realAttendDay) = $this->statNoSignOutRecord($record, $attendTime, $signDate, $shiftId, $offsetDatetimes,$isNormal, $shiftTimes,$allowNoSignOut);
            } else {
                // 有签退的情况
                list($lagTotal, $lagDay, $lagTime, $earlyTotal, $leaveEarlyDay, $leaveEarlyTime, $calibrationTotal,$calibrationDay, $calibrationTime,  $realAttendTime, $realAttendDay) = $this->statOffsetHasSignOutRecord($record, $signDate, $attendTime, $isNormal);
            }
        } else {
            // 没有签退的情况
            if ($record->sign_out_time == '') {
                $noSignOut = 1;
                list($lagTotal, $lagDay, $lagTime, $realAttendTime, $realAttendDay) = $this->statNoSignOutRecord($record, $attendTime, $signDate, $shiftId, $offsetDatetimes,$isNormal, $shiftTimes,$allowNoSignOut);
            } else {
                // 有签退的情况
                list($lagTotal, $lagDay, $lagTime, $earlyTotal, $leaveEarlyDay, $leaveEarlyTime, $calibrationTotal, $calibrationDay, $calibrationTime, $realAttendTime, $realAttendDay) = $this->statHasSignOutRecord($record, $shiftId, $signDate, $offsetDatetimes, $attendTime, $isNormal, $shiftTimes);
            }
        }
        // 统计校准
        if ($record->calibration_status == 2) {
            $calibrationTotal = $record->calibration_count == 0 ? 1 : $record->calibration_count;
            $calibrationTime = intval($record->calibration_time);
            $calibrationDay = $this->calcDay($calibrationTime, $attendTime);
        }
        return [$noSignOut, $lagTotal, $lagDay, $lagTime, $earlyTotal, $leaveEarlyDay, $leaveEarlyTime, $calibrationTotal, $calibrationDay, $calibrationTime, $realAttendDay, $realAttendTime];

    }
    /**
     * 统计有签退的历史记录
     *
     * @param type $record
     * @param type $signDate
     * @param type $attendTime
     * @param type $isNormal
     *
     * @return array
     */
    private function statOffsetHasSignOutRecord($record, $signDate, $attendTime, $isNormal = true)
    {
        $calibrationTotal = $calibrationDay = $calibrationTime = 0;
        list($lagTotal, $lagDay, $lagTime) = $this->statAttendLag($record, $attendTime);
        // 统计早退
        list($earlyTotal, $leaveEarlyDay, $leaveEarlyTime) = $this->statAttendLeaveEarly($record, $attendTime);
        // 统计校准
        if ($record->calibration_status == 2) {
            $calibrationTotal = 1;
            $calibrationTime = intval($record->calibration_time);
            $calibrationDay = $this->calcDay($calibrationTime, $attendTime);
        }
        // 统计实际出勤时间
        list($realAttendTime, $realAttendDay) = $this->getRealAttendTime($record, $signDate, $attendTime, function($record, $signOutNormal) {
            return [$record->sign_in_time, $record->sign_out_time];
        }, $isNormal);

        return [$lagTotal, $lagDay, $lagTime, $earlyTotal, $leaveEarlyDay, $leaveEarlyTime, $calibrationTotal,$calibrationDay, $calibrationTime, $realAttendTime, $realAttendDay];
    }
    /**
     * 统计有签退的记录
     *
     * @param type $record
     * @param type $shiftId
     * @param type $signDate
     * @param type $offsetDatetimes
     * @param type $attendTime
     * @param type $isNormal
     * @param type $shiftTimes
     *
     * @return array
     */
    private function statHasSignOutRecord($record, $shiftId, $signDate, $offsetDatetimes, $attendTime, $isNormal = true, $shiftTimes = null)
    {
        $calibrationTotal = $calibrationTime = $calibrationDay = 0;
        // 统计迟到
        if($isNormal) {
            $shiftTimes = $this->getShiftTimeById($shiftId);
        }
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);

        if (empty($effectOffsetDateTimes)) {
            // 统计迟到
            list($lagTotal, $lagDay, $lagTime) = $this->statAttendLag($record, $attendTime);

            // 统计早退
            list($earlyTotal, $leaveEarlyDay, $leaveEarlyTime) = $this->statAttendLeaveEarly($record, $attendTime);
        } else {
            $fullRestTimes = $isNormal ? $this->getFullRestTime($shiftId, $record->sign_in_normal, $record->sign_out_normal, $signDate) : [];
            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
            // 统计迟到
            list($lagTotal, $lagDay, $lagTime) = $this->statAttendLag($record, $attendTime, function($record) use($fullRestTimes, $signInNormal, $effectOffsetDateTimes) {
                $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $signInNormal, $record->sign_in_time);
                $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
                return $record->lag_time - $intersetTime;
            });
            // 统计早退
            list($earlyTotal, $leaveEarlyDay, $leaveEarlyTime) = $this->statAttendLeaveEarly($record, $attendTime, function($record) use($fullRestTimes, $signOutNormal, $effectOffsetDateTimes) {
                $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $record->sign_out_time, $signOutNormal);
                $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
                return $record->leave_early_time - $intersetTime;
            });
        }
        // 统计校准
        if ($record->calibration_status == 2) {
            $calibrationTotal = 1;
            $calibrationTime = intval($record->calibration_time);
            $calibrationDay = $this->calcDay($calibrationTime, $attendTime);
        }
        // 统计实际出勤时间
        list($realAttendTime, $realAttendDay) = $this->getRealAttendTime($record, $signDate, $attendTime, function($record, $signOutNormal) {
            return [$record->sign_in_time, $record->sign_out_time];
        }, $isNormal, $offsetDatetimes);

        return [$lagTotal, $lagDay, $lagTime, $earlyTotal, $leaveEarlyDay, $leaveEarlyTime, $calibrationTotal, $calibrationDay, $calibrationTime,  $realAttendTime, $realAttendDay];
    }
    /**
     * 统计未签退的记录
     *
     * @param type $record
     * @param type $attendTime
     * @param type $signDate
     * @param type $shiftId
     * @param type $offsetDatetimes
     * @param type $isNormal
     * @param type $shiftTimes
     * @param type $allowNoSignOut
     *
     * @return array
     */
    private function statNoSignOutRecord($record, $attendTime, $signDate, $shiftId, $offsetDatetimes, $isNormal = true, $shiftTimes = null, $allowNoSignOut = false)
    {
        $lagTotal = $lagDay = $lagTime = $realAttendTime = $realAttendDay = 0;
        // 必须签退，否则不计入出勤时间
        if ($isNormal) {
            $shiftTimes = $this->getShiftTimeById($shiftId);
        }
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);
        // 统计迟到
        if (empty($effectOffsetDateTimes)) {
            list($lagTotal, $lagDay, $lagTime) = $this->statAttendLag($record, $attendTime);
        } else {
            $fullRestTimes = $isNormal ? $this->getFullRestTime($shiftId, $record->sign_in_normal, $record->sign_out_normal, $signDate) : [];
            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $record->sign_in_normal, $record->sign_out_normal);
            list($lagTotal, $lagDay, $lagTime) = $this->statAttendLag($record, $attendTime, function($record) use($fullRestTimes, $signInNormal, $effectOffsetDateTimes) {
                $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $signInNormal, $record->sign_in_time);
                $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
                return $record->lag_time - $intersetTime;
            });
        }
        list($realAttendTime, $realAttendDay) = $this->statNoSignOutRealAttendTime($record, $signDate, $attendTime, $shiftTimes, $offsetDatetimes, $shiftId, $isNormal, $allowNoSignOut);
        return [$lagTotal, $lagDay, $lagTime, $realAttendTime, $realAttendDay];
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
    private function statAbsenteeism($offsetDatetimes, $signDate, $attendTime, $getShiftTime, $getEffectShiftTime = false, $getShiftDay = false)
    {
        $shiftTimes = $getShiftTime();
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);
        $shiftDay = 1;
        if (is_callable($getShiftDay)) {
            $shiftDay = $getShiftDay($attendTime);
        }
        if (empty($effectOffsetDateTimes)) {
            return [$shiftDay, $attendTime];
        }
        if (is_callable($getEffectShiftTime)) {
            $effectShiftTimes = $getEffectShiftTime($shiftTimes, $signDate);
        } else {
            $effectShiftTimes = $shiftTimes;
        }
        $intersetTime = $this->getMoreDatetimeIntersetTime($effectShiftTimes, $effectOffsetDateTimes);
        if($intersetTime == 0) {
            return [$shiftDay, $attendTime];
        }
        return [0, 0];
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
    private function statAttendLeaveEarly($record, $attendTime, $handle = false)
    {
        if ($record->is_leave_early) {
            if(is_callable($handle)){
                $earlyTime = $handle($record);
            } else {
                $earlyTime = $record->leave_early_time;
            }
            if($earlyTime > 0) {
                return [1, $this->calcDay($earlyTime, $attendTime), $earlyTime];
            }
        }
        return [0, 0, 0];
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
    private function statAttendLag($record, $attendTime, $handle = false)
    {
        if ($record->is_lag) {
            if (is_callable($handle)) {
                $lagTime = $handle($record);
            } else {
                $lagTime = $record->lag_time;
            }
            if ($lagTime > 0) {
                return [1, $this->calcDay($lagTime, $attendTime), $lagTime];
            }
        }
        return [0, 0, 0];
    }
    /**
     * 获取交换班排班时间
     *
     * @staticvar array $swapShiftTime
     * @param type $signDate
     * @param type $shiftId
     * @param type $attendTime
     * @param type $userId
     *
     * @return array
     */
    private function getSwapShiftTime ($signDate, $shiftId, $attendTime, $userId)
    {
        static $swapShiftTime = [];

        if (isset($swapShiftTime[$userId . $signDate])) {
            return $swapShiftTime[$userId . $signDate];
        }
        return $swapShiftTime[$userId . $signDate] = [$this->getShiftTimeById($shiftId), $attendTime];
    }
}