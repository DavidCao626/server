<?php

$routeConfig = [
    // 获取政务钉钉的配置信息
    ['dgwork/get-dgwork-config', 'getDgworkConfig', [296]],
    // 保存政务钉钉的配置信息
    ['dgwork/save-dgwork-config', 'saveDgworkConfig', 'post', [296]],
    // 校验政务钉钉的配置信息
    ['dgwork/check-dgwork-config', 'checkDgworkConfig', 'post',[296]],
    // 清空政务钉钉的配置信息
    ['dgwork/delete-dgwork-config', 'truncateDgwork', 'delete', [296]],
    // 获取关联用户的列表
    ['dgwork/dgwork-userList', 'getDgworkUserList', [296]],
    // 新增关联用户的绑定
    ['dgwork/add-dgwork-user', 'addDgworkUserBind', 'post', [296]],
    // 删除关联用户的绑定
    ['dgwork/delete-dgwork-user', 'deleteDgworkUserBind', 'delete', [296]],
    // 删除关联用户的绑定
    ['dgwork/get-dgwork-userInfo', 'getDgworkUserInfo', 'get', [296]],
    // 自动关联用户绑定
    ['dgwork/auto-bind-user', 'autoBindUser', 'get', [296]],
    // 获取浙政钉埋点配置
    ['dgwork/zj-point', 'getZjPoint', 'get', [296]],


];
