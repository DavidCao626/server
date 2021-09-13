<?php

return [
    'common' => [
        'web' => ['common'],
        'mobile' => ['common'],
        'server' => ['common', 'export', 'import', 'province', 'upload', 'validation']
    ],
    'app' => [
        'web' => ['mobile'],
        'server' => ['mobile']
    ],
    'address' => [
        'web' => ['address'],
        'server' => ['address']
    ],
    'album' => [
        'web' => ['album'],
        'server' => ['photoalbum']
    ],
    'archives' => [
        'web' => ['archives'],
        'mobile' => ['archives'],
        'server' => ['archives']
    ],
    'attendance' => [
        'web' => ['attendance'],
        'mobile' => ['attendance'],
        'server' => ['attendance']
    ],
    'book' => [
        'web' => ['book'],
        'mobile' => ['book'],
        'server' => ['book']
    ],
    'calendar' => [
        'web' => ['calendar'],
        'mobile' => ['calendar'],
        'server' => ['calendar']
    ],
    'charge' => [
        'web' => ['charge'],
        'mobile' => ['charge'],
        'server' => ['charge']
    ],
    'component' => [
        'web' => ['component'],
        'mobile' => ['component']
    ],
    'contract' => [
        'web' => ['contract'],
        'mobile' => ['contract'],
        'server' => ['contract'],
    ],
    'cooperation' => [
        'web' => ['cooperation'],
        'mobile' => ['cooperation'],
        'server' => ['cooperation']
    ],
    'customer' => [
        'web' => ['customer'],
        'mobile' => ['customer'],
        'server' => ['customer']
    ],
    'diary' => [
        'web' => ['diary'],
        'mobile' => ['diary'],
        'server' => ['diary']
    ],
    'dingtalk' => [
        'web' => ['dingtalk'],
        'server' => ['dingtalk']
    ],
    'document' => [
        'web' => ['document'],
        'mobile' => ['document'],
        'server' => ['document']
    ],
    'email' => [
        'web' => ['email'],
        'mobile' => ['email'],
        'server' => ['email']
    ],
    'webmail' => [
        'web' => ['webmail'],
        'mobile' => ['webmail'],
        'server' => ['webmail']
    ],
    'eui' => [
        'web' => ['eui'],
        'mobile' => ['emui']
    ],
    'flow' => [
        'web' => ['flow', 'form-parse'],
        'mobile' => ['flow', 'form-parse'],
        'server' => ['flow', 'outsend']
    ],
    'homePage' => [
        'web' => ['home-page','person-center'],
        'mobile' => ['home-page']
    ],
    'incomeexpense' => [
        'web' => ['incomeexpense'],
        'mobile' => ['incomeexpense'],
        'server' => ['incomeexpense']
    ],
    'lang' => [
        'web' => ['lang'],
        'server' => ['lang']
    ],
    'login' => [
        'web' => ['login'],
        'mobile' => ['login'],
        'server' => ['auth', 'register']
    ],
    'meeting' => [
        'web' => ['meeting'],
        'mobile' => ['meeting'],
        'server' => ['meeting']
    ],
    'news' => [
        'web' => ['news'],
        'mobile' => ['news'],
        'server' => ['news']
    ],
    'notify' => [
        'web' => ['notify'],
        'mobile' => ['notify'],
        'server' => ['notify']
    ],
    'officesupplies' => [
        'web' => ['office-supplies'],
        'mobile' => ['office-supplies'],
        'server' => ['officesupplies']
    ],
    'performance' => [
        'web' => ['performance'],
        'mobile' => ['performance'],
        'server' => ['performance']
    ],
    'personalset' => [
        'web' => ['personalset'],
        'server' => ['passwords','personalset']
    ],
    'personnelfiles' => [
        'web' => ['personnel-files'],
        'mobile' => ['personnel-files'],
        'server' => ['personnelFiles']
    ],
    'portal' => [
        'web' => ['portal'],
        'server' => ['portal']
    ],
    'product' => [
        'web' => ['product'],
        'mobile' => ['product'],
        'server' => ['product']
    ],
    'project' => [
        'web' => ['project'],
        'mobile' => ['project'],
        'server' => ['project']
    ],
    'report' => [
        'web' => ['report'],
        'mobile' => ['report'],
        'server' => ['report']
    ],
    'salary' => [
        'web' => ['salary'],
        'mobile' => ['salary'],
        'server' => ['salary']
    ],
    'shortMessage' => [
        'web' => ['short-message'],
        'server' => ['sms']
    ],
    'storage' => [
        'web' => ['storage'],
        'mobile' => ['storage'],
        'server' => ['storage']
    ],
    'system' => [
        'web' => ['system'],
        'server' => ['birthday',
            'combobox',
            'company',
            'department',
            'fields',
            'iprules',
            'public_group',
            'role',
            'security',
            'sms',
            'sso',
            'system',
            'systemlog',
            'system_sms',
            'systemmailbox',
            'tag',
            'template',
            'user',
            'webhook',
            'menu'
        ]
    ],
    'task' => [
        'web' => ['task'],
        'mobile' => ['task'],
        'server' => ['task']
    ],
    'vacation' => [
        'web' => ['vacation'],
        'server' => ['vacation']
    ],
    'vehicles' => [
        'web' => ['vehicles'],
        'mobile' => ['vehicles'],
        'server' => ['vehicles']
    ],
    'vote' => [
        'web' => ['vote'],
        'mobile' => ['vote'],
        'server' => ['vote']
    ],
    'weixin' => [
        'web' => ['weixin', 'work-wechat'],
        'server' => ['qyweixin', 'weixin', 'workwechat']
    ],
    'cas' => [
        'web' => ['cas'],
        'server' => ['cas']
    ],
    'ladp' => [
        'web' => ['domain'],
        'server' => ['domain']
    ],
    'flowmodeling' => [
        'web' => ['flow-modeling'],
        'server' => ['flowmodeling']
    ],
    'formModeling' => [
        'web' => ['form-modeling'],
        'server' => ['fields']
    ],
    'client' => [
        'server' => ['client']
    ],
    'servermanage' => [
        'web' => ['servermanage']
    ],
    'electronicsign' => [
        'web' => ['electronic-sign'],
        'mobile' => ['electronic-sign'],
        'server' => ['electronicsign']
    ],
    'xiaoe' => [
        'web' => ['xiaoe'],
        'mobile' => ['xiaoe'],
        'server' => ['xiaoe']
    ],
    'assets'=> [
        'web' => ['assets'],
        'mobile' => ['assets'],
        'server' => ['assets']
    ],
    'registers' => [
        'web'    => ['registers'],
        'server' => ['register']
    ],
    'invoice' => [
        'web' => ['invoice'],
        'mobile' => ['invoice'],
        'server' => ['invoice']
    ],
    'integrationCenter' => [
        'web' => ['integration-center','todo-push'],
        'server' => ['integrationCenter']
    ],
    'openApi' => [
        'web' => ['open-api'],
        'server' => ['openApi']
    ],
    'unifiedMessage' => [
        'web' => ['unified-message'],
        'mobile' => ['unified-message'],
        'server' => ['unifiedMessage']
    ],
    'dgwork' => [
        'web'    => ['dgwork'],
        'server' => ['dgwork']
    ],
    'elastic' => [
        'web'    => ['elastic'],
        'server' => ['elastic']
    ],
    'logcenter' => [
        'web' => ['log-center'],
        'mobile' => [],
        'server' => ['logcenter']
    ],
];
