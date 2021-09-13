<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/4/28
 * Time: 10:22
 */
namespace App\EofficeApp\Attendance\Caches;
use App\EofficeCache\ECacheInterface;

/**
 * 用于考勤基本单位设置的缓存管理
 * Class AttendanceStatUnitConfigCache
 * @package App\EofficeApp\Attendance\Caches
 */
class AttendanceStatUnitConfigCache extends AttendanceCache implements ECacheInterface
{
    // 默认是string数据结构,当前缓存实体使用的是hash数据结构
    public $struct = 'string';
    // 统一使用大写字母加下划线（_）;
    public $key = 'ATTENDANCE_STAT_UNIT_CONFIG';
    // 该缓存实体描述
    public $description = '用于缓存考勤基本单位设置';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '本缓存实体类获取缓存时触发'
        ],
        // 获取
        'get' => [
            '获取考勤基本单位设置',
        ],
        // 清除
        'clear' => [
            '编辑公共设置'
        ]
    ];
    /**
     * 用于获取缓存考勤基本单位设置
     *
     * @param [string|int] $dynamicKey
     *
     * @return array
     */
    public function get($dynamicKey = null)
    {
        return $this->find($dynamicKey, function() {
            $params = app('App\EofficeApp\Attendance\Repositories\AttendanceSetBaseRepository')->getParamsByKeys(['stat_unit_day', 'day_precision', 'stat_unit_hours', 'hours_precision', 'stat_leave_as_real_attend']);
            $result = $params->mapWithKeys(function ($item) {
                return [$item->param_key => intval($item->param_value)];
            });
            return $result;
        });
    }
}
