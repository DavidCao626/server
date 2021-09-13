<?php
$moduleDir = dirname(__DIR__) . '/app/EofficeApp';
$modules   = get_current_module(); //获取当前路由模块


/**
 * 注册需要验证权限的路由
 */
$router->group([
    'namespace'  => 'App\EofficeApp',
    'middleware' => 'decodeParams|authCheck|ModulePermissionsCheck|menuPower|openApiMiddleware|syncWorkWeChat|verifyCsrfReferer',
    'prefix'     => '/api'], function ($router) use ($moduleDir, $modules) {
    register_routes($router, $moduleDir, $modules);
});
/**
 * 不需要权限验证的路由配置
 */
$noTokenApi = [
    'Auth'           => [
        ['auth/login', 'login', 'post'],
        ['auth/refresh', 'refresh', 'get'],
        ['auth/login/quick', 'quickLogin', 'post'],
        ['auth/login/theme', 'getLoginThemeAttribute'],
        ['auth/sms/verifycode/{phoneNumber}', 'getSmsVerifyCode'],
        ['auth/sso', 'singleSignOn', 'post'],
        ['auth/sso', 'singleSignOn'],
        ['auth/sso/registerinfo', 'ssoRegisterInfo'],
        ['auth/dynamic-code/sync', 'dynamicCodeSync', 'POST'],
        ['auth/get-login-auth-type', 'getLoginAuthType'],
        ['auth/check', 'check'],
        ['auth/logout', 'logout'],
        ['auth/qrcode/general', 'generalLoginQRCode'],
        ['auth/qrcode/sign-on', 'qrcodeSignOn', 'post'],
        ['auth/initinfo', 'getLoginInitInfo'],
        ['auth/password/modify', 'modifyPassword', 'post'],
        ['auth/check-dynamic-code-status', 'getDynamicCodeSystemParamStatus'],
        ['auth/captcha/{temp}', 'getCaptcha'],
        ['auth/dynamic-auth-open', 'getUserDynamicCodeAuthStatus'],
        ['auth/cas-login-out/{loginUserId}', 'casLoginOut'],
        ['auth/socket/check', 'checkToken'],
        ['auth/token', 'deleteToken', 'delete'],
        ['auth/check-token', 'checkTokenExist', 'post']
    ],
    'Attendance' => [
        //考勤外发验证
        ['attendance/validate/{type}', 'outSendValidate', 'post'],
    ],
    'User' => [
        // 用户快速注册二维码
        ['user/register/qrcode/{sign}', 'checkRegisterQrcode'],
        // 用户快速注册信息提交
        ['user/share/register', 'userShareRegister', 'post'],
        // 用户已登录信息
        ['user/socket/get-socket', 'getUserSocket'],
    ],
    'System'     => [
        'Security' => [
            ['security/upload/{module}', 'getModuleUpload'],
            ['security/system-title', 'getSystemTitleSetting'],
        ],
        'Address'  => [
            ['address/out/province', 'getIndexProvince'],
            ['address/out/city', 'getIndexCity'],
            ['address/out/city-district/{cityId}', 'getCityDistrict'],
            ['address/out/province/{provinceId}/city', 'getIndexProvinceCity'],
            ['address/out/province/{provinceId}/city/{cityId}', 'getProvinceCity'],
        ],
        'Prompt'   => [
            ['prompt/get-new-user-guide-flag/{route}', 'getNewUserGuideFlag'],
            ['prompt/set-new-user-guide-flag', 'setNewUserGuideFlag', 'post'],
        ]
    ],
    'Empower'        => [
        ['empower/pc-empower', 'getPcEmpower'], //获取PC端授权
        ['empower/mobile-empower', 'getMobileEmpower'], //获取手机端授权
        ['empower/get-system-version', 'getSystemVersion'], //获取系统版本
        ['empower/get-machine-code', 'getMachineCode'], //获取机器码
        ['empower/export', 'exportEmpower'], //导出授权
        ['empower/import', 'importEmpower', 'post'], //导入授权
        ['empower/case-platform', 'getEmpowerPlatform']// 检查是否是案例平台
    ],
    //微信 -- 后台接入及事件//
    'Weixin'         => [
        ['weixin/check', "weixinCheck"],
        ['weixin/weixin-token', 'getWeixinToken'], //获取系统版本
        ['weixin/wxsignpackage', 'weixinSignPackage'], //获取机器码
        ['weixin/weixin-move', 'weixinMove', 'post'], //导出授权
        ['weixin/weixin-qrcode', 'getBindingQRcode'], //生成二维码
        ['weixin/invoice/param', 'getInvoiceParam'], // 获取拉去微信电子发票列表所需参数
    ],
    //企业微信 -- 接入
    'WorkWechat'     => [
        ['work-wechat/workwechat-get', 'getWorkWechat'],
        ['work-wechat/workwechat-flag', "workwechatCheck"],
        ['work-wechat/workWechatsignpackage', 'workwechatSignPackage'], //获取js-sdk配置
        ['work-wechat/getSignatureAndConfig', 'getSignatureAndConfig','post'], //企业微信一些特殊的jsd 调用，agentConfig 需要的签名 和配置
        ['work-wechat/workwechat-move', 'workwechatMove', 'post'], //文件上传 下载到本地服务器
        ['work-wechat/workwechat-userTransfer', 'tranferUser'],
        ['work-wechat/workwechat-syncCallback', 'syncCallback','get'],   //企业微信同步通讯录回调地址--url验证
        ['work-wechat/workwechat-syncCallback', 'syncCallback','post'],   //企业微信同步通讯录回调地址--事件回调
        ['work-wechat/invoice/param', 'getInvoiceParam'], // 获取拉去微信电子发票列表所需参数
    ],
    //钉钉 -- 接入
    'Dgwork'       => [
        // 签名数据包
        ['dgwork/dgwork-signPackage', 'dgworkSignPackage', 'get'],
        // 附件上传至系统附件
        ['dgwork/dgwork-move', 'dgworkMove', 'post'],
    ],
    //政务钉钉
    'Dingtalk'       => [
        ['dingtalk/get-dingtalk', 'getDingtalk'], //处理后台验证：get
        ['dingtalk/dingtalk-clientPackage', 'dingtalkClientpackage'],
        ['dingtalk/dingtalk-attendance', 'dingtalkAttendance'],
        ['dingtalk/dingtalk-move', 'dingtalkMove', 'post'],
        ['dingtalk/dingtalkReceive', 'dingtalkCallbackReceive', 'post'],
    ],
    'Mobile'         => [
        ['mobile/initinfo', 'initInfo'],
        ['mobile/oa/unbind', 'unbindMobile', 'post'], // 手机客户端解除绑定OA用户,用于消息推送
    ],
    'Lang'           => [
        ['lang/effect-packages', 'getEffectLangPackages'],
        ['lang/package/default/locale', 'getDefaultLocale', 'get'],
        ['lang/file/{module}/{locale}', 'getLangFile'],
        ['lang/version', 'getLangVersion'],
    ],
    'Attachment'     => [
        ['attachment/atuh-file', 'attachmentAuthFile', 'post'],
        ['attachment/share/{shareToken}', 'loadShareAttachment'],
        ['attachment/path/migrate', 'migrateAttachmentPath', 'post'],
    ],
    'Menu'           => [
        ['menu/user-menu-list/{user_id}', 'getUseMenuList'], //登录手机版
    ],
    'XiaoE'          => [
        //二次开发字典
        ['xiao-e/{module}/dict/{method}', 'extendGetDictSource'],
        //二次开发数据验证
        ['xiao-e/{module}/check/{method}', 'extendCheck'],
        //二次开发数据初始化
        ['xiao-e/{module}/init/{method}', 'extendInitData'],
        ['xiao-e/dict/{method}', 'getDictSource'],
        ['xiao-e/check/{method}', 'check'],
        ['xiao-e/init/{method}', 'initData'],
        //验证api是否请求通
        ['xiao-e/system/test', 'testApi'],
    ],
    'Elastic' => [
        // 注册es菜单
        ['elastic/menu/register', 'registerElasticMenu', 'post'],
        // 获取es菜单注册信息
        ['elastic/menu/register/info', 'getElasticMenuUpdateInfo'],
        // 移除es菜单
        ['elastic/menu/remove', 'removeElasticMenu', 'delete'],
        // 获取es是否运行
        ['elastic/run/status', 'isElasticRunning'],
        // 开始数据迁移
        ['elastic/data/migration', 'migrationData', 'post'],
        // 获取数据迁移状态
        ['elastic/data/migration', 'getMigrationDetail'],
        // 测试专用
        ['elastic/data/test', 'dealTestData', 'post'],
    ],
    'ElectronicSign' => [
        // 回调合同状态
        ['electronic-sign/contract/status', 'changeContractStatus', 'post'],
        // 回调物理用印
        ['electronic-sign/seal-apply/status', 'changeSealApplyStatus', 'post'],
    ],
    'OpenApi' => [
        //
        ['open-api/get-token', 'openApiToken', 'post'],
        ['open-api/refresh-token', 'openApiRefreshToken', 'post'],
    ],
    'UnifiedMessage' => [
        ['unified-message/register-token','registerToken','post']
    ],
    'Portal' => [
        ['portal/eo/avatar/{userId}', 'getEofficeAvatar']
    ],
    'IntegrationCenter' => [
        ['integration-center/todo-push/test', 'todoPushTest','post']
    ],
    'PersonnelFiles' => [
        // 人事档案树, 不能加模块权限
        ['personnel-files/get-personnel-files-tree/{deptId}', 'getOrganizationPersonnelMembers', 'post'],
    ],
    'Home' => [
        ['home/boot-page-status', 'getBootPageStatus'],
        ['home/boot-page-status', 'setBootPageStatus', 'post'],
        ['home/scene/seeder', 'sceneSeeder', 'post'],
        ['home/scene/seeder/progress', 'sceneSeederProgress', 'get'],
        ['home/url/data', 'getUrlData'],
        ['home/version/check', 'checkSystemVersion'],// 检测系统版本
        ['home/system/update', 'updateSystem'], // 更新系统
        ['home/empty-scene/seeder', 'emptySceneSeeder', 'get'],
    ]
];

