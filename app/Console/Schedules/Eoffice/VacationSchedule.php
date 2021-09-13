<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;

/**
 * 假期管理定时任务
 * @author shiqi
 */
class VacationSchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            app('App\EofficeApp\Vacation\Services\VacationService')->crontab();
        })->dailyAt('23:55');
        $schedule->call(function () {
            app('App\EofficeApp\Vacation\Services\VacationService')->sendExpireNotify();
        })->dailyAt('9:00');
    }
}
