<?php
/**
 * Elastic相关配置
 */

return [
    /**
     * ES服务器配置
     */
    'elasticsearch' => [
        // Elasticsearch 支持多台服务器负载均衡，因此这里是一个数组
        'hosts' => envOverload('ES_HOSTS', 'localhost'),
    ],

    /**
     * 各索引对应查询字段
     */
    'category_multi_fields' => [
        'user' => [
            'user_name', 'user_position_name', 'mobile', 'home_phone', 'dept_phone', 'address', 'email', 'department.name', 'attachment.content', 'attachment.attachment_name'
        ],
        'customer' => [
            'customer_name', 'customer_number', 'phone_number' ,'attachment.content', 'attachment.attachment_name'
        ],
        'customer_linkman' => [
            'mobile_phone_number', 'linkman_name', 'address', 'email', 'linkman_remarks' ,'attachment.content', 'attachment.attachment_name'
        ],
        'news' => [
            'title', 'news_desc', 'content' ,'attachment.content', 'attachment.attachment_name'
        ],
        'document' => [
            'subject', 'content' ,'attachment.content', 'attachment.attachment_name'
        ],
        'email' => [
            'subject', 'content' ,'attachment.content', 'attachment.attachment_name'
        ],
        'flow' => [
            'run_name', 'run_seq_strip_tags' ,'attachment.content', 'attachment.attachment_name'
        ],
        'address_public' => [
            'name', 'phone', 'email', 'serial_number' ,'attachment.content', 'attachment.attachment_name'
        ],
        'address_private' => [
            'name', 'phone', 'email', 'serial_number' ,'attachment.content', 'attachment.attachment_name'
        ],
        'personnel_files' => [
            'user_name', 'no', 'home_tel', 'email' ,'attachment.content', 'attachment.attachment_name'
        ],
        'notify' => [
            'subject', 'content' ,'attachment.content', 'attachment.attachment_name'
        ],
        'system_log' => [
            'log_content', 'log_ip', 'log_creator' ,'attachment.content', 'attachment.attachment_name'
        ],
        'customer_business_chance' => [
            'chance_name', 'chance_remarks', 'affiliated_customers'
        ],
        'customer_contract' => [
            'contract_name', 'contract_remarks', 'affiliated_customers','attachment.content', 'attachment.attachment_name'
        ],
        'customer_will_visit' => [
            'linkman_name', 'visit_content', 'affiliated_customers','creator_name'
        ],
        'customer_contact_record' => [
            'linkman_name', 'record_content', 'affiliated_customers','creator_name','attachment.content','attachment.attachment_name'
        ],
    ],

    /**
     * 搜索时模块 terms 过滤白名单
     */
    'view_white_terms_list' => [
//        'personnel_files'   // 人事档案拥有模块访问权限即可查看
    ],

    /**
     * 搜索时非空字段过滤 exists
     */
    'view_white_exists_list' => [
        'user' => 'user_accounts'        // 注销用户(user_accounts为空) 无法访问
    ],

    /**
     * 搜索黑名单控制
     */
    'view_black_list' => [],

    /**
     * 日志中心相关配置
     *
     */
    'logCenter' =>[
        'index' => 'log_all' ,
        'type'  => 'doc' ,
        'tablePrefix' => 'eo_log_',
        'max_result_window' => 100000
    ]
];