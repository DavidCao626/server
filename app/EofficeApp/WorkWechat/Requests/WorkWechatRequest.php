<?php

namespace App\EofficeApp\WorkWechat\Requests;

use App\EofficeApp\Base\Request;

class WorkWechatRequest extends Request
{

    public $errorCode = '0x034001';

    public function rules($request)
    {
        $rules = [
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
