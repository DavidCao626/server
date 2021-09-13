<?php
$routeConfig = [
    //添加外部数据库
    ['external-database', 'createExternalDatabase', 'post'],
    //删除外部数据库
    ['external-database/{databaseId}', 'deleteExternalDatabase', 'delete'],
    //编辑外部数据库
    ['external-database/{databaseId}', 'editExternalDatabase', 'put'],
    //查看外部数据库详情
    ['external-database/{databaseId}', 'getExternalDatabase', [282]],// 20201224-增加菜单权限
    //获取外部数据库列表
    ['external-database', 'getExternalDatabases'],// 集成管理、表单用到 不控制菜单
    //测试数据库连接
    ['external-database/test', 'testExternalDatabases', 'post'],//TODO 集成管理、cas、流程管理、导入等多处用到 暂不控制菜单
    //获取外部数据库表
    ['external-database/get/table', 'getExternalDatabasesTables'],//TODO 集成管理、表单数据源、自定义选择器等多处用到 暂不控制菜单
    //获取外部数据库表字段
    ['external-database/get/table-field-list', 'getExternalDatabasesTableFieldList'],//TODO 集成管理、表单数据源、自定义选择器等多处用到 暂不控制菜单
    //获取外部系统表数据
    ['external-database/get/table-data', 'getExternalDatabasesTableData'],//TODO 集成管理、表单数据源、自定义选择器等多处用到 暂不控制菜单
    //门户获取外部数据库配置
    ['external-database/get/portal-config', 'getPortalConfig'],// TODO 用不到
    //门户保存外部数据库配置
    ['external-database/save/portal-config', 'savePortalConfig', 'post'],// TODO 门户用到 暂不控制菜单
    //删除标签
    ['external-database/delete/portal-tab', 'deletePortaltab'],// TODO 用不到
    //通过sql获取数据
    ['external-database/get/sql-data', 'getExternalDatabasesDataBySql'],//TODO 集成管理、表单数据源、自定义选择器等多处用到 暂不控制菜单
    //验证sql
    ['external-database/get/sql-test', 'externalDatabaseTestSql'],//TODO 表单数据源用到，暂不控制菜单
];
