<?php
$routeConfig = [
    //获取印章列表
    ['signature', 'getIndexSignature'],
    //新建印章
    ['signature', 'createSignature', 'post'],
    //是否为管理员--废弃的路由
    // ['signature/is-admin', 'isAdmin', [112]],
    //查询印章详情
    ['signature/{signatureId}', 'getSignature'],
    //编辑印章
    ['signature/{signatureId}', 'editSignature', 'put'],
    //删除印章
    ['signature/{signatureId}', 'deleteSignature', 'delete'],


];
