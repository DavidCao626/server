<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\Entities\ProjectBaseEntity;
use App\EofficeApp\Project\Entities\ProjectDocumentEntity;
use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\Entities\ProjectQuestionEntity;
use App\EofficeApp\Project\Entities\ProjectTaskEntity;
use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Arr;

Trait ProjectRoleUserTrait
{
    static $projectRoleUserTraitData = [];

    /**
     * 执行数据更新，更新完后数据清空
     */
    public static function updateRelation()
    {
        $deleteIds = [];
        $insertData = [];
        foreach (static::$projectRoleUserTraitData as $managerId => $data) {
            self::handleProjectRelationData($managerId, $data, $deleteIds, $insertData);
        }
        $deleteIds && DatabaseManager::deleteByIds(ProjectRoleUserRepository::class, $deleteIds);
        $insertData && DatabaseManager::insertBatch(ProjectRoleUserRepository::class, $insertData, true);
        static::$projectRoleUserTraitData = [];
    }

    /**
     * 设置需要更新的权限数据
     * @param array $data 更新的数据来源，会从中提取出配置了的角色字段的值，值必须是id数组或id用,连接的字符串
     * @param string $type 类型：project|task|question|document
     * @param int $relationId 关联id
     * @param int $managerId 项目id
     * @param int $managerState 项目状态
     */
    public static function setFieldRoleRelation($data, $type, $relationId, $managerId, $managerState) {
        $managerType = ProjectManagerRepository::getManagerTypeByManagerId($managerId);
        $roleIdKey = RoleManager::getFieldKeyGroupByManagerType($type, $managerType);
        $roleIdKey = array_pop($roleIdKey); // id=>key对应的数组
//        $roleKeyId = arr.ay_flip($roleIdKey);
//        $data = array_extract($data, $roleIdKey, 'not set');
//        $data = array_filter($data, function ($value) {
//            return $value === 'not set' ? false : true;
//        });
        foreach ($roleIdKey as $roleId => $key) {
            if (array_key_exists($key, $data)) {
                $userIds = Arr::get($data, $key);
                self::setRelation($managerId, $managerState, $roleId, $relationId, $userIds);
            }
        }
    }

    /**
     * 为新的角色设置权限关联数据
     * @param Model|int $roleModel 角色数据对象|角色id
     */
    public static function setNewRoleRelation($roleModel) {
        is_scalar($roleModel) && $roleModel = ProjectRoleRepository::buildQuery()->find($roleModel); // id时查询一下
        $roleId = $roleModel->role_id;
        $managerType = $roleModel->manager_type;
        $type = $roleModel->type;
        $roleFieldKey = $roleModel->role_field_key;
        $isSystem = ProjectRoleRepository::buildDefaultQuery(['type' => $type, 'role_field_key' => $roleFieldKey])->exists(); // 是系统默认的都是主表字段
        $tableName = '';
        $relationField = ProjectBaseEntity::getRelationProjectField($type);
        $primaryField = '';
        $query = null;
        $projectManagerTableName = (new ProjectManagerEntity())->table;
        switch ($type) {
            case 'project':
                $class = new ProjectManagerEntity();
                if ($isSystem) {
                    $tableName = $class->table;
                    $primaryField = $class->primaryKey;
                } else {
                    $tableName = self::getProjectCustomTableKey($managerType, true);
                    $primaryField = 'data_id';
                    $relationField = 'data_id';
                }
                break;
            case 'task':
                $class = new ProjectTaskEntity();
                $mainTableName = $class->table;
                if ($isSystem) {
                    $tableName = $mainTableName;
                    $primaryField = $class->primaryKey;
                } else {
                    $tableName = self::getProjectTaskCustomTableKey($managerType, true);
                    $primaryField = 'data_id';
                    $query = DB::table($tableName)->join($mainTableName, "{$mainTableName}.task_id", '=', "{$tableName}.data_id")
                        ->join($projectManagerTableName, "{$projectManagerTableName}.manager_id", '=', "{$mainTableName}.$relationField");
                }

                break;
            case 'document':
                $class = new ProjectDocumentEntity();
                $tableName = $class->table;
                $primaryField = $class->primaryKey;
                break;
            case 'question':
                $class = new ProjectQuestionEntity();
                $tableName = $class->table;
                $primaryField = $class->primaryKey;
                break;
        }
        if (!$query) {
            $query = DB::table($tableName);
            // 非项目的主表需要获取项目的状态，因此关联项目表
            $primaryField != 'manager_id' && $query->join($projectManagerTableName, "{$projectManagerTableName}.manager_id", '=', "{$tableName}.$relationField");
        }
        $query->where("manager_type", $managerType)->orderBy($primaryField);
        $select = ["{$tableName}.$primaryField", "$relationField", "{$tableName}.$roleFieldKey", 'manager_state'];
        $query->select($select)->chunk(5000, function($data) use ($relationField, $roleId, $primaryField, $roleFieldKey) {
            foreach ($data as $item) {
                $item = (array) $item;
                $item[$roleFieldKey] && self::setRelation($item[$relationField], $item['manager_state'], $roleId, $item[$primaryField], $item[$roleFieldKey]);
            }
            self::updateRelation();
        });
    }

    public static function deleteRoleRelation($roleId)
    {
        if ($roleId) {
            ProjectRoleUserRepository::buildQuery(['role_id' => $roleId])->delete();
        }
    }

    /**
     * 为数据设置角色数据
     * @param Model|Collection $data 单个模型数据或者包含模型数据的集合，不是模型会报错
     * @param string $type 类型：project|task|question|document
     * @param array|int $limitRoleIds 限制的指定权限id，与该类型的所有id取交集
     */
    public static function setModelRoles(&$data, $type, $limitRoleIds = [])
    {
        HelpersManager::toEloquentCollection($data, function ($data) use ($type, $limitRoleIds) {
            // 利用模型获取主键
            $model = $data->first();
            $primaryKey = $model->getKeyName();
            $relationField = ProjectBaseEntity::getRelationProjectField($type);

            // 获取当前类型所有的角色数据
            $data = $data->keyBy($primaryKey);
            $relationIds = $data->keys()->toArray();
            $managerIds = $data->pluck($relationField)->unique()->toArray();
            $managerTypes = ProjectManagerRepository::getManagerTypeByManagerId($managerIds);
            $managerTypeRoleKeys = RoleManager::getFieldKeyGroupByManagerType($type, $managerTypes);
            $roleIds = RoleManager::getRoleIdsByType($type, $managerTypes);
            if ($limitRoleIds) {
                $limitRoleIds = HelpersManager::scalarToArray($limitRoleIds);
                $roleIds = array_intersect($roleIds, $limitRoleIds);
                $roleIdFlip = array_flip($roleIds);
                foreach ($managerTypeRoleKeys as $key => $items) {
                    $managerTypeRoleKeys[$key] = array_intersect_key($managerTypeRoleKeys[$key], $roleIdFlip);
                }
            }
            $queryParams = [
                'manager_id' => $managerIds,
                'role_id' => $roleIds,
                'relation_id' => $relationIds,
            ];
            $roles = ProjectRoleUserRepository::buildQuery($queryParams)
                ->select(['role_id', 'user_id', 'relation_id'])
                ->with('user:user_name,user_id')->get();
            $roles = $roles->groupBy('relation_id');
            foreach ($data as $relationId => &$item) {
                // 获取当前数据的角色
                if ($roles) {
                    $rolesTemp = $roles->get($relationId);
                    $rolesTemp = $rolesTemp ? $rolesTemp->groupBy('role_id')->toArray() : [];
                } else {
                    $rolesTemp = [];
                }
                $allUserName = [];
                $managerId = $item[$relationField];
                $roleKeys = $managerTypeRoleKeys[$managerTypes[$managerId]];
                $roleKeys = array_unique($roleKeys);
                foreach ($roleKeys as $roleId => $roleKey) {
                    $usersInfo = Arr::pluck(Arr::get($rolesTemp, $roleId, []), 'user');
                    $usersInfo = Arr::pluck($usersInfo, 'user_name', 'user_id');
                    $allUserName = array_merge($allUserName, $usersInfo);
//                $item[$roleKey] = Arr::pluck($usersInfo, 'user_id');
                    $item[$roleKey . '_name'] = implode(',', $usersInfo);
                    $item[$roleKey] = implode(',', array_keys($usersInfo)); // Todo 表单用的是,号间隔的数据，不是数组，需要统一
                }
                $item['users_info'] = $allUserName;
            }
            return $data->values();
        });
    }

    /**
     * 删除某类型的角色数据
     * @param string $type task|document|question
     * @param int|array $relationIds
     * @param int|array $managerId
     */
    public static function deleteProjectTypeRoleUser($type, $relationIds, $managerId)
    {
        $roleIds = RoleManager::getRoleIdsByType($type); // 能使用到索引
        $params = [
            'role_id' => $roleIds,
            'manager_id' => $managerId,
            'relation_id' => $relationIds,
            'relation_type' => $type,
        ];
        ProjectRoleUserRepository::buildQuery($params)->delete();
    }

    public static function getProjectUsers($managerId)
    {
        $userIds = ProjectRoleUserRepository::buildProjectDataQuery($managerId)->distinct()->pluck('user_id')->toArray();
        return OtherModuleRepository::buildUserQuery(['in_user_id' => $userIds])->get();
    }

    // 设置更新数据，保证数据格式
    private static function setRelation($managerId, $managerState, $roleId, $relationId, $userIds)
    {
        $userIds = is_array($userIds) ? $userIds : explode(',', $userIds);
        $userKey = "{$managerId}.roles.{$roleId}.{$relationId}";
        $managerStateKey = "{$managerId}.manager_state";
        Arr::set(static::$projectRoleUserTraitData, $userKey, $userIds);
        Arr::set(static::$projectRoleUserTraitData, $managerStateKey, $managerState);
    }

    // 更新时处理删除数据与新增数据
    private static function handleProjectRelationData($managerId, $data, array &$deleteIds, array &$insertData)
    {
        $managerState = $data['manager_state'];
        $roles = $data['roles'];
        $roleIds = array_keys($roles);
//        $roleKeys = RoleManager::getRoleKey($roleIds);
//        $relationTypes = self::getRoleRelationTypes($managerId);
        $queryParams = [
            'in_role_id' => $roleIds,
            'manager_id' => $managerId
        ];
        $oldData = ProjectRoleUserRepository::buildQuery($queryParams)->select(['id', 'role_id', 'relation_id', 'user_id'])->get()->groupBy('role_id');
        foreach ($oldData as $roleId => $items) {
            $oldData[$roleId] = $items->groupBy('relation_id');
        }
        $oldData = $oldData->toArray();
        $types = RoleManager::getTypeByRoleId($roleIds);
        foreach ($roles as $roleId => $relations) {
//            $roleId = $roleIds[$roleKey];
            foreach ($relations as $relationId => $newUserIds) {
                $oldRelations = Arr::get($oldData, "{$roleId}.{$relationId}", []);
                $oldUserIds = Arr::pluck($oldRelations, 'user_id', 'id');
                $deleteUserIds = array_diff($oldUserIds, $newUserIds);
                $deleteIdsTemp = array_extract(array_flip($oldUserIds), $deleteUserIds); // 根据用户id提取删除的数据id
                $deleteIdsTemp = array_values($deleteIdsTemp);
                $insertUserIds = array_diff($newUserIds, $oldUserIds);
                $insertDataTemp = self::buildNewInsertData($insertUserIds, $managerId, $managerState, $roleId, $types[$roleId], $relationId);
                $deleteIds = array_merge($deleteIds, $deleteIdsTemp);
                $insertData = array_merge($insertData, $insertDataTemp);
            }
        }
    }

    // 获取类型的大分类，如manager_person的大分类是project
//    private static function getRoleRelationTypes($managerId) {
//        $roleFields = HelpersManager::getProjectConfig('role_field');
//        $types = [];
//        foreach ($roleFields as $type => $roles) {
//            $temp = array_fill_keys($roles, $type);
//            $types = array_merge($types, $temp);
//        }
//        return $types;
//    }

    // 获取新插入的数据
    private static function buildNewInsertData($userId, $managerId, $managerState, $roleId, $relationType, $relationId)
    {
        $userIds = is_array($userId) ? $userId : [$userId];
        $data = [];
        foreach ($userIds as $userId) {
            if (!$userId) {
                continue;
            }
            $data[] = [
                'role_id' => $roleId,
                'manager_id' => $managerId,
                'relation_type' => $relationType,
                'relation_id' => $relationId,
                'user_id' => $userId,
                'manager_state' => $managerState
            ];
        }
        return $data;
    }
}
