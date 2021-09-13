<?php
namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectConfigEntity extends ProjectBaseEntity {

    use SoftDeletes;
    
    public $table = 'project_config';
    
    public $primaryKey = 'id';
 
    protected $dates = ['deleted_at'];

    protected $eqQuery = ['key'];

    protected $fillable = [
        'key',
        'value',
    ];
}
