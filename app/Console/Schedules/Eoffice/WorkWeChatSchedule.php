<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;


class WorkWeChatSchedule implements Schedule
{
    public function call($schedule)
    {
        //检查表是否已经被创建
        $check = app('App\EofficeApp\WorkWechat\Services\WorkWechatService')->checkTable();
        if($check){
            $SyncTimes = app('App\EofficeApp\WorkWechat\Services\WorkWechatService')->getTimingData();
            if ($SyncTimes['is_start']) {
                foreach ($SyncTimes['time'] as $SyncTime) {
                    $schedule->call(function () {
                        $param = [
                            'sync_type'=> 'day',
                            'sync_way'=> 'timing',
                        ];
                        app('App\EofficeApp\WorkWechat\Services\WorkWechatService')->timingSync($param,['user_id'=>'']);
                    })->dailyAt($SyncTime);
                }
            }
        }
    }
}
