<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;

/**
 * 考勤机同步定时任务
 * @author 王炜锋
 */
class AttendanceMachineSchedule implements Schedule
{
    public function call($schedule)
    {
        // 定时同步考勤机
        try {
            if ($times = app('App\EofficeApp\Attendance\Services\AttendanceMachineService')->getAttendanceTime()) {
                foreach ($times as $time) {
                    $schedule->call(function () {
                        app('App\EofficeApp\Attendance\Services\AttendanceMachineService')->syncQueueAttendance('today');
                    })->dailyAt($time);
                }
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
