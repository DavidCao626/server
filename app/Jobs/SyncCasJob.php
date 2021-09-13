<?php
namespace App\Jobs;

class SyncCasJob extends Job
{

    public $params;

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
        $result = app('App\EofficeApp\System\Cas\Services\CasService')->syncCasOrganizationData($params);
        return $result;
    }
}
