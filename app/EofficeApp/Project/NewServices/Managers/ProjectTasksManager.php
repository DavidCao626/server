<?php

namespace App\EofficeApp\Project\NewServices\Managers;


use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\Entities\ProjectRoleUserEntity;
use App\EofficeApp\Project\Entities\ProjectTaskEntity;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProjectTasksManager
{
    protected $managerId;
    protected $managerType;
    protected $tasks;
    protected $parentTaskIds = []; // 是父任务的id，id作为key（可加快检索速度）

    // 需要更新的数据存储
    protected $updateInfoTasks = [];
    protected $updateInfoTaskRoles = [
        'insert' => [],
        'delete' => [],
    ];
    protected $updateInfoProjects = [];

    protected static $mode = 'project'; // 两种模式：project|template
    protected static $needUpdateRolesFlag = false; // 是否需要更新父级执行人的权限

    private function __construct($managerId, Collection $tasks = null)
    {
        $this->managerId = $managerId;
        $this->managerType = ProjectManagerRepository::getManagerTypeByManagerId($managerId);
        $this->tasks = is_null($tasks) ? $this->getProjectTasks() : $tasks;
        $this->tasks->keyBy('task_id');
        $this->parentTaskIds = $this->tasks->pluck('parent_task_id')->filter()->flip()->toArray();
    }

    /**
     * 转化为grid列表显示，传入前先排序
     * @param $tasks
     * @param Collection|null $newTasks 新任务顺序列表，递归使用，默认不需要传递
     * @param int $parentTaskId 默认不需要传递
     * @return Collection
     */
    public static function toGridList($tasks, Collection $newTasks = null, $parentTaskId = 0)
    {
        is_null($newTasks) && $newTasks = collect();
        $currentTasks = $tasks->where('parent_task_id', $parentTaskId);
        foreach ($currentTasks as $currentTask) {
            $newTasks->push($currentTask);
            if (!$currentTask->is_leaf) {
                self::toGridList($tasks, $newTasks, $currentTask->task_id);
            }
        }
        return $newTasks;
    }

    /**
     * 同步项目与任务的相关数据
     * @param int $managerId 项目id
     * @param Collection|null $tasks 所有任务，null时会自动查询
     */
    public static function syncProjectTasks($managerId, Collection $tasks = null)
    {
        $self = new self($managerId, $tasks);
        $projectProgress = $self->getProgressAndSyncTaskInfo();
        $self->sync($projectProgress);
    }

    // 同步数据
    private function sync($projectProgress)
    {
        // 模板模式不需要更新项目进度
        if (!$this->isTemplateMode()) {
            $projectUpdateData = [
                'manager_id' => $this->managerId,
                'progress' => $projectProgress
            ];
            array_push($this->updateInfoProjects, $projectUpdateData);
        }

        foreach ($this->tasks as $task) {
            if ($task->isDirty()) {
                $dirty = $task->getDirty();
                $updateData = ['task_id' => $task->task_id];
                $updateData = array_merge($updateData, $dirty);
                array_push($this->updateInfoTasks, $updateData);
            }
        }

        if (!$this->isTemplateMode() && self::$needUpdateRolesFlag) {
            $this->setProjectRoleUserParentTasks();
        }

        $this->storage();
    }

    // 存储
    private function storage()
    {
        if ($this->updateInfoTasks) {
            ProjectTaskEntity::updateBatch($this->updateInfoTasks);
            $this->updateInfoTasks = [];
        }
        if ($this->updateInfoProjects) {
            ProjectManagerEntity::updateBatch($this->updateInfoProjects);
            $this->updateInfoProjects = [];
        }
        if ($deleteIds = $this->updateInfoTaskRoles['delete']) {
            ProjectRoleUserRepository::buildQuery(['id' => $deleteIds])->delete();
        }
        if ($insertData = $this->updateInfoTaskRoles['insert']) {
            DatabaseManager::insertBatch(ProjectRoleUserEntity::class, $insertData, true);
        }
    }

    private function getProjectTasks()
    {
        $key = $this->isTemplateMode() ? 'task_complate' : 'task_project';
        return ProjectTaskRepository::buildQuery([$key => $this->managerId])->orderBy('sort_id')->get()->keyBy('task_id');
    }


    /**
     * 获取项目进度，并且会更新任务的进度、is_leaf、tree_level、parent_task_ids四项数据
     * @param Model $parentTask 父任务id
     * @return int
     */
    private function getProgressAndSyncTaskInfo($parentTask = null)
    {
        $parentTaskId = $parentTask ? $parentTask['task_id'] : 0;
        $parentTaskIds = $parentTask ? $parentTask['parent_task_ids'] : '';
        $parentTreeLevel = $parentTask ? $parentTask['tree_level'] : 0;
        $currentParentTaskIds = $this->getParentTaskIds($parentTaskId, $parentTaskIds);
        $currentTreeLevel = $parentTreeLevel + 1;
        $currentTaskIds = $this->tasks->where('parent_task_id', $parentTaskId)->pluck('task_id');
        // 更新任务数据
        foreach ($currentTaskIds as $taskId) {
            $currentTask = $this->tasks[$taskId];
            $currentTask['tree_level'] = $currentTreeLevel;
            $currentTask['parent_task_ids'] = $currentParentTaskIds;
            $currentTask['is_leaf'] = !array_key_exists($taskId, $this->parentTaskIds);
            if (!$currentTask['is_leaf']) {
                $currentTask['task_persent'] = $this->getProgressAndSyncTaskInfo($currentTask);
            }
            $this->tasks[$taskId] = $currentTask;
        }
        // 计算进度
        $progressTotal = 0;
        $weightsTotal = 0;
        foreach ($currentTaskIds as $currentTaskId) {
            $currentTask = $this->tasks[$currentTaskId];
            $weights = $currentTask['weights'];
            $progress = $currentTask['task_persent'];
            $weightsTotal += $weights;
            $progressTotal += $progress * $weights;
        }
        return percent_division($progressTotal, $weightsTotal);
    }

    /**
     * 获取项目的任务负责人数据
     * @param $managerId
     * @return array
     */
    private function getProjectParentTaskRoleIds($managerId)
    {
        $rolesInfo = ProjectRoleUserRepository::buildProjectParentTaskPersonDoQuery($managerId)
            ->select('relation_id', 'user_id', 'role_id', 'id')
            ->get()->toArray();
        foreach ($rolesInfo as $key => $roleInfo) {
            unset($rolesInfo[$key]);
            $newKey = self::getRoleUserUniqueKey($roleInfo['user_id'], $roleInfo['role_id'], $roleInfo['relation_id']);
            $rolesInfo[$newKey] = $roleInfo['id'];
        }
        return $rolesInfo;
    }

    private function getParentTaskIds($parentTaskId, $parentTaskIds)
    {
        if (!$parentTaskId) {
            return '';
        }
        return $parentTaskIds ? "{$parentTaskIds},{$parentTaskId}" : $parentTaskId;
    }

    // 设置是否需要更新角色数据
    public static function setMode($value = 'template')
    {
        self::$mode = $value;
    }

    private function isTemplateMode()
    {
        return self::$mode === 'template';
    }

    // 设置任务父级角色的删除与更新数据
    private function setProjectRoleUserParentTasks()
    {
        $newProjectRoleUserData = [];
        $managerState = $this->getManagerState();

        // 20200914 AddProjectRoleUserTableAndMigrationData脚本更新项目进度时关联引用，此时无project_roles表，所以会报错，传入固定id即可
        try {
            $firstRoleId = RoleManager::getRoleId('p1_task_persondo', $this->managerType);
            $firstRoleId = array_pop($firstRoleId);
            $secondRoleId = RoleManager::getRoleId('p2_task_persondo', $this->managerType);
            $secondRoleId = array_pop($secondRoleId);
        } catch (\Exception $e) {
            $firstRoleId = 12;
            $secondRoleId = 13;
        }

        $allPersonDos = $this->tasks->pluck('task_persondo', 'task_id')->toArray();
        foreach ($this->tasks as $task) {
            $parentTaskIds = $task->parent_task_ids;
            $parentTaskIds = $parentTaskIds ? explode(',', $parentTaskIds) : [];
            if ($parentTaskIds) {
                $firstParentTaskId = [array_pop($parentTaskIds)];
                self::buildProjectRoleUserData($newProjectRoleUserData, $firstParentTaskId, $allPersonDos, $managerState, $firstRoleId, $task['task_id']);// 一级
                $parentTaskIds && self::buildProjectRoleUserData($newProjectRoleUserData, $parentTaskIds, $allPersonDos, $managerState, $secondRoleId, $task['task_id']); // 多级
            }
        }
        $oldProjectRoleUserIds = self::getProjectParentTaskRoleIds($this->managerId);
        foreach ($newProjectRoleUserData as $key => $newData) {
            if (array_key_exists($key, $oldProjectRoleUserIds)) {
                unset($oldProjectRoleUserIds[$key]);
            } else {
                $this->updateInfoTaskRoles['insert'][] = $newData;
            }
        }
        $this->updateInfoTaskRoles['delete'] = $oldProjectRoleUserIds;
    }

    private function getManagerState()
    {
        $project = ProjectManagerRepository::buildQuery()->find($this->managerId);
        return $project->manager_state;
    }

    // 拼装唯一key
    private function getRoleUserUniqueKey($userId, $roleId, $taskId)
    {
        return "{$userId}_{$roleId}_{$taskId}";
    }

    // 组装新的角色数据
    private function buildProjectRoleUserData(&$data, array $parentTaskIds, &$allPersonDos, $managerState, $roleId, $taskId)
    {
        if ($parentTaskIds) {
            $personDos = array_filter(array_extract($allPersonDos, $parentTaskIds));
            foreach ($personDos as $personDo) {
                $key = $this->getRoleUserUniqueKey($personDo, $roleId, $taskId);
                $data[$key] = [
                    'role_id' => $roleId,
                    'manager_id' => $this->managerId,
                    'relation_type' => 'task',
                    'relation_id' => $taskId,
                    'manager_state' => $managerState,
                    'user_id' => $personDo,
                ];
            }
        }
    }

    // 设置是否需要更新角色数据
    public static function setNeedUpdateRolesFlag($type, array $changes = [])
    {
        if ($type === 'add' || $type === 'delete') {
            self::$needUpdateRolesFlag = true;
            return;
        }
        if ($type === 'edit' && isset($changes['task_persondo'])) {
            self::$needUpdateRolesFlag = true;
            return;
        }
        self::$needUpdateRolesFlag = false;
    }
}
