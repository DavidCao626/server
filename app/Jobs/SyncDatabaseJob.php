<?php
namespace App\Jobs;

class SyncDatabaseJob extends Job
{

    public $params;

    /**
     * 数据库修复
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
        $result = app('App\EofficeApp\System\Database\Services\DatabaseService')->fixedTableQueue($params);
        return $result;
    }
}
