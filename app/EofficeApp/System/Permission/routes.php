<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| 系统权限设置
|
 */

$routeConfig = [

    // 权限分类
    ['permission/type', 'getPermissionType'],
    // 新建权限分类
    ['permission/type', 'addPermissionType', 'post'],
    // 编辑分类
    ['permission/type/{typeId}', 'editPermissionType', 'put'],
    // 删除分类
    ['permission/type/{typeId}', 'deletePermissionType', 'delete'],
    // 分类详情
    ['permission/type/{typeId}', 'getPermissionTypeDetail'],
    // 获取权限组列表
    ['permission/group/list', 'getPermissionGroups'],
    // 新建权限组
    ['permission/group', 'addPermissionGroup', 'post'],
    // 删除权限组
    ['permission/group/{groupId}', 'deletePermissionGroup', 'delete'],
    // 权限组详情
    ['permission/group/{groupId}', 'getGroupDetail'],
    // 编辑权限组
    ['permission/group/{groupId}', 'editPermissionGroup', 'put']
];
