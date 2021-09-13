<?php

namespace App\EofficeApp\EofficeCase\Services;

class Redis
{
    public $Config;
    public function __construct()
    {
        $this->Config = include(__DIR__ . '/conf/config.php');
    }

    // 清Redis缓存
    public function flushRedis($index)
    {
        if (!is_numeric($index)) {
            return false;
        }
        $server = [
            'host'     => config('database.redis.default.host'),
            'port'     => config('database.redis.default.port'),
            'database' => intval($index),
            'password' => config('database.redis.default.password'),
        ];
        $client = new \Predis\Client($server);
        $client->flushdb();
        return true;
    }

    /**
     * 分配redis_database
     * 
     * */
    public function allocRedisDatabase($config = [])
    {
        if (!is_array($config)) {
            return 1;
        }
        $redisMaxDatabase = count($config) + 1;
        $allRedisDatabase = [];
        for ($i = 1; $i <= $redisMaxDatabase; $i++) {
            $allRedisDatabase[] = $i;
        }
        $usedDatabase = [];
        if (count($config) > 0) {
            foreach ($config as $value) {
                $redisDatabase = $value['redis_database'] ?? false;
                if (is_numeric($redisDatabase) && $redisDatabase > 0) {
                    $usedDatabase[] = intval($redisDatabase);
                }
            }
        }

        $canUseDatabase = array_diff($allRedisDatabase, $usedDatabase);
        $newIndex = array_shift($canUseDatabase);
        $this->flushRedis($newIndex);
        return $newIndex;
    }
}
