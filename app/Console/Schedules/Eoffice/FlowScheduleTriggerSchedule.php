<?php

namespace App\Console\Schedules\Eoffice;

use App\Console\Schedules\Schedule;
use Eoffice;
use DB;
use Schema;
use Illuminate\Support\Facades\Redis;

/**
 * 流程定时触发提醒
 * @author zyx 20200408
 */
class FlowScheduleTriggerSchedule implements Schedule
{
    public function call($schedule)
    {
        // 解决没有表单时的脚本报错问题
        if (!Schema::hasTable('flow_schedule')) {
            return;
        }
        
        // 周配置
        $weeksConfig = ['mondays', 'tuesdays', 'wednesdays', 'thursdays', 'fridays', 'saturdays', 'sundays'];
        // 获取所有流程定时触发参数
        $scheduleConfigs = app('App\EofficeApp\Flow\Services\FlowSettingService')->getAllFlowScheduleReminds();
        if (!$scheduleConfigs) {
            return;
        }

        foreach ($scheduleConfigs as $scheduleConfig) {
            $sendData = [];
            $flowId = $scheduleConfig['flow_id'];
            if (!$flowId) {
                continue;
            }
            $flowType = $scheduleConfig['flow_type'];
            if (!$flowType) {
                continue;
            }

            // 先看Redis是否存在当前流程的一些信息
            $flowScheduleInfosNeeded = Redis::get('flow_schedule_infos_needed:flow_id_' . $flowId) ? unserialize(Redis::get('flow_schedule_infos_needed:flow_id_' . $flowId)) : [];

            if ($flowScheduleInfosNeeded) {
                $flowName = $flowScheduleInfosNeeded['flow_name'];
                $nodeId = $flowScheduleInfosNeeded['node_id'];
                $toUsers = $flowScheduleInfosNeeded['to_users'];
                $moduleType = $flowScheduleInfosNeeded['module_type'];
                if (!$toUsers) {
                    continue;
                }
            } else {
                $flowName = DB::table('flow_type')->where('flow_id', $flowId)->value('flow_name');
                $nodeId = DB::table('flow_process')->where('flow_id', $flowId)->where('head_node_toggle', 1)->value('node_id');
                if (!$flowName) {
                    continue;
                }
                // 提醒接收人，也就是首节点办理人
                $users = app('App\EofficeApp\Flow\Services\FlowSettingService')->getBothFreeAndFixedFlowHandlers(['flow_id' => $flowId, 'node_id' => $nodeId, 'flow_type' => $flowType]);
                // if ($flowType == 1) {
                //     $users = app('App\EofficeApp\Flow\Services\FlowService')->getFixedFlowTransactUser(['flow_id' => $flowId, 'target_process_id' => $nodeId, 'flow_process' => $nodeId, 'run_id' => 0]);
                // } else {
                //     $users = app('App\EofficeApp\Flow\Services\FlowSettingService')->getFreeFlowHandlers(['flow_id' => $flowId]);
                // }
                
                // 全体人员
                if ($users['scope']['user_id'] == 'ALL') {
                    $toUsers = json_decode(json_encode(DB::table('user')->where('user_accounts', '!=', '')->pluck('user_id')), TRUE);
                } else { // 指定人员
                    $toUsers = json_decode(json_encode($users['scope']['user_id']), TRUE);
                }
                if (!$toUsers) {
                    continue;
                }
                // moduletype,用处不明
                $moduleType = $scheduleConfig['flow_sort'];

                // 将流程一些信息存入Redis
                $data = [
                    'flow_name' => $flowName,
                    'node_id' => $nodeId,
                    'to_users' => array_unique(array_filter($toUsers)),
                    'module_type' => $moduleType
                ];
                Redis::setex('flow_schedule_infos_needed:flow_id_' . $flowId, 600, serialize($data));
            }

            // 提醒类型
            $sendData['remindMark'] = 'flow-schedule';
            // 过滤重复人员和为空人员
            $sendData['toUser'] = array_unique(array_filter($toUsers));
            // contentparam参数，供提醒设置时使用
            $sendData['contentParam'] = ['flowTitle' => $flowName, 'attentionContent' => $scheduleConfig['attention_content']];
            // stateparam参数，供消息中心跳转使用
            $sendData['stateParams'] = ["flow_id" => $flowId];;
            // moduletype
            $sendData['module_type'] = $moduleType;
                        
            if ($scheduleConfig['type'] == 'year') { // 每年一次指定月日
                $hour = substr($scheduleConfig['trigger_time'], 0, 2);
                $minute = substr($scheduleConfig['trigger_time'], 3, 2);
                $schedule->call(function () use ($sendData) {
                    // 发送提醒
                    Eoffice::sendMessage($sendData);
                })->cron("$minute $hour {$scheduleConfig['day']} {$scheduleConfig['month']} *");
                // })->everyMinute();
            } else if ($scheduleConfig['type'] == 'month') { // 每月一次指定日期
                $schedule->call(function () use ($sendData) {
                    // 发送提醒
                    Eoffice::sendMessage($sendData);
                })->monthlyOn($scheduleConfig['day'], $scheduleConfig['trigger_time']);
            } else if ($scheduleConfig['type'] == 'day') { // 每天一次指定时间
                $schedule->call(function () use ($sendData) {
                    // 发送提醒
                    Eoffice::sendMessage($sendData);
                })->dailyAt($scheduleConfig['trigger_time']);
            } else if ($scheduleConfig['type'] == 'week') { // 每周一次指定时间
                $schedule->call(function () use ($sendData) {
                    // 发送提醒
                    Eoffice::sendMessage($sendData);
                })->weekly()->{$weeksConfig[$scheduleConfig['week'] - 1]}()->at($scheduleConfig['trigger_time']);
            }
        }
    }
}