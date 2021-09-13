<?php


namespace App\EofficeApp\ElectronicSign\Services;


use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Cache;

/** 印控中心
 * Class SealControlCenterService
 * @package App\EofficeApp\ElectronicSign\Services
 */
class SealControlCenterService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->electronicSignService = 'App\EofficeApp\ElectronicSign\Services\ElectronicSignService';
        $this->qiyuesuoServerRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoServerRepository';
        $this->qiyuesuoService = 'App\EofficeApp\ElectronicSign\Services\qiyuesuoService';
        $this->companyService = 'App\EofficeApp\System\Company\Services\CompanyService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->paramsRepository = 'App\EofficeApp\System\Security\Repositories\SystemParamsRepository';
    }

    public function getSetting()
    {
//         $config = app($this->electronicSignService)->getServerBaseInfo();
//         if (!$config || $config['qys_on_off'] == 0) {
// //            return ['code' => ['0x093533', 'electronicsign']];
//             return ['message' => trans('electronicsign.0x093533')];
//         }
        $setting = app($this->electronicSignService)->getServerDetail(0, ['isQYSLogin' => [1]]);
        if (!$setting || (is_array($setting) && isset($setting['code']))){
            // return ['message' => trans('electronicsign.please_choose_server')];
//            return ['code' => ['please_choose_server', 'electronicsign']];
            $setting = [
                'setting_id' => 0,
                'message' => trans('electronicsign.please_choose_server')
            ];
        } else {
            $setting = $setting->toArray();
        }
        if ($style = Cache::get('seal_control_open_style')) {
            $setting['seal_control_open_style'] = $style;
        } else {
            $result = app($this->paramsRepository)->getParamsByWhere(["param_key" => ['seal_control_open_style']]);
            if($result && $result->count() && $result->first()) {
                $first = $result->first();
                if ($first && $first->seal_control_open_style){
                    Cache::put('seal_control_open_style', $first->seal_control_open_style, 24 * 60 *60);
                }
                $setting['seal_control_open_style'] = $first->seal_control_open_style ?? '_blank';
            }
        }
        $config = app($this->paramsRepository)->getParamsByWhere(["param_key" => [['seal_control_center_router_user_info', 'seal_control_center_router_sign', 'seal_control_center_router_seal_detail', 'seal_control_center_router_seal_manage_electronic', 'seal_control_center_router_seal_manage_physical', 'seal_control_center_router_launch', 'seal_control_center_router_verifier'], 'in']]);
        $setting['router'] = [];
        foreach ($config as $value) {
            $setting['router'][$value['param_key']] = $value['param_value'];
        }
        return $setting;
    }

    public function setParam($param)
    {
        $data = ["param_value" => $param['value']];
        $where = ['param_key' => [$param['key']]];
        $result = app($this->paramsRepository)->updateData($data, $where);
        Cache::put($param['key'], $param['value'], 24 * 60 * 60);
        return $result;
    }

    public function getUrl($param, $user)
    {
        $serverId = $param['server_id'] ?? 0;
        if ($serverId){
            $userId = $user['user_id'] ?? "";
            $page = isset($param['page']) ? $param['page'] : "";
            $qysServer = app($this->electronicSignService)->getServerDetail($serverId);
            if (!$qysServer || !isset($qysServer['serverType']) ||empty($qysServer['serverType'])) {
                return ['message' => trans('electronicsign.please_choose_server'), 'error_type' => 1];
            }
            if ($qysServer['serverType'] == 'private') {
                return $this->getPrivateUrl($qysServer, $page, $userId);
            } else {
                return $this->getPublicUrl($qysServer, $page, $userId);
            }
        } else {
            return ['message' => trans('electronicsign.please_choose_server'), 'error_type' => 1];
//            return ['code' => ['please_choose_server', 'electronicsign']];
        }
    }

    public function getPrivateUrl($qysServer, $page, $userId)
    {
        $qysServerUrl = '';
        if ($qysServer && !isset($qysServer['code'])){
            $serverUrl = $qysServer->serverUrl;
            if ($serverUrl && strpos($serverUrl, '9180') !== false) {
                $qysServerUrl = str_replace('9182', '9180', $serverUrl);
            } else {
                $signUrl = $qysServer->goPage;
                if ($signUrl) {
                    $qysServerUrl = str_replace('/sign/', '', $signUrl);
                } else {
                    return ['message' => trans('electronicsign.server_sign_url_error'), 'error_type' => 2];
                }
            }
        }
        if (!$qysServerUrl) {
            return ['message' => trans('electronicsign.server_url_error'), 'error_type' => 2];
//            return ['code' => ['server_url_error', 'electronicsign']];
        }
        $qystoken = app($this->electronicSignService)->getQysSignUserTokenStringByUserId($userId, 'mobile', $qysServer);
        if (is_array($qystoken)) {
            if(isset($qystoken['code']) && isset($qystoken['code']['0']) && $qystoken['code']['0'] == '0x093520') {
                return ['message' => trans('electronicsign.0x093520'), 'error_type' => 3];
//                return $qystoken;
            } else {
                // 出错了
                return ['message' => trans('electronicsign.server_url_error'), 'error_type' => 2];
//                return ['code' => ['server_url_error', 'electronicsign']];
            }
        } else {
            $pageRoute = '';
            if ($page == "user_info") {
                // 个人中心
                $pageUrl = $this->getRouter('seal_control_center_router_user_info');
                $pageRoute .= $pageUrl ? $pageUrl : "usercenter/info";
            } else if ($page == "has_sign" || $page == "todo_sign") {
                // 已签文件 / 待签文件
                $pageUrl = $this->getRouter('seal_control_center_router_sign');
                $pageRoute .= $pageUrl ? $pageUrl : "contractlist";
            } else if ($page == "seal_detail") {
                // 印章管理
                $pageUrl = $this->getRouter('seal_control_center_router_seal_detail');
                $pageRoute .= $pageUrl ? $pageUrl : "physical";
            } else if ($page == "seal_manage_electronic") {
                // 印章管理
                $pageUrl = $this->getRouter('seal_control_center_router_seal_manage_electronic');
                $pageRoute .= $pageUrl ? $pageUrl : "seal-electronic";
            }  else if ($page == "seal_manage_physical") {
                // 印章管理
                $pageUrl = $this->getRouter('seal_control_center_router_launch');
                $pageRoute .= $pageUrl ? $pageUrl : "seal-physical";
            } else if ($page == "launch") {
                // 发起合同
                $pageUrl = $this->getRouter('seal_control_center_router_seal_manage_physical');
                $pageRoute .= $pageUrl ? $pageUrl :  "launch/contract";
            } else if ($page == 'verifier') {
                // 文件验签
                $pageUrl = $this->getRouter('seal_control_center_router_verifier');
                $pageRoute .= $pageUrl ? $pageUrl : "verifier";
            }
            $signUrl = $qysServerUrl . '/' . $pageRoute . '?qystoken=' . $qystoken;
            // 20190604-单点登录跳转过来的时候，后面再跟一个platform=EOFFICE的固定值，用来在ec-契约锁中标识是来自eoffice
            $signUrl .= "&platform=EOFFICE";
            // &hide_menu=true 隐藏签署页面的菜单
            $signUrl .= "&hide_menu=true";
            // 拼接筛选参数
            if ($page == "has_sign") {
                // 已签文件
                $signUrl .= "&status=COMPLETE";
            } else if ($page == "todo_sign") {
                // 待签文件
                $signUrl .= "&status=REQUIRED";
            }
            if($style = Cache::get('seal_control_open_style')){
            } else {
                $result = app($this->paramsRepository)->getParamsByWhere(["param_key" => ['seal_control_open_style']]);
                if($result && $result->count() && $result->first()) {
                    $first = $result->first();
                    if ($first && $first->seal_control_open_style){
                        Cache::put('seal_control_open_style', $first->seal_control_open_style, 24 * 60 * 60);
                    }
                    $style = $first->seal_control_open_style ?? '_blank';
                }
            }
            return ['url' => $signUrl, 'style' => $style];
        }
    }

    private function getRouter($key)
    {
        $first = app($this->paramsRepository)->getParamsByWhere(["param_key" => [$key]])->first();
        return $first && $first->$key ? $first->$key : '';
    }

    public function getPublicUrl($qysServer, $page, $userId)
    {
        return ['message' => trans('electronicsign.public_service_not_support')];
    }

    /** 文件签章
     * @param $params
     */
    public function fileSignature($params, $user)
    {
        // 获取印控中心的契约锁服务id，根据服务id获取服务相关信息 创建契约锁服务对象
        $setting =  $this->getSetting();
        if (!$setting || !isset($setting['serverId']) || !$setting['serverId']) {
            return ['code' => ['please_choose_server', 'electronicsign']];
        }
        $server = app($this->electronicSignService)->getServerDetail($setting['serverId']);
        if($server['serverType'] == 'public') {
            return ['code' => ['public_service_not_support', 'electronicsign']];
        }
        $service = app($this->qiyuesuoService)->initByServer($server);
        $attachmentIds = $params['file'] ?? [];
        // 创建合同文档，组装合同所需数据，发起合同
         if (!$attachmentIds) {
            //提示：附件地址为空，请确认合同文件已上传
            return ['code' => ['0x093534', 'electronicsign']];
        }
        //获取附件对应地址
        $documentNames = [];
        $documentIds = [];
        if ($attachmentIds) {
            $attachmentUrls = app($this->attachmentService)->getMoreAttachmentById($attachmentIds);
            foreach ($attachmentUrls as $attachmentUrl) {
                $documentId = $service->createDocumentByFile([
                    'title' => $attachmentUrl['attachment_name'],
                    'file' => $attachmentUrl['temp_src_file'],
                ]);
                if (isset($documentId['code'])) {
                    //根据附件生成文档失败及原因
                    return $documentId;
                }
                $documentNames[] = $attachmentUrl['attachment_name'];
                $documentIds[] = [
                    'documentId' => $documentId['documentId'],
                    'attachmentId' => $attachmentUrl['attachment_id'],
                    'templateId' => ''
                ];
            }
        }
        if (empty($documentIds)) {
            return ['code' => ['documents_not_exists', 'electronicsign']];
        }
        if ($params['company_name']) {
            $company['company_name'] = $params['company_name'];
        } else {
            $company = app($this->companyService)->getCompanyDetail();
            if (!$company) {
                // 获取企业名称失败，无法创建合同
                return ['code' => ['0x093530', 'electronicsign']];
            }
        }
        // 签署方信息处理
        if ($params['type'] == 1) {
            $personalName = !empty($params['personalName']) ? $params['personalName'] : $user['user_name'];
            $personalPhone = !empty($params['personalPhone']) ? trim($params['personalPhone']) : trim($user['phone_number']);
            if (!$personalPhone){
                return ['code'=> ['get_phone_failed', 'electronicsign']];
            }
            // 个人签名
            $signInfo = [
                'tenantType' => 'PERSONAL',
                'tenantName' => $personalName,
//                'receiverName' => $personalName,
                'contact' => $personalPhone,
                'serialNo' => 1,
            ];
        } else {
            // 企业签章
            $companySignName = $params['companySignName'] ?? $user['user_name'];
            $companySignPhone = $params['companySignPhone'] ?? $user['phone_number'];
            if ($companySignPhone) {
                $action =  [
                    [
                        'type' => 'CORPORATE',
                        'name' => '企业签章',
                        'serialNo' => 1,
                        'actionOperators' => [
                            [
                                'operatorContact' => $companySignPhone
                            ]
                        ]
                    ]
                ];
            }
            $signInfo = [
                'tenantType' => 'CORPORATE',
                'tenantName' => $company['company_name'],
                'receiverName' => $companySignName,
                'contact' => $companySignPhone,
                'serialNo' => 1,
            ];
            if (isset($action) && !empty($action)) {
                $signInfo['actions'] = $action;
            }
        }
        $signInfos[] = $signInfo;
        // 生成合同参数处理
        $contractparams = [
            'subject' => $documentNames[0], //合同名称 必填
            'description' => '', // 合同描述
//            'ordinal' => $signOrdinal, //是否顺序签署  默认false
            // 合同编号（由对接方指定）；eoffice内，指定为[流程流水号(带html标签)]
//            'sn' => $runSeq,
//            'categoryId' => '', // 业务分类ID
            // 合同文档ID的集合 数组  直接创建合同必填，[已经在外部处理]
             'documents' => array_column($documentIds, 'documentId'),
//            'expireTime' => $signValidity, // 合同过期时间；格式：yyyy-MM-dd HH:mm:ss ；不传，一个月过期
//            'creatorName' => $user['user_name'], // 合同创建人姓名
            'creatorContact' => trim($user['phone_number']), // 合同创建人手机号码
//            'creatorId' => $creatorId, // 合同创建者id 用于展示获取
            'tenantName' => isset($company['company_name']) ? trim($company['company_name']) : '', // 发起方名称
            'signatories' => $signInfos, //isset($signInfo) ?: [], //$signInfo, //签署方  需从setting_sign_info中获取
            'businessData' => json_encode(['run_id' => 0]), //用户的业务数据及文档调整，json格式   增加 流程id run_id
        ];
//        dd($contractparams);
        $contractId = $service->qiyuesuoApi->createContract('/contract/createbycategory', $contractparams);
        if (isset($contractId['code'])) {
            return $contractId;
        }
    }

    public function updateSealControlCenterSetting($param)
    {
        $serverId = $param['server_id'] ?? 0;
        if ($serverId) {
            $server = app($this->qiyuesuoServerRepository)->getDetail($serverId);
            $server->isQYSLogin = 1;
            $server = $server->toArray();
            $result = app($this->electronicSignService)->editServer($serverId, $server);
        }
        $paramStyle = $param['param_style'] ?? '';
        if ($paramStyle) {
            $params = [
                'key' => 'seal_control_open_style',
                'value' => $paramStyle
            ];
            $result = $this->setParam($params);
        } else {
            if (isset($param['key']) && isset($param['value'])) {
                $result = $this->setParam($param);
            }
        }
        return $result;
    }
}