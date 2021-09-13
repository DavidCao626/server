<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$routeConfig = [
    // 发票列表
    ['invoice/list', 'getList', 'get'],
    // 未报销发票列表
    ['invoice/list/unreimburse', 'getUnreimburseInvoiceList', 'get', [551]],
    // 获取发票
    ['invoice', 'getInvoice', 'get'],
    ['invoice/detail/{invoiceId}', 'getOneInvoice', 'get'],
    // 新建发票
    ['invoice', 'addInvoice', 'post'],
    // 更新发票
    ['invoice', 'updateInvoice', 'put'],
    // 删除发票
    ['invoice', 'deleteInvoice', 'delete'],
    // 发票抬头列表
    ['invoice/titles', 'getTitles', 'get'],
    ['invoice/titles/personal', 'getPersonalTitles', 'get'],
    // 发票抬头
    ['invoice/title', 'getTitle', 'get'],
    // 新增发票抬头
    ['invoice/title', 'addInvoiceTitle', 'post'],
    // 更新发票抬头
    ['invoice/title', 'updateInvoiceTitle', 'put'],
    // 删除发票抬头
    ['invoice/title', 'deleteInvoiceTitle', 'delete'],
    // 创建团队
    ['invoice/corp/create', 'createCorp', 'post'],
    // 编辑团队信息
    ['invoice/corp/create', 'updateCorp', 'put'],
    // 根据账号查找团队
    ['invoice/corp/query', 'QueryCorpByAccount', 'get'],
    // 根据cid查找团队
    ['invoice/corp/query/cid', 'QueryCorpByCid', 'get'],
    // 获取所有团队
    ['invoice/corps', 'QueryCorpAll', 'get'],
    // 获取
    ['invoice/corp/query/cid', 'QueryCorpByCid', 'get'],
    // 开启识别
    ['invoice/recognize/open', 'openRecognize', 'post'],
    // 开启验真
    ['invoice/valid/open', 'openValid', 'post'],
    // 开启自动验真
    ['invoice/auto-valid/open', 'openAutoValid', 'post'],
    // 设置验真金额
    ['invoice/total-corp', 'totalCorp', 'post'],
    // 设置验真用户
    ['invoice/valid-users', 'corpValidUsers', 'post'],
    // 设置报销时间
    ['invoice/reim-vim', 'corpReimVtm', 'post'],
    // 企业设置允许报销那些税号列表
    ['invoice/corp-taxnos', 'corpTaxnos', 'post'],
    // 企业设置是否指定部分抬头报销
    ['invoice/corp-taxno', 'corpTaxno', 'post'],
    // 企业设置是否指定部分抬头报销
    ['invoice/reim', 'reim', 'post'],
    // 同步配置
    ['invoice/sync-corp', 'syncCorpConfig'],
    // 单个同步用户到发票云
    ['invoice/sync/user', 'syncUser', 'post'],
    // 批量同步用户到发票云
    ['invoice/sync/users', 'batchSyncUser', 'post'],
    // 用户列表
    ['invoice/users', 'userList', 'get'],
    ['invoice/users', 'userList', 'post'],
    // 更新用户
    ['invoice/sync/user', 'updateUser', 'put'],

    // 发票管理配置参数
    ['invoice/manage/params', 'getInvoiceManageParams', 'get'],
    // 编辑发票管理配置参数
    ['invoice/manage/params', 'saveInvoiceManageParams', 'post'],

    // 日志列表
    ['invoice/logs', 'getLogs', 'get'],
    ['invoice/log/{logId}', 'getLog', 'get'],

    // 流程集成配置列表
    ['invoice/flow/settings', 'getFlowSettings', 'get'],
    // 获取流程集成配置
    ['invoice/flow/setting/{settingId}', 'getFlowSetting', 'get'],
    // 新建流程集成配置
    ['invoice/flow/setting', 'addFlowSetting', 'post'],
    // 编辑流程集成配置
    ['invoice/flow/setting/{settingId}', 'editFlowSetting', 'put'],
    // 删除流程集成配置
    ['invoice/flow/setting/{settingId}', 'deleteFlowSetting', 'delete'],
    // 选择发票发起流程获取已配置的流程信息
    ['invoice/flow/settings/name', 'getSettingsOnlyIdName', 'get'],
    // 获取默认流程
    ['invoice/flow/settings/default', 'getDefaultSetting', 'get'],
    

    // 发票类型/消费类型
    ['invoice/types', 'getTypes', 'get'],

    // 发票上传文件识别
    ['invoice/file/upload', 'invoiceFileUpload', 'post'],
    // 列表发票验真
    ['invoice/valid', 'validInvoice', 'post'],
    // 手动新建发票验真
    ['invoice/valid/input', 'validInputInvoice', 'post'],
    // 企业票夹验真
    ['invoice/valid/corp', 'corpValidInvoice', 'post'],

    // 获取应用信息
    ['invoice/app-info', 'checkAppInfo', 'get'],
    // 创建应用
    ['invoice/app', 'createApp', 'post'],
    ['invoice/app/{configId}', 'updateApp', 'put'],
    ['invoice/app', 'queryApp'],

    // 获取启用的发票云服务信息

    ['invoice/test', 'test', 'get'],
    ['invoice/check-service', 'checkService', 'get'],

    // 同步微信发票
    ['invoice/add/wx', 'wxAddInvoice', 'post'],
    ['invoice/wx-token', 'getThirdAccessToken', 'post'],

    // 企业票夹
    ['invoice/receipts', 'receiptsInvoices', 'get'],
    ['invoice/sales', 'salesInvoices', 'get'],
    ['invoice/corp', 'corpInvoices'],


    ['invoice/statistics', 'invoiceStatistics', 'get'],

    ['invoice/check-flow-relation', 'checkFlowRelation', 'get'],
    // 同步三方appkey至发票云
    ['invoice/third/appkey', 'thirdSetAppkey', 'post'],
    // 获取三方key
    ['invoice/third/appkey', 'getThirdAppkey'],

    ['invoice/check-in-use', 'getIsUseInvoiceCloud'],
    // 获取导入的发票云页面链接
    ['invoice/import', 'getImportUrl'],
    // 获取识别查验次数
    ['invoice/recharge', 'getRecharge'],
    ['invoice/recharge/log', 'getRechargeLog'],
    // 共享发票
    ['invoice/share/user', 'shareUser', 'post'],
    // 同步发票共享人信息
    ['invoice/share/sync', 'shareSync'],

    ['invoice/valid/right/{type}', 'getValidRight'],

    ['invoice/batch', 'getInvoiceBatch'],
    // 报销前检测
    ['invoice/check-before', 'checkBefore', 'post'],
    // 手动取消报销
    ['invoice/cancel', 'cancelInvoice', 'post'],

    ['invoice/wx/param', 'getInvoiceParams'],
    ['invoice/field', 'getInvoiceField'],
    ['invoice/fields', 'invoiceFieldKeys']
];
