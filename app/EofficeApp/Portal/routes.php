<?php
$routeConfig = [
    ['portal', 'listPortal'], // 获取门户列表
    ['portal', 'addPortal', 'post', [101]], // 添加门户
    ['portal/global-search-item', 'getSearchItem'],//获取全局搜索选项
    ['portal/global-search-item', 'setSearchItem', 'post'],// 设置全家搜索选项
    ['portal/menus','listMenuPortal'], // 获取门户菜单
    ['portal/manage', 'listMangePortal', [101]], // 获取管理门户列表
    ['portal/sort', 'sortPortal', 'post', [101]], // 门户排序
    ['portal/import', 'getImportLayout', 'post'], // 门户导入
    ['portal/export/{portalId}', 'getExportLayout'], // 门户导出
    ['portal/menus/set','setMenuPortal', 'post', [101]], // 设置常用菜单
    ['portal/menus/get','getMenuPortal'], // 获取常用菜单
    ['portal/wechat/check','checkWeChat'], // 微信检查，全局的
    ['portal/report/type', 'getReportType'], // 获取报表列表，全局的
    ['portal/report/lists', 'getReportsByTypeId'], // 获取报表列表，全局的
    ['portal/navbar/set', 'setNavbar','post'], // 导航栏设置
    ['portal/navbar/get', 'getNavbar'], // 获取导航栏设置信息
    ['portal/home/init-data', 'getHomeInitData'], // 公共api
    ['portal/menus/setFavorite','setFavorite','post'],// 公共api
    ['portal/menus/cancelFavorite','cancelFavorite','post'],// 公共api
    ['portal/user-common-menu','setUserCommonMenu','post'],// 公共api
    ['portal/name/{portalId}', 'editPortalName', 'post', [101]], // 编辑门户名称
    ['portal/element-margin/{portalId}', 'getPortalElementMargin', 'get'], // 编辑门户名称
    ['portal/element-margin/{portalId}', 'editPortalElementMargin', 'post'], // 编辑门户名称
    ['portal/priv/{portalId}', 'editPortalPriv', 'post', [101]], // 编辑门户权限
    ['portal/{portalId}', 'getPortalInfo'], // 获取门户信息
    ['portal/{portalId}', 'deletePortal', 'delete', [101]], // 删除门户
    ['portal/{portalId}', 'editPortal', 'post', [101]], // 编辑门户
    ['portal/layout/set', 'setPortalLayout', 'post'], // 设置门户布局，全局的
    ['portal/layout/{portalId}', 'getPortalLayout'], // 获取门户布局， 全局的
    ['portal/priv-set/{portalId}', 'setPortalPriv', 'post', [101]], //设置门户权限
    ['portal/{portalId}/recover-default', 'recoverDefaultPortal'], // 恢复默认
    ['portal/{portalId}/unify', 'unifyPortal'], // 统一门户
    ['portal/{portalId}/set-default', 'setDefaultPortal'], // 设为默认门户
    ['portal/rss/parse', 'getRssContent'], // 获取 rss内容
    ['portal/avatar/set', 'setUserAvatar', 'post'], // 设置用户头像
    ['portal/logo/set', 'setSystemLogo', 'post'], // 设下系统logo
    ['portal/user/avatar/{userId}', 'getUserAvatar'], // 获取用户头像
//    ['portal/eo/avatar/{userId}', 'getEofficeAvatar'], // 获取用户头像
    ['portal/user/qr-code/{userId}', 'getUserQrCode'], // 获取用户二维码
    ['portal/icon/{portalId}','editPortalIcon','post', [101]], // 编辑门户图标
]; 