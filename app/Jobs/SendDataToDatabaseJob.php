<?php
namespace App\Jobs;

use GuzzleHttp\Client;
use Queue;

class SendDataToDatabaseJob extends Job
{
    public $sendDataJob;
    public $databaseinfo;
    public $sendDatas;
    public $relationTableInfoAdd;

    /**
     * 流程外发-外部数据库外发
     *
     * @return void
     */
    public function __construct($sendDataJob, $databaseinfo, $sendDatas, $relationTableInfoAdd)
    {
        $this->sendDataJob = $sendDataJob;
        $this->databaseinfo = $databaseinfo;
        $this->sendDatas = $sendDatas;
        $this->relationTableInfoAdd = $relationTableInfoAdd;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $runName = $this->sendDataJob['run_name'];
        $processName = $this->sendDataJob['process_name'];
        $userId = $this->sendDataJob['user_id'];
        $ip = $this->sendDataJob['ip'];

        // 新增几个参数
        $outsendId = $this->sendDataJob['outsend_id']; // 节点外发ID
        $runId = $this->sendDataJob['run_id'];
        $nodeId = $this->sendDataJob['node_id'];
        $errorType = 0; // 外发错误类型

        $insertResult = app("App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService")->sendDataToExternalDatabase($this->databaseinfo, $this->sendDatas);

        $logContent = trans('flow.0x030030') . ': ' . $runName . ', ' . trans("flow.flow_node_name") . ': ' . $processName . ' , ' . trans('flow.0x030175') . ' ' . trans('flow.0x030174') . ':<br>';
        if (isset($insertResult['code'])) {
            //            if($this->count > 1) {
            //                $count = $this->count - 1;
            //                if (is_array($insertResult['code'])) {
            //                    $logContent = $runName . '  节点: ' . $processName . ' , 数据外发失败，失败原因： ' . trans($insertResult['code'][1] . '.' . $insertResult['code'][0]).' 稍后将再次尝试发送，剩余尝试次数:'.$this->count;
            //                } else {
            //                    $logContent = $runName . '  节点: ' . $processName . ' , 数据外发失败，失败原因： ' . $insertResult['code'].' 稍后将再次尝试发送，剩余尝试次数:'.$this->count;
            //                }
            //
            //                $this->addSystemLog($userId, $logContent, 'outsend',$ip, 'flow_run,flow_outsend', $runId . ',' . $outsendId, 'run_id,outsend_id', 1);
            //                Queue::later($this->time,new sendDataToDatabaseJob($this->sendDataJob,$this->databaseinfo,$this->sendDatas,$count,$this->time));
            //            }else{
            //20191217,zyx,取消传入的尝试次数，防止队列出现嵌套循环问题
            if (is_array($insertResult['code'])) {
                $logContent .= trans('flow.0x030103'). ': ' . trans($insertResult['code'][1] . '.' . $insertResult['code'][0]);
            } else {
                $logContent .= trans('flow.0x030103'). ': ' . $insertResult['code'];
            }
            app('App\EofficeApp\Flow\Services\FlowLogService')->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $outsendId, 'id', 1, $ip, $this->relationTableInfoAdd , $runName);
            //            }
            $isFailed = 1;
            $errorType = 4; //外发失败，含原因
        } else {
            $logContent .= trans('flow.data') . "id $insertResult " . trans('flow.launch') . trans('flow.0x030121');
            $isFailed = 0;
        }
        // 记录日志
        app('App\EofficeApp\Flow\Services\FlowLogService')->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $outsendId, 'id', $isFailed, $ip, $this->relationTableInfoAdd , $runName);

        // 2019.9.10,zyx,提醒参数数组，增加数据外发提醒
        // 提醒发送规则：仅当数据外发失败时发送，以便管理员及时查看追踪问题
        if ($errorType) {
            $remindsData = [];
            $remindsData['toUser'] = ['admin', $userId]; // 暂定为管理员，后期可以增加流程定义人或指定人
            $remindsData['processName'] = $this->sendDataJob['process_name'] ?? ''; // 节点名称
            $remindsData['remindMark'] = 'flow-outsend'; // 提醒类型

            // 调用提醒方法
            app('App\EofficeApp\Flow\Services\FlowOutsendService')->flowOutSendRemind($errorType, $this->sendDataJob, $remindsData, $logContent);
        }
    }
}
