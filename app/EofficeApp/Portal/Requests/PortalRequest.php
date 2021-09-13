<?php
namespace App\EofficeApp\Portal\Requests;

use App\EofficeApp\Base\Request;
class PortalRequest extends Request
{
	protected $rules = [
		'addPortal'		=> [
			'portal_name' => 'required'
		],
//		'editPortalName'	=> [
//			'portal_name' => 'required'
//		],
		'setPortalLayout'	=> [
			'portal_id'	=> 'required',
			'portal_layout_content' => 'required'
		],
		'sortPortal'		=> [
			'sort_data'		=> 'required'
		]
	];
	
    public function rules($request)
    {
		$function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($this->rules, $function);
    }

}
