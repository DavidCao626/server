<?php

namespace App\EofficeApp\Birthday\Requests;

use App\EofficeApp\Base\Request;


class BirthdayRequest extends Request {

    public $errorCode = '0x029001';

    public function rules($request) {
        $rules = [
            'addBirthday' => [
                "birthday_title" => "required",
                "birthday_content" => "required",
                "birthday_underwrite" => "required",
                'user_id' => "required"
            ],
            'editBirthday' => [
                "birthday_id" => "required|integer",
                "birthday_title" => "required",
                "birthday_content" => "required",
                "birthday_underwrite" => "required"
            ],
            'deleteBirthday' => [
                "birthday_id" => "required" //多个ID用逗号分隔
            ],
            'getOneBrithday' => [
                "birthday_id" => "required|integer"
            ],
            'selectBrithday' => [
                "birthday_id" => "required|integer"
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
