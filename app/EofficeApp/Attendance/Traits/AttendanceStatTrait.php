<?php
namespace App\EofficeApp\Attendance\Traits;

/**
 * Description of AttendanceParamsTrait
 *
 * @author lizhijun
 */
trait AttendanceStatTrait 
{
   private function statNoSignOutRealAttendTime($record, $signDate, $attendTime, $shiftTimes, $offsetDatetimes, $shiftId, $isNormal = true, $allowNoSignOut = false)
    {
        if ($allowNoSignOut) { //允许不用签退
            // 统计实际出勤时间
            return $this->getRealAttendTime($record, $signDate, $attendTime, function($record, $signOutNormal) use($signDate) {
                if($signDate >= $this->currentDate) {
                    $nowTime = date('Y-m-d H:i:s');
                    $signOutTime = $nowTime > $signOutNormal ? $signOutNormal : $nowTime;
                    return [$record->sign_in_time, max($record->sign_in_time, $signOutTime)];
                }
                return [$record->sign_in_time, $signOutNormal];
            }, $isNormal, $offsetDatetimes);
        } else {
            // 统计实际出勤时间
            return $this->getRealAttendTimeNoRecord($signDate, $attendTime, $offsetDatetimes, $shiftTimes, $shiftId, $isNormal);
        }
    }
}
