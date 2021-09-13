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
    // 【组织架构同步】 获取用户中间表字段列表
    ['cas/get-user-assoc-fields-list', 'getUserAssocFieldsList', [350]],
    // 【组织架构同步】 获取部门中间表字段列表
    ['cas/get-dept-assoc-fields-list', 'getDepartmentAssocFieldsList', [350]],
    // 【组织架构同步】 获取人事档案中间表字段列表
    ['cas/get-personnel-file-assoc-fields-list', 'getPersonnelFileAssocFieldsList', [350]],
    // 【组织架构同步】 保存cas认证配置参数
    ['cas/save-cas-params', 'saveCasParams', 'post', [350]],
    // 【组织架构同步】 获取cas认证配置参数
    ['cas/get-cas-params', 'getCasParams', [350]],
    // 【组织架构同步】 同步组织架构数据
    ['cas/sync-organization-data', 'syncOrganizationData', [350]],
    // 【组织架构同步】 获取同步日志
    ['cas/get-cas-sync-log-list', 'getCasSyncLog', [350]],
];
