<?php

namespace App\EofficeApp\Qyweixin\Services;

use App\Utils\WorkWechat;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Qyweixin\Repositories\QyweixinTokenRepository;
use App\EofficeApp\Qyweixin\Repositories\QyweixinTicketRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\User\Services\UserService;
use App\EofficeApp\Auth\Services\AuthService;
use App\EofficeApp\System\Remind\Repositories\RemindsRepository; //消息提醒设置
use App\EofficeApp\Qyweixin\Repositories\QyweixinUserRepository;
use App\EofficeApp\Qyweixin\Repositories\QyweixinConversationRepository;
use App\EofficeApp\System\Department\Repositories\DepartmentRepository;
use App\EofficeApp\Menu\Services\UserMenuService;
use App\EofficeApp\System\Company\Repositories\CompanyRepository;
use App\EofficeApp\Empower\Services\EmpowerService;
use Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 企业号服务
 *
 * @author: 喻威 F:\wamp\www\eoffice10\server\app\Utils\Weixin.php
 *
 * @since：2015-10-19
 */
class QyweixinService extends BaseService {

    /** @var object $qyweixinTokenRepository 微信token资源库 */
    private $qyweixinTokenRepository;

    /** QyweixinTicketRepository  * */
    private $qyweixinTicketRepository;

    /** @var object $weixin 微信资源库 */
    private $workWechat;

    /*     * $userRepository* */
    private $userRepository;

    /** 用户 信息 * */
    private $userService;

    /** 授权注入 * */
    private $authService;

    /** 消息提醒设置 * */
    private $remindsRepository;

    /**     * 同步用户    */
    private $qyweixinUserRepository;

    /**
     * 部门树
     */
    private $departmentRepository;
    /*
     * 用户权限
     */
    private $userMenuService;
    private $empowerService;
    private $qyweixinConversationRepository;

    public function __construct(
    UserRepository $userRepository, WorkWechat $workWechat, QyweixinTokenRepository $qyweixinTokenRepository, UserMenuService $userMenuService
    , UserService $userService, AuthService $authService, QyweixinTicketRepository $qyweixinTicketRepository, RemindsRepository $remindsRepository, QyweixinUserRepository $qyweixinUserRepository, DepartmentRepository $departmentRepository, CompanyRepository $companyRepository
    , EmpowerService $empowerService, QyweixinConversationRepository $qyweixinConversationRepository) {


        $this->qyweixinTokenRepository = $qyweixinTokenRepository;
        $this->qyweixinTicketRepository = $qyweixinTicketRepository;
        $this->workWechat = $workWechat;
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->authService = $authService;
        $this->remindsRepository = $remindsRepository;
        $this->qyweixinUserRepository = $qyweixinUserRepository;
        $this->departmentRepository = $departmentRepository;
        $this->userMenuService = $userMenuService;
        $this->companyRepository = $companyRepository;
        $this->empowerService = $empowerService;
        $this->qyweixinConversationRepository = $qyweixinConversationRepository;
    }

    //自动更新access_token
    public function getAccessToken() {
        return $this->workWechat->getAccessToken();
    }

    /**
     * todo ： 手写页面进行访问测试 -- api
     */
    public function checkWechat($data) {

        $corpid = trim($data['corpid']);
        $secret = trim($data['secret']);
        $domain = trim($data["domain"]);
        $domain = trim($domain, "/");

        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return ['code' => ['0x034004', 'qyweixin']]; // 域名不合法
        }

        $urls = parse_url($domain);
        $host = $urls["host"];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            //IP地址
            if ($host == "localhost" || $host == "127.0.0.1") {
                return ['code' => ['0x034005', 'qyweixin']]; // 内网IP
            }

            $ip_range = array(
                array('10.0.0.0', '10.255.255.255'),
                array('172.16.0.0', '172.31.255.255'),
                array('192.168.0.0', '192.168.255.255'),
            );

            $ip = ip2long($host);
            $find = '';
            foreach ($ip_range as $k => $v) {
                if ($ip >= ip2long($v[0]) && $ip <= ip2long($v[1])) {
                    $find = $k;
                }
            }

            if ((!empty($find))) {
                return ['code' => ['0x034005', 'qyweixin']]; // 内网IP
            }
        }


        //访问测试  - 公开API 跳过 =-------------------------------------- todo ---------------
