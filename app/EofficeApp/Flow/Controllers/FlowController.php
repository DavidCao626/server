<?php
namespace App\EofficeApp\Flow\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Flow\Requests\FlowRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
/**
 * 流程 controller
 * 这个类，用来：1、验证request；2、组织数据；3、调用service实现功能；[4、组织返回值]
 *
 * @author dingpeng
 *
 * @since  2015-10-16 创建
 */
class FlowController extends Controller
{
    public function __construct(
        Request $request,
        FlowRequest $flowRequest
    ) {
        parent::__construct();
        $userInfo              = $this->own;
        $this->userId          = isset($userInfo['user_id']) ? $userInfo['user_id'] : "";
        $this->flowService     = 'App\EofficeApp\Flow\Services\FlowService';
        $this->flowRunService  = 'App\EofficeApp\Flow\Services\FlowRunService';
        $this->flowPrintService  = 'App\EofficeApp\Flow\Services\FlowPrintService';
        $this->flowFormService = 'App\EofficeApp\Flow\Services\FlowFormService';
        $this->flowReportService = 'App\EofficeApp\Flow\Services\FlowReportService';
        $this->flowWorkHandOverService = 'App\EofficeApp\Flow\Services\FlowWorkHandOverService';
        $this->flowSettingService = 'App\EofficeApp\Flow\Services\FlowSettingService';
        $this->flowParseService   = 'App\EofficeApp\Flow\Services\FlowParseService';
        $this->flowTrashService   = 'App\EofficeApp\Flow\Services\FlowTrashService';
        $this->flowPermissionService = 'App\EofficeApp\Flow\Services\FlowPermissionService';
        $this->flowRunStepRepository = 'App\EofficeApp\Flow\Repositories\FlowRunStepRepository';
        $this->flowRunProcessRepository = 'App\EofficeApp\Flow\Repositories\FlowRunProcessRepository';
        $this->flowRunRepository     = 'App\EofficeApp\Flow\Repositories\FlowRunRepository';
        $this->flowExportService     = 'App\EofficeApp\Flow\Services\FlowExportService';
        $this->attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService'); // 待附件上传类暴露出通用方法后删除
        $this->flowControlService     = 'App\EofficeApp\Flow\Services\FlowControlService';
        $this->flowOutsendService = 'App\EofficeApp\Flow\Services\FlowOutsendService';
        $this->flowMonitorService = 'App\EofficeApp\Flow\Services\FlowMonitorService';
        $this->flowSortRepository = 'App\EofficeApp\Flow\Repositories\FlowSortRepository';
        $this->flowFormSortRepository = 'App\EofficeApp\Flow\Repositories\FlowFormSortRepository';
        $this->flowTypeRepository  = 'App\EofficeApp\Flow\Repositories\FlowTypeRepository';
        $this->formFilter($request, $flowRequest);
        $this->request = $request;
    }

