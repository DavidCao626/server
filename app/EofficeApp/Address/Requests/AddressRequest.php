<?php

namespace App\EofficeApp\Address\Requests;

use App\EofficeApp\Base\Request;
class AddressRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
        $function = explode("@", $request->route()[1]['uses'])[1];
        if($function == 'addAddress' || $function == 'editAddress') {
            $rules = [
                'group_type'	=> 'required|in:1,2'	
            ];
            
            return $rules;
        }
        
        return [];
    }
}
