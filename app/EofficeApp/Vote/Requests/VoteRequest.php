<?php
namespace App\EofficeApp\Vote\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

class VoteRequest extends Request
{
    public function rules($request, $function = '')
    {
        $userId = isset($request->route()[2]['user_id']) ? $request->route()[2]['user_id'] : '';
        $rules = array(
            'editVoteManage' => array(
                'vote_name' => 'required',
                'start_time' => 'required',
                'end_time' => 'required'
            ) ,
            'addVoteManage' => array(
                'vote_name' => 'required',
                'start_time' => 'required',
                'end_time' => 'required'
            ) ,
        );

        if(empty($function)) {
            $function = explode("@", $request->route()[1]['uses'])[1];
        }
        return $this->getRouteValidateRule($rules, $function);
    }
}
