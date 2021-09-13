<?php
    $routeConfig = [
        /*
         *菜单
         *薪资调整 378
         *薪资报表 379
         *上报管理 380
         *薪酬权限 382
         *我的薪资 319
         *薪资录入 19
         *薪资管理 65
        */

    	//录入时获取当前用户薪资是否上报
        ['salary/is-report/{userId}/{reportId}', 'isSalaryReport', [19]],//ok

        /*
        *薪资项
        */
        // 薪资管理薪资项列表，薪资调整薪资项下拉框
        ['salary/items', 'getIndexSalaryItems', [378, 65]], //ok
        // 薪资调整列表，我的调薪记录列表高级查询薪资项下拉框，获取数值型薪资项
        ['salary/items/numeric', 'getNumericSalaryItems'],
        // 薪资管理薪资项中间列模糊查询
        ['salary/items/all', 'getAllSalaryItems', [65]],//ok
        // 新建薪资项，获取默认序号
        ['salary/items/maxsort', 'getMaxSort', [65]],//ok
        // 薪资管理-回收站列表
        ['salary/items/deleted', 'getDeletedSalaryItems', [65]],//ok
        // 薪资调整获取薪资项调整前值；某个薪资项详情
        ['salary/items/{fieldId}', 'getIndexSalaryItemsByFieldId', [65, 378]],//ok

        //新建薪酬项
        ['salary/add', 'addSalary', 'post', [65]],//ok
        //编辑薪酬项目
        ['salary/edit/{fieldId}', 'editSalaryInfo', 'post', [65]],//ok
        //删除薪酬项目
        ['salary/delete/{fieldId}', 'deleteSalaryField', 'delete', [65]], //ok
        // 回收站彻底删除薪资项
        ['salary/delete/force/{fieldId}', 'forceDeleteSalary', 'delete', [65]], //ok
        // 个人基准值列表
        ['salary/{fieldId}/personal-default-list', 'getSalaryPersonalDefaultList'],
        ['salary/{fieldId}/personal-default-list', 'setSalaryPersonalDefault', 'post', [65]],
        // 个人默认值
        ['salary/{fieldId}/default/{userId}', 'getUserPersonalDefault'],


        /*
        *上报管理
        */
        //上报列表
        ['salary/reports', 'getIndexSalaryReports', [19, 380]], //ok
        //新建薪酬上报流程
        ['salary/reports', 'createSalaryReports', 'post', [380]], //ok
        //终止薪酬上报流程
        ['salary/reports/{id}', 'editSalaryReports', 'put', [380]], //ok
        //删除薪酬上报流程
        ['salary/reports/{id}', 'deleteSalaryReports', 'delete', [380]], //ok
        //编辑薪酬上报日期
        ['salary/reports/{id}/date', 'editSalaryReportsDate', 'post', [380]], //ok
        //查询某次上报记录员工薪酬报表
        ['salary/reports/{reportId}/employees', 'getIndexSalaryReportsEmployees', [380]],//ok

        // 薪资录入-上报
        ['salary/user-salary', 'inputUserSalary', 'post', [19]],//ok
        // 薪资录入-部门批量上报
        ['salary/user-salary/multi-entry', 'multiReportedUserSalary', 'post', [19]],//ok
        //移动端我的薪资-薪资详情
        ['salary/user-salary/mobile', 'getUserSalaryDetailByMobile', [319]],//ok

        //我的薪资
        ['salary/my-salary', 'getMySalaryList'],//ok
        // 移动端我的薪酬列表
        ['salary/my-salary/mobile', 'getMySalaryListByMobile', [319]],//ok
        // 获取上报的薪资项
        ['salary/entry/{reportId}/fields', 'getEntryFieldLists', [19]],
        // 上报页面初始化时获取上报用户的薪资值
        ['salary/entry/{reportId}/{userId}/values', 'getEntryValuesWithNoInput', [19]],
        // 上报输入时获取薪资值
        ['salary/entry/{reportId}/{userId}/values', 'getEntryValuesWithInput', 'POST', [19]],
        // 按部门上报，初始化，获取：部门下人员(递归)；每个人的薪资值；已填报的值；
        ['salary/dept-entry/{reportId}/{deptId}', 'getDeptEntryInitValues', [19]],

        /*
        *薪酬项
        */


        /*
        *薪酬调整
        */
        //新建薪酬调整
        ['salary/adjust', 'addSalaryAdjust', 'post', [378]],//ok
        //薪酬调整列表
        ['salary/adjust', 'getSalaryAdjust'],
        //调整前薪资，表单系统数据中使用
        ['salary/adjust/old', 'getSalaryAdjustOld'],//ok
        // 薪资调整详情
        ['salary/adjust/{adjustId}', 'getSalaryAdjustInfo', [378]],//ok
        // 获取最新一条薪资调整记录(废弃，前端已经不再调用)
        ['salary/adjust/last/{userId}', 'getLastSalaryAdjust', [378]],//ok

        // 薪资报表
        ['salary/report', 'getSalaryReport', [379]],

        /*
        *薪资权限
        */
        // 权限设置列表
        ['salary/report/set', 'getSalaryReportSetList', [382]], //ok
        // 新增权限
        ['salary/report/set', 'addSalaryReportSet', 'post', [382]],//ok
        // 编辑权限
        ['salary/report/set/{id}', 'editSalaryReportSet', 'put', [382]],//ok
        // 查看权限详情
        ['salary/report/set/{id}', 'getSalaryReportSetById', [382]],//ok
        // 删除权限设置
        ['salary/report/set/{id}', 'deleteSalaryReportSet', 'delete', [382]],//ok

        // 可选的计算数据，薪资项-预设值-系统数据选择器使用
        ['salary/calculate', 'getCalculateData', [65]], //ok
        ['salary/calculate/detail', 'getCalculateDetail', [65]],//ok

        // 薪资管理薪资项中间列
        ['salary/fields/list', 'getSalaryFieldsManageList', [65]],//ok
        // 父级薪资项选择器接口
        ['salary/fields/parent', 'getSalaryFieldsParent', [65]],//ok
        // 薪资项移动
        ['salary/fields/move', 'salaryFieldMove', [65]],//ok
        // 薪资调整外发，薪资项下拉
        ['salary/fields/select', 'salaryFieldCanAdjust'],//ok
        // 获取可参与计算的薪资项
        ['salary/fields/count', 'getSalaryFieldsInCount', [65]], //ok
        // 录入页中间列组织树筛选，调整列表高级查询选择器筛选;20201126-获取当前用户可管理的用户集合
        // 19  薪资录入 378 薪资调整(201903加的);380 上报管理 379 薪资报表(202103修复问题增加);
        ['salary/manage', 'getManageUser', [19, 378, 380, 379]],

        // 人事卡片页面报表
        ['salary/{userId}/form', 'getSalaryForm', [319]],
        // 薪酬，基础设置
        ['salary/base-set', 'getSalaryBaseSet'], // 获取
        ['salary/base-set', 'saveSalaryBaseSet', 'post'], // 保存

    ];
