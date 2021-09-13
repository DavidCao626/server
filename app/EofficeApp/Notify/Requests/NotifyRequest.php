<?php
namespace App\EofficeApp\Notify\Requests;

use App\EofficeApp\Base\Request;
class NotifyRequest extends Request
{
	protected $rules = [
		'addNotifyType'		=> [
			'notify_type_name' => 'required'
		],
		'editNotifyType'	=> [
			'notify_type_name' => 'required'
		],
		'addNotify'			=> [
			'subject'		=> 'required',
			'content'		=> 'required',
			'begin_date'	=> 'required',
		],
		'editNotify'		=> [
			'subject'		=> 'required',
			'content'		=> 'required',
			'begin_date'	=> 'required',
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
        return $this->getRouteValidateRule($this->rules, $function);
    }
}
