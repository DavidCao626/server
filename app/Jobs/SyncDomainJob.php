<?php
namespace App\Jobs;

class SyncDomainJob extends Job {

    public $param;

    /**
     * 域同步
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

        $result = app('App\EofficeApp\System\Domain\Services\DomainService')->sync($param);

        return $result;
    }
}
