<?php

namespace App\EofficeApp\Elastic\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Configurations\InitConfig;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Elastic\Entities\ElasticSearchConfigEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ElasticSearchConfigRepository extends BaseRepository
{
    /**
     * 注册搜索引擎配置实体
     *
     * @param \App\EofficeApp\Elastic\Entities\ElasticSearchConfigEntity $entity
     *
     */
    public function __construct(ElasticSearchConfigEntity $entity )
    {
        parent::__construct($entity);
    }

    /**
     * 根据key和type获取指定对象
     *
     * @param string $key
     * @param string $type
     * @param bool $isCreated
     *
     * @return  ElasticSearchConfigEntity |null
     */
    public function getConfigByKey($key, $type = '', $isCreated = true)
    {
        if ($isCreated) {
            $config = $type ?
                $this->entity->firstOrCreate(['key' => $key, 'type' => $type]) :
                $this->entity->firstOrCreate(['key' => $key]);
        } else {
            $this->entity->where('key', $key);
            if ($type) {
                $this->entity->where('type', $type);
            }
            $config = $this->entity->first();
        }

        return $config;
    }

    /**
     * 根据 type 和 key 获取指定对象的value
     *
     * @param string $type
     * @param string $key
     *
     * @return string
     */
    public function getConfigValue($type, $key)
    {
        $config = $this->getConfigByKey($key, $type);

        return $config ? $config->value : '' ;
    }

    /**
     * 更新指定对象的value
     *
     * @param string $key
     * @param string $value
     * @param string $type
     */
    public function setConfigValueByKey($key, $value, $type = '')
    {
        /** @var ElasticSearchConfigEntity $config */
        $config = $this->getConfigByKey($key, $type);
        if ($config) {
            $config->value = $value;
            $config->save();
        }
    }

    /**
     * 清除指定key和type的配置
     *
     * @param string $key
     * @param string $type   配置对应的功能模块
     */
    public function removeConfig($key, $type = '')
    {
        $entity = $this->entity;
        $entity->where('key', $key);

        if ($type) {
            $entity->where('type', $type);
        }

        $deletedRows = $entity->delete();

        return $deletedRows;
    }

    /**
     * 初始化配置
     *  1. 基本配置
     *  2. 运行配置
     *  3. 全站搜索
     *  4. 全站搜索-schedule配置
     *  5. 全站搜素-queue配置
     */
    public function initConfig()
    {
        $this->entity->truncate();
        $run = InitConfig::INIT_RUN_CONFIG;
        $global = InitConfig::INIT_GLOBAL_SEARCH_CONFIG;
        $schedule = InitConfig::INIT_GLOBAL_SEARCH_SCHEDULE_CONFIG;
        $queue = InitConfig::INIT_GLOBAL_SEARCH_QUEUE_CONFIG;
        $other = InitConfig::INIT_OTHER_CONFIG;

        $initData = array_merge($run, $global, $schedule, $queue, $other);

        DB::table(ElasticTables::ELASTIC_CONFIG_TABLE)->insert($initData);
    }

    /**
     * 根据类型获取所有配置
     *
     * @param string $type  配置类型
     * @param bool  $isFrontFormat  是否格式化
     */
    public function getAllConfigsByType($type, $isFrontFormat = false)
    {
        $configs = $this->entity->where('type', $type)->get()->toArray();

        if ($isFrontFormat) {
            $configs = array_map(function ($config) {
                return [
                    'type' => $config['type'],
                    'key' => $config['key'],
                    'value' => $config['value'],
                    'trans' => 'elastic.'.$config['key']
                ];
            }, $configs);
        }
        return $configs;
    }

    /**
     * 获取运行配置
     *
     * @param bool $isFormat
     */
    public function getRunConfigInfo($isFormat = true)
    {
        return $this->getAllConfigsByType(ConfigOptions::CONFIG_TYPE_RUN, $isFormat);
    }

