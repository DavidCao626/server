<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\Authority;

use App\EofficeApp\Project\Entities\ProjectRoleManagerTypeEntity;
use App\EofficeApp\Project\Entities\ProjectRoleUserGroupEntity;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;

Trait ProjectRoleManagerTypeTrait
{
    /**
     * 新生成角色后，生成对应的类型关联
     * @param $roleId
     * @param array $managerTypes 已勾选的项目类型
     */
    public static function createRoleManagerTypeByRoleId($roleId, $managerTypes) {
        $insertData = [];
        foreach ($managerTypes as $managerType) {
            $insertData[] = [
                'role_id' => $roleId,
                'manager_type' => $managerType,
            ];
        }

        DatabaseManager::insertBatch(ProjectRoleManagerTypeEntity::class, $insertData, true);
    }

    public static function updateRoleManagerTypeByRoleId($roleId, $managerTypes)
    {
        self::deleteRoleManagerTypeByRoleId($roleId);
        self::createRoleManagerTypeByRoleId($roleId, $managerTypes);
    }

    public static function deleteRoleManagerTypeByRoleId($roleId)
    {
        if ($roleId) {
            ProjectRoleManagerTypeEntity::buildQuery(['role_id' => $roleId])->delete();
        }
    }

    // 给数据权限角色创建关联关系为本人
    public static function createDefaultDataRoleRelations ($managerType) {
        $roleIds = ProjectRoleRepository::buildDataRoleQuery()
            ->where('manager_type', $managerType)
            ->pluck('role_id')->toArray();
        $insertData = [];
        foreach ($roleIds as $roleId) {
            $insertData[] = [
                'role_id' => $roleId,
                'type' => ProjectRoleUserGroupEntity::relationPenetrateKey,
                'type_value' => 1,
            ];
        }
        DatabaseManager::insertBatch(ProjectRoleUserGroupEntity::class, $insertData, true);
    }
}
