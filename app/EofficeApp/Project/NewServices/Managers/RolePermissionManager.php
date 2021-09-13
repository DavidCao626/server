<?php

namespace App\EofficeApp\Project\NewServices\Managers;

use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleManagerTypeRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserGroupRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewRepositories\RoleFunctionPageRepository;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins\AddApiBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins\DeleteApiBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins\EditApiBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins\InfoApiBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins\ListApiBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\FunctionPageBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\FunctionPageManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use App\EofficeApp\Project\NewServices\ProjectService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
class RolePermissionManager
{

    /**
     * 初始化权限相关的数据对象
     * @param array $own
     * @param $action
     * @param string|null $functionPageId
     * @param array $params
     * @return DataManager
     */
    public static function getDataManager($own, $action, $functionPageId = '', array $params = [])
    {
        // 必须初始化的数据，存在顺序性，相互依赖的，已api与function_page为起点
        $dataManager = DataManager::getIns($own, true);

        $dataManager->setApiParams($params);

        self::setApiBin($action, $dataManager);
        $apiBin = $dataManager->getApiBin();

        if (!$apiBin || $apiBin->getType() == 'system') {
            return;
        }

        empty($functionPageId) &&  $functionPageId = $apiBin->getDefaultFpi();
        self::setFunctionPageBin($functionPageId, $dataManager);

        // Todo 需要管控，暂时通过
        if (!$dataManager->getFunctionPageBin()) {
            return;
        }

        $dataManager->getApiBin()->initApiData();
        RoleManager::initRoleRelationData(); // 初始化功能角色与当前用户在项目中的角色，用于判断角色
        $dataManager->getApiBin()->testPermission();

        // 非权限验证必须的数据
        FunctionPageManager::initFunctionPageApiBin();
        return $dataManager;
    }

    // 切换权限函数获取数据
    public static function toggleDataManager(Datamanager $dataManager, $action, $functionPageId, $params, callable $callable)
    {
        $own = $dataManager->getOwn();
        $newDataManager = self::getDataManager($own, $action, $functionPageId, $params);
        $data = $callable($newDataManager);
        $newDataManager->toggleDataManager($dataManager); // 切换回原来的类
        return $data;
    }

    /**
     * 构建列表的查询对象: 根绝角色与功能页的匹配，获得所有对应对象
     * @param  array $params 查询参数，有些重复的查询需要提前处理合并并删除，不传入会自动去DataManager中获取api的数据
     * @param  Builder $query 查询对象
     * @param  DataManager $dataManager 查询对象
     */
    public static function buildProjectListPermissionQuery(&$params, $query, $dataManager) {
        $projectIds = self::getProjectListPermissionIds($params, $dataManager);
        // 根据进度变更排序
        if ($progressChange = Arr::get($params, 'order_by.progress_change')) {
            unset($params['order_by']['progress_change']);
            ProjectManagerRepository::buildProgressUpdateSort($projectIds, $progressChange, $query);
        }

        if ($projectIds === 'all') {
            return;
        }
        if ($projectIds) {
            ProjectManagerRepository::buildQuery(['manager_id' => $projectIds], $query);
        } else {
            $query->whereRaw('1=2');
        }
    }

