<?php

namespace App\EofficeApp\XiaoE\Services;

/**
 * 小e基类
 * Class BaseService
 * @package App\EofficeApp\XiaoE\Services
 */
class BaseService
{

    public function __call($method, $arguments)
    {
        return false;
    }
}
