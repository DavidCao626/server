<?php
namespace App\Jobs;
use App\EofficeApp\LogCenter\Traits\LogTrait;
use DB;
class  IpTransAreaJob extends Job{
    use LogTrait;
    public $timeout = 3600;
    public function handle()
    {
        $this->ipTransform();
    }
    public function ipTransform()
    {
        set_time_limit(3600);
        try {
            $result = app('App\EofficeApp\LogCenter\Repositories\LogStatisticsRepository')->getLogIpArea()->toArray();
            foreach ($result as $k => $v) {
                $this->ipArea($v['ip'], 'pro');
            }
        }catch (\Exception $e){

        }

    }

}