/**
 * 注册不需要权限验证的路由
 */
if (is_array($modules)) {
    $currentRoutes = isset($noTokenApi[$modules[0]]) ? (isset($noTokenApi[$modules[0]][$modules[1]]) ? $noTokenApi[$modules[0]][$modules[1]] : []) : [];
} else {
    $currentRoutes = isset($noTokenApi[$modules]) ? $noTokenApi[$modules] : [];
}
if (!empty($currentRoutes)) {
    $router->group(['namespace' => 'App\EofficeApp', 'middleware' => 'decodeParams', 'prefix' => '/api'], function ($router) use ($currentRoutes, $moduleDir, $modules) {
        register_routes($router, $moduleDir, $modules, $currentRoutes);
    });
}
/**
 * 特殊路由处理
 */
$router->group(['namespace' => 'App\EofficeApp', 'middleware' => 'decodeParams', 'prefix' => '/api'], function ($router) {
    //微信 -- 后台接入及事件//
    $router->get('weixin-access', 'Weixin\Controllers\WeixinController@weixinAccess'); //企业号接入（验证）
    $router->get('menu/export', 'Menu\Controllers\MenuController@exportMenu'); //导出菜单
    $router->get('weixin-auth', 'Weixin\Controllers\WeixinController@weixinAuth'); //处理后台接入
    $router->post('weixin-auth', 'Weixin\Controllers\WeixinController@weixinAuth'); //处理事件推送

    $router->get('workwechat-access', 'WorkWechat\Controllers\WorkWechatController@workwechatAccess'); //企业号接入（验证）
    $router->get('workwechat-auth', 'WorkWechat\Controllers\WorkWechatController@wechatAuth'); //处理后台验证：get
    $router->post('workwechat-auth', 'WorkWechat\Controllers\WorkWechatController@wechatAuth'); //处理事件推送:post

    $router->get('dingtalk-access', 'Dingtalk\Controllers\DingtalkController@dingtalkAccess'); //手机版钉钉接入（验证）
    $router->get('pc-dingtalk-access', 'Dingtalk\Controllers\DingtalkController@pcDingtalkAccess'); //PC版钉钉接入（验证）
    $router->get('dingtalk-index', 'Dingtalk\Controllers\DingtalkController@dingtalkIndex'); //工作台设置
    $router->get('import-export/export/download/{key}', 'ImportExport\Controllers\ImportExportController@download'); //下载附件
    $router->get('dingtalk-auth-work', 'Dingtalk\Controllers\DingtalkController@dingtalkAuthWork');
    $router->get('dingtalk-auth', 'Dingtalk\Controllers\DingtalkController@dingtalkAuth');
    $router->post('geocode-attendance', 'WorkWechat\Controllers\WorkWechatController@geocodeAttendance'); //高德地图
    $router->post('qywechat-nearby', 'Qyweixin\Controllers\QyweixinController@qywechatNearby'); //附近


    $router->get('wps/v1/3rd/file/info','Document\Controllers\DocumentController@getWPSFileInfo'); // 金山WPS云文档回调:获取文件元数据
    $router->post('wps/v1/3rd/user/info','Document\Controllers\DocumentController@getWPSUserInfo'); // 金山WPS云文档回调:获取用户信息
    $router->post('wps/v1/3rd/file/online','Document\Controllers\DocumentController@getWPSFileOnline'); // 金山WPS云文档回调:通知此文件目前有那些人正在协作
    $router->post('wps/v1/3rd/file/save','Document\Controllers\DocumentController@saveWPSFile'); // 金山WPS云文档回调:上传文件新版本
    $router->get('wps/v1/3rd/file/version/{version}','Document\Controllers\DocumentController@getWPSFileVersion'); // 金山WPS云文档回调:获取特定版本信息
    $router->put('wps/v1/3rd/file/rename','Document\Controllers\DocumentController@renameWPSFile'); // 金山WPS云文档回调:文件重命名
    $router->post('wps/v1/3rd/file/history','Document\Controllers\DocumentController@getWPSFileHistory'); // 金山WPS云文档回调:获取所有历史版本文件信息
    $router->post('wps/v1/3rd/onnotify','Document\Controllers\DocumentController@getWPSOnNotify'); // 金山WPS云文档回调:回调通知
    $router->get('wps/v1/3rd/file','Document\Controllers\DocumentController@getWPSFileDownload'); // 金山WPS云文档回调:文件下载地址
    $router->get('wps/v1/3rd/avatar','Document\Controllers\DocumentController@getWPSUserAvatar'); // 金山WPS云文档回调:获取用户头像

    $router->post('wps/pre/v1/convert/webhook','Attachment\Controllers\AttachmentController@wpsFileConvertWebhook'); // wps文档转换回调

    $router->get('dgwork-access', 'Dgwork\Controllers\DgworkController@dgworkAccess'); //政务钉钉手机端接入（验证）
    $router->get('pc-dgwork-access', 'Dgwork\Controllers\DgworkController@pcDgworkAccess'); //政务钉钉PC版接入（验证）
    $router->get('dgwork-auth', 'Dgwork\Controllers\DgworkController@dgworkAuth');// 获取code后跳转到系统

});

