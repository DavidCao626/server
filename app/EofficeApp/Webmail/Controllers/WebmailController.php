<?php

namespace App\EofficeApp\Webmail\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Webmail\Requests\WebmailRequest;
use App\EofficeApp\Webmail\Services\WebmailConfigService;
use App\EofficeApp\Webmail\Services\WebmailFolderService;
use App\EofficeApp\Webmail\Services\WebmailService;
use Illuminate\Http\Request;

/**
 * 外部邮件控制器:提供外部邮件请求的实现方法
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailController extends Controller
{
    /** @var object 外部邮件service对象*/
    private $webmailService;
    private $webmailFolderService;

    public function __construct(
        Request $request,
        WebmailRequest $webmailRequest,
        WebmailService $webmailService,
        WebmailConfigService $webmailConfigService,
        WebmailFolderService $webmailFolderService
    ) {
        parent::__construct();
        $this->request        = $request;
        $this->webmailService = $webmailService;
        $this->webmailConfigService = $webmailConfigService;
        $this->webmailFolderService = $webmailFolderService;
        $this->formFilter($request, $webmailRequest);
        $this->userId = $this->own['user_id'];
    }

    /**
     * 创建发件箱
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function createOutbox()
    {
        $data                   = $this->request->all();
        $data['outbox_creator'] = $this->userId;

        $result = $this->webmailService->createOutbox($data);
        return $this->returnResult($result);
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
        $result = $this->webmailService->deleteOutbox($outboxId);
        return $this->returnResult($result);
    }

    /**
     * 编辑发件箱
     *
     * @param int $outboxId 发件箱id
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function updateOutbox($outboxId)
    {
        $result = $this->webmailService->updateOutbox($outboxId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取发件箱列表
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getOutboxs()
    {
        $result = $this->webmailService->getOutboxs($this->request->all(), $this->userId);
        $result['list'] && !isset($result['total']) && $result['total'] = count($result['list']);
        return $this->returnResult($result);
    }

    /**
     * 获取发件箱详情
     *
     * @param int $outboxId 发件箱id
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getOutbox($outboxId)
    {
        $result = $this->webmailService->getOutbox($outboxId);
        return $this->returnResult($result);
    }

    /**
     * 获取邮件服务器信息
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getWebmailServer()
    {
        $result = $this->webmailService->getWebmailServer($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 发送邮件
     *
     * @return array 操作结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function createMail()
    {
        $data                 = $this->request->all();
        $data['mail_creator'] = $this->userId;
        $result               = $this->webmailService->createMail($data);
        return $this->returnResult($result);
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
    public function deleteMail($mailId)
    {
        $result = $this->webmailService->deleteMail($mailId, $this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 编辑邮件
     *
     * @param int $mailId 邮件id
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function updateMail($mailId)
    {
        $result = $this->webmailService->updateMail($mailId, $this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取邮件列表
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getMails()
    {
        $result = $this->webmailService->getMails($this->request->all(),$this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取邮件详情
     *
     * @param int $mailId 邮件id
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getMail($mailId)
    {
        $result = $this->webmailService->getMail($mailId);
        return $this->returnResult($result);
    }

    /**
     * 创建文件夹
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function createFolder()
    {
        $data                       = $this->request->all();
        $data['folder_creator']     = $this->userId;
        $data['folder_create_time'] = date('Y-m-d H:i:s');

        $result = $this->webmailService->createFolder($data);
        return $this->returnResult($result);
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
        $result = $this->webmailService->deleteFolder($folderId);
        return $this->returnResult($result);
    }

    /**
     * 编辑文件夹
     *
     * @param int $folderId 文件夹id
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function updateFolder($folderId)
    {
        $result = $this->webmailService->updateFolder($folderId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取文件夹列表
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getFolders()
    {
        $result = $this->webmailService->getFolders($this->request->all(), $this->userId);
        return $this->returnResult($result);
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
        $result = $this->webmailService->getFolder($folderId);
        return $this->returnResult($result);
    }

    /**
     * 获取转移文件夹
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getTransferFolder()
    {
        $result = $this->webmailService->getTransferFolder($this->request->all(), $this->userId);
        return $this->returnResult($result);
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
    public function getAccountInfo($outboxId)
    {
        $result = $this->webmailService->getAccountInfo($outboxId, $this->userId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 收信
     *
     * @param int $outboxId 发件箱id
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function receiveMail($outboxId)
    {
        $result = $this->webmailService->receiveMail($outboxId, $this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 连接外部邮件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-03
     */
    public function connectSmtp()
    {
        $result = $this->webmailService->connectSmtp($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取收件人
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-08-19
     */
    public function getReceivers()
    {
        $result = $this->webmailService->getReceivers($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    // 获取收信规则
    public function getReceiveRule()
    {
        $result = $this->webmailService->getReceiveRule();
        return $this->returnResult($result);
    }

    // 设置收信规则
    public function setReceiveRule()
    {
        $result = $this->webmailService->setReceiveRule($this->request->all());
        return $this->returnResult($result);
    }

    // 获取全部的邮件
    public function getMailCount()
    {
        $result = $this->webmailService->getMailCount($this->userId);
        return $this->returnResult($result);
    }
/**
     * 创建标签
     *
     * @return int|array 操作成功状态|错误码
     *
     * @since 2019-08-30
     */
    public function createTag()
    {
        $data = $this->request->all();
        $data['tag_creator'] = $this->userId;

        $result = $this->webmailService->createTag($data);
        return $this->returnResult($result);
    }

    /**
     * 删除标签
     *
     * @param int|string $tagId 标签id,多个用逗号隔开
     *
     * @return int|array 操作成功状态|错误码
     *
     * @since 2019-08-30
     */
    public function deleteTag($tagId)
    {
        $result = $this->webmailService->deleteTag($tagId);
        return $this->returnResult($result);
    }

    /**
     * 编辑标签
     *
     * @param int $tagId 标签id
     *
     * @return int|array 操作成功状态|错误码
     *
     * @since 2019-08-30
     */
    public function updateTag($tagId)
    {
        $result = $this->webmailService->updateTag($tagId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取标签列表
     *
     * @return array 查询结果
     *
     * @since 2019-08-30
     */
    public function getTags()
    {
        $result = $this->webmailService->getTags($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取标签详情
     *
     * @param int $tagId 文件夹id
     *
     * @return array 查询结果
     *
     * @since 2019-08-30
     */
    public function getTag($tagId)
    {
        $result = $this->webmailService->getTag($tagId);
        return $this->returnResult($result);
    }

    /**
     * 设置标签
     *
     * @since 2019-08-30
     */
    public function setTag()
    {
        $result = $this->webmailService->setTag($this->request->all());
        return $this->returnResult($result);
    }

     /**
     * 取消标签
     *
     * @since 2019-08-30
     */
    public function cancelTag()
    {
        $result = $this->webmailService->cancelTag($this->request->all());
        return $this->returnResult($result);
    }

    public function setSendRules()
    {
        $result = $this->webmailService->setSendRules($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    public function getSendRules()
    {
        $result = $this->webmailService->getSendRules($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 创建发件箱
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function checkOutbox()
    {
        $data                   = $this->request->all();
        $data['outbox_creator'] = $this->userId;

        $result = $this->webmailService->checkOutbox($data);
        return $this->returnResult($result);
    }

    public function getLogs()
    {
        $data = $this->request->all();

        $result = $this->webmailService->getLogs($data, $this->userId);
        return $this->returnResult($result);
    }

    public function getRecords()
    {
        $data = $this->request->all();

        $result = $this->webmailService->getRecords($data, $this->userId);
        return $this->returnResult($result);
    }

    public function getShareSets()
    {
        $data = $this->request->all();

        $result = $this->webmailConfigService->getShareConfigList($data, $this->userId);
        return $this->returnResult($result);
    }

    public function getShareSet($configId)
    {
        $result = $this->webmailConfigService->getDetail($configId, $this->userId);
        return $this->returnResult($result);
    }

    public function addShareConfig()
    {
        $data = $this->request->all();

        $result = $this->webmailConfigService->addShareConfig($data, $this->userId);
        return $this->returnResult($result);
    }

    public function editShareConfig($configId)
    {
        $data = $this->request->all();

        $result = $this->webmailConfigService->editShareConfig($configId, $data, $this->userId);
        return $this->returnResult($result);
    }

    public function deleteShareConfig($configId)
    {
        $data = $this->request->all();

        $result = $this->webmailConfigService->deleteShareConfig($configId, $data, $this->userId);
        return $this->returnResult($result);
    }

    public function webmailFolders()
    {
        $data = $this->request->all();

        $result = $this->webmailFolderService->getListByDatabase($data);
        return $this->returnResult($result);
    }

    public function updateWebmailFolders($outboxId)
    {
        $result = $this->webmailFolderService->updateWebmailFolders($outboxId, $this->userId);
        return $this->returnResult($result);
    }

    public function addWebmailFolder()
    {
        $data = $this->request->all();
        $result = $this->webmailFolderService->addOneEmailFolder($data, $this->userId);
        return $this->returnResult($result);
    }

    public function editWebmailFolder()
    {
        $data = $this->request->all();
        $result = $this->webmailFolderService->editOneWebmailFolder($data, $this->userId);
        return $this->returnResult($result);
    }

    public function deleteWebmailFolder($outboxId, $folderId)
    {
        $result = $this->webmailFolderService->deleteOneWebmailFolder($outboxId, $folderId, $this->userId);
        return $this->returnResult($result);
    }

    public function getOneOutboxFolderAndMailCount($outboxId)
    {
        $result = $this->webmailFolderService->getOneOutboxFolderAndEmailCount($outboxId);
        return $this->returnResult($result);
    }
}
