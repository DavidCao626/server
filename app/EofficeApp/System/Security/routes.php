<?php
$routeConfig = [
    //获取白名单
    ['security/white-address', 'getWhiteAddress',[106]],
    //新增白名单
    ['security/white-address', 'addWhiteAddress', 'post',[106]],
    //删除白名单
    ['security/white-address/{whiteAddressId}', 'deleteAWhiteAddress', 'delete',[106]],
    //编辑白名单
    ['security/white-address/{whiteAddressId}', 'modifyAWhiteAddress', 'put',[106]],
    //获取上传设置列表
    ['security/upload', 'getModuleUploadList',[106]],
    ['security/set-editor', 'setSecurityEditor', 'post',[106]],
    //获取上传附件名称
    ['security/upload-file-rule/{id}', 'getUploadFileRule',[106]],
    //编辑上传设置
    ['security/upload/{id}', 'modifyModuleUpload', 'put',[106]],
    //编辑上传附件名称
    ['security/upload-file-rule/{id}', 'modifyUploadFileRule', 'put',[106]],
    //系统性能安全恢复初始设置
    ['security/capability/{paramKey}', 'resetCapabilityOption', 'put',[106, 111]],
    //获取system_params表参数
    ['security/system-params/{paramKey}', 'getParamsData',[106, 111]],
    //编辑system_params表参数
    ['security/system-params/{paramKey}', 'modifyParamsData','put',[106, 111]],
    //设置系统标题
    ['security/system-title', 'modifySystemTitleSetting', 'post', [117]],
    //获取系统标题。走“系统性能安全设置”的api
    ['security/system-title', 'getSystemTitleSetting'],
    //获取系统登录安全等级
    ['security/security-level', 'getSecurityLevel'],
    //获取系统安全选项
    ['security/{params}', 'getSecurityOption'],
    //编辑系统安全选项
    ['security/{params}', 'modifySecurityOption', 'put',[106, 111]],//未添加菜单id接口，在此性能安全设置菜单中未找到使用地方，可能其他地方在调用
    // 【水印】获取水印设置数据
    ['security/watermark-set/{type}', 'getWatermarkSettingInfo'],
    // 【水印】获取水印预览页面html
    ['security/watermark/priview-html', 'getWatermarkPriviewHtml'],
    // 【水印】保存水印设置
    ['security/watermark-set', 'saveWatermarkSetting', 'post'],
    // 获取系统参数
    ['security/batch/params', 'getSystemParams'],
    ['security/batch/params', 'setSystemParams', 'post']
];