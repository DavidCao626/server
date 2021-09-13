<?php

namespace App\EofficeApp\Report\Requests;

use App\EofficeApp\Base\Request;

class ReportRequest extends Request {

    public $errorCode = '0x021001';

    public function rules($request) {
		$tag_id = isset($request->route()[2]['tag_id']) ? $request->route()[2]['tag_id'] : '';
        $rules = [
            'addTage' => [
                'tag_title' => 'required|max:200|unique:report_tag,tag_title'
            ],
            'editVehicles' => [
                'vehicles_id'   => 'required|integer',
                'vehicles_name' => 'required|max:255',
                'vehicles_code' => 'required|max:32|unique:vehicles,vehicles_code,'.$tag_id.',vehicles_id'
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}