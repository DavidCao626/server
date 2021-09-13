<?php
    //23: 个人通讯录,
    //25: 公共通讯录,
    //67: 公共通讯录管理

$routeConfig = [
    // 个人/公共，获取子组,分组管理用,选择器有用
    ['address/group/children/{groupType}/{parentId}', 'getChildren'],
    // 获取组
	['address/group/{groupType}/view', 'getViewChildrenBySearch'],
//	['address/group/{group_type}/view', 'getViewChildrenBySearch', [25, 67]],
    // 递归获取所有可查看的通讯组,手机端筛选分类列表
    ['address/group/all-children/1', 'getAllViewGroupsByRecursion', [25, 67]],
    ['address/group/all-children/2', 'getPrivateGroupsOrderByTree', [23]],
    // 分组信息
    ['address/group/{groupType}', 'listGroup'],
    //获取parent_id分类的子分类
//    ['address/group/view-children/{group_type}/{parent_id}', 'getViewChildren', [25, 67, 23]],
    ['address/group/view-children/{groupType}/{parentId}', 'getViewChildren'],
//    ['address/group/{group_type}/{group_id}', 'showGroup', [1000000]],
    // 分组信息，基本设置
    ['address/group-manage/{groupType}/{groupId}', 'showManageGroup', [23, 67]],
    // 新建子组
    ['address/group', 'addGroup', 'post', [23, 67]],
    // 分组，基本设置修改
    ['address/group/{groupId}', 'editGroup', 'post', [23, 67]],
    // 分组排序
    ['address/group/sort/{groupType}', 'sortGroup', 'post', [23, 67]],
    // 删除分组
    ['address/group/{groupType}/{groupId}', 'deleteGroup', 'delete', [23, 67]],
    // 分组转移
    ['address/group/migrate/{groupType}/{fromId}/{toId}', 'migrateGroup', [23, 67]],
//    ['address/import', 'importAddress', [1000000]],
//    ['address/export', 'exportAddress', [1000000]],
    // 公共通讯录列表
    ['address/1', 'listAddressPublic', [25, 67]],
    ['address/selector/search/{groupType}', 'listAddressPublicForSelector'],
    // 个人通讯录列表
    ['address/2', 'listAddressPrivate', [23]],
    ['address/direct/1', 'directPublicAddress', [25, 67]],
    ['address/direct/2', 'directPrivateAddress', [23]],
//    ['address', 'addAddress', 'post', [1000000]],
//    ['address/{address_id}', 'editAddress', 'post', [1000000]],
//    ['address/{group_type}/{address_id}', 'showAddress', [1000000]],
//    ['address/{address_id}', 'deleteAddress', 'delete', [1000000]],
    ['address/migrate/{groupId}/{addressId}/{tableKey}', 'migrateAddress', [23, 67]],
    ['address/copy/{groupId}/{addressId}', 'copyAddress', [25]],
    ['address/children/1/{parentId}', 'getAddressPublicFamily', [25, 67]],
    ['address/children/2/{parentId}', 'getAddressPrivateFamily', [23]],
    ['address/list/1/{groupId}', 'getAddressPublicList'],
    ['address/list/2/{groupId}', 'getAddressPrivateList'],
    ['address/tree/1/{groupId}', 'getAddressPublicTree'],
    ['address/tree/2/{groupId}', 'getAddressPrivateTree']
];
