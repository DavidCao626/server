<?php
namespace App\EofficeApp\System\Barcode\Tests;
use App\Tests\UnitTest;

class BarcodeTest extends UnitTest
{
    public $callMethods = [
        'test'
    ];
    public function test()
    {
        $params = [
            'type' => 1,
            'value' => 121212,
            'hide_code' => 1
        ];
        app('App\EofficeApp\System\Barcode\Services\BarcodeService')->generateBarcode($params);
    }
}
