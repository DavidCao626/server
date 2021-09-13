<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectDiscussEntity;
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
class ProjectDiscussRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectDiscussEntity::buildQuery($params, $query);

        return $query;
    }

    public static function buildProjectDiscussQuery($managerId, $query = null)
    {
        $params = ['discuss_project' => $managerId];
        return self::buildQuery($params, $query);
    }

    public static function buildProjectDiscussListQuery($managerId, $params = [], $query = null)
    {
        $query = self::buildProjectDiscussQuery($managerId, $query);
        $params['discuss_replyid'] = 0;
        self::buildQuery($params, $query);
        $query->with('user:user_id,user_name');
        $query->with('reply.user:user_id,user_name');
        $query->with('quote.user:user_id,user_name');
        return $query;
    }
}
