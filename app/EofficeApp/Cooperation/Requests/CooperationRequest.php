<?php
namespace App\EofficeApp\Cooperation\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
use Exception;

class CooperationRequest extends Request
{
    protected $rules = array(
        'createCooperationSort' => array(
            'cooperation_sort_name' => 'required|max:255',
            'cooperation_sort_creater' => 'required'
        ) ,
        'editCooperationSort' => array(
            'cooperation_sort_name' => 'required|max:255'
        ) ,
        'createCooperationSubject' => array(
            'subject_title' => 'required',
            'cooperation_sort_id' => 'required|integer',
            'subject_creater' => 'required'
        ) ,
        'editCooperationSubject' => array(
            'subject_title' => 'required',
            'cooperation_sort_id' => 'required|integer',
            // 'subject_id' => 'required|integer'
        ) ,
        'updateCooperationSubjectViewTime' => array(
            'user_id' => 'required'
        ) ,
        'editCooperationRevertFirst' => array(
            'subject_id' => 'required|integer',
            // 'revert_content' => 'required'
        ) ,
        'deleteCooperationRevertFirst' => array(
            'subject_id' => 'required|integer',
        ) ,
        'getCooperationRevertAllService' => array(
            'subject_id' => 'required|integer'
        ),
        'getCooperationAboutDocument' => array(
            'subject_id' => 'required|integer'
        ),
        'getCooperationAboutAttachment' => 'getCooperationAboutDocument',
        'getCooperationRevertAboutDocument' => array(
            'revert_id' => 'required|integer'
        ),
        'getCooperationRevertAboutAttachment' => 'getCooperationRevertAboutDocument'
    );

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request) {
        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($this->rules, $function);
    }

    public function failedValidation(Validator $validator) {
        $error = $code = [];
        foreach ($validator->errors()->getMessages() as $v) {
            $code[] = '0x007002';
            $error[] = $v[0];
        }
        echo json_encode(error_response($code, '', $error));
        exit;
    }
}
