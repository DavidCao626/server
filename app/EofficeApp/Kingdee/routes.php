<?php
$routeConfig = [
    // 账套类接口
    // 新增k3账套
    ['kingdee/k3/account/config', 'addK3Account','post'],
    // 获取单个k3账套详情
    ['kingdee/k3/account/config', 'getK3AccountDetail','get'],
    // 删除单个k3账套
    ['kingdee/k3/account/config', 'deleteK3Account','delete'],
    // 更新单个k3账套信息
    ['kingdee/k3/account/config', 'updateK3Account','put'],
    // 获取k3账套列表
    ['kingdee/k3/account/configlist', 'getK3AccountList','get'],
    // 校验k3账套信息
    ['kingdee/k3/account/check', 'checkConfig','post'],

    // 单据类接口
    // 新增k3单据
    ['kingdee/k3/table/info', 'addK3Table','post'],
    // 获取单个k3单据
    ['kingdee/k3/table/info', 'getK3TableDetail','get'],
    // 获取k3单据列表
    ['kingdee/k3/table', 'getK3TableList','get'],
    // 删除单个k3单据
    ['kingdee/k3/table/info', 'deleteK3Table','delete'],
    // 更新单个k3单据信息
    ['kingdee/k3/table/info', 'updateK3TableDetail','put'],
    // 根据单据获取字段列表
    ['kingdee/k3/table/field', 'getK3TableField','get'],
    // 根据单据获取账套信息
    ['kingdee/k3/table/account', 'getK3AccountByTable','get'],

    // k3单据与流程关联接口
    // 新增k3单据与流程关联信息
    ['kingdee/k3/table/flow', 'addK3TableFlow','post'],
    // 获取k3单据已关联的流程列表
    ['kingdee/k3/table/flowlist', 'getK3TableFlowList','get'],
    // 获取k3单据已关联的流程信息
    ['kingdee/k3/table/flow', 'getK3TableFlow','get'],
    // 更新k3单据已关联的流程信息
    ['kingdee/k3/table/flow', 'updateK3TableFlow','put'],
    // 删除k3单据已关联的流程信息
    ['kingdee/k3/table/flow', 'deleteK3Flow','delete'],
    // 前端k3单据选择器接口
    ['kingdee/k3/select/table/flow', 'getK3TableSelectByFlow','get'],

    // 流程外发相关的接口
    ['kingdee/k3/outer', 'outer','post'],


    // API模块接口
    // API通用访问接口
    ['kingdee/k3/cloudapi/data', 'getK3ApiData','get'],
    // 表单获取K3数据源下拉框api接口列表
    ['kingdee/k3/cloudApi/list', 'getK3CloudApiList','get'],
    // 表单获取K3数据源智能api请求路由
    ['kingdee/k3/smartApi', 'getK3SmartApiData','get'],
    // 表单获取K3数据源下拉框静态数据接口列表
    ['kingdee/k3/staticData/list', 'getK3StaticDataList','get'],
    // 表单下拉框静态数据获取数据接口
    ['kingdee/k3/staticData/data', 'getStaticDataSource','get'],


    // 数据源模块
    // 新增静态数据
    ['kingdee/k3/static/data', 'addStaticData','post'],
    // 获取静态数据信息
    ['kingdee/k3/static/data', 'getStaticData','get'],
    // 更新静态数据
    ['kingdee/k3/static/data', 'updateStaticData','put'],
    // 删除静态数据
    ['kingdee/k3/static/data', 'deleteStaticData','delete'],
    // 获取静态数据列表
    ['kingdee/k3/static/datalist', 'getStaticDataList','get'],


    // 数据源模块
    // 新增cloudApi配置
    ['kingdee/k3/cloudapi/config', 'addCloudApiData','post'],
    // 获取cloudApi配置信息
    ['kingdee/k3/cloudapi/config', 'getCloudApiData','get'],
    // 更新cloudApi配置
    ['kingdee/k3/cloudapi/config', 'updateCloudApiData','put'],
    // 删除cloudApi配置
    ['kingdee/k3/cloudapi/config', 'deleteCloudApiData','delete'],
    // 获取cloudApi配置列表
    ['kingdee/k3/cloudapi/configlist', 'getCloudApiDataList','get'],
    // 校验cloudApi配置信息
    ['kingdee/k3/cloudapi/check', 'checkCloudApi','post'],


    // k3日志模块
    // 获取日志列表
    ['kingdee/k3/log/list', 'getK3LogList','get'],
    // 获取日志详情
    ['kingdee/k3/log/detail', 'getK3LogDetail','get'],

    // 配置向导
    // 下载操作手册
    ['kingdee/k3/file/helpfile', 'getK3HelpFile','get'],
];
