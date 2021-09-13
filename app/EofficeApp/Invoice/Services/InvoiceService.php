<?php


namespace App\EofficeApp\Invoice\Services;

use App\EofficeApp\Base\BaseService;
use Eoffice;
use Queue;
use App\Jobs\SyncInvoiceManageJob;
use Illuminate\Support\Facades\Cache;

class InvoiceService extends BaseService
{
    private $invoiceRepositories;
    private $invoiceCloudService;
    public function __construct(
    ) {
        parent::__construct();
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->invoiceRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceRepositories';
        $this->invoiceLogsService = 'App\EofficeApp\Invoice\Services\InvoiceLogsService';
        $this->thirdPartyInterfaceService = 'App\EofficeApp\IntegrationCenter\Services\ThirdPartyInterfaceService';
        $this->thirdPartyInvoiceCloudTeamsYunRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyInvoiceCloudTeamsYunRepository';
        $this->invoiceCloudService = 'App\EofficeApp\Invoice\Services\InvoiceCloudService';
        $this->invoiceManageService = 'App\EofficeApp\Invoice\Services\InvoiceManageService';
        $this->invoiceCloudTeamsYunUserRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceCloudTeamsYunUserRepositories';
        $this->invoiceFlowNodeActionSettingRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceFlowNodeActionSettingRepositories';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->workWechatService = 'App\EofficeApp\WorkWechat\Services\WorkWechatService';
        $this->weixinService = 'App\EofficeApp\Weixin\Services\WeixinService';
        $this->invoiceFlowSettingRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceFlowSettingRepositories';
        $this->openApiService = 'App\EofficeApp\OpenApi\Services\OpenApiService';
        $this->companyService = 'App\EofficeApp\System\Company\Services\CompanyService';
        $this->workWechatRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatRepository';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->flowService = 'App\EofficeApp\Flow\Services\FlowService';
    }

    public function getList($request, $own)
    {
        // 只展示自己创建的
        $params = $this->parseParams($request);
        $service = $this->getUseInvocieCloudService($own);
        if (is_array($service)) {
             return ['list' => [], 'count' => 0];
        }
        $data = $service->getInvoiceList($params);
//        return ['list' => [], 'count' => 0];
        if ($data['error_code'] == 0) {
            if ($data['data']['total'] > 0 && (!isset($data['data']['infos']) || !$data['data']['infos'])){
                $params['page'] = ceil($data['data']['total'] / $params['limit']);
                return $this->getList($params, $own);
            }
//            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig($params);
            // 处理数据为我们公共组件所需数据结构
            $data['data']['list'] = $data['data']['infos'] ?? [];
            if ($data['data']['list']) {
                // 解决微信回传的发票数据 没有cname值
                $types = $this->getTypes($params, $own);
                $types = array_column($types, NULL, 'key');
                foreach ($data['data']['list'] as $key => $item) {
                    if ($item && $item['comm_info'] && $item['comm_info']['pro'] && $item['comm_info']['pro']['type'] && !$item['comm_info']['pro']['cname']) {
                        $cname = isset($types[$item['comm_info']['pro']['type']]) && $types[$item['comm_info']['pro']['type']]['value'] ? $types[$item['comm_info']['pro']['type']]['value'] : '';
                        $item['comm_info']['pro']['cname'] = $this->returnCname($item['comm_info']['pro']['type'], $cname);
                    }
                    $data['data']['list'][$key] = $item;
                }
            }
            unset($data['data']['infos']);
            $data['data']['count'] = $data['data']['total'] ?? 0;
            return $data['data'];
        } else {
//            return ['code' => ['', $data['message']]];
            return ['list' => [], 'count' => 0];
        }
    }
    public function getUnreimburseInvoiceList($request, $own)
    {
        $params = $this->parseParams($request);
        $params['search']['status'] = [3];
        $service = $this->getUseInvocieCloudService($own);
        if (is_array($service)) {
            return ['list' => [], 'count' => 0];
        }
        if (isset($params['search']['id']) && isset($params['search']['id'][0])) {
            $id = $params['search']['id'][0];
            $data = $this->getInvoiceBatch(['ids' => is_array($id) ? $id : [$id]], $own, 'array');
        } else {
            $data = $service->getInvoiceList($params);
        }
        if ($data['error_code'] == 0) {
            // 处理数据为我们公共组件所需数据结构
            if (isset($data['data']['total']) && $data['data']['total'] > 0 && (!isset($data['data']['infos']) || !$data['data']['infos'])){
                $params['page'] = ceil($data['data']['total'] / $params['limit']);
                return $this->getUnreimburseInvoiceList($params, $own);
            }
            $data['data']['list'] = $data['data']['infos'] ?? [];
            if ($data['data']['list']) {
                $types = $this->getTypes($params, $own);
                $types = array_column($types, NULL, 'key');
                foreach ($data['data']['list'] as $key => $item) {
                    // 可报销发票通过id导入去除非非有效发票
                    if (isset($params['search']['id']) && isset($params['search']['id'][0]) && isset($item['comm_info']['pro']['valid']) && !in_array($item['comm_info']['pro']['valid'], [1,2])){
                        unset($data['data']['list'][$key]);
                        $data['data']['total'] = 0;
                        continue;
                    }
                    if ($item && isset($item['fid'])) {
                        $item['fid'] = number_format((double)$item['fid'], 0 ,'', '');
                        $item['buyer'] = $item['comm_info'] && $item['comm_info']['buyer'] ? $item['comm_info']['buyer'] : [];
                        $item['payer'] = $item['comm_info'] && $item['comm_info']['payer'] ? $item['comm_info']['payer'] : [];
                        if ($item && $item['comm_info'] && $item['comm_info']['pro'] && $item['comm_info']['pro']['type'] && !$item['comm_info']['pro']['cname']) {
                            $cname = isset($types[$item['comm_info']['pro']['type']]) && $types[$item['comm_info']['pro']['type']]['value'] ? $types[$item['comm_info']['pro']['type']]['value'] : '';
                            $item['comm_info']['pro']['cname'] = $this->returnCname($item['comm_info']['pro']['type'], $cname);
                        }
                        if (!$item['code']){
                            if (!$item['number']) {
                                $type = $item['comm_info'] &&  $item['comm_info']['pro'] && $item['comm_info']['pro']['cname'] ? $item['comm_info']['pro']['cname'] : '';
                                $buyer = $item['comm_info']['buyer']['company'];
                                $total = $item['comm_info'] && $item['comm_info']['price'] && $item['comm_info']['price']['total'] ? $item['comm_info']['price']['total'] : '0';
                                $item['code_value'] = $type . ($buyer ? '/'. $buyer : '') . ($total ? '/'.$total : '');
                            } else {
                                $item['code_value'] = $item['number'];
                            }
                        } else {
                            $item['code_value'] = $item['code'];
                        }
                        $data['data']['list'][$key] = $item;
                    }
                }
            }
            unset($data['data']['infos']);
            $data['data']['list'] = array_values($data['data']['list']);
            $data['data']['count'] = 0;
            if ($data['data']['total']) {
                $data['data']['count'] = $data['data']['total'];
            } else {
                $data['data']['count'] = count($data['data']['list']);
            }
            return $data['data'];
        } else {
//            return ['code' =>[ '', $data['message']]];
            return ['list' => [], 'count' => 0];
        }
    }

    public function getUseInvocieCloudService($user, $type = null, $configId = 0)
    {
        if (!$configId) {
            $param = ['is_use' => [1]];
            $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, $param);
        } else {
            $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceInvoiceCloud($configId);
        }
        if (!$config || !$config->type) {
            return ['code' => ['can_not_get_invoice_cloud', 'invoice']];
        }
        try{
            switch($config->type)
            {
                case '1':// 发票云
                    // if ($type == 'valid' && (!isset($config->teamsYun) || !isset($config->teamsYun->valid_on_off) || $config->teamsYun->valid_on_off != 0)) {
                    //     return ['code' => ['valid_not_open', 'invoice']];
                    // }
                    $service = new $this->invoiceCloudService($config->teamsYun, $user);
                    break;
                default:
                    return ['code' => ['can_not_get_invoice_cloud', 'invoice']];
            }
        } catch(\Exception $e) {
            return ['code' => ['', $e->getMessage()], 'dynamic' => $e->getMessage()];
        }
        return $service;
    }

    /** 获取发票详情
     * @param $params
     * @param $user
     * @return array
     */
    public function getInvoice($params, $user, $service = null)
    {
        if (!$service) {
            $service = $this->getUseInvocieCloudService($user);
            if (is_array($service)) {
                return $service;
            }
        }
        $serviceUrl = str_replace('invoiceApi', 'noauth', $service->url);
        $invoice = Cache::get('invoice_detail_'. $params['id']);
        if (!$params['id'] && $invoice && $invoice['origin_fid'] == '0') {
            $data['error_code'] = 0;
            $data['data']['invoice'] = $invoice;
        } else {
            if (isset($params['corp']) && ($params['corp'] === true || $params['corp'] == 'true')) {
                $data = $service->getInvoiceCorp($params);
            } else {
                $data = $service->getInvoice($params);
            }
            if ($data['error_code'] == 0) {
                $invoice = $data['data'];
                if ($invoice && $invoice['modify_info'] && ($invoice['modify_info']['furl'] || $invoice['modify_info']['pdf'])) {
                    $imageIndex = $invoice['modify_info']['furl'] ? $invoice['modify_info']['furl'] : '';
                    $pdfIndex = $invoice['modify_info']['pdf'] ? $invoice['modify_info']['pdf'] : '';
                    $invoice['modify_info']['attachment_id'] = '';
                    $invoice['modify_info']['url'] = '';
                    if (!empty($imageIndex)) {
                        if (strpos($imageIndex, 'http') !== false) {
                            $invoice['modify_info']['url'] = !empty($imageIndex) ? $imageIndex : '';
                        } else {
                            if (strpos($service->url, 'mypiaojia')){
                                $invoice['modify_info']['url'] = $imageIndex ? 'https://dl.mypiaojia.com/noauth/'. $imageIndex : '';
                            } else {
                                $invoice['modify_info']['url'] = $imageIndex ? $serviceUrl. $imageIndex : '';
                            }
                        }
                    }

                    if (!empty($pdfIndex)) {
                        if (strpos($pdfIndex, 'http') !== false) {
                            $invoice['modify_info']['pdfUrl'] = !empty($pdfIndex) ? $pdfIndex : '';
                        } else {
                            if (strpos($service->url, 'mypiaojia')){
                                $invoice['modify_info']['pdfUrl'] = $pdfIndex ? 'https://dl.mypiaojia.com/noauth/'. $pdfIndex : '';
                            } else {
                                $invoice['modify_info']['pdfUrl'] = $pdfIndex ? $serviceUrl. $pdfIndex : '';
                            }
                        }
                    }
                    
                    // if (!$invoice['modify_info']['url']) {
                        $log = app($this->invoiceLogsService)->getLogByImageIndex($pdfIndex);
                        if (isset($log->attachment_id)) {
                            $invoice['modify_info']['attachment_id'] = $log->attachment_id;
                            // $invoice['modify_info']['url'] = $log->image_index_url;
                        }
                    // }
                }
                $invoice['comm_info']['pro']['cname'] = '';
                if ($invoice && $invoice['comm_info'] && $invoice['comm_info']['pro'] && $invoice['comm_info']['pro']['type'] && !$invoice['comm_info']['pro']['cname']) {
                    $types = $this->getTypes($params, $user);
                    $types = array_column($types, NULL, 'key');
                    $cname = isset($types[$invoice['comm_info']['pro']['type']]) && $types[$invoice['comm_info']['pro']['type']]['value'] ? $types[$invoice['comm_info']['pro']['type']]['value'] : '';
                    $invoice['comm_info']['pro']['cname'] = $this->returnCname($invoice['comm_info']['pro']['type'], $cname);
                }
                if (!$invoice['code']){
                    if (!$invoice['number']) {
                        $type = $invoice['comm_info'] &&  $invoice['comm_info']['pro'] && $invoice['comm_info']['pro']['cname'] ? $invoice['comm_info']['pro']['cname'] : '';
                        $buyer = $invoice['comm_info']['buyer']['company'];
                        $total = $invoice['comm_info'] && $invoice['comm_info']['price'] && $invoice['comm_info']['price']['total'] ? $invoice['comm_info']['price']['total'] : '0';
                        $invoice['code_value'] = $type . ($buyer ? '/'. $buyer : '') . ($total ? '/'.$total : '');
                    } else {
                        $invoice['code_value'] = $invoice['number'];
                    }
                } else {
                    $invoice['code_value'] = $invoice['code'];
                }
                // 税额值计算
                $extInfo = isset($invoice['ext']) ? json_decode($invoice['ext'], 1) : [];
                $total = $invoice['comm_info']['price']['total'] = $invoice['comm_info']['price']['total'] ? sprintf('%01.2f', $invoice['comm_info']['price']['total']) : 0.00;
                $amount = $invoice['comm_info']['price']['amount'] = $invoice['comm_info']['price']['amount'] ? sprintf('%01.2f', $invoice['comm_info']['price']['amount']) : 0.00;
                $ttax = 0;
                if ($extInfo) {
                    $ttax = isset($extInfo['ttax']) ? sprintf('%01.2f', $extInfo['ttax']) : 0.00;
                    if ($ttax) {
                        $invoice['comm_info']['price']['ttax'] = $ttax;
                    } else {
                        $taxRate = $extInfo['trate'] ?? '';
                        if ($taxRate) {
                            $ttax = $total / (1 + $taxRate/100) * ($taxRate/100);
                            $extInfo['ttax'] = $ttax ? sprintf('%01.2f', $ttax) : 0.00;
                        }
                    }
                }
                if (!isset($extInfo['ttax']) || empty($extInfo['ttax'])) {
                    if ($invoice && $invoice['comm_info'] && $invoice['comm_info']['price'] && (!isset($invoice['comm_info']['price']['ttax']) || !$invoice['comm_info']['price']['ttax'])) {
                        $extInfo['ttax'] = $invoice['comm_info']['price']['ttax'] = $total && $amount ? sprintf('%01.2f',round($total - $amount, 2)) : 0.00;
                    }
                }
                $invoice['ext'] = json_encode($extInfo);
                if ($invoice && $invoice['comm_info'] && $invoice['comm_info']['price'] && $amount == 0) {
                    $invoice['comm_info']['price']['amount'] = sprintf('%01.2f',round($total - $ttax, 2));
                }
//                Cache::forever('invoice_detail_'. $invoice['id'], $invoice);
                unset($data['data']);
                $data['data']['invoice'] = $invoice;
            } else {
                $data['data']['invoice'] = new \stdClass();
            }
        }
        return $this->returnResult($data);
    }

    public function getInvoiceBatch($params, $user, $returnType = '', $service = null)
    {
        if (!$service) {
            $service = $this->getUseInvocieCloudService($user);
            if (is_array($service)) {
                return $service;
            }
        }
        $ids = $params['ids'] ?? [];
        if (is_string($ids)) {
            $ids = json_decode($ids, 1);
            if (isset($ids[0]) && is_array($ids[0])) {
                $ids = $ids[0];
            }
        }
        $infos = [];
        $data = ['error_code' => 0, 'data' =>[]];
        if ($ids) {
            $fids = [];
            foreach ($ids as $id) {
//                if ($invoice = Cache::get('invoice_detail_'. $id)) {
//                    $infos[] = [
//                        'ret' => 0,
//                        'info' => $invoice
//                    ];
//                } else {
                    $fids[] = $id;
//                }
            }
            if ($fids){
                $data1 = $service->getInvoiceBatch(['ids' => $fids]);
                if (isset($data1['data']) && isset($data1['data']['infos'])) {
                    if (isset($data1['error_code']) && $data1['error_code'] == 0) {
                        foreach ($data1['data']['infos'] as $info1) {
                            if ($info1['ret'] == 0) {
                                $invoice = $info1['info'] ?? [];
                                if (!$invoice['code']){
                                    if (!$invoice['number']) {
                                        $type = $invoice['comm_info'] &&  $invoice['comm_info']['pro'] && $invoice['comm_info']['pro']['cname'] ? $invoice['comm_info']['pro']['cname'] : '';
                                        $buyer = $invoice['comm_info']['buyer']['company'];
                                        $total = $invoice['comm_info'] && $invoice['comm_info']['price'] && $invoice['comm_info']['price']['total'] ? $invoice['comm_info']['price']['total'] : '0';
                                        $invoice['code_value'] = $type . ($buyer ? '/'. $buyer : '') . ($total ? '/'.$total : '');
                                    } else {
                                        $invoice['code_value'] = $invoice['number'];
                                    }
                                } else {
                                    $invoice['code_value'] = $invoice['code'];
                                }
                                $info1['info'] = $invoice;
                                $infos[] = $info1;
                            }
                        }
                    }
                }
            }
            $data['data']['infos'] = [];
                if ($returnType == 'array') {
                    foreach ($infos as $cacheInfo) {
                        if ($cacheInfo['ret'] == 0) {
                            $data['data']['infos'][] = $cacheInfo['info'];
                        }
                    }
                    $data['data']['total'] = count($data['data']['infos']);
                } else{
                foreach ($infos as $cacheInfo) {
                    if ($cacheInfo['ret'] == 0) {
                        $data['data']['infos'][] = $cacheInfo;
                }
            }
                $data = ['error_code' => 0, 'data' => ['infos' => $infos, 'total' => count($data['data']['infos'])]];
            }

        }
        if (!$returnType) {
            return $this->returnResult($data);
        } else {
            return $data;
        }
    }

    public function getInvoicesByIds($id, $user)
    {
        // 自定义字段使用可报销发票选择器时  默认100条
        if (isset($id) && !empty($id)) {
            if (strpos($id, ',')  !== false) {
                $id = explode(',', $id);
            }
            $data = $this->getInvoiceBatch(['ids' => is_array($id) ? $id : [$id]], $user, 'array');
            $return = isset($data['data']) && isset($data['data']['infos']) ? $data['data']['infos'] : [];
            return $return;
        } else {
            $data = $this->getUnreimburseInvoiceList(['limit' => 100], $user);
            return $data['list'];
        }
    }

    /** 获取发票抬头列表
     * @param $params
     * @param $user
     * @return array
     */
    public function getTitles($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return ['list' => [], 'count' => 0];
        }
        $pageSize = $params['limit'] ?? '';
        unset($params['limit']);
        $page = $params['page'];
        unset($params['page']);
        $data = $service->getInvoiceTitles($params);
        if ($data['error_code'] == 0) {
            if ($data['data']['total'] > 0 && (!isset($data['data']['datas']) || !$data['data']['datas']) && $pageSize){
                $page = ceil($data['data']['total'] / $pageSize);
            }
            // 处理数据为我们公共组件所需数据结构
            $data['data']['list'] = $data['data']['datas'] ?? [];
            if (isset($params['field'])){
                // 发票云暂不支持抬头列表筛选  方案：查询所有，按名称筛选处理数据
                $search = isset($params['search']) && !empty($params['search']) ? json_decode($params['search'], 1) : [];
                if (isset($search['multiSearch'])) {
                    $search = !empty($search['multiSearch']) ? $search['multiSearch'] : [];
                }
                if ($pageSize && $search && isset($search['name_tax_num']) && !empty($search['name_tax_num'])) {
                    $searchField = $search['name_tax_num'][0];
                    foreach ($data['data']['list'] as $key => $datum) {
                        if(strpos($datum['name'], $searchField) !== false) {
                            $data['data']['list'][$key]['name_tax_num'] = $datum['name']. '/' .$datum['tax_num'];
                        } else {
                            unset($data['data']['list'][$key]);
                        }
                    }
                } else {
                    foreach ($data['data']['list'] as $key => $datum) {
                        $data['data']['list'][$key]['name_tax_num'] = $datum['name']. '/' .$datum['tax_num'];
                    }
                }
            }
            unset($data['data']['datas']);
            $data['data']['count'] = count($data['data']['list']) ?? 0;
            if ($pageSize) {
                $data['data']['list'] = array_slice($data['data']['list'], ($page - 1) * $pageSize, $pageSize);
            }
            return $data['data'];
        } else {
//            if (isset($data['code'])) {
//                return $data;
//            }
            return ['list' => [], 'count' => 0];
        }
    }

    /** 获取发票抬头列表
     * @param $params
     * @param $user
     * @return array
     */
    public function getPersonalTitles($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return ['list' => [], 'count' => 0];
        }
        $data = $service->getPersonalInvoiceTitles($params);
        if ($data['error_code'] == 0) {
            if ($data['data']['total'] > 0 && (!isset($data['data']['datas']) || !$data['data']['datas'])){
                $params['page'] = ceil($data['data']['total'] / $params['limit']);
                return $this->getPersonalTitles($params, $user);
            }
            // 处理数据为我们公共组件所需数据结构
            $data['data']['list'] = $data['data']['datas'] ?? [];
            unset($data['data']['datas']);
            $data['data']['count'] = $data['data']['total'] ?? 0;
            $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, ['is_use' => [1]]);
