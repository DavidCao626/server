<?php


namespace App\EofficeApp\Elastic\Configurations;


/**
 * 此处存储ES配置相关常量
 */
final class ConfigOptions
{
    // ===========================================================
    //                       配置类型
    // ===========================================================
    const CONFIG_TYPE_SCHEMA_MAPPING = 'schemaMapping'; // 映射配置
    const CONFIG_TYPE_GLOBAL_SEARCH = 'globalSearch';    // 全站搜索配置
    const CONFIG_TYPE_SYSTEM_LOG = 'systemLog';     // 系统日志配置
    const CONFIG_TYPE_RUN = 'run';                  // 运行配置
    const CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE = 'globalSearchSchedule';   //  全站搜索的定时任务配置
    const CONFIG_TYPE_GLOBAL_SEARCH_QUEUE = 'globalSearchQueue';    //  全站搜索队列配置

    // ===========================================================
    //                       基本配置
    // ===========================================================

    const UPDATE_BY_SCHEDULE = 'update_by_schedule';            // 开启定时更新
    const SCHEDULE_UPDATE_PERIOD = 'schedule_update_period';    // 定时任务更新周期
    const SCHEDULE_UPDATE_DAY_TIME = 'schedule_update_day_time';    // 定时任务每天更新时间
    const SCHEDULE_UPDATE_WEEK_TIME = 'schedule_update_week_time';  // 定时任务每周更新时间
    const UPDATE_BY_QUEUE = 'update_by_queue';                  // 开启消息队列更新

    // 定时任务更新周期
    const SCHEDULE_UPDATE_PERIOD_DAY = 1;
    const SCHEDULE_UPDATE_PERIOD_WEEK = 2;
    // 定时任务周更新时间
    const SCHEDULE_UPDATE_WEEK_AT_MONDAY = 1;
    const SCHEDULE_UPDATE_WEEK_AT_TUESDAY = 2;
    const SCHEDULE_UPDATE_WEEK_AT_WEDNESDAY = 3;
    const SCHEDULE_UPDATE_WEEK_AT_THURSDAY = 4;
    const SCHEDULE_UPDATE_WEEK_AT_FRIDAY = 5;
    const SCHEDULE_UPDATE_WEEK_AT_SATURDAY = 6;
    const SCHEDULE_UPDATE_WEEK_AT_SUNDAY = 7;

    // 有效的更新周期
    const SCHEDULE_VALID_PERIOD = [
        self::SCHEDULE_UPDATE_PERIOD_DAY => self::SCHEDULE_UPDATE_PERIOD_DAY,
        self::SCHEDULE_UPDATE_PERIOD_WEEK => self::SCHEDULE_UPDATE_PERIOD_WEEK,
    ];

    // 有效的按周更新时间
    const SCHEDULE_VALID_UPDATE_WEEK = [
        'monday' => self::SCHEDULE_UPDATE_WEEK_AT_MONDAY,
        'tuesday' => self::SCHEDULE_UPDATE_WEEK_AT_TUESDAY,
        'wednesday' => self::SCHEDULE_UPDATE_WEEK_AT_WEDNESDAY,
        'thursday' => self::SCHEDULE_UPDATE_WEEK_AT_THURSDAY,
        'friday' => self::SCHEDULE_UPDATE_WEEK_AT_FRIDAY,
        'saturday' => self::SCHEDULE_UPDATE_WEEK_AT_SATURDAY,
        'sunday' => self::SCHEDULE_UPDATE_WEEK_AT_SUNDAY,
    ];

    // ===========================================================
    //                       映射配置配置
    // ===========================================================
    /**
     * 分析器
     *  1. analyzer 为 ik_smart或 ik_max_word
     */
    const SCHEMA_ANALYZER = 'analyzer';
    const SCHEMA_ANALYZER_IK_SMART = 1;
    const SCHEMA_ANALYZER_IK_MAX_WORD = 2;
    const SCHEMA_ANALYZER_KEYWORD = 3;

    // ===========================================================
    //                       运行配置
    // ===========================================================
    /**
     * 运行配置类型
     *  1. 日志存放位置
     *  2. jvm内存配置
     */
    const ALL_RUN_CONFIGS = [
        self::LOG_PATH => self::LOG_PATH,
        self::JVM_MEMORY => self::JVM_MEMORY,
    ];
    const LOG_PATH = 'log_path';    // 日志存放位置
    const JVM_MEMORY = 'jvm_memory';   // jvm内存配置

    /**
     * 配置初始化值
     */
    const DEFAULT_INDICES_PRESERVED = 3;    // 索引默认保留历史版本 3
    const DEFAULT_UPDATE_BY_SCHEDULE = true;      // 索引默认定时任务更新方式开启
    const DEFAULT_UPDATE_BY_QUEUE = false;      // 索引默认消息队列更新方式关闭
    const DEFAULT_SCHEDULE_UPDATE_DAY_TIME = '5:00'; // 默认每天5点更新
    const DEFAULT_SCHEDULE_UPDATE_PERIOD = 1;  // 索引默认更新周期 天
    const DEFAULT_JVM_MEMORY = 2;   // jvm内存默认使用2g
    const DEFAULT_LOGS_PATH = '/';  // es日志默认位于项目logs的es中
    const INDEX_LIMIT_BY_CATEGORY = 3; // 按分类索引数量上限
    const DEFAULT_SCHEMA_ANALYZER = self::SCHEMA_ANALYZER_IK_SMART; // 默认使用ik_smart

    // ===========================================================
    //                       索引更新相关
    // ===========================================================
    /**
     * 若索引更新方式为消息队列, 消息队列更新方式
     */
    const ES_QUEUE_UPDATE_TYPE = [
        'partUpdate' => self::QUEUE_UPDATE_PART_UPDATE,
        'documentReindex' => self::QUEUE_UPDATE_DOCUMENT_REINDEX,
        'typeReindex' => self::QUEUE_UPDATE_TYPE_REINDEX,
        'functionReindex' => self::QUEUE_UPDATE_FUNCTION_REINDEX,
        'allReindex' => self::QUEUE_UPDATE_ALL_REINDEX,
    ];

    const QUEUE_UPDATE_PART_UPDATE = 'partUpdate';              // 文档部分更新
    const QUEUE_UPDATE_DOCUMENT_REINDEX = 'documentReindex';    // 按文档重新索引
    const QUEUE_UPDATE_TYPE_REINDEX = 'typeReindex';            // 按类型重新索引
    const QUEUE_UPDATE_FUNCTION_REINDEX = 'functionReindex';    // 按功能重新索引
    const QUEUE_UPDATE_ALL_REINDEX = 'allReindex';              // 全部重新索引

    // ===========================================================
    //                       全站搜索相关配置
    // ===========================================================
    const ALL_GLOBAL_SEARCH_CONFIGS = [
        self::QUEUE_GLOBAL_SEARCH => self::QUEUE_GLOBAL_SEARCH,
        self::QUEUE_GLOBAL_SEARCH_CATEGORIES => self::QUEUE_GLOBAL_SEARCH_CATEGORIES,
    ];
    const QUEUE_GLOBAL_SEARCH = 'queue_global_search';  // 全站搜索的消息队列更新是否开启
    const QUEUE_GLOBAL_SEARCH_CATEGORIES  = 'queue_global_search_categories';   // 全站搜索消息队列更新的分类
}