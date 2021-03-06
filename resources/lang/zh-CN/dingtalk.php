<?php

return [
    '0x120001' => '请求异常',
    '0x120002' => ' 钉钉服务器未响应',
    '0x120003' => ' 应用列表数据异常',
    '0x120004' => ' 不是合法域名',
    '0x120005' => '不支持内网IP',
    '0x120006' => '查不到相关用户，拒绝访问',
    '0x120007' => '位置信息异常',
    '0x120008' => '高德服务器返回异常，请稍后重试',
    '0x120009' => '获取钉钉应用配置信息错误，请检查',
    '0x120010' => '无法识别用户，请检查钉钉和系统手机号匹配',
    '0x120011' => '警告，没有权限访问！',
    '0x000111' => '获取结果错误',
    '0x000112' => '没有开启curi_init',
    '0x000113' => '其他异常',
    '-1' => '系统繁忙',
    '404' => '请求的URI地址不存在',
    '33001' => '无效的企业ID',
    '33002' => '无效的微应用的名称',
    '33003' => '无效的微应用的描述',
    '33004' => '无效的微应用的ICON',
    '33005' => '无效的微应用的移动端主页',
    '33006' => '无效的微应用的PC端主页',
    '33007' => '微应用的移动端的主页与PC端主页不同',
    '33008' => '无效的微应用OA后台的主页',
    '34001' => '无效的会话id',
    '34002' => '无效的会话消息的发送者',
    '34003' => '无效的会话消息的发送者的企业Id',
    '34004' => '无效的会话消息的类型',
    '34005' => '无效的会话音频消息的播放时间',
    '34006' => '发送者不在企业中',
    '34007' => '发送者不在会话中',
    '34008' => '图片不能为空',
    '34009' => '链接内容不能为空',
    '34010' => '文件不能为空',
    '34011' => '音频文件不能为空',
    '34012' => '找不到发送者的企业',
    '34013' => '找不到群会话对象',
    '34014' => '会话消息的json结构无效或不完整',
    '40001' => '获取access_token时Secret错误，或者access_token无效',
    '40002' => '不合法的凭证类型',
    '40003' => '不合法的UserID',
    '40004' => '不合法的媒体文件类型',
    '40005' => '不合法的文件类型',
    '40006' => '不合法的文件大小',
    '40007' => '不合法的媒体文件id',
    '40008' => '不合法的消息类型',
    '40009' => '不合法的部门id',
    '40010' => '不合法的父部门id',
    '40011' => '不合法的排序order',
    '40012' => '不合法的发送者',
    '40013' => '不合法的corpid',
    '40014' => '不合法的access_token',
    '40015' => '发送者不在会话中',
    '40016' => '不合法的会话ID',
    '40017' => '在会话中没有找到与发送者在同一企业的人',
    '40018' => '不允许以递归方式查询部门用户列表',
    '40019' => '该手机号码对应的用户最多可以加入5个非认证企业',
    '40020' => '当前团队人数已经达到上限,用电脑登录钉钉企业管理后台，升级成为认证企业',
    '40021' => '更换的号码已注册过钉钉，无法使用该号码',
    '40022' => '企业中的手机号码和登录钉钉的手机号码不一致,暂时不支持修改用户信息,可以删除后重新添加',
    '40023' => '部门人数达到上限',
    '40024' => '(安全校验不通过)保存失败，团队人数超限。请在手机钉钉绑定支付宝完成实名认证，或者申请企业认证，人数上限自动扩充。',
    '40025' => '无效的部门JSONArray对象,合法格式需要用中括号括起来,且如果属于多部门,部门id需要用逗号分隔',
    '60107' => '使用该手机登录钉钉的用户已经在企业中',
    '40029' => '不合法的oauth_code',
    '40031' => '不合法的UserID列表',
    '40032' => '不合法的UserID列表长度',
    '40033' => '不合法的请求字符，不能包含\uxxxx格式的字符',
    '40035' => '不合法的参数',
    '40038' => '不合法的请求格式',
    '40039' => '不合法的URL长度',
    '40048' => 'url中包含不合法domain',
    '40055' => '不合法的agent结构',
    '40056' => '不合法的agentid',
    '40057' => '不合法的callbackurl',
    '40061' => '设置应用头像失败',
    '40062' => '不合法的应用模式',
    '40063' => '不合法的分机号',
    '40064' => '不合法的工作地址',
    '40065' => '不合法的备注',
    '40066' => '不合法的部门列表',
    '40067' => '标题长度不合法',
    '40068' => '不合法的偏移量',
    '40069' => '不合法的分页大小',
    '40070' => '不合法的排序参数',
    '40073' => '不存在的openid',
    '40077' => '不存在的预授权码',
    '40078' => '不存在的临时授权码',
    '40079' => '不存在的授权信息',
    '40080' => '不合法的suitesecret',
    '40082' => '不合法的suitetoken',
    '40083' => '不合法的suiteid',
    '40084' => '不合法的永久授权码',
    '40085' => '不存在的suiteticket',
    '40086' => '不合法的第三方应用appid',
    '40087' => '创建永久授权码失败',
    '40088' => '不合法的套件key或secret',
    '40089' => '不合法的corpid或corpsecret',
    '40090' => '套件已经不存在',
    '40091' => '用户授权码创建失败,需要用户重新授权',
    '41001' => '缺少access_token参数',
    '41002' => '缺少corpid参数',
    '41003' => '缺少refresh_token参数',
    '41004' => '缺少secret参数',
    '41005' => '缺少多媒体文件数据',
    '41006' => '缺少media_id参数',
    '41007' => '无效的ssocode',
    '41008' => '缺少oauth',
    '41009' => '缺少UserID',
    '41010' => '缺少url',
    '41011' => '缺少agentid',
    '41012' => '缺少应用头像mediaid',
    '41013' => '缺少应用名字',
    '41014' => '缺少应用描述',
    '41015' => '缺少Content',
    '41016' => '缺少标题',
    '41021' => '缺少suitekey',
    '41022' => '缺少suitetoken',
    '41023' => '缺少suiteticket',
    '41024' => '缺少suitesecret',
    '41025' => '缺少permanent_code',
    '41026' => '缺少tmp_auth_code',
    '41027' => '需要授权企业的corpid参数',
    '41028' => '禁止给全员发送消息',
    '41029' => '超过消息接收者人数上限',
    '41030' => '企业未对该套件授权',
    '41031' => 'auth_corpid和permanent_code不匹配',
    '41044' => '禁止发送消息',
    '41045' => '超过发送全员消息的次数上限',
    '41046' => '超过发送全员消息的每分钟次数上限',
    '41047' => '超过给该企业发消息的每分钟次数上限',
    '41048' => '超过给企业发消息的每分钟次数总上限',
    '41049' => '包含违禁内容',
    '41050' => '无效的活动编码',
    '41051' => '活动权益的校验失败',
    '41100' => '时间参数不合法',
    '41101' => '数据内容过长',
    '41102' => '参数值过大',
    '42001' => 'access_token超时',
    '42002' => 'refresh_token超时',
    '42003' => 'oauth_code超时',
    '42007' => '预授权码失效',
    '42008' => '临时授权码失效',
    '42009' => 'suitetoken失效',
    '43001' => '需要GET请求',
    '43002' => '需要POST请求',
    '43003' => '需要HTTPS',
    '43004' => '无效的HTTP HEADER Content-Type',
    '43005' => '需要Content-Type为application/json;charset=UTF-8',
    '43007' => '需要授权',
    '43008' => '参数需要multipart类型',
    '43009' => 'post参数需要json类型',
    '43010' => '需要处于回调模式',
    '43011' => '需要企业授权',
    '44001' => '多媒体文件为空',
    '44002' => 'POST的数据包为空',
    '44003' => '图文消息内容为空',
    '44004' => '文本消息内容为空',
    '45001' => '多媒体文件大小超过限制',
    '45002' => '消息内容超过限制',
    '45003' => '标题字段超过限制',
    '45004' => '描述字段超过限制',
    '45005' => '链接字段超过限制',
    '45006' => '图片链接字段超过限制',
    '45007' => '语音播放时间超过限制',
    '45008' => '图文消息超过限制',
    '45009' => '接口调用超过限制',
    '45016' => '系统分组，不允许修改',
    '45017' => '分组名字过长',
    '45018' => '分组数量超过上限',
    '45024' => '账号数量超过上限',
    '46001' => '不存在媒体数据',
    '46004' => '不存在的员工',
    '47001' => '解析JSON/XML内容错误',
    '48002' => 'Api禁用',
    '48003' => 'suitetoken无效',
    '48004' => '授权关系无效',
    '49000' => '缺少chatid',
    '49001' => '绑定的微应用超过个数限制',
    '49002' => '一个群只能被一个ISV套件绑定一次',
    '49003' => '操作者必须为群主',
    '49004' => '添加成员列表和删除成员列表不能有交集',
    '49005' => '群人数超过人数限制',
    '49006' => '群成员列表必须包含群主',
    '49007' => '超过创建群的个数上限',
    '50001' => 'redirect_uri未授权',
    '50002' => '员工不在权限范围',
    '50003' => '应用已停用',
    '50005' => '企业已禁用',
    '51000' => '跳转的域名未授权',
    '51001' => '跳转的corpid未授权',
    '51002' => '跳转请求不是来自钉钉客户端',
    '51003' => '跳转找不到用户信息',
    '51004' => '跳转找不到用户的企业信息',
    '51005' => '跳转用户不是企业管理员',
    '51006' => '跳转生成code失败',
    '51007' => '跳转获取用户企业身份失败',
    '51008' => '跳转url解码失败',
    '51009' => '要跳转的地址不是标准url',
    '52010' => '无效的corpid',
    '52011' => 'jsapi ticket 读取失败',
    '52012' => 'jsapi 签名生成失败',
    '52013' => '签名校验失败',
    '52014' => '无效的url参数',
    '52015' => '无效的随机字符串参数',
    '52016' => '无效的签名参数',
    '52017' => '无效的jsapi列表参数',
    '52018' => '无效的时间戳',
    '52019' => '无效的agentid',
    '60001' => '不合法的部门名称',
    '60002' => '部门层级深度超过限制',
    '60003' => '部门不存在',
    '60004' => '父亲部门不存在',
    '60005' => '不允许删除有成员的部门',
    '60006' => '不允许删除有子部门的部门',
    '60007' => '不允许删除根部门',
    '60008' => '父部门下该部门名称已存在',
    '60009' => '部门名称含有非法字符',
    '60010' => '部门存在循环关系',
    '60011' => '管理员权限不足，（user/department/agent）无权限',
    '60012' => '不允许删除默认应用',
    '60013' => '不允许关闭应用',
    '60014' => '不允许开启应用',
    '60015' => '不允许修改默认应用可见范围',
    '60016' => '部门id已经存在',
    '60017' => '不允许设置企业',
    '60018' => '不允许更新根部门',
    '60019' => '从部门查询人员失败',
    '60020' => '访问ip不在白名单之中',
    '60066' => '企业的设置不存在',
    '60067' => '部门的企业群群主不存在',
    '60068' => '部门的管理员不存在',
    '60102' => 'UserID在公司中已存在',
    '60103' => '手机号码不合法',
    '60104' => '手机号码在公司中已存在',
    '60105' => '邮箱不合法',
    '60106' => '邮箱已存在',
    '60107' => '使用该手机登录钉钉的用户已经在企业中',
    '60110' => '部门个数超出限制',
    '60111' => 'UserID不存在',
    '60112' => '用户name不合法',
    '60113' => '身份认证信息（手机/邮箱）不能同时为空',
    '60114' => '性别不合法',
    '60118' => '用户无有效邀请字段（邮箱，手机号）',
    '60119' => '不合法的position',
    '60120' => '用户已禁用',
    '60121' => '找不到该用户',
    '60122' => '不合法的extattr',
    '60123' => '不合法的jobnumber',
    '60124' => '用户不在此群中',
    '60125' => 'CRM配置信息创建失败',
    '60126' => 'CRM配置信息更新失败',
    '60127' => 'CRM人员配置信息删除失败',
    '70001' => '企业不存在或者已经被解散',
    '70002' => '获取套件下的微应用失败',
    '70003' => 'agentid对应微应用不存在',
    '70004' => '企业下没有对应该agentid的微应用',
    '70005' => 'ISV激活套件失败',
    '71006' => '回调地址已经存在',
    '71007' => '回调地址已不存在',
    '71008' => '回调call_back_tag必须在指定的call_back_tag列表中',
    '71009' => '返回文本非success',
    '71010' => 'POST的JSON数据不包含所需要的参数字段或包含的参数格式非法',
    '71011' => '传入的url参数不是合法的url格式',
    '71012' => 'url地址访问异常,错误原因为:%s',
    '71013' => '此域名或IP不能注册或者接收回调事件',
    '72001' => '获取钉盘空间失败',
    '72002' => '授权钉盘空间访问权限失败',
    '80001' => '可信域名没有IPC备案，后续将不能在该域名下正常使用jssdk',
    '81001' => '两个用户没有任何关系，请先相互成为好友',
    '81002' => '用户拒收消息',
    '88005' => '管理日历个人日历操作失败',
    '89001' => '管理日历启动导出任务失败',
    '89011' => '管理日历写入数据失败',
    '89012' => '管理日历更新数据失败',
    '90001' => '您的服务器调用钉钉开放平台所有接口的请求都被暂时禁用了',
    '90002' => '您的服务器调用钉钉开放平台当前接口的所有请求都被暂时禁用了',
    '90003' => '您的企业调用钉钉开放平台所有接口的请求都被暂时禁用了,仅对企业自己的Accesstoken有效',
    '90004' => '您当前使用的CorpId及CorpSecret被暂时禁用了,仅对企业自己的Accesstoken有效',
    '90005' => '您的企业调用当前接口次数过多,请求被暂时禁用了,仅对企业自己的Accesstoken有效',
    '90006' => '您当前使用的CorpId及CorpSecret调用当前接口次数过多,请求被暂时禁用了,仅对企业自己的Accesstoken有效',
    '90007' => '您当前要调用的企业的接口次数过多,对该企业的所有请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '90008' => '您当前要调用的企业的当前接口次数过多,对此企业下该接口的所有请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '90009' => '您调用企业接口超过了限制,对所有企业的所有接口的请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '90010' => '您调用企业当前接口超过了限制,对所有企业的该接口的请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '90011' => '您的套件调用企业接口超过了限制,该套件的所有请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '90012' => '您的套件调用企业当前接口超过了限制,该套件对此接口的所有请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '90013' => '您的套件调用当前企业的接口超过了限制,该套件对此企业的所有请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '90014' => '您的套件调用企业当前接口超过了限制,该套件对此企业该接口的所有请求都被暂时禁用了,仅对企业授权给ISV的Accesstoken有效',
    '900001' => '加密明文文本非法',
    '900002' => '加密时间戳参数非法',
    '900003' => '加密随机字符串参数非法',
    '900004' => '不合法的aeskey',
    '900005' => '签名不匹配',
    '900006' => '计算签名错误',
    '900007' => '计算加密文字错误',
    '900008' => '计算解密文字错误',
    '900009' => '计算解密文字长度不匹配',
    '900010' => '计算解密文字corpid不匹配',
    '420001' => '客户不存在',
    '420002' => '客户查询失败',
    '420003' => '联系人不存在',
    '420004' => '联系人查询失败',
    '420005' => '客户删除失败',
    '420006' => '联系人删除失败',
    '420007' => '跟进人绑定失败',
    '420008' => '客户id非法',
    '420009' => '跟进人id非法',
    '4200010' => '客户联系人id非法',
    '4200011' => '客户描述表单不存在',
    '4200012' => '客户描述表单查询失败',
    '4200013' => '联系人描述表单不存在',
    '4200014' => '联系人描述表单查询失败',
    '4200015' => '客户描述表单格式校验错误',
    '4200016' => '客户描述表单格缺少固定字段',
    '4200017' => '客户联系人描述表单格式校验错误',
    '4200018' => '客户联系人描述表单格缺少固定字段',
    '4200019' => '客户描述表单数据格式校验错误',
    '4200020' => '客户描述表单数据缺少固定字段',
    '4200021' => '客户联系人描述表单数据格式校验错误',
    '4200022' => '客户联系人描述表单数据缺少固定字段',
    '41042' => '加密失败',
    '41043' => '解密失败',
    '40100' => '分机号已经存在',
    '40101' => '邮箱已经存在',
    '50002' => '企业员工不在授权范围',
    '50004' => '企业部门不在授权范围',
    'phone_allowed_access' => '手机未授权或者不允许访问！',
    'Authorization_expires'=>'钉钉应用授权过期或者未授权，停止访问！',
    'module_authorization_expires'=> '模块授权过期或者未授权，停止访问！',
    'dingtalk_template'=>'钉钉模板',
    'dingtalk_synchronized'=>'钉钉考勤同步成功',
    'dingtalk_organization_synchronized'=>'组织架构已同步完成',
    'extension_unopened'=>'CURL扩展没有开启!',
    'sync_already'=>'该记录已被匹配!',
    'dingtalk_sync_role'=>'钉钉同步角色',
    'dingtalk_organization_sync'=>'钉钉组织架构同步',
    'sync_error_dingtalk_department_is_empty'=>'同步失败，钉钉部门列表返回为空',
    'dingtalk_department'=>'钉钉部门',
    'dingtalk_role'=>'钉钉角色',
    'sync_is_closed'=> '同步未开启,请点击保存开启',
    'add_department_error'=>'新增部门失败：',
    'update_department_parent_error'=>'更新部门父级关系错误：',
    'dingtalk_department_id_is_empty_sync_cancel'=>'钉钉部门ID返回为空，部门同步未进行',
    'sync_error_dingtalk_role_empty'=>'同步失败，钉钉角色列表返回为空',
    'dingtalk_one_level_have_no_handle_department'=>'在钉钉中未分配部门',
    'parent_department_not_exist_in_oa'=>'父级部门不存在OA系统中',
    'OA_rolelist_is_empty_delete_error'=>'OA角色列表为空，反向删除OA角色列表失败',
    'OA_userlist_is_empty_delete_error'=>'OA用户列表为空，反向删除OA用户列表失败',
    'OA_department_list_is_empty_delete_error'=>'OA部门列表为空，反向删除OA部门列表失败',
    'admin_role'=>'admin角色',
    'admin_department'=>'admin部门',
    'temp_role_error'=>'钉钉临时角色返回错误',
    'admin_department_error'=>'admin部门返回错误',
    'admin_role_error'=>'admin角色返回错误',
    'some_error_log_info'=> '同步成功，部分数据异常，具体如下：',
    'staff_user_id'=> '员工UserID',
    'dingtalk_config_is_empty'=> '钉钉基础配置为空，请去钉钉设置页面配置。'
];
