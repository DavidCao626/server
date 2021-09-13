<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectTaskDiaryEntity;
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
class ProjectTaskDiscussRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectTaskDiaryEntity::buildQuery($params, $query);

        return $query;
    }

    public static function buildProjectTaskDiscussQuery($managerId, $taskId, $query = null)
    {
        $params = [
            'taskdiary_project' => $managerId,
            'taskdiary_task' => $taskId
        ];
        return self::buildQuery($params, $query);
    }

    public static function buildProjectTaskDiscussListQuery($managerId, $taskId, $params = [], $query = null)
    {
        $query = self::buildProjectTaskDiscussQuery($managerId, $taskId, $query);
        $params['task_diary_replyid'] = 0;
        self::buildQuery($params, $query);
        $query->with('user:user_id,user_name');
        $query->with('reply.user:user_id,user_name');
        $query->with('quote.user:user_id,user_name');
        return $query;
    }
}
