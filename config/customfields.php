<?php
return [
    //系统内置选择器
    'selector' => [
        'common' => [
            'user' => ['user', 'user_id', 'user_name'], //表，主键字段名，查询字段名--用户
            'dept' => ['department', 'dept_id', 'dept_name'], //部门
            'role' => ['role', 'role_id', 'role_name'], //角色
        ],
        'customer' => [
            'customer' => ['customer', 'customer_id', 'customer_name'], //客户
            'product' => ['customer_product', 'product_id', 'product_name'], //产品
            'supplier' => ['customer_supplier', 'supplier_id', 'supplier_name'], //供应商
            'business' => ['customer_business_chance', 'chance_id', 'chance_name'], //供应商
            'sale'     => ['App\EofficeApp\Customer\Services\SaleRecordService','getSaleRecord','sales_id','product_name'], // 销售记录
            'customerLinkMans' => ['customer_linkman', 'linkman_id', 'linkman_name'], // 联系人
            'customerContract' => ['customer_contract', 'contract_id', 'contract_name'], // 客户合同

        ],
        'personnelFiles' => [
            'personnelFiles' => ['personnel_files', 'id', 'user_name'], //表，主键字段名，查询字段名--用户
            'personnelFilesManageDept' => ['department', 'dept_id', 'dept_name'],
            'personnelFilesQueryDept' => ['department', 'dept_id', 'dept_name'],
            'personnelFilesManage' => ['personnel_files', 'id', 'user_name'],
            'personnelFilesQuery' => ['personnel_files', 'id', 'user_name'],
        ],
        'officeSupplies' => [
            'officeSuppliesStorage' => ['office_supplies', 'id', 'office_supplies_name'], //办公用品 入库
            'officeSuppliesApply' => ['office_supplies', 'id', 'office_supplies_name'], //办公用品 申请
            'officeSupplies' => ['office_supplies', 'id', 'office_supplies_name'], //办公用品 全部
            'OfficeSuppliesApplyList' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'getOfficeSuppliesApplyName', 'office_supplies_id', 'office_supplies_name'],
            'officeSuppliesStorageList' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'getofficeSuppliesStorageName', 'office_supplies_id', 'office_supplies_name'],
        ],
        'document' => [
            'documentAllFolder' => ['document_folder', 'folder_id', 'folder_name'], //文档目录
            'document' => ['document_content', 'document_id', 'subject'], //文档选择器
        ],
        'address' => [
            'personalVGroup' => ['address_person_group', 'group_id', 'group_name'], //个人目录查看
            'publicVGroup' => ['address_public_group', 'group_id', 'group_name'], //公共目录查看
            'publicMGroup' => ['address_public_group', 'group_id', 'group_name'], //公共目录管理
            'publicAddress' => ['address_public', 'address_id', 'primary_1'], //公共目录管理
            'personalAddress' => ['address_private', 'address_id', 'primary_1'], //公共目录管理
        ],
        'book' => [
            'bookName' => ['book_info', 'id', 'book_name'], //图书选择
            'bookManage'=>  ['App\EofficeApp\Book\Services\BookService', 'getBookkManageName', 'book_id', 'book_name'], 
        ],
        'flow' => [
            'permissionFlowRun' => ['flow_run', 'run_id', 'run_name'], //当前用户能看到的所有流程，数据等同于流程查询
        ],
        'incomeexpense' => [
            'statPlan' => ['income_expense_plan', 'plan_id', 'plan_name'], //全部方案
            'recordPlan' => ['income_expense_plan', 'plan_id', 'plan_name'], //进行中方案
        ],
        'news' => [
            'news' => ['news', 'news_id', 'title'],
            'newsType' => ['App\EofficeApp\News\Services\NewsService', 'getNewsTypeForFormModelingList', 'news_type_id', 'news_type_name'], //新闻类别选择器
        ],
        'notify' => [
            'notify' => ['notify', 'notify_id', 'subject'],
            'notifyType' => ['App\EofficeApp\Notify\Services\NotifyService', 'getNotifyTypeForFormModelingList', 'notify_type_id', 'notify_type_name'], //公告类别选择器
        ],
        'product' => [
            'products' => ['product', 'product_id', 'product_name'], //产品选择器
            'productType' => ['product_type', 'product_type_id', 'product_type_name'], //产品类别选择器
        ],
        'storage' => [
            'warehouse' => ['storage_warehouse', 'warehouse_id', 'warehouse_name'], //仓库选择器
        ],
        'project' => [
            'myProject' => ['project_manager', 'manager_id', 'manager_name'], // 我的项目选择器
            'myMonitorProject' => ['project_manager', 'manager_id', 'manager_name'], // 我监控的项目
            'myManagerProject' => ['project_manager', 'manager_id', 'manager_name'], // 我管理的项目
            'myJoinProject' => ['project_manager', 'manager_id', 'manager_name'], // 我参与的项目
            'myCreateProject' => ['project_manager', 'manager_id', 'manager_name'], // 我创建的项目
            'myApprovalProject' => ['project_manager', 'manager_id', 'manager_name'], // 我审核的项目
            'projectType' => ['App\EofficeApp\Project\Services\ProjectService', 'getCustomProjectType', 'field_value', 'field_name'],
        ],
        'task' => [
            'myTask' => ['task_manage', 'id', 'task_name'],
            'taskClass' => ['App\EofficeApp\Task\Services\TaskService', 'getCustomTaskClass', 'class_id', 'class_name'],
        ],
        'contract' => [
            'contract' => ['contract_t', 'id', 'title'],
            'myContract' => ['contract_t', 'id', 'title'],
            'allContract' => ['contract_t', 'id', 'title'],
        ],
        'meeting' => [
            'externalUser' => ['meeting_external_user', 'external_user_id', 'external_user_name'], 
            'myMeeting' => ['meeting_apply', 'meeting_apply_id', 'meeting_subject'], 
        ],
        'vehicles' => [
            'myVehicles' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'getMyVehiclesDetailForCustom', 'vehicles_apply_id', 'vehicles_name'],
        ],
        'calendar' => [
            'myCalendar' => ['calendar', 'calendar_id', 'calendar_content']
        ],
        'assets' => [
            'assetsApply' => ['assets', 'id', 'assets_name'],
            'assetsRepair' => ['assets', 'id', 'assets_name'],
            'assetsRetire' => ['assets', 'id', 'assets_name'],
        ],
        'archives' => [
            'archivesFiles' => ['archives_file', 'file_id', 'file_name'],
            'archivesVolume' => ['archives_volume', 'volume_id', 'volume_name'],
            'archivesLibrary' => ['archives_library', 'library_id', 'library_name'],
        ],
        'charge' => [
            'chargeType' => ['charge_type', 'charge_type_id', 'charge_type_name'],
            'chargeSetting' => ['App\EofficeApp\Charge\Services\ChargeService', 'getCustomChargeSetting', 'charge_setting_id', 'name'],
            'charge' => ['App\EofficeApp\Charge\Services\ChargeService', 'getCustomCharge', 'charge_list_id', 'new_name'],
            'myChargeConfig' => ['App\EofficeApp\Charge\Services\ChargeService', 'getCustomCharge', 'charge_list_id', 'new_name'],
            'myUndertakeChargeConfig' => ['App\EofficeApp\Charge\Services\ChargeService', 'getCustomCharge', 'charge_list_id', 'new_name'],

        ],
        'invoice' => [
            'invoiceUnreimburseList' => ['App\EofficeApp\Invoice\Services\InvoiceService', 'getInvoicesByIds', 'id', 'code_value'],
        ]
    ],
    //系统数据下拉框
    'systemDataSelect' => [
        'charge' => [
            'charge_type_parent' => ['charge_type', 'charge_type_id', 'charge_type_name'], //一级报销科目
            'charge_type' => ['charge_type', 'charge_type_id', 'charge_type_name'], //二级报销科目
            'charge_undertake' => ['charge_undertake', 'charge_undertake_id', 'charge_undertake_name'], // 费用承担者
            'alert_method' => ['App\EofficeApp\Charge\Services\ChargeService', 'getChargeAlertMethods', 'alert_method', 'alert_method_name'],
            'set_type' => ['App\EofficeApp\Charge\Services\ChargeService', 'getSetTypes', 'set_type', 'set_type_name'],
        ], //费用
        'vehicles' => [
            'vehicles_name' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'getVehiclesNameCode', 'vehicles_id', 'vehicles_name_code'],
            'vehicles_sort_id' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'getVehiclesSort', 'vehicles_sort_id', 'vehicles_sort_name'],
        ], // 车辆
        'meeting' => [
            'room_name' => ['meeting_rooms', 'room_id', 'room_name'],
            'meeting_wifi_name' => ['meeting_wifi', 'meeting_wifi_id', 'meeting_wifi_name'],
            'interface_id'  => ['third_party_videoconference', 'videoconference_id', 'name']

        ],
        'attendance' => [
            'overtime_to' => ['App\EofficeApp\Attendance\Services\AttendanceOutSendService', 'getOvertimeTo', 'id', 'name']
        ]
        , //会议
        'vacation' => ['vacation', 'vacation_id', 'vacation_name'], //假期
        'customer' => [
            'linkman_id' => ['customer_linkman', 'linkman_id', 'linkman_name'],
            'contract_id' => ['customer_contract', 'contract_id', 'contract_name'],
            'seas_group_id' => ['customer_seas_group', 'id', 'name'],
        ],
        'contract' => ['contract_t_type', 'id', 'name'],
        'book' => ['book_type', 'id', 'type_name'], //图书类别
        'diary' => ['App\EofficeApp\Diary\Services\DiaryService', 'getDiaryTemplateType', 'diary_type_id', 'diary_type_title'], //从api获取返回值 service  方法名 主键 显示字段
        'personnel_files' => ['user_status', 'status_id', 'status_name'], //用户状态
