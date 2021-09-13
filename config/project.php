<?php
$projectConfig = [
    'roles' => [
        'manager_person' => 1,
        'manager_examine' => 2,
        'manager_monitor' => 3,
        'manager_creater' => 4,
        'team_person' => 5,
        'task_creater' => 6,
        'task_persondo' => 7,
        'doc_creater' => 8,
        'question_person' => 9,
        'question_doperson' => 10,
        'question_creater' => 11,
        'p1_task_persondo' => 12,// 上级
        'p2_task_persondo' => 13,// 多层上级
    ],
    // 唯一id => [parent_id, is_show, is_related, filter_state]
    'function_pages' => [
        'project' => [
            'my_project' => ['', 1, 0, []],
            'project_list' => ['my_project', 0, 1, []],
            'project_info' => ['my_project', 0, 1, []],
            'project_edit' => ['my_project', 1, 0, []],
            'project_delete' => ['my_project', 1, 0, []],
            'project_discuss' => ['my_project', 1, 0, []],
            'son_modules' => ['my_project', 1, 0, []],
            'project_other' => ['my_project', 1, 0, []],
            'pro_examine' => ['project_other', 1, 0, []], // 提交审核
            'pro_approve' => ['project_other', 1, 0, []], // 审核通过
            'pro_refuse' => ['project_other', 1, 0, []], // 审核退回
            'pro_over' => ['project_other', 1, 0, []], // 结束项目
            'pro_restart' => ['project_other', 1, 0, []], // 重启项目
        ],
        'task' => [
            'task' => ['son_modules', 1, 0, []],
            'task_list' => ['task', 0, 1, []],
            'task_info' => ['task', 0, 1, []],
            'task_add' => ['task', 1, 0, []],
            'son_task_add' => ['task', 1, 0, []],
            'task_edit' => ['task', 1, 0, []],
            'task_delete' => ['task', 1, 0, []],
            'task_progress' => ['task', 1, 0, []],
            'task_discuss' => ['task', 0, 1, []],
            'task_list_export' => ['task', 0, 1, []],
        ],
        'question' => [
            'question' => ['son_modules', 1, 0, []],
            'question_list' => ['question', 0, 1, []],
            'question_info' => ['question', 0, 1, []],
            'question_add' => ['question', 1, 0, []],
            'question_edit' => ['question', 1, 0, []],
            'question_delete' => ['question', 1, 0, []],
            'question_other' => ['question', 1, 0, []],
            'question_solve' => ['question_other', 1, 0, []],
            'question_receipt' => ['question_other', 1, 0, []],
        ],
        'document' => [
            'document' => ['son_modules', 1, 0, []],
            'document_list' => ['document', 0, 1, []],
            'document_info' => ['document', 0, 1, []],
            'document_add' => ['document', 1, 0, []],
            'document_edit' => ['document', 1, 0, []],
            'document_delete' => ['document', 1, 0, []],
            'document_other' => ['document', 1, 0, []],
            'document_batch_download' => ['document_other', 1, 0, []],
        ],
        'document_dir' => [
            'document_dir' => ['son_modules', 1, 0, []],
            'dir_list' => ['document_dir', 0, 1, []],
            'dir_info' => ['document_dir', 0, 1, []],
            'dir_add' => ['document_dir', 0, 1, []],
            'dir_edit' => ['document_dir', 0, 1, []],
            'dir_delete' => ['document_dir', 0, 1, []],
        ],
        'gantt' => [
            'gantt' => ['son_modules', 1, 0, []],
            'gantt_list' => ['gannt', 0, 1, []],
        ],
        'appraisal' => [
            'appraisal' => ['son_modules', 1, 0, []],
            'appraisal_list' => ['appraisal', 0, 1, []],
            'appraisal_edit' => ['appraisal', 1, 0, []],
        ],
        'log' => [
            'log' => ['son_modules', 1, 0, []],
            'log_list' => ['log', 0, 1, []],
        ]
    ],
    // function_name => [type, module, method, path, menu, must_params, default_order, default_fpi]
    'api' => [
        'projectList' => ['list', 'project', 'get', 'project/project-manager-portal/lists/{user_id}', [162], [], ['manager_id' => 'desc'], 'project_list'],
        'projectInfo' => ['info', 'project', 'get', 'project/project-manager/{manager_id}', [38, 162], ['manager_id'], [], 'project_info'],
        'customTabMenus' => ['info', 'project', 'get', 'project/project-manager/tab-menus/{projectId}', [162], ['manager_id' => 'projectId'], [], 'project_info'],
        'projectEdit' => ['edit', 'project', 'put', 'project/project-manager/{manager_id}', [162], ['manager_id'], [], 'project_edit'],
        'projectDelete' => ['delete', 'project', 'delete', 'project/project-manager/delete', [162], ['manager_id'], [], 'project_delete'],
        'projectTeamList' => ['list', 'project', 'get', 'project/project-teams/list', [162], ['manager_id'], [], 'project_info'],

        'discussList' => ['list', 'project', 'get', 'project/project-discuss/lists/{manager_id}', [162], ['manager_id'], [], 'project_discuss'],
        'discussAdd' => ['add', 'project', 'post', 'project/project-discuss/reply', [162], ['manager_id' => 'discuss_project'], [], 'project_discuss'],
        'discussEdit' => ['edit', 'project', 'put', 'project/project-discuss/{discuss_id}', [162], ['manager_id' => 'discuss_project', 'discuss_id'], [], 'project_discuss'],
        'discussDelete' => ['delete', 'project', 'delete', 'project/project-discuss/{discuss_id}', [162], ['manager_id' => 'discuss_project', 'discuss_id'], [], 'project_discuss'],

        'taskList' => ['list', 'project', 'get', 'project/project-task/project/task-list/{manager_id}', [162], ['manager_id'], [], 'task_list'],
        'taskInfo' => ['info', 'task', 'get', 'project/project-task/project/getone', [162], ['manager_id' => 'task_project', 'relation_id' => 'task_id'], [], 'task_info'],
        'taskAdd' => ['add', 'project', 'post', 'project/project-task/project/add', [162], ['manager_id' => 'task_project'], [], 'task_add'],
        'sonTaskAdd' => ['add', 'task', 'post', 'project/project-task/project/add/son', [162], ['manager_id' => 'task_project', 'relation_id' => 'parent_task_id'], [], 'son_task_add'],
        'taskEdit' => ['edit', 'task', 'put', 'project/project-task/project/{task_id}', [162], ['manager_id' => 'task_project', 'relation_id' => 'task_id'], [], 'task_edit'],
        'taskDelete' => ['delete', 'task', 'delete', 'project/project-task/project/delete', [162], ['manager_id' => 'task_project', 'relation_id' => 'task_id'], [], 'task_delete'],
        'frontTaskList' => ['list', 'project', 'get', 'project/project-task/front-list/manager/{manager_id}', [162], ['manager_id'], [], 'project_info'],
        'projectTemplateList' => ['list', 'project', 'get', 'project/project-template-all', [162], ['manager_id', 'template_type'], [], 'task_add'], // 模板列表
        'importProjectTemplateTask' => ['add', 'project', 'post', 'project/project-manager/improtTeamplates', [162], ['manager_id', 'template_id'], [], 'task_add'], // 导入模板任务

        'taskDiscussList' => ['list', 'task', 'get', 'project/project-task-diary-lists/{task_id}', [162], ['manager_id' => 'taskdiary_project', 'relation_id' => 'task_id'], [], 'task_discuss'],
        'taskDiscussAdd' => ['add', 'task', 'post', 'project/project-task-diary-reply', [162], ['manager_id' => 'taskdiary_project', 'relation_id' => 'taskdiary_task'], [], 'task_discuss'],
        'taskDiscussEdit' => ['edit', 'task', 'put', 'project/project-task-diary/{taskdiary_id}', [162], ['manager_id' => 'taskdiary_project', 'discuss_id' => 'taskdiary_id', 'relation_id' => 'taskdiary_task'], [], 'task_discuss'],
        'taskDiscussDelete' => ['delete', 'task', 'delete', 'project/project-task-diary/{taskdiary_id}', [162], ['manager_id' => 'taskdiary_project', 'discuss_id' => 'taskdiary_id', 'relation_id' => 'taskdiary_task'], [], 'task_discuss'],

        'questionList' => ['list', 'project', 'get', 'project/project-question/question-all', [162], ['manager_id'], [], 'question_list'],
        'questionInfo' => ['info', 'question', 'get', 'project/project-question/question-one', [162], ['manager_id', 'relation_id' => 'question_id'], [], 'question_info'],
        'questionAdd' => ['add', 'project', 'post', 'project/project-question/add', [162], ['manager_id' => 'question_project'], [], 'question_add'],
        'questionEdit' => ['edit', 'question', 'put', 'project/project-question/{question_id}', [162], ['manager_id' => 'question_project', 'relation_id' => 'question_id'], [], 'question_edit'],
        'questionDelete' => ['delete', 'question', 'delete', 'project/project-question/delete', [162], ['manager_id', 'relation_id' => 'question_id'], [], 'question_delete'],

        'documentList' => ['list', 'project', 'get', 'project/project-document/doc-all', [162], ['manager_id'], [], 'document_list'],
        'documentInfo' => ['info', 'document', 'get', 'project/project-document/doc-one', [162], ['manager_id', 'relation_id' => 'doc_id'], [], 'document_info'],
        'documentAdd' => ['add', 'project', 'post', 'project/project-document/add', [162], ['manager_id' => 'doc_project'], [], 'document_add'],
        'documentEdit' => ['edit', 'document', 'put', 'project/project-document/{doc_id}', [162], ['manager_id' => 'doc_project', 'relation_id' => 'doc_id'], [], 'document_edit'],
        'documentDelete' => ['delete', 'document', 'delete', 'project/project-document/delete', [162], ['manager_id' => 'doc_project', 'relation_id' => 'doc_id'], [], 'document_delete'],
        'batchDownloadAttachments' => ['info', 'project', 'post', 'project/project-document/batch-download-attachments', [162], ['manager_id'], [], 'document_batch_download'],
        'hasAttachments' => ['info', 'project', 'get', 'project/has-attachments/{manager_id}', [162], ['manager_id'], [], 'document_batch_download'],
        // 文档文件夹管理
        'documentDirList' => ['list', 'project', 'get', 'project/project-document/dir', [162], ['manager_id'], [], 'dir_list'],
        'documentDirInfo' => ['info', 'document_dir', 'get', 'project/project-document/dir/{dir_id}', [162], ['manager_id', 'relation_id' => 'dir_id'], [], 'dir_info'],
        'documentDirAdd' => ['add', 'project', 'post', 'project/project-document/dir', [162], ['manager_id' => 'dir_project'], [], 'dir_add'],
        'documentDirEdit' => ['edit', 'document_dir', 'put', 'project/project-document/dir/{dir_id}', [162], ['manager_id' => 'dir_project', 'relation_id' => 'dir_id'], [], 'dir_edit'],
        'documentDirDelete' => ['delete', 'document_dir', 'delete', 'project/project-document/dir/{dir_id}', [162], ['manager_id' => 'dir_project', 'relation_id' => 'dir_id'], [], 'dir_delete'],

        'ganttList' => ['list', 'project', 'get', 'project/project-gantt', [162], ['manager_id'], [], 'gantt_list'],

        'logList' => ['list', 'project', 'get', 'project/log-list/{manager_id}', [162], ['manager_id'], [], 'log_list'],
        'logSearch' => ['info', 'project', 'get', 'project/log-search/{manager_id}', [162], ['manager_id'], [], 'log_list'],
    ],

    // 功能页api关联
    'function_page_api' => [
        'project_list' => ['projectList'],
        'project_info' => ['projectInfo', 'customTabMenus', 'projectTeamList', 'frontTaskList'],
        'project_edit' => ['projectEdit'],
        'project_delete' => ['projectDelete'],
        'project_discuss' => ['discussList', 'discussAdd', 'discussEdit', 'discussDelete'],
        'pro_examine' => ['projectEdit'], // 提交审核
        'pro_approve' => ['projectEdit'], // 审核通过
        'pro_refuse' => ['projectEdit'], // 审核退回
        'pro_over' => ['projectEdit'], // 结束项目
        'pro_restart' => ['projectEdit'], // 重启项目

        'task_add' => ['taskAdd', 'projectTemplateList', 'importProjectTemplateTask'],
        'son_task_add' => ['sonTaskAdd'],
        'task_list' => ['taskList'],
        'task_info' => ['taskInfo'],
        'task_edit' => ['taskEdit'],
        'task_progress' => ['taskEdit'],
        'task_delete' => ['taskDelete'],
        'task_discuss' => ['taskDiscussList', 'taskDiscussAdd', 'taskDiscussEdit', 'taskDiscussDelete'],

        'question_add' => ['questionAdd'],
        'question_list' => ['questionList'],
        'question_info' => ['questionInfo'],
        'question_edit' => ['questionEdit'],
        'question_delete' => ['questionDelete'],
        'question_solve' => ['questionEdit'],
        'question_receipt' => ['questionEdit'],

        'document_add' => ['documentAdd', 'documentDirList'],
        'document_list' => ['documentList'],
        'document_info' => ['documentInfo'],
        'document_edit' => ['documentEdit', 'documentDirList'],
        'document_delete' => ['documentDelete'],
        'document_batch_download' => ['batchDownloadAttachments', 'hasAttachments'],

        'dir_list' => ['documentDirList'],
        'dir_info' => ['documentDirInfo'],
        'dir_add' => ['documentDirAdd'],
        'dir_edit' => ['documentDirEdit'],
        'dir_delete' => ['documentDirDelete'],

        'gantt_list' => ['ganttList'],

        'appraisal_edit' => ['projectEdit'],

        'log_list' => ['logList', 'logSearch'],
    ],
    // 角色功能页关联
    'role_function_page' => [
        1 => [
            'manager_person'    => ['project' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 1], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_examine'   => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'manager_monitor'   => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'manager_creater'   => ['project' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'team_person'       => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'task_creater'      => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'task_persondo'     => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 0, 0, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p1_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p2_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'doc_creater'       => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'question_person'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_doperson' => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 0, 0, 1, 1, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_creater'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
        ],
        2 => [
            'manager_person'    => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 1], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_examine'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 1, 0, 1, 1, 0, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_monitor'   => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'manager_creater'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 0, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'team_person'       => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'task_creater'      => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'task_persondo'     => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p1_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p2_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'doc_creater'       => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_person'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_doperson' => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 0, 0, 1, 1, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_creater'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
        ],
        3 => [
            'manager_person'    => ['project' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 1], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_examine'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_monitor'   => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'manager_creater'   => ['project' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'team_person'       => ['project' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [0, 0], 'appraisal' => [0, 0, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'task_creater'      => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'task_persondo'     => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 0, 0, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p1_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p2_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'doc_creater'       => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'question_person'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_doperson' => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 0, 0, 1, 1, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_creater'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
        ],
        4 => [
            'manager_person'    => ['project' => [1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 1], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_examine'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_monitor'   => ['project' => [1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 0], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_creater'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'team_person'       => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'task_creater'      => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'task_persondo'     => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 0, 0, 1, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p1_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p2_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'doc_creater'       => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'question_person'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_doperson' => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 0, 0, 1, 1, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_creater'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
        ],
        5 => [
            'manager_person'    => ['project' => [1, 1, 1, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 1], 'log' => [1, 1], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'manager_examine'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [1, 1], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'manager_monitor'   => ['project' => [1, 1, 1, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1], 'question' => [1, 1, 1, 1, 1, 1, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [1, 1], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'manager_creater'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'team_person'       => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'task_creater'      => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'task_persondo'     => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p1_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'p2_task_persondo'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 1, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 0, 0, 1, 1], 'task' => [1, 1, 1, 0, 0, 0, 0, 0, 1], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'doc_creater'       => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'document' => [1, 1, 1, 1, 1, 1, 1, 1], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [1, 1, 1, 1, 1, 1]],
            'question_person'   => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 1, 0, 1], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_doperson' => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 0, 0, 1, 1, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
            'question_creater'  => ['project' => [1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0], 'question' => [1, 1, 1, 0, 1, 1, 0, 0, 0], 'document' => [0, 0, 0, 0, 0, 0, 0, 0], 'task' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'gantt' => [1, 1], 'appraisal' => [1, 1, 0], 'log' => [0, 0], 'document_dir' => [0, 0, 0, 0, 0, 0]],
        ],
    ],

    // 角色对应的字段
    'role_field' => [
        'project' => [
            'manager_person',
            'manager_examine' ,
            'manager_monitor',
            'manager_creater',
            'team_person',
        ],
        'task' => [
            'task_creater',
            'task_persondo',
            'p1_task_persondo',
            'p2_task_persondo',
        ],
        'document' => [
            'doc_creater'
        ],
        'question' => [
            'question_person',
            'question_doperson',
            'question_creater',
        ],
    ],
    'api_config' => [
        'pro_examine' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['manager_state' => [[1, 3], 'in']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['manager_state']], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => ['manager_state' => 2],// 固定填充得值
                ]
            ]
        ],
        'pro_restart' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['manager_state' => [[5], 'in']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['manager_state']], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => ['manager_state' => 4],// 固定填充得值
                ]
            ]
        ],
        'pro_over' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['manager_state' => [[4], 'in']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['manager_state']], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => ['manager_state' => 5],// 固定填充得值
                ]
            ]
        ],
        'pro_approve' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['manager_state' => [[2], 'in']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['manager_state']], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => ['manager_state' => 4],// 固定填充得值
                ]
            ]
        ],
        'pro_refuse' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['manager_state' => [[2], 'in']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['manager_state']], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => ['manager_state' => 3],// 固定填充得值
                ]
            ]
        ],
        'task_progress' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['is_leaf' => 1, 'callback' => ['testFrontTaskComplete']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['task_persent']], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => [],// 固定填充得值
                ]
            ]
        ],
        'question_add' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => [], // 有该权限得数据
                'config' => [
                    'allow_fields' => [], // 允许编辑得值
                    'test_fields' => ['question_state' => [[0, 1], 'in']], // 测试值数据
                    'fixed_field_data' => [],// 固定填充得值
                ]
            ]
        ],
        'question_edit' => [
            [
                'sort' => 1,
                'role_ids' => ['type' => 'black', 'values'=> ['type' => 'question', 'role_field_key' => ['question_person', 'question_doperson', 'question_creater']]],
                'filter' => [], // 有该权限得数据
                'config' => [
                    'allow_fields' => [], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => [],// 固定填充得值
                ]
            ],
            [
                'sort' => 2,
                'role_ids' => ['type' => 'white', 'values'=> ['type' => 'question', 'role_field_key' => ['question_person', 'question_doperson', 'question_creater']]],
                'filter' => ['question_state' => 0], // 有该权限得数据
                'config' => [
                    'allow_fields' => [], // 允许编辑得值
                    'test_fields' => ['question_state' => [[0, 1], 'in']], // 测试值数据
                    'fixed_field_data' => [],// 固定填充得值
                ]
            ],

        ],
        'question_solve' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['question_state' => [[1, 2, 4], 'in']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['question_do', 'question_state']], // 允许编辑得值
                    'test_fields' => ['question_state' => [[2, 3], 'in']], // 测试值数据
                    'fixed_field_data' => ['question_dotime' => 'currentDatetime'],// 固定填充得值
                ]
            ]
        ],
        'question_receipt' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['question_state' => [[3], 'in']], // 有该权限得数据
                'config' => [
                    'allow_fields' => ['type' => 'white', 'values' => ['question_back', 'question_state']], // 允许编辑得值
                    'test_fields' => ['question_state' => [[4, 5], 'in']], // 测试值数据
                    'fixed_field_data' => ['question_backtime' => 'currentDatetime'],// 固定填充得值
                ]
            ]
        ],
        'question_delete' => [
            [
                'sort' => 1,
                'role_ids' => ['type' => 'black', 'values'=> ['type' => 'question', 'role_field_key' => ['question_person', 'question_doperson', 'question_creater']]],
                'filter' => [], // 有该权限得数据
            ],
            [
                'sort' => 2,
                'role_ids' => ['type' => 'white', 'values'=> ['type' => 'question', 'role_field_key' => ['question_person', 'question_doperson', 'question_creater']]],
                'filter' => ['question_state' => [[0, 5], 'in']], // 有该权限得数据
            ]
        ],
        'dir_delete' => [
            [
                'sort' => 1,
                'role_ids' => [],
                'filter' => ['callback' => ['canDeleteDocumentDir']], // 有该权限得数据
                'config' => [
                    'allow_fields' => [], // 允许编辑得值
                    'test_fields' => [], // 测试值数据
                    'fixed_field_data' => [],// 固定填充得值
                ]
            ]
        ],
    ]

];
// 合并第三方
if (file_exists(config_path('third_project.php'))) {
    return $projectConfig + include 'third_project.php';
}
return $projectConfig;
