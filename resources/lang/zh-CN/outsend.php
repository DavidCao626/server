<?php

/**
 * 数据外发语言包
 *
 */
return [
    '0x000001'                 => '子流程id为空',
    '0x000002'                 => '子流程没有创建权限',
    '0x000003'                 => '模块为空',
    '0x000004'                 => '外发数据为空',
    '0x000005'                 => '数据外发模块配置信息有误',
    '0x000006'                 => '数据库配置有误',
    '0x000007'                 => '数据库操作失败',
    '0x000008'                 => '流程信息有误',
    '0x000009'                 => '子流程创建人信息有误',
    '0x000010'                 => '数据库配置信息有误',
    '0x000011'                 => '获取数据库配置信息失败',
    '0x000012'                 => '模块外发失败',
    '0x000013'                 => '不满足触发条件',
    'base_files_error'         => '流程表单缺少控件名称为"product_type"的控件',
    'user' => [
        //模块名称，用于展示
        'title' => "用户管理",
        //流程外发处的可选字段
        'fileds' => [
            'user_accounts' => [
                'field_name' => '用户名'
            ],
            'user_name' => [
                //流程外发处的可选字段的字段名称
                'field_name' => '真实姓名',            ],
            'phone_number' => [
                'field_name' => '手机号码',
                'describe' => '手机号码'
            ],
            'user_job_number' => [
                'field_name' => '工号',
                'describe' => '工号'
            ],
            'user_password' => [
                'field_name' => '密码',
                'describe' => '用户密码'
            ],
            //'create_time' => '发布时间',
            'is_dynamic_code' => [
                'field_name' => '动态密码登录验证'
            ],
            'sn_number' => [
                'field_name' => '动态令牌序列号'
            ],
            'dynamic_code' => [
                'field_name' => '动态密码'
            ],
            'is_autohrms' => [
                'field_name' => '同步人事档案',
            ],
            'sex' => [
                'field_name' => '性别'
            ],
            'wap_allow' => [
                'field_name' => '手机访问'
            ],
            'user_status' => [
                'field_name' => '用户状态'
            ],
            'dept_id' => [
                'field_name' => '部门'
            ],
            'user_position' => [
                'field_name' => '职位'
            ],
            'attendance_scheduling' => [
                'field_name' => '考勤排班类型'
            ],
            'role_id_init' => [
                'field_name' => '角色'
            ],
            'superior_id_init' => [
                'field_name' => '上级'
            ],
            'subordinate_id_init' => [
                'field_name' => '下级'
            ],
            'post_priv' => [
                'field_name' => '管理范围'
            ],
            'post_dept' => [
                'field_name' => '管理范围(部门)'
            ],
            'list_number' => [
                'field_name' => '序号'
            ],
            'user_area' => [
                'field_name' => '区域'
            ],
            'user_city' => [
                'field_name' => '城市'
            ],
            'user_workplace' => [
                'field_name' => '职场'
            ],
            'user_job_category' => [
                'field_name' => '岗位类型'
            ],
            'birthday' => [
                'field_name' => '生日'
            ],
            'email' => [
                'field_name' => '邮箱'
            ],
            'oicq_no' => [
                'field_name' => 'QQ'
            ],
            'weixin' => [
                'field_name' => '微信'
            ],
            'dept_phone_number' => [
                'field_name' => '单位电话'
            ],
            'faxes' => [
                'field_name' => '单位传真'
            ],
            'home_address' => [
                'field_name' => '家庭地址'
            ],
            'home_zip_code' => [
                'field_name' => '家庭邮编'
            ],
            'home_phone_number' => [
                'field_name' => '家庭电话'
            ],
            'notes' => [
                'field_name' => '备注'
            ],
        ]
    ],
    'calendar'                 => [
        'title'  => '日程管理',
        'fileds' => [
            "calendar_content" => [
                'field_name' => '日程内容',
            ],
            "calendar_type_id" => [
                'field_name' => '日程类型'
            ],
            'calendar_begin'    => [
                'field_name' => '日程开始时间',
                'describe'   => '格式为xxxx-xx-xx',
            ],
            'calendar_end'      => [
                'field_name' => '日程结束时间',
                'describe'   => '格式为xxxx-xx-xx',
            ],
            'calendar_address' => [
                'field_name' => '位置'
            ],
            'handle_user'           => [
                'field_name' => '办理人',
                'describe'   => '办理人ID',
            ],
            'share_user'        => [
                'field_name' => '共享人',
                'describe'   => '共享人ID',
            ],
            'calendar_level'    => [
                'field_name' => '紧急程度',
                'describe'   => '紧急程度',
            ],
            'allow_remind' => [
                'field_name' => '是否提醒',
                'describe'   => '是否提醒'
            ],
            'remind_now' => [
                'field_name' => '立即提醒',
                'describe'   => '立即提醒'
            ],
            'start_remind' => [
                'field_name' => '开始提醒',
                'describe'   => '开始提醒'
            ],
            'end_remind' => [
                'field_name' => '结束提醒',
                'describe'   => '结束提醒'
            ],
            'repeat'            => [
                'field_name' => '是否重复',
                'describe'   => '是否重复',
            ],
            'repeat_type'       => [
                'field_name' => '重复类型',
                'describe'   => '重复类型',
            ],
            'repeat_circle'     => [
                'field_name' => '重复周期',
                'describe'   => '重复周期',
            ],
            'remind' => [
                'field_name' => '是否提醒',
                'describe'   => '是否提醒'
            ],
            'repeat_end_type'   => [
                'field_name' => '重复结束类型',
                'describe'   => '重复结束类型',
            ],
            'start_remind_h' => [
                'field_name' => '日程开始提醒小时',
                'describe'   => '提醒时间'
            ],
            'start_remind_m' => [
                'field_name' => '日程开始提醒分钟',
                'describe'   => '提醒时间'
            ],
            'end_remind_h' => [
                'field_name' => '日程结束提醒小时',
                'describe'   => '提醒时间'
            ],
            'end_remind_m' => [
                'field_name' => '日程结束提醒分钟',
                'describe'   => '提醒时间'
            ],
            'repeat_end_number' => [
                'field_name' => '日程重复结束次数',
                'describe'   => '重复结束次数',
            ],
            'repeat_end_date'   => [
                'field_name' => '日程重复结束日期',
                'describe'   => '重复结束日期',
            ],
            'calendar_remark'   => [
                'field_name' => '备注',
                'describe'   => '备注',
            ],
            'attachment_id'     => [
                'field_name' => '附件',
                'describe'   => '附件',
            ],
            'flow_id' => [
                'field_name' => '定义流程ID',
                'describe' => '定义流程ID'
            ],
            'run_id' => [
                'field_name' => '运行流程ID',
                'describe' => '运行流程ID'
            ],
            'flow_name' => [
                'field_name' => '流程名称',
                'describe' => '流程名称'
            ],
        ]
    ],
    'news'                     => [
        'title'  => "新闻管理",
        'fileds' => [
            'title' => [
                'field_name' => '新闻标题',
            ],
            'news_type_id'  => [
                'field_name' => '新闻类型',
                'describe'   => '系统中存在的新闻类型id，必须为整数',
            ],
            'top'           => [
                'field_name' => '置顶',
                'describe'   => '值为1时置顶，值为0是不置顶',
            ],
            'top_end_time'  => [
                'field_name' => '置顶有效期',
                'describe'   => '置顶值为1时此属性才有效，格式为"2017-01-20 12:14:01"',
            ],
            'allow_reply'   => [
                'field_name' => '评论开关',
                'describe'   => '值为1时允许评论，值为0时不允许评论',
            ],
            'create_time'   => '发布时间',
            'content' => [
                'field_name' => '新闻内容',
            ],
            'publish'       => [
                'field_name' => '新闻状态',
                'describe'   => '值为0为草稿状态，值为1为发布状态，值为2为审核状态',
            ],
            'creator'       => [
                'field_name' => '发布人',
                'describe'   => '发布人id',
            ],
            'attachment_id' => [
                'field_name' => '形象图片',
            ],
        ],
    ],
    'notify'                   => [
        'title'  => "公告管理",
        'fileds' => [
            'subject' => [
                'field_name' => '公告标题',
            ],
            'content' => [
                'field_name' => '公告内容',
            ],
            'notify_type_id' => [
                'field_name' => '公告类别',
                'describe'   => '公告类别id',
            ],
            'begin_date'     => [
                'field_name' => '生效日期',
                'describe'   => '格式为xxxx-xx-xx',
            ],
            'end_date'       => [
                'field_name' => '结束日期',
                'describe'   => '格式为xxxx-xx-xx',
            ],
            'publish'        => [
                'field_name' => '公告状态',
                'describe'   => '值为0为草稿状态，值为1为发布状态，值为2为审核状态',
            ],
            'allow_reply' => [
                'field_name' => '允许评论',
                'describe' => '0为不允许，1为允许'
            ],
            'top' => [
                'field_name' => '置顶',
                'describe' => '0为不置顶，1为置顶'
            ],
            'top_end_time' => [
                'field_name' => '置顶结束时间',
                'describe' => '置顶结束时间',
            ],
            'open_unread_after_login' => [
                'field_name' => '登录后提醒',
                'describe' => '0为不提醒，1为提醒'
            ],
            'priv_scope'     => [
                'field_name' => '发布范围',
                'describe'   => '1表示全体,0表示规定范围,此时需规定部门或角色或用户',
            ],
            'dept_id'        => [
                'field_name' => '部门',
                'describe'   => '部门id组成的数组',
            ],
            'role_id'        => [
                'field_name' => '角色',
                'describe'   => '角色id组成的数组',
            ],
            'user_id'        => [
                'field_name' => '用户',
                'describe'   => '用户id组成的数组',
            ],
            'from_id'        => [
                'field_name' => '创建人',
                'describe'   => '创建人id',
            ],
            'attachment_id'  => [
                'field_name' => '附件',
                'describe'   => '附件id',
            ],
            'creator_type' => [
                'field_name' => '发布单位',
            ],
            'creator_id' => [
                'field_name' => '发布人员',
            ],
            'department_id' => [
                'field_name' => '发布部门',
            ],
        ],
    ],
    'charge'                   => [
        'title'  => "费用录入",
        'fileds' => [
            'user_id'              => [
                'field_name' => '报销人',
            ],
            'dept_id'              => [
                'field_name' => '费用承担部门',
            ],
            'charge_type_parentid' => [
                'field_name' => '主科目',
            ],
            'charge_type_id'       => [
                'field_name' => '报销科目',
            ],
            'charge_cost'          => [
                'field_name' => '报销额度',
            ],
            'reason'               => [
                'field_name' => '事由',
            ],
            'payment_date'         => [
                'field_name' => '报销日期',
            ],
            'charge_undertaker'    => [
                'field_name' => '费用承担者',
                'describe'   => '1 个人 2 部门',
            ],
            'project_id'           => [
                'field_name' => '项目',
            ],
            'undertake_user' =>[
                'field_name' => '费用承担用户',
            ],
            'run_id'               => '运行流程ID',
            'run_name'             => '流程名称',
            'flow_id'              => '定义流程ID',
        ],
    ],
    'leave'                    => [
        'title'  => "请假",
        'fileds' => [
            'user_id'          => [
                'field_name' => '用户ID',
                'describe'   => '请假申请人用户ID',
            ],
            'vacation_id'      => [
                'field_name' => '假期类型',
            ],
            'create_time'      => [
                'field_name' => '创建时间',
            ],
            'leave_start_time' => [
                'field_name' => '请假开始时间',
            ],
            'leave_end_time'   => [
                'field_name' => '请假结束时间',
            ],
            'leave_days'       => [
                'field_name' => '请假天数',
            ],
            'leave_hours'       => [
                'field_name' => '请假小时数',
            ],
            'leave_reason'     => [
                'field_name' => '请假原因',
            ],
            'run_id'           => '流程运行ID',
            'run_name'         => '流程运行名称',
            'flow_id'          => '定义流程ID',
        ],
    ],
    'out'                      => [
        'title'  => "外出",
        'fileds' => [
            'user_id'        => [
                'field_name' => '用户ID',
                'describe'   => '外出申请人用户ID',
            ],
            'create_time'    => [
                'field_name' => '创建时间',
            ],
            'out_start_time' => [
                'field_name' => '外出开始时间',
            ],
            'out_end_time'   => [
                'field_name' => '外出结束时间',
            ],
            'out_reason'     => [
                'field_name' => '外出原因',
            ],
            'run_id'         => '流程运行ID',
            'run_name'       => '流程运行名称',
            'flow_id'        => '定义流程ID',
        ],
    ],
    'overtime'                 => [
        'title'  => "加班",
        'fileds' => [
            'user_id'             => [
                'field_name' => '用户ID',
                'describe'   => '加班申请人用户ID',
            ],
            'create_time'         => [
                'field_name' => '创建时间',
            ],
            'overtime_start_time' => [
                'field_name' => '加班开始时间',
            ],
            'overtime_end_time'   => [
                'field_name' => '加班结束时间',
            ],
            'overtime_to' => [
                'field_name' => '加班补偿方式',
            ],
            'overtime_reason'     => [
                'field_name' => '加班原因',
            ],
            'overtime_days'       => [
                'field_name' => '加班天数',
            ],
            'overtime_hours'       => [
                'field_name' => '加班小时数',
            ],
            'run_id'              => '流程运行ID',
            'run_name'            => '流程运行名称',
            'flow_id'             => '定义流程ID',
        ],
    ],
    'trip'                     => [
        'title'  => "出差",
        'fileds' => [
            'user_id'         => [
                'field_name' => '用户ID',
                'describe'   => '出差申请人用户ID',
            ],
            'create_time'     => [
                'field_name' => '创建时间',
            ],
            'trip_start_date' => [
                'field_name' => '出差开始日期',
            ],
            'trip_end_date'   => [
                'field_name' => '出差结束日期',
            ],
            'trip_reason'     => [
                'field_name' => '出差原因',
            ],
            'trip_area'       => [
                'field_name' => '出差地区',
            ],
            'run_id'          => '流程运行ID',
            'run_name'        => '流程运行名称',
            'flow_id'         => '定义流程ID',
        ],
    ],
    'repair' => [
        'title' => "补卡",
        'fileds' => [
            'user_id' => [
                'field_name' => '用户ID',
                'describe' => '补卡申请人用户ID',
                'required' => 1
            ],
            'create_time' => [
                'field_name' => '创建时间',
            ],
            'repair_date' => [
                'field_name' => '考勤日期',
                'required' => 1
            ],
            'sign_times' => [
                'field_name' => '补卡时间段',
                'required' => 1
            ],
            'repair_reason' => [
                'field_name' => '补卡原因',
            ],
            'repair_type' => [
                'field_name' => '补卡类型',
            ],
            'run_id' => '流程运行ID',
            'run_name' => '流程运行名称',
            'flow_id' => '定义流程ID',
        ]
    ],
    'backLeave' => [
        'title' => "销假",
        'fileds' => [
            'user_id' => [
                'field_name' => '用户ID',
                'describe' => '销假申请人用户ID',
                'required' => 1
            ],
            'create_time' => [
                'field_name' => '创建时间',
            ],
            'leave_id' => [
                'field_name' => '请假记录',
                'required' => 1
            ],
            'back_leave_reason' => [
                'field_name' => '销假原因',
            ],
            'run_id' => '流程运行ID',
            'run_name' => '流程运行名称',
            'flow_id' => '定义流程ID',
        ],
    ],
    'meeting'                  => [
        'title'  => "会议室管理",
        'fileds' => [
            'meeting_apply_user'        => [
                'field_name' => '申请人',
                'describe'   => '申请人用户ID',
            ],
            'meeting_subject'           => [
                'field_name' => '会议主题',
            ],
            'attence_type' => [
                'field_name' => '参会方式',
            ],
            'interface_id' => [
                'field_name' => '会议接口'
            ],
            'meeting_room_id'           => [
                'field_name' => '会议室',
                'describe'   => '会议室名称',
            ],
            'meeting_type'              => [
                'field_name' => '会议类型',
                'describe'   => '会议类型',
            ],
            'meeting_begin_time'        => [
                'field_name' => '会议开始时间',
            ],
            'meeting_end_time'          => [
                'field_name' => '会议结束时间',
            ],
            'meeting_reminder_timing_h' => [
                'field_name' => '会议开始提醒小时',
                'describe'   => '提醒时间',
            ],
            'meeting_reminder_timing_m' => [
                'field_name' => '会议开始提醒分钟',
                'describe'   => '提醒时间',
            ],
            'meeting_response'          => [
                'field_name' => '会议回执',
            ],
            'sign'                      => [
                'field_name' => '会议签到',
            ],
            'sign_type'                 => [
                'field_name' => '签到方式',
            ],
            'meeting_sign_user'         => [
                'field_name' => '签到人员',
            ],
            'meeting_sign_wifi'         => [
                'field_name' => '签到WiFi',
            ],
            'meeting_external_user'     => [
                'field_name' => '参会客户',
            ],
            'external_reminder_type'    => [
                'field_name' => '参会客户提醒方式',
            ],
            'meeting_join_member'       => [
                'field_name' => '参加人员',
            ],
            'meeting_other_join_member' => [
                'field_name' => '其他参加',
            ],
            'meeting_remark'            => [
                'field_name' => '申请备注',
                'describe'   => '会议申请备注',
            ],
            'meeting_approval_opinion'  => [
                'field_name' => '审批意见',
                'describe'   => '审批意见',
            ],
            'attachment_id'             => [
                'field_name' => '附件',
                'describe'   => '会议申请附件',
            ],
        ],
    ],
    'vehiclesApply'                 => [
        'title'  => "用车申请",
        'fileds' => [
            'vehicles_apply_apply_user' => [
                'field_name' => '申请人',
                'describe' => '申请人用户ID',
            ],
            'vehicles_apply_approval_user'   => [
                'field_name' => '审批人',
                'describe'   => '审批人用户ID，对应流程提交人ID',
            ],
            'vehicles_id'                    => [
                'field_name' => '车辆名称',
                'describe'   => '车辆名称(车牌号)',
            ],
            'vehicles_apply_begin_time'      => [
                'field_name' => '用车开始时间',
            ],
            'vehicles_apply_end_time'        => [
                'field_name' => '用车结束时间',
            ],
            'vehicles_apply_path_start'      => [
                'field_name' => '出发地',
            ],
            'vehicles_apply_path_end'        => [
                'field_name' => '目的地',
            ],
            'vehicles_apply_mileage'         => [
                'field_name' => '里程',
            ],
            'vehicles_apply_oil'             => [
                'field_name' => '油耗',
            ],
            'vehicles_apply_reason'          => [
                'field_name' => '事由',
            ],
            'vehicles_apply_remark'          => [
                'field_name' => '备注',
            ],
            'vehicles_apply_approval_remark' => [
                'field_name' => '审批意见',
                'describe'   => '审批意见',
            ],
            'attachment_id'                  => [
                'field_name' => '附件',
                'describe'   => '用车申请附件',
            ],
        ],
    ],
    'officeSuppliesApply'      => [
        'title'  => "办公用品申请",
        'fileds' => [
            'apply_user'         => [
                'field_name' => '申请人',
                'describe'   => '申请人用户ID'
            ],
            'office_supplies_id' => [
                'field_name' => '办公用品',
                'describe'   => '办公用品ID'
            ],
            'apply_number'       => [
                'field_name' => '使用数量'
            ],
            'receive_date'       => [
                'field_name' => '领用日期'
            ],
            'return_date'        => [
                'field_name' => '归还日期'
            ],
            'apply_type'         => [
                'field_name' => '使用类型',
                'describe'   => '使用类型ID'
            ],
            'receive_way'        => [
                'field_name' => '领取方式',
                'describe'   => '领取方式ID'
            ],
            'explan'             => [
                'field_name' => '申请说明',
                'describe'   => '申请说明'
            ],
            'approval_opinion'   => [
                'field_name' => '审批意见',
                'describe'   => '审批意见'
            ],
        ],
    ],
    'incomeexpense'            => [
        'title'  => "收支记录",
        'fileds' => [
            'plan_id'        => [
                'field_name' => '方案ID'
            ],
            'income'         => [
                'field_name' => '收入金额'
            ],
            'expense'        => [
                'field_name' => '支出金额'
            ],
            'income_reason'  => [
                'field_name' => '收入事由'
            ],
            'expense_reason' => [
                'field_name' => '支出事由'
            ],
            'attachment_id' => [
                'field_name' => '附件'
            ],
            'run_id' => [
                'field_name' => '流程运行ID'
            ],
            'run_name' => [
                'field_name' => '流程运行名称'
            ],
            'flow_id' => [
                'field_name' => '定义流程ID'
            ],
            'creator' => [
                'field_name' => '创建人'
            ],
            'record_time' => [
                'field_name' => '收支生成时间'
            ],
            'record_desc' => [
                'field_name' => '备注'
            ],
        ],
    ],
    'diary' => [
        'title' => "微博日志",
        'parent_menu'=>189,
        'fileds' => [
            'plan_content'           => [
                'field_name' => '微博内容',
            ],
            'kind_id'        => [
                'field_name' => '报告种类',
            ],
            'cycle' => '周期',
            'creator'        => [
                'field_name' => '创建人',
            ],
            'attachment_id'         => '附件',
        ]
    ],
    'incomeexpensePlan'        => [
        'title'  => "新建方案",
        'fileds' => [
            'plan_code' => [
                'field_name' => '方案ID',
            ],
            'plan_name' => [
                'field_name' => '方案名称',
            ],
            'plan_type_id'         => '方案类别',
            'expense_budget_alert' => '支出预算超额提醒',
            'plan_cycle'           => '计划执行周期',
            'plan_cycle_unit'      => '周期单位',
            'is_start'             => '是否启动方案',
            'creator' => [
                'field_name' => '创建人'
            ],
            'expense_budget'       => '支出预算',
            'income_budget'        => '收入预算',
            'plan_description'     => '方案描述',
            'attachment_id'        => '附件',
            'all_user'             => [
                'field_name' => '发布范围',
            ],
            'dept_id'              => [
                'field_name' => '部门',
            ],
            'role_id'              => [
                'field_name' => '角色',
            ],
            'user_id'              => [
                'field_name' => '用户',
            ],
        ],
    ],
    'vacationOvertime'         => [
        'title'  => "加班外发",
        'fileds' => [
            'user_id' => [
                'field_name' => '加班申请人Id',
            ],
            'days' => [
                'field_name' => '加班时长',
            ]
        ],
    ],
    'vacationLeave'            => [
        'title'  => "请假外发",
        'fileds' => [
            'user_id' => [
                'field_name' => '请假申请人Id',
            ],
            'days' => [
                'field_name' => '请假时长',
            ],
            'vacation_type' => [
                'field_name' => '请假类型',
            ]
        ],
    ],
    'contactRecord'            => [
        'title'  => "联系记录",
        'fileds' => [
            'customer_id' =>[
                'field_name' => '客户id',
            ] ,
            'linkman_id' => [
                'field_name' => '联系人id',
            ],
            'record_type' => [
                'field_name' => '联系类型',
            ],
            'record_start' => [
                'field_name' => '联系开始时间',
            ],
            'record_end' => [
                'field_name' => '联系结束时间',
            ],
            'record_creator' => [
                'field_name' => '创建人id',
            ],
            'record_content' => [
                'field_name' => '联系内容',
            ],
            'attachment_id' => [
                'field_name' => '联系附件',
            ],
        ],
    ],
    'contract'                 => [
        'title'  => "合同",
        'fileds' => [
            'contract_name'               => '合同名称',
            'contract_number'             => '合同编号',
            'contract_amount'             => '合同金额',
            'customer_id'                 => '客户id',
            'contract_type'               => '合同类型',
            'contract_start'              => '合同开始日期',
            'contract_end'                => '合同结束日期',
            'contract_creator'            => '创建人id',
            'contract_remarks'            => '合同备注',
            'first_party'                 => '甲方签字人',
            'first_party_signature_date'  => '甲方签字日期',
            'second_party'                => '乙方签字人',
            'second_party_signature_date' => '乙方签字日期',
            'attachment_ids'              => '附件',
            'supplier_id'                 => '供应商',
            'remindDate'                  => '到期提醒时间',
        ],
    ],
    'salesRecords'             => [
        'title'  => "销售记录",
        'fileds' => [
            'user_id' => [
                'field_name' => '创建人id',
            ],
            'customer_id' => [
                'field_name' => '客户id',
            ],
            'product_id' => [
                'field_name' => '产品id',
            ],
            'sales_amount' => [
                'field_name' => '销售数量',
            ],
            'sales_price' => [
                'field_name' => '销售价格',
            ],
            'sales_date' => [
                'field_name' => '销售日期',
            ],
            'discount' => [
                'field_name' => '折扣',
            ],
            'salesman' => [
                'field_name' => '销售员',
            ],
            'sales_remarks' => [
                'field_name' => '备注',
            ]
        ],
    ],
    'outStock'                 => [
        'title'  => "出库",
        'fileds' => [
            'product_id' => [
                'field_name' => '产品Id',
            ],
            'total_count' => [
                'field_name' => '库存量',
            ],
            'warehouse_id' => [
                'field_name' => '仓库id',
            ],
            'count' => [
                'field_name' => '出库量',
            ],
            'product_batchs' => [
                'field_name' => '产品批次',
            ],
            'rel_run_id' => [
                'field_name' => '关联流程id',
            ],
            'rel_contract_id' => [
                'field_name' => '关联合同id',
            ],
            'run_id'            => [
                'field_name' => '流程运行ID',
            ],
            'run_name'          => [
                'field_name' => '流程运行名称',
            ],
            'flow_id'           => [
                'field_name' => '定义流程ID',
            ]
        ],
    ],
    'inStock'                  => [
        'title'  => "入库",
        'fileds' => [
            'product_id' => [
                'field_name' => '产品Id',
            ],
            'product_date' => [
                'field_name' => '生产日期',
            ],
            'product_batchs' => [
                'field_name' => '产品批次',
            ],
            'warehouse_id' => [
                'field_name' => '仓库id',
            ],
            'count' => [
                'field_name' => '入库量',
            ],
            'rel_run_id' => [
                'field_name' => '关联流程id',
            ],
            'rel_contract_id' => [
                'field_name' => '关联合同id',
            ],
            'run_id'            => [
                'field_name' => '流程运行ID',
            ],
            'run_name'          => [
                'field_name' => '流程运行名称',
            ],
            'flow_id'           => [
                'field_name' => '定义流程ID',
            ]
        ],
    ],

    'salaryAdjust'             => [
        'title'  => "薪资调整",
        'fileds' => [
            'user_id'           => [
                'field_name' => '被调整人',
            ],
            'creator'           => [
                'field_name' => '调整人',
            ],
            'field_name'        => [
                'field_name' => '薪资项',
            ],
            'adjust_value'      => [
                'field_name' => '调整金额',
            ],
            'adjust_type'       => [
                'field_name' => '调整方式',
            ],
            'field_default_old' => [
                'field_name' => '调整前薪资',
            ],
            'field_default_new' => [
                'field_name' => '调整后薪资',
            ],
            'adjust_reason'     => [
                'field_name' => '调整原因',
            ],
            'run_id'   => [
                'field_name' => '运行流程ID',
            ],
            'run_name' => [
                'field_name' => '流程名称',
            ],
            'flow_id'  => [
                'field_name' => '定义流程ID',
            ]
        ],
    ],
    'from_type_160_id'         => '项目管理',
    'from_type_160_base_files' => '项目类型',
    'from_type_160'            => [
        'title'      => '项目管理',
        'id'         => '160_project',
        'baseFiles'  => '项目类型',
        'baseId'     => 'project_type',
        'otherFiles' => ['project_state' => '项目状态'],
        'additional' => [
            'id'     => '160_project_additional',
            'title'  => '任务管理',
            'fields' => [
                "task_name"      => [
                    'field_name' => '任务名称',
                ],
                "sort_id"        => [
                    'field_name' => '排序级别',
                ],
                "task_persondo"  => [
                    'field_name' => '任务执行人',
                ],
                "task_begintime" => [
                    'field_name' => '计划周期开始',
                ],
                "task_endtime"   => [
                    'field_name' => '计划周期结束',
                ],
                "task_level"     => [
                    'field_name' => '任务级别',
                ],
                "task_mark"      => [
                    'field_name' => '是否标记为里程碑',
                ],
                "task_explain"   => [
                    'field_name' => '任务描述',
                ],
                "task_remark"    => [
                    'field_name' => '备注',
                ],
                "attachments"    => [
                    'field_name' => '附件',
                ],
                "task_creater"   => [
                    'field_name' => '任务创建人',
                ],
                "creat_time"     => [
                    'field_name' => '任务创建时间',
                ]
            ],
        ],
    ],
    'from_type_530_id'         => '任务管理',
    'from_type_530'            => [
        'title'      => '任务管理',
        'id'         => '530_task',
        'additional' => [
            'id'     => '530_task_additional',
            'title'  => '子任务管理',
            'fields' => [
                'task_name' => [
                    'field_name' => '任务名称',
                ],
                'create_user' => [
                    'field_name' => '创建人',
                ],
                'manage_user' => [
                    'field_name' => '负责人',
                ],
                'joiner' => [
                    'field_name' => '参与人',
                ],
                'shared' => [
                    'field_name' => '共享人',
                ],
                'task_description' => [
                    'field_name' => '任务详情',
                ],
                'start_date' => [
                    'field_name' => '开始日期',
                ],
                'end_date' => [
                    'field_name' => '到期日',
                ],
                'important_level' => [
                    'field_name' => '重要程度',
                ],
                'attachment_ids' => [
                    'field_name' => '附件',
                ],
                'parent_id' => [
                    'field_name' => '主任务',
                ]
            ],
        ],
    ],
    'contractProject'          => [
        'title'  => "结算情况",
        'fileds' => [
            "contract_t_id" => [
                'field_name' => '合同',
            ],
            "type" => [
                'field_name' => '款项性质',
            ],
            "pay_type" => [
                'field_name' => '款项类别',
            ],
            "money" => [
                'field_name' => '款项金额（元）',
            ],
            "pay_way" => [
                'field_name' => '打款方式',
            ],
            "pay_account" => [
                'field_name' => '打款账号',
            ],
            "pay_time" => [
                'field_name' => '打款时间',
            ],
            "invoice_time" => [
                'field_name' => '开票时间',
            ],
            "run_id" => [
                'field_name' => '关联流程',
            ],
            "remarks" => [
                'field_name' => '备注',
            ]
        ],
    ],
    'contractOrder'            => [
        'title'  => "订单",
        'fileds' => [
            "contract_t_id" => [
                'field_name' => '合同',
            ],
            "product_id" => [
                'field_name' => '产品名称',
            ],
            "shipping_date" => [
                'field_name' => '发货时间',
            ],
            "number" => [
                'field_name' => '发货数量',
            ],
            "run_ids" => [
                'field_name' => '关联流程',
            ],
            "remarks" => [
                'field_name' => '备注',
            ]
        ],
    ],
    'contractRemind'           => [
        'title'       => "提醒计划",
        'parent_menu' => 150,
        'fileds'      => [
            "contract_t_id" => [
                'field_name' => '合同',
            ],
            "user_id" => [
                'field_name' => '提醒对象',
            ],
            "remind_date" => [
                'field_name' => '提醒日期',
            ],
            "content" => [
                'field_name' => '提醒内容',
            ],
            "remarks" => [
                'field_name' => '备注',
            ]
        ],
    ],
    'assetsStorage'            => [
        'title'  => "资产入库",
        'fileds' => [
            'assets_name'     => '资产名称',
            'product_at'      => '生产时间',
            'price'           => '产品价格(元)',
            'type'            => '所属分类',
            'user_time'       => '使用时长(月)',
            'residual_amount' => '预计净残值',
            'managers'        => '管理员',
            'run_id'          => '关联流程',
            'is_all'          => '使用范围',
            'dept'            => '使用部门',
            'role'            => '使用角色',
            'users'           => '使用人员',
            'operator_id'     => '登记操作员',
            'remark'          => '备注',
            'attachment_id'   => '物品图片',
        ],
    ],

    'assetsApply'              => [
        'title'  => "资产申请",
        'fileds' => [
            'id'            => [
                'field_name' => '资产名称',
            ],
            'apply_way'     => [
                'field_name' => '申请方式',
            ],
            'receive_at'    => [
                'field_name' => '领用时间',
            ],
            'return_at'     => [
                'field_name' => '归还时间',
            ],
            'apply_user'    => [
                'field_name' => '申请人',
            ],
            'approver'      => [
                'field_name' => '审批人',
            ],
            'run_ids'       => [
                'field_name' => '关联流程',
            ],
            'remark'        => [
                'field_name' => '备注',
            ]
        ],
    ],
    'assetsRepair'             => [
        'title'  => "资产维护",
        'fileds' => [
            'id'              => [
                'field_name' => '资产名称',
            ],
            'apply_user'      => [
                'field_name' => '申请人',
            ],
            'operator'        => [
                'field_name' => '操作人',
            ],
            'remark'          => [
                'field_name' => '备注',
            ],
            'run_id'          => [
                'field_name' => '关联流程',
            ]
        ],
    ],
    'assetsRet'                => [
        'title'  => "资产退库",
        'fileds' => [
            'id'              => [
                'field_name' => '资产名称',
            ],
            'apply_user'      => [
                'field_name' => '退库操作人',
            ],
            'remark'          => [
                'field_name' => '备注',
            ],
            'run_id'          => [
                'field_name' => '关联流程',
            ]
        ],
    ],
    'assetsInventory'           => [
        'title'  => "资产盘点",
        'fileds'=> [
            'name'            => [
                'field_name' => '盘点名称',
            ],
            'apply_user'      => [
                'field_name' => '当前领用用户',
            ],
            'start_at'        => [
                'field_name' => '资产登记开始时间',
            ],
            'end_at'          => [
                'field_name' => '资产登记结束时间',
            ],
            'type'            => [
                'field_name' => '所属分类',
            ],
            'managers'        => [
                'field_name' => '管理员',
            ],
            'created_at'      => [
                'field_name' => '盘点生成时间',
            ],
            'operator'        => [
                'field_name' => '盘点操作人',
            ],
            'run_id'          => [
                'field_name' => '关联流程',
            ]
        ],
    ],
    'vehiclesMaintenance' => [
        'title' => "用车维护",
        'parent_menu'=>600,
        'fileds' => [
            'vehicles_id' => [
                'field_name' => '车辆名称',
                'describe' => '车辆名称(车牌号)'
            ],
            'vehicles_maintenance_type' => [
                'field_name' => '维护类型',
            ],
            'vehicles_maintenance_begin_time' => [
                'field_name' => '用车维护开始时间',
            ],
            'vehicles_maintenance_end_time' => [
                'field_name' => '用车维护结束时间',
            ],
            'vehicles_maintenance_price' => [
                'field_name' => '维护费用(元)',
            ],
            'vehicles_maintenance_project' => [
                'field_name' => '维护项目',
            ],
            'vehicles_maintenance_remark' => [
                'field_name' => '备注',
            ],
        ],
        'dataOutSend' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'addVehiclesMaintenanceByFlowOutSend'],
    ],
    'task'                     => [
        'title'  => "任务管理",
        'fileds' => [
            'task_name' => [
                'field_name' => '任务名称',
            ],
            'parent_id' => [
                //流程外发处的可选字段的字段名称
                'field_name' => '主任务',
                //流程外发处的可选字段的取值描述
                'describe' => '系统中存在的主任务id，必须为整数'
            ],
            'create_user' => [
                'field_name' => '创建人',
                'describe' => '创建人id',
            ],
            'manage_user' => [
                'field_name' => '负责人',
                'describe' => '负责人id，默认为创建人'
            ],
            'joiner' => [
                'field_name' => '参与人',
                'describe' => '参与人id数组'
            ],
            'shared' => [
                'field_name' => '共享人',
                'describe' => '共享人id数组'
            ],
            'task_description' => [
                'field_name' => '任务详情',
            ],
            'start_date' => [
                'field_name' => '开始日期',
            ],
            'end_date' => [
                'field_name' => '结束日期',
            ],
            'important_level' => [
                'field_name' => '重要程度',
                'describe' => '值为0为普通，值为1为重要，值为2位紧急'
            ],
            'attachment_ids' => [
                'field_name' => '附件',
            ],
        ],
    ],
    'chargeAlert'                   => [
        'title'  => "费用预警",
        'fileds' => [
            'set_type'              => [
                'field_name' => '预警对象类型',
            ],
            'dept_id' => [
                'field_name' => '部门'
            ],
            'user_id' => [
                'field_name' => '用户'
            ],
            'role_id' => [
                'field_name' => '角色'
            ],
            'project_id' => [
                'field_name' => '项目'
            ],
            'alert_method' => [
                'field_name' => '预警方式',
            ],
            'alert_data_start'       => [
                'field_name' => '预警开始日期',
            ],
            'alert_data_end'          => [
                'field_name' => '预警结束日期',
            ],
            'subject_check'               => [
                'field_name' => '预警金额类型',
            ],
            'alert_value'               => [
                'field_name' => '预警金额',
            ],
            'alert_subject'         => [
                'field_name' => '预警科目',
            ]
        ],
    ],
];
