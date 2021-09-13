
<?php
// use Illuminate\Support\Facades\Redis;
// use Illuminate\Support\Facades\DB;

// use Illuminate\Support\Facades\Artisan;
// require __DIR__ . '/../../bootstrap/app.php';

//  //每小时执行一次的任务,插入system_login_log表中的数据
// $data = [];
// $datas = Redis::hGetAll('system_login_log');
// if (isset($datas) && !empty($datas)) {
//     foreach ($datas as $k => $v) {
//         $data = unserialize($v);
//         $result = DB::table('system_login_log')->insert($data);
//     }
// }
// Redis::del('system_login_log');

// $exitCode = Artisan::call('schedule:run', []);
