<?php
return [
    '0x036001'                               => '请求异常',
    '0x036002'                               => '系统异常',
    '0x036003'                               => '项目类型被占用',
    '0x036004'                               => '项目角色被占用',
    '0x036005'                               => '添加任务请指定项任务或者模板任务,数据异常',
    '0x036006'                               => '项目任务时任务执行人不能为空',
    '0x036007'                               => '非立项状态的项目是不可以编辑',
    '0x036008'                               => '审核中的项目不可以删除',
    '0x036009'                               => '项目模板中已存在数据,暂不能删除',
    '0x036010'                               => '导入的模板没有相关任务',
    '0x036011'                               => '该项目状态异常',
    '0x036012'                               => '回复问题不支持编辑',
    '0x036013'                               => '问题被看过,不可编辑',
    '0x036014'                               => '数据异常,自己不可以删除他人的讨论',
    '0x036015'                               => '数据异常,该问题状态不可以被编辑',
    '0x036016'                               => '数据异常,当前问题状态不支持该操作',
    '0x036017'                               => '请填写解决方案',
    '0x036018'                               => '你当前没有权限操作',
    '0x036019'                               => '项目已结束,不可以新建任务',
    '0x036020'                               => '项目审核中不支持该操作',
    '0x036021'                               => '项目已结束不支持该操作',
    '0x036022'                               => '项目当前状态不支持该操作',
    '0x036023'                               => '非项目负责人不可以更改项目状态',
    '0x036024'                               => '项目数据异常，请重试',
    '0x036025'                               => '项目名称为空',
    '0x036026'                               => '项目周期结束时间为空',
    '0x036027'                               => '项目周期开始时间为空',
    '0x036028'                               => '项目周期结束时间不能小于项目周期开始时间',
    '0x036030'                               => '任务周期结束时间不能小于任务周期始时间',
    '0x036029'                               => '权限配置异常:flag',
    "secondary"                              => "次要",
    "commonly"                               => "一般",
    "important"                              => "重要",
    "very_important"                         => "非常重要",
    "very_low"                               => "极低",
    "low"                                    => "低",
    "in"                                     => "中",
    "high"                                   => "高",
    "higher"                                 => "较高",
    "modify_task_id_schedule"                => '修改任务Id=:task_id的进度为::task_persent%',
    "modify_project_id_state"                => '修改项目Id=:task_id的状态为::task_status',
    "unsubmitted"                            => "未提交",
    "submission"                             => "已提交",
    "in_the_process_of_processing"           => "处理中",
    "already_processed"                      => "已处理",
    "unsolved"                               => "未解决",
    "resolved"                               => "已解决",
    "in_the_project"                         => "立项中",
    "examination_and_approval"               => "审批中",
    "retreated"                              => "已退回",
    "have_in_hand"                           => "进行中",
    "finished"                               => "已结束",
    "number"                                 => "数量",
    "project_creator"                        => "项目创建人",
    "project_leader"                         => "项目负责人",
    "project_type"                           => "项目类型",
    "emergency_degree"                       => "紧急程度",
    "priority_level"                         => "优先级别",
    "project_status"                         => "项目状态",
    "project_cycle"                          => "项目周期",
    "project_completed_progress_percent"     => "项目完成进度(%)",
    "other"                                  => "其他",
    "priority"                               => "优先级",
    "no_auditor_can_not_submission_of_audit" => "此项目没有审批人，不能提交审核",
    "task"                                   => "任务",
    "team"                                   => "团队",
    "discuss"                                => "讨论",
    "problem"                                => "问题",
    "question"                                => "问题",
    "file"                                   => "文档",
    "document"                                   => "文档",
    "gantt_chart"                            => "甘特图",
    "assessment"                             => "考核",
    "detail"                                 => "详情",
    "manager"                                => "管理的",
    "monitor"                                => "监控的",
    "executor"                               => "执行的",
    "project"                                => "项目",
    "log" => [
        'name' => '日志',
        "actions" => [
            'add' => '新增',
            'modify' => '修改',
            'delete' => '删除',
            'proExamine' => '提交审核',
            'proApprove' => '审核通过',
            'proRefuse' => '审核退回',
            'proEnd' => '结束项目',
            'proRestart' => '重启项目',
        ],
        'editType' => [
            'person' => '人员变动',
            'date' => '日期更改',
            'percent' => '进度变更',
            'other' => '其它',
            'manager_state' => '项目状态变更',
        ],
        'add' => '新增',
        'remove' => '移除',
    ],
    "fields" => [
        'task_name' => '任务名称',
        'task_persondo' => '任务执行人',
        'task_begintime' => '开始时间',
        'task_endtime' => '结束时间',
        'task_persent' => '任务进度',
        'manager_state' => '项目状态',
    ],
    'task_front_not_exists' => '前置任务不存在',
    'not_begin' => '未开始',
    "have_in_hand_overdue"  => "【逾期】进行中",
    "finished_overdue"  => "【逾期】已结束",
    "doc_name"  => "文档名称",
    "public_dir_name"  => "公共文件夹",
    "complete"  => "完成",
    "not_complete"  => "未完成",
    "role" => [
        "team_person" => "项目团队",
        "doc_creater" => "文档创建人",
        "question_person" => "问题提出人",
        "question_doperson" => "问题处理人",
        "question_creater" => "问题创建人",
        "p1_task_persondo" => "直接父级任务执行人",
        "p2_task_persondo" => "全部父级任务执行人",
    ],
    "this_field_role_exist" => "该字段已设置权限，请返回列表查看",
    "name_of_the_problem" => "问题名称",
    "processing_person" => "处理人",
    "expiry_time" => "到期时间",
];