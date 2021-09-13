<?php

namespace App\EofficeApp\Email\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Email\Requests\EmailRequest;
use App\EofficeApp\Email\Services\EmailService;

/**
 * 邮件控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class EmailController extends Controller {

    public function __construct(
    Request $request, EmailService $emailService, EmailRequest $emailRequest
    ) {
        parent::__construct();
        $this->emailService = $emailService;
        $this->emailRequest = $request;
        $this->formFilter($request, $emailRequest);
    }

    /**
     * 获取用户的文件夹
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getEmailBoxList() {
        $result = $this->emailService->getEmailBoxList($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    public function getEmailBoxListAll() {
        $result = $this->emailService->getEmailBoxListAll($this->emailRequest->all());
        return $this->returnResult($result);
    }

    public function emailTypes($type) {
        $result = $this->emailService->emailTypes($this->emailRequest->all(), $type,$this->own);
        return $this->returnResult($result);
    }

    public function readEmail($emailId) {
        $setUnRead = $this->emailRequest->get('set_unread'); // 检查是否是标记回未读状态
        $setRead = !$setUnRead;
        $result = $this->emailService->readEmail($emailId, $setRead);
        return $this->returnResult($result);
    }

     public function getEmailId() {
        $result = $this->emailService->getEmailId($this->emailRequest->all());
        return $this->returnResult($result);
    }

    public function emailLists() {
        $result = $this->emailService->emailLists($this->emailRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    //获取我的邮件类别 我的邮件菜单

    public function getMyEmail() {
        $result = $this->emailService->getMyEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 增加用户文件夹目录
     *
     * @return int 文件夹自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addEmailBox() {
        $result = $this->emailService->addEmailBox($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    public function getOneBox($boxId) {
        //user_id
        $result = $this->emailService->getOneBox($this->emailRequest->all(), $boxId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑用户的文件夹
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editEmailBox() {
        $result = $this->emailService->editEmailBox($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除文件夹
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteEmailBox() {
        $result = $this->emailService->deleteEmailBox($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    //根据条件获取收件箱 未读已读个数
    public function getEmailReceiveNum() {
        $result = $this->emailService->getEmailReceiveNum($this->emailRequest->all());
        return $this->returnResult($result);
    }

    //根据条件获取发件箱已读个数
    public function getOutEmailNum() {
        $result = $this->emailService->getOutEmailNum($this->emailRequest->all());
        return $this->returnResult($result);
    }

    public function getTempEmailNum() {
        $result = $this->emailService->getTempEmailNum($this->emailRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 邮件列表展示
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getEmail() {
        $result = $this->emailService->getEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 新增邮件(发送|草稿)
     *
     * @return int 返回自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function newEmail() {
        $result = $this->emailService->newEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    public function getEmailInfo($emailId) {
        //邮件Email_id 收件箱的id user_id
        $result = $this->emailService->getEmailInfo($this->emailRequest->all(), $emailId, $this->own['user_id']);
        return $this->returnResult($result);
    }

    public function getEmailData($emailId) {
        //邮件Email_id  user_id
        $result = $this->emailService->getEmailData($this->emailRequest->all(), $emailId, $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 使用签名
     *
     * @return string 使用签名信息
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function useEmailSign() {

        $result = $this->emailService->useEmailSign($this->emailRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 删除邮件
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteEmail() {

        $result = $this->emailService->deleteEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑邮件
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editEmail() {
        $result = $this->emailService->editEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 转发邮件
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function relayEmail() {

        $result = $this->emailService->newEmail($this->emailRequest->all(),$this->own);

        return $this->returnResult($result);
    }

    /**
     * 回复邮件
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function replyEmail() {
        $result = $this->emailService->replyEmail($this->emailRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 收件箱转移目录
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function transferEmail() {
        $result = $this->emailService->transferEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 导出邮件
     *
     * @todo 导出邮件
     *
     * @return
     */
    public function exportEmail($emailId) {
        $result = $this->emailService->exportEmail($emailId);
        return $this->returnResult($result);
    }

    /**
     * 下载邮件
     *
     */
    public function downZipEmail() {
        $result = $this->emailService->downZipEmail($this->emailRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 撤销邮件
     */
    public function emailRecycle($emailId) {
        $result = $this->emailService->emailRecycle($emailId, $this->emailRequest->all());
        return $this->returnResult($result);
    }

    //清空邮件（回收站）
    public function truncateEmail() {
        $result = $this->emailService->truncateEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    //撤销删除的邮件（回收站）
    public function recycleDeleteEmail() {
        $result = $this->emailService->recycleDeleteEmail($this->emailRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    //删除系统邮件
    public function systemEmailDelete() {
        $result = $this->emailService->systemEmailDelete($this->emailRequest->all());
        return $this->returnResult($result);
    }

    public function getEmailNums($boxId) {
        $result = $this->emailService->getEmailNums($boxId, $this->own['user_id']);
        return $this->returnResult($result);
    }
    public function downloadEml($emailId)
    {
        $result = $this->emailService->downloadEml($emailId, $this->own['user_id']);
        return $this->returnResult($result);
    }

    public function readStatistics($emailId)
    {
        $result = $this->emailService->readStatistics($emailId, $this->own['user_id']);
        return $this->returnResult($result);
    }

    public function toggleStar()
    {
        $result = $this->emailService->toggleStar($this->emailRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    public function emailReceiveList($emailId) {
        $result = $this->emailService->emailReceiveList($emailId, $this->emailRequest->all());
        return $this->returnResult($result);
    }
}
