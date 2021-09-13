<?php
return [
    '0x000001'                 => 'Subprocess id is empty',
    '0x000002'                 => 'Subprocess does not create permissions',
    '0x000003'                 => 'Module is empty',
    '0x000004'                 => 'Outgoing data is empty',
    '0x000005'                 => 'Data outbound module configuration information is incorrect',
    '0x000006'                 => 'Database configuration error',
    '0x000007'                 => 'Database operation failed',
    '0x000008'                 => 'Wrong process information',
    '0x000009'                 => 'Subprocess creator has incorrect information',
    '0x000010'                 => 'Database configuration information is incorrect',
    '0x000011'                 => 'Failed to get database configuration information',
    '0x000012'                 => 'Outgoing module failure',
    '0x000013'                 => 'does not meet the trigger conditions',
    'base_files_error'         => 'The workflow form must has one control with title "project_type"',
    'user' => [
        //模块名称，用于展示
        'title' => "User manage",
        //流程外发处的可选字段
        'fileds' => [
            'user_accounts' => [
                'field_name' => 'User name'
            ],
            'user_name' => [
                //流程外发处的可选字段的字段名称
                'field_name' => 'True name',            ],
            'phone_number' => [
                'field_name' => 'Phone number',
                'describe' => 'Phone number'
            ],
            'user_job_number' => [
                'field_name' => 'Job number',
                'describe' => 'Job number'
            ],
            'user_password' => [
                'field_name' => 'Password',
                'describe' => 'Password'
            ],
            //'create_time' => '发布时间',
            'is_dynamic_code' => [
                'field_name' => 'Dynamic code set'
            ],
            'sn_number' => [
                'field_name' => 'SN number'
            ],
            'dynamic_code' => [
                'field_name' => 'Dynamic code'
            ],
            'is_autohrms' => [
                'field_name' => 'Is autohrms',
            ],
            'sex' => [
                'field_name' => 'Set'
            ],
            'wap_allow' => [
                'field_name' => 'Wap allowed'
            ],
            'user_status' => [
                'field_name' => 'User Status'
            ],
            'dept_id' => [
                'field_name' => 'Department'
            ],
            'user_position' => [
                'field_name' => 'Position'
            ],
            'attendance_scheduling' => [
                'field_name' => 'Attendance scheduling'
            ],
            'role_id_init' => [
                'field_name' => 'Role'
            ],
            'superior_id_init' => [
                'field_name' => 'Superior'
            ],
            'subordinate_id_init' => [
                'field_name' => 'Subordinate'
            ],
            'post_priv' => [
                'field_name' => 'Post privilege'
            ],
            'post_dept' => [
                'field_name' => 'Post scope(department)'
            ],
            'list_number' => [
                'field_name' => 'List number'
            ],
            'user_area' => [
                'field_name' => 'Area'
            ],
            'user_city' => [
                'field_name' => 'City'
            ],
            'user_workplace' => [
                'field_name' => 'Workplace'
            ],
            'user_job_category' => [
                'field_name' => 'Job category'
            ],
            'birthday' => [
                'field_name' => 'Birthday'
            ],
            'email' => [
                'field_name' => 'Email'
            ],
            'oicq_no' => [
                'field_name' => 'QQ'
            ],
            'weixin' => [
                'field_name' => 'Weixin'
            ],
            'dept_phone_number' => [
                'field_name' => 'Company phonenumber'
            ],
            'faxes' => [
                'field_name' => 'Faxes'
            ],
            'home_address' => [
                'field_name' => 'Home address'
            ],
            'home_zip_code' => [
                'field_name' => 'Home zip code'
            ],
            'home_phone_number' => [
                'field_name' => 'Home phone'
            ],
            'notes' => [
                'field_name' => 'Remarks'
            ],
        ]
    ],
    'calendar'                 => [
        'title'  => 'Calendar management',
        'fileds' => [
            "calendar_content"  => [
                'field_name' => 'Calendar content',
            ],
            "calendar_type_id" => [
                'field_name' => 'Calendar type'
            ],
            'calendar_begin'    => [
                'field_name' => 'Calendar begin time',
                'describe'   => 'The format is "2017-01-20 12:14:01"',
            ],
            'calendar_end'      => [
                'field_name' => 'Calendar end time',
                'describe'   => 'The format is "2017-01-20 12:14:01',
            ],
            'calendar_address' => [
                'field_name' => 'Position'
            ],
            'handle_user'           => [
                'field_name' => 'Handler',
                'describe'   => 'Handler ID',
            ],
            'share_user'        => [
                'field_name' => 'Sharer',
                'describe'   => 'Sharer ID',
            ],
            'calendar_level'    => [
                'field_name' => 'Emergency level',
                'describe'   => 'Emergency level',
            ],
            'allow_remind' => [
                'field_name' => 'Allow remind',
                'describe'   => 'Allow remind'
            ],
            'remind_now' => [
                'field_name' => 'Remind now',
                'describe'   => 'Remind now'
            ],
            'start_remind' => [
                'field_name' => 'Start remind',
                'describe'   => 'Start remind'
            ],
            'end_remind' => [
                'field_name' => 'End remind',
                'describe'   => 'End remind'
            ],
            'repeat'            => [
                'field_name' => 'Repeat',
                'describe'   => 'Repeat',
            ],
            'repeat_type'       => [
                'field_name' => 'Repeat type',
                'describe'   => 'Repeat type',
            ],
            'repeat_circle'     => [
                'field_name' => 'Repeat cycle',
                'describe'   => 'Repeat cycle',
            ],
            'start_remind_h' => [
                'field_name' => 'Calendar begin remind hour',
                'describe'   => 'Remind time'
            ],
            'start_remind_m' => [
                'field_name' => 'Calendar begin remind minutes',
                'describe'   => 'Remind time'
            ],
            'end_remind_h' => [
                'field_name' => 'Calendar end remind hour',
                'describe'   => 'Remind time'
            ],
            'end_remind_m' => [
                'field_name' => 'Calendar end remind minutes',
                'describe'   => 'Remind time'
            ],
            'repeat_end_type'   => [
                'field_name' => 'Repeat end type',
                'describe'   => 'Repeat end type',
            ],
            'remind' => [
                'field_name' => 'Remind',
                'describe'   => 'Remind'
            ],
            'reminder_timing_h' => [
                'field_name' => 'Calendar begin reminder hour',
                'describe'   => 'Reminder time',
            ],
            'reminder_timing_m' => [
                'field_name' => 'Calendar begin reminder minutes',
                'describe'   => 'Reminder time',
            ],
            'repeat_end_number' => [
                'field_name' => 'Calendar repeat end times',
                'describe'   => 'repeat end time',
            ],
            'repeat_end_date'   => [
                'field_name' => 'Calendar repeat end date',
                'describe'   => 'repeat end time',
            ],
            'calendar_remark'   => [
                'field_name' => 'Remark',
                'describe'   => 'Remark',
            ],
            'attachment_id'     => [
                'field_name' => 'Attachment',
                'describe'   => 'Attachment',
            ],
            'flow_id' => [
                'field_name' => 'Define flow ID',
                'describe' => 'Define flow ID'
            ],
            'run_id' => [
                'field_name' => 'Flow running ID',
                'describe' => 'Flow running ID'
            ],
            'flow_name' => [
                'field_name' => 'Flow name',
                'describe' => 'Flow Name'
            ],
        ],
    ],
    'news'                     => [
        'title'  => 'News management',
        'fileds' => [
            'title'         => [
                'field_name' => 'Headlines',
            ],
            'news_type_id'  => [
                'field_name' => 'News type',
                'describe'   => 'The news type id present in the system must be an integer',
            ],
            'top'           => [
                'field_name' => 'Top',
                'describe'   => 'Pinned to a value of 1 and a value of 0 is not pinned',
            ],
            'top_end_time'  => [
                'field_name' => 'Top date',
                'describe'   => 'This attribute is only valid when the set-top value is 1. The format is "2017-01-20 12:14:01"',
            ],
            'allow_reply'   => [
                'field_name' => 'Comment switch',
                'describe'   => 'The comment is allowed when the value is 1, and the comment is not allowed when the value is 0.',
            ],
            'create_time'   => 'Release time',
            'content'       => [
                'field_name' => 'News content',
            ],
            'publish'       => [
                'field_name' => 'News status',
                'describe'   => 'The value 0 is the draft status, the value 1 is the release status, and the value 2 is the audit status',
            ],
            'creator'       => [
                'field_name' => 'Publisher',
                'describe'   => 'Publisher ID',
            ],
            'attachment_id' => [
                'field_name' => 'Image picture',
            ],
        ],
    ],
    'notify'                   => [
        'title'  => 'Notify management',
        'fileds' => [
            'subject'        => [
                'field_name' => 'Notify title',
            ],
            'content'        => [
                'field_name' => 'Notify content',
            ],
            'notify_type_id' => [
                'field_name' => 'Notify category',
                'describe'   => 'Notify category id',
            ],
            'begin_date'     => [
                'field_name' => 'Effective date',
                'describe'   => 'The format is xxxx-xx-xx',
            ],
            'end_date'       => [
                'field_name' => 'End date',
                'describe'   => 'The format is xxxx-xx-xx',
            ],
            'publish'        => [
                'field_name' => 'Notify status',
                'describe'   => 'The value 0 is the draft status, the value 1 is the release status, and the value 2 is the audit status',
            ],
            'allow_reply' => [
                'field_name' => 'Allow reply',
                'describe' => '0 allowed，1 disallowed'
            ],
            'top' => [
                'field_name' => 'Top',
                'describe' => '0 not top，1 top'
            ],
            'top_end_time' => [
                'field_name' => 'Top end time',
                'describe' => 'Top end time',
            ],
            'open_unread_after_login' => [
                'field_name' => 'Open unread after login',
                'describe' => '0 off，1 on'
            ],
            'priv_scope'     => [
                'field_name' => 'Release range',
                'describe'   => '1 indicates the whole, 0 indicates the specified range, in which case the department or role or user needs to be specified',
            ],
            'dept_id'        => [
                'field_name' => 'Department',
                'describe'   => 'An array of department ids',
            ],
            'role_id'        => [
                'field_name' => 'Character',
                'describe'   => 'Array of role ids',
            ],
            'user_id'        => [
                'field_name' => 'User',
                'describe'   => 'An array of user ids',
            ],
            'from_id'        => [
                'field_name' => 'Founder',
                'describe'   => 'Creator ID',
            ],
            'attachment_id'  => [
                'field_name' => 'The attachment',
                'describe'   => 'The attachment id',
            ],
            'creator_type' => [
                'field_name' => 'Publish unit',
            ],
            'creator_id' => [
                'field_name' => 'Publish personnel',
            ],
            'department_id' => [
                'field_name' => 'Publish department',
            ],
        ],
    ],
    'charge'                   => [
        'title'  => 'charge entry',
        'fileds' => [
            'user_id'              => [
                'field_name' => 'Reimbursers',
            ],
            'dept_id'              => [
                'field_name' => 'Reimbursement department',
            ],
            'charge_type_parentid' => [
                'field_name' => 'Main subject',
            ],
            'charge_type_id'       => [
                'field_name' => 'Reimbursement subject',
            ],
            'charge_cost'          => [
                'field_name' => 'Reimbursement quota',
            ],
            'reason'               => [
                'field_name' => 'Cause',
            ],
            'payment_date'         => [
                'field_name' => 'Reimbursement date',
            ],
            'charge_undertaker'    => [
                'field_name' => 'Cost bearer',
                'describe'   => '1 person 2 department',
            ],
            'project_id'           => [
                'field_name' => 'Project',
            ],
            'undertake_user' =>[
                'field_name' => 'Reimbursement user',
            ],
            'run_id'               => 'Process run ID',
            'run_name'             => 'Process run name',
            'flow_id'              => 'Define process ID',
        ],
    ],
    'leave'                    => [
        'title'  => 'Leave',
        'fileds' => [
            'user_id'          => [
                'field_name' => 'User ID',
                'describe'   => 'Leave applicant ID',
            ],
            'vacation_id'      => [
                'field_name' => 'Holiday type',
            ],
            'create_time'      => [
                'field_name' => 'Creation time',
            ],
            'leave_start_time' => [
                'field_name' => 'Leave start time',
            ],
            'leave_end_time'   => [
                'field_name' => 'Leave time',
            ],
            'leave_days'       => [
                'field_name' => 'Number of leave days',
            ],
            'leave_hours'       => [
                'field_name' => 'Number of leave hours',
            ],
            'leave_reason'     => [
                'field_name' => 'Leave reason',
            ],
            'run_id'           => 'Process run ID',
            'run_name'         => 'Process run name',
            'flow_id'          => 'Define process ID',
        ],
    ],
    'out'                      => [
        'title'  => 'Go out',
        'fileds' => [
            'user_id'        => [
                'field_name' => 'User ID',
                'describe'   => 'Outgoing Applicant User ID',
            ],
            'create_time'    => [
                'field_name' => 'Creation time',
            ],
            'out_start_time' => [
                'field_name' => 'Outgoing start time',
            ],
            'out_end_time'   => [
                'field_name' => 'Exit time',
            ],
            'out_reason'     => [
                'field_name' => 'Out reason',
            ],
            'run_id'         => 'Process run ID',
            'run_name'       => 'Process run name',
            'flow_id'        => 'Define process ID',
        ],
    ],
    'overtime'                 => [
        'title'  => 'Overtime',
        'fileds' => [
            'user_id'             => [
                'field_name' => 'User ID',
                'describe'   => 'Overtime applicant user ID',
            ],
            'create_time'         => [
                'field_name' => 'Creation time',
            ],
            'overtime_start_time' => [
                'field_name' => 'Overtime start time',
            ],
            'overtime_end_time'   => [
                'field_name' => 'Overtime end time',
            ],
            'overtime_to' => [
                'field_name' => 'Overtime compensation method',
            ],
            'overtime_reason'     => [
                'field_name' => 'Overtime reason',
            ],
            'overtime_days'       => [
                'field_name' => 'Overtime days',
            ],
            'run_id'              => 'Process run ID',
            'run_name'            => 'Process run name',
            'flow_id'             => 'Define process ID',
        ],
    ],
    'trip'                     => [
        'title'  => 'Traveling',
        'fileds' => [
            'user_id'         => [
                'field_name' => 'User ID',
                'describe'   => 'Travel applicant ID',
            ],
            'create_time'     => [
                'field_name' => 'Creation time',
            ],
            'trip_start_date' => [
                'field_name' => 'Travel start date',
            ],
            'trip_end_date'   => [
                'field_name' => 'Travel end date',
            ],
            'trip_reason'     => [
                'field_name' => 'Travel reasons',
            ],
            'trip_area'       => [
                'field_name' => 'Travel area',
            ],
            'run_id'          => 'Process run ID',
            'run_name'        => 'Process run name',
            'flow_id'         => 'Define process ID',
        ],
    ],
    'repair' => [
        'title' => "Attendance repair",
        'fileds' => [
            'user_id' => [
                'field_name' => 'User ID',
                'describe' => 'Repair Applicant User ID',
                'required' => 1
            ],
            'create_time' => [
                'field_name' => 'Creation time',
            ],
            'repair_date' => [
                'field_name' => 'Repair date',
                'required' => 1
            ],
            'sign_times' => [
                'field_name' => 'Repair time',
                'required' => 1
            ],
            'repair_reason' => [
                'field_name' => 'Repair reason',
            ],
            'run_id'          => 'Process run ID',
            'run_name'        => 'Process run name',
            'flow_id'         => 'Define process ID',
        ]
    ],
    'backLeave' => [
        'title' => "Report back after leave",
        'fileds' => [
            'user_id' => [
                'field_name' => 'User ID',
                'describe' => 'Report back after leave Applicant User ID',
                'required' => 1
            ],
            'create_time' => [
                'field_name' => 'Creation time',
            ],
            'leave_id' => [
                'field_name' => 'Leave records',
                'required' => 1
            ],
            'back_leave_reason' => [
                'field_name' => 'Report back after leave reason',
            ],
            'run_id'          => 'Process run ID',
            'run_name'        => 'Process run name',
            'flow_id'         => 'Define process ID',
        ],
    ],
    'meeting'                  => [
        'title'  => 'Conference room management',
        'fileds' => [
            'meeting_apply_user'        => [
                'field_name' => 'Applicant',
                'describe'   => 'Applicant user ID',
            ],
            'meeting_subject'           => [
                'field_name' => 'Conference theme',
            ],
            'attence_type'           => [
                'field_name' => 'Attence type',
            ],
            'meeting_room_id'           => [
                'field_name' => 'Meeting room',
                'describe'   => 'Conference room name',
            ],
            'interface_id' => [
                'field_name' => 'Meeting Api',
            ],
            'meeting_type'              => [
                'field_name' => 'Conference type',
                'describe'   => 'Conference type',
            ],
            'meeting_begin_time'        => [
                'field_name' => 'Conference start time',
            ],
            'meeting_end_time'          => [
                'field_name' => 'Meeting end time',
            ],
            'meeting_reminder_timing_h' => [
                'field_name' => 'Meeting begin reminder hour',
                'describe'   => 'Reminder time',
            ],
            'meeting_reminder_timing_m' => [
                'field_name' => 'Meeting begin reminder minutes',
                'describe'   => 'Reminder time',
            ],
            'meeting_response'          => [
                'field_name' => 'Meeting receipt',
            ],
            'sign'                      => [
                'field_name' => 'Meeting sign',
            ],
            'sign_type'                 => [
                'field_name' => 'Sign type',
            ],
            'meeting_sign_user'         => [
                'field_name' => 'Sign person',
            ],
            'meeting_sign_wifi'         => [
                'field_name' => 'Sign in WiFi',
            ],
            'meeting_external_user'     => [
                'field_name' => 'Participating customers',
            ],
            'external_reminder_type'    => [
                'field_name' => 'Remind customers',
            ],
            'meeting_join_member'       => [
                'field_name' => 'Participants',
            ],
            'meeting_other_join_member' => [
                'field_name' => 'Other participants',
            ],
            'meeting_remark'            => [
                'field_name' => 'Application note',
                'describe'   => 'Conference application notes',
            ],
            'meeting_approval_opinion'  => [
                'field_name' => 'Approval comments',
                'describe'   => 'Approval comments',
            ],
            'attachment_id'             => [
                'field_name' => 'Annex',
                'describe'   => 'Conference application attachment',
            ],
        ],
    ],
    'vehiclesApply'                 => [
        'title'  => 'Car application',
        'fileds' => [
            'vehicles_apply_apply_user' => [
                'field_name' => 'Apply user',
                'describe' => 'Apply user ID'
            ],
            'vehicles_apply_approval_user'   => [
                'field_name' => 'Approver',
                'describe'   => 'Approver user ID, corresponding process submitter ID',
            ],
            'vehicles_id'                    => [
                'field_name' => 'Vehicle name',
                'describe'   => 'Vehicle Name (License No.)',
            ],
            'vehicles_apply_begin_time'      => [
                'field_name' => 'Car start time',
            ],
            'vehicles_apply_end_time'        => [
                'field_name' => 'Car end time',
            ],
            'vehicles_apply_path_start'      => [
                'field_name' => 'Departure',
            ],
            'vehicles_apply_path_end'        => [
                'field_name' => 'Destination',
            ],
            'vehicles_apply_mileage'         => [
                'field_name' => 'Mileage',
            ],
            'vehicles_apply_oil'             => [
                'field_name' => 'Fuel consumption',
            ],
            'vehicles_apply_reason'          => [
                'field_name' => 'Cause',
            ],
            'vehicles_apply_remark'          => [
                'field_name' => 'Note',
            ],
            'vehicles_apply_approval_remark' => [
                'field_name' => 'Approval comments',
                'describe'   => 'Approval comments',
            ],
            'attachment_id'                  => [
                'field_name' => 'Annex',
                'describe'   => 'Apply for attachments',
            ],
        ],
    ],
    'officeSuppliesApply'      => [
        'title'  => 'Office supplies application',
        'fileds' => [
            'apply_user'         => [
                'field_name' => 'Applicant',
                'describe'   => 'Applicant user ID',
            ],
            'office_supplies_id' => [
                'field_name' => 'Office Supplies',
                'describe'   => 'Office supplies ID',
            ],
            'apply_number'       => [
                'field_name' => 'Usage amount',
            ],
            'receive_date'       => [
                'field_name' => 'Date of receipt',
            ],
            'return_date'        => [
                'field_name' => 'Return date',
            ],
            'apply_type'         => [
                'field_name' => 'Use type',
                'describe'   => 'Use type ID',
            ],
            'receive_way'        => [
                'field_name' => 'Collection method',
                'describe'   => 'Receiving method ID',
            ],
            'explan'             => [
                'field_name' => 'Application instructions',
                'describe'   => 'Application instructions',
            ],
            'approval_opinion'   => [
                'field_name' => 'Approval comments',
                'describe'   => 'Approval comments',
            ],
        ],
    ],
    'incomeexpense'            => [
        'title'  => 'Income and expenditure records',
        'fileds' => [
            'plan_id'        => [
                'field_name' => 'Project ID',
            ],
            'income'         => [
                'field_name' => 'Income amount',
            ],
            'expense'        => [
                'field_name' => 'Amount of payout',
            ],
            'income_reason'  => [
                'field_name' => 'Reasons for income',
            ],
            'expense_reason' => [
                'field_name' => 'Expense reasons',
            ],
            'attachment_id' => [
                'field_name' => 'attachment',
            ],
            'run_id'         => [
                'field_name' => 'Process run ID',
            ],
            'run_name'       => [
                'field_name' => 'Process run name',
            ],
            'flow_id'        => [
                'field_name' => 'Define process ID',
            ],
            'creator'        => [
                'field_name' => 'founder',
            ],
            'record_time'    => [
                'field_name' => 'Revenue and expenditure generation time',
            ],
            'record_desc'        => [
                'field_name' => 'Note',
            ],
        ],
    ],
    'diary' => [
        'title' => "Weibo log",
        'parent_menu'=>189,
        'fileds' => [
            'plan_content'           => [
                'field_name' => 'Weibo content',
            ],
            'kind_id'        => [
                'field_name' => 'report type',
            ],
            'cycle' => 'cycle',
            'creator'        => [
                'field_name' => 'creator',
            ],
            'attachment_id'         => 'attachment',
        ]
    ],
    'incomeexpensePlan'        => [
        'title'  => 'The new Plan',
        'fileds' => [
            'plan_code'            => [
                'field_name' => 'Plan ID',
            ],
            'plan_name'            => [
                'field_name' => 'Plan Name',
            ],
            'plan_type_id'         => 'Plan category',
            'expense_budget_alert' => 'Expenditure budget excess reminder',
            'plan_cycle'           => 'Planned execution cycle',
            'plan_cycle_unit'      => 'Cycle unit',
            'is_start'             => 'Whether to start the scheme or not',
            'creator'              => [
                'field_name' => 'Creator',
            ],
            'expense_budget'       => 'Expense Budget',
            'income_budget'        => 'Income Budget',
            'plan_description'     => 'Plan Description',
            'attachment_id'        => 'Attachment',
            'all_user'             => [
                'field_name' => 'Release range',
            ],
            'dept_id'              => [
                'field_name' => 'Department',
            ],
            'role_id'              => [
                'field_name' => 'Character',
            ],
            'user_id'              => [
                'field_name' => 'User',
            ],

        ],
    ],
    'vacationOvertime'         => [
        'title'  => 'Overtime outgoing',
        'fileds' => [
            'user_id' => [
                'field_name' => 'Overtime applicant Id',
            ],
            'days'    => [
                'field_name' => 'Overtime days',
            ]
        ],
    ],
    'vacationLeave'            => [
        'title'  => 'Leave holiday',
        'fileds' => [
            'user_id'       => [
                'field_name' => 'Leave applicant Id',
            ],
            'days'          => [
                'field_name' => 'Length of leave',
            ],
            'vacation_type' => [
                'field_name' => 'Leave type',
            ]
        ],
    ],
    'contactRecord'            => [
        'title'  => 'Call log',
        'fileds' => [
            'customer_id'    => [
                'field_name' => 'Customer id',
            ],
            'linkman_id'     => [
                'field_name' => 'Contact id',
            ],
            'record_type'    => [
                'field_name' => 'Contact type',
            ],
            'record_start'   => [
                'field_name' => 'Contact start time',
            ],
            'record_end'     => [
                'field_name' => 'Contact end time',
            ],
            'record_creator' => [
                'field_name' => 'Creator ID',
            ],
            'record_content' => [
                'field_name' => 'Contact content',
            ],
            'attachment_id' => [
                'field_name' => 'attachments',
            ],
        ],
    ],
    'contract'                 => [
        'title'  => 'contract',
        'fileds' => [
            'contract_name'               => 'Contract title',
            'contract_number'             => 'Contract No',
            'contract_amount'             => 'Contract amount',
            'customer_id'                 => 'Customer id',
            'contract_type'               => 'Type of contract',
            'contract_start'              => 'Contract start date',
            'contract_end'                => 'Contract end date',
            'contract_creator'            => 'Creator ID',
            'contract_remarks'            => 'Contract notes',
            'first_party'                 => 'Party A signatory',
            'first_party_signature_date'  => 'Party A’s signature date',
            'second_party'                => 'Party B signatories',
            'second_party_signature_date' => 'Party B’s signature date',
            'attachment_ids'              => 'attachments',
            'supplier_id'                 => 'Supplier',
            'remindDate'                  => 'remind date',
        ],
    ],
    'salesRecords'             => [
        'title'  => 'Sales records',
        'fileds' => [
            'user_id'        => [
                'field_name' => 'Creator ID',
            ],
            'customer_id'    => [
                'field_name' => 'Customer id',
            ],
            'product_id'     => [
                'field_name' => 'Product id',
            ],
            'sales_amount'   => [
                'field_name' => 'Sales volume',
            ],
            'sales_price'    => [
                'field_name' => 'Selling price',
            ],
            'sales_date'     => [
                'field_name' => 'Sales date',
            ],
            'discount'       => [
                'field_name' => 'Discount',
            ],
            'salesman'       => [
                'field_name' => 'Seller',
            ],
            'sales_remarks'  => [
                'field_name' => 'Note',
            ]
        ],
    ],
    'outStock'                 => [
        'title'  => "Product outgoing",
        'fileds' => [
            'product_id'      => [
                'field_name' => 'Product id',
            ],
            'total_count'     => [
                'field_name' => 'Inventory',
            ],
            'warehouse_id'    => [
                'field_name' => 'Warehouse id',
            ],
            'count'           => [
                'field_name' => 'Outgoing quantity',
            ],
            'product_batchs' => [
                'field_name' => 'Product batch',
            ],
            'rel_run_id'      => [
                'field_name' => 'Relation workflow id',
            ],
            'rel_contract_id' => [
                'field_name' => 'Relation contract id',
            ],
            'run_id'          => [
                'field_name' => 'Workflow run id',
            ],
            'run_name'        => [
                'field_name' => 'Workflow run name',
            ],
            'flow_id'         => [
                'field_name' => 'Workflow flow id',
            ]
        ],
    ],
    'inStock'                  => [
        'title'  => "Product storage",
        'fileds' => [
            'product_id'      => [
                'field_name' => 'Product id',
            ],
            'product_date'    => [
                'field_name' => 'Date of manufacture',
            ],
            'product_batchs'  => [
                'field_name' => 'Product batch',
            ],
            'warehouse_id'    => [
                'field_name' => 'Warehouse id',
            ],
            'count'           => [
                'field_name' => 'Scheduled Receipt',
            ],
            'rel_run_id'      => [
                'field_name' => 'Relation workflow id',
            ],
            'rel_contract_id' => [
                'field_name' => 'Relation contract id',
            ],
            'run_id'          => [
                'field_name' => 'Workflow run id',
            ],
            'run_name'        => [
                'field_name' => 'Workflow run name',
            ],
            'flow_id'         => [
                'field_name' => 'Workflow flow id',
            ]
        ],
    ],
    'salaryAdjust'             => [
        'title'  => "Salary adjust",
        'fileds' => [
            'user_id'           => [
                'field_name' => 'Adjusted person',
            ],
            'creator'           => [
                'field_name' => 'Remunerated people',
            ],
            'field_name'        => [
                'field_name' => 'Salary item',
            ],
            'adjust_value'      => [
                'field_name' => 'Adjust value',
            ],
            'adjust_type'       => [
                'field_name' => 'Adjust method',
            ],
            'field_default_old' => [
                'field_name' => 'Salary before adjust',
            ],
            'field_default_new' => [
                'field_name' => 'Salary after adjust',
            ],
            'adjust_reason'     => [
                'field_name' => 'Adjust reason',
            ],
            'run_id'            => [
                'field_name' => 'Workflow run id',
            ],
            'run_name'          => [
                'field_name' => 'Workflow run name',
            ],
            'flow_id'           => [
                'field_name' => 'Workflow flow id',
            ]

        ],
    ],
    'from_type_160_id'         => 'Project management',
    'from_type_160_base_files' => 'Project Type',
    'from_type_160'            => [
        'title'      => 'Project management',
        'id'         => '160_project',
        'baseFiles'  => 'Project Type',
        'baseId'     => 'project_type',
        'otherFiles' => ['project_state' => 'Project state'],
        'additional' => [
            'id'     => '160_project_additional',
            'title'  => 'Task management',
            'fields' => [
                "task_name"      => [
                    'field_name' => 'Task name',
                ],
                "sort_id"        => [
                    'field_name' => 'Ranking level',
                ],
                "task_persondo"  => [
                    'field_name' => 'Task executor',
                ],
                "task_begintime" => [
                    'field_name' => 'The planning cycle begins',
                ],
                "task_endtime"   => [
                    'field_name' => 'End of the plan cycle',
                ],
                "task_level"     => [
                    'field_name' => 'Task level',
                ],
                "task_mark"      => [
                    'field_name' => 'Is it marked as a milestone',
                ],
                "task_explain"   => [
                    'field_name' => 'Task description',
                ],
                "task_remark"    => [
                    'field_name' => 'Remarks',
                ],
                "attachments"    => [
                    'field_name' => 'Enclosure',
                ],
                "task_creater"   => [
                    'field_name' => 'Task Creator',
                ],
                "creat_time"     => [
                    'field_name' => 'Task creation time',
                ]
            ],
        ],
    ],
    'contractInformation'      => [
        'title'  => "Contract information",
        'fileds' => [
            "number"      => "Number",
            "title"       => "Title",
            "type_id"     => "Type",
            "user_id"     => "Flow-up",
            "target_name" => "Target object",
            "money"       => "Money",
            "content"     => "Content",
            "a_user"      => "Party a",
            "b_user"      => "Party b",
            "a_address"   => "Party a address",
            "b_address"   => "Party b address",
            "a_linkman"   => "Party a linkman",
            "b_linkman"   => "Party b linkman",
            "a_phone"     => "Party a phone",
            "b_phone"     => "Party b phone",
            "a_sign"      => "Party a sign",
            "b_sign"      => "Party b sign",
            "a_sign_time" => "Party a sign time",
            "b_sign_time" => "Party b sign time",
            "status"      => "status",
            "remarks"     => "remarks",
            'run_id'      => 'Workflow run id',
        ],
    ],
    'assetsStorage'            => [
        'title'  => "Asset storage",
        'fileds' => [
            'assets_name'     => 'Assets name',
            'product_at'      => 'product time',
            'price'           => 'Price',
            'type'            => 'Type',
            'user_time'       => 'User time',
            'residual_amount' => 'Residual amount',
            'managers'        => 'Managers',
            'run_id'          => 'Run',
            'is_all'          => 'Use range',
            'dept'            => 'Department',
            'role'            => 'Role',
            'users'           => 'Users',
            'operator_id'     => 'Operator',
            'remark'          => 'Remark',
            'attachment_id'   => 'Attachment',
        ],
    ],
    'assetsApply'              => [
        'title'  => "Assets apply",
        'fileds' => [
            'id'         => [
                'field_name' => 'Assets name',
            ],
            'apply_way'     => [
                'field_name' => 'Apply way',
            ],
            'receive_at' => [
                'field_name' => 'Receive time',
            ],
            'return_at'  => [
                'field_name' => 'Return time',
            ],
            'address'    => [
                'field_name' => 'Address for use',
            ],
            'apply_user' => [
                'field_name' => 'Apply user',
            ],
            'run_ids'    => [
                'field_name' => 'Run',
            ],
            'approver'   => [
                'field_name' => 'Approver',
            ],
            'remark'     => [
                'field_name' => 'Remark',
            ]
        ],
    ],
    'assetsRepair'             => [
        'title'  => "Assets repair",
        'fileds' => [
            'id'         => [
                'field_name' => 'Assets name',
            ],
            'apply_user' => [
                'field_name' => 'Apply user',
            ],
            'operator'   => [
                'field_name' => 'Operator person',
            ],
            'remark'     => [
                'field_name' => 'Remark',
            ],
            'run_id'     => [
                'field_name' => 'Run',
            ]
        ],
    ],
    'assetsRet'                => [
        'title'  => "Assets withdrawal",
        'fileds' => [
            'id'         => [
                'field_name' => 'Assets name',
            ],
            'apply_user' => [
                'field_name' => 'Apply user',
            ],
            'remark'     => [
                'field_name' => 'Remark',
            ],
            'run_id'     => [
                'field_name' => 'Run',
            ]
        ],
    ],
    'assetsInventory'           => [
        'title'  => "Assets inventory",
        'fileds'=> [
            'name'            => [
                'field_name' => 'Inventory name',
            ],
            'apply_user'      => [
                'field_name' => 'Now apply user',
            ],
            'start_at'        => [
                'field_name' => 'Register start at',
            ],
            'end_at'          => [
                'field_name' => 'Register end at',
            ],
            'type'            => [
                'field_name' => 'Type',
            ],
            'managers'        => [
                'field_name' => 'Managers',
            ],
            'created_at'      => [
                'field_name' => 'Inventory created at',
            ],
            'operator'        => [
                'field_name' => 'Inventory operator',
            ],
            'run_id'          => [
                'field_name' => 'Run id',
            ]
        ],
    ],
    'contractProject'          => [
        'title'  => "Settlement situation",
        'fileds' => [
            "contract_t_id"  => [
                'field_name' => "Contract",
            ],
            "type"         => [
                'field_name' => "Nature of money",
            ],
            "pay_type"     => [
                'field_name' => "Pay category",
            ],
            "money"        => [
                'field_name' => "Payment amount",
            ],
            "pay_way"      => [
                'field_name' => "Pay way",
            ],
            "pay_account"  => [
                'field_name' => "Pay account",
            ],
            "pay_time"     => [
                'field_name' => "Pay time",
            ],
            "invoice_time" => [
                'field_name' => "Invoice time",
            ],
            "run_id"       => [
                'field_name' => "Flows",
            ],
            "remarks"      => [
                'field_name' => "Remarks",
            ]
        ],
    ],
    'contractOrder'            => [
        'title'  => "Order",
        'fileds' => [
            "contract_t_id"   => [
                'field_name' => "Contract",
            ],
            "product_id"    => [
                'field_name' => "Product name",
            ],
            "shipping_date" => [
                'field_name' => "Shipping date",
            ],
            "number"        => [
                'field_name' => "Number",
            ],
            "run_ids"        => [
                'field_name' => "Flows",
            ],
            "remarks"       => [
                'field_name' => "Remarks",
            ]
        ],
    ],
    'contractRemind'           => [
        'title'       => "Remind plan",
        'parent_menu' => 150,
        'fileds'      => [
            "contract_t_id" => [
                'field_name' => "Contract",
            ],
            "user_id"       => [
                'field_name' => "Remind object",
            ],
            "remind_date"   => [
                'field_name' => "Remind date",
            ],
            "content"       => [
                'field_name' => "Remind content",
            ],
            "remarks"       => [
                'field_name' => "Remarks",
            ]
        ],
    ],
    'vehiclesMaintenance' => [
        'title' => "Vehicles maintenance",
        'parent_menu'=>600,
        'fileds' => [
            'vehicles_id' => [
                'field_name' => 'Vehicles name',
                'describe' => 'Vehicles name(vehicles code)'
            ],
            'vehicles_maintenance_type' => [
                'field_name' => 'Maintenance type',
            ],
            'vehicles_maintenance_begin_time' => [
                'field_name' => 'Maintenance begin time',
            ],
            'vehicles_maintenance_end_time' => [
                'field_name' => 'Maintenance end time',
            ],
            'vehicles_maintenance_price' => [
                'field_name' => 'Maintenance price(yuan)',
            ],
            'vehicles_maintenance_project' => [
                'field_name' => 'Maintenance project',
            ],
            'vehicles_maintenance_remark' => [
                'field_name' => 'Remark',
            ],
        ],
        'dataOutSend' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'addVehiclesMaintenanceByFlowOutSend'],
    ],
    'task'                     => [
        'title'  => "Task management",
        'fileds' => [
            'task_name' => [
                'field_name' => 'Task name',
            ],
            'parent_id' => [
                'field_name' => 'Main Task',
                'describe' => 'Main task id, must is integer'
            ],
            'create_user' => [
                'field_name' => 'Create user',
                'describe' => 'Create user ids',
            ],
            'manage_user' => [
                'field_name' => 'Manage user',
                'describe' => 'Manage user ids'
            ],
            'joiner' => [
                'field_name' => 'Joiners',
                'describe' => 'Joiner user ids'
            ],
            'shared' => [
                'field_name' => 'Sharer',
                'describe' => 'Sharer user ids'
            ],
            'task_description' => [
                'field_name' => 'Description',
            ],
            'start_date' => [
                'field_name' => 'Start date',
            ],
            'end_date' => [
                'field_name' => 'End date',
            ],
            'important_level' => [
                'field_name' => 'Important level',
                'describe' => 'Important level: 0 is ordinary, 1 is important, 2 is urgent,'
            ],
            'attachment_ids' => [
                'field_name' => 'attachments',
            ],
        ],
    ],
    'from_type_530_id'         => 'Task management',
    'from_type_530'            => [
        'title'      => 'Task management',
        'id'         => '530_task',
        'additional' => [
            'id'     => '530_task_additional',
            'title'  => 'Subtask management',
            'fields' => [
                'task_name' => [
                    'field_name' => 'Task name',
                ],
                'create_user' => [
                    'field_name' => 'Create user',
                ],
                'manage_user' => [
                    'field_name' => 'Manage user',
                ],
                'joiner' => [
                    'field_name' => 'Joiners',
                ],
                'shared' => [
                    'field_name' => 'Sharer',
                ],
                'task_description' => [
                    'field_name' => 'Description',
                ],
                'start_date' => [
                    'field_name' => 'Start date',
                ],
                'end_date' => [
                    'field_name' => 'End date',
                ],
                'important_level' => [
                    'field_name' => 'Important level',
                ],
                'attachment_ids' => [
                    'field_name' => 'attachments',
                ],
                'parent_id' => [
                    'field_name' => 'Main Task',
                ]
            ],
        ],
    ],
    'chargeAlert'                   => [
        'title'  => "Charge warning",
        'fileds' => [
            'set_type'              => [
                'field_name' => 'Object type',
            ],
            'dept_id' => [
                'field_name' => 'Department'
            ],
            'user_id' => [
                'field_name' => 'User'
            ],
            'role_id' => [
                'field_name' => 'Role'
            ],
            'project_id' => [
                'field_name' => 'Project'
            ],
            'alert_method' => [
                'field_name' => 'Warning method',
            ],
            'alert_data_start'       => [
                'field_name' => 'Warning start time',
            ],
            'alert_data_end'          => [
                'field_name' => 'Warning end time',
            ],
            'subject_check'               => [
                'field_name' => 'Warning value type',
            ],
            'alert_value'               => [
                'field_name' => 'Warning value',
            ],
            'alert_subject'         => [
                'field_name' => 'Warning subject',
            ]
        ],
    ],
];
