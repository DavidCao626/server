<?php

namespace App\EofficeApp\Vehicles\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Vehicles\Requests\VehiclesRequest;
use App\EofficeApp\Vehicles\Services\VehiclesService;

/**
 * 用车控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class VehiclesController extends Controller {

    public function __construct(
        Request $request,
        VehiclesService $vehiclesService,
        VehiclesRequest $vehiclesRequest
    ) {
        parent::__construct();
        $this->vehiclesService = $vehiclesService;
        $this->vehiclesRequest = $vehiclesRequest;
        $this->request = $request;
        $this->formFilter($request, $vehiclesRequest);
    }

    /**
     * 获取用车列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since  2015-10-22
     */
    public function getAllVehicles() {
        $param = $this->request->all();
        $result = $this->vehiclesService->getAllVehicles($param, $this->own);
        return $this->returnResult($result);
    }
    public function getAllNoAuthVehicles() {
        $result = $this->vehiclesService->getAllNoAuthVehicles($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 待审批的状态改变为审批中
     *
     * @return boolean
     */
    public function setVehiclesApply($vehiclesApplyId){
        $result = $this->vehiclesService->setVehiclesApply($vehiclesApplyId);
        return $this->returnResult($result);
    }

    /**
     * 用车日历
     *
     * @return array
     */
    public function getVehiclesCalendar() {
        $input = $this->request->all();
        $vehiclesCalendarList = $this->vehiclesService->getVehiclesCalendar($input);
        return $this->returnResult($vehiclesCalendarList);
    }

    /**
     * getOne 车子详细
     *
     * @return array
     */
    public function getOneVehiclesList() {
        $result = $this->vehiclesService->getVehiclesList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 申请详情
     *
     *  @return array
     */
    public function getOneVehicleApply() {
        $result = $this->vehiclesService->getOneVehicleApply($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 维护详情
     *
     *  @return array
     */
    public function getOneVehiclesMaintenance() {
        $result = $this->vehiclesService->getOneVehiclesMaintenance($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 我的用车记录
     */
    public function getAllVehiclesByUser() {
        $param = $this->request->all();
        $param['vehicles_apply_apply_user'] = $this->own['user_id'];
        $result = $this->vehiclesService->getAllVehiclesByUser($param);
        return $this->returnResult($result);
    }
    public function getMyVehiclesDetailForCustom() {
        $result = $this->vehiclesService->getMyVehiclesDetailForCustom($param);
        return $this->returnResult($result);
    }

    /**
     * 获取用户审批列表
     *
     * @return array
     */
    public function vehiclesApprovalList() {
        $param = $this->request->all();
        $result = $this->vehiclesService->vehiclesApprovalList($param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 用车审批 通过|拒绝|验收
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function vehiclesApproval() {
        $result = $this->vehiclesService->vehiclesApproval($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 用车审批催促
     *
     * @return bool
     *
     * @author 李旭
     */
    public function urgeVehiclesApply() {
        $result = $this->vehiclesService->urgeVehiclesApply($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 用车归还
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function vehiclesReturn() {
        $result = $this->vehiclesService->vehiclesReturn($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 增加车辆
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function addVehicles() {
        $result = $this->vehiclesService->addVehicles($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑车辆
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function editVehicles() {
        $result = $this->vehiclesService->editVehicles($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除车辆 --- deleted = 1 -- 标识
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     *
     */
    public function deleteVehicles($vehiclesId) {
        $result = $this->vehiclesService->deleteVehicles($vehiclesId);
        return $this->returnResult($result);
    }

    /**
     * 用车申请 新建
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function addVehiclesApply() {
        $result = $this->vehiclesService->addVehiclesApply($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 用车编辑
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function editVehiclesApply() {
        $result = $this->vehiclesService->editVehiclesApply($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除我的用车申请记录
     *
     * @return bool
     *
     * @author miaochenchen
     *
     * @since 2016-08-04
     */
    public function deleteOwnVehiclesApply($vehiclesApplyId) {
        $result = $this->vehiclesService->deleteOwnVehiclesApply($vehiclesApplyId);
        return $this->returnResult($result);
    }

    /**
     * 删除用车审批的申请记录
     *
     * @return bool
     *
     * @author miaochenchen
     *
     * @since 2016-08-04
     */
    public function deleteApprovalVehiclesApply($vehiclesApplyId) {
        $result = $this->vehiclesService->deleteApprovalVehiclesApply($vehiclesApplyId);
        return $this->returnResult($result);
    }

    /**
     * 删除车辆管理里的用车申请记录
     *
     * @return bool
     *
     * @author miaochenchen
     *
     * @since 2016-08-04
     */
    public function deleteVehiclesManageApplyRecord($vehiclesApplyId) {
        $result = $this->vehiclesService->deleteVehiclesManageApplyRecord($vehiclesApplyId);
        return $this->returnResult($result);
    }

    /**
     * 登记维护（车辆）
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function addVehiclesMaintenance() {
        $result = $this->vehiclesService->addVehiclesMaintenance($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑维护信息
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function editVehiclesMaintenance() {
        $result = $this->vehiclesService->editVehiclesMaintenance($this->request->all());
        return $this->returnResult($result);
    }

    /**
     *
     * 删除维护信息（结束维护的车子）
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function deleteVehiclesMaintenance($vehiclesMaintenanceId) {
        $result = $this->vehiclesService->deleteVehiclesMaintenance($vehiclesMaintenanceId);
        return $this->returnResult($result);
    }

    public function endVehiclesMaintenance($vehiclesMaintenanceId) {
        $result = $this->vehiclesService->endVehiclesMaintenance($vehiclesMaintenanceId);
        return $this->returnResult($result);
    }

    /**
     *
     * 获取审批列表冲突车辆的数量用于冲突标签徽章
     *
     * @return json
     *
     * @author miaochenchen
     *
     * @since 2016-06-02
     */
    public function getVehiclesConflictEmblem() {
        $param = $this->request->all();
        $param['user_id'] = $this->own['user_id'];
        return $this->vehiclesService->getVehiclesConflictEmblem($param);
    }

    /**
     *
     * 获取审批人列表
     *
     * @return json
     *
     * @author miaochenchen
     *
     * @since 2016-09-20
     */
    public function getVehiclesApprovalUserList() {
        $result = $this->vehiclesService->getVehiclesApprovalUserList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新建用车申请前判断申请时间段是否与现有用车有冲突
     *
     * @return boolean
     *
     * @author miaochenchen
     *
     * @since 2016-11-02
     */
    public function getNewVehiclesDateWhetherConflict() {
        $result = $this->vehiclesService->getNewVehiclesDateWhetherConflict($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /**
     * 编辑用车申请前判断申请时间段是否与现有用车有冲突
     *
     * @return boolean
     *
     */
    public function getVehiclesConflict() {
        $result = $this->vehiclesService->getVehiclesConflict($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取日期时间范围内所有车辆申请列表
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since 2016-11-15
     */
    public function getAllVehiclesApplyList() {
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        $result = $this->vehiclesService->getAllVehiclesApplyList($param);
        return $this->returnResult($result);
    }

    /**
     * 获取所有车牌号列表
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since 2016-12-02
     */
    public function getAllVehiclesCodeList() {
        $result = $this->vehiclesService->getAllVehiclesCodeList();
        return $this->returnResult($result);
    }

    /**
     * 判断是否有用车详情的查看权限
     * @param string $vehiclesApplyId
     * @return boolean
     */
    public function getVehiclesDetailPermissions() {
        return $this->returnResult($this->vehiclesService->getVehiclesDetailPermissions($this->request->all(), $this->own));
    }

    /**
     * 获取车辆使用情况表格
     * @param array
     * @return string
     */
    public function getVehiclesUsageTable() {
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        return $this->returnResult($this->vehiclesService->getVehiclesUsageTable($param));
    }

    /**
     * 根据车辆名称获取车辆ID
     * @param array
     * @return string
     */
    public function getVehiclesIdByVehiclesName() {
        $result = $this->vehiclesService->getVehiclesIdByVehiclesName($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 添加车辆分类
     */
    public function addVehiclesSort() {
        $result = $this->vehiclesService->addVehiclesSort($this->request->all());
        return $this->returnResult($result);
    }

    public function getSort() {
        return $this->returnResult($this->vehiclesService->getSort($this->request->all()));
    }

    /**
     * 获取车辆分类详情
     */
    public function getVehiclesSortDetail($vehiclesSortId) {
        return $this->returnResult($this->vehiclesService->getVehiclesSortDetail($vehiclesSortId));
    }
    public function editVehiclesSortDetail($vehiclesSortId) {
        $data = $this->request->all();
        $result = $this->vehiclesService->editVehiclesSortDetail($data,$vehiclesSortId);
        return $this->returnResult($result);
    }
    /**
     * 删除车辆分类
     */
    public function deleteVehiclesSort($vehiclesSortId) {
        $result = $this->vehiclesService->deleteVehiclesSort($vehiclesSortId);
        return $this->returnResult($result);
    }
    public function getPermessionVehiclesSort(){
        $data = $this->request->all();
        $result = $this->vehiclesService->getPermessionVehiclesSort($data);
        return $this->returnResult($result);
    }

    public function getVehiclesNameCode(){
        $result = $this->vehiclesService->getVehiclesNameCode($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取车辆保险
     */
    public function getVehiclesInsurance()
    {
        $result = $this->vehiclesService->getVehiclesInsurance($this->request->all());

        return $this->returnResult($result);
    }

    /**
     * 添加车辆保险
     */
    public function addVehiclesInsurance()
    {
        $result = $this->vehiclesService->addVehiclesInsurance($this->request->all());

        return $this->returnResult($result);
    }

    /**
     * 编辑车辆保险
     */
    public function editVehiclesInsurance()
    {
        $result = $this->vehiclesService->editVehiclesInsurance($this->request->all());

        return $this->returnResult($result);
    }

    /**
     * 删除车辆保险
     */
    public function deleteVehiclesInsurance($vehiclesInsuranceId)
    {
        $result = $this->vehiclesService->deleteVehiclesInsurance($vehiclesInsuranceId);

        return $this->returnResult($result);
    }

    /**
     * 获取车辆年检
     */
    public function getVehiclesAnnualInspection()
    {
        $result = $this->vehiclesService->getVehiclesAnnualInspection($this->request->all());

        return $this->returnResult($result);
    }

    /**
     * 添加车辆年检
     */
    public function addVehiclesAnnualInspection()
    {
        $result = $this->vehiclesService->addVehiclesAnnualInspection($this->request->all());

        return $this->returnResult($result);
    }

    /**
     * 编辑车辆年检
     */
    public function editVehiclesAnnualInspection()
    {
        $result = $this->vehiclesService->editVehiclesAnnualInspection($this->request->all());

        return $this->returnResult($result);
    }

    /**
     * 删除车辆年检
     */
    public function deleteVehiclesAnnualInspection($vehiclesAnnualInspectionId)
    {
        $result = $this->vehiclesService->deleteVehiclesAnnualInspection($vehiclesAnnualInspectionId);

        return $this->returnResult($result);
    }

    /**
     * 结束车辆年检
     */
    public function endVehiclesAnnualInspection($vehiclesAnnualInspectionId)
    {
        $result = $this->vehiclesService->endVehiclesAnnualInspection($vehiclesAnnualInspectionId);
        return $this->returnResult($result);
    }

    /**
     * 获取车辆保险/年检消息通知配置
     */
    public function getInsuranceNotifyConfig()
    {
        $result = $this->vehiclesService->getInsuranceNotifyConfig($this->request->all());

        return $this->returnResult($result);
    }
    /**
     * 更新车辆保险/年检消息通知配置
     */
    public function updateInsuranceNotifyConfig()
    {
       $result = $this->vehiclesService->updateInsuranceNotifyConfig($this->request->all());

        return $this->returnResult($result);
    }
}
