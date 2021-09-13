<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/17
 * Time: 14:33
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于新token的缓存管理
 * Class NewTokenCache
 * @package App\EofficeApp\Auth\Caches
 */
class NewTokenCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'NEW_TOKEN';
    public $ttl = ''; // 过期时间，未定义则永久存储
    // 该缓存实体描述
    public $description = '用于缓存新token';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '刷新新token',
        ],
        // 获取
        'get' => [
            '刷新新token',
        ],
        // 清除
        'clear' => [

        ]
    ];
    public function __construct()
    {
        $tokenGracePeriod = config('auth.token_grace_period', 60);
        $this->ttl = $tokenGracePeriod;
    }
    /**
     * 用于获取缓存新token
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
