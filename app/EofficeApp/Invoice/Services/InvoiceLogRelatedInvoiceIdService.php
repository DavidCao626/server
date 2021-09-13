<?php


namespace App\EofficeApp\Invoice\Services;

use App\EofficeApp\Base\BaseService;

class InvoiceLogRelatedInvoiceIdService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->invoiceLogRelatedInvoiceIdRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceLogRelatedInvoiceIdRepositories';
    }

    public function add($logs, $invoices, $update = false)
    {
        $data = [];
        foreach ($logs as $log) {
            $invoiceIds = $log['invoice_id'] ?? '';
            $invoiceIdArray = explode(',', $invoiceIds);
            foreach ($invoiceIdArray as $value) {
                $data[] = [
                    'log_id' => $log['log_id'],
                    'invoice_id' => $value,
                    'code' => $invoices[$value]['code'] ?? '',
                    'number' => $invoices[$value]['number'] ?? '',
                ];
                if ($update) {
                    $update = [
                        'code' => $invoices[$value]['code'] ?? '',
                        'number' => $invoices[$value]['number'] ?? '',
                    ];
                    app($this->invoiceLogRelatedInvoiceIdRepositories)->updateData($update, ['invoice_id' => $value]);
                }
            }
        }
        if ($data) {
            foreach(array_chunk($data, 100) as $item) {
                app($this->invoiceLogRelatedInvoiceIdRepositories)->insertMultipleData($item);
            }
        }
        return true;
    }

    public function getInvoiceIds($param)
    {
        return app($this->invoiceLogRelatedInvoiceIdRepositories)->getInvoiceIds($param);
    }
}