<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/24
 * Time: 17:51
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于SmsToken缓存管理
 * Class SmsTokenCache
 * @package App\EofficeApp\Auth\Caches
 */
class SmsTokenCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'SMS_TOKEN';
    // 该缓存实体描述
    public $description = '用于缓存SmsToken';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '验证绑定的手机号',
        ],
        // 获取
        'get' => [
            '手机短信验证'
        ],
        // 清除
        'clear' => [
        ]
    ];

    /**
     * 用于获取SmsToken
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
