<?php
namespace App\EofficeApp\Performance\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
use Exception;

class PerformanceRequest extends Request
{
    public function rules($request)
    {
        $rules = array(
            'modifyPlan' => array(
                'plan_start_type' => 'required|integer',
                'plan_end_type' => 'required|integer',
                'plan_start_day' => 'required|integer|min:1|max:30',
                'plan_end_day' => 'required|integer|min:1|max:30',
                'plan_is_useed' => 'required|boolean',
                'plan_is_remind' => 'required|boolean',
            ),
            'getPerformData' => array(
                'check_year' => 'required|integer',
                'circle' => 'required|string',
                'month' => 'required|integer',
            ),
            'getMyTemp' => array(
                'plan_id' => 'required|integer',
            ),
            'createTemp' => array(
                'temp_name' => 'required|string|max:255',
                'plan_id' => 'required|integer',
                'temp_score' => 'required|numeric|min:1',
                'temp_option' => 'required|string',
                'temp_option_note' => 'required|string',
                'temp_weight' => 'required|string',
                'temp_weight_note' => 'required|string',
            ),
            'modifyTemp' => 'createTemp',
            'createPerform' => array(
                'perform_user' => 'required|string',
                'plan_id' => 'required|integer',
                'temp_id' => 'required|integer',
                'temp_score' => 'required|numeric',
                'perform_points' => 'required|string',
                'perform_point' => 'required|numeric',
                'circle' => 'required|string',
                'check_year' => 'required|integer',
                'month' => 'required|integer',
            ),
        );

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
