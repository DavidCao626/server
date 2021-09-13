<?php

namespace App\EofficeApp\Weixin\Requests;

use App\EofficeApp\Base\Request;
 

class WeixinRequest extends Request {

    public $errorCode = '0x033001';

    public function rules($request) {
        $rules = [
 
            'connectWeixinToken' => [
                "appid" => "required|max:200",
                "appsecret" => "required|max:255"
            ]
            
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
