<?php

namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;
use DB;
use Eoffice;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Schema;
use Cache;
use Illuminate\Support\Facades\Redis;
use App\Jobs\sendFlowCopyJob;
use Queue;

/**
 * 流程运行service类，用来调用所需资源，实现流程里，除了FlowService类以外的，和流程运行有关的服务功能。
 * 关键字：和流程运行有关
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunService extends FlowBaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 新建流程的时候，生成流程标题
     *
     * @method flowNewPageGenerateFlowRunName
     *
     * @return [type]                        [description]
     */
    public function flowNewPageGenerateFlowRunName($data)
    {
        $flowId = isset($data["flow_id"]) ? $data["flow_id"] : "";
        $userId = isset($data["creator"]) ? $data["creator"] : "";
        $userName = isset($data["user_name"]) ? $data["user_name"] : "";
        $formData = isset($data["form_data"]) ? $data["form_data"] : "";
        $formStructure = isset($data["form_structure"]) ? $data["form_structure"] : "";
        // 关联获取定义流程的所有数据
        $flowTypeAllObject = app($this->flowTypeRepository)->getFlowTypeInfoRepository($data, ['flow_form_type', 'flow_sort']);
        if (isset($flowTypeAllObject)) {
            $flowTypeAllInfo = $flowTypeAllObject->toArray();
        } else {
            // 流程不存在
            return ['code' => ['0x030001', 'flow']];
        }
        $runName = '';
        $currentTime = date('Y-m-d H:i:s', time());
        if (isset($flowTypeAllInfo['flow_name_rules']) && !empty($flowTypeAllInfo['flow_name_rules'])) {
            $runNameRule = json_decode($flowTypeAllInfo['flow_name_rules'], true);
            if (!empty($runNameRule) && is_array($runNameRule)) {
                foreach ($runNameRule as $key => $value) {
                    $type = $value['type'];
                    switch ($type) {
                            // 自增的自增重置依据的（暂不支持）
                        case 'increase':

                            break;
                            // 日期时间
                        case 'date':
                            $runName .= date($value['attribute']['format'], time());
                            break;
                            // 流程信息
                        case 'flowInfo':
                            if (isset($value['control_id'])) {
                                $controlId = $value['control_id'];
                                switch ($controlId) {
                                        // 定义流程名称
                                    case 'flowDefineName':
                                        $runName .= $flowTypeAllInfo['flow_name'];
                                        break;
                                        // 定义流程ID
                                    case 'flowDefineId':
                                        $runName .= $flowTypeAllInfo['flow_id'];
                                        break;
                                        // 表单名称
                                    case 'formName':
                                        $runName .= $flowTypeAllInfo['flow_type_has_one_flow_form_type']['form_name'];
                                        break;
                                        // 流程分类名称
                                    case 'flowSortName':
                                        $runName .= $flowTypeAllInfo['flow_type_belongs_to_flow_sort']['title'];
                                        break;
                                        // 表单ID
                                    case 'formId':
                                        $runName .= $flowTypeAllInfo['form_id'];
                                        break;
                                        // 流程创建人名称
                                    case 'flowCreator':
                                        $runName .= !empty($userId) ? app($this->userService)->getUserName($userId) : $userName;
                                        break;
                                        // 流程创建时间
                                    case 'flowCreateTime':
                                        $runName .= $currentTime;
                                        break;
                                        // 流程运行ID
                                    case 'flowRunId':

                                        break;
                                    default:

                                        break;
                                }
                            }
                            break;
                        case 'formData':
                            if (isset($value['control_id'])) {
                                $controlId = $value['control_id'];
                                $formControlType = isset($formStructure[$controlId]['control_type']) ? $formStructure[$controlId]['control_type'] : '';
                                $formControlTitle = isset($formStructure[$controlId]['control_title']) ? $formStructure[$controlId]['control_title'] : '';
                                if (isset($data['run_name_preview']) && $data['run_name_preview']) {
                                    $runName .= $formControlTitle;
                                } else {
                                    if (!empty($formControlType)) {
                                        // 排除复选框、签名图片、明细、附件、会签
                                        if (isset($formData[$controlId . '_TEXT']) && $formData[$controlId . '_TEXT'] !== '') {
                                            if (is_array($formData[$controlId . '_TEXT'])) {
                                                $formData[$controlId . '_TEXT'] = implode(',', $formData[$controlId . '_TEXT']);
                                            }
                                            $runName .= $formData[$controlId . '_TEXT'];
                                        } else {
                                            if (isset($formData[$controlId]) && $formData[$controlId] !== '') {
                                                if ($formControlType == 'text') {
                                                    $formControlAttribute = isset($formStructure[$controlId]['control_attribute']) && !empty($formStructure[$controlId]['control_attribute']) ? json_decode($formStructure[$controlId]['control_attribute'], true) : [];
                                                    if (isset($formControlAttribute['data-efb-amount-in-words']) && $formControlAttribute['data-efb-amount-in-words']) {
                                                        // 金额大写
                                                        $runName .= $this->digitUppercase($formData[$controlId]);
                                                    } elseif (isset($formControlAttribute['data-efb-thousand-separator']) && $formControlAttribute['data-efb-thousand-separator']) {
                                                        // 千位分隔符
                                                        $parts = explode('.', $formData[$controlId], 2);
                                                        $int = isset($parts[0]) ? strval($parts[0]) : '0';
                                                        $dec = isset($parts[1]) ? strval($parts[1]) : '';
                                                        $dec_len = strlen($dec) > 8 ? 8 : strlen($dec);
                                                        $runName .= number_format(floatval($formData[$controlId]), $dec_len, '.', ',');
                                                    } else {
                                                        $runName .= $formData[$controlId];
                                                    }
                                                } else {
                                                    if (is_array($formData[$controlId])) {
                                                        $formData[$controlId] = implode(',', $formData[$controlId]);
                                                    }
                                                    $runName .= $formData[$controlId];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                        case 'txt':
                            $runName .= isset($value['title']) ? $value['title'] : '';
                            break;
                        default:

                            break;
                    }
                }
            }
        }

        return $runName;
    }

    /**
     * 新建流程的时候，生成流水号
     *
     * @method flowNewPageGenerateFlowRunSeq
     *
     * @return [type]                        [description]
     */
    public function flowNewPageGenerateFlowRunSeq($data)
    {
        static $createRunSeqTryNumber = 0;
        $flowId = isset($data["flow_id"]) ? $data["flow_id"] : "";
        // 关联获取定义流程的所有数据
        $flowTypeAllObject = app($this->flowTypeRepository)->getFlowTypeInfoRepository($data, ['flow_sort']);
        if (isset($flowTypeAllObject)) {
            $flowTypeAllInfo = $flowTypeAllObject->toArray();
        } else {
            // 流程不存在
            return ['code' => ['0x030001', 'flow']];
        }
        if ($flowTypeAllInfo['flow_type'] == 1) {
            // 固定流程
            $flowTypeAllInfo['flow_type'] = trans("flow.0x030009");
        } else {
            // 自由流程
            $flowTypeAllInfo['flow_type'] = trans("flow.0x030010");
        }
        // 查flow_run里，当前flow_id的，最大run_id的流程信息
        $maxFlowRunObject = app($this->flowRunRepository)->getMaxRunIdFlowDataRepository($data);
        $createTime = date("Y-m-d H:i:s");
        $maxFlowRunData = null;
        if (isset($maxFlowRunObject)) {
            $maxFlowRunData = $maxFlowRunObject->toArray();
            // 解析流水号
            $maxFlowRunData["run_seq"] = strip_tags(html_entity_decode($maxFlowRunData["run_seq"]));
            preg_match_all("/(.*?)\[RUN_SEQ(\d+)\|(\d+)\]/", html_entity_decode($flowTypeAllInfo["flow_sequence"]), $flow_num);
            $prv_username = $maxFlowRunData["flow_run_has_one_user"] && $maxFlowRunData["flow_run_has_one_user"]["user_name"] ? $maxFlowRunData["flow_run_has_one_user"]["user_name"] : '';
            $prv_time = $maxFlowRunData["create_time"];
            $prv_seq = $maxFlowRunData["run_seq"];
            $prv_name = $flowTypeAllInfo["flow_name"];
            $prv_title = $flowTypeAllInfo["flow_type_belongs_to_flow_sort"] ? $flowTypeAllInfo["flow_type_belongs_to_flow_sort"]["title"] : trans('flow.unclassified');
            $prv_type = $flowTypeAllInfo["flow_type"];
            $creat_y = substr($prv_time, 0, 4);
            $creat_m = substr($prv_time, 5, 2);
            $creat_d = substr($prv_time, 8, 2);
            $creat_h = substr($prv_time, 11, 2);
            $creat_i = substr($prv_time, 14, 2);
            if (count($flow_num[0])) {
                $flow_num[1][0] = strip_tags($flow_num[1][0]);
                $seq = '';
                $runSeqAuto = str_replace("[FLOW_NAME]", "{$prv_name}", $flow_num[1][0]);
                $runSeqAuto = str_replace("[FLOW_CATEGORY]", "{$prv_title}", $runSeqAuto);
                $runSeqAuto = str_replace("[FLOW_TYPE]", "{$prv_type}", $runSeqAuto);
                $runSeqAuto = str_replace("[CREATOR]", "{$prv_username}", $runSeqAuto);
                $runSeqAuto = str_replace("[YEAR]", "{$creat_y}", $runSeqAuto);
                $runSeqAuto = str_replace("[MONTH]", "{$creat_m}", $runSeqAuto);
                $runSeqAuto = str_replace("[DATE]", "{$creat_d}", $runSeqAuto);
                $runSeqAuto = str_replace("[HOUR]", "{$creat_h}", $runSeqAuto);
                $runSeqAuto = str_replace("[MINUTE]", "{$creat_i}", $runSeqAuto);
                $first_len = strlen(strip_tags(html_entity_decode($runSeqAuto)));
                $seq = substr($maxFlowRunData["run_seq"], $first_len, $flow_num[2][0]);
                //从起始位置，开始算的话，计数器应该是当前值-起始值+1
                $flowCountSeq = intval($seq) - intval($flow_num[3][0]) + 1;
                if ($flowCountSeq < 0) {
                    $flowCountSeq = 0;
                }
            } else {
                $flowCountSeq = 0;
            }
        } else {
            $flowCountSeq = 0;
        }
        // 生成新流水号
        $creat_y = substr($createTime, 0, 4);
        $creat_m = substr($createTime, 5, 2);
        $creat_d = substr($createTime, 8, 2);
        $creat_h = substr($createTime, 11, 2);
        $creat_i = substr($createTime, 14, 2);
        $runSeqAuto = str_replace("[FLOW_NAME]", "{$flowTypeAllInfo['flow_name']}", html_entity_decode($flowTypeAllInfo["flow_sequence"]));
        $flowSort = $flowTypeAllInfo["flow_type_belongs_to_flow_sort"] ? $flowTypeAllInfo["flow_type_belongs_to_flow_sort"]['title'] : trans('flow.unclassified');
        $runSeqAuto = str_replace("[FLOW_CATEGORY]", "{$flowSort}", $runSeqAuto);
        $runSeqAuto = str_replace("[FLOW_TYPE]", "{$flowTypeAllInfo['flow_type']}", $runSeqAuto);
        $userName = app($this->userService)->getUserName($data["user_id"]);
        $runSeqAuto = str_replace("[CREATOR]", "{$userName}", $runSeqAuto);
        $runSeqAuto = str_replace("[YEAR]", "{$creat_y}", $runSeqAuto);
        $runSeqAuto = str_replace("[MONTH]", "{$creat_m}", $runSeqAuto);
        $runSeqAuto = str_replace("[DATE]", "{$creat_d}", $runSeqAuto);
        $runSeqAuto = str_replace("[HOUR]", "{$creat_h}", $runSeqAuto);
        $runSeqAuto = str_replace("[MINUTE]", "{$creat_i}", $runSeqAuto);
        $search = ["search" => ["flow_id" => [$flowId]]];
        preg_match_all("/\[RUN_SEQ(\d+)\|(\d+)(\|.)?]/", $runSeqAuto, $flow_num);
        if (count($flow_num[1])) {
            foreach ($flow_num[1] as $key => $val) {
                if ($flowCountSeq) {
                    $flow_now_num = $flowCountSeq + $flow_num[2][$key];
                } else {
                    $flow_now_num = $flow_num[2][$key];
                }
                $flow_now_num = $this->getFlowNowNum($flowTypeAllInfo, $maxFlowRunData, $flow_num, $key);
                $resetFlag = str_replace('|', '', $flow_num[3][$key]);
                if ($resetFlag == 'M') {
                    $search['search']['created_at'] = [[date('Y-m-01 00:00:00'), date('Y-m-31 23:59:59')], 'between'];
                }
                if ($resetFlag == 'Y') {
                    $search['search']['created_at'] = [[date('Y-01-01 00:00:00'), date('Y-12-31 23:59:59')], 'between'];
                }
                if ($resetFlag == 'D') {
                    $search['search']['created_at'] = [[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')], 'between'];
                }
                if (strlen($flow_now_num) <= $val) {
                    $num = "%0" . $val . "d";
                    $flow_num[1][$key] = sprintf($num, $flow_now_num);
                    $runSeqAuto = str_replace($flow_num[0][$key], $flow_num[1][$key], $runSeqAuto);
                } else {
                    // $flowTypeSequence = str_replace("[流水号".$flowTypeSequenceMatchResult[2][0]."|".$flowTypeSequenceMatchResult[3][0]."]", "[流水号".($flowTypeSequenceMatchResult[2][0]+1)."|"."0".$seq."]", html_entity_decode($flowTypeAllInfo["flow_sequence"]));
                    // update
                    // app($this->flowTypeRepository)->updateData(["flow_sequence" => $flowTypeSequence],["flow_id" => $flowTypeAllInfo["flow_id"]]);
                    // 流水号已经达到设置的上限，无法新建，请联系管理员修改设置或删除无用工作流！
                    return ['code' => ['0x030002', 'flow']];
                }
            }
            if (Cache::get('flow_base_info_change_' . $flowTypeAllInfo['flow_id'])) {
            	Cache::forget('flow_base_info_change_' . $flowTypeAllInfo['flow_id']);
            	// Cache::forget('flow_seq_num_' . $flowTypeAllInfo['flow_id']);
            }
        }
        $flowSequence = $flowTypeAllInfo['flow_sequence'];
        if (strpos($flowSequence,'|Y') !== false || strpos($flowSequence,'|M') !== false || strpos($flowSequence,'|D') !== false) {
            return $runSeqAuto;
        }
        // 非周期重置的需要验证是否重复
        if ($runSeqAuto) {
            $search['search']['run_seq_strip_tags'] = [strip_tags($runSeqAuto)];
            $runInfo = app($this->flowRunRepository)->getFlowRunList($search);
            if ($runInfo->count()) {
                // 有重复，尝试10次
                $createRunSeqTryNumber++;
                if ($createRunSeqTryNumber < 10) {
                    return $this->flowNewPageGenerateFlowRunSeq($data);
                } else {
                    // 流水号生成失败，请联系系统管理员修改设置！
                    return ['code' => ['0x030003', 'flow']];
                }
            } else {
                return $runSeqAuto;
            }
        } else {
            return $runSeqAuto;
        }
    }

    /*
     * 获取流程序列号
     */
    public function getFlowNowNum($flowTypeAllInfo, $maxFlowRunData, $flow_num, $key)
    {
        $start = intval($flow_num[2][$key]);
        $flowNum = $start;
        $resetFlag = str_replace('|', '', $flow_num[3][$key]);
        //不需要重置
        if (empty($resetFlag)) {
            if (empty($maxFlowRunData)) {
                $flowNum = $start;
            } else {
                $seqNum = $this->getSeqNum($flowTypeAllInfo, $maxFlowRunData);
                if (isset($seqNum[$key]) && $seqNum[$key] != false) {
                    $flowNum = intval($seqNum[$key]) + 1;
                    if (intval($seqNum[$key]) != $seqNum[$key]) {
                        $flowNum = $start;
                    }
                    if ($flowNum < $start) {
                        $flowNum = $start;
                    }
                    if (strlen($flowNum) >= 5 && (strlen($flowNum) != strlen($maxFlowRunData['run_seq_strip_tags']))) {
                        $flowNum = $start;
                    }
                }
            }
        } else {
            if (!empty($maxFlowRunData)) {
                $seqNum = $this->getSeqNum($flowTypeAllInfo, $maxFlowRunData);
                if (isset($seqNum[$key]) && $seqNum[$key] !== false) {
                	$flowNumInfo = [];
                    $flowNum = intval($seqNum[$key]) + 1;
                    if ($flowNumInfo = Cache::get('flow_seq_num_' . $flowTypeAllInfo['flow_id'])) {
                    	if (!is_array($flowNumInfo)) {
                    		$flowNumInfo = json_decode($flowNumInfo,true);
                    	}
                    	if (isset($flowNumInfo[$flow_num[0][$key]])) {
                    		$flowNum = intval($flowNumInfo[$flow_num[0][$key]]) + 1;
                    		$flowNumInfo[$flow_num[0][$key]] =  $flowNum;
                    		Cache::put('flow_seq_num_' . $flowTypeAllInfo['flow_id'], json_encode($flowNumInfo), 1800000);
                    	}
                    }
                    $createTime = date("Y-m-d H:i:s");
                    $recordTime = $maxFlowRunData['create_time'];
                    $record = "";
                    $today = "";
                    if ($resetFlag == "Y") {
                        $today = substr($createTime, 0, 4);
                        $record = substr($recordTime, 0, 4);
                    } else if ($resetFlag == "M") {
                        $today = substr($createTime, 5, 2);
                        $record = substr($recordTime, 5, 2);
                    } else if ($resetFlag == "D") {
                        $today = substr($createTime, 8, 2);
                        $record = substr($recordTime, 8, 2);
                    }
                    if ($record != $today) {
                        $flowNum = $start;
                        $flowNumInfo[$flow_num[0][$key]] =  $flowNum;
                        Cache::put('flow_seq_num_' . $flowTypeAllInfo['flow_id'], json_encode($flowNumInfo), 1800000);
                    }
                }
                if (Cache::get('flow_base_info_change_' . $flowTypeAllInfo['flow_id'])) {
                	$update = true;
                	$flowSeqRuleBefore = Cache::get('flow_seq_num_rule_before' . $flowTypeAllInfo['flow_id']);
                	$flowSeqRuleAfter = Cache::get('flow_seq_num_rule_after' . $flowTypeAllInfo['flow_id']);
                	preg_match_all("/\[RUN_SEQ(\d+)\|(\d+)(\|.)?]/", html_entity_decode($flowSeqRuleBefore), $flowBeforeNum);
                	if (count($flowBeforeNum[1])) {
                        $beforeRule = str_replace(['|Y','|M','|D'], '', $flowBeforeNum[0][$key]);
                        $afterRule = str_replace(['|Y','|M','|D'], '', $flow_num[0][$key]);
                		if (isset($flow_num[0][$key]) && isset($flowBeforeNum[0][$key]) && $beforeRule == $afterRule) {
                			$update = false;
                		}
                	}
					if ($update) {
						$flowNum = $start;
						$flowNumInfo[$flow_num[0][$key]] =  $flowNum;
						Cache::put('flow_seq_num_' . $flowTypeAllInfo['flow_id'], json_encode($flowNumInfo), 1800000);
					}
                }
            }
        }
        return $flowNum;
    }

    /**
     * 获取序列号数组
     */
    public function getSeqNum($flowTypeAllInfo, $maxFlowRunData)
    {
        $result = [];
        $len = 0;
        $maxRunSeq = strip_tags(html_entity_decode($maxFlowRunData["run_seq"]));
        preg_match_all("/(.*?)\[RUN_SEQ(\d+)\|(\d+)(\|.)?]/", html_entity_decode($flowTypeAllInfo['flow_sequence']), $flow_num);
        if (count($flow_num[0])) {
            foreach ($flow_num[1] as $key => $val) {
                $flow_num[1][$key] = strip_tags($flow_num[1][$key]);
                $prv_username = $maxFlowRunData["flow_run_has_one_user"] && $maxFlowRunData["flow_run_has_one_user"]["user_name"] ? $maxFlowRunData["flow_run_has_one_user"]["user_name"] : '';
                $prv_time = $maxFlowRunData["create_time"];
                $prv_seq = $maxFlowRunData["run_seq"];
                $prv_name = $flowTypeAllInfo["flow_name"];
                $prv_title = $flowTypeAllInfo["flow_type_belongs_to_flow_sort"] ? $flowTypeAllInfo["flow_type_belongs_to_flow_sort"]["title"] : trans('flow.unclassified');
                $prv_type = $flowTypeAllInfo["flow_type"];
                $creat_y = substr($prv_time, 0, 4);
                $creat_m = substr($prv_time, 5, 2);
                $creat_d = substr($prv_time, 8, 2);
                $creat_h = substr($prv_time, 11, 2);
                $creat_i = substr($prv_time, 14, 2);
                $flow_num[1][0] = strip_tags($flow_num[1][0]);
                $seq = '';
                $runSeqAuto = str_replace("[FLOW_NAME]", "{$prv_name}", $flow_num[1][$key]);
                $runSeqAuto = str_replace("[FLOW_CATEGORY]", "{$prv_title}", $runSeqAuto);
                $runSeqAuto = str_replace("[FLOW_TYPE]", "{$prv_type}", $runSeqAuto);
                $runSeqAuto = str_replace("[CREATOR]", "{$prv_username}", $runSeqAuto);
                $runSeqAuto = str_replace("[YEAR]", "{$creat_y}", $runSeqAuto);
                $runSeqAuto = str_replace("[MONTH]", "{$creat_m}", $runSeqAuto);
                $runSeqAuto = str_replace("[DATE]", "{$creat_d}", $runSeqAuto);
                $runSeqAuto = str_replace("[HOUR]", "{$creat_h}", $runSeqAuto);
                $runSeqAuto = str_replace("[MINUTE]", "{$creat_i}", $runSeqAuto);
                $first_len = strlen(strip_tags(html_entity_decode($runSeqAuto))) + $len;
                $seq = substr($maxRunSeq, $first_len, $flow_num[2][$key]);
                $seq = is_numeric($seq) ? $seq : '';
                if (!empty($flow_num[3][$key])) {
                    // 如果截取后是空字符串说明是新加的元素，用新元素的起始数加入到流水号，如果新加的元素的起始数大于历史数据，从新的起始数递增
                    if ($seq === '') {
                        $seq = intval($flow_num[3][$key]) - 1;;
                    } else {
                        if (intval($flow_num[3][$key] > $seq)) {
                            $seq = intval($flow_num[3][$key]) - 1;
                        }
                    }
                }
                $result[] = $seq;
                $len = ($first_len + intval($flow_num[2][$key]));
            }
        }
        return $result;
    }

    /**
     * 【流程签办反馈】，判断签办反馈是否被看过，被看过的不能编辑和删除
     *
     * @method checkFeedbackIsReadRepository
     *
     * @param  [type]                        $feedbackId [description]
     *
     * @return [type]                                    [description]
     */
    public function checkFeedbackIsRead($feedbackId, $userId)
    {
        // 获取签办反馈对象
        if ($feedbackDetailObject = app($this->flowRunFeedbackRepository)->getDetail($feedbackId)) {
            $feedbackDetailArray = $feedbackDetailObject->toArray();
            $checkResult = app($this->flowRunProcessRepository)->checkFeedbackIsReadRepository($feedbackDetailArray, $userId);
            if ($checkResult > 0) {
                return $checkResult;
            } else {
                return "";
            }
        }
    }

    /**
     * 获取签办反馈列表的实现函数
     * @param  [type] $data  [description]
     * @param  [type] $runId [description]
     * @return [type]        [description]
     */
    public function getFeedbackRealize($data, $runId, $userInfo = [])
    {
        $data = $this->parseParams($data);
        $data["run_id"] = $runId;
        $returnData = $this->response(app($this->flowRunFeedbackRepository), 'getFeedbackListTotal', 'getFlowFeedbackListRepository', $data);
        $list = $returnData["list"];
        $runObject = app($this->flowRunRepository)->getDetail($runId , false , ['max_process_id', "flow_id"]);
        $maxProcessId = isset($runObject->max_process_id) ? $runObject->max_process_id : "";
        $typeObject = app($this->flowTypeRepository)->getDetail($runObject->flow_id , false ,['flow_type']);
        $currentUserId = isset($userInfo["user_id"]) ? $userInfo["user_id"] : "";
        $flowRunOriginProcessArray = app($this->flowRunProcessRepository)->entity->where('run_id' ,$runId )->pluck('origin_process_id')->toArray();
        if ($returnData["total"]) {
            foreach ($list as $key => $value) {
                $canEditFeedBack = "";
                $feedbackUserId = isset($value["user_id"]) ? $value["user_id"] : "";
                $feedbackProcessId = isset($value["process_id"]) ? $value["process_id"] : "";
                $processName = isset($value["flow_run_feedback_has_one_node"]) ? $value["flow_run_feedback_has_one_node"] : "";
                // 判断是否已经有人查看过，参考9.0的逻辑
                if ($feedbackUserId == $currentUserId && !in_array( $feedbackProcessId, $flowRunOriginProcessArray)) {
                    $param = [
                        "run_id" => $runId,
                        "process_id" => $feedbackProcessId,
                        "edit_time" => isset($value["edit_time"]) ? $value["edit_time"] : "",
                        "user_id" => $currentUserId,
                    ];
                    $canEditFeedBack = $this->verifyFlowHasOtherPersonVisited($param);
                    if ($canEditFeedBack === false) {
                        $canEditFeedBack = "1";
                    } else {
                        $canEditFeedBack = "";
                    }
                }
                if (empty($processName) || $typeObject->flow_type == 2) {
                    $list[$key]['flow_run_feedback_has_one_node']['process_name'] = $feedbackProcessId;
                }
                if ($value['free_process_step']) {
                    $freeProcessInfo = $this->getFreeNodeStepInfo($value['run_id'], $value['flow_process'], $value['free_process_step']);
                    if ($freeProcessInfo) {
                        $list[$key]['flow_run_feedback_has_one_node']['process_name'] = $freeProcessInfo->process_name;
                    }
                }
                $list[$key]["canEditFeedBack"] = $canEditFeedBack;
                $list[$key]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'feedback', 'entity_id' => $value["feedback_id"]]);
            }
        }
        $returnData["list"] = $list;
        return $returnData;
    }

    /**
     * 【流程签办反馈】，获取当前流程，当前用户的下一个run_feedback_id
     *
     * @method getNextRunFeedbackId
     *
     * @param  [type]               $runId [description]
     *
     * @return [type]                      [description]
     */
    public function getNextRunFeedbackId($runId, $userId)
    {
        if ($maxRunFeedbackIdObject = app($this->flowRunFeedbackRepository)->getNextRunFeedbackIdRepository($runId, $userId)) {
            $maxRunFeedbackIdArray = $maxRunFeedbackIdObject->toArray();
            $maxRunFeedbackId = $maxRunFeedbackIdArray["cnt"];
            return $maxRunFeedbackId + 1;
        }
    }

    /**
     * 获取当前步骤，除了当前人员以外，其他的未办理人员
     *
     * @method getHaveNotTransactPerson
     *
     * @param  [type]                   $param [process_id,run_id]
     *
     * @return object                          返回的是对象!~
     */
    public function getHaveNotTransactPerson($param)
    {
        // 获取当前步骤，所有的未办理人员
        if ($haveNotTransactPersonObject = app($this->flowRunProcessRepository)->getHaveNotTransactPersonRepository($param)) {
            // 排除当前人员
            $filtered = $haveNotTransactPersonObject->reject(function ($item) use ($param) {
                if (isset($item->user_id) && $item->user_id == $param["user_id"]) {
                    return true;
                } else {
                    return false;
                }
            });
            return $filtered;
        }
    }

    /**
     * 判断流程出口条件
     *
     * @method flowRunExportCondition
     *
     * @param array $param [to_process_id:目标节点id]
     *
     * @return [type]                        [description]
     */
    public function flowRunExportCondition($param = [])
    {
        return true;
    }

    /**
     * 获取某个节点的指定范围办理人或者智能获取办理人，9.0函数：autoGetProcessor
     *
     * @method autoGetProcessUser
     *
     * @param array $param [description]
     * @param array $targetNodeInfo [目标节点的对象]
     *
     * @return [type]                    [description]
     */
    public function autoGetProcessUser($param, $targetNodeInfo)
    {
        if (!isset($param['run_id'])) {
            $param['run_id'] = '';
        }
        if (!isset($param['process_id'])) {
            $param['process_id'] = '';
        }
        if (!isset($param['flow_id'])) {
            $param['flow_id'] = '';
        }
        // 智能获取办理人规则
        $processAutoGetUser = $targetNodeInfo->process_auto_get_user;
        if ($processAutoGetUser) {
            $data = [
                'run_id' => $param['run_id'],
                'node_id' => $param['process_id'],
                'flow_id' => $param['flow_id'],
            ];
            $userInfo = $this->getAutoGetUser($data);
            return $userInfo;
        } else {
            if ($targetNodeInfo->process_user == "ALL" || $targetNodeInfo->process_role == "ALL" || $targetNodeInfo->process_dept == "ALL") {
                return "ALL";
            } else {
                // 获取符合范围的人员
                $getUserParam = [
                    "fields" => ["user_id", "user_name"],
                    "page" => "0",
                    "returntype" => "object",
                ];
                $processUserId = $targetNodeInfo->flowProcessHasManyUser->pluck("user_id");
                $processRoleId = $targetNodeInfo->flowProcessHasManyRole->pluck("role_id");
                $processDeptId = $targetNodeInfo->flowProcessHasManyDept->pluck("dept_id");
                $getUserParam["search"] = [
                    "user_id" => $processUserId,
                    "role_id" => $processRoleId,
                    "dept_id" => $processDeptId,
                ];
                $userInfo = app($this->userRepository)->getConformScopeUserList($getUserParam);
                return $userInfo;
            }
        }
    }
    /**
     * 获取某个节点的指定范围抄送人或者智能获取抄送人
     *
     * @method autoGetCopyUser
     *
     * @param array $param [description]
     * @param array $currentNodeInfo [当前节点的对象]
     *
     * @return [type]                    [description]
     */
    public function autoGetCopyUser($param, $currentNodeInfo)
    {
        if (!isset($param['run_id'])) {
            $param['run_id'] = '';
        }
        if (!isset($param['node_id'])) {
            $param['node_id'] = '';
        }
        if (!isset($param['flow_id'])) {
            $param['flow_id'] = '';
        }
        // 智能获取抄送人规则
        $processAutoGetCopyUser = $currentNodeInfo->process_auto_get_copy_user;
        if ($processAutoGetCopyUser) {
            $data = [
                'run_id' => $param['run_id'],
                'node_id' => $param['node_id'],
                'flow_id' => $param['flow_id'],
            ];
            $userInfo = $this->getAutoGetUser($data, 'copy');
            return $userInfo;
        } else {
            if ($currentNodeInfo->process_copy_user == "ALL" || $currentNodeInfo->process_copy_role == "ALL" || $currentNodeInfo->process_copy_dept == "ALL") {
                return "ALL";
            } else {
                // 获取符合范围的人员
                $getUserParam = [
                    "fields" => ["user_id", "user_name"],
                    "page" => "0",
                    "returntype" => "object",
                ];
                $processCopyUserId = $currentNodeInfo->flowProcessHasManyCopyUser->pluck("user_id");
                $processCopyRoleId = $currentNodeInfo->flowProcessHasManyCopyRole->pluck("role_id");
                $processCopyDeptId = $currentNodeInfo->flowProcessHasManyCopyDept->pluck("dept_id");
                $getUserParam["search"] = [
                    "user_id" => $processCopyUserId,
                    "role_id" => $processCopyRoleId,
                    "dept_id" => $processCopyDeptId,
                ];
                $userInfo = app($this->userRepository)->getConformScopeUserList($getUserParam);
                return $userInfo;
            }
        }
    }

    /**
     * 将流程办理人员字符串获取在职人员数组后，格式化，拼接主办人
     *
     * @method formatFixedFlowTransactUser
     *
     * @param  [type]                      $transactUserString [description]
     * @param  [type]                      $hostFlagUser       [description]
     *
     * @return [type]                                          [description]
     */
    public function formatFixedFlowTransactUser($transactUserString, $hostFlagUser)
    {
        $defaultUsersInfo = app($this->userService)->getInserviceUser(["user_id" => $transactUserString]);
        $handleUserId = $defaultUsersInfo->pluck("user_id");
        // $handleUserName = $defaultUsersInfo->pluck("user_name");
        $hostUserId = "";
        // $hostUserName = "";
        if ($hostFlagUser) {
            $hostUserId = $hostFlagUser;
           // $hostUserName = app($this->userService)->getUserName($hostUserId);
        }
        $userInThisFlownode = [];
        $userInThisFlownode["scope"]["user_id"] = $handleUserId;
        // $userInThisFlownode["scope"]["user_name"] = $handleUserName;
        $userInThisFlownode["default"]["handle"]["user_id"] = $handleUserId;
        // $userInThisFlownode["default"]["handle"]["user_name"] = $handleUserName;
        $userInThisFlownode["default"]["host"]["user_id"] = [$hostUserId];
        // $userInThisFlownode["default"]["host"]["user_name"] = [$hostUserName];
                // 获取离职人员
        $transactUserArray = $transactUserString;
        if (is_string($transactUserString)) {
                $transactUserArray = explode(',', trim($transactUserString, ','));
        }
        // 获取离职的办理人员
        $userInThisFlownode["leave"] = array_diff( $transactUserArray  , $handleUserId->toArray());
        // 判断默认主办人是否离职
        $userInThisFlownode["host_leave"] = false;
        if (  !empty($userInThisFlownode['leave'])  && in_array($hostUserId, $userInThisFlownode['leave'])) {
                $userInThisFlownode["host_leave"] = true;
        }
        return $userInThisFlownode;
    }

    /**
     * 判断是否已经存在主办人
     * @param array $param
     * @return int
     */
    public function opFlagIsExist($param = [])
    {
        $runId = $param["run_id"];
        $processId = $param["process_id"];
        $flowRunCurrentData = [
            "run_id" => $runId,
            "search" => ["process_id" => [$processId]],
            "order_by" => ["host_flag" => "DESC"],
            'select_user' => false,
            'fields' =>['host_flag']
        ];
        if ($flowRunCurrentProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunCurrentData)) {
            $count = $flowRunCurrentProcessObject->count();
            if ($count) {
                return $flowRunCurrentProcessObject->first()->host_flag;
            } else {
                return 0;
            }
        }
    }

    /**
     * 判断是主办人提交还是经办人提交，前端的函数挪到这里。
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function decideHostOrHandleSubmit($param)
    {
        $handleType = "";
        // 先判断当前人员是否在最新步骤
        if ($param["processId"] == $param["maxProcessId"]) {
            // 当前人员是最新步骤，正常流转
            // 1.判断主办人还是经办人
            if ($param["hostFlag"] == "1") {
                // 主办人提交
                $handleType = "host";
            } else {
                // 2.如果hostFlag判断不是主办人，且是第四种办理方式，需要判断
                if ($param["handleWay"] == "3") {
                    // 2.1.计算当前流程是否有主办人
                    // 获取某条流程当前步骤是否有主办人，返回1，有主办人，返回0，没有主办人。
                    $opFlagIsExistResult = $this->opFlagIsExist(["run_id" => $param["runId"], "process_id" => $param["processId"]]);
                    if ($opFlagIsExistResult == "1") {
                        // 有主办人，此人经办人提交
                        $handleType = "handle";
                    } else {
                        // 没有主办人，此人主办人提交
                        $handleType = "host";
                    }
                } else {
                    // 经办人提交
                    $handleType = "handle";
                }
            }
        } else {
            // 经办人提交
            $handleType = "handle";
        }
        return $handleType;
    }

    /**
     * 设置主办人，这里传入不同参数，实现：“动态设置主办人”，“指定主办人”两个不同的功能
     * 20160930，此函数，在Flowserve里内部调用了，外部的那个路由里【动态设置主办人】功能部分，可以不要了！
     *
     * @method setOpFlag
     *
     * @param array $param [description]
     */
    public function setHostFlag($param)
    {
        // 传user_id，实现“动态设置主办人”
        $userId = isset($param["user_id"]) ? $param["user_id"] : false;
        // 传host_user，实现“指定主办人”
        $hostUser = isset($param["host_user"]) ? $param["host_user"] : false;
        if ($userId !== false) {
            return lock(function () use ($param) {
                return $this->setDynamicHostFlag($param);
            }, ['code' => ['0x030178', 'flow']]);
        } else if ($hostUser !== false) {
            return $this->manageAssignHostUser($param);
        } else {
            return "";
        }
    }

    /**
     * 动态设置主办人
     *
     * @method setDynamicHostFlag
     *
     * @param array $param [description]
     */
    public function setDynamicHostFlag($param = [])
    {

        $runId = $param["run_id"];
        $handleWay = $param["handle_way"];
        $processId = $param["process_id"];
        $flowProcess = $param["flow_process"];
        $userId = $param["user_id"];
        $fromType = "";
        if (isset($param["from_type"])) {
            $fromType = $param["from_type"];
        }
        $maxHostFlag = $this->opFlagIsExist(["process_id" => $processId, "run_id" => $runId,"concurrent_process"=>$flowProcess]);
        if ($maxHostFlag > 0) {
            return "";
        }
        switch ($handleWay) {
                //第一个接收设置为主办人
            case 1:
                //判断是否已经确定主办人了,如果是则不去修改主办人,原因是监控提交须确定主办人,可能出现监控设置了主办人,等经办人回来查看时再被设为主办人
                $flowRunProcessData = [
                    "run_id" => $runId,
                    "search" => ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess], "user_id" => [$userId, "!="]],
                    "whereRaw" => ["process_time IS NOT NULL"],
                ];
                if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessData)) {
                    if ($flowRunProcessObject->count() == 0) {
                        app($this->flowRunProcessRepository)->updateData(["host_flag" => "1"], ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess], "user_id" => [$userId]]);
                        return "1";
                    } else {
                        // 如果当前还没确定主办人，且已经有其他人查看了，设置最先查看的那个人为主办人
                        $flowRunProcessData = [
                            "search" => ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess]],
                            "whereRaw" => ["process_time IS NOT NULL"],
                            'order_by'   => ['process_time'=>'asc'],
                            'returntype' => 'first',
                        ];
                        $flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessData);
                        if (empty($flowRunProcessObject->flow_run_process_id)) {
                            return "";
                        }
                        app($this->flowRunProcessRepository)->updateData(["host_flag" => "1"], ["flow_run_process_id" => [$flowRunProcessObject->flow_run_process_id]]);
                        if($flowRunProcessObject->user_id == $userId) {
                            return "1";
                        }
                    }
                }
                return "";
                break;
                //最后一个接收设置为主办人
            case 2:
                $flowRunProcessData = [
                    "run_id" => $runId,
                    "search" => ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess]],
                    "whereRaw" => ["process_time IS NULL"],
                    "selectRaw" => ["COUNT(DISTINCT(USER_ID)) AS cnt"],
                ];
                $haveOpTag = '';
                $haveOpTagSelf = '';
                if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessData)) {
                    $haveOpTag = $flowRunProcessObject->first()->cnt;
                }
                // 所有人都查看了，设置最后看的人为主办人
                if ($haveOpTag == 0) {
                    $flowRunProcessData = [
                        "run_id" => $runId,
                        "search" => ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess]],
                        'order_by'   => ['process_time'=>'desc'],
                        'returntype' => 'first',
                    ];
                    $flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessData);
                    app($this->flowRunProcessRepository)->updateData(["host_flag" => "1"], ["flow_run_process_id" => [$flowRunProcessObject->flow_run_process_id]]);
                    if($flowRunProcessObject->user_id == $userId) {
                        return "1";
                    }
                }

                $flowRunProcessTagData = [
                    "run_id" => $runId,
                    "search" => ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess], "user_id" => [$userId]],
                    "whereRaw" => ["process_time IS NULL"],
                    "selectRaw" => ["COUNT(*) AS cnt"],
                ];
                if ($flowRunProcessTagObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessTagData)) {
                    $haveOpTagSelf = $flowRunProcessTagObject->first()->cnt;
                }
                if ($haveOpTag == 1 && $haveOpTagSelf > 0) {
                    app($this->flowRunProcessRepository)->updateData(["host_flag" => "1"], ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess], "user_id" => [$userId]]);
                    return "1";
                }
                return "";
                break;
                //所有办理人员都可以转到下一步
            case 3:
                //没有主办人，当前人员就是主办人，或者只有一个办理人当前人员也是主办人
                if ($fromType != "print") {
                    app($this->flowRunProcessRepository)->updateData(["host_flag" => "1"], ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess], "user_id" => [$userId]]);
                    return "1";
                }
                return "";
                break;
        }
    }
    /**
     * 指定主办人
     *
     * @method manageAssignHostUser
     *
     * @param  [type]               $param [description]
     *
     * @return [type]                      [description]
     */
    public function manageAssignHostUser($param)
    {
        $runId = $param["run_id"];
        $processId = $param["process_id"];
        if (is_array($param["host_user"]) && isset($param["host_user"][0])) {
            $param["host_user"] = $param["host_user"][0];
        }
        $hostUser = $param["host_user"];
        app($this->flowRunProcessRepository)->updateData(["host_flag" => "1"], ["process_id" => [$processId], "run_id" => [$runId], "user_id" => [$hostUser]]);
        app($this->flowRunStepRepository)->updateData(["host_flag" => "1"], ["run_id" => [$runId], "user_id" => [$hostUser[0]], "process_id" => [$processId]]);
        // 如果该节点时合并节点，同时更新合并节点的主办人身份
        $toDoArray = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'process_id' => [$processId], "user_id" => [$hostUser] ,'user_run_type' => [1]], 'returntype' => 'array']);
        if (count($toDoArray)) {
            $flowProcess = app($this->flowProcessRepository)->getDetail($toDoArray[0]['flow_process'] , false , ['merge']);
            if ( isset($flowProcess->merge) && $flowProcess->merge > 0 ) {
                    app($this->flowRunProcessRepository)->updateData(['host_flag' => 1], ['run_id' => [$runId], 'user_id' => [$toDoArray[0]['user_id']] ,'flow_process' => [$toDoArray[0]['flow_process']] , 'flow_serial' => [$toDoArray[0]['flow_serial']], 'user_run_type' => [1]]);
            }
        }

        return "1";
    }

    /**
     * 判断流程某个步骤主办人是否已经提交
     *
     * @method haveTurned
     *
     * @param array $param [description]
     *
     * @return [type]            [description]
     */
    public function haveTurned($param = [])
    {
        $runId = $param["run_id"];
        $processId = $param["process_id"];
        $handleWay = $param['handle_way'];
        $userId = $param["user_id"];
        $opFlagParam = ["process_id" => $processId, "run_id" => $runId];
        if(!empty($param['concurrent_process'])){
        	$opFlagParam['concurrent_process'] = $param['concurrent_process'];
        }
        $maxHostFlag = $this->opFlagIsExist($opFlagParam);
        if ($maxHostFlag == 0) {
            return false;
        } else {
            if ($handleWay == 3) {
                return false;
            }
        	$search = ["process_id" => [$processId], "run_id" => [$runId], "user_id" => [$userId], "host_flag" => ['1']];
        	if(!empty($param['concurrent_process'])){
        		$search['flow_process'] = [$param['concurrent_process']];
        	}
            $flowRunProcessData = [
                "run_id" => $runId,
                "search" => $search,
                "whereRaw" => [" ( DELIVER_TIME IS NULL OR DELIVER_TIME = '0000-00-00 00:00:00') "],
                "selectRaw" => ["COUNT(RUN_ID) AS CNT"],
                "select_user"=>false
            ];
            if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessData)) {
                if ($flowRunProcessObject->first()->CNT > 0) { // 没有提交
                    return false;
                } else {
                    return true; // 已提交
                }
            }
        }
    }

    /**
     * 监控人提交，验证的应该是这个步骤实际办理人是否提交
     *
     * @method checkMonitorHaveTurn
     *
     * @param array $param [description]
     *
     * @return [type]                      [description]
     */
    public function checkMonitorHaveTurn($param = [])
    {
        // 监控人在合并节点退回时会提示流程已提交或委托,循环节点时同一节点判断第一次
        if (isset($param['first_has_executed']) && $param['first_has_executed'] ==1  ) {
            return false;
        }
        // user_id是必填的。
        $processInfo = $param['process_info'] ?? [];
        $processTransactType = $processInfo['process_transact_type'] ?? 0;
        $userId = $param["user_id"];
        if (isset($param["monitor"])) {
            $flowRunProcessData = [
                "search" => ["process_id" => [$param["process_id"]], "run_id" => [$param["run_id"]], "host_flag" => ['1']],
            ];
            if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessData)) {
                if ($flowRunProcessObject->first()) {
                    $hostFlagUser = $flowRunProcessObject->first()->user_id;
                    $turnParam = [
                        "process_id" => [$param["process_id"]],
                        "run_id" => [$param["run_id"]],
                        "user_id" => [$hostFlagUser],
                        'handle_way' => $processTransactType
                       ];
                    if ($this->haveTurned($turnParam)) {
                        // 流程已提交或委托
                        return ['code' => ['0x030005', 'flow']];
                    }
                }
            }
        } else {
        	$turnParam = ["process_id" => [$param["process_id"]], "run_id" => [$param["run_id"]], "user_id" => [$userId], 'handle_way' => $processTransactType];
            if ($this->haveTurned($turnParam)) {
                // 流程已提交或委托
                return ['code' => ['0x030005', 'flow']];
            }
        }
    }

    /**
     * 判断每个办理人是否有委托流程
     * 9.0函数 flowHaveAgent
     *
     * @method checkFlowHaveAgent
     *
     * @param array $param [description]
     *
     * @param $firstLevel         获取一级委托
     *
     * @return [type]                    [description]
     */
    public function checkFlowHaveAgent($param = [], $firstLevel = false)
    {
        if (isset($param['flow_id']) && isset($param['user_id'])) {
            $getAgencyChainParam = [
                "flow_id" => $param['flow_id'],
                "user_id" => $param['user_id'],
            ];
            $agencyChain = $this->getFlowAgencyChain($getAgencyChainParam);
            if (count($agencyChain) > 0) {
                $index = count($agencyChain) - 1;
                if ($firstLevel) {
                    $index = 0;
                }
                return $agencyChain[$index]['flow_agency_id'] . '|' . $agencyChain[$index]['agency_user_id'];
            }
        }
        return false;
    }

    /**
     * 获取下一级委托信息
     * @param array $param
     * @return bool|string
     */
    public function getNextAgent($param = [])
    {
        if ($checkAgencyObject = app($this->flowAgencyRepository)->checkFlowHaveAgentRepository($param)) {
            if ($checkAgencyObject->first()) {
                $agentUserId = $checkAgencyObject->first()->agent_id;
                $agentInfo = $checkAgencyObject->first()->flow_agency_id . "|" . $agentUserId;
                return $agentInfo;
            }
        }
        return false;
    }

    /**
     * 获取委托代理链
     * @param array $param
     * @return array
     */
    public function getFlowAgencyChain($param = [])
    {
        $agencyChain = [];
        if ($agentInfo = $this->getNextAgent($param)) {
            // 遍历获取到最终的委托人
            while (true) {
                $agentInfoArray = explode("|", $agentInfo);
                $agency = [
                    'flow_agency_id' => $agentInfoArray[0],
                    'agency_user_id' => $agentInfoArray[1],
                ];
                // 委托人和代理人相同,不再向下查找
                $agencyEffective = true;
                if ($param['user_id'] == $agency['agency_user_id']) {
                    $agencyEffective = false;
                } else if (count($agencyChain) > 0) {
                    // 代理链已有该代理人,不再向下查找
                    foreach ($agencyChain as $agencyChainValue) {
                        if ($agencyChainValue['agency_user_id'] == $agency['agency_user_id']) {
                            $agencyEffective = false;
                        }
                    }
                }
                // 代理人有效
                if ($agencyEffective) {
                    $agencyChain[] = $agency;
                    $nextParam = ["flow_id" => $param['flow_id'], "user_id" => $agentInfoArray[1]];
                    // 获取下一级代理
                    if ($nextAgentInfo = $this->getNextAgent($nextParam)) {
                        $agentInfo = $nextAgentInfo;
                        //有委托继续找最终委托人
                        continue;
                    }
                }
                //没有继续循环 就跳出
                break;
            }
        }
        return $agencyChain;
    }

    /**
     * 记录流程委托链
     * @param $flowRunProcess
     * @param $agentParam
     */
    public function recordFlowAgencyChain($flowRunProcess, $agentParam)
    {
        $agencyChain = $this->getFlowAgencyChain($agentParam);
        $byAgencyId = $agentParam['user_id'] ?? null;
        // 有一个以上是连续委托
        if (count($agencyChain) > 0 && $byAgencyId) {
            foreach ($agencyChain as $key => $agency) {
                $data = [
                    'flow_run_process_id' => $flowRunProcess['flow_run_process_id'],
                    'flow_agency_id' => $agency['flow_agency_id'],
                    'user_id' => $agency['agency_user_id'],
                    'by_agency_id' => $byAgencyId,
                    'sort' => $key,
                ];
                app($this->flowRunProcessAgencyDetailRepository)->insertData($data);
                // 上一个被委托人是下一个委托人
                $byAgencyId = $agency['agency_user_id'];
            }
        }
    }

    /**
     * 自由流程提交下一步
     *
     * @method freeFlowRunTurnNext
     *
     * @param array $param [description]
     *
     * @return [type]                     [description]
     */
    public function freeFlowRunTurnNext($param = [])
    {
        // user_id，必填
        $userId = $param["user_id"];
        // 如果是监控人提交，验证一下
        if ($checkMonitorHaveTurnReturn = $this->checkMonitorHaveTurn($param)) {
            return $checkMonitorHaveTurnReturn;
        }
        $processId = $param["process_id"];
        $processIdNext = $processId + 1;
        $runId = $param["run_id"];
        $monitor = isset($param['monitor']) ? $param['monitor'] : '';
        // 主办人
        if (isset($param['process_host_user'])) {
            if (getType($param['process_host_user']) == "array") {
                $process_host_user = implode(",", $param['process_host_user']);
            } else if (getType($param['process_host_user']) == "string") {
                $process_host_user = $param['process_host_user'];
            }
        } else {
            $process_host_user = "";
        }
        $limitDate = isset($param['limit_date']) ? $param['limit_date'] : '';
        $currentTime = date("Y-m-d H:i:s", time());
        $runObject = $param['flow_run_info'] ?? app($this->flowRunRepository)->getDetail($runId);
        $flowId = $runObject->flow_id;

        // 20190919,zyx,如果是第一步提交，则新增一个字段更新->first_transact_time，作为流程开始时间使用
        $flowRunData = [
            "current_step" => $processIdNext,
            "transact_time" => $currentTime,
            "max_process_id" => $processIdNext
        ];
        if ($processId == 1) {
            $flowRunData['first_transact_time'] = $currentTime;
        }
        app($this->flowRunRepository)->updateData($flowRunData, ["run_id" => $runId]);
        // 20190919,zyx
        //        app($this->flowRunRepository)->updateData(["current_step" => $processIdNext, "transact_time" => $currentTime, "max_process_id" => $processIdNext], ["run_id" => $runId]);
        //更新流程的状态
        // $query = "UPDATE zzzz_flow_data_$this->formId SET CURRENT_STEP = '$processIdNext',TRANSACT_TIME = '".$currentTime."' WHERE RUN_ID = '".$runId."'";
        app($this->flowRunProcessRepository)->updateData(["process_flag" => '3', "deliver_time" => $currentTime], ["run_id" => [$runId], "process_id" => [$processId]]);
        if (!empty($monitor)) {
            // 监控提交或退回更新主办人和未办理的经办人的monitor_submit，流程步骤中显示为监控提交，其他自己已办理的显示正常状态
            app($this->flowRunProcessRepository)->updateData(["monitor_submit" => $monitor, 'user_run_type' => '2'], ["run_id" => [$runId], "process_id" => [$processId], "host_flag" => '1']);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["monitor_submit" => $monitor, "saveform_time" => $currentTime, "transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId], "host_flag" => '0'], "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '2'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            if ($processId > '1') {
                // 更新上一步的process_flag
                app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_flag" => "4"], "wheres" => ["run_id" => [$runId], "process_id" => [$processId - 1]]]);
            }
        } else {
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["saveform_time" => $currentTime, "transact_time" => $currentTime, "user_run_type" => 2], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "host_flag" => [0]], "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateData(["user_run_type" => '2',"transact_time" => $currentTime], ["run_id" => [$runId], "user_id" => [$userId], "process_id" => [$processId]]);
            //更新flow_run_step
            app($this->flowRunStepRepository)->updateData(["user_run_type" => '2'], ["run_id" => [$runId], "user_id" => [$userId], "process_id" => [$processId]]);
        }
        $lastHostInfo = app($this->flowRunProcessRepository)->getHostInfoByRunIdAndProcessId($runId, $processId);
        if (!empty($lastHostInfo)) {
            $todu_push_params = [];
            $todu_push_params['receiveUser'] = $lastHostInfo->user_id;
            $todu_push_params['deliverTime'] = $currentTime;
            $todu_push_params['deliverUser'] = $userId;
            $todu_push_params['operationType'] = 'reduce';
            $todu_push_params['operationId'] = '1';
            $todu_push_params['flowId'] = $flowId;
            $todu_push_params['runId'] = $runId;
            $todu_push_params['processId'] = $processId;
            $todu_push_params['flowRunProcessId'] = $lastHostInfo->flow_run_process_id;
            // 操作推送至集成中心
            app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
        }
        // 拆分办理人员
        $transactUserArray = explode(",", trim(trim($param["process_transact_user"], ",") . "," . trim($process_host_user, ","), ","));
        $transactUserArray = array_unique($transactUserArray);
        // 收集提醒被发送人
        $remindUserIdString = "";
        $haveAgentUsers  = app($this->flowAgencyRepository)->checkUsersFlowHaveAgentRepository(["flow_id" => $flowId, "user_id" => $transactUserArray]);
        $haveAgentUsers = array_column( $haveAgentUsers->toArray() , 'by_agent_id');
        $insertFlowRunProcessDataNoAgent = [];
        $joinUserIds = [];
        // 记录需要发送消息的用户ID和flow_run_process_id
        $transactUserIdAndFlowRunProcessId = [];
        foreach ($transactUserArray as $key => $currentTransactUserId) {
            $host_flag = $currentTransactUserId == $process_host_user ? 1 : 0;
            $todu_push_params = [];
            if (in_array($currentTransactUserId,  $haveAgentUsers)) {
                // 判断每个办理人是否有委托流程
                $agentParam = ["flow_id" => $flowId, "user_id" => $currentTransactUserId];
                if ($haveAgent = $this->checkFlowHaveAgent($agentParam)) {
                    $haveAgentArray = explode("|", $haveAgent);
                    $agentFlowId = $haveAgentArray[0];
                    $agentUserId = $haveAgentArray[1];
                    $insertFlowRunProcessData = [
                        "run_id" => $runId,
                        "process_id" => $processIdNext,
                        "receive_time" => $currentTime,
                        "user_id" => $agentUserId,
                        "process_flag" => "1",
                        "flow_process" => $processIdNext,
                    	"flow_serial" => $processIdNext,
                        "host_flag" => $host_flag,
                        "limit_date" => $limitDate,
                        "by_agent_id" => $currentTransactUserId,
                        "flow_agency_id" => $agentFlowId,
                        "flow_id" => $flowId,
                  		"origin_process" => $processId,
                  		"origin_user" => $userId,
                        "origin_process_id" => $processId,
                    	"user_last_step_flag" => 1,
                        "user_run_type" => 1
                    ];
                    $this->updateLastFlagAtSubmit(['run_id' => $runId, 'user_id' => $agentUserId, 'branch_serial' => 0, 'flow_serial' => $processIdNext, 'host_flag' => $host_flag, "flow_process" => $processIdNext]);
                    $flowRunProcess = app($this->flowRunProcessRepository)->insertData($insertFlowRunProcessData)->toArray();
                    if ($host_flag == '1') {
                        $flowRunProcessId = $flowRunProcess['flow_run_process_id'] ?? 0;
                    }
                    //记录流程委托链
                    $this->recordFlowAgencyChain($flowRunProcess, $agentParam);
                    //流程的短信提醒也转发到代理人那里
                    $currentTransactUserId = $agentUserId;
                }
            }else {
                $insertFlowRunProcessDataNoAgent = [
                    "run_id" => $runId,
                    "process_id" => $processIdNext,
                    "receive_time" => $currentTime,
                    "user_id" => $currentTransactUserId,
                    "process_flag" => "1",
                    "flow_process" => $processIdNext,
                	"flow_serial" => $processIdNext,
                    "host_flag" => $host_flag,
                    "limit_date" => $limitDate,
                    "flow_id" => $flowId,
    				"origin_process" => $processId,
    				"origin_user" => $userId,
                    "origin_process_id" => $processId,
                	"user_last_step_flag" => 1,
                    "user_run_type" => 1
                ];
                $this->updateLastFlagAtSubmit(['run_id' => $runId, 'user_id' => $currentTransactUserId, 'branch_serial' => 0, 'flow_serial' => $processIdNext, 'host_flag' => $host_flag, "flow_process" => $processIdNext]);
                $flowRunProcess = app($this->flowRunProcessRepository)->insertData($insertFlowRunProcessDataNoAgent)->toArray();
                if ($host_flag == '1') {
                    $flowRunProcessId = $flowRunProcess['flow_run_process_id'] ?? 0;
                }
            }
            $transactUserIdAndFlowRunProcessId[] = [
                'user_id'             => $currentTransactUserId,
                'flow_run_process_id' => $flowRunProcess['flow_run_process_id'] ?? 0,
                'host_flag'           => $host_flag
            ];
            $remindUserIdString .= $currentTransactUserId . ",";
            $joinUserIds[] = $currentTransactUserId;
        }
        app($this->flowRunProcessRepository)->updateData(["outflow_process" =>$processIdNext,"outflow_user" =>$process_host_user],["run_id" =>[$runId],"flow_process"=>[$processId],"process_id" => [$processId],"host_flag" => '1']);
        // app($this->flowRunStepRepository)->updateData(["transact_time" => time()],["run_id" => [$runId]]);
        // $this->rebuildFlowRunStepDataServiceRealize(["run_id" => $runId]);
        // 重新计算每个人的user_last_setp_flag
        app($this->flowParseService)->updateUserLastStepsFlag($runId, $joinUserIds);
        // 发送提醒
        // if (!empty($limitDate)) {
        //     // 催办提醒
        //     $sendData['remindMark'] = 'flow-urge';
        // } else {
        // 流程提交提醒
        $sendData['remindMark'] = 'flow-submit';
        // }
        //$userName = app($this->userService)->getUserName($userId);
        $userName = (isset($param['user_name']) && !empty($param['user_name'])) ? $param['user_name']: app($this->userService)->getUserName($userId);
        // $sendData['toUser'] = $remindUserIdString;
        $currentStepName = $processIdNext;
        $sendData['contentParam'] = ['flowTitle' => $runObject->run_name, 'userName' => $userName, 'currentStep' => $currentStepName];
        $sendData['stateParams'] = ["flow_id" => intval($flowId), "run_id" => intval($runId)];
        if (!empty($flowRunProcessId)) {
            $sendData['stateParams']['flow_run_process_id'] = intval($flowRunProcessId);
        }
        // 20180625-v2-继续使用系统提醒，但是传入sendMethod，实现提醒类型可选
        if (isset($param["sendMethod"]) && isset($param["flowSubmitHandRemindToggle"]) && $param["flowSubmitHandRemindToggle"] == "1") {
            $sendData['sendMethod'] = $this->flowRunRemindMethodFilter($param["sendMethod"]);
        }
        $sendData['module_type'] = app($this->flowTypeRepository)->getFlowSortByFlowId($flowId);
        $this->flowTurningSendMessage($transactUserIdAndFlowRunProcessId, $sendData);
        if (!empty($monitor)) {
            // 监控提醒，提醒上一节点主办人
            $lastHostInfo = app($this->flowRunProcessRepository)->getHostUserIdByRunIdAndProcessId($runId, $processId);
            if (!empty($lastHostInfo)) {
                $lastHandleUser = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'process_id' => [$processId]]])->pluck('user_id')->toArray();
                app($this->flowParseService)->markUnreadMessagesAsRead($flowId, $runId, $lastHandleUser);
                $sendData['remindMark'] = 'flow-monitor';
                $sendData['toUser'] = $lastHostInfo;
                unset($sendData['sendMethod']);
                Eoffice::sendMessage($sendData);
            }
        }
        return "1";
    }

    /**
     * 固定流程提交下一步
     *
     * @method freeFlowRunTurnNext
     *
     * @param array $param [description]
     *
     * @return [type]                     [description]
     */
    public function fixedFlowRunTurnNext($param = [], $submitUuid = '')
    {
        // user_id，必填
        $userId = $param["user_id"];
        $processId = $param["process_id"];
        $runId = $param["run_id"];
        $monitor = isset($param['monitor']) ? $param['monitor'] : '';
        $branchSerial = $param['branch_serial'];
        $flowSerial = $param['flow_serial'];
        $flowTurnType = $param['flowTurnType'] ?? '';
        $overHandleTime = $param['overhandleTime'] ?? null;
        $isMerge = $param['is_merge'];
        $isMergeFinished = $param['is_merge_finished'] ?? null;
        // 主办人
        if (isset($param['process_host_user'])) {
            if (getType($param['process_host_user']) == "array") {
                $process_host_user = implode(",", $param['process_host_user']);
            } else if (getType($param['process_host_user']) == "string") {
                $process_host_user = $param['process_host_user'];
            }
        } else {
            $process_host_user = "";
        }
        $limitDate = isset($param['limit_date']) ? $param['limit_date'] : '';
        $currentTime = date("Y-m-d H:i:s", time());
        $runObject = $param['flow_run_info'] ?? app($this->flowRunRepository)->getDetail($runId);
        $flowId = $runObject->flow_id;
        $receiveTime = $currentTime;
        $sendMessageFlag = true;
        // 当前节点id
        $flowProcess = $param["flow_process"];
        // 目标节点ID
        $nextFlowProcess = $param['next_flow_process'];
        // 步骤数+1
        $processIdNext = $processId + 1;
        // 查找合并节点上当前人为非主办人的记录更新为主办人
        if ($param['merge']) {
            $flowRunProcessParam = ["host_flag" =>[0], "user_run_type"=>[1], "user_last_step_flag"=>[1], "run_id" => [$runId], "flow_process" => [$flowProcess], "user_id" => [$userId]];
            app($this->flowRunProcessRepository)->updateData(["host_flag" => "1"], $flowRunProcessParam);
        }

        // 如果是监控人提交，验证一下，监控人提交下一步，
        // 如果是并发起始节点，需要保证并发选择的分支节点全部入库，再去检测是否节点已经办理
        $concurrent = $param['concurrent'] ?? 0;
        if ($concurrent) {
            $nodeIdArr = isset($param['transactProcessInfo']['process']) ? array_column($param['transactProcessInfo']['process'], 'nodeId') : [];
            $count = app($this->flowRunProcessRepository)->getFlowRunProcessList([
                'search' => [
                    'flow_process' => [$nodeIdArr, 'in'],
                    'process_id' => $processIdNext
                ],
                'returntype' => 'count'
            ]);
            if ($count == count($nodeIdArr)) {
                if ($checkMonitorHaveTurnReturn = $this->checkMonitorHaveTurn($param)) {
                    return $checkMonitorHaveTurnReturn;
                }
            }
        } else {
            if ($checkMonitorHaveTurnReturn = $this->checkMonitorHaveTurn($param)) {
                return $checkMonitorHaveTurnReturn;
            }
        }


        if (isset($param["origin_process_id"])) {
        	$processIdNext = $param["origin_process_id"] + 1;
        }
        if (!isset($param['branch_process'])) {
        	if ($processIdNext <= $runObject->max_process_id){
        		$processIdNext = $runObject->max_process_id + 1;
        	}
        }
        $processIdNext = $runObject->max_process_id + 1;
        if (!isset($param['branch_type'])) {
        	$param['branch_type'] = 1; // 1代表流转的下一步会产生分支
        	$param['branch_complete'] = 1; // 1代表流转的下一步产生的分支即将提交完成，此时是最后一个分支，此时可以更新当前节点的办理状态了
        }
        // 子流程id的串
        $subFlowRunIds = isset($param['sub_workflow_ids']) ? $param['sub_workflow_ids'] : '';
        $press_add_hour_turn = isset($param['press_add_hour_turn']) ? $param['press_add_hour_turn'] : 0;
        $flow_run_process_id = $param['flow_run_process_id'] ?? 0;
        //退回重新提交，不选人提交时，设置主办人
        if (isset($param["flowTurnType"]) && ($param["flowTurnType"] == "send_back_submit" )) {
            $transactUserArray = [];
            $process_host_user = $param['process_host_user'] ?? '';
            $sendBackParam = ['run_id' => [$runId], 'flow_process' => [$param['next_flow_process']]];
            if (isset($param['free_process_next_step'])) {
                $sendBackParam['free_process_step'] = [$param['free_process_next_step']];
            }
            $sendBackInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList(['fields' => ['user_id', 'host_flag','flow_process','process_id','origin_process','outflow_process'], 'search' => $sendBackParam,'order_by'=> ['flow_run_process_id'=>'asc'],'relationUserSystemInfo' =>true]);
            if ($sendBackInfo) {
                $sendBackInfo = $sendBackInfo->toArray();
                $sendBackProcess = '';
                if (!empty($param['next_flow_process']) && !is_array($param['next_flow_process'])) {
                	$sendBackProcess = $param['next_flow_process'];
                }
                foreach ($sendBackInfo as $_runInfo) {
                	if (!empty($sendBackProcess) && $sendBackProcess != $_runInfo['flow_process']) {
                		continue;
                	}
                	if (!empty($_runInfo['concurrent_node_id']) && empty($param['concurrent_node_id'])) {
                		$param['concurrent_node_id'] = $_runInfo['concurrent_node_id'];
                	}
                    if (isset($_runInfo['flow_run_process_has_one_user_system_info']['user_status']) && ($_runInfo['flow_run_process_has_one_user_system_info']['user_status'] != 2 && $_runInfo['flow_run_process_has_one_user_system_info']['user_status'] != 0)) {
                         $transactUserArray[] = $_runInfo['user_id'];
                    }
                    //监控退回再提交时，提交目标为监控人，此时改为节点原主办人
                    if (isset($_runInfo['host_flag']) && $_runInfo['host_flag'] == 1) {
                        if ($process_host_user != $_runInfo['user_id']) {
                            $process_host_user = $_runInfo['user_id'];
                        }
                        if (isset($_runInfo['flow_run_process_has_one_user_system_info']['user_status']) && ($_runInfo['flow_run_process_has_one_user_system_info']['user_status'] == 2 &&  $_runInfo['flow_run_process_has_one_user_system_info']['user_status'] == 0)) {
                             return ['code' => ['host_already_leave', 'flow']];
                        }
                    }
                }
                if (!isset($param['branch_type'])) {
                	$param['branch_type'] = 1;
                	$param['branch_complete'] = 1;
                }
            }
        } else {
            // 拆分办理人员
            $transactUserArray = explode(",", trim(trim($param["process_transact_user"], ",") . "," . trim($process_host_user, ","), ","));
        }
        $transactUserArray = array_unique($transactUserArray);
        // 后端验证是否设置了下一节点的办理人
        if (empty($transactUserArray) || empty($transactUserArray[0])) {
            // 请指定下一步的办理人员
            return ['code' => ['0x030012', 'flow']];
        }
        $maxProcessId =  $processIdNext;
		if(intval($maxProcessId)<intval($runObject->max_process_id)){
			$maxProcessId = $runObject->max_process_id;
		}
		// 获取下个节点类型
		$nextProcessInfo = app($this->flowProcessRepository)->getDetail($nextFlowProcess);
		$processType = $nextProcessInfo->process_type ?? 'common';
        $nextFlowProcessName = $nextProcessInfo->process_name ?? '';
		$freeProcessStep = $param['free_process_next_step'] ?? 0;
		$processInfo = app($this->flowParseService)->getProcessInfo($nextFlowProcess);
		if (isset($processInfo['merge']) && $processInfo['merge'] == 1) {
			$isFlowProcessReceive = app($this->flowParseService)->isFlowProcessReceive($runId,$nextFlowProcess);
		}
        // 根据当前办理人所处节点是否是合并节点，如果是合并节点需要同步更新相同节点的 flow_run_process 数据，按节点 id 而不是 process_id 来更新了
        // 所以将相同的节点数据却不同的步骤 id 进行转化
        $originProcessId = $processId;
        if ($isMerge && $flowTurnType != 'back') {
            $processId = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['process_id'], ['run_id'=> [$runId], 'flow_process' => $flowProcess]);
            $processId = array_values(array_unique(array_column($processId, 'process_id')));
        } else {
            $processId = [$processId];
        }
		if ($runObject->current_step) {
			app($this->flowRunRepository)->updateData(["current_step" => $nextFlowProcess, "transact_time" => $currentTime, "max_process_id" => $maxProcessId], ["run_id" => $runId]);
		} else {
			app($this->flowRunRepository)->updateData(["transact_time" => $currentTime, "max_process_id" => $maxProcessId], ["run_id" => $runId]);
		}
        if ($monitor) {
             // 更新指定步骤和节点（步骤+节点能唯一确定具体哪个步骤）上的办理人待办的 监控人提交 标识 （已有的标识可能是其他监控人提交的，不更改）
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => ["monitor_submit" => $monitor],
                "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'], "flow_process" => [$flowProcess], 'user_run_type' => [1]],
                "whereRaw" => ["monitor_submit IS NULL"]
            ]);
        }
        $processUpdateData = [
            "process_flag" => 3,
            "deliver_time" => $currentTime,
            "transact_time" => $currentTime,
            "user_run_type" => 2,
            'outflow_process' => $nextFlowProcess,
            "press_add_hour_turn" =>2
        ];
        if(empty($param['branch_type'])){
        	if(empty($param['concurrent_node_id'])){
        		app($this->flowRunProcessRepository)->updateData($processUpdateData, ["run_id" => [$runId], "process_id" => [$processId, 'in']]);
        	}else{
        		if ($param['merge']){
        			app($this->flowRunProcessRepository)->updateData($processUpdateData, ["run_id" => [$runId], "flow_process" => [$flowProcess]]);
        		} else {
        			app($this->flowRunProcessRepository)->updateData($processUpdateData, ["run_id" => [$runId], "flow_process" => [$flowProcess],"process_id" => [$processId, 'in']]);
        		}
        	}
        }else{
            if ($param['merge']) {
                if (isset($param["flowTurnType"]) && ($param["flowTurnType"] == "back") && !empty($param['concurrent_node_id']) && $param['concurrent_node_id'] != $flowProcess) {
                    app($this->flowRunProcessRepository)->updateData($processUpdateData, ["run_id" => [$runId], "flow_process" => [$flowProcess], "origin_process" => [$nextFlowProcess], "user_run_type" => [1]]);
                } else {
                    if (!empty($overHandleTime)) {
                        $processUpdateData['overhandle_time'] = $currentTime;
                    }
                    if (isset($param['branch_complete']) && $param['branch_complete']==1) {
                        // 合并节点一起处理提交退回的数据，有几条就处理几条所以按照节点来更新
                        app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => $processUpdateData, "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess], 'user_id' => [$userId]], "whereRaw" => ["(( deliver_time = '0000-00-00 00:00:00') OR (deliver_time IS NULL))"]]);
                        app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_flag" => 3], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'], 'host_flag' => [0], "process_flag" => [3, "<"]]]);
                    }
                }
            }
        	if(isset($param['branch_complete'])&&$param['branch_complete']==1){
        		if (!$param['merge']){
                    $processId = !empty($param['record_concurrent_process_ids']) ? $param['record_concurrent_process_ids'] : $processId;
        			app($this->flowRunProcessRepository)->updateFlowRunProcessData([
        			    "data" => $processUpdateData,
                        "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess],"process_id" => [$processId, 'in'], 'user_id' => [$userId]],
                        "whereRaw" => ["(( deliver_time = '0000-00-00 00:00:00') OR (deliver_time IS NULL))"]
                    ]);
                    // 更新当前用户作为经办人身份的办理状态
                    $this->updateCurrentUserHandleData($param, $processId, $currentTime);
        		}
        	}
        }
        // 监控提交或退回更新主办人和未办理的经办人的monitor_submit，流程步骤中显示为监控提交，其他自己已办理的显示正常状态
        if ($monitor) {
            $processUpdateData['monitor_submit'] = $monitor;
            // 更新指定步骤和节点（步骤+节点能唯一确定具体哪个步骤）上的主办人办理状态
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => $processUpdateData,
                "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess], "process_id" => [$processId, 'in'], 'host_flag' => [1]],
                "whereRaw" => ["((deliver_time = '0000-00-00 00:00:00') OR (deliver_time IS NULL))"]
            ]);
            // 更新 flow_run_step 表当前步骤和节点上的办理人的办理状态为已办
            app($this->flowRunStepRepository)->updateFlowRunStepData([
                "data" => ["user_run_type" => '2'],
                "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess]]
            ]);
            // 更新 flow_run_step 表当前步骤和节点上的办理人未查看的（已查看的查看时间不算）查看时间
            app($this->flowRunStepRepository)->updateFlowRunStepData([
                "data" => ["process_time" => $currentTime],
                "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'], "flow_process" => [$flowProcess]],
                "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]
            ]);
            // @todo 这里的判断条件待注释
            if ((isset($param["flowTurnType"]) && ($param["flowTurnType"] == "back" )) && $param['merge']) {
            	app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess],"origin_process" => [$nextFlowProcess]], "whereRaw" => ["(( process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            	app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess],"origin_process" => [$nextFlowProcess]], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            } else {
                // 更新 flow_run_process 表中当前步骤和节点没有查看的办理人（包括经办人和主办人）的查看时间和查看状态
            	app($this->flowRunProcessRepository)->updateFlowRunProcessData([
            	    "data" => ["process_time" => $currentTime, 'process_flag' => 3],
                    "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess]],
                    "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]
                ]);
            	// 更新 flow_run_process 表中当前步骤和节点经办人的办理时间和办理状态
            	app($this->flowRunProcessRepository)->updateFlowRunProcessData([
            	    "data" => ["saveform_time" => $currentTime, "transact_time" => $currentTime, 'user_run_type' => 2],
                    "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess],"host_flag" => '0'],
                    "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]
                ]);
            }
            $minProcessId = min($processId);
            if ($minProcessId > '1') {
                // 更新上一步的process_flag
                if (!isset($param['concurrent_flow'])) {
                	app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                	    "data" => ["process_flag" => "4"],
                        "wheres" => ["run_id" => [$runId], "process_id" => [$minProcessId - 1]]
                    ]);
                } else {
                	app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                	    "data" => ["process_flag" => "4"],
                        "wheres" => ["run_id" => [$runId], "process_id" => [$minProcessId - 1],'outflow_process' => [$flowProcess]]
                    ]);
                }
            }
        } else {
            // 更新flow_run_step
            if (empty($param['branch_type'])) {
            	if(empty($param['concurrent_node_id'])){
            		app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '2'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in']], "whereRaw" => ["((host_flag = '1') OR (user_id = '" . $userId . "'))"]]);
            	} else {
            		app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '2'], "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess]], "whereRaw" => ["((host_flag = '1') OR (user_id = '" . $userId . "'))"]]);
            	}
            } else {
            	app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '2'], "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess]], "whereRaw" => ["((host_flag = '1') OR (user_id = '" . $userId . "'))"]]);
            }
            // 主办人提交更新同分支上同步骤上的经办人的办理时间
            $this->doWhenHostUserSubmit($runId, $userId, $flowSerial, $branchSerial, $overHandleTime, $monitor);
        }

        //退回流程增加退回标识
        $isBack = 0;
        $send_back_user = '';
        $send_back_process = '';
        $send_back_free_step = 0;
        if (isset($param["flowTurnType"]) && $param["flowTurnType"] == "back") {
            $isBack = 1;
            $send_back_user = $userId;
            $send_back_process = $flowProcess;
            $send_back_free_step = $param['free_process_current_step'] ?? 0;
        }
        if (count($processId) == 1) {
            if (!isset($param['flow_run_process_id']) || empty($param['flow_run_process_id'])) {
                $flowRunProcessInfos = app($this->flowRunProcessRepository)->getFlowRunProcessList(['fields' => ['user_id', 'flow_run_process_id'],'returntype'=>'first', 'search' => ['run_id'=> [$runId],'process_id'=>[$processId, 'in']]]);
                $param['flow_run_process_id'] = $flowRunProcessInfos->flow_run_process_id;
            }
            $todu_push_params = [];
            $todu_push_params['receiveUser'] = $userId;
            $todu_push_params['deliverTime'] = $currentTime;
            $todu_push_params['deliverUser'] = $userId;
            $todu_push_params['operationType'] = 'reduce';
            $todu_push_params['operationId'] = '1';
            $todu_push_params['flowId'] = $flowId;
            $todu_push_params['runId'] = $runId;
            $todu_push_params['processId'] = $processId[0];
            $todu_push_params['flowRunProcessId'] = $param['flow_run_process_id'];
            // 操作推送至集成中心
            app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
        }else {
            $flowRunProcessInfos = app($this->flowRunProcessRepository)->getFlowRunProcessList(['fields' => ['user_id', 'flow_run_process_id'], 'search' => ['run_id'=> [$runId],'process_id'=>[$processId, 'in']]]);
            if ($flowRunProcessInfos) {
                foreach ($flowRunProcessInfos as $key => $value) {
                    $todu_push_params = [];
                    $todu_push_params['receiveUser'] = $value->user_id;
                    $todu_push_params['deliverTime'] = $currentTime;
                    $todu_push_params['deliverUser'] = $userId;
                    $todu_push_params['operationType'] = 'reduce';
                    $todu_push_params['operationId'] = '1';
                    $todu_push_params['flowId'] = $flowId;
                    $todu_push_params['runId'] = $runId;
                    $todu_push_params['processId'] = $value->process_id;
                    $todu_push_params['flowRunProcessId'] = $value->flow_run_process_id;
                    // 操作推送至集成中心
                    app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
                }
            }
        }
        // 20200807,zyx,flow_run_process表插入结果，用在流程外发失败日志展示
        $insertFlowRunProcessRes = [];
        // 收集提醒被发送人
        $remindUserIdString = "";
        $haveAgentUsers  = app($this->flowAgencyRepository)->checkUsersFlowHaveAgentRepository(["flow_id" => $flowId, "user_id" => $transactUserArray]);
        $haveAgentUsers = array_column( $haveAgentUsers->toArray() , 'by_agent_id');
        $insertFlowRunProcessDataNoAgent = [];
        $concurrentNodeId  = (!empty($param['concurrent_node_id']))?$param['concurrent_node_id']:0;
        if (!empty($param['merge']) && strpos($flowTurnType, 'turn') !== false) {
            $concurrentNodeId = 0;
        }
        $flowSerial = $this->getFlowSerial($runId,$flowId,$nextFlowProcess,$processIdNext,$flowTurnType,$concurrentNodeId,$flowProcess,$originProcessId);

        // 更新 flow_run 主表的当前最新流程序号
        app($this->flowRunRepository)->updateData(["max_flow_serial" => $flowSerial], ["run_id" => $runId]);

        $branchSerial = $this->getFlowBranchSerial($runId,$flowId,$nextFlowProcess,$processIdNext,$flowTurnType,$concurrentNodeId,$flowSerial,$flowProcess,$originProcessId);
        $processSerial = $this->getFlowProcessSerial($runId,$flowId,$nextFlowProcess,$processIdNext,$flowTurnType,$concurrentNodeId,$flowSerial,$branchSerial,$flowProcess,$originProcessId);
        // 记录需要发送消息的用户ID和flow_run_process_id
        $transactUserIdAndFlowRunProcessId = [];
        $joinUserIds = [];
        foreach ($transactUserArray as $key => $currentTransactUserId) {
            $todu_push_params = [];
            $host_flag = $currentTransactUserId == $process_host_user ? 1 : 0;
            // 没传主办人的时候 去历史合并节点中指定
            if (!$process_host_user && !empty($nextProcessInfo['merge'])){
            	$dbData = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_process'],['run_id'=>$runId,'flow_process'=>$nextFlowProcess,'user_id'=>$currentTransactUserId,"host_flag"=>1]);
            	if (!empty($dbData)) {
            		$host_flag = 1;
            	}
            }
            if (in_array($currentTransactUserId, $haveAgentUsers)) {
                // 判断每个办理人是否有委托流程
                $agentParam = ["flow_id" => $flowId, "user_id" => $currentTransactUserId];
                if ($haveAgent = $this->checkFlowHaveAgent($agentParam)) {
                    $haveAgentArray = explode("|", $haveAgent);
                    $agentFlowId = $haveAgentArray[0];
                    $agentUserId = $haveAgentArray[1];
                    $insertFlowRunProcessData = [
                        "run_id" => $runId,
                        "process_id" => $processIdNext,
                        "receive_time" => $currentTime,
                        "user_id" => $agentUserId,
                        "process_flag" => "1",
                        "flow_process" => $nextFlowProcess,
                        "process_type" => $processType,
                        "free_process_step" => $freeProcessStep,
                        "host_flag" => $host_flag,
                        "limit_date" => $limitDate,
                        "by_agent_id" => $currentTransactUserId,
                        "sub_flow_run_ids" => $subFlowRunIds,
                        "flow_agency_id" => $agentFlowId,
                        "flow_id" => $flowId,
                        "is_back" => $isBack,
                        "send_back_user" => $send_back_user,
                        "send_back_process" => $send_back_process,
                        "send_back_free_step" => $send_back_free_step,
                        "concurrent_node_id" => $concurrentNodeId,
                        "origin_process" => $flowProcess,
                    	"flow_serial" => $flowSerial,
                    	"branch_serial" => $branchSerial,
                    	"process_serial" => $processSerial,
                        "origin_user" => $param['user_id'],
                    	"origin_process_id" => $originProcessId,
                        "press_add_hour_turn" => $press_add_hour_turn,
                        "user_last_step_flag" => 1,
                        "user_run_type" => 1
                    ];
                    $this->updateLastFlagAtSubmit(['run_id' => $runId, 'user_id' => $agentUserId, 'branch_serial' => $branchSerial, 'flow_serial' => $flowSerial, 'host_flag' => $host_flag, "flow_process" => $nextFlowProcess]);
                    $insertFlowRunProcessRes[] = $flowRunProcess = app($this->flowRunProcessRepository)->insertData($insertFlowRunProcessData)->toArray();
                    if ($host_flag == '1') {
                        $flowRunProcessId = $flowRunProcess['flow_run_process_id'] ?? 0;
                    }
                    //记录流程委托链
                    $this->recordFlowAgencyChain($flowRunProcess, $agentParam);
                    //流程的短信提醒也转发到代理人那里
                    $currentTransactUserId = $agentUserId;
                }
            }else {
                $insertFlowRunProcessDataNoAgent = [
                    "run_id" => $runId,
                    "process_id" => $processIdNext,
                    "receive_time" => $receiveTime,
                    "user_id" => $currentTransactUserId,
                    "process_flag" => "1",
                    "flow_process" => $nextFlowProcess,
                    "process_type" => $processType,
                    "free_process_step" => $freeProcessStep,
                    "host_flag" => $host_flag,
                    "limit_date" => $limitDate,
                    "sub_flow_run_ids" => $subFlowRunIds,
                    "flow_id" => $flowId,
                    "is_back" => $isBack,
                    "send_back_user" => $send_back_user,
                    "send_back_process" => $send_back_process,
                    "send_back_free_step" => $send_back_free_step,
                    "press_add_hour_turn" => $press_add_hour_turn,
                    "concurrent_node_id" => $concurrentNodeId,
                    "origin_process" => $flowProcess,
                	"flow_serial" => $flowSerial,
                	"branch_serial" => $branchSerial,
                	"process_serial" => $processSerial,
                    "origin_user" => $param['user_id'],
                    "origin_process_id" => $originProcessId,
                	"user_last_step_flag" => 1,
                    "user_run_type" => 1
                ];
                $this->updateLastFlagAtSubmit(['run_id' => $runId, 'user_id' => $currentTransactUserId, 'branch_serial' => $branchSerial, 'flow_serial' => $flowSerial, 'host_flag' => $host_flag, "flow_process" => $nextFlowProcess]);
                $insertFlowRunProcessRes[] = $flowRunProcess = app($this->flowRunProcessRepository)->insertData($insertFlowRunProcessDataNoAgent)->toArray();
                if ($host_flag == '1') {
                    $flowRunProcessId = $flowRunProcess['flow_run_process_id'] ?? 0;
                }
                $todu_push_params['receiveUser'] = $currentTransactUserId;
            }
            $transactUserIdAndFlowRunProcessId[] = [
                'user_id'             => $currentTransactUserId,
                'flow_run_process_id' => $flowRunProcess['flow_run_process_id'] ?? 0,
                'host_flag'           => $host_flag
            ];
            $todu_push_params['deliverTime'] = $currentTime;
            $todu_push_params['deliverUser'] = $userId;
            $todu_push_params['operationType'] = 'add';
            $todu_push_params['operationId'] = '1';
            $todu_push_params['flowId'] = $flowId;
            $todu_push_params['runId'] = $runId;
            $todu_push_params['processId'] = $processIdNext;
            $todu_push_params['flowRunProcessId'] = $flowRunProcess['flow_run_process_id'] ?? 0;
            // 操作推送至集成中心
            app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
            // 20170830-下面这个if else 不要了，flow_run_step里面的数据，用一个函数来操作，避免出现host_flag的错误
            // if($flowRunStepInsertObject = app($this->flowRunStepRepository)->getFlowRunStepList(["search" => ["run_id" => [$runId],"user_id" => [$currentTransactUserId]]])) {
            //     if($flowRunStepInsertObject->count()) {
            //         $flow_step_id = $flowRunStepInsertObject->first()->flow_step_id;
            //         $updateFlowRunStepData = [
            //             "process_id" => $processIdNext,
            //             "flow_process" => $nextFlowProcess,
            //             "user_run_type" => '1',
            //             "host_flag" => $host_flag,
            //             "process_time" => NULL,
            //             "limit_date" => $limitDate
            //         ];
            //         app($this->flowRunStepRepository)->updateData($updateFlowRunStepData,["flow_step_id" => [$flow_step_id]]);
            //     } else {
            //         $insertFlowRunStepData = [
            //             "run_id" => $runId,
            //             "user_id" => $currentTransactUserId,
            //             "process_id" => $processIdNext,
            //             "flow_process" => $nextFlowProcess,
            //             "user_run_type" => '1',
            //             "host_flag" => $host_flag,
            //             "limit_date" => $limitDate,
            //             "flow_id" => $flowId
            //         ];
            //         app($this->flowRunStepRepository)->insertData($insertFlowRunStepData);
            //     }
            // }
            // 合并提交过滤重复提醒
            // 获取被提醒人 跟提交id关联，一次提交只发送一次提醒
            if ($submitUuid) {
                if (Cache::has($submitUuid . '_' . $currentTransactUserId)) {
                    continue;
                } else {
                    $remindUserIdString .= $currentTransactUserId . ",";
                    // 缓存下对应关系
                    Cache::put($submitUuid . '_' . $currentTransactUserId, 1, 300);
                }
            } else {
                $remindUserIdString .= $currentTransactUserId . ",";
            }
            $joinUserIds[] = $currentTransactUserId;
        }
        // 20200908，zyx,由于需要使用插入后的flow_run_process_id，因此插入操作还是放在foreach循环里，这里的批量插入注释掉
        // if (!empty($insertFlowRunProcessDataNoAgent)) {
        // 	app($this->flowRunProcessRepository)->insertMultipleData($insertFlowRunProcessDataNoAgent);
        // }
        app($this->flowParseService)->submitFlowProcess($runId,$flowProcess,$nextFlowProcess,$processId,$userId,$param,$process_host_user);
        // 20190919,zyx,如果是第一步提交，则新增一个字段更新->first_transact_time，作为流程开始时间使用
        $flowRunData = [
            "current_step" => $nextFlowProcess,
            "transact_time" => $currentTime,
            "max_process_id" => $maxProcessId
        ];
        if ($originProcessId == 1) {
            $flowRunData['first_transact_time'] = $currentTime;
        }
        // 更新flow_run表数据
        if (empty($runObject->current_step)) {
			$flowRunData['current_step'] = 0;
        }
        app($this->flowRunRepository)->updateData($flowRunData, ["run_id" => $runId]);
        //这个函数是重组flow_run_step,这个函数是重组flow_run_step得到数据，其实就是提交之后更新一哈
        // $this->rebuildFlowRunStepDataServiceRealize(["run_id" => $runId]);
        // 重新计算每个人的user_last_setp_flag
        app($this->flowParseService)->updateUserLastStepsFlag($runId, $joinUserIds);
        // 发送提醒
        $userName = (isset($param['user_name']) && !empty($param['user_name'])) ? $param['user_name']: app($this->userService)->getUserName($userId);
        if (isset($param["flowTurnType"]) && $param["flowTurnType"] == "back") {
            // 退回提醒
            $sendData['remindMark'] = 'flow-back';
        } else {
            $sendData['remindMark'] = 'flow-submit';
        }
        // $sendData['toUser'] = $remindUserIdString;
        $orderNo = $this->getTargetStepsOrderNo($flowSerial, $branchSerial, $processSerial);
        if ($processType == 'free' && $freeProcessStep) {
            $currentStepName = $orderNo . ': ' . $this->getFreeProcessName($runId, $nextFlowProcess, $freeProcessStep);
        } else {
            $currentStepName = $orderNo . ': ' . $nextFlowProcessName;
        }
        $sendData['contentParam'] = ['flowTitle' => $runObject->run_name, 'userName' => $userName, 'currentStep' => $currentStepName];
        $sendData['stateParams'] = ["flow_id" => intval($flowId), "run_id" => intval($runId)];
        // 20180625-v1-改成手动提醒，(sendMessage())的必填参数 : isHand , toUser , content , sendMethod
        // 20180625-v2-继续使用系统提醒，但是传入sendMethod，实现提醒类型可选
        if (isset($param["sendMethod"]) && isset($param["flowSubmitHandRemindToggle"]) && $param["flowSubmitHandRemindToggle"] == "1") {
            $sendData['sendMethod'] = $this->flowRunRemindMethodFilter($param["sendMethod"]);
        }
        $sendData['module_type'] = $param['flow_type_info']->flow_sort ?? app($this->flowTypeRepository)->getFlowSortByFlowId($flowId);
        if ($sendData['remindMark'] == 'flow-submit') {
        	if (isset($processInfo['merge'])){
        		if ($processInfo['merge'] == 2) {
        			$isForceMerge = app($this->flowParseService)->isForceMerge($runId,$nextFlowProcess,$userId);
        			if ($isForceMerge) {
        				$sendMessageFlag = false;
        			}
        		}
        		if ($processInfo['merge'] == 1) {
        			if (isset($isFlowProcessReceive) && $isFlowProcessReceive == true) {
        				$sendMessageFlag = false;
        			}
        		}
        	}
        }
        //将消息提醒投送到sendMessage任务中
        if ($sendMessageFlag) {
            $this->flowTurningSendMessage($transactUserIdAndFlowRunProcessId, $sendData);
        }
        $lastHostInfo = app($this->flowRunProcessRepository)->getHostInfoByRunIdAndProcessId($runId, $processId);
        if (!empty($monitor)) {
            // 监控提醒，提醒上一节点主办人
            if (!empty($lastHostInfo)) {
                $lastHandleUser = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'process_id' => [$originProcessId]]]);
                foreach ($lastHandleUser as $_user) {
                    if ($lastHostInfo->user_id != $_user->user_id) {
                        $todu_push_params = [];
                        $todu_push_params['receiveUser'] = $_user->user_id;
                        $todu_push_params['deliverTime'] = $currentTime;
                        $todu_push_params['deliverUser'] = $userId;
                        $todu_push_params['operationType'] = 'reduce';
                        $todu_push_params['operationId'] = '1';
                        $todu_push_params['flowId'] = $flowId;
                        $todu_push_params['runId'] = $runId;
                        $todu_push_params['processId'] = $processId;
                        $todu_push_params['flowRunProcessId'] = $_user->flow_run_process_id ?? 0;
                        // 操作推送至集成中心
                        app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
                    }
                }
                app($this->flowParseService)->markUnreadMessagesAsRead($flowId, $runId, $lastHandleUser);
                $sendData['remindMark'] = 'flow-monitor';
                $sendData['toUser'] = $lastHostInfo->user_id;
                unset($sendData['sendMethod']);
                if ($sendMessageFlag) {
                	Eoffice::sendMessage($sendData);
                }
            }
        }
        // 获取抄送参数，然后进行抄送
        $flowCopyParam = [
            "run_id" => $runId,
            "user_id" => $userId,
            "process_id" => $originProcessId,
            "flow_id" => $flowId,
            "flow_process" => $flowProcess,
            // 传手动被抄送人的时候，把此被抄送人放到数组里一起抄送
            "by_user_id" => isset($param["process_copy_user"]) ? $param["process_copy_user"] : "",
            "feedback_content" => isset($param["process_copy_feedback"]) ? $param["process_copy_feedback"] : "",
            "free_process_step" => isset($param["free_process_current_step"]) ? $param["free_process_current_step"] : 0
        ];

        $flowTypeObject = $param['flow_type_info'] ?? app($this->flowTypeRepository)->getFlowTypeInfoRepository(["flow_id" => $flowId]);
        $formId = $flowTypeObject->form_id ?? "";
        $conditionParam = [
            'status' => 'handle',
            'runId' => $runId,
            'formId' => $formId,
            'flowId' => $flowId,
            'nodeId' => $flowProcess,
            'flowTurnType' => $flowTurnType,
            'first_has_executed' =>  isset($param["first_has_executed"]) ? $param["first_has_executed"] : 0
        ];
        // 自由节点内部流转不触发
		/*
        if ($flowProcess != $nextFlowProcess) {
            // 合并流程只抄送一次
            if ((isset($param['branch_complete']) && $param['branch_complete'] == 1)  || !isset($param['branch_complete'])) {
                //固定抄送
                 // Queue::push(new sendFlowCopyJob($conditionParam, $flowCopyParam ));
                 $this->sendFixedFlowCopy($conditionParam, $flowCopyParam);
            }
        }
        return "1";
		*/
        // $this->sendFixedFlowCopy($conditionParam, $flowCopyParam);
        Queue::push(new sendFlowCopyJob($conditionParam, $flowCopyParam ));
        // 20200807,zyx,返回值增加插入结果
        return  $insertFlowRunProcessRes ? $insertFlowRunProcessRes :"1";
    }

    /**
     * 固定流程当前步骤标记为已提交，不产生新的步骤
     *
     * @method fixedFlowRunMarkSubmitted
     *
     * @param array $param [description]
     *
     * @return [type]                     [description]
     */
    public function fixedFlowRunMarkSubmitted($param = [])
    {
        // user_id，必填
        $userId = $param["user_id"];
        // 如果是监控人提交，验证一下，监控人提交下一步，
        if ($checkMonitorHaveTurnReturn = $this->checkMonitorHaveTurn($param)) {
            return $checkMonitorHaveTurnReturn;
        }
        $processId = $param["process_id"];
        $runId = $param["run_id"];
        $monitor = isset($param['monitor']) ? $param['monitor'] : '';
        $branchSerial = $param['branch_serial'] ?? 0;
        $flowSerial = $param['flow_serial'] ?? 0;
        $flowTurnType = $param['flowTurnType'] ?? '';

        $currentTime = date("Y-m-d H:i:s", time());
        $runObject = $param['flow_run_info'] ?? app($this->flowRunRepository)->getDetail($runId);
        $flowId = $runObject->flow_id;
        // 当前节点id
        $flowProcess = $param["flow_process"];

        // 更新flow_run表数据
        app($this->flowRunRepository)->updateData(["transact_time" => $currentTime], ["run_id" => $runId]);

        // 更新flow_run_process表数据
        $updateUserRunType = 2;
        if (!$runObject->current_step) {
            $updateUserRunType = 3;
        }
        $processUpdateData = [
            "process_flag"  => 4,
            "deliver_time"  => $currentTime,
            "transact_time" => $currentTime,
            "user_run_type" => $updateUserRunType,
        ];
        $processUpdateWhere = [
            "run_id"       => [$runId],
            "flow_process" => [$flowProcess],
            "process_id"   => [$processId],
            'user_id'      => [$userId]
        ];
        app($this->flowRunProcessRepository)->updateFlowRunProcessData([
            "data"     => $processUpdateData,
            "wheres"   => $processUpdateWhere,
            "whereRaw" => ["(( transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]
        ]);
        // 主办人提交更新同分支上同步骤上的经办人的办理时间
        $this->doWhenHostUserSubmit($runId, $userId, $flowSerial, $branchSerial, '', $monitor);
        // 监控提交或退回更新主办人和未办理的经办人的monitor_submit，流程步骤中显示为监控提交，其他自己已办理的显示正常状态
        if ($monitor) {
            $processUpdateData['monitor_submit'] = $monitor;
            // 更新指定步骤和节点（步骤+节点能唯一确定具体哪个步骤）上的主办人办理状态
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => $processUpdateData,
                "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess], "process_id" => [$processId], 'host_flag' => [1]],
                "whereRaw" => ["((transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]
            ]);
            // 更新指定步骤和节点（步骤+节点能唯一确定具体哪个步骤）上的办理人待办的 监控人提交 标识 （已有的标识可能是其他监控人提交的，不更改）
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => ["monitor_submit" => $monitor],
                "wheres" => ["run_id" => [$runId], "process_id" => [$processId], "flow_process" => [$flowProcess], 'user_run_type' => [1]],
                "whereRaw" => ["monitor_submit IS NULL"]
            ]);
            // 更新 flow_run_process 表中当前步骤和节点没有查看的办理人（包括经办人和主办人）的查看时间和查看状态
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => ["process_time" => $currentTime, 'process_flag' => 4],
                "wheres" => ["run_id" => [$runId], "process_id" => [$processId], "flow_process" => [$flowProcess]],
                "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]
            ]);
            // 更新 flow_run_process 表中当前步骤和节点经办人的办理时间和办理状态
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => ["saveform_time" => $currentTime, "transact_time" => $currentTime, 'user_run_type' => $updateUserRunType],
                "wheres" => ["run_id" => [$runId], "process_id" => [$processId], "flow_process" => [$flowProcess],"host_flag" => [0]],
                "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]
            ]);
            // 更新上一步的process_flag
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => ["process_flag" => "4"],
                "wheres" => ["run_id" => [$runId], "flow_serial" => [$flowSerial], "branch_serial" => [$branchSerial], 'outflow_process' => [$flowProcess]]
            ]);
        }
        $data = [];
        $lastHostInfo = app($this->flowRunProcessRepository)->getHostInfoByRunIdAndProcessId($runId, $processId);
        if ($lastHostInfo) {
            $data['receiveUser'] = $lastHostInfo->user_id;
            $data['deliverTime'] = $currentTime;
            $data['deliverUser'] = $userId;
            $data['operationType'] = 'reduce';
            $data['operationId'] = '1';
            $data['flowId'] = $flowId;
            $data['runId'] = $runId;
            $data['processId'] = $processId;
            $data['flowRunProcessId'] = $lastHostInfo->flow_run_process_id;
            // 操作推送至集成中心
            app($this->flowLogService)->addOperationRecordToIntegrationCenter($data);
        }
        //这个函数是重组flow_run_step,这个函数是重组flow_run_step得到数据，其实就是提交之后更新一哈
        // $this->rebuildFlowRunStepDataServiceRealize(["run_id" => $runId]);
        // 获取抄送参数，然后进行抄送
        $flowCopyParam = [
            "run_id" => $runId,
            "user_id" => $userId,
            "process_id" => $processId,
            "flow_id" => $flowId,
            "flow_process" => $flowProcess,
            // 传手动被抄送人的时候，把此被抄送人放到数组里一起抄送
            "by_user_id" => isset($param["process_copy_user"]) ? $param["process_copy_user"] : "",
            "feedback_content" => isset($param["process_copy_feedback"]) ? $param["process_copy_feedback"] : "",
            "free_process_step" => isset($param["free_process_current_step"]) ? $param["free_process_current_step"] : 0
        ];
        $flowTypeObject = $param['flow_type_info'] ?? app($this->flowTypeRepository)->getFlowTypeInfoRepository(["flow_id" => $flowId]);
        $formId = $flowTypeObject->form_id ?? "";
        $conditionParam = [
            'status' => 'handle',
            'runId' => $runId,
            'formId' => $formId,
            'flowId' => $flowId,
            'nodeId' => $flowProcess,
            'flowTurnType' => $flowTurnType,
            'first_has_executed' =>  isset($param["first_has_executed"]) ? $param["first_has_executed"] : 0
        ];
        // $this->sendFixedFlowCopy($conditionParam, $flowCopyParam);
        Queue::push(new sendFlowCopyJob($conditionParam, $flowCopyParam  ));
        return "1";
    }

    /**
     * 当主办人提交的时候更新同分支上同步骤上的经办人的办理时间等
     * @param $runId
     * @param $userId
     * @param $flowSerial
     * @param int $branchSerial
     * @param null $overHandleTime
     * @param null $monitorSubmit
     */
    public function doWhenHostUserSubmit($runId, $userId, $flowSerial, $branchSerial = 0, $overHandleTime = null, $monitorSubmit = null)
    {
        // 主办人提交更新同分支上同步骤上的经办人的办理时间
        $hostUserData = [
            "saveform_time" => Carbon::now(),
            "transact_time" => Carbon::now()
        ];
        if ($overHandleTime) { // 超时过来的需要更新下超时时间
            $hostUserData['overhandle_time'] = $overHandleTime;
        }
        if ($monitorSubmit) {
            $hostUserData['monitor_submit'] = $monitorSubmit;
        }
        app($this->flowRunProcessRepository)->updateFlowRunProcessData([
            "data" => $hostUserData,
            "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "host_flag" => '0', 'branch_serial' => [$branchSerial], 'flow_serial' => [$flowSerial]],
            "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]
        ]);
    }

    /**
     * 功能函数，流程提交退回后发送消息的函数
     */
    public function flowTurningSendMessage($transactUserIdAndFlowRunProcessId, $sendData)
    {
        // 由于这里需要需要每个人对应的自己的flow_run_process_id，所以只能循环来发送
        if (!empty($transactUserIdAndFlowRunProcessId)) {
            $willRemindsUser = [];
            // 过滤发送的人员，重复的人员只取主办人所在步骤
            foreach ($transactUserIdAndFlowRunProcessId as $key => $value) {
                if (!isset($value['user_id']) || !isset($value['host_flag'])) {
                    continue;
                }
                if (!isset($willRemindsUser[$value['user_id']]) || $value['host_flag'] == '1') {
                    $willRemindsUser[$value['user_id']] = $value;
                }
            }
            if (!empty($willRemindsUser)) {
                // 循环发送消息
                foreach ($willRemindsUser as $key => $value) {
                    if (empty($value['user_id']) || empty($value['flow_run_process_id'])) {
                        continue;
                    }
                    $sendData['toUser']                             = $value['user_id'];
                    $sendData['stateParams']['flow_run_process_id'] = intval($value['flow_run_process_id']);
                    Eoffice::sendMessage($sendData);
                }
            }
        }
        return true;
    }

    /**
     * 功能函数，过滤流程提交提醒里面的提醒方式，前端传过来的是ngModel的原型，二维的，判断已选中的且转换成一维
     * @param  [type] $sendMethod [description]
     * @return [type]             [description]
     */
    public function flowRunRemindMethodFilter($sendMethod)
    {
        $method = [];
        if (!empty($sendMethod)) {
            foreach ($sendMethod as $key => $value) {
                if ($value == "1") {
                    array_push($method, $key);
                }
            }
        }
        return $method;
    }

    /**
     * 自由流程提交结束
     *
     * @method freeFlowRunTurnNext
     *
     * @param array $param [description]
     *
     * @return [type]                     [description]
     */
    public function freeFlowRunTurnEnd($param, $flowEndParam)
    {
        // user_id，必填
        $userId = $param["user_id"];
        // 如果是监控人提交，验证一下
        if ($checkMonitorHaveTurnReturn = $this->checkMonitorHaveTurn($param)) {
            return $checkMonitorHaveTurnReturn;
        }
        $processId = $param["process_id"];
        $runId = $param["run_id"];
        $runObject = $param['flow_run_info'] ?? app($this->flowRunRepository)->getDetail($runId);
        $creator = $runObject->creator;
        $flowId = $runObject->flow_id;
        $monitor = isset($param['monitor']) ? $param['monitor'] : '';
        $currentTime = date("Y-m-d H:i:s", time());
        $lastHostInfo = app($this->flowRunProcessRepository)->getHostInfoByRunIdAndProcessId($runId, $processId);
        $flowRunUpdateData = ["current_step" => '0', "transact_time" => $currentTime];
        if ($processId == 1) {
            $flowRunUpdateData['first_transact_time'] = $currentTime;
        }
        app($this->flowRunRepository)->updateData($flowRunUpdateData, ["run_id" => $runId]);
        //更新流程数据
        // $query = "UPDATE zzzz_flow_data_$this->formId SET CURRENT_STEP = '0',TRANSACT_TIME = '".$curTime."' WHERE RUN_ID = '".$this->runId."'";
        app($this->flowRunProcessRepository)->updateData(["process_flag" => '4', "deliver_time" => $currentTime], ["run_id" => [$runId], "process_id" => [$processId]]);
        if (!empty($monitor)) {
            // 监控提交或退回更新主办人和未办理的经办人的monitor_submit，流程步骤中显示为监控提交，其他自己已办理的显示正常状态
            app($this->flowRunProcessRepository)->updateData(["monitor_submit" => $monitor, "transact_time" => $currentTime], ["run_id" => [$runId], "process_id" => [$processId], "host_flag" => '1']);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["monitor_submit" => $monitor, "saveform_time" => $currentTime, "transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId], "host_flag" => '0'], "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);

            // 推送未办理的办理人的状态为已办结
            $searchWhere = [
                'run_id' => [$runId],
                'user_last_step_flag' => [1]
            ];
            $todoList = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => $searchWhere, "whereRaw" => ["((flow_run_process.process_id = ".$processId." and flow_run_process.process_time = '".$currentTime."') OR (flow_run_process.user_run_type = 2))"], 'fields' =>['user_id','process_id','flow_run_process_id']]);

            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "user_run_type" => '2']]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["((transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]]]);
            app($this->flowRunProcessRepository)->updateData(["user_run_type" => '3'], ["run_id" => [$runId], "user_run_type" => '2']);
            if ($processId > '1') {
                // 更新上一步的process_flag
                app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_flag" => "4"], "wheres" => ["run_id" => [$runId], "process_id" => [$processId - 1]]]);
            }
        } else {
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["saveform_time" => $currentTime,"transact_time" => $currentTime, "user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "host_flag" => '0'], "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]]);
            //更新flow_run_step
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "process_id" => [$processId, '<=']], "whereRaw" => ["((transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL)) "]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["(host_flag = '1' OR user_id = '" . $userId . "')"]]);
            $searchWhere = [
                'run_id' => [$runId],
                "user_run_type" => [2],
                'user_last_step_flag' => [1]
            ];
            $todoList = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => $searchWhere,'fields' =>['user_id','process_id','flow_run_process_id']]);

            app($this->flowRunStepRepository)->updateData(["user_run_type" => '3'], ["run_id" => [$runId], "user_run_type" => '2']);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_run_type" => '3', "transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["(host_flag = '1' OR user_id = '" . $userId . "')"]]);
            app($this->flowRunProcessRepository)->updateData(["user_run_type" => '3'], ["run_id" => [$runId], "user_run_type" => '2']);
        }
        if ($todoList) {
            foreach ($todoList as $value) {
                $todu_push_params = [];
                $todu_push_params['receiveUser'] = $value['user_id'];
                $todu_push_params['deliverUser'] = $userId;
                $todu_push_params['operationType'] = 'reduce';
                $todu_push_params['operationId'] = '4';
                $todu_push_params['flowId'] = $flowId;
                $todu_push_params['runId'] = $runId;
                $todu_push_params['processId'] = $value['process_id'];
                $todu_push_params['flowRunProcessId'] = $value['flow_run_process_id'];
                // 操作推送至集成中心
                app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
            }
        }
        $flowOthersInfo = app($this->flowOthersRepository)->getDetail($flowId);
        $search["search"] = ["run_id" => [$runId]];
        if ($flowOthersInfo->flow_end_remind == 3) { //通知所有节点办理人
            if ($flowOthersInfo->remind_target == 1) { //主办人
                $search["search"]['host_flag'] = [1];
            } else if ($flowOthersInfo->remind_target == 2) { //经办人
                $search["search"]['host_flag'] = [0];
            }
        }
        $sendData = [];
        if ($flowOthersInfo->flow_end_remind == 2) { //只通知创建人
            $sendData['toUser'] = [$creator];
        } else if ($flowOthersInfo->flow_end_remind == 3) {
            // if ($flowRunStepObject = app($this->flowRunStepRepository)->getFlowRunStepList($search)) {
            if ($flowRunProcessList = app($this->flowRunProcessRepository)->getFlowRunProcessList($search)) {
                $remindUserIdArray = $flowRunProcessList->pluck("user_id");
                $sendData['toUser'] = $remindUserIdArray->toArray();
            }
        }
        // 发送提醒
        if (!empty($sendData['toUser'])) {
            //$runObject = app($this->flowRunRepository)->getDetail($runId);
            //$flowId = $runObject->flow_id;
            // $userName = app($this->userService)->getUserName($userId);
            $userName = (isset($param['user_name']) && !empty($param['user_name'])) ? $param['user_name']: app($this->userService)->getUserName($userId);
            $sendData['remindMark'] = 'flow-end';
            $sendData['contentParam'] = ['flowTitle' => $runObject->run_name, 'userName' => $userName];
            $sendData['stateParams'] = ["flow_id" => intval($flowId), "run_id" => intval($runId)];
            // 20180625-v2-继续使用系统提醒，但是传入sendMethod，实现提醒类型可选
            if (isset($param["sendMethod"]) && isset($param["flowSubmitHandRemindToggle"]) && $param["flowSubmitHandRemindToggle"] == "1") {
                $sendData['sendMethod'] = $this->flowRunRemindMethodFilter($param["sendMethod"]);
            }
            $sendData['module_type'] = $param['flow_type_info']->flow_sort ?? app($this->flowTypeRepository)->getFlowSortByFlowId($flowId);
            Eoffice::sendMessage($sendData);
        }
        if (!empty($monitor)) {
            // 监控提醒，提醒结束节点主办人

            if (!empty($lastHostInfo)) {
                $lastHandleUser = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'process_id' => [$processId]]])->pluck('user_id')->toArray();
                app($this->flowParseService)->markUnreadMessagesAsRead($flowId, $runId, $lastHandleUser);
                $sendData['remindMark'] = 'flow-monitor';
                $sendData['toUser'] = $lastHostInfo->user_id;
                unset($sendData['sendMethod']);
                Eoffice::sendMessage($sendData);
            }
        }
        $todu_push_params = [];
        $todu_push_params['receiveUser'] = $lastHostInfo->user_id;
        $todu_push_params['deliverTime'] = $currentTime;
        $todu_push_params['deliverUser'] = $userId;
        $todu_push_params['operationType'] = 'reduce';
        $todu_push_params['operationId'] = '4';
        $todu_push_params['flowId'] = $flowId;
        $todu_push_params['runId'] = $runId;
        $todu_push_params['processId'] = $processId;
        $todu_push_params['flowRunProcessId'] = $lastHostInfo->flow_run_process_id;
        // 操作推送至集成中心
        app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
        // 归档
        $this->flowFiling($param, $flowEndParam);
        return "1";
    }

    /**
     * 固定流程提交结束
     * @param $param
     * @param $flowEndParam
     * @return string|\string[][]
     */
    public function fixedFlowRunTurnEnd($param, $flowEndParam)
    {
        // user_id，必填
        $userId = $param["user_id"];
        // 如果是监控人提交，验证一下
        if ($checkMonitorHaveTurnReturn = $this->checkMonitorHaveTurn($param)) {
            return $checkMonitorHaveTurnReturn;
        }
        $originProcessId = $processId = $param["process_id"];
        $runId = $param["run_id"];
        $branchSerial = $param['branch_serial'];
        $flowSerial = $param['flow_serial'];
        $monitor = isset($param['monitor']) ? $param['monitor'] : '';
        $currentTime = date("Y-m-d H:i:s", time());
        $overHandleTime = $param['overhandleTime'] ?? null;
        $runObject = $param['flow_run_info'] ?? app($this->flowRunRepository)->getDetail($runId);
        $flowId = $runObject->flow_id;
        $creator = $runObject->creator;
        $flowProcess = $param["flow_process"];
        $lastHostInfo = app($this->flowRunProcessRepository)->getHostInfoByRunIdAndProcessId($runId, $processId);
        if (empty($lastHostInfo)) {
            $lastHostInfo = [];
        } elseif (is_object( $lastHostInfo)){
            $lastHostInfo = $lastHostInfo->toArray();
        }
        $flowTypeObject = $param['flow_type_info'] ?? app($this->flowTypeRepository)->getFlowTypeInfoRepository(["flow_id" => $flowId]);
        $formId = $flowTypeObject->form_id ?? "";
        $isMerge = $param['is_merge'];
        $conditionParam = [
            'status' => 'handle',
            'runId' => $runId,
            'formId' => $formId,
            'flowId' => $flowId,
            'nodeId' => $flowProcess,
            'flowTurnType' => 'end'
        ];
        $flowComplete = true;
        $forceEnd = !empty($param['force_end'])?$param['force_end']:'';
		$concurrentFlow = app($this->flowParseService)->isConcurrentFlow($flowId);
        // 抄送参数
        $flowCopyParam = [
            "run_id" => $runId,
            "user_id" => $userId,
            "process_id" => $originProcessId,
            "flow_id" => $flowId,
            "flow_process" => $flowProcess,
            // 传手动被抄送人的时候，把此被抄送人放到数组里一起抄送
            "by_user_id" => isset($param["process_copy_user"]) ? $param["process_copy_user"] : "",
            "feedback_content" => $param["process_copy_feedback"] ?? "",
            "free_process_step" => $param["free_process_current_step"] ?? 0
        ];

        // process_flag 节点状态（1、未查看、2、已查看、3、主办人已提交，下一步人未查看、4、主办人已提交，下一步已有人查看）

        /**
         * @todo 这个逻辑有问题 待调试后修改
         */
        // if (empty($monitor) && empty($forceEnd) && $concurrentFlow) {
        //     $flowComplete = app($this->flowParseService)->isEntireProcessComplete($runId,$flowId,$flowProcess);
        //     if(!$flowComplete){
        //         app($this->flowRunProcessRepository)->updateData(['user_run_type' => 2, "process_flag" => '3', "deliver_time" => $currentTime, "transact_time" => $currentTime], ["run_id" => [$runId], "process_id" => [$processId],"flow_process" => [$flowProcess]]);
        //         app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '2', "transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess]], "whereRaw" => ["(host_flag = '1' OR user_id = '" . $userId . "')"]]);

        //         // 抄送
        //         $this->sendFixedFlowCopy($conditionParam, $flowCopyParam);
        //         return '1';
        //     }
        // }

		// 根据当前办理人所处节点是否是合并节点，如果是合并节点需要同步更新相同节点的 flow_run_process 数据，按节点 id 而不是 process_id 来更新了
        // 所以将相同的节点数据却不同的步骤 id 进行转化
        if ($isMerge > 0) {
            $processId = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['process_id'], ['run_id'=> [$runId], 'flow_process' => $flowProcess]);
            $processId = array_column($processId, 'process_id');
        } else {
            $processId = [$processId];
        }
        // 按步骤更新所有办理人的提交时间 deliver_time (兼容之前的设计，属于冗余更新语句后期需要删除)
        app($this->flowRunProcessRepository)->updateData([
            "process_flag" => '4',
            "deliver_time" => $currentTime
        ],
            ["run_id" => [$runId], "process_id" => [$processId, 'in']]);

        // 按节点、主办人更新节点主办人(当前用户)的待办的提交时间和办理时间 (transact_time 后加入的字段)
        app($this->flowRunProcessRepository)->updateData(["process_flag" => '4', "deliver_time" => $currentTime, "transact_time" => $currentTime], ["run_id" => [$runId], "user_id" => [$userId], "flow_process" => [$flowProcess], 'user_run_type' => [1], "host_flag" => ['1']]);

        if ($monitor) {
            // 监控提交或退回更新主办人和未办理的经办人的monitor_submit，流程步骤中显示为监控提交，其他自己已办理的显示正常状态
            app($this->flowRunProcessRepository)->updateData(["monitor_submit" => $monitor], ["run_id" => [$runId], "process_id" => [$processId, 'in'],"host_flag" => '1']);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["monitor_submit" => $monitor,"process_flag" => '4', "deliver_time" => $currentTime, "transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess],"host_flag" => "1"], "whereRaw" => ["(( transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "flow_process" => [$flowProcess]], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["monitor_submit" => $monitor, "saveform_time" => $currentTime, "transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess], "host_flag" => '0'], "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess]], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess]]]);
            // 推送未办理的办理人的状态为已办结
            $searchWhere = [
                'run_id' => [$runId],
                'user_last_step_flag' => [1]
            ];
            /**
             * @todo 待优化以下逻辑
             */
            $processIdString = '(';
            foreach ($processId as $item) {
                $processIdString .= $item . ',';
            }
            $processIdString = rtrim($processIdString, ',') . ')';
            $todoList = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => $searchWhere, "whereRaw" => ["((flow_run_process.process_id in ". $processIdString .") OR (flow_run_process.user_run_type = 2))"], 'fields' =>['user_id','process_id','flow_run_process_id']]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in']]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "user_run_type" => '2']]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["process_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in']], "whereRaw" => ["((process_time = '0000-00-00 00:00:00') OR (process_time IS NULL))"]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in']], "whereRaw" => ["((transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]]);
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in'],"flow_process" => [$flowProcess]]]);
            app($this->flowRunProcessRepository)->updateData(["user_run_type" => '3'], ["run_id" => [$runId], "user_run_type" => '2']);

            $minProcessId = min($processId);
            if ($minProcessId > '1') {
                // 更新上一步的process_flag
                app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["process_flag" => "4"], "wheres" => ["run_id" => [$runId], "process_id" => [$minProcessId - 1]]]);
            }

        } else {

            // 更新 flow_run_step 当前办理人步骤为办结
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in']], "whereRaw" => ["(host_flag = '1' OR user_id = '" . $userId . "')"]]);

            // 将 flow_run_step 已经办理的用户状态改成结束（办结）
            app($this->flowRunStepRepository)->updateData(["user_run_type" => '3'], ["run_id" => [$runId], "user_run_type" => [2]]);

            // 主办人提交更新同分支上同步骤上的经办人的办理时间
            $this->doWhenHostUserSubmit($runId, $userId, $flowSerial, $branchSerial, $overHandleTime, $monitor);
            $searchWhere = [
                'run_id' => [$runId],
                "user_run_type" => [2],
                "user_last_step_flag" => [1]
            ];
            $todoList = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => $searchWhere,'fields' =>['user_id','process_id','flow_run_process_id']]);
            // 更新flow_run_step
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["transact_time" => $currentTime], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "process_id" => [$processId, '<=']], "whereRaw" => ["((transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL)) "]]);
            app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId]], "whereRaw" => ["(host_flag = '1' OR user_id = '" . $userId . "')"]]);

            // 更新 flow_run_process 当前办理人步骤为办结
            app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId, 'in']], "whereRaw" => ["(host_flag = '1' OR user_id = '" . $userId . "')"]]);

            // 更新 flow_run_process 将已经办理的用户状态改成结束（办结）
            app($this->flowRunProcessRepository)->updateData(["user_run_type" => '3'], ["run_id" => [$runId], "user_run_type" => [2]]);
            // 更新当前用户作为经办人身份的办理状态
            $this->updateCurrentUserHandleData($param, $processId, $currentTime);

        }
        $flowRunUpdateData = ["current_step" => '0', "transact_time" => $currentTime];
        if ($originProcessId == 1) {
            $flowRunUpdateData['first_transact_time'] = $currentTime;
        }
        // 更新流程运行表数据为已结束
        app($this->flowRunRepository)->updateData($flowRunUpdateData, ["run_id" => $runId]);

        if ($todoList) {
            foreach ($todoList as $value) {
                $todu_push_params = [];
                $todu_push_params['receiveUser'] = $value['user_id'];
                $todu_push_params['deliverUser'] = $userId;
                $todu_push_params['operationType'] = 'reduce';
                $todu_push_params['operationId'] = '4';
                $todu_push_params['flowId'] = $flowId;
                $todu_push_params['runId'] = $runId;
                $todu_push_params['processId'] = $value['process_id'];
                $todu_push_params['flowRunProcessId'] = $value['flow_run_process_id'];
                // 操作推送至集成中心
                app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
            }
        }
        $flowOthersInfo = app($this->flowOthersRepository)->getDetail($flowId);
        $search["search"] = ["run_id" => [$runId]];
        if ($flowOthersInfo->flow_end_remind == 3) { //通知所有节点办理人
            if ($flowOthersInfo->remind_target == 1) { //主办人
                $search["search"]['host_flag'] = [1];
            } else if ($flowOthersInfo->remind_target == 2) { //经办人
                $search["search"]['host_flag'] = [0];
            }
        } else if ($flowOthersInfo->flow_end_remind == 4) { //通知指定节点办理人
            if ($flowOthersInfo->remind_target == 1) { //主办人
                $search["search"]['host_flag'] = [1];
            } else if ($flowOthersInfo->remind_target == 2) { //经办人
                $search["search"]['host_flag'] = [0];
            }
            $search["search"]['flow_process'] = [explode(',', $flowOthersInfo->appoint_process), 'in'];
        }
        // 发送提醒
        $sendData = [];
        if ($flowOthersInfo->flow_end_remind == 2) { //只通知创建人
            $sendData['toUser'] = [$creator];
        } else {
            // if ($flowRunStepObject = app($this->flowRunStepRepository)->getFlowRunStepList($search)) {
            if ($flowRunProcessList = app($this->flowRunProcessRepository)->getFlowRunProcessList($search)) {
                $remindUserIdArray = $flowRunProcessList->pluck("user_id");
                $sendData['toUser'] = array_unique($remindUserIdArray->toArray());
            }
        }
        $userName = (isset($param['user_name']) && !empty($param['user_name'])) ? $param['user_name']: app($this->userService)->getUserName($userId);
        $sendData['contentParam'] = ['flowTitle' => $runObject->run_name, 'userName' => $userName];
        $sendData['stateParams'] = ["flow_id" => intval($flowId), "run_id" => intval($runId)];
        $sendData['module_type'] = $flowTypeObject->flow_sort ?? app($this->flowTypeRepository)->getFlowSortByFlowId($flowId);
        // 20180625-v2-继续使用系统提醒，但是传入sendMethod，实现提醒类型可选
        if (isset($param["sendMethod"]) && isset($param["flowSubmitHandRemindToggle"]) && $param["flowSubmitHandRemindToggle"] == "1") {
            $sendData['sendMethod'] = $this->flowRunRemindMethodFilter($param["sendMethod"]);
        }
        if (!empty($sendData['toUser'])) {
            $sendData['remindMark'] = 'flow-end';
            Eoffice::sendMessage($sendData);
        }
        if (!empty($monitor)) {
            // 监控提醒，提醒结束节点主办人
            if (!empty($lastHostInfo)) {
                $lastHandleUser = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'process_id' => [$originProcessId]]])->pluck('user_id')->toArray();
                app($this->flowParseService)->markUnreadMessagesAsRead($flowId, $runId, $lastHandleUser);
                $sendData['remindMark'] = 'flow-monitor';
                $sendData['toUser'] = $lastHostInfo['user_id'] ?? '';
                Eoffice::sendMessage($sendData);
            }
        }
        if (empty($lastHostInfo) && empty($monitor)) {
            $lastHostInfo['user_id'] = $userId;
        }
        if (count($processId) == 1) {
            $todu_push_params = [];
            $todu_push_params['receiveUser'] = $lastHostInfo['user_id'] ?? '';
            $todu_push_params['deliverTime'] = $currentTime;
            $todu_push_params['deliverUser'] = $userId;
            $todu_push_params['operationType'] = 'reduce';
            $todu_push_params['operationId'] = '4';
            $todu_push_params['flowId'] = $flowId;
            $todu_push_params['runId'] = $runId;
            $todu_push_params['processId'] = $processId[0];
            $todu_push_params['flowRunProcessId'] = $lastHostInfo['flow_run_process_id'] ?? '';
            // 操作推送至集成中心
            app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
        }else {
            $processInfos = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['process_id', 'user_id', 'flow_run_process_id'], ['run_id'=> [$runId], 'flow_process' => $flowProcess]);
            if ($processInfos) {
                foreach ($processInfos as  $_processInfos) {
                    $todu_push_params = [];
                    $todu_push_params['receiveUser'] = $_processInfos['user_id'];
                    $todu_push_params['deliverTime'] = $currentTime;
                    $todu_push_params['deliverUser'] = $userId;
                    $todu_push_params['operationType'] = 'reduce';
                    $todu_push_params['operationId'] = '4';
                    $todu_push_params['flowId'] = $flowId;
                    $todu_push_params['runId'] = $runId;
                    $todu_push_params['processId'] = $_processInfos['process_id'];
                    $todu_push_params['flowRunProcessId'] = $_processInfos['flow_run_process_id'];
                    // 操作推送至集成中心
                    app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
                }
            }
        }
        // 归档
        $this->flowFiling($param, $flowEndParam);

        // 抄送
        // $this->sendFixedFlowCopy($conditionParam, $flowCopyParam);
        Queue::push(new sendFlowCopyJob($conditionParam, $flowCopyParam  ));

        // 增加判断子流程是否已经归档，如果归档则更新归档文件的权限[坑]
        // $flowToDoc = new flowToDoc($flowid,$subRunIdArray[$i],"update");
        return "1";
    }

    // 更新当前用户作为经办人身份的办理状态
    public function updateCurrentUserHandleData($param, $processId, $currentTime)
    {
        $userId = $param['user_id'] ?? '';
        $runId = $param['run_id'] ?? '';
        if (empty($userId) || empty($runId) || empty($processId)) return false;
        $updateHandleUserData = [
            "process_flag" => 4,
            "deliver_time" => $currentTime,
            "transact_time" => $currentTime,
            "saveform_time" => $currentTime,
            "user_run_type" => 3,
        ];
        // 更新比当前flow_serial小的节点上当前用户作为经办人的未办理状态为已办理
        if (!empty($param['flow_serial'])) {
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => $updateHandleUserData,
                "wheres" => ["run_id" => [$runId], 'user_id' => [$userId], 'host_flag' => [0], 'flow_serial' => [$param['flow_serial'], '<']],
                "whereRaw" => ["(( transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]
            ]);
        }
        // 如果当前是分支上的节点，更新这条分支线上当前用户作为经办人的未办理状态为已办理
        if (!empty($param['branch_serial']) && !empty($param['flow_serial'])) {
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => $updateHandleUserData,
                "wheres" => ["run_id" => [$runId],"branch_serial" => [$param['branch_serial']], 'user_id' => [$userId], 'host_flag' => [0], 'flow_serial' => [$param['flow_serial']]],
                "whereRaw" => ["(( transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]
            ]);
            $updateHandleUserData['process_flag'] = 3;
            app($this->flowRunProcessRepository)->updateFlowRunProcessData([
                "data" => $updateHandleUserData,
                "wheres" => ["run_id" => [$runId],"branch_serial" => [$param['branch_serial']], 'user_id' => [$userId], 'host_flag' => [0], 'flow_serial' => [$param['flow_serial']], 'process_id' => [$processId, 'in']],
                "whereRaw" => ["(( transact_time = '0000-00-00 00:00:00') OR (transact_time IS NULL))"]
            ]);
        }
    }

    // 获取运行流程序号
    public function getFlowSerial($runId,$flowId,$nextFlowProcess,$processIdNext,$flowTurnType,$concurrentNodeId,$flowProcess,$processId){
        // 获取当前节点的信息
        $currentFlowRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_serial','concurrent_node_id', 'branch_serial'],['run_id'=> $runId, 'process_id' => $processId]);
        $currentFlowRunProcessInfo = $currentFlowRunProcessInfo[0] ?? [];
        $flowSerial = $currentFlowRunProcessInfo['flow_serial'] ?? 1;
        // 如果当前节点是是并发分支上的节点
        if (!empty($currentFlowRunProcessInfo['concurrent_node_id'])) {
            $currentProcessInfo = app($this->flowParseService)->getProcessInfo($flowProcess);
            // 如果当前节点是合并节点且不是退回的时候需要增加序号
            if (!empty($currentProcessInfo['merge']) && strpos($flowTurnType, 'back') === false) {
                $flowSerial++;
            }
        } else {
            // 如果当前节点不是并发分支上的节点
            $flowSerial++;
        }
        return $flowSerial;
    }

    // 获取分支序号
    public function getFlowBranchSerial($runId,$flowId,$nextFlowProcess,$processIdNext,$flowTurnType,$concurrentNodeId,$flowSerial,$flowProcess,$processId){
        // 获取当前节点的信息
        $currentFlowRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_serial','concurrent_node_id', 'branch_serial'],['run_id'=> $runId, 'process_id' => $processId]);
        $currentFlowRunProcessInfo = $currentFlowRunProcessInfo[0] ?? [];
        $branchSerial = $currentFlowRunProcessInfo['branch_serial'] ?? 0;
        $currentProcessInfo = app($this->flowParseService)->getProcessInfo($flowProcess);
        // 如果当前节点是并发节点
        if (!empty($currentProcessInfo['concurrent'])) {
            // 如果是退回的
            if (strpos($flowTurnType, 'back') !== false) {
                // 如果是未触发过并发分支的并发节点，则分支序号为0
                if (empty($currentFlowRunProcessInfo['concurrent_node_id']) ) {
                    return 0;
                } else {
                    return $branchSerial;
                }
            }
            // 获取同一个流程序号的办理步骤列表
            $flowRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_process','process_id','deliver_time','flow_serial','concurrent_node_id','host_flag','branch_serial','process_serial'],['run_id'=>$runId,'flow_serial'=>$flowSerial,'concurrent_node_id'=>$concurrentNodeId]);
            // 如果当前序号还没有记录，分支序号就为1
            if (empty($flowRunProcessInfo)){
                $nextProcessInfo = app($this->flowParseService)->getProcessInfo($nextFlowProcess);
                if (empty($nextProcessInfo['branch'])) {
                    return 0;
                }
                return 1;
            } else {
                // 如果当前序号有记录，取已经存在的分支序号加1
                $branchSerial = 0;
                foreach($flowRunProcessInfo as $v){
                    if ($v['flow_process'] == $flowProcess && $v['process_id'] == $processId) {
                        // 如果流出的节点在当前序号里已经有过提交记录，则直接等于之前的分支序号
                        if (app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'flow_serial' => [$flowSerial], 'flow_process' => [$nextFlowProcess]], 'returntype' => 'count'])) {
                            $branchSerial = $v['branch_serial'];
                            return $branchSerial;
                        }
                    }
                    if (intval($v['branch_serial']) > $branchSerial){
                        $branchSerial = intval($v['branch_serial']);
                    }
                }
                $branchSerial++;
                return $branchSerial;
            }
        // 如果当前节点是合并节点且不是退回的时候需要增加流程序号，或者当前节点不是分支上的节点也序号增加流程序号，此时分支序号设置为0
        } else if ((!empty($currentFlowRunProcessInfo['concurrent_node_id']) && !empty($currentProcessInfo['merge']) && strpos($flowTurnType, 'back') === false)) {
            $branchSerial = 0;
        // 如果当前是合并节点，且是退回操作，且是在普通序号上的合并节点，非并发分支上的，
        } else if (empty($currentFlowRunProcessInfo['concurrent_node_id'])) {
            if (!empty($currentProcessInfo['merge']) && (strpos($flowTurnType, 'back') !== false)) {
                // 获取同一个流程序号的办理步骤列表
                $flowRunProcessList = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'flow_serial' => [$flowSerial]], 'returntype' => 'first', 'order_by' => ['branch_serial' => 'desc']]);
                // 如果当前序号还没有记录，分支序号就为1
                if (empty($flowRunProcessList)){
                    $branchSerial = 1;
                } else {
                    $branchSerial = $flowRunProcessList->branch_serial + 1;
                }
            } else {
                $branchSerial = 0;
            }
        }
        return $branchSerial;
    }

    // 获取分支节点序号
    public function getFlowProcessSerial($runId,$flowId,$nextFlowProcess,$processIdNext,$flowTurnType,$concurrentNodeId,$flowSerial,$branchSerial,$flowProcess,$processId){
        // 获取当前节点的信息
        $currentFlowRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_serial','concurrent_node_id', 'branch_serial', 'process_serial'],['run_id'=> $runId, 'process_id' => $processId, 'branch_serial' => $branchSerial]);
        $currentFlowRunProcessInfo = $currentFlowRunProcessInfo[0] ?? [];
        $processSerial = $currentFlowRunProcessInfo['process_serial'] ?? 0;
        // 如果当前节点是是并发分支上的节点
        if (!empty($currentFlowRunProcessInfo['concurrent_node_id'])) {
            $currentProcessInfo = app($this->flowParseService)->getProcessInfo($flowProcess);
            // 如果当前节点是合并节点且不是退回的时候需要重置序号
            if (!empty($currentProcessInfo['merge']) && strpos($flowTurnType, 'back') === false) {
                $processSerial = 0;
            // 如果当前节点是并发节点
            } else if (!empty($currentProcessInfo['concurrent'])) {
                if (strpos($flowTurnType, 'back') !== false || !empty($currentFlowRunProcessInfo['concurrent_node_id'])) {
                    $processSerial++;
                } else {
                    // 如果流出的节点在当前序号里已经有过提交记录，则直接等于之前的分支序号+1，如果没有过提交记录则分支序号为1
                    if (app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'flow_serial' => [$flowSerial], 'flow_process' => [$nextFlowProcess]], 'returntype' => 'count'])) {
                        $processSerial++;
                    } else {
                        $processSerial = 1;
                    }
                }
            } else {
                $processSerial++;
            }
        } else {
            $currentProcessInfo = app($this->flowParseService)->getProcessInfo($flowProcess);
            if (!empty($currentProcessInfo['concurrent'])) {
                if (strpos($flowTurnType, 'back') !== false) {
                    $processSerial = 0;
                } else {
                    $nextProcessInfo = app($this->flowParseService)->getProcessInfo($nextFlowProcess);
                    if (empty($nextProcessInfo['branch'])) {
                        return 0;
                    }
                    $processSerial = 1;
                }
            } else if (!empty($currentProcessInfo['merge'])) {
                if (strpos($flowTurnType, 'back') !== false) {
                    // 获取同一个流程序号的办理步骤数量
                    $flowRunProcessListCount = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$runId], 'flow_serial' => [$flowSerial], 'branch_serial' => [$branchSerial]], 'returntype' => 'count']);
                    // 如果当前序号还没有记录，分支节点序号就为1
                    if (empty($flowRunProcessListCount)){
                        $processSerial = 1;
                    } else {
                        $processSerial = $flowRunProcessListCount + 1;
                    }
                }
            }
        }
        return $processSerial;
    }

    /**
     * 流程归档主函数
     * @param  [type] $param        [流程办理参数]
     * @param  [type] $flowEndParam [流程其他附属参数]
     * @return [type]               [description]
     */
    public function flowFiling($param, $flowEndParam)
    {
        $filingFailedlogFileName = 'flow_filing_conditions_verify_fail_' . date('Ymd') . '.txt';
        // 判断归档条件
        if (isset($flowEndParam["flow_to_doc"]) && $flowEndParam["flow_to_doc"] == "1" && isset($flowEndParam["flow_filing_conditions_setting_toggle"]) && $flowEndParam["flow_filing_conditions_setting_toggle"] == "1") {
            // 设置归档条件时，验证类型设置
            if (isset($flowEndParam["flow_filing_conditions_verify_mode"])) {
                $formData = isset($flowEndParam["formData"]) ? $flowEndParam["formData"] : [];
                // 1、使用表达式进行判断
                if ($flowEndParam["flow_filing_conditions_verify_mode"] == "1") {
                    if (isset($flowEndParam["flow_filing_conditions_setting_value"])) {
                        $flowOtherInfo = [
                            'form_structure' => isset($flowEndParam["form_structure"]) ? $flowEndParam["form_structure"] : [],
                            'user_id' => isset($flowEndParam["user_id"]) ? $flowEndParam["user_id"] : "",
                            'process_id' => isset($flowEndParam["process_id"]) ? $flowEndParam["process_id"] : "",
                        ];
                        $verify = $this->verifyFlowFormOutletCondition($flowEndParam["flow_filing_conditions_setting_value"], $formData, $flowOtherInfo);
                        if (!$verify) {
                            // 日志
                            app($this->flowLogService)->addFlowDataLogs($filingFailedlogFileName, "Filing Fail;runId : " . (isset($param["run_id"]) ? $param["run_id"] : "") . "\r\ncondition : " . $flowEndParam["flow_filing_conditions_setting_value"] . "\r\nformData : " . json_encode($formData) . "\r\n\r\n");
                            return false;
                        }
                    }
                } else if ($flowEndParam["flow_filing_conditions_verify_mode"] == "2") {
                    // 2、使用文件进行判断
                    if (isset($flowEndParam["flow_filing_conditions_verify_url"]) && $flowEndParam["flow_filing_conditions_verify_url"]) {
                        // 文件url
                        $verifyFileUrl = parse_relative_path_url($flowEndParam["flow_filing_conditions_verify_url"]);
                        $formData['run_id'] = $param["run_id"];
                        $formData['run_name'] = $flowEndParam["run_name"];
                        $formData['flow_id'] = $param["flow_id"];
                        $formData['process_id'] = $param['process_id'];
                        $formData['user_id'] = $param["user_id"];
                        try {
                            $guzzleResponse = (new Client())->request('POST', $verifyFileUrl, ['form_params' => $formData]);
                            $status = $guzzleResponse->getStatusCode();
                        } catch (\Exception $e) {
                            $status = $e->getMessage();
                        }
                        $validateFlag = false;
                        if (!empty($guzzleResponse)) {
                            //返回结果
                            $content = $guzzleResponse->getBody()->getContents();
                            if ($content == 'true' || $content == '1' || $content === true) {
                                $validateFlag = true;
                            }
                        }
                        if (!$validateFlag) {
                            // 日志
                            app($this->flowLogService)->addFlowDataLogs($filingFailedlogFileName, "Filing Fail;URL Verify;runId : " . (isset($param["run_id"]) ? $param["run_id"] : "") . "\r\nurl : " . $verifyFileUrl . "\r\nformData : " . json_encode($formData) . "\r\n\r\n");
                            return false;
                        }
                    }
                }
            }
        }
        // 判断是否设置了归档
        if ($flowEndParam["flow_to_doc"] == "1") {
            // 组织数据
            // 文档主表数据
            $returnData["document"] = [];
            // 文档附件数据
            $returnData["attachment"] = [];
            // 文档回复数据
            $returnData["revert"] = [];
            // 文档共享人员数据
            $returnData["user"] = "";
            if (isset($flowEndParam["file_folder_id"])) {
                if ($flowEndParam["file_folder_id"]) {
                    $folderId = $this->getFlowEndFilingForderId($param, $flowEndParam, $flowEndParam["file_folder_id"]);
                } else {
                    // 这种情况下，去创建文件夹
                    $param["file_folder_id"] = "";
                    $folderId = $this->getFlowEndFilingForderId($param, $flowEndParam);
                }
            } else {
                $folderId = $this->getFlowEndFilingForderId($param, $flowEndParam);
            }
            if ($folderId) {
                $runId = $param["run_id"];
                // 有权限人员
                $viewUser = $this->getFlowFilingViewUser($param);
                // 文档内容
                // 文件夹类型，1公共，5流程归档文件夹
                $returnData["document"]["folder_type"] = "5";
                // 文档类型，0-html,1-word,2-excel
                $returnData["document"]["document_type"] = "0";
                // 文件夹id，来自函数
                $returnData["document"]["folder_id"] = $folderId;
                $returnData["document"]["source_id"] = $runId;
                $returnData["document"]["source_seq"] = $flowEndParam["run_seq_strip_tags"];
                $returnData["document"]["flow_manager"] = $viewUser;
                $returnData["document"]["subject"] = $flowEndParam["run_name"];
                // 传空，这里不再保存解析后的表单内容，而是在查看文档页面做特殊处理，即时解析，为保密字段准备。
                $returnData["document"]["content"] = $this->getFlowFilingContent($runId);
                // 文档状态，0草稿，1发布
                $returnData["document"]["status"] = "1";
                // 创建人为空，没确定这里放什么呢，文档那里特殊处理成展示：流程归档
                // 归档文档的创建人，1-空/流程归档；2-结束流程时的主办人；3-流程创建人
                $flowFilingDocumentCreate = isset($flowEndParam["flow_filing_document_create"]) ? $flowEndParam["flow_filing_document_create"] : "1";
                $filingDocumentCreator = "archive";
                if ($flowFilingDocumentCreate == "2") {
                    $filingDocumentCreator = isset($flowEndParam["user_id"]) ? $flowEndParam["user_id"] : "";
                } else if ($flowFilingDocumentCreate == "3") {
                    $filingDocumentCreator = isset($flowEndParam["creator"]) ? $flowEndParam["creator"] : "";
                }
                $returnData["document"]["creator"] = $filingDocumentCreator;
                $returnData["document"]["extra_fields"] = json_encode(["source" => "flow_run", "run_id" => $runId, "run_seq" => $flowEndParam["run_seq"], "link_doc" => $flowEndParam["link_doc"]]);
                // 附件
                $flowAttachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'flow_run', 'entity_id' => ["run_id" => [$runId]]]);
                $returnData["attachment"] = $flowAttachments;
                // 回复
                $feedbackList = $this->getFeedbackRealize(["order_by" => ['edit_time' => 'asc']], $runId);
                $list = $feedbackList["list"];
                $revert = [];
                if ($feedbackList["total"]) {
                    foreach ($list as $key => $value) {
                        $revert[$key]["user_id"] = $value["user_id"];
                        $revert[$key]["revert_content"] = $value["content"];
                        $revert[$key]["prcs_name"] = $value["flow_run_feedback_has_one_node"]["process_name"];
                        $revert[$key]["prcs_id"] = $value["process_id"];
                        $revert[$key]["created_at"] = $value["edit_time"];
                        $revert[$key]["extra_fields"] = json_encode(["source" => "flow_run", "process_name" => $value["flow_run_feedback_has_one_node"]["process_name"], "process_id" => $value["process_id"]]);
                        $revert[$key]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'feedback', 'entity_id' => $value["feedback_id"]]);
                    }
                }
                $returnData["revert"] = $revert;
                $returnData["user"] = $viewUser;
                // 其他参数
                $returnData["current_user"] = $param["user_id"];
                // 调用文档模块函数
                $filingResult = app($this->documentService)->archiveDocument($returnData);
                return $filingResult;
            }
        }
    }

    /**
     * 待补充函数，返回流程归档的时候，归档到的目录的id
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowEndFilingForderId($param, $flowEndParam = [], $file_folder_id = 0)
    {
        if (!isset($param["flow_id"])) {
            return "";
        }
        if (!empty($param['file_folder_id'])) {
            return $param['file_folder_id'];
        }
        $flowId = $param["flow_id"];
        $flowFileFolderId = "";
        if ($file_folder_id) {
            $flowFileFolderId = $file_folder_id;
        } else {
            if (!isset($param["file_folder_id"])) {
                $flowFileFolderInfo = app($this->flowOthersRepository)->getFlowOthersInfo($flowId)->toArray();
                $flowFileFolderId = $flowFileFolderInfo[0]['file_folder_id'];
            }
        }
        if (!empty($flowEndParam['flow_filing_folder_rules_toggle']) && !empty($flowEndParam["flow_filing_folder_rules"])) {
            //获取归档文件夹解析规则
            $data = ['flow_id' => $flowId, 'creator' => isset($flowEndParam['creator']) ? $flowEndParam['creator'] : "", 'form_data' => $flowEndParam["formData"], 'form_structure' => $flowEndParam["form_structure"]];
            $data['flow_filing_folder_rules'] = $flowEndParam["flow_filing_folder_rules"];
            $flowRule = app($this->flowParseService)->getFlowFilingFolderRule($data);
            if (!empty($flowRule)) {
                //根节点
                $folderId = 0;
                if ($flowFileFolderId) {
                    $folderId = $flowFileFolderId;
                }
                foreach ($flowRule as $rule) {
                    $folderId = $this->createFlowFileFolder($rule, $folderId);
                }
                return $folderId;
            }
            return "";
        } else {
            if ($flowFileFolderId) {
                return $flowFileFolderId;
            } else {
                //获取流程名称流程类别
                $flowTypeInfo = app($this->flowTypeRepository)->getFlowTypeInfoRepository(["flow_id" => $flowId], ['flow_sort']);
                $flowName = $flowTypeInfo->flow_name;
                $flowSort = $flowTypeInfo->flowTypeBelongsToFlowSort ? $flowTypeInfo->flowTypeBelongsToFlowSort->title : trans('flow.unclassified');

                if (!$flowName || !$flowSort) {
                    return "";
                }
                //创建流程归档目录
                $flowFileFolderId = $this->createFlowFileFolder('流程归档', 0);
                if ($flowFileFolderId) {
                    $flowFileFolderSortId = $this->createFlowFileFolder($flowSort, $flowFileFolderId);
                    if ($flowFileFolderSortId) {
                        $flowFileFolderSortNameId = $this->createFlowFileFolder($flowName, $flowFileFolderSortId);
                        if ($flowFileFolderSortNameId) {
                            return $flowFileFolderSortNameId;
                        }
                    }
                }
                return "";
            }
        }
    }

    /**
     * 判断流程归档目录是否存在，不存在则新建，存在返回id
     */
    public function createFlowFileFolder($folderName, $parentId)
    {
        $data = [
            'fields' => 'folder_id',
            'search' => ['folder_name' => [$folderName], 'parent_id' => [$parentId]],
        ];
        if ($folderName == '流程归档') {
            $data['search']['folder_level_id'] = [0];
            $data['search']['folder_type'] = [5];
        }
        $result = app($this->documentFolderRepository)->listFolder($data)->toArray();

        if (empty($result[0]['folder_id'])) {
            $documentId = app($this->documentService)->addFolder(['parent_id' => $parentId, 'folder_name' => $folderName, 'folder_type' => 5], ['user_id' => 'admin']);
            return $documentId['folder_id'];
        } else {
            return $result[0]['folder_id'];
        }
    }

    /**
     * 返回流程归档的时候，有权限查看的人员
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowFilingViewUser($param)
    {
        // 查 flow_run_prcocess 表 user_id,monitor_submit,by_agent_id
        // 查 flow_copy 表 by_user_id
        // 查 flow_run 表 creator,view_user[注意：字符串]
        $runId = $param["run_id"];
        $flowRunProcessParam = [
            "run_id" => $runId,
            "fields" => ["flow_run_process_id", "user_id", "monitor_submit", "by_agent_id"],
            "agencyDetailInfo" => true,
        ];
        $userId = [];
        if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessParam)) {
            if ($flowRunProcessObject->count()) {
                foreach ($flowRunProcessObject->toArray() as $key => $value) {
                    if ($value["user_id"]) {
                        array_push($userId, $value['user_id']);
                    }
                    if ($value["monitor_submit"]) {
                        array_push($userId, $value['monitor_submit']);
                    }
                    if ($value["by_agent_id"]) {
                        array_push($userId, $value['by_agent_id']);
                    }
                    // 委托链中的所有人
                    if (isset($value['flow_run_process_has_many_agency_detail']) && count($value['flow_run_process_has_many_agency_detail']) > 0) {
                        foreach ($value['flow_run_process_has_many_agency_detail'] as $agencyDetailChainValue) {
                            array_push($userId, $agencyDetailChainValue['user_id']);
                            array_push($userId, $agencyDetailChainValue['by_agency_id']);
                        }
                    }
                }
            }
        }
        if ($flowCopyArray = app($this->flowCopyRepository)->getFlowCopyList(["returntype" => "array", "search" => ["run_id" => [$runId]], "fields" => ["by_user_id"]])) {
            if (count($flowCopyArray)) {
                foreach ($flowCopyArray as $copyKey => $copyValue) {
                    if ($copyValue["by_user_id"]) {
                        array_push($userId, $copyValue['by_user_id']);
                    }
                }
            }
        }
        if ($runObject = app($this->flowRunRepository)->getDetail($runId)) {
            if ($runObject->creator) {
                array_push($userId, $runObject->creator);
            }
            if ($runObject->view_user) {
                $viewUser = $runObject->view_user;
                $viewUserArray = explode(",", $viewUser);
                foreach ($viewUserArray as $viewKey => $viewValue) {
                    if ($viewValue) {
                        array_push($userId, $viewValue);
                    }
                }
            }
        }
        $userId = implode(",", array_unique($userId));
        return $userId;
    }

    /**
     * 进行固定抄送
     *
     * @method sendFixedFlowCopy
     *
     * @param array $conditionParam
     *
     * @param array $param
     * @return array|mixed|string
     */
    public function sendFixedFlowCopy($conditionParam, $param = [])
    {
        // 并发节点循环抄送会生成多条记录
        if (isset($conditionParam['first_has_executed']) && $conditionParam['first_has_executed'] != 0) {
            return true;
        }
        // 提交时传的抄送参数
        $byUserIdArray = !empty($param["by_user_id"]) ? $param["by_user_id"] : [];
        if (!empty($byUserIdArray) && $byUserIdArray == 'all') {
            $byUserIdArray = app($this->userService)->getAllUserIdString(['return_type' => 'array']);
        }
        if (!empty($byUserIdArray) && is_string($byUserIdArray)) {
            $byUserIdArray = explode(',', trim($byUserIdArray, ','));
        }
        //抄送参数配置
        if (isset($param["user_id"])) {
            $copyUser = $param["user_id"];
        } else {
            $transactUserArr = $this->getFlowTransactUser($param);
            if (count($transactUserArr["OP_FLAG_USER"]) > 0) {
                $copyUserArr = array_keys($transactUserArr["OP_FLAG_USER"]);
                $copyUser = $copyUserArr[0];
            }
        }
        if (!$copyUser) {
            // 没有抄送人员
            return ['code' => ['0x030021', 'flow']];
        }
        // 取节点信息
        if (isset($GLOBALS['getFlowNodeDetail'.$param["flow_process"]])) unset($GLOBALS['getFlowNodeDetail'.$param["flow_process"]]);
        if ($detailResult = $this->getFlowNodeDetail($param["flow_process"])) {
            $submitType = $conditionParam['flowTurnType'];
            // 默认抄送人员
            $defaultCopyUser = [];
            // 判断是否开启对应的默认抄送人触发时机
            $triggerCondition =
                (
                    (($submitType == 'turn') || ($submitType == 'send_back_submit')) &&
                    ($detailResult->trigger_copy_submit == 1)
                ) ||
                (($submitType == 'back') && ($detailResult->trigger_copy_back == 1)) ||
                (($submitType == 'end') && ($detailResult->trigger_copy_end == 1));
            if ($triggerCondition) {
                $userInfo = $this->autoGetCopyUser(["flow_id" => $param["flow_id"], "node_id" => $param["flow_process"], "run_id" => $param["run_id"]], $detailResult);
                // 智能获值，没人的时候，返回的消息
                $emptyReturnMessage = isset($userInfo["emptyReturnMessage"]) ? $userInfo["emptyReturnMessage"] : "";
                if (!empty($emptyReturnMessage)) {
                    return $emptyReturnMessage;
                }
                if (!empty($userInfo) && $userInfo == "ALL") {
                    $getUserParam = [
                        "fields" => ["user_id", "user_name"],
                        "page" => "0",
                        "returntype" => "object",
                        "getDataType" => "",
                    ];
                    $defaultCopyUser = app($this->userRepository)->getConformScopeUserList($getUserParam)->pluck("user_id")->toArray();
                } else {
                    if (isset($userInfo["autoGetUserInfo"]) && !$emptyReturnMessage) {
                        $userInfo = $userInfo["autoGetUserInfo"];
                        // 默认抄送人员范围
                        $scopeUserCountFlag = "";
                        if ($userInfo == "ALL") {
                            $getUserParam = [
                                "fields" => ["user_id", "user_name"],
                                "page" => "0",
                                "returntype" => "object",
                                "getDataType" => "",
                            ];
                            $defaultCopyUser = app($this->userRepository)->getConformScopeUserList($getUserParam)->pluck("user_id")->toArray();
                        } else {
                            $scopeUserCountFlag = count($userInfo);
                            if ($scopeUserCountFlag) {
                                $defaultCopyUser = $userInfo->pluck("user_id")->toArray();
                            }
                        }
                    } else {
                        if (!empty($userInfo) && is_object($userInfo)) {
                            $defaultCopyUser = $userInfo->pluck("user_id")->toArray();
                        }
                    }
                }
                // 固定流程判断抄送是否满足当前节点的抄送条件
                if ($detailResult->copy_condition) {
                    // 获取流程表单数据
                    $flowFormData = app($this->flowService)->getFlowFormParseData($conditionParam, user());
                    $formStructure = [];
                    if (isset($flowFormData['parseFormStructure']) && !empty($flowFormData['parseFormStructure'])) {
                        foreach ($flowFormData['parseFormStructure'] as $formStructureKey => $formStructureValue) {
                            $formStructure[$formStructureKey] = isset($formStructureValue['control_type']) ? $formStructureValue['control_type'] : '';
                        }
                    }
                    $copyCondition = $this->verifyFlowFormOutletCondition($detailResult->copy_condition, $flowFormData['parseData'], ['form_structure' => $formStructure , 'user_id' => $param["user_id"]?? '' , "process_id" => $param["process_id"]?? '' ]);
                    if (!$copyCondition) {
                        $defaultCopyUser = [];
                    }
                }
            }
            if (!empty($byUserIdArray) && count($byUserIdArray)) {
                // 合并默认抄送人和手动选择的抄送人
                $defaultCopyUser = array_unique(array_merge($defaultCopyUser, $byUserIdArray));
            }
            // 排除当前用户
            $defaultCopyUser = array_diff($defaultCopyUser, [$copyUser]);
            if (!empty($defaultCopyUser)) {
                $batchCopyParams = [
                    "by_copy_user" => $defaultCopyUser,
                    "run_id" => $param["run_id"],
                    "process_id" => $param["process_id"],
                    "copy_user" => $copyUser,
                    "feedback_content" => $param["feedback_content"],
                    "flow_process" => $param["flow_process"],
                    "free_process_step" => $param["free_process_step"],
                ];
                $batchCopyParams['flow_process'] = $param['flow_process'] ?? 0;

                $this->batchToCopyFLow($batchCopyParams);
                return $batchCopyParams;
            }
        }
    }

    /**
     * 获取流程办理人
     *
     * @method getFlowTransactUser
     *
     * @param array $param [description]
     *
     * @return [type]                     [description]
     */
    public function getFlowTransactUser($param = [])
    {
        if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowTransactUserRepository($param)) {
            $transactUserArr["HAVE_DEAL"] = array();
            $transactUserArr["NOT_DEAL"] = array();
            $transactUserArr["OP_FLAG_USER"] = array();
            if ($flowRunProcessObject->count()) {
                $flowRunProcessArray = $flowRunProcessObject->toArray();
                foreach ($flowRunProcessArray as $key => $value) {
                    if ($value["HAVE_DEAL"] && !in_array($value["HAVE_DEAL"], $transactUserArr["HAVE_DEAL"])) {
                        $transactUserArr["HAVE_DEAL"][$value["HAVE_DEAL"]] = $value["user_name"];
                    }

                    if ($value["NOT_DEAL"] && !in_array($value["NOT_DEAL"], $transactUserArr["NOT_DEAL"])) {
                        $transactUserArr["NOT_DEAL"][$value["NOT_DEAL"]] = $value["user_name"];
                    }

                    if ($value["OP_FLAG_USER"] && !in_array($value["OP_FLAG_USER"], $transactUserArr["OP_FLAG_USER"])) {
                        $transactUserArr["OP_FLAG_USER"][$value["OP_FLAG_USER"]] = $value["user_name"];
                    }
                }
            }
            return $transactUserArr;
        }
    }

    /**
     * 进行流程抄送。固定/手动抄送，都不直接调用 newFlowCopy ，而是调用此函数，进行循环和参数拼接之后，再调用 newFlowCopy 。
     * @param  [type] $param [被抄送人字段[by_copy_user]要求是一位数组]
     * @return [type]        [description]
     */
    public function batchToCopyFLow($param)
    {
        $runId = $param["run_id"];
        $byCopyUserIdArray = $param["by_copy_user"];
        if (count($byCopyUserIdArray)) {
            if ($runObject = app($this->flowRunRepository)->getDetail($runId , false , ['flow_id' , 'run_name' , 'current_step'])) {
                $flowId = $runObject->flow_id;
                $runName = $runObject->run_name;
                $copyData["flow_id"] = $flowId;
                $copyData["run_id"] = $runId;
                $copyData["process_id"] = $param["process_id"];
                $copyData["copy_user"] = $param["copy_user"];
                if(!empty($param['flow_process'])){
                	$copyData['flow_process'] = $param['flow_process'];
                }
                foreach ($byCopyUserIdArray as $key => $byUserId) {
                    $copyData["by_user_id"] = $byUserId;
                    // 单个抄送
                    $flowCopyId = $this->newFlowCopy($copyData);
                    // 收集被提醒人
                    if (!$flowCopyId) {
                        unset($byCopyUserIdArray[$key]);
                    }
                }
                // 发送提醒
                $userName = app($this->userService)->getUserName($param["copy_user"]);
                $sendData['remindMark'] = 'flow-cc';
                $sendData['toUser'] = $byCopyUserIdArray;
                $sendData['contentParam'] = ['flowTitle' => $runName, 'userName' => $userName];
                $sendData['stateParams'] = ["flow_id" => intval($flowId), "run_id" => intval($runId)];
                $sendData['module_type'] = app($this->flowTypeRepository)->getFlowSortByFlowId($flowId);
                Eoffice::sendMessage($sendData);

                if ($runObject->current_step == 0 ) {
                        $documentId =  app($this->documentContentRepository)->getDocumentIdByTypeAndSource( 5 ,$runId);
                        if (!empty($documentId)) {
                            // 判断是否归档，如果归档还需要将当前抄送人更新到share_user字段
                            $shareUser = app($this->documentShareRepository)->getDetail($documentId->document_id , false , ['share_user']);
                            if (!empty($shareUser)) {
                                $lastShareUser = $shareUser->share_user.','.implode(',', $byCopyUserIdArray);
                                app($this->documentShareRepository)->updateData(['share_user' => $lastShareUser], ['document_id' => $documentId->document_id]);
                            }
                        }
                }
                //生成反馈提醒
                $feedbackContent = isset($param["feedback_content"]) ? $param["feedback_content"] : false;
                if ($feedbackContent !== false && trim($feedbackContent) != "" ) {
                    $byCopyUserNameString =  app($this->userRepository)->entity->wheres(['user_id' => [$param["by_copy_user"], 'in']])->pluck('user_name')->toArray();
                    // 插入签办反馈
                    $data["edit_time"]    = date('Y-m-d H:i:s');
                    $data["receive_user_id"] =   implode(',', $param["by_copy_user"]);
                    $data["receive_user_name"] =   implode(' ', $byCopyUserNameString);
                    $data["feedback_type"] = 'cc';
                    $data["run_id"] = $runId;
                    $data["user_id"] = $param["copy_user"];
                    $data["process_id"] = $param["process_id"];
                    $data["flow_process"] = $param["flow_process"];
                    $data["free_process_step"] = $param["free_process_step"] ?? 0;
                    $feedbackContent = str_replace("\r\n", "<br />", $feedbackContent);
                    $feedbackContent = str_replace("\n", "<br />", $feedbackContent);
                    $data["content"] = $feedbackContent;
                    $data["run_feedback_id"] = $this->getNextRunFeedbackId($runId, $data["user_id"]);
                    $feedbackInsertData = array_intersect_key($data, array_flip(app($this->flowRunFeedbackRepository)->getTableColumns()));
                    app($this->flowRunFeedbackRepository)->insertData($feedbackInsertData);
                    //如果是流程已结束，还需要增加到文档回复中
                    if ($runObject->current_step == 0 ) {
                        if (!empty($documentId)) {
                               $revert["document_id"] = $documentId->document_id;
                               $revert["user_id"] = $data["user_id"];
                               $revert["revert_content"] = $data["content"];
                               $revert["created_at"] = $data["edit_time"];
                               $revert["extra_fields"] = json_encode(["source" => "flow_run", "process_name" =>  $data["flow_process"], "process_id" => $data["process_id"]]);
                               app( $this->documentRevertRepository)->insertData( $revert);
                        }
                    }
                }
            }
        }
    }
    /**
     * 实现单个流程抄送的数据库操作等
     *
     * @method newFlowCopy
     *
     * @param array $param [description]
     *
     * @return [type]             [description]
     */
    public function newFlowCopy($param = [])
    {
        if (empty($param["run_id"]) || empty($param["by_user_id"]) || empty($param["copy_user"]) || empty($param["process_id"]) || empty($param["flow_id"])) {
            return false;
        }
        $getCopyListParams = [
            "returntype" => "count",
            "search" => [
                "by_user_id" => [$param["by_user_id"]],
                "copy_user"  => [$param["copy_user"]],
                "process_id" => [$param["process_id"]],
                "run_id"     => [$param["run_id"]]
            ]
        ];
        $flowCopyObject = app($this->flowCopyRepository)->getFlowCopyList($getCopyListParams);
        if ($flowCopyObject) {
            return false;
        }
        $copyInsertData = [
            "by_user_id" => $param["by_user_id"],
            "run_id"     => $param["run_id"],
            "copy_user"  => $param["copy_user"],
            "copy_time"  => time(),
            "copy_type"  => "1",
            "flow_id"    => $param["flow_id"],
            "process_id" => $param["process_id"],
            "flow_process" => $param["flow_process"],
        ];
        return app($this->flowCopyRepository)->insertData($copyInsertData);
    }

    /**
     * 定义流程--新建流程的时候，根据流程模板id，复制流程
     *
     * @method flowDefineCopy
     *
     * @param  [type]         $flowTemplateId [description]
     * @param  [type]         $flowName       [description]
     * @param  [type]         $flowSort       [description]
     * @param  [type]         $own            [当前用户信息]
     *
     * @return [type]                         [description]
     */
    public function flowDefineCopy($flowTemplateId, $flowName, $flowSort, $own)
    {
        // //获取离职人员
        // if(!Redis::exists('out_users_array')){
        //     $all_quit_user = app($this->userService)->getLeaveOfficeUser();
        //     if($all_quit_user) {
        //         $all_quit_user = $all_quit_user->toArray();
        //         Redis::set('out_users_array',serialize($all_quit_user));
        //     }
        // }
        // $outUserList =  unserialize(Redis::get('out_users_array'));
        // $all_quit_user_lists = [];
        // foreach ($outUserList as $key => $value) {
        //     $all_quit_user_lists[] = $value['user_id'];
        // }
        if ($flowTypeObject = app($this->flowTypeRepository)->getFlowTypeInfoRepository(["flow_id" => $flowTemplateId])) {
            $processObject = $flowTypeObject->flowTypeHasManyFlowProcess;
            $flowOthersObject = $flowTypeObject->flowTypeHasOneFlowOthers;
            $flowTermObject = $flowTypeObject->flowTypeHasManyFlowTerm;
            $flowTypeArray = $flowTypeObject->toArray();
            $flowNamePinYin = convert_pinyin($flowName);
            $insertData = [
                "flow_name" => $flowName,
                "flow_name_py" => $flowNamePinYin[0],
                "flow_name_zm" => $flowNamePinYin[1],
                "flow_sort" => $flowSort,
                'flow_type' => $flowTypeArray['flow_type'],
                'form_id' => $flowTypeArray['form_id'],
                'flow_document' => $flowTypeArray['flow_document'],
                'flow_link' => $flowTypeArray['flow_link'],
                'flow_sequence' => $flowTypeArray['flow_sequence'],
                "flow_noorder" => $flowTypeArray['flow_noorder'],
                "handle_way" => $flowTypeArray['handle_way'],
                "countersign" => $flowTypeArray['countersign'],
                "countersign_remind" => $flowTypeArray['countersign_remind'],
                "can_edit_flowno" => $flowTypeArray["can_edit_flowno"],
                "press_add_hour" => $flowTypeArray["press_add_hour"],
                "create_user" => $flowTypeArray["create_user"],
                "create_role" => $flowTypeArray["create_role"],
                "create_dept" => $flowTypeArray["create_dept"],
                "flow_name_rules_html" => $flowTypeArray["flow_name_rules_html"],
                "flow_name_rules" => $flowTypeArray["flow_name_rules"],
                "can_edit_flowname" => $flowTypeArray["can_edit_flowname"],
                // "monitor_user_type"    => $flowTypeArray["monitor_user_type"],
                // "monitor_scope"        => $flowTypeArray["monitor_scope"],
                "allow_monitor" => $flowTypeArray["allow_monitor"],
                "limit_skip_holiday_toggle" => $flowTypeArray["limit_skip_holiday_toggle"],
                "overtime_except_nonwork" => $flowTypeArray["overtime_except_nonwork"],
                "sub_show_main_process_title" => $flowTypeArray["sub_show_main_process_title"],
                "open_debug" => $flowTypeArray["open_debug"],
                // "allow_view"           => $flowTypeArray["allow_view"],
                // "allow_turn_back"      => $flowTypeArray["allow_turn_back"],
                // "allow_delete"         => $flowTypeArray["allow_delete"],
                // "allow_take_back"      => $flowTypeArray["allow_take_back"],
                // "allow_end"            => $flowTypeArray["allow_end"],
                // "allow_urge"           => $flowTypeArray["allow_urge"],
            ];
            if ($flowTypeObject = app($this->flowTypeRepository)->insertData($insertData)) {
                $flowId = $flowTypeObject->flow_id;
                // 自由流程办理人
                if ($createUserObject = app($this->flowTypeCreateUserRepository)->getList($flowTemplateId)) {
                    if ($createUserObject->count()) {
                        $userData = [];
                        foreach ($createUserObject->pluck("user_id") as $v) {
                            // //人员离职，跳过
                            // if(in_array($v,$all_quit_user_lists)) {
                            //     continue;
                            // }
                            $userData[] = ['flow_id' => $flowId, 'user_id' => $v];
                        }
                        app($this->flowTypeCreateUserRepository)->insertMultipleData($userData);
                    }
                }
                if ($createRoleObject = app($this->flowTypeCreateRoleRepository)->getList($flowTemplateId)) {
                    if ($createRoleObject->count()) {
                        $roleData = [];
                        foreach ($createRoleObject->pluck("role_id") as $v) {
                            $roleData[] = ['flow_id' => $flowId, 'role_id' => $v];
                        }
                        app($this->flowTypeCreateRoleRepository)->insertMultipleData($roleData);
                    }
                }
                if ($createDeptObject = app($this->flowTypeCreateDepartmentRepository)->getList($flowTemplateId)) {
                    if ($createDeptObject->count()) {
                        $deptData = [];
                        foreach ($createDeptObject->pluck("dept_id") as $v) {
                            $deptData[] = ['flow_id' => $flowId, 'dept_id' => $v];
                        }
                        app($this->flowTypeCreateDepartmentRepository)->insertMultipleData($deptData);
                    }
                }

                if ($flowTypeArray["allow_monitor"] == '1') {
                    $flowTempManageRuleData = app($this->flowTypeManageRuleRepository)->getList($flowTemplateId)->toArray();
                    if (!empty($flowTempManageRuleData)) {
                        foreach ($flowTempManageRuleData as $key => $value) {
                            $insertData = $value;
                            unset($insertData['rule_id']);
                            $insertData['flow_id'] = $flowId;
                            $insertRuleResult = app($this->flowTypeManageRuleRepository)->insertData($insertData);
                            if (isset($insertRuleResult->rule_id)) {
                                // 监控人员
                                $manageUser = app($this->flowTypeManageUserRepository)->getList(['flow_id' => [$flowTemplateId], 'rule_id' => [$value['rule_id']]])->toArray();
                                if (!empty($manageUser)) {
                                    $insertManageUserData = [];
                                    foreach ($manageUser as $manageUserKey => $manageUserValue) {
                                        $insertManageUserData[] = [
                                            'flow_id' => $flowId,
                                            'user_id' => $manageUserValue['user_id'],
                                            'rule_id' => $insertRuleResult->rule_id,
                                        ];
                                    }
                                    if (!empty($insertManageUserData)) {
                                        app($this->flowTypeManageUserRepository)->insertMultipleData($insertManageUserData);
                                    }
                                }
                                // 监控角色
                                $manageRole = app($this->flowTypeManageRoleRepository)->getList(['flow_id' => [$flowTemplateId], 'rule_id' => [$value['rule_id']]])->toArray();
                                if (!empty($manageRole)) {
                                    $insertManageRoleData = [];
                                    foreach ($manageRole as $manageRoleKey => $manageRoleValue) {
                                        $insertManageRoleData[] = [
                                            'flow_id' => $flowId,
                                            'role_id' => $manageRoleValue['role_id'],
                                            'rule_id' => $insertRuleResult->rule_id,
                                        ];
                                    }
                                    if (!empty($insertManageRoleData)) {
                                        app($this->flowTypeManageRoleRepository)->insertMultipleData($insertManageRoleData);
                                    }
                                }
                                // 监控范围部门
                                $manageScopeDept = app($this->flowTypeManageScopeDeptRepository)->getList(['flow_id' => [$flowTemplateId], 'rule_id' => [$value['rule_id']]])->toArray();
                                if (!empty($manageScopeDept)) {
                                    $insertManageScopeDeptData = [];
                                    foreach ($manageScopeDept as $manageScopeDeptKey => $manageScopeDeptValue) {
                                        $insertManageScopeDeptData[] = [
                                            'flow_id' => $flowId,
                                            'dept_id' => $manageScopeDeptValue['dept_id'],
                                            'rule_id' => $insertRuleResult->rule_id,
                                        ];
                                    }
                                    if (!empty($insertManageScopeDeptData)) {
                                        app($this->flowTypeManageScopeDeptRepository)->insertMultipleData($insertManageScopeDeptData);
                                    }
                                }
                                // 监控范围人员
                                $manageScopeUser = app($this->flowTypeManageScopeUserRepository)->getList(['flow_id' => [$flowTemplateId], 'rule_id' => [$value['rule_id']]])->toArray();
                                if (!empty($manageScopeUser)) {
                                    $insertManageScopeUserData = [];
                                    foreach ($manageScopeUser as $manageScopeUserKey => $manageScopeUserValue) {
                                        $insertManageScopeUserData[] = [
                                            'flow_id' => $flowId,
                                            'user_id' => $manageScopeUserValue['user_id'],
                                            'rule_id' => $insertRuleResult->rule_id,
                                        ];
                                    }
                                    if (!empty($insertManageScopeUserData)) {
                                        app($this->flowTypeManageScopeUserRepository)->insertMultipleData($insertManageScopeUserData);
                                    }
                                }
                            }
                        }
                    }
                }
                // 自由流程，首节点必填设置
                $requiredInfo = app($this->flowRequiredForFreeFlowRepository)->getList($flowTemplateId);
                if ($requiredInfo) {
                    $requiredInfo = $requiredInfo->pluck('control_id')->toArray();
                } else {
                    $requiredInfo = [];
                }
                if (count($requiredInfo)) {
                    $freeFlowRequiredNewInfo = [];
                    foreach ($requiredInfo as $freeFlowRequiredKey => $freeFlowRequiredValue) {
                        $freeFlowRequiredNewInfo[] = ['flow_id' => $flowId, 'control_id' => $freeFlowRequiredValue];
                    }
                    app($this->flowRequiredForFreeFlowRepository)->insertMultipleData($freeFlowRequiredNewInfo);
                }
                // 节点数据
                // 记录插入的节点的id，后面用来插入出口条件
                $newFlowProcessIdArray = [];
                if ($processObject->count()) {
                    // 收集旧process_id和新process_id的对应
                    $collectPorcessIdOldToNewArray = [];
                    $collectOldPorcessToArray = [];
                    $collectOldAutoGetArray = [];
                    $collectOldAutoGetCopyArray = [];
                    $i = 1;

                    foreach ($processObject as $processKey => $processValue) {
                        $processValue = $processValue->toArray();
                        $processInsertData = [
                            "flow_id" => $flowId,
                            "process_id" => $processValue["process_id"],
                            "process_name" => $processValue["process_name"],
                            "process_item" => $processValue["process_item"],
                            "process_user" => $processValue["process_user"],
                            "process_role" => $processValue["process_role"],
                            "process_dept" => $processValue["process_dept"],
                            // "process_to"              => $processValue["process_to"],
                            "process_concourse" => $processValue["process_concourse"],
                            "process_concourse_remind" => $processValue["process_concourse_remind"],
                            "process_transact_type" => $processValue["process_transact_type"],
                            "process_default_user" => $processValue["process_default_user"],
                            "process_default_type" => $processValue["process_default_type"],
                            "process_default_manage" => $processValue["process_default_manage"],
                            "process_item_view" => $processValue["process_item_view"],
                            "process_item_capacity" => $processValue["process_item_capacity"],
                            "process_item_auto" => $processValue["process_item_auto"],
                            "process_term" => $processValue["process_term"],
                            "process_descript" => $processValue["process_descript"],
                            "sub_workflow_ids" => $processValue["sub_workflow_ids"],
                            "run_ways" => $processValue["run_ways"],
                            "process_auto_get_user" => $processValue["process_auto_get_user"],
                            "process_auto_get_copy_user" => $processValue["process_auto_get_copy_user"],
                            "process_forward" => $processValue["process_forward"],
                            "process_copy" => $processValue["process_copy"],
                            "process_item_required" => $processValue["process_item_required"],
                            "end_workflow" => $processValue["end_workflow"],
                            "press_add_hour" => $processValue["press_add_hour"],
                            "flow_outsend" => $processValue["flow_outsend"],
                            "flow_outmail" => $processValue["flow_outmail"],
                            "process_copy_user" => $processValue["process_copy_user"],
                            "process_copy_dept" => $processValue["process_copy_dept"],
                            "process_copy_role" => $processValue["process_copy_role"],
                            "flow_outsend_type" => $processValue["flow_outsend_type"],
                            "position" => $processValue["position"],
                            "head_node_toggle" => $processValue["head_node_toggle"],
                            "sun_flow_toggle" => $processValue["sun_flow_toggle"],
                            "flow_outsend_timing" => $processValue["flow_outsend_timing"],
                            "flow_outsend_toggle" => $processValue["flow_outsend_toggle"],
                            "sort" => $processValue["sort"],
                            "created_at" => date("Y-m-d H:i:s", time() + $i),
                            // "sun_flow_premise"        => $processValue["sun_flow_premise"],
                            "flow_run_template_toggle" => $processValue["flow_run_template_toggle"],
                            "process_entrust" => $processValue["process_entrust"],
                            "press_add_hour_turn" => $processValue["press_add_hour_turn"],
                            "press_add_hour_remind" => $processValue["press_add_hour_remind"],
                            "overtime_except_nonwork" => $processValue["overtime_except_nonwork"],
                            "limit_skip_holiday_toggle" => $processValue["limit_skip_holiday_toggle"],
                            "concurrent" => $processValue["concurrent"],
                            "merge" => $processValue["merge"],
                            "copy_condition"  => $processValue['copy_condition'],
                            "process_type"  => $processValue['process_type'],
                            "auto_submit_todo_flow"  => $processValue['auto_submit_todo_flow'],
                            "trigger_son_flow_back" => $processValue['trigger_son_flow_back'],
                            "branch"                => $processValue['branch'],
                            "overtime_handle_required" => $processValue['overtime_handle_required'],
                        ];
                        $i++;
                        if ($flowProcessInsertObject = app($this->flowProcessRepository)->insertData($processInsertData)) {
                            $id = $flowProcessInsertObject->node_id;
                            app($this->flowParseService)->copyFormValidate($processValue['node_id'], $id, $processValue);
                            $newFlowProcessIdArray[] = $id;
                            // 权限的分表数据的转存，上面已原样保存旧数据，所以可以不考虑ALL的问题。
                            $oldId = $processValue["node_id"];
                            $collectPorcessIdOldToNewArray[$oldId] = $id;
                            $collectOldPorcessToArray[$oldId] = $processValue["process_to"];
                            $collectOldAutoGetArray[$oldId] = $processValue["process_auto_get_user"];
                            $collectOldAutoGetCopyArray[$oldId] = $processValue["process_auto_get_copy_user"];
                            // 办理人
                            if ($processUserObject = app($this->flowProcessUserRepository)->getList($oldId)) {
                                if ($processUserObject->count()) {
                                    $userData = [];
                                    foreach ($processUserObject->pluck("user_id") as $v) {
                                        $userData[] = ['id' => $id, 'user_id' => $v];
                                    }
                                    app($this->flowProcessUserRepository)->insertMultipleData($userData);
                                }
                            }
                            if ($processRoleObject = app($this->flowProcessRoleRepository)->getList($oldId)) {
                                if ($processRoleObject->count()) {
                                    $roleData = [];
                                    foreach ($processRoleObject->pluck("role_id") as $v) {
                                        $roleData[] = ['id' => $id, 'role_id' => $v];
                                    }
                                    app($this->flowProcessRoleRepository)->insertMultipleData($roleData);
                                }
                            }
                            if ($processDeptObject = app($this->flowProcessDepartmentRepository)->getList($oldId)) {
                                if ($processDeptObject->count()) {
                                    $deptData = [];
                                    foreach ($processDeptObject->pluck("dept_id") as $v) {
                                        $deptData[] = ['id' => $id, 'dept_id' => $v];
                                    }
                                    app($this->flowProcessDepartmentRepository)->insertMultipleData($deptData);
                                }
                            }
                            // 抄送
                            if ($processCopyUserObject = app($this->flowProcessCopyUserRepository)->getList($oldId)) {
                                if ($processCopyUserObject->count()) {
                                    $userData = [];
                                    foreach ($processCopyUserObject->pluck("user_id") as $v) {
                                        $userData[] = ['id' => $id, 'user_id' => $v];
                                    }
                                    app($this->flowProcessCopyUserRepository)->insertMultipleData($userData);
                                }
                            }
                            if ($processCopyRoleObject = app($this->flowProcessCopyRoleRepository)->getList($oldId)) {
                                if ($processCopyRoleObject->count()) {
                                    $roleData = [];
                                    foreach ($processCopyRoleObject->pluck("role_id") as $v) {
                                        $roleData[] = ['id' => $id, 'role_id' => $v];
                                    }
                                    app($this->flowProcessCopyRoleRepository)->insertMultipleData($roleData);
                                }
                            }
                            if ($processCopyDeptObject = app($this->flowProcessCopyDepartmentRepository)->getList($oldId)) {
                                if ($processCopyDeptObject->count()) {
                                    $deptData = [];
                                    foreach ($processCopyDeptObject->pluck("dept_id") as $v) {
                                        $deptData[] = ['id' => $id, 'dept_id' => $v];
                                    }
                                    app($this->flowProcessCopyDepartmentRepository)->insertMultipleData($deptData);
                                }
                            }
                            // 默认办理人
                            if ($processDefaultUserObject = app($this->flowProcessDefaultUserRepository)->getList($oldId)) {
                                if ($processDefaultUserObject->count()) {
                                    $userData = [];
                                    foreach ($processDefaultUserObject->pluck("user_id") as $v) {
                                        $userData[] = ['id' => $id, 'user_id' => $v];
                                    }
                                    app($this->flowProcessDefaultUserRepository)->insertMultipleData($userData);
                                }
                            }
                            //节点字段设置
                            // 操作
                            // flow_process_control_operation
                            // flow_process_control_operation_detail
                            // 1、根据 $oldId [旧的node_id]，去 flow_process_control_operation 获取数据(取control_id)
                            // 先查 flow_process_control_operation
                            $operationParam = ["search" => ['node_id' => [$oldId]]];
                            $operationInfo = app($this->flowProcessControlOperationRepository)->getList($operationParam)->toArray();
                            if (count($operationInfo)) {
                                foreach ($operationInfo as $key => $value) {
                                    $operationId = isset($value["operation_id"]) ? $value["operation_id"] : "";
                                    $controlId = isset($value["control_id"]) ? $value["control_id"] : "";
                                    // 2、根据新 node_id 和 control_id，插入 flow_process_control_operation 获取新的operation_id
                                    $newOperationId = app($this->flowProcessControlOperationRepository)->insertGetId(['node_id' => $id, 'control_id' => $controlId]);
                                    // 3、根据 $operationId 去 flow_process_control_operation_detail 获取 operation_type 再 插入到 flow_process_control_operation_detail 里
                                    $operationDetailParam = ["search" => ['operation_id' => [$operationId]]];
                                    $operationDetailInfo = app($this->flowProcessControlOperationDetailRepository)->getList($operationDetailParam)->toArray();
                                    if (count($operationDetailInfo)) {
                                        $operationDetailData = [];
                                        foreach ($operationDetailInfo as $detailKey => $detailValue) {
                                            $operationType = isset($detailValue["operation_type"]) ? $detailValue["operation_type"] : "";
                                            $operationDetailData[] = ['operation_id' => $newOperationId, 'operation_type' => $operationType];
                                        }
                                        app($this->flowProcessControlOperationDetailRepository)->insertMultipleData($operationDetailData);
                                    }
                                }
                            }
                            // 字段控制改进，废弃
                            //flow_sun_workflow
                            //flow_outsend
                            if ($flowSunWorkflowObject = app($this->flowSunWorkflowRepository)->getList($oldId)) {
                                if ($flowSunWorkflowObject->count()) {
                                    $data = [];
                                    foreach ($flowSunWorkflowObject->toArray() as $v) {
                                        $data[] = [
                                            'node_id' => $id,
                                            'premise' => $v['premise'],
                                            'run_ways' => $v['run_ways'],
                                            'receive_flow_id' => $v['receive_flow_id'],
                                            'porcess_fields' => $v['porcess_fields'],
                                            'receive_fields' => $v['receive_fields'],
                                        ];
                                    }
                                    app($this->flowSunWorkflowRepository)->insertMultipleData($data);
                                }
                            }
                            //超时提醒
                            if ($flowOverTimeRemindObject = app($this->flowOverTimeRemindRepository)->getList($oldId)) {
                                if ($flowOverTimeRemindObject->count()) {
                                    $data = [];
                                    foreach ($flowOverTimeRemindObject->toArray() as $v) {
                                        $data[] = [
                                            'node_id' => $id,
                                            'flow_id' => $flowId,
                                            'remind_time' => $v['remind_time'],
                                            'overtime_ways' => $v['overtime_ways'],
                                        ];
                                    }
                                    app($this->flowOverTimeRemindRepository)->insertMultipleData($data);
                                }
                            }
                            $flowOutsendObject = app($this->flowOutsendRepository)->getList($oldId)->toArray();
                            if (count($flowOutsendObject)) {
                                $data = [];
                                foreach ($flowOutsendObject as $v) {
                                    $data = [
                                        'node_id' => $id,
                                        'premise' => $v['premise'],
                                        'database_id' => $v['database_id'],
                                        'table_name' => $v['table_name'],
                                        'porcess_fields' => $v['porcess_fields'],
                                        'receive_fields' => $v['receive_fields'],
                                        'outsend_url' => $v['outsend_url'],
                                        'module' => $v['module'],
                                        'flow_outsend_mode' => $v['flow_outsend_mode'],
                                        'module_select' => $v['module_select'],
                                        'custom_module' => $v['custom_module'],
                                        'custom_module_menu' => $v['custom_module_menu'],
                                        'flow_outsend_timing' => $v['flow_outsend_timing'],
                                        'send_back_forbidden' => $v['send_back_forbidden'],
                                        'data_handle_type' => $v['data_handle_type'],
                                        'data_match_type' => $v['data_match_type']
                                    ];
                                    $flow_outsend_id = app($this->flowOutsendRepository)->insertGetId($data);

                                    $fields = app($this->flowOutsendFieldsRepository)->getList($v['id']);
                                    if ($fields) {
                                        $fields = $fields->toArray();
                                        $flow_outsend_data = [];
                                        foreach ($fields as $_value) {
                                            $flow_outsend_data[] = [
                                                'flow_outsend_id' => $flow_outsend_id,
                                                'porcess_fields' => $_value['porcess_fields'],
                                                'receive_fields' => $_value['receive_fields'],
                                                'porcess_fields_parent' => $_value['porcess_fields_parent'],
                                                'receive_fields_parent' => $_value['receive_fields_parent'],
                                                'additional_field' => $_value['additional_field'],
                                                'analysis_url' => $_value['analysis_url'],
                                            ];
                                        }
                                        if (!empty($flow_outsend_data)) {
                                            //插入匹配字段表数据
                                            $fields = app($this->flowOutsendFieldsRepository)->insertMultipleData($flow_outsend_data);
                                        }
                                    }
                                    // 更新和删除模式的依赖字段
                                    if ($v['data_handle_type']) {
                                        $dependentFields = app($this->flowOutsendDependentFieldsRepository)->getList($v['id'])->toArray();
                                        if ($dependentFields) {
                                            foreach ($dependentFields as $dependentField) {
                                                $dependentField['flow_outsend_id'] = $flow_outsend_id;
                                                $dependentField['created_at'] = date('Y-m-d H:i:s');
                                                unset($dependentField['id']);
                                                app($this->flowOutsendDependentFieldsRepository)->insertMultipleData([$dependentField]);
                                            }
                                        }
                                    }
                                }
                            }
                            // 自由节点信息复制
                            if ($freeProcessInfo = app($this->flowProcessFreeRepository)->getFlowNodeDetail($oldId)) {
                                $newFreeProcessInfoData = [
                                    'node_id' => $id,
                                    'circular_superior' => $freeProcessInfo->circular_superior,
                                    'circular_superior_type' => $freeProcessInfo->circular_superior_type,
                                    'circular_superior_degree' => $freeProcessInfo->circular_superior_degree,
                                    'entrust_get_superior_rule' => $freeProcessInfo->entrust_get_superior_rule,
                                    'circular_superior_user' => $freeProcessInfo->circular_superior_user,
                                    'circular_superior_role' => $freeProcessInfo->circular_superior_role,
                                    'circular_superior_dept' => $freeProcessInfo->circular_superior_dept,
                                    'quit_type' => $freeProcessInfo->quit_type,
                                    //'quit_condition' => $freeProcessInfo->quit_condition,
                                    //'can_back' => $freeProcessInfo->can_back,
                                    'back_type' => $freeProcessInfo->back_type,
                                    'back_to_type' => $freeProcessInfo->back_to_type,
                                    'can_set_required' => $freeProcessInfo->can_set_required,
                                    'set_required_type' => $freeProcessInfo->set_required_type,
                                    'required_control_id' => $freeProcessInfo->required_control_id,
                                    'run_type' => $freeProcessInfo->run_type,
                                    'preset_process' => $freeProcessInfo->preset_process
                                ];
                                app($this->flowProcessFreeRepository)->insertData($newFreeProcessInfoData);
                                $flowProcessFreePreset = $freeProcessInfo->flowProcessFreeHasManyPreset ?? [];
                                if ($flowProcessFreePreset) {
                                    $flowProcessFreePreset = $flowProcessFreePreset->toArray();
                                    $flowProcessFreePresetData = [];
                                    foreach ($flowProcessFreePreset as $key => $value) {
                                        $flowProcessFreePresetData[] = [
                                            'node_id' => $id,
                                            'node_name' => $value['node_name'],
                                            'handle_user' => $value['handle_user'],
                                            'required_control_id' => $value['required_control_id'],
                                        ];
                                    }
                                    app($this->flowProcessFreePresetRepository)->insertMultipleData($flowProcessFreePresetData);
                                }
                            }
                        }
                    }
                    // 处理process_to
                    // 此数组结构：new_process_id => old_process_id
                    $collectPorcessIdNewToOldArray = array_flip($collectPorcessIdOldToNewArray);
                    $data = [];
                    foreach ($newFlowProcessIdArray as $newIdKey => $newIdValue) {
                        // old_process_to
                        $oldProcessTo = $collectOldPorcessToArray[$collectPorcessIdNewToOldArray[$newIdValue]];
                        if ($oldProcessTo) {
                            // 替换
                            $oldProcessToArray = explode(",", $oldProcessTo);
                            // get new string
                            $newProcessIdString = "";
                            foreach ($oldProcessToArray as $key => $value) {
                                if (isset($collectPorcessIdOldToNewArray[$value])) {
                                    $newProcessIdString .= $collectPorcessIdOldToNewArray[$value] . ",";
                                }
                            }
                            $newProcessIdString = rtrim($newProcessIdString, ",");
                            // update
                            app($this->flowProcessRepository)->updateData(["process_to" => $newProcessIdString], ["node_id" => [$newIdValue]]);
                        }
                        //智能获取办理人替换旧节点id
                        $oldProcessAutoGet = isset($collectOldAutoGetArray[$collectPorcessIdNewToOldArray[$newIdValue]]) ? $collectOldAutoGetArray[$collectPorcessIdNewToOldArray[$newIdValue]] : '';
                        if ($oldProcessAutoGet) {
                            $newAutoGetString = '';
                            //判断智能获取方式是否为获取某个节点相关信息
                            $autoGetUser = explode('|', $oldProcessAutoGet);
                            if (isset($autoGetUser[0]) && isset($autoGetUser[1]) && $autoGetUser[0] == 1) {
                                $autoGetUser[1] = isset($collectPorcessIdOldToNewArray[$autoGetUser[1]]) ? $collectPorcessIdOldToNewArray[$autoGetUser[1]] : '';
                                $newAutoGetString = implode('|', $autoGetUser);
                                app($this->flowProcessRepository)->updateData(["process_auto_get_user" => $newAutoGetString], ["node_id" => [$newIdValue]]);
                            }
                        }
                        //智能获取抄送人替换旧节点id
                        $oldProcessAutoGetCopy = isset($collectOldAutoGetCopyArray[$collectPorcessIdNewToOldArray[$newIdValue]]) ? $collectOldAutoGetCopyArray[$collectPorcessIdNewToOldArray[$newIdValue]] : '';
                        if ($oldProcessAutoGetCopy) {
                            $newAutoGetCopyString = '';
                            //判断智能获取方式是否为获取某个节点相关信息
                            $autoGetCopyUser = explode('|', $oldProcessAutoGetCopy);
                            if (isset($autoGetCopyUser[0]) && isset($autoGetCopyUser[1]) && $autoGetCopyUser[0] == 1) {
                                $autoGetCopyUser[1] = isset($collectPorcessIdOldToNewArray[$autoGetCopyUser[1]]) ? $collectPorcessIdOldToNewArray[$autoGetCopyUser[1]] : '';
                                $newAutoGetCopyString = implode('|', $autoGetCopyUser);
                                app($this->flowProcessRepository)->updateData(["process_auto_get_copy_user" => $newAutoGetCopyString], ["node_id" => [$newIdValue]]);
                            }
                        }
                    }

                    // 出口条件数据
                    if ($flowTermObject) {
                        foreach ($flowTermObject as $termKey => $termValue) {
                            // $processKey    = array_search($termValue->id,$processObject->pluck("node_id")->toArray());
                            // $currentTermId = $newFlowProcessIdArray[$processKey];
                            $termInsertData = [
                                "flow_id" => $flowId,
                                'source_id' => $collectPorcessIdOldToNewArray[$termValue["source_id"]],
                                'target_id' => $collectPorcessIdOldToNewArray[$termValue["target_id"]],
                                'condition' => $termValue["condition"],
                            ];
                            app($this->flowTermRepository)->insertData($termInsertData);
                        }
                    }
                    // 重新更新origin_node 和merge_node
                    app($this->flowParseService)->resetbranchInfo($flowId);
                }
                //自由流程超时提醒数据
                if ($flowTypeArray['flow_type'] == 2) {
                    //超时提醒
                    if ($flowOverTimeRemindObject = app($this->flowOverTimeRemindRepository)->getFlowList($flowTemplateId)) {
                        if ($flowOverTimeRemindObject->count()) {
                            $data = [];
                            foreach ($flowOverTimeRemindObject->toArray() as $v) {
                                $data[] = [
                                    'flow_id' => $flowId,
                                    'remind_time' => $v['remind_time'],
                                    'overtime_ways' => $v['overtime_ways'],
                                ];
                            }
                            app($this->flowOverTimeRemindRepository)->insertMultipleData($data);
                        }
                    }
                }
                // others数据
                if ($flowOthersObject) {
                    if ($flowOthersObject->flow_end_remind == 4) {
                        $newString = '';
                        foreach (explode(',', $flowOthersObject->appoint_process) as $value) {
                            if (!empty($collectPorcessIdOldToNewArray[$value])) {
                                $newString .= ($newString ? ',' : '') . $collectPorcessIdOldToNewArray[$value];
                            }
                        }
                        $flowOthersObject->appoint_process = $newString;
                    }
                    if (!empty($flowOthersObject->flow_detail_page_choice_other_tabs)) {
                        $flowOthersObject->flow_detail_page_choice_other_tabs = str_replace([',qyssign', 'qyssign'], '', $flowOthersObject->flow_detail_page_choice_other_tabs);
                    }
                    $flowOthersInsertData = [
                        "flow_id" => $flowId,
                        "flow_show_graph" => $flowOthersObject->flow_show_graph,
                        "flow_show_text" => $flowOthersObject->flow_show_text,
                        "flow_show_step" => $flowOthersObject->flow_show_step,
                        "flow_show_attach" => $flowOthersObject->flow_show_attach,
                        "flow_show_feedback" => $flowOthersObject->flow_show_feedback,
                        "flow_autosave" => $flowOthersObject->flow_autosave,
                        "flow_autosave_time" => $flowOthersObject->flow_autosave_time,
                        "flow_to_doc" => $flowOthersObject->flow_to_doc,
                        // "lable_show_default" => $flowOthersObject->lable_show_default,
                        "feed_back_after_flow_end" => $flowOthersObject->feed_back_after_flow_end,
                        "file_folder_id" => $flowOthersObject->file_folder_id,
                        "submit_without_dialog" => $flowOthersObject->submit_without_dialog,
                        "first_node_delete_flow" => $flowOthersObject->first_node_delete_flow,
                        "flow_send_back_required" => $flowOthersObject->flow_send_back_required,
                        "flow_send_back_submit_method" => $flowOthersObject->flow_send_back_submit_method,
                        "alow_select_handle" => $flowOthersObject->alow_select_handle,
                        "flow_print_template_toggle" => $flowOthersObject->flow_print_template_toggle,
                        "flow_filing_template_toggle" => $flowOthersObject->flow_filing_template_toggle,
                        "flow_submit_hand_remind_toggle" => $flowOthersObject->flow_submit_hand_remind_toggle,
                        "flow_detail_page_choice_other_tabs" => $flowOthersObject->flow_detail_page_choice_other_tabs,
                        "flow_end_remind" => $flowOthersObject->flow_end_remind,
                        "remind_target" => $flowOthersObject->remind_target,
                        "appoint_process" => $flowOthersObject->appoint_process,
                        "flow_show_history" => $flowOthersObject->flow_show_history,
                        "continuous_submission" => $flowOthersObject->continuous_submission,
                        "without_back" => $flowOthersObject->without_back,
                        "without_required" => $flowOthersObject->without_required,
                        "inheritance_sign" => $flowOthersObject->inheritance_sign,
                        "flow_filing_conditions_setting_toggle" => $flowOthersObject->flow_filing_conditions_setting_toggle,
                        "flow_filing_conditions_setting_value" => $flowOthersObject->flow_filing_conditions_setting_value,
                        "flow_filing_folder_rules_toggle" => $flowOthersObject->flow_filing_folder_rules_toggle,
                        "flow_filing_folder_rules" => $flowOthersObject->flow_filing_folder_rules,
                        "flow_filing_folder_rules_html" => $flowOthersObject->flow_filing_folder_rules_html,
                        "flow_show_data_template" => $flowOthersObject->flow_show_data_template,
                        "flow_show_user_template" => $flowOthersObject->flow_show_user_template,
                        "flow_filing_document_create" => $flowOthersObject->flow_filing_document_create,
                        "flow_filing_conditions_verify_mode" => $flowOthersObject->flow_filing_conditions_verify_mode,
                        "flow_filing_conditions_verify_url" => $flowOthersObject->flow_filing_conditions_verify_url,
                        "forward_after_flow_end"  =>$flowOthersObject->forward_after_flow_end,
                        "flow_submit_hand_overtime_toggle" =>$flowOthersObject->flow_submit_hand_overtime_toggle,
                        "flow_send_back_verify_condition" =>$flowOthersObject->flow_send_back_verify_condition,
                        "trigger_schedule" => $flowOthersObject->trigger_schedule, // 流程定时触发
                        "flow_print_times_limit" => $flowOthersObject->flow_print_times_limit, // 打印次数限制
                        "no_print_until_flow_end" => $flowOthersObject->no_print_until_flow_end, // 是否限制流程结束才能打印
                    ];
                    if (!empty($flowTypeArray['flow_type']) && $flowTypeArray['flow_type'] == '1') {
                        // 如果从模板新建固定流程，复制此选项
                        $flowOthersInsertData['form_control_filter'] = $flowOthersObject->form_control_filter;
                    }
                    // 20200513,zyx,如果有定时触发配置则同时导入
                    if ($flowOthersObject->trigger_schedule) {
                        // 先获取模块流程的定时触发配置
                        $templateFlowScheduleConfigs = app($this->flowSettingService)->getFlowSchedules($flowTemplateId);
                        // 插入新流程的定时触发配置
                        app($this->flowSettingService)->editFlowSchedules(['flow_id' => $flowId, 'trigger_schedule' => 1, 'schedule_configs' => $templateFlowScheduleConfigs->toArray()], $own);
                    }
                } else {
                    $flowOthersInsertData = [
                        "flow_id" => $flowId,
                        "flow_show_text" => "1",
                        // "lable_show_default" => "1",
                        "file_folder_id" => "0",
                    ];
                }
                app($this->flowOthersRepository)->insertData($flowOthersInsertData);
                app($this->flowParseService)->copyFormDataTemplate($flowTemplateId, $flowId, $own);
                // 表单模板规则，包括节点规则、归档规则、打印规则
                $getParam = [
                    "returntype" => "array",
                    "search" => ["flow_id" => [$flowTemplateId]],
                ];
                $templateRuleList = app($this->flowFormTemplateRuleRepository)->getFlowFormTemplateRule($getParam);
                if (!empty($templateRuleList)) {
                    foreach ($templateRuleList as $key => $value) {
                        // 插入规则
                        $nodeId = (isset($value["node_id"]) && $value["node_id"] > 0 && isset($collectPorcessIdOldToNewArray[$value["node_id"]])) ? $collectPorcessIdOldToNewArray[$value["node_id"]] : 0;
                        $newRuleInfo = [
                            "node_id" => $nodeId,
                            "flow_id" => $flowId,
                            "template_id" => $value["template_id"],
                            "template_type" => $value["template_type"],
                            "other_rule_flag" => $value["other_rule_flag"],
                            "other_person_template_toggle" => $value["other_person_template_toggle"],
                            "rule_order" => $value["rule_order"],
                            "rule_department" => $value["rule_department"],
                            "rule_role" => $value["rule_role"],
                            "rule_user" => $value["rule_user"],
                        ];
                        $ruleObject = app($this->flowFormTemplateRuleRepository)->insertData($newRuleInfo);
                        $newRuleId = $ruleObject->rule_id;
                        // 插入关联的部门 角色 人员
                        $userId = (isset($value["rule_list_has_many_user"]) && !empty($value["rule_list_has_many_user"])) ? collect($value["rule_list_has_many_user"])->pluck("user_id")->toArray() : [];
                        $roleId = (isset($value["rule_list_has_many_role"]) && !empty($value["rule_list_has_many_role"])) ? collect($value["rule_list_has_many_role"])->pluck("role_id")->toArray() : [];
                        $deptId = (isset($value["rule_list_has_many_dept"]) && !empty($value["rule_list_has_many_dept"])) ? collect($value["rule_list_has_many_dept"])->pluck("dept_id")->toArray() : [];
                        if (!empty($userId)) {
                            $userData = [];
                            foreach (array_filter($userId) as $v) {
                                $userData[] = ['rule_id' => $newRuleId, 'user_id' => $v];
                            }
                            app($this->flowFormTemplateRuleUserRepository)->insertMultipleData($userData);
                        }
                        if (!empty($roleId)) {
                            $roleData = [];
                            foreach (array_filter($roleId) as $v) {
                                $roleData[] = ['rule_id' => $newRuleId, 'role_id' => $v];
                            }
                            app($this->flowFormTemplateRuleRoleRepository)->insertMultipleData($roleData);
                        }
                        if (!empty($deptId)) {
                            $deptData = [];
                            foreach (array_filter($deptId) as $v) {
                                $deptData[] = ['rule_id' => $newRuleId, 'dept_id' => $v];
                            }
                            app($this->flowFormTemplateRuleDepartmentRepository)->insertMultipleData($deptData);
                        }
                    }
                }
                // FlowReport 数据；暂时不知道啥用处。没做。
                return $flowId;
            }
        }
    }

    /**
     * 更改流程是否有效字段的值
     *
     * @method flowDefineCopy
     *
     * @param array $flowParamArray 流程相关参数，is_effect=0：失效，is_effect=1：生效
     * @param array $updateWhereArray 需要的update限制条件
     * @param array $setTableArray 操作哪几个表，默认操作 flow_run/flow_run_process/flow_run_step
     *
     * @return str true/false  操作是否成功
     */
    public function updateFlowIsEffect($flowParamArray, $updateWhereArray = '', $setTableArray = '')
    {
        if (!$setTableArray) {
            $setTableArray = array("flow_run", "flow_run_process", "flow_run_step");
        }
        if ($flowParamArray["is_effect"]) {
            $is_effect = $flowParamArray["is_effect"];
        } else {
            $is_effect = "0";
        }

        $updateFlowRunDate = ["run_id" => [$flowParamArray["run_id"]]];
        $updateFlowRunProcessDate = ["run_id" => [$flowParamArray["run_id"]]];
        $updateFlowRunStepDate = ["run_id" => [$flowParamArray["run_id"]]];
        foreach ($setTableArray as $key => $value) {
            if ($value == "flow_run") {
                if ($updateWhereArray == "true") {
                    $updateFlowRunDate["is_effect"] = ["2"];
                }
                app($this->flowRunRepository)->updateData(["is_effect" => $is_effect], $updateFlowRunDate);
            }
            if ($value == "flow_run_process") {
                if ($updateWhereArray == "true") {
                    $updateFlowRunProcessDate["is_effect"] = ["2"];
                }
                app($this->flowRunProcessRepository)->updateData(["is_effect" => $is_effect], $updateFlowRunProcessDate);
            }
            if ($value == "flow_run_step") {
                if ($updateWhereArray == "true") {
                    $updateFlowRunStepDate["is_effect"] = ["2"];
                }
                app($this->flowRunStepRepository)->updateData(["is_effect" => $is_effect], $updateFlowRunStepDate);
            }
        }
        return "1";
    }

    /**
     * 翻译出口条件的关联关系
     *
     * @method getOutConditionRelationString
     *
     * @param  [type]                        $n [description]
     *
     * @return [type]                           [description]
     */
    public function getOutConditionRelationString($n)
    {
        switch ($n) {
            case 1:
                // 等于
                $str = trans("flow.0x030035");
                break;
            case 2:
                // 大于
                $str = trans("flow.0x030036");
                break;
            case 3:
                // 小于
                $str = trans("flow.0x030037");
                break;
            case 4:
                // 大于等于
                $str = trans("flow.0x030038");
                break;
            case 5:
                // 小于等于
                $str = trans("flow.0x030039");
                break;
            case 6:
                // 不等于
                $str = trans("flow.0x030040");
                break;
            case 11:
                // 字符等于
                $str = trans("flow.0x030041");
                break;
            case 12:
                // 开始字符
                $str = trans("flow.0x030042");
                break;
            case 13:
                // 包含字符
                $str = trans("flow.0x030043");
                break;
            case 14:
                // 结束字符
                $str = trans("flow.0x030044");
                break;
            case 15:
                // 不包括字符
                $str = trans("flow.0x030045");
                break;
            case 16:
                // 包含于
                $str = trans("flow.0x030046");
                break;
            case 17:
                // 字符不等于
                $str = trans("flow.0x030047");
                break;
        }
        return $str;
    }

    /**
     * 流程表单数据获取函数，不单独使用
     * 注意：此函数，也用于流程表单明细字段数据保存，传不同的form_id就行了，表结构几乎一样。
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowRunFormData($param)
    {
        $formId = isset($param["form_id"]) ? $param["form_id"] : false;
        $runId = isset($param["run_id"]) ? $param["run_id"] : false;
        $fields = isset($param["fields"]) ? $param["fields"] : false;
        $dataId = isset($param["data_id"]) ? $param["data_id"] : false;
        if ($formId && $runId) {
            $tableName = "zzzz_flow_data_" . $formId;
            if (Schema::hasTable($tableName)) {
                $flowRunFormData = DB::table($tableName);
                $tableColumnList = Schema::getColumnListing($tableName);
                if (count($fields)) {
                    $selectFields = array_intersect($tableColumnList, $fields);
                    if (!empty($selectFields)) {
                        $flowRunFormData = $flowRunFormData->select($selectFields);
                    } else {
                        return [];
                    }
                }
                $flowRunFormData = $flowRunFormData->where(["run_id" => $runId]);
                if($dataId) {
                    $flowRunFormData = $flowRunFormData->where(["data_id" => $dataId]);
                }
                $hasAmount = false;
                if (!empty($tableColumnList) && is_array($tableColumnList) && in_array('amount', $tableColumnList)) {
                    // if (Schema::hasColumn($tableName, "amount")) {
                    $hasAmount = true;
                }
                if ($hasAmount && isset($param['hasAmount']) && $param['hasAmount'] == 'no') {
                    $flowRunFormData = $flowRunFormData->where(['amount' => '']);
                }
                if (strpos($formId, "_") !== false) {
                    $flowRunFormData = $flowRunFormData->orderBy("sort_id");
                }
                $flowRunFormData = $flowRunFormData->get();
                if (count($flowRunFormData)) {
                    $flowRunFormData = json_decode(json_encode($flowRunFormData), true);
                    return $flowRunFormData;
                }
            }
        }
        return [];
    }

    /**
     * 保存表单信息的一级函数，在页面的保存按钮路由事件里调用
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function saveFlowRunFormData($param, $userInfo = [])
    {
        // 数据结构
        // $data["form_data"] = [
        //     "DATA_1"=> "1",
        //     "DATA_2"=> "2",
        //     "DATA_3"=> "3",
        //     "DATA_4"=> [
        //         [
        //             "DATA_4_1"=> "xxxx_1_1",
        //             "DATA_4_2"=> [
        //                  ['DATA_4_2_1'=>1,'DATA_4_2_2'=>2],
        //                  ['DATA_4_2_1'=>111,'DATA_4_2_2'=>222]
        //             ]
        //         ],[
        //             "DATA_4_1"=> "xxxx_2_1",
        //             "DATA_4_2"=> "xxxx_2_2"
        //         ],[
        //             "DATA_4_1"=> "xxxx_3_1",
        //             "DATA_4_2"=> "xxxx_3_2"
        //         ]
        //     ],
        //     "DATA_5"=> "系统管理员",
        //     "DATA_6"=> "value_test_6",
        //     "DATA_7"=> "value_test_7",
        //     "DATA_8"=> [
        //          "复选1","复选2"
        //     ],
        //     "DATA_9"=> [
        //         [
        //             "DATA_9_4"=> "asdasdA_9_4"
        //         ]
        //     ]
        // ];

        $runId = $param["run_id"];
        $formData = isset($param['form_data']) ? $param['form_data'] : '';
        if (!empty($formData)) {
            // $formId = $this->getFormIdByRunId($runId);
            $flowId = $this->getFlowIdByRunId($runId);
            $flowInfo = app($this->flowTypeRepository)->getDetail($flowId , false , ['form_id' ,'flow_type' , 'handle_way']);
            $formId =  $flowInfo->form_id;
            if (!$formId) {
                // 表单不存在
                return ['code' => ['0x030031', 'flow']];
            }
            // 新建的时候，先在zzzz主表插一条数据！
            if (isset($param["save_type"]) && $param["save_type"] == "create_flow") {
                $tableName = "zzzz_flow_data_" . $formId;
                if (DB::table($tableName)->where(["run_id" => $runId])->count() < 1) {
                    DB::table($tableName)->insertGetId(["run_id" => $runId]);
                }
            }
            // 获取字段控制信息
            $nodeId = $param['flow_process'];

            $flowType = $flowInfo->flow_type;
            $processId = $param['process_id'] ?? '';
            // 判断是否为主办人
            if ($flowType == 1) {
                $flowProcessInfo = app($this->flowProcessRepository)->getDetail($nodeId , false , ['process_transact_type']);
                $flowHandleWay = $flowProcessInfo->process_transact_type ?? 0;
            } else {
                $flowHandleWay = $flowInfo->handle_way ?? 0;
            }
            $checkHostFlag = 0;
            if ($flowHandleWay == '3') {
                // 如果是第四种办理方式，就当主办人处理
                $checkHostFlag = 1;
            } else {
                // 非第四种办理方式，查询是否是主办人
                if (!empty($userInfo['user_id'])) {
                    $searchHostFlagParams = [
                        'returntype' => 'count',
                        'search' => [
                            'user_id' => [$userInfo['user_id']],
                            'host_flag' => [1],
                            'process_id' => [$processId],
                            'run_id' => [$runId]
                        ]
                    ];
                    $checkHostFlag = app($this->flowRunProcessRepository)->getFlowRunProcessList($searchHostFlagParams);
                }
            }

            if ($flowType == 1 && $checkHostFlag) {
                $editInfo = app($this->flowProcessControlOperationDetailRepository)->getEditControls($nodeId);
                $updateInfo = app($this->flowProcessControlOperationDetailRepository)->getNotUpdateControls($nodeId);
                if ($editInfo) {
                    $editInfo = $editInfo->pluck('control_id')->toArray();
                } else {
                    $editInfo = [];
                }
                if ($updateInfo) {
                    $updateInfo = $updateInfo->pluck('control_id')->toArray();
                } else {
                    $updateInfo = [];
                }
                // 追加自由节点必填设置字段
                // 获取当前自由节点必填设置
                $freeProcessRunInfos = $this->getFreeNodeStepList($runId, $nodeId);
                if ($freeProcessRunInfos) {
                    foreach ($freeProcessRunInfos as $freeProcessRunInfo) {
                        $requiredInfo = $freeProcessRunInfo->required_control_id;
                        foreach (explode(',', $requiredInfo) as  $value) {
                            array_push($editInfo, $value);
                        }
                    }
                }
                if (!empty($editInfo)) {
                    foreach ($editInfo as $key => $value) {
                        array_push($editInfo, $value . '_TEXT');
                    }
                }
            } else {
                $editInfo = [];
                $updateInfo = [];
            }
            $name = 'flow_form_detail_layout_change_' . date('Ymd');
            // 获取表单控件结构
            $ruleId = isset($param['formTemplateRuleInfo']['run']) ? $param['formTemplateRuleInfo']['run'] : "";
            if ($ruleId) {
                $ruleInfo = app($this->flowService)->getFlowFormTemplateRuleInfo(["rule_id" => $ruleId]);
                // 这里取structure返回
                $formControlStructure = $ruleInfo["structure"] ?? [];
            } else {
                $formControlStructure = app($this->flowFormService)->getFlowFormControlStructure(['search' => ["form_id" => [$formId]]]);
            }
            if (count($formControlStructure) && $formId && count($formData)) {
                $controlStructureInfo = [];
                $newFormControlStructure = array_column($formControlStructure, null , 'control_id');
                foreach ($formControlStructure as $controlKey => $controlValue) {
                    $layoutControlChildInfo = [];
                    $layoutControlIsAmount = "";
                    $controlStructureId = $controlValue["control_id"];
                    $controlParentId = $controlValue["control_parent_id"];
                    $controlType = isset($controlValue["control_type"]) ? $controlValue["control_type"] : "";
                    $dataEfbWithText = "";
                    $efbSourceValue = "";
                    if ($controlValue["control_attribute"]) {
                        $controlAttribute = json_decode($controlValue["control_attribute"], true);
                        $dataEfbWithText = $controlAttribute["data-efb-with-text"] ?? "";
                        $efbSource = $controlAttribute["data-efb-source"] ?? "";
                        $dataEfbDefault = $controlAttribute["data-efb-default"] ?? "";
                        $dataEfbSelectedOptions = $controlAttribute["data-efb-selected-options"] ?? "";
                        // 自由流程
                        // 主办人全部更新 经办人只更新会签
                        if ($flowType == '2') {
                            if (!empty($checkHostFlag)) {
                                array_push($editInfo, $controlStructureId);
                                array_push($editInfo, $controlStructureId . '_TEXT');
                            } else {
                                if (in_array($controlType, ['countersign', 'signature-picture', 'electronic-signature'])) {
                                    array_push($editInfo, $controlStructureId);
                                }
                            }
                            // 固定流程
                            // 主办人更新可编辑字段以及系统数据控件 经办人只更新会签
                        } else {
                            if (!empty($checkHostFlag)) {
                                if (!in_array($controlStructureId, $updateInfo) && ($controlType == "data-selector" || ($efbSource != '' && $controlType != "select" && $controlType != "detail-layout"))) {
                                    array_push($editInfo, $controlStructureId);
                                    array_push($editInfo, $controlStructureId . '_TEXT');
                                }
                                // 如果是明细控件，需要判断其父控件是否有efbSource是来自文件属性
                                if ( $controlParentId && isset($newFormControlStructure[$controlParentId]["control_attribute"])) {
                                    $parentControlAttribute = $newFormControlStructure[$controlParentId]["control_attribute"];
                                    $parentControlAttribute = json_decode($parentControlAttribute, true);
                                    $parentEfbSource = isset($parentControlAttribute["data-efb-source"]) ? $parentControlAttribute["data-efb-source"] : "";
                                    if ($parentEfbSource == 'file') {
                                        array_push($editInfo, $controlStructureId);
                                        array_push($editInfo, $controlStructureId . '_TEXT');
                                    }
                                }
                            } else {
                                if (in_array($controlType, ['countersign', 'signature-picture', 'electronic-signature'])) {
                                    array_push($editInfo, $controlStructureId);
                                }
                            }
                        }
                        if ($controlType == "text" && $efbSource == "currentData") {
                            $efbSourceValue = isset($controlAttribute["data-efb-source-value"]) ? $controlAttribute["data-efb-source-value"] : "";
                            if ($efbSourceValue != "flow_serialNumber") {
                                $efbSourceValue = "";
                            }
                        }
                        // 如果是明细控件，判断此明细控件里，是否有合计的列
                        if ($controlType == "detail-layout") {
                            if (isset($controlAttribute["data-efb-layout-info"]) && $controlAttribute["data-efb-layout-info"]) {
                                $dataEfbLayoutInfo = $controlAttribute["data-efb-layout-info"];
                                if (!is_array($dataEfbLayoutInfo)) {
                                    $dataEfbLayoutInfo = json_decode($dataEfbLayoutInfo, true);
                                }
                                if (count($dataEfbLayoutInfo)) {
                                    foreach ($dataEfbLayoutInfo as $dataEfbLayoutInfoKey => $dataEfbLayoutInfoValue) {
                                        $layoutItemRowIsAmount = $dataEfbLayoutInfoValue["isAmount"] ?? "";
                                        // 在导出字段里面的控件，才会被导出
                                        $layoutControlChildInfo[$dataEfbLayoutInfoValue["id"]] = [
                                            "title" => $dataEfbLayoutInfoValue["title"],
                                            "type" => $dataEfbLayoutInfoValue["type"],
                                            "isAmount" => $layoutItemRowIsAmount,
                                            'data-efb-source-value' => $dataEfbLayoutInfoValue['data-efb-source-value'] ?? '',
                                            'data-efb-hide' => $dataEfbLayoutInfoValue['data-efb-hide'] ?? false
                                        ];
                                        // 明细二级子项
                                        if (
                                            ($dataEfbLayoutInfoValue["type"] == 'column') &&
                                            count($dataEfbLayoutInfoValue['children'])
                                        ) {
                                            foreach ($dataEfbLayoutInfoValue['children'] as $grandChildValue) {
                                                $layoutControlChildInfo[$grandChildValue['id']] = [
                                                    "title" => $grandChildValue['title'],
                                                    "type" => $grandChildValue["type"],
                                                    "isAmount" => $grandChildValue["isAmount"] ?? "",
                                                    'data-efb-source-value' => $grandChildValue['data-efb-source-value'] ?? '',
                                                    'data-efb-hide' => $grandChildValue['data-efb-hide'] ?? false
                                                ];
                                            }
                                        }
                                        if ($layoutItemRowIsAmount) {
                                            $layoutControlIsAmount = "1";
                                        }
                                    }
                                }
                            }

                        }else {
                            if (isset($controlAttribute['isAmount']) && $controlAttribute['isAmount']) {
                                $layoutControlIsAmount = 1;
                            }
                        }
                    }
                    $controlStructureInfo[$controlStructureId] = [
                        "control_id" => $controlValue["control_id"],
                        "control_parent_id" => $controlValue["control_parent_id"],
                        "control_type" => $controlValue["control_type"],
                        "data-efb-with-text" => $dataEfbWithText,
                        "data-efb-source-value" => $efbSourceValue,
                        "children" => $layoutControlChildInfo,
                        "isAmount" => $layoutControlIsAmount,
                        "data-efb-hide" => $controlAttribute['data-efb-hide'] ?? false,
                        'isCard' => (isset($controlAttribute['data-efb-format']) && $controlAttribute['data-efb-format']=="bankCardNumber") ? true : false,
                    ];
                };
                // 日志写入操作太耗时，暂时注释，按需单独开发，勿删勿删勿删
                if (envOverload("FLOW_GET_DATA_TOGGLE") === 'true') {
                    app($this->flowLogService)->addFlowDataLogs($name . '.txt', "[" . date("Y-m-d H:i:s", time()) . "]can_deit_control_id\r\n" . json_encode($editInfo).$checkHostFlag . "\r\n");
                }
                // 收集普通控件的控件值
                $updateFormData = [];
                // 收集【会签字段控件】的id，单独保存
                $countersignControlIdArray = [];
                $getColumnFlag = [];
                foreach ($formData as $controlId => $value) {
                    if (isset($formData[$controlId . '_TEXT']) && $formData[$controlId . '_TEXT'] === "") {
                        // 如果_TEXT的值已经从数据源被删除了，那就清除ID的值
                        $value = "";
                    }
                    // 属性不存在说明控件不存在，不处理其值
                    if (isset($controlStructureInfo[$controlId]) || isset($controlStructureInfo[str_replace("_TEXT", "", $controlId)])) {
                        $controlAttribute = $controlStructureInfo[$controlId] ?? [];
                    } else {
                        continue;
                    }
                    $zzzzTableName = "zzzz_flow_data_" . $formId;
                    // 如果，流水号类型的宏控件，且form_data里没值，且是在新建调用此函数的情况下，且传了流水号,那么，将此流水号的值保存进去
                    if (isset($param["run_seq"]) && $param["run_seq"] !== '' && isset($controlAttribute["data-efb-source-value"]) && $controlAttribute["data-efb-source-value"] == "flow_serialNumber" && isset($param["save_type"]) && $param["save_type"] == "create_flow") {
                        $value = $this->createFlowSaveControlRunSeqData($value, $param["run_seq"], $controlId, $nodeId);
                    }

                    $controlType = $controlAttribute["control_type"] ?? "";
                    // 1、保留 DATA_n 这种格式的数据 2、保留 DATA_n_TEXT 这种格式的。这两种格式的数据才能作为字段进表
                    $controlIdExplode = explode("_", $controlId);
                    if (count($controlIdExplode) == 2 || (count($controlIdExplode) == 3 && $controlIdExplode["2"] == "TEXT" && isset($controlStructureInfo[str_replace("_TEXT", "", $controlId)]) && $controlStructureInfo[str_replace("_TEXT", "", $controlId)]["data-efb-with-text"])) {
                        // 明细字段
                        if ($controlType == "detail-layout") {
                            $layoutDataTableName = "zzzz_flow_data_" . $formId . str_replace("DATA", "", $controlId);
                            if (is_array($value) && count($value)) {
                                $controlAttributeChild = $controlAttribute["children"] ?? [];
                                // 获取明细表字段列表
                                if (isset($getColumnFlag[$layoutDataTableName])) {
                                    $layoutDataTableColumnList = $getColumnFlag[$layoutDataTableName];
                                } else {
                                    $layoutDataTableColumnList = Schema::getColumnListing($layoutDataTableName);
                                    $getColumnFlag[$layoutDataTableName] = $layoutDataTableColumnList;
                                }
                                $newIds = [];
                                $oldIds = [];
                                // 记录一级明细下的二级明细表名
                                $scoundTables = [];
                                $oldData = DB::table($layoutDataTableName)->where(["run_id" => $runId])->get();
                                if ($oldData) {
                                    $oldData = $oldData->toArray();
                                    foreach ($oldData as $oldInfo) {
                                        if (!isset($oldInfo->amount) || $oldInfo->amount != 'amount') {
                                            $oldIds[] = $oldInfo->id;
                                        }
                                    }
                                }
                                // 循环明细字段的行
                                foreach ($value as $key => $itemInfo) {
                                    $updateFormDetailLayoutData = [];
                                    $itemInfoValue = [];
                                    if (is_array($itemInfo) && count($itemInfo)) {
                                        // 循环明细的列
                                        foreach ($itemInfo as $infoKey => $infoValue) {
                                            // zzzz表字段处理
                                            if (!empty($layoutDataTableColumnList) && is_array($layoutDataTableColumnList) && !in_array($infoKey, $layoutDataTableColumnList)) {
                                                if (!Schema::hasColumn($layoutDataTableName, $infoKey)) {
                                                    Schema::table($layoutDataTableName, function ($table) use ($infoKey) {
                                                        $table = $table->text($infoKey)->nullable();
                                                    });
                                                }
                                            }

                                            $itemAttributeInfo = $controlAttributeChild[$infoKey] ?? [];
                                            $itemControlType = $itemAttributeInfo["type"] ?? "";
                                            // 判断$infoKey的合法性
                                            $infoKeyExplode = explode("_", $infoKey);
                                            if ($infoKey == 'id' || ((count($infoKeyExplode) == 3 && $controlId == $infoKeyExplode["0"] . "_" . $infoKeyExplode["1"]) || (count($infoKeyExplode) == 4 && $controlId == $infoKeyExplode["0"] . "_" . $infoKeyExplode["1"] && $infoKeyExplode["3"] == "TEXT" && isset($controlStructureInfo[str_replace("_TEXT", "", $infoKey)]) && $controlStructureInfo[str_replace("_TEXT", "", $infoKey)]["data-efb-with-text"]))) {
                                                // 格式化银行卡
                                                if (isset($controlStructureInfo[$infoKey]["isCard"]) && $controlStructureInfo[$infoKey]["isCard"]) {
                                                    $infoValue = $this->filterCardDatas($infoValue);
                                                }
                                                // 收集【会签字段控件】的id，用于后面的保存
                                                if ($itemControlType == "countersign") {
                                                    $countersignControlIdArray[$controlId][] = ["control_id" => $infoKey, "control_value" => $infoValue];
                                                    $infoValue = "";
                                                }
                                                if ($infoKey == 'id') {
                                                    if ($infoValue) {
                                                        $itemInfoValue['id'] = $infoValue;
                                                    }
                                                }else {
                                                    if (is_array($infoValue) && $itemControlType != 'detail-layout') {
                                                        $infoValue = implode(",", $infoValue);
                                                    }else {
                                                        if (is_array($infoValue) && $itemControlType == 'detail-layout') {
                                                            $itemInfoValue[$infoKey] = $infoValue;
                                                            $infoValue = 'DETAIL-LAYOUT';
                                                        }
                                                    }
                                                }
                                                // 判断字段权限$editInfo
                                                if(!empty($itemAttributeInfo['data-efb-hide']) || in_array($infoKey, $editInfo) || $infoKey == 'id' || (isset($param["save_type"]) && $param["save_type"] == "create_flow")) {
                                                    if (isset($param["run_seq"]) && $param["run_seq"] !== '' && isset($itemAttributeInfo["data-efb-source-value"]) && $itemAttributeInfo["data-efb-source-value"] == "flow_serialNumber") {
                                                        $updateFormDetailLayoutData[$infoKey] = $this->createFlowSaveControlRunSeqData($infoValue, $param["run_seq"], $infoKey, $nodeId);
                                                    } else {
                                                        if ($infoKey == 'id') {
                                                            if ($infoValue) {
                                                                $updateFormDetailLayoutData[$infoKey] = $infoValue;
                                                            }
                                                        }else {
                                                            $updateFormDetailLayoutData[$infoKey] = $infoValue;
                                                        }

                                                    }
                                                    if (isset($updateFormDetailLayoutData['id']) && $updateFormDetailLayoutData['id']) {
                                                        $newIds[] = $infoValue;
                                                    }
                                                }
                                            }
                                        }
                                        if(count($updateFormDetailLayoutData)) {
                                            $updateFormDetailLayoutData['sort_id'] = $key + 1;
                                        }
                                        $flowRunFormDetailLayoutDataParams = ["run_id" => $runId, "form_id" => $formId . str_replace("DATA", "", $controlId), "form_data" => $updateFormDetailLayoutData];
                                        if (!empty($checkHostFlag) || (isset($param["save_type"]) && $param["save_type"] == "create_flow")) {
                                            // 日志写入操作太耗时，暂时注释，按需单独开发，勿删勿删勿删
                                            if (envOverload("FLOW_GET_DATA_TOGGLE") === 'true') {
                                                app($this->flowLogService)->addFlowDataLogs($name . '.txt', "[" . date("Y-m-d H:i:s", time()) . "]update_detail_layout_data\r\n" . json_encode($flowRunFormDetailLayoutDataParams) . "\r\n");
                                            }
                                            // 插入新数据
                                            $flowRunFormDetailLayoutData = $this->formDataPerformSave($flowRunFormDetailLayoutDataParams, "detail-layout");


                                            if ($itemInfoValue && $flowRunFormDetailLayoutData) {
                                                foreach ($itemInfoValue as $secondControlId => $secondControlValue) {
                                                    if ($secondControlId != 'id') {
                                                        // 二级明细表名
                                                        $secondTableName = 'zzzz_flow_data_'.$formId.str_replace("DATA", "", $secondControlId);
                                                        // 删除二级明细数据
                                                        DB::table($secondTableName)->where(['run_id'=> $runId,'data_id'=> $flowRunFormDetailLayoutData])->delete();
                                                    }
                                                }
                                                foreach ($itemInfoValue as $secondControlId => $secondControlValue) {
                                                    if ($secondControlId != 'id') {
                                                        // 二级明细表名
                                                        $secondTableName = 'zzzz_flow_data_'.$formId.str_replace("DATA", "", $secondControlId);
                                                        if (!in_array($secondTableName, $scoundTables)) {
                                                            $scoundTables[] = $secondTableName;
                                                        }
                                                        $secondFormDataInfo = [];
                                                        $amountData = [];
                                                        if ($secondControlValue) {
                                                            foreach ($secondControlValue as $_k=> $_secondControlValue) {
                                                                if ($_secondControlValue) {
                                                                    unset($_secondControlValue['data_id']);
                                                                    foreach ($_secondControlValue as $_skey => $_svalue) {
                                                                        // 格式化银行卡
                                                                        if (isset($controlStructureInfo[$_skey]["isCard"]) && $controlStructureInfo[$_skey]["isCard"]) {
                                                                            $_svalue = $this->filterCardDatas($_svalue);
                                                                        }
                                                                        if(isset($controlStructureInfo[$_skey]['isAmount']) && $controlStructureInfo[$_skey]['isAmount']) {
                                                                            if (!in_array($_skey.'_amount', $amountData)) {
                                                                                $amountData[] = $_skey.'_amount';
                                                                            }
                                                                        }
                                                                        if (is_array($_svalue)) {
                                                                            $_secondControlValue[$_skey] = implode(',', $_svalue);
                                                                        }
                                                                    }
                                                                    $secondFormDataInfo = $_secondControlValue;

                                                                    // 过滤空行
                                                                    $emptyLine = $this->isEmptyLine($_secondControlValue);
                                                                    if (!$emptyLine) {
                                                                        $secondFormDataInfo['sort_id'] = $_k + 1;
                                                                        $secondFormDataInfo["run_id"] = $runId;
                                                                        $secondFormDataInfo["data_id"] = $flowRunFormDetailLayoutData;
                                                                        $secondFormDataInfo['updated_at'] = date('Y-m-d H:i:s');
                                                                        $secondFormDataInfo['created_at'] = date('Y-m-d H:i:s');
                                                                        unset($secondFormDataInfo['id']);
                                                                        $id = DB::table($secondTableName)->insertGetId($secondFormDataInfo);
                                                                    }
                                                                }
                                                            }
                                                            if($amountData) {
                                                                foreach ($amountData as $amountKey) {
                                                                    if(isset($itemInfo[$amountKey])) {
                                                                        $amountSecondFormDataInfo = [];
                                                                        $amountSecondFormDataInfo["run_id"] = $runId;
                                                                        $amountSecondFormDataInfo["data_id"] = $flowRunFormDetailLayoutData;
                                                                        $amountSecondFormDataInfo["amount"] = 'amount';
                                                                        $amountSecondFormDataInfo[str_replace("_amount", '', $amountKey)] = $itemInfo[$amountKey];
                                                                        $amountSecondFormDataInfo['updated_at'] = date('Y-m-d H:i:s');
                                                                        $amountSecondFormDataInfo['created_at'] = date('Y-m-d H:i:s');
                                                                        unset($amountSecondFormDataInfo['id']);
                                                                        $id = DB::table($secondTableName)->insertGetId($amountSecondFormDataInfo);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                // 插入合计数据的那一行
                                $layoutControlIsAmount = isset($controlAttribute["isAmount"]) ? $controlAttribute["isAmount"] : "";
                                if ($layoutControlIsAmount) {
                                    // 没有 amount 列 的处理
                                    if (!empty($layoutDataTableColumnList) && is_array($layoutDataTableColumnList) && !in_array('amount', $layoutDataTableColumnList)) {
                                        // if (Schema::hasTable($layoutDataTableName) && !Schema::hasColumn($layoutDataTableName, "amount")) {
                                        Schema::table($layoutDataTableName, function ($table) {
                                            // 明细字段值合计标识
                                            $table->string("amount")->comment(trans("flow.0x030048"));
                                        });
                                    }
                                    $amountId = DB::table($layoutDataTableName)->where(["run_id" => $runId, "amount" => 'amount'])->first();
                                    if ($amountId) {
                                        $amountId = $amountId->id;
                                    } else {
                                        $amountId = '';
                                    }
                                    $layoutAmountLineInfo = [];
                                    // 组织数据
                                    $layoutAmountLineInfo = [
                                        "amount" => "amount",
                                        "id" => $amountId
                                    ];
                                    $layoutChildInfo = $controlAttribute["children"] ?? [];
                                    if (count($layoutChildInfo)) {
                                        foreach ($layoutChildInfo as $layoutChildId => $layoutChildValue) {
                                            if (isset($layoutChildValue["isAmount"]) && $layoutChildValue["isAmount"]) {
                                                $layoutAmountLineInfo[$layoutChildId] = $formData[$layoutChildId . '_amount'] ?? "";
                                            }
                                        }
                                    }
                                    $layoutAmountSaveParams = ["run_id" => $runId, "form_id" => $formId . str_replace("DATA", "", $controlId), "form_data" => $layoutAmountLineInfo];
                                    if (!empty($checkHostFlag) || (isset($param["save_type"]) && $param["save_type"] == "create_flow")) {
                                        // 日志写入操作太耗时，暂时注释，按需单独开发，勿删勿删勿删
                                        if (envOverload("FLOW_GET_DATA_TOGGLE") === 'true') {
                                            app($this->flowLogService)->addFlowDataLogs($name . '.txt', "[" . date("Y-m-d H:i:s", time()) . "]update_detail_layout_amount_data\r\n" . json_encode($layoutAmountSaveParams) . "\r\n");
                                        }
                                        // 插入数据
                                        $flowRunFormDetailLayoutData = $this->formDataPerformSave($layoutAmountSaveParams, "detail-layout");
                                    }
                                }

                                if (in_array($controlId, $editInfo) && $oldIds && !empty($checkHostFlag)) {
                                    foreach ($oldIds as $oldId) {
                                        if (!in_array($oldId, $newIds)) {
                                            $oldIds = DB::table($layoutDataTableName)->where(["id" => $oldId])->delete();
                                            foreach ($scoundTables as $tableName) {
                                                // 删除二级明细数据
                                                DB::table($tableName)->where(['run_id'=> $runId,'data_id'=> $oldId])->delete();
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (!empty($checkHostFlag) && in_array($controlId, $editInfo)) {
                                    DB::table($layoutDataTableName)->where(["run_id" => $runId])->delete();
                                    // 查找二级明细表 删除数据
                                    $formControlStructures = app($this->flowFormService)->getFlowFormControlStructure(['search' => ["form_id" => [$formId], 'control_type'=>['detail-layout'],'control_parent_id'=>[$controlId]],'fields'=>['control_id']]);
                                    foreach ($formControlStructures as $values) {
                                        $secondTableNames = 'zzzz_flow_data_'.$formId.str_replace("DATA", "", $values['control_id']);
                                        DB::table($secondTableNames)->where(['run_id'=> $runId])->delete();
                                    }
                                }
                            }
                        } else {
                            // 格式化银行卡
                            if ($controlType == "text" && isset($controlAttribute["isCard"]) && $controlAttribute["isCard"]) {
                                $value = $this->filterCardDatas($value);
                            }
                            // 收集【会签字段控件】的id，用于后面的保存
                            if ($controlType == "countersign") {
                                if (in_array($controlId, $editInfo) || (isset($param["save_type"]) && $param["save_type"] == "create_flow")) {
                                    $countersignControlIdArray[$controlId] = ["control_id" => $controlId, "control_value" => $value];
                                }
                                $value = "";
                            }
                            // formdata数据清洗，如果是数组的，转换成逗号拼接的字符串
                            if (is_array($value)) {
                                if (isset($value[0]) && is_array($value[0])) { //角色部门人员 有默认值时 为二维数组
                                    // 子流程会签匹配非会签这里会报错 处理一下
                                    if (isset($value[0]['countersign_id'])) {
                                        $value = '';
                                    } else {
                                        $value = implode(",", $value[0]);
                                    }
                                } else {
                                    $value = implode(",", $value);
                                }
                            }
                            if (in_array($controlId, $editInfo) || (isset($param["save_type"]) && $param["save_type"] == "create_flow")) {
                                // 普通控件
                                $updateFormData[$controlId] = $value;
                            }
                            // zzzz表字段处理
                            if (isset($getColumnFlag[$zzzzTableName])) {
                                $zzzzTableColumnList = $getColumnFlag[$zzzzTableName];
                            } else {
                                $zzzzTableColumnList = Schema::getColumnListing($zzzzTableName);
                                $getColumnFlag[$zzzzTableName] = $zzzzTableColumnList;
                            }
                            if (!empty($zzzzTableColumnList) && is_array($zzzzTableColumnList) && !in_array($controlId, $zzzzTableColumnList)) {
                                // if (!Schema::hasColumn($zzzzTableName, $controlId)) {
                                Schema::table($zzzzTableName, function ($table) use ($controlId) {
                                    $table = $table->text($controlId)->nullable();
                                });
                            }
                        }
                    }
                }
                if (count($updateFormData)) {
                    $flowRunFormDataParams = ["run_id" => $runId, "form_id" => $formId, "form_data" => $updateFormData];
                    // 日志写入操作太耗时，暂时注释，按需单独开发，勿删勿删勿删
                    if (envOverload("FLOW_GET_DATA_TOGGLE") === 'true') {
                        app($this->flowLogService)->addFlowDataLogs($name . '.txt', "[" . date("Y-m-d H:i:s", time()) . "]update_form_data\r\n" . json_encode($flowRunFormDataParams) . "\r\n");
                    }
                    $flowRunFormData = $this->formDataPerformSave($flowRunFormDataParams);
                }
                // 调用函数，单独保存会签字段控件的值
                // 日志写入操作太耗时，暂时注释，按需单独开发，勿删勿删勿删
                if (envOverload("FLOW_GET_DATA_TOGGLE") === 'true') {
                    app($this->flowLogService)->addFlowDataLogs($name . '.txt', "[" . date("Y-m-d H:i:s", time()) . "]save_countersign_data\r\n" . json_encode($param) . "\r\n" . json_encode($countersignControlIdArray) . "\r\n");
                }
                $countersignDataSaveResult = $this->formDataCountersignDataSave($param, $countersignControlIdArray);
            }
            return 1;
        }
        return 0;
    }
    /**
     * 过滤银行卡号
     */
    public function filterCardDatas($value) {
        if(!empty($value)) {
            if(is_array($value)) {
                $value = implode(',', $value);
            }
            return  preg_replace('/\D/','',$value);
        }else {
            return '';
        }
    }
    /**
     * 调用函数，处理新建流程保存时数据源为流水号的
     * @param  [type] $param                  [description]
     * @return [type]                         [description]
     */
    public function createFlowSaveControlRunSeqData($controlValue, $runSeq, $controlId, $nodeId)
    {
        // 如果，流水号类型的宏控件，且form_data里没值，且是在新建调用此函数的情况下，且传了流水号, 那么，将此流水号的值保存进去
        if ($runSeq !== '') {
            // 增加判断获值方式设置 为空获值或者总是获值时才执行操作
            // 获取当前节点字段控制
            $nodeControlOperation = app($this->flowProcessControlOperationRepository)->getList(['search' => ['node_id' => [$nodeId], 'control_id' => [$controlId]]]);
            if ($nodeControlOperation && count($nodeControlOperation)) {
                $nodeControlOperation = $nodeControlOperation->toArray();
                if (isset($nodeControlOperation[0]['control_operation_detail']) && count($nodeControlOperation[0]['control_operation_detail'])) {
                    foreach ($nodeControlOperation[0]['control_operation_detail'] as $nodeControlOperationValue) {
                        if (isset($nodeControlOperationValue['operation_type'])) {
                            if ($nodeControlOperationValue['operation_type'] == 'isempty') {
                                if ($controlValue === '') {
                                    return strip_tags($runSeq);
                                }
                            }
                            if ($nodeControlOperationValue['operation_type'] == 'always') {
                                return strip_tags($runSeq);
                            }
                        }
                    }
                }
            }
        }
        return $controlValue;
    }

    /**
     * 调用函数，单独保存会签字段控件的值
     * @param  [type] $param                  [description]
     * @param  [type] $countersignControlIdArray [description]
     * @return [type]                            [description]
     */
    public function formDataCountersignDataSave($param, $countersignControlIdArray)
    {
        $runId = isset($param["run_id"]) ? $param["run_id"] : "";
        $process_id = isset($param["process_id"]) ? $param["process_id"] : "";
        $flow_process = isset($param["flow_process"]) ? $param["flow_process"] : "";
        // $getCountersignParam["page"] = "0";
        // $getCountersignParam["returntype"] = "object";
        // $getCountersignParam["search"]["run_id"] = [$runId];
        // $countersignList = app($this->flowCountersignRepository)->getCountersign($getCountersignParam);
        // 数据库里存的会签的id
        // $databaseCountersignIdInfo = $countersignList->pluck("countersign_id")->toArray();
        $formData = isset($param['form_data']) ? $param['form_data'] : '';
        if (count($countersignControlIdArray) && $formData) {
            // 收集值
            $controlData = [];
            foreach ($countersignControlIdArray as $key => $value) {
                $controlId = isset($value["control_id"]) ? $value["control_id"] : "";
                if ($controlId) {
                    // $controlValue = isset($value["control_value"]) ? $value["control_value"] : [];
                    // if($controlValue && count($controlValue)) {
                    //     foreach ($controlValue as $key => $pageValue) {
                    //         $countersignId = isset($pageValue["countersign_id"]) ? $pageValue["countersign_id"] : "";
                    //         unset($databaseCountersignIdInfo[array_search($countersignId,$databaseCountersignIdInfo)]);
                    //     }
                    // }
                    if (isset($formData[$controlId . "_COUNTERSIGN"]) && $formData[$controlId . "_COUNTERSIGN"]) {
                        $controlData[$controlId] = $formData[$controlId . "_COUNTERSIGN"];
                    }
                } else {
                    if (count($value)) {
                        foreach ($value as $layoutLineNumber => $layoutControlInfo) {
                            $layoutControlId = isset($layoutControlInfo["control_id"]) ? $layoutControlInfo["control_id"] : "";
                            $layoutControlValue = isset($layoutControlInfo["control_value"]) ? $layoutControlInfo["control_value"] : [];
                            if (isset($formData[$layoutControlId . "_" . $layoutLineNumber . "_COUNTERSIGN"]) && $formData[$layoutControlId . "_" . $layoutLineNumber . "_COUNTERSIGN"]) {
                                $controlData[$layoutControlId . "_" . $layoutLineNumber] = $formData[$layoutControlId . "_" . $layoutLineNumber . "_COUNTERSIGN"];
                            }
                            // if($layoutControlValue && count($layoutControlValue)) {
                            //     foreach ($layoutControlValue as $key => $pageValue) {
                            //         $countersignId = isset($pageValue["countersign_id"]) ? $pageValue["countersign_id"] : "";
                            //         unset($databaseCountersignIdInfo[array_search($countersignId,$databaseCountersignIdInfo)]);
                            //     }
                            // }
                        }
                    }
                }
            }
            // 删除原来数据库里有，前端保存的时候，没有值的会签数据
            // if(count($databaseCountersignIdInfo)) {
            //     $deleteDatabaseParam = ['run_id' => [$runId],'countersign_id' => [$databaseCountersignIdInfo,'in']];
            //     app($this->flowCountersignRepository)->deleteByWhere($deleteDatabaseParam);
            // }
            // 拼接其他参数，插入
            if (count($controlData) && $runId && $process_id) {
                $insertData = [];
                $deleteIdData = [];
                $countersign_user_id = "";
                $comin = 1;
                $flowCounterList = [];
                foreach ($controlData as $key => $info) {
                    $control_id = str_replace("DATA_", "", $key);
                    if (isset($info["countersign_content"])) {
                        $countersign_user_id = $info["countersign_user_id"] ?? '';
                        if (!empty($info["countersign_content"])) {
                            if ($comin == 1) {
                                $flowCounterList = app($this->flowCountersignRepository)->entity->where('run_id' ,$runId )->where('process_id' ,$process_id )->where('save_type' , 2)->where('countersign_user_id' ,$countersign_user_id )->pluck('control_id')->toArray();
                                $comin = 0;
                            }
                            $insertData[] = [
                                "countersign_content" => $info["countersign_content"],
                                "countersign_user_id" => $countersign_user_id,
                                "run_id" => $runId,
                                "process_id" => $process_id,
                                "flow_process" => $flow_process,
                                "control_id" => $control_id,
                                "countersign_time" => date("Y-m-d H:i:s", time()),
                                'save_type' =>  in_array($control_id, $flowCounterList) ? 2 : 1,
                            ];
                        }
                        $deleteIdData[] = $control_id;
                    }
                }
                $deleteParam = ['run_id' => [$runId], 'process_id' => [$process_id], 'countersign_user_id' => [$countersign_user_id], 'control_id' => [$deleteIdData, 'in']];
                // 删除这个用户原有的会签数据后添加，如果当前的会签数据清空了，则不再增加，相当于删除了
                app($this->flowCountersignRepository)->deleteByWhere($deleteParam);
                if (!empty($insertData)) {
                    app($this->flowCountersignRepository)->insertMultipleData($insertData);
                }
                // 当会签使用契约锁签章时，保存会签需验证最新的和数据库的签署记录是否一致，将数据库多余的记录删除
                app($this->signatureConfigService)->handleFlowQiyuesuoSignature($runId, $insertData, $process_id, $countersign_user_id);
            }
        }
        return "";
    }

    /**
     * 删除流程表单数据表的数据
     * @param  [type] $param      [description]
     * @return [type]             [description]
     */
    public function deleteFlowRunFormData($param)
    {
        $formId = isset($param["form_id"]) ? $param["form_id"] : false;
        $runId = isset($param["run_id"]) ? $param["run_id"] : false;
        if ($formId && $runId) {
            $tableName = "zzzz_flow_data_" . $formId;
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->where('run_id', $runId)->delete();
            }
        }
    }

    /**
     * 此函数用原生的DB类操作数据库zzzz表，保存流程表单数据，不单独使用
     * 注意：此函数，也用于流程表单明细字段数据保存，传不同的form_id就行了，表结构几乎一样。
     * 参数 $updateType 可传值“ detail-layout ”，用在更新明细字段数据表的时候，这时候，不需要判断是否存在记录
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function formDataPerformSave($param, $updateType = "")
    {
        $formId = isset($param["form_id"]) ? $param["form_id"] : false;
        $runId = isset($param["run_id"]) ? $param["run_id"] : false;
        $formDataInfo = isset($param["form_data"]) ? $param["form_data"] : [];
        if ($formId && $runId && count($formDataInfo) && $formDataInfo) {
            $tableName = "zzzz_flow_data_" . $formId;
            if (Schema::hasTable($tableName)) {
                if ($updateType == "detail-layout") {
                    $formDataInfo["run_id"] = $runId;
                    if (isset($formDataInfo['id']) && $formDataInfo['id']) {
                        $updateId = $formDataInfo['id'];

                        $formDataInfo['updated_at'] = date('Y-m-d H:i:s');
                        if (DB::table($tableName)->where(["id" => $updateId])->count() < 1) {
                            $formDataInfo['created_at'] = date('Y-m-d H:i:s');
                            // 过滤空行
                            $emptyLine = $this->isEmptyLine($formDataInfo);
                            if ($emptyLine) {
                                return '';
                            }
                            unset($formDataInfo['id']);
                            $id = DB::table($tableName)->insertGetId($formDataInfo);
                        } else {
                            $id = $formDataInfo['id'];
                            unset($formDataInfo['id']);
                            unset($formDataInfo['run_id']);
                            unset($formDataInfo['amount']);
                            $update = DB::table($tableName)->where(["id" => $updateId])->update($formDataInfo);
                        }
                    } else {
                        $formDataInfo['created_at'] = date('Y-m-d H:i:s');
                        $formDataInfo['updated_at'] = date('Y-m-d H:i:s');
                        unset($formDataInfo['id']);
                        // 过滤空行
                        $emptyLine = $this->isEmptyLine($formDataInfo);
                        if ($emptyLine) {
                            return '';
                        }
                        $id = DB::table($tableName)->insertGetId($formDataInfo);
                    }
                } else {
                    if (DB::table($tableName)->where(["run_id" => $runId])->count() < 1) {
                        $id = DB::table($tableName)->insertGetId(["run_id" => $runId]);
                    }
                    $update = DB::table($tableName)->where(["run_id" => $runId])->update($formDataInfo);
                    $id = '';
                }
                return $id;
            }
        }
        return [];
    }
    /**
     * 插入数据时过滤明细空行
     */
    public function isEmptyLine($formDataInfo) {

        if(isset($formDataInfo['amount']) && $formDataInfo['amount'] == 'amount') {
            return false;
        }
        unset($formDataInfo['amount']);
        unset($formDataInfo['created_at']);
        unset($formDataInfo['updated_at']);
        unset($formDataInfo['id']);
        unset($formDataInfo['run_id']);
        unset($formDataInfo['data_id']);
        foreach ($formDataInfo as $key => $value) {
            if($value !== '') {
                return false;
            }
        }
        return true;
    }
    /**
     * 流程表单数据新插入函数，新建流程的时候，生成run_id之后，插入一条空的新数据
     * 1、原来的zzzz表里有太多其他数据，可以都不要。
     * 2、明细字段控件表【zzzz_flow_data_1_n】也需要插入初始数据！！
     * 20161031，思考之后，觉得明细字段不需要初始数据，普通zzzz表，一个run_id，就一行，明细的run_id是对应多行！普通zzzz表也不需要初始数据的！
     * 3、20161101，flowservice里已经不再调用这个函数了。
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function insertFlowRunFormData($param)
    {
        $formId = isset($param["form_id"]) ? $param["form_id"] : false;
        $runId = isset($param["run_id"]) ? $param["run_id"] : false;
        if ($formId && $runId) {
            $tableName = "zzzz_flow_data_" . $formId;
            if (Schema::hasTable($tableName)) {
                $data = DB::table($tableName)->where(["run_id" => $runId])->first();
                if (empty($data)) {
                    $id = DB::table($tableName)->insertGetId(["run_id" => $runId]);
                    return $id;
                }
            }
            // 明细字段数据表插入初始数据
            // $dataBase = env("DB_DATABASE");
            // $sql = DB::select("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = '".$dataBase."' AND table_name LIKE 'zzzz_flow_data\_".$formId."\_%'");
            // if(count($sql)) {
            //     foreach ($sql as $key => $value) {
            //         $detailTableName = $value->TABLE_NAME;
            //         // 双重验证，如果表名最后一位数字代表的数字，是主表的一个字段，再插入数据
            //         $iteamDetailControlId = "DATA_".str_replace($tableName."_", "", $value->TABLE_NAME);
            //         if(Schema::hasColumn($tableName, $iteamDetailControlId)) {
            //             if(Schema::hasTable($detailTableName)) {
            //                 $detailData = DB::table($detailTableName)->where(["run_id" => $runId])->first();
            //                 if(empty($detailData)) {
            //                     $id = DB::table($detailTableName)->insertGetId(["run_id" => $runId]);
            //                 }
            //             }
            //         }
            //     }
            // }
        }
        return [];
    }

    /**
     * 流程表单表【flow_form_type】新增数据，返回form_id之后，新建zzzz_flow_data_$FORM_ID表
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function insertFlowFormStructure($param)
    {
        $formId = isset($param["form_id"]) ? $param["form_id"] : false;
        if (!$formId) {
            return "";
        }
        $tableName = "zzzz_flow_data_" . $formId;
        if (Schema::hasTable($tableName)) {
            return true;
        }
        Schema::create($tableName, function ($table) {
            $table->increments("id")->comment(trans("flow.0x030049"));
            $table->integer("run_id")->comment(trans("flow.0x030050"));
            $table->timestamps();
            $table->index('run_id', 'index_run_id');
        });
        return Schema::hasTable($tableName);
    }

    /**
     * 处理解析后的表单控件，操作[流程数据表]和[明细字段数据表]
     * @param  [type] $data   [description]
     * @param  [type] $formId [description]
     * @return [type]         [description]
     */
    public function disposeFormControlStructure($data, $formId)
    { //Mark
        if (!$formId) {
            return ['code' => ['0x000003', 'common']];
        }
        $control = $data["control"];
        // $control = [
        //     "DATA_1" => [
        //         "type" => "input",
        //         "title" => "test_title_DATA_1",
        //     ],
        //     "DATA_4" => [
        //         "type" => "detail-layout",
        //         "title" => "test_title_DATA_4",
        //         "field_counter" => 3,
        //         "info" => [
        //             "DATA_4_1" => [
        //                 "type" => "detail-layout",
        //                 "title" => "test_title_DATA_1"，
        //                 "control_title_lang" => [
        //                     "cn"=>'xxx'
        //                 ],
        //                 "info" => [
        //                     "DATA_4_1_1" => [
        //                         "type" => "input",
        //                         "title" => "test_title_DATA_1"，
        //                         "control_title_lang" => [
        //                             "cn"=>'xxx'
        //                         ]
        //                     ],
        //                 ],
        //             ],
        //             "DATA_4_2" => [
        //                 "type" => "input",
        //                 "title" => "test_title_DATA_2"
        //             ]
        //         ]
        //     ],
        // ];
        // $data['change'] = [
        //     'update' => [
        //         ['updateRoute'=>'DATA_1#DATA_6','updateType'=>'delete'],
        //     ],
        //     'add' => ['DATA_17','DATA_18'],
        //     'delete' => ['DATA_17_1'],//明细子项合计值取消之后在这里记录
        // ];
        // "control_title_lang" => [
        //     "DATA_1":["cn"=>'xxx','en'=>'xxxx'],
        //     "DATA_2":["cn"=>'xxx']
        // ]
        //表单字段改变 同步更新出口条件，同时记录更改记录
        $changeDataTableName = 'flow_form_change_' . $formId;
        $control_change = [];
        $num = 0;
        $oldFormControl = [];
        $newFormControl = [];
        $formVersionNo = $data['form_version_no'] ?? 0;
        $oldFormVersionNo = $data['old_form_version_no'] ?? 0;
        $currentTime = date('Y-m-d H:i:s', time());
        //取原控件列表89
        $oldFormControl = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['fields' => ['control_id'], 'search' => ['form_id' => [$formId], 'form_version_no' => [$oldFormVersionNo]], 'returntype' => 'object'])->pluck('control_id')->toArray();
        //处理新控件列表
        foreach ($control as $key => $value) {
            if (isset($value['type']) && $value['type'] == "detail-layout") {
                array_push($newFormControl, $key);
                if (isset($value['info']) && !empty($value['info'])) {
                    foreach ($value['info'] as $controlName => $control_id) {
                        array_push($newFormControl, $controlName);
                        // 明细控件子控件
                        if (isset($control_id['attribute']['children']) && !empty($control_id['attribute']['children'])) {
                            $tmpChildren = is_array($control_id['attribute']['children']) ? $control_id['attribute']['children'] : json_decode($control_id['attribute']['children'], true);
                            foreach ($tmpChildren as $controlValue) {
                                $tempChilerenControlId = !empty($controlValue['attribute']) && !empty($controlValue['attribute']['id']) ? $controlValue['attribute']['id'] : ($controlValue['id'] ?? '');
                                if (!empty($tempChilerenControlId)) {
                                    array_push($newFormControl, $tempChilerenControlId);
                                }
                                // 子控件里的二级明细
                                // 二级明细项
                                if (isset($controlValue['type']) && $controlValue['type'] == 'detail-layout') {
                                    if (isset($controlValue['info']) && !empty($controlValue['info'])) {
                                        foreach ($controlValue['info'] as $_control_id => $_control_info) {
                                            array_push($newFormControl, $_control_id);
                                            // 二级明细控件子控件
                                            if (isset($_control_info['attribute']['children']) && !empty($_control_info['attribute']['children'])) {
                                                $_tmpChildren = is_array($_control_info['attribute']['children']) ? $_control_info['attribute']['children'] : json_decode($_control_info['attribute']['children'], true);
                                                foreach ($_tmpChildren as $_control_value) {
                                                    $_tempChilerenControlId = !empty($_control_value['attribute']) && !empty($_control_value['attribute']['id']) ? $_control_value['attribute']['id'] : ($_control_value['id'] ?? '');
                                                    if (!empty($_tempChilerenControlId)) {
                                                        array_push($newFormControl, $_tempChilerenControlId);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // 二级明细项
                        if (isset($control_id['type']) && $control_id['type'] == 'detail-layout') {
                            if (isset($control_id['info']) && !empty($control_id['info'])) {
                                foreach ($control_id['info'] as $_controlId => $_controlInfo) {
                                    array_push($newFormControl, $_controlId);
                                    // 二级明细控件子控件
                                    if (isset($_controlInfo['attribute']['children']) && !empty($_controlInfo['attribute']['children'])) {
                                        $_tmpChildren = is_array($_controlInfo['attribute']['children']) ? $_controlInfo['attribute']['children'] : json_decode($_controlInfo['attribute']['children'], true);
                                        foreach ($_tmpChildren as $_controlValue) {
                                            $_tempChilerenControlId = !empty($_controlValue['attribute']) && !empty($_controlValue['attribute']['id']) ? $_controlValue['attribute']['id'] : ($_controlValue['id'] ?? '');
                                            if (!empty($_tempChilerenControlId)) {
                                                array_push($newFormControl, $_tempChilerenControlId);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                array_push($newFormControl, $key);
            }
        }
        //取在原控件列表里 不在新控件列表里的控件
        $diff = array_diff($oldFormControl, $newFormControl);
        $deleteData = [];
        //处理update数据
        if (isset($data['change']['update']) && !empty($data['change']['update'])) {
            //将更新的控件id转换成key=>value形式
            $update_change = [];
            //记录更新字段中选择了删除出口条件的控件id 以及取消合计的明细子项id
            $update_delete = !empty($data['change']) ? $data['change'] : [];
            $data['change']['delete'] = [];
            foreach ($data['change']['update'] as $value) {
                if ($value["updateRoute"]) {
                    $change = explode('#', $value["updateRoute"]);
                    if (isset($change[0]) && isset($change[1])) {
                        $update_change[$change[0]] = $change[1];
                        if ($value['updateType'] && $value['updateType'] == 'delete') {
                            array_push($update_delete, $change[0]);
                        }
                    }
                }
            }
            $data['change']['update'] = $update_change;
        }
        //取出删除控件
        if (!empty($diff)) {
            if (isset($data['change']['update']) && !empty($data['change']['update'])) {
                $changeUpdate = [];
                foreach ($data['change']['update'] as $key => $value) {
                    array_push($changeUpdate, $key);
                }
                $deleteData = array_diff($diff, $changeUpdate);
            } else {
                $deleteData = $diff;
            }
            $data['change']['delete'] = $deleteData;
        }
        //获取使用当前表单的所有流程列表
        $flowLists = [];
        $flowLists = app($this->flowTypeRepository)->getFlowListForFormId($formId);
        if ($flowLists) {
            $flowLists = $flowLists->pluck('flow_id')->toArray();
            $flowNodes = [];
            foreach ($flowLists as $value) {
                $flownodesparam["flow_id"] = $value;
                $flownodesparam['fields'] = ["node_id"];
                $flownodesparam['returntype'] = "object";
                $flowNode = [];
                $flowNode = app($this->flowProcessRepository)->getFlowDefineProcessList($flownodesparam);
                if ($flowNode) {
                    $flowNode = $flowNode->pluck('node_id')->toArray();
                }
                $flowNodes = array_merge($flowNodes, $flowNode);
            }
            //删除固定流程字段相关数据
            if (!empty($flowNodes) && !empty($data['old_form_version_no'])) {
                $raw = 'control_id like "%DATA_%"';
                //删除字段控制相关数据
                app($this->flowProcessControlOperationRepository)->entity->whereRaw($raw)->wheres(['control_id' => [$newFormControl, 'not_in'], 'node_id' => [$flowNodes, 'in']])->delete();
                foreach ($flowNodes as $nodeId) {
                    Redis::hdel('fixed_flow_node_field_control_operation', $nodeId);
                    Redis::hdel('fixed_flow_node_field_control_operation_condition', $nodeId);
                }
            }
        }
        if (!empty($data['change'])) {
            if (!Schema::hasTable($changeDataTableName)) {
                // 建表
                Schema::create($changeDataTableName, function ($table) {
                    $table->increments("id")->comment(trans("flow.0x030049"));
                    $table->string("form_id")->comment(trans("flow.0x030051"));
                    $table->string("control_id")->comment(trans("flow.0x030052"));
                    $table->string("new_control_id")->comment(trans("flow.0x030053"));
                    $table->string("type")->comment(trans("flow.0x030054") . ' add|update|delete');
                    $table->dateTime("create_time")->comment(trans("flow.0x030055"));
                    $table->string("creator")->comment(trans("flow.0x030056"));
                    $table->timestamps();
                });
            }
            $changeData = [
                "form_id" => $formId,
                "control_id" => '',
                "new_control_id" => '',
                "type" => '',
                "create_time" => date('Y-m-d H:i:s'),
                "creator" => $data['user_id'],
            ];
            if (!empty($data['change']['update'])) {
                $updateInsertData = [];
                foreach ($data['change']['update'] as $key => $value) {
                    $updateData = $changeData;
                    $updateData['control_id'] = $key;
                    $updateData['new_control_id'] = $value;
                    $updateData['type'] = 'update';
                    $updateInsertData[] = $updateData;
                    if (empty($update_delete) || !in_array($key, $update_delete)) {
                        $control_change[$num]['control_id'] = $key;
                        $control_change[$num]['new_control_id'] = $value;
                        $num++;
                    }

                    //更新数据外发、子流程相关数据
                    //获取当前更新字段是否在子流程外发字段 或者子流程接收字段中
                    $sunWorkflowlistForprocess = app($this->flowSunWorkflowRepository)->getlists(['fields' => ['porcess_fields', 'id', 'receive_fields'], 'search' => ['node_id' => [$flowNodes, 'in']]]);
                    if (!empty($sunWorkflowlistForprocess)) {
                        foreach ($sunWorkflowlistForprocess as $sunWorkvalue) {
                            if (!empty($sunWorkvalue['porcess_fields'])) {
                                $sunWorkvalue['porcess_fields'] = $sunWorkvalue['porcess_fields'] . ',';
                                $srearchflieds = $key . ',';
                                $changeflieds = $value . ',';
                                if (preg_match("/" . $srearchflieds . "/", $sunWorkvalue['porcess_fields'])) {
                                    $sunWorkvalue['porcess_fields'] = str_replace($srearchflieds, $changeflieds, $sunWorkvalue['porcess_fields']);
                                    $sunWorkvalue['porcess_fields'] = rtrim($sunWorkvalue['porcess_fields'], ',');
                                    app($this->flowSunWorkflowRepository)->updateData(['porcess_fields' => $sunWorkvalue['porcess_fields']], ['id' => [$sunWorkvalue['id']]]);
                                }
                            }
                        }
                    }
                    //获取当前更新字段是否在子流程外发字段 或者子流程接收字段中
                    $sunWorkflowlistForreceive = app($this->flowSunWorkflowRepository)->getlists(['fields' => ['porcess_fields', 'id', 'receive_fields'], 'search' => ['receive_flow_id' => [$flowLists, 'in']]]);
                    if (!empty($sunWorkflowlistForreceive)) {
                        foreach ($sunWorkflowlistForreceive as $sunWorkvalue) {
                            if (!empty($sunWorkvalue['receive_fields'])) {
                                $sunWorkvalue['receive_fields'] = $sunWorkvalue['receive_fields'] . ',';
                                $srearchflieds = $key . ',';
                                $changeflieds = $value . ',';
                                if (preg_match("/" . $srearchflieds . "/", $sunWorkvalue['receive_fields'])) {
                                    $sunWorkvalue['receive_fields'] = str_replace($srearchflieds, $changeflieds, $sunWorkvalue['receive_fields']);
                                    $sunWorkvalue['receive_fields'] = rtrim($sunWorkvalue['receive_fields'], ',');
                                    app($this->flowSunWorkflowRepository)->updateData(['receive_fields' => $sunWorkvalue['receive_fields']], ['id' => [$sunWorkvalue['id']]]);
                                }
                            }
                        }
                    }

                    //获取当前更新字段是否在数据外发外发字段中
                    $outsendlist = app($this->flowOutsendRepository)->getlists(['fields' => ['porcess_fields', 'id'], 'search' => ['node_id' => [$flowNodes, 'in']]]);
                    if (!empty($outsendlist)) {
                        foreach ($outsendlist as $outsendvalue) {
                            if (!empty($outsendvalue['porcess_fields'])) {
                                $outsendvalue['porcess_fields'] = $outsendvalue['porcess_fields'] . ',';
                                $srearchflieds = $key . ',';
                                $changeflieds = $value . ',';
                                if (preg_match("/" . $srearchflieds . "/", $outsendvalue['porcess_fields'])) {
                                    $outsendvalue['porcess_fields'] = str_replace($srearchflieds, $changeflieds, $outsendvalue['porcess_fields']);
                                    $outsendvalue['porcess_fields'] = rtrim($outsendvalue['porcess_fields'], ',');
                                    app($this->flowOutsendRepository)->updateData(['porcess_fields' => $outsendvalue['porcess_fields']], ['id' => [$outsendvalue['id']]]);
                                }
                            }
                        }
                    }
                    //获取当前更新字段是否在流程标题规则设置字段中
                    $flowTypelist = app($this->flowTypeRepository)->getFlowTypeList(['fields' => ['flow_name_rules', 'flow_name_rules_html', 'flow_id'], 'search' => ['flow_id' => [$flowLists, 'in']], 'returntype' => 'array']);
                    if (!empty($flowTypelist)) {
                        foreach ($flowTypelist as $flowTypeValue) {
                            if (!empty($flowTypeValue['flow_name_rules'])) {
                                if (preg_match("/" . $key . "/", $flowTypeValue['flow_name_rules'])) {
                                    $flowTypeValue['flow_name_rules'] = str_replace($key, $value, $flowTypeValue['flow_name_rules']);
                                    app($this->flowTypeRepository)->updateData(['flow_name_rules' => $flowTypeValue['flow_name_rules']], ['flow_id' => [$flowTypeValue['flow_id']]]);
                                }
                            }
                            if (!empty($flowTypeValue['flow_name_rules_html'])) {
                                if (preg_match("/" . $key . "/", $flowTypeValue['flow_name_rules_html'])) {
                                    $flowTypeValue['flow_name_rules_html'] = str_replace($key, $value, $flowTypeValue['flow_name_rules_html']);
                                    app($this->flowTypeRepository)->updateData(['flow_name_rules_html' => $flowTypeValue['flow_name_rules_html']], ['flow_id' => [$flowTypeValue['flow_id']]]);
                                }
                            }
                        }
                    }

                    //更新自由流程首节点必填设置
                    $upateWhere = ['control_id' => [$key], 'flow_id' => [$flowLists, 'in']];
                    app($this->flowRequiredForFreeFlowRepository)->updateData(['control_id' => $value], $upateWhere);
                }
                if (!empty($updateInsertData)) {
                    DB::table($changeDataTableName)->insert($updateInsertData);
                }
            }
            if (!empty($data['change']['add'])) {
                $newInsertData = [];
                foreach ($data['change']['add'] as $value) {
                    $newData = $changeData;
                    $newData['control_id'] = $value;
                    $newData['type'] = 'add';
                    $newInsertData[] = $newData;
                }
                if (!empty($newInsertData)) {
                    DB::table($changeDataTableName)->insert($newInsertData);
                }
            }
            if (!empty($data['change']['delete'])) {
                $deleteInsertData = [];
                foreach ($data['change']['delete'] as $value) {
                    $deleteData = $changeData;
                    $deleteData['control_id'] = $value;
                    $deleteData['type'] = 'delete';
                    $deleteInsertData[] = $deleteData;
                    $control_change[$num]['control_id'] = $value;
                    $control_change[$num]['new_control_id'] = '';
                    $num++;
                }
                if (!empty($deleteInsertData)) {
                    DB::table($changeDataTableName)->insert($deleteInsertData);
                }
                if (isset($update_delete) && !empty($update_delete)) {
                    foreach ($update_delete as $deletedate) {
                        $deletearray = [];
                        $deletearray['control_id'] = $deletedate;
                        $deletearray['new_control_id'] = '';
                        array_push($control_change, $deletearray);
                    }
                }
                //更新自由流程首节点必填设置
                $nodeIdArray = ['flow_id' => [$flowLists, 'in'], 'control_id' => [$data['change']['delete'], 'in']];
                //删除字段控制相关数据
                app($this->flowRequiredForFreeFlowRepository)->deleteByWhere($nodeIdArray);
            }
            // }
        }
        //更新出口条件
        if (!empty($control_change)) {
            //获取使用当前表单的所有流程列表
            $flowList = app($this->flowTypeRepository)->getFlowListForFormId($formId);
            $replaceResult = "";
            if ($flowList) {
                $flowList = $flowList->pluck('flow_id')->toArray();
                //相关节点出口条件
                if (!empty($flowList)) {
                    if ($result = app($this->flowTermRepository)->getFlowNodeList($flowList)) {
                        if (!empty($result)) {
                            foreach ($result as $condition_value) {
                                foreach ($control_change as $control_name) {
                                    if (!isset($replaceFlowlist[$condition_value['term_id']])) {
                                        $replaceFlowlist[$condition_value['term_id']] = $condition_value['condition'];
                                    }
                                    if (!isset($control_name['control_id'])) {
                                        continue;
                                    }
                                    $changeControl = '"' . $control_name['control_id'] . '"';
                                    $replaceControl = isset($control_name['new_control_id']) ? ('"' . $control_name['new_control_id'] . '"') : '';
                                    if (preg_match("/" . $changeControl . "/", $replaceFlowlist[$condition_value['term_id']])) {
                                        if ($replaceControl) {
                                            $replaceFlowlist[$condition_value['term_id']] = str_replace($changeControl, $replaceControl, $replaceFlowlist[$condition_value['term_id']]);
                                        } else {
                                            $replaceFlowlist[$condition_value['term_id']] = '';
                                        }
                                    }
                                }
                            }
                            //$replaceResult = str_replace($search, $replace, $replaceFlowlist);
                        }
                    };
                }
            }
            if (!empty($replaceFlowlist)) {
                foreach ($replaceFlowlist as $key => $value) {
                    app($this->flowTermRepository)->updateData(['condition' => $value], ['term_id' => [$key]]);
                }
            }
        }
        if (count($control)) {
            $flowDataTableName = "zzzz_flow_data_" . $formId;
            if (!Schema::hasTable($flowDataTableName)) {
                Schema::create($flowDataTableName, function ($table) {
                    $table->increments("id")->comment(trans("flow.0x030049"));
                    $table->integer("run_id")->comment(trans("flow.0x030050"));
                    $table->timestamps();
                    $table->index('run_id', 'index_run_id');
                });
            }
            $controlStructureData = [];
            $controlStructurelayoutData = [];
            app($this->flowFormControlStructureRepository)->deleteByWhere(["form_id" => [$formId]]);
            $controlSort = 0;
            $langDataTemp = [];
            $controlStructureData = [];
            $flowDataTableColumnList = Schema::getColumnListing($flowDataTableName);

            Schema::table($flowDataTableName, function ($table) use ($flowDataTableName, $control, $flowDataTableColumnList, &$controlStructureData, $formId, &$controlSort, &$langDataTemp, $data, $currentTime) {
                foreach ($control as $controlKey => $contrilValue) {
                    if (!isset($contrilValue["type"])) {
                        continue;
                    }
                    $_type = isset($contrilValue["type"]) ? $contrilValue["type"] : $contrilValue["attribute"]["type"];
                    // 过滤掉备注中的单引号
                    $contrilValue['title'] = str_replace("'", " ", $contrilValue['title']);
                    if ($_type == 'editor') {
                        if (!empty($flowDataTableColumnList) && is_array($flowDataTableColumnList) && in_array($controlKey, $flowDataTableColumnList)) {
                            $table->longText($controlKey)->comment($contrilValue['title'])->default('')->change();
                        } else {
                            $table->longText($controlKey)->comment($contrilValue['title'])->nullable();
                        }
                        $flowDataTableColumnList[] = $controlKey;
                    }
                    //系统数据字段处理
                    if ((isset($contrilValue['attribute']['data-efb-with-text']) && ($contrilValue['attribute']['data-efb-with-text'] == 'true' || $contrilValue['attribute']['data-efb-with-text'] === true)) || $_type == 'select' || $_type == 'upload') {
                        $controlSystemData = $controlKey . "_TEXT";
                        if (!empty($flowDataTableColumnList) && is_array($flowDataTableColumnList) && !in_array($controlSystemData, $flowDataTableColumnList)) {
                            $table->text($controlSystemData)->comment($contrilValue['title'])->nullable();
                        } else {//更新字段comment
                            $table->text($controlSystemData)->comment($contrilValue['title'])->default('')->change();
                        }
                    }
                    // flow_form_control_structure表[流程表单控件结构表]字段处理
                    if (isset($contrilValue["title"])) { //Mark
                        $controlStructureData[] = [
                            "form_id" => $formId,
                            "control_id" => $controlKey,
                            "control_title" => isset($contrilValue["title"]) ? $contrilValue["title"] : $contrilValue["attribute"]["title"],
                            "control_type" => isset($contrilValue["type"]) ? $contrilValue["type"] : $contrilValue["attribute"]["type"],
                            "control_attribute" => json_encode($contrilValue["attribute"]),
                            "control_parent_id" => "",
                            "sort" => isset($contrilValue["sort"]) ? $contrilValue["sort"] : $controlSort,
                            "form_version_no" => $data['form_version_no'] ?? 0,
                            "created_at" => $currentTime
                        ];
                        $controlSort++;
                        // zzzz表字段处理
                        if ($contrilValue['type'] != 'editor') {
                            if (!empty($flowDataTableColumnList) && is_array($flowDataTableColumnList) && !in_array($controlKey, $flowDataTableColumnList)) {
                                $table->text($controlKey)->comment($contrilValue['title'])->nullable();
                            } else {//更新字段comment
                                $table->text($controlKey)->comment($contrilValue['title'])->default('')->change();
                            }
                        }
                    }
                    // 明细字段处理
                    if ($contrilValue["type"] == "detail-layout") {
                        $childParam['control_key'] = $controlKey;
                        $childParam['contril_value'] = $contrilValue;
                        $childParam['control_structure_data'] = $controlStructureData;
                        $childParam['data'] = $data;
                        $childParam['current_time'] = $currentTime;
                        $childParam['form_id'] = $formId;
                        $controlStructureData = $this->getChildDetailLayoutInfo($childParam);
                    }
                    //处理多语言对象中没有的控件
                    if (!isset($data['control_title_lang']) || empty($data['control_title_lang']) || !isset($data['control_title_lang'][$controlKey])) {
                        $langDataTemp[] = [
                            'table' => 'flow_form_control_structure',
                            'column' => 'control_title',
                            'lang_key' => $formId . '_' . $controlKey,
                            'lang_value' => $contrilValue['title'],
                        ];
                    }
                }
            });
            if (!empty($controlStructureData)) {
                app($this->flowFormControlStructureRepository)->insertMultipleData($controlStructureData);
            }
            if (!empty($langDataTemp)) {
                app($this->langService)->mulitAddDynamicLang($langDataTemp);
            }
        } else {
            app($this->flowFormControlStructureRepository)->deleteByWhere(["form_id" => [$formId]]);
        }
        if (Redis::exists('hand_page_form_control_structure_' . $formId)) {
            Redis::del('hand_page_form_control_structure_' . $formId);
        }
        if (Redis::exists('add_text_hand_page_form_control_structure_' . $formId)) {
            Redis::del('add_text_hand_page_form_control_structure_' . $formId);
        }
        // 获取子表单列表，更新所有子表单控件多语言
        $searchChildFormListParams = [
            'search' => ['parent_id' => [$formId]],
            'fields' => ['form_id'],
            'returntype' => 'object'
        ];
        $updateChildFormIdList = app($this->flowChildFormTypeRepository)->getFlowForm($searchChildFormListParams)->pluck('form_id')->toArray();

        //处理多语言
        $langData = [];
        $_langData = [];
        if (isset($data['control_title_lang']) && !empty($data['control_title_lang'])) {
            $control_title_lang = $data['control_title_lang'];
            foreach ($control_title_lang as $control_id_temp => $lang) {
                if (count($lang)) {
                    $hasLangValue = false;
                    foreach ($lang as $lang_key => $lang_value) {
                        if ($lang_value !== '') {
                            $hasLangValue = true;
                            $langData[$lang_key][] = [
                                'table' => 'flow_form_control_structure',
                                'column' => 'control_title',
                                'lang_key' => $formId . '_' . $control_id_temp,
                                'lang_value' => $lang_value,
                            ];
                            // 更新子表单控件多语言
                            if (!empty($updateChildFormIdList)) {
                                foreach ($updateChildFormIdList as $childFormIdKey => $childFormIdValue) {
                                    $langData[$lang_key][] = [
                                        'table' => 'flow_child_form_control_structure',
                                        'column' => 'control_title',
                                        'lang_key' => $childFormIdValue . '_' . $control_id_temp,
                                        'lang_value' => $lang_value
                                    ];
                                }
                            }
                        }
                    }
                    //所有多语言值为空时 把title插入当前语言环境
                    if (!$hasLangValue && isset($control[$control_id_temp]['title'])) {
                        $_langData[] = [
                            'table' => 'flow_form_control_structure',
                            'column' => 'control_title',
                            'lang_key' => $formId . '_' . $control_id_temp,
                            'lang_value' => $control[$control_id_temp]['title'],
                        ];
                        // 更新子表单控件多语言
                        if (!empty($updateChildFormIdList)) {
                            foreach ($updateChildFormIdList as $childFormIdKey => $childFormIdValue) {
                                $_langData[$lang_key][] = [
                                    'table' => 'flow_child_form_control_structure',
                                    'column' => 'control_title',
                                    'lang_key' => $childFormIdValue . '_' . $control_id_temp,
                                    'lang_value' => $control[$control_id_temp]['title']
                                ];
                            }
                        }
                    }
                } else {
                    //控件没有多语言信息时 把title插入当前语言环境
                    if (isset($control[$control_id_temp]['title'])) {
                        $_langData[] = [
                            'table' => 'flow_form_control_structure',
                            'column' => 'control_title',
                            'lang_key' => $formId . '_' . $control_id_temp,
                            'lang_value' => $control[$control_id_temp]['title'],
                        ];
                        // 更新子表单控件多语言
                        if (!empty($updateChildFormIdList)) {
                            foreach ($updateChildFormIdList as $childFormIdKey => $childFormIdValue) {
                                $_langData[$lang_key][] = [
                                    'table' => 'flow_child_form_control_structure',
                                    'column' => 'control_title',
                                    'lang_key' => $childFormIdValue . '_' . $control_id_temp,
                                    'lang_value' => $control[$control_id_temp]['title']
                                ];
                            }
                        }
                    }
                }
            }
            if (!empty($langData)) {
                foreach ($langData as $key => $value) {
                    app($this->langService)->mulitAddDynamicLang($value, $key);
                }
            }
            if (!empty($_langData)) {
                app($this->langService)->mulitAddDynamicLang($_langData);
            }
        }
    }
    /**
     * 处理明细子项
     */
    function getChildDetailLayoutInfo($param) {
        $controlKey = $param['control_key'];
        $contrilValue = $param['contril_value'];
        $controlStructureData = $param['control_structure_data'];
        $data = $param['data'];
        $currentTime = $param['current_time'];
        $formId = $param['form_id'];
        $flowDataTableName= "zzzz_flow_data_" . $formId;
        $second = false;
        if (isset($param['is_second']) && $param['is_second']) {
            $second = true;
        }
        // 明细字段数据表
        $detailFormId = str_replace("DATA_", "", $controlKey);
        $detailDataTableName = $flowDataTableName .'_'. $detailFormId;
        if (!Schema::hasTable($detailDataTableName)) {
            // 建表
            Schema::create($detailDataTableName, function ($table) use($second) {
                $table->increments("id")->comment(trans("flow.0x030049"));
                $table->integer("run_id")->comment(trans("flow.0x030050"));
                $table->integer("sort_id")->comment(trans("flow.0x030187"));
                // 明细字段值合计标识
                $table->string("amount")->comment(trans("flow.0x030048"));
                if ($second) {
                    // 二级明细与明细数据的关联id
                    $table->integer("data_id")->comment('');
                }
                $table->timestamps();
                $table->index('run_id', 'index_run_id');
            });
            $detailDataTableColumnList = Schema::getColumnListing($detailDataTableName);
        } else {
            $detailDataTableColumnList = Schema::getColumnListing($detailDataTableName);
            // 如果没有amount列
            if (!empty($detailDataTableColumnList) && is_array($detailDataTableColumnList) && !in_array('amount', $detailDataTableColumnList)) {
                Schema::table($detailDataTableName, function ($table) {
                    // 明细字段值合计标识
                    $table->string("amount")->comment(trans("flow.0x030048"));
                });
            }
            // 如果没有amount列
            if (!empty($detailDataTableColumnList) && is_array($detailDataTableColumnList) && !in_array('data_id', $detailDataTableColumnList)) {
                Schema::table($detailDataTableName, function ($table) use($second) {
                    if ($second) {
                        // 二级明细与明细数据的关联id
                        $table->integer("data_id")->comment('');
                    }
                });
            }
        }
        $detailInfo = isset($contrilValue["info"]) ? $contrilValue["info"] : array();
        if (count($detailInfo)) {
            $detailSort = 0;
            Schema::table($detailDataTableName, function ($detailTable) use ($detailInfo, $detailDataTableColumnList, &$controlStructureData, $formId, $controlKey, &$detailSort, $data, $currentTime) {
                $tmpDetailInfo = $detailInfo;
                foreach ($tmpDetailInfo as $tmpDetailValue) {
                    // 明细二级单元处理成一级单元
                    if (
                        (
                            (
                                isset($tmpDetailValue['type']) &&
                                ($tmpDetailValue['type'] == "column")
                            ) ||
                            (
                                isset($tmpDetailValue['attribute']['type']) &&
                                ($tmpDetailValue['attribute']['type'] == 'column')
                            )
                        ) &&
                        isset($tmpDetailValue['attribute']['children'])
                    ) {
                        $grandchildrenArr = is_string($tmpDetailValue['attribute']['children']) ? json_decode($tmpDetailValue['attribute']['children'], true) : $tmpDetailValue['attribute']['children'];
                        if (!$grandchildrenArr) {
                            continue;
                        }

                        foreach ($grandchildrenArr as $grandChildValue) {
                            // 父级保存实际父级，另在attribute中保存祖父级
                            $grandChildValue['control_parent_id'] = $tmpDetailValue['attribute']['id'];
                            $grandChildValue['attribute']['control_grandparent_id'] = $controlKey;
                            $detailInfo[$grandChildValue['attribute']['id'] ?? $grandChildValue['id']] = $grandChildValue;
                        }
                    }
                }

                foreach ($detailInfo as $detailKey => $detailValue) {
                    // 过滤掉备注中的单引号
                    $detailValue['title'] = str_replace("'", " ", $detailValue['title']);
                    $_detail_type = isset($detailValue["type"]) ? $detailValue["type"] : $detailValue["attribute"]['type'];
                    //系统数据字段处理
                    if ((isset($detailValue['attribute']['data-efb-with-text']) && ($detailValue['attribute']['data-efb-with-text'] == 'true' || $detailValue['attribute']['data-efb-with-text'] === true)) || $_detail_type == "select" || $_detail_type == "upload") {
                        $detailControlSystemData = $detailKey . "_TEXT";
                        if (!empty($detailDataTableColumnList) && is_array($detailDataTableColumnList) && !in_array($detailControlSystemData, $detailDataTableColumnList)) {
                            $detailTable->text($detailControlSystemData)->comment($detailValue['title'])->nullable();
                        } else {// 更新字段comment
                            $detailTable->text($detailControlSystemData)->comment($detailValue['title'])->default('')->change();
                        }
                    }
                    // 明细字段的--flow_form_control_structure表[流程表单控件结构表]字段处理
                    if (isset($detailValue["title"])) {
                        $controlStructureData[] = [
                            "form_id" => $formId,
                            "control_id" => $detailKey,
                            "control_title" => $detailValue["title"] ?? $detailValue["attribute"]['title'],
                            "control_type" => $detailValue["type"] ?? $detailValue["attribute"]['type'],
                            "control_attribute" => json_encode($detailValue["attribute"]),
                            "control_parent_id" => $detailValue['control_parent_id'] ?? $controlKey,
                            "sort" => $detailValue["sort"] ?? $detailSort,
                            "form_version_no" => $data['form_version_no'] ?? 0,
                            "created_at" => $currentTime
                        ];
                        $detailSort++;
                        if (!empty($detailDataTableColumnList) && is_array($detailDataTableColumnList) && !in_array($detailKey, $detailDataTableColumnList)) {
                            $detailTable->text($detailKey)->comment($detailValue['title'])->nullable();
                        } else {// 更新字段comment
                            $detailTable->text($detailKey)->comment($detailValue['title'])->default('')->change();
                        }
                    }
                    // 明细字段处理
                    if ($detailValue["type"] == "detail-layout") {
                        $childParam['control_key'] = $detailKey;
                        $childParam['contril_value'] = $detailValue;
                        $childParam['control_structure_data'] = $controlStructureData;
                        $childParam['data'] = $data;
                        $childParam['current_time'] = $currentTime;
                        $childParam['form_id'] = $formId;
                        $childParam['is_second'] = true;

                        $controlStructureData = $this->getChildDetailLayoutInfo($childParam);
                    }
                }
            });
        }
        return $controlStructureData;
    }
    /**
     * 【定义流程】 【表单模板】 保存定义流程的各种表单模板--废弃
     * 更新模板控件结构表
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function disposeFlowFormTemplateControlStructure($param)
    {
        $control = isset($param["control"]) ? $param["control"] : [];
        $flowId = isset($param["flow_id"]) ? $param["flow_id"] : "";
        $nodeId = isset($param["node_id"]) ? $param["node_id"] : "";
        $templateId = isset($param["template_id"]) ? $param["template_id"] : "";
        $formId = "";
        if ($flowId) {
            $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowId);
            $formId = $flowTypeInfo->form_id;
        }
        if (count($control) && $templateId) {
            $flowDataTableName = "zzzz_flow_data_" . $formId;
            $controlStructureData = [];
            $controlStructurelayoutData = [];
            // 删除已有的
            app($this->flowFormTemplateControlStructureRepository)->deleteByWhere(["template_id" => [$templateId]]);
            $controlSort = 0;
            foreach ($control as $controlKey => $contrilValue) {
                // flow_form_control_structure表[流程表单控件结构表]字段处理
                if (isset($contrilValue["title"])) {
                    $controlTotal = app($this->flowFormTemplateControlStructureRepository)->getTotal(['search' => ["template_id" => [$templateId], 'form_id' => [$formId], 'control_id' => [$controlKey]]]);
                    if ($controlTotal > 0) {
                        continue;
                    }
                    $controlStructureData = [
                        "template_id" => $templateId,
                        "form_id" => $formId,
                        "control_id" => $controlKey,
                        "control_title" => $contrilValue["title"],
                        "control_type" => $contrilValue["type"],
                        "control_attribute" => json_encode($contrilValue["attribute"]),
                        "control_parent_id" => "",
                        "sort" => $controlSort,
                    ];
                    $controlSort++;
                    app($this->flowFormTemplateControlStructureRepository)->insertData($controlStructureData);
                }
                // 明细字段处理
                if ($contrilValue["type"] == "detail-layout") {
                    $detailInfo = isset($contrilValue["info"]) ? $contrilValue["info"] : array();
                    if (count($detailInfo)) {
                        $detailSort = 0;
                        foreach ($detailInfo as $detailKey => $detailValue) {
                            // 明细字段的--flow_form_control_structure表[流程表单控件结构表]字段处理
                            if (isset($detailValue["title"])) {
                                $controlStructurelayoutData = [
                                    "template_id" => $templateId,
                                    "form_id" => $formId,
                                    "control_id" => $detailKey,
                                    "control_title" => $detailValue["title"],
                                    "control_type" => $detailValue["type"],
                                    "control_attribute" => json_encode($detailValue["attribute"]),
                                    "control_parent_id" => $controlKey,
                                    "sort" => $detailSort,
                                ];
                                $detailSort++;
                                app($this->flowFormTemplateControlStructureRepository)->insertData($controlStructurelayoutData);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取表单控件结构
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowFormControlStructureDetial($param, $withOutLayout = false)
    {
        $formControlStructure = app($this->flowFormService)->getFlowFormControlStructure(['search' => ["form_id" => [$param["form_id"]]]]);
        $formControlTypeArray = [];
        if (count($formControlStructure)) {
            $k = 0;
            foreach ($formControlStructure as $structureKey => $structureValue) {
                //智能获取办理人设置 排除明细、签名图片、会签、附件、水平垂直布局、电子签章、描述控件
                if (($structureValue["control_type"] == 'detail-layout' || $structureValue["control_type"] == 'signature-picture' || $structureValue["control_type"] == 'countersign' || $structureValue["control_type"] == 'upload' || $structureValue["control_type"] == 'dynamic-info' || $structureValue["control_type"] == 'horizontal-layout' || $structureValue["control_type"] == 'vertical-layout' || $structureValue["control_type"] == 'electronic-signature' || $structureValue["control_type"] == 'label' || $structureValue["control_type"] == 'barcode') && $withOutLayout) {
                    unset($formControlStructure[$structureKey]);
                    continue;
                }
                // 明细控件子项
                if ($structureValue["control_parent_id"]) {
                    unset($formControlStructure[$structureKey]);
                } else {
                    $formControlTypeArray[$k]['control_id'] = $structureValue["control_id"];
                    $formControlTypeArray[$k]['control_title'] = $structureValue["control_title"];
                    $k++;
                }
            }
        }

        return array_values($formControlTypeArray);
    }

    /**
     * 获取表单控件结构(含明细字段，数据外发处使用，获取包含明细字段子项在内的表单字段列表
     *  --明细字段子项排列在明细字段后)
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowFormControlStructureDetialForOutsend($param)
    {
        $data = [
            'fields' => ['control_parent_id', 'control_id', 'control_title', 'control_type'],
            'search' => [
                "form_id" => [$param["form_id"]],
                'control_parent_id' => [''], // 只查普通控件、明细父级控件，明细子控件在明细内处理
                'control_type' => [
                    // 暂先排除签名图片、水平垂直布局、电子签章、动态信息、明细二级column列
                    ['signature-picture', 'dynamic-info', 'horizontal-layout', 'vertical-layout', 'electronic-signature', 'barcode', 'column'],
                    'not_in',
                ]
            ],
        ];
        $formControlStructure = app($this->flowFormService)->getFlowFormControlStructure($data);
        $formControlTypeArray = [];
        if (count($formControlStructure)) {
            foreach ($formControlStructure as $structureValue) {
                $detailSearchParams = [
                    'fields' => ['control_parent_id', 'control_id', 'control_title', 'control_type'],
                    'search' => ["form_id" => [$param["form_id"]], 'control_parent_id' => [$structureValue['control_id']]],
                ];
                if ($structureValue['control_type'] == "detail-layout") { //明细字段,需要获取明细子项
                    $structureValue['haschilen'] = 1;
                    array_push($formControlTypeArray, $structureValue);
                    $formControlHasChildren = app($this->flowFormService)->getFlowFormControlStructure($detailSearchParams);
                    if (!$formControlHasChildren) {
                        continue;
                    }

                    //明细字段子项
                    foreach ($formControlHasChildren as $value) {
                        if ($value['control_type'] == 'detail-layout') {
                            continue;
                        }
                        if ($value['control_type'] == 'column') { // 如果是明细二级column列，则需要再去获取明细二级子项,且column列本身不显示
                            $columnDetailSearchParams = $detailSearchParams;
                            $columnDetailSearchParams['search']['control_parent_id'] = [$value['control_id']];
                            // 获取该明细下的二级明细
                            $formControlHasGrandchildren = app($this->flowFormService)->getFlowFormControlStructure($columnDetailSearchParams);
                            if (!$formControlHasGrandchildren) {
                                continue;
                            }

                            // 明细二级直接放入明细下，方便处理
                            foreach ($formControlHasGrandchildren as $grandchildValue) {
                                if ($grandchildValue['control_type'] == 'detail-layout') {
                                    continue;
                                }
                                $grandchildValue['haschilen'] = 0;
                                $grandchildValue['control_parent_id'] = $value['control_parent_id']; // 用明细父级ID替换明细二级的父级ID
                                array_push($formControlTypeArray, $grandchildValue);
                            }
                        } else { // 普通明细直接记录
                            $value['haschilen'] = 0;
                            array_push($formControlTypeArray, $value);
                        }
                    }
                } else { //普通字段
                    $structureValue['haschilen'] = 0;
                    array_push($formControlTypeArray, $structureValue);
                }
            }
        }
        return array_values($formControlTypeArray);
    }

    /**
     * 默认情况下， $getType 为空，获取表单控件结构并格式化成 control_id => control_type 的格式
     * 传 getData ，明细项，会被合计到各自的父级下面，不传，会拼接成一维数组
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowFormControlStructure($param, $getType = "")
    {
        if ($getType == "getData") {
            $formControlStructure = app($this->flowFormService)->getFlowFormControlStructure(['search' => ["form_id" => [$param["form_id"]]]]);
            $formControlTypeArray = [];
            if (count($formControlStructure)) {
                foreach ($formControlStructure as $structureKey => $structureValue) {
                    // 明细控件子项
                    if ($structureValue["control_parent_id"]) {
                        $formControlTypeArray[$structureValue["control_parent_id"]][$structureValue["control_id"]] = $structureValue["control_type"];
                    } else {
                        // 明细控件
                        if ($structureValue["control_type"] == "detail-layout") {
                        } else {
                            // 普通控件
                            $formControlTypeArray[$structureValue["control_id"]] = "";
                        }
                    }
                }
            }
            return $formControlTypeArray;
        } else {
            //此处优化直接获取control_id和control_type即可
            $formControlStructure =  app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$param["form_id"]]] , 'fields'=>['control_id' , 'control_type']]);
            //使用内置函数
            return  array_column( $formControlStructure , 'control_type' , 'control_id');
        }
    }

    /**
     * 记录流程表单历史记录
     * @param  [type] $formId [description]
     * @return [type]         [description]
     */
    public function recordFlowFormHistory($formId, $data)
    {
        $formData = array_intersect_key($data, array_flip(app($this->flowFormEditionRepository)->getTableColumns()));
        $formData["form_id"] = $formId;
        $formData["edit_time"] = date("Y-m-d H:i:s", time());
        $insertResult = app($this->flowFormEditionRepository)->insertData($formData);
        if ($insertResult && isset($insertResult->id)) {
            return $insertResult->id;
        } else {
            return "";
        }
    }

    /**
     * 获取流程紧急程度名称的函数
     * @param str $InstancyType 紧急程度的数字
     * @return str 紧急程度 0：正常、1：重要、2：紧急
     */
    public function getInstancyName($InstancyType)
    {
        $InstancyName = "";
        switch ($InstancyType) {
            case '0':
                $InstancyName = trans("flow.0x030026");
                break;
            case '1':
                $InstancyName = trans("flow.0x030027");
                break;
            case '2':
                $InstancyName = trans("flow.0x030028");
                break;
            default:
                break;
        }
        return $InstancyName;
    }

    /**
     * 删除flow_run_step表的此流程数据，重新添加，代替以前的《更新flow_run_step》
     * 这是一个flowRunService里的实现，给 getFormatFlowRunStepNewData 再封装一遍
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function rebuildFlowRunStepDataServiceRealize($param)
    {
        $runId = $param["run_id"];
        // 参数里的run_id可以是数组/字符串
        if (is_array($runId)) {
            $runIdArray = $runId;
        } else {
            $runIdArray = explode(",", rtrim($runId, ","));
        }
        if ($runIdArray && count($runIdArray)) {
            foreach ($runIdArray as $key => $value) {
                $hangupArray = [];
                //获取挂起的runid
                $hangupList = app($this->flowRunStepRepository)->getHangupListByRunId($value);
                foreach ($hangupList as $v) {
                    $hangupArray[$v['user_id'].$v['flow_process']] = $v;
                }
                app($this->flowRunStepRepository)->reallyDeleteByWhere(["run_id" => [[$value], 'in']]);
                $flowRunStepNewData = app($this->flowRunProcessRepository)->getFormatFlowRunStepNewData(["run_id" => $value]);
                // 最终结果数组
                $finalStepInfo = [];
                // 外部变量，用来按照用户id收集超过一行的用户的信息
                $stepInfoGroupByUserId = [];
                $flowRunStepNewData = json_decode(json_encode($flowRunStepNewData), true);
                if (count($flowRunStepNewData) > 0) {
                	$flowRunProcessData = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_process','process_id','deliver_time','user_id','flow_serial','branch_serial','process_serial','concurrent_node_id'],['run_id'=>$runId]);
                    // 数据分组
                    foreach ($flowRunStepNewData as $stepKey => $stepValue) {
                        $flowRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_process','process_id','deliver_time','flow_serial','branch_serial','process_serial','concurrent_node_id','user_id'],['run_id'=>$runId,'user_id'=>$stepValue['user_id'],"host_flag"=>$stepValue['host_flag'],'process_id'=>$stepValue['process_id'],'flow_process'=>$stepValue['flow_process']]);
                    	$flowRunProcessInfo = $flowRunProcessInfo[0];
                    	$branchSerial = $flowRunProcessInfo['branch_serial'];
                    	if (empty($branchSerial)) {
                    		$branchSerialStep = 0;
                    		$branchSerialProcess = 0;
                    		foreach ($flowRunProcessData as $k => $v){
                    			if ($stepValue['user_id'] == $v['user_id'] && !empty($v['branch_serial'])) {
                    				if (empty($branchSerialStep)) {
                    					$branchSerialStep = $v['branch_serial'];
                    				}
                    				if (!empty($v['deliver_time'])) {
										if (empty($branchSerialProcess)) {
											$branchSerialProcess = $v['branch_serial'];
										}
                    				}
                    			}
                    		}
                    		if (!empty($branchSerialProcess)) {
                    			$branchSerial = $branchSerialProcess;
                    		} else {
                    			if (!empty($branchSerialStep)) {
                    				$branchSerial = $branchSerialStep;
                    			}
                    		}
                    	}
                    	$stepInfoGroupByUserKey = $stepValue["user_id"].$branchSerial;
                    	// $stepInfoGroupByUserKey = $stepValue["user_id"].$branchSerial.$stepValue["host_flag"];
                    	$stepInfoGroupByUserId[$stepInfoGroupByUserKey][] = $stepValue;
                    }
                }
                if (count($stepInfoGroupByUserId)) {
                	$flowNodeArray = ['node'=>[],'item'=>[],'queue'=>[]];
                	foreach ($stepInfoGroupByUserId as $groupKey => $groupValue) {
                		$flowPorcessArray = [] ;
                		$groupItem = [];
                		foreach($groupValue as $k => $v){
                				$index = $v['user_id'];
                				if (isset($v['branch_serial'])) {
                					$index .= $v['branch_serial'];
                				}
                				if(!isset($flowPorcessArray[$index])){
                					$flowPorcessArray[$index] = $k;
                					if($v['user_run_type'] == 1){
                						$processIndex = $v['process_id'];
                						if(!isset($flowNodeArray['node'][$processIndex])){
                							$flowNodeArray['node'][$processIndex] = $k;
                							$flowNodeArray['queue'][$processIndex] = $v;
                						}else{
                							if(empty($flowNodeArray['item'][$processIndex])){
                								$flowNodeArray['item'][$processIndex] = [];
                								$processKey = $flowNodeArray['node'][$processIndex];
                								$flowNodeArray['item'][$processIndex][] = $flowNodeArray['queue'][$processIndex];
                							}
                							$flowNodeArray['item'][$processIndex][] = $v;
                						}
                					}
                				}else{
                					unset($groupValue[$k]);
                				}
                		}
                		foreach($groupValue as $v){
                			$groupItem[] = $v;
                		}
                		$stepInfoGroupByUserId[$groupKey] = $groupItem;
                	}
                    foreach ($stepInfoGroupByUserId as $groupKey => $groupValue) {
                        $itemData = "";
                        if (count($groupValue) == "1") {
                            $itemData = $groupValue[0];
                        } else {
                            // 进行计算
                            $itemData = $this->getFinalFlowRunStepDataByUser($groupValue);
                        }
                        unset($itemData["rank_field"]);
                        $itemData['hangup'] = 0;
                        $itemData['cancel_hangup_time'] = "0000-00-00 00:00:00";
                        if (isset($hangupArray[$groupKey])) {
                            if ($hangupArray[$groupKey]['flow_process'] == $itemData['flow_process']) {
                                $itemData['hangup'] = $hangupArray[$groupKey]['hangup'];
                                $itemData['cancel_hangup_time'] = $hangupArray[$groupKey]['cancel_hangup_time'];
                            }
                        }
                        if (isset($itemData['branch_serial'])) {
                        	unset($itemData['branch_serial']);
                        }
                        $finalStepInfo[$groupKey] = $itemData;
                    }
                }
                app($this->flowRunStepRepository)->insertMultipleData($finalStepInfo);
            }
        }
    }

    /**
     * 功能函数，计算某个用户多次参与某个流程的情况下，flow_run_step表的正确数据
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getFinalFlowRunStepDataByUser($data)
    {
        $finalData = [];
        if (count($data)) {
        	$concurrent = false;
        	$concurrentArray = [];
        	foreach ($data as $k => $v) {
        		if(!empty($v['concurrent_node_id'])){
        			if($v['user_run_type'] == 1 && $v['host_flag']==1){
        				if(empty($concurrentArray)){
        					$concurrentArray[$v['process_id']] = $v['process_id'];
        				} else {
        					if(!isset($concurrentArray[$v['process_id']])){
        						$concurrent = true;
        					}
        				}
        			}
        		}
        	}
        	if($concurrent) {
        		$data =	app($this->flowParseService)->sortByFiled($data,'user_run_type',SORT_ASC,'host_flag',SORT_DESC,'process_id',SORT_ASC,'process_time',SORT_ASC);
        	}
            $finalData = $data[0];
            $processTimeIsNull = false;
            foreach ($data as $key => $value) {
                // 如果某个节点是未读的，那么，最终数据里面，process_time 置为null
                if ($value["process_time"] == "" || !$value["process_time"] || $value["process_time"] == "0000-00-00 00:00:00") {
                    $processTimeIsNull = true;
                }
                if ($finalData["process_id"] == $value["process_id"]) {
                    //如果主办人未办理情况下才使用主办人信息 wz
                    if ($value["host_flag"] == "1" && (empty($value["transact_time"]) || $value["transact_time"] == '0000-00-00 00:00:00')) {
                        $finalData["host_flag"] = "1";
                        // 在这里全面使用主办人的信息
                        $finalData["process_time"] = $value["process_time"];
                        $finalData["transact_time"] = $value["transact_time"];
                        $finalData["limit_date"] = $value["limit_date"];
                    }
                    if ($value["user_run_type"] == "1") {
                        $finalData["user_run_type"] = "1";
                    }
                }
            }
            if ($processTimeIsNull) {
                $finalData["process_time"] = null;
            }
        }
        return $finalData;
    }

    /**
     * 功能函数，根据流程run_id，获取form_id
     * @param  [type] $runId [description]
     * @return [type]        [description]
     */
    public function getFormIdByRunId($runId, $withTrashed = false)
    {
        return app($this->flowRunRepository)->getFormIdByRunId($runId, $withTrashed);
    }

    /**
     * 功能函数，根据流程run_id，获取flow_id
     * @param  [type] $runId [description]
     * @return [type]        [description]
     * @author dingpeng <[<email address>]>
     */
    public function getFlowIdByRunId($runId)
    {
        if ($runId && $runObject = app($this->flowRunRepository)->getDetail($runId , false , ['flow_id'])) {
            return isset($runObject->flow_id) ? $runObject->flow_id : '';
        }
        return '';
    }

    /**
     * 功能函数，根据流程run_id，获取flow_id
     * @param  [type] $runId [description]
     * @return [type]        [description]
     * @author dingpeng <[<email address>]>
     */
    public function getFlowRunDetail($runId)
    {
        if ($runId && $runObject = app($this->flowRunRepository)->getDetail($runId)) {
            return $runObject ? $runObject->toArray() : [];
        }
        return [];
    }

    /**
     * 功能函数，验证流程查看权限，流程新建页面等没有runid的情况下用到
     * 9.0函数 run_role (general/workflow/prcs_role.php)
     * 参数：flow_id,user_info
     * 返回值：
     * 空：没有权限
     * 0：流程不存在
     * 8：是首节点办理人
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function flowCreatePermissionValidation($param)
    {
        // 步骤参数
        $flowId = isset($param["flow_id"]) ? $param["flow_id"] : false;
        // 验证是否有新建此流程的权限
        if ($flowId && isset($param['user_info'])) {
            $checkUserInHeadNodeTransactUser = $this->checkUserInHeadNodeTransactUser($param['user_info'], $flowId);
            if ($checkUserInHeadNodeTransactUser) {
                return 8;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * 判断某用户是否在流程首节点的办理人范围内
     *
     * @return string 1:在范围内 0:不在范围内
     * @since  2017-11-03 创建
     *
     * @author 缪晨晨
     *
     */
    public function checkUserInHeadNodeTransactUser($userInfo, $flowId)
    {
        // 获取流程信息
        $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowId , false , ['flow_type']);
        if (!empty($flowTypeInfo) && $flowTypeInfo->flow_type) {
            if ($flowTypeInfo->flow_type == '1') {
                // 固定流程
                $nodeParam = [
                    'flow_id' => $flowId,
                    'search' => [
                        'head_node_toggle' => [1],
                    ],
                    'fields' =>['node_id']
                ];
                $firstNodeInfo = app($this->flowProcessRepository)->getFlowDefineProcessList($nodeParam);
                if (!empty($firstNodeInfo) && isset($firstNodeInfo[0]['node_id'])) {
                    $param['search']['node_id'] = $firstNodeInfo[0]['node_id'];
                    $param['fields'] = ['node_id','process_auto_get_user','process_user','process_role','process_dept'];
                    $currentNodeInfo =  app($this->flowProcessRepository)->getFlowNodeDetailByPermission( $param);
                    $currentNodeProcessUser = $this->autoGetProcessUser([],  $currentNodeInfo);
                    if ($currentNodeProcessUser != 'ALL') {
                        if (!empty($currentNodeProcessUser)) {
                            $currentNodeProcessUserId = $currentNodeProcessUser->pluck("user_id")->toArray();
                            if (!in_array($userInfo['user_id'], $currentNodeProcessUserId)) {
                                return 0;
                            } else {
                                return 1;
                            }
                        } else {
                            return 0;
                        }
                    } else {
                        return 1;
                    }
                } else {
                    return 0;
                }
            } else {
                // 自由流程
                $checkFreeFlowHeadNodeTransactUser = app($this->flowTypeRepository)->checkFreeFlowHeadNodeTransactUser($flowId, $userInfo);
                if (!empty($checkFreeFlowHeadNodeTransactUser)) {
                    return 1;
                } else {
                    return 0;
                }
            }
        } else {
            return 0;
        }
    }

    //通过一级关联人解析相关人员
    public function getUserByParse($userId, $processAutoGetUser)
    {
        $userInfo = app($this->userService)->getUserAllData($userId)->toArray();
        //部门id
        $user_dept_id = $userInfo['user_has_one_system_info']['dept_id'];
        //上级部门id
        $user_parent_dept_id = $userInfo['user_has_one_system_info']['user_system_info_belongs_to_department']['parent_id'];
        //根部门id
        $user_root_dept_id = $userInfo['user_has_one_system_info']['user_system_info_belongs_to_department']['arr_parent_id'];
        //上级user
        $user_superior = $userInfo['user_has_many_superior'];
        //下级user
        $user_subordinate = $userInfo['user_has_many_subordinate'];
        //角色信息
        $user_role_id = [];
        foreach ($userInfo['user_has_many_role'] as $key => $value) {
            $user_role_id[] = $value['role_id'];
        }
        $relation2 = "";
        if ($processAutoGetUser[0] === '0') {
            $relation2 = $processAutoGetUser[1];
        } elseif ($processAutoGetUser[0] === '1') {
            $relation2 = $processAutoGetUser[2];
        }

        switch ($relation2) { //与关联人关系项
            case '0': //创建人本人
                $autoGetUserInfo = $userId;
                return $autoGetUserInfo;
                break;
            case '1': //同一部门的所有人
                $userListBydeptId = app($this->userSystemInfoRepository)->getUserIdByDeptId([$user_dept_id] , ['user_status' => [2, '<>']]);
                $autoGetUserInfo = array_column($userListBydeptId , 'user_id');
                // $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => [$user_dept_id]]]);
                // $autoGetUserInfo = [];
                // foreach ($userListBydeptId['list'] as $key => $value) {
                //     $autoGetUserInfo[$key] = $value['user_id'];
                // }
                return $autoGetUserInfo;
                break;
            case '2': //同一角色的所有人
                $autoGetUserInfo = [];

                if (!empty($user_role_id)) {
                    $userListByroleId = app($this->userService)->userSystemList(['search' => ['role_id' => [$user_role_id]]]);
                    foreach ($userListByroleId['list'] as $key => $value) {
                        array_push($autoGetUserInfo, $value['user_id']);
                    }
                }
                return $autoGetUserInfo;
                break;
            case '3': //同一部门的某个角色
                $relation3 = "";
                if ($processAutoGetUser[0] === '0') {
                    $relation3 = $processAutoGetUser[2];
                } elseif ($processAutoGetUser[0] === '1') {
                    $relation3 = $processAutoGetUser[3];
                }

                $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => [$user_dept_id], 'role_id' => [$relation3]]]);
                $autoGetUserInfo = [];
                foreach ($userListBydeptId['list'] as $key => $value) {
                    $autoGetUserInfo[$key] = $value['user_id'];
                }
                return $autoGetUserInfo;
                break;
            case '4': //同一角色的某个部门
                $relation3 = "";
                if ($processAutoGetUser[0] === '0') {
                    $relation3 = $processAutoGetUser[2];
                } elseif ($processAutoGetUser[0] === '1') {
                    $relation3 = $processAutoGetUser[3];
                }
                $autoGetUserInfo = [];
                if (!empty($user_role_id)) {
                    $userListByroleId = app($this->userService)->userSystemList(['search' => ['role_id' => [$user_role_id], 'dept_id' => [$relation3]]]);
                    foreach ($userListByroleId['list'] as $key => $value) {
                        array_push($autoGetUserInfo, $value['user_id']);
                    }
                }
                return $autoGetUserInfo;
                break;
            case '5': //同一部门的某个角色权限级别
                $relation3 = "";
                if ($processAutoGetUser[0] === '0') {
                    $relation3 = $processAutoGetUser[2];
                } elseif ($processAutoGetUser[0] === '1') {
                    $relation3 = $processAutoGetUser[3];
                }
                $autoGetUserInfo = [];
                $role_no_list = app($this->roleRepository)->getAllRoles(['search' => ['role_no' => [$relation3]]])->toArray();
                $role_list = [];
                foreach ($role_no_list as $key => $value) {
                    $role_list[$key] = $value['role_id'];
                }
                $userListByroleId = app($this->userService)->userSystemList(['search' => ['role_id' => [$role_list], 'dept_id' => [$user_dept_id]]]);
                foreach ($userListByroleId['list'] as $key => $value) {
                    array_push($autoGetUserInfo, $value['user_id']);
                }
                return $autoGetUserInfo;
                break;
            case '6': //上级部门的所有人
                $autoGetUserInfo = [];
                if ($user_parent_dept_id === 0) {
                    $user_parent_dept_id = $user_dept_id;
                }
                if ($user_parent_dept_id != 0) {
                    $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => [$user_parent_dept_id]]]);
                    foreach ($userListBydeptId['list'] as $key => $value) {
                        $autoGetUserInfo[$key] = $value['user_id'];
                    }
                }
                return $autoGetUserInfo;
                break;
            case '7': //上级部门的某个角色
                $relation3 = "";
                if ($processAutoGetUser[0] === '0') {
                    $relation3 = $processAutoGetUser[2];
                } elseif ($processAutoGetUser[0] === '1') {
                    $relation3 = $processAutoGetUser[3];
                }
                $autoGetUserInfo = [];
                if ($user_parent_dept_id === 0) {
                    $user_parent_dept_id = $user_dept_id;
                }
                if ($user_parent_dept_id != 0) {
                    $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => [$user_parent_dept_id], 'role_id' => [$relation3]]]);
                    foreach ($userListBydeptId['list'] as $key => $value) {
                        $autoGetUserInfo[$key] = $value['user_id'];
                    }
                }
                return $autoGetUserInfo;
                break;
            case '8': //上级部门的某个角色权限级别
                $relation3 = "";
                if ($processAutoGetUser[0] === '0') {
                    $relation3 = $processAutoGetUser[2];
                } elseif ($processAutoGetUser[0] === '1') {
                    $relation3 = $processAutoGetUser[3];
                }
                $autoGetUserInfo = [];
                if ($user_parent_dept_id === 0) {
                    $user_parent_dept_id = $user_dept_id;
                }
                if ($user_parent_dept_id != 0) {
                    $role_no_list = app($this->roleRepository)->getAllRoles(['search' => ['role_no' => [$relation3]]])->toArray();
                    $role_list = [];
                    foreach ($role_no_list as $key => $value) {
                        $role_list[$key] = $value['role_id'];
                    }
                    $userListByroleId = app($this->userService)->userSystemList(['search' => ['role_id' => [$role_list], 'dept_id' => [$user_parent_dept_id]]]);
                    foreach ($userListByroleId['list'] as $key => $value) {
                        array_push($autoGetUserInfo, $value['user_id']);
                    }
                }
                return $autoGetUserInfo;
                break;
            case '9': //所在部门的根部门的所有人
                $autoGetUserInfo = [];
                if ($user_root_dept_id === '0') {
                    // 如果所在部门就是根部门，那就查所在部门的
                    $user_root_dept_id = $user_dept_id;
                }
                if (!empty($user_root_dept_id)) {
                    $root_dept_id = explode(',', $user_root_dept_id);
                    $deptIdArray = [];
                    if (count($root_dept_id) == '1') {
                        $deptIdArray = $root_dept_id;
                    } else {
                        if (isset($root_dept_id[1]) && !empty($root_dept_id[1])) {
                            $deptIdArray = [$root_dept_id[1]];
                        }
                    }
                    if (!empty($deptIdArray)) {
                        $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => $deptIdArray]]);
                        foreach ($userListBydeptId['list'] as $key => $value) {
                            $autoGetUserInfo[$key] = $value['user_id'];
                        }
                    }
                }
                return $autoGetUserInfo;
                break;
            case '10': //所在部门的根部门的某个角色
                $relation3 = "";
                if ($processAutoGetUser[0] === '0') {
                    $relation3 = $processAutoGetUser[2];
                } elseif ($processAutoGetUser[0] === '1') {
                    $relation3 = $processAutoGetUser[3];
                }
                $autoGetUserInfo = [];
                if ($user_root_dept_id === '0') {
                    // 如果所在部门就是根部门，那就查所在部门的
                    $user_root_dept_id = $user_dept_id;
                }
                if (!empty($user_root_dept_id)) {
                    $root_dept_id = explode(',', $user_root_dept_id);
                    $deptIdArray = [];
                    if (count($root_dept_id) == '1') {
                        $deptIdArray = $root_dept_id;
                    } else {
                        if (isset($root_dept_id[1]) && !empty($root_dept_id[1])) {
                            $deptIdArray = [$root_dept_id[1]];
                        }
                    }
                    if (!empty($deptIdArray)) {
                        $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => $deptIdArray, 'role_id' => [$relation3]]]);
                        foreach ($userListBydeptId['list'] as $key => $value) {
                            $autoGetUserInfo[$key] = $value['user_id'];
                        }
                    }
                }
                return $autoGetUserInfo;
                break;
            case '11': //所在部门的负责人
                $autoGetUserInfo = [];
                $userOwnDeptDirector = app($this->userService)->getUserOwnDeptDirector($userId);
                if (!empty($userOwnDeptDirector)) {
                    foreach ($userOwnDeptDirector as $key => $value) {
                        if (!empty($value['user_id'])) {
                            $autoGetUserInfo[$key] = $value['user_id'];
                        }
                    }
                }
                return $autoGetUserInfo;
                break;
            case '12': //上级
                $autoGetUserInfo = [];
                if (!empty($user_superior)) {
                    foreach ($user_superior as $key => $value) {
                        if (!empty($value['superior_has_one_user'])) {
                            $autoGetUserInfo[$key] = $value['superior_has_one_user']['user_id'];
                        }
                    }
                }
                return $autoGetUserInfo;
                break;
            case '13': //下级
                $autoGetUserInfo = [];
                if (!empty($user_subordinate)) {
                    foreach ($user_subordinate as $key => $value) {
                        if (!empty($value['user_id'])) {
                            $autoGetUserInfo[] = $value['user_id'];
                        }
                    }
                }
                return $autoGetUserInfo;
                break;
            case '14': //上级部门负责人
                $autoGetUserInfo = [];
                $modelValue = app($this->userService)->getUserSuperiorDeptDirector($userInfo["user_id"]);
                if (!empty($modelValue)) {
                     $autoGetUserInfo = collect($modelValue)->pluck("user_id")->toArray();
                }
                return $autoGetUserInfo;
                break;
            case '15': //所在部门的根部门的负责人
                $autoGetUserInfo = [];
                if ($user_root_dept_id === '0') {
                    // 如果所在部门就是根部门，那就查所在部门的
                    $user_root_dept_id = $user_dept_id;
                }

                if (!empty($user_root_dept_id)) {
                    $root_dept_id = explode(',', $user_root_dept_id);
                    $deptIdArray = [];
                    if (count($root_dept_id) == '1') {
                        $deptIdArray = $root_dept_id;
                    } else {
                        if (isset($root_dept_id[1]) && !empty($root_dept_id[1])) {
                            $deptIdArray = [$root_dept_id[1]];
                        }
                    }
                    if (!empty($deptIdArray)) {
                         $autoGetUserInfo= app('App\EofficeApp\System\Department\Entities\DepartmentDirectorEntity')->whereIn('dept_id' ,$deptIdArray )->get()->pluck('user_id')->toArray();
                    }
                }
                return $autoGetUserInfo;
                break;
            default:
                return $autoGetUserInfo = [];
                break;
        }
    }

    /**
     * 【流程运行】 解析某条流程，某个节点设置的智能办理人信息；
     * 参数：run_id:流程id;node_id:当前节点的节点id
     *
     * @param  [type]              $data [description]
     * @param  [type]              $getType [获取人员类型，等于copy时获取抄送人员]
     *
     * @return [type]                    [description]
     * @author miaochenchen
     *
     */
    public function getAutoGetUser($data, $getType = "")
    {
        $run_id = $data["run_id"];
        // 弹出框，选中的那个节点
        $node_id = $data["node_id"];
        $flow_id = $data["flow_id"];
        if (empty($node_id)) {
            return [];
        }
        if (empty($flow_id)) {
            return [];
        }
        $autoGetUserInfo = [];
        // 智能获取办理人，有某些设置下，会出现没人的情况，此时，用具体一点的消息提醒来提示客户
        $emptyReturnMessage = "";
        // $flow_id  = $runObject->flow_id;
        // 获取当前节点的节点信息
        if ($currentNodeInfo = app($this->flowProcessRepository)->getDetail($node_id , false , ['process_auto_get_user' , 'process_auto_get_copy_user' ,'get_agency', 'process_name'])) {
            $processAutoGetUser = $currentNodeInfo->process_auto_get_user;
            if ($getType == 'copy') {
                $processAutoGetUser = $currentNodeInfo->process_auto_get_copy_user;
            }
            $processAutoGetUser = explode("|", $processAutoGetUser);
            switch ($processAutoGetUser[0]) {
                case '0': //关联人 流程创建人
                    if ($run_id && $runObject = app($this->flowRunRepository)->getDetail($run_id , false , ['creator'])) {
                        $flow_creator = $runObject->creator; //创建人
                        $autoGetUserInfo = $this->getUserByParse($flow_creator, $processAutoGetUser);
                    } else {
                        $flow_creator = "";
                        $autoGetUserInfo = [];
                    }
                    break;
                case '1': //关联人 某个节点主办人
                    if (empty($run_id)) {
                        return [];
                    }
                    $autoGetUserRelationAgency = $currentNodeInfo->get_agency;
                    $selectNodeId = $processAutoGetUser[1];
                    $flowRunCurrentData = [
                        "run_id" => $run_id,
                        "search" => ["host_flag" => [1], 'flow_process' => [$selectNodeId]],
                        "order_by" => ["process_id" => "desc"],
                    ];
                    $selectNodeInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunCurrentData)->toArray();
                    if ($selectNodeInfo) {
                        // 委托人(如果有委托发生)
                        $byAgentUserId = $selectNodeInfo[0]['by_agent_id'];
                        // 被委托人(如果有委托发生)/主办人(没有委托的情况)
                        $userId = $selectNodeInfo[0]['user_id'];
                        if (!empty($userId)) {
                            // 有委托
                            if ($selectNodeInfo[0]['by_agent_id']) {
                                // 根据被委托人获取
                                if ($autoGetUserRelationAgency == "1") {
                                    $autoGetUserInfo = $this->getUserByParse($userId, $processAutoGetUser);
                                } else {
                                    // 根据委托人获取
                                    $autoGetUserInfo = $this->getUserByParse($byAgentUserId, $processAutoGetUser);
                                }
                            } else {
                                // 没有委托发生
                                $autoGetUserInfo = $this->getUserByParse($userId, $processAutoGetUser);
                            }
                        }
                    }
                    // 没人
                    if (empty($autoGetUserInfo)) {
                        // 判断，是否走过目标节点
                        $flowNodeHasRun = [
                            "run_id" => $run_id,
                            "search" => ['flow_process' => [$selectNodeId]],
                        ];
                        $flowNodeHasRunCount = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowNodeHasRun)->count();
                        // 关联节点的信息
                        $relationNodeInfo = $this->getFlowNodeDetail($selectNodeId);
                        $relationNodeInfo = $relationNodeInfo->toArray();
                        if (count($relationNodeInfo)) {
                            // 走过
                            if ($flowNodeHasRunCount) {
                                // 办理方式
                                $transactType = $relationNodeInfo["process_transact_type"];
                                if ($transactType == "3") {
                                    // 目标节点设置了智能获取办理人员：关联节点[:process_name]的主办人。根据智能获取条件和关联节点的办理方式设置，目标节点没有符合条件的办理人，请与系统管理员联系！
                                    if ($getType == 'copy') {
                                        $emptyReturnMessage = trans("flow.0x030149", ['select_process_name' => $currentNodeInfo->process_name, 'process_name' => $relationNodeInfo["process_name"]]);
                                    } else {
                                        $emptyReturnMessage = trans("flow.0x030057", ['select_process_name' => $currentNodeInfo->process_name, 'process_name' => $relationNodeInfo["process_name"]]);
                                    }
                                }
                            } else {
                                // 没有走过
                                // 目标节点设置了智能获取办理人员：关联节点[:process_name]的主办人。流程尚未从关联节点流转过，根据智能获取条件，目标节点没有符合条件的办理人，请与系统管理员联系！
                                if ($getType == 'copy') {
                                    $emptyReturnMessage = trans("flow.0x030150", ['select_process_name' => $currentNodeInfo->process_name, 'process_name' => $relationNodeInfo["process_name"]]);
                                } else {
                                    $emptyReturnMessage = trans("flow.0x030058", ['select_process_name' => $currentNodeInfo->process_name, 'process_name' => $relationNodeInfo["process_name"]]);
                                }
                            }
                        }
                    }
                    break;
                case '2': //关联人 人力资源
                    if (!empty($processAutoGetUser[1]) && !empty($processAutoGetUser[2]) && $processAutoGetUser[2] != 'NaN') {
                        $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => [$processAutoGetUser[1]], 'role_id' => [$processAutoGetUser[2]]]]);
                        foreach ($userListBydeptId['list'] as $key => $value) {
                            $autoGetUserInfo[$key] = $value['user_id'];
                        }
                    } elseif (!empty($processAutoGetUser[1]) && empty($processAutoGetUser[2])) {
                        $userListBydeptId = app($this->userService)->userSystemList(['search' => ['dept_id' => [$processAutoGetUser[1]]]]);
                        foreach ($userListBydeptId['list'] as $key => $value) {
                            $autoGetUserInfo[$key] = $value['user_id'];
                        }
                    }
                    // elseif (empty($processAutoGetUser[1]) && !empty($processAutoGetUser[2])) {
                    //     $userListBydeptId = app($this->userService)->userSystemList(['search'=>['role_id'=>[$processAutoGetUser[2]]]]);
                    //     foreach ($userListBydeptId['list'] as $key => $value) {
                    //         $autoGetUserInfo[$key] = $value['user_id'];
                    //     }
                    // }
                    break;
                case '3': //关联人 流程表单中的某个字段，前端不能设置[明细字段]作为关联
                    if (empty($run_id)) {
                        return [];
                    }
                    $autoGetUserRelationFormControl = $processAutoGetUser[1];
                    $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flow_id);
                    $formId = $flowTypeInfo->form_id;
                    //判断控件类型
                    $_formControlStructure = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$formId], 'control_id' => [$autoGetUserRelationFormControl]]]);
                    if ($_formControlStructure && isset($_formControlStructure[0])) {
                        if ($_formControlStructure[0]['control_type'] == 'select') {
                            $autoGetUserRelationFormControl = $processAutoGetUser[1] . '_TEXT';
                        }
                    }

                    $flowRunFormDataParams = [
                        "run_id" => $run_id,
                        "form_id" => $formId,
                        "fields" => [$autoGetUserRelationFormControl],
                    ];
                    $flowRunFormDatas = $this->getFlowRunFormData($flowRunFormDataParams);
                    $autoUserStr = "";
                    if (count($flowRunFormDatas)) {
                        foreach ($flowRunFormDatas as $flowRunFormDataKey => $flowRunFormDataValue) {
                            $autoUserStr = isset($flowRunFormDataValue[$autoGetUserRelationFormControl]) ? $flowRunFormDataValue[$autoGetUserRelationFormControl] : "";
                        }
                    }

                    if ($autoUserStr == "") {
                    } else {
                        $autoUserStr = strip_tags($autoUserStr);
                        if (strpos($autoUserStr, "WV") || strpos($autoUserStr, "WV") === 0 || strpos($autoUserStr, "admin") || strpos($autoUserStr, "admin") === 0) {
                            $autoUserStr = str_replace(['[',']','"'], '', $autoUserStr);
                            $autoUserStr = explode(',', $autoUserStr);
                            foreach ($autoUserStr as $key => $value) {
                                $autoGetUserInfo[$key] = $value;
                            }
                            if (empty($autoGetUserInfo)) {
                                break;
                            }
                            // 过滤掉离职和删除的用户
                            $newAutoGetUserInfo = app($this->userRepository)->filterLeaveOffAndDeletedUserId($autoGetUserInfo);
                            if (empty($newAutoGetUserInfo)) {
                                $autoGetUserInfo = [];
                                break;
                            }
                            foreach ($autoGetUserInfo as $key => $value) {
                                if (!in_array($value, $newAutoGetUserInfo)) {
                                    unset($autoGetUserInfo[$key]);
                                }
                            }
                        } else {

                            //其余的内容，需要查询数据库
                            $autoUserStr = str_replace("'", '"', $autoUserStr);
                            $userConvert = trim(str_replace("，", ",", $autoUserStr));
                            $userConvert = trim(str_replace(" ", ",", $autoUserStr));

                            $userget = app($this->userService)->userSystemList(['search' => ['user_name' => [explode(',', $userConvert), 'in']]]);

                            $autoGetUserInfo = [];
                            if (!empty($userget['list'])) {
                                foreach ($userget['list'] as $key => $value) {
                                    $autoGetUserInfo[$key] = $value['user_id'];
                                }
                            }
                        }
                    }
                    break;
                default:
                    $autoGetUserInfo = [];
                    break;
            }
        }
        $newAutoGetUserInfo = [];
        if (is_Array($autoGetUserInfo)) {
            foreach ($autoGetUserInfo as $key => $value) {
                $newAutoGetUserInfo[]['user_id'] = $value;
            }
        } else {
            $newAutoGetUserInfo[]['user_id'] = $autoGetUserInfo;
        }
        $autoGetUserInfo = collect($newAutoGetUserInfo);
        // return $autoGetUserInfo;
        return ["autoGetUserInfo" => $autoGetUserInfo, "emptyReturnMessage" => $emptyReturnMessage];
    }

    /**
     * 【流程运行】 【流程数据】 获取某条流程的表单数据
     * 此函数，前端已不再调用。后端也不再有地方调用（原来的调用地点：智能获取办理人，第三种方式，获取表单数据）。
     *
     * @method getFlowRunningInfo
     *
     * @param  [type]             $data [description]
     *
     * @return [type]                   [description]
     */
    // function getFlowFormData($runId,$data) {
    //     // 验证
    //     $historyFlowId = isset($data["flow_id"]) ? $data["flow_id"]:false;
    //     if($historyFlowId) {
    //         $runObject = app($this->flowRunRepository)->getDetail($runId);
    //         $flowId    = $runObject->flow_id;
    //         if($historyFlowId != $flowId) {
    //             return [];
    //         }
    //     }
    //     // 返回的结果示例
    //     // $data = [
    //     //     "DATA_1"=> "1",
    //     //     "DATA_2"=> "2",
    //     //     "DATA_3"=> "3",
    //     //     "DATA_4"=> [
    //     //         [
    //     //             "DATA_4_1"=> "xxxx_1_1",
    //     //             "DATA_4_2"=> "xxxx_1_2"
    //     //         ],[
    //     //             "DATA_4_1"=> "xxxx_2_1",
    //     //             "DATA_4_2"=> "xxxx_2_2"
    //     //         ],[
    //     //             "DATA_4_1"=> "xxxx_3_1",
    //     //             "DATA_4_2"=> "xxxx_3_2"
    //     //         ]
    //     //     ],
    //     //     "DATA_5"=> "系统管理员",
    //     //     "DATA_6"=> "value_test_6",
    //     //     "DATA_7"=> "value_test_7",
    //     //     "DATA_8"=> "value_test_8",
    //     //     "DATA_9"=> [
    //     //         [
    //     //             "DATA_9_4"=> "asdasdA_9_4"
    //     //         ]
    //     //     ]
    //     // ];
    //     // 返回值
    //     $flowRunFormData = "";
    //     $formId = $this->getFormIdByRunId($runId);
    //     // 获取表单数据
    //     if($formId) {
    //         // 获取表单控件结构
    //         $formControlTypeArray = $this->getFlowFormControlStructure(["form_id" => $formId],"getData");
    //         $returnData = [];
    //         $fields = [];
    //         foreach ($formControlTypeArray as $controlKey => $controlInfo) {
    //             if(is_array($controlInfo)) {
    //                 $flowRunFormDataDetailLayoutParams = ["run_id" => $runId,"form_id" => $formId.str_replace("DATA", "", $controlKey),"fields" => $controlInfo];
    //                 $flowRunFormDetailLayoutData = $this->getFlowRunFormData($flowRunFormDataDetailLayoutParams);
    //                 $returnData[$controlKey] = $flowRunFormDetailLayoutData;
    //             } else {
    //                 $fields[$controlKey] = "";
    //             }
    //         }
    //         if(count($fields)) {
    //             // 普通控件获值
    //             $flowRunFormDataParams = ["run_id" => $runId,"form_id" => $formId,"fields" => $fields];
    //             $flowRunFormData = $this->getFlowRunFormData($flowRunFormDataParams);
    //             if(count($flowRunFormData)) {
    //                 $flowRunFormData = $flowRunFormData[0];
    //                 if(count($flowRunFormData)) {
    //                     foreach ($flowRunFormData as $key => $value) {
    //                         $returnData[$key] = $value;
    //                     }
    //                 }
    //             }
    //         }
    //         return $returnData;
    //     }
    //     return [];
    // }
    /*
     * 办理页面子流程列表
     */
    public function getRelationFlowData($runId, $own , $getType = 'data' , $param = [])
    {
        if (empty($runId)) {
            return [];
        }
        if ($getType == 'data') {
            // 判断流程查看权限
            $verifyParams = [
                "type" => 'view',
                'run_id' => $runId,
                'user_id' => $own['user_id'],
                'request' => $param
            ];
            if (!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyParams)) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        $result = [];
        if ($sunFlowRunIds = app($this->flowRunProcessRepository)->getFlowRunProcessList(['fields' => ['sub_flow_run_ids'], 'search' => ['run_id' => [$runId]]])) {
            $sunFlowRunIds = $sunFlowRunIds->toArray();
            $parentFlow = app($this->flowRunRepository)->getDetail($runId , 'false' , ['parent_id']);
            $runIds = "";
            $pid = '';
            if ($parentFlow && isset($parentFlow->parent_id) &&  !empty($parentFlow->parent_id)) {
                $parentArray = explode(',', $parentFlow->parent_id);
                $parentFlow->parent_id = $parentArray[0];
                $runIds .= $parentFlow->parent_id.",";
                $pid = $parentFlow->parent_id;
            }
            if (count($sunFlowRunIds)) {
                foreach ($sunFlowRunIds as $key => $value) {
                    if ($value["sub_flow_run_ids"]) {
                        $runIds .= $value["sub_flow_run_ids"] . ",";
                    }
                }
            }
            if (!empty($runIds)) {
                $runIds = explode(',', trim($runIds, ","));
                $where =[
                         'search' => ["flow_run.run_id" => [$runIds, 'in']  ] ,
                         ];

                $result = app($this->flowRunRepository)->getRelationRunList($where);
                if (!empty($result)) {
                    foreach ($result as $runKey => $runValue) {
                        if ($runValue['run_id'] == $pid ) {
                            $result[$runKey]['relation_flow_type'] = trans('flow.main_flow');
                        } else {
                            $result[$runKey]['relation_flow_type'] = trans('flow.sub_flow');
                        }
                        $flowRunProcess = $runValue['flow_run_has_many_flow_run_process'] ?? [];
                        foreach ($flowRunProcess as $processKey => $processValue) {
                            if ($processValue['user_id'] == $own['user_id'] && $processValue['user_last_step_flag']==1 && $processValue['user_run_type'] == 1) {
                                $result[$runKey]['go_type'] = 'handle';
                                $result[$runKey]['flow_run_process_id'] = $processValue['flow_run_process_id'];
                                $process_name =  $processValue['flow_run_process_has_one_flow_process']['process_name'] ?? '';
                                $result[$runKey]['handle_array'][] = ['flow_run_process_id' => $processValue['flow_run_process_id'] , 'flow_process' =>$processValue['flow_process'] ,'process_name' => $process_name ];
                            }
                        }
                        $result[$runKey]['go_type'] = $result[$runKey]['go_type']?? 'view';
                    }
                }
            }
        }
        if ($getType == 'count'){
            return count($result);
        }
        return $result;
    }

    /**
     *  获取出口条件中涉及目标控件的数据
     */
    public function getUseFormControls($data)
    {
        if (!isset($data['form_id']) || empty($data['form_id'])) {
            return [];
        }
        if (!isset($data['control_id']) || empty($data['control_id'])) {
            return [];
        }
        $formId = $data['form_id'];
        $controlId = $data['control_id'];

        $result = [];
        $flowList = app($this->flowTypeRepository)->getFlowListForFormId($formId);
        $replaceResult = "";
        if ($flowList) {
            $flowList = $flowList->pluck('flow_id')->toArray();
            //相关节点出口条件
            if (!empty($flowList)) {
                if ($controlResult = app($this->flowTermRepository)->getFlowNodeList($flowList)) {
                    if (!empty($controlResult)) {
                        foreach ($controlResult as $condition_value) {
                            $changeControl = '"' . $controlId . '"';
                            if (preg_match("/" . $changeControl . "/", $condition_value['condition'])) {
                                array_push($result, $condition_value);
                            }
                        }
                    }
                }
            }
        }
        foreach ($result as $key => $value) {
            if ($value['flow_id']) {
                $flowName = app($this->flowTypeRepository)->getDetail($value['flow_id']);
                if ($flowName) {
                    $result[$key]['flow_name'] = $flowName->flow_name;
                } else {
                    unset($result[$key]);
                }
            }
            if ($value['source_id']) {
                $sourceProcessName = app($this->flowProcessRepository)->getDetail($value['source_id']);
                if ($sourceProcessName) {
                    $result[$key]['source_name'] = $sourceProcessName->process_name;
                } else {
                    unset($result[$key]);
                }
            }
            if ($value['target_id']) {
                $targetProcessName = app($this->flowProcessRepository)->getDetail($value['target_id']);
                if ($targetProcessName) {
                    $result[$key]['target_name'] = $targetProcessName->process_name;
                } else {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }

    /**
     * 【流程运转】 根据子流程设置，判断流程是否可提交
     * @param  [type] $param  $param['node_id'] = 3406;
     * $param['process_id'] = '1';
     * @return [type]        [
     *                         ['run_name'=>'xxxxx','run_id'=>xxx]
     *                       ]
     */
    public function getUnfinishedSunflowList($param)
    {
        if (!isset($param['run_id']) || empty($param['run_id'])) {
            return [];
        }
        if (!isset($param['process_id']) || empty($param['process_id'])) {
            return [];
        }
        $unfinishedSunflowList = [];
        $search = ['run_id' => [$param['run_id']], 'process_id' => [$param['process_id'] +1]];
        if (!empty($param['flow_process'])) {
        	$search = ['run_id' => [$param['run_id']],'flow_process' => [$param['flow_process']]];
        }
        $dbData = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => $search , 'fields' => ['sub_flow_run_ids', 'flow_process','origin_process'], 'returntype' => 'object']);
        if (!empty($dbData)) {
        	foreach($dbData as $value) {
        		$sunflow_run_ids = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$param['run_id']],'flow_process' => [$value->origin_process],'host_flag' => [1]], 'fields' => ['sub_flow_run_ids', 'flow_process','host_flag','process_id'], 'returntype' => 'first']);
        		if ($sunflow_run_ids) {
        			$sunflow_run_ids = $sunflow_run_ids->toArray();
        			$sunflow_run_id = $sunflow_run_ids['sub_flow_run_ids'];
        			$nodeId = $sunflow_run_ids['flow_process'];
        			if ($sunflow_run_id && $nodeId) {
        				$sunflow_run_id = explode(',', $sunflow_run_id);
        				$unfinishedSunflow = app($this->flowSunWorkflowRepository)->getUnfinishedSunflowList($sunflow_run_id, $nodeId);
						if (!empty($unfinishedSunflow)) {
							foreach($unfinishedSunflow as $v){
								$unfinishedSunflowList[] = $v;
							}
						}
        			}
        		}
        	}
        }
        return $unfinishedSunflowList;
    }

    /**
     * 人民币小写转大写
     *
     * @param string $number 数值
     * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
     * @param bool $is_round 是否对小数进行四舍五入
     * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
     *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
     * @return string
     */
    public function digitUppercase($number = 0, $int_unit = '元', $is_round = false, $is_extra_zero = false)
    {
        if ($number) {
            $minusFlag = false;
            if (substr($number, 0, 1) == '-') {
                $number = substr($number, 1);
                $minusFlag = true;
            }
            if (!is_numeric($number)) {
                return $number;
            }
        } else {
            if ($number === '') {
                return '';
            }
            return '零' . $int_unit . '整';
        }
        // 将数字切分成两段
        $parts = explode('.', $number, 2);
        $int = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec = isset($parts[1]) ? strval($parts[1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2) {
            $dec = $is_round
                ? substr(strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1)
                : substr($parts[1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if (empty($int) && empty($dec)) {
            return '零' . $int_unit . '整';
        }

        // 定义
        $chs = array('0', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        $uni = array('', '拾', '佰', '仟');
        $dec_uni = array('角', '分');
        $exp = array('', '万');
        $res = '';

        // 整数部分从右向左找
        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
                $u = $int[$i] > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int[$i]] . $u . $str;
            }
            //echo $str."|".($k - 2)."<br>";
            $str = rtrim($str, '0'); // 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if (!isset($exp[$k])) {
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }
            $u2 = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');

        // 小数部分从左向右找
        if (!empty($dec)) {
            $res .= $int_unit;

            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero) {
                if (substr($int, -1) === '0') {
                    $res .= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) {
                $u = $dec[$i] > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec[$i]] . $u;
            }
            $res = rtrim($res, '0'); // 去掉末尾的0
            $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
        } else {
            if (empty($res)) {
                $res .= '零' . $int_unit . '整';
            } else {
                $res .= $int_unit . '整';
            }
        }
        if ($minusFlag) {
            $res = '(负)' . $res;
        }
        return $res;
    }

    /**
     * 封装一遍 flowProcessRepository 的 getFlowNodeDetail 函数，获取到form_id再调用，用来排序 flowControlRequired
     * @param $nodeId
     * @param int $formId
     * @return \Illuminate\Support\Collection
     */
    public function getFlowNodeDetail($nodeId, $formId = 0)
    {
        $formId = $formId ? $formId : $this->getFormIdByNodeId($nodeId);
        if ($formId) {
            return app($this->flowProcessRepository)->getFlowNodeDetail($nodeId, $formId);
        } else {
            return collect([]);
        }
    }

    /**
     * 功能函数，根据node_id，获取form_id
     * @param  [type] $runId [description]
     * @return [type]        [description]
     */
    public function getFormIdByNodeId($nodeId)
    {
        $nodeInfo = app($this->flowProcessRepository)->getDetail($nodeId , false , ['flow_id']);
        if ($nodeInfo) {
            $flowId = $nodeInfo->flow_id;
            $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowId);
            return object_get($flowTypeInfo, 'form_id', '');
        } else {
            return "";
        }
    }

    // 根据flow_id获取form_id
    public function getFormIdByFlowId($flowId)
    {
        $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flowId, false, ['form_id']);
        return object_get($flowTypeInfo, 'form_id', '');
    }

    /**
     * 判断当前流程是否被其他人查看过
     * 被看过，返回 true，没看过，返回 false
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function verifyFlowHasOtherPersonVisited($param)
    {
        $runId = isset($param["run_id"]) ? $param["run_id"] : "";
        $processId = isset($param["process_id"]) ? $param["process_id"] : "";
        $editTime = isset($param["edit_time"]) ? $param["edit_time"] : "";
        // 当前用户
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        // 默认，没看过，返回false
        $flag = false;
        if ($runId && $userId) {
            // 先查 flow_run_process
            $flowRunProcessParam = [
                "run_id" => $runId,
                "search" => ["user_id" => [$userId, "!="], "process_id" => [$processId], "last_visited_time" => [$editTime, ">"]],
                "whereRaw" => ["process_time IS NOT NULL"],
            ];
            if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessParam)) {
                if ($flowRunProcessObject->count()) {
                    // 有查到 说明被看过，返回true
                    $flag = true;
                    return $flag;
                }
            }
            // // 查 flow_copy
            // $flowCopyParam = [
            //     "returntype" => "object",
            //     "search" => ["by_user_id" => [$userId,"!="],"run_id" => [$runId],"receive_time" => ["read"]]
            // ];
            // if($flowCopyObject = app($this->flowCopyRepository)->getFlowCopyList($flowCopyParam)) {
            //     if($flowCopyObject->count()) {
            //         $flag = true;
            //         return $flag;
            //     }
            // }
        }
        return $flag;
    }

    /**
     * 获取固定流程某个节点的字段控制信息
     *
     * @method getFlowNodeControlOperationDetail
     *
     * @param string $nodeId [节点ID]
     * @param string $formId [表单ID]
     *
     * @return array   返回结果
     * @since  2019-07-31 创建
     *
     * @author 缪晨晨
     *
     */
    public function getFlowNodeControlOperation($nodeId, $formId)
    {
        return app($this->flowProcessRepository)->getFlowNodeControlOperationDetail($nodeId, $formId);
    }

    /**
     * 获取流程字段控制
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getFlowFormControlOperation($param)
    {
        $nodeId = isset($param["node_id"]) ? $param["node_id"] : "";
        $flowId = isset($param["flowId"]) ? $param["flowId"] : "";
        $formId = isset($param["formId"]) ? $param["formId"] : "";
        $headNodeToggle = isset($param["headNodeToggle"]) ? $param["headNodeToggle"] : "";
        if ($nodeId && $formId) {
            if (Redis::hexists('fixed_flow_node_field_control_operation', $nodeId)) {
                return unserialize(Redis::hget('fixed_flow_node_field_control_operation', $nodeId));
            }
            // 固定流程
            $controlOperationInfo = [];
            if ($detailResult = $this->getFlowNodeControlOperation($nodeId, $formId)) {
                // 处理 flowProcessControlOperation
                if (isset($detailResult->flowProcessHasManyControlOperation) && count($detailResult->flowProcessHasManyControlOperation)) {
                    $controlHasManyOperation = $detailResult->flowProcessHasManyControlOperation;
                    // 处理新添加控件没有字段控制信息问题
                    $controlInfo = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ['form_id' => [$formId]], 'fields' => ['control_id']]);
                    if ($controlInfo) {
                        foreach ($controlInfo as $key => $value) {
                            $controlOperationInfo[$value['control_id']] = [];
                        }
                    }
                    if (count($controlHasManyOperation)) {
                        foreach ($controlHasManyOperation as $key => $value) {
                            $controlId = (isset($value->control_id) && $value->control_id) ? $value->control_id : "";
                            if ($controlId) {
                                $controlOperationInfo[$controlId] = $value->controlOperationDetail->pluck("operation_type")->toArray();
                            }
                        }
                    }
                }
            }
            Redis::hset('fixed_flow_node_field_control_operation', $nodeId, serialize($controlOperationInfo));
            return $controlOperationInfo;
        } else {
            // 自由流程
            $freeFlowControlOperation = [];
            if ($flowId && $formId) {
                $requiredInfo = [];
                if ($headNodeToggle == "1") {
                    //自由流程首节点必填设置
                    $requiredInfo = app($this->flowRequiredForFreeFlowRepository)->getList($flowId);
                    if ($requiredInfo) {
                        $requiredInfo = $requiredInfo->pluck('control_id')->toArray();
                    }
                }
                // 如果没设置必填，会出现 node_operation 为空的情况，处理一下
                // 取表单控件id对应控件类型
                $formControlTypeInfo = $this->getFlowFormControlStructure(["form_id" => $formId]);
                $controlOperationNew = [];
                if (!empty($formControlTypeInfo)) {
                    $formControlTypeInfo["attachment"] = "";
                    $formControlTypeInfo["feedback"] = "";
                    $formControlTypeInfo["document"] = "";
                    foreach ($formControlTypeInfo as $key => $controlType) {
                        $operationItem = [];
                        if (array_search($key, $requiredInfo) !== false) {
                            array_push($operationItem, "required");
                        }
                        $freeFlowControlOperation[$key] = $operationItem;
                    }
                }
            }
        }
        return $freeFlowControlOperation;
    }

    /**
     * 获取流程字段控制必填条件解析
     * @param $param
     * @return array|mixed
     */
    public function getFlowFormControlOperationCondition($param)
    {
        $nodeId = isset($param["node_id"]) ? $param["node_id"] : "";
        $flowId = isset($param["flowId"]) ? $param["flowId"] : "";
        $formId = isset($param["formId"]) ? $param["formId"] : "";
        $headNodeToggle = isset($param["headNodeToggle"]) ? $param["headNodeToggle"] : "";
        if ($nodeId && $formId) {
            if (Redis::hexists('fixed_flow_node_field_control_operation_condition', $nodeId)) {
                return unserialize(Redis::hget('fixed_flow_node_field_control_operation_condition', $nodeId));
            }
            // 固定流程
            $controlOperationCondition = [];
            if ($detailResult = $this->getFlowNodeControlOperation($nodeId, $formId)) {
                // 处理 flowProcessControlOperation
                if (isset($detailResult->flowProcessHasManyControlOperation) && count($detailResult->flowProcessHasManyControlOperation)) {
                    $controlHasManyOperation = $detailResult->flowProcessHasManyControlOperation;
                    // 处理新添加控件没有字段控制信息问题
                    $controlInfo = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ['form_id' => [$formId]], 'fields' => ['control_id']]);
                    if ($controlInfo) {
                        foreach ($controlInfo as $key => $value) {
                            $controlOperationInfo[$value['control_id']] = [];
                        }
                    }
                    if (count($controlHasManyOperation)) {
                        foreach ($controlHasManyOperation as $key => $value) {
                            $controlId = (isset($value->control_id) && $value->control_id) ? $value->control_id : "";
                            if ($controlId) {
                                $operationType = $value->controlOperationDetail->pluck("operation_type")->toArray();
                                $operationCondition = $value->controlOperationDetail->pluck('operation_condition')->toArray();
                                $key = array_search('condition_required', $operationType);
                                if ($key !== false) {
                                    $controlOperationCondition[$controlId]['condition_required'] = $operationCondition[$key];
                                }
                                $key = array_search('control_required', $operationType);
                                if ($key !== false) {
                                    $controlOperationCondition[$controlId]['control_required'] = json_decode($operationCondition[$key], true);
                                }
                            }
                        }
                    }
                }
            }
            Redis::hset('fixed_flow_node_field_control_operation_condition', $nodeId, serialize($controlOperationCondition));
            // if (empty($controlOperationCondition)) {
            //     return (object) null;
            // }
            return $controlOperationCondition;
        } else {
            // 自由流程
            $freeFlowControlOperation = [];
            if ($flowId && $formId) {
                $requiredInfo = [];
                if ($headNodeToggle == "1") {
                    //自由流程首节点必填设置
                    $requiredInfo = app($this->flowRequiredForFreeFlowRepository)->getList($flowId);
                    if ($requiredInfo) {
                        $requiredInfo = $requiredInfo->pluck('control_id')->toArray();
                    }
                }
                // 如果没设置必填，会出现 node_operation 为空的情况，处理一下
                // 取表单控件id对应控件类型
                $formControlTypeInfo = $this->getFlowFormControlStructure(["form_id" => $formId]);
                $controlOperationNew = [];
                if (!empty($formControlTypeInfo)) {
                    $formControlTypeInfo["attachment"] = "";
                    $formControlTypeInfo["feedback"] = "";
                    $formControlTypeInfo["document"] = "";
                    foreach ($formControlTypeInfo as $key => $controlType) {
                        $operationItem = [];
                        if (array_search($key, $requiredInfo) !== false) {
                            array_push($operationItem, "required");
                        }
                        $freeFlowControlOperation[$key] = $operationItem;
                    }
                }
            }
        }
        return $freeFlowControlOperation;
    }
    /**
     * 【流程运行】 固定流程判断出口条件是否满足
     *
     * @method verifyFlowFormOutletCondition
     *
     * @param  [type]             $condition     [出口条件]
     * @param  [type]             $formData      [流程表单数据]
     * @param  [type]             $flowOtherInfo [流程其他参数]
     *
     * @return [type]                   [description]
     */
    public function verifyFlowFormOutletCondition($condition, $formData, $flowOtherInfo = [], $validate = false, $newFlow = false)
    {
        if (!$condition) {
            return true;
        } else {
            $matchList = [];
            $controlArray = [];
            $isCountersign = false;
            $htmlEntityDecode = [
                '&gt;' => '>',
                '&lt;' => '<',
                '&gt;=' => '>=',
                '&lt;=' => '<=',
            ];
            $condition = str_replace('&amp;', '&', $condition); // 将转义后的&还原，前端需要转义这个字符，不然在编辑器里输入这个字符后回车异常
            $regexStr = '/<(\S*?) [^>]*>(.*?)<\/\1>/';
            preg_match_all($regexStr, $condition, $matchArray);
            if (!empty($matchArray) && isset($matchArray[0]) && isset($matchArray[2])) {
                foreach ($matchArray[0] as $key => $value) {
                    $matchHtml = $value;
                    $matchValue = isset($matchArray[2][$key]) ? $matchArray[2][$key] : '';
                    // 控件
                    if (strpos($matchHtml, 'control') !== false) {
                        preg_match('/id=[\'"](.*?)[\'"]/', $matchHtml, $idMatchArray);

                        if (!empty($idMatchArray)) {
                            $idMatchSonArray = explode('_', $idMatchArray[1]);

                            $idValue = "";
                            if (count($idMatchSonArray) == 3) {
                                if ($newFlow == true) {
                                    // 明细合计项
                                    if (isset($formData[$idMatchArray[1] . '_amount'])) {
                                        $idValue = $formData[$idMatchArray[1] . '_amount'] ? $formData[$idMatchArray[1] . '_amount'] : 0;
                                        $controlArray[] = $idMatchArray[1];
                                    } else {
                                        $controlType = '';
                                        if (isset($flowOtherInfo['form_structure'][$idMatchArray[1]])) {
                                            if (isset($flowOtherInfo['form_structure'][$idMatchArray[1]]['control_type'])) {
                                                $controlType = $flowOtherInfo['form_structure'][$idMatchArray[1]]['control_type'];
                                            } else {
                                                $controlType = is_string($flowOtherInfo['form_structure'][$idMatchArray[1]]) ? $flowOtherInfo['form_structure'][$idMatchArray[1]] : '';
                                            }
                                        } else if (!empty($flowOtherInfo['form_structure']) && is_array($flowOtherInfo['form_structure'])) {
                                            foreach ($flowOtherInfo['form_structure'] as $formStructureKey => $formStructureValue) {
                                                if (isset($formStructureValue['control_id']) && $formStructureValue['control_id'] == $idMatchArray[1]) {
                                                    $controlType = $formStructureValue['control_type'] ?? '';
                                                    break;
                                                }
                                            }
                                        }
                                        if (isset($formData[$idMatchArray[1] . '_TEXT'])) {
                                            if (
                                                isset($formData[$idMatchArray[1]])
                                                && ((is_array($formData[$idMatchArray[1]]) && !empty($formData[$idMatchArray[1]]))
                                                || (!is_array($formData[$idMatchArray[1]]) && $formData[$idMatchArray[1]] !== ''))) {
                                                if($formData[$idMatchArray[1] . '_TEXT'] !== ''){
                                                    if(!is_array($formData[$idMatchArray[1] . '_TEXT'])){
                                                        $idValue = $formData[$idMatchArray[1] . '_TEXT'];
                                                    }else{
                                                        $idValue = implode(",",$formData[$idMatchArray[1] . '_TEXT']);
                                                    }
                                                } else {
                                                    $idValue = "";
                                                }
                                                $controlArray[] = $idMatchArray[1] . "_TEXT";
                                            } else {
                                                $idValue = "";
                                                $controlArray[] = $idMatchArray[1];
                                            }
                                        } else if (($controlType == 'countersign' || isset($formData[$idMatchArray[1].'_COUNTERSIGN']) || isset($formData[$idMatchArray[1]][0]['countersign_content'])) && isset($formData[$idMatchArray[1]])) {
                                            $countersignContent = '';
                                            if (isset($formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content']) && $formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content'] !== '') {
                                                $countersignContent =  preg_replace('/<img.*?>/', '#img', $formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content']);
                                                $countersignContent = preg_replace('/<.*?>/', '' ,$countersignContent);
                                            } else if (is_array($formData[$idMatchArray[1]])) {
                                                if (count($formData[$idMatchArray[1]]) > 0) {
                                                    foreach ($formData[$idMatchArray[1]] as $idMatchKey => $idMatchValue) {
                                                        if ($idMatchValue['countersign_user_id'] == $flowOtherInfo['user_id'] && isset($flowOtherInfo['process_id']) && $idMatchValue['process_id'] == $flowOtherInfo['process_id']) {
                                                            $countersignContent = $idMatchValue["countersign_content"];
                                                            $countersignContent =  preg_replace('/<img.*?>/', '#img', $countersignContent);
                                                            $countersignContent = preg_replace('/<.*?>/', '', $countersignContent);
                                                        }
                                                    }
                                                }
                                            } else {
                                                $formData[$idMatchArray[1]] = preg_replace('/<img.*?>/', '#img', $formData[$idMatchArray[1]]);
                                                $countersignContent = preg_replace('/<.*?>/', '', $formData[$idMatchArray[1]]);
                                            }
                                            $idValue = trim($countersignContent);
                                        } else {
                                            $idValue = isset($formData[$idMatchArray[1]]) ? preg_replace('/<.*?>/', '', $formData[$idMatchArray[1]]) : '';
                                            if (is_array($idValue)) {
                                                $idValue = implode(',', $idValue);
                                            }
                                            $controlArray[] = $idMatchArray[1];
                                        }
                                    }
                                } else {
                                    // 明细合计项
                                    if (isset($formData[$idMatchArray[1] . '_amount'])) {
                                        $idValue = $formData[$idMatchArray[1] . '_amount'] ? $formData[$idMatchArray[1] . '_amount'] : 0;
                                        $controlArray[] = $idMatchArray[1];
                                    } else if (isset($formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1]]['amount'][$idMatchArray[1]])) {
                                        $idValue = $formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1]]['amount'][$idMatchArray[1]] ? $formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1]]['amount'][$idMatchArray[1]] : 0;
                                        $controlArray[] = $idMatchArray[1];
                                    } else if (isset($formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1] . '_amount'][$idMatchArray[1]])) {
                                        $idValue = $formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1] . '_amount'][$idMatchArray[1]] ? $formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1] . '_amount'][$idMatchArray[1]] : 0;
                                        $controlArray[] = $idMatchArray[1];
                                    } else {
                                        $controlType = '';
                                        if (isset($flowOtherInfo['form_structure'][$idMatchArray[1]])) {
                                            if (isset($flowOtherInfo['form_structure'][$idMatchArray[1]]['control_type'])) {
                                                $controlType = $flowOtherInfo['form_structure'][$idMatchArray[1]]['control_type'];
                                            } else {
                                                $controlType = is_string($flowOtherInfo['form_structure'][$idMatchArray[1]]) ? $flowOtherInfo['form_structure'][$idMatchArray[1]] : '';
                                            }
                                        } else if (!empty($flowOtherInfo['form_structure']) && is_array($flowOtherInfo['form_structure'])) {
                                            foreach ($flowOtherInfo['form_structure'] as $formStructureKey => $formStructureValue) {
                                                if (isset($formStructureValue['control_id']) && $formStructureValue['control_id'] == $idMatchArray[1]) {
                                                    $controlType = $formStructureValue['control_type'] ?? '';
                                                    break;
                                                }
                                            }
                                        }
                                        if (isset($formData[$idMatchArray[1] . '_TEXT'])) {
                                            if (
                                                isset($formData[$idMatchArray[1]])
                                                && ((is_array($formData[$idMatchArray[1]]) && !empty($formData[$idMatchArray[1]]))
                                                || (!is_array($formData[$idMatchArray[1]]) && $formData[$idMatchArray[1]] !== ''))) {
                                                if($formData[$idMatchArray[1] . '_TEXT'] !== ''){
                                                    if(!is_array($formData[$idMatchArray[1] . '_TEXT'])){
                                                        $idValue = $formData[$idMatchArray[1] . '_TEXT'];
                                                    }else{
                                                        $idValue = implode(",",$formData[$idMatchArray[1] . '_TEXT']);
                                                    }
                                                } else {
                                                    $idValue = "";
                                                }
                                                $controlArray[] = $idMatchArray[1] . "_TEXT";
                                            } else {
                                                $idValue = "";
                                                $controlArray[] = $idMatchArray[1];
                                            }
                                        } else if (($controlType == 'countersign' || isset($formData[$idMatchArray[1].'_COUNTERSIGN']) || isset($formData[$idMatchArray[1]][0]['countersign_content'])) && isset($formData[$idMatchArray[1]])) {
                                            $countersignContent = '';
                                            if (isset($formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content']) && $formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content'] !== '') {
                                                $countersignContent =  preg_replace('/<img.*?>/', '#img', $formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content']);
                                                $countersignContent = preg_replace('/<.*?>/', '' ,$countersignContent);
                                            } else if (is_array($formData[$idMatchArray[1]])) {
                                                if (count($formData[$idMatchArray[1]]) > 0) {
                                                    foreach ($formData[$idMatchArray[1]] as $idMatchKey => $idMatchValue) {
                                                        if ($idMatchValue['countersign_user_id'] == $flowOtherInfo['user_id'] && isset($flowOtherInfo['process_id']) && $idMatchValue['process_id'] == $flowOtherInfo['process_id']) {
                                                            $countersignContent = $idMatchValue["countersign_content"];
                                                            $countersignContent =  preg_replace('/<img.*?>/', '#img', $countersignContent);
                                                            $countersignContent = preg_replace('/<.*?>/', '', $countersignContent);
                                                        }
                                                    }
                                                }
                                            } else {
                                                $formData[$idMatchArray[1]] = preg_replace('/<img.*?>/', '#img', $formData[$idMatchArray[1]]);
                                                $countersignContent = preg_replace('/<.*?>/', '', $formData[$idMatchArray[1]]);
                                            }
                                            $idValue = trim($countersignContent);
                                        } else {
                                            $idValue = isset($formData[$idMatchArray[1]]) ? preg_replace('/<.*?>/', '', $formData[$idMatchArray[1]]) : '';
                                            if (is_array($idValue)) {
                                                $idValue = implode(',', $idValue);
                                            }
                                            $controlArray[] = $idMatchArray[1];
                                        }
                                    }
                                }
                            } else {
                                $controlType = '';
                                if (isset($flowOtherInfo['form_structure'][$idMatchArray[1]])) {
                                    if (isset($flowOtherInfo['form_structure'][$idMatchArray[1]]['control_type'])) {
                                        $controlType = $flowOtherInfo['form_structure'][$idMatchArray[1]]['control_type'];
                                    } else {
                                        $controlType = is_string($flowOtherInfo['form_structure'][$idMatchArray[1]]) ? $flowOtherInfo['form_structure'][$idMatchArray[1]] : '';
                                    }
                                } else if (!empty($flowOtherInfo['form_structure']) && is_array($flowOtherInfo['form_structure'])) {
                                    foreach ($flowOtherInfo['form_structure'] as $formStructureKey => $formStructureValue) {
                                        if (isset($formStructureValue['control_id']) && $formStructureValue['control_id'] == $idMatchArray[1]) {
                                            $controlType = $formStructureValue['control_type'] ?? '';
                                            break;
                                        }
                                    }
                                }
                                if (isset($formData[$idMatchArray[1] . '_TEXT'])) {
                                    if (
                                        isset($formData[$idMatchArray[1]])
                                        && ((is_array($formData[$idMatchArray[1]]) && !empty($formData[$idMatchArray[1]]))
                                        || (!is_array($formData[$idMatchArray[1]]) && $formData[$idMatchArray[1]] !== ''))) {
                                        if($formData[$idMatchArray[1] . '_TEXT'] !== ''){
                                            if(!is_array($formData[$idMatchArray[1] . '_TEXT'])){
                                                $idValue = $formData[$idMatchArray[1] . '_TEXT'];
                                            }else{
                                                $idValue = implode(",",$formData[$idMatchArray[1] . '_TEXT']);
                                            }
                                        } else {
                                            $idValue = "";
                                        }
                                		$controlArray[] = $idMatchArray[1] . "_TEXT";
                                	} else {
                                		$idValue = "";
                                		$controlArray[] = $idMatchArray[1];
                                	}
                                } else if (($controlType == 'countersign' || isset($formData[$idMatchArray[1].'_COUNTERSIGN']) || isset($formData[$idMatchArray[1]][0]['countersign_content'])) && isset($formData[$idMatchArray[1]])) {
                                    $countersignContent = '';
                                    if (isset($formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content']) && $formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content'] !== '') {
                                        $countersignContent =  preg_replace('/<img.*?>/', '#img', $formData[$idMatchArray[1].'_COUNTERSIGN']['countersign_content']);
                                        $countersignContent = preg_replace('/<.*?>/', '' ,$countersignContent);
                                    } else if (is_array($formData[$idMatchArray[1]])) {
                                        if (count($formData[$idMatchArray[1]]) > 0) {
                                            foreach ($formData[$idMatchArray[1]] as $idMatchKey => $idMatchValue) {
                                                if ($idMatchValue['countersign_user_id'] == $flowOtherInfo['user_id'] && isset($flowOtherInfo['process_id']) && $idMatchValue['process_id'] == $flowOtherInfo['process_id']) {
                                                    $countersignContent = $idMatchValue["countersign_content"];
                                                    $countersignContent =  preg_replace('/<img.*?>/', '#img', $countersignContent);
                                                    $countersignContent = preg_replace('/<.*?>/', '', $countersignContent);
                                                }
                                            }
                                        }
                                    } else {
                                        $formData[$idMatchArray[1]] = preg_replace('/<img.*?>/', '#img', $formData[$idMatchArray[1]]);
                                        $countersignContent = preg_replace('/<.*?>/', '', $formData[$idMatchArray[1]]);
                                    }
                                    $idValue = trim($countersignContent);
                                } else {
                                    $idValue = isset($formData[$idMatchArray[1]]) ? preg_replace('/<.*?>/', '', $formData[$idMatchArray[1]]) : '';
                                    if (is_array($idValue)) {
                                        $idValue = implode(',', $idValue);
                                    }
                                    $controlArray[] = $idMatchArray[1];
                                }
                            }
                            // $matchList[] = !empty($idValue) ? $idValue : '""';
                            $matchListValueItem = $idValue === "" ? '""' : $idValue;
                            if (getType($matchListValueItem) == "array") {
                                $matchListValueItem = json_encode($matchListValueItem);
                            }
                            $matchListValueItem = str_replace('&amp;', '&', $matchListValueItem);
                            $matchListValueItem = str_replace('\'', '&#39;', $matchListValueItem);
                            $matchList[] = $matchListValueItem;
                        } else {
                            $matchList[] = $matchValue;
                        }
                    } //与或运算符
                    else if (strpos($matchHtml, 'and-or-operator') !== false) {
                        $matchValue = strtoupper(trim($matchValue));
                        if ($matchValue == "AND") {
                            $matchList[] = "&&";
                        } else if ($matchValue == "OR") {
                            $matchList[] = "||";
                        } else {
                            $matchList[] = $matchValue;
                        }
                    } //比较运算符或者关系运算符
                    else if (strpos($matchHtml, 'compare-operator') !== false || strpos($matchHtml, 'relational-operator') !== false) {
                        if (isset($htmlEntityDecode[$matchValue])) {
                            $matchList[] = $htmlEntityDecode[$matchValue];
                        } else {
                            $matchList[] = $matchValue;
                        }
                    } //字符串
                    else {
                        $matchList[] = $matchValue;
                    }
                }

                //处理括号
                $hasLeftBracket = true;
                $leftBracketIndex = -1;
                for ($i = 0; $i < count($matchList); $i++) {
                    $matchList[$i] = (string)$matchList[$i];
                    if (isset($matchList[$i]) && $matchList[$i] == '(') {
                        $hasLeftBracket = true;
                        $leftBracketIndex = $i;
                    } else if ($matchList[$i] == ')') {
                        if (!$hasLeftBracket) {
                            // 括号不匹配

                        } else {
                            //处理括号之间的内容
                            //去除括号
                            $tempArray = [];
                            for ($j = $leftBracketIndex + 1; $j < $i; $j++) {
                                $tempArray[] = $matchList[$j];
                            }
                            if ($validate) {
                                //数值运算
                                foreach ($tempArray as $k => $v) {
                                    if (in_array('+', $tempArray) || in_array('-', $tempArray) || in_array('*', $tempArray) || in_array('/', $tempArray)) {
                                        if ($v != '+' && $v != '-' && $v != '*' && $v != '/') {
                                            $tempArray[$k] = floatval($v);
                                        }
                                    } else {
                                        if (in_array('=', $tempArray)) {
                                            if ($v != '=') {
                                                $tempArray[$k] = strval($v);
                                            }
                                        }
                                    }
                                }
                            }

                            //优先计算括号内的内容
                            $evalValue = implode(' ',$this->formulaValue($tempArray));
                            //替换掉所有的&nbsp;、html标签、空格
                            $evalValue = preg_replace('/&nbsp;|%<[^>]+>|\s*/', '', $evalValue);

                            if ($evalValue === '') {
                                $tempResult = true;
                            } else {
                                // if ($validate == false) {
                                //     $evalValue = $evalValue . ' ? true : false ;';
                                // } else {
                                if (in_array('+', $tempArray) || in_array('-', $tempArray) || in_array('*', $tempArray) || in_array('/', $tempArray)) {
                                    $evalValue = "(" . $evalValue . ");";
                                } else {
                                    $evalValue = $evalValue . ' ? true : false ;';
                                }
                                // }
                                try {
                                    eval("\$tempResult=$evalValue");
                                } catch (\Exception $e) {
                                    return false;
                                } catch (\Error $error) {
                                    return false;
                                }
                            }
                            if (!is_numeric($tempResult)) {
                                $tempResult = "'".$tempResult."'";
                            }
                            array_splice($matchList, $leftBracketIndex, ($i - $leftBracketIndex + 1), $tempResult);
                            $i = 0;
                        }
                    }
                }
            }
            if ($validate) {
                foreach ($matchList as $k => $v) {
                    if (in_array('=', $matchList)) {
                        if ($v != '=') {
                            $matchList[$k] = strval($v);
                        }
                    }
                }
            }
            //处理字符串和函数
            $formulaParsedArray = $this->formulaValue($matchList);
            //替换掉所有的&nbsp;、html标签、空格
            $formulaParsedStr = implode(' ', $formulaParsedArray);
            // |<[^>]+>表单值已经过滤 这里不过滤
            $formulaParsedStr = preg_replace('/&nbsp;|\s*/', '', $formulaParsedStr);
            $formulaParsedStr = str_replace("''''", "'\"\"'", $formulaParsedStr);

            if ($formulaParsedStr === '') {
                return true;
            }
            $formulaParsedStr = $formulaParsedStr . ' ? true : false ;';
            try {
                eval("\$result=$formulaParsedStr");
                return $result;
            } catch (\Exception $e) {
                return false;
            } catch (\Error $error) {
                return false;
            }
        }
    }

    /**
     * 【流程运行】 功能函数，转化出口条件控件符号为判断表达式
     *
     * @method formulaValue
     *
     * @param  [type]             $infoValue         [出口条件内容数组]
     *
     * @return [type]                   [description]
     */
    public function formulaValue($infoValue)
    {
        // + - * / > < 两边的字符都必须是数值型
        // + - 两边的值如果不是数值型则会被转成0
        // * / 两边的值如果不是数值型则会被转成1
        // > < 两边的值如果不是数值型则会忽略此条件 两边的值都转成0 确保值为假的
        $mustNumber = [">", "<", ">=", "<=", "+", "-", "*", "/"];
        $mustString = ["=", "!=", "^=", "$=", "*=", "|=", "~~"];

        for ($i = 0; $i < count($infoValue); $i++) {
            //替换掉所有的&nbsp;、html标签、空格
            $infoValue[$i] = preg_replace('/&nbsp;|<[^>]+>|\s*/', '', $infoValue[$i]);
            if (in_array($infoValue[$i], $mustNumber)) {
                if (isset($infoValue[$i - 1]) && isset($infoValue[$i + 1]) && is_string($infoValue[$i - 1]) && is_string($infoValue[$i + 1])) {

                    //只保留数字、小数点、负号
                    $leftNumber = preg_replace('/[^0-9.-]/', '', strval($infoValue[$i - 1]));
                    $rightNumber = preg_replace('/[^0-9.-]/', '', strval($infoValue[$i + 1]));

                    //检查符号两边的是否是数字
                    if (!is_numeric($leftNumber) && ($infoValue[$i] == '>' || $infoValue[$i] == '<')) {
                        $leftNumber = 0;
                    }
                    if (!is_numeric($rightNumber)) {
                        if ($infoValue[$i] == '+' || $infoValue[$i] == '-' || $infoValue[$i] == '>' || $infoValue[$i] == '<') {
                            $rightNumber = 0;
                        } else if ($infoValue[$i] == '*' || $infoValue[$i] == '/') {
                            $rightNumber = 1;
                        }
                    }
                    $infoValue[$i - 1] = floatval($leftNumber);
                    $infoValue[$i + 1] = floatval($rightNumber);
                } else {
                    // 错误
                }
            } else if (in_array($infoValue[$i], $mustString)) {

                if (isset($infoValue[$i - 1]) && isset($infoValue[$i + 1]) && is_string($infoValue[$i - 1]) && is_string($infoValue[$i + 1])) {
                    $infoValue[$i + 1] = preg_replace('/&nbsp;|<[^>]+>|\s*/', '', $infoValue[$i + 1]);
                    //"^=", "$=", "*=", "|=" 四个需要转换成对应的js函数
                    //= 转成 ==
                    switch ($infoValue[$i]) {
                        case '=':
                            $infoValue[$i - 1] = "'" . $infoValue[$i - 1] . "'";
                            $infoValue[$i] = " == ";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                        case '^=':
                            $infoValue[$i - 1] = "'" . substr($infoValue[$i - 1], 0, strlen($infoValue[$i + 1])) . "'";
                            $infoValue[$i] = " == ";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                        case '$=':
                            $infoValue[$i - 1] = "'" . substr($infoValue[$i - 1], -strlen($infoValue[$i + 1])) . "'";
                            $infoValue[$i] = " == ";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                        case '*=':
                            if (strpos($infoValue[$i - 1], $infoValue[$i + 1]) !== false) {
                                $infoValue[$i - 1] = 1;
                            } else {
                                $infoValue[$i - 1] = 0;
                            }
                            $infoValue[$i] = "";
                            $infoValue[$i + 1] = "";
                            break;
                        case '|=':
                            if (strpos($infoValue[$i - 1], $infoValue[$i + 1]) !== false) {
                                $infoValue[$i - 1] = 0;
                            } else {
                                $infoValue[$i - 1] = 1;
                            }
                            $infoValue[$i] = "";
                            $infoValue[$i + 1] = "";
                            break;
                        case '~~':
                            $leftArray = is_array($infoValue[$i - 1]) ? $infoValue[$i - 1] : explode(',', trim($infoValue[$i - 1], ','));
                            $rightArray = is_array($infoValue[$i + 1]) ? $infoValue[$i + 1] : explode(',', trim($infoValue[$i + 1], ','));
                            $result = [];
                            for ($m = 0; $m < count($leftArray); $m++) {
                                if (in_array($leftArray[$m], $rightArray)) {
                                    $result[] = $leftArray[$m];
                                }
                            }
                            $infoValue[$i - 1] = '(';
                            $infoValue[$i] = "'" . implode(',', $result) . "'";
                            $infoValue[$i + 1] = ')';
                            break;
                        default:
                            $infoValue[$i - 1] = "'" . $infoValue[$i - 1] . "'";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                    }
                }
            }
        }

        return $infoValue;
    }

    /**
     * 通过run_id获取流程表单相关数据
     */
    public function getFlowFormDatasByRunId($runId, $content = true)
    {
        if (empty($runId)) {
            return [];
        }
        //获取流程表单结构
        //流程表单id
        $formId = $this->getFormIdByRunId($runId);
        if (empty($formId)) {
            return [];
        }
        //表单结构
        $formControlStructure = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$formId]]]);
        //表单存档
        $formTypeObject = app($this->flowFormTypeRepository)->getDetail($formId);
        $formType = '';
        if ($formTypeObject) {
            $formTypeArray = $formTypeObject->toArray();
            if (isset($formTypeArray['form_type']) && isset($formTypeArray['print_model']) && !empty($formTypeArray['print_model'])) {
                if ($formTypeArray['form_type'] == 'complex') {
                    // 标准版表单
                    $formType = strip_tags($formTypeArray['print_model']);
                } elseif ($formTypeArray['form_type'] == 'simple') {
                    $model = json_decode($formTypeArray['print_model'], true);
                    if (isset($model['controlItems']) && !empty($model['controlItems'])) {
                        $title = array_column($model['controlItems'], 'title');
                        if (!empty($title)) {
                            $title = implode(',', $title);
                            $formType .= ',' . $title;
                        }
                    }
                }
            }
        }
        //流程数据
        if (count($formControlStructure)) {
            $flowRunDatabaseData = app($this->flowService)->getParseFormDataFlowRunDatabaseData($formControlStructure, $runId, $formId);
        } else {
            $flowRunDatabaseData = [];
        }
        return [
            'form_id' => $formId,
            'form_model' => $formType,
            'form_data' => $flowRunDatabaseData,
        ];
    }

    /**
     * 调用[通过run_id获取流程表单相关数据]，然后把数据格式化(主要是解析出formdata里的数据)后返回，供归档函数使用
     * @param  [type] $runId [description]
     * @return [type]        [description]
     */
    public function getFlowFilingContent($runId)
    {
        $flowData = $this->getFlowFormDatasByRunId($runId);
        $formModelStr = $flowData['form_model'] ?? '';
        $formData = $flowData['form_data'] ?? '';
        $formDataStr = '';

        if (!empty($formData)) {
            $formDataStr .= ',' . json_encode($formData, JSON_UNESCAPED_UNICODE);
        }

        $flowDataString = $formModelStr . $formDataStr;
        return $flowDataString;
    }
    /**
     * 自由节点循环上级，动态生成后续节点流转信息
     * 循环获取当前节点主办人的上级为下个节点主办人，生成后续节点
     */
    public function circularSuperiorGetNextNodeInfo($param, $userInfo)
    {
        $userInfo = (!empty($userInfo) && isset($userInfo['user_id'])) ? $userInfo : own();
        $runId = $param['run_id'] ?? '';
        $nodeId = $param['node_id'] ?? '';
        $stepId = $param['step_id'] ?? 0;
        if (!$runId || !$nodeId) {
            return false;
        }
        $freeNodeInfo = app($this->flowProcessFreeRepository)->getOneFieldInfo(['node_id' => [$nodeId]]);
        if ($freeNodeInfo) {
            $freeNodeInfo = $freeNodeInfo->toArray();
        } else {
            return false;
        }
        switch ($freeNodeInfo['circular_superior_type']) {
            case '1':
                // 无限上级循环
                return $this->setSuperiorFreeStepPorcess($userInfo['user_id'], $runId, $nodeId, $stepId);
                break;
            case '2':
                // 按次数循环
                $number = $freeNodeInfo['circular_superior_degree'];
                // 从自由节点运转表查询已设置的流转步骤
                $stepList = $this->getFreeNodeStepList($runId, $nodeId, ['is_superior' => [1]]);
                $currentNumner = count($stepList);
                if ($number <= $currentNumner) {
                    return $this->getFreeNodeStepInfo($runId, $nodeId, $stepId + 1);
                } else {
                    return $this->setSuperiorFreeStepPorcess($userInfo['user_id'], $runId, $nodeId, $stepId);
                }
                break;
            case '3':
                $currentUserId = $userInfo['user_id'];
                $currentUrerRoles = $userInfo['role_id'] ?? [];
                $currentUserDept = $userInfo['dept_id'];
                if ($freeNodeInfo['circular_superior_user'] == $currentUserId || in_array($freeNodeInfo['circular_superior_role'], $currentUrerRoles) || $freeNodeInfo['circular_superior_dept'] == $currentUserDept) {
                    return false;
                } else {
                    return $this->setSuperiorFreeStepPorcess($userInfo['user_id'], $runId, $nodeId, $stepId);
                }
                // 按人员角色部门循环
                break;
            default:
                return false;
                break;
        }
    }
    /**
     * 获取上级信息，写入流转步骤表
     */
    public function setSuperiorFreeStepPorcess($userId, $runId, $nodeId, $stepId)
    {
        // 获取下一步骤信息
        $freeRealProcessInfo = app($this->flowProcessRepository)->getDetail($nodeId);
        if ($freeRealProcessInfo) {
            $freeRealProcessInfo = $freeRealProcessInfo->toArray();
            $freeRealProcessInfo['condition'] = [];
            $freeRealProcessInfo["limit_date"] = isset($freeRealProcessInfo["press_add_hour"]) && !empty($freeRealProcessInfo["press_add_hour"]) ? date('Y-m-d H:i:s', time() + floatval($freeRealProcessInfo["press_add_hour"]) * 3600) : 0;
        } else {
            $freeRealProcessInfo = [];
        }
        // 获取当前所在的自定义最大步骤
        $currentStepId = $this->getRunCurrentFreeNodeStep($runId, $nodeId);
        if ($stepId > $currentStepId) {
            return $this->getFreeNodeStepInfo($runId, $nodeId, $currentStepId + 1);
        }
        $superiorInfo = app($this->userSuperiorRepository)->getUserImmediateSuperior($userId, ['include_leave' => false]);

        if ($superiorInfo && count($superiorInfo) > 0) {
            $superiorId = [];
            if (count($superiorInfo) == 1) {
                $superiorId[] = $superiorInfo[0]['superior_user_id'];
            } else {
                foreach ($superiorInfo as  $value) {
                    $superiorId[] = $value['superior_user_id'];
                }
            }
            if (!empty($superiorId)) {
                $superiorId = array_unique($superiorId);
                $superiorId = implode(',', $superiorId);
            } else {
                $superiorId = '';
            }
            $stepId = intval($stepId) + 1;
            // 查找已经存在的上级审批数量
            // $isSuperiorData = app($this->flowProcessFreeStepRepository)->getFreeNodeStepList($runId, $nodeId, ['is_superior' => [1]]);
            // if ($isSuperiorData) {
            //     $isSuperiorData = count($isSuperiorData);
            // } else {
            //     $isSuperiorData = 0;
            // }
            if ($stepId>1) {
                $isSuperiorData = $stepId-1;
            } else {
                $isSuperiorData = '';
            }
            $param = [
                'runId' => $runId,
                'nodeId' => $nodeId,
                'userId' => $superiorId,
                'processName' => trans("flow.superior_approval") . $isSuperiorData,
                'is_superior' => 1,
                'requiredControlId' => '',
                'stepId' => $stepId,
                'backStep' => '',
            ];
            $result = $this->insertFreeNodeStep($param);
            return array_merge($freeRealProcessInfo, $result);
        } else {
            return false;
        }
    }
    /**
     * 获取当前设置的最大流转步骤id
     */
    public function getMaxFreeNodeStep($runId, $nodeId)
    {
        // 获取最大setp_id
        $stepInfo = app($this->flowProcessFreeStepRepository)->getMaxStep($runId, $nodeId);
        if ($stepInfo) {
            return $stepInfo->step_id;
        } else {
            return 0;
        }
    }
    /**
     * 获取当前所在流转步骤id
     */
    public function getRunCurrentFreeNodeStep($runId, $nodeId)
    {
        $currentRunStepInfo = app($this->flowRunProcessRepository)->getRunCurrentFreeNodeStep($runId, $nodeId);
        $currentRunStep = 0;
        if ($currentRunStepInfo) {
            $currentRunStep = $currentRunStepInfo->free_process_step;
        }
        return $currentRunStep;
    }
    /**
     * 增加自由节点流转步骤
     */
    public function saveFreeProcessSteps($param, $userInfo)
    {
        $runId = $param['run_id'] ?? '';
        $nodeId = $param['node_id'] ?? '';
        if (!$runId || !$nodeId) {
            return ['code' => ['0x000003', 'common']];
        }
        // 查找当前所在流转步骤
        $currentRunStep = $this->getRunCurrentFreeNodeStep($runId, $nodeId);
        // 过滤无效数据
        foreach ($param['process_list'] as $key => $value) {
            if ($value['step_id'] <= $currentRunStep) {
                return ['code' => ['0x030181', 'flow'], 'dynamic' => trans('flow.0x030181', ['step_id' => $currentRunStep + 1])];
            }
        }
        if (isset($param['process_list']) && !empty($param['process_list'])) {
            $sortDate = array_column($param['process_list'], 'step_id');
            array_multisort($sortDate, SORT_ASC, $param['process_list']);
            foreach ($param['process_list'] as $key => $value) {
                $param = [
                    'runId' => $runId,
                    'nodeId' => $nodeId,
                    'userId' => $value['handle_user'],
                    'processName' => $value['node_name'],
                    'is_superior' => $value['is_superior'] ?? 0,
                    'requiredControlId' => $value['required_control_id'] ?? '',
                    'stepId' => $value['step_id'],
                    'backStep' => '',
                ];
                $this->insertFreeNodeStep($param);
            }
        }
        $nextStepInfo = $this->getFreeNodeStepInfo($runId, $nodeId, $currentRunStep + 1);
        if ($nextStepInfo) {
            $nextStepInfo->free_process_current_step = $currentRunStep;
            $nextStepInfo->free_process_next_step = $nextStepInfo->step_id;
            return $nextStepInfo;
        } else {
            return ['code' => ['0x030181', 'flow'], 'dynamic' => trans('flow.0x030181', ['step_id' => $currentRunStep + 1])];
        }
    }
    /**
     * 运转顺序表插入数据
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function insertFreeNodeStep($param)
    {
        $runId = $param['runId'];
        $nodeId = $param['nodeId'];
        $userId = $param['userId'];
        $nodeName = $param['processName'];
        $isSuperior = $param['is_superior'] ?? 0;
        $stepId = $param['stepId'];
        $requiredControlId = $param['requiredControlId'] ?? '';
        $backProcess = $param['backStep'] ?? '';
        if (is_array($requiredControlId)) {
            $requiredControlId = implode(',', $requiredControlId);
        }
        // 上级信息写入自由节点运转表
        $insertData = [
            'run_id' => $runId,
            'node_id' => $nodeId,
            'step_id' => $stepId,
            'process_name' => $nodeName,
            'is_superior' => $isSuperior,
            'user_id' => $userId,
            'required_control_id' => $requiredControlId,
            'back_step' => $backProcess
        ];
        $updateDate = [
            'user_id' => $userId,
            'required_control_id' => $requiredControlId,
        ];
        if (!$insertData['is_superior']) {
            $updateDate['process_name'] = $nodeName;
        }

        $freenodeInfo = $this->getFreeNodeStepInfo($runId, $nodeId, $stepId);
        if ($freenodeInfo) {
            app($this->flowProcessFreeStepRepository)->updateData($updateDate, ['run_id' => $runId, 'node_id' => $nodeId, 'step_id' => $stepId]);
            $freeNodeStepInfo = app($this->flowProcessFreeStepRepository)->getFreeNodeStepInfo($runId, $nodeId, $stepId);
        } else {
            // 查找当前最大step_id
            $currentMaxId = $this->getMaxFreeNodeStep($runId, $nodeId);
            if ($insertData['step_id'] != $currentMaxId + 1) {
                $insertData['step_id'] = $currentMaxId + 1;
            }
            $id = app($this->flowProcessFreeStepRepository)->insertGetId($insertData);
            $freeNodeStepInfo = app($this->flowProcessFreeStepRepository)->getDetail($id);
        }
        if ($freeNodeStepInfo) {
            $freeNodeStepInfo = $freeNodeStepInfo->toArray();
        } else {
            $freeNodeStepInfo = [];
        }
        return $freeNodeStepInfo;
    }


    /**
     * 获取自由节点信息
     * @param $runId
     * @param $nodeId
     * @param $stepId
     * @return mixed
     */
    public function getFreeNodeStepInfo($runId, $nodeId, $stepId)
    {
        $freeNodeStepInfo = app($this->flowProcessFreeStepRepository)->getFreeNodeStepInfo($runId, $nodeId, $stepId);
        if ($freeNodeStepInfo) {
            $freeRealProcessInfo = app($this->flowProcessRepository)->getDetail($nodeId);
            $freeRealProcessInfo->step_id = $freeNodeStepInfo->step_id;
            $freeRealProcessInfo->process_name = $freeNodeStepInfo->process_name;
            $freeRealProcessInfo->user_id = $freeNodeStepInfo->user_id;
            $freeRealProcessInfo->required_control_id = $freeNodeStepInfo->required_control_id;
            $freeRealProcessInfo->back_step = $freeNodeStepInfo->back_step;
            $freeRealProcessInfo->is_superior = $freeNodeStepInfo->is_superior;
            $freeRealProcessInfo->condition = '';
            $freeRealProcessInfo["limit_date"] = isset($freeRealProcessInfo["press_add_hour"]) && !empty($freeRealProcessInfo["press_add_hour"]) ? date('Y-m-d H:i:s', time() + floatval($freeRealProcessInfo["press_add_hour"]) * 3600) : 0;
            return $freeRealProcessInfo;
        }
        return $freeNodeStepInfo;
    }
    public function getFreeNodeStepList($runId, $nodeId, $param = [])
    {
        return $freeNodeStepInfo = app($this->flowProcessFreeStepRepository)->getFreeNodeStepList($runId, $nodeId, $param);
    }
    // 查找某个自由节点可退回的步骤信息
    public function getFreeNodeRunBackSetpInfo($param)
    {
        $flowId = $param['flow_id'] ?? '';
        $runId = $param['run_id'] ?? '';
        $nodeId = $param['node_id'] ?? '';
        $stepId = $param['step_id'] ?? '';
        if (empty($flowId) || empty($runId) || empty($nodeId) || empty($stepId)) {
            return [];
        }
        // 获取节点设置信息
        $detailInfo = app($this->flowProcessFreeRepository)->getOneFieldInfo(['node_id' => [$nodeId]]);
        $freeRealProcessInfo = app($this->flowProcessRepository)->getDetail($nodeId);
        if ($freeRealProcessInfo) {
            $freeRealProcessInfo = $freeRealProcessInfo->toArray();
            $freeRealProcessInfo["limit_date"] = isset($freeRealProcessInfo["press_add_hour"]) && !empty($freeRealProcessInfo["press_add_hour"]) ? date('Y-m-d H:i:s', time() + floatval($freeRealProcessInfo["press_add_hour"]) * 3600) : 0;
        } else {
            $freeRealProcessInfo = [];
        }
        if ($detailInfo) {
            // 退回设置
            $backType = $detailInfo->back_type;
            if ($backType == 1) {
                //不允许退回
                return [];
            } else {
                $result = [];
                //退回至最初节点
                $flowNodes = app($this->flowProcessRepository)->getFlowDefineProcessList(['flow_id' => $flowId, 'search' => ['node_id' => [$nodeId]], 'returntype' => 'first']);
                if ($stepId - 1 == 0) {
                    $result[] = $flowNodes;
                } else {
                    // 退回上一步
                    if ($backType == 2) {
                        $flowNodes = $this->getFreeNodeStepInfo($runId, $nodeId, $stepId - 1);

                        $freeRealProcessInfo['back_step'] = $flowNodes->back_step;
                        $freeRealProcessInfo['process_type'] = 'free';
                        $freeRealProcessInfo['node_id'] = $nodeId;
                        $freeRealProcessInfo['process_name'] = $flowNodes->process_name;
                        $freeRealProcessInfo['required_control_id'] = $flowNodes->required_control_id;
                        $freeRealProcessInfo['run_id'] = $runId;
                        $freeRealProcessInfo['step_id'] = $flowNodes->step_id;
                        $freeRealProcessInfo['user_id'] = $flowNodes->user_id;
                        $result[] = $freeRealProcessInfo;
                    } elseif ($backType == 3) {
                        if ($flowNodes) {
                            $flowNodes = $flowNodes->toArray();
                            $flowNodes["limit_date"] = isset($flowNodes["press_add_hour"]) && !empty($flowNodes["press_add_hour"]) ? date('Y-m-d H:i:s', time() + floatval($flowNodes["press_add_hour"]) * 3600) : 0;
                        } else {
                            $flowNodes = [];
                        }
                        $result[] = $flowNodes;
                        // 退回任一步
                        $flowNodes = $this->getFreeNodeStepList($runId, $nodeId, ['step_id' => [$stepId, '<']]);
                        if ($flowNodes) {
                            $flowNodes = $flowNodes->toArray();
                            foreach ($flowNodes as $key => $value) {

                                $freeRealProcessInfo['back_step'] = $value['back_step'];
                                $freeRealProcessInfo['process_type'] = 'free';
                                $freeRealProcessInfo['node_id'] = $nodeId;
                                $freeRealProcessInfo['process_name'] = $value['process_name'];
                                $freeRealProcessInfo['required_control_id'] = $value['required_control_id'];
                                $freeRealProcessInfo['run_id'] = $runId;
                                $freeRealProcessInfo['step_id'] = $value['step_id'];
                                $freeRealProcessInfo['user_id'] = $value['user_id'];
                                $result[] = $freeRealProcessInfo;
                            }
                        }
                    }
                }
                return $result;
            }
        } else {
            return [];
        }
    }
    // 获取自由节点最后一步运转信息
    public function getFlowRunProcessLastStepInfo($runId, $nodeId)
    {
        $flowRunProcessLastStepInfo = app($this->flowRunProcessRepository)->getRunCurrentFreeNodeStep($runId, $nodeId);
        if ($flowRunProcessLastStepInfo) {
            $stepId = $flowRunProcessLastStepInfo->free_process_step;
            $flowProcessFreeInfo = $this->getFreeNodeStepInfo($runId, $nodeId, $stepId);
            if ($flowProcessFreeInfo) {
                return $flowProcessFreeInfo;
            }
        }
        return [];
    }

    /**
     * 通过模块英文名称找到模块中文名称及其他参数
     *
     * @author   zyx
     *
     */
    public function getModuleDetail($name)
    {
        // from_type类型
        if (str_pos($name, 'project') !== -1) {
            $menuArray = explode('_', $name);
            foreach ($menuArray as $v) {
                if ($v !== 'project') {
                    $config = config('flowoutsend.from_type');
                    $moduleInfo = $config[$v];
                }
            }
            return $moduleInfo;
        }

        // 系统模块
        $config_module = config('flowoutsend.module.' . $name);
        if (isset($config_module)) { // 系统模块
            $moduleInfo = $config_module;
            return $moduleInfo;
        }

        // 自定义模块
        $config_custom_module = config('flowoutsend.custom_module.' . $name);
        if (isset($config_custom_module)) { // 自定义模块
            $moduleInfo = $config_custom_module;
        }

        return $moduleInfo;
    }

    /**
     *
     * @param  [type] $run_name [description]
     * @return [type]        [description]
     */
    public function getBlankSpaceRunName($run_name)
    {
        $runArr = explode(' ', $run_name);
        $runstr = '';
        foreach ($runArr as $runkey => $runvalue) {
            if (!empty($runvalue)) {
                $runvalue = str_replace("'", "\'", $runvalue);
                $runstr .= " flow_run.run_name like '%".$runvalue."%' and";
            }
        }
        return rtrim($runstr, 'and');
    }

    /**
     * @DESC  流程基于某条flow_run_process数据的收回权限获取，分为普通收回权限和监控收回权限
     * @param $data  [array]  主体数据
     * @param $userInfo
     * @param $monitorRules  [array]  监控规则
     * @return mixed
     * @author zyx
     * @since 2019/12/23，20200723修改
     */
    public function checkFlowRunProcessTakeBackAuthority($data, $userInfo, $monitorRules)
    {
        $result['hasBeenChecked'] = 1;
        $result['takeBackFlag'] = 0;
        $result['monitorTakeBackFlag'] = 0;

        // 当前运行节点数据
        $currentFlowRunProcessInfo = $data['current_flow_run_process_info'];
        // 当前运行节点配置
        $currentFlowProcessInfo = $currentFlowRunProcessInfo['flow_run_process_has_one_flow_process'];
        // 流程步骤节点列表
        $flowRunProcessList = $data['flow_run_process_list'];

        // 1.预处理判断收回权限
        $preResult = $this->handleFlowTakeBackAuthority($data, $monitorRules, $userInfo);

        // 2.1预处理收回权限只对普通收回权限生效
        // 2.2只有普通收回权限，只能普通收回
        // 2.3没有监控收回权限也没有普通收回权限，不能收回
        if (!$preResult['monitorTakeBackFlag']
            // !$currentFlowRunProcessInfo['branch_serial']
            // !($preResult['takeBackFlag'] + $preResult['monitorTakeBackFlag']) ||
            // $preResult['takeBackFlag']
        ) {
            return $preResult;
        }

        // 分支节点不论是普通权限还是监控权限，都需要具体分析
        // 有监控收回权限的时候才去具体分析是否可以操作监控收回

        $currentHostInfo = [];
        // 3.当前节点不是分支节点，只是普通节点,看当前步骤节点主办人条目办理情况
        if (!$currentFlowRunProcessInfo['branch_serial']) {
            if ($currentFlowRunProcessInfo['host_flag']) { // 当前条目是主办人条目
                $currentHostInfo = $currentFlowRunProcessInfo;
            } else { // 当前条目不是主办人条目，遍历查找
                foreach ($flowRunProcessList as $flowRunProcessValue) {
                    if (
                        ($flowRunProcessValue['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) &&
                        $flowRunProcessValue['host_flag']
                    ) {
                        $currentHostInfo = $flowRunProcessValue;
                        break;
                    }
                }
            }

            // 3.1.1如果当前步骤没有主办人条目，说明是第3、4种办理方式，且尚未提交，则不能收回
            // 3.1.2当前节点主办人条目尚未提交时也不能收回
            if (
                !$currentHostInfo ||
                in_array($currentHostInfo['process_flag'], [1, 2])
            ) {
                return $result;
            }

            // 3.2当前节点主办人条目已提交且结束了流程则不能收回
            // if (
            //     ($currentHostInfo['process_flag'] > 2) && // 已提交
            //     !$currentHostInfo['outflow_process'] // 但没有出口，认为是结束
            // ) {
            //     return $result;
            // }

            $nextStepFlowSerial = $currentFlowRunProcessInfo['flow_serial'] + 1;
            // 看下一步骤节点flow_serial+1情况
            foreach ($flowRunProcessList as $key => $flowRunProcessValue) {
                if ($flowRunProcessValue['flow_serial'] == $nextStepFlowSerial) {
                    if ($flowRunProcessValue['branch_serial']) { // 下一步骤是分支节点，需要遍历每个分支下的process_serial
                        // 3.3如果某个分支下的步骤 > 1 则不能收回
                        if ($flowRunProcessValue['process_serial'] > 1) {
                            return $result;
                        }
                        // 3.4非强制合并节点，在某个分支提交后，其他分支再提交时不再生成新分支步骤，此时不能收回
                        if (
                            ($flowRunProcessValue['host_flag'] == 1) && // 主办人条目
                            ($flowRunProcessValue['process_flag'] > 2) && // 已提交
                            !$flowRunProcessValue['outflow_process'] // 但没有出口，认为是已提交至费强制合并节点之后的其他分支
                        ) {
                            return $result;
                        }
                    } else { // 3.5下一步骤只是普通节点，如果已经提交则不能收回
                        if (
                            ($flowRunProcessValue['host_flag'] == 1) && // 主办人条目
                            ($flowRunProcessValue['process_flag'] > 2) // 已提交
                        ) {
                            return $result;
                        }
                    }
                } else {
                    unset($flowRunProcessList[$key]);
                    continue;
                }
            }

            // 3.6.当前步骤是普通节点，监控收回
            // 3.6.1下一步骤是分支节点，且每个分支都只有一步，且没有已经结束的分支
            // 3.6.2下一步骤是普通节点，且尚未提交
            return $preResult;
        }

        // 4.当前步骤节点是分支节点
        $maxProcessSerial = app($this->flowRunProcessRepository)->getFieldMaxValue('process_serial', [
            'run_id' => [$data['run_id']],
            'flow_serial' => [$currentFlowRunProcessInfo['flow_serial']],
            'branch_serial' => [$currentFlowRunProcessInfo['branch_serial']]
        ]); // 当前分支最新步骤process_serial
        $maxProcessSerialDifference = $maxProcessSerial - $currentFlowRunProcessInfo['process_serial']; // 所在分支最新步骤与当前步骤的步骤差

        // 4.1当前分支步骤节点与所在分支最新步骤的步骤差超过1则不能收回
        if ($maxProcessSerialDifference > 1) {
            return $result;
        }

        // 4.2当前步骤节点是所在分支的倒数第二步
        if ($maxProcessSerialDifference == 1) {
            // 如果当前步骤节点是并发节点，则需要查看所有该合并节点提交生成的分支情况
            if (isset($currentFlowProcessInfo['concurrent']) && $currentFlowProcessInfo['concurrent']) {
                foreach ($flowRunProcessList as $flowRunProcessValue) {
                    // 当前并发节点提交生成的分支步骤，如果某个分支提交了则不能收回
                    if (
                        ($flowRunProcessValue['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) && // 相同步骤
                        ($flowRunProcessValue['origin_process_id'] == $currentFlowRunProcessInfo['process_id']) && // 来源步骤是当前步骤的分支
                        (
                            $flowRunProcessValue['host_flag'] &&  // 只取主办人条目
                            ($flowRunProcessValue['process_flag'] > 2) // 已提交
                        )
                    ) {
                        return $result;
                    }
                }

                // 分支上的并发节点提交，生成的步骤都没有提交，则可以收回
                return $preResult;
            }

            // 如果当前步骤节点是普通分支节点，则获取所在分支最新步骤信息
            $maxProcessSerialInfo = [];
            foreach ($flowRunProcessList as $flowRunProcessValue) {
                if (
                    ($flowRunProcessValue['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) &&
                    ($flowRunProcessValue['branch_serial'] == $currentFlowRunProcessInfo['branch_serial']) &&
                    ($flowRunProcessValue['process_serial'] == $maxProcessSerial) &&
                    $flowRunProcessValue['host_flag'] // 只取主办人条目
                ) {
                    $maxProcessSerialInfo = $flowRunProcessValue;
                    break;
                }
            }

            // 4.2.1所在分支最新步骤没有主办人条目，表示为第3、4种办理方式且尚未提交，可以监控收回
            if (!$maxProcessSerialInfo) {
                return $preResult;
            }

            // 4.2.2所在分支最新步骤主办人尚未结束或提交时可以监控收回，否则不能收回
            return ($maxProcessSerialInfo['process_flag'] < 3) ? $preResult : $result;
        }

        // 4.3当前步骤节点是当前分支的最新步骤，合并节点都写在分支上
        if (!$maxProcessSerialDifference) {
            if ($currentFlowRunProcessInfo['host_flag']) { // 当前条目是主办人条目
                $currentHostInfo = $currentFlowRunProcessInfo;
            } else { // 当前条目不是主办人条目，遍历查找
                foreach ($flowRunProcessList as $flowRunProcessValue) {
                    if (
                        ($flowRunProcessValue['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) &&
                        $flowRunProcessValue['host_flag']
                    ) {
                        $currentHostInfo = $flowRunProcessValue;
                        break;
                    }
                }
            }

            // 4.3.1如果当前步骤没有主办人条目，说明是第3、4种办理方式，且尚未提交，则不能收回
            // 4.3.2当前节点主办人条目尚未提交时也不能收回
            if (
                !$currentHostInfo ||
                in_array($currentHostInfo['process_flag'], [1, 2])
            ) {
                return $result;
            }

            // 4.3.3当前节点主办人条目已提交且结束了流程则不能收回
            if (
                ($currentHostInfo['process_flag'] > 2) && // 已提交
                !$currentHostInfo['outflow_process'] // 但没有出口，认为是结束
            ) {
                return $result;
            }

            $keepGoing = 0;
            $maxFlowSerial = $currentFlowRunProcessInfo['flow_serial'] + 1;
            // 4.4当前分支已经提交且尚未结束，找出合并后节点flow_serial+1信息
            foreach ($flowRunProcessList as $flowRunProcessValue) {
                if ($flowRunProcessValue['flow_serial'] == $maxFlowSerial) {
                    // 4.4.1如果合并后节点已经提交，则不能收回
                    // 4.4.2合并后节点不是当前分支提交生成的则不能收回
                    if (
                        in_array($flowRunProcessValue['process_flag'], [3, 4]) ||
                        ($flowRunProcessValue['origin_process'] != $currentFlowRunProcessInfo['flow_process']) ||
                        ($flowRunProcessValue['origin_process_id'] != $currentFlowRunProcessInfo['process_id'])
                    ) {
                        return $result;
                    }
                    $keepGoing = 1;
                }
            }

            // 4.4.3合并后节点尚未提交可监控收回
            if ($keepGoing) {
                return $preResult;
            }
        }

        return $result;
    }

    /**
     * 直接判断收回权限
     * @param $data 当前步骤主体数据
     * @param $monitorRules 监控规则
     * @param $userInfo
     *
     * @return $result
     */
    public function handleFlowTakeBackAuthority($data, $monitorRules, $userInfo) {
        $result['hasBeenChecked'] = 1;
        $result['takeBackFlag'] = 0;
        $result['monitorTakeBackFlag'] = 0;

        // 当前节点数据
        $currentFlowRunProcessInfo = $data['current_flow_run_process_info'];

        //首先获取最新节点办理状态,供收回弹窗区分提示文字使用
        // $handleStatusParams = [
        //     'run_id' => [$data['run_id']],
        //     'process_id' => [$data['max_process_id'] - 1],
        //     'process_flag' => [3],
        //     'host_flag' => [1],
        // ];
        // $handleStatusRes = app($this->flowRunProcessRepository)->getColumns(['user_id'], $handleStatusParams);

        // 判断当前步骤的办理状态，如果是3则表示下一步骤尚未查看
        $result['hasBeenChecked'] = $handleStatusRes = ($currentFlowRunProcessInfo['process_flag'] == 3) ? 0 : 1;

        //如果要使用监控收回权限，还需要获取上一节点主办人
        // 必然存在
        // $prevProcessHostUser = app($this->flowRunProcessRepository)->getHostUserIdByRunIdAndProcessId($data['run_id'], $data['max_process_id'] - 1);

        //1.如果是admin，给予完全的收回权限
        // 取消admin的超级权限
        // if (($userInfo['user_id'] == 'admin') && $prevProcessHostUser) {
        //     $result['monitorTakeBackFlag'] = 1;
        //     return $result;
        // }

        //2.是否有对本类流程的监控收回权限（基于流程监控设置）
        $monitorButtonDetails = $this->checkFlowMonitorButtonDetails($monitorRules, $currentFlowRunProcessInfo['flow_id'], $data['creator']);
        if ($monitorButtonDetails && $monitorButtonDetails['allow_take_back']) {
            $result['monitorTakeBackFlag'] = 1;
            return $result;
        }

        //3.仅有普通收回权限，当前人员是上一节点的主办人且下一节点尚未有人看过
        // if ($handleStatusRes && ($handleStatusRes[0] == $userInfo['user_id'])) {
        if (
            ($currentFlowRunProcessInfo['user_id'] == $userInfo['user_id']) &&
            $currentFlowRunProcessInfo['host_flag'] &&
            !$handleStatusRes
        ) {
            $result['takeBackFlag'] = 1;
        }

        return $result;
    }

    /**
     * 流程收回，获取要被收回的节点条目信息
     *
     * @param $flowRunInfo
     * @param $takeBackFlag 收回标识，普通收回还是监控收回
     *
     * @author zyx
     * @return array
     */
    public function getFlowRunProcessToTakeBack($flowRunInfo, $takeBackFlag) {
        // 流程运行节点列表
        $flowRunProcessList = $flowRunInfo['flow_run_process_list'];
        // 当前节点数据
        $currentFlowRunProcessInfo = $flowRunInfo['current_flow_run_process_info'];

        // 要收回的运行节点信息
        $flowRunProcessListToTakeBack = [];

        // 当前节点是普通节点，则收回的是下一步骤节点，不区分下一步骤节点是并发节点还是普通节点
        $nextFlowSerial = $currentFlowRunProcessInfo['flow_serial'] + 1;
        if (!$currentFlowRunProcessInfo['branch_serial']) {
            foreach ($flowRunProcessList as $flowRunProcessValue) {
                if ($flowRunProcessValue['flow_serial'] == $nextFlowSerial) { // 下一步骤节点
                    $flowRunProcessListToTakeBack[] = $flowRunProcessValue;
                }
            }
            return $flowRunProcessListToTakeBack;
        }

        // 当前节点是分支节点，则可能是收回合并后节点，也可能是收回当前分支的最新节点
        // 先获取当前分支最新步骤process_serial
        $maxProcessSerial = app($this->flowRunProcessRepository)->getFieldMaxValue('process_serial', [
            'run_id' => [$flowRunInfo['run_id']],
            'flow_serial' => [$currentFlowRunProcessInfo['flow_serial']],
            'branch_serial' => [$currentFlowRunProcessInfo['branch_serial']]
        ]);

        // 如果当前节点是当前分支上的最后节点，则收回合并后节点
        if ($maxProcessSerial == $currentFlowRunProcessInfo['process_serial']) {
            foreach ($flowRunProcessList as $flowRunProcessValue) {
                if ($flowRunProcessValue['flow_serial'] == $nextFlowSerial) {
                    $flowRunProcessListToTakeBack[] = $flowRunProcessValue;
                }
            }
        } else { // 如果当前节点不是当前分支上的最后节点，则是收回所有来源于当前步骤节点的数据
            foreach ($flowRunProcessList as $flowRunProcessValue) {
                if ($flowRunProcessValue['origin_process_id'] == $currentFlowRunProcessInfo['process_id']) {
                    $flowRunProcessListToTakeBack[] = $flowRunProcessValue;
                }
            }
        }

        return $flowRunProcessListToTakeBack;
    }

    /**
     * @DESC 返回用户对当前流程的监控权限明细
     * @param $monitorRules
     * @param $flowId
     * @param $creator
     * @return mixed
     * @author zyx
     * @since 2019/12/23
     */
    public function checkFlowMonitorButtonDetails($monitorRules, $flowId, $creator)
    {
        $allow_button = [
            'allow_view' => 0,
            'allow_delete' => 0,
            'allow_end' => 0,
            'allow_end' => 0,
            'allow_urge' => 0,
            'allow_turn_back' => 0,
            'allow_take_back' => 0,
        ];
        if (isset($monitorRules[$flowId]) && !empty($monitorRules[$flowId])) {
            foreach ($monitorRules[$flowId] as $ruleKey => $ruleValue) {
                if (!isset($ruleValue['user_id']) || empty($ruleValue['user_id'])) {
                    continue;
                }
                // 流程创建人在被监控范围内
                if (($ruleValue['user_id'] == 'all') || in_array($creator, $ruleValue['user_id'])) {
                    $allow_button = [
                        'allow_view' => $ruleValue['allow_view'],
                        'allow_delete' => $ruleValue['allow_delete'],
                        'allow_end' => $ruleValue['allow_end'],
                        'allow_urge' => $ruleValue['allow_urge'],
                        'allow_turn_back' => $ruleValue['allow_turn_back'],
                        'allow_take_back' => $ruleValue['allow_take_back'],
                    ];
                    return $allow_button;
                }
            }
        }

        return $allow_button;
    }

    /**
     * @DESC 通过run_id获取流程详情【自定义流程选择器处获取flow_id调用】
     * @param $runId
     * @return mixed
     * @author wz
     * @since 2019/12/26
     */
    public function getRunDetailByRunId($runId, $currentUser = '')
    {
        $flowRunInfo = app($this->flowRunRepository)->getDetail($runId);
        // 增加判断跳转到办理页面还是查看页面的参数handle=0跳到查看页面handle=1跳到办理页面
        if ($flowRunInfo) $flowRunInfo->handle = 0;
        if (!empty($currentUser) && $flowRunInfo) {
            $handleCountParams = [
                'returntype' => 'count',
                'search'     => [
                    'user_id'    => [$currentUser],
                    'run_id'     => [$runId],
                ],
                'whereRaw'   => ['((process_time is null) or (host_flag=1 && deliver_time is null) or (host_flag=0 && saveform_time is null))'],
            ];
            $handleCount = app($this->flowRunProcessRepository)->getFlowRunProcessList($handleCountParams);
            if ($handleCount > 0) $flowRunInfo->handle = 1;
        }
        return $flowRunInfo;
    }
    /**
     * @DESC 获取form_id
     * @param $run_id
     * @return mixed
     * @author wz
     * @since 2019/12/26
     */
    public function getFormDetailByFlowId($flow_id)
    {
        return   app($this->flowTypeRepository)->getDetail($flow_id, false, ['form_id']);
    }
    /**
     * @DESC 流程归档是否满足条件
     * @param $run_id
     * @return mixed
     * @author wz
     * @since 2020//3/4
     */
    public function validFileCondition($flowEndParam, $userInfo)
    {
        $flowOthers = app($this->flowOthersRepository)->getDetail($flowEndParam['flow_id'], false, ['flow_filing_conditions_setting_value', 'flow_filing_conditions_verify_mode', 'flow_filing_conditions_verify_url', 'flow_filing_conditions_setting_toggle', 'flow_to_doc']);
        if (isset($flowOthers->flow_to_doc) && $flowOthers->flow_to_doc == 0) {
            return ['show' => false];
        }
        // 只有结束时才有显示的意义
        if (!isset($flowEndParam['is_end'])) {
            // 判断本节点是否是可结束节点
             $getFlowTransactProcessParams = [
                    "flow_process" => $flowEndParam["flow_process"],
                    "run_id" =>  $flowEndParam["run_id"],
                    "flow_id" => $flowEndParam["flow_id"],
                    "user_id" => $flowEndParam["user_id"],
                    'free_process_step'=> 0
            ];
            $processInfo = app($this->flowService)->getFlowTransactProcess($getFlowTransactProcessParams,$userInfo);
            if (  (isset( $processInfo['submitEnd']) && $processInfo['submitEnd'] == 'submitEnd') || (isset($processInfo['turn'][0]['submitEnd']) && $processInfo['turn'][0]['submitEnd'] == 'submitEnd')) {
            } else {
                 return ['show' => false];
            }
        }

        if (isset($flowOthers->flow_filing_conditions_setting_toggle) && $flowOthers->flow_filing_conditions_setting_toggle == 0) {
            $show = true;
        } else {
            $flowEndParam['flow_filing_conditions_setting_value'] = $flowOthers->flow_filing_conditions_setting_value ?? '';
            $flowEndParam['flow_filing_conditions_verify_mode'] = $flowOthers->flow_filing_conditions_verify_mode ?? '';
            $flowEndParam['flow_filing_conditions_verify_url'] = $flowOthers->flow_filing_conditions_verify_url ?? '';
            if (isset($flowEndParam['formData']) && !empty($flowEndParam['formData']) && isset($flowEndParam['form_structure'])  && !empty($flowEndParam['form_structure'])) {
                $formData = $flowEndParam['formData'];
                foreach ($formData as $k => $v) {
                    if (strpos($k, 'COUNTERSIGN') !== false) {
                        $controlId = str_replace('_COUNTERSIGN', '', $k);
                        if (!is_array($formData[$controlId])) {
                            $formData[$controlId] = $formData[$controlId . "_COUNTERSIGN"]['countersign_content'] ?? '';
                            $formData[$controlId] = preg_replace('/<.*?>/', '', $formData[$controlId]);
                        } else {
                        }
                    } else {
                        if (isset($v[0]['countersign_content'])) {
                            $countersign = '';
                            foreach ($v as $value) {
                                $value['countersign_content'] = preg_replace('/<.*?>/', '', $value['countersign_content']);
                                if (!empty($value['countersign_content'])) {
                                    $countersign .= ',' . $value['countersign_content'];
                                }
                            }
                            $formData[$k] = $countersign;
                            // $formData[$k] = $v[0]['countersign_content'];
                        }
                        if (strpos($k, '_amount') !== false) {
                            $controlId = str_replace('_amount', '', $k);
                            if (!isset($formData[$controlId])) {
                                $formData[$controlId] = $v;
                            }
                        }
                    }
                }
                $flowEndParam['formData'] = $formData;
            } else {
                $flowFormDataParam = [
                    'status' => 'handle',
                    'runId'  => $flowEndParam['run_id'],
                    'formId' => $flowEndParam['form_id'],
                    'flowId' => $flowEndParam['flow_id'],
                    'nodeId' =>  $flowEndParam['flow_process'],
                ];
                $flowFormData = app($this->flowService)->getFlowFormParseData($flowFormDataParam, $userInfo);
                $flowEndParam["formData"] = isset($flowFormData['parseData']) ? $flowFormData['parseData'] : [];
                $flowEndParam["form_structure"] = isset($flowFormData['parseFormStructure']) ? $flowFormData['parseFormStructure'] : [];
            }
            $show = true;
            // 设置归档条件时，验证类型设置
            if (isset($flowEndParam["flow_filing_conditions_verify_mode"])) {
                $formData = isset($flowEndParam["formData"]) ? $flowEndParam["formData"] : [];
                // 1、使用表达式进行判断
                if ($flowEndParam["flow_filing_conditions_verify_mode"] == "1") {
                    if (isset($flowEndParam["flow_filing_conditions_setting_value"])) {
                        $flowOtherInfo = [
                            'form_structure' => isset($flowEndParam["form_structure"]) ? $flowEndParam["form_structure"] : [],
                            'user_id' => isset($flowEndParam["user_id"]) ? $flowEndParam["user_id"] : "",
                            'process_id' => isset($flowEndParam["process_id"]) ? $flowEndParam["process_id"] : "",
                        ];
                        $verify = $this->verifyFlowFormOutletCondition($flowEndParam["flow_filing_conditions_setting_value"], $formData, $flowOtherInfo);
                        if (!$verify) {
                            $show = false;
                        }
                    } else if ($flowEndParam["flow_filing_conditions_verify_mode"] == "2") {
                        // 2、使用文件进行判断
                        if (isset($flowEndParam["flow_filing_conditions_verify_url"]) && $flowEndParam["flow_filing_conditions_verify_url"]) {
                            $runInfo = app($this->flowRunRepository)->getDetail($flowEndParam["run_id"]  ,false , ['run_name']);
                            // 文件url
                            $verifyFileUrl = parse_relative_path_url($flowEndParam["flow_filing_conditions_verify_url"]);
                            $formData['run_id'] = $flowEndParam["run_id"];
                            $formData['run_name'] = $runInfo->run_name;
                            $formData['flow_id'] = $flowEndParam["flow_id"];
                            $formData['process_id'] = $flowEndParam['process_id'];
                            $formData['user_id'] = $flowEndParam["user_id"];
                            try {
                                $guzzleResponse = (new Client())->request('POST', $verifyFileUrl, ['form_params' => $formData]);
                                $status = $guzzleResponse->getStatusCode();
                            } catch (\Exception $e) {
                                $status = $e->getMessage();
                            }
                            $validateFlag = false;
                            if (!empty($guzzleResponse)) {
                                //返回结果
                                $content = $guzzleResponse->getBody()->getContents();
                                if ($content == 'true' || $content == '1' || $content === true) {
                                    $validateFlag = true;
                                }
                            }
                            if (!$validateFlag) {
                                $show = false;
                            }
                        }
                        if (!$validateFlag) {
                            $show = false;
                        }
                    }
                }
            }
        }
        if ($show == true ) {
             $flow_type = app($this->flowTypeRepository)->getDetail($flowEndParam['flow_id'], false, ['flow_type']);
             if  (isset($flow_type->flow_type) && $flow_type->flow_type == 1) {
                // 分支上的节点不能结束的话不能显示
                 $canFinished = app($this->flowParseService)->isCanFinishedOnBranch($flowEndParam["flow_id"], $flowEndParam["run_id"], $flowEndParam["process_id"] , $flowEndParam["flow_process"]);
                 $show = $canFinished ? true :  false;
             }
        }
        return ['show' => $show];
    }
    /**
     * @DESC 获取flow_others信息
     * @param $flow_id
     * @return mixed
     * @author wz
     * @since 2019/12/26
     */
    public function getFlowOthersDetailByFlowId($flow_id)
    {
        return   app($this->flowOthersRepository)->getDetail($flow_id);
    }

    /**
     * 判断流程步骤是否有已触发的子流程
     *
     * @param [array] $flowRunProcess
     * @return void
     */
    public function handleIfFlowRunProcessHasSonFlows($flowRunProcess, $maxProcessId) {
        $hasSonFlows = 0;
        // 最新步骤的上一步提交时已触发的子流程
        // 20200515,zyx,由于新加的流程节点退回时也能触发子流程功能，因此需要判断最新步骤是正常提交还是回生成
        // 使用最新步骤的上一步逻辑去查看是否触发了子流程
        if ($flowRunProcess["process_id"] == ($maxProcessId - 1)) {
            // 如果是退回生成，则收回子流程逻辑需要同时判断上一步是否开启了退回触发子流程
            // 如果是提交生成，直接判断上一步骤是否有已触发的子流程
            if (
                ($flowRunProcess['is_back'] && $flowRunProcess["flow_run_process_has_one_flow_process"]['trigger_son_flow_back']) ||
                !$flowRunProcess['is_back']
            ) {
                $hasSonFlows = $flowRunProcess['sub_flow_run_ids'] ? 1 : 0;
            }
        }
        return $hasSonFlows;
    }

    /**
     * 获取可办理或可收回的运行节点
     *
     * @author zyx
     * @return array
     */
    public function getFlowRunProcessListToDealWith($params, $userInfo) {
        $type = $params['type'] ?? 'handle';
        unset($params['type']);

        // 获取监控配置参数
        $flowMonitorParams = app($this->flowService)->getMonitorParamsByUserInfo($userInfo);
        $monitorRules = $flowMonitorParams['monitor_rules'] ?? [];

        // 获取流程运行信息
        $flowRunInfo = app($this->flowRunRepository)->getFlowRunningInfo($params['run_id'], [])->toArray();
        $flowType = $flowRunInfo['flow_run_has_one_flow_type']['flow_type'];
        $formId = $flowRunInfo['flow_run_has_one_flow_type']['form_id'];
        $instancyType = $flowRunInfo['instancy_type'];
        $creator = $flowRunInfo['creator'];
        $flowId = $flowRunInfo['flow_id'];

        // 流程的所有运行节点
        $flowRunProcessList = $flowRunInfo['flow_run_has_many_flow_run_process'];
        // 并发逻辑下，统一使用 flow_serial 字段的最大值表示最新步骤。
        $flowRunInfo['max_flow_serial'] = $maxFlowSerial = app($this->flowRunProcessRepository)->getFieldMaxValue('flow_serial', ['run_id' => [$params['run_id']]]);
        $lastFlowSerial = $maxFlowSerial - 1;

        $i = 0;
        $flowRunProcessListToDealWith = [];
        // 判断查看权限的标识
        $viewArr = [];

        // 监控权限判断
        $monitorAllows = [];
        if (!empty($monitorRules)) {
            $monitorAllows = app($this->flowRunService)->checkFlowMonitorButtonDetails($monitorRules, $flowId, $creator);
        }

        // 监控收回权限
        $monitorTakeBackFlag = 0;
        if ($monitorAllows && $monitorAllows['allow_take_back']) {
            $monitorTakeBackFlag = 1;
        }

        $flowBranchProcessArrToTakeBack = []; // 一个节点多个办理人只返回一条数据

        // 记录主办人已提交的数据,提供给监控提交弹框列表使用
        $hostFlowProcess = [];

        foreach ($flowRunProcessList as $value_process) {
            if ($value_process['host_flag'] == 1 && $value_process['user_run_type'] != 1) {
                array_push($hostFlowProcess, $value_process['process_id']);
            }
        }
        foreach ($flowRunProcessList as $key_process => $value_process) {
            if ($type == 'handle') { // 判断是否有当前人员可以办理的运行节点
                if (
                    ($value_process['user_id'] == $userInfo['user_id']) &&
                    ($value_process['user_run_type'] == 1)
                ) {
                    // 当前序号
                    $orderNo = $this->getTargetStepsOrderNo($value_process['flow_serial'], $value_process['branch_serial'], $value_process['process_serial']);
                    if ($value_process['process_type'] == 'free' && $value_process['free_process_step']) {
                        $value_process['current_steps'] = $orderNo . ': ' . $this->getFreeProcessName($value_process['run_id'], $value_process['flow_process'], $value_process['free_process_step']);
                    } else {
                        $value_process['current_steps'] = $orderNo . ': ' . $value_process["flow_run_process_has_one_flow_process"]["process_name"];
                    }
                    $flowRunProcessListToDealWith[] = $value_process;
                    $i++;
                }
            } else if ($type == 'take_back') { // 判断当前步骤节点的收回权限
                // 没有监控收回权限的普通用户只对自己是主办人的节点条目进行判断
                if (
                    !$monitorTakeBackFlag &&
                    (
                        ($value_process['user_id'] != $userInfo['user_id']) ||
                        !$value_process['host_flag']
                    )
                ) {
                    continue;
                }

                // 有监控收回权限的用户只判断max_flow_serial和max_flow_serial-1步骤的数据条目
                if (
                    $monitorTakeBackFlag &&
                    (
                        (
                            !$value_process['branch_serial'] && // 普通步骤节点，则必须是整个流程的倒数第二步才需要判断收回权限
                            ($value_process['flow_serial'] != $lastFlowSerial)
                        ) ||
                        (
                            $value_process['branch_serial'] && // 分支步骤节点，则必须是max_flow_serial和max_flow_serial-1步骤节点采取判断收回权限
                            ($value_process['flow_serial'] < $lastFlowSerial)
                        )
                    )
                ) {
                    continue;
                }

                $currentFlowRunProcess = $flowRunInfo;
                $currentFlowRunProcess['flow_run_process_list'] = $flowRunProcessList;
                $currentFlowRunProcess['current_flow_run_process_info'] = $value_process;
                // 判断收回权限
                $takeBackRes = $this->checkFlowRunProcessTakeBackAuthority($currentFlowRunProcess, $userInfo, $monitorRules);
                // 如果有收回权限则先塞到统计数组里
                if (
                    ($takeBackRes['takeBackFlag'] || $takeBackRes['monitorTakeBackFlag']) &&
                    !isset($flowBranchProcessArrToTakeBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']]) // 一个可收回节点只返回一条数据
                ) {
                    $flowRunProcessListToDealWith[$i] = $value_process;
                    $flowRunProcessListToDealWith[$i]['has_been_checked'] = $takeBackRes['hasBeenChecked'];//最新节点是否已经有人查看
                    $flowRunProcessListToDealWith[$i]['take_back_flag'] = $takeBackRes['takeBackFlag'];//普通收回权限
                    $flowRunProcessListToDealWith[$i]["monitor_take_back_flag"] = $takeBackRes['monitorTakeBackFlag'];//监控收回权限

                    // 当前可收回步骤已触发的子流程
                    $flowRunProcessListToDealWith[$i]['has_son_flows'] = $value_process['sub_flow_run_ids'] ? 1: 0;

                    // 当前可收回步骤的办理人
                    $handlerRes = app($this->flowParseService)->getStepHandlersBasedOnFlowRunProcessList($flowRunProcessList, ['process_id' => $value_process['process_id']]);
                    $flowRunProcessListToDealWith[$i]['handle_user_name_str'] = trim($handlerRes["handle_user_name_str"], ',');
                    $flowRunProcessListToDealWith[$i]['handle_user_info_arr'] = $handlerRes["handle_user_info_arr"];

                    // 统计过的可收回节点做个标识
                    $flowBranchProcessArrToTakeBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']] = 1;

                    // 序号
                    $orderNo = $this->getTargetStepsOrderNo($value_process['flow_serial'], $value_process['branch_serial'], $value_process['process_serial']);
                    if ($value_process['process_type'] == 'free' && $value_process['free_process_step']) {
                        $flowRunProcessListToDealWith[$i]['can_take_back_to_steps'] = $orderNo . ': ' . $this->getFreeProcessName($value_process['run_id'], $value_process['flow_process'], $value_process['free_process_step']);
                    } else {
                        $flowRunProcessListToDealWith[$i]['can_take_back_to_steps'] = $orderNo . ': ' . $value_process["flow_run_process_has_one_flow_process"]["process_name"];
                    }
                    $i++;
                }
            } else if ($type == 'view') {
                // 判断是否是流程参与人
                if ($value_process['user_id'] == user()['user_id'] || $value_process['by_agent_id'] == user()['user_id']) {
                    if (! in_array($key_process, $viewArr)) {
                        array_push($flowRunProcessListToDealWith, $value_process);
                        array_push($viewArr, $key_process);
                        $i++;
                    }
                }
                // 是否有监控查看权限
                if (isset($monitorAllows['allow_view']) && $monitorAllows['allow_view']) {
                    array_push($flowRunProcessListToDealWith, $value_process);
                    array_push($viewArr, $key_process);
                    $i++;
                }
                foreach ($value_process['flow_run_process_has_many_agency_detail'] as $v) {
                    if ($v['user_id'] == user()['user_id'] || $v['by_agency_id'] == user()['user_id']) {
                        if (! in_array($key_process, $viewArr)) {
                            array_push($flowRunProcessListToDealWith, $value_process);
                            array_push($viewArr, $key_process);
                            $i++;
                        }
                    }
                }
                // 是否是被抄送人
                $copyUserCount = app($this->flowCopyRepository)->getTotal(['search' => ['by_user_id' => [user()['user_id']], 'run_id' => [$value_process['run_id']],
                    'flow_id' => [$value_process['flow_id']], 'process_id' => [$value_process['process_id']]]]);
                if ($copyUserCount) {
                    if (! in_array($key_process, $viewArr)) {
                        array_push($flowRunProcessListToDealWith, $value_process);
                        array_push($viewArr, $key_process);
                        $i++;
                    }
                }
            } else if ($type == 'entrust') {  // 委托处理
                if ($value_process['user_id'] == user()['user_id']) {
                    if (empty($value_process['by_agent_id']) && (
                            ($value_process['host_flag'] == '0' && empty($value_process['saveform_time'])) ||
                            ($value_process['host_flag'] == '1' && empty($value_process['deliver_time'])))) {
                        if ($flowType == '1') {
                            $processEntrust = $value_process["flow_run_process_has_one_flow_process"]["process_entrust"] ?? 0;
                            if ($processEntrust == '1') {
                                $value_process['flow_type'] = $flowType;
                                $flowRunProcessListToDealWith[] = $value_process;
                                $i++;
                            }
                        } else {
                            $value_process['flow_type'] = $flowType;
                            $flowRunProcessListToDealWith[] = $value_process;
                            $i++;
                        }
                    }
                }
            } else if ($type == 'turn') {
                // 分支号不为0
                if ($value_process['user_run_type'] == 1 && $value_process['user_last_step_flag'] ==  1) {
                    // 如果有强制合并的数据并且还没有完全汇总就过滤强制合并节点，否则展示
                    if ($value_process['flow_run_process_has_one_flow_process']['merge'] == 2) {
                        if (
                            ! app($this->flowParseService)->isFinishedMergeProcess(
                            $value_process['flow_id'], $value_process['run_id'], $value_process['flow_run_process_has_one_flow_process']['node_id'])
                        )
                        continue;
                    }
                    // 如果同步骤上已经有主办人办理，那么过滤掉这样的经办人数据
                    if (in_array($value_process['process_id'], $hostFlowProcess)) {
                        continue;
                    }
                    $value_process['flow_type'] = $flowType;
                    $value_process['form_id'] = $formId;
                    $value_process['instancy_type'] = $instancyType;
                    $handlerRes = app($this->flowParseService)->getStepHandlersBasedOnFlowRunProcessList($flowRunProcessList, ['user_run_type' => 1, 'process_id' => $value_process['process_id']]);
                    $value_process["handle_user_name_str"] = rtrim($handlerRes["handle_user_name_str"], ',');
                    $value_process["handle_user_info_arr"] = $handlerRes["handle_user_info_arr"];
                    // 当前序号
                    $orderNo = $this->getTargetStepsOrderNo($value_process['flow_serial'], $value_process['branch_serial'], $value_process['process_serial']);
                    if ($value_process['process_type'] == 'free' && $value_process['free_process_step']) {
                        $value_process['current_steps'] = $orderNo . ': ' . $this->getFreeProcessName($value_process['run_id'], $value_process['flow_process'], $value_process['free_process_step']);
                    } else {
                        $value_process['current_steps'] = $orderNo . ': ' . $value_process["flow_run_process_has_one_flow_process"]["process_name"];
                    }
                    if ($value_process["host_flag"] == '1' && !empty($value_process["send_back_user"]) && $value_process["is_back"] == '1') {
                        $value_process['handle_user'] = $value_process["user_id"];
                    }
                    $flowRunProcessListToDealWith[] = $value_process; // 所有可监控提交的数据
                    $i++;
                }
            } else if ($type == 'turn_back') { // 监控退回
                if ($value_process['user_run_type'] == 1 && $value_process['user_last_step_flag'] == 1) {
                    if ($value_process['flow_run_process_has_one_flow_process']['head_node_toggle'] == 1) {
                        continue;
                    }
                    // 如果同步骤上已经有主办人办理，那么过滤掉这样的经办人数据
                    if (in_array($value_process['process_id'], $hostFlowProcess)) {
                        continue;
                    }
                    $value_process['flow_type'] = $flowType;
                    $value_process['form_id'] = $formId;
                    $value_process['instancy_type'] = $instancyType;
                    // 当前步骤序号
                    $orderNo = $this->getTargetStepsOrderNo($value_process['flow_serial'], $value_process['branch_serial'], $value_process['process_serial']);
                    if ($value_process['process_type'] == 'free' && $value_process['free_process_step']) {
                        $value_process['current_steps'] = $orderNo . ': ' . $this->getFreeProcessName($value_process['run_id'], $value_process['flow_process'], $value_process['free_process_step']);
                    } else {
                        $value_process['current_steps'] = $orderNo . ': ' . $value_process["flow_run_process_has_one_flow_process"]["process_name"];
                    }
                    $handlerRes = app($this->flowParseService)->getStepHandlersBasedOnFlowRunProcessList($flowRunProcessList, ['user_run_type' => 1, 'process_id' => $value_process['process_id']]);
                    $value_process["handle_user_name_str"] = rtrim($handlerRes["handle_user_name_str"], ',');
                    $value_process["handle_user_info_arr"] = $handlerRes["handle_user_info_arr"];
                    $flowRunProcessListToDealWith[] = $value_process; // 所有可监控提交的数据
                    $i++;
                }
            }
        }
        // 处理并发时监控提交的数据
        if ($type == 'turn') {
            $data = [];
            foreach (array_group_by($flowRunProcessListToDealWith, 'branch_serial') as $value) {
                $processId = [];
                foreach ($value as $v) {
                    if ($v['process_id'] == collect($value)->max('process_id') && ! in_array($v['process_id'], $processId)) {
                        array_push($processId, $v['process_id']);
                        //$v['can_turn_to_steps'] = $this->getTakeToTargetSteps($v['run_id'], $v['flow_process'], $v['process_id'], $type);
                        $data[] = $v;
                    }
                }
            }
            $flowRunProcessListToDealWith = $data;
        } else if ($type == 'turn_back') {
            $data = [];
            $branchAndSerial = [];
            foreach (array_group_by($flowRunProcessListToDealWith, 'branch_serial') as $value) {
                $processId = [];
                foreach ($value as $v) {
                     // 合并节点可能会有多个这样的节点
                    if ($v['process_id'] == collect($value)->max('process_id') && ! in_array($v['process_id'], $processId) && !in_array($v['flow_serial']."|".$v['flow_process'], $branchAndSerial)) {
                        array_push($branchAndSerial, $v['flow_serial']."|".$v['flow_process']);
                        $TargetSteps = $this->getTakeToTargetSteps($v['run_id'], $v['flow_process'], $v['process_id'], $type);
                        if ($TargetSteps) {
                            $v['can_turn_back_to_steps'] = $TargetSteps;
                            $data[] = $v;
                        }
                    }
                }
            }
            $flowRunProcessListToDealWith = $data;
        }
        return (isset($params['page']) && isset($params['limit']) && !empty($flowRunProcessListToDealWith)) ? ['list' => array_chunk($flowRunProcessListToDealWith, $params['limit'])[$params['page'] - 1], 'total' => count($flowRunProcessListToDealWith)] : $flowRunProcessListToDealWith;
    }

    /**
     * 获取可提交、退回的目标节点或步骤
     * @param $runId
     * @param $flowProcess
     * @param $processId
     * @param $type
     * @return string
     */
    public function getTakeToTargetSteps($runId, $flowProcess, $processId, $type)
    {
        $data = app($this->flowService)->getFlowTransactProcess([
            'run_id' => $runId,
            'user_id' => user()['user_id'],
            'flow_process' => $flowProcess,
            'process_id' => $processId
        ], user());
        $processName = '';
        switch ($type) {
            //  暂时不使用可提交至步骤，前端这里一列删除
            case 'turn':
               $turn = $data['turn'] ?? [];
               if ($turn) {
                   foreach ($turn as $value) {
                       $processName .= $value['process_name'].'、' ?? trans('flow.end') . ' ';
                   }
               }
               break;
            case 'turn_back':
                $back = $data['back'] ?? [];
                if ($back) {
                    foreach ($back as $value) {
                        $processName .= $value['process_name'] . '、';
                    }
                }
                break;
            default:
                break;
        }
        return trim($processName, '、');
    }

    /**
     * 获取流程办理过程中操作的当前步骤或目标步骤的前缀序号，取决于是当前参数还是目标参数等
     * @param $flowSerial
     * @param $branchSerial
     * @param $processSerial
     * @return string
     */
    public function getTargetStepsOrderNo($flowSerial, $branchSerial, $processSerial)
    {
        $targetSteps  = '';
        if ($branchSerial && $processSerial) {
            $targetSteps = $flowSerial . '-' . $branchSerial . '-' . $processSerial;
        } else {
            $targetSteps = $flowSerial;
        }
        return $targetSteps;
    }


    // 更新流程运行中flow_run_process表某个人的最新数据标识
    public function updateLastFlagAtSubmit($param) {
        $runId = $param['run_id'] ?? '';
        $userId = $param['user_id'] ?? '';
        $branchSerial = $param['branch_serial'] ?? 0;
        $flowSerial = $param['flow_serial'] ?? 0;
        $flowProcess = $param['flow_process'] ?? 0;
        $hostFlag = $param['host_flag'] ?? 0;
        if(empty($runId) || empty($userId)) {
            return false;
        }
        // 如果是并发分支上的人  更新当前分支上的历史数据以及之前的非并发数据
        if(!empty($branchSerial)) {
            // 如果最新步骤为主办人
            if($hostFlag) {
                // 更新分支上的之前数据user_last_flag为0 当前人数据
                app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_last_step_flag" => 0], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "branch_serial" => [$branchSerial], "flow_serial" => [$flowSerial]]]);
                return true;
            }else {
                // 更新之前已办理的主办人和所有经办人user_last_flag为0 当前人数据
                app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_last_step_flag" => 0], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "branch_serial" => [$branchSerial], "flow_serial" => [$flowSerial]], "whereRaw" => ["(host_flag = 0 or (host_flag = 1 and user_run_type != 1))"]]);
                return true;
            }
        }else{
            $merge = false;
            if ($flowProcess) {
                $flowProcessInfo = app($this->flowProcessRepository)->getDetail($flowProcess, false , ['merge']);
                if (isset($flowProcessInfo['merge']) && $flowProcessInfo['merge'] == 1) {
                    $merge = true;
                }
            }
            if ($merge) {
                // 更新之前非并发分支上的当前人数据标识为0
                app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_last_step_flag" => 0], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "branch_serial" => [0],  "flow_serial" => [$flowSerial, "<"]]]);
                // 更新当前合并分支上的已办理的当前人user_last_flag为0
                app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_last_step_flag" => 0], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "branch_serial" => [0],  "flow_serial" => [$flowSerial], "user_run_type"=>[1, '!='], "flow_process"=>[$flowProcess]]]);
            }else {
                // 如果最新步骤为主办人
                if($hostFlag) {
                    // 更新非并发分支上的之前数据user_last_flag为0 当前人数据
                    app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_last_step_flag" => 0], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "branch_serial" => [0],  "flow_serial" => [$flowSerial, "<="]]]);
                    return true;
                }else {
                    // 更新非并发分支上的之前已办理的主办人和所有经办人user_last_flag为0 当前人数据
                    app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["user_last_step_flag" => 0], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "branch_serial" => [0],  "flow_serial" => [$flowSerial, "<="]], "whereRaw" => ["(host_flag = 0 or (host_flag = 1 and user_run_type != 1))"]]);
                    return true;
                }
            }
        }
    }

    /**
     * 判断流程催办权限,用在列表
     *
     * @description 我的委托列表、流程创建人、上节点主办人、当前节点主办人、流程监控权限（催办），可催办
     *
     * @author zyx
     * @since 20200826
     *
     * @return boolean
     */
    public function checkFlowLimitAuthority($flowRunInfo, $userInfo, $monitorRules, $originFrom = '') {
        $flowRunProcessList = $flowRunInfo['flow_run_process_list'] ?? [];
        $currentFlowRunProcessInfo = $flowRunInfo['current_flow_run_process_info'] ?? [];
        $flowRunInfoDetail = $flowRunInfo['flow_run_info_detail'] ?? $flowRunInfo;

        // 1.已结束的流程不能催办
        if (!$flowRunInfoDetail['current_step']) {
            return false;
        }

        // 2.我的委托列表，或者当前数据的委托人（相当于委托列表），如果被委托人还没办理，则有催办权限
        if (($originFrom == 'myAgency') ||
            ($currentFlowRunProcessInfo && ($currentFlowRunProcessInfo['by_agent_id'] == $userInfo['user_id']))
        ) {
            return (
                    ($currentFlowRunProcessInfo['host_flag'] && empty($currentFlowRunProcessInfo['deliver_time'])) || // 是主办人，但未办理
                    (empty($currentFlowRunProcessInfo['host_flag']) && empty($currentFlowRunProcessInfo['saveform_time'])) // 是经办人，但未办理
                ) ? true : false;
        }

        // 3.流程创建人默认有催办权限
        $creator = $flowRunInfo['creator'] ?? $flowRunInfoDetail['creator'];
        $creatorAuth = false;
        if ($creator == $userInfo['user_id']) {
            $creatorAuth = true;
        }
        // 4.对本类流程的监控催办权限（基于流程监控设置）
        $monitorButtonDetails = $this->checkFlowMonitorButtonDetails($monitorRules, $flowRunInfo['flow_id'], $creator);
        $monitorAuth = false;
        if ($monitorButtonDetails && $monitorButtonDetails['allow_urge']) {
            $monitorAuth = true;
        }

        // 待办、已办事宜、超时列表
        // 5.有指定数据的情况下判断这条数据的催办权限
        if ($currentFlowRunProcessInfo) {
            // 超时列表也是多条数据，如果尚未提交且办理人不是自己，则可以催办，不区分主办人、经办人
            if ($originFrom == 'overtime') {
                if (
                    ($currentFlowRunProcessInfo['user_id'] != $userInfo['user_id']) &&
                    ( // 办理人不是自己
                        ($currentFlowRunProcessInfo['host_flag'] && ($currentFlowRunProcessInfo['process_flag'] < 3)) || // 主办人未提交
                        (!$currentFlowRunProcessInfo['host_flag'] && ($currentFlowRunProcessInfo['saveform_time'] === null)) // 经办人未提交
                    )
                ) {
                    return true;
                }

                return false;
            }
            // 查看当前节点数据的催办权限
            return $this->checkFlowRunProcessLimitAuthority($flowRunInfo, $currentFlowRunProcessInfo, $originFrom, $userInfo);
        }

        // 6.创建人、监控催办，如果只有自己可以催办则不返回权限
        if ($creatorAuth || $monitorAuth) {
            $FRPInfo = $this->getFlowRunProcessInfoToLimitRealize($flowRunInfo, [], $userInfo);
            if (
                !$FRPInfo || // 没有可催办人员
                (
                    (count($FRPInfo) == 1) && // 只有一个可催办人员且是自己
                    ($FRPInfo[0]['user_id'] == $userInfo['user_id'])
                )
            ) {
                return false;
            }

            return true;
        }

        // 7.没有特殊权限的情况下，验证用户在流程中的待办、已办催办权限
        if ($flowRunProcessList) {
            foreach ($flowRunProcessList as $flowRunProcess) {
                // 当前用户是主办人的再去判断
                if (
                    $flowRunProcess['host_flag'] &&
                    ($flowRunProcess['user_id'] == $userInfo['user_id'])
                ) {
                    $flowRunInfo['current_flow_run_process_info'] = $flowRunProcess;
                    // 查看当前节点数据的催办权限
                    $res = $this->checkFlowRunProcessLimitAuthority($flowRunInfo, $flowRunProcess, $originFrom, $userInfo);
                    if ($res) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 获取返回可以催办的运行节点信息和被催办人员
     *
     * @author zyx
     * @since 20201106
     *
     * @return array
     */
    public function getFlowRunProcessInfoToLimit($runInfo, $userInfo, $monitorRules) {
        $from = $runInfo['from'] ?? ""; // 来自我的委托列表、待办事宜
        $runId = $runInfo['run_id'];

        $flowRunProcessToLimit = [];
        // 如果传递了flow_run_process_id，且标明来源，则来自我的委托列表/待办列表
        // 则只催办这条数据的未办理人员/这个节点的未办理人员
        if (isset($runInfo['flow_run_process_id']) && $from) {
            // 从我的委托列表发起的，只能催办被委托人,且要求被委托人尚未办理
            $flowRunCurrentData = [
                "run_id" => $runId,
                "fields" => ["flow_run_process_id", "user_id", "flow_id"],
            ];

            if (($from == 'myAgency') || ($from == 'overtime')) { // 我的委托、超时列表只能催办未办理的被催办人
                $flowRunCurrentData["search"] = [
                    "flow_run_process_id" => [$runInfo['flow_run_process_id']],
                    // "by_agent_id" => [$userInfo['user_id']],
                ];
                $flowRunCurrentData["whereRaw"] = ["((saveform_time IS NULL and host_flag =0) or (deliver_time IS NULL and host_flag =1))"];
            } else if ($from == 'to_do') { // 待办催办当前节点未办理经办人
                $flowRunCurrentData["search"] = [
                    "process_id" => [$runInfo['process_id']],
                ];
                $flowRunCurrentData["whereRaw"] = ["saveform_time IS NULL and host_flag =0"];
            }

            // 找到需要催办的flow_run_process条目
            $targetFlowRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunCurrentData);
            if (!$targetFlowRunProcessInfo) {
                return [];
            }
            $targetFlowRunProcessInfo = $targetFlowRunProcessInfo->toArray();

            $i = 0;
            foreach ($targetFlowRunProcessInfo as $value) {
                $flowRunProcessToLimit[$i]['user_id'] = $value["user_id"];
                $flowRunProcessToLimit[$i]['flow_id'] = $value['flow_id'];
                $flowRunProcessToLimit[$i]['flow_run_process_id'] = $value['flow_run_process_id'];
                $i++;
            }

            return $flowRunProcessToLimit;
        }

        // 如果不是我的委托列表、待办事宜，则是从已办事宜、我的请求、我的监控、流程查询列表发起
        $flowRunInfo = app($this->flowRunRepository)->getFlowRunningInfo($runId, []);
        if (!$flowRunInfo) {
            return [];
        }
        $flowRunInfo = $flowRunInfo->toArray();
        $flowRunInfo['flow_run_process_list'] = $flowRunInfo['flow_run_has_many_flow_run_process'];

        // 20200902 并发逻辑下，统一使用 flow_serial 字段的最大值表示最新步骤。
        $flowRunInfo['max_flow_serial'] = app($this->flowRunProcessRepository)->getFieldMaxValue('flow_serial', ['run_id' => [$runId]]);

        // 已办事宜列表指定了具体某条数据，则只判断基于这条数据的催办权限
        if (isset($runInfo['flow_run_process_id'])) {
            foreach ($flowRunInfo['flow_run_process_list'] as $flowRunProcessValue) {
                if ($flowRunProcessValue['flow_run_process_id'] == $runInfo['flow_run_process_id']) {
                    $flowRunInfo['current_flow_run_process_info'] = $currentFlowRunProcessInfo = $flowRunProcessValue;

                    break;
                    // 验证当前数据催办权限
                    // if(!$this->checkFlowRunProcessLimitAuthority($flowRunInfo, $currentFlowRunProcessInfo)) { // 当前数据没有催办权限
                    //     return [];
                    // }
                }
            }

            // 返回可以催办的节点信息
            return $this->getFlowRunProcessInfoToLimitRealize($flowRunInfo, $currentFlowRunProcessInfo, $userInfo);
        }

        // 其他列表，直接判断整个流程的催办权限
        // if (!$this->checkFlowLimitAuthority($flowRunInfo, $userInfo, $monitorRules)) { // 没有催办权限
        //     return [];
        // }

        // 只催办对流程的流转有影响的人员，如果进行到下一步了，当前步骤的未办理经办人就不再催办
        return $this->getFlowRunProcessInfoToLimitRealize($flowRunInfo, [], $userInfo);
    }

    /**
     * 找出可以催办的运行节点数据
     *
     * @author zyx
     * @since 20201106
     *
     * @return array
     */
    public function getFlowRunProcessInfoToLimitRealize($flowRunInfo, $currentFlowRunProcessInfo, $userInfo) {
        $returnData = [];
        $i = 0;

        // 获取可以催办的节点flow_serial branch_serial process_serial数据
        $desinatedFBPData = $this->getFlowBranchProcessSerialToLimit($flowRunInfo, $currentFlowRunProcessInfo, $userInfo);

        // 找出可催办节点的未办理人员
        foreach ($desinatedFBPData as $value) {
            if (
                (($value['host_flag']) && ($value['process_flag'] < 3)) || // 主办人未提交
                ((!$value['host_flag']) && ($value['saveform_time'] === null)) // 经办人未提交
            ) {
                $returnData[$i]['user_id'] = $value['user_id'];
                $returnData[$i]['flow_id'] = $value['flow_id'];
                $returnData[$i]['flow_run_process_id'] = $value['flow_run_process_id'];
                $i++;
            }
        }

        return $returnData;
    }

    /**
     * 判断某条flow_run_process数据的催办权限
     *
     * @author zyx
     * @since 20201106
     *
     * @return boolean
     */
    public function checkFlowRunProcessLimitAuthority($flowRunInfo, $currentFlowRunProcessInfo, $originFrom = '', $userInfo) {
        $flowRunProcessList = $flowRunInfo['flow_run_process_list'];
        $currentFlowProcessInfo = $currentFlowRunProcessInfo['flow_run_process_has_one_flow_process'];
        $maxFlowSerial = $flowRunInfo['max_flow_serial'];
        $currentFlowProcessInfo['concurrent'] = $currentFlowProcessInfo['concurrent'] ?? 0;
        // 1.不是当前步骤节点的主办人不能催办
        if (!$currentFlowRunProcessInfo['host_flag']) {
            return false;
        }

        // 2.待办条目，如果有不是自己的经办人未提交，则可以催办
        if ($currentFlowRunProcessInfo['process_flag'] < 3) { // 待办条目
            // 查找尚未办理的条目
            foreach ($flowRunProcessList as $value) {
                if (
                    ($value['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) &&
                    ($value['branch_serial'] == $currentFlowRunProcessInfo['branch_serial']) &&
                    ($value['process_serial'] == $currentFlowRunProcessInfo['process_serial']) && // 当前节点
                    ($value['user_id'] != $userInfo['user_id']) && // 办理人不是自己
                    !$value['host_flag'] && // 经办人
                    empty($value['saveform_time']) // 尚未提交
                ) {
                    return true;
                }
            }

            return false;
        }

        // 已经提交的节点，需要具体判断下一步的情况

        // 如果当前步骤是普通步骤不是分支上的步骤，则判断下一步骤具体情况
        if (!$currentFlowRunProcessInfo['branch_serial']) {
            $nextFlowSerial = $currentFlowRunProcessInfo['flow_serial'] + 1;
            $arrOfBPSerial = [];
            $branchType = 0;
            foreach ($flowRunProcessList as $value) {
                if ($value['flow_serial'] == $nextFlowSerial) {
                    // 下一步不是分支上的节点，判断是否已经提交
                    if (!$value['branch_serial']) {
                        // 如果已经提交则不能催办
                        if (
                            $value['host_flag'] && // 主办人条目
                            ($value['process_flag'] > 2) // 已经提交
                        ) {
                            return false;
                        }
                    } else { // 下一步骤是分支节点，则统计各分支步骤
                        $branchType = 1;
                        if (
                            !isset($arrOfBPSerial[$value['branch_serial']]) ||
                            ($arrOfBPSerial[$value['branch_serial']] < $value['process_serial'])
                        ) {
                            $arrOfBPSerial[$value['branch_serial']] = $value['process_serial'];
                        }
                    }
                }
            }

            if ($branchType) { // 下一步骤是分支步骤
                // 如果有某个分支只有一步可以催办
                if (in_array(1, $arrOfBPSerial)) {
                    return true;
                }
                // 每个分支都超过一步则不能催办
                return false;
            } else { // 下一步骤是普通步骤，且尚未提交，则可以催办
                return true;
            }
        }

        // 当前步骤是分支上的节点,看当前步骤在分支上的位置
        $maxProcessSerialDiff = 0;
        foreach ($flowRunProcessList as $value) {
            // 当前分支步骤
            if (
                ($value['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) &&
                ($value['branch_serial'] == $currentFlowRunProcessInfo['branch_serial'])
            ) {
                // 步骤差
                $processSerialDiff = $value['process_serial'] - $currentFlowRunProcessInfo['process_serial'];
                $maxProcessSerialDiff  = ($maxProcessSerialDiff > $processSerialDiff) ? $maxProcessSerialDiff : $processSerialDiff;

                // 如果有超过当前步骤2步的步骤，当前步骤不能催办
                if ($processSerialDiff > 1) {
                    return false;
                }

                // 下一步主办人尚未提交，当前步骤可以催办
                if (
                    ($processSerialDiff == 1) &&
                    ($value['process_flag'] < 3)
                ) {
                    return true;
                }
            }
        }

        // 当前步骤是分支上的最后一步
        // 如果max_flow_serial-current_flow_serial==1，说明合并后节点尚未提交，可以催办
        // 其他情况不能催办
        if (!$maxProcessSerialDiff) {
            return (($maxFlowSerial - $currentFlowRunProcessInfo['flow_serial']) == 1) ? true : false;
        }

        return false;
    }

	/*
     * 获取正在运行中流程
     * @param $flowId
     * @return mixed
     */
    public function getFlowRuningCounts($flowId)
    {
        return app($this->flowRunRepository)->getFlowRunList(['search' => [
            'flow_id' => [$flowId],
            'current_step' => [0, '!='],
        ], 'returntype' => 'count']);
    }

    /**
     * 获取办理人在强制合并节点需要等待其他分支或节点办理人的信息
     * @param $params
     * @param $userInfo
     * @param $type 默认返回user_array，service内部调用时可能需要flow_run_process_list
     * @return array
     */
    public function getFlowRunForceMergeInfo($params, $userInfo, $type = 'user')
    {
        $result = app($this->flowRunProcessRepository)->getOneFieldInfo(['flow_run_process_id' => $params['flow_run_process_id']], ['flow_serial', 'run_id', 'flow_id', 'branch_serial', 'flow_process']);
        if ($result) {
            $flowSerial = $result->flow_serial;
            $forceNode = $result->flow_process;
            $params['flow_serial'] = $flowSerial;
            $params['run_id'] = $result->run_id;
            $forceBranchSerial = $params['branch_serial'] = $result->branch_serial;
            $forceMergeInfo = app($this->flowRunProcessRepository)->getFlowRunForceMergeInfo($params);
            $data = [];
            $list = [];

            foreach ($forceMergeInfo as $key => $value) {
                if ($value->flowRunProcessHasOneFlowProcess->merge == 2) { // 过滤在强制并发节点上的 flow_run_process 数据
                    continue;
                }
                if ($value->branch_serial == $forceBranchSerial) { // 过滤自身节点所在的分支 flow_run_process 数据
                    continue;
                }
                $data[] = $value->toArray();
            }
            $flowRunProcessData = app($this->flowRunProcessRepository)->entity->where('run_id' ,$result->run_id )->where('flow_serial' ,$flowSerial)->select('flow_process','branch_serial')->get()->toArray();
            // 过滤并发分支上没有连接到强制合并节点的数据
            $newData = [];
            $flowProcessInfo  =app($this->flowParseService)->getFlowProcessInfo($result->flow_id , false);
            $flowProcessInfo = array_column($flowProcessInfo , null ,'node_id');
            foreach ($data as $key => $value) {
                if ($value['process_flag'] >= 3) {
                    continue;
                }
                $branch =  $value['flow_run_process_has_one_flow_process']['branch'] ?? 0;
                if (!$branch) {
                    foreach ($flowRunProcessData as $runKey => $runValue) {
                        if ($runValue['branch_serial'] ==  $value['branch_serial']) {
                            $branch = $flowProcessInfo[$runValue['flow_process']]['branch'];
                        }
                        if ($branch) {
                            break;
                        }
                    }
                }
                if ( $branch ) {
                    foreach ($flowProcessInfo as $pKey => $pValue) {
                        if($pValue['branch'] == $branch && $pValue['sort'] < $flowProcessInfo[$forceNode]['sort'] && in_array($forceNode, explode(',', $pValue['process_to']))) {
                            // 判读同一分支上节点是否连向了合并节点
                            $newData[] = $value; // 此分支有节点连向了合并节点才有意义
                        }
                    }
                }
            }

            // zyx,20201106,需要返回尚未办理分支上的节点数据
            if ($type == 'frp_list') {
                return $newData;
            }

            $r = [];
            // 按分支分组处理强制合并节点之前待处理的 flow_run_process 数据，方便催办等
            foreach (array_group_by($newData, 'branch_serial') as $key => $value) {
                $r[$key] = $this->getCurrentHandleUserInfo($value);
            }
            $r = array_values($r);
            $list['list'] = $r;
            $list['total'] = count($r);
            return $list;
        }
        return [];
    }

    private function getCurrentHandleUserInfo(array $flowRunProcessInfo)
    {
        $data = [];
        $current_handle_user_name = '';
        foreach ($flowRunProcessInfo as $key => $value) {
            $orderNo = $value['flow_serial'] . '-' . $value['branch_serial'] . '-' . $value['process_serial'];
            $data['current_handle_user_id'][] = [
                $value['flow_run_process_has_one_user']['user_id'],
                $value['flow_run_process_has_one_user']['user_has_one_system_info']['user_status'],
                $value['flow_run_process_has_one_user']['user_name']
            ];
            $current_handle_user_name .= $value['flow_run_process_has_one_user']['user_name'] . ',';
            $data['current_handle_user_name'] = rtrim($current_handle_user_name, ',');
            $data['current_step'] = $orderNo . ': '  . $value['flow_run_process_has_one_flow_process']['process_name'];
        }
        return $data;
    }

    /**
     * 获取流程最新步骤序号 + 名称
     * @param $flowType
     * @param $runId
     * @param $maxFlowSerial
     * @return string
     */
    public function getLatestSteps($flowType, $runId, $maxFlowSerial)
    {
        if ($flowType == 1) {
            $queryParams = [
                "fields"           => ["flow_serial","flow_process","process_type","free_process_step","branch_serial","process_serial"],
                "relationNodeInfo" => true,
                "search"           => ["run_id" => [$runId], "flow_serial" => [$maxFlowSerial]],
                "order_by"         => ["process_id" => "desc"],
                "returntype"       => "first"
            ];
            $latestStepInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($queryParams);
            if (empty($latestStepInfo)) {
                return $maxFlowSerial;
            }
            return $this->jointProcessName($runId, $latestStepInfo->toArray());
        }
        // 自由流程直接返回步骤序号
        return $maxFlowSerial;
    }

    /**
     * 通过单条步骤信息拼接步骤名称
     * @param $runId
     * @param $flowRunProcessInfo
     * @return string
     */
    public function jointProcessName($runId, $flowRunProcessInfo)
    {
        $processNameStr = $flowRunProcessInfo['flow_serial'] ?? '';
        if (!empty($flowRunProcessInfo['branch_serial']) && !empty($flowRunProcessInfo['process_serial'])) {
            $processNameStr .= '-'.$flowRunProcessInfo['branch_serial'].'-'.$flowRunProcessInfo['process_serial'];
        }
        if (!empty($flowRunProcessInfo['process_type']) && $flowRunProcessInfo['process_type'] == 'free' && !empty($flowRunProcessInfo['free_process_step']) && !empty($flowRunProcessInfo['flow_process'])) {
            $processNameStr .= '：'.$this->getFreeProcessName($runId, $flowRunProcessInfo['flow_process'], $flowRunProcessInfo['free_process_step']);

        } else if (!empty($flowRunProcessInfo['flow_run_process_has_one_flow_process']['process_name'])) {
            $processNameStr .= '：'.$flowRunProcessInfo['flow_run_process_has_one_flow_process']['process_name'];
        }
        return $processNameStr;
    }

    /**
     * 获取自由节点步骤名
     * @param $runId
     * @param $nodeId
     * @param $freeProcessStep
     * @return string
     */
    public function getFreeProcessName($runId, $nodeId, $freeProcessStep)
    {
        $freeProcessInfo = $this->getFreeNodeStepInfo($runId, $nodeId, $freeProcessStep);
        return $freeProcessInfo ? $freeProcessInfo->process_name : '';
    }

    /**
     * 获取可以催办的节点flow_serial branch_serial process_serial数据
     *
     * @param array $flowRunInfo
     * @param array $currentFlowRunProcessInfo
     * @return array
     */
    public function getFlowBranchProcessSerialToLimit($flowRunInfo, $currentFlowRunProcessInfo, $userInfo) {
        $flowRunProcessList = $flowRunInfo['flow_run_process_list'];
        // $currentFlowProcessInfo = $currentFlowRunProcessInfo['flow_run_process_has_one_flow_process'];
        $FBPSerialCannotBeLimited = [];

        // 没有指定数据，则催办所有未提交节点
        if (!$currentFlowRunProcessInfo) {
            $forcedMergeArr = [];
            foreach ($flowRunProcessList as $key => $value) {
                // 已经记录过的不能催办节点条目数据直接删除
                if (in_array($value['flow_serial'] . '_' . $value['branch_serial'] . '_' . $value['process_serial'], $FBPSerialCannotBeLimited)) {
                    unset($flowRunProcessList[$key]);
                }
                // 已提交的节点，肯定有主办人
                if ($value['host_flag'] && ($value['process_flag'] > 2)) {
                    $FBPSerialCannotBeLimited[] = $value['flow_serial'] . '_' . $value['branch_serial'] . '_' . $value['process_serial'];
                    unset($flowRunProcessList[$key]);
                }
            }

            foreach ($flowRunProcessList as $key => $value) {
                $flowProcessInfo = $value['flow_run_process_has_one_flow_process'] ?? [];
                // 如果某个节点是合并节点，则一个用户只能保留一条数据
                if (
                    $flowProcessInfo &&
                    ($flowProcessInfo['merge'])
                ) {
                    if (in_array($value['flow_serial'] . '_' . $value['user_id'], $forcedMergeArr)) {
                        unset($flowRunProcessList[$key]);
                        continue;
                    }
                    // 没有保存过的做个记录
                    $forcedMergeArr[] = $value['flow_serial'] . '_' . $value['user_id'];
                }
            }

            return $flowRunProcessList;
        }

        // 有指定数据的时候，需要具体分析
        // 指定节点数据是在已办事宜列表，当前节点数据已经办理，则需要查看当前节点配置

        $nextFlowSerial = $currentFlowRunProcessInfo['flow_serial'] + 1;
        $nextProcessSerial = $currentFlowRunProcessInfo['process_serial'] + 1;
        // 如果当前节点是普通节点，则催办flow_serial+1节点未办理人员
        if (!$currentFlowRunProcessInfo['branch_serial']) {
            foreach ($flowRunProcessList as $key => $value) {
                // 非下一步骤节点条目数据直接删除
                if ($value['flow_serial'] != $nextFlowSerial) {
                    unset($flowRunProcessList[$key]);
                    continue;
                }
                // 已经记录过的不能催办节点条目数据直接删除
                if (in_array($value['branch_serial'], $FBPSerialCannotBeLimited)) {
                    unset($flowRunProcessList[$key]);
                    continue;
                }
                // 过滤已提交的分支节点
                if ($value['host_flag'] && ($value['process_flag'] > 2)) {
                    $FBPSerialCannotBeLimited[] = $value['branch_serial'];
                    unset($flowRunProcessList[$key]);
                    continue;
                }
                // 下一步骤如果是分支步骤，某个分支超过2步则不能催办这个分支
                if ($value['branch_serial'] && ($value['process_serial'] > 1)) {
                    $FBPSerialCannotBeLimited[] = $value['branch_serial'];
                    unset($flowRunProcessList[$key]);
                    continue;
                }
            }

            return $flowRunProcessList;
        }

        $nextProcessSerialList = [];
        $nextFlowSerialList = [];
        // 如果当前节点是分支节点，如果是分支最后步骤，则催办flow_serial+1，否则催办process_serial+1
        foreach ($flowRunProcessList as $value) {
            if (
                ($value['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) &&
                ($value['branch_serial'] == $currentFlowRunProcessInfo['branch_serial']) &&
                ($value['process_serial'] == $nextProcessSerial)
            ) {
                $nextProcessSerialList[] = $value;
            }
            if ($value['flow_serial'] == $nextFlowSerial) {
                $nextFlowSerialList[] = $value;
            }
        }

        return $nextProcessSerialList ? $nextProcessSerialList : $nextFlowSerialList;
    }

    /**
     * @DESC  流程全部节点的收回权限获取，当只有一个节点可收回时返回该节点信息，超过一个节点可收回时仅返回可收回标识canTakeBack = 2
     * @param $flowRunInfo  [array]  主体数据
     * @param $userInfo
     * @param $monitorRules  [array]  监控规则
     *
     * @author zyx
     * @since 2020/10/30
     *
     * @return array
     */
    public function checkFlowRunTakeBackAuthority($flowRunInfo, $userInfo, $monitorRules) {
        $canTakeBack = 0;
        $processInfoToTakeBack = [];

        $flowBranchProcessArrToTakeBack = [];
        $flowBranchProcessArrCannotBeTakenBack = [];

        $maxFlowSerial = $flowRunInfo['max_flow_serial'];
        $lastFlowSerial = $maxFlowSerial - 1;
        $flowRunProcessList = $flowRunInfo['flow_run_process_list'];

        // 流程至少有两步
        // 必须判断是否结束
        if (
            ($maxFlowSerial == 1) ||
            (isset($flowRunInfo['currentStep']) && !$flowRunInfo['currentStep']) ||
            (isset($flowRunInfo['current_step']) && !$flowRunInfo['current_step'])
        ) {
            return ['canTakeBack' => 0, 'processInfoToTakeBack' => []];
        }

        $monitorTakeBackFlag = 0;
        // 当前用户对本类流程的监控收回权限（基于流程监控设置）
        $monitorButtonDetails = $this->checkFlowMonitorButtonDetails($monitorRules, $flowRunInfo['flow_id'], $flowRunInfo['creator']);
        if ($monitorButtonDetails && $monitorButtonDetails['allow_take_back']) {
            $monitorTakeBackFlag = 1;
        }

        // 遍历流程运行节点信息
        foreach ($flowRunProcessList as $value_process) {
            // 超过1个可收回节点的话就不再循环,直接返回特征值
            if ($canTakeBack > 1) {
                break;
            }

            // 如果当前运行节点确认可收回，则直接跳过
            if (isset($flowBranchProcessArrToTakeBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']])) {
                continue;
            }

            // 如果当前运行节点确认不可收回，则直接跳过
            if (isset($flowBranchProcessArrCannotBeTakenBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']])) {
                continue;
            }

            // 没有监控收回权限的普通用户只对自己是主办人的节点条目进行判断
            if (!$monitorTakeBackFlag) {
                // 是自己的条目但不是主办人，则肯定不能收回
                if (
                    ($value_process['user_id'] == $userInfo['user_id']) &&
                    !$value_process['host_flag']
                ) {
                    // 确定不能收回的节点做个记录
                    $flowBranchProcessArrCannotBeTakenBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']] = 1;
                    continue;
                }
                // 不是自己的条目直接跳过
                if ($value_process['user_id'] != $userInfo['user_id']) {
                    continue;
                }
            }

            // 有监控收回权限的用户只判断max_flow_serial和max_flow_serial-1步骤的数据条目
            if (
                $monitorTakeBackFlag &&
                (
                    (
                        !$value_process['branch_serial'] && // 普通步骤节点，则必须是整个流程的倒数第二步才需要判断收回权限
                        ($value_process['flow_serial'] != $lastFlowSerial)
                    ) ||
                    (
                        $value_process['branch_serial'] && // 分支步骤节点，则必须是max_flow_serial和max_flow_serial-1步骤节点采取判断收回权限
                        ($value_process['flow_serial'] < $lastFlowSerial)
                    )
                )
            ) {
                // 确定不能收回的节点做个记录
                $flowBranchProcessArrCannotBeTakenBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']] = 1;
                continue;
            }

            // 当前步骤节点是分支节点
            if ($value_process['branch_serial']) {
                $maxProcessSerial = app($this->flowRunProcessRepository)->getFieldMaxValue('process_serial', [
                    'run_id' => [$flowRunInfo['run_id']],
                    'flow_serial' => [$value_process['flow_serial']],
                    'branch_serial' => [$value_process['branch_serial']]
                ]); // 当前分支最新步骤process_serial
                $maxProcessSerialDifference = $maxProcessSerial - $value_process['process_serial']; // 所在分支最新步骤与当前步骤的步骤差

                // 与当前分支最新步骤差 > 1不能收回
                // 是当前分支最新步骤，如果flow_serial不是整个流程倒数第二步则不能收回
                if (
                    ($maxProcessSerialDifference > 1) ||
                    (
                        !$maxProcessSerialDifference &&
                        ($value_process['flow_serial'] != $lastFlowSerial)
                    )
                ) {
                    // 确定不能收回的节点做个记录
                    $flowBranchProcessArrCannotBeTakenBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']] = 1;
                    continue;
                }

                // 与当前分支最新步骤差 = 1 ，只判断主办人条目
                if (
                    ($maxProcessSerialDifference == 1) &&
                    !$value_process['host_flag']
                ) {
                    continue;
                }
            }

            // 遍历每个运行节点，看当前节点是否有收回权限
            $tempData = $flowRunInfo;
            $tempData['current_flow_run_process_info'] = $value_process;
            $takeBackRes = $this->checkFlowRunProcessTakeBackAuthority($tempData, $userInfo, $monitorRules);
            // 至少一个收回权限
            if ($takeBackRes['takeBackFlag'] || $takeBackRes['monitorTakeBackFlag']) {
                $canTakeBack++;
                $processInfoToTakeBack = $takeBackRes;
                $processInfoToTakeBack['has_son_flows'] = $value_process['sub_flow_run_ids'] ? 1 : 0;
                $processInfoToTakeBack['process_id'] = $value_process['process_id'];
                $processInfoToTakeBack['flow_process'] = $value_process['flow_process'];
                $processInfoToTakeBack['flow_run_process_id'] = $value_process['flow_run_process_id'];
                // 统计过的可收回节点做个标识
                $flowBranchProcessArrToTakeBack[$value_process['flow_serial'] . '_' . $value_process['branch_serial'] . '_' . $value_process['process_serial']] = 1;
            }
        }

        return ['canTakeBack' => $canTakeBack, 'processInfoToTakeBack' => $processInfoToTakeBack];
    }

    /**
     * 获取流程列表展示时使用的最大步骤的process_id，先判断flow_serial，再区分判断process_id
     *
     * @param [array] $flowRunProcessList
     * @param [int] $flowType
     *
     * @author zyx
     */
    public function getMaxProcessIdBasedOnFlowRunProcessList($flowRunProcessList, $flowType, $maxFlowSerial) {
        $maxProcessId = 0;

        if ($flowType == 1) { // 固定流程，在max_flow_serial中找到最大的process_id
            foreach ($flowRunProcessList as $key => $flowRunProcessInfo) {
                if ($flowRunProcessInfo['flow_serial'] != $maxFlowSerial) {
                    unset($flowRunProcessList[$key]);
                }
            }

            $flowRunProcessList = array_values($flowRunProcessList);
        }

        // 固定流程，在max_flow_serial中找到最大的process_id
        // 自由流程直接返回最大的步骤序号
        $maxProcessId = collect($flowRunProcessList)->max('process_id');

        return $maxProcessId;
    }
}
