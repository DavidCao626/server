<?php
namespace App\EofficeApp\PersonalSet\Requests;

use App\EofficeApp\Base\Request;
class PersonalSetRequest extends Request
{
	protected $rules = [
		'addUserGroup'		=> [
			'group_name' => 'required'
		],
		'resetUserGroupName'	=> [
			'group_name' => 'required'
		],
		'addShortcutsRun'		=> [
			'win_number'		=> 'required|integer',
			'win_name'			=> 'required',
			'win_path'			=> 'required'
		],
		'editShortcutsRun'		=> [
			'win_number'		=> 'required|integer',
			'win_name'			=> 'required',
			'win_path'			=> 'required'
		],
		'modifyPassword'		=> [
			'password'			=> 'required'
			// 'password_repeat'	=> 'required|min:6|max:20'
		],
		'addCommonPhrase'		=> [
			'content'			=> 'required'
		],
		'editCommonPhrase'		=> [
			// 'content'			=> 'required'
		],
		'sortFixedCommonPhrase'	=> [
			'sort_data'			=> 'required'
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
