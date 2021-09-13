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
    // 【契约锁服务设置】 列表
    ['electronic-sign/servers', 'getServerList'],
    // 【契约锁服务设置】 新建服务
    ['electronic-sign/server', 'addServer', 'post'],
    // 【契约锁服务设置】 详情
    ['electronic-sign/server/{serverId}', 'getServerDetail'],
    // 【契约锁服务设置】 编辑
    ['electronic-sign/server/{serverId}', 'editServer', 'put'],
    // 【契约锁服务设置】 删除
    ['electronic-sign/server/{serverId}', 'deleteServer', 'delete'],
    // 【契约锁服务设置】 获取开关&加密串
    ['electronic-sign/server-base-config', 'getServerBaseInfo'],
    // 【契约锁服务设置】 保存开关&加密串
    ['electronic-sign/server-base-config', 'editServerBaseInfo', 'put'],

    // 【契约锁集成设置】 列表
    ['electronic-sign/integrations', 'getIntegrationList'],
    // 【契约锁集成设置】 新建集成
    ['electronic-sign/integration', 'addIntegration', 'post'],
    // 【契约锁集成设置】 详情
    ['electronic-sign/integration/{settingId}', 'getIntegrationDetail'],
    // 【契约锁集成设置】 通过定义流程ID获取详情
    ['electronic-sign/integration/flow-id/{flowId}', 'getIntegrationDetailByFlowId'],
    // 【契约锁集成设置】 编辑
    ['electronic-sign/integration/{settingId}', 'editIntegration', 'put'],
    // 【契约锁集成设置】 删除
    ['electronic-sign/integration/{settingId}', 'deleteIntegration', 'delete'],
    // 【契约锁集成功能】 根据流程id获取关联的合同id
    ['electronic-sign/flow-run-relation-contract/{runId}', 'getContractIdbyFlowRunId'],
    // 【契约锁集成功能】 获取签署合同的url，传合同号，流程id，返回完整的url
    ['electronic-sign/get-contract-sign-url/{contractId}', 'getContractSignUrl', 'post'],
    //合同创建v1
    ['electronic-sign/create-contract/v1', 'createContractV1', 'post'],
    //【流程办理页面调用，契约锁签署标签，获取跟契约锁关联的信息】
    ['electronic-sign/flow-relation/qys-sign-info', 'getFlowRelationQysSignInfo', 'post'],
    //【获取合同的各种地址【预签署、详情、下载、打印】】
    ['electronic-sign/contract-url/{contractId}', 'getContractUrl', 'post'],

    //【工作流契约锁物理用印授权日志】
    ['electronic-sign/seal-apply/auth/logs','getSealApplyAuthLogsList','get'],
    //【工作流契约锁物理用印授权文档创建日志】
    ['electronic-sign/seal-apply/create-doc/logs','getSealApplyCreateDocLogsList','get'],
    //【工作流契约锁物理用印日志】
    ['electronic-sign/seal-apply/logs','getSealApplyLogsList','get'],
    //【工作流契约锁物理用印集成设置】列表
    ['electronic-sign/seal-apply/list','getSealApplyList','get'],
    //【工作流契约锁物理用印集成设置】新建
    ['electronic-sign/seal-apply','addSealApply','post'],
    //【工作流契约锁物理用印集成设置】删除
    ['electronic-sign/seal-apply/{id}','deleteSealApply','delete'],
    //【工作流契约锁物理用印集成设置】编辑
    ['electronic-sign/seal-apply/{id}','editSealApply','put'],
    //【工作流契约锁物理用印集成设置】查看
    ['electronic-sign/seal-apply/{id}','getSealApply','get'],
    //【工作流契约锁物理用印列表同步】
    ['electronic-sign/seals', 'syncSeals','get'],
    //【契约锁相关资源设置】列表
    ['electronic-sign/related-resource', 'qysRelatedResourcePlan'],
    //【契约锁相关资源同步任务】
    ['electronic-sign/related-resource/sync', 'syncQysRelatedResourceTask'],
    //【契约锁相关资源同步任务日志】
    ['electronic-sign/related-resource/logs', 'relatedResourceLogs'],
    // 物理印章列表
    ['electronic-sign/related-resource/physical-seal/{flowId}', 'getPhysicalSeal'],
    // 电子印章列表
    ['electronic-sign/related-resource/electronic-seal/{flowId}', 'getElectronicSeal'],
    // 业务分类列表
    ['electronic-sign/related-resource/category/{flowId}', 'getCategory'],
    // 物理用印业务分类列表
    ['electronic-sign/related-resource/category/physical/{flowId}', 'getPhysicalCategory'],
    // 电子合同业务分类列表
    ['electronic-sign/related-resource/category/electronic/{flowId}', 'getElectronicCategory'],
    // 文件模板列表
    ['electronic-sign/related-resource/template/{flowId}', 'getTemplate'],
    // 下载用印图片
    ['electronic-sign/seal-apply/images/download', 'sealApplyImagesDownload', 'get'],
    // 检测契约锁服务
    ['electronic-sign/server/check', 'checkServer', 'post'],
    //【工作流契约锁电子合同日志】
    ['electronic-sign/contract/logs','getContractLogsList'],
    // 检测流程是否集成电子合同/物理用印
    ['electronic-sign/flow/relation/{flowId}/{settingId}','checkFlowRelation'],
    // 下载电子合同集成配置说明文档
    ['electronic-sign/document/contract','getDocument'],
    // 自动解析表单到配置
    ['electronic-sign/auto-config/contract', 'autoCheckContractFormConfig', 'post'],
    // 自动解析表单到配置
    ['electronic-sign/auto-config/seal-apply', 'autoCheckSealApplyFormConfig', 'post'],
    // 下载物理用印集成配置说明文档
    ['electronic-sign/document/physical-seal','getPhysicalSealDocument'],
    // 获取平台方企业信息
    ['electronic-sign/company-info','getCompany'],

    // 印控中心
    ['electronic-sign/seal-control-center/setting', 'getSealControlCenterSetting'],
    // 编辑配置
    ['electronic-sign/seal-control-center/setting', 'updateSealControlCenterSetting', 'put'],
    // 获取契约锁访问链接
    ['electronic-sign/seal-control-center/setting/url', 'getSealControlCenterUrl'],
    // 文件签署
    ['electronic-sign/seal-control-center/file/signature', 'fileSignature', 'post'],
    // 说明文档
    ['electronic-sign/document/seal_control','getSealControlDocument'],
];
