<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use DB;
use Eoffice;

class TaskSchedule implements Schedule
{
    public function call($schedule) 
    {
        //每天定时执行一次的任务
        $schedule->call(function () {
            if ($messages = app('App\EofficeApp\Task\Services\TaskService')->getTaskRemindByDate()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            };
            //提前x天 提醒
            if ($messages = app('App\EofficeApp\Task\Services\TaskService')->getTaskRemindByPlan()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
            //重复提醒
            if ($messages = app('App\EofficeApp\Task\Services\TaskService')->getTaskRepeatRemindByPlan()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->dailyAt('09:00');
    }
}
