<?php
namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectRoleEntity extends ProjectBaseEntity {
    
    use SoftDeletes;

    public $table = 'project_roles';

    public $primaryKey = 'role_id';
 
    protected $dates = ['deleted_at'];

    protected $eqQuery = ['type', 'role_field_key', 'manager_type', 'role_id'];
    protected $withQuery = ['user_group', 'manager_types', 'relation_penetrate'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('not_default', function (Builder $builder) {
            $builder->where('project_roles.is_default', '=', 0);
        });
    }

    public function user_group() {
        return $this->hasMany(ProjectRoleUserGroupEntity::class, 'role_id', 'role_id')->whereIn('type', ['all', 'user_ids', 'dept_ids', 'role_ids']);
    }

    public function manager_types() {
        return $this->hasMany(ProjectRoleManagerTypeEntity::class, 'role_id', 'role_id')->orderBy('id');
    }

    // 字段穿透
    public function relation_penetrate() {
        return $this->hasMany(ProjectRoleUserGroupEntity::class, 'role_id', 'role_id')->where('type', ProjectRoleUserGroupEntity::relationPenetrateKey);
    }

    protected $fillable = [
        'role_field_key',
        'type',
        'manager_type',
        'is_default',
        'is_system',
    ];
}
