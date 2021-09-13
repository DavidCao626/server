<?php

namespace App\EofficeApp\System\Prompt\Requests;

use App\EofficeApp\Base\Request;

class PromptRequest extends Request
{
    public $errorCode = '0x013001';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request) {
        $id = isset($request->route()[2]['id']) ? $request->route()[2]['id'] : 0;

        $rules = [
            'createPromptType'    => [
                'prompt_type_name' =>  "required|unique:prompt_type"
            ],
            'editPromptType'      => [
                'prompt_type_name' =>  "max:50|unique:prompt_type,prompt_type_name,".$id.",prompt_type_id",
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
