<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/4/28
 * Time: 16:49
 */

namespace App\EofficeApp\Attendance\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于考勤基础参数的缓存管理
 * Class AttendanceStatUnitConfigCache
 * @package App\EofficeApp\Attendance\Caches
 */
class AttendSetBaseCache extends AttendanceCache implements ECacheInterface
{
    // 默认是string数据结构,当前缓存实体使用的是hash数据结构
    public $struct = 'string';
    // 统一使用大写字母加下划线（_）;
    public $key = 'ATTEND_SET_BASE';
    // 该缓存实体描述
    public $description = '用于缓存考勤基础参数';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '设置考勤基础参数',
            '获取考勤基础参数'
        ],
        // 获取
        'get' => [
            '获取考勤基础参数',
        ],
        // 清除
        'clear' => [

        ]
    ];
    /**
     * 用于获取缓存考勤基础参数
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
