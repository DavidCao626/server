<?php


namespace App\EofficeApp\Invoice\Services;

use App\EofficeApp\Base\BaseService;

class InvoiceLogsService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->invoiceLogsRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceOperationLogsRepositories';
        $this->invoiceService = 'App\EofficeApp\Invoice\Services\InvoiceService';
        $this->attachmentService   = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->invoiceLogRelatedInvoiceIdService = 'App\EofficeApp\Invoice\Services\InvoiceLogRelatedInvoiceIdService';
    }

    public function getLogs($params, $user)
    {
        $params = $this->parseParams($params);
        $data = $this->response(app($this->invoiceLogsRepositories), 'getCount', 'getList', $this->parseParams($params));
        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];
        $count = 0;
        // 是否需要获取发票详情
        $detail = $params['detail'] ?? false;
        unset($params['detail']);
        if ($response == 'both' || $response == 'count') {
            $count = $data['total'];
        }
        if (($response == 'both' && $count > 0) || $response == 'data') {
            $lists = $data['list'];
            $invoices = [];
            if ($detail) {
                // 获取发票id数组
                $invoice_ids = array_column($lists, 'invoice_id');
                $fids = [];
                foreach ($invoice_ids as $invoice_id) {
                    $fids = array_merge($fids, explode(',', $invoice_id));
                }
                if ($fids) {
                    $fids = array_unique(array_filter($fids));
                    $request = [
                        'ids' => array_values($fids),
                    ];
                    $invoices = [];
                    if ($request) {
                        $invoices = app($this->invoiceService)->getInvoiceBatch($request, $user);
                    }
                    $invoices = $invoices['infos'] ?? [];
                    foreach($invoices as $invoiceKey => $invoice) {
                        if ($invoice['ret'] == 0 && isset($invoice['info'])) {
                            $id = $invoice['info']['id'] ?? '';
                            if ($id) {
                                $invoices[$id] = $invoice['info'];
                            }
                        }
                        unset($invoices[$invoiceKey]);
                    }
                }
            }
//            $attachmentIds = array_column($lists, 'attachment_id');
//            if ($attachmentIds) {
//                $attachments = app($this->attachmentService)->getMoreAttachmentById($attachmentIds);
//                $attachments = array_column($attachments, NULL, 'attachment_id');
////                dd($attachments);
//            }
            foreach ($lists as $k => $new) {
                $invoice_ids = explode(',', $new['invoice_id']);
                $new['invoice'] = [];
                foreach ($invoice_ids as $invoice_id) {
                    if ($invoices && isset($invoices[$invoice_id])) {
                        $new['invoice'][] = $invoices[$invoice_id];
                    }
                }
                if (!isset($new['attachment_id']) || empty($new['attachment_id'])) {
//                    if (!isset($new['attachment_id']) || empty($new['attachment_id']) || !isset($attachments[$new['attachment_id']])) {
                    $new['thumb_attachment_name'] = '';
                } else {
                    $new['thumb_attachment_name'] = app($this->attachmentService)->getThumbAttach($new['attachment_id']);
//                    $new['thumb_attachment_name'] = $attachments[$new['attachment_id']] ?? '';
                }
                $new['request_data'] = json_decode($new['request_data']);
                $list[$k] = $new;
            }
        }
        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }

    public function addLog($params)
    {
        $logs = [];
        if ($params) {
            if (count($params) === count($params, 1)){
                $params = [$params];
            }
            $allInvoiceIds = array_column($params, 'invoice_id');
            $all = [];
            foreach ($allInvoiceIds as $allInvoiceId){
                $invoiceIdArray = explode(',', $allInvoiceId);
                foreach ($invoiceIdArray as $item) {
                    if (!in_array($item, $all)) {
                        $all[] = $item;
                    }
                }
            }
            $update = false;
            foreach ($params as $param) {
                $param = array_intersect_key($param, array_flip(app($this->invoiceLogsRepositories)->getTableColumns()));
                if ($param) {
                    if (isset($param['request_data']) && is_array($param['request_data'])) {
                        $param['request_data'] = json_encode($param['request_data']);
                        $param['request_api_data'] = isset($param['request_api_data']) ? json_encode($param['request_api_data']) : '';
                    }
                    if (isset($param['result_data']) && is_array($param['result_data'])) {
                        $param['result_data'] = json_encode($param['result_data']);
                    }
                    $param['created_at'] = $param['updated_at'] = date('Y-m-d H:i:s');
                    $logs[] = $param;
                    if ($param['type'] == 22) {
                        $update = true;
                    }
                }
            }
            if (isset($logs) && !empty($logs)) {
                foreach ($logs as $key => $log) {
                    $logId = app($this->invoiceLogsRepositories)->insertData($log)->log_id;
                    if ($logId)
                        $logs[$key]['log_id'] = $logId;
                }
                if ($all) {
                    $user = ['user_id' => 'admin'];
                    $params = ['ids' => $all];
                    $invoices = app('App\EofficeApp\Invoice\Services\InvoiceService')->getInvoiceBatch($params, $user, 'array');
                    if (isset($invoices['error_code']) && $invoices['error_code'] == 0) {
                        $infos = $invoices['data']['infos'] ?? [];
                        $infos = array_column($infos, NULL, 'fid');
                    }
                    app($this->invoiceLogRelatedInvoiceIdService)->add($logs, $infos ?? [], $update);
                }
                return true;
            } else {
                return true;
            }
//            return isset($logs) && !empty($logs) ? app($this->invoiceLogsRepositories)->insertMultipleData($logs) : true;
        } else {
            return false;
        }
    }

    public function getLog($logId)
    {
        return app($this->invoiceLogsRepositories)->getLog($logId);
    }

    public function getLogByImageIndex($imageIndex)
    {
        return app($this->invoiceLogsRepositories)->getOneFieldInfo(['image_index' => $imageIndex]);
    }
}