    /**
     * 获取有权限得所有项目id
     * @param array $params 查询参数，有些重复的查询需要提前处理合并并删除，不传入会自动去DataManager中获取api的数据
     * @param $dataManager
     * @return array|string
     */
    public static function getProjectListPermissionIds(&$params, $dataManager)
    {
        $userId = $dataManager->getCurUserId();
        $projectIds = [];
        if (!$dataManager) {
            return $projectIds;
        }

        $isAll = !Arr::get($params, 'role_id'); // 是全部，而非我管理的项目等筛选，因为此类为具体角色，监控和二开插入的都不是角色数据
        $monitorProjectIds = [];
        if ($isAll) {
            $monitorProjectIds = self::getMonitorProjectIds($userId, $dataManager->getFunctionPageBin()->getFunctionPageId(), $params);
            if ($monitorProjectIds == 'all') {
                $projectIds = 'all';
                self::filterFixUserRole($projectIds, $params);
                return $projectIds;
            }
        }

        $rolePageFunctions = $dataManager->getRoleFunctionPageModels();
        if ($userId && $rolePageFunctions) {
            // 过滤状态、角色
            if (Arr::get($params, 'showFinalPorjectFlag') == 1) {
                $rolePageFunctions = $rolePageFunctions->where('manager_state', '!=', 5);
            }
            if ($managerState = Arr::get($params, 'manager_state')) {
                if (is_array($managerState)) {
                    if (is_numeric(Arr::get($managerState, 1))) {
                        $rolePageFunctions = $rolePageFunctions->whereIn('manager_state', $managerState);
                    } else {
                        $rolePageFunctions = $rolePageFunctions->where('manager_state', Arr::get($managerState, 1), Arr::get($managerState, 0));
                    }
                } else {
                    $rolePageFunctions = $rolePageFunctions->where('manager_state', $managerState);
                }
            }
            if ($tempRoleId = Arr::get($params, 'role_id')) {
                if (is_array($tempRoleId)) {
                    $rolePageFunctions = $rolePageFunctions->whereIn('role_id', $tempRoleId);
                } else {
                    $rolePageFunctions = $rolePageFunctions->where('role_id', '=', $tempRoleId);
                }
            }
            unset($params['showFinalPorjectFlag']);
            // 组合查询
            $rolePageFunctions = $rolePageFunctions->groupBy('role_id');

            $roleIds = $rolePageFunctions->keys();
            $roleRelationPenetrateUsers = ProjectService::getRoleRelationPenetrateUsers($roleIds, $userId); // 所有角色数据穿透的用户信息

            if ($rolePageFunctions->isNotEmpty()) {
                $projectRoleUserQuery = ProjectRoleUserRepository::buildNotDisabledQuery(/*['user_id' => $userId]*/);
                $projectRoleUserQuery->where(function($query) use ($rolePageFunctions, $userId, $roleRelationPenetrateUsers) {
                    foreach ($rolePageFunctions as $roleId => $items) {
                        $query->orWhere(function ($query) use ($roleId, $items, $userId, $roleRelationPenetrateUsers) {
                            $tempRoleUsers = Arr::get($roleRelationPenetrateUsers, $roleId);
                            $query->where('project_role_user.role_id', $roleId)
                                ->whereIn('project_role_user.manager_state', $items->pluck('manager_state')->toArray());
                            is_array($tempRoleUsers) ? $query->whereIn('user_id', $tempRoleUsers) : $query->where('user_id', $tempRoleUsers);
                        });
                    }
                });

                $projectIds = $projectRoleUserQuery->distinct()->pluck('project_role_user.manager_id')->toArray();

            }
        }

        $projectIds = array_merge($monitorProjectIds, $projectIds);
        self::filterFixUserRole($projectIds, $params);
        self::handleCustomProjectIds($projectIds, $isAll, $params, $dataManager);
        return $projectIds;
    }

    // 获取监控权限的所有项目id
    private static function getMonitorProjectIds($userId, $functionPageId, $params = []) {
        $userRoleIds = ProjectService::getRoleService()->getUserRole($userId);
        $userRoleIds = Arr::pluck($userRoleIds, 'role_id');
        $deptId = OtherModuleRepository::buildUserSystemInfoQuery()->find($userId);
        $deptId = $deptId ? $deptId->dept_id : 0;
        $withFinalProject = Arr::get($params, 'showFinalPorjectFlag') != 1;
        // 查询所有监控权限id
        $roleIds = ProjectRoleUserGroupRepository::buildMyRolesQuery($userId, $deptId, $userRoleIds)
            ->distinct('role_id')->pluck('role_id')->toArray();
        if (!$roleIds) {
            return [];
        }

        // 根据权限id获取存在此功能的项目状态和项目类型
        $allManagerStates = ProjectManagerEntity::getAllManagerStates();
        $allManagerTypes = ProjectService::getAllProjectTypes();
        $roleManagerStatesQuery = RoleFunctionPageRepository::buildQuery(['role_id' => $roleIds, 'function_page_id' => $functionPageId, 'is_checked' => 1]);
        !$withFinalProject && $roleManagerStatesQuery->where('manager_state', '!=', 5);
        $roleManagerStates = $roleManagerStatesQuery
            ->select('role_id', 'manager_state')
            ->get()
            ->groupBy('role_id')
            ->toArray();
        foreach ($roleManagerStates as $roleId => $item) {
            $roleManagerStates[$roleId] = Arr::pluck($item, 'manager_state');
        }
        $roleManagerTypes = ProjectRoleManagerTypeRepository::buildQuery(['role_id' => $roleIds])
            ->select('role_id', 'manager_type')
            ->get()
            ->groupBy('role_id')
            ->toArray();
        foreach ($roleManagerTypes as $roleId => $item) {
            $types = Arr::pluck($item, 'manager_type');
            if (in_array('all', $types)) {
                $types = $allManagerTypes;
            }
            $roleManagerTypes[$roleId] = $types;
        }

        // 对状态和类型进行聚合,组装成状态为key，分别对应有权限的类型
        $managerStatesManagerTypes = array_fill_keys($allManagerStates, []);
        foreach ($roleIds as $roleId) {
            $managerTypesTemp = Arr::get($roleManagerTypes, $roleId, []);
            $managerStatesTemp = Arr::get($roleManagerStates, $roleId, []);
            foreach ($managerStatesTemp as $managerState) {
                $managerStatesManagerTypes[$managerState] = array_unique(array_merge($managerStatesManagerTypes[$managerState], $managerTypesTemp));
            }
        }

        // 对其中的全部数据进行精简
        $allManagerTypesStates = [];
        foreach ($managerStatesManagerTypes as $managerState => $managerTypes) {
            if (!array_diff($allManagerTypes, $managerTypes)) {
                $allManagerTypesStates[] = $managerState;
                unset($managerStatesManagerTypes[$managerState]);
            }
        }

        // 计算结果
        if (empty($managerStatesManagerTypes)) {
            return 'all';
        } else {
            $orParams = [];
            foreach ($managerStatesManagerTypes as $managerState => $managerTypes) {
                $orParams['where_' . $managerState] = [
                    'manager_state' => $managerState,
                    'manager_type' => $managerTypes,
                ];
            }
            if ($allManagerTypesStates) {
                $params = [
                    'or_1' => [
                        'manager_state' => $allManagerTypesStates,
                        'or_1' => $orParams
                    ]
                ];
            } else {
                $params = [
                    'or_1' => $orParams
                ];
            }
            return ProjectManagerRepository::buildQuery($params)->pluck('manager_id')->toArray();
        }
    }

