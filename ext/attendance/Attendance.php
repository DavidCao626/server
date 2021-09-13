<?php

require __DIR__ . '/../../bootstrap/app.php';

use Illuminate\Support\Facades\Request;
use App\EofficeApp\Attendance\Services\AttendanceBaseService;

/**
 * Description of Attendance
 *
 * @author lizhijun
 */
class Attendance extends AttendanceBaseService {

    public function __construct() {
        parent::__construct();
    }

    public function parse() {
        
    }

    /**
     * 考勤签到
     *
     * @param array $data
     * @param string $userId
     *
     * @return object
     */
    public function repair($data) {
        if (!$this->hasAll($data, ['sign_in_time', 'sign_out_time', 'sign_date', 'current_user_id', 'is_use_overtime'])) {
            return ['code' => ['0x044029', 'attendance']];
        }
        $userId = $data['current_user_id'];
        $signDate = date('Y-m-d', strtotime($this->defaultValue('sign_date', $data, date('Y-m-d'))));
        $signTimes = $this->defaultValue('sign_times', $data, 1);
        $platform = 1; //获取考勤平台，考勤平台（1pc，2app，3微信公众号，4企业号,5企业微信,6移动钉钉,7PC钉钉,8考勤机）
        $shift = $this->checkUserScheduling($signDate, $userId);
        if (isset($shift['code'])) {
            return $shift;
        }
        return $this->signInTerminal($signDate, $shift, $data, $platform, $signTimes, $userId);
    }

    /**
     * 判断数组中是否存在这些元素且不为空
     * @param $array
     * @param $keys
     * @return bool
     */
    protected function hasAll($array, $keys) {
        if (!$array) {
            return false;
        }
        foreach ($keys as $key) {
            if (!isset($array[$key]) || !$array[$key]) {
                return false;
            }
        }
        return true;
    }

    private function checkUserScheduling($signDate, $userId) {
        $scheduling = $this->getOneUserSchedulingByDate($signDate, $userId);
        if (!$scheduling) {
            return ['code' => ['0x044038', 'attendance']];
        }
        if ($scheduling->status == 0) {
            return ['code' => ['0x044045', 'attendance']];
        }
        $schedulingDate = $this->getSchedulingBySchedulingIdAndDate($scheduling->scheduling_id, $signDate);
        $shift = null;
        if ($schedulingDate) {
            $shift = $this->getShiftById($schedulingDate['shift_id']);
        }
        //当天未设置排班，并且不允许非工作日考勤
        if (!$shift && $scheduling->allow_sign_holiday == 0) {
            return ['code' => ['0x044017', 'attendance']];
        }
        return $shift;
    }

