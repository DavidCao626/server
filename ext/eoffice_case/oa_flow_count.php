<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../bootstrap/app.php';

$flowCount = DB::table('flow_type')->where('deleted_at', NULL)->count();

echo json_encode([
    'flow_count' => $flowCount
]);