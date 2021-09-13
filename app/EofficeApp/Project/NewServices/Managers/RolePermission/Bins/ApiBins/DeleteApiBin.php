<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins;

use Illuminate\Database\Eloquent\Model;

class DeleteApiBin extends BaseApiBin
{
    public function __construct($apiConfig)
    {
        parent::__construct($apiConfig);
        $this->type = 'add';
    }

}