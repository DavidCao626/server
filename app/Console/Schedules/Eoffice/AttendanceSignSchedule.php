<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;

class AttendanceSignSchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            $attendanceService = app('App\EofficeApp\Attendance\Services\AttendanceService');
            // 打卡提醒任务
            $attendanceService->setSignRemindJob();
            // 自动签退任务
            $attendanceService->setAutoSignOutJob();
        })->dailyAt('00:00');
    }
}
