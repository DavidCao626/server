<?php
namespace App\Console\Schedules\Third;

use App\Console\Schedules\Schedule;
/**
 * 定时任务类
 * 
 * 1、必须以Schedule为后缀命名类名和文件名。
 * 2、必须实现Schedule接口。
 * 3、可以参考本示例代码。
 * 
 * @author lizhijun
 */
class TestSchedule implements Schedule
{
    public function call($schedule) 
    {
        /*
        |--------------------------------------------------------------------------
        | 定时任务程序....
        |--------------------------------------------------------------------------
        | 以下为调用示例
        | $schedule->call(function () {
        |    // 每周星期一13:00运行一次...
        | })->weekly()->mondays()->at('13:00');
        |
        | // 工作日的上午8点到下午5点每小时运行...
        | $schedule->command('foo')
        |         ->weekdays()
        |         ->hourly()
        |         ->timezone('America/Chicago')
        |         ->between('8:00', '17:00');
        |
        | 参考文档地址：https://laravel.com/docs/5.6/scheduling
        */
        
//        $schedule->call(function () {
//            $time = date('Y-m-d H:i:s');
//            file_put_contents('d:/1.txt', $time . "\r\n", FILE_APPEND);
//        })->everyMinute();
//        
//        $schedule->call(function () {
//            $time = date('Y-m-d H:i:s');
//            file_put_contents('d:/2.txt', $time . "\r\n", FILE_APPEND);
//        })->everyFiveMinutes();
    }

}

