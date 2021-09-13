<?php
namespace App\EofficeApp\Task\Permissions;
use Illuminate\Support\Arr;
class TaskPermission
{
    private $taskUserRepository;
    private $taskManageRepository;
    private $taskClassRepository;
    private $taskFeedbackRepository;
    private $taskService;
    private $calendarService;

    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。
    public $rules = [
        'getRemindSet' => 'taskRemind',
        'remindSet' => 'taskRemind',
        'getTaskReminds' => 'taskRemind',
        'getTaskLog' => 'getTaskInfo',
        'recovery' => 'recyclePermission',
        'forceDelete' => 'recyclePermission',
        'getDeletedTask' => 'recyclePermission',
    ];
    public function __construct() 
    {
        $this->taskUserRepository = 'App\EofficeApp\Task\Repositories\TaskUserRepository';
        $this->taskManageRepository = 'App\EofficeApp\Task\Repositories\TaskManageRepository';
        $this->taskClassRepository = 'App\EofficeApp\Task\Repositories\TaskClassRepository';
        $this->taskFeedbackRepository = 'App\EofficeApp\Task\Repositories\TaskFeedbackRepository';
        $this->taskService = 'App\EofficeApp\Task\Services\TaskService';
        $this->calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';
    }

    public function createTask($own, $data, $urlData)
    {
        $parentId = Arr::get($data, 'parent_id');
        //parentId不存在时表示添加任务，仅拥有我的任务(531)菜单的用户才能添加主任务
        if (!$parentId) {
            if (!in_array(531, $own['menus']['menu'])) {
                return false;
            }
        }
        return true;
    }

    //验证class_id是否是自己的
    public function taskMineClassTaskList($own, $data, $urlData)
    {
        $classId = $urlData['classId'];
        if ($classId) {
            return app($this->taskClassRepository)->entity
                ->where('id', $classId)
                ->where('user_id', $own['user_id'])
                ->exists();
        }
        return true;
    }

    //编辑分类，限制编辑自己的分类
    public function modifyTaskClass($own, $data, $urlData)
    {
        $classParams = [
            'search'   => ['user_id' => [$own['user_id']]],
        ];
        $classList = app($this->taskClassRepository)->getTaskClassList($classParams)->toArray();
        $classIds = array_column($classList, 'id');
        $classId = Arr::get($data, 'class_id');
        if ($classId && in_array($classId, $classIds)) {
            return true;
        }
        return array('code' => array('0x046038', 'task'));
    }

    //任务提醒权限过滤，仅负责人
    public function taskRemind($own, $data, $urlData)
    {
        $taskId = Arr::get($urlData, 'taskId');
        $task = app($this->taskManageRepository)->entity->find($taskId);
        if (!$task || $task->manage_user != $own['user_id']) {
            return false;
        }

        return true;
    }

    //添加评论接口
    public function createTaskFeedback($own, $data, $urlData)
    {
        return $this->taskSharedPermission(Arr::get($data, 'task_id'), $own['user_id'], $own);
    }

    //获取任务信息
    public function getTaskInfo($own, $data, $urlData)
    {
        // 回收站可查看任意任务
        if (in_array(536, $own['menus']['menu'])) {
            return true;
        }
        return $this->taskSharedPermission($urlData['taskId'], $own['user_id'], $own);
    }

    //获取任务信息
    public function getTaskFeedback($own, $data, $urlData)
    {
        if (in_array(536, $own['menus']['menu'])) {
            return true;
        }
        return $this->taskSharedPermission($urlData['taskId'], $own['user_id'], $own);
    }

    //获取子任务列表
    public function getSonTask($own, $data, $urlData)
    {
        return $this->taskSharedPermission($urlData['taskId'], $own['user_id'], $own);
    }

    //评分
    public function setTaskGrade($own, $data, $urlData)
    {
        return $this->taskSharedPermission($urlData['taskId'], $own['user_id'], $own);
    }

    public function getTaskSchedule($own, $data, $urlData)
    {
        $taskId = Arr::get($urlData, 'taskId');
        $userId = $own['user_id'];
        $res = $this->taskManagerPermission($userId, $taskId);
        $res = $res || $this->taskUserPermission($userId, $taskId, ['join', 'shared']);
        return $res;
    }

    //web端获取某个用户的任务列表
    public function getSimpleOneUserTask($own, $data, $urlData)
    {
        //只有任务分析菜单权限时，只能查看自己的任务列表
        if (!$this->hasReportMenu($own)) {
            $userId = Arr::get($data, 'user_id');
            if ($userId != $own['user_id']) {
                return false;
            }
        }
        return true;
    }

    //关注|取消关注任务
    public function followTask($own, $data, $urlData)
    {
        $taskId = Arr::get($data, 'taskIds');
        return $this->taskSharedPermission($taskId, $own['user_id'], $own);
    }

    public function recyclePermission($own, $data, $urlData)
    {
        if (in_array(536, $own['menus']['menu'])) {
            return true;
        }
        return false;
    }

    //验证分享者权限，含下级用户的权限
    private function taskSharedPermission($taskIds, $userId, &$own)
    {
        //拥有任务报表权限将拥有所有人的任务查看权限
        if ($this->hasReportMenu($own)) {
            return true;
        }
        if (!$taskIds) {
            return false;
        }
        if (!is_array($taskIds)) {
            $taskIds = [$taskIds];
        }
        $taskService = app($this->taskService);
        //获取有分享权限以上的所有任务ID数组
        $taskArray = $taskService->getPowerTaskArray($userId, 'shared');
        $taskIds = array_diff($taskIds, $taskArray);
        if (!$taskIds) {
            return true;
        }

        //有权限任务的子任务
        $sonTaskArray = $taskService->getSonTask($taskArray);
        $taskIds = array_diff($taskIds, $sonTaskArray);
        if (!$taskIds) {
            return true;
        }

        if ($this->hasSubordinateMenu($own)) {
            //下属的任务
            $subordinateTask = $taskService->getSubordinateTask($userId);
            $taskIds = array_diff($taskIds, $subordinateTask);
            if (!$taskIds) {
                return true;
            }

            //下属任务的子任务
            $subordinateSonTask = $taskService->getSonTask($subordinateTask);
            $taskIds = array_diff($taskIds, $subordinateSonTask);
            if (!$taskIds) {
                return true;
            }
        }

        return false;
    }

    /**
     * 验证task_user表的用户权限
     * @param $userId
     * @param $taskId
     * @param array $types 类型：['join', 'shared', 'follow']
     * @return mixed
     */
    private function taskUserPermission($userId, $taskId, array $types): bool
    {
        $where = [
            "task_relation" => [$types, "in"],
            "user_id"       => [$userId, 'in'],
            "task_id" => [$taskId]
        ];
        if (is_array($userId)) {
            $where['user_id'] = [$userId, 'in'];
        } else {
            $where['user_id'] = [$userId];
        }

        return app($this->taskUserRepository)->entity->wheres($where)->exists();
    }

    //验证用户是否是负责人
    private function taskManagerPermission($userId, $taskId): bool
    {
        $query = app($this->taskManageRepository)->entity->newQuery();
        $query->where('id', $taskId);
        if (is_array($userId)) {
            $query->whereIn('manage_user', $userId);
        } else {
            $query->where('manage_user', $userId);
        }

        return $query->exists();
    }

    private function hasReportMenu(&$own)
    {
        if (in_array(532, $own['menus']['menu'])) {
            return true;
        }
        return false;
    }

    private function hasSubordinateMenu(&$own)
    {
        if (in_array(534, $own['menus']['menu'])) {
            return true;
        }
        return false;
    }
}
