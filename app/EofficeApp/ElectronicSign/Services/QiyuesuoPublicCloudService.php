<?php
namespace App\EofficeApp\ElectronicSign\Services;

use App\EofficeApp\Base\BaseService;

/**
 * 契约锁内部接口 service
 */
class QiyuesuoPublicCloudService extends BaseService
{
    public $qiyuesuoPublicApi = null;

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
        $this->qiyuesuoTemplateParamRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoTemplateParamRepository';
        // 物理用印用印文件关联附件
        $this->attachmentRelataionQiyuesuoSealApplyImageRepository = 'App\EofficeApp\ElectronicSign\Repositories\AttachmentRelataionQiyuesuoSealApplyImageRepository';

        $this->qiyuesuoService = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoService';
        // 契约锁电子合同相关日志
        $this->qiyuesuoContractLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoContractLogRepository';
    }

    /**
     * 需要用到的接口地址
     *
     * @var array
     */
    private $url = [
        'createbyfile' => '/v2/document/addbyfile', //通过上传的文件创建合同文档
        'createbytemplate' => '/v2/document/addbytemplate', //用文件模板创建合同文档
        'create' => '/v2/contract/draft', // 创建合同草稿
        'send_contract' => '/v2/contract/send',
        'presign' => '/v2/contract/appointurl', //获取预签署页面（指定签署位置）
        'signurl_send' => '/v2/contract/pageurl', //接收人通过手机访问签署链接完成签署 永久有效
        'createRetainParams' => '/contract/createRetainParams', //编辑合同(保留模版参数)
        'contract_detail' => '/v2/contract/detail', //合同详情
        'view' => '/v2/contract/viewurl', // 获取合同浏览页面
        'download' => '/v2/contract/download', // 合同下载
        'sign' => '/v2/contract/pageurl', // 获取签署页面地址
        'categories' => '/v2/category/list', //查询业务分类
        'seals' => '/v2/seal/list', // 印章列表
        'templates' => '/v2/template/list', // 模板列表
        'company' => '/v2/company/list', // 子公司列表
        'signbycompany' => '/v2/contract/companysign', // 公章签署
        'signbylegalperson' => '/v2/contract/legalpersonsign', // 法人签署
        'invalid' => '/v2/contract/invalid', // 撤回/作废/删除合同
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
            if ($type && $type == 'seal_apply') {
                $server = app($this->qiyuesuoServerRepository)->getSealApplyServerByFlowId($flowId);
            } else {
                $server = app($this->qiyuesuoServerRepository)->getServerByFlowId($flowId);
            }
        }
        if ($server) {
            $this->qiyuesuoPublicApi = new QiyuesuoOpenApiService($server->serverUrl, $server->accessKey, $server->accessSecret, $server->serverType);
        }
        return $this;
    }

    public function initByServer($server)
    {
        if (is_object($server)) {
            $this->qiyuesuoPublicApi = new QiyuesuoOpenApiService($server->serverUrl, $server->accessKey, $server->accessSecret, $server->serverType);
        } else {
            $this->qiyuesuoPublicApi = new QiyuesuoOpenApiService($server['serverUrl'], $server['accessKey'], $server['accessSecret'], $server['serverType']);
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
        if (!$this->qiyuesuoPublicApi) {
            return ['code' => ['0x093515', 'electronicsign']];
        }
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
            case 'template':
                $url = $this->url['createbytemplate'];
                $params['templateId'] = isset($data['content']) ? $data['content'] : '';
                $params['params'] = isset($data['params']) ? $data['params'] : [];
                break;
            default:
                return ['qys_code' => 1001, 'message' => '请选择生成文档的方式！'];
        }
        return $this->qiyuesuoPublicApi->createDocument($url, $params);
    }

    /**
     * 上传文件生成文档
     *
     * @param [type] $data ['file'=>'上传文件','title'=>'合同文档名']
     * @return array
     */
    public function createDocumentByFile($data)
    {
        if (!$this->qiyuesuoPublicApi) {
            return ['code' => ['0x093515', 'electronicsign']];
        }
        $url = $this->url['createbyfile'];
        //验证数据  上传文件格式  文件大小filesize()
        $ext = strtolower(strrchr($data['file'], '.'));
        if (!$ext || !in_array(substr($ext, 1), ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif', 'tif', 'xls', 'xlsx'])) {
            return ['code' => ['0x093539', 'electronicsign']];
        }
        if (!file_exists($data['file'])) {
            return ['code' => ['0x093540', 'electronicsign']];
        }
        // 商务反馈说会有文件超过10兆，现将限制移除  -20200430
        // 20200508 跟契约锁沟通他们也是有文件大小限制，测试过程中由于文件超过20兆导致契约锁服务直接宕机了，所以判断还是需要的
        if (filesize($data['file']) > 10 * 1024 * 1024) {
            return ['code' => ['0x093536', 'electronicsign']];
        }
        $file = curl_file_create(realpath($data['file']));
        $params = [
            'title' => $data['title'], //合同标题
            'file' => $file,
            'contractId' => $data['contractId'],
            'fileSuffix' => substr($ext, 1),
        ];
        return $this->qiyuesuoPublicApi->service($url, $params);
    }

    public function createDocumentByTemplate($data)
    {
        if (!$this->qiyuesuoPublicApi) {
            return ['code' => ['0x093515', 'electronicsign']];
        }
        $url = $this->url['createbytemplate'];

        return $this->qiyuesuoPublicApi->sendRequest($url, $data);
    }

    /** --> 解析合同基本信息、签署方信息 --> 生成合同 --> 添加合同文档
     * @param $data {
     *     controlId: "DATA_..."
     *     flowInfo: {}
     *     formData: {}
     * }
     * @return array
     */
    public function createContractV1($data, $userInfo)
    {
        // 验证数据及契约锁服务是否开启提至上层
        $userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $flowInfo = isset($data['flowInfo']) ? $data['flowInfo'] : [];
        $runId = isset($flowInfo['runId']) ? $flowInfo['runId'] : '';
        $flowId = isset($flowInfo['flowId']) ? $flowInfo['flowId'] : '';
        $formId = $flowInfo['formId'] ?? 0;
        $formControlData = app($this->flowFormService)->getParseForm($formId);
        $formControlData = array_column($formControlData, null, 'control_id');
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
        $this->init($flowId);
        if (!$this->qiyuesuoPublicApi) {
            //提示：契约锁开放apiservice实例不存在
            $log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093515')]);
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
            return ['code' => ['0x093515', 'electronicsign']];
        }
        // if (app($this->electronicSignService)->getContractIdbyFlowRunId($runId)) {
        //     //提示：根据运行流程id获取到合同id
        //     $log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093518')]);
        //     app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
        //     return ['code' => ['0x093518', 'electronicsign']];
        // }
        // 传过来的表单数据
        $formData = isset($data['formData']) ? $data['formData'] : [];
        // 签署按钮所在附件控件的id
        $controlId = isset($data['controlId']) ? $data['controlId'] : '';
        $send = $setting['saveLaunch'] ? true : false;
        // 解析表单&设置，准备创建合同的数据
        $contractparams = $this->parseContractParams($flowInfo, $formData, $userId, $setting, $formControlData);
        if (isset($contractparams['code'])) {
            $codeKey = $contractparams['code'][0] ?? '';
            $codeVal = $contractparams['code'][1] ?? '';
            $message = $codeKey && $codeVal ?  trans('electronicsign.'. $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_get_params_failed'));
            $log['responseJson'] = json_encode(['message' => $message]);
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
            return $contractparams;
        }
        $createUrl = $this->url['create'];
        // 是否立即发起合同 默认为true
        $contractparams['send'] = false;
        // 合同创建人在OA的ID 考虑到获取人员卡片时使用 发起合同时不能有此参数
        $creatorId = $contractparams['creatorId'] ?? '';
        unset($contractparams['creatorId']);
        // 调openapi生成合同
        $contract = $this->qiyuesuoPublicApi->createContract($createUrl, $contractparams);
        if (!isset($contract['code'])) {
            $contract = isset($contract['result']) ? $contract['result'] : [];
            $contractId = isset($contract) && isset($contract['id']) ? $contract['id'] : 0;
            //填充合同相关信息用于流程契约锁签署信息展示
            $contract['contractId'] = $log['contractId'] = $contractparams['contractId'] = $contractId;
            // 添加合同文档
            $documents = $this->addDocuments($setting, $formData, $runId, $contractId, $formControlData);
            if (isset($documents['code'])) {
                $codeKey = $documents['code'][0] ?? '';
                $codeVal = $documents['code'][1] ?? '';
                $message = $codeKey && $codeVal ?  trans('electronicsign.'. $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_create_document_failed'));
                $log['responseJson'] = json_encode(['message' => $message]);
                app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                return $documents;
            }
            if (isset($contract['signatories']) && !empty($contract['signatories'])) {
                // 如果签署信息配置了关键字 发起合同需根据关键字指定签署位置 同时需要创建合同时返回的签署方标识id和签署动作id，这里需要合并创建合同返回的签署方信息和根据配置产生的签署方信息的关键字和运营企业方【平台方】的签章id   默认第一个关键字位置
                $keywords = [];
                $seals = [];
                $canLpSigns = [];
                foreach ($contractparams['signatories'] as $signVal) {
                    if ($signVal['tenantType'] == 'COMPANY') {
                        $actions = $signVal['actions'] ?? [];
                        foreach ($actions as $action) {
                            $seriaNo = $action['serialNo'];
                            if (isset($action['keyword']) && $action['keyword']) {
                                $keywords[$signVal['tenantName']][$seriaNo] = $action['keyword'];
                            }
                            if (isset($action['seal']) && $action['seal']) {
                                $seals[$signVal['tenantName']][$seriaNo] = $action['seal'];
                            }
                            if (isset($action['canLpSign']) && $action['canLpSign']) {
                                $canLpSigns[$signVal['tenantName']][$seriaNo] = $action['canLpSign'];
                            }
                        }
                    } else {
                        if (isset($action['keyword']) && $signVal['keyword']) {
                            $keywords[$signVal['tenantName']] = $signVal['keyword'];
                        }
                    }
                }
                foreach ($contract['signatories'] as $contractSignKey => $contractSignVal) {
                    if ($contractSignVal['tenantType'] == 'COMPANY') {
                        $actions = $contractSignVal['actions'] ?? [];
                        foreach ($actions as $actionkey => $actionVal) {
                            $contract['signatories'][$contractSignKey]['actions'][$actionkey]['keyword'] = isset($keywords[$contractSignVal['tenantName']]) && isset($keywords[$contractSignVal['tenantName']][$actionVal['serialNo']]) ? $keywords[$contractSignVal['tenantName']][$actionVal['serialNo']] : '';
                            $contract['signatories'][$contractSignKey]['actions'][$actionkey]['seal'] = isset($seals[$contractSignVal['tenantName']]) && isset($seals[$contractSignVal['tenantName']][$actionVal['serialNo']]) ? $seals[$contractSignVal['tenantName']][$actionVal['serialNo']] : '';
                            $contract['signatories'][$contractSignKey]['actions'][$actionkey]['canLpSign'] = isset($canLpSigns[$contractSignVal['tenantName']]) && isset($canLpSigns[$contractSignVal['tenantName']][$actionVal['serialNo']]) ? $canLpSigns[$contractSignVal['tenantName']][$actionVal['serialNo']] : '';
                        }
                    } else {
                        $contract['signatories'][$contractSignKey]['keyword'] = isset($keywords[$contractSignVal['tenantName']]) ? $keywords[$contractSignVal['tenantName']] : '';
                        $contract['signatories'][$contractSignKey]['seal'] = isset($seals[$contractSignVal['tenantName']]) ? $seals[$contractSignVal['tenantName']] : '';
                    }
                }
                $contractparams['signatories'] = $contract['signatories'];
            }
            $contractparams['document_names'] = $documents['documentNames'] ?? [];
            $contractparams['templateField'] = $documents['templateNames'] ?? [];
            // 合同状态  直接发起为签署中  不发起为草稿
            $contractparams['contractStatus'] = $send ? 'SIGNING' : 'DRAFT';
            // 合同创建人在OA的ID,考虑到获取人员卡片时使用
            $contractparams['creatorId'] = $creatorId;
            app($this->electronicSignService)->setFlowRelationQysSignInfo(compact('contractparams', 'runId'));
            // 发起合同
            if ($send) {
                // 发起合同时 设置了关键字需要指定签署位置
                $res = $this->sendContract($flowId, $contractId, $contractparams['tenantName'], $documents['documentIds'] ?? []);
                if (isset($res['code']) && isset($res['code'][1])) { // 合同创建者才可以编辑
                    $res['code'][1] = trans('electronicsign.launch_error') . $res['code'][1];
                    $log['responseJson'] = json_encode(['message' => $res['code'][1]]);
                    app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                    $this->invalidContract($contractId, 'delete');
                    return $res;
                }
            }
            $log['responseJson'] = json_encode(['message' => trans('electronicsign.contract_launch_success')]);
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
        } else {
            $codeKey = $contract['code'][0] ?? '';
            $codeVal = $contract['code'][1] ?? '';
            $message  = $codeKey && $codeVal ?  trans('electronicsign.'. $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.contract_launch_failed'));
            $log['responseJson'] = json_encode(['message' => $message]);
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
        }
        //含有code 提示：合同生成失败及错误信息 不含 返回合同id
        return $contract;
    }
    /**
     * 创建合同文档
     *
     * @param [type] $setting
     * @param [type] $formData
     * @param [type] $runId
     * @param [type] $contractId
     * @param [type] $formControlData
     *
     * @return void
     * @author yml
     */
    public function addDocuments($setting, $formData, $runId, $contractId, $formControlData)
    {
        //创建文档
        $documentIds = [];
        $documentNames = [];
        $templateNames = [];
        //解析合同模板数据 用模板创建合同文档  难点在于模板参数的处理 暂不考虑有模板参数的情况  暂时注释
        $templates = $setting['templateField'] ?? '';
        // 下拉框单选时传递的是字符串，多选时为数组
        $templateIds = isset($formData[$templates]) && !empty($formData[$templates]) ? (is_array($formData[$templates]) ? $formData[$templates] : [$formData[$templates]]) : [];
        if ($templateIds) {
            // 模板参数处理 --- 获取模板所需参数，必填的没有进行提示
            $templateParams = app($this->qiyuesuoService)->parseTemplateParam($formData, $formControlData);
            $tempalteParam = [];
            foreach ($templateParams as $tpKey => $tpval) {
                $value = is_array($tpval) ? implode(',', $tpval) : $tpval;
                $tempalteParam[] = [
                    'name' => $tpKey,
                    'value' => $value,
                ];
            }
            // 模板名为以,号隔开的字符串
            $templatesNames = isset($formData[$templates . '_TEXT']) ? (explode(',', $formData[$templates . '_TEXT'])) : [];
            if ($templateIds) {
                foreach ($templateIds as $key => $val) {
                    if (!$val) {
                        return ['code' => ['please_select_template', 'electronicsign']];
                    }
                    // 根据模板id获取模板参数 验证必填项
                    $tempalteParamCheck = app($this->qiyuesuoService)->checkTemplateParamRequired($val, $templateParams, $setting['serverId']);
                    if (isset($tempalteParamCheck['code'])) {
                        return $tempalteParamCheck;
                    }
                    $document = $this->createDocumentByTemplate([
                        'templateId' => $val, // 模板id
                        'title' => $templatesNames[$key] ?? trans('electronicsign.document_template') . $val,
                        'templateParams' => $tempalteParam, // 模板参数 将表单数据这里格式后传过去
                        'contractId' => $contractId,
                    ]);
                    if (isset($document['code'])) {
                        //生成文档失败及原因
                        return $document; //['code' => ['0x093519', 'electronicsign']];
                    }
                    $documentId = isset($document['result']) && isset($document['result']['documentId']) ? $document['result']['documentId'] : 0;
                    // $documentNames[] = $templateNames[$key] ?? '';
                    $templateNames[] = $templatesNames[$key] ?? '';
                    $documentIds[] = [
                        'runId' => $runId,
                        'documentId' => $documentId,
                        'attachmentId' => '',
                        'templateId' => $val
                    ];
                }
            }
        }
        //解析附件地址
        $documentField = $setting['documentField'] ?? '';
        $attachmentIds = isset($formData[$documentField]) ? $formData[$documentField] : [];
        if (!$attachmentIds && !$templateIds) { // 没有上传附件也未选择模板
            return ['code' => ['0x093534', 'electronicsign']];
        }
        //获取附件对应地址
        if ($attachmentIds) {
            $attachmentUrls = app($this->attachmentService)->getMoreAttachmentById($attachmentIds);
            // 公有云不支持txt
            foreach ($attachmentUrls as $attachmentUrl) {
                $document = $this->createDocumentByFile([
                    'title' => $attachmentUrl['attachment_name'],
                    'file' => $attachmentUrl['temp_src_file'],
                    'contractId' => $contractId,
                ]);
                if (isset($document['code'])) {
                    //根据附件生成文档失败及原因
                    return $document; //['code' => ['0x093519', 'electronicsign']];
                }
                $documentId = isset($document['result']) && isset($document['result']['documentId']) ? $document['result']['documentId'] : 0;
                $documentNames[] = $attachmentUrl['attachment_name'];
                $documentIds[] = [
                    'runId' => $runId,
                    'documentId' => $documentId,
                    'attachmentId' => $attachmentUrl['attachment_id'],
                    'templateId' => ''
                ];
            }
        }
        //插入文档id,合同id到对应流程合同关联表
        foreach ($documentIds as $key => $documentId) {
            $documentIds[$key]['contractId'] = $contractId;
            $documentIds[$key]['created_at'] = $documentIds[$key]['updated_at'] = date('Y-m-d H:i:s');
        }
        app($this->flowRunRelationQysContractRepository)->insertMultipleData($documentIds);
        // return $documentNames;
        return compact('documentNames', 'documentIds', 'templateNames');
    }

    /**
     * 解析表单&设置，准备创建合同的数据
     * [文档]信息已经在外面解析好，这里面不做处理
     * @param  [type] $flowInfo [description]
     * @param  [type] $formData [description]
     * @param  [type] $userId   [description]
     * @return [type]           [description]
     */
    public function parseContractParams($flowInfo, $formData, $userId, $setting = [], $formControlData = [])
    {
        $runId = isset($flowInfo['runId']) ? $flowInfo['runId'] : '';
        $flowId = isset($flowInfo['flowId']) ? $flowInfo['flowId'] : '';
        $runInfo = app($this->flowRunService)->getFlowRunDetail($runId);
        $runSeq = isset($runInfo['run_seq_strip_tags']) ? $runInfo['run_seq_strip_tags'] : '';
        if (!$formControlData) {
            $formId = $flowInfo['formId'] ?? 0;
            $formControlData = app($this->flowFormService)->getParseForm($formId);
            $formControlData = array_column($formControlData, null, 'control_id');
        }
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
        // 创建者id
        $userName = app($this->qiyuesuoService)->parseFlowDataOutSend($formData, $formControlData, 'creatorNameField', $setting, -1);
        $phoneNumber = app($this->qiyuesuoService)->parseFlowDataOutSend($formData, $formControlData, 'creatorContactField', $setting, -1);
        $creatorField = $setting['creatorField'] ?? '';
        if (!$phoneNumber && $creatorField) {
            if ($creatorField == 'flow_creator' && isset($runInfo['creator'])) {
                $userInfo = app($this->userService)->getUserAllData($runInfo['creator']);
            } else {
                $userInfo = app($this->userService)->getUserAllData($userId);
            }
            if (!empty($userInfo)) {
                $userInfo = $userInfo->toArray();
                if (!$userName){
                    $userName = isset($userInfo['user_name']) ? $userInfo['user_name'] : '';
                }
                $userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
                $phoneNumber = isset($userOneInfo['phone_number']) ? $userOneInfo['phone_number'] : '';
            }
        }
        $creatorId = $userId;
        //获取公司名  设为发起方名称
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
        $signInfo = $this->parseContractSignInfo($formData, $formControlData, $setting['settinghas_many_sign_info'], true, $company['company_name'], $signOrdinal);
        //签署方信息不能为空
        if(!$signInfo) {
            return ['code' => ['sign_info_not_empty', 'electronicsign']];
        }
        //创建合同参数数组
        $params = [
            'subject' => $subject, //合同名称 必填
            'description' => '', // 合同描述
            'ordinal' => $signOrdinal, //是否顺序签署  默认false
            // 合同编号（由对接方指定）；eoffice内，指定为[流程流水号(带html标签)]
            'sn' => $runSeq,
            // 合同文档ID的集合 数组  公有云先发起合同草稿在添加合同文档
            // 'documents' => '',
            'expireTime' => $signValidity, // 合同过期时间；格式：yyyy-MM-dd HH:mm:ss ；不传，一个月过期
            'creatorName' => $userName, // 合同创建人姓名
            'creatorContact' => $phoneNumber, // 合同创建人手机号码
            'creatorId' => $creatorId, // 合同创建者id 用于展示获取
            'tenantName' => isset($company['company_name']) ? trim($company['company_name']) : '', // 发起方公司名称
            'signatories' => $signInfo, //isset($signInfo) ?: [], //$signInfo, //签署方  需从setting_sign_info中获取
            'businessData' => json_encode(['run_id' => $runId]), //用户的业务数据及文档调整，json格式   增加 流程id run_id
        ];
        // 公有云所需合同创建人信息格式
        if ($phoneNumber) {
            $params['creator'] = [
                'name' => $userName,
                'contact' => $phoneNumber,
                'contactType' => 'MOBILE',
            ];
        }
        // 业务分类ID -- 公有云格式
        $categoryField = isset($setting['categoryField']) ? $setting['categoryField'] : '';
        if (!empty($categoryField)) {
            $params['categoryId'] = [
                'id' => isset($formData[$categoryField]) ? $formData[$categoryField] : '',
                'name' => isset($formData[$categoryField . '_TEXT']) ? $formData[$categoryField . '_TEXT'] : '',
            ];
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
    public function parseContractSignInfo($formData, $formControlData, $signInfo, $contractNeed = true, $company_name = '', $ordinal = false)
    {
        //获取集成设置-签署信息设置
        $return = [];
        if ($signInfo && $formData) {
            foreach ($signInfo as $key => $signInfoItem) {
                if ($signInfoItem) {
                    // 公有云运营方对应类型为COMPANY 方便页面展示时保存的是CORPORATE
                    $tenantType = $signInfoItem['tenantType'] != 'CORPORATE' ? $signInfoItem['tenantType'] : 'COMPANY';
                    $oneSignInfos = app($this->qiyuesuoService)->parseOneSignInfo($signInfoItem, $formData, $formControlData, $tenantType);
                    // 企业方和个人 可能存在多签署方
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
        //处理数据
        $name = [
            'COMPANY' => '签署公章',
            'OPERATOR' => '经办人签署',
            'LP' => '法人签署',
            'AUDIT' => '审批',
        ];
        // 定义签署方数组
        $returns = $company = $actions =  [];
        $seriaNo = 1;
        foreach ($return as $key => $val) {
            // 之前未考虑到多企业的情况
            if ($val['tenantType'] == 'COMPANY') {
                $tenantName = $val['tenantName'];
                $company[$tenantName] = [
                    'tenantType' => $val['tenantType'],
                    'tenantName' => $tenantName,
                ];
                // 接收人信息 receiver ['contact'=>'手机号码','contactType'=>'MOBILE']
                if ($val['contact'] && isset($val['contact'][0]) && !isset($company[$tenantName]['receiver'])) {
                    $company[$tenantName]['receiver'] = ['contact' => $val['contact'][0], 'contactType' => 'MOBILE'];
                }
                // 签署顺序
                if ($ordinal && !isset($company[$tenantName]['serialNo'])) {
                    $company[$tenantName]['serialNo'] = $seriaNo;
                    $seriaNo++;
                } else {
                    $company[$tenantName]['serialNo'] = $val['serialNo'];
                }
                // 签署动作
                $operators = [];
                if ($val['contact']) {
                    foreach ($val['contact'] as $contact) {
                        $operators[] = ['contact' => $contact, 'contactType' => 'MOBILE'];
                    }
                }
                $action = [
                    'type' => $val['type'],
                    'name' => isset($val['type']) && isset($name[$val['type']]) ? $name[$val['type']] : '签署公章',
                    // 公有云签署方签署动作serialNo 执行顺序，从0开始
                    'serialNo' => isset($company[$tenantName]['actions']) ? count($company[$tenantName]['actions']) : 0,
                    // 操作人 operators [['contact'=>'手机号码','contactType'=>'MOBILE']]
                    'operators' => $tenantName == $company_name ? $operators : [],
                    // 'operators' => $operators,
                    'keyword' => $val['keyword'] ?? '',
                    'sealId' => $val['seal'] ?? ($val['sealId'] ?? ''),
                    'canLpSign' => $val['canLpSign'] ?? '',
//                    'sealIds' => $tenantName == $company_name ? ($val['seals'] ? explode(',', $val['seals']) : '') : '',
                ];
                if ($tenantName == $company_name) {
                    if (is_array($val['seals'])) {
                        $action['sealIds'] = $val['seals'];
                    } else {
                        if ($val['seals'] && explode(',', $val['seals'])) {
                            $action['sealIds'] = explode(',', $val['seals']);
                        }
                    }
                }
                $actions[$tenantName][] = $action;
                $company[$tenantName]['actions'] = $actions[$tenantName];
                // $company[$tenantName]['actions'][] = [
                //     'type' => $val['type'],
                //     'name' => isset($val['type']) && isset($name[$val['type']]) ? $name[$val['type']] : '签署公章',
                //     // 公有云签署方签署动作serialNo 执行顺序，从0开始
                //     'serialNo' => isset($company[$tenantName]['actions']) ? count($company[$tenantName]['actions']) : 0,
                //     // 操作人 operators [['contact'=>'手机号码','contactType'=>'MOBILE']]
                //     'operators' => $tenantName == $company_name ? $operators : [],
                //     'keyword' => $val['keyword'] ?? '',
                //     'seal' => $val['seal'] ?? '',
                // ];
                // 设置法人签字了处理
                if ($tenantName == $company_name && strtolower($val['canLpSign']) == trans('electronicsign.yes')) {
                    $company[$tenantName]['actions'][] = [
                        'type' => 'LP',
                        'name' => $name['LP'],
                        'serialNo' => isset($company[$tenantName]['actions']) ? count($company[$tenantName]['actions']) : 0,
                        'keyword' => $val['keyword'] ?? '',
                        'canLpSign' => 1
                    ];
                }
            } else {
                // 设置顺序签署后添加签署顺序
                if ($ordinal) {
                    $val['serialNo'] = $seriaNo;
                    $seriaNo++;
                }
                if ($val['contact']) {
                    $val['receiver'] = ['contact' => $val['contact'], 'contactType' => 'MOBILE'];
                }
                $returns[] = $val;
            }
        }
        if (isset($company)) {
            foreach ($company as $companyVal) {
                array_unshift($returns, $companyVal);
            }
        }
        return $returns;
    }

    /**
     * 合同草稿发起合同
     *
     * @return array
     */
    public function sendContract($flowId, $contractId, $tenantName = '', $documents = [])
    {
        // 签署方信息配置中有关键字配置时发起合同需指定关键字
        $stampers = [];
        if ($documents) {
            // 根据合同id查询获取签署方信息
            $signatories = app($this->flowRunRelationQysContractSignInfoRepository)->getFieldInfo(['contractId' => $contractId]);
            if ($signatories) {
                foreach ($signatories as $signatory) {
                    $signatoryId = $signatory['signatoryId'];
                    if ($signatory['tenantType'] == 'COMPANY') {
                        if (isset($signatory['keyword']) && !empty($signatory['keyword']) && $signatory['type'] != 'AUDIT') {
                            foreach ($documents as $document) {
                                $stampers[] = [
                                    'signatoryId' => $signatoryId,
                                    'actionId' => $signatory['actionId'],
                                    'type' => in_array($signatory['type'], ['OPERATOR']) ? 'PERSONAL' : $signatory['type'],
                                    'documentId' => $document['documentId'] ?? ($document['id'] ?? ''),
                                    'keyword' => $signatory['keyword'],
                                ];
                                $stampers[] = [
                                    'signatoryId' => $signatoryId,
                                    'actionId' => $signatory['actionId'],
                                    'type' => 'TIMESTAMP',
                                    'documentId' => $document['documentId'] ?? ($document['id'] ?? ''),
                                    'keyword' => $signatory['keyword'],
                                ];
                            }
                        }
                    } else {
                        if (isset($signatory['keyword']) && !empty($signatory['keyword'])) {
                            foreach ($documents as $document) {
                                $stampers[] = [
                                    'signatoryId' => $signatoryId,
                                    'type' => 'PERSONAL',
                                    'documentId' => $document['documentId'] ?? ($document['id'] ?? ''),
                                    'keyword' => $signatory['keyword'] ?? '',
                                ];
                                $stampers[] = [
                                    'signatoryId' => $signatoryId,
                                    'type' => 'TIMESTAMP',
                                    'documentId' => $document['documentId'] ?? ($document['id'] ?? ''),
                                    'keyword' => $signatory['keyword'] ?? '',
                                ];
                            }
                        }
                    }
                }
            }
        }
        $params = [
            'contractId' => $contractId,
        ];
        if ($tenantName) {
            $params['tenantName'] = $tenantName;
        }
        if ($stampers) {
            $params['stampers'] = $stampers;
        }
        if ($flowId) {
            $this->init($flowId);
        }
        if (!$this->qiyuesuoPublicApi) {
            return ['code' => ['0x093515', 'electronicsign']];
        }
        return $this->qiyuesuoPublicApi->sendRequest($this->url['send_contract'], $params, 'POST');
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
        if (!$this->qiyuesuoPublicApi) {
            return ['code' => ['0x093515', 'electronicsign']];
        }
        return $this->qiyuesuoPublicApi->sendRequest($this->url['contract_detail'], $data, 'GET');
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
            if (!is_array(actions)) {
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
        if (!$this->qiyuesuoPublicApi) {
            //根据服务配置实例化 契约锁API service失败
            $log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093515')]);
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
            return ['code' => ['0x093515', 'electronicsign']];
        }
        if (!$server) {
            $server = app($this->qiyuesuoServerRepository)->getServerByFlowId($flowId);
        }
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
        $type = $data['type'] ?? 'view';
        $url = isset($this->url[$type]) ? $this->url[$type] : 'view';
        $params['contractId'] = $contractId;
        $method = 'GET';
        // 获取签署页面 草稿时发起合同
        if ($type == 'sign' || $type == 'presign') {
            $contractId = $this->checkRecreated($contractId, $data, $own);
            if (is_array($contractId) && !isset($contractId['contractId'])) {
                return $contractId;
            } else {
                $contractId = $contractId['contractId'];
                $params['contractId'] = $contractId;
            }
            $contract = $this->getContract($flowId, ['contractId' => $contractId]);
            $contract = $contract['result'] ?? [];
            if (!$contract) {
                // 获取合同数据失败，请稍后查看
                $log['responseJson'] = json_encode(['message' => trans('electronicsign.get_contract_failed')]);
                app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                return ['code' => ['get_contract_failed', 'electronicsign']];
            }
            $contractStatus = $contract['status'] ?? '';
            if ($type == 'sign' && $contractStatus == 'DRAFT') {
                // 将状态为草稿的合同发起
                // 需要考虑是否指定关键字签署位置  --- 指定位置的无需考虑
                $tenantName = $contract['tenantName'] ?? '';
                $needSetPosition = true;
                if (isset($contract['signatories']) && count($contract['signatories']) > 0) {
                    foreach ($contract['signatories'] as $signatory) {
                        if (isset($signatory['stampers']) && count($signatory['stampers']) > 0) {
                            $needSetPosition = false;
                        }
                    }
                }
                if ($needSetPosition) {
                    $documents = app($this->flowRunRelationQysContractRepository)->getFieldInfo(['contractId' => $contractId]);
                    $res = $this->sendContract($flowId, $contractId, $tenantName, $documents);
                } else {
                    $res = $this->sendContract($flowId, $contractId, $tenantName);
                }
                if (isset($res['code'])) { // 发起人才可以编辑
                    $res['code'][1] = trans('electronicsign.launch_error') . $res['code'][1];
                    $log['responseJson'] = json_encode(['message' => $res['code'][1]]);
                    app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                    return $res;
                }
            }
            if ($type == 'presign' && $contractStatus != 'DRAFT') {
                // 指定签署位置/编辑合同  草稿状态才可以
                $log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093537')]);
                app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                return ['code' => ['0x093537', 'electronicsign']];
            }
            // 公有云时需要传递签署人的手机号
            if ($type == 'sign') {
                $phoneNumber = $own['phone_number'] ?? '';
                if (!$phoneNumber) {
                    // 获取签署页面地址时需要手机号
                    $log['responseJson'] = json_encode(['message' => trans('electronicsign.openApi_get_sign_page_need_mobile')]);
                    app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                    return ['code' => ['openApi_get_sign_page_need_mobile', 'electronicsign']];
                }
                $params['user'] = [
                    'contact' => $phoneNumber,
                    'contactType' => 'MOBILE',
                ];
            }
            $method = 'POST';
        }
        // 下载处理
        if ($type == 'download') {
            // 获取合同状态  未完成的合同不能下载
            $contract = $this->getContract($flowId, ['contractId' => $contractId]);
            $contract = $contract['result'] ?? [];
            if (isset($contract['status']) && !in_array($contract['status'], ['COMPLETE', 'END', 'FORCE_END'])) {
                $log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093538')]);
                app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                return ['code' => ['0x093538', 'electronicsign']];
            }
            // 先判断合同id对应附件文件是否存在
            $attachmentId = app($this->flowRunRelationQysContractInfoRepository)->getFieldValue('attachmentId', ['contractId' => $contractId]);
//            if ($attachmentId) {
//                $log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success')]);
//                app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
//                return ['attachmentId' => $attachmentId];
//            }
            $attachment = app($this->attachmentService)->getOneAttachmentById($attachmentId);
            if ($attachmentId && $attachment) {
                // 判断相关附件中是否有，没有添加到流程相关附件
                $data['processId'] = $data['processId'] ?? '';
                $data['flowProcess'] = $data['flowProcess'] ?? '';
                if ($data['runId'] && $data['processId'] && $data['flowProcess']) {
                    $result = app($this->qiyuesuoService)->saveFlowPublicAttachment($attachmentId, $data['runId'], $data['processId'], $data['flowProcess'], $own['user_id']);
                    if (isset($result['code'])) {
                        $log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success_save_flow_related_failed')]);
                        app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                        return $result['code'];
                    } else {
                        $log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success')]);
                        app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                        return $result;
                    }
                } else {
                    $log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success')]);
                    app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                    return ['attachmentId' => $attachmentId];
                }
            }
            $filename = trans('electronicsign.qiyuesuo_contract_file') . '-' . ($contractId ?? time());
            // $filename = iconv("UTF-8", "GBK", $filename);
            $suffix = 'zip';
            $result = $this->qiyuesuoPublicApi->sendRequest($url, ['contractId' => $contractId], 'GET');
            if ($result) {
                // 保存本地查看文件有效性 测试使用 之前保存了文件损坏
                // $localPath = storage_path('logs').'/'.'contract/'. $contractId . time() .'.zip';
                // app($this->qiyuesuoService)->checkDownloadAndReturn($result, $localPath);
                // $content = file_get_contents($localPath);
                $attachmentId = app($this->qiyuesuoService)->saveFile($filename, $suffix, $result, $own['user_id'], $contractId, $data['runId'] ?? '', $data['processId'] ?? '', $data['flowProcess'] ?? '');
                $log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_success')]);
                app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                return $attachmentId;
            } else {
                $log['responseJson'] = json_encode(['message' => trans('electronicsign.download_contract_failed') . json_encode($result)]);
                app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
                return ['code' => ['download_contract_failed', 'electronicsign']];
            }
        } else {
            $result = $this->qiyuesuoPublicApi->sendRequest($url, $params, $method);
            //处理返回结果  通知将地址键值统一修改url  失败的错误信息格式整理
            if (is_array($result)) {
                if (!isset($result['code'])) {
                    $url = isset($result['result']) && isset($result['result']['pageUrl']) ? $result['result']['pageUrl'] : '';
                    $result = $url ?? ['code' => ['get_page_url_failed', 'electronicsign']];
                }
                $log['responseJson'] = json_encode(['message' => trans('electronicsign.operate_contract_success')]);
            } else {
                $log['responseJson'] = json_encode(['message' => $result]);
                $result = ['code' => ['', $result]];
            }
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
            return $result;
        }
    }

    /**
     * 获取子公司列表
     *
     * @param boolean $needplatform 是否将总公司一块返回
     *
     * @return array
     * @author yml
     */
    public function getCompanies($needplatform = false)
    {
        $data = $this->qiyuesuoPublicApi->sendRequest($this->url['company'], [], 'GET');
        if (isset($data['qys_code']) && $data['qys_code'] == 0) {
            $subCompanies = isset($data['result']) && isset($data['result']['subCompanies']) ? $data['result']['subCompanies'] : [];
            $platform = isset($data['result']) && isset($data['result']['platform']) ? $data['result']['platform'] : [];
        } else {
            return [];
        }
        if ($needplatform) {
            array_push($subCompanies, $platform);
        }
        return $subCompanies;
    }

    public function getList($type, $param)
    {
        $data = $this->qiyuesuoPublicApi->sendRequest($this->url[$type], $param, 'GET');
        $list = [];
        if (isset($data['qys_code']) && $data['qys_code'] == 0) {
            $list = isset($data['result']) && isset($data['result']['list']) ? $data['result']['list'] : [];
        }
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                // 公有云目前都是电子章且目前接口返回为带印章类型 --- 后期根据需求更改
                if ($type == 'seals') {
                    $val['category'] = 'ELECTRONIC';
                }
                if ($type == 'categories') {
                    $val['type'] = 'ELECTRONIC';
                }
                $list[$key] = array_merge($val, $param);
            }
        }
        return $list;
    }
    /**
     * 通过获取业务分类检测契约锁服务
     *
     * @return void
     * @author yml
     */
    public function checkServer()
    {
        return $this->qiyuesuoPublicApi->sendRequest($this->url['categories'], [], 'GET');
    }

    /**
     * 公章签署
     */
    public function signByCompany($data)
    {
        return $this->qiyuesuoPublicApi->sendRequest($this->url['signbycompany'], $data);
    }
    /**
     * 检测合同状态  -- 草稿的话发起合同
     *
     * @param [type] $contractId
     * @param [type] $flowId
     *
     * @return void
     * @author yml
     */
    public function checkContractStatus($flowId, $contractId)
    {
        $contract = $this->getContract($flowId, ['contractId' => $contractId]);
        $contract = $contract['result'] ?? [];
        if (isset($contract) && isset($contract['status']) && $contract['status'] == 'DRAFT') {
            $sendRes = $this->sendContract($flowId, $contractId, $contract['tenantName'], $contract['documents']);
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
     * 公有云电子合同的操作-草稿合同的删除、签署中合同的撤回、已完成合同作废
     *
     * @param [type] $contractId 合同id
     * @param [type] $type 操作类型：delete 删除，recall 撤回，cancel 作废
     *
     * @return void
     * @author yml
     */
    public function invalidContract($contractId, $type)
    {
        $data = ['contractId' => $contractId];
        $contract = $this->getContract(0, $data);
        $contract = $contract['result'] ?? [];
        if (!$contract) {
            // 未找到合同
            return ['code' => ['contract_not_found', 'electronicsign']];
        }
        $contractStatus = $contract['status'] ?? '';
        if (!$contractStatus) {
            // 合同状态异常，不能进行此操作
            return ['code' => ['contract_status_error', 'electronicsign']];
        }
        if ($type == 'delete'){
            if (!in_array($contractStatus, ['DRAFT', 'EXPIRED', 'RECALLED', 'REJECTED'])) {
                 // 合同状态不是草稿，不能进行删除操作
                return ['code' => ['contract_not_draft_no_delete', 'electronicsign']];
            }
        }
        if ($type == 'recall') {
            if ($contractStatus != 'SIGNING' && $contractStatus != 'FILLING') {
                // 合同状态不是签署中或者拟定过，不能进行撤回操作
               return ['code' => ['contract_not_signing_no_recall', 'electronicsign']];
           }
        }
        if ($type == 'cancel') {
            if ($contractStatus != 'COMPLETE') {
                // 合同状态不是已完成，不能进行作废操作
               return ['code' => ['contract_not_complete_no_cancel', 'electronicsign']];
           }
           // 作废合同时传递签署作废文件的发起方公章，发起方作为签署方可不填
        //    $data['sealId'] = '';
        }
        return $this->qiyuesuoPublicApi->sendRequest($this->url['invalid'], $data);
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
        $contract = $contract['result'] ?? [];
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
                // 'TERMINATING', 20210303先移除作废中
            } elseif (in_array($contractStatus, ['RECALLED', 'TERMINATED', 'INVALIDING', 'INVALIDED', 'EXPIRED', 'REJECTED']) || (!$contract && !$contractStatus)) {
                // 撤回/作废的 先创建新合同在删除旧合同
                $recreateFlag = true;
            } else {
                // 其他状态不处理
                return ['contractId' => $contractId, 'contractStatus' => $contractStatus];
            }
        } else {
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
                // 删除旧合同 --- 去掉和流程的关联
                $res = app($this->flowRunRelationQysContractRepository)->deleteByWhere(['runId' => [$runId], 'contractId' => [$contractId]]);
                if ($contractStatus == 'DRAFT') {
                    $this->invalidContract($contractId, 'delete');
                }
                return ['contractId' => $newContractId, 'contractStatus' => $contractStatus];;
            } else {
                return $newContractRes;
            }
        }
    }
}
