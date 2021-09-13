<?php
return [
    '0x050001'        => '发送失败',
    '0x050002'        => '未设置收件服务器',
    '0x050003'        => '您还未添加发件邮箱',
    '0x050004'        => '请开启php_imap扩展',
    '0x050010'        => '收件服务器异常',
    'folder_name'     => '文件夹名',
    'account'         => '账号',
    '0x050005'        => '请配置发件人',
    '0x050006'        => '请配置发件箱',
    '0x050007'        => '163邮箱暂不支持收件',
    '0x050008'        => '正文不能为空',
    '0x050009'        => '接收邮箱不能为空',
    'no_mail_subject' => '无主题',
    "drafts"          => "草稿箱",
    "dustbin"         => "垃圾箱",
    "hair_box"        => "发件箱",
    "inbox"           => "收件箱",
    '0x050011'        => '连接错误，账号或者授权码错误',
    "connection_mail_server_error" => "连接邮件服务器发生错误",
    "error_message"   => "错误信息",
    'error_mails'     => '邮箱配置错误',
    'no_privileges'   => '暂无操作权限',
    'folder_has_mails'=> '文件夹内有邮件',
    'send_mail_error' => '发件服务器配置错误：',
    'receive_mail_error' => '收件服务器配置错误：',
    'tag_has_mails'=> '标签内有邮件',
    'authenticate' => '授权认证失败，请确认邮箱账号、密码或授权码',
    'connect_host' => '连接SMTP服务器失败，请确认发件服务器地址',
    'data_not_accepted' => '发送邮件失败：邮件服务器未接收，可能原因发送邮件太频繁',
    'empty_message' => '发送邮件失败：邮件正文为空',
    'instantiate' => '发送邮件失败：不能实现mail方法',
    'mailer_not_supported' => '发送邮件失败：不支持mail方法',
    'provide_address' => '发送邮件失败：未设置接收邮箱',
    'recipients_failed' => '发送邮件失败：以下收件箱接收失败',
    'smtp_connect_failed' => '连接SMTP服务器失败，请确认邮箱发件服务地址正确',
    'encoding' => '发送邮件失败：未知的文件编码',
    'execute' => '发送邮件失败：执行发送邮件操作失败',
    'file_access' => '发送邮件失败：解析附件文件地址失败',
    'file_open' => '发送邮件失败：解析附件文件失败',
    'from_failed' => '发送邮件失败：邮箱地址错误',
    'invalid_address' => '发送邮件失败：解析收件/抄送/密送邮箱地址失败',
    'signing' => '发送邮件失败：数字签名文件验证失败',
    'smtp_error' => '连接SMTP服务器失败，请稍后重试',
    'variable_set' => '发送邮件失败：设置参数错误',
    'extension_missing' => '发送邮件失败：环境异常，未开启openssl拓展',
    'connection_error' => '连接失败，请检查邮箱账号、密码、收件服务器地址及端口',
    'connection_error_name_or_password' => '收件服务器连接失败，请检查邮箱账号、密码',
    'connection_error_host' => '收件服务器连接失败，请检查收件服务器地址',
    'connection_error_port' => '收件服务器连接失败，请检查收件服务器端口',
    'connection_error_authorized_code' => '收件服务器连接失败，请使用授权码',
    'send_server_error' => '发件服务器配置错误：请检查服务器地址及端口',
    'outbox_account_format_error' => '邮箱地址格式错误，请检查邮箱地址',
    'not_choose_account' => '未选择邮箱',
    'save_failed' => '保存失败',
    'edit_failed' => '编辑失败',
    'delete_failed' => '删除失败',
    'create_record_failed' => '创建联系记录失败',
    'mailbox_not_found' => '发送邮件失败：接收地址中有不存在或者被禁用的，请与收件人确认正确的邮件地址，',
    'deleted_boxes' => '已删除邮件',
    'get_outbox_failed' => '获取邮箱详情失败',
    'get_folder_failed' => '获取邮箱文件夹相关数据失败',
    'folder_is_not_belongs_to_outbox' => '文件夹和邮箱不对应，请确认！',
    'star_box' => '星标邮箱',
    'Could_not_create_mailbox' => 'IMAP同步创建文件夹失败！可能原因：文件夹已存在。',
    'Could_not_delete_mailbox' => 'IMAP同步删除文件夹失败！可能原因：文件夹不存在或系统文件夹无法删除或邮箱服务器端文件夹下存有邮件。',
    'Could_not_rename_mailbox' => 'IMAP同步重命名文件夹失败！可能原因：文件夹不存在或系统文件夹无法重命名。',
    'Could_not_move_messages' => 'IMAP同步转移邮件失败！可能原因：目标文件夹不存在或邮件不存在。',
    'Could_not_delete_message_from_mailbox' => 'IMAP删除邮件失败！可能原因：目标文件夹不存在或邮件不存在。',
    'getting_list_by_server' => '正在获取邮箱数据中，请稍后再试！'
];
