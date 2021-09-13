<?php

namespace App\Utils;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\System\Remind\Repositories\SystemRemindsRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\Weixin\Repositories\WeixinReplyRepository;
use App\EofficeApp\Weixin\Repositories\WeixinReplyTemplateRepository;
use App\EofficeApp\Weixin\Repositories\WeixinTicketRepository;
use App\EofficeApp\Weixin\Repositories\WeixinTokenRepository;
use App\EofficeApp\Weixin\Repositories\WeixinUserInfoRepository;
use App\EofficeApp\Weixin\Repositories\WeixinUserRepository;

define('WEACHATOKEN', 'eoffice9');

class Weixin
{

    protected $appid;
    protected $appsecret;
    protected $access_token;
    protected $token_time  = 0;
    protected $create_time = 0;
    protected $default_text;
    protected $is_push = 0;
    protected $domain;
    protected $tempId;
    //引入的资源库

    protected $weixinUserRepository;
    protected $weixinUserInfoRepository;
    protected $userRepository;
    protected $weixinTokenRepository;
    protected $weixinTicketRepository;
    protected $attachmentService;
    //消息
    protected $systemRemindsRepository;
    protected $weixinReplyRepository;
    protected $weixinReplyTemplateRepository;
    public function __construct(
        WeixinUserRepository $weixinUserRepository, WeixinUserInfoRepository $weixinUserInfoRepository, UserRepository $userRepository, WeixinTokenRepository $weixinTokenRepository, WeixinTicketRepository $weixinTicketRepository, AttachmentService $attachmentService, SystemRemindsRepository $systemRemindsRepository,WeixinReplyRepository $weixinReplyRepository,WeixinReplyTemplateRepository $weixinReplyTemplateRepository
    ) {
        $this->weixinUserRepository     = $weixinUserRepository;
        $this->weixinUserInfoRepository = $weixinUserInfoRepository;
        $this->userRepository           = $userRepository;
        $this->weixinTokenRepository    = $weixinTokenRepository;
        $this->weixinTicketRepository   = $weixinTicketRepository;
        $this->attachmentService        = $attachmentService;
        $this->systemRemindsRepository  = $systemRemindsRepository;
        $this->weixinReplyRepository  = $weixinReplyRepository;
        $this->weixinReplyTemplateRepository  = $weixinReplyTemplateRepository;
        $this->getAccessToken();
    }

