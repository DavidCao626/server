<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/4/28
 * Time: 11:53
 */
namespace App\EofficeApp\Attendance\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于班次签到签退信息的缓存管理
 * Class AttendanceStatUnitConfigCache
 * @package App\EofficeApp\Attendance\Caches
 */
class AttendShiftTimesCache extends AttendanceCache implements ECacheInterface
{
    // 默认是string数据结构,当前缓存实体使用的是hash数据结构
    public $struct = 'string';
    // 统一使用大写字母加下划线（_）;
    public $key = 'ATTEND_SHIFT_TIMES';
    // 该缓存实体描述
    public $description = '用于缓存班次签到签退信息';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '本缓存实体类获取缓存时触发'
        ],
        // 获取
        'get' => [
            '获取班次签到签退信息',
        ],
        // 清除
        'clear' => [

        ]
    ];
    /**
     * 用于获取缓存班次签到签退信息
     *
     * @param [string|int] $dynamicKey
     *
     * @return array
     */
    public function get($dynamicKey = null)
    {
        return $this->find($dynamicKey, function($dynamicKey = null) {
            return app('App\EofficeApp\Attendance\Repositories\AttendanceShiftsSignTimeRepository')->getSignTime($dynamicKey, ['sign_in_time', 'sign_out_time']);
        });
    }
}
