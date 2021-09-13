<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2021/3/17
 * Time: 16:58
 */
namespace App\EofficeApp\Auth\Caches;
use App\EofficeCache\ECacheInterface;
use Illuminate\Support\Facades\Redis;

/**
 * 用于refresh_token 生成时间缓存管理
 * Class RefreshTokenGenerateTimeCache
 * @package App\EofficeApp\Auth\Caches
 */
class RefreshTokenGenerateTimeCache extends AuthCache implements ECacheInterface
{
    // 统一使用大写字母加下划线（_）;
    public $key = 'REFRESH_TOKEN_GENERATE_TIME';
    public $ttl = ''; // 过期时间，未定义则永久存储
    // 该缓存实体描述
    public $description = '用于缓存refresh_token生成时间';
    // 缓存实体被调用的时机
    public $called = [
        // 设置
        'set' => [
            '刷新新token',
        ],
        // 获取
        'get' => [
            '刷新新token',
        ],
        // 清除
        'clear' => [
            '系统注销',
            '保存用户token关系对应',
        ]
    ];
    public function __construct()
    {
        if($this->isMobile()) {
            $tokenTtl = config('auth.mobile_refresh_token_ttl') * 60;
        } else {
            $tokenTtl = config('auth.web_refresh_token_ttl') * 60;
        }
        $this->ttl = $tokenTtl;
    }
    /**
     * 用于获取refresh_token生成时间
     *
     * @param [string|int] $dynamicKey
     *
     * @return array
     */
    public function get($dynamicKey = null)
    {
        return $this->find($dynamicKey);
    }

    /**
     * 判断是否是手机端登录
     * @return boolean
     *
     */
    public function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信,DingTalk钉钉客户端

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile', 'DingTalk','EofficeApp', 'Eoffice','okhttp');
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

    public function delAll()
    {
        $cacheKey = config('cache.prefix') . ':' .$this->cacheKey;
        $keys = Redis::keys($cacheKey.'*');
        if ($keys) {
            $tokenKeys = [];
            foreach ($keys as $key) {
                if(strpos($key, $this->cacheKey.':') > -1){
                    $tokenKeys[] = str_replace($this->cacheKey.':', '', $key);
                }
            }
            Redis::del($keys);
            Redis::del($tokenKeys);
        }
    }
}
