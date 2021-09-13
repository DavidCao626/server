<?php

namespace App\EofficeApp\Email\Services;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\Email\Repositories\EmailOperatesRepository;
use App\EofficeApp\Email\Repositories\EmailReceiveRepository;
use App\EofficeApp\Email\Repositories\EmailRepository;
use App\EofficeApp\Role\Entities\UserRoleEntity;
use App\Utils\Utils;
use Eoffice;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
/**
 * 邮件服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class EmailService extends BaseService
{
    public function __construct() {
        parent::__construct();
        $this->emailRepository        = 'App\EofficeApp\Email\Repositories\EmailRepository';
        $this->emailBoxRepository     = 'App\EofficeApp\Email\Repositories\EmailBoxRepository';
        $this->userRepository         = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->emailReceiveRepository = 'App\EofficeApp\Email\Repositories\EmailReceiveRepository';
        $this->attachmentRepository   = 'App\EofficeApp\Attachment\Repositories\AttachmentRepository';
        $this->attachmentService      = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->importExportService    = 'App\EofficeApp\ImportExport\Services\ImportExportService';
        $this->userMenuService        = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->userService        = 'App\EofficeApp\User\Services\UserService';
    }

    //ok 邮件文件夹列表
    public function getEmailBoxList($data,$own)
    {
        $data['user_id'] = $own['user_id'];
        //'order_by' => ['box_id' => 'desc'],
        $emailBoxs = $this->response(app($this->emailBoxRepository), "getEmailBoxTotal", "getEmailBoxList", $this->parseParams($data));

        if (isset($data["entry"]) && $data["entry"] == "mobile") {
            return $emailBoxs;
        }

        $result = [];
        $temp   = [];
        foreach ($emailBoxs['list'] as $box) {
            $temp['box_id']   = $box['box_id'];
            $temp['box_name'] = $box['box_name'];
            $temp['unread']   = app($this->emailReceiveRepository)->getUnreadOherFilefolderMailCout($data['user_id'], $box['box_id']);
            $temp['counts']   = app($this->emailReceiveRepository)->getOherFilefolderMailCout($data['user_id'], $box['box_id']);
            array_push($result, $temp);
        }
        $finalData = [
            "total" => $emailBoxs['total'],
            "list"  => $result,
        ];
        return $finalData;
    }

    //左侧不分页
    public function getEmailBoxListAll($data)
    {

        $emailBoxs = app($this->emailBoxRepository)->getEmailBoxListAll($this->parseParams($data));
        $result    = [];
        $temp      = [];
        foreach ($emailBoxs as $box) {
            $temp['box_id']   = $box['box_id'];
            $temp['box_name'] = $box['box_name'];
            $temp['unread']   = app($this->emailReceiveRepository)->getUnreadOherFilefolderMailCout($data['user_id'], $box['box_id']);
            $temp['counts']   = app($this->emailReceiveRepository)->getOherFilefolderMailCout($data['user_id'], $box['box_id']);
            array_push($result, $temp);
        }

        return $result;
    }

    public function getOneBox($data, $box_id, $own)
    {
        $data['user_id'] = $own['user_id'];
        $data = app($this->emailBoxRepository)->infoEmailBox($box_id, $data['user_id']);
        return $data[0];
    }

    //根据条件获取收件箱 未读已读个数
    public function getEmailReceiveNum($data)
    {
        $temp           = [];
        $temp['unread'] = app($this->emailReceiveRepository)->getUnreadOherFilefolderMailCout($data['user_id'], $data['box_id']);
        $temp['counts'] = app($this->emailReceiveRepository)->getOherFilefolderMailCout($data['user_id'], $data['box_id']);
        $temp['star_count']   = EmailReceiveRepository::starCount($data['user_id'], $data['box_id']);
        return $temp;
    }

    //根据条件获取发件箱已读个数
    public function getOutEmailNum($data)
    {
        $temp           = [];
        $temp['counts'] = app($this->emailRepository)->getOutEmailNum($data['user_id']); //发件箱
        return $temp;
    }

    public function getTempEmailNum($data)
    {
        $temp           = [];
        $temp['counts'] = app($this->emailRepository)->getTempEmailNum($data['user_id']);
        return $temp;
    }

    public function gettrashEmailNum($data)
    {
        $temp           = [];
        $temp['counts'] = app($this->emailRepository)->gettrashEmailNum($data['user_id']);
        return $temp;
    }

    /**
     * 增加用户文件夹
     *
     * @param array   $data
     *
     * @return  int
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function addEmailBox($data, $own)
    {
        $data['user_id'] = $own['user_id'];
        $boxInfo = app($this->emailBoxRepository)->infoEmailBoxByName($data['box_name'], $data['user_id']);
        if (count($boxInfo) > 0) {
            return ['code' => ['0x012004', 'email']]; // 该文件夹名称存在
        }
        $boxData = array_intersect_key($data, array_flip(app($this->emailBoxRepository)->getTableColumns()));
        $result  = app($this->emailBoxRepository)->insertData($boxData);
        return $result->box_id;
    }

    /**
     * 编辑文件夹
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editEmailBox($data,$own)
    {
        $data['user_id'] = $own['user_id'];
        $boxInfo = app($this->emailBoxRepository)->infoEmailBox($data['box_id'], $data['user_id']);

        if (count($boxInfo) == 0) {
            return ['code' => ['0x012003', 'email']]; // 系统异常
        }
        if ($data['box_name'] == $boxInfo[0]['box_name']) {
            return true;
        }
        $box = app($this->emailBoxRepository)->infoEmailBoxByName($data['box_name'], $data['user_id']);
        if (count($box) > 0) {
            return ['code' => ['0x012004', 'email']]; // 该文件夹名称存在
        }
        $boxData = array_intersect_key($data, array_flip(app($this->emailBoxRepository)->getTableColumns()));
        return app($this->emailBoxRepository)->updateData($boxData, ['box_id' => $boxData['box_id']]);
    }

    /**
     * 删除文件夹
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20 ok
     */
    public function deleteEmailBox($data, $own)
    {
        $destroyIds = explode(",", $data['box_id']);
        $where      = [
            'box_id'  => [$destroyIds, 'in'],
            'user_id' => [$own['user_id']],
        ];
        //将该用户的所有文件移动到收件箱中
        //获取用户box_id= data['box_id]的所有文件
        $userWhere = [
            'box_id'     => [$destroyIds, 'in'],
            'recipients' => [[$own['user_id']], '='],
        ];
        //回收至收件箱中
        app($this->emailReceiveRepository)->recycleByWhere($userWhere);
        return app($this->emailBoxRepository)->deleteByWhere($where);
    }

    /**
     * 使用签名
     *
     * @param array $data
     *
     * @return array 用户信息
     *
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function useEmailSign($data)
    {
        $data = app($this->userRepository)->getUserAllData($data['user_id']);
        return $data['user_name'];
    }

    /**
     * 新增邮件
     *
     * @param array $data
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function newEmail($data,$own)
    {
        $data['user_id'] = $own['user_id'];
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";

        // 存在全部时获取，且不含通讯受限用户
        $hasAll = array_search('all', $data) !== false;
        $allUserIds = [];
        $hasAll && $allUserIds = $this->getCommunicateUserId($own);
        //收件，抄送，密送对象
        $toIds      = (isset($data['to_id']) && $data['to_id'] != '') ? ($data['to_id'] == "all" ? $allUserIds : explode(',', $data['to_id'])) : "";
        $copys      = (isset($data['copy_to_id']) && $data['copy_to_id'] != '') ? ($data['copy_to_id'] == "all" ? $allUserIds : explode(',', $data['copy_to_id'])) : "";
        $secrets    = (isset($data['secret_to_id']) && $data['secret_to_id'] != '') ? ($data['secret_to_id'] == "all" ? $allUserIds : explode(',', $data['secret_to_id'])) : "";
        $content    = isset($data['content']) && (!empty($data['content'])) ? $data['content'] : "";
        $send_flag  = isset($data['send_flag']) && (!empty($data['send_flag'])) ? $data['send_flag'] : "0"; // 0 save 1 send
        $sms_remind = isset($data['sms_remind']) && (!empty($data['sms_remind'])) ? $data['sms_remind'] : "0";

        // 验证是否被通信限制
        $result = $this->testCommunicateUserId(array_merge($toIds ?: [], $copys ?: [], $secrets ?: []), $own);
        if ($result !== true) {
            return $result;
        }

        $dataFinal = [
            'from_id'   => $data['user_id'],
            'subject'   => $data['subject'],
            'content'   => $content,
            'send_time' => date("Y-m-d H:i:s", time()),
            'send_flag' => $send_flag,
            'deleted'   => 0,
        ];
//        if (!empty($data['attachments'])) {
//            $dataFinal['attachment_id'] = $data['attachments'];
//        }
        $result   = app($this->emailRepository)->insertData($dataFinal);
        if(!$result || $result == ''){
            return ['code' => ['large_error', 'email']];
        }
        $email_id = $result->email_id;
        //将信息插入到接收表
        $toEmail = [
            'email_id'   => $email_id,
            'sms_remind' => $sms_remind,
            'box_id'     => 0,
            'read_flag'  => 0,
            'deleted'    => 0,
        ];

        $smsToUsers = $toEmailData = [];
        if (!empty($toIds)) {
            foreach ($toIds as $toid) {
                $smsToUsers[]            = $toid;
                $toEmail['recipients']   = $toid;
                $toEmail['receive_type'] = "to";
                $toEmailData[]           = $toEmail;
            }
        }
        if (!empty($secrets)) {
            foreach ($secrets as $sid) {
                $smsToUsers[]            = $sid;
                $toEmail['recipients']   = $sid;
                $toEmail['receive_type'] = "secret";
                $toEmailData[]           = $toEmail;
            }
        }
        if (!empty($copys)) {
            foreach ($copys as $cid) {
                $smsToUsers[]            = $cid;
                $toEmail['recipients']   = $cid;
                $toEmail['receive_type'] = "copy";
                $toEmailData[]           = $toEmail;
            }
        }
        app($this->emailReceiveRepository)->insertMultipleData($toEmailData);

        if ($data['attachments']) {
            $attachmentObj = explode(",", $data['attachments']);
            $attachmentObj = $this->checkExistsAttachments($attachmentObj, $own);
            //执行插入消息表操作
            app($this->attachmentService)->attachmentRelation("email", $email_id, $attachmentObj);
        }

        if ($send_flag == 1) {
            //保存时 不推送系统消息
            //发送消息
            //获取用户的名称：
            $this->sendEmailRemind($data['subject'], $own['user_name'], $email_id, array_unique($smsToUsers));

        }
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($email_id);

        $this->createOperateData($data, $email_id, $own['user_id']);

        return $result;
    }

    // 创建操作数据：转发、回复
    private function createOperateData($input, $emailId, $userId) {
        $type = Arr::get($input, 'operate_type');
        $originEmailId = Arr::get($input, 'origin_email_id');
        if ($type && $originEmailId) {
            EmailOperatesRepository::createOperateData($type, $originEmailId, $emailId, $userId);
        }

    }

    // 检查已存在的附件id并替换
    private function checkExistsAttachments($attachmentIds, $own)
    {
        $existAttachmentIds = \DB::table('attachment_relataion_email')
            ->whereIn('attachment_id', $attachmentIds)
            ->pluck('attachment_id')->toArray();
        if ($existAttachmentIds) {
            $sourceData = [];
            foreach ($existAttachmentIds as $id) {
                $sourceData[] = [
                    'source_attachment_id' => $id,
                    'attachment_table' => 'email'
                ];
            }
            $newAttachmentIds = app($this->attachmentService)->attachmentCopy($sourceData, $own);
            $newAttachmentIds = Arr::pluck($newAttachmentIds, 'attachment_id');
            $attachmentIds = array_merge(array_diff($attachmentIds, $existAttachmentIds), $newAttachmentIds);
        }

        return $attachmentIds;
    }

    /**
     * 发送邮件调用 systemSendEmail
     */
    public function systemSendEmail($data)
    {
        $content    = isset($data['content']) && (!empty($data['content'])) ? $data['content'] : "";
        $send_flag  = 1; // 0 save 1 send
        $sms_remind = isset($data['sms_remind']) && (!empty($data['sms_remind'])) ? $data['sms_remind'] : "0";

        $dataFinal = [
            'from_id'   => $data['user_id'],
            'subject'   => str_replace('&nbsp;', '', strip_tags(htmlspecialchars_decode($data['subject']))), // @过来的消息，会发送html过来，需要过滤
            'content'   => $content,
            'send_time' => date("Y-m-d H:i:s", time()),
            'send_flag' => $send_flag,
            'deleted'   => 0,
            'contentParam' => json_encode(isset($data['contentParam']) ? $data['contentParam'] : '')
        ];

        $result   = app($this->emailRepository)->insertData($dataFinal);
        $email_id = $result->email_id;
        //将信息插入到接收表
        $toEmail = [
            'email_id'   => $email_id,
            'sms_remind' => $sms_remind,
            'box_id'     => 0,
            'read_flag'  => 0,
            'deleted'    => 0,
        ];

        $smsToUsers = "";

        if (is_array($data['to_id'])) {
            $toIds = $data['to_id'];
        } else {
            $toIds = explode(",", $data['to_id']);
        }

        foreach ($toIds as $toid) {
            if ($toid) {
                $smsToUsers .= $toid . ",";
                $toEmail['recipients']   = $toid;
                $toEmail['receive_type'] = "to";
                app($this->emailReceiveRepository)->insertData($toEmail);
            }
        }

        return $result;
    }

    /**
     * 删除邮件
     *
     * @param array $data
     *
     * @return int 1
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteEmail($data,$own)
    {
        $data['user_id'] = $own['user_id'];
        //发件箱 草稿箱  type = send
        //收件箱 receive
        $data['user_id'] = $own['user_id'];
        $emailIds = explode(",", $data['email_id']);
        if ($data['type'] == 'send') {
            $where = [
                'email_id' => [$emailIds, 'in'],
                'from_id'  => [[$data['user_id']], "="],
            ];
            $result = app($this->emailRepository)->deleteEmail($where);
        } else {
            $where = [
                'email_id'   => [$emailIds, 'in'],
                'recipients' => [[$data['user_id']], "="],
            ];
            $result = app($this->emailReceiveRepository)->deleteEmail($where);

            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($emailIds);
        }
        return $result;
    }

    /**
     * 删除系统邮件
     */
    public function systemEmailDelete($data)
    {
        $emailIds = explode(",", $data['id']);
        $where    = [
            'email_id'   => [$emailIds, 'in'],
            'recipients' => [[$data['user_id']], "="],
        ];
        return app($this->emailReceiveRepository)->deleteEmail($where);
    }

    /**
     * 编辑邮件
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    //发件箱中的编辑操作其实是 新增
    //草稿箱
    public function editEmail($data,$own = null)
    {
        $data['user_id'] = $own['user_id'];
        // 草稿箱
        //对于邮件表是更新
        //对于收件表示删除后在添加
        $to_id               = $data['to_id'];
        $copy_to_id          = isset($data['copy_to_id']) && (!empty($data['copy_to_id'])) ? $data['copy_to_id'] : "";
        $secret_to_id        = isset($data['secret_to_id']) && (!empty($data['secret_to_id'])) ? $data['secret_to_id'] : "";
        $content             = isset($data['content']) && (!empty($data['content'])) ? $data['content'] : "";
        $send_flag           = isset($data['send_flag']) && (!empty($data['send_flag'])) ? $data['send_flag'] : "0"; // 0 save 1 send
        $sms_remind          = isset($data['sms_remind']) && (!empty($data['sms_remind'])) ? $data['sms_remind'] : "0";
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";

        $dataFinal = [
            'from_id'   => $data['user_id'],
            'subject'   => $data['subject'],
            'content'   => $content,
            'send_time' => date("Y-m-d H:i:s", time()),
            'send_flag' => $send_flag,
            'deleted'   => 0,
        ];

        if ($send_flag == 1) {
            $dataFinal['send_time'] = date("Y-m-d H:i:s", time());
        }

        app($this->emailRepository)->updateData($dataFinal, ["email_id" => $data['email_id']]);

        //删除收件表
        app($this->emailReceiveRepository)->reallyDeleteByWhere(['email_id' => [[$data['email_id']], "="]]);
        //将信息插入到接收表
        $toEmail = [
            'email_id'   => $data['email_id'],
            'sms_remind' => $sms_remind,
            'box_id'     => 0,
            'read_flag'  => 0,
            'deleted'    => 0,
        ];

        if ($to_id == "all") {
            $to_id = Utils::getUserIds();
        }

        if ($copy_to_id == "all") {
            $copy_to_id = Utils::getUserIds();
        }

        if ($secret_to_id == "all") {
            $secret_to_id = Utils::getUserIds();
        }

        $toIds = explode(",", $to_id);
        foreach ($toIds as $toid) {
            if ($toid) {
                $toEmail['recipients']   = $toid;
                $toEmail['receive_type'] = "to";

                app($this->emailReceiveRepository)->insertData($toEmail);
            }
        }
        $secrets = explode(",", $secret_to_id);
        foreach ($secrets as $sid) {
            if ($sid) {
                $toEmail['recipients']   = $sid;
                $toEmail['receive_type'] = "secret";
                app($this->emailReceiveRepository)->insertData($toEmail);
            }
        }

        $copys = explode(",", $copy_to_id);
        foreach ($copys as $cid) {
            if ($cid) {
                $toEmail['recipients']   = $cid;
                $toEmail['receive_type'] = "copy";
                app($this->emailReceiveRepository)->insertData($toEmail);
            }
        }

        $attachments = isset($data['attachments']) ? $data['attachments'] : "";
        //执行插入消息表操作
        app($this->attachmentService)->attachmentRelation("email", $data['email_id'], $attachments);

        if ($send_flag == 1) {
            $allUserId = array_unique(array_merge($toIds, $secrets, $copys));
            $this->sendEmailRemind($dataFinal['subject'], $own['user_name'], $data['email_id'], $allUserId);
        }

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($data['email_id']);

        return $data['email_id'];
    }

    /**
     * 获取所有的类型 拼接得到 查询和转移使用
     */
    public function emailTypes($data, $type, $user_info = null)
    {
        $data['user_id'] = $user_info['user_id'];
        $data["fields"] = "box_id,box_name";
        $emailBoxs      = $this->response(app($this->emailBoxRepository), "getEmailTypesTotal", "getEmailTypes", $this->parseParams($data));

        $result = [];
        $temp   = [];
        if(!app($this->userMenuService)->judgeMenuPermission(24)) {
            //如果没有我的邮件权限，则查询时不显示收件箱，草稿箱，垃圾箱
            return $emailBoxs;
        }
//        if(!in_array(24,$user_info['menus']['menu'])){
//            //如果没有我的邮件权限，则查询时不显示收件箱，草稿箱，垃圾箱
//            return $emailBoxs;
//        }
        foreach ($emailBoxs['list'] as $box) {
            $temp['box_id']   = $box['box_id'];
            $temp['box_name'] = $box['box_name'];
            array_push($result, $temp);
        }

        switch ($type) {
            case "all":

                array_push($result, [
                    "box_id"   => -1,
                    "box_name" => trans("email.inbox"),
                ], [
                    "box_id"   => -2,
                    "box_name" => trans("email.drafts"),
                ], [
                    "box_id"   => -3,
                    "box_name" => trans("email.hair_box"),
                ]);

                $finalData = [
                    "total" => $emailBoxs['total'] + 3,
                    "list"  => $result,
                ];
                break;
            case "custom":

                array_push($result, [
                    "box_id"   => 0,
                    "box_name" => trans("email.inbox"),
                ]);
//                array_push($result, [
                //                    "box_id" => "new",
                //                    "box_name" => "新建文件夹",
                //                ]);

                $finalData = [
                    "total" => $emailBoxs['total'] + 1,
                    "list"  => $result,
                ];

                break;
            case "un-custom":
//                array_push($result, [
                //                    "box_id" => "new",
                //                    "box_name" => "新建文件夹",
                //                ]);
                //
                $finalData = [
                    "total" => $emailBoxs['total'],
                    "list"  => $result,
                ];

                break;
        }
        //添加我的文件夹下过滤掉当前文件夹处理
        if(isset($data['box_id']) && $data['box_id']){
            if($finalData['list'] && is_array($finalData['list'])){
                foreach ($finalData['list'] as $key => $vo){
                    if($data['box_id'] == $vo['box_id']){
                        unset($finalData['list'][$key]);
                    }
                }
            }
            sort($finalData['list']);
            $finalData['total'] = count($finalData['list']);
        }
        return $finalData;
    }

    /**
     * 我的邮件 分隔出我的文件夹 和 常规邮件类别
     */
    public function getMyEmail($data, $own)
    {
        $data['user_id'] = $own['user_id'];

        $custom    = [];
        $temp      = [];
        $emailBoxs = app($this->emailBoxRepository)->getEmailBoxListAll($this->parseParams($data));
        foreach ($emailBoxs as $box) {
            $temp['box_id']   = $box['box_id'];
            $temp['box_name'] = $box['box_name'];
            $temp_unread      = app($this->emailReceiveRepository)->getUnreadOherFilefolderMailCout($data['user_id'], $box['box_id']);
            //  $temp_counts = app($this->emailReceiveRepository)->getOherFilefolderMailCout($data['user_id'], $box['box_id']);
            $temp['unread'] = $temp_unread; // 过去的保留
            $temp['count'] = $temp_unread;
            $temp['box_title_before'] = $temp['box_name'] . trans("email.has") . "(";
            $temp['box_title_end'] = trans("email.unread_mail") . ")";
            array_push($custom, $temp);
        }

        $data["box_id"] = 0;

        $result = [];
        $inboxName = trans("email.inbox");
        $inboxUnread    = app($this->emailReceiveRepository)->getUnreadOherFilefolderMailCout($data['user_id'], 0);
        array_push($result, [
            "box_id"    => -1,
            "box_name"  => $inboxName,
            "box_desc"  => $inboxName,
            "box_title_before" => trans("email.the_inbox_has"),
            "box_title_end" => trans("email.unread_mail"),
            'count' => $inboxUnread
        ]);

        $drafteName = trans("email.drafts");
        $tempbox = $this->getTempEmailNum($data);
        array_push($result, [
            "box_id"    => -2,
            "box_name"  => $drafteName,
            "box_desc"  => $drafteName,
            "box_title_before" => $drafteName . '(',
            "box_title_end" => ')',
            'count' => $tempbox['counts']
        ]);
        $sentName = trans("email.has_been_sent");
        array_push($result, [
            "box_id"    => -3,
            "box_name"  => $sentName,
            "box_desc"  => $sentName,
            "box_title_before" => $sentName,
            "box_title_end" => '',
        ]);
        $deletedName = trans("email.deleted");
        array_push($result, [
            "box_id"    => -4,
            "box_name"  => $deletedName,
            "box_desc"  => $deletedName,
            "box_title_before" => $deletedName,
            "box_title_end" => '',
        ]);

        $finalData = [];

        array_push($finalData, [
            "name" => trans("email.my_mail"),
            "list" => $result,
        ]);
        array_push($finalData, [
            "name" => trans("email.my_folder"),
            "list" => $custom,
        ]);

        return $finalData;
    }

    /**
     * 转移邮件
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20 ok
     */
    public function transferEmail($data,$own)
    {
        $data['user_id'] = $own['user_id'];
        $emailIds = explode(",", $data['email_id']);
        $where    = [
            'email_id'   => [$emailIds, 'in'],
            'recipients' => [[$data['user_id']], "="],
        ];

        $finalData = [
            'box_id' => $data['box_id'],
        ];

        return app($this->emailReceiveRepository)->transferEmail($where, $finalData);
    }

    /**
     * 阅读邮件
     * @param  [type] $id [description]
     * @param  $setRead
     * @return [type]     [description]
     */
    public function readEmail($id, $setRead = true)
    {
        return app($this->emailReceiveRepository)->readEmail($id, $setRead);
    }

    public function getEmailId($data)
    {
        $where = [
            'email_id'   => [[$data['email_id']], "="],
            'recipients' => [[$data['user_id']], "="],
        ];
        $result = app($this->emailReceiveRepository)->getEmailId($where);
        if ($result == -1) {
            return ['code' => ['0x012005', 'email']];
        }
        return $result;
    }

    /**
     * 获取邮件列表信息
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getEmail($data,$own)
    {
        $data['user_id'] = $own['user_id'];
//        $entityIds = app($this->attachmentService)->getFileStatus("attachment_relataion_email", "entity_id");
//        $tempIds   = [];
//        foreach ($entityIds as $k) {
//            $tempIds[] = $k->entity_id;
//        }
        $this->createRelationEmail();
        $email_ids = [];
        switch ($data['box_id']) {
            case -1: // 收件箱 查看user_id  in （to_id）中，box_id=0  user_id  not in deleted_by_toid
                $resultTemp = $this->response(app($this->emailRepository), 'inBoxAllTotal', 'inBoxAllList', $this->parseParams($data));
                $temp       = [];
                $dataFinal  = [];
                if($resultTemp['list'] && is_array($resultTemp['list'])){
                    foreach ($resultTemp['list'] as $key =>$vo){
                        $email_ids[] = $vo['email_id'];
                    }
                }
                $wheres = ['entity_id' => [$email_ids, 'in']];
                $entityIds = app($this->attachmentService)->getEntityIdsFromAttachRelTable("email",$wheres);
                $fromUserIds = Arr::pluck($resultTemp['list'], 'from_id');
                $departments = app($this->userService)->getDeptInfoByUserIds($fromUserIds);
                foreach ($resultTemp['list'] as $v) {

                    $temp['email_id'] = $v['email_id'];
                    $temp['from_id']  = [
                        "from_id"   => $v['from_id'],
                        "user_name" => $this->getFromUserName($v),
                        "dept_name" => Arr::get($departments, $v['from_id'] . '.dept_name', ''),
                    ];
                    $temp['subject']          = $v['subject'];
                    $temp['send_time']        = $v['send_time'];
                    $temp['id']               = $v['id'];
                    $temp['read_flag']        = $v['read_flag'];
                    $temp['star_flag']        = $v['star_flag'];
                    $temp['attchment_status'] = 0;
                    if($entityIds && is_array($entityIds) && in_array($temp['email_id'], $entityIds)){
                        $temp['attchment_status'] = 1;
                    }
//                    if (in_array($temp['email_id'], $tempIds)) {
//                        $temp['attchment_status'] = 1;
//                    }
                    array_push($dataFinal, $temp);
                }
                $result = [
                    "total" => $resultTemp['total'],
                    "list"  => $dataFinal,
                ];

                break;
            case -2: // 草稿箱 查看user_id = from_id   发送状态=0 deleted_by_fromid = 0
                $result    = $this->response(app($this->emailRepository), 'tempBoxAllTotal', 'tempBoxAllList', $this->parseParams($data));
                $temp      = [];
                $dataFinal = [];
                foreach ($result['list'] as $v) {
                    $temp                   = $v;
                    $temp['from_user_name'] = app($this->userRepository)->getUserName($v['from_id']); //;
                    $dataFinal[]            = $temp;
                }
                $result = [
                    "total" => $result['total'],
                    "list"  => $dataFinal,
                ];

                break;
            case -3: // 发件箱 查看user_id from_id 且 发送状态=1  deleted_by_fromid = 0
                $resultTemp = $this->response(app($this->emailRepository), 'outBoxAllTotal', 'outBoxAllList', $this->parseParams($data));
                $temp       = [];
                $dataFinal  = [];
                if($resultTemp['list'] && is_array($resultTemp['list'])){
                    foreach ($resultTemp['list'] as $key =>$vo){
                        $email_ids[] = $vo['email_id'];
                    }
                }
                $wheres = ['entity_id' => [$email_ids, 'in']];
                $entityIds = app($this->attachmentService)->getEntityIdsFromAttachRelTable("email",$wheres);
                foreach ($resultTemp['list'] as $v) {
                    $temp['email_id']         = $v['email_id'];
                    $temp['from_id']          = $v['from_id'];
                    $temp['subject']          = $v['subject'];
                    $temp['send_time']        = $v['send_time'];
                    $recipients               = app($this->emailReceiveRepository)->getGroupRecipients($v['email_id']);
                    if($recipients){
                        if($recipients && is_array($recipients)){
                            foreach ($recipients as $item){
                                $sort[]=$item["list_number"] ? : 0;
                                $sort_user_id[]=$item["recipients"];
                            }
                        }
                        array_multisort($sort,SORT_ASC,$sort_user_id,SORT_ASC,$recipients);
                        unset($sort);
                        unset($sort_user_id);
                    }

                    $temp['from_user_name']   = app($this->userRepository)->getUserName($v['from_id']); //;
                    $to_ids                   = [];
                    $temp['attchment_status'] = 0;
                    if($entityIds && is_array($entityIds) && in_array($temp['email_id'], $entityIds)){
                        $temp['attchment_status'] = 1;
                    }

//                    if (in_array($temp['email_id'], $tempIds)) {
//                        $temp['attchment_status'] = 1;
//                    }

                    foreach ($recipients as $v) {
                        $to_ids[] = [
                            "recipients" => $v['recipients'],
                            "user_name"  => $v['user_name'],
                        ];
                    }

                    $temp['recipients'] = $to_ids;
                    array_push($dataFinal, $temp);
                }
                $result = [
                    "total" => $resultTemp['total'],
                    "list"  => $dataFinal,
                ];

                break;
            case -4: // 垃圾箱 查看user_id = from_id   发送状态=0 deleted_by_fromid = 0

                $tempTrashIds = app($this->emailRepository)->getTrashEmailIds($data["user_id"]);
                if (!$tempTrashIds) {
                    $data["trash_ids"] = [0]; //空值被过滤掉
                } else {
                    $data["trash_ids"] = $tempTrashIds;
                }

                $resultTemp = $this->response(app($this->emailRepository), 'trashBoxAllTotal', 'trashBoxAllList', $this->parseParams($data));

                $temp      = [];
                $dataFinal = [];
                if($resultTemp['list'] && is_array($resultTemp['list'])){
                    foreach ($resultTemp['list'] as $key =>$vo){
                        $email_ids[] = $vo['email_id'];
                    }
                }
                $wheres = ['entity_id' => [$email_ids, 'in']];
                $entityIds = app($this->attachmentService)->getEntityIdsFromAttachRelTable("email",$wheres);
                foreach ($resultTemp['list'] as $v) {

                    $temp['email_id'] = $v['email_id'];

                    $temp['from_id'] = [
                        "from_id"   => $v['from_id'],
                        "user_name" => $this->getFromUserName($v),
                    ];
                    $temp['subject']   = $v['subject'];
                    $temp['send_time'] = $v['send_time'];
                    $temp['id']        = $v['id'];
                    $temp['read_flag'] = (empty($v['id']) || !$v['send_flag']) ? 1 : $v['read_flag']; // 发件箱不存在这个id,草稿箱也不存在未读，直接设为已读

                    $temp['attchment_status'] = 0;
//                    $temp['contentParam']     = $v['contentParam'];
                    if($entityIds && is_array($entityIds) && in_array($temp['email_id'], $entityIds)){
                        $temp['attchment_status'] = 1;
                    }
//                    if (in_array($temp['email_id'], $tempIds)) {
//                        $temp['attchment_status'] = 1;
//                    }

                    array_push($dataFinal, $temp);
                }

                $result = [
                    "total" => $resultTemp['total'],
                    "list"  => $dataFinal,
                ];

                break;

                break;
            default: //我的文件夹字段
                $resultTemp = $this->response(app($this->emailRepository), 'selfFileBoxAllTotal', 'selfFileBoxAllList', $this->parseParams($data));
                $temp       = [];
                $dataFinal  = [];
                if($resultTemp['list'] && is_array($resultTemp['list'])){
                    foreach ($resultTemp['list'] as $key =>$vo){
                        $email_ids[] = $vo['email_id'];
                    }
                }
                $wheres = ['entity_id' => [$email_ids, 'in']];
                $entityIds = app($this->attachmentService)->getEntityIdsFromAttachRelTable("email",$wheres);
                $fromUserIds = Arr::pluck($resultTemp['list'], 'from_id');
                $departments = app($this->userService)->getDeptInfoByUserIds($fromUserIds);
                foreach ($resultTemp['list'] as $v) {
                    $temp['email_id'] = $v['email_id'];
                    $temp['from_id']  = [
                        "from_id"   => $v['from_id'],
                        "user_name" => $this->getFromUserName($v),
                        "dept_name" => Arr::get($departments, $v['from_id'] . '.dept_name', ''),
                    ];
                    $temp['subject']   = $v['subject'];
                    $temp['send_time'] = $v['send_time'];
                    $temp['id']        = $v['id'];
                    $temp['read_flag'] = $v['read_flag'];
                    $temp['star_flag'] = $v['star_flag'];
                    $temp['attchment_status'] = 0;
                    $temp['contentParam']     = $v['contentParam'];
                    if($entityIds && is_array($entityIds) && in_array($temp['email_id'], $entityIds)){
                        $temp['attchment_status'] = 1;
                    }
//                    if (in_array($v['email_id'], $tempIds)) {
//                        $temp['attchment_status'] = 1;
//                    }
                    array_push($dataFinal, $temp);
                }
                $result = [
                    "total" => $resultTemp['total'],
                    "list"  => $dataFinal,
                ];

                break;
        }
        if ($data['box_id'] == -1 || $data['box_id'] == -4 || $data['box_id'] > 0) {
            $this->setIconFlag($result['list'], $own['user_id']);
        }

        return $result;
    }

    private function setIconFlag(&$list, $userId)
    {
        if (!$list) {
            return;
        }
        $emailIds = Arr::pluck($list, 'email_id');
        $operateLog = EmailOperatesRepository::buildUserEmailQuery($userId, $emailIds)
            ->groupBy('origin_email_id')->groupBy('type')
            ->select('origin_email_id', 'type')
            ->get()->groupBy('type')->toArray();
        $relays = Arr::get($operateLog, 'relay', []);
        $relays = $relays ? Arr::pluck($relays, 'origin_email_id', 'origin_email_id') : [];
        $replies = Arr::get($operateLog, 'reply', []);
        $replies = $replies ? Arr::pluck($replies, 'origin_email_id', 'origin_email_id') : [];
        foreach ($list as &$item) {
            $flag = 1; // 0未读1已读2转发3回复4转发并回复
            $emailId = $item['email_id'];
            array_key_exists($emailId, $relays) && $flag++;
            array_key_exists($emailId, $replies) && $flag += 2;
            $flag == 1 && $flag = intval($item['read_flag']);
            if ($flag == 0) {
                $item['icon_class'] = 'icon-mail-bg';
                $item['icon_title'] = trans('email.unread');
            } elseif ($flag == 1) {
                $item['icon_class'] = 'icon-mail-read';
                $item['icon_title'] = trans('email.read');
            } elseif ($flag == 2) {
                $item['icon_class'] = 'icon-share';
                $item['icon_title'] = trans('email.relay');
            } elseif ($flag == 3) {
                $item['icon_class'] = 'icon-reply';
                $item['icon_title'] = trans('email.reply');
            } elseif ($flag == 4) {
                $item['icon_class'] = 'icon-reply-and-forward';
                $item['icon_title'] = trans('email.reply_and_relay');
            }
        }
    }

    private function createRelationEmail(){
        if(!Schema::hasTable('attachment_relataion_email')){
            Schema::create('attachment_relataion_email', function(Blueprint $table){
                $table->increments('relation_id')->comment('主键自增id');
                $table->Integer('entity_id')->comment('关联邮件id');
                $table->string('attachment_id',100)->comment('附件id');
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function emailLists($data ,$user_info = null )
    {
        $data['user_id'] = $user_info['user_id'];
        if (!isset($data['search_doing'])) {
            return [];
        }
        if(!in_array(24,$user_info['menus']['menu'])){
            //如果没有我的邮件权限，则查询时未选择邮箱给出提示
            if($data['email_box'] == -1 || $data['email_box'] == -2 || $data['email_box'] == -3){
                return ['code' => ['0x012010', 'email']];
            }
        }

//        $entityIds = app($this->attachmentService)->getFileStatus("attachment_relataion_email", "entity_id");
        $tempIds   = $temp   = $dataFinal   = $emailIds   = [];
//        foreach ($entityIds as $k) {
//            $tempIds[] = $k->entity_id;
//        }
        $resultTemp = $this->response(app($this->emailRepository), 'emailListsTotal', 'emailLists', $this->parseParams($data));
        if($resultTemp['list']){
            if($resultTemp['list'] && is_array($resultTemp['list'])){
                foreach ($resultTemp['list'] as $key =>$vo){
                    $email_ids[] = $vo['email_id'];
                }
            }
            $wheres = ['entity_id' => [$email_ids, 'in']];
            $entityIds = app($this->attachmentService)->getEntityIdsFromAttachRelTable("email",$wheres);
            foreach ($resultTemp['list'] as $v) {
                $temp["send_flag"] = $v["send_flag"]; //发送或者保存
                $temp["read_flag"] = $v["read_flag"]; // send_flag = 1时 已读 未读
                $temp['email_id']  = $v['email_id'];
                $temp['email_box'] = $data['email_box'];
                $temp['id']        = $v['id'];
                $temp['from_id']   = [
                    "from_id"   => $v['from_id'],
                    "user_name" => $this->getFromUserName($v),
                ];
                $temp['subject']   = $v['subject'];
                $temp['send_time'] = $v['send_time'];
                $recipients        = app($this->emailReceiveRepository)->getGroupRecipients($v['email_id']);
                $to_ids            = [];
                foreach ($recipients as $v1) {
                    $to_ids[] = [
                        "recipients" => $v1['recipients'],
                        "user_name"  => $v1['user_name'],
                    ];
                }
                $temp['recipients']       = $to_ids;
                $temp['attchment_status'] = 0;
                if($entityIds && is_array($entityIds) && in_array($temp['email_id'], $entityIds)){
                    $temp['attchment_status'] = 1;
                }
//            if (in_array($temp['email_id'], $tempIds)) {
//                $temp['attchment_status'] = 1;
//            }
                array_push($dataFinal, $temp);
            }

            if ($data['email_box'] == -1 || $data['email_box'] == -4 || $data['email_box'] > 0) {
                $this->setIconFlag($dataFinal, $user_info['user_id']);
            }

            $dataFinal = [
                'total' => $resultTemp['total'],
                'list'  => $dataFinal,
            ];

            return $dataFinal;
        }
        return $resultTemp;

    }

    private function process_data($params){
        $params = $this->parseParams($params);
        $data = $params['search'];
        $data['user_id'] = $params['user_id'];
        $data['order_by'] = $params['order_by'];
        $data['limit'] = $params['limit'];
        $data['autoFixPage'] = $params['autoFixPage'];
        $data['page'] = $params['page'];
        isset($data['from_id']) &&  $data['from_id'] = $data['from_id'][0];
        isset($data['recipients']) &&  $data['recipients'] = $data['recipients'][0];
        return $data ? :[];
    }

    //收件箱 详情
    public function getEmailInfo($data, $email_id, $user_id)
    {
        $data['user_id'] = $user_id;
        //邮件Email_id 收件箱的id user_id
        //获取邮件的基本信息
        $dataStatus = app($this->emailReceiveRepository)->getOneEmail($email_id, $user_id);
        //判断草稿箱，已发送邮件箱是否有邮件
//        $dataStatus_send = app($this->emailRepository)->getEmailStatus($email_id,$user_id);
        $nearlyIds = $this->getNearlyId(Arr::get($data, 'grid_params', ''), $email_id, $user_id);
        if (count($dataStatus) == 0 && app($this->emailRepository)->getEmailStatus($email_id,$user_id) == 0) {
            return []; //如果存在 说明该邮件存在 开始组装邮件
        } else {
            if(isset($data['id']) && $data['id'] > 0){
                $this->readEmail($data['id']);  //邮件置为已读
            }else{
                if($dataStatus){
                    $this->readEmail($dataStatus['0']['id']);  //消息系统邮件置为已读
                }
            }
            //获取邮件的基本信息
            $basicData = app($this->emailRepository)->getEmailInfo($email_id);
            $to_from_id[] = $basicData[0]["from_id"];

            $fromId[] = [
                "from_id"   => $basicData[0]["from_id"],
                "user_name" => $this->getFromUserName($basicData[0]),
            ];

            if ($basicData[0]["from_id"] == "systemAdmin") {
                $recipients = [];
            } else {
                $recipients = app($this->emailReceiveRepository)->getRecipients($email_id, true);
            }
            $basicData[0]["receive_data"] = $this->formatReceiveData($recipients, true, $user_id);
            $basicData[0]["user_name"] = $this->getFromUserName($basicData[0]);
            $basicData[0]["from_id"]   = $fromId;
//            $to_ids                    = [];
//            $copy_ids                  = [];
//            $secret_ids                = [];
//            $unread                    = []; //手机版
//            $read                      = [];
//            foreach ($recipients as $v) {
//                //通过user_id 换名字
//                if ($v['receive_type'] == 'secret') {
//                    //密送的 只能看到自己的 看不到其他密送的人
//                    if ($v['recipients'] == $data['user_id']) {
//                        if ($v['read_flag'] == 0) {
//                            $read[] = $v['user_name'];
//                        } else {
//                            $unread[] = $v['user_name'];
//                        }
//                        $secret_ids[] = [
//                            "recipients" => $v['recipients'],
//                            "user_name"  => $v['user_name'],
//                            "title"      => $v['read_flag'],
//                            "list_number"=> $v['list_number'],
//                        ];
//                    }
//                } else if ($v['receive_type'] == 'copy') {
//                    $copy_ids[] = [
//                        "recipients" => $v['recipients'],
//                        "user_name"  => $v['user_name'],
//                        "title"      => $v['read_flag'],
//                        "list_number"=> $v['list_number'],
//                    ];
//                    if ($v['read_flag'] == 0) {
//                        $read[] = $v['user_name'];
//                    } else {
//                        $unread[] = $v['user_name'];
//                    }
//                } else {
//                    $to_ids[] = [
//                        "recipients" => $v['recipients'],
//                        "user_name"  => $v['user_name'],
//                        "title"      => $v['read_flag'],
//                        "list_number"=> $v['list_number'],
//
//                    ];
//                    if ($v['read_flag'] == 0) {
//                        $read[] = $v['user_name'];
//                    } else {
//                        $unread[] = $v['user_name'];
//                    }
//                }
//            }

//            $sort_unread = array_unique($read);
//            $read = array_unique($unread);
//            sort($sort_unread);
//            sort($read);
            //组装附件
            $basicData[0]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'email', 'entity_id' => $email_id]);
//            $basicData[0]['copy_id']     = $copy_ids;
//            $basicData[0]['to_id']       = $to_ids;
//            $basicData[0]['secret_id']   = $secret_ids;
            $basicData[0]['to_from_id']  = $to_from_id;
//            $basicData[0]['unread']      = array_unique($read);
//            $basicData[0]['read']        = array_unique($unread);
//            $basicData[0]['unread']      = $sort_unread;
//            $basicData[0]['read']        = $read;
            $basicData[0]['star_flag']   = Arr::get($dataStatus, '0.star_flag', 0);
            $basicData[0]['id']   = Arr::get($dataStatus, '0.id', 0);
            $basicData[0]['deleted']   = Arr::get($dataStatus, '0.deleted', 1);
            $basicData[0] = array_merge($basicData[0], $nearlyIds);
            $basicData[0] = array_merge($basicData[0], $nearlyIds);
            return $basicData[0];
        }
    }

    // 获取收件人的所有数据
    private function formatReceiveData($recipients, $filterSecret = false, $filterUserId = null)
    {
        $types = ['to', 'copy', 'secret'];
        $dataMode = [
            'total' => 0,
            'read' => 0,
            'unread' => 0,
            'users' => []
        ];
        $receiveData = [
            'to' => $dataMode,
            'copy' => $dataMode,
            'secret' => $dataMode,
        ];
        foreach ($recipients as $v) {
            $type = $v['receive_type'];
            if (!in_array($type, $types)) {
                continue;
            }
            // 非密送本人则过滤
            if ($filterSecret && $type == 'secret' && $v['recipients'] != $filterUserId) {
                continue;
            }
            $receiveDataTemp = $receiveData[$type];
            $receiveDataTemp['users'][] = [
                'user_id' => $v['recipients'],
                'user_name' => $v['user_name'],
                'read_flag' => $v['read_flag'],
                'read_time' => $v['read_time'],
            ];
            $receiveDataTemp['total']++;
            $v['read_flag'] ? $receiveDataTemp['read']++ : $receiveDataTemp['unread']++;
            $receiveData[$type] = $receiveDataTemp;
        }
        return $receiveData;
    }

    private function getNearlyId($gridParams, $currentEmailId, $userId)
    {
        $gridParams = json_decode($gridParams, true);
        if (!$gridParams) {
            return [];
        }
        $gridParams['user_id'] = $userId;
        $boxId = Arr::get($gridParams, 'box_id', 0);
        if (is_array($gridParams)) {
            $ids = app($this->emailRepository)->getEmailListIds($boxId, $gridParams);
            $currentIndex = array_search($currentEmailId, $ids);
            $lastId = Arr::get($ids, $currentIndex - 1, 0);
            $nextId = Arr::get($ids, $currentIndex + 1, 0);
        } else {
            $lastId = $nextId = 0;
        }
        $ids = [
            'last_id' => $lastId,
            'next_id' => $nextId,
        ];
        if (!in_array($boxId, [-2, -3])) {
            if ($lastId) {
                $ids['last_email'] = Arr::first(app($this->emailReceiveRepository)->getOneEmail($lastId, $userId));
            }
            if ($nextId) {
                $ids['next_email'] = Arr::first(app($this->emailReceiveRepository)->getOneEmail($nextId, $userId));
            }
        }
        return $ids;
    }

    //封装数据 -- 邮件 发件箱等数据考量
    public function getEmailData($data, $email_id, $user_id)
    {
        //获取邮件的基本信息
        //设置可读(草稿箱内容不会置为已读)
        $emailBox = Arr::get($data, 'email_box');
        ($emailBox == -1) && app($this->emailReceiveRepository)->readEmailWay1($email_id, $user_id);
        $basicData = app($this->emailRepository)->getEmailInfo($email_id);
        if (count($basicData) == 0) {
            return [];
        }

        $to_from_id[] = $basicData[0]["from_id"];

        $basicData[0]["user_name"] = $this->getFromUserName($basicData[0]);
        if ($basicData[0]["from_id"] == "systemAdmin") {
            $fromId[] = [
                "from_id"   => "systemAdmin",
                "user_name" => trans("email.system_message"),
            ];
            $recipients = [];
        } else {
            $fromId[] = [
                "from_id"   => $basicData[0]["from_id"],
                "user_name" => $basicData[0]["user_name"],
            ];

            $recipients = app($this->emailReceiveRepository)->getRecipients($email_id, true);
        }

        $basicData[0]["receive_data"] = $this->formatReceiveData($recipients, false, $user_id);
        $basicData[0]["from_id"] = $fromId;
        $id = '';
        $to_ids = [];
//        $to_ids = $copy_ids = $secret_ids = $to_names = $copy_names = $secret_names = $unread = $read = [];
//        foreach ($recipients as $v) {
//            $id = $v['id'];
//            $recipientsIds = trim($v['recipients'], ',');
//            if ($v['receive_type'] == 'secret') {
//                // 收件箱密送人过滤：只能看自己
//                if ($emailBox == -1 && $recipientsIds !== $user_id) {
//                    continue;
//                }
//                $secret_names[] = [
//                    "recipients" => $recipientsIds,
//                    "user_name"  => $v['user_name'],
//                    "title"      => $v['read_flag'],
//                    "list_number"=> $v['list_number'],
//                    "read_time"=> $v['read_time'] ?: '-',
//                ];
//                $secret_ids[] = $recipientsIds;
//                if ($v['read_flag'] == 0) {
//                    $read[] = $v['user_name'];
//                } else {
//                    $unread[] = $v['user_name'];
//                }
//            } else if ($v['receive_type'] == 'copy') {
//                $copy_names[] = [
//                    "recipients" => $recipientsIds,
//                    "user_name"  => $v['user_name'],
//                    "title"      => $v['read_flag'],
//                    "list_number"=> $v['list_number'],
//                    "read_time"=> $v['read_time'] ?: '-',
//                ];
//                $copy_ids[] = $v['recipients'];
//                if ($v['read_flag'] == 0) {
//                    $read[] = $v['user_name'];
//                } else {
//                    $unread[] = $v['user_name'];
//                }
//            } else {
//                $to_names[] = [
//                    "recipients" => $recipientsIds,
//                    "user_name"  => $v['user_name'],
//                    "title"      => $v['read_flag'],
//                    "list_number"=> $v['list_number'],
//                    "read_time"=> $v['read_time'] ?: '-',
//                ];
//                $to_ids[] = $recipientsIds;
//                if ($v['read_flag'] == 0) {
//                    $read[] = $v['user_name'];
//                } else {
//                    $unread[] = $v['user_name'];
//                }
//            }
//        }
//        $sort_unread = array_unique($read);
//        $read = array_unique($unread);
//        sort($sort_unread);
//        sort($read);

        //组装附件
        $basicData[0]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'email', 'entity_id' => $email_id]) ;
//        $basicData[0]['unread']      = array_unique($read);
//        $basicData[0]['read']        = array_unique($unread);
//        $basicData[0]['unread']      = $sort_unread;
//        $basicData[0]['read']        = $read;

        //去重
        $to_to_id = array_unique(array_merge($to_ids, $to_from_id));

        $recipientData = [
//            "copy_id"     => $copy_ids,
//            "to_id"       => $to_ids,
//            "secret_id"   => $secret_ids,
//            "copy_name"   => $copy_names,
//            "to_name"     => $to_names,
//            "secret_name" => $secret_names,
            "to_from_id"  => $to_from_id, // 回复时使用
            "to_to_id"    => $to_to_id, //回复全部时
            "email_box"   => intval($data['email_box']), //区分是收件箱，发件箱，草稿箱
            "id"          => $id
        ];
        $nearlyIds = $this->getNearlyId(Arr::get($data, 'grid_params', ''), $email_id, $user_id);
        return array_merge($basicData[0], $recipientData, $nearlyIds);
    }

    // 撤销邮件
    public function emailRecycle($email_id, $input)
    {
        $emailData = app($this->emailRepository)->getDetail($email_id);
        if (!$emailData) {
            return ['code' => ['0x012005', 'email']];
        } else if ($emailData["deleted"] == 1) {
            return ['code' => ['0x012006', 'email']];
        } else if ($emailData["send_flag"] == 0) {
            return ['code' => ['0x012008', 'email']];
        } else if ($emailData["from_id"] != own()['user_id']) {
            return ['code' => ['0x000006', 'common']];
        }
        $from_id = $emailData["from_id"];
        // 撤回部分
        if (Arr::get($input, 'type') == 'some') {
            $ids = EmailReceiveRepository::buildQuery([
                'email_id' => $email_id,
                'read_flag' => 0
            ])->select('id')->get()->toArray();
            $ids && EmailReceiveRepository::buildQuery()->whereIn('id', $ids)->forceDelete();
            return trans('email.recycle_some_success', ['count' => count($ids)]);
        }
        //检查当前邮件有没有人打开过
        $where = [
            "email_id"   => [[$email_id], "="],
            "recipients" => [[$from_id], "!="],
            "read_flag"  => [[1]],
        ];

        $checkEmail = app($this->emailReceiveRepository)->getEmailsByWhere($where);

        if (count($checkEmail) > 0) {
            return ['code' => ['0x012007', 'email']];
        }

        //撤回
        $status = app($this->emailRepository)->updateData(["send_flag" => 0], ['email_id' => $email_id]);
        if (!$status) {
            return ['code' => ['0x012009', 'email']];
        }

        return $email_id;
    }

    public function defineEmailTemplate()
    {
        //邮件主题格式
        $tpl_mail = <<<EOF
Date: {senddate}
From: {fromname} <{fromname}>
To: {toname} <{toname}>
Cc: {copyname} <{copyname}>
Subject: {subject}
Mime-Version: 1.0
Content-Type: multipart/mixed;
 boundary="----=====weaver_com_cn_eoffice_=====----"

This is a multi-part message in MIME format.

------=====weaver_com_cn_eoffice_=====------
Content-Type: text/html;
charset="GB2312"
Content-Transfer-Encoding: quoted-printable

{email_content}

EOF;

        $tpl_annex = <<<EOF
--=====weaver_com_cn_eoffice_=====
Content-Type: application/octet-stream;
    name="{annexname}"
Content-Transfer-Encoding: base64
Content-Disposition: attachment;
    filename="{filename}"

{file_content}
EOF;

        $tpl_end = <<<EOF
--=====weaver_com_cn_eoffice_=====
EOF;

        return [
            "tpl_mail"  => $tpl_mail,
            "tpl_annex" => $tpl_annex,
            "tpl_end"   => $tpl_end,
        ];
    }

    /**
     * 导出邮件
     *
     * @todo 导出邮件
     *
     * @return
     */
    public function exportEmail($email_id)
    {
        try {
            $url = $this->createEmlFile($email_id);
        } catch (Exception $e) {
            throw new Exception(new JsonResponse(error_response('0x012003', $e->getMessage()), 500));
        }

        $url   = str_replace('\\', '/', $url);
        $count = strripos($url, "/") + 1;
        //获取文件的名字
        $file = substr($url, $count);

        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename= $file ");
        echo file_get_contents($url);

        if (file_exists($url)) {
            unlink($url);
        }
    }

    /**
     * 导出邮件
     *
     * @todo 导出邮件
     *
     * @create 2017-06-23
     *
     * @return
     */
    public function downloadEml($emailId, $current_user_id = '')
    {
        $loginUser = Redis::get("eoffice_login_users");
        $emailIds  = explode(',', $emailId);
        $count     = count($emailIds);
        if ($count > 1) {
            $files = [];
            $data  = [];

            foreach ($emailIds as $key => $value) {
                $data[]  = $this->downloadSingle($value);
                $files[] = $data[$key]['filePath'];
            }
            $zipPath  = $data[0]['zipPath'];
            $zipName  = $data[0]['zipName'];
            $fileName = iconv('GBK', 'UTF-8', $zipName);
            $filePath = iconv('GBK', 'UTF-8', $zipPath);
            $result   = $this->createZip($files, $filePath);
            foreach ($files as $file) {
                unlink($file);
            }

        } else {
            $data     = $this->downloadSingle($emailId);
            $fileName = iconv('GBK', 'UTF-8', $data['fileName']);
            $filePath = iconv('GBK', 'UTF-8', $data['filePath']);
        }
        $key    = md5($loginUser . $fileName . time());
        $config = [
            "user_id"     => $current_user_id,
            "export_type" => "async",
        ];
        app($this->importExportService)->exportJobExportPublish($config, $filePath, $fileName, $key);
        return trans("email.export_success");
    }

    /**
     * 导出单个邮件
     *
     * @todo 导出邮件
     *
     * @create 2017-06-23
     *
     * @return
     */
    public function downloadSingle($emailId)
    {
        $eml = new \App\Utils\Eml();

        $basicData = app($this->emailRepository)->getEmailInfo($emailId);

        if (count($basicData) > 0) {
            $emailData = $basicData[0];
        } else {
            return false; //数据异常
            exit;
        }
        //获取收件人信息
        $recipients   = Arr::get($basicData, '0.from_id') == "systemAdmin" ? [] : app($this->emailReceiveRepository)->getRecipients($emailId);
        // $recipients   = app($this->emailReceiveRepository)->getRecipients($emailId);
        $to_names     = " ";
        $copy_names   = "";
        $secret_names = "";
        $emailData['user_name'] = $this->getFromUserName($emailData);
        //获取图片id
        $str = $emailData['content'];
        preg_match_all('/<img.*?src="(.*?)".*?>/is', $str, $array);
        if ($array[1]) {
            $data   = [];
            $imgDir = getAttachmentDir();
            foreach ($array[1] as $k => $v) {
                // 截取?后的
                $str = substr($v, 0, strrpos($v, '?'));
                // 截取/后的
                $str = substr($str, strrpos($str, "/"));
                // 取出最后的/
                $str                    = trim($str, '/');
                $data['attach_ids'][$k] = $str;
                $data['path'][$str]     = $v;
            }

            foreach ($data['path'] as $k => $v) {
//                $attachment = app($this->attachmentRepository)->getOneAttachment($k);
                $attachment = app($this->attachmentService)->getOneAttachmentById($k);
                if ($attachment) {
//                    $imgPath              = trim($imgDir, '/') . '/' . $attachment['attachment_path'] . $attachment['affect_attachment_name'];
                    $imgPath              = $attachment['temp_src_file'];
                    $emailData['content'] = str_replace($v, $imgPath, $emailData['content']);
                }
            }
        }

        $content = $emailData['content'];

        foreach ($recipients as $v) {
            if ($v['receive_type'] == 'secret') {
                $secret_names .= $v['user_name'] . ",";
            } else if ($v['receive_type'] == 'copy') {
                $copy_names .= $v['user_name'] . ",";
            } else {
                $to_names .= $v['user_name'] . ",";
            }
        }

        $eml->Date = date('r', strtotime($emailData['send_time']));
        // $eml->Date = date("Y-m-d H:i:s", strtotime("$eml->Date   -14   hour"));
        $eml->From    = iconv('UTF-8', 'GBK//IGNORE', $emailData['user_name']);
        $eml->To      = iconv('UTF-8', 'GBK//IGNORE', $to_names);
        $eml->Cc      = iconv('UTF-8', 'GBK//IGNORE', $copy_names);
        $eml->Bcc     = iconv('UTF-8', 'GBK//IGNORE', $secret_names);
        $eml->Subject = iconv('UTF-8', 'GBK//IGNORE', $emailData['subject']);
        $eml->MsgId   = 50;
        $eml->body    = iconv('UTF-8', 'GBK//IGNORE', $content);
        $attachments = app($this->attachmentService)->getAttachments(['entity_table' => 'email', "entity_id" => $emailId]);
        if (!empty($attachments)) {
            if (count($attachments) > 1) {
                foreach ($attachments as $attachment) {
                    $eml->addAttachment($attachment['temp_src_file'], iconv('UTF-8', 'GBK//IGNORE', $attachment['attachment_name']));
                }
            } else {
                $eml->addAttachment($attachments[0]['temp_src_file'], iconv('UTF-8', 'GBK//IGNORE', $attachments[0]['attachment_name']));
            }
        }

        $dir       = trim(base_path(), '/') . '/' . 'public/export/';
        $yearPath  = $dir . date("Y");
        $monthPath = $dir . date("Y") . '/' . date("m");
        $dayPath   = $dir . date("Y") . '/' . date("m") . '/' . date("d");
        if (is_dir($yearPath)) {
            if (is_dir($monthPath)) {
                if (!is_dir($dayPath)) {
                    mkdir(iconv("UTF-8", "GBK//IGNORE", $dayPath), 0777, true);
                }
            } else {
                mkdir(iconv("UTF-8", "GBK//IGNORE", $monthPath), 0777, true);

            }
        } else {
            mkdir(iconv("UTF-8", "GBK//IGNORE", $yearPath), 0777, true);
        }

        $emlFile  = $eml->generate();
        $date     = strtotime(date("Y-m-d H:i:s"));
        $dates    = date("YmdHis");
        $fileName = iconv('UTF-8', 'GBK//IGNORE', 'e-office_' . trans("email.internal_mail") . '_' . $emailId . '_' . $dates);
        $filePath = $dayPath . '/' . $fileName . '.eml';
        if (!is_dir($dayPath)) {
            mkdir($dayPath. '/', 0777, true);
        }
        file_put_contents($filePath, $emlFile);

        $zipName = iconv('UTF-8', 'GBK//IGNORE', 'e-office_' . trans("email.internal_mail") . '_' . $dates . '.zip');
        $zipPath = iconv('UTF-8', 'GBK//IGNORE', $dayPath . '/' . 'e-office_' . trans("email.internal_mail") . '_' . $dates . '.zip');

        return [
            "zipName"  => $zipName,
            "zipPath"  => $zipPath,
            "fileName" => $fileName,
            "filePath" => $filePath,
        ];
    }

    /**
     * 导出邮件打包成Zip
     *
     * @create 2017-06-23
     *
     */
    public function createZip($files = array(), $destination = '', $overwrite = false)
    {
        if (file_exists($destination) && !$overwrite) {return false;}
        $valid_files = array();
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        if (count($valid_files)) {
            $zip = new \ZipArchive();
            if ($zip->open($destination, $overwrite ? \ZIPARCHIVE::OVERWRITE : \ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            foreach ($valid_files as $file) {
                $file_info_arr = pathinfo($file);
                $zip->addFile($file, $file_info_arr['basename']); //去掉层级目录
            }
            $zip->close();
            return file_exists($destination);
        } else {
            return false;
        }
    }

    /**
     * 下载邮件-齐少博
     *
     */
//    public function downZipEmailTest($param) {
    //        $email_id = $param['email_id'];
    //        if (empty($email_id)) {
    //            return false;
    //        }
    //
    //        try {
    //            $emails = explode(",", $email_id);
    //            $data = [];
    //            foreach ($emails as $email_id) {
    //                $data[] = $this->createEmlFileTest($email_id);
    //            }
    //
    //            return $data;
    //        } catch (Exception $e) {
    //            throw new Exception(new JsonResponse(error_response('0x012003', $e->getMessage()), 500));
    //        }
    //    }

    /**
     * 导出邮件 测试 齐少博
     *
     * @todo 导出邮件
     *
     * @return
     */
//    public function exportEmailTest($param) {
    //        $email_id = $param['email_id'];
    //        $emailIds = explode(',', $email_id);
    //        if(count($emailIds) > 1){
    //            foreach ($emailIds as $emailId) {
    //                $data[] = $this->createEmlFileTest($emailId);
    //            }
    //            return $data;
    //        }else{
    //            return $this->createEmlFileTest($email_id);
    //        }
    //        if(count($emailIds) > 1){
    //            foreach ($emailIds as $emailId) {
    //                $data[] = $this->createEmlFileTest($emailId);
    //            }
    //            return $data;
    //        }else{
    //            return $this->createEmlFileTest($email_id);
    //        }
    //        try {
    //            if(count($emailIds) > 1){
    //                foreach ($emailIds as $emailId) {
    //                    $data[] = $this->createEmlFileTest($emailId);
    //                }
    //                return $data;
    //            }else{
    //                return $this->createEmlFileTest($email_id);
    //            }
    //        } catch (Exception $e) {
    //            throw new Exception(new JsonResponse(error_response('0x012003', $e->getMessage()), 500));
    //        }
    //    }

    //生成eml文件 含附件信息
    //    public function createEmlFileTest($email_id) {
    //        $count = 0;
    //        $basicData = app($this->emailRepository)->getEmailInfo($email_id);
    //        if (count($basicData) > 0) {
    //            $emailData = $basicData[0];
    //        } else {
    //            return false; //数据异常
    //            exit;
    //        }
    //        $recipients = app($this->emailReceiveRepository)->getRecipients($email_id);
    //        $to_names = "";
    //        $copy_names = "";
    //        $secret_names = "";
    //        foreach ($recipients as $v) {
    //            if ($v['receive_type'] == 'secret') {
    //                $secret_names.= $v['user_name'] . ",";
    //            } else if ($v['receive_type'] == 'copy') {
    //                $copy_names .= $v['user_name'] . ",";
    //            } else {
    //                $to_names .= $v['user_name'] . ",";
    //            }
    //        }
    //
    //        $copy_names = trim($copy_names, ",");
    //        $to_names = trim($to_names, ",");
    //
    //
    //        $email_content = iconv("utf-8", "GBK//IGNORE", $emailData['content']);
    //
    //        $templates = $this->defineEmailTemplate();
    //        //获取附件
    //        $attachments = app($this->attachmentService)->getAttachment(['entity_table' => 'email', "entity_id" => $email_id]);
    //
    //
    //        $tpl_mail = $templates["tpl_mail"];
    //        $tpl_annex = $templates["tpl_annex"];
    //        $tpl_end = $templates["tpl_end"];
    //
    ////邮件主体替换
    //        $eml_content = str_replace("{senddate}", $emailData['send_time'], $tpl_mail);
    //        $eml_content = str_replace("{fromname}", $emailData['user_name'], $eml_content);
    //        $eml_content = str_replace("{toname}", $to_names, $eml_content);
    //        $eml_content = str_replace("{copyname}", $copy_names, $eml_content);
    //        $eml_content = str_replace("{subject}", $emailData['subject'], $eml_content);
    //        $eml_content = str_replace("{email_content}", $email_content, $eml_content);
    //        //邮件附件替换
    //        $eml_annex = "";
    //        $eml_p_annex = "";
    //        foreach ($attachments as $k => $attach) {
    //            //转成code
    //            $filename = iconv("utf-8", "GBK//IGNORE", $attach['temp_src_file']);
    //            if (file_exists($filename)) {
    //                $file_contents = "";
    //                $handle = fopen($filename, "r");
    //                while (!feof($handle)) {
    //                    $file_contents .= fread($handle, 50000);
    //                }
    //                fclose($handle);
    //
    //                $file_contents = base64_encode($file_contents);
    //
    //                $eml_p_annex = str_replace("{annexname}", $attach['attachment_name'], $tpl_annex);
    //                $eml_p_annex = str_replace("{filename}", $attach['attachment_name'], $eml_p_annex);
    //                $eml_p_annex = str_replace("{file_content}", $file_contents, $eml_p_annex);
    //                $eml_annex .= $eml_p_annex;
    //            }
    //        }
    //
    //
    //
    //        $tmp_f_name = strtotime($emailData['send_time']) . rand(1000, 9999);
    //
    //
    //        $eml_content = $eml_content . $eml_annex . $tpl_end;
    //
    //
    //        return [
    //            'file_name' => $tmp_f_name,
    //            'data' => $eml_content
    //        ];
    //    }

    //清空邮件（回收站）
    public function truncateEmail($data ,$own)
    {
        $data['user_id'] = $own['user_id'];
        if (isset($data["type"])) {
            if ($data["type"] == "all") {
                //删除所有的
                $where = [
                    'deleted' => [[1], "="],
                    'from_id' => [[$data['user_id']], "="],
                ];
                app($this->emailRepository)->deleteByWhere($where);
                $where2 = [
                    'deleted'    => [[1], "="],
                    'recipients' => [[$data['user_id']], "="],
                ];
                app($this->emailReceiveRepository)->deleteByWhere($where2);
            } else {
                //收件箱ID

                $Ids      = explode(",", $data['id']);
                $emailIds = explode(",", $data['email_id']);

                $where = [
                    'email_id' => [$emailIds, 'in'],
                    'deleted'  => [[1], "="],
                    'from_id'  => [[$data['user_id']], "="],
                ];
                app($this->emailRepository)->deleteByWhere($where);

                $where2 = [
                    'email_id'   => [$emailIds, 'in'],
                    'deleted'    => [[1], "="],
                    'recipients' => [[$data['user_id']], "="],
                ];
                app($this->emailReceiveRepository)->deleteByWhere($where2);
            }
            return 1;
        } else {
            return ['code' => ['0x012001', 'email']];
        }
    }

    //撤销删除的邮件（回收站）
    public function recycleDeleteEmail($data, $own)
    {
        $data['user_id'] = $own['user_id'];

        $Ids      = explode(",", $data['id']);
        $emailIds = explode(",", $data['email_id']);
        $where    = [
            'email_id' => [$emailIds, 'in'],
            'deleted'  => [1],
            'from_id'  => [$data['user_id']],
        ];
        app($this->emailRepository)->updateData(["deleted" => 0], $where);
        $where2 = [
            // 'id' => [$Ids, 'in'],
            'email_id'   => [$emailIds, 'in'],
            'deleted'    => [1],
            'recipients' => [$data['user_id']],
        ];
        app($this->emailReceiveRepository)->updateData(["deleted" => 0, "box_id" => 0], $where2);

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($emailIds);

        return 1;
    }

    public function getEmailNums($box_id, $user_id)
    {
        $result          = [];
        $data["user_id"] = $user_id;
        switch ($box_id) {
            case -1:
                $data["box_id"] = 0;
                $result         = $this->getEmailReceiveNum($data);
                break;
            case -2:
                $result = $this->getTempEmailNum($data);
                break;
            case -3:
                $result = $this->getOutEmailNum($data);
                break;
            case -4:
                $result = $this->gettrashEmailNum($data);
                break;
            default:
                $data["box_id"] = $box_id;
                $result         = $this->getEmailReceiveNum($data);
                break;
        }
        return $result;
    }

    public function sendEmailRemind($subject, $curUserName, $emailId, $toUserIds) {
        $sendData = [];
        $sendData['remindMark']   = 'email-submit';
        $sendData['toUser']       = array_unique($toUserIds);
        $sendData['contentParam'] = ['emailTitle' => $subject, 'userName' => $curUserName]; //当前登录
        $sendData['stateParams']  = ['email_id' => $emailId];
        Eoffice::sendMessage($sendData);
    }

    public function readStatistics($emailId, $userId)
    {
        $params = [
            'email_id' => $emailId,
            'from_id' => $userId,
        ];
        $email = EmailRepository::buildQuery($params);
        if (empty($email)) {
            return ['code' => ['0x000006', 'common']];
        }
        $total = EmailReceiveRepository::readCount($emailId);
        $readCount = $total ? EmailReceiveRepository::readCount($emailId, 1) :0;
        $unreadCount = $total - $readCount;
        $data = [
            'total' => $total,
            'read_count' => $readCount,
            'unread_count' => $unreadCount,
        ];
        $msg = $this->getRecycleMsg($data);

        return array_merge($data, $msg);
    }

    // 更新对应id的星标状态，并返回对应id变更后的值数组
    public function toggleStar($params, $own)
    {
        $ids = array_unique(explode(',', Arr::get($params, 'receive_ids', '')));
        $receives = EmailReceiveRepository::buildCanStarQuery($own['user_id'], $ids)->get();
        if (count($ids) != $receives->count()) {
            return ['code' => ['0x000006', 'common']];
        }
        $receives = $receives->groupBy('star_flag');
        $result = [];
        foreach ($receives as $key => $data) {
            $updateIds = $data->pluck('id')->toArray();
            $updateFlag = $key == 0 ? 1 : 0;
            EmailReceiveRepository::buildQuery()
                ->whereIn('id', $updateIds)
                ->update(['star_flag' => $updateFlag]);
            $tempResult = array_fill_keys($updateIds, $updateFlag);
            $result = $result + $tempResult;
        }
        $returnData['detail'] = $result; // 明细
        if (Arr::get($params, 'withStarEmailCount')) {
            $returnData['star_count'] = EmailReceiveRepository::starCount($own['user_id']);
        }
        return $returnData;
    }

    public function emailReceiveList($emailId, $params) {
        $params = $this->parseParams($params);
        $limit = Arr::get($params, 'limit', 10);
        $query = (app($this->emailReceiveRepository))->getEmailReceiveList($emailId, $params);
        $res = $query->paginate($limit)->toArray();
        return array_extract($res, ['total', 'list' => 'data']);
    }

    // 根据邮件阅读数据，获取撤回多语言信息与撤回类型
    private function getRecycleMsg($data) {
        $return = [];
        if ($data['read_count'] == 0) {
            $return['msgContent'] = trans('email.are_you_sure_you_want_to_withdraw_the_mail');
            $return['msgType'] = 'all';
        } else {
            if ($data['unread_count'] == 0) {
                $return['msgContent'] =  trans('email.no_email_can_recycle');
                $return['msgType'] = 'none';
            } else {
                $return['msgContent'] = trans('email.are_you_sure_you_want_to_withdraw_some_mail', $data);
                $return['msgType'] = 'some';
            }
        }
        return $return;
    }


    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int|array  $id
     */
    public function updateGlobalSearchDataByQueue($id)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchEmailMessage($id);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    public static function getUserEmailIds($userId) {
        return EmailRepository::getUserEmailIds($userId);
    }

    // 获取发件人名称
    private function getFromUserName($data)
    {
        $fromId = Arr::get($data, 'from_id', '');
        return $fromId === "systemAdmin" ? trans("email.system_message") : Arr::get($data, 'user_name', '');

    }

    /**
     * 获取当前用户，在指定用户中，通信受限的用户信息
     * @param array $userIds 指定用户
     * @param $own
     * @return array|bool 受限的用户信息
     */
    private function testCommunicateUserId(array $userIds, $own)
    {
        $roleIds = app($this->userService)->getCommunicateUserIds($own);
        $canCommunicateUserIds = UserRoleEntity::query()->whereIn('user_id', $userIds)
            ->whereIn('role_id', $roleIds)
            ->pluck('user_id')->toArray();
        $limitUserIds = array_diff($userIds, array_unique($canCommunicateUserIds));

        if ($limitUserIds) {
            $userNames = app($this->userRepository)->entity->whereIn('user_id', $limitUserIds)->pluck('user_name')->toArray();
            $userCount = count($userNames);
            $userNames = implode(',', $userNames);
            $msg = trans('email.you_are_communicate_by_users', ['userNames' => $userNames, 'userCount' => $userCount]);
            return ['code' => ['', ''], 'dynamic' => $msg];
        }

        return true;
    }

    // 获取可通信的全部用户id
    private function getCommunicateUserId($own) {
        $allUserIds = Utils::getUserIds();
        $allUserIds = explode(',', $allUserIds);
        $roleIds = app($this->userService)->getCommunicateUserIds($own);
        $allUserIds = UserRoleEntity::query()->whereIn('user_id', $allUserIds)
            ->whereIn('role_id', $roleIds)
            ->pluck('user_id')->toArray();

        return array_unique($allUserIds);
    }
}
