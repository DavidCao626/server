<?php
namespace App\EofficeApp\Auth\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
class AuthRequest extends Request
{
	protected $rules = [
		'login'		=> [
            'user_account' => "required"
        ],
        'setQRCodeLoginInfo' => [
            'nonce_str' => "required",
            'login_key' => "required"
        ],
        'qrcodeSignOn' => [
            'nonce_str' => "required",
            'login_key' => "required"
        ],
        'modifyPassword' => [
            'password' => 'required|min:6|max:20'
        ],
	];

	protected $messages = [
		'login'		=> [
            'user_account.required' => "0x003002",
        ],
        'setQRCodeLoginInfo' => [
            'nonce_str.required' => "0x003018",
            'login_key.required' => "0x003017"
        ],
        'qrcodeSignOn' => [
            'nonce_str.required' => "0x003018",
            'login_key.required' => "0x003017"
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
		// $this->setCurrentActionName();
        // return $this->rules[$this->actionName];
    }
	/**
	 * Set custom messages for validator errors.
	 *
	 * @return array
	 */
	public function messages()
	{
		return $this->messages[$this->actionName];
	}
	/**
	 * 验证失败后返回错误信息
	 * @param \Illuminate\Validation\Validator $validator
	 */
    public function failedValidation(Validator $validator){
        $this->responseFormErrors($validator, 'auth');
    }
}
