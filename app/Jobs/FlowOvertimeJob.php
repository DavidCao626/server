<?php
namespace App\Jobs;

class FlowOvertimeJob extends Job {

    /**
     *
     *
     * @return void
     */
    public function __construct() {
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        app('App\EofficeApp\Flow\Services\FlowService')->urgeRemind();
    }
}
