<?php
$disk = 'D:/';
$total = disk_total_space($disk);
$free = disk_free_space($disk);
$total = is_numeric($total) ? $total : 0;
$free  = is_numeric($free) ? $free : 0;
echo json_encode([
    'status' => 0,
    'disk_total_size' => $total,
    'disk_free_size'  => $free,
]);
