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
    // 获取全部查询条件
    ['front-component/web-search', 'getComponentSearchlist'],
    // 新增查询条件
    ['front-component/web-search/add', 'addComponentSearch', 'post'],
    // 更新查询条件
    ['front-component/web-search/{id}', 'editComponentSearch', 'put'],
    // 移除查询条件
    ['front-component/web-search/{id}', 'deleteComponentSearch', 'delete'],
    //添加系统数据
    ['front-component/customize-selector', 'addCustomizeSelector','post'],
    //编辑系统数据
    ['front-component/customize-selector/{id}', 'editCustomizeSelector','put'],
    //删除系统数据
    ['front-component/customize-selector/{id}', 'deleteCustomizeSelector','delete'],
    //获取系统数据
    ['front-component/customize-selector', 'getListCustomizeSelector'],
    //获取系统数据
    ['front-component/customize-selector/{identifier}', 'getOneCustomizeSelector'],
    //grid设置
    ['front-component/web-grid-set/{key}', 'getWebGridSet'],
    //grid设置
    ['front-component/web-grid-set', 'saveWebGridSet', 'post'],
];
