<?php


namespace App\Console\Schedules\Eoffice;


use App\Console\Schedules\Schedule;
use Eoffice;

/**
 * 协作相关定时任务
 *
 * Class CooperationSchedule
 * @package App\Console\Schedules\Eoffice
 */
class CooperationSchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            $interval = 1;
            // 协作开始消息提醒
            if ($messages = app('App\EofficeApp\Cooperation\Services\CooperationService')->cooperationBeginRemind($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->everyMinute();
    }
}