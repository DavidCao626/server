<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/29
 * Time: 14:12
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于图形码验证的缓存管理
 * Class WrongPwdTimesCache
 * @package App\EofficeApp\Auth\Caches
 */
class CaptchaKeyCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'CAPTCHA_KEY';
    public $ttl = 60; // 过期时间，未定义则永久存储
    // 该缓存实体描述
    public $description = '用于缓存图形码验证';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
        ],
        // 获取
        'get' => [
            '图形码验证',
        ],
        // 清除
        'clear' => [
        ]
    ];

    /**
     * 用于获取图形码验证
     *
     * @param [string|int] $dynamicKey
     *
     * @return array
     */
    public function get($dynamicKey = null)
    {
        return $this->find($dynamicKey);
    }
}
