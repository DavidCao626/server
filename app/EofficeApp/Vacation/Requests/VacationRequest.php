<?php

namespace App\EofficeApp\Vacation\Requests;

use App\EofficeApp\Base\Request;

class VacationRequest extends Request {
    public function rules($request) {
        $vacationId = isset($request->route()[2]['vacationId']) ? $request->route()[2]['vacationId'] : '';

        $rules = array(
            'createVacation' => array(
                'vacation_name' => 'required|string|max:200|unique:vacation,vacation_name,NULL,vacation_id,deleted_at,NULL',
            ),
            'modifyVacation' => array(
                'vacation_name' => 'required|string|max:200',
            ),
            'multiSetUserVacation' => array(
                'vacationId' => 'required'
            )
        );

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
