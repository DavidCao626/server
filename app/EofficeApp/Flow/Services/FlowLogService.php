<?php
namespace App\EofficeApp\Flow\Services;
use Eoffice;
use DB;
use Schema;
use Queue;
use App\Utils\Utils;
use App\EofficeApp\Base\BaseService;
use App\Jobs\FlowToIntegrationCenterWebhookSendJob;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\LogCenter\Facades\LogCenter;
/**
 * 流程日志service类
 * 用来处理流程日志相关的东西
 *
 * @author 丁鹏
 *
 * @since  2018-9-21 创建
 */
class FlowLogService extends FlowBaseService
{

    public function __construct(
    ) {
        parent::__construct();
    }

    /**
     * 过滤待保存的数组，根据页面不同，配置不同的过滤规则，达到只保存指定字段的效果
     * @param  [type] $updateData [description]
     * @param  [type] $routFrom   [$routFrom 可选值 editOtherBase editOtherFiling editOtherPrint editOtherRemind editOtherRequired editOtherData]
     * @param  [type] $flowId     [description]
     * @param  [type] $own        [description]
     * @return [type]             [description]
     */
    function flowOthersUpdateParamsFilter($updateData, $routFrom, $batchFlag, $workFor="") {
        if(!$routFrom) {
            return $updateData;
        }
        // 配置分发处理函数
        $functionName = "filterFlowOthers".ucfirst($routFrom);
        if($batchFlag == "batchFlow") {
            $functionName .= "Batch";
        }
        // 日志调用过滤的时候，会传这个参数
        if($workFor) {
            $functionName .= ucfirst($workFor);
        }
        // 保存/批量设置保存 的时候，筛选保存字段
        // $functionName 可选值
        // filterFlowOthersEditOtherBase; 流程设置-基本设置; --ok
        // filterFlowOthersEditOtherFiling; 流程设置-归档设置;
        // filterFlowOthersEditOtherPrint; 流程设置-打印模板设置;--ok
        // filterFlowOthersEditOtherRemind; 流程设置-流程结束提醒对象设置;
        // filterFlowOthersEditOtherData; 流程设置-表单数据模板设置;--ok
        // $functionName + Batch
        // filterFlowOthersEditOtherBaseBatch; 流程批量设置-基本设置; --ok
        // filterFlowOthersEditOtherFilingBatch; 流程批量设置-归档设置; --ok
        // filterFlowOthersEditOtherRemindBatch; 流程批量设置-流程结束提醒对象设置; --ok
        // 日志保存的时候，过滤可以进日志的字段
        // filterFlowOthersEditOtherFilingForLog; 流程设置-归档设置;  --ok
        // filterFlowOthersEditOtherFilingBatchForLog; 批量流程设置-归档设置;  --ok
        // filterFlowOthersEditOtherPrintForLog; 流程设置-打印模板设置;  --ok
        // filterFlowOthersEditOtherPrintBatchForLog; 批量流程设置-打印模板设置;  --ok
        // filterFlowOthersEditOtherRemindForLog; 流程设置-提醒设置设置;  --ok
        // filterFlowOthersEditOtherRemindBatchForLog; 批量流程设置-提醒设置设置;  --ok
        // filterFlowOthersBasicInfoForLog; basicInfo(定义流程-基本信息) --ok
        // filterFlowOthersBasicInfoBatchForLog; basicInfo(批量定义流程-基本信息) --ok
        // filterFlowOthersEditNodeBaseForLog; 定义流程节点-基本信息;editNodeBase; 过滤进日志的字段;--ok
        // filterFlowOthersEditNodeBaseBatchForLog; 批量定义流程节点-基本信息;editNodeBase; 过滤进日志的字段;--ok
        // filterFlowOthersEditNodeTemplateForLog; 定义流程节点-节点表单模板;editNodeTemplate; 过滤进日志的字段;--ok
        // filterFlowOthersEditNodeTemplateBatchForLog; 批量定义流程节点-节点表单模板;editNodeTemplate; 过滤进日志的字段;--ok
        // filterFlowOthersMonitorForLog; 流程设置-监控人员设置--ok

        if(method_exists($this, $functionName)) {
            return $this->{$functionName}($updateData);
        } else {
            return $updateData;
        }
    }

    // 白名单函数，以传入的 filter 为主，返回对比结果
    function whiteListContrast($data,$filter) {
        $result = [];
        if(!empty($filter)) {
            foreach ($filter as $key => $field) {
                if(isset($data[$field])) {
                    $result[$field] = $data[$field];
                }
            }
        }
        return $result;
    }
    // 黑名单函数，将传入的 filter 的 key 删除，返回对比结果
    function blackListContrast($data,$filter) {
        if(!empty($filter)) {
            foreach ($filter as $key => $field) {
                unset($data[$field]);
            }
        }
        return $data;
    }
    // 流程设置-基本设置 保存参数过滤
    function filterFlowOthersEditOtherBase($param) {
        $filter = ["flow_autosave", "flow_to_doc", "flow_show_name", "feed_back_after_flow_end", "submit_without_dialog", "first_node_delete_flow", "flow_send_back_required", "flow_send_back_submit_method", "alow_select_handle", "flow_submit_hand_remind_toggle", "continuous_submission", "flow_show_history", "flow_detail_page_choice_other_tabs","without_back","without_required","form_control_filter","flow_submit_hand_overtime_toggle" ,"forward_after_flow_end", "trigger_schedule",'flow_send_back_verify_condition'];
        return $this->whiteListContrast($param,$filter);
    }
    // 流程设置-打印模板设置 保存参数过滤
    function filterFlowOthersEditOtherPrint($param) {
        $filter = ["flow_print_template_toggle", "flow_print_times_limit", "no_print_until_flow_end", "flow_print_template_mode"];
        return $this->whiteListContrast($param,$filter);
    }
    // 流程批量设置-打印模板设置 保存参数过滤
    function filterFlowOthersEditOtherPrintBatch($param) {
        $filter = ["flow_print_times_limit", "no_print_until_flow_end"];
        return $this->whiteListContrast($param,$filter);
    }
    // 流程设置-表单数据模板设置 保存参数过滤
    function filterFlowOthersEditOtherData($param) {
        // 排除 flow_filing_folder_rules
        // $filter = ["flow_filing_folder_rules"];
        // $param = $this->blackListContrast($param,$filter);
        $filter = ["flow_show_data_template","flow_show_user_template"];
        $param = $this->whiteListContrast($param,$filter);
        return $param;
    }
    // 流程批量设置-基本设置 保存参数过滤
    function filterFlowOthersEditOtherBaseBatch($param) {
        $filter = ["flow_autosave","feed_back_after_flow_end","submit_without_dialog","first_node_delete_flow","flow_send_back_required","flow_send_back_submit_method","alow_select_handle","flow_submit_hand_remind_toggle","continuous_submission","flow_show_history","flow_detail_page_choice_other_tabs","without_back","without_required","form_control_filter","flow_submit_hand_overtime_toggle" ,"forward_after_flow_end",'flow_send_back_verify_condition'];
        return $this->whiteListContrast($param,$filter);
    }
    // 流程批量设置-归档设置 保存参数过滤
    function filterFlowOthersEditOtherFilingBatch($param) {
        $filter = ["file_folder_id","flow_filing_document_create","flow_filing_set","flow_to_doc","flow_to_doc_rule","flow_filing_folder_rules_toggle"];
        return $this->whiteListContrast($param,$filter);
    }
    // 流程批量设置-流程结束提醒对象设置 保存参数过滤
    function filterFlowOthersEditOtherRemindBatch($param) {
        $filter = ["appoint_process","flow_end_remind","remind_target"];
        return $this->whiteListContrast($param,$filter);
    }
    // 流程设置-归档设置; 过滤进日志的字段;
    function filterFlowOthersEditOtherFilingForLog($param) {
        // history_rule_list 是特殊的，用来处理自定义归档模板的变更
        $filter = ["flow_to_doc","flow_filing_set","flow_to_doc_rule","file_folder_id","flow_filing_folder_rules_toggle","flow_filing_conditions_setting_toggle","flow_filing_template_toggle","history_rule_list","flow_filing_conditions_setting_value","flow_filing_folder_rules","flow_filing_conditions_verify_mode","flow_filing_conditions_verify_url"];
        return $this->whiteListContrast($param,$filter);
    }
    // 批量流程设置-归档设置; 过滤进日志的字段;
    function filterFlowOthersEditOtherFilingBatchForLog($param) {
        $param = $this->filterFlowOthersEditOtherFilingForLog($param);
        return $param;
    }
    // 流程设置-打印模板设置; 过滤进日志的字段;
    function filterFlowOthersEditOtherPrintForLog($param) {
        $filter = ["flow_print_template_toggle","history_rule_list", 'no_print_until_flow_end', 'flow_print_times_limit', "flow_print_template_mode"];
        return $this->whiteListContrast($param,$filter);
    }
    // 批量流程设置-打印模板设置; 过滤进日志的字段;
    function filterFlowOthersEditOtherPrintBatchForLog($param) {
        $param = $this->filterFlowOthersEditOtherPrintForLog($param);
        return $param;
    }
    // 流程设置-提醒设置设置; 过滤进日志的字段;
    function filterFlowOthersEditOtherRemindForLog($param) {
        $filter = ["flow_end_remind","remind_target","appoint_process"];
        return $this->whiteListContrast($param,$filter);
    }
    // 批量流程设置-提醒设置设置; 过滤进日志的字段;
    function filterFlowOthersEditOtherRemindBatchForLog($param) {
        $param = $this->filterFlowOthersEditOtherRemindForLog($param);
        return $param;
    }
    // 定义流程-基本信息;basicInfo; 过滤进日志的字段;
    function filterFlowOthersBasicInfoForLog($param) {
        // 排除
        $filter = ["flow_name_py","flow_name_zm","flow_type_belongs_to_flow_sort","flow_name_rules_html","flow_type_has_one_flow_form_type","flow_type_has_one_flow_others","flow_type_has_many_flow_process"];
        $param = $this->blackListContrast($param,$filter);
        return $param;
        // $filter = ["flow_name","flow_sort","flow_type","handle_way","user_id","role_id","dept_id","countersign","form_id","form_name","flow_noorder","is_using","hide_running","flowPressTimeSetDay","flowPressTimeSetHour","flow_sequence","can_edit_flowno","can_edit_flowname"];
        // return $this->whiteListContrast($param,$filter);
    }
    // 批量定义流程-基本信息;basicInfo; 过滤进日志的字段;
    function filterFlowOthersBasicInfoBatchForLog($param) {
        $param = $this->filterFlowOthersBasicInfoForLog($param);
        return $param;
    }
    // 定义流程节点-基本信息;editNodeBase; 过滤进日志的字段;
    function filterFlowOthersEditNodeBaseForLog($param) {
        // 排除
        $filter = ["flowPressTimeSetDay","flowPressTimeSetHour"];
        $param = $this->blackListContrast($param,$filter);
        return $param;
    }
    // 批量定义流程节点-基本信息;editNodeBase; 过滤进日志的字段;
    function filterFlowOthersEditNodeBaseBatchForLog($param) {
        $param = $this->filterFlowOthersEditNodeBaseForLog($param);
        return $param;
    }
    // 定义流程节点-节点表单模板;editNodeTemplate; 过滤进日志的字段;
    function filterFlowOthersEditNodeTemplateForLog($param) {
        // 排除
        $filter = ["press_add_hour"];
        $param = $this->blackListContrast($param,$filter);
        $filter = ["flow_run_template_toggle","history_rule_list"];
        $param = $this->whiteListContrast($param,$filter);
        return $param;
    }
    // 批量定义流程节点-节点表单模板;editNodeTemplate; 过滤进日志的字段;
    function filterFlowOthersEditNodeTemplateBatchForLog($param) {
        $param = $this->filterFlowOthersEditNodeTemplateForLog($param);
        return $param;
    }
    // 流程设置-监控人员设置;
    function filterFlowOthersMonitorForLog($param) {
        // 排除
        $filter = ["flow_type_has_many_manage_scope_user","flow_type_has_many_manage_scope_dept","flow_type_has_many_manage_user","flow_type_has_many_manage_role"];
        $param = $this->blackListContrast($param,$filter);
        return $param;
    }

