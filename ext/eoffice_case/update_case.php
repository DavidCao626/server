<?php

require __DIR__ . '/../../bootstrap/app.php';

$CURRENT_DISK = 'D:';

$caseId = $_REQUEST['case_id'] ?? '';
if (!is_string($caseId) || $caseId === '') {
    echo404();
}
$serverDir = base_path();
$eoffice10Dir = $serverDir . DIRECTORY_SEPARATOR . '..';
$binDir = $serverDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin';
$configIniFile = $binDir . DIRECTORY_SEPARATOR . 'config_' . $caseId . '.ini';

$versionFile = $eoffice10Dir . DIRECTORY_SEPARATOR . 'version.json';
if (!file_exists($versionFile)) {
    echo404();
}
$version = json_decode(file_get_contents($versionFile), true);
if (!isset($version['package'])) {
    echo404();
}

if (!file_exists($configIniFile)) {
    echo404();
}

$command = $CURRENT_DISK . ' && cd ' . $serverDir . ' && php artisan eofficeCase:update ' . $version['package'] . ' ' . $caseId;
$command = str_replace("\\", '/', $command);
exec($command);

echo json_encode([
    'status' => 1
]);
die;

function echo404()
{
    header("status: 404 Not Found");
    die;
}
