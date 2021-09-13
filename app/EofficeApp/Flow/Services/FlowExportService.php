<?php

namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;

use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/**
 * 流程导出
 */
class FlowExportService extends FlowBaseService
{

    public function __construct()
    {
        parent::__construct();
    }

    //获取抄送流程导出数据
    function exportFlowCopyData($data)
    {
        $userInfo = isset($data['user_info']) ? $data['user_info'] : [];
        $param = $data;
        $flowCopyList = app($this->flowService)->flowCopyList($data, $userInfo);
        $header = [
            "run_seq" => trans("flow.0x030029"),  // 流水号
            "run_name" => trans("flow.0x030030"), // 流程标题
            "flow_name" => trans("flow.0x030073"), // 流程名称
            // "instancy_type|instancyTypeFilter" => trans("flow.0x030067"), // 紧急程度
            "instancy_type" => trans("flow.0x030067"), // 紧急程度
            "creator" => trans("flow.0x030074"), // 创建人
            "create_time" => trans("flow.0x030075"), // 创建时间
            "process_name" => trans("flow.0x030082"), // 最新步骤
            "copy_user" => trans("flow.0x030084"), // 抄送人员
            "copy_time" => trans("flow.0x030085"), // 抄送时间
        ];
        $flowIds = [];
        $flowData = [];
        $data = [];
        foreach ($flowCopyList['list'] as $k => $v) {
            if (!in_array($v['flow_id'], $flowIds)) {
                $flowIds[] = $v['flow_id'];
            }
            $data[$k]['run_seq'] = $v['flow_copy_has_one_flow_run']['run_seq'] ? strip_tags($v['flow_copy_has_one_flow_run']['run_seq']) .' ' : '';
            $data[$k]['run_name'] = $v['flow_copy_has_one_flow_run']['run_name'];
            $data[$k]['flow_name'] = $v['flow_copy_belongs_to_flow_type']['flow_name'] ?? '';
            $data[$k]['form_id'] = $v['flow_copy_belongs_to_flow_type']['form_id'] ?? '';
            $data[$k]['instancy_type'] = app($this->flowSettingService)->getInstancyName($v['flow_copy_has_one_flow_run']['instancy_type']);
            $data[$k]['creator'] = app($this->userService)->getUserName($v['flow_copy_has_one_flow_run']['creator']);
            $data[$k]['create_time'] = $v['flow_copy_has_one_flow_run']['create_time'];
            $maxFlowSerial = app($this->flowRunProcessRepository)->getFieldMaxValue('flow_serial', [
                'run_id' => [$v['run_id']]
            ]);
            $processName = app($this->flowRunService)->getLatestSteps($v['flow_copy_belongs_to_flow_type']['flow_type'], $v['run_id'], $maxFlowSerial);
            $data[$k]['process_name'] = $processName;
            $data[$k]['copy_user'] = $v['flow_copy_has_one_user']['user_name'];
            $data[$k]['copy_time'] = date('Y-m-d H:i:s', $v['copy_time']);
            $data[$k]['flow_id'] = $v['flow_id'];
            $data[$k]['run_id'] = $v['run_id'];
            $flowData[$v['flow_id']][] = $data[$k];
        }
        $param["formExportParams"] = true;
        return $this->getFlowExportFormData($header, $flowData, "", $param, $flowIds);
    }

    /**
     * 【流程导出】 获取超时流程导出数据;
     *
     * @param  [type]          $data [description]
     *
     * @return [type]                [description]
     */
    function exportFlowOverTimeData($data)
    {
        $userInfo = isset($data['user_info']) ? $data['user_info'] : [];
        $flowOverTimeList = app($this->flowService)->getOvertimeList($data, $userInfo);
        $header = [
            "run_seq" => ['data' => trans("flow.0x030029"), 'style' => ['width' => '20']],// 流水号
            "run_name" => ['data' => trans("flow.0x030030"), 'style' => ['width' => '20']], // 流程标题
            "flow_name" => ['data' => trans("flow.0x030073"), 'style' => ['width' => '20']], // 流程名称
            // "instancy_type|instancyTypeFilter" => trans("flow.0x030067"), // 紧急程度
            "instancy_type" => trans("flow.0x030067"), // 紧急程度
            "creator" => trans("flow.0x030074"), // 创建人
            "create_time" => ['data' => trans("flow.0x030075"), 'style' => ['width' => '20']], // 创建时间
            "process_name" => ['data' => trans("flow.0x030076"), 'style' => ['width' => '20']], // 步骤名称
            "processTypeString" => trans("flow.0x030077"), // 步骤状态
            "handle_user" => ['data' => trans("flow.0x030078"), 'style' => ['width' => '20']], // 办理人员
            "transact_time" => ['data' => trans("flow.0x030170"), 'style' => ['width' => '20']], // 接收时间
            "receive_time" => ['data' => trans("flow.0x030171"), 'style' => ['width' => '20']],  // 查看时间
            "process_time" => ['data' => trans("flow.0x030079"), 'style' => ['width' => '20']],  // 提交时间
            "limit_date" => ['data' => trans("flow.0x030177"), 'style' => ['width' => '20']], // 催促时间
            "overTimeString" => ['data' => trans("flow.0x030081"), 'style' => ['width' => '20']], // 超时时间
        ];

        // 用户状态数组
        $userStatusArr = [0 => '[' . trans('flow.deletion') . ']', 1 => '', 2 => '[' . trans('flow.resignation') . ']'];

        $data = [];
        foreach ($flowOverTimeList['list'] as $k => $v) {
            $data[$k]['run_seq'] = $v['flow_run_process_belongs_to_flow_run']['run_seq'] ? strip_tags($v['flow_run_process_belongs_to_flow_run']['run_seq']).' ' : '';
            $data[$k]['run_name'] = $v['flow_run_process_belongs_to_flow_run']['run_name'];
            $data[$k]['flow_name'] = $v['flow_run_process_belongs_to_flow_type']['flow_name'] ?? '';
            $data[$k]['instancy_type'] = app($this->flowSettingService)->getInstancyName($v['flow_run_process_belongs_to_flow_run']['instancy_type']);
            $data[$k]['creator'] = app($this->userService)->getUserName($v['flow_run_process_belongs_to_flow_run']['creator']);
            $data[$k]['create_time'] = $v['flow_run_process_belongs_to_flow_run']['create_time'];
            // $processName = !empty($v['flow_serial']) ? $v['flow_serial'] . '：' : '';
            // if (isset($v['free_current_process_name'])) {
            //     $processName .= $v['free_current_process_name'];
            // } else if (isset($v['current_process_name'])) {
            //     $processName .= $v['current_process_name'];
            // }
            $data[$k]['process_name'] = $v['current_steps'] ?? '';
            $data[$k]['processTypeString'] = $v['processTypeString'];
            $data[$k]['handle_user'] = $v['hostFlagString'] . ':' . $v['flow_run_process_has_one_user']['user_name']. ($userStatusArr[$v['flow_run_process_has_one_user_system_info']["user_status"]] ?? '');
            $data[$k]['transact_time'] = $v['receive_time'] ?? '';
            $data[$k]['receive_time']  = $v['process_time'] ?? '';
            $data[$k]['process_time']  = $v['deliver_time'] ?? '';
            $data[$k]['limit_date'] = $v['limit_date'];
            $data[$k]['overTimeString'] = $v['overTimeString'];
        }
        return compact('header', 'data');
        // $allExportData = [];
        // foreach ($flowIds as $flowId) {
        //     $allExportData[] = $this->getFlowExportFormData($header, $flowData[$flowId], $flowId, $param)[0];
        // }
        // return $allExportData;
    }

    /**
     * 【流程导出】 获取流程监控导出数据;
     *
     * @param  [type]          $data [description]
     *
     * @return [type]                [description]
     */
    public function exportFlowSuperviseData($data)
    {
        $param = $data;
        // 获取用户部门角色信息用于查询监控权限
        $userInfo = [];
        if (isset($data['user_id'])) {
            $userInfo = app($this->userService)->getUserDeptIdAndRoleIdByUserId($data['user_id']);
            if (!empty($userInfo) && isset($userInfo['dept_id']) && isset($userInfo['role_id'])) {
                $userInfo['role_id'] = explode(',', $userInfo['role_id']);
            }
            $userInfo['user_id'] = $data['user_id'];
        }
        $flowMonitorList = app($this->flowService)->getMonitorList($data, $userInfo);
        $header = [
            "run_seq" => trans("flow.0x030029"),  // 流水号
            "run_name" => trans("flow.0x030030"), // 流程标题
            "flow_name" => trans("flow.0x030073"), // 流程名称
            // "instancy_type|instancyTypeFilter" => trans("flow.0x030067"), // 紧急程度
            "instancy_type" => trans("flow.0x030067"), // 紧急程度
            "creator" => trans("flow.0x030074"), // 创建人
            //"create_time" => trans("flow.0x030075"), // 创建时间
            "transact_time" => trans("flow.0x030153"), // 办理时间
            "process_name" => trans("flow.0x030082"), // 最新步骤
            "processTypeString" => trans("flow.0x030077"), // 步骤状态
            "un_handle_user_name" => trans("flow.0x030083"), // 最新步骤未办理人
        ];

        // 用户状态数组
        $userStatusArr = [0 => '[' . trans('flow.deletion') . ']', 1 => '', 2 => '[' . trans('flow.resignation') . ']'];

        $flowIds = [];
        $flowData = [];
        $data = [];
        foreach ($flowMonitorList['list'] as $k => $v) {
            $data[$k]['run_seq'] = $v['run_seq'] ? strip_tags($v['run_seq']). ' ' : '';
            $data[$k]['run_name'] = $v['run_name'];
            $data[$k]['flow_name'] = $v['flow_run_has_one_flow_type']['flow_name'] ?? '';
            $data[$k]['form_id'] = $v['flow_run_has_one_flow_type']['form_id'] ?? '';
            $data[$k]['instancy_type'] = app($this->flowSettingService)->getInstancyName($v['instancy_type']);
            $data[$k]['creator'] = app($this->userService)->getUserName($v['creator']);
            //$data[$k]['create_time'] = $v['create_time'];
            $data[$k]['transact_time'] = $v['transact_time'] ?? '';
            $data[$k]['process_name'] = $v['latest_steps'] ?? '';
            if ($v["current_step"] == "0") {
                $status = trans("flow.0x030069"); // 已完成
            } else {
                $status = trans("flow.0x030070"); // 执行中
            }
            $data[$k]['processTypeString'] = $status;
            unset($status);
            $v['un_handle_user_name'] = '';
            if (!empty($v['handle_user_info_arr']) && is_array($v['handle_user_info_arr'])) {
                foreach ($v['handle_user_info_arr'] as $unHandleUserInfo) {
                    $v['un_handle_user_name'] .= $unHandleUserInfo[1] ?? '';
                    if (isset($unHandleUserInfo[2])) {
                        if ($unHandleUserInfo[2] == '2') {
                            $v['un_handle_user_name'] .= '[' . trans('flow.leave_office') . ']';
                        } else if ($unHandleUserInfo[2] == '0') {
                            $v['un_handle_user_name'] .= '[' . trans('flow.delete') . ']';
                        }
                    }
                    $v['un_handle_user_name'] .= ',';
                }
            }
            $data[$k]['un_handle_user_name'] = rtrim($v['un_handle_user_name'] , ',');
            $data[$k]['flow_id'] = $v['flow_id'];
            $data[$k]['run_id'] = $v['run_id'];
            $flowData[$v['flow_id']][] = $data[$k];
            if (!in_array($v['flow_id'], $flowIds)) {
                $flowIds[] = $v['flow_id'];
            }
        }
        $param["formExportParams"] = true;
        return $this->getFlowExportFormData($header, $flowData, "", $param, $flowIds);
        //$allExportData = [];
        //foreach($flowIds as $flowId){
        //$allExportData[] = $this->getFlowExportFormData($header,$flowData[$flowId],$flowId,$param)[0];
        //}
        //return $allExportData;
    }

