<?php

namespace App\EofficeApp\Dgwork\Services;

use App\EofficeApp\Base\BaseService;
use App\Utils\Utils;
use Eoffice;
use Queue;
use DB;
use Log;


class DgworkService extends BaseService
{

    private $dgworkConfigRepository;
    private $dgwork;
    private $dgworkUserRepository;
    private $userService;
    private $attachmentService;
    private $authService;
    private $remindsRepository;
    private $empowerService;
    private $unifiedMessageService;

    public function __construct()
    {
        $this->dgworkConfigRepository  = "App\EofficeApp\Dgwork\Repositories\DgworkConfigRepository";
        $this->dgwork                  = "App\Utils\Dgwork";
        $this->dgworkUserRepository   = "App\EofficeApp\Dgwork\Repositories\DgworkUserRepository";
        $this->dgworkZjPointRepository   = "App\EofficeApp\Dgwork\Repositories\DgworkZjPointRepository";
        $this->userService              = "App\EofficeApp\User\Services\UserService";
        $this->attachmentService        = "App\EofficeApp\Attachment\Services\AttachmentService";
        $this->empowerService           = "App\EofficeApp\Empower\Services\EmpowerService";
        $this->authService              = "App\EofficeApp\Auth\Services\AuthService";
        $this->remindsRepository        = "App\EofficeApp\System\Remind\Repositories\RemindsRepository";
        $this->unifiedMessageService = 'App\EofficeApp\UnifiedMessage\Services\UnifiedMessageService';
    }

    // 获取政务钉钉配置
    public function getDgworkConfig()
    {
        $config = app($this->dgworkConfigRepository)->getDgworkConfig();
        if (!empty($config['update_time'])) {
            $config['update_time'] = explode(",", $config['update_time']);
        }
        if(empty($config['is_push'])){
            $config['is_push'] = 0;
        }
        if(empty($config['type'])){
            $config['type'] = 1;
        }
        if($config['type'] == 2){
            // 浙政钉去查埋点配置
            $pointInfo = $this->getZjPoint();
            if($pointInfo){
                $config['bid'] = $pointInfo['bid'] ?? '';
                $config['sapp_id'] = $pointInfo['sapp_id'] ?? '';
                $config['sapp_name'] = $pointInfo['sapp_name'] ?? '';
            }
        }
        return $config;
    }

    // 保存配置
    public function saveDgworkConfig($data)
    {
        $checkResult = $this->checkDgworkConfig($data);
        if(isset($checkResult['code'])){
            return $checkResult;
        }
        $appKey     = trim($data['app_key'] ?? '');
        $appSecret     = trim($data['app_secret'] ?? '');
        $domain     = trim($data["domain"] ?? '');
        $domain     = trim($domain, "/");
        $is_push    = $data['is_push'] ?? 0;
        $tenant_id    = $data['tenant_id'] ?? '';
        $type    = $data['type'] ?? 1;
        
        // 校验配置是否正确通过请求access_token

        $accessToken = $checkResult['accessToken'] ?? '';
        $accessTokenExpire   = isset($checkResult['expiresIn']) ? time() + $checkResult['expiresIn'] : ''; // 应该是接口返回的时间以免更新后不一致

        $time = time();
        $config = [
            "app_key"      => $appKey,
            "app_secret"   => $appSecret,
            "domain"       => $domain,
            "is_push"      => $is_push,
            "tenant_id"    => $tenant_id,
            "type"         => $type,
            "access_token"      => $accessToken,
            "access_token_expire"   => $accessTokenExpire,
        ];

        $this->truncateDgwork();
        $dgworkData = app($this->dgworkConfigRepository)->addDgworkConfig($config);
        if ($dgworkData) {
            //插入、更新reminds
            $remids = app($this->remindsRepository)->checkReminds("dgwork");
            if (!$remids) {
                app($this->remindsRepository)->insertData(["id" => 10, "reminds" => "dgwork"]);
            }
        }
        // 处理浙政钉埋点
        if($type == 2 && !empty($data['bid']) && !empty($data['sapp_id']) && !empty($data['sapp_name'])){
            $pointData = [
                'config_id' => $dgworkData['dgwork_config_id'] ?? 1,
                'bid' => $data['bid'],
                'sapp_id' => $data['sapp_id'],
                'sapp_name' => $data['sapp_name'],
            ];
            app($this->dgworkZjPointRepository)->addDgworkZjPoint($pointData);
        }

        return $dgworkData;
    }

    // 获取浙政钉埋点
    public function getZjPoint()
    {
        $resPoint = app($this->dgworkZjPointRepository)->getDgworkZjPoint();
        return $resPoint;
    }

