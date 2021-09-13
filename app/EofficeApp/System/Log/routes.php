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
    //获取系统日志列表
    ['log', 'getLogList', [103]],
    //获取日志类别列表
    ['log/types', 'getLogTypeList', [103]],
    //删除系统日志
    ['log/delete', 'deleteLog', 'post', [103]],
    //统计系统日志
    ['log/statistics', 'getLogStatistics', [103]],
    //获取系统访问人数
    ['log/visitors', 'getSystemVisitors'],
	  //页面显示api地址
    ['log/addressurl', 'getAddressFromIpUrl', [103]],
];