<?php


namespace App\EofficeApp\Invoice\Services;

use Cache;
use Log;

/**
 * Class InvoiceCloudService eteams发票云服务类
 * @package App\EofficeApp\Invoice\Services
 */
class InvoiceCloudService extends InvocieBaseService
{
    // 发票抬头参数枚举累加值
    CONST INVOICE_TITLE_MASK = 3071;
    // 发票更新字段参数枚举累加值
    CONST INVOICE_UPDATE_MASK = 671436592;//268485485;//738456672;
    // 同步微信发票的申请的公共appid
    CONST PUBLIC_WX_APPID = 'wxf6bff4f8f28fff32';
    // 通过发票云申请微信access_token的接口所需加密key
    CONST TOKEN_AES_SECRET_EC = "1234567890weaver";
    public $url;
    private $urls = [
        'application_detail' => 'application/get', //获取应用信息
        'token' => 'token/buildToken',              //获取token
        'create_corp' => 'corp/create',             //创建团队
        'update_corp' => 'corp/update',             //修改团队信息
        'query_by_cid' => 'corp/queryByCid',          //根据团队id查询
        'query_by_account' => 'corp/queryByAccount',  //根据团队账号查询
        'query_all' => 'corp/queryAllCorp',            // 获取所有团队
        'query_corp_user' => 'corp/queryCorpUser',    //获取团队成员
        'query_user_by_account' => 'user/queryByCidAccount', //通过cid,用户帐号查询用户
        'user_create' => 'user/create',
        'batch_user_create' => 'user/batchCreate',        //批量创建人员
        'user_update' => 'user/update',                    //更新人员
        'get_user' => 'user/queryUserAllUser',             //查询用户所有绑定用户
        'corp_user' => 'corp/queryCorpUser',                // 分页查询团队人员
        'invoice_list_cid' => 'invoice/sync_scid_rt',                 //同步指定团队发票列表
        'invoice_list' => 'invoice/sync_rt',                 //同步指定团队发票列表
        'invoice_list_by_type' => 'invoice/sync_reim_rt',                 //同步团队发票列表 -- 带参数发票类型
        'invoice_detail' => 'invoice/get',                     //发票详情
        'invoice_detail_batch' => 'invoice/bat_get',                     //发票详情
        'invoice_title_add' => 'invoice/tcadd',                //创建发票抬头
        'invoice_title_delete' => 'invoice/tcdel',             //删除发票抬头
        'invoice_title_update' => 'invoice/tcupdate',          //更新发票抬头
        'invoice_title_list' => 'invoice/tcsync_rt',              //企业发票抬头列表
        'invoice_title_list_personal' => 'invoice/tssync_rt',              //企业发票抬头列表
        'invoice_title_detail' => 'invoice/tcget',             //发票抬头详情
        'invoice_add' => 'invoice/add',                        //创建发票
        'invoice_update' => 'invoice/update',                  //更新发票
        'invoice_delete' => 'invoice/del',                     //删除发票
        'invoice_recover' => 'invoice/recover',                //恢复发票invoiceFileUpload
        'invoice_ocr' => 'invoice/ocr',                        //发票识别
        'invoice_file_upload' => 'file/upload',         //发票上传识别
        'invoice_corp_local_valid' => 'invoice/corp_local_valid',  // 企业-发票验真
        'invoice_corp_local_ocr' => 'invoice/corp_input',          // 企业票夹中手动录入发票验真
        'invoice_personal_local_valid' => 'invoice/person_local_valid', //个人-发票验真
        'invoice_personal_input_valid' => 'invoice/valid_input', //个人-发票四要素验真
        'invoice_valid_input' => 'invoice/valid_input',       //发票手动录入验真
        'invoice_reimburse' => 'invoice/reimburse',            //报销发票 -》锁定
        'invoice_reimbursed' => 'invoice/reimbursed',            //报销发票完成 -》核销
        'invoice_cancel' => 'invoice/cancelReimburse',            //取消报销发票 -》取消锁定
        'invoice_reimbursements_list' => 'invoice/reimbursements',//已报销发票列表
        'invoice_unreimbursements_list' => 'invoice/unreimbursements', //未报销发票列表
        'invoice_reimbursing_list' => 'invoice/reimbursing',           //报销中发票列表
        'invoice_statistics' => 'invoice/statistics',                  //发票统计
        'valid_corp_open' => 'invoice/valid_corp_open',                //企业设置是否开启验真
        'valid_auto_corp' => 'invoice/valid_auto_corp',                //企业设置是是否自动验真
        'total_corp' => 'invoice/total_corp',                          //企业设置验真金额,
        'sync_config' => 'invoice/sync_config',               //同步支持的发票列表和发票消费类型
        'sync_corp' => 'invoice/sync_corp',                 // 属性设置--同步设置列表 -- 验真金额 验真用户
        'corp_valid_users' => 'invoice/corp_valid_users',   //属性设置--验真用户
        'corp_reim_vtm' => 'invoice/corp_reim_vtm',         // 企业设置发票报销有效时间
        'corp_taxnos' => 'invoice/corp_titles',             // 企业设置允许报销那些税号列表
        'corp_taxno' => 'invoice/corp_taxno',               // 企业设置是否指定部分抬头报销
        'reim' => 'invoice/corp_reim',                      // 企业设置那些查验结果可以报销
        'file_upload' => 'file/uploadFile',                    // 上传发票图片
        // 获取微信相关
        'wx_access_token' => 'wx/token', // 同步微信token
        'wx_add_invoice' => 'third/wx_add', // 同步微信token
        'third_set_appkey' => 'third/set_appkey', // 第三方id设置
        // 企业票夹
        'receipts' => 'invoice/receipts',          // 同步进项发票
        'sales' => 'invoice/sales',                // 同步销项发票
        'corp' => 'invoice/sync_corp_invoice',     // 同步企业发票
         // 应用
        'app_create' => 'app/create',       // 创建应用
        'app_update' => 'app/update',       // 编辑应用
        'app_query' => 'app/query',       // 查询应用
        'corp_invoice_detail' => 'invoice/corp_get',        // 企业票夹获取发票详情

        // 接口配置
        'add_interface' => 'invoice/set_third_party',   // 新增第三方识别、查验接口设置
        'interface_list' => 'invoice/sync_third_party', // 同步第三方识别、查验接口设置
        'get_interface' => 'invoice/get_third_party',   // 获取查验识别接口详情
        'default_interface' => 'invoice/third_party_default', // 新增默认第三方识别查验接口

        // 识别查验情况说明
        'recharge' => 'invoice/get_recharge',   //获取第三方识别、查验接口详情
        'recharge_log' => 'invoice/sync_tp_op', //同步第三方识别、查验记录列表

        // 发票共享
        'share_users' => 'invoice/share_users', // 发票新增删除共享人
        'share_sync' => 'invoice/share_sync', // 同步发票共享人列表

        // 报销前检测
        'check_before' => 'invoice/ec_comm',

        // 一票多报 eo接口
        'reim_one_invoice_more' => 'papi/inc/reim'
    ];
    public $appKey;
    private $appSecret;
    public $cid;
    public $userId;
    public $configId;
    public $localeLang;
//    public $role;
    private $invoiceCloudTeamsYunUserRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceCloudTeamsYunUserRepositories';
    private $invoiceService = 'App\EofficeApp\Invoice\Services\InvoiceService';
    public function __construct($config, $user)
    {
        $this->cid = $config->cid ?? '';
        $this->userId = $this->getInvoiceUserId($user['user_id']);
        $this->configId = $config->config_id ?? '';
        $this->url = trim($config->url ?? '');
        $this->appKey = trim($config->app_key ?? '');
        $this->appSecret = trim($config->app_secret ?? '');
        $this->role = $this->getInvoiceUserId($user['user_id'], 'role') ?? '';
        $this->localeLang = app($this->invoiceService)->getLocaleLang($user);
    }

