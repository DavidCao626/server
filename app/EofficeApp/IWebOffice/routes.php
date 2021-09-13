<?php
$routeConfig = [
    ['i-web-office/main', 'main','post'], // iweboffice2003使用的获取文档的路由
	['i-web-office/idoc/main', 'mainIdoc','post'], // iweboffice2015使用的获取文档的路由
	['i-web-office/download/{attachmentId}', 'download'],
	['i-web-office/set','getContentSet'],
    ['i-web-office/file-exists','fileExists'],
    // 获取金格签章样式
    ['i-web-office/signature/style','getSignatureStyle'],
    // 设置金格签章样式
    ['i-web-office/signature/style/update','setSignatureStyle', 'post'],
];