    private function signInTerminal($signDate, $shift, $data, $platform, $signTimes, $userId) {
        $attendType = 2;
        /**
         * 判断当天是否有排班
         * 如果当天是工作日，则执行以下代码，否则跳过
         */
        if ($shift) {
            $attendType = 1;
            $shiftId = $shift->shift_id;
            $shiftTimes = $this->getShiftTimeById($shiftId);
            //判断考勤时间是否为空，正常情况不会出现这种问题，这里为了增强代码的健壮性
            if (count($shiftTimes) == 0) {
                return ['code' => ['0x044019', 'attendance']];
            }
            //如果签到签退是时间格式还需拼上日期
            $signInTime = $this->makeFullDatetime($this->defaultValue('sign_in_time', $data, date('Y-m-d H:i:s'))); //签到时间
            $signOutTime = $this->makeFullDatetime($this->defaultValue('sign_out_time', $data, date('Y-m-d H:i:s'))); //签到时间

            $lagTime = 0;
            $shiftTime = $shiftTimes[$signTimes - 1];
            $signInNormal = $shiftTime->sign_in_time;
            $signOutNormal = $shiftTime->sign_out_time;
            $mustAttendTime = $shift->shift_type == 1 ? $shift->attend_time : $this->timeDiff($signInNormal, $signOutNormal); //本次应出勤的工时
            $signData = [
                'sign_date' => $signDate,
                'sign_in_normal' => $signInNormal,
                'sign_out_normal' => $signOutNormal,
                'user_id' => $userId,
                'sign_times' => $signTimes,
                'must_attend_time' => $mustAttendTime,
                'shift_id' => $shiftId,
                'attend_type' => $attendType,
            ];
            $where = [
                'sign_date' => [$signDate],
                'user_id' => [$userId],
                'sign_times' => [$signTimes],
            ];
            if ($record = app($this->attendanceRecordsRepository)->getOneAttendRecord($where)) {

                if ($signInTime && $record->sign_in_time > $signInTime) {
                    $lagTime = $shift ? $this->getLagTime($shift, $shiftTimes, $signInTime, $signDate, $signTimes) : 0;
                    $this->combineBatchSignInData($signData, $signInTime, $platform, $lagTime);
                } else {
                    $lagTime = $record->lag_time;
                    $signInTime = $record->sign_in_time;
                }
                if (($signOutTime && $signOutTime < $record->sign_out_time) || !$signOutTime) {
                    $signOutTime = $record->sign_out_time;
                }
                if ($shift) {
                    $this->combineBatchSignOutData($signData, $signDate, $signInTime, $signOutTime, $platform, $shift, $shiftTimes, $signTimes, $lagTime, $shiftTime);
                    $signData['is_repair'] = 1;
                } else {
                    $signData['sign_out_time'] = $signOutTime;
                    $signData['out_platform'] = $platform;
                    $signData['out_ip'] = getClientIp();
                    $signData['is_repair'] = 3;
                }
                
                app($this->attendanceRecordsRepository)->updateData($signData, $where);
                if (isset($signData['sign_out_time']) && $signData['sign_out_time']) {
                    $this->overtime($record, $signData['sign_out_time']);
                }
            } else {
                //过滤同步只有签退的钉钉数据
                if (!$signInTime) {
                    return;
                }
                $lagTime = $shift ? $this->getLagTime($shift, $shiftTimes, $signInTime, $signDate, $signTimes) : 0;
                $this->combineBatchSignInData($signData, $signInTime, $platform, $lagTime);
                if ($shift) {
                    $this->combineBatchSignOutData($signData, $signDate, $signInTime, $signOutTime, $platform, $shift, $shiftTimes, $signTimes, $lagTime, $shiftTime);
                    $signData['is_repair'] = 1;
                } else {
                    $signData['sign_out_time'] = $signOutTime;
                    $signData['out_platform'] = $platform;
                    $signData['out_ip'] = getClientIp();
                    $signData['out_address'] = '';
                    $signData['out_long'] = '';
                    $signData['out_lat'] = '';
                    $signData['leave_early_time'] = 0;
                    $signData['is_leave_early'] = 0;
                    $signData['is_repair'] = 3;
                }
                app($this->attendanceRecordsRepository)->insertData($signData);
                if(isset($signData['sign_out_time']) && $signData['sign_out_time']) {
                    $signOutTime = $signData['sign_out_time'];
                    $signData['sign_out_time'] = '';
                    $signData = (object)$signData;
                    $this->overtime($signData, $signOutTime);
                }
            }
        }
        return true;
    }
    /**
     * 组装批量打卡的签退数据
     *
     * @param type $signData
     * @param type $signDate
     * @param type $signInTime
     * @param type $signOutTime
     * @param type $platform
     * @param type $shift
     * @param type $shiftTimes
     * @param type $signTimes
     * @param type $lagTime
     * @param type $shiftTime
     */
    private function combineBatchSignOutData(&$signData, $signDate, $signInTime, $signOutTime, $platform, $shift, $shiftTimes, $signTimes, $lagTime, $shiftTime)
    {
        if (!empty($signOutTime)) {
            $signData['sign_out_time'] = $signOutTime;
            $signData['out_platform'] = $platform;
            $signData['out_ip'] = getClientIp();
            $signData['out_address'] = '';
            $signData['out_long'] = '';
            $signData['out_lat'] = '';
            if($shift) {
                $leaveEarlyTime = $this->getEarlyTime($shift, $shiftTimes, $signOutTime,$signDate,$signTimes);
                $signData['leave_early_time'] = $leaveEarlyTime;
                $signData['is_leave_early'] = $leaveEarlyTime > 0 ? 1 : 0;
                // 以下方法是允许一定时间内忽略迟到的设置
                list($signData['lag_time'], $newSignInTime) = $this->resetLagTimeAndSignInTime($shift, $signDate, $lagTime, $leaveEarlyTime, $signInTime, $signOutTime, $shiftTime);
                $signData['sign_in_time'] = $this->getSmallData($signInTime, $newSignInTime);
                $signData['is_lag'] = $signData['lag_time'] > 0 ? 1 : 0;
            } else {
                $signData['leave_early_time'] = 0;
                $signData['is_leave_early'] = 0;
            }
        } else {
            $signData['sign_out_time'] = '';
            $signData['out_platform'] = 0;
            $signData['out_ip'] = '';
            $signData['out_address'] = '';
            $signData['out_long'] = '';
            $signData['out_lat'] = '';
            $signData['leave_early_time'] = 0;
            $signData['is_leave_early'] = 0;
        }
    }
    private function getSignInData($sign_date, $user_id, $sign_in_time, $sign_in_normal, $sign_out_normal, $sign_times, $must_attend_time, $lag_time, $is_lag, $in_ip, $in_long, $in_lat, $in_address, $in_platform, $shift_id, $attend_type) {
        return compact('sign_date', 'user_id', 'sign_in_time', 'sign_in_normal', 'sign_out_normal', 'sign_times', 'must_attend_time', 'lag_time', 'is_lag', 'in_ip', 'in_long', 'in_lat', 'in_address', 'in_platform', 'shift_id', 'attend_type');
    }

