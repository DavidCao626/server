<?php

namespace App\EofficeApp\Attendance\Services;

use App\EofficeApp\Attendance\Traits\AttendanceStatTrait;
class AttendanceRecordsService extends AttendanceBaseService
{
    use AttendanceStatTrait;
    private $calibrationConfig;

    public function __construct()
    {
        parent::__construct();
    }

    public function oneAttendRecords($param, $userId)
    {
        $param = $this->parseParams($param);
        $year = $this->defaultValue('year', $param, date('Y'));
        $month = intval($this->defaultValue('month', $param, date('m')));
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $startDate = $this->formatDate($year, $month, 1);
        $endDate = $this->formatDate($year, $month, $days);
        $endNextDate = $this->getNextDate($endDate);
        $allowNoSignOut = $this->allowNoSignOut($userId); // 是否允许漏签
        /**
         * 获取当月排班日期
         */
        $schedulingDateMap = $this->schedulingMapWithUserIdsByDateScope($startDate, $endNextDate, [$userId]);
        $schedulingKeyMap = $this->arrayMapWithKeys($schedulingDateMap[$userId], 'scheduling_date', 'shift_id');
        // 获取请假，外出，出差，加班记录
        list($leaves, $outs, $trips) = $this->getOneUserLOTByDateScope($startDate, $endDate, $userId);
        $overtimes = app($this->attendanceOvertimeLogRepository)->getAttendOvertimes($startDate, $endNextDate, $userId);
        list($leavesGroup, $outsGroup, $tripsGroup, $overtimesGroup) = $this->groupOutSendData(['leave' => $leaves, 'out' => $outs, 'trip' => $trips, 'overtime' => $overtimes]);
        // 合并请假，外出时间，主要用于考勤抵消
        $offsetDatetimes = $this->getOffsetDatetimes($leaves, $outs, $trips, $userId);
        $this->calibrationConfig = app($this->attendanceSettingService)->getCommonSetting('calibration');
        /**
         * 获取当月考勤记录
         */
        $recordMap = $this->arrayGroupWithKeys(app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endNextDate], 'between'], 'user_id' => [$userId]]), 'sign_date');
        $result = [];
        for ($day = 1; $day <= $days; $day++) {
            $signDate = $this->formatDate($year, $month, $day);
            $result[$signDate] = $this->handleOneRecordForOne($schedulingKeyMap, $signDate, $recordMap, $leavesGroup, $outsGroup, $tripsGroup, $overtimesGroup, $offsetDatetimes, $allowNoSignOut);
        }
        $result[$endNextDate] = $this->handleOneRecordForOne($schedulingKeyMap, $endNextDate, $recordMap, $leavesGroup, $outsGroup, $tripsGroup, $overtimesGroup, $offsetDatetimes, $allowNoSignOut);
        return ['total' => $days + 1, 'list' => $result, 'vacation' => $this->getLeaveVacation()[0]];
    }

    private function handleOneRecordForOne($schedulingKeyMap, $signDate, $recordMap, $leavesGroup, $outsGroup, $tripsGroup, $overtimesGroup, $offsetDatetimes, $allowNoSignOut = false)
    {
        $shiftId = $schedulingKeyMap[$signDate] ?? null;
        $row = [
            'week' => date("w", strtotime($signDate)),
            'is_current' => $signDate == $this->currentDate,
            'shift' => null,
            'sign' => null,
            'leave' => [],
            'out' => [],
            'trip' => [],
            'overtime' => [],
            'result' => null
        ];
        $shift = $this->getShiftById($shiftId);
        if ($shift) {
            $shiftTimes = $this->getShiftTimeById($shiftId);
            $attendTime = $shift->attend_time;
            $row['shift'] = [
                'shift_id' => $shiftId,
                'shift_type' => $shift->shift_type,
                'attend_time' => $attendTime,
                'shift_name' => $shift->shift_name,
                'shift_times' => $shiftTimes,
                'rest_times' => $this->getRestTime($shiftId, $shift->shift_type == 1)
            ];
            if (isset($recordMap[$signDate])) {
                $records = $recordMap[$signDate];
                if (count($shiftTimes) > 1) {
                    $recordsGroup = $this->arrayMapWithKeys($records, 'sign_times');
                    foreach ($shiftTimes as $key => $shiftTime) {
                        $number = $key + 1;
                        if (isset($recordsGroup[$number])) {
                            $record = $recordsGroup[$number];
                            $handleResult = $this->handleOneShiftRecord($record, $signDate, $attendTime, $offsetDatetimes, $shiftId, [$shiftTime], $allowNoSignOut, false);
                            // 解析并组合考勤结果
                            $result = $this->combineAttendResult($shift, $record, $handleResult['early_time'], $handleResult['lag_time']);
                            $row['result'][$number] = empty($result) ? null : $result;
                            $row['sign'][$number] = $this->getSignArray($record);
                        } else {
                            // 判断当前班次是否旷工
                            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                            $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, [$shiftTime], [[$signInNormal, $signOutNormal]]);
                            if ($absOffsetTime == 0) {
                                $row['result'][$number] = ['absenteeism'];
                            } else {
                                $row['result'][$number] = $this->isOffsetAll($signInNormal, $signOutNormal, $absOffsetTime) ? ['no_offset_all'] : null;
                            }
                        }
                    }
                } else {
                    $row['sign'] = $this->getSignArray($records[0]);
                    $handleResult = $this->handleOneShiftRecord($records[0], $signDate, $attendTime, $offsetDatetimes, $shiftId, null, $allowNoSignOut, true);
                    $result = $this->combineAttendResult($shift, $records[0], $handleResult['early_time'], $handleResult['lag_time']);
                    $row['result'] = empty($result) ? null : $result;
                }
            } else {
                // 没有考勤记录，判断是否超过当前日期，小于当前日期判断是否矿工
                if ($signDate > $this->currentDate) {
                    $row['result'] = 'empty';
                } else {
                    if (count($shiftTimes) > 1) {
                        foreach ($shiftTimes as $key => $shiftTime) {
                            $number = $key + 1;
                            // 判断当前班次是否旷工
                            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                            $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, [$shiftTime], [[$signInNormal, $signOutNormal]]);
                            if ($absOffsetTime == 0) {
                                $row['result'][$number] = ['absenteeism'];
                            } else {
                                $row['result'][$number] = $this->isOffsetAll($signInNormal, $signOutNormal, $absOffsetTime) ? ['no_offset_all'] : null;
                            }
                        }
                    } else {
                        $effectShiftTimes = $this->getEffectShiftTimes($shiftTimes, $shift, $signDate);
                        $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, $shiftTimes, $effectShiftTimes);
                        if ($absOffsetTime == 0) {
                            $row['result'] = ['absenteeism'];
                        } else {
                            $row['result'] = $attendTime - $absOffsetTime > 0 ? ['no_offset_all'] : null;
                        }
                    }
                }
            }
        } else { 
            // 非工作日
            if (isset($recordMap[$signDate])) {
                $row['sign'] = $this->getSignArray($recordMap[$signDate][0]);
            }
            $row['result'] = 'rest';
        }
        //获取请假记录
        $row['leave'] = $leavesGroup[$signDate] ?? [];
        //获取加班记录
        $row['out'] = $outsGroup[$signDate] ?? [];
        //获取加班记录
        $row['trip'] = $tripsGroup[$signDate] ?? [];
        //获取加班记录
        $row['overtime'] = $overtimesGroup[$signDate] ?? [];
        $row['timeline'] = $this->getSignTimeLine($signDate, $schedulingKeyMap, $recordMap, $leavesGroup, $outsGroup, $tripsGroup, $overtimesGroup);
        return $row;
    }
    private function isOffsetAll($signInNormal, $signOutNormal, $offsetTime) 
    {
        return strtotime($signOutNormal) - strtotime($signInNormal) - $offsetTime > 0;
    }
    private function getSignTimeLine($signDate, $schedulingKeyMap, $recordMap, $leavesGroup, $outsGroup, $tripsGroup, $overtimesGroup)
    {
        $timeLine = [
            'shift' => null,
            'sign' => null,
            'leave' => null,
            'out' => null,
            'trip' => null,
            'overtime' => null
        ];
        $prvSignDate = $this->getPrvDate($signDate);
        $fullStartSignDate = $this->combineDatetime($signDate, '00:00:00');
        $fullEndSignDate = $this->combineDatetime($signDate, '23:59:59');
        if (isset($schedulingKeyMap[$prvSignDate])) {
            $shiftId = $schedulingKeyMap[$prvSignDate];
            $shiftTimes = $this->getShiftTimeById($shiftId);
            $shiftTimesCount = count($shiftTimes);
            if ($shiftTimesCount > 0) {
                $lastShiftTime = $shiftTimes[$shiftTimesCount - 1];
                // 判断是否为跨天班
                if ($lastShiftTime->sign_in_time > $lastShiftTime->sign_out_time) {
                    $timeLine['shift']['prv'] = $this->getTimeLineStyle($signDate, $fullStartSignDate, $signDate . ' ' . $this->getFullTime($lastShiftTime->sign_out_time, false));
                }
            }
        } else {
            $shiftTimesCount = 1;
        }
        if (isset($recordMap[$prvSignDate])) {
            $records = $recordMap[$prvSignDate];
            if ($shiftTimesCount > 1) {
                $recordsGroup = $this->arrayMapWithKeys($records, 'sign_times');
                $lastRecord = $recordsGroup[count($shiftTimes)] ?? null;
            } else {
                $lastRecord = $records[0];
            }
            if ($lastRecord) {
                $signInTime = $lastRecord->original_sign_in_time;
                $signOutTime = $lastRecord->original_sign_out_time;
                if ($signOutTime && $this->format($signOutTime, 'Y-m-d') == $signDate) {
                    $startDatetime = max($signInTime, $fullStartSignDate);
                    $timeLine['sign']['prv'] = $this->getTimeLineStyle($signDate, $startDatetime, $lastRecord->sign_out_time);
                }
            }
        }
        if (isset($schedulingKeyMap[$signDate])) {
            $shiftId = $schedulingKeyMap[$signDate];
            $shiftTimes = $this->getShiftTimeById($shiftId);
            foreach ($shiftTimes as $shiftTime) {
                $signInNormal = $shiftTime->sign_in_time;
                $signOutNormal = $shiftTime->sign_out_time;
                $end = $signInNormal > $signOutNormal ? $fullEndSignDate : $this->combineDatetime($signDate, $this->getFullTime($signOutNormal, false));
                $timeLine['shift']['current'][] = $this->getTimeLineStyle($signDate, $this->combineDatetime($signDate, $this->getFullTime($signInNormal, false)), $end);
            }
        }
        // 获取打卡时间线
        if (isset($recordMap[$signDate])) {
            foreach ($recordMap[$signDate] as $record) {
                $signInTime = $record->original_sign_in_time;
                $signOutTime = $record->original_sign_out_time;
                if ($this->format($signInTime, 'Y-m-d') == $signDate && $signOutTime) {
                    $end = $this->format($signOutTime, 'Y-m-d') > $signDate ? $fullEndSignDate : $signOutTime;
                    $timeLine['sign']['current'][$record->sign_times] = $this->getTimeLineStyle($signDate, $signInTime, $end);
                }
            }
        }
        $timeLine['leave'] = $this->getLOTOTimeLineStyle($leavesGroup, $signDate, 'leave_start_time', 'leave_end_time');
        $timeLine['out'] = $this->getLOTOTimeLineStyle($outsGroup, $signDate, 'out_start_time', 'out_end_time');
        $timeLine['trip'] = $this->getLOTOTimeLineStyle($tripsGroup, $signDate, 'trip_start_date', 'trip_end_date');
        $timeLine['overtime'] = $this->getLOTOTimeLineStyle($overtimesGroup, $signDate, 'overtime_start_time', 'overtime_end_time');
        return $timeLine;
    }

    private function getLOTOTimeLineStyle($group, $signDate, $startKey, $endKey)
    {
        if (isset($group[$signDate])) {
            $style = [];
            foreach ($group[$signDate] as $item) {
                $start = max($item->{$startKey}, $signDate . ' 00:00:00');
                $end = min($item->{$endKey}, $signDate . ' 23:59:59');
                $style[] = $this->getTimeLineStyle($signDate, $start, $end);
            }
            return $style;
        }
        return null;
    }

    private function getTimeLineStyle($date, $start, $end)
    {
        $widthSecond = max(strtotime($end) - strtotime($start), 60);
        $width = ($widthSecond / 86400) * 100;
        $left = ((strtotime($start) - strtotime($date)) / 86400) * 100;
        return [
            'left' => $left,
            'width' => $width
        ];
    }

    private function getSignArray($record)
    {
        $count = $this->calibrationConfig['calibration_count_pre_record'] ? $this->calibrationConfig['calibration_count_pre_record'] : 0;
        $calibrationCount = $record->calibration_count ? $record->calibration_count : 0;
        $lastCount = max($count - $calibrationCount, 0);
        $countLimit = $this->calibrationConfig['limit_calibration_count_pre_record'] ? $this->calibrationConfig['limit_calibration_count_pre_record'] : false;
        $fullSignInTime = $record->original_sign_in_time ? $record->original_sign_in_time : $record->sign_in_time;// 代码健壮性处理
        $fullSignOutTime = $record->original_sign_out_time;
        list($signInDate, $signInTime) = explode(' ', $fullSignInTime);
        $sign = [
            'record_id' => $record->record_id,
            'calibration_status' => $record->calibration_status,
            'last_calibration_count' => $lastCount,
            'calibration_count_limit' => $countLimit,
            'sign_in' => $this->combineOneSignInOrOut($record->in_ip, $record->in_address, $record->in_platform, $signInTime, $record->sign_date, $signInDate),
            'sign_out' => []
        ];
        if ($fullSignOutTime) {
            list($signOutDate, $signOutTime) = explode(' ', $fullSignOutTime);
            $sign['sign_out'] = $this->combineOneSignInOrOut($record->out_ip, $record->out_address, $record->out_platform, $signOutTime, $record->sign_date, $signOutDate);
        }
        return $sign;
    }
    private function combineOneSignInOrOut($ip, $address, $platform, $showSignTime, $signDate = '', $showSignDate = '')
    {
        return [
            'sign_time' => ($signDate && $showSignDate && $showSignDate > $signDate) ? $showSignTime . '[次日]' : $showSignTime,
            'ip' => $ip,
            'address' => $address,
            'platform' => $this->transPlatform($platform)
        ];
    }
    public function moreAttendRecords($param, $own)
    {
        $viewUser = app($this->attendanceSettingService)->getPurviewUser($own, 52);
        if (empty($viewUser)) {
            return ['total' => 0, 'list' => []];
        }
        $param = $this->parseParams($param);
        $param = $this->combineMulitOrderBy($param);// 多字段排序处理
        $year = $this->defaultValue('year', $param, date('Y'));
        $month = intval($this->defaultValue('month', $param, date('m')));
        $day = intval($this->defaultValue('day', $param, date('d')));
        $type = $this->defaultValue('type', $param, 'dept');
        $signDate = $this->formatDate($year, $month, $day);
        if ($type == 'dept') {
            if ($viewUser != 'all') {
                $param['user_id'] = $viewUser;
            }
            if (isset($param['dept_id'])) {
                $param['dept_id'] = json_decode($param['dept_id']);
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
        $userIds = array_column($users, 'user_id');
        $records = app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [$signDate], 'user_id' => [$userIds, 'in']]); //获取所有的考勤记录
        $recordsGroup = $this->arrayGroupWithKeys($records);
        $schedulings = $this->schedulingMapWithUserIdsByDate($signDate, $userIds);
        $outStats = app($this->attendanceOutStatRepository)->getMoreUserOneDayStatsByDate($userIds, $signDate);
        $leaveStats = app($this->attendanceLeaveDiffStatRepository)->getMoreUserOneDayStatsByDate($userIds, $signDate);
        $overtimeStats = app($this->attendanceOvertimeStatRepository)->getMoreUserOneDayStatsByDate($userIds, $signDate);
        $tripStats = app($this->attendanceTripStatRepository)->getMoreUserOneDayStatsByDate($userIds, $signDate);
        $offsetDatetimeGroup = $this->getOffsetDatetimeGroup($signDate, $this->getNextDate($signDate), $userIds);// 兼容跨天班，结束日期往后推一天
        $allowNoSignOutUserIdsMap = app($this->attendanceSettingService)->getAllowNoSignOutUserIdsMap($userIds);
        $list = [];
        if ($total > 0) {
            foreach ($users as $user) {
                $userId = $user['user_id'];
                $shift = (isset($schedulings[$userId]) && $schedulings[$userId]) ? $this->getShiftById($schedulings[$userId]['shift_id']) : null;
                $oneUserRecords = $recordsGroup[$userId] ?? [];
                $offsetDatetimes = $offsetDatetimeGroup[$userId] ?? [];
                $outSendData = [
                    'out' => isset($outStats[$userId]) ? ($outStats[$userId][0] ?? null) : null,
                    'leave' => isset($leaveStats[$userId]) ? ($leaveStats[$userId][0] ?? null) : null,
                    'trip' => isset($tripStats[$userId]) ? ($tripStats[$userId][0] ?? null) : null,
                    'overtime' => isset($overtimeStats[$userId]) ? ($overtimeStats[$userId][0] ?? null) : null
                ];
                $allowNoSignOut = $allowNoSignOutUserIdsMap[$userId] ?? false;
                $list[] = $this->handleOneRecordForMore($user, $signDate, $oneUserRecords, $shift, $outSendData, $offsetDatetimes, $allowNoSignOut);
            }
        }
        return ['total' => $total, 'list' => $list];
    }
    public function handleOneRecordForMore($user, $signDate, $records, $shift, $outSendData, $offsetDatetimes, $allowNoSignOut = false)
    {
        $row = [
            'user_id' => $user['user_id'],
            'user_name' => $user['user_name'],
            'dept_name' => $user['dept_name'],
            'sign_date' => $signDate,
            'shift' => null,
            'attend_hours' => 0,
            'real_attend_hours' => 0,
            'lag_time' => 0,
            'seriously_lag_time' => 0,
            'early_time' => 0,
            'sign_in' => [],
            'sign_out' => [],
            'leave' => [],
            'trip' => [],
            'out' => [],
            'overtime' => [],
            'result' => [],
        ];
        foreach (['out', 'leave', 'overtime', 'trip'] as $outSendType) {
            if (isset($outSendData[$outSendType])) {
                $outSendItem = $outSendData[$outSendType];
                $row[$outSendType] = ['day' => $outSendItem->{$outSendType . '_days'}, 'hours' => $outSendItem->{$outSendType . '_hours'}];
            }
        }
        if ($shift) {
            $attendTime = $shift->attend_time;
            $shiftId = $shift->shift_id;
            $row['attend_hours'] = $this->calcHours($attendTime);
            $shiftTimes = $this->getShiftTimeById($shiftId);
            $row['shift'] = [
                'shift_name' => $shift->shift_name,
                'shift_type' => $shift->shift_type,
                'shift_times' => $shiftTimes
            ];
            if ($records) {
                // 有考勤记录，处理交换班和正常班
                if (count($shiftTimes) > 1) {
                    $recordsGroup = $this->arrayMapWithKeys($records, 'sign_times');
                    $lagTime = $earlyTime = $seriouslyLagTime = $realAttendTime =  0;
                    foreach ($shiftTimes as $key => $shiftTime) {
                        $number = $key + 1;
                        $result = [];
                        if (isset($recordsGroup[$number])) {
                            $record = $recordsGroup[$number];
                            $this->combineSignInOrOut($row, $record, $number);
                            $handleResult = $this->handleOneShiftRecord($record, $signDate, $attendTime, $offsetDatetimes, $shiftId, [$shiftTime], $allowNoSignOut, false);
                            $oneLagTime = $this->isLag($record);
                            if (!$oneLagTime || !$this->isAbsenteeismLag($shift, $oneLagTime)) {
                                $this->sumTotal($realAttendTime, $handleResult['real_attend_time']);
                            }
                            // 解析并组合考勤结果
                            $result = $this->combineAttendResult($shift, $record, $handleResult['early_time'], $handleResult['lag_time'], function($time) use(&$seriouslyLagTime) {
                                $this->sumTotal($seriouslyLagTime, $time);
                            }, function($time) use(&$lagTime) {
                                $this->sumTotal($lagTime, $time);
                            }, function($time) use(&$earlyTime) {
                                $this->sumTotal($earlyTime, $time);
                            });
                        } else {
                            // 判断当前班次是否旷工
                            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                            $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, [$shiftTime], [[$signInNormal, $signOutNormal]]);
                            if ($absOffsetTime == 0) {
                                $result[] = 'absenteeism';
                            } else {
                                list($realTime, $realDay) = $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, [$shiftTime], null, false);
                                $this->sumTotal($realAttendTime, $realTime);
                                if ($this->isOffsetAll($signInNormal, $signOutNormal, $absOffsetTime) ) {
                                    $result[] = 'no_offset_all';
                                }
                            }
                        }
                        $row['result'][$number] = $result;
                    }
                    $row['early_time'] = $earlyTime;
                    $row['lag_time'] = $lagTime;
                    $row['seriously_lag_time'] = $seriouslyLagTime;
                    $row['real_attend_hours'] = $this->calcHours($realAttendTime);
                } else {
                    $record = $records[0];
                    $this->combineSignInOrOut($row, $record);
                    $handleResult = $this->handleOneShiftRecord($record, $signDate, $attendTime, $offsetDatetimes, $shiftId, null, $allowNoSignOut, true);
                    // 解析并组合考勤结果
                    $result = $this->combineAttendResult($shift, $record, $handleResult['early_time'], $handleResult['lag_time'], function($time) use(&$row) {
                        $row['seriously_lag_time'] = $time;
                    }, function($time) use(&$row) {
                        $row['lag_time'] = $time;
                    });
                    $row['result'][1] = $result;
                    $row['early_time'] = $handleResult['early_time'];
                    $oneLagTime = $this->isLag($record);
                    if (!$oneLagTime || !$this->isAbsenteeismLag($shift, $oneLagTime)) {
                        $row['real_attend_hours'] = $this->calcHours($handleResult['real_attend_time']);
                    } else {
                        $row['real_attend_hours'] =  0;
                    }
                }
            } else {
                // 没有考勤记录，判断是否超过当前日期，小于当前日期判断是否矿工
                if ($signDate > $this->currentDate) {
                    $row['result'] = 'empty';
                } else {
                    if (count($shiftTimes) > 1) {
                        $realAttendTime = 0;
                        foreach ($shiftTimes as $key => $shiftTime) {
                            $number = $key + 1;
                            // 判断当前班次是否旷工
                            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                            $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, [$shiftTime], [[$signInNormal, $signOutNormal]]);
                            $result = [];
                            if ($absOffsetTime == 0) {
                                $result[] = 'absenteeism';
                            } else {
                                list($realTime, $realDay) = $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, [$shiftTime], null, false);
                                $this->sumTotal($realAttendTime, $realTime);
                                if ($this->isOffsetAll($signInNormal, $signOutNormal, $absOffsetTime) ) {
                                    $result[] = 'no_offset_all';
                                }
                            }
                            $row['result'][$number] = $result;
                        }
                        $row['real_attend_hours'] = $this->calcHours($realAttendTime);
                    } else {
                        $effectShiftTimes = $this->getEffectShiftTimes($shiftTimes, $shift, $signDate);
                        $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, $shiftTimes, $effectShiftTimes);
                        $result = [];
                        if ($absOffsetTime == 0) {
                            $result[] = 'absenteeism';
                        } else {
                            // 统计实际出勤时间
                            $realAttendTimeArray = $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, $shiftTimes, $shiftId, true);
                            $row['real_attend_hours'] = $this->calcHours($realAttendTimeArray[0]);
                            if ($attendTime - $absOffsetTime > 0) {
                                $result[] = 'no_offset_all';
                            }
                        }
                        $row['result'][1] = $result;
                    }
                }
            }
        } else {
            // 没有排班，休息
            $row['attend_hours'] = 0;
            $row['result'] = 'rest';
            if ($records) {
                $record = $records[0];
                $this->combineSignInOrOut($row, $record);
                if ($record->sign_out_time) {
                    $row['real_attend_hours'] = $this->calcHours($this->datetimeDiff($record->sign_in_time, $record->sign_out_time));
                }
            }
        }
        return $row;
    }
    private function combineAttendResult($shift, $record, $earlyTime, $lagTime, $handleSeriouslyLag = null, $handleLag = null, $handleEarly = null)
    {
        $result = [];
        // 迟到
        if ($this->isAbsenteeismLag($shift, $lagTime)) {
            $result[] = 'absenteeism';
        } else if($this->isSeriouslyLag($shift, $lagTime)) {
            if ($handleSeriouslyLag) {
                $handleSeriouslyLag($lagTime);
            }
            $result[] = 'seriously_lag';
        } else {
            if ($lagTime) {
                if ($handleLag) {
                    $handleLag($lagTime);
                }
                $result[] = 'lag';
            }
        }
        // 早退
        if ($earlyTime) {
            $result[] = 'leave_early';
            if ($handleEarly) {
                $handleEarly($earlyTime);
            }
        }
        if(!$record->sign_out_time) {
            $result[] = 'no_sign_out';
        }
        return $result;
    }
    private function handleOneShiftRecord($record, $signDate, $attendTime, $offsetDatetimes, $shiftId, $shiftTimes = null, $allowNoSignOut = false, $isNormal = true)
    {
        if ($record->is_offset) {
            if ($record->sign_out_time) {
                return $this->handleOffsetHasSignOutRecord($record, $signDate, $attendTime, $isNormal);
            }
            return $this->handleNoSignOutRecord($record, $signDate, $offsetDatetimes, $shiftId, $attendTime, $shiftTimes, $allowNoSignOut, $isNormal);
        }
        if ($record->sign_out_time) {
            return $this->handleHasSignOutRecord($record, $signDate, $offsetDatetimes, $shiftId, $attendTime, $shiftTimes, $isNormal);
        }
        return $this->handleNoSignOutRecord($record, $signDate, $offsetDatetimes, $shiftId, $attendTime, $shiftTimes, $allowNoSignOut, $isNormal);
    }

    private function handleOffsetHasSignOutRecord($record, $signDate, $attendTime, $isNormal = true)
    {
        $lagTime = $this->isLag($record);
        $earlyTime = $this->isLeaveEarly($record);
        // 统计实际出勤时间
        $realAttendTimeArray = $this->getRealAttendTime($record, $signDate, $attendTime, function ($record, $signOutNormal) {
            return [$record->sign_in_time, $record->sign_out_time];
        }, $isNormal);

        return ['real_attend_time' => $realAttendTimeArray[0], 'lag_time' => $lagTime, 'early_time' => $earlyTime];
    }

    private function handleHasSignOutRecord($record, $signDate, $offsetDatetimes, $shiftId, $attendTime, $shiftTimes, $isNormal = true)
    {
        $lagTime = $earlyTime = 0;
        if ($isNormal) {
            $shiftTimes = $this->getShiftTimeById($shiftId);
        }
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);
        if (empty($effectOffsetDateTimes)) {
            $lagTime = $this->isLag($record);
            $earlyTime = $this->isLeaveEarly($record);
        } else {
            $fullRestTimes = $isNormal ? $this->getFullRestTime($shiftId, $record->sign_in_normal, $record->sign_out_normal, $signDate) : [];
            // 统计迟到
            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
            $lagTime = $this->isLag($record, function ($record) use ($fullRestTimes, $signInNormal, $signOutNormal, $effectOffsetDateTimes) {
                $endTime = min($record->sign_in_time, $signOutNormal);
                $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $signInNormal, $endTime);
                $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
                return $record->lag_time - $intersetTime;
            });
            // 统计早退
            $earlyTime = $this->isLeaveEarly($record, function ($record) use ($fullRestTimes, $signInNormal, $signOutNormal, $effectOffsetDateTimes) {
                $startTime = max($signInNormal, $record->sign_out_time);
                $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $startTime, $signOutNormal);
                $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
                return $record->leave_early_time - $intersetTime;
            });
        }
        // 统计实际出勤时间
        $realAttendTimeArray = $this->getRealAttendTime($record, $signDate, $attendTime, function ($record, $signOutNormal) {
            return [$record->sign_in_time, $record->sign_out_time];
        }, $isNormal, $offsetDatetimes);
        return ['real_attend_time' => $realAttendTimeArray[0], 'lag_time' => $lagTime, 'early_time' => $earlyTime];
    }

    private function handleNoSignOutRecord($record, $signDate, $offsetDatetimes, $shiftId, $attendTime, $shiftTimes, $allowNoSignOut, $isNormal = true)
    {
        $lagTime = $earlyTime = 0;
        // 必须签退，否则不计入出勤时间
        if ($isNormal) {
            $shiftTimes = $this->getShiftTimeById($shiftId);
        }
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);
        // 统计迟到
        if (empty($effectOffsetDateTimes)) {
            $lagTime = $this->isLag($record);
        } else {
            $fullRestTimes = $isNormal ? $this->getFullRestTime($shiftId, $record->sign_in_normal, $record->sign_out_normal, $signDate) : [];
            list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $record->sign_in_normal, $record->sign_out_normal);
            $lagTime = $this->isLag($record, function ($record) use ($signInNormal, $signOutNormal, $fullRestTimes, $effectOffsetDateTimes) {
                $endTime = min($record->sign_in_time, $signOutNormal);
                $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $signInNormal, $endTime);
                $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
                return $record->lag_time - $intersetTime;
            });
        }
        if ($allowNoSignOut) { //允许不用签退
            // 统计实际出勤时间
            $realAttendTimeArray = $this->getRealAttendTime($record, $signDate, $attendTime, function ($record, $signOutNormal) {
                return [$record->sign_in_time, $signOutNormal];
            }, $isNormal, $offsetDatetimes);
        } else {
            // 统计实际出勤时间
            $realAttendTimeArray = $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, $shiftTimes, $shiftId, $isNormal);
        }
        return ['real_attend_time' => $realAttendTimeArray[0], 'lag_time' => $lagTime, 'early_time' => $earlyTime];
    }

    private function combineSignInOrOut(&$row, $record, $number = 1)
    {
        $signInTime = $record->original_sign_in_time;
        $signOutTime = $record->original_sign_out_time;
        $row['sign_in'][$number] = $this->combineOneSignInOrOut($record->in_ip, $record->in_address, $record->in_platform, $signInTime);
        if ($signOutTime) {
            $row['sign_out'][$number] = $this->combineOneSignInOrOut($record->out_ip, $record->out_address, $record->out_platform, $signOutTime);
        }
    }

    /**
     * 获取考勤日志
     * @param $param
     * @param $type
     * @param $own
     * @return array|void
     */
    public function getAttendLogs($param, $type, $own)
    {
        
        $param = $this->parseParams($param);
        $userParam = array();
        if (isset($param['search']['user_id'])) {
            $userParam['user_id'] = $param['search']['user_id'];
            unset($param['search']['user_id']);
        }
        if (isset($param['search']['dept_id'])) {
            $userParam['dept_id'] = $param['search']['dept_id'];
            unset($param['search']['dept_id']);
        }
        $menuId = $type === 'originalSign' ? 53 : 254;
        $users = app($this->attendanceSettingService)->filterPurviewUser($own, $menuId, $userParam);

        return $this->_getAttendLogs($users, $param, $type);
    }
    public function getAttendOriginalRecords($param, $own)
    {
        return $this->getAttendLogs($param, 'originalSign', $own);
    }
    public function getMyAttendLogs($param, $type, $own) 
    {
        $userParams = ['noPage' => true, 'user_id' => $own['user_id']];
        
        $users = app($this->userRepository)->getSimpleUserList($userParams);
        
        return $this->_getAttendLogs($users, $this->parseParams($param), $type);
    }
    
    private function _getAttendLogs($users, $param, $type)
    {
        if (!$users) {
            return ['total' => 0, 'list' => []];
        }
        $userIds = array_column($users, 'user_id');
        $userMap = $this->arrayMapWithKeys($users, 'user_id');
        $time = array();
        if (isset($param['search']['startDate']) && isset($param['search']['endDate'])) {
            $time = [$param['search']['startDate'], $param['search']['endDate']];
            unset($param['search']['startDate'], $param['search']['endDate']);
        } else {
            if (isset($param['search']['year']) && isset($param['search']['month'])) {
                $time = $this->getMonthStartEnd($param['search']['year'], $param['search']['month']);
                unset($param['search']['year'], $param['search']['month']);
            }
        }
        //时间字段不同的表不一样搜索自行拼装
        $param['search']['user_id'] = [$userIds, 'in'];
        switch ($type) {
            case 'repair':
                return $this->getAttendRepairLog($param, $userMap, $time);
            case 'calibration':
                return $this->getAttendCalibrationLog($param, $userMap, $time);
            case 'backLeave':
                return $this->getAttendBackLeaveLog($param, $userMap, $time);
            case 'originalSign':
                return $this->getAttendOriginSignLog($param, $userMap, $time);
            case 'overtime':
                return $this->getOvertimeLog($param, $userMap, $time);
            default:
                return ['total' => 0, 'list' => []];
        }
    }
    /**
     * 补卡日志
     * @param $param
     */
    private function getAttendRepairLog($param, $userMap, $time = false)
    {
        if ($time) {
            $param['search']['repair_date'] = [$time, 'between'];
        }
        $total = app($this->attendanceRepairLogRepository)->getTotal($param);
        $logs = app($this->attendanceRepairLogRepository)->getList($param, true)->toArray();
        if (!$logs) {
            return ['list' => $logs, 'total' => $total];
        }
        $approverIds = array_unique(array_column($logs, 'approver_id'));
        $approvers = $this->arrayMapWithKeys($this->getUsersByIds($approverIds), 'user_id');
        $logs = array_map(function ($log) use ($userMap, $approvers) {
            $userId = $log['user_id'];
            $user = $userMap[$userId];
            $log['user_name'] = $user['user_name'];
            $log['dept_name'] = $user['dept_name'];
            $log['approver_name'] = $approvers[$log['approver_id']]['user_name'] ?? null;
            if ($log['new_sign_in_time'] === '0000-00-00 00:00:00') {
                $log['new_sign_in_time'] = '';
            }
            if ($log['new_sign_out_time'] === '0000-00-00 00:00:00') {
                $log['new_sign_out_time'] = '';
            }
            return $log;
        }, $logs);
        return ['list' => $logs, 'total' => $total];
    }

    /**
     * 校准日志
     * @param $param
     */
    private function getAttendCalibrationLog($param, $userMap, $time = false)
    {
        if ($time) {
            $param['search']['sign_date'] = [$time, 'between'];
        }
        $total = app($this->attendanceCalibrationLogRepository)->getTotal($param);
        $logs = app($this->attendanceCalibrationLogRepository)->getList($param)->toArray();
        if (!$logs) {
            return ['list' => $logs, 'total' => $total];
        }
        $approverIds = array_unique(array_column($logs, 'approver'));
        $approvers = $this->arrayMapWithKeys($this->getUsersByIds($approverIds), 'user_id');
        $logs = array_map(function ($log) use ($userMap, $approvers) {
            $userId = $log['user_id'];
            $user = $userMap[$userId];
            $log['user_name'] = $user['user_name'];
            $log['dept_name'] = $user['dept_name'];
            $log['approver_id'] = $log['approver'];
            $log['approver_name'] = $approvers[$log['approver']]['user_name'] ?? null;
            return $log;
        }, $logs);
        return ['list' => $logs, 'total' => $total];
    }

    /**
     * 销假日志
     * @param $param
     * @param $users
     * @param bool $time
     * @return array
     */
    private function getAttendBackLeaveLog($param, $userMap, $time = false)
    {
        if ($time) {
            list($startDate, $endDate) = $time;
            $param['search']['back_leave_start_time'] = [$endDate.' 23:59:59', '<='];
            $param['search']['back_leave_end_time'] = [$startDate.' 00:00:00', '>='];
        }
        $total = app($this->attendanceBackLeaveLogRepository)->getTotal($param);
        $logs = app($this->attendanceBackLeaveLogRepository)->getList($param, true)->toArray();
        if (!$logs) {
            return ['list' => $logs, 'total' => $total];
        }
        $approverIds = array_unique(array_column($logs, 'approver_id'));
        $approvers = $this->arrayMapWithKeys($this->getUsersByIds($approverIds), 'user_id');
        $logs = array_map(function ($log) use ($userMap, $approvers) {
            $userId = $log['user_id'];
            $user = $userMap[$userId];
            $log['user_name'] = $user['user_name'];
            $log['dept_name'] = $user['dept_name'];
            $log['approver_name'] = $approvers[$log['approver_id']]['user_name'] ?? null;
            return $log;
        }, $logs);
        return ['list' => $logs, 'total' => $total];
    }

    /**
     * 原始打卡记录
     * @param $param
     * @param $userMap
     * @param bool $time
     * @return array
     */
    private function getAttendOriginSignLog($param, $userMap, $time = false)
    {
        if ($time) {
            $param['search']['sign_date'] = [$time, 'between'];
        }
        $param['order_by'] = ['sign_date' => 'desc', 'user_id' => 'asc', 'sign_time' => 'desc'];
        $list = app($this->attendanceSimpleRecordsRepository)->getRecordsList($param)->toArray();
        $list = array_map(function ($row) use ($userMap) {
            $userId = $row['user_id'];
            $row['user_name'] = $userMap[$userId]['user_name'] ?? null;
            $row['dept_name'] = $userMap[$userId]['dept_name'] ?? null;
            $row['platform_name'] = $this->transPlatform($row['platform']);
            return $row;
        }, $list);
        $count = app($this->attendanceSimpleRecordsRepository)->getRecordsTotal($param);
        return ['list' => $list, 'total' => $count];
    }

    /**
     * 加班日志
     * @param $param
     * @param $userMap
     * @param bool $time
     * @return array
     */
    private function getOvertimeLog($param, $userMap, $time = false)
    {
        if ($time) {
            list($startDate, $endDate) = $time;
            $param['search']['overtime_start_time'] = [$endDate.' 23:59:59', '<='];
            $param['search']['overtime_end_time'] = [$startDate.' 00:00:00', '>='];
        }
        $total = app($this->attendanceOvertimeLogRepository)->getTotal($param);
        $logs = app($this->attendanceOvertimeLogRepository)->getList($param, true)->toArray();
        if (!$logs) {
            return ['list' => $logs, 'total' => $total];
        }
        $approverIds = array_unique(array_column($logs, 'approver_id'));
        $approvers = $this->arrayMapWithKeys($this->getUsersByIds($approverIds), 'user_id');
        $logs = array_map(function ($log) use ($userMap, $approvers) {
            $userId = $log['user_id'];
            $user = $userMap[$userId];
            $log['user_name'] = $user['user_name'];
            $log['dept_name'] = $user['dept_name'];
            $log['approver_name'] = $approvers[$log['approver_id']]['user_name'] ?? null;
            return $log;
        }, $logs);
        return ['list' => $logs, 'total' => $total];
    }

    private function getUsersByIds($ids)
    {
        $params = [
            'noPage' => true,
            'user_id' => $ids
        ];
        $users = app($this->userRepository)->getSimpleUserList($params);
        return $users;
    }

    /**
     * 获取我最近的请假记录，用户销假选择器
     * @param $userId
     */
    public function getMyLeaveRecords($params, $userId)
    {
        $params = $this->parseParams($params);
        $params['search']['user_id'] = [$userId];
        $params['order_by']['created_at'] = 'desc';
        if (isset($params['search']['run_name'])) {
            //从json中搜索要将汉字转换下
            $runName = $params['search']['run_name'][0];
            $runName = str_replace("\\", "_", json_encode($runName));
            $runName = str_replace('"', '', $runName);
            $params['search']['leave_extra'] = [$runName, 'like'];
            unset($params['search']['run_name']);
        }
        if (isset($params['fields']) && !empty($params['fields'])) {
            $fileds = [];
            foreach($params['fields'] as $field) {
                if ($field === 'datetime_range') {
                    $fileds[] = 'leave_start_time';
                    $fileds[] = 'leave_end_time';
                } else {
                    $fileds[] = $field;
                }
            }
            $params['fields'] = $fileds; 
        }
        $data = app($this->attendanceLeaveRepository)->getList($params);
        $total = app($this->attendanceLeaveRepository)->getTotal($params);
        if (!$data) {
            return ['list' => [], 'total' => 0];
        }
        $data = $data->toArray();
        $data = array_map(function ($leave) {
            if(isset($leave['leave_extra'])) {
                $extra = json_decode($leave['leave_extra'], true);
                $leave['run_name'] = $extra['run_name'] ?? null;
                $leave['run_id'] = $extra['run_id'] ?? null;
                $leave['flow_id'] = $extra['flow_id'] ?? null;
            }
            if (isset($leave['leave_days'])) {
                $leave['leave_days'] = round($leave['leave_days'], $this->dayPrecision);
            }
            if (isset($leave['leave_hours'])) {
                $leave['leave_hours'] = round($leave['leave_hours'], $this->hourPrecision);
            }
            $leave['datetime_range'] = $leave['leave_start_time'] . '~' . $leave['leave_end_time'];
            return $leave;
        }, $data);
        return ['list' => $data, 'total' => $total];
    }
    /**
     * 获取移动考勤记录
     *
     * @param type $param
     * @return type
     */
    public function getMobileRecords($param, $own)
    {
        $param = $this->parseParams($param);
        $type = $this->defaultValue('type', $param, ''); //我的轨迹
        $viewUser = $type == 'my' ? [] : app($this->attendanceSettingService)->getPurviewUser($own, 68);
        if (isset($param['dept_id'])) {
            //按部门筛选
            if (empty($viewUser)) {
                return ['count' => 0, 'lists' => []];
            }
            if ($viewUser != 'all') {
                $param['user_id'] = $viewUser;
            }
            $param['limit'] = 7;
            $total = app($this->userRepository)->getSimpleUserTotal($param);
            $userLists = app($this->userRepository)->getSimpleUserList($param);
            $lists = [];
            foreach ($userLists as $user) {
                $records = app($this->attendanceMobileRecordsRepository)->getAllRecordByOneUserOneDay($user['user_id'], $param['sign_date']);
                $parseRecords = [];
                if (!empty($records)) {
                    foreach ($records as $key => $record) {
                        $parseRecords[] = $this->parseMobileRecord($record);
                    }
                }
                $lists[] = [
                    'user_name' => $user['user_name'],
                    'user_id' => $user['user_id'],
                    'mobileRecord' => $parseRecords,
                ];
            }
            return ['count' => $total, 'lists' => $lists];
        }
        //按用户筛选
        if ($viewUser != 'all' && !in_array($param['user_id'], $viewUser) && $type != 'my') {
            return [];
        }
        $records = app($this->attendanceMobileRecordsRepository)->getOneUserRecords($param['user_id'], $param['search']);
        $recordsMap = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $recordsMap[$record->sign_date][] = $this->parseMobileRecord($record);
            }
        }

        $dates = $this->getDateFromRange($param['search']['sign_date'][0][0], $param['search']['sign_date'][0][1]);

        return $this->fillKeysArray($dates, $recordsMap);
    }
    /**
     * 解析移动考勤记录部分属性
     *
     * @param object $record
     *
     * @return $record
     */
    private function parseMobileRecord($record)
    {
        $record->attachments = app('App\EofficeApp\Attachment\Services\AttachmentService')->getAttachmentIdsByEntityId(['entity_table' => 'attend_mobile_records', 'entity_id' => $record->record_id]);

        if ($record->customer_id) {
            $record->customer_name = $this->getCustomerAttr($record->customer_id);
        }

        return $record;
    }
    /**
     * 获取考勤相关信息的详情记录
     *
     * @param type $year
     * @param type $month
     * @param type $userId
     *
     * @return array
     */
    public function getAttendDetailRecords($year, $month, $userId, $type, $params = null)
    {
        $startDate = $this->formatDate($year, $month, 1);
        if (in_array($type, ['flow_out', 'flow_trip', 'flow_leave', 'flow_overtime'])) {
            $endDate = $this->getMonthEndDate($year, $month);
            $startDate = $startDate . ' 00:00:00';
            $endDate = $endDate . ' 23:59:59';
        } else {
            $endDate = $this->formatDateMonth($year, $month) == date('Y-m') ? $this->currentDate : $this->getMonthEndDate($year, $month);
        }

        return $this->{$this->toCamelCase($type, '_') . 'Records'}($startDate, $endDate, $userId, $params);
    }
    /**
     * 严重迟到
     * @param type $startDate
     * @param type $endDate
     * @param type $userId
     * @param type $param
     * 
     * return array;
     */
    private function seriouslyLagRecords($startDate, $endDate, $userId, $param = false)
    {
        return $this->lagRecords($startDate, $endDate, $userId, $param, 'seriously');
    }
    /**
     * 获取上报位置记录
     *
     * @param type $startDate
     * @param type $endDate
     * @param type $userId
     * @param type $param

     * @return type
     */
    private function locationRecords($startDate, $endDate, $userId, $param = false)
    {
        $wheres = ['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId], 'sign_type' => [0]];
        $dir = $param['order_by']['sign_date'] ?? 'desc';
        $records = app($this->attendanceMobileRecordsRepository)->getOneUserRecords($userId, $wheres, ['*'], ['sign_date' => $dir, 'sign_time' => 'desc']);
        if($records->isEmpty()) {
            return [];
        }
        return $records->mapWithKeys(function($record, $key){
            return [$key => $this->parseMobileRecord($record)];
        });
    }
    private function outAttendRecords($startDate, $endDate, $userId, $param = false)
    {
        $wheres = ['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId], 'sign_type' => [2]];
        $dir = $param['order_by']['sign_date'] ?? 'desc';
        $records = app($this->attendanceMobileRecordsRepository)->getOneUserRecords($userId, $wheres, ['*'], ['sign_date' => $dir, 'sign_time' => 'desc']);
        if($records->isEmpty()) {
            return [];
        }
        return $records->mapWithKeys(function($record, $key){
            return [$key => $this->parseMobileRecord($record)];
        });
    }
    private function noAttendRecords($startDate, $endDate, $userId, $params = null)
    {
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId])[$userId];
        $realAttendRecords = $this->realAttendRecords($startDate, $endDate, $userId);
        $realAttendDate = [];
        $noAttendMap = [];
        if(count($realAttendRecords) > 0) {
            foreach ($realAttendRecords as $item) {
                $realAttendDate[] = $item['sign_date'];
                if($item['shift']->attend_time > $item['real_attend_time']) {
                    $noAttendMap[$item['sign_date']] = [
                        'shift' => $item['shift'],
                        'shift_time' => $item['shift_time'],
                        'no_attend_day' => 1 - $item['real_attend_day'],
                        'no_attend_time' => $item['shift']->attend_time - $item['real_attend_time'],
                        'sign' => $item['sign'],
                        'out' => $item['out'],
                        'trip' => $item['trip'],
                        'leave' => $item['leave']
                    ];
                }
            }
        }
        $allowNoSignOut = $this->allowNoSignOut($userId); // 是否允许漏签
        $recordsMap = [];
        if(!$allowNoSignOut) {
            $records = app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'],'sign_out_time' => [''], 'user_id' => [$userId, '='], 'attend_type' => [1]]);
            $recordsMap = $this->arrayGroupWithKeys($records, 'sign_date');
        }
        $noAttends = [];
        foreach ($schedulings as $scheduling) {
            $signDate = $scheduling['scheduling_date'];
            if($signDate > $this->currentDate) {
                continue;
            }
            if(isset($noAttendMap[$signDate])) {
                $item = $noAttendMap[$signDate];
                $item['sign_date'] = $signDate;
                $noAttends[] = $item;
            } else {
                if(in_array($signDate, $realAttendDate)){
                    continue;
                }
                $shiftId = $scheduling['shift_id'];
                $shiftTimes = $this->getShiftTimeById($shiftId);
                $shift = $this->getShiftById($shiftId);
                $attendTime = $shift->attend_time;
                $sign = null;
                if(isset($recordsMap[$signDate])) {
                    $oneDateRecords = $recordsMap[$signDate];
                    if (count($shiftTimes) > 1) {
                        $recordsGroup = $this->arrayMapWithKeys($oneDateRecords, 'sign_times');
                        foreach ($shiftTimes as $key => $shiftTime) {
                            if (isset($recordsGroup[$key + 1])) {
                                $record = $recordsGroup[$key + 1];
                                $sign[$key + 1] = ['sign_in_time' => $record->original_sign_in_time, 'sign_out_time' => $record->original_sign_out_time];
                            }
                        }
                    } else {
                        $record = $recordsMap[$signDate][0];
                        $sign = ['sign_in_time' => $record->original_sign_in_time, 'sign_out_time' => $record->original_sign_out_time];
                    }
                }
                $noAttends[] = [
                    'shift' => $shift,
                    'shift_time' => $shiftTimes,
                    'no_attend_day' => 1,
                    'no_attend_time' => $attendTime,
                    'sign' => $sign,
                    'sign_date' => $signDate,
                    'out' => [],
                    'trip' => [],
                    'leave' => []
                ];
            }
        }
        return $noAttends;
    }
    private function realAttendRecords($startDate, $endDate, $userId, $params = null)
    {
        $allowNoSignOut = $this->allowNoSignOut($userId); // 是否允许漏签
        list($leaves, $outs, $trips) = $this->getOneUserLOTByDateScope($startDate, $endDate, $userId);
        list($leavesGroup, $outsGroup, $tripsGroup) = $this->groupOutSendData(['leave' => $leaves, 'out' => $outs, 'trip' => $trips]);
        $offsetDatetimes = $this->getOffsetDatetimes($leaves, $outs, $trips, $userId);
        $records = app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId, '='], 'attend_type' => [1]]);
        $recordsMap = $this->arrayGroupWithKeys($records, 'sign_date');
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId])[$userId];
        $realAttendResult = [];
        foreach ($schedulings as $scheduling) {
            $signDate = $scheduling['scheduling_date'];
            if($signDate > $this->currentDate) {
                continue;
            }
            $shiftId = $scheduling['shift_id'];
            $shift = $this->getShiftById($shiftId);
            $shiftTimes = $this->getShiftTimeById($shiftId);
            $attendTime = $shift->attend_time;
            $realTimeCount = $realDayCount = 0;
            $sign = null;
            if (isset($recordsMap[$signDate])) {
                $oneDateRecords = $recordsMap[$signDate];
                if (count($shiftTimes) > 1) {
                    $recordsGroup = $this->arrayMapWithKeys($oneDateRecords, 'sign_times');
                    foreach ($shiftTimes as $key => $shiftTime) {
                        if (isset($recordsGroup[$key + 1])) {
                            $record = $recordsGroup[$key + 1];
                            list($realTime, $realDay) = $this->statHasSignRecordRealAttendTime($record, $signDate, $attendTime, $offsetDatetimes, $shiftTimes, $shiftId, false, $allowNoSignOut);
                            if ($record->is_lag && $this->isAbsenteeismLag($shift, $record->lag_time)) {
                                // 旷工不统计为实际出勤
                            } else {
                                $sign[$key + 1] = ['sign_in_time' => $record->original_sign_in_time, 'sign_out_time' => $record->original_sign_out_time];
                                $realTimeCount += $realTime;
                                $realDayCount += $realDay;
                            }
                        } else {
                            list($realTime, $realDay) = $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, [$shiftTime], null, false);
                            $realTimeCount += $realTime;
                            $realDayCount += $realDay;
                        }
                        
                    }
                } else {
                    $record = $recordsMap[$signDate][0];
                    $sign = ['sign_in_time' => $record->original_sign_in_time, 'sign_out_time' => $record->original_sign_out_time];
                    if ($record->is_lag && $this->isAbsenteeismLag($shift, $record->lag_time)) {
                        // 旷工不统计为实际出勤
                    } else {
                        list($realTimeCount, $realDayCount) = $this->statHasSignRecordRealAttendTime($record, $signDate, $attendTime, $offsetDatetimes, $shiftTimes, $shiftId, true, $allowNoSignOut);
                    }
                }

            } else {
                $isNormal = $shift->shift_type == 1 ? true : false;
                $shiftTimes = $this->getShiftTimeById($shiftId);
                list($realTimeCount, $realDayCount) = $this->getRealAttendTimeNoRecord($signDate, $shift->attend_time, $offsetDatetimes, $shiftTimes, $shiftId, $isNormal);
            }
            if($realTimeCount > 0) {
                $leave = [];
                if($this->statLeaveAsRealAttend){
                    $leave = $leavesGroup[$signDate] ?? [];
                }
                $realAttendResult[] = [
                    'sign_date' => $signDate,
                    'shift' => $shift,
                    'shift_time' => $shiftTimes,
                    'sign' => $sign,
                    'real_attend_time' => $realTimeCount,
                    'real_attend_day' => $realDayCount,
                    'out' => $outsGroup[$signDate] ?? [],
                    'trip' => $tripsGroup[$signDate] ?? [],
                    'leave' => $leave
                ];
            }
        }
        return $realAttendResult;
    }
    private function statHasSignRecordRealAttendTime($record, $signDate, $attendTime, $offsetDatetimes, $shiftTimes, $shiftId, $isNormal = true, $allowNoSignOut = false)
    {
        if($record->sign_out_time) {
            if ($record->is_offset) {
                return $this->statOffsetHasSignOutRealAttendTime($record, $signDate, $attendTime, $isNormal);
            } else {
                return $this->statHasSignOutRealAttendTime($record, $signDate, $attendTime, $offsetDatetimes, $isNormal);
            }
        } else {
            return $this->statNoSignOutRealAttendTime($record, $signDate, $attendTime, $shiftTimes, $offsetDatetimes, $shiftId, $isNormal, $allowNoSignOut);
        }
    }
    private function statHasSignOutRealAttendTime($record, $signDate, $attendTime, $offsetDatetimes, $isNormal = true)
    {
        // 统计实际出勤时间
        return $this->getRealAttendTime($record, $signDate, $attendTime, function($record, $signOutNormal) {
            return [$record->sign_in_time, $record->sign_out_time];
        }, $isNormal, $offsetDatetimes);
    }
    private function statOffsetHasSignOutRealAttendTime($record, $signDate, $attendTime, $isNormal = true)
    {
        // 统计实际出勤时间
        return $this->getRealAttendTime($record, $signDate, $attendTime, function($record, $signOutNormal) {
            return [$record->sign_in_time, $record->sign_out_time];
        }, $isNormal);
    }
    private function allowNoSignOut($userId)
    {
        $allowNoSignOutUserIdsMap = app($this->attendanceSettingService)->getAllowNoSignOutUserIdsMap([$userId]);
        
        return $allowNoSignOutUserIdsMap[$userId] ?? false;
    }
    private function attendRecords($startDate, $endDate, $userId, $params = null)
    {
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId])[$userId];
        return array_map(function($item){
            $item['shift'] = $this->getShiftById($item['shift_id']);
            $item['shift_time'] = $this->getShiftTimeById($item['shift_id']);
            $item['rest'] = $this->getRestTime($item['shift_id'], $item['shift']->shift_type == 1);
            return $item;
        }, $schedulings);
    }
    private function flowOutRecords($startDate, $endDate, $userId, $params = null)
    {
        $params = $this->getOutSendRecordsParams($startDate, $endDate, $userId, 'out');

        return app($this->attendanceOutRepository)->getOutSendDataLists($params);
    }
    private function flowLeaveRecords($startDate, $endDate, $userId, $param = null)
    {
        $params = $this->getOutSendRecordsParams($startDate, $endDate, $userId, 'leave');
        if (!empty($param)) {
            $vacationId = $param['vacation_id'] ?? 'all';
            if ($vacationId != 'all') {
                $params['search']['vacation_id'] = [$vacationId];
            }
        }
        $data = app($this->attendanceLeaveRepository)->getOutSendDataLists($params);

        if($data->isEmpty()) {
            return [];
        }
        return $data->mapWithKeys(function($item, $key){
            if ($item->vacation_id) {
                $vacation = $this->getVacationInfo($item->vacation_id);
                $item->vacation_name = $vacation->vacation_name;
            }
            return [$key => $item];
        });
    }

    private function flowOvertimeRecords($startDate, $endDate, $userId, $params = null)
    {
        $res = [
            'records' => [],
            'stat' => []
        ];
        $param['search']['overtime_start_time'] = [$endDate, '<='];
        $param['search']['overtime_end_time'] = [$startDate, '>='];
        $param['search']['user_id'] = [$userId];
        $param['order_by']['overtime_start_time'] = 'asc';
        $logs = app($this->attendanceOvertimeLogRepository)->getList($param, true)->toArray();
        if (!$logs) {
            return $res;
        }
        $res['records'] = array_map(function ($row) {
            if ($row['overtime_config']) {
                $row['overtime_config'] = json_decode($row['overtime_config'], true);
            }
            if ($row['overtime_flow']) {
                $row['overtime_flow'] = json_decode($row['overtime_flow'], true);
            }
            return $row;
        }, $logs);
        $res['stat'] = $this->getOvertimeStatGroupByTo($startDate, $endDate, $userId);
        return $res;
    }

    private function getOvertimeStatGroupByTo($startDate, $endDate, $userId)
    {
        $res = [0, 0, 0, 0, 0];
        $data = app($this->attendanceOvertimeStatRepository)->getOvertimeStatByDate($startDate, $endDate, $userId);
        if (!$data) {
            return $res;
        }
        foreach ($data as $stat) {
            $res[0] += $stat['overtime_days'];
            if ($stat['to'] == 1) {
                $res[1] += $stat['overtime_days'];
                $res[2] += $stat['overtime_days'] * $stat['ratio'];
            } else {
                if ($stat['to'] == 2) {
                    $res[3] += $stat['overtime_days'];
                    $res[4] += $stat['overtime_days'] * $stat['ratio'];
                }
            }
        }
        return array_map(function ($value) {
            return $this->roundDays($value);
        }, $res);
    }

    private function flowTripRecords($startDate, $endDate, $userId, $params = null)
    {
        $params = $this->getOutSendRecordsParams($startDate, $endDate, $userId, 'trip');

        return app($this->attendanceTripRepository)->getOutSendDataLists($params);
    }
    private function calibrationRecords($startDate, $endDate, $userId, $params = null)
    {
        return app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId, '='], 'calibration_status' => [2, '='], 'attend_type' => [1]]);
    }
    private function lagRecords($startDate, $endDate, $userId, $params = null, $lagType = 'normal')
    {
        // 获取请假，外出，出差，加班记录
        list($leaves, $outs, $trips) = $this->getOneUserLOTByDateScope($startDate, $endDate, $userId);
        list($leavesGroup, $outsGroup, $tripsGroup) = $this->groupOutSendData(['leave' => $leaves, 'out' => $outs, 'trip' => $trips]);
        $offsetDatetimes = $this->getOffsetDatetimes($leaves, $outs, $trips, $userId);
        $records = app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId, '='], 'is_lag' => [1, '='], 'attend_type' => [1]]);
        $normal = $absenteeism = $seriously = [];
        if (!$records->isEmpty()) {
            foreach ($records as $record) {
                $signDate = $record->sign_date;
                $shiftId = $record->shift_id;
                $shiftTimes = $this->getShiftTimeById($shiftId);
                $shift = $this->getShiftById($shiftId);
                $lagTime = $this->getLagTimeFromRecord($record, $signDate, $offsetDatetimes, $shiftTimes, $shift);
                if ($lagTime) {
                    $item = [
                        'record_id' => $record->record_id,
                        'calibration_status' => $record->calibration_status,
                        'sign_date' => $signDate,
                        'sign_in_time' => $record->original_sign_in_time,
                        'sign_out_time' => $record->original_sign_out_time,
                        'sign_in_normal' => $record->sign_in_normal,
                        'sign_out_normal' => $record->sign_out_normal,
                        'lag_time' => $lagTime,
                        'leave' => $leavesGroup[$signDate] ?? [],
                        'out' => $outsGroup[$signDate] ?? [],
                        'trip' => $tripsGroup[$signDate] ?? []
                    ];
                    if ($this->isAbsenteeismLag($shift, $lagTime)) {
                        $absenteeism[] = $item;
                    } else if ($this->isSeriouslyLag($shift, $lagTime)) {
                        $seriously[] = $item;
                    } else {
                        $normal[] = $item;
                    }
                }
            }
        }
        if ($lagType === 'seriously') {
            return $seriously;
        } else if ($lagType === 'normal') {
            return $normal;
        } else {
            return $absenteeism;
        }
    }
    private function getOneUserLOTByDateScope($startDate, $endDate, $userId)
    {
        $endNextDate = $this->getNextDate($endDate);
        // 获取请假，外出，出差，加班记录
        $LOT = [
            'leave' => [$this->attendanceLeaveRepository, 'getAttendLeaves'],
            'out' => [$this->attendanceOutRepository, 'getAttendOuts'],
            'trip' => [$this->attendanceTripRepository, 'getAttendTrips'],
        ];
        $group = [];
        foreach ($LOT as $key => $item) {
            $group[] = app($item[0])->{$item[1]}($startDate, $endNextDate, $userId);
        }
        return $group;
    }
    private function groupOutSendData($data)
    {
        $group = [];
        foreach ($data as $key => $value) {
            list($startKey, $endKey) = $this->outSendTimeKeys[$key];

            $group[] = $this->arrayGroupByDatetimeScope($value, $startKey, $endKey);
        }
        return $group;
    }
    public function getOneUserOutSendDataGroupByDateRange($repository, $method, $startDate, $endDate, $userId, $type = 'leave')
    {
        list($startKey, $endKey) = $this->outSendTimeKeys[$type];
        
        $data = app($repository)->{$method}($startDate, $endDate, $userId);
        
        $group = $this->arrayGroupByDatetimeScope($data, $startKey, $endKey);
        // 按日期拆分
        return $this->splitDatetimeByDateFromLeaveOrOutGroup([$userId => $group], $startKey, $endKey);
    }
    private function leaveEarlyRecords($startDate, $endDate, $userId, $params = null)
    {
        // 获取请假，外出，出差，加班记录
        list($leaves, $outs, $trips) = $this->getOneUserLOTByDateScope($startDate, $endDate, $userId);
        list($leavesGroup, $outsGroup, $tripsGroup) = $this->groupOutSendData(['leave' => $leaves, 'out' => $outs, 'trip' => $trips]);
        $offsetDatetimes = $this->getOffsetDatetimes($leaves, $outs, $trips, $userId);
        $records = app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId, '='], 'is_leave_early' => [1, '='], 'attend_type' => [1]]);
        $result = [];
        if (!$records->isEmpty()) {
            foreach ($records as $record) {
                $signDate = $record->sign_date;
                if ($record->is_offset) {
                    $earlyTime = $this->isLeaveEarly($record);
                } else {
                    $shiftId = $record->shift_id;
                    $shiftTimes = $this->getShiftTimeById($shiftId);
                    $shift = $this->getShiftById($shiftId);
                    $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);
                    if (empty($effectOffsetDateTimes)) {
                        $earlyTime = $this->isLeaveEarly($record);
                    } else {
                        // 统计早退
                        $fullRestTimes = $shift->shift_type == 1 ? $this->getFullRestTime($shiftId, $record->sign_in_normal, $record->sign_out_normal, $signDate) : [];
                        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
                        $earlyTime = $this->isLeaveEarly($record, function($record) use($fullRestTimes, $signInNormal, $signOutNormal, $effectOffsetDateTimes) {
                            $startTime = max($signInNormal, $record->sign_out_time);
                            $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $startTime, $signOutNormal);
                            $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
                            return $record->leave_early_time - $intersetTime;
                        });
                    }
                }
                if ($earlyTime) {
                    $result[] = [
                        'record_id' => $record->record_id,
                        'calibration_status' => $record->calibration_status,
                        'sign_date' => $signDate,
                        'sign_in_time' => $record->original_sign_in_time,
                        'sign_out_time' => $record->original_sign_out_time,
                        'sign_in_normal' => $record->sign_in_normal,
                        'sign_out_normal' => $record->sign_out_normal,
                        'leave_early_time' => $earlyTime,
                        'leave' => $leavesGroup[$signDate] ?? [],
                        'out' => $outsGroup[$signDate] ?? [],
                        'trip' => $tripsGroup[$signDate] ?? []
                    ];
                }
            }
        }
        return $result;
    }
    private function noSignOutRecords($startDate, $endDate, $userId, $params = null)
    {
        return app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId, '='], 'sign_out_time' => ['', '='], 'attend_type' => [1]]);
    }
    /**
     * 获取外出，请假，加班,出差查询参数
     *
     * @param type $year
     * @param type $month
     * @param type $userId
     * @param type $startField
     * @param type $endField
     * @return array
     */
    private function getOutSendRecordsParams($startDateTime, $endDateTime, $userId, $type)
    {
        $searchFields = [
            'out' => ['out_start_time', 'out_end_time'],
            'trip' => ['trip_start_date', 'trip_end_date'],
            'leave' => ['leave_start_time', 'leave_end_time'],
            'overtime' => ['overtime_start_time', 'overtime_end_time'],
        ];
        $param = [
            'page' => 0,
            'search' => ['user_id' => [$userId]],
        ];

        $orSearch = [];
        $orSearch[$searchFields[$type][0]] = [[$startDateTime, $endDateTime], 'between'];
        $orSearch[$searchFields[$type][1]] = [[$startDateTime, $endDateTime], 'between'];
        $param['orSearch'] = $orSearch;

        return $param;
    }
    private function getOffsetDatetimes($leaves, $outs, $trips, $userId)
    {
        // 按日期拆分
        $leavesSplitGroup = $this->splitDatetimeByDateFromLeaveOrOutGroup([$userId => $leaves], 'leave_start_time', 'leave_end_time');
        $outsSplitGroup = $this->splitDatetimeByDateFromLeaveOrOutGroup([$userId => $outs], 'out_start_time', 'out_end_time');
        $tripsSplitGroup = $this->splitDatetimeByDateFromLeaveOrOutGroup([$userId => $trips], 'trip_start_date', 'trip_end_date');
        // 合并请假，外出时间，主要用于考勤抵消
        $offsetDatetimeGroup = $this->combileLeaveOutTripByDateMapWithUserIds($leavesSplitGroup, $outsSplitGroup, $tripsSplitGroup, [$userId]);
        return $offsetDatetimeGroup[$userId] ?? [];
    }
    /**
     * 获取旷工记录
     *
     * @param type $year
     * @param type $month
     * @param type $userId
     * @param type $type
     * @param type $param
     *
     * @return type
     */
    private function absenteeismRecords($startDate, $endDate, $userId, $params = null)
    {
        list($leaves, $outs, $trips) = $this->getOneUserLOTByDateScope($startDate, $endDate, $userId);
        list($leavesGroup, $outsGroup, $tripsGroup) = $this->groupOutSendData(['leave' => $leaves, 'out' => $outs, 'trip' => $trips]);
        $offsetDatetimes = $this->getOffsetDatetimes($leaves, $outs, $trips, $userId);
        $wheres = ['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userId]];
        $records = app($this->attendanceRecordsRepository)->getRecords($wheres, ['*'], ['sign_date' => 'desc', 'sign_times' => 'asc']);
        $recordsMap = $this->arrayGroupWithKeys($records, 'sign_date');
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId])[$userId];
        $absResult = [];
        foreach ($schedulings as $scheduling) {
            $signDate = $scheduling['scheduling_date'];
            if($signDate > $this->currentDate) {
                continue;
            }
            $shiftId = $scheduling['shift_id'];
            $shift = $this->getShiftById($shiftId);
            $oneDateRecords = $recordsMap[$signDate] ?? [];
            if($shift->shift_type == 2) {
                $shiftTimes = $this->getShiftTimeById($shiftId);
                $absRecord = [];
                $recordsGroup = $this->arrayMapWithKeys($oneDateRecords, 'sign_times');
                foreach ($shiftTimes as $key => $shiftTime) {
                    $number = $key + 1;
                    if (!isset($recordsGroup[$number])) {
                        // 判断当前班次是否旷工
                        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                        $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, [$shiftTime], [[$signInNormal, $signOutNormal]]);
                        if ($absOffsetTime == 0) {
                            $absRecord[] = $shiftTime;
                        }
                    } else {
                        $record = $recordsGroup[$number];
                        $lagTime = $this->getLagTimeFromRecord($record, $signDate, $offsetDatetimes, $shiftTimes, $shift);
                        if ($lagTime && $this->isAbsenteeismLag($shift, $lagTime)) {
                            $absRecord[] = $this->combineLagAbsenteeismResult($signDate, $record, $lagTime, $leavesGroup, $outsGroup, $tripsGroup);
                        }
                    }
                }
                if(!empty($absRecord)) {
                    $absResult[] = [
                        'shift' => $shift,
                        'sign_date' => $signDate,
                        'result' => $absRecord
                    ];
                }
            } else {
                $shiftTimes = $this->getShiftTimeById($shiftId);
                if(empty($oneDateRecords)) {
                    $effectShiftTimes = $this->getEffectShiftTimes($shiftTimes, $shift, $signDate);
                    $absOffsetTime = $this->isAbsenteeism($offsetDatetimes, $signDate, $shiftTimes, $effectShiftTimes);
                    if ($absOffsetTime == 0) {
                        $absResult[] = [
                            'shift' => $shift,
                            'sign_date' => $signDate,
                            'result' => $shiftTimes
                        ];
                    }
                } else {
                    $record = $oneDateRecords[0];
                    $lagTime = $this->getLagTimeFromRecord($record, $signDate, $offsetDatetimes, $shiftTimes, $shift);
                    if ($lagTime && $this->isAbsenteeismLag($shift, $lagTime)) {
                        $result = $this->combineLagAbsenteeismResult($signDate, $record, $lagTime, $leavesGroup, $outsGroup, $tripsGroup);
                        $absResult[] = [
                            'shift' => $shift,
                            'sign_date' => $signDate,
                            'result' => [$result]
                        ];
                    }
                }
            }

        }
        return $absResult;
    }
    private function getLagTimeFromRecord($record, $signDate, $offsetDatetimes, $shiftTimes, $shift)
    {
        if ($record->is_offset) {
            return $this->isLag($record);
        } 
        $effectOffsetDateTimes = $this->getEffectOffsetDatetimes($offsetDatetimes, $signDate, $shiftTimes);
        if (empty($effectOffsetDateTimes)) {
            return $this->isLag($record);
        } 
        $fullRestTimes = $shift->shift_type == 2 ? $this->getFullRestTime($shift->shift_id, $record->sign_in_normal, $record->sign_out_normal, $signDate) : [];
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
        return $this->isLag($record, function($record) use($fullRestTimes, $signInNormal, $signOutNormal, $effectOffsetDateTimes) {
            $endTime = min($record->sign_in_time, $signOutNormal);
            $diffDatetime = $this->getSortDatetimeDiff($fullRestTimes, $signInNormal, $endTime);
            $intersetTime = $this->getMoreDatetimeIntersetTime($diffDatetime, $effectOffsetDateTimes);
            return $record->lag_time - $intersetTime;
        });
    }
    private function combineLagAbsenteeismResult($signDate, $record, $lagTime, $leaves, $outs, $trips)
    {
        return [
                'lag_absenteeism' => 1,
                'sign_in_time' => $record->original_sign_in_time,
                'sign_out_time' => $record->original_sign_out_time,
                'sign_in_normal' => $record->sign_in_normal,
                'sign_out_normal' => $record->sign_out_normal,
                'lag_time' => $lagTime,
                'lag_hours' => strval($this->calcHours($lagTime)),
                'leave' => $leaves[$signDate] ?? [],
                'out' => $outs[$signDate] ?? [],
                'trip' => $trips[$signDate] ?? []
            ];
    }
}