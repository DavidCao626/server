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

    ['dingtalk/check-dingtalk', 'checkDingtalk', 'post', [120]],
    ['dingtalk/save-dingtalk', 'saveDingtalk', 'post', [120]],
    ['dingtalk/truncate-dingtalk', 'truncateDingtalk', 'delete', [120]],
    ['dingtalk/get-dingtalk', 'getDingtalk', [120]],
    ['dingtalk/dingtalk-signPackage', 'dingtalkSignPackage'],
    ['dingtalk/dingtalk-userList', 'getDingtalkUserList', [120]],
    ['dingtalk/dingtalk-user/{userId}', 'deleteBindByOaId', 'delete', [120]],
    ['dingtalk/dingtalk-export', "dingtalkExport",[120]],
    ['dingtalk/dingtalk-sync', "dingtalkSync",[120]],
    ['dingtalk/dingtalk-logs', "dingtalkLogs",[120]],
    ['dingtalk/dingtalk-syncTime', "saveSyncTime",'post',[120]],
    ['dingtalk/dingtalk-deleteLogs/{id}', 'deleteDingtalkLog',[120]],
    // 新增钉钉组织架构同步排除接口
    // ['dingtalk/organization-sync/exception', 'addException', 'post'],
    // 获取钉钉部门列表接口
    ['dingtalk/getDepartmentList/{parentId}', 'getDingtalkDepartmentList'],
    // 获取钉钉角色列表接口
    ['dingtalk/getRoleList', 'getRoleList'],
    // 钉钉与OA部门同步
    ['dingtalk/addDingtalkDepartmentRelation', 'addDingtalkDepartmentRelation', 'post'],
    // 钉钉与OA角色同步
    // ['dingtalk/addDingtalkRoleRelation', 'addDingtalkRoleRelation', 'post'],
    // 组织架构同步实际功能函数接口
    ['dingtalk/dingtalkOASync', 'dingtalkOASync'],
    // 钉钉事件注册函数
    // ['dingtalk/registerCallback', 'registerCallback'],
    // 钉钉事件回调加解密接收函数
    // ['dingtalk/dingtalkReceive', 'dingtalkCallbackReceive', 'post'],
    // 钉钉获取日志列表函数
    ['dingtalk/getDingtalkSyncLogList', 'getDingtalkSyncLogList'],
    // 钉钉获取日志详情函数
    ['dingtalk/getDingtalkSyncLogdetail/{id}', 'getDingtalkSyncLogdetail'],
    // 钉钉组织架构同步队列接口
    ['dingtalk/organizationSync', 'organizationSync'],
    // 钉钉组织架构配置保存接口
    ['dingtalk/saveDingtalkOASyncConfig', 'saveDingtalkOASyncConfig', 'post'],
    // 钉钉组织架构配置获取接口
    ['dingtalk/getDingtalkOASyncConfig', 'getDingtalkOASyncConfig'],
    // 钉钉组织架构同步测试接口
    ['dingtalk/test', 'test'],
];
