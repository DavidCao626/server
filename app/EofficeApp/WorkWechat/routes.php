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
  | 模块迁移至集成中心  接口权限集成父级菜单id
 */

$routeConfig = [
        ['work-wechat/workwechat-save', 'saveWorkWechat', 'post', [913]],
        ['work-wechat/wechatapp-save', 'wechatAppSave', 'post', [913]],
        ['work-wechat/wechatapp-get', 'wechatAppGet', [913]],
        ['work-wechat/wechatapp-delete/{id}',"wechatAppDelete", [913]],
        ['work-wechat/get-enterprise-wechat','getEnterpriseWechat', [913]],//下载附件配置
        ['work-wechat/workwechat-truncate','workwechatTruncate','delete', [913]],
        ['work-wechat/workwechat-sync', 'sync', [913]], //同步组织架构
        ['work-wechat/phoneNumAssociation', 'phoneNumberAssociation', [913]], //手机号关联
        ['work-wechat/getSyncLogList', 'getSyncLogList','get', [913]], //获取同步组织架构日志
        ['work-wechat/save-workwechat-sync', 'saveWorkWeChatSync','post', [913]], //保存同步组织架构
        ['work-wechat/sync-data-backup', 'syncDataBackup','get', [913]], //备份数据的组织架构
        ['work-wechat/sync-data-reduction', 'syncDataReduction','get', [913]], //还原组织架构
        ['work-wechat/get-workwechat-group-chat-list-detail', 'getWorkWeChatGroupChatListDetail','post'], // 获取企业微信当前用户群主所有客户群详细
        ['work-wechat/get-remind-menu', 'getRemindMenu','get'], //获取消息提醒菜单
        ['work-wechat/save-remind-menu', 'saveRemindMenu','post'], //保存消息提醒菜单
        ['work-wechat/save-wechat-app-push', 'saveWechatAppPush','post'], //保存是否开启消息提醒
        ['work-wechat/get-attendance-sync-set', 'getAttendanceSyncSet','get'], //获取企业微信同步考勤设置
        ['work-wechat/sync-attendance', 'syncAttendance','post'], //同步考勤数据
        ['work-wechat/sync-attendance-log', 'getSyncAttendanceLog','get'], //同步考勤日志列表
        ['work-wechat/down-attendance-log', 'downSyncAttendanceLog','get'], //下载同步考勤日志
        ['work-wechat/check-sync-attendance-log', 'checkSyncAttendanceLog','post'], //检查同步考勤日志是否存在
];
