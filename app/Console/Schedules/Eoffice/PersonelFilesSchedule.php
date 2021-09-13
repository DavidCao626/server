<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;
class PersonelFilesSchedule  implements Schedule
{
    /**
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     */
    public function call($schedule)
    {
        //劳务合同到期提醒
        $schedule->call(function () {
            if ($messages = app('App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService')->laborRemind()) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }

        })->dailyAt('09:00');


    }
}