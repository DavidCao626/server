<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;

/**
 * 薪酬上报定时任务
 * 薪酬上报开始提醒，薪酬的提醒
 *
 * Class SalarySchedule
 * @package App\Console\Schedules\Eoffice
 */
class SalarySchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            // 薪酬上报开始提醒，薪酬的提醒，放到这里面进行发送，方便测试。
            if ($messages = app('App\EofficeApp\Salary\Services\SalaryRemindService')->salaryReportStartRemind()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->dailyAt('09:00');
    }
}