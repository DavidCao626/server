<?php
$routeConfig = [
    ['open-api/get/application/secret', 'getApplicationSecret'],
    ['open-api/set/application', 'setApplication', 'post'],
    ['open-api/refresh/application/secret/{id}', 'refreshApplicationSecret'],
    ['open-api/get/application/list', 'getApplicationList'],
    ['open-api/get/application/detail/{id}', 'getApplicationDetail'],
    ['open-api/register/application', 'registerApplication', 'post'],
    ['open-api/delete/application/{id}', 'deleteApplication', 'delete'],
    ['open-api/get/application-log', 'getApplicationLogList'],
    ['open-api/get/case', 'getOpenCase'],
    ['open-api/get/case-detail/{id}', 'getOneCase'],
];
