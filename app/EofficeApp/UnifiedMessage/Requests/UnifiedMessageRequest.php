<?php

namespace App\EofficeApp\UnifiedMessage\Requests;

use App\EofficeApp\Base\Request;


class UnifiedMessageRequest extends Request {

    public function rules($request) {
        $rules = [
            'registerHeterogeneousSystem' => [
                "system_code" => "required|max:200",
            ],
            'editHeterogeneousSystem' => [
                "for_short" => "required|max:200",
                "full_title" => "required|max:255"
            ],
            //接收外部消息参数验证
            'acceptMessageData' => [
                "message_type" => "required",
                "message_id" => "required|max:255",
                "message_title" => "required|max:255",
                //"dispose_state" => "required|max:255", 该接口接受到额消息均视为未处理
                // "read_state" => "required|max:255", 该接口接受到额消息均视为未读
                "sender" => "required|max:255",
                "message_create_time" => "required",
                "recipient" => "required",
                "pc_address" => "required",
                "app_address" => "required",
            ],
            //删除外部消息
            'deleteMessage' => [
                "message_type" => "required",
                "message_id" => "required",
            ],
            'deleteDesignatedPersonMessage'   => array(
                'recipients' => 'required',
            ),
            'editMessageState'   => array(
                'message_id' => 'required',
                "message_type" => "required",
            ),
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
