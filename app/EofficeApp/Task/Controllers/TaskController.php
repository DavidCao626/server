<?php

namespace App\EofficeApp\Task\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Task\Services\TaskService;
use App\EofficeApp\Task\Requests\TaskRequest;

class TaskController extends Controller {

    /**
     * [$taskService service实体]
     * @var [object]
     */
    protected $taskService;

    /**
     * [$request 请求实体]
     * @var [object]
     */
    protected $request;

    /**
     * [$taskRequest 表单验证实体]
     *
     * @var [object]
     */
    protected $taskRequest;

    public function __construct(
    TaskService $taskService, TaskRequest $taskRequest, Request $request
    ) {
        parent::__construct();

        $this->taskService = $taskService;
        $this->request = $request;
        $this->taskRequest = $taskRequest;

        $this->formFilter($request, $taskRequest);
    }

    /**
     * [createTask 创建任务]
     *
     * @method 朱从玺
     *
     * @return [json]     [创建结果]
     */
    public function createTask() {
        $taskData = $this->request->all();

        $result = $this->taskService->createTask($taskData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [completeTask 标注任务状态,完成/未完成]
     *
     * @method 朱从玺
     *
     * @return [json]       [标注结果]
     */
    public function completeTask() {
        $completeData = $this->request->input('taskIds');

        $result = $this->taskService->completeTask($completeData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [lockTask 锁定/解锁任务]
     *
     * @method 朱从玺
     *
     * @return [json]   [操作结果]
     */
    public function lockTask() {
        $lockId = $this->request->input('taskIds');

        $result = $this->taskService->lockTask($lockId, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [followTask 关注/取消关注]
     *
     * @method 朱从玺
     *
     * @return [string]     [操作结果]
     */
    public function followTask() {
        $followData = $this->request->input('taskIds');

        $result = $this->taskService->followTask($followData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [modifyTask 编辑任务]
     *
     * @method 朱从玺
     *
     * @param  [array]     $taskId [任务ID]
     *
     * @return [json]              [编辑结果]
     */
    public function modifyTask($taskId) {
        $newTaskData = $this->request->all();

        $result = $this->taskService->modifyTask($taskId, $newTaskData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [modifyTaskManager 编辑任务的负责人]
     *
     * @method 朱从玺
     *
     * @param  [int]             $taskId [任务ID]
     *
     * @return [json]                    [编辑结果]
     */
    public function modifyTaskManager($taskId) {
        $manager = $this->request->input('manage_user');

        $result = $this->taskService->modifyTaskManager($taskId, $manager, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [createJoiner 添加参与人]
     *
     * @method 朱从玺
     *
     * @return [json]         [添加结果]
     */
    public function createJoiner() {
        $createData = $this->request->all();

        $result = $this->taskService->createJoiner($createData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [createShared 添加共享人]
     *
     * @method 朱从玺
     *
     * @return [json]       [添加结果]
     */
    public function createShared() {
        $createData = $this->request->all();

        $result = $this->taskService->createShared($createData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [createTaskFeedback 添加反馈]
     *
     * @method 朱从玺
     *
     * @return [json]             [添加结果]
     */
    public function createTaskFeedback() {
        $feedbackData = $this->request->all();

        $result = $this->taskService->createTaskFeedback($feedbackData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [modifyTaskFeedback 编辑反馈]
     *
     * @method 朱从玺
     *
     * @param  [int]                $feedbackId   [反馈ID]
     *
     * @return [bool]                             [编辑结果]
     */
    public function modifyTaskFeedback($feedbackId) {
        $feedbackData = $this->request->all();

        $result = $this->taskService->modifyTaskFeedback($feedbackId, $feedbackData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [deleteTaskFeedback 删除反馈]
     *
     * @method 朱从玺
     *
     * @param  [int]              $feedbackId [反馈ID]
     *
     * @return [bool]                         [删除结果]
     */
    public function deleteTaskFeedback($feedbackId) {
        $result = $this->taskService->deleteTaskFeedback($feedbackId, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [getTaskInfo 获取任务数据]
     *
     * @method 朱从玺
     *
     * @param  [int]       $taskId [任务ID]
     *
     * @return [json]              [查询结果]
     */
    public function getTaskInfo($taskId) {
        $result = $this->taskService->getTaskInfo($taskId, $this->own['user_id'], $this->request->all());

        return $this->returnResult($result);
    }

    /**
     * [getTaskRelationUser 获取任务关联用户]
     *
     * @method 朱从玺
     *
     * @param  [int]               $taskId [任务ID]
     *
     * @return [json]                      [查询结果]
     */
    public function getTaskRelationUser($taskId) {
        $relationType = $this->request->input('relation_type');

        $relationUser = $this->taskService->getTaskRelationUser($taskId, $relationType, $this->own['user_id']);

        return $this->returnResult($relationUser);
    }

    /**
     * [getTaskFeedback 获取任务反馈]
     *
     * @method 朱从玺
     *
     * @param  [int]           $taskId [任务ID]
     *
     * @return [json]                  [查询结果]
     */
    public function getTaskFeedback($taskId) {
        $params = $this->request->all();

        $taskFeedback = $this->taskService->getTaskFeedback($taskId, $params, $this->own['user_id']);

        return $this->returnResult($taskFeedback);
    }

    /**
     * [getFeedbackInfo 获取某条任务反馈数据]
     *
     * @method 朱从玺
     *
     * @param  [int]           $feedbackId [反馈ID]
     *
     * @return [json]                      [查询结果]
     */
    public function getFeedbackInfo($feedbackId) {
        $feedbackInfo = $this->taskService->getFeedbackInfo($feedbackId);

        return $this->returnResult($feedbackInfo);
    }

    /**
     * [getTaskList 获取任务列表]
     *
     * @method 朱从玺
     *
     * @return [object]      [查询结果]
     */
    public function getTaskList() {
        $param = $this->request->all();

        $result = $this->taskService->getTaskList($param, $this->own['user_id']);

        return $this->returnResult($result);
    }

    public function getSonTask($taskId){
        $result = $this->taskService->getSonTask($taskId);

        return $this->returnResult($result);
    }
    //门户
    public function taskPortal() {
        $result = $this->taskService->taskPortal($this->request->all(), $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [deleteTask 删除任务]
     *
     * @method 朱从玺
     *
     * @param  [int]      $taskId [任务ID]
     *
     * @return [json]             [删除结果]
     */
    public function deleteTask($taskId) {
        $result = $this->taskService->deleteTask($taskId, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [getDeletedTask 获取已被删除的任务列表]
     *
     * @method 朱从玺
     *
     * @return [json]         [查询结果]
     */
    public function getDeletedTask() {
        $param = $this->request->all();

        $deletedTask = $this->taskService->getDeletedTask($param);

        return $this->returnResult($deletedTask);
    }

    public function recovery() {
        $param = $this->request->all();

        $deletedTask = $this->taskService->recovery($param);

        return $this->returnResult($deletedTask);
    }

    public function forceDelete() {
        $param = $this->request->all();
        $deletedTask = $this->taskService->forceDelete($param);

        return $this->returnResult($deletedTask);
    }

    /**
     * [restoreTask 还原任务]
     *
     * @method 朱从玺
     *
     * @return [bool]              [还原结果]
     */
    public function restoreTask() {
        $taskIds = $this->request->input('task_id');

        $result = $this->taskService->restoreTask($taskIds, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [getTaskRecoverLog 获取回收站日志]
     *
     * @method 朱从玺
     *
     * @return [json]         [查询结果]
     */
    public function getTaskRecoverLog() {
        $param = $this->request->all();

        $logList = $this->taskService->getTaskRecoverLog($param, $this->own['user_id']);

        return $this->returnResult($logList);
    }

    /**
     * [taskRecoverSearch 回收站日志自动查询]
     *
     * @method taskRecoverSearch
     *
     * @return [json]            [查询结果]
     */
    public function taskRecoverSearch() {
        $param = $this->request->all();

        $taskList = $this->taskService->taskRecoverSearch($param, $this->own['user_id']);

        return $this->returnResult($taskList);
    }

    /**
     * [pressTask 催办任务]
     *
     * @method 朱从玺
     *
     * @return [json]    [催办结果]
     */
    public function pressTask() {
        $param = $this->request->all();

        $result = $this->taskService->pressTask($param, $this->own['user_id'], $this->own['user_name']);

        return $this->returnResult($result);
    }

    /**
     * [getTaskLog 获取任务日志]
     *
     * @method 朱从玺
     *
     * @param  [int]      $taskId [任务ID]
     *
     * @return [json]             [日志数据]
     */
    public function getTaskLog($taskId) {
        $param = $this->request->all();

        $taskLog = $this->taskService->getTaskLog($taskId, $param, $this->own['user_id']);

        return $this->returnResult($taskLog);
    }

    /**
     * [getTaskReport 获取任务分析报告]
     *
     * @method 朱从玺
     *
     * @return [json]        [查询结果]
     */
    public function getTaskReport() {
        $param = $this->request->all();

        $report = $this->taskService->getTaskReport($param);

        return $this->returnResult($report);
    }

    /**
     * [getOneUserTask 获取任务分析列表相关任务详情]
     *
     * @method 朱从玺
     *
     * @return [json]         [查询结果]
     */
    public function getOneUserTask() {
        $param = $this->request->all();

        $result = $this->taskService->getOneUserTask($param);

        return $this->returnResult($result);
    }
    public function getSimpleOneUserTask()
    {
        return $this->returnResult($this->taskService->getSimpleOneUserTask($this->request->all()));
    }
    /**
     * [getUserTaskTrend 获取用户任务趋势图表数据]
     *
     * @method 朱从玺
     *
     * @return [json]           [查询结果]
     */
    public function getUserTaskTrend() {
        $param = $this->request->all();

        $result = $this->taskService->getUserTaskTrend($param, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [getSubordinateTaskList 获取用户下属任务列表]
     *
     * @method 朱从玺
     *
     * @return [json]                 [查询结果]
     */
    public function getSubordinateTaskList() {
        $param = $this->request->all();

        $taskList = $this->taskService->getSubordinateTaskList($param, $this->own['user_id']);

        return $this->returnResult($taskList);
    }

    /**
     * [getUserSubordinate 获取用户下级,用于中间列]
     *
     * @method 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @return [array]                      [查询结果]
     */
    public function getUserSubordinate($userId) {
        $result = $this->taskService->getUserSubordinate($userId,$this->request->all());

        return $this->returnResult($result);
    }

    /**
     * [mobileCreateTask 手机版创建任务]
     *
     * @method 朱从玺
     *
     * @return [object]           [创建结果]
     */
    public function mobileCreateTask() {
        $taskInfo = $this->request->all();

        $result = $this->taskService->mobileCreateTask($taskInfo, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [mobileEditTask 手机版编辑任务]
     *
     * @method 朱从玺
     *
     * @return [bool]         [编辑结果]
     */
    public function mobileEditTask($taskId) {
        $taskInfo = $this->request->all();

        $result = $this->taskService->mobileEditTask($taskId, $taskInfo, $this->own['user_id']);

        return $this->returnResult($result);
    }

    //测试
    public function searchTest() {
        $result = $this->taskService->getUserTaskList();

        return $this->returnResult($result);
    }

    /**
     * 新版任务API
     *
     * 主要功能:
     * 		任务类别新建编辑删除等操作;
     * 		在存在任务类别情况下的任务新建,列表获取,排序等;
     *
     * 老版API手机版在用,暂时保留,以后看情况整合
     */

    /**
     * [createTaskClass 创建任务类别]
     *
     * @method 朱从玺
     *
     * @return [bool]          [创建结果]
     */
    public function createTaskClass() {
        $classData = $this->request->all();

        $result = $this->taskService->createTaskClass($classData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [deleteTaskClass 删除任务类别]
     *
     * @method 朱从玺
     *
     * @param  [int]           $classId [类别ID]
     *
     * @return [bool]                   [删除结果]
     */
    public function deleteTaskClass($classId) {
        $result = $this->taskService->deleteTaskClass($classId, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [modifyTaskClass 编辑任务类别]
     *
     * @method 朱从玺
     *
     * @param  [int]           $classId [类别ID]
     *
     * @return [bool]                   [编辑结果]
     */
    public function modifyTaskClass($classId) {
        $classData = $this->request->all();

        $result = $this->taskService->modifyTaskClass($classId, $classData);

        return $this->returnResult($result);
    }

    /**
     * [getMyTaskList 获取我的任务列表]
     *
     * @method 朱从玺
     *
     * @return [object]        [查询结果]
     */
    public function getMyTaskList() {
        $params = $this->request->all();

        $result = $this->taskService->getMyTaskList($params, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [modifyClassSort 任务分类排序]
     *
     * @method 朱从玺
     *
     * @return [bool]          [排序结果]
     */
    public function modifyClassSort() {
        $taskList = $this->request->all();

        $result = $this->taskService->modifyClassSort($taskList, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [modifyTaskClassRelation 更新任务与任务分类关联]
     *
     * @method 朱从玺
     *
     * @return [bool]                  [更新结果]
     */
    public function modifyTaskClassRelation() {
        $taskList = $this->request->all();

        $result = $this->taskService->modifyTaskClassRelation($taskList, $this->own['user_id']);

        return $this->returnResult($result);
    }

    //
    public function quickModifyTaskDetail($taskId) {

        $result = $this->taskService->quickModifyTaskDetail($taskId, $this->request->all(), $this->own['user_id']);

        return $this->returnResult($result);
    }

    public function taskAuth($task_id) {
        $result = $this->taskService->taskAuth($task_id, $this->own['user_id'], $this->request->all());

        return $this->returnResult($result);
    }

    public function setTaskGrade($taskId) {
        $result = $this->taskService->setTaskGrade($taskId, $this->own['user_id'], $this->request->all());
        return $this->returnResult($result);
    }

    public function taskScheduleList() {
        $result = $this->taskService->taskScheduleList($this->own['user_id'], $this->request->all());
        return $this->returnResult($result);
    }

    public function getTaskSchedule($taskId) {
        $result = $this->taskService->getTaskSchedule($taskId, $this->request->all());
        return $this->returnResult($result);
    }

    //获取某个月 有日程的日期
    public function getTaskScheduleByDate() {
        $result = $this->taskService->getTaskScheduleByDate($this->own['user_id'], $this->request->all());
        return $this->returnResult($result);
    }

    //获取人物提醒方式（含未设置）
    public function getTaskReminds($taskId) {
        $result = $this->taskService->getTaskReminds($taskId);
        return $this->returnResult($result);
    }

    public function remindSet($taskId) {
        $result = $this->taskService->remindSet($taskId, $this->request->all());
        return $this->returnResult($result);
    }

    public function getRemindSet($taskId) {
        $result = $this->taskService->getRemindSet($taskId, $this->request->all());
        return $this->returnResult($result);
    }

    public function taskMineClass() {
        $result = $this->taskService->taskMineClass($this->own['user_id'], $this->request->all());
        return $this->returnResult($result);
    }

    public function taskMineClassTaskList($classId) {
        $result = $this->taskService->taskMineClassTaskList($this->own['user_id'], $classId, $this->request->all());
        return $this->returnResult($result);
    }

    public function getMyTask() {
        $result = $this->taskService->getMyTask($this->request->all());
        return $this->returnResult($result);
    }

    public function getMyTaskType() {
        $list = [
            ['field_value' => 'manage', 'field_name' => trans('task.im_in_charge')],
            ['field_value' => 'join', 'field_name' => trans('task.i_am_involved')],
            ['field_value' => 'shared', 'field_name' => trans('task.share_it_to_me')],
            ['field_value' => 'follow', 'field_name' => trans('task.im_concerned')],
        ];
        return $this->returnResult(['list' => $list]);
    }
}
