<?php

namespace App\EofficeApp\HtmlSignature\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;

/**
 * 签章控制器:提供html签章请求的实现方法
 *
 * @author dingp
 *
 * @since  2018-01-12 创建
 */
class HtmlSignatureController extends Controller
{
	/** @var object 签章service对象*/

	public function __construct(
		Request $request
	) {
		parent::__construct();
		$userInfo = $this->own;
		$this->userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : "";
		$this->htmlSignatureService = 'App\EofficeApp\HtmlSignature\Services\HtmlSignatureService';
		$this->documentService = 'App\EofficeApp\Document\Services\DocumentService';
		$this->htmlSignaturePermissionService = 'App\EofficeApp\HtmlSignature\Services\HtmlSignaturePermissionService';
		$this->signatureConfigService = 'App\EofficeApp\HtmlSignature\Services\SignatureConfigService';
		$this->request = $request;
	}

	/**
	 * 获取签章列表
	 * 传 $documentId 在实际使用中，是run_id
	 *
	 * @return array 签章列表
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function getHtmlSignatureList($documentId)
	{
        if (!app($this->htmlSignaturePermissionService)->verifyHtmlSignatureListPermission(["document_id" => $documentId, "user_id" => $this->userId])) {
            // 20190705-dingpeng-如果获取函数没权限，直接返回空，不再提示"没有权限"
            // return $this->returnResult(['code' => ['0x000006', 'common']]);
            if (!app($this->documentService)->getFlowRunDocument($documentId)) {
                return [];
            }
        }
        $result = app($this->htmlSignatureService)->getHtmlSignatureList($documentId, $this->request->all());
        return $this->returnResult($result);
	}

	/**
	 * 新建签章
	 *
	 * @return int 新建签章id
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function createHtmlSignature($documentId, $signatureId)
	{
		if (!app($this->htmlSignaturePermissionService)->verifyHtmlSignaturePermission(["document_id" => $documentId, "user_id" => $this->userId])) {
			return $this->returnResult(['code' => ['0x000006', 'common']]);
		}
		$result = app($this->htmlSignatureService)->createHtmlSignature($documentId, $signatureId, $this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 编辑签章
	 *
	 * @param  int $signatureId 签章id
	 *
	 * @return array 签章详情
	 *
	 * @author: dingp
	 *
	 * @since：2018-01-12
	 */
	public function editHtmlSignature($documentId, $signatureId)
	{
		if (!app($this->htmlSignaturePermissionService)->verifyHtmlSignaturePermission(["document_id" => $documentId, "user_id" => $this->userId])) {
			return $this->returnResult(['code' => ['0x000006', 'common']]);
		}
		$result = app($this->htmlSignatureService)->editHtmlSignature($documentId, $signatureId, $this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 删除签章
	 *
	 * @param  int|string $signatureId 签章id,多个用逗号隔开
	 *
	 * @return bool 操作是否成功
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function deleteHtmlSignature($documentId, $signatureId)
	{
		if (!app($this->htmlSignaturePermissionService)->verifyHtmlSignaturePermission(["document_id" => $documentId, "user_id" => $this->userId])) {
			return $this->returnResult(['code' => ['0x000006', 'common']]);
		}
		$result = app($this->htmlSignatureService)->deleteHtmlSignature($documentId, $signatureId);
		return $this->returnResult($result);
	}

	/**
	 * 金格签章，签章设置
	 *
	 * @return
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function goldgridSignatureSet()
	{
		$result = app($this->htmlSignatureService)->goldgridSignatureSet($this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 金格签章，获取签章设置
	 *
	 * @return
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function getGoldgridSignatureSet()
	{
		$result = app($this->htmlSignatureService)->getGoldgridSignatureSet($this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 金格签章keysn，获取系统内签章keysn的list
	 *
	 * @return array keysn列表
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function getGoldgridSignatureKeysnList()
	{
		$result = app($this->htmlSignatureService)->getGoldgridSignatureKeysnList($this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 金格签章keysn，新建签章keysn
	 *
	 * @return int 新建签章keysn result
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function createGoldgridSignatureKeysn()
	{
		$result = app($this->htmlSignatureService)->createGoldgridSignatureKeysn($this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 金格签章keysn，编辑签章keysn
	 *
	 * @param  int $userId 用户id
	 *
	 * @return array 签章keysn详情
	 *
	 * @author: dingp
	 *
	 * @since：2018-01-12
	 */
	public function editGoldgridSignatureKeysn($userId)
	{
		$result = app($this->htmlSignatureService)->editGoldgridSignatureKeysn($userId, $this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 金格签章keysn，删除签章keysn
	 *
	 * @param  string $userId 用户id,多个用逗号隔开
	 *
	 * @return bool 操作是否成功
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function deleteGoldgridSignatureKeysn($userId)
	{
		$result = app($this->htmlSignatureService)->deleteGoldgridSignatureKeysn($userId);
		return $this->returnResult($result);
	}

	/**
	 * 金格签章keysn，获取某用户的签章keysn
	 *
	 * @param  string $userId 用户id,多个用逗号隔开
	 *
	 * @return bool 操作是否成功
	 *
	 * @author dingp
	 *
	 * @since  2018-01-12
	 */
	public function getUserGoldgridSignatureKeysn($userId)
	{
		$result = app($this->htmlSignatureService)->getUserGoldgridSignatureKeysn($userId);
		return $this->returnResult($result);
	}

    /** 获取签章插件配置
     * @return array
     */
	public function getSignatureConfig()
	{
		$result = app($this->signatureConfigService)->getSignatureConfig();
		return $this->returnResult($result);
	}

    /** 获取契约锁签章插件配置
     * @return array
     */
	public function getQiyuesuoSignatureConfig()
	{
		$result = app($this->signatureConfigService)->getQiyuesuoSignatureConfig($this->request->all());
		return $this->returnResult($result);
	}

    /** 编辑契约锁签章插件设置
     * @param $settingId
     * @return array
     */
	public function saveQiyuesuoSignatureConfig($settingId)
	{
		$result = app($this->signatureConfigService)->saveQiyuesuoSignatureConfig($this->request->all(), $settingId);
		return $this->returnResult($result);
	}

    /** 保存签章插件配置
     * @return array
     */
	public function saveSignatureConfig()
	{
		$result = app($this->signatureConfigService)->saveSignatureConfig($this->request->all());
		return $this->returnResult($result);
	}

    /** 获取契约锁服务签章 印章列表url地址
     * @return array
     * {
        "status": 1,
        "data": {
        "signUrl": "https:\/\/app-v41.qiyuesuo.me\/weaversign?viewToken=990b2f4c-826b-45c1-bd79-05867f6ce03b&oaUrl=null",
        "message": "SUCCESS",
        "timestamp": "1597389672288",
        "qys_code": 0
        },
        "runtime": "0.332"
        }
     */
	public function serverSignatures()
	{
		$result = app($this->signatureConfigService)->serverSignatures($this->request->all(), $this->own);
		return $this->returnResult($result);
	}

    /** 保存签章信息记录
     * @return array
     */
    public function saveSignatureLog()
    {
        $result = app($this->signatureConfigService)->saveQiyuesuoSignatureLog($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /** 更新签署信息记录
     * @param $logId
     * @return array
     */
    public function updateSignatureLog($logId)
    {
        $result = app($this->signatureConfigService)->updateQiyuesuoSignatureLog($this->request->all(), $this->own, $logId);
        return $this->returnResult($result);
    }

    /** 获取签署信息记录
     * @param $logId
     * @return array
     */
    public function getSignatureLog($logId)
    {
        $result = app($this->signatureConfigService)->getSignatureLog($logId);
        return $this->returnResult($result);
    }

    /** 获取当前签章插件配置
     * @return array
     * {
    "status": 1,
    "data": {
        "config_id": 2,
        "type": 2, // 1 金格签章  2 契约锁签章
        "name": "契约锁签章",
        "is_use": 1,
        "keysn": {   // 金格签章所需 keysn   type 2 时为null
            "auto_id": 1,
            "user_id": "admin",
            "keysn": "11111",
            "created_at": "2020-08-14 15:26:07",
            "updated_at": "2020-08-14 15:26:07"
        },
        "qiyuesuo": {    type 1 时为null
            "setting_id": 1,
            "signature_type": 2, // 1.Ukey签章  2 服务签章
            "server_id": 7,
            "data_protect": 1,    //  1 数据保护启用 0 不启用
            "created_at": null,
            "updated_at": "2020-08-11 17:35:34"
        }
        },
    "runtime": "0.191"
    }
     */
    public function getCurrentService()
    {
        $result = app($this->signatureConfigService)->getCurrentService($this->own, $this->request->all());
        return $this->returnResult($result);
	}
	/**
	 * 删除契约锁签章的签署记录
	 *
	 * @param [type] $logId
	 *
	 * @return void
	 * @author yml
	 */
	public function deleteSignatureLog($logId)
	{
		$result = app($this->signatureConfigService)->deleteQiyuesuoSignatureLog($logId);
        return $this->returnResult($result);
	}
	/**
	 * 获取契约锁签章签署的记录列表
	 *
	 * @return void
	 * @author yml
	 */
	public function getSignatureLogList()
	{
		$token = $this->request->bearerToken();
		$result = app($this->signatureConfigService)->getSignatureLogList($this->request->all(), $this->own, $token);
        return $this->returnResult($result);
    }
	/**
	 * 获取流程数据保护的配置列表
	 *
	 * @return void
	 * @author yml
	 */
    public function getFlowConfig()
    {
        $result = app($this->signatureConfigService)->getFlowConfig($this->request->all());
        return $this->returnResult($result);
	}
	/**
	 * 获取单个流程的数据保护配置
	 *
	 * @param [type] $configId
	 *
	 * @return void
	 * @author yml
	 */
	public function getOneFlowConfig($configId)
	{
		$result = app($this->signatureConfigService)->getOneFlowConfig($configId);
        return $this->returnResult($result);
	}
	/**
	 * 保存流程的数据保护配置
	 *
	 * @return void
	 * @author yml
	 */
	public function saveFlowConfig()
	{
		$result = app($this->signatureConfigService)->saveFlowConfig($this->request->all());
        return $this->returnResult($result);
	}
	/**
	 * 修改流程的数据保护配置
	 *
	 * @param [type] $configId
	 *
	 * @return void
	 * @author yml
	 */
	public function updateFlowConfig($configId)
	{
		$result = app($this->signatureConfigService)->updateFlowConfig($this->request->all(), $configId);
        return $this->returnResult($result);
	}
	/**
	 * 获取签署证书信息
	 *
	 * @return void
	 * @author yml
	 */
	public function getCertDetail()
	{
		$result = app($this->signatureConfigService)->getCertDetail($this->request->all());
        return $this->returnResult($result);
	}
	/**
	 * 获取表单控件列表
	 *
	 * @return void
	 * @author yml
	 */
	public function getFlowControlFilterInfo()
    {
        $result = app($this->signatureConfigService)->getFlowControlFilterInfo($this->request->all());
        return $this->returnResult($result);
	}
	
	public function countsignCheckVerify()
	{
		$token = $this->request->bearerToken();
		$result = app($this->signatureConfigService)->countsignCheckVerify($this->request->all(), $token);
        return $this->returnResult($result);
	}
}
