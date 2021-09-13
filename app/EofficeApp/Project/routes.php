<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$routeConfig = [
    //项目管理:160 新建项目:161 我的项目:162 项目模板:165 费用清单: 38
//    ['project/project-monitor/set', 'setProjectMonitor', 'post'],
//    ['project/project-monitor/get', 'getProjectMonitor'],
//    ['project/project-examine/set', 'setProjectExamine', 'post'],
//    ['project/project-examine/get', 'getProjectExamineByUserId'],
//    ['project/max-order/{type}', 'getMaxOrderByType'],
//    ['project/project-examine/users', 'getProjectExamineUsers'],
//    ['project/project-type/add', 'addProjectType', 'post'],
//    ['project/project-type/{type_id}', 'editProjectType', 'put'],
//    ['project/project-type/{type_id}', 'deleteProjectType', 'delete'],
//    ['project/project-type/all', 'getAllProjectType'],
//    ['project/project-type/{type_id}', 'getOneProjectType'],
//    ['project/project-role/add', 'addProjectRole', 'post'],
//    ['project/project-role/{role_id}', 'editProjectRole', 'put'],
//    ['project/project-role/{role_id}', 'deleteProjectRole', 'delete'],
//    ['project/project-role/all', 'getAllProjectRole'],
//    ['project/project-role/{role_id}', 'getOneProjectRole'],
    ['project/mine-tasks', 'mineTaskList', 'get', [753]], //项目模板
    ['project/project-template/add', 'addProjectTemplate', 'post', [165]], //项目模板
    ['project/project-template/{templateId}', 'editProjectTemplate', 'put', [165]],//项目模板
    ['project/project-template/{templateId}', 'deleteProjectTemplate', 'delete', [165]],//项目模板
    ['project/project-template/all', 'getAllProjectTemplate', [165]],//项目模板
    ['project/project-template/{templateId}', 'getOneProjectTemplate', [165]],//项目模板
//    ['project/project-template-all', 'getAllTemplate', [162]],//导入任务模板时的模板列表接口
    // 增加项目任务
//    ['project/project-task/project/add', 'addProjectTask', 'post', [162]],//我的项目
//    ['project/project-task/project/{task_id}', 'editProjectTask', 'put', [162]],//我的项目
    // 删除任务
//    ['project/project-task/project/delete', 'deleteProjectTask', 'delete', [162]],//我的项目
    // 获取某个项目的单个任务的详情
//    ['project/project-task/project/getone', 'getOneProjectTask', [162]],//我的项目
    // 获取项目的任务标签的数据
//    ['project/project-task/project/task-list/{manager_id}', 'getProjectTaskListbyProjectId', [162]],//我的项目
    ['project/project-task/template/add', 'addProjectTemplateTask', 'post', [165]],//项目模板
    ['project/project-task/template/{taskId}', 'editProjectTemplateTask', 'put', [165]],//项目模板
    ['project/project-task/template/delete', 'deleteProjectTemplateTask', 'delete', [165]],//项目模板
    ['project/project-task/template/getone', 'getOneProjectTemplateTask', [165]],//项目模板
    ['project/project-task/template/task-list/{templateId}', 'getProjectTaskListbyTemplateId', [165]],//项目模板
    ['project/project-task/front-list/template/{id}', 'getTemplateFrontTask', [165]],//项目模板 wttf、我的项目（问题隶属任务、任务前置任务）
    ['project/get-person-do/{userId}', 'getPersonDo', [165]],//项目模板
    // 获取项目自定义表单中，外键的自定义标签信息
//    ['project/project-manager/tab-menus/{projectId}', 'customTabMenus', 'get', [162]],//我的项目
    // 新建项目
    ['project/project-manager/add', 'addProjectManager', 'post', [161]],//新建项目
    // 编辑项目
//    ['project/project-manager/{manager_id}', 'editProjectManager', 'put', [162]],//我的项目
    // 项目-获取项目类型的list，用在流程表单控件-下拉框-数据源-系统数据-项目管理-项目状态
    ['project/project-manager/manarger-state/list', 'getPorjectManagerStateList'],//表单设计器
//    ['project/project-manager/delete', 'deleteProjectManager', 'delete', [162]],//我的项目
    // 任务-选择任务执行人的下拉框
    ['project/project-manager/users', 'getProjectTeamsDrow', [162]],//我的项目
    ['project/project-manager/get-user', 'getUserName'],//前端存在此api，但未实际使用，可能已废弃
//    ['project/project-manager/improtTeamplates', 'importProjectTemplates', 'post', [162]],//我的项目
    // 项目模块，从看板页面，进入list页面，获取中间列数据
//    ['project/project-manager/lists', 'getProjectManagerList', [38, 162]],//费用清单 我的项目
    // 获取项目管理表中根据manager_type值进行获取
    ['project/project-manager/list', 'getProjectListAll', [800, 168]],//系统设置-下拉框配置 *用来获取要删除的分类的所有项目数据
    // 项目首页--项目看板展示页面，获取list
//    ['project/project-manager-portal/lists/{user_id}', 'getProjectListIndex', [162]],
    // 项目信息放到系统数据中（editor）--这里也是门户，获取项目list的页面
//    ['project/project-manager-system-data/lists/{user_id}', 'getProjectSystemData', [162]],* 已替换
    // 获取项目详情
//    ['project/project-manager/{manager_id}', 'getOneProject', [38, 162]],//费用清单 我的项目
    // 处理项目 [提交 批准 退回  结束 重新启动]
//    ['project/project-manager/project/deal', 'dealProjectManager', 'put', [162]],//我的项目
//    ['project/project-teams/set', 'setProjectTeams', 'put', [162]],//我的项目
//    ['project/project-teams/team-list', 'getProjectTeamsList', [162]],//手机端我的团队列表，废弃
    // 我的项目--项目团队，获取数据
//    ['project/project-teams/team-one', 'getOneProjectTeam', [162]],//我的项目 team
//    ['project/project-teams/app-list', 'getTeamsAppList', [162]], //我的项目
//    ['project/project-create-teams', 'getProjectCreateTeams'],//可能已废弃
//    ['project/project-discuss/add', 'addProjectDiscuss', 'post'],//可能已废弃
//    ['project/project-discuss/reply/{discuss_id}', 'getOneProjectDiscuss'],//可能已废弃


//    ['project/project-discuss/{discuss_id}', 'editProjectDiscuss', 'put', [162]],//我的项目
//    ['project/project-discuss/{discuss_id}', 'deleteProjectDiscuss', 'delete', [162]],//我的项目
//    ['project/project-discuss/reply', 'replyProjectDiscuss', 'post', [162]],//我的项目
//    ['project/project-discuss/lists/{manager_id}', 'getProjectDiscussList', [162]],//我的项目
    // 新建项目问题
//    ['project/project-question/add', 'addProjectQuestion', 'post', [162]],//我的项目
//    ['project/project-question/{question_id}', 'editProjectQuestion', 'put', [162]],//我的项目
//    ['project/project-question/delete', 'deleteProjectQuestion', 'delete', [162]],//我的项目
//    ['project/project-question/question/deal', 'dealProjectQuestion', 'put', [162]],//我的项目
//    ['project/project-question/question-all', 'getProjectQuestionList', [162]],//我的项目
//    ['project/project-question/question-one', 'getOneProjectQuestion', [162]],//我的项目
//    ['project/project-docment/add', 'addProjectDocment', 'post', [162]],//我的项目
//    ['project/project-docment/{doc_id}', 'editProjectDocment', 'put', [162]],//我的项目
//    ['project/project-docment/delete', 'deleteProjectDocment', 'delete', [162]],//我的项目
//    ['project/project-docment/doc-all', 'getProjectDocmentList', [162]],//我的项目
//    ['project/project-docment/doc-one', 'getOneProjectDocment', [162]],//我的项目
//    ['project/project-docment/batch-download-attachments', 'batchDownloadAttachments', 'post', [162]],//我的项目
    // 项目-甘特图标签
//    ['project/project-gantt', 'getProjectGantt', [162]],//我的项目
    // 编辑项目的状态
//    ['project/project-modify-status', 'managerModifyStatus', "PUT", [162]],//我的项目 禁用取消
    // 保存项目考核
//    ['project/project-appraisal', 'projectAppraisal', "PUT", [162]],//我的项目
    //获取项目的所有用户：负责人、监控人、审批人、团队成员
    ['project/project-user/{managerId}', 'getProjectUsers', [162]],//*已重构新版代码
    //手机版 项目首页
//    ['project/mobile/lists/{user_id}', 'mobileProjectIndex', [162]], //项目首页、选择器
//    ['project/mobile/{manarger_id}', 'getAppProject', [162]],//我的项目

    //任务处理
//    ['project/project-task-diary-add', 'addProjectTaskDiary', 'post', [162]],//我的项目，已废弃
    // 获取任务讨论详情
//    ['project/project-task-diary-reply/{taskdiary_id}', 'getOneProjectTaskDiary'],//可能已废弃


    // 编辑任务讨论
//    ['project/project-task-diary/{taskDiaryId}', 'editProjectTaskDiary', 'put', [162]],//我的项目
    // 删除任务讨论
//    ['project/project-task-diary/{taskDiaryId}', 'deleteProjectTaskDiary', 'delete', [162]],//我的项目
    // 新建任务讨论
//    ['project/project-task-diary-reply', 'replyProjectTaskDiary', 'post', [162]],//我的项目
    // 获取任务讨论list
//    ['project/project-task-diary-lists/{taskId}', 'getProjectTaskDiaryList', [162]],//我的项目


    // 修改任务进度百分比
//    ['project/project-task-process/{taskdiary_task}', 'modifyProjectTaskDiaryProcess', 'put', [162]],//我的项目
    // 点击任务list，更新任务状态，取消未读的小红点
    ['project/project-status-update', 'updateProjectStatus', 'post', [162]],//我的项目
//    ['project/has-attachments/{manager_id}', 'hasAttachments', [162]],//我的项目，检查项目是否有附件
    ['project/check-read-status/{managerId}', 'checkReadStatus', [162]],
    // 报表
    ['project/report/members', 'membersReport', [163, 164]],
    ['project/report/members/detail', 'membersReportDetail', [163, 164]],
    ['project/report/projects', 'projectsReport', [163, 166]],
    ['project/member-number', 'managerNumberList'],

    ['project/setting/other', 'otherSettingList', [751]],
    ['project/setting/other', 'otherSettingEdit', 'put', [751]],
    // 权限
    ['project/setting/authority/fp/tree', 'roleFunctionPageTreeList', 'get', [754]], // 功能树数据
    // 角色相关
    ['project/setting/authority/field-list', 'roleRelationFieldsList', 'get', [754]],
    // 数据权限
    ['project/setting/authority/roles', 'roleList', 'get', [754]],
    ['project/setting/authority/roles', 'roleAdd', 'post', [754]], // 新建权限组
    ['project/setting/authority/roles/{roleId}', 'roleEdit', 'put', [754]],
    ['project/setting/authority/roles/{roleId}', 'roleInfo', 'get', [754]],
    ['project/setting/authority/roles/{roleId}', 'roleDelete', 'delete', [754]],
    // 监控权限
    ['project/setting/authority/monitor-roles', 'monitorRoleList', 'get', [754]],
    ['project/setting/authority/monitor-roles', 'monitorRoleAdd', 'post', [754]], // 新建权限组
    ['project/setting/authority/monitor-roles/{roleId}', 'monitorRoleEdit', 'put', [754]],
    ['project/setting/authority/monitor-roles/{roleId}', 'monitorRoleInfo', 'get', [754]],
    ['project/setting/authority/monitor-roles/{roleId}', 'monitorRoleDelete', 'delete', [754]],
//    ['project/log-list/{manager_id}', 'logList', [162]],
//    ['project/log-search/{manager_id}', 'logSearch', [162]],
];
$apis = config('project.api', []);
if ($apis) {
    foreach ($apis as $action => $api) {
        $routeConfig[] = [
            $api[3],
            $action . 'V2',
            $api[2],
            $api[4],
        ];
    }
}
