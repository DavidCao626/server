<?php

/**
 * 流程数据外发-模块外发配置
 *
 * 1.fileds数组中的 required 属性是定义外发时该字段是否为必填
 * 2.本表中会被使用的内容是module部分即系统模块外发参数，custom_module部分即用户模块会去读取Redis中的参数
 * 3.实际上本表中的module各模块属性并未真正使用，而是读取了一下每个参数的格式（数组/字符串），
 *   然后 trans resources/lang中的outsend.php文件中同一个参数的内容，因此在此文件中修改某个参数的内容时，
 *   如果修改了参数的格式（字符串->数组，或是数组->字符串），则必须去outsend.php文件将同一参数改成相同格式
 */

return [
    'module' => [
        //模块id
        'user' => [
            //模块名称，用于展示
            'title' => "用户管理",
            'parent_menu'=> 95,
            //流程外发处的可选字段
            'fileds' => [
                'user_accounts' => [
                    'field_name' => '用户名',
                    'required' => 1,
                ],
                'user_name' => [
                    //流程外发处的可选字段的字段名称
                    'field_name' => '真实姓名',
                    'required' => 1,
                ],
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
                    'field_name' => '动态令牌序列号',
                ],
                'dynamic_code' => [
                    'field_name' => '动态密码',
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
                    'field_name' => '用户状态',
                    'required' => 1
                ],
                'dept_id' => [
                    'field_name' => '部门',
                    'required' => 1
                ],
                'user_position' => [
                    'field_name' => '职位'
                ],
                'attendance_scheduling' => [
                    'field_name' => '考勤排班类型'
                ],
                'role_id_init' => [
                    'field_name' => '角色',
                    'required' => 1
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
            ],
            //对数据的处理方法，这里会传入前端选择的字段以及对应的流程中的值
            'dataOutSend' => ['App\EofficeApp\User\Services\UserService', 'flowOutSendToUser'],
            'dataOutSendForUpdate' => ['App\EofficeApp\User\Services\UserService', 'flowOutSendToUpdateUser'],
            'dataOutSendForDelete' => ['App\EofficeApp\User\Services\UserService', 'flowOutSendToDeleteUser']
        ],
        'news' => [
            //模块名称，用于展示
            'title' => "新闻",
            'parent_menu'=>237,
            //流程外发处的可选字段
            'fileds' => [
                'title' => [
                    'field_name' => '新闻标题',
                    'required' => 1,
                ],
                'news_type_id' => [
                    //流程外发处的可选字段的字段名称
                    'field_name' => '新闻类型',
                    //流程外发处的可选字段的取值描述
                    'describe' => '系统中存在的新闻类型id，必须为整数'
                ],
                'top' => [
                    'field_name' => '置顶',
                    'describe' => '值为1时置顶，值为0是不置顶'
                ],
                'top_end_time' => [
                    'field_name' => '置顶有效期',
                    'describe' => '置顶值为1时此属性才有效，格式为"2017-01-20 12:14:01"'
                ],
                'allow_reply' => [
                    'field_name' => '评论开关',
                    'describe' => '值为1时允许评论，值为0时不允许评论'
                ],
                //'create_time' => '发布时间',
                'content' => [
                    'field_name' => '新闻内容',
                    'required' => 1
                ],
                'publish' => [
                    'field_name' => '新闻状态',
                    'describe' => '值为0为草稿状态，值为1为发布状态，值为2为审核状态'
                ],
                'creator' => [
                    'field_name' => '发布人',
                    'describe' => '发布人id',
                    'required' => 1,
                ],
                'attachment_id' => [
                    'field_name' => '形象图片',
                ]
            ],
            //对数据的处理方法，这里会传入前端选择的字段以及对应的流程中的值
            //传人数据示例：
            //   [
            //       'news_id'=>"12",
            //       'title'=>"这是一条新闻",
            //       'news_type_id'=>"2",
            //       'top'=>"1",
            //       'top_end_time'=>"2016-02-12",
            //       'allow_reply'=>"1",
            //   ]
            'dataOutSend' => ['App\EofficeApp\News\Services\NewsService', 'flowOutSendToNews'],
            'dataOutSendForUpdate' => ['App\EofficeApp\News\Services\NewsService', 'flowOutSendToUpdateNews'],
            'dataOutSendForDelete' => ['App\EofficeApp\News\Services\NewsService', 'flowOutSendToDeleteNews'],
            'no_update_fields' => ['creator']
        ],
        'calendar' => [
            'title' => '日程',
            'parent_menu'=>26,
            'fileds' => [
                "calendar_content" => [
                    'field_name' => '日程内容',
                    'required'   => 1
                ],
                "calendar_type_id" => [
                    'field_name' => '日程类型'
                ],
                'calendar_begin' => [
                    'field_name' => '日程开始时间',
                    'describe'   => '格式为xxxx-xx-xx',
                    'required'   => 1
                ],
                'calendar_end' => [
                    'field_name' => '日程结束时间',
                    'describe'   => '格式为xxxx-xx-xx',
                    'required'   => 1
                ],
                'calendar_address' => [
                    'field_name' => '位置'
                ],
                'handle_user' => [
                    'field_name' => '办理人',
                    'describe'   => '办理人ID',
                    'required'   => 1
                ],
                'share_user' => [
                    'field_name' => '共享人',
                    'describe'   => '共享人ID'
                ],
                'calendar_level' => [
                    'field_name' => '紧急程度',
                    'describe'   => '紧急程度'
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
                'repeat' => [
                    'field_name' => '是否重复',
                    'describe'   => '是否重复'
                ],
                'repeat_type' => [
                    'field_name' => '重复类型',
                    'describe'   => '重复类型'
                ],
                'repeat_circle' => [
                    'field_name' => '重复周期',
                    'describe'   => '重复周期'
                ],
                'repeat_end_type' => [
                    'field_name' => '重复结束类型',
                    'describe'   => '重复结束类型'
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
                    'describe'   => '提醒时间'
                ],
                'repeat_end_date' => [
                    'field_name' => '日程重复结束日期',
                    'describe'   => '提醒时间'
                ],
                'calendar_remark' => [
                    'field_name' => '备注',
                    'describe'   => '备注'
                ],
                'attachment_id' => [
                    'field_name' => '附件',
                    'describe' => '附件id'
                ],
                'flow_id' => [
                    'field_name' => '定义流程ID',
                    'describe' => '流程ID'
                ],
                'run_id' => [
                    'field_name' => '运行流程ID',
                    'describe' => '流程运行ID'
                ],
                'flow_name' => [
                    'field_name' => '流程名称',
                    'describe' => '流程名称'
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Calendar\Services\CalendarService', 'flowOutSendToCalendar'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Calendar\Services\CalendarService', 'flowOutSendToUpdateCalendar'],
            'dataOutSendForDelete' => ['App\EofficeApp\Calendar\Services\CalendarService', 'flowOutSendToDeleteCalendar'],
        ],
        'notify' => [
            'title' => "公告",
            'parent_menu'=>320,
            'fileds' => [
                'subject' => [
                    'field_name' => '公告标题',
                    'required' => 1
                ],
                'content' => [
                    'field_name' => '公告内容',
                    'required' => 1,
                ],
                'notify_type_id' => [
                    'field_name' => '公告类别',
                    'describe' => '公告类别id'
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
                'begin_date' => [
                    'field_name' => '生效日期',
                    'describe' => '格式为xxxx-xx-xx',
                    'required' => 1
                ],
                'end_date' => [
                    'field_name' => '结束日期',
                    'describe' => '格式为xxxx-xx-xx'
                ],
                'publish' => [
                    'field_name' => '公告状态',
                    'describe' => '值为0为草稿状态，值为1为发布状态，值为2为审核状态'
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
                'priv_scope' => [
                    'field_name' => '发布范围',
                    'describe' => '1表示全体,0表示规定范围,此时需规定部门或角色或用户',
                    'required' => 1
                ],
                'dept_id' => [
                    'field_name' => '部门',
                    'describe' => '部门id组成的数组',
                    'required' => 1
                ],
                'role_id' => [
                    'field_name' => '角色',
                    'describe' => '角色id组成的数组',
                    'required' => 1
                ],
                'user_id' => [
                    'field_name' => '用户',
                    'describe' => '用户id组成的数组',
                    'required' => 1
                ],
                'from_id' => [
                    'field_name' => '创建人',
                    'describe' => '创建人id',
                    'required' => 1
                ],
                'attachment_id' => [
                    'field_name' => '附件',
                    'describe' => '附件id'
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Notify\Services\NotifyService', 'flowOutSendToNotify'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Notify\Services\NotifyService', 'flowOutSendToUpdateNotify'],
            'dataOutSendForDelete' => ['App\EofficeApp\Notify\Services\NotifyService', 'flowOutSendToDeleteNotify'],
            'no_update_fields' => ['from_id']
        ],
        'charge' => [
            'title' => "费用录入",
            'parent_menu'=>37,
            'fileds' => [
                'user_id' => [
                    'field_name' => '报销人',
                    'required' => 1
                ],
                'dept_id' => [
                    'field_name' => '费用承担部门',
                ],
                'charge_type_parentid' => [
                    'field_name' => '主科目'
                ],
                'charge_type_id' => [
                    'field_name' => '报销科目',
                    'required' => 1
                ],
                'charge_cost' => [
                    'field_name' => '报销额度',
                    'required' => 1
                ],
                'reason' => [
                    'field_name' => '事由',
                    'required' => 1
                ],
                'payment_date' => [
                    'field_name' => '报销日期',
                ],
                'charge_undertaker' => [
                    'field_name' => '费用承担者',
                    'describe' => '1 个人 2 部门',
                    'required' => 1
                ],
                'project_id' => [
                    'field_name' => '项目'
                ],
                'undertake_user' =>[
                    'field_name' => '费用承担用户',
                ],
                'run_id'            => '运行流程ID',
                'run_name'          => '流程名称',
                'flow_id'           => '定义流程ID',
            ],
            'dataOutSend' => ['App\EofficeApp\Charge\Services\ChargeService', 'flowOutSendToCharge'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Charge\Services\ChargeService', 'flowOutSendToChargeUpdate'],
            'dataOutSendForDelete' => ['App\EofficeApp\Charge\Services\ChargeService', 'flowOutSendToChargeDelete'],
            'no_update_fields' => ['user_id']
        ],
        'leave' => [
            'title' => "请假",
            'parent_menu'=>32,
            'fileds' => [
                'user_id' => [
                    'field_name' => '用户ID',
                    'describe' => '请假申请人用户ID',
                    'required' => 1
                ],
                'vacation_id' => [
                    'field_name' => '假期类型',
                    'required' => 1
                ],
//                'create_time' => [
//                    'field_name' => '创建时间',
//                ],
                'leave_start_time' => [
                    'field_name' => '请假开始时间',
                    'required' => 1
                ],
                'leave_end_time' => [
                    'field_name' => '请假结束时间',
                    'required' => 1
                ],
                'leave_days' => [
                    'field_name' => '请假天数',
                    'describe' => ''
                ],
                'leave_hours' => [
                    'field_name' => '请假小时数',
                    'describe' => '请假小时数'
                ],
                'leave_reason' => [
                    'field_name' => '请假原因',
                ],
                'run_id'            => '流程运行ID',
                'run_name'          => '流程运行名称',
                'flow_id'           => '定义流程ID',
            ],
            'dataOutSend' => ['App\EofficeApp\Attendance\Services\AttendanceOutSendService', 'leave'],
        ],
        'out' => [
            'title' => "外出",
            'parent_menu'=>32,
            'fileds' => [
                'user_id' => [
                    'field_name' => '用户ID',
                    'describe' => '外出申请人用户ID',
                    'required' => 1
                ],
//                'create_time' => [
//                    'field_name' => '创建时间',
//                ],
                'out_start_time' => [
                    'field_name' => '外出开始时间',
                    'required' => 1
                ],
                'out_end_time' => [
                    'field_name' => '外出结束时间',
                    'required' => 1
                ],
                'out_reason' => [
                    'field_name' => '外出原因',
                ],
                'run_id'            => '流程运行ID',
                'run_name'          => '流程运行名称',
                'flow_id'           => '定义流程ID',
            ],
            'dataOutSend' => ['App\EofficeApp\Attendance\Services\AttendanceOutSendService', 'out'],
        ],
        'overtime' => [
            'title' => "加班",
            'parent_menu'=>32,
            'fileds' => [
                'user_id' => [
                    'field_name' => '用户ID',
                    'describe' => '加班申请人用户ID',
                    'required' => 1
                ],
//                'create_time' => [
//                    'field_name' => '创建时间',
//                ],
                'overtime_start_time' => [
                    'field_name' => '加班开始时间',
                    'required' => 1
                ],
                'overtime_end_time' => [
                    'field_name' => '加班结束时间',
                    'required' => 1
                ],
                'overtime_to' => [
                    'field_name' => '加班补偿方式',
                ],
                'overtime_reason' => [
                    'field_name' => '加班原因',
                ],
                'overtime_days' => [
                    'field_name' => '加班天数',
                ],
                'overtime_hours' => [
                    'field_name' => '加班小时数',
                ],
                'run_id'            => '流程运行ID',
                'run_name'          => '流程运行名称',
                'flow_id'           => '定义流程ID',
            ],
            'dataOutSend' => ['App\EofficeApp\Attendance\Services\AttendanceOutSendService', 'overtime'],
        ],
        'trip' => [
            'title' => "出差",
            'parent_menu'=>32,
            'fileds' => [
                'user_id' => [
                    'field_name' => '用户ID',
                    'describe' => '出差申请人用户ID',
                    'required' => 1
                ],
//                'create_time' => [
//                    'field_name' => '创建时间',
//                ],
                'trip_start_date' => [
                    'field_name' => '出差开始日期',
                    'required' => 1
                ],
                'trip_end_date' => [
                    'field_name' => '出差结束日期',
                    'required' => 1
                ],
                'trip_reason' => [
                    'field_name' => '出差原因',
                ],
                'trip_area'         => [
                    'field_name'=> '出差地区',
                    'isText' => true
                ],
                'run_id'            => '流程运行ID',
                'run_name'          => '流程运行名称',
                'flow_id'           => '定义流程ID',
            ],
            'dataOutSend' => ['App\EofficeApp\Attendance\Services\AttendanceOutSendService', 'trip'],
        ],
        'repair' => [
            'title' => "补卡",
            'parent_menu' => 32,
            'fileds' => [
                'user_id' => [
                    'field_name' => '用户ID',
                    'describe' => '补卡申请人用户ID',
                    'required' => 1
                ],
//                'create_time' => [
//                    'field_name' => '创建时间',
//                ],
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
            ],
            'dataOutSend' => ['App\EofficeApp\Attendance\Services\AttendanceOutSendService', 'repair'],
        ],
        'backLeave' => [
            'title' => "销假",
            'parent_menu' => 32,
            'fileds' => [
                'user_id' => [
                    'field_name' => '用户ID',
                    'describe' => '销假申请人用户ID',
                    'required' => 1
                ],
//                'create_time' => [
//                    'field_name' => '创建时间',
//                ],
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
            'dataOutSend' => ['App\EofficeApp\Attendance\Services\AttendanceOutSendService', 'backLeave'],
        ],
        'meeting' => [
            'title' => "会议室申请",
            'parent_menu'=>700,
            'fileds' => [
                'meeting_apply_user' => [
                    'field_name' => '申请人',
                    'describe' => '申请人用户ID',
                    'required'   => 1
                ],
                'meeting_subject' => [
                    'field_name' => '会议主题',
                    'required' => 1
                ],
                'attence_type' => [
                    'field_name' => '参会方式'
                ],
                'interface_id' => [
                    'field_name' => '会议接口'
                ],
                'meeting_room_id' => [
                    'field_name' => '会议室',
                    'describe' => '会议室名称'
                ],
                'meeting_type' => [
                    'field_name' => '会议类型',
                    'describe' => '会议类型'
                ],
                'meeting_begin_time' => [
                    'field_name' => '会议开始时间',
                    'required' => 1
                ],
                'meeting_end_time' => [
                    'field_name' => '会议结束时间',
                    'required' => 1
                ],
                'meeting_reminder_timing_h' => [
                    'field_name' => '会议开始提醒小时',
                    'describe'   => '提醒时间'
                ],
                'meeting_reminder_timing_m' => [
                    'field_name' => '会议开始提醒分钟',
                    'describe'   => '提醒时间'
                ],
                'meeting_response' => [
                    'field_name' => '会议回执',
                ],
                'sign' => [
                    'field_name' => '会议签到',
                ],
                'sign_type' => [
                    'field_name' => '签到方式',
                ],
                'meeting_sign_user' => [
                    'field_name' => '签到人员',
                ],
                'meeting_sign_wifi' => [
                    'field_name' => '签到Wi-Fi',
                ],
                'meeting_external_user' => [
                    'field_name' => '参会客户',
                ],
                'external_reminder_type' => [
                    'field_name' => '参会客户提醒方式',
                ],
                'meeting_join_member' => [
                    'field_name' => '参加人员',
                ],
                'meeting_other_join_member' => [
                    'field_name' => '其他参加',
                ],
                'meeting_remark' => [
                    'field_name' => '申请备注',
                    'describe' => '会议申请备注'
                ],
                'meeting_approval_opinion' => [
                    'field_name' => '审批意见',
                    'describe' => '审批意见'
                ],
                'attachment_id' => [
                    'field_name' => '附件',
                    'describe' => '会议申请附件'
                ]
            ],
            'dataOutSend' => ['App\EofficeApp\Meeting\Services\MeetingService', 'addMeetingByFlowOutSend'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Meeting\Services\MeetingService', 'flowOutSendToUpdateMeeting'],
            'dataOutSendForDelete' => ['App\EofficeApp\Meeting\Services\MeetingService', 'flowOutSendToDeleteMeeting'],
            'no_update_fields' => ['meeting_apply_user']
        ],
        'vehiclesApply' => [
            'title' => "用车申请",
            'parent_menu'=>600,
            'fileds' => [
                'vehicles_apply_apply_user' => [
                    'field_name' => '申请人',
                    'describe' => '申请人用户ID',
                    'required'   => 1
                ],
                'vehicles_apply_approval_user' => [
                    'field_name' => '审批人',
                    'describe' => '审批人用户ID，对应流程提交人ID',
                    'required' => 1
                ],
                'vehicles_id' => [
                    'field_name' => '车辆名称',
                    'describe' => '车辆名称(车牌号)',
                    'required' => 1
                ],
                'vehicles_apply_begin_time' => [
                    'field_name' => '用车开始时间',
                    'required' => 1
                ],
                'vehicles_apply_end_time' => [
                    'field_name' => '用车结束时间',
                    'required' => 1
                ],
                'vehicles_apply_path_start' => [
                    'field_name' => '出发地',
                ],
                'vehicles_apply_path_end' => [
                    'field_name' => '目的地',
                ],
                'vehicles_apply_mileage' => [
                    'field_name' => '里程',
                ],
                'vehicles_apply_oil' => [
                    'field_name' => '油耗',
                ],
                'vehicles_apply_reason' => [
                    'field_name' => '事由',
                ],
                'vehicles_apply_remark' => [
                    'field_name' => '备注',

                ],
                'vehicles_apply_approval_remark' => [
                    'field_name' => '审批意见',
                    'describe' => '审批意见'
                ],
                'attachment_id' => [
                    'field_name' => '附件',
                    'describe' => '用车申请附件'
                ]
            ],
            'dataOutSend' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'addVehiclesApplyByFlowOutSend'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'flowOutSendToUpdateVehicles'],
            'dataOutSendForDelete' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'flowOutSendToDeleteVehicles'],
            'no_update_fields' => ['vehicles_apply_apply_user']
        ],
        'vehiclesMaintenance' => [
            'title' => "用车维护",
            'parent_menu'=>600,
            'fileds' => [
                'vehicles_id' => [
                    'field_name' => '车辆名称',
                    'describe' => '车辆名称(车牌号)',
                    'required'   => 1
                ],
                'vehicles_maintenance_type' => [
                    'field_name' => '维护类型',
                    'required'   => 1
                ],
                'vehicles_maintenance_begin_time' => [
                    'field_name' => '用车维护开始时间',
                    'required' => 1
                ],
                'vehicles_maintenance_end_time' => [
                    'field_name' => '用车维护结束时间',
                    'required' => 1
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
        'officeSuppliesApply' => [
            'title' => "办公用品申请",
            'parent_menu'=>264,
            'fileds' => [
                'apply_user' => [
                    'field_name' => '申请人',
                    'describe' => '申请人用户ID',
                    'required' => 1
                ],
                'office_supplies_id' => [
                    'field_name' => '办公用品',
                    'describe' => '办公用品ID',
                    'required' => 1
                ],
                'apply_number' => [
                    'field_name' => '使用数量',
                    'required' => 1
                ],
                'receive_date' => [
                    'field_name' => '领用日期',
                    'required' => 1
                ],
                'return_date' => [
                    'field_name' => '归还日期',
                ],
                'apply_type' => [
                    'field_name' => '使用类型',
                    'describe' => '使用类型ID',
                    'required' => 1
                ],
                'receive_way' => [
                    'field_name' => '领取方式',
                    'describe' => '领取方式ID',
                    'required' => 1
                ],
                'explan' => [
                    'field_name' => '申请说明',
                    'describe' => '申请说明'
                ],
                'approval_opinion' => [
                    'field_name' => '审批意见',
                    'describe' => '审批意见'
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'flowOutSendCreateApplyRecord'],
            'dataOutSendForDelete' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'flowOutSendDeleteApplyRecord'],
        ],
        'incomeexpense' => [
            'title' => "收支记录",
            'parent_menu'=>132,
            'fileds' => [
                'plan_id'           => [
                    'field_name' => '方案ID',
                    'isArray' => true,
                    'required' => 1
                ],
                'income'            => [
                    'field_name' => '收入金额',
                    'isArray' => true,
		            'required' => 1
                ],
                'expense'           => [
                    'field_name' => '支出金额',
                    'isArray' => true,
                    'required' => 1
                ],
                'income_reason'            => [
                    'field_name' => '收入事由',
                    'isArray' => true
                ],
                'expense_reason'           => [
                    'field_name' => '支出事由',
                    'isArray' => true
                ],
                'attachment_id' => [
                    'field_name' => '附件',
                    'isArray' => true,
                ],
                'run_id' => [
                    'field_name' => '流程运行ID',
                    'required' => 1,
                ],
                'run_name' => [
                    'field_name' => '流程运行名称',
                    'required' => 1,
                ],
                'flow_id' => [
                    'field_name' => '定义流程ID',
                    'required' => 1,
                ],
                'creator' => [
                    'field_name' => '创建人',
                    'isArray' => true,
                    'required' => 1
                ],
                'record_time' => [
                    'field_name' => '收支生成时间',
                    'isArray' => true,
                    'required' => 1
                ],
                'record_desc' => [
                    'field_name' => '备注',
                    'isArray' => true,
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'addOutSendRecord'],
        ],
        'diary' => [
            'title' => "微博日志",
            'parent_menu'=>189,
            'fileds' => [
                'plan_content'           => [
                    'field_name' => '微博内容',
                    'required' => 1
                ],
                'kind_id'        => [
                    'field_name' => '报告种类',
                    'required' => 1
                ],
                'cycle' => '周期',
                'creator'        => [
                    'field_name' => '创建人',
                    'required' => 1
                ],
                'attachment_id'         => '附件'
            ],
            'dataOutSend' => ['App\EofficeApp\Diary\Services\DiaryService', 'addOutSendDiary'],
        ],
        'vacationOvertime' => [
            'title' => "加班外发",
            'parent_menu'=>434,
            'fileds' => [
                'user_id' => [
                    'field_name' => '加班申请人Id',
                    'required' => 1
                ],
                'days' => [
                    'field_name' => '加班时长',
                    'required' => 1
                ]
            ],
            'dataOutSend' => ['App\EofficeApp\Vacation\Services\VacationService', 'overtimeDataout'],
        ],
        'vacationLeave' => [
            'title' => "请假外发",
            'parent_menu'=>434,
            'fileds' => [
                'user_id' => [
                    'field_name' => '请假申请人Id',
                    'required' => 1
                ],
                'days' => [
                    'field_name' => '请假时长',
                    'required' => 1
                ],
                'vacation_type' => [
                    'field_name' => '请假类型',
                    'required' => 1
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Vacation\Services\VacationService', 'leaveDataout'],
        ],
    	'contactRecord' => [
			'title' => "联系记录",
            'parent_menu'=>44,
			'fileds' => [
					'customer_id' =>[
					    'field_name' => '客户id',
                        'required' => 1
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
                        'required' => 1
                    ],
                    'attachment_id' => [
                        'field_name' => '联系附件',
                    ],
			],
			'dataOutSend'          => ['App\EofficeApp\Customer\Services\ContactRecordService', 'flowStore'],
    	],
    	'salesRecords' => [
			'title' => "销售记录",
            'parent_menu'=>44,
			'fileds' => [
					'user_id' => [
                        'field_name' => '创建人id',
                    ],
					'customer_id' => [
                        'field_name' => '客户id',
                        'required' => 1
                    ],
					'product_id' => [
                        'field_name' => '产品id',
                        'required' => 1
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
                    ],
			],
			'dataOutSend' => ['App\EofficeApp\Customer\Services\SaleRecordService','flowStore'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Customer\Services\SaleRecordService', 'flowOutUpdate'],
            'dataOutSendForDelete' => ['App\EofficeApp\Customer\Services\SaleRecordService', 'flowOutDelete'],
            'no_update_fields' => ['user_id']
    	],
        'salaryAdjust' => [
            'title' => "薪资调整",
            'parent_menu'=>126,
            'fileds' => [
                'user_id' => [
                    'field_name' => '被调整人',
                    'isArray' => true,
                    'required' => 1
                ],
                'creator' => [
                    'field_name' => '调整人',
                    'isArray' => true,
                    'required' => 1
                ],
                'field_name' => [
                    'field_name' => '薪资项',
                    'isArray' => true,
                    'required' => 1
                ],
                'adjust_value' => [
                    'field_name' => '调整金额',
                    'isArray' => true,
                    'required' => 1
                ],
                'adjust_type' => [
                    'field_name' => '调整方式',
                    'isArray' => true,
                    'required' => 1
                ],
                'field_default_old' => [
                    'field_name' => '调整前薪资',
                    'isArray' => true
                ],
                'field_default_new' => [
                    'field_name' => '调整后薪资',
                    'isArray' => true
                ],
                'adjust_reason' => [
                    'field_name' => '调整原因',
                    'isArray' => true
                ],
                'run_id'   => [
                    'field_name' => '运行流程ID',

                ],
                'run_name' => [
                    'field_name' => '流程名称',
                ],
                'flow_id'  => [
                    'field_name' => '定义流程ID',
                ],

            ],
            'dataOutSend' => ['App\EofficeApp\Salary\Services\SalaryOutSendService', 'salaryAdjustOutSend'],
        ],
    	'outStock' => [
            'title' => "出库",
            'parent_menu'=>122,
            'fileds' => [
                    'product_id' => [
                        'field_name' => '产品Id',
                        'required' => 1
                    ],
                    'total_count' => [
                        'field_name' => '库存量',
                        'required' => 1
                    ],
                    'warehouse_id' => [
                        'field_name' => '仓库id',
                        'required' => 1
                    ],
                    'count' => [
                        'field_name' => '出库量',
                        'required' => 1
                    ],
                    'product_batchs' => [
                        'field_name' => '产品批次',
                        'required' => 1
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
                    ],
            ],
            'dataOutSend' => ['App\EofficeApp\Storage\Services\StorageService','stockOutFromWorkflow'],
    	],
        'inStock' => [
            'title' => "入库",
            'parent_menu'=>122,
            'fileds' => [
                    'product_id' => [
                        'field_name' => '产品Id',
                        'required' => 1
                    ],
                    'product_date' => [
                        'field_name' => '生产日期',
                        'required' => 1
                    ],
                    'product_batchs' => [
                        'field_name' => '产品批次',
                        'required' => 1
                    ],
                    'warehouse_id' => [
                        'field_name' => '仓库id',
                        'required' => 1
                    ],
                    'count' => [
                        'field_name' => '入库量',
                        'required' => 1
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
                    ],
            ],
            'dataOutSend' => ['App\EofficeApp\Storage\Services\StorageService','stockInFromWorkflow'],
        ],
        /*
        'contractProject' => [
            'title' => "结算情况",
            'parent_menu'=>150,
            'fileds' => [
                "contract_t_id" => [
                    'field_name' => '合同',
                    'required' => 1
                ],
                "type" => [
                    'field_name' => '款项性质',
                    'required' => 1
                ],
                "pay_type" => [
                    'field_name' => '款项类别',
                ],
                "money" => [
                    'field_name' => '款项金额（元）',
                    'required' => 1
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
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Contract\Services\ContractService', 'flowSendStoreProject'],
        ],
        'contractOrder' => [
            'title' => "订单",
            'parent_menu'=>150,
            'fileds' => [
                "contract_t_id" => [
                    'field_name' => '合同',
                    'required' => 1
                ],
                "product_id" => [
                    'field_name' => '产品名称',
                    'required' => 1
                ],
                "shipping_date" => [
                    'field_name' => '发货时间',
                    'required' => 1
                ],
                "number" => [
                    'field_name' => '发货数量',
                    'required' => 1
                ],
                "run_ids" => [
                    'field_name' => '关联流程',
                ],
                "remarks" => [
                    'field_name' => '备注',
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Contract\Services\ContractService', 'flowSendStoreOrder'],
        ],
        'contractRemind' => [
            'title' => "提醒计划",
            'parent_menu'=>150,
            'fileds' => [
                "contract_t_id" => [
                    'field_name' => '合同',
                    'required' => 1
                ],
                "user_id" => [
                    'field_name' => '提醒对象',
                    'required' => 1
                ],
                "remind_date" => [
                    'field_name' => '提醒日期',
                    'required' => 1
                ],
                "content" => [
                    'field_name' => '提醒内容',
                    'required' => 1
                ],
                "remarks" => [
                    'field_name' => '备注',
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Contract\Services\ContractService', 'flowSendStoreRemind'],
        ],
        */
        'assetsApply' => [
            'title' => "资产入库申请",
            'parent_menu'=>920,
            'fileds'=> [
                'id'            => [
                    'field_name' => '资产名称',
                    'required' => 1
                ],
                'apply_way'     => [
                    'field_name' => '领用方式',
                    'required' => 1
                ],
                'receive_at'    => [
                    'field_name' => '领用时间',
                    'required' => 1
                ],
                'return_at'     => [
                    'field_name' => '归还时间',
                    'required' => 1
                ],
                'apply_user'    => [
                    'field_name' => '申请人',
                    'required' => 1
                ],
                'approver'      => [
                    'field_name' => '审批人',
                    'required' => 1
                ],
                'run_ids'       => [
                    'field_name' => '关联流程',
                ],
                'remark'        => [
                    'field_name' => '备注',
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Assets\Services\AssetsService', 'assetsApplyOutSend'],
            'dataOutSendForDelete' => ['App\EofficeApp\Assets\Services\AssetsService', 'flowOutApplyDelete'],
        ],
        'assetsRepair' => [
            'title' => "资产维护",
            'parent_menu'=>920,
            'fileds'=> [
                'id'              => [
                    'field_name' => '资产名称',
                    'required' => 1
                ],
                'apply_user'      => [
                    'field_name' => '申请人',
                    'required' => 1
                ],
                'operator'        => [
                    'field_name' => '操作人',
                    'required' => 1
                ],
                'remark'          => [
                    'field_name' => '备注',
                ],
                'run_id'          => [
                    'field_name' => '关联流程',
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Assets\Services\AssetsService', 'assetsRepairOutSend'],
        ],
        'assetsRet' => [
            'title' => "资产退库",
            'parent_menu'=>920,
            'fileds'=> [
                'id'              => [
                    'field_name' => '资产名称',
                    'required' => 1
                ],
                'apply_user'      => [
                    'field_name' => '退库操作人',
                    'required' => 1
                ],
                'remark'          => [
                    'field_name' => '备注',
                ],
                'run_id'          => [
                    'field_name' => '关联流程',
                ],
            ],
            'dataOutSend' => ['App\EofficeApp\Assets\Services\AssetsService', 'assetsRetOutSend'],
        ],
        'assetsInventory' => [
            'title' => "资产盘点",
            'parent_menu'=>920,
            'fileds'=> [
                'name'            => [
                    'field_name' => '盘点名称',
                    'required' => 1
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
            'dataOutSend' => ['App\EofficeApp\Assets\Services\AssetsService', 'assetsInventoryOutSend'],
        ],
        'task' => [
            //模块名称，用于展示
            'title' => "任务",
            'parent_menu' => 530,
            //流程外发处的可选字段
            'fileds' => \App\EofficeApp\Task\Services\TaskService::taskFields(),
            'dataOutSend' => ['App\EofficeApp\Task\Services\TaskService', 'flowOutSendToTask'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Task\Services\TaskService', 'flowOutSendToUpdateTask'],
            'dataOutSendForDelete' => ['App\EofficeApp\Task\Services\TaskService', 'flowOutSendToDeleteTask'],
            'no_update_fields' => ['create_user', 'parent_id']
        ],
        'chargeAlert' => [
            'title' => "费用预警",
            'parent_menu' => 37,
            'fileds' => [
                'set_type' => [
                    'field_name' => '预警对象类型',
                    'required' => 1
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
                    'required' => 1
                ],
                'alert_data_start' => [
                    'field_name' => '预警开始日期'
                ],
                'alert_data_end' => [
                    'field_name' => '预警结束日期'
                ],
                'subject_check' => [
                    'field_name' => '预警金额类型',
                    'required' => 1
                ],
                'alert_value' => [
                    'field_name' => '预警金额',
                    'required' => 1
                ],
                'alert_subject' => [
                    'field_name' => '预警科目'
                ]
            ],
            'dataOutSend' => ['App\EofficeApp\Charge\Services\ChargeService', 'chargeAlertFromFlow'],
            'dataOutSendForDelete' => ['App\EofficeApp\Charge\Services\ChargeService', 'flowOutSendToChargeSettingDelete'],
        ]
    ],
    'custom_module' => [
        //tableKey
        'address_private' => [
            'dataOutSend' => ['App\EofficeApp\Address\Services\AddressFlowOutSendService','flowOutCreatePrivateAddress'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Address\Services\AddressFlowOutSendService','flowOutUpdatePrivateAddress'],
            'dataOutSendForDelete' => ['App\EofficeApp\Address\Services\AddressFlowOutSendService','flowOutDeletePrivateAddress'],
            'no_update_fields' => ['primary_5', 'primary_6']
        ],
        'address_public' => [
            'dataOutSend' => ['App\EofficeApp\Address\Services\AddressFlowOutSendService','flowOutCreatePublicAddress'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Address\Services\AddressFlowOutSendService','flowOutUpdatePublicAddress'],
            'dataOutSendForDelete' => ['App\EofficeApp\Address\Services\AddressFlowOutSendService','flowOutDeletePublicAddress'],
            'no_update_fields' => ['primary_5', 'primary_6']
        ],
        'book_info' =>[
            'dataOutSend' => ['App\EofficeApp\Book\Services\BookService','flowOutCreateBookInfo'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Book\Services\BookService','flowOutUpdateBookInfo'],
            'dataOutSendForDelete' => ['App\EofficeApp\Book\Services\BookService','flowOutDeleteBookInfo'],
        ],
        'book_manage' => [
            'dataOutSend' => ['App\EofficeApp\Book\Services\BookService','flowOutAddBookManage'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Book\Services\BookService','flowOutUpdateBookManage'],
            'dataOutSendForDelete' => ['App\EofficeApp\Book\Services\BookService','flowOutDeleteBookManage'],
        ],
        'customer' => [
            'dataOutSend' => ['App\EofficeApp\Customer\Services\CustomerService','flowStore'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Customer\Services\CustomerService', 'flowOutUpdate'],
            'dataOutSendForDelete' => ['App\EofficeApp\Customer\Services\CustomerService', 'flowOutDelete'],
            'no_update_fields' => ['customer_creator','created_at']
        ],
        'customer_product' => [
            'dataOutSend' => ['App\EofficeApp\Customer\Services\ProductService','flowStore'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Customer\Services\ProductService', 'flowOutUpdate'],
            'dataOutSendForDelete' => ['App\EofficeApp\Customer\Services\ProductService', 'flowOutDelete'],
            'no_update_fields' => ['product_creator']
        ],
        'customer_business_chance' => [
            'dataOutSend' => ['App\EofficeApp\Customer\Services\BusinessChanceService','flowStore'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Customer\Services\BusinessChanceService', 'flowOutUpdate'],
            'no_update_fields' => ['chance_creator','created_at']
        ],
        'customer_linkman' => [
            'dataOutSend' => ['App\EofficeApp\Customer\Services\LinkmanService','flowStore'],
        ],
        'customer_contract' => [
            'dataOutSend' => ['App\EofficeApp\Customer\Services\ContractService','flowStore'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Customer\Services\ContractService', 'flowOutUpdate'],
            'no_update_fields' => ['contract_creator','created_at']
        ],
        'office_supplies_storage' =>[
            'dataOutSend' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService','createOfficeInfo'],
            'dataOutSendForDelete' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService','flowOutSendDeleteStorage'],
        ],
        'archives_library' => [
            'dataOutSend' => ['App\EofficeApp\Archives\Services\ArchivesService','flowOutSendToCreateLibrary'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Archives\Services\ArchivesService', 'flowOutSendToUpdateLibrary'],
            'dataOutSendForDelete' => ['App\EofficeApp\Archives\Services\ArchivesService', 'flowOutSendToDeleteLibrary'],
            'no_update_fields' => ['library_creator', 'attachments', 'created_at']
        ],
        'archives_volume' => [
            'dataOutSend' => ['App\EofficeApp\Archives\Services\ArchivesService','flowOutSendToCreateVolume'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Archives\Services\ArchivesService', 'flowOutSendToUpdateVolume'],
            'dataOutSendForDelete' => ['App\EofficeApp\Archives\Services\ArchivesService', 'flowOutSendToDeleteVolume'],
            'no_update_fields' => ['volume_creator', 'attachments', 'created_at']
        ],
        'archives_file' => [
            'dataOutSend' => ['App\EofficeApp\Archives\Services\ArchivesService','flowOutSendToCreateFile'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Archives\Services\ArchivesService', 'flowOutSendToUpdateFile'],
            'dataOutSendForDelete' => ['App\EofficeApp\Archives\Services\ArchivesService', 'flowOutSendToDeleteFile'],
            'no_update_fields' => ['file_creator', 'attachments', 'created_at']
        ],
        'archives_appraisal' => [
            'dataOutSend' => ['App\EofficeApp\Archives\Services\ArchivesService','createAppraisal'],
        ],
        'archives_borrow' => [
            'dataOutSend' => ['App\EofficeApp\Archives\Services\ArchivesService','createBorrow'],
        ],
        'personnel_files' => [
            'dataOutSend' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService','createPersonnelFiles'],
            'dataOutSendForUpdate' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService','flowOutUpdatePersonnelFiles'],
            'dataOutSendForDelete' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService','flowOutDeletePersonnelFiles'],
        ],
        'project' => [
            'dataOutSend' => ['App\EofficeApp\Project\Services\ProjectService','flowOutSendToCreateProject'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Project\Services\ProjectService', 'flowOutSendToUpdateProject'],
            'dataOutSendForDelete' => ['App\EofficeApp\Project\Services\ProjectService', 'flowOutSendToDeleteProject'],
            'no_update_fields' => ['creat_time', 'manager_creater']
        ],
        'product' => [
            'dataOutSend' => ['App\EofficeApp\Product\Services\ProductService','addProductOutSend'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Product\Services\ProductService', 'flowOutUpdate'],
            'dataOutSendForDelete' => ['App\EofficeApp\Product\Services\ProductService', 'flowOutDelete'],
        ],
        'contract_t' => [
            'dataOutSend' => ['App\EofficeApp\Contract\Services\ContractService','flowSendStore'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Contract\Services\ContractService', 'flowOutUpdate'],
            'dataOutSendForDelete' => ['App\EofficeApp\Contract\Services\ContractService', 'flowOutDelete'],
            'no_update_fields' => ['creator', 'created_at','updated_at']
        ],
        'assets' => [
            'dataOutSend' => ['App\EofficeApp\Assets\Services\AssetsService', 'assetStorageOutSend'],
            'dataOutSendForUpdate' => ['App\EofficeApp\Assets\Services\AssetsService', 'flowOutUpdate'],
            'dataOutSendForDelete' => ['App\EofficeApp\Assets\Services\AssetsService', 'flowOutDelete'],
            'no_update_fields' => ['created_at', 'operator_id']
        ],
        'income_expense_plan' => [
            'dataOutSend' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'addOutSendPlan'],
            'dataOutSendForUpdate' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'updateOutSendPlan'],
            'dataOutSendForDelete' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'deleteOutSendPlan'],
            'no_update_fields' => ['creator']
        ],
        'contract_t_project' => [
            'dataOutSend' => ['App\EofficeApp\Contract\Services\ContractService', 'flowSendStoreProject'],
        ],
        'contract_t_order' => [
            'dataOutSend' => ['App\EofficeApp\Contract\Services\ContractService', 'flowSendStoreOrder'],
        ],
        'contract_t_remind' => [
            'dataOutSend' => ['App\EofficeApp\Contract\Services\ContractService', 'flowSendStoreRemind'],
        ],
    ],
    'from_type' => [
        160 => [
            'title' => '项目管理',
            'id'    => '160_project',
            'baseFiles'=>'项目类型',
            'baseId'=>'project_type',
            'otherFiles'=>['project_state'=>'项目状态'],
            'additional'=>[
                'id' => '160_project_additional',
                'title'=> '任务管理',
                'fields'=>[
                    "task_name"      => [
                        'field_name' => '任务名称',
                        'required'   => 1
                    ],
                    "sort_id"        => [
                        'field_name' => '排序级别',
                    ],
                    "task_persondo"  => [
                        'field_name' => '任务执行人',
                        'required'   => 1
                    ],
                    "task_begintime" => [
                        'field_name' => '计划周期开',
                        'required'   => 1
                    ],
                    "task_endtime"   => [
                        'field_name' => '计划周期结',
                        'required'   => 1
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
                        'required'   => 1
                    ],
                    "creat_time"     => [
                        'field_name' => '任务创建时间',
                    ],
                ]
            ]
        ],
        530 => [
            'title' => '子任务',
            'id'    => '530_task',
            'additional'=>[
                'id' => '530_task_additional',
                'title'=> '子任务',
                'fields'=> \App\EofficeApp\Task\Services\TaskService::taskFields(false, 0)
            ]
        ]
    ],
    'moduleIdConfig' => [
        1 => "flow",
        6 => "document",
        11 => "email",
        26 => "calendar", //日程管理
        32 => "attendance",
        37 => "charge",
        44 => "customer",
        82 => "performance",
        95 => "system",
        107 => "qyweixin",
        126 => "salary", //薪酬中心
        132 => "incomeexpense",
        14 => "webmail",
        43 => "shortMessage",
        160 => "project",
        189 => "diary",
        216 => "address",
        233 => "book",
        237 => "news",
        264 => "officeSupplies",
        320 => "notify",
        362 => "cooperation",
        366 => "album",
        415 => "personnelFiles",
        434 => "vacation",
        499 => "weixin",
        520 => "report",  //报表管理
        530 => "task",
        600 => "vehicles",
        700 => "meeting",
        900 => "archives", //档案管理
        120 => 'dingtalk',
        913 => 'workWechat',
        249 => 'vote',//投票调查
        104 => 'lang',
        60 => 'attendanceMachine',//考勤机集成
        122 => "storage",//仓储管理
        150 => "contract",//合同信息
        170 => "product",//产品管理
        192 => "mobile",//产品管理
        920 => "assets",//固定资产
    ]
];

