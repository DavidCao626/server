<?php

namespace App\Utils;

use App\EofficeApp\Dgwork\Repositories\DgworkConfigRepository;
use App\EofficeApp\Dgwork\Repositories\DgworkUserRepository;

// use App\Utils\OpenplatformSDK\ExecutableClient;
use App\Utils\ExecutableClient;
class Dgwork
{

    public $dgworkConfigRepository;
    public $config;
    public $dgworkUserRepository;
    public $executableClient;
    public $tenantId;

    public function __construct(
        DgworkConfigRepository $dgworkConfigRepository,  DgworkUserRepository $dgworkUserRepository,ExecutableClient $executableClient
    ) {
        $this->dgworkConfigRepository  = $dgworkConfigRepository;
        $this->dgworkUserRepository  = $dgworkUserRepository;
        $this->executableClient         = $executableClient;
        
        $config = $this->getConfig();
        if(empty($config['app_key']) || empty($config['app_secret']) || empty($config['type']) || empty($config['tenant_id'])){
            return ['code' => ["lack_param", 'dgwork']];
        }
        $this->setDomain($config['type']);
        // 政务钉钉域名：openplatform.dg-work.cn浙政钉域名    ：openplatform-pro.ding.zj.gov.cn
        $this->tenantId = $config['tenant_id'];
        $this->config = $config;
        $executableClient->setAccessKey($config['app_key']);// 实际使用的是这两个参数，底下的任意参数都可
        $executableClient->setSecretKey($config['app_secret']);
    }


    /**
     * 获取政务钉钉的access_token 返回过期时间为7200s，
     * 过期机制 会自动续期
     */
    public function getAccessToken(){
        $config = $this->config;
        if(!empty($config['access_token']) && !empty($config['access_token_expire']) && time() < $config['access_token_expire']){
            // 判断是否已存在access_token 已存在则判断是否过期未过期则返回
            return $config['access_token'];
        }
        $executableClient = $this->executableClient;
        $executableClient->setApiName('/gettoken.json');
        $executableClient -> addParameter("appkey", $config['app_key']);
        $executableClient -> addParameter("appsecret", $config['app_secret']);
        $ret = $executableClient->epaasCurlPost(3);
        if(!empty($ret['responseCode'])){
            return ['code' => [$ret['responseCode'], 'dgwork']];
        }
        if(empty($ret['data']['accessToken']) || empty($ret['data']['expiresIn'])){
            // 其他错误再次调试。
            return ['code' => ["request_api_error", 'dgwork']];
        }
        $config = [
            'access_token' => $ret['data']['accessToken'],
            'access_token_expire' => time() + $ret['data']['expiresIn']
        ];
        $this->dgworkConfigRepository->update($config);
        return $config['access_token'];
    }

    // 根据系统类型判断访问接口的域名
    public function setDomain($type)
    {
        if($type == 1){
            $this->executableClient->setDomain('https://openplatform.dg-work.cn');// 访问接口的域名
        }
        if($type == 2){
            $this->executableClient->setDomain('https://openplatform-pro.ding.zj.gov.cn');// 访问接口的域名
        }
    }
    /**
     * 获取政务钉钉的jsApi_ticket 返回过期时间为7200s，
     * 过期机制 不会自动续期
     */
    public function getJsApiTicket(){
        try {
            $config = $this->config;
            if(!empty($config['jsapi_ticket']) && !empty($config['jsapi_ticket_expire']) && time() < $config['jsapi_ticket_expire']){
                // 判断是否已存在access_token 已存在则判断是否过期未过期则返回
                return $config['jsapi_ticket'];
            }
            $executableClient = $this->executableClient;
            $executableClient->setApiName('/get_jsapi_token.json');
            // 获取access_token
            $access_token = $this->getAccessToken();
            $executableClient -> addParameter("accessToken", $access_token);
            $ret = $executableClient->epaasCurlPost(3);
            if(!empty($ret['responseCode'])){
                return ['code' => [$ret['responseCode'], 'dgwork']];
            }
            if(empty($ret['data']['accessToken']) || empty($ret['data']['expiresIn'])){
                // 其他错误再次调试。
                return ['code' => ["request_api_error", 'dgwork']];
            }
            $config = [
                'jsapi_ticket' => $ret['data']['accessToken'],
                'jsapi_ticket_expire' => time() + $ret['data']['expiresIn']
            ];
            $this->dgworkConfigRepository->update($config);
            return $config['jsapi_ticket'];
        } catch (Exception $e) {
            $msg = "getFilterWords|err, code: ". $e->getCode() . "|message: ". $e->getMessage();
            $code = $e->getCode(); 
            return ['code' => ["$code", 'dgwork']];
        }
    }

    public function getConfig()
    {
        $config = $this->dgworkConfigRepository->getDgworkConfig();
        if(!empty($config) && !empty($config['app_key']) && !empty($config['app_secret'])){
            return $config;
        }
        return [];
    }

