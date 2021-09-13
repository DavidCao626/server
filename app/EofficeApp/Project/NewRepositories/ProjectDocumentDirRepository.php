<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectDocumentDirEntity;
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
class ProjectDocumentDirRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectDocumentDirEntity::buildQuery($params, $query);

        return $query;
    }

    // 项目的文件夹列表
    public static function buildProjectDirQuery($managerId, $params = [], $query = null) {
        $query = self::buildQuery($params, $query);
        $query->where(function ($query) use ($managerId) {
            $query->where('dir_project', $managerId)->orWhere('dir_id', 1);
        })->orderBy('sort', 'asc');
        return $query;
    }

    public static function getSubordinateDirIds($dirId, $managerId, $includeSelf = true)
    {
        $managerDirIds = self::buildProjectDirQuery($managerId)->select('parent_id', 'dir_id')->get();
        $allDirIds = self::getSubordinateDirIdsData($dirId, $managerDirIds);
        $includeSelf && array_push($allDirIds, $dirId);
        return $allDirIds;
    }

    public static function withSonDir($query, $managerId, $isCount = false) {
        $functionName = $isCount ? 'withCount' : 'with';
        $query->$functionName(['sonDir' => function($query) use ($managerId) {
                $query->where('dir_project', $managerId);
        }]);
    }
    
    private static function getSubordinateDirIdsData($parentIds, $data, &$allSubordinateIds = [])
    {
        $parentIds = is_array($parentIds) ? $parentIds : [$parentIds];
        $subordinateIds = $data->whereIn('parent_id', $parentIds)->pluck('dir_id')->toArray();
        if ($subordinateIds) {
            $allSubordinateIds = array_merge($allSubordinateIds, $subordinateIds);
            self::getSubordinateDirIdsData($subordinateIds, $data, $allSubordinateIds);
        }
        return $allSubordinateIds;
    }
}
