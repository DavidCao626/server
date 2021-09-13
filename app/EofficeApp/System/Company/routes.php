<?php
$routeConfig = [
    //获取公司信息
    ['company', 'getIndexCompany'],
    //新建公司信息
    ['company', 'createCompany', 'post', [96]],
    //修改公司信息
    ['company/{id}', 'editCompany', 'put', [96]]
];