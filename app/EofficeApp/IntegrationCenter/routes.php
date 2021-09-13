<?php
// 集成中心，路由配置，主要是公共路由，专属路由一般分到各个模块中了
$routeConfig = [
    // 为集成中心看板提供列表数据
    ['integration-center/board', 'getBoard', 'get'],
    // 为“凭证配置”选择器提供数据的路由(定义流程-外发到凭证配置时用到)
    ['integration-center/voucher-intergration-config', 'getVocherIntergrationConfig', 'get'],
    // 获取凭证集成的基本信息配置，要传入凭证类型(用于U8/K3看板内，基本配置页面信息获取)
    ['integration-center/voucher-intergration/base-info/{vocheType}', 'getVocherIntergrationBaseInfo', 'get'],
    // 保存凭证集成的基本信息配置--主要是数据库
    ['integration-center/voucher-intergration/base-info/{baseId}', 'saveVocherIntergrationBaseInfo', 'put'],
    // 集成中心-第三方接口集成-提供列表数据
    // 下面的../third-party-interface/..路由type可选值['ocr:OCR识别','videoconference:视频会议']
    ['integration-center/third-party-interface/{type}','getThirdPartyInterfaceList', 'get'],
    // 集成中心-第三方接口-新增
    ['integration-center/third-party-interface/{type}','addThirdPartyInterface', 'post'],
    // 集成中心-第三方接口-编辑
    ['integration-center/third-party-interface/{type}/{configId}','editThirdPartyInterface', 'post'],
    // 集成中心-第三方接口-删除
    ['integration-center/third-party-interface/{type}/{configId}','deleteThirdPartyInterface', 'delete'],
    // 获取集成中心-第三方接口
    ['integration-center/third-party-interface/{type}/detail/where','getThirdPartyInterfaceByWhere', 'get'],
    // 集成中心-第三方接口-获取详情
    ['integration-center/third-party-interface/{type}/{configId}','getThirdPartyInterfaceInfo', 'get'],
    // 获取集成中心-第三方接口(1期内容，后面由袁梦林改成走公共抽象路由的)
    // 20200414-dingpeng-去掉这路由，这路由的功能，被[集成中心-第三方接口-获取详情]覆盖了
    // ['integration-center/third-party-interface/ocr/{ocrId}','getThirdPartyInterfaceOcr', 'get'],
    //待办推送列表
    ['integration-center/todo-push-system-list','todoPushSystemList', 'get'],
    //待办推送系统详情
    ['integration-center/todo-push-system-detail/{id}','todoPushSystemDetail', 'get'],
    //保存
    ['integration-center/save-todo-push-system-setting','saveTodoPushSystemSetting', 'post'],
    ['integration-center/todoPush','todoPush', 'post'],
    ['integration-center/delete-todo-push-system/{id}','deleteTodoPushSystem', 'get'],
    // 下载文档 关于推送数据说明
    ['integration-center/get-doc','getDoc', 'get'],
    // 下载 iweboffice控件支持的环境 文档，传入文档名
    ['integration-center/online-read/iweboffice-support-document/{document}','getIwebofficeSupportDocument'],
];
