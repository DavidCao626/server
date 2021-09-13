<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Caches;
/**
 * Description of CacheCenter
 *
 * @author lizhijun
 */
class CacheCenter 
{
    private static $instances = [];
    public static function make($cacheName, $key = null)
    {
        if (isset(static::$instances[$cacheName])) {
            $instance = static::$instances[$cacheName];
        } else {

            $cacheClass = 'App\Caches\Library\\' . $cacheName . 'Cache';

            $instance = new $cacheClass();

            static::$instances[$cacheName] = $instance;
        }
        $instance->setKey($key);
        return $instance;
    }
}
