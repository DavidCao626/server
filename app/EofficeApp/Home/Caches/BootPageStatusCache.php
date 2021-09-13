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
class BootPageStatusCache extends HomeCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'BOOT_PAGE_STATUS';
    // 该缓存实体描述
    public $description = '用于启动页状态缓存管理';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '启动页使用完成后设置',
        ],
        // 获取
        'get' => [
            '系统初始化时获取'
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
        return $this->find(function() {
                    return get_system_param('boot_page_status', 0);
                });
    }
}
