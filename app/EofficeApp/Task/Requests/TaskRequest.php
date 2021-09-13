<?php
namespace App\EofficeApp\Task\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

class TaskRequest extends Request
{
    public function rules($request)
    {
        $rules = array(
            'createTask' => array(
                'task_name' => 'required|string|max:200',
                'parent_id' => 'integer',
            ),
            'followTask' => array(
                'taskIds' => 'required'
            ),
            'completeTask' => array(
                'taskIds' => 'required'
            ),
            'lockTask' => array(
                'taskIds' => 'required'
            ),
            'restoreTask' => array(
                'task_id' => 'required'
            ),
            'modifyTask' => array(
                'task_name' => 'required|string|max:200',
                'important_level' => 'integer'
            ),
            'modifyTaskManager' => array(
                'manage_user' => 'required'
            ),
            'createJoiner' => array(
                'task_id' => 'required|integer|exists:task_manage,id',
            ),
            'createShared' => array(
                'task_id' => 'required|integer|exists:task_manage,id',
            ),
            'deleteJoiner' => array(
                'task_id' => 'required|integer',
                'user_id' => 'required|string'
            ),
            'deleteShared' => array(
                'task_id' => 'required|integer',
                'user_id' => 'required|string'
            ),
            'createTaskFeedback' => array(
                'task_id' => 'required|integer',
                'parent_id' => 'integer',
                // 'feedback_content' => 'required|string',
            ),
            'getTaskRelationUser' => array(
                'relation_type' => 'required|string'
            ),
            'taskTagModify' => 'tagTagCreate',
            'pressTask' => array(
                'taskArray' => 'required',
                'manager' => 'required',
                'joiner' => 'required',
                'pressContent' => 'required',
                'sendMethod' => 'required',
            ),
            'createTaskClass' => array(
                'class_name' => 'required'
            ),
            'mobileCreateTask' => array(
                'task_name' => 'required|string',
                'manage_user' => 'required|string'
            )
        );

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
