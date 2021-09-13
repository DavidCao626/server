<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectQuestionEntity;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
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
class ProjectQuestionRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectQuestionEntity::buildQuery($params, $query);

        return $query;
    }

    //项目发布的问题与我的草稿问题
    public static function buildMyProjectQuestion($managerIds, $userId, $query = null) {
        $creatorRoleId = RoleManager::getRoleId(['question_creater', 'question_person']);
        $params = [
            'manager_id' => $managerIds,
            'user_id' => $userId,
            'role_id' => $creatorRoleId
        ];
        $myCreateQuestionIds = ProjectRoleUserRepository::buildQuery($params)->pluck('relation_id')->toArray();
        $params = [
            'question_project' => $managerIds,
            'or_my_create' => [
                'question_state' => [0, '>'],
                'question_id' => $myCreateQuestionIds
            ]
        ];

        $query = self::buildQuery($params, $query);
        return $query;
    }

    // 项目发布的问题
    public static function buildProjectSubmittedQuestion($managerIds, $query = null) {
        return self::buildQuery([
            'question_state' => [0, '>'],
            'question_project' => $managerIds
        ], $query);
    }

    // 项目的问题
    public static function buildProjectQuestion($managerIds, $query = null) {
        return self::buildQuery([
            'question_project' => $managerIds
        ], $query);
    }
}
