<?php

namespace App\EofficeApp\FrontComponent\Requests;

use App\EofficeApp\Base\Request;

class FrontComponentRequest extends Request {

    public $errorCode = '0x000001';

    public function rules($request) {
        $rules = [
            "getComponentSearchlist" => [
                'user_id' => 'required',
                'page_state' => 'required'
            ],
            "addComponentSearch" => [
                'name' => 'required',
                'content' => 'required',
                'user_id' => 'required',
                'page_state' => 'required',
            ],
            "deleteComponentSearch" => [
            // 'id' => 'required'
            ],
            "editComponentSearch" => [
                // 'id' => 'required',
                'name' => 'required'
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
