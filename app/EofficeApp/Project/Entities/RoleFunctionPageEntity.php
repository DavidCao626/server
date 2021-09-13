<?php

namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\Builder;

class RoleFunctionPageEntity extends ProjectBaseEntity {

    public $table = 'project_role_function_page';

    public $eqQuery = ['role_id', 'function_page_id', 'manager_state', 'is_checked'];
    protected $withQuery = ['configs'];

    protected $fillable = [
        'role_id',
        'function_page_id',
        'manager_state',
        'is_checked',
        'is_fold',
        'examine_config'
    ];
    protected $hidden = ['created_at', 'updated_at', 'examine_config', 'is_fold'];
    public function configs() {
        return $this->hasMany(FunctionPageApiConfigEntity::class, 'function_page_id', 'function_page_id')->orderBy('sort');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('not_default', function (Builder $builder) {
            $builder->where('project_role_function_page.is_default', '=', 0);
        });
    }
//
//    public function setExamineConfigAttributes()
//    {
//    }
}
