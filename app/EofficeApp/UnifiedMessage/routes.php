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
  | 模块迁移至集成中心  接口权限集成父级菜单id
 */

$routeConfig = [
    //【异构系统】获取注册异构系统标识
    ['unified-message/heterogeneous-system/get/register', 'registerHeterogeneousSystemCode','get'],
    //【异构系统】刷新注册异构系统标识
    ['unified-message/heterogeneous-system/refresh/system_code', 'refreshHeterogeneousSystemCode','post'],
    //【异构系统】注册异构系统
    ['unified-message/heterogeneous-system/register', 'registerHeterogeneousSystem','post'],
    //【异构系统】删除异构系统
    ['unified-message/heterogeneous-system/{id}', 'deleteHeterogeneousSystem','delete'],
    //【异构系统】编辑异构系统消息接收
    ['unified-message/heterogeneous-system/edit/switch', 'editMessageSwitch','post'],
    //【异构系统】查询异构系统
    ['unified-message/heterogeneous-system/{id}', 'getHeterogeneousSystem','get'],
    //【异构系统】查询异构系统列表
    ['unified-message/heterogeneous-system/get/list', 'getHeterogeneousSystemList','get'],
    //【异构系统】查询异构系统同时返回第三方消息跳转的域名
    ['unified-message/heterogeneous-system/get-info/read-message', 'getDomainReadMessage','post'],
    //【用户关联】添加用户关联
    ['unified-message/user-bonding/add', 'addUserBonding','post'],
    //【用户关联】删除用户关联ById
    ['unified-message/user-bonding/{id}', 'deleteUserBondingById','delete'],
    //【用户关联】批量删除用户关联
    ['unified-message/user-bonding/batch/delete', 'batchDeleteUserBinding','post'],
    //【用户关联】删除所有用户关联
    ['unified-message/user-bonding/delete/all', 'deleteAllUserBonding','delete'],
    //【用户关联】编辑用户关联
    ['unified-message/user-bonding/{id}', 'editUserBonding','post'],
    //【用户关联】查看用户关联ById
    ['unified-message/user-bonding/{id}', 'getUserBondingById','get'],
    //【用户关联】用户关联列表
    ['unified-message/user-bonding/get/list', 'getUserBondingList','get'],
    //【消息数据】删除第三方消息数据
    ['unified-message/message-data/delete/{id}', 'deleteMessageByWhere','delete'],
    //【用户关联】批量删除第三方消息数据
    ['unified-message/message-data/batch/delete', 'batchDeleteMessage','post'],
    //【消息数据】查看第三方消息数据
    ['unified-message/message-data/read/{id}', 'readMessage','get'],
    //【消息数据】获取异构系统消息类型
    ['unified-message/heterogeneous-system/message-type/list', 'getHeterogeneousSystemMessageTypeList','get'],
//    //【用户关联】导出用户关联模板
//    ['unified-message/user-bonding/export-template', 'exportUserBonding','get'],
//    //【用户关联】批量导入用户关联
//    ['unified-message/user-bonding/import-template', 'importUserBonding','post'],
//    //【用户关联】外部系统用户关联sso
//    ['unified-message/user-bonding/associated', 'externalSystemUserBonding','get'],
    //【日志】日志详情ById
    ['unified-message/log/{id}', 'getLogById','get'],
    //【日志】日志列表
    ['unified-message/log/get/list', 'getLogList','get'],

//    //【消息数据】删除消息(根据条件删除）
//    ['unified-message/message-data/delete', 'deleteMessageByWhere','post'],
//    //【消息数据】修改消息处理状态（已处理、已读）
//    ['unified-message/message-data/edit', 'editMessageState','post'],
    //【消息数据】获取消息ById
    ['unified-message/message-data/{messageId}', 'getMessageById','get'],
    //【消息数据】获取消息列表
    ['unified-message/message-data/get/list', 'getMessageList','get'],
    //【消息数据】门户获取个人消息列表
    ['unified-message/message-data/get/portal-message-list', 'portalData','get'],
    //下载api文件
    ['unified-message/get-api-config','getAPIConfig'],//下载附件配置
];
