<?php

namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;

class FlowMonitorService extends FlowBaseService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 流程监控设置
     * @param $param
     * @param $flowId
     * @param $own
     * @return array|string
     */
    public function modifyFlowMonitor($param, $flowId, $own)
    {
        if ($flowId == "batchFlow") {
            $batchFlow = isset($param["batchFlow"]) ? $param["batchFlow"] : [];
            if (empty($batchFlow)) {
                // 保存失败，未获取到流程ID
                return ['code' => ['0x030154', 'flow']];
            } else {
                unset($param["batchFlow"]);
                $saveResult = "";
                foreach ($batchFlow as $key => $flowId) {
                    $saveResult = $this->modifyFlowMonitorRealize($param, $flowId, $own, "batchFlow");
                    if (is_array($saveResult) && isset($saveResult["code"])) {
                        return $saveResult;
                    }
                }
                return "1";
            }
        } else {
            return $this->modifyFlowMonitorRealize($param, $flowId, $own);
        }
    }

    /**
     * 流程监控设置处理操作
     * @param $param
     * @param $flowId
     * @param $own
     * @param string $saveType
     * @return array|string
     */
    public function modifyFlowMonitorRealize($param, $flowId, $own, $saveType = "")
    {
        if (!isset($param['allow_monitor']) || !isset($param['flow_type_has_many_manage_rule']) || ($param['allow_monitor'] == '1' && empty($param['flow_type_has_many_manage_rule']))) {
            // 监控规则不能为空
            return ['code' => ['0x030156', 'flow']];
        }
        $historyInfo = app($this->flowService)->getFlowDefineInfoService([], $flowId);

        if ($param['allow_monitor'] == '1') {
            if (isset($param['monitor_rule_strategy']) && $param['monitor_rule_strategy'] == 1) {
                // 批量设置时，选择仅新增监控规则的策略
                $this->onlyCreateRules($param, $flowId);
            } else {
                // 非批量模式或批量设置时，选择清空原有监控规则并重新添加的策略
                $this->wipeRules($flowId);
                $this->onlyCreateRules($param, $flowId);
            }
        } else {
            $this->wipeRules($flowId);
        }

        if (isset($historyInfo['allow_monitor']) && ($historyInfo['allow_monitor'] != $param['allow_monitor'])) {
            // 更新流程是否开启监控参数
            app($this->flowTypeRepository)->updateData(['allow_monitor' => $param['allow_monitor']], ['flow_id' => $flowId]);
        }

        // 调用日志函数
        $logParam                 = [];
        $newInfo                  = app($this->flowService)->getFlowDefineInfoService([], $flowId);
        $logParam["new_info"]     = $newInfo;
        $logParam["history_info"] = $historyInfo;
        app($this->flowLogService)->logFlowDefinedModify($logParam, "flow_type&flow_id", $flowId, "monitor", $own, $saveType);
        return "1";
    }

    /**
     * 清除原有监控规则
     * @param $flowId
     */
    private function wipeRules($flowId)
    {
        // 删除已有监控人员、角色数据;删除监控范围数据
        $where = ['flow_id' => [$flowId]];
        app($this->flowTypeManageRuleRepository)->deleteByWhere($where);
        app($this->flowTypeManageUserRepository)->deleteByWhere($where);
        app($this->flowTypeManageRoleRepository)->deleteByWhere($where);
        app($this->flowTypeManageScopeUserRepository)->deleteByWhere($where);
        app($this->flowTypeManageScopeDeptRepository)->deleteByWhere($where);
    }

    /**
     * 仅新增监控规则
     * @param $param
     * @param $flowId
     */
    private function onlyCreateRules($param, $flowId)
    {
        foreach ($param['flow_type_has_many_manage_rule'] as $key => $value) {
            $manageRuleInsertData = [
                'flow_id'           => $flowId,
                'monitor_user_type' => isset($value['monitor_user_type']) ? $value['monitor_user_type'] : 1,
                'monitor_scope'     => isset($value['monitor_scope']) ? $value['monitor_scope'] : 0,
                'allow_view'        => isset($value['allow_view']) ? $value['allow_view'] : 0,
                'allow_turn_back'   => isset($value['allow_turn_back']) ? $value['allow_turn_back'] : 0,
                'allow_delete'      => isset($value['allow_delete']) ? $value['allow_delete'] : 0,
                'allow_take_back'   => isset($value['allow_take_back']) ? $value['allow_take_back'] : 0,
                'allow_end'         => isset($value['allow_end']) ? $value['allow_end'] : 0,
                'allow_urge'        => isset($value['allow_urge']) ? $value['allow_urge'] : 0,
            ];
            $insertRule   = app($this->flowTypeManageRuleRepository)->insertData($manageRuleInsertData);
            $insertRuleId = $insertRule->rule_id ?? 0;
            if (empty($insertRuleId)) {
                continue;
            }
            if (isset($value['monitor_user_type'])) {
                if ($value['monitor_user_type'] == '1') {
                    // 如果监控人员类型是指定人员 插入监控人员数据
                    app($this->flowService)->insertFlowTypeManageData($value['monitor_user'], $this->flowTypeManageUserRepository, 'user_id', $insertRuleId, $flowId);
                } elseif ($value['monitor_user_type'] == '2') {
                    // 如果监控人员类型是指定角色 插入监控角色数据
                    app($this->flowService)->insertFlowTypeManageData($value['monitor_role'], $this->flowTypeManageRoleRepository, 'role_id', $insertRuleId, $flowId);
                }
            }
            if (isset($value['monitor_scope'])) {
                if ($value['monitor_scope'] == '2') {
                    // 监控范围为指定部门
                    app($this->flowService)->insertFlowTypeManageData($value['monitor_scope_dept'], $this->flowTypeManageScopeDeptRepository, 'dept_id', $insertRuleId, $flowId);
                } elseif ($value['monitor_scope'] == '5') {
                    // 监控范围为指定人员
                    app($this->flowService)->insertFlowTypeManageData($value['monitor_scope_user'], $this->flowTypeManageScopeUserRepository, 'user_id', $insertRuleId, $flowId);
                }
            }
        }
    }

    /**
     * 【流程运行】 监控流转前获取某条流程当前步骤是否有主办人，返回1：有主办人；返回0：没有主办人。
     *
     * @author miaochenchen
     * @param $param
     * @return [type]      [description]
     */
    public function getFlowProcessHostFlag($param)
    {
        $runId     = $param['run_id'] ?? '';
        $userId    = $param['user_id'] ?? '';
        $processId = $param['process_id'] ?? '';
        if (empty($runId) || empty($userId) || empty($processId)) {
            return ['code' => ['0x000006', 'common']];
        }
        $flowRunInfo = app($this->flowRunRepository)->getDetail($runId);
        if (empty($flowRunInfo->creator)) {
            return ['code' => ['0x000006', 'common']];
        }
        $flowMonitorTurnBackPermission = app($this->flowPermissionService)->getMonitorAllowTypePermission($userId, $flowRunInfo->flow_id, $flowRunInfo->creator, ['allow_turn_back', 'allow_end']);
        if (!$flowMonitorTurnBackPermission) {
            // 如果没有当前流程的监控流转权限，提示无权限
            return ['code' => ['0x000006', 'common']];
        }
        $result = app($this->flowRunService)->opFlagIsExist($param);
        if ($result == "1") {
            return "true";
        } else {
            $toDoArray = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'process_id' => [$param["process_id"]], 'user_run_type' => [1]], 'returntype' => 'array']);
            if (count($toDoArray) == '1' && !empty($param["process_id"])) {
                // 如果当前节点不存在主办人且只有一个未办理人，将这个未办理人自动设置为主办人
                app($this->flowRunProcessRepository)->updateData(['host_flag' => 1], ['run_id' => [$runId], 'process_id' => [$param["process_id"]], 'user_run_type' => [1]]);
                // 如果该节点时合并节点，需要同步更新合并节点的主办人身份
                $flowProcess = app($this->flowProcessRepository)->getDetail($toDoArray[0]['flow_process'] , false , ['merge']);
                if ( isset($flowProcess->merge) && $flowProcess->merge > 0 ) {
                    app($this->flowRunProcessRepository)->updateData(['host_flag' => 1], ['run_id' => [$runId], 'user_id' => [$toDoArray[0]['user_id']] ,'flow_process' => [$toDoArray[0]['flow_process']] , 'flow_serial' => [$toDoArray[0]['flow_serial']], 'user_run_type' => [1]]);
                } 
                return "true";
            }
            return "false";
        }
    }

}
