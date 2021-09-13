<?php
$routeConfig = [
    ['contract', 'lists',[150]],
    ['contract/my', 'myLists',[150]],
    ['contract', 'store', 'post', [153]],
    ['contract/store-custom/{table}', 'storeProject', 'put', [153]],
    ['contract/number', 'number'],
    ['contract/type', 'typeLists'],
    ['contract/my-type', 'myTypeLists'],
    ['contract/type', 'typeStore', 'post', [155]],
    ['contract/child-projects/{id}', 'childProjects'],
    ['contract/recycle/{id}', 'recycle', 'delete', [151,152]],
    ['contract/recycle/{id}', 'recover', 'put', [154]],
    ['contract/relation/{id}', 'modifyRelation', 'put', [151]],
    ['contract/type/{id}', 'typeShow', [155]],
    ['contract/type/{id}', 'typeUpdate', 'put', [155]],
    ['contract/type/{id}', 'typeDestory', 'delete', [155]],
    ['contract/{id}', 'show',[151, 152]],
    ['contract/{id}', 'update', 'put', [151, 152]],
    ['contract/{id}', 'destory', 'delete', [154]],
    // 获取详情页标签列表
    ['contract/menus/{id}', 'menus'],
    // 详情页标签列表显示/隐藏
    ['contract/menus/{key}', 'toggleCustomerMenus', 'put', [150]],
    // 分享合同
    ['contract/share/{id}', 'shareContract', 'put', [151,150]],
    // 获取当前分享合同已分享给的部门，角色，人员
    ['contract/share/getShare/{id}', 'getShare', 'put', [151,150]],
    // 获取当前人员分享的合同
    ['contract/share/list', 'getShareListId', 'get', [151,150]],
    // 获取所有菜单列表
    ['contract/all/menus', 'allMenus'],
    // 获取全部分类列表(不分页)
    ['contract/type-list/list', 'typeList'],
    // 获取结算情况详情
    ['contract/statistics/{id}', 'contractStatistics'],
    // 获取订单详情
    ['contract/order/{id}', 'contractOrder'],
    // 获取提醒计划详情
    ['contract/remind/{id}', 'contractRemind'],
    // 获取操作日志记录列表
    ['contract/{id}/logs', 'contractLogLists'],
    // 统计各个分类下的合同数量报表
    ['contract/analysis/{type}', 'typeReport'],
    // 所有合同结算统计的总支出，，总收入款
    ['contract/analysis/project/{type}', 'projectReport'],
    // 合同金额
    ['contract/account/money', 'contractMoney'],

    // 结算情况列表
    ['contract/project/list', 'projectList'],

    /**
     * 合同权限
    */
    // 获取权限字段下拉框
    ['contract/fields/user-selector', 'userSelector'],
    // 合同分类字段设置
    ['contract/type/relation-fields/{id}', 'setRelationFields','post',[155]],
    // 合同分类数据权限设置
    ['contract/type/data-permission/{id}', 'setDataPermission','post',[155]],
    // 数据权限组新增
    ['contract/permission-group/add', 'addGroup','post',[972]],
    // 数据权限组删除
    ['contract/permission-group/delete/{id}', 'groupDelete', 'put',[972]],
    // 数据权限组编辑
    ['contract/permission-group/edit/{id}', 'groupEdit', 'put',[972]],
    // 数据权限组列表
    ['contract/permission-group/list', 'groupList',[972]],
    // 数据权限组详情
    ['contract/permission-group/{id}', 'groupDetail',[972]],


    // 监控权限组新增
    ['contract/permission-group-monitor/add', 'monitorAddGroup','post',[972]],
    // 数据权限组删除
    ['contract/permission-group-monitor/delete/{id}', 'monitorGroupDelete', 'put',[972]],
    // 监控权限组列表
    ['contract/permission-group-monitor/list', 'monitorGroupList',[972]],
    // 监控权限组详情
    ['contract/permission-group-monitor/{id}', 'monitorGroupDetail',[972]],
    // 监控权限组编辑
    ['contract/permission-group-monitor/edit/{id}', 'monitorGroupEdit', 'put',[972]],

];