//        $url = $domain . "/general/qyweixin/validurl.php";
//        $status = getHttps($url);
        //测试链接
        $result = $this->connectToken($corpid, $secret);
        return $result;
    }

    public function getWechat() {

        return $this->qyweixinTokenRepository->getWechat([]);
    }

    public function connectToken($corpid, $secret) {

        $time = time();
        $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}";
        $weixinConnect = getHttps($url);
        if (!$weixinConnect) {
            return ['code' => ['0x034002', 'qyweixin']]; //企业微信响应异常
        }
        $connectList = json_decode($weixinConnect, true);

        if (isset($connectList['errcode'])) {
            $code = $connectList['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }
        return $connectList['access_token'];
    }

    public function saveWechat($data) {


        $corpid = trim($data['corpid']);
        $secret = trim($data['secret']);
        $domain = trim($data["domain"]);
        $domain = trim($domain, "/");

        $connectList = $this->connectToken($corpid, $secret);
        if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
            $code = $connectList['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }

        $data['access_token'] = $connectList;
        $data['token_time'] = time();
        $agentid = isset($data["agentid"]) ? $data["agentid"] : "";
        if ($agentid & $agentid > 0) {
            //同步菜单
        }

        $time = time();
        $weixinData = [
            "access_token" => $connectList,
            "token_time" => $time,
            "create_time" => $time,
            "domain" => $domain,
            "corpid" => $corpid,
            "secret" => $secret,
            "agentid" => $agentid,
            "wechat_code" => isset($data["wechat_code"]) ? $data["wechat_code"] : "",
            "is_push" => isset($data["is_push"]) ? $data["is_push"] : 0
        ];

        $this->truncateWechat();
        $qyData = $this->qyweixinTokenRepository->insertData($weixinData);
        if ($qyData) {
            //插入、更新reminds
            $remids = $this->remindsRepository->checkReminds("qyweixin");
            if (!$remids) {
                $this->remindsRepository->insertData(["id" => 4, "reminds" => "qyweixin"]);
            }
        }

        return $qyData;
    }

    public function truncateWechat() {

        $qyData = $this->qyweixinTokenRepository->truncateWechat();
        if ($qyData) {
            $where = [
                "reminds" => ["qyweixin"]
            ];
            $this->remindsRepository->deleteByWhere($where);
        }

        return $qyData ? 1 : ['code' => ["0x034001", 'qyweixin']];
    }

    public function qywechatCode($code) {
        if (!$code) {
            return ['code' => ['0x034006', 'qyweixin']];
        }

        $access_token = $this->getAccessToken();

        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";

        $postJson = getHttps($url);
        $postObj = json_decode($postJson, true);

        if (isset($postObj['errcode']) && $postObj['errcode'] != 0) {
            $code = $postObj['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }
        if (!isset($postObj["UserId"])) {
            return ['code' => ["0x034009", 'qyweixin']];
        }
        $userId = $postObj["UserId"];
        //获取当前的userInfo、 使用手机账号 weixin 邮箱 去匹配
        $user = $this->workWechat->matchUser($userId);
        if (!$user) {
            return ['code' => ["0x034006", 'qyweixin']];
        }
        $user_id = $user["user_id"];
        // $token = $this->authService->generateToken($user_id);
        $this->updateQyweixinConversation($user_id, $userId);
        return $user_id;
    }

    //js-sdk配置
    public function qywechatSignPackage($data = null) {
        return $this->workWechat->qywechatSignPackage($data);
    }

    //高德地图
    public function geocodeAttendance($data) {
        return $this->workWechat->geocodeAttendance($data);
    }

    //下载文件
    public function qyweixinMove($data) {
        return $this->workWechat->qyweixinMove($data);
    }

    //qywechatChat 会话
    public function qywechatChat($data) {
        return $this->workWechat->qywechatChat($data);
    }

    //推送消息
    public function pushMessage($option) {

        return $this->workWechat->pushMessage($option, "news");
    }

    //微信用户列表
    public function userListWechat($data) {
        $wechatTemp = $this->workWechat->userListWechat();
        $wechatData = json_decode($wechatTemp, true);
        if (isset($wechatData['errcode']) && $wechatData['errcode'] != 0) {
            $code = $wechatData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }
        $userlist = $wechatData["userlist"];
        //同步用户 同步到表
        $this->qyweixinUserRepository->truncateTable();
        foreach ($userlist as $v) {
            $tempData = array_intersect_key($v, array_flip($this->qyweixinUserRepository->getTableColumns()));
            $this->qyweixinUserRepository->insertData($tempData);
        }
        return "ok";
    }

    public function userListShow($data) {

        $tempData = $this->response($this->qyweixinUserRepository, 'getUserListShowTotal', 'getUserListShow', $this->parseParams($data));

        $temp = [];

        if ($tempData["list"]) {
            foreach ($tempData["list"] as $val) {
                $val["user_name"] = $this->userRepository->getUserName($val["userid"]);

                $temp[] = $val;
            }
        }

        return [
            "total" => $tempData["total"],
            "list" => $temp
        ];
    }

    public function getUserWechat() {
        $res = $this->qyweixinUserRepository->getUserWechat();
        $temp = [];
        foreach ($res as $val) {
            if ($val["status"] == 1) {
                $val["title"] = "已关注";
                $val["fieldKey"] = "follow";
            }
            switch ($val["status"]) {
                case 1:
                    $val["title"] = "已关注";
                    $val["fieldKey"] = "follow";
                    break;
                case 2:
                    $val["title"] = "已冻结";
                    $val["fieldKey"] = "freeze";
                    break;
                case 4:
                    $val["title"] = "未关注";
                    $val["fieldKey"] = "unfollow";
                    break;
            }

            $temp[] = $val;
        }

        return $temp;
    }

    public function syncOrganization($data) {

        $dept = $this->syncOrganizationDept($data);

        if ($dept == "ok") {
            $user = $this->syncOrganizationUser($data);
            if ($user !== "ok") {
                return $user;
            }
        } else {
            return $dept;
        }
        Redis::del('user:user_accounts');
        return "ok";
    }

    //同步组织架构
    public function syncOrganizationDept($data) {
        //创建组织架构
        $newPath = createCustomDir("wechat");
        $deptFile = $newPath . "enterprise_organization.csv"; //企业号
        $depts = $this->departmentRepository->getAllDepartment();
        $fp1 = fopen($deptFile, "w+"); //打开csv文件，如果不存在则创建
        $depts_arr1 = array("部门名称", "部门ID", "父部门ID", "排序"); //第一行数据
        $depts_str1 = implode(",", $depts_arr1); //用 ' 分割成字符串
        $tempDept = $this->companyRepository->getCompanyDetail();
        $set_name = $tempDept->company_name; //"所有部门";
        if (!$set_name) {
            $set_name = "部门管理";
        }
        $first_arr1 = array("$set_name", "1", "0", "1"); //第一行数据

        $first_arr1 = implode(",", $first_arr1); //用 ' 分割成字符串

        $dept_str = $depts_str1 . "\n" . $first_arr1 . "\n";
        foreach ($depts as $k => $v) {
            $dept_name = $v['dept_name'];
            $dept_id = $v['dept_id'];
            $parent_id = $v['parent_id'] == 0 ? 1 : $v['parent_id'];
            $dept_sort = $v['dept_sort'];

            $depts_arr2 = array(
                $dept_name,
                $dept_id,
                $parent_id,
                $dept_sort
            ); //第二行数据

            $depts_arr2 = implode(",", $depts_arr2);
            $dept_str .= $depts_arr2 . "\n"; //加入换行符
        }
        fwrite($fp1, $dept_str); //写入数据
        fclose($fp1); //关闭文件句柄


        $wechatDept = $this->workWechat->uploadTempFile($deptFile, 'file');
        $wechatDeptData = json_decode($wechatDept, true);
        if (isset($wechatDeptData['errcode']) && $wechatDeptData['errcode']) {
            $code = $wechatDeptData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }
        $dept_media_id = $wechatDeptData["media_id"];


        return $this->workWechat->batchSyncDept($dept_media_id);
    }

    public function syncOrganizationUser($data) {
        //创建临时文件csv
        $newPath = createCustomDir("wechat");
        $file = $newPath . "enterprise_contacts.csv"; //企业号
        $fp = fopen($file, "w+"); //打开csv文件，如果不存在则创建
        $data_arr1 = array("姓名", "帐号", "微信号", "手机号", "邮箱", "所在部门", "职位"); //第一行数据
        $data_str1 = implode(",", $data_arr1); //用 ' 分割成字符串
        $data_str = $data_str1 . "\n";
        //生成csv
        $userList = $this->userRepository->getUserList($data);

        foreach ($userList as $k => $v) {
            $weixin = $v['user_has_one_info']['weixin'] ? $v['user_has_one_info']['weixin'] : null;
            $phoneNumber = $v['user_has_one_info']['phone_number'] ? $v['user_has_one_info']['phone_number'] : null;
            $email = $v['user_has_one_info']['email'] ? $v['user_has_one_info']['email'] : null;
            if ($weixin || $phoneNumber || $email) {
                $userId = $v['user_id'];
                $userName = $v['user_name'];
                $deptParent = $v['user_has_one_system_info']['user_system_info_belongs_to_department']['arr_parent_id'];
                $deptChild = $v['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id'];
                $dept = "1;";
                if ($deptParent == "0") {
                    $dept .= $deptChild;
                } else {
                    $deptParentTemp = explode(",", $deptParent);
                    foreach ($deptParentTemp as $temp) {
                        if ($temp == 0) {
                            continue;
                        }
                        $dept .= $temp . ";";
                    }
                    $dept .=$deptChild;
                }
                $position = $v['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'];
                $data_arr2 = array(
                    $userName,
                    $userId,
                    $weixin,
                    $phoneNumber,
                    $email,
                    $dept,
                    $position
                ); //第二行数据

                $data_str2 = implode(",", $data_arr2);
                $data_str .= $data_str2 . "\n"; //加入换行符
            } else {
                continue;
            }
        }
        fwrite($fp, $data_str); //写入数据
        fclose($fp); //关闭文件句柄
        //上传素材文件
        $wechatTemp = $this->workWechat->uploadTempFile($file, 'file');
        $wechatData = json_decode($wechatTemp, true);

        if (isset($wechatData['errcode']) && $wechatData['errcode']) {
            $code = $wechatData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }
        $user_media_id = $wechatData["media_id"];
        return $this->workWechat->batchSyncuser($user_media_id);
    }

    //创建用户
    //  userid	 name  department  -- 必须
    //  mobile/weixinid/email三者不能同时为空
    // api/qywechat-createuser
    public function createUser($data) {
        // 封装用户
        //模拟数据
        $params = [
            "userid" => "test" . time(),
            "name" => "name" . time(),
            "department" => 1, //根目录下
            "mobile" => "182" . substr(time(), -8)
        ];

        return $this->workWechat->createUser($params);
    }

    public function oneKey($data) {
        //一键应用
        return false;
    }

    public function qywechatNearby($data) {

        return $this->workWechat->qywechatNearby($data);
    }

    //检查配置
    public function qywechatCheck() {

        $wechat = $this->getWechat();

        if (!$wechat) {
            return 0;
        }

        $is_push = $wechat->is_push;
        if (!$is_push) {
            return 0;
        }

        $data["corpid"] = $wechat->corpid;
        $data["domain"] = $wechat->domain;
        $data["secret"] = $wechat->secret;

        $temp = $this->checkWechat($data);

        if (isset($temp["code"]) && $temp["code"]) {
            return 0;
        }

        return 1;
    }

    public function getWechatApp() {

        $tempData = $this->workWechat->getWechatApp();

        if (!$tempData) {
            return [];
        }

        $resData = json_decode($tempData, true);
        if (isset($resData['errcode']) && $resData['errcode'] != 0) {
            $code = $resData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }
        return $resData["agentlist"];
    }

    //接入
//      var DING_TALK = 0, //钉钉平台
//    OFFICIAL_ACCOUNT = 1, //微信公众号
//    ENTERPRISE_ACCOUNT = 2, //企业号
//    ENTERPRISE_WECHAT = 3, //企业微信
    public function wechatAuth() {

        if (isset($_GET["code"]) && !empty($_GET["code"])) {

            $user_id = $this->qywechatCode($_GET["code"]);
            if (isset($user_id["code"]) && is_array($user_id["code"])) {
                $code = $user_id['code'][0];
                $message = urlencode(trans("qyweixin.$code"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
             $power = $this->empowerService->checkMobileEmpowerAndWapAllow($user_id);

             if (!empty($power["code"]) && !empty($power)) {
                $message = "手机未授权或者不允许访问！";
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            setcookie("loginUserId", $user_id, time() + 3600, "/");
            $target_module = "";
            $type = isset($_GET["state"]) ? $_GET["state"] : "";
            if (!$type) {
                $state_url = "/home/application";
            } else if (is_numeric($type)) {

                $wechat = config('eoffice.wechat');
                $state_url = isset($wechat[$type]) ? $wechat[$type] : "";
                if (!$state_url) {
                    $state_url = "/home/application";
                }

                //跳转到默认的模块
                $applistAuthIds = config('eoffice.applistAuthId');
                $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                if ($auth_id) {
                    $state_url = "";
                    $target_module = $auth_id;
                }
            } else {
                $json = json_decode($type, true);
                if ($json) {
                    readSmsByClient($user_id, $json);
                    setcookie("reminds", $type, time() + 3600, "/");
                    $state_url = ""; //error
                }
            }

            $wechat = $this->getWechat();
            if (!$wechat) {
                $message = urlencode(trans("qyweixin.0x034009"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $domain = $wechat->domain;
            $target = "";
            if ($target_module) {
                $target = "&target_module=" . $target_module;
            }
            $application_url = $domain . "/eoffice10/client/mobile/#" . $state_url . "?platform=enterpriseAccount" . $target;

            header("Location: $application_url");
            exit;
        }
        if (!isset($_GET['echostr'])) {
            $this->workWechat->responseMsg();
        } else {
            $this->workWechat->valid();
        }
    }

    //企业微信接入
    public function qywechatAccess($data) {

        //微信模块ID 499 企业微信107  钉钉应用 120
        $auhtMenus = ecache('Empower:EmpowerModuleInfo')->get();
        if (isset($auhtMenus["code"])) {
            $code = $auhtMenus["code"];
            $message = urlencode(trans("register.$code"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        } else {
            //能获取到授权信息
            if (!in_array(107, $auhtMenus)) {
                $message = "企业微信授权过期或者未授权，停止访问！";
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
        }

        $type = isset($data["type"]) ? $data["type"] : "-1";
        $reminds = isset($data["reminds"]) ? $data["reminds"] : "";
        if ($type == 0) {
            $state_url = $reminds;
        } else {
            if ($type == -1) {
                $state_url = "/home/application";
            } else {

                $state_url = $type;

                // 0 - 4 不在考虑范围
                $applistAuthIds = config('eoffice.applistAuthId');
                $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                if ($auth_id) {
                    if (!in_array($auth_id, $auhtMenus)) {
                        $message = "模块授权过期或者未授权，停止访问！";
                        $errorUrl = integratedErrorUrl($message);
                        header("Location: $errorUrl");
                        exit;
                    }
                }
            }
        }

        $wechat = $this->getWechat();

        if (!$wechat) {
            $message = urlencode(trans("qyweixin.0x034009"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        $corpid = $wechat->corpid;
        $domain = $wechat->domain;
        $secret = $wechat->secret;

        if (!($corpid && $domain && $secret)) {
            $message = urlencode(trans("qyweixin.0x034009"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }

        $tempUrl = $domain . "/eoffice10/server/public/api/qywechat-auth";
        $redirect_uri = urlencode($tempUrl);

        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $corpid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_base&state=" . $state_url . "#wechat_redirect";

        header("Location: $url");
        exit;
    }

//    public function updateAgentId() {
//        return $this->workWechat->createApp();
//    }

    public function getPlatformUser($data) {

        $platform = isset($data["platform"]) ? $data["platform"] : "";
        $user_id = isset($data["user_id"]) ? $data["user_id"] : "";
        if ($platform && $user_id) {
            $user_id = getPlatformUser($platform, $user_id);
        }

        return $user_id;
    }

    public function updateQyweixinConversation($oaId, $userId) {
        $where = [
            "oa_id" => [$oaId]
        ];
        $row = $this->qyweixinConversationRepository->getUser($where);

        if (!$row) {
            $data = [
                "oa_id" => $oaId,
                "userid" => $userId,
            ];
            $result = $this->qyweixinConversationRepository->insertData($data);
        } else {
            $data = [
                "oa_id" => $oaId,
                "userid" => $userId,
            ];
            $result = $this->qyweixinConversationRepository->updateData($data, $where);
        }

        return $result;
    }

     public function showQyweixin($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $auhtMenus = ecache('Empower:EmpowerModuleInfo')->get();

        $power = $this->empowerService->checkMobileEmpowerAndWapAllow($userId);

        $wechat = $this->getWechat();

        if (isset($auhtMenus["code"]) || !in_array(107, $auhtMenus) || !empty($power["code"]) || empty($wechat) || $wechat->is_push == 0) {
            return "false";
        } else {
            return "true";
        }

    }

}
