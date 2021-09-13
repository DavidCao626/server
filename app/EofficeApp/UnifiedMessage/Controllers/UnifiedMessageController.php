<?php

namespace App\EofficeApp\UnifiedMessage\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\UnifiedMessage\Requests\UnifiedMessageRequest;
use App\EofficeApp\UnifiedMessage\Services\UnifiedMessageService;
use App\EofficeApp\UnifiedMessage\Services\HeterogeneousSystemService;
use App\EofficeApp\UnifiedMessage\Services\MessageDataService;
use App\EofficeApp\UnifiedMessage\Services\UserBondingService;

/**
 * 统一消息待办控制器
 * Class UnifiedMessageController
 * @package App\EofficeApp\UnifiedMessage\Controllers
 */
class UnifiedMessageController extends Controller
{

    public $unifiedMessageRequest;
    //统一消息服务
    public $unifiedMessageService;
    //统一消息 -- 异构系统服务
    public $heterogeneousSystemService;
    //统一消息 -- 数据服务
    public $messageDataService;
    //统一消息 -- 用户关联服务
    public $userAssociatedService;

    public function __construct(
        Request $request,
        UnifiedMessageRequest $unifiedMessageRequest,
        UnifiedMessageService $unifiedMessageService,
        HeterogeneousSystemService $heterogeneousSystemService,
        MessageDataService $messageDataService,
        UserBondingService $userAssociatedService
    ) {
        parent::__construct();
        $this->unifiedMessageRequest = $request;
        $this->unifiedMessageService = $unifiedMessageService;
        $this->heterogeneousSystemService = $heterogeneousSystemService;
        $this->messageDataService = $messageDataService;
        $this->userAssociatedService = $userAssociatedService;
        $this->formFilter($request, $unifiedMessageRequest);
    }

    /**
     * 注册异构系统标识
     * @return string
     * @author [dosy]
     */
    public function registerHeterogeneousSystemCode()
    {
        $userId = $this->own['user_id'];
        $data = $this->heterogeneousSystemService->registerHeterogeneousSystemCode($userId);
        return $this->returnResult($data);
    }

    /**
     * 注册异构系统标识
     * @return string
     * @author [dosy]
     */
    public function refreshHeterogeneousSystemCode()
    {
        $param = $this->unifiedMessageRequest->all();
        $userId = $this->own['user_id'];
        $data = $this->heterogeneousSystemService->refreshHeterogeneousSystemCode($param, $userId);
        return $this->returnResult($data);
    }

    /**
     * 注册异构系统
     * @author [dosy]
     */
    public function registerHeterogeneousSystem()
    {
        $param = $this->unifiedMessageRequest->all();
        $userId = $this->own['user_id'];
        $data = $this->heterogeneousSystemService->registerHeterogeneousSystem($param, $userId);
        return $this->returnResult($data);
    }

    /**
     * 删除异构系统
     * @author [dosy]
     */
    public function deleteHeterogeneousSystem($id)
    {
        $data = $this->heterogeneousSystemService->deleteHeterogeneousSystem($id);
        return $this->returnResult($data);
    }

    /**
     * 编辑异构系统
     * @author [dosy]
     */
    public function editHeterogeneousSystem($id)
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->heterogeneousSystemService->editHeterogeneousSystem($id, $param);
        return $this->returnResult($data);
    }

    /**
     * 查询异构系统
     * @author [dosy]
     */
    public function getHeterogeneousSystem($id)
    {
        $data = $this->heterogeneousSystemService->getHeterogeneousSystem($id);
        return $this->returnResult($data);
    }

    /**
     * 查询异构系统列表
     * @author [dosy]
     */
    public function getHeterogeneousSystemList()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->heterogeneousSystemService->getHeterogeneousSystemList($param);
        return $this->returnResult($data);
    }

    /**
     * 查询异构系统消息类型列表
     * @author [dosy]
     */
    public function getHeterogeneousSystemMessageTypeList()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->heterogeneousSystemService->getHeterogeneousSystemMessageTypeList($param);
        return $this->returnResult($data);
    }

    /**
     * 获取消息域名，同时阅读消息
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getDomainReadMessage()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->heterogeneousSystemService->getDomainReadMessage($param, $this->own['user_id']);
        return $this->returnResult($data);
    }

    /**
     * 编辑接收消息开关
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function editMessageSwitch()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->heterogeneousSystemService->editMessageSwitch($param);
        return $this->returnResult($data);
    }

    /******************************************************对外接口**************************************************/
    /**
     * 注册获取token
     * @author [dosy]
     */
    public function registerToken()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->unifiedMessageService->registerToken($param);
        return $this->returnResult($data);
    }

    /**
     * 接收第三方消息数据
     * @author [dosy]
     */
    public function acceptMessageData()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->messageDataService->acceptMessageData($param);
        return $this->returnResult($data);
    }

    /**
     * 删除指定人消息
     * @author [dosy]
     */
    public function deleteDesignatedPersonMessage()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->messageDataService->deleteDesignatedPersonMessage($param);
        return $this->returnResult($data);
    }

    /**
     * 删除消息ById
     * @author [dosy]
     */
    public function deleteMessage()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->messageDataService->deleteMessage($param);
        return $this->returnResult($data);
    }

    /**
     * 修改消息状态（已处理、已读）
     * @author [dosy]
     */
    public function editMessageState()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->messageDataService->editMessageState($param);
        return $this->returnResult($data);
    }
