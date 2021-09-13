<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/1/28
 * Time: 14:05
 */
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use DB;

class DocumentSchedule implements Schedule
{
    public function call($schedule)
    {
        $schedule->call(function () {
            //更新置顶到期的文档置顶状态
            app('App\EofficeApp\Document\Services\DocumentService')->cancelOutTimeDocument();

            // 更新文档共享到期状态
            app('App\EofficeApp\Document\Services\DocumentService')->cancelShareDocument();
        })->everyMinute();
    }
}