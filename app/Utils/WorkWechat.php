<?php

namespace App\Utils;

use App\EofficeApp\WorkWechat\Repositories\WorkWechatRepository;
use App\EofficeApp\WorkWechat\Repositories\WorkWechatAppRepository;
use App\EofficeApp\WorkWechat\Services\WorkWechatService;
use App\EofficeApp\Qyweixin\Repositories\QyweixinTicketRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use App\Utils\Encode\WXBizMsgCrypt;
use App\EofficeApp\Attachment\Services\AttachmentService;

define('TOKEN', 'eoffice9');

//企业微信
class WorkWechat {

    public $corpid;
    public $secret;
    public $agentid = 0;
    public $access_token;
    public $syncMustToken;
    public $encodingAesKey;
    public $token_time;
    public $create_time;
    public $is_push = 0;
    public $autoMsg = '';
    public $domain;
    //引入的资源库
    public $wXBizMsgCrypt;
    public $userRepository;
   // public $qyweixinTokenRepository;
    private $workWechatRepository;
    private $workWechatAppRepository;
    private $workWechatService;
    public $qyweixinTicketRepository;
    //附件
    public $attachmentService;

    //菜单配置 


    public function __construct(
        UserRepository $userRepository,
        WorkWechatRepository $workWechatRepository,
        QyweixinTicketRepository $qyweixinTicketRepository,
        AttachmentService $attachmentService,
        WorkWechatAppRepository $workWechatAppRepository,
        WorkWechatService $workWechatService
    ) {
        $this->qyweixinTicketRepository = $qyweixinTicketRepository;
        $this->attachmentService = $attachmentService;
        $this->userRepository = $userRepository;
        // $this->qyweixinTokenRepository = $workWechatRepository;
        $this->workWechatRepository = $workWechatRepository;
        $this->workWechatAppRepository = $workWechatAppRepository;
        $this->workWechatService = $workWechatService;

        $this->getAccessToken();
        //$this->wXBizMsgCrypt = new WXBizMsgCrypt(TOKEN, $this->encodingAesKey, $this->corpid);
    }

    //验证签名
    public function valid() {
        if ($this->syncMustToken && $this->encodingAesKey) {
            $echoStr = $_GET["echostr"];
            $msg_signature = $_GET["msg_signature"];
            $timestamp = $_GET["timestamp"];
            $nonce = $_GET["nonce"];
            $wXBizMsgCrypt = new WXBizMsgCrypt($this->syncMustToken, $this->encodingAesKey, $this->corpid);
            // 需要返回的明文
            $respondstr = "";
            $errCode = $wXBizMsgCrypt->VerifyURL($msg_signature, $timestamp, $nonce, $echoStr, $respondstr);
            if ($errCode === 0) {
                echo $respondstr;
            } else {
                echo $errCode;
            }
            exit;
        }else{
            echo '';
            exit();
        }

    }

