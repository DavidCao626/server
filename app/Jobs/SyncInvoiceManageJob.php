<?php
namespace App\Jobs;

use App\EofficeApp\Invoice\Services\InvoiceService;

class SyncInvoiceManageJob extends Job
{

    public $params;
    // 超时时间
    public $timeout = 120;
    // 重试时间 - 任务执行超过这个时间再执行一次
    public $delay = 120;

    /**
     * CAS同步
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $params = $this->params;
        $type = $params['type'] ?? '';
        $user = $params['userInfo'] ?? [];
        /**
         * @var InvoiceService $service
         */
        $service = app('App\EofficeApp\Invoice\Services\InvoiceService');
        switch ($type) {
            case 'flow':
                $service->flowOutsendInvoice($params);
            break;
            case 'flow-back':
                $service->backInvoice($params['param'], $user);
                break;
            case 'user':
                $service->batchSyncUser($params['param'] ?? [], $user);
                break;
            case 'recognize':
                $service->recognizeInvoice($params);
                break;
            default:
                break;
        }
        return true;
    }
}
