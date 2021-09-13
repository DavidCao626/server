<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Caches\Library;
use App\Caches\BaseCache;
use App\Caches\HCacheInterface;
use Illuminate\Support\Facades\Redis;
/**
 * Description of UserStatusCache
 * 调用方式   CacheCenter::make('HTest')->getCache('admin');获取userId为admin的用户信息
 *          CacheCenter::make('HTest')->clearCache('admin'); 清除userId为admin的用户信息
 *          CacheCenter::make('HTest')->setCache('admin', 用户信息); 设置用户ID为admin的用户信息
 *          CacheCenter::make('HTest')->clearAllCache('); // 清除所有用户信息缓存。
 * 
 * @author lizhijun
 */
class HTestCache extends BaseCache implements HCacheInterface
{
    public $prefix = 'users';
    /**
     * 清除缓存，如果没有特殊处理可以直接使用父类的chear方法。
     * @return type
     */
    public function clearCache($userId) 
    {
        return $this->clear($userId);
    }
    /**
     * 获取缓存
     * 
     * @return type
     */
    public function getCache($userId) 
    {
        if ($this->hasCache($userId)) {
            return $this->get( $userId);
        }
        
        // 查询数据后获取缓存信息
        $user = [];
        
        $this->setCache($userId, $user);
        
        return $user;
    }
    /**
     * 设置缓存
     * @param type $value
     * @return type
     */
    public function setCache($userId, $value) 
    {
        return  Redis::hSet($this->cacheKey, $userId, $value);
    }

    public function clearAllCache()
    {
         return Redis::del($this->cacheKey);
    }

}
