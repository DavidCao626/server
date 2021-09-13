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
 * 
 * @author lizhijun
 */
class MobileFontSizeCache extends BaseCache implements CacheInterface
{    
    public $prefix = 'mobile_font_size';
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
        
        $fontSize = app('App\EofficeApp\Mobile\Repositories\MobileFontSizeRepository')->getFontSize($this->key);
        
        $this->setCache($fontSize);
        
        return $fontSize;
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
