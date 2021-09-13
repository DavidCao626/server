<?php
namespace SsoDecrypt;
/**
 *单点登录密码解密类
 */
class Decrypt
{
    public function laiwuDecrypt($data) {
        $privateKey = "9584756325487154";
        $iv 		= "6854715236584578";
        $encryptedData = base64_decode(base64_encode($data['p']));
        $data['p'] 	   = trim(openssl_decrypt($data['p'], 'aes-128-cbc', $privateKey, OPENSSL_ZERO_PADDING, $iv));
        return $data;
    }
}
