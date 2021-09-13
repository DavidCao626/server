<?php

namespace App\EofficeApp\System\Combobox\Requests;

use App\EofficeApp\Base\Request;

class ComboboxRequest extends Request
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
            'createCombobox'    => [
                'combobox_name' =>  "required|max:50|unique:system_combobox",
                'tag_id'        =>  'required|integer',
            ],
            'editCombobox'      => [
                'combobox_name' =>  "required|max:50|unique:system_combobox,combobox_name,".$id.",combobox_id",
            ],
            'createComboboxTags' => [
                'tag_name'      =>  "required|max:50|unique:system_combobox_tag",
            ],
            'editComboboxTags' => [
                'tag_name'      =>  "required|max:50|unique:system_combobox_tag,tag_name,".$id.",tag_id",
            ],
        ];
        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
