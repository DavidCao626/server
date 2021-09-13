<?php
namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Schema;
use DB;
/**
 * 流程权限service类，用来管理流程的权限控制
 *
 * @since  2019-03-08 创建
 */
class FlowPermissionService extends FlowBaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 流程主办人提交权限验证
     *
     * @author miaochenchen
     *
     * @since 2019-03-24
     *
     * @param  [array]  $params        [验证权限所需参数]
     * @param  [string] $loginUserInfo [当前用户信息]
     *
     * @return [array or boolean]         [错误代码]
     */
    public function verifyFlowTurningPermission($params, $loginUserInfo)
    {
        $runId               = $params['run_id'] ?? '';
        $processId           = $params['process_id'] ?? ''; // 步骤ID
        $flowProcess         = $params['flow_process'] ?? ''; // 节点ID
        $nextFlowProcess     = $params['next_flow_process'] ?? '';
        $processHostUser     = $params['host_user'] ?? ''; // 主办人(字符串类型的，英文逗号隔开)
        $processTransactUser = $params['transact_user'] ?? ''; // 经办人(字符串类型的，英文逗号隔开)
        $monitor             = $params['monitor'] ?? ''; // 监控提交传的参数
        $currentUser         = $loginUserInfo['user_id'] ?? '';
        $flowTurnType        = $params['flow_turn_type'] ?? ''; // 判断提交还是退回，退回=back
        $limitDate           = $params['limit_date'] ?? ''; // 催促时间
        $processCopyUser     = $params['process_copy_user'] ?? ''; // 被抄送人
        $flowRunProcessId    = $params['flow_run_process_id'] ?? 0;

        $flowRunInfo     = $params['flow_run_info'] ?? '';
        $flowTypeInfo    = $params['flow_type_info'] ?? '';
        $flowProcessInfo = $params['flow_process_info'] ?? '';
        $flowOtherInfo = $params['flow_others_info'] ?? '';
        $flowFormData    = $params['flow_form_data'] ?? '';
        $freeProcessStep    = $params['freeProcessStep'] ?? 0;
        $overtime    = $params['overtime'] ?? ''; //超时处理
        $concurrent_node_id    = $params['concurrent_node_id'] ?? ''; // 并发节点提交时来源节点
        $send_back_submit = 0;
        if (empty($flowRunInfo)) {
            $flowRunInfo = app($this->flowRunRepository)->getDetail($runId);
            if (empty($flowRunInfo)) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        $flowId       = $flowRunInfo->flow_id ?? '';
        $creator      = $flowRunInfo->creator ?? '';
        $maxProcessId = $flowRunInfo->max_process_id ?? '';
        if (empty($flowTypeInfo)) {
            $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowId);
            if (empty($flowTypeInfo)) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        // 办理权限判断
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $runId,
            'user_id' => $currentUser,
            'flow_type_info' => $flowTypeInfo,
            'flow_run_info'  => $flowRunInfo
        ];
        if (!$this->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            // 没有此流程的办理权限
            return ['code' => ['0x030022', 'flow']];
        }
        $flowType     = $flowTypeInfo->flow_type;
        $formId       = $flowTypeInfo->form_id;

        if ($flowType == '1') {
            if (empty($flowProcessInfo)) {
                $flowProcessInfo = $this->getProcessInfo($flowProcess);// 缓存数据
                if (empty($flowProcessInfo)) {
                    return ['code' => ['0x000006', 'common']];
                }
            }
            $handleWay = $flowProcessInfo->process_transact_type ?? 0;
        } else {
            $handleWay = $flowTypeInfo->handle_way ?? 0;
        }
        // 并发流程此处不再做比较
        $isConcurrentFlow = app($this->flowParseService)->isConcurrentFlow($flowId);
        if (!$isConcurrentFlow && ($processId != $maxProcessId)) {
            // 是否已被超时提交
             $currentUserJoinInfoParams = [
                    'search'     => [
                        'run_id'     => [$runId],
                        'user_id'    => [$currentUser],
                        'process_id' => [$processId],
                        'host_flag'  => [1]
                    ],
                    "whereRaw" => ["((overhandle_time <> '0000-00-00 00:00:00') and (overhandle_time IS not NULL))"],
                    'returntype' => 'count',
            ];
            $currentUserLatestJoinInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($currentUserJoinInfoParams);
            if ($currentUserLatestJoinInfo) {
                 return ['code' => ['already_overtime_submit', 'flow']];
            }
            return ['code' => ['0x000006', 'common']];
        }
        // 1、如果是监控提交，验证是否有监控流转权限
        if ($monitor) {
            $verifyMonitorPermission = $this->getMonitorAllowTypePermission($currentUser, $flowId, $creator, ['allow_turn_back', 'allow_end']);
            if (!$verifyMonitorPermission) {
                return ['code' => ['0x000006', 'common']];
            }
        } else {
            // 如果是第四种办理方式或超时提交则跳过主办人验证
            if ($handleWay != '3' && empty($overtime)) {
                // 查看当前用户参与最新步骤的信息
                $currentUserJoinInfoParams = [
                    'search'     => [
                        'run_id'     => [$runId],
                        'user_id'    => [$currentUser],
                        'process_id' => [$processId],
                        'host_flag'  => [1]
                    ],
                    'order_by'   => ['process_id' => 'desc'],
                    'returntype' => 'count',
                    'select_user' =>false
                ];
                $currentUserLatestJoinInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($currentUserJoinInfoParams);
                if (empty($currentUserLatestJoinInfo)) {
                    // 经办人不允许使用主办人提交api提交流程
                    return ['code' => ['0x000006', 'common']];
                }
            }
        }
        // 获取表单数据和结构
        $flowFormDataAndStructureParam = [
            'status' => 'handle',
            'runId'  => $runId,
            'formId' => $formId,
            'flowId' => $flowId,
            'nodeId' => $flowProcess,
            'flow_type_info' => $flowTypeInfo,
            'flow_run_info'  => $flowRunInfo,
            'freeProcessStep' => $freeProcessStep
        ];
        if (!empty($flowFormData)) {
             $flowFormDataAndStructure  = $flowFormData;
        }else{
             $flowFormDataAndStructure = app($this->flowService)->getFlowFormParseData($flowFormDataAndStructureParam, $loginUserInfo);
        }
        $formData                 = $flowFormDataAndStructure['parseData'] ?? [];
        $parseFormStructure       = $flowFormDataAndStructure['parseFormStructure'] ?? [];
        // if (empty($formData) || empty($parseFormStructure)) {
        //     return ['code' => ['0x000006', 'common']];
        // }
        // 获取流程其他设置
        $flowOthersInfo = $flowTypeInfo->flowTypeHasOneFlowOthers ?? app($this->flowOthersRepository)->getDetail($flowId);
        if (empty($flowOthersInfo)) {
            return ['code' => ['0x000006', 'common']];
        }
        // 退回的流程提交到原路径的两个设置，有一个启用了就说明是提交到原路径
        $flowSendBackSubmitMethod = $flowOthersInfo->flow_send_back_submit_method ?? 0;
        // 退回验证必填设置
        $flowSendBackRequired = $flowOthersInfo->flow_send_back_required ?? 0;
        $verifyFormDataRequireParams = [
            'run_id'               => $runId,
            'flow_process'         => $flowProcess,
            'process_id'           => $processId,
            'flow_submit_status'   => 'host',
            'parse_data'           => $formData,
            'parse_form_structure' => $parseFormStructure,
            'flow_form_data'       => $flowFormData,
            'flow_run_info'        => $flowRunInfo,
            'flow_type_info'       => $flowTypeInfo,
            'flow_run_process_id'  => $flowRunProcessId
        ];
        // 跨节点提交忽略必填设置
        $turnRequiredCheck = ($flowTurnType != 'back' && (!isset($params['isWithoutRequired']) || !$params['isWithoutRequired'])); // 提交必填验证
        $backRequiredCheck = ($flowTurnType == 'back' && $flowSendBackRequired); // 退回必填验证
        if (!$monitor && ($turnRequiredCheck || $backRequiredCheck)) {
            // 2、验证流程数据必填
            $verifyFormDataResult = $this->verifyFormDataRequired($verifyFormDataRequireParams, $loginUserInfo);
            if (isset($verifyFormDataResult['code'])) {
                return $verifyFormDataResult;
            }
        }
        if ($flowType == '1') {
            if (empty($flowProcess)) {
                return ['code' => ['0x000006', 'common']];
            }

            // 3、验证流出节点是否在范围内(固定流程验证)
            $transactProcessParams = [
                'monitorSubmit' => $monitor,
                'run_id'        => $runId,
                'flow_process'  => $flowProcess,
                'user_id'       => $currentUser,
                'handle_user'   => $monitor ? $currentUser : '',
                'process_id'    => $processId
            ];
            static $tempFlowTransactProcess = []; // 监控并发再提交turn返回会是空值，并发节点在这里循环提交，提交一次之后获取的turn是空值，用静态变量保存第一次循环的值
            if (isset($tempFlowTransactProcess[$runId.'_'.$flowProcess]) && !empty($tempFlowTransactProcess[$runId . '_' . $flowProcess])) {
                $flowTransactProcess = $tempFlowTransactProcess[$runId . '_' . $flowProcess];
            } else {
                $flowTransactProcess = app($this->flowService)->getFlowTransactProcess($transactProcessParams, $loginUserInfo);
                $tempFlowTransactProcess[$runId . '_' . $flowProcess] = $flowTransactProcess;
            }
            if (empty( $flowTransactProcess) || $overtime) {
                $flowTransactProcess = app($this->flowService)->getFlowTransactProcess($transactProcessParams, $loginUserInfo);
            }
            $transactNodeIdArray = [];
            $handleUserStr       = trim($processTransactUser, ",") . $processHostUser;
            if (empty($handleUserStr)) {
                // 结束流程
                // 判断当前节点是否可结束流程
                if (empty($flowProcessInfo['end_workflow']) && empty($flowProcessInfo->end_workflow)) {
                    if (isset($flowTransactProcess['turn'][0]['submitEnd']) || isset($flowTransactProcess['submitEnd'])) {
                        if (!empty($nextFlowProcess) && $handleUserStr != '') {
                            return ['code' => ['0x000006', 'common']];
                        }
                    } else {
                        return ['code' => ['0x000006', 'common']];
                    }
                }
            } else {
                // 记录出口条件
                $condition = '';
                if ($flowTurnType == 'back') {
                    // 退回
                    if (!empty($flowTransactProcess['back'])) {
                        foreach ($flowTransactProcess['back'] as $key => $value) {
                            if (!empty($value['node_id'])) {
                                $transactNodeIdArray[] = $value['node_id'];
                                if ($value['node_id'] == $nextFlowProcess) {
                                    $condition = $value['condition'] ?? '';
                                }
                            }
                        }
                    }
                    if (empty($transactNodeIdArray) || !in_array($nextFlowProcess, $transactNodeIdArray)) {
                        return ['code' => ['0x000006', 'common']];
                    }
                } else {
                    // 前进
                    if (isset($flowTransactProcess['turn'][0]['submitEnd']) || isset($flowTransactProcess['submitEnd'])) {
                        // 结束流程
                        if (!empty($nextFlowProcess) && $handleUserStr != '') {
                            return ['code' => ['0x000006', 'common']];
                        }
                    } else {
                        // 检查是否是退回的流程
                        $checkIsBack = [
                            'search'     => [
                                'run_id'     => [$runId],
                                'process_id' => [$processId],
                                // 'host_flag'  => [1]
                            ],
                            'returntype' => 'first',
                            'order_by'   => ['is_back' => 'desc'],
                        ];
                        $checkIsBack = app($this->flowRunProcessRepository)->getFlowRunProcessList($checkIsBack);
                        if (!empty($checkIsBack->is_back) && $flowSendBackSubmitMethod) {
                            // 如果开启了退回的流程提交到原路径则验证流出节点是否是原路径
                            $backNodeId = $checkIsBack->send_back_process ?? '';
                            if ($nextFlowProcess != $backNodeId) {
                                return ['code' => ['0x000006', 'common']];
                            } else {
                                // 弹出框直接提交时flowTurnType不会是send_back_submit
                                $send_back_submit = 1;
                            }
                        } else {
                            if (!empty($flowTransactProcess['turn'])) {
                                foreach ($flowTransactProcess['turn'] as $key => $value) {
                                    if (!empty($value['node_id'])) {
                                        $transactNodeIdArray[] = $value['node_id'];
                                        if ($value['node_id'] == $nextFlowProcess) {
                                            $condition = $value['condition'] ?? '';
                                        }
                                    }
                                }
                            }
                            if (!$monitor && (empty($transactNodeIdArray) || !in_array($nextFlowProcess, $transactNodeIdArray)) ) {
                                return ['code' => ['0x000006', 'common']];
                            }
                        }
                        // 5、会签判断
                        if (!$monitor && $flowTurnType != 'back' && !(empty($flowProcessInfo['process_concourse']) && empty($flowProcessInfo->process_concourse)) ) {
                            $haveNotTransactPersonObjectParams = [
                                'run_id'     => $runId,
                                'process_id' => $processId,
                                'search'     => [
                                'user_id' => [$currentUser, '!=']
                                ],
                                'flow_process' => $flowProcess
                            ];
                            $haveNotTransactPersonObject = app($this->flowRunProcessRepository)->getHaveNotTransactPersonRepository($haveNotTransactPersonObjectParams);
                            // if (!$haveNotTransactPersonObject->isEmpty()){
                                // var_dump($haveNotTransactPersonObjectParams);
                            // }
                            if (!$haveNotTransactPersonObject->isEmpty()) {
                                // 当前节点需要会签且有人员未办理
                                return ['code' => ['0x030128', 'flow']];
                            }
                        }
                    }
                }
                // 退回重新提交不选人的情况不需要验证办理人范围
                if ($flowTurnType != "send_back_submit" && ($flowTurnType == 'back' || !(isset($flowTransactProcess['turn'][0]['submitEnd']) || isset($flowTransactProcess['submitEnd'])))) {
                    // 4、前进（非结束）和退回的流程验证提交选的办理人是否在范围内(固定流程验证)
                    $verifyTransactUserScopeParams = [
                        'flow_process'          => $flowProcess,
                        'run_id'                => $runId,
                        'target_process_id'     => $nextFlowProcess,
                        'process_host_user'     => $processHostUser,
                        'process_transact_user' => $processTransactUser,
                        'submit_type' => $flowTurnType,
                    ];
                    $verifyUserScope = $this->verifyUserWithinTransactorScope($verifyTransactUserScopeParams);
                    if (isset($verifyUserScope['code'])) {
                        return $verifyUserScope;
                    }
                }
            }
            // 6、数据验证
            $dataValidateInfo = app($this->flowParseService)->getFlowValidateData(['node_id' => $flowProcess]);
            if (!$monitor && ($flowTurnType != 'back') && !empty($dataValidateInfo)) {
                $dataValidate = app($this->flowParseService)->validateFlowDataAchieve($loginUserInfo, $flowId, $formId, $flowProcess, $processId, $runId);
                if ((!isset($dataValidate['validate']) || !$dataValidate['validate']) ) {
                    if (isset($dataValidate['flow_data_valid_mode'])) {
                        $promptText = $dataValidate['prompt_text'] ?? trans('flow.0x030168');
                        // 数据权限验证不通过，返回设置的提示文字
                        return ['code' => ['0x030168', 'flow'], 'dynamic' => $promptText];
                    } else {
                        $dataValidateTemplate = '';
                        if (!empty($dataValidate) && is_array($dataValidate) && empty($dataValidate[0]['flow_data_valid_mode'])) {
                            foreach ($dataValidate as $dataValidateKey => $dataValidateValue) {
                                if (!empty($dataValidateValue['prompt_text'])) {
                                    $dataValidateTemplate .= $dataValidateValue['prompt_text'] . ';';
                                }
                            }
                            $dataValidateTemplate = trim($dataValidateTemplate, ';');
                            return ['code' => ['0x030168', 'flow'], 'dynamic' => $dataValidateTemplate];
                        }
                        if (!empty($overtime) && !empty($dataValidate) && is_array($dataValidate) && isset($dataValidate[0]['flow_data_valid_mode'])) {
                            return ['code' => ['0x030168', 'flow'], 'dynamic' => $dataValidateTemplate];
                        }
                    }
                }
            }
            // 7、出口条件验证(监控提交不验证出口条件)
            if (!$monitor && !empty($condition) && empty($overtime)) {
                // 退回按原路径提交且设置不验证
                $flowSendBackCondition = $flowOthersInfo->flow_send_back_verify_condition ?? 1;
                if ( ($flowTurnType == 'send_back_submit' || $send_back_submit == 1) && $flowSendBackCondition == 0) {
                } else {
                    $verifyConditionParams = [
                    'form_structure' => $parseFormStructure,
                    'user_id'        => $currentUser,
                    'process_id'     => $processId,
                    ];
                    $verifyConditionResult = app($this->flowRunService)->verifyFlowFormOutletCondition($condition, $formData, $verifyConditionParams);
                    if (!$verifyConditionResult) {
                        // 不满足出口条件
                        return ['code' => ['0x030127', 'flow']];
                    }
                }
            }
            // 8、判断抄送权限(监控提交不验证抄送权限)
            if (!$monitor && !empty($processCopyUser)) {
                // 判断是否有抄送权限
                $verifyCopyParams = [
                    'run_id'       => $runId,
                    'user_id'      => $currentUser,
                    'process_id'   => $processId,
                ];
                $verifyFlowCopyPermission = $this->verifyFlowRunDispensePermission($verifyCopyParams, 'process_copy');
                if (isset($verifyFlowCopyPermission['code'])) {
                    return $verifyFlowCopyPermission;
                }
            }
            // 9、判断催促时间
            $currentTime = date('Y-m-d H:i:s',time());
            if (!empty($limitDate) && $limitDate < $currentTime) {
                // 催促时间不能早于当前时间
                return ['code' => ['0x030169', 'flow']];
            }
        }
        return true;
    }

    /**
     * 流程经办人提交权限验证
     *
     * @author miaochenchen
     *
     * @since 2019-03-24
     *
     * @param  [array]  $params        [验证权限所需参数]
     * @param  [string] $loginUserInfo [当前用户信息]
     *
     * @return [array or boolean]         [错误代码]
     */
    public function verifyFlowTurningOtherPermission($params, $loginUserInfo)
    {
        $runId       = $params['run_id'] ?? '';
        $currentUser = $loginUserInfo['user_id'] ?? '';
        if (empty($runId) || empty($currentUser)) {
            return ['code' => ['0x000006', 'common']];
        }
        // 办理权限判断
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $runId,
            'user_id' => $currentUser,
        ];
        if (!$this->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return ['code' => ['0x000006', 'common']];
        }
        // 查看当前用户参与最新步骤的信息
        $currentUserJoinInfoParams = [
            'search'     => [
                'run_id'        => [$runId],
                'user_id'       => [$currentUser],
                'host_flag'     => [0],
                'saveform_time' => [null]
            ],
            'order_by'   => ['process_id' => 'desc'],
            'returntype' => 'first',
        ];
        $currentUserLatestJoinInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($currentUserJoinInfoParams);
        if (empty($currentUserLatestJoinInfo)) {
            return ['code' => ['0x000006', 'common']];
        }
        // if (!empty($currentUserLatestJoinInfo) && $currentUserLatestJoinInfo->saveform_time && !$currentUserLatestJoinInfo->system_submit) {
            // return ['code' => ['0x000006', 'common']];
        // }
        $hostFlag = $currentUserLatestJoinInfo->host_flag ?? '';
        if ($hostFlag == '1') {
            // 主办人禁止使用经办人提交api提交流程
            return ['code' => ['0x000006', 'common']];
        }
        if (empty($currentUserLatestJoinInfo->process_id) || empty($currentUserLatestJoinInfo->flow_process)) {
            return ['code' => ['0x000006', 'common']];
        }
        $checkOneStepHasSameUserParams = [
            'search'     => [
                'run_id'     => [$runId],
                'user_id'    => [$currentUser],
                'process_id' => [$currentUserLatestJoinInfo->process_id],
                'host_flag'  => [1]
            ],
            'whereRaw'   => [' deliver_time is null '],
            'returntype' => 'count',
        ];
        // 如果同一步骤中有用户既是主办人又是经办人，委托产生的这种情况，再判断下是否有主办人身份=>此处再加判断如果主办人身份是否已经处理完那就用经办人身份
        $checkUserHostFlag = app($this->flowRunProcessRepository)->getFlowRunProcessList($checkOneStepHasSameUserParams);
        if ($checkUserHostFlag) {
            // 主办人禁止使用经办人提交api提交流程
            return ['code' => ['0x000006', 'common']];
        }
        $verifyFormDataRequireParams = [
            'run_id'             => $runId,
            'flow_process'       => $currentUserLatestJoinInfo->flow_process,
            'process_id'         => $currentUserLatestJoinInfo->process_id,
            'flow_submit_status' => 'handle',
        ];
        // 1、验证流程数据必填
        $verifyFormDataResult = $this->verifyFormDataRequired($verifyFormDataRequireParams, $loginUserInfo);
        if (isset($verifyFormDataResult['code'])) {
            return $verifyFormDataResult;
        }
        return true;
    }

    /**
     * 验证当前新建的流程委托规则是否已存在
     *
     * @author miaochenchen
     *
     * @since 2019-03-24
     *
     * @param  [array]  $params [验证权限所需参数]
     * @param  [string] $currentUser [当前用户ID]
     *
     * @return [array or boolean]         [错误代码]
     */
    public function verifyAddFlowAgencyRuleParams($params, $currentUser)
    {
        // 被委托人
        $agentId = $params['agent_id'] ?? '';
        // 委托人
        $byAgentId = $params['by_agent_id'] ?? '';
        // 委托的流程ID
        $flowIdString = $params['flow_id_string'] ?? '';
        // 开始时间
        $startTime = $params['start_time'] ?? '';
        // 结束时间
        $endTime      = $params['end_time'] ?? '';
        $flowIdString = trim($flowIdString, ',');
        if (empty($agentId) || empty($byAgentId) || empty($flowIdString)) {
            return ['code' => ['0x000006', 'common']];
        }
        if (!empty($endTime) && $endTime < $startTime) {
            return ['code' => ['0x030173', 'flow']];
        }
        $flowIdArray = explode(',', $flowIdString);
        if ($agentId == $byAgentId) {
            return ['code' => ['0x000006', 'common']];
        }
        foreach ($flowIdArray as $key => $value) {
            // 验证是否可以委托
            $checkAgencyParams = [
                "flow_id"    => $value,
                "user_id"    => $byAgentId,
                "start_time" => $startTime,
                "end_time"   => $endTime
            ];
            if ($checkAgencyObject = app($this->flowAgencyRepository)->checkFlowAlreadyAgentRepository($checkAgencyParams)) {
                if ($checkAgencyObject->first()) {
                    $flowName = app($this->flowTypeRepository)->findFlowType($value, 'flow_name');
                    return ['code' => ['has_conflict_entrust_rule', 'flow'], 'dynamic' => trans('flow.has_conflict_entrust_rule', ['flow_name' => $flowName])];
                }
            }
        }
        return true;
    }

    /**
     * 验证运行中的流程删除权限
     *
     * @author miaochenchen
     *
     * @since 2019-03-18
     *
     * @param  [array] $params [验证权限所需参数]
     *
     * @return [array or boolean]         [错误代码]
     */
    public function verifyRunningFlowDeletePermission($params)
    {
        $currentUser = $params['user_info']['user_id'] ?? '';
        $userMenu    = $params['user_info']['menus']['menu'] ?? [];
        if (empty($params['run_id']) || empty($currentUser) || empty($userMenu)) {
            return ['code' => ['0x000006', 'common']];
        }
        if ($currentUser == 'admin' && in_array(5, $userMenu)) {
            // admin特殊权限，如果有流程查询菜单，可以删除任意流程
            return true;
        }
        $flowRunInfo = app($this->flowRunRepository)->getDetail($params['run_id']);
        if (empty($flowRunInfo)) {
            return ['code' => ['0x000006', 'common']];
        }
        if (!isset($params['flow_id'])) {
            $params['flow_id'] = $flowRunInfo->flow_id ?? '';
            if (empty($params['flow_id'])) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        $flowTypeInfo = app($this->flowTypeRepository)->getDetail($params['flow_id']);
        if (empty($flowTypeInfo)) {
            return ['code' => ['0x000006', 'common']];
        }
        $flowType     = $flowTypeInfo->flow_type ?? '';
        $maxProcessId = $flowRunInfo->max_process_id ?? '';
        $currentStep  = $flowRunInfo->current_step ?? '';
        // 查询第一步骤的主办人作为创建人，因为有的第一步骤是委托状态，被委托人也作为创建人来判断删除权限
        $firstProcessHostUserParams = [
            'search'     => [
                'run_id'     => [$params['run_id']],
                'process_id' => [1],
                'host_flag'  => [1],
            ],
            'returntype' => 'first',
        ];
        $firstProcessHostUserInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($firstProcessHostUserParams);
        if (empty($firstProcessHostUserInfo->user_id)) {
            return ['code' => ['0x000006', 'common']];
        }
        $creator = $firstProcessHostUserInfo->user_id;
        if ($maxProcessId == '1') {
            // 当前用户是流程创建人且在第一步骤的可删除
            return true;
        } else {
            if ($flowType == '1') {
                // 判断是否是退回到首节点且设置了退回到首节点可删除流程
                $flowProcessParams = [
                    'returntype' => 'count',
                    'search'     => [
                        'head_node_toggle' => [1], // 是否是流程首节点
                        'node_id'          => [$currentStep],
                    ],
                ];
                $atFirstNodeCheck = app($this->flowProcessRepository)->getFlowProcessList($flowProcessParams);
                if ($atFirstNodeCheck) {
                    $flowOtherInfo       = app($this->flowOthersRepository)->getDetail($params['flow_id']);
                    $firstNodeDeleteFlow = $flowOtherInfo->first_node_delete_flow ?? '';
                    if ($firstNodeDeleteFlow == '1') {
                        return true;
                    }
                }
            }
        }
        // 判断监控删除权限
        $monitorDeletePermission = $this->getMonitorAllowTypePermission($currentUser, $params['flow_id'], $creator, 'allow_delete');
        if ($monitorDeletePermission) {
            return true;
        } else {
            return ['code' => ['0x000006', 'common']];
        }
    }

    /**
     * 固定流程验证表单、签办反馈、附件必填
     *
     * @param  [array] $params        [验证必填的参数]
     * @param  [array] $loginUserInfo [当前登录用户信息]
     *
     * @return [array]                [错误代码]
     * @since 2019-03-13
     *
     * @author miaochenchen
     *
     */
    public function verifyFormDataRequired($params, $loginUserInfo)
    {
        $runId = $params['run_id'] ?? ''; // 必填参数当前运行流程ID
        $currentUserId = $loginUserInfo['user_id'] ?? ''; // 必填参数当前登录用户ID
        $processId = $params['process_id'] ?? ''; // 必填参数当前步骤数字
        $flowProcess = $params['flow_process'] ?? ''; // 固定流程必填参数当前节点ID
        $flowSubmitStatus = $params['flow_submit_status'] ?? 'host'; // 当前是主办人还是经办人host、handle
        $flowHandlePageFlowRunInfo = $params['flow_handle_page_flow_run_info'] ?? '';
        $flowFormDataAndStructure   = $params['flow_form_data_and_structure'] ?? '';
        $flowRunProcessId = $params['flow_run_process_id'] ?? 0;
        $flowFormData = $params['flow_form_data'] ?? '';
        if (empty($runId) || empty($currentUserId)) {
            return ['code' => ['0x000006', 'common']];
        }
        // 获取流程运行基本详情
        $flowRunInfo = $params['flow_run_info'] ?? app($this->flowRunRepository)->getDetail($runId);
        if (empty($flowRunInfo)) {
            return ['code' => ['0x000006', 'common']];
        }
        // 获取定义流程ID
        if (!isset($params['flow_id'])) {
            $params['flow_id'] = $flowRunInfo->flow_id ?? '';
            if (empty($params['flow_id'])) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        // 获取定义流程基本信息
        $flowTypeInfo = $params['flow_type_info'] ?? app($this->flowTypeRepository)->getDetail($params['flow_id']);
        if (empty($flowTypeInfo)) {
            return ['code' => ['0x000006', 'common']];
        }
        $flowId = $params['flow_id']; // 定义流程ID
        $flowType = $flowTypeInfo->flow_type ?? ''; // 流程类型
        $formId = $flowTypeInfo->form_id ?? ''; // 表单ID
        if (empty($flowHandlePageFlowRunInfo)) {
            $handlePageParams = [
                'currentUser' => $currentUserId,
                'flow_id' => $flowId,
                'page' => 'handle',
                'run_id' => $runId,
                'user_name' => "",
                'flow_run_process_id' => $flowRunProcessId
            ];
            $flowHandlePageFlowRunInfo = app($this->flowService)->getFlowHandlePageMainData($handlePageParams, $loginUserInfo);
        }
        // $nodeOperation             = $flowHandlePageFlowRunInfo['node_operation'] ?? [];

        //$nodeOperation = $flowHandlePageFlowRunInfo['node_operation'] ?? app($this->flowRunService)->getFlowFormControlOperation(["formId" => $formId, "node_id" => $flowProcess]);
        $nodeOperationParam = [];
        if ($flowType == 2) {
            $nodeOperationParam = ["formId" => $formId];
        } else {
            $nodeOperationParam = ["formId" => $formId, "node_id" => $flowProcess];
        }
        $nodeOperation = $flowHandlePageFlowRunInfo['node_operation'] ?? app($this->flowRunService)->getFlowFormControlOperation($nodeOperationParam);
        $filterParam = [
            "filter_form" => "handle",
            "form_id" => $formId,
            "flow_type" => $flowType,
            "hostFlag" => $flowSubmitStatus == 'host' ? 1 : 0,
            "flowSubmitStatus" => $flowSubmitStatus,
            "node_operation" => $nodeOperation,
        ];
        $nodeOperation = app($this->flowService)->filterFlowNodeOperation($filterParam);
        // 字段控件ID
        $nodeRequiredInfo = [];
        // 勾选了必填项的字段控件ID
        $nodeRequiredInfoChecked = [];
        // 查询流程设置是否勾选了签办反馈和相关附件标签
        $checkFeedbackAndAttachment = [];

        if (!empty($nodeOperation)) {
            $flowOthersInfo = $flowTypeInfo->flowTypeHasOneFlowOthers ?? '';
            $checkFeedbackAndAttachment = $this->checkFlowHasFeedbackAndAttachmentPage($flowId, $flowOthersInfo);
            // 从$nodeOperation里解析出控件ID
            $controlIds = array_keys($nodeOperation);
            foreach ($controlIds as $id) {
                if (substr($id, 0, strlen('DATA_')) === 'DATA_' && !empty($nodeOperation[$id]) && is_array($nodeOperation[$id]) && (array_search("edit", $nodeOperation[$id]) !== false || array_search("attachmentUpload", $nodeOperation[$id]) !== false)) {
                    array_push($nodeRequiredInfo, $id);
                }
            }
            foreach ($nodeOperation as $nodeOperationKey => $nodeOperationValue) {
                if (is_array($nodeOperationValue) && array_search("required", $nodeOperationValue) !== false && (array_search("edit", $nodeOperationValue) !== false || array_search("attachmentUpload", $nodeOperationValue) !== false)) {
                    array_push($nodeRequiredInfoChecked, $nodeOperationKey);
                }
            }
        }
        if (empty($flowFormDataAndStructure)) {
            // 获取表单数据和结构
            $flowFormDataAndStructureParam = [
                'status' => 'handle',
                'runId' => $runId,
                'formId' => $formId,
                'flowId' => $flowId,
                'nodeId' => $flowProcess,
                'formTemplateRuleInfo' => $flowHandlePageFlowRunInfo['formTemplateRuleInfo'] ?? ''
            ];
            $flowFormDataAndStructure = app($this->flowService)->getFlowFormParseData($flowFormDataAndStructureParam, $loginUserInfo);
            $formData = $flowFormDataAndStructure['parseData'] ?? [];
            $parseFormStructure = $flowFormDataAndStructure['parseFormStructure'] ?? [];
        } else {
            $formData           = $flowFormDataAndStructure['parseData'];
            $parseFormStructure = $flowFormDataAndStructure['parseFormStructure'];
        }
        // 必填验证结果
        $verifyResult = [];
        // if (empty($formData) || empty($parseFormStructure)) {
        //     // 请设置至少一个表单控件
        //     return ['code' => ['0x030167', 'flow']];
        // }
        // 流程标题必填
        if (empty($flowRunInfo->run_name)) {
            // 请填写流程标题
            return ['code' => ['0x030162', 'flow']];
        }
        // 过滤不满足显示条件的表单控件再验证必填
        // $nodeRequiredInfo 是满足显示条件的所有控件ID
        // $nodeRequiredInfoChecked 满足显示条件并且勾选了必填的控件ID
        //$nodeRequiredInfo = $this->filterHiddenNode($processId, $nodeRequiredInfo, $parseFormStructure, $formData);

        $nodeShowAndRequiredInfo = array_intersect($nodeRequiredInfo, $nodeRequiredInfoChecked); // 能显示的并且勾选了必填的
        // 根据设置动态验证是否必填，返回满足必填条件的表单控件
        $nodeRequiredInfo = $this->verifyControlOperationCondition($formId, $flowProcess, $processId, $formData, $parseFormStructure, $nodeRequiredInfo, $nodeShowAndRequiredInfo, $nodeOperation);
        // 表单数据验证
        if (!empty($nodeRequiredInfo)) {
            $nodeRequiredArray= [];
            // 遍历表单数据来逐行验证
            foreach ($formData as $key => $controlValue) {
                $verifyResult = $this->verifyDetailLineRequired($parseFormStructure, $key, $controlValue, $nodeRequiredInfo, $flowSubmitStatus, $processId, $formData, $currentUserId, $flowProcess);
                if (isset($verifyResult['code'])) {
                    return $verifyResult;
                }
            }
        }

        // 验证签办反馈和附件必填
        if (!empty($nodeRequiredInfoChecked)) {
            $data['user_id'] = $currentUserId;
            $data['flow_process'] = $flowProcess;
            if (in_array('feedback', $nodeRequiredInfoChecked) && isset($checkFeedbackAndAttachment['feedback']) && $checkFeedbackAndAttachment['feedback'] == 1) {
                $data['verifyType'] = 'feedback';
                $feedBackCheckRequired = $this->verifyFeedbackAndAttachmentRequired($runId, $data);
                if (!$feedBackCheckRequired) {
                    // 请填写签办反馈
                    return ['code' => ['0x030165', 'flow']];
                }
            }

            if ($flowSubmitStatus == 'host' && in_array('attachment', $nodeRequiredInfoChecked) && isset($checkFeedbackAndAttachment['attachment']) && $checkFeedbackAndAttachment['attachment'] == 1) {
                $data['verifyType'] = 'attachment';
                $attachmentCheckRequired = $this->verifyFeedbackAndAttachmentRequired($runId, $data);
                if (!$attachmentCheckRequired) {
                    // 请上传相关附件
                    return ['code' => ['0x030166', 'flow']];
                }
            }
        }
        return $verifyResult;
    }
    /**
     * 验证明细行内必填
     */
    public function verifyDetailLineRequired($parseFormStructure, $controlId, $controlValue, $nodeRequiredInfo, $flowSubmitStatus, $processId, $formData, $currentUserId, $flowProcess) {
        // 明细本体，需要验证显示条件以及每一行的行显示条件，不满足则不验证有效行
        // 显示条件包括最外层字段和（同级）的行字段
        // 行条件包括最外层字段和（同级）的行字段和（子级，需要逐行验证取值）字段
        //
        // 需要将表单数据重新组装，每一行都将条件控件的值组装到最外层 传递给计算公式
        //
        //
        // 明细子项 需要逐行验证显示条件
        // 条件包括最外层和（父级）行字段
        //
        //
        // 必填验证结果
        $verifyResult = [];
        if (isset($parseFormStructure[$controlId]) && $parseFormStructure[$controlId]) {
            $controlAttribute = json_decode($parseFormStructure[$controlId]["control_attribute"], true);
            $controlType = $parseFormStructure[$controlId]["control_type"];
            $controlDataEfbSource = isset($controlAttribute["data-efb-source"]) ? $controlAttribute["data-efb-source"] : '';
            $empty_raw = true;
            // 明细控件
            if ($controlType == "detail-layout") {
                // 一级明细是否满足显示条件
                $empty_raw = $this->filterHiddenNode($processId, $controlId, $parseFormStructure, $formData);

                if($empty_raw) {
                    foreach ($controlValue as $detailValue) {
                        // 一级明细行是否为空行
                        $isEmptyLine = $this->verifyLineEmpty($detailValue,$parseFormStructure,$controlId, $currentUserId, $flowProcess, $processId);
                        // 不是空行
                        if(!$isEmptyLine) {
                            $empty_detail = true;
                            $isNotEmpty = true;
                            $_formData = [];
                            // 将行数据拼接进去
                            $_formData = array_merge($formData,$detailValue);
                            if(isset($controlAttribute['data-efb-row-display-condition']) && $controlAttribute['data-efb-row-display-condition']) {
                                // 一级明细行判断是否满足行显示条件
                                $empty_detail = app($this->flowRunService)->verifyFlowFormOutletCondition($controlAttribute['data-efb-row-display-condition'], $_formData, ['form_structure' => $parseFormStructure, 'user_id' => user()['user_id'], 'process_id' => $processId]);
                            }
                            if ($empty_detail) {
                                foreach ($detailValue as $detailKey => $detailValueItem) {
                                    if (isset($parseFormStructure[$detailKey]) && $parseFormStructure[$detailKey]) {
                                        $controlAttribute = json_decode($parseFormStructure[$detailKey]["control_attribute"], true);
                                        $controlType = $parseFormStructure[$detailKey]["control_type"];
                                        $controlDataEfbSource = isset($controlAttribute["data-efb-source"]) ? $controlAttribute["data-efb-source"] : '';
                                        $empty_second_detail = true;
                                        // 明细控件
                                        if ($controlType == "detail-layout" && is_array($detailValueItem) && $detailValueItem) {
                                            // 二级明细是否满足显示条件
                                            $empty_second_detail = $this->filterHiddenNode($processId, $detailKey, $parseFormStructure, $_formData);
                                            if($empty_second_detail) {
                                                foreach ($detailValueItem as $second_detailValue) {
                                                    // 二级明细行是否为空行
                                                    $isEmptyLine = $this->verifyLineEmpty($second_detailValue,$parseFormStructure,$detailKey, $currentUserId, $flowProcess, $processId);
                                                    // 不是空行
                                                    if(!$isEmptyLine) {
                                                        $isShow = true;
                                                        $isNotEmpty = true;
                                                        $second_formData = [];
                                                        // 将行数据拼接进去
                                                        $second_formData = array_merge($_formData,$second_detailValue);
                                                        if(isset($controlAttribute['data-efb-row-display-condition']) && $controlAttribute['data-efb-row-display-condition']) {
                                                            // 二级明细 行显示条件
                                                            $isShow = app($this->flowRunService)->verifyFlowFormOutletCondition($controlAttribute['data-efb-row-display-condition'], $second_formData, ['form_structure' => $parseFormStructure, 'user_id' => user()['user_id'], 'process_id' => $processId]);
                                                        }
                                                        if ($isShow) {
                                                            foreach ($second_detailValue as $secondDetailKey => $second_detailValueItem) {
                                                                if (isset($parseFormStructure[$secondDetailKey]) && $parseFormStructure[$secondDetailKey]) {
                                                                    $controlAttribute = json_decode($parseFormStructure[$secondDetailKey]["control_attribute"], true);
                                                                    $controlType = $parseFormStructure[$secondDetailKey]["control_type"];
                                                                    $controlDataEfbSource = isset($controlAttribute["data-efb-source"]) ? $controlAttribute["data-efb-source"] : '';
                                                                    $empty_raw = true;
                                                                    // 二级明细子项 显示条件
                                                                    $empty_raw = $this->filterHiddenNode($processId, $secondDetailKey, $parseFormStructure, $second_formData);
                                                                    if ($empty_raw && in_array($secondDetailKey, $nodeRequiredInfo) && (isset($flowSubmitStatus) && ($flowSubmitStatus == 'host' || ($flowSubmitStatus == 'handle' && ($controlType == "countersign" || $controlType == "signature-picture" || $controlType == "electronic-signature"))))) {
                                                                        $verifyResult = $this->verifyLineRequired($parseFormStructure, $secondDetailKey, $second_formData,$processId, $second_detailValueItem, $currentUserId, $flowProcess);
                                                                        if(isset($verifyResult['code'])) {
                                                                            return $verifyResult;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }else {
                                                        $isNotEmpty = false;
                                                    }
                                                    // 如果父级必填，验证是否为空或者都是空行
                                                    if (in_array($detailKey, $nodeRequiredInfo)) {
                                                        if (!$isNotEmpty) {
                                                            // 明细主体必填
                                                            if ($parseFormStructure[$detailKey]["control_title"]) {
                                                                $controlTitle = $parseFormStructure[$detailKey]["control_title"];
                                                            } else {
                                                                $controlTitle = $detailKey;
                                                            }
                                                            // 明细控件【' . $layoutTitle . '】有必填字段未填写，请填写相应字段
                                                            $verifyResult = ['code' => ['0x030163', 'flow'], 'dynamic' => trans('flow.0x030163', ['control_title' => $controlTitle])];
                                                            return $verifyResult;
                                                        }
                                                    }
                                                }
                                            }
                                        }else {// 普通控件
                                            // 一级级明细中 普通控件 显示条件
                                            $empty_second_detail = $this->filterHiddenNode($processId, $detailKey, $parseFormStructure, $_formData);
                                            if ($empty_second_detail && in_array($detailKey, $nodeRequiredInfo) && (isset($flowSubmitStatus) && ($flowSubmitStatus == 'host' || ($flowSubmitStatus == 'handle' && ($controlType == "countersign" || $controlType == "signature-picture" || $controlType == "electronic-signature"))))) {
                                                $verifyResult = $this->verifyLineRequired($parseFormStructure, $detailKey, $_formData,$processId, $detailValueItem, $currentUserId, $flowProcess);
                                                if(isset($verifyResult['code'])) {
                                                    return $verifyResult;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }else {
                            $isNotEmpty = false;
                        }
                        // 如果父级必填，验证是否为空或者都是空行
                        if (in_array($controlId, $nodeRequiredInfo)) {
                            if (!$isNotEmpty) {
                                // 明细主体必填
                                if ($parseFormStructure[$controlId]["control_title"]) {
                                    $controlTitle = $parseFormStructure[$controlId]["control_title"];
                                } else {
                                    $controlTitle = $controlId;
                                }
                                // 明细控件【' . $layoutTitle . '】有必填字段未填写，请填写相应字段
                                $verifyResult = ['code' => ['0x030163', 'flow'], 'dynamic' => trans('flow.0x030163', ['control_title' => $controlTitle])];
                                return $verifyResult;
                            }
                        }
                    }
                }
            }else {// 普通控件
                // 普通控件 显示条件
                $empty_raw = $this->filterHiddenNode($processId, $controlId, $parseFormStructure, $formData);
                if ($empty_raw && in_array($controlId, $nodeRequiredInfo) && (isset($flowSubmitStatus) && ($flowSubmitStatus == 'host' || ($flowSubmitStatus == 'handle' && ($controlType == "countersign" || $controlType == "signature-picture" || $controlType == "electronic-signature"))))) {
                    $verifyResult = $this->verifyLineRequired($parseFormStructure, $controlId, $formData,$processId, $controlValue, $currentUserId, $flowProcess);
                    if(isset($verifyResult['code'])) {
                        return $verifyResult;
                    }
                }
            }
        }
        return $verifyResult;
    }
    /**
     * 判断控件ID是否满足显示条件，过滤不满足条件的控件，满足则返回
     * @param $processId
     * @param $controlIds
     * @param $parseFormStructure
     * @param $formData
     * @return array
     */
    public function filterHiddenNode($processId, $controlId, $parseFormStructure, $formData)
    {
        $showControlId = true;
        $controlAttributes = $parseFormStructure[$controlId]['control_attribute'] ?? '';
        $isShow = false;
        if ($controlAttributes) {
            $controlAttributes = \GuzzleHttp\json_decode($controlAttributes, true);
            if (isset($controlAttributes['data-efb-display-condition'])) {
                $isShow = app($this->flowRunService)->verifyFlowFormOutletCondition($controlAttributes['data-efb-display-condition'], $formData, ['form_structure' => $parseFormStructure, 'user_id' => user()['user_id'], 'process_id' => $processId]);
            } else {
                $isShow = true;
            }
        }
        //
        $selectControl = false;
        $needControl = false;
        $hiddenControl = false;
        if ($isShow) {
            $showControlId = true;
            // 再验证下拉选择器的 data-efb-set-display-control 显示条件
            $controlAttributes = $parseFormStructure[$controlId]['control_attribute'] ?? '';
            if ($controlAttributes) {
                $controlAttributes = \GuzzleHttp\json_decode($controlAttributes, true);
                // 先判断自身的是否有隐藏控件设置
                if (isset($controlAttributes['data-efb-hide']) && $controlAttributes['data-efb-hide']) {
                    $hiddenControl = true;
                }
                if (!$hiddenControl && isset($controlAttributes['data-efb-set-display-control'])) {
                    $controlArr = \GuzzleHttp\json_decode($controlAttributes['data-efb-set-display-control'], true);
                    $control = array_column($controlArr, 'control');
                    $result = array_reduce($control, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                    $count = count($control); // 下拉选项个数
                    $arrayCountValues = array_count_values($result); // 重复个数如果和下拉选项个数相同，则说明该控件显示
                    foreach ($arrayCountValues as $key => $value) {
                        if ($value == $count) {
                            $needControl = true;
                        }
                    }
                    foreach ($controlArr as $control) {
                        $showControl = $control['control'] ?? [];
                        $relation = $control['relation'];
                        $value = $control['value'];
                        foreach ($showControl as $showId) {
                            $selectedValue = $formData[$controlId] ?? '';
                            switch ($relation) {
                                case "=":
                                    if ($selectedValue != $value) {
                                        $selectControl = true;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }else {
            $showControlId = false;
        }
        if($selectControl) {
            $showControlId = false;
        }
        if($needControl) {
            $showControlId = true;
        }
        return $showControlId;
    }
    /**
     * 明细行验证必填
     */
    public function verifyLineRequired($parseFormStructure, $controlId, $formData,$processId, $controlValue, $currentUserId, $flowProcess)
    {
        $verifyResult = [];
        $controlAttribute = json_decode($parseFormStructure[$controlId]["control_attribute"], true);
        $controlType = $parseFormStructure[$controlId]["control_type"];
        $controlDataEfbSource = isset($controlAttribute["data-efb-source"]) ? $controlAttribute["data-efb-source"] : '';
        // 如果是 checkbox || upload ，把值变成字符串
        if ($controlType == "checkbox" || $controlType == "upload" || ($controlType == "select" && $controlDataEfbSource == "commonData")) {
            $controlValue = $this->mergeArrayValueForVerifyRequired($controlValue);
        } else if ($controlType == "countersign") {
            $countersignContent = "";
            // 判断历史数据里有没有会签内容
            if ($controlValue && is_array($controlValue) && count($controlValue) > 0) {
                foreach ($controlValue as $cKey => $cValue) {
                    if (isset($cValue['countersign_user_id']) && isset($currentUserId) && isset($flowProcess) && isset($cValue['process_id'])) {
                        if ($cValue && $cValue['countersign_user_id'] == $currentUserId && $cValue['process_id'] == $processId) {
                            $countersignContent = $cValue["countersign_content"];
                        }
                    }
                }
            }
            $controlValue = $countersignContent;
            // 前端默认值有bug 暂时去掉默认值填充，前端没传值就为空
            // if (empty($controlValue)) {
            //     // 如果会签设置了默认内容，取默认值
            //     if (isset($formData[$controlId . '_default_value']) && !empty($formData[$controlId . '_default_value'])) {
            //         $controlValue = $formData[$controlId . '_default_value'];
            //     }
            // }
        }
        if ($controlValue === null || (is_array($controlValue) && empty($controlValue)) || (is_string($controlValue) && ($controlValue === "" || ($controlValue !== "" && trim($controlValue) === "")))) {

            if ($parseFormStructure[$controlId]["control_title"]) {
                $controlTitle = $parseFormStructure[$controlId]["control_title"];
            } else {
                $controlTitle = $controlId;
            }
            // 必填字段【' . $verifyResult . '】未填写，请填写相应字段
            $verifyResult = ['code' => ['0x030164', 'flow'], 'dynamic' => trans('flow.0x030164', ['control_title' => $controlTitle])];
        }
        return $verifyResult;
    }
    /**
     * 验证行是否为空
     */
    public function verifyLineEmpty($formData, $parseFormStructure, $controlId, $currentUserId, $flowProcess, $processId)
    {
        if(empty($formData)) {
            return true;
        }
        $count = 0;
        $empty = 0;
        foreach ($formData as $key => $controlValue) {
            if (isset($parseFormStructure[$controlId]) && $parseFormStructure[$controlId]) {
                $controlAttribute = json_decode($parseFormStructure[$controlId]["control_attribute"], true);
                $controlType = $parseFormStructure[$controlId]["control_type"];
                $controlDataEfbSource = isset($controlAttribute["data-efb-source"]) ? $controlAttribute["data-efb-source"] : '';
                // 如果是 checkbox || upload ，把值变成字符串
                if ($controlType == "checkbox" || $controlType == "upload" || ($controlType == "select" && $controlDataEfbSource == "commonData")) {
                    $controlValue = $this->mergeArrayValueForVerifyRequired($controlValue);
                } else if ($controlType == "countersign") {
                    $countersignContent = "";
                    // 判断历史数据里有没有会签内容
                    if ($controlValue && is_array($controlValue) && count($controlValue) > 0) {
                        foreach ($controlValue as $cKey => $cValue) {
                            if (isset($cValue['countersign_user_id']) && isset($currentUserId) && isset($flowProcess) && isset($cValue['process_id'])) {
                                if ($cValue && $cValue['countersign_user_id'] == $currentUserId && $cValue['process_id'] == $processId) {
                                    $countersignContent = $cValue["countersign_content"];
                                }
                            }
                        }
                    }
                    $controlValue = $countersignContent;
                    // if (empty($controlValue)) {
                    //     // 如果会签设置了默认内容，取默认值
                    //     if (isset($formData[$controlId . '_default_value']) && !empty($formData[$controlId . '_default_value'])) {
                    //         $controlValue = $formData[$controlId . '_default_value'];
                    //     }
                    // }
                }
                $count++;
                if ($controlValue === null || (is_array($controlValue) && empty($controlValue)) || (is_string($controlValue) && ($controlValue === "" || ($controlValue !== "" && trim($controlValue) === "")))) {
                    $empty++;
                }
            }
        }
        return $count == $empty;
    }
    /**
     * 表单控件字段必填条件根据设置值动态解析
     * @param $formId
     * @param $nodeId
     * @param $processId
     * @param $formData
     * @param $parseFormStructure
     * @param $nodeRequiredInfo
     * @param $nodeShowAndRequiredInfo
     * @return array
     */
    public function verifyControlOperationCondition($formId, $nodeId, $processId, $formData, $parseFormStructure, $nodeRequiredInfo, $nodeShowAndRequiredInfo, $nodeOperation)
    {
        $controlOperationCondition = app($this->flowRunService)->getFlowFormControlOperationCondition(["formId" => $formId, "node_id" => $nodeId]);
        $formStructure = [];
        foreach ($parseFormStructure as $key => $value) {
            $formStructure[$key] = $value['control_type'] ?? '';
        }
        $requireControlIds = [];
        $realRequireControlIds = [];
        // 判断是否是下拉控件，即判断是否含有控制其他控件的控件条件值
        $hasSelectedControlFlag = false; // 是否有下拉控制的控件必填标识
        foreach ($nodeRequiredInfo as $noChecked) {
            if (isset($controlOperationCondition[$noChecked]['control_required']) && !empty($controlOperationCondition[$noChecked]['control_required'])) {
                foreach ($controlOperationCondition[$noChecked]['control_required'] as $controlRequired) {
                    $selectedControl = $controlRequired['control'] ?? [];
                    $relation = $controlRequired['relation'];
                    $value = $controlRequired['value'];
                    foreach ($selectedControl as $selectedId) {
                        array_push($requireControlIds, $selectedId);
                        $selectedValue = $formData[$noChecked] ?? '';
                        switch ($relation) {
                            case "=":
                                if ($selectedValue == $value && !in_array($selectedId, $realRequireControlIds)) {
                                    array_push($realRequireControlIds, $selectedId);
                                }
                                break;
                            case '>':
                                if ($selectedValue > $value && !in_array($selectedId, $realRequireControlIds)) {
                                    array_push($realRequireControlIds, $selectedId);
                                }
                                break;
                            case "<":
                                if ($selectedValue < $value && !in_array($selectedId, $realRequireControlIds)) {
                                    array_push($realRequireControlIds, $selectedId);
                                }
                                break;
                        }
                    }
                }
                $hasSelectedControlFlag = true;
            }
        }

        // 获取真正意义上的必填控件，然后再计算控件自身的必填条件
        $realCheckedNodeInfo = [];
        if ($hasSelectedControlFlag) {
            $realCheckedNodeInfo = array_merge($realRequireControlIds, array_diff($nodeShowAndRequiredInfo, $requireControlIds));
        } else {
            $realCheckedNodeInfo = $nodeShowAndRequiredInfo;
        }
        // 最后验证其必填条件值
        $node = [];
        foreach ($realCheckedNodeInfo as $value) {
            if (empty($nodeOperation[$value]) || !is_array($nodeOperation[$value]) || (array_search("edit", $nodeOperation[$value]) === false && array_search("attachmentUpload", $nodeOperation[$value]) === false)) {
                continue;
            }
            if (isset($controlOperationCondition[$value]['condition_required']) && $controlOperationCondition[$value]['condition_required']) {
                $verifyFlag = app($this->flowRunService)->verifyFlowFormOutletCondition($controlOperationCondition[$value]['condition_required'], $formData, ['form_structure' => $formStructure, 'user_id' => user()['user_id'], 'process_id' => $processId]);
                if ($verifyFlag && !in_array($value, $node)) {
                    array_push($node, $value);
                }
            } else {
                if (!in_array($value, $node)) {
                    array_push($node, $value);
                }
            }
        }
        return $node;
    }


    /**
     * 判断控件ID是否满足显示条件，过滤不满足条件的控件，满足则返回  -已修改调用，暂时不用这个方法
     * @param $processId
     * @param $controlIds
     * @param $parseFormStructure
     * @param $formData
     * @return array
     */
    public function filterHiddenNodes($processId, $controlIds, $parseFormStructure, $formData)
    {

        $showControlIds = [];
        $detailId = []; // 明细控件ID
        foreach ($controlIds as $controlId) {
            $controlAttributes = $parseFormStructure[$controlId]['control_attribute'] ?? '';
            if ($controlAttributes) {
                $controlAttributes = \GuzzleHttp\json_decode($controlAttributes, true);
                if (isset($controlAttributes['data-efb-display-condition'])) {
                    $formStructure = [];
                    foreach ($parseFormStructure as $key => $value) {
                        // 记录明细控件是否满足显示，如果明细父项不满足显示条件，子项不继续判断
                        $formStructure[$key] = $value['control_type'] ?? '';
                        if ($value['control_type'] == 'detail-layout' && !in_array($key, $detailId)) {
                            array_push($detailId, $key);
                        }
                    }
                    $isShow = app($this->flowRunService)->verifyFlowFormOutletCondition($controlAttributes['data-efb-display-condition'], $formData, ['form_structure' => $formStructure, 'user_id' => user()['user_id'], 'process_id' => $processId]);

                    if ($isShow) {
                        array_push($showControlIds, $controlId);
                    }
                } else {
                    array_push($showControlIds, $controlId);
                }
            }
        }
        //
        $selectControl = [];
        $needControl = [];
        $hiddenControl = [];
        foreach ($showControlIds as $controlId) {
            // 再验证下拉选择器的 data-efb-set-display-control 显示条件
            $controlAttributes = $parseFormStructure[$controlId]['control_attribute'] ?? '';
            if ($controlAttributes) {
                $controlAttributes = \GuzzleHttp\json_decode($controlAttributes, true);
                // 先判断自身的是否有隐藏控件设置
                if (isset($controlAttributes['data-efb-hide']) && $controlAttributes['data-efb-hide']) {
                    $hiddenControl[] = $controlId;
                }

                if (isset($controlAttributes['data-efb-set-display-control'])) {
                    $controlArr = \GuzzleHttp\json_decode($controlAttributes['data-efb-set-display-control'], true);
                    $control = array_column($controlArr, 'control');
                    $result = array_reduce($control, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                    $count = count($control); // 下拉选项个数
                    $arrayCountValues = array_count_values($result); // 重复个数如果和下拉选项个数相同，则说明该控件显示
                    foreach ($arrayCountValues as $key => $value) {
                        if ($value == $count) {
                            $needControl[] = $key;
                        }
                    }
                    foreach ($controlArr as $control) {
                        $showControl = $control['control'] ?? [];
                        $relation = $control['relation'];
                        $value = $control['value'];
                        foreach ($showControl as $showId) {
                            $selectedValue = $formData[$controlId] ?? '';
                            switch ($relation) {
                                case "=":
                                    if ($selectedValue != $value) {
                                        foreach ($showControl as $id) {
                                            if (! in_array($id, $selectControl)) {
                                                array_push($selectControl, $id);
                                            }
                                        }
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }
        $showControlIds = array_diff($showControlIds, $hiddenControl); // 过滤隐藏的
        $showControlIds = array_diff($showControlIds, $selectControl); // 处理下拉框控制的
        $showControlIds = array_merge($showControlIds, $needControl); // 下拉框控制的如果都选择了，则保留
        foreach ($detailId as $value) {
            // 如果明细父项不显示，则过滤掉明细子项，即便满足显示条件，避免后面验证必填导致异常
            if (!array_key_exists($value, array_flip($showControlIds))) {
                foreach ($showControlIds as $k => $id) {
                    if (substr($id, 0, strlen($value)) === $value) {
                        unset($showControlIds[$k]);
                    }
                }
            }
        }
        return $showControlIds;
    }


    /**
     * 判断流程是否设置了签办反馈和相关附件标签
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [type]  $flowId  [流程ID]
     *
     * @return [type]    [description]
     */
    public function checkFlowHasFeedbackAndAttachmentPage($flowId, $flowOthersInfo = '')
    {
        $flowOthersInfo = $flowOthersInfo ?: app($this->flowOthersRepository)->getDetail($flowId);
        $feedbackAndAttachmentCheck = [
            'feedback'   => 1,
            'attachment' => 1,
        ];
        if (!empty($flowOthersInfo) && isset($flowOthersInfo->flow_detail_page_choice_other_tabs)) {
            if (!empty($flowOthersInfo->flow_detail_page_choice_other_tabs)) {
                $flowDetailPageChoiceOtherTabs = explode(',', $flowOthersInfo->flow_detail_page_choice_other_tabs);
                if (!empty($flowDetailPageChoiceOtherTabs) && is_array($flowDetailPageChoiceOtherTabs)) {
                    if (!in_array('feedback', $flowDetailPageChoiceOtherTabs)) {
                        $feedbackAndAttachmentCheck['feedback'] = 0;
                    }
                    if (!in_array('attachment', $flowDetailPageChoiceOtherTabs)) {
                        $feedbackAndAttachmentCheck['attachment'] = 0;
                    }
                } else {
                    $feedbackAndAttachmentCheck['feedback']   = 0;
                    $feedbackAndAttachmentCheck['attachment'] = 0;
                }
            } else {
                $feedbackAndAttachmentCheck['feedback']   = 0;
                $feedbackAndAttachmentCheck['attachment'] = 0;
            }
        }
        return $feedbackAndAttachmentCheck;
    }

    /**
     * 验证签办反馈、公共附件必填
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [string] $runId [流程运行ID]
     * @param  [array]  $data  [其他参数]
     *
     * @return [boolean]       [正确和错误]
     */
    public function verifyFeedbackAndAttachmentRequired($runId, $data)
    {
        $verifyType = $data["verifyType"];
        if ($verifyType == "feedback") {
            $data           = $this->parseParams($data);
            $data["run_id"] = $runId;
            $data["search"] = ["user_id" => [$data["user_id"]]];
            if (!empty($data["flow_process"])) {
                $data["search"]["flow_process"] = [$data["flow_process"]];
            }
            $returnData = $this->response(app($this->flowRunFeedbackRepository), 'getFeedbackListTotal', 'getFlowFeedbackListRepository', $data);
            if ($returnData["total"]) {
                return true;
            } else {
                return false;
            }
        } else if ($verifyType == "attachment") {
            $searchAttachmentParams = [
                'entity_table' => 'flow_run',
                'entity_id' => [
                    "run_id" => [$runId],
                    "user_id" => [$data["user_id"]]
                ]
            ];
            if (!empty($data["flow_process"])) {
                $searchAttachmentParams["entity_id"]["flow_process"] = [$data["flow_process"]];
            }
            $attachments = app($this->attachmentService)->getAttachmentIdsByEntityId($searchAttachmentParams);
            if (count($attachments)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 【流程运行】验证某个明细布局里面的某个选项列是否必填
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [string] $id                   [控件id:(DATA_n)]
     * @param  [array]  $layoutData           [明细布局的value(获取方式:$formData[control_id])的数组]
     * @param  [string] $itemCount            [明细布局的列数]
     * @param  [string] $controlType          [待验证列的控件类型]
     * @param  [array]  $formData             [所有数据]
     * @param  [array]  $parseFormStructure   [所有表结构]
     * @param  [array]  $controlDataEfbSource [当前控件(被验证列)的data-efb-source属性]
     *
     * @return [boolean]                    [正确错误]
     */
    public function verifyLayoutRequired($id, $layoutData, $itemCount, $controlType, $formData, $parseFormStructure, $controlDataEfbSource, $processId)
    {
        $verifyResult = true;
        if (!empty($layoutData)) {
            foreach ($layoutData as $key => $value) {
                $verifyItemValid = "";
                if ($itemCount == "1") {
                    // 只有一列，置为有效
                    $verifyItemValid = true;
                } else {
                    // 判断明细控件的某一行内容是否是有效的，即，除了待验证列以外的列，是否都是为空，都为空，无效
                    $verifyItemValid = $this->verifyLayoutItemValid($id, $value, $key, $formData, $parseFormStructure, $processId);
                }
                if ($verifyItemValid) {
                    $controlValue = "";
                    if (isset($value[$id])) {
                        $idText = $id . '_TEXT';
                        if (! isset($value[$idText])) {
                            $controlValue = $value[$id];
                        } else {
                            $controlValue = $value[$idText] ? $value[$idText] : $controlValue;
                        }
                    }
                    // 如果是 checkbox || upload ，把值变成字符串
                    if ($controlType == "checkbox" || $controlType == "upload" || ($controlType == "select" && $controlDataEfbSource == "commonData") || $controlType == "data-selector") {
                        $controlValue = $this->mergeArrayValueForVerifyRequired($controlValue);
                    } else if ($controlType == "countersign") {
                        $layoutCountersignControlInfo = [];
                        if ($formData[$id . '_' . $key . '_COUNTERSIGN']) {
                            $layoutCountersignControlInfo = $formData[$id . '_' . $key . '_COUNTERSIGN'];
                        }
                        if ($layoutCountersignControlInfo["countersign_content"]) {
                            $controlValue = $layoutCountersignControlInfo["countersign_content"];
                        }
                    }
                    if ($controlValue != "" && trim($controlValue) != "") {
                        $verifyResult = true;
                    } else {
                        $verifyResult = false;
                    }
                }
                if ($verifyResult == false) {
                    return $verifyResult;
                }
            }
        }
        return $verifyResult;
    }

    /**
     * 判断明细控件的某一行内容是否是有效的，即，除了待验证列以外的列，是否都是为空，都为空，无效；如果明细字段，只有一列，且列id=传进来的id，有效
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [string] $id                 [控件id:(DATA_n)]
     * @param  [string] $itemValue          [明细子项内容]
     * @param  [string] $itemKey            [明细子项key]
     * @param  [array]  $formData           [表单数据]
     * @param  [array]  $parseFormStructure [表单控件结构]
     *
     * @return [boolean]                    [返回 false，表明此行无效。true，有效]
     */
    public function verifyLayoutItemValid($id, $itemValue, $itemKey, $formData, $parseFormStructure, $processId)
    {
        $valueFlag = false;
        if (!empty($itemValue)) {
            foreach ($itemValue as $key => $value) {
                // 明细数据中返回了id字段，判断的时候需要跳过
                if ($key != $id && $key != 'id') {
                    $controlType = "";
                    if (isset($parseFormStructure[$key]) && $parseFormStructure[$key] && $parseFormStructure[$key]["control_type"]) {
                        $controlType = $parseFormStructure[$key]["control_type"];
                    }
                    if ($controlType == "countersign") {
                        $layoutCountersignControlInfo = [];
                        if ($formData[$key . '_' . $itemKey . '_COUNTERSIGN']) {
                            $layoutCountersignControlInfo = $formData[$key . '_' . $itemKey . '_COUNTERSIGN'];
                        }
                        if ($layoutCountersignControlInfo["countersign_content"]) {
                            $controlValue = $layoutCountersignControlInfo["countersign_content"];
                            if ($controlValue !== "" && trim($controlValue) !== "") {
                                $valueFlag = true;
                            }
                        }
                    } else {
                        if ($value) {
                            if (!is_array($value)) {
                                if ($value !== "") {
                                    $valueFlag = true;
                                }
                            } else {
                                $controlValue = "";
                                $controlValue = $this->mergeArrayValueForVerifyRequired($value);
                                if ($controlValue !== "" && trim($controlValue) !== "") {
                                    $valueFlag = true;
                                }
                            }
                        }
                    }
                    if ($valueFlag) {
                        return $valueFlag;
                    }
                }
            }
        }
        return $valueFlag;
    }

    /**
     * 判断明细字段，是否有有效行，用来验证明细字段整体必填
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [array] $layoutData         [明细控件数据]
     * @param  [array] $formData           [表单数据]
     * @param  [array] $parseFormStructure [表单控件结构]
     *
     * @return [boolean]                   [有有效行，返回true，没有，返回空]
     */
    public function verifyLayoutDetailHasValidLine($layoutData, $formData, $parseFormStructure, $processId)
    {
        if (!empty($layoutData)) {
            foreach ($layoutData as $key => $value) {
                $verifyItemValid = $this->verifyLayoutItemValid("", $value, $key, $formData, $parseFormStructure, $processId);
                if ($verifyItemValid === true) {
                    return true;
                }
            }
        }
        return "";
    }
    /**
     * 判断二级明细字段，是否有有效行
     *
     */
    public function verifySecondLayoutDetailHasValidLine($controlId, $formData, $parseFormStructure, $processId)
    {
        $controlCount = explode('_', $controlId);
        // 二级明细父级
        $parentId = $controlCount[0]."_".$controlCount[1];
        $result = false;

        if(isset($formData[$parentId]) && isset($parseFormStructure[$parentId]) && isset($parseFormStructure[$controlId])) {
            if(!empty($formData[$parentId])) {
                foreach ($formData[$parentId] as $key => $value) {
                    // 每一行是否有效
                    $lanHasData = false;
                    if(isset($value[$controlId]) && !empty($value[$controlId])) {
                        foreach ($value[$controlId] as $secondLine) {
                            // 每一个字段是否有效
                            $hasData = false;
                            foreach ($secondLine as $_key => $_value) {
                                if($_key != 'data_id') {
                                    if(is_array($_value)) {
                                        $_value =  implode(',', $_value);
                                    }
                                    if($_value) {
                                        $hasData = true;
                                    }
                                }
                            }
                            if ($hasData) {
                                $lanHasData = true;
                            }
                        }
                    }
                    if ($lanHasData) {
                        $result = true;
                    }
                }
            }
        }
        if($result) {
            return true;
        }else {
            return $controlId;
        }
    }
    /**
     * 判断二级明细字段，是否有有效行
     *
     */
    public function verifySecondLayoutDetailHasValidDetail($controlId, $formData, $parseFormStructure, $processId)
    {
        $controlCount = explode('_', $controlId);
        // 二级明细父级
        $grandId = $controlCount[0]."_".$controlCount[1];
        $parentId = $controlCount[0]."_".$controlCount[1]."_".$controlCount[2];
        $result = true;

        if(isset($formData[$grandId]) && isset($parseFormStructure[$grandId]) && isset($parseFormStructure[$controlId])) {
            if(!empty($formData[$grandId])) {
                $grantLineHasData = true;
                foreach ($formData[$grandId] as $key => $value) {
                    // 每一行中查找二级明细子项
                    $lanHasData = true;
                    if(isset($value[$parentId]) && !empty($value[$parentId])) {
                        // 每一个二级行字段是否有效
                        $hasData = true;
                        foreach ($value[$parentId] as $secondLine) {
                            if (isset($secondLine[$controlId])) {
                                if(is_array($secondLine[$controlId])) {
                                    if(empty($secondLine[$controlId])) {
                                        $hasData = false;
                                    }
                                }else {
                                    if($secondLine[$controlId] === '') {
                                        $hasData = false;
                                    }
                                }
                            }
                        }
                        if (!$hasData) {
                            $lanHasData = false;
                        }
                    }
                    if (!$lanHasData) {
                        $grantLineHasData = false;
                    }
                }
                if (!$grantLineHasData) {
                    $result = false;
                }
            }
        }
        if($result) {
            return true;
        }else {
            return $controlId;
        }
    }
    /**
     * 功能函数，加工一下 checkbox 和 upload 控件的值，用于判断必填
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [array] $controlValue [控件值]
     *
     * @return [string]              [有值，返回json字符串，没有，返回空]
     */
    public function mergeArrayValueForVerifyRequired($controlValue)
    {
        $mergeResult = "";
        if (is_string($controlValue)) {
            return $controlValue;
        }
        if (is_array($controlValue) && !empty($controlValue)) {
            $controlItemFlag = "";
            foreach ($controlValue as $key => $value) {
                if (strval($key) != '$$hashKey') {
                    if ($controlItemFlag == "" && $value) {
                        $controlItemFlag = $value;
                    }
                }
            }
            if ($controlItemFlag) {
                $mergeResult = json_encode($controlValue);
            }
        } else {
            $mergeResult = "";
        }
        return $mergeResult;
    }

    /**
     * 判断提交过程中的办理人是否在定义流程设置的办理人范围内
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [type]        $params [description]
     *
     * @return [type]              [description]
     */
    public function verifyUserWithinTransactorScope($params)
    {
        $runId               = $params['run_id'] ?? '';
        $flowProcess         = $params['flow_process'] ?? '';
        $nextFlowProcess     = $params['target_process_id'] ?? '';
        $processHostUser     = $params['process_host_user'] ?? '';
        $processTransactUser = $params['process_transact_user'] ?? '';
        $submitType = $params['submit_type'] ?? '';
        if (empty($runId) || empty($flowProcess) || empty($nextFlowProcess) || (empty($processHostUser) && empty($processTransactUser))) {
            return ['code' => ['0x000006', 'common']];
        }
        $getTransactUserScopeParams = [
            'flow_process'      => $flowProcess,
            'run_id'            => $runId,
            'target_process_id' => $nextFlowProcess,
            'submit_type' => $submitType
        ];

        $transactUserScope = app($this->flowService)->getFixedFlowTransactUser($getTransactUserScopeParams);

        if (empty($transactUserScope['scope']['user_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        if ($transactUserScope['scope']['user_id'] != 'ALL') {
            $transactUserScope        = $transactUserScope['scope']['user_id']->toArray();
            $processTransactUserArray = [];
            if (!empty($processTransactUser) && !empty(trim($processTransactUser, ","))) {
                $processTransactUserArray = explode(',', trim($processTransactUser, ","));
            }
            if (!empty($processHostUser)) {
                $processTransactUserArray[] = $processHostUser;
            }
            $checkHasNoPermissionUser = array_diff($processTransactUserArray, $transactUserScope);
            if (!empty($checkHasNoPermissionUser)) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        return true;
    }

    /**
     * 判断当前用户是否有当前节点的某个分发权限，转发、抄送、邮件外发权限
     *
     * @author miaochenchen
     *
     * @since 2019-03-13
     *
     * @param  [array]  $params           [判断当前流程的一些参数]
     * @param  [string] $type             [转发(process_forward)、抄送(process_copy)、邮件外发(flow_outmail)]
     *
     * @return [array or boolean]         [错误码]
     */
    public function verifyFlowRunDispensePermission($params, $type)
    {
        $currentUserId = $params['user_id'] ?? '';
        $runId         = $params['run_id'] ?? '';
        $processId     = $params['process_id'] ?? '';
        $pageFrom = $params['page_from'] ?? '';
        if (empty($currentUserId) || empty($runId)) {
            return ['code' => ['0x000006', 'common']];
        }
        $flowRunInfo = app($this->flowRunRepository)->getDetail($runId);
        if (empty($flowRunInfo->flow_id)) {
            return ['code' => ['0x000006', 'common']];
        }
        $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowRunInfo->flow_id);
        if (empty($flowTypeInfo->flow_type)) {
            return ['code' => ['0x000006', 'common']];
        }
        if ($flowTypeInfo->flow_type != '1') {
            // 判断是自由流程默认就有抄送按钮
            if ($type == 'process_copy') {
                return true;
            } else {
                return ['code' => ['0x000006', 'common']];
            }
        }
        if (empty($processId)) {
            return ['code' => ['0x000006', 'common']];
        }
        $currentUserJoinInfoParams = [
            'search'     => [
                'run_id'     => [$runId],
                'user_id'    => [$currentUserId],
                'process_id' => [$processId]
            ],
            'order_by'   => [ 'user_run_type' => 'asc','process_id' => 'desc'],
            'returntype' => 'first',
        ];
        // 20200911,zyx,针对办理页面过来的抄送，取给定process_id的数据
        if (($pageFrom == "handle") && $processId) {
            $currentUserJoinInfoParams['search']['process_id'] = [$processId];
        }
        // 查询当前用户参与过的最新的步骤信息
        $currentUserLatestJoinInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($currentUserJoinInfoParams);
        if (empty($currentUserLatestJoinInfo->flow_process)) {
            return ['code' => ['0x000006', 'common']];
        }
        // 办理人可能存在多个并发分支上，用最大参与步骤ID来判断权限这个就不可以了，先注释，前面查最大参与步骤时加上process_id查询条件
        // if (!empty($processId) && $currentUserLatestJoinInfo->process_id != $processId) {
        //     // 如果参数里传的步骤ID不等于用户参与的最新的步骤ID，无权限
        //     return ['code' => ['0x000006', 'common']];
        // }
        $currentUserHostFlag = $currentUserLatestJoinInfo->host_flag ?? 0;
        $lastUserJoinInfoParams = [
            'search'     => [
                'run_id'  => [$runId],
                'user_id' => [$currentUserId],
                'process_id' => [$currentUserLatestJoinInfo->process_id],
                'host_flag' =>[1]
            ],
            'returntype' => 'count',
        ];
        //判断这个人是否在该节点下是否是多次办理，取最大办理人
        $haslastUser = app($this->flowRunProcessRepository)->getFlowRunProcessList($lastUserJoinInfoParams);
        if ( $haslastUser > 0 ) {
             $currentUserHostFlag = 1;
        }
        $flowProcessInfo     = $this->getProcessInfo($currentUserLatestJoinInfo->flow_process);// 缓存数据
        if (empty($flowProcessInfo) || empty($flowProcessInfo->{$type})) {
            return ['code' => ['0x000006', 'common']];
        }
        if ($flowProcessInfo->{$type} == '1' && $currentUserHostFlag < 1) {
            // 如果是第四种办理方式，则默认有权限,没产生主办人之前
            $opFlagIsExistResult = app($this->flowRunService)->opFlagIsExist(["run_id" => $runId, "process_id" =>$processId]);

            if ($flowProcessInfo->process_transact_type != '3' && !$opFlagIsExistResult) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        //如果固定流程已结束，判断能否转发
        if ($flowTypeInfo->flow_type == '1' && $type == 'process_forward' &&  $flowRunInfo['current_step'] == 0 ) {
                $flowOthersInfo = app($this->flowOthersRepository)->getDetail($flowRunInfo->flow_id);
                if ($flowOthersInfo->forward_after_flow_end == 0) {
                    return ['code' => ['0x000006', 'common']];
                }
        }
        return true;
    }

    /**
     * [controller里面，验证流程办理/查看权限的入口函数，返回布尔类型的值，true 有权限，false 没权限]
     * [区别于flowRunPermissionValidation函数（在service里调用的，在函数内判断权限并返回权限标识数字的函数）]
     *
     * @author dingpeng
     *
     * @param   [type]  $params  [type 请求的验证权限的类型(handle view);run_id;user_id;]
     *
     * @return  [type]           [true 有权限，false 没权限]
     */
    public function verifyFlowHandleViewPermission($params)
    {
        // 请求的验证权限的类型
        $verifyType = isset($params["type"]) ? $params["type"] : "";
        // 必填
        $runId = $params["run_id"];
        // 必填
        $userId = $params["user_id"];
        $requestInfo = isset($params['request']) ?$params['request'] : [];
        $requestInfo['run_id'] = $runId;
        $requestInfo['user_id'] =  $userId;
        $requestInfo['currentUser'] =  $userId;
        $maxProcessId = isset($params["max_process_id"]) ? $params["max_process_id"] : "";
        if (!$runId || !$userId) {
            return false;
        }
        $flowRunInfo = $params['flow_run_info'] ?? $this->getPermissionFlowRunInfo($runId);
        if (empty($flowRunInfo)) {
            return false;
        }
        $flowId  = isset($flowRunInfo["flow_id"]) ? $flowRunInfo["flow_id"] : "";
        $creator = $flowRunInfo['creator'] ?? '';
        if ($verifyType == "handle") {
            if (empty($flowRunInfo['current_step'])) {
                // 结束的流程主办人没有办理权限
                $currentUserJoinInfoParams = [
                    'search'     => [
                        'run_id'  => [$runId],
                        'user_id' => [$userId],
                    ],
                    'order_by'   => ['process_id' => 'desc'],
                    'returntype' => 'first',
                    'select_user' =>false,
                ];
                $currentUserLatestJoinInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($currentUserJoinInfoParams);
                if (!empty($currentUserLatestJoinInfo->system_submit)) {
                    return true;
                }
                if (!isset($currentUserLatestJoinInfo->host_flag) || $currentUserLatestJoinInfo->host_flag == '1') {
                    // 判断是否有未提交的经办人身份的步骤
                    $currentUserJoinInfoParams = [
                        'search'     => [
                            'run_id'        => [$runId],
                            'user_id'       => [$userId],
                            'host_flag'     => [0]
                        ],
                        'order_by'   => ['process_id' => 'desc'],
                        'whereRaw' => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"],
                        'returntype' => 'first',
                        'select_user' =>false,
                    ];
                    $currentUserLatestJoinInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($currentUserJoinInfoParams);
                    if ($currentUserLatestJoinInfo && $currentUserLatestJoinInfo->saveform_time && !$currentUserLatestJoinInfo->system_submit) {
                        return false;
                    }
                }
            }
            $flowTypeInfo = $params['flow_type_info'] ?? app($this->flowTypeRepository)->getDetail($flowId, false, ['flow_type', 'handle_way']);
            $flowType  = $flowTypeInfo->flow_type ?? 0;
            $handleWay = $flowTypeInfo->handleWay ?? 0;
            if ($flowType == 2  && $handleWay ==3 && !empty($maxProcessId)) {
                $runInfo =app($this->flowRunRepository)->getDetail( $runId );
                if (!empty($runInfo->max_process_id)  && $runInfo->max_process_id > $maxProcessId ) {
                    return false;
                }
            }
            // 判断是否有未提交的经办人身份的步骤-系统自动提交
            $currentUserJoinInfoParams = [
                'search'     => [
                    'run_id'        => [$runId],
                    'user_id'       => [$userId],
                    'host_flag'     => [0],
                    'system_submit' => [1]
                ],
                'order_by'   => ['process_id' => 'desc'],
                'returntype' => 'first',
            ];
            $currentUserLatestJoinInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($currentUserJoinInfoParams);
            if ($currentUserLatestJoinInfo) {
                return true;
            }
            // 验证flowRunStep，办理人
            // 验证办理权限的时候， flowRunStep 要验证 user_run_type = 1
            if ($flowRunProcessCount = $this->getPermissionFlowRunStepInfo($runId, $userId, "", "1")) {
                return true;
            }
            // 验证是否有监控流转权限(监控提交菜单获取流出节点和节点办理人权限验证)
            $allowTurnBack = $this->getMonitorAllowTypePermission($userId, $flowId, $creator, ['allow_turn_back', 'allow_end']);
            if ($allowTurnBack) {
                return true;
            }
        } else if ($verifyType == "view") {
            // 查看的权限范围要大于办理，所以办理里面判断了的权限也要在查看里判断
            if ($userId == "admin") {
                return true;
            }
            // 验证创建人
            if ($creator == $userId) {
                return true;
            }
            // 验证lowRunProcess，办理人
            // 验证查看权限的时候， flowRunProcess有数据就行
            if ($flowRunProcessCount = $this->getPermissionFlowRunProcessInfo($runId, $userId, "")) {
                return true;
            }
            // 非admin的情况下，验证是否有监控查看权限
            $allowView = $this->getMonitorAllowTypePermission($userId, $flowId, $creator, ['allow_view', 'allow_turn_back']);
            if ($allowView) {
                return true;
            }
            // 验证 flow_run_process 表的委托人
            if ($flowRunProcessCount = $this->getPermissionFlowRunProcessAgencyInfo($runId, $userId)) {
                return true;
            }
            // 验证流程抄送，按抄送步骤倒序
            if ($flowCopyInfo = $this->getPermissionFlowCopyInfo($runId, $userId)) {
                return true;
            }
            // 验证关联流程权限
            if ($this->relationProcessViewPermission($requestInfo)) {
                return true;
            }
            // 验证父子流程权限
            if ($this->getFlowAndSonViewPermission($requestInfo)) {
                return true;
            }
            // 验证签办反馈权限
            if ($this->getFlowFeedbackPermission($requestInfo)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 功能函数，验证流程查看权限，办理/查看页面等有runid的情况下用到
     * 9.0函数 run_role (general/workflow/prcs_role.php)
     * 返回值：
     * 空：没有权限
     * 0：流程不存在
     * 1：是办理人
     * 2：是流程监控人员且当前流程启用监控且开启监控查看权限
     * 3：通过 flow_run 表 VIEW_USER 字段获得的查看权限。（注：子流程查看权限，现在没有子流程，所以没有这种返回值）
     * 4：委托人
     * 5：被抄送人
     * 6：创建人
     * 7：admin
     * @author dingpeng
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function flowRunPermissionValidation($param , $requestInfo = [])
    {
        // 菜单权限已经放到外面调用地方验证!
        // $userMenu = $param["user_menu"];
        // 必填
        $runId = $param["run_id"];
        // 必填
        $userId   = $param["user_id"];
        $userInfo = isset($param["user_info"]) ? $param["user_info"] : [];
        // 步骤参数
        $processId      = isset($param["process_id"]) ? $param["process_id"] : false;
        $flowId         = isset($param["flow_id"]) ? $param["flow_id"] : false;
        $permissionInfo = ["permissionNumber" => "", "permissionData" => []];
        // 验证是否存在
        if ($flowRunInfo = !empty($param['flow_run_info']) ? $param['flow_run_info']->toArray() : $this->getPermissionFlowRunInfo($runId)) {
            // 停用且隐藏则没有权限
            $is_effect = $flowRunInfo["is_effect"] ?? '';
            if ( $is_effect != 1) {
                $permissionInfo["permissionNumber"] = "";
                return $permissionInfo;
            }
            // 验证admin
            if ($userId && $userId == "admin") {
                $permissionInfo["permissionNumber"] = 7;
                return $permissionInfo;
            }
            // 验证创建人
            $creator = $flowRunInfo["creator"] ?? '';
            if ($creator == $userId) {
                $permissionInfo["permissionNumber"] = 6;
                return $permissionInfo;
            }
            // 验证flowRunProcess，办理人
            if ($flowRunProcessCount = $this->getPermissionFlowRunProcessInfo($runId, $userId, $processId)) {
                $permissionInfo["permissionNumber"] = 1;
                return $permissionInfo;
            }
            // 非admin的情况下，验证是否有监控查看权限
            // 20190315 不再在flowService里获取参数 allow_view 了，从此Service的子函数里获取
            $allowView = $this->getMonitorAllowTypePermission($userId, $flowId, $creator, 'allow_view');
            if ($allowView) {
                $permissionInfo["permissionNumber"] = 2;
                return $permissionInfo;
            }
            // 验证 flow_run_process 表的委托人
            if ($flowRunProcessCount = $this->getPermissionFlowRunProcessAgencyInfo($runId, $userId)) {
                $permissionInfo["permissionNumber"] = 4;
                return $permissionInfo;
            }
            // 验证流程抄送，按抄送步骤倒序
            if ($flowCopyInfo = $this->getPermissionFlowCopyInfo($runId, $userId)) {
                $permissionInfo = $flowCopyInfo;
                return $permissionInfo;
            }
            // 验证关联流程权限
            if ($this->relationProcessViewPermission($requestInfo)) {
                $permissionInfo["permissionNumber"] = 3;
                return $permissionInfo;
            }
            // 验证父子流程权限
            if ($this->getFlowAndSonViewPermission($param)) {
                $permissionInfo["permissionNumber"] = 3;
                return $permissionInfo;
            }
            // 验证签办反馈权限
            if ($this->getFlowFeedbackPermission($param)) {
                $permissionInfo["permissionNumber"] = 3;
                return $permissionInfo;
            }

        } else {
            // 流程不存在
            $permissionInfo["permissionNumber"] = 0;
            return $permissionInfo;
        }
        // 没有权限
        $permissionInfo["permissionNumber"] = "";
        return $permissionInfo;
    }

    /**
     * 某用户的监控权限判断
     * 20190315由flowService挪到这里
     * **需要自己判断非admin
     * @author dingpeng
     * @param  [type] $userId        [当前用户ID]
     * @param  [type] $flowId        [定义流程ID]
     * @param  [type] $creator       [流程创建人ID]
     * @param  [type] $allowType     [查看监控权限类型, 如果要判断多个权限只要有一个存在即可的就传数组]
     * @return [type]                [description]
     */
    public function getMonitorAllowTypePermission($userId, $flowId, $creator, $allowType)
    {
        $userInfo = [];
        if (isset($userId)) {
            $userInfo = app($this->userService)->getUserDeptIdAndRoleIdByUserId($userId);
            if (!empty($userInfo) && isset($userInfo['dept_id']) && isset($userInfo['role_id'])) {
                $userInfo['role_id'] = explode(',', $userInfo['role_id']);
            } else {
                return false;
            }
            $userInfo['user_id'] = $userId;
        }
        $monitorAllowTypePermission = false;
        $monitorRulesParams         = app($this->flowService)->getMonitorParamsByUserInfo($userInfo, $flowId);
        if (!empty($creator) && isset($monitorRulesParams['monitor_rules'][$flowId]) && !empty($monitorRulesParams['monitor_rules'][$flowId])) {
            foreach ($monitorRulesParams['monitor_rules'][$flowId] as $ruleKey => $ruleValue) {
                if (isset($ruleValue['user_id']) && !empty($ruleValue['user_id'])
                    && ($ruleValue['user_id'] == 'all' || (in_array($creator, $ruleValue['user_id'])))) {
                    if (is_array($allowType)) {
                        foreach ($allowType as $allowTypeKey => $allowTypeValue) {
                            if (isset($ruleValue[$allowTypeValue]) && $ruleValue[$allowTypeValue] == '1') {
                                $monitorAllowTypePermission = true;
                            }
                        }
                    } else {
                        if (isset($ruleValue[$allowType]) && $ruleValue[$allowType] == '1') {
                            $monitorAllowTypePermission = true;
                        }
                    }
                }
            }
        }
        return $monitorAllowTypePermission;
    }

    /**
     * 封装获取flow_run表数据的函数，指定列并处理数据，以在上面flowRunPermissionValidation函数简单使用flow_run数据
     * @author dingpeng
     * @param  [type] $runId [description]
     * @return [type]        [description]
     */
    public function getPermissionFlowRunInfo($runId)
    {
        // $params      = ['search' => ['run_id' => [$runId]], 'fields' => ['flow_id', 'run_id', 'creator', 'current_step'], 'returntype' => 'first' ,'select_user' =>false];
        $params      = ['search' => ['run_id' => [$runId]], 'fields' => ['flow_id', 'run_id', 'creator', 'current_step' , 'is_effect'], 'returntype' => 'first'];
        $flowRunInfo = [];
        if ($runId && $runObject = app($this->flowRunRepository)->getFlowRunList($params)) {
            if ($runObject) {
                $flowRunInfo = $runObject->toArray();
            }
        }
        return $flowRunInfo;
    }

    /**
     * 封装获取flow_run_process表数据的函数，指定列并处理数据，以在上面flowRunPermissionValidation函数简单使用flow_run_process数据
     * 验证flowRunProcess，办理人
     * @author dingpeng
     * @param  [type] $runId     [description]
     * @param  [type] $userId    [description]
     * @param  [type] $processId [description]
     * @return [type]            [description]
     */
    public function getPermissionFlowRunProcessInfo($runId, $userId, $processId)
    {
        $flowRunProcessParam = ["search" => ["run_id" => [$runId], "user_id" => [$userId]]];
        if (is_array($runId)) {
            $flowRunProcessParam['search']['run_id'] = [$runId, 'in'];
        }
        if ($processId > 0) {
            $flowRunProcessParam["search"]["process_id"] = [$processId];
        }
        $flowRunProcessCount = 0;
        if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessParam)) {
            if ($flowRunProcessObject->count()) {
                $flowRunProcessCount = $flowRunProcessObject->count();
            }
        }
        return $flowRunProcessCount;
    }

    /**
     * 封装获取flow_run_step表数据的函数，指定列并处理数据，以在上面flowRunPermissionValidation函数简单使用flow_run_step数据
     * 验证flowRunStep，办理人
     * @author dingpeng
     * @param  [type] $runId     [description]
     * @param  [type] $userId    [description]
     * @param  [type] $processId [description]
     * @return [type]            [description]
     */
    public function getPermissionFlowRunStepInfo($runId, $userId, $processId, $userRunType = false)
    {
        $flowRunStepParam = ["search" => ["run_id" => [$runId], "user_id" => [$userId]]];
        if ($processId > 0) {
            $flowRunStepParam["search"]["process_id"] = [$processId];
        }
        if ($userRunType !== false) {
            $flowRunStepParam["search"]["user_run_type"] = [$userRunType];
        }
        $flowRunStepCount = 0;
        if ($flowRunStepObject = app($this->flowRunProcessRepository)->getFlowRunProcessList(array_merge($flowRunStepParam, ['returntype' => 'count']))) {
            $flowRunStepCount = $flowRunStepObject ? $flowRunStepObject : 0;
        }
        return $flowRunStepCount;
    }

    /**
     * 封装获取flow_run_process表数据的函数，指定列并处理数据，以在上面flowRunPermissionValidation函数简单使用flow_run_process数据
     * 验证 flow_run_process 表的委托人
     * @author dingpeng
     * @param  [type] $runId     [description]
     * @param  [type] $userId    [description]
     * @return [type]            [description]
     */
    public function getPermissionFlowRunProcessAgencyInfo($runId, $userId)
    {
        $flowRunProcessAgentParam = [
            "run_id"           => $runId,
            "search"           => ["by_agent_id" => [null, '!=']],
            "agencyDetailInfo" => true,
            "returntype"       => "array",
        ];
        if (is_array($runId)) {
            $flowRunProcessAgentParam['run_id'] = $runId;
        }
        $flowRunProcessCount      = 0;
        $flowRunProcessAgentArray = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessAgentParam);
        if (count($flowRunProcessAgentArray) > 0) {
            foreach ($flowRunProcessAgentArray as $flowRunProcessValue) {
                // 验证是否最终委托人
                if ($flowRunProcessValue['by_agent_id'] == $userId) {
                    $flowRunProcessCount = 1;
                    break;
                }
                // 验证是否中间委托人
                $agencyDetail = $flowRunProcessValue['flow_run_process_has_many_agency_detail'] ?? [];
                if (count($agencyDetail) > 0) {
                    foreach ($agencyDetail as $agencyDetailValue) {
                        if ($agencyDetailValue['by_agency_id'] == $userId) {
                            $flowRunProcessCount = 1;
                            break;
                        }
                    }
                    // 已经确定是中间委托人
                    if ($flowRunProcessCount != 0) {
                        break;
                    }
                }
            }
        }
        return $flowRunProcessCount;
    }

    /**
     * 封装获取flow_copy表数据的函数，指定列并处理数据，以在上面flowRunPermissionValidation函数简单使用flow_copy数据
     * 验证流程抄送，按抄送步骤倒序
     * @author dingpeng
     * @param  [type] $runId     [description]
     * @param  [type] $userId    [description]
     * @return [type]            [description]
     */
    public function getPermissionFlowCopyInfo($runId, $userId)
    {
        $flowCopyParam = [
            "returntype" => "object",
            "search"     => ["by_user_id" => [$userId], "run_id" => [$runId]],
            "order_by"   => ["process_id" => "desc"],
        ];
        if (is_array($runId)) {
            $flowCopyParam['search']['run_id'] = [$runId, 'in'];
        }
        $permissionInfo = [];
        if ($flowCopyObject = app($this->flowCopyRepository)->getFlowCopyList($flowCopyParam)) {
            if ($flowCopyObject->count()) {
                $permissionInfo["permissionNumber"] = 5;
                $permissionInfo["permissionData"]   = $flowCopyObject->first()->toArray();
            }
        }
        return $permissionInfo;
    }

    /**
     * 功能函数，验证是否有某个菜单的权限
     * 20190311-从flowRunService挪过来的
     * @author dingpeng
     * @param  [string] $targetMenu [需要被验证的菜单id]
     * @return [type]             [有一个菜单id有权限，就返回true]
     */
    public function verifyUserMenuPermission($targetMenu,$userInfo,$param)
    {
        if(isset($param['flow_export'])){
            return true;
        }
        $userId = $userInfo['user_id'] ?? 'admin';
        if (!is_array($targetMenu)) {
            $targetMenu = explode(',', $targetMenu);
        }
        $permissionMenus = $userInfo['menus'] ?? app($this->userMenuService)->getUserMenus($userId);
        if (isset($permissionMenus['menu'])) {
            if (!empty(array_intersect($targetMenu, $permissionMenus['menu']))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 验证某个用户，对某个flow_id，是否有新建权限
     * @author dingpeng
     * @param  [type] $params [flow_id user_info等信息(部门角色用户信息，从own里面获取)]
     * @return [type]         [description]
     */
    public function verifyFlowNewPermission($params)
    {
        $own    = isset($params["own"]) ? $params["own"] : [];
        $flowId = isset($params["flow_id"]) ? $params["flow_id"] : "";
        $roleId = (isset($own["role_id"]) && !empty($own["role_id"])) ? implode(",", $own["role_id"]) : "";
        $data   = [
            "dept_id" => isset($own["dept_id"]) ? $own["dept_id"] : "",
            "role_id" => $roleId,
            "user_id" => isset($own["user_id"]) ? $own["user_id"] : "",
            "search"  => ["flow_id" => [$flowId]],
        ];
        $permissionParam = $data;
        unset($permissionParam["search"]);
        $permissionParam["flow_id"]           = $flowId;
        $permissionParam["fixedFlowTypeInfo"] = app($this->flowService)->getFixedFlowTypeInfoByUserInfo($data);
        $permissionParam["returntype"]        = "count";
        $flowList                             = app($this->flowTypeRepository)->flowNewPermissionListRepository($permissionParam);
        return $flowList > 0;
    }

    /**
     * 判断流程类别权限
     *
     * @param  [string]             $sortId     [类别id]
     * @param  [array]              $own        [用户信息]
     * @param  [boolean]            $menu       [是否检查菜单项]
     * @param  [array]              $userMenu   [用户菜单]
     *
     * @return [boolean]                        [返回验证结果]
     */
    public function verifyFlowSortPermission($params)
    {
        $sortId = $params['sortId'] ?? '';
        $own    = $params['own'] ?? '';
        // 用户有权限的id集合
        $powerList = $this->getPermissionFlowSortList($own['user_id'],$own);
        // 是否在集合中
        if (in_array($sortId, $powerList)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断流程节点权限
     *
     * @param  [string]             $nodeId     [节点id]
     * @param  [array]              $own        [用户信息]
     *
     * @return [boolean]                        [返回验证结果]
     */
    public function verifyFlowNodePermission($nodeId, $own)
    {
        if ($detailResult = $this->getProcessInfo($nodeId)) {// 缓存数据
            return $this->verifyFlowSettingPermission($detailResult->flow_id, $own);
        } else {
            return false;
        }
    }

    /**
     * 判断流程出口条件权限
     *
     * @param  [string]             $termId     [termId]
     * @param  [array]              $own        [用户信息]
     *
     * @return [boolean]                        [返回验证结果]
     */
    public function verifyFlowTermPermission($termId, $own)
    {
        if ($detailResult = app($this->flowTermRepository)->getDetail($termId)) {
            return $this->verifyFlowSettingPermission($detailResult->flow_id, $own);
        } else {
            return false;
        }
    }

    /**
     * 判断定义流程操作权限，操作定义流程时，需要有对应流程分类的权限
     * @param  [type] $flowId  [需要被验证的flowId]
     * @param  [string] $own   [当前用户，所有的菜单，从外部传递]
     * @return [type]          [true|flase]
     */
    public function verifyFlowSettingPermission($flowId, $own)
    {
        if (isset($own['user_id']) && !empty($own['user_id'])) {
            $userId = $own['user_id'];
        } else {
            return false;
        }
        if (!$flowId) {
            return false;
        }
        // 获取流程所属分类
        $flowInfo = app($this->flowTypeRepository)->getDetail($flowId , false , ['flow_sort']);
        if ($flowInfo) {
            $sortId = $flowInfo->flow_sort;
        } else {
            return false;
        }
        // 用户有权限的typeId集合
        $powerList = $this->getPermissionFlowSortList($userId,$own);

        // 是否在集合中
        if (in_array($sortId, $powerList)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 判断定义表单子表单操作权限，操作定义表单时，需要有对应表单分类的权限
     * @param  [type] $formId  [需要被验证的formId]
     * @param  [string] $own   [当前用户，所有的菜单，从外部传递]
     * @return [type]          [true|flase]
     */
    public function verifyChildFormSettingPermission($formId, $own)
    {
        //获取子表单对应父表单
        $parentInfo = app($this->flowChildFormTypeRepository)->getDetail($formId);
        if ($parentInfo) {
            $parendId = $parentInfo->parent_id;
        } else {
            return false;
        }
        return $this->verifyFormSettingPermission($parendId, $own);
    }
    /**
     * 判断定义表单操作权限，操作定义表单时，需要有对应表单分类的权限
     * @param  [type] $formId  [需要被验证的formId]
     * @param  [string] $own   [当前用户，所有的菜单，从外部传递]
     * @param  [string] $type  [表单类型，parent=主表单，child=子表单]
     * @return [type]          [true|flase]
     */
    public function verifyFormSettingPermission($formId, $own, $type = "parent" , $needFormInfo = false)
    {
        if (isset($own['user_id']) && !empty($own['user_id'])) {
            $userId = $own['user_id'];
        } else {
            return false;
        }
        if (!$formId) {
            return false;
        }

        // 如果是子表单的，获取主表单ID来判断分类权限
        if ($type == 'child') {
            $flowFormInfo = app($this->flowChildFormTypeRepository)->getDetail($formId);
            $formId = $flowFormInfo->parent_id ?? 0;
            if (!$formId) {
                return false;
            }
        }
        // 获取表单所属分类
        $flowFormInfo = app($this->flowFormTypeRepository)->getDetail($formId);
        if ($flowFormInfo) {
            $sortId = $flowFormInfo->form_sort;
            if ($sortId == 0 && $needFormInfo) {
                return $flowFormInfo;
            } else if ($sortId == 0) {
                 return true;
            }
        } else {
            return false;
        }
        // 用户有权限的typeId集合
        $powerList = $this->getPermissionFlowFormSortList($userId,$own);//缓存

        // 是否在集合中
        if (in_array($sortId, $powerList)) {
            if ($needFormInfo) {
                return $flowFormInfo;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * [验证签办反馈的编辑删除权限]
     *
     * @author dingpeng
     *
     * @param   [type]  $params  [feedback_id;user_id]
     *
     * @return  [type]           [1:可以编辑删除 0:不可以编辑删除]
     */
    public function verifyFlowFeedbackPermission($params)
    {
        $canEditFeedBack = "";
        $feedbackId      = isset($params["feedback_id"]) ? $params["feedback_id"] : "";
        $currentUserId   = isset($params["user_id"]) ? $params["user_id"] : "";
        if ($feedbackId && $feedbackObject = app($this->flowRunFeedbackRepository)->getFlowFeedBackDetail($feedbackId, [])) {
            $feedbackInfo      = $feedbackObject->toArray();
            $runId             = isset($feedbackInfo["run_id"]) ? $feedbackInfo["run_id"] : "";
            $feedbackUserId    = isset($feedbackInfo["user_id"]) ? $feedbackInfo["user_id"] : "";
            $feedbackProcessId = isset($feedbackInfo["process_id"]) ? $feedbackInfo["process_id"] : "";
            $feedbackEditTime  = isset($feedbackInfo["edit_time"]) ? $feedbackInfo["edit_time"] : "";
            if ($runId && $runObject = app($this->flowRunRepository)->getDetail($runId)) {
                $maxProcessId = isset($runObject->max_process_id) ? $runObject->max_process_id : "";
                // 判断是否已经有人查看过，参考9.0的逻辑
                if ($feedbackUserId == $currentUserId && $feedbackProcessId == $maxProcessId) {
                    $param = [
                        "run_id"     => $runId,
                        "process_id" => $feedbackProcessId,
                        "edit_time"  => $feedbackEditTime,
                        "user_id"    => $currentUserId,
                    ];
                    $canEditFeedBack = app($this->flowRunService)->verifyFlowHasOtherPersonVisited($param);
                    if ($canEditFeedBack === false) {
                        $canEditFeedBack = "1";
                    } else {
                        $canEditFeedBack = "";
                    }
                }
            }
        }
        return $canEditFeedBack;
    }
    /**
     * 判断节点设置操作权限，操作定义流程节点时，需要有对应流程分类的权限
     * @param  [type] $nodeId  [需要被验证的formId]
     * @param  [string] $own   [当前用户，所有的菜单，从外部传递]
     * @return [type]          [true|flase]
     */
    public function verifyNodeSettingPermission($nodeId, $own)
    {
        // 获取节点所属流程
        $flowProcessInfo = $this->getProcessInfo($nodeId);// 缓存数据
        if ($flowProcessInfo) {
            $flowId = $flowProcessInfo->flow_id;
            return $this->verifyFlowSettingPermission($flowId, $own);
        } else {
            return false;
        }
    }
    /**
     * 获取节点信息（缓存）
     * @param [type] $nodeId [节点id
     * @return [type] [节点相关关信息 flow_process表数据]
     */
    public function getProcessInfo($nodeId)
    {
        $nodeInfo = app($this->flowProcessRepository)->getDetail($nodeId);
        //用到此处缓存的地方有
        // 【提交流程】 固定&自由流程，提交页面提交flow/run/flow-turning
        // 【流程运行】 转发flow/run/flow-run-forward/{run_id}
        // 【流程邮件外发】 流程邮件外发实现flow/flow-out-mail
        // 【流程抄送】 新建抄送 flow/flow-copy
        // 【节点设置】 删除节点flow/flow-define/flow-node/{node_id}
        // 【节点设置】 编辑节点信息flow/flow-define/flow-node/{node_id}
        // 【节点设置】 编辑办理人员[默认办理人一起保存]flow/flow-define/flow-node-transact-user/{node_id}
        // 【节点设置】 编辑字段控制flow/flow-define/flow-node-field-control/{node_id}
        // 【节点设置】 编辑路径设置flow/flow-define/flow-node-path-set/{node_id}
        // 【节点设置】 编辑出口条件的关联关系flow/flow-define/flow-node-outlet-relation/{node_id}
        // 【节点设置】 编辑子流程flow/flow-define/flow-node-subflow/{node_id}
        // 【节点设置】 编辑抄送人员flow/flow-define/flow-node-copy-user/{node_id}
        // 【办理人员】 经办人员/部门/角色的值的变化，会触发flow/flow-define/verify-default-user-include
        // 【流程定义】 节点设置-流程图节点信息更新flow/flow-define/flow-chart-node/{id}
        // 【流程定义】 节点设置-流程图节点删除 --验证节点编辑权限flow/flow-define/flow-chart-node/{id}
        // 【流程定义】 节点设置-流程图节点清除连线flow/flow-define/flow-chart-node-delete-processto
        // 【流程定义】 节点设置-流程外发保存数据flow/flow-define/flow-outsend-save-data
        // 【流程定义】 节点设置-流程数据验证保存数据flow/flow-define/flow-validate-save-data
        // 【运行流程】 流程数据验证获取数据flow/run/flow-validate-get-data
        //
        //此处缓存需要重置的地方有
        //1、编辑流程基本信息的本体函数modifyFlowDefineBasicInfoRealize
        //2、删除定义流程removeFlowDefineBasicInfo
        //3、固定流程统一设置催促时间unifiedSetPresstime
        //4、更新节点排序updateNodeSort
        //5、批量保存流程节点信息batchSaveFlowNodeService
        //6、删除节点removeFlowNode
        //7、编辑节点信息modifyFlowNode
        //8、编辑办理人员[默认办理人一起保存]modifyFlowNodeTransactUser
        //9、编辑路径设置modifyFlowNodePathSet
        //10、编辑子流程modifyFlowNodeSubflow
        //11、编辑抄送人员modifyFlowNodeCopyUser
        //12、验证人员是否在范围内verifyDefaultUserInclude
        //13、流程图节点信息更新chartEditNode
        //14、流程图节点删除chartDeleteNode
        //15、流程图节点清除所有连线chartDeleteAllNodeProcessTo
        //16、流程图节点清除连线chartDeleteNodeProcessTo
        //17、流程外发保存数据flowOutsendSaveData
        //18、替换流程设置办理人离职人员replaceHandleInfo
        //19、复制数据验证copyFormValidate
        //20、流程数据验证保存数据flowValidateSaveData
        //
        // 问题有点多，暂不使用
        // if(Redis::exists('flow_process_info_'.$nodeId)) {
        //     $nodeInfo = unserialize(Redis::get('flow_process_info_'.$nodeId));
        // }else{
        //     $nodeInfo = app($this->flowProcessRepository)->getDetail($nodeId);
        //     Redis::set('flow_process_info_'.$nodeId,serialize($nodeInfo));
        // }
        return $nodeInfo;
    }
    /**
     * 获取表单分类权限信息（缓存）
     * @param [type] $userId 用户id
     * @return [type] [有权限的类别id]
     */
    public function getPermissionFlowFormSortList($userId,$userInfo)
    {
        //用到此处缓存的地方有
        //【定义流程】 【流程表单】 删除流程表单flow/flow-define/flow-form/{id}
        //【定义流程】 【表单版本】 获取表单版本列表flow/flow-form/version/{id}
        //【定义流程】 【表单版本】 获取表单版本详情flow/flow-form/version/info/{id}
        //【流程表单】 表单简易版标准版切换flow/flow-define/form_type_conversion
        //【流程表单】 表单简易版标准版切换获取表单控件列表flow/flow-define/form_type_conversion_get_control/{formId}
        //【流程表单】 表单简易版标准版切换获取表单控件列表flow/flow-define/form_type_conversion_get_control_for_complex/{formId}
        //【流程表单】 子表单-生成子表单flow/flow-form/child_form/create_child
        //【流程表单】 子表单-子表单列表flow/flow-form/child_form/child_list/{parent_id}
        //【流程定义】 导入表单flow/flow-define/flow-form-import/{form_id}
        //【流程表单】 子表单-获取子表单详情flow/flow-form/child_form/get_detail/{form_id}
        //【流程表单】 子表单-删除单个子表单flow/flow-form/child_form/{form_id}
        //【流程表单】 子表单-编辑单个子表单flow/flow-form/child_form/{form_id}
        //
        // 需要清除此处缓存的地方有
        // 1、编辑表单分类editFlowFormSort
        // 2、删除表单分类deleteFlowFormSort
        // 3、用户部门角色变更
        // 4、新建表单分类createFlowFormSort
        if(Redis::exists('flow_form_type_power_list_'.$userId)) {
            $powerList = unserialize(Redis::get('flow_form_type_power_list_'.$userId));
        }else{
            // 用户有权限的typeId集合
            $powerList = app($this->flowService)->getPermissionFlowFormSortList($userInfo);
            if ($powerList) {
                $powerList = $powerList->pluck('id')->toArray();
            } else {
                $powerList = [];
            }
            Redis::set('flow_form_type_power_list_'.$userId,serialize($powerList));
        }
        return $powerList;
    }
    /**
     * 获取流程分类权限信息（缓存）
     * @param [type] $userId 用户id
     * @return [type] [有权限的类别id]
     */
    public function getPermissionFlowSortList($userId,$userInfo)
    {
        //用到此处缓存的地方有
        //1、【定义流程】 新建固定or自由流程基本信息flow/flow-define/flow-define-basic-info
        //2、【定义流程】 编辑固定or自由流程基本信息flow/flow-define/flow-define-basic-info/{flow_id}
        //3、【定义流程】 删除固定or自由流程基本信息flow/flow-define/flow-define-basic-info/{flow_id}
        //4、【定义流程】 固定流程统一设置催促时间flow/flow-define/flow-unified-set-press-time/{flow_id}
        //5、【定义流程】 编辑监控人员flow/flow-define/flow-monitor/{flow_id}
        //6、【定义流程】 编辑其他设置flow/flow-define/flow-other-info/{flow_id}
        //7、【定义流程】 【节点设置】 批量保存流程节点信息flow/flow-define/flow-node/node-list/{flow_id}
        //8、【定义流程】 【节点设置】 新建节点flow/flow-define/flow-node
        //9、【定义流程】 【节点设置】 字段控制，解析表单控件flow/flow-define/flow-node-field-control-filter
        //10、【流程定义】 获取表单字段详情flow/flow-define/flow-form-flies/{flow_id}
        //11、【流程定义】 获取表单字段详情-用于数据外发flow/flow-define/flow-form-flies-for-outsend/{id}
        //12、【定义流程】 【表单模板】 定义流程，获取各种表单模板规则的列表flow/flow-define/flow-template/rule-list
        //13、【定义流程】 获取自由流程必填设置flow/flow-define/free_flow_required/{flowId}
        //14、【定义流程】 获取自由流程必填字段flow/flow-define/free_flow_required_info/{flowId}
        //15、【定义流程】 编辑自由流程必填设置flow/flow-define/free_flow_required/{flowId}
        //16、【流程表单】 子表单-子表单列表flow/flow-form/child_form/child_list_by_flow/{flow_id}
        //17、【流程设置】获取表单数据模板flow/run/data-template
        //18、【流程设置】设置表单数据模板flow/flow-form/data-template
        //19、【定义流程】 更新节点排序flow/flow-define/update_sort/{flowId}
        //20、【流程定义】 节点设置-流程图节点新建flow/flow-define/flow-chart-node
        //21、【流程定义】 节点设置-流程图节点清除所有连线flow/flow-define/flow-chart-node-delete-all-processto/{id}
        //22、【流程定义】 节点设置-流程图节点保存出口条件flow/flow-define/flow-chart-node-update-condition
        //23、【流程定义】 编辑流水号规则flow/flow-define/update_flow_sequence_rule/{flow_id}
        //24、【流程定义】 流程图模式下获取当前流出节点列表flow/flow-define/get_flow_out_current_node_list/{flow_id}
        //25、【流程定义】 获取流程所有节点设置办理人离职人员信息flow/flow-define/quit-user-replace/{flow_id}
        //26、【流程定义】 替换流程设置办理人离职人员flow/flow-define/handle-user-replace/{flow_id}
        //27、【定义流程】 【表单模板】 定义流程，保存各种表单模板规则flow/flow-define/flow-template/rule-list
        //28、【流程定义】 获取流程所有节点设置办理人列表flow/flow-define/handle-user-replace/user/{flow_id}
        //29、【流程定义】 获取流程所有节点设置办理角色列表flow/flow-define/handle-user-replace/role/{flow_id}
        //30、【流程定义】 获取流程所有节点设置办理部门列表flow/flow-define/handle-user-replace/dept/{flow_id}
        //
        // 需要清除此处缓存的地方有
        // 1、编辑流程分类editFlowSort
        // 2、删除流程分类deleteFlowSort
        // 3、用户部门角色变更
        // 4、新建流程分类createFlowSort
        if(Redis::exists('flow_sort_power_list_'.$userId)) {
            $powerList = unserialize(Redis::get('flow_sort_power_list_'.$userId));
        }else{
            // 用户有权限的typeId集合
            $powerList = app($this->flowService)->getPermissionFlowSortList($userInfo);
            if ($powerList) {
                $powerList = $powerList->pluck('id')->toArray();
                //  增加未分类
                $powerList = array_merge($powerList, [0]);
            } else {
                $powerList = [0];
            }
            Redis::set('flow_sort_power_list_'.$userId,serialize($powerList));
        }
        return $powerList;
    }

    /**
     * 关联流程和父子流程查看权限
     * @param [type] $run_ids  关联的运行流程ids
     * @param [type] $user_id  当前用户id
     * @return [type] [有权限的类别id]
     */
    public function verifyFlowViewPermission($params)
    {
        // 必填
        $runIds = $params["run_ids"];
        // 必填
        $userId = $params["user_id"];
        if (empty($runIds)) {
            return false;
        }
        if (!$userId) {
            return false;
        }
        // 验证lowRunProcess，办理人
        // 验证查看权限的时候， flowRunProcess有数据就行
        if ($flowRunProcessCount = $this->getPermissionFlowRunProcessInfo($runIds, $userId, "")) {
            return true;
        }
        // // 验证 flow_run_process 表的委托人
        if ($flowRunProcessCount = $this->getPermissionFlowRunProcessAgencyInfo($runIds, $userId)) {
            return true;
        }
        // // 验证流程抄送，按抄送步骤倒序
        if ($flowCopyInfo = $this->getPermissionFlowCopyInfo($runIds, $userId)) {
            return true;
        }
        return false;
    }

    /**
     * 关联流程查询查看权限判断
     * @param [type] $relation_type  关联类型
     * @param [type] $currentUser  当前用户id
     * @param [type] $relation_rf_id  关联流程id和表单ID
    * @param [type]  $relation_control  关联的控件
     * @return [type] [有权限的类别id]
     */
    public function relationProcessViewPermission ($param) {
        $relationType  = isset($param['relation_type']) ? $param['relation_type'] : '';
        if (empty( $relationType)) {
                return false;
        }
         $userId = isset($param["currentUser"]) ? $param["currentUser"] : '';
        // 流程选择器跳转过来
        if ($relationType == 'selector') {
            $relationControl  = isset($param['relation_control']) ? $param['relation_control'] : '';
            $relationRunId  = isset($param['relation_rf_id']) ? $param['relation_rf_id'] : '';
            if (empty($relationControl) || empty($relationRunId) ){
                return false;
            }
            $info  = explode(',', $relationRunId);
            $controlArr = explode('_', $relationControl);
            $tableName = '';
            if (count($info) == 2 && !empty($info[0])) {
                if (count($controlArr) == 2) {
                 $tableName = 'zzzz_flow_data_'.$info[1];
                } else if (count($controlArr) == 3) {
                  $tableName = 'zzzz_flow_data_'.$info[1]."_".$controlArr[1];
                }
                // 判断数据表中是否有对应数据
                if (Schema::hasTable($tableName)) {
                   $count = DB::table($tableName)->where('run_id', $info[0])->whereRaw('find_in_set(?, '.$relationControl.')' , [$param['run_id']])->count();
                   if ( $count) {
                    // 判断有无查看权限
                    return $this->verifyFlowViewPermission(['run_ids' =>[$info[0]] ,'user_id' => $userId]);
                   }
                    return false;
                } else {
                    return false;
                }
            } else if (count($info) == 2 && empty($info[0])) {
                return true;
            }


        } else if ($relationType == 'monitor') {
            // 流程监控多级查看子流程
            $relationRunId  = isset($param['relation_rf_id']) ? $param['relation_rf_id'  ] : '';
            if (empty($relationRunId) ){
                return false;
            }
            $lineRunIds = $this->getAnOnlineProcess($relationRunId);
            if (!in_array($param['run_id'], $lineRunIds)) {
                 return false;
            }
            $monitor_run_info = app($this->flowRunRepository)->getDetail($relationRunId , false , ['flow_id' , 'creator']);
            if (!empty($monitor_run_info)) {
                 return  $this->getMonitorAllowTypePermission($userId, $monitor_run_info->flow_id, $monitor_run_info->creator, 'allow_view');
            }
        }
        return false;
    }
    /**
     * 父子流程查看权限，多级查看权限关联
     * @param [type] $relation_type  关联类型
     * @return [type] [有权限的类别id]
     */
    public function getFlowAndSonViewPermission($param) {
        unset($param['user_info']);
        // 获取一条线上的流程id集合
        $param['run_ids'] = $this->getAnOnlineProcess($param['run_id']);
        if (!empty( $param['run_ids'] )) {
            // 验证所有父子run_id是否具有权限
            return $this->verifyFlowViewPermission($param);
        } else {
            return false;
        }
    }

    /**
     *  签办反馈的@的用户，具有查看流程的权限
     * @return [type] [有权限的类别id]
     */
    public function getFlowFeedbackPermission($param) {
        return  app($this->flowRunFeedbackRepository)->entity->whereRaw('find_in_set(?, at_user)' , [$param['user_id']])->Where('run_id' ,$param['run_id'])->count();

    }

    /**
     *  获取父子流程一条线上的run_ids
     * @return [type] [有权限的类别id]
     */
    public function getAnOnlineProcess($runId) {
        $data =  app($this->flowRunRepository)->entity->select(['run_id' , 'parent_id' , 'flow_id'])->whereRaw('find_in_set(?, parent_id)' , [$runId])->orWhere('run_id' ,$runId)->get()->toArray();
        $lineRunIds = [];
        if (!empty( $data )) {
            // 获取所有有权限的run_id
            foreach ($data as $runKey => $runValue) {
                array_push($lineRunIds, $runValue['run_id']);
                if (!empty($runValue['parent_id'])){
                   $lineRunIds =  array_merge($lineRunIds, explode(',', $runValue['parent_id']));
                }
            }
            return  array_unique($lineRunIds);
        }
        return [];
    }

}
