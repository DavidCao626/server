<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;
class NotifySchedule  implements Schedule
{
    /**
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     */
    public function call($schedule)
    {
        //公告有效开始日期提醒
        $schedule->call(function () {
            if ($messages = app('App\EofficeApp\Notify\Services\NotifyService')->notifyBeginRemind()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->dailyAt('09:00');

        $schedule->call(function () {
            //更新置顶到期的公告置顶状态
            app('App\EofficeApp\Notify\Services\NotifyService')->cancelOutTimeTop();

        })->everyMinute();

    }
}