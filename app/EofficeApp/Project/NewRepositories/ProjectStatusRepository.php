<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectStatusEntity;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use DB;
use Illuminate\Database\Eloquent\Builder;

class ProjectStatusRepository extends ProjectBaseRepository
{

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectStatusEntity::buildQuery($params, $query);

        return $query;
    }

    /**
     * 构建未读数据的查询对象
     * @param $relationIds
     * @param $curUserId
     * @param string $type project|task
     * @param null $query
     * @return Builder
     */
    public static function buildNotReadQuery($relationIds, $curUserId, $type = 'project', $query = null)
    {
        return self::buildQuery([
            'type' => $type,
            'relation_id' => $relationIds,
            'remind_flag' => 0,
            'participant' => $curUserId
        ], $query);
    }

    /**
     * 为项目相关角色 创建未读数据
     * @param $managerId
     * @param $type 'project|task'
     * @param $relationId
     * @param $curUserId
     */
    public static function create($managerId, $type, $relationId, $curUserId)
    {
        $projectRoleKeys = [
            'manager_person',
            'manager_examine' ,
            'manager_monitor',
            'manager_creater',
            'team_person',
        ];
        $managerType = ProjectManagerRepository::getManagerTypeByManagerId($managerId);
        $roleIds = RoleManager::getRoleId($projectRoleKeys, $managerType);
        $participants = ProjectRoleUserRepository::buildQuery([
            'manager_id' => $managerId,
            'role_id' => $roleIds
        ])->pluck('user_id')->unique()->toArray();
        $participants = array_diff($participants, [$curUserId]); // 排除自己
        $insertData = [];
        foreach ($participants as $participant) {
            if ($participant) {
                $projectData['participant'] = $participant;
                $projectData['type'] = $type;
                $projectData['remind_flag'] = 0;
                $projectData['relation_id'] = $relationId;
                $insertData[] = $projectData;
            }
        }

        $insertData && DatabaseManager::insertBatch(self::class, $insertData, true);
    }

    // 更新未读数据
    public static function update($managerId, $type, $relationId, $curUserId)
    {
        self::buildQuery(['type' => $type, 'relation_id' => $relationId])->delete();
        self::create($managerId, $type, $relationId, $curUserId);
    }
}
