<?php
namespace App\EofficeApp\Home\Tests;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use App\Tests\UnitTest;
/**
 * Description of HomeTest
 *
 * @author lizhijun
 */
class HomeTest extends UnitTest
{
    public $callMethods = [
        'sceneSeederTest'
    ];
    
    public function sceneSeederTest()
    {
        $downPath = 'http://192.168.30.66/api/eoffice-cases/eoffice-init/scene/download/EC5fd99edd72124';
        $response = app('App\EofficeApp\Home\Services\HomeService')->downScene($downPath, 64 * 1024 * 1024);
        $this->response($response);
    }
    
    public function download($downPath)
    {
        $remote = fopen($downPath,'r');
        $fileSize = 64 * 1024 * 1024;
        $local = fopen('./local-file.zip','w');

        $read_bytes = 0;
        while(!feof($remote)) {
          $buffer = fread($remote,2048);
          fwrite($local,$buffer);
          $read_bytes += 2048;
          $progress = min(100,100 * $read_bytes / $fileSize);
          echo $progress, "\r\n";
        }
        fclose($remote);
        fclose($local);
    }
}
