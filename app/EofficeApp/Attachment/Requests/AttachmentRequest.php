<?php
namespace App\EofficeApp\Attachment\Requests;

use App\EofficeApp\Base\Request;

class AttachmentRequest extends Request {

    public $errorCode = '0x011009';

    public function rules($request) {
        $rules = [
  
           
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
