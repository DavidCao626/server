<?php
namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectRoleManagerTypeEntity extends ProjectBaseEntity {
    
    public $table = 'project_role_manager_type';

    public $primaryKey = 'id';

    protected $eqQuery = ['role_id', 'manager_type'];

    protected $fillable = [
        'role_id',
        'manager_type'
    ];
}
