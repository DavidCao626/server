<?php
namespace App\EofficeApp\System\Barcode\Requests;

use App\EofficeApp\Base\Request;

class BarcodeRequest extends \Illuminate\Http\Request
{
    public function rules(Request $request)
    {
        $rules = [
            'generateBarcode' => [
                'type' => 'required',
                'value' => 'required'
            ]
        ];
        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
