<?php

namespace App\Utils;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Dingtalk\Repositories\DingtalkTicketRepository;
use App\EofficeApp\Dingtalk\Repositories\DingtalkTokenRepository;
use App\EofficeApp\Dingtalk\Repositories\DingtalkUserRepository;
use App\EofficeApp\User\Repositories\UserRepository;

class DingTalk
{

    public $corpid;
    public $secret;
    public $agentid = 0;
    public $access_token;
    public $token_time;
    public $create_time;
    public $is_push = 0;
    public $domain;
    public $userRepository;
    public $dingtalkTokenRepository;
    public $attachmentService;
    public $dingtalkTicketRepository;
    public $dingtalkUserRepository;
    public $app_secret;
    public $app_key;

    public function __construct(
        UserRepository $userRepository, DingtalkTokenRepository $dingtalkTokenRepository, AttachmentService $attachmentService, DingtalkTicketRepository $dingtalkTicketRepository, DingtalkUserRepository $dingtalkUserRepository
    ) {

        $this->attachmentService        = $attachmentService;
        $this->userRepository           = $userRepository;
        $this->dingtalkTokenRepository  = $dingtalkTokenRepository;
        $this->dingtalkTicketRepository = $dingtalkTicketRepository;
        $this->dingtalkUserRepository   = $dingtalkUserRepository;
        $this->getAccessToken();
    }

