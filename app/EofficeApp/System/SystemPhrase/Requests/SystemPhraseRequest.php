<?php
namespace App\EofficeApp\System\SystemPhrase\Requests;

use App\EofficeApp\Base\Request;
class SystemPhraseRequest extends Request
{
	protected $rules = [
		'addSystemPhrase'		=> [
			'content'			=> "required|"
		],
		'editSystemPhrase'		=> [
			'content'			=> 'required|'
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
