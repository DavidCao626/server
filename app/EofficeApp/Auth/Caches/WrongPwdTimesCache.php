<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/16
 * Time: 17:09
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;
use Illuminate\Support\Facades\Redis;

/**
 * 用于密码错误次数的缓存管理
 * Class WrongPwdTimesCache
 * @package App\EofficeApp\Auth\Caches
 */
class WrongPwdTimesCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'WRONG_PWD_TIMES';
    public $ttl = ''; // 过期时间，未定义则永久存储
    // 该缓存实体描述
    public $description = '用于缓存密码错误次数';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '本缓存实体类获取缓存时触发',
            '非系统账号防暴力破解提示',
            '验证账号密码',
        ],
        // 获取
        'get' => [
            '非系统账号防暴力破解提示',
            '验证账号密码',
        ],
        // 清除
        'clear' => [
            '验证账号密码',
        ]
    ];

    public function __construct()
    {
        $this->systemParams = $this->getSystemParam();
        $isAutoUnlock = $this->systemParams['auto_unlock_account'] ?? 0;
        if($isAutoUnlock){
            $unlockTime = $this->systemParams['auto_unlock_time'] ?? '1d';
            $time = (int) substr($unlockTime, 0, strlen($unlockTime)-1) ?? 1;
            $unit = substr($unlockTime, -1) ?? 'd';
            $minute = $unit == 'd' ? $time * 1440 : $time * 60;
            $this->ttl = $minute * 60;
        }
    }

    /**
     * 用于获取缓存密码错误次数
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

    /**
     * 获取系统参数
     * @return type
     */
    public function getSystemParam()
    {
        $params = [];
        foreach (get_system_param() as $key => $value) {
            $params[$value->param_key] = $value->param_value;
        }
        return $params;
    }
}
