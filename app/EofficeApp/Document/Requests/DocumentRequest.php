<?php
namespace App\EofficeApp\Document\Requests;

use App\EofficeApp\Base\Request;

class DocumentRequest extends Request
{
	protected $rules = [
		'addMode'	=> [
			'mode_title'	=> 'required'
		],
		'editMode'	=> [
			'mode_title'	=> 'required'
		],
		'addFolder'	=> [
			'folder_name'	=> 'required'
		],
		'batchAddFolder' => [
			'folder_names'	=> 'required'
		],
		'editFolder'	=> [
			'folder_name'	=> 'required'
		],
		'copyFolder'	=> [
			'folder_name'	=> 'required'
		],
		'setFolderBaseInfo'	=> [
			'folder_name'	=> 'required'
		],
		'editFolderName' => [
			'folder_name'	=> 'required'
		],
		'addDocument'	=> [
			'folder_id'		=> 'required',
			'subject'		=> 'required'
		],
		'editDocument'	=> [
			'folder_id'		=> 'required',
			'subject'		=> 'required'
		],
        'editDocumentName'	=> [
            'folder_id'		=> 'required',
            'subject'		=> 'required'
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
