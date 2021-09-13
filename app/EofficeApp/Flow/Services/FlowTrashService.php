<?php
namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use DB;
use Illuminate\Support\Facades\Log;
use Cache;
/**
 * 流程垃圾箱service类，用来管理相关流程资源的删除、恢复、清理
 *
 * @since  2018-11-9 创建
 */
class FlowTrashService extends FlowBaseService
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 删除运行中的流程
     *
     * @author lixuanxuan
     *
     * @param  [type]        $data [description]
     *
     * @return [type]              [description]
     */
    function deleteFlowAll($runId, $own, $verify = true)
    {
        $runIdArray = explode(",", trim($runId, ","));
        if(count($runIdArray) > 0){
            foreach ($runIdArray as $key => $value) {
                // 回收子流程时不需要权限
                if ($verify) {
                    // 判断是否有流程的删除权限
                    $verifyDeleteParams = [
                        'run_id'    => $value,
                        'user_info' => $own
                    ];
                    $deletePermission = app($this->flowPermissionService)->verifyRunningFlowDeletePermission($verifyDeleteParams);
                    if (isset($deletePermission['code'])) {
                        return $deletePermission;
                    }
                }

                $runInfo = app($this->flowRunRepository)->getDetail($value);
                if($runInfo) {
                    // 获取当前run_id下的数据
                    $searchWhere = [
                        'run_id' => [$value],
                        'user_last_step_flag' => [1]
                    ];
                    $todoList = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => $searchWhere,'fields' =>['user_id', 'flow_id', 'run_id', 'process_id', 'flow_run_process_id']]);
                    $wheres = ["run_id" => [$value]];
                    app($this->flowRunRepository)->deleteByWhere($wheres);
                    app($this->flowRunStepRepository)->deleteByWhere($wheres);
                    // app($this->flowRunProcessRepository)->deleteByWhere($wheres);
                    app($this->flowRunFeedbackRepository)->deleteByWhere($wheres);
                    app($this->flowCopyRepository)->deleteByWhere($wheres);
                    //删除超时提醒记录
                    app($this->flowRunOverTimeRepository)->deleteByWhere($wheres);
                    // 删除流水号缓存
                    Cache::forget('flow_seq_num_' . $runInfo->flow_id);

                    //20191212，如果是子流程，则删除主流程节点sub_flow_run_ids中的当前run_id
                    $res = DB::table('flow_run_process')->select('flow_run_process_id', 'sub_flow_run_ids')->whereRaw("sub_flow_run_ids like '%$runId%'")->first();
                    if ($res) {
                        $count = 0;
                        $sub_flow_run_ids_arr = explode(',', $res->sub_flow_run_ids);
                        foreach ($sub_flow_run_ids_arr as $k => $v) {
                            if ($v == $value) {
                                unset($sub_flow_run_ids_arr[$k]);
                                $count++;
                                break;
                            }
                        }
                        if ($count) {
                            DB::table('flow_run_process')->where('flow_run_process_id', '=', $res->flow_run_process_id)->update(['sub_flow_run_ids' => trim(implode(',', $sub_flow_run_ids_arr), ',')]);
                        }
                    }
                    app($this->flowRunProcessRepository)->deleteByWhere($wheres);

                    // 删除导入历史流程列表数据
                    //添加日志
                    $userId = $own['user_id'];
                    $userName = $own['user_name'];
                    $runName = $runInfo->run_name;
                    $replaceStr = [
                        'run_name'  => $runName,
                        'run_id'    => $value,
                        'user_name' => $userName
                    ];
                    // 流程: xxx run_id: xxx 被: xxx 删除。"
                    $logContent = trans("flow.0x030063", $replaceStr);
                    app($this->flowLogService)->addSystemLog($userId,$logContent,'runFlowDelete','flow_run',$value , '' , 0 ,'' ,[] , $runName);
                    // 删除Htmlsignature表
                    // $this->htmlsignatureRepository->deleteByWhere($wheres);
                    // 删除Sms表
                    // $this->smsRepository->deleteByWhere($wheres);
                    if ($todoList) {
                        foreach ($todoList as $todovalue) {
                            $todu_push_params = [];
                            $todu_push_params['receiveUser'] = $todovalue['user_id'];
                            $todu_push_params['deliverUser'] = $own['user_id'];
                            $todu_push_params['operationType'] = 'reduce';
                            $todu_push_params['operationId'] = '10';
                            $todu_push_params['flowId'] = $todovalue['flow_id'];
                            $todu_push_params['runId'] = $todovalue['run_id'];
                            $todu_push_params['processId'] = $todovalue['process_id'];
                            $todu_push_params['flowRunProcessId'] = $todovalue['flow_run_process_id'];
                            // 操作推送至集成中心
                            app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
                        }
                    }
                }
            }
        }
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($runId);

        return $runIdArray;
    }

    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchDataByQueue($id)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchFlowMessage($id);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}