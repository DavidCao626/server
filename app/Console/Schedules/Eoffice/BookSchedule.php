<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;
class BookSchedule  implements Schedule
{
    public function call($schedule)
    {
        //每天定时执行一次的任务
        $schedule->call(function () {
            //图书归还到期提醒
            if ($messages = app('App\EofficeApp\Book\Services\BookService')->bookReturnExpireRemind()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->dailyAt('09:00');

    }
}