    public function getInvoiceUserId($userId,$field = 'userId')
    {
        $users = Cache::get('invoice_cloud_teamsyun_users_'.$this->cid, null);
        if (!$users){
            $users = app($this->invoiceCloudTeamsYunUserRepositories)->getAll($this->cid);
            $users = array_column($users, NULL, 'user_id');
            Cache::put('invoice_cloud_teamsyun_users_'.$this->cid, $users);
        }
        return isset($users[$userId]) && $users[$userId] && $users[$userId][$field] ? $users[$userId][$field] : '';
    }

    public function getOaUserId($userId, $field = 'user_id')
    {
        $users = Cache::get('invoice_cloud_teamsyun_users_'.$this->cid, null);
        if (!$users){
            $users = app($this->invoiceCloudTeamsYunUserRepositories)->getAll($this->cid);
            $users = array_column($users, NULL, 'userId');
        }
        return isset($users[$userId]) && $users[$userId] && $users[$userId][$field] ? $users[$userId][$field] : '';
    }

    public function getOaUsers($userIds)
    {
        $users = Cache::get('invoice_cloud_teamsyun_users_'.$this->cid, null);
        if (!$users){
            $users = app($this->invoiceCloudTeamsYunUserRepositories)->getAll($this->cid);
        }
        $users = array_column($users, NULL, 'userId');
        $returnUsers = [];
        foreach ($userIds as $userId) {
            if (isset($users[$userId])) {
                $returnUsers[$userId] = $users[$userId];
            }
        }
        return $returnUsers;
    }

