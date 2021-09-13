<?php
$config = require_once './config.php';
$data = $_POST;

if (empty($data)) {
    return '';
}
$date = $time = '';
foreach ($data as $key => $value) {
    if (strpos(strtolower($key), 'data_') === 0) {
        if ($value === '上午' || strtolower($value) === 'am') {
            $time = $config['am'][0];
        } else if ($value === '下午' || strtolower($value) === 'pm') {
            $time = $config['pm'][0];
        } else {
            $date = $value;
        }
    }
}
echo json_encode($date . ' ' . $time);
exit;
