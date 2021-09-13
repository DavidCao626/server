<?php
namespace App\Jobs;
use Lang;
class ExportJob extends Job
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
        unset($GLOBALS['langType']);
        $GLOBALS['langType'] = isset($this->params['param']['lang_type']) ? $this->params['param']['lang_type'] : (isset($this->params['param']['param']['lang_type']) ? $this->params['param']['param']['lang_type'] : null);
        Lang::setLocale($GLOBALS['langType']);
        return app('App\EofficeApp\ImportExport\Services\ExportService')->handleExport($this->params);
    }

}