    // 校验配置（获取不保存的access_token）
    public function checkConfig($appKey,$appSecret,$type)
    {
        if(empty($appKey) || empty($appSecret) || empty($type)){
            return ['code' => ["lack_param", 'dgwork']];
        }
        $this->setDomain($type);
        $executableClient = $this->executableClient;
        $executableClient->setApiName('/gettoken.json');
        $executableClient -> addParameter("appkey", $appKey);
        $executableClient -> addParameter("appsecret", $appSecret);
        $executableClient->setAccessKey($appKey);// 实际使用的是这两个参数，底下的任意参数都可
        $executableClient->setSecretKey($appSecret);
        $ret = $executableClient->epaasCurlPost(3);
        
        if(!empty($ret['responseCode'])){
            return ['code' => [$ret['responseCode'], 'dgwork']];
        }
        if(empty($ret['data']['accessToken']) || empty($ret['data']['expiresIn'])){
            // 其他错误再次调试。
            return ['code' => ["request_api_error", 'dgwork']];
        }
        return [
            'accessToken' => $ret['data']['accessToken'],
            'expiresIn' => $ret['data']['expiresIn'],
        ];
    }

    // 通过临时授权码获取用户信息
    public function getUserInfoByCode($code)
    {
        if(empty($code)){
            return ['code' => ["auth_code_empty", 'dgwork']];
        }
        
        $accessToken = $this->getAccessToken();
        if(!empty($accessToken['code'])){
            return $accessToken;
        }

        $executableClient = $this->executableClient;
        $executableClient->setApiName('/rpc/oauth2/dingtalk_app_user.json');
        $executableClient -> addParameter("access_token", $accessToken);
        $executableClient -> addParameter("auth_code", $code);
        $ret = $executableClient->epaasCurlPost(3);
        if(!empty($ret['responseCode'])){
            return ['code' => [$ret['responseCode'], 'dgwork']];
        }
        if(empty($ret['data'])){
            // 其他错误再次调试。
            return ['code' => ["request_api_error", 'dgwork']];
        }
        return $ret['data'];
    }

    // 根据人员code获取人员信息
    public function getDgworkUserInfo($employeeCode)
    {   
        if(empty($employeeCode)){
            return ['code' => ["lack_param", 'dgwork']];
        }
        $executableClient = $this->executableClient;
        $executableClient->setApiName('/mozi/employee/listEmployeesByCodes');
        $executableClient -> addParameter("employeeCodes", $employeeCode);
        $executableClient -> addParameter("tenantId", $this->tenantId);
        $ret = $executableClient->epaasCurlPost(3);
        if(!empty($ret['responseCode'])){
            return ['code' => [$ret['responseCode'], 'dgwork']];
        }
        if(empty($ret['data'])){
            // 其他错误再次调试。
            return ['code' => ["data_empty", 'dgwork']];
        }
        return $ret['data'];
    }

    // 根据手机号获取人员code
    public function getEmployeeCodeByPhone($phone)
    {
        if(empty($phone)){
            return ['code' => ["lack_param", 'dgwork']];
        }
        $executableClient = $this->executableClient;
        $executableClient->setApiName('/mozi/employee/get_by_mobile');
        $executableClient -> addParameter("areaCode", '86');
        $executableClient -> addParameter("tenantId", $this->tenantId);
        $executableClient -> addParameter("namespace", 'local');
        $executableClient -> addParameter("mobile", $phone);
        $ret = $executableClient->epaasCurlPost(3);
        if(!empty($ret['responseCode'])){
            return ['code' => [$ret['responseCode'], 'dgwork']];
        }
        if(empty($ret['data'])){
            // 其他错误再次调试。
            return ['code' => ["data_empty", 'dgwork']];
        }
        return $ret['data'];
    }

    

    // 政务钉钉附近定位方法
    public function dgworkNearby($data)
    {
        if (!(isset($data["longitude"]) && $data["longitude"] && isset($data["latitude"]) && $data["latitude"])) {
            return ['code' => ["0x034001", 'dingtalk']];
        }

        if (!isset($data["radius"])) {
            $data["radius"] = "1000";
        }

        $position = get_nearby_place($data);

        if (!$position) {
            return ['code' => ["0x034008", 'dingtalk']];
        }

        return $position;
    }

    //js-sdk 方法继承 后端接入
    public function dgworkSignpackage()
    {
        $ticket = $this->getJsApiTicket();
        if(!empty($ticket['code'])){
            return $ticket;
        }
        $data = [
            'ticket' => $ticket,
            'jsApiList' => [
                "alert",
                "getGeolocation",
            ]
        ];
        return $data;
    }

