<?php


namespace App\EofficeApp\Elastic\Configurations;


final class RedisKey
{
    // 搜索预配置相关
    const REDIS_CONFIG_TERMS_USER_PREFIX = 'ELASTIC:CONFIG:TERMS:USER:';    // redis中用户配置的key的前缀
    const REDIS_CONFIG_USER_CYCLE = 300;                       // redis中用户配置的key生存周期为300s

    // 定时任务相关
    const REDIS_SCHEDULE_CONFIG = 'ELASTIC:SCHEDULE:CONFIG';    // 定时任务相关配置

    // 队列更新
    const REDIS_UPDATE = 'ELASTIC:UPDATE:QUEUE';

    // 服务管理平台相关
    const REDIS_PLUG_IN_STATUS = 'ELASTIC:PLUG:IN:STATUS'; // 服务管理平台开启es插件
}