<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Illuminate\Support\Facades\Redis;
use DB;
/**
 * Description of WebmailReceiveSchedule
 *
 * @author Administrator
 */
class WebmailReceiveSchedule implements Schedule
{
    public function call($schedule) 
    {
        // wemail 定时收取
        if (Redis::exists('webmail_receive_type')) {
            if (Redis::get('webmail_receive_type') == 1) {
                $time = Redis::get('webmail_receive_time');
                switch ($time) {
                    case '1':
                        $schedule->call(function() {
                            app('App\EofficeApp\Webmail\Services\WebmailService')->receiveAllMail();
                        })->everyFiveMinutes();
                        break;
                    case '2':
                        $schedule->call(function() {
                            app('App\EofficeApp\Webmail\Services\WebmailService')->receiveAllMail();
                        })->everyThirtyMinutes();
                        break;
                    case '3':
                        $schedule->call(function() {
                            app('App\EofficeApp\Webmail\Services\WebmailService')->receiveAllMail();
                        })->hourly();
                        break;
                    case '4':
                        $schedule->call(function() {
                            app('App\EofficeApp\Webmail\Services\WebmailService')->receiveAllMail();
                        })->dailyAt('9:00');
                        $schedule->call(function() {
                            app('App\EofficeApp\Webmail\Services\WebmailService')->receiveAllMail();
                        })->dailyAt('21:00');
                        break;
                    case '5':
                        $schedule->call(function() {
                            app('App\EofficeApp\Webmail\Services\WebmailService')->receiveAllMail();
                        })->dailyAt('9:00');
                        break;
                    default:
                        break;
                }

            }
        } else {
            $lists = DB::table('system_params')->whereIn('param_key', ['webmail_receive_type', 'webmail_receive_time'])->get();
            if (!$lists->isEmpty()) {
                foreach ($lists as $key => $value) {
                    if ($value->param_key == 'webmail_receive_type') {
                        Redis::setnx('webmail_receive_type', $value->param_value);
                    }
                    if ($value->param_key == 'webmail_receive_time') {
                        Redis::setnx('webmail_receive_time', $value->param_value);
                    }
                }
            }
        }
    }

}