//            $corpTaxno = isset($config->teamsYun) && isset($config->teamsYun['corp_taxno']) ? $config->teamsYun['corp_taxno'] : 0;
            $corpTaxno = 1;
            $corpTaxnos = isset($config->teamsYun) && isset($config->teamsYun['corp_taxnos']) ? json_decode($config->teamsYun['corp_taxnos'], 1) : [];
            foreach ($data['data']['list'] as $key => $datum) {
                if ($corpTaxno == 0 || !$corpTaxnos) {
                    $data['data']['list'][$key]['can_reimburse'] = 1;
                    continue;
                } else {
                    $data['data']['list'][$key]['can_reimburse'] = 0;
                    if ($corpTaxnos) {
                        foreach($corpTaxnos as $oneCorpTaxno){
                            if ($oneCorpTaxno['name'] == $datum['name'] && $oneCorpTaxno['tax_num'] == $datum['tax_num']) {
                                $data['data']['list'][$key]['can_reimburse'] = 1;
                                break;
                            }
                        }
                    }
                }
            }
            return $data['data'];
        } else {
            return ['list' => [], 'count' => 0];
        }
    }

    /** 获取发票抬头详情
     * @param $params
     * @param $user
     * @return array
     */
    public function getTitle($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->invoiceTitle($params);
        if ($data['error_code'] == 0) {
            // 处理数据为我们公共组件所需数据结构
            return $data['data'];
        } else {
            if (isset($data['code'])) {
                return $data;
            }
            return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
        }
    }

    /** 创建团队/企业
     * @param $params
     * @param $user
     * @return array
     */
    public function createCorp($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->createCorp($params);
        if ($data['error_code'] == 0) {
            // 保存团队信息
            $params['cid'] = $data['data'];
            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig($params, ['config_id' => [$service->configId]]);
            $this->addDefaultInterface($user, ['cid' => $data['data']]);
            return $data['data'];
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    public function updateCorp($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->updateCorp($params);
        if ($data['error_code'] == 0) {
            // 保存团队信息 -- 更新团队信息
//            $crop = $service->QueryCorpByCid(['cid' => ]);
            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig($params, ['config_id' => [$service->configId]]);
            return $data['data'];
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    /** 根据账号查团队信息
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function QueryCorpByAccount($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->queryByAccount($params);
        return $this->returnResult($data);
    }

    public function QueryCorpAll($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user, [], $params['configId'] ?? 0);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->queryAll($params);
        return $this->returnResult($data);
    }

    /** 根据团队id查团队信息
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function QueryCorpByCid($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->QueryCorpByCid($params);
        return $this->returnResult($data);
    }

    /** 获取应用详情
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function checkAppInfo($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->checkAppInfo($params);
        return $this->returnResult($data);
    }

    /** t同步单个用户
     * @param $params
     * @param $user
     * @return array
     */
    public function updateUser($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $log = [
            'creator' => $user['user_id'],
            'type' => '2', // 更新用户
            'request_data' => $params,
            'request_api_data' => $params,
        ];
        $data = $service->updateUser($params);
        if ($data['error_code'] == 0) {
            if (isset($params['user_name'])) {
                $relatedUser = [
                    'name' => $params['user_name'],
                    'state' => $params['state']
                ];
                unset($params['role']);
                $log['request_data'] = $log['request_api_data'] = $params;
            }
            // 发票云接口直接修改角色 -- 安全部门测试认为是BUG，现调整为 调整角色时清除token重新生成token时传入角色获取对应角色的token 20201126
            if (isset($params['role'])) {
                $role = $params['role'] ?? '1';
                $relatedUser = [
                    'role' => $role
                ];
                $log['type'] = $role == '1' ? '6' : '7'; // 6 设为普通成员  7 设为管理员
                Cache::pull('invoice_cloud_token_teamsyun_' . $params['userId']);
                Cache::pull('invoice_cloud_teamsyun_users_'. $service->cid);
            }
            app($this->invoiceCloudTeamsYunUserRepositories)->updateData($relatedUser, ['userId' => $params['userId']]);
        }
        // 单个返回发票云用户id
        return $this->returnResult($data, $log);
    }

    public function syncUser($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $log = [
            'creator' => $user['user_id'],
            'type' => '1', // 同步用户
            'request_data' => $params,
            'request_api_data' => $params,
        ];
        // 先通过账号查找 找不到新增
        $exist = $service->getInvoiceUser(['account' => $params['account']]);
        if ($exist['error_code'] == 0) {
            $data = $exist;
            $data['data'] = $exist['data']['userId'];
            $params['name'] = $exist['data']['name'];
            $params['role'] = $exist['data']['role'];
        } else {
            $params['role'] = $params['role'] ?? 1;
            $data = $service->syncUser($params);
        }
        $log['result_data'] = $data;
        // 单个返回发票云用户id
        if ($data['error_code'] == 0) {
            $relatedUser = [
                'userId' => $data['data'],
                'user_id' => $params['user_id'],
                'name' => $params['name'],
                'account' => $params['account'],
                'role' => $params['role'] ?? 1,
                'cid' => $service->cid,
                'state' => $params['state'] ?? 1,
            ];
            $res = app($this->invoiceCloudTeamsYunUserRepositories)->insertData($relatedUser);
            app($this->invoiceLogsService)->addLog([$log]);
            $relatedUser['created_at'] = date('Y-m-d H:i:s');
            Cache::pull('invoice_cloud_teamsyun_users_'.$service->cid);
            return $res ? $res->toArray() : $relatedUser;
        }
        $log['error_message'] = $data['message'];
        app($this->invoiceLogsService)->addLog([$log]);
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    public function batchSyncUserSchedule($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
//        $params['service'] = $service;
        $flag = Cache::get('invoice_first_sync_'. $service->cid);
        if ($flag) {
            return ['code' => ['user_is_sync', 'invoice']];
        } else {
            Cache::put('invoice_first_sync_'. $service->cid, true, 1);
        }
        Queue::push(new SyncInvoiceManageJob(['type' => 'user', 'userInfo' => $user, 'param' => $params]));
//        $this->batchSyncUser($params, $user);
        return true;
    }

    /** 批量同步用户
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function batchSyncUser($params, $user, $service = null)
    {
        $type = 3; // 手动批量同步
        if (!empty($params) && isset($params['type'])){
            $type = $params['type'] == 'sync' ? '4' : ($params['type'] == 'first' ? '5' : '3'); // 自动批量同步   5 首次自动同步用户
            unset($params['type']);
        }
        if (!$service) {
            $service = $this->getUseInvocieCloudService($user);
            if (is_array($service)) {
                return $service;
            }
        }
        $users = app($this->userService)->userSystemList();
        $users = $users['list'] ?? [];
        // 获取所有用户整理为需要的参数格式  --- 去除已同步的
        $yunUserArray = Cache::get('invoice_cloud_teamsyun_users_'.$service->cid, null);
        if ($yunUserArray) {
            $yunUserArray = array_column($yunUserArray, NULL, 'user_id');
        }
        if ($type == 5 || !$yunUserArray) {
            $yunUserArray = app($this->invoiceCloudTeamsYunUserRepositories)->getAll($service->cid);
            $yunUserArray = array_column($yunUserArray, NULL, 'user_id');
        }
        $yunUsers = $yunUserArray ? array_column($yunUserArray, 'user_id') : [];
        $updateParams = [];
        foreach ($users as $key => $item) {
            $oldParams[] = [
                'account' => $item['user_accounts'],
                'name' => $item['user_name'],
                'state' => 1
            ];
            if (!$yunUsers || !in_array($item['user_id'], $yunUsers)){
                $params[] = [
                    'account' => $item['user_accounts'],
                    'name' => $item['user_name'],
                    'state' => 1
                ];
            } else {
                // 用户名或状态变化的 更新信息至发票云
                if (in_array($item['user_id'], $yunUsers) && $yunUser = $yunUserArray[$item['user_id']]) {
                    if ($yunUser['name'] != $item['user_name']) {
                        $updateParams[] = [
                            'userId' => $yunUser['userId'],
                            'name' => $item['user_name']
                        ];
                    }
                    $userStatus = $item['user_has_one_system_info']['user_status'] != 2 ? 1 : 2;
                    if ($userStatus != $yunUserArray[$item['user_id']]['state']) {
                        $updateParams[] = [
                            'userId' => $yunUser['userId'],
                            'state' => $userStatus
                        ];
                    }
                }
            }
            if (!$item['user_accounts'] || (isset($item['user_has_one_system_info']) && isset($item['user_has_one_system_info']['user_status']) && $item['user_has_one_system_info']['user_status'] == 2)) {
                unset($user[$key]);
            }
        }
        // 以账号为键名数组
        $userAccounts = array_column($users, 'user_id', 'user_accounts');
        $log = [
            'creator' => $user['user_id'],
            'type' => $type,
            'request_data' => $params
        ];
        // 返回的user不存在即为离职用户
        $leavingUsers = array_diff(array_keys($yunUserArray), $userAccounts);
        if ($leavingUsers) {
            foreach($leavingUsers as $leavingUser) {
                $yunUser = $yunUserArray[$leavingUser];
                $updateParams[] = [
                    'userId' => $yunUser['userId'],
                    'state' => 2
                ];
            }
        }
        if ($updateParams) {
            foreach ($updateParams as $updateParam) {
                $updateData = $service->updateUser($updateParam);
                // 离职删除对应信息
                if ($updateData['error_code'] == 0) {
//                    if ($updateParam['state'] == 1) {
                        app($this->invoiceCloudTeamsYunUserRepositories)->updateData($updateParam, ['userId' => $updateParam['userId']]);
//                    } else {
//                        app($this->invoiceCloudTeamsYunUserRepositories)->deleteByWhere(['userId' => [$updateParam['userId']]]);
//                    }
                }
            }
        }
        if ($params) {
//            if ($type == 5) {
            // 人员数量大时 全部同步会超时  50人同步一次
                $params = array_chunk($params, 50);
                $data = [
                    'error_code' => 0,
                    'data' => [
                        'suc' => [],
                        'err' => []
                    ],
                ];
                foreach ($params as $info) {
                    $res = $service->batchSyncUser($info);
                    if (isset($res['data'])){
                        $data['data']['suc'] = isset($res['data']['suc']) ? array_merge($data['data']['suc'], $res['data']['suc']) : $data['data']['suc'];
                        $data['data']['err'] = isset($res['data']['err']) ? array_merge($data['data']['err'], $res['data']['err']) : $data['data']['err'];
                    }
                }
//            } else {
//                $data = $service->batchSyncUser($params);
//            }
            $log['result_data'] = $data;
        } else {
            $log['request_data'] = $oldParams;
            unset($oldParams);
            $log['result_data'] = ['error_code' => 0, 'message' => trans('invoice.user_already_sync')];
            $log['error_message'] = trans('invoice.user_already_sync');
            app($this->invoiceLogsService)->addLog([$log]);
            // 系统消息提醒
            $this->sendMessage('invoice-user', $user);
            Cache::pull('invoice_first_sync_'. $service->cid);
            return ['code' => ['user_already_sync', 'invoice']];
        }

        $relatedUsers = [];
        // 返回结果有成功有失败 成功的
        $time = date('Y-m-d H:i:s');
        if ($data['error_code'] == 0) {
            if (isset($data['data']) && isset($data['data']['suc']) && !empty(($data['data']['suc']))) {
                $relatedUsers = [];
                foreach($data['data']['suc'] as $suc) {
                    $relatedUsers[] = [
                        'userId' => $suc['userId'],
                        'account' => $suc['account'],
                        'name' => $suc['name'],
                        'user_id' => $userAccounts[$suc['account']],
                        'role' => $suc['role'] ?? 1,
                        'state' => $suc['state'] ?? 1,
                        'cid' => $service->cid,
                        'created_at' => $time,
                        'updated_at' => $time
                    ];
                }
            } else {
                $log['error_message'] = trans('invoice.user_sync_failed');
            }
            if (isset($data['data']) && isset($data['data']['err']) && !empty(($data['data']['err']))) {
                $log['error_message'] = trans('invoice.user_sync_failed');
                // 失败人员信息
                $errorAccounts = [];
                $errorMessages = [];
                foreach ($data['data']['err'] as $err) {
                    $actionMsg = $err['actionMsg'] ?? [];
                    $actionData = $err['data'] ?? [];
                    if (isset($actionData) && !empty($actionData['userId'])) {
                        $relatedUsers[] = [
                            'userId' => $actionData['userId'],
                            'account' => $actionData['account'],
                            'name' => $actionData['name'],
                            'user_id' => $userAccounts[$actionData['account']],
                            'role' => $suc['role'] ?? 1,
                            'state' => $suc['state'] ?? 1,
                            'cid' => $service->cid,
                            'created_at' => $time,
                            'updated_at' => $time
                        ];
                    }
                    if ($actionMsg) {
                        if($actionMsg['code'] == 407){
                            $errorMessages[$actionMsg['code']] = $actionMsg['message'] . trans('invoice.user_sync_yet');
                        } else {
                            $errorMessages[$actionMsg['code']] = $actionMsg['message'];
                        }
                        $errorAccounts[$actionMsg['code']][] = $err['data']['name'] ?? '';
                    }
                }
                if ($relatedUsers) {
                    $this->deleteUser($relatedUsers, $service->cid);
                    foreach(array_chunk($relatedUsers, 100) as $relatedUser) {
                        app($this->invoiceCloudTeamsYunUserRepositories)->insertMultipleData($relatedUser);
                    }
                    Cache::pull('invoice_cloud_teamsyun_users_'. $service->cid);
                }
                if ($errorAccounts && $errorMessages) {
                    $errorMessages = array_unique($errorMessages);
                    if (count($errorMessages) == 1 && isset($errorAccounts['407'])) {
                        $log['error_message'] = '';
                    } else {
                    foreach ($errorMessages as $key => $value) {
//                        $log['error_message'] .= '，'. $value. ': '. implode('/', $errorAccounts[$key]);
                        $log['error_message'] .= '，'. $value;
                    }
                }
                }
                app($this->invoiceLogsService)->addLog([$log]);
                $this->sendMessage('invoice-user', $user);
                Cache::pull('invoice_first_sync_'. $service->cid);
                return ['code' => ['user_sync_failed', 'invoice']];
            } else {
                if ($relatedUsers) {
                    $this->deleteUser($relatedUsers, $service->cid);
                    app($this->invoiceCloudTeamsYunUserRepositories)->insertMultipleData($relatedUsers);
                    Cache::pull('invoice_cloud_teamsyun_users_'. $service->cid);
                }
                app($this->invoiceLogsService)->addLog([$log]);
            }
        } else {
            $log['error_message'] = $data['message'] ?? '';
            app($this->invoiceLogsService)->addLog([$log]);
        }
        // 首次同步 将当前操作用户发票云角色设为管理员 -- 
        if ($type == 5) {
            $userId = $service->getInvoiceUserId($user['user_id']);
            if ($userId) 
                $this->updateUser(['role' => 2, 'userId' => $userId], $user);
                $this->syncCorpConfig($user);
        }
        Cache::pull('invoice_first_sync_'. $service->cid);
        // 系统消息提醒
        $this->sendMessage('invoice-user', $user);
        return true;
    }

    /** 返回的数据插入数据库先删除 处理掉 用户已通过后OA端删除后重新创建同名的数据，同名数据的发票云userId一样 导致插入数据库失败
     * @param $relatedUsers
     * @param $cid
     * @return mixed
     */
    private function deleteUser($relatedUsers, $cid)
    {
        $deleteUser = array_column($relatedUsers, 'userId');
        return app($this->invoiceCloudTeamsYunUserRepositories)->deleteByWhere(['cid' => [$cid, '='], 'userId' => [$deleteUser, 'in']]);
    }

    private function sendMessage($remindMark, $user)
    {
        Eoffice::sendMessage([
            'remindMark' => $remindMark,
            'toUser' => [$user['user_id']]
        ]);
    }

    /** 开启/关闭发票验真功能
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function openValid($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->openValid($params);
        if ($data['error_code'] == 0) {
            // 保存到本地
            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig(['valid_on_off' => $params['valid']], ['config_id' => [$service->configId]]);
            return $data['data'] ?? true;
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    /** 开启/关闭发票识别
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function openRecognize($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        if ($service->role != 2) {
            return $service->role == 1 ? ['code' => ['invoice_cloud.405', 'invoice']] : ['code' => ['user_not_sync_invoice_cloud', 'invoice']];
        }
        $result = app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig(['recognize_on_off' => $params['recognize_on_off']], ['config_id' => [$service->configId]]);
        return $result ? true : ['code' => ['operate_failed', 'invoice']];
    }

    public function getInvoiceUser($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->getInvoiceUser($params);
        if ($data['error_code'] == 0) {
            return $data['data'] ?? true;
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    /** 开启发票识别自动验真功能
     * @param $params
     * @param $user
     * @return array
     */
    public function openAutoValid($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->openAutoValid($params);
        if ($data['error_code'] == 0) {
            // 保存到本地
            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig(['recognize_valid_on_off' => $params['auto_valid']], ['config_id' => [$service->configId]]);
            return $data['data'];
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    /** 手动添加发票
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function addInvoice($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->addInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => '21', // 2 发票变化  21 新增  22  编辑  23  删除
            'request_data' => $params,
            'result_data' => $data,
        ];
        return $this->changeStatusReturnResult($data, $log);
    }

    /** 更新发票
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function updateInvoice($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        if (is_string($params['info'])) {
            $params['info'] = json_decode($params['info'], 1);
        }
//        if ($params['type'] == 'mobile') {
//            dd($params);
//        }
        $data = $service->updateInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => '22', // 2 发票变化  21 新增  22  编辑  23  删除
            'request_data' => $params,
            'result_data' => $data,
            'invoice_id' => $params['info']['id'] ?? ''
        ];
//        if (isset($params['info']['id'])) {
//            Cache::pull('invoice_detail_'. $params['info']['id']);
//        }
        return $this->returnResult($data, $log, $service);
    }

    /** 删除发票
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function deleteInvoice($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $ids = $params['ids'];
        $params['ids'] = explode(',', $ids);
        $data = $service->deleteInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => '23', // 2 发票变化  21 新增  22  编辑  23  删除
            'request_data' => $params,
            'result_data' => $data,
            'invoice_id' => $ids
        ];
        return $this->returnResult($data, $log, $service);
    }

    /** 新建发票抬头
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function addInvoiceTitle($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->addInvoiceTitle($params);
        return $this->returnResult($data);
    }

    /** 更新发票抬头
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function updateInvoiceTitle($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->updateInvoiceTitle($params);
        return $this->returnResult($data);
    }

    /** 删除发票抬头
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function deleteInvoiceTitle($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->deleteInvoiceTitle($params);
        return $this->returnResult($data);
    }

    /** 锁定发票 --- 开始发票报销
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function lockInvoice($params, $user, $runId)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->lockInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => 41, // 4 发票状态变化  41 锁定  42  核销  43  取消锁定
            'request_data' => $params,
            'result_data' => $data,
            'run_id' => $runId,
        ];
        return $this->changeStatusReturnResult($data, $log, $service);
    }

    /** 发票报销完成
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function reimbursedInvoice($params, $user, $runId)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->reimbursedInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => 42, // 4 发票状态变化  41 锁定  42  核销  43  取消锁定
            'request_data' => $params,
            'result_data' => $data,
            'run_id' => $runId,
        ];
        return $this->changeStatusReturnResult($data, $log, $service);
    }

    /** 取消报销发票
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function cancelInvoice($params, $user, $runId)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->cancelInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => 43, // 4 发票状态变化  41 锁定  42  核销  43  取消锁定
            'request_data' => $params,
            'result_data' => $data,
            'run_id' => $runId,
        ];

        return $this->changeStatusReturnResult($data, $log, $service);
    }

    /** 发票状态改变 --- 由于批量操作，返回结果需处理 日志记录
     * @param $data
     * @param $log
     * @return array|bool
     */
    public function changeStatusReturnResult($data, $log, $service = null)
    {
        if ($data['error_code'] == 0) {
            $infos = $data['data']['infos'] ?? [];
            $logs = [];
            $errorMessage = '';
            if ($log) {
                foreach($infos as $info) {
                    $log['result_data'] = $info;
                    $log['invoice_id'] = $info['id'] ?? '';
                    if (isset($log['type']) && in_array($log['type'], [41, 42, 43])) {
//                        $log['invoice_id'] = isset($info['id']) ? number_format($info['id'], 0 ,'', ''): '';
                        if ($info['ret'] != 0) {
                            $message = trans('invoice.invoice_cloud.' . $info['ret']);
                            $infoMessage = $info['message'] ?? '';
                            $info['message'] = $message == 'invoice.invoice_cloud.' . $info['ret'] ? ($infoMessage ?? trans('invoice.invoice_cloud.other').$info['ret']) : $message;
                        }
                    } else if(isset($log['type']) && in_array($log['type'], [21])) {
                        $log['invoice_id'] = $info['info']['id'] ?? '';
                        if (isset($info['ret']) && $info['ret'] != 0) {
                            $message = trans('invoice.invoice_cloud.' . $info['ret']);
                            $infoMessage = $info['message'] ?? '';
                            $errorMessage = $message == 'invoice.invoice_cloud.' . $info['ret'] ? ($infoMessage ?? trans('invoice.invoice_cloud.other').$info['ret']) : $message;
                        }
                    }
                    // 清掉对应发票详情的缓存
//                    Cache::pull('invoice_detail_'. $log['invoice_id']);
//                    if ($service && $log['invoice_id']) {
//                        $this->getInvoice(['id' => $log['invoice_id']], null, $service);
//                    }
                    $log['error_message'] = $info['message'] ?? ($errorMessage ?? '');
                    $logs[] = $log;
                }
            }
            if ($logs) {
                app($this->invoiceLogsService)->addLog($logs);
            }
            if ($errorMessage) {
                $infos['message'] = $errorMessage;
            } else {
                $infos['message'] = '';
            }
            return $infos ?? true;
        } else {
            if ($log) {
                $log['error_message'] = $data['message'];
                app($this->invoiceLogsService)->addLog([$log]);
            }
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }


    /** 上传图片发票识别
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function invoiceFileUploadOld($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        // 保存操作日志  ---  对应附件id
        $log = [
            'creator' => $user['user_id'],
            'type' => '11', // 发票识别
            'request_data' => $params,
        ];
        // 处理图片信息
        $newParams = [];
        if (isset($params['attachments']) && isset($params['attachments'][0])) {
            $attachmentUrl = app($this->attachmentService)->getOneAttachmentById($params['attachments'][0]);
            $newParams['file'] = fopen($attachmentUrl['temp_src_file'], 'r');
            $log['attachment_id'] = $params['attachments'][0];
        } else {
            return ['code' => ['get_upload_photo_failed', 'invoice']];
        }
        $data = $service->invoiceFileUpload($newParams);
        $log['result_data'] = $data;
        return $this->returnResult($data, $log);
    }

    public function invoiceFileUpload($params, $user)
    {
        // 保存操作日志  ---  对应附件id
        $log = [
            'creator' => $user['user_id'],
            'type' => '11', // 发票识别
            'request_data' => $params,
        ];
//        if (isset($params['attachments']) && isset($params['attachments'][0])) {
//            $attachmentUrl = app($this->attachmentService)->getOneAttachmentById($params['attachments'][0]);
//            $newParams['file'] = fopen($attachmentUrl['temp_src_file'], 'r');
//            if (filesize($attachmentUrl['temp_src_file']) > 10 * 1024 * 1024) {
//                return ['code' => ['upload_photo_size_too_large', 'invoice']];
//            }
//            $log['attachment_id'] = $params['attachments'][0] ?? '';
//        } else {
//            return ['code' => ['get_upload_photo_failed', 'invoice']];
//        }
//        // 先上传文件，再通过文件id识别发票
//        $data = $service->fileUpload($newParams);
//        if ($data['error_code'] == 0) {
//            $imageInfo = $data['data'] ?? [];
//            $imageIndex = $imageInfo['id'] ?? '';
//            $invoices = $service->recognizeInvoice(['image_index' => $imageIndex]);
//            $invoices['image_info'] = $imageIndex;
//            $log['image_index'] = $imageIndex;
//            $log['image_index_url'] = $imageInfo['url'] ?? '';
//            $log['result_data'] = $invoices;
//            $invoices['suc_nums'] = $invoices['fail_nums'] = 0;
//            $invoices['fail_errors'] = [];
//            if (isset($invoices['data']['infos'])) {
//                foreach ($invoices['data']['infos'] as $key => $invoice) {
//                    $invoices['data']['infos'][$key]['message'] = $invoice['ret'] == 0 ? '识别成功' : trans('invoice.invoice_cloud.' . $invoice['ret']);
//                    if ($invoice['ret'] == 0) {
//                        $invoices['suc_nums'] += 1;
//                    } else {
//                        $invoices['fail_nums'] += 1;
//                        $invoices['fail_errors'][] = trans('invoice.invoice_cloud.' . $invoice['ret']);
//                    }
//                }
//            }
//            $log['error_message'] = '识别成功' . $invoices['suc_nums'] . '条，' . '识别失败' .$invoices['fail_nums']. '条'. ($invoices['fail_nums'] > 0 ? '，失败原因：' . implode('、', $invoices['fail_errors']) : '') ;
//            return $this->returnResult($invoices, $log);
//        } else {
//            $log['result_data'] = $data;
//            return $this->returnResult($data, $log);
//        }
        if (isset($params['attachments'])) {
            $successNum = $failNum = 0;
            $errorMessage = [];
            if (isset($params['queue']) && $params['queue'] == 1) {
                $type = 'recognize';
                Queue::push(new SyncInvoiceManageJob(compact('params', 'user', 'log', 'successNum', 'failNum', 'errorMessage', 'type')));
                return true;
            } else {
                $sync = 1;
                return $this->recognizeInvoice(compact('params', 'user', 'log', 'successNum', 'failNum', 'errorMessage', 'sync'));
            }
        } else {
            $log['error_message'] = trans('invoice.get_upload_photo_failed');
            $this->returnResult([], $log);
            return ['code' => ['', $log['error_message']], 'dynamic' => $log['error_message']];
        }
        return true;
    }

    public function recognizeInvoice($param)
    {
        $params = $param['params'];
        $user = $param['user'];
        $successNum = $param['successNum'];
        $failNum = $param['failNum'];
        $errorMessage = $param['errorMessage'];
        $log = $param['log'];
        $service = $this->getUseInvocieCloudService($param['user']);
        if (is_array($service)) {
            return $service;
        }
               // 需调整 --- 2021-03-18 嵌入发票云查验设置页面 获取自动查验需要请求发票云接口
               $configParam = $this->syncCorpConfigParam($user, ['sflag' => 2]);
               //        $autoValid = app($this->thirdPartyInvoiceCloudTeamsYunRepository)->getFieldValue('recognize_valid_on_off', ['config_id' => $service->configId]);
                       $autoValid = $configParam['recognize_valid_on_off'] ?? 0;
        // 处理图片信息
        $newParams = [];
        $count = count($params['attachments']) ?? 0;
        foreach ($params['attachments'] as $attachment) {
            $attachmentUrl = app($this->attachmentService)->getOneAttachmentById($attachment);
            $filePath = $attachmentUrl['attachment_base_path'] . $attachmentUrl['attachment_relative_path'] . 'thumb_original_' . $attachmentUrl['affect_attachment_name'] ;
            if (file_exists($filePath)) {
                $newParams['file'] = fopen($filePath, 'r');
            } else {
                $newParams['file'] = fopen($attachmentUrl['temp_src_file'], 'r');
            }
            if (filesize($attachmentUrl['temp_src_file']) > 10 * 1024 * 1024) {
                $log['error_message'] = trans('invoice.upload_photo_size_too_large');
                $errorMessage[] = $log['error_message'];
                $failNum += 1;
                $this->returnResult(['error_code' => 9999, 'message' => $log['error_message']], $log);
            } else {
                $log['attachment_id'] = $attachment ?? '';
                $data = $service->fileUpload($newParams);
                if ($data['error_code'] == 0  && isset($data['data']['id']) && !empty($data['data']['id'])) {
                    $imageInfo = $data['data'] ?? [];
                    $imageIndex = $imageInfo['id'] ?? '';
                    $invoices = $service->recognizeInvoice(['image_index' => $imageIndex, 'sync' => $param['sync'] ?? 0]);
                    $invoices['image_info'] = $imageIndex;
                    $log['image_index'] = $imageIndex;
                    $log['image_index_url'] = $imageInfo['url'] ?? '';
                    $log['result_data'] = $invoices;
                    $invoices['suc_nums'] = $invoices['fail_nums'] = 0;
                    $fail_errors = [];
                    $invoiceId = [];
                    if (isset($invoices['data']['infos'])) {
                        foreach ($invoices['data']['infos'] as $key => $invoice) {
                            $message = trans('invoice.invoice_cloud.' . $invoice['ret']) == 'invoice.invoice_cloud.'.$invoice['ret'] ? $invoice['message'] : trans('invoice.invoice_cloud.' . $invoice['ret']);
                            $invoices['data']['infos'][$key]['message'] = $invoice['ret'] == 0 ? trans('invoice.recognition_succeeded') : $message;
                            if (isset($invoice['info']['id'])) {
                                $invoiceId[] = $invoice['info']['id'];
                            }
                            if ($invoice['ret'] == 0) {
                                $invoices['suc_nums'] += 1;
                                $successNum += 1;
                                // 如果自动验真 进行验真操作
                                if ($autoValid && $count == 1) {
                                    $this->validInvoice([
                                        'id' => $invoice['info']['id']
                                    ], $user);
                                }
                            } else {
                                $invoices['fail_nums'] += 1;
                                $failNum += 1;
                                $message = trans('invoice.invoice_cloud.' . $invoice['ret']) == 'invoice.invoice_cloud.'.$invoice['ret'] ? $message : trans('invoice.invoice_cloud.' . $invoice['ret']);
                                if (!in_array($message, $fail_errors)){
                                    $fail_errors[] = $message;
                                }
                                if (!in_array($message, $errorMessage)){
                                    $errorMessage[] = $message;
                                }
                            }
                        }
                        if (isset($invoiceId) && !empty($invoiceId)) {
                            $log['invoice_id'] = implode(',', $invoiceId);
                        }
                        $log['error_message'] = trans('invoice.recognition_succeeded') . $invoices['suc_nums'] . trans('invoice.some') . trans('invoice.recognition_failed') .$invoices['fail_nums']. trans('invoice.some'). ($invoices['fail_nums'] > 0 ? trans('invoice.failed_reason') . implode('、', $fail_errors) : '') ;
                        $this->returnResult($invoices, $log);
                    } else {
                        $failNum += 1;
                        $message = trans('invoice.invoice_cloud.' . $invoices['error_code']) == 'invoice.invoice_cloud.'.$invoices['error_code'] ? $invoices['message'] : trans('invoice.invoice_cloud.' . $invoices['error_code']);
                        $errorMessage[] = $message;
                        $log['error_message'] = $message;
                        $this->returnResult($invoices, $log);
                    }
                } else {
                    $log['result_data'] = $data;
                    $failNum += 1;
                    if ($data['error_code'] == 0) {
                        $log['error_message'] = trans('invoice.upload_file_to_teamsyun_failed');
                        $errorMessage[] = $log['error_message'];
                    } else {
                        $errorMessage[] = trans('invoice.invoice_cloud.' . $data['error_code']) == 'invoice.invoice_cloud.'.$data['error_code'] ? $data['message'] : trans('invoice.invoice_cloud.' . $data['error_code']);
                    }
                    $this->returnResult($data, $log);
                }
            }
        }
        if ($successNum || $failNum) {
            if ($successNum > 0 && $failNum == 0) {
                $message = trans('invoice.recognition_succeeded');
            } else if ($successNum == 0 && $failNum > 0) {
                $message = trans('invoice.recognition_fail'). trans('invoice.failed_reason') . implode('、', $errorMessage);
            } else {
                $message = trans('invoice.recognition_succeeded') . $successNum . trans('invoice.some') . trans('invoice.recognition_failed') .$failNum. trans('invoice.some'). ($failNum > 0 ? trans('invoice.failed_reason') . implode('、', $errorMessage) : '') ;
            }
            return compact('successNum', 'failNum', 'message');
        }
    }

    /** 发票验真
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function validInvoice($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user, $type = 'valid');
        if (is_array($service)) {
            return $service;
        }
        $configValid = $this->syncCorpConfigParam($user, ['sflag' => 2], $service);
        if ($configValid['valid_on_off']) {
            return ['code' => ['valid_not_open', 'invoice']];
        }
        $data = $service->validInvoice($params);
//        $data = ['error_code' => 1675, 'message' => '发票在公司抬头下面不存在'];
        $log = [
            'creator' => $user['user_id'],
            'type' => '12', // 发票验真
            'request_data' => $params,
            'result_data' => $data,
            'invoice_id' => $params['id'] ?? ''
        ];
        return $this->returnResult($data, $log);
    }

    /** 手动录入四要素验真
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function validInputInvoice($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user, $type = 'valid');
        if (is_array($service)) {
            return $service;
        }
        $configValid = $this->syncCorpConfigParam($user, ['sflag' => 2], $service);
        if (!isset($configValid['valid_on_off']) || $configValid['valid_on_off']) {
            return ['code' => ['valid_not_open', 'invoice']];
        }
        $data = $service->validInputInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => '12', // 发票验真
            'request_data' => $params,
            'result_data' => $data,
        ];
        return $this->returnResult($data, $log);
    }

    function formatFid($fid)
    {
        return number_format($fid, 0 ,'', '');
    }

    /** 发票类型
     * @param $params
     * @param $user
     * @return array
     */
    public function getTypes($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return [];
        }
        $type = $params['type'] ?? 2;
        if ($values = Cache::get('invoice_sync_config_'.$type)) {
            $data['data'] = $values;
        } else {
            $data = $service->getTypes($params);
            if ($data['error_code'] == 0) {
                Cache::put('invoice_sync_config_'.$type, $data['data'], 60*2);
            } else {
                $data['data'] = [];
            }
        }
        if ($data['data']){
            $keys = array_column($data['data'],'key');
            array_multisort($keys,SORT_ASC, $data['data']);
        }
        $params = $this->parseParams($params);
        if (isset($params['search']) && isset($params['search']['key'])) {
            $keys = $params['search']['key'][0];
            if (!is_array($keys)) {
                $keys = [$keys];
            }
            foreach ($data['data'] as $itemKey => $item) {
                if (!in_array($item['key'], $keys)){
                    unset($data['data'][$itemKey]);
                }
            }
            $data['data'] = array_values($data['data']);
        }
        // 支持发票类型选择器 根据名称筛选
        if (isset($params['search']) && isset($params['search']['value'])) {
            foreach ($data['data'] as $itemKey => $item) {
                if (strpos( $item['value'],$params['search']['value'][0]) === false){
                    unset($data['data'][$itemKey]);
                }
            }
            $data['data'] = array_values($data['data']);
        }
        return $data['data'] ?? [];
    }

    /** 队列任务执行 -- 开始报销【锁定发票】  报销完成 【发票核销】  取消报销 【取消锁定】
     * @param $flowTurnType
     * @param $flowId
     * @param $runId
     * @param $flowProcess
     * @param $processId
     * @param $next_flow_process
     * @param $flowData
     * @param $flowFormDataParam
     * @param $userInfo
     */
    public function syncFlowOutsendInvoice($flowTurnType, $flowId, $runId, $flowProcess, $processId, $next_flow_process, $flowData, $flowFormDataParam, $userInfo, $runName = '')
    {
        $type = 'flow';
        $params = compact('flowTurnType', 'flowId', 'runId', 'flowProcess', 'processId', 'next_flow_process', 'flowData', 'flowFormDataParam', 'userInfo', 'type', 'runName');
        // 加判断 当前流程是否集成发票
        if (app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$flowId]])) {
            Queue::push(new SyncInvoiceManageJob($params));
        }
//        $this->flowOutsendInvoice($params);
    }

    public function flowOutsendInvoice($param)
    {
//        Log::info($param);
        extract($param);
        // 可外发的情况 1.提交不管是否退回 2.提交退回不触发 3.到达不管是否退回 4.到达退回不触发 5.仅退回
        //  reimburse 报销发票 -- 发票锁定   reimbursed 报销完成 -- 发票核销     cancel 取消报销 -- 取消锁定
        $methods = [
            'reimburse' => 'lockInvoice',
            'reimbursed' => 'reimbursedInvoice',
            'cancel' => 'cancelInvoice',
        ];
        $types = [
            'reimburse' => 41, // 发起报销--锁定发票
            'reimbursed' => 42, // 报销完成--发票核销
            'cancel' => 43  // 取消报销--取消锁定
        ];
        // 增加判断当前流程是否集成 是否开启
        $setting = app($this->invoiceFlowSettingRepositories)->getOneFieldInfo(['workflow_id' => $flowId]);
        if($setting) {
            $outsendInfos = app($this->invoiceFlowNodeActionSettingRepositories)->getFieldInfo(['workflow_id' => $flowId]);
            foreach (['reimburse', 'reimbursed', 'cancel'] as $typeVal) {
                $currentProcessSetInfo = $this->checkOutsend($outsendInfos, $flowProcess, $typeVal);
                // 下个节点到达触发
                $nestProcessSetInfo = $this->checkOutsend($outsendInfos, $next_flow_process, $typeVal);
                // 当前节点提交   // 仅退回到当前节点   // 到达下一节点触发   // 仅退回到下一节点
                if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingArrival'])) && (
                        ($currentProcessSetInfo['flowOutsendTimingArrival'] == 0 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($currentProcessSetInfo['back']) && $currentProcessSetInfo['back'] == 1))) || ($currentProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && (($nestProcessSetInfo['flowOutsendTimingArrival'] == 1 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($nestProcessSetInfo['back']) && $nestProcessSetInfo['back'] == 1))) || ($nestProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")))) {
                    if (method_exists($this,$methods[$typeVal])){
//                        $method = $methods[$typeVal];
                        // 组装数据
                        $log = [
                            'creator' => $userInfo['user_id'],
                            'type' => $types[$typeVal],
                            'request_data' => $param,
                            'run_id' => $runId
                        ];
                        if ($setting->enable){
//                            $apiData = $this->parseInvoiceParam($flowId, $flowData, $runId);
//                            if (!empty(($apiData))) {
//                                if (count($apiData) == count($apiData, 1) && !isset($apiData['params'])) {
//                                    $this->$method(['ids' => $apiData, 'reim' => json_encode(['dataid' => $runId])], $userInfo, $runId);
//                                } else {
//                                    // 按金额报销
//                                    $this->checkBeforeReim(['infos' => $apiData['params'], 'type' => $typeVal], $userInfo, $runId);
//                                }

                            $apiData = $this->parseInvoiceParamV2($flowId, $flowData);
                            if (!empty(($apiData))) {
                               $this->checkBeforeReimV2(['infos' => $apiData, 'type' => $typeVal], $userInfo, $runId, $runName ?? '');
                            } else {
                                $result = ['error_code' => '9999', 'message' => trans('invoice.not_get_flow_data')];
                                $log['result_data'] = $result;
                                return $this->returnResult($result, $log);
                            }
                        } else {
                            $result = ['error_code' => '9999', 'message' => trans('invoice.setting_not_enable')];
                            $log['result_data'] = $result;
                            return $this->returnResult($result, $log);
                        }
                    } else {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function checkOutsend($allOutsendInfos, $nodeId, $action)
    {
        $outsendInfos = [];
        foreach ($allOutsendInfos as $outsendInfo) {
            if ($outsendInfo['node_id'] == $nodeId) {
                $outsendInfos[] = $outsendInfo;
            }
        }
        $outsendStatus = [
            'status' => 0,   // 是否触发外发
            'flowOutsendTimingArrival' => 0, // 0提交触发 1到达触发  2仅退回触发
            'flowOutsendTimingSubmit' => 0, // 流程提交时外发
            'back' =>0, // 流程提交退回或退回到达时不触发 0不触发
        ];
        if ($outsendInfos) {
            foreach ($outsendInfos as $outsendInfo) {
                if ($outsendInfo['action'] === $action){
                    if (in_array($outsendInfo['trigger_time'], [0, 1, 2])) { // 0 提交触发 1到达触发 2仅退回触发
                        $outsendStatus['status'] = 1;
                        if ($outsendInfo['trigger_time'] === 0) {
                            $outsendStatus['flowOutsendTimingArrival'] = 0;
                            $outsendStatus['flowOutsendTimingSubmit'] = 1;
                            $outsendStatus['back'] = $outsendInfo['back'] == 0 ? 1 : 0;
                        } elseif ($outsendInfo['trigger_time'] === 1) {
                            $outsendStatus['flowOutsendTimingArrival'] = 1;
                            $outsendStatus['back'] = $outsendInfo['back'] == 0 ? 1 : 0;
                        } else {
                            // 仅退回   退回到达触发
                            $outsendStatus['flowOutsendTimingArrival'] = 2;
                            $outsendStatus['back'] = 1;
                        }
                    }
                }
            }
        }
        return $outsendStatus;
    }

    public function parseInvoiceParam($flowId, $flowData, $runId = 0)
    {
        // 通过配置id获取流程集成配置  --- 获取发票号码对应字段，获取发票唯一标识
        $setting = app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$flowId]]);
        $codeField = $setting['code_field'] ?? '';
        $moneyField = $setting['money_field'] ?? '';
        $current = time();
        $fids = [];
        $params = [];
        // 是否明细数据
        $fieldValueArray = explode('_', $codeField);
        // 明细数据
        if ($fieldValueArray && count($fieldValueArray) > 2) {
            $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
            // 获取对应明细数据 获取明细行数最少的作为循环次数
            $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
            $fieldDetailLineCount = count($fieldDetail);
            if ($fieldDetailLineCount == 0) {
                return false;
            } else {
                for ($i = 0; $i < $fieldDetailLineCount; $i++) {
                    $fields = isset($flowData[$fieldDetailLayoutId]) && isset($flowData[$fieldDetailLayoutId][$i]) && isset($flowData[$fieldDetailLayoutId][$i][$codeField]) ? $flowData[$fieldDetailLayoutId][$i][$codeField] : [];
                    if (!is_array($fields)) {
                        $fields = explode(',', $fields);
                    }
                    $fids = array_merge($fields, $fids);
                    if ($moneyField) {
                        $moneyFields = isset($flowData[$fieldDetailLayoutId]) && isset($flowData[$fieldDetailLayoutId][$i]) && isset($flowData[$fieldDetailLayoutId][$i][$moneyField]) ? $flowData[$fieldDetailLayoutId][$i][$moneyField] : [];
                        if (!is_array($moneyFields)) {
                            $moneyFields = explode(',', $moneyFields);
                        }
                        foreach ($fields as $key => $field) {
                            $param = [
                                'dataid' => $runId,
                                'amount' => $moneyFields[$key],
                                'fid' => $field,
                                'date' => $current
                            ];
                            $params[] = $param;
                        }

                    }
                }
            }
        } else {
            // 非明细
            $fields = $flowData[$codeField] ?? [];
            if (!is_array($fields)) {
                $fields = explode(',', $fields);
            }
            $fids = array_merge($fields, $fids);
            if ($moneyField) {
                $moneyFields = $flowData[$moneyField] ?? [];
                if (!is_array($moneyFields)) {
                    $moneyFields = explode(',', $moneyFields);
                }
                foreach ($fields as $key => $field) {
                    $param = [
                        'dataid' => $runId,
                        'amount' => $moneyFields[$key],
                        'fid' => $field,
                        'date' => $current
                    ];
                    $params[] = $param;
                }
            }
        }
        if (!$moneyField) {
            return $fids;
        } else {
            return ['params' => $params];
        }

    }

    /** 发票报销相关接口eo版本参数处理
     * @param     $flowId
     * @param     $flowData
     * @param int $runId
     */
    public function parseInvoiceParamV2($flowId, $flowData)
    {
    // 通过配置id获取流程集成配置  --- 获取发票号码对应字段，获取发票唯一标识
        $setting = app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$flowId]]);
        $codeField = $setting['code_field'] ?? '';
        $moneyField = $setting['money_field'] ?? '';
        $params = [];
        // 是否明细数据
        $fieldValueArray = explode('_', $codeField);
        // 明细数据
        if ($fieldValueArray && count($fieldValueArray) > 2) {
            $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
            // 获取对应明细数据 获取明细行数最少的作为循环次数
            $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
            $fieldDetailLineCount = count($fieldDetail);
            if ($fieldDetailLineCount == 0) {
                return false;
            } else {
                for ($i = 0; $i < $fieldDetailLineCount; $i++) {
                    $fields = isset($flowData[$fieldDetailLayoutId]) && isset($flowData[$fieldDetailLayoutId][$i]) && isset($flowData[$fieldDetailLayoutId][$i][$codeField]) ? $flowData[$fieldDetailLayoutId][$i][$codeField] : [];
                    if (!is_array($fields)) {
                        $fields = explode(',', $fields);
                    }
                    $moneyFields = $moneyField && isset($flowData[$fieldDetailLayoutId]) && isset($flowData[$fieldDetailLayoutId][$i]) && isset($flowData[$fieldDetailLayoutId][$i][$moneyField]) ? $flowData[$fieldDetailLayoutId][$i][$moneyField] : [];
                    if (!is_array($moneyFields)) {
                        $moneyFields = explode(',', $moneyFields);
                    }
                    foreach ($fields as $key => $field) {
                        $param = [
                            'amount' => $moneyFields[$key] ?? '',
                            'fid' => $field
                        ];
                        $params[] = $param;
                    }
                }
            }
        } else {
            // 非明细
            $fields = $flowData[$codeField] ?? [];
            if (!is_array($fields)) {
                $fields = explode(',', $fields);
            }
            $moneyFields = $moneyField && $flowData[$moneyField] ?? [];
            if (!is_array($moneyFields)) {
                $moneyFields = explode(',', $moneyFields);
            }
            foreach ($fields as $key => $field) {
                $param = [
                    'amount' => $moneyFields[$key] ?? '',
                    'fid' => $field
                ];
                $params[] = $param;
            }
        }
        return $params;
    }

    /** 默认返回数据  ---  无需组装数据
     * @param $data
     * @return array|bool
     */
    private function returnResult($data, $log = [], $service = null)
    {
        if (isset($data['error_code']) && $data['error_code'] == 0) {
            if ($log) {
                // 验真后更新发票缓存
                if (in_array($log['type'], [12]) && $service) {
                    $invoice = isset($data['data']['info']) ? $data['data']['info'] : [];
                    $invoiceId = $invoice['id'] ?? ($invoice['id'] ?? '');
//                    Cache::forget('invoice_detail_'. $invoiceId);
                    if ($invoice && $invoice['modify_info'] && ($invoice['modify_info']['furl'] || $invoice['modify_info']['pdf'])) {
                        $imageIndex = $invoice['modify_info']['furl'] ? $invoice['modify_info']['furl'] : ($invoice['modify_info']['pdf'] ?? '');
                        $oldLog = app($this->invoiceLogsService)->getLogByImageIndex($imageIndex);
                        if (isset($oldLog->attachment_id)) {
                            $invoice['modify_info']['attachment_id'] = $oldLog->attachment_id;
                            $invoice['modify_info']['url'] = $oldLog->image_index_url;
                        } else {
                            $invoice['modify_info']['attachment_id'] = '';
                        }
                    }
//                    Cache::forever('invoice_detail_'. $invoiceId, $invoice);
                }
                $log['result_data'] = $data;
                if ($log['type'] == 23 && isset($data['data']['fails'])) {
                    $fids = array_column($data['data']['fails'], 'fid');
                    $invoices = $this->getInvoiceBatch(['ids' =>$fids], [], 'array',$service);
                    $invoices = $invoices['data']['infos'] ?? [];
                    $invoicesFids = array_column($invoices, NULL, 'fid');
                    $message = trans('invoice.delete_failed_reason');
                    foreach ($data['data']['fails'] as $fail) {
                        if (isset($invoicesFids[$fail['fid']])) {
                            $message .=  $invoicesFids[$fail['fid']]['code_value'] .': '. $fail['message'] . ' ';
                        }
                    }
                    $log['error_message'] = $message;
                    app($this->invoiceLogsService)->addLog([$log]);
                    return ['code' => ['', $message], 'dynamic' => $message];
                } else {
                    app($this->invoiceLogsService)->addLog([$log]);
                }
            }
            return $data['data'] ?? true;
        }
        if ($log) {
            $log['error_message'] = $data['message'];
            app($this->invoiceLogsService)->addLog([$log]);
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    public function userList($param, $user)
    {
        $param = $this->parseParams($param);
        $params = ['is_use' => [1]];
        $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, $params);
        if (!$config || !$config->type) {
//            return ['code' => ['can_not_get_invoice_cloud', 'invoice']];
            return ['list' => [], 'count' => []];
        }
        try{
            switch($config->type)
            {
                case '1':// 发票云
                    $param["returntype"] = "array";
                    $config = $config->toArray();
                    if ($config['teams_yun'] && $config['teams_yun']['cid']) {
                        $param['cid'] = $config['teams_yun']['cid'];
                    }
                    $returnData = $this->response(app($this->invoiceCloudTeamsYunUserRepositories), 'getUserCount', 'getUserList', $param);
                    return $returnData;
                default:
//                    return ['code' => ['can_not_get_invoice_cloud', 'invoice']];
                    return ['list' => [], 'count' => []];
            }
        } catch(\Exception $e) {
           return ['code' => ['', $e->getMessage()]];
            return ['list' => [], 'count' => []];
        }
    }

    public function checkService($param, $user)
    {
        $type = $param['type'] ?? '1';
        try{
            switch($type)
            {
                case '1':// 发票云
                    $service = new $this->invoiceCloudService((object)$param, $user);
                    break;
                default:
                    return ['code' => ['can_not_get_invoice_cloud', 'invoice']];
            }
        } catch(\Exception $e) {
            return ['code' => ['', $e->getMessage()]];
        }
        $result = $service->queryAll();
        return $this->returnResult($result);
    }

    public function getWxToken()
    {

    }

    public function wxAddInvoice($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        // 微信同步发票区分来源来自哪里  企业微信还是公众号 -- 获取对应access_token[20200630发票云通过openApi自行获取对应token]
        $platform = $params['platform'] ?? '';
        unset($params['platform']);
        if (!$platform) {
            return ['code' => ['invoice', 'get_platform_failed']];
        } else {
            // 企业微信
            if($platform == 'enterprise_wechat') {
//                $accessToken = app($this->workWechatService)->getAccessToken();
                $flag = 10;
            } else {
                // 微信公众号
//                $accessToken = app($this->weixinService)->getAccessToken();
                $flag = 11;
            }
        }
//        if (is_array($accessToken)) {
//            return $accessToken;
//        }
//        if (!$accessToken){
//            return ['code'=>['', '获取微信凭证失败，请稍后重新获取']];
//        }
//        \Log::info($params['invoices']);
        // 未选择发票 安卓存在未选择发票提交的情况
        if (!$params['invoices']) {
            return true;
        }
        $param = [
            'data' => $params['invoices'],
//            'token' => $accessToken,
            'flag' => $flag
        ];
        $data = $service->wxAddInvoice($param);
        $log = [
            'creator' => $user['user_id'],
            'type' => '13', // 微信发票同步
            'request_data' => $params,
            'result_data' => $data,
        ];
        return $this->returnResult($data, $log);
    }

    public function syncCorpConfig($user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
//        if ($data = Cache::get('invoice_cloud_corp_config')) {
//        } else {
        $changeRole = false;
        $userId = $service->getInvoiceUserId($user['user_id']);
        if ($service->role == 1) {
            $changeRole = true;
            $service->role = 2;
            $service->buildToken($service->cid, $userId);
        }
            $data = $service->syncCorpConfig();
//            if ($data['error_code'] == 0)
//                Cache::put('invoice_cloud_corp_config', $data, 24 *60);
//        }
        // 解析并存储信息到本地
        if ($data['error_code'] == 0) {
            $infos = $data['data'] && $data['data']['infos'] ? $data['data']['infos'] : [];
            $keys = [
                '0' => 'valid_total',   // 验真金额
//                '1' => 'corp_taxnos',   // 允许报销那些税号
                '2' => 'corp_taxnos',
                '3' => 'valid_on_off',  // 是否允许验真
                '4' => 'recognize_valid_on_off',    // 是否自动验真
                '5' => 'valid_user',    // 设置允许验真用户
                '8' => 'reimbursable_month',    // 设置报销有效时间
                '12' => 'reim',         // 那些查验结果可以报销
                '13' => 'corp_taxno'    // 	是否指定部分税号可以报销
            ];
            $keyArray = array_keys($keys);
            $param = [];
            foreach ($infos as $info) {
                if (in_array($info['key'], $keyArray)) {
                    if ($info['key'] == 2) {
                        $param[$keys[$info['key']]] = isset($info['titles']) ? json_encode($info['titles']) : '';
                    } else if ($info['key'] == 5) {
                        $param[$keys[$info['key']]] = isset($info['values']) ? json_encode($info['values']) : '';
                    } else {
                        $param[$keys[$info['key']]] = $info['values'][0];
                    }
                }
            }
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        if ($changeRole) {
            $service->role = 1;
            $service->buildToken($service->cid, $userId);
        }
        return $this->returnResult($data);
    }

    public function totalCorp($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->totalCorp($params);
        // 保存验真金额到数据库
        if ($data['error_code'] == 0) {
            Cache::forget('invoice_cloud_corp_config');
            $param = ['valid_total' => $params['total'] ?? 0];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        return $this->returnResult($data);
    }

    public function corpValidUsers($params, $user)
    {
        $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, ['is_use' => [1]]);
        if (!$config || !$config->type) {
            return ['code' => ['can_not_get_invoice_cloud', 'invoice']];
        }
        try{
            $service = new $this->invoiceCloudService($config->teamsYun, $user);
        } catch(\Exception $e) {
            return ['code' => ['', $e->getMessage()], 'dynamic' => $e->getMessage()];
        }
//        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        // 获取查验相关设置
        $configValid = $this->syncCorpConfigParam($user, ['sflag' => 2], $service);
        $validUsers = $configValid['valid_user'] ? json_decode($configValid['valid_user'], 1) : [];
        // 对比 新增的和删除的
        $param = [
            'vus_add' => [],
            'vus_del' => []
        ];
        $vusAdd = $params['vus_add'] ?? '';
        if ($vusAdd) {
            $vusAdd = json_decode($vusAdd, 1);
        }
        $param['vus_del'] = array_values(array_diff($validUsers, $vusAdd));
        $param['vus_add'] = array_values(array_diff($vusAdd, $validUsers));
        $data = $service->corpValidUsers($param);
        // 保存验真用户到数据库
        if ($data['error_code'] == 0) {
            Cache::forget('invoice_cloud_corp_config');
            $param = ['valid_user' => $params['vus_add'] ?? ''];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        return $this->returnResult($data);
    }

    public function corpReimVtm($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->corpReimVtm($params);
        // 保存有效报销时间到数据库
        if ($data['error_code'] == 0) {
            Cache::forget('invoice_cloud_corp_config');
            $param = ['reimbursable_month' => $params['month'] ?? 0];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        return $this->returnResult($data);
    }

    public function receiptsInvoices($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->receiptsInvoices($params);
        if ($data['error_code'] == 0) {
//            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig($params);
            // 处理数据为我们公共组件所需数据结构
            $data['data']['list'] = $data['data']['infos'] ?? [];
            unset($data['data']['infos']);
            $data['data']['count'] = $data['data']['total'] ?? 0;
            return $data['data'];
        } else {
            return ['code' => ['', $data['message']]];
        }
    }

    public function salesInvoices($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->salesInvoices($params);
        if ($data['error_code'] == 0) {
//            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig($params);
            // 处理数据为我们公共组件所需数据结构
            $data['data']['list'] = $data['data']['infos'] ?? [];
            unset($data['data']['infos']);
            $data['data']['count'] = $data['data']['total'] ?? 0;
            return $data['data'];
        } else {
            return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
        }
    }

    /** 企业票夹 -- 全部发票列表
     * @param $params
     * @param $user
     * @return array
     */
    public function corpInvoices($params, $user)
    {
        $params = $this->parseParams($params);
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
//            return $service;
            return ['list' => [], 'count' => 0];
        }
        $data = $service->corpInvoices($params);
        if ($data['error_code'] == 0) {
            // 处理数据为我们公共组件所需数据结构  --- 需解析发票归属人信息
            if ($data['data']['total'] > 0 && (!isset($data['data']['infos']) || !$data['data']['infos'])){
                $params['page'] = ceil($data['data']['total'] / $params['limit']);
                return $this->corpInvoices($params, $user);
            }
            $data['data']['list'] = $data['data']['infos'] ?? [];
            $list = $data['data']['infos'] ?? [];
            if ($list) {
                $types = $this->getTypes($params, $user);
                $types = array_column($types, NULL, 'key');
                $users = array_column($list, 'user');
                $userIds = array_unique(array_column($users, 'uid'));
                $oaUsers = $service->getOaUsers($userIds);
                foreach($list as $key => $value) {
                    if ($value && $value['comm_info'] && $value['comm_info']['pro'] && $value['comm_info']['pro']['type'] && !$value['comm_info']['pro']['cname']) {
                        $cname = isset($types[$value['comm_info']['pro']['type']]) && $types[$value['comm_info']['pro']['type']]['value'] ? $types[$value['comm_info']['pro']['type']]['value'] : '';
                        $list[$key]['comm_info']['pro']['cname'] = $this->returnCname($value['comm_info']['pro']['type'], $cname);
                    }
                    $list[$key]['user']['user_id'] = isset($oaUsers[$value['user']['uid']]) && $oaUsers[$value['user']['uid']]['user_id'] ? $oaUsers[$value['user']['uid']]['user_id'] : '';
                    $list[$key]['user']['user_name'] = isset($oaUsers[$value['user']['uid']]) && $oaUsers[$value['user']['uid']]['name'] ? $oaUsers[$value['user']['uid']]['name'] : '';
                }
            }
            $data['data']['list'] = $list;
            unset($data['data']['infos']);
            $data['data']['count'] = $data['data']['total'] ?? 0;
//            unset($data['data']['total']);
            unset($data['data']['end_flag']);
            return $data['data'];
        } else {
//            return ['code' => ['', $data['message']]];
            return ['list' => [], 'count' => 0];
        }
    }

    public function invoiceStatistics($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->invoiceStatistics($params);
        if ($data['error_code'] == 0) {
            $mask = $params['mask'];
            if (in_array($mask, [1, 2])) {
                // 获取发票类型
                if ($invoiceTypes = Cache::get('invoice_sync_config_2')) {
                } else {
                    $types = $service->getTypes(['type' => 2]);
                    if ($types['error_code'] == 0) {
                        $invoiceTypes = $types['data'];
                    }
                }
                if ($invoiceTypes){
                    $invoiceTypes = array_column($invoiceTypes, NULL, 'key');
                }
                $infos = $data['data']['infos'][0]['infos'] ?? [];
                $newInfos = [];
                if ($infos) {
                    foreach($infos as $key => $info){
                        $infos[$key]['name'] = isset($invoiceTypes[$info['key']]) ? $invoiceTypes[$info['key']]['value'] : '';
                        $newInfos[$info['key']] = $infos[$key];
                    }
                    foreach ($invoiceTypes as $invoiceTypeKey => $invoiceType) {
                        if (!isset($newInfos[$invoiceTypeKey])) {
                            $newInfos[$invoiceTypeKey] = [
                                'key' => $invoiceTypeKey,
                                'name' => $invoiceType['value'],
                                'value' => '0.00'
                            ];
                        }
                    }
                } else {
                    foreach ($invoiceTypes as $invoiceTypeKey => $invoiceType) {
                        $newInfos[$invoiceTypeKey] = [
                            'key' => $invoiceTypeKey,
                            'name' => $invoiceType['value'],
                            'value' => '0.00'
                        ];
                    }
                }
                return array_values($newInfos);
            } else {
                if ($invoiceSources = Cache::get('invoice_sync_config_4')) {
                } else {
                    $types = $service->getTypes(['type' => 4]);
                    if ($types['error_code'] == 0) {
                        $invoiceSources = $types['data'];
                    }
                }
                if ($invoiceSources){
                    $invoiceSources = array_column($invoiceSources, NULL, 'key');
                }
                $infos = $data['data']['infos'][0]['infos'] ?? [];
                $newInfos = [];
                if ($infos) {
                    foreach($infos as $key => $info){
                        $info['name'] = isset($invoiceSources[$info['key']]) ? $invoiceSources[$info['key']]['value'] : '';
                        $newInfos[intval($info['key'])] = $info;
                    }
                    foreach ($invoiceSources as $invoiceSourceKey => $invoiceSource) {
                        if (!isset($newInfos[$invoiceSourceKey])) {
                            $newInfos[$invoiceSourceKey] = [
                                'key' => intval($invoiceSourceKey),
                                'name' => $invoiceSource['value'],
                                'value' => '0.00'
                            ];
                        }
                    }
                } else {
                    foreach ($invoiceSources as $invoiceSourceKey => $invoiceSource) {
                        $newInfos[$invoiceSourceKey] = [
                            'key' => $invoiceSourceKey,
                            'name' => $invoiceSource['value'],
                            'value' => '0.00'
                        ];
                    }
                }
                $newInfoKeys = array_column($newInfos,'key');
                array_multisort($newInfoKeys,SORT_ASC,$newInfos);
                return array_values($newInfos);
            }
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    public function thirdSetAppkey($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->thirdSetAppkey($params);
        return $this->returnResult($data);
    }

    public function corpTaxno($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->corpTaxno($params);
        if ($data['error_code'] == 0) {
            Cache::forget('invoice_cloud_corp_config');
            $param = ['corp_taxno' => $params['taxno'] ?? 0];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        return $this->returnResult($data);
    }
    public function corpTaxnos($params, $user)
    {
        $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, ['is_use' => [1]]);
        if (!$config || !$config->type) {
            return ['code' => ['can_not_get_invoice_cloud', 'invoice']];
        }
        try{
            $service = new $this->invoiceCloudService($config->teamsYun, $user);
        } catch(\Exception $e) {
            return ['code' => ['', $e->getMessage()], 'dynamic' => $e->getMessage()];
        }
        if (is_array($service)) {
            return $service;
        }
        $corpTaxnos = $config->teamsYun['corp_taxnos'] ? json_decode($config->teamsYun['corp_taxnos'], 1) : [];
        $corpTaxnosArray = [];
        foreach($corpTaxnos as $corpTaxno) {
            if (isset($corpTaxno['name']) && isset($corpTaxno['tax_num'])) {
                $corpTaxnosArray[] = $corpTaxno['name'] . '/' . $corpTaxno['tax_num'];
            }
        }
        if (is_array($service)) {
            return $service;
        }
        $taxnoAdd = $params['taxno_add'] ? json_decode($params['taxno_add'], 1) : [];
        $taxnoDel = array_values(array_diff($corpTaxnosArray, $taxnoAdd));
        $taxnoAdds = array_values(array_diff($taxnoAdd, $corpTaxnosArray));
        if ($taxnoAdds) {
            foreach ($taxnoAdds as $add) {
                $title = explode('/', $add);
                $param['titles'][] = ['name' => $title[0] ?? '', 'tax_num' => $title[1]];
            }
        }
        if ($taxnoDel) {
            $taxnoDel = array_unique($taxnoDel);
            $taxnoDels = [];
            foreach ($taxnoDel as $item) {
                $taxnoDels[] = explode('/', $item)[1];
            }
            $param['taxno_del'] = $taxnoDels;
        }
        $data = $service->corpTaxnos($param);
        if ($data['error_code'] == 0) {
            Cache::forget('invoice_cloud_corp_config');
            $updateTitles = [];
            if ($taxnoAdd){
                foreach ($taxnoAdd as $add) {
                    $taxnoAddTitle = explode('/', $add);
                    $updateTitles[] = ['name' => $taxnoAddTitle[0] ?? '', 'tax_num' => $taxnoAddTitle[1]];
                }
            }
            $param = ['corp_taxnos' => $updateTitles ? json_encode($updateTitles) : ''];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        return $this->returnResult($data);
    }

    public function reim($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->reim($params);
        if ($data['error_code'] == 0) {
            Cache::forget('invoice_cloud_corp_config');
            $param = ['reim' => $params['reim'] ?? 0];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        return $this->returnResult($data);
    }

    public function getThirdAppkey()
    {
        $weixin = app($this->weixinService)->getWeixinToken();
        $workWechat = app($this->workWechatService)->getWorkWechat();
        $weixinAppkey = $weixin && isset($weixin->appid) ? $weixin->appid : '';
        $workWechatAppkey = $workWechat && $workWechat->corpid ? $workWechat->corpid : '';
        return compact('weixinAppkey', 'workWechatAppkey');
    }

    public function getThirdAccessToken($param, $user)
    {
//        $appkey = $param['appkey'] ?? '';
        $source = $param['source'] ?? '';
//        if (!$appkey){
//            return ['code' => ['param_error_appkey', 'invoice']];
//        }
        if (!$source || !in_array($source, [10, 11])){
            return ['code' => ['source_error', 'invoice']];
        }
        $key = 'IMW#$%EIMW2123IH';
        switch ($source){
            case 10:
                // 企业微信
                if (isset($_COOKIE["agentid"])) {
                    $agentid = $_COOKIE["agentid"];
                } else {
                    $agentid = app($this->workWechatRepository)->getWorkWechat()->sms_agent;
                }
                $workWechat = app($this->workWechatService)->getWorkWechat($agentid);
                if (!$workWechat->wechat_code) {
                    $wechatCode = app($this->workWechatService)->getAccessToken($agentid);
                    if (!$wechatCode || is_array($wechatCode)) {
                        return is_array($wechatCode) ? $wechatCode : ['code' => ['get_token_error', 'invoice']];
                    }
                    $workWechat->wechat_code = $wechatCode;
                }
                $token = openssl_encrypt($workWechat->wechat_code, 'AES-128-ECB', $key);
                return ['appkey' => $workWechat->corpid, 'token' => $token];
                break;
            case 11:
                // 公众号
                $weixin = app($this->weixinService)->getWeixinToken();
                if (!$weixin->access_token) {
                    $accessToken = app($this->weixinService)->getAccessToken();
                    if (!$accessToken || is_array($accessToken)) {
                        return is_array($accessToken) ? $accessToken : ['code' => ['get_token_error', 'invoice']];
                    }
                    $weixin->access_token = $accessToken;
                }
                $token = openssl_encrypt($weixin->access_token, 'AES-128-ECB', $key);
                return ['appkey' => $weixin->appid, 'token' => $token];
                break;
            default:
                return ['code' => ['source_error', 'invoice']];
        }
    }

    public function createApp($params, $user)
    {
        // 创建openApi应用
        $openApiParams = [
            'application_name' => trans('integrationCenter.invoice_cloud'),
            'user_basis_field' => 'user_accounts',
        ];
        $application = app($this->openApiService)->getData(['application_name' => [trans('integrationCenter.invoice_cloud')]]);
        if ($application){
            $openApiResult = $application->toArray();
        } else {
            $openApiResult = app($this->openApiService)->registerApplication($openApiParams, $user['user_id']);
        }
        if (!$openApiResult || isset($openApiResult['code'])) {
            return $openApiResult;
        }
        $appParams = [
            'appName' => $params['appName'],
            'thirdAppId' => $openApiResult['agent_id'] ?? 100000 + $openApiResult['id'],
            'thirdSecret' => $openApiResult['secret'],
            'thirdAddr' => $params['thirdAddr'] ?? OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST,
            'appType' => 3
        ];
        $service = new $this->invoiceCloudService((object)$params, $user);
        $data = $service->createApp($appParams);
        if ($data['error_code'] == 0) {
            $info = $data['data'];
//             保存应用信息值发票云配置表
            $teamsYunParam = [
                'url' => $params['url'],
                'app_key' => $info['appId'],
                'app_secret' => $info['secret'],
                'oa_server_url' => $info['thirdAddr'],
                'open_application_id' => $openApiResult['id']
            ];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($teamsYunParam, 1, $user);
            // 创建默认企业
            $company = app($this->companyService)->getCompanyDetail();
            $default = 'eoffice-'.date('Y-m-d') . '-'. time();
            $corpParam = [
                'corpName' => $company && $company['company_name'] ? $company['company_name'] : $default,
                'account' => $company && $company['company_name'] ? $company['company_name'] : $default
            ];
            $this->createCorp($corpParam, $user);
        }
        return $this->returnResult($data);
    }

    public function updateApp($params, $user, $configId)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        // 获取openApi应用 id 获取发票云配置的openApi是否存在 不存在重新创建
        $invoiceCloud = $this->getIsUseInvoiceCloud($user);
        $application = null;
        if ($invoiceCloud && $invoiceCloud->teamsYun){
            $applicationId = $invoiceCloud->teamsYun['open_application_id'];
            $application = app($this->openApiService)->getData(['id' => [$applicationId]]);
        }
        if($application) {
            $applicationId = $application['id'];
            $appParams = [
                'appName' => $params['appName'],
                'thirdAppId' => $application['agent_id'] ?? 100000 + $application['id'],
                'thirdSecret' => $application['secret'],
                'thirdAddr' => $params['thirdAddr'] ?? OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST,
            ];
        } else {
            $openApiParams = [
                'application_name' => trans('integrationCenter.invoice_cloud'),
                'user_basis_field' => 'user_accounts',
            ];
            $openApiResult = app($this->openApiService)->registerApplication($openApiParams, $user['user_id']);
            $applicationId = $openApiResult['id'];
            $appParams = [
                'appName' => $params['appName'],
                'thirdAppId' => $openApiResult['agent_id'] ?? 100000 + $openApiResult['id'],
                'thirdSecret' => $openApiResult['secret'],
                'thirdAddr' => $params['thirdAddr'] ?? OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST,
            ];
        }
        $data = $service->updateApp($appParams);
        if ($data['error_code'] == 0) {
            $invoiceCloudParam = [
                'open_application_id' => $applicationId,
                'oa_server_url' => $appParams['thirdAddr'],
                'name' => $appParams['appName'],
                'remark' => $params['remark'] ?? ''
            ];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($invoiceCloudParam, $configId, $user);
        }
        return $this->returnResult($data);
    }
    public function queryApp($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->queryApp($params);
        return $this->returnResult($data);
    }

    public function corpValidInvoice($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user, $type = 'valid');
        if (is_array($service)) {
            return $service;
        }
        $configValid = $this->syncCorpConfigParam($user, ['sflag' => 2], $service);
        if (!isset($configValid['valid_on_off']) || $configValid['valid_on_off']) {
            return ['code' => ['valid_not_open', 'invoice']];
        }
        $data = $service->corpValidInvoice($params);
        $log = [
            'creator' => $user['user_id'],
            'type' => '12', // 发票验真
            'request_data' => $params,
            'result_data' => $data,
        ];
        return $this->returnResult($data, $log);
    }

    public function corpUser($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->corpUser($params);
        return $this->returnResult($data);
    }

    public function getIsUseInvoiceCloud($user, $param =[])
    {
        $param = ['is_use' => [1]];
        $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, $param);
        if ($config && $config->teamsYun) {
            $config->token = '';
            $service = new $this->invoiceCloudService($config->teamsYun, $user);
            try{
                $token = $service->getToken([]);
                $config->token = $token;
                $config->role = $service->role ?? 1;
                // 验真金额获取
                $total = $this->syncCorpConfigParam($user, ['sflag' => 2, 'stype' =>2]);
                $config->teamsYun->valid_total = $total['valid_total'];
            } catch (\ErrorException $e) {
//                $config->token = '';
//                return ['code' => ['', $e->getMessage()]];
            }
        }
        return $config;
    }
//    // 刷新发票详情缓存
//    private function resetInvoiceCache($params, $user)
//    {
//        // 获取发票云角色为管理员的用户
//        // 通过企业票夹获取发票详情的接口获取详情
//    }

    public function addDefaultInterface($user, $param)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->addDefaultInterface($param);
        return $this->returnResult($data);
    }

    /** 根据openapi应用id获取发票云配置
     * @param $applicationId
     * @return mixed
     */
    public function checkInvoiceByOpenApiApplicationId($applicationId)
    {
        return app($this->thirdPartyInterfaceService)->getInvoiceCloudTeamsYunUseConfigByApplicationId($applicationId);
    }

    public function getImportUrl($params, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, ['is_use' => [1]]);
        $dev = isset($params['dev']) ? $params['dev'] : 0; 
        if ($dev == 0) {
            $serverUrl = $config->teamsyun && $config->teamsyun['web_addr'] ? $config->teamsyun['web_addr'] : '';
        } else if (in_array($dev, [1, 2, 3, 4, 6, 7])) {
            $serverUrl = $config->teamsyun && $config->teamsyun['h5_addr'] ? $config->teamsyun['h5_addr'] : '';
        }
        if (!$serverUrl) {
            return ['code' => ['', '发票云WEB地址配置为空']];
        }
        try{
            $token = $service->getToken();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ['code' => ['', $message], 'dynamic' => $message];
        }
        $comp = $params['comp'] ?? '';
        $lang = $this->getLocaleLang($user);
        // 先查用户是否同步到发票云没有  获取token
        $component = isset($params['component']) ? $params['component'] : 'component';
        if ($component == 'h5') {
            $url = $serverUrl .'/invoiceWeb/taro/dist/h5/?token=' . $token . "&src=EO&dev=". $dev ."&lang=". $lang;
        } else {
            $url = $serverUrl . "/corpInvoice/#/" . $component . "?token=" . $token . "&src=EO&dev=". $dev ."&baseUrl=". $serverUrl ."&lang=". $lang;
            if ($comp) {
                $url .= "&comp=" . $comp;
            }
            if (array_key_exists('fids', $params)) {
                $url .= "&fids=" . $params['fids'];
            }
        }
        return ['url' => $url, 'host' => $serverUrl];
    }

    public function getLocaleLang($user)
    {
        // 处理多语言
        $langArray = [
            'zh_cn',   // 简体，中国
            'zh_tw',   // 繁体，台湾
            'en_us',   // 英语，美国
            'es_es',   // 西班牙语，西班牙
            'fr_fr',   // 法语，法国
            'pt_pt',   // 葡萄牙语，葡萄牙
            'ar_ae',   // 阿拉伯语，阿拉伯联合酋长国
            'ru_ru',   // 俄语，俄罗斯
            'ja_jp',   // 日语，日本
            'de_de',   // 德语，德国
            'ko_kr',   // 韩语，韩国
            'th_th',   // 泰语，泰国
        ];
        $localLang = strtolower(app($this->langService)->getUserLocale($user['user_id']));
        if (in_array($localLang, $langArray)) {
            return $localLang;
        } else {
            $return = 'zh_cn';
            foreach($langArray as $lang) {
                if (strpos($localLang, $lang) !== false) {
                    $return = $lang;
                    break;
                }
            }
            return $return;
        }
    }

    public function getRecharge($user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $data = $service->recharge();
        if ($data['error_code'] == 0) {
            $infos = $data['data']['infos'] ?? [];
            $return = [];
            foreach($infos as $info) {
                if($info['flag'] == 0) {
                    $return['recognize'] = $info;
                } else if ($info['flag'] == 1) {
                    $return['verify'] = $info;
                }
            }
            return $return;
        }
        return $this->returnResult($data);
    }

    public function getRechargeLog($param, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return ['count' => 0, 'list' => []];
        }
        $serviceUrl = str_replace('invoiceApi', 'noauth', $service->url);
        $data = $service->rechargeLog($param);
        if ($data['error_code'] == 0) {
            // 分页问题：选到最后一页后切换每页个数更大
            if ($data['data']['total'] > 0 && (!isset($data['data']['infos']) || !$data['data']['infos'])){
                $param['page'] = ceil($data['data']['total'] / $param['limit']);
                return $this->getRechargeLog($param, $user);
            }
            $data['data']['list'] = $data['data']['infos'] ?? [];
            unset($data['data']['infos']);
            if ($data['data']['list']) {
                $users = array_column($data['data']['list'], 'ou');
                $userIds = array_unique(array_column($users, 'uid'));
                $oaUsers = $service->getOaUsers($userIds);
                foreach ($data['data']['list'] as $key => $value) {
                    // 附件图片地址处理
                    $data['data']['list'][$key]['url'] = '';
                    if ($imageIndex = $value['image_index']) {
                        if (strpos($service->url, 'mypiaojia')){
                            $data['data']['list'][$key]['url'] = 'https://dl.mypiaojia.com/noauth/'. $imageIndex;
                        } else {
                            $data['data']['list'][$key]['url'] = $serviceUrl. $imageIndex;
                        }
                    }
                    // 用户id获取
                    $data['data']['list'][$key]['user_id'] =  isset($oaUsers[$value['ou']['uid']]) && isset($oaUsers[$value['ou']['uid']]['user_id']) ? $oaUsers[$value['ou']['uid']]['user_id'] : '';
                    $data['data']['list'][$key]['user_name'] =  isset($oaUsers[$value['ou']['uid']]) && isset($oaUsers[$value['ou']['uid']]['name']) ? $oaUsers[$value['ou']['uid']]['name'] : '';
                }
            }
            $data['data']['count'] = $data['data']['total'] ?? 0;
            return $data['data'];
        } else {
            return ['count' => 0, 'list' => []];
        }
    }

    public function shareUser($param, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        // 处理数据
        $shareUsers = $delusers = [];
        $invoices = $param['invoice'] ?? [];
        $oldShareUserIds = [];
        array_map(function ($value) use (&$oldShareUserIds) {
            if (isset($value['share_user_ids'])) {
                array_walk_recursive($value['share_user_ids'], function($val) use (&$oldShareUserIds) {
                    array_push($oldShareUserIds, $val);
                });
            }
        }, $invoices);
        $users = array_merge($param['share_users'] ?? [], $oldShareUserIds);
        $userInfos = $service->getOaUsers($users);
        $newParams = ['operate_type' => $param['operate_type'] ?? 0];
        // 对比旧的共享人员，新建和删除
        foreach($invoices as $invoice) {
            $shareUsers = $param['share_users'] ?? [];
            $oldShareUsers = $invoice['share_user_ids'];
            $newShareUsers = array_diff($shareUsers, $oldShareUsers);
            $newShareUsersObjects = $delShareUsersObjects = [];
            if ($newShareUsers) {
                foreach($newShareUsers as $key => $shareUser) {
                    $newShareUsersObject = [
                        'cid' => $service->cid,
                        'uid' => $shareUser,
                        'appid' => $service->appKey,
                        'name' => $userInfos[$shareUser] && $userInfos[$shareUser]['name'] ? $userInfos[$shareUser]['name'] : ''
                    ];
                    $newShareUsersObjects[] = ['user' => $newShareUsersObject];
                }
            }
            $delShareUsers = array_diff($oldShareUsers, $shareUsers);
            if ($delShareUsers) {
                foreach($delShareUsers as $key => $shareUser) {
                    $delShareUsersObject = [
                        'cid' => $service->cid,
                        'uid' => $shareUser,
                        'appid' => $service->appKey,
                        'name' => $userInfos[$shareUser] && $userInfos[$shareUser]['name'] ? $userInfos[$shareUser]['name'] : ''
                    ];
                    $delShareUsersObjects[] = $delShareUsersObject;
                }
            }
            if ($newShareUsersObjects || $delShareUsersObjects) {
//                $newParams = [
//                    'fids' => [$invoice['fid']],
//                    'operate_type' => $param['operate_type'] ?? 0,
//                ];
//                if ($newShareUsersObjects) {
//                    $newParams['share_users'] = $newShareUsersObjects;
//                }
//                if ($delShareUsersObjects) {
//                    $newParams['del_users'] = $delShareUsersObjects;
//                }
                $newParams['infos'][] = [
                    'fid' => $invoice['fid'],
                    'share_users' => $newShareUsersObjects,
                    'del_users' => $delShareUsersObjects
                ];
            } else {
                return ['code' => ['please_select_share_user', 'invoice']];
            }
        }

        $data = $service->shareUser($newParams);
        
        return $this->returnResult($data);
    }

    public function shareSync($param, $user)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        // 多张发票处理
        if (is_string($param['fid'])) {
            $param['fid'] = json_decode($param['fid'], 1);
        }
        $return = [];
        foreach($param['fid'] as $fid) {
            $data = $service->shareSync(['fid' => $fid]);
            if ($data['error_code'] == 0) {
                $return[$fid] = $data['data'];
            } else {
    
            }
        }
        return $return;
    }


    public function exportInvoice($param)
    {
        $user = $param['user_info'];
        $corp = $param['corp'] ?? '';
        $header = [
            'invoice_id' => trans('invoice.invoice_id'),
            'invoice_code'   => trans('invoice.invoice_code'),
            'invoice_number' => trans('invoice.invoice_number'),
            'invoice_type'   => trans('invoice.invoice_type'),
            'invoice_date'   => trans('invoice.invoice_date'),
            'payer_company'  => trans('invoice.payer_company'),
            'buyer_company'  => trans('invoice.buyer_company'),
            'total' => trans('invoice.total'),
            'valid' => trans('invoice.valid'),
            'status' => trans('invoice.invoice_normal_status'),
            'sreim' => trans('invoice.invoice_status'),
            'source' => trans('invoice.invoice_source'),
            'create_time' => trans('invoice.create_time'),
            // 'creator' => trans('invoice.creator')
        ];
        if ($corp) {
            $header['creator'] = trans('invoice.creator');
        }
        $recordData = $this->getListAll($param, $user);
        $data = [];
        $validStatus = [
            0 => trans('invoice.not_valid'),
            1 => trans('invoice.valid_checked'),
            2 => trans('invoice.is_valid'),
            3 => trans('invoice.valid_failed'),
            4 => trans('invoice.invalid'),
            5 => trans('invoice.check_failure'),
        ];
        $reimStatus = [
            0 => trans('invoice.unreimbursed'),
            1 => trans('invoice.reimbursing'),
            2 => trans('invoice.reimbursed'),
            3 => trans('invoice.red_flush'),
            4 => trans('invoice.occupied'),
        ];
        $status = [
            0 => trans('invoice.normal'),
            1 => trans('invoice.out_of_work'),
            2 => trans('invoice.obsolete'),
            3 => trans('invoice.red_letter'),
            4 => trans('invoice.abnormal'),
            5 => trans('invoice.forwarded'),
        ];
        $sources = $this->getTypes(['type' => 4], ['user_id' => $user['user_id']]);
        $sources = array_column($sources, 'value', 'key');
        foreach ($recordData as $value) {
            $item = [
                'invoice_id' => $value['fid'],
                'invoice_code'   => $value['code'] ?? '',
                'invoice_number' => $value['number'] ?? '',
                'invoice_type'   => isset($value['comm_info']['pro']) && isset($value['comm_info']['pro']['cname']) ? $value['comm_info']['pro']['cname'] : '',
                'invoice_date'   => isset($value['comm_info']['pro']) && isset($value['comm_info']['pro']['date']) ? $value['comm_info']['pro']['date'] : '',
                'payer_company'  => isset($value['comm_info']['payer']) && isset($value['comm_info']['payer']['company']) ? $value['comm_info']['payer']['company'] : '',
                'buyer_company'  => isset($value['comm_info']['buyer']) && isset($value['comm_info']['buyer']['company']) ? $value['comm_info']['buyer']['company'] : '',
                'total' => isset($value['comm_info']['price']) && isset($value['comm_info']['price']['total']) ? $value['comm_info']['price']['total'] : '',
                'valid' => isset($value['comm_info']['pro']) && isset($value['comm_info']['pro']['valid']) ? ($validStatus[$value['comm_info']['pro']['valid']] ?? '') : '',
                'status' => isset($value['comm_info']['pro']) && isset($value['comm_info']['pro']['status']) ? ($status[$value['comm_info']['pro']['status']] ?? '') : '',
                'sreim' => isset($value['comm_info']['pro']) && isset($value['comm_info']['pro']['sreim']) ? ($reimStatus[$value['comm_info']['pro']['sreim']] ?? '') : '',
                'source' => isset($value['modify_info']['source']) && isset($sources[$value['modify_info']['source']]) ? $sources[$value['modify_info']['source']] : '',
                'create_time' => isset($value['modify_info']['ctm']) ? date('Y-m-d H:i:s', $value['modify_info']['ctm']) : '',
                // 'creator' => isset($value['user']) && isset($value['user']['user_name']) ? $value['user']['user_name'] : ''
            ];
            if ($corp) {
                $item['creator'] = isset($value['user']) && isset($value['user']['user_name']) ? $value['user']['user_name'] : '';
            }
            $data[] = $item;
        }
        return compact('header', 'data');
    }

    public function getListAll($params, $own)
    {
        $params = $this->parseParams($params);
        $service = $this->getUseInvocieCloudService($own);
        if (is_array($service)) {
            return [];
        }
        $endFlag = false;
        $infos = [];
        $types = $this->getTypes($params, $own);
        $types = array_column($types, NULL, 'key');
        $params['limit'] = 1000;
        $params['page'] = 1;
        $corp = $params['corp'] ?? '';
        unset($params['corp']);
        while (!$endFlag) {
            if (!$corp) {
                $data = $service->getInvoiceList($params);
            } else {
                $data = $service->corpInvoices($params);
            }
            if ($data['error_code'] == 0) {
                $endFlag = !isset( $data['data']['end_flag']) || $data['data']['end_flag'] == 0 ? true : false;
                if (isset($data['data']['infos']) && count($data['data']['infos']) > 0) {
                    // 解决微信回传的发票数据 没有cname值
                        $users = array_column($data['data']['infos'], 'user');
                        $userIds = array_unique(array_column($users, 'uid'));
                        $oaUsers = $service->getOaUsers($userIds);
                    
                    foreach ($data['data']['infos'] as $key => $item) {
                        if ($item && $item['comm_info'] && $item['comm_info']['pro'] && $item['comm_info']['pro']['type'] && !$item['comm_info']['pro']['cname']) {
                            $cname = isset($types[$item['comm_info']['pro']['type']]) && $types[$item['comm_info']['pro']['type']]['value'] ? $types[$item['comm_info']['pro']['type']]['value'] : '';
                            $item['comm_info']['pro']['cname'] = $this->returnCname($item['comm_info']['pro']['type'], $cname);
                        }
                        $item['user']['user_id'] = isset($oaUsers[$item['user']['uid']]) && $oaUsers[$item['user']['uid']]['user_id'] ? $oaUsers[$item['user']['uid']]['user_id'] : '';
                        $item['user']['user_name'] = isset($oaUsers[$item['user']['uid']]) && $oaUsers[$item['user']['uid']]['name'] ? $oaUsers[$item['user']['uid']]['name'] : '';
                        $infos[$key] = $item;
                    }
                }
            } else {
                $endFlag = true;
//                return ['code' => ['', $data['message']]];
            }
            $params['page']++;
        }

        return $infos;
    }
    /**
     * 获取当前用户是否有验真权限
     *
     * @param [type] $user
     *
     * @return void
     * @author yml
     */
    public function getValidRight($user, $type)
    {
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return ['right' => 0];
        }
        $userId = $service->userId;
        $config = app($this->thirdPartyInterfaceService)->getThirdPartyInterfaceByWhere('invoice_cloud', $user, ['is_use' => [1]]);
        if (!$config || !$config->type) {
            return ['right' => 0];
        }
        // 查询验真开关是否开启
        $configValid = $this->syncCorpConfigParam($user, ['sflag' => 2]);
        if (!isset($configValid['valid_on_off']) || $configValid['valid_on_off']) {
            return ['right' => 0];
        }
        $number = $configValid['on_out'];
        // 查验功能是否只允许录入 0否，1是
        $onlyInput = $number & 64;
        if ($configValid['on_out'] && $onlyInput) {
            return ['right' => 0];
        }
        // 是否仅允许企业台账中使用手动查验功能 0否，1是
        $onlyCorp = $number & 128;
        if ($configValid['on_out'] && $onlyCorp) {
            return ['right' => $type == 'person' ? 0 : 1];
        }
        $validUsers = $configValid['valid_user'] ? json_decode($configValid['valid_user'], 1) : [];
        if (!$validUsers || in_array($userId, $validUsers)) {
            return ['right' => 1];
        } else {
            return ['right' => 0];
        }
    }

    public function checkBefore($params, $user, $runId = 0)
    {
        // flag //  1  批量同步发票信息到ec //  2  同步报销信息 //  3  报销前检测  //  4  同步流程信息  //  5  发起报销
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        if ($app = Cache::get('invoice_app_'. $service->appKey)) {
            $app = json_decode($app, 1);
        } else {
            $app = $service->queryApp([]);
        }
        $aesSecret = $app['data']['aesSecret'] ?? '';
        $type = $params['type'] ?? '';
        $param['cid'] = $service->cid;
        $param['userId'] = $service->userId;
        $log = [
            'creator' => $user['user_id'],
            'run_id' => $runId,
        ];
        if (!$type) {
            $param['flag'] = 3;
            if (isset($params['fids'])) {
                $param['fids'] = $params['fids'];
                $log['invoice_id'] = implode($param['fids']);
            }
            if (isset($params['items']) && !empty($params['items'])) {
                $param['items']= $params['items'];
                $log['invoice_id'] = implode(array_column($param['items'], 'fid'));
            }

            $logType = 44;
        } else {
            $types = [
                'cancel'  => '0',
                'reimburse'  => '1',
                'reimbursed'  => '2'
            ];
            $param['flag'] = 2;
            $param['sreim'] = $types[$type];
            $logType = $param['sreim'] === '0' ? 43 : ($param['sreim'] === '1' ? 41 : 42);
            $param['infos'] = $params['infos'];
            $log['invoice_id'] = implode(array_column($param['infos'], 'fid'));
        }
        $log['type'] = $logType;// 4 发票状态变化  41 锁定  42  核销  43  取消锁定  44 报销前检测
        $param = json_encode($param);
        $log['request_data'] = $param;
//        \Log::info('请求参数：');
//        \Log::info($param);
        $param1 = openssl_encrypt($param, 'AES-128-CBC', $aesSecret, OPENSSL_RAW_DATA, $aesSecret);
        $data = $service->checkBefore(base64_encode($param1));
//        \Log::info('返回数据');
//        \Log::info($data);
        $log['result_data'] = $data;
        if ($data['error_code'] == 0) {
            $returnData = $data['data'];
            $returnData = base64_decode($returnData);
            $returnData = openssl_decrypt($returnData, 'AES-128-CBC', $aesSecret, OPENSSL_RAW_DATA, $aesSecret);
//            \Log::info('请求结果：');
//            \Log::info($returnData);
            $log['result_data'] = $returnData;
            $returnArray = json_decode($returnData, 1);
            if ($returnArray && isset($returnArray['infos'])) {
                $log['error_message'] = '';
                foreach ($returnArray['infos'] as $info) {
                    if ($info['ret'] > 0) {
                        $log['error_message'] .= $info['message']. ' ';
                    }
                }
            }
            app($this->invoiceLogsService)->addLog([$log]);
            return json_decode($returnData, 1);
        }
        return $this->returnResult($data, $log);
    }

    public function checkBeforeReim($params, $user, $runId = 0)
    {
        $type = $params['type'] ?? '';
        $res = true;
        // 报销时先进行报销前检测
        if ($type == 'reimburse') {
//            $fids = array_column($params['infos'], 'fid');
            $infos = $params['infos'];
            foreach ($infos as $k => $info){
                $infos[$k]['sreim'] = 1;
            }
            $result = $this->checkBefore(['items' => $infos, 'reim' => json_encode(['dataid' => $runId])], $user, $runId);
            if ($result) {
                $res = false;
            }
        }
        if ($res) {
            $this->checkBefore($params, $user, $runId);
        }
        return true;
    }

    /** eo版本发票报销接口 支持一票多报  文档地址：https://imapp.teamsyun.com/web/#/17?page_id=1877
     * @param $params
     * @param $user
     * @param $runId
     * @param $runName
     * @return InvoiceCloudService|array|bool
     */
    public function checkBeforeReimV2($params, $user, $runId, $runName)
    {
        /** @var InvoiceCloudService $service */
        $service = $this->getUseInvocieCloudService($user);
        if (is_array($service)) {
            return $service;
        }
        $type = $params['type'] ?? '';
        $param['cid'] = $service->cid;
        $param['userId'] = $service->userId;
        $log = [
            'creator' => $user['user_id'],
            'run_id' => $runId,
        ];
        $types = [
            'cancel'  => '0',
            'reimburse'  => '1',
            'reimbursed'  => '2'
        ];
        $param['flag'] = 2;
        $param['sreim'] = $types[$type];
        $param['dataid'] = $runId;
        $param['name'] = $runName;
        $logType = $param['sreim'] === '0' ? 43 : ($param['sreim'] === '1' ? 41 : 42);
        $param['infos'] = $params['infos'];
        $fids = array_column($param['infos'], 'fid');
        $uniqueFids = array_unique(array_column($param['infos'], 'fid'));
        $log['invoice_id'] = implode(',', $uniqueFids);
        $log['type'] = $logType;// 4 发票状态变化  41 锁定  42  核销  43  取消锁定  44 报销前检测
//        \Log::info('请求参数：');
//        \Log::info($param);
        $log['request_data'] = json_encode($param);
        // 考虑一次报销 一张发票拆分的问题 需计算报销金额是否大于可报销金额
        $flag = false;
        if ($param['sreim'] == '1' && count($fids) != count($uniqueFids)) {
            $countFids = array_count_values($fids);
            foreach ($countFids as $key => $v) {
                if ($v > 1) {
                    $result = $this->checkFidReimMoney($param['infos'], $key, $user);
                    if (isset($result['error_code'])) {
                        $data = $result;
                        $flag = true;
                        break;
                    }
                }
            }
        }
        if (!$flag) {
            $data = $service->reimInvoice($param);
        }
//        \Log::info('返回数据');
//        \Log::info($data);
        $log['result_data'] = $data;
        if ($data['error_code'] == 0) {
            $returnData = $data['data'];
//            \Log::info('请求结果：');
//            \Log::info($returnData);
            if (!empty($returnData)) {
                $log['result_data'] = $returnData;
            }
            if ($returnData && isset($returnData['infos'])) {
                $log['error_message'] = '';
                foreach ($returnData['infos'] as $info) {
                    if ($info['ret'] > 0) {
                        $log['error_message'] .= $info['message']. ' ';
                    }
                }
            }
            app($this->invoiceLogsService)->addLog([$log]);
            return true;
        }
        return $this->returnResult($data, $log);
    }

    /** 计算报销金额是否大于可报销金额
     * @param $params
     * @param $fid
     * @param $user
     * @return array|bool
     */
    private function checkFidReimMoney($params, $fid, $user)
    {
        $invoice = $this->getInvoice(['id' => $fid], $user);
        if (isset($invoice['code']) && isset($invoice['code'][0]) && isset($invoice['code'][1])) {
            $dynamic = isset($invoice['dynamic']) ? $invoice['dynamic'] : '';
            $message = (isset($dynamic[0]) && $dynamic[0]) ? $dynamic[0] : trans($invoice['code'][0] . '.' . $invoice['code'][1]);
            return ['error_code' => 9999, 'message' => $message];
        }
        $invoice = $invoice['invoice'];
        $money = array_sum(array_column($params,'amount'));
        $price = isset($invoice) && isset($invoice['comm_info']) && isset($invoice['comm_info']['price']) ? $invoice['comm_info']['price'] : [];
        $total = $price['total'] ? floatval($price['total']) : 0;
        $zreim = $price['zreim'] ? floatval($price['zreim']) : 0;
        $treim = $price['treim'] ? floatval($price['treim']) : 0;
        $canReimMoney = $total - $zreim - $treim;
        if ($money > $canReimMoney) {
            $code = $invoice['code'] ?? '';
            $number = $invoice['number'] ?? '';
            $message = trans('invoice.reim_money_greater_than_can_reim');
            if ($code && $number) {
                $message = trans('invoice.invoice_code') .': ' . $code . trans('invoice.invoice_number') .': ' . $number . ' ' .trans('invoice.reim_money_greater_than_can_reim');
            } elseif ($code) {
                $message = trans('invoice.invoice_code') .': ' . $code . ' ' .trans('invoice.reim_money_greater_than_can_reim');
            } elseif ($number) {
                $message = trans('invoice.invoice_number') .': ' . $number . ' ' .trans('invoice.reim_money_greater_than_can_reim');
            }
            return ['error_code' => 9999, 'message' => $message];
        }
        return true;
    }

    public function checkBack($workFlowId, $nodeId)
    {
        $outsendInfos = app($this->invoiceFlowNodeActionSettingRepositories)->getFieldInfo(['workflow_id' => $workFlowId, 'node_id' => $nodeId, 'action' => 'cancel', 'trigger_time' =>3]);
        if ($outsendInfos) {
            return true;
        } else {
            return false;
        }
    }

    /** 收回流程触发动作 取消报销
     * @param $user
     * @param $runInfo
     * @return bool
     */
    public function syncBackInvoice($user, $runInfo)
    {
        $runId = $runInfo['run_id'] ?? '';
        $flowId = $runInfo['flow_id'] ?? '';
        $nodeId = $runInfo['current_flow_run_process_info']['flow_process'] ?? '';
        $formId = $runInfo['flow_run_has_one_flow_type']['form_id'] ?? '';
        // 先校验当前流程当前节点是否配置收回操作
        $flag = $this->checkBack($flowId, $nodeId);
        if ($flag) {
            // 获取流程表单信息
            Queue::push(new SyncInvoiceManageJob(['type' => 'flow-back', 'userInfo' => $user, 'param' => compact('runId', 'flowId', 'nodeId', 'formId')]));
        }
        return true;
    }

    public function backInvoice($params, $user)
    {
        $runId = $params['runId'] ?? '';
        $flowId = $params['flowId'] ?? '';
        $nodeId = $params['nodeId'] ?? '';
        $formId = $params['formId'] ?? '';
        $runName = $params['run_name'] ?? '';
        $info = app($this->flowService)->getFlowFormParseData([
            'status' => 'handle',
            'nodeId' => $nodeId,
            'formId' => $formId,
            'flowId' =>$flowId,
            'runId' => $runId
        ], $user);
//        $apiData = $this->parseInvoiceParam($flowId, $info['parseData'], $runId);
//        if (count($apiData) == count($apiData, 1) && !isset($apiData['params'])) {
//            return $this->cancelInvoice(['ids' => $apiData, 'reim' => json_encode(['dataid' => $runId])], $user, $runId);
//        } else {
//            // 按金额报销
//            return $this->checkBeforeReim(['infos' => $apiData['params'], 'type' => 'cancel'], $user, $runId);
//        }
        $apiData = $this->parseInvoiceParamV2($flowId, $info['parseData']);
        if (!empty(($apiData))) {
            $this->checkBeforeReimV2(['infos' => $apiData, 'type' => 'cancel'], $user, $runId, $runName);
        } else {
            $result = ['error_code' => '9999', 'message' => trans('invoice.not_get_flow_data')];
            $log = [
                'creator' => $user['user_id'],
                'type' => 'cancel',
                'request_data' => $params,
                'run_id' => $runId,
                'result_data' => $result
            ];
            return $this->returnResult($result, $log);
        }
//        return $this->cancelInvoice($params, $user, $runId);
    }
    public function getWeixinAccessToken($user)
    {
        $service = new InvoiceCloudService([], $user);
        return $service->getWxTokenV2();
    }

    public function getInvoiceField($params, $user)
    {
        $invoiceId = $params['invoice_id'] ?? '';
        $field = $params['field'] ?? '';
        if (!$invoiceId || !$field) {
            return '';
        }
        $invoice = $this->getInvoice(['id' => $invoiceId], $user);
        if (!$invoice || empty($invoice) || !is_array($invoice)) {
            return '';
        }$invoice = $invoice['invoice'];
        $fields = $this->invoiceFieldKeys();
        $basicFieldKeys = $fields['basic'] ?? [];
        $commonFieldKeys = $fields['common'] ?? [];
        $modifyFieldKeys = $fields['modify'] ?? [];
        $extFieldKeys = $fields['ext'] ?? [];
        if (in_array($field, array_keys($basicFieldKeys))) {
            return $invoice[$field] ?? '';
        }
        $flag = explode('.', $field);
        if (in_array($field, array_keys($commonFieldKeys))) {
            if (count($flag) > 1) {
                switch ($flag[1]) {
                    case 'valid':
                        $validStatus = [
                            '0' => trans('invoice.no_inspection'),
                            '1' => trans('invoice.valid_checked'),
                            '2' => trans('invoice.valid_unchecked'),
                            '3' => trans('invoice.valid_failed'),
                            '4' => trans('invoice.invalid'),
                            '5' => trans('invoice.check_failed')
                        ];
                        return $validStatus[$invoice['comm_info'][$flag[0]][$flag[1]]] ?? '';
                        break;
                    case 'status':
                        $invoiceStatus = [
                            '0' => trans('invoice.normal'),
                            '1' => trans('invoice.out_of_work'),
                            '2' => trans('invoice.obsolete'),
                            '3' => trans('invoice.red_letter'),
                            '4' => trans('invoice.abnormal'),
                            '5' => trans('invoice.forwarded')
                        ];
                        return $invoiceStatus[$invoice['comm_info'][$flag[0]][$flag[1]]] ?? '';
                        break;
                    case 'sreim':
                        $invoiceReimStatus = [
                            '0' => trans('invoice.unreimbursed'),
                            '1' => trans('invoice.reimbursing'),
                            '2' => trans('invoice.reimbursed'),
                            '3' => trans('invoice.red_flush'),
                            '4' => trans('invoice.occupied'),
                        ];
                        return $invoiceReimStatus[$invoice['comm_info'][$flag[0]][$flag[1]]] ?? '';
                        break;
                    default:
                        return isset($invoice['comm_info'][$flag[0]][$flag[1]]) ? (string)$invoice['comm_info'][$flag[0]][$flag[1]] : '';
                }
            } else {
                return $invoice['comm_info'][$field];
            }
        }
        if (in_array($field, array_keys($modifyFieldKeys))) {
            if (count($flag) > 1) {
                return isset($invoice['modify_info'][$flag[0]][$flag[1]]) ? $invoice['modify_info'][$flag[0]][$flag[1]] : '';
            } else {
                if ($field == 'ctm') {
                    return date('Y-m-d H:i:s', $invoice['modify_info']['ctm']);
                } else if ($field == 'source') {
                        $invoiceSources = $this->getTypes(['type' => 4], $user);
                    $invoiceSources = array_column($invoiceSources, NULL, 'key');
                    return $invoiceSources[$invoice['modify_info']['source']]['value'] ?? '';
                } else if ($field == 'attr') {
                    $attr = [ '1' => trans('invoice.attr_corp'), '2' => trans('invoice.attr_person')];
                    return $attr[$invoice['modify_info']['attr']] ?? '';
                } else if ($field == 'furl') {
                    return $invoice['modify_info']['url'] ?? '';
                } else if ($field == 'pdf') {
                    return $invoice['modify_info']['pdfUrl'] ?? '';
                } else {
                    return isset($invoice['modify_info'][$field]) ? $invoice['modify_info'][$field] : '';
                }
            }
        }
        if (in_array($field, array_keys($extFieldKeys))) {
            $extInfo = $invoice['ext'] ? json_decode($invoice['ext'], 1) : [];
            if (count($flag) > 1) {
                return $extInfo[$flag[0]][$flag[1]] ?? '';
            } else {
                if (in_array($field, ['corp_seal', 'high_way', 'oil_mark', 'transit'])) {
                    return $extInfo[$field] == 1 ? trans('invoice.yes') : trans('invoice.no');
                }
                return isset($extInfo[$field]) ? (string)$extInfo[$field] : '';
            }
        }
        return '';
    }

    public function invoiceFieldKeys($params = [], $user = null)
    {
        $type = $params['type'] ?? '';
        $invoiceId = $params['invoice_id'] ?? '';
        if ($invoiceId) {
            $invoice = $this->getInvoice(['id' => $invoiceId], $user);
            $type = $invoice['invoice']['comm_info']['pro']['type'] ?? '';
        } else if (!is_int($type)) {
            $invoiceTypes = $this->getTypes([], $user);
            $invoiceTypes = array_column($invoiceTypes, NULL, 'value');
            if (isset($invoiceTypes[$type]) && !empty($invoiceTypes[$type])) {
                $type = $invoiceTypes[$type]['key'];
            }
        }
        $basicFieldKeys = [
            'id' => trans('invoice.id'), //'发票唯一值',
            'code' => trans('invoice.invoice_code'), //'发票代码',
            'number' => trans('invoice.invoice_number'),//'发票号码',
//            'suser' => trans('invoice.invoice_number'),//'发票归属人'
        ];
        $comFieldKeys = [
            'pro.cname' => trans('invoice.invoice_type'),//'发票类型',
            'pro.ccode' => trans('invoice.type_code'),//'发票票种编码',
            'pro.date' => trans('invoice.invoice_date'),//'开票日期',
            'pro.chcode' => trans('invoice.check_code'),//'开票校验码',
            'pro.kind' => trans('invoice.kind'),//'发票消费类型',
            'pro.valid' => trans('invoice.valid'),//'查验状态',
            'pro.status' => trans('invoice.invoice_normal_status'),//'发票本身状态',
            'pro.sreim' => trans('invoice.reim_status'),//'发票报销状态',
            'pro.ccy' => trans('invoice.ccy'),//'币种类型',
            'price.amount' => trans('invoice.amount'),//'不含税',
            'price.total' => trans('invoice.total'),//'含税金额合计',
            'price.zreim' => trans('invoice.reiming_money'),//'报销中金额',
            'price.treim' => trans('invoice.reimed_money'),//'已报销金额',
            'payer.tcode' => trans('invoice.payer_tcode'),//'销售方纳税人识别号',
            'payer.company' => trans('invoice.payer_company'),//'销售方名称',
            'buyer.tcode' => trans('invoice.buyer_tcode'),//'购买方纳税人识别号',
            'buyer.company' => trans('invoice.buyer_company'),//'购买方名称'
        ];
        $modFieldKeys = [
            'furl' => trans('invoice.furl'),//'发票图片id或者链接',
            'pdf' => trans('invoice.pdf'),//'发票图片pdf或者链接',
            'attr' => trans('invoice.attr'),//'发票属性',
            'purp' => trans('invoice.purp'),//'发票备注',
            'ctm' => trans('invoice.ctm'),//'添加时间',
            'source' => trans('invoice.invoice_source'),//'发票来源',
            'verr_msg' => trans('invoice.verr_msg'),//'查验失败原因'
        ];
        $extFieldKeys = [
            '0' => [
                'ttax' => trans('invoice.ttax'),//'税额合计',
                'trate' => trans('invoice.tax_rate'),//'税率',
                'pcontact' => trans('invoice.payer_addr'),//'销售方地址及电话',
                'pbank' => trans('invoice.payer_bank'),//'销售方开户银行及账号',
                'bcontact' => trans('invoice.buyer_addr'),//'购买方地址及电话',
                'bbank' => trans('invoice.buyer_bank'),//'购买方开户银行及账号',
                'content' => trans('invoice.content'),//'货物或服务名称',
                'aperiod' => trans('invoice.aperiod'),//'会计期间',
                'corp_seal' => trans('invoice.corp_seal'),//'是否有公司印章',
                'form_name' => trans('invoice.form_name'),//'发票联次',
                'agent_mark' => trans('invoice.agent_mark'),//'是否代开',
                'acquisition' => trans('invoice.acquisition'),//'是否收购',
                'block_chain' => trans('invoice.block_chain'),//'区块链标记',
                'city' => trans('invoice.city'),//'市',
                'province' => trans('invoice.province'),//'省',
                'service_name' => trans('invoice.service_name'),//'服务类型',
                'reviewer' => trans('invoice.reviewer'),//'复核人',
                'receiptor' => trans('invoice.receiptor'),//'收款人',
                'issuer' => trans('invoice.issuer'),//'开票人',
                'transit' => trans('invoice.transit'),//'通行费标志',
                'oil_mark' => trans('invoice.oil_mark'),//'成品油标志',
                'machine_code' => trans('invoice.machine_code'),//'机器编号',
                'ciphertext' => trans('invoice.ciphertext'),//'密码区',
                'category' => trans('invoice.category'),//'种类', // 卷票单独有
                'high_way' => trans('invoice.high_way'),//'	高速标记',
                'code_confirm' => trans('invoice.code_confirm'),//'机打发票代码',
                'number_confirm' => trans('invoice.number_confirm'),//'机打发票号码',
                'stax' => trans('invoice.stax'),//'车船税',
                'comment' => trans('invoice.comment'),//'备注'
            ],
            // 机动车销售统一发票
            '6' => [
                'ttax' => trans('invoice.ttax'),// '税额合计',
                'trate' => trans('invoice.trate'),// '税率',
                'pcontact' => trans('invoice.pcontact'),// '销售方地址及电话',
                'pbank' => trans('invoice.pbank'),// '销售方开户银行及账号',
                'bcontact' => trans('invoice.bcontact'),// '购买方地址及电话',
                'bbank' => trans('invoice.bbank'),// '购买方开户银行及账号',
                'content' => trans('invoice.content'),// '货物或服务名称',
                'machine_code' => trans('invoice.machine_code'),// '机器编号',
                'machine_number' => trans('invoice.machine_number'),// '机打号码',
                'city' => trans('invoice.city'),// '市',
                'province' => trans('invoice.province'),// '省',
                'vehicleDetail.idCode' => trans('invoice.vehicleDetail_idCode'),// '组织机构代码',
                'vehicleDetail.carType' => trans('invoice.vehicleDetail_carType'),// '车辆类型',
                'vehicleDetail.brankNumber' => trans('invoice.vehicleDetail_brankNumber'),// '厂牌型号',
                'vehicleDetail.certificateNumber' => trans('invoice.vehicleDetail_certificateNumber'),// '合格证号',
                'vehicleDetail.commodityInspectionNumber' => trans('invoice.vehicleDetail_commodityInspectionNumber'),// '商检单号',
                'vehicleDetail.engineCode' => trans('invoice.vehicleDetail_engineCode'),// '发动机号',
                'vehicleDetail.importationNumber' => trans('invoice.vehicleDetail_importationNumber'),// '进口证明书号',
                'vehicleDetail.seatingCapacity' => trans('invoice.vehicleDetail_seatingCapacity'),// '限乘人数',
                'vehicleDetail.vehicleIdentificationCode' => trans('invoice.vehicleDetail_vehicleIdentificationCode'),// '车辆识别代号',
                'vehicleDetail.taxOfficeCode' => trans('invoice.vehicleDetail_taxOfficeCode'),// '主管税务机关代码',
                'vehicleDetail.taxOfficeName' => trans('invoice.vehicleDetail_taxOfficeName'),// '主管税务机关名称',
                'vehicleDetail.dutyPaidNumber' => trans('invoice.vehicleDetail_dutyPaidNumber'),// '完税凭证号码',
                'vehicleDetail.origin' => trans('invoice.vehicleDetail_origin'),// '产地'
            ],
            // 二手车车销售统一发票
            '7' => [
                'content' => trans('invoice.content'),// '货物或服务名称',
                'machine_code' => trans('invoice.machine_code'),// '机器编号',
                'city' => trans('invoice.city'),// '市',
                'province' => trans('invoice.province'),// '省',
                'sellAddr' => trans('invoice.sellAddr'),// '销售方地址',
                'sellTel' => trans('invoice.sellTel'),// '销售方电话',
                'buyAddr' => trans('invoice.buyAddr'),// '购买方地址',
                'buyTel' => trans('invoice.buyTel'),// '购买方电话',
                'numberOrderError' => trans('invoice.numberOrderError'),// '发票联次',
                'aperiod' => trans('invoice.aperiod'),// '会计期间',
                'usedVehicle.carType' => trans('invoice.usedVehicle_carType'),// '车辆类型',
                'usedVehicle.brankNumber' => trans('invoice.usedVehicle_brankNumber'),// '厂牌型号',
                'usedVehicle.vehicleIdentificationCode' => trans('invoice.usedVehicle_vehicleIdentificationCode'),// '车辆识别代号',
                'usedVehicle.licensePlate' => trans('invoice.usedVehicle_licensePlate'),// '车牌号',
                'usedVehicle.registrationNumber' => trans('invoice.usedVehicle_registrationNumber'),// '登记证号',
                'usedVehicle.auctionTaxCode' => trans('invoice.usedVehicle_auctionTaxCode'),// '经营、拍卖单位纳税人识别号',
                'usedVehicle.auctionCompany' => trans('invoice.usedVehicle_auctionCompany'),// '经营、拍卖单位名称',
                'usedVehicle.auctionTelephone' => trans('invoice.usedVehicle_auctionTelephone'),// '经营、拍卖单位电话',
                'usedVehicle.auctionAddress' => trans('invoice.usedVehicle_auctionAddress'),// '经营、拍卖单位地址',
                'usedVehicle.auctionBankAccount' => trans('invoice.usedVehicle_auctionBankAccount'),// '经营、拍卖单位开户行、账号',
                'usedVehicle.marketTaxCode' => trans('invoice.usedVehicle_marketTaxCode'),// '二手车市场识别号',
                'usedVehicle.marketCompany' => trans('invoice.usedVehicle_marketCompany'),// '二手车市场名称',
                'usedVehicle.marketAddress' => trans('invoice.usedVehicle_marketAddress'),// '二手车市场地址',
                'usedVehicle.marketTelephone' => trans('invoice.usedVehicle_marketTelephone'),// '二手车市场电话',
                'usedVehicle.marketBankAccount' => trans('invoice.usedVehicle_marketBankAccount'),// '二手车市场开户行、账号',
                'usedVehicle.transferVehicleManagement' => trans('invoice.usedVehicle_transferVehicleManagement'),// '转入地车辆管理所名称',
                'stax' => trans('invoice.stax'),// '车船税',
            ],
            // 定额发票
            '8' => [
                'city' => trans('invoice.city'),// '市',
                'province' => trans('invoice.province'),// '省',
                'companySeal' => trans('invoice.companySeal'),// '是否有公司印章',
                'content' => trans('invoice.content'),// '货物或服务名称',
            ],
            // 	出租车发票
            '9' => [
                'city' => trans('invoice.city'),// '市',
                'province' => trans('invoice.province'),// '省',
                'time_geton' => trans('invoice.time_geton'),// '上车时间',
                'time_getoff' => trans('invoice.time_getoff'),// '下车时间',
                'mileage' => trans('invoice.mileage'),// '里程',
                'place' => trans('invoice.place'),// '发票所在地',
                'license_plate' => trans('invoice.license_plate'),// '车牌号',
                'fare' => trans('invoice.fare'),// '燃油费',
                'content' => trans('invoice.content'),// '货物或服务名称',
                'surcharge' => trans('invoice.surcharge'),// '附加费'
            ],
            // 机打发票
            '10' => [
                'city' => trans('invoice.city'),// '市',
                'province' => trans('invoice.province'),// '省',
                'companySeal' => trans('invoice.companySeal'),// '是否有公司印章',
                'time' => trans('invoice.time'),// '时间',
                'category' => trans('invoice.category'),// '种类',
                'content' => trans('invoice.content'),// '货物或服务名称',
            ],
            // 可报销发票
            '11' => [
            ],
            // 火车票
            '12' => [
                'time' => trans('invoice.time'),// '时间',
                'name' => trans('invoice.name'),// '乘车人姓名',
                'station_geton' => trans('invoice.station_geton'),// '上车车站',
                'station_getoff' => trans('invoice.station_getoff'),// '下车车站',
                'train_number' => trans('invoice.train_number'),// '车次',
                'seat' => trans('invoice.seat'),// '座位类型',
                'serial_number' => trans('invoice.serial_number'),// '序列号',
                'user_id' => trans('invoice.user_id'),// '身份证号',
                'content' => trans('invoice.content'),// '货物或服务名称',
                'ticket_gate' => trans('invoice.ticket_gate'),// '检票口',
            ],
            // 过路费发票
            '13' => [
                'time' => trans('invoice.time'),// '时间',
                'entrance' => trans('invoice.entrance'),// '入口',
                'exit' => trans('invoice.exit'),// '出口',
                'highway_flag' => trans('invoice.highway_flag'),// '高速标志',
                'content' => trans('invoice.content'),// '货物或服务名称',
            ],
            // 船票
            '14' => [
                'time' => trans('invoice.time'),// '时间',
                'name' => trans('invoice.name'),// '乘车人姓名',
                'station_geton' => trans('invoice.station_geton'),// '出发车站',
                'station_getoff' => trans('invoice.station_getoff'),// '到达车站',
                'city' => trans('invoice.city'),// '市',
                'province' => trans('invoice.province'),// '省',
                'content' => trans('invoice.content'),// '货物或服务名称',
            ],
            // 客运汽车
            '15' => [
                'time' => trans('invoice.time'),// '时间',
                'name' => trans('invoice.name'),// '乘车人姓名',
                'station_geton' => trans('invoice.station_geton'),// '出发车站',
                'station_getoff' => trans('invoice.station_getoff'),// '到达车站',
                'user_id' => trans('invoice.user_id'),// '身份证号',
                'train_number' => trans('invoice.train_number'),// '车次',
                'seat' => trans('invoice.seat'),// '座位类型',
                'serial_number' => trans('invoice.serial_number'),// '序列号',
                'content' => trans('invoice.content'),// '货物或服务名称',
            ],
            // 航空运输电子客票行程单
            '16' => [
                'user_name' => trans('invoice.user_name'),// '乘机人姓名',
                'user_id' => trans('invoice.user_id'),// '身份证号',
                'agentcode' => trans('invoice.agentcode'),// '销售单位代号',
                'issue_by' => trans('invoice.issue_by'),// '填开单位',
                'fare' => trans('invoice.fare1'),// '票价',
                'ttax' => trans('invoice.ttax'),// '税费',
                'fuel_surcharge' => trans('invoice.fuel_surcharge'),// '燃油附加费',
                'caac_development_fund' => trans('invoice.caac_development_fund'),// '民航发展基金',
                'insurance' => trans('invoice.insurance'),// '保险费',
                'international_flag' => trans('invoice.international_flag'),// '国内国际标签',
                'print_number' => trans('invoice.print_number'),// '印刷序号',
                'content' => trans('invoice.content'),// '货物或服务名称'
            ],
            // 国际小票
            '17' => [
                'store_name' => trans('invoice.store_name'),// '店名',
                'time' => trans('invoice.time'),// '时间',
                'tax' => trans('invoice.tax'),// '税费',
                'discount' => trans('invoice.discount'),// '折扣',
                'tips' => trans('invoice.tips'),// '小费',
                'content' => trans('invoice.content'),// '货物或服务名称'
            ],
            // 滴滴行程单
            '18' => [
                'date_start' => trans('invoice.date_start'),// '行程开始时间',
                'date_end' => trans('invoice.date_end'),// '行程结束时间',
                'phone' => trans('invoice.phone'),// '行程人手机号',
                'content' => trans('invoice.content'),// '货物或服务名称'
            ],
            // 完税证明
            '19' => [
                'content' => trans('invoice.content'),// '货物或服务名称'
            ],
            // 卷式发票
            '20' => [

            ],
            // 地铁票
            '21' => [

            ],
            // 区块链
            '22' => [

            ],
        ];
        if (!$params || $type == 'all') {
            $ext = [];
            array_map(function ($value) use (&$ext) {
                $ext = array_merge($ext, $value);
            }, $extFieldKeys);
            $extFieldKeys = $ext;
        } else {
            if (in_array(intval($type), [1, 2, 3, 4, 5])) {
                $extFieldKeys = $extFieldKeys[0];
            } else {
                $extFieldKeys = $extFieldKeys[$type] ?? [];
            }
        }
        if (!$params) {
            return ['basic' => $basicFieldKeys, 'common' => $comFieldKeys, 'modify' => $modFieldKeys, 'ext' => $extFieldKeys];
        } else {
            $fields =  array_merge($basicFieldKeys, $comFieldKeys, $modFieldKeys, $extFieldKeys);
            $result = [];
            array_map(function ($key, $value) use (&$result) {
                $result[] = [ 'key' => $key, 'value' => $value ];
            }, array_keys($fields), $fields);
            return $result;
        }
    }
    

    public function getInvoiceParams($user, $param)
    {
        $appid = InvoiceCloudService::PUBLIC_WX_APPID;
        $type = $param['type'] ?? '';
        if ($type == 'appid') {
            return compact('appid');
        }
        // 获取access_token
        $accessToken = $this->getWeixinAccessToken($user);
        $token = $accessToken['data']['token'] ?? '';
        if (!$token || (isset($accessToken['error_code']) && $accessToken['error_code'] > 0)) {
            return $accessToken;
        }
        // 获取api_ticket
        $apiTicket = $this->getTicket($token, 'wx');
        if (!$apiTicket || isset($apiTicket['code'])) {
            return $apiTicket;
        }
        // cardSign 参数处理
        $cardType = 'INVOICE';
        $timestamp = time();
        $nonceStr = md5(uniqid(microtime(true), true));
        $signType = 'SHA1';
        $cardSign = sha1($apiTicket . $appid . $timestamp . $nonceStr . $cardType);
        return compact('timestamp', 'nonceStr', 'signType', 'cardSign', 'appid');
    }

    public function getTicket($access_token, $source)
    {
        switch ($source) {
            case 'qywx':
                return app($this->workWechatService)->getTicket($access_token);
            case 'wx':
                return app($this->weixinService)->getTicket($access_token);
        }
    }

    public function syncCorpConfigParam($user, $params, $service = null)
    {
        if (!$service) {
            $service = $this->getUseInvocieCloudService($user);
            if (is_array($service)) {
                return $service;
            }
        }
        $changeRole = false;
        $userId = $service->getInvoiceUserId($user['user_id']);
        if ($service->role == 1) {
            $changeRole = true;
            $service->role = 2;
            $service->buildToken($service->cid, $userId);
        }
        $data = $service->syncCorpConfig($params);
        // 返回
        $param = [];
        if ($data['error_code'] == 0) {
            $infos = $data['data'] && $data['data']['infos'] ? $data['data']['infos'] : [];
            $param['on_out'] = $data['data'] && $data['data']['on_out'] ? $data['data']['on_out'] : 0;
            $keys = [
                '0' => 'valid_total',   // 验真金额
//                '1' => 'corp_taxnos',   // 允许报销那些税号
                '2' => 'corp_taxnos',
                '3' => 'valid_on_off',  // 是否允许验真
                '4' => 'recognize_valid_on_off',    // 是否自动验真
                '5' => 'valid_user',    // 设置允许验真用户
                '8' => 'reimbursable_month',    // 设置报销有效时间
                '12' => 'reim',         // 那些查验结果可以报销
                '13' => 'corp_taxno'    // 	是否指定部分税号可以报销
            ];
            $keyArray = array_keys($keys);
            foreach ($infos as $info) {
                if (in_array($info['key'], $keyArray)) {
                    if ($info['key'] == 2) {
                        $param[$keys[$info['key']]] = isset($info['titles']) ? json_encode($info['titles']) : '';
                    } else if ($info['key'] == 5) {
                        $param[$keys[$info['key']]] = isset($info['values']) ? json_encode($info['values']) : '';
                    } else {
                        $param[$keys[$info['key']]] = $info['values'][0];
                    }
                }
            }
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($param, $service->configId, $user);
        }
        if ($changeRole) {
            $service->role = 1;
            $service->buildToken($service->cid, $userId);
        }
        return $param;
    }

    public function returnCname($type, $cname)
    {
        if ($type == 28 && !$cname) {
            return '增值税电子专用发票';
        }
        return $cname;
    }
}