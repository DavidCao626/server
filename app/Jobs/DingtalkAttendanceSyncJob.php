<?php
namespace App\Jobs;

class DingtalkAttendanceSyncJob extends Job
{

    public $user_id;
    public $data;
    /**
     *
     *
     * @return void
     */
    public function __construct($data,$user_id)
    {
        $this->data   = $data;
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
        $result = app('App\EofficeApp\Dingtalk\Services\DingtalkService')->dingtalkSync($this->data,$this->user_id);
        // var_dump($result);
        return $result;
    }
}
