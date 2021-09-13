<?php
$routeConfig = [
    ['mobile/user/privacy-protocal/is-agree', 'isAgreePrivacyProtocal', 'get'],// 是否已经同意隐私协议
    ['mobile/user/privacy-protocal/agree', 'agreePrivacyProtocal', 'post'],// 同意隐私协议
    ['mobile/mac-address', 'getMobileMac', 'get'],
    ['mobile/app/update/check', 'checkAppVersion', 'get'],
    ['mobile/oa/bind', 'bindMobile', 'post'], // 手机客户端绑定OA用户,用于消息推送
    ['mobile/file/upload', 'upload', 'post'], // 手机端附件上传
    ['mobile/attachment/thumbs', 'getAttachmentThumbs'], // 获取附件缩略图
    ['mobile/oa/redirect-url', 'getRedirectUrl'], // 获取消息跳转链接
    ['mobile/app/qrcode', 'generalUrlQRCode'], // 生成app下载二维码
    ['mobile/qrcodeinfo/set', 'setQRCodeLoginInfo', 'post'], // 设置扫码登录信息
    ['mobile/user/bind', 'bindUserMobile', 'post'], // 手机mac地址绑定用户
    ['mobile/user/unbind', 'unbindUserMobile', 'post'],// 手机mac地址绑定用户解除
    ['mobile/user/bind/check', 'bindUserMobileCheck', 'get'], // 手机mac地址是否绑定用户检查
    ['mobile/app/logo/set', 'setAppLoginLogo', 'post', [193]], // 设置app登录页logo
    ['mobile/app/logo/get', 'getAppLoginLogoAttachmentId', 'get', [193]], // 获取app登录页logo
    ['mobile/app/mobile-bind-check', 'setMobileCodeCheckFlag', 'post', [194]], // 启用绑定手机
    ['mobile/app/mobile-bind-check', 'getMobileCodeCheckFlag', 'get', [194]], // 获取是否启用绑定手机标志。
    ['mobile/app/mobile-bind', 'getUserBindMobileList', 'get', [194]], // 获取已绑定手机列表
    ['mobile/app/mobile-sign', 'saveMobileSign', 'post', [194]], // 设置手机签批
    ['mobile/app/mobile-sign', 'getMobileSign', 'get', [194]], // 获取手机签批信息
    ['mobile/app/user-type', 'setUserType','post', [194]],
    ['mobile/app/user-type', 'getUserType','get', [194]],
    ['mobile/navbar/children/{parentId}', 'getNavbarChildren', 'get'],
    ['mobile/navbar', 'addNavbar', 'post'],
    ['mobile/navbar/sort/{parentId}', 'sortNavbar', 'post'],
    ['mobile/navbar/{navbarId}', 'editNavbar', 'post'],
    ['mobile/navbar/{navbarId}', 'deleteNavbar', 'delete'],
    ['mobile/navbar/{navbarId}', 'getNavbarDetail', 'get'],
    ['mobile/font-size/{userId}', 'getFontSize', 'get'],
    ['mobile/font-size/{userId}', 'setFontSize', 'post']
];
 