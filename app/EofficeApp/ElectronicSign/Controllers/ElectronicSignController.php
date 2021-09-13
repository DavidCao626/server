<?php
namespace App\EofficeApp\ElectronicSign\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\ElectronicSign\Requests\ElectronicSignRequest;
use Illuminate\Http\Request;

/**
 * 电子签署 controller
 * 这个类，用来：1、验证request；2、组织数据；3、调用service实现功能；[4、组织返回值]
 *
 * @author yml
 *
 * @since  2019-04-16 创建
 */
class ElectronicSignController extends Controller
{
    /** @var object 电子签署 */
    private $electronicSignService;

    public function __construct(
        Request $request,
        ElectronicSignRequest $electronicSignRequest
    ) {
        parent::__construct();
        $userInfo = $this->own;
        $this->userId = $userInfo['user_id']; // 用户id
        $this->electronicSignService = 'App\EofficeApp\ElectronicSign\Services\ElectronicSignService';
        $this->qiyuesuoService = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoService';
        $this->qiyuesuoRelatedResourceServiceoService = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoRelatedResourceService';
        $this->sealControlCenterService = 'App\EofficeApp\ElectronicSign\Services\SealControlCenterService';
        $this->formFilter($request, $electronicSignRequest);
        $this->request = $request;
    }

