<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Illuminate\Support\Facades\Redis;
use Eoffice;
use DB;
/**
 * Description of ContractSchedule
 *
 * @author Administrator
 */
class ContractSchedule implements Schedule
{
    public function call($schedule) 
    {
        //每天定时执行一次的任务
        $schedule->call(function () {
            if ($messages = app('App\EofficeApp\Contract\Services\ContractService')->getContractRemind()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->dailyAt('09:00');
    }
}
