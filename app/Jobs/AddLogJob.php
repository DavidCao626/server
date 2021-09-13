<?php
namespace App\Jobs;

use App\EofficeApp\LogCenter\Facades\LogCenter;
class AddLogJob extends Job
{
    private $params;
    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        LogCenter::addLogTerminal($this->params['module_key'], $this->params['log_data'], $this->params['history_data'], $this->params['current_data']);
    }
}
