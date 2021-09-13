<?php

$routeConfig = [
    ['log-center/track', 'userActivityTrack'],
    ['log-center/rank', 'moduleRank'],
    ['log-center/detail', 'getLogDetail'],
    ['log-center/history', 'getLogChange'],
    ['log-center/logs', 'getLogList', 'get'], // 获取日志列表
    ['log-center/data-logs', 'getOneDataLogs', 'get'],
    ['log-center/statistics', 'getLogStatistics'],
    ['log-center/get-log-tree/{userId}', 'getSubordinate'],
    ['log-center/category', 'getModuleCategory'],
    ['log-center/operations', 'getOneCategoryOperations'],
    ['log-center/module', 'getAllModule']
];
