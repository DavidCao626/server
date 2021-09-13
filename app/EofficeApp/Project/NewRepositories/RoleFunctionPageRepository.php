<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectRoleEntity;
use App\EofficeApp\Project\Entities\RoleFunctionPageEntity;
use DB;
use Illuminate\Database\Eloquent\Builder;

class RoleFunctionPageRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = RoleFunctionPageEntity::buildQuery($params, $query);

        return $query;
    }

    public static function buildDefaultDataQuery($params = [], $query = null)
    {
        return self::buildQuery($params, $query)
            ->withoutGlobalScope('not_default')
            ->where('is_default', 1);
    }

    // 排除监控角色的权限数据
    public static function buildDataRoleQuery($params = [], $query = null)
    {
        $roleTable = (new ProjectRoleEntity())->table;
        $roleFunctionPageTable = (new RoleFunctionPageEntity())->table;
        return self::buildQuery($params, $query)
            ->join($roleTable, "{$roleFunctionPageTable}.role_id", '=', "{$roleTable}.role_id")
            ->where("{$roleTable}.manager_type", '>', 0);
    }
}
