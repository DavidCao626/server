<?php

namespace App\EofficeApp\Dingtalk\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\DingtalkOrganizationSyncJob;
use App\Jobs\DingtalkAttendanceSyncJob;
use App\Utils\Utils;
use Eoffice;
use Queue;
use DB;
use Log;
use Illuminate\Support\Facades\Redis;


/**
 * 企业号服务
 *
 * @author: 喻威 F:\wamp\www\eoffice10\server\app\Utils\Weixin.php
 *
 * @since：2015-10-19
 */
class DingtalkService extends BaseService
{

    private $dingtalkTokenRepository;
    private $dingtalkTicketRepository;
    private $dingTalk;
    private $userRepository;
    private $roleRepository;
    private $userService;
    private $departmentService;
    private $roleService;
    private $authService;
    private $departmentRepository;
    private $userMenuService;
    private $remindsRepository;
    private $dingtalkUserRepository;
    private $dingtalkLogsRepository;
    private $empowerService;
    private $attendanceService;
    private $attendanceMachineService;
    private $dingtalkOrganizationSyncExceptionRepository;
    private $dingtalkOrganizationSyncDepartmentRepository;
    private $dingtalkOrganizationSyncRoleRepository;
    private $dingtalkOrganizationSyncUserRepository;
    private $dingtalkOrganizationSyncConfigRepository;
    private $userSystemInfoRepository;
    private $userInfoRepository;
    private $menuRepository;
    private $dingtalkSyncLogRepository;
    private $attendanceRecordsRepository;
    private $attendanceBaseService;
    private $unifiedMessageService;
    private $dingUserArray = [];

    public function __construct()
    {
        $this->dingtalkTokenRepository  = "App\EofficeApp\Dingtalk\Repositories\DingtalkTokenRepository";
        $this->dingtalkTicketRepository = "App\EofficeApp\Dingtalk\Repositories\DingtalkTicketRepository";
        $this->dingTalk                 = "App\Utils\DingTalk";
        $this->userRepository           = "App\EofficeApp\User\Repositories\UserRepository";
        $this->roleRepository           = "App\EofficeApp\Role\Repositories\RoleRepository";
        $this->menuRepository           = "App\EofficeApp\Menu\Repositories\MenuRepository";
        $this->userService              = "App\EofficeApp\User\Services\UserService";
        $this->departmentService        = "App\EofficeApp\System\Department\Services\DepartmentService";
        $this->roleService              = "App\EofficeApp\Role\Services\RoleService";
        $this->authService              = "App\EofficeApp\Auth\Services\AuthService";
        $this->remindsRepository        = "App\EofficeApp\System\Remind\Repositories\RemindsRepository";
        $this->departmentRepository     = "App\EofficeApp\System\Department\Repositories\DepartmentRepository";
        $this->userMenuService          = "App\EofficeApp\Menu\Services\UserMenuService";
        $this->dingtalkUserRepository   = "App\EofficeApp\Dingtalk\Repositories\DingtalkUserRepository";
        $this->empowerService           = "App\EofficeApp\Empower\Services\EmpowerService";
        $this->attendanceService        = "App\EofficeApp\Attendance\Services\AttendanceService";
        $this->attendanceMachineService        = "App\EofficeApp\Attendance\Services\AttendanceMachineService";
        $this->dingtalkLogsRepository   = "App\EofficeApp\Dingtalk\Repositories\DingtalkLogsRepository";
        $this->dingtalkOrganizationSyncExceptionRepository   = "App\EofficeApp\Dingtalk\Repositories\DingtalkOrganizationSyncExceptionRepository";
        $this->dingtalkOrganizationSyncDepartmentRepository   = "App\EofficeApp\Dingtalk\Repositories\DingtalkOrganizationSyncDepartmentRepository";
        $this->dingtalkOrganizationSyncRoleRepository   = "App\EofficeApp\Dingtalk\Repositories\DingtalkOrganizationSyncRoleRepository";
        $this->dingtalkOrganizationSyncUserRepository   = "App\EofficeApp\Dingtalk\Repositories\DingtalkOrganizationSyncUserRepository";
        $this->dingtalkOrganizationSyncConfigRepository   = "App\EofficeApp\Dingtalk\Repositories\DingtalkOrganizationSyncConfigRepository";
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userInfoRepository = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->dingtalkSyncLogRepository = 'App\EofficeApp\Dingtalk\Repositories\DingtalkSyncLogRepository';
        $this->attendanceRecordsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRecordsRepository';
        $this->attendanceBaseService = 'App\EofficeApp\Attendance\Services\AttendanceBaseService';
        $this->unifiedMessageService = 'App\EofficeApp\UnifiedMessage\Services\UnifiedMessageService';
    }

    //自动更新access_token
    public function getAccessToken()
    {
        return app($this->dingTalk)->getAccessToken();
    }

    /**
     * todo ： 手写页面进行访问测试 -- api
     */
    public function checkDingtalk($data)
    {

        $corpid     = trim($data['corpid']);
        $secret     = isset($data["secret"]) ? trim($data["secret"]) : "";
        $domain     = trim($data["domain"]);
        $domain     = trim($domain, "/");
        $app_key    = isset($data["app_key"]) ? trim($data["app_key"]) : "";
        $app_secret = isset($data["app_secret"]) ? trim($data["app_secret"]) : "";
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return ['code' => ['0x120004', 'dingtalk']]; // 域名不合法
        }

        $urls = parse_url($domain);
        $host = $urls["host"];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            //IP地址
            if ($host == "localhost" || $host == "127.0.0.1") {
                return ['code' => ['0x120005', 'dingtalk']]; // 内网IP
            }

            $ip_range = array(
                array('10.0.0.0', '10.255.255.255'),
                array('172.16.0.0', '172.31.255.255'),
                array('192.168.0.0', '192.168.255.255'),
            );

            $ip   = ip2long($host);
            $find = '';
            foreach ($ip_range as $k => $v) {
                if ($ip >= ip2long($v[0]) && $ip <= ip2long($v[1])) {
                    $find = $k;
                }
            }

            if ((!empty($find))) {
                return ['code' => ['0x120005', 'dingtalk']]; // 内网IP
            }
        }

        //访问测试  - 公开API 跳过 =-------------------------------------- todo ---------------
        //        $url = $domain . "/general/dingtalk/validurl.php";
        //        $status = getHttps($url);
        //测试链接
        if (!empty($secret)) {
            $result = $this->connectToken($corpid, $secret);
        } else {
            $result = $this->connectToken($app_key, $app_secret);
        }

        return $result;
    }

    public function getDingtalk()
    {

        $result = app($this->dingtalkTokenRepository)->getDingTalk([]);
        if (isset($result->update_time) && !empty($result->update_time)) {
            $result->update_time = explode(",", $result->update_time);
        }
        return $result;
    }

    public function getDingtalkTime()
    {
        $result = $this->getDingtalk();
        if (!empty($result) && !empty($result->update_time) && $result->is_auto == 1) {
            return $result->update_time;
        }
    }

    public function connectToken($corpid, $secret)
    {

        $time          = time();
        $url           = "https://oapi.dingtalk.com/gettoken?corpid={$corpid}&corpsecret={$secret}";
        $weixinConnect = getHttps($url);
        if (!$weixinConnect) {
            return ['code' => ['0x120002', 'dingtalk']]; //企业钉钉响应异常
        }
        $connectList = json_decode($weixinConnect, true);

        if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
            $code = $connectList['errcode'];
            return ['code' => ["$code", 'dingtalk']];
        }
        return $connectList['access_token'];
    }

    public function saveDingtalk($data)
    {
        $corpid     = trim($data['corpid']);
        $secret     = isset($data['secret']) ? trim($data['secret']) : '';
        $domain     = trim($data["domain"]);
        $domain     = trim($domain, "/");
        $app_key    = isset($data["app_key"]) ? trim($data["app_key"]) : "";
        $app_secret = isset($data["app_secret"]) ? trim($data["app_secret"]) : "";
        if (!empty($secret)) {
            $connectList = $this->connectToken($corpid, $secret);
        } else {
            $connectList = $this->connectToken($app_key, $app_secret);
        }

        if (isset($connectList['code'])) {
            return $connectList;
        }

        $data['access_token'] = $connectList;
        $data['token_time']   = time();

        $time       = time();
        $weixinData = [
            "access_token" => $connectList,
            "token_time"   => $time,
            "create_time"  => $time,
            "domain"       => $domain,
            "corpid"       => $corpid,
            "secret"       => $secret,
            "agentid"      => isset($data["agentid"]) ? $data["agentid"] : "",
            "is_push"      => isset($data["is_push"]) ? $data["is_push"] : 0,
            "app_id"       => isset($data["app_id"]) ? trim($data["app_id"]) : "",
            "app_key"      => $app_key,
            "app_secret"   => $app_secret,
        ];

        $this->truncateDingTalk();
        $dingtalkData = app($this->dingtalkTokenRepository)->insertData($weixinData);
        if ($dingtalkData) {
            //插入、更新reminds
            $remids = app($this->remindsRepository)->checkReminds("dingtalk");
            if (!$remids) {
                app($this->remindsRepository)->insertData(["id" => 6, "reminds" => "dingtalk"]);
            }
        }

        return $dingtalkData;
    }

    public function truncateDingtalk()
    {

        $dingtalkData = app($this->dingtalkTokenRepository)->truncateDingTalk();
        if ($dingtalkData) {
            $where = [
                "reminds" => ["dingtalk"],
            ];
            app($this->remindsRepository)->deleteByWhere($where);
        }

        return $dingtalkData ? 1 : ['code' => ["0x120001", 'dingtalk']];
    }

