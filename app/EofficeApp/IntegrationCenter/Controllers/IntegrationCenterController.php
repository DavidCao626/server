<?php

namespace App\EofficeApp\IntegrationCenter\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\IntegrationCenter\Services\IntegrationCenterService;
use App\EofficeApp\IntegrationCenter\Services\ThirdPartyInterfaceService;
use App\EofficeApp\IntegrationCenter\Services\TodoPushService;

/**
 *
 */
class IntegrationCenterController extends Controller {

    private $request;
    private $integrationCenterService;
    private $todoPushServer;
    public function __construct(
    Request $request, IntegrationCenterService $IntegrationCenterService, ThirdPartyInterfaceService $thirdPartyInterfaceService, TodoPushService $todoPushServer
    ) {
        parent::__construct();

        $this->integrationCenterService = $IntegrationCenterService;
        $this->thirdPartyInterfaceService = $thirdPartyInterfaceService;
        $this->todoPushServer = $todoPushServer;
        $this->request = $request;
    }

    /**
     * 为集成中心看板提供列表数据
     * @author 王炜锋 <[<email address>]>
     * @return [type] [description]
     */
    public function getBoard() {
        $result = $this->integrationCenterService->getBoard($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 为“凭证配置”选择器提供数据的路由(定义流程-外发到凭证配置时用到)
     * @author dingpeng <[<email address>]>
     * @return [type] [description]
     */
    public function getVocherIntergrationConfig() {
        $result = $this->integrationCenterService->getVocherIntergrationConfig($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 获取凭证集成的基本信息配置，要传入凭证类型
     * @author dingpeng <[<email address>]>
     * @return [type] [description]
     */
    public function getVocherIntergrationBaseInfo($vocheType) {
        $result = $this->integrationCenterService->getVocherIntergrationBaseInfo($this->request->all(),$vocheType);
        return $this->returnResult($result);
    }
    /**
     * 保存凭证集成的基本信息配置
     * @author yml <[<email address>]>
     * @return [type] [description]
     */
    public function saveVocherIntergrationBaseInfo($baseId)
    {
        $result = $this->integrationCenterService->saveVocherIntergrationBaseInfo($this->request->all(),$baseId);
        return $this->returnResult($result);
    }
    /**
     * 获取第三方接口集成列表数据
     *
     * @param [type] $type 三方接口类型 (可选值['ocr:OCR识别','videoconference:视频会议'])
     *
     * @return void
     * @author yml
     */
    public function getThirdPartyInterfaceList($type) {
        $result = $this->thirdPartyInterfaceService->getList($this->request->all(), $type);
        return $this->returnResult($result);
    }
    /**
     * 集成中心-新增第三方接口
     *
     * @param [type] $type  三方接口类型 (可选值['ocr:OCR识别','videoconference:视频会议'])
     * @param [type] $configId  配置id
     *
     * @return void
     * @author yml
     */
    public function addThirdPartyInterface($type) {
        $result = $this->thirdPartyInterfaceService->addThirdPartyInterface($this->request->all(), $type, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 编辑第三方接口配置
     *
     * @param [type] $type  三方接口类型 (可选值['ocr:OCR识别','videoconference:视频会议'])
     * @param [type] $configId  配置id
     *
     * @return void
     * @author yml
     */
    public function editThirdPartyInterface($type, $configId) {
        $result = $this->thirdPartyInterfaceService->editThirdPartyInterface($this->request->all(), $type, $configId, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 删除三方接口配置
     *
     * @param [type] $type  三方接口类型 (可选值['ocr:OCR识别','videoconference:视频会议'])
     * @param [type] $configId  配置id
     *
     * @return void
     * @author yml
     */
    public function deleteThirdPartyInterface($type, $configId) {
        $result = $this->thirdPartyInterfaceService->deleteThirdPartyInterface($type, $configId, $this->own);
        return $this->returnResult($result);
    }

    public function getThirdPartyInterfaceOcr($ocrId, $type = 'ocr') {
        $result = $this->thirdPartyInterfaceService->getThirdPartyInterface($ocrId, $this->own, $type);
        return $this->returnResult($result);
    }

    public function getThirdPartyInterfaceByWhere($type) {
        $result = $this->thirdPartyInterfaceService->getThirdPartyInterfaceByWhere($type, $this->own, $this->request->all());
        return $this->returnResult($result);
    }
        /**
     * 集成中心-第三方接口配置-获取详情
     *
     * @param [type] $type  三方接口类型 (可选值['ocr:OCR识别','videoconference:视频会议'])
     * @param [type] $configId  配置id
     *
     * @return void
     * @author yml
     */
    public function getThirdPartyInterfaceInfo($type, $configId) {
        $result = $this->thirdPartyInterfaceService->getThirdPartyInterfaceInfo($type, $configId);
        return $this->returnResult($result);
    }
    /**
     * 获取OCR集成接口配置
     *
     * @param [type] $ocrId OCR集成配置id
     * 20200414-dingpeng-去掉这路由，这路由的功能，被[集成中心-第三方接口-获取详情]覆盖了
     *
     * @return void
     * @author yml
     */
    // public function getThirdPartyInterfaceOcr($ocrId) {
    //     $result = $this->thirdPartyInterfaceService->getThirdPartyInterfaceOcr($ocrId, $this->own);
    //     return $this->returnResult($result);
    // }

    /*****************************待办推送**************/
    /**
     * 推送
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function todoPush()
    {
        $result = $this->todoPushServer->pushData($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 推送系统列表
     * @return array
     * @author [dosy]
     */
    public function todoPushSystemList()
    {
        $result = $this->todoPushServer->todoPushSystemList();
        return $this->returnResult($result);
    }

    /**
     * 推送系统详情
     * @param $id
     * @return array
     * @author [dosy]
     */
    public function todoPushSystemDetail($id)
    {
        $result = $this->todoPushServer->todoPushSystemDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 保存推送设置
     * @return array
     * @author [dosy]
     */
    public function saveTodoPushSystemSetting()
    {
        $result = $this->todoPushServer->saveTodoPushSystemSetting($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除待办推送
     * @param $id
     * @return array
     * @author [dosy]
     */
    public function deleteTodoPushSystem($id){
        $result = $this->todoPushServer->deleteTodoPushSystem($id);
        return $this->returnResult($result);
    }

    /**
     * 推送测试
     * @return array
     * @author [dosy]
     */
    public function todoPushTest(){
        $result = $this->todoPushServer->todoPushTest($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 下载文档
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @author [dosy]
     */
    public function getDoc()
    {
        return response()->download("integrationCenter".DIRECTORY_SEPARATOR."todoPush" . DIRECTORY_SEPARATOR . "关于推送数据说明.doc");
    }

    // 下载 iweboffice控件支持的环境 文档，传入文档名(2015_environment:2015控件环境;2015_control_msi:控件安装包)
    public function getIwebofficeSupportDocument($document)
    {
        $docName = "";
        if($document == '2015_environment') {
            $docName = "iWebOffice2015控件支持的环境.png";
        } else if($document == '2015_control_msi') {
            $docName = "iWebOffice2015.msi";
        }
        if($docName) {
            return response()->download("integrationCenter".DIRECTORY_SEPARATOR."onlineRead".DIRECTORY_SEPARATOR."iwebofficeDocument".DIRECTORY_SEPARATOR.$docName);
        }
    }
}
