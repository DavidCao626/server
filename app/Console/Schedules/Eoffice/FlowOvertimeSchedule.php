<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use App\Jobs\FlowOvertimeJob;
use Queue;

/**
 * 流程超时处理
 * @author wangzheng
 */
class FlowOvertimeSchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            Queue::push(new FlowOvertimeJob());
        })->everyMinute();
    }
}