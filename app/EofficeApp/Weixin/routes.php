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
  | 模块迁移至集成中心  接口权限集成父级菜单id
 */

$routeConfig = [
        ['weixin/connect', 'connectWeixinToken',[499]],
        ['weixin/weixin-token/set', 'setWeixinToken', 'post',[499]],
        ['weixin/weixin-menu/add', 'addMenu', 'post',[499]], //增加
        ['weixin/weixin-menu/{id}', 'editMenu', 'put',[499]], //编辑
        ['weixin/weixin-menu/{id}', 'deleteMenu', 'delete',[499]], //删除
        ['weixin/weixin-junior/add', 'addJuniorMenu', 'post',[499]],
        ['weixin/update-menu', 'updateMenu',[499]], //更新菜单
        ['weixin/weixin-menu-check/{node}', 'checkMenu',[499]], //检查菜单
        ['weixin/weixin-menu-tree/{menuParent}', 'weixinMenuTree',[499]],
        ['weixin/weixin-menu/{id}', 'getMenuByMenuId', 'get',[499]], //weixin-menu-junior
        ['weixin/weixin-follow', 'getWeixinUserFollowList',[499]], //关注用户
        ['weixin/weixin-synchronize', 'synchronizeUser',[499]], //同步用户
        ['weixin/weixin-bind', 'getWeixinUserBindList',[499]], //绑定
        ['weixin/unwrap-weixin/{userId}', 'unwrapWeixin',[499]], //解绑用户
        ['weixin/weixin-truncate','clearWeixinToken','delete',[499]],
        ['weixin/weixin-menu-list',"weixinMenuList",[499]],	
        // ['weixin/weixin-qrcode/{user_id}',"getBindingQRcode",[432]], //生成二维码
        ['weixin/login','weixinLogin','post',[499]],//微信公众号首次登陆oa
        ['weixin/get_weixin_ip','downWeiXinIp','get'],//获取微信地址
        //自动回复
        ['weixin/weixin-reply/set', 'setWeixinReply', 'post',[499]],
        ['weixin/weixin-reply/get', 'getWeixinReply', 'get',[499]],
        ['weixin/reply-template/get', 'getReplyTemplateList', 'get',[499]],
        ['weixin/reply-template/get/{id}', 'getReplyTemplate', 'get',[499]],
        ['weixin/reply-template/set', 'setReplyTemplate', 'post',[499]],
        ['weixin/reply-template/delete/{id}', 'deleteReplyTemplate', 'delete',[499]],
];