    /**
     * 【流程导出】 获取流程查询导出数据;
     */
    function exportFlowSearchData($params)
    {
        $userInfo = $params['user_info'] ?? [];
        $userId = $params['user_id'] ?? '';
        $searchResult = app($this->flowService)->getFlowSearchList($params, $userInfo);
        $header = [
            "run_seq" => trans("flow.0x030029"),   // 流水号
            "run_name" => trans("flow.0x030030"),  // 流程标题
            "status" => trans("flow.0x030066"),    // 流程状态
            "instancy_type" => trans("flow.0x030067"), // 紧急程度
            "create_time" => trans("flow.0x030068"), // 流程开始时间
            "end_time" => trans("flow.flow_end_time"), // 流程结束时间
            "process_name" => trans("flow.0x030082"), // 最新步骤
        ];
        $flowIds = [];
        $flowData = [];
        $data = [];
        if (!empty($searchResult['list'])) {
            foreach ($searchResult['list'] as $k => $v) {
                $flowId = isset($v['flow_id']) ? $v['flow_id'] : "";
                if (!in_array($flowId, $flowIds)) {
                    $flowIds[] = $flowId;
                }
                $data[$k]['run_seq'] = html_entity_decode($v['run_seq'], ENT_QUOTES, "UTF-8");
                $data[$k]['run_name'] = htmlentities($v["run_name"], ENT_QUOTES, "utf-8");
                if ($v["current_step"] == "0") {
                    $status = trans("flow.0x030069"); // 已完成
                } else {
                    $status = trans("flow.0x030070"); // 执行中
                }
                $data[$k]['status'] = $status;
                $data[$k]['instancy_type'] = app($this->flowSettingService)->getInstancyName($v["instancy_type"]);
                $data[$k]['create_time'] = $v["create_time"];
                $data[$k]['end_time'] = ($v["current_step"] == "0" ? $v['transact_time'] : "");
                $processName = '';
                if ($v["max_process_id"]) {
                    $processName = trans("flow.0x030011", ['process_id' => $v["max_process_id"]]);
                }
                $flowType = "";
                $formId = "";
                $flowName = "";
                if (isset($v['flow_run_has_one_flow_type']) && isset($v['flow_run_has_one_flow_type']["flow_type"])) {
                    $flowType = $v['flow_run_has_one_flow_type']["flow_type"];
                    $formId = $v['flow_run_has_one_flow_type']["form_id"];
                    $flowName = $v['flow_run_has_one_flow_type']["flow_name"];
                }
                $data[$k]['flow_type'] = $flowType;
                $data[$k]['form_id'] = $formId;
                $data[$k]['flow_name'] = $flowName;
                $data[$k]['process_name'] = $v['latest_steps'] ?? '';
                $data[$k]['flow_id'] = $flowId;
                $data[$k]['run_id'] = $v['run_id'];
                // 调用流程查看的主函数，获取相关信息
                $flowViewPageInfo = app($this->flowService)->getFlowViewPageFlowRunInfo($v['run_id'], ["user_id" => $userId], ["getDataType" => "flow_query"]);
                $nodeId = "";
                if (isset($flowViewPageInfo["node_id"]) && $flowViewPageInfo["node_id"]) {
                    $nodeId = $flowViewPageInfo["node_id"];
                } else if (isset($flowViewPageInfo["flowProcess"]) && $flowViewPageInfo["flowProcess"]) {
                    $nodeId = $flowViewPageInfo["flowProcess"];
                }
                $processId = isset($flowViewPageInfo["processId"]) ? $flowViewPageInfo["processId"] : "";
                // 查字段控制，过滤附件上传控件的下载权限
                $viewPageNodeOperation = [];
                if ($flowType == "2") {
                    $headNodeToggle = $processId == "1" ? "1" : "0";
                    $viewPageNodeOperation = app($this->flowRunService)->getFlowFormControlOperation(["formId" => $formId, "flowId" => $flowId, "headNodeToggle" => $headNodeToggle]);
                } else if ($flowType == "1") {
                    $nodeInfo = app($this->flowService)->getFlowNodeInfo($nodeId);
                    if (isset($nodeInfo["controlOperation"])) {
                        $viewPageNodeOperation = $nodeInfo["controlOperation"];
                    }
                }
                // 查看流程页面，过滤 node_operation
                $filterParam = [
                    "filter_form" => "view",
                    "form_id" => $formId,
                    "flow_type" => $flowType,
                    "hostFlag" => isset($flowViewPageInfo["hostFlag"]) ? $flowViewPageInfo["hostFlag"] : "",
                    "flowSubmitStatus" => isset($flowViewPageInfo["flowSubmitStatus"]) ? $flowViewPageInfo["flowSubmitStatus"] : "",
                    "node_operation" => $viewPageNodeOperation,
                ];
                $nodeOperation = app($this->flowService)->filterFlowNodeOperation($filterParam);
                // 收集允许下载的 控件id 和 流程id 关联
                $allowDownloadControlInfo = [];
                if (!empty($nodeOperation)) {
                    foreach ($nodeOperation as $nodeOperationKey => $nodeOperationValue) {
                        if (array_search("attachmentDownload", $nodeOperationValue) !== false) {
                            array_push($allowDownloadControlInfo, $nodeOperationKey);
                        }
                    }
                }
                $data[$k]['allowDownloadControlInfo'] = $allowDownloadControlInfo;
                $flowData[$flowId][] = $data[$k];
            }
        }
        return $this->getFlowExportFormData($header, $flowData, "", $params, $flowIds);
    }

    /**
     * 流程查询导出的时候，同时导出相关附件，此函数处理相关附件
     * 接收导出的各个参数，返回一个数组，里面放附件(带层级，供zippy压缩)
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function exportFlowSearchDataAndAttachment($params)
    {
        $getDataTimestamp = isset($params['getDataTimestamp']) ? $params['getDataTimestamp'] : "";
        $attachmentInfo = Cache::get('flow_search_export_attachment_info_' . $getDataTimestamp);
        $attachmentInfo = json_decode($attachmentInfo, true);
        Cache::forget('flow_search_export_attachment_info_' . $getDataTimestamp);
        return $attachmentInfo;
    }

    /**
     * 流程查询导出的时候，在export文件里设置了导出，在实际执行导出的时候，再验证一下流程这里的导出开关
     *
     * @param  [type] $params [description]
     * @return [boolean]      [验证结果]
     */
    public function verifyExportFlowSearchDataCompress($params)
    {
        // 去system_params里 根据key flow_search_export_compress_set 读配置，1 开启，2 关闭。
        $flowSearchExportCompressSet = app($this->flowSettingService)->getFlowSettingsParamValueByParamKey("flow_search_export_compress_set");
        return $flowSearchExportCompressSet == "1" ? true : false;
    }