    /**
     * 处理历史数据/新数据的参数默认值
     * @param  [type] $param      [待处理的数组]
     * @param  [type] $id         [nodeid/flow_id]
     * @param  [type] $routFrom   [$routFrom 可选值 editOtherBase editOtherFiling editOtherPrint editOtherRemind editOtherRequired editOtherData]
     * @param  [type] $saveType   [保存函数执行的操作类型，可选值 node/flow/batchFlow/batchNode/null]
     * @param  [type] $verifyType [调用此验证函数的待处理数组是什么类型的数据 history/new]
     * @return [type]             [description]
     */
    function flowOthersParamsDefaultValueTransact($param, $id, $routFrom, $saveType, $verifyType) {
        if(!$routFrom) {
            return $param;
        }
        // 配置分发处理函数
        $functionName = "transactDefault".ucfirst($routFrom);
        if($saveType == "batchFlow") {
            $functionName .= "BatchFlow";
        } else if($saveType == "batchNode") {
            $functionName .= "BatchNode";
        }
        $functionName .= ucfirst($verifyType);
        // echo $functionName;exit();
        // $functionName 可选值
        // transactDefaultEditOtherBase; 流程设置-基本设置;--
        // transactDefaultEditOtherFiling; 流程设置-归档设置;--ok
        // transactDefaultEditOtherPrint; 流程设置-打印模板设置;--ok
        // transactDefaultEditOtherRemind; 流程设置-流程结束提醒对象设置;
        // transactDefaultEditOtherData; 流程设置-表单数据模板设置;--
        // $functionName + History/New
        // transactDefaultEditOtherFilingHistory; 流程设置-归档设置;--ok
        // transactDefaultEditOtherFilingNew; 流程设置-归档设置;--ok
        // transactDefaultEditOtherPrintNew; 流程设置-打印设置(打印模板);--ok
        // transactDefaultEditOtherDataNew; 流程设置-表单数据模板设置;--
        // $functionName + BatchFlow
        // transactDefaultEditOtherBaseBatchFlow; 流程批量设置-基本设置;--
        // transactDefaultEditOtherFilingBatchFlow; 流程批量设置-归档设置;
        // transactDefaultEditOtherRemindBatchFlow; 流程批量设置-流程结束提醒对象设置;
        // 流程节点编辑相关
        // transactDefaultEditNodeBaseNew; 流程节点设置-基本信息--ok
        // transactDefaultEditNodeHandleNew; 流程节点设置-办理人员--ok
        // transactDefaultEditNodeHandleHistory; 流程节点设置-办理人员--ok
        // transactDefaultEditNodeCopyNew; 流程节点设置-抄送人员--ok
        // transactDefaultEditNodeCopyHistory; 流程节点设置-抄送人员--ok
        // transactDefaultEditNodeTemplateNew; 流程节点设置-节点表单模板(editNodeTemplate);--ok
        // transactDefaultEditNodeTemplateBatchNodeHistory; 流程批量节点设置-节点表单模板(editNodeTemplate);--ok
        // transactDefaultEditNodeTemplateBatchNodeNew; 流程批量节点设置-节点表单模板(editNodeTemplate);--ok
        // transactDefaultEditNodeOutsendHistory; 流程节点设置-数据外发(EditNodeOutsend);--ok
        // transactDefaultEditNodeOutsendNew; 流程节点设置-数据外发(EditNodeOutsend);--ok
        // transactDefaultEditNodeValidateHistory; 流程节点设置-数据验证(editNodeValidate);--ok
        // transactDefaulteditNodeValidateNew; 流程节点设置-数据验证(editNodeValidate);--ok
        // transactDefaultEditNodeValidateBatchNodeHistory; 流程节点设置-批量设置-数据验证(editNodeValidate);--ok
        // transactDefaulteditNodeValidateBatchNodeNew; 流程节点设置-批量设置-数据验证(editNodeValidate);--ok
        if(method_exists($this, $functionName)) {
            return $this->{$functionName}($param,$id);
        } else {
            return $param;
        }
    }

    // 流程设置-基本设置 默认值处理
    function transactDefaultEditOtherBase($param,$id) {
        // $param["continuous_submission"]   = ( isset($param["continuous_submission"]) && $param["continuous_submission"] ) ? $param["continuous_submission"] : "1";
        // $param["lable_show_default"]   = ( isset($param["lable_show_default"]) && $param["lable_show_default"] ) ? $param["lable_show_default"] : "1";
        // $param["inheritance_sign"]        = ( isset($param["inheritance_sign"]) && $param["inheritance_sign"] ) ? $param["inheritance_sign"] : "2";
        // $param["flow_show_data_template"] = ( isset($param["flow_show_data_template"]) && $param["flow_show_data_template"] ) ? $param["flow_show_data_template"] : "0";
        return $param;
    }

    // 流程批量设置-基本设置 默认值处理
    function transactDefaultEditOtherBaseBatchFlow($param,$id) {
        // $param = $this->transactDefaultEditOtherBase($param);
        // $param["flow_detail_page_choice_other_tabs"] = ( isset($param["flow_detail_page_choice_other_tabs"]) && $param["flow_detail_page_choice_other_tabs"] ) ? $param["flow_detail_page_choice_other_tabs"] : "feedback,attachment,document,map,step,sunflow";
        // $param["flow_show_history"] = ( isset($param["flow_show_history"]) && $param["flow_show_history"] ) ? $param["flow_show_history"] : "1";
        // $param["alow_select_handle"] = ( isset($param["alow_select_handle"]) && $param["alow_select_handle"] ) ? $param["alow_select_handle"] : "1";
        // $param["flow_end_remind"] = ( isset($param["flow_end_remind"]) && $param["flow_end_remind"] ) ? $param["flow_end_remind"] : "3";
        // $param["remind_target"] = ( isset($param["remind_target"]) && $param["remind_target"] ) ? $param["remind_target"] : "3";
        // $param["inheritance_sign"] = ( isset($param["inheritance_sign"]) && $param["inheritance_sign"] ) ? $param["inheritance_sign"] : "2";
        return $param;
    }

