<?php

namespace App\EofficeApp\System\CustomFields\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
class FieldsRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
		$rules = ['field_name'	=> 'required'];
		
		if($this->input('field_type',1) == 0) {
			$rules['field_default'] =  'numeric';
		} else if($this->input('field_type',1) == 3) {
			$rules['field_default'] =  'date_format:Y-m-d H:i:s';
		}
		
        return $rules;
    }
	/**
	 * Set custom messages for validator errors.
	 *
	 * @return array
	 */
	public function messages()
	{
		$message = ['field_name.required'	=> '0x016002'];
		
		if($this->input('field_type',1) == 0) {
			$message['field_default.numeric'] =  '0x016013';
		} else if($this->input('field_type',1) == 3) {
			$message['field_default.date_format'] =  '0x016013';
		}
		
        return $message;
	}
	/**
	 * 验证失败后返回错误信息
	 * @param \Illuminate\Validation\Validator $validator
	 */
    public function failedValidation(Validator $validator)
	{		
		$this->responseFormErrors($validator, 'fields');
    }
}
