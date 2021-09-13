<?php

namespace App\EofficeApp\Project\NewServices\Managers;

use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewServices\ProjectService;
use Eoffice;
use Illuminate\Support\Arr;
class MessageManager
{

    // 向非创建人的立项人 发送立项提醒
    public static function sendProjectCreatedReminder($project)
    {
        if (!$project || $project->manager_creater == $project->manager_person) {
            return ;
        }
        $sendData['remindMark'] = 'project-designing';
        $sendData['toUser'] = self::getManagerUserId($project);
        $sendData['contentParam'] = ['projectName' => Arr::get($project, 'manager_name', '')];
        $sendData['stateParams'] = ['manager_id' => Arr::get($project, 'manager_id', 0)];

        Eoffice::sendMessage($sendData);
    }

    // 检查所有明天到期的项目，并发送项目到期提醒
    public static function checkProjectExpire()
    {
        $tomorrow = date('Y-m-d', strtotime('+1day'));
        $params = ['manager_endtime' => $tomorrow, 'manager_state' => 4];
        ProjectManagerRepository::buildQuery($params)
            ->chunk(500, function ($projects) {
                foreach ($projects as $project) {
                    self::sendProjectExpireReminder($project);
                }
            });
    }


    // 向审核人 发送审核提醒
    public static function sendProjectExamineReminder($project)
    {
        $sendData['remindMark'] = 'project-examine';
        $sendData['toUser'] = self::getExamineUserId($project);
        $sendData['contentParam'] = ['projectName' => Arr::get($project, 'manager_name', '')];
        $sendData['stateParams'] = ['manager_id' => Arr::get($project, 'manager_id', 0)];

        Eoffice::sendMessage($sendData);
    }

    // 向负责人 发送退回提醒
    public static function sendProjectReturnReminder($project)
    {
        $sendData['remindMark'] = 'project-return';
        $sendData['toUser'] = self::getManagerUserId($project);
        $sendData['contentParam'] = ['projectName' => Arr::get($project, 'manager_name', '')];
        $sendData['stateParams'] = ['manager_id' => Arr::get($project, 'manager_id', 0)];

        Eoffice::sendMessage($sendData);
    }

    // 向负责人、监控人、任务执行人发送项目开始提醒
    public static function sendProjectBeginReminder($project, $taskExecutorUserIds = null)
    {
        // 获取负责人与监控人
        $manager = self::getManagerUserId($project);
        $monitor = self::getMonitorUserId($project);
        $manager = ['user_id' => $manager, 'langRoleName' => [trans('project.manager')]];
        $monitor = ['user_id' => $monitor, 'langRoleName' => [trans('project.monitor')]];
        // 获取任务执行人
        if (is_null($taskExecutorUserIds)) {
            $taskExecutorUserIds = ProjectTaskRepository::buildProjectTaskQuery($project['manager_id'])
                ->pluck('task_persondo')->toArray();
        }
        $taskExecutorUserIds = ['user_id' => $taskExecutorUserIds, 'langRoleName' => [trans('project.executor')]];
        $roles = self::groupRoles($manager, $monitor, $taskExecutorUserIds);
        // 循环发送提醒
        $managerName = Arr::get($project, 'manager_name', '');
        foreach ($roles as $projectRole => $userIds) {
            $sendData['remindMark'] = 'project-begin';
            $sendData['toUser'] = $userIds;
            $sendData['contentParam'] = [
                'projectName' => $managerName,
                'projectRole' => $projectRole,
            ]; //当前登录
            $sendData['stateParams'] = ['manager_id' => Arr::get($project, 'manager_id', 0)];

            Eoffice::sendMessage($sendData);
        }
    }

    // 向任务执行人 发送开始或任务到期提醒
    public static function checkProjectTaskBeginOrExpireRemind($type = 'begin') {
        if ($type === 'begin') {
            $remindMark = 'project-task_begin';
            $timeColumn = 'task_begintime';
            $dateKey = 'taskBeginDate';
        } else if ($type === 'expire') {
            $remindMark = 'project-task_expire';
            $timeColumn = 'task_endtime';
            $dateKey = 'taskEndDate';
        } else {
            return;
        }

        $date = date('Y-m-d', strtotime('+1day'));
        ProjectTaskRepository::buildQuery()->where($timeColumn, $date)
            ->where('task_persent', '<', 100)
            ->select('task_id', 'task_project', 'task_name', 'task_persondo')
            ->with('project_manager:manager_id,manager_name,manager_state')
            ->chunk(500, function($tasks) use ($date, $remindMark, $dateKey) {
                foreach ($tasks as $task) {
                    $managerState = Arr::get($task, 'project_manager.manager_state');
                    if ($managerState !== 4) {
                        continue;
                    }
                    $task = $task->toArray();
                    $sendData['remindMark'] = $remindMark;
                    $sendData['toUser'] = $task['task_persondo'];
                    $sendData['contentParam'] = [
                        'projectName' => Arr::get($task, 'project_manager.manager_name'),
                        'taskName' => $task['task_name'],
                        $dateKey => $date,
                    ];
                    $sendData['stateParams'] = [
                        'manager_id' => $task['task_project'],
                        'task_id' => $task['task_id'],
                    ];
                    Eoffice::sendMessage($sendData);
                }
            });
    }

