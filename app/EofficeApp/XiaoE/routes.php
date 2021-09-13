<?php
$routeConfig = [
    ['xiao-e/{module}/boot/{method}', 'extendBoot', 'post'],//二次开发意图处理
    ['xiao-e/boot/{method}', 'boot', 'post'],//标准版意图处理
    ['xiao-e/system/authorise', 'authorise', 'post', [450]],//获取appId和appSecret
    ['xiao-e/system/intention/config', 'getConfigIntention', 'get', [450]],//获取需要配置的意图
    ['xiao-e/system/intention/{key}', 'getIntenTionDetail', 'get', [450]],//获取某意图的具体配置参数
    ['xiao-e/system/intention', 'updateIntentionParams', 'post', [450]],//更新意图params
    ['xiao-e/system/secret', 'getSecretInfo', [450]],//获取秘钥信息
    ['xiao-e/system/secret', 'updateSecretInfo', 'post', [450]],//更新秘钥信息
    ['xiao-e/system/dict/sync', 'syncDictData', [450]],//同步数据字典
    ['xiao-e/system/monitoring', 'getMonitoringList', [450]],//监控信息列表
    ['xiao-e/system/monitoring/chart', 'getMonitoringChartConfig', [450]],//监控信息echart配置
];