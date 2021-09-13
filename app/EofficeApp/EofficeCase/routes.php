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
  // 导入案例
  ['eoffice-case/import', 'importEofficeCase', 'post'],
  // 删除案例
  ['eoffice-case', 'deleteEofficeCase', 'delete'],
  // 导出案例
  ['eoffice-case/export', 'exportEofficeCase', 'post'],
  // 开启即时通讯
  ['eoffice-case/open-im', 'openIMServer', 'post'],
];
