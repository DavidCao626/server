<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectFunctionPageEntity;
use DB;
use Illuminate\Database\Eloquent\Builder;

class ProjectFunctionPageRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectFunctionPageEntity::buildQuery($params, $query);

        return $query;
    }

}
