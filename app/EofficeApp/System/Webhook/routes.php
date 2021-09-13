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
    //获取所有菜单
    ['webhook/menu', 'getMenus'],
    //设置webhook
    ['webhook', 'setWebhook', 'post'],
    //获取webhook
    ['webhook/{webhookMenu}', 'getWebhook'],
    //测试webhook
    ['webhook/test', 'testWebhook', 'post'],// TODO 集成管理、流程数据外发、流程数据验证用到 暂不控制
];
