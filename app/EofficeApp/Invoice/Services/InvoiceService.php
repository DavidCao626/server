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
        // ????????????????????????
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
            // ???????????????????????????????????????????????????
            $data['data']['list'] = $data['data']['infos'] ?? [];
            if ($data['data']['list']) {
                // ????????????????????????????????? ??????cname???
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
            // ???????????????????????????????????????????????????
            if (isset($data['data']['total']) && $data['data']['total'] > 0 && (!isset($data['data']['infos']) || !$data['data']['infos'])){
                $params['page'] = ceil($data['data']['total'] / $params['limit']);
                return $this->getUnreimburseInvoiceList($params, $own);
            }
            $data['data']['list'] = $data['data']['infos'] ?? [];
            if ($data['data']['list']) {
                $types = $this->getTypes($params, $own);
                $types = array_column($types, NULL, 'key');
                foreach ($data['data']['list'] as $key => $item) {
                    // ?????????????????????id??????????????????????????????
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
                case '1':// ?????????
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

    /** ??????????????????
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
                // ???????????????
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
        // ????????????????????????????????????????????????  ??????100???
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

    /** ????????????????????????
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
            // ???????????????????????????????????????????????????
            $data['data']['list'] = $data['data']['datas'] ?? [];
            if (isset($params['field'])){
                // ???????????????????????????????????????  ???????????????????????????????????????????????????
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

    /** ????????????????????????
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
            // ???????????????????????????????????????????????????
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

    /** ????????????????????????
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
            // ???????????????????????????????????????????????????
            return $data['data'];
        } else {
            if (isset($data['code'])) {
                return $data;
            }
            return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
        }
    }

    /** ????????????/??????
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
            // ??????????????????
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
            // ?????????????????? -- ??????????????????
//            $crop = $service->QueryCorpByCid(['cid' => ]);
            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig($params, ['config_id' => [$service->configId]]);
            return $data['data'];
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    /** ???????????????????????????
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

    /** ????????????id???????????????
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

    /** ??????????????????
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

    /** t??????????????????
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
            'type' => '2', // ????????????
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
            // ????????????????????????????????? -- ???????????????????????????BUG??????????????? ?????????????????????token????????????token????????????????????????????????????token 20201126
            if (isset($params['role'])) {
                $role = $params['role'] ?? '1';
                $relatedUser = [
                    'role' => $role
                ];
                $log['type'] = $role == '1' ? '6' : '7'; // 6 ??????????????????  7 ???????????????
                Cache::pull('invoice_cloud_token_teamsyun_' . $params['userId']);
                Cache::pull('invoice_cloud_teamsyun_users_'. $service->cid);
            }
            app($this->invoiceCloudTeamsYunUserRepositories)->updateData($relatedUser, ['userId' => $params['userId']]);
        }
        // ???????????????????????????id
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
            'type' => '1', // ????????????
            'request_data' => $params,
            'request_api_data' => $params,
        ];
        // ????????????????????? ???????????????
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
        // ???????????????????????????id
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

    /** ??????????????????
     * @param $params
     * @param $user
     * @return array|bool
     */
    public function batchSyncUser($params, $user, $service = null)
    {
        $type = 3; // ??????????????????
        if (!empty($params) && isset($params['type'])){
            $type = $params['type'] == 'sync' ? '4' : ($params['type'] == 'first' ? '5' : '3'); // ??????????????????   5 ????????????????????????
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
        // ????????????????????????????????????????????????  --- ??????????????????
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
                // ??????????????????????????? ????????????????????????
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
        // ????????????????????????
        $userAccounts = array_column($users, 'user_id', 'user_accounts');
        $log = [
            'creator' => $user['user_id'],
            'type' => $type,
            'request_data' => $params
        ];
        // ?????????user???????????????????????????
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
                // ????????????????????????
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
            // ?????????????????? ?????????????????????  50???????????????
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
            // ??????????????????
            $this->sendMessage('invoice-user', $user);
            Cache::pull('invoice_first_sync_'. $service->cid);
            return ['code' => ['user_already_sync', 'invoice']];
        }

        $relatedUsers = [];
        // ?????????????????????????????? ?????????
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
                // ??????????????????
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
//                        $log['error_message'] .= '???'. $value. ': '. implode('/', $errorAccounts[$key]);
                        $log['error_message'] .= '???'. $value;
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
        // ???????????? ??????????????????????????????????????????????????? -- 
        if ($type == 5) {
            $userId = $service->getInvoiceUserId($user['user_id']);
            if ($userId) 
                $this->updateUser(['role' => 2, 'userId' => $userId], $user);
                $this->syncCorpConfig($user);
        }
        Cache::pull('invoice_first_sync_'. $service->cid);
        // ??????????????????
        $this->sendMessage('invoice-user', $user);
        return true;
    }

    /** ??????????????????????????????????????? ????????? ??????????????????OA??????????????????????????????????????????????????????????????????userId?????? ???????????????????????????
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

    /** ??????/????????????????????????
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
            // ???????????????
            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig(['valid_on_off' => $params['valid']], ['config_id' => [$service->configId]]);
            return $data['data'] ?? true;
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    /** ??????/??????????????????
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

    /** ????????????????????????????????????
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
            // ???????????????
            app($this->thirdPartyInterfaceService)->UpdateInvoiceCloudTeamsYunUseConfig(['recognize_valid_on_off' => $params['auto_valid']], ['config_id' => [$service->configId]]);
            return $data['data'];
        }
        return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
    }

    /** ??????????????????
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
            'type' => '21', // 2 ????????????  21 ??????  22  ??????  23  ??????
            'request_data' => $params,
            'result_data' => $data,
        ];
        return $this->changeStatusReturnResult($data, $log);
    }

    /** ????????????
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
            'type' => '22', // 2 ????????????  21 ??????  22  ??????  23  ??????
            'request_data' => $params,
            'result_data' => $data,
            'invoice_id' => $params['info']['id'] ?? ''
        ];
//        if (isset($params['info']['id'])) {
//            Cache::pull('invoice_detail_'. $params['info']['id']);
//        }
        return $this->returnResult($data, $log, $service);
    }

    /** ????????????
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
            'type' => '23', // 2 ????????????  21 ??????  22  ??????  23  ??????
            'request_data' => $params,
            'result_data' => $data,
            'invoice_id' => $ids
        ];
        return $this->returnResult($data, $log, $service);
    }

    /** ??????????????????
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

    /** ??????????????????
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

    /** ??????????????????
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

    /** ???????????? --- ??????????????????
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
            'type' => 41, // 4 ??????????????????  41 ??????  42  ??????  43  ????????????
            'request_data' => $params,
            'result_data' => $data,
            'run_id' => $runId,
        ];
        return $this->changeStatusReturnResult($data, $log, $service);
    }

    /** ??????????????????
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
            'type' => 42, // 4 ??????????????????  41 ??????  42  ??????  43  ????????????
            'request_data' => $params,
            'result_data' => $data,
            'run_id' => $runId,
        ];
        return $this->changeStatusReturnResult($data, $log, $service);
    }

    /** ??????????????????
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
            'type' => 43, // 4 ??????????????????  41 ??????  42  ??????  43  ????????????
            'request_data' => $params,
            'result_data' => $data,
            'run_id' => $runId,
        ];

        return $this->changeStatusReturnResult($data, $log, $service);
    }

    /** ?????????????????? --- ?????????????????????????????????????????? ????????????
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
                    // ?????????????????????????????????
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


    /** ????????????????????????
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
        // ??????????????????  ---  ????????????id
        $log = [
            'creator' => $user['user_id'],
            'type' => '11', // ????????????
            'request_data' => $params,
        ];
        // ??????????????????
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
        // ??????????????????  ---  ????????????id
        $log = [
            'creator' => $user['user_id'],
            'type' => '11', // ????????????
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
//        // ?????????????????????????????????id????????????
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
//                    $invoices['data']['infos'][$key]['message'] = $invoice['ret'] == 0 ? '????????????' : trans('invoice.invoice_cloud.' . $invoice['ret']);
//                    if ($invoice['ret'] == 0) {
//                        $invoices['suc_nums'] += 1;
//                    } else {
//                        $invoices['fail_nums'] += 1;
//                        $invoices['fail_errors'][] = trans('invoice.invoice_cloud.' . $invoice['ret']);
//                    }
//                }
//            }
//            $log['error_message'] = '????????????' . $invoices['suc_nums'] . '??????' . '????????????' .$invoices['fail_nums']. '???'. ($invoices['fail_nums'] > 0 ? '??????????????????' . implode('???', $invoices['fail_errors']) : '') ;
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
               // ????????? --- 2021-03-18 ????????????????????????????????? ?????????????????????????????????????????????
               $configParam = $this->syncCorpConfigParam($user, ['sflag' => 2]);
               //        $autoValid = app($this->thirdPartyInvoiceCloudTeamsYunRepository)->getFieldValue('recognize_valid_on_off', ['config_id' => $service->configId]);
                       $autoValid = $configParam['recognize_valid_on_off'] ?? 0;
        // ??????????????????
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
                                // ?????????????????? ??????????????????
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
                        $log['error_message'] = trans('invoice.recognition_succeeded') . $invoices['suc_nums'] . trans('invoice.some') . trans('invoice.recognition_failed') .$invoices['fail_nums']. trans('invoice.some'). ($invoices['fail_nums'] > 0 ? trans('invoice.failed_reason') . implode('???', $fail_errors) : '') ;
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
                $message = trans('invoice.recognition_fail'). trans('invoice.failed_reason') . implode('???', $errorMessage);
            } else {
                $message = trans('invoice.recognition_succeeded') . $successNum . trans('invoice.some') . trans('invoice.recognition_failed') .$failNum. trans('invoice.some'). ($failNum > 0 ? trans('invoice.failed_reason') . implode('???', $errorMessage) : '') ;
            }
            return compact('successNum', 'failNum', 'message');
        }
    }

    /** ????????????
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
//        $data = ['error_code' => 1675, 'message' => '????????????????????????????????????'];
        $log = [
            'creator' => $user['user_id'],
            'type' => '12', // ????????????
            'request_data' => $params,
            'result_data' => $data,
            'invoice_id' => $params['id'] ?? ''
        ];
        return $this->returnResult($data, $log);
    }

    /** ???????????????????????????
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
            'type' => '12', // ????????????
            'request_data' => $params,
            'result_data' => $data,
        ];
        return $this->returnResult($data, $log);
    }

    function formatFid($fid)
    {
        return number_format($fid, 0 ,'', '');
    }

    /** ????????????
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
        // ??????????????????????????? ??????????????????
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

    /** ?????????????????? -- ??????????????????????????????  ???????????? ??????????????????  ???????????? ??????????????????
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
        // ????????? ??????????????????????????????
        if (app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$flowId]])) {
            Queue::push(new SyncInvoiceManageJob($params));
        }
//        $this->flowOutsendInvoice($params);
    }

    public function flowOutsendInvoice($param)
    {
//        Log::info($param);
        extract($param);
        // ?????????????????? 1.???????????????????????? 2.????????????????????? 3.???????????????????????? 4.????????????????????? 5.?????????
        //  reimburse ???????????? -- ????????????   reimbursed ???????????? -- ????????????     cancel ???????????? -- ????????????
        $methods = [
            'reimburse' => 'lockInvoice',
            'reimbursed' => 'reimbursedInvoice',
            'cancel' => 'cancelInvoice',
        ];
        $types = [
            'reimburse' => 41, // ????????????--????????????
            'reimbursed' => 42, // ????????????--????????????
            'cancel' => 43  // ????????????--????????????
        ];
        // ???????????????????????????????????? ????????????
        $setting = app($this->invoiceFlowSettingRepositories)->getOneFieldInfo(['workflow_id' => $flowId]);
        if($setting) {
            $outsendInfos = app($this->invoiceFlowNodeActionSettingRepositories)->getFieldInfo(['workflow_id' => $flowId]);
            foreach (['reimburse', 'reimbursed', 'cancel'] as $typeVal) {
                $currentProcessSetInfo = $this->checkOutsend($outsendInfos, $flowProcess, $typeVal);
                // ????????????????????????
                $nestProcessSetInfo = $this->checkOutsend($outsendInfos, $next_flow_process, $typeVal);
                // ??????????????????   // ????????????????????????   // ????????????????????????   // ????????????????????????
                if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingArrival'])) && (
                        ($currentProcessSetInfo['flowOutsendTimingArrival'] == 0 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($currentProcessSetInfo['back']) && $currentProcessSetInfo['back'] == 1))) || ($currentProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && (($nestProcessSetInfo['flowOutsendTimingArrival'] == 1 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($nestProcessSetInfo['back']) && $nestProcessSetInfo['back'] == 1))) || ($nestProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")))) {
                    if (method_exists($this,$methods[$typeVal])){
//                        $method = $methods[$typeVal];
                        // ????????????
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
//                                    // ???????????????
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
            'status' => 0,   // ??????????????????
            'flowOutsendTimingArrival' => 0, // 0???????????? 1????????????  2???????????????
            'flowOutsendTimingSubmit' => 0, // ?????????????????????
            'back' =>0, // ????????????????????????????????????????????? 0?????????
        ];
        if ($outsendInfos) {
            foreach ($outsendInfos as $outsendInfo) {
                if ($outsendInfo['action'] === $action){
                    if (in_array($outsendInfo['trigger_time'], [0, 1, 2])) { // 0 ???????????? 1???????????? 2???????????????
                        $outsendStatus['status'] = 1;
                        if ($outsendInfo['trigger_time'] === 0) {
                            $outsendStatus['flowOutsendTimingArrival'] = 0;
                            $outsendStatus['flowOutsendTimingSubmit'] = 1;
                            $outsendStatus['back'] = $outsendInfo['back'] == 0 ? 1 : 0;
                        } elseif ($outsendInfo['trigger_time'] === 1) {
                            $outsendStatus['flowOutsendTimingArrival'] = 1;
                            $outsendStatus['back'] = $outsendInfo['back'] == 0 ? 1 : 0;
                        } else {
                            // ?????????   ??????????????????
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
        // ????????????id????????????????????????  --- ?????????????????????????????????????????????????????????
        $setting = app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$flowId]]);
        $codeField = $setting['code_field'] ?? '';
        $moneyField = $setting['money_field'] ?? '';
        $current = time();
        $fids = [];
        $params = [];
        // ??????????????????
        $fieldValueArray = explode('_', $codeField);
        // ????????????
        if ($fieldValueArray && count($fieldValueArray) > 2) {
            $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
            // ???????????????????????? ?????????????????????????????????????????????
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
            // ?????????
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

    /** ????????????????????????eo??????????????????
     * @param     $flowId
     * @param     $flowData
     * @param int $runId
     */
    public function parseInvoiceParamV2($flowId, $flowData)
    {
    // ????????????id????????????????????????  --- ?????????????????????????????????????????????????????????
        $setting = app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$flowId]]);
        $codeField = $setting['code_field'] ?? '';
        $moneyField = $setting['money_field'] ?? '';
        $params = [];
        // ??????????????????
        $fieldValueArray = explode('_', $codeField);
        // ????????????
        if ($fieldValueArray && count($fieldValueArray) > 2) {
            $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
            // ???????????????????????? ?????????????????????????????????????????????
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
            // ?????????
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

    /** ??????????????????  ---  ??????????????????
     * @param $data
     * @return array|bool
     */
    private function returnResult($data, $log = [], $service = null)
    {
        if (isset($data['error_code']) && $data['error_code'] == 0) {
            if ($log) {
                // ???????????????????????????
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
                case '1':// ?????????
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
                case '1':// ?????????
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
        // ??????????????????????????????????????????  ??????????????????????????? -- ????????????access_token[20200630???????????????openApi??????????????????token]
        $platform = $params['platform'] ?? '';
        unset($params['platform']);
        if (!$platform) {
            return ['code' => ['invoice', 'get_platform_failed']];
        } else {
            // ????????????
            if($platform == 'enterprise_wechat') {
//                $accessToken = app($this->workWechatService)->getAccessToken();
                $flag = 10;
            } else {
                // ???????????????
//                $accessToken = app($this->weixinService)->getAccessToken();
                $flag = 11;
            }
        }
//        if (is_array($accessToken)) {
//            return $accessToken;
//        }
//        if (!$accessToken){
//            return ['code'=>['', '????????????????????????????????????????????????']];
//        }
//        \Log::info($params['invoices']);
        // ??????????????? ??????????????????????????????????????????
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
            'type' => '13', // ??????????????????
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
        // ??????????????????????????????
        if ($data['error_code'] == 0) {
            $infos = $data['data'] && $data['data']['infos'] ? $data['data']['infos'] : [];
            $keys = [
                '0' => 'valid_total',   // ????????????
//                '1' => 'corp_taxnos',   // ????????????????????????
                '2' => 'corp_taxnos',
                '3' => 'valid_on_off',  // ??????????????????
                '4' => 'recognize_valid_on_off',    // ??????????????????
                '5' => 'valid_user',    // ????????????????????????
                '8' => 'reimbursable_month',    // ????????????????????????
                '12' => 'reim',         // ??????????????????????????????
                '13' => 'corp_taxno'    // 	????????????????????????????????????
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
        // ??????????????????????????????
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
        // ????????????????????????
        $configValid = $this->syncCorpConfigParam($user, ['sflag' => 2], $service);
        $validUsers = $configValid['valid_user'] ? json_decode($configValid['valid_user'], 1) : [];
        // ?????? ?????????????????????
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
        // ??????????????????????????????
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
        // ????????????????????????????????????
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
            // ???????????????????????????????????????????????????
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
            // ???????????????????????????????????????????????????
            $data['data']['list'] = $data['data']['infos'] ?? [];
            unset($data['data']['infos']);
            $data['data']['count'] = $data['data']['total'] ?? 0;
            return $data['data'];
        } else {
            return ['code' => ['', $data['message']], 'dynamic' => $data['message']];
        }
    }

    /** ???????????? -- ??????????????????
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
            // ???????????????????????????????????????????????????  --- ??????????????????????????????
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
                // ??????????????????
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
                // ????????????
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
                // ?????????
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
        // ??????openApi??????
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
//             ???????????????????????????????????????
            $teamsYunParam = [
                'url' => $params['url'],
                'app_key' => $info['appId'],
                'app_secret' => $info['secret'],
                'oa_server_url' => $info['thirdAddr'],
                'open_application_id' => $openApiResult['id']
            ];
            app($this->thirdPartyInterfaceService)->editInvoiceCloud($teamsYunParam, 1, $user);
            // ??????????????????
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
        // ??????openApi?????? id ????????????????????????openApi???????????? ?????????????????????
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
            'type' => '12', // ????????????
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
                // ??????????????????
                $total = $this->syncCorpConfigParam($user, ['sflag' => 2, 'stype' =>2]);
                $config->teamsYun->valid_total = $total['valid_total'];
            } catch (\ErrorException $e) {
//                $config->token = '';
//                return ['code' => ['', $e->getMessage()]];
            }
        }
        return $config;
    }
//    // ????????????????????????
//    private function resetInvoiceCache($params, $user)
//    {
//        // ??????????????????????????????????????????
//        // ?????????????????????????????????????????????????????????
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

    /** ??????openapi??????id?????????????????????
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
            return ['code' => ['', '?????????WEB??????????????????']];
        }
        try{
            $token = $service->getToken();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ['code' => ['', $message], 'dynamic' => $message];
        }
        $comp = $params['comp'] ?? '';
        $lang = $this->getLocaleLang($user);
        // ??????????????????????????????????????????  ??????token
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
        // ???????????????
        $langArray = [
            'zh_cn',   // ???????????????
            'zh_tw',   // ???????????????
            'en_us',   // ???????????????
            'es_es',   // ????????????????????????
            'fr_fr',   // ???????????????
            'pt_pt',   // ????????????????????????
            'ar_ae',   // ???????????????????????????????????????
            'ru_ru',   // ??????????????????
            'ja_jp',   // ???????????????
            'de_de',   // ???????????????
            'ko_kr',   // ???????????????
            'th_th',   // ???????????????
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
            // ????????????????????????????????????????????????????????????
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
                    // ????????????????????????
                    $data['data']['list'][$key]['url'] = '';
                    if ($imageIndex = $value['image_index']) {
                        if (strpos($service->url, 'mypiaojia')){
                            $data['data']['list'][$key]['url'] = 'https://dl.mypiaojia.com/noauth/'. $imageIndex;
                        } else {
                            $data['data']['list'][$key]['url'] = $serviceUrl. $imageIndex;
                        }
                    }
                    // ??????id??????
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
        // ????????????
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
        // ??????????????????????????????????????????
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
        // ??????????????????
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
                    // ????????????????????????????????? ??????cname???
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
     * ???????????????????????????????????????
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
        // ??????????????????????????????
        $configValid = $this->syncCorpConfigParam($user, ['sflag' => 2]);
        if (!isset($configValid['valid_on_off']) || $configValid['valid_on_off']) {
            return ['right' => 0];
        }
        $number = $configValid['on_out'];
        // ????????????????????????????????? 0??????1???
        $onlyInput = $number & 64;
        if ($configValid['on_out'] && $onlyInput) {
            return ['right' => 0];
        }
        // ?????????????????????????????????????????????????????? 0??????1???
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
        // flag //  1  ???????????????????????????ec //  2  ?????????????????? //  3  ???????????????  //  4  ??????????????????  //  5  ????????????
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
        $log['type'] = $logType;// 4 ??????????????????  41 ??????  42  ??????  43  ????????????  44 ???????????????
        $param = json_encode($param);
        $log['request_data'] = $param;
//        \Log::info('???????????????');
//        \Log::info($param);
        $param1 = openssl_encrypt($param, 'AES-128-CBC', $aesSecret, OPENSSL_RAW_DATA, $aesSecret);
        $data = $service->checkBefore(base64_encode($param1));
//        \Log::info('????????????');
//        \Log::info($data);
        $log['result_data'] = $data;
        if ($data['error_code'] == 0) {
            $returnData = $data['data'];
            $returnData = base64_decode($returnData);
            $returnData = openssl_decrypt($returnData, 'AES-128-CBC', $aesSecret, OPENSSL_RAW_DATA, $aesSecret);
//            \Log::info('???????????????');
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
        // ?????????????????????????????????
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

    /** eo???????????????????????? ??????????????????  ???????????????https://imapp.teamsyun.com/web/#/17?page_id=1877
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
        $log['type'] = $logType;// 4 ??????????????????  41 ??????  42  ??????  43  ????????????  44 ???????????????
//        \Log::info('???????????????');
//        \Log::info($param);
        $log['request_data'] = json_encode($param);
        // ?????????????????? ??????????????????????????? ????????????????????????????????????????????????
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
//        \Log::info('????????????');
//        \Log::info($data);
        $log['result_data'] = $data;
        if ($data['error_code'] == 0) {
            $returnData = $data['data'];
//            \Log::info('???????????????');
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

    /** ?????????????????????????????????????????????
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

    /** ???????????????????????? ????????????
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
        // ?????????????????????????????????????????????????????????
        $flag = $this->checkBack($flowId, $nodeId);
        if ($flag) {
            // ????????????????????????
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
//            // ???????????????
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
            'id' => trans('invoice.id'), //'???????????????',
            'code' => trans('invoice.invoice_code'), //'????????????',
            'number' => trans('invoice.invoice_number'),//'????????????',
//            'suser' => trans('invoice.invoice_number'),//'???????????????'
        ];
        $comFieldKeys = [
            'pro.cname' => trans('invoice.invoice_type'),//'????????????',
            'pro.ccode' => trans('invoice.type_code'),//'??????????????????',
            'pro.date' => trans('invoice.invoice_date'),//'????????????',
            'pro.chcode' => trans('invoice.check_code'),//'???????????????',
            'pro.kind' => trans('invoice.kind'),//'??????????????????',
            'pro.valid' => trans('invoice.valid'),//'????????????',
            'pro.status' => trans('invoice.invoice_normal_status'),//'??????????????????',
            'pro.sreim' => trans('invoice.reim_status'),//'??????????????????',
            'pro.ccy' => trans('invoice.ccy'),//'????????????',
            'price.amount' => trans('invoice.amount'),//'?????????',
            'price.total' => trans('invoice.total'),//'??????????????????',
            'price.zreim' => trans('invoice.reiming_money'),//'???????????????',
            'price.treim' => trans('invoice.reimed_money'),//'???????????????',
            'payer.tcode' => trans('invoice.payer_tcode'),//'???????????????????????????',
            'payer.company' => trans('invoice.payer_company'),//'???????????????',
            'buyer.tcode' => trans('invoice.buyer_tcode'),//'???????????????????????????',
            'buyer.company' => trans('invoice.buyer_company'),//'???????????????'
        ];
        $modFieldKeys = [
            'furl' => trans('invoice.furl'),//'????????????id????????????',
            'pdf' => trans('invoice.pdf'),//'????????????pdf????????????',
            'attr' => trans('invoice.attr'),//'????????????',
            'purp' => trans('invoice.purp'),//'????????????',
            'ctm' => trans('invoice.ctm'),//'????????????',
            'source' => trans('invoice.invoice_source'),//'????????????',
            'verr_msg' => trans('invoice.verr_msg'),//'??????????????????'
        ];
        $extFieldKeys = [
            '0' => [
                'ttax' => trans('invoice.ttax'),//'????????????',
                'trate' => trans('invoice.tax_rate'),//'??????',
                'pcontact' => trans('invoice.payer_addr'),//'????????????????????????',
                'pbank' => trans('invoice.payer_bank'),//'??????????????????????????????',
                'bcontact' => trans('invoice.buyer_addr'),//'????????????????????????',
                'bbank' => trans('invoice.buyer_bank'),//'??????????????????????????????',
                'content' => trans('invoice.content'),//'?????????????????????',
                'aperiod' => trans('invoice.aperiod'),//'????????????',
                'corp_seal' => trans('invoice.corp_seal'),//'?????????????????????',
                'form_name' => trans('invoice.form_name'),//'????????????',
                'agent_mark' => trans('invoice.agent_mark'),//'????????????',
                'acquisition' => trans('invoice.acquisition'),//'????????????',
                'block_chain' => trans('invoice.block_chain'),//'???????????????',
                'city' => trans('invoice.city'),//'???',
                'province' => trans('invoice.province'),//'???',
                'service_name' => trans('invoice.service_name'),//'????????????',
                'reviewer' => trans('invoice.reviewer'),//'?????????',
                'receiptor' => trans('invoice.receiptor'),//'?????????',
                'issuer' => trans('invoice.issuer'),//'?????????',
                'transit' => trans('invoice.transit'),//'???????????????',
                'oil_mark' => trans('invoice.oil_mark'),//'???????????????',
                'machine_code' => trans('invoice.machine_code'),//'????????????',
                'ciphertext' => trans('invoice.ciphertext'),//'?????????',
                'category' => trans('invoice.category'),//'??????', // ???????????????
                'high_way' => trans('invoice.high_way'),//'	????????????',
                'code_confirm' => trans('invoice.code_confirm'),//'??????????????????',
                'number_confirm' => trans('invoice.number_confirm'),//'??????????????????',
                'stax' => trans('invoice.stax'),//'?????????',
                'comment' => trans('invoice.comment'),//'??????'
            ],
            // ???????????????????????????
            '6' => [
                'ttax' => trans('invoice.ttax'),// '????????????',
                'trate' => trans('invoice.trate'),// '??????',
                'pcontact' => trans('invoice.pcontact'),// '????????????????????????',
                'pbank' => trans('invoice.pbank'),// '??????????????????????????????',
                'bcontact' => trans('invoice.bcontact'),// '????????????????????????',
                'bbank' => trans('invoice.bbank'),// '??????????????????????????????',
                'content' => trans('invoice.content'),// '?????????????????????',
                'machine_code' => trans('invoice.machine_code'),// '????????????',
                'machine_number' => trans('invoice.machine_number'),// '????????????',
                'city' => trans('invoice.city'),// '???',
                'province' => trans('invoice.province'),// '???',
                'vehicleDetail.idCode' => trans('invoice.vehicleDetail_idCode'),// '??????????????????',
                'vehicleDetail.carType' => trans('invoice.vehicleDetail_carType'),// '????????????',
                'vehicleDetail.brankNumber' => trans('invoice.vehicleDetail_brankNumber'),// '????????????',
                'vehicleDetail.certificateNumber' => trans('invoice.vehicleDetail_certificateNumber'),// '????????????',
                'vehicleDetail.commodityInspectionNumber' => trans('invoice.vehicleDetail_commodityInspectionNumber'),// '????????????',
                'vehicleDetail.engineCode' => trans('invoice.vehicleDetail_engineCode'),// '????????????',
                'vehicleDetail.importationNumber' => trans('invoice.vehicleDetail_importationNumber'),// '??????????????????',
                'vehicleDetail.seatingCapacity' => trans('invoice.vehicleDetail_seatingCapacity'),// '????????????',
                'vehicleDetail.vehicleIdentificationCode' => trans('invoice.vehicleDetail_vehicleIdentificationCode'),// '??????????????????',
                'vehicleDetail.taxOfficeCode' => trans('invoice.vehicleDetail_taxOfficeCode'),// '????????????????????????',
                'vehicleDetail.taxOfficeName' => trans('invoice.vehicleDetail_taxOfficeName'),// '????????????????????????',
                'vehicleDetail.dutyPaidNumber' => trans('invoice.vehicleDetail_dutyPaidNumber'),// '??????????????????',
                'vehicleDetail.origin' => trans('invoice.vehicleDetail_origin'),// '??????'
            ],
            // ??????????????????????????????
            '7' => [
                'content' => trans('invoice.content'),// '?????????????????????',
                'machine_code' => trans('invoice.machine_code'),// '????????????',
                'city' => trans('invoice.city'),// '???',
                'province' => trans('invoice.province'),// '???',
                'sellAddr' => trans('invoice.sellAddr'),// '???????????????',
                'sellTel' => trans('invoice.sellTel'),// '???????????????',
                'buyAddr' => trans('invoice.buyAddr'),// '???????????????',
                'buyTel' => trans('invoice.buyTel'),// '???????????????',
                'numberOrderError' => trans('invoice.numberOrderError'),// '????????????',
                'aperiod' => trans('invoice.aperiod'),// '????????????',
                'usedVehicle.carType' => trans('invoice.usedVehicle_carType'),// '????????????',
                'usedVehicle.brankNumber' => trans('invoice.usedVehicle_brankNumber'),// '????????????',
                'usedVehicle.vehicleIdentificationCode' => trans('invoice.usedVehicle_vehicleIdentificationCode'),// '??????????????????',
                'usedVehicle.licensePlate' => trans('invoice.usedVehicle_licensePlate'),// '?????????',
                'usedVehicle.registrationNumber' => trans('invoice.usedVehicle_registrationNumber'),// '????????????',
                'usedVehicle.auctionTaxCode' => trans('invoice.usedVehicle_auctionTaxCode'),// '???????????????????????????????????????',
                'usedVehicle.auctionCompany' => trans('invoice.usedVehicle_auctionCompany'),// '???????????????????????????',
                'usedVehicle.auctionTelephone' => trans('invoice.usedVehicle_auctionTelephone'),// '???????????????????????????',
                'usedVehicle.auctionAddress' => trans('invoice.usedVehicle_auctionAddress'),// '???????????????????????????',
                'usedVehicle.auctionBankAccount' => trans('invoice.usedVehicle_auctionBankAccount'),// '???????????????????????????????????????',
                'usedVehicle.marketTaxCode' => trans('invoice.usedVehicle_marketTaxCode'),// '????????????????????????',
                'usedVehicle.marketCompany' => trans('invoice.usedVehicle_marketCompany'),// '?????????????????????',
                'usedVehicle.marketAddress' => trans('invoice.usedVehicle_marketAddress'),// '?????????????????????',
                'usedVehicle.marketTelephone' => trans('invoice.usedVehicle_marketTelephone'),// '?????????????????????',
                'usedVehicle.marketBankAccount' => trans('invoice.usedVehicle_marketBankAccount'),// '?????????????????????????????????',
                'usedVehicle.transferVehicleManagement' => trans('invoice.usedVehicle_transferVehicleManagement'),// '??????????????????????????????',
                'stax' => trans('invoice.stax'),// '?????????',
            ],
            // ????????????
            '8' => [
                'city' => trans('invoice.city'),// '???',
                'province' => trans('invoice.province'),// '???',
                'companySeal' => trans('invoice.companySeal'),// '?????????????????????',
                'content' => trans('invoice.content'),// '?????????????????????',
            ],
            // 	???????????????
            '9' => [
                'city' => trans('invoice.city'),// '???',
                'province' => trans('invoice.province'),// '???',
                'time_geton' => trans('invoice.time_geton'),// '????????????',
                'time_getoff' => trans('invoice.time_getoff'),// '????????????',
                'mileage' => trans('invoice.mileage'),// '??????',
                'place' => trans('invoice.place'),// '???????????????',
                'license_plate' => trans('invoice.license_plate'),// '?????????',
                'fare' => trans('invoice.fare'),// '?????????',
                'content' => trans('invoice.content'),// '?????????????????????',
                'surcharge' => trans('invoice.surcharge'),// '?????????'
            ],
            // ????????????
            '10' => [
                'city' => trans('invoice.city'),// '???',
                'province' => trans('invoice.province'),// '???',
                'companySeal' => trans('invoice.companySeal'),// '?????????????????????',
                'time' => trans('invoice.time'),// '??????',
                'category' => trans('invoice.category'),// '??????',
                'content' => trans('invoice.content'),// '?????????????????????',
            ],
            // ???????????????
            '11' => [
            ],
            // ?????????
            '12' => [
                'time' => trans('invoice.time'),// '??????',
                'name' => trans('invoice.name'),// '???????????????',
                'station_geton' => trans('invoice.station_geton'),// '????????????',
                'station_getoff' => trans('invoice.station_getoff'),// '????????????',
                'train_number' => trans('invoice.train_number'),// '??????',
                'seat' => trans('invoice.seat'),// '????????????',
                'serial_number' => trans('invoice.serial_number'),// '?????????',
                'user_id' => trans('invoice.user_id'),// '????????????',
                'content' => trans('invoice.content'),// '?????????????????????',
                'ticket_gate' => trans('invoice.ticket_gate'),// '?????????',
            ],
            // ???????????????
            '13' => [
                'time' => trans('invoice.time'),// '??????',
                'entrance' => trans('invoice.entrance'),// '??????',
                'exit' => trans('invoice.exit'),// '??????',
                'highway_flag' => trans('invoice.highway_flag'),// '????????????',
                'content' => trans('invoice.content'),// '?????????????????????',
            ],
            // ??????
            '14' => [
                'time' => trans('invoice.time'),// '??????',
                'name' => trans('invoice.name'),// '???????????????',
                'station_geton' => trans('invoice.station_geton'),// '????????????',
                'station_getoff' => trans('invoice.station_getoff'),// '????????????',
                'city' => trans('invoice.city'),// '???',
                'province' => trans('invoice.province'),// '???',
                'content' => trans('invoice.content'),// '?????????????????????',
            ],
            // ????????????
            '15' => [
                'time' => trans('invoice.time'),// '??????',
                'name' => trans('invoice.name'),// '???????????????',
                'station_geton' => trans('invoice.station_geton'),// '????????????',
                'station_getoff' => trans('invoice.station_getoff'),// '????????????',
                'user_id' => trans('invoice.user_id'),// '????????????',
                'train_number' => trans('invoice.train_number'),// '??????',
                'seat' => trans('invoice.seat'),// '????????????',
                'serial_number' => trans('invoice.serial_number'),// '?????????',
                'content' => trans('invoice.content'),// '?????????????????????',
            ],
            // ?????????????????????????????????
            '16' => [
                'user_name' => trans('invoice.user_name'),// '???????????????',
                'user_id' => trans('invoice.user_id'),// '????????????',
                'agentcode' => trans('invoice.agentcode'),// '??????????????????',
                'issue_by' => trans('invoice.issue_by'),// '????????????',
                'fare' => trans('invoice.fare1'),// '??????',
                'ttax' => trans('invoice.ttax'),// '??????',
                'fuel_surcharge' => trans('invoice.fuel_surcharge'),// '???????????????',
                'caac_development_fund' => trans('invoice.caac_development_fund'),// '??????????????????',
                'insurance' => trans('invoice.insurance'),// '?????????',
                'international_flag' => trans('invoice.international_flag'),// '??????????????????',
                'print_number' => trans('invoice.print_number'),// '????????????',
                'content' => trans('invoice.content'),// '?????????????????????'
            ],
            // ????????????
            '17' => [
                'store_name' => trans('invoice.store_name'),// '??????',
                'time' => trans('invoice.time'),// '??????',
                'tax' => trans('invoice.tax'),// '??????',
                'discount' => trans('invoice.discount'),// '??????',
                'tips' => trans('invoice.tips'),// '??????',
                'content' => trans('invoice.content'),// '?????????????????????'
            ],
            // ???????????????
            '18' => [
                'date_start' => trans('invoice.date_start'),// '??????????????????',
                'date_end' => trans('invoice.date_end'),// '??????????????????',
                'phone' => trans('invoice.phone'),// '??????????????????',
                'content' => trans('invoice.content'),// '?????????????????????'
            ],
            // ????????????
            '19' => [
                'content' => trans('invoice.content'),// '?????????????????????'
            ],
            // ????????????
            '20' => [

            ],
            // ?????????
            '21' => [

            ],
            // ?????????
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
        // ??????access_token
        $accessToken = $this->getWeixinAccessToken($user);
        $token = $accessToken['data']['token'] ?? '';
        if (!$token || (isset($accessToken['error_code']) && $accessToken['error_code'] > 0)) {
            return $accessToken;
        }
        // ??????api_ticket
        $apiTicket = $this->getTicket($token, 'wx');
        if (!$apiTicket || isset($apiTicket['code'])) {
            return $apiTicket;
        }
        // cardSign ????????????
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
        // ??????
        $param = [];
        if ($data['error_code'] == 0) {
            $infos = $data['data'] && $data['data']['infos'] ? $data['data']['infos'] : [];
            $param['on_out'] = $data['data'] && $data['data']['on_out'] ? $data['data']['on_out'] : 0;
            $keys = [
                '0' => 'valid_total',   // ????????????
//                '1' => 'corp_taxnos',   // ????????????????????????
                '2' => 'corp_taxnos',
                '3' => 'valid_on_off',  // ??????????????????
                '4' => 'recognize_valid_on_off',    // ??????????????????
                '5' => 'valid_user',    // ????????????????????????
                '8' => 'reimbursable_month',    // ????????????????????????
                '12' => 'reim',         // ??????????????????????????????
                '13' => 'corp_taxno'    // 	????????????????????????????????????
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
            return '???????????????????????????';
        }
        return $cname;
    }
}