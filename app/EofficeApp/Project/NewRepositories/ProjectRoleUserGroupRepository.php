<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectRoleUserGroupEntity;

class ProjectRoleUserGroupRepository extends BaseRepository {

    public static function buildQuery($params = [], $query = null)
    {
        return ProjectRoleUserGroupEntity::buildQuery($params, $query);
    }

    // 我的所有监控角色
    public static function buildMyRolesQuery($userId, $deptId, array $userRoleIds, $query = null)
    {
        $params = [
            'or_1' => [
                'type' => 'all',
                'or_user_group' => [
                    'where_1' => [
                        'type' => 'user_ids',
                        'type_value' => $userId,
                    ],
                    'where_2' => [
                        'type' => 'dept_ids',
                        'type_value' => $deptId,
                    ],
                    'where_3' => [
                        'type' => 'role_ids',
                        'type_value' => $userRoleIds,
                    ],
                ]
            ]
        ];

        return self::buildQuery($params, $query);
    }

    // 数据穿透权限
    public static function buildMyRelationPenetrateQuery($roleIds = [])
    {
        $query =  self::buildQuery(['type' => ProjectRoleUserGroupEntity::relationPenetrateKey]);
        $roleIds && $query->whereIn('role_id', $roleIds);
        return $query;
    }
}
