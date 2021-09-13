<?php

namespace App\Jobs;

class FlowToIntegrationCenterWebhookSendJob extends Job
{

    public $params;

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
        $this->todoPush();
    }
    public function todoPush()
    {
        $service = app("App\EofficeApp\IntegrationCenter\Services\TodoPushService");
        $service->pushData($this->params);
    }
}
