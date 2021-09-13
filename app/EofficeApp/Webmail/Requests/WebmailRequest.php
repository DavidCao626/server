<?php

namespace App\EofficeApp\Webmail\Requests;

use App\EofficeApp\Base\Request;

class WebmailRequest extends Request
{

    public $errorCode = '0x038003';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
//        $id = isset($request->route()[2]['id']) ? $request->route()[2]['id'] : 0;
        $folderId = isset($request->route()[2]['folderId']) ? $request->route()[2]['folderId'] : 0;
        $outboxId = isset($request->route()[2]['outboxId']) ? $request->route()[2]['outboxId'] : 0;
        $rules = [
            'createFolder'       => [
                'folder_name'       => "required|max:200|unique:webmail_folder",
            ],
            'updateFolder'       => [
                'folder_name'       => "max:200|unique:webmail_folder,folder_name,".$folderId.",folder_id",
            ],
            'createOutbox'       => [
                'account'           => "required|max:200|unique:webmail_outbox",
            ],
            'updateOutbox'       => [
                'account'           => "max:200|unique:webmail_outbox,account,".$outboxId.",outbox_id",
            ],
            'addWebmailFolder'       => [
                'folder_name'       => "required|max:200|unique:webmail_folder",
            ],
            'editWebmailFolder'       => [
                'folder_name'       => "max:200|unique:webmail_folder,folder_name,".$folderId.",folder_id",
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
