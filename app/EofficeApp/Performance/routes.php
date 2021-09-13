<?php
$routeConfig = [
    //    83: 我的考核
    //    86: 考核方案
    //    87: 考核模板
    //    89: 考核统计
    //    88: 设置考核人

    //获取当前人员及其所有有考核权限的人员信息
    ['performance/my-perform', 'getMyPerform', [83]],
    //获取某个用户某个考核方案的某个月/季/半年/年的考核数据
    ['performance/my-perform/{userId}', 'getPerformData', [83]],
    //查询某个用户某种方案下的当前模板数据
    ['performance/my-perform/temp/{userId}', 'getMyTemp', [83]],
    //保存考核数据
    ['performance/my-perform', 'createPerform', 'post', [83]],
    //获取考核方案列表
    ['performance/performance-plan', 'getPlanList', [86, 87]],
    //获取考核方案数据
    ['performance/performance-plan/{planId}', 'getPlanInfo', [83, 86]],
    //修改考核方案
    ['performance/performance-plan/{planId}', 'modifyPlan', 'put', [86]],
    //performance/创建模板
    ['performance/performance-temp', 'createTemp', 'post', [87]],
    //获取考核模板列表
    ['performance/performance-temp', 'getTempList', [87]],
    //获取考核模板列表10.5
    ['performance/performance-temp-list', 'getTempListNew', [87]],
    //获取模板数据
    ['performance/performance-temp/{tempId}', 'getTempInfo', [83, 87]],
    //编辑考核模板
    ['performance/performance-temp/{tempId}', 'modifyTemp', 'put', [87]],
    //复制模板
    ['performance/performance-temp/{tempId}', 'copyTemp', 'post', [87]],
    //删除考核模板
    ['performance/performance-temp/{tempId}', 'deleteTemp', 'delete', [87]],
    //获取没有考核人的用户列表
    ['performance/performer', 'getNoPerformer', [88]],
    //获取用户考核人
    ['performance/performer/{userId}', 'getPerformer', [88]],
    //获取用户被考核人
    ['performance/performer-approver/{userId}', 'getApprover', [88]],
    //清空考核人
//    ['performance/performer/{user_id}', 'makePerformerEmpty', 'put', [88]],
    //清空被考核人
//    ['performance/performer-approver/{user_id}', 'makeApproverEmpty', 'put', [88]],
    //设置指定用户的考核人为默认人员
    ['performance/performer/default/{userId}', 'setPerformerDefault', 'put', [88]],
    //设置指定用户的被考核人为默认人员
    ['performance/performer-approver/default/{userId}', 'setApproverDefault', 'put', [88]],
    //编辑用户考核人
    ['performance/performer/{userId}', 'modifyPerformer', 'post', [88]],
    //获取考核统计列表
    ['performance/performance-statistic', 'getStatisticList', 'post', [89]],
    //用户搜索
    ['performance/performance-statistic/user', 'getStatisticUser', [89]],
    //判断考核中的周期
    ['performance/my-perform/current/{circle}', 'getCurrentMonth', [83]],
    //样式类型
    ['performance/performer/performer-class/month-class', 'getMonthClass', [83]],
    ['performance/performer/performer-class/season-class', 'getSeasonClass', [83]],
    ['performance/performer/performer-class/halfYear-class', 'getHalfYearClass', [83]],
];
