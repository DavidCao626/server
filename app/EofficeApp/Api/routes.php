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
    //获取api返回值
    ['api/apis', 'getApis'],
    //测试sql
    ['api/test-sql', 'testSql', 'post'],
    //获取sql字段信息
    ['api/sql/fields', 'getSqlFields', 'post'],
    //测试url是否可以访问
    ['api/url/test', 'testUrl', 'post'],
    //测试url返回值
    ['api/url/data', 'getUrlData', 'post']
];