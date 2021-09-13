<?php
namespace App\EofficeApp\System\Department\Requests;

use App\EofficeApp\Base\Request;

class DepartmentRequest extends Request
{
    protected $rules = [
        'addDepartment'	=> [
			'dept_name' => "required",
		],
		'editDepartment'=> [
			'dept_name' => "required",
		]
    ];
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
        $function = explode("@", $request->route()[1]['uses'])[1];
		
        return $this->getRouteValidateRule($this->rules, $function);
    }
}
