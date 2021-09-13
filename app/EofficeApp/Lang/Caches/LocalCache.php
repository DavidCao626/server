<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/24
 * Time: 10:51
 */
namespace App\EofficeApp\Lang\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于模块授权缓存管理
 * Class LocalCache
 * @package App\EofficeApp\Lang\Caches
 */
class LocalCache extends LangCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'LOCAL';
    // 该缓存实体描述
    public $description = '用于缓存多语言';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [

        ],
        // 获取
        'get' => [
            '刷新新token',
        ],
        // 清除
        'clear' => [

        ]
    ];

    /**
     * 用于获取模块授权
     *
     * @param [string|int] $dynamicKey
     *
     * @return array
     */
    public function get($dynamicKey = null)
    {
        return $this->find($dynamicKey, function() {
            return 'zh-CN';
        });
    }
}
