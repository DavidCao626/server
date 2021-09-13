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
    ['sms/list', 'getSmsList'],
    ['sms/add', 'addSms', 'post'],
    ['sms/get-talk/{user1}/{user2}/{type}', 'getTalkList'], //$user1, $user2, $type
    ['sms/get-unread/{user_id}', 'getUnreadCountByReceive'],
    ['sms/group/{user_id}', 'getSmsGroup'],
    ['sms/delete/{user_id}', 'deleteAllSms', 'delete'],
    ['sms/cancel/{user_id}', 'cancelSms', 'put'],
    ['sms/{id}', 'deleteSms', 'delete'],
    ['sms/read/{id}', 'readSms', 'put'],
    ['sms/cacel', 'cacelNoticeSms', 'put'],
    ['sms/received', 'receivedSms'],
    ['sms/sent', 'sentSms'],
    ['sms/{sms_id}', 'getOneSms'],
    ['sms/send/{sms_id}', 'getSendSms'],
    ['sms/re-send/{sms_id}', 'reSendSms', 'post'],
];
