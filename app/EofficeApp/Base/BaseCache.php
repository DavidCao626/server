<?php

namespace App\EofficeApp\Base;
use DB;
use Illuminate\Support\Facades\Redis;

class BaseCache
{
    const MAX_WHERE_IN = 5000;

    public static function setByQuery($query, $primaryId,  $cacheKey, $restore = true, $chunkNum = 50000)
    {
        $query->select($primaryId);
        Redis::sadd($cacheKey, '');
        $query->chunk($chunkNum, function ($items) use ($primaryId, $cacheKey, $restore) {
            self::sAddArray($cacheKey, $items->pluck($primaryId)->toArray(), $restore);
        });
    }

    /**
     * 将数据加入集合
     * @param $cacheKey
     * @param $array
     * @param bool $restore
     */
    public static function sAddArray($cacheKey, $array, $restore = false)
    {
        if ($restore) {
            Redis::del($cacheKey);
        }
        if (empty($array)) {
            array_push($array, 0);
        }
        array_unshift($array, $cacheKey);
        self::redisCommand('sadd', $array);
    }

    /**
     * 设置过期时间，默认10分钟
     * @param $cacheKeys string|array
     * @param int $ttl
     */
    public static function expire($cacheKeys, $ttl = 600)
    {
        $cacheKeys = is_array($cacheKeys) ? $cacheKeys : [$cacheKeys];
        foreach ($cacheKeys as $cacheKey) {
            Redis::expire($cacheKey, $ttl);
        }
    }

    /**
     * 设置过期时间，默认10分钟
     * @param $cacheKeys string|array
     * @param int $timestamp
     */
    public static function expireAt($cacheKeys, $timestamp = null)
    {
        $timestamp = is_null($timestamp) ? strtotime('+10second') : $timestamp;
        $cacheKeys = is_array($cacheKeys) ? $cacheKeys : [$cacheKeys];
        foreach ($cacheKeys as $cacheKey) {
            Redis::expireAt($cacheKey, $timestamp);
        }
    }

    public static function set($cacheKey, $data, $second = null)
    {
        Redis::set($cacheKey, $data);
        $second && self::expire($cacheKey, $second);
    }

    public static function get($cacheKey)
    {
        return Redis::get($cacheKey);
    }

    /**
     * 利用缓存构建whereIn
     * @param $query
     * @param $column
     * @param string|array $data 缓存key或id数组
     * @param int $maxWhereIn
     * @return mixed
     */
    protected static function buildWhereInFromCache($query, $column, &$data, $maxWhereIn = self::MAX_WHERE_IN) {
        // 如果是string，则是缓存名，不是值
        if (is_string($data)) {
            $data = Redis::sMembers($data);
        }
        if (!empty($data) && count($data) > $maxWhereIn) {
            $tableName = rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($data, $maxWhereIn, true);
            foreach ($tempIds as $key => $item) {
                $ids  = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->rightJoin($tableName, $tableName . ".data_id", '=', $column);
        } else {
            $query = $query->whereIn($column, $data);
        }

        return $query;
    }

    /**
     * 验证缓存是否存在
     * @param $cacheKeys string|array
     * @return bool
     */
    protected static function exists($cacheKeys)
    {
        $cacheKeys = is_array($cacheKeys) ? $cacheKeys : [$cacheKeys];
        foreach ($cacheKeys as $cacheKey) {
            if (!Redis::exists($cacheKey)) {
                return false;
            }
        }

        return true;
    }

    protected static function del($cacheKeys)
    {
        $cacheKeys = is_array($cacheKeys) ? $cacheKeys : [$cacheKeys];
        foreach ($cacheKeys as $cacheKey) {
            Redis::del($cacheKey);
        }
    }

//    protected static function setOperation($data, $i = 0)
//    {
//        $types = ['sInterStore', 'sUnionStore'];
//        $type = array_shift($data);
//
//        if (!in_array($type, $types)) {
//            return '';
//        }
//        $cacheKeys = [];
//        foreach ($data as $cacheKey) {
//            if (is_array($cacheKey)) {
//                $cacheKey = self::setOperation($cacheKey, ++$i);
//            }
//            $cacheKeys[] = $cacheKey;
//        }
//        $newCacheKey = uniqid('project_') . $i;
//
//        array_unshift($cacheKeys, $newCacheKey);
//        self::redisCommand($type, $cacheKeys);
//        self::expire($newCacheKey, 60);
//        return $newCacheKey;
//    }

    /**
     * 执行redis命令
     * @param string $command eg:sadd
     * @param array $array [$cacheKey, value1, value2,value3]
     */
    protected static function redisCommand($command, $array)
    {
        call_user_func_array('Redis::' . $command, $array);
    }

    // 为缓存键值拼接用户id
//    protected static function padUId($prefixCacheKey, $userId = null)
//    {
//        $userId = is_null($userId) ? user_id() : $userId;
//        return $prefixCacheKey . $userId;
//    }
}