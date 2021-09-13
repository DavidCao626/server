<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Caches\Library;
use App\Caches\BaseCache;
use App\Caches\CacheInterface;
use Cache;
/**
 * Description of UserStatusCache
 * 调用方式 CacheCenter::make(UserStatus', 'admin')->getCache(); // 获取admin的用户状态
 *      CacheCenter::make('UserStatus', 'admin')->clearCache(); // 清除admin的用户状态
 *      CacheCenter::make('UserStatus', 'admin')->setCache(1); // 设置admin的用户状态为1
 * 
 * @author lizhijun
 */
class UserStatusCache extends BaseCache implements CacheInterface
{    
    public $prefix = 'user_status';
    /**
     * 清除缓存，如果没有特殊处理可以直接使用父类的chear方法。
     * @return type
     */
    public function clearCache() 
    {
        return $this->key ? $this->clear() : true;
    }
    /**
     * 获取缓存
     * 
     * @return type
     */
    public function getCache() 
    {
        if ($this->hasCache()) {
            return $this->get();
        }
        
        $status = app('App\EofficeApp\User\Repositories\UserSystemInfoRepository')->getUserStatus($this->key);
        
        $this->setCache($status);
        
        return $status;
    }
    /**
     * 设置缓存
     * @param type $value
     * @return type
     */
    public function setCache($value) 
    {
        return $this->key ? Cache::forever($this->cacheKey, $value) : true;
    }

}
