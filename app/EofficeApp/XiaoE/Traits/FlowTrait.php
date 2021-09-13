<?php

namespace App\EofficeApp\XiaoE\Traits;

use App\Utils\XiaoE\Route;

trait FlowTrait
{
    /**
     * 生成流程标题
     * @param $data
     * @param $userId
     * @return mixed
     */
    public function flowNewPageGenerateFlowRunName($data, $userId)
    {
        $userName = app($this->userRepository)->getUserName($userId);
        return app($this->flowRunService)->flowNewPageGenerateFlowRunName([
            'flow_id' => $data['flow_id'],
            'creator' => $userId,
            'user_name' => $userName,
            'form_data' => $data['form_data'],
            'form_structure' => '',//暂时先不传
        ]);
    }

    /**
     * 处理流程表单数据
     * @param $data
     * @param $key
     * @param bool $callBack
     * @return bool|array
     */
    public function handleFlowFormData($data, $configKey, $own, $replace = false, $afterBallBack = false)
    {
        if (!isset($data['creator']) || empty($data['creator'])) {
            return false;
        }
        $config = $this->config($configKey);
        if (!$config) {
            return false;
        }
        if (!isset($config['form_data']) || empty($config['form_data'])) {
            return ['code' => ['0x011006', 'xiaoe']];
        }
        foreach ($config['form_data'] as $column => $key) {
            $config['form_data'][$column] = $data[$key] ?? '';
            $type = $config['form_data_type'][$column] ?? false;
            $config = $this->formateFormData($column, $key, $type, $config, $data, $own);
            if ($replace) {
                $config['form_data'] = $replace($config['form_data'], $column, $key, $type);
            }
        }
        $config['creator'] = $data['creator'];
        //生成流程标题
        if (isset($data['title']) && !empty($data['title'])) {
            $flowRunName = $data['title'];
        } else {
            $flowRunName = $this->flowNewPageGenerateFlowRunName($config, $data['creator']);
        }
        $config['flow_run_name'] = $flowRunName;
        //生成run_name_html
        $result = make($this->flowService, false)->flowNewPageFlowRunInfo(['flow_id' => $config['flow_id'], 'user_id' => $own['user_id']], $own);
        if (isset($result['can_edit_flowname']) && $result['can_edit_flowname']) {
            $can_edit_flowname = 'true';
        } else {
            $can_edit_flowname = 'false';
        }
        $flow_run_name_html = '<div contenteditable="' . $can_edit_flowname . '" class="title-item">' . $config['flow_run_name'] . '</div>';
        $config['run_name_html'] = $flow_run_name_html;
        unset($config['form_data_type']);
        //用户额外的数据处理
        if ($afterBallBack) {
            return $afterBallBack($config);
        }
        return $config;
    }

    /**
     * 将数据转换成对应控件的类型
     */
    public function formateFormData($formField, $xiaoeField, $type, $config, $data, $own)
    {
        switch ($xiaoeField) {
            //用户信息
            case 'creator':
                if ($type == 'data-selector' || $type == 'select') {
                    $config['form_data'][$formField] = [$own['user_id']];
                    $config['form_data'][$formField . '_TEXT'] = $own['user_name'];
                } else {
                    $config['form_data'][$formField] = $own['user_name'];
                }
                break;
            //当前时间
            case 'now':
                $config['form_data'][$formField] = date('Y-m-d H:i:s');
                break;
            //部门
            case 'dept':
                if ($type == 'data-selector' || $type == 'select') {
                    $config['form_data'][$formField] = [$own['dept_id']];
                    $config['form_data'][$formField . '_TEXT'] = $own['dept_name'];
                } else {
                    $config['form_data'][$formField] = $own['dept_name'];
                }
                break;
            //角色
            case 'role':
                if (isset($own['roles']) && !empty($own['roles'])) {
                    $roles = $own['roles'];
                    if ($type == 'data-selector' || $type == 'select') {
                        $config['form_data'][$formField] = array_column($roles, 'role_id');
                        $config['form_data'][$formField . '_TEXT'] = implode(',', array_column($roles, 'role_name'));
                    } else {
                        $config['form_data'][$formField] = implode(',', array_column($roles, 'role_name'));
                    }
                }
                break;
            default:
                return $config;
        }
        return $config;
    }

