<?php
namespace App\EofficeApp\IncomeExpense\Requests;

use App\EofficeApp\Base\Request;
class IncomeExpenseRequest extends Request
{
	
	
	protected $rules = [
		'addPlanType'	=> [
			'plan_type_name'	=> 'required'
		],
		'editPlanType'	=> [
			'plan_type_name'	=> 'required'
		],
		'addPlan'		=> [
			'plan_code'			=> 'required | unique:income_expense_plan,plan_code',
			'plan_name'			=> 'required'
		],
		'editPlan'		=> [
			'plan_code'			=> 'required | unique:income_expense_plan,plan_code',
			'plan_name'			=> 'required'
		],
		'addRecord'		=> [
			'plan_id'			=> 'required'
		],
		'editRecord'		=> [
			'plan_id'			=> 'required'
		],
	];
	 /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
		$function = explode("@", $request->route()[1]['uses'])[1];
		if ($function == 'editPlan') {
            $planId = isset($request->route()[2]['planId']) ? $request->route()[2]['planId'] : 0;
			$this->rules['editPlan']['plan_code'] = $this->rules['editPlan']['plan_code'] . ',' . $planId . ',plan_id';
		}	
        return $this->getRouteValidateRule($this->rules, $function);
    }
}
