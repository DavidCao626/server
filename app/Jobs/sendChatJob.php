<?php
namespace App\Jobs;

class sendChatJob extends Job {

    public $sendData;

    /**
     * 数据导入导出
     *
     * @return void
     */
    public function __construct($sendData) {
        $this->sendData = $sendData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $message = app('App\EofficeApp\Vendor\Message\AppPush');
        
        $message->sendMessage($this->sendData);
    }
}
