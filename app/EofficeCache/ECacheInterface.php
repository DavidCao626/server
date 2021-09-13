<?php
namespace App\EofficeCache;

/**
 * EOFFICE缓存接口，所有eoffice缓存必须实现该接口。
 *
 * @author lizj
 */
interface ECacheInterface 
{
    public function get($dynamicKey = null);
}
