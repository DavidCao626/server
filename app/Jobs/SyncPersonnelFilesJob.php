<?php
namespace App\Jobs;

class SyncPersonnelFilesJob extends Job
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
        $result = app('App\EofficeApp\User\Services\UserService')->SyncPersonnelFiles($params);
        return $result;
    }
}
