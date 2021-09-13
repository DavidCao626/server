<?php
namespace App\EofficeCache;
/**
 * 用于构建eoffice缓存实体对象和初始化。
 *
 * @author lizhijun
 */
class ECache 
{
    private static $instances = [];
    public static function make($cacheName)
    {
        if (isset(static::$instances[$cacheName])) {
            $instance = static::$instances[$cacheName];
        } else {
            list($module, $cacheEntityName) = explode(':', $cacheName);
            $cacheClass = 'App\EofficeApp\\' . ucfirst($module) . '\Caches\\' . ucfirst($cacheEntityName) . 'Cache';
            $instance = new $cacheClass();
            $instance->init();
            static::$instances[$cacheName] = $instance;
        }
        return $instance;
    }
}
