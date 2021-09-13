<?php
require __DIR__ . '/../../bootstrap/app.php';

$dingTalk = app("App\Utils\DingTalk");
$token = $dingTalk->getAccessToken();

// 查询事件回调列表
function getCallbackList($token){
	$url = "https://oapi.dingtalk.com/call_back/get_call_back?access_token=$token";
	$res = getHttps($url);
	var_dump($res);
}
// 删除事件回调接口
function deleteCallback($token){
	$url = "https://oapi.dingtalk.com/call_back/delete_call_back?access_token=$token";
	$res = getHttps($url);
	var_dump($res);
}

// getCallbackList($token);
deleteCallback($token);