    /**
     * 获取当前人员可以新建的流程;带查询
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function getNewIndexCreateList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->own["user_id"] ?? "";
        $data['dept_id'] = $this->own['dept_id'] ?? "";
        $data['role_id'] = !empty($this->own['role_id']) ? implode(',', $this->own['role_id']) : '';
        $result = app($this->flowService)->flowNewIndexCreateList($data);
        return $this->returnResult($result);
    }

    /**
     * 获取常用流程list
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function getFavoriteFlowList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->own["user_id"] ?? "";
        $data['dept_id'] = $this->own['dept_id'] ?? "";
        $data['role_id'] = !empty($this->own['role_id']) ? implode(',', $this->own['role_id']) : '';
        $result = app($this->flowService)->getFavoriteFlowList($data);
        return $this->returnResult($result);
    }

    /**
     * 获取某个流程的当前用户创建的最新的20条流程，供历史数据导入;带查询[run_name];必填flow_id;
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function getNewIndexCreateHistoryList()
    {
        $data = $this->request->all();
        if (isset($data["user_id"])) {
            $data["user_id"] = $this->own["user_id"] ?? "";
        }
        $result = app($this->flowService)->flowNewIndexCreateHistoryList($data);
        if (isset($data['platform']) && $data['platform'] == 'mobile') {
            $mobileResult['list'] = $result;
            $mobileResult['total'] = count($result) == 0 ? 0: 10;
             return $this->returnResult($mobileResult);
        }
        return $this->returnResult($result);
    }

    /**
     * 【新建流程index】 流程收藏 新建收藏
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function addFlowFavorite()
    {
        $data = $this->request->all();
        if (isset($data["user_id"])) {
            $data["user_id"] = $this->own["user_id"] ?? "";
        }
        $flowId = isset($data["flow_id"]) ? $data["flow_id"] : "";
        if(!$flowId) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        $permissionParams = ["own" => $this->own,"flow_id" => $flowId];
        // 验证权限，能新建的流程才能被加入收藏
        if(app($this->flowPermissionService)->verifyFlowNewPermission($permissionParams)) {
            $result = app($this->flowService)->addFlowFavorite($data);
            return $this->returnResult($result);
        } else {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
    }

    /**
     * 【新建流程index】 流程收藏 删除收藏
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function deleteFlowFavorite()
    {
        $data = $this->request->all();
        if (isset($data["user_id"])) {
            $data["user_id"] = $this->own["user_id"] ?? "";
        }
        $result = app($this->flowService)->deleteFlowFavorite($data);
        return $this->returnResult($result);
    }

    /**
     * 流程挂起
     */
    public function hangupFlow()
    {
        $data = $this->request->all();
        if (!isset($data['flow_step_id'])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $flowRunStepInfo = app($this->flowRunProcessRepository)->getDetail($data['flow_step_id']);
        if (empty($flowRunStepInfo->run_id)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data["user_id"] = $this->userId;
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $flowRunStepInfo->run_id,
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowParseService)->hangupFlow($data);
        return $this->returnResult($result);
    }
    /**
     * 取消流程挂起
     */
    public function cancelHangupFlow()
    {
        $data = $this->request->all();
        if (!isset($data['flow_step_id'])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $flowRunStepInfo = app($this->flowRunProcessRepository)->getDetail($data['flow_step_id']);
        if (empty($flowRunStepInfo->run_id)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data["user_id"] = $this->userId;
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $flowRunStepInfo->run_id,
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowParseService)->cancelHangupFlow($data);
        return $this->returnResult($result);
    }

    /**
     * 新建流程页面根据设置，展示流程基本信息;flow_id必填
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function getNewPageFlowRunInfo()
    {
        $data = $this->request->all();
        if (isset($data["user_id"])) {
            $data["user_id"] = $this->own["user_id"] ?? "";
        }
        $result = app($this->flowService)->flowNewPageFlowRunInfo($data);
        return $this->returnResult($result);
    }

    /**
     * 新建流程页面，展示流程表单;流程表单暂时不解析！
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function getNewPageFlowFrom()
    {
        // $result = app($this->flowService)->flowNewPageFlowFrom($this->request->all());
        // return $this->returnResult($result);
    }

    /**
     * 新建流程页面，带着流程标题，创建流程，新建流程运行数据和流程表单数据，返回流程运行数据ID即【run_id】。
     *
     * @apiTitle 创建流程
     * @param {int} flow_id 【必填】要创建流程的流程ID，在流程管理-流程设计-基本信息页面可查看到此参数。
     * @param {string} flow_run_name 【必填】流程标题。
     * @param {string} run_name_html 【必填】流程标题HTML，可以直接等于flow_run_name的值，注意需要包裹一层div标签。
     * @param {array} form_data 【可选】流程表单数据。
     * @param {string} create_type 【可选】创建流程类型，传"sonFlow"时，表明是创建子流程。
     * @param {int} instancy_type 【可选】流程紧急程度，0：正常、1：重要、2：紧急，默认值是0。
     * @param {int} agentType 【可选】如果用户设置了此流程的委托规则，用户在新建流程时选择的委托处理方式。1：委托掉，2：保留。

     * @paramExample {json} 整体参数示例
     * {
     *  "flow_id":"1",
     *  "flow_run_name":"测试创建流程",
     *  "run_name_html":"<div>测试创建流程</div>",
     *  "form_data":{
     *      "DATA_1" : "1",
     *      "DATA_2" : "2",
     *      "DATA_4" : {
     *          "DATA_4_1" : "xxxx_1_1",
     *          "DATA_4_2" : {
     *              "b","c"
     *          }
     *      }
     *  },
     *  "create_type":"",
     *  "instancy_type":"0",
     *  "agentType":"2"
     * }
     *
     * @success {int} run_id 流程运行数据ID
     * @success {int} process_id 流程运行步骤序号
     * @success {int} flow_process 当前流程所在节点ID，在流程管理-流程设计-节点设置-节点信息页面可查看节点ID
     * @success {int} entrust_flag 流程创建后是否已经被委托的标识，1：表示已经委托
     * @success {string} entrust_user_name 流程创建后，被委托人的用户名
     * @successExample {json} 成功返回值示例
     * {
     *   "run_id":"100",
     *   "process_id":"1",
     *   "flow_process":"1",
     *   "entrust_flag":"",
     *   "entrust_user_name":"",
     * }
     */
    public function newPageSaveFlow()
    {
        $data = $this->request->all();
        $data["creator"] = $this->userId;
        $flowId = isset($data["flow_id"]) ? $data["flow_id"] : "";
        if(!$flowId) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        $permissionParams = ["own" => $this->own,"flow_id" => $flowId];
        // 验证权限，能新建的流程才能被保存并创建run_id
        if(app($this->flowPermissionService)->verifyFlowNewPermission($permissionParams)) {
            $result = app($this->flowService)->newPageSaveFlowInfo($data, $this->own);
            return $this->returnResult($result);
        } else {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
    }

    /**
     * 新建流程页面，判断是否有委托
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function verifyFlowHaveAgent()
    {
        $data = $this->request->all();
        if (isset($data["user_id"])) {
            $data["user_id"] = $this->own["user_id"] ?? "";
        }
        $flowId = isset($data["flow_id"]) ? $data["flow_id"] : 0;
        $permissionParams = ["own" => $this->own, "flow_id" => $flowId];
        // 验证权限，能新建的流程才能进行下面的判断
        if (app($this->flowPermissionService)->verifyFlowNewPermission($permissionParams)) {
            $result = app($this->flowService)->verifyFlowHaveAgent($data);
            return $this->returnResult($result);
        } else {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
    }

    /**
     * 流程办理页面 获取流程主体部分所需所有数据，分办理页面/新建页面/查看页面
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function getFlowHandlePageMainData()
    {
        $data = $this->request->all();
        $data['currentUser'] = isset($this->own['user_id']) ? $this->own['user_id'] : "";
        $result = app($this->flowService)->getFlowHandlePageMainData($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 流程办理页面 判断不选人提交
     *
     * @author 缪晨晨
     *
     * @since  2018-12-05 创建
     *
     * @return array    不选人提交参数
     */
    public function verifySubmitWithoutDialog()
    {
        $param = $this->request->all();
        $runId = isset($param["runId"]) ? $param["runId"] : 0;
        if (($runId > 0) && !app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "handle", "run_id" => $runId, "user_id" => $this->userId])) {
            $result = [
                "turn" => [
                    "flag" => false,
                ],
                "back" => [
                    "flag" => false,
                ],
            ];
            return $this->returnResult($result);
        }
        $result = app($this->flowService)->verifySubmitWithoutDialog($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 流程办理页面 获取流程办理/查看页面上，流程其他信息标签里面的数量
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    public function getFlowRunOtherTabsCount($runId)
    {
        $param = $this->request->all();
        if (($runId > 0) && !app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view", "run_id" => $runId, "user_id" => $this->userId , 'request' =>$param])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowRunOtherTabsCount($runId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 查看/办理页面初始化的时候，当前人员接收流程
     *
     * @author dingpeng
     *
     * @return [type]              [description]
     */
    public function saveReceiveFlow($runId)
    {
        $data = $this->request->all();
        $data["user_id"] = isset($this->own["user_id"]) ? $this->own["user_id"] : "";
        $result = app($this->flowService)->saveReceiveFlowRun($runId, $data);
        return $this->returnResult($result);
    }
    /**
     * 根据办理人员排班信息来更新超时时间
     *
     * @author wz
     *
     * @return [type]              [description]
     */
    public function getOvertimeBySelectedUser()
    {
        $data = $this->request->all();
        $data["user_id"] = isset($this->own["user_id"]) ? $this->own["user_id"] : "";
        $result = app($this->flowService)->getOvertimeBySelectedUser($data);
        return $this->returnResult($result);
    }
    /**
     * 根据下一节点信息获取流程超时时间
     *
     * @author wz
     *
     * @return [type]              [description]
     */
    public function getOvertimeByFlowProcess()
    {
        $data = $this->request->all();
        $data["user_id"] = isset($this->own["user_id"]) ? $this->own["user_id"] : "";
        $result = app($this->flowService)->getOvertimeByFlowProcess($data, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 查看/办理页面初始化的时候，记录最后查看时间
     *
     * @author dingpeng
     *
     * @return [type]              [description]
     */
    public function recordLastVisitdTime($runId)
    {
        $data = $this->request->all();
        $data["user_id"] = isset($this->own["user_id"]) ? $this->own["user_id"] : "";
        $result = app($this->flowService)->saveLastVisitdTime($runId, $data);
        return $this->returnResult($result);
    }

    /**
     * 删除流程所有数据
     *
     * @author dingpeng
     *
     * @return [type]     [description]
     */
    public function deleteFlow($runId)
    {
        $result = app($this->flowTrashService)->deleteFlowAll($runId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 保存流程名称、流水号、流程表单等信息
     *
     * @apiTitle 保存流程信息
    * @param {int} run_id 【必填】流程运行数据ID。
    * @param {int} run_seq 【可选】流程流水号。
    * @param {string} run_name 【可选】流程标题。
    * @param ... 【可选】流程主表flow_run表其他字段
    * @param {array} form_data 【可选】流程表单数据。
    * @paramExample {json} 整体参数示例
    * {
    *  "run_id":"100",
    *  "run_seq":"A001",
    *  "run_name":"测试保存流程",
    *  "form_data":{
    *      "DATA_1" : "1",
    *      "DATA_2" : "2",
    *      "DATA_4" : {
    *          "DATA_4_1" : "xxxx_1_1",
    *          "DATA_4_2" : {
    *              "b","c"
    *          }
    *      }
    *  },
    * }
    */
    public function saveFlowRunFlowInfo()
    {
        $data = $this->request->all();
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $data['run_id'] ?? '',
            'user_id' => $this->userId ,
            'max_process_id' => $data['max_process_id'] ?? '',
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            if (isset($data['process_id']) && $data['process_id'] == $verifyPermissionParams['max_process_id']) {
            	return $this->returnResult(['code' => ['flow_have_takeback', 'flow']]);
            }
        	return $this->returnResult(['code' => ['flow_have_end', 'flow']]);
            // return $this->returnResult(['code' => ['flow_save_error_tip', 'flow']]);
        }
        if (isset($data['max_process_id'])) {
            unset($data['max_process_id']);
        }
        $result = app($this->flowService)->saveFlowRunInfo($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程签办反馈】，获取签办反馈列表
     *
     * @author dingpeng
     *
     * @return [type]              [description]
     */
    public function getFlowFeedbackList($runId)
    {
        $data = $this->request->all();
        if(($runId > 0) && !app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view","run_id" => $runId,"user_id" => $this->userId , 'request' =>$data ])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowFeedbackListService($this->request->all(), $runId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程签办反馈】，新建签办反馈
     *
     * @author dingpeng
     *
     * @return [type]              [description]
     */
    public function createFlowFeedback()
    {
        $data = $this->request->all();
        if (isset($data["user_id"])) {
            $data["user_id"] = $this->own["user_id"] ?? "";
        }
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view","run_id" => $data["run_id"],"user_id" => $this->userId])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->createFlowFeedbackService($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程签办反馈】，编辑签办反馈
     *
     * @author dingpeng
     *
     * @return [type]              [description]
     */
    public function editFlowFeedback($feedbackId)
    {
        $data = $this->request->all();
        if (!isset($data["user_id"]) || ($data["user_id"] != $this->own["user_id"])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data["user_id"] = $this->own["user_id"] ?? "";
        // 编辑/删除权限验证
        if(app($this->flowPermissionService)->verifyFlowFeedbackPermission(["feedback_id" => $feedbackId,"user_id" => $data["user_id"]]) !== "1") {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->editFlowFeedbackService($data, $feedbackId);
        return $this->returnResult($result);
    }

    /**
     * 【流程签办反馈】，删除签办反馈
     *
     * @author dingpeng
     *
     * @return [type]              [description]
     */
    public function deleteFlowFeedback($feedbackId)
    {
        $data = $this->request->all();
        if (!isset($data["user_id"]) || ($data["user_id"] != $this->own["user_id"])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data["user_id"] = $this->own["user_id"] ?? "";
        // 编辑/删除权限验证
        if(app($this->flowPermissionService)->verifyFlowFeedbackPermission(["feedback_id" => $feedbackId,"user_id" => $data["user_id"]]) !== "1") {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->deleteFlowFeedbackService($data, $feedbackId);
        return $this->returnResult($result);
    }

    /**
     * 【流程签办反馈】获取单个签办反馈
     *
     * @author dingpeng
     *
     * @return [type]              [description]
     */
    public function getFlowFeedbackDetail($feedbackId)
    {
        $result = app($this->flowService)->getFlowFeedbackDetailService($feedbackId, $this->request->all());
        $runId = isset($result["run_id"]) ? $result["run_id"] : "";
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view","run_id" => $runId,"user_id" => $this->userId])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        return $this->returnResult($result);
    }

    /**
     * 【流程公共附件】 保存公共附件
     *
     * @author dingpeng
     *
     * @return [type]                  [description]
     */
    public function postFlowPublicAttachment($runId)
    {
        $data = $this->request->all();
        $data["user_id"] = isset($this->own["user_id"]) ? $this->own["user_id"] : "";
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "handle","run_id" => $runId,"user_id" => $this->userId])) {
            return $this->returnResult(false);
        }
        $result = app($this->flowService)->postFlowPublicAttachmentService($data, $runId);
        return $this->returnResult($result);
    }

    /**
     * 【流程相关文档】 获取相关文档列表
     *
     * @author dingpeng
     *
     * @return [type]                  [description]
     */
    public function getFlowRelatedDocument($runId)
    {
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view","run_id" => $runId,"user_id" => $this->userId])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowRelatedDocumentService($this->request->all(), $runId);
        return $this->returnResult($result);
    }

    /**
     * 【流程相关文档】 保存相关文档
     *
     * @author dingpeng
     *
     * @return [type]                  [description]
     */
    public function addFlowRelatedDocument($runId)
    {
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "handle","run_id" => $runId,"user_id" => $this->userId])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->addFlowRelatedDocumentService($this->request->all(), $runId);
        return $this->returnResult($result);
    }

    /**
     * 【流程子流程】 获取子流程列表
     * 20190319-检查，这路由废弃了，现在办理页面调用的是路由【'flow/run/sun-flow/{run_id}'】
     *
     * @author dingpeng
     *
     * @return [type]                  [description]
     */
    public function getFlowSubflow()
    {
        $result = app($this->flowService)->getFlowSubflowService($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【流程图】 ，获取流程图页面的流程步骤数据--用于流程图
     *
     * @author dingpeng
     *
     * @return [type]                  [description]
     */
    public function getFlowChart($runId)
    {
        $param = $this->request->all();
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view","run_id" => $runId,"user_id" => $this->userId , 'request' =>$param ])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowChartService($runId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程图】 ，获取流程图页面的流程运行步骤数据
     *
     * @param $runId
     * @return \App\EofficeApp\Base\json [type]  [description]
     * @author dingpeng
     *
     */
    public function getFlowRunProcessData($runId)
    {
        $param = $this->request->all();
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view","run_id" => $runId,"user_id" => $this->userId , 'request' =>$param])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowRunProcessData($runId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取某条流程所有节点，获取&判断可流出节点；自由、固定流程都是这个。
     *
     * @apiTitle 获取&判断流程节点
     * @param {string} monitorSubmit 【可选】是否是监控提交的标识，传monitor，表示是监控提交。
     * @param {string} handle_user 【可选】是监控提交的时候，监控人用户ID。
     * @param {int} run_id 【必填】流程运行数据ID。
     * @param {int} flow_process 【必填】当前用户所在节点的节点ID，在流程管理-流程设计-节点设置-节点信息页面可查看节点ID。
     * @param {int} flow_id 【可选】流程ID，在流程管理-流程设计-基本信息页面可查看到此参数。新建流程且不选人提交的时候用到。
     * @paramExample {json} 整体参数示例
     * {
     *  "run_id":"100",
     *  "flow_process":"9",
     * }
     *
     * @success {int} maxNodeFlag 最大节点标识，返回1，表示流程到了最后一个节点，流程可以提交结束
     * @success {string} concourse 需要会签表示，返回concourse，表示不能提交，需要会签
     * @success {object} turn 可以前进的节点
     * @success {object} back 可以退回的节点
     * @successExample {json} 成功返回值示例
     * {
     *   maxNodeFlag : "0",
     *   turn : {
     *       {
     *           node_id: 59,
     *           flow_id: 1,
     *           sort: 0,
     *           process_name: "节点4",
     *           ...其他可流出节点相关的属性
     *       }, {
     *           node_id: 60,
     *           flow_id: 1,
     *           sort: 1,
     *           process_name: "节点5",
     *           ...其他可流出节点相关的属性
     *       }
     *   },
     *   back : {
     *       {
     *           node_id: 50,
     *           flow_id: 1,
     *           sort: 0,
     *           process_name: "节点1",
     *           ...其他可退回节点相关的属性
     *       }, {
     *           node_id: 51,
     *           flow_id: 1,
     *           sort: 1,
     *           process_name: "节点2",
     *           ...其他可退回节点相关的属性
     *       }
     *   },
     * }
     * @return \App\EofficeApp\Base\json
     */
    public function showFlowTransactProcess()
    {
        $data = $this->request->all();
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $data['run_id'] ?? '',
            'user_id' => $this->userId
        ];
        // if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
        //     return $this->returnResult(['code' => ['0x000006', 'common']]);
        // }
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFlowTransactProcess($data,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取某条【固定】流程，某个可以流出的节点的所有办理人信息，自由流程不需要判断，可以提交给所有人。
     *
     * @apiTitle 获取可流出节点办理人信息
     * @param {int} run_id 【必填】流程运行数据ID
     * @param {int} target_process_id 【必填】目标节点ID，在流程管理-流程设计-节点设置-节点信息页面可查看节点ID。
     * @param {int} flow_process 【必填】当前节点的节点ID，在流程管理-流程设计-节点设置-节点信息页面可查看节点ID。
     * @param {int} flow_id 【可选】流程ID，在流程管理-流程设计-基本信息页面可查看到此参数。
     * @paramExample {json} 整体参数示例
     * {
     *  "run_id":"100",
     *  "target_process_id":"10",
     *  "flow_process":"9",
     *  "flow_id":"",
     * }
     *
     * @success {object} default 目标节点，默认办理人信息。包含：[handle]:办理人用户id对象，通过default.handle.user_id获取，[host]:主办人id对象，通过default.host.user_id获取。
     * @success {string} modal 办理人是否可以修改的标识，传readonly表示不可以修改
     * @success {object} scope 目标节点的办理人范围。通过scope.user_id获取。如果是ALL，表示全体范围。
     * @successExample {json} 成功返回值示例
     * {
     *   default : {
     *       handle : {
     *           user_id : {
     *               "admin",
     *               "WV00000002"
     *           }
     *       },
     *       host : ""
     *   },
     *   modal : "readonly",
     *   scope : {
     *       user_id: "ALL"
     *   }
     * }
     * @return \App\EofficeApp\Base\json
     */
    public function showFixedFlowTransactUser()
    {

        $data = $this->request->all();
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $data['run_id'] ?? '',
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFixedFlowTransactUser($data);
        return $this->returnResult($result);
    }

    /**
     * 获取指定节点的默认抄送人
     * @return \App\EofficeApp\Base\json
     * @author jikaixiang
     */
    public function showFixedFlowCopyUser()
    {
        $data = $this->request->all();
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $data['run_id'] ?? '',
            'user_id' => $this->userId,
            'flow_id' => $data['flow_id'] ?? ''
        ];
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFixedFlowCopyUser($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 获取某条流程，某节点办理人总数，如果只有一个办理人，监控提交等直接跳过选择主办人
     *
     * @author miaochenchen
     *
     * @return [type]               [description]
     */
    public function getFlowMaxProcessUserCount()
    {
        $data = $this->request->all();
        if (!isset($data['run_id'])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $flowRunInfo = app($this->flowRunRepository)->getDetail($data['run_id']);
        if (empty($flowRunInfo->creator)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $flowMonitorTurnBackPermission = app($this->flowPermissionService)->getMonitorAllowTypePermission($this->userId, $flowRunInfo->flow_id, $flowRunInfo->creator, ['allow_turn_back', 'allow_end']);
        if (!$flowMonitorTurnBackPermission) {
            // 如果没有当前流程的监控流转权限，提示无权限
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowMaxProcessUserCount($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 获取某条【固定】流程，当前节点的所有办理人信息
     *
     * @author dingpeng
     *
     * @return [type]               [description]
     */
    public function showFixedFlowCurrentUser()
    {
        $result = app($this->flowService)->getFixedFlowCurrentUser($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 固定&自由流程，提交页面提交下一步、提交结束流程
     *
     * @apiTitle 提交流程
    * @param {int} run_id 【必填】流程运行数据ID。
    * @param {int} process_id 【必填】所在步骤ID。
    * @param {string} process_transact_user 【可选】办理人用户ID，逗号拼接的字符串。
    * @param {string} process_host_user 【必填】主办人用户ID，主办人只有一个。
    * @param {int} next_flow_process 【必填】固定流程才需要传的，必填的，目标节点ID
    * @param {int} flow_process 【必填】固定流程才需要传的，当前节点ID。
    * @param {string} process_copy_user 【可选】固定抄送人用户ID，逗号拼接的字符串。
    * @param {array} sonFlowInfo 【可选】待创建的子流程信息。
    * @param {string} flowTurnType 【可选】流程提交类型，可选，传back的时候，用来标识是退回操作。
    * @paramExample {json} 整体参数示例
    * {
    *  "run_id":"100",
    *  "process_id":"1",
    *  "process_transact_user":"admin,WV00000001,WV00000002",
    *  "process_host_user":"admin",
    *  "next_flow_process":"10",
    *  "flow_process":"9"
    * }
    */
    public function flowTurning()
    {
    	$data = $this->request->all();
    	$data['user_id'] = $this->userId;
    	$submitUuid = uniqid('flow_run_submit_');
    	$result = app($this->flowParseService)->postBranchTurning($data, $this->own, false, $submitUuid);
    	return $this->returnResult($result);
    }
    /**
     * 【流程运行】 【提交流程】 固定&自由流程，提交页面经办人提交流程
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function flowTurningOther()
    {
        $data = $this->request->all();
        $data['user_id'] = $this->userId;
        $result = app($this->flowService)->postFlowTurningOther($data, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 【流程运行】 【提交流程】 固定流程批量提交
     *
     * @author miaochenchen
     *
     * @return [type]      [description]
     */
    public function flowMultiTurning()
    {
        $submitUuid = uniqid('flow_run_submit_');
        $result = app($this->flowService)->flowMultiTurning($this->request->all(), $this->own, $submitUuid);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 【提交流程】 固定流程批量结束
     *
     * @author miaochenchen
     *
     * @return [type]      [description]
     */
    public function flowMultiEnd()
    {
        $result = app($this->flowService)->flowMultiEnd($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 获取流程flow_run为主表的所有相关流程运行信息
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function getFlowRunningInfo($runId)
    {
        $data = $this->request->all();
        $verifyPermissionParams = [
            'type'    => 'view',
            'run_id'  => $runId,
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowRunningInfo($runId, $data);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 【流程数据】 获取某条流程解析后的formdata
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function getFlowFormParseData()
    {
        $data = $this->request->all();
        // 文档模块需要调用，后续修改
        // if (!empty($data['runId']) && $data['runId'] > 0) {
        //     $verifyPermissionParams = [
        //         'type'    => 'view',
        //         'run_id'  => $data['runId'] ?? '',
        //         'user_id' => $this->userId
        //     ];
        //     if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
        //         return $this->returnResult(['code' => ['0x000006', 'common']]);
        //     }
        // }
        $result = app($this->flowService)->getFlowFormParseData($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 【流程数据】 文档模块，获取流程信息，展示归档后的流程
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function getFilingDocumentFlowInfo($documentId)
    {
        $result = app($this->flowService)->getFilingDocumentFlowInfo($documentId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 获取某条流程当前步骤是否有主办人，返回1，有主办人，返回0，没有主办人。
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function getFlowProcessHostFlag($runId)
    {
        $data = $this->request->all();
        $data['user_id'] = $this->userId;
        $data['run_id'] = $runId;
        $result = app($this->flowMonitorService)->getFlowProcessHostFlag($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 动态设置某条流程的主办人
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function setFlowProcessHostUser($runId)
    {
        $param           = $this->request->all();
        $param["run_id"] = $runId;
        $flowRunInfo = app($this->flowRunRepository)->getDetail($runId);
        if (empty($flowRunInfo->creator)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $flowMonitorTurnBackPermission = app($this->flowPermissionService)->getMonitorAllowTypePermission($this->userId, $flowRunInfo->flow_id, $flowRunInfo->creator, ['allow_turn_back', 'allow_end']);
        if (!$flowMonitorTurnBackPermission) {
            // 如果没有当前流程的监控流转权限，提示无权限
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $verifyPermissionParams = [
            'type'    => 'view',
            'run_id'  => $runId,
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowRunService)->setHostFlag($param);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 转发
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function doFlowRunForward($runId)
    {
        $data = $this->request->all();
        $data['user_id'] = $this->userId;
        $result = app($this->flowService)->flowRunForwardRealize($runId, $data);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 委托
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function doFlowRunAgent($byAgentUser)
    {
        $result = app($this->flowService)->flowRunAgentRealize($byAgentUser, $this->request->all(), $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 收回
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function doFlowRunTakeBack($runId)
    {
        $data   = $this->request->all();
        $data['user_id'] = isset($this->own['user_id']) ? $this->own['user_id'] : '';
        $result = app($this->flowService)->flowRunTakeBackRealize($runId, $data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 催办
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function doFlowRunLimit()
    {
        $result = app($this->flowService)->flowRunLimitRealize($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 验证签办反馈、公共附件必填
     *
     * @author dingpeng
     *
     * @return [type]      [description]
     */
    public function verifyFlowRunRequired($runId)
    {
        $data = $this->request->all();
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $runId,
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data['user_id'] = $this->userId;
        $result = app($this->flowService)->verifyFlowRunRequired($runId, $data);
        return $this->returnResult($result);
    }

    /**
     * 【流程邮件外发】 流程邮件外发实现
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function sendFlowOutMail()
    {
        $data = $this->request->all();
        $data["user_id"] = isset($this->own["user_id"]) ? $this->own["user_id"] : "";
        $verifyPermissionParams = [
            'type'    => 'handle',
            'run_id'  => $data['run_id'] ?? '',
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->sendFlowOutMail($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程抄送】 新建抄送
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function createFlowCopy()
    {
        $data = $this->request->all();
        $data["copy_user"] = isset($this->own["user_id"]) ? $this->own["user_id"] : "";
        $verifyPermissionParams = [
            'type'    => 'view',
            'run_id'  => $data['run_id'] ?? '',
            'user_id' => $this->userId
        ];
        if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyPermissionParams)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->addFlowCopy($data);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的抄送流程
     *
     * @apiTitle 获取抄送流程
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"copy_id":"desc"}, // 默认按照抄送ID倒序排列
     *    page: 2, // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 40,
     *         "list": [
     *             {
     *                 "copy_id": 1,
     *                 "by_user_id": "admin", // 被抄送用户ID
     *                 "run_id": 26, // 流程运行ID
     *                 "copy_user": "WV00000198", // 抄送用户ID
     *                 "copy_time": 1493021194, // 抄送时间(时间戳)
     *                 "receive_time": "2018-07-03 11:20:48", // 接收时间
     *                 "submit_time": null,
     *                 "copy_type": 1,
     *                 "process_id": 1,
     *                 "flow_id": 14, // 定义流程ID
     *                 "created_at": "2017-04-25 00:06:34",
     *                 "updated_at": "2018-07-03 11:20:48",
     *                 "deleted_at": null,
     *                 "flow_copy_belongs_to_flow_type": {
     *                     "flow_id": 14,
     *                     "flow_name": "采购合同审批流程", // 流程名称
     *                     "flow_name_rules": "",
     *                     "form_id": 28,
     *                     "flow_document": "1",
     *                     "flow_link": 1,
     *                     "flow_type": "1",
     *                     "flow_sort": 4,
     *                     "flow_sequence": "<p>HT[RUN_SEQ5|00001]</p>",
     *                     "flow_sequence_length": 4,
     *                     "flow_sequence_start": 0,
     *                     "flow_noorder": 0,
     *                     "handle_way": 0,
     *                     "countersign": 0,
     *                     "can_edit_flowno": 0,
     *                     "can_edit_flowname": 1,
     *                     "create_user": null,
     *                     "create_dept": null,
     *                     "create_role": null,
     *                     "flow_name_py": "caigouhetongshenpiliucheng",
     *                     "flow_name_zm": "cghtsplc",
     *                     "is_using": 1,
     *                     "hide_running": 0,
     *                     "press_add_hour": null,
     *                     "monitor_user_type": 1,
     *                     "monitor_scope": 0,
     *                     "allow_monitor": 1,
     *                     "allow_view": 1,
     *                     "allow_turn_back": 1,
     *                     "allow_delete": 1,
     *                     "allow_take_back": 1,
     *                     "allow_end": 1,
     *                     "allow_urge": 0,
     *                     "created_at": "2017-04-21 00:34:13",
     *                     "updated_at": "2017-04-21 02:19:56",
     *                     "deleted_at": null
     *                 },
     *                 "flow_copy_has_many_flow_process": [ // 流程节点信息
     *                     {
     *                         "process_name": "提交合同审批单",
     *                         "node_id": 63,
     *                         "flow_id": 14
     *                     },
     *                     ......更多流程节点信息
     *                 ],
     *                 "flow_copy_has_one_flow_run": {
     *                     "run_id": 26,
     *                     "run_name": "采购合同审批流程(2017-04-24 16:06:27:夏静)", // 流程标题
     *                     "run_seq": "<p>HT00002</p>", // 流水号(带格式)
     *                     "current_step": 64, // 当前节点ID
     *                     "max_process_id": 2, // 最新步骤ID
     *                     "instancy_type": 0, // 紧急程度
     *                     "creator": "WV00000198", // 创建人ID
     *                     "create_time": "2017-04-24 16:06:33", // 创建时间
     *                     "flow_run_has_many_flow_run_step": [ // 流程运行步骤数据
     *                         {
     *                             "flow_step_id": 1222,
     *                             "run_id": 26,
     *                             "user_id": "WV00000001",
     *                             "process_id": 2,
     *                             "flow_process": 64,
     *                             "user_run_type": 1,
     *                             "host_flag": 1,
     *                             "process_time": null,
     *                             "transact_time": 1493021194,
     *                             "limit_date": "0000-00-00 00:00:00",
     *                             "is_effect": 1,
     *                             "flow_id": 14,
     *                             "created_at": "-0001-11-30 00:00:00",
     *                             "updated_at": null,
     *                             "deleted_at": null,
     *                             "flow_run_process_has_one_flow_process": {
     *                                 "node_id": 64,
     *                                 "flow_id": 14,
     *                                 "process_id": 0,
     *                                 "sort": 2,
     *                                 "process_name": "上级审批",
     *                                 "process_user": "",
     *                                 "process_item": null,
     *                                 "process_dept": "",
     *                                 "process_role": "",
     *                                 "process_to": "65,63",
     *                                 "process_concourse": 0,
     *                                 "process_transact_type": "0",
     *                                 "process_default_user": "",
     *                                 "process_default_type": "0",
     *                                 "process_default_manage": "",
     *                                 "process_item_view": null,
     *                                 "process_item_capacity": null,
     *                                 "process_item_auto": null,
     *                                 "process_term": null,
     *                                 "process_descript": null,
     *                                 "sub_workflow_ids": null,
     *                                 "run_ways": 0,
     *                                 "process_auto_get_user": "",
     *                                 "process_auto_get_copy_user": null,
     *                                 "get_agency": 0,
     *                                 "process_forward": 0,
     *                                 "process_copy": 2,
     *                                 "process_item_required": null,
     *                                 "end_workflow": null,
     *                                 "flow_outsend": null,
     *                                 "flow_outmail": 0,
     *                                 "process_copy_user": null,
     *                                 "process_copy_dept": null,
     *                                 "process_copy_role": null,
     *                                 "press_add_hour": 1,
     *                                 "flow_outsend_type": 1,
     *                                 "position": "{\"left\":-800,\"top\":-350}",
     *                                 "head_node_toggle": 0,
     *                                 "sun_flow_premise": null,
     *                                 "flow_outsend_toggle": 0,
     *                                 "flow_outsend_timing": 0,
     *                                 "sun_flow_toggle": 0,
     *                                 "handle_user_instant_save": "[\"WV00000001\"]",
     *                                 "created_at": "2017-04-21 00:34:13",
     *                                 "updated_at": "2017-10-17 15:36:51",
     *                                 "deleted_at": null,
     *                                 "flow_run_template_toggle": 0
     *                             }
     *                         },
     *                         ......更多流程运行步骤数据
     *                     ]
     *                 },
     *                 "flow_copy_has_one_user": { // 抄送人用户ID和姓名
     *                     "user_id": "WV00000198",
     *                     "user_name": "王斌"
     *                 },
     *                 "max_process_name": "上级审批" // 最新所在步骤名称
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getFlowCopyList()
    {
        $data = $this->request->all();
        $data['by_user_id'] = $this->userId;
        $result = app($this->flowService)->flowCopyList($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程抄送】 获取抄送流程名称列表;[带查询]
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function getFlowCopyFlowNameList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->flowCopyFlowNameList($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程委托】 新建委托规则
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function createFlowAgencyRule()
    {
        $data = $this->request->all();
        if ($this->userId != 'admin') {
            $byAgentUser = $data['by_agent_id'] ?? '';
            if ($this->userId != $byAgentUser) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->addFlowAgencyRule($data, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 【流程委托】 可以委托的流程的列表，在新建委托时使用
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function canNewAgencyFlowList($userId)
    {
        if ($this->userId != 'admin') {
            if ($userId != $this->userId) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->canAddAgencyFlowList($userId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【流程委托】 收回委托规则
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function tackBackFlowAgencyRule($agencyRuleId)
    {
        $result = app($this->flowService)->recycleFlowAgencyRule($this->request->all(), $agencyRuleId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程委托】 收回全部委托规则[我的委托、被委托、其他委托]
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function tackBackAllOfFlowAgencyRule()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->recycleAllOfFlowAgencyRule($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程委托】 获取委托规则列表;[带查询，可以支持:我的委托规则、被委托规则、其他委托规则]
     *
     * @author dingpeng
     *
     * @return [type]         [description]
     */
    public function getFlowAgencyRuleList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->flowAgencyRuleList($data);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的委托记录(委托记录或被委托记录)
     *
     * @apiTitle 获取委托记录
     * @param {string} agencyType 委托记录类型
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    agencyType: "myAgency", // myAgency：我的委托记录，byAgency：被委托记录
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"transact_time":"desc"}, // 默认按照最后提交时间倒序排列
     *    page: 2, // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 1, // 委托记录或被委托记录数量
     *         "list": [ // 委托记录或被委托列表数据
     *             {
     *                 "run_id": 903, // 流程运行ID
     *                 "process_id": 1,
     *                 "user_id": "WV00000001", // 被委托人
     *                 "receive_time": "2018-08-07 11:10:53",
     *                 "process_time": null,
     *                 "deliver_time": null,
     *                 "saveform_time": null,
     *                 "process_flag": 1,
     *                 "flow_process": 559, // 当前所在节点ID
     *                 "host_flag": 1,
     *                 "limit_date": null,
     *                 "is_remind": "0",
     *                 "by_agent_id": "admin", // 委托人ID
     *                 "flow_agency_id": null,
     *                 "sub_flow_run_ids": null,
     *                 "forward_user_id": null,
     *                 "last_visited_time": "2018-08-07 10:02:05",
     *                 "monitor_submit": null,
     *                 "is_effect": 1,
     *                 "flow_id": 184,
     *                 "created_at": "2018-08-07 10:02:05",
     *                 "updated_at": "2018-08-07 10:02:05",
     *                 "deleted_at": null,
     *                 "is_back": 0,
     *                 "send_back_process": null,
     *                 "send_back_user": null,
     *                 "run_name": "节点提交测试2018-08-07 10:01:39系统管理员", // 流程标题
     *                 "run_name_html": null,
     *                 "run_name_rules": null,
     *                 "attachment_id": null,
     *                 "attachment_name": null,
     *                 "run_seq": "<p>节点提交测试2018080710020076</p>", // 流水号(带格式)
     *                 "run_seq_strip_tags": "节点提交测试2018080710020076", // 流水号(不带格式)
     *                 "create_time": "2018-08-07 10:02:05",
     *                 "link_doc": null,
     *                 "creator": "admin",
     *                 "view_user": null,
     *                 "current_step": 559,
     *                 "transact_time": "2018-08-07 10:02:05",
     *                 "instancy_type": 0,
     *                 "max_process_id": 1,
     *                 "flow_run_process_has_many_flow_process": [ // 流程节点信息（根据当前节点ID获取当前节点名称）
     *                     {
     *                         "process_name": "第一节点",
     *                         "process_id": 0,
     *                         "flow_id": 184,
     *                         "node_id": 559
     *                     },
     *                     {
     *                         "process_name": "第二节点",
     *                         "process_id": 0,
     *                         "flow_id": 184,
     *                         "node_id": 560
     *                     },
     *                     {
     *                         "process_name": "第三节点",
     *                         "process_id": 0,
     *                         "flow_id": 184,
     *                         "node_id": 561
     *                     }
     *                 ],
     *                 "flow_run_process_has_one_user": { // 被委托人ID和姓名
     *                     "user_id": "WV00000001",
     *                     "user_name": "郑晓丽"
     *                 },
     *                 "flow_run_process_has_one_agent_user": { // 委托人ID和姓名
     *                     "user_id": "admin",
     *                     "user_name": "系统管理员"
     *                 },
     *                 "flow_run_process_belongs_to_flow_run": {
     *                     "run_id": 903,
     *                     "run_name": "节点提交测试2018-08-07 10:01:39系统管理员", // 流程标题
     *                     "run_seq": "<p>节点提交测试2018080710020076</p>",
     *                     "max_process_id": 1,
     *                     "current_step": 559,
     *                     "create_time": "2018-08-07 10:02:05",
     *                     "transact_time": "2018-08-07 10:02:05"
     *                 }
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getFlowAgencyRecordList()
    {
        $data = $this->request->all();
        $data['user_id'] = $this->userId;
        $result = app($this->flowService)->flowAgencyRecordList($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的待办事宜
     *
     * @apiTitle 获取待办
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"transact_time":"desc"},     // 默认按照最后提交时间倒序排列
     *    page: 2,                                // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 68, // 待办数量
     *         "list": [    // 待办列表数据
     *             {
     *                 "flow_step_id": 18468,
     *                 "run_id": 694,
     *                 "user_id": "WV00000001",
     *                 "process_id": 2,
     *                 "flow_process": 478,
     *                 "user_run_type": 1,
     *                 "host_flag": 0,
     *                 "process_time": null,
     *                 "transact_time": 1526884093, // 最后提交时间（时间戳）
     *                 "limit_date": "0000-00-00 00:00:00",
     *                 "is_effect": 1,
     *                 "flow_id": 159,
     *                 "created_at": "-0001-11-30 00:00:00",
     *                 "updated_at": "2018-05-21 14:28:13",
     *                 "deleted_at": null,
     *                 "flow_run_step_has_one_user": {
     *                     "user_id": "WV00000001",
     *                     "user_name": "张三"
     *                 },
     *                 "flow_run_process_belongs_to_flow_run": {
     *                     "run_id": 694,
     *                     "run_name": "110", // 流程标题
     *                     "run_name_html": null, // 流程标题（带样式）
     *                     "run_name_rules": null,
     *                     "flow_id": 159,
     *                     "attachment_id": null,
     *                     "attachment_name": null,
     *                     "run_seq": "<p>201805160019</p>", // 流水号（带样式）
     *                     "run_seq_strip_tags": "201805160019", // 流水号（带样式）
     *                     "create_time": "2018-05-16 18:24:36",
     *                     "link_doc": null,
     *                     "creator": "WV00000682", // 流程创建人ID
     *                     "view_user": null,
     *                     "current_step": 0,
     *                     "transact_time": "2018-05-21 14:28:13",
     *                     "instancy_type": 0, // 紧急程度
     *                     "max_process_id": 6,
     *                     "is_effect": 1,
     *                     "created_at": "2018-05-16 18:24:36",
     *                     "updated_at": "2018-05-21 14:28:13",
     *                     "deleted_at": null,
     *                     "flow_run_has_one_flow_type": {
     *                         "flow_id": 159,
     *                         "flow_name": "报销流程", // 流程名称
     *                         "flow_sort": 24,
     *                         "flow_type": "1",
     *                         "form_id": 181,
     *                         "countersign": 0,
     *                         "flow_type_has_one_flow_others": {
     *                             "flow_id": 159,
     *                             "first_node_delete_flow": 0,
     *                             "flow_to_doc": 0,
     *                             "file_folder_id": 0
     *                         },
     *                         "flow_type_belongs_to_flow_sort": {
     *                             "title": "报销", // 流程分类
     *                             "id": 24
     *                         }
     *                     },
     *                     "flow_run_has_one_user": {
     *                         "user_id": "WV00000682",
     *                         "user_name": "李四" // 流程创建人姓名
     *                     }
     *                 },
     *                 "flow_run_process_has_one_flow_process": {
     *                     "node_id": 478,
     *                     "flow_id": 159,
     *                     "process_id": 0,
     *                     "process_name": "经理审批", // 当前步骤名称
     *                     "head_node_toggle": 0,
     *                     "end_workflow": null,
     *                     "process_transact_type": "0",
     *                     "press_add_hour": null,
     *                     "process_concourse": 0
     *                 },
     *                 "isBack": 0, // 是否是退回流程
     *                 "sendBackUser": "",
     *                 "sendBackUserName": "",
     *                 "last_process_info": { // 上节点信息（主办人）
     *                     "run_id": 694,
     *                     "process_id": 5,
     *                     "user_id": "admin",
     *                     "receive_time": "2018-05-16 18:26:46",
     *                     "process_time": "2018-05-16 18:29:08",
     *                     "deliver_time": "2018-05-16 18:30:29",
     *                     "saveform_time": null,
     *                     "process_flag": 4,
     *                     "flow_process": 481,
     *                     "host_flag": 1,
     *                     "limit_date": "0000-00-00 00:00:00",
     *                     "is_remind": "0",
     *                     "by_agent_id": null,
     *                     "flow_agency_id": null,
     *                     "sub_flow_run_ids": "",
     *                     "forward_user_id": null,
     *                     "last_visited_time": "2018-05-16 18:30:08",
     *                     "monitor_submit": null,
     *                     "is_effect": 1,
     *                     "flow_id": 159,
     *                     "created_at": "2018-05-16 18:26:46",
     *                     "updated_at": "2018-05-21 14:37:13",
     *                     "deleted_at": null,
     *                     "is_back": 0,
     *                     "send_back_process": 0,
     *                     "send_back_user": "",
     *                     "flow_run_process_has_one_user": {
     *                         "user_id": "admin",
     *                         "user_name": "系统管理员"
     *                     },
     *                     "flow_run_process_has_one_monitor_submit_user": null
     *                 },
     *                 "limitInfo": { // 催办信息
     *                     "limitLevelFlag": "",
     *                     "limitIntervalString": "",
     *                     "limitSponsorInfo": []
     *                 }
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function teedToDoList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getTeedToDoList($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的已办事宜
     *
     *  @apiTitle 获取已办
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"transact_time":"desc"}, // 默认按照最后提交时间倒序排列
     *    page: 2, // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 36, // 已办事宜数量
     *         "list": [    // 已办事宜列表数据
     *             {
     *                 "flow_step_id": 27429,
     *                 "run_id": 804, // 流程运行ID
     *                 "user_id": "admin",
     *                 "process_id": 1,
     *                 "flow_process": 539,
     *                 "user_run_type": 2,
     *                 "host_flag": 1,
     *                 "process_time": "2018-05-23 15:15:08",
     *                 "transact_time": 1527059708, // 最后提交时间（时间戳）
     *                 "limit_date": null,
     *                 "is_effect": 1,
     *                 "flow_id": 181,
     *                 "created_at": "-0001-11-30 00:00:00",
     *                 "updated_at": null,
     *                 "deleted_at": null,
     *                 "flow_run_step_has_one_user": {
     *                     "user_id": "admin",
     *                     "user_name": "系统管理员"
     *                 },
     *                 "flow_run_process_belongs_to_flow_run": {
     *                     "run_id": 804,
     *                     "run_name": "流程测试--mll2018-05-23 15:14:52", // 流程标题
     *                     "run_name_html": null,
     *                     "run_name_rules": null,
     *                     "flow_id": 181,
     *                     "attachment_id": null,
     *                     "attachment_name": null,
     *                     "run_seq": "<p>2018052315150005</p>", // 带格式的流水号
     *                     "run_seq_strip_tags": "2018052315150005", // 不带格式的流水号
     *                     "create_time": "2018-05-23 15:15:08",
     *                     "link_doc": null,
     *                     "creator": "admin", // 创建人ID
     *                     "view_user": null,
     *                     "current_step": 540, // 当前节点ID
     *                     "transact_time": "2018-05-23 15:25:47",
     *                     "instancy_type": 0, // 紧急程度
     *                     "max_process_id": 2,
     *                     "is_effect": 1,
     *                     "created_at": "2018-05-23 15:15:08",
     *                     "updated_at": "2018-05-23 15:25:47",
     *                     "deleted_at": null,
     *                     "flow_run_has_one_flow_type": {
     *                         "flow_id": 181, // 定义流程ID
     *                         "flow_name": "流程测试--mll", // 流程名称
     *                         "flow_sort": 14,
     *                         "flow_type": "1",
     *                         "form_id": 213,
     *                         "countersign": 0,
     *                         "handle_way": 0,
     *                         "flow_type_has_one_flow_others": {
     *                             "flow_id": 181,
     *                             "first_node_delete_flow": 0,
     *                             "flow_to_doc": 0,
     *                             "file_folder_id": null,
     *                             "flow_submit_hand_remind_toggle": 0
     *                         },
     *                         "flow_type_belongs_to_flow_sort": {
     *                             "title": "测试-mll", // 流程分类名称
     *                             "id": 14
     *                         }
     *                     },
     *                     "flow_run_has_one_user": { // 流程创建人ID和姓名
     *                         "user_id": "admin",
     *                         "user_name": "系统管理员"
     *                     }
     *                 },
     *                 "flow_run_process_has_one_flow_process": {
     *                     "node_id": 539,
     *                     "flow_id": 181,
     *                     "process_id": 0,
     *                     "process_name": "起始节点",
     *                     "head_node_toggle": 1,
     *                     "end_workflow": null,
     *                     "process_transact_type": "0",
     *                     "press_add_hour": null,
     *                     "process_concourse": 0
     *                 },
     *                 "max_process_id": 2, // 最新步骤ID
     *                 "max_process_name": "新节点1", // 最新步骤名称
     *                 "current_handle_user_name": "苏果", // 当前办理人姓名
     *                 "current_handle_user_id": { // 当前办理人姓名和ID
     *                     "苏果": [
     *                         "WV00000022",
     *                         1
     *                     ]
     *                 },
     *                 "take_back_flag": 1,
     *                 "limit_button_flag": 1
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function alreadyDoList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $data['user_info'] = $this->own;
        $result = app($this->flowService)->getAlreadyDoList($data);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的办结事宜
     *
     * @apiTitle 获取办结
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"transact_time":"desc"}, // 默认按照最后提交时间倒序排列
     *    page: 2, // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 53, // 办结事宜数量
     *         "list": [ // 办结事宜列表数据
     *             {
     *                 "flow_step_id": 27536,
     *                 "run_id": 815, // 流程运行ID
     *                 "user_id": "admin",
     *                 "process_id": 2,
     *                 "flow_process": 423,
     *                 "user_run_type": 3,
     *                 "host_flag": 1,
     *                 "process_time": "2018-05-28 16:39:48",
     *                 "transact_time": 1530676112, // 最后提交时间(时间戳)
     *                 "limit_date": "0000-00-00 00:00:00",
     *                 "is_effect": 1,
     *                 "flow_id": 132, // 定义流程ID
     *                 "created_at": "-0001-11-30 00:00:00",
     *                 "updated_at": "2018-07-04 11:48:32",
     *                 "deleted_at": null,
     *                 "flow_run_step_has_one_user": {
     *                     "user_id": "admin",
     *                     "user_name": "系统管理员"
     *                 },
     *                 "flow_run_process_belongs_to_flow_run": {
     *                     "run_id": 815,
     *                     "run_name": "出差外发1(系统管理员:2018-05-25 10:38:01)", // 流程标题
     *                     "run_name_html": null,
     *                     "run_name_rules": null,
     *                     "flow_id": 132,
     *                     "attachment_id": null,
     *                     "attachment_name": null,
     *                     "run_seq": "<p>0003201805251038系统管理员</p>", // 流水号(带格式)
     *                     "run_seq_strip_tags": "0003201805251038系统管理员", // 流水号(不带格式)
     *                     "create_time": "2018-05-25 10:38:35",
     *                     "link_doc": null,
     *                     "creator": "admin", // 创建人ID
     *                     "view_user": null,
     *                     "current_step": 0,
     *                     "transact_time": "2018-07-04 11:48:32", // 最后提交时间
     *                     "instancy_type": 0,
     *                     "max_process_id": 2,
     *                     "is_effect": 1,
     *                     "created_at": "2018-05-25 10:38:35",
     *                     "updated_at": "2018-07-04 11:48:32",
     *                     "deleted_at": null,
     *                     "flow_run_has_one_flow_type": {
     *                         "flow_id": 132,
     *                         "flow_name": "出差外发1", // 流程名称
     *                         "flow_sort": 12,
     *                         "flow_type": "1",
     *                         "form_id": 115,
     *                         "countersign": 0,
     *                         "handle_way": 0,
     *                         "flow_type_has_one_flow_others": {
     *                             "flow_id": 132,
     *                             "first_node_delete_flow": 0,
     *                             "flow_to_doc": 0,
     *                             "file_folder_id": null,
     *                             "flow_submit_hand_remind_toggle": 0
     *                         },
     *                         "flow_type_belongs_to_flow_sort": {
     *                             "title": "外发流程", // 流程分类名称
     *                             "id": 12
     *                         }
     *                     },
     *                     "flow_run_has_one_user": { // 流程创建人ID和姓名
     *                         "user_id": "admin",
     *                         "user_name": "系统管理员"
     *                     }
     *                 },
     *                 "flow_run_process_has_one_flow_process": {
     *                     "node_id": 423,
     *                     "flow_id": 132,
     *                     "process_id": 0,
     *                     "process_name": "New node",
     *                     "head_node_toggle": 0,
     *                     "end_workflow": null,
     *                     "process_transact_type": "0",
     *                     "press_add_hour": null,
     *                     "process_concourse": 0
     *                 },
     *                 "max_process_name": "New node" // 最新步骤名称
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function finishedList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFinishedList($data);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的我的请求
     *
     * @apiTitle 获取我的请求
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"transact_time":"desc"}, // 默认按照最后提交时间倒序排列
     *    page: 2, // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 197, // 我的请求数量
     *         "list": [ // 我的请求列表数据
     *             {
     *                 "run_id": 903, // 流程运行ID
     *                 "run_name": "节点提交测试2018-08-07 10:01:39系统管理员", // 流程标题
     *                 "run_name_html": null,
     *                 "run_name_rules": null,
     *                 "flow_id": 184,
     *                 "attachment_id": null,
     *                 "attachment_name": null,
     *                 "run_seq": "<p>节点提交测试2018080710020076</p>", // 流水号(带格式)
     *                 "run_seq_strip_tags": "节点提交测试2018080710020076", // 流水号(不带格式)
     *                 "create_time": "2018-08-07 10:02:05", // 创建时间
     *                 "link_doc": null,
     *                 "creator": "admin", // 创建人ID
     *                 "view_user": null,
     *                 "current_step": 559,
     *                 "transact_time": "2018-08-07 10:02:05", // 最后提交时间
     *                 "instancy_type": 0, // 紧急程度
     *                 "max_process_id": 1,
     *                 "is_effect": 1,
     *                 "created_at": "2018-08-07 10:02:05",
     *                 "updated_at": "2018-08-07 10:02:05",
     *                 "deleted_at": null,
     *                 "flow_run_has_one_flow_type": {
     *                     "flow_id": 184, // 定义流程ID
     *                     "flow_name": "节点提交测试", // 流程名称
     *                     "flow_name_rules": "", // 流程标题规则
     *                     "form_id": 216, // 表单ID
     *                     "flow_document": "1",
     *                     "flow_link": 1,
     *                     "flow_type": "1",
     *                     "flow_sort": 26,
     *                     "flow_sequence": "<p>[FLOW_NAME][YEAR][MONTH][DATE][HOUR][MINUTE][RUN_SEQ4|0000]</p>", // 流水号规则
     *                     "flow_sequence_length": 4,
     *                     "flow_sequence_start": 0,
     *                     "flow_noorder": 0,
     *                     "handle_way": 0,
     *                     "countersign": 0,
     *                     "can_edit_flowno": 1,
     *                     "can_edit_flowname": 1,
     *                     "create_user": null,
     *                     "create_dept": null,
     *                     "create_role": null,
     *                     "flow_name_py": "jiediantijiaoceshi",
     *                     "flow_name_zm": "jdtjcs",
     *                     "is_using": 1,
     *                     "hide_running": 0,
     *                     "press_add_hour": null,
     *                     "monitor_user_type": 1,
     *                     "monitor_scope": 0,
     *                     "allow_monitor": 1,
     *                     "allow_view": 1,
     *                     "allow_turn_back": 1,
     *                     "allow_delete": 1,
     *                     "allow_take_back": 1,
     *                     "allow_end": 1,
     *                     "allow_urge": 1,
     *                     "created_at": "2018-05-25 18:00:48",
     *                     "updated_at": "2018-08-06 14:07:01",
     *                     "deleted_at": null
     *                 },
     *                 "flow_run_has_many_flow_run_process": [ // 流程运行步骤数据
     *                     {
     *                         "run_id": 903,
     *                         "user_id": "admin",
     *                         "process_id": 1,
     *                         "flow_process": 559,
     *                         "host_flag": 1,
     *                         "process_flag": 2,
     *                         "process_time": "2018-08-07 10:02:05", // 查看时间
     *                         "saveform_time": null, // 经办人提交时间
     *                         "deliver_time": null, // 提交时间
     *                         "flow_run_process_has_one_flow_process": {
     *                             "node_id": 559,
     *                             "process_name": "第一节点"
     *                         }
     *                     }
     *                 ],
     *                 "flow_run_has_many_flow_run_process_relate_current_user": {
     *                     "run_id": 903,
     *                     "process_id": 1,
     *                     "user_id": "admin",
     *                     "receive_time": "2018-08-07 10:02:05",
     *                     "process_time": "2018-08-07 10:02:05",
     *                     "deliver_time": null,
     *                     "saveform_time": null,
     *                     "process_flag": 2,
     *                     "flow_process": 559,
     *                     "host_flag": 1,
     *                     "limit_date": null,
     *                     "is_remind": "0",
     *                     "by_agent_id": null,
     *                     "flow_agency_id": null,
     *                     "sub_flow_run_ids": null,
     *                     "forward_user_id": null,
     *                     "last_visited_time": "2018-08-07 10:02:05",
     *                     "monitor_submit": null,
     *                     "is_effect": 1,
     *                     "flow_id": 184,
     *                     "created_at": "2018-08-07 10:02:05",
     *                     "updated_at": "2018-08-07 10:02:05",
     *                     "deleted_at": null,
     *                     "is_back": 0,
     *                     "send_back_process": null,
     *                     "send_back_user": null
     *                 },
     *                 "max_process_name": "第一节点" // 最新所在步骤名称
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function myRequestList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $data['user_info'] = $this->own;
        $result = app($this->flowService)->getMyRequestList($data);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的流程监控
     *
     * @apiTitle 获取流程监控
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"transact_time":"desc"},     // 默认按照最后提交时间倒序排列
     *    page: 2,                                // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 138, // 流程监控数量
     *         "list": [ // 流程监控列表数据
     *             {
     *                 "run_id": 903, // 流程运行ID
     *                 "run_name": "节点提交测试2018-08-07 10:01:39系统管理员", // 流程标题
     *                 "run_name_html": null,
     *                 "run_name_rules": null,
     *                 "flow_id": 184,
     *                 "attachment_id": null,
     *                 "attachment_name": null,
     *                 "run_seq": "<p>节点提交测试2018080710020076</p>", // 流水号(带格式)
     *                 "run_seq_strip_tags": "节点提交测试2018080710020076", // 流水号(不带格式)
     *                 "create_time": "2018-08-07 10:02:05",
     *                 "link_doc": null,
     *                 "creator": "admin", // 创建人ID
     *                 "view_user": null,
     *                 "current_step": 559, // 当前所在节点ID
     *                 "transact_time": "2018-08-07 10:02:05",
     *                 "instancy_type": 0, // 紧急程度ID
     *                 "max_process_id": 1, // 当前所在步骤ID
     *                 "is_effect": 1,
     *                 "created_at": "2018-08-07 10:02:05",
     *                 "updated_at": "2018-08-07 10:02:05",
     *                 "deleted_at": null,
     *                 "flow_run_has_many_flow_run_process": [ // 流程运行步骤信息
     *                     {
     *                         "run_id": 903,
     *                         "process_id": 1,
     *                         "user_id": "WV00000001", // 办理人ID
     *                         "receive_time": "2018-08-07 11:10:53",
     *                         "process_time": null,
     *                         "deliver_time": null,
     *                         "saveform_time": null,
     *                         "process_flag": 1,
     *                         "flow_process": 559,
     *                         "host_flag": 1,
     *                         "limit_date": null,
     *                         "is_remind": "0",
     *                         "by_agent_id": "admin",
     *                         "flow_agency_id": null,
     *                         "sub_flow_run_ids": null,
     *                         "forward_user_id": null,
     *                         "last_visited_time": "2018-08-07 10:02:05",
     *                         "monitor_submit": null,
     *                         "is_effect": 1,
     *                         "flow_id": 184,
     *                         "created_at": "2018-08-07 10:02:05",
     *                         "updated_at": "2018-08-07 11:10:53",
     *                         "deleted_at": null,
     *                         "is_back": 0,
     *                         "send_back_process": null,
     *                         "send_back_user": null,
     *                         "flow_run_process_has_one_user": { // 办理人信息
     *                             "user_id": "WV00000001",
     *                             "user_name": "郑晓丽"
     *                         },
     *                         "flow_run_process_has_one_user_system_info": {
     *                             "user_id": "WV00000001",
     *                             "user_status": 1 // 办理人在职或其他状态
     *                         },
     *                         "flow_run_process_has_one_flow_process": {
     *                             "node_id": 559,
     *                             "process_name": "第一节点",
     *                             "end_workflow": null,
     *                             "head_node_toggle": 1
     *                         }
     *                     }
     *                 ],
     *                 "flow_run_has_one_flow_type": {
     *                     "flow_id": 184, // 定义流程ID
     *                     "flow_name": "节点提交测试", // 流程名称
     *                     "flow_name_rules": "",
     *                     "form_id": 216,
     *                     "flow_document": "1",
     *                     "flow_link": 1,
     *                     "flow_type": "1",
     *                     "flow_sort": 26,
     *                     "flow_sequence": "<p>[FLOW_NAME][YEAR][MONTH][DATE][HOUR][MINUTE][RUN_SEQ4|0000]</p>",
     *                     "flow_sequence_length": 4,
     *                     "flow_sequence_start": 0,
     *                     "flow_noorder": 0,
     *                     "handle_way": 0,
     *                     "countersign": 0,
     *                     "can_edit_flowno": 1,
     *                     "can_edit_flowname": 1,
     *                     "create_user": null,
     *                     "create_dept": null,
     *                     "create_role": null,
     *                     "flow_name_py": "jiediantijiaoceshi",
     *                     "flow_name_zm": "jdtjcs",
     *                     "is_using": 1,
     *                     "hide_running": 0,
     *                     "press_add_hour": null,
     *                     "monitor_user_type": 1,
     *                     "monitor_scope": 0,
     *                     "allow_monitor": 1,
     *                     "allow_view": 1,
     *                     "allow_turn_back": 1,
     *                     "allow_delete": 1,
     *                     "allow_take_back": 1,
     *                     "allow_end": 1,
     *                     "allow_urge": 1,
     *                     "created_at": "2018-05-25 18:00:48",
     *                     "updated_at": "2018-08-06 14:07:01",
     *                     "deleted_at": null,
     *                     "flow_type_has_one_flow_others": {
     *                         "flow_id": 184,
     *                         "flow_to_doc": 0,
     *                         "file_folder_id": 0,
     *                         "flow_send_back_submit_method": 0,
     *                         "alow_select_handle": 1,
     *                         "flow_send_back_required": 0,
     *                         "first_node_delete_flow": 1,
     *                         "flow_submit_hand_remind_toggle": 0
     *                     }
     *                 },
     *                 "flow_run_has_one_user": {
     *                     "user_id": "admin",
     *                     "user_name": "系统管理员"
     *                 },
     *                 "maxHostFlag": "1",
     *                 "can_view": 0, // 当前用户是否有查看此流程权限 0没有 1有
     *                 "max_process_name": "第一节点", // 最新所在步骤名称
     *                 "end_workflow": null,
     *                 "head_node_toggle": 1,
     *                 "is_back": 0,
     *                 "send_back_user": "",
     *                 "send_back_process": "",
     *                 "process_id": "",
     *                 "handle_user": "",
     *                 "back_transact_process_count": 0,
     *                 "own_take_back_flag": 0,
     *                 "take_back_flag": 0,
     *                 "un_handle_user_name": "郑晓丽", // 最新步骤未办理人姓名
     *                 "un_handle_user_id": { // 最新步骤未办理人ID和姓名
     *                     "郑晓丽": [
     *                         "WV00000001",
     *                         1
     *                     ]
     *                 },
     *                 "flow_process": 559,
     *                 "has_received": 1,
     *                 "current_user_in_un_handle_user_flag": 0 // 判断当前用户是否在未办理人中1在0不在
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function monitorList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getMonitorList($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取某个用户的超时查询
     *
     *  @apiTitle 获取超时查询
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *    autoFixPage: 1,
     *    limit: 10,
     *    order_by: {"transact_time":"desc"},     // 默认按照最后提交时间倒序排列
     *    page: 2,                                // page为0时获取全部
     *    search: {"run_name":['流程标题',"like"]} // 流程标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 694, // 超时查询数量
     *         "list": [ // 超时查询列表数据
     *             {
     *                 "run_id": 820, // 流程运行ID
     *                 "process_id": 2,
     *                 "user_id": "admin",
     *                 "receive_time": "2018-07-03 11:36:22",
     *                 "process_time": null,
     *                 "deliver_time": null,
     *                 "saveform_time": null,
     *                 "process_flag": 1,
     *                 "flow_process": 2, // 所在节点ID
     *                 "host_flag": 0,
     *                 "limit_date": "2018-07-03 12:36:00", // 催办时间
     *                 "is_remind": "0",
     *                 "by_agent_id": null,
     *                 "flow_agency_id": null,
     *                 "sub_flow_run_ids": null,
     *                 "forward_user_id": null,
     *                 "last_visited_time": null,
     *                 "monitor_submit": null,
     *                 "is_effect": 1,
     *                 "flow_id": 91,
     *                 "created_at": "2018-07-03 11:36:22",
     *                 "updated_at": "2018-07-03 11:36:22",
     *                 "deleted_at": null,
     *                 "is_back": 0,
     *                 "send_back_process": null,
     *                 "send_back_user": null,
     *                 "flow_run_process_belongs_to_flow_run": {
     *                     "run_id": 820,
     *                     "run_name": "2018/06/21 14/45/24系统管理员   ", // 流程标题
     *                     "run_name_html": null,
     *                     "run_name_rules": null,
     *                     "flow_id": 91,
     *                     "attachment_id": null,
     *                     "attachment_name": null,
     *                     "run_seq": "<p>00072018062114</p>", // 流水号(带格式)
     *                     "run_seq_strip_tags": "00072018062114", // 流水号(不带格式)
     *                     "create_time": "2018-06-21 14:45:24",
     *                     "link_doc": null,
     *                     "creator": "admin", // 创建人ID
     *                     "view_user": null,
     *                     "current_step": 2,
     *                     "transact_time": "2018-07-03 11:36:22",
     *                     "instancy_type": 0,
     *                     "max_process_id": 2,
     *                     "is_effect": 1,
     *                     "created_at": "2018-06-21 14:45:24",
     *                     "updated_at": "2018-07-03 11:36:22",
     *                     "deleted_at": null
     *                 },
     *                 "flow_run_process_has_one_user": {
     *                     "user_id": "admin",
     *                     "user_name": "系统管理员"
     *                 },
     *                 "flow_run_process_has_one_user_system_info": {
     *                     "user_id": "admin",
     *                     "user_status": 1
     *                 },
     *                 "flow_run_process_has_many_flow_process": [
     *                     // 流程运行步骤信息(根据最新所在节点ID来这里去最新步骤名称)
     *                 ],
     *                 "flow_run_process_belongs_to_flow_type": {
     *                     "flow_id": 91,
     *                     "flow_name": "a_hongxia_free"
     *                 },
     *                 "hostFlagString": "经办人", // 办理人员类型
     *                 "processTypeString": "未查看", // 办理状态
     *                 "overTimeString": "34天22小时59分钟7秒" // 超时时间
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function overtimeList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getOvertimeList($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取流程查询列表
     *
     * @apiTitle 获取流程查询列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} formExportParams 导出字段条件
     * @param {json} formSearchParams 查询条件
     * @param {json} search 查询条件
     * @param {json} flow_module_factory 如果需要查询表单数据详情，需要传递此参数，参数值为1。
     * @param {json} form_id 表单ID，如果需要查询表单数据详情，需要传递此参数，在流程管理-流程设计-基本信息页面可查看到对应流程的表单ID。
     *
     * @paramExample {string} 参数示例
     * {
     *     "autoFixPage": 1,
     *     "limit": 10,
     *     "page": 1,
     *     "order_by": {"transact_time": "desc"}, // 默认按照最后提交时间倒序
     *     "formExportParams": {
     *         "DATA_48": "单行文本框_48" // 导出字段条件(仅查询的时候这个参数值设置为空json)
     *     },
     *     "formSearchParams": { // 表单控件查询条件(需要传入控件属性等条件，如果仅需要基础的查询，比参数值设置为空json)
     *         "DATA_48": {
     *             "control": {
     *                 "control_attribute": {
     *                     ...控件属性
     *                 },
     *                 "control_parent_id": "", // 控件父级ID
     *                 "control_type": "text" // 控件类型
     *             },
     *             "export": "1", // 字段是否导出
     *             "relation": "8", // 关联关系
     *             "search": "测试查询关键字", // 查询关键字
     *         }
     *     },
     *     "search": { // 基础查询条件
     *         "creator": [ // 创建人
     *             ["admin"], "in" // 多个用户示例["WV00000001", "WV00000002",...], "in"
     *         ],
     *         "flow_sort": [26], // 流程分类ID
     *         "flow_id": [184], // 定义流程ID
     *         "current_step": ["0"], // 流程状态 已完成"current_step": ["0"]、执行中"current_step":["0","!="]
     *         "instancy_type": ["0"], // 紧急程度ID
     *         "run_seq_strip_tags": ["123", "like"], // 流水号
     *         "run_name": ["测试标题", "like"], // 流程标题
     *         "start_date1": ["2018-07-01"], // 开始日期起始日期
     *         "start_date2": ["2018-08-01"], // 开始日期结束日期
     *         "end_date1": ["2018-07-01"], // 结束日期起始日期
     *         "end_date2": ["2018-08-01"] // 结束日期结束日期
     *     },
     *     "flow_module_factory": 1,
     *     "form_id": 123
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 73, // 流程查询数量
     *         "list": [ // 流程查询列表数据
     *             {
     *                 "run_id": 903, // 流程运行ID
     *                 "run_name": "节点提交测试2018-08-07 10:01:39系统管理员", // 流程标题
     *                 "run_name_html": null,
     *                 "run_name_rules": null,
     *                 "flow_id": 184, // 定义流程ID
     *                 "attachment_id": null,
     *                 "attachment_name": null,
     *                 "run_seq": "<p>节点提交测试2018080710020076</p>", // 流水号(带格式)
     *                 "run_seq_strip_tags": "节点提交测试2018080710020076", // 流水号(不带格式)
     *                 "create_time": "2018-08-07 10:02:05", // 创建时间
     *                 "link_doc": null,
     *                 "creator": "admin", // 创建人ID
     *                 "view_user": null,
     *                 "current_step": 559, // 当前所在节点ID
     *                 "transact_time": "2018-08-07 10:02:05", // 最后提交时间
     *                 "instancy_type": 0, // 紧急程度ID
     *                 "max_process_id": 1, // 最新步骤ID
     *                 "is_effect": 1,
     *                 "created_at": "2018-08-07 10:02:05",
     *                 "updated_at": "2018-08-07 10:02:05",
     *                 "deleted_at": null,
     *                 "flow_run_has_one_user": { // 流程创建人ID和姓名
     *                     "user_id": "admin",
     *                     "user_name": "系统管理员"
     *                 },
     *                 "flow_run_has_one_flow_type": {
     *                     "flow_id": 184,
     *                     "flow_name": "节点提交测试", // 流程名称
     *                     "allow_monitor": 1,
     *                     "flow_type_has_many_manage_user": [
     *                         {
     *                             "flow_id": 184,
     *                             "user_id": "WV00000002"
     *                         },
     *                         {
     *                             "flow_id": 184,
     *                             "user_id": "WV00000008"
     *                         },
     *                         {
     *                             "flow_id": 184,
     *                             "user_id": "admin"
     *                         }
     *                     ],
     *                     "flow_type_has_one_flow_others": {
     *                         "flow_id": 184,
     *                         "first_node_delete_flow": 1
     *                     }
     *                 },
     *                 "can_view": 0, // 判断是否有查看流程权限0、没有；1、有
     *                 "max_process_name": "第一节点", // 最新所在步骤名称
     *                 "head_node_toggle": 1,
     *                 "take_back_flag": 0,
     *                 "has_monitor": 1
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function flowSearchList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFlowSearchList($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程列表】 获取流程动态信息控件历史流程列表
     *
     * @author miaochenchen
     *
     * @since 2019-12-10
     *
     * @return [type]       [description]
     */
    public function getFlowDynamicInfoHistoryList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFlowDynamicInfoHistoryList($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程列表】 获取流程选择器列表
     *
     * @author lixuanxuan
     *
     * @return [type]       [description]
     */
    public function flowSelectorList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFlowSelectorList($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程类别列表】 获取待办事宜-流程类别列表
     *
     * @author dingpeng
     *
     * @return [type]       [description]
     */
    public function teedToDoFlowSortList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getTeedToDoFlowSortList($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程类别列表】 获取已办事宜-流程类别列表
     *
     * @author dingpeng
     *
     * @return [type]       [description]
     */
    public function alreadyDoFlowSortList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getAlreadyDoFlowSortList($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程类别列表】 获取办结事宜-流程类别列表
     *
     * @author dingpeng
     *
     * @return [type]       [description]
     */
    public function finishedFlowSortList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getFinishedFlowSortList($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程类别列表】 获取我的请求-流程类别列表
     *
     * @author dingpeng
     *
     * @return [type]       [description]
     */
    public function myRequestFlowSortList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getMyRequestFlowSortList($data);
        return $this->returnResult($result);
    }

    /**
     * 【流程类别列表】 获取流程监控-流程类别列表
     *
     * @author dingpeng
     *
     * @return [type]       [description]
     */
    public function monitorFlowSortList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getMonitorFlowSortList($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程类别列表】 获取超时查询-流程类别列表
     *
     * @author dingpeng
     *
     * @return [type]       [description]
     */
    public function overtimeFlowSortList()
    {
        $data = $this->request->all();
        $data["user_id"] = $this->userId;
        $result = app($this->flowService)->getOvertimeFlowSortList($data ,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 获取流程类别列表
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowSortList()
    {
        $result = app($this->flowService)->getFlowSortListService($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 返回按流程分类分组的流程列表
     * @return array
     */
    public function getFlowListGroupByFlowSort()
    {
        $result = app($this->flowService)->getFlowListGroupByFlowSort($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 新建定义流程分类
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 定义流程分类数据
     */
    public function createFlowSort()
    {
        $data   = $this->request->all();
        $result = app($this->flowService)->createFlowSort($data);
        return $this->returnResult($result);
    }

    /**
     * 编辑定义流程分类
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 定义流程分类数据
     */
    public function editFlowSort($id)
    {
        $data   = $this->request->all();
        $result = app($this->flowService)->editFlowSort($data, $id);
        return $this->returnResult($result);
    }
    /**
     * 删除定义流程分类
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    public function deleteFlowSort($id)
    {
        $result = app($this->flowService)->deleteFlowSort($id);
        return $this->returnResult($result);
    }

    /**
     * 获取定义流程分类详情
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 定义流程分类结果
     */
    public function getFlowSortDetail($id)
    {
        if ($id == 0  || empty(app($this->flowSortRepository)->getDetail($id)) ) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowSortDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 获取流程表单列表
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowForm()
    {
        $result = app($this->flowService)->getFlowFormService($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 升级10.0的表单为10.5版本的（为了兼容10.0导入的表单）
     *
     * @author miaochenchen
     *
     * @since  2019-08-12 创建
     *
     * @return array
     */
    public function updateFormHtml()
    {
        $result = app($this->flowFormService)->updateFormHtml($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新建流程表单
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 流程表单数据
     */
    public function createFlowForm()
    {
        $data   = $this->request->all();
        if ($data['form_sort'] != 0) {
            $powerList = app($this->flowService)->getPermissionFlowFormSortList($this->own);
            if ($powerList) {
                $powerList = $powerList->pluck('id')->toArray();
            } else {
                $powerList = [];
            }
            if (!in_array($data['form_sort'], $powerList)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->createFlowForm($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑流程表单
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 流程表单数据
     */
    public function editFlowForm($id)
    {
        // 服务本身已经验证权限
        $data   = $this->request->all();
        $result = app($this->flowService)->editFlowForm($data, $id, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除流程表单
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    public function deleteFlowForm($id)
    {
        foreach (explode(',', trim($id, ",")) as $key => $formIdString) {
            if (!app($this->flowPermissionService)->verifyFormSettingPermission($formIdString, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->deleteFlowForm($id);
        return $this->returnResult($result);
    }

    /**
     * 获取流程表单详情
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 定义流程分类结果
     */
    public function getFlowFormDetail($id)
    {
    	// $id = 104;
    	// $formControl =  app($this->flowFormService)->changeFormControlStructure($id);
        $result = app($this->flowService)->getFlowFormDetail($id, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 根据 flowId 获取正在运行中的流程数据
     * @param $flowId
     * @return array
     */
    public function getFlowRuningCounts($flowId)
    {
        $result = app($this->flowRunService)->getFlowRuningCounts($flowId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 复制流程表单
     * @auther  lixuanxuan
     * @since  2018-11-19 创建
     * @param  $formId
     * @return  json
     */
    public function copyFlowForm($formId)
    {
        // 服务本身已经验证权限
        $result = app($this->flowService)->copyFlowForm($formId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】 【流程表单】 根据综合参数，获取流程表单详情，用在解析 节点模板/归档模板/打印模板
     * @return [type] [description]
     */
    public function getFlowTemplateFormDetail()
    {
        $result = app($this->flowService)->getFlowTemplateFormDetail($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】【表单版本】 获取表单版本列表
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowFormVersion($id)
    {
    	if(!app($this->flowPermissionService)->verifyFormSettingPermission($id,$this->own)) {
    		return $this->returnResult(['code' => ['0x000006', 'common']]);
    	}
        $result = app($this->flowService)->getFlowFormVersion($id, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新建表单版本
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单版本数据
     */
    public function createFlowFormVersion($formId)
    {
        if(!app($this->flowPermissionService)->verifyFormSettingPermission($formId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data   = $this->request->all();
        $result = app($this->flowService)->createFlowFormVersion($formId, $data);
        return $this->returnResult($result);
    }

    /**
     * 编辑表单版本
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单版本数据
     */
    public function editFlowFormVersion($versionId)
    {
        $data   = $this->request->all();
        $result = app($this->flowService)->editFlowFormVersion($data, $versionId);
        return $this->returnResult($result);
    }

    /**
     * 删除表单版本
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    public function deleteFlowFormVersion($versionId)
    {
        $result = app($this->flowService)->deleteFlowFormVersion($versionId);
        return $this->returnResult($result);
    }

    /**
     * 获取表单版本详情
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单版本详情
     */
    public function getFlowFormVersionDetail($id)
    {
        $result = app($this->flowService)->getFlowFormVersionDetail($id);
    	if ($result) {
    		$versionForm = $result->toArray();
    		if (isset($versionForm['form_id'])) {
    			if(!app($this->flowPermissionService)->verifyFormSettingPermission($versionForm['form_id'],$this->own)) {
    				return $this->returnResult(['code' => ['0x000006', 'common']]);
    			}
    		}
    	}
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 获取已定义的流程列表
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowDefineList()
    {
        $result = app($this->flowService)->getFlowDefineListService($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 获取某条流程的全部定义流程信息
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowDefineInfo($flowId)
    {
        $result = app($this->flowService)->getFlowDefineInfoService($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 【定义流程】 获取某条流程的基本定义流程信息
     *
     * @author miaochenchen
     *
     * @return [type]          [description]
     */
    public function getFlowDefineBasicInfo($flowId)
    {
        // 权限验证
        if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowSettingService)->getFlowDefineBasicInfo($flowId, $this->request->all());
        return $this->returnResult($result);
    }
    // 获取定义的全部出口条件信息
    public function getFlowDefineInfoList($flowId)
    {
        $result = app($this->flowService)->getFlowDefineInfoListService($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 【定义流程】 获取某条流出节点的条件
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowOutNodeInfo($flowId)
    {
        $result = app($this->flowService)->getFlowOutNodeInfoService($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 获取流程模板列表[子流程列表也是这个]
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowDefineRelateFlowSort()
    {
        $result = app($this->flowService)->getFlowDefineRelateFlowSortService($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 新建固定or自由流程基本信息
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function addFlowDefineBasicInfo()
    {
        $data = $this->request->all();
        if (!empty($data['flow_sort'])) {
            if (!app($this->flowPermissionService)->verifyFlowSortPermission(['sortId'=>$data['flow_sort'],'own'=>$this->own])) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->createFlowDefineBasicInfo($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 编辑固定or自由流程基本信息
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowDefineBasicInfo($flowId)
    {
        if($flowId != "batchFlow") {
            if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->modifyFlowDefineBasicInfo($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 删除固定or自由流程基本信息
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function deleteFlowDefineBasicInfo($flowId)
    {
        if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->removeFlowDefineBasicInfo($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 固定流程统一设置催促时间
     *
     * @author miaochenchen
     *
     * @return [type]          [description]
     */
    public function unifiedSetPresstime($flowId)
    {
        if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->unifiedSetPresstime($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 编辑监控人员
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowMonitor($flowId)
    {
        if($flowId != "batchFlow") {
            if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        // 将默认的 flowService 监控里的编辑流程监控的相关逻辑进行拆分，此备注方便兼容性测试，通过后即可逐步删除 flowService 内相关代码
        // 原代码 app($this->flowService)->modifyFlowMonitor($this->request->all(), $flowId. $this->own);
        $result = app($this->flowMonitorService)->modifyFlowMonitor($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 编辑其他设置
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowOtherInfo($flowId)
    {
        if($flowId != "batchFlow") {
            if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->modifyFlowOtherInfo($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 获取节点列表
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowNodeList($flowId)
    {
        $param = $this->request->all();
        $runId = isset($param["runId"]) ? $param["runId"] : 0;
        if (($runId > 0) && !app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view", "run_id" => $runId, "user_id" => $this->userId])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowNodeListService($this->request->all(), $flowId);
        return $this->returnResult($result);
    }
    /**
     * 【定义流程】 【节点设置】 获取节点列表-查看办理页面
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowNodeListForRunPage($flowId)
    {
    	$param = $this->request->all();
    	$runId = isset($param["runId"]) ? $param["runId"] : 0;
    	if (($runId > 0) && !app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view", "run_id" => $runId, "user_id" => $this->userId])) {
    		return $this->returnResult(['code' => ['0x000006', 'common']]);
    	}
        $result = app($this->flowService)->getFlowNodeListForRunPage($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 批量保存流程节点信息
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function batchSaveFlowNode($flowId)
    {
        if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->batchSaveFlowNodeService($this->request->all(), $flowId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 获取节点详情
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowNode($nodeId)
    {
        // if(!app($this->flowPermissionService)->verifyNodeSettingPermission($nodeId,$this->own)) {
            // return $this->returnResult(['code' => ['0x000006', 'common']]);
        // }
        $result = app($this->flowService)->getFlowNodeInfo($nodeId);
        return $this->returnResult($result);
    }

    /**
     * 判断节点是否被其他节点在流程办理人员处引用
     * @return array
     */
    public function isNodeQuotedInHandler()
    {
        return $this->returnResult(app($this->flowService)->isNodeQuotedInHandler($this->request->all()));
    }

    /**
     * 【定义流程】 【节点设置】 获取节点详情
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowNodeForBasePage($nodeId)
    {
    	// if(!app($this->flowPermissionService)->verifyNodeSettingPermission($nodeId,$this->own)) {
    		// return $this->returnResult(['code' => ['0x000006', 'common']]);
    	// }
        $type = 'false';
        if (isset($this->request->all()['type'])) {
            $type = $this->request->all()['type'];
        }
        $result = app($this->flowService)->getFlowNodeForBasePage($nodeId, $type);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 获取节点子流程详情
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getSunflowInfo($nodeId)
    {
    	$data = $this->request->all();
        $result = app($this->flowService)->getSunflowInfo($nodeId,$data);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 删除节点
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function deleteFlowNode($nodeId)
    {
        if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->removeFlowNode($this->request->all(), $nodeId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 新建节点--节点信息
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function addFlowNode()
    {
        $data = $this->request->all();
        if (!app($this->flowPermissionService)->verifyFlowSettingPermission($data['flow_id'], $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->createFlowNode($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑节点信息
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowNode($nodeId)
    {
        if($nodeId != "batchNode") {
            if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }

        $result = app($this->flowService)->modifyFlowNode($this->request->all(), $nodeId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑办理人员[默认办理人一起保存]
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowNodeTransactUser($nodeId)
    {
        if($nodeId != "batchNode") {
            if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->modifyFlowNodeTransactUser($this->request->all(), $nodeId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑字段控制
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowNodeFieldControl($nodeId)
    {
        if($nodeId != "batchFlow" && $nodeId != "batchNode") {
            if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->modifyFlowNodeFieldControl($this->request->all(), $nodeId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 字段控制，解析表单控件，获取控件类型作为筛选条件，带数量
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowNodeFieldControlFilterInfo()
    {
    	$param = $this->request->all();
    	$flowId = isset($param['flow_id']) && !empty($param['flow_id']) ? $param['flow_id'] : '';
    	if ($flowId) {
    		if (!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId, $this->own)) {
    			return $this->returnResult(['code' => ['0x000006', 'common']]);
    		}
    	}
        $result = app($this->flowService)->getFlowNodeFieldControlFilterInfo($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑路径设置
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowNodePathSet($nodeId)
    {
        if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->modifyFlowNodePathSet($this->request->all(), $nodeId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 获取出口条件列表
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowNodeOutletList($nodeId)
    {
        $result = app($this->flowService)->getFlowNodeOutletList($this->request->all(), $nodeId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 获取出口条件详情
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowNodeOutlet($termId)
    {
        $result = app($this->flowService)->getFlowNodeOutletInfo($termId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 添加出口条件
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function addFlowNodeOutlet()
    {
        $result = app($this->flowService)->newFlowNodeOutlet($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑出口条件
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowNodeOutlet($termId)
    {
        if (!app($this->flowPermissionService)->verifyFlowTermPermission($termId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->modifyFlowNodeOutlet($this->request->all(), $termId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 删除出口条件
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function deleteFlowNodeOutlet($termId)
    {
        if (!app($this->flowPermissionService)->verifyFlowTermPermission($termId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->removeFlowNodeOutlet($this->request->all(), $termId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑出口条件的关联关系
     *
     * @author dingpeng
     *
     * @param  [type]                     $nodeId [description]
     *
     * @return [type]                             [description]
     */
    public function editFlowNodeOutletRelation($nodeId)
    {
        if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->modifyFlowNodeOutletRelation($this->request->all(), $nodeId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑子流程
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowNodeSubflow($nodeId)
    {
        if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->modifyFlowNodeSubflow($this->request->all(), $nodeId);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 编辑抄送人员
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowNodeCopyUser($nodeId)
    {

        if($nodeId != "batchNode") {
            if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowService)->modifyFlowNodeCopyUser($this->request->all(), $nodeId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程表单解析】
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getParseForm($formId)
    {
        // 从flowService里分发
        $result = app($this->flowService)->getParseForm($formId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 【办理人员】 经办人员/部门/角色的值的变化，会触发【默认办理人/主办人】的验证事件，验证人员是否在范围内，返回处理后的，在范围内的【默认办理人/主办人】
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function verifyDefaultUserInclude()
    {
    	$param = $this->request->all();
    	if (isset($param['node_id']) && $param['node_id']) {
    		$nodeId = $param['node_id'];
    		if (!app($this->flowPermissionService)->verifyFlowNodePermission($nodeId, $this->own)) {
    			return $this->returnResult(['code' => ['0x000006', 'common']]);
    		}
    	}
        $result = app($this->flowService)->verifyDefaultUserInclude($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【流程表单控件序号】 根据流程表单id，查询此流程表单里所有的控件，按照序号（control_sort_id）排序（asc），关联所属分组（belongs_group）的信息。为了路由的规范性，表单id（flow_form_id）通过必填参数的方式传递。
     *
     * @author dingpeng
     *
     * @return [type] [description]
     */
    public function getFlowFormControlSort()
    {
        $result = app($this->flowService)->getFlowFormControlSort($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【流程表单控件分组】 根据流程表单id（flow_form_id），获取此表单里的所有“控件分组”，按照序号（group_sort_id）排序（asc），关联下属所有表单控件信息。
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowFormControlGroup()
    {
        $result = app($this->flowService)->getFlowFormControlGroup($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【流程表单控件分组】 排序，分组两个表的数据保存只需要一个路由，格式化之后传到这个路由里，在此路由里进行处理。
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function saveFlowFormControlGroup()
    {
        $result = app($this->flowService)->saveFlowFormControlGroup($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 【会签控件】 获取会签控件列表
     *
     * @author dingpeng
     *
     * @return [type]          [descriptigetFlowCounterSignon]
     */
    public function getFlowCounterSign($runId)
    {
        $data   = $this->request->all();
        $data['user_id'] = isset($this->own['user_id']) ? $this->own['user_id'] : '';
        $data['own'] = $this->own ?? [];
        $result = app($this->flowService)->getFlowCounterSign($runId, $data);
        return $this->returnResult($result);
    }

    /**
     * 【会签控件】 新建会签
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单版本数据
     */
    public function createFlowCounterSign($runId)
    {
        $data   = $this->request->all();
        $result = app($this->flowService)->createFlowCounterSign($runId, $data);
        return $this->returnResult($result);
    }

    /**
     * 【会签控件】 编辑会签
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单版本数据
     */
    public function editFlowCounterSign($countersignId)
    {
        $data   = $this->request->all();
        $result = app($this->flowService)->editFlowCounterSign($data, $countersignId);
        return $this->returnResult($result);
    }

    /**
     * 【会签控件】 删除会签
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    public function deleteFlowCounterSign($countersignId)
    {
        $result = app($this->flowService)->deleteFlowCounterSign($countersignId);
        return $this->returnResult($result);
    }

    /**
     * 【会签控件】 获取会签详情
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单版本详情
     */
    public function getFlowCounterSignDetail($countersignId)
    {
        $result = app($this->flowService)->getFlowCounterSignDetail($countersignId);
        return $this->returnResult($result);
    }

    /**
     * 【流程定义】 获取表单字段详情
     *
     */
    public function getFlowFormFliesDetail($flowId)
    {
        if (!$flowId) {
            return $this->returnResult([]);
        }
        // 判断流程编辑权限
        if(!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId,$this->own)){
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $param  = $this->request->all();
        $formId = app($this->flowRunService)->getFormIdByFlowId($flowId);
        if (isset($param['withOutLayout'])) {
            $withOutLayout = true;
        } else {
            $withOutLayout = false;
        }
        $formControlTypeArray = app($this->flowRunService)->getFlowFormControlStructureDetial(["form_id" => $formId], $withOutLayout);
        return $this->returnResult($formControlTypeArray);
    }
    /**
     * 【流程定义】 获取表单字段详情
     *
     */
    public function getFlowFormFliesDetailForOutsend($id)
    {
        $sunflow = $this->request->all();
        if (isset($sunflow['sunflow_id']) && $sunflow['sunflow_id']) {
            $id = $sunflow['sunflow_id'];
        }
        if (!$id) {
            return $this->returnResult([]);
        }
        // 判断流程编辑权限
        // if(is_numeric($id) && !app($this->flowPermissionService)->verifyFlowSettingPermission($id,$this->own)){
        //     return $this->returnResult(['code' => ['0x000006', 'common']]);
        // }
        $flowinfo = app($this->flowTypeRepository)->getDetail($id , false , ['form_id']);
        $formId               = $flowinfo['form_id'] ?? '';
        $formControlTypeArray = app($this->flowRunService)->getFlowFormControlStructureDetialForOutsend(["form_id" => $formId]);
        $otherData = [
            [
                "control_parent_id" => "",
                "control_id"        => "flow_id",
                "control_title"     => trans("flow.0x030090"), // 定义流程ID,
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "run_id",
                "control_title"     => trans("flow.0x030091"), // 运行流程ID
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "run_name",
                "control_title"     => trans("flow.0x030073"), // 流程名称
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "form_id",
                "control_title"     => trans("flow.0x030092"), // 流程表单ID
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "node_id",
                "control_title"     => trans("flow.0x030093"), // 流程节点ID
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "process_id",
                "control_title"     => trans("flow.0x030094"), // 运行步骤ID
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "attachments",
                "control_title"     => trans("flow.0x030095"), // 相关附件
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "feedback",
                "control_title"     => trans("flow.0x030096"), // 签办反馈
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "document",
                "control_title"     => trans("flow.0x030097"), // 相关文档
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "flow_creator",
                "control_title"     => trans("flow.0x030098"), // 流程创建人ID
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ],
            [
                "control_parent_id" => "",
                "control_id"        => "flow_submit_user",
                "control_title"     => trans("flow.0x030099"), // 流程提交人ID
                "control_type"      => "systemData",
                "haschilen"         => 0,
            ]
        ];
        if (isset($sunflow['hasSysdata']) && !empty($formControlTypeArray)) {
            $formControlTypeArray = array_merge($formControlTypeArray, $otherData);
        }
        // 20200227,zyx,子流程字段配对，增加紧急程度的选项
        if (isset($sunflow['hasSonFlowConf']) && $sunflow['hasSonFlowConf'] && !empty($formControlTypeArray)) {
            $sonFlowConfData = [
                [
                    "control_parent_id" => "",
                    "control_id"        => "emergency_level",
                    "control_title"     => trans("flow.0x030067"), // 紧急程度
                    "control_type"      => "sonFlowConfData",
                    "haschilen"         => 0,
                ]
            ];
            $formControlTypeArray = array_merge($formControlTypeArray, $sonFlowConfData);
        }

        return $this->returnResult($formControlTypeArray);
    }
    /**
     * 【流程定义】 节点设置-流程图节点信息更新
     *
     */
    public function chartEditNode($id)
    {
        $formControlTypeArray = app($this->flowService)->chartEditNode($this->request->all(), $id, $this->own);
        return $this->returnResult($formControlTypeArray);
    }
    /**
     * 【流程定义】 节点设置-流程图节点删除
     *
     */
    public function chartDeleteNode($id)
    {
        $formControlTypeArray = app($this->flowService)->chartDeleteNode($id, $this->own);
        return $this->returnResult($formControlTypeArray);
    }
    /**
     * 【流程定义】 节点设置-流程图节点新建
     *
     */
    public function chartCreateNode()
    {
        $formControlTypeArray = app($this->flowService)->chartCreateNode($this->request->all(), $this->own);
        return $this->returnResult($formControlTypeArray);
    }
    /**
     * 【流程定义】 节点设置-流程图节点清除所有连线
     *
     */
    public function chartDeleteAllNodeProcessTo($id)
    {
        $formControlTypeArray = app($this->flowService)->chartDeleteAllNodeProcessTo($id, $this->own);
        return $this->returnResult($formControlTypeArray);
    }
    /**
     * 【流程定义】 节点设置-流程图节点清除连线
     *
     */
    public function chartDeleteNodeProcessTo()
    {
        $formControlTypeArray = app($this->flowService)->chartDeleteNodeProcessTo($this->request->all(), $this->own);
        return $this->returnResult($formControlTypeArray);
    }
    /**
     * 【流程定义】 节点设置-流程图节点保存出口条件
     *
     */
    public function chartUpdateNodeCondition()
    {
        $formControlTypeArray = app($this->flowService)->chartUpdateNodeCondition($this->request->all(), $this->own);
        return $this->returnResult($formControlTypeArray);
    }
    // /**
    //  * 【流程定义】 节点设置-流程外发测试外部数据库连接
    //  *
    //  */
    // public function externalDatabaseTestConnection(){
    //     $result= app($this->flowService)->externalDatabaseTestConnection($this->request->all());
    //     return $this->returnResult($result);
    // }
    /**
     * 【流程定义】 节点设置-流程外发获取内部模块列表
     *
     */
    public function flowOutsendGetModuleList()
    {
        $result = app($this->flowOutsendService)->flowOutsendGetModuleList($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 【流程定义】 节点设置-流程外发获取内部模块字段列表
     *
     */
    public function flowOutsendGetModuleFieldsList()
    {
        $result = app($this->flowOutsendService)->flowOutsendGetModuleFieldsList($this->request->all());
        return $this->returnResult($result);
    }
    // /**
    //  * 【流程定义】 节点设置-流程外发获取外部数据库表
    //  *
    //  */
    // public function flowOutsendDatabaseTableList(){
    //     $result= app($this->flowService)->flowOutsendDatabaseTableList($this->request->all());
    //     return $this->returnResult($result);
    // }
    // /**
    //  * 【流程定义】 节点设置-流程外发获取外部数据库表
    //  *
    //  */
    // public function externalDatabaseGetData(){
    //     $result= app($this->flowService)->externalDatabaseGetData($this->request->all());
    //     return $this->returnResult($result);
    // }
    // /**
    //  * 【流程定义】 节点设置-流程外发获取外部数据库表字段
    //  *
    //  */
    // public function flowOutsendDatabaseTableFieldList(){
    //     $result= app($this->flowService)->flowOutsendDatabaseTableFieldList($this->request->all());
    //     return $this->returnResult($result);
    // }
    /**
     * 【流程定义】 节点设置-流程外发保存数据
     *
     */
    public function flowOutsendSaveData()
    {
        $result = app($this->flowOutsendService)->flowOutsendSaveData($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /**
     * 【流程定义】 节点设置-流程超时设置保存数据
     *
     */
    public function flowOverTimeSaveData()
    {
        $result = app($this->flowService)->flowOverTimeSaveData($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 编辑流水号规则
     *
     */
    public function updateFlowSequenceRule($flowId)
    {
        $result = app($this->flowService)->updateFlowSequenceRule($this->request->all(), $flowId,$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取流出节点列表流程图模式
     *
     */
    public function getFlowOutNodeList($nodeId)
    {
        $result = app($this->flowService)->getFlowOutNodeList($nodeId,$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取流出节点列表流程图模式
     *
     */
    public function getFlowCurrentNodeList($flowId)
    {
        $result = app($this->flowService)->getFlowCurrentNodeList($flowId,$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取流出节点列表列表模式
     *
     */
    public function getFlowOutNodeListForList($nodeId)
    {
        $result = app($this->flowService)->getFlowOutNodeListForList($nodeId,$this->own);
        return $this->returnResult($result);
    }

    /*
     * 【表单设计器】 返回导入的表单内容
     *
     */
    public function getImportFlowForm($formId)
    {
        $result = app($this->flowService)->getImportFlowForm($this->request->all(), $formId, $this->own);
        return $this->returnResult($result);
    }

    /*
     * 【表单设计器】 判断导入的表单素材版本
     *
     */
    public function checkFormVersion()
    {
        $result = app($this->flowFormService)->checkFormVersion($this->request->all());
        return $this->returnResult($result);
    }

    /*
     * 【定义流程】 获取流程办理人离职人员信息
     *
     */
    public function getFlowQuitUserList($flowId)
    {
        $result = app($this->flowService)->getFlowQuitUserList($flowId,$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 替换流程设置办理人离职人员
     *
     */
    public function replaceHandleInfo($flowId)
    {
        $result = app($this->flowService)->replaceHandleInfo($this->request->all(), $flowId, $this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 【表单模板】 取定义流程的各种表单模板
     *
     */
    public function getFlowNodeTemplate()
    {
        $result = app($this->flowService)->getFlowNodeTemplate($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 【表单模板】 保存定义流程的各种表单模板
     *
     */
    public function saveFlowNodeTemplate()
    {
        $result = app($this->flowService)->saveFlowNodeTemplate($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 【表单模板】 定义流程，获取各种表单模板规则的列表
     *
     */
    public function getFlowTemplateRuleList()
    {
        $param = $this->request->all();
        if (!isset($param['flow_id'])) {
            return $this->returnResult([]);
        }
        // 判断流程编辑权限
        if(!app($this->flowPermissionService)->verifyFlowSettingPermission($param['flow_id'],$this->own)){
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowTemplateRuleList($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 【表单模板】 定义流程，保存各种表单模板规则
     *
     */
    public function saveFlowTemplateRule()
    {
        $result = app($this->flowService)->saveFlowTemplateRule($this->request->all(),$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 【表单模板】 获取流程表单模板的list
     *
     */
    public function getFlowTemplateList()
    {
        $result = app($this->flowService)->getFlowTemplateList($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取流程所有节点设置办理人列表
     *
     */
    public function getFlowHandleUserList($flowId)
    {
        $result = app($this->flowService)->getFlowHandleUserList($flowId,$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取流程所有节点设置办理角色列表
     *
     */
    public function getFlowHandleRoleList($flowId)
    {
        $result = app($this->flowService)->getFlowHandleRoleList($flowId,$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取流程所有节点设置办理部门列表
     *
     */
    public function getFlowHandleDeptList($flowId)
    {
        $result = app($this->flowService)->getFlowHandleDeptList($flowId,$this->own);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 办理页面子流程列表
     *
     */
    public function getRelationFlowData($runId)
    {
        $param = $this->request->all();
        $result = app($this->flowRunService)->getRelationFlowData($runId,$this->own , 'data' ,$param );
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取出口条件中涉及目标控件的数据
     *
     */
    public function getUseFormControls()
    {
        $result = app($this->flowRunService)->getUseFormControls($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【流程表单】 表单--下拉框动态数据源--获取流程经办人列表
     *
     */
    public function getSelectSourceProcessUserList()
    {
        $result = app($this->flowService)->getSelectSourceProcessUserList($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【流程表单】 表单--下拉框动态数据源--获取流程本步骤经办人列表
     *
     */
    public function getSelectSourceCurrentProcessUserList()
    {
        $result = app($this->flowService)->getSelectSourceCurrentProcessUserList($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 更新节点排序
     *
     */
    public function updateNodeSort($flowId)
    {
        $result = app($this->flowService)->updateNodeSort($flowId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程报表】 获取流程报表设置，获取分组依据和数据分析字段
     * @return [type] [description]
     */
    public function getFlowReportGroupAndAnalyzeConfig()
    {
        $result = app($this->flowReportService)->getFlowReportGroupAndAnalyzeConfig($this->request->all());
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取自由流程必填设置
     *
     */
    public function getFreeFlowRequired($flowId)
    {
        if (!is_numeric($flowId)) {
            return $this->returnResult([]);
        }
        // 判断流程编辑权限
        if(!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId,$this->own)){
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFreeFlowRequired($flowId);
        return $this->returnResult($result);
    }
    /*
     * 【定义流程】 获取自由流程必填字段
     *
     */
    public function getFreeFlowRequiredInfo($flowId)
    {
        if(!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFreeFlowRequired($flowId, 'info');
        return $this->returnResult($result);
    }

    /**
     * 【流程报表】 编辑自由流程必填设置
     * @return [type] [description]
     */
    public function editFreeFlowRequired($flowId)
    {
        if(!app($this->flowPermissionService)->verifyFlowSettingPermission($flowId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->editFreeFlowRequired($flowId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /**
     * 【流程报表】 获取流程名称规则设置元素列表
     * @return [type] [description]
     */
    public function getFlowNameRulesField($formId)
    {
        $result = app($this->flowService)->getFlowNameRulesField($formId);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 表单简易版标准版切换
     * @return [type] [description]
     */
    public function formTypeConversion()
    {
        $param = $this->request->all();
        foreach ($param as  $data) {
            if (!isset($data['form_id']) || empty($data['form_id'])) {
                continue;
            } else {
                if (!empty($data['is_child'])) {
                    $verifyFormSettingPermission = app($this->flowPermissionService)->verifyFormSettingPermission($data['form_id'],$this->own,'child');
                } else {
                    $verifyFormSettingPermission = app($this->flowPermissionService)->verifyFormSettingPermission($data['form_id'],$this->own);
                }
                if (!$verifyFormSettingPermission) {
                    return $this->returnResult(['code' => ['0x000006', 'common']]);
                }
            }
        }
        $result = app($this->flowService)->formTypeConversion($param);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 formTypeConversionGetControl
     * @return [type] [description]
     */
    public function formTypeConversionGetControl($formId)
    {
        if(!app($this->flowPermissionService)->verifyFormSettingPermission($formId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->formTypeConversionGetControl($formId);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 formTypeConversionGetControl
     * @return [type] [description]
     */
    public function formTypeConversionGetControlForComplex($formId)
    {
        if(!app($this->flowPermissionService)->verifyFormSettingPermission($formId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->formTypeConversionGetControlForComplex($formId);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 子表单-生成子表单
     * @return [type] [description]
     */
    public function createChildForm()
    {
        $param = $this->request->all();
        if (!isset($param['parent_id'])) {
            return $this->returnResult(['code' => ['main_form_is_necessary', 'flow']]);
        }
        if (empty($param['parent_id'])) {
            return $this->returnResult(['code' => ['save_main_form_first', 'flow']]);
        }
        if(!app($this->flowPermissionService)->verifyFormSettingPermission($param['parent_id'], $this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowFormService)->createChildForm($param);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 子表单-子表单列表
     * @return [type] [description]
     */
    public function getChildFormList($parentId)
    {
        if(!app($this->flowPermissionService)->verifyFormSettingPermission($parentId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $page = 0;
        $limit = 10;
        $data = $this->request->all();
        if(!empty($data['page'])){
        	$page = $data['page'];
        }
        if(!empty($data['limit'])){
        	$limit = $data['limit'];
        }
        $result = app($this->flowFormService)->getChildFormList($parentId,$page,$limit);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 子表单-子表单列表
     * @return [type] [description]
     */
    public function getChildFormListByFlowId($flowId)
    {
        if($flowId && $flowId !=='undefined' && !app($this->flowPermissionService)->verifyFlowSettingPermission($flowId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowFormService)->getChildFormListByFlowId($flowId, $this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 子表单-获取子表单详情
     * @return [type] [description]
     */
    public function getChildFormDetail($formId)
    {
        if(!app($this->flowPermissionService)->verifyChildFormSettingPermission($formId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowFormService)->getChildFormDetail($formId);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 子表单-删除单个子表单
     * @return [type] [description]
     */
    public function deleteChildForm($formId)
    {
        if(!app($this->flowPermissionService)->verifyChildFormSettingPermission($formId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowFormService)->deleteChildForm($formId);
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 子表单-编辑单个子表单
     * @return [type] [description]
     */
    public function editChildForm($formId)
    {
        if(!app($this->flowPermissionService)->verifyChildFormSettingPermission($formId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowFormService)->editChildForm($formId, $this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 【流程表单】 子表单-根据父表单删除所有子表单
     * @return [type] [description]
     */
    public function deleteChildFormByParent($parentId)
    {
        if(!app($this->flowPermissionService)->verifyFormSettingPermission($parentId,$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowFormService)->deleteChildFormByParent($parentId);
        return $this->returnResult($result);
    }

    /**
     * 【流程表单】 子表单-更新子表单
     * @return [type] [description]
     */
    public function updateChildForm()
    {
    	$request = $this->request->all();
    	$formId = isset($request['form_id'])?$request['form_id']:null;
    	$childFormId = !empty($request['child_form_id'])?$request['child_form_id']:null;
    	$updateType = !empty($request['update_type'])?$request['update_type']:null;
		$result = app($this->flowFormService)->changeFormControlStructure($formId,$childFormId,$updateType);
    	return $this->returnResult($result);
    }

    /**
     * 【流程表单】 子表单-编辑所有子表单
     * @return [type] [description]
     */
    public function editAllChildForm()
    {
        $result = app($this->flowFormService)->editAllChildForm($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取流程表单类别列表
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowFormSortList()
    {
        $result = app($this->flowService)->getFlowFormSortListService($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 新建表单分类
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单分类数据
     */
    public function createFlowFormSort()
    {
        $data   = $this->request->all();
        $data['creator'] = isset($this->own['user_id']) ? $this->own['user_id'] : '';
        $result = app($this->flowService)->createFlowFormSort($data);
        return $this->returnResult($result);
    }

    /**
     * 编辑表单分类
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单分类数据
     */
    public function editFlowFormSort($id)
    {
        $data   = $this->request->all();
        $data['creator'] = isset($this->own['user_id']) ? $this->own['user_id'] : '';
        $result = app($this->flowService)->editFlowFormSort($data, $id);
        return $this->returnResult($result);
    }
    /**
     * 删除表单分类
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    public function deleteFlowFormSort($id)
    {
        $result = app($this->flowService)->deleteFlowFormSort($id);
        return $this->returnResult($result);
    }

    /**
     * 获取表单分类详情
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单分类结果
     */
    public function getFlowFormSortDetail($id)
    {
        if ($id == 0 || empty(app($this->flowFormSortRepository)->getDetail($id)) ) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowService)->getFlowFormSortDetail($id);
        return $this->returnResult($result);
    }
    /**
     * 获取表单类别最大序号
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单分类结果
     */
    public function getMaxFlowFormSort()
    {
        $result = app($this->flowService)->getMaxFlowFormSort();
        return $this->returnResult($result);
    }
    /**
     * 获取流程类别最大序号
     *
     * @author dingpeng
     *
     * @since  2015-10-16 创建
     *
     * @return json 表单分类结果
     */
    public function getMaxFlowSort()
    {
        $result = app($this->flowService)->getMaxFlowSort();
        return $this->returnResult($result);
    }
    /**
     * 获取流程紧急程度选项
     *
     * @author lizhijun
     *
     * @since 2018-06-13
     *
     * @return array
     */
    public function getInstancyOptions()
    {
        return $this->returnResult(app($this->flowSettingService)->getInstancyOptions($this->request->all(), $this->userId));
    }
    /**
     * 获取流程紧急程度选项ID和名称对应关系
     *
     * @author 缪晨晨
     *
     * @since 2018-02-18
     *
     * @return array
     */
    public function getInstancyIdNameRelation()
    {
        return $this->returnResult(app($this->flowSettingService)->getInstancyIdNameRelation($this->request->all()));
    }
    /**
     * 保存流程紧急程度选项
     *
     * @author lizhijun
     *
     * @since 2018-06-13
     *
     * @return boolean
     */
    public function saveInstancyOptions()
    {
        return $this->returnResult(app($this->flowSettingService)->saveInstancyOptions($this->request->all(), $this->own));
    }
    /**
     * 删除流程紧急程度选项
     *
     * @author lizhijun
     *
     * @since 2018-06-13
     *
     * @return boolean
     */
    public function deleteInstancyOption($instancyId)
    {
        return $this->returnResult(app($this->flowSettingService)->deleteInstancyOption($instancyId));
    }
    /**
     * 【流程设置】获取流程设置某个参数的值
     *
     * @method 缪晨晨
     *
     * @param  [string]        $paramKey [要查询的参数key]
     *
     * @return [object]                   [查询结果]
     */
    public function getFlowSettingsParamValueByParamKey($paramKey)
    {
        $result = app($this->flowSettingService)->getFlowSettingsParamValueByParamKey($paramKey);
        return $this->returnResult($result);
    }
    /**
     * 【流程设置】设置流程设置某个参数
     *
     * @method 缪晨晨
     *
     * @return [object]                   [设置结果]
     */
    public function setFlowSettingsParamValue()
    {
        $result = app($this->flowSettingService)->setFlowSettingsParamValue($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 【流程交办】获取流程相关人员
     *
     */
    public function getFlowUserInfo()
    {
        return $this->returnResult(app($this->flowWorkHandOverService)->getFlowOutUser($this->request->all(), $this->own));
    }
    /**
     * 【流程交办】获取流程相关人员
     *
     */
    public function getFlowAllUserInfo()
    {
        return $this->returnResult(app($this->flowWorkHandOverService)->getFlowUserInfo($this->request->all(), $this->own));
    }
    /**
     * 【流程交办】全局替换流程相关人员
     *
     */
    public function replaceFlowUser()
    {
        return $this->returnResult(app($this->flowWorkHandOverService)->replaceFlowUser($this->request->all(), $this->own));
    }
    /**
     * 【流程交办】单个替换流程相关人员
     *
     */
    public function replaceOneFlowUser()
    {
        return $this->returnResult(app($this->flowWorkHandOverService)->replaceOneFlowUser($this->request->all(), $this->own));
    }
    /**
     * 【流程交办】单个替换流程相关人员
     *
     */
    public function replaceFlowUserByType()
    {
        return $this->returnResult(app($this->flowWorkHandOverService)->replaceFlowUserByType($this->request->all(), $this->own));
    }
    /**
     * 【流程交办】单个替换流程相关人员
     *
     */
    public function replaceFlowUserByUser()
    {
        return $this->returnResult(app($this->flowWorkHandOverService)->replaceFlowUserByUser($this->request->all(), $this->own));
    }
    /**
     * 【流程交办】单个替换流程相关人员
     *
     */
    public function replaceFlowUserByGrid()
    {
        return $this->returnResult(app($this->flowWorkHandOverService)->replaceFlowUserByGrid($this->request->all(), $this->own));
    }
    /**
     * 获得表单数据模板
     */
    public function getFormDataTemplate()
    {
        $param = $this->request->all();
        if(!isset($param['flowId'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if(!app($this->flowPermissionService)->verifyFlowSettingPermission($param['flowId'],$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowParseService)->getFormDataTemplate($param, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 获得表单数据模板
     */
    public function getFormDataTemplateForRun()
    {
        $param = $this->request->all();
        if(!isset($param['flowId'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        //验证新建流程权限
        if(!app($this->flowPermissionService)->verifyFlowNewPermission(['own'=>$this->own,'flow_id'=>$param['flowId']])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }

        $result = app($this->flowParseService)->getFormDataTemplate($param, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 设置表单数据模板
     */
    public function setFormDataTemplate()
    {
        $param = $this->request->all();
        if(!isset($param['flowId'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if(!app($this->flowPermissionService)->verifyFlowSettingPermission($param['flowId'],$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowParseService)->setFormDataTemplate($param, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 设置表单数据模板
     */
    public function setFormDataTemplateForRun()
    {
        $param = $this->request->all();
        if(!isset($param['flowId'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if(isset($param['user_id']) && $param['user_id'] != $this->own['user_id']) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        //验证新建流程权限
        if(!app($this->flowPermissionService)->verifyFlowNewPermission(['own'=>$this->own,'flow_id'=>$param['flowId']])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowParseService)->setFormDataTemplate($param, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 保存用户模板
     */
    public function saveUserTemplate()
    {
        $param = $this->request->all();
        foreach ($param as $key => $value) {
            if(!isset($value['flow_id'])) {
                return $this->returnResult(['code' => ['0x000003', 'common']]);
            }
            if(isset($value['user_id']) && $value['user_id'] != $this->own['user_id']) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
            //验证新建流程权限
            if(!app($this->flowPermissionService)->verifyFlowNewPermission(['own'=>$this->own,'flow_id'=>$value['flow_id']])) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }

        $result = app($this->flowParseService)->saveUserTemplate($param,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 删除用户模板
     */
    public function deleteUserTemplate($id)
    {
        $result = app($this->flowParseService)->deleteUserTemplate($id,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 【流程办理】保存流程前根据流程标题规则获取流程标题
     */
    public function getFlowRunNameByRule()
    {
        $result = app($this->flowService)->getFlowRunNameByRule($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程定义】 节点设置-流程数据验证保存数据
     *
     */
    public function flowValidateSaveData()
    {
        $param = $this->request->all();

        if(isset($param["node_id"]) && $param["node_id"] == "batchNode") {
            $batchNode = isset($param["batchNode"]) ? $param["batchNode"] : [];
            if(empty($batchNode)) {
                // 保存失败，未获取到流程节点ID
                return $this->returnResult(['code' => ['0x030155', 'flow']]);
            } else {
                $saveResult = "";
                foreach ($batchNode as $key => $nodeId) {
                    if(!app($this->flowPermissionService)->verifyNodeSettingPermission($nodeId,$this->own)) {
                        return $this->returnResult(['code' => ['0x000006', 'common']]);
                    }
                }
            }
        }
        if (isset($param['node_id']) && is_numeric($param['node_id'])) {
            $nodeId = $param['node_id'];
            if(!app($this->flowPermissionService)->verifyNodeSettingPermission($nodeId,$this->own)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }
        $result = app($this->flowParseService)->flowValidateSaveData($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程定义】 节点设置-流程数据验证获取数据
     *
     */
    public function getFlowValidateData()
    {
        $param = $this->request->all();
        if(!isset($param['node_id'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if(!app($this->flowPermissionService)->verifyNodeSettingPermission($param['node_id'],$this->own)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $result = app($this->flowParseService)->getFlowValidateData($param, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 【流程定义】 节点设置-流程数据验证获取数据
     *
     */
    public function getFlowValidateDataForRun()
    {
        $param = $this->request->all();
        $result = app($this->flowParseService)->getFlowValidateData($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【流程运行】  验证流程数据
     *
     */
    public function validateFlowData($id)
    {
        $param = $this->request->all();
        if (!empty($param['resolveFormula'])){
        	$result['parsedResult'] = false;
        	try{
        		$conditions_value = $param['formulaStr'] ?? '';
        		$formData = $param['formData'] ?? [];
        		$flowOtherInfo =$param['flowOtherInfo'] ?? [];
        		$newFlow = (!empty($formData['route']) && $formData['route'] == 'true') ? true : false;
        		$verifyResult = app($this->flowRunService)->verifyFlowFormOutletCondition($conditions_value, $formData, $flowOtherInfo,true,$newFlow);
        		if ($verifyResult){
        			$result['parsedResult'] = true;
        		}
        	}catch(\Exception $e){
        		$result['parsedResult'] = false;
        		$result['exception'] = $e->getMessage();
        	}catch(\Error $error) {
        		$result['parsedResult'] = false;
        		$result['error'] = ['0x000003', 'common'];
        	}
        	return $this->returnResult($result);
        }
        if(!isset($param['run_id'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if($param['run_id'] ==0) {
            //验证新建流程权限
            if(!app($this->flowPermissionService)->verifyFlowNewPermission(['own'=>$this->own,'flow_id'=>$param['flow_id']])) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }else {
            $verifyParam = [
                'type'=>'handle',
                'run_id'=>$param['run_id'],
                'user_id'=>$this->own['user_id'],
            ];
            if(!app($this->flowPermissionService)->verifyFlowHandleViewPermission($verifyParam)) {
                return $this->returnResult(['code' => ['0x000006', 'common']]);
            }
        }

        $result = app($this->flowParseService)->validateFlowData($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 【定义流程】 【节点设置】 获取自由节点详情
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function getFlowFreeNode($nodeId)
    {
        $result = app($this->flowService)->getFlowFreeNodeInfo($nodeId);
        return $this->returnResult($result);
    }
    /**
     * 【定义流程】 【节点设置】 获取自由节点详情
     *
     * @author dingpeng
     *
     * @return [type]          [description]
     */
    public function editFlowFreeNode($nodeId)
    {
        $param = $this->request->all();
        $result = app($this->flowService)->editFlowFreeNode($nodeId, $param);
        return $this->returnResult($result);
    }
    /**
     * 保存自由节点步骤信息
     */
    public function saveFreeProcessSteps()
    {
        $param = $this->request->all();
        $result = app($this->flowRunService)->saveFreeProcessSteps($param,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 【定义流程】 预览流程标题处 获取服务器时间
     *
     */
    public function getServerDate()
    {
        $result = app($this->flowService)->getFlowNameRulesDateDatas();
        return $this->returnResult($result);
    }

    /**
     * 【办理流程】 获取流程数据外发记录
     * @author   zyx
     *
     * @param    int
     *
     * @return  array
     * */
    public function getOutsendList()
    {
        $param = $this->request->all();
        if(!isset($param['run_id'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if(!isset($param['flow_id'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if (!$param['run_id']) { // 新建流程，没有run_id，直接返回空数组
            $result = [];
        } else {
            $result = app($this->flowOutsendService)->getOutsendList($param);
        }
        return $this->returnResult($result);
    }
    /**
     * 【自定义流程选择器】 通过run_id获取流程详情
     *
     */
    public function getRunDetailByRunId($runId)
    {
        if(!isset($runId)) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        $result = app($this->flowRunService)->getRunDetailByRunId($runId, $this->userId);
        if (empty( $result)) {
            return $this->returnResult(['code' => ['0x000002', 'common']]);
        }
        return $this->returnResult($result);
    }

    /**
     * 【流程定义】 导出流程资源
     */
    public function exportFlowMaterial($flowId){
        return $this->returnResult(app($this->flowExportService)->exportFlowMaterial($flowId));
    }

    /**
     * 【流程定义】 导入流程资源
     */
    public function importFlowMaterial(Request $request){
        return $this->returnResult(app($this->flowExportService)->importFlowMaterial($request->all(), $this->own));
    }

    /**
     * 【流程定义】 通过flow_id获取form_id
     *
     */
    public function getFormDetailByFlowId($flowId)
    {
        if(!isset($flowId)) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        $result = app($this->flowRunService)->getFormDetailByFlowId(  $flowId);
        if (empty( $result)) {
            return $this->returnResult(['code' => ['0x000002', 'common']]);
        }
        return $this->returnResult($result);
    }

    /**
     * 解析历史表单数据集
     * @return array
     */
    public function getControlTypeData()
    {
        return $this->returnResult(app($this->flowParseService)->getControlTypeData());
    }

    /**
     * 生成二维码图片，返回附件ID
     * @return array
     */
    public function generateQrCode()
    {
        $formId = $this->request->input('formId');
        $data = [
            'mode' => 'router',
            'body' => [
                'commands' => ['flow/form/upload-form'],
                'extras' => [
                    'userId' => $this->own['user_id'],
                    'formId' => $formId,
                ]
            ],
            'timestamp' => time(),
            'ttl' => 0,
        ];
        // 文件名
        $fileName = 'qrcode_' . $formId . '.png';
        if (! File::exists(public_path($fileName))) {
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size('200')->generate(json_encode($data), public_path($fileName));
        }
        $file = [
            'attachment_name' => $fileName,
            'temp_src_file' => public_path($fileName)
        ];
        return $this->returnResult($this->addAttachment($file));
    }

    /**
     * 以下附件相关的代码都是临时代码，等待通用的附件方法开发好之后，重构
     * @param $data
     * @return mixed
     */
    private function addAttachment($data)
    {
        $attachmentId = $this->attachmentService->makeAttachmentId($this->own['user_id']);
        $md5FileName = $this->getMd5FileName($data['attachment_name']);
        $newFullFileName = $this->attachmentService->createCustomDir($attachmentId) . DIRECTORY_SEPARATOR . $md5FileName;
        rename($data['temp_src_file'], $newFullFileName); // 移动到新目录
        $fileType = pathinfo($newFullFileName, PATHINFO_EXTENSION);
        $attachmentPaths = $this->attachmentService->parseAttachmentPath($newFullFileName);
        $attachmentInfo = [
            "attachment_id" => $attachmentId,
            "attachment_name" => $data['attachment_name'],
            "affect_attachment_name" => $md5FileName,
            'new_full_file_name' => $newFullFileName,
            "thumb_attachment_name" => $this->generateImageThumb($fileType, null, $newFullFileName),
            "attachment_size" => filesize($newFullFileName),
            "attachment_type" => $fileType,
            "attachment_create_user" => $this->own['user_id'],
            "attachment_base_path" => $attachmentPaths[0],
            "attachment_path" => $attachmentPaths[1],
            "attachment_mark" => $this->getAttachmentMark($fileType),
            "relation_table" => 'document_content',
            "rel_table_code" => $this->getRelationTableCode('document_content'),
        ];
        $this->attachmentService->handleAttachmentDataTerminal($attachmentInfo);
        return $attachmentId;
    }

    private function getMd5FileName($gbkFileName)
    {
        $name = substr($gbkFileName, 0, strrpos($gbkFileName, "."));

        return md5(time() . $name) . strrchr($gbkFileName, '.');
    }

    private function generateImageThumb($fileType, $data, $sourcFile)
    {
        if (in_array($fileType, config('eoffice.uploadImages'))) {
            $thumbWidth = isset($data["thumbWidth"]) && $data["thumbWidth"] ? $data["thumbWidth"] : config('eoffice.thumbWidth', 100);
            $thumbHight = isset($data["thumbHight"]) && $data["thumbHight"] ? $data["thumbHight"] : config('eoffice.thumbHight', 40);
            $thumbPrefix = config('eoffice.thumbPrefix', "thumb_");
            return scaleImage($sourcFile, $thumbWidth, $thumbHight, $thumbPrefix);
        }

        return '';
    }

    private function getAttachmentMark($fileType)
    {
        $uploadFileStatus = config('eoffice.uploadFileStatus');

        foreach ($uploadFileStatus as $key => $status) {
            if (in_array(strtolower($fileType), $status)) {
                return $key;
            }
        }

        return 9;
    }

    private function getRelationTableCode($tableName)
    {
        return md5($tableName);
    }

    /**
     * 【流程运行】  验证流程数据
     *
     */
    public function validFileCondition()
    {
        $param = $this->request->all();
        if(!isset($param['run_id'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        if(!isset($param['flow_id'])) {
            return $this->returnResult(['code' => ['0x000003', 'common']]);
        }
        $result = app($this->flowRunService)->validFileCondition($param , $this->own);
        return $this->returnResult($result);
    }
     /**
     * 【流程定义】 通过flow_id获取flow_others信息
     *
     */
    public function getFlowOthersDetailByFlowId($flowId)
    {
        if(!isset($flowId)) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        $result = app($this->flowRunService)->getFlowOthersDetailByFlowId(  $flowId);
        if (empty( $result)) {
            return $this->returnResult(['code' => ['0x000002', 'common']]);
        }
        return $this->returnResult($result);
    }

    /**
     * 【控件收藏】
     *
     */
    public function saveControlCollection()
    {
        $param = $this->request->all();
        $result = app($this->flowControlService)->saveControlCollection($param , $this->own);
        return $this->returnResult(['data' =>$result]);
    }
    /**
     * 【控件收藏】
     *
     */
    public function getControlCollectionList()
    {
        $result = app($this->flowControlService)->getControlCollectionList();
        return $this->returnResult($result );
    }

    /**
     * 获取流程定时触发配置
     *
     * @since zyx 20200413
     */
    public function getFlowSchedulesByFlowId($flowId) {
        if (!isset($flowId) || !$flowId || !app($this->flowPermissionService)->verifyFlowSettingPermission($flowId,$this->own)) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }

        $result = app($this->flowSettingService)->getFlowSchedules($flowId);
        return $this->returnResult($result);
    }

    /**
     * 编辑流程定时触发配置
     *
     * @since zyx  20200414
     * @return void
     */
    public function editFlowSchedules() {
        $param = $this->request->all();
        $flow_id = $param['flow_id'] ?? 0;
        // 验证流程的编辑权限
        if (!$flow_id || !app($this->flowPermissionService)->verifyFlowSettingPermission($flow_id,$this->own)) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        // 执行修改操作
        $result = app($this->flowSettingService)->editFlowSchedules($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑开启调试模式
     *
     * @since wz
     * @return void
     */
    public function editFlowDefineDebug() {
        $param = $this->request->all();
        $flow_id = $param['flow_id'] ?? 0;
        // 验证流程的编辑权限
        if (!$flow_id || !app($this->flowPermissionService)->verifyFlowSettingPermission($flow_id,$this->own)) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        // 执行修改操作
        $result = app($this->flowSettingService)->editFlowDefineDebug($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取办理或收回可以处理的运行节点
     *
     * @author zyx
     * @return [array]
     */
    public function getFlowRunProcessListToDealWith() {
        $params = $this->request->all();
        $userInfo = $this->own;
        $result = app($this->flowRunService)->getFlowRunProcessListToDealWith($params, $userInfo);
		return $this->returnResult($result);
    }

    /**
     * 当处于强制合并节点时，获取其他并发分支上待办等信息
     * @return array
     */
    public function getFlowRunForceMergeInfo()
    {
        $params = $this->request->all();
        $userInfo = $this->own;
        $result = app($this->flowRunService)->getFlowRunForceMergeInfo($params, $userInfo);
        return $this->returnResult($result);
    }


	/*
     * 新增流程打印日志
     *
     * @author zyx
     * @since 20200506
     */
    public function addFlowRunPrintLog() {
        $param = $this->request->all();
        $result = app($this->flowPrintService)->addFlowRunPrintLog($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取流程打印日志
     *
     * @author zyx
     * @since 20200506
     * @return array
     */
    public function getFlowRunPrintLog() {
        $params = $this->request->all();
        if (!isset($params['run_id']) || empty($params['run_id'])) {
            return $this->returnResult(['code' => ['0x000001', 'common']]);
        }
        $result = app($this->flowPrintService)->getFlowRunPrintLog($params, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取用户有无某个表单的权限和表单设计的菜单权限
     *
     * @since wz
     */
    public function getFormEditPermission($formId ) {
        $own = $this->own;
        if (isset($own['menus']['menu']) && count(array_intersect([201] , $own['menus']['menu'])) > 0  ) {
            if ($formInfo = app($this->flowPermissionService)->verifyFormSettingPermission( $formId ,$own , 'parent' , true)) {
                 $result = ['permission' => true , 'form_type' =>$formInfo->form_type ?? 'complex' ];
            } else {
                $result = ['permission' => false];
            }
        } else {
            $result = ['permission' => false];
        }
        return $this->returnResult($result);
    }

    /**
     * 获取当前用户的流程打印规则列表，不验证流程编辑菜单权限和当前流程的编辑权限
     *
     * @author zyx
     * @return array
     */
    public function getUserFlowPrintRuleList() {
        $param = $this->request->all();
        if (!isset($param['flow_id'])) {
            return $this->returnResult([]);
        }

        $result = app($this->flowService)->getFlowTemplateRuleList($param);
        return $this->returnResult($result);
    }
}
