<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/24
 * Time: 10:51
 */
namespace App\EofficeApp\Empower\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于模块授权缓存管理
 * Class EmpowerModuleInfoCache
 * @package App\EofficeApp\Empower\Caches
 */
class EmpowerModuleInfoCache extends EmpowerCache implements ECacheInterface
{
    // 默认是string数据结构,当前缓存实体使用的是hash数据结构
    public $struct = 'string';
    // 统一使用大写字母加下划线（_）;
    public $key = 'EMPOWER_MODULE_INFO';
    // 该缓存实体描述
    public $description = '用于缓存模块授权';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [

        ],
        // 获取
        'get' => [
            '注册系统初始信息',
            '刷新用户初始化信息',
            '获取有权限的系统模块',
        ],
        // 清除
        'clear' => [
            '其他验证方式',
            '导入授权',
            '添加模块授权信息',
            '清除模块授权缓存',
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
            $data = app('App\EofficeApp\Empower\Services\EmpowerService')->getModuleEmpower();
            if (isset($data['code'])) {
                return $data;
            }
            $modules = [];
            foreach ($data as $v) {
                if (in_array($v['empower'], ['trial', 'in_trial', 'is_empower'])) {
                    $modules[] = $v['menu_id'];
                }
            }
            return $modules;
        });
    }
}
