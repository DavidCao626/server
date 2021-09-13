<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;

/**
 * 契约锁相关资源定时更新任务
 * @author yml
 */
class QiyuesuoRelatedResourceSyncSchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            $config = app('App\EofficeApp\ElectronicSign\Services\ElectronicSignService')->getServerBaseInfo();
            if ($config && isset($config['qys_on_off']) && $config['qys_on_off'] == 1) {
                app('App\EofficeApp\ElectronicSign\Services\QiyuesuoRelatedResourceService')->syncTask(['actions' => 'all'], []);
            }
        })->dailyAt('00:00');
    }
}
