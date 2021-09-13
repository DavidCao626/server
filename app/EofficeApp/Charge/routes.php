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
        /*
         *菜单
         *费用清单 38
         *费用类型 40
         *我的费用 41
         *费用录入 42
         *费用统计 90
         *预警设置 91
         *权限设置 92
        */

        /*
        *费用类型
        */
        // 某个费用类别的详细
        ['charge/get-charge-type/{chargeTypeId}', 'getChargeTypeById', [40]],
        // 费用类型列表
        ['charge/type', 'chargeTypeList'],
        // 费用类型搜索，返回完整的科目路径
        ['charge/type/search', 'chargeTypeSearch'],
        // 所有费用类型
        ['charge/type/all', 'chargeTypeLists'],
        // 新增费用类型
        ['charge/type/add', 'addChargeType', 'POST', [40]],
        // 获取所有二级费用类型
        ['charge/type/sub', 'getChargeSubType', [38, 40, 41, 42, 90]],
        // 编辑费用类型
        ['charge/type', 'editChargeType', 'PUT', [40]],
        // 删除费用类型
        ['charge/type/{chargeTypeId}', 'deleteChargeType', 'delete', [40]],
        // 根据一级费用类型获取二级费用类型
        ['charge/type/{parentId}', 'getChargeTypeListByParentId', [38, 40, 41, 42]],

        /*
        *费用预警设置
        */
        // 费用预警列表
        ['charge/set', 'getChargeSetList', [91]],
        // 设置费用预警
        ['charge/set', 'chargeSet', 'POST', [91]],
        // 预警设置选择器
        ['charge/set/selector', 'getChargeSetSelectorData'],
        // 获取某一个费用预警的详情
        ['charge/set/{setId}', 'getOneChargeSetting', [91]],
        // 根据日期获取预警信息
        ['charge/set/date', 'getChargeSetByDate', 'post', [38, 41, 42]],
        // 删除预警
        ['charge/set/{setId}', 'deleteChargeSetItem', 'delete', [91]],
        //编辑费用预警设置，展示科目预警值
        ['charge/subject', 'chargeSubjectList', [91]],

        /*
        *录入费用
        */
        // 批量新建费用
        ['charge/muti-charge/add', 'addMutiCharge', 'POST', [42]],
        // 新建费用
        ['charge/new-charge/add', 'addNewCharge', 'POST', [42]],
        // 编辑费用
        ['charge/new-charge/{chargeListId}', 'editNewCharge', 'PUT', [38, 41]],
        // 删除费用(外发的费用只有admin可以删除，非外发的可看即可删)
        ['charge/new-charge/{chargeListId}', 'deleteNewCharge', 'delete', [38, 41]],

        /*
        *权限设置
        */
        // 获取权限设置列表
        ['charge/permission', 'getChargePermission', [92]],
        // 设置权限
        ['charge/permission', 'addChargePermission', 'POST', [92]],
        // 权限设置详情
        ['charge/permission/view', 'getChargePermissionView', [92]],
        // 编辑权限设置
        ['charge/permission/{id}', 'editChargePermission', 'PUT', [92]],
        // 获取权限详情
        ['charge/permission/{id}', 'getChargePermissionById', [92]],
        // 删除权限设置
        ['charge/permission/{id}', 'deleteChargePermission', 'delete', [92]],

        /*
        *费用清单，我的费用
        */
        // 我的费用，费用列表点击后的详情列表
        ['charge/list', 'chargeListDetail', [38, 41]],
        // 费用清单，我的费用表格视图
        ['charge/list/data', 'chargeListData', [38, 41]],
        // 费用清单中间列组织树合计值
        ['charge/list/total', 'getChargeTreeTotal', [38]],
        // 费用清单中间列展开部门时调用
        ['charge/list/{parentId}', 'chargeListTree', [38]],
        // 图表视图
        ['charge/charts', 'chargeCharts', [38, 41]],

        /*
        *表单费用数据源
        */
        // 科目预警值数据源
        ['charge/data-source/subject', 'chargeSubjectValue'],
        //科目已报销数据源
        ['charge/data-source/subject-use', 'chargeSubjectUseValue'],
        //科目剩余报销数据源
        ['charge/data-source/subject-unuse', 'chargeSubjectUnuseValue'],
        // 科目已报销总额
        ['charge/data-source/subject-all-use', 'chargeSubjectAllUse'],
        // 预警对象类型数据源
        ['charge/data-source/set-type', 'getChargeSetType'],
        // 预警方式
        ['charge/data-source/alert-method', 'getChargeAlertMethod'],
        // 指定时间范围内已报销额度
        ['charge/data-source/date-range/{type}', 'getDataSourceInDateRange'],
        //个人费用数据源
        ['charge/data-source/{userId}', 'chargeDataSource'],
        //部门费用数据源
        ['charge/data-source-by-dept/{deptId}', 'chargeDataSourceByDeptId'],
        //费用数据源
        ['charge/data-source-by-charge-name', 'chargeDataSourceByChargeName'],
        //公司费用数据源
        ['charge/data-source-by-company', 'chargeDataSourceByCompany'],
        //项目费用数据源
        ['charge/data-source-by-project/{projectId}', 'chargeDataSourceByProject', [162]],
        //费用承担者下拉框
        ['charge/data-source-by-undertake', 'chargeDataSourceByUndertake'],

        // 门户费用元素
        ['charge/portal', 'getChargePortal', [41]],
         //费用统计列表
        ['charge/statistics', 'chargeStatistics', [90]],
        //费用统计明细列表
        ['charge/details', 'chargeDetails', [90]],
        ['charge/app/alert', 'chargeAppAlert', [41]],
        ['charge/app/total', 'chargeAppTotal', [41]],
        ['charge/app/type/{parentId}', 'chargeAppTree', [41]],
        //手机app我的费用页面面板数据
        ['charge/mobile/type', 'chargeMobileType'],
        //录入费用时，根据用户id获取用户预警
        ['charge/get-charge-value/{userId}', 'getChargeSetByUserId', [38, 41, 42]],

        // 费用详情
        ['charge/{chargeListId}', 'getNewCharge', [38, 41, 90]],
        // 获取导入的费用科目预警值
        ['charge/subject/warning', 'getChargeSubjectWarning']
];
