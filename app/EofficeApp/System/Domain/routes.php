<?php
$routeConfig = [
	/*
	*基本设置 391
    *同步日志 392
    *模块迁移至集成中心  接口权限集成父级菜单id
	*/
	// 获取LDAP配置信息
    ['domain', 'getDomainInfo', [390]],
    // 设置LDAP信息
    ['domain', 'saveDomain', 'post', [390]],
    // 测试连接
    ['domain/test', 'testDomainConnect', [390]],
    // 同步
    ['domain/sync', 'syncDomain', 'post',[390]],
    // 日志列表
    ['domain/log', 'getSyncLogs', [390]],
    // 日志详情用户列表
    ['domain/log/{logId}', 'getSyncLogDetail', [390]],
    // 删除日志
    ['domain/log/{logId}', 'deleteSyncRecord', 'delete', [390]],
    // 同步日志详情数量统计
    ['domain/sync-result/{logId}', 'getSyncResult', [390]],
    // 获取LDAP自动同步配置信息
    ['domain/sync/config', 'getDomainSyncConfig', [390]],
    // 设置LDAP自动同步信息
    ['domain/sync/config', 'setDomainSyncConfig', 'post', [390]],
];