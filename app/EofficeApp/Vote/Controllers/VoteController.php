<?php
namespace App\EofficeApp\Vote\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Vote\Services\VoteService;
use App\EofficeApp\Vote\Requests\VoteRequest;


class VoteController extends Controller
{
    public function __construct(
        Request $request,
        VoteService $voteService,
        VoteRequest $voteRequest
    ) {
        parent::__construct();
        $this->voteService = $voteService;
        $this->voteRequest = $voteRequest;
        $this->formFilter($request, $voteRequest);
        $this->request = $request;
    }

    /**
     * 获取调查表列表
     */
    public function getVoteManageList() {
        $result = $this->voteService->getVoteManageList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取调查表设置详情
     */
    public function getVoteManageInfo($voteId) {
        $result = $this->voteService->getVoteManageInfo($voteId,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 编辑调查表
     */
    public function editVoteManage($voteId) {
        $result = $this->voteService->editVoteManage($this->request->all(),$voteId,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 添加调查表
     */
    public function addVoteManage() {
        $result = $this->voteService->addVoteManage($this->request->all(),$this->own);
        return $this->returnResult($result);
    }
    /**
     * 删除调查表
     */
    public function deleteVoteManage($voteId) {
        $result = $this->voteService->deleteVoteManage($voteId,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 编辑调查表设计器
     */
    public function editVoteDesign($voteId) {
        $result = $this->voteService->editVoteDesign($this->request->all(),$voteId,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 获取参与的调查列表
     */
    public function getMineList() {
        $result = $this->voteService->getMineList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }
    /**
     * 获取参与的开启登录发起调查的列表
     */
    public function getAfterLoginOpenList() {
        $result = $this->voteService->getAfterLoginOpenList($this->own);
        return $this->returnResult($result);
    }
    /**
     * 参与调查保存数据
     */
    public function saveVoteData($voteId) {
        $result = $this->voteService->saveVoteData($this->request->all(),$voteId,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 更新调查表状态
     */
    public function updateVoteManage($voteId) {
        $result = $this->voteService->updateVoteManage($this->request->all(),$voteId,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 获取投票明细
     */
    public function getVoteResult($voteId) {
        $result = $this->voteService->getVoteResult($voteId);
        return $this->returnResult($result);
    }
    /**
     * 获取参与统计人员
     */
    public function getVoteInUser($voteId) {
        $result = $this->voteService->getVoteInUser($voteId,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 获取投票数据列表
     */
    public function getVoteDataList($voteId) {
        $result = $this->voteService->getVoteDataList($voteId,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }
    /**
     * 获取投票数据列表
     */
    public function getVoteInDetail($id) {
        $result = $this->voteService->getVoteInDetail($id,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }
    /**
     * 获取样式列表
     */
    public function getVoteModeList() {
        $result = $this->voteService->getVoteModeList($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 新建样式
     */
    public function addVoteMode() {
        $result = $this->voteService->addVoteMode($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 编辑样式
     */
    public function editVoteMode($modeId) {
        $result = $this->voteService->editVoteMode($modeId,$this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 删除样式
     */
    public function deleteVoteMode($modeId) {
        $result = $this->voteService->deleteVoteMode($modeId);
        return $this->returnResult($result);
    }
    /**
     * 删除投票记录
     */
    public function deleteVoteResult($voteId) {
        $result = $this->voteService->deleteVoteResult($voteId,$this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 删除全部投票记录
     */
    public function deleteAllVoteResult($voteId) {
        $result = $this->voteService->deleteAllVoteResult($voteId);
        return $this->returnResult($result);
    }
    /**
     * 恢复默认样式
     */
    public function defaultVoteMode($modeId) {
        $result = $this->voteService->defaultVoteMode($modeId);
        return $this->returnResult($result);
    }
    /**
     * 获取样式数据
     */
    public function getVoteMode($modeId) {
        $result = $this->voteService->getVoteMode($modeId);
        return $this->returnResult($result);
    }

}
