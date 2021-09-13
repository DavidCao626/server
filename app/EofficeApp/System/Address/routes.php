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
    ['address/out/province', 'getIndexProvince'],
    ['address/out/city', 'getIndexCity'],
    // 查询县
    ['address/out/city-district/{cityId}', 'getCityDistrict'],
    //查询所有城市列表
    ['address/out/province/{provinceId}/city', 'getIndexProvinceCity'],
    //查询市
    ['address/out/province/{provinceId}/city/{cityId}', 'getProvinceCity'],
    ['address/out/province/{provinceId}', 'getProvince'],
    ['address/out/city/{id}', 'showCity'],
    ['address/out/district/{id}', 'showDistrict'],
    ['address/out/area', 'showAreas'],

];
