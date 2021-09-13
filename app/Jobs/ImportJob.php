<?php
namespace App\Jobs;

class ImportJob extends Job
{
    private $params;
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
        return app('App\EofficeApp\ImportExport\Services\ImportService')->handleImport($this->params);
    }

}