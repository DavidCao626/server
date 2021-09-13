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
    // 高拍仪集成列表
    ['high-meter-integration/setting', 'getList', 'get'],
    // 根据配置id获取高拍仪集成
    ['high-meter-integration/setting/config/{settingId}', 'getConfig', 'get'],
    // 设置修改
    ['high-meter-integration/setting/{settingId}', 'editSetting', 'post'],
    // 是否配置高拍仪
    ['high-meter-integration/setting/check', 'checkOpen'],
    // 获取设置连接地址
    ['high-meter-integration/base-url', 'getBaseUrl'],
    // 保存个人设置的连接地址
    ['high-meter-integration/base-url/private', 'savePrivateBaseUrl', 'post'],
];
