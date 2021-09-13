<?php
$routeConfig = [
	['report/tag/list', 'getAllTag'],
	['report/datasource/datasource-type', 'getDatasourceType',[524]],
	['report/datasource/add', 'addDatasource', 'post',[524]],
	['report/datasource/list_info', 'findDatasource',[524]],
	['report/datasource/{datasource_id}', 'editDatasource', 'put',[524]],
	['report/datasource/{datasourceId}','deleteDatasource', 'delete',[524]],
	// ['report/chart/example', 'getChartExample'],
	//报表列表
	['report/chart/list', 'getChartList',[520]],
	//查看报表
	['report/chart/list_view', 'getChartListPermission',[521]], //报表选择器
	//获得报表信息
	['report/chart/list_info', 'findChart',[521,522,523]],
	//删除报表
	['report/chart/{chartId}','deleteChart','delete',[523]],
	//添加报表
	['report/chart/add', 'addChart', 'post',[522]],
	//编辑报表
	['report/chart/{chartId}','editChart', 'put',[523]],
	//获得报表信息
	['report/chart/get_chart', 'getChart', 'post',[521,522,523]],
	//数据过滤
	['report/datasource/filter', 'getDatasourceFilter',[521,522,523]],
	//外部数据 --- 存在漏洞问题 移除路由
//	['report/chart/origin','getOriginList'],
	['report/datasourcelist','getDatasource',[522,523,524]],
	['report/chartlist','chartList',[521]],
	['report/getUrlData','getUrlData',[524]],
	['report/getImportData','getImportData',[524]],
	//请求自定义报表数据，加载报表图表
	// ['report/chart/get_custom_chart', 'getCustomChart', 'post'],
	//保存自定义报表数据
	['report/chart/saveCustomData', 'saveCustomData', 'post',[524]],
	//编辑自定义报表数据
	['report/chart/editCustomData', 'editCustomData', 'post',[524]],
	['report/chart/get-grid-list', 'getGridList', 'post'],
];