<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectTeamEntity;
use DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * 项目管理 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectTeamRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectTeamEntity::buildQuery($params, $query);

        return $query;
    }

}
