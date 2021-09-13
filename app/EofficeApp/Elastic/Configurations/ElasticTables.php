<?php


namespace App\EofficeApp\Elastic\Configurations;

/**
 * ES相关表名统一定义
 */
final class ElasticTables
{
    /**
     * 表名
     */
    const ELASTIC_CONFIG_TABLE = 'elastic_search_config';  // ES配置表
    const ELASTIC_UPDATE_LOG_TABLE = 'elastic_search_update_log';  // ES索引更新记录表
    const ELASTIC_OPERATION_LOG_TABLE = 'elastic_search_operation_log';  // ES配置操作记录表
    const ELASTIC_DIC_EXTENSION_TABLE = 'elastic_dic_extension';  // ES扩展词词典记录表
    const ELASTIC_DIC_SYNONYM_TABLE = 'elastic_dic_synonym';  // ES同义词词典记录表
    const ATTACHMENT_CONTENT_TABLE = 'attachment_content';  // 附件内容表
    const ELASTIC_STASH_INDEX_TABLE = 'elastic_stash_index'; // 索引临时贮存表

    /**
     * 操作
     */
    const OPERATION_ADD = 'add';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    const OPERATION_RESTORE = 'restore';
}