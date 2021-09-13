<?php

namespace App\EofficeApp\Sso\Requests;

use App\EofficeApp\Base\Request;
 

class SsoRequest extends Request {

    public $errorCode = '0x031001';

    public function rules($request) {
        $rules = [
            'addSso' => [
                'sso_sys_name' => 'required|max:200'
            ],
            'editSso' => [
                'sso_id' => 'required|integer',
                'sso_sys_name' => 'required|max:200'
            ],
            'deleteSso' => [
                'sso_id' => 'required',
            ],
            'getOneSso' => [
                'sso_id' => 'required|integer'
            ],
//        'getSsoLoginList' =>[
//            'user_id' => 'required',// 当前登录的用户ID
//        ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