    // 流程设置-归档设置;--base
    function transactDefaultEditOtherFiling($param,$id) {
        $param["flow_filing_folder_rules"] = isset($param["flow_filing_folder_rules"]) ? json_decode($param["flow_filing_folder_rules"]) : "";
        return $param;
    }
    // 流程设置-归档设置;--history
    function transactDefaultEditOtherFilingHistory($param,$id) {
        $param = $this->transactDefaultEditOtherFiling($param,$id);
        return $param;
    }
    // 流程设置-归档设置;--new
    function transactDefaultEditOtherFilingNew($param,$id) {
        $param = $this->transactDefaultEditOtherFiling($param,$id);
        // 获取当前流程的最新归档模板list 存为 history_rule_list ，用来和前端传递的历史值比较
        if(isset($param["flow_filing_template_toggle"]) && $param["flow_filing_template_toggle"] == "1" && $id) {
            $ruleListParam = ["template_type" => "filing" ,"flow_id" => $id];
            $ruleList = app($this->flowService)->getFlowTemplateRuleList($ruleListParam);
            $param["history_rule_list"] = $ruleList->toArray();
        }
        return $param;
    }
    // 流程设置-打印设置(打印模板);--new
    function transactDefaultEditOtherPrintNew($param,$id) {
        // 获取当前流程的最新打印模板list 存为 history_rule_list ，用来和前端传递的历史值比较
        if(isset($param["flow_print_template_toggle"]) && $param["flow_print_template_toggle"] == "1" && $id) {
            $ruleListParam = ["template_type" => "print" ,"flow_id" => $id];
            $ruleList = app($this->flowService)->getFlowTemplateRuleList($ruleListParam);
            $param["history_rule_list"] = $ruleList->toArray();
        }
        return $param;
    }
    // 流程设置-打印模板设置;
    function transactDefaultEditOtherPrint($param,$id) {
        $param["flow_filing_folder_rules"] = isset($param["flow_filing_folder_rules"]) ? json_decode($param["flow_filing_folder_rules"]) : "";
        return $param;
    }
    // 流程设置-表单数据模板设置;
    function transactDefaultEditOtherData($param,$id) {
        return $param;
    }
    // 流程设置-表单数据模板设置;
    function transactDefaultEditOtherDataNew($param,$id) {
        return $param;
    }
    // 流程节点设置-基本信息
    function transactDefaultEditNodeBaseNew($param,$id) {
        $param["press_add_hour"] = isset($param["press_add_hour"]) ? (int)$param["press_add_hour"] : "0";
        return $param;
    }
    // 流程节点设置-办理人员
    function transactDefaultEditNodeHandleNew($param,$id) {
        $param["process_user"]           = isset($param["process_user"]) ? $param["process_user"] : [];
        $param["process_role"]           = isset($param["process_role"]) ? $param["process_role"] : [];
        $param["process_dept"]           = isset($param["process_dept"]) ? $param["process_dept"] : [];
        $param["process_default_user"]   = (isset($param["process_default_user"]) && $param["process_default_user"]) ? $param["process_default_user"] : [];
        $param["process_default_manage"] = (isset($param["process_default_manage"]) && $param["process_default_manage"]) ? $param["process_default_manage"] : [];
        $param["process_default_type"]   = isset($param["process_default_type"]) ? $param["process_default_type"] : "";
        $param["process_auto_get_user"]  = isset($param["process_auto_get_user"]) ? $param["process_auto_get_user"] : "";
        $param["get_agency"]             = isset($param["get_agency"]) ? $param["get_agency"] : "";
        return $param;
    }
    // 流程节点设置-办理人员-历史数据
    function transactDefaultEditNodeHandleHistory($param,$id) {
        $handle_user_id = isset($param["handle_user_id"]) ? $param["handle_user_id"] : "";
        $handle_user_id = json_decode(json_encode($handle_user_id),true);
        $handle_role_id = isset($param["handle_role_id"]) ? $param["handle_role_id"] : "";
        $handle_role_id = json_decode(json_encode($handle_role_id),true);
        $handle_dept_id = isset($param["handle_dept_id"]) ? $param["handle_dept_id"] : "";
        $handle_dept_id = json_decode(json_encode($handle_dept_id),true);
        $default_user_id = (isset($param["default_user_id"]) && $param["default_user_id"]) ? $param["default_user_id"] : [];
        $default_user_id = json_decode(json_encode($default_user_id),true);
        $param["process_user"] = (isset($param["process_user"]) && $param["process_user"] == "ALL") ? "ALL" : $handle_user_id;
        $param["process_role"] = (isset($param["process_role"]) && $param["process_role"] == "ALL") ? "ALL" : $handle_role_id;
        $param["process_dept"] = (isset($param["process_dept"]) && $param["process_dept"] == "ALL") ? "ALL" : $handle_dept_id;
        $param["process_default_user"]  = (isset($param["process_default_user"]) && $param["process_default_user"] == "ALL") ? "ALL" : $default_user_id;
        // $param["process_default_user"]   = (isset($param["process_default_user"]) && $param["process_default_user"]) ? $param["process_default_user"] : [];
        $param["process_default_manage"] = (isset($param["process_default_manage"]) && $param["process_default_manage"]) ? $param["process_default_manage"] : [];
        $param["process_default_type"]   = isset($param["process_default_type"]) ? $param["process_default_type"] : "";
        $param["process_auto_get_user"] = isset($param["process_auto_get_user"]) ? $param["process_auto_get_user"] : "0";
        $param["get_agency"]            = isset($param["get_agency"]) ? $param["get_agency"] : "";
        return $param;
    }
    // 流程节点设置-抄送人员
    function transactDefaultEditNodeCopyNew($param,$id) {
        $param["process_copy_user"] = isset($param["process_copy_user"]) ? $param["process_copy_user"] : [];
        $param["process_copy_role"] = isset($param["process_copy_role"]) ? $param["process_copy_role"] : [];
        $param["process_copy_dept"] = isset($param["process_copy_dept"]) ? $param["process_copy_dept"] : [];
        $param["process_auto_get_copy_user"] = (isset($param["process_auto_get_copy_user"]) && $param["process_auto_get_copy_user"]) ? $param["process_auto_get_copy_user"] : [];
        $param["get_agency"] = isset($param["get_agency"]) ? $param["get_agency"] : "";
        return $param;
    }
    // 流程节点设置-抄送人员-历史数据
    function transactDefaultEditNodeCopyHistory($param,$id) {
        $copy_user_id = isset($param["copy_user_id"]) ? $param["copy_user_id"] : "";
        $copy_user_id = json_decode(json_encode($copy_user_id),true);
        $copy_role_id = isset($param["copy_role_id"]) ? $param["copy_role_id"] : "";
        $copy_role_id = json_decode(json_encode($copy_role_id),true);
        $copy_dept_id = isset($param["copy_dept_id"]) ? $param["copy_dept_id"] : "";
        $copy_dept_id = json_decode(json_encode($copy_dept_id),true);
        $param["process_copy_user"] = (isset($param["process_copy_user"]) && $param["process_copy_user"] == "ALL") ? "ALL" : $copy_user_id;
        $param["process_copy_role"] = (isset($param["process_copy_role"]) && $param["process_copy_role"] == "ALL") ? "ALL" : $copy_role_id;
        $param["process_copy_dept"] = (isset($param["process_copy_dept"]) && $param["process_copy_dept"] == "ALL") ? "ALL" : $copy_dept_id;
        $param["process_auto_get_copy_user"] = (isset($param["process_auto_get_copy_user"]) && $param["process_auto_get_copy_user"]) ? $param["process_auto_get_copy_user"] : [];
        $param["get_agency"] = isset($param["get_agency"]) ? $param["get_agency"] : "";
        return $param;
    }
    // 流程节点设置-节点表单模板(editNodeTemplate);--new
    function transactDefaultEditNodeTemplateNew($param,$id) {
        // 获取当前流程的最新打印模板list 存为 history_rule_list ，用来和前端传递的历史值比较
        if(isset($param["flow_run_template_toggle"]) && $param["flow_run_template_toggle"] == "1" && $id) {
            $ruleListParam = ["template_type" => "run" ,"node_id" => $id];
            $ruleList = app($this->flowService)->getFlowTemplateRuleList($ruleListParam);
            $param["history_rule_list"] = $ruleList->toArray();
        }
        return $param;
    }
    // 流程批量节点设置-节点表单模板(editNodeTemplate);--ok
    function transactDefaultEditNodeTemplateBatchNodeHistory($param,$id) {
        $historyRuleList = [];
        $batchHistoryRuleList = isset($param["batch_history_rule_list"]) ? $param["batch_history_rule_list"] : [];
        if(!empty($batchHistoryRuleList)) {
            foreach ($batchHistoryRuleList as $key => $value) {
                if($value["node_id"] == $id) {
                    $historyRuleList[] = $value;
                }
            }
        }
        unset($param["batch_history_rule_list"]);
        $param["history_rule_list"] = $historyRuleList;
        return $param;
    }
    // 流程批量节点设置-节点表单模板(editNodeTemplate);--ok
    function transactDefaultEditNodeTemplateBatchNodeNew($param,$id) {
        $param = $this->transactDefaultEditNodeTemplateNew($param,$id);
        return $param;
    }
    // 流程节点设置-数据外发;--ok
    function transactDefaultEditNodeOutsendHistory($param,$id) {
        if(isset($param["flow_outsend_toggle"]) && $param["flow_outsend_toggle"] == "1") {
            // 去掉outsend里面的id属性
            $outsend = isset($param["outsend"]) ? $param["outsend"] : [];
            if(!empty($outsend)) {
                $outsendNew = [];
                foreach ($outsend as $key => $value) {
                    unset($value["id"]);
                    unset($value["outsend_has_many_fields"]);
                    $outsendNew[$key] = $value;
                }
                $param["outsend"] = $outsendNew;
            }
        }
        return $param;
    }
    // 流程节点设置-数据外发;--ok
    function transactDefaultEditNodeOutsendNew($param,$id) {
        $param = $this->transactDefaultEditNodeOutsendHistory($param,$id);
        return $param;
    }
    // 流程节点设置-数据验证;--ok
    function transactDefaultEditNodeValidateHistory($param,$id) {
        $param = $this->transactDefaultEditNodeValidateBatchNodeHistory($param,$id);
        return $param;
    }
    // 流程节点设置-数据验证;--ok
    function transactDefaulteditNodeValidateNew($param,$id) {
        $param = $this->transactDefaultEditNodeValidateBatchNodeHistory($param,$id);
        return $param;
    }
    // 流程节点设置-批量设置-数据验证;--ok
    function transactDefaultEditNodeValidateBatchNodeHistory($param,$id) {
        if(isset($param["flow_data_valid_toggle"]) && $param["flow_data_valid_toggle"] == "1") {
            // 去掉validate里面的id属性
            $validate = isset($param["validate"]) ? $param["validate"] : [];
            if(!empty($validate)) {
                $validateNew = [];
                foreach ($validate as $key => $value) {
                    unset($value["id"]);
                    $validateNew[$key] = $value;
                }
                $param["validate"] = $validateNew;
            }
        }
        return $param;
    }
    // 流程节点设置-批量设置-数据验证;--ok
    function transactDefaulteditNodeValidateBatchNodeNew($param,$id) {
        $param = $this->transactDefaultEditNodeValidateBatchNodeHistory($param,$id);
        return $param;
    }