    /**
     * 获取契约锁服务列表
     *
     * @return
     */
    public function getServerList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getServerList($data));
    }

    /**
     * 新建契约锁服务
     *
     * @return
     */
    public function addServer()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->addServer($data));
    }

    /**
     * 编辑契约锁服务
     *
     * @return
     */
    public function editServer($serverId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->editServer($serverId, $data));
    }

    /**
     * 获取某一个契约锁服务详情
     *
     * @return
     */
    public function getServerDetail($serverId)
    {
        return $this->returnResult(app($this->electronicSignService)->getServerDetail($serverId));
    }

    /**
     * 删除某个契约锁服务
     *
     * @return
     */
    public function deleteServer($serverId)
    {
        return $this->returnResult(app($this->electronicSignService)->deleteServer($serverId));
    }

    /**
     * 【契约锁服务设置】 获取开关&加密串
     *
     * @return
     */
    public function getServerBaseInfo()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getServerBaseInfo($data));
    }

    /**
     * 【契约锁服务设置】 保存开关&加密串
     *
     * @return
     */
    public function editServerBaseInfo()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->editServerBaseInfo($data));
    }

    /**
     * 获取契约锁集成设置列表
     *
     * @return
     */
    public function getIntegrationList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getIntegrationList($data));
    }

    /**
     * 新建契约锁集成设置
     *
     * @return
     */
    public function addIntegration()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->addIntegration($data));
    }

    /**
     * 编辑契约锁集成设置
     *
     * @return
     */
    public function editIntegration($settingId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->editIntegration($settingId, $data));
    }

    /**
     * 获取某一个契约锁集成设置详情
     *
     * @return
     */
    public function getIntegrationDetail($settingId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getIntegrationDetail($settingId));
    }

    /**
     * 通过流程ID获取某一个契约锁集成设置详情
     *
     * @return
     */
    public function getIntegrationDetailByFlowId($flowId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getIntegrationDetailByFlowId($flowId));
    }

    /**
     * 删除某一个契约锁集成设置
     *
     * @return
     */
    public function deleteIntegration($settingId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->deleteIntegration($settingId));
    }

    /**
     * 【契约锁集成功能】 根据流程id获取关联的合同id
     *
     * @return
     */
    public function getContractIdbyFlowRunId($runId)
    {
        return $this->returnResult(app($this->electronicSignService)->getContractIdbyFlowRunId($runId));
    }

    /**
     * 【契约锁集成功能】 获取签署合同的url，传合同号，流程id，返回完整的url
     *
     * @author dingpeng
     * @param   [type]  $contractId  [$contractId description]
     *
     * @return  [type]               [return description]
     */
    public function getContractSignUrl($contractId)
    {
        $params = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getContractSignUrl($contractId, $params, $this->own));
    }

    /**
     * 【契约锁集成功能】 根据流程id创建合同
     *
     * @return
     */
    public function createContractV1()
    {
        $data = $this->request->all();
        // return $this->returnResult(app($this->qiyuesuoService)->createContractV1($data, $this->own));
        return $this->returnResult(app($this->electronicSignService)->createContractV1($data, $this->own));
    }
    /**
     * 合同查看/下载/打印的地址
     *  请求参数
     * flowId 定义流程id
     * contractId 合同id
     * type 请求类型  【presign 预签署  view 详情 download 下载  print 打印】
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getContractUrl($contractId)
    {
        $params = $this->request->all();
        // return $this->returnResult(app($this->qiyuesuoService)->getContractUrl($contractId, $params, $this->own));
        return $this->returnResult(app($this->electronicSignService)->getContractUrl($contractId, $params, $this->own));
    }
    /**
     * 【流程办理页面调用，契约锁签署标签，获取跟契约锁关联的信息】
     *
     * @return
     */
    public function getFlowRelationQysSignInfo()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getFlowRelationQysSignInfo($data, $this->own));
    }
    /**
     * 【获取发起的契约锁合同列表】
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function contractList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->contractList($data, $this->own));
    }

    /**
     * 【回调契约锁合同状态】
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function changeContractStatus()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->changeContractStatus($data, $this->own));
    }

    /**
     * 工作流契约锁物理用印授权日志
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getSealApplyAuthLogsList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->getSealApplyAuthLogsList($data));
    }

    /**
     * 工作流契约锁物理用印授权文档创建日志
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getSealApplyCreateDocLogsList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->getSealApplyCreateDocLogsList($data));
    }

    /**
     * 工作流契约锁物理用印日志
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getSealApplyLogsList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->getSealApplyLogsList($data));
    }

    /**
     * 工作流契约锁物理用印集成设置--列表
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getSealApplyList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getSealApplyList($data));
    }

    /**
     * 工作流契约锁物理用印集成设置--新建
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function addSealApply()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->addSealApply($data));
    }

    /**
     * 工作流契约锁物理用印集成设置--删除
     * @param $id
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function deleteSealApply($id)
    {
        return $this->returnResult(app($this->electronicSignService)->deleteSealApply($id));
    }

    /**
     * 工作流契约锁物理用印集成设置--编辑
     * @param $id
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function editSealApply($id)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->editSealApply($id, $data));
    }

    /**
     * 工作流契约锁物理用印集成设置--查看
     * @param $id
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getSealApply($id)
    {
        return $this->returnResult(app($this->electronicSignService)->getSealApply($id));
    }

    /**
     * 【契约锁集成功能】 创建物理用印申请
     *
     * @return
     */
    public function createSealApplyV1()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->createSealApplyV1($data, $this->own));
    }
    /**
     * 同步契约锁印章数据---不走队列
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function syncSeals()
    {
        return $this->returnResult(app($this->qiyuesuoService)->syncSeals());
    }
    /**
     * 契约锁相关资源任务列表
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function qysRelatedResourcePlan()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getRelatedResourceTaskList($data));
    }
    /**
     * 同步契约锁相关资源任务 --- 走队列
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function syncQysRelatedResourceTask()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->syncTask($data, $this->own));
    }
    /**
     * 获取物理印章列表
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getPhysicalSeal($flowId)
    {
        $data = $this->request->all();
        $data['flow_id'] = $flowId;
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->getPhysicalSeal($data));
    }
    /**
     * 获取用印类型为电子印章的业务分类列表
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getElectronicSeal($flowId)
    {
        $data = $this->request->all();
        $data['flow_id'] = $flowId;
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->getElectronicSeal($data));
    }
    /**
     * 获取业务分类列表
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getCategory($flowId)
    {
        $data = $this->request->all();
        $data['flow_id'] = $flowId;
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->getCategory($data));
    }

    /**
     * 获取物理用印业务分类列表
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getPhysicalCategory($flowId)
    {
        $data = $this->request->all();
        $data['flow_id'] = $flowId;
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->getPhysicalCategory($data));
    }
    /**
     * 获取电子合同业务分类列表
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getElectronicCategory($flowId)
    {
        $data = $this->request->all();
        $data['flow_id'] = $flowId;
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->getElectronicCategory($data));
    }
    /**
     * 获取文件模板列表
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getTemplate($flowId)
    {
        $data = $this->request->all();
        $data['flow_id'] = $flowId;
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->getTemplate($data));
    }

    public function sealApplyImagesDownload()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->sealApplyImagesDownload($data, $this->own));
    }

    public function relatedResourceLogs()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoRelatedResourceServiceoService)->getLogs($data, $this->own));
    }

    public function checkServer()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->checkServer($data, $this->own));
    }

    /**
     * 工作流契约锁物理用印日志
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getContractLogsList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->electronicSignService)->getContractLogsList($data));
    }
    /**
     * 检测流程是否已集成电子合同和物理用印
     *
     * @return array
     * @author yml
     */
    public function checkFlowRelation($flowId, $settingId)
    {
        $data = $this->request->all();
        $type = $data['type'] ?? '';
        return $this->returnResult(app($this->electronicSignService)->checkFlowRelation($flowId, $settingId, $data));
    }
    //下载文档
    public function getDocument()
    {
        return response()->download("integrationCenter" . DIRECTORY_SEPARATOR . "qiyuesuo" . DIRECTORY_SEPARATOR . "电子合同集成设置操作说明文档.docx");
    }

    //下载文档
    public function getPhysicalSealDocument()
    {
        return response()->download("integrationCenter" . DIRECTORY_SEPARATOR . "qiyuesuo" . DIRECTORY_SEPARATOR . "物理用印集成设置操作说明文档.docx");
    }

    public function autoCheckContractFormConfig()
    {
        $data = $this->request->all();
        $data['type'] = 'contract';
        return $this->returnResult(app($this->electronicSignService)->autoCheckContractFormConfig($data));
    }

    public function autoCheckSealApplyFormConfig()
    {
        $data = $this->request->all();
        $data['type'] = 'seal_apply';
        return $this->returnResult(app($this->electronicSignService)->autoCheckContractFormConfig($data));
    }
    /**
     * 物理用印完成时回调用印图片
     *
     * @return void
     * @author yml
     */
    public function changeSealApplyStatus()
    {
        $data = $this->request->all();
        return json_encode(app($this->qiyuesuoService)->sealApplyCallback($data));
    }

    public function getCompany()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->qiyuesuoService)->getCompany($data));
    }
    
    public function getSealControlCenterSetting()
    {
        return $this->returnResult(app($this->sealControlCenterService)->getSetting());
    }

    public function getSealControlCenterUrl()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->sealControlCenterService)->getUrl($data, $this->own));
    }

    public function fileSignature()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->sealControlCenterService)->fileSignature($data, $this->own));
    }

    public function setSealControiParam()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->sealControlCenterService)->setParam($data));
    }

    public function updateSealControlCenterSetting()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->sealControlCenterService)->updateSealControlCenterSetting($data));
    }

    //下载文档
    public function getSealControlDocument()
    {
        return response()->download("integrationCenter" . DIRECTORY_SEPARATOR . "qiyuesuo" . DIRECTORY_SEPARATOR . "浏览器契约锁单点登录问题解决方案.docx");
    }
}
