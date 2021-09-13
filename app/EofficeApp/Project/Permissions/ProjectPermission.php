<?php
namespace App\EofficeApp\Project\Permissions;

use App\EofficeApp\Project\Services\ProjectService;
use Illuminate\Support\Arr;
class ProjectPermission
{
    private $projectService;
    private $discussRepository;
    private $taskRepository;
    private $taskDiaryRepository;
    private $projectQuestionRepository;

    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。
    public $rules = [
//        'getProjectTeamsDrow' => 'checkProjectView',
//        'addProjectQuestion' => 'questionView',
//        'getAppProject' => 'checkProjectViewUrlId',
//        'editProjectTaskDiary' => 'checkTaskView',//编辑任务的讨论
//        'deleteProjectTaskDiary' => 'checkTaskView',//删除任务的讨论
//        'getOneProjectTeam' => 'checkProjectView',//web端获取团队成员
//        'getTeamsAppList' => 'checkProjectView',//mobile端获取团队成员
//        'importProjectTemplates' => 'checkTaskModify',//导入项目模板任务列表，检查权限
//        'setProjectTeams' => 'checkTaskModify',//更新团队成员，与任务的修改权限一致
//        'logList' => 'checkLogViewPermission',
//        'logSearch' => 'checkLogViewPermission',
    ];
    public function __construct()
    {
        $this->projectService = 'App\EofficeApp\Project\Services\ProjectService';
        $this->discussRepository = 'App\EofficeApp\Project\Repositories\ProjectDiscussRepository';
        $this->projectManagerRepository = 'App\EofficeApp\Project\Repositories\ProjectManagerRepository';
        $this->taskRepository = 'App\EofficeApp\Project\Repositories\ProjectTaskRepository';
        $this->taskDiaryRepository = 'App\EofficeApp\Project\Repositories\ProjectTaskDiaryRepository';
        $this->projectQuestionRepository = 'App\EofficeApp\Project\Repositories\ProjectQuestionRepository';
    }
//
//    //检测项目查看权限
//    public function checkProjectView($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($data, 'manager_id');
//        return $this->canViewProject($userId, $managerId);
//    }
//
//    public function checkProjectViewUrlId($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($urlData, 'manarger_id');
//        return $this->canViewProject($userId, $managerId);
//    }
//
//    public function checkLogViewPermission($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $projectId = Arr::get($urlData, 'manager_id');
//        if ($projectId) {
//            $project = app($this->projectManagerRepository)->getDetail($projectId);
//            if ($project) {
//                return ProjectService::isManager($project, $userId) ||
//                    ProjectService::isMonitor($project, $userId) ||
//                    ProjectService::isExamine($project, $userId);
//            }
//        }
//
//        return false;
//    }
//
//    //检测项目问题查看权限
//    public function questionView($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($data, 'question_project');
//        return $this->canViewProject($userId, $managerId);
//    }
//
//    //检测项目任务的 [增加|编辑|删除] 权限
//    public function checkTaskModify($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($data, 'manager_id');
//        return $this->canModifyTask($userId, $managerId);
//    }
//
//    //更新团队成员，与任务的修改权限一致
//    public function setProjectTeams($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($data, 'team_project');
//        return $this->canModifyTask($userId, $managerId);
//    }
//
//    //检测项目任务的查看权限
//    public function checkTaskView($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($data, 'taskdiary_project');
//        return $this->canViewTask($userId, $managerId);
//    }
//
//    //获取任务的讨论列表
//    public function getProjectTaskDiaryList($own, $data, $urlData)
//    {
//        $taskId = Arr::get($urlData, 'task_id');
//        $task = app($this->taskRepository)->entity->find($taskId);
//        if (!$task) {
//            return false;
//        }
//        $managerId = $task->task_project;
//        $userId = $own['user_id'];
//        return $this->canViewTask($userId, $managerId);
//    }
//
//    //编辑任务进度,验证对任务的编辑权限
//    public function modifyProjectTaskDiaryProcess($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $taskId = Arr::get($urlData, 'taskdiary_task');
//        $managerId = Arr::get($data, 'taskdiary_project');
//        $params = $this->formatCheckParams($userId, $managerId);
//        $params['task_id'] = $taskId;
//        return app($this->projectService)->getProjectTaskDeal($params);
//    }
//
//    //删除项目问题
//    public function deleteProjectQuestion($own, $data, $urlData)
//    {
//        $questionIds = Arr::get($data, 'question_id');
//        $questionIds = explode(",", $questionIds);
//        $questionIds = array_unique(array_filter($questionIds));
//        if (!$questionIds) {
//            return false;
//        }
//        $questions = app($this->projectQuestionRepository)->entity
//            ->whereIn('question_id', $questionIds)
//            ->get();
//        if ($questions->count() != count($questionIds)) {
//            return false;
//        }
//        $userId = $own['user_id'];
//        //循环验证，未提交时创建人与提出者可删除，已解决时提出者可删除
//        foreach ($questions as $question) {
//            if ($question->question_state == 0 && !in_array($userId, [$question->question_creater, $question->question_person])) {
//                return false;
//            } else if($question->question_state == 5 && $userId != $question->question_person) {
//                return false;
//            }
//        }
//        return true;
//    }
//
//    //标记 项目或任务 的读取状态
//    public function updateProjectStatus($own, $data, $urlData)
//    {
//        $relationId = Arr::get($data, 'relation_id');
//        $type = Arr::get($data, 'type');
//        $userId = $own['user_id'];
//        if ($type == 'project') {
//            return $this->canViewProject($userId, $relationId);
//        } else if ($type == 'task') {
//            $task = app($this->taskRepository)->entity->find($relationId);
//            if (!$task) {
//                return false;
//            }
//            $managerId = $task->task_project;
//            return $this->canViewTask($userId, $managerId);
//        }
//        return false;
//    }
//
//    //项目任务：发表讨论|回复讨论
//    public function replyProjectTaskDiary($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($data, 'taskdiary_project');
//        $taskId = Arr::get($data, 'taskdiary_task');
//        $checkPermission = $this->canViewTask($userId, $managerId);
//        if ($checkPermission !== true) {
//            return false;
//        }
//        //验证任务是否存在于该项目下
//        $task = app($this->taskRepository)->entity->where('task_project', $managerId)->find($taskId);
//        if (!$task) {
//            return false;
//        }
//
//        //验证回复的讨论是否存在于该任务下，防止任意回复
//        $taskDiaryReplyId = Arr::get($data, 'task_diary_replyid');
//        if ($taskDiaryReplyId > 0) {
//            $replyTaskDiary = app($this->taskDiaryRepository)->entity->where('taskdiary_project', $managerId)
//                ->where('taskdiary_task', $taskId)
//                ->where('task_diary_replyid', 0)
//                ->find($taskDiaryReplyId);
//            return $replyTaskDiary ? true : false;
//        }
//        return true;
//    }
//
//    //发表讨论|回复讨论
//    public function replyProjectDiscuss($own, $data, $urlData)
//    {
//        $userId = $own['user_id'];
//        $managerId = Arr::get($data, 'discuss_project');
//        $checkPermission = $this->canViewProject($userId, $managerId);
//        if ($checkPermission !== true) {
//            return false;
//        }
//        //验证回复的讨论是否存在于该项目，防止任意回复
//        $discussReplyId = Arr::get($data, 'discuss_replyid');
//        if ($discussReplyId > 0) {
//            $replyDiscuss = app($this->discussRepository)->entity->where('discuss_project', $managerId)
//                ->where('discuss_replyid', 0)
//                ->find($discussReplyId);
//            return $replyDiscuss ? true : false;
//        }
//        return true;
//    }
//
//    //格式化权限查询参数
//    private function formatCheckParams($userId, $managerId)
//    {
//        return [
//            'user_id' => $userId,
//            'manager_id' => $managerId
//        ];
//    }
//
//    private function canViewProject($userId, $managerId)
//    {
//        return app($this->projectService)->getProjectCheckPrivate($this->formatCheckParams($userId, $managerId));
//    }
//
//    private function canViewTask($userId, $managerId)
//    {
//        return app($this->projectService)->getProjectTaskSelects($this->formatCheckParams($userId, $managerId));
//    }
//
//    private function canModifyTask($userId, $managerId)
//    {
//        return app($this->projectService)->getProjectTaskPrivate($this->formatCheckParams($userId, $managerId));
//    }
}
