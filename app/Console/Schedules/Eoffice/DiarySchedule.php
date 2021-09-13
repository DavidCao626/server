<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;

/**
 * 微博日志定时任务
 * @author 王炜锋
 */
class DiarySchedule implements Schedule
{
    public function call($schedule)
    {
        // 定时提醒提交
        try {
            if ($permission = app('App\EofficeApp\Diary\Services\DiaryService')->getDiaryRemind()) {
                $is_auto = isset($permission['is_auto'])?$permission['is_auto']:0;
                if ($is_auto == 1) {
                    $time = isset($permission['remind_time']) ? $permission['remind_time'] : [];
                    $param = [];
                    $work_user = isset($permission['work_user'])?$permission['work_user']:[];
                    $param['user'] = $work_user;
                    if ($time) {
                        $schedule->call(function () use($param) {
                            app('App\EofficeApp\Diary\Services\DiaryService')->pushDiaryReminds($param);
                        })->dailyAt($time);
                    }
                }
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
