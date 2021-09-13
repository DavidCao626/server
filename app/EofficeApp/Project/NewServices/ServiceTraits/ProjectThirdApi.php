<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermissionManager;

Trait ProjectThirdApi
{

    /**
     * 获取我有权限的项目id
     * @param array $own 不传则通过user()函数获取，必须保证是有token的状态下可以不传，队列等不行
     * @param array $params 参数，与projectList参数相同，或项目表字段查询均支持
     * @return array
     */
    public static function thirdMineProjectId($own = [], $params = [])
    {
        !$own && $own = user();
        $dataManager = RolePermissionManager::getDataManager($own, 'projectList', 'project_list', $params);
        self::setProjectTypeRoleId($dataManager);
        $projectIds = RolePermissionManager::getProjectListPermissionIds($params, $dataManager);
        if ($projectIds === 'all') {
            $projectIds = ProjectManagerRepository::buildQuery()->pluck('manager_id')->toArray();
        }
        return array_unique($projectIds);
    }

    public static function thirdMineProjectQuery($own = [], $params = [])
    {
        $projectIds = self::thirdMineProjectId($own, $params);
        return ProjectManagerRepository::buildQuery($params)->whereIn('manager_id', $projectIds);
    }

    /**
     * 获取项目的团队人员数据
     * @param $managerId
     * @return array [['user_id' => '', user_name => '']...]
     */
    public static function getProjectTeamPerson($managerId) {
        $teamRoleIds = RoleManager::getRoleId('team_person');
        $users = ProjectRoleUserRepository::buildQuery(['role_id' => $teamRoleIds, 'manager_id' => $managerId])
            ->with('user:user_name,user_id')
            ->get()->pluck('user');

        return $users->toArray();
    }

}
