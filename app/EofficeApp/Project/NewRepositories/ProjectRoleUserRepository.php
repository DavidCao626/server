<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectRoleUserEntity;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use DB;
use Illuminate\Database\Eloquent\Builder;

class ProjectRoleUserRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectRoleUserEntity::buildQuery($params, $query);

        return $query;
    }

    public static function buildProjectTaskPersonDoQuery($managerId, $query = null)
    {
        $taskPersonRoleId = RoleManager::getRoleId('task_persondo');
        $params = [
            'manager_id' => $managerId,
            'relation_type' => 'task',
            'role_id' => $taskPersonRoleId
        ];
        return self::buildQuery($params, $query);
    }

    // 构建项目的所有父任务执行人
    public static function buildProjectParentTaskPersonDoQuery($managerId, $query = null)
    {
        // 20200914 AddProjectRoleUserTableAndMigrationData脚本更新项目进度时关联引用，此时无project_roles表，所以会报错，传入固定id即可
        try {
            $parentTaskRoleIds = RoleManager::getRoleId(['p1_task_persondo', 'p2_task_persondo']);
        } catch (\Exception $e) {
            $parentTaskRoleIds = [12, 13];
        }
        $params = [
            'manager_id' => $managerId,
            'relation_type' => 'task',
            'role_id' => $parentTaskRoleIds
        ];
        return self::buildQuery($params, $query);
    }

    public static function buildProjectDataQuery($managerId, $query = null)
    {
        return self::buildQuery(['manager_id' => $managerId], $query);
    }

    // 构建全部数据的查询对象，即包含禁用状态的数据
    public static function buildNotDisabledQuery($params = [], $query = null)
    {
        return self::buildQuery($params, $query)->where('is_disabled', 0);
    }
}
