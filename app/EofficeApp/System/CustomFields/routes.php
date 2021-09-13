<?php
$routeConfig = [
    ['custom-fields/modules/lists','getCustomModules'],//获取自定义字段模块
    ['custom-fields/parseCutomData', 'parseCutomData'], //解析数据 表单那边用到解析某个值
    ['custom-fields/getCustomFields','getCustomFields'], //获取某个模块所有字段  外发用到
    ['custom-fields/data/{tableKey}', 'getCustomDataLists'],//获取自定义页面列表
    ['custom-fields/data/auto-search/{tableKey}', 'getCustomDataAutoSearchLists'],//获取自动查询的自定义数据列表
    ['custom-fields/data/{tableKey}/{dataId}', 'getCustomDataDetail'],//获取自定义页面数据详情
    ['custom-fields/data/{tableKey}/{dataId}', 'deleteCustomData', 'delete'],//删除自定义页面数据
    ['custom-fields/data/{tableKey}/{dataId}', 'editCustomData', 'post'],//编辑自定义页面数据
    ['custom-fields/data/{tableKey}', 'addCustomData', 'post'],//新建自定义页面数据
	['custom-fields/{tableKey}', 'listCustomFields'],//获取自定义字段列表
    ['custom-fields/{tableKey}', 'saveCustomFields', 'post',[405]],//保存自定义字段
    // ['custom-fields/{table_key}/{field_id}', 'showCustomField'],//获取某个自定义字段详情  
];