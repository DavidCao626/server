<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$routeConfig = [
    ['barcode/generate-barcode', 'generateBarcode', 'post'],
    ['barcode/pre-generate-barcode', 'preGenerateBarcode', 'post'],
    ['barcode/generate-qrcode', 'generateQrCode', 'post'],
    ['barcode/value', 'getBarcodeValue'],
    ['barcode/value', 'batchGetBarcodeValue', 'post'] // 批量查询条形码内容
];
