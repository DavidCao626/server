<?php

namespace App\Console\Schedules\Eoffice;
use App\Console\Schedules\Schedule;

class SystemSchedule  implements Schedule
{
    /**
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     */
    public function call($schedule)
    {
        $schedule->call(function () {
            //生日提醒
            app('App\EofficeApp\Birthday\Services\BirthdayService')->sendBrithday();

        })->dailyAt('09:00');

    }
}