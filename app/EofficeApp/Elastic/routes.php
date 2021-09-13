<?php
/**
 * Elastic Model Routes Register
 */
$routeConfig = [
    // 搜索接口
    ['elastic/search-website', 'searchAction'],
    // ===========================================================
    //                      配置相关
    // ===========================================================
    // 获取基本/运行配置信息
    ['elastic/config/detail', 'getConfigDetailAction'],
    // 获取全站搜索配置信息
    ['elastic/config/update-configuration/info', 'getGlobalSearchConfigAction'],
    // 获取指定别名对应的全部索引
    ['elastic/config/category/indices', 'getSearchIndicesByCategory'],
    // 别名切换接口
    ['elastic/config/switch-alias', 'switchSearchIndicesVersion', 'post'],
    // 更新运行配置接口
    ['elastic/config/run-update', 'updateRunConfigAction', 'post'],
    // 更新全站搜索配置接口
    ['elastic/config/update-configuration/update', 'updateGlobalSearchConfigAction', 'post'],
    // 获取mapping的analyzer中tokenizer
    ['elastic/config/schema-mapping/analyzer', 'getWordsSegmentation'],
    // 切换mapping的analyzer中tokenizer
    ['elastic/config/schema-mapping/analyzer', 'switchWordsSegmentation', 'post'],
    // ===========================================================
    //                      分词测试相关
    // ===========================================================
    // 获取指定分析器的分词
    ['elastic/token/analyzer', 'getTokensByAnalyzer'],

    // ===========================================================
    //                      索引更新相关
    // ===========================================================
    // 创建索引
    ['elastic/options/create/update-configuration', 'createGlobalSearchIndex', 'post'],
    // 获取索引更新进度
    ['elastic/options/update/process', 'getIndexUpdateProcess'],
    // 获取全部索引上次更新时间
    ['elastic/option/update/record', 'getIndexUpdateRecord'],

    // ===========================================================
    //                      ES服务相关
    // ===========================================================
    // 获取ES服务运行状态
    ['elastic/service/status', 'getESServiceStatus'],
    // ES服务 重安装/重启
    ['elastic/service/options', 'operateESService', 'post'],
    // ===========================================================
    //                      词典相关
    // ===========================================================
    // 获取扩展词典
    ['elastic/dic/extension', 'getExtensionWords'],
    // 配置扩展词典
    ['elastic/dic/extension', 'updateExtensionWords', 'post'],
    // 删除扩展词
    ['elastic/dic/extension', 'removeExtensionWords', 'delete'],
    // 还原已删除的扩展词
    ['elastic/dic/extension/restore', 'restoreExtensionWords', 'put'],
    // 同步扩展词
    ['elastic/dic/extension/sync', 'syncExtensionWords', 'post'],

    // 获取同义词词典
    ['elastic/dic/synonym', 'getSynonymWords'],
    // 配置同义词词典
    ['elastic/dic/synonym', 'updateSynonymWords', 'post'],
    // 删除同义词
    ['elastic/dic/synonym', 'removeSynonymWords', 'delete'],
    // 还原已删除的同义词
    ['elastic/dic/synonym/restore', 'restoreSynonymWords', 'put'],
    // 同步同义词
    ['elastic/dic/synonym/sync', 'syncSynonymWords', 'post'],

    // ===========================================================
    //                      日志相关
    // ===========================================================
    // 获取更新日志记录列表
    ['elastic/log/update-record/list', 'getUpdateRecordLogList'],
    // 获取操作日志记录列表
    ['elastic/log/operation-record/list', 'getOperationRecordLogList'],
    // ===========================================================
    //                      其他
    // ===========================================================
    // 模块测试
    ['elastic/function/test', 'esFunctionTest'],
    // 开始手动更新时判断队列是否开启
    ['elastic/queue/status/before-update', 'getQueueStatusBeforeUpdateByManual'],
    // 判断正在进行手动更新的队列是否因意外停止
    ['elastic/queue/status/in-update', 'getQueueStatusInUpdateByManual'],
    // 清除redis中更新记录
    ['elastic/queue/status/clear', 'clearUpdateProcess', 'delete'],
];