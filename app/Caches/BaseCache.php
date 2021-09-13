<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Caches;
use Cache;
use Illuminate\Support\Facades\Redis;
/**
 * Description of BaseCache
 *
 * @author lizhijun
 */
class BaseCache 
{
    protected $basePrefix = '';
    protected $cacheKey;
    protected $key;
    private $delimiter = '_';
    public function setKey($key = null) 
    {
        $this->key = $key;
        $prefix = $this->prefix ?? null;
        $this->setCacheKey($key, $prefix);
    }
    private function setCacheKey($key, $prefix = null)
    {
        $cacheKey = '';
        if($prefix){
            $cacheKey .= $prefix;
        }
        if($key) {
            $key = md5(is_array($key) ? json_encode($cacheKey) : $key);
            if($cacheKey) {
                $cacheKey .= $this->delimiter . $key;
            } else {
                $cacheKey .= $key;
            }
        }
        if($this->basePrefix) {
            $cacheKey = $this->basePrefix . $this->delimiter . $cacheKey;
        }

        $this->cacheKey = $cacheKey;
    }
    public function getCacheKey() 
    {
        return $this->cacheKey;
    }
    public function hasCache($subKey = null) 
    {
        if ($subKey) {
            return Redis::hExists($this->cacheKey, $subKey);
        }
        return Cache::has($this->cacheKey);
    }
    public function clear($subKey = null) {
        if ($subKey) {
            Redis::hDel($this->cacheKey, $subKey);
        }
        return Cache::forget($this->cacheKey);
    }
    public function get($subKey = null)
    {
        if ($subKey) {
            return Redis::hGet($this->cacheKey, $subKey);
        }
        return Cache::get($this->cacheKey);
    }
    public function clearAll()
    {
        Redis::del($this->cacheKey);
    }
}