    // 向项目负责人、监控人 发送任务完成提醒
    public static function sendProjectTaskCompleteReminder($task, $project = null)
    {
        $taskId = Arr::get($task, 'task_id', 0);
        $projectId = Arr::get($task, 'task_project', 0);
        if (is_null($project)) {
            $project = ProjectManagerRepository::buildQuery()->find($projectId);
        }
        if (!$task || !$project) {
            return ;
        }
        // 获取负责人与监控人
        $manager = self::getManagerUserId($project);
        $monitor = self::getMonitorUserId($project);
        $allParentPersonDos = ProjectService::getAllParentTaskPersonDos($taskId, $projectId);
        $manager = ['user_id' => $manager, 'langRoleName' => [trans('project.manager')]];
        $monitor = ['user_id' => $monitor, 'langRoleName' => [trans('project.monitor')]];
        $taskPersonDos = ['user_id' => $allParentPersonDos, 'langRoleName' => [trans('project.executor')]];
        // 非叶子节点需要提醒执行人
        if (!$task['is_leaf']) {
            $taskPersonDos['user_id'][] = $task['task_persondo'];
        }
        $roles = self::groupRoles($manager, $monitor, $taskPersonDos);

        $managerName = Arr::get($project, 'manager_name', '');
        $taskName = Arr::get($task, 'task_name', '');
        foreach ($roles as $projectRole => $userIds) {
            $sendData['remindMark'] = 'project-task_complete';
            $sendData['toUser'] = $userIds;
            $sendData['contentParam'] = [
                'projectName' => $managerName,
                'projectRole' => $projectRole,
                'taskName' => $taskName,
            ]; //当前登录
            $sendData['stateParams'] = [
                'manager_id' => $projectId,
                'task_id' => $taskId,
            ];

            Eoffice::sendMessage($sendData);
        }
    }


    // 向任务负责人 发送前置任务完成提醒
    public static function sendProjectFrontTaskCompleteReminder($frontTask)
    {
        $frontTaskId = Arr::get($frontTask, 'task_id', '');
        $tasks = ProjectTaskRepository::buildQuery(['task_frontid' => $frontTaskId, 'with_project_manager' => true])->get();
        // 获取负责人与监控人
        foreach ($tasks as $task) {
            $project = $task->project_manager;
            $userId = $task->task_persondo;
            $managerName = Arr::get($project, 'manager_name', '');
            $taskName = Arr::get($task, 'task_name', '');
            $sendData['remindMark'] = 'project-front_task_complete';
            $sendData['toUser'] = $userId;
            $sendData['contentParam'] = [
                'projectName' => $managerName,
                'taskName' => $taskName,
            ]; //当前登录
            $sendData['stateParams'] = [
                'manager_id' => Arr::get($project, 'manager_id', 0),
                'task_id' => Arr::get($task, 'task_id', 0),
            ];

            Eoffice::sendMessage($sendData);
        }
    }

    // 向任务负责人（非创建人） 发送新任务完成提醒
    public static function sendProjectNewTaskReminder($task, $curUserId, $project = null, $isAdd = true)
    {
        // 负责人与当前操作人一致 不发送提醒
        if ($task['task_persondo'] === $curUserId) {
            return ;
        }
        // 编辑时，修改了负责人才发送提醒
        if (!$isAdd) {
            if (!in_array('task_persondo', array_keys($task->getChanges()))) {
                return;
            }
        }
        if (is_null($project)) {
            $projectId = Arr::get($task, 'task_project', '');
            $project = ProjectManagerRepository::buildQuery()->find($projectId);
        }
        $managerName = Arr::get($project, 'manager_name', '');

        $sendData['remindMark'] = 'project-new_task';
        $sendData['toUser'] = $task['task_persondo'];
        $sendData['contentParam'] = [
            'projectName' => $managerName,
            'taskName' => $task['task_name'],
        ]; //当前登录
        $sendData['stateParams'] = [
            'manager_id' => Arr::get($project, 'manager_id', 0),
            'task_id' => Arr::get($task, 'task_id', 0),
        ];

        Eoffice::sendMessage($sendData);
    }

