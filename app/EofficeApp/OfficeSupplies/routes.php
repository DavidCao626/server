<?php
    // 264 模块id
    //    265: 定义类别
    //    266: 基本资料
    //    267: 入库管理
    //    268: 使用申请
    //    269: 申请审批
    //    270: 采购清单
$routeConfig = [
    //获取全部办公用品二级分类列表
    ['office-supplies/type/second', 'getOfficeSuppliesAllSecondTypeList', [270]],
    //获取办公用品二级分类列表
    ['office-supplies/type/second/{typeFrom}', 'getOfficeSuppliesSecondTypeList', [268, 269, 266]],
    //获取办公用品所有分类列表
    ['office-supplies/type/all', 'getOfficeSuppliesAllTypeList', [265]],
    //获取父级办公用品类型列表
    ['office-supplies/type/parent', 'getOfficeSuppliesTypeParentList', [265]],
    //创建入库记录
    ['office-supplies/storage', 'createStorageRecord', 'post', [267]],
    //快速入库
    ['office-supplies/quick-storage', 'createQuickStorageRecord', 'post', [267]],
    //获取入库记录
//    ['office-supplies/storage/{storage_id}', 'getStorageRecord'],
    //获取入库记录列表
    ['office-supplies/storage', 'getStorageList', [267]],
    //清空办公用品数据
    ['office-supplies/storage', 'emptySuppliesData', 'delete', [267]],
    //删除用户记录
    ['office-supplies/storage/{storageId}', 'deleteStorageRecord', 'delete', [267]],
    //创建申请记录
    ['office-supplies/apply', 'createApplyRecord', 'post', [268]],
    //批量申请记录
    ['office-supplies/batch-apply', 'batchCreateApplyRecord', 'post', [268]],
    //获取申请记录
    ['office-supplies/apply/{applyId}', 'getApplyRecord', [268, 269]],
    //获取申请列表
    ['office-supplies/apply', 'getApplyList', [269]],
    //获取申请列表
    ['office-supplies/my-apply', 'getMyApplyList', [268]],
    //删除申请记录
    ['office-supplies/apply/{applyId}', 'deleteApplyRecord', 'delete', [268, 269]],
    //审批/归还申请
    ['office-supplies/apply/{applyId}', 'modifyApplyRecord', 'put', [269]],
    //获取申请审批列表 前端未使用
//    ['office-supplies/apply-approval', 'getApplyApprovalList'],
    //获取采购列表
    ['office-supplies/purchase', 'getPurchaseList', [270]],
    //获取采购详情
    ['office-supplies/purchase/{id}', 'getPurchaseDetailById', [270]],
    //获取入库编号
    ['office-supplies/storage-no', 'getCreateNo', [268, 267]],
    //创建办公用品类型
    ['office-supplies/type', 'createOfficeSuppliesType', 'post', [265]],
    //编辑办公用品类型
    ['office-supplies/type/{typeId}', 'modifyOfficeSuppliesType', 'put', [265]],
    //获取办公用品类型数据
    ['office-supplies/type/{typeId}', 'getOfficeSuppliesType', [265]],
    //获取办公用品类型列表
    ['office-supplies/type', 'getOfficeSuppliesTypeList', [264]],
    ['office-supplies/get-permission-type', 'getPermissionOfficeSuppliesTypeList'],
    //删除办公用品类型数据
    ['office-supplies/type/{typeId}', 'deleteOfficeSuppliesType', 'delete', [265]],
    //创建办公用品
    ['office-supplies', 'createOfficeSupplies', 'post', [266]],
    //编辑办公用品
    ['office-supplies/{officeSuppliesId}', 'modifyOfficeSupplies', 'put', [266]],
    //获取办公用品数据
    ['office-supplies/{officeSuppliesId}', 'getOfficeSupplies', [268, 266, 267]],
    //获取全部办公用品列表
    ['office-supplies', 'getAllOfficeSuppliesList', [270]],
    //获取有权限的办公用品列表
    ['office-supplies/permission/{typeFrom}', 'getOfficeSuppliesList', [268, 269, 266, 267]],
    //获取全部正常办公用品列表/即没有type
    ['office-supplies/supplies/normal', 'getSuppliesAllNormalList', [264]],
    //获取有权限的办公用品列表
    ['office-supplies/supplies/normal/{typeFrom}', 'getSuppliesNormalList', [264]],
    //删除办公用品
    ['office-supplies/{officeSuppliesId}', 'deleteOfficeSupplies', 'delete', [266]],

];
