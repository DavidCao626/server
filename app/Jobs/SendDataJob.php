<?php
namespace App\Jobs;

use GuzzleHttp\Client;
use Queue;

class SendDataJob extends Job
{
    public $param;
    public $relationTableInfoAdd;

    /**
     * 流程外发-地址外发
     *
     * @return void
     */
    public function __construct($param, $relationTableInfoAdd)
    {
        $this->param = $param;
        $this->relationTableInfoAdd = $relationTableInfoAdd;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //F:\www\eoffice10\server\vendor\laravel\lumen-framework\config\queue.php:
        //'default' => env('QUEUE_DRIVER', 'sync'),
        //默认同步

        $data = $this->param['form_data'];
        $runName = $this->param['run_name'];
        $processName = $this->param['process_name'];
        $userId = $this->param['user_id'];
        $ip = $this->param['ip'];

        // 新增几个参数
        $outsendId = $data['outsend_id']; // 节点外发ID
        $runId = $data['run_id'];
        $nodeId = $data['node_id'];
        $errorType = 0; // 外发错误类型

        if (!check_white_list($this->param['url'], ['user_id' => $userId, 'ip' => $ip])) {
            return;
        }
        try {
            $guzzleResponse = (new Client())->request('POST', $this->param['url'], ['form_params' => $data]);
            $status = $guzzleResponse->getStatusCode();
        } catch (\Exception $e) {
            $status = $e->getMessage();
        }

        $logContent = trans('flow.0x030030') . ': ' . $runName . ', ' . trans("flow.flow_node_name") . ': ' . $processName . ' , ' . trans('flow.0x030175') . ' ' . trans('flow.0x030174') . ':<br>';
        if ($status == '200') {
            $logContent .= trans('flow.0x030104');
            $isFailed = 0;
        } else {

            //  if($this->count > 1) {
            //      $count = $this->count - 1;
            //      $logContent = $runName . '  节点：  ' . $processName . ' ,  数据外发失败，失败原因： ' . $status.' 稍后将再次尝试发送，剩余尝试次数:'.$this->count;
            //      $this->addSystemLog($userId, $logContent, 'outsend',$ip, 'flow_run,flow_outsend', $runId . ',' . $outsendId, 'run_id,outsend_id', 1);
            //      Queue::later($this->time,new sendDataJob($this->param,$count,$this->time));
            //  }else{
            //20191217,zyx,取消传入的尝试次数，防止队列出现嵌套循环问题
            $logContent .= $status;
            $isFailed = 1;
            //  }
            $errorType = 4; // 外发失败，含原因
        }
        // 记录日志
        app('App\EofficeApp\Flow\Services\FlowLogService')->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $outsendId, 'id', $isFailed, $ip, $this->relationTableInfoAdd ,  $runName);

        // 2019.9.10,zyx,提醒参数数组，增加数据外发提醒
        // 提醒发送规则：仅当数据外发失败时发送，以便管理员及时查看追踪问题
        if ($errorType) {
            $remindsData = [];
            $remindsData['toUser'] = ['admin', $userId]; // 暂定为管理员，后期可以增加流程定义人或指定人
            $remindsData['processName'] = $data['process_name'] ?? ''; // 节点名称
            $remindsData['remindMark'] = 'flow-outsend'; // 提醒类型

            // 调用提醒方法
            app('App\EofficeApp\Flow\Services\FlowOutsendService')->flowOutSendRemind($errorType, $data, $remindsData, $logContent);
        }
    }
}