    //响应
    public function responseMsg($param=[]) {
        @$postStr = file_get_contents("php://input");
        if (!$postStr) {
            echo "error";
            exit;
        }
        libxml_disable_entity_loader(true);
        $sMsg = '';
        if (isset($param['msg_signature'])&&$param['timestamp']&&$param['nonce']&&$this->syncMustToken && $this->encodingAesKey){
            $wXBizMsgCrypt = new WXBizMsgCrypt($this->syncMustToken, $this->encodingAesKey, $this->corpid);
            $errCode = $wXBizMsgCrypt->DecryptMsg($param['msg_signature'], trim($param['timestamp']), trim($param['nonce']), $postStr, $sMsg);
            if ($errCode===0){
                $xmlElement = simplexml_load_string($sMsg, 'SimpleXMLElement', LIBXML_NOCDATA);
                $this->workWechatService->abnormalDataLog($xmlElement);
                $msgType       = trim($xmlElement->MsgType); // event
                $fromUserName       = trim($xmlElement->FromUserName); // sys
                $event       = trim($xmlElement->Event); // change_contact
                $changeType       = trim($xmlElement->ChangeType);
                if ($msgType=='event'&&$fromUserName=='sys'&&$event=='change_contact'){
                    switch ($changeType){
                        case 'create_party':
                            $deptId = trim($xmlElement->Id);
                            $deptName = trim($xmlElement->Name);
                            $deptParentId = trim($xmlElement->ParentId);
                            $deptOrder = trim($xmlElement->Order);
                            $createDeptData = [
                              'id'=>$deptId,
                              'name'=>$deptName,
                              'parentid'=>$deptParentId,
                              'order'=>$deptOrder,
                            ];
                            $result = $this->workWechatService->autoSync($createDeptData,'create_party');
                            $resultDate = [
                                    'result'=> $result,
                                    'data'=>$xmlElement
                                    ];
                            $this->workWechatService->abnormalDataLog($resultDate);
                            break;
                        case 'update_party':
                            $deptId = trim($xmlElement->Id);
//                            $deptName = trim($xmlElement->Name);
//                            $deptParentId = trim($xmlElement->ParentId);
                            $updateDeptData = [
                                'dept_id'=>$deptId,
//                                'dept_name'=>$deptName,
//                                'dept_parent_id'=>$deptParentId,
                            ];
                            $result = $this->workWechatService->autoSync($updateDeptData,'update_party');
                            $resultDate = [
                                'result'=> $result,
                                'data'=>$xmlElement
                            ];
                            $this->workWechatService->abnormalDataLog($resultDate);
                            break;
                        case 'delete_party':
                            $deptId = trim($xmlElement->Id);
                            $deleteDeptData = [
                                'dept_id'=>$deptId,
                            ];
                            $result = $this->workWechatService->autoSync($deleteDeptData,'delete_party');
                            $resultDate = [
                                'result'=> $result,
                                'data'=>$xmlElement
                            ];
                            $this->workWechatService->abnormalDataLog($resultDate);
                            break;
                        case 'create_user':
                            $userAccount = trim($xmlElement->UserID);
                            $userName = trim($xmlElement->Name);
                            $userDepartment = explode(',',trim($xmlElement->Department));
                            $userIsLeaderInDept = explode(',',trim($xmlElement->IsLeaderInDept));
                            $userMobile = trim($xmlElement->Mobile);
                            $userPosition = trim($xmlElement->Position);
                            $userGender = trim($xmlElement->Gender);
                            $userEmail = trim($xmlElement->Email);
//                            $userStatus = trim($xmlElement->Status);
//                            $userAvatar = trim($xmlElement->Avatar);
//                            $userAlias = trim($xmlElement->Alias);
                            $userTelephone = trim($xmlElement->Telephone);
                            $userAddress = trim($xmlElement->Address);
                            $addUser = [
                                'user_account'=>$userAccount,
                                'user_name'=>$userName,
                                'department'=>$userDepartment,
                                'user_is_leader_in_dept'=>$userIsLeaderInDept,
                                'mobile'=>$userMobile,
                                'position'=>$userPosition,
                                'gender'=>$userGender,
                                'email'=>$userEmail,
                                //'user_status'=>$userStatus,
                               // 'user_avatar'=>$userAvatar,
                               // 'user_alias'=>$userAlias,
                                // return app($this->userService)->getLoginUserInfo($user->user_id, []);
                                'telephone'=>$userTelephone,
                                'address'=>$userAddress,
                            ];
                            $result = $this->workWechatService->autoSync($addUser,'create_user');
                            $resultDate = [
                                'result'=> $result,
                                'data'=>$xmlElement
                            ];
                            $this->workWechatService->abnormalDataLog($resultDate);
                            break;
                        case 'update_user':
                            $userid = trim($xmlElement->UserID);
                            $userIsLeaderInDept = explode(',',trim($xmlElement->IsLeaderInDept));
                            $userAddress = trim($xmlElement->Address);
                            $updateUser = [
                                'userid'=>$userid,
                                'user_is_leader_in_dept'=>$userIsLeaderInDept,
                                'address'=>$userAddress,
                            ];
                            if (isset($xmlElement->NewUserID)){
                                $updateUser['new_user_id']  = trim($xmlElement->NewUserID);
                            }
                            if (isset($xmlElement->Name)){
                                $updateUser['user_name']  = trim($xmlElement->Name);
                            }
                            if (isset($xmlElement->Department)){
                                $updateUser['department']  = explode(',',trim($xmlElement->Department));
                            }

                            if (isset($xmlElement->Mobile)){
                                $updateUser['phone_number'] =  trim($xmlElement->Mobile);
                            }
                            if (isset($xmlElement->Email)){
                                $updateUser['email'] =  trim($xmlElement->Email);
                            }
                            if (isset($xmlElement->Gender)){
                                $updateUser['gender'] =  trim($xmlElement->Gender);
                            }
                            if (isset($xmlElement->Telephone)){
                                $updateUser['dept_phone_number'] =  trim($xmlElement->Telephone);
                            }
                            if (isset($xmlElement->Position)){
                                $updateUser['position'] =  trim($xmlElement->Position);
                            }

                            $result = $this->workWechatService->autoSync($updateUser,'update_user');
                            $resultDate = [
                                'result'=> $result,
                                'data'=>$xmlElement
                            ];
                            $this->workWechatService->abnormalDataLog($resultDate);
                            break;
                        case 'delete_user':
                            $deleteUser = [
                                'user_accounts'=> trim($xmlElement->UserID)
                            ];
                            $result = $this->workWechatService->autoSync($deleteUser,'delete_user');
                            $resultDate = [
                                'result'=> $result,
                                'data'=>$xmlElement
                            ];
                            $this->workWechatService->abnormalDataLog($resultDate);
                            break;
                    }
                    echo '';
                    exit();
                }
            }else{
                $error = [
                    'param'=>$param,
                    'postStr'=>$postStr,
                    'sMsg'=>$sMsg,
                ];
                $this->workWechatService->abnormalDataLog($error);
                echo '';
                exit();
            }
        }
        echo '';
        exit();
    }

