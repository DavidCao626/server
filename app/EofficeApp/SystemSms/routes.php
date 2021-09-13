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
    ['system-sms/add', 'addSystemSms', 'post'],
    ['system-sms/set-read', 'moduleToReadSms', 'post'],
    ['system-sms', 'mySystemSms'],
    ['system-sms/sign/read', 'signSystemSmsRead'],
    ['system-sms/unread', 'getSystemSmsUnread'],
    ['system-sms/get-unread-last','getLastTotal'],
    ['system-sms/get-new-system-sms','getNewDetailByGroupBySmsType'],
    ['system-sms/read/{sms_id}','setSmsRead'],
    ['system-sms/{id}', 'viewSystemSms'],

];