    /**
     * 保存定义流程修改的日志
     * @param  [type] $param         [description]
     * @param  [type] $tableAndField ['&'分隔表名和表字段，用于 log_relation_table&log_relation_field] [$log_relation_field 可选值 flow_id/node_id 这个值跟 $id 绑定]
     * @param  [type] $id            [description]
     * @param  [type] $routFrom      页面来源
     * 1 [流程设置 可选值 editOtherBase editOtherFiling editOtherPrint editOtherRemind editOtherRequired editOtherData]
     * 2 [流程基本信息 可选值 basicInfo(定义流程-基本信息) monitor(定义流程-监控设置) ]
     * 3 [流程节点设置 可选值 editNodeBase (节点信息设置) editNodeBaseProcessSort (节点排序) editNodeBaseProcessTo (节点出口设置) editNodeBaseFlowMapNodeInfoUpdate (流程节点设置-流程图节点信息更新) editNodeBaseFlowMapDeleteNodeLink (流程节点设置-流程图删除节点连线) editNodeBaseFlowMapDeleteAllNodeLink (流程节点设置-流程图删除[所有]节点连线) editNodeBaseAddFlowProcess (流程节点设置-新建节点) editNodeBaseDeleteFlowProcess (流程节点设置-删除节点) editNodeBaseEditNodeCondition (流程节点设置-编辑出口条件) editNodeBaseUnifiedSetPresstime (节点信息设置-统一设置催促时间) editNodeTemplate (节点表单模板设置) editNodeField (字段控制设置) editNodeCopy (抄送人员设置) editNodeHandle (办理人员设置) ...]
     * @param  [type] $own           [description]
     * @param  [type] $saveType      [保存函数执行的操作类型，可选值 node/flow/batchFlow/batchNode/null]
     * @return [type]                [description]
     */
    function logFlowDefinedModify($param,$tableAndField,$id,$routFrom,$own,$saveType="") {
        // 日志主体
        $logContent = [];
        // 历史数据，用来记录日志
        $historyInfo = isset($param["history_info"]) ? $param["history_info"] : [];
        // 处理历史数据默认值
        $historyInfo = $this->flowOthersParamsDefaultValueTransact($historyInfo, $id, $routFrom, $saveType, "history");
        // 新数据
        $newInfo = isset($param["new_info"]) ? $param["new_info"] : [];
        // 处理new数据默认值
        $newInfo = $this->flowOthersParamsDefaultValueTransact($newInfo, $id, $routFrom, $saveType, "new");
        // 过滤日志用的[新值]数组，根据页面不同，日志只保存特定的值
        $newInfo = $this->flowOthersUpdateParamsFilter($newInfo, $routFrom, $id, "forLog");
        // 关联表 关联表字段
        $tableAndFieldInfo = explode("&", $tableAndField);
        $logRelationTable = $tableAndFieldInfo[0];
        $logRelationField = $tableAndFieldInfo[1];
        // own 解析出user_id
        if(isset($own) && !empty($own)) {
            $userId = isset($own["user_id"]) ? $own["user_id"] : "";
        } else {
            $userId = isset($param["log_creator"]) ? $param["log_creator"] : "";
        }
        // 来源，批量设置/单个设置
        if($saveType == "batchFlow") {
            $logFrom = trans("flow.flow_batch_set"); // 流程批量设置
        } else if($saveType == "batchNode") {
            $logFrom = trans("flow.node_batch_set"); // 节点批量设置
        } else if($saveType == "node") {
            $logFrom = trans("flow.node_set"); // 节点设置
        } else if($saveType == "flow") {
            $logFrom = trans("flow.flow_set"); // 流程设置
        } else {
            $logFrom = trans("flow.flow_set"); // 流程设置
        }
        // 日志头部信息-流程名称/节点名称
        $logHeadTitle = "";
        $formId = "";
        if($logRelationField == "flow_id") {
            $flowName = app($this->flowTypeRepository)->findFlowType($id);
        } else if($logRelationField == "node_id") {
            $nodeInfo = app($this->flowProcessRepository)->findFlowNode($id,["flow_id","process_name"]);
            $flowId = isset($nodeInfo["flow_id"]) ? $nodeInfo["flow_id"] : "";
            if($flowId) {
                $flowInfo = app($this->flowTypeRepository)->findFlowType($flowId,["flow_name","form_id"]);
                $flowName = isset($flowInfo["flow_name"]) ? $flowInfo["flow_name"] : "";
                $formId = isset($flowInfo["form_id"]) ? $flowInfo["form_id"] : "";
            }
            $nodeName = isset($nodeInfo["process_name"]) ? $nodeInfo["process_name"] : "";
        }
        $logContentTitle = $logFrom."；";
        $relation_title = '';
        if(isset($flowName) && $flowName) {
            $logContentTitle .= trans("flow.0x030073")."：".$flowName."；"; // 流程名称：xxx
            $relation_title = $flowName;
        }
        if(isset($nodeName) && $nodeName) {
            $logContentTitle .= trans("flow.flow_node_name")."：".$nodeName."；"; // 节点名称：xxx
            $relation_title .= "【".$nodeName."】";
        }
        // title operation data
        $logContent["title"] = $logContentTitle;
        // 拼接操作类型 operation (即具体是什么操作，前端是什么页面等)
        $operation = "";
        if($logRelationTable == "flow_others") {
            $operation = "modifyFlowOtherInfo-".$routFrom;
        } else if($logRelationTable == "flow_type") {
            $operation = "modifyFlowDefineBasicInfo-".$routFrom;
        } else if($logRelationTable == "flow_process") {
            $operation = "modifyFlowNode-".$routFrom;
        } else if($logRelationTable == "flow_process_control_operation") {
            $operation = "modifyFlowNode-".$routFrom;
        }
        $logContent["operation_flag"] = $operation; // 日志来源标识
        $operation = $this->transformFlowDefinedLogOperation($operation);
        $logContent["operation"] = $operation ? trans("flow.logging_sources")."：".$operation."；" : ""; // 日志来源：xxx
        // 差异数据对比 $historyInfo - $newInfo
        $logContentData = $this->intersectContrastLogData($historyInfo,$newInfo);
        // echo "<pre>";
        // print_r($historyInfo["history_rule_list"]);
        // print_r($newInfo["history_rule_list"]);
        // echo "<pre>";
        // print_r($logContentData);
        // exit();
        // 字段控制的日志，记录一下表单控件，用于前端展示差异
        // if($logContent["operation_flag"] == "modifyFlowNode-editNodeField" && $formId) {
        if($logContent["operation_flag"] == "modifyFlowNode-editNodeField" || $logContent["operation_flag"] == "modifyFlowNode-") {
            $formControlFormat = [];
            if($formId) {
                $formControl = app($this->flowService)->getParseForm($formId,[]);
                if(!empty($formControl)) {
                    foreach ($formControl as $controlKey => $controlValue) {
                        $controlId = isset($controlValue["control_id"]) ? $controlValue["control_id"] : "";
                        $controlTitle = isset($controlValue["control_title"]) ? $controlValue["control_title"] : "";
                        $formControlFormat[$controlId] = ["control_id" => $controlId,"control_title" => $controlTitle];
                    }
                }
            }
            // "0x030095" => "相关附件",
            // "0x030096" => "签办反馈",
            // "0x030097" => "相关文档",
            $formControlFormat["attachment"] = ["control_title" => trans("flow.0x030095")];
            $formControlFormat["feedback"]   = ["control_title" => trans("flow.0x030096")];
            $formControlFormat["document"]   = ["control_title" => trans("flow.0x030097")];
            // 放到日志的content里面
            $logContent["form_control"] = $formControlFormat;
        }
        $logContent["data"] = $logContentData;
        // 有差异，再生成日志
        if(!empty($logContentData)) {
            // 生成日志
            $this->addSystemLog($userId,json_encode($logContent),"definedFlow",$logRelationTable,$id,$logRelationField , 0 , '' , [] , $relation_title);
        }
        return "";
    }

