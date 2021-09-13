<?php

namespace App\EofficeApp\IpRules\Requests;

use App\EofficeApp\Base\Request;

class IpRulesRequest extends Request {

    public $errorCode = '0x028001';

    public function rules($request) {
        $rules = [
            'addIpRules' => [
                "ip_rules_begin_ip" => "required|ip",
                "ip_rules_end_ip" => "required|ip",
            ],
            'editIpRules' => [
                "ip_rules_id" => "required|integer",
                "ip_rules_begin_ip" => "required|ip",
                "ip_rules_end_ip" => "required|ip",
            ],
            'deleteIpRules' => [
                "ip_rules_id" => "required"
            ],
            'getOneIpRules' => [
                "ip_rules_id" => "required"
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }



}
