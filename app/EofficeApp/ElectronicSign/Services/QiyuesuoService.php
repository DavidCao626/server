<?php

namespace App\EofficeApp\ElectronicSign\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Cache;

/**
 * 契约锁内部接口 service
 */
class QiyuesuoService extends BaseService
{
	public $qiyuesuoApi = null;

	protected $qiyuesuoSealApplyAuthLogRepository;
	protected $qiyuesuoSealApplyCreateDocLogRepository;
	protected $qiyuesuoSealApplyLogRepository;
	protected $qiyuesuoSealApplySettingRepository;
	public function __construct()
	{
		parent::__construct();
		$this->qiyuesuoServerRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoServerRepository';
		$this->qiyuesuoSettingRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSettingRepository';
		$this->electronicSignService = 'App\EofficeApp\ElectronicSign\Services\ElectronicSignService';
		$this->flowRunService = 'App\EofficeApp\Flow\Services\FlowRunService';
		$this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
		$this->flowRunRelationQysContractRepository = 'App\EofficeApp\ElectronicSign\Repositories\FlowRunRelationQysContractRepository';
		$this->flowRunRelationQysContractInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\FlowRunRelationQysContractInfoRepository';
		$this->flowRunRelationQysContractSignInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\FlowRunRelationQysContractSignInfoRepository';
		$this->userService = 'App\EofficeApp\User\Services\UserService';
		$this->companyService = 'App\EofficeApp\System\Company\Services\CompanyService';
		$this->qiyuesuoConfigRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoConfigRepository';
		$this->flowService = 'App\EofficeApp\Flow\Services\FlowService';
		$this->qiyuesuoSealApplyAuthLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplyAuthLogRepository';
		$this->qiyuesuoSealApplyCreateDocLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplyCreateDocLogRepository';
		$this->qiyuesuoSealApplyLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplyLogRepository';
		$this->qiyuesuoSealApplySettingRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplySettingRepository';
		// 契约锁同步相关资源日志记录的Repository
		$this->qiyuesuoSyncRelatedResourceLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSyncRelatedResourceLogRepository';
		// 契约锁物理印章的Repository
		$this->qiyuesuoSealRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealRepository';
		// 流程信息，flow_表
		$this->flowFormService = 'App\EofficeApp\Flow\Services\FlowFormService';
		//  契约锁同步相关资源任务
		$this->qiyuesuoRelatedResourceTaskRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoRelatedResourceTaskRepository';
		// 契约锁物理用印集成流程触发节点设置
		$this->qiyuesuoSealApplySettingOutsendInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplySettingOutsendInfoRepository';
		// 契约锁业务分类的Repository
		$this->qiyuesuoCategoryRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoCategoryRepository';
		// 契约锁模板的Repository
		$this->qiyuesuoTemplateRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoTemplateRepository';
		// 物理用印用印文件关联附件
		$this->attachmentRelataionQiyuesuoSealApplyImageRepository = 'App\EofficeApp\ElectronicSign\Repositories\AttachmentRelataionQiyuesuoSealApplyImageRepository';
		// 契约锁模板参数的Repository
		$this->qiyuesuoTemplateParamRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoTemplateParamRepository';
		// 契约锁电子合同相关日志
		$this->qiyuesuoContractLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoContractLogRepository';
		$this->flowRunRepository = 'App\EofficeApp\Flow\Repositories\FlowRunRepository';
	}

	/**
	 * 需要用到的接口地址
	 *
	 * @var array
	 */
	private $url = [
		'createbyfile' => '/document/createbyfile', //通过上传的文件创建合同文档
		'createbyimages' => '/document/createbyimages', //用多张图片创建合同文档
		'createbytemplate' => '/document/createbytemplate', //用文件模板创建合同文档
		'createbyhtml' => '/document/createbyhtml', //根据HTML创建文档
		'addattachment' => '/document/addattachment', //添加附件
		'addwatermark' => '/document/addwatermark', //添加水印图片
		'create' => '/contract/create', // 创建合同
		'createbycategory' => '/contract/createbycategory', //根据业务分类创建合同
		'send_contract' => '/contract/send',
		'presign' => '/contract/presignurl', //获取预签署页面（指定签署位置）
		'sign' => '/contract/signurl', //为运营方提供的签署页面 30分钟
		'signurl_send' => '/contract/signurl/send', //接收人通过手机访问签署链接完成签署 永久有效
		'createRetainParams' => '/contract/createRetainParams', //编辑合同(保留模版参数)
		'finishforce' => '/contract/finish/force', //强制结束合同
		'finishaddattachment' => '/contract/finish/addattachment', //合同强制结束后添加附件
		'signbyplatform' => '/contract/signbyplatform', //运营方签署
		'signbycompany' => '/contract/signbycompany', // 公司公章签署
		'signbylegalperson' => '/contract/signbylegalperson', //公司法人签署
		'signbyperson' => '/contract/signbyperson', //个人签署
		'categories' => '/contract/categories', //查询业务分类
		'contract_detail' => '/contract/detail', //合同详情
		'view' => '/contract/viewurl', // 获取合同浏览页面
		'download' => '/contract/download', // 合同下载
		'print' => '/contract/efprinturl', // 获取防伪打印页面地址
		'templates' => '/template/list', // 获取模板列表
		'contracts' => '/contract/list', // 获取合同列表
		'create_seal_apply' => '/seal/apply/multiple', //物理用印接口授权认证
		'create_seal_apply_by_category' => '/seal/apply/mulitipleByCategory', // 按业务分类物理用印接口授权认证
		'seal_apply_images_download' => '/seal/apply/images/download', // 用于获取用印图片，以压缩包(.zip)格式返回
		'delete_contract' => '/contract/delete', // 删除合同，只能删除“草稿”状态下的合同
		'recall_contract' => '/contract/recall', // 撤回“签署中”或“指定中”的合同
		'cancel_contract' => '/contract/cancel', // 对已完成的合同发起作废，生成作废文件，并代发起方签署作废文件（在所有合同签署方签署作废文件后，合同作废完成）
		'my_signatures' => '/binary/signurl', // 我的签章图片地址,
		'company_list' => '/company/list',  // 获取企业列表
		'signature_detail' => '/binary/detail', // 获取签章信息详情
		'companies' => '/company/list', // 平台方及子公司列表
        'inner_seals' => '/seal/innercompany/seallist', // 内部企业及平台方印章
	];

    /**
     * 根据定义流程ID初始化 【获取对应契约锁服务】
     *
     * @param [type] $flowId
     * @return object
     */
    public function init($flowId, $type = '')
    {
        if ($flowId) {
            $this->flowId = $flowId;
            if ($type && $type == 'seal_apply'){
                $server = app($this->qiyuesuoServerRepository)->getSealApplyServerByFlowId($flowId);
            } else {
                $server = app($this->qiyuesuoServerRepository)->getServerByFlowId($flowId);
            }
        } else {
            // 未传定义流程id去获取最近添加的一条
            $server = app($this->qiyuesuoServerRepository)->getLastServer();
        }
        if ($server) {
            $this->qiyuesuoApi = new QiyuesuoOpenApiService($server->serverUrl, $server->accessKey, $server->accessSecret, 'private', $server->fileMaxSize);
        } else {
            // return ['code' => 1001, 'message' => '获取契约锁服务设置失败！'];
        }
        return $this;
    }

    public function initByServer($server)
    {
        if (is_object($server)){
            $this->qiyuesuoApi = new QiyuesuoOpenApiService($server->serverUrl, $server->accessKey, $server->accessSecret, $server->serverType, $server->fileMaxSize ?? 10);
        } else {
            $this->qiyuesuoApi = new QiyuesuoOpenApiService($server['serverUrl'], $server['accessKey'], $server['accessSecret'], $server['serverType'], $server['fileMaxSize'] ?? 10);
        }
        return $this;
    }

	/**
	 * 创建合同文档
	 *
	 * @param [type] $data
	 * ['type' => '文档类型 文件file、图片images、网页html、模板template','title'=>'文档标题','content'=>'具体内容 图片时为数组其他字符串','params' => '模板参数 模板需传json']
	 * @return array
	 */
	public function createDocument($data)
	{
		if (!$this->qiyuesuoApi) {
			return ['code' => ['0x093515', 'electronicsign']];
		}
		// if (!$this->checkDocment($data)) {
		//     return ['code' => ['0x093519', 'electronicsign']];
		// }
		$url = '';
		$params = [
			'title' => $data['title'], //合同标题
		];
		switch ($data['type']) {
			case 'file':
				$url = $this->url['createbyfile'];
				$file = curl_file_create(realpath($data['content']), $data['content']->extension(), $data['content']->getClientOriginalName());
				$params['file'] = $file;
				break;
			case 'images':
				$url = $this->url['createbyimages'];
				$params['images'] = isset($data['content']) ? $data['content'] : [];
				break;
			case 'html':
				$url = $this->url['createbyhtml'];
				$params['html'] = isset($data['content']) ? $data['content'] : '';
				break;
			case 'template':
				$url = $this->url['createbytemplate'];
				$params['templateId'] = isset($data['content']) ? $data['content'] : '';
				$params['params'] = isset($data['params']) ? $data['params'] : [];
				break;
			default:
				return ['qys_code' => 1001, 'message' => '请选择生成文档的方式！'];
		}
		return $this->qiyuesuoApi->createDocument($url, $params);
	}

