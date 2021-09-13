<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectDocumentEntity;
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
class ProjectDocumentRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectDocumentEntity::buildQuery($params, $query);

        return $query;
    }

    // 项目的文档
    public static function buildProjectDocument($managerIds, $query = null) {
        return self::buildQuery([
            'doc_project' => $managerIds
        ], $query);
    }

}
