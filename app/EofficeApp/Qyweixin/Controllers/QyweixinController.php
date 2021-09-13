<?php

namespace App\EofficeApp\Qyweixin\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Qyweixin\Requests\QyweixinRequest;
use App\EofficeApp\Qyweixin\Services\QyweixinService;

/**
 * 生日贺卡控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class QyweixinController extends Controller {

    public function __construct(
    Request $request, QyweixinService $qyweixinService, QyweixinRequest $qyweixinRequest
    ) {
        parent::__construct();
        $this->qyweixinService = $qyweixinService;
        $this->qyweixinRequest = $request;
        $this->formFilter($request, $qyweixinRequest);
    }

    public function checkWechat() { //检查
        $result = $this->qyweixinService->checkWechat($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    public function getWechat() {

        $result = $this->qyweixinService->getWechat();
        return $this->returnResult($result);
    }

    public function saveWechat() {
        $result = $this->qyweixinService->saveWechat($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    public function truncateWechat() {
        $result = $this->qyweixinService->truncateWechat();
        return $this->returnResult($result);
    }

    //接入
    public function wechatAuth() {
        $result = $this->qyweixinService->wechatAuth($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //AuthCode
    public function qywechatOauth() { //auth2 验证
        $result = $this->qyweixinService->qywechatOauth($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //更新菜单
    public function qywechatMenu() {
        $result = $this->qyweixinService->qywechatMenu($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //code获取用户
    public function qywechatCode($code) {
        $result = $this->qyweixinService->qywechatCode($code);
        return $this->returnResult($result);
    }

    //js-sdk
    public function qywechatSignPackage() {
        $result = $this->qyweixinService->qywechatSignPackage($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //高德获取位置信息
    public function geocodeAttendance() {
        $result = $this->qyweixinService->geocodeAttendance($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //下载文件
    public function qyweixinMove() {
        $result = $this->qyweixinService->qyweixinMove($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //qywechatChat
    public function qywechatChat() {
        $result = $this->qyweixinService->qywechatChat($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //微信关注（非）用户
    public function userListWechat() {
        $result = $this->qyweixinService->userListWechat($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //关联用户列表
    public function userListShow() {
        $result = $this->qyweixinService->userListShow($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //获取个数
    public function getUserWechat() {
        $result = $this->qyweixinService->getUserWechat($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //同步组织架构
    public function syncOrganization() {
        $result = $this->qyweixinService->syncOrganization($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //一键应用
    public function oneKey() {
        $result = $this->qyweixinService->oneKey($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //创建用户
    public function createUser() {
        $result = $this->qyweixinService->createUser($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //附近地址
    public function qywechatNearby() {
        $result = $this->qyweixinService->qywechatNearby($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    public function qywechatCheck() {
        $result = $this->qyweixinService->qywechatCheck($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    public function getWechatApp() {
        $result = $this->qyweixinService->getWechatApp();
        return $this->returnResult($result);
    }

    public function updateAppList() {
        $result = $this->qyweixinService->updateAppList($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    //接入验证
    public function qywechatAccess() {
        $result = $this->qyweixinService->qywechatAccess($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

    public function getEnterpriseAccount() {
        return response()->download("wechat".DIRECTORY_SEPARATOR."enterprise-account.doc");
    }

//    public function updateAgentId() {
//        $result = $this->qyweixinService->updateAgentId();
//        return $this->returnResult($result);
//    }

    public function getSyncOrganizationFile() {
        return response()->download("wechat".DIRECTORY_SEPARATOR."同步组织架构.log");
    }

    public function getPlatformUser() {
        $result = $this->qyweixinService->getPlatformUser($this->qyweixinRequest->all());
        return $this->returnResult($result);
    }

}
