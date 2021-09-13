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
    // 获取服务状态
    ['server-manage/status', 'getServerStatus'],
    // 立即更新
    ['server-manage/update/now', 'startUpdateNow'],
    // 稍后更新
    ['server-manage/update/later', 'startUpdateLater'],
    // 取消更新
    ['server-manage/update/cancel', 'cancelUpdate'],
    // 获取新版本信息
    ['server-manage/version', 'getNewVersionInfo'],
    // 设置更新时间
    ['server-manage/update-time', 'setUpdateTime', 'post']
];
