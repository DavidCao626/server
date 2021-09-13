<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;

/**
 * CAS模块定时任务
 *
 * Class CASSchedule
 * @package App\Console\Schedules\Eoffice
 */
class CASSchedule  implements Schedule
{
    public function call($schedule)
    {
        // 每天23点执行cas组织架构同步
        $schedule->call(function () {
            app('App\EofficeApp\System\Cas\Services\CasService')->syncOrganizationData();
        })->dailyAt('23:00');
    }
}