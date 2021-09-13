<?php

require __DIR__ . '/../../bootstrap/app.php';

//  获取oa大小
$eofficeCaseService = 'App\EofficeApp\EofficeCase\Services\EofficeCaseService';
$result = app($eofficeCaseService)->getEofficeCaseSize($_REQUEST['case_id']);

echo json_encode($result);