<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission;

use App\EofficeApp\Project\Entities\ProjectBaseEntity;
use App\EofficeApp\Project\Entities\ProjectDocumentDirEntity;
use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleManagerTypeRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserGroupRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewRepositories\RoleFunctionPageRepository;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\ProjectService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PermissionManager
{

    /**
     * @param DataManager $dataManager
     * @param bool $setPermissionInfo
     * @param null $otherFunctionPageId 如果指定功能，则验证此功能的权限，否则验证当前api的权限
     * @param bool $withoutSpecial 特殊权限会重复调用同一函数，因此第二次调用就要传该参数，防止死循环
     * @return bool
     */
    public static function validPermission(DataManager $dataManager, $setPermissionInfo = true, $otherFunctionPageId = null, $withoutSpecial = false)
    {
        $data = $dataManager->getRelations();
        $project = $dataManager->getProject();
        $managerIdManagerState = [$project['manager_id'] => $project['manager_state']];
        $userId =$dataManager->getCurUserId();
        $functionPageId = $otherFunctionPageId ? $otherFunctionPageId : $dataManager->getFunctionPageBin()->getFunctionPageId();

        self::getDataFunctionPages($data, $managerIdManagerState, $userId, $functionPageId, $withoutSpecial);
        $hasPermission = true;
        foreach ($data as $item) {
            if (!array_key_exists($functionPageId, $item->function_page_configs)) {
                $hasPermission = false;
            }
        }
        $setPermissionInfo && $dataManager->setRelations($data);
        return $hasPermission;
    }

    public static function setDataFunctionPages(&$data, DataManager $dataManager, array $functionPageIds)
    {
        $userId =$dataManager->getCurUserId();
        // 特例： 为项目的列表时，这个id与state都为null，再getDataFunctionPages中会做覆盖处理
        $managerId = $dataManager->getManagerId();
        $managerState = $dataManager->getProject('manager_state');
        $managerIdManagerState = [$managerId => $managerState];
        self::getDataFunctionPages($data, $managerIdManagerState, $userId, $functionPageIds);
    }

    public static function setDifferentProjectDataFunctionPages(&$data, $userId, array $functionPageIds)
    {
        //        $own = ['user_id' => $userId];
        if ($data->isEmpty()) {
            return;
        }
        $model = $data->first();
        $type = ProjectBaseEntity::getTypeByClass($model);
        $managerFieldKey = ProjectBaseEntity::getRelationProjectField($type);
        $managerIds = $data->pluck($managerFieldKey)->unique()->toArray();
        $managerIdManagerState = ProjectManagerRepository::buildQuery(['manager_id' => $managerIds])
            ->pluck('manager_state', 'manager_id')->toArray();
        self::getDataFunctionPages($data, $managerIdManagerState, $userId, $functionPageIds);
    }

    public static function setProjectListFunctionPages(Collection &$projectList, $curUserId, array $functionPageIds)
    {
        $managerIdManagerState = $projectList->pluck('manager_state', 'manager_id')->toArray();
        self::getDataFunctionPages($projectList, $managerIdManagerState, $curUserId, $functionPageIds);
    }

    // 查出用户所有的角色，同时查出指定功能再不同项目状态下拥有的角色，交集得出用户角色与用户用于的功能。
    // 部分功能拥有权限设置，因此还需要根绝配置进行功能过滤
    public static function getDataFunctionPages(&$data, $managerIdManagerState, $userId, $functionPageIds = null, $withoutSpecial = false)
    {
        if (!$functionPageIds) {return;}
        HelpersManager::toEloquentCollection($data, function ($data) use ($managerIdManagerState, $userId, $functionPageIds, $withoutSpecial) {
            $model = $data->first();
            $type = ProjectBaseEntity::getTypeByClass($model);

            // 当类型是项目时，兼容多项目的情况，覆盖状态
            if ($type == 'project') {
                $managerIdManagerState = $data->pluck('manager_state', 'manager_id')->toArray();
            }

            $primaryKey = $model->getKeyName();
            $relationIds = $data->pluck($primaryKey)->toArray();
            $managerIds = array_keys($managerIdManagerState);
            $managerFieldKey = ProjectBaseEntity::getRelationProjectField($type);

            // 拿到所有数据的角色，按项目id分层
            $managerIdManagerTypes = ProjectManagerRepository::getManagerTypeByManagerId($managerIds);
            $queryParams = self::getAllManagerTypeRoleIdUserQueryParams($managerIdManagerTypes, $userId);
            $params = [
                'manager_id' => $managerIds,
//                'user_id' => $userId,
                'or_users' => $queryParams,
                'or_1' => [
                    'where_1' => [
                        'relation_type' => $type,
                        'relation_id' => $relationIds
                    ],
                    'relation_type' => [$type, '!=']
                ]
            ];
            $projectsRoles = ProjectRoleUserRepository::buildNotDisabledQuery($params)->get(); // 拿到数据上用户所有的角色
            $roleIds = $projectsRoles->pluck('role_id')->unique()->toArray();
            $projectsRoles = $projectsRoles->groupBy('manager_id');

            // 监控权限的角色信息
            $monitorRoleIdsData = self::getMonitorRoleIds($userId, $managerIdManagerTypes);
            $roleIds = array_merge($roleIds, $monitorRoleIdsData['role_ids']);
            $monitorRoleIdsData = $monitorRoleIdsData['manager_role_ids'];

                // 区分公共角色与数据的角色
            foreach ($projectsRoles as $managerId => $roles) {
                $commonRoleIds = $roles->where('relation_type', '!=', $type)->pluck('role_id')->unique()->toArray();
                $relationRoleIds = $roles->where('relation_type', '=', $type)->groupBy('relation_id');
                foreach ($relationRoleIds as $relationId => $item) {
                    $relationRoleIds[$relationId] = $item->pluck('role_id')->unique()->toArray();
                }
                $projectsRoles[$managerId] = [
                    'common' => $commonRoleIds,
                    'relationRoleIds' => $relationRoleIds->toArray(),
                ];
            }
            $projectsRoles = $projectsRoles->toArray();

            // 拿到相关的功能角色数据,按状态、角色分层得到功能id
            $params = [
                'role_id' => $roleIds,
                'function_page_id' => $functionPageIds,
                'is_checked' => 1,
                'manager_state' => array_unique($managerIdManagerState),
                'with_configs' => true
            ];
            $functionPages = RoleFunctionPageRepository::buildQuery($params)->get()->groupBy('manager_state');
//            foreach ($functionPages as $managerState => $item1) {
//                $item1 = $item1->groupBy('role_id');
//                foreach ($item1 as $roleId => $item2) {
//                    $item1[$roleId] = $item2->pluck('function_page_id');
//                }
//                $functionPages[$managerState] = $item1;
//            }
//            $functionPages = $functionPages->toArray();

            // 组装每个项目的功能数据
            foreach ($data as &$item) {
                $roles = [];
                $managerId = $item[$managerFieldKey];
                $relationId = $item->getKey();
                $projectRoles = array_extract($projectsRoles, $managerId);
                $managerState = array_extract($managerIdManagerState, $managerId, 0);
                if ($projectRoles) {
                    $commonRoleIds = $projectRoles['common'];
                    $relationRoleIds = Arr::get($projectRoles, 'relationRoleIds.' . $relationId, []);
                    $roles = array_merge($commonRoleIds, $relationRoleIds);
                }
                if ($monitorRoleIdsData) {
                    $monitorRoleIds = Arr::get($projectRoles, 'monitor', []);
                    $roles = array_merge($roles, Arr::get($monitorRoleIdsData, $managerId, []));
                }
                $functionPageIdConfigs = [];
                if ($roles && $functionPages) {
                    $curFunctionPages = $functionPages->get($managerState);
                    if ($curFunctionPages) {
                        $curFunctionPages = self::getRoleIdFunctionPages($curFunctionPages, $roles);
                        $functionPageIdConfigs = FunctionPageApiConfigManager::filterFunctionPages($item, $curFunctionPages); // 进行功能过滤
                    }
                }
                self::setMenuFunctionPageIds($functionPageIdConfigs, $functionPageIds, $managerId); // 根据菜单插入数据，含来自文件插入数据
                !$withoutSpecial && self::setSpecialFunctionPageIds($functionPageIdConfigs, $functionPageIds, $item);
                $item->all_roles = $roles;
                $item->function_page_configs = $functionPageIdConfigs;
            }
            return $data;
        });

    }

    // 获取监控权限的id以及每个项目对应的id
    private static function getMonitorRoleIds($userId, $managerIdManagerTypes)
    {
        $userRoleIds = ProjectService::getRoleService()->getUserRole($userId);
        $userRoleIds = Arr::pluck($userRoleIds, 'role_id');
        $deptId = OtherModuleRepository::buildUserSystemInfoQuery()->find($userId);
        $deptId = $deptId ? $deptId->dept_id : 0;
        // 查询所有监控权限id
        $roleIds = ProjectRoleUserGroupRepository::buildMyRolesQuery($userId, $deptId, $userRoleIds)
            ->distinct('role_id')->pluck('role_id')->toArray();

        $managerTypeRoles = [];
        $allManagerTypeRoleIds = [];
        if ($roleIds) {
            $managerIdManagerTypesTemp = $managerIdManagerTypes;
            array_push($managerIdManagerTypesTemp, 'all');
            $managerTypeRoles = ProjectRoleManagerTypeRepository::buildQuery(['role_id' => $roleIds, 'manager_type' => $managerIdManagerTypesTemp])->get();
            $roleIds = $managerTypeRoles->pluck('role_id')->toArray();
            $managerTypeRoles = $managerTypeRoles->groupBy('manager_type');

            // 提取全部类型的role_id
            if ($managerTypeRoles->has('all')) {
                $allManagerTypeRoleIds = $managerTypeRoles['all']->pluck('role_id')->toArray();
                $managerTypeRoles = $managerTypeRoles->forget('all');
            }

            foreach ($managerTypeRoles as $managerType => $roles) {
                $managerTypeRoles[$managerType] = $roles->pluck('role_id')->toArray();
            }
            $managerTypeRoles = $managerTypeRoles->toArray();
        }


        foreach ($managerIdManagerTypes as $managerId => $managerType) {
            $roleIdsTemp = Arr::get($managerTypeRoles, $managerType, []);
            $allManagerTypeRoleIds && $roleIdsTemp = array_merge($roleIdsTemp, $allManagerTypeRoleIds);
            $managerIdManagerTypes[$managerId] = $roleIdsTemp;
        }
        $data = [
            'role_ids' => $roleIds,
            'manager_role_ids' => $managerIdManagerTypes
        ];
        return $data;
    }

    // 获取当前数据的角色所拥有的权限
    private static function getRoleIdFunctionPages($functionPages, array $roleIds)
    {
        $newFunctionPages = collect();
        foreach ($functionPages as $key => $item) {
            if (in_array($item['role_id'], $roleIds)) {
                $newFunctionPages->push($item);
            }
        }
        return $newFunctionPages;
    }

    // 菜单权限角色赋予
    private static function setMenuFunctionPageIds(array &$functionPages, $functionPageIds, $managerId = '')
    {
        $otherFunctionPageIds = [];
        $otherFunctionPageConfig = [];
        if (ProjectService::hasReportPermission()) {
            $systemFunctionPageIds = ['project_info', 'task_info', 'question_info', 'document_info', 'document_batch_download', 'gantt_list', 'task_list', 'question_list', 'document_list', 'gantt_list', 'appraisal_list', 'dir_list'];
            $otherFunctionPageIds = array_merge($otherFunctionPageIds, $systemFunctionPageIds);
        }

        $object = config('project.custom_fpis.0');
        $func = config('project.custom_fpis.1');
        if (method_exists($object, $func)) {
            $temp = $object::$func($managerId, DataManager::getIns()->getOwn());
            $tempFPIs = array_keys($temp);
            $otherFunctionPageIds = array_merge($otherFunctionPageIds, $tempFPIs);
            $otherFunctionPageConfig = $temp;
        }

        // 合并权限
        $functionPageIds = HelpersManager::scalarToArray($functionPageIds);
        $otherFunctionPageIds = array_intersect($otherFunctionPageIds, $functionPageIds);
        foreach ($otherFunctionPageIds as $item) {
            if (!array_key_exists($item, $functionPages)) {
                $functionPages[$item] = [];
            }
        }

        // 合并自定义的功能配置
        if ($otherFunctionPageConfig) {
            foreach ($functionPages as $key => $item) {
                if (isset($otherFunctionPageConfig[$key])) {
                    $functionPages[$key] = $otherFunctionPageConfig[$key];
                }
            }
        }
    }

    /**
     * 根据项目类型，获取当前用户所有角色字段穿透的枚举查询条件
     * @param array $managerTypes 项目类型
     * @param string $userId 按次用户获取穿透的用户数据
     * @return array [where_1 => [role_id, user_ids]...]
     */
    public static function getAllManagerTypeRoleIdUserQueryParams($managerTypes, $userId)
    {
        $managerTypes = array_unique($managerTypes);
        $roleIds = ProjectRoleRepository::buildDataRoleQuery()
            ->whereIn('manager_type', $managerTypes)
            ->pluck('role_id')->toArray();
        $roleRelationPenetrateUsers = ProjectService::getRoleRelationPenetrateUsers($roleIds, $userId); // 所有角色数据穿透的用户信息
        $queryParams = [];
        foreach ($roleRelationPenetrateUsers as $roleId => $userIds) {
            $queryParams['where_' . $roleId] = [
                'role_id' => $roleId,
                'user_id' => $userIds
            ];
        }
        return $queryParams;
    }

    private static function setSpecialFunctionPageIds(array &$functionPages, $functionPageIds, $item) {
        // 档案文件夹 公共文件夹 给予添加权限
        $dataManager = DataManager::getIns();
        if ($dataManager->getProject()) {
            if (get_class($item) == ProjectDocumentDirEntity::class && $item['dir_id'] == 1 && !array_key_exists('dir_add', $functionPages)) {
                $canAddDir = self::validPermission($dataManager, false, 'dir_add', true);
                $canAddDir && $functionPages['dir_add'] = [];
            }
        }
    }
}
