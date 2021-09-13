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
interface CacheInterface 
{
    public function setCache($value);
    public function getCache();
    public function clearCache();
}
