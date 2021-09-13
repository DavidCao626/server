<?php

namespace App\EofficeApp\PublicGroup\Requests;

use App\EofficeApp\Base\Request;

class PublicGroupRequest extends Request {

    public $errorCode = '0x032001';

    public function rules($request) {
        $rules = [
            'addPublicGroup' => [
                "group_name" => "required|max:200",
                'user_id' => "required"
            ],
            'editPublicGroup' => [
                "group_id" => "required|integer",
                "group_name" => "required||max:200",
            ],
            'deletePublicGroup' => [
                "group_id" => "required" //多个ID用逗号分隔
            ],
            'getOnePublicGroup' => [
                "group_id" => "required|integer"
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
