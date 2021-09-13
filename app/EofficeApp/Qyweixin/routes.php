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
        ['check-wechat', 'checkWechat', 'post'],
        ['save-wechat', 'saveWechat', 'post'],
        ['truncate-wechat', 'truncateWechat', 'delete'],
        ['userlist-wechat', 'userListWechat', 'get'],
        ['user-shows',"userListShow"],
        ['userstatus-wechat',"getUserWechat"],
        ['sync-organization-wechat','syncOrganization'],//同步组织架构
        ['one-key-wechat','oneKey'],//一键生成APP
        ['check-wechat/flag',"qywechatCheck"],
        ['get-wechat-app',"getWechatApp"],
        ['qywechat-applist-update',"updateAppList","POST"],
        ['get-enterprise-account','getEnterpriseAccount'],//下载附件配置
        ['update-agentid','updateAgentId'],
        ['get-sync-organization-detail','getSyncOrganizationFile'],//下载导入结果
        ['platform-user','getPlatformUser'],//钉钉 企业号接入 
];
