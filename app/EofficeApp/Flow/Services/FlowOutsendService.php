<?php

namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;
use App\Jobs\sendDataJob;
use App\Jobs\sendDataToDatabaseJob;
use Eoffice;
use GuzzleHttp\Client;
use Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;
/**
 * 流程外发service类
 *
 * 按功能拆分的service，尽量将外发功能相关的代码放在此service中
 *
 * @author zyx
 * @since 20200424
 */

class FlowOutsendService extends FlowBaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 【流程运行】 流程提交时外发主体逻辑
     *
     * @since 20200424转移
     */
    public function flowOutsend($param, $userInfo)
    {
        // 定义数据外发结果
        // $errorType = 0;
        // 外发结果保存字符串
        $logContent = '';
        // 预定义提醒数组
        $remindsData = [];
        $remindsData['toUser'] = ['admin', $userInfo['user_id']]; // 暂定为管理员，后期可以增加流程定义人或指定人 // 20191122,新增节点的提交人，暂不区分外发本身是在提交时还是到达时触发
        // $remindsData['sendMethod'] = [1]; // 直接使用数据库定义字段 //提醒方式，测试阶段默认1-短消息
        $remindsData['processName'] = ''; // 节点名称
        $remindsData['remindMark'] = 'flow-outsend'; // 提醒类型

        $param["user_id"] = $userInfo['user_id'];
        if (!isset($param["node_id"]) || !isset($param["run_id"]) || empty($param["node_id"]) || empty($param["run_id"])) {
            $logContent = trans('flow.0x030030') . ':' . $param["run_name"] . trans('flow.outsendlogone');
            //外发失败，失败原因：节点信息不全//有误
            app($this->flowLogService)->addSystemLog($userInfo['user_id'], $logContent, 'outsend', 'flow_run', 0, 'run_id', 1, []);

            // 调用提醒方法，// 错误类型1,节点信息不全
            $this->flowOutSendRemind(1, $param, $remindsData, $logContent);
            return ['code' => ['0x000008', 'outsend']];
        }

        $flowId = $param["flow_id"];
        $nodeId = $param["node_id"];
        $userId = $userInfo["user_id"];
        $runId = $param["run_id"];
        $runName = $param["run_name"];
        $isBack = $param["is_back"] ?? 0;
        $type = $param["type"] ?? '';
        $insertRes = $param['insert_res']; // flow_run_process表插入返回结果
        $relationTableInfoAdd = [ // 日志补充关联表flow_run_process
            'log_relation_table_add' => 'flow_run_process', // 补充关联表
            'log_relation_id_add' => ($type == 'current') ? app($this->flowRunProcessRepository)->getFieldValue('flow_run_process_id', ['run_id' => $runId, 'flow_process' => $nodeId, 'process_id' => $param['process_id']]) : $insertRes[0]['flow_run_process_id'], // 补充关联表主键，区分当前节点触发和下节点到达时触发
            'log_relation_field_add' => 'flow_run_process_id', // 补充关联表字段
        ];
        $currentUserInfo = [];
        if (!empty($userInfo)) {
            $currentUserInfo['user_id'] = $userInfo['user_id'];
            $currentUserInfo['user_name'] = $userInfo['user_name'];
            $currentUserInfo['user_accounts'] = $userInfo['user_accounts'];
            $currentUserInfo['dept_name'] = $userInfo['dept_name'];
            $currentUserInfo['dept_id'] = $userInfo['dept_id'];
            $currentUserInfo['role_name'] = json_encode($userInfo['role_name']);
            $currentUserInfo['role_id'] = json_encode($userInfo['role_id']);
        }

        //获取节点详情
        $detailResult = app($this->flowRunService)->getFlowNodeDetail($nodeId)->toArray();
        if (!$detailResult) {
            $logContent = trans('flow.0x030030') . ':' . $runName . trans('flow.outsendlogthree');
            //外发失败，失败原因：节点信息获取失败//有误
            app($this->flowLogService)->addSystemLog($userInfo['user_id'], $logContent, 'outsend', 'flow_run', $runId, 'run_id', 1, [], $runName);

            // 调用提醒方法 // 错误类型3,节点信息获取失败
            $this->flowOutSendRemind(3, $param, $remindsData, $logContent);
            return ['code' => ['0x000008', 'outsend']];
        }

        //数据外发开关
        $flowOutsendToggle = $detailResult['flow_outsend_toggle'];
        //外发数据
        $outsend = $detailResult["flow_process_has_many_outsend"];
        //节点名称
        $remindsData['processName'] = $detailResult['process_name'] ?? '';

        // 当前节点未开启外发或外发配置为空
        if (!$flowOutsendToggle || empty($outsend)) {
            return ['code' => ['0x000005', 'outsend']];
        }

        //主流程表单id
        $porcessformId = app($this->flowRunService)->getFormIdByRunId($runId);
        //主流程结构
        $formControlStructure = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$porcessformId]]]);
        // 处理表单结构
        // $formControlStructure = app($this->flowParseService)->handleDetailLayoutGrandchildrenStructure($formControlStructure);
        // 重新组装表单结构数组
        $formControlStructure = app($this->flowParseService)->handleFlowFormStructure($formControlStructure);
        //主流程数据
        if (count($formControlStructure)) {
            $flowRunDatabaseData = app($this->flowService)->getParseFormDataFlowRunDatabaseData($formControlStructure, $runId, $porcessformId, '', $userInfo);
        } else {
            $flowRunDatabaseData = [];
        }

        $flowOtherInfo = [];
        $parseData = [];
        foreach ($outsend as $key => $value) {
            if (($type == 'current' && $value['flow_outsend_timing'] == 0) ||
                ($type == 'next' && $value['flow_outsend_timing'] == 1) ||
                ($isBack == 1 && ($value['flow_outsend_timing'] != 2 && $value['send_back_forbidden'] == 1)) ||
                ($value['flow_outsend_timing'] == 2 && ($isBack != 1 || $type != 'current'))
            ) {
                continue;
            }
            //判断触发条件
            if (!empty($value['premise'])) {
                if (empty($flowOtherInfo)) {
                    $flowOtherInfo['process_id'] = $param['process_id'];
                    $flowOtherInfo['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : "";
                    $flowFormDataParam = ['status' => 'handle', 'runId' => $runId, 'formId' => $porcessformId, 'flowId' => $flowId, 'nodeId' => $nodeId];
                    //获取表单数据
                    $flowFormData = app($this->flowService)->getFlowFormParseData($flowFormDataParam, $userInfo);
                    $flowOtherInfo['form_structure'] = $flowFormData['parseFormStructure'];
                    $parseData = $flowFormData['parseData'];
                }
                $verifyResult = true;
                try {
                    $verifyResult = app($this->flowRunService)->verifyFlowFormOutletCondition($value['premise'], $parseData, $flowOtherInfo);
                } catch (\Exception $e) {
                    $verifyResult = false;
                } catch (\Error $error) {
                    $verifyResult = false;
                }
                //不满足触发条件
                if (!$verifyResult) {
                    continue;
                }
            }
            // 地址外发
            if ($value['flow_outsend_mode'] == 1) {
                $controlKeyValue = [];
                //记录控件类型
                foreach ($formControlStructure as $formControlStructures) {
                    if (isset($formControlStructures['control_id']) && isset($formControlStructures['control_type']) && isset($formControlStructures['control_attribute'])) {
                        $controlKeyValue[$formControlStructures['control_id']] = $formControlStructures['control_type'];
                    }
                }
                //主流程数据
                if (count($formControlStructure)) {
                    foreach ($flowRunDatabaseData as $key => $_flowRunDatabaseData) {
                        if (isset($controlKeyValue[$key]) && $controlKeyValue[$key] == 'upload') {
                            if (is_array($_flowRunDatabaseData)) {
                                foreach ($_flowRunDatabaseData as $_key => $_value) {
                                    if (isset($controlKeyValue[$_key]) && $controlKeyValue[$_key] == 'upload') {
                                        $newAttachmentId = app($this->attachmentService)->attachmentCopy([['source_attachment_id' => $_value]], ['user_id'=>$userId]);
                                        if ($newAttachmentId && isset($newAttachmentId[0]['attachment_id'])) {
                                            $flowRunDatabaseData[$key][$_key] = $newAttachmentId[0]['attachment_id'];
                                        }
                                    }
                                }
                            } else {
                                $newAttachmentId = app($this->attachmentService)->attachmentCopy([['source_attachment_id' => $_flowRunDatabaseData]], ['user_id'=>$userId]);
                                if ($newAttachmentId && isset($newAttachmentId[0]['attachment_id'])) {
                                    $flowRunDatabaseData[$key] = $newAttachmentId[0]['attachment_id'];
                                }
                            }
                        }
                    }
                }

                $flowRunDatabaseData['outsend_id'] = $value['id']; // 节点数据外发ID
                $flowRunDatabaseData['run_id'] = $runId;
                $flowRunDatabaseData['run_name'] = $runName;
                $flowRunDatabaseData['flow_id'] = $flowId;
                $flowRunDatabaseData['node_id'] = $nodeId;
                $flowRunDatabaseData['form_id'] = $porcessformId;
                $flowRunDatabaseData['process_name'] = $detailResult['process_name'];
                $flowRunDatabaseData['userInfo'] = $currentUserInfo;
                if (isset($value['outsend_url']) && !empty($value['outsend_url'])) {

                    $sendDataJob = [];
                    $sendDataJob['url'] = parse_relative_path_url($value['outsend_url']);
                    $sendDataJob['form_data'] = $flowRunDatabaseData;
                    $sendDataJob['run_name'] = $runName;
                    $sendDataJob['process_name'] = $detailResult['process_name'];
                    $sendDataJob['user_id'] = $userId;
                    $sendDataJob['ip'] = getClientIp();
                    Queue::push(new sendDataJob($sendDataJob, $relationTableInfoAdd));
                }
            } else if ($value['flow_outsend_mode'] == 2) { // 内部系统外发
                // 20200324,zyx,合并处理内部系统外发代码
                if (!isset($value['custom_module_menu']) || ($value['custom_module_menu'] == '')) {
                    continue;
                }

                // 流程部分信息
                $_flowInfo = [
                    'run_id' => $runId,
                    'run_name' => $runName,
                    'flow_id' => $flowId,
                    'form_id' => $porcessformId,
                    'process_name' => $detailResult['process_name'],
                ];
                // 外发处理方式
                $dataHandleType = $value['data_handle_type'];

                // 获取依赖字段数据，分为run_id和unique_id两个单元
                // $dependent_field = $dataHandleType ? app($this->flowParseService)->parseDataForDependentField($flowRunDatabaseData, $value) : [];

                // 外发配置参数放到一个数组里
                $flowOutsendConfig = [
                    'data_handle_type' => $dataHandleType, // 数据处理模式,
                    'dependent_field' => [], // 依赖字段
                    'detail_dependent_field' => [], // 明细依赖字段
                    'flow_outsend_id' => $value['id'], // 当前外发id
                    'flow_run_id' => $runId,
                ];

                $newSendData = [];
                $dependentFieldsArr = [];
                $i = 0;
                // 更新和删除模式的前置处理
                if ($dataHandleType) {
                    $dependentControlParentId = '';
                    $detailDependentControlParentId = '';
                    $customFieldParentId = '';
                    // 依赖字段数组
                    $dependentFieldArr = $value['outsend_has_many_dependent_fields'];
                    // 配对数组
                    $outsendFieldsArr = $value['outsend_has_many_fields'];
                    // 依赖字段不为空，需要特殊处理
                    foreach ($dependentFieldArr as $dependentFieldVal) {
                        foreach ($formControlStructure as $formControl) {
                            if (
                                ($formControl['control_id'] == $dependentFieldVal['form_field']) &&
                                ($formControl['control_parent_id'])
                            ) {
                                if ($dependentFieldVal['to_detail']) {
                                    // 表单明细数据依赖字段父级ID
                                    $detailDependentControlParentId = $formControl['control_parent_id'];
                                    // 由依赖字段所在明细获取已配对的模块明细字段，再由该明细字段获取明细父级ID
                                    foreach ($outsendFieldsArr as $outsendField) {
                                        if ($outsendField['porcess_fields_parent'] == $formControl['control_parent_id']) {
                                            // 由模块明细字段获取明细父级ID
                                            $customFieldParentId = app($this->formModelingService)->getCustomParentField(['key' => $value['custom_module_menu'], 'field_code' => $outsendField['receive_fields']]);
                                            break;
                                        }
                                    }
                                } else {
                                    // 表单主数据依赖字段父级ID
                                    $dependentControlParentId = $formControl['control_parent_id'];
                                    break;
                                }
                            }
                        }
                    }

                    // 获取依赖字段数据，分为run_id和unique_id两个单元
                    $dependent_field = $dataHandleType ? app($this->flowParseService)->parseDataForDependentField($flowRunDatabaseData, $value, $dependentControlParentId, 'main') : [];
                    // 获取明细依赖字段数据，暂时支持更新模式
                    $detail_dependent_field = (($dataHandleType == 1) && $detailDependentControlParentId) ? app($this->flowParseService)->parseDataForDependentField($flowRunDatabaseData, $value, $detailDependentControlParentId, 'detail') : [];

                    // run_id依赖
                    $dependentRunId = $dependent_field['run_id'] ?? 0;
                    // unique_id依赖
                    $dependentUniqueId = $dependent_field['unique_id'] ?? 0;

                    // 对明细依赖字段是标准数组时的提前判断，如果每行都是空则报错
                    $AllDetailDependentFieldControlIsEmpty = 0;
                    if ($detailDependentControlParentId && $detail_dependent_field && is_array($detail_dependent_field)) {
                        $i = 0;
                        foreach ($detail_dependent_field as $detailDFValue) {
                            $i = $detailDFValue ? $i : $i + 1;
                        }
                        if ($i == count($detail_dependent_field)) {
                            $AllDetailDependentFieldControlIsEmpty = 1;
                        }
                    }

                    // 主表依赖字段不能为空
                    // 明细依赖字段配置了但数据为空
                    // 按unique_id匹配，unique_id不能为空
                    // 按run_id匹配，run_id不能为空
                    if (
                        !$dependent_field ||
                        $AllDetailDependentFieldControlIsEmpty ||
                        ($detailDependentControlParentId && !$detail_dependent_field) ||
                        (($value['data_match_type'] == 2) && (!$dependentUniqueId)) ||
                        (($value['data_match_type'] == 1) && (!$dependentRunId || is_array($dependentRunId)))
                    ) {
                        if (!$dependent_field) {
                            $failedInfo = 'main_data_dependent_field_config_is_wrong'; // 主数据依赖字段没有匹配到指定控件
                        } else if ($detailDependentControlParentId && !$detail_dependent_field) {
                            $failedInfo = 'detail_data_dependent_field_is_wrong'; // 明细数据依赖字段没有匹配到指定控件或数据为空
                        } else if ($AllDetailDependentFieldControlIsEmpty) {
                            $failedInfo = 'detail_data_dependent_field_is_wrong'; // 明细数据依赖字段所有行数据都为空
                        } else if (
                            ($value['data_match_type'] == 2) && (!$dependentUniqueId)
                        ) {
                            $failedInfo = 'main_data_dependent_field_is_wrong'; // 主数据依赖字段控件数据为空
                        } else if (
                            ($value['data_match_type'] == 1) && (!$dependentRunId || is_array($dependentRunId))
                        ) {
                            $failedInfo = 'main_data_dependent_field_is_wrong'; // 主数据依赖字段控数据为空
                        } else {
                            $failedInfo = 'outsendlogtwo';
                        }
                        // if (!$dependent_field) { // 依赖字段数组为空认为是配对设置错误
                            // $failedInfo = 'outsendlogtwo';
                        // } else { // 依赖字段指定的控件数据为空
                            // $failedInfo = 'dependent_field_wrong';
                        // }
                        $logContent = trans('flow.0x030030') . ':' . $runName . ', ' . trans("flow.0x030101") . ':  ' . $detailResult['process_name'] . trans('flow.' . $failedInfo);
                        app($this->flowLogService)->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $value['id'], 'id', 1, '', $relationTableInfoAdd, $runName);
                        $this->flowOutSendRemind(4, $param, $remindsData, $logContent);
                        continue;
                    }

                    // 如果是run_id则转换为unique_id
                    $transRes = app($this->flowParseService)->handleDependentField($dependent_field, $value['custom_module_menu']);
                    $dependentFieldsArr = $transRes['dependentFieldsArr']; // run_id和unique_id的组合数组
                    $uniqueIdArr = $transRes['uniqueIdArr']; // 要使用的外发unique_id
                    // 依赖字段解析后没有相关数据
                    if (!$uniqueIdArr) {
                        $logContent = trans('flow.0x030030') . ':' . $runName . '  ' . trans("flow.0x030101") . ':  ' . $detailResult['process_name'] . trans('flow.dependent_run_id_has_no_log');
                        app($this->flowLogService)->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $value['id'], 'id', 1, '', $relationTableInfoAdd, $runName);
                        $this->flowOutSendRemind(4, $param, $remindsData, $logContent);
                        continue;
                    }

                    $sendData = [];
                    if ($dataHandleType == 1) { // 更新模式先获取配对数据
                        // 如果是项目外发更新，需要单独处理
                        if ($value['custom_module_menu'] == '160_project') {
                            foreach ($uniqueIdArr as $uniqId) {
                                // 依赖字段数据为空或者不是数字
                                if (!$uniqId || !is_numeric($uniqId)) {
                                    continue;
                                }

                                // 如果是项目外发更新，则需要补充项目类型字段
                                $managerInfoById = app($this->projectManagerRepository)->getFieldValue('manager_type', ['manager_id' => $uniqId]);
                                // 如果用ID获取不到数据则跳出
                                if (!$managerInfoById) {
                                    $logContent = trans('flow.0x030030') . ':' . $runName . '  ' . trans("flow.0x030101") . ':  ' . $detailResult['process_name'] . trans('flow.dependent_unique_id_has_no_log', ['id' => $uniqId]);
                                    app($this->flowLogService)->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $value['id'], 'id', 1, '', $relationTableInfoAdd, $runName);
                                    $this->flowOutSendRemind(4, $param, $remindsData, $logContent);
                                    continue;
                                }

                                // 把项目类型字段塞到表单数组中
                                $value['base_files'] = $flowRunDatabaseData['project_type'] = $managerInfoById;
                                // 处理外发配对数据
                                $sendData = $this->getFlowOutsendDataForOutsendCustomModule($value, $runId, $param, $flowRunDatabaseData, $formControlStructure, 1);

                                // 循环处理配对数据
                                foreach ($sendData as $sendDataValue) {
                                    if (empty($sendDataValue)) {
                                        continue;
                                    }

                                    $newSendData[$i] = $sendDataValue;
                                    // 把unique_id塞进数组
                                    $flowOutsendConfig['dependent_field']['unique_id'] = $uniqId;
                                    $newSendData[$i]['flow_outsend_config'] = $flowOutsendConfig;
                                    $i++;
                                }
                            }
                        } else {
                            //判断是系统模块还是系统自定义模块、用户自定义模块，分开处理
                            $moduleConfig = config('flowoutsend.module.' . $value['custom_module_menu']);
                            if ($moduleConfig) {
                                $sendData = $this->getFlowOutsendDataForOutsend($value, $runId, $param, $flowRunDatabaseData, $formControlStructure);
                            } else {
                                $sendData = $this->getFlowOutsendDataForOutsendCustomModule($value, $runId, $param, $flowRunDatabaseData, $formControlStructure, 1);
                            }

                            // 循环塞入外发配置
                            foreach ($uniqueIdArr as $uniqIdKey => $uniqId) {
                                // 依赖字段数据为空，不是数字，不是标准格式
                                if (
                                    !$uniqId ||
                                    (
                                        !preg_match('/^WV[0-9]{8}$/', $uniqId) && // 用户表主键格式固定
                                        !is_numeric($uniqId) && // 其他模块主键都是数字
                                        ($uniqId != 'admin') // 管理员账号
                                    )
                                ) {
                                    continue;
                                }

                                // 循环处理配对数据，赋值外发配置
                                foreach ($sendData as $sendDataKey => $sendDataValue) {
                                    if (empty($sendDataValue)) {
                                        continue;
                                    }

                                    // 主数据依赖字段是明细字段时，按照明细数据依赖字段的处理方式，一行对应一行，不再产生笛卡尔积
                                    if ($dependentControlParentId && ($sendDataKey != $uniqIdKey)) {
                                        continue;
                                    }

                                    $flowOutsendConfig['to_detail_data'] = false;
                                    $flowOutsendConfig['dependent_field']['unique_id'] = $uniqId;
                                    $sendDataValue['flow_outsend_config'] = $flowOutsendConfig;

                                    // 明细依赖字段不为空，则需要把明细依赖数据塞入数组
                                    if ($detailDependentControlParentId && $customFieldParentId) {
                                        $sendDataValue['flow_outsend_config']['detail_dependent_field']['unique_id'] = $customFieldParentId;
                                        $sendDataValue['flow_outsend_config']['to_detail_data'] = true;

                                        // 找到外发数据中的明细字段并将unique_id按行添加
                                        foreach ($sendDataValue as $sendDataValueKey => $sendDataValueValue) {
                                            if (($sendDataValueKey == $customFieldParentId) && is_array($sendDataValueValue)) {
                                                foreach ($sendDataValueValue as $sendDataValueValueKey => $sendDataValueValueValue) {
                                                    // 把detail_unique_id塞进每一行明细数组
                                                    $sendDataValue[$sendDataValueKey][$sendDataValueValueKey]['detail_unique_id'] = $detail_dependent_field['unique_id'][$sendDataValueValueKey];
                                                }
                                            }
                                        }
                                    }
                                    $newSendData[$i] = $sendDataValue;
                                    $i++;
                                }
                            }
                        }
                    } else if ($dataHandleType == 2) { // 删除模式不需要配对数据，只需要遍历处理外发配置
                        foreach ($uniqueIdArr as $uniqId) {
                            // 依赖字段数据为空，不是数字，不是标准格式
                            if (
                                !$uniqId ||
                                (
                                    !preg_match('/^WV[0-9]{8}$/', $uniqId) && // 用户表主键格式固定
                                    !is_numeric($uniqId) && // 其他模块主键都是数字
                                    ($uniqId != 'admin') // 管理员账号
                                )
                            ) {
                                continue;
                            }

                            // 把unique_id塞进数组
                            $flowOutsendConfig['dependent_field']['unique_id'] = $uniqId;
                            // if ($detailDependentControlParentId && $customFieldParentId) {
                            //     $flowOutsendConfig['to_detail_data'] = true;
                            // }
                            $newSendData[$i]['flow_outsend_config'] = $flowOutsendConfig;
                            $i++;
                        }
                    }

                    // 模块ID unique_id 解析后没有相关数据,直接退出
                    if (!$i) {
                        continue;
                    }
                } else { // 新建模式的前置处理
                    //判断是系统模块还是系统自定义模块、用户自定义模块，分开处理
                    $moduleConfig = config('flowoutsend.module.' . $value['custom_module_menu']);
                    if ($moduleConfig) {
                        $sendData = $this->getFlowOutsendDataForOutsend($value, $runId, $param, $flowRunDatabaseData, $formControlStructure);
                    } else {
                        $sendData = $this->getFlowOutsendDataForOutsendCustomModule($value, $runId, $param, $flowRunDatabaseData, $formControlStructure);
                    }
                    // 新建数据外发失败，失败原因：字段配对结构错误
                    if (empty($sendData)) {
                        $logContent = trans('flow.0x030030') . ':' . $runName . ', ' . trans("flow.flow_node_name") . ':  ' . $detailResult['process_name'] . trans('flow.outsendlogtwo');
                        app($this->flowLogService)->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $value['id'], 'id', 1, '', $relationTableInfoAdd, $runName);
                        $this->flowOutSendRemind(2, $param, $remindsData, $logContent);
                        continue;
                    }

                    foreach ($sendData as $sendDataValue) {
                        if (empty($sendDataValue)) {
                            continue;
                        }

                        $newSendData[$i] = $sendDataValue;
                        $newSendData[$i]['flow_outsend_config'] = $flowOutsendConfig;
                        $i++;
                    }
                }
                // 遍历数据处理外发
                foreach ($newSendData as $newSendDataValue) {
                    //当前用户id
                    $newSendDataValue['current_user_id'] = $userId;

                    $logContent = trans('flow.0x030030') . ':' . $runName . ', ' . trans("flow.0x030101") . '：' . $detailResult['process_name'] . ', ' . trans('flow.0x030175') . ' ' . trans('flow.0x030174') . ":<br> ";

                    // 区分调起外发处理方法
                    if ($value['module_select'] == 1) {
                        $result = $this->flowOutsendToModule($value['custom_module_menu'], $newSendDataValue, $_flowInfo);
                    } else {
                        $result = $this->flowOutsendToCustomModule($value['module'], $value['custom_module_menu'], $newSendDataValue, $_flowInfo);
                    }

                    // 部分不支持更新和删除的模块返回值格式未更新，需要兼容
                    $result = is_array($result) ? $result : [];

                    $isFailed = isset($result['code']) ? 1 : 0;
                    // 处理返回结果并拼接动态返回信息
                    $logContent .= $this->handleFlowOutsendResult($result, $newSendDataValue['flow_outsend_config']['data_handle_type'], $runId, $newSendDataValue['flow_outsend_config']['dependent_field']['unique_id'] ?? 0);

                    // 外发失败提醒，错误类型4：外发失败，具体原因见logcontent
                    if ($isFailed) {
                        $this->flowOutSendRemind(4, $param, $remindsData, $logContent);
                    }

                    // 记录系统外发日志
                    app($this->flowLogService)->addSystemLog($userId, $logContent, 'outsend', 'flow_outsend', $value['id'], 'id', $isFailed, '', $relationTableInfoAdd, $runName);
                    // 记录外发模块字段匹配日志
                    if (isset($result['dataForLog']) && count($result['dataForLog'])) {
                        // 依赖字段参数重新塞进去
                        $newSendDataValue['flow_outsend_config']['dependent_field'] = $dependentFieldsArr;
                        app($this->flowLogService)->addFlowOutsendToModuleLog($newSendDataValue['flow_outsend_config'], $result['dataForLog'], $value['custom_module_menu'], $userId);
                    }
                }
            } else if ($value['flow_outsend_mode'] == 3) { // 外部数据库外发
                $sendData = $this->getFlowOutsendDataForOutsend($value, $runId, $param, $flowRunDatabaseData, $formControlStructure);

                $databaseinfo['database_id'] = $value['database_id'];
                $databaseinfo['table_name'] = $value['table_name'];
                if (!empty($sendData)) {
                    foreach ($sendData as $sendDatas) {
                        if (isset($sendDatas['dependent_field'])) {
                            unset($sendDatas['dependent_field']);
                        }
                        $sendDataJob = [];
                        $sendDataJob['flow_id'] = $flowId;
                        $sendDataJob['outsend_id'] = $value['id']; // 节点外发ID
                        $sendDataJob['run_name'] = $runName;
                        $sendDataJob['process_name'] = $detailResult['process_name'];
                        $sendDataJob['user_id'] = $userId;
                        $sendDataJob['ip'] = getClientIp();
                        $sendDataJob['run_id'] = $runId;
                        $sendDataJob['node_id'] = $nodeId;
                        Queue::push(new sendDataToDatabaseJob($sendDataJob, $databaseinfo, $sendDatas, $relationTableInfoAdd));
                    }
                }
            } else if ($value['flow_outsend_mode'] == 4) { // 凭证外发
                $_param = [];
                $_param['voucher_category'] = $value['voucher_category'] ?? 1;
                $_param['voucher_config'] = $value['voucher_config'] ?? '';
                $flowRunDatabaseData['run_id'] = $runId;
                $flowRunDatabaseData['run_name'] = $runName;
                $flowRunDatabaseData['flow_id'] = $flowId;
                $flowRunDatabaseData['form_id'] = $porcessformId;
                app($this->integrationCenterService)->voucherIntergrationOutSend($_param, $flowRunDatabaseData);
            }
        }
    }

    /**
     * 数据外发提醒实现逻辑
     *
     * @param  int     $errorType    错误类型：1:节点信息不全；2:节点详情获取失败；3:外发配置参数有误；4:外发失败
     * @param  array   $param        从前端获取的参数
     * @param  array   $remindsData  预留补充参数数组
     * @param  string  $result       外发操作失败返回的原因
     *
     * @author  zyx
     */
    public function flowOutSendRemind($errorType, $param, $remindsData, $result)
    {
        $sendData = [];

        // 提醒类型
        $sendData['remindMark'] = $remindsData['remindMark'];
        // 提醒接收人
        $sendData['toUser'] = array_unique(array_filter($remindsData['toUser'])); //过滤重复人员和为空人员
        // contentparam参数，供提醒设置时使用
        $sendData['contentParam'] = ['flowTitle' => $param['run_name'], 'logContent' => $result, 'processName' => $remindsData['processName']];
        // stateparam参数，供消息中心跳转使用
        $sendData['stateParams'] = ["flow_id" => intval($param['flow_id']), "run_id" => intval($param['run_id'])];;
        // 发送方式,应使用发送方式的title而非ID，为避免后续错误，在此处直接忽略定义
        // if (isset($remindsData["sendMethod"])) {
        // }
        // moduletype,用处不明
        $sendData['module_type'] = app($this->flowTypeRepository)->getFlowSortByFlowId($param['flow_id']); // 用处未知

        // 发送提醒
        $flowRunProcessId = app($this->flowParseService)->getFlowRunProcessId($param['run_id'],'1');
        if (!empty($flowRunProcessId)) {
        	$sendData['stateParams']['flow_run_process_id'] = intval($flowRunProcessId);
        }

        Eoffice::sendMessage($sendData);
    }

    /*
     * 组装数据外发数据1，系统模块数据匹配
     *
     * @since 20200424转移
     */
    public function getFlowOutsendDataForOutsend($data, $runId, $param, $flowRunDatabaseData, $formControlStructure)
    {
        $formData = [];
        $outsendFields = isset($data['outsend_has_many_fields']) ? $data['outsend_has_many_fields'] : '';
        if (empty($outsendFields)) {
            return [];
        }
        $flowRunData = $this->getFlowDataForOutsend($flowRunDatabaseData, $formControlStructure, $param['user_id'], $outsendFields);
        $flowRunDatabaseData = $flowRunData['flowRunDatabaseData'];
        $controlIdToTiele = $flowRunData['controlIdToTiele'];
        $controlKeyValue = $flowRunData['controlKeyValue'];
        $controlIdToAttr = $flowRunData['controlIdToAttr'];
        $sendDataAdditional = [];
        for ($i = 0; $i < count($outsendFields); $i++) {
            $processField = $outsendFields[$i]['porcess_fields'];
            $receiveField = $outsendFields[$i]['receive_fields'];
            if (empty($processField) || empty($receiveField)) {
                continue;
            }
            // 系统字段处理
            // $parentId = $outsendFields[$i]['porcess_fields_parent'];
            if (in_array($processField, ['flow_id', 'run_id', 'run_name', 'form_id', 'node_id', 'process_id', 'attachments', 'feedback', 'document', 'flow_creator', 'flow_submit_user'])) {
                $formData[$processField] = $receiveField;
                // 任务管理附加子任务
                if (!empty($outsendFields[$i]['additional_field'])) {
                    //以值为key便于下一步解析
                    $sendDataAdditionalFormData[$processField] = $receiveField . '_1';
                }
            // 非系统字段处理
            } else {
                $analysis_url = isset($outsendFields[$i]['analysis_url']) ? $outsendFields[$i]['analysis_url'] : '';
                // 任务管理附加子任务
                if (!empty($outsendFields[$i]['additional_field'])) {
                    $sendDataAdditional[$receiveField] = $this->analysisData($processField, $flowRunDatabaseData, $analysis_url);
                } else {
                    // 非任务管理的其他字段
                    $formData[$receiveField] = $this->analysisData($processField, $flowRunDatabaseData, $analysis_url);
                    $isText = $this->flowOutsendGetModuleFliedIsText(['moduleId' => $data['custom_module_menu'], 'id' => $receiveField]);
                    if ($isText) {
                        $formData[$receiveField] = isset($flowRunDatabaseData[$processField . '_TEXT']) ? $flowRunDatabaseData[$processField . '_TEXT'] : $formData[$receiveField];
                    }
                }
            }
        }

        $sendData = [];
        $maxnum = 1;
        foreach ($formData as $key => $value) {
            if (!in_array($key, ['flow_id', 'run_id', 'run_name', 'form_id', 'node_id', 'process_id', 'attachments', 'feedback', 'document', 'flow_creator', 'flow_submit_user'])) {
                if ($data['flow_outsend_mode'] == 2 && isset($data['custom_module_menu']) && !empty($data['custom_module_menu'])) {
                    if ($this->flowOutsendGetModuleFliedAttribute(['moduleId' => $data['custom_module_menu'], 'id' => $key])) {
                        continue;
                    }
                }
                $maxnum = is_array($value) && count($value) > $maxnum ? count($value) : $maxnum;
            }
        }
        //分割明细字段 生成多条插入数据
        for ($i = 0; $i < $maxnum; $i++) {
            foreach ($formData as $key => $value) {
                //处理系统字段数据
                if (!is_array($value) && in_array($key, ['flow_id', 'run_id', 'run_name', 'form_id', 'node_id', 'process_id', 'attachments', 'feedback', 'document', 'flow_creator', 'flow_submit_user'])) {
                    $sendData[$i][$value] = $this->handleModuleFields($key, $data, $param);
                    if (isset($sendDataAdditionalFormData[$key])) {
                        $sendDataAdditional[$value] = $sendData[$i][$value];
                    }
                } else {
                    //处理明细字段要求返回数组的情况
                    $isArray = $this->flowOutsendGetModuleFliedAttribute(['moduleId' => $data['custom_module_menu'], 'id' => $key]);

                    if ($data['flow_outsend_mode'] == 2 && isset($data['custom_module_menu']) && !empty($data['custom_module_menu']) && $isArray) {
                        if (!is_array($value)) {
                            $sendData[$i][$key] = [$value];
                        } else {
                            $sendData[$i][$key] = $value;
                        }
                    } else {
                        if (is_array($value)) {
                            if (isset($value[$i])) {
                                $sendData[$i][$key] = $value[$i];
                            } else {
                                $sendData[$i][$key] = isset($value[0]) ? $value[0] : '';
                            }
                        } else {
                            $sendData[$i][$key] = $value;
                        }
                    }
                }
            }
            // 解析任务
            if ($sendDataAdditional && ($additionalArr = $this->handleModuleAdditional($sendDataAdditional))) {
                $sendData[$i]['additional'] = $additionalArr;
            }
        }
        return $sendData;
    }

    /*
     * 组装数据外发数据2,系统自定义模块或表单建模模块数据匹配
     *
     * @since 20200424转移
     */
    public function getFlowOutsendDataForOutsendCustomModule($data, $runId, $param, $flowRunDatabaseData, $formControlStructure, $dataHandleType = 0)
    {
        $formData = [];
        if (!empty($data['custom_module_menu'])) {
            $_module = $data['custom_module_menu'];
        } else {
            return [];
        }

        $outsendFields = isset($data['outsend_has_many_fields']) ? $data['outsend_has_many_fields'] : '';
        if (empty($outsendFields)) {
            return [];
        }
        $flowRunData = $this->getFlowDataForOutsend($flowRunDatabaseData, $formControlStructure, $param['user_id'], $outsendFields);
        $flowRunDatabaseData = $flowRunData['flowRunDatabaseData'];
        $controlIdToTiele = $flowRunData['controlIdToTiele'];
        $controlKeyValue = $flowRunData['controlKeyValue'];
        $controlIdToAttr = $flowRunData['controlIdToAttr'];
        //处理项目类型数据;
        $baseFilesData = '';
        $sendData = [];
        $sendDataAdditional = [];
        $project_count = 0;

        // 项目外发，需要单独处理
        if (isset($data['base_files']) && !empty($data['base_files'])) {
            if (
                !$dataHandleType && // 更新模式的项目外发不验证此处
                (
                    !isset($flowRunDatabaseData[$data['base_files']]) ||
                    empty($flowRunDatabaseData[$data['base_files']])
                )
            ) {
                return [];
            }
            $baseFilesData = $dataHandleType ? ($flowRunDatabaseData['project_type'] ?? '') : ($flowRunDatabaseData[$data['base_files']] ?? ''); // project_type在更新时单独配对处理
            $config = config('flowoutsend.from_type');
            $baseFilesId = '';
            if ($config && isset($config[$data['module']]) && !empty($config[$data['module']])) {
                $baseFilesId = $config[$data['module']]['baseId'];
                $sendData[$baseFilesId] = $baseFilesData;
            } else {
                return [];
            }
            //项目附加字段匹配
            if ($config && isset($config[$data['module']]) && !empty($config[$data['module']])) {
                foreach ($config[$data['module']]['otherFiles'] as $file_key => $file_value) {
                    if (isset($controlIdToTiele[$file_value]) && isset($flowRunDatabaseData[$controlIdToTiele[$file_value]])) {
                        $sendData[$file_key] = $flowRunDatabaseData[$controlIdToTiele[$file_value]];
                    }
                }
            }
            if (empty($baseFilesId)) {
                return [];
            }
        }

        $project_count = count($sendData);
        //循环发送字段 为接收字段赋值
        for ($i = 0; $i < count($outsendFields); $i++) {
            $processField = $outsendFields[$i]['porcess_fields'];
            $receiveField = $outsendFields[$i]['receive_fields'];
            if (empty($processField) || empty($receiveField)) {
                continue;
            }

            //项目、任务等的父级参数
            if (!empty($outsendFields[$i]['receive_fields_parent'])) {
                $_module = $outsendFields[$i]['receive_fields_parent'];
                // 先过滤项目类型，如果非当前选择的类型则直接跳过
                $moduleName = explode("_", $_module);
                if (isset($moduleName[2]) && ($moduleName[2] != $baseFilesData) && !$dataHandleType) {
                    continue;
                }
            }

            // 附加字段
            if (!empty($outsendFields[$i]['additional_field'])) {
                // 匹配流程相关字段需调用流程字段处理方法
                if (in_array($processField, ['flow_id', 'run_id', 'run_name', 'form_id', 'node_id', 'process_id', 'attachments', 'feedback', 'document', 'flow_creator', 'flow_submit_user'])) {
                    $sendDataAdditional[$receiveField] = $this->handleModuleFields($processField, $data, $param);
                } else {
                    // 匹配表单字段调用表单数据处理方法
                    $sendDataAdditional[$receiveField] = $this->analysisData($processField, $flowRunDatabaseData, $outsendFields[$i]['analysis_url'] ?? '');
                }
            // 非附加字段
            } else {
                // 项目、任务等的非附加字段处理
                if (isset($outsendFields[$i]['receive_fields_parent']) && !empty($outsendFields[$i]['receive_fields_parent']) && $outsendFields[$i]['receive_fields_parent'] != 'additional') {
                    $_temp = explode('_', $outsendFields[$i]['receive_fields_parent']);
                    $_parentId = $_temp[2];
                    // 项目外发，只获取表单中选择的项目类型相同的字段配对数据
                    if ($_parentId == $baseFilesData) {
                        // 匹配流程相关字段调用流程字段处理方法
                        if (in_array($processField, ['flow_id', 'run_id', 'run_name', 'form_id', 'node_id', 'process_id', 'attachments', 'feedback', 'document', 'flow_creator', 'flow_submit_user'])) {
                            $sendData[$receiveField] = $this->handleModuleFields($processField, $data, $param);
                        // 匹配表单字段调用表单数据处理方法
                        } else {
                            $sendData[$receiveField] = $this->analysisData($processField, $flowRunDatabaseData, $outsendFields[$i]['analysis_url'] ?? '');
                        }
                    }
                // 其他各种普通字段
                } else {
                    // 匹配流程相关字段调用流程字段处理方法
                    if (in_array($processField, ['flow_id', 'run_id', 'run_name', 'form_id', 'node_id', 'process_id', 'attachments', 'feedback', 'document', 'flow_creator', 'flow_submit_user'])) {
                        $sendData[$receiveField] = $this->handleModuleFields($processField, $data, $param);
                    // 匹配表单字段调用表单数据处理方法
                    } else {
                        $sendData[$receiveField] = $this->analysisData($processField, $flowRunDatabaseData, $outsendFields[$i]['analysis_url'] ?? '');
                    }
                }

                //获取明细项父级id
                $customParentField = app($this->formModelingService)->getCustomParentField(['key' => $_module, 'field_code' => $receiveField]);
                // 如果有明细父级则塞进明细单元内部
                if (!empty($customParentField) && isset($sendData[$receiveField])) {
                    $sendData[$customParentField][$receiveField] = $sendData[$receiveField];
                    unset($sendData[$receiveField]);
                }
            }
        }

        // 解析后数据为空
        if (empty($sendData)) {
            return [];
        }
        // 项目类解析后只有补充参数而没有配对的数据
        if ($project_count && $project_count == count($sendData)) {
            return [];
        }

        $newSendData = [];
        $detailData = [];
        // 重新组装模块明细字段对应表单明细字段内容
        // 将 [a => [a1, a2], b => [b1, b2]] 改为 [a => [a1, b1], b => [a2, b2]]
        $max = 1;
        foreach ($sendData as $_field => $_fieldvalue) {
            if (!is_array($_fieldvalue) || empty($_fieldvalue)) {
                continue;
            }
            $tempDetailRow = [];
            // 只处理对应表单明细字段的内容
            foreach ($_fieldvalue as $_key => $_value) {
                // 此模块字段是非明细字段，且对应表单明细字段，统计表单明细行数供后面拆分使用
                if (is_numeric($_key)) {
                    $max = (count($_fieldvalue) > $max) ? count($_fieldvalue) : $max;
                    continue;
                }
                // 此模块字段是明细字段
                // 该模块明细下的子字段对应表单的明细字段，拆分数据形式
                if (is_array($_value)) {
                    foreach ($_value as $_k => $_v) {
                        $detailData[$_field][$_k][$_key] = $_v;
                    }
                    $detailRowCount = count($_value);
                } else { // 该模块明细字段下的子字段对应表单非明细
                    $tempDetailRow[$_key] = $_value;
                }
                unset($sendData[$_field]);
            }
            if (!isset($detailData[$_field])) {
                continue;
            }
            // 将模块明细子字段对应表单非明细的组装成明细
            foreach ($tempDetailRow as $tempDetailKey => $tempDetailValue) {
                for ($i = 0;$i < $detailRowCount;$i++) {
                    $detailData[$_field][$i][$tempDetailKey] = $tempDetailValue;
                }
            }
        }

        // 模块非明细字段对应表单明细字段，需要分割这个明细字段，生成多条插入数据
        // 按照最多单元的表单明细控件数量拆分成多条数据
        for ($i = 0; $i < $max; $i++) {
            $newSendData[$i] = $detailData;
            // 完全的明细字段配对，没有明细外字段的配对，此时$sendData为空，需要单独处理
            if (empty($sendData)) {
                continue;
            }

            // 有明细字段外的字段配对，此时$sendData不为空
            foreach ($sendData as $_field => $_fieldvalue) {
                if (!is_array($_fieldvalue) || empty($_fieldvalue)) {
                    $newSendData[$i][$_field] = $_fieldvalue;
                    continue;
                }

                if (isset($_fieldvalue[$i])) {
                    $newSendData[$i][$_field] = $_fieldvalue[$i];
                } else { // 表单中这行明细没有数据，则用第一行的数据填补
                    $newSendData[$i][$_field] = $_fieldvalue[0] ?? (is_array($_fieldvalue) ? '' : $_fieldvalue);
                }
            }
        }

        // 项目外发，需要解析任务
        if ($sendDataAdditional && ($additional = $this->handleModuleAdditional($sendDataAdditional))) {
            // 如果有多个项目，则不支持再附加任务内容
            if (count($newSendData) > 1) {

            } else {
                // 20200630，项目外发搭配任务支持的格式
                // 1.多个项目，但不支持搭配任务
                // 2.单个项目，搭配多个任务
                // 3.project_type、project_state必须在明细外，否则无法正常解析
                $newSendData[0]['additional'] = $additional;
            }
        }
        return $newSendData;
    }

    /*
     * 【流程运行】 数据外发具体实现1-外发至系统模块
     *
     */
    public function flowOutsendToModule($module, $data, $flowInfo)
    {
        if (!$module) {
            return ['code' => ['0x000003', 'outsend']];
        }
        if (!$data) {
            return ['code' => ['0x000004', 'outsend']];
        }

        $userId = $data['current_user_id'];
        // $config = config('flowoutsend.module.' . $module . '.dataOutSend');
        // 20200117,zyx,外发数据处理模式，默认为新增，1表示更新，2表示删除
        $handleType = '';
        $flowOutsendConfig = $data['flow_outsend_config'] ?? []; // 新增加的外发配置参数都在这个数组里
        unset($data['flow_outsend_config']); // 删除外发配置单元
        if (isset($flowOutsendConfig['data_handle_type']) && $flowOutsendConfig['data_handle_type']) {
            // 如果ID为空则直接返回
            if (!$flowOutsendConfig['dependent_field']['unique_id']) {
                return ['code' => ['0x000004', 'outsend']];
            }
            // 更新还是删除
            if ($flowOutsendConfig['data_handle_type'] == 1) {
                $handleType = 'ForUpdate';
                // 更新模式，如果没有要更新的字段数据也直接返回
                if (!$data) {
                    return ['code' => ['0x000004', 'outsend']];
                }
            } elseif ($flowOutsendConfig['data_handle_type'] == 2) {
                $handleType = 'ForDelete';
            }
            // 更新和删除功能的数据格式修改一下
            $newData['data'] = $data; // 匹配的字段
            $newData['unique_id'] = $flowOutsendConfig['dependent_field']['unique_id']; // 依赖的字段
            $data = $newData;
        }
        $config = config('flowoutsend.module.' . $module . '.dataOutSend' . $handleType);
        if (!empty($config)) {
            $method = $config[1];
            try {
                return app($config[0])->$method($data);
            } catch (\Exception $e) {
                return ['code' => $e->getMessage()];
            } catch (\Error $e) {
                return ['code' => $e->getMessage()];
            };
        } else {
            $config = config('flowoutsend.custom_module.' . $module . '.dataOutSend' . $handleType);
            // if (isset($data['current_user_id'])) {
            //     unset($data['current_user_id']);
            // }
            // 系统自定义模块需要增加一个标识符，表明是流程外发
            // 更新和删除模式放在data单元中
            // 20200507,zyx,更新和删除的标识符应白锦的要求改为outsourceForEdit
            if (isset($data['data'])) {
                $data['data']['outsourceForEdit'] = true;
            } else { // 新增模式直接放在数组中
                $data['outsource'] = true;
            }
            // 20200324，统一处理调起方法
            return $this->handleFlowOutsendMethod($config, $handleType, $data, $module, $userId, $flowInfo);
        }
    }

    /*
     * 【流程运行】 数据外发具体实现2-外发至系统自定义模块和表单建模模块
     *
     * @since 20200424转移
     */
    public function flowOutsendToCustomModule($customModule, $moduleMenu, $data, $flowInfo)
    {
        if (!$customModule || !$moduleMenu) {
            return ['code' => ['0x000003', 'outsend']];
        }
        if (!$data || !isset($data['current_user_id'])) {
            return ['code' => ['0x000004', 'outsend']];
        }
        $userId = $data['current_user_id'];
        // unset($data['current_user_id']);

        $data['outsource'] = true;
        // 20200117,zyx,外发数据处理模式，默认为新增，1表示更新，2表示删除
        $handleType = '';
        $flowOutsendConfig = $data['flow_outsend_config'] ?? []; // 新增加的外发配置参数都在这个数组里
        unset($data['flow_outsend_config']); // 删除外发配置单元
        if (isset($flowOutsendConfig['data_handle_type']) && $flowOutsendConfig['data_handle_type']) {
            // 如果ID为空则直接返回
            // if (!$flowOutsendConfig['dependent_field']['unique_id']) {
            //     return ['code' => ['0x000004', 'outsend']];
            // }
            // 更新还是删除
            if ($flowOutsendConfig['data_handle_type'] == 1) {
                $handleType = 'ForUpdate';
                // 更新模式，如果没有要更新的字段数据，即count==1时也直接返回
                if (count($data) == 1) {
                    return ['code' => ['0x000004', 'outsend']];
                }
            } elseif ($flowOutsendConfig['data_handle_type'] == 2) {
                $handleType = 'ForDelete';
            }
            unset($data['current_user_id']);
            // 20200507,zyx,更新和删除的标识符应白锦的要求改为outsourceForEdit
            unset($data['outsource']);
            $data['outsourceForEdit'] = true;
            // 更新和删除功能的数据格式修改一下
            $newData['data'] = $data; // 匹配的字段
            $newData['current_user_id'] = $userId;
            // $newData['unique_id'] = $flowOutsendConfig['dependent_field']['unique_id']; // 依赖的字段
            if (isset($flowOutsendConfig['dependent_field']['unique_id'])) {
                $newData['unique_id'] = $flowOutsendConfig['dependent_field']['unique_id']; // 主表依赖的字段
            }
            if (isset($flowOutsendConfig['to_detail_data']) && $flowOutsendConfig['to_detail_data']) {
                $newData['to_detail_data'] = 1; // 明细依赖字段
            }
            $data = $newData;
        }
        // 项目配置获取方式特殊处理
        $fromTypeConfig = config('flowoutsend.from_type');
        if (isset($fromTypeConfig[$customModule]) && isset($fromTypeConfig[$customModule]['id']) && $fromTypeConfig[$customModule]['id'] == $moduleMenu) {
            $module = explode('_', $fromTypeConfig[$customModule]['id'])[1];
            $config = config('flowoutsend.custom_module.' . $module . '.dataOutSend' . $handleType);
        } else {
            $config = config('flowoutsend.custom_module.' . $moduleMenu . '.dataOutSend' . $handleType);
        }
        // 20200324，统一处理调起方法
        return $this->handleFlowOutsendMethod($config, $handleType, $data, $moduleMenu, $userId, $flowInfo);
    }

    /**
     * @DESC 集中处理内部系统外发的结果处理方法
     * @param $result 外发结果
     * @param $process_name 节点名称
     * @param $runName 流程名称
     * @param $flowOutsendId 当前外发ID
     * @param $uniqId 更新或删除时的主键id
     *
     * @author zyx
     * @return [string]
     */
    public function handleFlowOutsendResult($result, $dataHandleType, $runId, $uniqId = 0)
    {
        $dataHandleTypeInfo = ['launch', 'update', 'delete'];
        $logContent = '';
        // 更新和删除拆分使用每个数据ID
        // if ($dataHandleType) {
        $uniqId = $uniqId ? $uniqId : (isset($result['dataForLog'][0]['id_to']) ? (is_array($result['dataForLog'][0]['id_to']) ? trim(implode(',', $result['dataForLog'][0]['id_to']), ',') : trim($result['dataForLog'][0]['id_to'], ',')) : 0);
        // } else { // 新建使用runID
        //     $uniqId = $runId;
        // }
        $dynamicInfo = '';
        // 返回的补充信息
        if (Arr::get($result, 'dynamic')) {
            $dynamicInfo .= ', ' . (is_scalar($result['dynamic']) ? $result['dynamic'] : $result['dynamic'][0]);
        }
        // 附件数据错误信息
        // if (isset($result['attachmentsNullInfo']) && $result['attachmentsNullInfo']) {
        //     $dynamicInfo .= ', ' . $result['attachmentsNullInfo'];
        // }

        // 数据ID为0时，要么是失败，要么是部分模块返回值格式未更新，需要单独做处理
        if ($uniqId) {
            $logContentStart = trans('flow.data') . "id $uniqId ";
        } else {
            $logContentStart = '';
        }

        if (!isset($result['code'])) { // 外发成功
            $logContent = $logContentStart . trans('flow.' . $dataHandleTypeInfo[$dataHandleType]) . trans('flow.0x030121') . "$dynamicInfo<br>";
        } else { // 外发失败
            // 错误信息
            // 如果有dynamic单元则不再使用code单元
            if (Arr::get($result, 'dynamic')) {
                $codeAndDynamicInfo = trim($dynamicInfo, ', ');
            } else {
                // 没有dynamic单元时直接使用code单元
                if (is_array($result['code'])) {
                    $codeAndDynamicInfo = trans($result['code'][1] . '.' . $result['code'][0]);
                } else {
                    $codeAndDynamicInfo = $result['code'];
                }
            }

            $logContent = $logContentStart . trans('flow.' . $dataHandleTypeInfo[$dataHandleType]) . trans('flow.0x030122') . ',' . trans('flow.reason') . "$codeAndDynamicInfo<br>";
        }
        return $logContent;
    }

    /**
     * @DESC 集中处理调起外发方法
     *
     * @param [type] $config
     * @param [type] $handleType
     * @param [type] $data
     * @param [type] $module
     * @param [type] $userId
     * @param [type] $flowInfo
     *
     * @since 20200324,zyx
     * @return void
     */
    public function handleFlowOutsendMethod($config, $handleType, $data, $module, $userId, $flowInfo)
    {
        if (empty($config)) {
            //调用统一添加数据方法
            try {
                // 20200117,zyx,增加更新和删除数据方法调用
                if ($handleType == 'ForUpdate') {
                    return app($this->formModelingService)->editOutsendData($data, $module);
                } elseif ($handleType == 'ForDelete') {
                    return app($this->formModelingService)->deleteOutsendData($data, $module);
                } else {
                    $data['outsource'] = true;
                    return app($this->formModelingService)->addOutsendData($data, $module);
                }
            } catch (\Exception $e) {
                return ['code' => $e->getMessage()];
            } catch (\Error $e) {
                return ['code' => $e->getMessage()];
            };
        } else {
            $method = $config[1];
            $sendData = [
                'data' => $data['data'] ?? $data,
                'tableKey' => $module,
                'current_user_id' => $userId,
                'flowInfo' => $flowInfo
            ];
            // 如果是更新或删除，则增加unique_id单元
            if ($handleType) {
                $sendData['unique_id'] = $data['unique_id'];
            }else {
                $sendData['data']['outsource'] = true;
            }
            try {
                return app($config[0])->$method($sendData);
            } catch (\Exception $e) {
                return ['code' => $e->getMessage()];
            } catch (\Error $e) {
                return ['code' => $e->getMessage()];
            };
        }
    }

    /**
     * 【流程运行】获取流程外发需要的表单数据
     */
    public function getFlowDataForOutsend($flowRunDatabaseData, $formControlStructure, $userId, $outsendFields)
    {
        $controlKeyValue = [];
        $controlIdToTiele = [];
        $controlIdToAttr = [];
        //记录控件类型 排除拼接进去的_TEXT字段
        foreach ($formControlStructure as $formControlStructures) {
            if (isset($formControlStructures['control_id']) && isset($formControlStructures['control_type']) && isset($formControlStructures['control_attribute']) && strpos($formControlStructures['control_id'], '_TEXT') === false) {
                $controlKeyValue[$formControlStructures['control_id']] = $formControlStructures['control_type'];
                $controlIdToTiele[$formControlStructures['control_title']] = $formControlStructures['control_id'];
                $controlIdToAttr[$formControlStructures['control_id']] = $formControlStructures['control_attribute'];
            }
        }
        $attachmentControlIds = [];
        // 判断配置中是否匹配了附件控件，从而进行附件的复制操作
        foreach ($outsendFields as $outsendField) {
            if (isset($controlKeyValue[$outsendField['porcess_fields']]) && $controlKeyValue[$outsendField['porcess_fields']] == 'upload') {
                $attachmentControlIds[] = $outsendField['porcess_fields'];
            }
        }
        //主流程数据
        if (count($formControlStructure)) {
            foreach ($flowRunDatabaseData as $key => $value) {
                if (is_array($value) && isset($controlKeyValue[$key]) && $controlKeyValue[$key] != 'countersign') {
                    foreach ($value as $_key => $_value) {
                        if (isset($controlKeyValue[$_key])) {
                            $flowRunDatabaseData[$key][$_key] = $this->handleControlData($_key, $_value, $controlIdToAttr, $controlKeyValue, $userId, $attachmentControlIds);
                        }
                    }
                } else {
                    if (isset($controlKeyValue[$key])) {
                        $flowRunDatabaseData[$key] = $this->handleControlData($key, $value, $controlIdToAttr, $controlKeyValue, $userId, $attachmentControlIds);
                    }
                }
            }
        } else {
            $flowRunDatabaseData = [];
        }
        return [
            'flowRunDatabaseData' => $flowRunDatabaseData,
            'controlIdToTiele' => $controlIdToTiele,
            'controlKeyValue' => $controlKeyValue,
            'controlIdToAttr' => $controlIdToAttr,
        ];
    }

    /**
     * 【流程运行】解析特殊字段
     *
     * @param [type] $processField
     * @param [type] $flowRunDatabaseData
     * @param [type] $analysis_url
     * @return void
     */
    public function analysisData($processField, $flowRunDatabaseData, $analysis_url)
    {
        //判断是否为多个字段组合
        $analysis_data = [];
        if (strpos($processField, ',') !== false) {
            $processFields = explode(',', $processField);
            foreach ($processFields as $value) {
                $item = explode('_', $value);
                //判断明细项
                if (count($item) == 3) {
                    $parentId = $item[0] . '_' . $item[1];
                    $analysis_data[$value] = isset($flowRunDatabaseData[$parentId][$value]) ? $flowRunDatabaseData[$parentId][$value] : '';
                } else {
                    $analysis_data[$value] = isset($flowRunDatabaseData[$value]) ? $flowRunDatabaseData[$value] : '';
                }
            }
        } else {
            $item = explode('_', $processField);
            //判断明细项
            if (count($item) == 3) {
                $parentId = $item[0] . '_' . $item[1];
                $analysis_data[$processField] = isset($flowRunDatabaseData[$parentId][$processField]) ? $flowRunDatabaseData[$parentId][$processField] : '';
            } else {
                $analysis_data[$processField] = isset($flowRunDatabaseData[$processField]) ? $flowRunDatabaseData[$processField] : '';
            }
        }

        if (!empty($analysis_url)) {
            if (!check_white_list($analysis_url)) {
                return '';
            }
            $client = new Client();
            $guzzleResponse = $client->request('POST', $analysis_url, ['form_params' => $analysis_data]);
            $status = $guzzleResponse->getStatusCode();
            $body = json_decode($guzzleResponse->getBody());
            if ($status == 200) {
                if (is_array($body)) {
                    return json_encode($body);
                } else {
                    return $body;
                }
            } else {
                return '';
            }
        } else {
            if (count($analysis_data) > 1) {
                $result = '';
                foreach ($analysis_data as $_key => $_value) {
                    if (is_array($_value)) {
                        $result .= json_encode($_value);
                    } else {
                        $result .= $_value;
                    }
                }
                return $result;
            } else {
                foreach ($analysis_data as $_key => $_value) {
                    return $_value;
                }
            }
        }
    }

    /**
     * 【流程运行】 流程外发获取内部模块部分字段属性
     *
     */
    public function flowOutsendGetModuleFliedIsText($param)
    {
        if (empty($param)) {
            return false;
        }
        $moduleId = $param['moduleId'];
        $id = $param['id'];
        if ($config = config('flowoutsend.module')) {
            if (isset($config[$moduleId]['fileds'][$id]['isText']) && $config[$moduleId]['fileds'][$id]['isText'] === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * 【流程运行】 流程外发获取内部模块部分字段属性
     *
     */
    public function flowOutsendGetModuleFliedAttribute($param)
    {
        if (empty($param)) {
            return false;
        }
        $moduleId = $param['moduleId'];
        $id = $param['id'];
        if ($config = config('flowoutsend.module')) {
            if (isset($config[$moduleId]['fileds'][$id]['isArray']) && $config[$moduleId]['fileds'][$id]['isArray'] === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * 【流程运行】获取radiokey
     *
     * @param [type] $selectData
     * @param [type] $controlIdToAttr
     * @return void
     */
    public function getRadioKey($selectData, $controlIdToAttr)
    {
        if ($selectData === '') {
            return '';
        }
        if (empty($controlIdToAttr)) {
            return $selectData;
        }
        $attribute = json_decode($controlIdToAttr, true);
        if (isset($attribute["data-efb-options"])) { //自定义下拉框
            $dataEfbOptions = $attribute["data-efb-options"];
            $dataEfbOptions = explode(',', $dataEfbOptions);
            if (!is_array($selectData)) {
                $searchKey = array_search($selectData, $dataEfbOptions);
                if ($searchKey === false) {
                    return '';
                } else {
                    return $searchKey;
                }
            } else {
                foreach ($selectData as $key => $_selectData) {
                    $searchKey = array_search($_selectData, $dataEfbOptions);
                    if ($searchKey === false) {
                        $searchKey = '';
                    }
                    $selectData[$key] = $searchKey;
                }
                return $selectData;
            }
        }
        return $selectData;
    }

    /**
     * 【流程运行】获取CheckBoxkey
     *
     * @param [type] $selectData
     * @param [type] $controlIdToAttr
     * @return void
     */
    public function getCheckboxKey($selectData, $controlIdToAttr)
    {
        if ($selectData === '') {
            return '';
        }
        if (empty($controlIdToAttr)) {
            return $selectData;
        }
        $attribute = json_decode($controlIdToAttr, true);
        if (isset($attribute["data-efb-options"])) { //自定义下拉框
            $dataEfbOptions = $attribute["data-efb-options"];
            $dataEfbOptions = explode(',', $dataEfbOptions);

            if (!is_array($selectData)) {
                $selectData = explode(',', $selectData);
                $result = '';
                foreach ($selectData as $value) {
                    if (in_array($value, $dataEfbOptions)) {
                        $searchKey = array_search($value, $dataEfbOptions);
                        if ($searchKey === false) {
                            $searchKey = '';
                        }
                        $result .= ($result !== '' ? ',' : '') . $searchKey;
                    }
                }
                return $result;
            } else {
                foreach ($selectData as $k => $_selectData) {
                    $_selectData = explode(',', $_selectData);
                    $result = '';
                    foreach ($_selectData as $value) {
                        if (in_array($value, $dataEfbOptions)) {
                            $searchKey = array_search($value, $dataEfbOptions);
                            if ($searchKey === false) {
                                $searchKey = '';
                            }
                            $result .= ($result !== '' ? ',' : '') . $searchKey;
                        }
                    }
                    $selectData[$k] = $result;
                }
                return $selectData;
            }
        }
        return $selectData;
    }

    /**
     * 【流程定义】 节点设置-流程外发获取系统模块字段title
     *
     */
    public function flowOutsendGetModuleFieldTitle($param)
    {
        if (empty($param)) {
            return [];
        }
        $fieldName = "";
        $moduleId = $param['moduleId'];
        $id = $param['id'];
        $menu = $param['module'];

        if ($config = config('flowoutsend.module')) {
            if (isset($config[$moduleId])) {
                $module = $config[$moduleId];
                if (isset($module['fileds'][$id])) {
                    if (is_array($module['fileds'][$id])) {
                        if (isset($module['fileds'][$id]['field_name'])) {
                            $fieldName = trans('outsend.' . $moduleId . '.fileds.' . $id . '.field_name'); //$module['fileds'][$id]['field_name']
                        } else {
                            $fieldName = "";
                        }
                    } else {
                        $fieldName = trans('outsend.' . $moduleId . '.fileds.' . $id); //$module['fileds'][$id];
                    }
                }
            } else {
                $fieldName = app($this->formModelingService)->getFiledName($param['moduleId'], $param['id']);
            }
        }
        // 任务子任务处理
        $rootFieldName = '';
        if (isset($param['receive_fields_parent']) && !empty($param['receive_fields_parent'])) {
            if ($param['receive_fields_parent'] == 'additional') {
                $rootFieldName = trans('outsend.from_type_' . $menu . '.additional.title'); //config('flowoutsend.from_type.530.additional.title');
                $fieldName = trans('outsend.from_type_' . $menu . '.additional.fields.' . $id . '.field_name'); //config('flowoutsend.from_type.530.additional.fields.'.$id.'.field_name');
            } else {
                if ($config = config('flowoutsend.module')) {
                    if (isset($config[$moduleId])) {
                        $module = $config[$moduleId];
                        $rootFieldName = trans('outsend.from_type_' . $menu . '.title');
                        if (!isset($module['fileds'][$id])) {
                            return '';
                        }
                        if (is_array($module['fileds'][$id])) {
                            if (isset($module['fileds'][$id]['field_name'])) {
                                $fieldName = trans('outsend.' . $moduleId . '.fileds.' . $id . '.field_name'); //$module['fileds'][$id]['field_name']
                            } else {
                                $fieldName = "";
                            }
                        } else {
                            $fieldName = trans('outsend.' . $moduleId . '.fileds.' . $id); //$module['fileds'][$id];
                        }
                    } else {
                        $fieldName = app($this->formModelingService)->getFiledName($param['moduleId'], $param['id']);
                    }
                }
            }
        }
        return $rootFieldName ? ('[' . $rootFieldName . ']' . $fieldName) : $fieldName;
    }

    /**
     * 【流程定义】 节点设置-流程外发获取自定义模块和表单建模字段title
     *
     */
    public function flowOutsendGetCustomModuleFieldTitle($param)
    {
        if (empty($param) || !isset($param['customModuleId']) || empty($param['customModuleId']) || !isset($param['customModuleMenu']) || empty($param['customModuleMenu']) || !isset($param['id']) || empty($param['id'])) {
            return '';
        }
        // 普通字段
        $rootFieldName = '';
        if (isset($param['receive_fields_parent']) && !empty($param['receive_fields_parent'])) {
            $param['customModuleMenu'] = $param['receive_fields_parent'];
            $rootFieldName = app($this->formModelingService)->getRootFiledName($param['receive_fields_parent']);
        }
        // 自定义模块的创建人字段显示需要单独处理
        if ($param['id'] == 'creator') {
            $fieldName = trans('flow.0x030074');
        } else {
            $fieldName = app($this->formModelingService)->getFiledName($param['customModuleMenu'], $param['id']);
        }

        // 项目任务字段
        // 20200831，zyx,项目任务字段前端展示时用additional，但是数据获取需要使用project_task_value_1
        if (isset($param['receive_fields_parent']) && $param['receive_fields_parent'] == 'additional') {
            $rootFieldName = app($this->formModelingService)->getRootFiledName('project_task_value_1');
            $fieldName = app($this->formModelingService)->getFiledName('project_task_value_1', $param['id']);
            // $rootFieldName = trans('outsend.from_type_' . $param['customModuleId'] . '.additional.title');
            // $fieldName = trans('outsend.from_type_' . $param['customModuleId'] . '.additional.fields.' . $param['id'] . '.field_name');
        }
        return $rootFieldName ? ('[' . $rootFieldName . ']' . $fieldName) : $fieldName;
    }

    /**
     * 【流程定义】 节点设置-流程外发获取模块列表
     *
     */
    public function flowOutsendGetModuleList($param)
    {
        $result = [];
        //获取配置模块列表
        $config = config('flowoutsend.module');
        foreach ($config as $key => $value) {
            // 增加外发方法参数
            $result[] = ['id' => $key, 'title' => trans('outsend.' . $key . '.title'), 'module_type' => 'outsend_config', 'menu_parent' => $value['parent_menu'], 'dataOutSendForUpdate' => isset($value['dataOutSendForUpdate']) ? 1 : 0, 'dataOutSendForDelete' => isset($value['dataOutSendForDelete']) ? 1 : 0, 'has_outsend_config' => 1];
        }
        // 获取项目160配置
        $projectConfig = config('flowoutsend.from_type');
        // $result[] = ['id' => $projectConfig[160]['id'], 'title' => mulit_trans_dynamic("menu.menu_name.menu_" . 160), 'module_type' => 'system_custom', 'menu_parent' => 160, 'dataOutSendForUpdate' => 1, 'dataOutSendForDelete' => 1];

        // 获取内置自定义模块列表
        $systemCustomList = app($this->formModelingService)->getCustomMenuList(['is_dynamic' => 1]);
        // 系统自定义模块配置
        $systemCustomConfig = config('flowoutsend.custom_module');
        // 系统自定义模块外发更新和删除需要看配置
        foreach ($systemCustomList as $key => $val) {
            $systemCustomList[$key]['dataOutSendForUpdate'] = isset($systemCustomConfig[$val['id']]['dataOutSendForUpdate']) ? 1 : 0;
            $systemCustomList[$key]['dataOutSendForDelete'] = isset($systemCustomConfig[$val['id']]['dataOutSendForDelete']) ? 1 : 0;
            $systemCustomList[$key]['has_outsend_config'] = isset($systemCustomConfig[$val['id']]) ? 1 : 0; // 是否有外发配置，没有的话表示是纯用户自定义表单
        }

        //获取用户自定义页面列表
        $customList = app($this->formModelingService)->getCustomMenuList(['is_dynamic' => 2]);
        // 用户自定义模块默认都能外发更新和删除
        foreach ($customList as $key => $val) {
            $customList[$key]['dataOutSendForUpdate'] = 1;
            $customList[$key]['dataOutSendForDelete'] = 1;
            $customList[$key]['has_outsend_config'] = isset($systemCustomConfig[$val['id']]) ? 1 : 0; // 是否有外发配置，没有的话表示是纯用户自定义表单
        }

        $result = array_merge($result, $customList, $systemCustomList);
        $moduleData = [];
        $moduleDataForUpdate = [];
        $moduleDataForDelete = [];
        $i = 0;
        foreach ($result as $key => $value) {
            // 各父级模块菜单
            if (!isset($moduleData[$value['menu_parent']])) {
                $moduleData[$value['menu_parent']] = [];
                $moduleData[$value['menu_parent']]['title'] = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_parent']);
                $moduleData[$value['menu_parent']]['id'] = $value['menu_parent'];
                $moduleData[$value['menu_parent']]['module_type'] = $value['module_type'];
                $moduleData[$value['menu_parent']]['hasChilen'] = [];
            }

            if ($value['menu_parent'] != '160') { // 非项目模块子菜单直接塞进父菜单中
                $moduleData[$value['menu_parent']]['hasChilen'][] = $value;
                // 支持外发更新
                if ($value['dataOutSendForUpdate'] == 1) {
                    if (!isset($moduleDataForUpdate[$value['menu_parent']])) {
                        $moduleDataForUpdate[$value['menu_parent']] = $moduleData[$value['menu_parent']];
                        $moduleDataForUpdate[$value['menu_parent']]['hasChilen'] = [];
                    }
                    $moduleDataForUpdate[$value['menu_parent']]['hasChilen'][] = $value;
                }
                // 支持外发删除
                if ($value['dataOutSendForDelete'] == 1) {
                    if (!isset($moduleDataForDelete[$value['menu_parent']])) {
                        $moduleDataForDelete[$value['menu_parent']] = $moduleData[$value['menu_parent']];
                        $moduleDataForDelete[$value['menu_parent']]['hasChilen'] = [];
                    }
                    $moduleDataForDelete[$value['menu_parent']]['hasChilen'][] = $value;
                }
            } else { // 项目模块子菜单需要区分处理
                if (
                    (strstr($value['id'], 'project_value') !== false) ||
                    (strstr($value['id'], 'project_task_value') !== false)
                ) { // 项目类型下拉框对应的菜单都放在 项目管理 子菜单下，此时只返回 项目管理 本级菜单，其他子项都不返回
                    if ($i) {
                        continue;
                    }
                    $moduleData[160]['hasChilen'][] = [
                        "isSystemCustom" => true,
                        "menu_parent" => 160,
                        'module_type' => 'system_custom',
                        'id' => $projectConfig[160]['id'],
                        'title' => trans("outsend.from_type_" . 160 . ".title"),
                        'baseId' => $projectConfig[160]['baseId'],
                        'baseFiles' => trans("outsend.from_type_" . 160 . ".baseFiles"),
                        'has_outsend_config' => 1,
                    ];
                    $i++; // 项目管理菜单只需要填写一次
                } else { // 项目管理下的自定义菜单认为是通用的自定义菜单外发配置，与项目管理配置没有实质关联
                    $moduleData[160]['hasChilen'][] = $value;
                }
                // 项目管理默认支持更新和删除
                $moduleDataForUpdate[160] = $moduleDataForDelete[160] = $moduleData[160];
            }
        }
        if (isset($param['parent_id']) && !empty($param['parent_id'])) {
            if (isset($moduleData[$param['parent_id']]['hasChilen'])) {
                return $moduleData[$param['parent_id']]['hasChilen'];
            } else {
                return [];
            }
        }
        // 20200327,zyx,一次性返回新建更新删除的3套模块列表
        return [array_values($moduleData), array_values($moduleDataForUpdate), array_values($moduleDataForDelete)];
    }

    /**
     * 【流程定义】 节点设置-流程外发获取内部模块列表
     *
     */
    public function flowOutsendGetModuleFieldsList($param)
    {
        if (!isset($param['moduleId']) || $param['moduleId'] == "") {
            return [];
        }
        $dataHandleType = $param['data_handle_type'] ?? 0; // 部分字段不支持更新
        $moduleId = $param['moduleId'];
        $config = config('flowoutsend.module');
        $data = [];
        //系统配置模块
        if (isset($config[$moduleId])) {
            $noUpdateFields = $config[$moduleId]['no_update_fields'] ?? []; // 有限制不能更新的字段则需要筛选
            array_push($noUpdateFields, 'creator'); // 创建人默认不能更新
            foreach ($config[$moduleId]['fileds'] as $key => $value) {
                // 如果是获取模块更新或删除字段，且当前字段属于限制更新的字段则跳过
                if ($dataHandleType && $noUpdateFields && in_array($key, $noUpdateFields)) {
                    continue;
                }
                $tmpTitle = is_array($value) ? trans('outsend.' . $moduleId . '.fileds.' . $key . '.field_name') : trans('outsend.' . $moduleId . '.fileds.' . $key);
                $isRequired = is_array($value) ? ($value['required'] ?? 0) : 0;
                $data[] = [
                    'id' => $key,
                    'title' => $tmpTitle, // 展示title，带必填标识
                    'describe' => is_array($value) ? (isset($value['describe']) ? trans('outsend.' . $moduleId . '.fileds.' . $key . '.describe') : trans('outsend.' . $moduleId . '.fileds.' . $key . '.field_name')) : trans('outsend.' . $moduleId . '.fileds.' . $key), //$value['describe']$value['field_name']$value,
                    'type' => 'module_fields',
                    'is_required' => $isRequired,
                    'is_required_for_show' => $isRequired,
                    'checked_num' => 0, // 配对次数
                ];
            }
            // 20200402，zyx,任务模块走这里
            $menuId = $config[$moduleId]['parent_menu'];
            $from_type_config = config('flowoutsend.from_type');
            if (isset($from_type_config[$menuId])) {
                foreach ($data as $_k => $_data) {
                    $data[$_k]['parent_menu'] = $menuId;
                    $data[$_k]['rootTitle'] = trans('outsend.from_type_' . $menuId . '_id');
                }
                $newdata = [
                    'hasChilen' => $data,
                    'id' => $menuId,
                    'title' => trans('outsend.from_type_' . $menuId . '.title'),
                ];
                $data = [];
                $data[] = $newdata;
                // 任务模块的子任务菜单只有在新建模式下才显示
                if ($dataHandleType == 0) {
                    $additional = [
                        'id' => $from_type_config[$menuId]['additional']['id'],
                        'title' => trans('outsend.from_type_' . $menuId . '.additional.title')
                    ];
                    foreach ($from_type_config[$menuId]['additional']['fields'] as $key => $value) {
                        $tmpTitle = trans('outsend.from_type_' . $menuId . '.additional.fields.' . $key . '.field_name');
                        $isRequired = $value['required'] ?? 0;
                        $additional['hasChilen'][] = [
                            'id' => $key,
                            'is_required' => $isRequired,
                            'is_required_for_show' => $isRequired,
                            'parent_id' => "",
                            'parent_menu' => "additional",
                            'rootTitle' => trans('outsend.from_type_' . $menuId . '.additional.title'),
                            'title' => $tmpTitle,
                            'type' => "additional",
                            'checked_num' => 0,
                        ];
                    }
                    $data[] = $additional;
                }
            }
        } else {
            //系统自定义模块
            $coutomList = app($this->formModelingService)->getSystemCustomFields(['key' => $param['moduleId'], 'data_handle_type' => $dataHandleType]);

            if (!empty($coutomList)) {
                $data = $coutomList;
            } else {
                return [];
            }
        }

        return $data;
    }

    /**
     * 【流程定义】记录外发依赖字段
     *
     * @param [type] $flowOutsendId
     * @param [type] $value
     *
     * @author  zyx
     * @return void
     */
    public function addFlowOutsendDependentField($flowOutsendId, $value)
    {
        // 数据匹配逻辑为1时用run_id，为2时用unique_id表示模块数据的唯一主键
        if ($value['data_handle_type']) {
            $dependentMatchFields = [];
            if ($value['data_match_type'] == 1) {
                $dependentField = 'run_id';
            } elseif ($value['data_match_type'] == 2) {
                $dependentField = 'unique_id';
            }
            $dependentMatchFields[] = [
                'flow_outsend_id' => $flowOutsendId,
                'dependent_field' => $dependentField,
                'form_field'      => $value['dependent_field'],
                'to_detail'       => 0,
                'created_at'       => date('Y-m-d H:i:s')
            ];
            // 有明细依赖字段
            if (isset($value['detail_dependent_field']) && $value['detail_dependent_field']) {
                $dependentMatchFields[] = [
                    'flow_outsend_id' => $flowOutsendId,
                    'dependent_field' => $dependentField,
                    'form_field'      => $value['detail_dependent_field'],
                    'to_detail'       => 1,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
            }

            // 写入外发依赖字段表
            app($this->flowOutsendDependentFieldsRepository)->insertMultipleData($dependentMatchFields);
        }
    }

    /**
     * 【流程定义】 节点设置-流程外发设置参数保存
     *
     */
    public function flowOutsendSaveData($data, $userInfo, $saveType = "")
    {
        if (isset($data["node_id"]) && $data["node_id"] == "batchNode") {
            $batchNode = isset($data["batchNode"]) ? $data["batchNode"] : [];
            if (empty($batchNode)) {
                // 保存失败，未获取到流程节点ID
                return ['code' => ['0x030155', 'flow']];
            } else {
                unset($data["batchNode"]);
                $saveResult = "";
                foreach ($batchNode as $key => $nodeId) {
                    $data["node_id"] = $nodeId;
                    $saveResult = $this->flowOutsendSaveData($data, $userInfo, "batchNode");
                }
                return $saveResult;
            }
        }
        if (isset($data['node_id']) && $data['node_id']) {
            $nodeId = $data['node_id'];
            // 清空节点信息redis缓存
            if (Redis::exists('flow_process_info_' . $nodeId)) {
                Redis::del('flow_process_info_' . $nodeId);
            }
            // 判断节点编辑权限
            if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $userInfo)) {
                return ['code' => ['0x000006', 'common']];
            }

            $historyNodeInfo = app($this->flowService)->getFlowNodeInfo($nodeId);
            //保存子流程数据
            if (isset($data['sun_flow_toggle'])) {
                //验证字段
                if ($data['sun_flow_toggle'] == 1 && (empty($data['sunflows']))) {
                    return ['code' => ['0x000003', 'common']];
                }

                if ($data['sun_flow_toggle'] == 0) {
                    //更新flowprocess表数据
                    $updateData = [
                        'sun_flow_toggle' => 0,
                        //'run_ways'=>$data['run_ways'],
                        //'flow_outsend_timing'=>$data['flow_outsend_timing'],
                    ];
                    app($this->flowProcessRepository)->updateData($updateData, ['node_id' => [$nodeId]]);
                    //删除子流程数据表信息
                    //app($this->flowSunWorkflowRepository)->deleteByWhere(["node_id" => [$nodeId]]);
                } else if ($data['sun_flow_toggle'] == 1) {
                    if (count($data['sunflows']) > 0) {
                        $insertData = [];
                        foreach ($data['sunflows'] as $key => $value) {
                            //拼接数据外发字段
                            $sendFileds = "";
                            $receiveFileds = "";
                            if (!empty($value['relations'])) {
                                foreach ($value['relations'] as $relations) {
                                    if ($sendFileds) {
                                        $sendFileds .= "," . $relations['formFileds'];
                                    } else {
                                        $sendFileds = $relations['formFileds'];
                                    }
                                    if ($receiveFileds) {
                                        $receiveFileds .= "," . $relations['receiveFileds'];
                                    } else {
                                        $receiveFileds = $relations['receiveFileds'];
                                    }
                                }
                            }
                            //更新子流程数据表信息
                            $insertData[] = [
                                'node_id' => $nodeId,
                                'receive_flow_id' => $value['sunflow_id'],
                                'porcess_fields' => $sendFileds,
                                'receive_fields' => $receiveFileds,
                                'premise' => $value['premise'],
                                'run_ways' => $value['run_ways'],
                            ];
                        }
                        if (!empty($insertData)) {
                            //更新flowprocess表数据
                            $updateData = [
                                'sun_flow_toggle' => $data['sun_flow_toggle'],
                                'trigger_all_son_flows' => $data['trigger_all_son_flows'],
                                'trigger_son_flow_back' => $data['trigger_son_flow_back'],
                                //'run_ways'=>$data['run_ways'],
                                //'flow_outsend_timing'=>$data['flow_outsend_timing'],
                            ];
                            app($this->flowProcessRepository)->updateData($updateData, ['node_id' => [$nodeId]]);

                            app($this->flowSunWorkflowRepository)->deleteByWhere(["node_id" => [$nodeId]]);

                            app($this->flowSunWorkflowRepository)->insertMultipleData($insertData);
                        }
                    }
                }

                $historyData = [];
                $historyData["sun_flow_toggle"] = isset($historyNodeInfo["sun_flow_toggle"]) ? $historyNodeInfo["sun_flow_toggle"] : "0";
                $historyData["sunflows"] = isset($historyNodeInfo["outsendToSunWorkflow"]) ? $historyNodeInfo["outsendToSunWorkflow"] : [];
                $newData = [];
                if (isset($GLOBALS['getFlowNodeDetail' . $nodeId])) {
                    unset($GLOBALS['getFlowNodeDetail' . $nodeId]);
                }
                $newNodeInfo = app($this->flowService)->getFlowNodeInfo($nodeId);
                $newData["sun_flow_toggle"] = isset($newNodeInfo["sun_flow_toggle"]) ? $newNodeInfo["sun_flow_toggle"] : "0";
                $newData["sunflows"] = isset($newNodeInfo["outsendToSunWorkflow"]) ? $newNodeInfo["outsendToSunWorkflow"] : [];
                $routFrom = isset($data['route_from']) ? $data['route_from'] : '';
                // 调用日志函数
                $logParam = [];
                $logParam["new_info"] = json_decode(json_encode($newData), true);
                $logParam["history_info"] = json_decode(json_encode($historyData), true);
                app($this->flowLogService)->logFlowDefinedModify($logParam, "flow_process&node_id", $nodeId, $routFrom, $userInfo, $saveType);
                return 1;
            } else if (isset($data['flow_outsend_toggle'])) { //保存数据外发数据
                if ($data['flow_outsend_toggle'] == 1) {
                    if (count($data['outsend']) > 0) {
                        //更新flowprocess表数据
                        app($this->flowProcessRepository)->updateData(['flow_outsend_toggle' => $data['flow_outsend_toggle']], ['node_id' => [$nodeId]]);
                        //删除其他方式
                        $oldOutsendList = app($this->flowOutsendRepository)->getList($nodeId);
                        if ($oldOutsendList) {
                            $oldOutsendList = $oldOutsendList->pluck('id')->toArray();
                        }
                        app($this->flowOutsendRepository)->deleteByWhere(["node_id" => [$nodeId]]);
                        app($this->flowOutsendFieldsRepository)->deleteByWhere(["flow_outsend_id" => [$oldOutsendList, 'in']]);
                        app($this->flowOutsendDependentFieldsRepository)->deleteByWhere(["flow_outsend_id" => [$oldOutsendList, 'in']]);
                        foreach ($data['outsend'] as $key => $value) {
                            if ($value['flow_outsend_mode'] == 1) {
                                if (empty($value['outsend_url'])) {
                                    return ['code' => ['0x000003', 'common']];
                                }

                                $_insertData = [
                                    'node_id' => $nodeId,
                                    'outsend_url' => $value['outsend_url'],
                                    'premise' => $value['premise'],
                                    'flow_outsend_timing' => $value['flow_outsend_timing'],
                                    'send_back_forbidden' => $value['send_back_forbidden'],
                                    'flow_outsend_mode' => $value['flow_outsend_mode'],
                                ];
                                app($this->flowOutsendRepository)->insertData($_insertData);
                            } else if ($value['flow_outsend_mode'] == 2) {
                                // 20200224,zyx,更新/删除外发字段配对关系可以为空
                                if ((empty($value['module']) || empty($value['custom_module_menu']) || empty($value['relations'])) && (!$value['data_handle_type'])) {
                                    return ['code' => ['0x000003', 'common']];
                                }

                                $baseFildeId = '';
                                // 如果是160项目外发需要单独获取，其他模块都不需要
                                if ($value['module'] == 160 && $value['custom_module_menu'] == '160_project') {
                                    //处理项目类型数据
                                    $config = config('flowoutsend.from_type');
                                    if ($config && isset($config[$value['module']])) {
                                        //检测项目必须设置的字段知否存在
                                        $baseFiles = $config[$value['module']]['baseId'];
                                        //获取表单字段
                                        $flowId = isset($value['flow_id']) ? $value['flow_id'] : '';
                                        if (!$flowId) {
                                            return ['code' => ['0x000003', 'common']];
                                        }
                                        $flowinfo = app($this->flowTypeRepository)->getDetail($flowId);
                                        $formId = $flowinfo->form_id;
                                        $formControlTypeArray = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(["search" => ['control_title' => [$baseFiles], 'form_id' => [$formId]]]);
                                        if (!empty($formControlTypeArray)) {
                                            //保
                                            $baseFildeId = $formControlTypeArray[0]['control_id'];
                                        } else {
                                            $baseFildeId = '';
                                        }
                                    }
                                }

                                $_insertData = [
                                    'module_select' => $value['module_select'],
                                    'node_id' => $nodeId,
                                    'module' => $value['module'],
                                    'custom_module_menu' => $value['custom_module_menu'],
                                    'premise' => $value['premise'],
                                    'flow_outsend_timing' => $value['flow_outsend_timing'],
                                    'send_back_forbidden' => $value['send_back_forbidden'],
                                    'flow_outsend_mode' => $value['flow_outsend_mode'],
                                    'base_files' => $baseFildeId,
                                    'data_handle_type' => $value['data_handle_type'],
                                    'data_match_type' => $value['data_match_type'],
                                    // 'data_creator_field' => $value['data_creator_field'] ?? 'flow_submit_user',
                                    // 'dependent_field' => $value['data_handle_type'] ? json_encode($dependentFieldMatchRelations) : ''
                                ];
                                //单条插入后再插入关联字段表数据
                                $temp_id = app($this->flowOutsendRepository)->insertGetId($_insertData);
                                //拼接数据外发字段
                                $fieldsInsertData = [];
                                foreach ($value['relations'] as $relations) {
                                    if (isset($relations['formFileds']) && isset($relations['receiveFileds'])) {
                                        //判断项目类型字段
                                        if (in_array($value['custom_module_menu'], ['160_project', 'task'])) {
                                            if (isset($relations['additional']) && $relations['additional'] == 'additional') {
                                                $receive_fields_parent = 'additional';
                                            } else {
                                                $receive_fields_parent = $relations['parentMenu'];
                                            }
                                        } else {
                                            $receive_fields_parent = '';
                                        }

                                        //如果是明细字段,记录其父级控件
                                        $detailPorcessValue = explode('_', $relations['formFileds']);
                                        if (count($detailPorcessValue) == 3) {
                                            $porcess_fields_parent = $detailPorcessValue[0] . "_" . $detailPorcessValue[1];
                                        } else {
                                            $porcess_fields_parent = '';
                                        }
                                        $fieldsInsertData[] = [
                                            'flow_outsend_id' => $temp_id,
                                            'porcess_fields' => $relations['formFileds'],
                                            'receive_fields' => $relations['receiveFileds'],
                                            'receive_fields_parent' => $receive_fields_parent ? $receive_fields_parent : '',
                                            'porcess_fields_parent' => $porcess_fields_parent,
                                            'analysis_url' => isset($relations['analysis_url']) ? $relations['analysis_url'] : '',
                                            'additional_field' => isset($relations['additional']) ? $relations['additional'] : '',
                                        ];
                                    }
                                }
                                // 20200225,zyx,补充更新和删除模式的依赖字段
                                $this->addFlowOutsendDependentField($temp_id, $value);

                                if (!empty($fieldsInsertData)) {
                                    app($this->flowOutsendFieldsRepository)->insertMultipleData($fieldsInsertData);
                                }
                            } else if ($value['flow_outsend_mode'] == 3) {
                                if (empty($value['relations']) || empty($value['database_id']) || empty($value['table_name'])) {
                                    return ['code' => ['0x000003', 'common']];
                                }

                                $insertData = [
                                    'node_id' => $nodeId,
                                    'database_id' => $value['database_id'],
                                    'table_name' => $value['table_name'],
                                    //'porcess_fields' => $sendFileds,
                                    //'receive_fields' => $receiveFileds,
                                    'premise' => $value['premise'],
                                    'flow_outsend_timing' => $value['flow_outsend_timing'],
                                    'send_back_forbidden' => $value['send_back_forbidden'],
                                    'flow_outsend_mode' => $value['flow_outsend_mode'],
                                ];
                                //单条插入后再插入关联字段表数据
                                $temp_id = app($this->flowOutsendRepository)->insertGetId($insertData);
                                //拼接数据外发字段
                                $fieldsInsertData = [];
                                foreach ($value['relations'] as $relations) {
                                    if (isset($relations['formFileds']) && isset($relations['receiveFileds'])) {
                                        //如果是明细字段,记录其父级控件
                                        $detailPorcessValue = explode('_', $relations['formFileds']);
                                        if (count($detailPorcessValue) == 3) {
                                            $porcess_fields_parent = $detailPorcessValue[0] . "_" . $detailPorcessValue[1];
                                        } else {
                                            $porcess_fields_parent = '';
                                        }
                                        $fieldsInsertData[] = [
                                            'flow_outsend_id' => $temp_id,
                                            'porcess_fields' => $relations['formFileds'],
                                            'receive_fields' => $relations['receiveFileds'],
                                            'porcess_fields_parent' => $porcess_fields_parent,
                                            'analysis_url' => isset($relations['analysis_url']) ? $relations['analysis_url'] : '',
                                        ];
                                    }
                                }
                                if (!empty($fieldsInsertData)) {
                                    app($this->flowOutsendFieldsRepository)->insertMultipleData($fieldsInsertData);
                                }
                            } else if ($value['flow_outsend_mode'] == 4) {
                                if (empty($value['voucher_category']) || empty($value['voucher_config'])) {
                                    return ['code' => ['0x000003', 'common']];
                                }

                                $insertData = [
                                    'node_id' => $nodeId,
                                    'voucher_category' => $value['voucher_category'],
                                    'voucher_config' => $value['voucher_config'],
                                    'premise' => $value['premise'],
                                    'flow_outsend_timing' => $value['flow_outsend_timing'],
                                    'send_back_forbidden' => $value['send_back_forbidden'],
                                    'flow_outsend_mode' => $value['flow_outsend_mode'],
                                ];
                                app($this->flowOutsendRepository)->insertData($insertData);
                            }
                        }
                    }
                } else if ($data['flow_outsend_toggle'] == 0) {
                    //更新flowprocess表数据
                    app($this->flowProcessRepository)->updateData(['flow_outsend_toggle' => $data['flow_outsend_toggle']], ['node_id' => [$nodeId]]);
                }
                $historyData = [];
                $historyData["flow_outsend_toggle"] = isset($historyNodeInfo["flow_outsend_toggle"]) ? $historyNodeInfo["flow_outsend_toggle"] : "0";
                $historyData["outsend"] = isset($historyNodeInfo["outsend"]) ? $historyNodeInfo["outsend"] : [];
                $newData = [];
                $newNodeInfo = app($this->flowService)->getFlowNodeInfo($nodeId);
                $newData["flow_outsend_toggle"] = isset($newNodeInfo["flow_outsend_toggle"]) ? $newNodeInfo["flow_outsend_toggle"] : "0";
                $newData["outsend"] = isset($newNodeInfo["outsend"]) ? $newNodeInfo["outsend"] : [];
                $routFrom = isset($data['route_from']) ? $data['route_from'] : '';
                // 调用日志函数
                $logParam = [];
                $logParam["new_info"] = json_decode(json_encode($newData), true);
                $logParam["history_info"] = json_decode(json_encode($historyData), true);
                app($this->flowLogService)->logFlowDefinedModify($logParam, "flow_process&node_id", $nodeId, $routFrom, $userInfo, $saveType);
            } else {
                return ['code' => ['0x000003', 'common']];
            }
        } else {
            return ['code' => ['0x000003', 'common']];
        }
    }

    /**
     * 处理流程节点的数据外发信息
     *
     * @param [type] $outsend
     * @param [type] $flowId
     * @return void
     */
    public function handleOutsendInfoForNode($outsend, $flowId)
    {
        $result["outsend"] = $outsend;
        $sequence = 1;
        foreach ($outsend as $key => $value) {
            $moduledivCollection = [];
            $databasedivCollection = [];
            $field_relation = app($this->flowOutsendFieldsRepository)->getList($value['id']);
            if ($field_relation) {
                $field_relation = $field_relation->toArray();
            }
            if ($value['flow_outsend_mode'] == 1) {
            } else if ($value['flow_outsend_mode'] == 2) {
                $porcessformInfo = app($this->flowTypeRepository)->getDetail($flowId);
                $porcessformId = $porcessformInfo->form_id ?? 0;
                foreach ($field_relation as $num => $field_relations) {
                    if (!isset($value['custom_module_menu']) || empty($value['custom_module_menu'])) {
                        continue;
                    }
                    $receiveFiledsTitle = "";
                    if ($value['module_select'] == 1) {
                        $receiveFiledsTitle = $this->flowOutsendGetModuleFieldTitle(['moduleId' => $value['custom_module_menu'], 'id' => $field_relations['receive_fields'], 'module' => $value['module'], 'receive_fields_parent' => $field_relations['receive_fields_parent']]);
                    } else if ($value['module_select'] == 2) {
                        $receiveFiledsTitle = $this->flowOutsendGetCustomModuleFieldTitle(['customModuleId' => $value['module'], 'customModuleMenu' => $value['custom_module_menu'], 'id' => $field_relations['receive_fields'], 'receive_fields_parent' => $field_relations['receive_fields_parent']]);
                    }
                    $moduledivCollection[$num]['receiveFiledsTitle'] = $receiveFiledsTitle;
                    $moduledivCollection[$num]['formFileds'] = $field_relations['porcess_fields'];
                    $moduledivCollection[$num]['receiveFileds'] = $field_relations['receive_fields'];
                    $moduledivCollection[$num]['porcess_fields_parent'] = $field_relations['porcess_fields_parent'];
                    $moduledivCollection[$num]['parentMenu'] = $field_relations['receive_fields_parent'];
                    $moduledivCollection[$num]['additional'] = $field_relations['additional_field'];
                    $moduledivCollection[$num]['analysis_url'] = $field_relations['analysis_url'];

                    // 格式化返回表单字段
                    $formFieldRes = $this->parseFormFieldForOutsend($field_relations['porcess_fields'], $porcessformId);
                    $moduledivCollection[$num]['formFiledsTitle'] = $formFieldRes['formFiledsTitle'];
                    $moduledivCollection[$num]['oneToMore'] = $formFieldRes['oneToMore'];
                }
            } else if ($value['flow_outsend_mode'] == 3) {
                $porcessformInfo = app($this->flowTypeRepository)->getDetail($flowId);
                $porcessformId = $porcessformInfo->form_id ?? 0;
                foreach ($field_relation as $num => $field_relations) {
                    $databasedivCollection[$num]['formFileds'] = $field_relations['porcess_fields'];
                    $databasedivCollection[$num]['receiveFileds'] = $field_relations['receive_fields'];
                    $databasedivCollection[$num]['porcess_fields_parent'] = $field_relations['porcess_fields_parent'];
                    $databasedivCollection[$num]['receive_fields_parent'] = $field_relations['receive_fields_parent'];
                    $databasedivCollection[$num]['additional_field'] = $field_relations['additional_field'];
                    $databasedivCollection[$num]['analysis_url'] = $field_relations['analysis_url'];

                    // 格式化返回表单字段
                    $formFieldRes = $this->parseFormFieldForOutsend($field_relations['porcess_fields'], $porcessformId);
                    $databasedivCollection[$num]['formFiledsTitle'] = $formFieldRes['formFiledsTitle'];
                    $databasedivCollection[$num]['oneToMore'] = $formFieldRes['oneToMore'];
                    $databasedivCollection[$num]['receiveFiledsTitle'] = $field_relations['receive_fields'];
                }
            }

            //20191125,zyx,内部模块外发中的自定义表单部分，需要拼接标签布局的名称
            $moduledivCollection = app($this->formModelingService)->handleCustomFieldsTitle($value['custom_module_menu'], $moduledivCollection);

            $result["outsend"][$key]['modulerelations'] = $moduledivCollection;
            unset($result["outsend"][$key]['outsend_has_many_fields']);
            $result["outsend"][$key]['databaserelations'] = $databasedivCollection;
            $result["outsend"][$key]['flow_outsend_mode'] = isset($value['flow_outsend_mode']) ? $value['flow_outsend_mode'] : '';
            $result["outsend"][$key]['sequence'] = $sequence;
            $result["outsend"][$key]['databaseformFileds'] = null;
            $result["outsend"][$key]['moduleformFileds'] = null;
            $result["outsend"][$key]['moduleFileds'] = null;
            $result["outsend"][$key]['databaseFileds'] = null;
            $result["outsend"][$key]['flow_outsend_timing'] = $value['flow_outsend_timing'];
            $result["outsend"][$key]['send_back_forbidden'] = $value['send_back_forbidden'];
            $result["outsend"][$key]['module'] = isset($value['module']) ? $value['module'] : '';
            $result["outsend"][$key]['outsend_url'] = isset($value['outsend_url']) ? $value['outsend_url'] : '';
            $result["outsend"][$key]['table_name'] = isset($value['table_name']) ? $value['table_name'] : '';
            $result["outsend"][$key]['database_id'] = isset($value['database_id']) ? $value['database_id'] : '';
            $result["outsend"][$key]['premise'] = isset($value['premise']) ? $value['premise'] : '';
            // 20200402，zyx,项目外发增加参数
            $result["outsend"][$key]['baseFiles'] = $value['base_files'] ? trans("outsend.from_type_" . 160 . ".baseFiles") : '';
            if ($result["outsend"][$key]['baseFiles']) {
                $result["outsend"][$key]['baseId'] = config('flowoutsend.from_type')[160]['baseId'];
            }
            $sequence++;
        }
        return $result['outsend'];
    }

    /**
     * 通过run_id找出流程各节点外发失败记录
     *
     * @author   zyx
     *
     * @param    mixed  $param
     *
     * @return   array
     * */
    public function getOutsendList($param)
    {
        $returnResult = [];

        $result = app($this->flowRunProcessRepository)->getFlowOutsendList($param);
        if ($result) {
            foreach ($result as $k => $v) {
                // 按照步骤process_id拆分展示
                if (isset($returnResult[$v['process_id']])) {
                    continue;
                }

                $flowProcessInfo = [];
                // 节点信息
                $flowProcessInfo = $v['flow_run_process_has_one_flow_process'];
                unset($v['flow_run_process_has_one_flow_process']);
                if (!$flowProcessInfo) {
                    continue;
                }
                $v['process_name'] = $flowProcessInfo['process_name'];

                // 节点外发配置
                $outsendConfig = $flowProcessInfo['flow_process_has_many_outsend'];
                if (!$outsendConfig) {
                    continue;
                }

                // 取出节点所有的外发配置ID并键值反转
                $outsendConfigIdsArr = array_column($outsendConfig, 'id');
                $outsendConfigIdsArrFlip = array_flip(array_column($outsendConfig, 'id'));

                $search = [
                    'relation_table' => ['flow_outsend'],
                    'relation_id' => [$outsendConfigIdsArr, 'in'],
                    'log_relation_id_add' => [$v['flow_run_process_id']],
                    'log_operate' => ['out_send'],
                ];
                // 当前运行节点外发日志App\EofficeApp\System\Log\Entities\SystemFlowLogEntity
                $outsendLogs = app('App\EofficeApp\LogCenter\Repositories\LogRepository')->getLogList(['search' => $search], 'workflow');
                // $outsendLogs = $v['flow_run_process_has_many_flow_outsend_log'];
                // unset($v['flow_run_process_has_many_flow_outsend_log']);

                if (!$outsendLogs) {
                    continue;
                }

                $logRes = [];
                // 处理外发日志
                foreach ($outsendLogs as $outsendValue) {
                    $outsendValue = is_object($outsendValue) ? json_decode(json_encode($outsendValue), true) : $outsendValue;
                    // 新数据is_failed失败为1，老数据is_failed为null但外发结果包含‘失败’字样
                    if ($outsendValue['is_failed'] || (strpos($outsendValue['log_content'], '失败') !== false)) {
                        // 外发失败且当前外发配置仍存在的时候塞进统计数组
                        if (isset($outsendConfigIdsArrFlip[$outsendValue['relation_id']])) {
                            $outsendValue['show_id'] = $outsendConfigIdsArrFlip[$outsendValue['relation_id']] + 1;
                            $logRes[] = $outsendValue;
                        }
                    }
                }
                if (!$logRes) {
                    continue;
                }

                $v['log'] = array_values($logRes);
                if ($v['branch_serial']) {
                    $v['process_name'] = $v['flow_serial'] . '-' . $v['branch_serial'] . '-' . $v['process_serial'] . '：' . $v['process_name'];
                } else {
                    $v['process_name'] = $v['flow_serial'] . '：' . $v['process_name'];
                }
                $returnResult[$v['process_id']] = $v;
            }
        }

        return $returnResult;
    }

    /**
     * 格式化返回表单字段列表，供流程外发使用
     *
     * @param array $field_relations
     * @param [type] $formId
     * @return void
     */
    public function parseFormFieldForOutsend($porcess_fields, $formId)
    {
        // 流程相关字段名称
        $flowInfoTitleArr = [
            'flow_id' => trans("flow.0x030090"), // 定义流程ID
            'run_id' => trans("flow.0x030091"), // 运行流程ID
            'form_id' => trans("flow.0x030092"), // 流程表单ID
            'node_id' => trans("flow.0x030093"), // 流程节点ID
            'process_id' => trans("flow.0x030094"), // 运行步骤ID
            'attachments' => trans("flow.0x030095"), // 相关附件
            'feedback' => trans("flow.0x030096"), // 签办反馈
            'document' => trans("flow.0x030097"), // 相关文档
            'flow_creator' => trans("flow.0x030098"), // 流程创建人ID
            'run_name' => trans("flow.0x030073"), // 流程名称
            'flow_submit_user' => trans("flow.0x030099"), // 流程提交人ID
        ];

        // 配对字段
        $tempProcessFieldsArr = explode(',', trim($porcess_fields, ','));
        // 是否为多对一(多表单->一模块)配对
        $oneToMore = (count($tempProcessFieldsArr) > 1) ? true : false;

        $formFieldsTitle = '';
        foreach ($tempProcessFieldsArr as $key => $control) {
            // 多个字段时用,拼接
            // 如果是流程相关字段直接返回，否则为表单字段
            $formFieldsTitle .= ($flowInfoTitleArr[$tempProcessFieldsArr[$key]] ?? app($this->flowService)->getFlowFormControlStructureControlTitle(["form_id" => $formId, 'control_id' => $control])) . ',';
        }

        return ['formFiledsTitle' => trim($formFieldsTitle, ','), 'oneToMore' => $oneToMore];
    }

    /**
     * 返回表单控件数据
     *
     * @param [string] $key
     * @param [type] $value
     * @param [array] $controlIdToAttr
     * @param [array] $controlKeyValue
     * @author zyx 20200602
     * @return
     */
    public function handleControlData($key, $value, $controlIdToAttr, $controlKeyValue, $userId, $attachmentControlIds) {
        switch ($controlKeyValue[$key]) {
            case 'radio': //单选处理
                $res = $this->getRadioKey($value, $controlIdToAttr[$key]);
                break;
            case 'checkbox': //多选处理
                $res = $this->getCheckboxKey($value, $controlIdToAttr[$key]);
                break;
            case 'countersign': //会签控件处理
                $res = isset($value[0]['countersign_content']) ? strip_tags($value[0]['countersign_content']) : '';
                break;
            case 'upload': // 附件配对时需要复制附件信息，生成新的附件id
                $res = '';
                if ($value) {
                    // 明细里的附件是多个单元的二维数组
                    if (is_array($value)) {
                        $valueValStr = [];
                        foreach ($value as $valueVal) {
                            if (in_array($key,$attachmentControlIds)) {
                                $strValue = explode(',', $valueVal);
                                foreach ($strValue as $_key=>$_strValue) {
                                    $newAttachmentId = app($this->attachmentService)->attachmentCopy([['source_attachment_id' => $_strValue]], ['user_id'=>$userId]);
                                    if ($newAttachmentId && isset($newAttachmentId[0]['attachment_id'])) {
                                        $strValue[$_key] = $newAttachmentId[0]['attachment_id'];
                                    }
                                }
                                $valueVal = implode(',',$strValue);
                            }
                            $valueValStr[] = $valueVal;
                        }
                        $res = $valueValStr;
                    } else {
                        $res = $value;
                        if (in_array($key,$attachmentControlIds)) {
                            $strValue = explode(',', $value);
                            foreach ($strValue as $_key=>$_strValue) {
                                $newAttachmentId = app($this->attachmentService)->attachmentCopy([['source_attachment_id' => $_strValue]], ['user_id'=>$userId]);
                                if ($newAttachmentId && isset($newAttachmentId[0]['attachment_id'])) {
                                    $strValue[$_key] = $newAttachmentId[0]['attachment_id'];
                                }
                            }
                            $res = implode(',',$strValue);
                        }
                    }
                }
                break;
            default:
                $res = $value;
        }
        return $res;
    }

    /**
     * 验证处理附件字段，如果不存在则需要返回错误
     *
     * @param array $sendDatas
     * @return bool
     */
    public function verifyAttachmentsFields($sendDatas) {
        // 先看主配对字段中是否存在附件字段外发
        $attachmentsIntersectKey = array_intersect_key(['attachment_id' => 'attachment_id', 'attachments' => 'attachments', 'attachment_ids' => 'attachment_ids'], $sendDatas);
        if ($attachmentsIntersectKey) {
            if (
                (isset($attachmentsIntersectKey['attachment_id']) && ($sendDatas['attachment_id'] == 'attachment does not exists')) ||
                (isset($attachmentsIntersectKey['attachments']) && ($sendDatas['attachments'] == 'attachment does not exists')) ||
                (isset($attachmentsIntersectKey['attachment_ids']) && ($sendDatas['attachment_ids'] == 'attachment does not exists'))
            ) {
                return true;
            }
        }
        // 再去验证项目补充数据中是否有附件字段外发
        if (isset($sendDatas['additional'])) {
            foreach ($sendDatas['additional'] as $additionalVal) {
                $additionalAttachmentsIntersectKey = array_intersect_key(['attachment_id' => 'attachment_id', 'attachments' => 'attachments', 'attachment_ids' => 'attachment_ids'], $additionalVal);
                if ($additionalAttachmentsIntersectKey) {
                    if (
                        (isset($additionalAttachmentsIntersectKey['attachment_id']) && ($additionalVal['attachment_id'] == 'attachment does not exists')) ||
                        (isset($additionalAttachmentsIntersectKey['attachments']) && ($additionalVal['attachments'] == 'attachment does not exists')) ||
                        (isset($additionalAttachmentsIntersectKey['attachment_ids']) && ($additionalVal['attachment_ids'] == 'attachment does not exists'))
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 处理配对字段中的附件字段
     *
     * @param [array] $sendDataAdditional
     * @return array
     */
    public function handleModuleAdditional($sendDataAdditional) {
        //解析任务
        $maxCount = 0;
        foreach ($sendDataAdditional as $_field => $_fieldvalue) {
            if (is_array($_fieldvalue)) {
                $maxCount = count($_fieldvalue) > $maxCount ? count($_fieldvalue) : $maxCount;
            }
        }
        $additionalArr = [];
        // 多个任务
        if ($maxCount > 0) {
            for ($i = 0;$i < $maxCount; $i++) {
                foreach ($sendDataAdditional as $_field => $_fieldvalue) {
                    if (is_array($_fieldvalue)) {
                        if (isset($_fieldvalue[$i])) {
                            $additionalArr[$i][$_field] = $_fieldvalue[$i];
                        } else {
                            $additionalArr[$i][$_field] = '';
                        }
                    } else {
                        $additionalArr[$i][$_field] = $_fieldvalue;
                    }
                }
            }
        // 单个任务直接返回
        } else {
            $additionalArr = $sendDataAdditional ? [$sendDataAdditional] : [];
        }

        return $additionalArr;
    }

    /**
     * 处理配对字段中的非附加字段
     *
     * @param [string] $processField 表单和流程字段
     * @paran [array] $data 外发配置数据
     * @param [array] $param
     * @return void
     */
    public function handleModuleFields($processField, $data, $param) {
        switch ($processField) {
            case 'flow_id':
                $receiveData = $param['flow_id'];
                break;
            case 'run_id':
                $receiveData = $param['run_id'];
                break;
            case 'form_id':
                $receiveData = $param['form_id'] ?? (!empty($param['flow_id']) ? (app($this->flowTypeRepository)->getFieldValue('form_id', ['flow_id' => $param['flow_id']]) ?? 0) : 0);
                break;
            case 'run_name':
                $receiveData = app($this->flowRunRepository)->getFieldValue('run_name', ['run_id' => $param['run_id']]) ?? [];
                break;
            case 'node_id':
                $receiveData = $param['node_id'];
                break;
            case 'process_id':
                $receiveData = $param['process_id'];
                break;
            case 'attachments':
                $receiveData = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'flow_run', 'entity_id' => ["run_id" => [$param['run_id']]]]) ?? '';
                // 多个附件的时候会返回一个多个单元的数组，后面的明细解析会有问题，这里拼成字符串返回
                if (is_array($receiveData)) {
                    foreach ($receiveData as $key => $value) {
                        $newAttachmentId = app($this->attachmentService)->attachmentCopy([['source_attachment_id' => $value]], ['user_id'=>$param['user_id']]);
                        if ($newAttachmentId && isset($newAttachmentId[0]['attachment_id'])) {
                            $receiveData[$key] = $newAttachmentId[0]['attachment_id'];
                        }
                    }
                    $receiveData = implode(',', $receiveData);
                }else {
                    if($receiveData) {
                        $newAttachmentId = app($this->attachmentService)->attachmentCopy([['source_attachment_id' => $receiveData]], ['user_id'=>$param['user_id']]);
                        if ($newAttachmentId && isset($newAttachmentId[0]['attachment_id'])) {
                            $receiveData = $newAttachmentId[0]['attachment_id'];
                        }
                    }
                }
                break;
            case 'feedback':
                $feedbacks = app($this->flowRunService)->getFeedbackRealize($data, $param['run_id']);
                $receiveData = ($feedbacks && isset($feedbacks['list'])) ? $feedbacks['list'] : [];
                break;
            case 'document':
                $documents = app($this->flowRunRepository)->getFieldValue('link_doc', ['run_id' => $param['run_id']]);
                $receiveData = $documents ? $documents : '';
                break;
            case 'flow_creator':
                $creator = app($this->flowRunRepository)->getFieldValue('creator', ['run_id' => $param['run_id']]);
                $receiveData = $creator ? ((is_array($creator) && isset($creator[0])) ? $creator[0] : $creator) : '';
                break;
            case 'flow_submit_user':
                $receiveData = $param['user_id'];
                break;
            default:
                $receiveData = '';
                break;
        }
        return $receiveData;
    }
}