/**
 * 统一消息接入路由
 */
$router->group(['namespace' => 'App\EofficeApp', 'middleware' => 'AccessUnifiedMessage', 'prefix' => '/api'], function ($router) {
    //【消息数据】接收第三方消息数据
    $router->post('unified-message/message-data/accept', 'UnifiedMessage\Controllers\UnifiedMessageController@acceptMessageData');
    //【消息数据】删除第三方消息数据ByMessageId
    $router->post('unified-message/message-data/delete/id', 'UnifiedMessage\Controllers\UnifiedMessageController@deleteMessage');
    //【消息数据】删除第三方消息数据通过指定接收人
    $router->post('unified-message/message-data/delete/recipient', 'UnifiedMessage\Controllers\UnifiedMessageController@deleteDesignatedPersonMessage');
    //【消息数据】修改第三方消息数据状态（已处理）
    $router->post('unified-message/message-data/edit', 'UnifiedMessage\Controllers\UnifiedMessageController@editMessageState');
});

/**
 * 仅验证token不验证模块权限
 */
$router->group(['namespace' => 'App\EofficeApp', 'middleware' => 'authCheck', 'prefix' => '/api'], function ($router) {
    //获取客户联系人
    $router->post('work-wechat/get-customer-linkman', 'WorkWechat\Controllers\WorkWechatController@getCustomerLinkman');
    ////保存客户联系人
    $router->post('work-wechat/save-workwechat-with-customer', 'WorkWechat\Controllers\WorkWechatController@saveWorkWechatWithCustomer');
    //删除企业微信外部联系人和客户联系人之间的关联
    $router->post('work-wechat/delete-workwechat-with-customer', 'WorkWechat\Controllers\WorkWechatController@deleteWorkWechatWithCustomer');
    //获取当前登录用户企业微信外部联系人列表
    $router->post('work-wechat/get-workwechat-external-contact-list', 'WorkWechat\Controllers\WorkWechatController@getWorkWeChatExternalContactList');
    //通过企业微信客户群chatId获取客户联系人
    $router->post('work-wechat/get-customer', 'WorkWechat\Controllers\WorkWechatController@getCustomer');
    //保存企业微信客户群和客户关系
    $router->post('work-wechat/save-workwechat-group-with-customer', 'WorkWechat\Controllers\WorkWechatController@saveWorkWeChatGroupWithCustomer');
    //删除企业微信客户群绑定
    $router->post('work-wechat/delete-workwechat-group-with-customer', 'WorkWechat\Controllers\WorkWechatController@deleteWorkWechatGroupWithCustomer');
});
