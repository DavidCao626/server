<?php

namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\User\Entities\UserEntity;

class ProjectRoleUserEntity extends ProjectBaseEntity {

    public $table = 'project_role_user';

    public $primaryKey = 'id';

    protected $eqQuery = [ 'id', 'role_id', 'manager_id', 'relation_id', 'user_id', 'manager_state', 'relation_type'];
    protected $inQuery = ['role_id', 'id', 'manager_id', 'relation_type', 'relation_id'];

    protected $fillable = [
        'role_id',
        'manager_id',
        'relation_id',
        'user_id',
        'manager_state',
    ];

    public function user() {
        return $this->hasOne(UserEntity::class, 'user_id', 'user_id');
    }
}