    /**
     * 对比历史数据和新数据，格式化结果用作日志主体
     * @param [type] $history [description]
     * @param [type] $new     [description]
     */
    function intersectContrastLogData($history,$new){
        $result = [];
        if(!empty($new)) {
            foreach ($new as $newField => $newValue) {
                if($newField !== "created_at" && $newField !== "updated_at" && $newField !== "deleted_at") {
                    $resultItem = [];
                    $newValueDefault = "";
                    if(is_numeric($newValue)) {
                        $newValueDefault = "0";
                    }
                    if(is_array($newValue)) {
                        $newValueDefault = [];
                    }
                    // 新值的key，不在旧值里面，收集
                    if(!isset($history[$newField])) {
                        if(!$this->contrastItemLogData($newValueDefault,$newValue)) {
                            $resultItem["history"] = $newValueDefault;
                            $resultItem["new"] = $newValue;
                            $resultItem["field"] = $newField;
                            $resultItem["field_name"] = $newField;
                        }
                    } else {
                        $historyValue = $history[$newField];
                        $contrastResult = $this->contrastItemLogData($historyValue,$newValue);
                        if(!$contrastResult) {
                            // 判断不相等，收集
                            $resultItem["history"] = $historyValue;
                            $resultItem["new"] = $newValue;
                            $resultItem["field"] = $newField;
                            $resultItem["field_name"] = $newField;
                        }
                    }
                    if(!empty($resultItem)) {
                        $result[] = $resultItem;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 对比单个的数据，不相同返回false
     * @param  [type] $history [description]
     * @param  [type] $new     [description]
     * @return [type]          [description]
     */
    function contrastItemLogData($history,$new) {
        try {
            if(is_array($history)) {
                return json_encode($history) == json_encode($new);
                $history = json_decode(json_encode($history),true);
                $new = json_decode(json_encode($new),true);
                // 数组，用数组的对比
                return empty(array_merge(array_diff($history,$new),array_diff($new,$history)));
            } else {
                // 包含 , 认为是逗号分隔，分割后比较
                if(strpos($history, ',') !== false) {
                    $historyArray = explode(",", $history);
                    $newArray     = explode(",", $new);
                    $diffResult   = array_merge(array_diff($historyArray,$newArray),array_diff($newArray,$historyArray));
                    return empty($diffResult);
                } else {
                    return trim($history) == trim($new);
                }
            }
        } catch (\Exception $e) {
            return false;
        };
    }

    /**
     * [定义流程，增加系统日志，在这里处理参数和分发，最终调用flowService--addSystemLog函数插入数据]
     * @param  [type] $userId             [description]
     * @param  [type] $logContent         [description]
     * @param  [type] $log_type           [description]
     * @param  string $log_relation_table [description]
     * @param  string $log_relation_id    [description]
     * @param  string $log_relation_field [description]
     * @return [type]                     [description]
     */
    function flowDefinedAddSystemLog($param) {
        // // $param 里面传了 $this->own 解析出user_id
        // if(isset($param["own"]) && !empty($param["own"])) {
        //     $userId = isset($param["own"]["user_id"]) ? $param["own"]["user_id"] : "";
        // } else {
        //     $userId = isset($param["log_creator"]) ? $param["log_creator"] : "";
        // }
        // // 来源，批量设置/单个设置
        // if(isset($param["from"]) && $param["from"] == "batchFlow") {
        //     $logFrom = trans("flow.flow_batch_set"); // 流程批量设置
        // } else if(isset($param["from"]) && $param["from"] == "batchNode") {
        //     $logFrom = trans("flow.node_batch_set"); // 节点批量设置
        // } else {
        //     $logFrom = trans("flow.flow_set"); // 流程设置
        // }
        // // 非必填参数
        // $flowName   = isset($param["flow_name"]) ? $param["flow_name"] : "";
        // // 必填
        // $flowId   = isset($param["flow_id"]) ? $param["flow_id"] : "";
        // $nodeId   = isset($param["node_id"]) ? $param["node_id"] : "";
        // // 查 node_name 存入日志！！！
        // if(!$flowId) {
        //     $flowId = app($this->flowProcessRepository)->findFlowNode($nodeId,"flow_id");
        // }
        // if(!$flowName && $flowId) {
        //     // 查flowName
        //     $flowName = app($this->flowTypeRepository)->findFlowType($flowId);
        // }
        // /**
        //  * 传过来的是一个数组
        //  * @data [日志的数据主体，[history 历史数据，new 新数据, field 字段， field_name 字段名]]
        //  */
        // $logContent = isset($param["log_content"]) ? $param["log_content"] : [];
        // // 拼接操作详情
        // $logContent["title"] = $logFrom." : [".$flowName."] ; ";
        // // 拼接操作类型(即具体是什么操作，前端是什么页面等)
        // $operation = isset($param["operation"]) ? $param["operation"] : "";
        // $operation = $this->transformFlowDefinedLogOperation($operation);
        // $logContent["operation"] = $operation ? $operation." ; " : "";
        // // 拼接数据
        // $logContentString = json_encode($logContent);
        // $logType          = isset($param["log_type"]) ? $param["log_type"] : "definedFlow";
        // $logRelationTable = isset($param["log_relation_table"]) ? $param["log_relation_table"] : "";
        // $logRelationId    = isset($param["log_relation_id"]) ? $param["log_relation_id"] : "";
        // $logRelationField = isset($param["log_relation_field"]) ? $param["log_relation_field"] : "";
        // app($this->flowService)->addSystemLog($userId,$logContentString,$logType,$logRelationTable,$logRelationId,$logRelationField);
    }
    /**
     * 比较函数，用来对比定义流程保存的数据变动
     * @param  [type] $old [description]
     * @param  [type] $new [description]
     * @return [type]      [description]
     */
    function arrayIntersectAssocDeep($old,$new) {
        // if(!empty($new)) {
        //     $contrastResult = [];
        //     foreach ($new as $key => $value) {
        //         $contrastResult[] = [
        //             "history" => isset($old[$key]) ? $old[$key] : "",
        //             "new" => $value,
        //             "field" => $key,
        //             "field_name" => $key,
        //         ];
        //     }
        //     return $contrastResult;
        // }
        return [];
    }
    /**
     * 转换定义流程路由的 operation 参数
     * @param  [type] $operation [description]
     * @return [type]            [description]
     */
    function transformFlowDefinedLogOperation($operation) {

        switch ($operation) {
            // case 'modifyFlowDefineBasicInfo':
            //     $operationExplain = trans("flow.workflow_basic_information_modification"); // 流程基本信息修改
            //     break;
            case 'modifyFlowDefineBasicInfo-monitor':
                $operationExplain = trans("flow.workflow_monitoring_modification"); // 流程监控修改
                break;
            case 'modifyFlowDefineBasicInfo-basicInfo':
                $operationExplain = trans("flow.workflow_basic_information_modification"); // 流程基本信息修改
                break;
            case 'modifyFlowOtherInfo-':
                $operationExplain = trans("flow.process_settings_others_information_settings"); // 流程设置-其他信息设置
                break;
            case 'modifyFlowOtherInfo-editOtherBase':
                $operationExplain = trans("flow.workflow_settings_base_information_settings"); // 流程设置-基本设置
                break;
            case 'modifyFlowOtherInfo-editOtherFiling':
                $operationExplain = trans("flow.workflow_settings_filing_settings"); // 流程设置-归档设置
                break;
            case 'modifyFlowOtherInfo-editOtherPrint':
                $operationExplain = trans("flow.workflow_settings_print_template_settings"); // 流程设置-打印模板设置
                break;
            case 'modifyFlowOtherInfo-editOtherRemind':
                $operationExplain = trans("flow.workflow_settings_end_remind_settings"); // 流程设置-流程结束提醒对象设置
                break;
            case 'modifyFlowOtherInfo-editOtherRequired':
                $operationExplain = trans("flow.workflow_settings_free_workflow_required_settings"); // 流程设置-必填设置
                break;
            case 'modifyFlowOtherInfo-editOtherData':
                $operationExplain = trans("flow.workflow_settings_form_data_template_settings"); // 流程设置-表单数据模板设置
                break;
            case 'modifyFlowNode-':
            case 'modifyFlowNode-editNodeBase':
                $operationExplain = trans("flow.workflow_node_settings_node_base_information_settings"); // 流程节点设置-节点信息设置
                break;
            case 'modifyFlowNode-editNodeBaseProcessSort':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.process_sort"); // 流程节点设置-节点排序
                break;
            case 'modifyFlowNode-editNodeBaseProcessTo':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.process_to_set"); // 流程节点设置-节点出口设置
                break;
            case 'modifyFlowNode-editNodeBaseFlowMapNodeInfoUpdate':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.flow_map_node_info_update"); // 流程节点设置-流程图节点信息更新
                break;
            case 'modifyFlowNode-editNodeBaseFlowMapDeleteNodeLink':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.flow_map_delete_node_link"); // 流程节点设置-流程图删除节点连线
                break;
            case 'modifyFlowNode-editNodeBaseFlowMapDeleteAllNodeLink':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.flow_map_delete_all_node_link"); // 流程节点设置-流程图删除所有节点连线
                break;
            case 'modifyFlowNode-editNodeBaseAddFlowProcess':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.create_flow_process"); // 流程节点设置-新建节点
                break;
            case 'modifyFlowNode-editNodeBaseDeleteFlowProcess':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.delete_flow_process"); // 流程节点设置-删除节点
                break;
            case 'modifyFlowNode-editNodeBaseEditNodeCondition':
                $operationExplain = trans("flow.workflow_node_settings")."-".trans("flow.edit_node_condition"); // 流程节点设置-编辑出口条件
                break;
            case 'modifyFlowNode-editNodeBaseUnifiedSetPresstime':
                $operationExplain = trans("flow.workflow_node_settings_node_base_information_settings")."-".trans("flow.unified_set_press_time"); // 流程节点设置-节点信息设置-统一设置催促时间
                break;
            case 'modifyFlowNode-editNodeTemplate':
                $operationExplain = trans("flow.workflow_node_settings_node_form_template_settings"); // 流程节点设置-节点表单模板设置
                break;
            case 'modifyFlowNode-editNodeField':
                $operationExplain = trans("flow.workflow_node_settings_field_control_settings"); // 流程节点设置-字段控制设置
                break;
            case 'modifyFlowNode-editNodeCopy':
                $operationExplain = trans("flow.workflow_node_settings_copy_user_settings"); // 流程节点设置-抄送人员设置
                break;
            case 'modifyFlowNode-editNodeHandle':
                $operationExplain = trans("flow.workflow_node_settings_handle_user_settings"); // 流程节点设置-办理人员设置
                break;
            case 'modifyFlowNode-editNodeSunflow':
                $operationExplain = trans("flow.workflow_node_settings_sonflow_settings"); // 流程节点设置-子流程设置
                break;
            case 'modifyFlowNode-editNodeOutsend':
                $operationExplain = trans("flow.workflow_node_settings_outsend_settings"); // 流程节点设置-数据外发设置
                break;
            case 'modifyFlowNode-editNodeValidate':
                $operationExplain = trans("flow.workflow_node_settings_data_validation"); // 流程节点设置-数据验证
                break;
            case 'modifyFlowNode-editNodeOverTimeRemind':
                $operationExplain = trans("flow.workflow_node_settings_overtime"); // 流程节点设置-超时设置
                break;
            case 'modifyFlowOtherInfo-editOtherOvertime':
                $operationExplain = trans("flow.workflow_settings_overtime"); // 流程设置-归档设置
                break;
            default:
                $operationExplain = "";
                break;
        }
        return $operationExplain;
    }

    // /**
    //  * 功能函数，保存流程其他设置的日志之前，格式化flow_others数据
    //  * @param  [type] $info [description]
    //  * @return [type]       [description]
    //  */
    // function flowOthersInfoDefaultValueFormat($flowOthersInfo) {
    //     $flowOthersInfo["continuous_submission"]    = ( isset($flowOthersInfo["continuous_submission"]) && $flowOthersInfo["continuous_submission"] ) ? $flowOthersInfo["continuous_submission"] : "1";
    //     $flowOthersInfo["inheritance_sign"]         = ( isset($flowOthersInfo["inheritance_sign"]) && $flowOthersInfo["inheritance_sign"] ) ? $flowOthersInfo["inheritance_sign"] : "2";
    //     $flowOthersInfo["flow_show_data_template"]  = ( isset($flowOthersInfo["flow_show_data_template"]) && $flowOthersInfo["flow_show_data_template"] ) ? $flowOthersInfo["flow_show_data_template"] : "0";
    //     $flowOthersInfo["flow_filing_folder_rules"] = isset($flowOthersInfo["flow_filing_folder_rules"]) ? json_decode($flowOthersInfo["flow_filing_folder_rules"]) : "";
    //     return $flowOthersInfo;
    // }

    /**
     * 功能函数，在保存节点信息之前，过滤接收的参数，使其对应页面上的表单控件
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function filterFlowProcessFieldForEditLog($data,$filterType) {
        $result = [];
        // switch ($filterType) {
        //     case 'modifyFlowOtherInfo-editOtherBase':
        //         // 流程设置-基本设置
        //         $result["choice_other_tabs_feedback"]     = isset($data["choice_other_tabs_feedback"]) ? $data["choice_other_tabs_feedback"] : "";
        //         $result["choice_other_tabs_attachment"]   = isset($data["choice_other_tabs_attachment"]) ? $data["choice_other_tabs_attachment"] : "";
        //         $result["choice_other_tabs_document"]     = isset($data["choice_other_tabs_document"]) ? $data["choice_other_tabs_document"] : "";
        //         $result["choice_other_tabs_map"]          = isset($data["choice_other_tabs_map"]) ? $data["choice_other_tabs_map"] : "";
        //         $result["choice_other_tabs_step"]         = isset($data["choice_other_tabs_step"]) ? $data["choice_other_tabs_step"] : "";
        //         $result["choice_other_tabs_sunflow"]      = isset($data["choice_other_tabs_sunflow"]) ? $data["choice_other_tabs_sunflow"] : "";
        //         $result["lable_show_default"]             = isset($data["lable_show_default"]) ? $data["lable_show_default"] : "";
        //         $result["flow_autosave"]                  = isset($data["flow_autosave"]) ? $data["flow_autosave"] : "";
        //         $result["flow_to_doc"]                    = isset($data["flow_to_doc"]) ? $data["flow_to_doc"] : "";
        //         $result["file_folder_id"]                 = isset($data["file_folder_id"]) ? $data["file_folder_id"] : "";
        //         $result["flow_show_name"]                 = isset($data["flow_show_name"]) ? $data["flow_show_name"] : "";
        //         $result["feed_back_after_flow_end"]       = isset($data["feed_back_after_flow_end"]) ? $data["feed_back_after_flow_end"] : "";
        //         $result["submit_without_dialog"]          = isset($data["submit_without_dialog"]) ? $data["submit_without_dialog"] : "";
        //         $result["first_node_delete_flow"]         = isset($data["first_node_delete_flow"]) ? $data["first_node_delete_flow"] : "";
        //         $result["flow_send_back_required"]        = isset($data["flow_send_back_required"]) ? $data["flow_send_back_required"] : "";
        //         $result["flow_send_back_submit_method"]   = isset($data["flow_send_back_submit_method"]) ? $data["flow_send_back_submit_method"] : "";
        //         $result["alow_select_handle"]             = isset($data["alow_select_handle"]) ? $data["alow_select_handle"] : "";
        //         $result["flow_submit_hand_remind_toggle"] = isset($data["flow_submit_hand_remind_toggle"]) ? $data["flow_submit_hand_remind_toggle"] : "";
        //         $result["continuous_submission"]          = isset($data["continuous_submission"]) ? $data["continuous_submission"] : "";
        //         $result["flow_show_history"]              = isset($data["flow_show_history"]) ? $data["flow_show_history"] : "";
        //         break;
        //     case 'modifyFlowOtherInfo-editOtherFiling':
        //         // 流程设置-归档设置
        //         $result["flow_filing_template_toggle"]           = isset($data["flow_filing_template_toggle"]) ? $data["flow_filing_template_toggle"] : "";
        //         $result["file_folder_id"]                        = isset($data["file_folder_id"]) ? $data["file_folder_id"] : "";
        //         $result["flow_to_doc_rule"]                      = isset($data["flow_to_doc_rule"]) ? $data["flow_to_doc_rule"] : "";
        //         $result["flow_filing_folder_rules_toggle"]       = isset($data["flow_filing_folder_rules_toggle"]) ? $data["flow_filing_folder_rules_toggle"] : "";
        //         $result["flow_to_doc"]                           = isset($data["flow_to_doc"]) ? $data["flow_to_doc"] : "";
        //         $result["flow_filing_conditions_setting_toggle"] = isset($data["flow_filing_conditions_setting_toggle"]) ? $data["flow_filing_conditions_setting_toggle"] : "";
        //         $result["flow_filing_conditions_setting_value"]  = isset($data["flow_filing_conditions_setting_value"]) ? $data["flow_filing_conditions_setting_value"] : "";
        //         $result["flow_filing_folder_rules_html"]         = isset($data["flow_filing_folder_rules_html"]) ? $data["flow_filing_folder_rules_html"] : "";
        //         $result["flow_filing_folder_rules"]              = isset($data["flow_filing_folder_rules"]) ? $data["flow_filing_folder_rules"] : "";
        //         break;
        //     case 'modifyFlowOtherInfo-editOtherPrint':
        //         // 流程设置-打印模板设置
        //         $result["flow_print_template_toggle"] = isset($data["flow_print_template_toggle"]) ? $data["flow_print_template_toggle"] : "";
        //         break;
        //     case 'modifyFlowOtherInfo-editOtherRemind':
        //         // 流程设置-流程结束提醒对象设置
        //         $result["remind_target"]   = isset($data["remind_target"]) ? $data["remind_target"] : "";
        //         $result["flow_end_remind"] = isset($data["flow_end_remind"]) ? $data["flow_end_remind"] : "";
        //         $result["appoint_process"] = isset($data["appoint_process"]) ? $data["appoint_process"] : "";
        //         break;
        //     case 'modifyFlowOtherInfo-editOtherData':
        //         // 流程设置-表单数据模板设置
        //         $result["flow_show_data_template"] = isset($data["flow_show_data_template"]) ? $data["flow_show_data_template"] : "";
        //         break;
        //     case 'modifyFlowOtherInfo-editOtherBase-history':
        //     case 'modifyFlowOtherInfo-editOtherFiling-history':
        //     case 'modifyFlowOtherInfo-editOtherPrint-history':
        //     case 'modifyFlowOtherInfo-editOtherRemind-history':
        //     case 'modifyFlowOtherInfo-editOtherData-history':
        //         // 流程设置-基本设置-历史数据
        //         $result = $data;
        //         $result["continuous_submission"]    = ( isset($data["continuous_submission"]) && $data["continuous_submission"] ) ? $data["continuous_submission"] : "1";
        //         $result["inheritance_sign"]         = ( isset($data["inheritance_sign"]) && $data["inheritance_sign"] ) ? $data["inheritance_sign"] : "2";
        //         $result["flow_show_data_template"]  = ( isset($data["flow_show_data_template"]) && $data["flow_show_data_template"] ) ? $data["flow_show_data_template"] : "0";
        //         $result["flow_filing_folder_rules"] = isset($data["flow_filing_folder_rules"]) ? json_decode($data["flow_filing_folder_rules"]) : "";
        //         break;
        //     case 'modifyFlowNode-editNodeBase':
        //         // 流程节点设置-节点信息设置
        //         $result["node_id"]               = isset($data["node_id"]) ? $data["node_id"] : "";
        //         $result["process_id"]            = isset($data["process_id"]) ? $data["process_id"] : "";
        //         $result["process_name"]          = isset($data["process_name"]) ? $data["process_name"] : "";
        //         $result["sort"]                  = isset($data["sort"]) ? $data["sort"] : "";
        //         $result["process_to"]            = isset($data["process_to"]) ? $data["process_to"] : "";
        //         $result["end_workflow"]          = isset($data["end_workflow"]) ? $data["end_workflow"] : "";
        //         $result["process_transact_type"] = isset($data["process_transact_type"]) ? $data["process_transact_type"] : "";
        //         $result["process_forward"]       = isset($data["process_forward"]) ? $data["process_forward"] : "";
        //         $result["process_copy"]          = isset($data["process_copy"]) ? $data["process_copy"] : "";
        //         $result["flow_outmail"]          = isset($data["flow_outmail"]) ? $data["flow_outmail"] : "";
        //         $result["process_concourse"]     = isset($data["process_concourse"]) ? $data["process_concourse"] : "";
        //         $result["press_add_hour"]        = isset($data["press_add_hour"]) ? $data["press_add_hour"] : "";
        //         $result["process_descript"]      = isset($data["process_descript"]) ? $data["process_descript"] : "";
        //         break;
        //     case 'modifyFlowNode-editNodeTemplate':
        //         // 流程节点设置-节点表单模板设置
        //         $result["flow_run_template_toggle"] = isset($data["flow_run_template_toggle"]) ? $data["flow_run_template_toggle"] : "";
        //         break;
        //     case 'modifyFlowNode-editNodeCopy':
        //         // 流程节点设置-抄送人员设置
        //         $process_copy_user = isset($data["process_copy_user"]) ? $data["process_copy_user"] : "";
        //         $process_copy_role = isset($data["process_copy_role"]) ? $data["process_copy_role"] : "";
        //         $process_copy_dept = isset($data["process_copy_dept"]) ? $data["process_copy_dept"] : "";
        //         $process_copy_user = is_array($process_copy_user) ? implode("," , $process_copy_user) : $process_copy_user;
        //         $process_copy_role = is_array($process_copy_role) ? implode("," , $process_copy_role) : $process_copy_role;
        //         $process_copy_dept = is_array($process_copy_dept) ? implode("," , $process_copy_dept) : $process_copy_dept;
        //         $result["process_copy_user"] = $process_copy_user;
        //         $result["process_copy_role"] = $process_copy_role;
        //         $result["process_copy_dept"] = $process_copy_dept;
        //         $result["process_auto_get_copy_user"] = isset($data["process_auto_get_copy_user"]) ? $data["process_auto_get_copy_user"] : "";
        //         $result["get_agency"]                 = isset($data["get_agency"]) ? $data["get_agency"] : "";
        //         break;
        //     case 'modifyFlowNode-editNodeCopy-history':
        //         // 流程节点设置-抄送人员设置-历史数据处理
        //         $copy_user_id = isset($data["copy_user_id"]) ? $data["copy_user_id"] : "";
        //         $copy_user_id = json_decode(json_encode($copy_user_id));
        //         $copy_user_id = is_array($copy_user_id) ? implode("," , $copy_user_id) : $copy_user_id;
        //         $copy_role_id = isset($data["copy_role_id"]) ? $data["copy_role_id"] : "";
        //         $copy_role_id = json_decode(json_encode($copy_role_id));
        //         $copy_role_id = is_array($copy_role_id) ? implode("," , $copy_role_id) : $copy_role_id;
        //         $copy_dept_id = isset($data["copy_dept_id"]) ? $data["copy_dept_id"] : "";
        //         $copy_dept_id = json_decode(json_encode($copy_dept_id));
        //         $copy_dept_id = is_array($copy_dept_id) ? implode("," , $copy_dept_id) : $copy_dept_id;
        //         $result["process_copy_user"] = (isset($data["process_copy_user"]) && $data["process_copy_user"] == "ALL") ? "all" : $copy_user_id;
        //         $result["process_copy_role"] = (isset($data["process_copy_role"]) && $data["process_copy_role"] == "ALL") ? "all" : $copy_role_id;
        //         $result["process_copy_dept"] = (isset($data["process_copy_dept"]) && $data["process_copy_dept"] == "ALL") ? "all" : $copy_dept_id;
        //         $result["process_auto_get_copy_user"] = isset($data["process_auto_get_copy_user"]) ? $data["process_auto_get_copy_user"] : "0";
        //         $result["get_agency"]                 = isset($data["get_agency"]) ? $data["get_agency"] : "";
        //         break;
        //     case 'modifyFlowNode-editNodeHandle':
        //         // 流程节点设置-办理人员设置
        //         $process_user = isset($data["process_user"]) ? $data["process_user"] : "";
        //         $process_role = isset($data["process_role"]) ? $data["process_role"] : "";
        //         $process_dept = isset($data["process_dept"]) ? $data["process_dept"] : "";
        //         $process_user = is_array($process_user) ? implode("," , $process_user) : $process_user;
        //         $process_role = is_array($process_role) ? implode("," , $process_role) : $process_role;
        //         $process_dept = is_array($process_dept) ? implode("," , $process_dept) : $process_dept;
        //         $result["process_user"] = $process_user;
        //         $result["process_role"] = $process_role;
        //         $result["process_dept"] = $process_dept;
        //         $result["process_default_user"]   = isset($data["process_default_user"]) ? $data["process_default_user"] : "";
        //         $result["process_default_manage"] = isset($data["process_default_manage"]) ? $data["process_default_manage"] : "";
        //         $result["process_default_type"]   = isset($data["process_default_type"]) ? $data["process_default_type"] : "";
        //         $result["process_auto_get_user"]  = isset($data["process_auto_get_user"]) ? $data["process_auto_get_user"] : "";
        //         $result["get_agency"]             = isset($data["get_agency"]) ? $data["get_agency"] : "";
        //         break;
        //     case 'modifyFlowNode-editNodeHandle-history':
        //         // 流程节点设置-办理人员设置-历史数据处理
        //         $handle_user_id = isset($data["handle_user_id"]) ? $data["handle_user_id"] : "";
        //         $handle_user_id = json_decode(json_encode($handle_user_id));
        //         $handle_user_id = is_array($handle_user_id) ? implode("," , $handle_user_id) : $handle_user_id;
        //         $handle_role_id = isset($data["handle_role_id"]) ? $data["handle_role_id"] : "";
        //         $handle_role_id = json_decode(json_encode($handle_role_id));
        //         $handle_role_id = is_array($handle_role_id) ? implode("," , $handle_role_id) : $handle_role_id;
        //         $handle_dept_id = isset($data["handle_dept_id"]) ? $data["handle_dept_id"] : "";
        //         $handle_dept_id = json_decode(json_encode($handle_dept_id));
        //         $handle_dept_id = is_array($handle_dept_id) ? implode("," , $handle_dept_id) : $handle_dept_id;
        //         $default_user_id = isset($data["default_user_id"]) ? $data["default_user_id"] : "";
        //         $default_user_id = json_decode(json_encode($default_user_id));
        //         $default_user_id = is_array($default_user_id) ? implode("," , $default_user_id) : $default_user_id;
        //         $result["process_user"] = (isset($data["process_user"]) && $data["process_user"] == "ALL") ? "all" : $handle_user_id;
        //         $result["process_role"] = (isset($data["process_role"]) && $data["process_role"] == "ALL") ? "all" : $handle_role_id;
        //         $result["process_dept"] = (isset($data["process_dept"]) && $data["process_dept"] == "ALL") ? "all" : $handle_dept_id;
        //         $result["process_default_user"]  = (isset($data["process_default_user"]) && $data["process_default_user"] == "ALL") ? "all" : $default_user_id;
        //         $result["process_default_user"]   = isset($data["process_default_user"]) ? $data["process_default_user"] : "";
        //         $result["process_default_manage"] = isset($data["process_default_manage"]) ? $data["process_default_manage"] : "";
        //         $result["process_default_type"]   = isset($data["process_default_type"]) ? $data["process_default_type"] : "";
        //         $result["process_auto_get_user"] = isset($data["process_auto_get_user"]) ? $data["process_auto_get_user"] : "0";
        //         $result["get_agency"]            = isset($data["get_agency"]) ? $data["get_agency"] : "";
        //         break;
        //     default:
        //         $result = $data;
        //         break;
        // }
        return $result;
    }
    public function addFlowDataLogs($file, $data = []) {
        // 流程表单数据日志开关
        static $flowGetDataToggle = null;
        if ($flowGetDataToggle === null) {
            $flowGetDataToggle = envOverload("FLOW_GET_DATA_TOGGLE");
        }
        if ($flowGetDataToggle !== 'true') {
            return false;
        }
        if ($file) {
            $path = Utils::getAttachmentDir('logs');

            if(!is_dir($path. 'data_logs')){
                mkdir($path. 'data_logs', 0777, true);
                chmod($path. 'data_logs', 0777);
            }else {
                // 去掉这里的处理防止报错（手动处理data_logs目录的权限）
                // chmod($path. 'data_logs', 0777);
            }
            $file_path = $path . 'data_logs/' . $file;

            file_put_contents($file_path, $data, FILE_APPEND);
        }
    }

    /**
     * 模块间数据交互记录日志
     *
     * @author zyx
     *
     * @param [array] $fromInfo
     * @param [array] $toInfo
     * @param string $dataHandleType
     * @param string $userId
     *
     * @since 20200121
     */
    public function addFlowOutsendToModuleLog($fromInfo, $toInfo, $module, $userId)
    {
        $insertDatas = [];
        // 可能有多个返回值单元
        foreach ($toInfo as $toInfoVal) {
            if (!$toInfoVal) {
                continue;
            }
            // id_to参数可能是一张表多个ID的情况
            $moduleIdArr = is_string($toInfoVal['id_to']) ? explode(',', trim($toInfoVal['id_to'], ',')) : (is_array($toInfoVal['id_to']) ? $toInfoVal['id_to'] : [$toInfoVal['id_to']]);
            if (count($moduleIdArr)) {
                foreach ($moduleIdArr as $idVal) {
                    $insertDatas[] = [
                        'flow_run_id' => $fromInfo['flow_run_id'],
                        'flow_outsend_id' => $fromInfo['flow_outsend_id'],
                        'dependent_field' => json_encode($fromInfo['dependent_field']),
                        'data_handle_type' => $fromInfo['data_handle_type'],
                        'module_to' => $module,
                        'table_to' => $toInfoVal['table_to'],
                        'field_to' => $toInfoVal['field_to'],
                        'id_to' => $idVal,
                        'creator' => $userId,
                        'log_time' => date('Y-m-d H:i:s'),
                        'is_to_detail' => $fromInfo['is_to_detail'] ?? 0,
                    ];
                }
            }
        }
        if (count($insertDatas)) {
            DB::table('flow_outsend_to_module_log')->insert($insertDatas);
        }
    }

    /**
     * 添加系统日志
     *
     * @param [type] $userId
     * @param [type] $logContent
     * @param [type] $log_type
     * @param string $log_relation_table  数据外发时 => 'flow_run,flow_outsend'
     * @param string $log_relation_id  数据外发时 => $run_id,$outsend_id
     * @param string $log_relation_field  数据外发时 => 'run_id,outsend_id'
     * @param integer $is_failed
     * @param [string] $ip 地址外发和外部数据库外发的IP不需要获取，20200427,zyx修改
     * @param $relationTableInfoAdd 外发失败日志补充字段，20200807,zyx增加
     * @return void
     */
    public function addSystemLog($userId, $logContent, $log_type, $log_relation_table = '', $log_relation_id = '', $log_relation_field = '', $is_failed = 0, $ip = '', $relationTableInfoAdd = [] ,  $relation_title = '') {
        // $data = [
        //     'log_content' => $logContent,
        //     'log_type' => $log_type,
        //     'log_creator' => $userId,
        //     'log_time' => date('Y-m-d H:i:s'),
        //     'module' => 'flow',
        //     'log_ip' => $ip ? $ip : getClientIp(),
        //     'log_relation_table' => $log_relation_table, // 关联表
        //     'log_relation_id' => $log_relation_id, // 关联表主键
        //     'log_relation_field' => $log_relation_field, // 关联表字段
        //     'is_failed' => $is_failed // 外发成功或失败，成功为0，失败为1
        // ];
        $flowDesign = ['definedFlow', 'defineFlowDelete', 'UserReplace', 'quitUserReplace'];
        $flowRun = ['outsend', 'sunFlow', 'initFlowRunSeq', 'runFlowDelete', 'sunflow'];
        $categoryArr = ['definedFlow' => 'edit', 'defineFlowDelete' => 'delete', 'UserReplace' => 'handle_user_replace', 'quitUserReplace' => 'quit_user_replace',
            'outsend' => 'out_send', 'sunFlow' => 'create_sun_flow', 'initFlowRunSeq' => 'init_flow_run_seq', 'runFlowDelete' => 'delete', 'sunflow' => 'create_sun_flow'
        ];
        $category  = in_array( $log_type, $flowRun) ? 'flow_run' : 'flow_design';
        $operate =  $categoryArr[$log_type] ?? '';
        $data = [
            'creator' => $userId,
            'content' => $logContent,
            'relation_id' => $log_relation_id,
            'relation_table' => $log_relation_table,
            'is_failed' => $is_failed, // 外发成功或失败，成功为0，失败为1
            'log_relation_field' => $log_relation_field, // 关联表字段
            'relation_title' => $relation_title, // 操作对象
        ];
         // 20200807,zyx,如果是外发失败日志，需要补充关联表数据
        if (($log_type == 'outsend') && $relationTableInfoAdd) {
            $data['log_relation_table_add'] = $relationTableInfoAdd['log_relation_table_add']; // 补充关联表
            $data['log_relation_id_add'] = $relationTableInfoAdd['log_relation_id_add']; // 补充关联表主键
            $data['log_relation_field_add'] = $relationTableInfoAdd['log_relation_field_add']; // 补充关联表字段
        }
        logCenter::info("workflow.{$category}.{$operate}",$data);
        // add_system_log($data);
    }
    /**
     * 推送数据记录之集成中心
     *
     */
    public function addOperationRecordToIntegrationCenter($params) {
        // 获取集成中心推送开启状态
        if (!Redis::exists('todu_push_status')) {
            $status = app($this->todoPushService)->getPushStatus();
        }else {
            $status = Redis::get('todu_push_status');
        }
        if (!$status) {
            return false;
        }
        // 接收人
        $receiveUser = $params['receiveUser'] ?? '';
        // 发送时间
        $deliverTime = $params['deliverTime'] ??  date('Y-m-d H:i:s', time());
        // 发送人
        $deliverUser = $params['deliverUser'] ?? '';
        // 操作类型
        $operationType = $params['operationType'] ?? '';
        // 操作分类
        $operationId = $params['operationId'] ?? '';
        $flowRunProcessId = $params['flowRunProcessId'] ?? '';
        $operationTitleArray = [
            '1' => '主办人提交',
            '2' => '经办人提交',
            '3' => '系统自动提交',
            '4' => '结束流程',
            '5' => '收回流程',
            '6' => '流程委托',
            '7' => '挂起流程',
            '8' => '流程转发',
            '9' => '新建流程',
            '10' => '删除流程',
            '11' => '停用且隐藏流程',
            '12' => '启用流程',
            '13' => '流程交办',
            '14' => '取消挂起'
        ];
        $dateTypeTitle = [
            '1' => '增加待办',
            '2' => '增加已办',
            '3' => '增加办结',
            '4' => '删除',
            '5' => '挂起',
            '6' => '停用',
            '7' => '启用',
        ];
        $operationTitle = $operationTitleArray[$operationId] ?? '';
        $flowId = $params['flowId'] ?? '';
        $runId = $params['runId'] ?? '';
        $processId = $params['processId'] ?? '';
        $viewType = $params['viewType'] ?? '0';
        $dateType = '';
        switch ($operationId) {
            case '1':
            case '5':
            case '6':
            case '13':
            case '14':
                if ($operationType == 'add') {
                    $dateType = '1';
                }elseif($operationType == 'reduce') {
                    $dateType = '2';
                }
                break;
            case '2':
            case '3':
                $dateType = '2';
                break;
            case '4':
                $dateType = '3';
                break;
            case '7':
                $dateType = '5';
                break;
            case '8':
            case '9':
                $dateType = '1';
                break;
            case '10':
                $dateType = '4';
                break;
            case '11':
                $dateType = '6';
                break;
            case '12':
                $dateType = '7';
                break;
            default:
                $dateType = '';
                break;
        }
        $data = [
            'receiveUser' => $receiveUser,
            'deliverTime' => $deliverTime,
            'deliverUser' => $deliverUser,
            'operationType' => $operationType,
            'operationId' => $operationId,
            'operationTitle' => $operationTitle,
            'dateType' => $dateType,
            'flowId' => $flowId,
            'runId' => $runId,
            'processId' => $processId,
            'viewType' => $viewType,
            'flowRunProcessId' => $flowRunProcessId
        ];
        Queue::push(new FlowToIntegrationCenterWebhookSendJob($data));
    }
}
