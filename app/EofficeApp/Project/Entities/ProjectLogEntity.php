<?php
namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\User\Entities\UserEntity;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProjectLogEntity extends ProjectBaseEntity {
    
    public $table = 'project_log';
    public $timestamps = false;

    protected $eqQuery = ['manager_id', 'category_type', 'action', 'operator', 'field', 'operate_time'];
    protected $obQuery = ['operate_time'];
    protected $withQuery = ['category', 'operator_user'];

    public function category()
    {
        Relation::morphMap(self::getProjectTableMapping());
        return $this->morphTo('category');
    }

    public function operator_user()
    {
        return $this->hasOne(UserEntity::class, 'user_id', 'operator')->withTrashed();
    }
}
