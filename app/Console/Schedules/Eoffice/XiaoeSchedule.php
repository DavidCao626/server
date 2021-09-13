<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use App\EofficeApp\XiaoE\Services\SystemService;

/**
 * 小e定时任务
 * @author shiqi
 */
class XiaoeSchedule implements Schedule
{
    public function call($schedule)
    {
        /**
         * 同步字典，每周同步一次
         * 防止智能移动平台并发过大，分阶段同步
         */
        $schedule->call(function () {
            /** @var SystemService $service */
            $service = app('App\EofficeApp\XiaoE\Services\SystemService');
            $service->syncDictData();
        })->sundays();
    }
}
