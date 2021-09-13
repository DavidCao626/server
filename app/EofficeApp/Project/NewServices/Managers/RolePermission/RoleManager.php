<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission;

use App\EofficeApp\Project\Entities\RoleFunctionPageEntity;
use App\EofficeApp\Project\NewRepositories\RoleFunctionPageRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class RoleManager
{

    const CACHE_KEY = 'PROJECT_ROLES_CACHE_KEY';

    /**
     * 获取角色id
     * @param null|array|string $key
     * @param null|string $managerType 项目类型
     * @return array role_id
     */
    public static function getRoleId($key = null, $managerType = null) {
        $roles = self::getRoles();
        $ids = [];
        $keyTemp = is_scalar($key) ? [$key] : $key;
        foreach ($roles as $role) {
            if ($key &&  !in_array($role['role_field_key'], $keyTemp)) {
                continue;
            }
            if ($managerType && $role['manager_type'] != $managerType) {
                continue;
            }
            $ids[] = $role['role_id'];
        }
        return $ids;
    }

    /**
     * 获取角色key
     * @param string|array $id
     * @return array role_field_key
     */
    public static function getRoleKey($id = null) {
        $roles = self::getRoles();
        $keys = [];
        $idTemp = is_scalar($id) ? [$id] : $id;
        foreach ($roles as $role) {
            if ($id &&  !in_array($role['role_id'], $idTemp)) {
                continue;
            }
            $keys[$role['role_id']] = $role['role_field_key'];
        }
        return $keys;
    }

    /**
     * 获取所有角色id
     * @param string $type project|question|document|task
     * @param null|int|array $managerType
     * @return array roles_id
     */
    public static function getRoleIdsByType($type, $managerType = null) {
        $roles = self::getRoles();
        $ids = [];
        $managerType && $managerType = HelpersManager::scalarToArray($managerType);
        foreach ($roles as $role) {
            if ($type &&  $role['type'] != $type) {
                continue;
            }
            if ($managerType &&  !in_array($role['manager_type'], $managerType)) {
                continue;
            }
            $ids[] = $role['role_id'];
        }
        return $ids;
    }

    /**
     * 获取某类型的全部字段key，键值为role_id，且根据manager_type分组
     * @param string $type project|question|document|task
     * @param $managerTypes
     * @return array eg:[
     *      1 => [1 => 'manager_person', 2 => 'manager_examine', ...],
     *      2 => [14 => 'manager_person', 15 => 'manager_examine', ...]
     *      ...
     * ]
     */
    public static function getFieldKeyGroupByManagerType($type, $managerTypes)
    {
        $managerTypes = HelpersManager::scalarToArray($managerTypes);
        $data = array_fill_keys($managerTypes, []);
        $roles = self::getRoles();
        foreach ($managerTypes as $managerType) {
            foreach ($roles as $role) {
                if ($role['type'] != $type) {
                    continue;
                }
                if ($role['manager_type'] != $managerType) {
                    continue;
                }
                $data[$role['manager_type']][$role['role_id']] = $role['role_field_key'];
            }
        }
        return $data;
    }

    /**
     * 获取每个字段名称对应的大类别
     * @param string|array $id
     * @return array types
     */
    public static function getTypeByRoleId($id)
    {
        $roles = self::getRoles();
        $data = [];
        $idTemp = is_scalar($id) ? [$id] : $id;
        foreach ($roles as $role) {
            if ($id &&  !in_array($role['role_id'], $idTemp)) {
                continue;
            }
            $data[$role['role_id']] = $role['type'];
        }
        return $data;
    }


    // 初始化角色相关联数据，用于判断权限
    public static function initRoleRelationData() {
        $dataManager = DataManager::getIns();
        $functionPageId = $dataManager->getFunctionPageBin()->getFunctionPageId();
        $managerState = $dataManager->getProject('manager_state');
        $managerId = $dataManager->getManagerId();
        $params = [
            'function_page_id' => $functionPageId,
            'manager_state' => $managerState,
            'is_checked' => 1,
        ];
        $roleFunctionPageModels = RoleFunctionPageRepository::buildDataRoleQuery($params)->get();
        $dataManager->setRoleFunctionPageModels($roleFunctionPageModels);
        // 当前用户该项目所拥有的角色,暂时没用上屏蔽
        if ($managerId && $roleFunctionPageModels->isNotEmpty()) {
//            $params = [
//                'manager_id' => $managerId,
//                'user_id' => $dataManager->getCurUserId(),
//            ];
//            $projectRoleUserModels = ProjectRoleUserRepository::buildQuery($params)->get();
//            $dataManager->setProjectRoleUserModels($projectRoleUserModels);
        }
    }

    #############私有函数###################

    // 获取
//    private static function getRolesConfig() {
//        $roles = self::getRoles();
//        return Arr::pluck($roles, 'role_id', 'role_field_key');
//    }

    public static function clearRoleCache()
    {
        CacheManager::delCache(self::CACHE_KEY);
    }

    private static function getRoles() {
        return CacheManager::getArrayCache(self::CACHE_KEY, function () {
            return ProjectRoleRepository::getRoles()->toArray();
        });
    }

    // 脚本关联
    private static function getRoleFunctionPageConfig() {
        return CacheManager::getProjectConfig('role_function_page');
    }

    // 【脚本】初始化生成关联数据
    public static function initRoleFunctionPage() {
        $roleFunctionPageConfig = self::getRoleFunctionPageConfig();
        $functionPagesConfig = FunctionPageManager::getFunctionPagesConfig();
//        $functionPageIds = array_keys($functionPagesConfig);
        $roleKeys = [
            'manager_person' => 1,
            'manager_examine' => 2,
            'manager_monitor' => 3,
            'manager_creater' => 4,
            'team_person' => 5,
            'task_creater' => 6,
            'task_persondo' => 7,
            'doc_creater' => 8,
            'question_person' => 9,
            'question_doperson' => 10,
            'question_creater' => 11,
            'p1_task_persondo' => 12,// 上级
            'p2_task_persondo' => 13,// 多层上级
        ];
        $data = [];
        $now = Carbon::now()->toDateTimeString();
        foreach ($roleFunctionPageConfig as $managerState => $items) {
            foreach ($items as $roleKey => $categories) {
                foreach ($categories as $category => $checkedStatus) {
                    $categoryFunctionPageIds = array_keys($functionPagesConfig[$category]);
                    foreach ($checkedStatus as $index => $isChecked) {
                        $data[] = [
                            'role_id' => $roleKeys[$roleKey],
                            'function_page_id' => $categoryFunctionPageIds[$index],
                            'manager_state' => $managerState,
                            'is_checked' => $isChecked,
                            'examine_config' => '',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }
        if ($data) {
            $data = array_chunk($data, 1000);
            foreach ($data as $batchData) {
                RoleFunctionPageEntity::query()->insert($batchData);
            }
        }
    }
}