    //验证签名
    public function valid()
    {

        $echoStr   = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce     = $_GET["nonce"];
        $token     = WEACHATOKEN;

        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            echo $echoStr;
            exit;
        }
    }

    //响应
    public function responseMsg()
    {

        @$postStr = file_get_contents("php://input");

        if (!$postStr) {
            echo "error";
            exit;
        }
        libxml_disable_entity_loader(true);
        $xmlElement = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $type       = trim($xmlElement->MsgType);
        switch ($type) {
            //接收事件
            case "event":
                $result = $this->receiveEvent($xmlElement); //ddd
                break;
            //接收文本消息
            case "text":
                $result = $this->receiveText($xmlElement);
                break;
            //接收图片消息
            case "image":
                $result = $this->receiveText($xmlElement);
                break;
            //接收语音消息
            case "voice":
                $result = $this->receiveText($xmlElement);
                break;
            //接收视频消息
            case "video":
                $result = $this->receiveText($xmlElement);
                break;
            //接收小视频消息
            case "shortvideo":
                $result = $this->receiveText($xmlElement);
                break;
            //接收地理位置消息
            case "location":
                $result = $this->receiveText($xmlElement);
                break;
            //接收链接消息
            case "link":
                $result = $this->receiveText($xmlElement);
                break;
            //接收文本消息
            default:
                $result = "unknown msg type: " . $type;
                break;
        }
        echo $result;
//        switch ($type) {
//            case "event":
//                $result = $this->receiveEvent($xmlElement); //ddd
//                break;
//            case "text":
//                $result = $this->receiveText($xmlElement);
//                break;
//
//            default:
//                $result = "unknown msg type: " . $type;
//                break;
//        }
//        echo $result;
    }

    public function getAccessToken($flag = null)
    {

        $weixin = $this->weixinTokenRepository->getWeixinToken();

        if (!isset($weixin->appid)) {
            return false;
        }

        $this->appid        = $weixin->appid;
        $this->appsecret    = $weixin->appsecret;
        $this->access_token = $weixin->access_token;
        $this->default_text = $weixin->default_text;
        $this->domain       = $weixin->domain;
        $this->tempId       = $weixin->temp_id;
        $this->is_push      = $weixin->is_push;

        if ($flag) {
            $weixin->token_time = 0; //强制更新
        }

        if (abs(time() - $weixin->token_time) > 7000) { // 过期时间是7000秒，留200秒作为缓冲时间
            //GET请求的地址
            $url           = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$weixin->appid}&secret={$weixin->appsecret}";
            $weixinConnect = getHttps($url);
            $connectList   = json_decode($weixinConnect, true);
            if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
                $code = $connectList['errcode'];
                return ['code' => ["$code", 'weixin']];
            }
            if (isset($connectList['access_token'])) {
                $data['access_token'] = $connectList['access_token'];
                $data['token_time']   = time();

                $this->weixinTokenRepository->updateData($data, ['appid' => $weixin->appid]);
                $this->access_token = $data['access_token'];
                return $data['access_token'];
            }

        }
        return $weixin->access_token;
    }

    protected function subscribe($openid)
    {
        if (!$openid) {
            return false;
        }
        $time = time();
        $data = [
            "openid"      => $openid,
            "create_time" => $time,
        ];

        $this->weixinUserRepository->insertData($data);
        $res = $this->saveUserInfo($openid);
        return $res;
    }

    protected function unsubscribe($openid)
    {
        if (!$openid) {
            return false;
        }

        $s1 = $this->weixinUserRepository->deleteByWhere(["openid" => $openid]);
        $s2 = $this->weixinUserInfoRepository->deleteByWhere(["openid" => $openid]);

        return $s1 && $s2;
    }

    //接收事件消息
    protected function receiveEvent($object)
    {

        $content = "";
        switch (strtolower($object->Event)) {

            case "subscribe":
                $openid = $object->FromUserName;
                $this->subscribe($openid); // 关注
                $key = $object->EventKey;
                $arr = explode('_', $key);

                $content = $this->default_text ? $this->default_text : 'Welcome！';

                if (isset($arr[1]) && $arr[1]) {
                    // 排除WV00000000
                    if ('999999' == $arr[1]) {
                        $user_id = 'admin';
                    } else {
                        $user_id = 'WV' . str_pad($arr[1], 8, '0', STR_PAD_LEFT);
                    }

                    $ws = [
                        "user_id" => [$user_id],
                        "openid"  => [[$openid], "!="],
                    ];

                    $row = $this->weixinUserRepository->getInfoByWhere($ws);

                    if (isset($row['user_id'])  && isset($row) ) {
                        $content .= trans('weixin.already_bind_other');
                    } else {
                        $this->bindingMP($user_id, $openid);
                    }
                }

                break;
            case "unsubscribe":
                $openid = $object->FromUserName;
                $this->unsubscribe($openid);

                $content = "";
                break;
            case "scan":

                $key    = $object->EventKey;
                $openid = $object->FromUserName;

                if (!$key) {
                    $content = trans('weixin.system_exception');
                    break;
                }

                if ('999999' == $key) {
                    $user_id = 'admin';
                } else {
                    $user_id = 'WV' . str_pad($key, 8, '0', STR_PAD_LEFT);
                }

                $content = $this->bindingMP($user_id, $openid);
                break;
            case "click":

                switch ($object->EventKey) {

                    case 'attendance':
                        $content = "attendance ".trans("weixin.click_event");
                        break;

                    case 'autoLogin':
                        $content = "autoLogin ".trans("weixin.click_event");
                        break;

                    default:
                        break;
                }

                break;
            case "view":
                $content = "view ".trans("weixin.click_event");
                break;
            case "location":
                //缓存用户位置
                //                $opt['openid'] = $object->FromUserName;
                //                $opt['lng'] = $object->Longitude;
                //                $opt['lat'] = $object->Latitude;
                //$content = $this->cacheUserLocation($opt);
                break;
            default:

                break;
        }
        //回复空串，微信不会做任何处理
        if(empty($content)){
            return '';
        }
        $result = $this->transmitText($object, $content);

        return $result;
    }

    //接收文本消息
    protected function receiveText($object)
    {
        global $_lang;
        $keyword = trim($object->Content);

        $allReplyData = $this->weixinReplyRepository->getData();
        $replyContent = '';
        if (!empty($allReplyData)) {
            $autoReply = isset($allReplyData['auto_reply']) ? $allReplyData['auto_reply'] : false;
            $keywordsAutoReply = isset($allReplyData['keywords_auto_reply']) ? $allReplyData['keywords_auto_reply'] : false;
            //优先检查关键字回复
            if ($keywordsAutoReply) {
                $keywordsContentJson = isset($allReplyData['keywords_template_content']) ? $allReplyData['keywords_template_content'] : '';
                if (!empty($keywordsContentJson)) {
                    $keywordsContent = json_decode($keywordsContentJson, true);
                    foreach ($keywordsContent as $key => $keywordArr) {
                        if ($keywordArr['keywords'] == trim($keyword)) {
                            if (isset($keywordArr['keywords_template_id']) && !empty($keywordArr['keywords_template_id'])) {
                                $keywordsReplyTemplateContent = $this->weixinReplyTemplateRepository->getDataById($keywordArr['keywords_template_id']);
                                switch ($keywordsReplyTemplateContent['template_type']) {
                                    case 'text':
                                        $autoReplyContent = $keywordsReplyTemplateContent['text_content'];
                                        $replyContent = strip_tags($autoReplyContent, '\n');
                                        $replyContent = html_entity_decode($replyContent);
                                        if (empty($replyContent)) {
                                            exit();
                                        }
                                        return $this->transmitText($object, $replyContent);
                                        break;
                                    case 'news':
                                        $newsTitle = isset($keywordsReplyTemplateContent['news_title']) && !empty($keywordsReplyTemplateContent['news_title']) ? $keywordsReplyTemplateContent['news_title'] : exit();
                                        $newsDescription = isset($keywordsReplyTemplateContent['news_description']) && !empty($keywordsReplyTemplateContent['news_description']) ? $keywordsReplyTemplateContent['news_description'] : exit();
                                        $newsUrl = isset($keywordsReplyTemplateContent['news_url']) && !empty($keywordsReplyTemplateContent['news_url']) ? $keywordsReplyTemplateContent['news_url'] : exit();
                                        $newsShareToken = isset($keywordsReplyTemplateContent['news_share_token']) && !empty($keywordsReplyTemplateContent['news_share_token']) ? $keywordsReplyTemplateContent['news_share_token'] : exit();
                                        $imgUrl = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST . '/eoffice10/server/public/api/attachment/share/' . $newsShareToken;
                                        //$imgUrl ='http://bpdwxeoffice.weaver.cn/eoffice10/server/public/api/attachment/share/' . $newsShareToken;
                                        return $this->transmitNews($object, $newsTitle, $newsDescription, $newsUrl, $imgUrl);
                                        break;
                                    default:
                                        break;
                                }
                            }
                            break;
                        }
                    }
                }
            }
            //内容自动内容回复
            if ($autoReply) {
                $autoReplyTemplateId = isset($allReplyData['auto_reply_template_id']) ? $allReplyData['auto_reply_template_id'] : '';
                if (!empty($autoReplyTemplateId)) {
                    //$this->weixinReplyTemplateRepository
                    $autoReplyTemplateContent = $this->weixinReplyTemplateRepository->getDataById($autoReplyTemplateId);
                    switch ($autoReplyTemplateContent['template_type']) {
                        case 'text':
                            $autoReplyContent = $autoReplyTemplateContent['text_content'];
                            $replyContent = strip_tags($autoReplyContent, '\n');
                            $replyContent = html_entity_decode($replyContent);
                            if (empty($replyContent)) {
                                exit();
                            }
                            return $this->transmitText($object, $replyContent);
                            break;
                        case 'news':
                            $newsTitle = isset($autoReplyTemplateContent['news_title']) && !empty($autoReplyTemplateContent['news_title']) ? $autoReplyTemplateContent['news_title'] : exit();
                            $newsDescription = isset($autoReplyTemplateContent['news_description']) && !empty($autoReplyTemplateContent['news_description']) ? $autoReplyTemplateContent['news_description'] : exit();
                            $newsUrl = isset($autoReplyTemplateContent['news_url']) && !empty($autoReplyTemplateContent['news_url']) ? $autoReplyTemplateContent['news_url'] : exit();
                            $newsShareToken = isset($autoReplyTemplateContent['news_share_token']) && !empty($autoReplyTemplateContent['news_share_token']) ? $autoReplyTemplateContent['news_share_token'] : exit();
                            $imgUrl = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST . '/eoffice10/server/public/api/attachment/share/' . $newsShareToken;
                            return $this->transmitNews($object, $newsTitle, $newsDescription, $newsUrl, $imgUrl);
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        exit();
       /* $content = trans('weixin.welcome_office');
        return $this->transmitText($object, $content);*/
//        if (empty($replyContent)) {
//            exit();
//        }
//        return $this->transmitText($object, $replyContent);
        /*        global $_lang;
                $keyword = trim($object->Content);
                $content = trans('weixin.welcome_office');

                switch ($keyword) {

                    default:
                        break;
                }

                return $this->transmitText($object, $content);*/
    }

    public function bindingMP($user_id, $openid)
    {

        if (!$openid || !$user_id) {
            return false;
        }

        $time = time();

        $row         = $this->weixinUserRepository->getInfoByWhere(["openid" => [$openid]]);
        $checkID_row = $this->weixinUserRepository->getInfoByWhere(["user_id" => [$user_id]]);

        if (isset($row['user_id']) && $row['user_id']) {
            if ($row['user_id'] == $user_id) {
                $date = date('Y-m-d H:i', $row['auth_time']);
                return $date . trans('weixin.already_bind'); // 已经绑定
            } else {
                return trans('weixin.bind_other_account');
            }
        } else if (isset($checkID_row['openid']) && $checkID_row['openid']) {
            return trans('weixin.already_bind_other');
        } else if (isset($row['openid']) && $row['openid']) {
            $data = [
                "user_id"   => $user_id,
                "auth_time" => $time,
            ];
            $where = [
                "openid" => $openid,
            ];
            $res = $this->weixinUserRepository->updateData($data, $where);
        } else {
            $data = [
                "user_id"     => $user_id,
                "auth_time"   => $time,
                "openid"      => $openid,
                "create_time" => $time,
                "deleted"     => 0,
            ];
            $res = $this->weixinUserRepository->insertData($data);
        }

        $content = $res ? trans('weixin.bind_success') : trans('weixin.bind_failed');

        return $content;
    }

    public function saveUserInfo($openid)
    {

        if (!$openid || !$this->access_token) {
            return false;
        }
        $api    = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$this->access_token}&openid={$openid}&lang=zh_CN";
        $result = getHttps($api);

        $info = json_decode($result, true);

        if (isset($info['errcode']) && $info['errcode'] > 4000) {
            return false;
        }

        $weixinData              = array_intersect_key($info, array_flip($this->weixinUserInfoRepository->getTableColumns()));
        $weixinData['subscribe'] = 1;
        return $this->weixinUserInfoRepository->insertData($weixinData);
    }

    public function getBindingQRcode($uid)
    {

        $userStr = '';

        if ('WV' == substr($uid, 0, 2)) {
            $userStr = (int) substr($uid, 2);
        }
        if ('admin' == $uid) {
            $userStr = '999999';
        }

        $ticket = $this->getTicket($userStr);

        if(isset($ticket['code'])){
            return $ticket;
        }

        if (!$ticket) {
            //强制更新getAccessToken
            $access_token = $this->getAccessToken(1);
            $ticket       = $this->getTicket($userStr);
        }

        if (!$ticket) {
            return ['code' => ["40001", 'weixin']];
        }

        if (empty($ticket['ticket'])) {
            return ['code' => ["40001", 'weixin']];
        }

        return $this->getQRcode($ticket['ticket'], $uid);
    }

    public function getTicket($scene_id = null)
    {
        $access_token = $this->getAccessToken();

        if (!$access_token) {
            return false;
        }

        if (is_array($access_token)){
            return $access_token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$access_token}";

        $array = array(
            'expire_seconds' => 3000,
            'action_name'    => 'QR_SCENE',
            'action_info'    => array(
                'scene' => array(
                    'scene_id' => $scene_id,
                ),
            ),
        );
        $post_json = json_encode($array);

        $json = getHttps($url, $post_json);
        return json_decode($json, true);
    }

    public function updateMenu($menu)
    {
        $access_token = $this->getAccessToken();
        if (is_string($access_token)){
            $api          = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
            $result       = getHttps($api, $menu);
            return $result;
        }else{
            return $access_token;
        }
    }

    public function getQRcode($ticket, $uid, $file = null)
    {

        $mediaId = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
        return $mediaId;
        $newPath = createCustomDir("qrcode/weixin/");
        $file    = $newPath . $uid . ".jpg"; //企业号
        if (file_exists($file)) {
            unlink($file);
        }
        //生成新的二维码
        ob_start(); //打开输出
        readfile($mediaId); //输出图片文件
        $img = ob_get_contents(); //得到浏览器输出
        ob_end_clean(); //清除输出并关闭
        $size = strlen($img); //得到图片大小
        $fp2  = @fopen($file, "a");
        fwrite($fp2, $img); //向当前目录写入图片文件，并重新命名
        fclose($fp2);

        return imageToBase64($file);
    }

    //xml格式化
    protected function transmitText($object, $content)
    {
        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    //图文xml格式化
    protected function transmitNews($object, $newsTitle, $newsDescription, $newsUrl, $imgUrl)
    {
        $xmlTpl = "<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[news]]></MsgType>
  <ArticleCount>1</ArticleCount>
  <Articles>
    <item>
      <Title><![CDATA[%s]]></Title>
      <Description><![CDATA[%s]]></Description>
      <PicUrl><![CDATA[%s]]></PicUrl>
      <Url><![CDATA[%s]]></Url>
    </item>
  </Articles>
</xml>";
/*        $title = '标题';
        $description = '描述';
        $picUrl = 'http://bpdwxeoffice.weaver.cn/eoffice10/server/public/api/attachment/index/e91ef274d2da754c583353a5244f009f?api_token=675956bb154114ee4bb53ef850856b8cea0ce36f0d701f3e753c729704ea2523684f93a37b19d060daba9417095a3164f8b3fa826a12065eeba20e754b1db46a';
        $url = 'www.baidu.com';*/
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $newsTitle, $newsDescription, $imgUrl, $newsUrl);
        return $result;
    }
    //js-sdk 方法继承
    public function weixinSignPackage($data = null)
    {

        $url = isset($data["url"]) && $data["url"] ? $data["url"] : "";

        if (!$url) {
            return ['code' => ["0x000115", 'weixin']];
        }

        $url         = urldecode($url);
        $jsapiTicket = $this->getJsApiTicket();

        if (isset($jsapiTicket['code']) && $jsapiTicket['code'] != 0) {
            return $jsapiTicket;
        }

        //$url = $this->domain . "/eoffice10/client/mobile/home/profile/attendance";
        $timestamp = time();
        $nonceStr  = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->appid,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string,
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket()
    {

        $data = $this->weixinTicketRepository->getTickt([]);
        if (!$data) {
            $expire_time = 0;
        } else {
            $expire_time = $data->expire_time;
        }
        if (abs(time() - $expire_time) > 7000) { //$ticket过期时间是7200秒，留200秒做为缓冲时间
            $accessToken = $this->getAccessToken();
            if(!is_string($accessToken)){
                return $accessToken;
            }
            // 如果是企业号用以下 URL 获取 ticket
            $url        = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $wechatTemp = getHttps($url);
            $wechatData = json_decode($wechatTemp, true);
            if (isset($wechatData['errcode']) && $wechatData['errcode'] != 0) {
                $code = $wechatData['errcode'];
                return ['code' => ["$code", 'weixin']];
            }

            $ticket = $wechatData["ticket"];
            if ($ticket) {
                $wechat["jsapi_ticket"] = $ticket;
                $wechat["expire_time"]  = time();
                $this->weixinTicketRepository->truncateWechat();
                $this->weixinTicketRepository->insertData($wechat);
            } else {
                return ['code' => ["-1", 'weixin']];
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }

        return $ticket;
    }

    //下载图片到自己的服务器
    public function weixinMove($data)
    {
        if (is_array($data["serverId"])) {
            $mediaIds = $data["serverId"];
        } else {
            $mediaIds = explode(",", trim($data["serverId"], ","));
        }

        $fileName             = [];
        $fileIds              = [];
        $thumbWidth           = config('eoffice.thumbWidth', 100);
        $thumbHight           = config('eoffice.thumbHight', 40);
        $thumbPrefix          = config('eoffice.thumbPrefix', "thumb_");
        $attachmentFile       = 1; //图片格式
        $attachment_base_path = getAttachmentDir();

        $temp = [];
        foreach ($mediaIds as $mediaId) {

            $accessToken = $this->getAccessToken();
            if(!is_string($accessToken)){
                return $accessToken;
            }
            //$url         = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$mediaId";
            $url         = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$mediaId";

            ob_start(); //打开输出
            readfile($url); //输出图片文件
            $img = ob_get_contents(); //得到浏览器输出
            ob_end_clean(); //清除输出并关闭
            //生成附件ID
            $attachmentId = md5(time() . $mediaId . rand(1000000, 9999999));
            $newPath      = $this->attachmentService->createCustomDir($attachmentId);
            $mediaIdName  = $mediaId . ".jpg";
            $originName   = $newPath . $mediaIdName;
            $size         = strlen($img); //得到图片大小
            $fp2          = @fopen($originName, "a");
            fwrite($fp2, $img); //向当前目录写入图片文件，并重新命名
            fclose($fp2);

            $thumbAttachmentName = scaleImage($originName, $thumbWidth, $thumbHight, $thumbPrefix);
            //       组装数据 存入附件表
            $attachment_path = str_replace($attachment_base_path, '', $newPath);
            // $tableData       = [
            //     "attachment_id"          => $attachmentId,
            //     "attachment_name"        => $mediaId . ".jpg",
            //     "affect_attachment_name" => $mediaId . ".jpg",
            //     "thumb_attachment_name"  => $thumbAttachmentName,
            //     "attachment_size"        => $size,
            //     "attachment_type"        => "jpg",
            //     "attachment_create_user" => "",
            //     "attachment_base_path"   => $attachment_base_path,
            //     "attachment_path"        => $attachment_path,
            //     "attachment_file"        => 1,
            //     "attachment_time"        => date("Y-m-d H:i:s", time()),
            // ];
            // $this->attachmentService->addAttachment($tableData);
             $newFullFileName  = $newPath . DIRECTORY_SEPARATOR . $mediaId . ".jpg";
             $attachmentInfo = [
                "attachment_id"          => $attachmentId,
                "attachment_name"        => $mediaId . ".jpg",
                "affect_attachment_name" => $mediaId . ".jpg",
                'new_full_file_name'     => $newFullFileName,
                "thumb_attachment_name"  => $thumbAttachmentName,
                "attachment_size"        => $size,
                "attachment_type"        => 'jpg',
                "attachment_create_user" => '',
                "attachment_base_path"   => $attachment_base_path,
                "attachment_path"        => $attachment_path,
                "attachment_mark"        => 1,
                "relation_table"         => '',
                "rel_table_code"         => ""
            ];

            // return $this->handleAttachmentDataTerminal($attachmentInfo);
            $this->attachmentService->handleAttachmentDataTerminal($attachmentInfo);

            //生成64code
            $path   = $attachment_base_path . $attachment_path . DIRECTORY_SEPARATOR . $thumbAttachmentName;
            $temp[] = [
                "attachmentId"    => $attachmentId,
                "attachmentName"  => $mediaIdName,
                "attachmentThumb" => imageToBase64($path),
                // 为了兼容统一的附件接口增加attachmentType属性
                "attachmentSize"        => $size,
                "attachmentType"        => 'jpg',
                "attachmentMark"        => 1,
            ];
        }

        return $temp;
    }

    //推送消息
    public function pushMessage($option, $msgType = 'OA')
    {

        $weixin = $this->weixinTokenRepository->getWeixinToken();
        if (!empty($weixin)) {
            $is_push = $weixin->is_push;
            if (!$is_push) {
                return false;
            }
            switch ($msgType) {
                case 'OA':
                    $this->pushOA($option);
                    break;
                default:
                    break;
            }
        }

    }

    public function pushOA($option)
    {
        //通过 menu   type 确定消息类型
        $openids = $this->exchangeOpenid($option["toids"]);
		if (!empty($openids)) {
			foreach ($openids as $val) {
                $openId = $val->openid;
                if ($openId) {
                    if (isset($option['chat']) && !empty($option['chat'])) {
                        $this->chatMessage($option, $openId);
                    } else {
                        $this->templateMessage($option, $openId);
                    }

                }
            }
        return true;
		}
        
    }

    public function chatMessage($option, $openid)
    {
        $ws = [
            "openid" => [$openid],
        ];

        $row       = $this->weixinUserRepository->getInfoByWhere($ws);
        $user_name = "";
        if (!empty($row)) {
            $user_id   = $row->user_id;
            $user_name = $this->userRepository->getUserName($user_id);
        }

        $time   = date('Y-m-d H:i');
        $weixin = $this->weixinTokenRepository->getWeixinToken();
        $domain = $weixin->domain;
        //@消息特殊处理
        if (isset($option['urlParams']) && !empty($option['urlParams'])) {
            $urlParam = $option['urlParams'];
            $url = $domain . "/eoffice10/server/public/api/weixin-access?type=$urlParam";
        } else {
            $url = $domain . "/eoffice10/server/public/api/weixin-access?type=31";
        }
        //拆解用户
        $accessToken = $this->getAccessToken();
        if(is_string($accessToken)){
            $api         = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$accessToken";
            $template = array(
                'touser'      => $openid,
                'template_id' => $this->tempId,
                'url'         => $url,
                'topcolor'    => '#7B68EE',
                'data'        => array(
                    'first'    => array(
                        'value' => '',
                        'color' => '#000000',
                    ),
                    'keyword1' => array(
                        'value' =>  strip_tags(html_entity_decode($option["content"])),
                        'color' => '#0000FF',
                    ),
                    'keyword2' => array(
                        'value' => $option["from"],
                        'color' => '#000000',
                    ),
                    'keyword3' => array(
                        'value' => urlencode($time),
                        'color' => '#000000',
                    ),
                    'remark'   => array(
                        'value' => '',
                        'color' => '#000000',
                    ),
                ),
            );
            $msg = urldecode(json_encode($template));
            return getHttps($api, $msg);
        }

    }

    //切换信息
    public function exchangeOpenid($users)
    {
        //数组
        $where = [
            "user_id" => [$users, "in"],
        ];

        return $this->weixinUserRepository->exchangeOpenid($where);
    }

    public function templateMessage($option, $openid)
    {

        $search = [
            "remind_menu" => [$option["menu"]],
            "remind_type" => [$option["type"]],
        ];
        $tempMenu = $this->systemRemindsRepository->getRemindDetail($search);
        $ws       = [
            "openid" => [$openid],
        ];

        $row       = $this->weixinUserRepository->getInfoByWhere($ws);
        $user_name = "";
        if (!empty($row)) {
            $user_id   = $row->user_id;
            $user_name = $this->userRepository->getUserName($user_id);
        }
        $tempMenuName = trans('weixin.message_notify');
        if ($tempMenu) {
            $tempMenuName = $tempMenu->remind_name;
        }
        $time = date('Y-m-d H:i');

        //拆解用户
        $accessToken = $this->getAccessToken();
        // \Log::info('accessToken---'.$accessToken);
        if(!is_string($accessToken)){
            return $accessToken;
        }
        $api         = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$accessToken";
        // \Log::info('accessToken--api---'.$api);
        $template = array(
            'touser'      => $openid,
            'template_id' => $this->tempId,
            'url'         => $option["url"],
            'topcolor'    => '#7B68EE',
            'data'        => array(
                'first'    => array(
                    'value' => '',
                    'color' => '#000000',
                ),
                'keyword1' => array(
                    'value' => strip_tags(html_entity_decode($option["content"])) ,
                    'color' => '#0000FF',
                ),
                'keyword2' => array(
                    'value' => urlencode($user_name),
                    'color' => '#000000',
                ),
                'keyword3' => array(
                    'value' => urlencode($time),
                    'color' => '#000000',
                ),
                'remark'   => array(
                    'value' => '',
                    'color' => '#000000',
                ),
            ),
        );

        $msg = urldecode(json_encode($template));

        return getHttps($api, $msg);
    }

    public function noticeMessage($openid, $message ="trans('weixin.new_message')",$user_name)
    {

        $time = date('Y-m-d H:i');
        //拆解用户
        $accessToken = $this->getAccessToken();
        if(!is_string($accessToken)){
            return $accessToken;
        }
        $api         = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$accessToken";

        $template = array(
            'touser'      => $openid,
            'template_id' => $this->tempId,
            'url'         => "",
            'topcolor'    => '#7B68EE',
            'data'        => array(
                'first'    => array(
                    'value' => '',
                    'color' => '#000000',
                ),
                'keyword1' => array(
                    'value' => $message,
                    'color' => '#0000FF',
                ),
                'keyword2' => array(
                    'value' => urlencode($user_name),
                    'color' => '#000000',
                ),
                'keyword3' => array(
                    'value' => urlencode($time),
                    'color' => '#000000',
                ),
                'remark'   => array(
                    'value' => '',
                    'color' => '#000000',
                ),
            ),
        );

        $msg = urldecode(json_encode($template));

        return getHttps($api, $msg);
    }

}
