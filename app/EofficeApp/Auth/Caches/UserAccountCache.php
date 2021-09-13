<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/24
 * Time: 17:24
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于动态验证码缓存管理
 * Class PhoneNumberCache
 * @package App\EofficeApp\Auth\Caches
 */
class UserAccountCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'USER_ACCOUNT';
    public $ttl = 60; // 过期时间，未定义则永久存储
    // 该缓存实体描述
    public $description = '用于缓存账号和验证码绑定';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '验证绑定的账号',
        ],
        // 获取
        'get' => [
            '验证绑定的账号',
        ],
        // 清除
        'clear' => [
        ]
    ];

    /**
     * 用于获取动态验证码
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
