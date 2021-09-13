<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\EofficeApp\Attendance\Tests;
use App\Tests\UnitTest;
/**
 * Description of CacheTest
 *
 * @author 90536
 */
class CacheTest extends UnitTest
{
    public $callMethods = [
        'setNormalCache',
        'getNormalCache'
    ];
    public function setNormalCache()
    {
        // 设置数据

        ecache('Attendance:Test')->set(1,'ceshi');
        ecache('Attendance:Test')->set(2,'22');
        ecache('Attendance:Test')->set(3,'3333');
    }
    public function clearNormalCache()
    {
        // 清除数据
        ecache('Attendance:Test')->clear();
    }
    public function getNormalCache()
    {
        // 获取数据
        echo ecache('Attendance:Test')->get(1);
        echo ecache('Attendance:Test')->get(5);
    }
    public function setHCache()
    {
        // 设置数据
        ecache('Attendance:HTest')->set(1,'ceshi');
        ecache('Attendance:HTest')->set(2,'ceshi');
    }
    public function clearHCache()
    {
        // 清除数据
        ecache('Attendance:HTest')->delete(1);
        ecache('Attendance:HTest')->delete(2);
        ecache('Attendance:HTest')->clear();
    }
    public function getHCache()
    {
        // 获取数据
        ecache('Attendance:HTest')->get(1);
        ecache('Attendance:HTest')->get(2, function(){
            $data = 'test';
           return $data; 
        });
    }
}
