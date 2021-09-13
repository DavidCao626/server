<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;
class NewsSchedule  implements Schedule
{
    /**
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     */
    public function call($schedule)
    {
        //更新置顶到期的新闻置顶状态
        $schedule->call(function () {
            //更新置顶到期的新闻置顶状态
            app('App\EofficeApp\News\Services\NewsService')->cancelOutTimeTop();

        })->everyMinute();


    }
}