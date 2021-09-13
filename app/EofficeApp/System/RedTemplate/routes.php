<?php
$routeConfig = [
    //获取套红模板列表
    ['red-template', 'getRedTemplateList'],
    //获取有权限的套红模板列表
    ['red-template/mine', 'getMyRedTemplateList'],
    //新建套红模板
    ['red-template', 'createRedTemplate', 'post'],
    //查询套红模板详情
    ['red-template/{templateId}', 'getRedTemplate'],
    //编辑套红模板
    ['red-template/{templateId}', 'editRedTemplate', 'put'],
    //删除套红模板
    ['red-template/{templateId}', 'deleteRedTemplate', 'delete']
];