    // 向任务负责人（非创建人） 发送新任务完成提醒
    public static function sendQuestionSubmitReminder($question)
    {
        $questionPerson = OtherModuleRepository::buildUserQuery()->whereKey($question['question_person'])->select(['user_name'])->first();
        $userName = object_get($questionPerson, 'user_name', '');
        $sendData['remindMark'] = 'project-submit';
        $sendData['toUser'] = trim($question["question_doperson"], ",");
        $sendData['contentParam'] = [
            'userName' => $userName,
        ]; //当前登录
        $sendData['stateParams'] = [
            'question_id' => $question['question_id'],
            'manager_id' => $question['question_project']
        ];

        Eoffice::sendMessage($sendData);
    }

    public static function sendNewTaskDiscussReminder($project, $task)
    {
        $projectId = $project['manager_id'];
        $projectName = $project["manager_name"];
        // 提醒对象：项目团队成员、项目负责人、监控人
        $manager = self::getManagerUserId($project);
        $taskPersonDo = [$task['task_persondo']];
        $taskParentPersonDo = ProjectRoleUserRepository::buildProjectParentTaskPersonDoQuery($projectId)
            ->where('relation_id', $task['task_id'])->pluck('user_id')->toArray();
        $smsToUsers = array_merge($manager, $taskPersonDo, $taskParentPersonDo);
        if (in_array($project['manager_state'], [4, 5])) {
            $monitor = self::getMonitorUserId($project);
            $smsToUsers = array_merge($smsToUsers, $monitor);
        }

        $smsToUsers = array_unique($smsToUsers);
        $sendData['remindMark']   = 'project-taskComment';
        $sendData['toUser']       = $smsToUsers;
        $sendData['contentParam'] = ['projectName' => $projectName];
        $sendData['stateParams']  = ['task_id' => $task["task_id"], 'manager_id' => $projectId];
        Eoffice::sendMessage($sendData);
    }






    // 向负责人、监控人 发送项目到期提醒
    private static function sendProjectExpireReminder($project)
    {
        $endData = Arr::get($project, 'manager_endtime', '');
//        if (!self::isTomorrow($endData)) {
//            return;
//        }
        // 获取负责人与监控人
        $manager = self::getManagerUserId($project);
        $monitor = self::getMonitorUserId($project);
        $manager = ['user_id' => $manager, 'langRoleName' => [trans('project.manager')]];
        $monitor = ['user_id' => $monitor, 'langRoleName' => [trans('project.monitor')]];
        $roles = self::groupRoles($manager, $monitor);
        // 循环发送提醒
        $managerName = Arr::get($project, 'manager_name', '');
        foreach ($roles as $projectRole => $userIds) {
            $sendData['remindMark'] = 'project-expire';
            $sendData['toUser'] = $userIds;
            $sendData['contentParam'] = [
                'projectName' => $managerName,
                'projectRole' => $projectRole,
                'projectEndDate' => $endData,
            ]; //当前登录
            $sendData['stateParams'] = ['manager_id' => Arr::get($project, 'manager_id', 0)];

            Eoffice::sendMessage($sendData);
        }
    }


    private static function isTomorrow($testDate, $tomorrow = null)
    {
        $testDate = date('Y-m-d', strtotime($testDate));
        $tomorrow = is_null($tomorrow) ? date('Y-m-d', strtotime('+1day')) : $tomorrow;
        return $testDate === $tomorrow;
    }


    private static function getManagerUserId($project)
    {
        $managerPerson = Arr::get($project, 'manager_person');
        return $managerPerson ? explode(',', $managerPerson) : [];
    }

    private static function getExamineUserId($project): array
    {
        $monitor = Arr::get($project, 'manager_examine');
        return $monitor ? explode(',', $monitor) : [];
    }

    private static function getMonitorUserId($project): array
    {
        $monitor = Arr::get($project, 'manager_monitor');
        return $monitor ? explode(',', $monitor) : [];
    }

    // 将单独的角色人员合并到一起
    // 示例：$role = ['user_id' => [], 'langRoleName' => ['监控者'];
    // 返回示例：['监控者、执行者' => ['admin', 'somebody']]
    private static function groupRoles(...$roles)
    {
        $userRoles = [];
        foreach ($roles as $role) {
            $temp = array_fill_keys($role['user_id'], $role['langRoleName']);
            $userRoles = array_merge_recursive($userRoles, $temp);
        }
        $data = [];
        foreach ($userRoles as $key => $item) {
            $newKey = implode('、', $item);
            $data[$newKey][] = $key;
        }
        return $data;
    }
}
