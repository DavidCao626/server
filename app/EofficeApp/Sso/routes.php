<?php
$routeConfig = [
    // 系统设置单点登录列表
    ['sso/list', 'getSsoList', [402]],
    // 单点登录选择器
    ['sso/tree', 'getSsoTree'],
    //新建外部系统
    ['sso/add', 'addSso', 'post', [402]],
    //个性设置外部系统列表
    ['sso/my-login', 'getMySsoLoginList'],
    ['sso/my-login/{ssoId}', 'getMySsoLoginDetail'],

    ['sso/{ssoId}', 'editSso', 'put', [402]],
    ['sso/{ssoId}', 'deleteSso', 'delete', [402]],
    ['sso/{ssoId}', 'getOneSso', [402]],
    // ['sso/get-sso/{sso_id}', 'getSsoLogin'],
    //个性设置外部系统修改
    ['sso/login/{ssoLoginId}', 'editSsoLogin', 'put'],
    // ['sso/login/{user_id}', 'getSsoLoginList']
];
