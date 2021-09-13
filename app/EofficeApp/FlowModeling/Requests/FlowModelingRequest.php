<?php

namespace App\EofficeApp\FlowModeling\Requests;

use App\EofficeApp\Base\Request;

class FlowModelingRequest extends Request
{
    public function rules($request)
    {
        $rules    = [];
        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
