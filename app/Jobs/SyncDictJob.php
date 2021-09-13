<?php

namespace App\Jobs;

class SyncDictJob extends Job
{

    public $param;

    public function __construct()
    {

    }

    public function handle()
    {

        $result = app('App\EofficeApp\XiaoE\Services\SystemService')->syncDictData();

        return $result;
    }
}