    public function getAccessToken()
    {

        $dingtalk = $this->dingtalkTokenRepository->getDingTalk([]);

        if (!isset($dingtalk->corpid)) {
            return false;
        }

        $this->corpid       = $dingtalk->corpid;
        $this->secret       = $dingtalk->secret;
        $this->access_token = $dingtalk->access_token;
        $this->domain       = $dingtalk->domain;
        $this->token_time   = $dingtalk->token_time;
        $this->is_push      = $dingtalk->is_push;
        $this->agentid      = $dingtalk->agentid;
        $this->app_secret   = isset($dingtalk->app_secret) ? $dingtalk->app_secret : '';
        $this->app_key      = isset($dingtalk->app_key) ? $dingtalk->app_key : '';
        $this->app_id       = isset($dingtalk->app_id) ? $dingtalk->app_id : '';
        if (abs(time() - $this->token_time) > 7200) {
            //GET???????????????
            if (empty($this->secret)) {
                $url = "https://oapi.dingtalk.com/gettoken?appkey={$this->app_key}&appsecret={$this->app_secret}";
            }else{
                $url = "https://oapi.dingtalk.com/gettoken?corpid={$this->corpid}&corpsecret={$this->secret}";
            }
            $dingtalkConnect = getHttps($url);
            $connectList     = json_decode($dingtalkConnect, true);
            if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
                $code = $connectList['errcode'];
                return ['code' => ["$code", 'dingtalk']];
            }
            $data['access_token'] = $connectList['access_token'];
            $data['token_time']   = time();

            $this->dingtalkTokenRepository->updateData($data, ['corpid' => $this->corpid]);
            $this->access_token = $data['access_token'];
            return $data['access_token'];
        }
        return $dingtalk->access_token;
    }

    //js-sdk ???????????? ????????????
    public function dingtalkSignpackage()
    {
        $code = $this->getAccessToken();
        if (!$code || isset($code["code"])) {
            return $code;
        }

        $domain = $this->domain;
        if (isset($this->app_id) && !empty($this->app_id)) {
            $agentId = $this->app_id;
        } else {
            $agentId = $this->agentid;
        }
        $corpId      = $this->corpid;
        $jsapiTicket = $this->getJsApiTicket();
        if (isset($jsapiTicket['code']) && $jsapiTicket['code'] != 0) {
//            jsapi???????????????
//            dd($jsapiTicket);
            return $jsapiTicket;
        }
//        $url = "http://bpoffice.weaver.cn/eoffice10/server/public/";
        //        $url = urldecode($url);
//        $url       = "http://wwfeoding.weaver.cn/eoffice10/client/mobile/home/profile/attendance";
        $url       = urldecode($domain . $_SERVER["REQUEST_URI"]);
        $timeStamp = time();
        $nonceStr  = $this->createNonceStr();

        // ?????????????????????????????? key ??? ASCII ???????????????
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timeStamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            'url'       => $url,
            'nonceStr'  => $nonceStr,
            'agentId'   => $agentId,
            'timeStamp' => $timeStamp,
            'corpId'    => $corpId,
            'signature' => $signature);
        return $signPackage;
    }

    public function dingtalkClientpackage($data)
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
//            $url = $domain . "/eoffice10/client/app/web/platform.html";//?????????????????????
//            $url = $domain . "/eoffice10/client/web/platform.html";
//            $url = $domain . "/eoffice10/client/web/ding-talk/";
            $url = $domain . "/eoffice10/client/web/";
        } else {
//            $url = $domain . "/eoffice10/client/app/mobile/";     //?????????
//            $url = $domain . "/eoffice10/client/mobile/home/profile/attendance";  //???????????????????????????
//            $url = $domain . "/eoffice10/client/mobile/";   //angular?????????????????? ???????????????????????????

            if(!empty($data['path'])){
                $url = $data['path'];   //angular??????????????????
                // $url       = "http://wwfeoding.weaver.cn/eoffice10/client/mobile/";   //angular??????????????????
            }else{
                $url = $domain . "/eoffice10/client/mobile/"; // ??????????????????mobile???????????????????????????????????????
                // ??????????????????mobile????????????????????????????????????????????????????????? ?????????????????????
            }
        }
        $timeStamp = time();
        $nonceStr  = $this->createNonceStr();

        // ?????????????????????????????? key ??? ASCII ???????????????
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

        $data = $this->dingtalkTicketRepository->getTickt([]);
        if (!$data) {
            $expire_time = 0;
        } else {
            $expire_time = $data->expire_time;
        }
        // if ($expire_time < time()) {
        $accessToken = $this->getAccessToken();
        // ??????????????????????????? URL ?????? ticket
        $url          = "https://oapi.dingtalk.com/get_jsapi_ticket?access_token=$accessToken";
        $dingtalkTemp = getHttps($url);
        $dingtalkData = json_decode($dingtalkTemp, true);
        if (isset($dingtalkData['errcode']) && $dingtalkData['errcode'] != 0) {
            $code = $dingtalkData['errcode'];
            return ['code' => ["$code", 'dingtalk']];
        }

        $ticket = $dingtalkData["ticket"];
        if ($ticket) {
            $dingtalk["jsapi_ticket"] = $ticket;
            $dingtalk["expire_time"]  = time() + 7000;
            $this->dingtalkTicketRepository->truncateDingtalkTicket();
            $this->dingtalkTicketRepository->insertData($dingtalk);
        }
        // } else {
        //     $ticket = $data->jsapi_ticket;
        // }

        return $ticket;
    }

    // js-sdk end
    //????????????
    public function geocodeAttendance($data)
    {
        $address = geocode_to_address($data);
        if (isset($address['code'])) {
            return $address;
        }
        if (!$address) {
            return ['code' => ["40059", 'qyweixin']]; //???????????????????????????????????????
        }
        return $address;
    }

    //????????????
    public function pushMessage($option, $msgType = 'oa')
    {
        if (!$this->is_push) {
            return false;
        }

        switch ($msgType) {
            case 'oa':
                if (count($option["toids"]) > 100) {
                    $userIds = array_chunk($option["toids"], 100);
                    foreach ($userIds as $user_id) {
                        $option['toids'] = $user_id;
                        $this->pushDingtalk($option);
                    }
                } else {
                    $this->pushDingtalk($option);
                }
                break;
            default:
                break;
        }
    }

    public function pushDingtalk($option)
    {
        $access_token = $this->getAccessToken();
//        ????????????????????????????????????????????????
        $api          = "https://oapi.dingtalk.com/message/send?access_token=$access_token";
//        $api          = "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=$access_token";

        $oaId = $option["toids"];

        //?????????
        $option["touser"] = $this->tranferDingTalkUser($option["toids"]);

        $option["agentid"] = $this->agentid;
        unset($option["toids"]);

        $msg = $this->getTemplateToPush($option);
        return getHttps($api, $msg);
    }

    protected function getTemplateToPush($option)
    {
//        ?????????????????????????????????

//        ???????????????menu??????
        $arr_text = ['dingtalk-complete','attendancemachine-complete'];
        if (in_array($option['menu'],$arr_text)) {
//            ???????????????????????????
            $option["url"] = '';
            $option["pc_url"] = '';
        }
//        ????????????
//        $arr_temp = array(
//            "msgtype"    => "text",
//            "text" => array(
//                "content" => $option['content']
//            )
//        );
//        $template = array(
//            'userid_list'  => $option["touser"], //??????
//            'agentid' => $option["agentid"], //??????agentId
//            "msg"      => json_encode($arr_temp)
//        );


//      ????????????
        $template = array(
            'touser'  => $option["touser"], //?????????
            'toparty' => "", //?????????
            'agentid' => $option["agentid"], //??????,
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
//                    "content" => rand(10,99).$option['content'].rand(10,99),
//                    "content" => "????????????????????????",
                ),
            ),
        );