    // 删除配置信息
    public function truncateDgwork()
    {

        $dgworkConfig = app($this->dgworkConfigRepository)->truncateDgwork();
        // 删除埋点信息，目前单应用逻辑
        app($this->dgworkZjPointRepository)->truncateDgworkZjPoint();
        if ($dgworkConfig) {
            $where = [
                "reminds" => ["dgwork"],
            ];
            app($this->remindsRepository)->deleteByWhere($where);
        }

        return $dgworkConfig ? 1 : ['code' => ["0x120001", 'dingtalk']];
    }


    // 是否拥有该提醒方式的权限
    public function showDgwork($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        $dgwork = $this->getDgworkConfig();

        if (isset($auhtMenus["code"]) || !in_array(296, $auhtMenus) || empty($dgwork) || $dgwork['is_push'] == 0) {
            return "false";
        } else {
            return "true";
        }

    }

    public function checkDgworkConfig($data)
    {
        $appKey    = isset($data["app_key"]) ? trim($data["app_key"]) : "";
        $appSecret = isset($data["app_secret"]) ? trim($data["app_secret"]) : "";
        $type = isset($data["type"]) ? trim($data["type"]) : 1;
        $domain     = trim($data["domain"]);
        $domain     = trim($domain, "/");
        if(empty($appKey) || empty($appSecret)){
            return ['code' => ['lack_param', 'dgwork']]; // 域名不合法
        }
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

        // 通过获取access_token 来校验是否合法
        return app($this->dgwork)->checkConfig($appKey,$appSecret,$type);
    }