    public function getToken()
    {
        $token = Cache::get('invoice_cloud_token_teamsyun_' . $this->userId);
        if (!$token) {
            if (!$this->userId) {
                throw new \ErrorException(trans('invoice.user_not_sync_invoice_cloud'));
            }
            $tokenRes = $this->buildToken($this->cid, $this->userId);
            if (is_array($tokenRes)) {
                if (isset($tokenRes['error_code']) && $tokenRes['error_code'] == 0) {
                    Cache::put('invoice_cloud_token_teamsyun_' . $this->userId, $tokenRes['data'], 12 * 60 * 60 - 1);
                    $token = $tokenRes['data'];
                } else {
                    throw new \ErrorException($tokenRes['message']);
                }
            } else {
                throw new \ErrorException(trans('invoice.get_token_error'));
            }
        }
        return $token;
    }

    public function buildToken($cid, $userId)
    {
        $param = ['cid' => $cid, 'userId' => $userId, 'role' => $this->role]; //, 'role' => (string)$role
        // $this->logSendBackResult('buildToken:' . json_encode($param));
        return $this->sendRequest($this->urls['token'], $param);
    }

    public function getHeaders()
    {
        return ['appkey' => $this->appKey, 'content-type' => 'application/json', 'charset' => 'UTF-8', 'i18n' => $this->localeLang];
    }

    public function paramEncode($param)
    {
        $time = $this->msec_time();
        if (is_array($param)) {
            $param = json_encode($param);
        }
        // $this->logSendBackResult('secret:' . $this->appSecret);
        $md5 = md5($param . '&time=' . $time . '&secret='.$this->appSecret);
        return $param . '&time=' . $time . '&md5=' . $md5;
    }

    public function sendRequest($url, $param, $needToken = false, $heads = [], $multipart = [])
    {
        try{
            if ($url == 'app/create'){
                $param = json_encode($param);
            } else {
                if (!$this->url || !$this->appKey || !$this->appSecret) {
                    throw new \ErrorException(trans('invoice.config_param_error'));
                }
                if ($needToken) {
                    $token = $this->getToken($param);
                    if (in_array($url, ['invoice/receipts', 'invoice/sales', 'file/uploadFile'])) {
                        $url .= '?token=' . $token .'&appkey=' .$this->appKey;
                    } else {
//                        $url .= '?appkey=' .$this->appKey;
                        $heads['token'] = $token;
                    }
                    $param = json_encode($param);
                } else {
                    if (!in_array($url, ['invoice/receipts', 'invoice/sales'])) {
                        $url .= '?appkey=' .$this->appKey;
                    }
                    $param = $this->paramEncode($param);
                    $param = trim($param);
                }
            }
        } catch (\ErrorException $e) {
            return $this->handleResult(['code' => ['', $e->getMessage()]]);
        }

        return $this->service($url, $param, $heads, $multipart);
    }

    public function service($serviceUrl, $param = [], $heads = [], $multipart = [])
    {
        $flag = 1;
        while ($flag <= 3) {
            $url = $this->url . $serviceUrl;
            if(filter_var($url, FILTER_VALIDATE_URL) === false){
                return $this->handleResult(['code' => ['', trans('invoice.invalid_url')]]);
            }
            $headers = $heads ? array_merge($this->getHeaders(), $heads) : $this->getHeaders();
            try {
                // $this->logSendBackResult('$url:'. $url);
                $this->logSendBackResult('$param:'. $param);
                // $this->logSendBackResult('$headers:'. json_encode($headers));
                $this->logSendBackResult('request:'. json_encode(['url' => $url, 'headers' => $headers]));
                $result = $this->request('post', $url, $param, $headers, $multipart);
            } catch (\ErrorException $e) {
                $message = $e->getMessage();
                $this->logSendBackResult('$result:' . $e->getMessage());
                $message = strtolower($message);
                if (strpos($message, 'curl error') !== false || strpos($message, 'client error') !== false) {
                    $message = trans('invoice.request_error');
                } else if (strpos($message, 'server error') !== false) {
                    $message = trans('invoice.request_error');
                }
                return $this->handleResult(['code' => ['', $message]]);
            }
            if ($result) {
                break;
            }
            $flag++;
        }
        $this->logSendBackResult('$result:' . $result);
        $batch = 0;
//        if (in_array($serviceUrl, ['invoice/reimburse', 'invoice/reimbursed', 'invoice/cancelReimburse', 'invoice/add'])) {
//            $batch = 1;
//        }
//        \Log::info($serviceUrl);
        return $this->handleResult($result, $batch);
    }

