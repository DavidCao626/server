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

    //添加发件箱
    ['webmail/outboxs', 'createOutbox', 'post', [144]],
    //删除发件箱
    ['webmail/outboxs/{outboxId}', 'deleteOutbox', 'delete', [144]],
    //编辑发件箱
    ['webmail/outboxs/{outboxId}', 'updateOutbox', 'put', [144]],
    //获取发件箱列表
    ['webmail/outboxs', 'getOutboxs', [144,142]],
    //查看发件箱详情
    ['webmail/outboxs/{outboxId}', 'getOutbox', [144]],
    //获取邮件服务器
    ['webmail/servers', 'getWebmailServer', [142]],
    //连接邮件服务器
    ['webmail/connect', 'connectSmtp', [142]],
    //新建邮件
    ['webmail/mails', 'createMail', 'post', [142]],
    //删除邮件
    ['webmail/mails/{mailId}', 'deleteMail', 'delete', [142]],
    //编辑邮件
    ['webmail/mails/{mailId}', 'updateMail', 'put', [142]],
    //获取邮件列表
    ['webmail/mails', 'getMails', [142]],
    //查看邮件详情
    ['webmail/mails/{mailId}', 'getMail', [142]],
    //新建文件夹
    ['webmail/folders', 'createFolder', 'post' ,[142]],
    //删除文件夹
    ['webmail/folders/{folderId}', 'deleteFolder', 'delete', [142,144]],
    //编辑文件夹
    ['webmail/folders/{folderId}', 'updateFolder', 'put', [142,144]],
    //获取文件夹列表
    ['webmail/folders', 'getFolders', [142]],
    //查看文件夹详情
    ['webmail/folders/{folderId}', 'getFolder', [142]],
    //获取转移文件夹列表
    ['webmail/transfer/folder', 'getTransferFolder', [142]],
    //获取账号信息
    ['webmail/outboxs/{outboxId}/info', 'getAccountInfo', [142]],
    //收信
    ['webmail/outboxs/{outboxId}/receive', 'receiveMail', [142]],
    ['webmail/receivers', 'getReceivers', [142,147]],
    ['webmail/receive/rule', 'getReceiveRule', [147]],
    ['webmail/receive/rule', 'setReceiveRule', 'post', [147]],
    ['webmail/mail/account', 'getMailCount', [147]],



    //添加发件箱
//    ['webmail/outboxs', 'createOutbox', 'post'],
    //删除发件箱
//    ['webmail/outboxs/{id}', 'deleteOutbox', 'delete'],
    //编辑发件箱
//    ['webmail/outboxs/{id}', 'updateOutbox', 'put'],
    //获取发件箱列表
//    ['webmail/outboxs', 'getOutboxs'],
    //查看发件箱详情
//    ['webmail/outboxs/{id}', 'getOutbox'],
    //获取邮件服务器
//    ['webmail/webmail-servers', 'getWebmailServer'],
    //连接邮件服务器
//    ['webmail/webmail-connect', 'connectSmtp'],
    //新建邮件
//    ['webmail/mails', 'createMail', 'post'],
    //删除邮件
//    ['webmail/mails/{mail_id}', 'deleteMail', 'delete'],
    //编辑邮件
//    ['webmail/mails/{mail_id}', 'updateMail', 'put'],
    //获取邮件列表
//    ['webmail/mails', 'getMails'],
    //查看邮件详情
//    ['webmail/mails/{mail_id}', 'getMail'],
    //新建文件夹
//    ['webmail/folders', 'createFolder', 'post'],
    //删除文件夹
//    ['webmail/folders/{id}', 'deleteFolder', 'delete'],
    //编辑文件夹
//    ['webmail/folders/{id}', 'updateFolder', 'put'],
    //获取文件夹列表
//    ['webmail/folders', 'getFolders'],
    //查看文件夹详情
//    ['webmail/folders/{id}', 'getFolder'],
    //获取转移文件夹列表
//    ['webmail/transfer-folder', 'getTransferFolder'],
    //获取账号信息
//    ['webmail/outboxs/{id}/info', 'getAccountInfo'],
    //收信
//    ['webmail/outboxs/{id}/receive', 'receiveMail'],
    //获取收件人
//    ['webmail/receivers', 'getReceivers'],
//    ['webmail/receive-rule', 'getReceiveRule'],
//    ['webmail/receive-rule', 'setReceiveRule', 'post'],
    ['webmail/mail-account', 'getMailCount'],

    // 新建标签
    ['webmail/tag', 'createTag', 'post' ,[142]],
    // 删除标签
    ['webmail/tag/{tagId}', 'deleteTag', 'delete', [142,144]],
    // 编辑标签
    ['webmail/tag/{tagId}', 'updateTag', 'put', [142,144]],
    // 获取标签列表
    ['webmail/tags', 'getTags', [142]],
    // 查看标签详情
    ['webmail/tag/{tagId}', 'getTag', [142]],
    // 给邮件添加标签
    ['webmail/set/tag', 'setTag', 'put', [142,144]],
    // 给邮件取消标签
    ['webmail/cancel/tag', 'cancelTag', 'put', [142,144]],
    // 保存发件配置
    ['webmail/send/rule', 'setSendRules', 'post', [142,144]],
    // 获取发件配置
    ['webmail/send/rule', 'getSendRules', [142,144]],
    //添加发件箱
    ['webmail/outbox/check', 'checkOutbox', 'post', [144]],
    // 收发日志
    ['webmail/log', 'getLogs', [144]],
    // 邮件客户联系记录
    ['webmail/customer/record', 'getRecords', [144]],

    // 共享组列表
    ['webmail/share-sets', 'getShareSets', [144]],
    ['webmail/share-set/{configId}', 'getShareSet', [144]],
    // 添加共享组
    ['webmail/share-set', 'addShareConfig', 'post', [144]],
    // 编辑共享组
    ['webmail/share-set/{configId}', 'editShareConfig', 'put', [144]],
    // 删除共享组
    ['webmail/share-set/{configId}', 'deleteShareConfig', 'delete', [144]],
    /**
     * 邮箱文件夹相关 2021-06-24 imap同步
     */
    // 获取所有邮箱文件夹
    ['webmail/email-folders', 'webmailFolders'],
    // 获取某个邮箱的文件夹列表
    ['webmail/email-folders/{outboxId}', 'updateWebmailFolders', 'put'],
    // 新增邮箱文件夹
    ['webmail/email-folder', 'addWebmailFolder', 'post'],
    // 编辑邮箱文件夹
    ['webmail/email-folder', 'editWebmailFolder', 'put'],
    // 删除邮箱文件夹
    ['webmail/email-folder/{outboxId}/{folderId}', 'deleteWebmailFolder', 'delete'],
    /**
     * 获取单个邮箱文件夹及邮件个数
     */
    ['webmail/outbox/detail/{outboxId}', 'getOneOutboxFolderAndMailCount']

];