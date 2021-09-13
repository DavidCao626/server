<?php
namespace App\Console\Schedules\Eoffice;
use App\Console\Schedules\Schedule;
use App\Jobs\FormModelingScheduleJob;
use Eoffice;
Use Queue;
class FormModelingSchedule implements Schedule{
    /**
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     */
    public function call($schedule){
        $schedule->call(function () {
           Queue::push(new FormModelingScheduleJob(), null, 'eoffice_custom_message_queue');
        })->everyMinute();

        try {
            if ($reminds = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getReminds()) {
                if (!empty($reminds)) {
                    foreach ($reminds as $remind) {
                        $remind_time = $remind['remind_date'];
                        //$sendData['toUser']     = $remind['user'];
                        //$sendData['content']    = $remind['content'];
                        $sendData['sendMethod'] = $remind['sendMethod'];
                        $sendData['isHand'] = true;
                        $sendData['remindMark'] = $remind['sms_menu'] . "-" . "custom";
                        if ($remind['type'] == "period") {
                            foreach ($remind_time as $key => $value) {
                                $sendData['stateParams'] = $value['params'];
                                $function_name = $value['date']['function_name'];
                                $param = $value['date']['param'];
                                $sendData['toUser'] = $value['user'];
                                $sendData['content'] = $value['content'];
                                if ($function_name == "dailyAt") {
                                    $schedule->call(function () use ($sendData) {
                                        Eoffice::sendMessage($sendData);
                                    })->{$function_name}($param);
                                } else if ($function_name == "weekly") {
                                    $param = explode(",", $param);
                                    $schedule->call(function () use ($sendData) {
                                        Eoffice::sendMessage($sendData);
                                    })->{$function_name}()->{$param[0]}()->at($param[1]);
                                } else if ($function_name == "monthlyOn") {
                                    $param = explode(",", $param);
                                    $schedule->call(function () use ($sendData) {
                                        Eoffice::sendMessage($sendData);
                                    })->{$function_name}($param[0], $param[1]);
                                } else if ($function_name == "cron") {
                                    $param = explode(",", $param);
                                    $params = $param[0] . " " . $param[1] . " " . $param[2] . " " . $param[3] . " " . $param[4];
                                    $schedule->call(function () use ($sendData) {
                                        Eoffice::sendMessage($sendData);
                                    })->{$function_name}($params);
                                }

                            }
                        }

                    }

                }

            }
        } catch (\Exception $e) {
            return false;
        }
    }

    private function dispatch()
    {
        dispatch(new FormModelingScheduleJob());
    }
}