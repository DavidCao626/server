<?php

namespace App\EofficeApp\Performance\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Performance\Services\PerformanceService;
use App\EofficeApp\Performance\Requests\PerformanceRequest;

/**
 * 绩效考核模块控制器
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 *
 */
class PerformanceController extends Controller
{
    /**
     * [$request Request验证]
     *
     * @var [object]
     */
    protected $request;

    /**
     * [$performanceService 考核方案表Service]
     *
     * @var [object]
     */
    protected $performanceService;

    protected $performanceRequest;

    public function __construct(
        PerformanceService $performanceService,
        PerformanceRequest $performanceRequest,
        Request $request
    )
    {
        parent::__construct();

        $this->performanceService = $performanceService;
        $this->performanceRequest = $performanceRequest;
        $this->request = $request;

        $this->formFilter($request, $performanceRequest);
    }

    /**
     * [getMyPerform 获取当前人员及其所有有考核权限的人员信息]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]            [被考核人信息]
     */
    public function getMyPerform()
    {
        $params = $this->request->all();
        $userApprovers = $this->performanceService->getMyPerform($this->own['user_id'], $this->own['user_name'], $params);
        return $this->returnResult($userApprovers);
    }

    /**
     * [getPerformData 获取某个用户某个考核方案的某个月/季/半年/年的考核数据]
     *
     * @author 朱从玺
     *
     * @param  [int]     $userId [用户ID]
     *
     * @since  2015-10-26 创建模板
     *
     * @return [json]            [考核数据]
     */
    public function getPerformData($userId)
    {
        $param = $this->request->all();

        $result = $this->performanceService->getPerformData($userId, $param, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [getMyTemp 查询某个用户某种方案下的当前模板数据]
     *
     * @author 朱从玺
     *
     * @param  [int]     $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]            [模板数据]
     */
    public function getMyTemp($userId)
    {
        $planId = $this->request->input('plan_id');

        $result = $this->performanceService->getMyTemp($userId, $planId, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [createPerform 保存考核数据]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]        [保存结果]
     */
    public function createPerform()
    {
        $performData = $this->request->all();

        $result = $this->performanceService->createPerform($performData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [getPlanList 获取考核方案列表]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]      [查询结果]
     */
    public function getPlanList()
    {
        $result = $this->performanceService->getPlanList();

        return $this->returnResult($result);
    }

    /**
     * [getPlanInfo 获取考核方案数据]
     *
     * @author 朱从玺
     *
     * @param  [int]        $planId [考核方案ID]
     *
     * @since  2015-10-23 创建
     *
     * @return [json]               [查询结果]
     */
    public function getPlanInfo($planId)
    {
        $result = $this->performanceService->getPlanInfo($planId);

        return $this->returnResult($result);
    }

    /**
     * [modifyPlan 修改考核方案]
     *
     * @author 朱从玺
     *
     * @param  [int]      $planId [方案ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]             [修改结果]
     */
    public function modifyPlan($planId)
    {
        $newPlanData = $this->request->all();

        $result = $this->performanceService->modifyPlan($planId, $newPlanData);

        return $this->returnResult($result);
    }

    /**
     * [createTemp 创建模板]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                [创建结果]
     */
    public function createTemp()
    {
        $tempData = $this->request->all();

        $result = $this->performanceService->createTemp($tempData);

        return $this->returnResult($result);
    }

    /**
     * [getTempList 获取考核模板列表]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                [查询结果]
     */
    public function getTempList()
    {
        $params = $this->request->all();

        $tempList = $this->performanceService->getTempList($params);

        return $this->returnResult($tempList);
    }

    /**
     * [getTempList 获取考核模板列表10.5版]
     *
     * @since  2019-4-10 创建
     *
     * @return [json]                [查询结果]
     */
    public function getTempListNew()
    {
        $params = $this->request->all();

        $tempList = $this->performanceService->getTempListNew($params);

        return $this->returnResult($tempList);
    }

    /**
     * [getTempInfo 获取模板数据]
     *
     * @author 朱从玺
     *
     * @param  [int]       $tempId [模板ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]              [查询结果]
     */
    public function getTempInfo($tempId)
    {
        $tempInfo = $this->performanceService->getTempInfo($tempId);

        return $this->returnResult($tempInfo);
    }

    /**
     * [modifyTemp 编辑考核模板]
     *
     * @author 朱从玺
     *
     * @param  [int]      $tempId [模板ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [bool]             [编辑结果]
     */
    public function modifyTemp($tempId)
    {
        $newTempData = $this->request->all();

        $result = $this->performanceService->modifyTemp($tempId, $newTempData);

        return $this->returnResult($result);
    }

    /**
     * [copyTemp 复制模板]
     *
     * @author 朱从玺
     *
     * @param  [int]    $tempId [模板ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]           [复制结果]
     */
    public function copyTemp($tempId)
    {
        $result = $this->performanceService->copyTemp($tempId);

        return $this->returnResult($result);
    }

    /**
     * [deleteTemp 删除考核模板]
     *
     * @author 朱从玺
     *
     * @param  [int]      $tempId [模板ID]
     *
     * @since  2015-10-26
     *
     * @return [json]             [删除结果]
     */
    public function deleteTemp($tempId)
    {
        $result = $this->performanceService->deleteTemp($tempId);

        return $this->returnResult($result);
    }

    /**
     * [getNoPerformer 获取没有考核人的用户列表]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]         [查询结果]
     */
    public function getNoPerformer()
    {
        $result = $this->performanceService->getNoPerformer();

        return $this->returnResult($result);
    }

    /**
     * [getPerformer 获取用户考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]       $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                 [查询结果]
     */
    public function getPerformer($userId)
    {
        $performerInfo = $this->performanceService->getPerformer($userId);

        return $this->returnResult($performerInfo);
    }

    /**
     * [getApprover 获取用户被考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]       $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                 [查询结果]
     */
    public function getApprover($userId)
    {
        $result = $this->performanceService->getApprover($userId);

        return $this->returnResult($result);
    }

    /**
     * [makePerformerEmpty 清空考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                       [清空结果]
     */
    public function makePerformerEmpty($userId)
    {
        $result = $this->performanceService->makePerformerEmpty($userId);

        return $this->returnResult($result);
    }

    /**
     * [makeApproverEmpty 清空被考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                       [清空结果]
     */
    public function makeApproverEmpty($userId)
    {
        $result = $this->performanceService->makeApproverEmpty($userId);

        return $this->returnResult($result);
    }

    /**
     * [setPerformerDefault 设置指定用户的考核人为默认人员]
     *
     * @author 朱从玺
     *
     * @param  [string]              $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                        [设置结果]
     */
    public function setPerformerDefault($userId)
    {
        $result = $this->performanceService->setPerformerDefault($userId);

        return $this->returnResult($result);
    }

    /**
     * [setApproverDefault 设置指定用户的被考核人为默认人员]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                       [设置结果]
     */
    public function setApproverDefault($userId)
    {
        $result = $this->performanceService->setApproverDefault($userId);

        return $this->returnResult($result);
    }

    /**
     * [modifyPerformer 编辑用户考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]        $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                  [编辑结果]
     */
    public function modifyPerformer($userId)
    {
        $newPerformer = $this->request->all();

        $result = $this->performanceService->modifyPerformer($userId, $newPerformer);

        return $this->returnResult($result);
    }

    /**
     * [getStatisticList 获取考核统计列表]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]           [查询结果]
     */
    public function getStatisticList()
    {
        $param = $this->request->all();

        $result = $this->performanceService->getStatisticListAndCount($param);

        return $this->returnResult($result);
    }

    /**
     * [getStatisticUser 用户搜索]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                     [查询结果]
     */
    public function getStatisticUser()
    {
        $param = $this->request->all();

        $result = $this->performanceService->searchUser($param);

        return $this->returnResult($result);
    }

    /**
     * [getCurrentMonth 判断考核中的月度]
     *
     * @method 朱从玺
     *
     * @param  [string]          $circle [周期,months/seasons/halfYears/years]
     *
     * @return [string]                  [判断结果]
     */
    public function getCurrentMonth($circle)
    {
        $result = $this->performanceService->getCurrentMonth($circle,$this->request->all());

        return $this->returnResult($result);
    }

    public function getMonthClass(){
        return $this->returnResult($this->performanceService->getMonthClass($this->request->all()));
    }
    public function getSeasonClass(){
        return $this->returnResult($this->performanceService->getSeasonClass($this->request->all()));
    }
    public function getHalfYearClass(){
    	return $this->returnResult($this->performanceService->getHalfYearClass($this->request->all()));
    }
}