    public function getInvoiceList($param)
    {
        $param = $this->parseInvoiceParams($param);
        if (isset($param['sreim'])) {
            return $this->sendRequest($this->urls['invoice_list_by_type'], $param, true);
        } else {
            return $this->sendRequest($this->urls['invoice_list'], $param, true);
        }
    }

    private function parseInvoiceParams($params)
    {
        $newParams = [];
        $newParams['page_size'] = $params['limit'] ?? 10;
        if (isset($params['page'])) {
            // 开始位置
            $newParams['start_pos'] = $params['page'] == 0 ? 0 : (($params['page'] - 1) * $newParams['page_size']);
        } else {
            $newParams['start_pos'] = 0;
        }
        if (isset($params['search'])) {
            $search = $params['search'];
            // 开票时间间隔
            if (isset($search['invoiceDate']) && $invoiceDate = $search['invoiceDate']) {
                // 开始
                if ($invoiceDate && isset($invoiceDate['startDate']) && $invoiceDate['startDate']) {
                    $newParams['date_begin'] = strtotime($invoiceDate['startDate']);
                }
                // 结束
                if (isset($invoiceDate['endDate']) && $invoiceDate['endDate']) {
                    $newParams['date_end'] = strtotime($invoiceDate['endDate']) + 86399;
                } else {
                    $newParams['date_end'] = time();
                }
            }
            // 开票时间间隔
            if (isset($search['addDate']) && $addDate = $search['addDate']) {
                // 开始
                if ($addDate && isset($addDate['startDate']) && $addDate['startDate']) {
                    $newParams['create_tm_begin'] = strtotime($addDate['startDate']);
                }
                // 结束
                if (isset($addDate['endDate']) && $addDate['endDate']) {
                    $newParams['create_tm_end'] = strtotime($addDate['endDate']) + 86399;
                } else {
                    $newParams['create_tm_end'] = time();
                }
            }
            // 发票状态
            if (isset($search['status']) && !empty($search['status'])) {
                $newParams['sreim'] = (int)$search['status'][0];
            }
            if (isset($search['valid']) && !empty($search['valid'])) {
                $newParams['valids'] = array_map(function($val) {
                    return (int)$val;
                }, $search['valid']);
            }
            if (isset($search['created_at_begin']) && $createTime = $search['created_at_begin']) {
                $newParams['create_tm_begin'] = $createTime;
            }
            if (isset($search['created_at_end']) && $createTime = $search['created_at_end']) {
                $newParams['create_tm_end'] = $createTime;
            }
            if (isset($search['types']) && !empty($search['types'])) {
                $newParams['types'] = is_array($search['types']) ? $search['types'] : [$search['types']];
            }
            if (isset($search['content']) && !empty($search['content'])) {
                if (is_array($search['content'])){
                    if (isset($search['content'][1]) && ($search['content'][1] == 'like')) {
                        $newParams['content'] = $search['content'][0];
                    } else {
                        $contentArray = implode($search['content']);
                        if ($contentArray && isset($contentArray[0])){
                            $newParams['content'] = implode($search['content'])[0];
                        }
                    }
                } else {
                    $newParams['content'] = $search['content'];
                }
            }
            if (isset($search['multiSearch']) && $multiSearch = $search['multiSearch']) {
                if($multiSearch['content'] && isset($multiSearch['content'][0])){
                    $newParams['content'] = $multiSearch['content'][0];
                }
            }
            if (isset($search['kind']) && !empty($search['kind'])) {
                $newParams['kind'] = $search['kind'];
            }
            $keys = ['code', 'number', 'payer_company', 'payer_taxno', 'buyer_company', 'buyer_taxno', 'total_begin', 'total_end'];
            foreach ($keys as $key) {
                if (isset($search[$key]) && !empty($search[$key])) {
                    $newParams[$key] = $search[$key];
                }
            }
        }
//        if (isset($params['list_taxnos'])) {
//            $newParams['list_taxnos'] = $params['list_taxnos'];
//        }
        if (isset($params['order_by'])) {
            $keys = array_keys($params['order_by']);
            $field = $keys[0] ?? '';
            $desc = $params['order_by'][$field] ?? '';
            $newParams['sorts'] = [[
                'sort_type' => $field == 'date' ? 0 : 1,
                'sort' => $desc != 'desc' ? 0 : 1
            ]];
        }
        // 选择器的搜索条件

        return $newParams;
    }

