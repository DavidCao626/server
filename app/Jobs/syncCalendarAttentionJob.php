<?php
namespace App\Jobs;

class SyncCalendarAttentionJob extends Job {

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

        $result = app('App\EofficeApp\Calendar\Services\CalendarService')->sendMessageToAttention($param);

        return $result;
    }
}
