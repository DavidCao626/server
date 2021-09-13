<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/24
 * Time: 10:51
 */
namespace App\EofficeApp\Portal\Caches;
use App\EofficeCache\ECacheInterface;
use Illuminate\Support\Facades\Redis;

/**
 * 用于导航栏搜索分类缓存管理
 * Class GlobalSearchItemCache
 * @package App\EofficeApp\Empower\Caches
 */
class GlobalSearchItemCache extends PortalCache implements ECacheInterface
{
    // 默认是string数据结构,当前缓存实体使用的是hash数据结构
    public $struct = 'string';
    public $ttl = 3600; // 过期时间，未定义则永久存储
    // 统一使用大写字母加下划线（_）;
    public $key = 'GLOBAL_SEARCH_ITEM';
    // 该缓存实体描述
    public $description = '用于缓存导航栏搜索分类';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '获取全站搜索项目'
        ],
        // 获取
        'get' => [
            '获取全站搜索项目',
        ],
        // 清除
        'clear' => [
        ]
    ];

    /**
     * 用于获取导航栏搜索分类
     *
     * @param [string|int] $dynamicKey
     *
     * @return array
     */
    public function get($dynamicKey = null)
    {
        return $this->find($dynamicKey);
    }

    public function delAll()
    {
        $keys = Redis::keys($this->cacheKey.'*');
        if ($keys) {
            Redis::del($keys);
        }
    }
}
