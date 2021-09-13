<?php

namespace App\EofficeApp\Diary\Requests;

use App\EofficeApp\Base\Request;

class DiaryRequest extends Request
{
    public $errorCode = '0x008002';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
        $rules = [
            'createDiaryAttention'  => [
                'attention_to_person'   => 'required',
            ],
            'editDiaryAttention'    => [
                'attention_status'      => 'required|in:1,2,3',
            ],
            'createDiaryVisits'     => [
                'visit_to_person'       => 'required'
            ],
            'editDiarys'            => [
                'diary_content'         => 'required',
            ],
            // 'createDiaryReplys'     => [
            //     'diary_reply_content'   => 'required',
            // ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}