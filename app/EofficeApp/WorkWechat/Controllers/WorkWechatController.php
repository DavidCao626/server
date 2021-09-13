<?php

namespace App\EofficeApp\WorkWechat\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\WorkWechat\Requests\WorkWechatRequest;
use App\EofficeApp\WorkWechat\Services\WorkWechatService;
use Illuminate\Http\Request;

class WorkWechatController extends Controller
{

    public function __construct(
        Request $request,
        WorkWechatService $WorkWechatService,
        WorkWechatRequest $WorkWechatRequest
    ) {
        parent::__construct();
        $this->WorkWechatService = $WorkWechatService;
        $this->WorkWechatRequest = $request;
        $this->formFilter($request, $WorkWechatRequest);
    }

    //下载文档
    public function getEnterpriseWechat()
    {
        //return response()->download("wechat" . DIRECTORY_SEPARATOR . "enterprise-wechat.doc");
        return response()->download("wechat" . DIRECTORY_SEPARATOR . "关于应用配置说明文档详细.doc");
    }

    //保存企业微信配置
    public function saveWorkWechat()
    {
        $result = $this->WorkWechatService->saveWorkWechat($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }
    // 保存消息推送菜单
    public function saveRemindMenu()
    {
        $result = $this->WorkWechatService->saveRemindMenu($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }
    //保存企业微信消息推送
    public function saveWechatAppPush()
    {
        $result = $this->WorkWechatService->saveWechatAppPush($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //获取配置信息
    public function getWorkWechat()
    {
        $result = $this->WorkWechatService->getWorkWechat();
        return $this->returnResult($result);
    }

    //保存app应用
    public function wechatAppSave()
    {
        $result = $this->WorkWechatService->wechatAppSave($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }
    //获取消息提醒菜单
    public function getRemindMenu(){
        $result = $this->WorkWechatService->getRemindMenu();
        return $this->returnResult($result);
    }
    //微信验证
    public function wechatAuth()
    {
        $result = $this->WorkWechatService->wechatAuth($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //企业微信接入
    public function workwechatAccess()
    {
        $result = $this->WorkWechatService->workwechatAccess($this->WorkWechatRequest->all());
        return $this->returnResult($result);

    }

    //下载文件
    public function workwechatMove()
    {
        $result = $this->WorkWechatService->workwechatMove($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //配置检验
    public function workwechatCheck()
    {
        $result = $this->WorkWechatService->workwechatCheck($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //获取微信应用
    public function wechatAppGet()
    {
        $result = $this->WorkWechatService->wechatAppGet($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //删除微信应用
    public function wechatAppDelete($id)
    {

        $result = $this->WorkWechatService->wechatAppDelete($id);
        return $this->returnResult($result);
    }

    //获取SignPackage
    public function workwechatSignPackage()
    {
        $result = $this->WorkWechatService->workwechatSignPackage($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //获取SignPackage
    public function getSignatureAndConfig()
    {
        $result = $this->WorkWechatService->getSignatureAndConfig($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //同步组织架构
    public function sync()
    {
        $result = $this->WorkWechatService->syncOrganization($this->own);
        return $this->returnResult($result);
    }

    //手机号关联
    public function phoneNumberAssociation()
    {
        $result = $this->WorkWechatService->userListWechat();
        return $this->returnResult($result);
    }

    //清空微信配置
    public function workwechatTruncate()
    {
        $result = $this->WorkWechatService->workwechatTruncate($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //用户列表
    public function userListWechat()
    {
        $result = $this->WorkWechatService->userListWechat();
        return $this->returnResult($result);
    }

    public function tranferUser()
    {
        $result = $this->WorkWechatService->tranferUser($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    //高德获取位置信息
    public function geocodeAttendance()
    {
        $result = $this->WorkWechatService->geocodeAttendance($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取企业微信同步通讯录日志
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getSyncLogList()
    {
        $result = $this->WorkWechatService->getSyncLogList($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    public function getInvoiceParam()
    {
        $result = $this->WorkWechatService->getInvoiceParam($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }
    
    public function saveWorkWeChatSync(){
        $result = $this->WorkWechatService->saveWorkWeChatSync($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    public function syncCallback(){
        $result = $this->WorkWechatService->syncCallback($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 备份组织架构
     * @return array
     * @author [dosy]
     */
    public function syncDataBackup(){
        $result = $this->WorkWechatService->syncDataBackup($this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 还原组织架构
     * @return array
     * @author [dosy]
     */
    public function syncDataReduction(){
        $result = $this->WorkWechatService->syncDataReduction($this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 获取企业微信同步考勤设置
     * @return array
     * @creatTime 2020/12/28 17:02
     * @author [dosy]
     */
    public function getAttendanceSyncSet(){
        $result = $this->WorkWechatService->getAttendanceSyncSet();
        return $this->returnResult($result);
    }

    /**
     * 手动同步考勤
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @creatTime 2020/12/28 17:02
     * @author [dosy]
     */
    public function syncAttendance(){
        $result = $this->WorkWechatService->syncAttendance($this->WorkWechatRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 考勤同步日志
     * @return array
     * @creatTime 2020/12/30 16:17
     * @author [dosy]
     */
    public function getSyncAttendanceLog(){
      //  return $this->WorkWechatService->downSyncAttendanceLog($this->WorkWechatRequest->all());
        $result = $this->WorkWechatService->getSyncAttendanceLog($this->WorkWechatRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 下载考勤日志
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @creatTime 2020/12/30 16:17
     * @author [dosy]
     */
    public function downSyncAttendanceLog(){
        return $this->WorkWechatService->downSyncAttendanceLog($this->WorkWechatRequest->all());
    }

    public function checkSyncAttendanceLog(){
        $result = $this->WorkWechatService->checkSyncAttendanceLog($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 企业微信外部联系人获取客户联系人卡片
     * @return array
     * @author [dosy]
     */
    public function getCustomerLinkman(){
        $result = $this->WorkWechatService->getCustomerLinkman($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 保存企业微信外部联系人和客户联系人的关联关系
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function saveWorkWechatWithCustomer(){
        $result = $this->WorkWechatService->saveWorkWechatWithCustomer($this->WorkWechatRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除企业微信外部联系人和客户联系人绑定关系
     * @return array
     * @author [dosy]
     */
    public function deleteWorkWechatWithCustomer(){
        $result = $this->WorkWechatService->deleteWorkWechatWithCustomer($this->WorkWechatRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取当前登录用户企业微信外部联系人列表
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatExternalContactList(){
        $result = $this->WorkWechatService->getWorkWeChatExternalContactList($this->WorkWechatRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     *  获取企业微信当前用户群主所有客户群详细
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatGroupChatListDetail(){
        $result = $this->WorkWechatService->getWorkWeChatGroupChatListDetail($this->own);
        return $this->returnResult($result);
    }

    /**
     * 保存企业微信客户群和客户关系
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function saveWorkWeChatGroupWithCustomer(){
        $result = $this->WorkWechatService->saveWorkWeChatGroupWithCustomer($this->WorkWechatRequest->all(),$this->own);
        return $this->returnResult($result);
    }
    /**
     * 删除企业微信客户群绑定
     * @return array
     * @author [dosy]
     */
    public function deleteWorkWechatGroupWithCustomer(){
        $result = $this->WorkWechatService->deleteWorkWechatGroupWithCustomer($this->WorkWechatRequest->all(),$this->own);
        return $this->returnResult($result);
    }
    /**
     * 通过企业微信客户群chatId获取客户联系人
     * @return array
     * @author [dosy]
     */
    public function getCustomer(){
        $result = $this->WorkWechatService->getCustomer($this->WorkWechatRequest->all());
        return $this->returnResult($result);
    }
}
