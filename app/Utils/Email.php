<?php

namespace App\Utils;

use Log;
use PhpImap;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Eoffice;

ini_set("memory_limit","516M");
/**
 * 外部邮件类
 *
 * @author qishaobo
 *
 * @since  2016-08-04 创建
 */
class Email
{
    /**
     * 发送邮件
     *
     * @param array $param 发送参数
     *
     * @return bool|array 错误信息
     *
     * @author qishaobo
     *
     * @since  2016-08-04
     */
    public function sendMail($param = [])
    {
        $webmailService = app('App\EofficeApp\Webmail\Services\WebmailService');
        $mail = $this->setSmtp($param);
        $mail->setFrom($param['from'], $param['send_nickname'] ?? '');

        $mailTos = array_filter(explode(';', $param['to']));
        $mailId = $param['mail_id'] ?? 0;
        try{
            foreach ($mailTos as $mailTo) {
                if (strrpos($mailTo, '<') !== false) {
                    $mailToPart = explode("<", $mailTo);
                    $mail->addAddress($mailToPart[0], rtrim($mailToPart[1], '>'));
                } else {
                    $mail->addAddress($mailTo);
                }
            }

            //$mail->addAddress($param['to']);

            if (!empty($param['cc'])) {
                $cc = array_filter(explode(';', $param['cc']));
                foreach ($cc as $v) {
                    $mail->addCC($v);
                }
            }

            if (!empty($param['bcc'])) {
                $bcc = array_filter(explode(';', $param['bcc']));
                foreach ($bcc as $v) {
                    $mail->addBCC($v);
                }
            }

            if (!empty($param['addAttachment'])) {
                foreach ($param['addAttachment'] as $v) {
                    $mail->addAttachment($v['file'], $v['file_name']);
                }
            }

            if ($param['isHTML']) {
                $mail->isHTML(true);
            }

            $mail->Subject = $param['subject'];
            // 处理发信内容中图片信息
            $preg = preg_match_all('/<img src="server\/public\/api\/attachment\/index\/(.*?)\/>/', $param['body'], $matches);
            if ($preg !== false && isset($matches) && !empty($matches)) {
                if (isset($matches[1])){
                    $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
                    foreach ($matches[1] as $matchKey => $matchValue) {
                        $matchValueArray = explode('?', $matchValue);
                        $attachmentId = $matchValueArray[0] ?? '';
                        if ($attachmentId) {
                            // 获取base64数据流 组装img数据替换原有数据
                            $base64 = $attachmentService->getCustomerFace($attachmentId, true);
                            if ($base64) {
                                $replace = '<img src="'. $base64 . '" />';
                                $param['body'] = str_replace($matches[0][$matchKey], $replace, $param['body']);
                            }              
                        }
                    }
                }
            }
            $mail->Body    = $param['body'];
            // 邮箱地址有些情况会通过验证但是发送失败 增加正则验证 如QQ邮箱后面加几个数字
            if ($mailId && !$webmailService->checkMailAccount($param['username'])){
                $message = trans('webmail.outbox_account_format_error');
                app('App\EofficeApp\Webmail\Repositories\WebmailSendReceiveLogRepository')->updateData(['result_status' => 2, 'result_info' => $message], ['mail_id' => $mailId]);
                return ['mailerError' => $message];
            }
            $res = $mail->send();
            if ($res && $mailId) {
                app('App\EofficeApp\Webmail\Repositories\WebmailSendReceiveLogRepository')->updateData(['result_status' => 1], ['mail_id' => $mailId]);
                $webmailService->autoCustomerContactRecord($param);
            }
            return true;
        } catch (\Exception $e) {
            if ($mailId) {
                $message = $webmailService->handleMessage($mail->ErrorInfo);
                app('App\EofficeApp\Webmail\Repositories\WebmailSendReceiveLogRepository')->updateData(['result_status' => 2, 'result_info' => $message], ['mail_id' => $mailId]);
                return ['mailerError' => $message];
            }
            return ['mailerError' => $mail->ErrorInfo];
        }
//        if ($mail->send()) {
//            return true;
//        }

//        Log::error('Email error ' . $mail->ErrorInfo);
//
//        return ['mailerError' => $mail->ErrorInfo];
    }

    /**
     * 连接SMTP邮件服务器
     *
     * @param array $param 发送参数
     *
     * @return bool 错误信息
     *
     * @author qishaobo
     *
     * @since  2016-08-04
     */
    public function smtpConnect($param = [])
    {
        $mail          = $this->setSmtp($param);
        $mail->Timeout = 10; //连接10s超时
        return $mail->smtpConnect();
    }

