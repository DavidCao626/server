<?php

namespace App\EofficeApp\Sms\Requests;

use App\EofficeApp\Base\Request;

class SmsRequest extends Request {

    public $errorCode = '0x035001';

    public function rules($request) {
        $rules = [
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
