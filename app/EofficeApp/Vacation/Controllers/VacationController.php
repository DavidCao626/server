<?php

namespace App\EofficeApp\Vacation\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Vacation\Services\VacationService;
use App\EofficeApp\Vacation\Requests\VacationRequest;
use App\EofficeApp\Vacation\Services\VacationLockService as Lock;
/**
 * 假期管理模块控制器
 *
 * @author  施奇
 *
 * @since   2016-08-10
 *
 */
class VacationController extends Controller
{
    /**
     * [$vacationService 假期管理模块service]
     *
     * @var [object]
     */
    protected $vacationService;

    /**
     * [$request request传参]
     *
     * @var [object]
     */
    protected $request;

    /**
     * [$vacationRequest 假期管理表单验证]
     *
     * @var [object]
     */
    protected $vacationRequest;

    public function __construct(
        VacationService $vacationService,
        VacationRequest $vacationRequest,
        Request $request
    )
    {
        parent::__construct();

        $this->vacationService = $vacationService;
        $this->vacationRequest = $vacationRequest;
        $this->request = $request;

        $this->formFilter($request, $vacationRequest);
    }

    /**
     * [createVacation 创建假期类型]
     *
     * @author 施奇
     *
     * @return [bool]         [创建结果]
     */
    public function createVacation()
    {
        $vacationData = $this->request->all();

        $result = $this->vacationService->createVacation($vacationData);

        return $this->returnResult($result);
    }

    /**
     * [modifyVacation 编辑假期类型]
     *
     * @author 施奇
     *
     * @param  [int]          $vacationId [假期类型ID]
     *
     * @return [bool]                     [编辑结果]
     */
    public function modifyVacation($vacationId)
    {
        $lock=new Lock('modifyVacation');
        $lock->lock();
        $newVacationData = $this->request->all();
        $result = $this->vacationService->modifyVacation($vacationId, $newVacationData);
        $lock->unLock();
        return $this->returnResult($result);
    }

    public function modifySet()
    {
        $lock=new Lock('modifySet');
        $lock->lock();
        $newVacationData = $this->request->all();
        $result = $this->vacationService->modifySet($newVacationData);
        $lock->unLock();
        return $this->returnResult($result);
    }

    public function modifySetScale()
    {
        $lock=new Lock('modifySetScale');
        $lock->lock();
        $newVacationData = $this->request->all();
        $newVacationData['creator'] = $this->own['user_id'];
        $result = $this->vacationService->modifySetScale($newVacationData);
        $lock->unLock();
        return $this->returnResult($result);
    }

    /**
     * [getVacationList 获取假期列表]
     *
     * @author 施奇
     *
     * @return [array]                  [查询结果]
     */
    public function getVacationList()
    {
        $params = $this->request->all();

        $vacationList = $this->vacationService->getVacationList($params);

        return $this->returnResult($vacationList);
    }

    /**
     * [deleteVacation 删除假期类型]
     *
     * @author 施奇
     *
     * @param  [int]          $vacationId [假期类型ID]
     *
     * @return [bool]                     [删除结果]
     */
    public function deleteVacation($vacationId)
    {
        $result = $this->vacationService->deleteVacation($vacationId);

        return $this->returnResult($result);
    }

    /**
     * [getVacationData 获取假期数据]
     *
     * @author 施奇
     *
     * @param  [int]             $vacationId [假期ID]
     *
     * @return [object]                      [获取结果]
     */
    public function getVacationData($vacationId)
    {
        $vacationData = $this->vacationService->getVacationData($vacationId);

        return $this->returnResult($vacationData);
    }

    /**
     * [getUserVacationList 获取用户假期列表]
     *
     * @author 施奇
     *
     * @return [array]                      [查询结果]
     */
    public function getUserVacationList()
    {
        $params = $this->request->all();

        $userVacationList = $this->vacationService->getUserVacationList($params);

        return $this->returnResult($userVacationList);
    }

    /**
     * [getUserVacationData 获取用户假期]
     *
     * @author 施奇
     *
     * @param  [string]          $userId [用户ID]
     *
     * @return [array]                   [查询结果]
     */
    public function getUserVacationData($userId)
    {
        $userVacation = $this->vacationService->getUserVacationData($userId);

        return $this->returnResult($userVacation);
    }

    /**
     * [modifyUserVacation 编辑用户假期]
     *
     * @author 施奇
     *
     * @param  [string]             $userId [用户ID]
     *
     * @return [bool]                       [编辑结果]
     */
    public function modifyUserVacation($userId)
    {
        $userVacationData = $this->request->all();

        $result = $this->vacationService->modifyUserVacation($userId, $userVacationData);

        return $this->returnResult($result);
    }

    /**
     * [deleteUserLastVacation 删除用户上周期假期]
     *
     * @author 施奇
     *
     * @param  [string]                 $userId [用户ID]
     *
     * @return [bool]                           [删除结果]
     */
    public function deleteUserLastVacation($userId)
    {
        $vacationArray = $this->request->all();

        $result = $this->vacationService->deleteUserLastVacation($userId, $vacationArray);

        return $this->returnResult($result);
    }

    /**
     * [multiSetUserVacation 批量设置用户假期]
     *
     * @author 施奇
     *
     * @return [bool]               [设置结果]
     */
    public function multiSetUserVacation()
    {
        $setData = $this->request->all();

        $result = $this->vacationService->multiSetUserVacation($setData);

        return $this->returnResult($result);
    }

    /**
     * [getUserVacationDays 获取用户某个假期剩余天数]
     *
     * @method 施奇
     *
     * @param  [string]              $userId     [用户ID]
     * @param  [int]                 $vacationId [假期ID]
     *
     * @return [array]                           [获取结果]
     */
    public function getUserVacationDays($userId, $vacationId)
    {
        $result = $this->vacationService->getUserVacationDays($userId, $vacationId);

        return $this->returnResult($result);
    }

    public function getUserVacationDaysByName($userId, $vacationName)
    {
        $result = $this->vacationService->getUserVacationDaysByName($userId, $vacationName);

        return $this->returnResult($result);
    }

    public function sortVacation()
    {
        $result = $this->vacationService->sortVacation($this->request->all());

        return $this->returnResult($result);
    }

    public function getMineVacationData($mine)
    {
        $userVacation = $this->vacationService->getMineVacationData($mine == 'mine' ? $this->own['user_id'] : $mine, $this->request->all());

        return $this->returnResult($userVacation);
    }

    public function getMineVacationUsedRecord($mine)
    {
        $res = $this->vacationService->getMineVacationUsedRecord($mine == 'mine' ? $this->own['user_id'] : $mine, $this->request->all());

        return $this->returnResult($res);
    }

    public function getMineVacationExpireRecord($mine){

        $res = $this->vacationService->getExpireRecords($mine == 'mine' ? $this->own['user_id'] : $mine);

        return $this->returnResult($res);
    }

    public function getVacationSet(){

        $res = $this->vacationService->getVacationSetData();

        return $this->returnResult($res);
    }

    public function getVacationHistory(){
        $params = $this->request->all();
        $res = $this->vacationService->getVacationHistoryData($params);
        return $this->returnResult($res);
    }
}