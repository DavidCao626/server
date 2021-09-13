<?php
namespace App\Tests;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Test
 *
 * @author lizhijun
 */
class UnitTest
{
    public $startTime;
    public $startDatetime;
    public $startMemory;
    public function __construct() 
    {
        $this->startTime = microtime(true);
        $this->startDatetime = date('Y-m-d H:i:s');
        $this->startMemory = round(memory_get_usage() / 1024 / 1024, 2);
        echo "\n\033[0;33m|------------------------------------------------------------|\n";
        echo "|    E-OFFICE10 JUNIT\n";
        echo "|------------------------------------------------------------|\n\n";
        echo "\033[0m";
    }
    
    public function __destruct() 
    {
        $endTime = microtime(true);
        $total = $endTime - $this->startTime;
        if ($total < 1) {
            $total = round($total * 1000) . 'ms';
        } else {
            $total = round($total, 2) . 's';
        }
        echo "\n\033[0;33m|------------------------------------------------------------|\n";
        echo "|    初始内存: " . $this->startMemory . " MB\n";
        echo "|    结束内存: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
        echo "|    峰值内存: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
        echo "|    运行时长: " . $total . " \n";
        echo "|    开始时间: " . $this->startDatetime . " \n";
        echo "|    结束时间: " . date('Y-m-d H:i:s') . " \n";
        echo "|------------------------------------------------------------|\n\n";
    }
    public function run()
    {
        if($this->callMethods && !empty($this->callMethods)) {
            foreach ($this->callMethods as $method) {
                echo "|------------------------------------------------------------|\n";
                echo "|-- ".date('Y-m-d H:i:s')." -- [" . $method . "]\n";
                echo "|------------------------------------------------------------|\n\n";
                $this->{$method}();
                echo "\n";
            }
        } else {
            echo '暂无可测试方法';
        }
    }
    protected function getCurrentMemory()
    {
        return memory_get_usage();
    }
    
    protected function response($data)
    {
        var_dump($data);
    }
    protected function responseJson($data, $format = true)
    {
        if ($format) {
            var_dump(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            echo json_encode($data);
        }
    }
}
