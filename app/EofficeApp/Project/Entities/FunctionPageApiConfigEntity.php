<?php
namespace App\EofficeApp\Project\Entities;

class FunctionPageApiConfigEntity extends ProjectBaseEntity {
    
    public $table = 'project_function_page_api_config';
    
    public $primaryKey = 'id';

    protected $casts = [
        'config' => 'json',
        'filter' => 'json',
        'role_ids' => 'json'
    ];

}
