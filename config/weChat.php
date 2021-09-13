<?php

return [
    'workWeChatTokenDuration' => 7000,
    'workWeChatApi' => [
        'sendAgentMessage' => 'https://qyapi.weixin.qq.com/cgi-bin/message/send',
        'getToken' => 'https://qyapi.weixin.qq.com/cgi-bin/gettoken',
        //获取部门列表
        'departmentList' => 'https://qyapi.weixin.qq.com/cgi-bin/department/list',
        'userList' => 'https://qyapi.weixin.qq.com/cgi-bin/user/list',
        'userInfo' => 'https://qyapi.weixin.qq.com/cgi-bin/user/get',
        'addDept' => 'https://qyapi.weixin.qq.com/cgi-bin/department/create',
        'updateDept' => 'https://qyapi.weixin.qq.com/cgi-bin/department/update',
        'deleteDept' => 'https://qyapi.weixin.qq.com/cgi-bin/department/delete',
        'addUser' => 'https://qyapi.weixin.qq.com/cgi-bin/user/create',
        'updateUser' => 'https://qyapi.weixin.qq.com/cgi-bin/user/update',
        'deleteUser' => 'https://qyapi.weixin.qq.com/cgi-bin/user/delete',
        //获取打卡数据
        'getCheckInData' => 'https://qyapi.weixin.qq.com/cgi-bin/checkin/getcheckindata',
        'getCorpCheckinOption' => 'https://qyapi.weixin.qq.com/cgi-bin/checkin/getcorpcheckinoption',
        //批量获取客户详情
        'externalContact' => 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/batch/get_by_user',
        //获取客户群列表
        'groupChatList' => 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/list',
        //获取客户群详情
        'groupChatDetail' => 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/get',
        //获取客户详情
        'externalContactDetail' => 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get',
    ],
    'workWeChatLeaveDept' => '离职部门',
    'workWeChatUserField' => [
        'wapAllow' => '手机访问',
        'wapAllowYes' => '允许',
        'wapAllowNo' => '不允许',
        'userStatus' => '用户状态',
        'attendanceScheduling'=>'考勤排班类型',
        'userArea'=>'区域',
        'userPosition'=>'职位',
        'userCity'=>'城市',
        'userWorkplace'=>'职场',
        'userJobCategory'=>'岗位类别',
        'birthday'=>'生日',
        'oicqNo'=>'QQ',
        'weixin'=>'微信号',
        'faxes'=>'单位传真',
        'homeZipCode'=>'家庭邮编',
        'homePhoneNumber'=>'家庭电话',
        'notes'=>'备注',
        'userJobNumber'=>'工号',
    ],
    'workWeChatRemindAgentBind' =>[
        // 新闻 => ['新闻发布'、"新闻收回"]
        // news 、notice、flow、email、meeting、salary、schedule、car、office_supplies、document、cooperation、personnel_files、user、performance、diary、project、customer
        // task、vote、contract、webmail、cas、attendancemachine、assets、birthday、book、dingtalk、attendance、heterogeneous_1、storage、domain、database、invoice、vacation
        1 => [],
        2 => ['schedule','user','vote','contract','webmail','cas','birthday','dingtalk','storage','domain','database','invoice'],
        3 => [],
        4 => [],
        5 => ['flow'],
        6 => ['document'],
        7 => ['email'],
        9 => ['attendancemachine','attendance','vacation'],
        10 => [],
        11 => ['customer'],
        12 => ['performance'],
        13 => ['salary'],
        14 => [],
        15 => [],
        16 => ['project'],
        17 => ['diary'],
        18 => [],
        19 => ['book'],
        20 => ['news'],
        21 => ['office_supplies'],
        22 => ['notice'],
        23 => ['cooperation'],
        24 => [],
        25 => ['personnel_files'],
        26 => [],
        27 => ['task'],
        28 => ['car'],
        29 => ['meeting'],
        30 => [],
        31 => ['im'],
        32 => ['assets']
    ]
];
