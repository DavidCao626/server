<?php
namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;
use DB;
/**
 * 流程报表service类，用来管理相关流程资源的报表分析
 *
 * @since  2018-11-9 创建
 */
class FlowReportService extends FlowBaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 【流程报表】 获取过滤条件
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowReportDatasourceFilter($param = array())
    {
        $flowId              = isset($param['workflowID']) && !empty($param['workflowID']) ? $param['workflowID'] : '';
        $flowFormFilterArray = array();
        if (!empty($flowId)) {
            $flowDetail = app($this->flowTypeRepository)->getDetail($flowId);
            if (!empty($flowDetail)) {
                $flowFromData = app($this->flowFormService)->getParseForm($flowDetail->form_id, array());
                if (!empty($flowFromData)) {
                    foreach ($flowFromData as $key => $value) {
                        $flowFormFilterArray[] = [
                            'filter_type' => 'flow_form_data_input',
                            'itemValue'   => $value['control_id'],
                            'itemName'    => $value['control_title'],
                        ];
                    }
                }
            }
        }
        $instancyOptions = app($this->flowSettingService)->getInstancyMapOptions();
        $flowSystemField = [
            [
                //流程开始日期
                'filter_type' => 'date',
                'itemValue'   => 'startDate',
                'itemName'    => trans("flow.0x030109"), // 流程开始日期
            ],
            [
                //流程结束日期
                'filter_type' => 'date',
                'itemValue'   => 'endDate',
                'itemName'    => trans("flow.0x030110"), // 流程结束日期
            ],
            [
                //状态
                'filter_type' => 'singleton',
                'itemValue'   => 'status',
                'itemName'    => trans("flow.0x030066"), // 流程状态
                'source'      => [
                    '0' => trans("flow.0x030111"), // 所有
                    '1' => trans("flow.0x030069"), // 已完成
                    '2' => trans("flow.0x030070"), // 执行中
                ],
            ],
            [
                //紧急程度
                'filter_type' => 'singleton',
                'itemValue'   => 'instancyType',
                'itemName'    => trans("flow.0x030067"), // 紧急程度
                'source'      => $instancyOptions,
            ],
            [
                //创建人
                'filter_type'   => 'selector',
                'selector_type' => 'user',
                'itemValue'     => 'creator',
                'itemName'      => trans("flow.0x030112"), // 流程创建人
            ],
            [
                //流水号
                'filter_type' => 'input',
                'itemValue'   => 'runSeq',
                'itemName'    => trans("flow.0x030029"), // 流水号
            ],
            [
                //流程标题
                'filter_type' => 'input',
                'itemValue'   => 'runName',
                'itemName'    => trans("flow.0x030030"), // 流程标题
            ],
        ];
        return array_merge($flowSystemField, $flowFormFilterArray);
    }
    /**
     * 【流程报表】 获取流程报表设置，获取分组依据和数据分析字段
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowReportGroupAndAnalyzeConfig($param)
    {
        if (!isset($param['flow_id'])) {
            return [];
        }
        $flowIdsArray = explode(',', trim($param['flow_id'], ','));
        if (empty($flowIdsArray)) {
            return [];
        }
        $data = [];
        foreach ($flowIdsArray as $flowIdKey => $flowIdValue) {
            $result       = [];
            $flowId       = $flowIdValue;
            $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowId);
            if (empty($flowTypeInfo)) {
                continue;
            }
            $formId              = $flowTypeInfo->form_id;
            $result['flow_name'] = $flowTypeInfo->flow_name;
            $flowFormParseInfo   = app($this->flowFormService)->getParseForm($formId, []);
            $groupByInfo         = [
                'creator'    => trans("flow.0x030074"), // 创建人
                'start_time' => trans("flow.0x030075"), // 创建时间
                'end_time'   => trans("flow.0x030113"), // 结束时间
            ];
            $controlItemInfo = [];
            if (count($flowFormParseInfo)) {
                foreach ($flowFormParseInfo as $key => $controlInfo) {
                    $attribute     = isset($controlInfo["control_attribute"]) ? $controlInfo["control_attribute"] : [];
                    $dataEfbSource = isset($attribute["data-efb-source"]) ? $attribute["data-efb-source"] : "";
                    // 过过滤二级明细
                    $countControlId = count(explode('_', $controlInfo['control_id']));
                    if(($countControlId == 3 && $controlInfo['control_type'] == 'detail-layout') || $countControlId == 4) {
                        continue;
                    }
                    if ($controlInfo['control_type'] == 'data-selector' || $controlInfo['control_type'] == "select") {
                        // 下拉框的支持key之后这里全改成_TEXT
                        $controlInfo["control_id"] = $controlInfo["control_id"] . '_TEXT';
                    }
                    $controlItemInfo[$controlInfo["control_id"]] = $controlInfo["control_title"];
                }
            }
            //分组依据
            $result['datasource_group_by'] = $groupByInfo + $controlItemInfo;
            //数据分析字段
            $result['datasource_data_analysis'] = count($controlItemInfo) ? $controlItemInfo : "";
            $data[$flowId]                      = $result;
        }
        return $data;
    }

    /**
     * 为报表模块返回数据，为了将数据分析为表单字段+明细字段混合的类型分开查询再合并
     * @param  [type] $datasource_group_by      [description]
     * @param  [type] $datasource_data_analysis [description]
     * @param  [type] $chart_search             [description]
     * @return [type]                           [description]
     */
    public function getFlowReportData($datasource_group_by, $datasource_data_analysis, $chart_search)
    {
        if (isset($datasource_data_analysis["count"]) && $datasource_data_analysis["count"] == "count") {
            return $this->getFlowReportDataFunction($datasource_group_by, $datasource_data_analysis, $chart_search);
        } else {
            // 数据分析选择如果是表单字段+明细字段混合的就分开查询再合并
            $formFieldArray         = [];
            $detailLayoutFieldArray = [];
            foreach ($datasource_data_analysis as $field) {
                $tempFieldArray = explode('_', str_replace('_TEXT', '', $field));
                if (!empty($tempFieldArray)) {
                    if (count($tempFieldArray) == 3) {
                        $detailLayoutFieldArray[$tempFieldArray[1]][$field] = $field;
                    } else {
                        $formFieldArray[$field] = $field;
                    }
                }
            }
            $formFieldReportData         = [];
            $detailLayoutFieldReportData = [];
            $reportDataResult            = [];
            if (!empty($formFieldArray)) {
                // 表单字段的报表数据
                $reportDataResult = $this->getFlowReportDataFunction($datasource_group_by, $formFieldArray, $chart_search);
            }
            if (!empty($detailLayoutFieldArray)) {
                // 一个或多个明细字段的数据 分开查询合并
                foreach ($detailLayoutFieldArray as $key => $value) {
                    $detailLayoutFieldReportData = $this->getFlowReportDataFunction($datasource_group_by, $value, $chart_search);
                    $reportDataResult            = array_merge($reportDataResult, $detailLayoutFieldReportData);
                }
            }
            return $reportDataResult;
        }
    }

    /**
     * 为报表模块返回数据主函数
     * @param  [type] $datasource_group_by      [description]
     * @param  [type] $datasource_data_analysis [description]
     * @param  [type] $chart_search             [description]
     * @return [type]                           [description]
     */
    public function getFlowReportDataFunction($datasource_group_by, $datasource_data_analysis, $chart_search)
    {
        $userNameArray = app($this->userRepository)->getUserIdAsKeyAndNameAsValueArray();
        // 流程相关信息
        $flowId       = $chart_search["workflowID"];
        $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowId);
        if (empty($flowTypeInfo)) {
            return [];
        }
        $formId         = $flowTypeInfo->form_id;
        $flowName       = $flowTypeInfo->flow_name;
        $result         = [];
        $reportData     = [];
        $group_by_field = "";
        $whereRaw       = [];
        // 获取数据参数
        $getDataParam                      = [];
        $getDataParam["search"]["flow_id"] = [$flowId];
        $getDataParam["whereRaw"]          = [];
        // group by之后，select后面跟的，sum 还是 count的标识
        $selectField = "";
        // 数据分析判断
        // group_select_fields的查询，是用addSelect实现的
        $detailLayoutTableName = '';
        $tempFieldArray        = explode('_', str_replace('_TEXT', '', $datasource_group_by));
        if (!empty($tempFieldArray) && count($tempFieldArray) == 3) {
            $detailLayoutTableName = 'zzzz_flow_data_' . $formId . '_' . $tempFieldArray[1];
        }
        if (isset($datasource_data_analysis["count"]) && $datasource_data_analysis["count"] == "count") {
            $getDataParam["group_select_fields"][] = "count(flow_run.run_id) as count";
            $getDataParam["group_select_fields"][] = $datasource_group_by;
        } else {
            foreach ($datasource_data_analysis as $field) {
                // $getDataParam["group_select_fields"][] = "SUM({$field}) as {$field}";
                $tempFieldArray = explode('_', str_replace('_TEXT', '', $field));
                if (!empty($tempFieldArray) && count($tempFieldArray) == 3) {
                    $detailLayoutTableName                 = 'zzzz_flow_data_' . $formId . '_' . $tempFieldArray[1];
                    $tempField                             = $detailLayoutTableName . '.' . $field;
                    $getDataParam["group_select_fields"][] = "SUM(CAST({$tempField} AS DECIMAL(10,2))) as {$field}";
                } else {
                    $getDataParam["group_select_fields"][] = "SUM(CAST({$field} AS DECIMAL(10,2))) as {$field}";
                }
            }
            if ($datasource_group_by != 'start_time' && $datasource_group_by != 'end_time' && $datasource_group_by != 'creator') {
                $getDataParam["group_select_fields"][] = $datasource_group_by;
            }
        }
        $getDataParam["table"]  = $detailLayoutTableName;
        $getDataParam["formId"] = $formId;
        // 取表单解析，用来处理分析字段的title
        $flowFormParseInfo = app($this->flowFormService)->getParseForm($formId, []);
        $controlItemInfo   = [];
        $formSearchParams  = [];
        $formDateSearchParams = [];
        if (count($flowFormParseInfo)) {
            foreach ($flowFormParseInfo as $key => $controlInfo) {
                if (array_key_exists($controlInfo["control_id"], $chart_search) && isset($chart_search[$controlInfo["control_id"]]['selectedRelation']) && isset($chart_search[$controlInfo["control_id"]]['inputValue'])) {
                    // 生成表单字段查询参数
                    $formSearchParams[$controlInfo["control_id"]] = [
                        'relation' => $chart_search[$controlInfo["control_id"]]['selectedRelation'],
                        'search'   => $chart_search[$controlInfo["control_id"]]['inputValue'],
                        "control"  => [
                            "control_type"      => $controlInfo['control_type'],
                            "control_parent_id" => $controlInfo['control_parent_id'],
                            "control_attribute" => (array) $controlInfo['control_attribute'],
                        ],
                    ];
                }
                // 涉及日期时间的筛选项处理
                if (array_key_exists($controlInfo["control_id"], $chart_search) && isset($chart_search[$controlInfo["control_id"]]['type']) && isset($chart_search[$controlInfo["control_id"]]['value'])) {
                    $formDateSearchParams[$controlInfo["control_id"]] = [
                        'type' => $chart_search[$controlInfo["control_id"]]['type'],
                        'value'   => $chart_search[$controlInfo["control_id"]]['value'],
                        "control"  => [
                            "control_type"      => $controlInfo['control_type'],
                            "control_parent_id" => $controlInfo['control_parent_id'],
                            "control_attribute" => (array) $controlInfo['control_attribute'],
                        ],
                    ];
                }
                $attribute     = isset($controlInfo["control_attribute"]) ? $controlInfo["control_attribute"] : [];
                $dataEfbSource = isset($attribute["data-efb-source"]) ? $attribute["data-efb-source"] : "";
                if ($controlInfo['control_type'] == 'data-selector' || $controlInfo['control_type'] == "select") {
                    // 下拉框的支持key之后这里全改成_TEXT
                    $controlInfo["control_id"] = $controlInfo["control_id"] . '_TEXT';
                }
                $controlItemInfo[$controlInfo["control_id"]] = $controlInfo["control_title"];
            }
        }
        // 表单字段筛选条件 --- 时间日期字段筛选
        if (!empty($formDateSearchParams)) {
            $formDateSearchString = $this->handleDateSearch($formDateSearchParams, $formId);
            $getDataParam["formDateSearchString"] = $formDateSearchString['formSearch'];
            // 明细字段的过滤条件转换为run_id集合
            if ($formDateSearchString["detailSearch"]) {
                $layoutListParam["detailTableWhereString"] = $formDateSearchString["detailSearch"]['detailSearchString'];
                $layoutListParam["detailControlParentId"]  = $formDateSearchString["detailSearch"]["detailControlParentId"];
                $layoutListParam["formId"]                 = $formId;
                $layoutListParam["flowId"]                 = $flowId;
                // 查明细范围内的 run_id
                $layoutList                       = app($this->flowRunRepository)->getFlowZzzzFlowDataSearchList($layoutListParam);
                $layoutRunIdList                  = $layoutList->pluck("run_id")->toArray();
                if (isset($getDataParam["search"]["run_id"]) && !empty($getDataParam["search"]["run_id"])) {
                    $getDataParam["search"]["run_id"][0] = array_merge($layoutRunIdList, $getDataParam["search"]["run_id"]);
                } else {
                    $getDataParam["search"]["run_id"] = [$layoutRunIdList, "in"];
                }
            }
        }
        // 表单字段筛选条件 --- 表单文本字段筛选
        if (!empty($formSearchParams)) {
            $layoutListParam       = [];
            $relationSqlStringInfo = app($this->flowService)->getRelationSqlStringFromSearchInfo($formSearchParams, $formId);
            if ($relationSqlStringInfo["mainTableWhereString"]) {
                $getDataParam["mainTableWhereString"] = $relationSqlStringInfo["mainTableWhereString"];
            }
            // 明细字段的过滤条件转换为run_id集合
            if ($relationSqlStringInfo["detailTableWhereString"]) {
                $layoutListParam["detailTableWhereString"] = $relationSqlStringInfo["detailTableWhereString"];
                $layoutListParam["detailControlParentId"]  = $relationSqlStringInfo["detailControlParentId"];
                $layoutListParam["formId"]                 = $formId;
                $layoutListParam["flowId"]                 = $flowId;
                // 查明细范围内的 run_id
                $layoutList                       = app($this->flowRunRepository)->getFlowZzzzFlowDataSearchList($layoutListParam);
                $layoutRunIdList                  = $layoutList->pluck("run_id")->toArray();
                if (isset($getDataParam["search"]["run_id"]) && !empty($getDataParam["search"]["run_id"])) {
                    $getDataParam["search"]["run_id"][0] = array_unique(array_merge($layoutRunIdList, $getDataParam["search"]["run_id"]));
                } else {
                    $getDataParam["search"]["run_id"] = [array_unique($layoutRunIdList), "in"];
                }
                $getDataParam['detailTableWhereString'] = $relationSqlStringInfo["detailTableWhereString"];
            }
        }
        // 基本字段筛选条件 --- 默认字段筛选
        $filterInfo = $this->getFlowReportFilterByWhere($chart_search);
        if (count($filterInfo)) {
            if (isset($filterInfo["common"]) && count($filterInfo["common"])) {
                foreach ($filterInfo["common"] as $common_key => $common_value) {
                    $getDataParam["search"][$common_key] = $common_value;
                }
            }
            if (isset($filterInfo["raw"]) && count($filterInfo["raw"])) {
                foreach ($filterInfo["raw"] as $raw_key => $raw_value) {
                    $getDataParam["whereRaw"][] = $raw_value;
                }
            }
        }
        // 分组依据判断
        if ($datasource_group_by == "creator") {
            $getDataParam["groupBy"]    = "flow_run.creator";
            $getDataParam["fields"][]   = "creator";
            $getDataParam["returntype"] = "object";
            $reportInfo                 = app($this->flowRunRepository)->getFlowRunReportData($getDataParam);
            $resultData                 = [];
            foreach ($datasource_data_analysis as $field) {
                $dataItem = [];
                if ($reportInfo->count()) {
                    foreach ($reportInfo->toArray() as $info_key => $info_value) {
                        $info_value          = (array) $info_value;
                        $userName            = isset($userNameArray[$info_value['creator']]) ? $userNameArray[$info_value['creator']] : '';
                        $dataItem[$info_key] = ["name" => $userName, "y" => (float) $info_value[$field]];
                    }
                }
                if ($field == "count") {
                    $field = trans("flow.0x030114"); // 数量
                } else {
                    $field = isset($controlItemInfo[$field]) ? $controlItemInfo[$field] : "";
                }
                $field        = $flowName . '-' . $field;
                $resultData[] = ['data' => $dataItem, 'name' => $field, 'group_by' => trans("flow.0x030074")]; // 创建人
            }
            return $resultData;
        } else if ($datasource_group_by == "start_time") {
            // 外部参数传递
            $dateType                = $chart_search['dateType'];
            $dateValue               = $chart_search['dateValue'];
            $tempData                = $this->getDatexAxis($dateType, $dateValue);
            $getDataParam["groupBy"] = "DATE_FORMAT";
            // 处理报表图 x 轴的筛选&来自于【时间维度】的默认查询条件
            if ($dateType == "year") {
                $getDataParam["selectRaw"] = ["DATE_FORMAT(flow_run.create_time, '%Y') DATE_FORMAT"];
            } else if ($dateType == "quarter") {
                $getDataParam["selectRaw"]  = ["quarter(flow_run.create_time) DATE_FORMAT"];
                $getDataParam["whereRaw"][] = "DATE_FORMAT(flow_run.create_time, '%Y') = '" . $dateValue . "'";
            } else if ($dateType == "month") {
                $getDataParam["selectRaw"]  = ["DATE_FORMAT(flow_run.create_time, '%c') DATE_FORMAT"];
                $getDataParam["whereRaw"][] = "DATE_FORMAT(flow_run.create_time, '%Y') = '" . $dateValue . "'";
            } else if ($dateType == "day") {
                $getDataParam["selectRaw"]  = ["DATE_FORMAT(flow_run.create_time, '%e') DATE_FORMAT"];
                $getDataParam["whereRaw"][] = "DATE_FORMAT(flow_run.create_time, '%Y-%c') = '" . $dateValue . "'";
            }
            $getDataParam["returntype"] = "object";
            // 获取数据
            $reportInfo = app($this->flowRunRepository)->getFlowRunReportData($getDataParam);
            $resultData = [];
            foreach ($datasource_data_analysis as $field) {
                $xAxisItem = $tempData;
                if ($reportInfo->count()) {
                    foreach ($reportInfo->toArray() as $info_key => $info_value) {
                        $info_value                      = (array) $info_value;
                        $infoDateFormat                  = $info_value["DATE_FORMAT"];
                        $xAxisItem[$infoDateFormat]["y"] = (float) $info_value[$field];
                    }
                }
                if ($field == "count") {
                    $field = trans("flow.0x030114"); // 数量
                } else {
                    $field = $controlItemInfo[$field];
                }
                $field        = $flowName . '-' . $field;
                $resultData[] = ['data' => $xAxisItem, 'name' => $field, 'group_by' => trans("flow.0x030075")]; // 创建时间
            }
            return $resultData;
        } else if ($datasource_group_by == "end_time") {
            // 外部参数传递
            $dateType                = $chart_search['dateType'];
            $dateValue               = $chart_search['dateValue'];
            $tempData                = $this->getDatexAxis($dateType, $dateValue);
            $getDataParam["groupBy"] = "DATE_FORMAT";
            // 处理报表图 x 轴的筛选&来自于【时间维度】的默认查询条件
            if ($dateType == "year") {
                $getDataParam["selectRaw"] = ["DATE_FORMAT(flow_run.transact_time, '%Y') DATE_FORMAT"];
            } else if ($dateType == "quarter") {
                $getDataParam["selectRaw"]  = ["quarter(flow_run.transact_time) DATE_FORMAT"];
                $getDataParam["whereRaw"][] = "DATE_FORMAT(flow_run.transact_time, '%Y') = '" . $dateValue . "'";
            } else if ($dateType == "month") {
                $getDataParam["selectRaw"]  = ["DATE_FORMAT(flow_run.transact_time, '%c') DATE_FORMAT"];
                $getDataParam["whereRaw"][] = "DATE_FORMAT(flow_run.transact_time, '%Y') = '" . $dateValue . "'";
            } else if ($dateType == "day") {
                $getDataParam["selectRaw"]  = ["DATE_FORMAT(flow_run.transact_time, '%e') DATE_FORMAT"];
                $getDataParam["whereRaw"][] = "DATE_FORMAT(flow_run.transact_time, '%Y-%c') = '" . $dateValue . "'";
            }
            // 结束时间分组，默认带上结束的参数
            $getDataParam["search"]["current_step"] = [0];
            $getDataParam["returntype"]             = "object";
            // 获取数据
            $reportInfo = app($this->flowRunRepository)->getFlowRunReportData($getDataParam);
            $resultData = [];
            foreach ($datasource_data_analysis as $field) {
                $xAxisItem = $tempData;
                if ($reportInfo->count()) {
                    foreach ($reportInfo->toArray() as $info_key => $info_value) {
                        $info_value                      = (array) $info_value;
                        $infoDateFormat                  = $info_value["DATE_FORMAT"];
                        $xAxisItem[$infoDateFormat]["y"] = (float) $info_value[$field];
                    }
                }
                if ($field == "count") {
                    $field = trans("flow.0x030114"); // 数量
                } else {
                    $field = $controlItemInfo[$field];
                }
                $field        = $flowName . '-' . $field;
                $resultData[] = ['data' => $xAxisItem, 'name' => $field, 'group_by' => trans("flow.0x030113")]; // 结束时间
            }
            return $resultData;
        } else {
            // 其他分组依据，DATA_1 .. DATD_n，来自表单字段
            $getDataParam["groupBy"]    = $datasource_group_by;
            $getDataParam["returntype"] = "object";
            // 获取数据
            $reportInfo         = app($this->flowRunRepository)->getFlowRunReportData($getDataParam);
            $resultData         = [];
            $tempEmptyInfoValue = 0;
            $xAxisItem          = [];
            foreach ($datasource_data_analysis as $field) {
                if ($reportInfo->count()) {
                    foreach ($reportInfo->toArray() as $info_key => $info_value) {
                        $info_value     = (array) $info_value;
                        $infoDateFormat = $info_value[$datasource_group_by];
                        if (empty($infoDateFormat)) {
                            // 空
                            $xAxisItem[trans("flow.0x030115")]["name"] = $infoDateFormat;
                            $xAxisItem[trans("flow.0x030115")]["y"]    = (float) (floatval($info_value[$field]) + $tempEmptyInfoValue);
                        } else {
                            $xAxisItem[$infoDateFormat]["name"] = $infoDateFormat;
                            $xAxisItem[$infoDateFormat]["y"]    = (float) $info_value[$field];
                        }
                    }
                }
                if ($field == "count") {
                    $field = trans("flow.0x030114"); // 数量
                } else {
                    $field = $controlItemInfo[$field];
                }
                $field        = $flowName . '-' . $field;
                $resultData[] = ['data' => $xAxisItem, 'name' => $field, 'group_by' => $controlItemInfo[$datasource_group_by]];
            }
            return $resultData;
        }
    }

    /**
     * 获取流程报表数据的时候，解析传递过来的【数据筛选】条件
     * 返回数组，两个key：common，可以拼接到search上面的普通查询条件;raw，需要放到DB:row里执行的条件
     * @param  [type] $searchArray [description]
     * @return [type]              [description]
     */
    public function getFlowReportFilterByWhere($searchArray)
    {
        $where = [];
        if ($searchArray) {
            foreach ($searchArray as $key => $value) {
                switch ($key) {
                    //创建人
                    case 'creator':
                        if (!empty($value)) {
                            $value                      = trim($value, ',');
                            $valueSplit                 = explode(",", $value);
                            $where["common"]["creator"] = [$valueSplit, "in"];
                            break;
                        }
                    // //创建时间
                    // case 'createTime' :
                    //     $valueSplit = explode(",", $value);
                    //     if (count($valueSplit) == 2) {
                    //         $timeStart = strtotime($valueSplit[0]);
                    //         $timeEnd = strtotime($valueSplit[1]) + 24*3600;
                    //         // $where .= " AND UNIX_TIMESTAMP(f.CREATE_TIME) >= '{$timeStart}' AND UNIX_TIMESTAMP(f.CREATE_TIME) < '{$timeEnd}' ";
                    //         $where["raw"][] = " UNIX_TIMESTAMP(flow_run.create_time) >= '{$timeStart}' AND UNIX_TIMESTAMP(flow_run.create_time) < '{$timeEnd}' ";
                    //     } else {
                    //         $time = strtotime($value);
                    //         $dayTime = $time + 24*3600;
                    //         // $where .= " AND UNIX_TIMESTAMP(f.CREATE_TIME) >= {$time} AND UNIX_TIMESTAMP(f.CREATE_TIME) < {$dayTime} ";
                    //         $where["raw"][] = " UNIX_TIMESTAMP(flow_run.CREATE_TIME) >= {$time} AND UNIX_TIMESTAMP(flow_run.CREATE_TIME) < {$dayTime} ";
                    //     }
                    //     break;
                    // //结束时间
                    // case 'overTime' :
                    //     // $where .= " AND f.CURRENT_STEP = 0";
                    //     // $where["common"]["current_step"] = ["0"];
                    //     $valueSplit = explode(",", $value);
                    //     if (count($valueSplit) == 2) {
                    //         $timeStart = strtotime($valueSplit[0]);
                    //         $timeEnd = strtotime($valueSplit[1]) + 24*3600;
                    //         // $where .= " AND UNIX_TIMESTAMP(f.TRANSACT_TIME) >= '{$timeStart}' AND UNIX_TIMESTAMP(f.TRANSACT_TIME) < '{$timeEnd}' ";
                    //         $where["raw"][] = " flow_run.current_step = 0 AND UNIX_TIMESTAMP(flow_run.TRANSACT_TIME) >= '{$timeStart}' AND UNIX_TIMESTAMP(flow_run.TRANSACT_TIME) < '{$timeEnd}' ";
                    //     } else {
                    //         $time = strtotime($value);
                    //         $dayTime = $time + 24*3600;
                    //         // $where .= " AND UNIX_TIMESTAMP(f.TRANSACT_TIME) >= {$time} AND UNIX_TIMESTAMP(f.TRANSACT_TIME) < {$dayTime} ";
                    //         $where["raw"][] = " flow_run.current_step = 0 AND UNIX_TIMESTAMP(flow_run.TRANSACT_TIME) >= {$time} AND UNIX_TIMESTAMP(flow_run.TRANSACT_TIME) < {$dayTime} ";
                    //     }
                    //     break;
                    case "startDate":
                        if (!empty($value)) {
                            $value = explode(',', $value);
                            if (isset($value[0]) && !empty($value[0])) {
                                $startDate1     = $value[0];
                                $where["raw"][] = "flow_run.create_time >= '" . $startDate1 . "'";
                            }
                            if (isset($value[1]) && !empty($value[1])) {
                                $startDate2     = $value[1];
                                $time_stamp     = strtotime($startDate2);
                                $start_time     = date("Y-m-d", mktime(0, 0, 0, date("m", $time_stamp), date("d", $time_stamp) + 1, date("Y", $time_stamp)));
                                $where["raw"][] = "flow_run.create_time < '" . $start_time . "'";
                            }
                        }
                        break;
                    case "endDate":
                        if (!empty($value)) {
                            $value = explode(',', $value);
                            if (isset($value[0]) && !empty($value[0])) {
                                $endDate1       = $value[0];
                                $where["raw"][] = "(flow_run.current_step = 0 AND flow_run.transact_time >= '" . $endDate1 . "')";
                            }
                            if (isset($value[1]) && !empty($value[1])) {
                                $endDate2       = $value[1];
                                $time_stamp_end = strtotime($endDate2);
                                $start_time_end = date("Y-m-d", mktime(0, 0, 0, date("m", $time_stamp_end), date("d", $time_stamp_end) + 1, date("Y", $time_stamp_end)));
                                $where["raw"][] = "(flow_run.current_step = 0 AND flow_run.transact_time < '" . $start_time_end . "')";
                            }
                        }
                        break;
                    //流程状态
                    case 'status':
                        if ($value != 0) {
                            if ($value == 1) {
                                // $where .= " AND f.CURRENT_STEP=0";
                                $where["common"]["current_step"] = ["0"];
                            } else {
                                // $where .= " AND f.CURRENT_STEP!=0";
                                $where["common"]["current_step"] = ["0", "!="];
                            }

                        }
                        break;
                    //紧急程度
                    case "instancyType":
                        if ($value !== '') {
                            $where["common"]["instancy_type"] = [$value];
                        }
                        break;
                    //流水号
                    case "runSeq":
                        if (!empty($value)) {
                            $where["common"]["run_seq"] = [$value, 'like'];
                        }
                        break;
                    //流程标题
                    case "runName":
                        if (!empty($value)) {
                            $where["common"]["run_name"] = [$value, 'like'];
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $where;
    }

    /**
     * 日期类型分组字段x坐标
     * @param  [type] $dateType  [description]
     * @param  [type] $dateValue [description]
     * @return [type]            [description]
     */
    public function getDatexAxis($dateType, $dateValue)
    {
        switch ($dateType) {
            //按年
            case 'year':
                $split = explode("-", $dateValue);
                if (count($split) == 2) {
                    for ($i = $split[0]; $i <= $split[1]; $i++) {
                        $reportData[$i] = array(
                            "name" => (string) $i,
                            "y"    => 0,
                        );
                    }
                }
                break;
            //按季度
            case 'quarter':
                $reportData[1] = array(
                    "name" => trans("flow.0x030117"), // 第一季度
                    "y"    => 0,
                );
                $reportData[2] = array(
                    "name" => trans("flow.0x030118"), // 第二季度
                    "y"    => 0,
                );
                $reportData[3] = array(
                    "name" => trans("flow.0x030119"), // 第三季度
                    "y"    => 0,
                );
                $reportData[4] = array(
                    "name" => trans("flow.0x030120"), // 第四季度
                    "y"    => 0,
                );
                break;
            //按月
            case 'month':
                for ($i = 1; $i <= 12; $i++) {
                    $reportData[$i] = array(
                        "name" => $i,
                        "y"    => 0,
                    );
                }
                break;
            //按天
            case 'day':
                //获取指定月的最后一天
                $lastDay = date('t', strtotime($dateValue));
                for ($i = 1; $i <= $lastDay; $i++) {
                    $reportData[$i] = array(
                        "name" => $i,
                        "y"    => 0,
                    );
                }
                break;
            default:
                break;
        }
        return $reportData;
    }

    /**
     * 报表-流程报表-获取用户id
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowUserIdsByReport($chartSearch)
    {
        if (isset($chartSearch['dept_id'])) {
            $deptId = explode(',', $chartSearch['dept_id']);

            $userIdByDept = [];
            if (!empty($deptId)) {
                foreach ($deptId as $key => $value) {
                    $dept = array_column(app($this->departmentRepository)->getALLChlidrenByDeptId($value), 'dept_id');

                    if (!empty($dept)) {
                        $dept[] = $value;

                        $depts = implode(',', $dept);

                        $userIdByDept = array_merge($userIdByDept, app($this->documentService)->getUserIdByDeptId($depts));
                    } else {
                        $userIdByDept = array_merge($userIdByDept, app($this->documentService)->getUserIdByDeptId($value));
                    }
                }
            }
        } else {
            $userIdByDept = [];
        }

        if (isset($chartSearch['role_id'])) {
            $userIdByRole = app($this->userRepository)::getUserIdsByRoleIds(explode(',', $chartSearch['role_id']));
        } else {
            $userIdByRole = [];
        }
        if (isset($chartSearch['user_id'])) {
            $userIdById = explode(',', trim($chartSearch['user_id'], ','));
        } else {
            $userIdById = [];
        }

        if (!isset($chartSearch['dept_id']) && !isset($chartSearch['role_id']) && isset($chartSearch['user_id'])) {
            $userIds = $userIdById;
        } elseif (isset($chartSearch['dept_id']) && !isset($chartSearch['role_id']) && isset($chartSearch['user_id'])) {
            $userIds = array_unique(array_intersect($userIdById, $userIdByDept));
        } elseif (isset($chartSearch['role_id']) && isset($chartSearch['dept_id']) && isset($chartSearch['user_id'])) {
            $userIds = array_unique(array_intersect($userIdById, $userIdByRole, $userIdByDept));
        } elseif (isset($chartSearch['role_id']) && isset($chartSearch['dept_id']) && !isset($chartSearch['user_id'])) {
            $userIds = array_unique(array_intersect($userIdByDept, $userIdByRole));
        } elseif (!isset($chartSearch['role_id']) && isset($chartSearch['dept_id']) && !isset($chartSearch['user_id'])) {
            $userIds = $userIdByDept;
        } elseif (isset($chartSearch['role_id']) && !isset($chartSearch['dept_id']) && !isset($chartSearch['user_id'])) {
            $userIds = $userIdByRole;
        } elseif (!isset($chartSearch['role_id']) && !isset($chartSearch['dept_id']) && !isset($chartSearch['user_id'])) {
            $userIds = '';
        } elseif (!isset($chartSearch['dept_id']) && isset($chartSearch['role_id']) && isset($chartSearch['user_id'])) {
            $userIds = array_unique(array_intersect($userIdById, $userIdByRole));
        }
        // 继续过滤用户状态
        if (isset($chartSearch['user_status']) ) {
            // user_status 0在职 1离职
            if ($chartSearch['user_status'] == 0 ) {
                $statusUserList = DB::table('user_system_info')->where('user_status' , '<>' , 2)->get()->pluck('user_id')->toArray();
            } elseif ($chartSearch['user_status'] == 1) {
                $statusUserList = DB::table('user_system_info')->where('user_status' , '=' , 2)->get()->pluck('user_id')->toArray();
            }
            if (!empty($userIds)) {
                $userIds = array_intersect($statusUserList , $userIds);
            } else {
               $userIds =  $statusUserList;
            }
        }
        return $userIds;
    }
    /**
     * 报表-流程报表-；流程类型过滤()
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowTypeDatasourceFilter()
    {
        $sorts = app($this->flowSortRepository)->getFlowSortListForMiddle();
        // 如果有0的流程分类，需要增加未分类类型
        $count = app($this->flowTypeRepository)->getflowNoSortCount();
        $sorts = $sorts->toArray();
        if ($count > 0) {
            array_push($sorts, ['id' => 0, 'title' => trans('flow.unclassified')]);
        }
        $sorts           = array_column($sorts, 'title', 'id');
        $flowSystemField = [
            [
                //流程类型
                'filter_type' => 'singleton',
                'itemValue'   => 'flowsortId',
                'itemName'    => trans("report.flow_type"), // 流程类型
                'source'      => $sorts,
                'multiple'    => true,
            ],
            [
                'filter_type'   => 'selector',
                'selector_type' => 'dept',
                'itemValue'     => 'dept_id',
                'itemName'      => trans('report.flow_creater_dept'),
            ],
            [
                'filter_type'   => 'selector',
                'selector_type' => 'role',
                'itemValue'     => 'role_id',
                'itemName'      => trans('report.flow_creater_role'),
            ],
            [
                'filter_type'   => 'selector',
                'selector_type' => 'user',
                'itemValue'     => 'user_id',
                'itemName'      => trans('report.flow_creater'),
            ],
            [
                'filter_type' => 'date',
                'itemValue'   => 'date_range',
                'itemName'    => trans('report.flow_date_range'),
            ],
            [
                'itemName'      => trans('report.flow_name'), //页面显示名称
                'itemValue'     => 'flowID', //报表参数
                'selector_type' => 'defineFlowList', //选择器,
                'filter_type'   => 'selector',
            ],
        ];
        return $flowSystemField;
    }
    /**
     * 报表-流程报表-；流程类型过滤()
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowTypeHandleDatasourceFilter()
    {
        $sorts = app($this->flowSortRepository)->getFlowSortListForMiddle();
        // 如果有0的流程分类，需要增加未分类类型
        $count = app($this->flowTypeRepository)->getflowNoSortCount();
        $sorts = $sorts->toArray();
        if ($count > 0) {
            array_push($sorts, ['id' => 0, 'title' => trans('flow.unclassified')]);
        }
        $sorts           = array_column($sorts, 'title', 'id');
        $flowSystemField = [
            [
                //流程类型
                'filter_type' => 'singleton',
                'itemValue'   => 'flowsortId',
                'itemName'    => trans("report.flow_type"), // 流程类型
                'source'      => $sorts,
                'multiple'    => true,
            ],
            [
                'filter_type'   => 'selector',
                'selector_type' => 'dept',
                'itemValue'     => 'dept_id',
                'itemName'      => trans('report.flow_handler_dept'),
            ],
            [
                'filter_type'   => 'selector',
                'selector_type' => 'role',
                'itemValue'     => 'role_id',
                'itemName'      => trans('report.flow_handler_role'),
            ],
            [
                'filter_type'   => 'selector',
                'selector_type' => 'user',
                'itemValue'     => 'user_id',
                'itemName'      => trans('report.flow_handler'),
            ],
            [
                'filter_type' => 'date',
                'itemValue'   => 'date_range',
                'itemName'    => trans('report.flow_date_range'),
            ],
            [
                'itemName'      => trans('report.flow_name'), //页面显示名称
                'itemValue'     => 'flowID', //报表参数
                'selector_type' => 'defineFlowList', //选择器,
                'filter_type'   => 'selector',
            ],
            [
                //是否离职
                'filter_type' => 'singleton',
                'itemValue'   => 'user_status',
                'itemName'    => trans('report.user_status'), // 流程类型
                'source'      => [ trans('report.job') , trans('report.leave')],
            ],
        ];
        return $flowSystemField;
    }

    /**
     * 报表-流程报表-流程类型分析(流程类型或名称)
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowTypeReportData($datasourceGroupBy = 'flowType', $datasourceDataAnalysis = '', $chartSearch)
    {
        $data = [];
        //获取流程类型数量
        $overarr    = [];
        $runningarr = [];
        //搜索条件处理
        $chartSearch['userIds']   = $this->getFlowUserIdsByReport($chartSearch);
        $flowCountGroupByFlowType = app($this->flowSortRepository)->getFlowCountGroupByCustomType($datasourceGroupBy, $chartSearch);
        if (!empty($flowCountGroupByFlowType)) {
            foreach ($flowCountGroupByFlowType as $sortKey => $sortValue) {
                $typeOverCount    = 0;
                $typeRunningCount = 0;
                if (isset($sortValue['flow_sort_has_many_flow_type']) && !empty($sortValue['flow_sort_has_many_flow_type'])) {
                    $types = $sortValue['flow_sort_has_many_flow_type'];
                    foreach ($types as $typeKey => $typeValue) {
                        $OverCount    = 0;
                        $RunningCount = 0;
                        if (isset($typeValue['flow_type_has_many_flow_run']) && !empty($typeValue['flow_type_has_many_flow_run'])) {
                            $runs = $typeValue['flow_type_has_many_flow_run'];
                            foreach ($runs as $runKey => $runValue) {
                                if ($runValue['current_step'] == 0) {
                                    $typeOverCount = $typeOverCount + $runValue['run_count'];
                                    $OverCount     = $OverCount + $runValue['run_count'];
                                } else if ($runValue['is_effect'] == 1) {
                                    //  隐藏且停用不加入计算
                                    $typeRunningCount = $typeRunningCount + $runValue['run_count'];
                                    $RunningCount     = $RunningCount + $runValue['run_count'];
                                }
                            }
                        }
                        $overarr[]    = ['name' => $typeValue['flow_name'], 'y' => $OverCount];
                        $runningarr[] = ['name' => $typeValue['flow_name'], 'y' => $RunningCount];
                    }
                }
                $typeoverarr[]    = ['name' => $sortValue['title'], 'y' => $typeOverCount];
                $typerunningarr[] = ['name' => $sortValue['title'], 'y' => $typeRunningCount];
            }
        }
        if ($datasourceGroupBy == 'flowType') {
            $runningarr = $typerunningarr;
            $overarr    = $typeoverarr;
            $group      = trans('report.flow_type');
        } else {
            $group = trans('report.flow_title');
        }

        //判断是流程类型是待办数量和已归档
        if (isset($datasourceDataAnalysis['overcount']) && isset($datasourceDataAnalysis['runningcount'])) {
            $data1 = array(
                'name'     => trans('report.running_count'),
                'group_by' => $group,
                'data'     => array(),
            );
            $data1['data'] = array_values($runningarr);
            $data[]        = $data1;
            $data2         = array(
                'name'     => trans('report.over_count'),
                'group_by' => $group,
                'data'     => array(),
            );
            $data2['data'] = array_values($overarr);
            $data[]        = $data2;
        } else {
            //判断是流程类型是待办数量和已归档
            if (isset($datasourceDataAnalysis['runningcount']) || empty($datasourceDataAnalysis) || isset($datasourceDataAnalysis['count'])) {
                $data1 = array(
                    'name'     => trans('report.running_count'),
                    'group_by' => $group,
                    'data'     => array(),
                );
                $ys = array_column($runningarr, 'y');
                array_multisort($ys, SORT_DESC, $runningarr);
                $data1['data'] = array_values($runningarr);
                $data[]        = $data1;
            }
            if (isset($datasourceDataAnalysis['overcount'])) {
                $data2 = array(
                    'name'     => trans('report.over_count'),
                    'group_by' => $group,
                    'data'     => array(),
                );
                $ys = array_column($overarr, 'y');
                array_multisort($ys, SORT_DESC, $overarr);
                $data2['data'] = array_values($overarr);
                $data[]        = $data2;
            }
        }
        return $data;
    }

    /**
     * 报表-流程报表-待办流程分析(用户部门或角色)
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowToDoReportData($datasourceGroupBy = 'userDept', $datasourceDataAnalysis = '', $chartSearch)
    {
        //获取用户待办数量
        if ($datasourceGroupBy == 'user') {
            $data = array(
                0 => [
                    'name'     => trans('report.to_do_count'),
                    'group_by' => trans('report.user'),
                    'data'     => array(),
                ],
            );
        } else if ($datasourceGroupBy == 'userDept') {
            $data = array(
                0 => [
                    'name'     => trans('report.to_do_count'),
                    'group_by' => trans('report.user_dept'),
                    'data'     => array(),
                ],
            );
        } else if ($datasourceGroupBy == 'userRole') {
            $data = array(
                0 => [
                    'name'     => trans('report.to_do_count'),
                    'group_by' => trans('report.user_role'),
                    'data'     => array(),
                ],
            );
        } else {
            return array();
        }
        $chartSearch['userIds'] = $this->getFlowUserIdsByReport($chartSearch);

        $flowtododatas   = app($this->flowRunStepRepository)->getFlowToDoCountGroupByCustomType($datasourceGroupBy, $chartSearch);
        $data[0]['data'] = $flowtododatas;
        return $data;
    }
    /**
     * 报表-流程报表-待办流程路径分析(依据：流程名称)
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowToDoPathReportData($datasourceGroupBy = 'flowName', $datasourceDataAnalysis = '', $chartSearch)
    {
        $data = [];
        //获取流程类型数量
        $runningarr = [];
        //搜索条件处理
        $chartSearch['userIds']   = $this->getFlowUserIdsByReport($chartSearch);
        $flowCountGroupByFlowType = app($this->flowTypeRepository)->getFlowPathCountGroupByCustomType($datasourceGroupBy, $chartSearch);
        if (!empty($flowCountGroupByFlowType)) {
            foreach ($flowCountGroupByFlowType as $flowKey => $flowValue) {
                $RunningCount = 0;
                if (isset($flowValue['flow_type_has_many_flow_run_step']) && !empty($flowValue['flow_type_has_many_flow_run_step'])) {
                    $types = $flowValue['flow_type_has_many_flow_run_step'];
                    foreach ($types as $typeKey => $typeValue) {
                        $RunningCount = $RunningCount + $typeValue['run_count'];
                    }
                }
                $runningarr[] = ['name' => $flowValue['flow_name'], 'y' => $RunningCount];
            }
        }
        $group = trans('report.flow_name');
        $data1 = array(
            'name'     => trans('report.to_do_count'),
            'group_by' => $group,
            'data'     => array(),
        );
        $ys = array_column($runningarr, 'y');
        array_multisort($ys, SORT_DESC, $runningarr);
        $data1['data'] = array_values($runningarr);
        $data[]        = $data1;
        return $data;
    }

    /**
     * 报表-流程报表-流程效率分析(依据：分组依据：流程名称)
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowEfficiencyReportData($datasourceGroupBy = 'flowName', $datasourceDataAnalysis = '', $chartSearch)
    {
        $data = [];
        //获取流程类型数量
        $efficiency = [];
        //搜索条件处理
        $chartSearch['userIds']   = $this->getFlowUserIdsByReport($chartSearch);
        $flowCountGroupByFlowType = app($this->flowTypeRepository)->getFlowEfficiencyCountGroupByCustomType($datasourceGroupBy, $chartSearch);
        if (!empty($flowCountGroupByFlowType)) {
            foreach ($flowCountGroupByFlowType as $typeKey => $typeValue) {
                $sumtime = 0;
                $runs    = $typeValue['flow_type_has_many_flow_run'];
                $number  = count($runs);
                foreach ($runs as $runKey => $runValue) {
                    //计算总的处理时间
                    $process = $runValue['flow_run_has_many_flow_run_process'];
                    //计算出最小的接受时间和最大的处理时间
                    $min = 0;
                    $max = 0;
                    foreach ($process as $processKey => $processValue) {
                        if ($processValue['process_id'] == 1) {
                            $min = strtotime($processValue['receive_time']);
                        }
                        if ($processValue['host_flag'] == 1) {
                            if (!empty($processValue['deliver_time'])) {
                                $maxtime = strtotime($processValue['deliver_time']);
                            } else {
                                $max = time();
                                //如果此时是停办且隐藏且未办理完则该run_id不参与计算
                                if ($runValue['is_effect'] != 1) {
                                    $min = 0;
                                    $max = 0;
                                    $number--;
                                    break;
                                }

                            }
                        } else {
                            if (!empty($processValue['saveform_time'])) {
                                $maxtime = strtotime($processValue['saveform_time']);
                            } else {
                                $max = time();
                                if ($runValue['is_effect'] != 1) {
                                    $min = 0;
                                    $max = 0;
                                    $number--;
                                    break;
                                }

                            }
                        }
                        if (isset($maxtime) && $maxtime > $max) {
                            $max = $maxtime;
                        }
                    }
                    $sumtime = $sumtime + ($max - $min);
                }
                $time = $this->fomateTimeForFlowReport($number, $sumtime);
                //时间处理
                $efficiency[] = ['name' => $typeValue['flow_name'], 'y' => $time['avg'], 'tips' => $time['tips']];
            }
        }

        $group = trans('report.flow_name');
        $data1 = array(
            'name'     => trans('report.average_handle_time'),
            'title'    => '(' . trans("flow.0x030059") . ')',
            'group_by' => $group,
            'data'     => array(),
        );
        $ys = array_column($efficiency, 'y');
        array_multisort($ys, SORT_DESC, $efficiency);
        $data1['data'] = array_values($efficiency);
        $data[]        = $data1;
        return $data;
    }

    //格式化流程报表显示时间
    public function fomateTimeForFlowReport($number, $sumtime)
    {
        if (!empty($number)) {
            $avgsecond = $sumtime / $number;
            $d         = floor($avgsecond / (3600 * 24));
            $h         = floor(($avgsecond % (3600 * 24)) / 3600);
            $m         = floor((($avgsecond % (3600 * 24)) % 3600) / 60);
            $s         = floor((($avgsecond % 60)));
            $avg       = round($d + $h / 24 + $m / (24 * 60) + $s / (24 * 60 * 3600), 7);
            $tips      = $d . trans("flow.0x030059") . $h . trans("flow.0x030060") . $m . trans("flow.0x030061") . $s . trans("flow.0x030062");
        } else {
            $avg  = 0;
            $tips = "0" . trans("flow.0x030059") . "0" . trans("flow.0x030060") . "0" . trans("flow.0x030061") . '0' . trans("flow.0x030062");
        }
        return ['avg' => $avg, 'tips' => $tips];
    }

    /**
     * 报表-流程报表-流程办理效率分析(依据：分组依据：部门用户角色)
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowHandleEfficiencyReportData($datasourceGroupBy = 'userDept', $datasourceDataAnalysis = '', $chartSearch)
    {
        if ($datasourceGroupBy == 'user') {
            $data = array(
                0 => [
                    'name'     => trans('report.average_handle_time'),
                    'group_by' => trans('report.user'),
                    'title'    => '(' . trans("flow.0x030059") . ')',
                    'data'     => array(),
                ],
            );
        } else if ($datasourceGroupBy == 'userDept') {
            $data = array(
                0 => [
                    'name'     => trans('report.average_handle_time'),
                    'group_by' => trans('report.user_dept'),
                    'title'    => '(' . trans("flow.0x030059") . ')',
                    'data'     => array(),
                ],
            );
        } else if ($datasourceGroupBy == 'userRole') {
            $data = array(
                0 => [
                    'name'     => trans('report.average_handle_time'),
                    'group_by' => trans('report.user_role'),
                    'title'    => '(' . trans("flow.0x030059") . ')',
                    'data'     => array(),
                ],
            );
        } else {
            return array();
        }
        $chartSearch['userIds'] = $this->getFlowUserIdsByReport($chartSearch);
        $flowRunDatas           = app($this->flowRunProcessRepository)->getFlowHandleEfficiencyGroupByCustomType($datasourceGroupBy, $chartSearch);
        if ($datasourceGroupBy == 'user') {
            foreach ($flowRunDatas as $runKey => $runValue) {
                $sumtime = 0;
                $process = $runValue['user_has_many_flow_run_process'];
                $number  = count($process);
                //停用且隐藏且未结束的不参与计算
                foreach ($process as $processKey => $processValue) {
                    if ($processValue['host_flag'] == 1) {
                        if (!empty($processValue['deliver_time'])) {
                            $chatime = strtotime($processValue['deliver_time']) - strtotime($processValue['receive_time']);
                        } else {
                            if ($processValue['is_effect'] == 1) {
                                $chatime = time() - strtotime($processValue['receive_time']);
                            } else {
                                $number--;
                                $chatime = 0;
                            }
                        }
                    } else {
                        if (!empty($processValue['saveform_time'])) {
                            $chatime = strtotime($processValue['saveform_time']) - strtotime($processValue['receive_time']);
                        } else {
                            if ($processValue['is_effect'] == 1) {
                                $chatime = time() - strtotime($processValue['receive_time']);
                            } else {
                                $number--;
                                $chatime = 0;
                            }
                        }
                    }
                    $sumtime = $sumtime + $chatime;

                }
                $time = $this->fomateTimeForFlowReport($number, $sumtime);
                //时间处理
                $efficiency[] = ['name' => $runValue['name'], 'y' => $time['avg'], 'tips' => $time['tips']];
            }
        } else if ($datasourceGroupBy == 'userDept' || $datasourceGroupBy == 'userRole') {
            foreach ($flowRunDatas as $runKey => $runValue) {
                $sumtime = 0;
                $number  = 0;
                if ($datasourceGroupBy == 'userDept') {
                    $users = $runValue['department_has_many_user'];
                } else {
                    $users = $runValue['role_has_many_user'];
                }
                foreach ($users as $userKey => $userVal) {
                    $process = $userVal['user_has_many_flow_run_process'];
                    $number  = $number + count($process);
                    foreach ($process as $processKey => $processValue) {
                        if ($processValue['host_flag'] == 1) {
                            if (!empty($processValue['deliver_time'])) {
                                $chatime = strtotime($processValue['deliver_time']) - strtotime($processValue['receive_time']);
                            } else {
                                if ($processValue['is_effect'] == 1) {
                                    $chatime = time() - strtotime($processValue['receive_time']);
                                } else {
                                    $chatime = 0;
                                    $number--;
                                }
                            }
                        } else {
                            if (!empty($processValue['saveform_time'])) {
                                $chatime = strtotime($processValue['saveform_time']) - strtotime($processValue['receive_time']);
                            } else {
                                if ($processValue['is_effect'] == 1) {
                                    $chatime = time() - strtotime($processValue['receive_time']);
                                } else {
                                    $chatime = 0;
                                    $number--;
                                }
                            }
                        }
                        $sumtime = $sumtime + $chatime;

                    }
                }

                $time = $this->fomateTimeForFlowReport($number, $sumtime);
                //时间处理
                $efficiency[] = ['name' => $runValue['name'], 'y' => $time['avg'], 'tips' => $time['tips']];
            }
        } else {
            return array();
        }

        $ys = array_column($efficiency, 'y');
        array_multisort($ys, SORT_DESC, $efficiency);
        $data[0]['data'] = array_values($efficiency);
        return $data;
    }
    /**
     * 报表-流程报表-流程超期分析(分组依据流程类型或名称)
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowLimitEfficiencyReportData($datasourceGroupBy = 'flowType', $datasourceDataAnalysis = '', $chartSearch)
    {
        $data = [];
        //获取流程类型数量
        $limitArray     = [];
        $typeLimitArray = [];
        //搜索条件处理
        $chartSearch['userIds']   = $this->getFlowUserIdsByReport($chartSearch);
        $flowCountGroupByFlowType = app($this->flowSortRepository)->getFlowLimitCountGroupByCustomType($datasourceGroupBy, $chartSearch);
        foreach ($flowCountGroupByFlowType as $sortKey => $sortValue) {
            $typeLimitCount = 0;
            $types          = $sortValue['flow_sort_has_many_flow_type'];
            foreach ($types as $typeKey => $typeValue) {
                $limitCount = 0;
                $runs       = $typeValue['flow_type_has_many_flow_run'];
                foreach ($runs as $runKey => $runValue) {
                    foreach ($runValue['flow_run_has_many_flow_run_process'] as $processKey => $processValue) {
                        if ($processValue['host_flag'] == 1) {
                            if (empty($processValue['deliver_time'])) {
                                if (strtotime($processValue['limit_date']) > time()) {
                                    continue;
                                } else {
                                    //停用且隐藏的数据则不加入计算
                                    if ($processValue['is_effect'] == 1) {
                                        $limitCount++;
                                    }
                                    break;
                                }
                            }
                            if (strtotime($processValue['limit_date']) < strtotime($processValue['deliver_time'])) {
                                $limitCount++;
                                break;
                            }
                        } else {
                            if (empty($processValue['saveform_time'])) {
                                if (strtotime($processValue['limit_date']) > time()) {
                                    continue;
                                } else {
                                    if ($processValue['is_effect'] == 1) {
                                        $limitCount++;
                                    }
                                    break;
                                }
                            }
                            if (strtotime($processValue['limit_date']) < strtotime($processValue['saveform_time'])) {
                                $limitCount++;
                                break;
                            }
                        }
                    }

                }
                $typeLimitCount = $typeLimitCount + $limitCount;
                $limitArray[]   = ['name' => $typeValue['flow_name'], 'y' => $limitCount];
            }

            $typeLimitArray[] = ['name' => $sortValue['title'], 'y' => $typeLimitCount];
        }
        if ($datasourceGroupBy == 'flowType') {
            $limitArray = $typeLimitArray;
            $group      = trans('report.flow_type');
        } else {
            $group = trans('report.flow_title');
        }
        //超期数量
        $data1 = array(
            'name'     => trans('report.limit_count'),
            'group_by' => $group,
            'data'     => array(),
        );
        $sortArray = array_column($limitArray, 'y');
        array_multisort($sortArray, SORT_DESC, $limitArray);
        $data1['data'] = array_values($limitArray);
        $data[]        = $data1;

        return $data;
    }

    /**
     * 报表-流程报表-流程办理超期分析(依据：分组依据：部门用户角色)
     *
     * @author wz
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowHandleLimitReportData($datasourceGroupBy = 'userDept', $datasourceDataAnalysis = '', $chartSearch)
    {
        if ($datasourceGroupBy == 'user') {
            $data = array(
                0 => [
                    'name'     => trans('report.limit_count'),
                    'group_by' => trans('report.user'),
                    'data'     => array(),
                ],
            );
        } else if ($datasourceGroupBy == 'userDept') {
            $data = array(
                0 => [
                    'name'     => trans('report.limit_count'),
                    'group_by' => trans('report.user_dept'),
                    'data'     => array(),
                ],
            );
        } else if ($datasourceGroupBy == 'userRole') {
            $data = array(
                0 => [
                    'name'     => trans('report.limit_count'),
                    'group_by' => trans('report.user_role'),
                    'data'     => array(),
                ],
            );
        } else {
            return array();
        }
        $chartSearch['userIds'] = $this->getFlowUserIdsByReport($chartSearch);
        $flowRunDatas           = app($this->flowRunProcessRepository)->getFlowHandleLimitCountGroupByCustomType($datasourceGroupBy, $chartSearch);
        if ($datasourceGroupBy == 'user') {
            foreach ($flowRunDatas as $flowKey => $flowValue) {
                $limitCount = 0;
                $process    = $flowValue['user_has_many_flow_run_process'];
                foreach ($process as $processKey => $processValue) {
                    if ($processValue['host_flag'] == 1) {
                        if (empty($processValue['deliver_time'])) {
                            if (strtotime($processValue['limit_date']) < time() && $processValue['is_effect'] == 1) {
                                $limitCount++;
                            }
                        } else if (strtotime($processValue['limit_date']) < strtotime($processValue['deliver_time'])) {
                            $limitCount++;
                        }
                    } else {
                        if (empty($processValue['saveform_time'])) {
                            if (strtotime($processValue['limit_date']) < time() && $processValue['is_effect'] == 1) {
                                $limitCount++;
                            }
                        } else if (strtotime($processValue['limit_date']) < strtotime($processValue['saveform_time'])) {
                            $limitCount++;
                        }
                    }
                }
                $limitArray[] = ['name' => $flowValue['name'], 'y' => $limitCount];
            }
        } else if ($datasourceGroupBy == 'userDept' || $datasourceGroupBy == 'userRole') {
            foreach ($flowRunDatas as $runKey => $runValue) {
                $limitCount = 0;
                if ($datasourceGroupBy == 'userDept') {
                    $users = $runValue['department_has_many_user'];
                } else {
                    $users = $runValue['role_has_many_user'];
                }
                foreach ($users as $userKey => $userValue) {
                    $process = $userValue['user_has_many_flow_run_process'];
                    foreach ($process as $processKey => $processValue) {
                        if ($processValue['host_flag'] == 1) {
                            if (empty($processValue['deliver_time'])) {
                                if (strtotime($processValue['limit_date']) < time() && $processValue['is_effect'] == 1) {
                                    $limitCount++;
                                }
                            } else if (strtotime($processValue['limit_date']) < strtotime($processValue['deliver_time'])) {
                                $limitCount++;
                            }
                        } else {
                            if (empty($processValue['saveform_time'])) {
                                if (strtotime($processValue['limit_date']) < time() && $processValue['is_effect'] == 1) {
                                    $limitCount++;
                                }
                            } else if (strtotime($processValue['limit_date']) < strtotime($processValue['saveform_time'])) {
                                $limitCount++;
                            }
                        }
                    }
                }
                $limitArray[] = ['name' => $runValue['name'], 'y' => $limitCount];
            }
        } else {
            return array();
        }

        $sortArray = array_column($limitArray, 'y');
        array_multisort($sortArray, SORT_DESC, $limitArray);
        $data[0]['data'] = array_values($limitArray);
        return $data;
    }

    /**
     * 报表-流程报表-流程节点办理效率分析(依据：节点名称)
     *
     * @author wz
     *
     * @param  分组依据     ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     *
     * @return array
     */
    public function getFlowNodeHandleEfficiencyReportData($datasourceGroupBy = 'node', $datasourceDataAnalysis = '', $chartSearch)
    {
        $data = [];
        //获取流程类型数量
        $efficiency = [];
        //搜索条件处理
        $chartSearch['userIds']   = $this->getFlowUserIdsByReport($chartSearch);
        $flowCountGroupByFlowType = app($this->flowTypeRepository)->getFlowNodeEfficiencyCountGroupByCustomType($datasourceGroupBy, $chartSearch);
        // var_dump($flowCountGroupByFlowType );die;
        if (!empty($flowCountGroupByFlowType)) {
            foreach ($flowCountGroupByFlowType as $typeKey => $typeValue) {
                //暂时不计算自由流程
                if ($typeValue['flow_type'] == 2) {
                    continue;
                }
                $processes    = $typeValue['flow_type_has_many_flow_process'];
                $runs         = $typeValue['flow_type_has_many_flow_run'] ?? [];
                $runprocesses = [];
                foreach ($runs as $key => $value) {
                    $runprocesses = array_merge($runprocesses, $value['flow_run_has_many_flow_run_process'] ?? []);
                }
                foreach ($processes as $processKey => $processValue) {
                    $sumtime = 0;
                    $number  = 0;
                    foreach ($runprocesses as $runKey => $runValue) {
                        if ($processValue['node_id'] == $runValue['flow_process']) {
                            if ($runValue['host_flag'] == 1) {
                                if (!empty($runValue['deliver_time'])) {
                                    $sumtime += (strtotime($runValue['deliver_time']) - strtotime($runValue['receive_time']));
                                    $number++;
                                } else {
                                    //如果此时是停办且隐藏且未办理完则该run_id不参与计算
                                    if ($runValue['is_effect'] == 1 && !empty($runValue['receive_time'])) {
                                        $number++;
                                        $sumtime += (time() - strtotime($runValue['receive_time']));
                                    }
                                }
                            } else {
                                if (!empty($runValue['saveform_time'])) {
                                    $number++;
                                    $sumtime += (strtotime($runValue['saveform_time']) - strtotime($runValue['receive_time']));
                                } else {
                                    if ($runValue['is_effect'] == 1 && !empty($runValue['receive_time'])) {
                                        $number++;
                                        $sumtime += (time() - strtotime($runValue['receive_time']));
                                    }
                                }
                            }
                        }
                    }
                    $time = $this->fomateTimeForFlowReport($number, $sumtime);
                    //时间处理
                    $efficiency[] = ['name' => $processValue['process_name'] . '【' . $typeValue['flow_name'] . '】', 'y' => $time['avg'], 'tips' => $time['tips']];
                }
            }
        }
        $group = trans('report.flow_name');
        $data1 = array(
            'name'     => trans('report.average_handle_time'),
            'title'    => '(' . trans("flow.0x030059") . ')',
            'group_by' => $group,
            'data'     => array(),
        );
        $data1['data'] = $efficiency;
        $data[]        = $data1;
        return $data;
    }

    public function handleDateSearch($dateSearch, $formId)
    {
        $searchString = '';
        $detailControlParentId = [];
        $detailSearchString = '';
        foreach ($dateSearch as $key => $search) {
            $type = $search['type'];
            $value = $search['value'];
            $keyArray = explode('_', $key);
            $valueArray = explode(',', $value);
            $timeStart = $valueArray[0] ? strtotime($valueArray[0]) : '';
            $timeEnd = $valueArray[1] ? strtotime($valueArray[1]) : '';
            // 明细数据处理
            if (count($keyArray) > 2) {
                $detailControlParentId[] = $search['control']['control_parent_id'];
                $pid = $keyArray[1];
                $detailTableName = 'zzzz_flow_data_' . $formId . '_' . $pid;
                $fieldKey = $detailTableName . '.' . $key;
                if($timeStart && $fieldKey) {
                    $detailSearchString .= " UNIX_TIMESTAMP({$fieldKey}) >= '{$timeStart}' AND";
                }
                if ($timeEnd && $fieldKey) {
                    $detailSearchString .= " UNIX_TIMESTAMP({$fieldKey}) <= '{$timeEnd}' AND";
                }
            } else {
                // 非明细数据
                if ($type && $value) {
                    $fieldKey = 'zzzz_flow_data_'. $formId . '.' . $key;
                    if($timeStart && $fieldKey) {
                        $searchString .= " UNIX_TIMESTAMP({$fieldKey}) >= '{$timeStart}' AND";
                    }
                    if ($timeEnd && $fieldKey) {
                        $searchString .= " UNIX_TIMESTAMP({$fieldKey}) <= '{$timeEnd}' AND";
                    }
                }
            }
        }
        return ['detailSearch' => ['detailSearchString' => substr($detailSearchString, 0, -3), 'detailControlParentId' => $detailControlParentId], 'formSearch' => substr($searchString, 0, -3)];
    }

}
