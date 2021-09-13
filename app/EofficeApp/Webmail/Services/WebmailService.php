<?php

namespace App\EofficeApp\Webmail\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\EmailJob;
use PhpImap\Exception;
use Queue;
use DB;
use Illuminate\Support\Facades\Redis;
use Eoffice;

/**
 * 外部邮件service类，用来调用所需资源，提供和外部邮件有关的服务。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailService extends BaseService
{

    const WEBMAIL_RECEIVE_TYPE = 'webmail_receive_type';
    const WEBMAIL_RECEIVE_TIME = 'webmail_receive_time';
    const MAX_RECEIVE_NUMBER = 5;

    public function __construct() {
        parent::__construct();
        $this->email                     = 'App\Utils\Email';
        $this->attachmentService         = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->webmailMailRepository     = 'App\EofficeApp\Webmail\Repositories\WebmailMailRepository';
        $this->webmailFolderRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailFolderRepository';
        $this->webmailOutboxRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailOutboxRepository';
        $this->webmailServerRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailServerRepository';
        $this->webmailReceiverRepository = 'App\EofficeApp\Webmail\Repositories\WebmailReceiverRepository';
        $this->webmailTagRepository      = 'App\EofficeApp\Webmail\Repositories\WebmailTagRepository';
        $this->webmailMailTagRepository = 'App\EofficeApp\Webmail\Repositories\WebmailMailTagRepository';
        $this->webmailConfigRepository = 'App\EofficeApp\Webmail\Repositories\WebmailConfigRepository';
        $this->empowerService           = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->webmailSendReceiveLogRepository = 'App\EofficeApp\Webmail\Repositories\WebmailSendReceiveLogRepository';
        $this->webmailCustomerRecordRepository = 'App\EofficeApp\Webmail\Repositories\WebmailCustomerRecordRepository';
        $this->customerContactRecordService = 'App\EofficeApp\Customer\Services\ContactRecordService';
        $this->linkmanRepository      = 'App\EofficeApp\Customer\Repositories\LinkmanRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->webmailFolderService = 'App\EofficeApp\Webmail\Services\WebmailFolderService';
    }

    /**
     * 创建发件箱
     *
     * @param array $data 新建数据
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function createOutbox($data)
    {
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['user_name']   = trim($data['account']);
        $data['password']    = encrypt(trim($data['password']));

        if (!empty($data['imap_server'])) {
            $data['default_use_imap'] = 1;
        }
        // 检测邮箱设置有效性 是否可以收件
        // $checkStatus = $this->checkOutbox($data);
        // if (isset($checkStatus['code'])){
        //     return $checkStatus;
        // }
        if ($webmailOutboxObj = app($this->webmailOutboxRepository)->insertData($data)) {
            return $webmailOutboxObj->outbox_id;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除发件箱
     *
     * @param int|string $outboxId 发件箱id,多个用逗号隔开
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function deleteOutbox($outboxId)
    {
        $outboxIds = array_filter(explode(',', $outboxId));

        if (empty($outboxIds)) {
            return 0;
        }

        if (app($this->webmailOutboxRepository)->deleteById($outboxIds)) {
            // 删除邮箱时 清空对应个人文件夹公共邮箱邮件  设置标签的也要取消掉
            app($this->webmailMailTagRepository)->cancelTagByOutbox(['outbox_id' => $outboxId, true]);
            app($this->webmailMailRepository)->removeFolderByOutbox(['outbox_id' => $outboxId, true]);
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑发件箱
     *
     * @param int $outboxId 发件箱id
     * @param array $data 更新数据
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function updateOutbox($outboxId, $data)
    {
        if (isset($data['password'])) {
            $data['password'] = encrypt($data['password']);
        }

        if(isset($data['account']) && $data['account']){
            $data['user_name'] = $data['account'];
        }
        if (isset($data['is_default']) && $data['is_default'] == 1) {
            app($this->webmailOutboxRepository)->updateData(['is_default' => 0], ['outbox_creator' => $data['outbox_creator']]);
        }
        if (isset($data['is_public']) && $data['is_public'] == 0) {
            // 取消公共邮箱时 清空对应个人文件夹公共邮箱邮件  设置标签的也要取消掉
            app($this->webmailMailTagRepository)->cancelTagByOutbox(['outbox_id' => $outboxId]);
            app($this->webmailMailRepository)->removeFolderByOutbox(['outbox_id' => $outboxId]);
        }
        // 检测邮箱设置有效性 是否可以收件
        // if (isset($data['password'])) {
        //     $checkStatus = $this->checkOutbox($data);
        // }
        // if (isset($checkStatus['code'])){
        //     return $checkStatus;
        // }
        if (app($this->webmailOutboxRepository)->updateData($data, ['outbox_id' => $outboxId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 检测客户填的邮箱配置是否可以收件
     *
     * @param [type] $outbox
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function checkOutbox($outbox)
    {
        // $outbox['password'] = decrypt($outbox['password']);
        //检测是否可连接发件服务器
        $sendLog = [
            'outbox_info' => json_encode($outbox),
            'outbox_id' => $outbox['outbox_id'] ?? 0,
            'mail_id' => 0,
            'mail_info' => '',
            'type' => 0, // 操作类型，0：服务校验 1：发件 2：收件
            'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
            'creator' => $outbox['outbox_creator'] ?? ''
        ];
        if(!$this->checkMailAccount($outbox['account'])){
            $sendLog['result_info'] = trans('webmail.outbox_account_format_error');
            $sendLog['result_status'] = 2;
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return ['code' => ['outbox_account_format_error', 'webmail']];
        }
        if (empty($outbox['smtp_server']) || empty($outbox['smtp_port'])) {
            $sendLog['result_info'] = trans('webmail.send_server_error');
            $sendLog['result_status'] = 2;
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return ['code' => ['send_server_error', 'webmail']];
        }
        try{
            $smtpStatus = $this->connectSmtp($outbox);
        }catch (\Exception $e){
            // $message = $outbox['account'] . $this->handleMessage($e->getMessage());
            $message = $this->handleMessage($e->getMessage());
            $sendLog['result_info'] = $message;
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return ['code' => ['', $message]];
        }
        if (!$smtpStatus) {
            $message = trans('webmail.send_server_error');
            $sendLog['result_info'] = $message;
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return ['code' => ['', $message]];
        }
        // 检测收件
        if (!function_exists('imap_search') || !function_exists('imap_status')) {
            return ['code' => ['0x050004', 'webmail']];
        }
        $outbox = $this->handleSomeOutbox($outbox);
        $inboxServerData = [
            'account'   => $outbox['account'],
            'password'  => $outbox['password'],
            'outbox_id' => $outbox['outbox_id'] ?? 0,
            'account_cut' => $outbox['account_cut'] ?? 0 // 某些邮箱服务器 邮箱账号接收值仅为不带@后域名的账号值
        ];
        if ($outbox['server_type'] == 'POP3') {
            if (empty($outbox['pop3_server']) || empty($outbox['pop3_port'])) {
                $sendLog['result_info'] = trans('webmail.0x050002');
                app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
                return ['code' => ['0x050002', 'webmail']];
            }
            $server = $outbox['pop3_server'] . ':' . $outbox['pop3_port'] . '/pop';
            $server .= $outbox['pop3_ssl'] == 1 ? '/ssl' : '';
        } else {
            if (empty($outbox['imap_server']) || empty($outbox['imap_port'])) {
                $sendLog['result_info'] = trans('webmail.0x050002');
                app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
                return ['code' => ['0x050002', 'webmail']];
            }
            $server = $outbox['imap_server'] . ':' . $outbox['imap_port'] . '/imap';
            $server .= $outbox['imap_ssl'] == 1 ? '/ssl' : '';
        }
        $server = $server.'/novalidate-cert';   //发送到邮件服务器时不验证本地证书
        $inboxServerData['server'] = '{' . $server . '}INBOX';
        try{
            app($this->email)->getMailsInfo($inboxServerData, 'check');
        }catch (\Exception $e){
            // $receiveMessage = $inboxServerData['account'] . $this->handleMessage($e->getMessage(), 'receive');
            $receiveMessage = $this->handleMessage($e->getMessage(), 'receive');
            $sendLog['result_info'] = $receiveMessage;
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return ['code' => ['', $receiveMessage]];
//            return $e->getMessage();
        }
        return true;
    }

    /**
     * 获取发件箱列表
     *
     * @param array $param 查询条件
     *
     * @return rray 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getOutboxs($param, $userId)
    {
        $param                             = $this->parseParams($param);
        if(!isset($param['search']['outbox_id'])){
            $param['search']['outbox_creator'] = [$userId];
        }

        return $this->response(app($this->webmailOutboxRepository), 'getNum', 'getOutboxs', $param);
    }

    /**
     * 获取发件箱详情
     *
     * @param int $outboxId 发件箱id
     *
     * @return rray 查询结果
     *
     * @author qishaobo
     *
     */
    public function getOutbox($outboxId)
    {
        if ($webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($outboxId)) {
            $webmailOutboxObj->load(['creatorName' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }]);

            $data = $webmailOutboxObj->toArray();
            try {
                $data['password'] = decrypt($data['password']);
            } catch (DecryptException $e) {
                return [];
            }

            return $data;
        }

        return [];
    }

    /**
     * 获取邮件服务器信息
     *
     * @param array $search 查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getWebmailServer($param)
    {
        $param  = $this->parseParams($param);
        $search = $param['search'];
        if ($webmailServerObj = app($this->webmailServerRepository)->getWebmailServer($search)) {
            $result = $webmailServerObj->toArray();
            return empty($result) ? [] : $result[0];
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function parseMailBodyImgPath($matches)
    {
        $hostHttpUrlHead = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST ."/eoffice10/";
        // if (isset($matches[0]) && (strpos($matches[0], "../")) === false){
        //     $matches[0] = str_replace(["/server", "server"], "../../server", $matches[0]);
        // }
        return str_replace(["../../../", "../../", "../"], $hostHttpUrlHead, $matches[0]);
    }

    /**
     * 创建邮件
     *
     * @param int $data 发件内容
     *
     * @return array 操作结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function createMail($data)
    {
        if (empty($data['mail_body'])) {
            //$data['mail_body'] = "";
            return ['code' => ['0x050008', 'webmail']];
        }
        $mailBody = isset($data['mail_body']) ? $data['mail_body'] : "";
        $mailBody = preg_replace_callback(
            '/<img.*?src="(.*?)".*?>/is',
            array($this, 'parseMailBodyImgPath'),
            $mailBody);
        $mail = [
            'outbox_id'    => $data['outbox_id'],
            'mail_to'      => $data['mail_to'],
            'cc'           => empty($data['cc']) ? '' : $data['cc'],
            'bcc'          => empty($data['bcc']) ? '' : $data['bcc'],
            'mail_subject' => empty($data['mail_subject']) ? trans('webmail.no_mail_subject') : $data['mail_subject'],
            'mail_body'    => $data['mail_body'],
            'mail_time'    => date('Y-m-d H:i:s'),
            'is_read'      => 1,
            'folder'       => $data['folder'],
            'mail_creator' => $data['mail_creator'],
            'create_time'  => date('Y-m-d H:i:s'),
            'to_message'   => '',
            'customer_linkman' => $data['customer_linkman'] ?? '',
            'is_auto_customer_record' => $data['is_auto_customer_record'] ?? 0,
            'customer_linkman_outbox' => $data['customer_linkman_outbox'] ?? ''
        ];
        // 处理收件人
        $mails_to = explode(';',$data['mail_to']);
        if($mails_to && is_array($mails_to)){
            $data['mail_to'] = '';
            foreach ($mails_to as $kk => $item){
                // name<email>格式处理
                if($item){
                    if (preg_match('/<.*>/', $item, $matches) !== false) {
                        if (isset($matches[0])) {
                            $item = substr($matches[0], 1, -1);
                        }
                    }
                    $data['mail_to'] .=';'.$item;
                }
            }
        } else {
            // name<email>格式处理
            if (preg_match('/<.*>/', $mails_to, $matches) !== false) {
                if ($matches[0]) {
                    $data['mail_to'] = substr($matches[0], 1, -1);
                }
            }
        }
        $data['mail_to'] = trim($data['mail_to'],';');
        if(empty($data['mail_to'])){
            return ['code' => ['0x050009', 'webmail']];
        }

        $mailFrom = $this->getOutbox($data['outbox_id']);
        $nickNameConfig = app($this->webmailConfigRepository)->getFieldInfo(['config_creator' => $data['mail_creator']]);
        if ($nickNameConfig && $nickNameConfig[0]) {
            $config = $nickNameConfig[0];
            $useSendNickname = $config['use_send_nickname'] ?? 0;
            $nickName = $config['send_nickname'] ?? '';
            $nickName = $useSendNickname ?  $nickName : '';
        }
        $mailFrom['send_nickname'] = $nickName ?? '';
        $mail['mail_from'] = $mailFrom['account'];

        if (empty($data['mail_id'])) {
            $webmailMailObj = app($this->webmailMailRepository)->insertData($mail);
            $data['mail_id'] = $mailId = $webmailMailObj->mail_id;
        } else {
            $webmailMailObj = app($this->webmailMailRepository)->updateData($mail, ['mail_id' => [$data['mail_id']]]);
            $mailId         = $data['mail_id'];
        }

        $receivers     = [];
        $receiversData = array_filter(explode(';', $mail['mail_to'] . ';' . $mail['cc'] . ';' . $mail['bcc']));

        foreach ($receiversData as $v) {
            if (strrpos($v, '>') !== false) {
                continue;
            }

            $receivers[] = [
                'receiver_name'    => $v,
                'receiver_mail'    => $v,
                'receiver_remark'  => '',
                'tag_id'           => 17,
                'receiver_creator' => $data['mail_creator'],
            ];
        }

        $this->addReceiver($receivers);

        if (!empty($data['attachments'])) {
            app($this->attachmentService)->attachmentRelation("webmail_mail", $mailId, $data['attachments']);
        }

        if ($data['folder'] == 'sent') {
            $data['mail_body'] = $mailBody;
            //处理某一客户公司邮箱(xxxx@mjpm.cn)
            if(isset($mailFrom['account']) && $mailFrom['account']){
                $accountArray = explode('@',$mailFrom['account']);
                if($accountArray && is_array($accountArray) && isset($accountArray[1]) && $accountArray[1] == 'mjpm.cn'){
                    $mailFrom['account']   = $accountArray[0];
                }
            }
            $result = $this->sendMail($mailFrom, $data, $mailId);
        }

        if ($webmailMailObj) {
            return $data;
        } else {
            return ['code' => ['0x050001', 'webmail']];
        }

    }

    /**
     * 发送邮件
     *
     * @param int $data 发件内容
     *
     * @return array 操作结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function sendMail($mailFrom, $data, $mailId)
    {
        $attachment = [];

        if (!empty($data['attachments'])) {
            $search = [
                'entity_table' => 'webmail_mail',
                'entity_id'    => $mailId,
            ];

            $attachments = app($this->attachmentService)->getAttachments($search);
            foreach ($attachments as $v) {
                $attachment[] = [
                    'file_name' => $v['attachment_name'],
                    'file'      => $v['temp_src_file'],
                ];
            }
        }

        $mail = [
            'host'          => $mailFrom['smtp_server'],
            'username'      => $mailFrom['account'],
            'password'      => $mailFrom['password'],
            'port'          => $mailFrom['smtp_port'],
            'smtp_ssl'      => $mailFrom['smtp_ssl'],
            'from'          => $mailFrom['user_name'],
            'send_nickname' => $mailFrom['send_nickname'],
            'to'            => $data['mail_to'],
            'cc'            => empty($data['cc']) ? '' : $data['cc'],
            'bcc'           => empty($data['bcc']) ? '' : $data['bcc'],
            'addAttachment' => $attachment,
            'subject'       => $data['mail_subject'],
            'body'          => $data['mail_body'],
            'isHTML'        => true,
            'mail_id'       => $mailId,
            'auto_tls'      => $mailFrom['auto_tls'],
            'customer_linkman' => $data['customer_linkman'] ?? '',
            'mail_creator' => $data['mail_creator'] ?? '',
            'outbox_id' => $mailFrom['outbox_id'],
        ];
        $param = [
            'handle' => 'Send',
            'param'  => $mail,
        ];
        $sendLog = [
            'outbox_info' => json_encode($mailFrom),
            'outbox_id' => $mailFrom['outbox_id'] ?? 0,
            'mail_id' => $mailId,
            'mail_info' => json_encode($data),
            'type' => 1, // 操作类型，0：服务校验 1：发件 2：收件
            'result_status' => 0, // 操作结果 ，0：队列执行中 1:成功 2：失败
            'creator' => $data['mail_creator'] ?? ''
        ];
        app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
//        (new Email)->sendMail($mail);
        Queue::push(new EmailJob($param));

        return true;
    }

    /**
     * 删除邮件
     *
     * @param int|string $mailId 邮件id,多个用逗号隔开
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function deleteMail($mailId, $param, $userId)
    {
        if (isset($param['search'])) {
            $param = $this->parseParams($param);
            if (app($this->webmailMailRepository)->deleteByWhere($param['search'])) {
                return true;
            }

            return 0;
        }

        $mailIds = array_filter(explode(',', $mailId));

        if (empty($mailIds)) {
            return 0;
        }
        // 组装需要imap同步的参数
        $imapMoveMailParams = $this->moveMailImap($mailIds);
        if (app($this->webmailMailRepository)->deleteById($mailIds)) {
            $this->deleteAttachmens($mailIds);
            if ($imapMoveMailParams) {
                foreach ($imapMoveMailParams as $uid) {
                    $inboxServer = $uid['outbox'];
                    $inboxServer['type'] = 'deleteMail';
                    $inboxServer['mail_uid'] = $uid['mail_uid'];
                    $inboxServer['mail_id'] = $uid['mail_id'];
                    $inboxServer['mail_info'] = $uid['mail_info'];
                    $inboxServer['userId'] = $userId;
                    Queue::push(new EmailJob(['handle' => 'folder','param' => $inboxServer]));
                }
            }
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    private function deleteAttachmens($mailIds){
        $result = [];
        if($mailIds && is_array($mailIds)){
            foreach ($mailIds as $key => $mailId){
                $result['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'webmail_mail', 'entity_id' => $mailId]);
                if($result['attachment_id']){
                    app($this->attachmentService)->removeAttachment($result);
                }
            }

        }
    }

    private function deleteOutboxMail($mailIds)
    {
        foreach($mailIds as $mailId) {

        }
    }

    /**
     * 编辑邮件
     *
     * @param int $mailId 邮件id
     * @param array $data 更新数据
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function updateMail($mailId, $data, $userId)
    {
        $mailIds = array_filter(explode(',', $mailId));

        if (empty($mailIds)) {
            return 0;
        }
        // 组装需要imap同步的参数
        $imapMoveMailParams = [];
        if (isset($data['folder'])) {
            $imapMoveMailParams = $this->moveMailImap($mailIds);
            // 参照QQ邮箱的逻辑 参考qq邮箱的逻辑 删除邮件-》已删除
            if ($data['folder'] == 'trash' && isset($data['is_delete']) && $data['is_delete']) {
                $data['folder'] = 'deleted';
            }
        }
        if (app($this->webmailMailRepository)->updateData($data, ['mail_id' => [$mailIds, 'in']])) {
            if ($imapMoveMailParams) {
                foreach ($imapMoveMailParams as $uid) {
                    $folder = app($this->webmailFolderService)->getFolder($data['folder'], $uid['outbox']['outbox_id']);
                    $inboxServer = $uid['outbox'];
                    $inboxServer['type'] = 'moveMail';
                    $inboxServer['mailbox_name'] = $folder['folder_name'];
                    $inboxServer['origin_mailbox_name'] = $uid['folder'];
                    $inboxServer['mail_uid'] = $uid['mail_uid'];
                    $inboxServer['mail_id'] = $uid['mail_id'];
                    $inboxServer['mail_info'] = $uid['mail_info'];
                    $inboxServer['userId'] = $userId;
                    Queue::push(new EmailJob(['handle' => 'folder','param' => $inboxServer ]));
                }
            }
            return true;
        }

        return true;
        //return ['code' => ['0x000003', 'common']];
    }

    /** 转移邮件时组装需要imap的数据
     * @param $mailIds
     * @return array
     */
    public function moveMailImap($mailIds)
    {
        $mails = app($this->webmailMailRepository)->getFieldInfo([],[], ['mail_id' => [$mailIds, 'in']]);
        $imapMoveMailParams = [];
        foreach ($mails as $mail) {
            if ($mail['mail_uid']) {
                $folder = app($this->webmailFolderService)->getFolder($mail['folder'], $mail['outbox_id']);
                if ($folder && isset($folder['folder_name'])) {
                    $inboxServer = $this->getInboxServer($mail['outbox_id'], $folder['folder_name']);
                    if ($inboxServer['server_type'] == 'IMAP' && $inboxServer['imap_sync'] == 1) {
                        $imapMoveMailParams[] = [
                            'mail_uid' => $mail['mail_uid'],
                            'mail_id' => $mail['mail_id'],
                            'outbox' => $inboxServer,
                            'folder' => $folder['folder_name'] ?? '',
                            'mail_info' => json_encode(['mail_subject' => $mail['mail_subject']])
                        ];
                    }
                }
            }
        }
        return $imapMoveMailParams;
    }

    /**
     * 获取邮件列表
     *
     * @param array $param 查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getMails($param)
    {
        $param = $this->parseParams($param);
        if(isset($param['portal']) && $param['portal']) $param = $this->get_outbx_ids($param);
        $data  = $this->response(app($this->webmailMailRepository), 'getNum', 'getMails', $param);

        if (!empty($data['list'])) {
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'webmail_mail', 'entity_id' => $v['mail_id']]);
            }
        }

        return $data;
    }

    private function get_outbx_ids($param){
        $portal = $param['portal'] ?? '';
        // 编辑器系统数据源 带参数邮箱id直接返回
        if (isset($portal) && $portal == 'editor') {
            if (isset($param['search']) && array_key_exists('outbox_id', $param['search'])) {
                return $param;
            } else {
                $search = app($this->webmailOutboxRepository)->getOutboxs(['search' => ['outbox_creator'=> [own()['user_id']]]]);
            }
        } else {
            $param['search'] = ['outbox_creator'=> [own()['user_id']]];
            $search = app($this->webmailOutboxRepository)->getOutboxs($param);
        }
        $params = $fileds = [];
        if($search && is_array($search)){
            foreach ($search as $key => $vo){
                $params[] = $vo['outbox_id'];
            }
        }
        switch ($portal){
            case 1:$fileds = [['sent', 'draft', 'trash'], 'not_in'];break;
            case 2:$fileds = [['sent'], 'in'];break;
            case 3:$fileds = [['draft'], 'in'];break;
        }
        if ($portal !== 'editor') {
            unset($param['search'],$param['fields']);
            $param['search'] = ['outbox_id'=>[$params,'in'], 'folder'=>$fileds];
            $param['order_by'] = ['mail_time'=>'DESC'];
        } else {
            // 编辑器系统数据源 增加邮箱id  公共邮箱
            $param['search']['outbox_id'] = [$params,'in'];
        }
        return $param;
    }


    /**
     * 获取邮件详情
     *
     * @param int $mailId 邮件id
     *
     * @return rray 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getMail($mailId)
    {
        if ($webmailMailObj = app($this->webmailMailRepository)->getDetail($mailId)) {
            $result = $webmailMailObj->toArray();

            if (!$result['is_read']) {
                app($this->webmailMailRepository)->updateData(['is_read' => 1], ['mail_id' => [$mailId]]);
            }
            $result['result_status'] = app($this->webmailSendReceiveLogRepository)->getStatusByMail($mailId);
            $result['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'webmail_mail', 'entity_id' => $mailId]);

            return $result;
        }

        return [];
    }

    /**
     * 创建文件夹
     *
     * @param array $data 新建数据
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function createFolder($data)
    {
        if ($webmailFolderObj = app($this->webmailFolderRepository)->insertData($data)) {
            return $webmailFolderObj->role_id;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除文件夹
     *
     * @param int|string $folderId 文件夹id,多个用逗号隔开
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function deleteFolder($folderId)
    {
        $folderIds = array_filter(explode(',', $folderId));

        if (empty($folderIds)) {
            return 0;
        }
        //删除前判断当前文件夹是否有邮件
        if (app($this->webmailMailRepository)->getNum(['search' => ['folder' => [$folderId]]])){
            return ['code' => ['folder_has_mails', 'webmail']];
        }
        if (app($this->webmailFolderRepository)->deleteById($folderIds)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑文件夹
     *
     * @param int $folderId 文件夹id
     * @param array $data 更新数据
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function updateFolder($folderId, $data)
    {
        if (app($this->webmailFolderRepository)->updateData($data, ['folder_id' => $folderId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取文件夹列表
     *
     * @param array $param 查询条件
     *
     * @return array 查询结果a
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getFolders($param, $userId)
    {
        $param                             = $this->parseParams($param);
        $param['search']['folder_creator'] = [$userId];
        $param['search']['is_email'] = 0;
        return $this->response(app($this->webmailFolderRepository), 'getNum', 'getFolders', $param);
    }

    /**
     * 获取文件夹详情
     *
     * @param int $folderId 文件夹id
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getFolder($folderId)
    {
        if ($webmailFolderObj = app($this->webmailFolderRepository)->getDetail($folderId)) {
            return $webmailFolderObj->toArray();
        }

        return [];
    }

    /**
     * 获取转移文件夹
     *
     * @param array $param 查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getTransferFolder($param, $userId)
    {
        $folders = [
            "inbox" => trans("webmail.inbox"),
            "sent"  => trans("webmail.hair_box"),
            "draft" => trans("webmail.drafts"),
            "trash" => trans("webmail.dustbin"),
            "deleted" => trans("webmail.deleted_boxes")
        ];
        $outboxId = $param['outbox_id'] ?? '';
        if (!empty($outboxId)) {
            $outbox = $this->getOutbox($outboxId);
        }
        $data = [];
        // 支持imap同步的获取的邮箱需要从数据库获取
        if (isset($outbox) && !empty($outbox) && $outbox['server_type'] == 'IMAP' && $outbox['imap_sync'] == 1) {
            /** @var WebmailFolderService $webmailFolderService */
            $webmailFolderService = app($this->webmailFolderService);
            $folders = $webmailFolderService->getListByDatabase(['outbox_id' => $outboxId]);
            $folders = array_column($folders, NULL, 'folder_id');
            if (isset($param['folder'])) {
                array_map(function($value) use($param, &$folders) {
                    if (($value['folder_name_alias'] && $value['folder_name_alias'] == $param['folder']) || $value['folder_id'] == $param['folder']) {
                        unset($folders[$value['folder_id']]);
                    }
                }, $folders);
            }
            foreach ($folders as $k => $v) {
                if ($v['folder_name_alias'] && in_array($v['folder_name_alias'], $webmailFolderService::SYSTEMFOLDERALIAS)) {
                    $data[] = [
                        'folder_id'   => $v['folder_name_alias'],
                        'folder_name' => $v['alias'],
                    ];
                } else {
                    $data[] = [
                        'folder_id'   => $v['folder_id'],
                        'folder_name' => $v['folder_name'],
                    ];
                }
            }
        } else {
            if (isset($param['folder'])) {
                unset($folders[$param['folder']]);
            }
            foreach ($folders as $k => $v) {
                $data[] = [
                    'folder_id'   => $k,
                    'folder_name' => $v,
                ];
            }
        }
        sort($data);
        $params = [
            'fields' => ['folder_id', 'folder_name'],
            'page'   => 0,
            'search' => [
                'folder_creator' => [$userId],
                'is_email' => [0]
            ],
        ];
        if (isset($param['folder'])) {
            $params['search']['folder_id'] = [$param['folder'], '!='];
        }
        $result = app($this->webmailFolderRepository)->getFolders($params);
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                if (isset($param['folder']) && $v['folder_id'] == $param['folder']) {
                    unset($result[$k]);
                }
            }

            $data = array_merge($data, $result);
        }

        return $data;
    }

    /**
     * 获取账号信息
     *
     * @param int $outboxId 发件箱id
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function getAccountInfo($outboxId, $userId, $params)
    {
        if (!function_exists('imap_search')) {
            return ['code' => ['0x050004', 'webmail']];
        }
        $folder = $params['folder'] ?? 'inbox';
        $inboxServer = $this->getInboxServer($outboxId, $folder);
        //处理某一客户公司邮箱(xxxx@mjpm.cn)
        if(isset($inboxServer['account']) && $inboxServer['account']){
            $accountArray = explode('@',$inboxServer['account']);
            if($accountArray && is_array($accountArray) && isset($accountArray[1]) && $accountArray[1] == 'mjpm.cn'){
                $inboxServer['account']   = $accountArray[0];
            }

        }
        if (isset($inboxServer['code'])) {
            $outbox = app($this->webmailOutboxRepository)->getDetail($outboxId)->toArray();
            $sendLog = [
                'outbox_info' => json_encode($outbox),
                'outbox_id' => $outboxId ?? 0,
                'mail_id' => 0,
                'mail_info' => '',
                'type' => 2, // 操作类型，0：服务校验 1：发件 2：收件
                'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
                'result_info' => trans('webmail.0x050002'),
                'creator' => $userId
            ];
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return $inboxServer;
        }
        // 获取邮箱未读邮件个数
        try{
            $mailIds = app($this->email)->getMailsInfo($inboxServer, 'nums');
            $cacheIds = $this->getAlreadyReceiveMail($inboxServer['account'], $folder);
            $cacheIdsCount = count($cacheIds);
            $mailCount = $mailIds - $cacheIdsCount;
            if ($mailCount < 0) {
                $mailIds = app($this->email)->getMailsInfo($inboxServer);
                $data = [
                    'num' => count(array_diff($mailIds, $cacheIds)),
                ];
            } else {
                $data = [
                    'num' => $mailIds - $cacheIdsCount,
                ];
            }
            return $data;
        }catch (\Exception $e){
            $message = $this->handleMessage($e->getMessage(), 'receive');
            $sendLog = [
                'outbox_info' => json_encode($inboxServer),
                'outbox_id' => $outboxId ?? 0,
                'mail_id' => 0,
                'mail_info' => '',
                'type' => 2, // 操作类型，0：服务校验 1：发件 2：收件
                'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
                'result_info' => $message,
                'creator' => $userId
            ];
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return ['code' => ['', $inboxServer['account'] . $message]];

//            return ['code' => ['', $inboxServer['account'].trans("webmail.error_mails"). trans("webmail.receive_mail_error") . $e->getMessage()]];
//            return $e->getMessage();
        }




//        $mailIds = app($this->email)->getMailsInfo($inboxServer);
//        if (is_string($mailIds)) {
//            if (strpos($mailIds, 'Refused') !== false) {
//                return ['code' => ['0x050010', 'webmail']];
//            } else {
//                return error_response('0x050020', '', trans("webmail.connection_mail_server_error") . '，' . trans("webmail.error_message") . '：' . $mailIds);
//            }
//        }
//        if (isset($mailIds['code'])) {
//
//            return $mailIds;
//        }
//        $cacheIds = $this->getAlreadyReceiveMail($inboxServer['account']);
//        $data = [
//            'num' => count(array_diff($mailIds, $cacheIds)),
//        ];
//        return $data;

        // $unit = ['b', 'K', 'M', 'G', 'T'];

        // $i = 0;
        // while ($data['size'] > 1024) {
        //     $data['size'] = $data['size']/1024;
        //     $i++;
        // }

        // if ($data['size'] > 0) {
        //     $data['size'] = round($data['size'], 2).' '.$unit[$i];
        // }

        // return $data;
    }

    /**
     * 获取已经收的信
     *
     * @param string $account 账号
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function getAlreadyReceiveMail($account, $folder = '')
    {
        $folder = $folder != 'inbox' ? $folder : '';
        $fileName = $this->alreadyReceiveMailFile($account, $folder);
        if (!is_file($fileName)) {
            return [];
        }

        $data = file_get_contents($fileName);

        return array_filter(explode(',', $data));
    }

    /**
     * 添加已经收的信
     *
     * @param string $account 账号
     * @param array $data 数据
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function addAlreadyReceiveMail($account, $data, $folder = '')
    {
        $dataOld = $this->getAlreadyReceiveMail($account, $folder);
        $data    = array_unique(array_merge($data, $dataOld));

        $fileName = $this->alreadyReceiveMailFile($account, $folder);

        return file_put_contents($fileName, implode(',', $data));
    }

    /**
     * 添加已经收的信
     *
     * @param string $account 账号
     * @param string $folder
     *
     * @return string 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function alreadyReceiveMailFile($account, $folder = '')
    {
        $folder = $folder != 'inbox' ? $folder : '';
        $emailPath = base_path('public/attachment/email/');
        if (!is_dir($emailPath)) {
            dir_make($emailPath, 0777);
            chmod($emailPath, 0777);//umask（避免用户缺省权限属性与运算导致权限不够）
        }

        $fileName = $account . $folder . '-receive.txt';

        return $emailPath . $fileName;
    }

    /**
     * 收信
     *
     * @param int $outboxId 发件箱id
     * @param array $param 查询参数
     *
     * @return mixed 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function receiveMail($outboxId, $param, $userId)
    {
        $folder = $param['folder'] ?? 'inbox';
        $inboxServer = $this->getInboxServer($outboxId, $folder);
        if (empty($param['num'])) {
            return '';
        }

        if (isset($inboxServer['code'])) {
            $outbox = app($this->webmailOutboxRepository)->getDetail($outboxId)->toArray();
            $sendLog = [
                'outbox_info' => json_encode($outbox),
                'outbox_id' => $outboxId,
                'mail_id' => 0,
                'mail_info' => '',
                'type' => 2, // 操作类型，0：服务校验 1：发件 2：收件
                'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
                'creator' => $userId ?? '',
                'result_info' => trans('webmail.0x050002'),
            ];
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return $inboxServer['code'];
        }
        if(isset($inboxServer['account']) && $inboxServer['account']){
            $accountArray = explode('@',$inboxServer['account']);
            if($accountArray && is_array($accountArray) && isset($accountArray[1]) && $accountArray[1] == 'mjpm.cn'){
                $inboxServer['account']   = $accountArray[0];
            }
        }

        $cacheIds = $this->getAlreadyReceiveMail($inboxServer['account'], $folder);
        try{
            $mailIds  = app($this->email)->getMailsInfo($inboxServer);
        } catch (\Exception $e) {
            $message = $this->handleMessage($e->getMessage(), 'receive');
            $sendLog = [
                'outbox_info' => json_encode($inboxServer),
                'outbox_id' => $outboxId,
                'mail_id' => 0,
                'mail_info' => '',
                'type' => 2, // 操作类型，0：服务校验 1：发件 2：收件
                'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
                'creator' => $userId ?? '',
                'result_info' => $message,
            ];
            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            // 收件失败的通知
            $sendData['remindMark']   = 'webmail-receive_failed';
            $sendData['toUser']       = [$userId];
            $sendData['contentParam'] = ['failedReason' => $message, 'outboxAccount' => $inboxServer['account']];
            $sendData['stateParams']  = ['type'=>'webmail-receiveFailed'];
            Eoffice::sendMessage($sendData);
            return ['code' => ['', $message]];
        }
        $mailIds = is_array($mailIds) ? $mailIds : [];
        $cacheIds = is_array($cacheIds) ? $cacheIds : [];
        $mailIds  = array_reverse(array_diff($mailIds, $cacheIds));
        $mailIds  = array_slice($mailIds, 0, $param['num']);
        if (empty($mailIds)) {
            return true;
        }
        $this->addAlreadyReceiveMail($inboxServer['account'], $mailIds, $folder);
        $param = [
            'handle' => 'Receive',
            'param'  => [
                'inboxServer' => $inboxServer,
//                'receive'     => $mailIds,
                'userId'      => $userId,
            ],
        ];
        // dispatch((new EmailJob($param))->onQueue('high'));
        // 收取一个邮件为一个队列任务
        foreach ($mailIds as $mailId) {
            if ($mailId) {
                $param['param']['receive'] = [$mailId];
                Queue::push(new EmailJob($param));
            }
        }

        return true;
    }

    /**
     * 获取收件服务器
     *
     * @param int $outboxId 发件箱id
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function getInboxServer($outboxId, $folder = '')
    {
        $outbox = app($this->webmailOutboxRepository)->getDetail($outboxId)->toArray();
        $outbox = $this->handleSomeOutbox($outbox);
        $inboxServerData = [
            'account'   => $outbox['account'],
            'password'  => decrypt($outbox['password']),
            'outbox_id' => $outbox['outbox_id'],
            'account_cut' => $outbox['account_cut'] ?? 0, // 某些邮箱服务器 邮箱账号接收值仅为不带@后域名的账号值
            'imap_sync' => $outbox['imap_sync'] ?? 0,
            'server_type' => $outbox['server_type'] ?? 0,
        ];

        if ($outbox['server_type'] == 'POP3') {
            if (empty($outbox['pop3_server']) || empty($outbox['pop3_port'])) {
                return ['code' => ['0x050002', 'webmail']];
            }

            $server = $outbox['pop3_server'] . ':' . $outbox['pop3_port'] . '/pop';
            $server .= $outbox['pop3_ssl'] == 1 ? '/ssl' : '';
        } else {
            if (empty($outbox['imap_server']) || empty($outbox['imap_port'])) {
                return ['code' => ['0x050002', 'webmail']];
            }

            $server = $outbox['imap_server'] . ':' . $outbox['imap_port'] . '/imap';
            $server .= $outbox['imap_ssl'] == 1 ? '/ssl' : '';
        }
        $server = $server.'/novalidate-cert';   //发送到邮件服务器时不验证本地证书
        $inboxServerData['server'] = '{' . $server . '}' . strtoupper($folder);

        return $inboxServerData;
    }

    /**
     * 判断当前操作环境
     *
     * @return int 1 or 2
     *
     * @author zw
     *
     * @since  2018-04-18
     */
    private function getSystem(){
        $agent = php_uname();
        $system = '';
        if(strpos($agent,"Windows")!==false){
            $system = 1;    //windows操作环境
        }else if(strpos($agent,"Linux")!==false){
            $system = 2;    //linux操作环境
        }
        return $system;
    }

    /**
     * 连接SMTP邮件服务器
     *
     * @param array $mailFrom 服务器参数
     *
     * @return number 操作结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function connectSmtp($mailFrom)
    {
        $mail = [
            'host'     => $mailFrom['smtp_server'],
            'username' => trim($mailFrom['account']),
            'password' => trim($mailFrom['password']),
            'port'     => $mailFrom['smtp_port'],
            'auto_tls' => $mailFrom['auto_tls'] ?? 1
        ];

        return (int) app($this->email)->smtpConnect($mail);
    }

    /**
     * 添加收件人
     *
     * @param array $receivers 收件人信息
     *
     * @return array 操作结果
     *
     * @author qishaobo
     *
     * @since  2016-08-18
     */
    public function addReceiver($receivers)
    {
        try {
            foreach ($receivers as $receiver) {
                app($this->webmailReceiverRepository)->insertData($receiver);
            }
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * 获取收件人
     *
     * @param array $receivers 收件人信息
     *
     * @return array 操作结果
     *
     * @author qishaobo
     *
     * @since  2016-08-18
     */
    public function getReceivers($param, $userId)
    {
        return app($this->webmailReceiverRepository)->getReceivers($param, $userId);
    }

    /**
     * 创建邮件
     *
     * @param int $data 发件内容
     *
     * @return array 操作结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function sendWebmail($data)
    {
        if (empty($data['mail_creator'])) {
            return ['code' => ['0x050005', 'webmail']];
        }

        $param = [
            'search' => [
                'outbox_creator' => [$data['mail_creator']],
            ],
        ];

        $outboxs = app($this->webmailOutboxRepository)->getOutboxs($param);

        if (empty($outboxs)) {
            return ['code' => ['0x050006', 'webmail']];
        }

        $data['outbox_id'] = $outboxs[0]['outbox_id'];
        $data['folder']    = 'sent';

        return $this->createMail($data);
    }

    public function getReceiveRule()
    {
        $result = DB::table('system_params')->whereIn('param_key', [self::WEBMAIL_RECEIVE_TYPE, self::WEBMAIL_RECEIVE_TIME])->get();
        if ($result->isEmpty()) {
            return [];
        }
        $type = $time = 0;
        foreach ($result as $key => $item) {
            if ($item->param_key === self::WEBMAIL_RECEIVE_TYPE) {
                $type = $item->param_value;
            }
            if ($item->param_key === self::WEBMAIL_RECEIVE_TIME) {
                $time = $item->param_value;
            }
        }
        return compact('type', 'time');
    }

    /**
     * 设置收信规则
     * @param array $input
     * @param array $user
     * @return array
     */
    public function setReceiveRule($input)
    {
        $type = isset($input['type']) ? intval($input['type']) : 0;
        $time = isset($input['time']) ? intval($input['time']) : 1;
        DB::table('system_params')->where('param_key', self::WEBMAIL_RECEIVE_TYPE)->update(['param_value'=> $type]);
        DB::table('system_params')->where('param_key', self::WEBMAIL_RECEIVE_TIME)->update(['param_value'=> $time]);
        Redis::set(self::WEBMAIL_RECEIVE_TYPE, $type);
        Redis::set(self::WEBMAIL_RECEIVE_TIME, $time);
        return true;
    }

    /**
     * 获取当前用户的全部邮件
     * @param  int $user_id
     * @return array
     */
    public function getMailCount($user_id)
    {
        $result = [];
        $outbox_lists = DB::table('webmail_outbox')->where('outbox_creator', $user_id)->orwhere('is_public',1)->get();
        if ($outbox_lists->isEmpty()) {
            return $result;
        }

        $outbox_ids = [];
        foreach ($outbox_lists as $key => $item) {
            $outbox_ids[] = $item->outbox_id;
        }
        $not_in = ['trash', 'sent', 'draft'];
        $mail_lists = DB::table('webmail_mail')->select(['is_read'])->whereIn('outbox_id', $outbox_ids)->whereNotIn('folder', $not_in)->get();
        if ($mail_lists->isEmpty()) {
            return $result;
        }
        $unread = 0;
        foreach ($mail_lists as $key => $item) {
            if (!$item->is_read) {
                $unread ++;
            }
        }
        $result = [
            'total' => count($mail_lists),
            'unread' => $unread
        ];
        return $result;
    }

    public function receiveAllMail()
    {
        // 获取所有的邮件
        $outbox_lists = app($this->webmailOutboxRepository)->getAllOutbox();
//        $outbox_lists = DB::table('webmail_outbox')->get();
        if ($outbox_lists->isEmpty()) {
            return [];
        }
        foreach ($outbox_lists as $key => $value) {
            $inboxServer = $this->getInboxServer($value->outbox_id, 'inbox');
            if (isset($inboxServer['code'])) {
                continue;
            }
            $cacheIds = $this->getAlreadyReceiveMail($inboxServer['account']);
            //处理某一客户公司邮箱(xxxx@mjpm.cn)
            if(isset($inboxServer['account']) && $inboxServer['account']){
                $accountArray = explode('@',$inboxServer['account']);
                if($accountArray && is_array($accountArray) && isset($accountArray[1]) && $accountArray[1] == 'mjpm.cn'){
                    $inboxServer['account']   = $accountArray[0];
                }
            }
            try{
                $mailIds  = app($this->email)->getMailsInfo($inboxServer);
                $mailIds = is_array($mailIds) ? $mailIds : [];
                $cacheIds = is_array($cacheIds) ? $cacheIds : [];
                $mailIds  = array_reverse(array_diff($mailIds, $cacheIds));
                if (empty($mailIds)) {
                    continue;
                }
                if (count($mailIds) > self::MAX_RECEIVE_NUMBER) {
                    $mailIds  = array_slice($mailIds, 0, self::MAX_RECEIVE_NUMBER);
                }
                $this->addAlreadyReceiveMail($inboxServer['account'], $mailIds);
                $param = [
                    'handle' => 'Receive',
                    'param'  => [
                        'inboxServer' => $inboxServer,
                        'receive'     => $mailIds,
                        'userId'      => $value->outbox_creator,
                    ],
                ];
                Queue::push(new EmailJob($param));
            }catch (\Exception $e){
                $message = $this->handleMessage($e->getMessage(), 'receive');
                $sendLog = [
                    'outbox_info' => json_encode($inboxServer),
                    'outbox_id' => $value->outbox_id,
                    'mail_id' => 0,
                    'mail_info' => '',
                    'type' => 2, // 操作类型，0：服务校验 1：发件 2：收件
                    'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
                    'creator' => $userId ?? '',
                    'result_info' => $message,
                ];
                app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
                $sendData['remindMark']   = 'webmail-receive_failed';
                $sendData['toUser']       = [$value->outbox_creator];
                $sendData['contentParam'] = ['failedReason' => $message, 'outboxAccount' => $value->account];
                $sendData['stateParams']  = ['type'=>'webmail-receiveFailed'];
                Eoffice::sendMessage($sendData);
                continue;
            }

        }
        return true;
    }
    public function showWebEmail()
    {
        $auhtMenus = app($this->empowerService)->getPermissionModules();

        if (isset($auhtMenus["code"]) || !in_array(14, $auhtMenus)) {
            return "false";
        } else {
            return "true";
        }

    }

     /**
     * 创建标签
     *
     * @param array $data 新建数据
     *
     * @return int|array 操作成功状态|错误码
     *
     * @since  2016-07-28
     */
    public function createTag($data)
    {
        if ($webmailTagObj = app($this->webmailTagRepository)->insertData($data)) {
            return $webmailTagObj->tag_id;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除标签
     *
     * @param int|string $tagrId 标签id,多个用逗号隔开
     *
     * @return int|array 操作成功状态|错误码
     *
     * @since  2016-07-28
     */
    public function deleteTag($tagId)
    {
        $tagId = array_filter(explode(',', $tagId));

        if (empty($tagId)) {
            return 0;
        }
        //删除前判断当前标签是否有邮件
        if (app($this->webmailMailTagRepository)->getNum(['tag_id' => $tagId])) {
            return ['code' => ['tag_has_mails', 'webmail']];
        }
        if (app($this->webmailTagRepository)->deleteById($tagId)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑标签
     *
     * @param int $tagrId 标签id
     * @param array $data 更新数据
     *
     * @return int|array 操作成功状态|错误码
     *
     * @since  2016-07-28
     */
    public function updateTag($tagrId, $data)
    {
        if (app($this->webmailTagRepository)->updateData($data, ['tag_id' => $tagrId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取标签列表
     *
     * @param array $param 查询条件
     *
     * @return array 查询结果
     *
     * @since  2016-07-28
     */
    public function getTags($param, $userId)
    {
        $param = $this->parseParams($param);
        $param['search']['tag_creator'] = [$userId];
        return $this->response(app($this->webmailTagRepository), 'getNum', 'getTags', $param);
    }

    /**
     * 获取标签详情
     *
     * @param int $tagrId 标签id
     *
     * @return array 查询结果
     *
     * @since  2019-08-30
     */
    public function getTag($tagrId)
    {
        if ($webmailTagObj = app($this->webmailTagRepository)->getDetail($tagrId)) {
            return $webmailTagObj->toArray();
        }

        return [];
    }
    /**
     * 设置标签
     *
     * @param [type] $data
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function setTag($data)
    {
        $mailIds = $data['mail_id'] ?? [];
        $tagId = $data['tag_id'] ?? 0;
        if (!$mailIds || !$tagId) {
            return false;
        }
        if (!is_array($mailIds)){
            $mailIds = array_filter(explode(',', $mailIds));
        }
        foreach ($mailIds as $mailId) {
            app($this->webmailMailTagRepository)->updateOrCreate(['mail_id' => $mailId, 'tag_id' => $tagId]);
        }
        return true;
    }
    /**
     * 取消标签
     *
     * @param [type] $mailId
     * @param [type] $tagId
     *
     * @return boolean
     * @author yuanmenglin
     * @since
     */
    public function cancelTag($data)
    {
        $mailId = $data['mail_id'] ?? 0;
        $tagId = $data['tag_id'] ?? 0;
        // 单个邮件指定标签取消
        if ($mailId && $tagId){
            $where = [
                'mail_id' => [$mailId],
                'tag_id' => [$tagId]
            ];
            app($this->webmailMailTagRepository)->deleteByWhere($where);
        // 批量邮件取消所有标签
        } else if ($mailId && !$tagId) {
            if (!is_array($mailId)){
                $mailId = array_filter(explode(',', $mailId));
            }
            app($this->webmailMailTagRepository)->deleteByWhere(['mail_id' => [$mailId, 'in']]);
        // 清空某个标签下的邮件
        } else if (!$mailId && $tagId) {
            app($this->webmailMailTagRepository)->deleteByWhere(['tag_id' => [$tagId]]);
        } else {
            return false;
        }
        return true;
    }

    public function getSendRules($param, $userId)
    {
        $param['config_creator'] = $userId;
        $info = app($this->webmailConfigRepository)->getFieldInfo($param);
        return $info ? $info[0] : [];

    }

    public function setSendRules($param, $userId)
    {
        $param['config_creator'] = $userId;
        $configId = $param['config_id'] ?? 0;
        if (!$configId){
            return app($this->webmailConfigRepository)->insertData($param);
        } else {
            return app($this->webmailConfigRepository)->updateData($param, ['config_id' => $configId]);
        }
    }

    public function handleMessage($errorMessage, $type = 'send')
    {
        if ($type == 'send') {
            $directMessages = [
                'authenticate' => 'SMTP Error: Could not authenticate.',   //--- 发送邮件授权认证失败，请确认邮箱账号、密码/授权码
                'connect_host' => 'SMTP Error: Could not connect to SMTP host.', //--- 发送邮件连接邮箱SMTP服务器失败，请确认发件服务器地址
                'data_not_accepted' => 'SMTP Error: data not accepted.',    //--- 发送邮件失败：邮件服务器未接收，可能原因发送邮件太频繁
                'empty_message' => 'Message body empty',    //--- 发送邮件失败：邮件正文为空
                'instantiate' => 'Could not instantiate mail function.',    //--- 发送邮件失败：不能实现mail方法
                'mailer_not_supported' => 'mailer is not supported.',       //--- 发送邮件失败：不支持mail方法
                'provide_address' => 'You must provide at least one recipient email address.',  //--- 发送邮件失败：未设置接收邮件
                'recipients_failed' => 'SMTP Error: The following recipients failed: ',     //--- 发送邮件失败：以下收件箱接收失败
                'smtp_connect_failed' => 'SMTP connect() failed.',  //--- 发送邮件连接SMTP服务器失败，请确认邮箱地址正确
            ];
            $indirectMessages = [
                'encoding' => 'Unknown encoding: ',     //--- 发送邮件失败：未知的文件编码
                'execute' => 'Could not execute: ',     //--- 发送邮件失败：执行发送邮件操作失败
                'file_access' => 'Could not access file: ',     //--- 发送邮件失败：解析附件文件地址失败
                'file_open' => 'File Error: Could not open file: ',     //--- 发送邮件失败：解析附件文件失败
                'from_failed' => 'The following From address failed: ',     //--- 发送邮件失败：邮箱地址错误
                'invalid_address' => 'Invalid address: ',                   //--- 发送邮件失败：解析收件/抄送/密送邮箱地址失败
                'signing' => 'Signing Error: ',     //--- 发送邮件失败：数字签名文件验证失败
                'smtp_error' => 'SMTP server error: ',      //--- 发送邮件连接SMTP服务器失败，请稍后重试
                'variable_set' => 'Cannot set or reset variable: ',     //--- 发送邮件失败：设置参数错误
                'extension_missing' => 'Extension missing: ',       //--- 发送邮件失败：环境异常，未开启openssl拓展
                'mailbox_not_found' => 'Mailbox not found', //  接收地址不存在、或者接收地址被禁用，请与收件人确认正确的邮件地址。
            ];
            $transMessage = [
                'authenticate' => trans('webmail.authenticate'),
                'connect_host' => trans('webmail.connect_host'),
                'data_not_accepted' => trans('webmail.data_not_accepted'),
                'empty_message' => trans('webmail.empty_message'),
                'instantiate' => trans('webmail.instantiate'),
                'mailer_not_supported' => trans('webmail.mailer_not_supported'),
                'provide_address' => trans('webmail.provide_address'),
                'recipients_failed' => trans('webmail.recipients_failed'),
                'smtp_connect_failed' => trans('webmail.smtp_connect_failed'),
                'encoding' => trans('webmail.encoding'),
                'execute' => trans('webmail.execute'),
                'file_access' => trans('webmail.file_access'),
                'file_open' => trans('webmail.file_open'),
                'from_failed' => trans('webmail.from_failed'),
                'invalid_address' => trans('webmail.invalid_address'),
                'signing' => trans('webmail.signing'),
                'smtp_error' => trans('webmail.smtp_error'),
                'variable_set' => trans('webmail.variable_set'),
                'extension_missing' => trans('webmail.extension_missing'),
                'mailbox_not_found' =>trans('webmail.mailbox_not_found')
            ];
            if (in_array($errorMessage, $directMessages)) {
                $key = array_search($errorMessage, $directMessages);
            } else {
                $key = '';
                foreach ($indirectMessages as $messageKey => $message) {
                    if (strpos($errorMessage, $message) !== false) {
                        $key = $messageKey;
                        break;
                    }
                }
            }
            if (in_array($key, ['mailbox_not_found'])) {
                return $transMessage[$key] ? $transMessage[$key] . $errorMessage : trans('webmail.send_mail_error') . $errorMessage;
            } else {
                return $transMessage[$key] ?? trans('webmail.send_mail_error') . $errorMessage;
            }
        } else if ($type == 'receive') {
            if (strpos($errorMessage, 'Connection error') !== false) {
                $defaultMessage = trans('webmail.connection_error');
                if (strpos($errorMessage, 'user name') !== false || strpos($errorMessage, 'password') !== false) {
                    $defaultMessage = trans('webmail.connection_error_name_or_password');
                }else if (strpos($errorMessage, 'unknown host') !== false) {
                    $defaultMessage = trans('webmail.connection_error_host');
                }else if (strpos($errorMessage, 'port') !== false) {
                    $defaultMessage = trans('webmail.connection_error_name_or_password');
                }else if (strpos($errorMessage, 'authorized code') !== false) {
                    $defaultMessage = trans('webmail.connection_error_authorized_code');
                }
            }
            return $defaultMessage ?? trans('webmail.receive_mail_error') . $errorMessage;
        } else {
            return $errorMessage;
        }
    }

    public function getLogs($param, $userId)
    {
        $param = $this->parseParams($param);
        if (!isset($param['search']['creator'])) {
            $param['search']['creator'] = [$userId];
        }
        return $this->response(app($this->webmailSendReceiveLogRepository), 'getNum', 'getLogs', $param);
    }

    /** 检测邮箱地址格式是否正确
     * @param $account 邮箱地址
     * @return bool
     */
    public function checkMailAccount($account)
    {
        $para = "/^([0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*\.)+[a-zA-Z]*)$/u";
        if(preg_match($para, $account)){
            return true;
        }else{
            return false;
        }
    }

    private function handleSomeOutbox($outbox)
    {
        if(isset($outbox['account']) && $outbox['account']){
            $accountArray = explode('@',$outbox['account']);
            // $accountArray[1] == 'mail.jxinfo.com.tw')
            if($accountArray && is_array($accountArray) && isset($accountArray[1]) && ($accountArray[1] == 'mjpm.cn')){
                $outbox['account_cut'] = 1;
            }
        }
        return $outbox;
    }

    public function autoCustomerContactRecord($mail)
    {
        // 两种数据  --- customer_1010  linkmna_1010
        $customerLinkmans = $mail['customer_linkman'] ?? '';
        $customersArray = explode(';', $customerLinkmans);
        $customerLinkmanArray = $customerArray1 = [];
        // 分别处理
        foreach ($customersArray as $one) {
            if (strpos($one, 'linkman_') !== false) {
                $customerLinkmanArray[] = str_replace('linkman_', '', $one);
            }
            if (strpos($one, 'customer_') !== false) {
                $customer1 = ['customer_id' => str_replace('customer_', '', $one)];
                $customerArray1[] = $customer1;
            }
        }
        $customers = [];
        if ($customerLinkmanArray) {
            $customers = app($this->linkmanRepository)->getFieldInfo(null, ['customer_id', 'linkman_id'], ['linkman_id' => [$customerLinkmanArray, 'in']]);
        }
        // 合并数据
        if (is_array($customers) && is_array($customerArray1)){
            $customers = array_merge($customers, $customerArray1);
        }
        if ($customers && isset($mail['mail_creator']) && !empty($mail['mail_creator'])) {
            $mailSubject = $mail['subject'];
            $url = '<p><br /><a style="color: #2a6496; font-size: 14px; line-height: 18px;" class="mceNonEditable" target="_blank" href="##{&quot;webmail&quot;:{&quot;mail_id&quot;:'.$mail['mail_id'].',&quot;outbox_id&quot;:'.$mail['outbox_id'].',&quot;type&quot;:&quot;relation&quot;}}##" rel="noopener noreferrer">'. $mailSubject .'</a></p>';
            $data = [
                'record_content' => $url,
                'record_type' => 2,
                'record_start' => $mail['mail_time'] ?? date('Y-m-d H:i:s'),
//                'record_end' => date('Y-m-d H:i:s'),
                'record_creator' => $mail['mail_creator']
            ];
            $customerSend = [];
            $webmailCustomerRecordLogs = [];
            $current = date('Y-m-d H:i:s');
            $result = app($this->userRepository)->getUserAllData($mail['mail_creator'])->toArray();
            // 组装个人信息 -- 插入联系记录验证权限使用
            $own = [];
            if($result){
                $role_ids = [];
                foreach ($result['user_has_many_role'] as $key => $vo) {
                    $role_ids[] = $vo['role_id'];
                }
                $own = [
                    'user_id' => $mail['mail_creator'],
                    'dept_id' => $result['user_has_one_system_info']['dept_id'],
                    'role_id' => $role_ids,
                ];
            }
            foreach ($customers as $customer) {
                $linkmanId = $customer['linkman_id'] ?? '';
                if (!in_array($customer['customer_id']. '_' . $linkmanId, $customerSend)) {
                    $customerSend[] = $customer['customer_id']. '_' . $linkmanId;
                    $data['customer_id'] = $customer['customer_id'];
                    $data['linkman_id'] = $linkmanId;
                    // store 创建联系记录涉及客户相关权限问题
                    $res = app($this->customerContactRecordService)->store($data, $own);
                    $errorMessage = '';
                    if (!isset($res['record_id'])) {
                        $code = $res['code'] ?? [];
                        $codeKey = isset($code[1]) ? $code[1] : '';
                        $codeValue = isset($code[0]) ? $code[0] : '';
                        if ($code && $codeKey && $codeValue) {
                            $errorMessage = trans($codeKey. '.' . $codeValue);
                        } else {
                            $errorMessage = trans('webmail.create_record_failed');
                        }
                    }
                    $webmailCustomerRecordLogs[] = [
                        'mail_id' => $mail['mail_id'],
                        'customer_id' => $customer['customer_id'],
                        'linkman_id' => $linkmanId,
                        'record_id' => $res['record_id'] ?? 0,
                        'operator' => $mail['mail_creator'],
                        'result' => json_encode($res),
                        'error_message' => $errorMessage,
                        'created_at' => $current,
                    ];
                }
            }
            if ($webmailCustomerRecordLogs) {
                app($this->webmailCustomerRecordRepository)->insertMultipleData($webmailCustomerRecordLogs);
            }
        }
        return true;
    }

    public function getRecords($param)
    {
        $param = $this->parseParams($param);
        $data  = $this->response(app($this->webmailCustomerRecordRepository), 'getNum', 'getList', $param);
        return $data;
    }

    public function getWebMailFolders($outboxId, $userId)
    {
        $inboxServer = $this->getInboxServer($outboxId, 'inbox');
        if (isset($inboxServer['code'])) {
//            $outbox = app($this->webmailOutboxRepository)->getDetail($outboxId)->toArray();
//            $sendLog = [
//                'outbox_info' => json_encode($outbox),
//                'outbox_id' => $outboxId ?? 0,
//                'mail_id' => 0,
//                'mail_info' => '',
//                'type' => 2, // 操作类型，0：服务校验 1：发件 2：收件
//                'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
//                'result_info' => trans('webmail.0x050002'),
//                'creator' => $userId
//            ];
//            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return $inboxServer;
        }
        // 获取邮箱未读邮件个数
        try{
            $folders = app($this->email)->getWebMailFolders();
            return $folders;
        }catch (\Exception $e){
            $message = $this->handleMessage($e->getMessage(), 'receive');
//            $sendLog = [
//                'outbox_info' => json_encode($inboxServer),
//                'outbox_id' => $outboxId ?? 0,
//                'mail_id' => 0,
//                'mail_info' => '',
//                'type' => 2, // 操作类型，0：服务校验 1：发件 2：收件
//                'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
//                'result_info' => $message,
//                'creator' => $userId
//            ];
//            app($this->webmailSendReceiveLogRepository)->insertData($sendLog);
            return ['code' => ['', $inboxServer['account'] . $message]];
        }
    }

    /** 通过server值获取name值【处理默认的邮箱名，兼容之前的处理】
     * @param $server
     * @return int|string
     */
    public function getFolderByServer($server)
    {
        $folderName = explode('}', $server);
        $name = $folderName[1];
        $folder = app($this->webmailFolderService)->getFolderNameByName(strtolower($name));
        if (!$folder) {
            $folderInfo = app($this->webmailFolderRepository)->getOneFieldInfo(['server' => $server]);
            $folder = $folderInfo ? $folderInfo['folder_id'] : 0;
        }
        return $folder;
    }

    /** 处理IMAP同步的错误信息
     * @param $message
     * @return mixed
     */
    public function handleImapMessage($message)
    {
        $errors = [
            'Could not create mailbox!' => trans('webmail.Could_not_create_mailbox'),
            'Could not delete mailbox!' => trans('webmail.Could_not_delete_mailbox'),
            'Could not rename mailbox!' => trans('webmail.Could_not_rename_mailbox'),
            'Could not move messages!' => trans('webmail.Could_not_move_messages'),
            'Could not delete message from mailbox!' => trans('webmail.Could_not_delete_message_from_mailbox'),
        ];
        return $errors[$message] ?? $message;
    }
}
