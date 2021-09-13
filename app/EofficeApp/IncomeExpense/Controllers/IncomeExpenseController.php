<?php
namespace App\EofficeApp\IncomeExpense\Controllers;

use App\EofficeApp\IncomeExpense\Services\IncomeExpenseService;
use App\EofficeApp\IncomeExpense\Requests\IncomeExpenseRequest;
use \Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
/**
 * 收支模块控制器，用于收支模块前端和后端数据层的调度。
 * 
 * @author 李志军
 * 
 * @since 2015-10-16 
 */
class IncomeExpenseController extends Controller
{
	/** @var object 收支模块服务类对象 */
	private $incomeExpenseService;
	
	/**
	 * 注册收支模块服务对象
	 * 
	 * @param \App\EofficeApp\IncomeExpense\Services\IncomeExpenseService $incomeExpenseService
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function __construct(
            IncomeExpenseService $incomeExpenseService,
            Request $request,
            IncomeExpenseRequest $incomeExpenseRequest
            ) 
	{
		parent::__construct();
		
		$this->incomeExpenseService = $incomeExpenseService;
        $this->request = $request;
        $this->formFilter($request, $incomeExpenseRequest);
	}
	/**
	 * 获取收支方案类型列表
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 收支方案类型列表
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function listPlanType()
	{
		return $this->returnResult($this->incomeExpenseService->listPlanType($this->request->all()));
	}
	/**
	 * 新建方案类型
	 * 
	 * @param \App\Http\Requests\IncomeExpenseRequest $request
	 * 
	 * @return json 方案类型id
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function addPlanType()
	{
		return $this->returnResult($this->incomeExpenseService->addPlanType($this->request->all()));
	}
	/**
	 * 编辑方案类型
	 * 
	 * @param \App\Http\Requests\IncomeExpenseRequest $request
	 * 
	 * @param int $planTypeId 方案类别id
	 * 
	 * @return json 编辑结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function editPlanType($planTypeId)
	{
		return $this->returnResult($this->incomeExpenseService->editPlanType($this->request->all(), $planTypeId));
	}
	/**
	 * 获取方案类别详情
	 * 
	 * @param int $planTypeId 方案类别id
	 * 
	 * @return json 方案类别详情
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function showPlanType($planTypeId)
	{
		return $this->returnResult($this->incomeExpenseService->showPlanType($planTypeId));
	}
	/**
	 * 删除方案类型
	 * 
	 * @param int $planTypeId
	 * 
	 * @return json 删除结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function deletePlanType($planTypeId)
	{
		return $this->returnResult($this->incomeExpenseService->deletePlanType($planTypeId,$this->own));
	}
	/**
	 * 获取方案列表
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 方案列表
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function listPlan()
	{
		return $this->returnResult($this->incomeExpenseService->listPlan($this->request->all(),$this->own));
	}
	public function getListPlan()
	{
		return $this->returnResult($this->incomeExpenseService->listPlan($this->request->all(),$this->own));
	}
	public function getProceedListPlan()
	{
		return $this->returnResult($this->incomeExpenseService->getProceedListPlan($this->request->all(),$this->own));
	}
	public function listAllPlan()
	{
		return $this->returnResult($this->incomeExpenseService->listAllPlan($this->request->all(),$this->own));
	}
	/**
	 * 新建收支方案
	 * 
	 * @param \App\Http\Requests\IncomeExpenseRequest $request
	 * 
	 * @return json 方案id
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function addPlan()
	{
		return $this->returnResult($this->incomeExpenseService->addPlan($this->request->all(), $this->own['user_id']));
	}
	/**
	 * 获取方案编号
	 * 
	 * @return json 方案编号
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function getPlanCode()
	{
		return $this->returnResult($this->incomeExpenseService->getPlanCode());
	}
	/**
	 * 编辑收支方案
	 * 
	 * @param \App\Http\Requests\IncomeExpenseRequest $request
	 * 
	 * @param int $planId 方案id
	 * 
	 * @return json 编辑结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function editPlan($planId)
	{
		return $this->returnResult($this->incomeExpenseService->editPlan($this->request->all(), $planId));
	}

	public function showEditPlan($planId)
	{
		return $this->returnResult($this->incomeExpenseService->showEditPlan($planId));
	}
	/**
	 * 获取方案详情
	 * 
	 * @param int $planId 方案id
	 * 
	 * @return json 方案详情
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16 
	 */
	public function showPlan($planId)
	{
		return $this->returnResult($this->incomeExpenseService->showPlan($planId,$this->own));
	}
	/**
	 * 删除收支方案
	 * 
	 * @param int $planId 方案id
	 * 
	 * @return json 删除结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function deletePlan($planId)
	{
		return $this->returnResult($this->incomeExpenseService->deletePlan($planId));
	}
	/**
	 * 获取假删除方案列表
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 假删除方案列表
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function listTrashedPlan()
	{
		return $this->returnResult($this->incomeExpenseService->listTrashedPlan($this->request->all()));
	}
	/**
	 * 恢复软删除的方案
	 * 
	 * @param string $planId
	 * 
	 * @return json 删除结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-27
	 */
	public function recoverTrashedPlan($planId)
	{
		return $this->returnResult($this->incomeExpenseService->recoverTrashedPlan($planId));
	}
	/**
	 * 彻底销毁软删除的方案
	 * 
	 * @param string $planId
	 * 
	 * @return json 销毁结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-27
	 */
	public function destroyTrashedPlan($planId)
	{
		return $this->returnResult($this->incomeExpenseService->destroyTrashedPlan($planId));
	}
	/**
	 * 获取收支记录列表
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 收支记录列表
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function listRecord()
	{
		return $this->returnResult($this->incomeExpenseService->listRecord($this->request->all(),$this->own));
	}
	/**
	 * 新建收支记录
	 * 
	 * @param \App\Http\Requests\IncomeExpenseRequest $request
	 * 
	 * @return json 记录id
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function addRecord()
	{
		return $this->returnResult($this->incomeExpenseService->addRecord($this->request->all(),$this->own['user_id']));
	}
	/**
	 * 新建外发收支记录
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 记录id
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function addOutSendRecord(){
       return $this->returnResult($this->incomeExpenseService->addRecord($this->request->all()));
    }
	/**
	 * 编辑外发收支记录
	 * 
	 * @param \App\Http\Requests\IncomeExpenseRequest $request
	 * 
	 * @param int $recordId 记录id
	 * 
	 * @return json 编辑结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function editRecord($recordId)
	{
		return $this->returnResult($this->incomeExpenseService->editRecord($this->request->all(), $this->request->file('upload_file'), $recordId,$this->own['user_id']));
	}
	/**
	 * 获取记录详情
	 * 
	 * @param int $recordId 记录id
	 * 
	 * @return json 记录详情
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function showRecord($recordId)
	{
		return $this->returnResult($this->incomeExpenseService->showRecord($recordId));
	}
	/**
	 * 删除收支记录
	 * 
	 * @param int $recordId 记录id
	 * 
	 * @return json 删除结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function deleteRecord($recordId)
	{
		return $this->returnResult($this->incomeExpenseService->deleteRecord($recordId ,$this->own['user_id']));
	}
	/**
	 * 收支统计
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function planStat()
	{
		return $this->returnResult($this->incomeExpenseService->planStat($this->request->all(),$this->own['user_id']));
	}
	/**
	 * 分别统计收支方案
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function planDiffStat()
	{
		return $this->returnResult($this->incomeExpenseService->planDiffStat($this->request->all(),$this->own['user_id']));
	}
	/**
	 * 导出统计数据
	 * 
	 * @param \Illuminate\Http\Request $request
	 * 
	 * @return json 导出结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function exportPlanStat()
	{
		return $this->returnResult($this->incomeExpenseService->exportPlanStat($this->request->all(),$this->own['user_id']));
	}
	/**
	 * 启动方案
	 * 
	 * @param type $planId
	 * 
	 * @return 启动结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function beginPlan($planId) 
	{
		return $this->returnResult($this->incomeExpenseService->beginPlan($planId));
	}
	/**
	 * 结束方案
	 * 
	 * @param type $planId
	 * 
	 * @return 结束结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-16
	 */
	public function endPlan($planId)
	{
		return $this->returnResult($this->incomeExpenseService->endPlan($planId));
	}
}
