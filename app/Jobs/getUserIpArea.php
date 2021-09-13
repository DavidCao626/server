<?php
namespace App\Jobs;

use log;
use DB;
use Illuminate\Support\Facades\Redis;
use Cache;


class getUserIpArea extends Job
{
    public $param;

    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * 登录后异步请求，获取并保存iparea
     *
     * @return bool
     */
     public function handle()
    {
        $param = $this->param;
        $handle = 'handle'.ucwords($param['handle']);
        $this->$handle($param['param']);

    }

    public function handleIpArea($param)
    {
        $result = app("App\EofficeApp\System\Log\Services\LogService")->queueIpArea($param);
        return $result;
    }

}