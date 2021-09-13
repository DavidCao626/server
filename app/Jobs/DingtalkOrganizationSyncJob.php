<?php
namespace App\Jobs;

class DingtalkOrganizationSyncJob extends Job
{

    // public $param;
    public $user_id;
    public $timeout = 0;
    /**
     *
     *
     * @return void
     */
    public function __construct($user_id)
    {
        // $this->param   = $param;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        // $userId = own('user_id');// 这里也拿不到用户信息
        $result = app('App\EofficeApp\Dingtalk\Services\DingtalkService')->dingtalkOASync($this->user_id);
        // var_dump($result);
        return $result;
    }
}
