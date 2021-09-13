<?php
$routeConfig = [
    //获取假期列表
    ['vacation', 'getVacationList'],
    //创建假期
    ['vacation', 'createVacation', 'post',[435]],

    //获取假期设置
    ['vacation/setting/expired', 'getVacationSet',[437, 438]],
    //获取假期比例设置
    ['vacation/setting/scale', 'getVacationSet',[437, 438]],
    //获取转换历史
    ['vacation/setting/history', 'getVacationHistory',[437, 438]],
    //获取用户假期列表
    ['vacation/user', 'getUserVacationList',[436]],
    //获取假期数据
    ['vacation/{vacationId}', 'getVacationData',[435]],
    //排序假期
    ['vacation/sort', 'sortVacation', 'put',[435]],
    //假期常用设置
    ['vacation/setting/expired', 'modifySet', 'put',[438]],
    //假期比例设置
    ['vacation/setting/scale', 'modifySetScale', 'put',[438]],
    //编辑假期
    ['vacation/{vacationId}', 'modifyVacation', 'put',[435]],
    //删除假期
    ['vacation/{vacationId}', 'deleteVacation', 'delete',[435]],
    //批量设置用户假期
    ['vacation/user/multi', 'multiSetUserVacation', 'post',[436]],
    //我的假期
    ['vacation/{mine}/balance', 'getMineVacationData',[437]],
    ['vacation/{mine}/record', 'getMineVacationUsedRecord',[437]],
    ['vacation/{mine}/expire', 'getMineVacationExpireRecord',[437]],
    //获取用户假期
    ['vacation/user/{userId}', 'getUserVacationData',[436, 437]],
    //编辑用户假期
    ['vacation/user/{userId}', 'modifyUserVacation', 'put',[436]],
    //删除用户上周期假期
    ['vacation/user/last/{userId}', 'deleteUserLastVacation', 'delete',[436]],
    //获取用户某个假期剩余天数
    ['vacation/user/{userId}/{vacationId}', 'getUserVacationDays'],
    //根据假期名称获取用户某个假期剩余天数
    ['vacation/user/name/{userId}/{vacationName}', 'getUserVacationDaysByName',[436]],
];