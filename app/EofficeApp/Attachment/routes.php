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
    ['attachment/index/{attachmentId}', 'loadAttachment'],
    ['attachment/down-zip', 'downZip', "POST"],
    ['attachment/thumb/{attachmentId}', 'getThumbAttach'],
    ['attachment/online-read','getOnlineReadOption'],//TODO 附件组件、文档详情、集尘管理用到，暂不控制
    ['attachment/view','transToHtmlView'],
    ['attachment/own', "getAttachmentByUserId"],
    ['attachment/remove', "removeAttachment", "post"],
    ['attachment/base64','base64Attachment','post'],
    ['attachment/base64/pdf','base64AttachmentPdf','post'],
    ['attachment/base64/muti','base64Attachments','post'],
    ['attachment/upload','upload','post'],//上传附件
    ['attachment/list', 'getAttachments', "post"],//获取附件列表
    ['attachment/wps/npapi/init', 'wpsNpApiInit', 'post'],// npapi插件初始化
    ['attachment/wps/npapi/empty/cache', 'emptyTargetCacheDir', 'post'], // 清空npapi初始化产生的缓存
    ['attachment/wps/npapi/init/info', 'wpsNpApiInitInfo'], // 获取npapi初始化信息
    ['attachment/wps/npapi/generate/attachment-id', 'generateAttachmentId', 'post'], // 生成创建文档使用的附件id
    ['attachment/online-read','saveOnlineReadOption', 'post'],
    ['attachment/replace','attachmentReplace', 'post'],
    ['attachment/copy','copy','post'],//附件复制
    // 20210507-丁鹏-集成iweboffice2015备注：前端，没有地方调用此路由;(server\app\EofficeApp\IWebOffice\routes.php i-web-office/set内，调用了此路由)
    ['attachment/print/{attachmentId}','getPrintPower'],
    ['attachment/detail/{attachmentId}','getDetail'],
    ['attachment/own-delete','deleteAttachmentRel','post'],
    ['attachment/read/{attachmentId}','getAttachmentContent'],
    // ===========================================================
    //                      wps云文档
    // ===========================================================
    // 获取wps访问地址
    ['attachment/wps/view', 'getWpsTransHtml'],
    // 创建word/excel新文档时创建附件
    ['attachment/wps/new/id', 'createWpsAttachmentId'],
    // ===========================================================
    //                      wps文档转换
    // ===========================================================
    // wps文档转换配置
    ['attachment/wps/convert/set', 'setWpsFileConvertConfig', 'post'],
    // 获取wps文档转换配置
    ['attachment/wps/convert/config', 'getWpsFileConvertConfig'],
    // 文件类型转换
    ['attachment/convert/deal','convertFile', 'post'],
    // 获取文件类型转换进度
    ['attachment/convert/process', "getConvertProgress"],

    ['attachment/image/ocr', 'ocr', 'post'], // ocr 识别
    ['attachment/ocr/config', 'getOcrConfig'], // 判断 ocr 配置是否开启
    ['attachment/compress/{attachmentId}/{size}', 'getCompressImage'],//获取压缩图片
];
