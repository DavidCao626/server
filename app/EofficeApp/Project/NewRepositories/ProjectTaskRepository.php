<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectTaskEntity;
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
class ProjectTaskRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectTaskEntity::buildQuery($params, $query);

        return $query;
    }

    /**
     * 返回项目的任务数与未读状态
     * @param array $projectIds
     * @param $userId
     * @return array $projectTaskData [ 'projectId' => ['task_count' => int, 'plan' => float] ... ]
     */
    public static function getProjectListTaskInfo(array $projectIds, $userId) {
        $tasks = ProjectTaskRepository::buildQuery(['task_project' => $projectIds])->select('task_id', 'task_project', 'task_persent')->get();
        $taskIds = $tasks->pluck('task_id')->toArray();
        $projectTaskCount = $tasks->groupBy('task_project');
        $notReadTaskId = [];
        $taskIds && $notReadTaskId = ProjectStatusRepository::buildNotReadQuery($taskIds, $userId, 'task')->pluck('relation_id', 'relation_id')->toArray();
        $projectTaskData = [];
        foreach ($projectIds as $projectId) {
            $projectTasks = $projectTaskCount->get($projectId);
            if ($projectTasks) {
                $taskCount = $projectTasks->count();
                $completeTaskCount = $projectTasks->where('task_persent', '=', 100)->count();
                $projectTaskData[$projectId] = [
                    'task_new_feedback' => count(array_filter(array_extract($notReadTaskId, $projectTasks->pluck('task_id')->toArray()))),
                    'task_count' => $taskCount,
                    'complete_task_count' => $completeTaskCount,
                ];
            } else {
                $projectTaskData[$projectId] = [
                    'task_new_feedback' => 0,
                    'task_count' => 0,
                    'complete_task_count' => 0,
                ];
            }
        }
        return $projectTaskData;
    }

    public static function buildProjectTaskQuery($projectId, $query = null): Builder
    {
        return self::buildQuery(['task_project' => $projectId], $query);
    }

    /**
     * 构建任务是否完成得查询对象
     * @param int $taskStatus 0未完成1完成
     * @param null $query
     * @return Builder
     */
    public static function buildTaskStatusQuery($taskStatus = 0, $query = null): Builder
    {
        if ($taskStatus == 1) {
            $queryParams = ['task_persent' => 100];
        } else {
            $queryParams = ['task_persent' => [100, '<']];
        }
        return self::buildQuery($queryParams);
    }
}
