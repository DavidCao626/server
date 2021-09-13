<?php

require __DIR__ . '/../../bootstrap/app.php';
use Illuminate\Support\Facades\DB;



$lastLoginLog = DB::table('eo_log_system')->where('log_operate', 'login')->orderBy('log_time', 'desc')->first();
$loginCount   = DB::table('eo_log_system')->where('log_operate', 'login')->count();
$result = [];
if ($lastLoginLog && $lastLoginLog->creator) {
    $userId   = $lastLoginLog->creator;
    $userInfo = DB::table('user')->where('user_id', $userId)->first();
    if ($userInfo) {
        $result = [
            'oa_user_id'       => $userId,
            'oa_user_name'     => $userInfo->user_name,
            'last_login_time'  => $lastLoginLog->log_time,
            'login_ip'         => $lastLoginLog->ip,
            'login_count'      => $loginCount,
        ];
    }
}

echo json_encode($result);
