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
    //  获取角色列表数据(角色选择器)
    ['role', 'getIndexRoles'],
    // 客户管理调用(获取有权限的角色列表)
    ['role/list', 'getRolesList'],
    //保存角色数据
    ['role', 'createRoles', 'post', [99]],
    //保存人员角色(没用到)
    ['role/user-role', 'createUserRole', 'post'],
    //获取人员的角色(没用到)
    ['role/user-role/{userId}', 'getUserRole'],
    //删除角色(没用到)
    ['role/user-role/{userId}', 'deleteUserRole', 'delete'],
    //获取角色通信列表
    ['role/communicate', 'getIndexRoleCommunicate', [106, 99]],
    //添加角色通信
    ['role/communicate', 'createRoleCommunicate', 'post', [106, 99]],
    //角色通信字段控制
    ['role/communicate/control-fields', 'roleControlFields', [106, 99]],
    //获取角色通详情
    ['role/communicate/{id}', 'getRoleCommunicate', [106, 99]],
    //编辑角色通信列表
    ['role/communicate/{id}', 'editRoleCommunicate', 'put', [106, 99]],
    //删除角色
    ['role/communicate/{id}', 'deleteRoleCommunicate', 'delete', [106, 99]],
    //获取用户上下级列表(没用到)
    ['role/user-superior', 'getIndexUserSuperior'],
    //保存用户上下级(没用到)
    ['role/user-superior', 'createUserSuperior', 'post'],
    //获取用户所有下级(日程微博获取默认下级用到)
    ['role/user-superior/all/{userId}', 'getAllUserSuperior'],
    //获取用户上下级
    ['role/user-superior/{userId}', 'getUserSuperior'],
    //获取所有权限级别(流程设置用到)
    ['role/user-role-level', 'getRoleLevel'],
    //获取某个部门下所有角色(流程设置用到)
    ['role/user-dept-role', 'getDeptRole'],
    // 内部邮件获取可通讯的角色
    ['role/communicateRoles', 'communicateRoles','get'],
    // 没找到
    ['role/get-max-role-id/{data}', 'getMaxRoleNoFromData','get'],
    // 获取角色
    //获取角色详情
    ['role/{roleId}', 'getRoles', [99]],
    //编辑角色数据
    ['role/{roleId}', 'editRoles', 'put', [99]],
    //删除角色
    ['role/{roleId}', 'deleteRoles', 'delete', [99]],
];
