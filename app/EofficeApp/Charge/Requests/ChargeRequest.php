<?php
namespace App\EofficeApp\Charge\Requests;

use App\EofficeApp\Base\Request;

class ChargeRequest extends Request {

    public $errorCode = '0x020004';

    public function rules($request) {
        $rules = [
            'deleteChargeType' => [
                'charge_type_id' => 'required|integer'
            ],
            'addNewCharge' => [
                'charge_type_id'       => 'required',
                'charge_undertaker'    => 'required',
                'payment_date'         => 'required',
                'reason'               => 'required',
                'user_id'              => 'required',
            ],
            'editNewCharge' => [
                'charge_type_id'       => 'required',
                'charge_undertaker'    => 'required',
                'payment_date'         => 'required',
                'reason'               => 'required',
                'user_id'              => 'required',
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