    public function getInvoice($param)
    {
        return $this->sendRequest($this->urls['invoice_detail'], $param, true);
    }

    public function getInvoiceCorp($param)
    {
        return $this->sendRequest($this->urls['corp_invoice_detail'], $param, true);
    }

    public function getInvoiceBatch($param)
    {
        return $this->sendRequest($this->urls['invoice_detail_batch'], $param, true);
    }

    public function addInvoice($param)
    {
//        $param['id'] = $param['id'];
        if (isset($param['id']) && !$param['id']) {
            unset($param['id']);
        }
        if (isset($param['third'])) {
            $param['third'] = json_decode($param['third'], 1);
        }
        if (isset($param['comm_info'])) {
            $param['comm_info'] = json_decode($param['comm_info'], 1);
        }
        if (isset($param['modify_info'])) {
            $param['modify_info'] = json_decode($param['modify_info'], 1);
        }
        $params = ['infos' => [$param]];
        return $this->sendRequest($this->urls['invoice_add'], $params, true);
    }

    public function updateInvoice($param)
    {
        if (isset($param['info']['comm_info']) && is_string($param['info']['comm_info'])) {
            $param['info']['comm_info'] = json_decode($param['info']['comm_info'], 1);
        }
        if (isset($param['info']['modify_info']) && is_string($param['info']['modify_info'])) {
            $param['info']['modify_info'] = json_decode($param['info']['modify_info'], 1);
        }
        // 发票云需要计算更新字段标识累计值
//        $param['mask'] = self::INVOICE_UPDATE_MASK;
        $param['mask'] = 0;
        return $this->sendRequest($this->urls['invoice_update'], $param, true);
    }

    public function deleteInvoice($param)
    {
        $ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
//        foreach ($ids as $key => $fid) {
//            $ids[$key] = (double)$fid;
//        }
        return $this->sendRequest($this->urls['invoice_delete'], ['ids' => $ids], true);
    }

    public function getInvoiceTitles($param)
    {
//        $flag = $this->role == 1 ? 2 : 1; // 1 获取企业抬头  2 个人
        $param = [
            'mask' => self::INVOICE_TITLE_MASK,
            'flag' => 1,
            // 发票云暂不支持通过关键字查询 - 方案：查询100条 结果按条件搜索
            'page_size' => 100,
//            'start_pos' => 0,
            'sort' => 1
        ];
        return $this->sendRequest($this->urls['invoice_title_list'], $param, true);
    }

    public function getPersonalInvoiceTitles($param)
    {
        $pageSize = $param['limit'] ?? 10;
        $page = $param['page'] ?? 1;
        $param = [
            'mask' => self::INVOICE_TITLE_MASK,
            'page_size' => $pageSize,
            'start_pos' => ($page - 1) * $pageSize,
            'sort' => 1
        ];
        return $this->sendRequest($this->urls['invoice_title_list_personal'], $param, true);
    }

    public function invoiceTitle($param)
    {
        $param['ftid'] = number_format($param['ftid'], 0 ,'', '');
        return $this->sendRequest($this->urls['invoice_title_detail'], $param, true);
    }

    public function addInvoiceTitle($param)
    {
        if (isset($param['ftid'])) {
            unset($param['ftid']);
        }
//        $flag = $this->role == 1 ? 2 : 1; // 1 获取企业抬头  2 个人
        $param['type'] = (int) $param['type'];
        $params = ['infos' => [$param], 'flag' => 1];
        return $this->sendRequest($this->urls['invoice_title_add'], $params, true);
    }
    public function updateInvoiceTitle($info)
    {
        $mask = self::INVOICE_TITLE_MASK;
        if (isset($info['ftid'])) {
            $info['ftid'] = number_format($info['ftid'], 0 ,'', '');
        }
        unset($info['user']);
        unset($info['oa_id']);
        unset($info['uid']);
//        $flag = $this->role == 1 ? 2 : 1; // 1 获取企业抬头  2 个人
        $flag = 1;
        $info['type'] = (int) $info['type'];
        $info['default'] = (int) $info['default'];
        return $this->sendRequest($this->urls['invoice_title_update'], compact('mask', 'info', 'flag'), true);
    }

