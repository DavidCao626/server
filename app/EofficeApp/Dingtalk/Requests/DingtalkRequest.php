<?php

namespace App\EofficeApp\Dingtalk\Requests;

use App\EofficeApp\Base\Request;

class DingTalkRequest extends Request {

    public $errorCode = '0x120001';

    public function rules($request) {
        $rules = [
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
