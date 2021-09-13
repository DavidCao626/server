<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;
class OfficeSuppliesSchedule  implements Schedule
{
    public function call($schedule)
    {
        //每天定时执行一次的任务
        $schedule->call(function () {
            //办公用品归还到期提醒
            if ($messages = app('App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService')->officeSuppliesReturnExpireRemind()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->dailyAt('09:00');

    }
}