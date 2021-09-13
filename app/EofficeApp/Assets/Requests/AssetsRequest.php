<?php
namespace App\EofficeApp\Assets\Requests;

use App\EofficeApp\Base\Request;

class AssetsRequest extends Request
{
    public $errorCode = '0x023002';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
        $rules = [
            'creatAssets'    => [
                'assets_name'    => 'required',
                'price'          => 'required|numeric',
                'user_time'      => 'required|numeric',
                'residual_amount'=> 'required|numeric|',
                'product_at'     => 'required',
                'managers'       => 'required',
            ],
            'deleteApply'    => [
                'id'            => 'required|numeric',
            ],
            'apply'          => [
                'assets_id'     => 'required|numeric',
                'type'          => 'required|numeric',
                'receive_at'    => 'required|date',
//                'return_at'     => 'required|date',
                'status'        => 'required|in:0',
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
