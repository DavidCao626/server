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
//921  资产登记
//922  使用申请
//923  申请审批
//924  信息变更
//925  资产维护
//926  资产退库
//927  资产盘点
//929  资产编码规则设置
//930  资产分类设置
//931  分析报表
//932  资产清单
//933  折旧对账表
//934  资产汇总
$routeConfig = [
    //资产分类列表
    ['assets/assets-type', 'getAssetsType', [921, 922, 923, 924, 925, 926, 932, 933]],
    //创建资产分类类型
    ['assets/create-type', 'createType', 'POST', [930]],
    //资产分类下拉列表(资产选择器左侧分类列表)
    ['assets/select-type', 'selectType', [921, 922, 923, 924, 925, 926, 927, 932, 933, 930]],
    //资产分类table列表(下拉框与资产分类列表菜单使用)
    ['assets/type-list', 'typeList', [920]], // 扫描详情时调用,有资产权限即可
    //资产分类删除
    ['assets/delete-type/{id}', 'deleteType', [930]],
    //资产分类详情
    ['assets/detail-type/{id}', 'detailType', [921, 930]],
    //资产分类编辑
    ['assets/edit-type/{id}', 'editType', 'PUT', [930]],
    //条码规则生成
    ['assets/rule-set/{type}', 'ruleSet', [921, 929]],
    //条码规则设置
    ['assets/rule-set', 'setCode', 'POST', [929]],
    //条码规则详情
    ['assets/rule-detail', 'ruleDetail', [929, 924, 921, 923, 925]],
    //资产入库数据创建
    ['assets/creat-data', 'creatAssets', 'POST', [921]],
    //资产入库记录详情
    ['assets/assets-detail/{id}', 'assetsDetail', [921, 922, 925, 926]],
    //资产入库列表
    ['assets/assets-data', 'getAssetsList', [921, 932]],
    //门户列表处理
    ['assets/portal-assets', 'portalAssetsList', [921]],
    //资产入库(系统数据外发下拉框配置)
    ['assets/assets-data/{type}', 'assetTypesList', [920]],
    //资产系统选择器接口(user_id调整)
    ['assets/assets-list/{sign}', 'assetChoiceList', [920]],
    //资产使用申请
    ['assets/apply', 'apply', 'POST', [922]],
    //资产使用申请列表
    ['assets/apply-list', 'applyList', [922, 923]],
    //资产申请详情
    ['assets/apply-detail/{id}', 'applyDetail', [922, 923]],
    //申请删除
    ['assets/delete-apply/{id}', 'deleteApply', 'DELETE', [922, 923]],
    //申请审批
    ['assets/approval-apply/{id}', 'approvalApply', 'PUT', [922, 923]],
    //使用资产归还
    ['assets/return-apply/{id}', 'returnApply', 'PUT', [922, 923]],
    //资产变更
    ['assets/assets-change/{id}', 'assetsChange', 'POST', [924]],
    //资产变更列表
    ['assets/change-list', 'changeList', [924]],
    //资产变更记录详情
    ['assets/change-detail/{id}', 'changeDetail', [924]],
    //资产维护申请
    ['assets/repair', 'repair', 'POST', [925]],
    //资产维护申请列表
    ['assets/assets-repair', 'repairList', [925]],
    //资产维护申请详情
    ['assets/repair-detail/{id}', 'repairDetail', [925]],

//    ['assets/repair-complete/{id}', 'repairComplete'],
    //资产维护申请编辑
    ['assets/repair-edit/{id}', 'repairEdit', 'PUT', [925]],
    //资产退库申请
    ['assets/create-retir', 'createRetiring', 'POST', [926]],
    //资产退库列表
    ['assets/retir-list', 'retirList', [926]],
    //资产退库详情
    ['assets/retir-detail/{id}', 'retirDetail', [926]],
    //新增盘点
    ['assets/create-inventory', 'createInvent', 'POST', [927]],
    //盘点列表
    ['assets/inventory-list', 'inventoryList', [927]],
    //盘点清单
    ['assets/inventory-view/{id}', 'inventoryView', [927]],
    //盘点清单内容列表
    ['assets/data-view/{id}', 'detailList', [927]],
    //盘点资产状态编辑
    ['assets/inventory-status/{id}', 'inventoryStatus', 'PUT', [927]],
    //清单履历列表
    ['assets/assets-resume', 'resumeList', [932]],
    //折旧对账表
    ['assets/assets-account', 'account', [933]],
    //折旧详情
    ['assets/account-detail/{id}', 'accountDetail', [933]],
    //资产汇总
    ['assets/assets-summary', 'summary', [934]],
    //生成二维码
    ['assets/assets-qrcode/{id}', 'qrcode', [921, 923, 924, 925]],
    //扫码详情/盘点
    ['assets/qrcode-detail/{id}', 'qrcodeDetail', [920]],
    //获取自定义字段
    ['assets/custom-fields/{table}', 'customFields', 'PUT', [921, 923, 924, 925, 929]],

    ['assets/flow/data/config', 'flowData', [920]],

    ['assets/flow/{key}', 'getFlow', 'PUT', [920]],

    // 预生成表单
    ['assets/add/flow/define/{key}/{id}', 'addFlowDefine', 'PUT', [920]],

    //资产登记删除
    ['assets/delete/{id}', 'deleteStorage', 'DELETE', [921, 932]],

    // 资产表单数据源
    ['assets/data-source-by-assets_id', 'dataSourceByAssetsId'],

    //盘点删除
    ['assets/delete/inventory/{id}', 'deleteInventory', 'DELETE', [927]],

    //领用方式
    ['assets/apply-way', 'applyWay', [922]],
];