<?php
namespace App\Jobs;

class sendFlowCopyJob extends Job
{

    public $params;
    public $conditionParam;
    public $flowCopyParam;
    public $type;
    /**
     * 流程抄送
     *
     * @return void
     */
    public function __construct($conditionParam , $flowCopyParam)
    {
        $this->conditionParam = $conditionParam;
        $this->flowCopyParam = $flowCopyParam;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        app('App\EofficeApp\Flow\Services\FlowRunService')->sendFixedFlowCopy($this->conditionParam  ,  $this->flowCopyParam); 
    }
}
