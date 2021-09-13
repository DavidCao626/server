<?php
namespace App\EofficeApp\Attendance\Caches;
use App\EofficeCache\ECacheInterface;
/**
 * 用于通用权限组的缓存管理
 *
 * @author 90536
 */
class CommonPurviewGroupCache extends AttendanceCache implements ECacheInterface
{
    // 默认是string数据结构,当前缓存实体使用的是hash数据结构
    public $struct = 'string'; 
    // 统一使用大写字母加下划线（_）;
    public $key = 'COMMON_PURVIEW_GROUP'; 
    // 该缓存实体描述
    public $description = '用于缓存通用权限组'; 
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '本缓存实体类获取缓存时触发'
        ],
        // 获取
        'get' => [
            '获取外勤签到成员',
            '获取外勤签退成员',
            '获取允许漏签成员',
            '获取无需打卡提醒成员',
            '获取允许自动签退成员',
        ],
        // 清除
        'clear' => [
            '设置外勤签到成员',
            '设置外勤签退',
            '设置允许漏签成员',
            '设置无需打卡提醒成员',
            '设置允许自动签退成员时触发'
        ]
    ];
    /**
     * 用于获取缓存通用权限组
     * 
     * @param [string|int] $dynamicKey
     * 
     * @return array
     */
    public function get($dynamicKey = null) 
    {
        //获取考勤通用权限组（1外勤签到成员，2外勤签退成员，3允许漏签成员，4无需打卡提醒成员，5允许自动签退成员）
        return $this->find($dynamicKey, function($dynamicKey = null) {
                    $group = app('App\EofficeApp\Attendance\Repositories\AttendanceCommonPurviewGroupRepository')->getOnePurviewGroup($dynamicKey);
                    if ($group) {
                        return [
                            'all_member' => $group->all_member,
                            'dept_id' => explode(',', $group->dept_id),
                            'user_id' => explode(',', $group->user_id),
                            'role_id' => explode(',', $group->role_id)
                        ];
                    }
                    return [
                        'all_member' => 0,
                        'dept_id' => '',
                        'user_id' => '',
                        'role_id' => ''
                    ];
                });
    }
}
