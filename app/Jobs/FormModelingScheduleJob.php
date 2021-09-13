<?php
namespace App\Jobs;
use Eoffice;
class FormModelingScheduleJob extends Job{
    public function handle()
    {
        if ($reminds = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getReminds()) {
            if (!empty($reminds)) {
                foreach ($reminds as $remind) {
                    $remind_time = $remind['remind_date'];
                    if ($remind['type'] == "previous" || $remind['type'] == "delay") {
                        foreach ($remind_time as $key => $value) {
                            $now_time = date("Y-m-d H:i", time());
                            $now = strtotime($now_time);
                            $remind_date = strtotime($value['date']);
                            if ($remind_date == $now) {
                                $sendData['toUser'] = $value['user'];
                                $sendData['content'] = $value['content'];
                                $sendData['sendMethod'] = $remind['sendMethod'];
                                $sendData['isHand'] = true;
                                $sendData['remindMark'] = $remind['sms_menu'] . "-" . "custom";
                                $sendData['stateParams'] = $value['params'];
                                Eoffice::sendMessage($sendData);
                            }
                        }
                    }
                }
            }
        }
    }
}