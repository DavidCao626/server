<?php
namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\EofficeApp\Project\NewServices\ProjectService;
use DB;
use Eoffice;

class VoteSchedule implements Schedule
{
    public function call($schedule) 
    {
        $schedule->call(function () {
            $interval = 1;
            //调查开始消息提醒
            if ($messages = app('App\EofficeApp\Vote\Services\VoteService')->voteBeginRemind($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
            //更改投票调查的状态
            app('App\EofficeApp\Vote\Services\VoteService')->closeOutTimeVotes();
        })->everyMinute();
    }
}