    /**
     * 设置SMTP邮件服务器
     *
     * @param array $param 发送参数
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-08-04
     */
    public function setSmtp($param = [])
    {
//        require base_path('vendor/phpmailer/phpmailer/PHPMailerAutoload.php');

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet  = 'UTF-8';
        $mail->Host     = $param['host'];
        $mail->SMTPAuth = isset($param['smtp_auth']) ? $param['smtp_auth'] : true;
        $mail->Username = $param['username'];
        $mail->Password = $param['password'];

        if (isset($param['smtp_ssl']) && $param['smtp_ssl'] == 1) {
            $mail->SMTPSecure = 'ssl';
        }
        if (isset($param['port']) && $param['port'] == '587') {
            $accountArray = explode('@', $param['username']);
            if($accountArray && is_array($accountArray) && isset($accountArray[1]) && $accountArray[1] == 'nanpao.com'){
            } else {
                $mail->SMTPSecure = 'tls';
            }
        }
        if (isset($param['auto_tls']) && $param['auto_tls'] === 0) {
            $mail->SMTPAutoTLS = false;
        }
        $mail->Port = $param['port'];
        return $mail;
    }

    /**
     * 接收邮件
     *
     * @param array $param 接收参数
     * @param array $receive 接收邮件id
     * @param string $userId 操作人id
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-08-04
     */
    public function receiveMail($param, $receive, $userId)
    {
        $mailbox               = $this->inboxServer($param);
        $webmailMailRepository = app('App\EofficeApp\Webmail\Repositories\WebmailMailRepository');
        $attachmentService     = app('App\EofficeApp\Attachment\Services\AttachmentService');
        $webmailService        = app('App\EofficeApp\Webmail\Services\WebmailService');
        // 处理folder
        $folder = $webmailService->getFolderByServer($param['server']);
        $extraParam = [
            'outbox_id'    => $param['outbox_id'],
            // 'mail_to'      => $param['account'],
            'folder'       => $folder ?? 'inbox',
            'is_read'      => 0,
            'mail_creator' => $userId,
            'create_time'  => date('Y-m-d H:i:s'),
        ];

        $this->generateEmailDir();
        $basePath       = base_path() . '/';
        $attachmentPath = 'public/attachment/email/';

        $attachmentBasePath = str_replace(['/', '\\'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $basePath . $attachmentPath);
        $mail_infos = [];
        foreach ($receive as $uid) {
            $data = $extraParam;
            try {
                $mail = $mailbox->getMail($uid);
                $fromName = $mail->fromName ? transEncoding($mail->fromName, 'UTF-8') : '';
                $data['mail_from']    = $fromName ? $fromName . '<' . $mail->fromAddress . '>' : $mail->fromAddress;
                $data['mail_subject'] = transEncoding($mail->subject, 'UTF-8');
                $data['mail_time']    = $mail->date;
                $data['mail_body']    = $mail->textHtml ? transEncoding($mail->textHtml, 'UTF-8') : '';
                $cc                   = $mail->cc;
                $data['cc']           = '';
                $data['mail_to']      = '';
                $data['mail_to'] = $mail->toString ? transEncoding($mail->toString, 'UTF-8'): $param['account'];
                $data['mail_uid'] = $uid;
//            if (!empty($mail_to)) {
//                $mail_to_users = '';
//                foreach ($mail_to as $to_key => $to_item) {
//                    $data['mail_to'] = $to_key . ';';
//                }
//                $data['mail_to'] = rtrim($data['mail_to'], ';');
//            }
                if (!empty($cc)) {
                    foreach ($cc as $k => $v) {
                        $data['cc'] .= $v.'<'.$k .'>'. ',';
                    }
                    $data['cc'] = rtrim($data['cc'], ';');
                }

                $attachments         = $mail->getAttachments();
                $attachmentIds       = [];
                $imagesExts          = config('eoffice.uploadImages');
                $thumbAttachmentName = "";
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        $attachmentId = md5(time() . $attachment->id . rand(1000000, 9999999));
                        $filePath     = $attachment->filePath;
                        $fileSize     = filesize($filePath);
                        $fileName     = str_replace($attachmentBasePath, '', $filePath);
                        $temp_arr     = explode('.', $fileName);
//                    $fileType     = isset($temp_arr[1]) ? $temp_arr[1] : '';
                        $fileType     = count($temp_arr) > 0 ? end($temp_arr) : '';

                        if (in_array($fileType, $imagesExts)) {

                            $thumbWidth  = isset($data["thumbWidth"]) && $data["thumbWidth"] ? $data["thumbWidth"] : config('eoffice.thumbWidth', 100);
                            $thumbHight  = isset($data["thumbHight"]) && $data["thumbHight"] ? $data["thumbHight"] : config('eoffice.thumbHight', 40);
                            $thumbPrefix = config('eoffice.thumbPrefix', "thumb_");

                            $thumbAttachmentName = scaleImage($basePath . $attachmentPath . $fileName, $thumbWidth, $thumbHight, $thumbPrefix);
                        }

                        $uploadFileStatusTemp = config('eoffice.uploadFileStatus');
                        if (in_array($fileType, $uploadFileStatusTemp[1])) {
                            $attachmentFile = 1;
                        } else if (in_array($fileType, $uploadFileStatusTemp[2])) {
                            $attachmentFile = 2;
                        } else if (in_array($fileType, $uploadFileStatusTemp[3])) {
                            $attachmentFile = 3;
                        } else {
                            $attachmentFile = 9;
                        }

                        $attachmentData = [
                            "attachment_id"          => $attachmentId,
                            "attachment_name"        => $attachment->name,
                            "affect_attachment_name" => $fileName,
                            "thumb_attachment_name"  => $thumbAttachmentName,
                            "attachment_type"        => $fileType,
                            "attachment_size"        => $fileSize,
                            "attachment_create_user" => $userId,
                            "attachment_base_path"   => $basePath,
                            "attachment_path"        => $attachmentPath,
                            "attachment_mark"        => $attachmentFile,
                            "attachment_time"        => date("Y-m-d H:i:s"),
                            "relation_table"         => '',
                            "rel_table_code"         => '',
                            'new_full_file_name'     => '',
                        ];

//                    $attachmentService->addAttachment($attachmentData);
                        $attachmentService->handleAttachmentDataTerminal($attachmentData);
                        $attachment = $attachmentService->getOneAttachmentById($attachmentId);
                        // 正则匹配后替换 2020-08-21 发现多张图片时位置反了 调整 根据附件名称替换
                        $attachmentNameArray = explode('.', $attachmentData['attachment_name']);
                        array_pop($attachmentNameArray);
                        $attachmentName = implode('.', $attachmentNameArray);
                        if ($attachmentName && strpos($data['mail_body'], $attachmentName) !== false) {
                            $replace = '../../server/'.$attachment['attachment_relative_path'].$attachment['affect_attachment_name'];
                            $data['mail_body'] = str_replace('cid:'. $attachmentName, $replace, $data['mail_body']);
                        } else {
                            // $attachmentIds[] = $attachmentId;
                            $preg = preg_match('/src="cid:(.*?)"/is', $data['mail_body'], $matches);
                            if ($preg !== false && isset($matches) && !empty($matches)) {
                                if (isset($matches[1])){
                                    $replace = '../../server/'.$attachment['attachment_relative_path'].$attachment['affect_attachment_name'];
                                    $data['mail_body'] = str_replace('cid:'. $matches[1], $replace, $data['mail_body']);
                                }
                            } else {
                                $attachmentIds[] = $attachmentId;
                            }
                        }
                    }
                }
                $webmailMailObj = $webmailMailRepository->insertData($data);
            } catch (\Exception $exception){
                $webmailMailObj = false;
                $message = $exception->getMessage();
            }
            $data['mail_id'] = 0;
            if(!$webmailMailObj){
                $mail_infos[] = $data;
                // return true;
                if (isset($message) && $message) {
                    //添加消息推送
                    $sendData['remindMark']   = 'webmail-receive_failed';
                    $sendData['toUser']       = [$userId];
                    $sendData['contentParam'] = ['failedReason' => $message];
                    $sendData['stateParams']  = ['type'=>'webmail-receiveFailed'];
                    Eoffice::sendMessage($sendData);
                }
            } else {
                if ($webmailMailObj && is_object($webmailMailObj)) {
                    $webmail_obj_id = $webmailMailObj->mail_id ? $webmailMailObj->mail_id : 0;
                    $data['mail_id'] = $webmail_obj_id;
                    $mail_infos[] = $data;
                    if ($webmail_obj_id) {
                        if ($attachmentIds) {
                            $attachmentService->attachmentRelation("webmail_mail", $webmail_obj_id, $attachmentIds);
                        }
                        //添加消息推送
                        $sendData['remindMark']   = 'webmail-receive';
                        $sendData['toUser']       = [$userId];
                        $sendData['contentParam'] = ['emailSubject' => $data['mail_subject'], 'mailFrom' => transEncoding($data['mail_from'], 'UTF-8')]; //当前登录
                        $sendData['stateParams']  = ['mail_id' => $webmailMailObj['mail_id'] ? intval($webmailMailObj['mail_id']):intval($webmailMailObj->mail_id),'outbox_id'=>$data['outbox_id'],'type'=>'webmail-receive'];
                        Eoffice::sendMessage($sendData);
                    }
                }
            }
        }
        // 记录日志
        $receiveLogs = [];
        $createdTime = date("Y-m-d H:i:s");
        foreach ($mail_infos as $key => $mail_info) {
            if ($mail_info) {
                $receiveLogs[$key]['outbox_info'] = json_encode($param);
                $receiveLogs[$key]['outbox_id'] = $param['outbox_id'] ?? 0;
                $receiveLogs[$key]['mail_id'] = $mail_info['mail_id'] ?? 0;
                $receiveLogs[$key]['mail_info'] = json_encode(['mail_subject'=> $mail_info['mail_subject']]);
                $receiveLogs[$key]['type'] = 2;
                $receiveLogs[$key]['result_status'] = $mail_info['mail_id'] ? 1 : 2;
                $receiveLogs[$key]['creator'] = $userId;
                $receiveLogs[$key]['created_at'] = $createdTime;
                $receiveLogs[$key]['updated_at'] = $createdTime;
            }
        }
        if ($receiveLogs) {
            app('App\EofficeApp\Webmail\Repositories\WebmailSendReceiveLogRepository')->insertMultipleData($receiveLogs);
        }
        return true;
    }

    /**
     * 接收邮件信息
     *
     * @param array $param 接收参数
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-08-04
     */
    public function getMailsInfo($param, $type = '')
    {
        ini_set("max_execution_time", 300);
        $mailbox = $this->inboxServer($param);

        error_reporting(E_ALL ^ E_NOTICE);
        // 获取邮箱相关信息  默认 邮件id数组  check 用于检测邮箱状态  nums 用于获取全部邮件个数
        if (!$type) {
            $email = $mailbox->searchMailbox('ALL');
        } else if ($type == 'check') {
            $email = $mailbox->statusMailbox();
        } else if ($type == 'nums') {
            $email = $mailbox->countMails();
        }

        if (empty($email) && $email !== 0 && (strpos($param["server"], '163') !== false || strpos($param["server"], '126') !== false)) {
            return [
                'code'    => ['set163', 'webMail'],
                'dynamic' => '请复制以下地址，设置当前客户端允许收取邮件：http://config.mail.163.com/settings/imap/index.jsp?uid=' . $param['account'],
            ];
        }

        return $email;

    }

    /**
     * 接收邮件服务器信息
     *
     * @param array $param 参数
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-08-04
     */
    public function inboxServer($param)
    {
        // 有些邮箱账号接收值仅为不带@后域名的账号值
        if (isset($param['account_cut']) && $param['account_cut'] == 1) {
            $account = explode('@', $param['account']);
            if (isset($account[0])) {
                $param['account'] = $account[0];
            }
        }
        $this->generateEmailDir();
        $attachmentBasePath = base_path('public/attachment/email/');
        $attachmentBasePath = str_replace(['/', '\\'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $attachmentBasePath);
        // 解决含有中文的邮箱imap_open失败的问题 PhpImap\Imap::encodeStringToUtf7Imap
        $inboxServerObj     = new PhpImap\Mailbox(PhpImap\Imap::encodeStringToUtf7Imap($param['server']), $param['account'], $param['password'], $attachmentBasePath);

        return $inboxServerObj;
    }

    /**
     * 生成email文件夹
     *
     * @param array $param 参数
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-08-04
     */
    public function generateEmailDir()
    {
        $attachmentPath = base_path('public/attachment/');
        if (!is_dir($attachmentPath)) {
            mkdir($attachmentPath, 0777);
        }

        $emailPath = base_path('public/attachment/email/');
        if (!is_dir($emailPath)) {
            mkdir($emailPath, 0777);
        }
    }

    /** 获取邮箱文件夹
     * @param $param
     * @return array
     */
    public function getWebMailFolders($param)
    {
        ini_set("max_execution_time", 300);
        $mailbox = $this->inboxServer($param);
        error_reporting(E_ALL ^ E_NOTICE);
        return $mailbox->getListingFolders();
    }

    public function handleWebmail($param, $type, $userId)
    {
        /** @var PhpImap\Mailbox $mailbox */
        $mailbox = $this->inboxServer($param);
        $sendLog = [
            'outbox_info' => json_encode($param),
            'outbox_id' => $param['outbox_id'] ?? 0,
            'mail_id' => $param['mail_id'] ?? 0,
            'mail_info' => $param['mail_info'] ?? '',
//            'type' => 2, // 操作类型  11：创建文件夹 12：重命名文件夹 13：删除文件夹  14：转移邮件  15：删除邮件
//            'result_status' => 2, // 操作结果 ，0：队列执行中 1:成功 2：失败
            'creator' => $userId
        ];
        try{
            switch ($type) {
                case 'createFolder':
                    $name = $param['folder_name'] ?? '';
                    $sendLog['type'] = 11;
                    $sendLog['request_data'] = json_encode(['folder_name' => $name]);
                    $mailbox->createMailbox($name);
                    break;
                case 'renameFolder':
                    $oldName = $param['old_name'] ?? '';
                    $newName = $param['new_name'] ?? '';
                    $sendLog['type'] = 12;
                    $sendLog['request_data'] = json_encode(compact('oldName', 'newName'));
                    $mailbox->renameMailbox($oldName, $newName);
                    break;
                case 'deleteFolder':
                    $name = $param['folder_name'] ?? '';;
                    $sendLog['type'] = 13;
                    $sendLog['request_data'] = json_encode(['folder_name' => $name]);
                    $mailbox->deleteMailbox($name);
                    break;
                case 'deleteMail':
                    $mailId = $param['mail_uid'] ?? 0;
                    $sendLog['type'] = 15;
                    $sendLog['request_data'] = json_encode(['mail_id'=> $param['mail_id'] ?? 0]);
                    $mailbox->deleteMail($mailId);
                    break;
                case 'moveMail':
                    $mailId = $param['mail_uid'] ?? '';
                    $boxName = $param['mailbox_name'] ?? '';
                    $sendLog['type'] = 14;
                    $sendLog['request_data'] = json_encode(['folder_name' => $boxName, 'origin_mailbox_name' => $param['origin_mailbox_name'] ?? '', 'mail_id'=> $param['mail_id'] ?? 0]);
                    $mailbox->moveMail($mailId, $boxName);
                    $mail = app('App\EofficeApp\Webmail\Repositories\WebmailMailRepository')->getDetail($param['mail_id']);
                    /**
                     * 邮件转移后 对应邮件的uid会变化，要到对应邮箱获取最新的同样主题的邮件的uid 同时记录到已收取的邮件id文件中
                     */
                    if ($mail && isset($mail['mail_uid']) && $mail['mail_uid']) {
                        $mailbox->switchMailbox($boxName);
                        $uid = imap_search($mailbox->getImapStream(), 'SUBJECT "'. $mail['mail_subject'] .'"');
                        if ($uid && count($uid) > 0) {
                            $uid = count($uid) > 1 ? max($uid) : $uid[0];
                            $sendLog['result_info'] = json_encode(['mailbox_name'=> $boxName, 'old_uid' => $mailId, 'new_uid' => $uid]);
                            app('App\EofficeApp\Webmail\Repositories\WebmailMailRepository')->updateData(['mail_uid' => $uid], ['mail_id' => $param['mail_id']]);
                            app('App\EofficeApp\Webmail\Services\WebmailService')->addAlreadyReceiveMail($param['account'], [$uid], $boxName);
                        }
                    }
                    break;
            }
            $mailbox->disconnect();
            $sendLog['result_status'] = 1;
            app('App\EofficeApp\Webmail\Repositories\WebmailSendReceiveLogRepository')->insertData($sendLog);
            return true;
        } catch (\Exception $e) {
            $mailbox->disconnect();
            $message = app('App\EofficeApp\Webmail\Services\WebmailService')->handleImapMessage($e->getMessage());
            $sendLog['result_status'] = 2;
            $sendLog['result_info'] = $message;
            app('App\EofficeApp\Webmail\Repositories\WebmailSendReceiveLogRepository')->insertData($sendLog);
            return ['code' => ['', $message], 'dynamic' => $message];
        }
    }

}
