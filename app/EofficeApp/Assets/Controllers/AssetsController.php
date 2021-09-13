<?php

namespace App\EofficeApp\Assets\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Assets\Requests\AssetsRequest;
use App\EofficeApp\Assets\Services\AssetsService;

/**
 * 资产管理
 *
 * @author zw
 *
 * @since
 */
class AssetsController extends Controller
{
    /**
     * 资产管理列表
     * @var object
     */
    private $AssetsService;

    public function __construct(
        Request $request,
        AssetsRequest $assetsRequest,
        AssetsService $AssetsService

    ) {
        parent::__construct();
        $userInfo = $this->own;
        $this->userId = $userInfo['user_id'];
        $this->request = $request;
        $this->formFilter($request, $assetsRequest);
        $this->AssetsService = $AssetsService;
    }


    /**
     * 获取资产分类列表
     *
     * @return array 获取资产分类列表
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    function getAssetsType()
    {
        $result = $this->AssetsService->getAssetsType($this->own);
        return $this->returnResult($result);
    }

    /**
     * 创建资产分类
     *
     * @return array 创建资产分类
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    function createType()
    {
        $result = $this->AssetsService->createAssetsType($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 资产分类删除
     *
     * @return array 资产分类删除
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function deleteType($id){
        $result = $this->AssetsService->deleteType($id);
        return $this->returnResult($result);
    }

    /**
     * 资产分类详情
     *
     * @return array 资产分类详情
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function detailType($id){
        $result = $this->AssetsService->getTypeDetail($id);
        return $this->returnResult($result);
    }
    /**
     * 资产分类编辑
     *
     * @return array 资产分类编辑
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function editType($id){
        $result = $this->AssetsService->editType($id,$this->request->all());
        return $this->returnResult($result);
    }




    /**
     * 获取资产列表
     *
     * @return array 获取资产列表
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function getAssetsList(){
        $result = $this->AssetsService->getAssetsList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 门户资产列表
     *
     * @return array 获取门户资产列表
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function portalAssetsList(){
        $result = $this->AssetsService->getportalList($this->request->all());
        return $this->returnResult($result);
    }


    /**
     * 获取申请资产列表
     *
     * @return array 获取资产列表
     *
     * @author zw
     *
     * @since  2018-08-16
     */
    public function assetTypesList($type){
        $result = $this->AssetsService->getSelectDate($type, $this->own,$this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 资产系统选择器
     *
     * @return array 获取资产列表
     *
     * @author zw
     *
     * @since  2018-08-16
     */
    public function assetChoiceList($sign){
        $result = $this->AssetsService->assetChoiceList($sign, $this->own,$this->request->all());
        return $this->returnResult($result);
    }


    /**
     * 公共资产分类列表下拉框接口
     *
     * @return array 资产分类列表
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function selectType(){
        $result = $this->AssetsService->selectType($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 分类table列表
     *
     * @return array 资产分类列表
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function typeList(){
        $result = $this->AssetsService->typeList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 资产入库
     *
     * @return array 获取资产列表
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function creatAssets(){
        $result = $this->AssetsService->creatAsset($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 资产入库详情
     *
     * @return array 详情
     *
     * @author zw
     *
     * @since  2018-04-04
     */
    public function assetsDetail($id){
        $result = $this->AssetsService->getDetail($id);
        return $this->returnResult($result);
    }


    /**
     * 资产使用申请
     *
     * @return array 资产使用申请
     *
     * @author zw
     *
     * @since  2018-06-06
     */
    public function apply(){
        $result = $this->AssetsService->creatApply($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 资产使用申请记录
     *
     * @return array 资产使用申请记录
     *
     * @author zw
     *
     * @since  2018-06-06
     */
    public function applyList(){
        $result = $this->AssetsService->getApplyList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 资产使用申请详情
     *
     * @return array 资产使用申请详情
     *
     * @author zw
     *
     * @since  2018-04-04
     */

    public function applyDetail($id){
        $result = $this->AssetsService->getApplyDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 使用申请删除
     *
     * @return array 使用申请删除
     *
     * @author zw
     *
     * @since  2018-06-06
     */

    public function deleteApply($id){
        $result = $this->AssetsService->deleteApply($id);
        return $this->returnResult($result);
    }


    /**
     * 使用申请审批
     *
     * @return array 使用申请审批
     *
     * @author zw
     *
     * @since  2018-06-07
     */
    public function approvalApply($id){
        $result = $this->AssetsService->approvalApply($id,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 使用资产归还
     *
     * @return array 使用资产归还
     *
     * @author zw
     *
     * @since  2018-06-07
     */
    public function returnApply($id){
        $result = $this->AssetsService->returnApply($id,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 资产变更
     *
     * @return array 资产变更
     *
     * @author zw
     *
     * @since  2018-06-07
     */
    public function assetsChange($id){
        $result = $this->AssetsService->assetsChange($id,$this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 资产变更列表
     *
     * @return array 资产变更
     *
     * @author zw
     *
     * @since  2018-06-07
     */
    public function changeList(){
        $result = $this->AssetsService->changeList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }


    /**
     * 资产变更详情
     *
     * @return array 资产变更详情
     *
     * @author zw
     *
     * @since  2018-06-07
     */
    public function changeDetail($id){
        $result = $this->AssetsService->changeDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 资产维护申请
     *
     * @return array 资产维护申请
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function repair(){
        $result = $this->AssetsService->creatRepair($this->request->all());
        return $this->returnResult($result);
    }


    /**
     * 资产维护申请列表
     *
     * @return array 资产维护申请列表
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function repairList(){
        $result = $this->AssetsService->repairList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }


    /**
     * 资产维护申请列表详情
     *
     * @return array 资产维护申请列表详情
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function repairDetail($id){
        $result = $this->AssetsService->repairDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 维护申请审批完成
     *
     * @return array 维护申请审批完成
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function repairEdit($id){
        $result = $this->AssetsService->repairEdit($id,$this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 资产退库申请
     *
     * @return array 资产退库申请
     *
     * @author zw
     *
     * @since  2018-06-11
     */

    public function createRetiring(){
        $result = $this->AssetsService->createRetiring($this->request->all(),$this->own);
        return $this->returnResult($result);
    }


    /**
     * 资产退库列表
     *
     * @return array 资产退库列表
     *
     * @author zw
     *
     * @since  2018-06-11
     */

    public function retirList(){
        $result = $this->AssetsService->retirList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 资产退库详情
     *
     * @return array 资产退库详情
     *
     * @author zw
     *
     * @since  2018-06-11
     */

    public function retirDetail($id){
        $result = $this->AssetsService->retirDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 新增盘点
     *
     * @return array 新增盘点
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function createInvent(){
        $result = $this->AssetsService->createInvent($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 盘点列表
     *
     * @return array 盘点列表
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function inventoryList(){
        $result = $this->AssetsService->inventoryList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 盘点清单
     *
     * @return array 盘点列表
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function inventoryView($id){
        $result = $this->AssetsService->viewList($id);
        return $this->returnResult($result);
    }


    /**
     * 盘点清单内容列表
     *
     * @return array 盘点列表
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function detailList($id){
        $result = $this->AssetsService->getView($id,$this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 盘点资产状态编辑
     *
     * @return array 盘点列表
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function inventoryStatus($id){
        $result = $this->AssetsService->inventoryEdit($id,$this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 清单履历列表
     *
     * @return array 清单履历列表
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function resumeList(){
        $result = $this->AssetsService->getResumeList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 折旧对账表
     *
     * @return array 折旧对账表
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function account(){
        $result = $this->AssetsService->accountList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 折旧详情
     *
     * @return array 折旧详情
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function accountDetail($id){
        $result = $this->AssetsService->accountDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 资产汇总
     *
     * @return array 资产汇总
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function summary(){
        $result = $this->AssetsService->getSummary($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 生成二维码
     *
     * @return array 生成二维码
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function qrcode($id){
        $result = $this->AssetsService->assetsQrCode($id);
        return $this->returnResult($result);
    }

    /**
     * 生成资产编码
     *
     * @return array 生成资产条码
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function ruleSet($type){
        $result = $this->AssetsService->getRuleSet($type);
        return $this->returnResult($result);
    }

    /**
     * 设置生成资产条码/二维码显示样式
     *
     * @return array 数组
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function setCode(){
        $result = $this->AssetsService->setCode($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取生成资产条码/二维码设置详情
     *
     * @return array 数组
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function ruleDetail(){
        $result = $this->AssetsService->getRule($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 扫码详情页
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function qrcodeDetail($id){
        $result = $this->AssetsService->qrcodeDetail($id);
        return $this->returnResult($result);
    }


    /**
     * 获取自定义字段
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function customFields($table){
        $result = $this->AssetsService->customFields($this->request->all(),$table);
        return $this->returnResult($result);
    }

    public function flowData(){
        $result = $this->AssetsService->flowData($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    public function getFlow($key){
        $result = $this->AssetsService->getFlow($key,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    public function addFlowDefine($key,$id){
        $result = $this->AssetsService->addFlowDefine($key,$id,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 资产入库删除
     *
     * @return array 删除
     *
     * @author zw
     *
     * @since  2020-03-03
     */
    public function deleteStorage($id){
        return $this->returnResult($this->AssetsService->deleteStorage($id));
    }

    public function dataSourceByAssetsId(){
        $result = $this->AssetsService->dataSourceByAssetsId($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 资产盘点删除
     *
     * @return array 删除
     *
     * @author zw
     *
     * @since  2020-03-03
     */
    public function deleteInventory($id){
        return $this->returnResult($this->AssetsService->deleteInventory($id));
    }

    public function applyWay(){
        $result = $this->AssetsService->applyWay();
        return $this->returnResult($result);
    }

}