//      ????????????
        return urldecode(json_encode($template));
    }

    // ?????????????????????????????????????????????
    public function dingtalkUserList()
    {
        $access_token = $this->getAccessToken();
        // ??????????????????
        $api          = "https://oapi.dingtalk.com/department/list?access_token=$access_token";
        $depts        = getHttps($api);
        $dingtalkData = json_decode($depts, true);
        if (isset($dingtalkData['errcode']) && $dingtalkData['errcode'] != 0) {
            $code = $dingtalkData['errcode'];
            return ['code' => ["$code", 'dingtalk']];
        }

        $deptList     = $dingtalkData["department"];
        $userInfoList = [];
        foreach ($deptList as $dept) {
            $name                = $dept["name"];
            $userInfoList[$name] = $userInfo = $this->getDingtalkUserListByDeptId($dept["id"]);
        }
        return $userInfoList;
    }

    public function tranferDingTalkUser($users)
    {
        if (!is_array($users)) {
            $users = explode(",", $users);
        }
        $str = "";
        foreach ($users as $useId) {
            $row = $this->dingtalkUserRepository->getDingTalkUserIdById($useId);
            if ($row && $row["userid"]) {
                $str .= $row["userid"] . "|";
            } else {
                $str .= $useId . "|";
            }
        }

        return trim($str, "|");
    }
    // ????????????id???????????????????????????????????????
    public function getDingtalkUserListByDeptId($deptId)
    {

        $access_token = $this->getAccessToken();

        $api          = "https://oapi.dingtalk.com/user/simplelist?access_token=$access_token&department_id=$deptId";
        $users        = getHttps($api);
        $dingtalkData = json_decode($users, true);
        if (isset($dingtalkData['errcode']) && $dingtalkData['errcode'] != 0) {
            $code = $dingtalkData['errcode'];
            return ['code' => ["$code", 'dingtalk']];
        }

        return $dingtalkData["userlist"];
    }

    public function createPath()
    {
        $attachmentId = "";
        $newPath      = $this->attachmentService->createCustomDir($attachmentId);
        return $newPath;
    }

    //??????????????????
    public function uploadTempFile($file, $type)
    {

        //??????getHttps????????????????????????????????? ???
        $access_token = $this->getAccessToken();
        $fields       = array('media' => new \CURLFile($file));
        $url          = "https://oapi.dingtalk.com/media/upload?access_token=access_token=$access_token&type=$type";

        try {
            if (function_exists('curl_init')) {

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSLVERSION, 1);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);

                if ($output === false) {
                    $res = array(
                        "errcode" => "0x033003",
                        "errmsg"  => curl_error($curl),
                    );
                    $output = json_encode($res);
                }

                curl_close($ch);
            } else {
                $res = array(
                    "errcode" => "0x033003",
                    "errmsg"  => trans('dingtalk.extension_unopened'),
                );
                $output = json_encode($res);
            }
        } catch (Exception $exc) {

            $res = array(
                "errcode" => "0x033003",
                "errmsg"  => $exc->getTraceAsString(),
            );

            $output = json_encode($res);
        }
        return $output;
    }

    // ????????????????????????
    public function dingtalkNearby($data)
    {
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

    // ??????????????????????????????????????????
    public function dingtalkMove($data)
    {

        if (is_array($data["imgs"])) {
            $imgs = $data["imgs"];
        } else {
            $imgs = explode(",", trim($data["imgs"], ","));
        }

        $fileName             = [];
        $fileIds              = [];
        $thumbWidth           = config('eoffice.thumbWidth', 100);
        $thumbHight           = config('eoffice.thumbHight', 40);
        $thumbPrefix          = config('eoffice.thumbPrefix', "thumb_");
        $attachmentFile       = 1; //????????????
        $attachment_base_path = getAttachmentDir();

        $temp = [];
        foreach ($imgs as $mediaId) {
            $pathinfo = pathinfo($mediaId);
            $imgNmae  = $pathinfo["basename"];
            $imgExt   = $pathinfo["extension"];

            ob_start(); //????????????
            readfile($mediaId); //??????????????????
            $img = ob_get_contents(); //?????????????????????
            ob_end_clean(); //?????????????????????
            //????????????ID
            $attachmentId = md5(time() . $imgNmae . rand(1000000, 9999999));
            $newPath      = $this->attachmentService->createCustomDir($attachmentId);
            $mediaIdName  = $imgNmae . "." . $imgExt;
            $originName   = $newPath . $mediaIdName;
            $size         = strlen($img); //??????????????????
            $fp2          = @fopen($originName, "a");
            fwrite($fp2, $img); //???????????????????????????????????????????????????
            fclose($fp2);

            $thumbAttachmentName = scaleImage($originName, $thumbWidth, $thumbHight, $thumbPrefix);
            //       ???????????? ???????????????
            $attachment_path = str_replace($attachment_base_path, '', $newPath);
            // $tableData = [
            //     "attachment_id" => $attachmentId,
            //     "attachment_name" => $mediaIdName,
            //     "affect_attachment_name" => $mediaIdName,
            //     "thumb_attachment_name" => $thumbAttachmentName,
            //     "attachment_size" => $size,
            //     "attachment_type" => "jpg",
            //     "attachment_create_user" => "",
            //     "attachment_base_path" => $attachment_base_path,
            //     "attachment_path" => $attachment_path,
            //     "attachment_file" => 1,
            //     "attachment_time" => date("Y-m-d H:i:s", time())
            // ];
            // $this->attachmentService->addAttachment($tableData);
            $newFullFileName = $newPath . DIRECTORY_SEPARATOR . $mediaIdName;

            $attachmentInfo = [
                "attachment_id"          => $attachmentId,
                "attachment_name"        => $mediaIdName,
                "affect_attachment_name" => $mediaIdName,
                'new_full_file_name'     => $newFullFileName,
                "thumb_attachment_name"  => $thumbAttachmentName,
                "attachment_size"        => $size,
                "attachment_type"        => 'jpg',
                "attachment_create_user" => '',
                "attachment_base_path"   => $attachment_base_path,
                "attachment_path"        => $attachment_path,
                "attachment_mark"        => 1,
                "relation_table"         => '',
                "rel_table_code"         => "",
            ];
            // return $this->handleAttachmentDataTerminal($attachmentInfo);
            $this->attachmentService->handleAttachmentDataTerminal($attachmentInfo);
            //??????64code
            $path   = $attachment_base_path . $attachment_path . DIRECTORY_SEPARATOR . $thumbAttachmentName;
            $temp[] = [
                "attachmentId"    => $attachmentId,
                "attachmentName"  => $mediaIdName,
                "attachmentThumb" => imageToBase64($path),
                // ???????????????????????????????????????attachmentType??????
                "attachmentSize"        => $size,
                "attachmentType"        => 'jpg',
                "attachmentMark"        => 1,
            ];
        }

        return $temp;
    }

    // ??????????????????????????????
    /**
     * @param fetchChild ????????????????????????
     * @param id ??????id
     */
    public function getDingtalkDepartmentList($fetchChild = 'false',$id = 1){
        $code = $this->getAccessToken();
        // $id = ''; ??????????????????
        $url = "https://oapi.dingtalk.com/department/list?access_token=$code&fetch_child=".$fetchChild."&id=$id";
        // dd($url);
        $res = getHttps($url);
        $res = json_decode($res,true);
        if($res['errcode'] != 0){
            return ['code' => [$res['errcode'], 'dingtalk']];
        }
        return $res['department'];
    }
    // ??????????????????????????????
    /**
     * @param defaultRole ???????????????????????????????????????
     */
    public function getRoleList($defaultRole = false){
        $code = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/topapi/role/list?access_token=$code";
        // dd($url);
        $res = getHttps($url);
        $res = json_decode($res,true);
        if($res['errcode'] != 0){
            return ['code' => [$res['errcode'], 'dingtalk']];
        }
        // ????????????????????????????????????????????????
        if($defaultRole === false){
            // ???????????????$res['result']['list']????????????$res['result']['list'][0]??????????????????
            unset($res['result']['list'][0]);
        }
        $rolesList = [];
        foreach($res['result']['list'] as $roles){
            if(!empty($roles) && !empty($roles['roles'])){
                foreach($roles['roles'] as $role){
                    $rolesList[] = $role;
                }
            }
        }
        return $rolesList;
    }

    // ????????????????????????????????????
    /**
     * @param departmentId ??????id
     * @param offset ???????????????
     * @param size ???????????????
     */
    public function getDingtalkDepartmentUserList($departmentId,$offset=0,$size=100){
        $code = $this->getAccessToken();
        // ??????????????????????????????????????????,size??????100
        $url = "https://oapi.dingtalk.com/user/listbypage?access_token=$code&department_id=$departmentId&offset=$offset&size=$size";
        $res = getHttps($url);
        $res = json_decode($res,true);
        if($res['errcode'] != 0){
            return ['code' => [$res['errcode'], 'dingtalk']];
        }
        return $res['userlist'];
        // ?????????????????????????????????????????????????????????????????????????????????
    }
    // ????????????????????????????????????
    /**
     * @param roleId ??????id
     */
    public function getDingtalkRoleUserList($roleId){
        $code = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/topapi/role/simplelist?access_token=$code";
        $param = [
            'role_id' => $roleId,
            'size' => 200,
            'offset' => 0
        ];
        $res = getHttps($url,json_encode($param));
        $res = json_decode($res,true);
        $userList[] = $res['result']['list'];
        while(isset($res['result']['hasMore']) && $res['result']['hasMore'] == true){
            $param['offset'] += 200;
            $res = getHttps($url,json_encode($param));
            $res = json_decode($res,true);
            $userList[] = $res['result']['list'];
        }
        return $userList;
        // ???????????????????????????????????????
    }

    /**
     * ????????????????????????
     */
    public function registerCallback(){
        $code = $this->getAccessToken();
        $aes_key="123456789012345678901234567890aq";
        $aes_key_encode=base64_encode($aes_key);
        $aes_key_encode=substr($aes_key_encode,0,-1);//??????= ???
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
            'aes_key'       => $aes_key_encode,
            'url'           => 'http://wwfeoding.weaver.cn/eoffice10_dev/server/public/api/dingtalk/dingtalkReceive',
        ];
        $url  = 'https://oapi.dingtalk.com/call_back/register_call_back?access_token=' . $code;
        $json = getHttps($url, json_encode($param));
        return json_decode($json,true);
    }

    // ????????????????????????
    function getCallbackList(){
        $code = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/call_back/get_call_back?access_token=$code";
        $res = getHttps($url);
        return json_decode($res,true);
    }
}
