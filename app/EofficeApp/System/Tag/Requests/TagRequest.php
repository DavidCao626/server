<?php

namespace App\EofficeApp\System\Tag\Requests;

use App\EofficeApp\Base\Request;

class TagRequest extends Request
{
    public $errorCode = '0x013001';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request) {
        $tagId = isset($request->route()[2]['id']) ? $request->route()[2]['id'] : 0;
        $rules = array(
            'createTag' => array(
                // 'tag_name' => 'required|unique:tag,tag_name',
                'tag_name' => 'required',
                'tag_type' => 'required',
                'tag_creator' => 'required',
            ),
            'editTag' => array(
                // 'tag_id' => 'required|unique:tag,tag_id,'.$tagId,
                // 'tag_name' => 'required|unique:tag,tag_name,'.$tagId.',tag_id',
                'tag_name' => 'required',
                'tag_type' => 'required',
            ),
        );
        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
