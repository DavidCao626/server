<?php

namespace App\Jobs;

use DB;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\LogCenter\Facades\LogScheme;

class SyncLogToElasticSearchJob extends Job
{

    public $timeout = 0;
    public $tries = 0;
    public function handle()
    {
        //todo 判断是不是开启es
        $this->syncMysqlToEs();

    }

    public function syncMysqlToEs()
    {
        set_time_limit(0);
        $service = app('App\EofficeApp\LogCenter\Services\ElasticService');
        $logModules = LogScheme::getAllLogModules();
        $tableConfig = [];
        foreach ($logModules as $k => $value) {
            $tableConfig[] = config('elastic.logCenter.tablePrefix') . $value['module_key']; //todo 通过makelog方法获取表前缀
        }
        //获取当前每个模块最大id ['system' =>232]
        $syncPosition = $service->searchModuleMaxId();
        foreach ($tableConfig as $table) {  //todo 每次同步几条合适？
            DB::table($table)->orderBy('log_id')->where('log_id', '>', $syncPosition[substr($table, 7)])->chunk(2000, function ($list) use ($table, $service) {
                $data = json_decode(json_encode($list), true);
                foreach ($data as $k => $v) {
                    $data[$k]['module_key'] = substr($table, 7);
                }
                $service->addManyLog($data);
                // todo 后期注释掉
//                print_r(date("Y-m-d H:i:s", time()));

            });
        }

        if(Redis::exists('logCenter:lock')){
            Redis::del('logCenter:lock');
        }


    }
}
