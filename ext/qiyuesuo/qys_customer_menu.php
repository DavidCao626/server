<?php
require __DIR__ . '/../../bootstrap/app.php';

$qysServerUrl = '';// http://116.62.192.247:9180   http://privapp.qiyuesuo.me

$qysObject = app('App\EofficeApp\ElectronicSign\Services\ElectronicSignService');
if (!$qysServerUrl) {
    $qysServer = $qysObject->getServerDetail(0, ['serverType' => 'private']);
    if ($qysServer && !isset($qysServer['code']) && $serverUrl = $qysServer->serverUrl){
        $qysServerUrl = str_replace('9182', '9180', $serverUrl);
    }
} else {
    $qysServer = $qysObject->getServerDetail(0, ['serverType' => 'private', 'serverUrl' => str_replace('9180', '9182', $qysServerUrl)]);
}
$qysServer = $qysServer ? $qysServer->toArray() : [];
$userId = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : "";
$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : "";
$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : "";
if (!$token) {
    echo "获取用户token失败";die;
}
$qystoken = $qysObject->getQysSignUserTokenString($userId, '', $qysServer, $token);
if (!$qysServerUrl) {
    echo "契约锁服务地址异常，请确认！";
} else {
    if (is_array($qystoken)) {
        if(isset($qystoken['code']) && isset($qystoken['code']['0']) && $qystoken['code']['0'] == '0x093520') {
            echo "当前用户没有设置手机号码，无法使用印控中心！";
        } else {
            // 出错了
            header('Location:'.$qysServerUrl.'/login?hide_menu=true');
        }
    } else {
        $pageRoute = '';
        if($page == "user_info") {
            // 个人中心
            $pageRoute .= "usercenter/info";
        } else if($page == "has_sign" || $page == "todo_sign") {
            // 已签文件 / 待签文件
            $pageRoute .= "contractlist";
        } else if($page == "seal_detail" || $page == "seal_manage") {
            // 用章明细 / 印章管理
            $pageRoute .= "seal";
        } else if($page == "launch") {
            $pageRoute .= "launch/contract";
        }
        $signUrl = $qysServerUrl.'/'.$pageRoute.'?qystoken=' . $qystoken;
        // 20190604-单点登录跳转过来的时候，后面再跟一个platform=EOFFICE的固定值，用来在ec-契约锁中标识是来自eoffice
        $signUrl .= "&platform=EOFFICE";
        // &hide_menu=true 隐藏签署页面的菜单
        $signUrl .= "&hide_menu=true";
        // 拼接筛选参数
        if($page == "has_sign") {
            // 已签文件
            $signUrl .= "&status=COMPLETE";
        } else if($page == "todo_sign") {
            // 待签文件
            $signUrl .= "&status=REQUIRED";
        }
//         echo $signUrl;
//         exit();
        header('Location: '.$signUrl);
        // header('Location: http://www.baidu.com');
    }
}
