<?php
namespace App\EofficeApp\Email\Requests;

use App\EofficeApp\Base\Request;

class EmailRequest extends Request {

    public $errorCode = '0x012001';

    public function rules($request) {
        $rules = [
            'getEmailBoxList' => array(
//                'user_id' => 'required'
            ),
            'addEmailBox' => array(
                'box_name' => 'required|max:200',
//                'user_id' => 'required'
            ),
            'editEmailBox' => array(
                'box_id' => 'required|integer',
                'box_name' => "required|max:200",
//                'user_id' => "required"
            ),
            'deleteEmailBox' => array(
                'user_id' => 'required',
//                'box_id' => 'required'
            ),
            'getEmail' => array(
//                'user_id' => 'required',
                'box_id' => 'required'
            ),
            'newEmail' => array(
                'subject' => 'required|max:200',
//                'user_id' => 'required',
                'to_id' => 'required'
            ),
            'useEmailSign' => array(
                'user_id' => 'required'
            ),
            'deleteEmail' => array(
                'id' => 'required',
//                'user_id' => "required",
                "type" => "required|in:send,receive", //发送人 接收人
            ),
            'editEmail' => array(
                'subject' => 'required|max:200',
                'email_id' => 'required|integer',
                'to_id' => 'required',
//                'user_id' => "required"
            ),
            'relayEmail' => array(
                'subject' => 'required|max:200',
                'email_id' => 'required|integer',
                'to_id' => 'required',
//                'user_id' => "required"
            ),
            'replyEmail' => array(
                'subject' => 'required|max:200',
                'email_id' => 'required|integer',
                'to_id' => 'required',
//                'user_id' => "required"
            ),
            'transferEmail' => array(
                'email_id' => 'required', // 1-N 逗号分隔
//                'user_id' => "required",
                'box_id' => 'required'
            )
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
