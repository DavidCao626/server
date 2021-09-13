<?php

namespace App\EofficeApp\Project\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

class ProjectRequest extends Request {

    public $errorCode = '0x036001';

    public function rules($request) {
        $rules = [

            'addProjectTemplate' => [
                'user_id' => 'required',
                'template_type' => 'required|integer',
                'template_name' => 'required|max:255'
            ],
            'editProjectTemplate' => [
                'user_id' => 'required',
                'template_type' => 'required|integer',
                'template_name' => 'required|max:255'
            ],
            'addProjectTask' => [
                'user_id' => 'required',
                'task_name' => 'required|max:255',
                'task_begintime' => 'required|date',
                'task_endtime' => 'required|date',
                'task_project' => 'required|integer',
                'task_persondo' => 'required',
            ],
            'editProjectTask' => [
                'user_id' => 'required',
                'task_name' => 'required|max:255',
                'task_begintime' => 'required|date',
                'task_endtime' => 'required|date',
                'task_project' => 'required|integer',
                'task_persondo' => 'required'
            ],
//            'addProjectTemplateTask' => [
//                'user_id' => 'required',
//                'task_name' => 'required|max:255',
//                'task_begintime' => 'required|date',
//                'task_endtime' => 'required|date',
//                'task_complate' => 'required|integer',
//            ],
//            'editProjectTemplateTask' => [
//                'user_id' => 'required',
//                'task_name' => 'required|max:255',
//                'task_begintime' => 'required|date',
//                'task_endtime' => 'required|date',
//                'task_complate' => 'required|integer',
//            ],
            //删除模板任务
            'deleteProjectTemplateTask' => [
                'task_id' => 'required',
                'task_complate' => 'required|integer',
            ],
            'getOneProjectTemplateTask' => [
                'task_id' => 'required|integer',
                'task_complate' => 'required|integer'
            ],
            //删除项目任务
            'deleteProjectTask' => [
                'task_id' => 'required',
                'user_id' => 'required',
                'task_project' => 'required|integer'
            ],
            'getOneProjectTask' => [
                'user_id' => 'required', //登录用户
                'task_project' => 'required|integer',
                'task_id' => 'required|integer'
            ],
            'getProjectTaskListbyProjectId' => [
                'user_id' => 'required', //登录用户
                'manager_id' => 'required'
            ],
            'addProjectTaskDiary' => [
                'user_id' => 'required',
                'taskdiary_task' => 'required|integer',
                'taskdiary_project' => 'required|integer',
                'taskdiary_explain' => 'required'
            ],
            'deleteProjectTaskDiary' => [
                'taskdiary_id' => 'required',
                'taskdiary_task' => 'required|integer',
            ],
            'addProjectManager' => [
                'user_id' => 'required',
                // 'manager_name' => 'required',
                // 'manager_begintime' => 'required|date',
                // 'manager_endtime' => 'required|date',
                // 'manager_type' => 'required|integer',
                // 'manager_examine' => 'required',
                // 'manager_person' => 'required',
            ],
            'editProjectManager' => [
                'manager_id' => 'required|integer',
                'user_id' => 'required',
                // 'manager_name' => 'required',
                // 'manager_begintime' => 'required|date',
                // 'manager_endtime' => 'required|date',
                // 'manager_type' => 'required|integer',
                // 'manager_examine' => 'required',
                // 'manager_person' => 'required',
            ],
            'deleteProjectManager' => [
                'user_id' => 'required', //登录用户
                'manager_id' => 'required'
            ],
            'getProjectTeams' => [
                'manager_id' => 'required|integer'
            ],
            'importProjectTemplates' => [
                'template_id' => 'required|integer',
                'manager_id' => 'required|integer',
                'user_id' => 'required'
            ],
            //获取某人的项目列表
            'getProjectManagerList' => [
                'user_id' => 'required'
            ],
            //处理项目
            'dealProjectManager' => [
                "operate" => "required|in:proExamine,proApprove,proRefuse,proEnd,proRestart", //提交 退回 批准 结束 重新启动
                'manager_id' => 'required|integer',
                'user_id' => 'required'
            ],
            'setProjectTeams' => [
                'team_project' => 'required|integer',
            ],
            'getProjectTeamsList' => [
                'user_id' => 'required',
            ],
            'getOneProjectTeam' => [
                'team_id' => 'required|integer'
            ],
            'addProjectQuestion' => [
                'question_project' => 'required|integer',
                'user_id' => 'required',
                'question_name' => 'required',
                'question_person' => 'required',
                'question_doperson' => 'required',
                'question_endtime' => 'required|date'
            ],
            'editProjectQuestion' => [
                'question_id' => 'required|integer',
                'question_project' => 'required|integer',
                'user_id' => 'required',
                'question_name' => 'required',
                'question_person' => 'required',
                'question_doperson' => 'required',
                'question_endtime' => 'required|date'
            ],
            'deleteProjectQuestion' => [
                'question_id' => 'required'
            ],
            'dealProjectQuestion' => [
                'type' => "required|in:issueSubmit,issueProcessing,issueProcessed,issueUnsolved,issueResolved", //提交 处理中 已处理 未解决 已解决
                'user_id' => 'required',
                'question_id' => 'required|integer',
            ],
            'getOneProjectQuestion' => [
                'question_id' => 'required|integer'
            ],
            'documentAddV2' => [
                'doc_project' => 'required|integer',
                'user_id' => 'required',
                'doc_name' => 'required'
            ],
            'documentEditV2' => [
                'doc_id' => 'required|integer',
                'doc_project' => 'required|integer',
                'user_id' => 'required',
                'doc_name' => 'required'
            ],
            'deleteProjectDocment' => [
                'doc_id' => 'required'
            ],
            'getOneProjectDocment' => [
                'doc_id' => 'required|integer'
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
