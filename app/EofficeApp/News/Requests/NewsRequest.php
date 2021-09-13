<?php
namespace App\EofficeApp\News\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
class NewsRequest extends Request
{
	protected $rules = [
		'addNews'	=> [
            'title'		=> "required",
			'content'	=> 'required'
        ],
		'editNews'	=> [
            'title'		=> "required",
			'content'	=> 'required'
        ],
		// 'addComment' => [
		// 	'content'	=> 'required'
  //       ],
		// 'editComment' => [
		// 	'content'	=> 'required'
  //       ],
	];
	
	 /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
		$function = explode("@", $request->route()[1]['uses'])[1];
//		if ($function == 'editNewsType') {
//			$this->rules['editNewsType']['news_type_name'] = $this->rules['editNewsType']['news_type_name'] . ',' . $request->route('news_type_id') . ',news_type_id';
//		}

        return $this->getRouteValidateRule($this->rules, $function);
    }
}
