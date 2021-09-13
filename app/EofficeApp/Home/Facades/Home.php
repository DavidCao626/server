<?php
namespace App\EofficeApp\Home\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * Description of AuthFacade
 *
 * @author lizhijun
 */
class Home extends Facade 
{
    protected static function getFacadeAccessor()
    {
        return 'App\EofficeApp\Home\Services\HomeService';
    }
}
