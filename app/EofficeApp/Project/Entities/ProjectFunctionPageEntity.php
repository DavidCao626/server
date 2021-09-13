<?php
namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectFunctionPageEntity extends ProjectBaseEntity {
    
    use SoftDeletes;

    public $table = 'project_function_pages';

    public $primaryKey = 'function_page_id';
    protected $keyType = 'string';
 
    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $appends = ['name'];

    // 翻译名称
    public function getNameAttribute()
    {
        return trans_dynamic("project_function_pages.function_page_id." . $this->getKey());
    }
}