    // 暂只需要上面一个签名
    public function dgworkClientpackage($data)
    {
        $code = $this->getAccessToken();
        if (!$code || isset($code["code"])) {
            return $code;
        }
        if (isset($this->app_id) && !empty($this->app_id)) {
            $agentId = $this->app_id;
        } else {
            $agentId = $this->agentid;
        }
        $domain      = $this->domain;
        $corpId      = $this->corpid;
        $jsapiTicket = $this->getJsApiTicket();
        if (isset($data['from']) && $data['from'] == 'web') {
            $url = $domain . "/eoffice10/client/web/";
        } else {
            if(!empty($data['path'])){
                $url = $data['path'];   //angular下的实际路由
            }else{
                $url = $domain . "/eoffice10/client/mobile/";
            }
        }
        $timeStamp = time();
        $nonceStr  = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timeStamp&url=$url";

        $signature   = sha1($string);
        $signPackage = array(
            'url'       => $url,
            'nonceStr'  => $nonceStr,
            'agentId'   => $agentId,
            'timeStamp' => $timeStamp,
            'corpId'    => $corpId,
            'signature' => $signature);
        return $signPackage;
    }

    public function downloadFile($mediaId)
    {
        if(empty($mediaId)){
            return '';
        }
        $accessToken = $this->getAccessToken();
        if(!empty($accessToken['code'])){
            return '';
        }
        $executableClient = $this->executableClient;
        $executableClient->setApiName('/media/download');
        $executableClient -> addParameter("access_token", $accessToken);
        $executableClient -> addParameter("media_id", $mediaId);
        $ret = $executableClient->epaasCurlGet(3,1);
        if(!empty($ret['success']) && $ret['success'] == false){
            return '';
        }
        return $ret;
        
    }
    

    //推送消息
    public function pushMessage($option, $msgType = 'oa')
    {
        $config = $this->config;
        if (empty($config['is_push'])) {
            return false;
        }

        switch ($msgType) {
            case 'oa':
                if (count($option["toids"]) > 100) {
                    $userIds = array_chunk($option["toids"], 100);
                    foreach ($userIds as $user_id) {
                        $option['toids'] = $user_id;
                        $this->pushDgwork($option);
                    }
                } else {
                    $this->pushDgwork($option);
                }
                break;
            default:
                break;
        }
    }
    // 工作通知消息
    public function pushDgwork($option)
    {
        // $accessToken = $this->getAccessToken();
        // if(!empty($accessToken['code'])){
        //     return '';
        // };

        //换用户
        $employeeCodeList = $this->tranferDgworkUser($option["toids"] ?? '');
        if(empty($employeeCodeList)){
            return '';
        }
        $msg = $this->getTemplateToPush($option);
        $executableClient = $this->executableClient;
        $executableClient->setApiName('/message/workNotification');
        // $executableClient -> addParameter("access_token", $accessToken);
        $executableClient -> addParameter("tenantId", $this->tenantId);
        $executableClient -> addParameter("receiverIds", $employeeCodeList);
        $executableClient -> addParameter("msg", $msg);
        $ret = $executableClient->epaasCurlPost(3,1);
        file_put_contents('send3.txt',json_encode($ret));
        if(!empty($ret['success']) && $ret['success'] == false){
            return '';
        }
    }

    protected function getTemplateToPush($option)
    {
//        文本消息的menu数组
        $arr_text = ['dingtalk-complete','attendancemachine-complete'];
        if (in_array($option['menu'],$arr_text)) {
            // 只需要发送文本消息
            $option["url"] = '';
            $option["pc_url"] = '';
        }
//      消息类型为OA消息
        $template = array(
            "msgtype" => "oa",
            "oa"      => array(
                "message_url"    => $option["url"],
                "pc_message_url" => $option["pc_url"],
                "head"           => array(
                    "bgcolor" => "FF4876FF",
                    "text"    => "",
                ),
                "body"           => array(
                    "title"   => "",
                    "content" => $option['content'],
                ),
            ),
        );
        // file_put_contents('txs.txt',json_encode($template));
        return urldecode(json_encode($template));
    }

    public function tranferDgworkUser($users)
    {
        if(empty($users)){
            return '';
        }
        if (!is_array($users)) {
            $users = explode(",", $users);
        }
        $str = "";
        foreach ($users as $useId) {
            $row = $this->dgworkUserRepository->getDgworkAcccountIdByUserId($useId);
            if ($row && $row["account_id"]) {
                $str .= $row["account_id"] . ",";
            }
        }

        return trim($str, ",");
    }

    // 通过员工 Code 列表获取员账号 ID
    public function getAccountIdByEmployeeCodeList($employeeCodeList)
    {
        if(empty($employeeCodeList)){
            return '';
        }
        // var_dump(json_encode($employeeCodeList));die;
        $executableClient = $this->executableClient;
        $executableClient->setApiName('/mozi/employee/listEmployeeAccountIds');
        // $executableClient -> addParameter("employeeCodes", json_encode($employeeCodeList));

        // foreach ($employeeCodeList as $employeeCode){
        //     $executableClient -> addParameter("employeeCodes", $employeeCode);
        // }
        $executableClient -> addParameter("employeeCodes", $employeeCodeList);
        
        $executableClient -> addParameter("tenantId", $this->tenantId);
        $ret = $executableClient->epaasCurlPost(3);
        if(!empty($ret['responseCode'])){
            return ['code' => [$ret['responseCode'], 'dgwork']];
        }
        if(empty($ret['data'])){
            // 其他错误再次调试。
            return ['code' => ["data_empty", 'dgwork']];
        }
        return $ret['data'];

    }












}
