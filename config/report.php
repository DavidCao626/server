<?php

/**
 * 报表配置
 * brief:
 * datasource_filter:数据过滤
 * 目前有以下几种形式,selector:选择器(单选/多选,date:日期,singleton:下拉框(单选,range:输入范围；input：文本框
 * singleton:除流程外，其他数据源类型需要配置字符串键值，如不使用0,1,2,
 * 使用明确意义的键值:all,progress,complete,['all'=>'全部','progress'=>'进行中','complete'=>'已完成']
 * itemValue:需要过滤的key,itemName:页面展示的名称,filter_type:数据过滤类型
 *
 */
return [
    'datasource_types' => [
    	//流程
    	[
    		//数据源类型
            'datasource_type' => "workflow",
            'class' => 'report.Process_management',
    		//数据源名称
    		'datasource_name' => 'report.flow',
    		//依据数据源类型和流程名称确定分组依据
    		'datasource_custom' => [
    				'itemName' => 'report.flow_name', //页面显示名称
    				'itemValue' => 'workflowID',  //报表参数
    				'selector_type' => 'defineFlowList',//选择器,
    		],
    		//获取分组依据/数据分析字段api
    		'api' =>'api/flow/report/config/group-analyze',
    		//查询参数字段
    		'query_key' =>'flow_id',
    		//分组依据
    		'datasource_group_by' => [
    			'key' => 'datasource_group_by',
    		],
    		//数据分析
    		'datasource_data_analysis' => [
    			'key' => 'datasource_data_analysis',
    		],
    		//时间维度
    		'time_filter' => [
    			'start_time','end_time'
    		],
    		//数据源
    		'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowReportData'],
    		//数据过滤接口
            'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowReportDatasourceFilter'],
    	],
    	//客户
        [
        	//数据源类型
            'datasource_type' => "customer",
            'class' => 'report.Customer_management',
            //数据源名称
            'datasource_name' => 'report.customer',
            //数据源分组依据
            'datasource_group_by' => [
                'customerType' => 'report.customer_type',
				'customerIndustry' => 'report.industry',
            	'customerManager' => 'report.customer_manager',
            	'customerProvince' => 'report.customer_province',
            	'customerCity' => 'report.customer_city',
            	'customerCreateTime' => 'report.customer_create_time',
            	'customer_service_manager' => 'report.customer_service_manager',
            	'customer_status' => 'report.customer_status',
            	'customer_from' => 'report.customer_from',
            	'customer_attribute' => 'report.customer_attribute',
            	'scale' => 'report.scale',
            ],
			//数据源分析字段
            'datasource_data_analysis' => [
                'count' => 'report.count'
            ],
			//数据源
            'datasource_from' => ['App\EofficeApp\Customer\Services\CustomerService', 'getCustomerReportData'],
			//数据过滤
			'datasource_filter' => [
                [
                    //创建人
                    'filter_type' => 'selector',
                    'selector_type' => 'user',
                    'itemValue' => 'customerCreator',
                    'itemName' => 'report.document_creator',
                ],
				[
					//选择客户经理
					'filter_type' => 'selector',
    				'selector_type' => 'user',
					'itemValue' => 'customerManager',
					'itemName' => 'report.customer_manager',
				],
				[
					//选择客服经理
					'filter_type' => 'selector',
					'selector_type' => 'user',
					'itemValue' => 'customer_service_manager',
					'itemName' => 'report.customer_service_manager',
				],
				[
					//省份
					'filter_type' => 'selector',
					'selector_type' => 'province',
					'itemValue' => 'customerProvince',
					'itemName' => 'report.customer_province',
				],
				[
					//城市
					'filter_type' => 'selector',
					'selector_type' => 'city',
					'itemValue' => 'customerCity',
					'itemName' => 'report.customer_city',
				],
				[
					//行业
					'filter_type' => 'selector',
					'selector_type' => 'industry',
					'itemValue' => 'customerIndustry',
					'itemName' => 'report.industry',
				],
				[
					//创建时间
					'filter_type' => 'date',
					'itemValue'=> 'created_at',
					'itemName' => 'report.customer_create_time'
				],

			]
        ],
        // 合同管理模块
            /*
        [
            //数据源类型

            'datasource_type' => "contract_t",
            'class' => 'report.contract_t',
            //数据源名称
            'datasource_name' => 'report.contract',
            //数据源分组依据
            'datasource_group_by' => [
                'contract_t_type' => 'report.contract_t_type', // 款项性质
                'contract_t_pay_type' => 'report.contract_t_pay_type', // 款项类别
                'contract_t_pay_way' => 'report.contract_pay_way',  // 打款方式
            ],
            //数据源分析字段
            'datasource_data_analysis' => [
                'count' => 'report.money',                          // 金额
            ],
            'datasource_filter' => [
                [
                    // 款项性质
                    'filter_type' => 'selector',
                    'selector_type' => 'type',
                    'itemValue' => 'type',
                    'itemName' => 'report.type_name',
                ],
                [
                    // 款项类别
                    'filter_type' => 'selector',
                    'selector_type' => 'pay_type',
                    'itemValue' => 'pay_type',
                    'itemName' => 'report.pay_type_name',
                ],
                [
                    // 打款方式
                    'filter_type' => 'selector',
                    'selector_type' => 'pay_way',
                    'itemValue' => 'pay_way',
                    'itemName' => 'report.pay_way_name',
                ],
            ]
        ],
        */
    	//合同
		[
			 //数据源类型
            'datasource_type' => "contract",
            'class' => 'report.Customer_management',
            'datasource_name' => 'report.contract',
            'datasource_group_by' => [
                'contractType' => 'report.contract_type',
            	'contractStartTime' => 'report.contract_start_time',
            	'contractEndTime' => 'report.contract_end_time',
            	'remind_date' => 'report.remind_date',
            ],
            'datasource_data_analysis' => [
            	'contractAmount'=> 'report.contract_amount',
            	'payment_amount'=> 'report.payment_amount',
            ],
			'time_filter' => [
				'contractStartTime','contractEndTime','remind_date'
			],
            'datasource_from' => ['App\EofficeApp\Customer\Services\CustomerService', 'getContractData'],
			//数据过滤
			'datasource_filter' => [
                [
                    //创建人
                    'filter_type' => 'selector',
                    'selector_type' => 'user',
                    'itemValue' => 'contractCreator',
                    'itemName' => 'report.document_creator',
                ],
				[
					//客户选择器
					'filter_type' => 'selector',
					'selector_type' => 'customer',
					'itemValue' => 'customerName',
					'itemName' => 'report.belongs_to_customer'
				],
				[
					//合同有效期
					'filter_type' => 'date',
					'itemValue' => 'contractDate',
					'itemName' => 'report.contract_valid'
				],
				[
					//收付款时间
					'filter_type' => 'date',
					'itemValue' => 'remind_date',
					'itemName' => 'report.remind_date'
				],
			]
        ],
    	//商机
    	[
    		//数据源类型
            'datasource_type' => "business",
            'class' => 'report.Customer_management',
    		//数据源名称
    		'datasource_name' => 'report.business',
    		//数据源分组依据
    		'datasource_group_by' => [
    				'businessType' => 'report.business_type',
    				'businessSource' => 'report.business_source',
    				'businessStep' => 'report.business_step',
    				'businessEndTime' => 'report.business_end_time'
    		],
    		//数据源分析字段
    		'datasource_data_analysis' => [
				'quote' => 'report.quote'
    		],
    		//时间维度
    		'time_filter' => [
    			'businessEndTime'
    		],
    		//数据源
    		'datasource_from' => ['App\EofficeApp\Customer\Services\CustomerService', 'getBusinessReportData'],
			//数据过滤
    		'datasource_filter' => [
                [
                    //创建人
                    'filter_type' => 'selector',
                    'selector_type' => 'user',
                    'itemValue' => 'customerCreator',
                    'itemName' => 'report.document_creator',
                ],
    			[
    				//客户选择器
    				'filter_type' => 'selector',
    				'selector_type' => 'customer',
    				'itemValue' => 'customerName',
    				'itemName' => 'report.belongs_to_customer'
    			],
    			[
    				//创建时间
    				'filter_type' => 'date',
    				'itemValue'=> 'created_at',
    				'itemName' => 'report.customer_create_time'
    			],
    			[
    				//商机可能性
    				'filter_type' => 'range',
    				'itemValue' => 'chance_possibility',
    				'itemName' => 'report.chance_possibility',
    			],
    		]
    	],
        //用户管理
        [
            //数据源类型
            'datasource_type' => "user",
            'class' => 'report.user_manage',
            //数据源名称
            'datasource_name' => 'report.user_manage',
            //数据源分组依据
            'datasource_group_by' => [
                'userStatus' => 'report.user_state',
                'userDept'   => 'report.user_dept',
                'userRole'   => 'report.user_role'
            ],
            //数据源分析字段
            'datasource_data_analysis' => [
                'count' => 'report.count'
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\User\Services\UserService', 'getUserReportData'],
            //数据过滤
            'datasource_filter_from' => ['App\EofficeApp\User\Services\UserService', 'getUserDatasourceFilter'],
            // 'datasource_filter' => [
            //     [
            //         'filter_type' => 'selector',
            //         'selector_type' => 'dept',
            //         'itemValue' => 'dept_id',
            //         'itemName' => 'report.user_dept'
            //     ],
            //     [
            //         'filter_type' => 'selector',
            //         'selector_type' => 'role',
            //         'itemValue' => 'role_id',
            //         'itemName' => 'report.user_role'
            //     ],
            //     [
            //         'filter_type' => 'singleton',
            //         'selector_type' => 'role',
            //         'itemValue' => 'role_id',
            //         'itemName' => 'report.user_role'
            //     ]
            // ]
        ],

        //项目
        [
            //数据源类型
            'datasource_type' => "project",
            'class' => 'report.Project_management',
            //数据源名称
            'datasource_name' => 'report.project_manage',
            //数据源分组依据
            'datasource_group_by' => [
                'manager_creater' => 'report.manager_creater',
                'manager_person' => 'report.manager_person',
                'manager_type' => 'report.manager_type',
                'manager_fast' => 'report.manager_fast',
                'manager_level' => 'report.manager_level',
                'manager_state' => 'report.manager_state',
            ],
            //数据源分析字段
            'datasource_data_analysis' => [
                'count' => 'report.count',
                'progress' => 'report.project_progress',
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Project\Services\ProjectService', 'getProjectReportData'],
            //数据过滤
            'datasource_filter_from' => ['App\EofficeApp\Project\Services\ProjectService', 'getProjectDatasourceFilter'],
        ],

        //公告管理
        [
            //数据源类型
            'datasource_type' => "notify",
            'class' => 'report.notify_manage',
            //数据源名称
            'datasource_name' => 'report.notify_manage',
            'datasource_custom' => [
                    'itemName' => 'report.notify_name', //页面显示名称
                    'itemValue' => 'notifyId',  //报表参数
                    'selector_type' => 'notifyList',//选择器,
            ],
            //获取分组依据/数据分析字段api
            'api' =>'api/notify/report/config/group-analyze',
            //查询参数字段
            'query_key' =>'notify_id',
            //分组依据
            'datasource_group_by' => [
                'key' => 'datasource_group_by',
            ],
            //数据分析
            'datasource_data_analysis' => [
                'key' => 'datasource_data_analysis',
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Notify\Services\NotifyService', 'getNotifyReportData'],
            'datasource_filter' => [
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'dept',
                    'itemValue' => 'dept_id',
                    'itemName' => 'report.user_dept'
                ],
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'role',
                    'itemValue' => 'role_id',
                    'itemName' => 'report.user_role'
                ]
            ]
        ],

        //文档
        [
            //数据源类型
            'datasource_type' => "document",
            'class' => 'report.document_manage',
            //数据源名称
            'datasource_name' => 'report.document_manage',
            //数据源分组依据
            'datasource_group_by' => [
                'creator' => 'report.document_creator',
            ],
            //数据源分析字段
            'datasource_data_analysis' => [
                'createCount' => 'report.create_count',
                // 'unReadCount' => '未阅读文档',
                'readCount' => 'report.read_count',
                'replyCount' => 'report.reply_count',
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Document\Services\DocumentService', 'getDocumentReportData'],
            //数据过滤
            'datasource_filter' => [
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'dept',
                    'itemValue' => 'dept_id',
                    'itemName' => 'report.user_dept'
                ],
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'role',
                    'itemValue' => 'role_id',
                    'itemName' => 'report.user_role'
                ],
                [
                    'filter_type' => 'date',
                    'itemValue' => 'date_range',
                    'itemName' => 'report.date_range',
                ],
            ]
        ],

        // //项目
        // [
        //     //数据源类型
        //     'datasource_type' => "project",
        //     //数据源名称
        //     'datasource_name' => "项目管理",
        //     //数据源分组依据
        //     'datasource_group_by' => [
        //         'manager_creater' => '项目创建人',
        //         'manager_person' => '项目负责人',
        //         'manager_type' => '项目类型',
        //         'manager_fast' => '紧急程度',
        //         'manager_level' => '优先级别',
        //         'manager_state' => '项目状态',
        //     ],
        //     //数据源分析字段
        //     'datasource_data_analysis' => [
        //         'count' => '数量'
        //     ],
        //     //数据源
        //     'datasource_from' => ['App\EofficeApp\Project\Services\ProjectService', 'getProjectReportData'],
        //     //数据过滤
        //     'datasource_filter_from' => ['App\EofficeApp\Project\Services\ProjectService', 'getProjectDatasourceFilter'],
        // ],
        //流程类型分析
        [
            //数据源类型
            'datasource_type' => "workflowTypeAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.workflow_type_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'flowType' => 'report.flow_type',
                'flowTitle'   => 'flow.0x030073',
            ],
            //数据分析
            'datasource_data_analysis' => [
                'runningcount' => 'report.running_count' , //进行中数量
                'overcount'    => 'report.over_count' ,    //已完成数量
             ],
            //时间维度
            'time_filter' => [
                'start_time','end_time'
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeReportData'],
            //数据过滤接口
            'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeDatasourceFilter'],
            'tips' => 'report.workflow_type_analysis_tips'

        ],
         //待办流程分析
        [
            //数据源类型
            'datasource_type' => "toDoProcessAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.to_do_process_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'user'       => 'report.user',
                'userDept'   => 'report.user_dept',
                'userRole'   => 'report.user_role'
            ],
            //数据分析
            'datasource_data_analysis' => [
                'toDoCount' => 'report.to_do_count' , //进行中数量
             ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowToDoReportData'],
            //数据过滤接口
           'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeHandleDatasourceFilter'],
           'tips' => 'report.to_do_process_analysis_tips'
        ],
         //待办流程路径分析
        [
            //数据源类型
            'datasource_type' => "toDoProcessPathAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.to_do_process_path_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'flowName'   => 'report.flow_name',
            ],
            //数据分析
            'datasource_data_analysis' => [
                'toDoCount' => 'report.to_do_count' , //进行中数量
             ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowToDoPathReportData'],
            //数据过滤接口
           'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeHandleDatasourceFilter'],
            'datasource_filter' => [
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'dept',
                    'itemValue' => 'dept_id',
                    'itemName' => 'report.flow_handler_dept'
                ],
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'role',
                    'itemValue' => 'role_id',
                    'itemName' => 'report.flow_handler_role'
                ],
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'user',
                    'itemValue' => 'user_id',
                    'itemName' => 'report.flow_handler'
                ],
                [
                    'filter_type' => 'date',
                    'itemValue' => 'date_range',
                    'itemName' => 'report.flow_date_range',
                ],
            ],
            'tips' => 'report.to_do_process_path_analysis_tips'
        ],
         //流程效率分析
        [
            //数据源类型
            'datasource_type' => "ProcessEfficiencyAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.process_efficiency_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'flowName'   => 'report.flow_name',
            ],
            //数据分析
            'datasource_data_analysis' => [
                'toDoCount' => 'report.average_handle_time' , //进行中数量
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowEfficiencyReportData'],
            //数据过滤接口
           'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeHandleDatasourceFilter'],
            'datasource_filter' => [
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'dept',
                    'itemValue' => 'dept_id',
                    'itemName' => 'report.flow_handler_dept'
                ],
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'role',
                    'itemValue' => 'role_id',
                    'itemName' => 'report.flow_handler_role'
                ],
                [
                    'filter_type' => 'selector',
                    'selector_type' => 'user',
                    'itemValue' => 'user_id',
                    'itemName' => 'report.flow_handler'
                ],
                [
                    'filter_type' => 'date',
                    'itemValue' => 'date_range',
                    'itemName' => 'report.flow_date_range',
                ],
            ],
            'tips' => 'report.process_efficiency_analysis_tips'
        ],
        //流程办理效率分析
        [
            //数据源类型
            'datasource_type' => "ProcessHandleEfficiencyAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.process_handle_efficiency_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'user'       => 'report.user',
                'userDept'   => 'report.user_dept',
                'userRole'   => 'report.user_role'
            ],
            //数据分析
            'datasource_data_analysis' => [
                'toDoCount' => 'report.average_handle_time' , //平均处理时间
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowHandleEfficiencyReportData'],
            'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeHandleDatasourceFilter'],
            'tips' => 'report.process_handle_efficiency_analysis_tips'
        ],
        //流程超期分析
        [
            //数据源类型
            'datasource_type' => "ProcessLimitEfficiencyAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.process_limit_efficiency_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'flowType' => 'report.flow_type',
                'flowTitle'   => 'flow.0x030073',
            ],
            //数据分析
            'datasource_data_analysis' => [
                'limitCount' => 'report.limit_count' , //超期数量
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowLimitEfficiencyReportData'],
            'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeHandleDatasourceFilter'],
            'tips' => 'report.process_limit_efficiency_analysis_tips'
        ],
         //流程办理超期分析
        [
            //数据源类型
            'datasource_type' => "ProcessHandleLimitAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.process_handle_limit_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'user'       => 'report.user',
                'userDept'   => 'report.user_dept',
                'userRole'   => 'report.user_role'
            ],
            //数据分析
            'datasource_data_analysis' => [
                'limitCount' => 'report.limit_count' , //超期数量
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowHandleLimitReportData'],
            'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeHandleDatasourceFilter'],
            'tips' => 'report.process_handle_limit_analysis_tips'

        ],
         //流程节点办理效率分析
        [
            //数据源类型
            'datasource_type' => "ProcessNodeHandleAnalysis",
            'class' => 'report.Process_management',
            //数据源名称
            'datasource_name' => 'report.process_node_handle_analysis',
            //依据数据源类型或流程名称确定分组依据
            'datasource_group_by' => [
                'node'       => 'report.node_name',
                // 'userDept'   => 'report.user_dept',
                // 'userRole'   => 'report.user_role'
            ],
            //数据分析
            'datasource_data_analysis' => [
                'limitCount' => 'report.average_handle_time' , //超期数量
            ],
            //数据源
            'datasource_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowNodeHandleEfficiencyReportData'],
            'datasource_filter_from' => ['App\EofficeApp\Flow\Services\FlowReportService', 'getFlowTypeHandleDatasourceFilter'],
            'tips' => 'report.process_node_handle_efficiency_analysis_tips'

        ],

]];