    public function deleteInvoiceTitle($params)
    {
        $ftids = [];
        foreach ($params as $id) {
            $ftids[] = number_format($id, 0 ,'', '');
        }
//        $flag = $this->role == 1 ? 2 : 1; // 1 获取企业抬头  2 个人
        $flag = 1;
        return $this->sendRequest($this->urls['invoice_title_delete'], compact('ftids', 'flag'), true);
    }

    public function createCorp($param)
    {
        return $this->sendRequest($this->urls['create_corp'], $param);
    }

    public function updateCorp($param)
    {
        $param['cid'] = $this->cid;
        return $this->sendRequest($this->urls['update_corp'], $param);
    }

    public function QueryCorpByCid($param)
    {
        return $this->sendRequest($this->urls['query_by_cid'], $param);
    }

    public function queryByAccount($param)
    {
        return $this->sendRequest($this->urls['query_by_account'], $param);
    }

    public function queryAll($param = [])
    {
        $param = [
            'pageSize' => 50
        ];
        return $this->sendRequest($this->urls['query_all'], $param);
    }

    public function checkAppInfo()
    {
        $param = [ 'appId' => $this->appKey ];
        return $this->sendRequest($this->urls['application_detail'], $param, true);
    }

    public function syncUser($param)
    {
        $param['cid'] = $this->cid;
        return $this->sendRequest($this->urls['user_create'], $param);
    }

    public function batchSyncUser($param)
    {
        // 把cid拼到参数
        foreach ($param as $key => $value){
            $param[$key]['cid'] = $this->cid;
        }
        return $this->sendRequest($this->urls['batch_user_create'], $param);
    }

    public function updateUser($param)
    {
        $params = [
            'cid' => $this->cid,
            'userId' => $param['userId'],
//            'name' => $param['user_name'],
        ];
        if (isset($param['user_name'])) {
            $params['name'] = $param['user_name'];
        }
        if (isset($param['role'])) {
            $params['role'] = $param['role'];
        }
        return $this->sendRequest($this->urls['user_update'], $params);
    }

    public function openValid($param)
    {
        return $this->sendRequest($this->urls['valid_corp_open'], $param, true);
    }

    public function openAutoValid($param)
    {
        return $this->sendRequest($this->urls['valid_auto_corp'], $param, true);
    }

    public function getTypes($param)
    {
        $requestParam = [];
        if (!isset($param['type']) || !$param['type']) {
            $requestParam['mask'] = 2;
        } else {
            $requestParam['mask'] = $param['type'];
        }
        $data = $this->sendRequest($this->urls['sync_config'], $requestParam, true);
        if ($data['error_code'] == 0){
            $data['data'] = $data['data']['infos'][0]['infos'] ?? [];
        }
        return $data;
    }

    function logSendBackResult($content) {
        $dateTime = date("Y-m-d H:i:s");
        $logDate = date("Y_m_d");
        if (!is_dir(base_path('storage/logs/invoice'))) {
            mkdir(base_path('storage/logs/invoice'));
        }
        file_put_contents(base_path('storage/logs/invoice/invoice_log_'.$logDate.'.txt'), $dateTime."\r\n".$content."\r\n\r\n",FILE_APPEND);
    }

    private function handleResult($result, $batch = 0)
    {
        if (!is_array($result)) {
            $result = json_decode($result, 1);
        }
        $actionMsg = $result['actionMsg'] ?? [];
        $data = $result['data'] ?? [];
        if ($actionMsg) {
            $return['error_code'] = $actionMsg['code'];
            if ($actionMsg['code'] == 0) {
                // 批量操作的需要额外处理
                // 发票批量处理
                if ($batch == 1) {
                    foreach ($data['infos'] as $key => $value) {
                        if (isset($value['ret']) && $value['ret'] == 0) {
                            $data[$key]['message'] = '';
                        } else {
//                            $data[$key]['message'] = $this->errors[$value['ret']] ?? $value['ret'];
                            $message = trans('invoice.invoice_cloud.' . $value['ret']);
                            $data[$key]['message'] = $message == 'invoice.invoice_cloud.' . $value['ret'] ? trans('invoice.invoice_cloud.other').$value['ret'] : $message;
                        }
                    }
                } else if ($batch == 2) {
                    // 用户批量处理
                }
                $return['data'] = $data ?? true;
            } else {
                // 2009 2013 含有变量参数
                $message = trans('invoice.invoice_cloud.' . $actionMsg['code']);
//                $return['message'] = $actionMsg['message'] ?? (trans('invoice.invoice_cloud.' . $actionMsg['code']) ?? $actionMsg['code']);
//                $return['message'] = trans('invoice.invoice_cloud.' . $actionMsg['code']) ?? $actionMsg['code'];
                $return['message'] =  $message == 'invoice.invoice_cloud.' . $actionMsg['code'] ? ($actionMsg['message'] ?? trans('invoice.invoice_cloud.other').$actionMsg['code']) : $message;
            }
        } else {
            if (isset($result['code'])){
                $return = [
                    'error_code' => 99999,
                    'message' => $result['code'][1] ?? trans('invoice.return_msg_error')
                ];
            } else {
                $return = [
                    'error_code' => 99999,
                    'message' => trans('invoice.return_msg_error')
                ];
            }
        }
        return $return;
    }

