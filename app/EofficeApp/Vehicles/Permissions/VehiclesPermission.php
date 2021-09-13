<?php
namespace App\EofficeApp\Vehicles\Permissions;

use DB;

class VehiclesPermission
{
    private $vehiclesApplyRepository;
    private $vehiclesMaintenanceRepository;
    private $vehiclesRepository;
    public function __construct() {
        $this->vehiclesApplyRepository = 'App\EofficeApp\Vehicles\Repositories\VehiclesApplyRepository';
        $this->vehiclesMaintenanceRepository = 'App\EofficeApp\Vehicles\Repositories\VehiclesMaintenanceRepository';
        $this->vehiclesRepository = 'App\EofficeApp\Vehicles\Repositories\VehiclesRepository';
        $this->vehiclesService = 'App\EofficeApp\Vehicles\Services\VehiclesService';
    }

    public function deleteOwnVehiclesApply($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        $applyId = $this->parseApplyId($data, $urlData);
        $result = [];
        foreach($applyId as $key => $value) {
            $detail = app($this->vehiclesApplyRepository)->getDetail($value);
            $status = $detail->vehicles_apply_status;
            $applyTime =  $detail->vehicles_apply_time;
            if (($status == 1 && $currentUserId == $detail->vehicles_apply_apply_user && $applyTime === null) || ($status == 3)) {
                $result = [];
            } else {
                $result[] = $value;
            }
        }
        if (count($result) > 0) {
            return false;
        }
        return true;
    }

