<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\EofficeApp\Project\NewServices\ProjectService;
use DB;

class ProjectSchedule implements Schedule
{
    public function call($schedule) 
    {
        // 检查逾期的项目与任务
        $schedule->call(function () {
            ProjectService::updateProjectAndTaskOverdue();
        })->dailyAt('00:00');

        // 发送提醒：明日到期的项目、开始的任务、到期的任务
        $schedule->call(function () {
            MessageManager::checkProjectExpire();
            MessageManager::checkProjectTaskBeginOrExpireRemind('begin');
            MessageManager::checkProjectTaskBeginOrExpireRemind('expire');
        })->dailyAt('09:00');
    }
}
