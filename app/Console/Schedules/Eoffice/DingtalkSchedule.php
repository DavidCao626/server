<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;

/**
 * 钉钉定时任务
 * @author 王炜锋
 */
class DingtalkSchedule implements Schedule
{
    public function call($schedule)
    {
        if ($SyncTimes = app('App\EofficeApp\Dingtalk\Services\DingtalkService')->getDingtalkTime()) {
            foreach ($SyncTimes as $SyncTime) {
                $schedule->call(function () {
                    $data['type'] = 'today';
                    app('App\EofficeApp\Dingtalk\Services\DingtalkService')->dingtalkAttendanceSync($data);
                })->dailyAt($SyncTime);
            }
        }

        // 钉钉组织架构同步
        if ($SyncTimes = app('App\EofficeApp\Dingtalk\Services\DingtalkService')->getDingtalkSyncTime()) {
            foreach ($SyncTimes as $SyncTime) {
                $schedule->call(function () {
                    // app('App\EofficeApp\Dingtalk\Services\DingtalkService')->dingtalkOASync('admin');
                    $result = app('App\EofficeApp\Dingtalk\Services\DingtalkService')->organizationSync('admin'); // 得走队列
                })->dailyAt($SyncTime);
            }
        }
    }
}
