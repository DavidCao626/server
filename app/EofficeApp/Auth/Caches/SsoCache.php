<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/24
 * Time: 10:39
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于单点登录后的用户信息缓存管理
 * Class SsoCache
 * @package App\EofficeApp\Auth\Caches
 */
class SsoCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'SSO';
    // 该缓存实体描述
    public $description = '用于缓存单点登录后的用户信息';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '单点登录验证'
        ],
        // 获取
        'get' => [
            '获取单点登录后的用户信息'
        ],
        // 清除
        'clear' => [

        ]
    ];

    /**
     * 用于获取单点登录后的用户信息
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