//        "storage" => ['storage_warehouse', 'warehouse_id', 'warehouse_name'],
        "storage" => [
            'warehouse'=> ['storage_warehouse', 'warehouse_id', 'warehouse_name'],
            'product_batchs'=> ['storage_stock_in_records', 'record_id', 'product_batchs'],
        ],
        'archives' => [
            'library_id' => ['archives_library', 'library_id', 'library_name'],
            'volume_id' => ['archives_volume', 'volume_id', 'volume_name'],
            'hold_time' => ['archives_hold_time', 'hold_time_id', 'hold_time_title'],
        ], //档案
        'project' => [
            'manager_state' => ['App\EofficeApp\Project\Services\ProjectService', 'getPorjectManagerStateList', 'state_id', 'state_name'],
            'manager_number' => ['project_manager', 'manager_id', 'manager_number']
        ], //从api获取返回值 service  方法名 主键 显示字段
        'salary' => ['salary_fields', 'field_id', 'field_name'], //薪酬
        'assets' => [
            'assets_type' => ['assets_type', 'id', 'type_name'], //资产分类
            'apply_id' => ['assets', 'id', 'assets_name'], //资产申请,
            'repair_id' => ['assets', 'id', 'assets_name'], //资产维护,
            'signout_id' => ['assets', 'id', 'assets_name'], //资产退库,
            'manager' => ['App\EofficeApp\Assets\Services\AssetsService', 'getManagerName', 'user_id', 'approver'], //资产申请审批人,
            'apply_way' => ['App\EofficeApp\Assets\Services\AssetsService', 'applyWay', 'id', 'name'], // 获取领用方式,

        ], //费用
        'incomeexpense' => ['income_expense_plan_type', 'plan_type_id', 'plan_type_name'], //收支方案
        'calendar' => ['calendar_type', 'type_id', 'type_name'], //收支方案
        'user' => ['user_status', 'status_id', 'status_name'], //用户状态
        'officeSupplies' => ['office_supplies_type','id','type_name'],
    ],
    //过滤函数
    'filter' => [
        'customer' => ['App\EofficeApp\Customer\Services\CustomerService', 'filterCustomerLists'], //(返回固定格式数组)如：['customer_id' => [[],'in']] ,全部：返回 [];
        'customer_linkman' => ['App\EofficeApp\Customer\Services\LinkmanService', 'filterLinkmanLists'],
        'customer_contract' => ['App\EofficeApp\Customer\Services\ContractService', 'filterContractLists'],
        'customer_business_chance' => ['App\EofficeApp\Customer\Services\BusinessChanceService', 'filterBusinessChanceLists'],
//        'office_supplies_storage' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'filterStorageNameList'],
        'address_private' => ['App\EofficeApp\Address\Services\AddressService', 'filterAddressPrivateList'],
        'contract_t' => ['App\EofficeApp\Contract\Services\ContractService', 'filterLists'],
        'office_supplies_storage' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'filterStorageLists'],
        'contract_t_project' => ['App\EofficeApp\Contract\Services\ContractService', 'filterProjectList'],
        'contract_t_order' => ['App\EofficeApp\Contract\Services\ContractService', 'filterOrderList'],
        'contract_t_remind' => ['App\EofficeApp\Contract\Services\ContractService', 'filterRemindList'],
    ],
    // 自己获取总数
    'customTotal' => [
        'customer' => ['App\EofficeApp\Customer\Services\CustomerService', 'getCustomerTotal'],
    ],
    //需要翻译的表
    'table' => ['user_status', 'province', 'city', 'archives_hold_time', 'charge_undertake', 'district', 'calendar_type'],
    //列表返回以下数据但是不展示
    'fields' => [
        'customer' => ['customer_manager', 'customer_service_manager', 'last_contact_time', 'phone_number'],
        'customer_linkman' => ['main_linkman', 'mobile_phone_number', 'customer_id'],
        'book_info' => ['book_remainder'],
        'contract_t' => ['content', 'status', 'user_id', 'created_at'],
    ],
    //字段方法各模块单独处理
    'fields_show' => [
        'my_borrow' => ['App\EofficeApp\Book\Services\BookService', 'handleMyBorrowFields'],
    ],
    //编辑各模块需要单独处理
    'editDataAfter' => [
    ],
    //新增各模块需要单独处理
    'addDataAfter' => [
        'office_supplies_storage' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'officeSuppliesStorageAfterAdd'],
        'customer' => ['App\EofficeApp\Customer\Services\CustomerService', 'customerAfterAdd'],
        'customer_linkman' => ['App\EofficeApp\Customer\Services\LinkmanService', 'linkmanAfterAdd'],
        'contract_t' => ['App\EofficeApp\Contract\Services\ContractService', 'contractAfterAdd'],
    ],
    //详情前置处理
    'detailBefore' => [
        'customer_linkman' => ['App\EofficeApp\Customer\Services\CustomerService', 'filterLinkmanDetail'],
        'customer_business_chance' => ['App\EofficeApp\Customer\Services\CustomerService', 'filterBusinessChanceDetail'],
        'archives_volume' => ['App\EofficeApp\Archives\Services\ArchivesService', 'getVolumePrivate'], //案卷管理
        'archives_file' => ['App\EofficeApp\Archives\Services\ArchivesService', 'getFilePrivate'], //文件管理
        'archives_library' => ['App\EofficeApp\Archives\Services\ArchivesService', 'getLibraryPrivate'], //卷库管理
        'address_public' => ['App\EofficeApp\Address\Services\AddressService', 'addressPublicDetailPurview'], //公共通讯录
        'address_private' => ['App\EofficeApp\Address\Services\AddressService', 'addressPrivateDetailPurview'], //公共通讯录
        'personnel_files' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'personnelFilePower'], //人事档案权限
        'office_supplies_storage' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'storageDetailPower'], //人事档案权限
        'customer' => ['App\EofficeApp\Customer\Services\CustomerService', 'filterBeforeCustomerDetail'], //客户回收站详情处理
        'vehicles' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'vehiclesCustomFieldsValidateDelete'],
        'contract_t_order' => ['App\EofficeApp\Contract\Services\ContractService', 'BeforeOrderDetail'],
        'contract_t_project' => ['App\EofficeApp\Contract\Services\ContractService', 'BeforeProjectDetail'],
        'contract_t_remind' => ['App\EofficeApp\Contract\Services\ContractService', 'BeforeRemindDetail'],
        'contract_t' => ['App\EofficeApp\Contract\Services\ContractService', 'BeforeContractDetail'],
    ],
    //新增前置处理
    'addBefore' => [
        'address_private' => ['App\EofficeApp\Address\Services\AddressService', 'addressPrivateAddPurview'],
        'address_public' => ['App\EofficeApp\Address\Services\AddressService', 'addressPublicAddPurview'],
        'personnel_files' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'filterPersonnelFilesAdd'],
        'office_supplies_storage' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'officeSuppliesStorageAddPurview'],
        'vehicles' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'vehiclesCustomFieldsValidate'],
    ],
    //编辑前置处理
    'editBefore' => [
        'address_private' => ['App\EofficeApp\Address\Services\AddressService', 'addressPrivateEditPurview'],
        'address_public' => ['App\EofficeApp\Address\Services\AddressService', 'addressPublicEditPurview'],
        'personnel_files' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'filterPersonnelFilesEdit'], //(返回固定格式数组)
        'vehicles' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'vehiclesCustomFieldsValidate'],
    ],
    //删除前置处理
    'deleteBefore' => [
        'address_private' => ['App\EofficeApp\Address\Services\AddressService', 'addressPrivateDeletePurview'],
        'address_public' => ['App\EofficeApp\Address\Services\AddressService', 'addressPublicDeletePurview'],
        'vehicles' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'vehiclesCustomFieldsDeleteValidate'],
    ],
    //获取列表默认排序
    'dataListRepositoryOrder' => [
        'personnel_files' => ['personnel_files.id' => 'asc'],
    ],
    //表单解析数据，特殊解析
    'parseFormData' => [
        'income_expense_plan' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'dealIncomeExpenseField'],
    ],
    //模块特殊检查唯一性
    'checkUnique' => [
        'address_private' => ['App\EofficeApp\Address\Services\AddressService', 'checkPrivateUnique']
    ],
    'menu' => [
        'customer' => '502',
        'customer_linkman' => '503',
        'customer_product' => '509',
        'customer_business_chance' => '505',
        'customer_supplier' => '508',
        'customer_contract' => '506',
        'product' => '171',
        'address_public' => ['25', '67'],
        'address_private' => '23',
        'book_manage' => '80', //图书借阅归还
        'book_info' => ['79', '35'],
        'my_borrow' => '77',
        'office_supplies_storage' => '267', //入库管理
        'personnel_files' => ['416', '417'], //人事档案
        'archives_volume' => '902', //案卷管理
        'archives_file' => '903', //文件管理
        'archives_borrow' => '904', //借阅管理
        'archives_appraisal' => '911', //档案鉴定
        'archives_library' => '901', //卷库管理
        'contract_t' => ['151','152'], //合同管理
        'assets' => '921', //固定资产
        'contract_t_remind' => ['151','152'], //提醒计划
        'contract_t_project' => ['151','152'], //结算情况
        'contract_t_order' => ['151','152'], //合同订单
    ],
    'addMenu' => [
        'customer' => '502',
        'customer_linkman' => '503',
        'customer_product' => '509',
        'customer_business_chance' => '505',
        'customer_supplier' => '508',
        'customer_contract' => '506',
        'product' => '171',
        'address_public' => '67',
        'address_private' => '23',
        'book_manage' => '80', //图书借阅归还
        'book_info' => ['79', '35'],
        'my_borrow' => '77',
        'office_supplies_storage' => '267', //入库管理
        'personnel_files' => '418', //人事档案
        'archives_volume' => '902', //案卷管理
        'archives_file' => '903', //文件管理
        'archives_borrow' => '904', //借阅管理
        'archives_appraisal' => '911', //档案鉴定
        'archives_library' => '901', //卷库管理
        'contract_t' => ['151','152'], //合同管理
        'assets' => '921', //固定资产
        'contract_t_remind' => ['151','152'], //提醒计划
        'contract_t_project' => ['151','152'], //结算情况
        'contract_t_order' => ['151','152'], //合同订单
    ],
    'editMenu' => [
        'customer' => '502',
        'customer_linkman' => '503',
        'customer_product' => '509',
        'customer_business_chance' => '505',
        'customer_supplier' => '508',
        'customer_contract' => '506',
        'product' => '171',
        'address_public' => '67',
        'address_private' => '23',
        'book_manage' => '80', //图书借阅归还
        'book_info' => ['79', '35'],
        'my_borrow' => '77',
        'office_supplies_storage' => '267', //入库管理
        'personnel_files' => '417', //人事档案
        'vehicles' => '604', //用车管理
        'archives_volume' => '902', //案卷管理
        'archives_file' => '903', //文件管理
        'archives_borrow' => '904', //借阅管理
        'archives_appraisal' => '911', //档案鉴定
        'archives_library' => '901', //卷库管理
        'contract_t' => ['151','152'], //合同管理
        'assets' => ['921', '924'], //固定资产
        'contract_t_remind' => ['151','152'], //提醒计划
        'contract_t_project' => ['151','152'], //结算情况
        'contract_t_order' => ['151','152'], //合同订单
    ],
    'detailMenu' => [
        'customer' => '502',
        'customer_linkman' => '503',
        'customer_product' => '509',
        'customer_business_chance' => '505',
        'customer_supplier' => '508',
        'customer_contract' => '506',
        'product' => '171',
        'address_public' => ['25', '67'],
        'address_private' => '23',
        'book_manage' => ['80', '77'], //图书借阅归还
        'book_info' => ['79', '35'],
        'my_borrow' => '77',
        'office_supplies_storage' => '267', //入库管理
        'personnel_files' => ['416', '417', '419'], //人事档案
        'vehicles' => '604', //用车管理
        'archives_volume' => '902', //案卷管理
        'archives_file' => '903', //文件管理
        'archives_borrow' => '904', //借阅管理
        'archives_appraisal' => '911', //档案鉴定
        'archives_library' => '901', //卷库管理
        'contract_t' => ['151','152'], //合同管理
        'assets' => '920', //
        'income_expense_plan' => '137',
        'contract_t_remind' => ['151','152'], //提醒计划
        'contract_t_project' => ['151','152'], //结算情况
        'contract_t_order' => ['151','152'], //合同订单
    ],
    'deleteMenu' => [
        'customer' => '502',
        'customer_linkman' => '503',
        'customer_product' => '509',
        'customer_business_chance' => '505',
        'customer_supplier' => '508',
        'customer_contract' => '506',
        'product' => '171',
        'address_public' => '67',
        'address_private' => '23',
        'book_manage' => '80', //图书借阅归还
        'book_info' => ['79', '35'],
        'my_borrow' => '77',
        'office_supplies_storage' => '267', //入库管理
        'personnel_files' => '417', //人事档案
        'vehicles' => '604', //用车管理
        'archives_volume' => '902', //案卷管理
        'archives_file' => '903', //文件管理
        'archives_borrow' => '904', //借阅管理
        'archives_appraisal' => '911', //档案鉴定
        'archives_library' => '901', //卷库管理
        'contract_t' => ['151','152'], //合同管理
        'assets' => '921', //固定资产
        'contract_t_remind' => ['151','152'], //提醒计划
        'contract_t_project' => ['151','152'], //结算情况
        'contract_t_order' => ['151','152'], //合同订单
    ],
    'menuForeignKey' => [
        // 客户
        'customer_customer' => 'customer',
        'customer_business' => 'customer_business_chance',
        'customer_supplier' => 'customer_supplier',
        'customer_product' => 'customer_product',
        // 图书
        'book_bookName' => 'book_info',
        // 通讯录
        'address_personalAddress' => 'address_private',
        'address_personalVGroup' => 'address_private',
        'address_publicAddress' => 'address_public',
        'address_publicVGroup' => 'address_public',
        'address_publicMGroup' => 'address_public',
        // 资产
        'assets_assetsApply' => 'assets',
        'assets_assetsRepair' => 'assets',
        'assets_assetsRetire' => 'assets',
        // 合同
        'contract_contract' => 'contract_t',
        'contract_myContract' => 'contract_t',
        'contract_allContract' => 'contract_t',
        // 档案管理
        'archives_archivesFiles' => 'archives_file',
        'archives_archivesVolume' => 'archives_volume',
        'archives_archivesLibrary' => 'archives_library',
        // 项目
        'project_myApprovalProject' => 'project_value_',
        'project_myCreateProject' => 'project_value_',
        'project_myJoinProject' => 'project_value_',
        'project_myManagerProject' => 'project_value_',
        'project_myMonitorProject' => 'project_value_',
        'project_myProject' => 'project_value_',
        // 产品
        'product_products' => 'product',
        'product_productType' => 'product',
        // 人事档案
        'personnelFiles_personnelFiles' => 'personnel_files',
        // 办公用品入库
        'officeSupplies_officeSuppliesStorage' => 'office_supplies_storage',

    ],
    //导入模板中没有的字段
    'importDisabledFields' => [
        'customer' => ['customer_creator', 'created_at', 'last_contact_time'],
        'assets' => ['registr_number', 'created_at', 'expire_at'],
        'address_public' => ['primary_5'],
        'address_private' => ['primary_5'],
        'contract_t' => ['status','created_at','updated_at','creator'],
        'customer_product' => ['product_creator'],
//        'book_info' => ['book_remainder'],
    ],
    //获取详情后各模块自己处理数据方法
    'detailAfter' => [
        'customer' => ['App\EofficeApp\Customer\Services\CustomerService', 'filterCustomerDetail'],
        'assets' => ['App\EofficeApp\Assets\Services\AssetsService', 'assetsAfterDetail'],
        'customer_linkman' => ['App\EofficeApp\Customer\Services\LinkmanService', 'linkmanAfterDetail'],
        'project_value_' => ['App\EofficeApp\Project\Services\ProjectService', 'handleCustomProjectDetail'],
    ],

    'listAfter' => [
        'customer' => ['App\EofficeApp\Customer\Services\CustomerService', 'filterCustomerList'],
    ],

];