    public function getAccessToken($agentId = 0){
        $wechat = $this->workWechatRepository->getWorkWechat();
        if (!isset($wechat->corpid) || empty($wechat->corpid)) {
            return false;
        }
        $this->corpid = $wechat->corpid;
        $this->domain = $wechat->domain;
        $this->is_push = $wechat->is_push;
        $this->syncMustToken = $wechat->sync_must_token;
        $this->encodingAesKey = $wechat->sync_must_encoding_aes_key;
        //做兼容处理，无agentId的取消息的应用id
        if ($agentId===0){
            $this->agentid = $wechat->sms_agent;;
            $this->secret = $wechat->secret;
        }else{
            $this->agentid = $agentId;
        }
        $this->access_token = $this->workWechatService->getAccessToken($this->agentid);
        return $this->access_token;
    }
//    public function getAccessToken() {
//        $wechat = $this->qyweixinTokenRepository->getWechat([]);
//        if (!isset($wechat->corpid)) {
//            return false;
//        }
//        $this->corpid = $wechat->corpid;
//        $this->secret = $wechat->secret;
//        $this->access_token = $wechat->access_token;
//        $this->domain = $wechat->domain;
//        $this->token_time = $wechat->token_time;
//        $this->is_push = $wechat->is_push;
//        $this->agentid = $wechat->agentid;
//        if (abs(time() - $this->token_time) > 7200) {
//            //GET请求的地址
//            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$this->corpid}&corpsecret={$this->secret}";
//
//            $wechatConnect = getHttps($url);
//            $connectList = json_decode($wechatConnect, true);
//            if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
//                $code = $connectList['errcode'];
//                return ['code' => ["$code", 'qyweixin']];
//            }
//            $data['access_token'] = $connectList['access_token'];
//            $data['token_time'] = time();
//
//            $this->qyweixinTokenRepository->updateData($data, ['corpid' => $this->corpid]);
//            $this->access_token = $data['access_token'];
//            return $data['access_token'];
//        }
//        return $wechat->access_token;
//    }

