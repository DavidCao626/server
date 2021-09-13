<?php
$routeConfig = [
    //获取公共模板列表
    ['common-template', 'getIndexCommonTemplate'],
    //新建公共模板
    ['common-template', 'createCommonTemplate', 'post', [259]],
    //查询公共模板详情
    ['common-template/{templateId}', 'getCommonTemplate'],
    //编辑公共模板
    ['common-template/{templateId}', 'editCommonTemplate', 'put', [259]],
    //删除公共模板
    ['common-template/{templateId}', 'deleteCommonTemplate', 'delete', [259]],
    // 导入内容模板
    ['common-template/import', 'importContentTemplate', 'post'],
    // 导出内容模板
    ['common-template/export/{id}', 'exportContentTemplate']
];
