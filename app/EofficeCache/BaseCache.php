<?php
namespace App\EofficeCache;
use Illuminate\Support\Facades\Redis;
use Cache;
/**
 * eoffice缓存基类，所以eoffice缓存必须集成该类
 *
 * @author lizhijun
 */
class BaseCache 
{
    protected $basePrefix = 'EOFFICE';
    protected $cacheKey;
    protected $key;
    private $delimiter = ':';
    public $struct = 'string';
    protected $ttl = null;
    public function init()
    {
        if (!$this->module) {
            throw new Exception("The cache module undefined!");
        }
        if (!$this->key) {
            throw new Exception("The cache key undefined!");
        }
        $this->cacheKey = $this->basePrefix . $this->delimiter . $this->module . $this->delimiter . $this->key;
    }
    public function clear($dynamicKey = null)
    {
        if ($this->struct == 'string') {
            return Cache::forget($this->makeFullCacheKey($dynamicKey));
        } else {
            if ($dynamicKey) {
                return Redis::hDel($this->cacheKey, $dynamicKey);
            }
            return Redis::del($this->cacheKey);
        }
    }
    public function has($dynamicKey = null)
    {
        if ($this->struct == 'string') {
            return Cache::has($this->makeFullCacheKey($dynamicKey));
        } else {
            return $dynamicKey ? Redis::hExists($this->cacheKey, $dynamicKey) : false;
        }
    }
    public function set(...$arg)
    {
        if (empty($arg)) {
            return false;
        }
        if ($this->struct == 'string') {
            $dynamicKey = null;
            if (count($arg) > 1) {
                $dynamicKey = $arg[0];
                $value = $arg[1];
            } else {
                $value = $arg[0];
            }
            $cacheKey = $this->makeFullCacheKey($dynamicKey);
            if ($this->ttl) {
                return Cache::put($cacheKey, $value, $this->ttl);
            }
            return Cache::forever($cacheKey, $value);
        } else {
            if (count($arg) < 2) {
                return false;
            }
            return Redis::hSet($this->cacheKey, $arg[0], $arg[1]);
        }
    }
    public function find(...$arg)
    {
        $result = null;
        if($this->struct == 'string') {
            $dynamicKey = null;
            $callback = null;
            if (count($arg) > 1) {
                $dynamicKey = $arg[0];
                $callback = $arg[1];
            } else if (count($arg) === 1){
                if (is_callable($arg[0])) {
                    $callback = $arg[0];
                } else {
                    $dynamicKey = $arg[0];
                }
            }
            if ($this->has($dynamicKey)) {
                $cacheKey = $this->makeFullCacheKey($dynamicKey);
                return Cache::get($cacheKey);
            }
            if ($callback && is_callable($callback)) {
                $result = $dynamicKey ? $callback($dynamicKey) : $callback();
                
                $dynamicKey ? $this->set($dynamicKey, $result) : $this->set($result);
            }
        } else {
            if (empty($arg)) {
                return null;
            }
            $dynamicKey = $arg[0];
            if ($this->has($dynamicKey)) {
                return Redis::hGet($this->cacheKey, $dynamicKey);
            }
            $callback = count($arg) > 1 ? $arg[1] : null;
            if ($callback && is_callable($callback) && ($result = $callback($dynamicKey))) {
                $this->set($dynamicKey, $result);
            }
        }
        return $result;
    }
    public function hMSet($data)
    {
        $result = Redis::hMSet($this->cacheKey, $data);
        if ($this->ttl) {
            Redis::expire($this->cacheKey, $this->ttl);
        }
        return $result;
    }
    public function hMGet($keys)
    {
        return Redis::hMGet($this->cacheKey, $keys);
    }
    public function hGetAll()
    {
        return Redis::hGetAll($this->cacheKey);
    }
    public function ttl($dynamicKey)
    {
        $cacheKey = config('cache.prefix') . ':' . ($this->has($dynamicKey) ? $this->makeFullCacheKey($dynamicKey) : $this->cacheKey);
        return Redis::ttl($cacheKey);
    }
    private function makeFullCacheKey($dynamicKey = null)
    {
        return $dynamicKey ? $this->cacheKey . $this->delimiter . $dynamicKey : $this->cacheKey;
    }
}