    // 
//    public function createApp() {
//
//        $agentId = $this->getAgendId();
//        if (!$agentId) {
//            return ['code' => ["0x000114", 'qyweixin']];
//        }
//
//        //删除以往agentid
//        $this->deleteMenuByAgentId($agentId);
//        $option = array();
//
//        $option['name'] = "消息中心";
//        $option['type'] = 'view';
//        $value = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
//        $redirect = $this->domain . '/eoffice10/server/public/api/qywechat-access?type=31';
//        $redirect_uri = urlencode($redirect);
//        $build = array(
//            'appid' => $this->corpid,
//            'redirect_uri' => $redirect_uri,
//            'response_type' => 'code',
//            'scope' => 'snsapi_base',
//            'state' => '1-' . $agentId,
//        );
//        $value .= http_build_query($build) . '#wechat_redirect';
//
//        $option['value'] = $value;
//        $imenu = [];
//        $imenu[] = $this->dealKey($option);
//        ;
//        $menu = array('button' => $imenu);
//
//        return $this->createMenu($agentId, $menu);
//    }

    public function getAgendId() {
        $agentid = $this->agentid;
        if (!$agentid) {
            $wechat = $this->workWechatRepository->getWechat();
            if (!isset($wechat->corpid)) {
                $agentid = 0;
            }
        }

        return $agentid;
    }

//    public function deleteMenuByAgentId($agentId) {
//        if (!$agentId) {
//            return;
//        }
//        $access_token = $this->getAccessToken();
//
//        $api = "https://qyapi.weixin.qq.com/cgi-bin/menu/delete?access_token=$access_token&agentid=$agentId";
//        return getHttps($api);
//    }

    public function queryWithFilter($data, $filter) {
        if (!is_array($data) || !is_array($filter)) {
            return false;
        }
        $respon = [];
        $temp = array();
        foreach ($data as $row) {

            foreach ($filter as $k) {
                $temp[$k] = $row[$k];
            }
            $respon[] = $temp;
        }

        return $respon;
    }

    //创建菜单
    public function createMenu($agentid, $menu) {
        $access_token = $this->getAccessToken();

        $api = "https://qyapi.weixin.qq.com/cgi-bin/menu/create?agentid={$agentid}&access_token=" . $access_token;
        $code = urldecode(json_encode($menu));

        $wechatTemp = getHttps($api, $code);
        $wechatData = json_decode($wechatTemp, true);
        if (isset($wechatData['errcode']) && $wechatData['errcode'] != 0) {
            $code = $wechatData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }

        return $wechatData;
    }

