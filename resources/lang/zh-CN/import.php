<?php

/**
 * 数据导入语言包
 *
 * @author qishaobo
 *
 * @since  2016-06-16 创建
 */
$import = [
    'customer'                  => '客户信息',
    'customer-linkman'          => '客户联系人',
    'emails'                    => '内部邮件',
    'template'                  => '模板',
    'book'                      => '图书',
    'personnelFiles'            => '人事档案',
    'importAttendance'          => '考勤',
    'importPublicAddress'       => '公共通讯录导入',
    'importPersonalAddress'     => '个人通讯录导入',
    'officeSupplies'            => '办公用品导入',
    'user'                      => '用户导入',
    'orginazation'              => '组织结构导入',
    'vacation'                  => '用户假期导入',
    'attendance'                => '考勤导入',
    'synchro'                   => '考勤机账号同步',
    'salary'                    => '薪酬录入',
    'detail-layout'             => '表单明细字段',
    'import_fail'               => '失败',
    'import_success'            => '成功',
    'import_result'             => '导入结果',
    'import_reason'             => '原因',
    'import_data_fail'          => '导入数据时失败',
    'import_report'             => '导入报告',
    'export_data_empty'         => '导出数据不能为空!',
    'update_data_not_found'     => '更新数据查找不到',
    'productInStock'            => '产品入库',
    'productOutStock'           => '产品出库',
    'product'                   => '产品导入',
    'attendanceMachine'         => '考勤机',
    'import_report_fail_excel'  => "导入失败，原因：导入的excel模板不正确",
    "import_report_empty_excel" => "导入失败，原因：导入的excel为空",
    'attendanceMachinetemplate' => '考勤机导入模板',
    'assetsStorage'             => '资产入库导入',
    'assetsApplyRet'            => '资产退库导入',
    'assetsInventory'           => '资产盘点',
    'Custom_page_import_report' => '自定义页面导入报告',
    'Customize_page'            => '自定义页面',
    'contract'                  => '合同导入',
    'customer-supplier'         => '客户供应商',
    'customerProduct'           => '客户产品信息',
    'salaryPersonalDefault'     => '薪酬项个人默认值导入',
    'heterogeneousSystemUser'   => '异构系统用户关联',
    'chargeType'                => '费用类型导入',
    'chargeWarning'             => '费用预警导入',
    'chargeWarningSubject'      => '科目预警值导入',
    'charge'                    => '录入费用导入',

    '0x010001'                  => '导入模板不正确',
    'import_project_template'   => '合同结算导入',
    'contractProject'           => '合同结算导入',
    'contractOrder'             => '合同订单导入',
    'contractRemind'            => '合同提醒计划导入',
];
// 合并第三方二开的导入多语言配置
if (file_exists(resource_path('lang/zh-CN/third_import.php'))) {
    return $import + include 'third_import.php';
}
return $import;
