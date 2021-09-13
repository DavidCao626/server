<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/25
 * Time: 14:23
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于SmsToken生成时间缓存管理
 * Class SmsTokenGenerateTimeCache
 * @package App\EofficeApp\Auth\Caches
 */
class SmsTokenGenerateTimeCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'SMS_TOKEN_GENERATE_TIME';
    // 该缓存实体描述
    public $description = '用于缓存SmsToken生成时间';
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
     * 用于获取SmsToken生成时间
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
