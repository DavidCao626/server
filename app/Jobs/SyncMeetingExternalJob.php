<?php
namespace App\Jobs;

class SyncMeetingExternalJob extends Job {

    public $param;

    /**
     *外部人员异步插入
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

        $result = app('App\EofficeApp\Meeting\Services\MeetingService')->parseMeetingExternalJoin($param);

        return $result;
    }
}
