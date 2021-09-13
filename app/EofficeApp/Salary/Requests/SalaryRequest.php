<?php
namespace App\EofficeApp\Salary\Requests;

use App\EofficeApp\Base\Request;

class SalaryRequest extends Request
{

    public $errorCode = '0x038003';

    /**
     * Get the validation rules that apply to the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function rules($request)
    {
        $rules = [
                'createSalaryReports' => [
                    'start_date' => "required|date",
                    'end_date'   => "required|date",
                    'report_year_and_month'   => "required|date",
                ],
                'createUserSalary'    => [
                    'report_id'  => "required",
                    'user_id'    => "required"
                ],
            ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
