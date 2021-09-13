<?php
namespace App\EofficeApp\Home\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于启动页状态缓存管理
 * 
 * Class BootPageStatusCache
 * 
 * @package App\EofficeApp\Home\Caches
 */
class SceneSeederProgressCache extends HomeCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'SCENE_SEEDER_PROGRESS';
    // 该缓存实体描述
    public $description = '用户案例场景迁移进度缓存管理';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '设置迁移进度',
        ],
        // 获取
        'get' => [
            '获取迁移进度'
        ],
        // 清除
        'clear' => [
        ]
    ];

    /**
     * 用于获取系统启动页状态
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
