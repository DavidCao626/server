<?php
$routeConfig = [
    // 【公司】为前端的基本配置-公司配置列表提供数据
    ['yonyou-voucher/u8/company/config', 'getCompanyConfig'],
    // 【公司】前端获取某一公司的基本配置-公司配置
    ['yonyou-voucher/u8/company/config/{companyId}', 'getOneCompanyConfig'],
    // 【公司】新建公司配置
    ['yonyou-voucher/u8/company/config', 'addCompanyConfig', 'post'],
    // 【公司】编辑公司配置
    ['yonyou-voucher/u8/company/config/{companyId}', 'modifyCompanyConfig', 'put'],
    // 【公司】删除公司配置
    ['yonyou-voucher/u8/company/config/{companyId}', 'deleteCompanyConfig', 'delete'],
    // 【公司】【表单控件】，解析公司列表
    ['yonyou-voucher/u8/company-config', 'getCompanyConfigSelect'],
    // 【公司】【表单控件】，解析科目列表
    ['yonyou-voucher/u8/code-config', 'getCodeConfigSelect'],
    // 【公司】【表单控件】，解析辅助核算列表
    ['yonyou-voucher/u8/auxiliary-config', 'getAuxiliaryConfigSelect'],
    // 【公司】【科目】科目数据来源，上传附件，解析附件excel的表头（前端作为科目编码/名称下拉框的待选项）
    ['yonyou-voucher/u8/company/course/upload/parse-excel-header', 'getCourseUploadExcelHeader'],
    // 【凭证配置】获取U8凭证配置信息
    ['yonyou-voucher/u8/voucher/config/{voucherConfigId}', 'getVoucherConfig'],
    // 【凭证配置】获取U8凭证配置主表信息
    ['yonyou-voucher/u8/voucher/main-config', 'getVoucherMainConfig'],
    // 【凭证配置】新建U8凭证配置主表信息
    ['yonyou-voucher/u8/voucher/main-config', 'addVoucherMainConfig', 'post'],
    // 【凭证配置】编辑U8凭证配置主表信息
    ['yonyou-voucher/u8/voucher/main-config/{voucherConfigId}', 'modifyVoucherMainConfig', 'put'],
    // 【凭证配置】删除U8凭证配置主表信息
    ['yonyou-voucher/u8/voucher/main-config/{voucherConfigId}', 'deleteVoucherMainConfig', 'delete'],
    // 【字段配置】获取字段配置信息，要用param传借/贷类型
    ['yonyou-voucher/u8/voucher/field-config/{voucherConfigId}', 'getVoucherFieldConfig'],
    // 【字段配置】保存字段配置信息，要用param传借/贷类型
    ['yonyou-voucher/u8/voucher/field-config/{voucherConfigId}', 'modifyVoucherFieldConfig', 'put'],
    // 【日志】获取U8凭证操作日志列表
    ['yonyou-voucher/u8/voucher/logs', 'getVoucherLogList', 'get'],
    // 【日志】获取U8凭证操作日志详情
    ['yonyou-voucher/u8/voucher/logDetail/{logId}', 'getVoucherLogDetail', 'get'],
    // 【公司】获取外部数据库公司配置，自动将公司同步到公司配置
    ['yonyou-voucher/u8/company-config-init', 'getCompanyInitConfigFromU8System', 'post'],
    // 【公司】获取外部数据库科目表科目类型
    ['yonyou-voucher/u8/code-type', 'getCodeTypes'],
    // 【公司】获取外部数据库科目表科目年度
    ['yonyou-voucher/u8/code-iyear', 'getCodeIyears'],
    // 凭证预览默认表单数据源
    ['yonyou-voucher/u8/preview/data', 'previewDefaultSource'],
    // 解析返回预览凭证所需数据
    ['yonyou-voucher/u8/preview/voucher', 'previewVoucherData', 'post'],
];
