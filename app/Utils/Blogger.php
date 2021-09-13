<?php

namespace App\Utils;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Blogger
{
    /**
     * 日志实例
     *
     * @return object
     */
    public static function getLogger($fileName, $logName)
    {
        return (new Logger($logName))->pushHandler(
            (new StreamHandler(storage_path().'/logs/'. $fileName .'.log', Logger::INFO))
            ->setFormatter(new LineFormatter(null, null, true, true))
        );
    }

    /**
     * 慢查询sql记录
     *
     * @return void
     */
    public static function longSqlLog($db, $sql) {
        $appDebug = env('APP_DEBUG', false);
        $logQueryTime = config("app.long_query_time");

        if ($appDebug && $db->time > $logQueryTime && $db->sql[0] == 's') {
            $fileName = date('Y').'/'.date('m').'/long_sql';
            self::getLogger($fileName, 'sql')->info($sql, [$db->time.'ms']);
        }
    }

    /**
     * 慢执行api记录
     *
     * @return void
     */
    public static function longApiLog($api, $runTime) {
        $appDebug = env('APP_DEBUG', false);
        $apiExecuteTime = config("app.api_execute_time");
        $runTime = $runTime * 1000;

        if ($appDebug && $runTime > $apiExecuteTime) {
            $fileName = date('Y').'/'.date('m').'/long_api';
            self::getLogger($fileName, 'api')->info($api, [$runTime . 'ms']);
        }
    }

    /**
     * 记录日志
     *
     * @return void
     */
    public static function log($fileName, $message, $type = 'info', $context = []) {
        self::getLogger($fileName, $fileName)->log($type, $message, $context);
    }
}