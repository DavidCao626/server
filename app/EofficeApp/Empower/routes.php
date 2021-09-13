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
    //获取模块授权信息
    ['empower/module-empower', 'getModuleEmpower'],
    //增加模块授权信息
    ['empower/module-empower', 'addModuleEmpower', 'post'],
    //检查手机授权和是否允许手机访问
    ['empower/check-mobile-empower-and-wap-allow/{userId}', 'checkMobileEmpowerAndWapAllow'],
    ['empower/info/{userId}', 'getEmpowerInfo'],
];