//
//    /**
//     * 删除所有消息
//     * @author [dosy]
//     */
//    public function deleteAllMessage()
//    {
//        $data = $this->messageDataService->deleteAllMessage();
//        return $this->returnResult($data);
//    }

    /******************************************************end**********************************************/

    /**
     * 获取消息ById
     * @author [dosy]
     */
    public function getMessageById($messageId)
    {
        $data = $this->messageDataService->getMessageById($messageId);
        return $this->returnResult($data);
    }

    /**
     * 获取消息列表
     * @author [dosy]
     */
    public function getMessageList()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->messageDataService->getMessageList($param);
        return $this->returnResult($data);
    }

    /**
     * 返回门户消息数据
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function portalData()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->messageDataService->portalData($param, $this->own['user_id']);
        return $this->returnResult($data);
    }

    /**
     * 删除消息deleteMessageByWhere【目前ID】
     * @author [dosy]
     */
    public function deleteMessageByWhere($id)
    {
        $data = $this->messageDataService->deleteMessageByWhere($id, $this->own['user_id']);
        return $this->returnResult($data);
    }

    /**
     * 批量删除消息
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function batchDeleteMessage()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->messageDataService->batchDeleteMessage($param, $this->own['user_id']);
        return $this->returnResult($data);
    }

    /**
     * 阅读消息
     * @param $id
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function readMessage($id)
    {
        $data = $this->messageDataService->readMessage($id, $this->own['user_id']);
        return $this->returnResult($data);
    }

    /**
     * 添加用户关联
     * @author [dosy]
     */
    public function addUserBonding()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->userAssociatedService->addUserBonding($param);
        return $this->returnResult($data);
    }

    /**
     * 删除用户关联ById
     * @author [dosy]
     */
    public function deleteUserBondingById($id)
    {
        $data = $this->userAssociatedService->deleteUserBondingById($id);
        return $this->returnResult($data);
    }

    /**
     * 批量删除用户关联
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function batchDeleteUserBinding()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->userAssociatedService->batchDeleteUserBinding($param);
        return $this->returnResult($data);
    }

    /**
     * 删除所有用户关联
     * @author [dosy]
     */
    public function deleteAllUserBonding()
    {
        $data = $this->userAssociatedService->deleteAllUserBonding();
        return $this->returnResult($data);
    }

    /**
     * 编辑用户关联
     * @author [dosy]
     */
    public function editUserBonding($id)
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->userAssociatedService->editUserBonding($id, $param);
        return $this->returnResult($data);
    }

    /**
     * 查看用户关联
     * @author [dosy]
     */
    public function getUserBondingById($id)
    {
        $data = $this->userAssociatedService->getUserBondingById($id);
        return $this->returnResult($data);
    }

    /**
     * 用户关联列表
     * @author [dosy]
     */
    public function getUserBondingList()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->userAssociatedService->getUserBondingList($param);
        return $this->returnResult($data);
    }

    /**
     * 导出用户关联模板
     * @author [dosy]
     */
    public function exportUserBonding()
    {
        $data = $this->userAssociatedService->exportUserBonding();
        return $this->returnResult($data);
    }

    /**
     * 批量导入用户关联
     * @author [dosy]
     */
    public function importUserBonding()
    {
        $data = $this->userAssociatedService->importUserBonding();
        return $this->returnResult($data);
    }

    /**
     * 外部系统用户关联sso
     * @author [dosy]
     */
    public function externalSystemUserBonding()
    {
        $data = $this->userAssociatedService->externalSystemUserBonding();
        return $this->returnResult($data);
    }

    /**
     * 添加日志
     * @author [dosy]
     */
    public function addLog()
    {
        $data = $this->unifiedMessageService->addLog();
        return $this->returnResult($data);
    }

    /**
     * 日志详情ById
     * @author [dosy]
     */
    public function getLogById($id)
    {
        $data = $this->unifiedMessageService->getLogById($id);
        return $this->returnResult($data);
    }

    /**
     * 日志列表
     * @author [dosy]
     */
    public function getLogList()
    {
        $param = $this->unifiedMessageRequest->all();
        $data = $this->unifiedMessageService->getLogList($param);
        return $this->returnResult($data);
    }

    /**
     * 下载文档
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @author [dosy]
     */
    public function getAPIConfig()
    {
        return response()->download("unifiedMessage" . DIRECTORY_SEPARATOR . "关于统一消息使用及API接入说明.doc");
    }
}
