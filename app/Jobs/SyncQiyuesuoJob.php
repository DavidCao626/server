<?php
namespace App\Jobs;

use App\EofficeApp\ElectronicSign\Services\ElectronicSignService;
use App\EofficeApp\ElectronicSign\Services\QiyuesuoService;

class SyncQiyuesuoJob extends Job
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
        $userInfo = $params['userInfo'];
        /** @var QiyuesuoService $service */
        $service = app('App\EofficeApp\ElectronicSign\Services\QiyuesuoService');
        switch ($type)
        {
            case 'seal_apply':
                return $service->syncSeals($userInfo);
            break;
            case 'category':
                return $service->syncCategory($userInfo);
            break;
            case 'template':
                return $service->syncTemplate($userInfo);
            break;
            case 'all':
                $service->syncSeals($userInfo);
                $service->syncCategory($userInfo);
                $service->syncTemplate($userInfo);
                return true;
            break;
            case 'flow':
                /** @var ElectronicSignService $service */
                $service = app('App\EofficeApp\ElectronicSign\Services\ElectronicSignService');
                return $service->flowOutsend($params);
            default:
                return false;
        }
    }
}
