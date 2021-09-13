<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Illuminate\Support\Facades\Redis;
use Eoffice;
use DB;
/**
 * Description of CustomerSchedule
 *
 * @author Administrator
 */
class CustomerSchedule implements Schedule
{
    public function call($schedule) 
    {
        //每天定时执行一次的任务
        $schedule->call(function () {
            //客户联系人生日提醒
            if ($messages = app('App\EofficeApp\Customer\Services\LinkmanService')->customerBirthdayReminds()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }

            //合同收款提醒
            if ($messages = app('App\EofficeApp\Customer\Services\ContractService')->contractRemimds()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }

            //合同到期提醒
            if ($messages = app('App\EofficeApp\Customer\Services\ContractService')->expireContractReminds()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }

            // 客户回收
            app('App\EofficeApp\Customer\Services\CustomerService')->recycleCustomers();

            // 客户回收提醒 (提前N天) / 公海分配提醒(已分配出去的客户，没有联系一直提醒)
            app('App\EofficeApp\Customer\Services\CustomerService')->recycleCustomersRemind();

        })->dailyAt('09:00');

        //每分钟执行一次的任务。windows任务计划执行频率最快为5分钟一次，所有该任务实际执行频率为5分钟一次
        $schedule->call(function () {
            $interval = 1; //minutes
            //客户拜访提醒
            if ($messages = app('App\EofficeApp\Customer\Services\CustomerService')->willVisitReminds($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->everyMinute();
    }
}