    public function deleteApprovalVehiclesApply($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        $applyId = $this->parseApplyId($data, $urlData);
        $result = [];
        foreach($applyId as $key => $value) {
            $detail = app($this->vehiclesApplyRepository)->getDetail($value);
            if ($detail && $currentUserId != $detail->vehicles_apply_approval_user) {
                $result[] = $value;
            }
        }
        if (count($result) > 0) {
            return false;
        }
        return true;
    }
    public function parseApplyId($data, $urlData) {
        if (!isset($urlData['vehiclesApplyId']) && empty($urlData['vehiclesApplyId'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $applyId = explode(',', $urlData['vehiclesApplyId']);
        return $applyId;
    }
    public function setVehiclesApply($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        $detail = $this->getApplyDetail($urlData);
        if ($detail) {
            $applyUser = $detail->vehicles_apply_approval_user;
            $applyStatus = $detail->vehicles_apply_status;
            if ($currentUserId == $applyUser) {
                return true;
            }
        }

        return false;
    }
    // 获取用车申请详情
    public function getApplyDetail($urlData) {
        if (!isset($urlData['vehiclesApplyId']) && empty($urlData['vehiclesApplyId'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $applyId = $urlData['vehiclesApplyId'];
        $detail = app($this->vehiclesApplyRepository)->getDetail($applyId);
        return $detail;
    }
    // 删除车辆类别
    public function  deleteVehiclesSort($own, $data, $urlData) {
        if (!isset($urlData['vehiclesSortId']) || empty($urlData['vehiclesSortId'])) {
            return ['code' => ['0x021024', 'vehicles']];
        }
        $result = DB::table('vehicles')
            ->where('vehicles_sort_id', $urlData['vehiclesSortId'])
            ->get()->toArray();
        if (!empty($result)) {
            return false;
        }
        return true;
    }


    public function deleteVehiclesMaintenance($own, $data, $urlData) {
        if (!isset($urlData['vehiclesMaintenanceId']) || empty($urlData['vehiclesMaintenanceId'])) {
            return ['code' => ['0x021025', 'vehicles']];
        }
        $maintenceIdArr = explode(',', $urlData['vehiclesMaintenanceId']);
        $end = [];
        $time = date('Y-m-d H:i:s', time());
        foreach($maintenceIdArr as $key => $value) {
            $detail = app($this->vehiclesMaintenanceRepository)->getDetail($value);
            $Mend = $detail->manual_end;
            $MendTime = $detail->vehicles_maintenance_begin_time;
            $endTime = $detail->vehicles_maintenance_end_time;
            if ($Mend != 1 && $time > $MendTime && $time<$endTime) {
                $end[] = $value;
            }
        }
        if (count($end) > 0) {
             return false;
        }
        return true;
    }

    public function endVehiclesMaintenance($own, $data, $urlData) {
        if (!isset($urlData['vehiclesMaintenanceId']) || empty($urlData['vehiclesMaintenanceId'])) {
            return ['code' => ['0x021025', 'vehicles']];
        }
        $maintenceIdArr = explode(',', $urlData['vehiclesMaintenanceId']);
        $end = [];
        $time = date('Y-m-d H:i:s', time());
        foreach($maintenceIdArr as $key => $value) {
            $detail = app($this->vehiclesMaintenanceRepository)->getDetail($value);
            $Mend = $detail->manual_end;
            $MendTime = $detail->vehicles_maintenance_begin_time;
            if ($Mend != 1 && $time < $MendTime) {
                $end[] = $value;
            }
            if ($Mend == 1) {
                $end[] = $value;
            }
        }
        if (count($end) > 0) {
             return false;
        }
        return true;
    }
    public function getVehiclesCalendar($own, $data, $urlData) {
        if (!isset($data['search']) || empty($data['search'])) {
            return ['code' => ['0x000006','common']];
        }
        $search = json_decode($data['search'], true);
        $vehiclesId = $search['vehicles_id'][0];
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $allVehiclesId = app($this->vehiclesRepository)->getAllVehicles($param);
        $allVehiclesIds = array_column($allVehiclesId, 'vehicles_id');
        if (in_array($vehiclesId, $allVehiclesIds)) {
            return true;
        }
        return false;
    }

    //用车归还
    public function vehiclesReturn($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($data['vehicles_apply_id']) && empty($data['vehicles_apply_id'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $data['vehiclesApplyId'] = $data['vehicles_apply_id'];
        $detail = $this->getApplyDetail($data);
        if (!$detail) {
            return false;
        }
        $applyUser = $detail->vehicles_apply_apply_user;
        $applyStatus = $detail->vehicles_apply_status;
        if ($currentUserId == $applyUser && ($applyStatus == 2 || $applyStatus == 6)) {
            return true;
        }
        return false;

    }

    // 用车审批
    public function vehiclesApproval($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($data['vehicles_apply_id']) && empty($data['vehicles_apply_id'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $data['vehiclesApplyId'] = $data['vehicles_apply_id'];//兼容
        $detail = $this->getApplyDetail($data);
        if (!$detail) {
            return false;
        }
        $applyUser = $detail->vehicles_apply_approval_user;
        $applyStatus = $detail->vehicles_apply_status;
        if ($currentUserId == $applyUser && ($applyStatus == 1 || $applyStatus == 4)) {
            return true;
        }
        return false;
    }
    //获取用车id
    public function getVehiclesIdByVehiclesName($own, $data, $urlData) {
        if (!isset($data['vehicles_name']) && empty($data['vehicles_name'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $vehiclesId = app($this->vehiclesService)->getVehiclesIdByVehiclesName($data);
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $allVehiclesId = app($this->vehiclesRepository)->getAllVehicles($param);
        $allVehiclesIds = array_column($allVehiclesId, 'vehicles_id');
        if (in_array($vehiclesId, $allVehiclesIds)) {
            return true;
        }
        return false;
    }

    // 申请用车
    public function addVehiclesApply($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($data['vehicles_id']) && empty($data['vehicles_id'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $vehiclesId = $data['vehicles_id'];
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $allVehiclesId = app($this->vehiclesRepository)->getAllVehicles($param);
        $allVehiclesIds = array_column($allVehiclesId, 'vehicles_id');
        $approvalUser = $data['vehicles_apply_approval_user'] ?? '';
        $params['fields'] = 'user_name_py,user_name_zm,user_id,user_name';
        $params['page'] = 0;
        $approvalUserList = app($this->vehiclesService)->getVehiclesApprovalUserList($params);
        $approvalUserListArr = [];
        if (isset($approvalUserList['list']) && !empty($approvalUserList['list'])) {
            $approvalUserListArr = array_column($approvalUserList['list'], 'user_id');
        }
        if (in_array($vehiclesId, $allVehiclesIds) && $currentUserId == $data['vehicles_apply_apply_user'] && in_array($approvalUser,$approvalUserListArr)) {
            return true;
        }
        return false;
    }

    public function editVehiclesApply($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['vehicles_apply_id']) && empty($urlData['vehicles_apply_id'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $vehiclesId = $data['vehicles_id'];
        $urlData['vehiclesApplyId'] = $urlData['vehicles_apply_id'];
        $detail = $this->getApplyDetail($urlData);
        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        $allVehiclesId = app($this->vehiclesRepository)->getAllVehicles($param);
        $allVehiclesIds = array_column($allVehiclesId, 'vehicles_id');
        $approvalUser = $data['vehicles_apply_approval_user'] ?? '';
        $applyUser = $detail->vehicles_apply_apply_user;
        $applyStatus = $detail->vehicles_apply_status;
        $applyTime = $detail->vehicles_apply_time;
        $params['fields'] = 'user_name_py,user_name_zm,user_id,user_name';
        $params['page'] = 0;
        $approvalUserList = app($this->vehiclesService)->getVehiclesApprovalUserList($params);
        $approvalUserListArr = [];
        if (isset($approvalUserList['list']) && !empty($approvalUserList['list'])) {
            $approvalUserListArr = array_column($approvalUserList['list'], 'user_id');
        }
        if (in_array($vehiclesId, $allVehiclesIds) && $currentUserId == $data['vehicles_apply_apply_user'] && in_array($approvalUser,$approvalUserListArr) && $applyStatus == 1 && $applyTime === null) {
            return true;
        }
        return false;
    }
}
