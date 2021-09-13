<?php
$routeConfig = [
	// 新增产品类别
	['product/product-type/add', 'addProductType', 'post',[173]],
	// 编辑产品类别
	['product/product-type/{productTypeId}', 'editProductType', 'post',[173]],
	// 删除分类
	['product/product-type/{productTypeId}', 'deleteProductType', 'delete',[173]],
	// 批量删除
	['product/product-type/{productTypeId}/batch', 'batchDeleteType', 'delete',[173]],
	// 类别转移
	['product/product-type/migrate/{productTypeId}/{parentId}', 'typeMigrate',[173]],
	// 批量转移
	['product/product-type/move/{productTypeId}/{parentId}', 'batchMoveType',[173]],
	// 产品类别列表
	['product/product-type', 'getProductTypeList'],//产品分类选择器
	// 产品类别排序
	['product/product-type/{types}/sort', 'sortTypes',[173]],
	// 产品类别列表
	['product/product-type/list', 'getProductTypeListByList'],
	// 移动端新增产品时选择分类
	['product/product-type/select', 'getProductTypeListOnSelect', [171, 172, 173]],
	// 判断类别是否有子类
	['product/product-type/has_children', 'hasChildrenOrNot',[173]],
	// 产品类别树api(分类下展示产品)
	['product/product-type-all/{parentId}', 'getProductTypeWithProduct'],//产品选择器左边树
	// 获取子类别列表
	['product/product-type/{parentId}', 'getProductTypeByParentId'],//产品分类选择器
	// 获取父级类别
	['product/product-type-parent/{productTypeId}', 'getProductTypeParent',[173]],
	// 获取所有子类别(传递数组)
	['product/product-type-child', 'getChildProductTypeIdByArray',[173]],
	// 获取所有子类别
	['product/product-type-child/{productTypeId}', 'getAllChildProductTypeId',[171,173]],
	// 获取子类别
	['product/children/{productTypeId}', 'getChildrenTypeIds',[173]],
	// 产品列表
	['product/product', 'getProductList'],
	// 获取产品编号
	['product/number', 'getProductNumber',[171]],
	// 新增产品
	['product/add', 'addProduct', 'post',[172]],
	// 产品编号唯一检查
	['product/number/check', 'checkProductNumber',[171]],
	// 获取类别下产品
	['product/{productTypeId}', 'getProductByTypeId'],//产品选择器
	// 删除产品
	['product/{productId}', 'deleteProduct', 'delete',[171]],
	// 产品详情
	['product/info/{productId}', 'getProductInfo',[171]],
    //产品自定义字段详情
    ['product/custom/info/{tableKey}', 'getProductCustomDataDetail',[171]],
];