    /**
     * 处理角色筛选，处理任务状态筛选
     * @param $projectIds
     * @param $params
     */
    private static function filterFixUserRole(&$projectIds, $params)
    {
        if (!$projectIds) {
            return;
        }
        $hasFilter = false;
        $filterProjectIds = [];
        foreach ($params as $key => $value) {

            if (strpos($key, 'role_id_') === 0) {
                $roleFieldKey = str_replace('role_id_', '', $key);
                $roleIds = RoleManager::getRoleId($roleFieldKey);
                $userRoleProjectIds = ProjectRoleUserRepository::buildNotDisabledQuery([
                    'user_id' => $value,
                    'role_id' => $roleIds
                ])->pluck('manager_id')->toArray();
                $filterProjectIds = !$hasFilter ? $userRoleProjectIds : array_intersect($filterProjectIds, $userRoleProjectIds);
                $hasFilter = true;
            }

            if ($key == 'task_status') {
                $taskStatusProjectIds = ProjectTaskRepository::buildTaskStatusQuery($value)
                    ->groupBy('task_project')->pluck('task_project')->toArray();
                $filterProjectIds = !$hasFilter ? $taskStatusProjectIds :  array_intersect($filterProjectIds, $taskStatusProjectIds);
                $hasFilter = true;
            }
        }

        if ($hasFilter) {
            $projectIds = $projectIds == 'all' ? $filterProjectIds : array_intersect($projectIds, $filterProjectIds); // 为全部项目时，则直接取过滤出来的id，反之取交集
        }
    }

    private static function setApiBin($action, DataManager $dataManager)
    {
        $bin = null;
        $apiConfig = config('project.api.' . $action);
        if ($apiConfig) {
            $apiConfig['action'] = $action;
            switch ($apiConfig[0]) {
                case 'list':
                    $bin = new ListApiBin($apiConfig);
                    break;
                case 'info':
                    $bin = new InfoApiBin($apiConfig);
                    break;
                case 'edit':
                    $bin = new EditApiBin($apiConfig);
                    break;
                case 'add':
                    $bin = new AddApiBin($apiConfig);
                    break;
                case 'delete':
                    $bin = new DeleteApiBin($apiConfig);
                    break;
            }
            $dataManager->setApiBin($bin);
        }

    }

    private static function setFunctionPageBin($functionPageId, DataManager $dataManager)
    {
        $functionPageId = $functionPageId ?? 0;
        $functionPagesConfig = config('project.function_pages');
        $functionPagesConfig = collect($functionPagesConfig)->collapse()->toArray();
        $functionPageConfig = Arr::get($functionPagesConfig, $functionPageId);
        if ($functionPageConfig) {
            $functionPageConfig['function_page_id'] = $functionPageId;
            $dataManager->setFunctionPageBin(new FunctionPageBin($functionPageConfig));
        }
    }

    // 读取二开插入的数据权限
    private static function handleCustomProjectIds(&$ids, $isAll, $params, DataManager $dataManager)
    {
        if (!$isAll) {
            return;
        }
        $object = config('project.custom_project_ids.0');
        $func = config('project.custom_project_ids.1');
        if (method_exists($object, $func)) {
            $projectIds = $object::$func($dataManager->getOwn());
            if ($projectIds === 'all') {
                $ids = 'all';
            } else if (is_array($projectIds) && $projectIds) {
                $ids = array_unique(array_merge($ids, $projectIds));
            }
        }
    }
}
