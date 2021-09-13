<?php


namespace App\EofficeApp\Elastic\Configurations;

/**
 * 此处存储ES初始配置
 */
final class InitConfig
{
    const FIELDS_TYPE = 'type';
    const FIELDS_KEY = 'key';
    const FIELDS_VALUE = 'value';
    const FIELDS_DEFAULT = 'default';
    const FIELDS_REMARK = 'remark';

    /**
     * 运行配置
     *  1. 日志目录
     *  2. jvm
     */
    const INIT_RUN_CONFIG = [
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_RUN,
            self::FIELDS_KEY => ConfigOptions::LOG_PATH,
            self::FIELDS_VALUE => ConfigOptions::DEFAULT_LOGS_PATH,
            self::FIELDS_DEFAULT => ConfigOptions::DEFAULT_LOGS_PATH,
            self::FIELDS_REMARK => '日志路径',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_RUN,
            self::FIELDS_KEY => ConfigOptions::JVM_MEMORY,
            self::FIELDS_VALUE => ConfigOptions::DEFAULT_JVM_MEMORY,
            self::FIELDS_DEFAULT => ConfigOptions::DEFAULT_JVM_MEMORY,
            self::FIELDS_REMARK => 'JVM分配内存',
        ],
    ];
    /**
     * 全站搜索配置
     *  1. 开启定时更新
     *  2. 开启队列更新
     */
    const INIT_GLOBAL_SEARCH_CONFIG = [
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
            self::FIELDS_KEY => ConfigOptions::UPDATE_BY_SCHEDULE,
            self::FIELDS_VALUE => ConfigOptions::DEFAULT_UPDATE_BY_SCHEDULE,
            self::FIELDS_DEFAULT => ConfigOptions::DEFAULT_UPDATE_BY_SCHEDULE,
            self::FIELDS_REMARK => '是否开启定时更新',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
            self::FIELDS_KEY => ConfigOptions::UPDATE_BY_QUEUE,
            self::FIELDS_VALUE => ConfigOptions::DEFAULT_UPDATE_BY_QUEUE,
            self::FIELDS_DEFAULT => ConfigOptions::DEFAULT_UPDATE_BY_QUEUE,
            self::FIELDS_REMARK => '是否开启队列更新',
        ]
    ];
    /**
     * 全站搜索-schedule配置
     *  1. 定时更新周期
     *  2. 定时天更新时间
     *  3. 定时周更新时间
     */
    const INIT_GLOBAL_SEARCH_SCHEDULE_CONFIG = [
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE,
            self::FIELDS_KEY => ConfigOptions::SCHEDULE_UPDATE_PERIOD,
            self::FIELDS_VALUE => ConfigOptions::DEFAULT_SCHEDULE_UPDATE_PERIOD,
            self::FIELDS_DEFAULT => ConfigOptions::DEFAULT_SCHEDULE_UPDATE_PERIOD,
            self::FIELDS_REMARK => '定时更新周期:1 每天更新 2:每周更新',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE,
            self::FIELDS_KEY => ConfigOptions::SCHEDULE_UPDATE_DAY_TIME,
            self::FIELDS_VALUE => ConfigOptions::DEFAULT_SCHEDULE_UPDATE_DAY_TIME,
            self::FIELDS_DEFAULT => ConfigOptions::DEFAULT_SCHEDULE_UPDATE_DAY_TIME,
            self::FIELDS_REMARK => '定时更新每天更新时间',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE,
            self::FIELDS_KEY => ConfigOptions::SCHEDULE_UPDATE_WEEK_TIME,
            self::FIELDS_VALUE => ConfigOptions::SCHEDULE_UPDATE_WEEK_AT_SUNDAY,
            self::FIELDS_DEFAULT => ConfigOptions::SCHEDULE_UPDATE_WEEK_AT_SUNDAY,
            self::FIELDS_REMARK => '定时更新周几更新',
        ],
    ];
    /**
     * 全站搜索-queue配置
     *  1.  用户相关
     *  2.  客户相关
     *  3.  客户联系人相关
     *  4.  文档相关
     *  5.  邮件相关
     *  6.  流程相关
     *  7.  新闻相关
     *  8.  公告相关
     *  9.  人事档案相关
     *  10. 个人通讯录相关
     *  11. 个人通讯录相关
     *  12. 系统日志相关
     */
    const INIT_GLOBAL_SEARCH_QUEUE_CONFIG = [
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::USER_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新用户信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::CUSTOMER_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新客户信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::CUSTOMER_LINKMAN_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新客户联系人信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::DOCUMENT_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新文档信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::EMAIL_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新邮件信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::FLOW_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新流程信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::NEWS_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新新闻信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::NOTIFY_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新公告信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::PERSONNEL_FILES_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新人事档案信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::PUBLIC_ADDRESS_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新公告通讯录信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::PRIVATE_ADDRESS_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新个人通讯录信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::SYSTEM_LOG_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新系统日志相关信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::CUSTOMER_BUSINESS_CHANCE_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新业务机会相关',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::CUSTOMER_CONTRACT_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新客户合同相关信息',
        ],
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE,
            self::FIELDS_KEY => Constant::CUSTOMER_WILL_VISIT_CATEGORY,
            self::FIELDS_VALUE => false,
            self::FIELDS_DEFAULT => false,
            self::FIELDS_REMARK => '消息队列是否开启更新客户提醒相关信息',
        ],
    ];

    /**
     * 其他系统配置
     */
    const INIT_OTHER_CONFIG = [
        [
            self::FIELDS_TYPE => ConfigOptions::CONFIG_TYPE_SCHEMA_MAPPING,
            self::FIELDS_KEY => ConfigOptions::SCHEMA_ANALYZER,
            self::FIELDS_VALUE => ConfigOptions::SCHEMA_ANALYZER_IK_SMART,
            self::FIELDS_DEFAULT => ConfigOptions::SCHEMA_ANALYZER_IK_SMART,
            self::FIELDS_REMARK => 'schema映射版本控制',
        ],
    ];
}