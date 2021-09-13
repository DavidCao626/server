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
class CalendarSchedule implements Schedule
{
    public function call($schedule) 
    {
        //每分钟定时执行一次的任务
        $schedule->call(function () {
        	$interval = 1;
           //日程开始消息提醒
            if ($messages = app('App\EofficeApp\Calendar\Services\CalendarRecordService')->calendarBeginRemind($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
            //日程结束消息提醒
            if ($messages = app('App\EofficeApp\Calendar\Services\CalendarRecordService')->calendarEndRemind($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
            // 日程动态加载
            app('App\EofficeApp\Calendar\Services\CalendarService')->insertCalendarRepeatNever();
        })->everyMinute();
    }
}