    /**
     * 获取全站搜索指定类型的配置信息
     *
     * @param string $key   配置信息的key
     * @param string $type  配置类型
     *
     * @return array
     */
    public function getGlobalSearchUpdateConfig($key, $type)
    {
        $config = [
            'basic' => false,
            'globalSearch' => false,
            'extra' => [],
        ];

        $config['basic'] = true;
        $globalSearch = $this->getConfigValue(ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH, $key);

        if ($globalSearch) {
            $config['globalSearch'] = true;
        }
        $config['extra'] = $this->getAllConfigsByType($type, true);

        return $config;
    }

    /**
     * 更新全站搜索定时任务状态
     *
     * @param bool $scheduleStatus 全站搜索定时任务开启/关闭
     */
    public function updateGlobalSearchScheduleStatus($scheduleStatus)
    {
        $this->entity->updateOrCreate(
            [
                'type' => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
                'key' => ConfigOptions::UPDATE_BY_SCHEDULE
            ],
            ['value' => $scheduleStatus]
        );
    }

    /**
     * 更新全站定时任务配置
     *
     * @param array $extra
     */
    public function updateGlobalSearchScheduleConfig($extra)
    {
        try {
            $globalSearchScheduleConfig = [
                'type' => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE
            ];

            foreach ($extra as $key => $value) {
                $config = array_merge($globalSearchScheduleConfig, ['key' => $key]);
                $this->entity->updateOrCreate($config, ['value' => $value]);
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * 更新全站搜索定消息队列状态
     *
     * @param bool $queueStatus 全站搜索定消息队列开启/关闭
     */
    public function updateGlobalSearchQueueStatus($queueStatus)
    {
        $this->entity->updateOrCreate(
            [
                'type' => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
                'key' => ConfigOptions::UPDATE_BY_QUEUE
            ],
            ['value' => $queueStatus]
        );
    }

    /**
     * 更新全站消息队列配置
     *
     * @param array $extra
     */
    public function updateGlobalSearchQueueConfig($extra)
    {
        try {
            $globalSearchScheduleConfig = [
                'type' => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE
            ];

            foreach ($extra as $key => $value) {
                $key = $value['key'];
                $value = (bool) $value['value'];
                $config = array_merge($globalSearchScheduleConfig, ['key' => $key]);
                $this->entity->updateOrCreate($config, ['value' => $value]);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * 获取全站搜索队列配置
     *
     * @return array
     */
    public function getGlobalSearchQueueConfig()
    {
        return $this->entity->where('type', ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE)
                            ->select(['key','value'])
                            ->get()
                            ->toArray();
    }

    /**
     * 获取全站搜素消息队列已开启的索引分类
     *
     * @return array
     */
    public function getGlobalSearchQueueStartedCategory()
    {
       return $this->entity->where('type', ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE)
                           ->where('value', 1)
                           ->pluck('key')
                           ->toArray();
    }

    /**
     * 批量更新
     *
     * @see https://github.com/mavinoo/laravelBatch
     */
    public static function batchUpdate($model, array $values, $index = null)
    {
        $final = [];
        $ids = [];

        if (!count($values)) {
            return false;
        }
        if (!isset($index) || empty($index)) {
            $index = $model->getKeyName();
        }
        foreach ($values as $key => $val) {
            $ids[] = $val[$index];
            foreach (array_keys($val) as $field) {
                if ($field !== $index) {
                    $value = (is_null($val[$field]) ? 'NULL' : '"' . self::mysqlEscape($val[$field]) . '"');
                    $final[$field][] = 'WHEN `' . $index . '` = "' . $val[$index] . '" THEN ' . $value . ' ';
                }
            }
        }
        $cases = '';
        foreach ($final as $k => $v) {
            $cases .= '`' . $k . '` = (CASE ' . implode("\n", $v) . "\n"
                . 'ELSE `' . $k . '` END), ';
        }
        $full_table = $model->getConnection()->getTablePrefix() . $model->getTable();
        $query = "UPDATE `" .$full_table . "` SET " . substr($cases, 0, -2) . " WHERE `$index` IN(" . '"' . implode('","', $ids) . '"' . ");";
        \DB::update($query);
    }

    private static function mysqlEscape($inp)
    {
        if(is_array($inp)) return array_map(__METHOD__, $inp);

        if(!empty($inp) && is_string($inp))
        {
            return str_replace(
                ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
                ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
                $inp);
        }

        return $inp;
    }

    /**
     * 根据schema的analyzer获取版本
     */
    public function getSchemaVersion()
    {
        $analyzer = (int)$this->getConfigValue(ConfigOptions::CONFIG_TYPE_SCHEMA_MAPPING, ConfigOptions::SCHEMA_ANALYZER);

        if ($analyzer) {
            if ($analyzer === ConfigOptions::SCHEMA_ANALYZER_IK_SMART) {
                return Constant::V1;
            } elseif ($analyzer === ConfigOptions::SCHEMA_ANALYZER_KEYWORD) {
                return Constant::V3;
            } else {
                return Constant::V2;
            }
        }

        return Constant::DEFAULT_VERSION;
    }

    /**
     * 获取当前配置的analyzer
     */
    public function getAnalyzer()
    {
        $analyzer = (int) $this->getConfigValue(ConfigOptions::CONFIG_TYPE_SCHEMA_MAPPING, ConfigOptions::SCHEMA_ANALYZER);

        if ($analyzer === ConfigOptions::SCHEMA_ANALYZER_IK_SMART) {
            return 'ik_smart';
        } else {
            return 'ik_max_word';
        }
    }

    /**
     * 更新schemaMapping的analyzer
     */
    public function updateAnalyzer($type)
    {
        $this->entity->updateOrCreate(
            [
                'type' => ConfigOptions::CONFIG_TYPE_SCHEMA_MAPPING,
                'key' => ConfigOptions::SCHEMA_ANALYZER
            ],
            ['value' => $type]
        );
    }

    /**
     * 更新定时任务缓存
     * 格式如下
     *  [
     *      update_by_schedule => 1,
     *      globalSearchSchedule => [
     *          schedule_update_period => 1,
     *          schedule_update_day_time => 1,
     *          schedule_update_week_time => 1,
     *      ]
     *  ]
     */
    public function refreshConfigCache(): array
    {
        $config = [
            ConfigOptions::UPDATE_BY_SCHEDULE => 0,
            ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE => [],
        ];
        // 是否开启定时更新
        $globalSearchEnable = $this->getConfigValue(
            ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
            ConfigOptions::UPDATE_BY_SCHEDULE
        );
        $config[ConfigOptions::UPDATE_BY_SCHEDULE] = $globalSearchEnable;

        if ($globalSearchEnable) {
            // 获取更新相关配置
            $scheduleConfigs = $this->getScheduleUpdateConfig();
            foreach ($scheduleConfigs as $scheduleConfig) {
                $config[ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE][$scheduleConfig->key] = $scheduleConfig->value;
            }

        }

        Redis::set(RedisKey::REDIS_SCHEDULE_CONFIG, json_encode($config));

        return $config;
    }

    /**
     * 获取定时任务更新配置
     *
     * @return array|Collection
     */
    private function getScheduleUpdateConfig()
    {
        return $this->getTargetTypeConfig(ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE);
    }


    /**
     * 获取指定类型配置
     *
     * @param string $type
     * @param bool   $toArray
     *
     * @return array|Collection
     */
    public function getTargetTypeConfig(string  $type, bool $toArray = false)
    {
        $queryBuilder =  $this->entity->where('type', $type)->get();

        if ($toArray) {
            $queryBuilder = $queryBuilder->toArray();
        }

        return $queryBuilder;
    }
}