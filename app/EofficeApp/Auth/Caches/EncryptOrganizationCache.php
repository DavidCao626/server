<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/29
 * Time: 10:19
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于access_token 用户是否加密组织架构缓存管理
 * Class AccessTokenGenerateTimeCache
 * @package App\EofficeApp\Auth\Caches
 */
class EncryptOrganizationCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'ENCRYPT_ORGANIZATION';
    // 该缓存实体描述
    public $description = '用于缓存用户是否加密组织架构';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '当前用户是否加密组织架构'
        ],
        // 获取
        'get' => [
        ],
        // 清除
        'clear' => [
        ]
    ];

    /**
     * 用于获取用户是否加密组织架构
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
