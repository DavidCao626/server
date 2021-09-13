<?php
namespace App\EofficeApp\Flow\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
use Exception;

class FlowRequest extends Request
{
    protected $rules = array(
        'getNewIndexCreateList' => array(),
        'getNewIndexCreateFavoriteList' => array(),
        'getNewIndexCreateHistoryList' => array(),
        'getNewPageFlowRunInfo' => array(),
        'getNewPageFlowFrom' => array(),
        'getNewPageFlowSave' => array()
    );

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request) {
        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($this->rules, $function);
        // // 获取请求验证的函数名
        // // ltrim(strstr($a,'@'),'@')
        // if (count(explode("@", Request::route()->getActionName())) >= 2) {
        //     $actionFunctionName = explode("@", Request::route()->getActionName()) [1];
        // }

        // // 取配置并返回
        // if (isset($this->rules[$actionFunctionName])) {
        //     if (is_array($this->rules[$actionFunctionName])) {
        //         return $this->rules[$actionFunctionName];
        //     }
        //     else {
        //         return $this->rules[$this->rules[$actionFunctionName]];
        //     }
        // }
        // else {
        //     return [];
        // }
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
