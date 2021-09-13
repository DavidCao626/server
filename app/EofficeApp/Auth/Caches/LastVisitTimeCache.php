<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/24
 * Time: 14:18
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于最后一次访问时间的缓存管理
 * Class LastVisitTimeCache
 * @package App\EofficeApp\Auth\Caches
 */
class LastVisitTimeCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'LAST_VISIT_TIME';
    // 该缓存实体描述
    public $description = '用于缓存最后一次访问时间';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '客户端刷新token',
        ],
        // 获取
        'get' => [

        ],
        // 清除
        'clear' => [

        ]
    ];

    /**
     * 用于获取缓存最后一次访问时间
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
