<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\NewRepositories\ProjectDiscussRepository;
use App\EofficeApp\Project\NewRepositories\ProjectDocumentRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectQuestionRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewRepositories\ProjectStatusRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermissionManager;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use App\Utils\ResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Schema;
Trait ProjectTrait
{
    public static function projectList($userId, $params = [], DataManager $dataManager = null) {
        $default = [
            '@with_my_project_not_read' => 1,
            '@with_my_task_count' => 1,
            '@with_my_question_count' => 1,
            '@with_my_doc_count' => 1,
            '@with_project_role' => 0,
            'showFinalPorjectFlag' => 0,
            'prefixId' => '', // 排列在前面的id
        ];
        $params = array_merge($default, $params);

        // 获取查询对象
        $queryParams =self::handleListParams($params);
        is_null($dataManager) && $dataManager = DataManager::getIns(['user_id' => $userId], true);


        if (Arr::get($params, 'list_type') === 'custom') {
            $data = self::getCustomProjectListData($params, $dataManager);
        } else {
            $query = self::getProjectListQuery($queryParams, $dataManager);
            $data = HelpersManager::paginate($query, $dataManager);
        }

        if (Arr::get($params, '@with_my_project_not_read')) {
            self::setProjectDiscussCount($data['list'], $userId);
        }
        if (Arr::get($params, '@with_my_question_count')) {
            self::setQuestionCount($data['list'], $userId);
        }
        if (Arr::get($params, '@with_my_doc_count')) {
            self::setDocumentCount($data['list']);
        }
        self::formatProjectListData($data['list'], $params, $userId);
        $params['@with_project_role'] && self::setProjectRoleInfo($data['list']);

        return $data;
    }

    public static function projectInfo(DataManager $dataManager) {
        $params = [
            '@with_project_role' => 1, // 项目角色数据
            '@with_project_info' => 1, // 项目字段翻译的数据
            '@with_project_custom_info' => 1, // 自定义字段数据
            '@with_my_project_not_read' => 0, // 自定义字段数据
            '@with_count_info' => 0, // 包含若干数量信息
        ];
        $inputParams = $dataManager->getApiParams();
        $userId = $dataManager->getCurUserId();
        $params = array_merge($params, $inputParams);

        $project = $dataManager->getProject();

        $params['@with_project_role'] && self::setProjectRoleInfo($project);

        if ($params['@with_project_info']) {
            $degrees = self::getAllProjectDegrees();
            $priorities = self::getAllProjectPriorities();
            $projectTypes = self::getAllProjectTypes();
            $project['manager_fast_name'] = Arr::get($degrees, $project['manager_fast'], '');
            $project['manager_level_name'] = Arr::get($priorities, $project['manager_level'], '');
            $project['type_name'] = Arr::get($projectTypes, $project['manager_type'], '');
            $project['manager_state_name'] = self::getProjectStateName($project['manager_state']);
        }

        $params['@with_my_project_not_read'] && self::setProjectDiscussCount($project, $userId);
        $params['@with_project_custom_info'] && self::setProjectCustomData($project);
        $params['@with_count_info'] && self::setBatchCountInfo($project, $userId);

        return $project;
    }

    // 团队列表，仅手机端团队人员用到
    public static function projectTeamList(DataManager $dataManager)
    {
        $params = $dataManager->getApiParams();
        $project = $dataManager->getProject();
        $teamRoleId = RoleManager::getRoleId('team_person', $project['manager_type']);
        $userIds = $project->teams($teamRoleId)->pluck('user_id')->toArray();

        $param["search"] = [
            "user_id" => [$userIds, "in"]
        ];

        $param = array_merge($param, $params);

        return self::getSelf()->response(self::getUserRepository(), 'getUserListTotal', 'getUserList', $param);
    }

    public static function projectAdd($data, $userId)
    {
        $managerType = self::getManagerType($data);
        $responseService = ResponseService::getNewIns();
        self::filterNull($data);
        self::handleRoleAllUser($data);
        self::filterProjectDate($data)->checkException(); // 验证日期
        self::handleInputModelData($data, $userId); // 处理保存的默认数据
        $customTableKey = self::getProjectCustomTableKey($managerType);
        $checkResultParam['search']['is_system'] = [1];
        $checkResult = self::getFormModelingService()->authDataValid($customTableKey, $data,$checkResultParam);
        $responseService->setCodeException($checkResult);
        $responseService->checkException();

        $model = new ProjectManagerEntity();
        $model->fill($data)->save();
        $managerId = $model->getKey();
        try {
            self::auxiliaryTableSave($customTableKey, $data, $model, $managerId, 'project', 'add', false);
        } catch (\Exception $e) {
            $model->delete();
            throw $e;
        }

        ProjectLogManager::getIns($data['manager_creater'], $managerId)->projectAddLog($model->manager_name);
        self::setFieldRoleRelation($data, 'project', $managerId, $managerId, $model->manager_state);
        self::updateRelation();

        MessageManager::sendProjectCreatedReminder($model);

        return ['model' => $model];
    }

    public static function projectEdit(DataManager $dataManager) {
        $userId = $dataManager->getCurUserId();
        $project = $dataManager->getProject();
        $managerId = $project['manager_id'];

        $functionPageApi = $dataManager->getFunctionPageBin()->getFunctionPageId();
        $relations = $dataManager->getRelations();
        $editApiBin = $dataManager->getApiBin();
        $data = $dataManager->getApiParams();

        // 处理数据，填充变量
        self::handleRoleAllUser($data);
        self::filterProjectDate($data)->checkException(); // 验证日期
        self::checkProjectManagerUnique($data, $managerId)->checkException(); // 验证主表唯一
        $editApiBin->fillRelationData($relations, $data, $functionPageApi);

        $project = $relations->first();
        // 副表入库
        $customTableKey = self::getProjectCustomTableKey($project['manager_type']);
        self::auxiliaryTableSave($customTableKey, $data, $project, $managerId, 'project', 'edit', false);

        if (array_key_exists('team_person', $data)) {
            self::setFieldRoleRelation(array_extract($data, ['team_person']), 'project', $managerId, $managerId, $project['manager_state']); // 放更新之前设置，更新函数内会更新权限
        }

        $result = $project->mineSave($userId, $managerId);

        self::checkManagerStateChange($project, $project->getChanges(), $dataManager->getFunctionPageBin()->getFunctionPageId());

        return ['model' => $project];
    }

    public static function projectDelete(DataManager $dataManager)
    {
        $deleteManagerIds = $dataManager->getRelations()->pluck('manager_id')->toArray();
        $deleteManagerStates = $dataManager->getRelations()->pluck('manager_state')->toArray();
        $curUserId = $dataManager->getCurUserId();
        $managerNames = $dataManager->getRelations()->pluck('manager_name', 'manager_id')->toArray();

        //删除项目
        self::auxiliaryTableDelete($deleteManagerStates, $deleteManagerIds);
        $res = DatabaseManager::deleteByIds(ProjectManagerEntity::class, $deleteManagerIds);

        if ($res) {
            //任务讨论、附件删除(暂不删除)

            //任务、附件删除 (附件暂不删除)
            $taskQuery = ProjectTaskRepository::buildProjectTaskQuery($deleteManagerIds);
            DatabaseManager::deleteByQuery($taskQuery);
            //文档、附件删除 (附件暂不删除)
            $taskQuery = ProjectDocumentRepository::buildProjectDocument($deleteManagerIds);
            DatabaseManager::deleteByQuery($taskQuery);
            //问题 附件删除 (附件暂不删除)
            $taskQuery = ProjectQuestionRepository::buildProjectQuestion($deleteManagerIds);
            DatabaseManager::deleteByQuery($taskQuery);
            //项目讨论、附件删除(暂不删除)

            // 生成日志
            foreach ($managerNames as $managerIdTemp => $managerNameTemp) {
                ProjectLogManager::getIns($curUserId, $managerIdTemp)->projectDeleteLog($managerNameTemp);
            }
        }
        CacheManager::cleanProjectReportCache();
        self::syncCalendarStatus($deleteManagerIds, $curUserId, true);

        return $res;
    }

    // Todo 被福建森阳网络二开调用，不能大改动
    public static function exportProject($params)
    {
        return self::exportHandle($params, function ($apiParams, $header, $userInfo) {
            return self::getExportProjectData($apiParams, $header, $userInfo);
        });
    }

    public static function managerNumberList($input)
    {
        $query = ProjectManagerEntity::buildQuery($input);
        $query->select('manager_number', 'manager_id');
        $result = paginate($query);
        return $result;
    }

    public static function syncCalendarStatus($managerId, $curUserId, $isDelete = true, $isComplete = false) {
        if (is_array($managerId)) {
            foreach ($managerId as $managerIdTemp) {
                self::syncCalendarStatus($managerIdTemp, $curUserId, $isDelete, $isComplete);
            }
        }
        $relationData = [
            'source_id' => $managerId,
            'source_from' => 'project-detail'
        ];

        if ($isDelete) {
            return self::getCalendarService()->emitDelete($relationData, $curUserId);
        } else if ($isComplete) {
            return self::getCalendarService()->emitComplete($relationData);
        }
    }

    public static function syncCalendar(Model $project, $curUserId,  $isAdd = true)
    {
        // 编辑时必须修改了相关数据才更新日程
        if (!$isAdd && !$project->isDirty(['manager_name', 'manager_person', 'manager_begintime', 'manager_endtime'])) {
            return;
        }
        // 外发到日程模块 --开始--
        $managerId = $project->getKey();
        $calendarData = [
            'calendar_content' => $project['manager_name'],
            'handle_user'      => explode(',', $project['manager_person']),
            'calendar_begin'   => $project['manager_begintime'],
            'calendar_end'     => $project['manager_endtime']
        ];
        $relationData = [
            'source_id'     => $managerId,
            'source_from'   => 'project-detail',
            'source_title'  => $project['manager_name'],
            'source_params' => ['manager_id' => $managerId]
        ];
        $functionName = $isAdd ? 'emit' : 'emitUpdate';
        self::getCalendarService()->$functionName($calendarData, $relationData, $curUserId);
    }

    private static function getExportProjectData($params, $header, $userInfo)
    {
        // 特殊处理，grid会把数组变成字符串，导出不会处理，所以传过来的是数组
        if (isset($params['project_type']) && is_array($params['project_type'])) {
            $params['project_type'] = array_pop($params['project_type']);
        }
        $params['is_export'] = 1;// 插入导入标识
        $dataManager = RolePermissionManager::getDataManager($userInfo, 'projectList', 'project_list', $params);
        self::setProjectTypeRoleId($dataManager);
        $params = $dataManager->getApiParams();
        $currentUserId = Arr::get($userInfo, 'user_id');
        $data = self::projectList($currentUserId, $params, $dataManager);
        $data = Arr::get($data, 'list', []);
        $data = is_array($data) ? $data : $data->toArray();
        foreach ($data as $key => $item) {
            $data[$key]['progress'] .= '%';
            $data[$key]['manager_explain'] = strip_tags(htmlspecialchars_decode($item['manager_explain']));
        }
        return $data;
    }

    private static function handleRoleAllUser(array &$data)
    {
        $keys = ['manager_examine', 'manager_examine', 'manager_examine', 'team_person'];
        foreach ($keys as $key) {
            if (Arr::get($data, $key) === 'all') {
                $data[$key] = self::getUserIds();
            }
        }
    }

    private static function filterProjectDate($data)
    {
        $beginTime = Arr::get($data, 'manager_begintime');
        $endTime = Arr::get($data, 'manager_endtime');
        $responseService = ResponseService::getIns();
        if ($beginTime && $endTime && $endTime < $beginTime) {
            $responseService->setException('0x036028', 'project');
        }

        return $responseService;
    }

    private static function checkManagerStateChange($model, array $changes, $fpi)
    {
        if (isset($changes['manager_state'])) {
            ProjectRoleUserRepository::buildProjectDataQuery($model->getKey())->update(['manager_state' => $changes['manager_state']]);
            switch ($fpi) {
                case 'pro_examine':
                    MessageManager::sendProjectExamineReminder($model);
                    break;
                case 'pro_refuse':
                    MessageManager::sendProjectReturnReminder($model);
                    break;
                case 'pro_approve':
                    MessageManager::sendProjectBeginReminder($model);
                    break;
            }
        }
    }

    private static function setProjectCustomData(Model &$project) {
        $customData = self::getProjectCustomInfo($project['manager_type'], $project['manager_id']);
        $primaryTableKeys = array_keys($project->getAttributes());
        $customData = Arr::except($customData, $primaryTableKeys);
        $project->setRawAttributes(array_merge($project->getAttributes(), $customData));
    }

    // 获取项目角色数据
    private static function setProjectRoleInfo(&$data) {
        if (!$data) {return;}
        self::setModelRoles($data, 'project');
//        $roles = $project->project_person()->select('role_id', 'user_id', 'manager_id')->with('user:user_id,user_name')->get();
//        $roles = $roles->groupBy('role_id')->toArray();
//        $roleIds = RoleManager::getRoleId();
////         根据角色的role_id提取用户数据
//        $project['persons'] = Arr::pluck(Arr::get($roles, $roleIds['manager_person'], []), 'user');
//        $project['examines'] = Arr::pluck(Arr::get($roles, $roleIds['manager_examine'], []), 'user');
//        $project['monitors'] = Arr::pluck(Arr::get($roles, $roleIds['manager_monitor'], []), 'user');
//        $project['teams'] = Arr::pluck(Arr::get($roles, $roleIds['team_person'], []), 'user');
//        $project['manager_creater'] = Arr::get($roles, $roleIds['manager_creater'] . '.0.user_id', '');
//
//        $project['manager_examines'] = Arr::pluck($project['examines'], 'user_id');
//        $project['manager_persons'] = Arr::pluck($project['persons'], 'user_id');
//        $project['manager_monitors'] = Arr::pluck($project['monitors'], 'user_id');
//        $project['team_persons'] = Arr::pluck($project['teams'], 'user_id');
//        $project['team_person'] = implode(',', $project['team_persons']);
//        $project['manager_creater_name'] = Arr::get($roles, $roleIds['manager_creater'] . '.0.user.user_name', '');
//        $project['manager_person'] = implode(',', $project['manager_person']);
//        $project['manager_monitor'] = implode(',', $project['manager_monitor']);
//        $project['manager_examine'] = implode(',', $project['manager_examine']);
//        $project['team_person'] = implode(',', $project['team_person']);


    }

    private static function getProjectCustomInfo($managerType, $managerId) {
        return self::getFormModelingService()->getCustomDataDetail(self::getProjectCustomTableKey($managerType), $managerId);
    }

    private static function formatProjectListData(Collection &$data, $params, $userId) {
        $data = $data->keyBy('manager_id');
        $projectIds = $data->keys()->toArray();
        if (Arr::get($params, '@with_my_task_count')) {
            $tasks = ProjectTaskRepository::getProjectListTaskInfo($projectIds, $userId);
            foreach ($data as $managerId => &$item) {
                if (isset($tasks[$managerId])) {
                    foreach ($tasks[$managerId] as $key => $value) {
                        $item[$key] = $value;
                    }
                }
            }
//            self::twoDimensionArrayMerge($data, $tasks);
        }

        $allProjectTypes = self::getAllProjectTypes();
        foreach ($data as $managerId => $item) {
            $data[$managerId]['manager_type_name'] = Arr::get($allProjectTypes, $item['manager_type'], '');
            isset($item['manager_explain']) && $data[$managerId]['manager_explain'] = strip_tags(htmlspecialchars_decode($item['manager_explain']));
        }
        $data = $data->values();
    }

    private static function getProjectListQuery($queryParams = [], $dataManager = null) {

        $query = ProjectManagerRepository::buildQuery();
        RolePermissionManager::buildProjectListPermissionQuery($queryParams, $query, $dataManager);
        ProjectManagerRepository::buildQuery($queryParams, $query);

        $query->orderBy('manager_id', 'desc'); // Todo 需要改进
        return $query;
    }

    private static function getCustomProjectListData($originParams = [], DataManager $dataManager) {
        $managerType = Arr::get($originParams, 'manager_type');
        $queryParams = self::handleListParams($originParams); // 要从search中提取部分参数
        $projectIds = RolePermissionManager::getProjectListPermissionIds($queryParams, $dataManager);
        $customTableKey = self::getProjectCustomTableKey($managerType);

        $originParams = self::handleListParams($originParams, false); // search是json，所以需要处理
        self::removeRoleIdKey($originParams['search']);
        is_array($projectIds) && $originParams['search']['manager_id'] =  [$projectIds, 'in'];
        $originParams['search']['manager_type'] =  [$managerType, '='];

        // 特殊处理状态，因为那边不兼容识别这种格式
        $managerState = Arr::get($originParams, 'search.manager_state');
        if ($managerState) {
            $originParams['search']['manager_state'] = [[$managerState], '='];
        }

        $own = $dataManager->getOwn();
        if (Arr::get($originParams, 'is_export')) {
            $data = self::getFormModelingService()->exportFields($customTableKey, $originParams, $own, null, function (&$newData, $oldData) {
                 $newData['progress'] = object_get($oldData, 'progress', 0);
                 $newData['manager_id'] = object_get($oldData, 'manager_id', 0);
                 $newData['manager_type'] = object_get($oldData, 'manager_type', 0);
            });
            $data = [
                'list' => $data['data'],
                'total' => count($data['data']),
            ];
        } else {
            $data = self::getFormModelingService()->getCustomDataLists($originParams, $customTableKey, $own);
        }

        // 处理list ，团队成员需要单独处理
        self::toProjectEloquentCollection($data['list']);
        if ($data['list']->isNotEmpty()) {
            self::setModelRoles($data['list'], 'project', RoleManager::getRoleId('team_person')); // 获取团队成员
            foreach ($data['list'] as &$item) {
                if ($item['team_person'] && $item['users_info']) {
                    $item['raw_team_person'] = $item['team_person'];
                    $teamPersons = explode(',', $item['team_person']);
                    $usersTemp = array_extract($item['users_info'], $teamPersons, []);
                    $item['map_team_person'] = $usersTemp;
                    $item['team_person'] = implode(',', $usersTemp);
                }
            }
        }

        return $data;
    }

    // 过滤角色得key，否则自定义字段报错
    private static function removeRoleIdKey(&$search)
    {
        if (!$search) return;
        foreach ($search as $key => $item) {
            if (strpos($key, 'role_id_') === 0) {
                unset($search[$key]);
            }
        }
    }

    private static function setQuestionCount(&$data, $userId)
    {
        HelpersManager::toEloquentCollection($data, function ($data) use ($userId) {
            $managerIds = $data->pluck('manager_id')->toArray();
            $questionCountData = ProjectQuestionRepository::buildMyProjectQuestion($managerIds, $userId)
                ->selectRaw('count(*) as question_count, question_project as manager_id')
                ->groupBy('question_project')
                ->get()->pluck('question_count', 'manager_id');
            foreach ($data as $key => $project) {
                $data[$key]['question_count'] = $questionCountData->get($project['manager_id'], 0);
            }
            return $data;
        });
    }

    private static function setDocumentCount(&$data)
    {
        HelpersManager::toEloquentCollection($data, function ($data) {
            $managerIds = $data->pluck('manager_id')->toArray();
            $questionCountData = ProjectDocumentRepository::buildProjectDocument($managerIds)
                ->selectRaw('count(*) as doc_count, doc_project as manager_id')
                ->groupBy('doc_project')
                ->get()->pluck('doc_count', 'manager_id');
            foreach ($data as $key => $project) {
                $data[$key]['doc_count'] = $questionCountData->get($project['manager_id'], 0);
            }
            return $data;
        });
    }

    private static function setProjectDiscussCount(&$data, $userId)
    {
        HelpersManager::toEloquentCollection($data, function ($data) use ($userId) {
            $managerIds = $data->pluck('manager_id')->toArray();
            $questionCountData = ProjectStatusRepository::buildNotReadQuery($managerIds, $userId)
                ->selectRaw('count(*) as project_new_disscuss, relation_id as manager_id')
                ->groupBy('relation_id')
                ->get()->pluck('project_new_disscuss', 'manager_id');

            foreach ($data as $key => $project) {
                $data[$key]['project_new_disscuss'] = $questionCountData->get($project['manager_id'], 0);
            }
            return $data;
        });
    }

    // 手机端需要的数量数据
    private static function setBatchCountInfo(&$project, $userId)
    {
        $project['task_count'] = $project->tasks->count();
        $project['document_count'] = $project->documents->count();
        self::setQuestionCount($project, $userId);
        $project['discuss_count'] =  $project->discusses->count();
        $teamRoleId = RoleManager::getRoleId('team_person', $project['manager_type']);
        $project['team_count'] =  $project->teams($teamRoleId)->count();
    }

    private static function handleInputModelData(&$data, $userId)
    {
        $data['manager_state'] = empty($data['manager_state']) ? 1 : $data['manager_state'];
        if(!isset($data['manager_creater'])) {
            $data['manager_creater'] = $userId;
        }
        $data['creat_time']    = isset($data['creat_time']) ? $data['creat_time'] : date("Y-m-d H:i:s", time());
        $data['manager_fast']  = isset($data['manager_fast']) ? $data['manager_fast']:"";
        $data['manager_level'] = isset($data['manager_level']) ? $data['manager_level']:"";
        $data['sort_id']       = isset($data['sort_id']) ? $data['sort_id'] : 0;
    }

    private static function getManagerType($data)
    {
        $managerType = Arr::get($data, 'manager_type', 0);
        if (!$managerType) {
            ResponseService::throwException('0x036001', 'project');
        }
        return $managerType;
    }

    // 转化为take模型的集合
    private static function toProjectEloquentCollection(&$data)
    {
        $list = collect();
        if ($data) {
            foreach ($data as $key => $item) {
                $model = (new ProjectManagerEntity())->setRawAttributes((array) $item);
                $list->push($model);
            }
        }

        $data = $list;
    }

    // 临时函数
    public static function setProjectTypeRoleId($dataManager)
    {
        $typeRoleIds = [
            'manager' => ['type' => 'project', 'role_field_key' => 'manager_person'],
            'monitor' => ['type' => 'project', 'role_field_key' => 'manager_monitor'],
            'approval' => ['type' => 'project', 'role_field_key' => 'manager_examine'],
            'create' => ['type' => 'project', 'role_field_key' => 'manager_creater'],
            'join' => ['type' => 'project', 'role_field_key' => 'team_person'],
        ];
        $projectType = $dataManager->getApiParams('project_type', '');
        $roleParams = Arr::get($typeRoleIds, $projectType, []);
        if ($roleParams) {
            $roleIds = ProjectRoleRepository::buildQuery($roleParams)->pluck('role_id')->toArray();
            $roleIds && $dataManager->setInApiParams('role_id', $roleIds);
        }
    }

}