    public function hasFlowCreatePermission($userId, $flowId)
    {
        $roleAndDept = make($this->userService,false)->getUserDeptIdAndRoleIdByUserId($userId);
        $roleId = $roleAndDept['role_id'];
        $deptId = $roleAndDept['dept_id'];
        $fixedFlowTypeInfo = make($this->flowService,false)->getFixedFlowTypeInfoByUserInfo(['user_id' => $userId, 'role_id' => $roleId, 'dept_id' => $deptId]);
        //验证是否有新建权限
        $count = app($this->flowTypeRepository)->flowNewPermissionListTotalRepository(['flow_id' => $flowId, 'user_id' => $userId, 'role_id' => $roleId, 'dept_id' => $deptId, 'fixedFlowTypeInfo' => $fixedFlowTypeInfo]);
        return $count > 0 ? true : false;
    }

    /**
     * 提交流程
     * @param $userId
     * @param $flow
     * @param $key
     */
    public function turnFlow($own, $flowId, $run)
    {
        $openUrl = $this->openFlowHandlePage($flowId, $run['run_id']);
        $config = [
            'next_flow_process' => '',//固定流程才会传的，必填的，目标节点ID
            'process_copy_user' => '',//固定抄送人id，逗号拼接的字符串
            'sonFlowInfo' => '',//待创建的子流程信息,
            'flowTurnType' => '',//流程提交类型，可选，传back的时候，用来标识是退回操作
        ];
        //多个流出节点需要配置指定流出节点
        if ($config['next_flow_process']) {
            $nextFlowProcessId = $config['next_flow_process'];
        } else {
            $nextFlowProcess = app($this->flowService)->getFlowTransactProcess([
                'run_id' => $run['run_id'],
                'user_id' => $own['user_id'],
                'flow_process' => $run['flow_process']
            ], $own);
            if (!(isset($nextFlowProcess['turn']) && count($nextFlowProcess['turn'] == 1))) {
                return $openUrl;
            }
            $nextFlowProcessId = $nextFlowProcess['turn'][0]['node_id'];
            $config['next_flow_process'] = $nextFlowProcessId;
        }
        $transactUser = app($this->flowService)->getFixedFlowTransactUser([
            'run_id' => $run['run_id'],
            'flow_process' => $run['flow_process'],
            'target_process_id' => $nextFlowProcessId
        ]);
        //查询办理人范围
        if (isset($transactUser['scope']['user_id']) && is_object($transactUser['scope']['user_id'])) {
            $transactUserId = $transactUser['scope']['user_id']->toArray();
            if (count($transactUserId) != 1) {
                return $openUrl;
            }
            $transactUserId = $transactUserId[0];
        } else {
            return $openUrl;
        }
        //提交流程
        $submit = [
            'user_id' => $own['user_id'],//提交人id，必填
            'run_id' => $run['run_id'],//流程id，必填
            'process_id' => $run['process_id'],//所在步骤id，必填
            'flow_process' => $run['flow_process'],//固定流程才会传的，当前节点ID
            'process_transact_user' => $transactUserId,//办理人id，逗号拼接的字符串,
            'process_host_user' => $transactUserId,//主办人id，主办人只有一个
        ];
        $submit = array_merge($submit, $config);
        return app($this->flowService)->postFlowTurning($submit, $own);
    }

    /**
     * 返回流程办理页面的地址
     * @param $flowId
     * @param $runId
     * @return array
     */
    public function openFlowHandlePage($flowId, $runId)
    {
        $url = Route::navigate('/flow/handle', ['run_id' => $runId, 'flow_id' => $flowId]);
        return $this->windowOpen($url);
    }
}
