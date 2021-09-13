<?php

namespace App\EofficeApp\Vehicles\Requests;

use App\EofficeApp\Base\Request;

class VehiclesRequest extends Request {

    public $errorCode = '0x021001';

    public function rules($request) {
        $vehiclesId = isset($request->route()[2]['vehiclesId']) ? $request->route()[2]['vehiclesId'] : '';
        $rules = [
            'addVehicles' => [
                'vehicles_name' => 'required|max:255',
                'vehicles_code' => 'required|max:32|unique:vehicles,vehicles_code'
            ],
            //编辑车辆
            'editVehicles' => [
                'vehicles_id'   => 'required|integer',
                'vehicles_name' => 'required|max:255',
                'vehicles_code' => 'required|max:32|unique:vehicles,vehicles_code,'.$vehiclesId.',vehicles_id'
            ],
            //删除车辆
            'deleteVehicles' => [
                'vehicles_id'   => 'required'
            ],
            // 用车申请
            'addVehiclesApply' => [
                'vehicles_id'                  => 'required|integer', // 申请那辆车
                'vehicles_apply_approval_user' => 'required', //审核
                'vehicles_apply_apply_user'    => 'required', // 登录用户
                'vehicles_apply_begin_time'    => 'required|date',
                'vehicles_apply_end_time'      => 'required|date' // 结束时间
            ],
            // 用车申请编辑
            'editVehiclesApply' => [
                'vehicles_apply_id'            => 'required|integer', // 申请那辆车
                'vehicles_id'                  => 'required|integer', // 申请那辆车
                'vehicles_apply_approval_user' => 'required', //审核
                'vehicles_apply_apply_user'    => 'required', // 登录用户
                'vehicles_apply_begin_time'    => 'required|date',
                'vehicles_apply_end_time'      => 'required|date' // 结束时间
            ],
            // 车子维护
            // 'addVehiclesMaintenance' => [
            //     'vehicles_id'                     => 'required|integer',
            //     // 'vehicles_maintenance_begin_time' => 'required|date', //
            //     // 'vehicles_maintenance_end_time'   => 'required|date' // 结束时间
            // ],
            // 编辑维护信息
            'editVehiclesMaintenance' => [
                'vehicles_maintenance_id'         => 'required|integer',
                'vehicles_id'                     => 'required|integer',
//                'vehicles_maintenance_begin_time' => 'required|date', //
//                'vehicles_maintenance_end_time'   => 'required|date' // 结束时间
            ],
            // 编辑保险信息
            'editVehiclesInsurance' => [
                'vehicles_insurance_id'         => 'required|integer',
                'vehicles_id'                     => 'required|integer',
//                'vehicles_insurance_begin_time' => 'required|date', //
//                'vehicles_insurance_end_time'   => 'required|date' // 结束时间
            ],
            // 编辑年检信息
            'editVehiclesAnnualInspection' => [
                'vehicles_annual_inspection_id'         => 'required|integer',
                'vehicles_id'                     => 'required|integer',
//                'vehicles_annual_inspection_begin_time' => 'required|date', //
//                'vehicles_annual_inspection_end_time'   => 'required|date' // 结束时间
            ],
            //用车日历
            'getVehiclesCalendar' => [
                'vehicles_id' => 'required|integer'
            ],
            //点开某个车辆的详情
            'getOneVehiclesInfo' => [
                'id'   => 'required|integer', //
                'type' => 'required|in:vehiclesRecode,vehiclesMaintenance,vehiclesInfo' // 申请查看或者维护查看
            ],
            'getAllVehiclesByUser' => [
                'vehicles_apply_apply_user' => 'required',
            ],
            // 用车管理：用车记录 维护记录 详情
            'getOneVehiclesList' => [
                'vehicles_id' => 'required|integer',
                'type'        => 'required|in:vehiclesRecode,vehiclesMaintenance,vehiclesInfo'
            ],
            //用车审批
            'vehiclesApproval' => [
                'user_id'           => 'required', //当前登录的用户
                'op'                => 'required|in:approval,forbidden,checkVehicles,checkReject', // 对应 批准，拒绝，验收
                'vehicles_apply_id' => 'required|integer' // 申请记录ID
            ],
            //用车归还
            'vehiclesReturn' => [
                'user_id'           => 'required', //当前登录的用户
                'vehicles_apply_id' => 'required' // 申请记录ID
            ],
            //用车审批列表
            'vehiclesApprovalList' => [
                'user_id' => 'required'//当前登录的用户
            ],
            'getOneVehicleApply' => [
                'vehicles_apply_id' => 'required|integer'
            ],
            'getOneVehiclesMaintenance' => [
                'vehicles_maintenance_id' => 'required|integer'
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
