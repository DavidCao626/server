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
    // 获取模块列表
    ['flow-modeling/get-flow-module-list', 'getFlowModuleList', [205]],
    // 获取模块树(提供给模块选择器)
    ['flow-modeling/tree/{moduleParent}', 'getFlowModuleTree'],
    // 查询模块树(提供给模块选择器)
    ['flow-modeling/tree-selector-search', 'searchFlowModuleTreeForSelector'],
    // 获取模块的设置详情
    ['flow-modeling/get-flow-module-info/{moduleId}', 'getFLowModuleInfoByModuleId', [205]],
    // 添加模块
    ['flow-modeling/add-flow-module', 'addFlowModule', 'POST', [205]],
    // 编辑模块
    ['flow-modeling/edit-flow-module/{moduleId}', 'editFlowModule', 'POST', [205]],
    // 删除模块
    ['flow-modeling/delete-flow-module/{moduleId}', 'deleteFlowModule', 'DELETE', [205]],
];