    public function lockInvoice($param)
    {
        return $this->sendRequest($this->urls['invoice_reimburse'], $param, true);
    }

    public function reimbursedInvoice($param)
    {
        return $this->sendRequest($this->urls['invoice_reimbursed'], $param, true);
    }

    public function cancelInvoice($param)
    {
        return $this->sendRequest($this->urls['invoice_cancel'], $param, true);
    }

    public function invoiceFileUpload($param)
    {
        $heads = ['content-type' => 'multipart/form-data'];
        $multipart = [
            'name' => 'file',
            'contents' => $param['file']
        ];
        return $this->sendRequest($this->urls['invoice_file_upload'], $param, true, $heads, [$multipart]);
    }

    public function fileUpload($param)
    {
        $heads = ['content-type' => 'multipart/form-data'];
        $multipart = [
            'name' => 'file',
            'contents' => $param['file']
        ];
        return $this->sendRequest($this->urls['file_upload'], $param, true, $heads, [$multipart]);
    }

    /** 个人发票验真
     * @param $param
     * @return array
     */
    public function validInvoice($param)
    {
        $id = $param['id'];
        return $this->sendRequest($this->urls['invoice_personal_local_valid'], ['id' => $id], true);
    }

    /** 企业发票验真
     * @param $param
     * @return array
     */
    public function corpValidInvoice($param)
    {
        $id = $param['id'];
        return $this->sendRequest($this->urls['invoice_corp_local_valid'], ['fid' => $id], true);
    }

    /** 个人发票手动录入验真
     * @param $param
     * @return array
     */
    public function validInputInvoice($param)
    {
        $param['date'] = strtotime($param['date']);
        $param['type'] = intval($param['type']);
        return $this->sendRequest($this->urls['invoice_valid_input'], $param, true);
    }

    /** 获取发票云用户信息
     * @param $param
     * @return array
     */
    public function getInvoiceUser($param)
    {
        $param['cid'] = $this->cid;
        return $this->sendRequest($this->urls['query_user_by_account'], $param);
    }

    /** 发票识别
     * @param $param
     * @return array
     */
    public function recognizeInvoice($param)
    {
        // invoice_ocr
        $param['is_sync'] = isset($param['sync']) ? $param['sync'] : 0;
        unset($param['sync']);
        return $this->sendRequest($this->urls['invoice_ocr'], $param, true);
    }

    public function getWxToken()
    {
        return $this->sendRequest($this->urls['wx_access_token'], [], true);
    }

    /** 同步微信电子发票
     * @param $param
     * @return array
     */
    public function wxAddInvoice($param)
    {
        return $this->sendRequest($this->urls['wx_add_invoice'], $param, true);
    }

    /** 同步企业配置
     * @return array
     */
    public function syncCorpConfig($param = [])
    {
        return $this->sendRequest($this->urls['sync_corp'], $param ?? [], true);
    }

    /** 设置发票验真金额
     * @param $param
     * @return array
     */
    public function totalCorp($param)
    {
        return $this->sendRequest($this->urls['total_corp'], $param, true);
    }

    /** 设置验真用户
     * @param $param
     * @return array
     */
    public function corpValidUsers($param)
    {
        return $this->sendRequest($this->urls['corp_valid_users'], $param, true);
    }

    /** 设置报销时间
     * @param $param
     * @return array
     */
    public function corpReimVtm($param)
    {
        return $this->sendRequest($this->urls['corp_reim_vtm'], $param, true);
    }

    /** 销项
     * @param $param
     * @return array
     */
    public function receiptsInvoices($param)
    {
        return $this->sendRequest($this->urls['receipts'], $param, true);
    }

    /** 进项
     * @param $param
     * @return array
     */
    public function salesInvoices($param)
    {
        return $this->sendRequest($this->urls['sales'], $param, true);
    }

