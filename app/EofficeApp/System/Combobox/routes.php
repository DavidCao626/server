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
    //行业下拉框，兼容手机版
    ['combobox/industry', 'getIndustry'],
    //获取系统下拉表所有字段
    ['combobox/combobox-all-fields/{id}', 'getAllFields'],
    //获取系统下拉表字段
    ['combobox/{id}/fields', 'getIndexComboboxFields'],
    //添加下拉表字段
    ['combobox/{id}/fields', 'createComboboxFields', 'post', [800, 168]], // 168为项目管理中的项目分类菜单权限；已知问题：会导致可通过http请求操作其它全部下拉字段，问题不大，后续看看项目类型是否单独剥离出去
    //编辑下拉表字段
    ['combobox/{id}/fields/{fieldId}', 'editComboboxFields', 'put', [800, 168]],
    //删除下拉表字段
    ['combobox/{id}/fields/{fieldId}', 'deleteComboboxFields', 'delete', [800, 168]],



    //获取下拉表标签（一级菜单）
    ['combobox/combobox-tags', 'getIndexComboboxTags'],
    //添加下拉表标签
    ['combobox/combobox-tags', 'createComboboxTags', 'post', [800]],
    //编辑下拉表标签
    ['combobox/combobox-tags/{id}', 'editComboboxTags', 'put', [800]],
    //删除下拉表标签
    ['combobox/combobox-tags/{id}', 'deleteComboboxTags', 'delete', [800]],




    //获取系统下拉列表
    ['combobox', 'getIndexCombobox'],
    //添加下拉项（二级菜单）
    ['combobox', 'createCombobox', 'post', [800]],
    ['combobox/getValue/{id}', 'getComboboxFieldsValueById', 'get'],
    //下拉选择
    ['combobox/field-select/{field}', 'getComboboxFieldData'],
  //行业下拉框，兼容手机版
    ['combobox/industry', 'getIndustry'],

    //获取下拉项详情
    ['combobox/{id}', 'getCombobox'],
    //编辑下拉表字段
    ['combobox/{id}', 'editCombobox', 'put', [800]],
    //删除下拉表
    ['combobox/{id}', 'deleteCombobox', 'delete', [800]],
    // 获取某个下拉的详情

];
