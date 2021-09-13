<?php

namespace App\EofficeApp\Project\Entities;

class FunctionPageApiEntity extends ProjectBaseEntity {

    public $table = 'project_function_page_api';

    protected $eqQuery = ['function_name', 'function_page_api'];
}
