<?php
return [
    'modules_name' => [
        'Auth'=>'权限',
        'Flow'=>'流程',
        'Document'=>'文档',
        'User'=>'用户',
        'Department'=>'部门',
        'Attachment'=>'附件',
        'News'=>'新闻',
        'Notify'=>'公告',
        'Meeting'=>'会议',
        'Calendar'=>'日程',
        'SystemSms'=>'消息',
        'Menu'=>'菜单',
        'Role'=>'角色',
        'Combobox'=>'下拉内容',
        'Invoice'=>'发票',
        'Customer'=>'客户',
        'Project'=>'项目',
        'Address'=>'地址',
        'Contract'=>'合同',
        'Charge'=>'费用',
        ],
    'modules' => [
        'Auth' => [
            'login'
        ],
        'Flow' => [
            'newPageSaveFlow',           // 创建流程
            'saveFlowRunFlowInfo',       // 保存流程名称、流水号、流程表单等信息
            'showFlowTransactProcess',   // 获取某条流程所有节点，获取&判断可流出节点
            'showFixedFlowTransactUser', // 获取某条【固定】流程，某个可以流出的节点的所有办理人信息
            'flowTurning',               // 固定&自由流程，提交页面提交下一步、提交结束流程
            'teedToDoList',              // 获取某个用户的待办事宜
            'alreadyDoList',             // 获取某个用户的已办事宜
            'finishedList',              // 获取某个用户的办结事宜
            'myRequestList',             // 获取某个用户的我的请求
            'getFlowCopyList',           // 获取某个用户的抄送流程
            'getFlowAgencyRecordList',   // 获取某个用户的委托记录(委托记录或被委托记录)
            'monitorList',               // 获取某个用户的流程监控
            'overtimeList',              // 获取某个用户的超时查询
            'flowSearchList'             // 获取流程查询列表
        ],
        'Document' => [
            'getShowChildrenFolder',     // 用于显示选中文件夹的子文件夹列表
            'listDocument',              // 获取文档列表
            'showDocument'               // 用于获取文档详情
        ],
        'User' => [
            'getUserSystemList',         // 用户列表数据
            'userSystemCreate',          // 新建用户
            'userSystemEdit',            // 编辑用户
            'userSystemDelete'           // 删除用户
        ],
        'Department' => [
            'allTree',                   // 部门列表数据
            'addDepartment',             // 新建部门
            'editDepartment',            // 编辑部门
            'deleteDepartment',          // 删除部门,
            'getDeptDetail'              // 部门详情
        ],
        'Attachment' => [
            'getAttachments',
            'upload',
            "loadAttachment",
        ],
        'News' => [
            'addNews',                   // 新建新闻
            'getList',                   // 获取新闻列表数据
        ],
        'Notify' => [
            'addNotify',                 // 新建公告
            'listNotify',                // 获取公告列表数据
        ],
        'Meeting' => [
            'listOwnMeeting'             // 获取我的会议列表
        ],
        'Calendar' => [
            'getinitList'                // 获取我的日程列表
        ],
        'SystemSms' => [
            'mySystemSms'               // 获取消息列表
        ],
        'Menu' => [
            'getUserMenus',               // 获取某个用户有权限的菜单
            'searchMenuTree',             // 获取菜单列表数据
        ],
        'Role' => [
            'getIndexRoles',                 //获取角色列表数据
            'createRoles',                 //保存角色数据
            'editRoles'                 //编辑角色数据
        ],
        'Combobox' => [
            'getAllFields'              // 获取下拉框字段
        ],
	    'Invoice' => [
            'getThirdAccessToken'       //获取微信token
        ],
        'Customer' => [
            'customerLists',             // 获取客户列表
            'updateCustomer',            // 更新客户信息
            'deleteCustomer',            // 删除客户信息
            'storeCustomer',             // 新增客户信息
            'linkmanLists',              // 联系人列表
            'updateLinkman',             // 更新联系人信息
            'storeContactRecord',        // 添加联系记录
            'contactRecordLists',        // 联系记录列表
        ],
        'Project' => [
            'projectListV2',
            'projectInfoV2',
            'addProjectManager',
            'projectEditV2',
            'projectDeleteV2',
            'taskListV2',
            'taskInfoV2',
            'taskAddV2',
            'taskEditV2',
            'taskDeleteV2',
            'questionListV2',
            'documentListV2',
        ],
        'Address' => [
            'getIndexProvinceCity',      // 获取市列表
        ],
        'Contract' => [
            'lists',                     // 获取合同信息列表
            'recycle',                   // 删除合同信息
            'update',                    // 更新合同信息
        ],
        'Charge' => [
            'chargeListDetail',
            'addNewCharge',
            'editChargeType',
            'chargeTypeSearch',
            'chargeStatistics',
            'chargeSet',
            'getChargeSetList',
            'deleteChargeSetItem',
            'getChargePermission',
            'addChargePermission',
            'editChargePermission',
        ]
    ],
    'system_module' => [
        'Cas',
        'Combobox',
        'CommonTemplate',
        'Company',
        'CustomFields',
        'Database',
        'Department',
        'Domain',
        'ExternalDatabase',
        'Log',
        'Prompt',
        'RedTemplate',
        'Remind',
        'Route',
        'Security',
        'ShortMessage',
        'Signature',
        'SystemMailbox',
        'SystemPhrase',
        'Tag',
        'Template',
        'Webhook',
    ],
    'version' => '1.0.0',
    'second_leave_modules' => ['System'],
    'out_dir' => './public/src-api-doc/',
    'api_dir' => './public/api-doc/',
    'error_code_dir' => './resources/lang/zh-cn/',
    'api_doc_dir' => './public/api_doc',
    'api_doc_template' => './public/api_doc/template'
];
