<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Illuminate\Support\Facades\Redis;
use DB;
use Eoffice;
/**
 * Description of StorageSchedule
 *
 * @author Administrator
 */
class MeetingSchedule implements Schedule
{
    public function call($schedule) 
    {
        //每分钟定时执行一次的任务
        $schedule->call(function () {
        	$interval = 1;
           // 会议开始消息提醒
            if ($messages = app('App\EofficeApp\Meeting\Services\MeetingService')->meetingBeginRemind($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
            // 会议结束同步更新日程数据
            app('App\EofficeApp\Meeting\Services\MeetingService')->emitEndMeetingToUpdateCalendar($interval);
        })->everyMinute();
    }
}
