<?php

define('DS', DIRECTORY_SEPARATOR);
// 当前OA服务协议
define('OA_SERVICE_PROTOCOL', isset($_SERVER['REQUEST_SCHEME']) ? strtolower($_SERVER['REQUEST_SCHEME']) : 'http');
// 当前OA服务主机地址
define('OA_SERVICE_HOST', $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ''));