//    通过免登code来获取钉钉用户信息，对比OA内部是否有该用户。如果有则返回OA用户的ID
    public function dingtalkCode($code)
    {
        if (!$code) {
            return ['code' => ['0x120006', 'dingtalk']];
        }

        $access_token = $this->getAccessToken();

        $url = "https://oapi.dingtalk.com/user/getuserinfo?access_token={$access_token}&code={$code}";

        $postJson = getHttps($url);
        $postObj  = json_decode($postJson, true);

        if (isset($postObj['errcode']) && $postObj['errcode'] != 0) {
            $code = $postObj['errcode'];
            return ['code' => ["$code", 'dingtalk']];
        }
        if (!isset($postObj["userid"])) {
            return ['code' => ["0x120009", 'dingtalk']];
        }
        $userId = $postObj["userid"];
        //通过userId 获取 当前用户的手机号 通过手机号绑定
        $userApi = "https://oapi.dingtalk.com/user/get?access_token={$access_token}&userid={$userId}";

        $userJson = getHttps($userApi);
        $userObj  = json_decode($userJson, true);
        if (isset($userObj['errcode']) && $userObj['errcode'] != 0) {
            $code = $userObj['errcode'];
            return ['code' => ["$code", 'dingtalk']];
        }
        $mobile = isset($userObj["mobile"]) ? $userObj["mobile"] : "";

        $name = isset($userObj["name"]) ? $userObj["name"] : "";

        $userid = isset($userObj["userid"]) ? $userObj["userid"] : "";
        if ($mobile) {
            $where = ["user_info.phone_number" => [$mobile]];
        } else {
            if (isset($userObj['isAdmin']) && $userObj['isAdmin']) {
                $where = ["user.user_name" => [$name]];
            } else {
                $where = ["user.user_id" => [$userid]];
            }
        }
        $fields = ["user.user_id", "user_info.phone_number"];
        //通过userId or mobile 做一个OA绑定
        $userInfo = app($this->userRepository)->checkUserByWhere($where, $fields);
        if (!$userInfo) {
            return ['code' => ["0x120010", 'dingtalk']];
        }
        $oaId = $userInfo->user_id;
        if (!$mobile) {
            $mobile = $userInfo->phone_number;
        }
        $this->updateDingTalkUser($oaId, $userId, $mobile);

        return $oaId;
    }

    public function updateDingTalkUser($oaId, $userId, $mobile)
    {
        $where = [
            "mobile" => [$mobile],
        ];
        $row   = app($this->dingtalkUserRepository)->getUser($where);

        if (!$row) {
            $data   = [
                "oa_id"  => $oaId,
                "userid" => $userId,
                "mobile" => $mobile,
            ];
            $result = app($this->dingtalkUserRepository)->insertData($data);
        } else {
            $data   = [
                "oa_id"  => $oaId,
                "userid" => $userId,
            ];
            $result = app($this->dingtalkUserRepository)->updateData($data, $where);
        }

        return $result;
    }

    //js-sdk配置
    public function dingtalkSignPackage()
    {
        return app($this->dingTalk)->dingtalkSignPackage();
    }

    //js-sdk配置 前端接入使用
    public function dingtalkClientpackage($data)
    {
        return app($this->dingTalk)->dingtalkClientpackage($data);
    }

    //高德地图
    public function dingtalkAttendance($data)
    {
        return app($this->dingTalk)->geocodeAttendance($data);
    }

    //下载文件
    public function dingtalkMove($data)
    {
        return app($this->dingTalk)->dingtalkMove($data);
    }

    //dingtalkChat 会话
    public function dingtalkChat($data)
    {
        return app($this->dingTalk)->dingtalkChat($data);
    }

    //推送消息
    public function pushMessage($option)
    {

        return app($this->dingTalk)->pushMessage($option);
    }

    //钉钉用户列表
    public function userListDingTalk($data)
    {
        $dingtalkTemp = app($this->dingTalk)->userListDingTalk();
        $dingtalkData = json_decode($dingtalkTemp, true);
        if (isset($dingtalkData['errcode']) && $dingtalkData['errcode'] != 0) {
            $code = $dingtalkData['errcode'];
            return ['code' => ["$code", 'dingtalk']];
        }
        $userlist = $dingtalkData["userlist"];
        //同步用户 同步到表
        app($this->dingtalkUserRepository)->truncateTable();
        foreach ($userlist as $v) {
            $tempData = array_intersect_key($v, array_flip(app($this->dingtalkUserRepository)->getTableColumns()));
            app($this->dingtalkUserRepository)->insertData($tempData);
        }
        return "ok";
    }

    public function userListShow($data)
    {

        $tempData = $this->response(app($this->dingtalkUserRepository), 'getUserListShowTotal', 'getUserListShow', $this->parseParams($data));

        $temp = [];

        if ($tempData["list"]) {
            foreach ($tempData["list"] as $val) {
                $val["user_name"] = app($this->userRepository)->getUserName($val["userid"]);

                $temp[] = $val;
            }
        }

        return [
            "total" => $tempData["total"],
            "list"  => $temp,
        ];
    }

    public function dingtalkNearby($data)
    {

        return app($this->dingTalk)->dingtalkNearby($data);
    }

    //检查配置
    public function dingtalkCheck()
    {

        $dingtalk = $this->getDingTalk();

        if (!$dingtalk) {
            return 0;
        }

        $is_push = $dingtalk->is_push;
        if (!$is_push) {
            return 0;
        }

        $data["corpid"] = $dingtalk->corpid;
        $data["domain"] = $dingtalk->domain;
        $data["secret"] = $dingtalk->secret;

        $temp = $this->checkDingTalk($data);

        if (isset($temp["code"]) && $temp["code"]) {
            return 0;
        }

        return 1;
    }

    //接入
    //      var DING_TALK = 0, //钉钉平台
    //    OFFICIAL_ACCOUNT = 1, //钉钉公众号
    //    ENTERPRISE_ACCOUNT = 2, //企业号
    //    ENTERPRISE_WECHAT = 3, //企业钉钉
    public function dingtalkAuth()
    {
        if (isset($_GET["code"]) && !empty($_GET["code"])) {

            $user_id = $this->dingtalkCode($_GET["code"]);//用免登code校验用户并获取用户OAid
            if (isset($user_id["code"]) && is_array($user_id["code"])) {
                $code     = $user_id['code'][0];
                $message  = urlencode(trans("dingtalk.$code"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($user_id);//查看用户是否有手机端授权
            if (!empty($power["code"]) && !empty($power)) {
                $message  = trans('dingtalk.phone_allowed_access');
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            // $user = app($this->userService)->getUserAllData($user_id); //获取所有用户信息,不可以用这个方法要用getLoginUserInfo
            $user = app($this->userService)->getLoginUserInfo($user_id);
            if (!$user) {
                return ['code' => ['0x120006', 'dingtalk']];
            }
            $platform = isset($_GET["pc"]) && $_GET["pc"] == 1 ? "dingTalkPC" : "dingTalk";
            app($this->authService)->systemParams = app($this->authService)->getSystemParam();
            $registerUserInfo                     = app($this->authService)->generateInitInfo($user, "zh-CN", $platform, false);// 此处将平台参数传入来获取相应的菜单
            if (empty($registerUserInfo) || empty($registerUserInfo['token'])) {
                return ['code' => ['0x120006', 'dingtalk']];
            }
            setcookie("token", $registerUserInfo['token'], time() + 3600, "/");
            setcookie("loginUserId", $user_id, time() + 3600, "/");//缓存用户token与用户id
            $dingtalk = $this->getDingTalk();
            if (!$dingtalk) {
                $message  = urlencode(trans("dingtalk.0x120009"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $domain        = $dingtalk->domain;
            $target_module = "";
            $type      = isset($_COOKIE["state_url"]) ? $_COOKIE["state_url"] : "";
            $state_url = "";
            setcookie('state_url', '', 0);

            if ($type == "" && $platform == "dingTalkPC") {
//                pc钉钉进入此处
                $application_url = $domain . "/eoffice10/client/web/ding-talk#token=" . $registerUserInfo['token'];
//                dd($application_url);
                header("Location: $application_url");
                exit;
            }
            if (!$type) {
                $state_url = "";
            } else if (is_numeric($type)) {
                $target = "";
                // 0 - 4 不在考虑范围
                if ($platform == "dingTalk") {
                    $dingtalk  = config('eoffice.wechat');
                    $state_url = isset($dingtalk[$type]) ? $dingtalk[$type] : "";
                    if (!$state_url) {
                        $state_url = "";
                    }
                    // dd($state_url);//此处获取到了/home/application
                    //跳转到默认的模块
                    $applistAuthIds = config('eoffice.applistAuthId');
                    $auth_id        = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                    if ($auth_id) {
                        $state_url     = "";
                        $target_module = $auth_id;
                        //  setcookie("target_module", $auth_id, time() + 3600, "/");
                    }
                } else {
                    $applistAuthIds = config('eoffice.applistAuthId');
                    $menuIds        = config('eoffice.workwechat');
                    $module_id      = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                    $menu_id        = isset($menuIds[$type]) ? $menuIds[$type] : "";
                    if (!empty($module_id) && !empty($menu_id)) {
                        $state_url = "&module_id=" . $module_id . "&menu_id=" . $menu_id;
                    } else {
                        $state_url = "";
                    }
                }

            } else {
                $json = json_decode($type, true);
                if ($json) {
                    //异构系统特殊处理
                    app($this->unifiedMessageService)->unifiedMessageLocation($json,$user_id);
//                    if (isset($json['module']) && is_string($json['module'])) {
//                        $heterogeneous = strpos($json['module'], 'heterogeneous_');
//                        if ($heterogeneous === 0) {
//                            $params = isset($json['params']) ? $json['params'] : '';
//                            $param['system_id'] = $params['heterogeneous_system_id'] ? $params['heterogeneous_system_id'] : '';
//                            $param['message_id'] = $params['message_id'] ? $params['message_id'] : '';
//                            $heterogeneousSystemInfo = app($this->heterogeneousSystemService)->getDomainReadMessage($param, $user_id);
//                            if (isset($heterogeneousSystemInfo['code'])) {
//                                $message = urlencode(trans("unifiedMessage.0x000022"));
//                                $errorUrl = integratedErrorUrl($message);
//                                header("Location: $errorUrl");
//                                exit;
//                            }
//                            $domain = $heterogeneousSystemInfo['pc_domain'];
//                            $address = isset($params['pc_address']) ? $params['pc_address'] : '';
//                            $url = $domain . $address;
//                            header("Location: $url");
//                            exit();
//                        }
//                    }
                    //设置已读
                    readSmsByClient($user_id, $json);
                    setcookie("reminds", $type, time() + 3600, "/");
                    $state_url = ""; //error
                }
                $target    = "";
                $module    = isset($json['module']) ? $json['module'] : '';
                $action    = isset($json['action']) ? $json['action'] : '';
                $params    = isset($json['params']) ? json_encode($json['params']) : '';
                $state_url = "module=" . $module . "&action=" . $action . "&params=" . $params;
                if ($state_url) {
                    $application_url = $domain . "/eoffice10/client/mobile#" . $state_url . "&platform=" . $platform . $target . "&token=" . $registerUserInfo['token'];
                } else {
                    $application_url = $domain . "/eoffice10/client/mobile#" . "platform=" . $platform . $target . "&token=" . $registerUserInfo['token'];
                }
                header("Location: $application_url");
                exit;
            }
            if ($target_module) {
                //$target = "&target_module=" . $target_module; 10.0
                $target = "&module_id=" . $target_module;
            }
            if ($platform == "dingTalk") {
                // $application_url = $domain . "/eoffice10/client/mobile#" . $state_url . "&platform=" . $platform . $target . "&token=" . $registerUserInfo['token'];
                // 在2019.11.26改动，之前的是10.0的路由会导致具体模块跳转无效
                $application_url = $domain . "/eoffice10/client/mobile#target_url=" . $state_url . "&platform=" . $platform . $target . "&token=" . $registerUserInfo['token'];

            } else {
                $application_url = $domain . "/eoffice10/client/web" . $state_url . $target . "&token=" . $registerUserInfo['token'];
            }
            // dd($state_url); //  /home/application
//            dd($application_url);
            header("Location: $application_url");
            exit;
        }
    }

    //企业手机版钉钉接入
//    钉钉统一接入函数
    public function dingtalkAccess($data)
    {
        //微信模块ID 499 企业微信107  钉钉应用 120
//        获取可访问的模块
        $auhtMenus = app($this->empowerService)->getPermissionModules();// 此处返回的是系统授权模块

        if (isset($auhtMenus["code"])) {
//            报错
            $code     = $auhtMenus["code"];
            $message  = urlencode(trans("register.$code"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        } else {
            //能获取到授权信息
            if (!in_array(120, $auhtMenus)) {
//                没有钉钉模块报错
                $message  = trans('dingtalk.Authorization_expires');
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
        }

        $type    = isset($data["type"]) ? $data["type"] : "-1";//入口的模块名
        $reminds = isset($data["reminds"]) ? $data["reminds"] : "";//提醒方式
        $isPc    = isset($data["pc"]) && $data["pc"] == 1 ? 1 : 0;// pc端标记
        if ($isPc == 0) {
            $reminds = base64_decode($reminds);
        }
        if ($type == 0) {
            $state_url = $reminds;
        } else {
            if ($type == -1) {
                $state_url = "";
            } else {

                $state_url = $type;
                // 0 - 4 不在考虑范围
                $applistAuthIds = config('eoffice.applistAuthId');
                $auth_id        = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                if ($auth_id && $auth_id <= 1000) {
                    // 此处修改为系统需要授权的模块才校验
                    if (!in_array($auth_id, $auhtMenus)) {

                        $message  = trans("dingtalk.module_authorization_expires");
                        $errorUrl = integratedErrorUrl($message);
                        header("Location: $errorUrl");
                        exit;
                    }
                }
            }
        }

        $dingtalk = $this->getDingTalk();//获取oa钉钉的配置
        if (!$dingtalk) {
            $message  = urlencode(trans("dingtalk.0x120009"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        $corpid = $dingtalk->corpid;
        $domain = $dingtalk->domain;
        $secret = $dingtalk->secret;

        if (!($corpid && $domain)) {
            $message  = urlencode(trans("dingtalk.0x120009"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        setcookie("state_url", $state_url, time() + 3600, "/");
//        跳转至前端验证jsapi鉴权
        $res = $this->dingtalkFilter($isPc);
        if(isset($res['code'])){
            return $res;
        }

        exit;
    }

    //钉钉PC版入口
    public function pcDingtalkAccess($data)
    {
        $data["pc"] = 1;
        $this->dingtalkAccess($data);
    }

    public function dingtalkFilter($isPc)
    {
        $getConfig = app($this->dingTalk)->dingtalkSignpackage();
//        添加判断code的校验来得到签名处返回的错误，例如IP修改的白名单问题
        if(isset($getConfig['code'])){
            return ['code'=>[$getConfig['code'][0],'dingtalk']];
        }
        if ($isPc) {
            include_once './dingtalk/indexpc.php';
        } else {
            include_once './dingtalk/index.php';
        }

        exit;
    }

    //工作台设置的入口：
    public function dingtalkIndex($data = [])
    {
        //微信模块ID 499 企业微信107  钉钉应用 120
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (isset($auhtMenus["code"])) {
            $code     = $auhtMenus["code"];
            $message  = urlencode(trans("register.$code"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        } else {
            //能获取到授权信息
            if (!in_array(120, $auhtMenus)) {
                $message  = trans('dingtalk.Authorization_expires');
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
        }

        $dingtalk = $this->getDingTalk();
        if (!$dingtalk) {
            $message  = urlencode(trans("dingtalk.0x120009"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        $corpid = $dingtalk->corpid;
        $domain = $dingtalk->domain;
        $secret = $dingtalk->secret;

        if (!($corpid && $domain)) {
            $message  = urlencode(trans("dingtalk.0x120009"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }

        $getConfig = app($this->dingTalk)->dingtalkSignpackage();
        if(isset($getConfig['code'])){
            return $getConfig;
        }

        include_once './dingtalk/dingwork.php';
        exit;
    }

    //跳转到工作台页面
    public function dingtalkAuthWork($data = [])
    {

        if (isset($_GET["code"]) && !empty($_GET["code"])) {
            $user_id = $this->dingtalkCode($_GET["code"]);
            if (isset($user_id["code"]) && is_array($user_id["code"])) {
                $code     = $user_id['code'][0];
                $message  = urlencode(trans("dingtalk.$code"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($user_id);
            if (!empty($power["code"]) && !empty($power)) {
                $message  = trans('dingtalk.phone_allowed_access');
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $user = app($this->userService)->getUserAllData($user_id);
            if (!$user) {
                return ['code' => ['0x120006', 'dingtalk']];
            }
            app($this->authService)->systemParams = app($this->authService)->getSystemParam();
            $registerUserInfo                     = app($this->authService)->generateInitInfo($user, "zh-CN", '', false);
            if (empty($registerUserInfo) || empty($registerUserInfo['token'])) {
                return ['code' => ['0x120006', 'dingtalk']];
            }
            setcookie("token", $registerUserInfo['token'], time() + 3600, "/");
            setcookie("loginUserId", $user_id, time() + 3600, "/");

            $dingtalk = $this->getDingTalk();
            if (!$dingtalk) {
                $message  = urlencode(trans("dingtalk.0x120009"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }

            $domain          = $dingtalk->domain;
//            问号改成#号
//            $application_url = $domain . "/eoffice10/client/mobile/home/dingwork?platform=dingTalk" . "&token=" . $registerUserInfo['token'];
            // $application_url = $domain . "/eoffice10/client/mobile/home/dingwork/#platform=dingTalk" . "&token=" . $registerUserInfo['token'];旧的也可以使用
            $application_url = $domain . "/eoffice10/client/mobile/home/dingwork#platform=dingTalk" . "&token=" . $registerUserInfo['token'];
            header("Location: $application_url");
            exit;
        }
    }

    public function dingtalkUserList()
    {
        return app($this->dingTalk)->dingtalkUserList();
    }

    public function showDingtalk($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $auhtMenus = app($this->empowerService)->getPermissionModules();

        // $power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($userId);

        $dingtalk = $this->getDingTalk();

        if (isset($auhtMenus["code"]) || !in_array(120, $auhtMenus) || empty($dingtalk) || $dingtalk->is_push == 0) {
            return "false";
        } else {
            return "true";
        }

    }
    public function dingtalkAttendanceSync($data,$userId = ''){
        // 需要判断钉钉配置是否存在
        $config = app($this->dingtalkTokenRepository)->getDingTalk();
        if(empty($config)){
            return ['code' => ['dingtalk_config_is_empty', 'dingtalk']];
        }
        Queue::push(new DingtalkAttendanceSyncJob($data,$userId));
        // $this->dingtalkSync($data,$userId);
    }

    public function dingtalkSync($data,$toUserId)
    {
        $user    = app($this->userRepository)->getUserIdByFields(['user_id']);
        $userIds = array_column($user, "user_id");
        $userId  = [];
        foreach ($userIds as $key => $value) {
            $value = app($this->dingtalkUserRepository)->getDingTalkUserIdById($value);
            if (!empty($value)) {
                $userId[] = $value['userid'];
            }

        }
        if (count($userId) > 50) {
            $userIds = array_chunk($userId, 50);
            foreach ($userIds as $user_id) {
                $syncResult = $this->connectDingtalk($data, $user_id);
                if($syncResult == false){
                    $successLog = false;
                }
            }
            if(isset($successLog) && $successLog == false){
                $syncResult = false;
            }
        } else {
            $syncResult = $this->connectDingtalk($data, $userId);
        }
        if($syncResult === true){
            $insertData = [
                'status'    => 'ok',
                'msg'       => 'ok',
                'sync_type' => $data['type'] == 'today' ? 1 : 2,
            ];
            app($this->dingtalkLogsRepository)->insertData($insertData);
        }
        
        if($toUserId){
            $sendData = [
                'toUser'      => $toUserId,
                // 'toUser'      => own('user_id'),
                'remindState' => '',//前端路由
                'remindMark'  => 'dingtalk-complete',//需要执行脚本
    //            'remindMark'  => 'attendancemachine-complete',//需要执行脚本
    //            'sendMethod'  => ['sms'],
    //            'isHand'      => true,
                'content'     => trans("dingtalk.dingtalk_synchronized"),
                'stateParams' => [],//跳转路由
            ];
            Eoffice::sendMessage($sendData);
        }
        
    }

    public function connectDingtalk($data, $userId)
    {
        if(empty($userId)){
            // 同步人员是空的
            return true;
        }
        $access_token = $this->getAccessToken();
        $url          = "https://oapi.dingtalk.com/attendance/list?access_token=$access_token"; // 改成获取打卡结果接口
        // $url          = "https://oapi.dingtalk.com/attendance/listRecord?access_token=$access_token";
        $today        = date("Y-m-d", time());
        if ($data['type'] == "today") {
            $attendanceList = [];
            $start_date    = $today . " 00:00:00";
            $end_date      = $today . " 23:59:59";
            $offset = 0;
            $limit = 50;
            $i = 0;
            do{
                $msgTemp       = array(
                    'userIdList'       => $userId,
                    'workDateFrom' => $start_date,
                    'workDateTo'   => $end_date,
                    'offset'  => $offset+$limit*$i,
                    'limit'   => $limit,
                );
                $msg           = urldecode(json_encode($msgTemp));
                $weixinConnect = getHttps($url, $msg);
                $weixinConnect = json_decode($weixinConnect, true);
                if (empty($weixinConnect)) {
                    $insertData = [
                        'status'    => 'error',
                        'msg'       => '连接错误',
                        'sync_type' => 1,
                    ];
                    app($this->dingtalkLogsRepository)->insertData($insertData);
                    return false;
                }
                if (isset($weixinConnect['errcode']) && $weixinConnect['errcode'] != 0) {
                    $insertData = [
                        'status'    => 'error',
                        'msg'       => $weixinConnect['errmsg'],
                        'sync_type' => 1,
                    ];
                    app($this->dingtalkLogsRepository)->insertData($insertData);
                    return false;
                }
                if (isset($weixinConnect['recordresult'])) {
                    $result = $weixinConnect['recordresult'];
                    $attendanceList = array_merge($attendanceList,$result);
                }
                $i++;

            }while(count($result) == $limit && $i<=10);
            $this->insertAttendance($attendanceList,[$start_date,$end_date]);
            return true;
        } else {
            $month_first = date('Y-m-01', strtotime($today));
            $month_last  = date('Y-m-d', strtotime("$month_first +1 month -1 day"));
            $day         = date('t', strtotime($today));
            $times       = floor($day / 5);
            $array       = [];
            for ($i = 0; $i < $times; $i++) {
                if ($i == 0) {
                    $before = $month_first;
                } else {
                    $before = date('Y-m-d', strtotime("$month_first +1 day"));
                }
                $month_first = date('Y-m-d', strtotime("$month_first +5 day"));
                if ($i == $times - 1) {
                    $month_first = $month_last;
                }
                array_push($array, $before . "+ " . $month_first);
            }
            foreach ($array as $key => $value) {
                $attendanceList = [];
                $date_format = explode("+", $value);
                $start_date  = $date_format[0] . " 00:00:00";
                $end_date    = $date_format[1] . " 23:59:59";
                $offset = 0;
                $limit = 50;
                $i = 0;
                $result = [];
                do{
                    $msgTemp     = array(
                        'userIdList'       => $userId,
                        'workDateFrom' => $start_date,
                        'workDateTo'   => $end_date,
                        'offset'  => $offset+$limit*$i,
                        'limit'   => $limit,
                    );
                    // var_dump($msgTemp);
                    $msg           = urldecode(json_encode($msgTemp));
                    $weixinConnect = getHttps($url, $msg);
                    $weixinConnect = json_decode($weixinConnect, true);
                    if (empty($weixinConnect)) {
                        $insertData = [
                            'status'    => 'error',
                            'msg'       => '连接错误',
                            'sync_type' => 2,
                        ];
                        app($this->dingtalkLogsRepository)->insertData($insertData);
                        continue;
                    }
                    if (isset($weixinConnect['errcode']) && $weixinConnect['errcode'] != 0) {
                        $insertData = [
                            'status'    => 'error',
                            'msg'       => $weixinConnect['errmsg'],
                            'sync_type' => 2,
                        ];
                        app($this->dingtalkLogsRepository)->insertData($insertData);
                        continue;
                    }
                    if (isset($weixinConnect['recordresult'])) {
                        
                        $result = $weixinConnect['recordresult'];
                        $attendanceList = array_merge($attendanceList,$result);
                    }
                    $i++;
                    // var_dump($result);
                }while(count($result) == $limit && $i<=10);
                // var_dump($attendanceList);
                $this->insertAttendance($attendanceList,[$start_date,$end_date]);
            }
            return true;
        }
    }

    public function dingtalkLogs($param)
    {
        $logs  = app($this->dingtalkLogsRepository)->dingtalkLogsGet($param);
        $total = app($this->dingtalkLogsRepository)->dingtalkLogsCount();
        return ['total' => $total, 'list' => $logs];
    }

    public function deleteDingtalkLog($id)
    {
        return app($this->dingtalkLogsRepository)->deleteDingtalkLog($id);

    }

    public function saveSyncTime($data)
    {
        // 需要判断钉钉配置是否存在
        $config = app($this->dingtalkTokenRepository)->getDingTalk();
        if(empty($config)){
            return ['code' => ['dingtalk_config_is_empty', 'dingtalk']];
        }
        if (isset($data['update_time']) && is_array($data['update_time'])) {
            $data['update_time'] = implode(",", $data['update_time']);
        }
        $update = [
            'is_auto'     => isset($data['is_auto']) ? $data['is_auto'] : 0,
            'update_time' => isset($data['update_time']) ? $data['update_time'] : '',
        ];
        app($this->dingtalkTokenRepository)->update($update);
    }

    public function insertAttendance($result,$startToEnd)
    {
        $attendance = [];
        // dd($result);
        foreach ($result as $key => $value) {
            if (isset($value['checkType']) && isset($value['timeResult']) && $value['timeResult'] != 'NotSigned') { // 去除多排班情况下的未打卡数据
                $userid                                  = $value['userId'];
                $userid                                  = app($this->dingtalkUserRepository)->getDingTalkOaIdById($userid);
                $oa_id                                   = $userid['oa_id'];
                $date                                    = date("Y-m-d", $value['workDate'] / 1000);
                $attendance[$oa_id . $date]['user_id']   = $oa_id;
                $attendance[$oa_id . $date]['sign_date'] = $date;
                $checkDate                               = date("Y-m-d H:i", $value['baseCheckTime'] / 1000); // 理论打卡时间，去除秒为了跟OA排班时间一致
                $attendance[$oa_id . $date][$checkDate]  = ['time' => date("Y-m-d H:i:s", $value['userCheckTime'] / 1000),'type' => $value['checkType']];
            }
        }
        // $shiftInfo = app($this->attendanceBaseService)->schedulingMapWithUserIdsByDate('2021-06-23',['admin']);
        // $shift = app($this->attendanceBaseService)->getShiftById($shiftInfo['admin']['shift_id'])->toArray();
        // $shiftTime = app($this->attendanceBaseService)->getShiftTimeById($shiftInfo['admin']['shift_id'])->toArray();
        // dd($shiftTime);
        // 正常班
        // "admin" => array:2 [
        //     "scheduling_id" => 1
        //     "shift_id" => 6
        //   ]\
        
        if(empty($attendance)){
            // 考勤数据为空直接退出
            return true;
        }
        // 此处需要按照新考勤的方法导入考勤数据，钉钉返回的数据结构简单不支持多排班
//        [
//            'admin' => [
//                '2019-01-02' => [
//                    ['sign_date' => '2019-01-02', 'sign_times' => 1, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8 , 'in_address' => '' ,'out_address' => ''],
//                    ['sign_date' => '2019-01-02', 'sign_times' => 2, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8]
//                ],
//                '2019-01-02' => [
//                    ['sign_date' => '2019-01-02', 'sign_times' => 1, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8],
//                    ['sign_date' => '2019-01-02', 'sign_times' => 2, 'sign_in_time' => '', 'sign_out_time' =>'', 'platform' => 8]
//                ]
//            ]
//        ];

        // dd($attendance);
        $batchSignData = [];
        foreach ($attendance as $value) {
            $signDate = date('Y-m-d', strtotime($value['sign_date']));  //格式化考勤日期
            $userId   = $value['user_id'];
            $shiftInfo = app($this->attendanceBaseService)->schedulingMapWithUserIdsByDate($signDate,[$userId]);// 获取排班信息
            if($shiftInfo[$userId] != null){
                // 有排班的工作日打卡
                $shift = app($this->attendanceBaseService)->getShiftById($shiftInfo[$userId]['shift_id'])->toArray();
                $shiftTime = app($this->attendanceBaseService)->getShiftTimeById($shiftInfo[$userId]['shift_id'])->toArray();
                if($shift['shift_type'] == 1){
                    // var_dump($value);
                    // 正常班
                    //  [
                    //   0 => array:2 [
                    //     "sign_in_time" => "09:00"
                    //     "sign_out_time" => "18:00"
                    //   ]
                    // ]
                    // 取出正常班的时间
                    $signInTime = $signDate.' '.$shiftTime[0]['sign_in_time'];
                    $signOutTime = $signDate.' '.$shiftTime[0]['sign_out_time'];
                    // var_dump($value[$signInTime]);
                    // 打卡记录表数据的插入
                    if (isset($value[$signInTime]['time'])) {
                        $recordLog = [
                            'checktime' => $value[$signInTime]['time'],
                            'user_id' => $value['user_id'] ?? '',
                            'sign_date' => $value['sign_date'] ?? '',
                            'type' => 1,
                            'platform' => 10,
                        ];
                        app($this->attendanceMachineService)->checkAndInsertSimpleRecord($recordLog);
                    }
                    if (isset($value[$signOutTime]['time'])) {
                        $recordLog = [
                            'checktime' => $value[$signOutTime]['time'],
                            'user_id' => $value['user_id'] ?? '',
                            'sign_date' => $value['sign_date'] ?? '',
                            'type' => 2,
                            'platform' => 10,
                        ];
                        app($this->attendanceMachineService)->checkAndInsertSimpleRecord($recordLog);
                    }

                    // 判断只有签退没有签到的情况，跨天班，签到签退分两组获取的情况
                    if(empty($value[$signInTime])){
                        // 查询该人是否有签到
                        $existAttendanceRecord = app($this->attendanceRecordsRepository)->getOneAttendRecord(['sign_date' => [$signDate], 'user_id' => [$userId], 'sign_times' => [1]]);
                        if($existAttendanceRecord){
                            $existAttendanceRecord = $existAttendanceRecord->toArray();
                            if(!empty($existAttendanceRecord['sign_in_time'])){
                                $value[$signInTime]['time'] = $existAttendanceRecord['sign_in_time'];
                                $value[$signInTime]['in_address'] = $existAttendanceRecord['in_address'];
                            }
                        }
                    }
                    $batchSignData[$userId][$signDate][] = [
                        'sign_date' => $signDate,
                        'sign_nubmer' => 1,
                        'sign_in_time' => $value[$signInTime]['time'] ?? '',
                        'sign_out_time' => $value[$signOutTime]['time'] ?? '',
                        // 'in_address' => $value[$signInTime]['in_address'] ?? '' ,
                        // 'out_address' => $value['out_address'] ?? '',
                        'platform' => 10
                    ];
                    // dd($batchSignData);
                }
                
                if($shift['shift_type'] == 2){
                    // 多排班
                    //  [
                    //   0 => array:2 [
                    //     "sign_in_time" => "09:00"
                    //     "sign_out_time" => "12:00"
                    //   ],
                    //   1 => array:2 [
                    //     "sign_in_time" => "13:00"
                    //     "sign_out_time" => "18:00"
                    //   ]
                    // ]
                    foreach($shiftTime as $index => $shiftTimeInfo){
                        $signInTime = $signDate.' '.$shiftTimeInfo['sign_in_time'];
                        $signOutTime = $signDate.' '.$shiftTimeInfo['sign_out_time'];
                        // 打卡记录表数据的插入
                        if (isset($value[$signInTime]['time'])) {
                            $recordLog = [
                                'checktime' => $value[$signInTime]['time'],
                                'user_id' => $value['user_id'] ?? '',
                                'sign_date' => $value['sign_date'] ?? '',
                                'type' => 1,
                                'platform' => 10,
                            ];
                            app($this->attendanceMachineService)->checkAndInsertSimpleRecord($recordLog);
                        }
                        if (isset($value[$signOutTime]['time'])) {
                            $recordLog = [
                                'checktime' => $value[$signOutTime]['time'],
                                'user_id' => $value['user_id'] ?? '',
                                'sign_date' => $value['sign_date'] ?? '',
                                'type' => 2,
                                'platform' => 10,
                            ];
                            app($this->attendanceMachineService)->checkAndInsertSimpleRecord($recordLog);
                        }

                        if(!empty($value[$signInTime]['time']) || !empty($value[$signOutTime]['time'])){
                            $batchSignData[$userId][$signDate][] = [
                                'sign_date' => $signDate,
                                'sign_nubmer' => $index + 1,
                                'sign_in_time' => $value[$signInTime]['time'] ?? '',
                                'sign_out_time' => $value[$signOutTime]['time'] ?? '',
                                // 'in_address' => $value['in_address'] ?? '' ,
                                'platform' => 10
                            ];
                        }
                    }

                    // 判断只有签退没有签到的情况，跨天班，签到签退分两组获取的情况,多排班不支持
                    // if(empty($value['sign_in_time'])){
                    //     // 查询该人是否有签到
                    //     $existAttendanceRecord = app($this->attendanceRecordsRepository)->getOneAttendRecord(['sign_date' => [$signDate], 'user_id' => [$userId], 'sign_times' => [1]]);
                    //     if($existAttendanceRecord){
                    //         $existAttendanceRecord = $existAttendanceRecord->toArray();
                    //         if(!empty($existAttendanceRecord['sign_in_time'])){
                    //             $value['sign_in_time'] = $existAttendanceRecord['sign_in_time'];
                    //             $value['in_address'] = $existAttendanceRecord['in_address'];
                    //         }
                    //     }
                    // }
                }
                
                
            }else{
                // 非工作日打卡，不支持，钉钉没有
                // $batchSignData[$userId][$signDate][] = [
                //     'sign_date' => $signDate,
                //     'sign_nubmer' => $data['sign_nubmer'] ?? 1,
                //     'sign_in_time' => $data['sign_in_time'],
                //     'sign_out_time' => $data['sign_out_time'],
                //     'platform' => $data['platform']
                // ];
            }
            
        }
        $batchSignData = app($this->attendanceMachineService)->parseRecordExist($batchSignData);
        $startToEnd[0] = date('Y-m-d', strtotime("$startToEnd[0] -1 day"));
        $startToEnd[1] = date('Y-m-d', strtotime($startToEnd[1]));
        // dd($startToEnd);
        $res = app($this->attendanceService)->batchSign($batchSignData,$startToEnd[0],$startToEnd[1]);

        return true;
    }

    //钉钉导出
    public function dingtalkExport()
    {
        //获取所有的部门
        $depart = app($this->departmentRepository)->getAllDepartment();
        $depts  = [];
        foreach ($depart as $v) {
            $depts[$v['dept_id']] = $v['dept_name'];
        }
        $param['phone_number'] = 1;
        $userList              = app($this->userRepository)->getUserList($param);
        $data                  = [];
        $result                = [];

        foreach ($userList as $k => $v) {
            $phonenumber = $v['user_has_one_info']['phone_number'];
            if (!$phonenumber) {
                continue;
            }
            $data['user_id'] = $v['user_id'];
            //部门
            $arr_parent_id = $v['user_has_one_system_info']['user_system_info_belongs_to_department']['arr_parent_id'];
            $dept_name     = $v['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'];
            $str           = "";
            if ($arr_parent_id) {
                $sorts = explode(",", $arr_parent_id);
                foreach ($sorts as $vsort) {
                    if ($vsort) {
                        $str .= $depts[$vsort] . "-";
                    }
                }
            }
            $data['dept_name'] = $str . $dept_name;

            $roleName = [];
            foreach ($v['user_has_many_role'] as $roleKey => $roleValue) {
                $roleName[] = $roleValue['has_one_role']['role_name'];
            }

            $data['priv_name'] = join(",", $roleName);
            $data['user_name'] = $v['user_name'];
            $data['sex']       = $v['user_has_one_info']['sex'] == 1 ? "男" : "女";
            $data["job_num"]   = $v["user_job_number"];
            $data['mobile_no'] = $phonenumber;
            $data['email']     = $v['user_has_one_info']['email'];
            $data['dept_tel']  = $v['user_has_one_info']['dept_phone_number'];
            $data['notes']     = $v['user_has_one_info']['notes'];
            $result[]          = $data;
        }
        $file        = "dingtalk" . DIRECTORY_SEPARATOR . "dingtalk.xls";
        $objExcel    = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $objActSheet = $objExcel->getActiveSheet();
        foreach ($result as $key => $value) {
            $k = $key + 4;
            $objActSheet->setCellValue('A' . $k, $value['user_id']);
            $objActSheet->setCellValue('B' . $k, $value['dept_name']);
            $objActSheet->setCellValue('C' . $k, $value['priv_name']);
            $objActSheet->setCellValue('D' . $k, $value['user_name']);
            $objActSheet->setCellValue('E' . $k, " " . $value['job_num']);
            $objActSheet->setCellValue('F' . $k, "");
            $objActSheet->setCellValue('G' . $k, $value['mobile_no']);
            $objActSheet->setCellValue('H' . $k, $value['email']);
            $objActSheet->setCellValue('I' . $k, $value['dept_tel']);
            $objActSheet->setCellValue('J' . $k, "");
            $objActSheet->setCellValue('K' . $k, $value['notes']);
            $objActSheet->setCellValue('L' . $k, "");
        }
        $filename = "e-office_" . trans('dingtalk.dingtalk_template') . " _" . date("Ymd") . time();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objExcel, 'Xls');
        $objWriter->save('php://output');
        exit;
    }

    public function getDingtalkUserList($param)
    {
        $param    = $this->parseParams($param);
        $param['type'] = $param['type'] ?? 'all';

        $responseData['total'] = $this->getDingtalkUserCount($param);
        $param['returntype'] = 'array';
        $responseData['list'] = app($this->dingtalkUserRepository)->getDingtalkUserList($param);
        return $responseData;
    }

    public function getDingtalkUserCount($param){
        $param['page'] = 0 ;
        $param['returntype'] = 'count';
        return app($this->dingtalkUserRepository)->getDingtalkUserList($param);
    }

    public function deleteBindByOaId($userId)
    {
        if(empty($userId)){
            return false;
        }
        // 判断是否有该用户的关联关系
        $user = app($this->dingtalkUserRepository)->checkOaUserId($userId);
        if($user){
            return app($this->dingtalkUserRepository)->deleteByOaId($userId);
        }

    }

    // 钉钉组织架构同步排除内容
    /**
     * @param data 包含部门角色人员的排除id
     *
     */
    public function addException($data){
        // 先清空表
        app($this->dingtalkOrganizationSyncExceptionRepository)->truncateTable();
        $depIds = $data['depIds'] ?? '';
        $roleIds = $data['roleIds'] ?? '';
        $userIds = $data['userIds'] ?? '';
        if(!empty($depIds)){
            app($this->dingtalkOrganizationSyncExceptionRepository)->addException($depIds,'department');
        }
        if(!empty($roleIds)){
            app($this->dingtalkOrganizationSyncExceptionRepository)->addException($roleIds,'role');
        }
        if(!empty($userIds)){
            app($this->dingtalkOrganizationSyncExceptionRepository)->addException($userIds,'user');
        }
    }

    // 获取同步排除内容
    /**
     * @param type 排除类型
     *
     */
    public function getException($type){
        if(!empty($type)){
            app($this->dingtalkOrganizationSyncExceptionRepository)->getException($type);
        }
    }

    // 获取钉钉部门列表
    public function getDingtalkDepartmentList($data,$parentId = 1){
        $fetchChild = $data['fetchChild'] ?? 'false';
        $result = app($this->dingTalk)->getDingtalkDepartmentList($fetchChild,$parentId);
        return $result;
    }

    // 同步钉钉与OA部门
    public function addDingtalkDepartmentRelation($data){
        $dingtalkDeptId = $data['dingtalk_dept_id'] ?? '';
        $oaDeptId = $data['oa_dept_id'] ?? '';
        $dingtalkDeptName = $data['dingtalk_dept_name'] ?? '';
        $oaDeptName = $data['oa_dept_name'] ?? '';
        if(!empty($dingtalkDeptId) && !empty($oaDeptId) && !empty($dingtalkDeptName) && !empty($oaDeptName)){
            $insertData = [
                'dingtalk_dep_id' => $dingtalkDeptId,
                'oa_dep_id' => $oaDeptId,
                'dingtalk_dep_name' => $dingtalkDeptName,
                'oa_dep_name' => $oaDeptName,
            ];
            $result = app($this->dingtalkOrganizationSyncDepartmentRepository)->addDingtalkDepartmentRelation($insertData);
            if(!$result){
                return ['code' => ['sync_already', 'dingtalk']];
            }
            return $result;
        }
    }

    // 同步钉钉与OA角色
    public function addDingtalkRoleRelation($data){
        $dingtalkRoleId = $data['dingtalk_role_id'] ?? '';
        $oaRoleId = $data['oa_role_id'] ?? '';
        $dingtalkRoleName = $data['dingtalk_role_name'] ?? '';
        $oaRoleName = $data['oa_role_name'] ?? '';
        if(!empty($dingtalkRoleId) && !empty($oaRoleId) && !empty($dingtalkRoleName) && !empty($oaRoleName)){
            $insertData = [
                'dingtalk_role_id' => $dingtalkRoleId,
                'oa_role_id' => $oaRoleId,
                'dingtalk_role_name' => $dingtalkRoleName,
                'oa_role_name' => $oaRoleName,
            ];
            $result = app($this->dingtalkOrganizationSyncRoleRepository)->addDingtalkRoleRelation($insertData);
            if(!$result){
                return ['code' => ['sync_already', 'dingtalk']];
            }
            return $result;
        }
    }

    // 同步钉钉与OA角色
    public function addDingtalkUserRelation($data){
        $dingtalkUserId = $data['dingtalk_user_id'] ?? '';
        $oaUserId = $data['oa_user_id'] ?? '';
        $dingtalkUserName = $data['dingtalk_user_name'] ?? '';
        $oaUserName = $data['oa_user_name'] ?? '';
        if(!empty($dingtalkUserId) && !empty($oaUserId) && !empty($dingtalkUserName) && !empty($oaUserName)){
            $insertData = [
                'dingtalk_user_id' => $dingtalkUserId,
                'oa_user_id' => $oaUserId,
                'dingtalk_user_name' => $dingtalkUserName,
                'oa_user_name' => $oaUserName,
            ];
            $result = app($this->dingtalkOrganizationSyncUserRepository)->addDingtalkUserRelation($insertData);
            if(!$result){
                return ['code' => ['sync_already', 'dingtalk']];
            }
            return $result;
        }
    }

    // 获取钉钉角色列表
    public function getRoleList(){
        $result = app($this->dingTalk)->getRoleList();
        return $result;
    }

    // 保存钉钉组织架构同步配置
    public function saveDingtalkOASyncConfig($data){
        if(own('user_id') != 'admin'){
            return ['code' => ['0x120011', 'dingtalk']];
        }
        if(!isset($data['switch'])){
            // 异常错误
            return ['code' => ['0x120001','dingtalk']];
        }
        if($data['switch'] && empty($data['config']['depend_on'])){
            // 如果开启配置但主系统未配置则报异常错误
            return ['code' => ['0x120001','dingtalk']];
        }
        $switch  = DB::table('system_params')->where("param_key", "dingtalk_OA_organization_sync_switch")->first();
        if (!empty($switch)) {
            DB::table('system_params')->where("param_key", "dingtalk_OA_organization_sync_switch")->update(['param_value' => $data['switch']]);
        } else {
            DB::table('system_params')->insert(['param_key' => "dingtalk_OA_organization_sync_switch", 'param_value' => $data['switch']]);
        }

        if(isset($data['config']['is_auto']) && $data['config']['is_auto'] == 1){
            // 开启自动同步了
            if (isset($data['config']['update_time']) && is_array($data['config']['update_time'])) {
                $updateTime = implode(",", $data['config']['update_time']);
            }
        }
        $configData = [
            'depend_on' => $data['config']['depend_on'] ,
            'update_time' => $updateTime ?? '' ,
            'is_auto' => $data['config']['is_auto'] ?? ''
        ];
        return app($this->dingtalkOrganizationSyncConfigRepository)->saveSyncConfig($configData);
    }


    // 获取钉钉组织架构同步配置
    public function getDingtalkOASyncConfig(){
        $switch = DB::table('system_params')->where("param_key", "dingtalk_OA_organization_sync_switch")->first();
        $result['switch'] = isset($switch->param_value) ? $switch->param_value : '0';
        $config = app($this->dingtalkOrganizationSyncConfigRepository)->getSyncConfig();
        $result['config'] = "";
        if(!empty($config)){
            $result['config'] = $config;
            if (isset($result['config']->update_time) && !empty($result['config']->update_time)) {
                $result['config']->update_time = explode(",", $result['config']->update_time);
            }
        }

        // $result['config'] = json_encode($result['config']);
        return $result;// 这里之前为什么不json_encode
    }


    // 获取钉钉组织架构同步定时更新时间
    public function getDingtalkSyncTime(){
        // 判断是否开启架构同步功能
        $switch = DB::table('system_params')->where("param_key", "dingtalk_OA_organization_sync_switch")->first();
        if(isset($switch->param_value) && $switch->param_value == '1'){// 这里不用判断表dingtalkOrganizationSyncConfig是否存在因为不存在这个配置不会开启
            // 开启了同步
            $config = app($this->dingtalkOrganizationSyncConfigRepository)->getSyncConfig();
            if(isset($config->is_auto) && $config->is_auto == 1 && !empty($config->update_time)){
                return explode(",", $config->update_time);
            }
        }
    }

    // OA与钉钉用户同步功能主函数
    /**
     * 同步的登录用户必须是admin。否则同步完需要重新登录，不然会有问题的
     */
    public function dingtalkOASync($loginUser){
        // 暂时没有做OA为主的同步 默认按钉钉
        if($loginUser != 'admin'){
            return ['code' => ['0x120011', 'dingtalk']];
        }
        $GLOBALS['dingtalkSyncUser'] = $loginUser;
        $switch = DB::table('system_params')->where("param_key", "dingtalk_OA_organization_sync_switch")->first();
        if(!isset($switch->param_value) || $switch->param_value != '1'){
            // 同步未开启
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOASync',
                'operation_data' => 'switch:'.json_encode($switch),
                'operation_result' => 'error',
                'operation_result_data' => trans("dingtalk.sync_is_closed"),
            ];
            $this->addDingtalkSyncLog($logData);
            $this->flushGlobalVariable();
            Redis::del('user:user_accounts');
            return ['code' => ['sync_is_closed', 'dingtalk']];
        }

        // 这里判断以哪个架构为主，因为现在只支持钉钉，因此暂不判断，到时候下面的代码都转移到另一个函数中



        // 需要增加临时角色用于用户的新增，后续将做删除，不做删除，防止有些用户真的不带角色。
        $tempRoleId = $this->addDingtalkRole();
        if(empty($tempRoleId) || isset($tempRoleId['code'])){
            // 临时角色返回错误
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOASync',
                'operation_data' => 'tempRoleId:'.json_encode($tempRoleId),
                'operation_result' => 'error',
                'operation_result_data' => trans("dingtalk.temp_role_error"),
            ];
            $this->addDingtalkSyncLog($logData);
            $this->flushGlobalVariable();
            Redis::del('user:user_accounts');
            return ['code' => ['temp_role_error', 'dingtalk']];
        }
        // 此处执行反向删除操作
        $resOrganizationReverseFilter = $this->organizationReverseFilter();
        if(isset($resOrganizationReverseFilter['code'])){
            // 反向删除错误
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOASync',
                'operation_data' => 'resOrganizationReverseFilter:'.json_encode($resOrganizationReverseFilter),
                'operation_result' => 'error',
                'operation_result_data' => trans($resOrganizationReverseFilter['code'][1].'.'.$resOrganizationReverseFilter['code'][0]),
            ];
            $this->addDingtalkSyncLog($logData);
            $this->flushGlobalVariable();
            Redis::del('user:user_accounts');
            return ['code' => $resOrganizationReverseFilter['code']];
        }

        // 处理admin的专属部门角色以避免权限问题，在反向删除后避免冲突
        $resHandAdminPower = $this->handleAdminPower();
        if(isset($resHandAdminPower['code'])){
            // 处理admin部门角色权限错误
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOASync',
                'operation_data' => 'resHandAdminPower:'.json_encode($resHandAdminPower),
                'operation_result' => 'error',
                'operation_result_data' => trans($resHandAdminPower['code'][1].'.'.$resHandAdminPower['code'][0]),
            ];
            $this->addDingtalkSyncLog($logData);
            $this->flushGlobalVariable();
            Redis::del('user:user_accounts');
            return ['code' => $resHandAdminPower['code']];
        }
        // var_dump(1);
        // 部门与人员同步
        $resSyncDepartment = $this->dingtalkOADepartmentSync($tempRoleId);
        if(!empty($resSyncDepartment['code'])){
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOASync',
                'operation_data' => 'resSyncDepartment:'.json_encode($resSyncDepartment),
                'operation_result' => 'error',
                'operation_result_data' => trans($resSyncDepartment['code'][1].'.'.$resSyncDepartment['code'][0]),
            ];
            $this->addDingtalkSyncLog($logData);
            $this->flushGlobalVariable();
            Redis::del('user:user_accounts');
            return ['code' => $resSyncDepartment['code']];
        }

        // var_dump(2);
        // 角色同步
        $resSyncRole = $this->dingtalkOARoleSync($tempRoleId);
        if(!empty($resSyncRole['code'])){
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOASync',
                'operation_data' => 'resSyncRole:'.json_encode($resSyncRole),
                'operation_result' => 'error',
                'operation_result_data' => trans($resSyncRole['code'][1].'.'.$resSyncRole['code'][0]),
            ];
            $this->addDingtalkSyncLog($logData);
            $this->flushGlobalVariable();
            Redis::del('user:user_accounts');
            return ['code' => $resSyncRole['code']];
        }
        // var_dump(3);
        // 将反向删除移到上面
        $this->userReverseFilter();
        // 发送推送消息
        $sendData = [
            'toUser'      => $loginUser,
            'remindState' => '',//前端路由
            'remindMark'  => 'dingtalk-complete',//需要执行脚本
//            'remindMark'  => 'attendancemachine-complete',//需要执行脚本
//            'sendMethod'  => ['sms'],
//            'isHand'      => true,
            'content'     => trans("dingtalk.dingtalk_organization_synchronized"),
            'stateParams' => [],//跳转路由
        ];
        Eoffice::sendMessage($sendData);


        // 记录同步成功的日志
        $logData = [
            'sync_operation_type' => 'sync',
            'operation_method' => 'dingtalkOASync',
            'operation_data' => 'success',
            'operation_result' => 'success',
            'operation_result_data' => 'success',
        ];
        $this->addDingtalkSyncLog($logData);
        $this->flushGlobalVariable();
        Redis::del('user:user_accounts');
    }

    /**
     * 增加钉钉默认角色
     */
    public function addDingtalkRole(){
        // 先查找是否存在钉钉角色的角色，不存在则新增
        $tempRoleName = trans("dingtalk.dingtalk_sync_role",[],'zh-CN');
        $param['search'] = ['role_name' => [$tempRoleName,'=']];
        $exitRole = app($this->roleService)->getLists($param);
        if(!empty($exitRole['list'][0]['role_id'])){
            $tempRoleId = $exitRole['list'][0]['role_id'];
        }else{
            $tempData = [
                'role_name' => $tempRoleName,
                'role_no'   => 0,
            ];
            $tempRoleId = app($this->roleService)->createRole($tempData);
        }
        return $tempRoleId;
    }


    /**
     * 处理admin账号的部门与角色问题，这一块可以在反向删除后处理可以避免重名问题
     */
    public function handleAdminPower(){
        // 判断admin部门是否存在,先查找是否存在admin角色，不存在则新增
        $adminDepartmentName = trans("dingtalk.admin_department",[],'zh-CN');
        $param['search'] = ['dept_name' => [$adminDepartmentName,'=']];
        $exitDepartment = app($this->departmentService)->deptTreeSearch($param);
        if(!empty($exitDepartment[0]['dept_id'])){
            $adminDepartmentId = $exitDepartment[0]['dept_id'];
        }else{
            $tempDepartmentData = [
                'dept_name' => $adminDepartmentName,
            ];
            $adminDepartmentId = app($this->departmentService)->addDepartment($tempDepartmentData,trans("dingtalk.dingtalk_organization_sync"));
            if(empty($adminDepartmentId) || isset($adminDepartmentId['code'])){
                return ['code' => ['admin_department_error', 'dingtalk']];
            }
            if(isset($adminDepartmentId['dept_id'])){
                $adminDepartmentId = $adminDepartmentId['dept_id'];
            }

        }


        // 判断admin角色是否存在,先查找是否存在admin角色，不存在则新增
        $adminRoleName = trans("dingtalk.admin_role",[],'zh-CN');
        $param['search'] = ['role_name' => [$adminRoleName,'=']];
        $exitRole = app($this->roleService)->getLists($param);
        if(!empty($exitRole['list'][0]['role_id'])){
            $adminRoleId = $exitRole['list'][0]['role_id'];
        }else{
            $tempRoleData = [
                'role_name' => $adminRoleName,
                'role_no'   => 0,
            ];
            $adminRoleId = app($this->roleService)->createRole($tempRoleData);
            if(empty($adminRoleId) || isset($adminRoleId['code'])){
                return ['code' => ['admin_role_error', 'dingtalk']];
            }
        }

        // 将部门与角色做admin绑定
        // 将admin角色绑定在admin上
        $roledata = [
            'user_id' => 'admin',
            'role_id' => (string)$adminRoleId,// 支持多个以,隔开的id
        ];
        $resAddUserRole = app($this->roleService)->addUserRole($roledata, true);
        if(isset($resAddUserRole['code'])){
            return $resAddUserRole;
        }
        // 给admin角色给与权限
        $res = app($this->menuRepository)->entity->select('menu_id')->get()->toArray();
        $menuIds = '';
        // 封装菜单列表ids
        if(count($res)>0){
            $menuIdArray = [];
            foreach($res as $value){
                // 取出所有menu_id
                $menuIdArray[] = $value['menu_id'];
            }
            $menuIds = implode(',',$menuIdArray);
        }
        $setData = [
            'checkIds' => $menuIds,
            'noCheckIds' => '',
            'role_id' => $adminRoleId
        ];
        $resSetRoleMenu = app($this->userMenuService)->setRoleMenu($adminRoleId,$setData);
        if(isset($resSetRoleMenu['code'])){
            return $resSetRoleMenu;
        }
        // 将admin部门绑定在admin上
        $departmentData = [
            'dept_id' => $adminDepartmentId
        ];
        $resUpdateData = app($this->userSystemInfoRepository)->updateData($departmentData,['user_id' => 'admin']);
        if(isset($resUpdateData['code'])){
            return $resUpdateData;
        }
    }


    /**
     * 钉钉为主的部门同步
     */
    public function dingtalkOADepartmentSync($tempRoleId){
        // 获取钉钉部门列表，执行部门同步
        $resDepartmentList = $this->getDingtalkDepartmentList(['fetchChild'=>'true']);
        if(empty($resDepartmentList)){
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOADepartmentSync',
                'operation_data' => 'dingtalkOADepartmentSync',
                'operation_result' => 'error',
                'operation_result_data' => trans("dingtalk.sync_error_dingtalk_department_is_empty"),
            ];
            // $this->addDingtalkSyncLog($logData);
            return ['code' => ['0x000113', 'dingtalk'],'logInfo' => $logData];
        }
        // 循环遍历部门列表
        $departmentTree = [];
        foreach ($resDepartmentList as $department){
            if(!empty($department['id'])){
                $hasSync = app($this->dingtalkOrganizationSyncDepartmentRepository)->getSyncByDingtalkId($department['id']);
                if(!empty($hasSync)){
                    // 已经同步过了，跳过
                    $oaDepartmentId = $hasSync['oa_dep_id'];
                    // 去更新部门信息，只更新部门名称
                    // 判断部门名称是否已使用
                    if (app($this->departmentService)->deptNameIsRepeat($department['name'], '')) {
                        // 已被使用则不更新
                    }else{
                        $deptNamePinyin = Utils::convertPy($department['name']);
                        $updateDepartmentData = [
                            'dept_name'     => $department['name'],
                            'dept_name_py'  => $deptNamePinyin[0],
			                'dept_name_zm'  => $deptNamePinyin[1],
                        ];
                        app($this->departmentRepository)->updateDepartment($updateDepartmentData, $oaDepartmentId);
                    }

                    // 暂时不去维护关联表的名称
                }else{
                    // 未同步则新增部门，新增时维护部门的父级
                    $dingtalkDeptName = empty($department['name']) ? trans("dingtalk.dingtalk_department").$department['id'] : $department['name'];

                    // 新增之前要校验部门名称不能重复,是这里导致了上下级的问题。
                    // 部门重名限制，同级与父子级名称不可有重复
                    if (app($this->departmentService)->deptNameIsRepeat($dingtalkDeptName, '')) {
                        $dingtalkDeptName = 'dingtalk-'.$department['id'].$dingtalkDeptName;
                    }
                    $inserDepData = [
                        'dept_name' => $dingtalkDeptName,
                        // 'parent_id' =>
                        // 这里不能直接附父级id因为该父级可能都不存在,这里不做局部过滤判断父级存在则插入是因为已同步的也需要做层级更新，代码重复且会更复杂
                    ];
                    // var_dump($inserDepData);没问题
                    $resAdd = app($this->departmentService)->addDepartment($inserDepData,trans("dingtalk.dingtalk_organization_sync"));// 第二个参数写日志用的保留的是操作者
                    if(!empty($resAdd['dept_id'])){
                        // 新增成功了，插入关联表
                        $oaDepartmentId = $resAdd['dept_id'];
                        $insertData = [
                            'dingtalk_dep_id'   => $department['id'],
                            'oa_dep_id'         => $resAdd['dept_id'],
                            'dingtalk_dep_name' => $dingtalkDeptName,
                            'oa_dep_name'       => $dingtalkDeptName,
                        ];
                        app($this->dingtalkOrganizationSyncDepartmentRepository)->addDingtalkDepartmentRelation($insertData);
                    }else{
                        $logData = [
                            'sync_operation_type' => 'sync',
                            'operation_method' => 'dingtalkOADepartmentSync',
                            'operation_data' => json_encode($department),
                            'operation_result' => 'error',
                            'operation_result_data' => trans("dingtalk.add_department_error").json_encode($resAdd),
                        ];
                        // $this->addDingtalkSyncLog($logData);
                        $this->GlobalLogStack($logData);
                    }
                }
                // 做父级关系绑定,保存钉钉的父部门ID。
                $departmentTree[$oaDepartmentId] = $department['parentid'];
                if(!empty($resHandleTree)){
                    $logData = [
                        'sync_operation_type' => 'sync',
                        'operation_method' => 'dingtalkOADepartmentSync',
                        'operation_data' => 'dingtalkOADepartmentSync',
                        'operation_result' => 'error',
                        'operation_result_data' => trans("dingtalk.update_department_parent_error").json_encode($resHandleTree),
                    ];
                    // $this->addDingtalkSyncLog($logData);

                    return $resHandleTree;
                }
                // 为了防止多次遍历，用户同步放在这里，也是因为根据部门id获取用户列表时是不返回角色信息的，所以如果发生了新增角色是必定要有一个临时角色的。
                // 获取钉钉的用户列表，执行用户同步
                $userOffset = 0;
                // $totalUserList = [];
                do{
                    $departmentUserList = app($this->dingTalk)->getDingtalkDepartmentUserList($department['id'],$userOffset,100);
                    // $totalUserList = array_merge($totalUserList,$departmentUserList);
                    $resHandle = $this->handleDingtalkDepartmentUser($departmentUserList,$oaDepartmentId,$tempRoleId);
                    if(isset($resHandle['code'])){
                        // 报错了
                        return $resHandle;
                    }
                    $userOffset += 100;
                }while(count($departmentUserList) == 100);
            }else{
                $logData = [
                    'sync_operation_type' => 'sync',
                    'operation_method' => 'dingtalkOADepartmentSync',
                    'operation_data' => json_encode($department),
                    'operation_result' => 'error',
                    'operation_result_data' => trans("dingtalk.dingtalk_department_id_is_empty_sync_cancel"),
                ];
                // $this->addDingtalkSyncLog($logData);
                $this->GlobalLogStack($logData);
            }
        }

        // 更新部门的父子层级
        $resHandleTree = $this->handleDepartmentTree($departmentTree);
    }

    /**
     * 钉钉为主的角色同步
     *
     */
    public function dingtalkOARoleSync($tempRoleId){
        // 获取钉钉角色列表，执行角色同步
        $resRoleList = $this->getRoleList();
        if(empty($resRoleList)){
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'dingtalkOASync',
                'operation_data' => 'dingtalkOASync',
                'operation_result' => 'error',
                'operation_result_data' => trans("dingtalk.sync_error_dingtalk_role_empty"),
            ];
            // $this->addDingtalkSyncLog($logData);
            return ['code' => ['0x000113', 'dingtalk'],'logInfo' => $logData];
        }
        $userRoles = [];
        foreach ($resRoleList as $role){
            if(!empty($role['id'])){
                $hasSync = app($this->dingtalkOrganizationSyncRoleRepository)->getSyncByDingtalkId($role['id']);
                if(!empty($hasSync)){
                    // 已经同步过了，跳过，正常情况下应该都是已经同步过了
                    $oaRoleId = $hasSync['oa_role_id'];
                    // 做角色名称的更新操作
                    if(!empty($role['name'])){
                        $roleNamePyAndZm = convert_pinyin($role['name']);
                        $updateRoleData = [
                            'role_name'    => $role['name'],
                            'role_name_py' => isset($roleNamePyAndZm[0]) ? $roleNamePyAndZm[0] : '',
                            'role_name_zm' => isset($roleNamePyAndZm[1]) ? $roleNamePyAndZm[1] : '',
                        ];
                        app($this->roleRepository)->updateData($updateRoleData, ['role_id' => $oaRoleId]);
                    }
                }else{
                    // 未同步则新增角色，并且插入关联表
                    $dingtalkRoleName = empty($role['name']) ? trans("dingtalk.dingtalk_role").$role['id'] : $role['name'];
                    $inserRoleData = [
                        'role_name' => $dingtalkRoleName,
                        'role_no'   => 0,
                    ];
                    $resRoleId = app($this->roleService)->createRole($inserRoleData);
                    $oaRoleId = $resRoleId;
                    // var_dump($role['id'].':res'.json_encode($resRoleId).'------'.json_encode($hasSync));
                    if(!empty($resRoleId)){
                        // 新增成功了，插入关联表
                        $insertData = [
                            'dingtalk_role_id'   => $role['id'],
                            'oa_role_id'         => $resRoleId,
                            'dingtalk_role_name' => $dingtalkRoleName,
                            'oa_role_name'       => $dingtalkRoleName,
                        ];
                        app($this->dingtalkOrganizationSyncRoleRepository)->addDingtalkRoleRelation($insertData);
                    }
                }
                // 用户信息的角色反向同步放在此处
                $resRoleUpdateUser = $this->dingtalkRoleUpdateUser($role,$tempRoleId,$oaRoleId,$userRoles);
                if(!empty($resRoleUpdateUser['code'])){
                    return $resRoleUpdateUser;
                }
            }
        }
        // dd($userRoles);
        // 这里如果碰到钉钉那边的无角色则无法更新角色的信息
        foreach($userRoles as $userId => $roles){
            // 统一更新角色,数组改字符串
            $userRoleIdString = implode(',',$roles);
            $roledata = [
                'user_id' => $userId,
                'role_id' => $userRoleIdString,// 支持多个以,隔开的id
            ];
            app($this->roleService)->addUserRole($roledata, true);
            // userRoleIdString 是这样的数据"78,70"
            $userMaxRoleNo = app($this->roleService)->getMaxRoleNoFromData($userRoleIdString);
            $updateData = ['max_role_no' => $userMaxRoleNo];
            app($this->userSystemInfoRepository)->entity->where(['user_id' => $userId])->update($updateData);
        }
    }

    /**
     * 钉钉获取角色列表反向同步OA用户列表的角色信息
     * @param role 钉钉返回的角色信息
     * @param tempRoleId 临时角色的id
     * @param oaRoleId 钉钉同步角色后对应OA角色id
     */
    public function dingtalkRoleUpdateUser($role,$tempRoleId,$oaRoleId,&$userRoles){
        // 根据角色id获取用户列表
        $roleUserList = app($this->dingTalk)->getDingtalkRoleUserList($role['id']);
        if(count($roleUserList)>0){
            foreach($roleUserList as $userList){
                // 说明该角色下有人,更新对应人员的角色id
                // 维护userID-rolesId数组
                foreach ($userList as $user){
                    // 根据钉钉的userId查找到OA的Userid。
                    $resUser = app($this->dingtalkOrganizationSyncUserRepository)->getSyncByDingtalkId($user['userid']);
                    if($resUser && isset($resUser['oa_user_id'])){
                        $OAUserId = $resUser['oa_user_id'];
                        // 通过OA的userId更新用户角色信息
                        // 获取OA系统该用户的角色列表
                        // $userRoleId = app($this->roleService)->getUserRole($resUser['oa_user_id']);
                        // $oaRoleList = [];
                        // foreach ($userRoleId as $oaRole){
                        //     $oaRoleList[] = $oaRole['role_id'];
                        // }
                        // 判断oaRoleList的长度如果是1且id值为$tempRoleId表示是新建的用户
                        // if(count($oaRoleList) == 1 && $oaRoleList[0] == $tempRoleId){
                        if(isset($userRoles[$OAUserId])){
                            // 附加角色id
                            if(!in_array($oaRoleId,$userRoles[$OAUserId])){
                                $userRoles[$OAUserId][] = $oaRoleId;
                            }
                        }else{
                            $userRoles[$OAUserId][] = $oaRoleId;
                        }
                            // 执行角色的更新操作
                            // $roledata = [
                            //     'user_id' => $resUser['oa_user_id'],
                            //     'role_id' => $oaRoleId,
                            // ];
                            // app($this->roleService)->addUserRole($roledata, true);
                            // $userRoleIdString = $oaRoleId;

                        // }else{
                            // 非初次同步的更新
                            // 判断当前的OA角色id是否在角色列表中，存在则跳过不存在则附加方式新增
                            // if(!in_array($oaRoleId,$oaRoleList)){
                                // $roledata = [
                                //     'user_id' => $resUser['oa_user_id'],
                                //     'role_id' => $oaRoleId,
                                // ];
                                // app($this->roleService)->addUserRole($roledata, false);
                                // 此处不做系统本来有的用户的角色删除，采用附加方式。因为无法判断原有id是否是OA旧角色id。但此方法无法实现角色减少。
                                // $oaRoleList[] = $oaRoleId;
                            // }
                            // $userRoleIdString = implode(',',$oaRoleList);
                        // }
                    }else{
                        // 不存在理论上不会发生,同名的有个人没有被插入进来,真会有在一级公司下的人就不会同步进来，但是如果还有别的二级部门则会进来
                        $logData = [
                            'sync_operation_type' => 'sync',
                            'operation_method' => 'dingtalkRoleUpdateUser',
                            'operation_data' => 'dingtalkRoleUpdateUser',
                            'operation_result' => 'warn',
                            'operation_result_data' => trans("dingtalk.staff_user_id") . ' : ' . $user['userid'] . trans("dingtalk.dingtalk_one_level_have_no_handle_department"),
                            // 'operation_result_data' => '可是却就是发生了'."\r\n".json_encode($resUser),
                        ];
                        // $this->addDingtalkSyncLog($logData);
                        $this->GlobalLogStack($logData);
                        // 直接跳过不终止
                    }
                }
            }
        }
    }

    // 处理同步根据部门id获取到的用户列表数
    public function handleDingtalkDepartmentUser($UserList,$departmentId,$tempRoleId){
        foreach ($UserList as $user){
            // 使用手机号来判断用户是否存在
            // userManageSystemList
            // 暂时不考虑旧用户，直接新增删除
            // $param['search'] = ["phone_number"=>[$user['mobile'],"="]];
            // $resUserInfo = app($this->userService)->userSystemList($param);
            // if(count($resUserInfo['list']) == 0){
            if(!isset($user['userid'])){
                // 返回的钉钉用户信息没有userid
                continue;
            }
            $this->dingUserArray[$user['userid']] = 1;
            $sync = app($this->dingtalkOrganizationSyncUserRepository)->getSyncByDingtalkId($user['userid']);
            if(empty($sync)){
                // 不存在则新增用户
                // 需要判断用户名是否合法及重名问题
                $user_accounts = $user['userid'];
                $checkUserAccount = app($this->userService)->validateUserAccountsValidation($user_accounts);
                if (!$checkUserAccount) {
                    // 用户名不合法
                    $user_accounts = 'dingUser'.$user['mobile'];
                }
                // 拼接校验唯一性参数
                $uniqueData = [
                    'user_accounts' => $user_accounts,
                    'phone_number' => $user['mobile']
                ];
                $checkResult = app($this->userService)->checkUserInfoUnique($uniqueData, '');

                if (!empty($checkResult['code'])) {
                    // 有返回值说明报错了
                    if($checkResult['code'][0] == '0x005006'){
                        // 手机号码已存在,判断是否是离职用户，
                        $userInfo = DB::table('user_info')->where('phone_number',$user['mobile'])->first();
                        if(!empty($userInfo)){
                            $userinfoId = $userInfo->user_id;
                            $userSystemInfo = app($this->userRepository)->getUserAllData($userinfoId);
                            $userSystemInfo = $userSystemInfo->toArray();
                            if(!empty($userSystemInfo) && isset($userSystemInfo['user_has_one_system_info']['user_status']) && $userSystemInfo['user_has_one_system_info']['user_status'] == 2){
                                $userSystemInfo['user_accounts'] = $user_accounts;
                                $userSystemInfo['user_status'] = 1;
                                app($this->userService)->userSystemEdit($userSystemInfo,[]);
                                // 维护关联关系
                                $relateData = [
                                    'dingtalk_user_id' => $user['userid'],
                                    'oa_user_id' => $userinfoId,
                                    'dingtalk_user_name' => $user['name'],
                                    'oa_user_name' => $user['name']
                                ];
                                app($this->dingtalkOrganizationSyncUserRepository)->updateOrInsertByOAId($userinfoId,$relateData);
                                // 更新维护用户信息
                                $userNamePyArray = convert_pinyin($user['name']);
                                $userUpdateData = [
                                    'user_name' => $user['name'],
                                    'user_name_py' => $userNamePyArray[0],
                                    'user_name_zm' => $userNamePyArray[1],
                                    'user_accounts'=> $user_accounts
                                ];
                                app($this->userRepository)->updateData($userUpdateData,['user_id' => $userinfoId]);
                                $userSystemInfoUpdateData = [
                                    'dept_id' => $departmentId
                                ];
                                app($this->userSystemInfoRepository)->updateData($userSystemInfoUpdateData,['user_id' => $userinfoId]);

                                // 增加邮箱的同步更新
                                // 返回的接口数据中会有极少数人连email字段都没有因此要加判断
                                $userInfoUpdateData = [
                                    'email' => $user['email'] ?? '',
                                    'phone_number' => $user['mobile'] ?? '',
                                ];
                                app($this->userInfoRepository)->updateData($userInfoUpdateData,['user_id' => $userinfoId]);
                                continue;
                            }
                        }
                        
                    }
                    $user_accounts = 'dingUser'.$user['userid'];
                    $logData = [
                        'sync_operation_type' => 'sync',
                        'operation_method' => 'handleDingtalkDepartmentUser',
                        'operation_data' => $user_accounts.'name:'.$user['name'],
                        'operation_result' => 'error',
                        'operation_result_data' => $user['name'].trans($checkResult['code'][1].'.'.$checkResult['code'][0]),
                    ];
                    // $this->addDingtalkSyncLog($logData);
                    $this->GlobalLogStack($logData);
                    continue;
                }
                $insertUserData = [
                    'attendance_scheduling' => 1,
                    'dept_id' => $departmentId,
                    'is_dynamic_code' => 0,
                    'list_number' => "",
                    'phone_number' => $user['mobile'],
                    'post_priv' => "0",
                    'role_id_init' => $tempRoleId,
                    'sex' => "1",
                    'user_accounts' => $user_accounts,// 之前这里会乱掉
                    'user_name' => $user['name'],
                    'user_status' => 1,
                    'user_subordinate_id' => "",
                    'user_superior_id' => "",
                    'wap_allow' => 0,
                    'email' => $user['email'] ?? '',
                ];
                // var_dump($insertUserData);
                $resultCreateUser = app($this->userService)->userSystemCreate($insertUserData);
                if(isset($resultCreateUser['code'])){
                    // 可能存在超过授权用户数的同步问题
                    // dd($resultCreateUser);
                    $logData = [
                        'sync_operation_type' => 'sync',
                        'operation_method' => 'handleDingtalkDepartmentUser',
                        'operation_data' => json_encode($insertUserData),
                        'operation_result' => 'error',
                        'operation_result_data' => trans($resultCreateUser['code'][1].'.'.$resultCreateUser['code'][0]),
                    ];
                    // $this->addDingtalkSyncLog($logData);
                    $this->GlobalLogStack($logData);
                    // return $resultCreateUser;
                }
                if(!empty($resultCreateUser['user_id'])){
                    // 添加关联关系
                    $insertUserSyncData = [
                        'dingtalk_user_id' => $user['userid'],
                        'oa_user_id' => $resultCreateUser['user_id'],
                        'dingtalk_user_name' => $user['name'],
                        'oa_user_name' => $user['name'],
                    ];
                    $this->addDingtalkUserRelation($insertUserSyncData);
                }
            }else{
                // 已存在则匹配上，判断关联关系是否已存在，不存在则增加关联关系
                // $sync = app($this->dingtalkOrganizationSyncUserRepository)->getSyncByDingtalkId($user['userid']);
                // if(empty($sync)){
                //     $insertUserSyncData = [
                //         'dingtalk_user_id' => $user['userid'],
                //         'oa_user_id' => $resUserInfo['list'][0]['user_id'],
                //         'dingtalk_user_name' => $user['name'],
                //         'oa_user_name' => $resUserInfo['list'][0]['user_name'],
                //     ];
                //     $this->addDingtalkUserRelation($insertUserSyncData);
                // }
                // 已经匹配过了

                // 已存在则更新用户的信息，部门，用户名称。可以直接维护user表的名称三字段。和user_system_info的dept_id
                // $param['search'] = ["user_id"=>[$user['userid'],"="]];
                // $resUserInfo = app($this->userService)->userSystemList($param);// 不查了用户是假删除，直接更新
                // if(count($resUserInfo['list']) != 0){
                // 此处掉系统编辑方法更新会很慢

                // 增加手机号(userinfo表)与用户账号(user表)的更新
                // 需要判断用户账号是否合法及重名问题
                $OAUserId = $sync['oa_user_id'] ?? '';
                if(empty($OAUserId)) continue ;
                $user_accounts = $user['userid'];
                $checkUserAccount = app($this->userService)->validateUserAccountsValidation($user_accounts);
                if (!$checkUserAccount) {
                    // 用户账号不合法
                    $user_accounts = 'dingUser'.$user['mobile'];
                }
                // 判断该用户是否是离职状态，如果离职则改为在职
                $userInfo = app($this->userRepository)->getUserAllData($OAUserId);
                if(!empty($userInfo)){
                    $userInfo = $userInfo->toArray();
                    if(!empty($userInfo) && isset($userInfo['user_has_one_system_info']['user_status']) && $userInfo['user_has_one_system_info']['user_status'] == 2){
                        $userInfo['user_accounts'] = $user_accounts;
                        $userInfo['user_status'] = 1;
                        app($this->userService)->userSystemEdit($userInfo,[]);
                    }
                }
                

                $userNamePyArray = convert_pinyin($user['name']);
                $userUpdateData = [
                    'user_name' => $user['name'],
                    'user_name_py' => $userNamePyArray[0],
                    'user_name_zm' => $userNamePyArray[1],
                    'user_accounts'=> $user_accounts
                ];
                app($this->userRepository)->updateData($userUpdateData,['user_id' => $sync['oa_user_id']]);
                $userSystemInfoUpdateData = [
                    'dept_id' => $departmentId
                ];
                app($this->userSystemInfoRepository)->updateData($userSystemInfoUpdateData,['user_id' => $sync['oa_user_id']]);

                // 增加邮箱的同步更新

                // 返回的接口数据中会有极少数人连email字段都没有因此要加判断
                $userInfoUpdateData = [
                    'email' => $user['email'] ?? '',
                    'phone_number' => $user['mobile'] ?? '',
                ];
                app($this->userInfoRepository)->updateData($userInfoUpdateData,['user_id' => $sync['oa_user_id']]);
                // }
            }
        }
    }

    // 更新部门树的上级关系
    public function handleDepartmentTree($departmentTree){
        // dd($departmentTree);
        foreach($departmentTree as $departmentId => $dingtalkParentId){
            if($dingtalkParentId == 1){
                continue;
            }
            // 需要同时维护三个字段 parent_id,arr_parent_id,has_children

            // 将钉钉的parentId转化为OA 部门id
            $oaParent = app($this->dingtalkOrganizationSyncDepartmentRepository)->getSyncByDingtalkId($dingtalkParentId);
            if(!empty($oaParent)){
                // var_dump('depId:'.$departmentId.'---'.$oaParent['oa_dep_id']);
                // oa_dep_id
                // 获取更新后的arr_parent_id
                $arr_parent_id = app($this->departmentService)->getArrParentId($oaParent['oa_dep_id']);
                // 将父级的has_children改为1
                app($this->departmentRepository)->updateDepartment(['has_children' => 1], $oaParent['oa_dep_id']);
                app($this->departmentRepository)->updateDepartment(['parent_id' => $oaParent['oa_dep_id'],'arr_parent_id' => $arr_parent_id], $departmentId);
            }else{
                // 写日志，父级id不存在系统中
                $logData = [
                    'sync_operation_type' => 'sync',
                    'operation_method' => 'handleDepartmentTree',
                    'operation_data' => 'OAid:'."$departmentId".'--dingtalkParentId:'.$dingtalkParentId,
                    'operation_result' => 'error',
                    'operation_result_data' => trans("dingtalk.parent_department_not_exist_in_oa"),
                ];
                // $this->addDingtalkSyncLog($logData);
                $this->GlobalLogStack($logData);
            }

        }
    }

    public function testss(){
        // $param['search'] = ["phone_number"=>['11815287550',"="]];
        // $resUserInfo = app($this->userService)->userSystemList($param);
        // return $resUserInfo;
        // return trans("dingtalk.0x120009",[],'zh-CN');

        // $param['search'] = ['dept_name' => ['测试','=']];
        // $exitDepartment = app($this->departmentService)->deptTreeSearch($param);
        // dd($exitDepartment[0]);

    }

    // OA组织架构反向过滤删除主函数
    /**
     * 需要判断返回值带不带code
     */
    public function organizationReverseFilter(){
        // 角色
        $resRoleFileter = $this->roleReverseFilter();
        if(isset($resRoleFileter['code'])){
            return $resRoleFileter;
        }
        // 用户
        $resUserFileter = $this->userReverseFilter();
        if(isset($resUserFileter['code'])){
            return $resUserFileter;
        }
        // 最后删除部门
        $resDepartmentFileter = $this->departmentReverseFilter();
        if(isset($resDepartmentFileter['code'])){
            return $resDepartmentFileter;
        }
    }

    // 部门反向过滤删除函数
    public function departmentReverseFilter(){
        // 获取并循环遍历OA部门列表，如果没有关联则删除
        $OADepartmentList = app($this->departmentService)->listDept([]);
        $OADepartmentList = $OADepartmentList['list'];
        if(empty($OADepartmentList)){
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'departmentReverseFilter',
                'operation_data' => 'departmentReverseFilter',
                'operation_result' => 'error',
                'operation_result_data' => trans("dingtalk.OA_department_list_is_empty_delete_error"),
            ];
            // $this->addDingtalkSyncLog($logData);
            return ['code' => ['0x000113', 'dingtalk'],'logInfo' => $logData];
        }
        $adminDepartmentName = trans("dingtalk.admin_department",[],'zh-CN');
        foreach($OADepartmentList as $department){
            $hasSync = app($this->dingtalkOrganizationSyncDepartmentRepository)->getSyncByOAId($department['dept_id']);
            if($hasSync > 0){
                // 已经同步过了，跳过,部门返回count
            }else{
                // 如果是管理员的部门则跳过
                if($department['dept_name'] == $adminDepartmentName){
                    continue;
                }
                // 删除该部门，不使用系统内方法，父子部门的关系完全以钉钉为主，不再维护系统内的关系。否则子部门如果不能被删除会直接导致父部门也不能被删除
                // $resDelete = app($this->departmentService)->delete($department['dept_id'], own('user_id'));
                $resDelete = app($this->departmentRepository)->deleteDepartment($department['dept_id']);
                if(isset($resDelete['code'])){
                    $logData = [
                        'sync_operation_type' => 'sync',
                        'operation_method' => 'departmentReverseFilter',
                        'operation_data' => 'departmentReverseFilter',
                        'operation_result' => 'error',
                        'operation_result_data' => trans($resDelete['code'][1].'.'.$resDelete['code'][0]),// 这里之前没有encode也没报错也不记录日志，而且好像终止执行了
                    ];
                    // $this->addDingtalkSyncLog($logData);
                    // return $resDelete; 不中断只记录日志
                    $this->GlobalLogStack($logData);
                }
            }
        }
    }

    // 角色反向过滤删除函数
    public function roleReverseFilter(){
        // 获取并循环遍历OA角色列表，如果没有关联则删除
        $OARoleList = app($this->roleService)->getRoleList();
        $OARoleList = $OARoleList['list'];
        // return $OARoleList;
        // var_dump($OARoleList);
        if(empty($OARoleList)){
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'roleReverseFilter',
                'operation_data' => 'roleReverseFilter',
                'operation_result' => 'error',
                'operation_result_data' => trans("dingtalk.OA_rolelist_is_empty_delete_error"),
            ];
            // $this->addDingtalkSyncLog($logData);
            return ['code' => ['0x000113', 'dingtalk'],'logInfo' => $logData];
        }
        $dingtalkSyncRoleName = trans("dingtalk.dingtalk_sync_role",[],'zh-CN');
        $adminRoleName = trans("dingtalk.admin_role",[],'zh-CN');
        foreach($OARoleList as $role){
            $hasSync = app($this->dingtalkOrganizationSyncRoleRepository)->getSyncByOAId($role['role_id']);
            if(!empty($hasSync)){
                // 已经同步过了，跳过
            }else{
                // 删除该角色
                // 需要排除增加时的临时角色钉钉同步角色,和admin的角色
                // 钉钉同步角色
                if($role['role_name'] != $dingtalkSyncRoleName && $role['role_name'] != $adminRoleName){
                    $resDelete = app($this->roleService)->deleteRole($role['role_id']);
                    if(isset($resDelete['code'])){
                        $logData = [
                            'sync_operation_type' => 'sync',
                            'operation_method' => 'roleReverseFilter',
                            'operation_data' => 'roleReverseFilter',
                            'operation_result' => 'error',
                            'operation_result_data' => trans($resDelete['code'][1].'.'.$resDelete['code'][0]),
                        ];
                        // $this->addDingtalkSyncLog($logData);
                        // return $resDelete; 不中断只记录日志
                        $this->GlobalLogStack($logData);
                    }
                }
            }
        }
    }


    // 用户反向过滤删除函数
    /**
     * 用户表是软删除，获取到的用户列表是未删除的列表，删除操作是软删除。
     */
    public function userReverseFilter(){
        // 获取并循环遍历OA用户列表，如果没有关联则删除
        $OAUserList = app($this->userRepository)->getUserIdByFields('user_id');
        // return $OAUserList;
        if(empty($OAUserList)){
            $logData = [
                'sync_operation_type' => 'sync',
                'operation_method' => 'userReverseFilter',
                'operation_data' => 'userReverseFilter',
                'operation_result' => 'error',
                'operation_result_data' => trans("dingtalk.OA_userlist_is_empty_delete_error"),
            ];
            // $this->addDingtalkSyncLog($logData);
            return ['code' => ['0x000113', 'dingtalk'],'logInfo' => $logData];
        }
        foreach($OAUserList as $user){
            // 如果是admin则跳过
            if($user['user_id'] == 'admin'){
                continue;
            }
            $hasSync = app($this->dingtalkOrganizationSyncUserRepository)->getSyncByOAId($user['user_id']);
            if(!empty($hasSync)){
                // 已经同步过了，跳过
                if(!empty($this->dingUserArray)){
                    // 已同步的记录里可能会有离职
                    $dingtalkUserid = $hasSync['dingtalk_user_id'] ?? '';
                    if(!empty($this->dingUserArray[$dingtalkUserid])){
                        // 该用户是正常接口返回的用户
                    }else{
                        // 该关联用户已经不在钉钉接口返回列表中
                        // 执行用户离职操作
                        $userInfo = app($this->userRepository)->getUserAllData($user['user_id']);
                        if(!empty($userInfo)){
                            app($this->userService)->leaveUserAccount($user['user_id'],$userInfo->toArray(),[]);
                        }else{
                            $logData = [
                                'sync_operation_type' => 'sync',
                                'operation_method' => 'leaveUserAccount',
                                'operation_data' => json_encode($userInfo),
                                'operation_result' => 'error',
                                'operation_result_data' => 'userInfo is empty',
                            ];
                            $this->GlobalLogStack($logData);
                        }
                    }
                }
            }else{
                // 删除该用户
                $resDelete = app($this->userService)->userSystemDelete($user['user_id'], own('user_id'));
                if(isset($resDelete['code'])){
                    $logData = [
                        'sync_operation_type' => 'sync',
                        'operation_method' => 'userReverseFilter',
                        'operation_data' => 'userReverseFilter',
                        'operation_result' => 'error',
                        'operation_result_data' => trans($resDelete['code'][1].'.'.$resDelete['code'][0]),
                    ];
                    // $this->addDingtalkSyncLog($logData);
                    // return $resDelete; 不中断只记录日志
                    $this->GlobalLogStack($logData);
                }
            }
        }
    }

    // 组织架构同步功能
    public function organizationSync($userId = ''){
        // 定时任务不走队列好像执行不了
        if($userId != ''){
            $resSync = Queue::push(new DingtalkOrganizationSyncJob($userId));
        }else{
            $resSync = Queue::push(new DingtalkOrganizationSyncJob(own('user_id')));
        }
        // 钉钉同步之后删除用户已有的缓存
        Redis::del('user:user_accounts');
        // $resSync = $this->dingtalkOASync(own('user_id'));
        if(isset($resSync['code'])){
           return $resSync;
        }
        return $resSync;
    }

    // 钉钉组织架构同步日志新增
    /**
     * @param data Array
     * [
     *      'sync_operation_type' => 'type',
     *      'operation_method' => '',
     *      'operation_data' => '',
     *      'operation_result' => 'type',
     *      'operation_result_data' => 'type',
     * ]
     */
    public function addDingtalkSyncLog($data)
    {
        // 总体成功情况下解析不中断日志插入进去
        if(isset($data['operation_result']) && $data['operation_result'] == 'success' && isset($GLOBALS['nonDisruptiveLog']) &&is_array($GLOBALS['nonDisruptiveLog']) && count($GLOBALS['nonDisruptiveLog']) >= 1){
            $data['operation_result_data'] = trans("dingtalk.some_error_log_info");
            foreach($GLOBALS['nonDisruptiveLog'] as $index => $log){
                // operation_data字段记录了最详细的报告
                $data['operation_data'] .= "\r\n".json_encode($log);
                $id = $index+1;
                $data['operation_result_data'] .= "\r\n ".$id.' . '.$log['operation_result_data'];
            }
        }
        if(!empty($data['sync_operation_type']) && !empty($data['operation_result'])){
            $insertData = [
                'sync_operation_type' => $data['sync_operation_type'] ?? '',
                'operation_method' => $data['operation_method'] ?? '',
                'operation_data' => $data['operation_data'] ?? '',
                // 'operation_data' => isset($data['operation_data']) ?? '',
                'operation_result' => $data['operation_result'] ?? '',
                'operation_result_data' => $data['operation_result_data'] ?? '',
                'operator' => $GLOBALS['dingtalkSyncUser'] ?? own('user_id'),
            ];
            return app($this->dingtalkSyncLogRepository)->addSyncLog($insertData);
        }
    }

    // 此处统一记录钉钉组织架构同步
    public function GlobalLogStack($logData)
    {
        // 保存不中断日志
        if(!isset($GLOBALS['nonDisruptiveLog'])){
            $GLOBALS['nonDisruptiveLog'] = [];
        }
        $GLOBALS['nonDisruptiveLog'][] = $logData;
    }

    // 此处统一销毁钉钉组织架构同步功能产生的全局变量
    private function flushGlobalVariable()
    {
        if(isset($GLOBALS['dingtalkSyncUser'])){
            unset($GLOBALS['dingtalkSyncUser']);
        }
        if(isset($GLOBALS['nonDisruptiveLog'])){
            unset($GLOBALS['nonDisruptiveLog']);
        }
    }

    // 钉钉组织架构同步获取日志详情
    public function getDingtalkSyncLogdetail($id){
        if(!empty($id)){
            return app($this->dingtalkSyncLogRepository)->getDingtalkSyncdetail($id);
        }
    }

    // 钉钉组织架构同步日志列表获取
    public function getDingtalkSyncLogList($param){
        $param = $this->parseParams($param);
        $data['list'] = app($this->dingtalkSyncLogRepository)->getDingtalkSyncLogList($param);
        $data['total'] = app($this->dingtalkSyncLogRepository)->dingtalkSyncLogsCount();
        return  $data;
    }

    /**
     * 钉钉回调注册函数
     */
    public function registerCallback(){
        return app($this->dingTalk)->registerCallback();
    }
    // OA与钉钉用户同步功能
    public function dingtalkCallbackReceive($data){
        if(isset($data['signature']) && isset($data['timestamp']) && isset($data['nonce'])){
            require_once("../app/lib/openapi-demo-php-master/corp/crypto/DingtalkCrypt.php");
            $signature = $data['signature'];
            $timestamp = $data['timestamp'];
            $nonce = $data['nonce'];
            $postdata = file_get_contents("php://input");
            $postList = json_decode($postdata,true);
            $encrypt = $postList['encrypt'];
            file_put_contents('./data.txt', 'signature:'.$signature.'timestamp:'.$timestamp.'nonce:'.$nonce.'encrypt:'.$encrypt);
            $aes_key="123456789012345678901234567890aq";
            $aes_key_encode=base64_encode($aes_key);
            $aes_key_encode=substr($aes_key_encode,0,-1);//去掉= 号
            $timeStamp = $timestamp;
            $callBackToken = "123456";
            $callBackAseKey = $aes_key_encode;
            $callBackCorpid = "ding7496815d5e5a91e4";
            $crypt = new \DingtalkCrypt($callBackToken, $callBackAseKey, $callBackCorpid);
            $msg = "";
            $errCode = $crypt->DecryptMsg($signature, $timeStamp, $nonce, $encrypt, $msg);
            $eventMsg = json_decode($msg);
            if(!empty($eventMsg)){
                $eventType = $eventMsg->EventType;
                if ($errCode == 0 && $eventType != 'check_url') {
                    switch ($eventType) {
                        case 'user_add_org':   //通讯录用户增加
                        //处理user_add_org回调事件
                            break;
                        case 'check_in':   //登录用于测试
                        file_put_contents('./responce.txt', "我成功啦！".json_encode($eventMsg));
                        default :
                            break;
                    }
                } else {
                }
            }
            //reponse to dingding
            $encryptMsg = '';
            $res = "success";
            file_put_contents('./1.txt', "1.".$res.'\r\n'."2.".$timeStamp.'\r\n'."3.".$nonce.'\r\n'."4.".$encryptMsg);
            $errCode = $crypt->EncryptMsg($res, $timeStamp, $nonce, $encryptMsg);
            // 此处返回900007
            file_put_contents('./1.txt',"\r\n".date("Y-m-d H:i:s",time()).$errCode,FILE_APPEND);
            if ($errCode == 0) {
                echo $encryptMsg;
            } else {
            }
        }

    }
}
