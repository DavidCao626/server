<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Schema;

/**
 * 发票管理-发票云定时同步人员
 * @author yml
 */
class InvoiceManageSyncUsersSyncSchedule implements Schedule
{
    public function call($schedule)
    {
        if (Schema::hasTable('invoice_manage_params') && $syncTimes = app('App\EofficeApp\Invoice\Services\InvoiceManageService')->getUserSyncTime()) {
            $schedule->call(function () {
                app('App\EofficeApp\Invoice\Services\InvoiceService')->batchSyncUserSchedule(['type' => 'sync'], ['user_id' => 'admin']); // 得走队列
            })->dailyAt($syncTimes);
        }
    }
}
