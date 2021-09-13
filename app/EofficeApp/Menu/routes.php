<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$routeConfig = [
    ['menu/menu-tree/{menu_parent}', 'getMenuTree'],
    ['menu/menu-info/{menu_id}', 'getMenuInfoByMenuId'], //获取菜单的设置详情
    ['menu/menu-add', 'setMenu', 'POST',[113]], //执行添加菜单
    ['menu/menu-edit/{menu_id}', 'editMenu', 'POST',[113]], //编辑菜单
    ['menu/menu-delete/{menu_id}', 'deleteMenu', 'DELETE',[113]],
    ['menu/menu-role/{role_id}', 'setRole', 'POST',[99]],
    ['menu/menu-list/{menu_parent}', 'getMenuList'],
    ['menu/user-menu/set', 'setUserMenu', 'POST'], //个性设置下设置菜单
    ['menu/menu-role/{role_id}', 'getRoleMenu',[99]],
    ['menu/menu-role-type/{role_id}/{type}', 'getRoleMenuByType',[99]],
    // 设置角色权限
    ['menu/menu-role-set/{role_id}', 'setRoleMenu', 'POST',[99]],
    ['menu/user-menu-order', 'setUserMenuOrder',[113]],
    ['menu/add-system-menu', 'addSystemMenu'], //外部执行
    ['menu/create-menu-list/{user_id}', 'getCreateMenuList'], //获取用户新建菜单列表 门户用到
    ['menu/menu-add/menu/custom', 'setCustomMenu', 'POST'], //添加个性自定义菜单
    ['menu/menu-edit/menu/custom', 'editCustomMenu', 'POST'], //编辑自定义菜单
    ['menu/menu-delete/menu/custom/{menu_id}', 'deleteCustomMenu','DELETE'], //删除自定义菜单
    ['menu/menu-delete/default/{user_id}', 'defaultMenu', 'DELETE'],
    ['menu/user-menus/sort/custom/{user_id}', 'sortMenu', 'POST'],
    ['menu/user-menus/config/get/custom', 'getMenuConfig'], //获取菜单配置
    ['menu/get-menu-search', 'getMenuInfo'], 
    ['menu/menu-tree-search','searchMenuTree',[113]],
    ['menu/menu-tree-search/by-array','getMenuByIdArray','POST'],
    ['menu/menu-judge-permission/{menu_id}','judgeMenuPermission'], //判断某个菜单权限
    ['menu/menu-sort','setMenuSort','POST'],
    ['menu/menu-sort','getMenuSort'],
    ['menu/sort-tree','getMenuSortTree'],
    ['menu/menu-sort-delete/{id}','deleteMenuSort','DELETE'],

];
