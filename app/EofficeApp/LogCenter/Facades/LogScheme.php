<?php
namespace App\EofficeApp\LogCenter\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * Description of AuthFacade
 *
 * @author lizhijun
 */
class LogScheme extends Facade 
{
    protected static function getFacadeAccessor()
    {
        return 'App\EofficeApp\LogCenter\Services\LogSchemeService';
    }
}
