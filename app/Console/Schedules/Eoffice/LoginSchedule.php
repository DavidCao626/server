<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Illuminate\Support\Facades\Redis;
use DB;
class LoginSchedule  implements Schedule
{
    /**
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     */
    public function call($schedule)
    {

        //每小时执行一次的任务,插入system_login_log表中的数据
        $schedule->call(function () {
            $data = [];
            $datas = Redis::hGetAll('system_login_log');
            if (isset($datas) && !empty($datas)) {
                foreach ($datas as $k => $v) {
                    $data = unserialize($v);
                    $result = DB::table('system_login_log')->insert($data);
                }
            }
            Redis::del('system_login_log');
        })->hourly();

    }
}