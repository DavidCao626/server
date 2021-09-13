<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../bootstrap/app.php';

$userCount = DB::table('user')->where('deleted_at', NULL)->count();

echo json_encode([
    'user_count' => $userCount
]);
