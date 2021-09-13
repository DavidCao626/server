<?php

namespace App\EofficeApp\Menu\Requests;

use App\EofficeApp\Base\Request;

class MenuRequest extends Request {

    public $errorCode = '0x009001';

    public function rules($request) {
        $rules = [
            "getMenuList" => [
                "user_id" => 'required'
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