    /** 企业票夹
     * @param $param
     * @return array
     */
    public function corpInvoices($param)
    {
        $param = $this->parseInvoiceParams($param);
        return $this->sendRequest($this->urls['corp'], $param, true);
    }

    /** 发票统计
     * @param $param
     * @return array
     */
    public function invoiceStatistics($param)
    {
        return $this->sendRequest($this->urls['invoice_statistics'], $param, true);
    }

    /** 设置三方appkey【废弃】
     * @param $param
     * @return array
     */
    public function thirdSetAppkey($param)
    {
        return $this->sendRequest($this->urls['third_set_appkey'], $param, true);
    }

    /** 设置是否开启报销税号
     * @param $param
     * @return array
     */
    public function corpTaxno($param)
    {
        return $this->sendRequest($this->urls['corp_taxno'], $param, true);
    }

    /** 设置可报销的税号
     * @param $param
     * @return array
     */
    public function corpTaxnos($param)
    {
        $param['product'] = 2;
        return $this->sendRequest($this->urls['corp_taxnos'], $param, true);
    }

    /** 设置是否开启查验报销
     * @param $param
     * @return array
     */
    public function reim($param)
    {
        return $this->sendRequest($this->urls['reim'], $param, true);
    }

    /** 创建应用
     * @param $param
     * @return array
     */
    public function createApp($param)
    {
        return $this->sendRequest($this->urls['app_create'], $param);
    }

    /** 编辑应用
     * @param $param
     * @return array
     */
    public function updateApp($param)
    {
        return $this->sendRequest($this->urls['app_update'], $param);
    }

    /** 查询应用
     * @param $param
     * @return array
     */
    public function queryApp($param)
    {
        $app = $this->sendRequest($this->urls['app_query'], $param);
        if ($app['error_code'] == 0)
            Cache::set('invoice_app_'. $this->appKey, json_encode($app));
            return $app;
        return [];
    }

    public function corpUser($param)
    {
        $param = [
            'cid' => $this->cid,
//            'mask' =>
        ];
        return $this->sendRequest($this->urls['corp_user'], $param);
    }

    public function addInterface($param)
    {
        return $this->sendRequest($this->urls['add_interface'], $param);
    }

    public function addDefaultInterface($param)
    {
        return $this->sendRequest($this->urls['default_interface'], $param);
    }

    public function recharge()
    {
        return $this->sendRequest($this->urls['recharge'], [], true);
    }

    public function rechargeLog($param)
    {
        $param = $this->parseChargeParams($param);
        return $this->sendRequest($this->urls['recharge_log'], $param, true);
    }

    private function parseChargeParams($params)
    {
        $newParams = [];
        $newParams['page_size'] = $params['limit'] ?? 10;
        if (isset($params['page'])) {
            // 开始位置
            $newParams['start_pos'] = $params['page'] == 0 ? 0 : (($params['page'] - 1) * $newParams['page_size']);
        } else {
            $newParams['start_pos'] = 0;
        }
        if (isset($params['search'])) {
            $search = json_decode($params['search'], 1);
            $newParams['flag'] = isset($search['flag']) ? $search['flag'] : 2;
            $newParams['sync_type'] = $search['sync_type'] ?? 0;
        }
            
        if (isset($params['order_by'])) {
            $keys = array_keys($params['order_by']);
            $field = $keys[0] ?? '';
            $desc = $params['order_by'][$field] ?? '';
            $newParams['sorts'] = [[
                'sort_type' => $field == 'date' ? 0 : 1,
                'sort' => $desc != 'desc' ? 0 : 1
            ]];
        }

        return $newParams;
    }

    public function shareUser($param)
    {
        return $this->sendRequest($this->urls['share_users'], $param, true);
    }

    public function shareSync($param)
    {
        return $this->sendRequest($this->urls['share_sync'], $param, true);
    }

    public function checkBefore($param)
    {
        return $this->sendRequest($this->urls['check_before'], $param);
    }

    public function reimInvoice($param)
    {
        return $this->sendRequest($this->urls['reim_one_invoice_more'], $param, true);
    }
    
    public function getWxTokenV2()
    {
        $url = 'https://api.mypiaojia.com/invoiceApi/wx/getWxToken';
        $encodeParam = openssl_encrypt(json_encode(['appKey' => self::PUBLIC_WX_APPID, 'time' => time()]), 'AES-128-ECB', self::TOKEN_AES_SECRET_EC);
        $param = [
            'sign' => $encodeParam
        ];
        return $this->service($url, json_encode($param));
    }
    
}