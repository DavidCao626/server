<?php


namespace App\EofficeApp\Elastic\Configurations;

/**
 * 此处存储ES分类相关常量
 */
final class Constant
{
    // ===========================================================
    //                       通用
    // ===========================================================
    const ELASTIC_MENU_Id = 297;    // 模块id
    const ELASTIC_QUEUE = 'elasticsearch'; // es队列名
    const DEFAULT_QUEUE = 'default'; // 默认队列名称
    const DEFAULT_VERSION = self::V1; // 默认版本
    const V1 = 'v1'; // 使用ik_smart
    const V2 = 'v2'; // 使用ik_max_word
    const V3 = 'v3'; // 使用keyword分词器

    const COMMON_INDEX_TYPE = 'for_client';
    const ALIAS_PREFIX = 'eoffice_';    // 别名前缀

    const PLUG_STATUS = 'search_plug_status'; // 服务管理平台插件开启状态
    const PLUG_STATUS_OFF = 0; // 关闭
    const PLUG_STATUS_ON = 1; // 开启

    // ===========================================================
    //                       1.用户相关
    // ===========================================================
    const USER_CATEGORY = 'user';
    const USER_ALIAS = 'eoffice_user';
    const USER_INDEX_PREFIX = 'eoffice_user_v%d';
    const USER_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       2.客户相关
    // ===========================================================
    const CUSTOMER_CATEGORY = 'customer';
    const CUSTOMER_ALIAS = 'eoffice_customer';
    const CUSTOMER_INDEX_PREFIX = 'eoffice_customer_v%d';
    const CUSTOMER_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       3.客户联系人相关
    // ===========================================================
    const CUSTOMER_LINKMAN_CATEGORY = 'customer_linkman';
    const CUSTOMER_LINKMAN_ALIAS = 'eoffice_customer_linkman';
    const CUSTOMER_LINKMAN_INDEX_PREFIX = 'eoffice_customer_linkman_v%d';
    const CUSTOMER_LINKMAN_INDEX_TYPE = 'for_client';


    // ===========================================================
    //                       4.文档相关
    // ===========================================================
    const DOCUMENT_CATEGORY = 'document';
    const DOCUMENT_ALIAS = 'eoffice_document';
    const DOCUMENT_INDEX_PREFIX = 'eoffice_document_v%d';
    const DOCUMENT_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       5.邮件相关
    // ===========================================================
    const EMAIL_CATEGORY = 'email';
    const EMAIL_ALIAS = 'eoffice_email';
    const EMAIL_INDEX_PREFIX = 'eoffice_email_v%d';
    const EMAIL_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       6.流程相关
    // ===========================================================
    const FLOW_CATEGORY = 'flow';
    const FLOW_ALIAS = 'eoffice_flow';
    const FLOW_INDEX_PREFIX = 'eoffice_flow_v%d';
    const FLOW_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       7.新闻相关
    // ===========================================================
    const NEWS_CATEGORY = 'news';
    const NEWS_ALIAS = 'eoffice_news';
    const NEWS_INDEX_PREFIX = 'eoffice_news_v%d';
    const NEWS_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       8.公告相关
    // ===========================================================
    const NOTIFY_CATEGORY = 'notify';
    const NOTIFY_ALIAS = 'eoffice_notify';
    const NOTIFY_INDEX_PREFIX = 'eoffice_notify_v%d';
    const NOTIFY_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       9.人事档案相关
    // ===========================================================
    const PERSONNEL_FILES_CATEGORY = 'personnel_files';
    const PERSONNEL_FILES_ALIAS = 'eoffice_personnel_files';
    const PERSONNEL_FILES_INDEX_PREFIX = 'eoffice_personnel_files_v%d';
    const PERSONNEL_FILES_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       10.个人通讯录相关
    // ===========================================================
    const PRIVATE_ADDRESS_CATEGORY = 'address_private';
    const PRIVATE_ADDRESS_ALIAS = 'eoffice_address_private';
    const PRIVATE_ADDRESS_INDEX_PREFIX = 'eoffice_address_private_v%d';
    const PRIVATE_ADDRESS_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       11.公共通讯录相关
    // ===========================================================
    const PUBLIC_ADDRESS_CATEGORY = 'address_public';
    const PUBLIC_ADDRESS_ALIAS = 'eoffice_address_public';
    const PUBLIC_ADDRESS_INDEX_PREFIX = 'eoffice_address_public_v%d';
    const PUBLIC_ADDRESS_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       12.系统日相关
    // ===========================================================
    const SYSTEM_LOG_CATEGORY = 'system_log';
    const SYSTEM_LOG_ALIAS = 'eoffice_system_log';
    const SYSTEM_LOG_INDEX_PREFIX = 'eoffice_system_log_v%d';
    const SYSTEM_LOG_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       13.客户业务机会相关
    // ===========================================================
    const CUSTOMER_BUSINESS_CHANCE_CATEGORY = 'customer_business_chance';
    const CUSTOMER_BUSINESS_CHANCE_ALIAS = 'eoffice_customer_business_chance';
    const CUSTOMER_BUSINESS_CHANCE_INDEX_PREFIX = 'eoffice_customer_business_chance_v%d';
    const CUSTOMER_BUSINESS_CHANCE_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       14.客户合同相关
    // ===========================================================
    const CUSTOMER_CONTRACT_CATEGORY = 'customer_contract';
    const CUSTOMER_CONTRACT_ALIAS = 'eoffice_customer_contract';
    const CUSTOMER_CONTRACT_INDEX_PREFIX = 'eoffice_customer_contract_v%d';
    const CUSTOMER_CONTRACT_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       15.客户提醒相关
    // ===========================================================
    const CUSTOMER_WILL_VISIT_CATEGORY = 'customer_will_visit';
    const CUSTOMER_WILL_VISIT_ALIAS = 'eoffice_customer_will_visit';
    const CUSTOMER_WILL_VISIT_INDEX_PREFIX = 'eoffice_customer_will_visit_v%d';
    const CUSTOMER_WILL_VISIT_INDEX_TYPE = 'for_client';

