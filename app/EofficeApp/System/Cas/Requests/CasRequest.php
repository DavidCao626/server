<?php
namespace App\EofficeApp\System\Cas\Requests;

use App\EofficeApp\Base\Request;

class CasRequest extends Request
{
    public function rules($request)
    {
        $rules = [
            'saveCasParams' => [
                'cas_url'          => "required",
                'sync_basis_field' => "required",
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
