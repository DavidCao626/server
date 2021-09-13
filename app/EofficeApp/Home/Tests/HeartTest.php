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
class HeartTest extends UnitTest
{
    public $callMethods = [
        'heartTest'
    ];
    
    public function heartTest()
    {
        while(true) {
            sleep(1);
            $response = ecache('Home:SceneSeederProgress')->get();
            $this->response($response);
        }
    }
}