    /**
     * 获取流程导出表单数据字符串;
     *
     */
    function getFlowExportFormData(&$header, &$flowData, $flowId, $param, $flowIds)
    {
        $paramUserInfo = $param["user_info"] ?? [];
        $userInfo = $paramUserInfo["user_info"] ?? [];
        $getDataTimestamp = $param['getDataTimestamp'] ?? "";
        // 流程查询导出，配置了压缩，需要把附件获取并装到一个数组里，然后用key-$getDataTimestamp存到缓存里
        $compress = (isset($param['compress']) && $param['compress'] === true) ? true : false;
        // 附件数组
        $exportAttachmentInfo = [];
        // 附件名重复的附件处理
        $attachmentsNameRepeatRecord = [];
        // 附件目录数组
        // $exportAttachmentRelationFolderInfo = [];
        $sheetArr = [];

        foreach ($flowIds as $flowId) {
            $data = $flowData[$flowId];
            if (!count($data)) {
                continue;
            }

            $formId = $data[0]['form_id'];
            $flow_name = $data[0]['flow_name'];
            // 传递参数
            if (!$formId) {
                continue;
            }

            $exportString = "";
            $column = 1;
            $sheetName = str_replace([":","：","/","\\","?","？","*","[","]","@"], "", $flow_name);
            $sheetName = mb_substr($sheetName, 0, 30);
            if (in_array($sheetName, $sheetArr)) {
                $sheetName .= $flowId;
            } else {
                $sheetArr[] = $sheetName;
            }
            $exportString .= '<Worksheet ss:Name="' . $sheetName . '"><Table><Row ss:Index="1">';
            foreach ($header as $k => $item) {
                $exportString .= '<Cell ss:Index="' . $column . '" ss:StyleID="Headercell"><Data ss:Type="String">' . $item . '</Data></Cell>';
                $column++;
            }

            // 解析导出表单字段
            $controlParseInfo = [];

            // 获取表单控件列表
            // if (Redis::hexists("flow_export", "flow_form_parse_info_" . $formId . '_export')) {
            //     $flowFormParseInfo = unserialize(Redis::hget("flow_export", "flow_form_parse_info_" . $formId . '_export'));
            // } else {
                $flowFormParseInfo = app($this->flowFormService)->getParseForm($formId, ['status' => 'export']);
            //     Redis::hset("flow_export", "flow_form_parse_info_" . $formId . '_export', serialize($flowFormParseInfo));
            // }

            // 如果指定了导出字段则需要对表单控件进行过滤
            $tempFormExportParams = [];
            if (isset($param["formExportParams"])) {
                if (!$flowFormParseInfo) {
                    continue;
                }
                if (is_array($param["formExportParams"]) && !empty($param["formExportParams"])) {
                    foreach ($flowFormParseInfo as $v) {
                        if (isset($param["formExportParams"][$v['control_id']])) {
                            $tempFormExportParams[$v['control_id']] = $v['control_title'];
                        }
                    }
                } else {
                    if (
                        ($param["formExportParams"] === true) ||
                        (!isset($param['search']['flow_id']))
                    ) {
                        foreach ($flowFormParseInfo as $v) {
                            $tempFormExportParams[$v['control_id']] = $v['control_title'];
                        }
                    }
                }
            }

            $formExportParams = $tempFormExportParams;

            // 头部字段数据解析
            if (count($formExportParams) && count($flowFormParseInfo)) {
                // 收集控件，用在下面的数据解析
                foreach ($flowFormParseInfo as $flowFormParseInfoValue) {
                    $controlId = $flowFormParseInfoValue["control_id"];  // 控件ID
                    $controlTitle = $flowFormParseInfoValue["control_title"]; // 控件名称
                    $controlParentId = $flowFormParseInfoValue["control_parent_id"] ?? ""; // 控件父级ID
                    // 明细子项直接跳过
                    if ($controlParentId) {
                        continue;
                    }
                    $controlStructureAttribute = json_decode(json_encode($flowFormParseInfoValue["control_attribute"]), true); // 控件属性

                    // 明细控件
                    if ($flowFormParseInfoValue["control_type"] == "detail-layout") {
                        $dataEfbLayoutInfo = $controlStructureAttribute["data-efb-layout-info"];
                        if (!is_array($dataEfbLayoutInfo)) {
                            $dataEfbLayoutInfo = json_decode($dataEfbLayoutInfo, true);
                        }
                        $layoutControlChildInfo = [];
                        $layoutControlIsAmount = "";
                        if (count($dataEfbLayoutInfo)) {
                            // 明细表格，表头跨列数量
                            $layoutControlColspanCount = 0;
                            $amoutExport = "";

                            // 处理明细内部单元，将二级子项转到一级位置
                            $tmpLayoutArr = [];
                            foreach ($dataEfbLayoutInfo as $dataEfbLayoutInfoValue) {
                                if (
                                    ($dataEfbLayoutInfoValue['type'] == 'column') &&
                                    (isset($dataEfbLayoutInfoValue['children'])) &&
                                    count($dataEfbLayoutInfoValue['children'])
                                ) {
                                    foreach ($dataEfbLayoutInfoValue['children'] as $grandchildValue) {
                                        $tmpLayoutArr[] = $grandchildValue;
                                    }
                                } else {
                                    $tmpLayoutArr[] = $dataEfbLayoutInfoValue;
                                }
                            }
                            $dataEfbLayoutInfo = $tmpLayoutArr;

                            foreach ($dataEfbLayoutInfo as $dataEfbLayoutInfoValue) {
                                $LayoutItemRowId = $dataEfbLayoutInfoValue["id"]; // 明细ID
                                $LayoutItemRowTitle = $dataEfbLayoutInfoValue["title"]; // 明细名称
                                $LayoutItemRowIsAmount = $dataEfbLayoutInfoValue["isAmount"] ?? "";
                                $LayoutItemRowType = $dataEfbLayoutInfoValue["type"] ?? "";
                                // 在导出字段里面的控件，才会被导出
                                if (isset($formExportParams[$LayoutItemRowId]) && $formExportParams[$LayoutItemRowId]) {
                                    $isLayout = false;
                                    $layout = '';
                                    if ($LayoutItemRowType == 'detail-layout') {
                                        $isLayout = true;
                                        $layout = '【暂不支持导出】';
                                    }
                                    $exportString .= '<Cell ss:Index="' . $column . '" ss:StyleID="Headercell"><Data ss:Type="String">' . $LayoutItemRowTitle. ($isLayout ? $layout : '') . '</Data></Cell>';
                                    $column++;
                                    $layoutControlChildInfo[$LayoutItemRowId] = $dataEfbLayoutInfoValue;
                                    $layoutControlColspanCount++;
                                    if ($LayoutItemRowIsAmount) {
                                        $layoutControlIsAmount = "1";
                                        $amoutExport = $LayoutItemRowTitle;
                                        $layoutControlColspanCount++;
                                    }
                                }
                                // 合计列
                                if ($amoutExport) {
                                    $exportString .= '<Cell ss:Index="' . $column . '" ss:StyleID="Headercell"><Data ss:Type="String">' . $amoutExport . "(" . trans("flow.0x030142") . ")" . '</Data></Cell>';
                                    $column++;
                                }
                                unset($amoutExport);
                                $amoutExport = "";
                            }
                        }
                        $controlParseInfo[$controlId] = ["title" => $controlTitle, "type" => $flowFormParseInfoValue["control_type"], "child" => $layoutControlChildInfo, "isAmount" => $layoutControlIsAmount];
                    } else {
                        // 不是明细的子项，才会被收集到头部
                        // 在导出字段里面的控件，才会被导出
                        if (!$controlParentId && isset($formExportParams[$controlId]) && $formExportParams[$controlId]) {
                            $exportString .= '<Cell ss:Index="' . $column . '" ss:StyleID="Headercell"><Data ss:Type="String">' . $controlTitle . '</Data></Cell>';
                            $column++;
                            $controlParseInfo[$controlId] = ["title" => $controlTitle, "type" => $flowFormParseInfoValue["control_type"], "attribute" => $controlStructureAttribute];
                        }
                    }
                }
            }
            $exportString .= '</Row>';
            $count = 0;
            //当前行数
            $data_row = 2;
            //每行增量
            $row_increase = 1;
            //单行数据合并行数
            $row_index = 1;
            foreach ($data as $dataValue) {
                $runName = $dataValue["run_name"];
                // if (isset($dataValue["allowDownloadControlInfo"])) {
                //     $allowDownloadControlInfo = $dataValue["allowDownloadControlInfo"];
                //     unset($dataValue["allowDownloadControlInfo"]);
                // } else {
                //     $allowDownloadControlInfo = [];
                // }

                $merge_detail = [];
                $merge_sort = [];
                $mergeDetailRelationFolderInfo = [];
                // 调用打开查看流程的函数，获取到表单模板信息
                $formTemplateParam = [
                    "currentUser" => $userInfo["user_id"] ?? "",
                    "flow_id" => $dataValue["flow_id"] ?? "",
                    "page" => "view",
                    "run_id" => $dataValue["run_id"] ?? "",
                    "user_name" => $userInfo["user_name"] ?? "",
                    "getDataType" => "flow_query",
                    "data_template" => "search",
                    "flow_export" => "asyn",
                    "funcQueryFrom" => "flow_export",
                ];
                $viewPageMainInfo = app($this->flowService)->getFlowHandlePageMainData($formTemplateParam, $userInfo);

                // 流程模板规则
                $formTemplateRuleInfo = $viewPageMainInfo["formTemplateRuleInfo"] ?? [];
                // 字段控制权限
                $nodeOperation = $viewPageMainInfo['node_operation'] ?? [];

                // 取流程表单数据
                $flowParseDataParam = [
                    "flowId" => $dataValue["flow_id"],
                    "formId" => $formId,
                    "runId" => $dataValue["run_id"],
                    "status" => "view",
                    // 传 formTemplateRuleInfo 和 flow_query ，在解析表单值那里，会去根据表单模板规则获取数据
                    "formTemplateRuleInfo" => json_encode($formTemplateRuleInfo),
                    "getDataType" => "flow_query",
                    "nodeId" => $viewPageMainInfo['node_id'] ?? ($viewPageMainInfo['flowProcess'] ?? ''),
                ];
                $flowFormParse = app($this->flowService)->getFlowFormParseData($flowParseDataParam, $userInfo);
                $parseFormStructure = $flowFormParse["parseFormStructure"];
                $parseData = $flowFormParse["parseData"];

                // 固定列数据解析
                $times = 0;
                $exportString .= '<Row ss:Index="' . $data_row . '">';
                $inner_column = 1;
                foreach ($dataValue as $k => $v) {
                    // if (in_array($k, ["flow_id", "run_id", "form_id", "flow_type", "flow_name"])) {
                    if (!isset($header[$k])) {
                        continue;
                    }
                    $value = is_string($v) ? strip_tags($v) : $v;
                    // if ($count == 0) {
                    //     //$allExportData[0]['header'][$times]['style']['width'] = $this->getHeaderWidth($value);
                    // }
                    $exportString .= '<Cell ss:Index="' . $inner_column . '"  place><Data ss:Type="String">' . $value . '</Data></Cell>';
                    $inner_column++;
                    $times++;
                }
                // 解析导出表单字段，拼上值
                if (count($controlParseInfo)) {
                    foreach ($controlParseInfo as $controlId => $controlParseItem) {
                        // 控件值
                        $controlValue = $parseData[$controlId] ?? "";
                        // 控件名称
                        $controlTitle = $controlParseItem["title"] ?? "";
                        $controlType = $controlParseItem["type"] ?? "";
                        $controlAmountValue = $parseData[$controlId . "_amount"] ?? [];

                        // 明细
                        if (isset($controlParseItem["type"]) && $controlParseItem["type"] == "detail-layout") {
                            $layoutChildInfo = $controlParseItem["child"];
                            $layoutChildIsAmount = $controlParseItem["isAmount"];
                            if (count($layoutChildInfo)) {
                                // 生成一个table
                                if ($controlValue && is_array($controlValue) && count($controlValue)) {
                                    // 计算合计值
                                    $amountData = [];
                                    if ($layoutChildIsAmount) {
                                        foreach ($layoutChildInfo as $layoutChildId => $layoutChildValue) {
                                            $controlAmountItemValue = "";
                                            if (isset($layoutChildValue["isAmount"]) && $layoutChildValue["isAmount"]) {
                                                $controlAmountItemValue = isset($controlAmountValue[$layoutChildId]) ? $controlAmountValue[$layoutChildId] : "";
                                                $layoutChildAmount = isset($layoutChildValue["amount"]) ? $layoutChildValue["amount"] : [];
                                                // decimalPlaces 是否设置保留位数 boolean | decimalPlacesDigit 保留的位数 | rounding 是否四舍五入
                                                $dataEfbDecimalPlaces = $layoutChildAmount['decimalPlaces'] ?? false;
                                                $controlAmountItemValue = string_numeric($controlAmountItemValue);
                                                if ($dataEfbDecimalPlaces && is_numeric($controlAmountItemValue)) {
                                                    $dataEfbDecimalPlacesDigit = (int)($layoutChildAmount['decimalPlacesDigit'] ?? 2);
                                                    $dataEfbRounding = $layoutChildAmount['rounding'] ?? false;
                                                    $controlAmountItemValue = decimal_places($controlAmountItemValue, $dataEfbDecimalPlacesDigit, $dataEfbRounding);
                                                }
                                                // amountInWords 大写，大写的处理要放在四舍五入后面
                                                $amountInWords = isset($layoutChildAmount["amountInWords"]) ? $layoutChildAmount["amountInWords"] : "";
                                                if ($amountInWords) {
                                                    if ($controlAmountItemValue !== "") {
                                                        try {
                                                            $controlAmountItemValue = app($this->flowRunService)->digitUppercase($controlAmountItemValue);
                                                        } catch (\Exception $e) {
                                                            $controlAmountItemValue = "";
                                                        }
                                                    }
                                                }
                                                // thousandSeparator 千位分隔符
                                                $thousandSeparator = isset($layoutChildAmount["thousandSeparator"]) ? $layoutChildAmount["thousandSeparator"] : "";
                                                if ($thousandSeparator && is_numeric($controlAmountItemValue)) {
                                                    $parts = explode('.', $controlAmountItemValue, 2);
                                                    $int = isset($parts[0]) ? strval($parts[0]) : '0';
                                                    $dec = isset($parts[1]) ? strval($parts[1]) : '';
                                                    $dec_len = strlen($dec) > 8 ? 8 : strlen($dec);
                                                    $controlAmountItemValue = number_format($controlAmountItemValue, $dec_len, '.', ',');
                                                }
                                                // percentage 百分比
                                                $dataEfbPercentage = isset($layoutChildAmount["percentage"]) ? $layoutChildAmount["percentage"] : "";
                                                if ($dataEfbPercentage && is_numeric($controlAmountItemValue)) {
                                                    $leng = 0;
                                                    if (isset($layoutChildAmount["decimalPlacesDigit"]) && !empty($layoutChildAmount["decimalPlacesDigit"])) {
                                                        $leng = $layoutChildAmount["decimalPlacesDigit"];
                                                    }
                                                    if ($leng <= 2) {
                                                        $controlAmountItemValue = $controlAmountItemValue * 100 . '%';
                                                    } else {
                                                        $leng = $leng - 2;
                                                        $controlAmountItemValue = sprintf('%01.' . $leng . 'f', $controlAmountItemValue) . '%';
                                                    }

                                                }

                                            }

                                            if (is_array($controlAmountItemValue)) {
                                                $controlAmountItemValue = trim(implode(",", $controlAmountItemValue), ",");
                                            }
                                            $amountData[$layoutChildId] = $controlAmountItemValue;
                                        }
                                    }
                                    $detail = [];
                                    $detail_sort = [];
                                    $LayoutValueTable = "";
                                    $controlCount = count($controlValue);
                                    if ($row_index < $controlCount) {
                                        $row_index = $controlCount;
                                    }
                                    // 附件目录数组-用在附件数据的cell里，做打开目录操作
                                    $exportAttachmentRelationFolderInfo = [];
                                    if ($compress) {
                                        $attachmentControlFolderPath = $this->filterInfoForFolder($sheetName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($runName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($controlTitle) . DIRECTORY_SEPARATOR;
                                    }
                                    foreach ($controlValue as $layoutControlKey => $layoutControlValue) {
                                        $LayoutValueTable .= "<tr>";
                                        $j = 0;
                                        foreach ($layoutChildInfo as $layoutChildId => $layoutChildValue) {
                                            $itemValue = isset($layoutControlValue[$layoutChildId]) ? $layoutControlValue[$layoutChildId] : "";
                                            $itemId = $layoutChildValue['id'] ?? '';
                                            $itemType = $layoutChildValue["type"];
                                            $itemDataEfbSource = isset($layoutChildValue["data-efb-source"]) ? $layoutChildValue["data-efb-source"] : "";
                                            // 特殊值的处理
                                            if ($itemType == "data-selector" || $itemType == "select") {
                                                if (isset($layoutControlValue[$layoutChildId . "_TEXT"]) && $layoutControlValue[$layoutChildId . "_TEXT"] !== '') {
                                                    $itemValue = $layoutControlValue[$layoutChildId . "_TEXT"];
                                                }
                                            } else if ($itemType == "upload") {
                                                // 字段控制设置了此附件控件可以被下载的才能下载
                                                if (!empty($nodeOperation[$itemId]) && is_array($nodeOperation[$itemId]) && in_array('attachmentDownload', $nodeOperation[$itemId])) {
                                                    $attachments = app($this->attachmentService)->getAttachments(['attach_ids' => $itemValue]);
                                                    // 处理附件文件导出 条件(有附件 开了配置 字段控制设置了此附件控件可以被下载)
                                                    // if(!empty($attachments) && $compress && array_search($layoutChildId,$allowDownloadControlInfo) !== false) {
                                                    if (!empty($attachments) && $compress) {
                                                        foreach ($attachments as $key => $attachmentItem) {
                                                            // 文件本体文件名
                                                            $attachmentItemName = $attachmentItem["attachment_name"];
                                                            $fullAttachmentPath = rtrim(getAttachmentDir(), "/") . DIRECTORY_SEPARATOR . rtrim($attachmentItem["attachment_relative_path"], "/") . DIRECTORY_SEPARATOR . $attachmentItem["affect_attachment_name"];
                                                            if (is_file($fullAttachmentPath)) {
                                                                $attachmentFolderPath = $attachmentControlFolderPath . $this->filterInfoForFolder($layoutChildValue["title"]) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($attachmentItemName);
                                                                $attachmentFolderPath = transEncoding($attachmentFolderPath, "utf-8");
                                                                // 附件重复，处理
                                                                if (isset($exportAttachmentInfo[$attachmentFolderPath])) {
                                                                    if (isset($attachmentsNameRepeatRecord[$attachmentFolderPath])) {
                                                                        // 编号
                                                                        $nameRepeatRecordNumber = $attachmentsNameRepeatRecord[$attachmentFolderPath];
                                                                        $nameRepeatRecordNumber++;
                                                                        // 重命名
                                                                        $itemAttachmentNameRename = $this->renameAttachmentUseRepeatNumber($attachmentItemName, $nameRepeatRecordNumber);
                                                                        $attachmentsNameRepeatRecord[$attachmentFolderPath] = $nameRepeatRecordNumber;
                                                                    } else {
                                                                        $nameRepeatRecordNumber = 1;
                                                                        // 重命名
                                                                        $itemAttachmentNameRename = $this->renameAttachmentUseRepeatNumber($attachmentItemName, $nameRepeatRecordNumber);
                                                                        $attachmentsNameRepeatRecord[$attachmentFolderPath] = $nameRepeatRecordNumber;
                                                                    }
                                                                    $attachmentFolderPathRename = $attachmentControlFolderPath . $this->filterInfoForFolder($layoutChildValue["title"]) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($itemAttachmentNameRename);
                                                                    $attachmentFolderPathRename = transEncoding($attachmentFolderPathRename, "utf-8");
                                                                    $exportAttachmentInfo[$attachmentFolderPathRename] = $fullAttachmentPath;
                                                                } else {
                                                                    $exportAttachmentInfo[$attachmentFolderPath] = $fullAttachmentPath;
                                                                }
                                                                $controlRelfPath = "." . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($sheetName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($runName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($controlTitle) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($layoutChildValue["title"]);
                                                                $exportAttachmentRelationFolderInfo[$layoutChildId][$j][$layoutControlKey] = $controlRelfPath;
                                                            }
                                                        }
                                                    }
                                                    $attachments = collect($attachments);
                                                    $attachments = $attachments->pluck("attachment_name")->toArray();
                                                    $itemValue = implode(",", $attachments);
                                                } else {
                                                    $attachments = app($this->attachmentService)->getAttachments(['attach_ids' => $itemValue]);
                                                    $attachments = collect($attachments);
                                                    $attachments = $attachments->pluck("attachment_name")->toArray();
                                                    $itemValue = implode(",", $attachments);
                                                }
                                            } else if ($itemType == "signature-picture") {
                                                if ($itemValue) {
                                                    $itemArray = explode(',', $itemValue);
                                                    if (count($itemArray) > 0 ) {
                                                        $itemValue = $itemArray[0];
                                                    }
                                                    // 签名人
                                                    $itemValue = trans("flow.0x030071") . ":" . app($this->flowParseService)->getUserSimpleInfo($controlValue);
                                                }
                                            } else if ($itemType == "electronic-signature") {
                                                // if ($controlValue) {
                                                //     $controlValue = "";
                                                // }
                                                $itemValue = trans("flow.0x030176"); // 电子签章控件不支持导出！
                                            } else if ($itemType == "countersign") {
                                                if (is_array($itemValue) && count($itemValue)) {
                                                    $countersignString = "";
                                                    $countersignNumber = 0;
                                                    foreach ($itemValue as $key => $countersignInfo) {
                                                        $countersignNumber++;
                                                        $countersignUserInfo = isset($countersignInfo["countersign_user"]) ? $countersignInfo["countersign_user"] : "";
                                                        $countersignUserName = isset($countersignUserInfo["user_name"]) ? $countersignUserInfo["user_name"] : "";
                                                        $countersignInfo["countersign_content"] = str_replace(['<p>', '<h2>'], '', $countersignInfo["countersign_content"]);
                                                        $countersignInfo["countersign_content"] = str_replace(['</p>', '</h2>'], '&#10;', $countersignInfo["countersign_content"]);
                                                        $countersignString .= $countersignUserName . ":" . $countersignInfo["countersign_content"] . "" . $countersignInfo["countersign_time"] . "&#10;&#10;";
                                                        // if ($countersignNumber < count($itemValue)) {
                                                        //     $countersignString .= "<br>";
                                                        // }
                                                    }
                                                    $itemValue = $countersignString;
                                                } else {
                                                    $itemValue = "";
                                                }
                                            } else if ($itemType == "dynamic-info") {
                                                $itemValue = trans("flow.0x030072"); //动态信息控件不支持导出！
                                            } else if ($itemType == "text") {
                                                $layoutItemDataEfbFormat = isset($layoutChildValue["data-efb-format"]) ? $layoutChildValue["data-efb-format"] : "";
                                                // 单行文本框，格式化为数字
                                                if ($layoutItemDataEfbFormat == "number") {
                                                    // 在前端设置里面，把下面的两个条件弄成互斥的！
                                                    // data-efb-decimal-places 是否设置保留位数 boolean | data-efb-decimal-places-digit 保留的位数 | data-efb-rounding 是否四舍五入
                                                    $itemValue = string_numeric($itemValue);
                                                    $dataEfbDecimalPlaces = $layoutChildValue['data-efb-decimal-places'] ?? false;
                                                    if ($dataEfbDecimalPlaces && is_numeric($itemValue)) {
                                                        $dataEfbDecimalPlacesDigit = (int)($layoutChildValue['data-efb-decimal-places-digit'] ?? 2);
                                                        $dataEfbRounding = $layoutChildValue['data-efb-rounding'] ?? false;
                                                        $itemValue = decimal_places($itemValue, $dataEfbDecimalPlacesDigit, $dataEfbRounding);
                                                    }
                                                    // data-efb-amount-in-words 大写
                                                    $dataEfbAmountInWords = isset($layoutChildValue["data-efb-amount-in-words"]) ? $layoutChildValue["data-efb-amount-in-words"] : "";
                                                    if ($dataEfbAmountInWords) {
                                                        if ($itemValue !== "") {
                                                            try {
                                                                $itemValue = app($this->flowRunService)->digitUppercase($itemValue);
                                                            } catch (\Exception $e) {
                                                                $itemValue = "";
                                                            }
                                                        }
                                                    }
                                                    // data-efb-thousand-separator 千位分隔符
                                                    $dataEfbThousandSeparator = isset($layoutChildValue["data-efb-thousand-separator"]) ? $layoutChildValue["data-efb-thousand-separator"] : "";
                                                    if ($dataEfbThousandSeparator && is_numeric($itemValue)) {
                                                        $parts = explode('.', $itemValue, 2);
                                                        $int = isset($parts[0]) ? strval($parts[0]) : '0';
                                                        $dec = isset($parts[1]) ? strval($parts[1]) : '';
                                                        $dec_len = strlen($dec) > 8 ? 8 : strlen($dec);
                                                        $itemValue = number_format($itemValue, $dec_len, '.', ',');
                                                    }
                                                    // data-efb-percentage 百分比
                                                    $dataEfbPercentage = isset($layoutChildValue["data-efb-percentage"]) ? $layoutChildValue["data-efb-percentage"] : "";
                                                    if ($dataEfbPercentage && is_numeric($itemValue)) {
                                                        $leng = 0;
                                                        if (isset($layoutChildValue["data-efb-percentage"]) && !empty($layoutChildValue["data-efb-percentage"])) {
                                                            $leng = $layoutChildValue["data-efb-percentage"];
                                                        }
                                                        if ($leng <= 2) {
                                                            $itemValue = $itemValue * 100 . '%';
                                                        } else {
                                                            $leng = $leng - 2;
                                                            $itemValue = sprintf('%01.' . $leng . 'f', $itemValue) . '%';
                                                        }
                                                    }
                                                }
                                            } else if ($itemType == 'editor') {
                                                $itemValue = str_replace('<p>', '', $itemValue);
                                                $itemValue = str_replace('</p>', '&#10;', $itemValue);
                                            } else if ($itemType == 'detail-layout') {
                                                $itemValue = '';
                                            }
                                            if (is_array($itemValue)) {
                                                $itemValue = trim(implode(",", $itemValue), ",");
                                            }
                                            $LayoutValueTable .= "<td style='vnd.ms-excel.numberformat:@'>" . $itemValue . "</td>";
                                            $itemValue = str_replace(['<', '>'], ['&lt;', '&gt;'], $itemValue);
                                            $detail[$layoutChildId][$j][$layoutControlKey] = $itemValue;
                                            if (isset($layoutChildValue["isAmount"]) && $layoutChildValue["isAmount"] && $layoutControlKey == 0) {
                                                $LayoutValueTable .= "<td rowspan='" . (count($controlValue)) . "' style='text-align:center;'>";
                                                $text = "";
                                                if (count($amountData) && isset($amountData[$layoutChildId])) {
                                                    $LayoutValueTable .= $amountData[$layoutChildId];
                                                    $text = $amountData[$layoutChildId];
                                                }
                                                $LayoutValueTable .= "</td>";
                                                $detail_sort[$j] = $text;
                                            }
                                            $j++;
                                        }
                                        $LayoutValueTable .= "</tr>";
                                    }
                                    $merge_detail[$inner_column] = $detail;
                                    $merge_sort[$inner_column] = $detail_sort;
                                    $mergeDetailRelationFolderInfo[$inner_column] = $exportAttachmentRelationFolderInfo;
                                    $index_count = 0;
                                    foreach ($detail as $k => $v) {
                                        foreach ($v as $key => $item) {
                                            $attachmentControlHref = "";
                                            if (isset($exportAttachmentRelationFolderInfo[$k]) && isset($exportAttachmentRelationFolderInfo[$k][$key])) {
                                                $attachmentControlHrefArray = $exportAttachmentRelationFolderInfo[$k][$key];
                                                $attachmentControlHref = reset($attachmentControlHrefArray);
                                            }
                                            foreach ($item as $itemKey => $value) {
                                                $exportString .= '<Cell ss:Index="' . $inner_column . '" ss:StyleID="CellWrapText" ';
                                                if ($attachmentControlHref && strip_tags($value)) {
                                                    $exportString .= 'ss:Formula="=HYPERLINK(&quot;' . $attachmentControlHref . '&quot;,&quot;' . $this->handleAttachmentsNameStringEscapeError(strip_tags($value)) . ' &quot;)"';
                                                }
                                                $value = str_replace(['<', '>'], ['&lt;', '&gt;'], $value);
                                                $exportString .= '><Data ss:Type="String">' . strip_tags($value) . '</Data></Cell>';
                                                break;
                                            }
                                        }
                                        $inner_column++;
                                        if (isset($detail_sort[$index_count])) {
                                            $exportString .= '<Cell ss:Index="' . $inner_column . '" ss:StyleID="CellWrapText" place><Data ss:Type="String">' . strip_tags($detail_sort[$index_count]) . '</Data></Cell>';
                                            $inner_column++;
                                        }
                                        $index_count++;
                                    }
                                    //foreach($detail_sort as $sumAmount){
                                    //$exportString .= '<Cell ss:Index="'.$inner_column.'" place><Data ss:Type="String">'.strip_tags($sumAmount).'</Data></Cell>';
                                    //$inner_column++;
                                    //}
                                    unset($detail);
                                    $detail = null;
                                    unset($detail_sort);
                                    $detail_sort = null;
                                } else {
                                    foreach ($layoutChildInfo as $layoutChildId => $layoutChildValue) {
                                        $inner_column++;
                                        if (isset($layoutChildValue["isAmount"]) && $layoutChildValue["isAmount"]) {
                                            $inner_column++;
                                        }
                                    }
                                }
                            }
                        } else { // 非明细
                            $attribute = isset($controlParseItem["attribute"]) ? $controlParseItem["attribute"] : [];
                            $dataEfbFormat = isset($attribute["data-efb-format"]) ? $attribute["data-efb-format"] : "";
                            $controlDataEfbSource = isset($attribute["data-efb-source"]) ? $attribute["data-efb-source"] : "";
                            // 附件上传控件，如果可以下载的时候，加的那个href的地址
                            $attachmentControlHref = "";
                            if ($controlType == "data-selector" || $controlType == "select") {
                                if (isset($parseData[$controlId . "_TEXT"]) && $parseData[$controlId . "_TEXT"] !== '') {
                                    $controlValue = $parseData[$controlId . "_TEXT"];
                                }
                            } else if ($controlType == "upload") {
                                // 字段控制设置了此附件控件可以被下载的才能下载
                                if (!empty($nodeOperation[$controlId]) && is_array($nodeOperation[$controlId]) && in_array('attachmentDownload', $nodeOperation[$controlId])) {
                                    $attachments = app($this->attachmentService)->getAttachments(['attach_ids' => $controlValue]);
                                    // 处理附件文件导出 条件(有附件 开了配置 字段控制设置了此附件控件可以被下载)
                                    // if(!empty($attachments) && $compress && array_search($controlId,$allowDownloadControlInfo) !== false) {
                                    if (!empty($attachments) && $compress) {
                                        foreach ($attachments as $key => $attachmentItem) {
                                            $fullAttachmentPath = rtrim(getAttachmentDir(), "/") . DIRECTORY_SEPARATOR . rtrim($attachmentItem["attachment_relative_path"], "/") . DIRECTORY_SEPARATOR . $attachmentItem["affect_attachment_name"];
                                            $attachmentFolderPath = $this->filterInfoForFolder($sheetName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($runName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($controlTitle) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($attachmentItem["attachment_name"]);
                                            $attachmentFolderPath = transEncoding($attachmentFolderPath, "utf-8");
                                            // 附件重复，处理
                                            if (isset($exportAttachmentInfo[$attachmentFolderPath])) {
                                                if (isset($attachmentsNameRepeatRecord[$attachmentFolderPath])) {
                                                    // 编号
                                                    $nameRepeatRecordNumber = $attachmentsNameRepeatRecord[$attachmentFolderPath];
                                                    $nameRepeatRecordNumber++;
                                                    // 重命名
                                                    $itemAttachmentNameRename = $this->renameAttachmentUseRepeatNumber($attachmentItem["attachment_name"], $nameRepeatRecordNumber);
                                                    $attachmentsNameRepeatRecord[$attachmentFolderPath] = $nameRepeatRecordNumber;
                                                } else {
                                                    $nameRepeatRecordNumber = 1;
                                                    // 重命名
                                                    $itemAttachmentNameRename = $this->renameAttachmentUseRepeatNumber($attachmentItem["attachment_name"], $nameRepeatRecordNumber);
                                                    $attachmentsNameRepeatRecord[$attachmentFolderPath] = $nameRepeatRecordNumber;
                                                }
                                                $attachmentFolderPathRename = $this->filterInfoForFolder($sheetName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($runName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($controlTitle) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($itemAttachmentNameRename);
                                                $attachmentFolderPathRename = transEncoding($attachmentFolderPathRename, "utf-8");
                                                $exportAttachmentInfo[$attachmentFolderPathRename] = $fullAttachmentPath;
                                            } else {
                                                $exportAttachmentInfo[$attachmentFolderPath] = $fullAttachmentPath;
                                            }
                                            $attachmentControlHref = "." . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($sheetName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($runName) . DIRECTORY_SEPARATOR . $this->filterInfoForFolder($controlTitle);
                                        }
                                    }
                                    $attachments = collect($attachments);
                                    $attachments = $attachments->pluck("attachment_name")->toArray();
                                    $controlValue = implode(",", $attachments);
                                } else {
                                    $attachments = app($this->attachmentService)->getAttachments(['attach_ids' => $controlValue]);
                                    $attachments = collect($attachments);
                                    $attachments = $attachments->pluck("attachment_name")->toArray();
                                    $controlValue = implode(",", $attachments);
                                }
                            } else if ($controlType == "signature-picture") {
                                if ($controlValue) {
                                    $controlValueArray = explode(',', $controlValue);
                                    if (count($controlValueArray) > 0 ) {
                                        $controlValue = $controlValueArray[0];
                                    }
                                    // 签名人
                                    $controlValue = trans("flow.0x030071") . ":" . app($this->flowParseService)->getUserSimpleInfo($controlValue);
                                }
                            } else if ($controlType == "electronic-signature") {
                                // if ($controlValue) {
                                //     $controlValue = "";
                                // }
                                $controlValue = trans("flow.0x030176"); // 电子签章控件不支持导出！
                            } else if ($controlType == "countersign") {
                                if (is_array($controlValue) && count($controlValue)) {
                                    $countersignString = "";
                                    $countersignNumber = 0;
                                    $merge_data = [];
                                    foreach ($controlValue as $key => $countersignInfo) {
                                        $countersignNumber++;
                                        $countersignUserInfo = isset($countersignInfo["countersign_user"]) ? $countersignInfo["countersign_user"] : "";
                                        $countersignUserName = isset($countersignUserInfo["user_name"]) ? $countersignUserInfo["user_name"] : "";
                                        $countersignInfo["countersign_content"] = str_replace(['<p>', '<h2>'], '', $countersignInfo["countersign_content"]);
                                        $countersignInfo["countersign_content"] = str_replace(['</p>', '</h2>'], '&#10;', $countersignInfo["countersign_content"]);
                                        $countersignString .= $countersignUserName . ":" . $countersignInfo["countersign_content"] . "" . $countersignInfo["countersign_time"] . "&#10;&#10;";
                                        //$merge_data[]['data'] = $countersignUserName . ":" . strip_tags($countersignInfo["countersign_content"]) . "" . $countersignInfo["countersign_time"];
                                        // if ($countersignNumber < count($controlValue)) {
                                        //     $countersignString .= "   ";
                                        // }
                                    }
                                    $controlValue = $countersignString;
                                    //continue;
                                } else {
                                    $controlValue = "";
                                }
                            } else if ($controlType == "dynamic-info") {
                                $controlValue = trans("flow.0x030072"); //动态信息控件不支持导出！
                            } else if ($controlType == "text") {
                                // 单行文本框，格式化为数字
                                if ($dataEfbFormat == "number") {
                                    // 在前端设置里面，把下面的两个条件弄成互斥的！
                                    // data-efb-decimal-places 是否设置保留位数 boolean | data-efb-decimal-places-digit 保留的位数 | data-efb-rounding 是否四舍五入
                                    $dataEfbDecimalPlaces = $attribute['data-efb-decimal-places'] ?? false;
                                    $controlValue = string_numeric($controlValue);
                                    if ($dataEfbDecimalPlaces && is_numeric($controlValue)) {
                                        $dataEfbDecimalPlacesDigit = (int)($attribute['data-efb-decimal-places-digit'] ?? 2);
                                        $dataEfbRounding = $attribute['data-efb-rounding'] ?? false;
                                        $controlValue = decimal_places($controlValue, $dataEfbDecimalPlacesDigit, $dataEfbRounding);
                                    }
                                    // data-efb-amount-in-words 大写，金额大写的要放在保留小数和四舍五入后面
                                    $dataEfbAmountInWords = isset($attribute["data-efb-amount-in-words"]) ? $attribute["data-efb-amount-in-words"] : "";
                                    if ($dataEfbAmountInWords) {
                                        $controlValue = app($this->flowRunService)->digitUppercase($controlValue);
                                        try {
                                            $controlValue = app($this->flowRunService)->digitUppercase($controlValue);
                                        } catch (\Exception $e) {
                                            $controlValue = "";
                                        }
                                    }
                                    // data-efb-thousand-separator 千位分隔符
                                    $dataEfbThousandSeparator = isset($attribute["data-efb-thousand-separator"]) ? $attribute["data-efb-thousand-separator"] : "";
                                    if ($dataEfbThousandSeparator && is_numeric($controlValue)) {
                                        $parts = explode('.', $controlValue, 2);
                                        $int = isset($parts[0]) ? strval($parts[0]) : '0';
                                        $dec = isset($parts[1]) ? strval($parts[1]) : '';
                                        $dec_len = strlen($dec) > 8 ? 8 : strlen($dec);
                                        $controlValue = number_format($controlValue, $dec_len, '.', ',');
                                    }
                                    // data-efb-percentage 百分比
                                    $dataEfbPercentage = isset($attribute["data-efb-percentage"]) ? $attribute["data-efb-percentage"] : "";
                                    if ($dataEfbPercentage && is_numeric($controlValue)) {
                                        $leng = 0;
                                        if (isset($attribute["data-efb-percentage"]) && !empty($attribute["data-efb-percentage"])) {
                                            $leng = $attribute["data-efb-percentage"];
                                        }
                                        if ($leng <= 2) {
                                            $controlValue = round(round($controlValue, 2) * 100, 2) . '%';
                                        } else {
                                            $leng = $leng - 2;
                                            $controlValue = sprintf('%01.' . $leng . 'f', $controlValue) . '%';
                                        }

                                    }
                                }
                            } else if ($controlType == 'editor') {
                                $controlValue = str_replace('<p>', '', $controlValue);
                                $controlValue = str_replace('</p>', '&#10;', $controlValue);
                            }

                            // 普通控件
                            if (is_array($controlValue)) {
                                $controlValue = trim(implode(",", $controlValue), ",");
                            }
                            if (isset($attribute["data-efb-hide"]) && $attribute["data-efb-hide"]) {
                                $controlValue = "";
                            }
                            $controlValue = strip_tags($controlValue,'<img>');
                            // $controlValue = str_replace(['&#10;', '&nbsp;'], '', $controlValue);
                            // $exportString .= '<Cell ss:Index="'.$inner_column.'" place><Data ss:Type="String">'.strip_tags($controlValue).'</Data></Cell>';
                            $controlValue = str_replace(['<', '>'], ['&lt;', '&gt;'], $controlValue);
                            $exportString .= '<Cell ss:Index="' . $inner_column . '" ss:StyleID="CellWrapText" ';
                            if ($attachmentControlHref) {
                                $exportString .= 'ss:Formula="=HYPERLINK(&quot;' . $attachmentControlHref . '&quot;,&quot;' . $this->handleAttachmentsNameStringEscapeError(strip_tags($controlValue)) . ' &quot;)"';
                            }
                            $exportString .= ' place><Data ss:Type="String">' . strip_tags($controlValue) . '</Data></Cell>';

                            $inner_column++;
                        }
                    }
                }
                $exportString .= '</Row>';

                if ($row_index > 1) {
                    $exportString = str_replace('place', 'ss:MergeDown="' . ($row_index - 1) . '"', $exportString);
                } else {
                    $exportString = str_replace('place', "", $exportString);
                }
                $row_detail = [];
                $rowDetailRelationFolderInfo = [];
                foreach ($merge_detail as $k => $value) {
                    $column_info = $k;
                    $relationFolderInfoValue = $mergeDetailRelationFolderInfo[$k];
                    $temp_count = 0;
                    foreach ($value as $itemKey => $item) {
                        foreach ($item as $infoKey => $info) {
                            $row_info = $data_row;
                            foreach ($info as $index => $v) {
                                $row_detail[$row_info][$column_info] = $v;
                                if (isset($relationFolderInfoValue[$itemKey]) && isset($relationFolderInfoValue[$itemKey][$infoKey]) && isset($relationFolderInfoValue[$itemKey][$infoKey][$index]))
                                    $rowDetailRelationFolderInfo[$row_info][$column_info] = $relationFolderInfoValue[$itemKey][$infoKey][$index];
                                $row_info++;
                            }
                        }
                        if (isset($merge_sort[$k][$temp_count])) {
                            $column_info++;
                        }
                        $column_info++;
                        $temp_count++;
                    }
                }
                foreach ($row_detail as $k => $value) {
                    if ($k > $data_row) {
                        $exportString .= '<Row ss:Index="' . $k . '">';
                        foreach ($value as $t => $v) {
                            $attachmentControlHrefRowLine = "";
                            if (isset($rowDetailRelationFolderInfo[$k]) && isset($rowDetailRelationFolderInfo[$k][$t])) {
                                $attachmentControlHrefRowLine = $rowDetailRelationFolderInfo[$k][$t];
                            }
                            $exportString .= '<Cell ss:Index="' . $t . '" ';
                            if ($attachmentControlHrefRowLine && $v) {
                                $exportString .= 'ss:Formula="=HYPERLINK(&quot;' . $attachmentControlHrefRowLine . '&quot;,&quot;' . $this->handleAttachmentsNameStringEscapeError(strip_tags($v)) . ' &quot;)"';
                            }
                            $exportString .= ' ><Data ss:Type="String">' . $v . '</Data></Cell>';
                        }
                        $exportString .= '</Row>';
                    }
                }
                yield $exportString;
                //释放内存
                unset($exportString);
                $exportString = '';
                $data_row += $row_index;
                //每行增量
                $row_increase = 1;
                //单行合并数量
                $row_index = 1;
                $count++;
                unset($merge_detail);
                $merge_detail = null;
                unset($row_detail);
                $row_detail = null;
                unset($mergeDetailRelationFolderInfo);
                $mergeDetailRelationFolderInfo = null;
                unset($rowDetailRelationFolderInfo);
                $rowDetailRelationFolderInfo = null;
            }
            yield "</Table></Worksheet>";
        }

        // 附件存数组
        Cache::put('flow_search_export_attachment_info_' . $getDataTimestamp, json_encode($exportAttachmentInfo), 60);
        if (empty($flowIds)) {
            yield '<Worksheet ss:Name="Sheet1"><Table><Row ss:Index="1"><Cell ss:Index="1"><Data ss:Type="String"></Data></Cell></Row></Table></Worksheet>';
        }
    }

    function getHeaderWidth($value)
    {
        $length = mb_strlen($value);
        if ($length > 6) {
            $length *= 1;
        } else {
            if ($length > 3) {
                $length *= 2;
            } else {
                $length *= 3;
            }
        }
        return $length;
    }

    /**
     * 功能函数，过滤掉文件夹不支持的字符串，默认替换为"_"
     * 用于：1、流程查询导出附件的时候， flow_name run_name control_title 作为文件夹名称的过滤
     * @param  [type] $value   [description]
     * @param string $replace [description]
     * @return [type]          [description]
     */
    public function filterInfoForFolder($value, $replace = "_")
    {
    	$str = str_replace(['/', '\\', ':', '*', '"', '<', '>', '|', '?', '@', '[', ']', '：', '？'], $replace, $value);
    	$str = str_replace([" ","　","\n","\r","\t","&nbsp;"],'',$str);
    	return $str;
    }

    /**
     * 功能函数，在附件导出时，如果有重复的附件，将附件重命名
     * @param  [type] $attachmentItemName     [附件名]
     * @param  [type] $nameRepeatRecordNumber [重命名依据]
     * @return [type]                         [编辑后的附件名]
     */
    public function renameAttachmentUseRepeatNumber($attachmentName, $number)
    {
        $nameInfo = pathinfo($attachmentName);
        $extension = $nameInfo["extension"];
        $filename = $nameInfo["filename"];
        $filenameNew = $filename . " (" . $number . ")";
        $name = $filenameNew . "." . $extension;
        return $name;
    }

    /**
     * 新加表单
     * @param $flowFormDetail
     * @return mixed
     */
    private function importForm($formDetail)
    {
        $formDetail = $this->processFormMaterial($formDetail);
        if (!$formDetail) {
            // 缺少必填项
            return ['code' => ['0x000001', 'common']];
        }
        // 表单控件信息
        $control = $formDetail['control'] ?? [];
        $formName = $formDetail['form_name'] ?? '';
        if (!is_array($control) || !is_string($formName) ) {
             // 提交字段不合法
             return ['code' => ['0x000008', 'common']];
        }

        $formDetail['form_version_no'] = substr(microtime(true)*100, 0, 12);
        $formData = array_intersect_key($formDetail, array_flip(app($this->flowFormTypeRepository)->getTableColumns()));
        // 插入新表单
        $flowFormObject = app($this->flowFormTypeRepository)->insertData($formData);
        $newFormId = $flowFormObject->form_id;
        app($this->flowRunService)->recordFlowFormHistory($newFormId, $formData);

        // 创建zzzz_flow_data_$FORM_ID表
        app($this->flowRunService)->insertFlowFormStructure(["form_id" => $newFormId]);

        $add = [];
        $formControl = [];
        if (is_array($control) && count($control) > 0) {

            foreach ($control as $key => $value) {
                $id = $value['control_id'];
                $attribute = isset($value['control_attribute']) ? json_decode($value['control_attribute'], true) : "";
                if (isset($value['control_parent_id']) && !empty($value['control_parent_id'])) {
                    $controlCount = explode('_', $value['control_id']);
                    // 二级明细
                    if (count($controlCount) == 4) {
                        $parentId = $controlCount[0].'_'.$controlCount[1]."_".$controlCount[2];
                        $grantpId = $controlCount[0].'_'.$controlCount[1];
                        $formControl[$grantpId]['info'][$parentId]['info'][$id]['title'] = $value['control_title'];
                        $formControl[$grantpId]['info'][$parentId]['info'][$id]['type'] = $value['control_type'];
                        $formControl[$grantpId]['info'][$parentId]['info'][$id]['attribute'] = $attribute;
                        $formControl[$grantpId]['info'][$parentId]['info'][$id]['sort'] = $value['sort'];
                    }else {
                        //明细布局内容
                        $parentId = $value['control_parent_id'];
                        $formControl[$parentId]['info'][$id]['title'] = $value['control_title'];
                        $formControl[$parentId]['info'][$id]['type'] = $value['control_type'];
                        $formControl[$parentId]['info'][$id]['attribute'] = $attribute;
                        $formControl[$parentId]['info'][$id]['sort'] = $value['sort'];
                    }

                } else {
                    $formControl[$id]['title'] = $value['control_title'];
                    $formControl[$id]['type'] = $value['control_type'];
                    $formControl[$id]['attribute'] = $attribute;
                    $formControl[$id]['sort'] = $value['sort'];
                    $add[] = $id;
                }
            }
        }
        $data = [
            'control' => $formControl,
            'user_id' => 'admin',
            'change' => [
                'add' => $add,
            ],
            'form_version_no' => $formDetail['form_version_no']
        ];
        // 处理解析后的表单控件，操作[流程数据表]和[明细字段数据表]
        app($this->flowRunService)->disposeFormControlStructure($data, $newFormId);
        return $newFormId;
    }

    /**
     * 处理表单素材资源
     * @param $form
     * @return mixed
     */
    private function processFormMaterial($form)
    {
        if(is_string($form)) {
            $form = json_decode($form, true);
        }
        if(!$form || !is_array($form)) {
            return false;
        }
        $control = $form['control'] ?? [];
        if (is_string($control)) {
            $control = json_decode($control, true);
        }
        $form['control']   = $control;
        $form['form_name'] = $form['form_name'] ?? '';
        return $form;
    }

    /**
     * 导出流程素材
     * @param $flowId
     */
    public function exportFlowMaterial($flowId)
    {
        $result = [];
        $flowInfo = $this->getFlowInfo($flowId);
        if ($flowInfo) {
            $formId = $flowInfo['flow_type']['form_id'];
            $flowFormDetail = app($this->flowFormTypeRepository)->getDetail($formId);
            if ($flowFormDetail) {
                $control = app($this->flowFormService)->getFlowFormControlStructure(['fields' => ['control_auto_id', 'form_id', 'sort', 'control_id', 'control_title', 'control_type', 'control_attribute', 'control_parent_id', 'created_at', 'updated_at', 'deleted_at'], 'search' => ['form_id' => [$flowFormDetail->form_id]]]);
                $result = [
                    'version' => version(),
                    'flow'    => $flowInfo ?? [],
                    'form'    =>  [
                        'form_name'     => $flowFormDetail->form_name,
                        'print_model'   => $flowFormDetail->print_model,
                        'field_counter' => $flowFormDetail->field_counter,
                        'form_type'     => $flowFormDetail->form_type,
                        'form_sort'     => 0,
                        'control'       => $control
                    ],
                ];
                try{
                    // 导出发票云相关配置信息
                    $invoiceInfo = app($this->invoiceManageService)->exportInvoiceFlowConfig($flowId);
                    if (is_array($invoiceInfo) && count($invoiceInfo) > 0) {
                        $result['invoice'] = $invoiceInfo;
                    }
                    // 导出电子签相关配置信息
                    $qiyuesuoInfo = app($this->electronicSignService)->exportQiyuesuoFlowConfig($flowId);
                    if (is_array($qiyuesuoInfo) && count($qiyuesuoInfo) > 0) {
                        $result['qiyuesuo'] = $qiyuesuoInfo;
                    }
                } catch(\Exception $e) {
                    Log::info($e->getMessage());
                }
            }
        }
        return $result;
    }

    /**
     * 获取流程信息
     * @param $flowId
     * @return array
     */
    private function getFlowInfo($flowId)
    {
        if (!is_numeric($flowId)) {
            return false;
        }
        $flowType = app($this->flowTypeRepository)->getDetail($flowId)->toArray();
        if ($flowType) {
            unset($flowType['flow_id']);
            $flowType['flow_sort'] = 0;
            $flowType['allow_monitor'] = 0;
            $flowType['is_using'] = 1;
            $flowType['create_user'] = null;
            $flowType['create_dept'] = null;
            $flowType['create_role'] = null;
            $flowProcess = [];
            // 出口条件
            $flowTerm    = [];
            // 自由流程必填
            $flowRequiredForFreeFlow = [];
            // 超时提醒
            $flowOvertimeRemind = [];
            // 自由流程
            if ($flowType['flow_type'] == 2) {
                $flowProcess = [];
                // 出口条件
                $flowTerm    = [];
                $flowRequiredForFreeFlow = app($this->flowService)->getFreeFlowRequired($flowId);
            } else {
                $flowProcess = $this->getFlowProcess($flowId, $flowType['form_id']);
                $flowTerm    = $this->getFlowTermList($flowId);
                $flowRequiredForFreeFlow = [];
            }
            // 获取other表信息
            $flowOthers = app($this->flowOthersRepository)->getDetail($flowId);
            $flowOvertimeRemind = $this->getFlowOvertimeRemindList($flowId);
            if ($flowOthers) {
                $flowOthers = $flowOthers->toArray();
                // 流程是否设置打印模板[设置:1;不设置:0;]'
                $flowOthers['flow_print_template_toggle'] = 0;
                // 显示导入表单数据模板，1、显示；、0不显示
                $flowOthers['flow_show_data_template'] = 0;
                // 显示用户数据模板，1、显示；、0不显示
                $flowOthers['flow_show_user_template'] = 0;
                // 归档文件夹
                $flowOthers['file_folder_id'] = 0;
                // 自定义归档模板[设置:1;不设置:0;]
                $flowOthers['flow_filing_template_toggle'] = 0;
                // 文件验证时，关闭设置
                if ($flowOthers['flow_filing_conditions_verify_mode'] == 2) {
                    $flowOthers['flow_filing_conditions_setting_toggle'] = 0;
                }
                // 归档验证模式置为使用表达式进行判断
                $flowOthers['flow_filing_conditions_verify_mode'] = 1;
                // 清空归档文件验证
                $flowOthers['flow_filing_conditions_verify_url'] = '';

                // 20200512,zyx,定时触发配置,如果配置了定时任务，导出时也一并导出
                if ($flowOthers['trigger_schedule']) {
                    $flowOthers['trigger_schedule_config'] = app($this->flowSettingService)->getFlowSchedules($flowId);
                } else {
                    $flowOthers['trigger_schedule_config'] = [];
                }
            } else {
                $flowOthers = [];
            }
            return [
                'flow_type'                   => $flowType,
                'flow_others'                 => $flowOthers,
                'flow_process'                => $flowProcess,
                'flow_term'                   => $flowTerm,
                'node_count'                  => count($flowProcess),
                'flow_required_for_free_flow' => $flowRequiredForFreeFlow,
                'flow_overtime_remind'        => $flowOvertimeRemind,
            ];
        }
        return false;
    }

    /**
     * 获取出口条件
     */
    private function getFlowTermList($flowId)
    {
        $flowTermList = app($this->flowTermRepository)->getFlowNodeList($flowId);
        $flowTerm = [];
        if ($flowTermList && is_array($flowTermList) && count($flowTermList)) {
            foreach ($flowTermList as $term) {
                $flowTerm[] = [
                    'source_id' => $term['source_id'],
                    'target_id' => $term['target_id'],
                    'condition' => $term['condition'],
                ];
            }
        }
        return $flowTerm;
    }
    /**
     * 获取流程超时提醒
     */
    private function getFlowOvertimeRemindList($flowId)
    {
        $flowOverTimeRemindList = app($this->flowOverTimeRemindRepository)->getFlowList($flowId);
        $flowOverTimeRemind = [];
        if ($flowOverTimeRemindList && count($flowOverTimeRemindList) > 0) {
            $flowOverTimeRemind = $flowOverTimeRemindList->toArray();
        }
        return $flowOverTimeRemind;
    }

    /**
     * 新增流程出口条件
     */
    private function importFlowTerm($flowId, $flowTermList, $nodeMap, $own)
    {
        if (is_array($flowTermList) && count($flowTermList) > 0) {
            foreach ($flowTermList as $term) {
                $newSourceId = $nodeMap[$term['source_id']] ?? 0;
                $newTargetId = $nodeMap[$term['target_id']] ?? 0;
                if ($newSourceId != 0 && $newTargetId != 0) {
                    $flowTerm = [
                        'flow_id'   => $flowId,
                        'source_id' => $newSourceId,
                        'target_id' => $newTargetId,
                        'condition' => $term['condition'],
                    ];
                    app($this->flowService)->chartUpdateNodeCondition($flowTerm, $own);
                }
            }
        }
    }

    /**
     * 新增流程出口条件
     */
    private function importFlowOvertimeRemind($flowId, $flowOvertimeRemindList, $nodeMap)
    {
        if (is_array($flowOvertimeRemindList) && count($flowOvertimeRemindList) > 0) {
            $newRemind = [];
            foreach ($flowOvertimeRemindList as $remind) {
                $originNodeId = $remind['node_id'] ?? -1;
                $nodeId = $nodeMap[$originNodeId] ?? NULL;
                if (is_numeric($nodeId) || $nodeId == NULL) {
                    $currentTime = date('Y-m-d H:i:s' , time());
                    $newRemind[] = [
                        'flow_id'       => $flowId,
                        'node_id'       => $nodeId,
                        'remind_time'   => $remind['remind_time'] ?? 0,
                        'overtime_ways' => $remind['overtime_ways'] ?? 0,
                        'created_at'    => $currentTime,
                        'updated_at'    => $currentTime,
                    ];
                }
            }
            if (is_array($newRemind) && count($newRemind) > 0) {
                app($this->flowOverTimeRemindRepository)->insertMultipleData($newRemind);
            }
        }
        return true;
    }

    /**
     * 获取流程节点信息
     * @param $flowProcess
     * @return mixed
     */
    private function getFlowProcess($flowId, $formId)
    {
        $param = [
            'search' => ['flow_id' => [$flowId]],
            'returntype' => 'array'
        ];
        $flowProcess = app($this->flowProcessRepository)->getFlowProcessList($param);
        if ($flowProcess && is_array($flowProcess) && count($flowProcess) > 0) {
            foreach ($flowProcess as $key => $process) {
                if (array_key_exists('flow_process_has_many_user', $process)) {
                    unset($process['flow_process_has_many_user']);
                }
                unset($process['flow_id']);
                $process['handle_user_instant_save'] = '';
                // 是否触发子流程[触发:1;不触发:0;]
                $process['sun_flow_toggle'] = 0;
                $process['flow_run_template_toggle'] = 0;
                if ($process['head_node_toggle'] == 1 ) {
                    // 首节点办理人，默认不能指定
                    $process['process_user'] = '';
                } else {
                    // 其他节点默认全部
                    $process['process_user'] = 'ALL';
                }
                $process['process_dept'] = '';
                $process['process_role'] = '';
                $process['process_default_manage'] = '';
                $process['process_default_user'] = '';
                // 办理流程时，本节点办理人员不勾选
                $process['process_default_type'] = '0';
                // 智能获取
                $process['process_auto_get_user'] = '';
                $process['process_auto_get_copy_user'] = '';
                $process['process_copy_user'] = '';
                $process['process_copy_dept'] = '';
                $process['process_copy_role'] = '';

                $nodeId = $process['node_id'];
                if (is_string($process['position']) && $process['position'] != '') {
                    $process['position'] = json_decode($process['position'], true);
                    if ($process['position'] == NULL) {
                        $process['position'] = '';
                    }
                }
                // 获取详细节点信息
                $nodeDetail = app($this->flowProcessRepository)->getFlowNodeDetail($nodeId, $formId);
                $outsend = [];
                $validate = [];
                $controlOperation = [];
                if ($nodeDetail) {
                    $nodeDetail = $nodeDetail->toArray();
                    $outsend =  $nodeDetail['flow_process_has_many_outsend'] ?? [];
                    if (is_array($outsend) && count($outsend) > 0) {
                        foreach ($outsend as $outsendKey => $outsendValue) {
                            $flowOutsendMode = $outsendValue['flow_outsend_mode'] ?? 0;
                            // 仅导出内部系统
                            if ($flowOutsendMode != 2) {
                                unset($outsend[$outsendKey]);
                                continue;
                            }
                            if (!isset($outsendValue['module'])) {
                                unset($outsend[$outsendKey]);
                                continue;
                            }
                            if (!isset($outsendValue['custom_module_menu'])) {
                                unset($outsend[$outsendKey]);
                                continue;
                            }
                            if (isset($outsendValue['custom_module_menu']) && is_numeric($outsendValue['custom_module_menu']) && $outsendValue['custom_module_menu'] >= 1000) {
                                // 仅导出内部模块外发，删掉自定义模块
                                unset($outsend[$outsendKey]);
                            }
                            // 清除outsend_url
                            if (isset($outsend[$outsendKey])) {
                                $outsend[$outsendKey]['outsend_url'] = '';
                                $outsend[$outsendKey]['premise'] = '';
                            }
                        }
                    } else {
                        $outsend = [];
                    }
                    $flowProcessHasManyControlOperation = $nodeDetail['flow_process_has_many_control_operation'] ?? [];
                    if ($flowProcessHasManyControlOperation && is_array($flowProcessHasManyControlOperation) && count($flowProcessHasManyControlOperation) > 0) {
                        $controlOperation = $flowProcessHasManyControlOperation;
                    } else {
                        $controlOperation = [];
                    }
                    // 获取数据验证
                    $validate = app($this->flowDataValidateRepository)->getFlowValidateData($nodeId, true);
                    if (is_array($validate) && count($validate) > 0) {
                        foreach ($validate as $validateKey => $validateValue) {
                            // 只导出条件验证
                            if ($validateValue['validate_type'] != 0) {
                                unset($validate[$validateKey]);
                                continue;
                            }
                            $validate[$validateKey]['file_url']    = '';
                        }
                    } else {
                        $validate = [];
                    }
                }
                $flowProcess[$key] = array_merge($process, [
                    // 外发配置
                    'outsend'           => $outsend,
                    // 字段控制
                    'control_operation' => $controlOperation,
                    // 数据验证
                    'validate'          => $validate,
                ]);
                $flowProcess[$key]['flow_outsend_toggle']    = $process['flow_outsend_toggle'] == 1 && count($outsend) > 0 ? 1 : 0;
                $flowProcess[$key]['flow_data_valid_toggle'] = $process['flow_data_valid_toggle'] == 1 && count($validate) > 0 ? 1 : 0;
            }
        }
        return $flowProcess;
    }

    /**
     * 获取附件文本内容
     * @param $attachmentId string|array 附件id
     * @return array
     */
    private function getAttachmentContent($attachmentId)
    {
        $attachmentIdArray = [];
        if (is_array($attachmentId)) {
            $attachmentIdArray = $attachmentId;
        }
        if (is_string($attachmentId)) {
            $attachmentIdArray[0] = $attachmentId;
        }
        $content = [];
        if (is_array($attachmentIdArray) && count($attachmentIdArray)) {
            foreach ($attachmentIdArray as $attId) {
                $attachmentFile = app($this->attachmentService)->getOneAttachmentById($attId);
                if (isset($attachmentFile['attachment_base_path']) && isset($attachmentFile['attachment_relative_path'])) {

                    $filePath = $attachmentFile['attachment_base_path'] . $attachmentFile['attachment_relative_path'] . $attachmentFile['affect_attachment_name'];
                    if (file_exists($filePath)) {
                        $fileContent = file_get_contents($filePath);
                        if (!empty($fileContent)) {
                            try {
                                $contentArray = json_decode($fileContent, true);
                                if (is_array($contentArray)) {
                                    $content[$attId] = $contentArray;
                                }
                            } catch (\Exception $e) {
                                Log::info($e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * 导入流程素材
     */
    public function importFlowMaterial($data, $own)
    {
        $flows = [];
        if (isset($data['attachment_id'])) {
            $attachmentId = $data['attachment_id'];
            if (is_string($attachmentId)) {
                $attachmentId = explode(',', $attachmentId);
            }
            if ( !is_array($attachmentId) || count($attachmentId) == 0 ) {
                // 导入参数配置错误
                return ['code' => ['0x030033', 'flow']];
            }

            $flows = $this->getAttachmentContent($attachmentId);
        } else {
            if (isset($data['flows'])) {
                if (is_string($data['flows'])) {
                    $data['flows'] = json_decode($data['flows'], true);
                }
                if (is_array($data['flows']) && count($data['flows']) > 0) {
                    foreach ($data['flows'] as $flowInfo) {
                        $content = $flowInfo['file_content'] ?? [];
                        if (is_string($content)) {
                            $content = json_decode($content, true);
                        }

                        if (is_array($content) && count($content) > 0) {
                            $flows[] = $content;
                        }
                    }
                }
            }
        }

        $idSuccess = [];
        if (is_array($flows) && count($flows) > 0) {
            foreach ($flows as $flowKey => $flow) {
                try {
                    $flow = $this->processFlowMaterial($flow);
                    if (!$flow) {
                        continue;
                    }
                    $flowVersion = $flow['version'] ?? '';
                    $flowInfo = $flow['flow'] ?? [];
                    $formInfo = $flow['form'] ?? [];
                    if (!is_array($formInfo) || !is_array($flowInfo) || count($formInfo) == 0 || count($flowInfo) == 0) {
                        continue;
                    }
                    $flowType = $flowInfo['flow_type'] ?? [];
                    if (empty($flowType)) {
                        continue;
                    }
                    $formId = $this->importForm($formInfo);
                    if (!$formId) {
                        continue;
                    }
                    if (is_string($flowVersion) && $flowVersion != '') {
                        if (check_version_larger_than_system_version($flowVersion)) {
                            $flowName = isset($flowInfo['flow_type']) && isset($flowInfo['flow_type']['flow_name']) ? $flowInfo['flow_type']['flow_name'] : '';
                            // 流程素材版本太高
                            return ['code' => ['flow_material_version_too_high', 'flow'], 'dynamic' => trans('flow.flow_material_version_too_high', ['flow_name' => $flowName])];
                        }
                    }
                    $flowId = $this->importFlowType($flowType, $formId, $own['user_id']);
                    if ($flowId) {
                        $flowOthers                 = $flowInfo['flow_others'] ?? [];
                        $flowProcess                = $flowInfo['flow_process'] ?? [];
                        $flowTerm                   = $flowInfo['flow_term'] ?? [];
                        $flowOvertimeRemindList     = $flowInfo['flow_overtime_remind'] ?? [];
                        $flowRequiredForFreeflow    = $flowInfo['flow_required_for_free_flow'] ?? [];
                        $flowOthers['flow_id']      = $flowId;
                        $nodeMap = $this->importFlowProcess($flowId, $flowProcess, $own);
                        // nodeMap originNodeId => $newNodeId
                        $this->importFlowOthers($flowOthers, $nodeMap, $own);
                        $this->importFlowTerm($flowId, $flowTerm, $nodeMap, $own);
                        $this->importFlowOvertimeRemind($flowId, $flowOvertimeRemindList, $nodeMap);
                        app($this->flowService)->editFreeFlowRequired($flowId, $flowRequiredForFreeflow);
                        $idSuccess[$flowKey] = $flowId;

                        // 导入发票云相关配置信息
                        if (isset($flow['invoice'])) {
                            app($this->invoiceManageService)->importInvoiceFlowConfig($flowId, $flow, $nodeMap, $own);
                        }
                        // 导入电子签相关配置信息
                        if (isset($flow['qiyuesuo'])) {
                            app($this->electronicSignService)->importQiyuesuoFlowConfig($flowId, $flow, $nodeMap, $own);
                        }
                    }
                } catch (\Exception $e) {
                    Log::info($e->getMessage());
                }
            }
        }
        return $idSuccess;
    }

    /**
     * 导入一条流程
     * @param $formId
     * @param $flowType
     * @param $flowOthers
     * @return mixed
     */
    private function importFlowType($flowType, $formId, $currentUserId)
    {
        unset($flowType['flow_id']);
        $flowType['form_id'] = $formId;
        $flowType = array_intersect_key($flowType, array_flip(app($this->flowTypeRepository)->getTableColumns()));
        if (isset($flowType['created_at'])) unset($flowType['created_at']);
        if (isset($flowType['updated_at'])) unset($flowType['updated_at']);
        // 插入新流程
        $flowTypeObject = app($this->flowTypeRepository)->insertData($flowType);
        if ($flowTypeObject && isset($flowTypeObject->flow_id)) {
            $flowId = $flowTypeObject->flow_id;
             // 自由流程默认当前用户有权限
             if ($flowType['flow_type'] == 2) {
                app($this->flowTypeCreateUserRepository)->insertData([
                    'flow_id' => $flowId,
                    'user_id' => $currentUserId,
                ]);
            }
            return $flowId;
        }
        return false;
    }

    /**
     * 导入流程其他设置
     */
    private function importFlowOthers($flowOthers, $nodeIdMap, $own = []) {
        // 处理流程结束提醒指定节点
        $appointProcess = $flowOthers['appoint_process'] ?? '';
        if ($appointProcess != '') {
            $appointProcess = explode(',', $appointProcess);
        }
        $newAppointProcess = [];
        if (is_array($appointProcess) && count($appointProcess)) {
            foreach($appointProcess as $ap) {
                if (isset($nodeIdMap[$ap])) {
                    $newAppointProcess[] = $nodeIdMap[$ap];
                }
            }
        }
        $flowOthers['appoint_process'] = implode(',', $newAppointProcess);
        if (!empty($flowOthers['flow_detail_page_choice_other_tabs'])) {
            $flowOthers['flow_detail_page_choice_other_tabs'] = str_replace([',qyssign', 'qyssign'], '', $flowOthers['flow_detail_page_choice_other_tabs']);
        }

        // 20200512，zyx,导入定时触发配置
        if (isset($flowOthers['trigger_schedule_config']) && $flowOthers['trigger_schedule_config']) {
            app($this->flowSettingService)->editFlowSchedules(['flow_id' => $flowOthers['flow_id'], 'trigger_schedule' => 1, 'schedule_configs' => $flowOthers['trigger_schedule_config']], $own);
        }

        $flowOthers = array_intersect_key($flowOthers, array_flip(app($this->flowOthersRepository)->getTableColumns()));
        // 插入流程Others
        app($this->flowOthersRepository)->insertData($flowOthers);

        return true;
    }

    /**
     * 新增流程节点信息
     * @param $flowId
     * @param $flowProcess
     */
    private function importFlowProcess($flowId, $flowProcess, $own)
    {
        $map = [];
        if ($flowProcess && is_array($flowProcess) && count($flowProcess) > 0) {
            $processTo = [];
            foreach ($flowProcess as $key => $node) {
                $originNodeId = $node['node_id'];
                unset($node['node_id']);
                $node['flow_id'] = $flowId;
                // 清空默认主办人、默认办理人
                $node['process_default_manage'] = '';
                $node['process_default_user'] = '';
                // 办理流程时，本节点办理人员不勾选
                $node['process_default_type'] = '0';
                $data = array_intersect_key($node, array_flip(app($this->flowProcessRepository)->getTableColumns()));
                $result = app($this->flowService)->chartCreateNode($data, $own);
                $nodeId = $result['node_id'];
                $map[$originNodeId] = $nodeId;
                $flowProcess[$key]['node_id'] = $nodeId;
                $outsends = $node['outsend'] ?? [];
                // 默认首节点办理人为当前用户
                $headNodeToggle = $node['head_node_toggle'] ?? 0;
                if ( $headNodeToggle == 1 ) {
                    $userPermision = [
                        [
                            'id'      => $nodeId,
                            'user_id' => $own['user_id'],
                        ]
                    ];
                    app($this->flowProcessUserRepository)->insertMultipleData($userPermision);
                }
                // 导入数据验证
                app($this->flowDataValidateRepository)->flowValidateSaveData($node, $nodeId);
                // 导入数据外发
                if (is_array($outsends) && count($outsends) > 0) {
                    foreach ($outsends as $outsend) {
                        $outsendFields = $outsend['outsend_has_many_fields'] ?? [];
                        $dependentFields = $outsend['outsend_has_many_dependent_fields'] ?? [];
                        unset($outsend['id']);
                        $outsend['node_id'] = $nodeId;
                        $outsend = array_intersect_key($outsend, array_flip(app($this->flowOutsendRepository)->getTableColumns()));
                        $flowOutsendId = app($this->flowOutsendRepository)->insertGetId($outsend);
                        if (count($outsendFields) > 0) {
                            foreach ($outsendFields as $outsendField) {
                                $outsendField['flow_outsend_id'] = $flowOutsendId;
                                unset($outsendField['id']);
                                $outsendField = array_intersect_key($outsendField, array_flip(app($this->flowOutsendFieldsRepository)->getTableColumns()));
                                app($this->flowOutsendFieldsRepository)->insertData($outsendField);
                            }
                        }
                        // 更新和删除模式需要导入依赖字段
                        if (count($dependentFields)) {
                            foreach ($dependentFields as $dependentField) {
                                $dependentField['flow_outsend_id'] = $flowOutsendId;
                                unset($dependentField['id']);
                                $dependentField['created_at'] = date('Y-m-d H:i:s');
                                $dependentFieldNew = array_intersect_key($dependentField, array_flip(app($this->flowOutsendDependentFieldsRepository)->getTableColumns()));
                                app($this->flowOutsendDependentFieldsRepository)->insertMultipleData([$dependentFieldNew]);
                            }
                        }
                    }
                }
                // 导入字段控制
                $controlOperation = $node['control_operation'] ?? [];
                if (is_array($controlOperation) && count($controlOperation) > 0) {
                    foreach ($controlOperation as $control) {
                        $controlId = $control['control_id'] ?? '';
                        if (!is_string($controlId) || $controlId == '') {
                            continue;
                        }
                        $controlOperationDetail = $control['control_operation_detail'] ?? [];
                        if (is_array($controlOperationDetail) && count($controlOperationDetail) > 0) {
                            $flowProcessControlOperationId = app($this->flowProcessControlOperationRepository)->insertGetId([
                                'node_id'    => $nodeId,
                                'control_id' => $controlId
                            ]);
                            foreach ($controlOperationDetail as $detail) {
                                $operationType = $detail['operation_type'] ?? '';
                                if (is_string($operationType) && $operationType != '') {
                                    app($this->flowProcessControlOperationDetailRepository)->insertData([
                                        'operation_id'        => $flowProcessControlOperationId,
                                        'operation_type'      => $operationType,
                                        'operation_condition' => $detail['operation_condition'] ?? ''
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            // 修改process_to
            foreach ($flowProcess as $key => $node) {
                $nodeId = $node['node_id'];
                $processTo = explode(',', $node['process_to']);
                $newProcessTo = [];
                foreach ($processTo as $key => $val) {
                    if (is_numeric($val)) {
                        $newProcessTo[] = $map[$val] ?? '';
                    }
                }
                $update = [
                    'process_to' => implode(',', $newProcessTo)
                ];
                // 升级为新的process_to
                $newFlowProcess = app($this->flowProcessRepository)->updateData($update, ['node_id' => $nodeId]);
            }
            // 重新更新origin_node 和merge_node
            app($this->flowParseService)->resetbranchInfo($flowId);
        }
        return $map;
    }

    /**
     * 处理流程素材更新
     * @param $flow
     * @return mixed
     */
    public function processFlowMaterial($flow)
    {
        if (is_string($flow)) {
            $flow = json_decode($flow, true);
        }
        if (!is_array($flow) || count($flow) == 0 ) {
            return false;
        }

        if (!isset($flow['flow']) || !isset($flow['form'])) {
            return false;
        }

        if (!isset($flow['flow']['flow_type']) || !isset($flow['flow']['flow_others'])) {
            return false;
        }

        if (!isset($flow['form']['form_name']) || !isset($flow['form']['print_model'])) {
            return false;
        }
        return $flow;
    }

    /**
     * 功能函数，处理附件名称字符串超长问题，防止Excel打开出错
     *
     * @param  [string] 或 [array]  $value 要处理的附件名参数
     *
     * @return [string] $res
     */
    public function handleAttachmentsNameStringEscapeError($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (is_string($value)) {
            $dealArr = explode(',', $value);
        } else {
            $dealArr = $value;
        }

        //20191219，暂定为4个附件名，如果仍然超长则继续缩减
        for ($i = 4; $i > 0; $i--) {
            $tmpArr = array_slice($dealArr, 0, $i);
            $tmpStr = trim(implode(',', $tmpArr), ',');
            if (strlen($tmpStr) < 240) {
                //超过4个文件，或者<=4个文件但是被缩减数量
                if ((count($dealArr) > 4) || ($i < count($dealArr))) {
                    $tmpStr .= ' 等';
                }
                return $tmpStr;
            }
        }

        return '文件名称过长，不予显示';
    }
}
