<?php
// 回调地址
// http://wwfeoding.weaver.cn/eoffice10_dev/server/ext/test/testDing.php
// 注册回调URL
// https://oapi.dingtalk.com/call_back/register_call_back?access_token=ACCESS_TOKEN
require __DIR__ . '/../../bootstrap/app.php';

$dingTalk = app("App\Utils\DingTalk");
// var_dump($dingTalk->getAccessToken());
$token = $dingTalk->getAccessToken();
// "call_back_tag": ["user_add_org", "user_modify_org", "user_leave_org"],
// "token": "123456",
// "aes_key": "1234567890123456789012345678901234567890123",
// "url":"http://test001.vaiwan.com/eventreceive"
$aes_key="123456789012345678901234567890aq";
$aes_key_encode=base64_encode($aes_key);
$aes_key_encode=substr($aes_key_encode,0,-1);//去掉= 号
$param = [
    'call_back_tag' => [
    	'check_in',
    	'user_add_org',
        'user_modify_org',
        'user_leave_org',
        'org_dept_create',
        'org_dept_modify',
        'org_dept_remove',
        'label_user_change',
        'label_conf_add',
        'label_conf_del',
        'label_conf_modify'
	],
    'token'         => '123456',
    // 'aes_key'       => '1234567890123456789012345678901234567890123',
    'aes_key'       => $aes_key_encode,
    'url'           => 'http://wwfeoding.weaver.cn/eoffice10_dev/server/ext/dingtalk_callback/receive.php',
];
$url  = 'https://oapi.dingtalk.com/call_back/register_call_back?access_token=' . $token;
$json = getHttps($url, json_encode($param));
// file_put_contents('./responce.txt', json_encode($json), FILE_APPEND);
var_dump($json);
exit;