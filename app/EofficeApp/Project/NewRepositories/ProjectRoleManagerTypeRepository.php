<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectRoleManagerTypeEntity;

class ProjectRoleManagerTypeRepository extends BaseRepository {

    public static function buildQuery($params = [], $query = null)
    {
        return ProjectRoleManagerTypeEntity::buildQuery($params, $query);
    }

}
