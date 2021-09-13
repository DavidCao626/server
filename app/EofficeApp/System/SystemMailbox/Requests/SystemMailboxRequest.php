<?php
namespace App\EofficeApp\System\SystemMailbox\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

/**
 * 系统邮箱设置请求验证
 *
 */
class SystemMailboxRequest extends Request
{
    public function rules($request)
    {
        $rules = array(
            'createSystemMailbox' => array(
                'email_address' => 'required',
                'user_name' => 'required',
                'password' => 'required',
                'pop_server' => 'required',
                'smtp_server' => 'required',
            ),
            'modifySystemMailbox' => array(
                'email_address' => 'required',
                'user_name' => 'required',
                'password' => 'required',
                'pop_server' => 'required',
                'smtp_server' => 'required',
            ),
        );

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
