<?php
$routeConfig = [
        ['income-expense/plan-type', 'addPlanType', 'post',[138]], //新增方案类别
        ['income-expense/plan-type/{planTypeId}', 'editPlanType', 'post',[138]], //编辑方案类别
        ['income-expense/plan-type', 'listPlanType'], //展示方案类别
        ['income-expense/plan-type/{planTypeId}', 'showPlanType',[138]], //展示某个方案类别信息
        ['income-expense/plan-type/{planTypeId}', 'deletePlanType', 'delete',[138]], //删除方案类别
        ['income-expense/plan', 'addPlan', 'post',[136]], //新增方案
        ['income-expense/plan/{planId}', 'editPlan', 'post',[137]],//编辑方案
        ['income-expense/plan', 'listPlan',[132,137]], //我的方案列表
        ['income-expense/list-plan', 'getListPlan',[132,133,134,135,136,137]],//我的方案列表
        ['income-expense/proceed-plan', 'getProceedListPlan',[132,133,134,135,136,137]],//进行中方案列表
        ['income-expense/all-plan', 'listAllPlan', [132,137,133,134,135]], //所有方案列表
        ['income-expense/portal-plan', 'listAllPlan', [137]], //门户收支方案列表
        ['income-expense/plan/{planId}', 'showPlan',[135,137,133]], //某个方案信息
        ['income-expense/plan/{planId}/edit', 'showEditPlan',[136]], //编辑方案
        ['income-expense/plan/{planId}', 'deletePlan', 'delete',[137]], //删除方案
        ['income-expense/plan/{planId}/begin', 'beginPlan',[137]], //开始方案
        ['income-expense/plan/{planId}/end', 'endPlan',[137]], //结束方案
        ['income-expense/trashed-plan', 'listTrashedPlan',[139]], //获取假删除方案列表
        ['income-expense/trashed-plan/{planId}/recover', 'recoverTrashedPlan',[139]], //恢复删除方案
        ['income-expense/trashed-plan/{planId}/destroy', 'destroyTrashedPlan', 'delete',[139]],//销毁方案
        ['income-expense/record', 'addRecord', 'post',[135]], //新增收支记录
        ['income-expense/record/{recordId}', 'editRecord', 'post',[133]],//编辑收支记录
        ['income-expense/record', 'listRecord',[133]],//收支记录列表
        ['income-expense/record/{recordId}', 'deleteRecord', 'delete',[133]], //删除收支记录
        // ['income-expense/record/{record_id}', 'showRecord'], //展示某个收支信息
        ['income-expense/stat', 'planStat',[134]], //收支统计
        ['income-expense/diff-stat', 'planDiffStat',[134]], //分别统计收支方案
        ['income-expense/plan-code', 'getPlanCode',[136]], //获取方案编号
];