    //                       16.客户联系记录
    // ===========================================================
    const CUSTOMER_CONTACT_RECORD_CATEGORY = 'customer_contact_record';
    const CUSTOMER_CONTACT_RECORD_ALIAS = 'eoffice_customer_contact_record';
    const CUSTOMER_CONTACT_RECORD_INDEX_PREFIX = 'eoffice_customer_contact_record%d';
    const CUSTOMER_CONTACT_RECORD_INDEX_TYPE = 'for_client';

    // ===========================================================
    //                       建议词相关
    // ===========================================================
    const SUGGESTIONS_CATEGORY = 'suggestion';
    // 建议词更新时，临时存储建议词的文件，日志文件
    const TMP_FILE = 'reindex_suggestions_tmp_file.txt';

    // ===========================================================
    //                       全站搜索属性
    // ===========================================================
    // 全站搜索全部索引分类(别名指向分类)
    public static $allIndices = [
        self::CUSTOMER_ALIAS => self::CUSTOMER_CATEGORY,
        self::CUSTOMER_LINKMAN_ALIAS => self::CUSTOMER_LINKMAN_CATEGORY,
        self::DOCUMENT_ALIAS => self::DOCUMENT_CATEGORY,
        self::EMAIL_ALIAS => self::EMAIL_CATEGORY,
        self::FLOW_ALIAS => self::FLOW_CATEGORY,
        self::NEWS_ALIAS => self::NEWS_CATEGORY,
        self::NOTIFY_ALIAS => self::NOTIFY_CATEGORY,
        self::PERSONNEL_FILES_ALIAS => self::PERSONNEL_FILES_CATEGORY,
        self::PRIVATE_ADDRESS_ALIAS => self::PRIVATE_ADDRESS_CATEGORY,
        self::PUBLIC_ADDRESS_ALIAS => self::PUBLIC_ADDRESS_CATEGORY,
        self::USER_ALIAS => self::USER_CATEGORY,
        self::CUSTOMER_BUSINESS_CHANCE_ALIAS => self::CUSTOMER_BUSINESS_CHANCE_CATEGORY,
        self::CUSTOMER_CONTRACT_ALIAS => self::CUSTOMER_CONTRACT_CATEGORY,
        self::CUSTOMER_WILL_VISIT_ALIAS => self:: CUSTOMER_WILL_VISIT_CATEGORY,
        self::CUSTOMER_CONTACT_RECORD_ALIAS => self:: CUSTOMER_CONTACT_RECORD_CATEGORY,
    ];

    // 全站搜索全部模块权限
    public static $allModels = [
        self::CUSTOMER_CATEGORY => 502, // 客户
        self::CUSTOMER_LINKMAN_CATEGORY => 503, // 客户联系人
        self::DOCUMENT_CATEGORY => 8,   // 文档
        self::EMAIL_CATEGORY => 0,  // 邮件
        self::FLOW_CATEGORY => 5,   // 流程
        self::NEWS_CATEGORY => 130, // 新闻
        self::NOTIFY_CATEGORY => 131,   // 公告
        self::PERSONNEL_FILES_CATEGORY => [415, 416],   // 认识档案
        self::PRIVATE_ADDRESS_CATEGORY => 23,   // 个人通讯录
        self::PUBLIC_ADDRESS_CATEGORY => 25,    // 公共通讯录
        self::USER_CATEGORY => 0,   // 用户
        self::SYSTEM_LOG_CATEGORY => 103,   // 系统日志
        self::CUSTOMER_BUSINESS_CHANCE_CATEGORY => 505, // 客户业务机会
        self::CUSTOMER_CONTRACT_CATEGORY => 506,    // 客户合同
        self::CUSTOMER_WILL_VISIT_CATEGORY=> 502,   // 客户提醒
        self::CUSTOMER_CONTACT_RECORD_CATEGORY=> 502,   // 客户联系记录
    ];
}