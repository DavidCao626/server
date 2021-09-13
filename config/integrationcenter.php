<?php
/**
 * 集成中心配置
 * 把集成中心添加子模块需要的配置项都迁移到这里面来，主要配置项：
 * 菜单id；排序；所属分类
 *
 * @author dingpeng
 *
 * @since  2020-06-03
 */
return [
    // 看板配置
    'module' => [
        // 小e集成
        '450' => [
            'order' => '1',
            'classify' => 'production',
        ],
        // 契约锁集成
        '935' => [
            'order' => '2',
            'classify' => 'production',
        ],
        // 微信公众号集成
        '499' => [
            'order' => '3',
            'classify' => 'production',
        ],
        // 企业微信集成
        '913' => [
            'order' => '4',
            'classify' => 'production',
        ],
        // 钉钉集成
        '120' => [
            'order' => '5',
            'classify' => 'production',
        ],
        // 签章插件
        '395' => [
            'order' => '6',
            'classify' => 'function',
        ],
        // CAS集成
        '350' => [
            'order' => '7',
            'classify' => 'function',
        ],
        // LDAP集成
        '390' => [
            'order' => '8',
            'classify' => 'function',
        ],
        // 短信平台集成
        '257' => [
            'order' => '9',
            'classify' => 'function',
        ],
        // 考勤机集成
        '60' => [
            'order' => '10',
            'classify' => 'function',
        ],
        // U8集成
        '285' => [
            'order' => '11',
            'classify' => 'production',
        ],
        // K3集成
        '286' => [
            'order' => '12',
            'classify' => 'production',
        ],
        // 外部数据库
        '282' => [
            'order' => '13',
            'classify' => 'function',
        ],
        // 文档插件
        '283' => [
            'order' => '14',
            'classify' => 'function',
        ],
        // Webhook
        '284' => [
            'order' => '15',
            'classify' => 'function',
        ],
        // 登录集成
        '402' => [
            'order' => '16',
            'classify' => 'function',
        ],
        // 地图插件
        '288' => [
            'order' => '17',
            'classify' => 'function',
        ],
        // 统一消息
        '287' => [
            'order' => '18',
            'classify' => 'production',
        ],
        // 高拍仪集成
        '289' => [
            'order' => '19',
            'classify' => 'production',
        ],
        // OCR接口集成
        '291' => [
            'order' => '20',
            'classify' => 'function',
        ],
        // openAPI
        '290' => [
            'order' => '21',
            'classify' => 'function',
        ],
        // 视频会议
        '292' => [
            'order' => '22',
            'classify' => 'production',
        ],
        // 发票云集成
        '294' => [
            'order' => '24',
            'classify' => 'production'
        ],
        // Elasticsearch
        '297' => [
            'order' => '27',
            'classify' => 'function'
        ],
    ]
];