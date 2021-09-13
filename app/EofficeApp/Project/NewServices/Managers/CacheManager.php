<?php

namespace App\EofficeApp\Project\NewServices\Managers;

use App\EofficeApp\Base\BaseCache;
use Illuminate\Support\Arr;
class CacheManager extends BaseCache
{
    const PROJECT_USER_REPORT = 'PROJECT_USER_REPORT';
    const PROJECT_USER_REPORT_COUNT = 'PROJECT_USER_REPORT_COUNT';
    const PROJECT_REPORT = 'PROJECT_REPORT';
    const PROJECT_CONFIG_PREFIX = 'PROJECT_CONFIG_'; // 项目配置的缓存前缀

    public static function getArrayCache($key, callable $func, $second = null)
    {
        if (!self::exists($key)) {
            $data = $func();
            self::set($key, json_encode($data), $second);
        } else {
            $data = self::get($key);
            $data = json_decode($data, true);
        }
        return $data;
    }

    public static function getValueCache($key, callable $func, $second = null) {
        if (!self::exists($key)) {
            $data = $func();
            self::set($key, $data, $second);
        } else {
            $data = self::get($key);
        }
        return $data;
    }

    public static function delCache($key) {
        self::del($key);
    }

    public static function cleanProjectReportCache()
    {
        self::del([self::PROJECT_USER_REPORT, self::PROJECT_USER_REPORT_COUNT, self::PROJECT_REPORT]);
    }

    // 获取项目配置
    public static function getProjectConfig(string $key) {
        return config('project.' . $key, []); // Todo 开发完删除
        $cacheKey = self::PROJECT_CONFIG_PREFIX . strtoupper($key);
        self::getArrayCache($cacheKey, function () use ($key) {
           return config('project.' . $key, []);
        });
    }

    // 本次请求 缓存数据
    public static function getOnceArrayCache($key, callable $func)
    {
        static $onceCacheData = [];
        if (!Arr::get($onceCacheData, $key)) {
            $onceCacheData[$key] = $func();
        }

        return $onceCacheData[$key];
    }
}