	/**
	 * 上传文件生成文档
	 *
	 * @param [type] $data ['file'=>'上传文件','title'=>'合同文档名']
	 * @return array
	 */
	public function createDocumentByFile($data)
	{
		if (!$this->qiyuesuoApi) {
			return ['code' => ['0x093515', 'electronicsign']];
		}
		$url = $this->url['createbyfile'];
		//验证数据  上传文件格式  文件大小filesize()
        	if (!($ext = strrchr($data['file'], '.')) || !in_array(strtolower(substr($ext, 1)), ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif', 'tif', 'txt', 'xls', 'xlsx'])) {
			return ['code' => ['0x093535', 'electronicsign']];
		}
		if (!file_exists($data['file'])) {
			return ['code' => ['0x093540', 'electronicsign']];
		}
		// 商务反馈说会有文件超过10兆，现将限制移除
		// 20200508 跟契约锁沟通他们也是有文件大小限制，测试过程中由于文件超过20兆导致契约锁服务直接宕机了，所以判断还是需要的
	        $maxSize = $this->qiyuesuoApi->fileMaxSize ?? 10;
	        if (filesize($data['file']) > $maxSize * 1024 * 1024) {
	            return ['code' => ['0x093536', 'electronicsign'], 'dynamic' => trans('electronicsign.suggest_tar'). $maxSize . trans('electronicsign.mb')];
		}
		$file = curl_file_create(realpath($data['file']));
		$params = [
			'title' => $data['title'], //合同标题
			'file' => $file,
		];
		return $this->qiyuesuoApi->createDocumentByFile($url, $params);
	}

	/** 生成文档-》生成合同
	 * @param $data {
	 *     controlId: "DATA_..."
	 *     flowInfo: {}
	 *     formData: {}
	 * }
	 * @return array
	 */
	public function createContractV1($data, $userInfo)
	{
		$userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
		$flowInfo = isset($data['flowInfo']) ? $data['flowInfo'] : [];
		$runId = isset($flowInfo['runId']) ? $flowInfo['runId'] : '';
		$flowId = isset($flowInfo['flowId']) ? $flowInfo['flowId'] : '';
		$formId = $flowInfo['formId'] ?? 0;
		$formControlData = app($this->flowFormService)->getParseForm($formId);
		$formControlData = array_column($formControlData, NULL, 'control_id');
		// $send = $data['send'] ?? false;
		if (empty($flowId)) {
			$flowId = app($this->flowRunService)->getFlowIdByRunId($runId);
			$flowInfo['flowId'] = $flowId;
		}
		$log = [
			'flowId' => $flowId,
			'runId' => $runId,
			'userid' => $userId,
			'ipaddress' => getClientIp(),
			'createTime' => date('Y-m-d H:i:s'),
			'requestJson' => json_encode($data),
			'action' => 'create_contract',
		];
		// 根据flowId取设置
		$setting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId)->toArray();
		// 发起申请日志
		$log['settingId'] = $setting['settingId'];
		$log['serverId'] = $setting['serverId'];
		//根据流程id实例化契约锁开放Api service
		$qiyuesuoService = $this->init($flowId);
		if (!$this->qiyuesuoApi) {
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093515')]);
			$this->addLog($this->qiyuesuoContractLogRepository, $log);
			//提示：契约锁开放apiservice实例不存在
			return ['code' => ['0x093515', 'electronicsign']];
		}
		// if (app($this->electronicSignService)->getContractIdbyFlowRunId($runId)) {
		//     //提示：根据运行流程id获取到合同id
		//     $log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093518')]);
		//     $this->addLog($this->qiyuesuoContractLogRepository, $log);
		//     return ['code' => ['0x093518', 'electronicsign']];
		// }
		// 传过来的表单数据
		$formData = isset($data['formData']) ? $data['formData'] : [];
		// 签署按钮所在附件控件的id
		$controlId = isset($data['controlId']) ? $data['controlId'] : '';

		$send = $setting['saveLaunch'] ? true : false;
		//创建文档
		$documentIds = [];
		$documentNames = [];
		//解析合同模板数据 用模板创建合同文档  难点在于模板参数的处理 暂不考虑有模板参数的情况  暂时注释
		$templates = $setting['templateField'] ?? '';
		// 下拉框单选时传递的是字符串，多选时为数组
		$templateIds = isset($formData[$templates]) && !empty($formData[$templates]) ? (is_array($formData[$templates]) ? $formData[$templates] : [$formData[$templates]]) : [];
		if ($templateIds) {
			// 模板参数处理
			$templateParams = $this->parseTemplateParam($formData, $formControlData);
			// 模板名为以,号隔开的字符串
			$templateNames = isset($formData[$templates . '_TEXT']) ? (explode(',', $formData[$templates . '_TEXT'])) : [];
			if ($templateIds) {
				foreach ($templateIds as $key => $val) {
					// 根据模板id获取模板参数 验证必填项
					$tempalteParamCheck = $this->checkTemplateParamRequired($val, $templateParams, $setting['serverId']);
					if (isset($tempalteParamCheck['code'])) {
						$codeKey = $tempalteParamCheck['code'][0] ?? '';
						$codeVal = $tempalteParamCheck['code'][1] ?? '';
						$message = $codeKey && $codeVal ? trans('electronicsign.' . $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_create_document_by_template_failed'));
						$log['responseJson'] = json_encode(['message' => $message]);
						$this->addLog($this->qiyuesuoContractLogRepository, $log);
						return $tempalteParamCheck;
					}
					$documentId = $qiyuesuoService->createDocument([
						'content' => $val, // 模板id
						'title' => $templateNames[$key] ?? '',
						'params' => json_encode($templateParams), // 模板参数 ---将表单数据转化为k:v格式传过去
						'type' => 'template',
					]);
					if (isset($documentId['code'])) {
						//生成文档失败及原因
						$codeKey = $documentId['code'][0] ?? '';
						$codeVal = $documentId['code'][1] ?? '';
						$message = $codeKey && $codeVal ? trans('electronicsign.' . $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_create_document_by_template_failed'));
						$log['responseJson'] = json_encode(['message' => $message]);
						$this->addLog($this->qiyuesuoContractLogRepository, $log);
						return $documentId; //['code' => ['0x093519', 'electronicsign']];
					}
					// $documentNames[] = $templateNames[$key] ?? '';
					$documentIds[] = [
						'runId' => $runId,
						'documentId' => $documentId['documentId'],
						'attachmentId' => '',
						'templateId' => $val,
					];
				}
			}
		}
		//解析附件地址
		$documentField = $setting['documentField'] ?? '';
		$attachmentIds = isset($formData[$documentField]) ? $formData[$documentField] : [];
		if (!$attachmentIds && !$templates) {
			// 没有上传附件也未选择模板
			// if (!$attachmentIds) {
			//提示：附件地址为空，请确认合同文件已上传
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093534')]);
			$this->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093534', 'electronicsign']];
		}
		//获取附件对应地址
		if ($attachmentIds) {
			$attachmentUrls = app($this->attachmentService)->getMoreAttachmentById($attachmentIds);
			foreach ($attachmentUrls as $attachmentUrl) {
				$documentId = $qiyuesuoService->createDocumentByFile([
					'title' => $attachmentUrl['attachment_name'],
					'file' => $attachmentUrl['temp_src_file'],
				]);
				if (isset($documentId['code'])) {
					//根据附件生成文档失败及原因
					$codeKey = $documentId['code'][0] ?? '';
					$codeVal = $documentId['code'][1] ?? '';
					$message = $codeKey && $codeVal ? trans('electronicsign.' . $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_create_document_by_attachment_failed'));
					$log['responseJson'] = json_encode(['message' => $message]);
					$this->addLog($this->qiyuesuoContractLogRepository, $log);
					return $documentId; //['code' => ['0x093519', 'electronicsign']];
				}
				$documentNames[] = $attachmentUrl['attachment_name'];
				$documentIds[] = [
					'runId' => $runId,
					'documentId' => $documentId['documentId'],
					'attachmentId' => $attachmentUrl['attachment_id'],
					'templateId' => '',
				];
			}
		}
		if (empty($documentIds)) {
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.documents_not_exists')]);
			$this->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['documents_not_exists', 'electronicsign']];
		}
		// 解析表单&设置，准备创建合同的数据  -- 签署信息关键字
		$contractparams = $this->parseContractParams($flowInfo, $formData, $userId, $setting, $documentIds);
		if (isset($contractparams['code'])) {
			$codeKey = $contractparams['code'][0] ?? '';
			$codeVal = $contractparams['code'][1] ?? '';
			$message = $codeKey && $codeVal ? trans('electronicsign.' . $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_get_params_failed'));
			$log['responseJson'] = json_encode(['message' => $message]);
			$this->addLog($this->qiyuesuoContractLogRepository, $log);
			return $contractparams;
		}
		// 拼接[文档]信息
		$contractparams['documents'] = array_column($documentIds, 'documentId');
		if (!$this->checkContract($contractparams)) {
			//提示：合同数据验证失败
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093517')]);
			$this->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093517', 'electronicsign']];
		}
		// 生成文档是否带业务分类
//		$contractCategoryFlag = (isset($contractparams['categoryId']) && $contractparams['categoryId']) ? true : false;
        $createUrl = $this->url['createbycategory'];
//        if ($contractCategoryFlag === true) {
//		} else if ($contractCategoryFlag === false) {
//			$createUrl = $this->url['create'];
//		}
		// 是否立即发起合同
		$contractparams['send'] = $send;
		// 合同创建人在OA的ID 考虑到获取人员卡片时使用 发起合同时不能有此参数
		$creatorId = $contractparams['creatorId'] ?? '';
		unset($contractparams['creatorId']);
		// 调openapi生成文档
		$contractId = $this->qiyuesuoApi->createContract($createUrl, $contractparams);
		if (!isset($contractId['code'])) {
			//插入文档id,合同id到对应流程合同关联表
			foreach ($documentIds as $key => $documentId) {
				$documentIds[$key]['contractId'] = $contractId['contractId'];
				$documentIds[$key]['created_at'] = $documentIds[$key]['updated_at'] = date('Y-m-d H:i:s');
			}
			app($this->flowRunRelationQysContractRepository)->insertMultipleData($documentIds);
			//填充合同相关信息用于流程契约锁签署信息展示
			$log['contractId'] = $contractparams['contractId'] = $contractId['contractId'];
			$contractparams['document_names'] = $documentNames;
			// 合同状态  直接发起为签署中  不发起为草稿
			$contractparams['contractStatus'] = $send ? 'SIGNING' : 'DRAFT';
			// 合同创建人在OA的ID,考虑到获取人员卡片时使用
			$contractparams['creatorId'] = $creatorId;
			if (isset($templateNames)) {
				$contractparams['templateField'] = $templateNames;
			}
			app($this->electronicSignService)->setFlowRelationQysSignInfo(compact('contractparams', 'documentIds', 'runId'));
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.contract_launch_success')]);
		} else {
			$codeKey = $contractId['code'][0] ?? '';
			$codeVal = $contractId['code'][1] ?? '';
			$message = $codeKey && $codeVal ? trans('electronicsign.' . $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_launch_failed'));
			$log['responseJson'] = json_encode(['message' => $message]);
		}
		$this->addLog($this->qiyuesuoContractLogRepository, $log);
		//含有code 提示：合同生成失败及错误信息 不含 返回合同id
		return $contractId;
	}

	/**
	 * 解析表单&设置，准备创建合同的数据
	 * [文档]信息已经在外面解析好，这里面不做处理
	 * @param  [type] $flowInfo [description]
	 * @param  [type] $formData [description]
	 * @param  [type] $userId   [description]
	 * @return [type]           [description]
	 */
	public function parseContractParams($flowInfo, $formData, $userId, $setting = [], $documentIds = [])
	{
		$runId = isset($flowInfo['runId']) ? $flowInfo['runId'] : '';
		$flowId = isset($flowInfo['flowId']) ? $flowInfo['flowId'] : '';
		$runInfo = app($this->flowRunService)->getFlowRunDetail($runId);
		$runSeq = isset($runInfo['run_seq_strip_tags']) ? $runInfo['run_seq_strip_tags'] : '';
		$formId = $flowInfo['formId'] ?? 0;
		$formControlData = app($this->flowFormService)->getParseForm($formId);
		$formControlData = array_column($formControlData, NULL, 'control_id');
		// 根据flowId取设置
		if (!$setting) {
			$setting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId)->toArray();
		}
		// 合同名称
		$subjectField = isset($setting['subjectField']) ? $setting['subjectField'] : '';
		$subject = isset($formData[$subjectField]) ? $formData[$subjectField] : '';
		if (!$subject) {
			// 获取合同名称失败，请检查合同名称字段配置
			return ['code' => ['0x093522', 'electronicsign']];
		}
		// 解析当前用户用户名&手机号，作为创建人姓名手机号   增加设置 合同创建人----流程办理人【默认】   流程发起人
		// 调整 增加了设置合同创建者名称、联系方式 ，支持文本或者人员选择器 --- 易用性改进 非必填，字段未设置时不传创建者参数
		$creatorField = $setting['creatorField'] ?? '';
		// $phoneNumber = '';
		// $userName = '';
		// if ($creatorField && $creatorField == 'flow_creator' && isset($runInfo['creator'])) {
		//     $userInfo = app($this->userService)->getUserAllData($runInfo['creator']);
		// }
		// 创建者id
		$userName = $this->parseFlowDataOutSend($formData, $formControlData, 'creatorNameField', $setting, -1);
		$phoneNumber = $this->parseFlowDataOutSend($formData, $formControlData, 'creatorContactField', $setting, -1);
		$creatorId = $userId;
		// 未设置创建人手机号 获取当前用户的
		if (!$phoneNumber && $creatorField) {
			if ($creatorField == 'flow_creator' && isset($runInfo['creator'])) {
				$userInfo = app($this->userService)->getUserAllData($runInfo['creator']);
			} else {
				$userInfo = app($this->userService)->getUserAllData($userId);
			}
			if (!empty($userInfo)) {
				$userInfo = $userInfo->toArray();
				if (!$userName) {
					$userName = isset($userInfo['user_name']) ? $userInfo['user_name'] : '';
				}
				$userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
				$phoneNumber = isset($userOneInfo['phone_number']) ? $userOneInfo['phone_number'] : '';
			}
		}
		// if (!isset($userInfo) || !$userInfo) {
		//     $userInfo = app($this->userService)->getUserAllData($userId);
		// }
		// if (!empty($userInfo)) {
		//     $userInfo = $userInfo->toArray();
		//     $userName = isset($userInfo['user_name']) ? $userInfo['user_name'] : '';
		//     $userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
		//     $phoneNumber = isset($userOneInfo['phone_number']) ? $userOneInfo['phone_number'] : '';
		//     $creatorId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
		// }
		// if (!$phoneNumber) {
		//     // 获取用户手机号码失败，无法创建合同
		//     return ['code' => ['0x093523', 'electronicsign']];
		// }
		// if (!$userName) {
		//     // 获取用户名失败，无法创建合同
		//     return ['code' => ['0x093524', 'electronicsign']];
		// }
		//获取公司名  设为发起方名称  将发起方加入签署方数组中
		//合同发起人字段
		$initiateurField = $setting['initiateurField'] ?? '';
		$initiateur = '';
		if ($initiateurField) {
			// 下拉框时取值
			// $initiateur = $formData[$initiateurField. 'TEXT'] ?? ($formData[$initiateurField] ?? '');
			// 单行文本框取值
			$initiateur = $formData[$initiateurField] ?? '';
		}
		if (!$initiateur) {
			$company = app($this->companyService)->getCompanyDetail();
			if (!$company) {
				// 获取企业名称失败，无法创建合同
				return ['code' => ['0x093530', 'electronicsign']];
			}
		} else {
			$company['company_name'] = $initiateur;
		}
		//签署有效期
		$signValidityField = $setting['signValidityField'] ?? '';
		$signValidity = $formData[$signValidityField] ?? '';
		if (date('Y-m-d H:i:s', strtotime($signValidity)) == $signValidity) {
		} elseif (date('Y-m-d H:i', strtotime($signValidity)) == $signValidity) {
			$signValidity = date('Y-m-d H:i:s', strtotime($signValidity));
		} elseif (date('Y-m-d', strtotime($signValidity)) == $signValidity) {
			$signValidity = date('Y-m-d H:i:s', strtotime($signValidity) + 86399);
		} else {
			//有效期格式不是有效的时间格式  默认契约锁设置一个月
			$signValidity = '';
		}
		// 如果有效期小于当前时间 返回签署有效期不能小于当前时间
		if ($signValidity) {
			if (strtotime($signValidity) <= time()) {
				return ['code' => ['sign_validity_not_valid', 'electronicsign']];
			}
		}
		// 合同签署顺序  true 顺序签署  默认false 无序签署
		$signOrdinalField = $setting['signOrdinalField'] ?? '';
		$signOrdinal = $signOrdinalField && $signOrdinalField == 'ordinal' ? true : false;
		//签署方信息
		$signInfo = $this->parseContractSignInfo($formData, $formControlData, $setting['settinghas_many_sign_info'], true, $company['company_name'], $signOrdinal, $documentIds);
		//签署方信息不能为空
		if (!$signInfo) {
			return ['code' => ['sign_info_not_empty', 'electronicsign']];
		}
		//创建合同参数数组
		$params = [
			'subject' => $subject, //合同名称 必填
			'description' => '', // 合同描述
			'ordinal' => $signOrdinal, //是否顺序签署  默认false
			// 合同编号（由对接方指定）；eoffice内，指定为[流程流水号(带html标签)]
			'sn' => $runSeq,
			'categoryId' => '', // 业务分类ID
			// 合同文档ID的集合 数组  直接创建合同必填，[已经在外部处理]
			// 'documents' => '',
			'expireTime' => $signValidity, // 合同过期时间；格式：yyyy-MM-dd HH:mm:ss ；不传，一个月过期
			'creatorName' => $userName, // 合同创建人姓名
			'creatorContact' => $phoneNumber, // 合同创建人手机号码
			'creatorId' => $creatorId, // 合同创建者id 用于展示获取
			'tenantName' => isset($company['company_name']) ? trim($company['company_name']) : '', // 发起方名称
			'signatories' => $signInfo, //isset($signInfo) ?: [], //$signInfo, //签署方  需从setting_sign_info中获取
			'businessData' => json_encode(['run_id' => $runId]), //用户的业务数据及文档调整，json格式   增加 流程id run_id
		];
		// 业务分类ID
		$categoryField = isset($setting['categoryField']) ? $setting['categoryField'] : '';
		if (!empty($categoryField)) {
			$params['categoryId'] = isset($formData[$categoryField]) ? $formData[$categoryField] : '';
			$params['categoryName'] = isset($formData[$categoryField . '_TEXT']) ? $formData[$categoryField . '_TEXT'] : '';
		}
		return $params;
	}

	/**
	 * 解析签署信息
	 *
	 * @param [type] $runId 运行流程id
	 * @param [type] $sign_info  签署设置信息
	 * @param [type] $contractNeed  用于生成合同 此时需要对平台企业数据处理     *
	 * @param [type] $company_name  作为发起方的单位名称
	 */
	public function parseContractSignInfo($formData, $formControlData, $signInfo, $contractNeed = true, $company_name = '', $ordinal = false, $documentIds = [])
	{
		//获取集成设置-签署信息设置  --- 需要支持明细数据
		$return = [];
		if ($signInfo && $formData) {
			foreach ($signInfo as $key => $signInfoItem) {
				if ($signInfoItem) {
					$tenantType = $signInfoItem['tenantType'];
					$oneSignInfos = $this->parseOneSignInfo($signInfoItem, $formData, $formControlData, $tenantType);
					// 平台方 -- 单一签署方数组
					// if ($tenantType == 'CORPORATE'){
					//     $return[] = $oneSignInfos;
					// } else {
					//     // 企业方和个人 可能存在多签署方
					//     foreach ($oneSignInfos as $oneSignInfo) {
					//         $return[] = $oneSignInfo;
					//     }
					// }
					foreach ($oneSignInfos as $oneSignInfo) {
						if (isset($oneSignInfo['tenantName']) && !empty($oneSignInfo['tenantName'])) {
							$return[] = $oneSignInfo;
						}
					}
				}
			}
		}
		if (!$contractNeed) {
			return $return;
		}
		//处理数据 平台企业签署信息数据整理
		//发起方名称
		if (!$company_name) {
			$company = app($this->companyService)->getCompanyDetail();
			$company_name = $company['company_name'] ?? '';
		}
		$name = [
			'CORPORATE' => '企业签章',
			'PERSONAL' => '个人签字',
			'LP' => '法定代表人签字',
		];
		// 定义签署平台方数组和企业数组
		$returns = $corporate = $company = $actions = [];
		$seriaNo = 1;
		foreach ($return as $key => $val) {
			// 之前未考虑到多企业的情况
			if (in_array($val['tenantType'], ['CORPORATE', 'COMPANY'])) {
				$lowerCorporate = strtolower($val['tenantType']);
				$tenantName = $val['tenantType'] == 'CORPORATE' ? $company_name : $val['tenantName'];
				$$lowerCorporate[$tenantName] = [
					'tenantType' => $val['tenantType'],
					'tenantName' => $tenantName,
				];
				// 接收人 contact 支持多个为数组
				if ($val['contact'] && isset($val['contact'][0]) && $val['tenantType'] == 'COMPANY' && !isset($$lowerCorporate[$tenantName]['receiver'])) {
					$$lowerCorporate[$tenantName]['contact'] = $val['contact'][0];
				}
				// 签署顺序
				if ($ordinal) {
					if (!isset($$lowerCorporate[$tenantName]['serialNo'])) {
						$$lowerCorporate[$tenantName]['serialNo'] = $seriaNo;
						$seriaNo++;
					}
				} else {
					$$lowerCorporate[$tenantName]['serialNo'] = 1;
				}
				// 签署动作  contact 支持多个为数组
				$actionOperators = [];
				if ($val['contact']) {
					foreach ($val['contact'] as $contact) {
						$actionOperators[] = ['operatorContact' => $contact];
					}
				}
				$locations = [];
				// 默认第一个关键字
				if ($val['keyword']) {
					foreach ($documentIds as $documentId) {
						$locations[] = [
							'documentId' => $documentId['documentId'],
							'rectType' => $val['type'] == 'PERSONAL' ? 'SEAL_PERSONAL' : 'SEAL_CORPORATE',
							'keyword' => $val['keyword'],
						];
						$locations[] = [
							'documentId' => $documentId['documentId'],
							'rectType' => 'TIMESTAMP',
							'keyword' => $val['keyword'],
						];
					}
				}
				$action = [
                    'type' => isset($val['type']) ? $val['type'] : '', //'CORPORATE',//'PERSONAL',//PERSONAL时actionOperators必填
                    'name' => isset($val['type']) && isset($name[$val['type']]) ? $name[$val['type']] : '企业签章', //'个人签章'
                    'serialNo' => isset($$lowerCorporate[$tenantName]['actions']) ? count($$lowerCorporate[$tenantName]['actions']) + 1 : 1,
                    'actionOperators' => $actionOperators,
                    'locations' => $locations,
                    'keyword' => $val['keyword'] ?? '',
                    'sealId' => $val['seal'] ?? '',
                    'canLpSign' => $val['canLpSign'],
//                    'sealIds' => $val['seals'] ? json_encode($val['seals']) : '',
                ];
				if ($val['seals']) {
                    $action['sealIds'] = json_encode($val['seals']);
                }
				$actions[$tenantName][] = $action;
				$$lowerCorporate[$tenantName]['actions'] = $actions[$tenantName];
				// 设置法人签字了处理
				if ($val['tenantType'] == 'CORPORATE' && strtolower($val['canLpSign']) == trans('electronicsign.yes')) {
					$$lowerCorporate[$tenantName]['actions'][] = [
						'type' => 'LP',
						'name' => $name['LP'],
						'serialNo' => isset($$lowerCorporate[$tenantName]['actions']) ? count($$lowerCorporate[$tenantName]['actions']) + 1 : 1,
						'locations' => $locations,
						'keyword' => $val['keyword'] ?? '',
						'canLpSign' => 1,
					];
				}
			} else {
				// 设置顺序签署后添加签署顺序
				$personal = $val;
				if ($ordinal) {
					$personal['serialNo'] = $seriaNo;
					$seriaNo++;
				} else {
					$personal['serialNo'] = 1;
				}
				// 个人根据关键字指定签署位置
				if (isset($personal['keyword']) && !empty($personal['keyword'])) {
					$locations = [];
					foreach ($documentIds as $documentId) {
						$locations[] = [
							'documentId' => $documentId['documentId'],
							'rectType' => 'SEAL_PERSONAL',
							'keyword' => $personal['keyword'],
						];
						$locations[] = [
							'documentId' => $documentId['documentId'],
							'rectType' => 'TIMESTAMP',
							'keyword' => $personal['keyword'],
						];
					}
					$personal['actions'][] = [
						'type' => 'PERSONAL',
						'name' => '个人签字',
						'serialNo' => 1,
						'actionOperators' => [
							['operatorContact' => $personal['contact']],
						],
						'locations' => $locations,
					];
				}
				$returns[] = $personal;
			}
		}
		if (isset($company)) {
			foreach ($company as $companyVal) {
				array_unshift($returns, $companyVal);
			}
		}
		if (isset($corporate)) {
			foreach ($corporate as $corporateVal) {
				array_unshift($returns, $corporateVal);
			}
		}
		return $returns;
	}

	/**
	 * 合同草稿发起合同
	 *
	 * @return array
	 */
	public function sendContract($flowId, $contractId)
	{
		if ($flowId) {
			$this->init($flowId);
		}
		if (!$this->qiyuesuoApi) {
			return ['code' => ['0x093515', 'electronicsign']];
		}
		return $this->qiyuesuoApi->sendRequest($this->url['send_contract'], ['contractId' => $contractId]);
	}

	/**
	 * 获取合同详情
	 *
	 * @return array
	 */
	public function getContract($flowId, $data)
	{
		if ($flowId) {
			$this->init($flowId);
		}
		if (!$this->qiyuesuoApi) {
			return ['code' => ['0x093515', 'electronicsign']];
		}
		return $this->qiyuesuoApi->sendRequest($this->url['contract_detail'], $data, 'GET');
	}

	/**
	 * 验证创建文档的数据格式
	 *
	 * @param  $data [array]
	 * @return bool
	 */
	public function checkDocment($data)
	{
		if (!array_key_exists("type", $data) || !array_key_exists("content", $data) || !array_key_exists("title", $data)) {
			return false; //缺少参数
		}
		if ($data['type'] == 'file') {
			if (($ext = strrchr($data['content'], '.')) && in_array(strtolower(substr($ext, 1)), ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif', 'tif'])) {
			} else {
				return false; //文件格式错误
			}
		}
		if ($data['type'] == 'images') {
			if (!is_array($images = json_decode($data['content'], 1))) {
				return false; //文件格式错误
			}
			foreach ($images as $image) {
				if ((!$ext = strrchr('.', $image)) || !in_array(strtolower($ext), ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif', 'tif'])) {
					return false; //文件格式错误
				}
			}
		}
		return true;
	}

	/**
	 * 验证创建合同的数据格式
	 *
	 * @param  $data [array]
	 * @return bool
	 */
	public function checkContract($post_data)
	{
		if (!array_key_exists("subject", $post_data) || !array_key_exists("documents", $post_data)) {
			return false; // 参数异常
		}
		if (array_key_exists("signatories", $post_data)) {
			// 暂时去掉!!!
			// foreach ($signatories as $r) {
			//     if (($r['tenantType'] !== 'PERSONAL' && $r['tenantType'] !== 'COMPANY' && $r['tenantType'] !== 'PLATFORM')) {
			//         return false; //未指定接收人类型或者接收人类型错误
			//     }
			//     if ($r['tenantType'] === 'PERSONAL' || $r['tenantType'] === 'COMPANY') {
			//         if (!$r['type']) {
			//             return false; //.接收方是个人（或公司）时，type,name,mobile,authLevel必填
			//         }
			//         if (!is_int($r['ordinal'])) {
			//             return false; //签署顺序是顺序签署时，ordinal不能为空，且Number类型
			//         }
			//     }
			// }
		}
		// !!!废弃
		if (array_key_exists("actions", $post_data)) {
			$actions = $post_data['actions'];
			if (!is_array($actions)) {
				$actions = json_decode($actions, true);
			}
			foreach ($actions as $action) {
				if (($action['type'] !== 'PERSONAL' && $action['type'] !== 'CORPORATE' && $action['type'] !== 'LP')) {
					return false; //未指定签署动作类型或者签署动作类型错误
				}
				if (!array_key_exists("name", $action)) {
					return false; //签署动作名称
				}
				if (array_key_exists("actionOperators", $action)) {
					$actionOperators = $action['actionOperators'];
					if (!is_array($actionOperators)) {
						$actionOperators = json_decode($actionOperators, true);
					}
					foreach ($actionOperators as $actionOperator) {
						if (!array_key_exists("operatorContact", $actionOperator)) {
							return false; //签署人联系方式
						}
					}
				}
				if (array_key_exists("locations", $action)) {
					$locations = $action['locations'];
					if (!is_array($locations)) {
						$locations = json_decode($locations, true);
					}
					foreach ($locations as $location) {
						//合同文档ID    签章类型： SEAL_PERSONAL（个人签名）, SEAL_CORPORATE（公司公章）     对应的动作名称（必须与创建合同时的签署动作名称一致）
						if (!array_key_exists("documentId", $location) || !array_key_exists("rectType", $location) || !array_key_exists("actionName", $location)) {
							return false;
						}
						if (!in_array($location['rectType'], ['SEAL_PERSONAL', 'SEAL_CORPORATE'])) {
							return false;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * 办理流程页面解析pageMain数据的时候，调用契约锁service的解析函数，解析此流程是否已经关联契约锁
	 * 如果关联了契约锁设置，那么修改 node_operation ，让页面对应的附件控件出现签署等按钮
	 *
	 * @author dingpeng
	 * @param   [type]  $returnData  [流程办理页面解析函数返回的所有数据]
	 *
	 * @return  [type]           [修改了node_operation的数据]
	 */
	public function parseFlowRunRelationQysSetting($returnData)
	{
		$flowType = isset($returnData["flowType"]) ? $returnData["flowType"] : "";
		if ($flowType != "1") {
			// 非固定流程不管
			return $returnData;
		}
		$formId = isset($returnData["formId"]) ? $returnData["formId"] : "";
		$flowId = isset($returnData["flow_id"]) ? $returnData["flow_id"] : "";
		$runId = isset($returnData["run_id"]) ? $returnData["run_id"] : "";
		$nodeId = isset($returnData["nodeId"]) ? $returnData["nodeId"] : "";
		// 有集成设置
		if ($settingInfo = $this->getFlowRunRelationQysSetting($flowId)) {
			// 如果当前流程所在节点，在设置info里面，设置了签署权限，那么:合同文件字段 的操作权限里，加上(qys_sign)
			$haveSignOperationFlag = false;
			$qysOperationList = [];
			// 合同文件字段 documentField
			$documentFieldControl = isset($settingInfo['documentField']) ? $settingInfo['documentField'] : '';
			// 合同模板文件字段 templateField
			$templateFieldControl = isset($settingInfo['templateField']) ? $settingInfo['templateField'] : '';
			// 此节点签署权限设置
			$operationSettingInfo = isset($settingInfo['settinghas_many_operation_info']) ? $settingInfo['settinghas_many_operation_info'] : [];
			if (!empty($operationSettingInfo)) {
				foreach ($operationSettingInfo as $operationKey => $operationValue) {
					$operationString = isset($operationValue['operation']) ? $operationValue['operation'] : "";
					if ($operationValue['nodeId'] == $nodeId && $operationString) {
						$qysOperationList = explode(",", trim($operationString, ","));
					}
				}
			}
			if (!empty($qysOperationList)) {
				foreach ($qysOperationList as $key => $value) {
					$qysOperationList[$key] = 'qys_' . $value;
				}
				$nodeOperation = isset($returnData['node_operation']) ? $returnData['node_operation'] : [];
				if ($documentFieldControl) {
					$documentFieldControlOperation = isset($nodeOperation[$documentFieldControl]) ? $nodeOperation[$documentFieldControl] : [];
					// 合并字段权限和签署权限
					$controlOperation = array_merge($documentFieldControlOperation, $qysOperationList);
					$nodeOperation[$documentFieldControl] = $controlOperation;
				}
				if ($templateFieldControl) {
					$templateFieldControlOperation = isset($nodeOperation[$templateFieldControl]) ? $nodeOperation[$templateFieldControl] : [];
					// 合并字段权限和签署权限
					$controlOperation = array_merge($templateFieldControlOperation, $qysOperationList);
					$nodeOperation[$templateFieldControl] = $controlOperation;
				}
				$returnData['node_operation'] = $nodeOperation;
			}
		}
		return $returnData;
	}
	/**
	 * 根据flowid获取契约锁集成配置并判断
	 *
	 * @author dingpeng
	 * @param   [type]  $flowId  [$flowId description]
	 *
	 * @return  [type]           [return description]
	 */
	public function getFlowRunRelationQysSetting($flowId)
	{
		$settingInfo = app($this->electronicSignService)->getIntegrationDetailByFlowId($flowId);
		// 当前定义流程关联了契约锁设置
		if (!empty($settingInfo) && !isset($settingInfo['code'])) {
			$settingInfo = $settingInfo->toArray();
			return $settingInfo;
		}
		return '';
	}
	/**
	 * 获取合同的查看、下载、打印地址
	 *
	 * @param [type] $contractId 合同id
	 * @param [type] $data  定义流程id flowID  type 类型[presign, view, download, print]
	 * @param [type] $own
	 *
	 * @return [array]
	 * @author yuanmenglin
	 * @since
	 */
	public function getContractUrl($contractId, $data, $own, $server = '')
	{
		$flowId = $data['flowId'];
		$this->init($flowId);
		if (!$this->qiyuesuoApi) {
			//根据服务配置实例化 契约锁API service失败
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093515')]);
			$this->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093515', 'electronicsign']];
		}
		if (!$server) {
			$server = app($this->qiyuesuoServerRepository)->getServerByFlowId($flowId);
		}
		$url = isset($this->url[$data['type']]) ? $this->url[$data['type']] : 'view';
		$log = [
			'flowId' => $flowId,
			'runId' => $data['runId'] ?? 0,
			'userid' => $own['user_id'] ?? 0,
			'ipaddress' => getClientIp(),
			'createTime' => date('Y-m-d H:i:s'),
			'requestJson' => json_encode($data),
			'contractId' => $contractId,
			'action' => $data['type'] . '_contract',
			'serverId' => $server->serverId ?? '',
		];
		if ($data['type'] == 'presign') {
			// 合同状态为草稿时才可以指定签署位置
			$contractId = $this->checkRecreated($contractId, $data, $own);
			if (is_array($contractId) && !isset($contractId['contractId'])) {
				return $contractId;
			} else {
				$contractId = $contractId['contractId'];
			}
			$contract = $this->getContract($flowId, ['contractId' => $contractId]);
			if (isset($contract['contract']) && isset($contract['contract']['status']) && $contract['contract']['status'] != 'DRAFT') {
				$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093537')]);
				$this->addLog($this->qiyuesuoContractLogRepository, $log);
				return ['code' => ['0x093537', 'electronicsign']];
			}
		}
		if ($data['type'] == 'download') {
			// 获取合同状态  未完成的合同不能下载
			$contract = $this->getContract($flowId, ['contractId' => $contractId]);
			if (isset($contract['contract']) && isset($contract['contract']['status']) && !in_array($contract['contract']['status'], ['COMPLETE', 'FINISHED'])) { // $contract['contract']['status'] != 'COMPLETE'
				$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093538')]);
				$this->addLog($this->qiyuesuoContractLogRepository, $log);
				return ['code' => ['0x093538', 'electronicsign']];
			}
			// 先判断合同id对应附件文件是否存在
			$attachmentId = app($this->flowRunRelationQysContractInfoRepository)->getFieldValue('attachmentId', ['contractId' => $contractId]);
			$attachment = app($this->attachmentService)->getOneAttachmentById($attachmentId);
			if ($attachmentId && $attachment) {
				// 判断相关附件中是否有，没有添加到流程相关附件
                $data['processId'] = $data['processId'] ?? '';
                $data['flowProcess'] = $data['flowProcess'] ?? '';
				if ($data['runId'] && $data['processId'] && $data['flowProcess']) {
					$result = $this->saveFlowPublicAttachment($attachmentId, $data['runId'], $data['processId'], $data['flowProcess'], $own['user_id']);
					if (isset($result['code'])) {
						$log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success_save_flow_related_failed')]);
						$this->addLog($this->qiyuesuoContractLogRepository, $log);
						return $result['code'];
					} else {
						$log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success')]);
						$this->addLog($this->qiyuesuoContractLogRepository, $log);
						return $result;
					}
				} else {
					$log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success')]);
					$this->addLog($this->qiyuesuoContractLogRepository, $log);
					return ['attachmentId' => $attachmentId];
				}
			}
			$filename = trans('electronicsign.qiyuesuo_contract_file') . '-' . ($contractId ?? time());
			// $filename = iconv("UTF-8", "GBK", $filename);
			$suffix = 'zip';
			$result = $this->qiyuesuoApi->sendRequest($url, ['contractId' => $contractId], 'GET');
			if ($result) {
				// 保存本地查看文件有效性 测试使用 之前保存了文件损坏
				// $localPath = storage_path('logs').'/'.'contract/'. $contractId . time() .'.zip';
				// $this->checkDownloadAndReturn($result, $localPath);
				// $content = file_get_contents($localPath);
				$attachmentId = $this->saveFile($filename, $suffix, $result, $own['user_id'], $contractId, $data['runId'] ?? '', $data['processId'] ?? '', $data['flowProcess'] ?? '');
				if (isset($attachmentId['code'])) {
					$log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success_save_flow_related_failed')]);
					$this->addLog($this->qiyuesuoContractLogRepository, $log);
					return $attachmentId['code'];
				} else {
					$log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success')]);
					$this->addLog($this->qiyuesuoContractLogRepository, $log);
					return $attachmentId;
				}
			} else {
				$log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_failed')]);
				$this->addLog($this->qiyuesuoContractLogRepository, $log);
				return ['code' => ['0x093538', 'electronicsign']];
			}
		} else {
			$result = $this->qiyuesuoApi->sendRequest($url, ['contractId' => $contractId], 'GET');
			//处理返回结果  通知将地址键值统一修改url  失败的错误信息格式整理
			$urlType = ['efPrintUrl', 'printUrl', 'presignUrl', 'viewUrl'];
			if (is_array($result)) {
				if (!isset($result['code'])) {
					foreach ($urlType as $val) {
						if (isset($result[$val])) {
							$result['url'] = $result[$val];
							unset($result[$val]);
							break;
						}
					}
					$result = $result['url'];
					$log['responseJson'] = json_encode(['message' => trans('electronicsign.operate_contract_success')]);
				} else {
					$message = $result['message'] ?? trans('electronicsign.operate_contract_failed');
					$log['responseJson'] = json_encode(['message' => $message]);
					$result = ['code' => ['', $message]];
				}
			} else {
				$log['responseJson'] = json_encode(['message' => $result]);
				$result = ['code' => ['', $result]];
			}
			$this->addLog($this->qiyuesuoContractLogRepository, $log);
			return $result;
		}
	}

	/**
	 * 参照Mobile模块的moveFile函数 将文件流保存为附件
	 *
	 * @param [type] $name   文件名
	 * @param [type] $suffix    文件后缀
	 * @param [type] $content   文件流内容
	 * @param [type] $userId    用户id
	 * @param [type] $contractId    合同id
	 *
	 * @return array
	 * @author yuanmenglin
	 * @since
	 */
	public function saveFile($name, $suffix, $attachment, $userId, $contractId, $runId, $processId = '', $flowProcess = '', $relationTable = '', $type = 'qys_contract')
	{
		$attachmentId = app($this->attachmentService)->makeAttachmentId($userId);

		$attachmentPath = app($this->attachmentService)->createCustomDir($attachmentId);

		$attachmentName = $name . '.' . $suffix;

		$fullAttachmentName = $attachmentPath . $attachmentName;

		// 参照Mobile模块的moveFile函数 数据流不用处理
		// $attachment = base64_decode($attachment);

		$attachmentSize = strlen($attachment);

		$handle = @fopen($fullAttachmentName, "a");

		fwrite($handle, $attachment);
		// fputs($handle,$attachment);//写入文件

		fclose($handle);
		$thumbAttachmentName = '';
		$attachmentPaths = app($this->attachmentService)->parseAttachmentPath($fullAttachmentName);
		$attachmentInfo = [
			"attachment_id" => $attachmentId,
			"attachment_name" => $attachmentName,
			"affect_attachment_name" => $attachmentName,
			'new_full_file_name' => $fullAttachmentName,
			"thumb_attachment_name" => $thumbAttachmentName,
			"attachment_size" => $attachmentSize,
			"attachment_type" => $suffix,
			"attachment_create_user" => $userId,
			"attachment_base_path" => $attachmentPaths[0],
			"attachment_path" => $attachmentPaths[1],
			"attachment_mark" => 9, // 9为zip
			"relation_table" => $relationTable,
			"rel_table_code" => isset($relationTable) ? md5($relationTable) : "",
		];
		app($this->attachmentService)->handleAttachmentDataTerminal($attachmentInfo); //组装数据 存入附件表
		if ($type == 'qys_contract') {
			// 将附件id存到合同流程相关表中
			app($this->flowRunRelationQysContractInfoRepository)->updateData(['attachmentId' => $attachmentId], ['contractId' => $contractId]);
		}
		// 保存到流程公共附件
		if ($processId && $flowProcess) {
			// 获取已有的公共附件
			return $this->saveFlowPublicAttachment($attachmentId, $runId, $processId, $flowProcess, $userId);
		}
		return compact('attachmentId');
	}

	/** 保存到流程相关附件
	 * @param $attachmentId
	 * @param $runId
	 * @param $processId
	 * @param $flowProcess
	 * @param $userId
	 * @return array
	 */
	public function saveFlowPublicAttachment($attachmentId, $runId, $processId, $flowProcess, $userId)
	{
		$flowAttachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'flow_run', 'entity_id' => ["run_id" => [$runId]]]);
		if (!in_array($attachmentId, $flowAttachments)) {
			array_unshift($flowAttachments, $attachmentId);
			$data = [
				'attachments' => implode(',', $flowAttachments),
				'user_id' => $userId,
				'process_id' => $processId,
				'flow_process' => $flowProcess,
			];
			if ($runId) {
				// 当前接口以修改的方式保存公共附件
				//                $res = app($this->flowService)->postFlowPublicAttachmentService($data, $runId);
				$relationInfo = [
					"table_name" => "flow_run",
					"fileds" => [
						[
							"field_name" => "run_id",
							"field_type" => "integer",
							"field_length" => "",
							"field_common" => "流程ID",
						],
						[
							"field_name" => "user_id",
							"field_type" => "string",
							"field_length" => "20",
							"field_common" => "用户ID",
						],
						[
							"field_name" => "edit_time",
							"field_type" => "dateTime",
							"field_length" => "",
							"field_common" => "编辑时间",
						],
						[
							"field_name" => "flow_process",
							"field_type" => "integer",
							"field_length" => "",
							"field_common" => "流程节点ID",
						],
						[
							"field_name" => "process_id",
							"field_type" => "integer",
							"field_length" => "",
							"field_common" => "步骤ID",
						],
					],
				];
				$runInfo = [
					"run_id" => [$runId],
					"user_id" => [$data["user_id"]],
					// "edit_time" => [date("Y-m-d H:i:s",time())],
					"flow_process" => [$data["flow_process"]],
					"process_id" => [$data["process_id"]],
					"wheres" => ["run_id" => [$runId]],
				];
				$res = app($this->attachmentService)->attachmentRelation($relationInfo, $runInfo, $data["attachments"]);
				if (!$res) {
					return ['attachmentId' => $attachmentId, 'code' => ['download_contract_success_save_flow_related_failed', 'electronicsign']];
				}
			}
		}
		return compact('attachmentId');
	}
	/**
	 * 保存到本地storage下 【数据流保存到附件文件损坏时测试使用】
	 *
	 * @param [type] $output
	 * @param [type] $path
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */

	public function checkDownloadAndReturn($output, $path)
	{
		//判断是否返回文件流
		$array_output = json_decode($output, true);
		if (is_array($array_output) && array_key_exists("code", $array_output) && $array_output['code'] !== 0) {
			return array(
				"code" => 1001,
				"message" => '下载文件失败,' . $array_output['message'],
			);
		}
		//对文件名的编码，避免中文文件名乱码
		$destination = iconv("UTF-8", "GBK", $path);
		$file = fopen($destination, "w+");
		$answer = fputs($file, $output); //写入文件
		fclose($file);
		if ($answer === false) {
			return array(
				"code" => 1001,
				"message" => '下载文件失败',
			);
		} else {
			return array(
				"code" => 0,
				"message" => '下载文件完成,字节数:' . $answer,
			);
		}
	}

	/**
	 * 获取契约锁合同列表  暂不需要
	 *
	 * @param [type] $params
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function contractList($params)
	{
		$params = $this->parseParams($params);
		// 通过契约锁接口获取
		// $this->init(0);
		// if (!$this->qiyuesuoApi) {
		//     return ['code' => ['0x093515', 'electronicsign']];
		// }
		// $params['selectLimit'] = 10;
		// return $this->qiyuesuoApi->sendRequest($this->url['contracts'], $params, 'GET');
		// 本地数据获取 无合同状态
		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];

		if ($response == 'both' || $response == 'count') {
			$count = app($this->flowRunRelationQysContractInfoRepository)->getCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			$list = app($this->flowRunRelationQysContractInfoRepository)->getList($params);
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 契约锁回调合同状态
	 *
	 * @param [type] $data
	 * @param [type] $user
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function changeContractStatus($data, $user)
	{
		if (!isset($data['contractId'])) {
			return false;
		}
		if (empty($user)) {
		    $user  = ['user_id' => 'admin'];
        }
		$contractId = $data['contractId'] ?? '';
		$status = $data['status'] ?? '';
		$oldStatus = app($this->flowRunRelationQysContractInfoRepository)->getFieldValue('contractStatus', ['contractId' => $contractId]);
		// 当前只考虑草稿、签署中和已完成状态   由于同时回调签署中和已完成的顺序问题 不处理：已完成的不能改为签署中, 已发起的回传状态为草稿
        if (($oldStatus == 'COMPLETE' && $status == 'SIGNING') || ($oldStatus == 'SIGNING' && $status == 'DRAFT')) {
            return false;
        }
//		if (!$oldStatus || ($oldStatus == 'COMPLETE' && $status != 'SIGNING')) {
			app($this->flowRunRelationQysContractInfoRepository)->updateData(['contractStatus' => $status, 'callback' => json_encode($data)], ['contractId' => $contractId]);
			// 合同签署完成 -- 自动下载合同文件 --- 根据合同id获取流程id，根据流程id获取关联的契约锁服务，调用对应下载合同接口
			if ($status == 'COMPLETE') {
				$runId = app($this->flowRunRelationQysContractInfoRepository)->getFieldValue('runId', ['contractId' => $contractId]);
				if ($runId) {
					$flowRunInfo = app($this->flowRunRepository)->getDetail($runId);
					$flowId = $flowRunInfo->flow_id ?? 0;
					$maxProcessId = $flowRunInfo->max_process_id;
					$processId = $maxProcessId - 1;
					$flowProcess = $flowRunInfo->current_step ?? 0;
					if ($flowId) {
						app($this->electronicSignService)->getContractUrl($contractId, ['flowId' => $flowId, 'runId' => $runId, 'processId' => $processId, 'flowProcess' => $flowProcess, 'type' => 'download'], $user);
					}
				}
			}
			// 系统信息通知
            app($this->electronicSignService)->sendMessage([
                'contractId' => $contractId, 'status' => $status, 'data' => $data
            ], 'contract');
//		}
		return true;
	}

	/**
	 * 工作流契约锁物理用印授权日志
	 * @param $params
	 * @return array|int
	 * @author [dosy]
	 */
	public function getSealApplyAuthLogsList($params)
	{
		$params = $this->parseParams($params);
		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];
		$count = 0;
		if (!in_array($response, ['both', 'count', 'data'])) {
			return ['code' => ['0x093517', 'electronicsign']];
		}
		if ($response == 'both' || $response == 'count') {
			$count = app($this->qiyuesuoSealApplyAuthLogRepository)->getCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			foreach (app($this->qiyuesuoSealApplyAuthLogRepository)->getList($params) as $new) {
				$list[] = $new;
			}
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 添加日志
	 * @param $repositoryObj 仓库
	 * @param $params
	 * @return bool
	 * @author [dosy]
	 */
	public function addLog($repositoryObj, $params)
	{
		$insertData = array_intersect_key($params, array_flip(app($repositoryObj)->getTableColumns()));
		if (empty($insertData)) {
			return ['code' => ['add_failed', 'electronicsign']];
		}
		$resAdd = app($repositoryObj)->insertData($insertData);
		return $resAdd;
	}

	/**
	 * 工作流契约锁物理用印授权文档创建日志
	 * @param $params
	 * @return array|int
	 * @author [dosy]
	 */
	public function getSealApplyCreateDocLogsList($params)
	{
		$params = $this->parseParams($params);

		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];
		$count = 0;
		if (!in_array($response, ['both', 'count', 'data'])) {
			return ['code' => ['0x093517', 'electronicsign']];
		}
		if ($response == 'both' || $response == 'count') {
			$count = app($this->qiyuesuoSealApplyCreateDocLogRepository)->getCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			foreach (app($this->qiyuesuoSealApplyCreateDocLogRepository)->getList($params) as $new) {
				$list[] = $new;
			}
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 工作流契约锁物理用印日志
	 * @param $params
	 * @return array|int
	 * @author [dosy]
	 */
	public function getSealApplyLogsList($params)
	{
		$params = $this->parseParams($params);

		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];
		$count = 0;
		if (!in_array($response, ['both', 'count', 'data'])) {
			return ['code' => ['0x093517', 'electronicsign']];
		}
		if ($response == 'both' || $response == 'count') {
			$count = app($this->qiyuesuoSealApplyLogRepository)->getCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			foreach (app($this->qiyuesuoSealApplyLogRepository)->getList($params) as $new) {
				$list[] = $new;
			}
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 发起物理用印申请
	 *
	 * @param [type] $data 流程及表单信息
	 * @param [type] $userInfo 用户信息
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function createSealApplyV1($data, $userInfo)
	{
		$flowId = $data['flowId'] ?? '';
		$runId = $data['runId'] ?? '';
		$userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
		$applyLog = [
			'flowId' => $flowId,
			'runId' => $runId,
			'userid' => $userId,
			'ipaddress' => getClientIp(),
			'createTime' => date('Y-m-d H:i:s'),
			'requestJson' => json_encode($data),
			'action' => 'seal_apply',
		];
		if (!isset($flowId) || !isset($runId)) {
			//提示：参数流程信息和表单数据未找到
			$applyLog['responseJson'] = json_encode(['message' => trans('electronicsign.0x093517')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return ['code' => ['0x093517', 'electronicsign']];
		}
		$config = app($this->qiyuesuoConfigRepository)->getItem('qys_on_off');
		if ($config && isset($config[0]) && isset($config[0]['paramValue']) && $config[0]['paramValue'] == 0) {
			//提示：配置项契约锁服务未开启
			$applyLog['responseJson'] = json_encode(['message' => trans('electronicsign.0x093533')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return ['code' => ['0x093533', 'electronicsign']];
		}
		// 基础信息
		if (empty($flowId)) {
			$flowId = app($this->flowRunService)->getFlowIdByRunId($runId);
		}
		$formId = $data['formId'] ?? '';
		$formControlData = app($this->flowFormService)->getParseForm($formId);
		$formControlData = array_column($formControlData, NULL, 'control_id');
		// 根据定义流程id获取契约锁服务 -- 获取服务接口需修改
		$this->init($flowId, 'seal_apply');
		if (!$this->qiyuesuoApi) {
			//提示：契约锁开放apiservice实例不存在
			$applyLog['responseJson'] = json_encode(['message' => trans('electronicsign.0x093515')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return ['code' => ['0x093515', 'electronicsign']];
		}
		// 传过来的表单数据
		$formData = $data;
		// 获取用印配置 根据物理用印配置解析表单数据
		$sealConfig = app($this->qiyuesuoSealApplySettingRepository)->getSettingByFlowId($flowId);
		if (!$sealConfig) {
			// 获取设置失败
			$applyLog['responseJson'] = json_encode(['message' => trans('electronicsign.seal_apply_docment_get_setting_failed')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return ['code' => ['0x093515', 'electronicsign']];
		}
		$sealConfig = $sealConfig->toArray();
		// 发起申请日志
		$applyLog['settingId'] = $sealConfig['settingId'];
		$applyLog['serverId'] = $sealConfig['serverId'];
		// 用印授权人数组
		$auths = $this->sealApplyAuths($sealConfig, $formData, $formControlData);
		if (isset($auths['code'])) {
			$applyLog['responseJson'] = json_encode(['message' => $auths['code'][0] ? trans('electronicsign.' . $auths['code'][0]) : trans('electronicsign.seal_apply_user_apply_failed')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return $auths;
		}
		//创建文档
		$documentIds = [];
		$documentNames = [];
		// 创建文档日志
		$createDocLogs = [];
		//解析合同模板数据 用模板创建合同文档  难点在于模板参数的处理 暂不考虑有模板参数的情况  暂时注释
		$templates = $sealConfig['sealTemplateField'] ?? '';
		if ($templates) {
			// 下拉框单选时传递的是字符串，多选时为数组
			$templateIds = isset($formData[$templates]) ? (is_array($formData[$templates]) ? $formData[$templates] : [$formData[$templates]]) : [];
			// 模板名为以,号隔开的字符串
			$templateNames = isset($formData[$templates . '_TEXT']) ? (explode(',', $formData[$templates . '_TEXT'])) : [];
			if ($templateIds) {
				foreach ($templateIds as $key => $val) {
					$documentId = $this->createDocument([
						'content' => $val, // 模板id
						'title' => $templateNames[$key] ?? '',
						'params' => '', // 模板参数 暂未想到好的处理方法
						'type' => 'template',
					]);
					if (isset($documentId['code'])) {
						//生成文档失败及原因
						$applyLog['responseJson'] = json_encode(['message' => isset($documentId['code'][0]) && !empty($documentId['code'][0]) ? trans('electronicsign.' . $documentId['code'][0]) : trans('electronicsign.seal_apply_docment_template_get_failed')]);
						$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
						return $documentId; //['code' => ['0x093519', 'electronicsign']];
					}
					$documentNames[] = $templateNames[$key] ?? '';
					$documentIds[] = [
						'runId' => $runId,
						'documentId' => $documentId['documentId'],
						'attachmentId' => '',
					];
					// 创建文档日志
					$createDocLogs[] = [
						'flowId' => $flowId,
						'runId' => $runId,
						'documentId' => $documentId['documentId'],
						'userid' => $userInfo['user_id'] ?? '',
						'ipaddress' => getClientIp(),
					];
				}
			}
		}
		//解析附件地址
		// 文档id数组 --- 根据附件id上传文档获得文档id
		$sealDocumentField = $sealConfig['sealDocumentField'] ?? '';
		$attachmentIds = isset($formData[$sealDocumentField]) ? $formData[$sealDocumentField] : [];
		//获取附件对应地址
		if ($attachmentIds) {
			$attachmentUrls = app($this->attachmentService)->getMoreAttachmentById($attachmentIds);
			foreach ($attachmentUrls as $attachmentUrl) {
				$documentId = $this->createDocumentByFile([
					'title' => $attachmentUrl['attachment_name'],
					'file' => $attachmentUrl['temp_src_file'],
				]);
				if (isset($documentId['code'])) {
					//根据附件生成文档失败及原因
					$applyLog['responseJson'] = json_encode(['message' => isset($documentId['code'][0]) && !empty($documentId['code'][0]) ? trans('electronicsign.' . $documentId['code'][0]) : trans('electronicsign.seal_apply_docment_attachement_get_failed')]);
					$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
					return $documentId; //['code' => ['0x093519', 'electronicsign']];
				}
				$documentNames[] = $attachmentUrl['attachment_name'];
				$documentIds[] = [
					'runId' => $runId,
					'documentId' => $documentId['documentId'],
					'attachmentId' => $attachmentUrl['attachment_id'],
				];
				// 创建文档日志
				$createDocLogs[] = [
					'flowId' => $flowId,
					'runId' => $runId,
					'documentId' => $documentId['documentId'],
					'userid' => $userInfo['user_id'] ?? '',
					'ipaddress' => getClientIp(),
				];
			}
		}
		// 拼接[文档]信息
		$documents = array_column($documentIds, 'documentId');
		if ($documents && $createDocLogs) {
			foreach ($createDocLogs as $createDocLog) {
				$this->addLog($this->qiyuesuoSealApplyCreateDocLogRepository, $createDocLog);
			}
		}
		// 发起申请所需参数 --- 【必填】发起公司名、用印主题、申请人电话
		$tenantName = $this->getValue($sealConfig, $formData, 'tenantNameField', -1);
		$subject = $this->getValue($sealConfig, $formData, 'subjectField', -1);
		$categoryId = $this->getValue($sealConfig, $formData, 'sealCategoryIdField', -1);
		$serialNo = $this->getValue($sealConfig, $formData, 'serialNoField', -1);
		$description = $this->getValue($sealConfig, $formData, 'descriptionField', -1);
		// 申请人相关信息获取需调整 兼容文本框和系统数据源
		// $applyerName = $this->getValue($sealConfig, $formData, 'applyerNameField', -1);
		$applyerName = $this->parseFlowDataOutSend($formData, $formControlData, 'applyerNameField', $sealConfig, -1);
		// $applyerContact = $this->getValue($sealConfig, $formData, 'applyerContactField', -1);
		$applyerContact = $this->parseFlowDataOutSend($formData, $formControlData, 'applyerContactField', $sealConfig, -1);
		if (!$applyerContact) {
			$applyLog['responseJson'] = json_encode(['message' => trans('electronicsign.seal_apply_docment_applyer_contact_empty')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return ['code' => ['seal_apply_docment_applyer_contact_empty', 'electronicsign']];
		}
//        $url = $categoryId ? $this->url['create_seal_apply_by_category'] : $this->url['create_seal_apply'];
		$url = $this->url['create_seal_apply'];
        $params = [
            'tenantName' => $tenantName, // 发起公司名称
            'subject' => $subject, // 此次物理用印主题
            'categoryId' => $categoryId, // 业务分类id
            'serialNo' => $serialNo, // 序列号
            'description' => $description, //描述
            'applyerName' => $applyerName, // 申请人姓名
            'applyerContact' => $applyerContact, // 申请人联系方式
            'auths' => $auths, // 用印授权人数组
            'documents' => $documents, // 文档id数组
            'forms' => [], // 模板参数集合 暂不支持
        ];
		// 发起物理用印申请
		$sealApplyReponse = $this->qiyuesuoApi->sendRequest($url, $params);
		$applyLog['responseJson'] = json_encode($sealApplyReponse);
		if (isset($sealApplyReponse['code'])) {
			$sealAppyReponseOne = isset($sealApplyReponse['code'][1]) && (!empty($sealApplyReponse['code'][1])) ? $sealApplyReponse['code'][1] : '';
			$applyLog['responseJson'] = json_encode(['message' => ($sealAppyReponseOne ?? trans('electronicsign.seal_apply_response_data_get_failed'))]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return $sealApplyReponse;
		} else {
			// 记录请求日志及返回信息中用印授权的相关信息 -- 授权码
			$result = $sealApplyReponse['result'] ?? [];
			$applyLog['businessId'] = $businessId = $result['id'] ?? '';
			$sealAuths = $result ? ($result['sealAuths'] ?? []) : [];
			if ($sealAuths) {
				$logs = [];
				foreach ($sealAuths as $key =>$sealAuth) {
					$logs[] = [
						'flowId' => $flowId,
						'runId' => $runId,
						'authId' => $sealAuth['id'] ?? '',
						'authuserId' => $sealAuth['userId'] ?? '',
						'userName' => $sealAuth['userName'] ?? '',
						'contact' => $sealAuth['contact'] ?? ($auths[$key]['users'][0]['mobile'] ?? ''),
						'userNumber' => $sealAuth['userId'] ?? '',
						'vertifyCode' => $sealAuth['vertifyCode'] ?? '',
						'sealName' => $sealAuth['sealName'] ?? '',
						'sealId' => $sealAuth['sealId'] ?? '',
						'ownerName' => $sealAuth['ownerName'] ?? '',
						'deviceNo' => $sealAuth['deviceNo'] ?? '',
						'businessId' => $businessId,
					];
				}
				//记录授权码日志
				if ($logs) {
					foreach ($logs as $log) {
						$this->addLog($this->qiyuesuoSealApplyAuthLogRepository, $log);
					}
				}
			}
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			// 发送系统消息提醒
            app($this->electronicSignService)->sendMessage([
                'businessId' => $businessId, 'data' => ['status' => 'CREATE']
            ], 'seal_apply');
		}
		return true;
	}
	/**
	 * 根据配置解析授权人信息---包括印章识别码、用印次数、用印起始结束时间及用印授权人
	 *
	 * @param [type] $config
	 * @param [type] $flowData 流程表单数据
	 * @param [type] $formControlData  表单控件数据
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function sealApplyAuths($config, $flowData, $formControlData)
	{
		// 注意事项：授权人手机号取值 -- 判断是否明细 --判断表单控件类型  单行：,/; 分隔多个  选择器,分隔
		$deviceNoField = $config['deviceNoField'] ?? '';
		// 取表结构
		$formId = $flowData['formId'] ?? '';
		if (!$formControlData) {
			$formControlData = app($this->flowFormService)->getParseForm($formId);
			$formControlData = array_column($formControlData, NULL, 'control_id');
		}
		$fieldDetailLayoutId = '';
		if ($deviceNoField) {
			$fieldValueArray = explode('_', $deviceNoField);
			if ($fieldValueArray && count($fieldValueArray) > 2) {
				$fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
			}
		}
		$auths = [];
		// 明细--多印章
		if ($fieldDetailLayoutId) {
			$detail = $flowData[$fieldDetailLayoutId] ?? [];
			$length = count($detail);
			if ($length) {
				for ($i = 0; $i < $length; $i++) {
					$auth = $this->setOneAuth($config, $flowData, $formControlData, $i);
					if (isset($auth['code'])) {
						// return $auth;
						continue;
					} else {
						$auths[] = $auth;
					}
				}
			} else {
			}
		} else {
			// 单印章
			$auth = $this->setOneAuth($config, $flowData, $formControlData, -1);
			if (isset($auth['code'])) {
				return $auth;
			} else {
				$auths[] = $auth;
			}
		}
		return $auths;
	}

    private function setOneAuth($config, $flowData, $formData, $i)
    {
        $auth = [];
        $deviceNo = $this->getValue($config, $flowData, 'deviceNoField', $i);
        if (!$deviceNo) {
            return ['code' => ['seal_apply_device_number_get_failed', 'electronicsign']];
        }
        $count = $this->getValue($config, $flowData, 'countField', $i);
        if (!$deviceNo) {
            return ['code' => ['seal_apply_count_apply_failed', 'electronicsign']];
        }
        $startTime = $this->getValue($config, $flowData, 'startTimeField', $i);
        if ($startTime) {
            $startTime = date_format(date_create($startTime), 'Y-m-d H:i:s');
        }
        $endTime = $this->getValue($config, $flowData, 'endTimeField', $i);
        if ($endTime) {
            $endTime = date_format(date_create($endTime), 'Y-m-d H:i:s');
        }
        $mobile = $this->parseFlowDataOutSend($flowData, $formData, 'mobileField', $config, $i);
        if (!$mobile) {
            return ['code' => ['seal_apply_phone_number_apply_failed', 'electronicsign']];
        }
        if (is_array($mobile)) {
            $mobiles = [];
            foreach($mobile as $value){
                $mobiles[] = ['mobile' => trim($value)];
            }
            $auth = [
                'deviceNo' => $deviceNo, //授权印章识别码
                // 'sealName' => '', // 授权印章名称 以上二选一
                'count' => $count, // 用印次数
                'startTime' => $startTime, //授权使用开始时间,格式样例：2019-04-09 09:18:53
                'endTime' => $endTime, // 授权使用结束时间,格式样例：2019-04-09 09:18:53
                'users' => $mobiles, // 用印授权人
            ];
        }
        return $auth;
    }

    public function getValue($config, $flowData, $fieldName, $i)
    {
        // 判断是否明细取值  当前$type是否来源明细字段
        $configFieldName = $config[$fieldName] ?? '';

        $detailValue = '';
        if ($configFieldName) {
            $fieldValueArray = explode('_', $configFieldName);
            if ($fieldValueArray && count($fieldValueArray) > 2) {
                $detailValue = $fieldValueArray[0] . '_' . $fieldValueArray[1];
            }
        } else {
            return '';
        }
        if ($i >= 0) { // 来源明细数据
            if ($detailValue) {
                $fieldValue = isset($flowData[$detailValue]) && isset($flowData[$detailValue][$i][$configFieldName]) && isset($flowData[$detailValue][$i][$configFieldName]) ? $flowData[$detailValue][$i][$configFieldName] : '';
            } else {
                $fieldValue = $flowData[$configFieldName];
            }
        } else { //来源表单数据
            if ($detailValue) {
                $fieldValue = isset($flowData[$detailValue]) && isset($flowData[$detailValue][0][$configFieldName]) && isset($flowData[$detailValue][0][$configFieldName]) ? $flowData[$detailValue][0][$configFieldName] : '';
            } else {
                $fieldValue = $flowData[$configFieldName];
            }
        }
        return $fieldValue;
    }

    /**
     * 功能函数，根据配置的字段，解析表单数据，同时考虑配置的控件的控件类型，合理取值
     * 函数，全面替换外发函数内的用法
     * @param  [type] $flowData    [流程数据]
     * @param  [type] $formData    [表单结构]
     * @param  [type] $field     [要取值的字段]
     * @param  [type] $config [配置info]
     * @return [type]              [description]
     */
    function parseFlowDataOutSend($flowData,$formData,$field,$config, $i)
    {
        $controlValue = '';
        // 取凭证字段 $field 对应的表单控件id
        $configFormField = $config[$field] ?? '';
        // 对应表单控件属性
        $controlInfo = $formData[$configFormField] ?? [];
        // 控件类型 可选值['text','textarea','radio','checkbox','select','label','editor','data-selector','signature-picture','upload','countersign','electronic-signature','dynamic-info','detail-layout']
        $controlType = $controlInfo['control_type'] ?? '';
        // 用印授权人字段解析  电子合同签署人联系方式  个人的签署方名称
        if(in_array($field, ['mobileField', 'contact', 'tenantName'])) {
            if($controlType == 'text' || $controlType == 'textarea') {
                // 单行或多行 以,或;分隔 验证手机号格式
                $controlValue = $i < 0 ? ($flowData[$configFormField] ?? '') : ($this->getValue($config, $flowData, $field, $i));
                $controlValue = str_replace(';', ',', $controlValue);
                if (is_string($controlValue)) {
                    $controlValue = explode(',', $controlValue);
                }
                if (in_array($field, ['mobileField', 'contact'])) {
                    foreach ($controlValue as $controlKey => $controlItem) {
                        if (preg_match("/^1[23456789]\d{9}$/", trim($controlItem)) == 0) {
                            // 验证失败移除
                            unset($controlValue[$controlKey]);
                        } else {
                            $controlValue[$controlKey] = trim($controlItem);
                        }
                    }
                }
            } else if ($controlType == 'data-selector'){
                $controlValuePerson = $i < 0 ? ($flowData[$configFormField] ?? '') :  ($this->getValue($config, $flowData, $field, $i));
                // $controlValue = str_replace([';', '，', '；'], ',', $controlValue);
                if (is_string($controlValuePerson)){
                    $controlValuePerson = explode(',', $controlValuePerson);
                }
                $controlValue = [];
                foreach ($controlValuePerson as $controlKey => $controlItem) {
                    $userInfo = app($this->userService)->getUserAllData($controlItem);
                    $userInfo = $userInfo ? $userInfo->toArray() : [];
                    $userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
                    if (in_array($field, ['mobileField', 'contact'])) {
                        $phoneNumber = isset($userOneInfo['phone_number']) ? trim($userOneInfo['phone_number']) : '';
                        if (!empty($phoneNumber) && preg_match("/^1[23456789]\d{9}$/", $phoneNumber) > 0) {
                            $controlValue[] = $phoneNumber;
                        }
                    } else {
                        $controlValue[] = $userInfo['user_name'] ?? '';
                    }
                }
            }
        }
        // 用印授权人名称字段解析 电子合同合同创建者字段、签署人联系方式字段
        if(in_array($field, ['applyerContactField', 'applyerNameField', 'creatorNameField', 'creatorContactField'])) {
            if($controlType == 'text' || $controlType == 'textarea') {
                // 单行或多行 以,或;分隔 验证手机号格式
                $controlValue = $i < 0 ? ($flowData[$configFormField] ?? '') : ($this->getValue($config, $flowData, $field, $i));
                if (in_array($field, ['applyerContactField', 'creatorContactField'])) {
                    $controlValue = trim($controlValue);
                    if (preg_match("/^1[23456789]\d{9}$/", $controlValue) == 0) {
                        // 验证失败移除
                        $controlValue = '';
                    }
                }
            } else if ($controlType == 'data-selector'){
                $controlValue = $i < 0 ? ($flowData[$configFormField] ?? '') :  ($this->getValue($config, $flowData, $field, $i));
                if (is_array($controlValue)) {
                    $controlValue = !empty($controlValue) ? ($controlValue[0] ?? '') : '';
                }
                $userInfo = app($this->userService)->getUserAllData($controlValue);
                $userInfo = $userInfo ? $userInfo->toArray() : [];
                $userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
                if (in_array($field, ['applyerContactField', 'creatorContactField'])) {
                    $phoneNumber = $userOneInfo['phone_number'] ? trim($userOneInfo['phone_number']) :'';
                    if (!empty($phoneNumber) && preg_match("/^1[23456789]\d{9}$/", $phoneNumber) > 0) {
                        $controlValue = $phoneNumber;
                    } else {
                        $controlValue = '';
                    }
                } else {
                    $controlValue = $userInfo['user_name'] ?? '';
                }
            }
        }
        return $controlValue;
    }

    /**
     * 获取公司印章列表 -- 同步数据使用
     *
     * @param [type] $flowId
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function syncSeals($userInfo = [])
    {
        $logData = [
            'operate_action' => 'syncSeals',
            'operate_type' => 'PHYSICSSEAL', // 同步物理印章
            'operator' => $userInfo['user_id'] ?? ''
        ];
        $config = app('App\EofficeApp\ElectronicSign\Services\ElectronicSignService')->getServerBaseInfo();
        // 获取所有服务
        if ($config && isset($config['qys_on_off']) && $config['qys_on_off'] == 1) {
            $successServer = $failServer = [];
            $servers = app($this->qiyuesuoServerRepository)->getServersList([]);
            if ($servers) {
                foreach ($servers as $server) {
                    if (!$server['serverUrl'] || !$server['accessKey'] || !$server['accessSecret']) {
                        // 服务相关数据错误
                        $server['error_message'] = 'sync_failed_server_not_found';
                        $failServer[] = $server;
                        continue;
                    }
                    // 初始化契约锁服务
                    $serverType = $server['serverType'];
                    $qiyuesuoApi = new QiyuesuoOpenApiService($server['serverUrl'], $server['accessKey'], $server['accessSecret'], $server['serverType']);
                    $sealsResponse = [];
                    if ($serverType == 'private') {
                        $params = ['category' => 'PHYSICS'];
                        // 请求获取所有内部企业(包括平台方)印章列表
                        // $physealsResponse = $qiyuesuoApi->sendRequest('/seal/innercompany/seallist', $params, 'GET'); // 物理章
                        $physealsResponse = $this->getSeals($qiyuesuoApi, $params);
                        // $elestricSealsResponse = $qiyuesuoApi->sendRequest('/seal/innercompany/seallist', [], 'GET'); // 电子章
                        $elestricSealsResponse = $this->getSeals($qiyuesuoApi, []);
                        if ($elestricSealsResponse || $physealsResponse) {
                            $sealsResponse['result'] = array_merge($physealsResponse, $elestricSealsResponse);
                        }
                    } else {
                        $qiyuesuoPublicServer = new QiyuesuoPublicCloudService();
                        $qiyuesuoPublicServer->qiyuesuoPublicApi = $qiyuesuoApi;
                        $companies = $qiyuesuoPublicServer->getCompanies(true);
                        $sealsResponse = $response = [];
                        foreach ($companies as $key => $val) {
                            $result = $qiyuesuoPublicServer->getList('seals', ['tenantName' => $val['name'], 'tenantId' => $val['id']]);
                            $response = array_merge($response, $result);
                        }
                        if ($response) {
                            $sealsResponse = [
                                'qys_code' => 0,
                                'result' => [['seals' => $response]]
                            ];
                        }
                    }
                    $serverId = $server['serverId'];
                    // 解析返回数据 将印章数据存入数据库
                    if (isset($sealsResponse['code']) && !empty($sealsResponse['code'])) {
                        // 请求失败
                        $server['error_message'] = 'sync_failed_request_failed';
                        $failServer[] = $server;
                        continue;
                    } else {
                        // 解析数据
                        $result = $sealsResponse['result'] ?? [];
                        $sealData = [];
                        if ($result) {
                            foreach ($result as $resultValue) {
                                $seals = $resultValue['seals'] ?? [];
                                foreach ($seals as $sealKey => $seal) {
                                    $sealData[] = [
                                        'sealId' => $seal['id'],
                                        'owner' => $seal['owner'] ?? ($seal['tenantId'] ?? ''),
                                        'ownerName' => $seal['ownerName'] ?? ($seal['tenantName'] ?? ''),
                                        'name' => $seal['name'],
                                        'type' => $seal['type'] ?? '',
                                        'createTime' => $seal['createTime'],
                                        'status' => isset($seal['status']) && isset($seal['status']['key']) ? $seal['status']['key'] : '',
                                        'useCount' => $seal['useCount'] ?? '',
                                        'category' => $seal['category'] ?? '',
                                        'deviceId' => $seal['deviceId'] ?? '',
                                        'bluetooth' => $seal['bluetooth'] ?? '',
                                        'location' => $seal['location'] ?? '',
                                        'serverId' => $serverId
                                    ];
                                }
                            }
                            // 删除之前的这个契约锁服务插入的印章，重新存入契约锁物理印章表
                            if ($sealData) {
                                app($this->qiyuesuoSealRepository)->deleteByWhere(['serverId' => [$serverId]]);
                                $res = true;
                                foreach (array_chunk($sealData, 100) as $array) {
                                    $res = app($this->qiyuesuoSealRepository)->insertMultipleData($array);
                                }
//                                $res = app($this->qiyuesuoSealRepository)->insertMultipleData($sealData);
                                if ($res) {
                                    $successServer[] = $server;
                                } else {
                                    $server['error_message'] = 'sync_failed_seals_save_error';
                                    $failServer[] = $server;
                                }
                            } else {
                                $server['error_message'] = 'sync_failed_seals_not_found';
                                $failServer[] = $server;
                            }
                        } else {
                            $server['error_message'] = 'sync_failed_seals_not_found';
                            $failServer[] = $server;
                        }
                    }
                }
                $this->syncLogData($successServer, $failServer, $logData, 'seal_apply');
            } else {
                // 未查询到契约锁服务，请先配置契约锁服务
                $logData['operate_result'] = 'sync_failed_server_not_found';
                app($this->qiyuesuoSyncRelatedResourceLogRepository)->insertData($logData);
            }
        } else {
            $logData['operate_result'] = 'sync_failed_server_not_open';
            app($this->qiyuesuoSyncRelatedResourceLogRepository)->insertData($logData);
        }
        return true;
    }

    /**
     * 检测流程节点是否有物理用印的集成触发配置
     *
     * @param [type] $flowId
     * @param [type] $nodeId
     * @param [type] $runId
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function checkOutsend($flowId, $nodeId, $runId, $action)
    {
        $outsendInfos = app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->getFieldInfo(['flowId' => $flowId, 'nodeId' => $nodeId, 'action' => $action]);
        $outsendStatus = [
            'status' => 0,   // 是否有物理用印/电子合同触发外发
            'flowOutsendTimingArrival' => 0, // 0提交触发 1到达触发  2仅退回触发
            'flowOutsendTimingSubmit' => 0, // 流程提交时外发
            'back' => 1, // 流程提交退回或退回到达时不触发 0触发
        ];
        if ($outsendInfos) {
            foreach ($outsendInfos as $outsendInfo) {
                if ($outsendInfo['action'] == $action){
                    if ($outsendInfo['flowOutsendTimingArrival'] >= 0) { // 0 提交触发 1到达触发 2仅退回触发
                        $outsendStatus['status'] = 1;
                        $outsendStatus['flowOutsendTimingArrival'] = $outsendInfo['flowOutsendTimingArrival'];
                        if ($outsendInfo['flowOutsendTimingArrival'] == 0) {
                            $outsendStatus['flowOutsendTimingSubmit'] = 1;
                        }
                        $outsendStatus['back'] = $outsendInfo['back'];
                    }
                }
            }
        }
        return $outsendStatus;
    }
    /**
     * 同步获取业务分类
     *
     * @param array $userInfo
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function syncCategory($userInfo = [])
    {
        $logData = [
            'operate_action' => 'syncCategory',
            'operate_type' => 'CATEGORY', // 同步业务分类
            'operator' => $userInfo['user_id'] ?? ''
        ];
        $config = app('App\EofficeApp\ElectronicSign\Services\ElectronicSignService')->getServerBaseInfo();
        // 获取所有服务
        if ($config && isset($config['qys_on_off']) && $config['qys_on_off'] == 1) {
            $successServer = $failServer = [];
            $servers = app($this->qiyuesuoServerRepository)->getServersList([]);
            if ($servers) {
                foreach ($servers as $server) {
                    if (!$server['serverUrl'] || !$server['accessKey'] || !$server['accessSecret']) {
                        // 服务相关数据错误
                        $server['error_message'] = 'sync_failed_server_not_found';
                        $failServer[] = $server;
                        continue;
                    }
                    $serverType = $server['serverType'];
                    $qiyuesuoApi = new QiyuesuoOpenApiService($server['serverUrl'], $server['accessKey'], $server['accessSecret'], $server['serverType']);
                    if ($serverType == 'private') {
                        // 初始化契约锁服务
                        // 查询公司列表 按公司循环查询
                        // $categoryResponse = $qiyuesuoApi->sendRequest($this->url['categories'], [], 'GET');
                        $companies = $this->getCompanies($qiyuesuoApi);
                        $categoryResponse = $response = [];
                        if (!empty($companies)) {
                            foreach ($companies as $key => $val) {
                                $result = $this->getCategories($qiyuesuoApi, ['companyName' => $val['name'], 'companyId' => $val['id']]);
                                $response = array_merge($response, $result);
                            }
                        }
                        if ($response) {
                            $categoryResponse = [
                                'qys_code' => 0,
                                'categories' => $response
                            ];
                        }
                    } else {
                        $qiyuesuoPublicServer = new QiyuesuoPublicCloudService();
                        $qiyuesuoPublicServer->qiyuesuoPublicApi = $qiyuesuoApi;
                        $companies = $qiyuesuoPublicServer->getCompanies(true);
                        $categoryResponse = $response = [];
                        foreach ($companies as $key => $val) {
                            $result = $qiyuesuoPublicServer->getList('categories', ['tenantName' => $val['name'], 'tenantId' => $val['id']]);
                            $response = array_merge($response, $result);
                        }
                        if ($response) {
                            $categoryResponse = [
                                'qys_code' => 0,
                                'categories' => $response
                            ];
                        }
                    }
                    $serverId = $server['serverId'];
                    // 解析返回数据 将印章数据存入数据库
                    if (isset($categoryResponse['qys_code']) && !empty($categoryResponse['qys_code'])) {
                        // 请求失败
                        $server['error_message'] = 'sync_failed_request_failed';
                        $failServer[] = $server;
                        continue;
                    } else {
                        // 解析数据
                        $categories = $categoryResponse['categories'] ?? [];
                        $categoryData = [];
                        if ($categories) {
                            foreach ($categories as $category) {
                                $categoryData[] = [
                                    'categoryId' => $category['id'],
                                    'name' => $category['name'],
                                    'tenantId' => $category['tenantId'] ?? '',
                                    'tenantName' => $category['tenantName'] ?? '',
                                    'createTime' => $category['createTime'] ?? '',
                                    'primary' => $category['type'] ?? '',
                                    'state' => $category['status'] ?? '',
                                    'sealId' => $category['sealId'] ?? '',
                                    'type' => $category['type'] ?? '',
                                    'config' => $category['config'] ?? '',
                                    'faceSign' => $category['faceSign'] ?? false,
                                    'serverId' => $serverId
                                ];
                            }

                            // 删除之前的这个契约锁服务插入的印章，重新存入契约锁物理印章表
                            if ($categoryData) {
                                app($this->qiyuesuoCategoryRepository)->deleteByWhere(['serverId' => [$serverId]]);
                                $res = true;
                                foreach (array_chunk($categoryData, 100) as $array) {
                                    $res = app($this->qiyuesuoCategoryRepository)->insertMultipleData($array);
                                }
//                                $res = app($this->qiyuesuoCategoryRepository)->insertMultipleData($categoryData);
                                if ($res) {
                                    $successServer[] = $server;
                                } else {
                                    $server['error_message'] = 'sync_failed_seals_save_error';
                                    $failServer[] = $server;
                                }
                            } else {
                                $server['error_message'] = 'sync_failed_seals_not_found';
                                $failServer[] = $server;
                            }
                        } else {
                            $server['error_message'] = 'sync_failed_seals_not_found';
                            $failServer[] = $server;
                        }
                    }
                }
                $this->syncLogData($successServer, $failServer, $logData, 'category');
            } else {
                // 未查询到契约锁服务，请先配置契约锁服务
                $logData['operate_result'] = 'sync_failed_server_not_found';
                app($this->qiyuesuoSyncRelatedResourceLogRepository)->insertData($logData);
            }
        } else {
            $logData['operate_result'] = 'sync_failed_server_not_open';
            app($this->qiyuesuoSyncRelatedResourceLogRepository)->insertData($logData);
        }
        return true;
    }
    /**
     * 同步模板列表
     *
     * @param array $userInfo
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function syncTemplate($userInfo = [])
    {
        $logData = [
            'operate_action' => 'syncTemplate',
            'operate_type' => 'TEMPLATE', // 同步业务分类
            'operator' => $userInfo['user_id'] ?? ''
        ];
        $config = app('App\EofficeApp\ElectronicSign\Services\ElectronicSignService')->getServerBaseInfo();
        // 获取所有服务
        if ($config && isset($config['qys_on_off']) && $config['qys_on_off'] == 1) {
            $successServer = $failServer = [];
            $servers = app($this->qiyuesuoServerRepository)->getServersList([]);
            if ($servers) {
                foreach ($servers as $server) {
                    if (!$server['serverUrl'] || !$server['accessKey'] || !$server['accessSecret']) {
                        // 服务相关数据错误
                        $server['error_message'] = 'sync_failed_server_not_found';
                        $failServer[] = $server;
                        continue;
                    }
                    // 初始化契约锁服务
                    $serverType = $server['serverType'];
                    $serverId = $server['serverId'];
                    $qiyuesuoApi = new QiyuesuoOpenApiService($server['serverUrl'], $server['accessKey'], $server['accessSecret'], $server['serverType']);
                    $templateResponse = $response = [];
                    if ($serverType == 'private') {
                        // $templateResponse = $qiyuesuoApi->sendRequest($this->url['templates'], [], 'GET');
                        try {
                            $companies = $this->getCompanies($qiyuesuoApi);
                            if (!empty($companies)) {
                                foreach ($companies as $key => $val) {
                                    $result = $this->getTemplates($qiyuesuoApi, ['tenantName' => $val['name'], 'tenantId' => $val['id']]);
                                    $response = array_merge($response, $result);
                                }
                            }
                        } catch (\Exception $exception) {
                            $server['error_message'] = 'sync_failed_server_not_found';
                            $failServer[] = $server;
                            continue;
                        }

                        if ($response) {
                            $templateResponse = [
                                'qys_code' => 0,
                                'result' => $response
                            ];
                        }
                    } else {
                        $qiyuesuoPublicServer = new QiyuesuoPublicCloudService();
                        $qiyuesuoPublicServer->qiyuesuoPublicApi = $qiyuesuoApi;
                        $companies = $qiyuesuoPublicServer->getCompanies(true);
                        foreach ($companies as $key => $val) {
                            $result = $qiyuesuoPublicServer->getList('templates', ['tenantName' => $val['name'], 'tenantId' => $val['id']]);
                            $response = array_merge($response, $result);
                        }
                        if ($response) {
                            $templateResponse = [
                                'qys_code' => 0,
                                'result' => $response
                            ];
                        }
                        // dd($templateResponse);
                    }
                    // 解析返回数据 将印章数据存入数据库
                    if (isset($templateResponse['qys_code']) && !empty($templateResponse['qys_code'])) {
                        // 请求失败
                        $server['error_message'] = 'sync_failed_request_failed';
                        $failServer[] = $server;
                        continue;
                    } else {
                        // 解析数据
                        $templates = $templateResponse['result'] ?? [];
                        $templateData = $templateParamData = [];
                        if ($templates) {
                            foreach ($templates as $template) {
                                $templateData[] = [
                                    'templateId' => $template['id'],
                                    'title' => $template['title'] ?? ($template['name'] ?? ''), // 公有私有接口返回数据格式不一致
                                    'tenantId' => $template['tenantId'] ?? '',
                                    'tenantName' => $template['tenantName'] ?? '',
                                    'createTime' => $template['createTime'] ?? '',
                                    'updateTime' => $template['updateTime'] ?? '',
                                    'fileKey' => $template['fileKey'] ?? '',
                                    'form' => $template['form'] ?? '',
                                    'word' => $template['word'] ?? '',
                                    'pdfKey' => $template['pdfKey'] ?? '',
                                    'status' => $template['status'] ?? '',
                                    'templateType' => $template['templateType'] ?? '',
                                    'type' => $template['type'] ?? false,
                                    'serverId' => $serverId
                                ];
                                // 模板参数的处理
                                $templateParam = $serverType == 'public' ? ($template['parameters'] ?? []) : ($template['params'] ?? []);
                                if ($templateParam) {
                                    foreach ($templateParam as $paramKey => $paramVal) {
                                        $templateParamData[] = [
                                            'paramId' => $paramVal['id'] ?? '',
                                            'templateId' => $template['id'],
                                            'serverId' => $serverId,
                                            'name' => $paramVal['name'] ?? '',
                                            'required' => $paramVal['required'] ?? '',
                                            'type' => $paramVal['type'] ?? '',
                                        ];
                                    }
                                }
                            }
                            // 删除之前的这个契约锁服务插入的模板数据
                            if ($templateData) {
                                // 删除旧模板文件数据
                                app($this->qiyuesuoTemplateRepository)->deleteByWhere(['serverId' => [$serverId]]);
                                // 防止一致性插入太多
                                $res = true;
                                foreach (array_chunk($templateData, 100) as $array) {
                                    $res = app($this->qiyuesuoTemplateRepository)->insertMultipleData($array);
                                }
                                // 删除旧模板文件参数数据并插入新数据 -- 新建数据表
                                if ($res) {
                                    if ($templateParamData) {
                                        app($this->qiyuesuoTemplateParamRepository)->deleteByWhere(['serverId' => [$serverId]]);
                                        foreach (array_chunk($templateParamData, 100)  as $item) {
                                            app($this->qiyuesuoTemplateParamRepository)->insertMultipleData($item);
                                        }
                                    }
                                    $successServer[] = $server;
                                } else {
                                    $server['error_message'] = 'sync_failed_seals_save_error';
                                    $failServer[] = $server;
                                }
                            } else {
                                // 获取印章列表数据为空
                                $server['error_message'] = 'sync_failed_seals_not_found';
                                $failServer[] = $server;
                            }
                        } else {
                            $server['error_message'] = 'sync_failed_seals_not_found';
                            $failServer[] = $server;
                        }
                    }
                }
                $this->syncLogData($successServer, $failServer, $logData, 'template');
            } else {
                $logData['operate_result'] = 'sync_failed_server_not_found';
                app($this->qiyuesuoSyncRelatedResourceLogRepository)->insertData($logData);
            }
        } else {
            $logData['operate_result'] = 'sync_failed_server_not_open';
            app($this->qiyuesuoSyncRelatedResourceLogRepository)->insertData($logData);
        }
        return true;
    }

	public function syncLogData($successServer, $failServer, $logData, $type)
	{
		//记录执行日志和执行时间及执行结果
		if (!$successServer && $failServer) {
			// 全部失败
			$logData['operate_result'] = 'all_failed';
			$logData['attach_information'] = json_encode($failServer);
		} else if (!$failServer && $successServer) {
			// 全部成功
			$logData['operate_result'] = 'all_success';
		} else {
			// 部分成功部分失败
			$logData['operate_result'] = 'success_or_failed';
			$logData['attach_information'] = json_encode($failServer);
		}
		app($this->qiyuesuoSyncRelatedResourceLogRepository)->insertData($logData);
		// 更改这个任务最后执行时间
		app($this->qiyuesuoRelatedResourceTaskRepository)->updateData(['last_time' => date('Y-m-d H:i:s')], ['action' => $type]);
	}
	/**
	 * 下载用印文件
	 *
	 * @param [type] $data
	 * @param [type] $userInfo
	 *
	 * @return void
	 * @author yml
	 * @since
	 */
	public function sealApplyImagesDownload($data, $userInfo)
	{
		$flowId = $data['flowId'] ?? '';
		$runId = $data['runId'] ?? '';
		$this->init($flowId, 'seal_apply');
		$userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
		$applyLog = [
			'flowId' => $flowId,
			'runId' => $runId,
			'userid' => $userId,
			'ipaddress' => getClientIp(),
			'createTime' => date('Y-m-d H:i:s'),
			'requestJson' => json_encode($data),
			'action' => 'seal_apply_images_download',
		];
		if (!$this->qiyuesuoApi) {
			//根据服务配置实例化 契约锁API service失败
			$applyLog['responseJson'] = json_encode(['message' => trans('electronicsign.0x093515')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return ['code' => ['0x093515', 'electronicsign']];
		}
		$success = $failed = [];
		// 获取用印配置 根据物理用印配置解析表单数据
		$sealConfig = app($this->qiyuesuoSealApplySettingRepository)->getSettingByFlowId($flowId);
		if (!$sealConfig) {
			// 获取设置失败
			$applyLog['responseJson'] = json_encode(['message' => trans('electronicsign.seal_apply_docment_get_setting_failed')]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
			return ['code' => ['0x093515', 'electronicsign']];
		}
		$sealConfig = $sealConfig->toArray();
		// 发起申请日志
		$applyLog['settingId'] = $sealConfig['settingId'];
		$applyLog['serverId'] = $sealConfig['serverId'];
		// 用印授权人数组
		// 获取契约锁物理用印业务id  需要去重
		$businessIds = app($this->qiyuesuoSealApplyAuthLogRepository)->getFieldInfo(['runId' => $runId]);
		$attachmentIds = [];
		$businessSaveIds = [];
		foreach ($businessIds as $businessinfo) {
			$businessId = $businessinfo['businessId'] ?? '';
			// 没有业务id或重复 跳过
			if (empty($businessId) || in_array($businessId, $businessIds)) {
				continue;
			}
			$userName = $businessinfo['userName'];
			$contact = $businessinfo['contact'];
			$businessSaveIds[] = $businessId;
			$filename = trans('electronicsign.qys_seal_apply_image') . '-' . $userName . '-' . $contact . '-' . ($businessId ?? time()); //
			// $filename = iconv("UTF-8", "GBK", $filename);
			$suffix = 'zip';
			// 检测这个文件有没有下载过
			$entityId = app($this->attachmentService)->getEntityIdsByAttachmentName($filename . '.' . $suffix, 'qiyuesuo_seal_apply_image');
			if (!$entityId || empty($entityId)) {
				$result = $this->qiyuesuoApi->sendRequest($this->url['seal_apply_images_download'], ['businessId' => $businessId], 'GET');
				if ($result) {
					// 保存本地查看文件有效性 测试使用 之前保存了文件损坏
					// $localPath = storage_path('logs').'/'.'seal/'. $businessId . time() .'.zip';
					// $res = $this->checkDownloadAndReturn($result, $localPath);
					// $content = file_get_contents($localPath);
					$attachmentId = $this->saveFile($filename, $suffix, $result, $userInfo['user_id'], $businessId, $runId, 0, 0, '', 'qiyuesuo_seal_apply_auth_log');
					if ($attachmentId) {
                        $attachmentId = is_array($attachmentId) ? $attachmentId['attachmentId']: $attachmentId;
						$attachmentIds[] = $attachmentId;
						// 存到关联表
						app($this->attachmentRelataionQiyuesuoSealApplyImageRepository)->insertData([
							'run_id' => $runId,
							'entity_id' => $businessinfo['authLogId'],
							'attachment_id' => $attachmentId,
						]);
						$success[] = $businessinfo;
					}
				} else {
					// 请求失败
					$failed[] = $businessinfo;
				}
			} else {
				$success[] = $businessinfo;
			}
		}
		// 日志记录
		if ($success || $failed) {
			$response = '';
			if ($success) {
				$response = trans('electronicsign.seal_apply_download_success');
				foreach ($success as $successVal) {
					$response .= 'businessId:' . $successVal['businessId'] . trans('electronicsign.seal_applyer') . $successVal['userName'] . ' ';
				}
			}
			if ($failed) {
				$response .= trans('electronicsign.seal_apply_download_failed');
				foreach ($failed as $failedVal) {
					$response .= 'businessId:' . $failedVal['businessId'] . trans('electronicsign.seal_applyer') . $failedVal['userName'] . ' ';
				}
				$response .= trans('electronicsign.seal_apply_failed_reason');
			}
			$applyLog['responseJson'] = json_encode(['message' => $response]);
			$this->addLog($this->qiyuesuoSealApplyLogRepository, $applyLog);
		}
		return true;
	}
	/**
	 * 契约锁物理用印后回调用印图片保存
	 *
	 * @param [type] $data
	 * ['businessId'=>'业务Id','mobile'=>'用印人的手机号','imageType'=>'图片类型,FACE（人脸）;SIGNATORY（用印）;EMERGENCY（应急）','images'=>[]]
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function sealApplyCallback($data)
	{
		$businessId = $data['businessId'] ?? '';
		if (!$businessId) {
			// 没有业务id
			return ['code' => '1000001', 'message' => trans('electronicsign.not_found_buiness_id')];
		}
		// 数据库查找相关记录
		$businessinfo = app($this->qiyuesuoSealApplyAuthLogRepository)->getOneFieldInfo(['businessId' => $businessId]);
		if (!$businessinfo || !$businessinfo['runId']) {
			// 未查到相关记录
			return ['code' => '1000001', 'message' => trans('electronicsign.not_found_data')];
		}
		$status = $data['status'] ?? '';
		$runId = $businessinfo->runId;
		$flowId = $businessinfo->flowId;
		$userName = $businessinfo->userName;
		$contact = $businessinfo->contact;
		$userId = 0;
		if ($status) {
			if ($status == 'COMPLETE') {
				$filename = trans('electronicsign.qys_seal_apply_image') . '-' . $userName . '-' . $contact . '-' . ($businessId ?? time());
				$suffix = 'zip';
				// 检测这个文件有没有下载过
				$entityId = app($this->attachmentService)->getEntityIdsByAttachmentName($filename . '.' . $suffix, 'qiyuesuo_seal_apply_image');
				if (!$entityId || empty($entityId)) {
					$this->init($flowId, 'seal_apply');
					$result = $this->qiyuesuoApi->sendRequest($this->url['seal_apply_images_download'], ['businessId' => $businessId], 'GET');
					if ($result) {
						$attachmentId = $this->saveFile($filename, $suffix, $result, $userId, $businessId, $runId, 0, 0, '', 'qiyuesuo_seal_apply_auth_log');
						if ($attachmentId) {
							// 存到关联表
							app($this->attachmentRelataionQiyuesuoSealApplyImageRepository)->insertData([
								'run_id' => $runId,
								'entity_id' => $businessinfo->authLogId,
								'attachment_id' => $attachmentId,
							]);
						}
					}
				}
			}
            // 系统信息通知
            app($this->electronicSignService)->sendMessage([
                'businessId' => $businessId,
                'data' => $data,
                'businessinfo' => $businessinfo
            ], 'seal_apply');
		} else {
			$mobile = $data['mobile'] ?? '';
			$images = $data['images'] ?? [];
			// 根据手机号获取用户id
			$userId = 0;
			if ($businessinfo) {
				$runId = $businessinfo['runId'];
				$attachmentIds = [];
				foreach ($images as $key => $image) {
					$suffix = 'png';
					$filename = trans('electronicsign.qys_seal_photo') . '-' . $userName . '-' . $contact . '-' . $businessId . '-' . $key;
					$attachmentId = $this->saveFile($filename, $suffix, $image, $userId, $businessId, $runId, 0, 0, '', 'qiyuesuo_seal_apply_auth_log');
					if ($attachmentId) {
						$attachmentIds[] = [
							'run_id' => $runId,
							'entity_id' => $businessinfo->authLogId ?? 0,
							'attachment_id' => $attachmentId,
						];
					}
				}
				if ($attachmentIds) {
					app($this->attachmentRelataionQiyuesuoSealApplyImageRepository)->insertMultipleData($attachmentIds);
				}
			}
		}
		return ['code' => 0, 'message' => 'success'];
	}
	/**
	 * 获取表单明细数据字段名
	 *
	 * @param [type] $setting
	 * @param [type] $field
	 *
	 * @return string
	 * @author yml
	 */
	public function checkDetailField($setting, $field)
	{
		$fieldName = $setting[$field] ?? '';
		$detailValue = '';
		if ($fieldName) {
			$fieldValueArray = explode('_', $fieldName);
			if ($fieldValueArray && count($fieldValueArray) > 2) {
				$detailValue = $fieldValueArray[0] . '_' . $fieldValueArray[1];
			}
		}
		return $detailValue;
	}
	/**
	 * 解析一个签署方设置
	 *
	 * @param [type] $signInfoItem
	 * @param [type] $formData
	 * @param [type] $formControlData
	 * @param [type] $tenantType
	 *
	 * @return array
	 * @author yml
	 */
	public function parseOneSignInfo($signInfoItem, $formData, $formControlData, $tenantType)
	{
		// 先确认是否是明细数据 -- 根据签署方名称和联系方式确定
		$detailField = $this->checkDetailField($signInfoItem, 'tenantName');
		$oneSignInfo = [];
		foreach ($signInfoItem as $k => $v) {
			if (in_array($k, ['tenantType', 'tenantName', 'contact', 'type', 'serialNo', 'keyword', 'seal', 'canLpSign', 'seals'])) {
				if (!$detailField) {
					// 非明细数据
					$oneSignInfo[$k] = $this->parseOneSignInfoItem($k, $v, $formData, $formControlData, $signInfoItem, $tenantType, '', -1);
				} else {
					// 明细数据  -- 根据签署方类型处理数据
					$detail = $formData[$detailField] ?? [];
					$detailCount = count($detail);
					if ($detailCount) {
						for ($i = 0; $i < $detailCount; $i++) {
							$oneSignInfo[$i][$k] = $this->parseOneSignInfoItem($k, $v, $formData, $formControlData, $signInfoItem, $tenantType, $detailField, $i);
						}
					}
				}
			}
		}
		if (!$detailField) {
			$oneSignInfo = [$oneSignInfo];
		}
		if ($oneSignInfo) {
			// 平台方 将签署人联系方式加入签署人数组 --- 需要考虑不同签署动作
			if ($tenantType == 'CORPORATE') {
				// $contacts = [];
				// foreach($oneSignInfo as $infoKey => $infoVal) {
				//     foreach ($infoVal['contact'] as $oneContact) {
				//         if (!in_array($oneContact, $contacts)) {
				//             $contacts[] = $oneContact;
				//         }
				//     }
				// }
				// $oneSignInfo = $oneSignInfo[0];
				// $oneSignInfo['contact'] = $contacts;
			} elseif ($tenantType == 'COMPANY') {
				// 企业方  同一企业的放置到那个企业的签署人
				$contacts = [];
				foreach ($oneSignInfo as $infoKey => $infoVal) {
					$tenantName = isset($infoVal['tenantName']) ? (is_array($infoVal['tenantName']) ? $infoVal['tenantName'][0] : $infoVal['tenantName']) : '';
					if (!isset($contacts[$tenantName])) {
						$contacts[$tenantName] = $infoVal['contact'];
					} else {
						foreach ($infoVal['contact'] as $oneContact) {
							if (!in_array($oneContact, $contacts[$tenantName])) {
								// $contacts[$tenantName] = array_merge($contacts[$tenantName], $infoVal['contact']);
								$contacts[$tenantName][] = $oneContact;
							}
						}
						unset($oneSignInfo[$infoKey]);
					}
				}
				foreach ($oneSignInfo as $infoKey => $infoVal) {
					$tenantName = isset($infoVal['tenantName']) ? (is_array($infoVal['tenantName']) ? $infoVal['tenantName'][0] : $infoVal['tenantName']) : '';
					$oneSignInfo[$infoKey]['tenantName'] = $tenantName;
					$oneSignInfo[$infoKey]['contact'] = $contacts[$tenantName];
				}
			} elseif ($tenantType == 'PERSONAL') {
				// 考虑人员选择器 名称联系方式是人员选择器是一对一获取，去除重复的联系方式
				$contacts = [];
				$signInfos = [];
				foreach ($oneSignInfo as $infoKey => $infoVal) {
					if (is_array($infoVal['tenantName'])) {
						foreach ($infoVal['tenantName'] as $tenantKey => $tenantVal) {
							$tenantName = $tenantVal ?? '';
							$contact = $infoVal['contact'][$tenantKey] ?? '';
							if ($tenantName && $contact && !in_array($contact, $contacts)) {
								$contacts[] = $contact;
								$signInfos[] = [
									'tenantType' => $infoVal['tenantType'],
									'tenantName' => $tenantName,
									'contact' => $contact,
									'type' => $infoVal['type'],
									'serialNo' => $infoVal['serialNo'],
									'keyword' => $infoVal['keyword'],
									'seal' => $infoVal['seal'],
								];
							}
						}
						$oneSignInfo = $signInfos;
					} else {
						// 名称不是人员选择器 去除重复的联系方式
						if (is_array($infoVal['contact'])) {
							foreach ($infoVal['contact'] as $oneContact) {
								if (in_array($oneContact, $contacts)) {
									unset($oneSignInfo[$infoKey]);
									break;
								} else {
                                    					$contacts[] = trim($oneContact);
								}
							}
						}
					}
				}
			}
		}
		return $oneSignInfo;
	}
	/**
	 * 解析签署方设置的各个字段值 -- 考虑明细
	 *
	 * @param [type] $k
	 * @param [type] $v
	 * @param [type] $formData
	 * @param [type] $formControlData
	 * @param [type] $signInfoItem
	 * @param [type] $tenantType
	 * @param [type] $detailField
	 * @param [type] $i
	 *
	 * @return string
	 * @author yml
	 */
	public function parseOneSignInfoItem($k, $v, $formData, $formControlData, $signInfoItem, $tenantType, $detailField, $i)
	{
		$oneSignInfoItem = '';
		switch ($k) {
			case 'tenantType':
				$oneSignInfoItem = $tenantType;
				break;
			case 'type':
			case 'serialNo':
				$oneSignInfoItem = $v;
				break;
			case 'contact':
				// 联系方式字段支持文本及人员选择器
				$oneSignInfoItem = $this->parseFlowDataOutSend($formData, $formControlData, 'contact', $signInfoItem, $i);
				break;
			case 'tenantName':
				// 签署方个人支持用户选择器
				if ($tenantType == 'PERSONAL') {
					$oneSignInfoItem = $this->parseFlowDataOutSend($formData, $formControlData, 'tenantName', $signInfoItem, $i);
				} else {
					$oneSignInfoItem = $i >= 0 ? ($formData[$detailField][$i][$v] ?? '') : ($formData[$v] ?? '');
				}
				break;
            case 'seals':
                $oneSignInfoItem = $i >= 0 ? ($formData[$detailField][$i][$v] ?? []) : ($formData[$v] ?? []);
                if (!is_array($oneSignInfoItem) && !empty($oneSignInfoItem)) {
                    $oneSignInfoItem = $oneSignInfoItem && strpos($oneSignInfoItem,',') !== false ? explode(',', $oneSignInfoItem) : [$oneSignInfoItem];
                }
                break;
			default:
				$oneSignInfoItem = $i >= 0 ? ($formData[$detailField][$i][$v] ?? '') : ($formData[$v] ?? '');
		}
		return $oneSignInfoItem;
	}
	/**
	 * 解析表单数据
	 */
	public function parseTemplateParam($formData, $formControlData)
	{
		$data = [];
		$formControlData = array_column($formControlData, 'control_title', 'control_id');
		foreach ($formData as $key => $value) {
			$controlKey = $formControlData[$key] ?? '';
			if ($controlKey) {
				$data[$controlKey] = $value;
			}
		}
		return $data;
	}
	/**
	 * 通过获取业务分类检测契约锁服务
	 *
	 * @return void
	 * @author yml
	 */
	public function checkServer()
	{
		return $this->qiyuesuoApi->sendRequest($this->url['categories'], [], 'GET');
	}
	/**
	 * 公章签署
	 */
	public function signByCompany($data)
	{
		return $this->qiyuesuoApi->sendRequest($this->url['signbycompany'], $data);
	}
	/**
	 * 检测合同状态  --  草稿时发起合同
	 *
	 * @param [type] $flowId
	 * @param [type] $contractId
	 *
	 * @return void
	 * @author yml
	 */
	public function checkContractStatus($flowId, $contractId)
	{
		$contract = $this->getContract($flowId, ['contractId' => $contractId]);
		if (isset($contract['contract']) && isset($contract['contract']['status']) && $contract['contract']['status'] == 'DRAFT') {
			$sendRes = $this->sendContract($flowId, $contractId);
			if (!isset($sendRes['code'])) {
				// 更改本地合同状态 为签署中
				app($this->flowRunRelationQysContractInfoRepository)->updateData(['contractStatus' => 'SIGNING'], ['contractId' => $contractId]);
			} else {
				return $sendRes;
			}
		}
		return true;
	}
	/**
	 * 检测模板所需参数必填的是否都已填
	 *
	 * @param [type] $documentId
	 * @param [type] $templateParams
	 * @param [type] $serverId
	 *
	 * @return void
	 * @author yml
	 */
	public function checkTemplateParamRequired($templateId, $templateParams, $serverId)
	{
		$paramKeys = app($this->qiyuesuoTemplateParamRepository)->getFieldInfo(['templateId' => $templateId, 'serverId' => $serverId, 'required' => 1]);
		if ($paramKeys) {
			$requireParamKey = [];
			foreach ($paramKeys as $paramKey) {
				$param = $templateParams[$paramKey['name']] ?? '';
				if (!$param) {
					$requireParamKey[] = $paramKey['name'];
				}
			}
			if ($requireParamKey) {
				return ['code' => ['', trans('electronicsign.template_required_param_not_found') . implode(' ', $requireParamKey)]];
			}
		}
		return true;
	}
	/**
	 * 合同删除/作废/撤回操作分发
	 *
	 * @param [type] $contractId
	 * @param [type] $type
	 *
	 * @return void
	 * @author yml
	 */
	public function invalidContract($contractId, $type)
	{
		switch ($type) {
			case 'delete': // 删除
				return $this->deleteContract($contractId);
				break;
			case 'cancel': // 作废
				return $this->cancelContract($contractId);
				break;
			case 'recall': // 撤回
				return $this->recallContract($contractId);
				break;
			default:
				// 未知的合同操作类型
				return ['code' => ['', '']];
		}
	}
	/**
	 * 草稿合同删除
	 *
	 * @param [type] $contractId
	 *
	 * @return void
	 * @author yml
	 */
	public function deleteContract($contractId)
	{
		$data = ['contractId' => $contractId];
		$contract = $this->getContract(0, $data);
		$contract = $contract['contract'] ?? [];
		if (!$contract) {
			// 未找到合同
			return ['code' => ['contract_not_found', 'electronicsign']];
		}
		$contractStatus = $contract['status'] ?? '';
		if (!$contractStatus || !in_array($contractStatus, ['DRAFT', 'EXPIRED', 'RECALLED', 'REJECTED'])) {
			// 合同状态不是“草稿”、“已过期”、“已撤回”、“已退回”，不能进行删除操作
			return ['code' => ['contract_not_draft_no_delete', 'electronicsign']];
		}
		// 调用删除接口
		return $this->qiyuesuoApi->sendRequest($this->url['delete_contract'], $data);
	}
	/**
	 * 签署中合同撤回
	 *
	 * @param [type] $contractId
	 *
	 * @return void
	 * @author yml
	 */
	public function recallContract($contractId)
	{
		$data = ['contractId' => $contractId];
		$contract = $this->getContract(0, $data);
		$contract = $contract['contract'] ?? [];
		if (!$contract) {
			// 未找到合同
			return ['code' => ['contract_not_found', 'electronicsign']];
		}
		$contractStatus = $contract['status'] ?? '';
		if (!$contractStatus || ($contractStatus != 'SIGNING' && $contractStatus != 'FILLING')) {
			// 合同状态不是签署中或者拟定过，不能进行撤回操作
			return ['code' => ['contract_not_signing_no_recall', 'electronicsign']];
		}
		// 调用撤回接口
		return $this->qiyuesuoApi->sendRequest($this->url['recall_contract'], $data);
	}
	/**
	 * 已完成合同作废
	 *
	 * @param [type] $contractId
	 *
	 * @return void
	 * @author yml
	 */
	public function cancelContract($contractId)
	{
		$data = ['contractId' => $contractId];
		$contract = $this->getContract(0, $data);
		$contract = $contract['contract'] ?? [];
		if (!$contract) {
			// 未找到合同
			return ['code' => ['contract_not_found', 'electronicsign']];
		}
		$contractStatus = $contract['status'] ?? '';
		if (!$contractStatus || $contractStatus != 'COMPLETE') {
			// 合同状态不是已完成，不能进行作废操作
			return ['code' => ['contract_not_complete_no_cancel', 'electronicsign']];
		}
		$signInfo = $this->getCancelSealId($contractId);
		$sealId = $signInfo['seal'] ?? '';
		if ($sealId) {
			$data['sealId'] = $sealId;
		} else {
			// // 私有系统作废合同需要设置签署作废文件的公章
			// return ['code' => ['contract_not_complete_no_cancel', 'electronicsign']];
		}
		$tenantName = $contract['tenantName'];
		if ($tenantName) {
			$data['tenantName'] = $tenantName;
		}
		// 调用作废接口
		return $this->qiyuesuoApi->sendRequest($this->url['cancel_contract'], $data);
	}

    public function getCancelSealId($contractId)
    {
        $signInfo = app($this->flowRunRelationQysContractSignInfoRepository)->getOneFieldInfo(['tenantType' => 'CORPORATE','type' => 'CORPORATE', 'contractId' => $contractId]);
        return $signInfo ? $signInfo->toArray() : [];
    }
    /**
     * 检测合同状态是否需要重新创建 -- 草稿状态下  判断合同文件及合同模板是否更改，更改的话先创建新合同在删除旧合同；撤回/作废的先创建新合同在删除旧合同
     * @param  [type] $contractId [description]
     * @param  [type] $flowInfo   [description]
     * @param  [type] $userInfo   [description]
     * @return [type]             [description]
     */
    public function checkRecreated($contractId, $flowInfo, $userInfo)
    {
        $runId = $flowInfo['runId'] ?? 0;
        $flowId = $flowInfo['flowId'] ?? 0;
        $contract = $this->getContract($flowId, ['contractId' => $contractId]);
		$contract = $contract['contract'] ?? [];
        $contractStatus = $contract['status'] ?? '';
        $formId = $flowInfo['formId'] ?? 0;
        $formData = $flowInfo['formData'] ?? [];
        if ($contract) {
            if ($contractStatus == 'DRAFT') {
                // 草稿状态下  判断合同文件及合同模板是否更改，更改的话先创建新合同在删除旧合同
                $setting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId)->toArray();
                $documentField = $setting['documentField'] ?? '';
                $templateField = $setting['templateField'] ?? '';
                $documentNewValue = $documentField ? ($formData[$documentField] ?? '') : '';
                $templateNewValue = $templateField ? ($formData[$templateField] ?? '') : '';
                $contractDocuments = app($this->flowRunRelationQysContractRepository)->getFieldInfo(['runId' => $runId, 'contractId' => $contractId]);
                $documentOldValue = [];
                $templateOldValue = '';
                foreach ($contractDocuments as $key => $document) {
                    if ($document['attachmentId']) {
                        $documentOldValue[] = $document['attachmentId'];
                    }
                    if ($document['templateId']) {
                        $templateOldValue = $document['templateId'];
                    }
                }
                if (array_diff($documentNewValue, $documentOldValue) != [] || $templateNewValue != $templateOldValue) {
                    $recreateFlag = true;
                } else {
                    return ['contractId' => $contractId, 'contractStatus' => $contractStatus];
                }
                // 可重新创建合同的状态：已撤回 作废中 已作废 已删除 已过期 已退回 20210303先排除作废中
            } elseif (in_array($contractStatus, ['RECALLED', 'TERMINATED', 'DELETE', 'EXPIRED', 'REJECTED'])) { // 'TERMINATING',
                // 撤回/作废的 先创建新合同在删除旧合同
                $recreateFlag = true;
            } else {
                // 其他状态不处理
                return ['contractId' => $contractId, 'contractStatus' => $contractStatus];
            }
        } else { // 查不到对应合同信息 重新发起合同
            $recreateFlag = true;
        }
        if ($recreateFlag) {
            $data = [
                'flowInfo' => [
                    'runId' => $runId,
                    'flowId' => $flowId,
                    'formId' => $formId,
                ],
                'formData' => $formData
            ];
            $newContractRes = $this->createContractV1($data, $userInfo);
            $newContractId = $newContractRes['contractId'] ?? '';
            if ($newContractId) {
                // 删除旧合同
                $res = app($this->flowRunRelationQysContractRepository)->deleteByWhere(['runId' => [$runId], 'contractId' => [$contractId]]);
                return ['contractId' => $newContractId, 'contractStatus' => $contractStatus];;
            } else {
                return $newContractRes;
            }
        }
    }
    /**
     * [changeSealApplyStatus 物理用印回调下载用印图片]
     * @param  [type] $data     [description]
     * @param  [type] $userInfo [description]
     * @return [type]           [description]
     */
    public function changeSealApplyStatus($data, $userInfo)
    {
        $businessId = $data['businessId'] ?? '';
        if (!$businessId) {
            // 没有业务id
            return false;
        }
        // 数据库查找相关记录
        $businessinfo = app($this->qiyuesuoSealApplyAuthLogRepository)->getOneFieldInfo(['businessId' => $businessId]);
        if (!$businessinfo || !$businessinfo['runId']) {
            // 未查到相关记录
            return false;
        }
        $status = $data['status'] ?? '';
        $runId = $businessinfo['runId'];
        if ($status) {
            // 用印完成回调
            if ($status == 'COMPLETE') {
                $filename = trans('electronicsign.qys_seal_apply_image') .'-' . ($businessId ?? time()); //
                $suffix = 'zip';
                $result = $this->qiyuesuoApi->sendRequest($this->url['seal_apply_images_download'], ['businessId' => $businessId], 'GET');
                if ($result) {
                    $attachmentId = $this->saveFile($filename, $suffix, $result, $userInfo['user_id'], $businessId, $runId, 0, 0, '', 'qiyuesuo_seal_apply_auth_log');
                    if ($attachmentId) {
                        // 存到关联表
                        app($this->attachmentRelataionQiyuesuoSealApplyImageRepository)->insertData([
                            'run_id' => $runId,
                            'entity_id' => $businessinfo['authLogId'],
                            'attachment_id' => $attachmentId
                        ]);
                    }
                }
            }
        } else {
            // 用印图片回调事件
            // 下载图片
            $filename = trans('electronicsign.qys_seal_apply_image') .'-' . ($businessId ?? time()); //
            // $filename = iconv("UTF-8", "GBK", $filename);
            $images = $data['images'] ?? [];
            if ($images) {
                $suffix = 'png';
                foreach ($images as $key => $image) {
                    // 保存本地查看文件有效性 测试使用 之前保存了文件损坏
                    // $localPath = storage_path('logs').'/'.'seal/'. $businessId . time() . $suffix;
                    // $res = $this->checkDownloadAndReturn($image, $localPath);
                    // $content = file_get_contents($localPath);
                   $attachmentId = $this->saveFile($filename, $suffix, $image, $userInfo['user_id'], $businessId, $runId, 0, 0, '', 'qiyuesuo_seal_apply_auth_log');
                   if ($attachmentId) {
                        $attachmentIds[] = $attachmentId;
                    }
                }
                // 存到关联表
                if ($attachmentIds) {
                    $insertData = [];
                    foreach ($attachmentIds as $key => $value) {
                        $insertData [] = [
                            'run_id' => $runId,
                            'entity_id' => $businessinfo['authLogId'],
                            'attachment_id' => $attachmentId
                        ];
                    }
                    app($this->attachmentRelataionQiyuesuoSealApplyImageRepository)->insertMultipleData($insertData);
                }
            }
        }

        // 系统信息通知
        app($this->electronicSignService)->sendMessage($businessId, $status, $data['type'], 'seal_apply');

        return true;
    }

    /** 未设置单点登录加密秘钥的获取签署页面
     * @param $data ['runId' => '', 'contractId' => '', 'platform' => '', 'userId' => '']
     * @return array/string
     */
    public function getSignUrlWithoutEncryptKey($data)
    {
        $runId = $data['runId'] ?? '';
        $contractId = $data['contractId'] ?? '';
        $userId = $data['userId'] ?? '';
        $flowId = $data['flowId'] ?? '';
        // 无单点登录加密秘钥的获取对应签署页面 --- 需要当前用户是签署方用户才行
        $signInfos = app($this->flowRunRelationQysContractSignInfoRepository)->getByRunId($runId);
        if ($signInfos) {
            $signInfos = $signInfos->toArray();
            $userInfo = app($this->userService)->getUserAllData($userId);
            if (!empty($userInfo)) {
                $userInfo = $userInfo->toArray();
                $userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
                $phoneNumber = isset($userOneInfo['phone_number']) ? $userOneInfo['phone_number'] : '';
                if (!$phoneNumber) {
                    // 获取用户手机号码失败，无法进行签署
                    return ['code' => ['0x093520', 'electronicsign']];
                }
                // 遍历签署方信息 匹配的话拼接开放接口所需数据
                $param = [];
                foreach ($signInfos as $signInfo) {
                    if ($signInfo['contact'] == $phoneNumber) {
                        $param['contractId'] = $contractId;
                        $param['tenantName'] = $signInfo['tenantName'];
                        $param['tenantType'] = $signInfo['tenantType'];
                        $param['contact'] = $signInfo['contact'];
                        break;
                    } else{
                        if (!$signInfo['contact']) {
                            $param['contractId'] = $contractId;
                            $param['tenantName'] = $signInfo['tenantName'];
                            $param['tenantType'] = $signInfo['tenantType'];
                        }
                    }
                }
                if (!$param) {
                    return ['code' => ['user_not_signatory', 'electronicsign']];
                }
                if ($flowId) {
                    $this->init($flowId);
                }
                if (!$this->qiyuesuoApi) {
                    //根据服务配置实例化 契约锁API service失败
                    return ['code' => ['0x093515', 'electronicsign']];
                }
                $signUrl = $this->qiyuesuoApi->sendRequest($this->url['sign'], $param);
                return $signUrl['signUrl'] ?? $signUrl;
            } else {
                // 获取用户信息失败，无法进行签署
                return ['code' => ['0x093521', 'electronicsign']];
            }
        }
    }

    private function getCompanies($qiyuesuoApi)
    {
        $companies = $qiyuesuoApi->sendRequest($this->url['companies'], [], 'GET');
        if (isset($companies['qys_code']) && !empty($companies['qys_code'])) {
            return [];
        }
        return $companies['result'] ?? [];
    }

    private function getCategories($qiyuesuoApi, $params)
    {
        $result = $qiyuesuoApi->sendRequest($this->url['categories'], $params, 'GET');
        if (isset($result['qys_code']) && !empty($result['qys_code'])) {
            return [];
        }
        $categories = $result['categories'] ?? [];
        foreach($categories as $key => $categoryValue) {
            $categories[$key]['tenantId'] = $params['companyId'] ?? '';
            $categories[$key]['tenantName'] = $params['companyName'] ?? '';   
        }
        return $categories;
    }

    private function getTemplates($qiyuesuoApi, $params)
    {
        $result = $qiyuesuoApi->sendRequest($this->url['templates'], $params, 'GET');
        if (isset($result['qys_code']) && !empty($result['qys_code'])) {
            return [];
        }
        $templates = $result['result'] ?? [];
        foreach($templates as $key => $value) {
            $templates[$key]['tenantId'] = $params['tenantId'] ?? '';
            $templates[$key]['tenantName'] = $params['tenantName'] ?? '';   
        }
        return $templates;
    }

    private function getSeals($qiyuesuoApi, $params)
    {
        $result = $qiyuesuoApi->sendRequest($this->url['inner_seals'], $params, 'GET');
        if (isset($result['qys_code']) && !empty($result['qys_code'])) {
            return [];
        }
        $seals = $result['result'] ?? [];
        return $seals;
	}
	public function serverSignatures($params, $serverId)
    {
        // 获取服务签章所选的服务id，根据ID查询服务，初始化服务
        $server = app($this->qiyuesuoServerRepository)->getDetail($serverId);
        if (!$server) {
            return false;
        }
        $service = $this->initByServer($server);
        $url = $service->qiyuesuoApi->sendRequest($this->url['my_signatures'], $params);
        return $url;
    }

    public function getCompany($params)
    {
		$serverId = isset($params['serverId']) ? $params['serverId'] : 0;
        unset($params['serverId']);
        // 获取服务签章所选的服务id，根据ID查询服务，初始化服务
        $server = app($this->qiyuesuoServerRepository)->getDetail($serverId);
        if (!$server) {
            return false;
		}
        $service = $this->initByServer($server);
        // tenantType    TenantType    否    租户类型:CORPORATE(平台方),COMPANY(平台外部企业),INNER_COMPANY(内部企业)，默认获取全部企业
        $company = $service->qiyuesuoApi->sendRequest($this->url['company_list'], $params, 'GET');
        if (isset($company['qys_code']) && $company['qys_code'] == 0){
            $companyInfo = isset($company['result']) &&  isset($company['result'][0]) ? $company['result'][0] : [];
            return $companyInfo;
        } else {
            return $company;
        }
	}
	/**
	 * 根据业务id获取签章信息详情
	 *
	 * @param [type] $params
	 * @param [type] $serverId
	 *
	 * @return void
	 * @author yml
	 */
	public function getCertDetail($params, $serverId)
	{
		if ($detail = Cache::get('qiyuesuo_signature_detail_'.$params['bizId'])) {
			return $detail;
		} else {
			// 获取服务签章所选的服务id，根据ID查询服务，初始化服务
			$server = app($this->qiyuesuoServerRepository)->getDetail($serverId);
			if (!$server) {
				return false;
			}
			$service = $this->initByServer($server);
			$data = $service->qiyuesuoApi->sendRequest($this->url['signature_detail'], $params, 'GET');
			Cache::put('qiyuesuo_signature_detail_'.$params['bizId'], $data, 24 * 60 * 60);
			return $data;

		}
	}
}
