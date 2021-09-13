<?php
namespace App\Jobs;

use App\EofficeApp\OpenApi\Services\OpenApiService;
use Eoffice;
use Illuminate\Support\Facades\Redis;
class OpenApiJob extends Job {

    public $log;

    public function __construct($log) {
        $this->log = $log;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        /** @var OpenApiService $service */
        $service = app("App\EofficeApp\OpenApi\Services\OpenApiService");
        $service->recordLog($this->log);

       // app(' App\EofficeApp\OpenApi\Services\OpenApiService')->recordLog($this->log);
    }
}
