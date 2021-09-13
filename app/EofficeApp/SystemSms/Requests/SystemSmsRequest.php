<?php

namespace App\EofficeApp\SystemSms\Requests;

use App\EofficeApp\Base\Request;


class SystemSmsRequest extends Request {

    public $errorCode = '0x035001';

    public function rules($request) {
        $rules = [
                //        'addSms' => [
                //            "user_id" => "required",
                //            "recipients" => "required",
                //            "content" => "required",
                //        ],
                //      
 
                // 
                //        'getOneSms' => [
                //            "sms_id" => "required|integer"
                //        ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

     

}
