<?php


namespace App\EofficeApp\Elastic\Services\ServiceManagementPlatform;

use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * 服务管理平台认证相关
 *
 * Class PlatformAuthService
 * @package App\EofficeApp\Elastic\Services\ServiceManagementPlatform
 */
class PlatformAuthService
{
    const KEY = '$D&T_3F@eo_Pj';    // 签名加密秘钥
    const VALID_TIME = 30;          // 签名有效时间30s

    /**
     * 验证请求
     *
     * @param HeaderBag $headerBag
     * @return bool
     *
     * @throws \Exception
     */
    public function validRequest(HeaderBag $headerBag): bool
    {
        $timestamp = $headerBag->get('X-Request-Time');
        $sign = $headerBag->get('X-Request-Signature');
        $platform = $headerBag->get('X-Request-Platform');

        // TODO 调试用 上线前删除
        if ($headerBag->get('X-Request-IsAdmin') == 'weoffice') {
            return true;
        }

        if ($timestamp && $sign && $platform) {
            $now = time();
            if ($now - $timestamp > self::VALID_TIME) {
                throw new \Exception('非法请求');
            }

            if ($this->authenticate($sign, $timestamp)) {
                return true;
            }
        }

       throw new \Exception('非法请求');
    }

    /**
     * 校验签名.
     *
     * @param $signature
     * @param $timestamp
     *
     * @return bool
     */
    public function authenticate($signature, $timestamp): bool
    {
        $sign = $this->sign($timestamp);

        return $sign === $signature;
    }

    /**
     * 生成签名.
     *
     * @param $timestamp
     * @param string $parameterStr
     *
     * @return string
     */
    public function sign($timestamp): string
    {
        $signature = hash_hmac('md5', $timestamp, self::KEY, false);

        return $signature;
    }
}