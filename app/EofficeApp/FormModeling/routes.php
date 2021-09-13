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
    ['form-modeling/modules/lists', 'getFormModuleLists'], //获取表单模块列表
    ['form-modeling/fields/{tableKey}', 'saveFormFields', 'post'], //保存表单字段
    ['form-modeling/fields/{tableKey}', 'listCustomFields'], //获取自定义字段
    ['form-modeling/bind-template', 'bindTemplate', 'post'], //绑定模板
    ['form-modeling/bind-template/{tableKey}', 'getBindTemplate'], //获取绑定模板 或者 获取某个模版信息
    ['form-modeling/template', 'saveTemplate', 'post'], //保存模板
    ['form-modeling/template/{id}', 'editTemplate', 'post'], //编辑模板
    ['form-modeling/template/{id}', 'deleteTemplate', 'delete'], //删除模板
    ['form-modeling/template/list/{tableKey}', 'getTemplateList'], //获取模板列表
    ['form-modeling/template/{id}', 'getTemplate'], //单个模板信息
    ['form-modeling/current/template/{tableKey}', 'getCurrentTemplate'], //获取当前模板
    ['form-modeling/app/get/{tableKey}', 'getSystemApp'], //获取当前模板
    ['form-modeling/app/quick/save/{tableKey}', 'quickSave','post'], //获取当前模板

    // ['form-modeling/template', 'getTemplateInfo'], //获取模板信息
    ['form-modeling/data/{tableKey}', 'getCustomDataLists'], //获取列表数据
    ['form-modeling/data/{tableKey}', 'addCustomData', 'post'], //新建自定义页面数据
    ['form-modeling/data/{tableKey}/{dataId}', 'getCustomDataDetail'], //获取自定义页面数据
    ['form-modeling/data/{tableKey}/{dataId}', 'editCustomData', 'post'], //编辑自定义页面数据
    ['form-modeling/data/{tableKey}/{dataId}', 'deleteCustomData', 'delete'], //删除自定义页面数据
    ['form-modeling/parse-custom-data', 'parseCustomData'], //解析数据
    ['form-modeling/permission/{tableKey}', 'savePermission', 'post'], //设置列表权限
    ['form-modeling/permission/{tableKey}', 'getCustomMenu'], //获取列表权限
    ['form-modeling/template/copy/{id}', 'copyTemplate'], //复制模板
    ['form-modeling/date/calculate', 'compareDate'], //比较日期
    ['form-modeling/check/unique', 'checkFieldsUnique'], //判定数据唯一
    ['form-modeling/export/material/{tableKey}', 'exportMaterial'], //导出素材
    ['form-modeling/import/material/{tableKey}', 'importMaterial', 'post'], //导入素材
];
