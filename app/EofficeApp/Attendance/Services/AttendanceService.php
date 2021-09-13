<?php
namespace App\EofficeApp\Attendance\Services;
use App\Jobs\{SendSignRemindJob, AutoSignOutJob};
use Illuminate\Support\Str;
use Queue;
use Eoffice;
use App\EofficeApp\Attendance\Traits\{ AttendanceParamsTrait, AttendanceOvertimeTrait, AttendanceSettingTrait };
/**
 * 考勤模块Service
 *
 * @author  李志军
 *
 * @since  2017-06-26 创建
 */
class AttendanceService extends AttendanceBaseService
{
    use AttendanceParamsTrait;
    use AttendanceOvertimeTrait;
    use AttendanceSettingTrait;
    private $userSystemInfoRepository;
    public function __construct()
    {
        parent::__construct();
        $this->userSystemInfoRepository = "App\EofficeApp\User\Repositories\UserSystemInfoRepository";
    }
    public function getAttendInfo($userId, $signDate)
    {
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($signDate, $signDate, [$userId])[$userId];
        $schedulingMap = $this->arrayMapWithKeys($schedulings, 'scheduling_date', 'shift_id');
        $attendInfo = [];
        // 获取当天的考勤数据
        $this->combineCurrentAttendInfo($attendInfo, $schedulingMap, $signDate, $userId);
        return $attendInfo;
    }
    public function getAdvancedAttendInfo($userId, $signDate)
    {
        $attendInfo = [];
        $prvDate = $this->getPrvDate($signDate);
        $schedulings = $this->schedulingMapWithUserIdsByDateScope($prvDate, $signDate, [$userId])[$userId];
        $schedulingMap = $this->arrayMapWithKeys($schedulings, 'scheduling_date', 'shift_id');
        $prvShiftInfo = $this->getShiftInfoBySignDateUsedSchedulingMap($schedulingMap, $prvDate);
        // 获取前一天的考勤数据
        if ($prvShiftInfo) {
            list($shift, $shiftTimes) = $prvShiftInfo;
            $lastShiftTime = $shiftTimes[count($shiftTimes) - 1];
            if ($lastShiftTime->sign_in_time > $lastShiftTime->sign_out_time) {
                $item = $this->getSimpleAttendance($userId, $prvDate, $shift, $lastShiftTime, count($shiftTimes));
                $item['lag_level'] = $this->getLagLevel($item['lag'], $item['lag_time'], $shift);
                $item['sign_in_normal'] = $item['sign_in_normal'] . '[昨日]';
                $attendInfo[] = $item;
            } else {
                $this->allowSignOutNextDay($attendInfo, $signDate, $prvDate, $userId, $shift, $lastShiftTime, count($shiftTimes));
            }
        } else {
            $this->allowSignOutNextDay($attendInfo, $signDate, $prvDate, $userId);
        }
        // 获取当天的考勤数据
        $this->combineCurrentAttendInfo($attendInfo, $schedulingMap, $signDate, $userId);
        return $attendInfo;
    }
    private function combineCurrentAttendInfo(&$attendInfo, $schedulingMap, $signDate, $userId)
    {
        $currentShiftInfo = $this->getShiftInfoBySignDateUsedSchedulingMap($schedulingMap, $signDate);
        if ($currentShiftInfo) {
            list($shift, $shiftTimes) = $currentShiftInfo;
            foreach ($shiftTimes as $key => $shiftTime) {
                $item = $this->getSimpleAttendance($userId, $signDate, $shift, $shiftTime, $key + 1);
                $item['lag_level'] = $this->getLagLevel($item['lag'], $item['lag_time'], $shift);
                if ($shiftTime->sign_out_time < $shiftTime->sign_in_time) {
                    $item['sign_out_normal'] = $item['sign_out_normal'] . '[次日]';
                }
                $item['sign_in_limit'] = $shift->sign_in_limit;
                $item['sign_in_begin_time'] = $shift->sign_in_begin_time;
                $attendInfo[] = $item;
            }
        } else {
            $attendInfo[] = $this->getSimpleAttendance($userId, $signDate);
        }
    }
    private function getLagLevel($isLag, $lagTime, $shift)
    {
        if ($isLag) {
            if ($this->isAbsenteeismLag($shift, $lagTime)) {
                return 'absenteeism';
            } else if ($this->isSeriouslyLag($shift, $lagTime)) {
                return 'seriously';
            } else {
                return 'normal';
            }
        }
        return '';
    }
    private function allowSignOutNextDay(&$data, $signDate, $prvDate, $userId, $shift = null, $shiftTime = null, $signTimes = 1)
    {
        $scheduling = $this->getOneUserSchedulingByDate($signDate, $userId);
        if ($scheduling && $scheduling->allow_sign_out_next_day) {
            $item = $this->getSimpleAttendance($userId, $prvDate, $shift, $shiftTime, $signTimes);
            if ($item['sign_in_time']) {
                $item['sign_in_normal'] = ($shift ? $item['sign_in_normal'] : '休息') . '[昨日]';
                $item['sign_out_normal'] = ($shift ? $item['sign_out_normal'] : '休息') . '[昨日]';
                $item['lag_level'] = $this->getLagLevel($item['lag'], $item['lag_time'], $shift);
                $data[] = $item;
            }
        }
    }
    private function getSimpleAttendance($userId, $signDate, $shift = null, $shiftTime = null, $signTimes = 1)
    {
        $data = [
            'sign_date' => $signDate,
            'shift_id' => 0,
            'sign_times' => $signTimes,
            'sign_in_normal' => '休息',
            'sign_out_normal' => '休息',
            'full_sign_in_normal' => '',
            'full_sign_out_normal' => '',
            'sign_in_time' => '',
            'sign_out_time' => '',
            'lag' =>  0,
            'lag_time' => 0,
            'leave_early' => 0,
            'in_platform' => '',
            'in_ip' => '',
            'out_platform' => '',
            'out_ip' => ''
        ];
        // 有排班
        if($shift) {
            list($fullSignInNormal, $fullSignOutNormal) = $this->getSignNormalDatetime($signDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
            $data['shift_id'] = $shift->shift_id;
            $data['sign_in_normal'] = $shiftTime->sign_in_time;
            $data['sign_out_normal'] = $shiftTime->sign_out_time;
            $data['full_sign_in_normal'] = $fullSignInNormal;
            $data['full_sign_out_normal'] = $fullSignOutNormal;
        }
        // 有打卡记录
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord(['sign_date' => [$signDate], 'user_id' => [$userId], 'sign_times' => [$signTimes]]);
        if ($record) {
            $data['sign_in_time'] = $record->original_sign_in_time;
            $data['sign_out_time'] = $record->original_sign_out_time;
            $data['lag'] = $record->is_lag;
            $data['lag_time'] = $record->lag_time;
            $data['leave_early'] = $record->is_leave_early;
            $data['in_platform'] = $record->in_platform;
            $data['in_ip'] = $record->in_ip;
            $data['out_platform'] = $record->out_platform;
            $data['out_ip'] = $record->out_ip;
        }
        return $data;
    }
    
    private function getShiftInfoBySignDateUsedSchedulingMap($schedulingMap, $signDate)
    {
        if (isset($schedulingMap[$signDate]) && $schedulingMap[$signDate]) {
            $shiftId = $schedulingMap[$signDate];

            $shiftTimes = $this->getShiftTimeById($shiftId);
            $shift = $this->getShiftById($shiftId);
            return [$shift, $shiftTimes];
        }
        return false;
    }
    /**
     * 获取用户一天的打卡记录
     *
     * @param string $signDate
     * @param string $userId
     *
     * @return array
     */
    public function getOneUserOneDaySignRecords(string $signDate, string $userId)
    {
        $records = app($this->attendanceSimpleRecordsRepository)->getOneUserOneDaySignRecords($signDate, $userId);

        return ['total' => count($records), 'list' => $records];
    }
    /**
     * 登录后自动签到
     *
     * @param array $own
     *
     * @return boolean
     */
    public function autoSignIn($own, $data = [])
    {
        $signDate = $this->currentDate;
        $data['sign_in_time'] = date('Y-m-d H:i:s');
        $userId = $own['user_id'];
        $shift = $this->checkUserScheduling($this->currentDate, $userId);
        // 自动签到
        if (!$shift || $shift->auto_sign != 1) {
            return ['code' => ['0x044030', 'attendance']];
        }
        if (!$this->ipControl($own)) {
            return ['code' => ['0x044041', 'attendance']];
        }
        $platform = $this->getMobilePlatformId($this->defaultValue('platform', $data, 1));
        $this->checkAttendPlatform($platform, $userId); // 验证是否开启相应的考勤平台       
        $signCategory = $this->checkMobileAttend($userId, $data, $platform); //移动签到验证
        return $this->signInNext($data, $signDate, $shift, $platform, 1, $userId, $signCategory);
    }
    /**
     * 考勤签到
     *
     * @param array $data
     * @param array $own
     *
     * @return object
     */
    public function signIn($data, $own)
    {
        if (!$this->ipControl($own)) {
            return ['code' => ['0x044041', 'attendance']];
        }
        $userId = $own['user_id'];
        $signDate = $this->formatSignDate($data);
        $signTimes = $this->defaultValue('sign_times', $data, 1);
        $platform = $this->getMobilePlatformId($this->defaultValue('platform', $data, 1));
        $shift = $this->checkUserScheduling($signDate, $userId);
        $this->checkAttendPlatform($platform, $userId); // 验证是否开启相应的考勤平台       
        $signCategory = $this->checkMobileAttend($userId, $data, $platform); //移动签到验证
        return $this->signInNext($data, $signDate, $shift, $platform, $signTimes, $userId, $signCategory);
    }
    /**
     * 外勤签到
     * @param type $data
     * @param type $own
     * @return type
     */
    public function externalSignIn($data, $own)
    {
        //判断是否开启移动考勤
        if ($this->getSystemParam('attendance_mobile_useed') == 0) {
            return ['code' => ['0x044023', 'attendance']];
        }
        $userId = $own['user_id'];
        $signDate = $this->formatSignDate($data);
        $signTimes = $this->defaultValue('sign_times', $data, 1);
        $platform = $this->getMobilePlatformId($this->defaultValue('platform', $data, 1));
        $shift = $this->checkUserScheduling($signDate, $userId);
        $postionImage = $data['postionImage'] ?? null;
        return $this->signInNext($data, $signDate, $shift, $platform, $signTimes, $userId, 1, 2, 5, function($recordId)  use ($postionImage) {
            if($recordId && $postionImage) {
                app('App\EofficeApp\Attachment\Services\AttachmentService')->attachmentRelation('attend_mobile_records', $recordId, $postionImage);
            }
        });
    }
    private function signInNext($data, $signDate, $shift, $platform, $signTimes, $userId, $signCategory, $signType = 1, $signType1 = 1, $ternimal = null)
    {
        /**
         * 参数初始化
         */
        $originalSignInTime = $signInTime = $this->defaultValue('sign_in_time', $data, date('Y-m-d H:i:s')); //签到时间
        $signInNormal = '';
        $signOutNormal = '';
        $mustAttendTime = 0;
        $shiftId = 0;
        $lagTime = 0;
        $attendType = 2;
        $ip = getClientIp();
        $long = $this->defaultValue('long', $data);
        $lat = $this->defaultValue('lat', $data);
        $address = $this->defaultValue('address', $data);
        //判断是否已经签到过了
        if (app($this->attendanceRecordsRepository)->recordIsExists(['sign_date' => [$signDate], 'user_id' => [$userId], 'sign_times' => [$signTimes]])) {
            return ['code' => ['0x044020', 'attendance']];
        }
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
            $currentShiftTime = $shiftTimes[$signTimes - 1];
            $signInNormal = $currentShiftTime->sign_in_time;
            $signOutNormal = $currentShiftTime->sign_out_time;
            $fullSignInNormal = $this->combineDatetime($signDate, $this->timeStrToTime($signInNormal, 0, true));
            $mustAttendTime = $shift->shift_type == 1 ? $shift->attend_time : $this->timeDiff($signInNormal, $signOutNormal); //本次应出勤的工时
            //判断是否到签到时间，是否超出签到范围
            $this->canSignIn($signDate, $shift, $signInTime, $fullSignInNormal);
            $lagTime = $this->getLagTime($shift, $signInTime, $signDate, $signInNormal, $signOutNormal); //获取迟到时间
            // 如果班次开启了允许迟到几分钟不算迟到，则$signInTime设为$fullSignInNormal；
            if ($signInTime > $fullSignInNormal && $lagTime == 0 && $shift->allow_late == 1) {
                $signInTime = $fullSignInNormal;
            }
        }
        $signData = [
            'sign_date' => $signDate, 
            'user_id' => $userId, 
            'original_sign_in_time' => $originalSignInTime,
            'sign_in_time' => $signInTime, 
            'sign_in_normal' => $signInNormal, 
            'sign_out_normal' => $signOutNormal, 
            'sign_times' => $signTimes, 
            'must_attend_time' => $mustAttendTime, 
            'lag_time' => $lagTime, 
            'is_lag' => $lagTime > 0 ? 1 : 0, 
            'in_ip' => $ip, 
            'in_long' => $long, 
            'in_lat' => $lat, 
            'in_address' => $address, 
            'in_platform' => $platform, 
            'shift_id' => $shiftId, 
            'attend_type' => $attendType
        ];
        $result = app($this->attendanceRecordsRepository)->insertData($signData);
        if ($result) {
            $this->signTerminal($userId, $signDate, $originalSignInTime, $ip, $long, $lat, $address, $platform, $data, 1, $signType, $signType1, $signCategory, $ternimal);
        }
        return $result;
    }
    /**
     * 签退
     *
     * @param array $data
     * @param string $own
     *
     * @return object
     */
    public function signOut($data, $own)
    {
        if (!$this->ipControl($own)) {
            return ['code' => ['0x044041', 'attendance']];
        }
        $userId = $own['user_id'];
        $signDate = $this->formatSignDate($data);
        $signTimes = $this->defaultValue('sign_times', $data, 1);
        $platform = $this->getMobilePlatformId($this->defaultValue('platform', $data, 1));
        $shift = $this->checkUserScheduling($signDate, $userId);
        
        $this->checkAttendPlatform($platform, $userId); // 验证是否开启相应的考勤平台
        $signCategory = $this->checkMobileAttend($userId, $data, $platform); //移动签退验证
        return $this->signOutNext($data, $signDate, $shift, $platform, $signTimes, $userId, $signCategory);
    }
    /**
     * 外勤签退
     * 
     * @param type $data
     * @param type $own
     * @return type
     */
    public function externalSignOut($data, $own)
    {
        $userId = $own['user_id'];
        $signDate = $this->formatSignDate($data);
        $signTimes = $this->defaultValue('sign_times', $data, 1);
        $platform = $this->getMobilePlatformId($this->defaultValue('platform', $data, 1));
        $shift = $this->checkUserScheduling($signDate, $userId);
        //判断是否开启移动考勤
        $openMobileAttend = $this->getSystemParam('attendance_mobile_useed');
        if ($openMobileAttend == 0) {
            return ['code' => ['0x044023', 'attendance']];
        }
        $postionImage = $data['postionImage'] ?? null;
        return $this->signOutNext($data, $signDate, $shift, $platform, $signTimes, $userId, 1, 2, 6, function($recordId)  use ($postionImage) {
            if($recordId && $postionImage) {
                app('App\EofficeApp\Attachment\Services\AttachmentService')->attachmentRelation('attend_mobile_records', $recordId, $postionImage);
            }
        });
    }
    private function signOutNext($data, $signDate, $shift, $platform, $signTimes, $userId, $signCategory, $signType = 1, $signType1 = 2, $ternimal = null) 
    {
        $originalSignOutTime = $signOutTime = $this->defaultValue('sign_out_time', $data, date('Y-m-d H:i:s')); //签退时间
        $ip = getClientIp();
        $long = $this->defaultValue('long', $data);
        $lat = $this->defaultValue('lat', $data);
        $address = $this->defaultValue('address', $data);
        $signData = [
            'leave_early_time' => 0,
            'is_leave_early' => 0,
            'original_sign_out_time' => $originalSignOutTime,
            'sign_out_time' => $signOutTime,
            'out_ip' => $ip,
            'out_long' => $long,
            'out_lat' => $lat,
            'out_address' => $address,
            'out_platform' => $platform
        ];
        $wheres = ['sign_date' => [$signDate], 'sign_times' => [$signTimes], 'user_id' => [$userId]];
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord($wheres);
        if (!$record) {
            return ['code' => ['0x044101', 'attendance']];
        }
        $result = true;
        if (!$record->sign_out_time || $record->sign_out_time < $signOutTime) {
            if ($shift) {
                //获取考勤时间，判断考勤时间是否为空，正常情况不会出现这种问题，这里为了增强代码的健壮性
                $shiftTimes = $this->getShiftTimeById($shift->shift_id); //获取排班考勤时间
                if (count($shiftTimes) == 0) {
                    return ['code' => ['0x044019', 'attendance']];
                }
                // 获取排班签到、签退的完成时间
                $currentShiftTime = $shiftTimes[$signTimes - 1];
                $signInNormal = $currentShiftTime->sign_in_time;
                $signOutNormal = $currentShiftTime->sign_out_time;
                list($fullSignInNormal, $fullSignOutNormal) = $this->getSignNormalDatetime($signDate, $signInNormal, $signOutNormal);
                // 判断能否签退
                $this->canSignOut($shift, $record->sign_in_time, $signOutTime, $fullSignInNormal, $fullSignOutNormal);
                // 晚到晚退规则下的考勤数据。
                $this->resetSignInData($signData, $shift, $signDate, $record->is_lag, $record->sign_in_time, $signOutTime, $fullSignInNormal, $fullSignOutNormal, $signInNormal, $signOutNormal, $record->sign_out_time);
                // 获取早退时间，设置签退时间，早退时间，是否早退相关数据。
                $this->setSignOutData($signData, $shift, $record->sign_in_time, $signOutTime, $signDate, $signInNormal, $signOutNormal, $fullSignOutNormal);
            }
            $result = app($this->attendanceRecordsRepository)->updateData($signData, $wheres);
            if ($result) {
                $this->overtime($record, $signOutTime);// 考勤打卡直接转加班
            }
        } else {
            if ($shift) {
                //获取考勤时间，判断考勤时间是否为空，正常情况不会出现这种问题，这里为了增强代码的健壮性
                $shiftTimes = $this->getShiftTimeById($shift->shift_id); //获取排班考勤时间
                if (count($shiftTimes) == 0) {
                    return ['code' => ['0x044019', 'attendance']];
                }
                // 获取排班签到、签退的完成时间
                $currentShiftTime = $shiftTimes[$signTimes - 1];
                $signInNormal = $currentShiftTime->sign_in_time;
                $signOutNormal = $currentShiftTime->sign_out_time;
                list($fullSignInNormal, $fullSignOutNormal) = $this->getSignNormalDatetime($signDate, $signInNormal, $signOutNormal);
                // 判断能否签退
                $this->canSignOut($shift, $record->sign_in_time, $signOutTime, $fullSignInNormal, $fullSignOutNormal);
            }
        }
        $this->signTerminal($userId, $signDate, $originalSignOutTime, $ip, $long, $lat, $address, $platform, $data, 2, $signType, $signType1,$signCategory, $ternimal);
        return $result ? $signData : false;
    }
    private function formatSignDate($data)
    {
        $signDate = $this->defaultValue('sign_date', $data, $this->currentDate);
        
        return $this->format($signDate, 'Y-m-d');
    }
    private function signTerminal($userId, $signDate, $signTime, $ip, $long, $lat, $address, $platform, $data, $signStatus = 1, $signType = 1, $signType1 = 1, $signCategory = 1, $ternimal = null)
    {
        $remark = $data['remark'] ?? '';
        if ($platform != 8 && $platform != 10) {    //添加10钉钉同步不添加记录
            // signType = 5表示外勤签到 ,1签到，2签退，0上报位置，4打卡，5外勤签到，6外勤签退
            $this->addSimpleRecords($userId, $signDate, $signTime, $signType1, $platform, $ip, $long, $lat, $address, $remark);
        }
        if ($this->isMobilePlatform($platform)) {
            $wifiName = $data['attend_wifi_name'] ?? '';
            $wifiMac = $data['attend_wifi_mac'] ?? '';
            $customerId = $data['customer_id'] ?? 0;
            $mobileResult = $this->addMobileLocationRecord($userId, $signDate, $signTime, $ip, $long, $lat, $address, $wifiName, $wifiMac, $platform, $signType, $signStatus, $signCategory, $customerId, $remark);
            if ($ternimal) {
                $ternimal($mobileResult->record_id);
            }
        }
        return true;
    }
    /**
     * 获取早退时间
     *
     * @param object $shift
     * @param string $signTime
     * @param string $signOutTime
     * @param int $number
     *
     * @return int
     */
    private function getEarlyTime($shift, $signInTime, $signOutTime, $signDate, $shiftSignInTime, $shiftSignOutTime)
    {
        if (empty($signOutTime)) {
            return [false, 0];
        }
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftSignInTime, $shiftSignOutTime);
        $signOutTime = $this->makeFullDatetime($signOutTime, $signDate);
        $signOutTime = max(min($signOutTime, $signOutNormal), $signInNormal);
        $earlyTime = $this->datetimeDiff($signOutTime, $signOutNormal);
        //正常班减去休息时间
        if ($shift->shift_type == 1) {
            $restTimes = $this->getRestTime($shift->shift_id);
            $earlyTime -= $this->getRestTotal($restTimes, $signOutTime, $signOutNormal, $shiftSignInTime, $shiftSignOutTime, $signDate);
        }
        if ($earlyTime <= 0) {
            return [false, 0];
        }
        if ($shift->allow_early_to_early == 1) {
            // 判断早到早走的请况
            $allowEarlyTime = $shift->early_to_early_time ? $shift->early_to_early_time * 60 : 0;
            if ($allowEarlyTime > 0 && $earlyTime <= $allowEarlyTime && $signInTime < $signInNormal) {
                if ($earlyTime <= $this->datetimeDiff($signInTime, $signInNormal)) {
                    return [true, 0];
                }
            }
        } else if($shift->allow_leave_early == 1) {
            // 判断早走几分钟不算早退的情况
            $allowEarlyTime = $shift->leave_early_time ? $shift->leave_early_time * 60 : 0;
            if ($allowEarlyTime > 0 && ($earlyTime <= $allowEarlyTime)) {
                return [true, 0];
            }
        }
        return [true, $earlyTime];
    }
    /**
     * 重置考勤签到记录的迟到时间
     *
     * @param string $signDate
     * @param string $signTime
     * @param object $shift
     * @param string $signOutTime
     * @param string $userId
     * @param int $number
     *
     * @return boolean
     */
    private function resetSignInLagTime($shift, $signDate, $isLag, $signInTime, $signOutTime, $signInNormal, $signOutNormal, $shiftSignInTime, $shiftSignOutTime, $oldSignOutTime = null)
    {
        $allowLateTime = $shift->late_to_late_time ? $shift->late_to_late_time * 60 : 0;
        // 没有开启晚到晚退或最大允许晚到时间<=0不需要处理
        if ($shift->allow_late_to_late == 0 || $allowLateTime <= 0) {
            return [false, 0];
        }
        // 记录里有迟到时间不需要处理，因为说明签到的时间久没有满足晚到晚退规则。
        if ($isLag == 1 && !($oldSignOutTime && $oldSignOutTime < $signOutTime)) {
            return [false, 0];
        }
        $lagTime = $this->calcLagTime($signDate, $shift, $signInTime, $signInNormal, $signOutNormal, $shiftSignInTime, $shiftSignOutTime);
        // 迟到时间为0，不需要处理。
        if ($lagTime == 0) {
            return [false, 0];
        }
        // 早退,直接返回迟到时间
        if ($signOutTime <= $signOutNormal) {
            return [true, $lagTime];
        }
        // 没有补足工时，则返回迟到时间
        if ($lagTime > $this->datetimeDiff($signOutNormal, $signOutTime)) {
            return [true, $lagTime];
        }
        // 重置签退时间为0
        return [true, 0];
    }
    /**
     * 判断是否在签到时间范围内
     * @param type $signDate
     * @param type $shift
     * @param type $signInTime
     * @param type $shiftTimes
     * @param type $signTimes
     * @return boolean
     */
    private function canSignIn($signDate, $shift, $signInTime, $fullSignInNormal)
    {
        if ($shift->sign_in_limit == 0) {
            return true;
        }        
        $limitBeginSignIn = $this->limitBeginSignIn($signDate, $shift->sign_in_begin_time, $signInTime, $fullSignInNormal);
        if (!$limitBeginSignIn) {
            $this->throwException(['code' => ['0x044018', 'attendance']]);
        }
        $limitEndSignIn = $this->limitEndSignIn($shift->sign_in_end_time, $signInTime, $fullSignInNormal);
        if (!$limitEndSignIn) {
            $this->throwException(['code' => ['0x044100', 'attendance']]);
        }
    }
    /**
     * 判断最早签到时间限制
     * @param type $signDate
     * @param type $beginTime
     * @param type $signInTime
     * @param type $signInNormal
     * @return boolean
     */
    private function limitBeginSignIn($signDate, $beginTime, $signInTime, $signInNormal)
    {
        if (!$beginTime || $beginTime <= 0) {
            return true;
        }
        $signInBeginTime = $beginTime * 60;
        
        $compareTime = $this->datetimeDiff($this->combineDatetime($signDate, '00:00:00'), $signInNormal);

        //判断如果允许提前的时间已经到了当天00:00:00,直接返回true
        if ($signInBeginTime >= $compareTime) {
            return true;
        }
        $canSignTime = date('Y-m-d H:i:s', strtotime($signInNormal) - $signInBeginTime);

        return $signInTime >= $canSignTime;
    }
    /**
     * 判断最晚签到时间限制
     * @param type $endTime
     * @param type $signInTime
     * @param type $signInNormal
     * @return boolean
     */
    private function limitEndSignIn($endTime, $signInTime, $signInNormal)
    {
        if (!$endTime || $endTime <= 0) {
            return true;
        }
        
        $signInEndTime = $endTime * 60;
        
        $canSignTime = date('Y-m-d H:i:s', strtotime($signInNormal) + $signInEndTime);
        
        return $signInTime <= $canSignTime;
    }
    /**
     * 判断能否签到
     * @param type $shift
     * @param type $signOutTime
     * @param type $signInNormal
     * @param type $signOutNormal
     * @return boolean
     */
    private function canSignOut($shift, $signInTime, $signOutTime, $signInNormal, $signOutNormal)
    {
        //还未到上班时间不可签退
        if ($signOutTime <= $signInNormal) {
            $this->throwException(['code' => ['0x044053', 'attendance']]);
        }
        if ($shift->sign_out_now_limit == 1) {
            // 开启了立即签退限制、判断签到时间和本次签退时间间隔是否达到限制的时间间隔。
            $signOutNowLimitTime = $shift->sign_out_now_limit_time * 60;
            $canSignTime = date('Y-m-d H:i:s', strtotime($signInTime) + $signOutNowLimitTime);
            if ($signOutTime < $canSignTime) {
                $this->throwException(['code' => ['0x044099', 'attendance']], trans('attendance.0x044099', ['time' => $shift->sign_out_now_limit_time]));
            }
        }
        if ($shift->sign_out_limit == 1 && $shift->sign_out_end_time) {
            // 开启了签退时间限制、判断当前签退时间是否超出最晚签退时间限制。
            $signOutEndTime = $shift->sign_out_end_time * 60;
            $canSignTime = date('Y-m-d H:i:s', strtotime($signOutNormal) + $signOutEndTime);
            if ($signOutTime > $canSignTime) {
                $this->throwException(['code' => ['0x044098', 'attendance']]);
            }
        }
    }
    /**
     * 获取迟到时间
     *
     * @param object $shift
     * @param string $signInTime
     *
     * @return int
     */
    private function getLagTime($shift, $signInTime, $signDate, $shiftSignInTime, $shiftSignOutTime)
    {        
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($signDate, $shiftSignInTime, $shiftSignOutTime);
        
        $lagTime = $this->calcLagTime($signDate, $shift, $signInTime, $signInNormal, $signOutNormal, $shiftSignInTime, $shiftSignOutTime);
        
        if ($shift->allow_late_to_late == 1) {
            // 判断晚到晚退的请况
            $allowLateTime = $shift->late_to_late_time ? $shift->late_to_late_time * 60 : 0;
            if ($allowLateTime > 0 && ($lagTime < $allowLateTime)) {
                return 0;
            }
        } else if($shift->allow_late == 1) {
            // 判断晚到几分钟不算迟到的情况
            $allowLateTime = $shift->allow_late_time ? $shift->allow_late_time * 60 : 0;
            if ($allowLateTime > 0 && ($lagTime < $allowLateTime)) {
                return 0;
            }
        }
        return $lagTime;
    }
    private function calcLagTime($signDate, $shift, $signInTime, $signInNormal, $signOutNormal, $shiftSignInTime, $shiftSignOutTime) 
    {
        $signInTime = $this->makeFullDatetime($signInTime, $signDate);
        $signInTime = min(max($signInTime, $signInNormal), $signOutNormal);
        $lagTime = $this->datetimeDiff($signInNormal, $signInTime);
        //正常班减去休息时间
        if ($shift->shift_type == 1) {
            $restTimes = $this->getRestTime($shift->shift_id, true);
            $lagTime -= $this->getRestTotal($restTimes, $signInNormal, $signInTime, $shiftSignInTime, $shiftSignOutTime, $signDate);
        }
        return $lagTime;
    }
    public function overtime($record, $signOutTime)
    {
        $userId = $record->user_id;
        $signDate = $record->sign_date;
        $lastSignOutTime = $record->original_sign_out_time;
        $shiftId = $record->shift_id;
        $signInTime = $record->original_sign_in_time;
        $config = $this->getStaticOvertimeConfig($userId);
        $method = $config['method'] ?? null;
        //需流程审批加班的
        if (!in_array($method, [3, 4])) {
            return true;
        }
        
        $holidays = $this->getHolidayGroupByDate($signDate, $signDate, $userId);
        $overtimeBeginTime = null;
        if ($shiftId) {
            //工作日
            if(!$config->work_open) {
                return true;
            }
            $fullSignOutNormal = $this->getSignNormalDatetime($signDate, $record->sign_in_normal, $record->sign_out_normal)[1];
            $overtimeBeginTime = $this->getOvertimeBeginTime($fullSignOutNormal, $config);
            //本次加班没达到开始的统计要求
            if (!$this->isEffectiveWorkOvertime($overtimeBeginTime, $signOutTime, $config)) {
                return true;
            }
            //特殊情况，下班后签到加班
            $overtimeBeginTime = max($signInTime, $overtimeBeginTime);
            //计算出本次签到的有效时间，为本次新增的有效时间段
            list($beginTime, $endTime) = $this->getEffectOvertimeDatetimes($overtimeBeginTime, $signOutTime, $lastSignOutTime, $config);
            $shift = $this->getShiftById($shiftId);
            $attendTime = $shift->attend_time;
            $key = 'work';
        } elseif (isset($holidays[$signDate])) {
            //节假日
            if(!$config->holiday_open) {
                return true;
            }
            //本次加班没达到开始的统计要求
            if (!$this->isEffectiveRestOvertime($signInTime, $signOutTime, $config)) {
                return true;
            }
            $key = 'holiday';
            $attendTime= $config->holiday_convert * 3600;
            list($beginTime, $endTime) = $this->getEffectOvertimeDatetimes($signInTime, $signOutTime, $lastSignOutTime, $config);
        } else {
            //休息日
            if(!$config->rest_open) {
                return true;
            }
            //本次加班没达到开始的统计要求
            if (!$this->isEffectiveRestOvertime($signInTime, $signOutTime, $config)) {
                return true;
            }
            $key = 'rest';
            $attendTime= $config->rest_convert * 3600;
            list($beginTime, $endTime) = $this->getEffectOvertimeDatetimes($signInTime, $signOutTime, $lastSignOutTime, $config);
        }
        //插入记录的相关数据
        $to = $config->{$key . '_to'};
        $ratio = $config->{$key . '_ratio'};
        $vacation = $config->{$key . '_vacation'};
        //加班日志
        $log = [
            'user_id' => $userId,
            'overtime_start_time' => $beginTime,
            'overtime_end_time' => $endTime,
            'from' => 2, //加班日志来源，1流程，2打卡
            'overtime_config' => json_encode($config), //记录下当时的加班配置
            'detail' => []
        ];
        //无需审批，根据打卡时间自动计算加班时长
        if ($method == 3) {
            $overtimeTimeDate = date('Y-m-d', strtotime($beginTime));
            list($seconds, $effectTimes ) = $this->getOvertimeSeconds($overtimeTimeDate, $beginTime, $endTime, $config, $key);
            $daysHours = $this->parseOvertimeDayHours($seconds, $attendTime, $config, $key);
            $days = $daysHours['days'] ?? 0;
            $hours = $daysHours['hours'] ?? 0;
//            $hours = $this->calcHours($seconds);
            $log['days'] = $days;
            $log['hours'] = $hours;
            //本次加班有效时间段
            $log['detail'][] = [
                'date' => $overtimeTimeDate,
                'times' => $effectTimes,
                'days' => $days,
                'hours' => $hours
            ];
            $this->addOvertimeDaysHours($days, $hours, $userId, $overtimeTimeDate, $to, $ratio, $vacation);
        } else {
            //需审批，以打卡为准，但不超过加班流程时长
            $overtimeTimeDate = date('Y-m-d', strtotime($beginTime));
            $records = app($this->attendanceOvertimeRepository)->getAttendOvertime($beginTime, $endTime, $userId)->toArray();
            if (!$records) {
                return true;
            }
            //本次签退会对以前审批的流程产生影响
            $seconds = 0;
            //上次签退是否已经产生了加班记录
            $hasLastOvertime = false;
            foreach ($records as $record) {
                if ($record['overtime_hours']) {
                    $hasLastOvertime = true;
                }
                $recordTo = $to;
                //员工自选
                if ($recordTo == 3) {
                    $recordTo = in_array($record['overtime_to'], [1, 2]) ? $record['overtime_to'] : 1;
                }
                $time = $this->getIntersetTime($beginTime, $endTime, $record['overtime_start_time'], $record['overtime_end_time'], true);
                if ($time) {
                    list($addSeconds, $effectTimes ) = $this->getOvertimeSeconds($overtimeTimeDate, $time[0], $time[1], $config, $key);
                    $overtimeDays = $this->calcDay($addSeconds, $attendTime);
                    $overtimeHours = $this->calcHours($addSeconds);
                    //更细外发记录表的天数
                    $update = app($this->attendanceOvertimeRepository)->updateData([
                            'overtime_days' => $record['overtime_days'] + $overtimeDays,
                            'overtime_hours' => $record['overtime_hours'] + $overtimeHours
                        ], ['overtime_id' => [$record['overtime_id']]
                    ]);
                    if ($update) {
                        $seconds += $addSeconds;
                    }
                    //本次加班有效时间段
                    $log['detail'][] = [
                        'date' => $overtimeTimeDate,
                        'times' => $effectTimes,
                        'days' => $overtimeDays,
                        'hours' => $overtimeHours
                    ];
                }
            }
            //需审批，以打卡为主的话，按照打卡记录加班可能有效，但是和流程关联，可能就没有大小加班的条件
            if (!$hasLastOvertime && $overtimeBeginTime) {
                $log['overtime_start_time'] = $overtimeBeginTime;
            }
            $daysHours = $this->parseOvertimeDayHours($seconds, $attendTime, $config, $key);
            $days = $daysHours['days'] ?? 0;
            $hours = $daysHours['hours'] ?? 0;
//            $hours = $this->calcHours($seconds);
            $log['days'] = $days;
            $log['hours'] = $hours;
            $log['history_overtime'] = implode(',', array_column($records, 'overtime_id'));
            $this->addOvertimeDaysHours($days, $hours, $userId, $overtimeTimeDate, $recordTo, $ratio, $vacation);
        }
        return $this->addOvertimeLog($log);
    }
    /**
     * 获取加班时长，返回秒
     * @param type $signDate
     * @param type $begin
     * @param type $end
     * @param type $config
     * @param type $dateType
     * @return type
     */
    private function getOvertimeSeconds($signDate, $begin, $end, $config, $dateType = 'holiday') 
    {
        if ($dateType === 'work') {
            return [$this->datetimeDiff($begin, $end), [[$begin, $end]]];
        }
        $effectTimes = $this->getOvertimeEffectTimes($signDate, $begin, $end, $config, $dateType);
        $times = 0;
        if (!empty($effectTimes)) {
            foreach ($effectTimes as $item) {
                $times += $this->datetimeDiff($item[0], $item[1]);
            }
        }
        return [$times, $effectTimes];
    }
    private function parseOvertimeDay($time, $attendTime, $config, $type = 'work')
    {
        $limit = $config->{$type . '_limit_days'};

        $days = $this->calcDay($time, $attendTime);

        return ($limit && $days > 1) ? 1 : $days;
    }

    private function parseOvertimeDayHours($time, $attendTime, $config, $type = 'work')
    {
        $limit = $config->{$type . '_limit_days'};
        $hours = 0;
        $days = 0;
        //加班最小单位
        if ($config->{$type . '_min_unit'}) {
            switch ($config->{$type . '_min_unit'}){
                case 1:  //无
                    $hours = $this->calcHours($time);
                    $days = $this->calcDay($time, $attendTime);
                    break;
                case 2: //半小时
                    $hours = $this->floor($this->calcHours($time), 0.5);
                    $days = $this->calcDayByHours($hours, $attendTime/3600);
                    break;
                case 3: //一小时
                    $hours = $this->floor($this->calcHours($time), 1);
                    $days = $this->calcDayByHours($hours, $attendTime/3600);
                    break;
                case 4: //半天
                    $hours = $this->floor($this->calcHours($time), $attendTime/3600/2);
                    $days = $this->calcDayByHours($hours, $attendTime/3600);
                    break;
                default:
                    $hours = $this->calcHours($time);
                    $days = $this->calcDay($time, $attendTime);
            }
        }
        return ['days' => ($limit && $days > 1) ? 1 : $days, 'hours' => $hours];
    }

    private function getEffectOvertimeDatetimes($begin, $end, $lastEnd, $config, $isWork = false)
    {
        $hasOvertime = $isWork ? $this->isEffectiveWorkOvertime($begin, $lastEnd, $config) : $this->isEffectiveRestOvertime($begin, $lastEnd, $config);
        
        if ($hasOvertime) {
            return [$lastEnd, $end];
        } 
        
        return [$begin, $end];
    }
    private function addOvertimeDaysHours($days, $hours, $userId, $date, $to, $ratio, $vacation)
    {
        //转假期余额的话，假期模块增加余额
        if ($to == 1 && $vacation) {
            $data = [
                'user_id' => $userId,
                'days' => $days * $ratio,
                'vacation_id' => $vacation
            ];
            app($this->vacationService)->overtimeDataout($data);
        }
        $wheres = [
            'date' => [$date],
            'user_id' => [$userId]
        ];
        $result = app($this->attendanceOvertimeStatRepository)->getOneRecord($wheres);
        if (!$result) {
            $insertData = [
                'user_id' => $userId,
                'date' => $date,
                'year' => $this->format($date, 'Y'),
                'month' => $this->format($date, 'm'),
                'day' => $this->format($date, 'd'),
                'overtime_days' => $days,
                'overtime_hours' => $hours,
                'to' => $to,
                'ratio' => $ratio,
                'vacation' => $to == 1 ? $vacation : null
            ];
            return app($this->attendanceOvertimeStatRepository)->insertData($insertData);
        } else {
            return app($this->attendanceOvertimeStatRepository)->updateData([
                'overtime_days' => $result->overtime_days + $days,
                'overtime_hours' => $result->overtime_hours + $hours
            ], $wheres);
        }
        return true;
    }
    
    private function isEffectiveWorkOvertime($overtimeBeginTime, $signOutTime, $config)
    {
        if (!$signOutTime) {
            return false;
        }
        if ($signOutTime <= $overtimeBeginTime) {
            //没有达到加班的的开始时间，比如下班后一个小时开始计算加班，还没有下班后一个小时
            return false;
        } else if ($this->datetimeDiff($overtimeBeginTime, $signOutTime) < $config->work_min_time * 60) {
            //加班时间太短了
            return false;
        }
        return true;
    }
    private function isEffectiveRestOvertime($signInTime, $signOutTime, $config)
    {
        if (!$signOutTime) {
            return false;
        }
        if ($this->datetimeDiff($signInTime, $signOutTime) < $config->work_min_time * 60) {
            return false;
        }
        return true;
    }
    /**
     * 批量考勤，主要用在考勤打卡数据导入，考勤机同步
     * @param type $signArray
     * @param type $startDate
     * @param type $endDate
     */
    public function batchSign($signArray, $startDate, $endDate)
    {
        $userIds = array_keys($signArray);
        $signDataArray = [];
        $scheduings = $this->getMoreUserSchedulingByDateScope($startDate, $endDate, $userIds);
        $clientIp = getClientIp();
        foreach ($signArray as $userId => $oneUserSignArray) {
            $oneUserSchedulings = $scheduings[$userId] ?? [];
            foreach ($oneUserSignArray as $signDate => $data) {
                if (!isset($oneUserSchedulings[$signDate])) {
                    continue;
                }
                $scheduling = $oneUserSchedulings[$signDate];
                $schedulingDate = $this->getSchedulingBySchedulingIdAndDate($scheduling->scheduling_id, $signDate);
                $shift = $schedulingDate ? $this->getShiftById($schedulingDate['shift_id']) : null;
                if (!$shift && $scheduling->allow_sign_holiday == 0) {
                    continue;
                }
                $shiftId = 0;
                $attendType = 2;
                $shiftTimes = [];
                if ($shift) {
                    $attendType = 1;
                    $shiftId = $shift->shift_id;
                    $shiftTimes = $this->getShiftTimeById($shiftId);
                    if (count($shiftTimes) == 0) {//判断考勤时间是否为空，正常情况不会出现这种问题，这里为了增强代码的健壮性
                        continue;
                    }
                }
                foreach ($data as $item) {
                    $signTimes = $this->defaultValue('sign_nubmer', $item, 1); //第几次考勤（正常班只有一次考勤，交换班可能有多次）
                    //如果签到签退是时间格式还需拼上日期
                    $signInTime = $this->makeFullDatetime($this->defaultValue('sign_in_time', $item, date('Y-m-d H:i:s'))); //签到时间
                    $signOutTime = $this->makeFullDatetime($this->defaultValue('sign_out_time', $item, date('Y-m-d H:i:s'))); //签到时间
                    $platform = $this->defaultValue('platform', $item, 8);
                    $signInNormal = '';
                    $signOutNormal = '';
                    $fullSignInNormal = '';
                    $fullSignOutNormal = '';
                    $mustAttendTime = 0;
                    if (!empty($shiftTimes)) {
                        $shiftTime = $shiftTimes[$signTimes - 1];
                        $signInNormal = $shiftTime->sign_in_time;
                        $signOutNormal = $shiftTime->sign_out_time;
                        list($fullSignInNormal, $fullSignOutNormal) = $this->getSignNormalDatetime($signDate, $signInNormal, $signOutNormal);
                        $mustAttendTime = $shift->shift_type == 1 ? $shift->attend_time : $this->timeDiff($signInNormal, $signOutNormal); //本次应出勤的工时
                    }
                    $signData = [
                        'sign_date' => $signDate,
                        'sign_in_normal' => $signInNormal,
                        'sign_out_normal' => $signOutNormal,
                        'user_id' => $userId,
                        'sign_times' => $signTimes,
                        'must_attend_time' => $mustAttendTime,
                        'shift_id' => $shiftId,
                        'attend_type' => $attendType
                    ];
                    $where = [
                        'sign_date' => [$signDate],
                        'user_id' => [$userId],
                        'sign_times' => [$signTimes]
                    ];
                    if ($record = app($this->attendanceRecordsRepository)->getOneAttendRecord($where)) {
                        if ($signInTime && $record->sign_in_time > $signInTime) {
                            $this->initSignInData($signData, $signInTime, $platform, $clientIp);
                            if ($shift) {
                                $this->setSignInData($signData, $shift, $signInTime, $signDate, $signInNormal, $signOutNormal, $fullSignInNormal);
                            }
                            $isLag = $signData['is_lag'];
                        } else {
                            $isLag = $record->is_lag;
                            $signInTime = $record->original_sign_in_time;
                        }
                        if ($signOutTime && $signOutTime > $record->sign_out_time) {
                            $this->initSignOutData($signData, $signOutTime, $platform, $clientIp);
                            if ($shift) {
                                // 根据签退时间再次设置签到相关数据， 包含是否迟到判断，迟到时间
                                $this->resetSignInData($signData, $shift, $signDate, $isLag, $signInTime, $signOutTime, $fullSignInNormal, $fullSignOutNormal, $signInNormal, $signOutNormal, $record->sign_out_time);
                                // 获取设置签退时间相关数据，包含获取早退时间，判断是否早退等。
                                $this->setSignOutData($signData, $shift, $signInTime, $signOutTime, $signDate, $signInNormal, $signOutNormal, $fullSignOutNormal);
                            }
                        }
                        app($this->attendanceRecordsRepository)->updateData($signData, $where);
                        if ($signOutTime && $signOutTime > $record->sign_out_time) {
                            $this->overtime($record, $signData['sign_out_time']);
                        }
                    } else {
                        //过滤同步只有签退的钉钉数据
                        if (!$signInTime) {
                            continue;
                        }
                        $this->initSignInData($signData, $signInTime, $platform, $clientIp);
                        $this->initSignOutData($signData, $signOutTime, $platform, $clientIp);
                        if ($shift) {
                            // 设置签到相关数据，包含是否迟到判断，迟到时间
                            $this->setSignInData($signData, $shift, $signInTime, $signDate, $signInNormal, $signOutNormal, $fullSignInNormal);
                            // 根据签退时间再次设置签到相关数据， 包含是否迟到判断，迟到时间
                            $this->resetSignInData($signData, $shift, $signDate, $signData['is_lag'], $signInTime, $signOutTime, $fullSignInNormal, $fullSignOutNormal, $signInNormal, $signOutNormal);
                            // 获取设置签退时间相关数据，包含获取早退时间，判断是否早退等。
                            $this->setSignOutData($signData, $shift, $signInTime, $signOutTime, $signDate, $signInNormal, $signOutNormal, $fullSignOutNormal);
                        }
                        $signDataArray[] = $signData;
                    }
                }
            }
        }
        if (isset($signDataArray) && !empty($signDataArray)) {
            app($this->attendanceRecordsRepository)->insertMultipleData($signDataArray);
            foreach ($signDataArray as $signData) {
                if (isset($signData['sign_out_time']) && $signData['sign_out_time']) {
                    $signOutTime = $signData['sign_out_time'];
                    $signData['sign_out_time'] = '';
                    $signData['original_sign_out_time'] = '';
                    $signData = (object) $signData;
                    $this->overtime($signData, $signOutTime);
                }
            }
        }
        return true;
    }
    private function initSignInData(&$signData, $signInTime, $platform, $clientIp)
    {
        $signData['original_sign_in_time'] = $signInTime;
        $signData['sign_in_time'] = $signInTime;
        $signData['is_lag'] = 0;
        $signData['lag_time'] = 0;
        $signData['in_platform'] = $platform;
        $signData['in_ip'] = $clientIp;
        $signData['in_address'] = '';
        $signData['in_long'] = '';
        $signData['in_lat'] = '';
        $signData['created_at'] = $signInTime;
    }
    private function initSignOutData(&$signData, $signOutTime, $platform, $clientIp)
    {
        $signData['original_sign_out_time'] = $signOutTime;
        $signData['sign_out_time'] = $signOutTime;
        $signData['leave_early_time'] = 0;
        $signData['is_leave_early'] = 0;
        $signData['out_platform'] = $platform;
        $signData['out_ip'] = $clientIp;
        $signData['out_address'] = '';
        $signData['out_long'] = '';
        $signData['out_lat'] = '';
    }
    private function setSignInData(&$signData, $shift, $signInTime, $signDate, $signInNormal, $signOutNormal, $fullSignInNormal)
    {
        $lagTime = $this->getLagTime($shift, $signInTime, $signDate, $signInNormal, $signOutNormal);
                            // 如果班次开启了允许迟到几分钟不算迟到，则$signInTime设为$fullSignInNormal；
        if ($signInTime > $fullSignInNormal && $lagTime == 0 && $shift->allow_late == 1) {
            $signInTime = $fullSignInNormal;
        }
        $signData['sign_in_time'] = $signInTime;
        $signData['is_lag'] = $lagTime > 0 ? 1 : 0;
        $signData['lag_time'] = $lagTime;
    }
    private function setSignOutData(&$signData, $shift, $signInTime, $signOutTime, $signDate, $signInNormal, $signOutNormal, $fullSignOutNormal)
    {
        list($resetEarly, $earlyTime) = $this->getEarlyTime($shift, $signInTime, $signOutTime, $signDate, $signInNormal, $signOutNormal); 
        if ($resetEarly) {
            $signData['leave_early_time'] = $earlyTime;
            if ($earlyTime == 0) {
                $signData['is_leave_early'] = 0;
                $signData['sign_out_time'] = $fullSignOutNormal;
            } else {
                $signData['is_leave_early'] = 1;
            }
        }
    }
    private function resetSignInData(&$signData, $shift, $signDate, $isLag, $signInTime, $signOutTime, $fullSignInNormal, $fullSignOutNormal, $signInNormal, $signOutNormal, $oldSignOutTime = null)
    {
        list($reset, $lagTime) = $this->resetSignInLagTime($shift, $signDate,$isLag, $signInTime, $signOutTime, $fullSignInNormal, $fullSignOutNormal, $signInNormal, $signOutNormal, $oldSignOutTime); 
        if ($reset) {
            $signData['lag_time'] = $lagTime;
            if ($lagTime == 0) {
                $signData['is_lag'] = 0;
                $signData['sign_in_time'] = $fullSignInNormal;
            } else {
                $signData['is_lag'] = 1;
            }
        }
    }
    private function getOneUserSchedulingByDate($date, $userId)
    {
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
    private function getSchedulingInfoBySchedulingId($schedulingId)
    {
        static $schedulings = [];
        if(isset($schedulings[$schedulingId])) {
            return $schedulings[$schedulingId];
        }

        return $schedulings[$schedulingId] = app($this->attendanceSchedulingRepository)->getOneScheduling(['scheduling_id' => [$schedulingId]]);
    }
    private function getMoreUserSchedulingByDateScope($startDate, $endDate, $userIds)
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
                    $scopeEndDate = min($modifyDate, $endDate);
                    $scheduling = $this->getSchedulingInfoBySchedulingId($schedulingId);
                    $this->combineSchedulingByDateRange($schedulings, $scopeBeginDate, $scopeEndDate, $scheduling);
                    $scopeBeginDate = $this->getNextDate($modifyDate);
                }
                if ($scopeBeginDate < $endDate) {
                    if (isset($currentSchedulingIdMap[$userId])) {
                        $scheduling = $this->getSchedulingInfoBySchedulingId($currentSchedulingIdMap[$userId]);
                        $this->combineSchedulingByDateRange($schedulings, $scopeBeginDate, $endDate, $scheduling);
                    }
                }
            } else {
                if (isset($currentSchedulingIdMap[$userId])) {
                    $scheduling = $this->getSchedulingInfoBySchedulingId($currentSchedulingIdMap[$userId]);
                    $this->combineSchedulingByDateRange($schedulings, $startDate, $endDate, $scheduling);
                }
            }
            $schedulingMap[$userId] = $schedulings;
        }

        return $schedulingMap;
    }
    private function combineSchedulingByDateRange(&$schedulings, $startDate, $endDate, $scheduling = null)
    {
        $dates = $this->getDateFromRange($startDate, $endDate);
        foreach($dates as $date) {
            $schedulings[$date] = $scheduling;
        }
    }
    private function checkUserScheduling($signDate, $userId)
    {
        $scheduling = $this->getOneUserSchedulingByDate($signDate, $userId);
        if (!$scheduling) {
            $this->throwException(['code' => ['0x044038', 'attendance']]);
        }
        if ($scheduling->status == 0) {
            $this->throwException(['code' => ['0x044045', 'attendance']]);
        }
        $schedulingShiftMap = $this->getSchedulingBySchedulingIdAndDate($scheduling->scheduling_id, $signDate);
        
        $shift = $schedulingShiftMap ? $this->getShiftById($schedulingShiftMap['shift_id']) : null;
        
        if (!$shift && $scheduling->allow_sign_holiday == 0) {
            $this->throwException(['code' => ['0x044017', 'attendance']]);
        }
        return $shift;
    }
    public function addSimpleRecords($user_id, $sign_date, $sign_time, $sign_type, $platform, $ip, $long, $lat, $address, $remark)
    {
        list($year, $month, $day) = explode('-', $sign_date);

        $data = compact('user_id', 'sign_date', 'sign_time', 'year', 'month', 'sign_type', 'platform', 'ip', 'long', 'lat', 'address', 'remark');

        return app('App\EofficeApp\Attendance\Repositories\AttendanceSimpleRecordsRepository')->insertData($data);
    }
    private function ipControl($own)
    {
        $group = [
            'user_id' => $own['user_id'],
            'dept_id' => $own['dept_id'],
            'role_id' => $own['role_id'],
        ];

        return app('App\EofficeApp\IpRules\Services\IpRulesService')->accessControl($group, 1);
    }
    private function checkAttendPlatform($platform, $userId)
    {
        if ($platform == 1) {
            if (!$this->getSystemParam('attendance_web_useed', 0)) {
                $this->throwException(['code' => ['0x044034', 'attendance']]);
            } else {
                $deptId = $this->getUserDeptId($userId);
                $roleIds = $this->getUserRoleIds($userId);
                if (!app($this->attendancePcSignRepository)->isInScope($userId, $deptId, $roleIds)) {
                    $this->throwException(['code' => ['0x044069', 'attendance']]);
                }
            }
        } else {
            if ($this->isMobilePlatform($platform) && !$this->getSystemParam('attendance_mobile_useed', 0)) {
                $this->throwException(['code' => ['0x044023', 'attendance']]);
            }
        }
    }
    
    private function addMobileLocationRecord($user_id, $sign_date, $sign_time, $ip, $long, $lat, $address, $wifi_name, $wifi_mac, $platform, $sign_type, $sign_status, $sign_category, $customer_id = 0, $remark ='')
    {
        $mobileData = compact('user_id', 'sign_date', 'sign_time', 'ip', 'long', 'lat', 'address','wifi_name','wifi_mac', 'platform', 'sign_type', 'sign_status', 'sign_category', 'customer_id', 'remark');

        return app($this->attendanceMobileRecordsRepository)->insertData($mobileData);
    }
    /**
     * 上报位置
     *
     * @param type $data
     * @param type $userId
     *
     * @return boolean
     */
    public function reportLocation($data, $userId)
    {
        $data['user_id'] = $userId;
        $data['sign_date'] = $this->formatSignDate($data);
        $data['sign_time'] = $this->defaultValue('sign_time', $data, date('Y-m-d H:i:s'));
        $data['ip'] = getClientIp();
        $data['sign_type'] = 0;
        $data['sign_status'] = 0;
        $data['platform'] = $this->getMobilePlatformId($data['platform']);
        $this->addSimpleRecords($userId, $data['sign_date'], $data['sign_time'], 0, $data['platform'], $data['ip'], $data['long'] ?? '', $data['lat'] ?? '', $data['address'] ?? '', $data['remark'] ?? '');
        $result = app($this->attendanceMobileRecordsRepository)->insertData($data);
        if (!empty($data['postionImage'])) {
            app('App\EofficeApp\Attachment\Services\AttachmentService')->attachmentRelation('attend_mobile_records', $result->record_id, $data['postionImage']);
        }

        return $result;
    }
    /**
     * 申请校准
     *
     * @param int $recordId
     *
     * @return boolean
     */
    public function applyCalibration($data, $recordId, $own)
    {
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord(['record_id' => [$recordId]]);
        $signDateTime = strtotime($record->sign_date);
        $month = date('m', $signDateTime);
        $year = date('Y', $signDateTime);
        $endDate = $this->getMonthEndDate($year, intval($month));
        $startDate = $year . '-' . $month . '-01';
        $calibrationConfig = app($this->attendanceSettingService)->getCommonSetting('calibration');
        if(!$calibrationConfig['allow_calibration']) {
            return ['code' => ['0x044058', 'attendance']];
        }

        if($record->calibration_status==0 && $calibrationConfig['limit_calibration_record_count']) {
            $calibrationCount = app($this->attendanceRecordsRepository)->getRecordsCount(['calibration_status' => [0, '!='], 'user_id' => [$own['user_id']], 'sign_date' => [[$startDate, $endDate], 'between']]);
            if($calibrationCount >= $calibrationConfig['calibration_record_count']) {
                return ['code' => ['0x044059', 'attendance']];
            }
        }
        if ($calibrationConfig['limit_apply_calibration_time']) {
            if($record->calibration_status == 0) {
                $days = (strtotime($this->currentDate) - $signDateTime) / 3600 / 24;
                if($days > $calibrationConfig['apply_calibration_time']) {
                     return ['code' => ['0x044056', 'attendance']];
                }
            }
        }
        if ($calibrationConfig['limit_calibration_count_pre_record']) {
            if ($record->calibration_status == 3) {
                if ($calibrationConfig['calibration_count_pre_record'] <= $record->calibration_count) {
                    return ['code' => ['0x044057', 'attendance']];
                }
            }
        }

        $calibrationData = [
            'calibration_status' => 1,
            'calibration_count' => intval($record->calibration_count) + 1,
            'calibration_reason' => $data['calibration_reason'],
            'calibration_sign' => $data['calibration_sign'] ?? 1,
        ];

        $result = app($this->attendanceRecordsRepository)->updateData($calibrationData, ['record_id' => [$recordId]]);
        if($result) {
            $remindConfig = app($this->attendanceSettingService)->getCommonSetting('sign_remind');
            if ($remindConfig['open_calibration_remind'] == 0) {
                return false;
            }
            $managerUserIds = app($this->attendanceSettingService)->getManageUserByUserId($own, 248);
            if(!empty($managerUserIds)) {
                $message = [
                    'remindMark' => 'attendance-calibration',
                    'toUser' => $managerUserIds,
                ];
                Eoffice::sendMessage($message);
            }
        }
    }
    private function addCalibrationApproveLog($signDate, $userId, $signInTime, $signOutTime, $approver, $status)
    {
        $data = [
            'sign_date' => $signDate,
            'user_id' => $userId,
            'old_sign_in' => $signInTime,
            'old_sign_out' => $signOutTime,
            'approver' => $approver,
            'calibration_status' => $status,
            'approve_time' => date('Y-m-d H:i:s')
        ];
        return app($this->attendanceCalibrationLogRepository)->insertData($data);
    }
    public function returnedCalibration($data, $recordId, $own)
    {
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord(['record_id' => [$recordId]]);

        $calibrationData = [
            'calibration_status' => 3,
            'calibration_reason' => $data['calibration_reason'],
        ];

        $result = app($this->attendanceRecordsRepository)->updateData($calibrationData, ['record_id' => [$recordId]]);
        if($result) {
            $remindConfig = app($this->attendanceSettingService)->getCommonSetting('sign_remind');
            if ($remindConfig['open_calibration_remind'] == 0) {
                return false;
            }
            $message = [
                'remindMark' => 'attendance-calibration_refuse',
                'toUser' => [$record->user_id],
                'contentParam' => ['signDate' => $record->sign_date]
            ];
            Eoffice::sendMessage($message);

            $this->addCalibrationApproveLog($record->sign_date, $record->user_id, $record->sign_in_time, $record->sign_out_time, $own['user_id'], 3);
        }
        return $result;
    }

    public function approveCalibration($data, $recordId, $own)
    {
        $calibrationData = [
            'calibration_status' => 2,
            'calibration_reason' => $data['calibration_reason']
        ];
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord(['record_id' => [$recordId]]);
        $signTimeArray = app($this->attendanceShiftsSignTimeRepository)->getSignTime($record->shift_id);
        $signTime = $signTimeArray[$record->sign_times - 1];
        $restTime = $this->getRestTime($record->shift_id);
        $time = 0;
        $calibrationSign = $record->calibration_sign ?? 1;
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $signTime->sign_in_time, $signTime->sign_out_time);
        // 校准签到卡
        if ($calibrationSign == 1 || $calibrationSign == 2) {
            if ($record->is_lag == 1) {
                $calibrationData['sign_in_time'] = $signInNormal;
                $calibrationData['original_sign_in_time'] = $signInNormal;
                $calibrationData['lag_time'] = 0;
                $calibrationData['is_lag'] = 0;
                $time += $this->datetimeDiff($signInNormal, min(max($record->sign_in_time, $signInNormal), $signOutNormal));
                $time -= $this->getRestTotal($restTime, $signInNormal, $record->sign_in_time, $signTime->sign_in_time, $signTime->sign_out_time, $record->sign_date);
            }
        }
        //校准签退卡
        if ($calibrationSign == 1 || $calibrationSign == 3) {
            if ($record->is_leave_early == 1) {
                $calibrationData['sign_out_time'] = $signOutNormal;
                $calibrationData['original_sign_out_time'] = $signOutNormal;
                $calibrationData['leave_early_time'] = 0;
                $calibrationData['is_leave_early'] = 0;
                $time += $this->datetimeDiff(max(min($record->sign_out_time, $signOutNormal), $signInNormal), $signOutNormal);
                $time -= $this->getRestTotal($restTime, $record->sign_out_time, $signOutNormal, $signTime->sign_in_time, $signTime->sign_out_time, $record->sign_date);
            }

            if ($record->sign_out_time == '') {
                $time = $record->must_attend_time;
                $calibrationData['sign_out_time'] = $signOutNormal;
                $calibrationData['original_sign_out_time'] = $signOutNormal;
            }
        }
        $calibrationData['calibration_time'] = $time;
        $calibrationData['calibration_aprove_time'] = date('Y-m-d H:i:s');
        $result = app($this->attendanceRecordsRepository)->updateData($calibrationData, ['record_id' => [$recordId]]);
        if ($result) {
            $this->addCalibrationApproveLog($record->sign_date, $record->user_id, $record->sign_in_time, $record->sign_out_time, $own['user_id'], 2);
        }
        return $result;
    }

    /**
     * 获取考勤校验信息
     *
     * @param int $recordId
     *
     * @return object
     */
    public function getCalibrationInfo($recordId)
    {
        return app($this->attendanceRecordsRepository)->getOneAttendRecord(['record_id' => [$recordId]], ['calibration_status', 'calibration_reason', 'calibration_time', 'calibration_sign', 'sign_in_time', 'sign_out_time','sign_in_normal', 'sign_out_normal']);
    }
    /**
     * 一键校验
     *
     * @param type $userId
     *
     * @return boolean
     */
    public function approveAllApply($own, $applySign = 0)
    {
        $userIds = app($this->attendanceSettingService)->getPurviewUser($own, 248);

        if (empty($userIds)) {
            return true;
        }

        $where = ['calibration_status' => [1], 'attend_type' => [1]];

        if ($userIds != 'all') {
            $where['user_id'] = [$userIds, 'in'];
        }
        if ($applySign) {
            $where['calibration_sign'] = [$applySign];
        }
        $records = app($this->attendanceRecordsRepository)->getRecords($where, ['record_id', 'calibration_reason']);

        if (count($records) > 0) {
            foreach ($records as $record) {
                $calibrationReasion = ($record->calibration_reason ? $record->calibration_reason . "\r\n" : '') . trans("attendance.key_to_calibrate");

                $this->approveCalibration(['calibration_reason' => $calibrationReasion], $record->record_id, $own);
            }
        }

        return true;
    }
    /**
     * 获取校验列表
     *
     * @param array $param
     * @param string $userId
     *
     * @return array
     */
    public function getCalibrationRecords($param, $own)
    {
        $param = $this->parseParams($param);
        $userIds = app($this->attendanceSettingService)->getPurviewUser($own, 248);
        if (empty($userIds)) {
            return ['total' => 0, 'list' => []];
        }

        if (isset($param['search'])) {
            $filterUserIds = isset($param['search']['user_id']) ? $param['search']['user_id'] : [];
            if ($userIds != 'all') {
                if (empty($filterUserIds)) {
                    $param['search']['user_id'] = [$userIds, 'in'];
                } else {
                    $intersectUserId = array_intersect($filterUserIds, $userIds);
                    $param['search']['user_id'] = [$intersectUserId, 'in'];
                }
            }
        } else {
            if ($userIds != 'all') {
                $param['search'] = ['user_id' => [$userIds, 'in']];
            }
        }

        $param['search']['attend_type'] = [1];

        if (!isset($param['search']['calibration_status']) || $param['search']['calibration_status'][0]== 'all') {
            $param['search']['calibration_status'] = [[1, 2, 3], 'in'];
        }

        $data = $this->response(app($this->attendanceRecordsRepository), 'getRecordsTotal', 'getRecordsLists', $param);

        if (count($data['list']) > 0) {
            foreach ($data['list'] as $key => $item) {
                $item->user_name = get_user_simple_attr($item->user_id);
                $data['list'][$key] = $item;
            }
        }

        return $data;
    }
    /**
     * 获取节假日列表（未知用处）
     *
     * @return array
     */
    public function listRests()
    {
        $rests = app($this->attendanceRestRepository)->getAllRests()->toArray();
        foreach ($rests as $key => &$rest) {
            $rest['rest_name'] = mulit_trans_dynamic('attend_rest.rest_name.rest_name.' . $rest['rest_name']);
        }
        return $rests;
    }
    /**
     * 获取数据外发列表
     *
     * @param type $param
     * @param type $type
     *
     * @return type
     */
    public function getFlowOutsendList($param, $type, $own)
    {
        $viewUser = app($this->attendanceSettingService)->getPurviewUser($own, 53);
        $repository = 'attendance' . ucfirst($type) . 'Repository';

        $param = $this->parseParams($param);

        if (!isset($param['search'])) {
            $param['search'] = [];
        }
        $paramType = $this->defaultValue('type', $param);
        if ($paramType == 'dept' && isset($param['search']['dept_id'])) {
            $deptId = $param['search']['dept_id'][0];
            unset($param['search']['dept_id']);
            $users = app($this->userRepository)->getUserByDepartment($deptId)->toArray();
            $userIds = array_column($users, 'user_id');
            if ($viewUser != 'all') {
                $userIds = array_intersect($userIds, $viewUser);
            }
            $param['search']['user_id'] = [$userIds, 'in'];
        } else {
            if ($viewUser != 'all' && !isset($param['search']['user_id'])) {
                $param['search']['user_id'] = [$viewUser, 'in'];
            }
        }
        if (!isset($param['apply_time'])) {
            $year = $this->defaultValue('year', $param, date('Y'));
            $month = $this->defaultValue('month', $param, date('m'));
            $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $start = $this->formatDateMonth($year, $month) . '-01 00:00:00';
            $end = $this->formatDateMonth($year, $month) . '-' . $days . ' 23:59:59';
        } else {
            $param['apply_time'] = json_decode($param['apply_time'], true);
            $start = $param['apply_time']['start'] . ' 00:00:00';
            $end = $param['apply_time']['end'] . ' 23:59:59';
        }
        if (!isset($this->outSendTimeKeys[$type])) {
            return [];
        }
        $outDate = $this->outSendTimeKeys[$type];
        $orSearch = [];
        foreach ($outDate as $dateSearchField) {
            $orSearch[$dateSearchField] = [[$start, $end], 'between'];
        }
        $param['orSearch'] = $orSearch;
        $data = app($this->$repository)->getOutSendDataLists($param);
        //驼峰转下划线
        $type = Str::snake($type, '_');
        if (count($data) > 0) {
            foreach ($data as $key => $item) {
                $data[$key]->user_name = get_user_simple_attr($item->user_id);
                $data[$key]->timeline = $item->{$outDate[0]};
                if ($item->vacation_id) {
                    $vacation = $this->getVacationInfo($item->vacation_id);
                    $data[$key]->vacation_name = $vacation->vacation_name ?? null;
                }
                $data[$key]->start = $item->{$outDate[0]};
                $data[$key]->end = isset($outDate[1]) ? $item->{$outDate[1]} : null;
                $data[$key]->days = $this->roundDays($item->{$type . '_days'});
                $data[$key]->hours = $this->roundHours($item->{$type . '_hours'});
                $data[$key]->reason = $item->{$type . '_reason'};
                $data[$key]->extra = json_decode($item->{$type . '_extra'});
            }
        }
        return $data;
    }
    /**
     * 获取用户排班日期和对应的班次id和名称(包括节假日)。
     *
     * @param int $year
     * @param int $month
     * @param string $userId
     *
     * @return array
     */
    public function getUserSchedulingDate($year, $month, $userId, $own = false)
    {
        //考勤权限，用户排班信息菜单
        if ($own) {
            $viewUser = app($this->attendanceSettingService)->getPurviewUser($own, 253);
            if ($viewUser != 'all' && !in_array($userId, $viewUser)) {
                return ['code' => ['0x000017', 'common']];
            }
        }
        $result = array();
        list($startDate, $endDate) = $this->getMonthStartEnd($year, $month);
        //班次
        $shifts = $this->getShiftTimesGroupByDate($startDate, $endDate, $userId);
        if ($shifts) {
            foreach ($shifts as $date => $shift) {
                if ($shift) {
                    $result[$date] = [$shift->shift_id, $shift->shift_name];
                }
            }
        }
        //节假日
        $holidays = $this->getHolidayGroupByDate($startDate, $endDate, $userId);
        if ($holidays) {
            foreach ($holidays as $date => $holiday) {
                $holidayName = mulit_trans_dynamic('attend_rest.rest_name.rest_name.' . $holiday->rest_name);
                $result[$date] = [0, $holidayName];
            }
        }
        return $result;
    }
    public function getMobileMySchedulingDate($year, $month, $userId)
    {
        list($startDate, $endDate) = $this->getMonthStartEnd($year, $month);
        //班次
        return $this->getShiftTimesGroupByDate($startDate, $endDate, $userId);
    }
    /**
     * 获取用户节假日和对应的节假日id和名称
     */
    public function getUserSchedulingRest($year, $month, $userId)
    {
        list($startDate, $endDate) = $this->getMonthStartEnd($year, $month);
        $holidays = $this->getHolidayGroupByDate($startDate, $endDate, $userId);
        $currentHolidays = [];
        if (count($holidays) > 0) {
            $allSchemes = $this->getAllScheme(); //获取所有节假日方案
            foreach ($holidays as $date => $holiday) {
                $currentHolidays[$date] = [
                    'rest_id' => $holiday->rest_id,
                    'rest_name' => mulit_trans_dynamic('attend_rest.rest_name.rest_name.' . $holiday->rest_name),
                    'rest_color' => $allSchemes[$holiday->scheme_id]['color'] ?? '',
                ];
            }
        }
        return $currentHolidays;
    }
    /**
     * 获取所有的节假日方案数组，以id作为键值
     *
     * @param type $field
     *
     * @return array
     */
    private function getAllScheme()
    {
        $schemes = app($this->attendanceRestSchemeRepository)->getAllSchemes(true)->toArray();

        return $this->arrayMapWithKeys($schemes, 'scheme_id');
    }
    /**
     * 判断是否是移动考勤，并且判断是否在考勤范围内
     *
     * @param string $userId
     * @param array $data
     * @param int $platform
     *
     * @return boolean
     */
    private function checkMobileAttend($userId, $data, $platform)
    {
        //判断考勤平台是否是移动考勤
        if (!$this->isMobilePlatform($platform)) {
            return 0;
        }
        //判断是否开启移动考勤
        if ($this->getSystemParam('attendance_mobile_useed') == 0) {
            $this->throwException(['code' => ['0x044023', 'attendance']]);
        }

        $deptId = $this->getUserDeptId($userId);
        $roleIds = $this->getUserRoleIds($userId);
        $locationMode = $this->getSystemParam('attend_mobile_type_location', 1);
        $wifiMode = $this->getSystemParam('attend_mobile_type_wifi', 1);
        if ($locationMode && $wifiMode) {
            if (isset($data['attend_mobile_type'])) {
                $mobileAttendType = $data['attend_mobile_type'];
            } else {
                $mobileAttendType = $this->hasAll($data, ['lat', 'long']) ? 1 : 2;
            }
        } elseif ($locationMode) {
            $mobileAttendType = 1;
        } else {
            $mobileAttendType = 2;
        }
        if ($mobileAttendType == 1) {
            $points = app($this->attendancePointsRepository)->getPurviewPoints($userId, $deptId, $roleIds);
            if (count($points) > 0) {
                foreach ($points as $point) {
                    $minRadiu = $point->allow_accuracy_deviation == 1 ? 300 : 50;
                    
                    $attendPointsRadiu = max($point->point_radius, $minRadiu);

                    $distance = $this->getDistance($point->point_longitude, $point->point_latitude, $data['long'], $data['lat']);

                    if ($distance < $attendPointsRadiu) {
                        return 1;
                    }
                }
                $this->throwException(['code' => ['0x044025', 'attendance']]);
            }

            $this->throwException(['code' => ['0x044024', 'attendance']]);
        } else {
            if (!isset($data['attend_wifi_mac']) || empty($data['attend_wifi_mac'])) {
                $this->throwException(['code' => ['0x044037', 'attendance']]);
            }
            if (!$this->isInWifiSignScope($data['attend_wifi_mac'], $userId, $deptId, $roleIds)) {
                $this->throwException(['code' => ['0x044035', 'attendance']]);
            }

            return 2;
        }
    }
    private function isInWifiSignScope($wifiMac, $userId, $deptId, $roleIds)
    {
        $wifiInfo = app($this->attendanceWifiRepository)->getWifiInfo(['attend_wifi_mac' => [$this->getAllWiFiMac($wifiMac),'in']]);
        if ($wifiInfo) {
            if ($wifiInfo->attend_all == 1) {
                return true;
            }

            $attendUser = json_decode($wifiInfo->attend_user);
            $attendDept = json_decode($wifiInfo->attend_dept);
            $attendRole = json_decode($wifiInfo->attend_role);

            if (!empty($attendUser) && in_array($userId, $attendUser)) {
                return true;
            }
            if (!empty($attendDept) && in_array($deptId, $attendDept)) {
                return true;
            }

            if (!empty($attendRole)) {
                foreach ($roleIds as $roleId) {
                    if (in_array($roleId, $attendRole)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
    /**
     * 获取前端传入的WiFi-MAC的所有可能形式
     * @param $wifiMac
     * @return array|string
     */
    private function getAllWiFiMac($wifiMac)
    {
        $allTypeWiFiMac = [];
        if (!$wifiMac) {
            return $allTypeWiFiMac;
        }
        $wifiMacArray = explode(':', $wifiMac);
        $allTypeWiFiMac[] = strtolower(implode(':', array_map(function ($row) {
                            return strlen($row) == 1 ? '0' . $row : $row;
                        }, $wifiMacArray)));
        $allTypeWiFiMac[] = strtolower(implode(':', array_map(function ($row) {
                            return (strlen($row) == 2 && substr($row, 0, 1) == '0' && !preg_match("/^[A-Za-z]/", substr($row, 1, 1))) ? intval($row) : $row;
                        }, $wifiMacArray)));
        $allTypeWiFiMac[] = strtoupper($allTypeWiFiMac[0]);
        $allTypeWiFiMac[] = strtoupper($allTypeWiFiMac[1]);
        return $allTypeWiFiMac;
    }
    public function getOneMonthSignRecords($userId, $year, $month)
    {
        $orderBy = ['sign_date' => 'desc', 'sign_time' => 'desc'];
        $attendance = app($this->attendanceSimpleRecordsRepository)->getMoreRecords($year, $month, [$userId], [], $orderBy);
        $result = [];
        foreach ($attendance as $value) {
            $result[$value->user_id . $value->sign_date . $value->sign_time] = $value;
        }
        return array_values($result);
    }
    public function getAttendanceRecords($data)
    {
        $user_id     = isset($data['user_id']) ? $data['user_id'] : '';
        $sign_date   = isset($data['sign_date']) ? $data['sign_date'] : '';
        $recordList  = app($this->attendanceRecordsRepository)->getAttendanceRecord($user_id, $sign_date, 'get');
        $recordTotal = app($this->attendanceRecordsRepository)->getAttendanceRecord($user_id, $sign_date, 'count');
        return ['total' => $recordTotal, 'list' => $recordList];
    }
    /**
     * 发送打卡提醒
     * 
     * @param type $userId
     * @param type $signDate
     * @param type $number
     * @param type $signType
     * 
     * @return boolean
     */
    public function sendSignRemind($userId, $signDate, $number = 1, $signType = 'sign_in')
    {
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord(['user_id' => [$userId], 'sign_date' => [$signDate], 'sign_times' => [$number]]);
        // 签退提醒，有打卡记录并且有签退时间时不提醒。
        if ($signType == 'sign_out' && $record && $record->sign_out_time) {
            return true;
        }
        // 签到提醒，有打卡记录时不提醒。
        if ($signType == 'sign_in' && $record) {
            return true;
        }
        $message = [
            'remindMark' => 'attendance-' . $signType,
            'toUser' => [$userId],
        ];
        Eoffice::sendMessage($message);
        return true;
    }
    /**
     * 设置打卡提醒任务
     * @return boolean
     */
    public function setSignRemindJob()
    {
        $remindConfig = app($this->attendanceSettingService)->getCommonSetting('sign_remind');
        if ($remindConfig['open_sign_remind'] == 0) {
            return false;
        }
        $signInRemindStartTime = $remindConfig['sign_in_remind_start_time'] ?? 0;
        $signInRemindCount = $remindConfig['sign_in_remind_count'] ?? 1;
        $signInRemindInterval = $remindConfig['sign_in_remind_interval'] ?? 1;
        $signOutRemindStartTime = $remindConfig['sign_out_remind_start_time'] ?? 0;
        $signOutRemindCount = $remindConfig['sign_out_remind_count'] ?? 1;
        $signOutRemindInterval = $remindConfig['sign_out_remind_interval'] ?? 1;
        $allowUserNoSignRemind = $remindConfig['allow_user_no_sign_remind'] ?? 0;
        $currentDate = date('Y-m-d');
        // 1、获取打卡提醒用户ID。
        // 2、过滤没有考勤模块权限的用户ID。
        // 3、过滤无需打卡提醒的用户ID。
        $authUserIds =  $this->getOwnAttendaceUserIds();// 过滤没有考勤模块权限的用户ID。
        $diffUserIds = $allowUserNoSignRemind ? $this->getNoSignRemindUserIds($remindConfig) : []; // 无需打卡提醒的用户ID
        $users = app($this->userRepository)->getSimpleUserList(['noPage' => true]);
        $userIds = array_diff(array_intersect(array_column($users, 'user_id'), $authUserIds), $diffUserIds);
        // 获取所有用户的排班
        $schedulings = $this->schedulingMapWithUserIdsByDate($currentDate, $userIds);
        $attendanceSchedulingRepository = app($this->attendanceSchedulingRepository);
        $schedulingCaches = [];
        foreach ($schedulings as $userId => $scheduling) {
            if (!$scheduling || !$scheduling['shift_id'] || !$scheduling['scheduling_id']) {
                continue;
            }
            // 判断排班是否启用，此处用了享元模式
            $schedulingId = $scheduling['scheduling_id'];
            if (isset($schedulingCaches[$schedulingId])) {
                $schedulingDetail = $schedulingCaches[$schedulingId];
            } else {
                $schedulingDetail = $attendanceSchedulingRepository->getDetail($schedulingId);
                $schedulingCaches[$schedulingId] = $schedulingDetail;
            }
            if (!$schedulingDetail || $schedulingDetail->status === 0) {
                continue;
            }
            $shiftTimes = $this->getShiftTimeById($scheduling['shift_id']);
            if (empty($shiftTimes)) {
                continue;
            }
            foreach ($shiftTimes as $key => $shiftTime) {
                list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($currentDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                $signNumber = $key + 1;
                // 设置签到提醒
                $this->setSignRemindLaterJob($userId, $currentDate, $signInRemindCount, $signInRemindStartTime, $signInRemindInterval, $signInNormal, $signNumber, 'sign_in');
                // 设置签退提醒
                $this->setSignRemindLaterJob($userId, $currentDate, $signOutRemindCount, $signOutRemindStartTime, $signOutRemindInterval, $signOutNormal, $signNumber, 'sign_out');
            }
        }
    }
    
    public function setAutoSignOutJob()
    {
        $config = app($this->attendanceSettingService)->getCommonSetting('no_sign_out');
        if ($config['allow_auto_sign_out'] == 0) {
            return true;
        }
        $allowAutoSignOutUserIds = $this->getUserIdByUserRoleDeptConfig($config, 'auto_all_member', 'auto_user_id', 'auto_dept_id', 'auto_role_id');
        if (empty($allowAutoSignOutUserIds)){
            return true;
        }
        // 获取所有用户的排班
        $currentDate = date('Y-m-d');
        $schedulings = $this->schedulingMapWithUserIdsByDate($currentDate, $allowAutoSignOutUserIds);
        if (empty($schedulings)) {
            return true;
        }
        foreach ($schedulings as $userId => $scheduling) {
            if (!$scheduling || !$scheduling['shift_id']) {
                continue;
            }
            $shiftTimes = $this->getShiftTimeById($scheduling['shift_id']);
            if (empty($shiftTimes)) {
                continue;
            }
            foreach ($shiftTimes as $key => $shiftTime) {
                list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($currentDate, $shiftTime->sign_in_time, $shiftTime->sign_out_time);
                $signDelay = strtotime($signOutNormal) - strtotime($this->combineDatetime($currentDate, '00:00:00'))  + 60;
                Queue::later($signDelay, new AutoSignOutJob($userId, $currentDate, $signOutNormal, $key + 1));
            }
        }
    }
    public function autoSignOut($userId, $signDate, $signOutTime, $signNumber)
    {
        $signData = [
            'leave_early_time' => 0,
            'is_leave_early' => 0,
            'original_sign_out_time' => $signOutTime,
            'sign_out_time' => $signOutTime,
            'out_ip' => '127.0.0.1',
            'out_long' => '',
            'out_lat' => '',
            'out_address' => '',
            'out_platform' => 14
        ];
        $wheres = ['sign_date' => [$signDate], 'sign_times' => [$signNumber], 'user_id' => [$userId]];
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord($wheres);
        if (!$record) {
            return false;
        }
        $result = true;
        if (!$record->sign_out_time || $record->sign_out_time < $signOutTime) {
            $result = app($this->attendanceRecordsRepository)->updateData($signData, $wheres);
        }
        $this->addSimpleRecords($userId, $signDate, $signOutTime, 2, 14, '127.0.0.1', '', '', '', '自动签退');
        return $result ? $signData : false;
    }
    /**
     * 设置打卡提醒延迟任务
     * 
     * @param type $userId
     * @param type $signDate
     * @param type $remindCount
     * @param type $remindStartTime
     * @param type $remindInterval
     * @param type $signNormal
     * @param type $signNumber
     * @param type $signType
     */
    private function setSignRemindLaterJob($userId, $signDate, $remindCount, $remindStartTime, $remindInterval, $signNormal, $signNumber = 1, $signType = 'sign_in')
    {
        for ($i = 0; $i < $remindCount; $i ++) {
            $signDelay = strtotime($signNormal) - strtotime($this->combineDatetime($signDate, '00:00:00')) + $remindInterval * 60 * $i;
            $signDelay = $signType === 'sign_in' ? ($signDelay - $remindStartTime * 60) : ($signDelay + $remindStartTime * 60);
            Queue::later($signDelay, new SendSignRemindJob($userId, $signDate, $signNumber, $signType));
        }
    }
    /**
     * 获取不需要打卡提醒的用户
     * 
     * @param type $data
     * 
     * @return array
     */
    private function getNoSignRemindUserIds($data)
    {
        $userId = $data['user_id'] ?? [];
        $deptId = $data['dept_id'] ?? [];
        $roleId = $data['role_id'] ?? [];
        if (!empty($roleId)) {
            $roleUsers = app('App\EofficeApp\Role\Repositories\UserRoleRepository')->getUserRole(['role_id' => [$roleId, 'in']], 1);
            $roleUserId = count($roleUsers) > 0 ? array_column($roleUsers, 'user_id') : [];
        }
        if (!empty($deptId)) {
            $deptUsers = app($this->userRepository)->getUserByAllDepartment($deptId);
            $deptUserId = count($deptUsers) > 0 ? array_column($deptUsers->toArray(), 'user_id') : [];
        }
        
        return array_unique(array_merge($userId, $roleUserId, $deptUserId));
        
    }
    /**
     * 获取有考勤模块权限的用户
     * 
     * @return array
     */
    private function getOwnAttendaceUserIds()
    {
        $menuMap = app('App\EofficeApp\Menu\Repositories\RoleMenuRepository')->getMenusGroupByMenuId(['menu_id' => [32]]);
        $roleIds = array_column($menuMap, 'role_id');
        if(empty($roleIds)) {
            return [];
        }
        $users = app('App\EofficeApp\Role\Repositories\UserRoleRepository')->getUserRole(['role_id' => [$roleIds, 'in']], 1);
        if(count($users) > 0) {
            return array_column($users, 'user_id');
        }
        return [];
    }
}