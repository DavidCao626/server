<?php

/**
 * 模块迁移至集成中心  接口权限集成父级菜单id
 */
$routeConfig = [
	//获取当前流程的签章
	['html-signature/h5/{documentId}', 'getHtmlSignatureList'],
	//新建签章
	['html-signature/h5/create/{documentId}/{signatureId}', 'createHtmlSignature', 'post'],
	//编辑签章
	['html-signature/h5/{documentId}/{signatureId}', 'editHtmlSignature', 'post'],
	//删除签章
	['html-signature/h5/{documentId}/{signatureId}', 'deleteHtmlSignature', 'delete'],
	// 金格签章，签章设置
	['html-signature/goldgrid-signature-set', 'goldgridSignatureSet', 'post', [395]],
	// 金格签章，获取签章设置
	['html-signature/goldgrid-signature-set', 'getGoldgridSignatureSet'],
	// 金格签章keysn，获取系统内签章keysn的list
	['html-signature/goldgrid-signature/keysn', 'getGoldgridSignatureKeysnList', [395]],
	// 金格签章keysn，新建签章keysn
	['html-signature/goldgrid-signature/keysn', 'createGoldgridSignatureKeysn', 'post', [395]],
	// 金格签章keysn，编辑签章keysn
	['html-signature/goldgrid-signature/keysn/{userId}', 'editGoldgridSignatureKeysn', 'post', [395]],
	// 金格签章keysn，删除签章keysn
	['html-signature/goldgrid-signature/keysn/{userId}', 'deleteGoldgridSignatureKeysn', 'delete', [395]],
	// 金格签章keysn，获取某用户的签章keysn
	['html-signature/goldgrid-signature/keysn/{userId}', 'getUserGoldgridSignatureKeysn'],

    // 获取当前签章插件配置
    ['html-signature/current-config', 'getCurrentService'],
	// 获取签章插件配置列表
	['html-signature/config', 'getSignatureConfig'],
    // 保存签章插件配置
	['html-signature/config', 'saveSignatureConfig', 'post'],
    // 获取签章插件契约锁配置
	['html-signature/qiyuesuo-config', 'getQiyuesuoSignatureConfig'],
    // 保存签章插件契约锁配置
	['html-signature/qiyuesuo-config/{settingId}', 'saveQiyuesuoSignatureConfig', 'post'],
    // 获取契约锁印章列表地址
	['html-signature/qiyuesuo/signature', 'serverSignatures', 'post'],
    // 添加签署信息记录
    ['html-signature/qiyuesuo/signature/log', 'saveSignatureLog', 'post'],
    // 编辑签署信息记录
    ['html-signature/qiyuesuo/signature/log/{logId}', 'updateSignatureLog', 'put'],
    // 删除签署信息记录
    ['html-signature/qiyuesuo/signature/log/{logId}', 'deleteSignatureLog', 'delete'],
    // 通过id获取签署信息记录
	['html-signature/qiyuesuo/signature/log/{logId}', 'getSignatureLog', 'get'],
	// 获取所有签署记录
    ['html-signature/qiyuesuo/signature/logs', 'getSignatureLogList', 'get'],
	// 对比保护信息	
	['html-signature/qiyuesuo/signature/detail', 'getCertDetail', 'post'],
    // 获取流程相关配置
	['html-signature/qiyuesuo/flow-config', 'getFlowConfig', 'get'],	
	['html-signature/qiyuesuo/flow-config/{configId}', 'getOneFlowConfig', 'get'],
	['html-signature/qiyuesuo/flow-config', 'saveFlowConfig', 'post'],
	['html-signature/qiyuesuo/flow-config/{configId}', 'updateFlowConfig', 'put'],

	['html-signature/qiyuesuo/flow/controls', 'getFlowControlFilterInfo'],
	// 会签验证数据保护
	['html-signature/qiyuesuo/flow/verify', 'countsignCheckVerify', 'post'],

];
