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
//24 我的邮件
//16 新建邮件
//29 查询邮件
//31 我的文件夹
$routeConfig = [
    ['email/box/add', 'addEmailBox', 'post',[31]],
    ['email/box/{boxId}', 'editEmailBox', 'put',[31]],
    ['email/box/{boxId}', 'deleteEmailBox', 'delete',[31]],
    ['email/box/my/{boxId}', 'getOneBox',[24]],
    ['email/box', 'getEmailBoxList',[31]],
    ['email/box/list', 'getEmailBoxListAll',[24]],
    ['email/user', 'useEmailSign',[16]],    // 目前没找到页面使用
    ['email/my/{emailId}', 'getEmailInfo',[29,24]],
    ['email/from/{emailId}', 'getEmailData',[24,29]],
    ['email/new', 'newEmail', 'post',[16]],
    ['email/{emailId}', 'editEmail', 'put',[24]],
    ['email/{emailId}', 'deleteEmail', 'delete',[29,16]],
    ['email/list', 'getEmail',[24]],
    ['email/transfer', 'transferEmail', 'post',[24]],
//    ['email/inEmail', 'getEmailReceiveNum',[16]],       // 目前没找到页面使用
//    ['email/outEmail', 'getOutEmailNum',[24]],
//    ['email/tempEmail', 'getTempEmailNum',[24]],
    ['email/set/read/{emailId}', 'readEmail', 'put',[24]],
    ['email/read-statistics/{emailId}', 'readStatistics', [24]],
    ['email/lists', 'emailLists',[29,24]],
    ['email/types/{type}', 'emailTypes',[24,29]],
    ['email/my/email/type', 'getMyEmail',[24]],
//    ['email/export/{emailId}', 'exportEmail',[29,24]],
//    ['email/down/zip', 'downZipEmail',[29,24]],
    ['email/recycle/{emailId}', 'emailRecycle', "put",[24]], //撤回
    ['email/truncate/email', "truncateEmail", "delete",[24]], //清空（回收站）
    ['email/recycle/delete/email', "recycleDeleteEmail", "put",[24]], //撤销删除
    ['email/system/delete/email', 'systemEmailDelete', "delete",[29,24]],
    ['email/get/num/{boxId}', 'getEmailNums', "get",[24]],
    ['email/get/email', 'getEmailId',[29,24]],
    ['email/eml/download/{emailId}', 'downloadEml',[29,24]],
    ['email/star/toggle', 'toggleStar', 'put', [24]], // 星标
    ['email/receives/{emailId}', 'emailReceiveList', 'get', [24]] // 获取收件人数据

//    ['email-box/add', 'addEmailBox', 'post'],
//    ['email-box/{box_id}', 'editEmailBox', 'put'],
//    ['email-box/{box_id}', 'deleteEmailBox', 'delete'],
//    ['email-box/my/{box_id}', 'getOneBox'],
//    ['email-box', 'getEmailBoxList'],
//    ['email-box-list', 'getEmailBoxListAll'],
//    ['email/user', 'useEmailSign'],
//    ['email/my/{email_id}', 'getEmailInfo'],
//    ['email/from/{email_id}', 'getEmailData'],
//    ['email/new', 'newEmail', 'post'],
//    ['email/{email_id}', 'editEmail', 'put'],
//    ['email/{email_id}', 'deleteEmail', 'delete'],
//    ['email/list', 'getEmail',[11]],
//    ['email/transfer', 'transferEmail', 'post'],
//    ['email/inEmail', 'getEmailReceiveNum'],
//    ['email/outEmail', 'getOutEmailNum'],
//    ['email/tempEmail', 'getTempEmailNum'],
//    ['email/set-read/{email_id}', 'readEmail', 'put'],
//    ['email/email-lists', 'emailLists'],
//    ['email/email-types/{type}', 'emailTypes'],
//    ['email/my-email/type', 'getMyEmail'],
//    ['email-export/{email_id}', 'exportEmail'],
//    ['email/down-zip', 'downZipEmail'],
//    ['email-recycle/{email_id}', 'emailRecycle', "put"], //撤回
//    ['truncate-email', "truncateEmail", "delete"], //清空（回收站）
//    ['recycle-delete-email', "recycleDeleteEmail", "put"], //撤销删除
//    ['system-delete-email', 'systemEmailDelete', "delete"],
//    ['email/get-num/{box_id}', 'getEmailNums', "get"],
//    ['email/get-email', 'getEmailId'],
//    ['email/eml/download/{email_id}', 'downloadEml']
];
