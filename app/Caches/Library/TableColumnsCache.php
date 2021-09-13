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
 */
class TableColumnsCache extends BaseCache implements HCacheInterface
{
    public $prefix = 'table_column';
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
    public function getCache($Repository) 
    {
        if ($this->hasCache($Repository)) {
            return unserialize($this->get( $Repository));
        }
        
        $TableColumns = app($Repository)->getTableColumns();
        $this->setCache($Repository, $TableColumns);
        return array_flip($TableColumns);
    }
    /**
     * 设置缓存
     * @param type $value
     * @return type
     */
    public function setCache($Repository, $TableColumns) 
    {
        return  Redis::hSet($this->cacheKey, $Repository, serialize(array_flip($TableColumns)));
    }

    public function clearAllCache()
    {
         return Redis::del($this->cacheKey);
    }

}
