<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Illuminate\Support\Facades\Redis;
use DB;
/**
 * Description of StorageSchedule
 *
 * @author Administrator
 */
class StorageSchedule implements Schedule
{
    public function call($schedule) 
    {
        //每天定时执行一次的任务
        $schedule->call(function () {
            // 仓库库存产品预警提醒。
            app('App\EofficeApp\Storage\Services\StorageService')->storageRemind();
        })->dailyAt('09:00');
    }
}
