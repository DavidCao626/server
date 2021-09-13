<?php
namespace App\Console\Schedules;
/**
 * 参考文档地址:https://laravel.com/docs/5.6/scheduling
 * 
 *@author lizhijun
 */
interface Schedule
{
    public function call($schedule);
}