    public function dealKey($array) {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $temp = array();

                $type = $value['type'];
                switch ($type) {
                    case 'view':
                        $temp['url'] = urlencode($value['value']);
                        break;
                    case 'click':
                        $temp['key'] = urlencode($value['value']);
                        break;
                }
                $temp['type'] = $type;
                $temp['name'] = urlencode($value['name']);

                $result[] = $temp;
            } else {
                if ('type' == $key) {
                    switch ($value) {
                        case 'view':
                            $result['url'] = urlencode($array['value']);
                            break;
                        case 'click':
                            $temp['key'] = urlencode($array['value']);
                            break;
                    }
                }
                $result[$key] = urlencode($value);
                unset($result['value']);
            }
        }

        return $result;
    }

    //js-sdk 方法继承
    public function qywechatSignPackage($data = null) {
        $url = isset($data["url"]) && $data["url"] ? $data["url"] : "";

        if (!$url) {
            return ['code' => ["0x000115", 'qyweixin']];
        }

        $url = urldecode($url);

        $jsapiTicket = $this->getJsApiTicket();

        if (isset($jsapiTicket['code']) && $jsapiTicket['code'] != 0) {
            return $jsapiTicket;
        }
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->corpid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket() {
        $data = $this->qyweixinTicketRepository->getTickt([]);
        if (!$data) {
            $expire_time = 0;
        } else {
            $expire_time = $data->expire_time;
        }
        if (abs(time() - $expire_time) > 0) {
            $accessToken = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $wechatTemp = getHttps($url);
            $wechatData = json_decode($wechatTemp, true);
            if (isset($wechatData['errcode']) && $wechatData['errcode'] != 0) {
                $code = $wechatData['errcode'];
                return ['code' => ["$code", 'qyweixin']];
            }



            $ticket = $wechatData["ticket"];
            if ($ticket) {
                $wechat["jsapi_ticket"] = $ticket;
                $wechat["expire_time"] = time() + 7000;
                $this->qyweixinTicketRepository->truncateWechat();
                $this->qyweixinTicketRepository->insertData($wechat);
            } else {
                return ['code' => ["-1", 'qyweixin']];
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }

        return $ticket;
    }

    // js-sdk end
    //高德地图
    public function geocodeAttendance($data) {
        $address = geocode_to_address($data);
        if (isset($address['code'])) {
            return $address;
        }
        if (!$address) {
            return ['code' => ["40059", 'qyweixin']]; //不合法的上报地理位置标志位
        }
        return $address;
    }

    //下载图片到自己的服务器
    public function qyweixinMove($data) {

        if (is_array($data["serverId"])) {
            $mediaIds = $data["serverId"];
        } else {
            $mediaIds = explode(",", trim($data["serverId"], ","));
        }

        $fileName = [];
        $fileIds = [];
        $thumbWidth = config('eoffice.thumbWidth', 100);
        $thumbHight = config('eoffice.thumbHight', 40);
        $thumbPrefix = config('eoffice.thumbPrefix', "thumb_");
        $attachmentFile = 1; //图片格式
        $attachment_base_path = getAttachmentDir();

        $temp = [];
        foreach ($mediaIds as $mediaId) {

            $accessToken = $this->getAccessToken();
            $url = "https://qyapi.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$mediaId";

            ob_start(); //打开输出
            readfile($url); //输出图片文件
            $img = ob_get_contents(); //得到浏览器输出
            ob_end_clean(); //清除输出并关闭
            //生成附件ID
            $attachmentId = md5(time() . $mediaId . rand(1000000, 9999999));
            $newPath = $this->attachmentService->createCustomDir($attachmentId);
            $mediaIdName = $mediaId . ".jpg";
            $originName = $newPath . $mediaIdName;
            $size = strlen($img); //得到图片大小
            $fp2 = @fopen($originName, "a");
            fwrite($fp2, $img); //向当前目录写入图片文件，并重新命名
            fclose($fp2);

            $thumbAttachmentName = scaleImage($originName, $thumbWidth, $thumbHight, $thumbPrefix);
            //       组装数据 存入附件表
            $attachment_path = str_replace($attachment_base_path, '', $newPath);
            $tableData = [
                "attachment_id" => $attachmentId,
                "attachment_name" => $mediaId . ".jpg",
                "affect_attachment_name" => $mediaId . ".jpg",
                "thumb_attachment_name" => $thumbAttachmentName,
                "attachment_size" => $size,
                "attachment_type" => "jpg",
                "attachment_create_user" => "",
                "attachment_base_path" => $attachment_base_path,
                "attachment_path" => $attachment_path,
                "attachment_file" => 1,
                "attachment_time" => date("Y-m-d H:i:s", time())
            ];
            $this->attachmentService->addAttachment($tableData);

            //生成64code 
            $path = $attachment_base_path . $attachment_path . DIRECTORY_SEPARATOR . $thumbAttachmentName;
            $temp[] = [
                "attachmentId" => $attachmentId,
                "attachmentName" => $mediaIdName,
                "attachmentThumb" => imageToBase64($path),
                // 为了兼容统一的附件接口增加attachmentType属性
                "attachmentSize"        => $size,
                "attachmentType"        => 'jpg',
                "attachmentMark"        => 1,
            ];
        }


        return $temp;
    }

    //wechat 会话配置
    public function qywechatChat($data) {
        $accessToken = $this->getAccessToken();
        $api = "https://qyapi.weixin.qq.com/cgi-bin/ticket/get?access_token=$accessToken&type=contact";
        $wechatTemp = getHttps($api);
        if (!$wechatTemp) {
            return false;
        }
        $wechatData = json_decode($wechatTemp, true);
        if (isset($wechatData['errcode']) && $wechatData['errcode'] != 0) {
            $code = $wechatData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }

        $ticket = $wechatData["ticket"];
        $groupId = $wechatData["group_id"];

        $url = $data["url"];
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "group_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "groupId" => $groupId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    //推送消息
    public function pushMessage($option, $msgType = 'news') {

        if (!$this->is_push) {
            return false;
        }

        switch ($msgType) {
            case 'news':
                $this->pushNews($option);
                break;
            default:
                break;
        }
    }

    public function pushNews($option) {
        //链接
        $content = $option["content"];
        $agentid = $this->getAgendId();
        $toUsers = $option["toids"];
        //解析用户

        if (!is_array($toUsers)) {
            $toUsers = explode(",", $toUsers);
        }

        //匹配用户
        $pushUser = "";
        foreach ($toUsers as $user) {
            $temp = $this->matchUser($user);
            if ($temp && isset($temp["user_id"])) {
                $pushUser .= $temp["user_id"] . "|";
            }
        }



        $pushUserFiter = trim($pushUser, "|");

        $redirectUrl = $option["url"];

        $access_token = $this->getAccessToken();
        $api = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=$access_token";
        $msgTemp = array(
            'touser' => $pushUserFiter, // --- 推送的用户
            'toparty' => "",
            'totag' => "",
            'msgtype' => 'news',
            'agentid' => $agentid, //其他 ----- 后面根据配置重新选择 todo
            'news' => array(
                'articles' => array(
                    array(
                        'title' => html_entity_decode($content), //内容
                        'description' => html_entity_decode($content),
                        'url' => $redirectUrl, // 链接
                        'picurl' => ""
                    )
                )
            )
        );



        $msg = urldecode(json_encode($msgTemp));

        return getHttps($api, $msg);
    }

    public function matchUser($tempUserId) {
        $access_token = $this->getAccessToken();

        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&userid={$tempUserId}";
        $postJson = getHttps($url);
        if (!$postJson) {
            return false;
        }
        $postObj = json_decode($postJson, true);
        if (isset($postObj['errcode']) && $postObj['errcode'] != 0) {
            $code = $postObj['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }
        $weixinid = isset($postObj["weixinid"]) ? $postObj["weixinid"] : "";
        $mobile = isset($postObj["mobile"]) ? $postObj["mobile"] : "";
        $email = isset($postObj["email"]) ? $postObj["email"] : "";

        if ($weixinid || $mobile || $email) {
            if ($mobile) {
                $where = ["phone_number" => [$mobile]];
            } else if ($weixinid) {
                $where = ["weixin" => [$weixinid]];
            } else if ($email) {
                $where = ["email" => [$email]];
            }
            $user = $this->userRepository->checkUserByWhere($where);
            return $user;
        } else {
            return null;
        }
    }

    public function userListWechat() {

        $access_token = $this->getAccessToken();
        $api = "https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token=$access_token&department_id=1&fetch_child=1&status=0 ";
        return getHttps($api);
    }

    public function createPath() {
        $attachmentId = "";
        $newPath = $this->attachmentService->createCustomDir($attachmentId);
        return $newPath;
    }

    //上传临时文件
    public function uploadTempFile($file, $type) {

        //放到getHttps中就会报错！搞不清楚了 擦
        $access_token = $this->getAccessToken();
        $fields = array('media' => new \CURLFile($file));
        $url = "https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token=$access_token&type=$type";


        try {
            if (function_exists('curl_init')) {

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_SSLVERSION, 1);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);

                if ($output === false) {
                    $res = array(
                        "errcode" => "0x033003",
                        "errmsg" => curl_error($ch)
                    );
                    $output = json_encode($res);
                }

                curl_close($ch);
            } else {
                $res = array(
                    "errcode" => "0x033003",
                    "errmsg" => "CURL扩展没有开启!"
                );
                $output = json_encode($res);
            }
        } catch (Exception $exc) {

            $res = array(
                "errcode" => "0x033003",
                "errmsg" => $exc->getTraceAsString()
            );

            $output = json_encode($res);
        }
        return $output;
    }

    function batchSyncuser($user_media_id) {

        $access_token = $this->getAccessToken();

        $userUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/replaceuser?access_token=$access_token";
        $fields = array('media_id' => $user_media_id);
        $userMsg = urldecode(json_encode($fields));

        $vl = getHttps($userUrl, $userMsg);
        $vlData = json_decode($vl, true);
        if (isset($vlData['errcode']) && $vlData['errcode']) {
            $code = $vlData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        } else {
            $jobid = $vlData["jobid"];
            $successUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/getresult?access_token=$access_token&jobid=$jobid";
            $tempData = getHttps($successUrl);
            $v2Data = json_decode($tempData, true);

            //写入日志文件中
            $logContent = $tempData;
            $newPath = $this->getTempPath();
            $logfile = $newPath . DIRECTORY_SEPARATOR . "import-enterprise-account.log"; //企业号 

            $fp2 = @fopen($logfile, "a");

            if (isset($v2Data['errcode']) && $v2Data['errcode']) {
                $code = $v2Data['errcode'];
                $result = ['code' => ["$code", 'qyweixin']];
            } else {
                $result = "ok";
            }
            fwrite($fp2, $logContent); //向当前目录写入图片文件，并重新命名
            fclose($fp2);
            return $result;
        }
    }

    function batchSyncDept($dept_media_id) {
        $access_token = $this->getAccessToken();
        //同步通讯录
        $deptUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/replaceparty?access_token=$access_token";
        $deptFields = array('media_id' => $dept_media_id);
        $deptMsg = urldecode(json_encode($deptFields));
        $vl = getHttps($deptUrl, $deptMsg);
        $vlData = json_decode($vl, true);

        if (isset($vlData['errcode']) && $vlData['errcode'] != 0) {
            $code = $vlData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        } else {
            $jobid = $vlData["jobid"];
            $successUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/getresult?access_token=$access_token&jobid=$jobid";
            $tempData = getHttps($successUrl);
            $v2Data = json_decode($tempData, true);

            if (isset($v2Data['errcode']) && $v2Data['errcode'] != 0) {
                $code = $vlData['errcode'];
                return ['code' => ["$code", 'qyweixin']];
            }
        }

        return "ok";
    }

    function createUser($data) {
        $access_token = $this->getAccessToken();
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/create?access_token=$access_token";
        $dataTemp = urldecode(json_encode($data));
        $tempData = getHttps($url, $dataTemp);
        $resData = json_decode($tempData, true);
        if (isset($resData['errcode']) && $resData['errcode'] != 0) {
            $code = $resData['errcode'];
            return ['code' => ["$code", 'qyweixin']];
        }

        return "ok";
    }

    function qywechatNearby($data) {
        if (!(isset($data["longitude"]) && $data["longitude"] && isset($data["latitude"]) && $data["latitude"])) {
            return ['code' => ["0x034001", 'qyweixin']];
        }

        if (!isset($data["radius"])) {
            $data["radius"] = "1000";
        }

        $position = get_nearby_place($data);

        if (!$position) {
            return ['code' => ["0x034008", 'qyweixin']];
        }

        return $position;
    }

    public function getWechatApp() {
        $access_token = $this->getAccessToken();
        if ($access_token) {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/agent/list?access_token=$access_token";
            return getHttps($url);
        } else {
            return false;
        }
    }

    private function getTempPath() {
        $uploadDir = "";
        $attachBase = "www/eoffice10/server/public/wechat";
        $docPath = str_replace('\\', '/', $_SERVER["DOCUMENT_ROOT"]);
        $docPathTemp = rtrim($docPath, "/");
        $docNum = strripos($docPathTemp, "/");
        $docFinalPath = substr($docPathTemp, 0, $docNum);
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //winos系统
            if (strripos($attachBase, ":/") || strripos($attachBase, ":\\")) {
                $uploadDir = rtrim(str_replace('\\', '/', $attachBase), "/") . DIRECTORY_SEPARATOR;
            } else {
                $attachBase = str_replace('\\', '/', $attachBase);
                $uploadDir = rtrim($docFinalPath, "/") . "/" . ltrim($attachBase, "/");
            }
        } else {
            if (substr($attachBase, 0, 1) == DIRECTORY_SEPARATOR) {
                $uploadDir = rtrim(str_replace('\\', '/', $attachBase), "/") . DIRECTORY_SEPARATOR;
            } else {
                $attachBase = str_replace('\\', '/', $attachBase);
                $uploadDir = rtrim($docFinalPath, "/") . "/" . ltrim($attachBase, "/");
            }
        }

        $uploadDir = str_replace('\\', '/', $uploadDir);
        if (!$uploadDir) {
            return ['code' => ['0x011019', 'upload']]; //目录不存在
        }
        //通过ID查询 如果没有 直接返回错误  //eoffice10\client\app\web\docs
        return rtrim($uploadDir, "/");
    }

}
