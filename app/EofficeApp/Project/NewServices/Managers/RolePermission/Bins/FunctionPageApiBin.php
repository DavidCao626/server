<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins;

class FunctionPageApiBin
{
    private $functionName;
    private $functionPageId;
    private $filterFieldConfig;
    private $dataFunctionPermission;

    public function __construct($functionPageApiConfig)
    {
        $this->functionName = $functionPageApiConfig['function_name'];
        $this->functionPageId = $functionPageApiConfig['function_page_id'];
        $this->filterFieldConfig = $functionPageApiConfig['filter_field_config'];
        $this->dataFunctionPermission = $functionPageApiConfig['data_function_permission'];
    }

}