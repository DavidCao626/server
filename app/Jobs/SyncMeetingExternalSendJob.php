<?php
namespace App\Jobs;

class SyncMeetingExternalSendJob extends Job {

    public $param;

    /**
     * 会议异步发送短信邮件通知外部人员
     *
     * @return void
     */
    public function __construct($param) {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $param = $this->param;

        $result = app('App\EofficeApp\Meeting\Services\MeetingService')->parseMeetingExternalSend($param);

        return $result;
    }
}
