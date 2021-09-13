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
use DB;
/**
 * Description of UserPrivacyProtocalCache
 * 
 * @author lizhijun
 */
class UserPrivacyProtocalCache extends BaseCache implements CacheInterface
{    
    public $prefix = 'user_privacy_protocal_status';
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
        $data = DB::table('user_privacy_protocal')->where('user_id', $this->key)->first();
        
        $agreeProtocal = $data ? $data->agree_protocal : 0;
        
        $this->setCache($agreeProtocal);
        
        return $agreeProtocal;
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