    private function signTerminal($userId, $signDate, $signTime, $ip, $long, $lat, $address, $platform, $signType = 1) {
        return $this->addSimpleRecords($userId, $signDate, $signTime, $signType, $platform, $ip, $long, $lat, $address, '');
    }

    public function addSimpleRecords($user_id, $sign_date, $sign_time, $sign_type, $platform, $ip, $long, $lat, $address, $remark) {
        list($year, $month, $day) = explode('-', $sign_date);

        $data = compact('user_id', 'sign_date', 'sign_time', 'year', 'month', 'sign_type', 'platform', 'ip', 'long', 'lat', 'address', 'remark');

        return app('App\EofficeApp\Attendance\Repositories\AttendanceSimpleRecordsRepository')->insertData($data);
    }

    private function getOneUserSchedulingByDate($date, $userId) {
        $currentSchedulingId = $this->schedulingIdMapWithUserIds([$userId])[$userId];
        $nearestModifyRecords = app($this->attendanceSchedulingModifyRecordRepository)->getNearestModifyRecordByDateAndUserIds($date, [$userId]);
        $historySchedulingIdMap = $this->arrayGroupWithKeys($nearestModifyRecords);
        if (isset($historySchedulingIdMap[$userId])) {
            $scheduling = $historySchedulingIdMap[$userId][0];
            return $this->getSchedulingInfoBySchedulingId($scheduling->scheduling_id);
        }
        if ($currentSchedulingId) {
            return $this->getSchedulingInfoBySchedulingId($currentSchedulingId);
        }
        return false;
    }

    private function getSchedulingInfoBySchedulingId($schedulingId) {
        static $schedulings = [];
        if (isset($schedulings[$schedulingId])) {
            return $schedulings[$schedulingId];
        }

        return $schedulings[$schedulingId] = app($this->attendanceSchedulingRepository)->getOneScheduling(['scheduling_id' => [$schedulingId]]);
    }

}
