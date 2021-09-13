<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;

/**
 * 绩效考核模块定时任务
 *
 * Class PerformanceSchedule
 * @package App\Console\Schedules\Eoffice
 */
class PerformanceSchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            //绩效考核到期提醒
            if ($messages = app('App\EofficeApp\Performance\Services\PerformanceService')->performanceExpireRemind()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }

            // 绩效考核外发日程，提前2天
            app('App\EofficeApp\Performance\Services\PerformanceService')->sendCalenderSchedule(2);

        })->dailyAt('09:00');
    }
}