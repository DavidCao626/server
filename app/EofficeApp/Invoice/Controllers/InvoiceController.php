<?php


namespace App\EofficeApp\Invoice\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Invoice\Services\InvoiceService;
use App\EofficeApp\Invoice\Services\InvoiceManageService;
use App\EofficeApp\Invoice\Services\InvoiceLogsService;

class InvoiceController  extends Controller
{
    private $request;
    private $invoiceService;
    private $invoiceManageService;

    public function __construct(
        Request $request,
        InvoiceService $invoiceServices,
        InvoiceManageService $invoiceManageService,
        InvoiceLogsService $invoiceLogsService
    ) {
        parent::__construct();

        $this->request = $request;
        $this->invoiceService = $invoiceServices;
        $this->invoiceManageService = $invoiceManageService;
        $this->invoiceLogsService = $invoiceLogsService;
    }

    /** 获取发票列表
     * @return array
     */
    public function getList()
    {
        $result = $this->invoiceService->getList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getUnreimburseInvoiceList()
    {
        $param = $this->request->all();
        $result = $this->invoiceService->getUnreimburseInvoiceList($param, $this->own);
        return $this->returnResult($result);
    }

    /** 获取发票类型
     * @return array
     */
    public function getTypes()
    {
        $result = $this->invoiceService->getTypes($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 获取发票抬头列表
     * @return array
     */
    public function getTitles()
    {
        $result = $this->invoiceService->getTitles($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 获取发票抬头列表
     * @return array
     */
    public function getPersonalTitles()
    {
        $result = $this->invoiceService->getPersonalTitles($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 创建团队
     * @return array
     */
    public function createCorp()
    {
        $result = $this->invoiceService->createCorp($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function updateCorp()
    {
        $result = $this->invoiceService->updateCorp($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 根据团队账号查找
     * @return array
     */
    public function QueryCorpByAccount()
    {
        $result = $this->invoiceService->QueryCorpByAccount($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 查询所有团队
     * @return array
     */
    public function QueryCorpAll()
    {
        $result = $this->invoiceService->QueryCorpAll($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 根据cid查找团队
     * @return array
     */
    public function QueryCorpByCid()
    {
        $result = $this->invoiceService->QueryCorpByCid($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 获取集成的应用信息
     * @return array
     */
    public function checkAppInfo()
    {
        $result = $this->invoiceService->checkAppInfo($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 单个创建用户
     * @return array
     */
    public function syncUser()
    {
        $result = $this->invoiceService->syncUser($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 批量创建用户
     * @return array
     */
    public function batchSyncUser()
    {
        $result = $this->invoiceService->batchSyncUserSchedule($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 更新用户
     * @return array
     */
    public function updateUser()
    {
        $result = $this->invoiceService->updateUser($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 开启验真
     * @return array
     */
    public function openValid()
    {
        $result = $this->invoiceService->openValid($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 开启识别自动验真
     * @return array
     */
    public function openAutoValid()
    {
        $result = $this->invoiceService->openAutoValid($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 获取发票抬头详情
     * @return array
     */
    public function getTitle()
    {
        $result = $this->invoiceService->getTitle($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 新建发票
     * @return array
     */
    public function addInvoice()
    {
        $result = $this->invoiceService->addInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 更新发票
     * @return array
     */
    public function updateInvoice()
    {
        $result = $this->invoiceService->updateInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 删除发票
     * @return array
     */
    public function deleteInvoice()
    {
        $result = $this->invoiceService->deleteInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 获取发票详情
     * @return array
     */
    public function getInvoice()
    {
        $result = $this->invoiceService->getInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getOneInvoice($invoiceId)
    {
        if (strpos($invoiceId, ',') !== false) {
//            $result = ['code' => ['invoice_id_error', 'invoice']];
//            $result = $this->invoiceService->getInvoiceBatch(['ids' => explode(',', $invoiceId)], $this->own);
            $result = [];
            foreach (explode(',', $invoiceId) as $value) {
                $invoice = $this->invoiceService->getInvoice(['id' => $value], $this->own);
                if (isset($invoice['invoice']) && !empty($invoice['invoice'])) {
                    $result['infos'][] = ['info' => $invoice['invoice']];
                }
            }
            $result['total'] = count($result['infos']);
        } else {
            $param = ['id' => $invoiceId];
            $result = $this->invoiceService->getInvoice($param, $this->own);
        }
        return $this->returnResult($result);
    }

    /** 新建发票抬头
     * @return array
     */
    public function addInvoiceTitle()
    {
        $result = $this->invoiceService->addInvoiceTitle($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 更新发票抬头
     * @return array
     */
    public function updateInvoiceTitle()
    {
        $result = $this->invoiceService->updateInvoiceTitle($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 删除发票抬头
     * @return array
     */
    public function deleteInvoiceTitle()
    {
        $result = $this->invoiceService->deleteInvoiceTitle($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getInvoiceManageParams()
    {
        $result = $this->invoiceManageService->getInvoiceManageParams($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function saveInvoiceManageParams()
    {
        $result = $this->invoiceManageService->saveInvoiceManageParams($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getLogs()
    {
        $result = $this->invoiceLogsService->getLogs($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getLog($logId)
    {
        $result = $this->invoiceLogsService->getLog($logId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getFlowSettings()
    {
        $result = $this->invoiceManageService->getFlowSettings($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getFlowSetting($settingId)
    {
        $result = $this->invoiceManageService->getFlowSetting($settingId, $this->request->all());
        return $this->returnResult($result);
    }

    public function addFlowSetting()
    {
        $result = $this->invoiceManageService->addFlowSetting($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function editFlowSetting($settingId)
    {
        $result = $this->invoiceManageService->editFlowSetting($this->request->all(), $settingId, $this->own);
        return $this->returnResult($result);
    }
    public function deleteFlowSetting($settingId)
    {
        $result = $this->invoiceManageService->deleteFlowSetting($settingId, $this->own);
        return $this->returnResult($result);
    }

    public function invoiceFileUpload()
    {
        $result = $this->invoiceService->invoiceFileUpload($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function userList()
    {
        $result = $this->invoiceService->userList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function validInvoice()
    {
        $result = $this->invoiceService->validInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function validInputInvoice()
    {
        $result = $this->invoiceService->validInputInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function checkService()
    {
        $result = $this->invoiceService->checkService($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function wxAddInvoice()
    {
        $result = $this->invoiceService->wxAddInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function syncCorpConfig()
    {
        $result = $this->invoiceService->syncCorpConfig($this->own);
        return $this->returnResult($result);
    }

    public function totalCorp()
    {
        $result = $this->invoiceService->totalCorp($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function corpValidUsers()
    {
        $result = $this->invoiceService->corpValidUsers($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function corpReimVtm()
    {
        $result = $this->invoiceService->corpReimVtm($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function receiptsInvoices()
    {
        $result = $this->invoiceService->receiptsInvoices($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function salesInvoices()
    {
        $result = $this->invoiceService->salesInvoices($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function corpInvoices()
    {
        $result = $this->invoiceService->corpInvoices($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function invoiceStatistics()
    {
        $result = $this->invoiceService->invoiceStatistics($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function checkFlowRelation()
    {
        $result = $this->invoiceManageService->checkFlowRelation($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function corpTaxno()
    {
        $result = $this->invoiceService->corpTaxno($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function corpTaxnos()
    {
        $result = $this->invoiceService->corpTaxnos($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function thirdSetAppkey()
    {
        $result = $this->invoiceService->thirdSetAppkey($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function reim()
    {
        $result = $this->invoiceService->reim($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getThirdAppkey()
    {
        $result = $this->invoiceService->getThirdAppkey();
        return $this->returnResult($result);
    }
    /**
     * 更改三方发票状态时获取三方token
     *
     * @apiTitle 获取微信token
     * @param {int} source 当前三方来源  10 企业微信 11 微信公众号
     *
     * @paramExample {json} 参数示例
     * {
     *  "source": "10",
     * }
     *
     * @success {boolean} status(1) 获取成功
     * @success {array} data 返回appkey和token
     *
     * @successExample {json} Success-Response:
     * {
     *  "status": 1,
     *  "data": {
     *      "appkey": "61bb77aa7c4eb8e3bb8b20c0ac72ab1e", // appkey 如不一致，请重新记录对应的appkey
     *      "token": "dsfdfgjdfgk899078678asd"  // 加密后的token
     *   }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getThirdAccessToken()
    {
        $result = $this->invoiceService->getThirdAccessToken($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 创建应用
     * @return array
     */
    public function createApp()
    {
        $result = $this->invoiceService->createApp($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 编辑应用
     * @return array
     */
    public function updateApp($configId)
    {
        $result = $this->invoiceService->updateApp($this->request->all(), $this->own, $configId);
        return $this->returnResult($result);
    }

    /** 查询应用
     * @return array
     */
    public function queryApp()
    {
        $result = $this->invoiceService->queryApp($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function corpValidInvoice()
    {
        $result = $this->invoiceService->corpValidInvoice($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getIsUseInvoiceCloud()
    {
        $result = $this->invoiceService->getIsUseInvoiceCloud($this->own, $this->request->all());
        return $this->returnResult($result);
    }

    public function getImportUrl()
    {
        $result = $this->invoiceService->getImportUrl($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getRecharge()
    {
        $result = $this->invoiceService->getRecharge($this->own);
        return $this->returnResult($result);
    }

    public function getRechargeLog()
    {
        $result = $this->invoiceService->getRechargeLog($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getSettingsOnlyIdName()
    {
        $result = $this->invoiceManageService->getSettingsOnlyIdName($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getDefaultSetting()
    {
        $result = $this->invoiceManageService->getDefaultSetting($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function shareUser()
    {
        $result = $this->invoiceService->shareUser($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function shareSync()
    {
        $result = $this->invoiceService->shareSync($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function openRecognize()
    {
        $result = $this->invoiceService->openRecognize($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getValidRight($type = 'person')
    {
        $result = $this->invoiceService->getValidRight($this->own, $type);
        return $this->returnResult($result);
    }

    public function getInvoiceBatch()
    {
        $result = $this->invoiceService->getInvoiceBatch($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /*
     * 报销前检测
     */
    public function checkBefore()
    {
        $result = $this->invoiceService->checkBefore($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /*
     * 手动取消报销
     */
    public function cancelInvoice($runId = 0)
    {
        // 手动取消报销
        $params = $this->request->all();
        $params['reim'] = json_encode(['dataid' => $runId]);
        $result = $this->invoiceService->cancelInvoice($params, $this->own, $runId);
        return $this->returnResult($result);
    }

    public function getInvoiceParams()
    {
        $result = $this->invoiceService->getInvoiceParams($this->own, $this->request->all());
        return $this->returnResult($result);
    }
    public function getInvoiceField()
    {
        $result = $this->invoiceService->getInvoiceField($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function invoiceFieldKeys()
    {
        $params = $this->request->all();
        $params['type'] = $params['type'] ?? 'all';
        $result = $this->invoiceService->invoiceFieldKeys($params, $this->own);
        return $this->returnResult($result);
    }
}
