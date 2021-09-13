<?php
$routeConfig = [
    // 新建车辆
    ['vehicles/add', 'addVehicles', 'post', [604]],
    ['vehicles/urge', 'urgeVehiclesApply', 'post'],
    // 没找到这个路由哪里有用到
    ['vehicles/get-name-code', 'getVehiclesNameCode'],
    // 编辑车辆
    ['vehicles/{vehicles_id}', 'editVehicles', 'put', [604]],
    // 删除车辆
    ['vehicles/{vehiclesId}', 'deleteVehicles', 'delete', [604]],
    // 新增用车申请
    ['vehicles/apply/add', 'addVehiclesApply', 'post', [601,606]],
    // 删除我的用车申请
    ['vehicles/apply/own/{vehiclesApplyId}', 'deleteOwnVehiclesApply', 'delete', [602]],
    // 删除我审批的车辆
    ['vehicles/apply/approval/{vehiclesApplyId}', 'deleteApprovalVehiclesApply', 'delete', [603]],
    // 删除车辆管理下的用车申请(有用车管理菜单即可删除)
    ['vehicles/apply/manage/{vehiclesApplyId}', 'deleteVehiclesManageApplyRecord', 'delete', [604]],
    // 设置待审核申请的审批查看时间
    ['vehicles/set-vehicles-apply/{vehiclesApplyId}', 'setVehiclesApply', 'put', [603]],
    // 添加车辆维护(有菜单即可操作)
    ['vehicles/maintenance/add', 'addVehiclesMaintenance', 'post', [604]],
    // 编辑车辆维护(有菜单即可操作)
    ['vehicles/maintenance/{vehicles_maintenance_id}', 'editVehiclesMaintenance', 'put', [604]],
    // 删除车辆维护(有菜单即可操作)
    ['vehicles/maintenance/{vehiclesMaintenanceId}', 'deleteVehiclesMaintenance', 'delete', [604]],
    // 结束用车维护(有菜单即可操作)
    ['vehicles/maintenance/end/{vehiclesMaintenanceId}', 'endVehiclesMaintenance', 'put', [604]],
    // 获取所有车辆, 车辆管理列表
    ['vehicles/list', 'getAllVehicles'],
    // 获取所有用车, 没有权限控制
    ['vehicles/get-list', 'getAllNoAuthVehicles', [605,603,604]],
    // 没有用到
    ['vehicles/get-all', 'getAll'],
    // 用车日历
    ['vehicles/getCalendar', 'getVehiclesCalendar', [601]],
    // 查兰我的申请(没用到)
    ['vehicles/vehicles-apply/my', 'getAllVehiclesByUser', [602]],
    ['vehicles/vehicles-apply/get-my-for-custom', 'getMyVehiclesDetailForCustom'],
    // 用车详细情况
    ['vehicles/list_info', 'getOneVehiclesList', [604, 606]],
    // 用车审批列表
    ['vehicles/vehicles-apply/list', 'vehiclesApprovalList', [603,605]],
    // 审核用车申请
    ['vehicles/vehicles-apply/set', 'vehiclesApproval', 'post', [603]],
    // 申请记录详细
    ['vehicles/vehicles-info/apply', 'getOneVehicleApply'],
    // 用车维护详情
    ['vehicles/vehicles-info/maintenance', 'getOneVehiclesMaintenance', [601, 604]],
    // 用车归还
    ['vehicles/vehicles-apply/return', 'vehiclesReturn', 'post', [602]],
    // 获取用车申请冲突数量
    ['vehicles/vehicles-approval/conflict/emblem', 'getVehiclesConflictEmblem', [603]],
    // 编辑用车申请(用車申請有調用, 但是入口關閉了)
    ['vehicles/apply/{vehicles_apply_id}', 'editVehiclesApply', 'put', [602]],
    // 获取用车审批人
    ['vehicles/vehicles-approval-user', 'getVehiclesApprovalUserList', [601, 606, 602]],
    // 获取指定日期内是否有用车冲突,(用于申请时判断)
    ['vehicles/get-date-whether-conflict', 'getNewVehiclesDateWhetherConflict', [601, 606, 602]],
    // 新建用车申请前判断申请时间段是否与现有用车有冲突
    ['vehicles/get-vehicles-conflict', 'getVehiclesConflict', [602]],
    // 获取所有的用车申请列表
    ['vehicles/get-all-vehicles-apply-list', 'getAllVehiclesApplyList', [601, 606]],
    // 获取所有车牌号列表
    ['vehicles-code/list', 'getAllVehiclesCodeList'],
    // 断是否有用车详情或用车维护的查看权限
    ['vehicles/vehicles-detail-permissions', 'getVehiclesDetailPermissions', 'post', [601,606]],
    // 获取用车分析列表
    ['vehicles/get-vehicles-usage', 'getVehiclesUsageTable', [606]],
    // 获取车辆名称
    ['vehicles/vehicles/get-vehicles-id', 'getVehiclesIdByVehiclesName', 'post', [606]],
    // 添加车辆分类
    ['vehicles/category/vehicles-sort', 'addVehiclesSort', 'post', [607]],
    // 获取分类
    ['vehicles/categoryList', 'getSort', [604, 607]],
    // 获取制定分类详情
    ['vehicles/category/vehicles-sort/{vehiclesSortId}', 'getVehiclesSortDetail', [607]],
    // 编辑分类
    ['vehicles/category/vehicles-sort/{vehiclesSortId}', 'editVehiclesSortDetail', "post", [607]],
    // 删除分类
    ['vehicles/category/vehicles-sort/{vehiclesSortId}', 'deleteVehiclesSort', "delete", [607]],
    // 此路由用于车辆管理，下拉框获取有权限的分类(这个api没有用到)
    ['vehicles/category/get-permessions-sort', 'getPermessionVehiclesSort'],
    // ===========================================================
    //                      车辆保险
    // ===========================================================
    // 用车保险详情
    ['vehicles/insurance/detail', 'getVehiclesInsurance', [604]],
    // 添加车辆保险(有菜单即可操作)
    ['vehicles/insurance/add', 'addVehiclesInsurance', 'post', [604]],
    // 编辑车辆保险(有菜单即可操作)
    ['vehicles/insurance/{vehicles_insurance_id}', 'editVehiclesInsurance', 'put', [604]],
    // 删除车辆保险(有菜单即可操作)
    ['vehicles/insurance/{vehiclesInsuranceId}', 'deleteVehiclesInsurance', 'delete', [604]],
    // ===========================================================
    //                      车辆年检
    // ===========================================================
    // 用车年检详情
    ['vehicles/annual-inspection/detail', 'getVehiclesAnnualInspection', [604]],
    // 添加车辆年检(有菜单即可操作)
    ['vehicles/annual-inspection/add', 'addVehiclesAnnualInspection', 'post', [604]],
    // 编辑车辆年检(有菜单即可操作)
    ['vehicles/annual-inspection/{vehicles_annual_inspection_id}', 'editVehiclesAnnualInspection', 'put', [604]],
    // 删除车辆年检(有菜单即可操作)
    ['vehicles/annual-inspection/{vehiclesAnnualInspectionId}', 'deleteVehiclesAnnualInspection', 'delete', [604]],
    // 结束车辆年检(有菜单即可操作)
    ['vehicles/annual-inspection/end/{vehiclesAnnualInspectionId}', 'endVehiclesAnnualInspection', 'put', [604]],
    // ===========================================================
    //                      车辆年检和保险通知设置
    // ===========================================================
    // 获取配置
    ['vehicles/insurance/notification/detail', 'getInsuranceNotifyConfig', [608]],
    // 更新配置
    ['vehicles/insurance/notification/config', 'updateInsuranceNotifyConfig', 'post', [608]],
];