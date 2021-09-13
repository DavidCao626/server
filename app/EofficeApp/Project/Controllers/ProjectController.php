<?php

namespace App\EofficeApp\Project\Controllers;

use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermissionManager;
use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Project\Requests\ProjectRequest;
use App\EofficeApp\Project\Services\ProjectService;
use App\EofficeApp\Project\NewServices\ProjectService as NewProjectService;
use Illuminate\Support\Arr;
/**
 * 项目控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectController extends Controller {

    use ProjectControllerV2Trait; // Todo 后续完成后处理
    private $dataManager;
    public function __construct(
    Request $request, ProjectService $projectService, ProjectRequest $projectRequest//表单验证
    ) {
        parent::__construct();
        $this->projectService = $projectService;
        $this->projectRequest = $request; //使用接收参数
        $this->formFilter($request, $projectRequest);
        $this->init(); // 初始化了权限配置、主要数据对象，验证了权限
    }

    /**
     * 设置监控用户
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function setProjectMonitor() {
        $result = $this->projectService->setProjectMonitor($this->projectRequest->all());
        return $this->returnResult($result);
    }

    //获取模板 不带分页
    public function getAllTemplate() {
        $result = $this->projectService->getAllTemplate($this->projectRequest->all());
        return $this->returnResult($result);
    }

    //获取甘特图信息
    public function getProjectGantt() {
        $result = $this->projectService->getProjectGantt($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    public function mineTaskList() {
        $result = NewProjectService::mineTaskList($this->projectRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取监控用户
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectMonitor() {
        $result = $this->projectService->getProjectMonitor($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 设置用户审批权限
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function setProjectExamine() {
        $result = $this->projectService->setProjectExamine($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 我的项目 门户列表
     */
    public function getProjectListIndex($user_id) {
        $user_id = $this->own['user_id'];
        $result = $this->projectService->getProjectListIndex($user_id, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 我的项目 放到编辑器中
     */
    public function getProjectSystemData($user_id) {
        $user_id = $this->own['user_id'];
        $result = $this->projectService->getProjectSystemData($user_id, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户审批权限
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectExamineByUserId() {
        $result = $this->projectService->getProjectExamineByUserId($this->projectRequest->all());
        return $this->returnResult($result);
    }

    public function getProjectCreateTeams() {
        $result = $this->projectService->getProjectCreateTeams($this->projectRequest->all());
        return $this->returnResult($result);
    }

    //getPersonDo

    public function getPersonDo($userId) {
        $userId = $this->own['user_id'];
        $result = $this->projectService->getPersonDo($userId);
        return $this->returnResult($result);
    }

    /**
     * 获取有项目审核的用户
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectExamineUsers() { //FIND_IN_SET(  "51", exam_manager )
        $result = $this->projectService->getProjectExamineUsers($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 增加项目类型
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectType() { // == 创建新增字段表
        $result = $this->projectService->addProjectType($this->projectRequest->all());

        return $this->returnResult($result);
    }

    //获取前置任务 -- 模板、项目

    public function getTemplateFrontTask($id) {
        $params['order_by'] = ['sort_id' => 'asc'];
        $result = NewProjectService::taskTemplateList($id, $params);
        $exceptTaskId = $this->projectRequest->input('except_task_id', '');
        // 格式化任务名称
        foreach ($result['list'] as $key => &$item) {
            if ($item['task_id'] == $exceptTaskId) {
                unset($result['list'][$key]);
                continue;
            }
            $item['task_name'] = str_pad($item['task_name'], ($item['tree_level'] - 1) * 3 + strlen($item['task_name']), '　', STR_PAD_LEFT);
        }
        $result['list'] = $result['list']->values();

        return $this->returnResult($result);
    }

    /**
     * 编辑项目类型
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectType() {
        $result = $this->projectService->editProjectType($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 删除项目类型
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectType() {
        //== 删除新增字段表 drop table
        //项目时，则不能删除项目类型
        $result = $this->projectService->deleteProjectType($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取全部项目类型
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getAllProjectType() {
        $result = $this->projectService->getAllProjectType($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取一个项目详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectType($id) {
        $result = $this->projectService->getOneProjectType($id);
        return $this->returnResult($result);
    }

    public function getMaxOrderByType($type) {
        $result = $this->projectService->getMaxOrderByType($type);
        return $this->returnResult($result);
    }

    /**
     * 增加项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectRole() {
        $result = $this->projectService->addProjectRole($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectRole($role_id) {
        $result = $this->projectService->editProjectRole($this->projectRequest->all(), $role_id);
        return $this->returnResult($result);
    }

    /**
     * 删除项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectRole($role_id) {
        //项目时，则不能删除项目角色。
        $result = $this->projectService->deleteProjectRole($this->projectRequest->all(), $role_id);
        return $this->returnResult($result);
    }

    /**
     * 获取全部项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getAllProjectRole() {
        $result = $this->projectService->getAllProjectRole($this->projectRequest->all());
        return $this->returnResult($result);
    }

    public function getOneProjectRole($role_id) {
        $result = $this->projectService->getOneProjectRole($role_id);
        return $this->returnResult($result);
    }

    /**
     * 增加项目模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectTemplate() {
        $result = $this->projectService->addProjectTemplate($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 编辑项目模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectTemplate($templateId) {

        $result = $this->projectService->editProjectTemplate($this->projectRequest->all(), $templateId);
        return $this->returnResult($result);
    }

    /**
     * 删除项目模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectTemplate($templateId) {
        //项目时，则不能删除项目角色。
        $result = $this->projectService->deleteProjectTemplate($templateId);
        return $this->returnResult($result);
    }

    /**
     * 获取全部项目模板（附带任务名称）
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getAllProjectTemplate() {
        $result = $this->projectService->getAllProjectTemplate($this->projectRequest->all());
        return $this->returnResult($result);
    }

    //获取一个模板
    public function getOneProjectTemplate($templateId) {
        $result = $this->projectService->getOneProjectTemplate($templateId);
        return $this->returnResult($result);
    }

    //任务 --
    /**
     * 增加项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectTask() {
        $result = $this->projectService->addProjectTask($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 编辑项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectTask($task_id) {
        $result = $this->projectService->editProjectTask($this->getAllInputAndCurUserId(), $task_id);
        return $this->returnResult($result);
    }

    /**
     * 删除项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectTask() {
        //项目时，则不能删除项目角色。
        $result = $this->projectService->deleteProjectTask($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 增加项目模板任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectTemplateTask() {
        $result = NewProjectService::taskTemplateAdd($this->getAllInputAndCurUserId(), $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 编辑项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectTemplateTask($taskId) {
        $result = NewProjectService::taskTemplateEdit($taskId, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 删除模板任务
     * //任务跟项目无关
     * @return
     */
    public function deleteProjectTemplateTask() {
        //项目时，则不能删除项目角色。
        $result = NewProjectService::taskTemplateDelete($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 获取任务详细(by task_id)
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectTask() {
        $result = $this->projectService->getOneProjectTask($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 获取任务详细(by task_id)
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectTemplateTask() {
        $result = NewProjectService::taskTemplateInfo($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 获取任务列表（by temp_id）
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectTaskListbyTemplateId($templateId) {
        $result = NewProjectService::taskTemplateList($templateId, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 获取任务列表（by project_id  [ manager_id  ]）
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectTaskListbyProjectId($manager_id) {
        $result = $this->projectService->getProjectTaskListbyProjectId($this->getAllInputAndCurUserId(), $manager_id);
        return $this->returnResult($result);
    }

    // 任务处理 --
    /**
     * 增加项目任务处理
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectTaskDiary() {
        $result = $this->projectService->addProjectTaskDiary($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    public function editProjectTaskDiary($taskDiaryId) {
        $result = $this->projectService->editProjectTaskDiary($taskDiaryId, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    //111

    public function replyProjectTaskDiary() {
        $result = $this->projectService->replyProjectTaskDiary($this->projectRequest->all());
        return $this->returnResult($result);
    }

    public function getOneProjectTaskDiary($taskdiary_id) {
        $result = $this->projectService->getOneProjectTaskDiary($taskdiary_id);
        return $this->returnResult($result);
    }

    public function getProjectTaskDiaryList($taskId) {
        $result = $this->projectService->getProjectTaskDiaryList($this->projectRequest->all(), $taskId, $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 删除项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectTaskDiary($taskDiaryId) {
        $result = $this->projectService->deleteProjectTaskDiary($taskDiaryId,$this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 新建项目
     * 项目字段可参考表单建模项目的字段列表
     * @apiTitle 新建项目
     * @param {string}  manager_name 必填，项目名称
     * @param {string}  manager_begintime 必填，开始时间
     * @param {string}  manager_endtime 必填，结束时间
     * @param {string}  manager_person 必填，项目负责人，多个以逗号间隔
     * @param {string}  manager_type 必填，项目类型，参考项目分类设置
     * @param {string}  manager_number 可选，项目编号
     * @param {string}  manager_fast 可选，紧急程度
     * @param {string}  manager_level 可选，优先级别
     * @param {string}  manager_examine 可选，项目审核人，多个以逗号间隔
     * @param {string}  manager_monitor 可选，项目监控人，多个以逗号间隔
     * @param {string}  team_person 可选，项目团队成员，多个以逗号间隔
     * @param {string}  manager_explain 可选，项目描述
     * @param {string}  manager_creater 可选，默认当前操作人
     * @param {string}  creat_time 可选，默认当前时间
     * @param {string}  manager_state 可选，默认1，项目状态：1立项，2审核，3已退回，4进行中，5已结束
     *
     * @paramExample {string} 参数示例
     * {
     *       "manager_number": null
     *       "manager_name": "士大夫"
     *       "manager_begintime": "2020-09-15"
     *       "manager_endtime": "2020-09-23"
     *       "manager_person": "admin"
     *       "manager_fast": 1
     *       "manager_level": null
     *       "manager_examine": "admin"
     *       "manager_monitor": "admin"
     *       "team_person": "admin"
     *       "manager_explain": ""
     *       "manager_creater": "admin"
     *       "creat_time": "2020-09-15 14:49:21"
     *       "user_id": "admin"
     *       "manager_type": 1
     *       "manager_state": "1"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *          "model": {} // 包含新增项目的基本字段数据，不包含自定义字段的数据。字段说明参考项目详情数据接口
     *     }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function addProjectManager() {
        $result = NewProjectService::projectAdd($this->getAllInputAndCurUserId(), $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 编辑项目
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectManager() {
        $result = $this->projectService->editProjectManager($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目-获取项目类型的list，用在流程表单控件-下拉框-数据源-系统数据-项目管理-项目状态
     *
     * @return array
     *
     * @author dp
     *
     * @since 2018-01-24
     */
    public function getPorjectManagerStateList() {
        $result = $this->projectService->getPorjectManagerStateList($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 删除项目
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectManager() {
        $result = $this->projectService->deleteProjectManager($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 获取项目的参与人
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectTeams() {
        $result = $this->projectService->getProjectTeams($this->projectRequest->all());
        return $this->returnResult($result);
    }

    public function getProjectTeamsDrow() {
        $result = $this->projectService->getProjectTeamsDrow($this->projectRequest->all());
        return $this->returnResult($result);
    }

    public function getUserName() {
        $result = $this->projectService->getUserName($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 导入模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function importProjectTemplates() {
        $result = $this->projectService->importProjectTemplates($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    // -------not do
    /**
     * 项目列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectManagerList() {
        $result = $this->projectService->getProjectManagerList($this->getAllInputAndCurUserId(), $this->own);
        return (array) $this->returnResult($result);
    }

    /**
     * 处理项目
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function dealProjectManager() {
        $result = $this->projectService->dealProjectManager($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 设置团队成员
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function setProjectTeams() {
        $result = $this->projectService->setProjectTeams($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取团队列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectTeamsList() {
        $result = $this->projectService->getProjectTeamsList($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取某个团队
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectTeam() {
        $result = $this->projectService->getOneProjectTeam($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 项目讨论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectDiscuss() {
        $result = $this->projectService->addProjectDiscuss($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目讨论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectDiscuss($discuss_id) { //discuss_readtime为空
        $result = $this->projectService->editProjectDiscuss($discuss_id, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目讨论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectDiscuss($discuss_id) { //discuss_readtime为空
        $result = $this->projectService->deleteProjectDiscuss($discuss_id, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 回复讨论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function replyProjectDiscuss() {
        $result = $this->projectService->replyProjectDiscuss($this->projectRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 讨论列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectDiscussList($manager_id) {
        $result = $this->projectService->getProjectDiscussList($this->projectRequest->all(), $manager_id, $this->own['user_id']);
        return $this->returnResult($result);
    }

    public function getOneProjectDiscuss($id) {
        $result = $this->projectService->getOneProjectDiscuss($id);
        return $this->returnResult($result);
    }

    /**
     * 项目问题
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectQuestion() {
        $result = $this->projectService->addProjectQuestion($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 编辑项目问题
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectQuestion() {
        $result = $this->projectService->editProjectQuestion($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectQuestion() { //批量时 只删除符合条件的
        $input = $this->projectRequest->all();
        $input['user_id'] = $this->own['user_id'];
        $result = $this->projectService->deleteProjectQuestion($input);
        return $this->returnResult($result);
    }

    /**
     * 处理项目问题
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function dealProjectQuestion() {
        $result = $this->projectService->dealProjectQuestion($this->projectRequest->all(), $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 项目问题列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectQuestionList() {
        $result = $this->projectService->getProjectQuestionList($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目问题详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectQuestion() {
        $result = $this->projectService->getOneProjectQuestion($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目文档
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectDocment() {
        $result = $this->projectService->addProjectDocment($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目文档
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectDocment() {
        $result = $this->projectService->editProjectDocment($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目文档删除
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectDocment() {
        $result = $this->projectService->deleteProjectDocment($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    //批量下载文档中的附件
    public function batchDownloadAttachments() {
        $result = $this->projectService->batchDownloadAttachments($this->projectRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    //检查项目是否有附件
    public function hasAttachments($managerId)
    {
        $result = $this->projectService->hasAttachments($managerId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 项目文档列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectDocmentList() {
        $result = $this->projectService->getProjectDocmentList($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 获取任务处理的详情
     */
    public function getOneProjectTaskHandle($taskdiary_id) {
        $result = $this->projectService->getOneProjectTaskHandle($taskdiary_id, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    /**
     * 项目文档详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectDocment() {
        $result = $this->projectService->getOneProjectDocment($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    public function getOneProject($manager_id) {
        $result = $this->projectService->getOneProject($manager_id, $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 项目相关用户 项目团队 负责人 监控人 审批人
     */
    public function getProjectUsers($managerId) {
        $result = NewProjectService::getProjectUsers($managerId);
//        $result = $this->projectService->getProjectUsers($manager_id, $this->projectRequest->all());
        return $this->returnResult($result);
    }

    //项目移动版主页
    public function mobileProjectIndex($user_id) {
        $user_id = $this->own['user_id'];
        $result = $this->projectService->mobileProjectIndex($user_id, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    //项目信息
    public function getAppProject($manager_id) {
        $result = $this->projectService->getAppProject($manager_id, $this->own['user_id']);
        return $this->returnResult($result);
    }

    public function getTeamsAppList() {
        $result = $this->projectService->getTeamsAppList($this->projectRequest->all());
        return $this->returnResult($result);
    }

    public function managerModifyStatus() {
        $result = $this->projectService->managerModifyStatus($this->projectRequest->all(), $this->own['user_id']);
        return $this->returnResult($result);
    }

    public function projectAppraisal() {
        $result = $this->projectService->projectAppraisal($this->projectRequest->all(), $this->own['user_id']);
        return $this->returnResult($result);
    }

    public function modifyProjectTaskDiaryProcess($taskdiary_task) {
        $result = $this->projectService->modifyProjectTaskDiaryProcess($taskdiary_task, $this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    public function updateProjectStatus() {
        $result = $this->projectService->updateProjectStatus($this->getAllInputAndCurUserId());
        return $this->returnResult($result);
    }

    public function getProjectReportData() {
        $datasource_data_analysis = [
                'count' => '数量'
            ];
        $result = $this->projectService->getProjectReportData('manager_creater',$datasource_data_analysis,'');
        return $this->returnResult($result);
    }
    // 根据manager_type获取project_manager表中的数据
    public function getProjectListAll() {
        $result = $this->projectService->getProjectListAll($this->projectRequest->all());
        return $this->returnResult($result);
    }

    //获取项目自定义表单中，外键的自定义标签信息
    public function customTabMenus($projectId) {
        $result = $this->projectService->customTabMenus((int) $projectId, $this->projectRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    public function checkReadStatus($managerId) {
        $userId = $this->own['user_id'];
        $readStatus = $this->projectService->checkReadStatus($userId, $managerId);
        return $this->returnResult($readStatus);
    }

    public function membersReport()
    {
        $input = $this->projectRequest->all();
        $data = NewProjectService::userReport($input);
        return $this->returnResult($data);
    }

    public function membersReportDetail()
    {
        $input = $this->projectRequest->all();
        $data = NewProjectService::userReportDetail($input);
        return $this->returnResult($data);
    }

    public function projectsReport()
    {
        $input = $this->projectRequest->all();
        $data = NewProjectService::projectReport($input);
        return $this->returnResult($data);
    }

    public function managerNumberList()
    {
        $input = $this->projectRequest->all();
        $data = NewProjectService::managerNumberList($input);
        return $this->returnResult($data);
    }

    public function logList($manager_id)
    {
        $input = $this->projectRequest->all();
        $input['manager_id'] = $manager_id;
        $data = NewProjectService::logList($input);
        return $this->returnResult($data);
    }

    public function logSearch($manager_id)
    {
        $input = $this->projectRequest->all();
        $input['manager_id'] = $manager_id;
        $data = NewProjectService::logSearch($input);
        return $this->returnResult($data);
    }

    public function otherSettingList()
    {
        $data = NewProjectService::otherSettingList();
        return $this->returnResult($data);
    }

    public function otherSettingEdit()
    {
        NewProjectService::otherSettingEdit($this->projectRequest->all());
        return $this->returnResult(true);
    }

    public function roleFunctionPageTreeList()
    {
        $tree = NewProjectService::roleFunctionPageTreeList($this->projectRequest->all());
        return $this->returnResult($tree);
    }

    public function roleList()
    {
        $roleList = NewProjectService::roleList($this->projectRequest->all());
        return $this->returnResult($roleList);
    }

    public function roleInfo($roleId)
    {
        $data = NewProjectService::roleInfo($roleId);
        return $this->returnResult($data);
    }

    public function roleRelationFieldsList()
    {
        $data = NewProjectService::roleRelationFieldsList($this->projectRequest->all());
        return $this->returnResult($data);
    }

    public function roleAdd()
    {
        $data = NewProjectService::roleAdd($this->projectRequest->all());
        return $this->returnResult($data);
    }

    public function roleEdit($roleId)
    {
        $data = NewProjectService::roleEdit($roleId, $this->projectRequest->all());
        return $this->returnResult($data);
    }

    public function roleDelete($roleId)
    {
        $data = NewProjectService::roleDelete($roleId);
        return $this->returnResult($data);
    }

    public function monitorRoleList()
    {
        $roleList = NewProjectService::monitorRoleList($this->projectRequest->all());
        return $this->returnResult($roleList);
    }

    public function monitorRoleInfo($roleId)
    {
        $data = NewProjectService::monitorRoleInfo($roleId);
        return $this->returnResult($data);
    }

    public function monitorRoleAdd()
    {
        $data = NewProjectService::monitorRoleAdd($this->projectRequest->all());
        return $this->returnResult($data);
    }

    public function monitorRoleEdit($roleId)
    {
        $data = NewProjectService::monitorRoleEdit($roleId, $this->projectRequest->all());
        return $this->returnResult($data);
    }

    public function monitorRoleDelete($roleId)
    {
        $data = NewProjectService::monitorRoleDelete($roleId);
        return $this->returnResult($data);
    }

    // 初始化权限数据
    private function init() {
        $route = $this->projectRequest->route();
        $action = Arr::get($route, '1.uses', '');
        $action = explode('@', $action);
        $action = Arr::get($action, 1, '');
        $action = str_replace('V2', '', $action); // Todo 后续删除
        $urlParams = Arr::get($route, 2, []);
        $params = $this->projectRequest->all();
        $params = array_merge($params, $urlParams);
        $fpi = Arr::get($params, 'fpi', '');
        $this->dataManager = RolePermissionManager::getDataManager($this->own, $action, $fpi, $params);
    }
}
