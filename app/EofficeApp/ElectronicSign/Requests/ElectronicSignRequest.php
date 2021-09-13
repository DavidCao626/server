<?php
namespace App\EofficeApp\ElectronicSign\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

class ElectronicSignRequest extends Request
{
    protected $rules = [
        'addServer'  => [
            'serverName'   => 'required',
            'serverUrl'    => 'required',
            'accessKey'    => 'required',
            'accessSecret' => 'required',
            'serverType'   => 'required',
            // 'goPage'       => 'required',
        ],
        'editServer' => [
            'serverId'     => 'required',
            'serverName'   => 'required',
            'serverUrl'    => 'required',
            'accessKey'    => 'required',
            'accessSecret' => 'required',
            'serverType'   => 'required',
            // 'goPage'       => 'required',
        ],
        // 'addIntegration'  => [
        //     'workflowId'     => 'required',
        //     'serverSource'   => 'required',
        //     'operation_info' => 'required',
        //     'sign_info'      => 'required',
        // ],
        // 'editIntegration' => [
        //     'settingId'      => 'required',
        //     'workflowId'     => 'required',
        //     'serverSource'   => 'required',
        //     'operation_info' => 'required',
        //     'sign_info'      => 'required',
        // ],
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($this->rules, $function);
    }

    public function failedValidation(Validator $validator)
    {
        $error = $code = [];
        foreach ($validator->errors()->getMessages() as $v) {
            $code[]  = '0x007002';
            $error[] = $v[0];
        }
        echo json_encode(error_response($code, '', $error));
        exit;
    }
}
