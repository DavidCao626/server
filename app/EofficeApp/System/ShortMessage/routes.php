<?php
$routeConfig = [
    //获取webvice方法对应的相关参数
    ['short-message/functionparams', 'getSMSFunctionParams', 'get', [257]],
    //获取手机短信系统配置
    ['short-message/getconfig', 'getSmsConfig','get', [257]],
    //编辑手机短信系统配置
    ['short-message/editconfig', 'editSmsConfig','post', [257]],
    //删除手机短信配置
    ['short-message/delete', 'smsSetDelete','get', [257]],
    //添加手机短信配置
    ['short-message/add', 'addSms','post', [257]],
    //获取webservice所有方法
    ['short-message/function','getWebServiceFunction', [257]],
    //获取手机短信配置列表
    ['short-message/list', 'getSMSSetsList', [257]],
    //获取短信发送种类
    ['short-message/type', 'getSMSType', [257]],
    //获取手机短信服务配置
    ['short-message/set', 'getSMSSetList', [257,255]],
    //修改手机短信服务配置
    ['short-message/set/{smsId}', 'editSMSSet', 'put', [257]],
    //发送手机短信
    ['short-message/send', 'sendSMS', 'post', [255]],
    //获取我的手机短信
    ['short-message/mine', 'getMineSMSList',[258]],
    //获取手机短信
    ['short-message/manage', 'getSMSList', [256]],
    //获取手机短信
    ['short-message/users', 'getCommunicateUsers', [255]],
    //删除手机短信
    ['short-message/{id}', 'deleteSMS', 'delete', [256]],
    //手机短信详情
    ['short-message/{id}', 'getSMS', [256,258]],
];