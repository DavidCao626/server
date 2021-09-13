<?php
if (!empty($_GET)) {
    // 引入加解密库

    require_once("./openapi-demo-php-master/corp/crypto/DingtalkCrypt.php");
    // file_put_contents('./responce.txt', json_encode($_POST));
    // {"signature":"a13bf5c26d9e6ee373266d8c35861436e9c8482f","timestamp":"1570612488544","nonce":"VugEH8NA"}
    // 获取返回的数据
    $signature = $_GET['signature'];
    $timestamp = $_GET['timestamp'];
    $nonce = $_GET['nonce'];
    $postdata = file_get_contents("php://input");
    $postList = json_decode($postdata,true);
    $encrypt = $postList['encrypt'];
    // define("TOKEN","123456");
    // define("SUITE_KEY","ding7496815d5e5a91e4");
    // define("ENCODING_AES_KEY",base64_encode("1234567890123456789012345678901234567890123"));


    $aes_key="123456789012345678901234567890aq";
    $aes_key_encode=base64_encode($aes_key);
    $aes_key_encode=substr($aes_key_encode,0,-1);//去掉= 号
    // $str= 'success';
    // $token="123456";
    // $corpId = "ding7496815d5e5a91e4";
    // $suiteKey=$corpId;
    // $msg=$str;
    // $timeStamp=$timestamp;
    // $encryptMsg = "";


    // $crypt = new DingtalkCrypt($token, $aes_key_encode, $suiteKey); //token是注册接口时的随机字符串 regist.php与index.php 这里都使用'123456'，两个文件的token要一样
    // $res = $crypt->EncryptMsg($msg, $timeStamp, $nonce, $encryptMsg);
    // file_put_contents('./responce.txt', $res);
    // //var_dump($res);//返回的字符串已经base64encode过了
    // echo $res;

    // 另一种方案

    $timeStamp = $timestamp;
    $callBackToken = "123456";
    $callBackAseKey = $aes_key_encode;
    $callBackCorpid = "ding7496815d5e5a91e4";
    $crypt = new DingtalkCrypt($callBackToken, $callBackAseKey, $callBackCorpid);

    $msg = "";
    $errCode = $crypt->DecryptMsg($signature, $timeStamp, $nonce, $encrypt, $msg);
    $eventMsg = json_decode($msg);
    if(!empty($eventMsg)){
    $eventType = $eventMsg->EventType;
    file_put_contents('./goten.txt', json_encode($eventType)."\r\n\r\n",FILE_APPEND);exit;
    if ($errCode == 0 && $eventType != 'check_url') {
        switch ($eventType) {
            case 'user_add_org':   //通讯录用户增加
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'user_modify_org':   //通讯录用户更改
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'user_leave_org':   //通讯录用户离职
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'org_dept_create':   //通讯录企业部门创建
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'org_dept_modify':   //通讯录企业部门修改
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'org_dept_remove':   //通讯录企业部门删除
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'label_user_change':   //员工角色信息发生变更
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'label_conf_add':   //增加角色或者角色组
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'label_conf_del':   //删除角色或者角色组
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'label_conf_modify':   //修改角色或者角色组
            file_put_contents('./parseData.txt', json_encode($eventMsg)."\r\n\r\n",FILE_APPEND);
                break;
            case 'check_in':   //通讯录用户增加
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
    // file_put_contents('./1.txt', "1.".$res.'\r\n'."2.".$timeStamp.'\r\n'."3.".$nonce.'\r\n'."4.".$encryptMsg);
    $errCode = $crypt->EncryptMsg($res, $timeStamp, $nonce, $encryptMsg);
    file_put_contents('./1.txt',"\r\n\r\n".date("Y-m-d H:i:s",time()).$errCode,FILE_APPEND);
    if ($errCode == 0) {
        echo $encryptMsg;
        //Log::info("RESPONSE: " . $encryptMsg);
    } else {
        //Log::info("RESPONSE ERR: " . $errCode);
    }
    exit;



















    // $crypt = new DingtalkCrypt(TOKEN, ENCODING_AES_KEY, SUITE_KEY);
    // $msg = "";
    // $errCode = $crypt->DecryptMsg($signature, $timestamp, $nonce, $encrypt, $msg);
    // if($errCode != 0){
    //     file_put_contents('./responce.txt',json_encode($_GET) . "  ERR:" . $errCode,FILE_APPEND);
    //     /**
    //      * 创建套件时检测回调地址有效性，使用CREATE_SUITE_KEY作为SuiteKey
    //      */
    //     $crypt = new DingtalkCrypt(TOKEN, ENCODING_AES_KEY, CREATE_SUITE_KEY);
    //     $errCode = $crypt->DecryptMsg($signature, $timeStamp, $nonce, $encrypt, $msg);
    //     if ($errCode == 0)
    //     {
    //         file_put_contents('./responce.txt',"DECRYPT CREATE SUITE MSG SUCCESS " . json_encode($_GET) . "  " . $msg,FILE_APPEND);
    //         $eventMsg = json_decode($msg);
    //         $eventType = $eventMsg->EventType;
    //         if ("check_create_suite_url" === $eventType)
    //         {
    //             $random = $eventMsg->Random;
    //             $testSuiteKey = $eventMsg->TestSuiteKey;
                
    //             $encryptMsg = "";
    //             $errCode = $crypt->EncryptMsg($random, $timeStamp, $nonce, $encryptMsg);
    //             if ($errCode == 0) 
    //             {
    //                 file_put_contents('./responce.txt',"CREATE SUITE URL RESPONSE: " . $encryptMsg,FILE_APPEND);
    //                 echo $encryptMsg;
    //             } 
    //             else 
    //             {
    //                 file_put_contents('./responce.txt',"CREATE SUITE URL RESPONSE ERR: " . $errCode,FILE_APPEND);
    //             }
    //         }
    //     }
    //     else 
    //     {
    //         file_put_contents('./responce.txt',json_encode($_GET) . "CREATE SUITE ERR:" . $errCode,FILE_APPEND);
    //     }
    //     return;
    // }else {
    //     /**
    //      * 套件创建成功后的回调推送
    //      */
    //     file_put_contents('./responce.txt',"DECRYPT MSG SUCCESS " . json_encode($_GET) . "  " . $msg,FILE_APPEND);
    //     $eventMsg = json_decode($msg);
    //     $eventType = $eventMsg->EventType;
    //     /**
    //      * 套件ticket
    //      */
    //     if ("suite_ticket" === $eventType)
    //     {
    //         Cache::setSuiteTicket($eventMsg->SuiteTicket);
    //     }
    //     /**
    //      * 临时授权码
    //      */
    //     else if ("tmp_auth_code" === $eventType)
    //     {
    //         $tmpAuthCode = $eventMsg->AuthCode;
    //         Activate::autoActivateSuite($tmpAuthCode);
    //     }
    //     /**
    //      * 授权变更事件
    //      */
    //     user_add_org : 通讯录用户增加
    //     user_modify_org : 通讯录用户更改
    //     user_leave_org : 通讯录用户离职
    //     org_admin_add ：通讯录用户被设为管理员
    //     org_admin_remove ：通讯录用户被取消设置管理员
    //     org_dept_create ： 通讯录企业部门创建
    //     org_dept_modify ： 通讯录企业部门修改
    //     org_dept_remove ： 通讯录企业部门删除
    //     org_remove ： 企业被解散
        
    //     else if ("user_add_org" === $eventType)
    //     {
    //         file_put_contents('./responce.txt',json_encode($_GET) . "  ERR:user_add_org",FILE_APPEND);
    //         //handle auth change event
    //     }
    //     else if ("user_modify_org" === $eventType)
    //     {
    //         file_put_contents('./responce.txt',json_encode($_GET) . "  ERR:user_modify_org",FILE_APPEND);
    //         //handle auth change event
    //     }
    //     else if ("user_leave_org" === $eventType)
    //     {
    //         file_put_contents('./responce.txt',json_encode($_GET) . "  ERR:user_leave_org",FILE_APPEND);
    //         //handle auth change event
    //     }
    //     /**
    //      * 应用被解除授权的时候，需要删除相应企业的存储信息
    //      */
    //     else if ("suite_relieve" === $eventType)
    //     {
    //         $corpid = $eventMsg->AuthCorpId;
    //         ISVService::removeCorpInfo($corpid);
    //         //handle auth change event
    //     }else if ("change_auth" === $eventType)
    //      {
    //          //handle auth change event
    //      }
    //     /**
    //      * 回调地址更新
    //      */
    //     else if ("check_update_suite_url" === $eventType)
    //     {
    //         $random = $eventMsg->Random;
    //         $testSuiteKey = $eventMsg->TestSuiteKey;
            
    //         $encryptMsg = "";
    //         $errCode = $crypt->EncryptMsg($random, $timeStamp, $nonce, $encryptMsg);
    //         if ($errCode == 0) 
    //         {
    //             file_put_contents('./responce.txt',"UPDATE SUITE URL RESPONSE: " . $encryptMsg,FILE_APPEND);
    //             echo $encryptMsg;
    //             return;
    //         } 
    //         else 
    //         {
    //             file_put_contents('./responce.txt',"UPDATE SUITE URL RESPONSE ERR: " . $errCode,FILE_APPEND);
    //         }
    //     }
    //     else
    //     {
    //         //should never happen
    //     }
        
    //     $res = "success";
    //     $encryptMsg = "";
    //     $errCode = $crypt->EncryptMsg($res, $timeStamp, $nonce, $encryptMsg);
    //     if ($errCode == 0) 
    //     {
    //         echo $encryptMsg;
    //         file_put_contents('./responce.txt',"RESPONSE: " . $encryptMsg,FILE_APPEND);
    //     } 
    //     else 
    //     {
    //         file_put_contents('./responce.txt',"RESPONSE ERR: " . $errCode,FILE_APPEND);
    //     }
    // }


















//     "msg_signature":"111108bb8e6dbce3c9671d6fdb69d15066227608",
    //   "timeStamp":"1783610513",
    //   "nonce":"123456",
    //   "encrypt":"1ojQf0NSvw2WPvW7LijxS8UvISr8pdDP+rXpPbcLGOmIBNbWetRg7IP0vdhVgkVwSoZBJeQwY2zhROsJq/HJ+q6tp1qhl9L1+ccC9ZjKs1wV5bmA9NoAWQiZ+7MpzQVq+j74rJQljdVyBdI/dGOvsnBSCxCVW0ISWX0vn9lYTuuHSoaxwCGylH9xRhYHL9bRDskBc7bO0FseHQQasdfghjkl"
    // $respdata = [
    //     'msg_signature' => $signature,
    //     'timeStamp'     => $timestamp,
    //     'nonce'         => $nonce,
    //     'encrypt'       => '1ojQf0NSvw2WPvW7LijxS8UvISr8pdDP+rXpPbcLGOmIBNbWetRg7IP0vdhVgkVwSoZBJeQwY2zhROsJq/HJ+q6tp1qhl9L1+ccC9ZjKs1wV5bmA9NoAWQiZ+7MpzQVq+j74rJQljdVyBdI/dGOvsnBSCxCVW0ISWX0vn9lYTuuHSoaxwCGylH9xRhYHL9bRDskBc7bO0FseHQQasdfghjkl',
    // ];
    // // echo json_encode(json_encode($respdata));
    // die;
}