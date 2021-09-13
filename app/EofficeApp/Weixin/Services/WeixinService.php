<?php

namespace App\EofficeApp\Weixin\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Redis;

/**
 * weixin服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class WeixinService extends BaseService
{

    /** @var object $weixinUserRepository 微信用户资源库 */
    private $weixinUserRepository;

    /** @var object $weixinMenuRepository 微信菜单资源库 */
    private $weixinMenuRepository;

    /** @var object $weixinTokenRepository 微信token资源库 */
    private $weixinTokenRepository;

    /** @var object $weixin 微信资源库 */
    private $weixin;

    /** @var object $weixin 微信用户信息资源库 */
    private $weixinUserInfoRepository;

    /*     * $userRepository* */
    private $userRepository;

    /** 用户 信息 * */
    private $userService;

    /** 授权注入 * */
    private $authService;

    /** 消息提醒设置 * */
    private $remindsRepository;

    /*     * @weixin* */
    private $userMenuService;
    private $empowerService;
    /**
     * @var object wechat 微信自动回复资源库
     */
    private $weixinReplyRepository;
    private $weixinReplyTemplateRepository;
    private $attachmentService;
    private $unifiedMessageService;

    public function __construct()
    {
        $this->weixinUserRepository = "App\EofficeApp\Weixin\Repositories\WeixinUserRepository";
        $this->weixinMenuRepository = "App\EofficeApp\Weixin\Repositories\WeixinMenuRepository";
        $this->weixinTokenRepository = "App\EofficeApp\Weixin\Repositories\WeixinTokenRepository";
        $this->weixinUserInfoRepository = "App\EofficeApp\Weixin\Repositories\WeixinUserInfoRepository";
        $this->weixinReplyRepository = "App\EofficeApp\Weixin\Repositories\WeixinReplyRepository";
        $this->weixinReplyTemplateRepository = "App\EofficeApp\Weixin\Repositories\WeixinReplyTemplateRepository";
        $this->weixin = "App\Utils\Weixin";
        $this->userRepository = "App\EofficeApp\User\Repositories\UserRepository";
        $this->userService = "App\EofficeApp\User\Services\UserService";
        $this->authService = "App\EofficeApp\Auth\Services\AuthService";
        $this->remindsRepository = "App\EofficeApp\System\Remind\Repositories\RemindsRepository";
        $this->userMenuService = "App\EofficeApp\Menu\Services\UserMenuService";
        $this->empowerService = "App\EofficeApp\Empower\Services\EmpowerService";
        $this->attachmentService = "App\EofficeApp\Attachment\Services\AttachmentService";
        $this->unifiedMessageService = 'App\EofficeApp\UnifiedMessage\Services\UnifiedMessageService';
    }

    //自动更新access_token
    public function getAccessToken()
    {

        return app($this->weixin)->getAccessToken();
    }

    //连接测试
    public function connectWeixinToken($data)
    {

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$data['appid']}&secret={$data['appsecret']}";
        $weixinConnect = getHttps($url);
        if (!$weixinConnect) {
            return ['code' => ['0x033003', 'weixin']];
        }
        if (isset($data['domain'])) {
            $domain = $data['domain'];
            if (substr($domain, -1) == "/") {
                return ['code' => ['0x000116', 'weixin']];
            }
        }

        $connectList = json_decode($weixinConnect, true);
        if (isset($connectList['errcode']) && $connectList['errcode']) {
            $code = $connectList['errcode'];
            return ['code' => ["$code", 'weixin']];
        }

        $access_token = $connectList['access_token'];
        $token_time = time();

        //更新
        app($this->weixinTokenRepository)->updateData(["access_token" => $access_token, "token_time" => $token_time], ["appid" => $data['appid']]);
        return $weixinConnect;
    }

    // 公众号设置
    public function setWeixinToken($data)
    {
        //测试链接
        $connectList = $this->connectWeixinToken($data);

        if (isset($connectList["code"]) && $connectList["code"]) {
            return $connectList;
        }
        $connectList = json_decode($connectList, true);
        $data['access_token'] = $connectList['access_token'];
        $data['token_time'] = time();

        //获取access_token [860a95c0ad3df8cc8b94d6e3afda5fbb] ->手机注册申请的key
        //获取经纬度
        //$location = address_to_geocode($address);
        $location = address_to_geocode($data['address']);
        if (!$location) {
            return ['code' => ['0x033002', 'weixin']];
        }
        $data["lng"] = $location['long'];
        $data["lat"] = $location['lat'];

        $this->clearWeixinToken();

        $weixinData = array_intersect_key($data, array_flip(app($this->weixinTokenRepository)->getTableColumns()));
        $weixinData['create_time'] = time();
        $wxData = app($this->weixinTokenRepository)->insertData($weixinData);
        if ($wxData) {
            //插入、更新reminds
            $remids = app($this->remindsRepository)->checkReminds("wechat");
            if (!$remids) {
                app($this->remindsRepository)->insertData(["id" => 2, "reminds" => "wechat"]);
            }
        }

        return $wxData;
    }

    //清空
    public function clearWeixinToken()
    {

        $wxData = app($this->weixinTokenRepository)->clearWeixinToken();

        if ($wxData) {
            $where = [
                "reminds" => ["wechat"],
            ];
            app($this->remindsRepository)->deleteByWhere($where);
        }

        return $wxData ? 1 : ['code' => ['0x033001', 'weixin']];
    }

    //获取token设置信息

    public function getWeixinToken()
    {
        return app($this->weixinTokenRepository)->getWeixinToken();
    }

    //增加菜单
    public function addMenu($data)
    {
        //获取配置的域名
        $custom_value = isset($data["custom_value"]) ? $data["custom_value"] : 0;
        //获取该有的配置信息
        if ($custom_value == 0) {
            $data["fixation_value"] = $data["value"]; //PC展示效果用 该参数不计算后面的逻辑
            $configData = $this->getWeixinToken();
            if (!$configData) {
                return ['code' => ['0x033012', 'weixin']];
            }
            $domain = $configData->domain;
            $tempUrl = $domain . "/eoffice10/server/public/api/weixin-access?type=" . $data["value"]; //weixin-access

            $data['value'] = $tempUrl;
            $data["custom_value"] = 0;
        } else {
            $data["custom_value"] = 1;
            $data["fixation_value"] = "";
        }

        $info = app($this->weixinMenuRepository)->getDataByWhere(["node" => ["p", "="]]);
        if (count($info) >= 3) {
            return ['code' => ['0x033004', 'weixin']];
        }
        $data["node"] = "p";
        if (isset($data['value']) && !empty($data['value'])) {
            if (strtolower(substr($data['value'], 0, 4)) == 'http') {
                $data["type"] = "view";
            } else {
                $data["type"] = "click";
            }
        } else {
            $data["type"] = "click";
        }
        $weixinData = array_intersect_key($data, array_flip(app($this->weixinMenuRepository)->getTableColumns()));
        $res = app($this->weixinMenuRepository)->insertData($weixinData);

        return $res->id;
    }

    //增加下级菜单
    public function addJuniorMenu($data)
    {

        $custom_value = isset($data["custom_value"]) ? $data["custom_value"] : 0;
        //获取该有的配置信息
        if ($custom_value == 0) {
            $data["fixation_value"] = $data["value"]; //PC展示效果用 该参数不计算后面的逻辑
            $configData = $this->getWeixinToken();
            if (!$configData) {
                return ['code' => ['0x033012', 'weixin']];
            }
            $domain = $configData->domain;
            $tempUrl = $domain . "/eoffice10/server/public/api/weixin-access?type=" . $data["value"]; //weixin-access
            $data['value'] = $tempUrl;
            $data["custom_value"] = 0;
        } else {
            $data["custom_value"] = 1;
            $data["fixation_value"] = "";
        }

        $info = app($this->weixinMenuRepository)->getDataByWhere(["node" => [$data["node"], "="]]);
        if (count($info) >= 5) {
            return ['code' => ['0x033005', 'weixin']];
        }
        if (isset($data['value']) && !empty($data['value'])) {
            if (strtolower(substr($data['value'], 0, 4)) == 'http') {
                $data["type"] = "view";
            } else {
                $data["type"] = "click";
            }

        } else {
            $data["type"] = "click";
        }

        $weixinData = array_intersect_key($data, array_flip(app($this->weixinMenuRepository)->getTableColumns()));
        $res = app($this->weixinMenuRepository)->insertData($weixinData);

        return $res->id;
    }

    //编辑菜单
    public function editMenu($id, $data)
    {

        $custom_value = isset($data["custom_value"]) ? $data["custom_value"] : 0;
        //获取该有的配置信息
        if ($custom_value == 0) {
            $data["fixation_value"] = $data["value"]; //PC展示效果用 该参数不计算后面的逻辑
            $configData = $this->getWeixinToken();
            if (!$configData) {
                return ['code' => ['0x033012', 'weixin']];
            }
            $domain = $configData->domain;
            $tempUrl = $domain . "/eoffice10/server/public/api/weixin-access?type=" . $data["value"]; //weixin-access
            $data['value'] = $tempUrl;
            $data["custom_value"] = 0;
        } else {
            $data["custom_value"] = 1;
            $data["fixation_value"] = "";
        }

        $info = app($this->weixinMenuRepository)->getDataByWhere(["id" => [$id, "="]]);
        $countInfo = count($info);
        if ($countInfo == 0) {
            return ['code' => ['0x033006', 'weixin']];
        }
        if (strtolower(substr($data['value'], 0, 4)) == 'http') {
            $data["type"] = "view";
        } else {
            $data["type"] = "click";
        }

        $weixinData = array_intersect_key($data, array_flip(app($this->weixinMenuRepository)->getTableColumns()));
        return app($this->weixinMenuRepository)->updateData($weixinData, ['id' => $id]);
    }

    //删除菜单
    public function deleteMenu($id)
    {
        return app($this->weixinMenuRepository)->deleteWeixinMenu($id);
    }

    //微信菜单更新
    public function updateMenu()
    {

        $nodes = app($this->weixinMenuRepository)->getDataByWhere(["node" => ["p", "="]]);

        $items = [];
        foreach ($nodes as $k => $node) {
            //获取当前的下级id
            $juniors = app($this->weixinMenuRepository)->getDataByWhere(["node" => [$node["id"], "="]]);
            if (count($juniors) > 0) {
                $items[$k]["name"] = urlencode($node["name"]);
                $items[$k]["sub_button"] = [];
                foreach ($juniors as $k1 => $junior) {
                    $items[$k]["sub_button"][$k1]["type"] = $junior["type"];
                    $items[$k]["sub_button"][$k1]["name"] = urlencode($junior["name"]);
                    if ($junior["type"] == "click") {
                        $items[$k]["sub_button"][$k1]["key"] = $junior["value"];
                    } else {
                        $items[$k]["sub_button"][$k1]["url"] = $junior["value"];
                    }
                }
            } else {

                $items[$k]["type"] = $node["type"];
                $items[$k]["name"] = urlencode($node["name"]);
                if ($node["type"] == "click") {
                    $items[$k]["key"] = $node["value"];
                } else {
                    $items[$k]["url"] = $node["value"];
                }
            }
        }

        $menu = [
            "button" => $items,
        ];

        $menu = urldecode(json_encode($menu)); //

        $result = app($this->weixin)->updateMenu($menu);
        if (is_array($result)) {
            return $result;
        }
        $msg = json_decode($result, true);
        if ($msg["errcode"] == 0) {
            return true;
        } else {
            //抛出错误
            return ['code' => [(string)$msg["errcode"], 'weixin']];
        }
    }

    //关注用户
    public function getWeixinUserFollowList($data)
    {

        return $this->response(app($this->weixinUserInfoRepository), 'getWeixinUserFollowTotal', 'getWeixinUserFollowList', $this->parseParams($data));
    }

    //绑定
    public function getWeixinUserBindList($data)
    {

        return $this->response(app($this->userRepository), 'getWeixinUserTotal', 'getWeixinUserList', $this->parseParams($data));
    }

    //同步用户
    public function synchronizeUser($next_openid = null)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['code' => ['40001', 'weixin']];
        }
        //同步之前 先应该清空关注用户
        app($this->weixinUserInfoRepository)->truncateTable();

        $api = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={$accessToken}";
        if ($next_openid) {
            $api .= "&next_openid={$next_openid}";
        }

        $result = getHttps($api);
        if (!$result) {
            return ['code' => ['0x033003', 'weixin']];
        }

        $result = json_decode($result, true);

        $total = $result['total'];
        $count = $result['count'];
        $openids = $result['data']['openid'];
        $next_openid = $result['next_openid'];

        if ($count > 0) {
            foreach ($openids as $openid) {
                $this->saveSynchronizeUser($openid);
            }
        }

        if (10000 == $count && 10000 < $total) {
            return $this->synchronizeUser($next_openid);
        }

        return true;
    }

    //存储 saveSynchronizeUser 同步用户信息
    public function saveSynchronizeUser($openid)
    {

        if (!$openid) {
            return false;
        }
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }
        $api = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$accessToken}&openid={$openid}&lang=zh_CN";

        $result = getHttps($api);
        if (!$result) {
            return ['code' => ['0x033003', 'weixin']];
        }
        $result = json_decode($result, true);

        //如果存在openid 更新 否则 插入

        $checkStatus = app($this->weixinUserInfoRepository)->getDetail($openid);
        $result = array_intersect_key($result, array_flip(app($this->weixinUserInfoRepository)->getTableColumns()));
        if ($checkStatus) {
            $result = app($this->weixinUserInfoRepository)->updateData($result, ['openid' => $openid]);
        } else {
            $result = app($this->weixinUserInfoRepository)->insertData($result);
        }

        return $result;
    }

    //微信树
    public function weixinMenuTree($menu_parent)
    {
        if ($menu_parent == 0) {
            $menu_parent = "p";
        }
        $where = [
            "node" => [$menu_parent],
        ];
        $trees = app($this->weixinMenuRepository)->getDataByWhere($where);

        //获取node字段
        $nodes = app($this->weixinMenuRepository)->getDataByWhere(["node" => ["p", "!="]]);
        $tempNode = [];
        foreach ($nodes as $node) {
            $tempNode[] = $node["node"];
        }

        $temp = [];
        $restult = [];
        foreach ($trees as $tree) {
            $temp["id"] = $tree["id"];
            $temp["name"] = $tree["name"];
            $temp["has_children"] = $tree["node"] == "p" && in_array($tree["id"], $tempNode) ? 1 : 0;
            array_push($restult, $temp);
        }

        return $restult;
    }

    //获取微信菜单信息
    public function getMenuByMenuId($id)
    {

        $result = app($this->weixinMenuRepository)->getDetail($id);
        $count = app($this->weixinMenuRepository)->getCount(["node" => [$id]]);
        if ($count > 0) {
            $result["has_child"] = 1;
        } else {
            $result["has_child"] = 0;
        }

        return $result;
    }

    //检查菜单个数
    public function checkMenu($node)
    {

        if ($node == 0) {
            $count = app($this->weixinMenuRepository)->getCount(["node" => ["p"]]);
            if ($count >= 3) {
                return ['code' => ['0x033008', 'weixin']];
            }
        } else {
            $count = app($this->weixinMenuRepository)->getCount(["node" => [$node]]);
            if ($count >= 5) {
                return ['code' => ['0x033009', 'weixin']];
            }
        }

        return true;
    }

    //接入
    public function weixinAuthOld()
    {
        if (isset($_GET["code"]) && !empty($_GET["code"])) {
            $state = isset($_GET["state"]) ? $_GET["state"] : "";
            if (Redis::get("code_" . $_GET["code"])) {
                $user_id = Redis::get("code_" . $_GET["code"]);
            } else {
                $user_id = $this->weixinCode($_GET["code"]);
            }

            if (isset($user_id["code"]) && is_array($user_id["code"])) {
                $code = $user_id['code'][0];
                $message = urlencode(trans("weixin.$code"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($user_id);
            if (!empty($power["code"]) && !empty($power)) {
                $message = "手机未授权或者不允许访问！";
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $user = app($this->userService)->getUserAllData($user_id);
            if (!$user) {
                return ['code' => ['0x000117', 'weixin']];
            }
            app($this->authService)->systemParams = app($this->authService)->getSystemParam();
            $registerUserInfo = app($this->authService)->generateInitInfo($user, "zh-CN", '', false);
            if (empty($registerUserInfo) || empty($registerUserInfo['token'])) {
                return ['code' => ['0x000117', 'weixin']];
            }
            setcookie("token", $registerUserInfo['token'], time() + 3600, "/");
            setcookie("loginUserId", $user_id, time() + 3600, "/");
            $target_module = "";
            $state_url = $type = $state;
            if (!$type) {
                $state_url = "";
            } else {
                if (is_numeric($type)) {

                    $wechat = config('eoffice.wechat');
                    $state_url = isset($wechat[$type]) ? $wechat[$type] : "";
                    if (!$state_url) {
                        $state_url = "";
                    }

                    //跳转到默认的模块
                    $applistAuthIds = config('eoffice.applistAuthId');
                    $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                    if ($auth_id) {
                        $state_url = "";
                        $target_module = $auth_id;
                        // setcookie("target_module", $auth_id, time() + 3600, "/");
                    }
                } else {
                    $json = json_decode($type, true);
                    if ($json) {
                        readSmsByClient($user_id, $json);
                        // setcookie("reminds", $type, time() + 3600, "/");
                        $state_url = ""; //error
                        $module = isset($json['module']) ? $json['module'] : '';
                        $action = isset($json['action']) ? $json['action'] : '';
                        $params = isset($json['params']) ? json_encode($json['params']) : '';
                        $state_url = "module=" . $module . "&action=" . $action . "&params=" . $params;
                    }
                }
            }

            $wechat = $this->getWeixinToken();
            if (!$wechat) {
                $message = urlencode(trans("weixin.0x033012"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $domain = $wechat->domain;
            $target = "";
            if ($target_module) {
                $target = "&module_id=" . $target_module;
            }
            if ($state_url) {
                $application_url = $domain . "/eoffice10/client/mobile/#target_url=" . $state_url . "&platform=officialAccount" . $target . "&token=" . $registerUserInfo['token'];
            } else {
                $application_url = $domain . "/eoffice10/client/mobile/#" . "platform=officialAccount" . $target . "&token=" . $registerUserInfo['token'];
            }
            header("Location: $application_url");
            exit;
        }
        if (!isset($_GET['echostr'])) {
            app($this->weixin)->responseMsg();
        } else {

            app($this->weixin)->valid();
        }
    }

    /**
     * @return array
     * //接入
     */
    public function weixinAuth()
    {
        if (isset($_GET["code"]) && !empty($_GET["code"])) {
            $state = isset($_GET["state"]) ? $_GET["state"] : "";
            if (Redis::get("code_" . $_GET["code"])) {
                $user_id = Redis::get("code_" . $_GET["code"]);
            } else {
                $user_id = $this->weixinCode($_GET["code"]);
                //首次通过微信公众号进入oa且未绑定
                if (isset($user_id['openid']) && !empty($user_id['openid'])) {
                    $sign = authCode($user_id['openid'], 'ENCODE', 'eoffice9731', 300);
                    $wechat = $this->getWeixinToken();
                    if (!$wechat) {
                        $message = urlencode(trans("weixin.0x033012"));
                        $errorUrl = integratedErrorUrl($message);
                        header("Location: $errorUrl");
                        exit;
                    }
                    $domain = $wechat->domain;
                    $application_url = $domain . "/eoffice10/client/mobile/login#platform=officialAccount&sign=" . $sign . "&state=" . $state;
                    header("Location: $application_url");
                    exit;
                }
            }
            if (isset($user_id["code"]) && is_array($user_id["code"])) {
                $code = $user_id['code'][0];
                $message = urlencode(trans("weixin.$code"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($user_id);
            if (!empty($power["code"]) && !empty($power)) {
                $message = "手机未授权或者不允许访问！";
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $user = app($this->userService)->getUserAllData($user_id);
            if (!$user) {
                return ['code' => ['0x000117', 'weixin']];
            }
            $local = "zh-CN";
            app($this->authService)->systemParams = app($this->authService)->getSystemParam();
            $registerUserInfo = app($this->authService)->generateInitInfo($user, $local, '', false);
            if (empty($registerUserInfo) || empty($registerUserInfo['token'])) {
                return ['code' => ['0x000117', 'weixin']];
            }
            setcookie("token", $registerUserInfo['token'], time() + 3600, "/");
            setcookie("loginUserId", $user_id, time() + 3600, "/");
            $target_module = "";
            $state_url = $type = $state;
            $wechat = $this->getWeixinToken();
            if (!$wechat) {
                $message = urlencode(trans("weixin.0x033012"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $domain = $wechat->domain;
            $target = "";
            if (!$type) {
                $state_url = "";
            } else {
                if (is_numeric($type)) {
                    $wechat = config('eoffice.wechat');
                    $state_url = isset($wechat[$type]) ? $wechat[$type] : "";
                    if (!$state_url) {
                        $state_url = "";
                    } else {
                        $state_url = "target_url=" . $state_url;
                    }
                    //跳转到默认的模块
                    $applistAuthIds = config('eoffice.applistAuthId');
                    $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                    if ($auth_id) {
                        $state_url = "";
                        $target_module = $auth_id;
                        $target = "&module_id=" . $target_module;
                    }
                } else {
                    $json = json_decode($type, true);
                    if ($json) {
                        //异构系统特殊处理
                        app($this->unifiedMessageService)->unifiedMessageLocation($json,$user_id);
                        //@消息特殊处理
                        if (isset($json['mention'])) {
                            $module = isset($json['mention']) ? $json['mention'] : '';
                            $action = isset($json['action']) ? $json['action'] : '';
                            $params = isset($json['params']) ? json_encode($json['params']) : '';
                            //dd($params);
                            $state_url = "mention=" . $module . "&action=" . $action . "&params=" . $params;
                        } else {
                            readSmsByClient($user_id, $json);
                            $module = isset($json['module']) ? $json['module'] : '';
                            $action = isset($json['action']) ? $json['action'] : '';
                            $params = isset($json['params']) ? json_encode($json['params']) : '';
                            $state_url = "module=" . $module . "&action=" . $action . "&params=" . $params;
                        }
                    }
                }
            }
            // https://scs.shangxiao.cn/eoffice10/client/mobile/#target_url=flow/handle/17&platform=enterpriseAccount
            // target_url=/home/new
            // module=new&action=publish&params
            // module_id=1
            if ($state_url) {
                $application_url = $domain . "/eoffice10/client/mobile/#" . $state_url . "&platform=officialAccount" . $target . "&token=" . $registerUserInfo['token'] . '&local=' . $local;
            } else {
                $application_url = $domain . "/eoffice10/client/mobile/#" . "platform=officialAccount" . $target . "&token=" . $registerUserInfo['token'] . '&local=' . $local;
            }
            header("Location: $application_url");
            exit;
        }
        if (!isset($_GET['echostr'])) {
            app($this->weixin)->responseMsg();
        } else {
            app($this->weixin)->valid();
        }
    }

    //微信接入
    public function weixinAccess($data)
    {
        //微信模块ID 499 企业微信107  钉钉应用 120
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (isset($auhtMenus["code"])) {
            $code = $auhtMenus["code"];
            $message = urlencode(trans("register.$code"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        } else {
            //能获取到授权信息
            if (!in_array(499, $auhtMenus)) {
                $message = trans("weixin.Authorization_expires");
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
        }

        $type = isset($data["type"]) ? $data["type"] : "-1";
        $reminds = isset($data["reminds"]) ? $data["reminds"] : "";
        if ($type === '0') {
            $state_url = $reminds;
        } else {
            if ($type == -1) {
                $state_url = "";
            } else {
                $state_url = $type;

                $applistAuthIds = config('eoffice.applistAuthId');
                $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                if ($auth_id) {
                    if (!in_array($auth_id, $auhtMenus)) {

                        $message = trans('weixin.module_authorization_expires');
                        $errorUrl = integratedErrorUrl($message);
                        header("Location: $errorUrl");
                        exit;
                    }
                }
            }
        }

        $weixin = $this->getWeixinToken(); //111
        if (!$weixin) {
            $message = urlencode(trans("weixin.0x033012"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        $appid = $weixin->appid;
        $domain = $weixin->domain;
        $appsecret = $weixin->appsecret;

        if (!($appid && $domain && $appsecret)) {
            $message = urlencode(trans("weixin.0x033012"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        $tempUrl = $domain . "/eoffice10/server/public/api/weixin-auth";
        $redirect_uri = urlencode($tempUrl);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_base&state=" . $state_url . "#wechat_redirect";
        header("Location: $url");
        exit;
    }

    /// ------------
    //获取用户
    public function weixinCode($code)
    {
        if (!$code) {
            return ['code' => ['41008', 'weixin']];
        }
        $weixin = $this->getWeixinToken(); //111
        if (!$weixin) {
            return ['code' => ["0x033012", 'weixin']];
        }
        $appid = $weixin->appid;
        $domain = $weixin->domain;
        $appsecret = $weixin->appsecret;

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $appsecret . "&code=" . $code . "&grant_type=authorization_code";

        $postJson = getHttps($url);
        $postObj = json_decode($postJson, true);

        if (isset($postObj['errcode']) && $postObj['errcode'] != 0) {
            $code = $postObj['errcode'];
            return ['code' => ["$code", 'weixin']];
        }

        if (!isset($postObj['openid'])) {
            return ['code' => ["41009", 'weixin']];
        }
        $openid = $postObj['openid'];
        $userInfo = app($this->userRepository)->getUserInfoByOpenid($openid);

        if (!isset($userInfo['user_id']) || !$userInfo['user_id']) {
            return ['openid' => $openid];
            // return ['code' => ["0x033011", 'weixin']];
        }
        Redis::set('code_' . $code, $userInfo['user_id']);
        return $userInfo['user_id'];
    }

    //生成二维码
    public function getBindingQRcode($user_id, $data)
    {
        if (empty($user_id)) {
            $result = $result = ['code' => ['0x003001', 'auth']];
            return $result;
        }
        return app($this->weixin)->getBindingQRcode($user_id);
    }

    //解绑用户
    public function unwrapWeixin($user_id)
    {

        $res = app($this->weixinUserRepository)->getInfoByWhere(["user_id" => [$user_id]]);
        if (!$res) {
            return ['code' => ["0x000114", 'weixin']];
        }
        $openId = $res->openid;
        //强制推送解绑消息
        $user_name = app($this->userRepository)->getUserName($user_id);
        $this->noticeMessage($openId, trans('weixin.unbind_user'), $user_name);
        return app($this->weixinUserRepository)->updateData(["user_id" => '', "auth_time" => ""], ["openid" => $openId]);
    }

    public function weixinSignPackage($data = null)
    {
        return app($this->weixin)->weixinSignPackage($data);
    }

    //下载文件
    public function weixinMove($data)
    {
        return app($this->weixin)->weixinMove($data);
    }

    //推送消息
    public function pushMessage($option)
    {
        return app($this->weixin)->pushMessage($option);
    }

    public function noticeMessage($openid, $message, $user_name)
    {
        return app($this->weixin)->noticeMessage($openid, $message, $user_name);
    }

    //
    public function weixinCheck()
    {
        //微信检查
        $wechat = $this->getWeixinToken();
        if (!$wechat) {
            return 0;
        }

        $is_push = $wechat->is_push;
        if (!$is_push) {
            return 0;
        }
        /*$data["appid"]     = $wechat->appid;
        $data["appsecret"] = $wechat->appsecret;

        $temp = $this->connectWeixinToken($data);
        if (isset($temp["code"]) && $temp["code"]) {
            return 0;
        }*/

        return 1;
    }

    public function weixinMenuList()
    {
        $wechat = $this->getDatasourceType();
        $temp = [];
        $result = [];
        $i = 0;
        foreach ($wechat as $key => $val) {
            $temp["value"] = $key;
            $temp["name"] = $val;
            $result[] = $temp;
            $i++;
        }
        return [
            "total" => $i,
            "list" => $result,
        ];
    }

    public function getDatasourceType($data = array())
    {
        $datasource_type = config('eoffice.weixin');
        foreach ($datasource_type as $k => &$v) {
            $datasource_type[$k] = trans($v);
        }
        return $datasource_type;
    }

    public function showWechat($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $auhtMenus = app($this->empowerService)->getPermissionModules();

        //$power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($userId);

        $wechat = $this->getWeixinToken();

        if (isset($auhtMenus["code"]) || !in_array(499, $auhtMenus) || empty($wechat) || $wechat->is_push == 0) {
            return "false";
        } else {
            return "true";
        }

    }

    /******************************************************************* 微信自动回复 stert*********************************************/
    /**
     * 设置微信自动回复
     * @param $param
     * @return array
     * @author [dosy]
     */
    public function setWeixinReply($param)
    {
        //内容回复
        if (isset($param['auto_reply']) && $param['auto_reply'] != 0) {
            $data['auto_reply'] = $param['auto_reply'];
            if (isset($param['auto_reply_template_id']) && !empty($param['auto_reply_template_id'])) {
                $data['auto_reply_template_id'] = $param['auto_reply_template_id'];
            } else {
                return ['code' => ["0x10000", 'weixin']];
            }
        } else {
            $data['auto_reply'] = 0;
        }
        //关键字回复
        if (isset($param['keywords_auto_reply']) && $param['keywords_auto_reply'] != 0) {
            $data['keywords_auto_reply'] = $param['keywords_auto_reply'];
            $keywordsContent = isset($param['keywordsContent']) ? $param['keywordsContent'] : array();

            if (!is_array($keywordsContent)) {
                $keywordsContent = array();
            } else {
                foreach ($keywordsContent as $key => $value) {
                    if (empty($value['keywords'])) {
                        unset($keywordsContent[$key]);
                    }
//                    }else{
//                        //dd($value)
//                        $keywordsContent[$key]['keywords']=trim($value['keywords']);
//                    }
                }
            }
            $keywordsContent = array_values($keywordsContent);
            $data['keywords_template_content'] = json_encode($keywordsContent, JSON_UNESCAPED_UNICODE);
        } else {
            $data['keywords_auto_reply'] = 0;
        }
        $replyData = array_intersect_key($data, array_flip(app($this->weixinReplyRepository)->getTableColumns()));
        app($this->weixinReplyRepository)->clearWechatReply();
        $result = app($this->weixinReplyRepository)->insertData($replyData);
        return $result;
    }

    /**
     * 获取微信自动回复设置
     * @return mixed
     * @author [dosy]
     */
    public function getWeixinReply()
    {
        $result = app($this->weixinReplyRepository)->getData();
        return $result;
    }

    /**
     * 获取微信自动回复模板列表
     * @param $param
     * @return array
     * @author [dosy]
     */
    public function getReplyTemplateList($param)
    {
        $params = $this->parseParams($param);
        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];
        if ($response == 'both' || $response == 'count') {
            $count = app($this->weixinReplyTemplateRepository)->getCount($params);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->weixinReplyTemplateRepository)->getList($params) as $new) {
                $list[] = $new;
            }
        }
        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }

    /**
     * 凭借模板id获取回复模板
     * @param $id
     * @return mixed
     * @author [dosy]
     */
    public function getReplyTemplate($id)
    {
        $result = app($this->weixinReplyTemplateRepository)->getDataById($id);
        return $result;
    }

    /**
     * 凭借模板id删除回复模板
     * @param $id
     * @return mixed
     * @author [dosy]
     */
    public function deleteReplyTemplate($id)
    {
        $data = app($this->weixinReplyRepository)->getData();
        if ($data) {
            $autoReply = isset($data['auto_reply']) ? $data['auto_reply'] : 0;
            $autoReplyTemplateId = isset($data['auto_reply_template_id']) ? $data['auto_reply_template_id'] : 0;
            $keywordsAutoReply = isset($data['keywords_auto_reply']) ? $data['keywords_auto_reply'] : 0;
            $keywordsTemplateContent = isset($data['keywords_template_content']) ? $data['keywords_template_content'] : [];
            if ($autoReply && $autoReplyTemplateId == $id) {
                return ['code' => ["0x10002", 'weixin']];
            }
            if ($keywordsAutoReply) {
                $keywordsTemplateContent = json_decode($keywordsTemplateContent);
                foreach ($keywordsTemplateContent as $key => $value) {
                    $keywordsTemplateId = isset($value->keywords_template_id) ? $value->keywords_template_id : 0;
                    if ($keywordsTemplateId == $id) {
                        return ['code' => ["0x10002", 'weixin']];
                    }
                }
            }
        }
        $templateData = app($this->weixinReplyTemplateRepository)->getDataById($id);
        if (isset($templateData['news_share_token']) && !empty($templateData['news_share_token'])) {
            app($this->attachmentService)->deleteOneShareLink($templateData['news_share_token']);
        }
        if (isset($templateData['news_attachments_code']) && !empty($templateData['news_attachments_code'])) {
            $data = ['attachment_id' => $templateData['news_attachments_code']];
            $res = app($this->attachmentService)->removeAttachment($data);
            if ($res['code']) {
                return $res;
            }
        }
        $result = app($this->weixinReplyTemplateRepository)->deleteById($id);
        return $result;
    }

    /**
     *设置微信回复模板内容
     * @param $param
     * @return mixed
     * @author [dosy]
     */
    public function setReplyTemplate($param, $won)
    {
        $replyData = array_intersect_key($param, array_flip(app($this->weixinReplyTemplateRepository)->getTableColumns()));
        if (!isset($replyData['template_name']) || empty($replyData['template_name'])) {
            return ['code' => ["0x10001", 'weixin']];
        }
        // 判断是新建还是编辑
        if (isset($param['template_id']) && !empty($param['template_id'])) {
            $templateData = $this->getReplyTemplate($param['template_id']);
            // 模板id错误
            if (!$templateData) {
                return ['code' => ["0x10003", 'weixin']];
            }
            //存在图片获取分享token
            if (isset($param['news_attachments_code']) && !empty($param['news_attachments_code'])) {
                $attachmentShareInfo = app($this->attachmentService)->shareAttachment($param['news_attachments_code'], [], $won['user_id']);
                if ($attachmentShareInfo['code']) {
                    return $attachmentShareInfo;
                }
                $replyData['news_share_token'] = isset($attachmentShareInfo['share_token']) ? $attachmentShareInfo['share_token'] : '';
            }
            //删除编辑之前该模板存在的分享token
            if (isset($templateData['news_share_token']) && !empty($templateData['news_share_token'])) {
                app($this->attachmentService)->deleteOneShareLink($templateData['news_share_token']);
            }
            //----图片替换删除原图片
            if (isset($param['news_attachments_code']) && !empty($param['news_attachments_code']) && $param['news_attachments_code'] != $templateData['news_attachments_code']) {
                $data = ['attachment_id' => $templateData['news_attachments_code']];
                $res = app($this->attachmentService)->removeAttachment($data);
                if ($res['code']) {
                    return $res;
                }
            }
            $where = ['template_id' => $param['template_id']];
            $result = app($this->weixinReplyTemplateRepository)->updateData($replyData, $where, ['template_id']);
        } else {
            // 新建保存
            if (isset($param['news_attachments_code']) && !empty($param['news_attachments_code'])) {
                $attachmentShareInfo = app($this->attachmentService)->shareAttachment($param['news_attachments_code'], [], $won['user_id']);
                if ($attachmentShareInfo['code']) {
                    return $attachmentShareInfo;
                }
                $replyData['news_share_token'] = isset($attachmentShareInfo['share_token']) ? $attachmentShareInfo['share_token'] : '';

                //dd($img_url);
                //$A=app($this->attachmentService)->loadShareAttachment($replyData['news_share_token'] ,[]);
                //dd($A);
            }
            $result = app($this->weixinReplyTemplateRepository)->insertData($replyData);
        }
        return $result;
    }
    /******************************************************************* 微信自动回复 end*********************************************/

    /**
     * 微信公众号绑定oa用户
     * @param $openId
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function weixinUserBindOAUser($openId, $userId)
    {
        $where = ["openid" => [$openId]];
        $weixinUser = app($this->weixinUserRepository)->getInfoByWhere($where);
        //null 说明没有关注公众号
        if (empty($weixinUser)) {
            return ['code' => ['0x0100001', 'weixin']];
        }
        if (isset($weixinUser['user_id']) && empty($weixinUser['user_id'])) {
            $checkWeixinUser = app($this->weixinUserRepository)->getInfoByWhere(["user_id" => [$userId]]);
            //有值说明当前账号已经被绑定
            if (empty($checkWeixinUser)) {
                $time = time();
                $data = [
                    "user_id" => $userId,
                    "auth_time" => $time,
                ];
                $where = [
                    "openid" => $openId,
                ];
                $res = app($this->weixinUserRepository)->updateData($data, $where);
                //推送绑定消息
                $user_name = app($this->userRepository)->getUserName($userId);
                $this->noticeMessage($openId, trans('weixin.bind_success'), $user_name);
                return $res;
            }
        }
        return ['code' => ['already_bind_other', 'weixin']];
    }

    /**
     * 从微信公众号首次登陆oa（未绑定oa用户首次从微信公众号登陆，需要先登录绑定）
     * @param $params
     * @param $own
     * @return string
     * @creatTime 2020/12/3 14:18
     * @author [dosy]
     */
    public function weixinLogin($params, $own)
    {
        //$params['sign'] = authCode($params['openid'], 'ENCODE', 'eoffice9731');
        //$domain . "/eoffice10/server/public/api/weixin-access?type=" . $data["value"]
        // requestUrl = 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js';
        // $application_url = $domain . "/eoffice10/client/mobile/#" . "platform=officialAccount";
        // 小程序接入方案：$domain . "/eoffice10/client/mobile/#" . "platform=officialAccount&target_url=" . $target（例如：flow/handle/17 =》直接从复制的链接mobile/后面截取就好了） . "&token=" . $token . '&local=' . $local;
        // 解错了就是'';
        $sign = $params['sign'] ?? '';
        $state = $params['state'] ?? '';
        $userId = $own['user_id'];
        $target = "";
        $local = $params['locale'] ?? "zh-CN";
        $token = $params['token'] ?? "";
        $openId = authCode($sign, 'DECODE', 'eoffice9731');
        //检查该用户是否允许手机端访问
        $power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($userId);
        if (!empty($power["code"]) && !empty($power)) {
            //$message = "手机未授权或者不允许访问！";
            $message = urlencode(trans("workwechat.phone_allowed_access"));
            $errorUrl = integratedErrorUrl($message);
            return $errorUrl;
        }
        //检查用户
        $user = app($this->userService)->getUserAllData($userId);
        if (!$user) {
            $message = urlencode(trans("weixin.0x000117"));
            $errorUrl = integratedErrorUrl($message);
            return $errorUrl;
        }
        //获取微信配置
        $wechat = $this->getWeixinToken();
        if (!$wechat) {
            $message = urlencode(trans("weixin.0x033012"));
            $errorUrl = integratedErrorUrl($message);
            return $errorUrl;
        }
        $domain = $wechat->domain;

        //进入跳转页
        $state_url = $type = $state;
        if (!$type) {
            $state_url = "";
        } else {
            if (is_numeric($type)) {
                $wechat = config('eoffice.wechat');
                $state_url = isset($wechat[$type]) ? $wechat[$type] : "";
                if (!$state_url) {
                    $state_url = "";
                } else {
                    $state_url = "target_url=" . $state_url;
                }
                //跳转到默认的模块
                $applistAuthIds = config('eoffice.applistAuthId');
                $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                if ($auth_id) {
                    $state_url = "";
                    $target_module = $auth_id;
                    $target = "&module_id=" . $target_module;
                }
            } else {
                $json = json_decode($type, true);
                if ($json) {
                    readSmsByClient($userId, $json);
                    $module = isset($json['module']) ? $json['module'] : '';
                    $action = isset($json['action']) ? $json['action'] : '';
                    $param = isset($json['params']) ? json_encode($json['params']) : '';
                    $state_url = "module=" . $module . "&action=" . $action . "&params=" . $param;
                }
            }
        }
        // target_url=/home/new
        // module=new&action=publish&params
        // module_id=1
        if ($state_url) {
            $application_url = $domain . "/eoffice10/client/mobile/#" . $state_url . "&platform=officialAccount" . $target . "&token=" . $token . '&local=' . $local;
        } else {
            $application_url = $domain . "/eoffice10/client/mobile/#" . "platform=officialAccount" . $target . "&token=" . $token . '&local=' . $local;
        }
        if (!empty($openId)) {
            /* //获取token
            app($this->authService)->systemParams = app($this->authService)->getSystemParam();
             $registerUserInfo = app($this->authService)->generateInitInfo($user, "zh-CN");
             if (empty($registerUserInfo) || empty($registerUserInfo['token'])) {
                 $message = urlencode(trans("weixin.0x000117"));
                 $errorUrl = integratedErrorUrl($message);
                 return $errorUrl;
             }
             setcookie("token", $registerUserInfo['token'], time() + 3600, "/");
             setcookie("loginUserId", $userId, time() + 3600, "/");*/
            //绑定微信用户与oa账号
            $res = $this->weixinUserBindOAUser($openId, $userId);
            if (isset($res["code"]) && is_array($res["code"])) {
                $code = $res['code'][0];
                $message = urlencode(trans("weixin.$code"));
                $errorUrl = integratedErrorUrl($message);
                return $errorUrl;
            }
        }
        return $application_url;
    }

    /** 组装拉取微信电子发票列表所需参数
     * @return array|bool|mixed|string
     */
    public function getInvoiceParam()
    {
        // 获取access_token
        $accessToken = $this->getAccessToken();
        if (!$accessToken || isset($accessToken['code'])){
            return $accessToken;
        }
        // 获取api_ticket
        $apiTicket = $this->getTicket($accessToken);
        if (!$apiTicket || isset($apiTicket['code'])){
            return $apiTicket;
        }
        // cardSign 参数处理
        $wechat = $this->getWeixinToken();
        $corpid = $wechat->appid;
        $cardType = 'INVOICE';
        $timestamp = time();
        $nonceStr = md5(uniqid(microtime(true),true));
        $signType = 'SHA1';
        $cardSign = sha1($apiTicket.$corpid.$timestamp.$nonceStr.$cardType);
        return compact('timestamp', 'nonceStr', 'signType', 'cardSign');
    }

    /** 微信内获取获取电子发票ticket
     * @param $access_token 调用接口凭证
     * @param string $type
     * @return array|mixed|string
     */
    public function getTicket($access_token, $type = 'wx_card')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$access_token&type=$type";
        $vl = getHttps($url);
        $result = json_decode($vl, true);
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            $code = $result['errcode'];
            return ['code' => ["$code", 'workwechat']];
        } else {
            $apiTicket = $result['ticket'] ?? '';
            return $apiTicket;
        }
    }

    public function getWxInvoices($params)
    {
        // 获取agentid
        $agentId = $_COOKIE['agentid'] ?? '';
        if (!$agentId) {
            return ['code'=>['', '无agentid']];
        }
        // 获取access_token
        $accessToken = $this->getAccessToken();
        if (!$accessToken || isset($accessToken['code'])){
            return $accessToken;
        }
        $userList = $this->getDeptUserInfo($accessToken, 1);
        if (isset($userList['code'])) {
            return $userList;
        }
        $userCount = count($userList);
        $info = $params['info'] ?? [];
        if(!$info) {
            return ['code' => ['', '微信返回发票列表信息错误']];
        }
        $invoices = [];$errors = [];
        //https://qyapi.weixin.qq.com/cgi-bin/card/invoice/reimburse/getinvoiceinfo?access_token=ACCESS_TOKEN
        if ($userCount > 200) {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/card/invoice/reimburse/getinvoiceinfo?access_token=$accessToken";
            $fields = $info;
            $userMsg = urldecode(json_encode($fields));
            $vl = getHttps($url, $userMsg);
            $vlData = json_decode($vl, true);
            if (isset($vlData['errcode']) && $vlData['errcode'] != 0) {
                $code = $vlData['errcode'];
                $errors = ['code' => ["$code", 'workwechat']];
            } else {
                $invoices[] = $vlData;
            }
        } else {
            if (count($info) == count($info, 1)){
                $info = [$info];
            }
            foreach ($info as $param) {
                $url = "https://qyapi.weixin.qq.com/cgi-bin/card/invoice/reimburse/getinvoiceinfo?access_token=$accessToken";
                $fields = $param;
                $userMsg = urldecode(json_encode($fields));
                $vl = getHttps($url, $userMsg);
                $vlData = json_decode($vl, true);
                if (isset($vlData['errcode']) && $vlData['errcode'] != 0) {
                    $code = $vlData['errcode'];
                    $errors = ['code' => ["$code", 'workwechat']];
                    if ($code == '48001'){
                        break;
                    }
                } else {
                    $invoices[] = $vlData;
                }
            }
        }
        if ($errors) {
            return $errors;
        }
        return $invoices;
    }

    /**
     * 下载微信服务器IP
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @creatTime 2020/11/25 15:36
     * @author [dosy]
     */
    public function downWeiXinIp(){
        $weiXinReturn = $this->getWeiXinIp();
        $this->writeWeiXinIpLog($weiXinReturn);
        return response()->download(storage_path() . '/logs/weiXin/微信服务器IP地址.log');
    }

    /**
     * 获取微信服务器IP
     * @return mixed
     * @creatTime 2020/11/25 15:37
     * @author [dosy]
     */
    public function getWeiXinIp(){
        // 获取access_token
        $accessToken = $this->getAccessToken();
        if (!$accessToken || isset($accessToken['code'])){
            return $accessToken;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/get_api_domain_ip?access_token=$accessToken";
        $getData = getHttps($url);
        $result = json_decode($getData, true);
        return $result;
    }

    /**
     * 微信服务器IP写入指定日志
     * @param $weiXinReturn
     * @creatTime 2020/11/25 15:36
     * @author [dosy]
     */
    public function writeWeiXinIpLog($weiXinReturn){
        $requestData = date('Y-m-d H:i:s') . '***:' . json_encode($weiXinReturn, JSON_UNESCAPED_UNICODE) . "\r\n";
        $fileName = storage_path() . '/logs/weixin/微信服务器IP地址.log';
        if (!is_dir( storage_path() . '/logs/weiXin')){
            mkdir(storage_path().'/logs/weiXin',0777);
        }
        file_put_contents($fileName, $requestData, FILE_APPEND);
    }

}




