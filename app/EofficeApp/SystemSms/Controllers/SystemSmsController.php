<?php

namespace App\EofficeApp\SystemSms\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\SystemSms\Requests\SystemSmsRequest;
use App\EofficeApp\SystemSms\Services\SystemSmsService;

/**
 * 内部消息控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class SystemSmsController extends Controller {

    public function __construct(
    Request $request, SystemSmsService $systemSmsService, SystemSmsRequest $systemSmsRequest
    ) {
        parent::__construct();
        $this->systemSmsService = $systemSmsService;
        $this->systemSmsRequest = $request;
        $this->formFilter($request, $systemSmsRequest);
    }

    /**
     * 发送系统消息
     */
    public function addSystemSms() {

        $result = $this->systemSmsService->addSystemSms($this->systemSmsRequest->all());
        return $this->returnResult($result);
    }
    /**
     * 获取我的消息列表
     *
     *  @apiTitle 获取消息列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  autoFixPage: 1
     *  limit: 10
     *  page: 1
     *  search: {"sms_menu":["meeting"]}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          "total": 25, // 消息数量
     *          "list": [ // 消息列表
     *              {
     *                  "id": 23523, // 消息接收人ID
     *                  "sms_id": 12, // 消息提醒类别ID
     *                  "sms_menu": "meeting", // 消息所属模块
     *                  "sms_type": "join", // 消息类别
     *                  "remind_name": "会议参加提醒, // 提醒名称
     *                  "content": "请参加会议：测试开始时间；会议时间：2018-06-20 11:00 ~ 2018-06-20 23:59.....", // 消息内容
     *                  "recipients": "admin", // 接收人
     *                  "contentParam": "", // 消息内容参数
     *                  "link_name": "查看会议", // 链接名称
     *                  "link_url": "meeting.mine.detail({meeting_apply_id:51})", // 链接地址
     *                  "params": "{"meeting_apply_id":"51"}", // 链接参数
     *                  "remind_id": 61, // 消息提醒类别ID
     *                  "remind_flag": 1, // 已读未读, 0:未读, 1:已读
     *                  "remind_state": "meeting.mine.detail", // 消息跳转路由
     *                  "send_time": "2018-07-03 11:24:21", // 消息发送时间
     *                  "deleted": 0, // 删除标识, 0: 未删除, 1: 已删除
     *              }
     *          .....
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function mySystemSms() {
        $result = $this->systemSmsService->mySystemSms($this->own['user_id'], $this->systemSmsRequest->all());
        return $this->returnResult($result);
    }

    public function signSystemSmsRead(){
        $result = $this->systemSmsService->signSystemSmsRead($this->own['user_id'], $this->systemSmsRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 查看具体某一条消息（id）
     * 消息标示 置1
     */
    public function viewSystemSms($id) { //不是消息sms_id
        $result = $this->systemSmsService->viewSystemSms($id);
        return $this->returnResult($result);
    }
    /**
     * 查看具体某一条消息（sms_id）
     * 消息标示 置1
     */
    public function setSmsRead($sms_id) { //不是消息sms_id
        $result = $this->systemSmsService->setSmsRead($sms_id, $this->own['user_id']);
        return $this->returnResult($result);
    }

    //系统消息未读数目
    public function getSystemSmsUnread() {

        $result = $this->systemSmsService->getSystemSmsUnread($this->own['user_id'], $this->systemSmsRequest->all());
        return $this->returnResult($result);
    }

    public function getNewDetailByGroupBySmsType() {
        $result = $this->systemSmsService->getNewDetailByGroupBySmsType($this->own['user_id']);
        return $this->returnResult($result);
    }
    public function getLastTotal() {
        $result = $this->systemSmsService->getLastTotal($this->own['user_id']);
        return $this->returnResult($result);
    }
    public function moduleToReadSms() {
        $result = $this->systemSmsService->moduleToReadSms($this->systemSmsRequest->all(), $this->own['user_id']);
        return $this->returnResult($result);
    }

}
