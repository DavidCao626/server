<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Caches;
/**
 *
 * @author lizhijun
 */
interface HCacheInterface 
{
    public function setCache($key, $value);
    public function getCache($key);
    public function clearCache($key);
    public function clearAllCache();
}
