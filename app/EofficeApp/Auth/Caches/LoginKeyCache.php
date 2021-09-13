<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/25
 * Time: 14:33
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于LoginKey的缓存管理
 * Class LoginKeyCache
 * @package App\EofficeApp\Auth\Caches
 */
class LoginKeyCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'LOGIN_KEY';
    public $ttl = ''; // 过期时间，未定义则永久存储
    // 该缓存实体描述
    public $description = '用于缓存LoginKey';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [

        ],
        // 获取
        'get' => [
            '扫码登录',
        ],
        // 清除
        'clear' => [
            '扫码登录',
        ]
    ];

    /**
     * 用于获取缓存新LoginKey
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
