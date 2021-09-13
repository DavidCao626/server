<?php
namespace App\EofficeApp\User\Requests;

use App\EofficeApp\Base\Request;

class UserRequest extends Request
{
    public function rules($request, $function = '')
    {
        $userId = isset($request->route()[2]['user_id']) ? $request->route()[2]['user_id'] : '';
        $rules  = array(
            'postUserStatusCreate' => array(
                'status_name' => 'required|max:255',
            ),
            'postUserStatusEdit'   => array(
                'status_name' => 'required|max:255',
            ),
            'userSystemCreate'     => array(
                'user_name'             => 'required|string',
                'user_accounts'         => 'required|string',
                'sex'                   => 'required',
                'user_status'           => 'required',
                'dept_id'               => 'required',
                'role_id_init'          => 'required',
                'post_priv'             => 'required',
                'list_number'           => 'numeric',
            ),
            'userSystemEdit'       => array(
                'user_id'               => 'required|string',
                'user_name'             => 'required|string',
                'user_accounts'         => 'required|string',
                'sex'                   => 'required',
                'user_status'           => 'required',
                'dept_id'               => 'required',
                'role_id_init'          => 'required',
                'post_priv'             => 'required',
                'list_number'           => 'integer|numeric',
            ),
        );

        if (empty($function)) {
            $function = explode("@", $request->route()[1]['uses'])[1];
        }
        return $this->getRouteValidateRule($rules, $function);
    }
}