    public function dgworkAccess($data)
    {
        // 获取可访问的模块
        $auhtMenus = app($this->empowerService)->getPermissionModules();// 此处返回的是系统授权模块
        if (isset($auhtMenus["code"])) {
            // 报错了
            $code     = $auhtMenus["code"];
            $message  = urlencode(trans("register.$code"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        } else {
            //能获取到授权信息
            if (!in_array(296, $auhtMenus)) {
                //没有钉钉模块报错
                $message  = trans('dgwork.Authorization_expires');
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
        setcookie("state_url", $state_url, time() + 3600, "/");
        if ($isPc) {
            include_once './dgwork/indexpc.php';
        } else {
            include_once './dgwork/index.php';
        }

        exit;
    }


    // pc端的跳转到免登前端页面
    public function pcDgworkAccess($data)
    {
        $data["pc"] = 1;
        $this->dgworkAccess($data);
    }


    // 获取到code后获取信息免登到OA
    public function dgworkAuth($data)
    {
        if (empty($data["code"])) {
            return ['code' => ['auth_code_empty', 'dgwork']];
        }

        $user_id = $this->getUserIdByCode($data["code"]);//用免登code校验用户并获取用户OAid
        if (isset($user_id["code"]) && is_array($user_id["code"])) {
            $code     = $user_id['code'][0];
            $message  = urlencode(trans("dgwork.$code"));
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

        $platform = isset($_GET["pc"]) && $_GET["pc"] == 1 ? "dgWorkPC" : "dgWork";
        
        app($this->authService)->systemParams = app($this->authService)->getSystemParam();
        $registerUserInfo                     = app($this->authService)->generateInitInfo($user, "zh-CN", $platform, false);// 此处将平台参数传入来获取相应的菜单
        if (empty($registerUserInfo) || empty($registerUserInfo['token'])) {
            return ['code' => ['0x120006', 'dingtalk']];
        }
        setcookie("token", $registerUserInfo['token'], time() + 3600, "/");
        setcookie("loginUserId", $user_id, time() + 3600, "/");//缓存用户token与用户id
        $dgworkConfig = $this->getDgworkConfig();
        if (!$dgworkConfig) {
            $message  = urlencode(trans("dingtalk.0x120009"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        $domain        = $dgworkConfig['domain'];
        $target_module = "";
        $type      = isset($_COOKIE["state_url"]) ? $_COOKIE["state_url"] : "";
        $state_url = "";
        setcookie('state_url', '', 0);

        if ($type == "" && $platform == "dgWorkPC") {
//                pc钉钉进入此处
            $application_url = $domain . "/eoffice10/client/web/ding-talk#token=" . $registerUserInfo['token'];
//                dd($application_url);
            header("Location: $application_url");
            exit;
        }
        if (!$type) {
            // 不存在跳转到具体模块
            $target    = "";
            $state_url = "";
        } else if (is_numeric($type)) {
            $target = "";
            // 0 - 4 不在考虑范围
            if ($platform == "dgWork") {
                // 手机端
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
                // pc端
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
        if ($platform == "dgWork") {
            // $application_url = $domain . "/eoffice10/client/mobile#" . $state_url . "&platform=" . $platform . $target . "&token=" . $registerUserInfo['token'];
            // 在2019.11.26改动，之前的是10.0的路由会导致具体模块跳转无效
            $application_url = $domain . "/eoffice10/client/mobile#target_url=" . $state_url . "&platform=" . $platform . $target . "&token=" . $registerUserInfo['token'];

        } else {
            $application_url = $domain . "/eoffice10/client/web" . $state_url . $target . "&token=" . $registerUserInfo['token'];
        }
        header("Location: $application_url");
        exit;
        
    }

    // 通过免登code来获取政务钉钉用户信息，对比OA内部是否有该用户的关联。如果有则返回OA用户的ID
    public function getUserIdByCode($code)
    {
        if (!$code) {
            return ['code' => ['auth_code_empty', 'dgwork']];
        }

        $userInfo = app($this->dgwork)->getUserInfoByCode($code);
        if(!empty($userInfo['code'])){
            return $userInfo;
        }
        if(empty($userInfo['employeeCode'])){
            return ['code' => ['data_type_error', 'dgwork']];
        }
        $employeeCode = $userInfo['employeeCode'];
        // 查询该租户id下是否有OA userid绑定
        $bind = app($this->dgworkUserRepository)->getUserIdByEmployeeCode($employeeCode);
        if(empty($bind['user_id'])){
            return ['code' => ['user_not_bind', 'dgwork']];
        }
        return $bind['user_id'];
    }

    // 获取关联用户的列表
    public function getDgworkUserList($param)
    {
        $param    = $this->parseParams($param);
        $param['type'] = $param['type'] ?? 'all';

        $responseData['total'] = $this->getDgworkUserCount($param);
        $param['returntype'] = 'array';
        $responseData['list'] = app($this->dgworkUserRepository)->getDgworkUserList($param);
        return $responseData;
    }

    public function getDgworkUserCount($param){
        $param['page'] = 0 ;
        $param['returntype'] = 'count';
        return app($this->dgworkUserRepository)->getDgworkUserList($param);
    }

    // 新增关联用户的绑定
    public function addDgworkUserBind($data)
    {
        if(empty($data['employee_code']) || empty($data['user_id'])){
            return ['code' => ["lack_param", 'dgwork']];
        }
        // 判断该用户user_id是否已被绑定
        $check = app($this->dgworkUserRepository)->checkBindExist($data);
        if($check){
            return ['code' => ["bind_exist", 'dgwork']];
        }
        $res = app($this->dgworkUserRepository)->addDgworkUserBind($data);
        
        return $res;
    }


    // 删除用户关联
    public function deleteDgworkUserBind($data)
    {
        if(empty($data['user_id'])){
            return false;
        }
        return app($this->dgworkUserRepository)->deleteDgworkUserBind($data['user_id']);
    }

    // 
    public function getDgworkUserInfo($data)
    {
        if(empty($data['employee_code'])){
            return false;
        }
        // $userInfo = app($this->dgwork)->getDgworkUserInfo($data['employee_code']);
        $userInfo = app($this->dgwork)->getEmployeeCodeByPhone($data['employee_code']);
        return $userInfo;
    }

    public function autoBindUser()
    {
        // 获取OA用户列表
        $userList = app($this->userService)->userSystemList();
        if(empty($userList['total'])){
            return false;
        }
        // 清空关联表
        app($this->dgworkUserRepository)->truncateDgworkUser();
        $success = 0;
        foreach($userList['list'] as $user){
            if(empty($user['phone_number']) || empty($user['user_id'])){
                continue;
            }
            // var_dump($user['phone_number']);
            // 手机号不为空则请求接口获取人员的employee_code
            $employeeCode = app($this->dgwork)->getEmployeeCodeByPhone($user['phone_number']);
            if(!empty($employeeCode['code']) || empty($employeeCode['employeeCode'])){
                continue;
            }
            $data[$employeeCode['employeeCode']] = [
                'employee_code' => $employeeCode['employeeCode'],
                'user_id' => $user['user_id'],
                'created_at' => date('Y-m-d H:i:s' ,time()),
                'updated_at' => date('Y-m-d H:i:s' ,time()),
            ];
            $employeeCodeList[] = $employeeCode['employeeCode'];
            
        }
        if(empty($employeeCodeList)){
            return false;
        }
        // 由于不知道批量的接口传递参数的格式暂时逐个传递
        // $accountList = app($this->dgwork)->getAccountIdByEmployeeCodeList($employeeCodeList);
        // dd($accountList);
        $insertData = [];
        foreach($employeeCodeList as $employeeCode){
            $account = app($this->dgwork)->getAccountIdByEmployeeCodeList($employeeCode);
            if(is_array($account) && count($account)>0 && !empty($account[0]['accountId']) && !empty($data[$employeeCode])){
                $data[$employeeCode]['account_id'] = $account[0]['accountId'];
                $insertData[] = $data[$employeeCode];
                $success++;
            }
        }
        $res = app($this->dgworkUserRepository)->insertDgworkUserBind($insertData);
        return $success;
    }

    public function dgworkNearby($data)
    {
        return app($this->dgwork)->dgworkNearby($data);
    }

    //js-sdk配置
    public function dgworkSignPackage()
    {
        return app($this->dgwork)->dgworkSignPackage();
    }

    //js-sdk配置 前端接入使用
    // public function dgworkClientpackage($data)
    // {
    //     return app($this->dgwork)->dgworkClientpackage($data);
    // }

    //高德地图
    public function dgworkAttendance($data)
    {
        $address = geocode_to_address($data);
        if (!$address) {
            return ['code' => ["40059", 'qyweixin']]; //不合法的上报地理位置标志位
        }
        return $address;
    }

    // 钉钉素材文件生成系统附件方法
    public function dgworkMove($data)
    {
        if (is_array($data["media_ids"]) && is_array($data["path_list"])) {
            $imgs = $data["media_ids"];
            $paths = $data["path_list"];
        } else {
            $imgs = explode(",", trim($data["media_ids"], ","));
            $paths = explode(",", trim($data["path_list"], ","));
        }

        $fileName             = [];
        $fileIds              = [];
        $thumbWidth           = config('eoffice.thumbWidth', 100);
        $thumbHight           = config('eoffice.thumbHight', 40);
        $thumbPrefix          = config('eoffice.thumbPrefix', "thumb_");
        $attachment_base_path = getAttachmentDir();

        $temp = [];
        foreach ($imgs as $index => $mediaId) {
            // 根据mediaId获取下载文件流
            $fileStream = app($this->dgwork)->downloadFile($mediaId);
            if(empty($fileStream)){
                continue;
            }
            
            ob_start(); //打开输出
            echo $fileStream;
            $file = ob_get_contents(); //得到浏览器输出
            ob_end_clean(); //清除输出并关闭

            $pathinfo = pathinfo($paths[$index]);
            // $imgNmae  = $pathinfo["basename"];
            $imgExt   = $pathinfo["extension"];
            //生成附件ID
            $attachmentId = md5(time() . $mediaId . rand(1000000, 9999999));
            $newPath      = app($this->attachmentService)->createCustomDir($attachmentId);
            $mediaIdName  = $mediaId . "." . $imgExt;
            $originName   = $newPath . $mediaIdName;
            $size         = strlen($file); //得到图片大小
            $fp2          = @fopen($originName, "a");
            fwrite($fp2, $file); //向当前目录写入图片文件，并重新命名
            fclose($fp2);

            $thumbAttachmentName = scaleImage($originName, $thumbWidth, $thumbHight, $thumbPrefix);
            //       组装数据 存入附件表
            $attachment_path = str_replace($attachment_base_path, '', $newPath);
            $newFullFileName = $newPath . DIRECTORY_SEPARATOR . $mediaIdName;

            $attachmentInfo = [
                "attachment_id"          => $attachmentId,
                "attachment_name"        => $mediaIdName,
                "affect_attachment_name" => $mediaIdName,
                'new_full_file_name'     => $newFullFileName,
                "thumb_attachment_name"  => $thumbAttachmentName,
                // 其他文件格式由缩略图的问题
                // "thumb_attachment_name" => $this->generateImageThumb($fileType, $data, $newFullFileName),
                "attachment_size"        => $size,
                "attachment_type"        => $imgExt,
                "attachment_create_user" => '',
                "attachment_base_path"   => $attachment_base_path,
                "attachment_path"        => $attachment_path,
                // "attachment_mark"        => 1,
                "attachment_mark"        => app($this->attachmentService)->getAttachmentMark($imgExt),
                "relation_table"         => '',
                "rel_table_code"         => "",
            ];

            app($this->attachmentService)->handleAttachmentDataTerminal($attachmentInfo);
            //生成64code
            $path   = $attachment_base_path . $attachment_path . DIRECTORY_SEPARATOR . $thumbAttachmentName;
            $temp[] = [
                "attachmentId"    => $attachmentId,
                "attachmentName"  => $mediaIdName,
                "attachmentThumb" => imageToBase64($path),
                // 为了兼容统一的附件接口增加attachmentType属性
                "attachmentSize"        => $size,
                "attachmentType"        => $imgExt,
                "attachmentMark"        => 1,
            ];
        }

        return $temp;
    }


    //推送消息
    public function pushMessage($option)
    {
        return app($this->dgwork)->pushMessage($option);
    }

























    
}
