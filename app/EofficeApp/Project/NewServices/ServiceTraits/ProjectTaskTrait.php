<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\Entities\ProjectTaskEntity;
use App\EofficeApp\Project\NewRepositories\ProjectConfigRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewRepositories\ProjectStatusRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;
use App\EofficeApp\Project\NewServices\Managers\ProjectTasksManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\PermissionManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermissionManager;
use App\Utils\ResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
Trait ProjectTaskTrait
{

    public static function taskList(DataManager $dataManager, $withPercent = false)
    {
        $params = [
            '@with_task_role' => 0,
            '@with_task_read_flag' => 1,
        ];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);
        $userId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();
        $managerType = $dataManager->getProject('manager_type');

        $customTableKey = self::getProjectTaskCustomTableKey($managerType);
        $queryParams = self::handleListParams($params, false);
        Arr::set($queryParams, 'search.task_project', [$managerId]);

        $data = self::getFormModelingService()->getCustomDataLists($queryParams, $customTableKey, $dataManager->getOwn());

        // 处理无数据情况，返回的直接就是空数组，没有list、total
        $data['list'] = $data['list'] ?? [];
        $data['total'] = $data['total'] ?? 0;
        self::toTaskEloquentCollection($data['list']);

        $newList = ProjectTasksManager::toGridList($data['list']);
        if (count($newList) == $data['total']) {
            $data['list'] = $newList;
            $data['is_tree'] = 1;
        } else {
            $data['is_tree'] = 0;
        }
        $params['@with_task_role'] && self::setTaskRoles($data['list']);
        $params['@with_task_read_flag'] && self::setTaskReadFlag($data['list'], $userId);
        self::setTaskInfo($data['list'], $withPercent);
        self::setTaskIndex($data['list'], $data['is_tree']);

        return $data;
    }

    public static function mineTaskList($inputParams, $own)
    {
        $params = [
            '@with_task_role' => 0,
            '@with_task_read_flag' => 1,
        ];
        $params = array_merge($params, $inputParams);
        $isMobile = Arr::get($inputParams, 'platform') === 'mobile';

        $customTableKey = self::getProjectTaskCustomTableKey();
        $queryParams = self::handleListParams($params, false);
        $data = [];

        $myProjectIds = self::thirdMineProjectId();
        if (isset($queryParams['search']['task_project'])) {
            $searchTaskProject = $queryParams['search']['task_project'];
            $searchTaskProject = is_array($searchTaskProject) ? $searchTaskProject : [];
            $myProjectIds = array_intersect($myProjectIds, $searchTaskProject);
        }
        if ($myProjectIds) {
            Arr::set($queryParams, 'search.task_project', [$myProjectIds, 'in']);
            $data = self::getFormModelingService()->getCustomDataLists($queryParams, $customTableKey, $own);
        }
        $data['list'] = $data['list'] ?? [];
        $data['total'] = $data['total'] ?? 0;
        self::toTaskEloquentCollection($data['list']);
        $data['is_tree'] = 0;
        $params['@with_task_role'] && self::setTaskRoles($data['list']);
        $params['@with_task_read_flag'] && self::setTaskReadFlag($data['list'], $own['user_id']);
        self::setTaskInfo($data['list'], $isMobile);

        $withFPIs = Arr::get($inputParams, '@with_fpis');
        if ($withFPIs) {
            $withFPIs = explode(',', $withFPIs);
            PermissionManager::setDifferentProjectDataFunctionPages($data['list'], $own['user_id'], $withFPIs);
        }
        self::setTaskProjectInfo($data['list']);

        return $data;
    }

    public static function taskAdd(DataManager $dataManager, $parentTask = null)
    {
        $project = $dataManager->getProject();
        $curUserId = $dataManager->getCurUserId();
        $data = $dataManager->getApiParams();
        self::filterNull($data);
        return self::commonTaskAdd($data, $curUserId, $project, $parentTask);
    }


    public static function taskEdit(DataManager $dataManager)
    {
        $userId = $dataManager->getCurUserId();
        $functionPageApi = $dataManager->getFunctionPageBin()->getFunctionPageId();
        $managerId = $dataManager->getManagerId();
        $project = $dataManager->getProject();
        $relation = $dataManager->getRelations();
        $editApiBin = $dataManager->getApiBin();
        $data = $dataManager->getApiParams();
        self::filterProjectInputData($data)->checkException();

        self::filterTaskData($data)->checkException();
        $editApiBin->fillRelationData($relation, $data, $functionPageApi);
        $model = $relation->first();

        $customKey = self::getProjectTaskCustomTableKey($project['manager_type']);
        self::auxiliaryTableSave($customKey, $data, $project, $model->getKey(), 'task', 'edit', false);
        $result = $model->mineSave($userId,  $managerId, 'modify', true); // 放后面更新会一起更新角色数据

        self::syncAttachments($model, $data, 'attachments');
        ProjectStatusRepository::update($managerId, 'task', $model->getKey(), $userId);
        ProjectTasksManager::setNeedUpdateRolesFlag('edit', $model->getChanges());
        MessageManager::sendProjectNewTaskReminder($model, $userId, $project, false);
        $result && ProjectTasksManager::syncProjectTasks($managerId);

        return ['model' => $model];
    }

    public static function taskInfo(DataManager $dataManager)
    {
        $params = [
            '@with_front_task' => 1,
            '@with_task_custom_info' => 1
        ];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);

        $task = $dataManager->getRelations()->first();
        self::setTaskInfo($task);
        self::setTaskRoles($task);
        self::setAttachments($task);

        if (Arr::get($params, '@with_front_task')) {
            $task->front_task;
        }
        if (Arr::get($params, '@with_task_custom_info')) {
            self::setTaskCustomData($task, $dataManager->getProject('manager_type'));
        }
        return $task;
    }

    public static function taskDelete(DataManager $dataManager)
    {
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();
        $relations = $dataManager->getRelations();
        $taskIds = $relations->pluck('task_id')->toArray();
        $allSonTaskIds = self::getAllSonTaskIds($taskIds, $managerId);
        $deleteIds = array_merge($taskIds, $allSonTaskIds);
        $taskNames = self::getTaskNames($deleteIds);

        // 软删除，关联删除均不处理
        // 任务附件暂不删除
        // 任务处理记录（含附件）暂不删除
        // 任务作为前置任务暂不置空

        $res = DatabaseManager::deleteByIds(ProjectTaskEntity::class, $deleteIds);
        self::deleteProjectTypeRoleUser('task', $deleteIds, $managerId);
        // 记录日志
        $logManager = self::initDeleteLog('task', $curUserId, $taskNames, $managerId);
        $res && $logManager && $logManager->storageFillData();
        ProjectTasksManager::setNeedUpdateRolesFlag('delete');
        ProjectTasksManager::syncProjectTasks($managerId); // 同步项目任务数据
        CacheManager::cleanProjectReportCache(); // 删除报表缓存

        return [];
    }

    public static function ganttList(DataManager $dataManager)
    {
        $managerId = $dataManager->getManagerId();
        $tasks = ProjectTaskRepository::buildProjectTaskQuery($managerId)->orderBy('sort_id')->get();
        $tasks = ProjectTasksManager::toGridList($tasks);

        //拆解成固有格式
        $temp = [];
        $values = [];
        $destResult = [];
        $manager_name = $dataManager->getProject('manager_name');
        foreach ($tasks as $task) {
            $temp['name'] = $task["task_name"];
            $temp['name2'] = str_pad($task['task_name'], ($task['tree_level'] - 1) * 3 + strlen($task['task_name']), '　', STR_PAD_LEFT);
            $values["id"] = $task["task_id"];
            $values["from"] = $task["task_begintime"];
            $values["to"] = $task["task_endtime"];
            $values["customClass"] = self::getTaskStatus($task);
            $values["label"] = $task["task_name"];
            $values["desc"] = $task["task_name"];
            $temp['values'][0] = $values;
            array_push($destResult, $temp);
        }
        return [
            "manager_name" => $manager_name,
            "task_gantt" => $destResult,
            'colors' => array_values(self::getAllTaskStatusInfo())
        ];

    }

    public static function importProjectTemplateTask(DataManager $dataManager)
    {
        $params = $dataManager->getApiParams();
        $project = $dataManager->getProject();
        $userId = $dataManager->getCurUserId();
        $templateId = $params['template_id'];

        $result = self::taskTemplateList($templateId, ['page' => 0, '@with_custom_info' => 1]);
        // 不是树，则有问题无法处理
        if (!Arr::get($result, 'is_tree'))
            ResponseService::throwException('0x036001', 'project');
        $result = json_decode(json_encode($result['list']), true); // 存在自定义字段的集合对象，直接toArray无法将深层的对象转化为数组，会有问题
        self::commonBatchTaskAdd($result, $userId, $project, true);
        return [];
    }

    // 返回not_begin、have_in_hand、c、finished、finished_overdue五种状态，也是多语言的key
    public static function getTaskStatus($task)
    {
        $taskBeginTime = $task['task_begintime'];
        $progress = $task['task_persent'];
        $completeDate = $task['complete_date'];
        $overdueKey = $task['is_overdue'] ? '_overdue' : '';
        $now = date('Y-m-d');
        if ($now < $taskBeginTime && !$progress) {
            return 'not_begin';
        } else if (!$completeDate) {
            return 'have_in_hand' . $overdueKey;
        } else {
            return 'finished' . $overdueKey;
        }
    }

    /**
     * 获取所有任务状态信息
     */
    public static function getAllTaskStatusInfo()
    {
        return [
           'not_begin' => ['name' => trans('project.not_begin'), 'key' => 'not_begin'],
           'have_in_hand' => ['name' => trans('project.have_in_hand'), 'key' => 'have_in_hand'],
           'have_in_hand_overdue' => ['name' => trans('project.have_in_hand_overdue'), 'key' => 'have_in_hand_overdue'],
           'finished' => ['name' => trans('project.finished'), 'key' => 'finished'],
           'finished_overdue' => ['name' => trans('project.finished_overdue'), 'key' => 'finished_overdue'],
        ];
    }

    /**
     * 获取任务名称
     * @param int|array $taskIds
     * @return array id为key，name为value
     */
    public static function getTaskNames($taskIds)
    {
        $names = [];
        $taskIds && $names = ProjectTaskRepository::buildQuery(['task_id' => $taskIds])->pluck('task_name', 'task_id')->toArray();
        return $names;
    }

    /**
     * 获取所有的子任务（不限层级，不含本身）
     * @param int|array $taskIds
     * @param int $managerId
     * @return array
     */
    private static function getAllSonTaskIds($taskIds, $managerId)
    {
        $taskIds = HelpersManager::scalarToArray($taskIds);
        if (!$taskIds) {
            return [];
        }
        $query = ProjectTaskRepository::buildProjectTaskQuery($managerId);
        ProjectTaskRepository::buildQuery(['is_leaf' => 1], $query);
        $leafTaskParentTaskIds = $query->pluck('parent_task_ids', 'task_id');
        $allSonTaskIds = [];
        foreach ($leafTaskParentTaskIds as $selfTaskId => $parentTaskIds) {
            $parentTaskIds = explode(',', $parentTaskIds);
            if (!$parentTaskIds) {
                continue;
            }
            foreach ($taskIds as $taskId) {
                if (($index = array_search($taskId, $parentTaskIds)) !== false) {
                    $sonTaskIds = array_slice($parentTaskIds, $index + 1);
                    array_push($sonTaskIds, $selfTaskId); // 插入本身
                    $allSonTaskIds = array_merge($allSonTaskIds, $sonTaskIds);
                }
            }
        }

        return array_unique($allSonTaskIds);
    }

    // @throw ResponseException
    private static function commonTaskAdd($data, $userId, $project, $parentTask = null)
    {
        self::filterProjectInputData($data)->checkException();
        $managerId = $project['manager_id'];
        self::handleTaskAddData($data, $userId, $managerId);
        $model = new ProjectTaskEntity();
        isset($parentTask['task_id']) && $model->parent_task_id = $parentTask['task_id']; // 父级任务赋值，不支持批量赋值，防止绕过权限修改父级任务
        $model->fill($data);
        $model->mineSave($userId, $managerId, 'add');

        try {
            $customTableKey = self::getProjectTaskCustomTableKey($project['manager_type']);
            self::auxiliaryTableSave($customTableKey, $data, $project, $model->getKey(), 'task');
        } catch (\Exception $e) {
            $model->delete();
            throw $e;
        }

        self::syncAttachments($model, $data, 'attachments', 'add');
        MessageManager::sendProjectNewTaskReminder($model, $userId, $project);
        ProjectStatusRepository::create($managerId, 'task', $model->getKey(), $userId);

        ProjectTasksManager::setNeedUpdateRolesFlag('add');
        ProjectTasksManager::syncProjectTasks($managerId);
        return ['model' => $model];
    }

    private static function commonBatchTaskAdd($data, $userId, $project, $isImport = false)
    {
        $managerId = $project['manager_id'];
        $data = array_values($data);
        $models = [];
        foreach ($data as $key => $taskData) {
            unset($taskData['task_frontid'], $taskData['parent_task_id']);
            self::handleTaskAddData($taskData, $userId, $managerId);
            $model = new ProjectTaskEntity();
            $model->fill($taskData);
            $model->mineSave($userId, $managerId, 'add', false, false);

            $customTableKey = self::getProjectTaskCustomTableKey($project['manager_type']);
            self::auxiliaryTableSave($customTableKey, $taskData, $project, $model->getKey(), 'task', false);
            $models[$key] = $model;
            MessageManager::sendProjectNewTaskReminder($model, $userId, $project);
        }
        self::updateRelation(); // 更新角色关联
        // 添加日志
        $logManager = ProjectLogManager::getIns($userId, $managerId, true);
        foreach ($models as $model) {
            $logManager->beginFillDataModule();
            $logManager->taskAddLog($model['task_name'], $model['task_id']);
        }
        $logManager->storageFillData();


        // 导入时处理前置任务与父任务的对应关系，需要使用key来找到对应关系
        if ($isImport) {
            $updateData = [];
            $oldTaskIdNewTaskId = [];
            foreach ($data as $key => $item1) {
                $oldTaskIdNewTaskId[$item1['task_id']] = $models[$key]['task_id'];
            }
            foreach ($data as $key => $item) {
                $frontTaskIdTemp = Arr::get($item, 'task_frontid', '0');
                $parentTaskIdTemp = Arr::get($item, 'parent_task_id', '0');
                $newFrontTaskId = Arr::get($oldTaskIdNewTaskId, $frontTaskIdTemp, 0);
                $newParentTaskId = Arr::get($oldTaskIdNewTaskId, $parentTaskIdTemp, 0);
                if ($newFrontTaskId || $newParentTaskId) {
                    $tempUpdateData = [];
                    $tempUpdateData['task_id'] = $models[$key]['task_id'];
                    $newFrontTaskId && $tempUpdateData['task_frontid'] = $newFrontTaskId;
                    $newParentTaskId && $tempUpdateData['parent_task_id'] = $newParentTaskId;
                    $updateData[] = $tempUpdateData;
                }
            }
            $updateData && ProjectTaskEntity::updateBatch($updateData);
        }

        ProjectTasksManager::setNeedUpdateRolesFlag('add');
        ProjectTasksManager::syncProjectTasks($managerId); // 最后更新多级任务的关系
    }

    private static function handleTaskAddData(&$data, $userId, $managerId)
    {
        self::filterTaskData($data)->checkException();
        $data['task_project'] = $managerId;
        $data['task_creater'] = $userId;
        $data['creat_time'] = isset($data['flow_creat_time']) ? $data['flow_creat_time'] : date("Y-m-d H:i:s", time());
        $data['task_persent'] = 0;
        $data['task_complate'] = 0;
        $data['sort_id'] = $data['sort_id'] ?? 0;
        $data['task_frontid'] = $data['task_frontid'] ?? 0;
        if ($data['task_frontid']) {
            $exists = ProjectTaskRepository::buildQuery()->whereKey($data['task_frontid'])->exists();
            !$exists && ResponseService::throwException('task_front_not_exists', 'project');
        }
    }

    public static function setTaskInfo(&$data, $withPercent = false, $setFrontId = true) {

        $priorities = self::getAllProjectPriorities();
        HelpersManager::toEloquentCollection($data, function ($data) use ($priorities, $withPercent, $setFrontId) {
            $taskProgressShowModel = ProjectConfigRepository::getTaskProgressShowModel();
            foreach ($data as $item) {
                $item['task_level_name'] = Arr::get($priorities, $item['task_level'] ?? -1, '');
                $item['is_complete'] = $item['task_persent'] == 100;
                $item['task_progress_show_mode'] = $taskProgressShowModel;
                $item['task_persent_row'] = $item['task_persent'];
                if ($taskProgressShowModel == 0) {
                    $item->setAttributesWithOutFixed('task_persent', $item['is_complete'] ? trans('project.complete') : trans('project.not_complete'));
                } elseif ($withPercent) {
                    $item->setAttributesWithOutFixed('task_persent', $item['task_persent'] . '%');
                }
                // 目前前置任务可以两个任务互相关联，这里会产生递归死循环，因此使用setFrontId来阻止循环
                if ($setFrontId && $item->task_frontid && $item->front_task) {
                    self::setTaskInfo($item['front_task'], $withPercent, false);
                }

                // 处理完成时间与开始时间格式
                isset($item['start_date']) && $item['start_date'] = HelpersManager::datetimeToDate($item['start_date']);
                isset($item['complete_date']) && $item['complete_date'] = HelpersManager::datetimeToDate($item['complete_date']);
            }
            return $data;
        });
    }

    // 设置任务的自定义字段数据
    private static function setTaskCustomData(Model &$task, $managerType) {
        $customData = self::getTaskCustomInfo($managerType, $task['task_id']);
        $primaryTableKeys = array_keys($task->getAttributes());
        $customData = Arr::except($customData, $primaryTableKeys);
        $task->setRawAttributes(array_merge($task->getAttributes(), $customData));
    }

    private static function setTaskRoles(&$data) {
        self::setModelRoles($data, 'task');
        HelpersManager::toEloquentCollection($data, function ($data) {
            foreach ($data as &$item) {
                $usersInfo = Arr::get($item, 'users_info', []);
                $item['task_creater_name'] = Arr::get($usersInfo, $item['task_creater'], '');
                $item['task_persondo_name'] = Arr::get($usersInfo, $item['task_persondo'], '');
            }
            return $data;
        });
    }

    public static function getTaskCustomInfo($managerType, $taskId) {
        return self::getFormModelingService()->getCustomDataDetail(self::getProjectTaskCustomTableKey($managerType), $taskId);
    }

    /**
     * 获取任务的全部父级任务执行人用户id
     * @param $taskId
     * @param $managerId
     * @return array userIds
     */
    public static function getAllParentTaskPersonDos($taskId, $managerId)
    {
        return ProjectRoleUserRepository::buildProjectParentTaskPersonDoQuery($managerId)
            ->where('relation_id', $taskId)
            ->pluck('user_id')
            ->toArray();
    }

    // 设置未读状态
    private static function setTaskReadFlag(&$data, $userId) {
        HelpersManager::toEloquentCollection($data, function ($data) use ($userId) {
            $taskIds = $data->pluck('task_id')->toArray();
            $taskNotReadCount = ProjectStatusRepository::buildNotReadQuery($taskIds, $userId, 'task')
                ->groupBy('relation_id')
                ->selectRaw('relation_id,count(*) as count')
                ->get()
                ->pluck('count', 'relation_id');
            foreach ($data as $key =>$item) {
                $data[$key]['task_read_flag'] = $taskNotReadCount->get($item['task_id'], 0);
            }
            return $data;
        });

    }

    /**
     * @param $data
     * @return ResponseService
     */
    private static function filterProjectInputData($data)
    {
        $beginTime = Arr::get($data, 'task_begintime');
        $endTime = Arr::get($data, 'task_endtime');
        $responseService = ResponseService::getNewIns();
        if (!HelpersManager::isEmptyDate($beginTime) && !HelpersManager::isEmptyDate($endTime) && $endTime < $beginTime) {
            $responseService->setException('0x036030', 'project');
        }

        return $responseService;
    }

    // 转化为take模型的集合
    private static function toTaskEloquentCollection(&$data)
    {
        $list = collect();
        if ($data) {
            foreach ($data as $key => $item) {
                $model = (new ProjectTaskEntity())->setRawAttributes((array) $item);
                $list->push($model);
            }
        }

        $data = $list;
    }

    public static function flowOutAddTask($tasks, $project)
    {
        $projectId = $project['manager_id'];
        $saveTaskResult = [];
        foreach ($tasks as $key => $task) {
            $taskInfo = [];
            $taskInfo["task_project"]  = $projectId;
            $task['task_name'] = Arr::get($task, 'task_name', '');
            $task['task_persondo'] = Arr::get($task, 'task_persondo', '');
            $task['task_creater'] = Arr::get($task, 'task_creater', '');
            $task['task_begintime'] = Arr::get($task, 'task_begintime', '');
            $task['task_endtime'] = Arr::get($task, 'task_endtime', '');
            $testEmpty = self::buildFlowOutTaskMsg($task);
            if($testEmpty === true) {
                $taskInfo["flow_creat_time"] = Arr::get($task, 'creat_time', date("Y-m-d H:i:s"));
                $own = ['user_id' => $task['task_creater']];
                $dataManager = self::tryCatchToCode(function () use ($own, $projectId) {
                    return RolePermissionManager::getDataManager($own, 'taskAdd', 'task_add', ['task_project' => $projectId]);
                });
                if (is_array($dataManager)) {
                    $saveTaskResult[$key + 1] = $dataManager;
                } else {
                    $saveTaskResult[$key + 1] = self::tryCatchToCode(function () use ($task, $project) {
                        self::commonTaskAdd($task, $task['task_creater'], $project);
                        return true;
                    });
                }
            } else {
                $saveTaskResult[$key + 1] = $testEmpty;
            }
        }
        return $saveTaskResult;
    }

    private static function setTaskProjectInfo(&$data) {
        if ($data->isEmpty()) {
            return;
        }
        $managerIds = $data->pluck('task_project')->toArray();
        $projects = ProjectManagerRepository::buildQuery(['manager_id' => $managerIds])
            ->select('manager_name', 'manager_id')->get()->keyBy('manager_id');
        foreach ($data as $item) {
            $item['project'] = $projects->get($item['task_project']);
        }
    }

    // 外发项目，明细外发任务时，组装失败原因
    private static function buildFlowOutTaskMsg($task) {
        $msg = trans('common.0x000001');
        $empty = [];
        if (!$task['task_name']) { $empty[] = trans('outsend.from_type_160.additional.fields.task_name.field_name'); }
        if (!$task['task_persondo']) { $empty[] = trans('outsend.from_type_160.additional.fields.task_persondo.field_name'); }
        if (!$task['task_creater']) { $empty[] = trans('outsend.from_type_160.additional.fields.task_creater.field_name'); }
        if (!$task['task_begintime']) { $empty[] = trans('outsend.from_type_160.additional.fields.task_begintime.field_name'); }
        if (!$task['task_endtime']) { $empty[] = trans('outsend.from_type_160.additional.fields.task_endtime.field_name'); }
        if ($empty) {
            return $msg . ': ' . implode(',', $empty);
        } else {
            return true;
        }
    }

    // 验证任务名称，模板任务也公用此函数
    private static function filterTaskData($data)
    {
        $responseService = ResponseService::getIns();
        if (array_key_exists('task_name', $data) && emptyWithoutZero($data['task_name'], true)) {
            $responseService->setException('', '', trans('project.fields.task_name') . trans('fields.required_field'));
        }

        return $responseService;
    }

    // 设置任务序号
    private static function setTaskIndex(&$data, $isTree) {
        if ($isTree) {
            $index = [];
            foreach ($data as $item) {
                $treeLevel = $item['tree_level'] - 1;
                $index[$treeLevel] = isset($index[$treeLevel]) ? ++$index[$treeLevel] : 1;
                $index = array_slice($index, 0, $treeLevel + 1);
                $item['task_index'] = implode('.', $index);
            }
        } else {
            $i = 1;
            foreach ($data as $item) {
                $item['task_index'] = $i++;
            }
        }
    }

    public static function exportProjectTask($builder, $params)
    {
        return self::newExportHandle($builder, $params, function ($apiParams, $header, $userInfo, $builder) {
            return self::getExportProjectTaskData($apiParams, $header, $userInfo, $builder);
        });
    }

    private static function getExportProjectTaskData($params, $header, $userInfo, $builder)
    {
        if (!isset($params['order_by'])) {
            $params['order_by'] = ['sort_id' => 'asc'];
        }

        // 获取导出的任务数据
        $dataManager = RolePermissionManager::getDataManager($userInfo, 'taskList', 'task_list_export', $params);
        $managerType = $dataManager->getProject('manager_type');
        $customTableKey = self::getProjectTaskCustomTableKey($managerType);
        $queryParams = self::handleListParams($params, false);
        $managerId = $dataManager->getManagerId();
        Arr::set($queryParams, 'search.task_project', [$managerId]);
        $queryParams['module_key'] = $customTableKey;
        $appointFields = array_keys($header);
        $appointFields = array_diff($appointFields, ['system_attachment']);
        $data = self::getFormModelingService()->export($builder, $queryParams, $appointFields, function (&$newData, $oldData) {
            $newData['parent_task_id'] = object_get($oldData, 'parent_task_id', 0);
            $newData['tree_level'] = object_get($oldData, 'tree_level', 0);
            $newData['is_leaf'] = object_get($oldData, 'is_leaf', 0);
            $newData['task_id'] = object_get($oldData, 'task_id', 0);
        }, function (&$data, &$header2, &$sheetName, &$attachments) use ($header) {
            $needExportSystemAttachment = array_key_exists('system_attachment', $header);
            self::toTaskEloquentCollection($data);
            $treeData = ProjectTasksManager::toGridList($data);
            $data = count($treeData) == count($data) ? $treeData : $data;
            self::setTaskInfo($data, true);
            $needExportSystemAttachment && self::setAttachments($data);
            $data = $data->toArray();

            $header2 = $header;
            $sheetName = trans('export.projectTask');
            if ($needExportSystemAttachment) {
                $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
                $formModelingService = self::getFormModelingService();
                foreach ($data as $itemKey => $item) {
                    list($attachmentData, $oneAttachments) = $formModelingService->handleExportAttachment($attachmentService, $item['attachments'], $itemKey);
                    $attachments = array_merge($attachments, $oneAttachments);
                    $data[$itemKey]['system_attachment']= empty($attachmentData) ? ['data' => ''] : $attachmentData;
                }
            }
        });

        return $data;
    }
}
