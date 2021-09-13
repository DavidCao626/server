<?php


namespace App\EofficeApp\PersonnelFiles\Entities;


use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\PersonnelFiles\Enums\Permission\Ranges;
use App\EofficeApp\Role\Entities\RoleEntity;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
use App\EofficeApp\User\Entities\UserEntity;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelFilesPermissionEntity extends BaseEntity
{
    use SoftDeletes;

    protected $table = 'personnel_files_permissions';

    protected $primaryKey = 'id';

    public function manager()
    {
        Relation::morphMap([
            'user' => UserEntity::class,
            'dept' => DepartmentEntity::class,
            'role' => RoleEntity::class,
        ]);

        return $this->morphTo();
    }

    public function getDeptIdAttribute($value)
    {
        return array_filter(explode(',', $value), function ($id) {
            return ! is_null($id) && $id != '';
        });
    }

    public function setDeptIdAttribute($value)
    {
        $this->attributes['dept_id'] =  implode(',', $value);
    }

    public function getRangeAttribute($value)
    {
        $range = [
            'query' => 0,
            'manage' => 0
        ];
        $range['query'] = $value & Ranges::QUERY;
        $range['manage'] = $value & Ranges::MANAGE;

        return $range;
    }

    public function setRangeAttribute($value)
    {
        $query = $value['query'] ?? 0;
        $manage = $value['manage'] ?? 0;

        $this->attributes['range'] = $query + $manage;
    }



}
