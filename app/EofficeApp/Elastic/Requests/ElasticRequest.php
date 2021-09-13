<?php


namespace App\EofficeApp\Elastic\Requests;


use App\EofficeApp\Base\Request;

class ElasticRequest  extends Request
{
    protected $rules = [
        'switchSearchIndicesVersion'	=> [
            'index'	=> 'required'
        ],
//        'updateGlobalSearchConfigAction' => [
//            'update_time'   => 'required'
//        ],
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

}