<?php

return [
    'pagesize' => '10', //每页默认显示条数
    'flowChartPagesize' => '20', //流程图页面，每页默认显示条数
    'uploadDeniedExtensions' => ['html', 'htm', 'php', 'php2', 'php3', 'php4', 'php5', 'phtml', 'pwml', 'asp', 'aspx', 'ascx', 'jsp', 'cfm', 'cfc', 'pl', 'bat', 'exe', 'com', 'dll', 'vbs', 'js', 'reg', 'cgi', 'htaccess', 'asis', 'sh', 'shtml', 'shtm', 'phtm'], //放开inc文件 上传授权需要
    'uploadMaxSize' => '50000', //50M
    'uploadDeaultPath' => 'attachment', // 获取当前系统目录+eoffice-attachment  //jpg png bmp gif jpeg ico
    'uploadImages' => ['jpg', 'png', 'gif', 'jpeg','bmp','PNG','JPEG','GIF','JPG','BMP'],
    'uploadFileStatus' => [
        '1' => ['jpg', 'png', 'gif', 'jpeg','bmp','JPG','PNG','JPEG','GIF','BMP'],
        '2' => ['pdf'],
        '3' => ['doc', 'xls', 'ppt', 'docx', 'xlsx', 'pptx'],
        '4' => ['ogg', 'mp4', 'wmv', 'flv','avi','rmvb','rm','asf','divx','mpg','mpeg','mkv'],
        '5' => ['wps', 'wpt'],// wps插件使用
        '9' => [], //其他类型
    ],
    'attachmentDir' => envOverload('ATTACHMENT_DIR',''), //  1 D:/1123  D:/1123/
    'thumbWidth' => '360',
    'thumbHight' => '240',
    'thumbPrefix' => 'thumb_',
    'originalThumbPrefix'=>'thumb_original_', //压缩图片
    'combobox' => [
        'MARITAL_STATUS',
        'EDUCATIONAL',
        'POLITICAL_STATUS',
        'COMPANY_SCALE',
        'COMPANY_SCALE1',
        'CUSTOMER_TRADE',
        'CONTACT_TYPE',
        'BUSINESS_TYPE',
        'BUSINESS_SOURCE',
        'AGREEMENT_TYPE',
        'BUSINESS_STAGE',
        'CUSTOMER_SOURCE',
        'CUSTOMER_TYPE',
        'VEHICLE_TYPE',
        'DEPRECIATION_STAGE',
        'MAINTENANCE_TYPE',
        'MEETING_TYPE',
        'USE_TYPE', 'GET_TYPE', 'NEWS_TYPE', 'BUSINESS_STATUS', 'NOTIFY_TYPE', 'KHSX',
        'PROJECT_TYPE',     // 项目类型
        'PROJECT_DEGREE',   // 项目程度
        'PROJECT_PRIORITY', // 项目优先级
        'USER_POSITION',    // 用户职位
        'USER_AREA',        // 用户区域
        'USER_CITY',        // 用户城市
        'USER_WORKPLACE',   // 用户职场
        'USER_JOB_CATEGORY',// 用户岗位类别
        'CONTRACT_TYPE',
        'CONTRACT_PAY_WAY',
        'ARCHIVES_TYPE',    //案卷类型
        'PRODUCT_UNIT',    //产品单位

    ], //不能删除的下拉框
    'systemLogType' => [
        // 如果value为数组，则日志是单独的一个表；如果value是字符串，则是system_log表
        'login' => [
            'PC',           // pc登录
            'mobile',       // 手机登录
            'pwderror',     // 密码错误
            'ilip',
            'erroruname'    // 错误用户名
        ],
        'customer' => 'customer', // 客户模块信息
        'customer_linkman' => 'customer_linkman', // 客户联系人
        'document' => 'document', // 文档
        'notify'   => 'notify',   // 公告删除
//        'organizationalStructure' => 'organizationalStructure',
        'webhook' => ['add'],
        'user' => [    // 用户
            'add',
            'edit',
            'delete',
            'pwdchange'
        ],
        'department' => [ // 文档
            'add',
            'edit',
            'delete'
        ],
        'flow' => [       // 流程
            'outsend',
            'sunFlow',
            'defineFlowDelete',
            'UserReplace',   //操作名中不能有模块名字,如果有，flow第一个首字母也必须为大写，如sunFlow
            'quitUserReplace',
            'runFlowDelete',  //删除流程
            'initFlowRunSeq',  //日志记录流程run_id和流程初始的流水号
            'definedFlow',  //定义流程相关日志
        ],
        'charge' => 'charge', //删除费用记录日志
        'incomeexpense' => 'incomeexpense',  //收支删除记录
        'whiteListCheck' => 'whiteListCheck',  //收支删除记录,
        'contract_t' => 'contract_t'           // 合同操作日志
    ],
    //新建类菜单ID
    'createMenus' => [
        2, //新建流程
        7, //新建文档
        16, //新建邮件 手机版邮件列表
        501, //新建客户
        // 136, //新建方案
        161, //新建项目
        238, //新建新闻
        239, //新建公告
        364, //新建协作
        // 368, //新建相册
        123,
        124,
        418, //新建档案
        135, //新建记录
        266, //基本资料-- 办公用品列表
        80, //借阅管理
        601, //用车申请 手机版车辆列表
        701, //会议安排 手机版会议列表
//        19, //薪资录入
        42, //录入费用
        79, //图书信息录入管理
        922, //资产使用申请
    ],
    'webmail' => [
        'pop3Port' => '110',
        'smtpPort' => '25',
        'imapPort' => '143',
        'pop3PortSsl' => '995',
        'smtpPortSsl' => '465',
        'imapPortSsl' => '993',
    ],
    //自定义菜单类型
    "customMenuType" => ["web", "flow", "flowModeling", "document", "systemMenu", "singleSignOn", "favoritesMenu","customize","unifiedMessage"],
    "customDocumentSubMenuId" => [7, 8], //文档下的子菜单ID
    "customFlowSubMenuId" => [2, 3, 252, 323, 420, 5, 521], // 流程下面菜单
    //企业号默认跳转链接 ： 5-30（去掉26）走模块菜单
    'wechat' => [
        //其他type值 走模块ID 不走配置
        1 => '/home/profile/attendance', //签到签退--定点打卡
        2 => '/home/application', //应用中心
        3 => '/home/new', //新建中心
        4 => '/home/profile/outattendance', //移动考勤--上报位置
        18 => '/home/address-book/index', //通讯录
        31 => '/home/message/index',
        33 => '/customer/workwechat/customer-workwechat', //企业微信-客户卡片
        34 => '/home/profile/out-attendance' //外勤打卡
    ],
    //微信菜单配置 type值1-31
    'weixin' => [
        1 => 'weixin.Sign_in_sign_out',
        2 => 'weixin.Application_center',
        3 => 'weixin.New_center',
        4 => 'weixin.Go_out_attendance', //外出考勤
        5 => 'weixin.Approval_process', //流程审批
        6 => 'weixin.Document_center', //文档中心
        7 => 'weixin.Internal_mail', //内部邮件
        8 => 'weixin.Schedule_management', //日程管理
        9 => 'weixin.Attendance_management', //考勤管理
        10 => 'weixin.Charge_management', //费用管理
        11 => 'weixin.Customer_management', //客户管理
        12 => 'weixin.Performance_assessment', //绩效考核
        13 => 'weixin.Pay_center', //薪酬中心
        14 => 'weixin.Income_and_Expenses_Management', //收支管理
        //    15 => '外部邮件', //外部邮件
        16 => 'weixin.Project_management', //项目管理
        17 => 'weixin.Weibo_log', //微博日志
        18 => 'weixin.Address_Book', //通讯录
        19 => 'weixin.Book_information', //图书信息
        20 => 'weixin.News_management', //新闻管理
        21 => 'weixin.Office_Supplies', //办公用品
        22 => 'weixin.Notice_management', //公告管理
        23 => 'weixin.Collaboration_area', //协作区
        //    24 => '相册管理', //相册管理
        25 => 'weixin.Personnel_files', //人事档案
        //    26 => '假期管理', //假期管理
        27 => 'weixin.Task_management', //任务管理
        28 => 'weixin.Car_management', //用车管理
        29 => 'weixin.Meeting_management', //会议管理
        30 => 'weixin.Archives_management', //档案管理
        31 => 'weixin.My_message',
        32 => 'weixin.Fixed_assets', //资产管理
        33 => 'weixin.Customer_card', //客户卡片
        34 => 'weixin.Field_clock'  //客户卡片
    ],
    //模块接入时 验证是否授权
    'applistAuthId' => [
        // typeID => menu_parent 授权控制ID
        5 => '1', //待办流程
        6 => '6', //我的文档
        7 => '11', //我的邮件
        8 => '26', //我的日程
        9 => '32', //我的考勤
        10 => '37', //我的费用
        11 => '44', //客户信息
        12 => '82', //我的考核
        13 => '126', //我的薪资
        14 => '132', //收支记录
        15 => '140', //我的账号
        16 => '160', //我的项目
        17 => '189', //我的微博
        // 18 => '216', //通讯录  -- 不控制角色权限
        19 => '233', //图书查询
        20 => '237', //查看新闻
        21 => '264', //使用申请
        22 => '320', //查看公告
        23 => '362', //我的协作
        // 24 => '367', //我的相册
        25 => '415', //档案查询
        26 => '434', //假期管理
        27 => '530', //我的任务
        28 => '600', //我的用车
        29 => '700', //我的会议
        30 => '900', //卷库管理
        32 => '920', //资产管理
    ],
    'workwechat' => [
        // typeID => menu_id 授权控制ID
        5 => '3', //待办流程
        6 => '8', //我的文档
        7 => '24', //我的邮件
        8 => '141', //我的日程
        9 => '17', //我的考勤
        10 => '41', //我的费用
        11 => '502', //客户信息
        12 => '83', //我的考核
        13 => '319', //我的薪资
        14 => '133', //收支记录
        15 => '142', //我的账号
        16 => '162', //我的项目
        17 => '184', //我的微博
        // 18 => '23', //通讯录  -- 不控制角色权限
        19 => '35', //图书查询
        20 => '130', //查看新闻
        21 => '268', //使用申请
        22 => '131', //查看公告
        23 => '363', //我的协作
        // 24 => '367', //我的相册
        25 => '416', //档案查询
        26 => '435', //假期管理
        27 => '531', //我的任务
        28 => '602', //我的用车
        29 => '702', //我的会议
        30 => '901', //卷库管理
        32 => '921', //资产管理
    ],
    'trialVersion' => [
        'probation' => 30,
        'pcUserNumber' => 30000,
    ],
    // 'getAddressFromIpUrl' => 'http://int.dpool.sina.com.cn/iplookup/iplookup.php',
    'getAddressFromIpUrl' => 'http://whois.pconline.com.cn/ipJson.jsp',
    'reminds' => [
        'workwechat' => ['App\EofficeApp\WorkWechat\Services\WorkWechatService', 'showWorkWechat'],
        'dingtalk' => ['App\EofficeApp\Dingtalk\Services\DingtalkService', 'showDingtalk'],
        'wechat' => ['App\EofficeApp\Weixin\Services\WeixinService', 'showWechat'],
        'qyweixin' => ['App\EofficeApp\Qyweixin\Services\QyweixinService', 'showQyweixin'],
        'appPush' => ['App\EofficeApp\WorkWechat\Services\WorkWechatService', 'showAppPush'],
        'email' => ['App\EofficeApp\WorkWechat\Services\WorkWechatService', 'showEmail'],
        'shortMessage' => ['App\EofficeApp\System\ShortMessage\Services\ShortMessageService', 'showShortMessage'],
        'webMail' => ['App\EofficeApp\Webmail\Services\WebmailService', 'showWebEmail'],
        'dgwork' => ['App\EofficeApp\Dgwork\Services\DgworkService', 'showDgwork'],
    ],
    // 系统管理菜单及其子菜单ID
    'systemManageMenu' => [95,34,109,114,405,916,102,240,96,97,98,99,101,113,500,105,117,801,800,118,304,259,112,361,241,103,242,106,403,108],
    //系统安全白名单地址
    'systemSecurityWhiteAddress' => [
        'https://login.cloud.huawei.com/oauth2/v2/token',
        'https://api.push.hicloud.com/pushsend.do',
        'http://service.e-office.cn',
        'https://service.e-office.cn'
    ],
    // 'exceptIntegrationMenu' => [282,114,283,284,285,402,286,350,287,390,288,395,289,60,290,257,292,120,293,935,294,450,291,499,913],
    'exceptIntegrationMenu' => [351,352,114,391,392,396,397,61,430,431,432,433,914,915,119,121,936,451,452],
    'map_url' => [
        'amap' => [
            // 根据地址获取坐标
            'geo' => 'http://restapi.amap.com/v3/geocode/geo',
            // 根据坐标获取地址
            'regeo' => 'http://restapi.amap.com/v3/geocode/regeo',
            // 获取周边
            'around' => 'http://restapi.amap.com/v3/place/around'
        ],
        'google' => [
            // 国际访问
            'geo' => 'https://maps.googleapis.com/maps/api/geocode/json',
            // 周边
            'nearby' => 'https://maps.googleapis.com/maps/api/place/nearbysearch/json'
        ]
    ]
];
