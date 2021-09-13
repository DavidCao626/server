<?php
$routeConfig = [
	['system-mailbox', 'getSystemMailboxList', [403]],//获取系统邮箱列表
	['system-mailbox', 'createSystemMailbox', 'post', [403]],//新建系统邮箱
	['system-mailbox/{emailboxId}', 'modifySystemMailbox', 'post', [403]],//编辑系统邮箱
	['system-mailbox/{emailboxId}', 'deleteSystemMailbox', 'delete', [403]],//删除系统邮箱
	['system-mailbox/{emailboxId}', 'getSystemMailboxDetail', [403]],//获取系统邮箱详情
	//['system-mailbox/default', 'getDefaultSystemMailboxDetail'],//获取默认系统邮箱
	['system-mailbox/{emailboxId}/set-as-default-mailbox', 'setAsDefaultMailbox', 'post', [403]],//设置默认系统邮箱
	['system-mailbox/{emailboxId}/cancel-as-default-mailbox', 'cancelAsDefaultMailbox', 'post', [403]],//取消默认系统邮箱
];
