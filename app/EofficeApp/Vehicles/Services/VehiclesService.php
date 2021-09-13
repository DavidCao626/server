<?php
namespace App\EofficeApp\Vehicles\Services;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Vehicles\Repositories\VehiclesAnnualInspectionRepository;
use App\EofficeApp\Vehicles\Repositories\VehiclesInsuranceMessageConfigRepository;
use App\EofficeApp\Vehicles\Repositories\VehiclesInsuranceRepository;
use App\EofficeApp\FormModeling\Services\FormModelingService;
use App\EofficeApp\Vehicles\Repositories\VehiclesApplyRepository;
use App\EofficeApp\Vehicles\Repositories\VehiclesMaintenanceRepository;
use App\EofficeApp\Vehicles\Repositories\VehiclesRepository;
use Eoffice;
use App\EofficeApp\Base\BaseService;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
/**
 * 用车服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class VehiclesService extends BaseService {
    public function __construct() {
        parent::__construct();
        $this->vehiclesRepository                       = 'App\EofficeApp\Vehicles\Repositories\VehiclesRepository';
        $this->vehiclesApplyRepository                  = 'App\EofficeApp\Vehicles\Repositories\VehiclesApplyRepository';
        $this->vehiclesMaintenanceRepository            = 'App\EofficeApp\Vehicles\Repositories\VehiclesMaintenanceRepository';
        $this->vehiclesInsuranceRepository              = 'App\EofficeApp\Vehicles\Repositories\VehiclesInsuranceRepository';
        $this->vehiclesAnnualInspectionRepository       = 'App\EofficeApp\Vehicles\Repositories\VehiclesAnnualInspectionRepository';
        $this->vehiclesInsuranceMessageConfigRepository = 'App\EofficeApp\Vehicles\Repositories\VehiclesInsuranceMessageConfigRepository';
        $this->userRepository                           = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->attachmentService                        = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userService                              = 'App\EofficeApp\User\Services\UserService';
        $this->userMenuService                          = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->calendarService                          = 'App\EofficeApp\Calendar\Services\CalendarService';
        $this->systemComboboxService                    = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->vehiclesSortMemberUserRepository         = 'App\EofficeApp\Vehicles\Repositories\VehiclesSortMemberUserRepository';
        $this->vehiclesSortMemberRoleRepository         = 'App\EofficeApp\Vehicles\Repositories\VehiclesSortMemberRoleRepository';
        $this->vehiclesSortMemberDepartmentRepository   = 'App\EofficeApp\Vehicles\Repositories\VehiclesSortMemberDepartmentRepository';
        $this->vehiclesSortRepository                   = 'App\EofficeApp\Vehicles\Repositories\VehiclesSortRepository';
        $this->vehiclesSelectField          = [
                                                'vehicles_type'    => 'VEHICLE_TYPE',
                                                'vehicles_new_old' => 'DEPRECIATION_STAGE'
                                            ];
    }

    /**
     * 车辆管理列表
     *
     * @param  array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     *
     */
    public function getAllVehicles($data, $loginUserInfo) {

        if ((isset($data['user_id']) && !empty($data['user_id']) && ($data['user_id'] != '{user_id}')) && (isset($data['type_name']) && ($data['type_name'] == 'form'))) {
            $data['user_id'] = isset($data["user_id"]) ? $data["user_id"]:"";
            // 车辆表单支持部门角色权限控制
            $userData = app($this->userService)->getUserAllData($data['user_id']);
            if (!empty($userData)) {
                $userData = $userData->toArray();
                $data['role_id'] = array_column($userData['user_has_many_role'], 'role_id');
                $data['dept_id'] = isset($userData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id']) ? $userData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id'] : '';
            }
        }else if (isset($data['user_id']) && empty($data['user_id'])) {
            return $this->getAllNoAuthVehicles($data);
        }

        if ((isset($data['user_id']) && ($data['user_id'] == "{user_id}")) || !isset($data['user_id'])){
            $data['user_id'] = isset($loginUserInfo["user_id"]) ? $loginUserInfo["user_id"]:"";
            $data['role_id'] = isset($loginUserInfo["role_id"]) ? $loginUserInfo["role_id"]:"";
            $data['dept_id'] = isset($loginUserInfo["dept_id"]) ? $loginUserInfo["dept_id"]:"";
        }

        //车辆管理页面： 空闲 |使用中|暂无维护|待维护|维护中
        $return = $this->response(app($this->vehiclesRepository), 'getAllVehiclesTotal', 'getAllVehicles', $this->parseParams($data));
        foreach ($return['list'] as $k => $v) {
            $selectField = $this->vehiclesSelectField;
            foreach ($selectField as $field => $id) {
                $return['list'][$k]['vehicles_type_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $v[$field]);
            }
            $return['list'][$k]['vehicles_space']     = $v['vehicles_space']?$v['vehicles_space']:'';
            $return['list'][$k]['vehicles_name'] = $v['vehicles_name'] . "(" . $v['vehicles_code'] . ")";
            $return['list'][$k]['vehicles_title']     = '';
            if($return['list'][$k]['vehicles_space']) {
                $return['list'][$k]['vehicles_title'] .= trans('vehicles.vehicles_space').'：'.$return['list'][$k]['vehicles_space'].'；';
            }
            if($return['list'][$k]['vehicles_type_name']) {
                $return['list'][$k]['vehicles_title'] .= trans('vehicles.new_and_old_degree').'：'.$return['list'][$k]['vehicles_type_name'].'；';
            }
        }
        return $return;
    }
    /**
     * 车辆管理列表没有用户权限的,主要用于设置
     *
     * @param  array $data
     *
     * @return array
     *
     */
    public function getAllNoAuthVehicles($data) {
        //车辆管理页面： 空闲 |使用中|暂无维护|待维护|维护中
        $return = $this->response(app($this->vehiclesRepository), 'getAllVehiclesNoAuthTotal', 'getAllVehiclesNoAuth', $this->parseParams($data));
        foreach ($return['list'] as $k => $v) {
            $selectField = $this->vehiclesSelectField;
            foreach ($selectField as $field => $id) {
                $return['list'][$k]['vehicles_type_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $v[$field]);
            }
            $return['list'][$k]['vehicles_space']     = $v['vehicles_space']?$v['vehicles_space']:'';
            $return['list'][$k]['vehicles_name'] = $v['vehicles_name'] . "(" . $v['vehicles_code'] . ")";
            $return['list'][$k]['vehicles_title']     = '';
            if($return['list'][$k]['vehicles_space']) {
                $return['list'][$k]['vehicles_title'] .= trans('vehicles.vehicles_space').'：'.$return['list'][$k]['vehicles_space'].'；';
            }
            if($return['list'][$k]['vehicles_type_name']) {
                $return['list'][$k]['vehicles_title'] .= trans('vehicles.new_and_old_degree').'：'.$return['list'][$k]['vehicles_type_name'].'；';
            }
        }
        return $return;
    }

    public function getAllVehiclesByUser($param) {
        if (isset($param['search'])) {
            $param['search'] = json_decode($param['search'], true);
        }
        if(isset($param['search']["vehicles_name"][0]) && !empty($param['search']["vehicles_name"][0])) {
            $vehiclesName = explode('(', $param['search']["vehicles_name"][0]);
            $param['vehicles_name'] = $vehiclesName[0];
            if(isset($vehiclesName[1])) {
                $vehiclesCode = explode(')', $vehiclesName[1]);
                $param['vehicles_code'] = $vehiclesCode[0];
            }
            unset($param['search']['vehicles_name']);
        }
        return $this->response(app($this->vehiclesApplyRepository), 'getAllVehiclesByUserTotal', 'getAllVehiclesByUser', $this->parseParams($param));
    }

    public function getMyVehiclesDetailForCustom($param) {
        $info = app($this->vehiclesApplyRepository)->getVehiclesApplyDetailForCustom($param);
        return $info;
    }

    /**
     *
     * 用车日历
     *
     * @param type $param
     * @return type
     */
    public function getVehiclesCalendar($param) {
        $param = $this->parseParams($param);
        //根据拿到的结果进行重组 求得当前状态值
        //获取当前所有vehicles_apply_id对应的记录
        $infoVehiclesApply       = app($this->vehiclesApplyRepository)->getVehiclesApplyForCalendar($param, true);
        $infoVehiclesMaintenance = app($this->vehiclesMaintenanceRepository)->getVehiclesMaintenanceForCalendar($param);
        $time = date("Y-m-d H:i:s", time());
        if(!empty($infoVehiclesMaintenance)) {
            foreach ($infoVehiclesMaintenance as $key => $temp) {
                if ($temp['vehicles_maintenance_begin_time'] > $time) {
                    $infoVehiclesMaintenance[$key]['vehicles_maintenance_status'] = trans('vehicles.to_maintenance');
                } else if ($temp['vehicles_maintenance_begin_time'] < $time && $temp['vehicles_maintenance_end_time'] > $time) {
                    $infoVehiclesMaintenance[$key]['vehicles_maintenance_status'] = trans('vehicles.in_the_maintenance');
                } else if ($temp['vehicles_maintenance_end_time'] <= $time) {
                    //维护结束的不展示在日历中
                    unset($infoVehiclesMaintenance[$key]);
                }
            }
            //数组重组
            $infoVehiclesApply = array_merge($infoVehiclesApply, $infoVehiclesMaintenance);
        }
        $finalData = [
            "total" => count($infoVehiclesApply),
            "list"  => $infoVehiclesApply
        ];
        return $finalData;
    }

    /**
     * 用车详细情况
     *
     * @param type $data
     *
     * @return array
     */
    public function getVehiclesList($data) {
        switch ($data['type']) {
            case 'vehiclesRecord':
                $result = $this->response(app($this->vehiclesApplyRepository), 'infoVehiclesApplyTotal', 'infoVehiclesApplyList', $this->parseParams($data));
                break;
            case 'vehiclesMaintenance':
                $maintenances = $this->response(app($this->vehiclesMaintenanceRepository), 'infoVehiclesMaintenanceTotal', 'infoVehiclesMaintenanceList', $this->parseParams($data));
                $comboboxTableName = get_combobox_table_name(20);
                if (isset($maintenances['list']) && !empty($maintenances['list'])) {
                    foreach($maintenances['list'] as $key => $value) {
                        $maintenances['list'][$key]['field_name'] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_".$value['field_id']);
                    }
                }
                $temp    = [];
                $results = [];
                $time = date("Y-m-d H:i:s", time());
                //获取当前维护项目的类别
                foreach ($maintenances['list'] as $temp) {
                    if ($temp['vehicles_maintenance_begin_time'] > $time) {
                        $temp['vehicles_maintenance_status'] = trans('vehicles.to_maintenance');
                        $temp['maintenance_status'] = 1;
                    } else if ($temp['vehicles_maintenance_begin_time'] < $time && $temp['vehicles_maintenance_end_time'] > $time) {
                        $temp['vehicles_maintenance_status'] = trans('vehicles.in_the_maintenance');
                        $temp['maintenance_status'] = 2;
                    } else if ($temp['vehicles_maintenance_end_time'] <= $time) {
                        $temp['vehicles_maintenance_status'] = trans('vehicles.end');
                        $temp['maintenance_status'] = 3;
                    }
                    array_push($results, $temp);
                }
                $result = [
                    'total' => $maintenances['total'],
                    'list' => $results
                ];
                break;
            case 'vehiclesInfo':
                $result = app($this->vehiclesRepository)->infoVehicles($data['vehicles_id']);
                $selectField = $this->vehiclesSelectField;
                    foreach ($selectField as $field => $id) {
                        $result[$field] = isset($result[$field]) ? $result[$field] : 'vehicles_type';
                        $result[$field.'_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $result[$field]);
                }
                $result['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'vehicles', 'entity_id'=>$data['vehicles_id']]);
                break;
            case 'vehiclesInsurance':
                $insurances = $this->response(app($this->vehiclesInsuranceRepository), 'infoVehiclesInsuranceTotal', 'infoVehiclesInsuranceList', $this->parseParams($data));
                $results = [];
                $time = date("Y-m-d 00:00:00", time());
                //获取当前维护项目的类别
                foreach ($insurances['list'] as $temp) {
                    if ($temp['vehicles_insurance_begin_time'] > $time) {
                        $temp['vehicles_insurance_status'] = trans('vehicles.to_be_effective');
                        $temp['insurance_status'] = 1;
                    } else if ($temp['vehicles_insurance_begin_time'] <= $time && $temp['vehicles_insurance_end_time'] >= $time) {
                        $temp['vehicles_insurance_status'] = trans('vehicles.in_the_insurance');
                        $temp['insurance_status'] = 2;
                    } else if ($temp['vehicles_insurance_end_time'] < $time) {
                        $temp['vehicles_insurance_status'] = trans('vehicles.expired');
                        $temp['insurance_status'] = 3;
                    }
                    array_push($results, $temp);
                }
                $result = [
                    'total' => $insurances['total'],
                    'list' => $results
                ];
                break;
            case 'vehiclesAnnualInspection':
                $insurances = $this->response(app($this->vehiclesAnnualInspectionRepository), 'infoVehiclesAnnualInspectionTotal', 'infoVehiclesAnnualInspectionList', $this->parseParams($data));
                $results = [];
                $time = date("Y-m-d 00:00:00", time());
                //获取当前维护项目的类别
                foreach ($insurances['list'] as $temp) {
                    if ($temp['vehicles_annual_inspection_begin_time'] > $time) {
                        $temp['vehicles_annual_inspection_status'] = trans('vehicles.for_annual_inspection');
                        $temp['annual_inspection_status'] = 1;
                    } else if ($temp['vehicles_annual_inspection_begin_time'] <= $time && $temp['vehicles_annual_inspection_end_time'] >= $time) {
                        $temp['vehicles_annual_inspection_status'] = trans('vehicles.annual_inspection_period');
                        $temp['annual_inspection_status'] = 2;
                    } else if ($temp['vehicles_annual_inspection_end_time'] < $time) {
                        $temp['vehicles_annual_inspection_status'] = trans('vehicles.expired');
                        $temp['annual_inspection_status'] = 3;
                    }
                    array_push($results, $temp);
                }
                $result = [
                    'total' => $insurances['total'],
                    'list' => $results
                ];
                break;
        }

        return $result;
    }

    /**
     * 申请记录详细
     * @param type $data
     * @return type
     */
    public function getOneVehicleApply($data, $loginUserInfo) {
        // 获取详情并判断查看权限
        $vehiclesApplyDetail = $this->getVehiclesDetailPermissions($data, $loginUserInfo);

        if(!empty($vehiclesApplyDetail)) {
            $sort = app($this->vehiclesSortRepository)->getDetail($vehiclesApplyDetail['vehicles_sort_id']);
            $vehiclesApplyDetail['vehicles_sort_name']         = $sort ? $sort->vehicles_sort_name : '';
            $vehiclesApplyDetail['vehicles_apply_approval_user_name'] = app($this->userRepository)->getUserName($vehiclesApplyDetail['vehicles_apply_approval_user']);
            if ($vehiclesApplyDetail['vehicles_apply_check_user']) {
                $vehiclesApplyDetail['vehicles_apply_check_user_name'] = app($this->userRepository)->getUserName($vehiclesApplyDetail['vehicles_apply_check_user']);
            }
            $userInfo = app($this->userService)->getUserAllData($vehiclesApplyDetail['vehicles_apply_apply_user']);
            if (!empty($userInfo) && isset($userInfo->userHasOneSystemInfo)) {
                if (isset($userInfo->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name)) {
                    $vehiclesApplyDetail['apply_user_dept_name'] = $userInfo->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name ? $userInfo->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name : '';
                }
            }else{
                $vehiclesApplyDetail['apply_user_dept_name'] = $vehiclesApplyDetail['vehicles_apply_apply_user'];
            }
            $vehiclesApplyDetail['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'vehicles_apply', 'entity_id'=>$data['vehicles_apply_id']]);
            $vehiclesApplyDetail['return_attachment_id'] = explode(',', $vehiclesApplyDetail['return_attachment_id']);

            if (!empty($vehiclesApplyDetail['return_attachment_id'])) {
                $vehiclesApplyDetail['attachment_id'] = array_diff($vehiclesApplyDetail['attachment_id'], $vehiclesApplyDetail['return_attachment_id']);
            }

            $vehiclesApplyDetail['attachment_id'] = array_merge($vehiclesApplyDetail['attachment_id']);
            return $vehiclesApplyDetail;
        }else{
            return ['code' => ['0x000006','common']];
        }
    }

    /**
     * 判断是否有用车详情或用车维护的查看权限
     * @param string $vehiclesApplyId ; array $loginUserInfo
     * @return string
     */
    function getVehiclesDetailPermissions($params, $loginUserInfo) {
        // 判断是否有车辆管理，如果有可查看所有车辆申请详情
        $managePermissions = in_array('604', $loginUserInfo['menus']['menu']);
        if(isset($params['vehicles_apply_id']) && $params['vehicles_apply_id']) {
            $vehiclesApplyDetail = app($this->vehiclesApplyRepository)->infoVehiclesApply($params['vehicles_apply_id']);
            if(empty($vehiclesApplyDetail)) {
                return '0';
            }
            // 申请人权限
            $applyUserPermissions = $loginUserInfo['user_id'] == $vehiclesApplyDetail['vehicles_apply_apply_user'];
            // 审批人权限
            $approvalUserPermissions = $loginUserInfo['user_id'] == $vehiclesApplyDetail['vehicles_apply_approval_user'];
            // 判断查看申请记录详情权限
            if(!$managePermissions) {
                if(!$applyUserPermissions && !$approvalUserPermissions) {
                    return '0';
                }else{
                    return $vehiclesApplyDetail;
                }
            }else{
                return $vehiclesApplyDetail;
            }
        }else if(isset($params['vehicles_maintenance_id']) && $params['vehicles_maintenance_id']) {
            if(!$managePermissions) {
                return '0';
            }else{
                $vehiclesMaintenanceDetail =  app($this->vehiclesMaintenanceRepository)->infoVehiclesMaintenance($params['vehicles_maintenance_id']);
                return $vehiclesMaintenanceDetail;
            }
        }else{
            return ['code' => ['0x000003','common']];
        }
    }

    /**
     * 设置待审核申请的审批查看时间
     * @param string $vehicles_apply_id
     * @return boolean
     */
    public function setVehiclesApply($vehicles_apply_id) {
        return app($this->vehiclesApplyRepository)->setVehiclesApply($vehicles_apply_id);
    }

    /**
     * 维护记录详细
     * @param type $data
     * @return type
     */
    public function getOneVehiclesMaintenance($data, $loginUserInfo) {
        $vehiclesMaintenanceDetail = app($this->vehiclesMaintenanceRepository)->infoVehiclesMaintenance($data['vehicles_maintenance_id']);
        // 判断是否有车辆管理，如果有可查看所有车辆维护记录详情
        $managePermissions = in_array('604', $loginUserInfo['menus']['menu']);
        if(!$managePermissions || empty($vehiclesMaintenanceDetail)) {
            return ['code' => ['0x000006','common']];
        }
        $comboboxTableName = get_combobox_table_name($vehiclesMaintenanceDetail['combobox_id']);
        $vehiclesMaintenanceDetail['field_name'] = mulit_trans_dynamic($comboboxTableName . '.field_name.'. $vehiclesMaintenanceDetail['field_name']);
        return $vehiclesMaintenanceDetail;
    }

    /**
     * 用车审核列表
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function vehiclesApprovalList($param, $own) {
        $param['user_id'] = $own['user_id'];
        if (isset($param['search'])) {
            $param['search'] = json_decode($param['search'], true);
        }
        if(isset($param['search']["vehicles_name"][0]) && !empty($param['search']["vehicles_name"][0])) {
            $vehiclesName = explode('(', $param['search']["vehicles_name"][0]);
            $param['vehicles_name'] = $vehiclesName[0];
            if(isset($vehiclesName[1])) {
                $vehiclesCode = explode(')', $vehiclesName[1]);
                $param['vehicles_code'] = $vehiclesCode[0];
            }
            unset($param['search']['vehicles_name']);
        }

        return $this->response(app($this->vehiclesApplyRepository), 'vehiclesApprovalTotal', 'vehiclesApprovalList', $this->parseParams($param));
    }

    /**
     * 增加车辆
     *
     * @param type $data
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function addVehicles($data) {
        $vehiclesData  = array_intersect_key($data, array_flip(app($this->vehiclesRepository)->getTableColumns()));
        $result        = app($this->vehiclesRepository)->insertData($vehiclesData);
        $vehicles_id   = $result->vehicles_id;
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("vehicles", $vehicles_id, $data['attachment_id']);
        }
        return $result;
    }

    /**
     * 编辑车辆
     *
     * @param type $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function editVehicles($data) {
        $vehiclesInfo = app($this->vehiclesRepository)->infoVehicles($data['vehicles_id']);
        if(count($vehiclesInfo) == 0) {
            return ['code' => ['0x021003', 'vehicles']]; // 系统异常
        }
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesRepository)->getTableColumns()));
        $resultStatus = app($this->vehiclesRepository)->updateData($vehiclesData, ['vehicles_id' => $vehiclesData['vehicles_id']]);
        if(isset($data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("vehicles", $vehiclesData['vehicles_id'], $data['attachment_id']);
        }
        return $resultStatus;
    }

    /**
     * 删除车辆
     *
     * @param type $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function deleteVehicles($vehiclesId) {
        // $destroyIds = explode(",", $vehiclesId);
        //当车辆ID被占用时 不可以删除
        //根据查询车辆记录 或者 车辆维护
        $countMaintenance = app($this->vehiclesMaintenanceRepository)->getVehiclesMaintenanceDelete($vehiclesId);
        $countApply = app($this->vehiclesApplyRepository)->getVehiclesApplyDelete($vehiclesId);
        if ($countMaintenance > 0 || $countApply > 0) {
            // 车辆被占用，不可以被删除
            return ['code' => ['0x021004', 'vehicles']];
        }
        //删除车辆附件
        $vehiclesAttachmentData = ['entity_table' => 'vehicles', 'entity_id' => $vehiclesId];
        app($this->attachmentService)->deleteAttachmentByEntityId($vehiclesAttachmentData);
        //设置车牌号为空
        $vehiclesData = ['vehicles_code' => ''];
        app($this->vehiclesRepository)->updateData($vehiclesData, ['vehicles_id' => $vehiclesId]);
        return app($this->vehiclesRepository)->deleteVehicles(['vehicles' => $vehiclesId]);
    }

    /**
     * 通过流程外发增加用车申请记录
     *
     * @param type $data
     *
     * @return int 自增ID
     *
     * @author 缪晨晨
     *
     * @since 2017-09-21
     */
    public function addVehiclesApplyByFlowOutSend($data) {
        
        if (!isset($data['vehicles_apply_apply_user']) || empty($data['vehicles_apply_apply_user'])) {
            return ['code' => ['0x021014', 'vehicles']];
        }
        if (!isset($data['vehicles_apply_approval_user']) || empty($data['vehicles_apply_approval_user'])) {
            return ['code' => ['0x021015', 'vehicles']];
        }
        if (!isset($data['vehicles_apply_begin_time']) || empty($data['vehicles_apply_begin_time'])) {
            return ['code' => ['0x021016', 'vehicles']];
        }
        if (!isset($data['vehicles_apply_end_time']) || empty($data['vehicles_apply_end_time'])) {
            return ['code' => ['0x021017', 'vehicles']];
        }

        // 用车外发  $data['vehicles_id'] = $this->getVehiclesIdByVehiclesName(['vehicles_name' => $data['vehicles_id']]);
        if (!isset($data['vehicles_id']) || empty($data['vehicles_id'])) {
            return ['code' => ['0x021013', 'vehicles']];
        }
        if ($data['vehicles_apply_begin_time'] > $data['vehicles_apply_end_time']) {
            return ['code' => ['0x021018', 'vehicles']];
        }
        // 用车冲突时候, 不允许申请
        $conflictParam = [
            'startDate' => $data['vehicles_apply_begin_time'],
            'endDate' => $data['vehicles_apply_end_time'],
            'vehiclesId' => $data['vehicles_id'],

        ];
        $conflictData = app($this->vehiclesApplyRepository)->getNewVehiclesDateWhetherConflict($conflictParam, $data['vehicles_apply_apply_user']);
        if ($conflictData == '1') {
            return ['code' => ['0x021037', 'vehicles']];
        }
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesApplyRepository)->getTableColumns()));
        $vehiclesData['vehicles_apply_status'] = 2;
        $vehiclesData['vehicles_apply_approval_time'] = date('Y-m-d H:i:s');
        $vehiclesData['conflict'] = $this->getConflictVehicles($vehiclesData['vehicles_id'], $vehiclesData['vehicles_apply_begin_time'], $vehiclesData['vehicles_apply_end_time']);
        $addVehiclesApplyResult = app($this->vehiclesApplyRepository)->insertData($vehiclesData);
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("vehicles_apply", $addVehiclesApplyResult->vehicles_apply_id, $data['attachment_id']);
        }
        if ($vehiclesData['conflict']) {
            $this->updateVehiclesConflictId($vehiclesData['conflict'], $addVehiclesApplyResult->vehicles_apply_id);
        }
        if ($addVehiclesApplyResult && isset($addVehiclesApplyResult['code'])) {
            return $addVehiclesApplyResult;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'vehicles_apply',
                    'field_to' => 'vehicles_apply_id',
                    'id_to'    => $addVehiclesApplyResult->vehicles_apply_id
                ]
            ]
        ];
    }

    public function flowOutSendToUpdateVehicles($data) {
        if (empty($data)) {
            return ['code' => ['0x021003', 'vehicles']];
        }
        $vehiclesApplyId = $data['unique_id'] ?? '';
        //获取编辑前的申请详情
        $oldVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($vehiclesApplyId);
        $vehiclesInfo = [];
        if ($oldVehicles) {
            $vehiclesInfo = $oldVehicles->toArray();
        }
        
        if(empty($vehiclesInfo) || (isset($vehiclesInfo['false_delete']) && $vehiclesInfo['false_delete'] == 1)) {
            return ['code' => ['0x021023', 'vehicles']];
        } else if($oldVehicles->vehicles_apply_status !== 1 || $oldVehicles->vehicles_apply_time != '') {
            //待审核车辆才可以被编辑
            return ['code' => ['0x021005', 'vehicles']];
        }
        $updateData = $data['data'] ?? [];
        if ($updateData) {
            
            $updateData["vehicles_apply_apply_user"] = $updateData['current_user_id'] ?? '';
            if (isset($updateData["vehicles_apply_apply_user"]) && $updateData["vehicles_apply_apply_user"] != $oldVehicles->vehicles_apply_apply_user) {
                return ['code' => ['0x021010', 'vehicles']];
            }
            $updateData['vehicles_apply_id'] = $vehiclesApplyId;

            if (isset($updateData['vehicles_apply_apply_user']) && empty($updateData['vehicles_apply_apply_user'])) {
                return ['code' => ['0x021014', 'vehicles']];
            }
            if (isset($updateData['vehicles_apply_approval_user']) && empty($updateData['vehicles_apply_approval_user'])) {
                return ['code' => ['0x021015', 'vehicles']];
            }
            if (isset($updateData['vehicles_apply_begin_time']) && empty($updateData['vehicles_apply_begin_time'])) {
                return ['code' => ['0x021016', 'vehicles']];
            }
            if (isset($updateData['vehicles_apply_end_time']) && empty($updateData['vehicles_apply_end_time'])) {
                return ['code' => ['0x021017', 'vehicles']];
            }
            if (isset($updateData['vehicles_id']) && empty($updateData['vehicles_id'])) {
                return ['code' => ['0x021013', 'vehicles']];
            }
            // 获取有权限的车辆
            $info = app($this->userRepository)->getUserAllData($updateData['current_user_id'])->toArray();
            if($info){
                $role_ids = [];
                foreach ($info['user_has_many_role'] as $key => $vo) {
                    $role_ids[] = $vo['role_id'];
                }
                $own = [
                    'user_id' => $updateData['current_user_id'],
                    'dept_id' => $info['user_has_one_system_info']['dept_id'],
                    'role_id' => $role_ids,
                ];
            }

//            $own = own();
            $param['user_id'] = $own['user_id'] ??'';
            $param['dept_id'] = $own['dept_id'] ??'';
            $param['role_id'] = $role_ids ?? [];
            $allVehiclesId = app($this->vehiclesRepository)->getAllVehicles($param);
            $allVehiclesIds = array_column($allVehiclesId, 'vehicles_id');
            if (isset($updateData["vehicles_id"]) && !in_array($updateData["vehicles_id"], $allVehiclesIds)) {
                return ['code' => ['0x000006', 'common']];
            }
            $updateData['vehicles_apply_begin_time'] = isset($updateData['vehicles_apply_begin_time']) && !empty($updateData['vehicles_apply_begin_time']) ? $updateData['vehicles_apply_begin_time'] : $oldVehicles->vehicles_apply_begin_time;
            $updateData['vehicles_apply_approval_user'] = isset($updateData['vehicles_apply_approval_user']) && !empty($updateData['vehicles_apply_approval_user']) ? $updateData['vehicles_apply_approval_user'] : $oldVehicles->vehicles_apply_approval_user;
            $updateData['vehicles_apply_end_time'] = isset($updateData['vehicles_apply_end_time']) && !empty($updateData['vehicles_apply_end_time']) ? $updateData['vehicles_apply_end_time'] : $oldVehicles->vehicles_apply_end_time;
            $updateData['vehicles_id'] = isset($updateData['vehicles_id']) && !empty($updateData['vehicles_id']) ? $updateData['vehicles_id'] : $oldVehicles->vehicles_id;
            if ($updateData['vehicles_apply_begin_time'] > $updateData['vehicles_apply_end_time']) {
                return ['code' => ['0x021018', 'vehicles']];
            }
            // 用车冲突时候, 不允许申请
            $conflictParam = [
                'startDate' => $updateData['vehicles_apply_begin_time'],
                'endDate' => $updateData['vehicles_apply_end_time'],
                'vehiclesId' => $updateData['vehicles_id'],
                'applyId' => $vehiclesApplyId

            ];
            $conflictData = app($this->vehiclesApplyRepository)->getVehiclesConflict($conflictParam, $updateData['current_user_id']);
            if ($conflictData == '1') {
                return ['code' => ['0x021037', 'vehicles']];
            }
            unset($updateData['current_user_id']);
            $return = $this->editVehiclesApply($updateData, ['user_id' => $updateData['vehicles_apply_apply_user']]);
            if ($return && isset($return['code'])) {
                return $return;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'vehicles_apply',
                        'field_to' => 'vehicles_apply_id',
                        'id_to'    => $vehiclesApplyId
                    ]
                ]
            ];
        }
    }

    public function flowOutSendToDeleteVehicles($data) {
        if (empty($data)) {
            return ['code' => ['0x021003', 'vehicles']];
        }
        $vehiclesApplyId = isset($data['unique_id']) ? $data['unique_id'] : [];
        $deleteData = $data['data'] ?? [];
        // 判断数据是否存在
        $currentVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($vehiclesApplyId);
        
        if ($currentVehicles) {
            $vehiclesInfo = $currentVehicles->toArray();
        }
        if (empty($vehiclesInfo) || (isset($vehiclesInfo['false_delete']) && $vehiclesInfo['false_delete'] == 1)) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $status = $currentVehicles->vehicles_apply_status;
        $applyTime =  $currentVehicles->vehicles_apply_time;
        $currentUserId = $deleteData['current_user_id'];
        if (($status == 1 && $currentUserId == $currentVehicles->vehicles_apply_apply_user && $applyTime === null) || $status == 3) {
            $result = false;
        } else {
            $result = true;
        } 
        if ($result) {
            return ['code' => ['0x000006', 'common']];
        }
        if ((isset($currentVehicles->vehicles_apply_status) && isset($currentVehicles->vehicles_apply_time)) && $currentVehicles->vehicles_apply_status == 1 && $currentVehicles->vehicles_apply_time != '') {
            return ['code' => ['0x021011', 'vehicles']];
        }
        $own = ['user_id' => $currentUserId];
        $return = $this->deleteOwnVehiclesApply($vehiclesApplyId,$own);
        if ($return && isset($return['code'])) {
            return $return;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'vehicles_apply',
                    'field_to' => 'vehicles_apply_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    /**
     * 增加用车申请
     *
     * @param type $data
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function addVehiclesApply($data) {
        if ($data['vehicles_apply_begin_time'] > $data['vehicles_apply_end_time']) {
            return ['code' => ['0x021018', 'vehicles']]; // 报相应的提示信息
        }
        // $vehiclesMaintenanceFlag = app($this->vehiclesMaintenanceRepository)->getVehiclesLatestMaintenanceTime($data['vehicles_id'], $data['vehicles_apply_begin_time'], $data['vehicles_apply_end_time']);
        // if($vehiclesMaintenanceFlag) {
        //     // 如果申请时间段内车辆处于维护状态不允许申请
        //     return ['code' => ['0x021012', 'vehicles']];
        // }
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesApplyRepository)->getTableColumns()));
        $vehiclesData['vehicles_apply_status'] = 1;
        $vehiclesData['conflict'] = $this->getConflictVehicles($vehiclesData['vehicles_id'], $vehiclesData['vehicles_apply_begin_time'], $vehiclesData['vehicles_apply_end_time']);
        $addVehiclesApplyResult = app($this->vehiclesApplyRepository)->insertData($vehiclesData);
        if ($vehiclesData['conflict']) {
            $this->updateVehiclesConflictId($vehiclesData['conflict'], $addVehiclesApplyResult->vehicles_apply_id);
        }
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            app($this->attachmentService)->attachmentRelation("vehicles_apply", $addVehiclesApplyResult->vehicles_apply_id, $data['attachment_id']);
        }
        //发送消息提醒
        $userName          = app($this->userService)->getUserName($data['vehicles_apply_apply_user']);
        $getVehiclesInfo   = app($this->vehiclesRepository)->infoVehicles($data['vehicles_id']);
        $vehiclesName      = $getVehiclesInfo['vehicles_name'];
        $vehiclesCode      = $getVehiclesInfo['vehicles_code'];
        $VehiclesapplyTime = $data['vehicles_apply_begin_time'];
        $sendData['remindMark']     = 'car-submit';
        $sendData['toUser']         = $data['vehicles_apply_approval_user'];
        $sendData['contentParam']   = ['applyUser' => $userName, 'carName' => $vehiclesName, 'carNumber' => $vehiclesCode, 'beginTime' => $VehiclesapplyTime];
        $sendData['stateParams']    = ['vehicles_apply_id' => $addVehiclesApplyResult->vehicles_apply_id, 'type' => 'vehicles-approval'];
        Eoffice::sendMessage($sendData);
        // 外发到日程模块 --开始--
        $calendarData = [
            'calendar_content' => '用车审批'. '--' . $getVehiclesInfo['vehicles_name'],
            'handle_user'      => explode(',', $data['vehicles_apply_approval_user']),
            'calendar_begin'   => date('Y-m-d H:i:s'),
            'calendar_end'     => $data['vehicles_apply_begin_time'],
            'calendar_remark'  => isset($data['vehicles_apply_remark']) ? $data['vehicles_apply_remark'] : '',
            'attachment_id'    => $data['attachment_id'] ?? ''
        ];
        $relationData = [
            'source_id'     => $addVehiclesApplyResult->vehicles_apply_id,
            'source_from'   => 'car-submit',
            'source_title'  => '用车审批'. '--' . $getVehiclesInfo['vehicles_name'],
            'source_params' => ['vehicles_apply_id' => $addVehiclesApplyResult->vehicles_apply_id]
        ];
        app($this->calendarService)->emit($calendarData, $relationData, $data['vehicles_apply_apply_user']);
        // 外发到日程模块 --结束--
        return $addVehiclesApplyResult;
    }

    public function urgeVehiclesApply($data) {
        if (!isset($data['vehicles_apply_id']) || empty($data['vehicles_apply_id'])) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $applyDetail = app($this->vehiclesApplyRepository)->getDetail($data['vehicles_apply_id']);

        if (!$applyDetail->count()) {
            return ['code' => ['0x021023', 'vehicles']];
        }
        $getVehiclesInfo   = app($this->vehiclesRepository)->infoVehicles($applyDetail->vehicles_id);

        $vehiclesName      = isset($getVehiclesInfo['vehicles_name']) ? $getVehiclesInfo['vehicles_name'] : '';
        $vehiclesCode      = isset($getVehiclesInfo['vehicles_code']) ? $getVehiclesInfo['vehicles_code'] : '';
        $userName          = app($this->userService)->getUserName($applyDetail->vehicles_apply_apply_user);
        $sendData['remindMark']     = 'car-urge';
        $sendData['toUser']         = $applyDetail->vehicles_apply_approval_user;
        $sendData['contentParam']   = ['applyUser' => $userName, 'carName' => $vehiclesName, 'carNumber' => $vehiclesCode, 'beginTime' => $applyDetail->vehicles_apply_begin_time];
        $sendData['stateParams']    = ['vehicles_apply_id' => $data['vehicles_apply_id'], 'type' => 'vehicles-approval'];
        Eoffice::sendMessage($sendData);
    }

    /**
     * 编辑用车申请
     *
     * @param type $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function editVehiclesApply($data, $own) {
        if (!isset($data['vehicles_id']) || empty($data['vehicles_id'])) {
            return ['code' => ['0x021013', 'vehicles']];
        }
        $data['conflict'] = str_replace([',' . $data['vehicles_apply_id'], $data['vehicles_apply_id']], '', $this->getConflictVehicles($data['vehicles_id'], $data['vehicles_apply_begin_time'], $data['vehicles_apply_end_time']));
        $data['vehicles_apply_status'] = 1;
        //获取编辑前的申请详情
        $oldVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($data['vehicles_apply_id']);        
        if(empty($oldVehicles)) {
            return ['code' => ['0x021003', 'vehicles']];
        }else if($oldVehicles->vehicles_apply_status !== 1 || $oldVehicles->vehicles_apply_time != '') {
            //待审核车辆才可以被编辑
            return ['code' => ['0x021005', 'vehicles']];
        }

        if($oldVehicles['vehicles_apply_apply_user'] != $data['vehicles_apply_apply_user']) {
            return ['code' => ['0x021010', 'vehicles']]; // 数据异常，检测到当前用户并非申请
        }
        if($data['vehicles_apply_begin_time'] > $data['vehicles_apply_end_time']) {
            return ['code' => ['0x021018', 'vehicles']];
        }
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesApplyRepository)->getTableColumns()));
        $vehiclesData['return_attachment_id'] = '';
        if(!app($this->vehiclesApplyRepository)->editVehiclesApply($vehiclesData, $data['vehicles_apply_id'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if(isset($data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("vehicles_apply", $data['vehicles_apply_id'], $data['attachment_id']);
        }
        if($oldVehicles->vehicles_id != $data['vehicles_id'] || $oldVehicles->vehicles_apply_begin_time != $data['vehicles_apply_begin_time'] || $oldVehicles->vehicles_apply_end_time != $data['vehicles_apply_end_time']) {
            $this->replaceOldVehiclesConflictId($oldVehicles->vehicles_id, $oldVehicles->vehicles_apply_begin_time, $oldVehicles->vehicles_apply_end_time, $data['vehicles_apply_id']);
        }
        if($data['conflict']) {
            $this->updateVehiclesConflictId($data['conflict'], $data['vehicles_apply_id']);
        }
        $userName          = app($this->userService)->getUserName($data['vehicles_apply_apply_user']);
        $getVehiclesInfo   = app($this->vehiclesRepository)->infoVehicles($data['vehicles_id']);
        $vehiclesName      = $getVehiclesInfo['vehicles_name'];
        $vehiclesCode      = $getVehiclesInfo['vehicles_code'];
        $VehiclesapplyTime = $data['vehicles_apply_begin_time'];
        $calendarData = [
            'calendar_content' => '用车审批'. '--' . $getVehiclesInfo['vehicles_name'],
            'handle_user'      => explode(',', $data['vehicles_apply_approval_user']),
            'calendar_begin'   => date('Y-m-d H:i:s'),
            'calendar_end'     => $data['vehicles_apply_begin_time'],
            'calendar_remark'  => isset($data['vehicles_apply_remark']) ? $data['vehicles_apply_remark'] : '',
            'attachment_id'    => $data['attachment_id'] ?? ''
        ];
        $relationData = [
            'source_id'     => $data['vehicles_apply_id'],
            'source_from'   => 'car-submit',
            'source_title'  => '用车审批'. '--' . $getVehiclesInfo['vehicles_name'],
            'source_params' => ['vehicles_apply_id' => $data['vehicles_apply_id']]
        ];
        $return = app($this->calendarService)->emitUpdate($calendarData, $relationData, $data['vehicles_apply_apply_user']);
        return true;
    }

    /**
     * 删除我的用车下的用车申请
     *
     * @param type $applyIds
     *
     * @return bool
     *
     * @author miaochenchen
     *
     * @since 2016-08-04
     */
    public function deleteOwnVehiclesApply($applyIds,$own = null) {
        $vApplyIdArray = explode(',', $applyIds);
        foreach($vApplyIdArray as $key => $value) {
            $currentVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($value);
            if ($currentVehicles->vehicles_apply_status != 5 && $currentVehicles->vehicles_apply_status != 3 && $currentVehicles->vehicles_apply_status != 1) {
                return ['code' => ['0x021011', 'vehicles']];
            }
            if ($currentVehicles->vehicles_apply_status == 1 && $currentVehicles->vehicles_apply_time != '') {
                return ['code' => ['0x021011', 'vehicles']];
            }
            
            if ($currentVehicles->false_delete == 0 && $currentVehicles->vehicles_apply_status != 1) {
                //已拒绝的申请+已验收的申请删除，不影响用车审批的记录
                if (!app($this->vehiclesApplyRepository)->editVehiclesApply(['false_delete' => 1], $value)) {
                    return ['code' => ['0x000003', 'common']];
                }
            } else {
                //待审批的直接删除
                $vehiclesApplyAttachmentData = ['entity_table' => 'vehicles_apply', 'entity_id' => $value];
                app($this->attachmentService)->deleteAttachmentByEntityId($vehiclesApplyAttachmentData);
                if (!app($this->vehiclesApplyRepository)->deleteVehiclesApply($value)) {
                    return ['code' => ['0x000003', 'common']];
                }
                $a = $this->emitCalendarComplete($currentVehicles->vehicles_apply_id, $own ? $own['user_id'] : own()['user_id'], 'delete');
                // 删除成功之后, 处理冲突问题
                $this->replaceOldVehiclesConflictId($currentVehicles->vehicles_id, $currentVehicles->vehicles_apply_begin_time, $currentVehicles->vehicles_apply_end_time, $applyIds);
            }
        }
        return true;
    }

    /**
     * 删除用车审批下的用车申请
     *
     * @param type $applyIds
     *
     * @return bool
     *
     * @author miaochenchen
     *
     * @since 2016-08-04
     */
    public function deleteApprovalVehiclesApply($applyIds) {
        $vApplyIdArray = explode(',', $applyIds);
        foreach($vApplyIdArray as $key => $value) {
            $currentVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($value);
            if ($currentVehicles && $currentVehicles->vehicles_apply_status != 5 && $currentVehicles->vehicles_apply_status != 3) {
                return ['code' => ['0x021011', 'vehicles']];
            }
            if ($currentVehicles && $currentVehicles->false_delete == 0) {
                //已拒绝的申请+已验收的申请删除，不影响我的用车的记录
                if (!app($this->vehiclesApplyRepository)->editVehiclesApply(['false_delete' => 2], $value)) {
                    return ['code' => ['0x000003', 'common']];
                }
            } else {
                $vehiclesApplyAttachmentData = ['entity_table' => 'vehicles_apply', 'entity_id' => $value];
                app($this->attachmentService)->deleteAttachmentByEntityId($vehiclesApplyAttachmentData);
                if (!app($this->vehiclesApplyRepository)->deleteVehiclesApply($value)) {
                    return ['code' => ['0x000003', 'common']];
                }
            }
        }
        return true;
    }

    /**
     * 删除车辆管理下的用车申请
     *
     * @param type $applyIds
     *
     * @return bool
     *
     * @author miaochenchen
     *
     * @since 2016-08-04
     */
    public function deleteVehiclesManageApplyRecord($applyIds) {
        $vApplyIdArray = explode(',', $applyIds);
        foreach($vApplyIdArray as $key => $value) {
            $currentVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($value);
            if ($currentVehicles->vehicles_apply_status != 5) {
                return ['code' => ['0x021011', 'vehicles']];
            }
            if (!app($this->vehiclesApplyRepository)->deleteVehiclesApply($value)) {
                return ['code' => ['0x000003', 'common']];
            }
            $vehiclesApplyAttachmentData = ['entity_table' => 'vehicles_apply', 'entity_id' => $value];
            app($this->attachmentService)->deleteAttachmentByEntityId($vehiclesApplyAttachmentData);
        }
        return true;
    }

    /**
     * 增加用车维护
     *
     * @param type $data
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function addVehiclesMaintenance($data) {
        if($data['vehicles_maintenance_begin_time'] > $data['vehicles_maintenance_end_time']) {
            return ['code' => ['0x021020', 'vehicles']]; // 系统异常
        }
        if(($data['vehicles_maintenance_begin_time'] || $data['vehicles_maintenance_end_time']) == null) {
            return ['code' => ['0x021033', 'vehicles']];
        }
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesMaintenanceRepository)->getTableColumns()));
        $result       = app($this->vehiclesMaintenanceRepository)->insertData($vehiclesData);
        return $result->vehicles_maintenance_id;
    }

    /**
     * 流程外发车辆维护
     * @param [array] $data [外发数据]
     *
     * @return int
     */
    public function addVehiclesMaintenanceByFlowOutSend($data) {
        if (!isset($data['vehicles_id']) || empty($data['vehicles_id'])) {
            return ['code' => ['0x021013', 'vehicles']];
        }
        if(!isset($data['vehicles_maintenance_begin_time']) || $data['vehicles_maintenance_begin_time'] == null) {
            return ['code' => ['0x021021', 'vehicles']];
        }
        if(!isset($data['vehicles_maintenance_end_time']) || $data['vehicles_maintenance_end_time'] == null) {
            return ['code' => ['0x021022', 'vehicles']];
        }
        if(!isset($data['vehicles_maintenance_type']) || $data['vehicles_maintenance_type'] == null) {
            return ['code' => ['0x021026', 'vehicles']];
        }
        $result = $this->addVehiclesMaintenance($data);
        if ($result && isset($result['code'])) {
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'vehicles_maintenance',
                    'field_to' => 'vehicles_maintenance_id',
                    'id_to'    => $result
                ]
            ]
        ];
    }

    /**
     * 编辑维护信息
     *
     * @param type $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function editVehiclesMaintenance($data) {
        if ($data['vehicles_maintenance_begin_time'] > $data['vehicles_maintenance_end_time']) {
            return ['code' => ['0x021001', 'vehicles']];
        }

        if(($data['vehicles_maintenance_begin_time'] || $data['vehicles_maintenance_end_time']) == null) {
            return ['code' => ['0x021033', 'vehicles']];
        }

        $vehiclesInfo = app($this->vehiclesMaintenanceRepository)->infoVehiclesMaintenance($data['vehicles_maintenance_id']);
        if(count($vehiclesInfo) == 0) {
            return ['code' => ['0x021003', 'vehicles']];
        }elseif(strtotime($vehiclesInfo['vehicles_maintenance_end_time']) <= time()) {
            // 维护结束了
            return ['code' => ['0x021007', 'vehicles']];
        }
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesMaintenanceRepository)->getTableColumns()));
        return app($this->vehiclesMaintenanceRepository)->updateData($vehiclesData, ['vehicles_maintenance_id' => $vehiclesData['vehicles_maintenance_id']]);
    }

    public function endVehiclesMaintenance($vehiclesMaintenanceId) {
        $currentTime = date("Y-m-d H:i:s", time());
        $vehiclesData = [
            "vehicles_maintenance_end_time" => $currentTime,
            "manual_end" => 1
        ];
        if($result = app($this->vehiclesMaintenanceRepository)->updateData($vehiclesData, ['vehicles_maintenance_id' => $vehiclesMaintenanceId])) {
            $vehiclesMaintenanceDetail = app($this->vehiclesMaintenanceRepository)->infoVehiclesMaintenance($vehiclesMaintenanceId);
            if(!empty($vehiclesMaintenanceDetail)) {
                if(!empty($vehiclesMaintenanceDetail['vehicles_code'])) {
                    $vehiclesMaintenanceDetail['vehicles_name'] = $vehiclesMaintenanceDetail['vehicles_name'].'('.$vehiclesMaintenanceDetail['vehicles_code'].')';
                }
            }
            $comboboxTableName = get_combobox_table_name(20);
            $toUser = implode(',', app($this->userMenuService)->getMenuRoleUserbyMenuId(604));
            $sendData['remindMark']   = 'car-end';
            $sendData['toUser']       = $toUser;
            $sendData['contentParam'] = [
                'carName'        => $vehiclesMaintenanceDetail['vehicles_name'],
                'maintainType'   => mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_".$vehiclesMaintenanceDetail['field_id']),
                'maintainEndTime'=> $currentTime
            ];
            $sendData['stateParams']  = ['vehicles_maintenance_id' => $vehiclesMaintenanceId];
            Eoffice::sendMessage($sendData);
        }
        return $result;
    }

    /**
     * 删除维护信息
     *
     * @param type $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function deleteVehiclesMaintenance($vehiclesMaintenanceId) {
        $destroyIds = explode(",", $vehiclesMaintenanceId);
        $where = [
            'vehicles_maintenance_id' => [$destroyIds, 'in']
        ];
        return app($this->vehiclesMaintenanceRepository)->deleteByWhere($where);
    }

    /**
     * 审核用车申请
     *
     * @param type $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function vehiclesApproval($data) {
        $data['remark'] = isset($data['remark']) && !empty($data['remark']) ? $data['remark'] : "";
        $oldVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($data['vehicles_apply_id']);
        if(count(json_decode(json_encode($oldVehicles),true)) == 0) {
            // 系统异常
            return ['code' => ['0x021003', 'vehicles']];
        }elseif($oldVehicles['vehicles_apply_approval_user'] != $data['user_id']) {
            // 数据异常，检测到当前用户并非审核人
            return ['code' => ['0x021009', 'vehicles']];
        }
        switch ($data['op']) {
            case "approval":
                // 批准
                $finalData['vehicles_apply_status'] = 2;
                $finalData['vehicles_apply_approval_time'] = date("Y-m-d H:i:s", time());
                $finalData['vehicles_apply_approval_remark'] = $data['remark'];
                $sendData['remindMark'] = 'car-pass';
                break;
            case "forbidden":
                // 拒绝
                $finalData['vehicles_apply_status'] = 3;
                $finalData['conflict'] = '';
                $finalData['vehicles_apply_approval_time'] = date("Y-m-d H:i:s", time());
                $finalData['vehicles_apply_approval_remark'] = $data['remark'];
                $sendData['remindMark'] = 'car-refuse';
                app($this->vehiclesApplyRepository)->editVehiclesApply($finalData, $data['vehicles_apply_id']);
                $this->replaceOldVehiclesConflictId($oldVehicles->vehicles_id, $oldVehicles->vehicles_apply_begin_time, $oldVehicles->vehicles_apply_end_time, $data['vehicles_apply_id']);
                break;
            case "checkVehicles":
                // 验收
                $finalData['vehicles_apply_status'] = 5;
                $finalData['conflict'] = '';
                $finalData['vehicles_apply_check_time'] = date("Y-m-d H:i:s", time());
                $finalData['vehicles_apply_check_remark'] = $data['remark'];
                $finalData['vehicles_apply_check_user'] = $data['user_id'];
                $finalData['vehicles_apply_end_time'] = date("Y-m-d H:i:s", time());
                $sendData['remindMark'] = 'car-checkPass';
                $this->replaceOldVehiclesConflictId($oldVehicles->vehicles_id, $oldVehicles->vehicles_apply_begin_time, $oldVehicles->vehicles_apply_end_time, $data['vehicles_apply_id']);
                break;
            case 'checkReject':
                // 驳回
                $finalData['vehicles_apply_status'] = 6;
                $finalData['vehicles_apply_check_time'] = date("Y-m-d H:i:s", time());
                $finalData['vehicles_return_reject_remark'] = $data['remark'];
                $finalData['vehicles_apply_check_user'] = $data['user_id'];
                $sendData['remindMark'] = 'car-checkReject';
                break;
        }
        $vehiclesApprovalResult = app($this->vehiclesApplyRepository)->editVehiclesApply($finalData, $data['vehicles_apply_id']);
        // 日程完成
        if (($data['op'] == 'approval' || $data['op'] == 'forbidden')) {
            $this->emitCalendarComplete($data['vehicles_apply_id'], $data['user_id'], 'update');
        }
        //发送消息提醒
        $userName = app($this->userService)->getUserName($data['user_id']);
        $sendData['toUser']       = $oldVehicles['vehicles_apply_apply_user'];
        $sendData['contentParam'] = ['userName' => $userName];
        $sendData['stateParams']  = ['vehicles_apply_id' => $data['vehicles_apply_id']];
        Eoffice::sendMessage($sendData);
        return $vehiclesApprovalResult;
    }

    private function emitCalendarComplete($sourceId, $loginUserId, $type='update') {
        $relationData = [
            'source_id'     => $sourceId,
            'source_from'   => 'car-submit'
        ];
        if ($type == 'update') {
            $return = app($this->calendarService)->emitComplete($relationData);
        } else {
            $return = app($this->calendarService)->emitDelete($relationData, $loginUserId);
        }
        
        return $return;
    }

    /**
     * 归还
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function vehiclesReturn($data) {
        $data['remark'] = isset($data['remark']) && !empty($data['remark']) ? $data['remark'] : "";
        $applyInfo = app($this->vehiclesApplyRepository)->infoVehiclesApply($data['vehicles_apply_id']);
        if (isset($applyInfo['return_attachment_id']) && !empty($applyInfo['return_attachment_id'])) {
            $returnAttach = explode(',', $applyInfo['return_attachment_id']);
            if (isset($data['return_attachment_id']) && $data['return_attachment_id'] != $applyInfo['return_attachment_id']) {
                $diff = array_diff($returnAttach, $data['return_attachment_id']);
                if (!empty($diff)) {
                    foreach ($diff as $key => $value) {
                        app($this->attachmentService)->removeAttachment(['attachment_id' => $value]);  
                    }
                } 
                
            }
        }
        if(count($applyInfo) == 0) {
            return ['code' => ['0x021003', 'vehicles']]; // 系统异常
        }elseif($applyInfo['vehicles_apply_apply_user'] != $data['user_id']) {
            return ['code' => ['0x021010', 'vehicles']]; // 数据异常，检测到当前用户并非申请
        }
        $finalData['vehicles_apply_status'] = 4;
        $finalData['vehicles_apply_return_time'] = $data['vehicles_apply_return_time'];
        $finalData['vehicles_apply_return_remark'] = $data['remark'];
        $finalData['vehicles_apply_oil'] = $data['vehicles_apply_oil'];
        $finalData['vehicles_apply_mileage'] = $data['vehicles_apply_mileage'];
        // $finalData['return_attachment_id'] = '';
        if (isset($data['return_attachment_id']) && is_array($data['return_attachment_id'])) {
            $finalData['return_attachment_id'] = implode(',', array_filter($data['return_attachment_id']));
        }
        $vehiclesReturnResult = app($this->vehiclesApplyRepository)->updateData($finalData, ['vehicles_apply_id' => $data['vehicles_apply_id']]);
        if(isset($data['return_attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("vehicles_apply", $data['vehicles_apply_id'], array_filter($data['return_attachment_id']), 'add');
        }
        //发送消息提醒
        $userName             = app($this->userService)->getUserName($data['user_id']);
        $vehiclesNameCode     = $applyInfo['vehicles_name'].'('.$applyInfo['vehicles_code'].')';
        $sendData['remindMark']     = 'car-return';
        $sendData['toUser']         = $applyInfo['vehicles_apply_approval_user'];
        $sendData['contentParam']   = ['carName' => $vehiclesNameCode, 'userName' => $userName, 'returnTime' => date('Y-m-d H:i', strtotime($data['vehicles_apply_return_time']))];
        $sendData['stateParams']    = ['vehicles_apply_id' => $data['vehicles_apply_id']];
        Eoffice::sendMessage($sendData);
        return $vehiclesReturnResult;
    }

    /**
     * 获取审批列表冲突车辆的数量用于冲突标签徽章
     *
     * @param array $data
     *
     * @return json
     *
     * @author miaochenchen
     *
     * @since 2016-06-02
     */
    public function getVehiclesConflictEmblem($param) {
        $param['returnType'] = 'count';
        $param['search']['conflict'] = ["",'!='];
        $result = app($this->vehiclesApplyRepository)->vehiclesApprovalTotal($param);
        $data['data'][0]['fieldKey'] = 'conflict';
        $data['data'][0]['total']    = $result;
        return json_encode($data);
    }

    /**
     * 获取相应的冲突车辆申请
     *
     * @param $vehiclesId, $vBeginTime, $vEndTime
     *
     * @return string
     *
     * @author miaochenchen
     *
     * @since 2016-08-03
     */
    public function getConflictVehicles($vehiclesId, $vBeginTime, $vEndTime)
    {
        $search = [
            'vehicles_id'               => [$vehiclesId],
            'vehicles_apply_begin_time' => [$vEndTime, '<'],
            'vehicles_apply_end_time'   => [$vBeginTime, '>'],
            'vehicles_apply_status'     => [[0,1, 2, 4, 6], 'in'],
            'false_delete'              => [0]
        ];
        $result = app($this->vehiclesApplyRepository)->getConflictVehicles($search);
        if(count($result) == 0) {
            return '';
        }
        $conflictId = '';
        foreach($result as $value) {
            $conflictId .= $value->vehicles_apply_id . ',';
        }
        return substr($conflictId, 0, strrpos($conflictId, ','));
    }

    /**
     * 更新车辆申请的冲突信息
     *
     * @param $conflictId, $vApplyId
     *
     * @return string
     *
     * @author miaochenchen
     *
     * @since 2016-08-03
     */
    public function updateVehiclesConflictId($conflictId, $vApplyId)
    {
        foreach(explode(',', $conflictId) as $key => $value) {
           if(!empty($value)){
               $currentVehicles = app($this->vehiclesApplyRepository)->showVehiclesApply($value);
               if($currentVehicles['conflict'] == "") {
                   $tempConflictId = $vApplyId;
               }else{
                   $tempConflictId = $currentVehicles->conflict . ',' . $vApplyId;
               }
               app($this->vehiclesApplyRepository)->editVehiclesApply(['conflict' => $tempConflictId], $value);
           }
        }
    }

    /**
     * 将非冲突的id替换为空
     *
     * @param $vehiclesId, $vBeginTime, $vEndTime, $vehiclesApplyId
     *
     * @return string
     *
     * @author miaochenchen
     *
     * @since 2016-08-03
     */
    private function replaceOldVehiclesConflictId($vehiclesId, $vBeginTime, $vEndTime, $vehiclesApplyId)
    {
        $search = [
            'vehicles_id'               => [$vehiclesId],
            'vehicles_apply_begin_time' => [$vEndTime, '<'],
            'vehicles_apply_end_time'   => [$vBeginTime, '>'],
            'vehicles_apply_status'     => [[0, 1, 2, 4, 6], 'in'],
            'false_delete'              => [0]
        ];
        $result = app($this->vehiclesApplyRepository)->getConflictVehicles($search);
        if(count($result) == 0) {
            return '';
        }
        foreach($result as $value) {
            $tempConflictId = str_replace([$vehiclesApplyId . ',', ',' . $vehiclesApplyId, $vehiclesApplyId], '', $value->conflict);
            app($this->vehiclesApplyRepository)->editVehiclesApply(['conflict' => $tempConflictId], $value->vehicles_apply_id);
        }
    }

    /**
     *
     * 获取审批人列表
     *
     * @param
     *
     * @return json
     *
     * @author miaochenchen
     *
     * @since 2016-09-20
     */
    public function getVehiclesApprovalUserList($params)
    {

        $params = $this->parseParams($params);
        $roleIdArray = app($this->userMenuService)->getRoleIdArrayByMenuId('603');
        $userList = array();
        if(!empty($roleIdArray)) {
            $params['search']['role_id'] = [$roleIdArray, 'in'];
            $userList = app($this->userService)->userSystemList($params);
        }
        return $userList;
    }

    /**
     * 新建用车申请前判断申请时间段是否与现有用车有冲突
     *
     * @param array
     *
     * @return boolean
     *
     * @author miaochenchen
     *
     * @since 2016-11-02
     */
    public function getNewVehiclesDateWhetherConflict($param, $loginUserInfo) {
        $user_id = $loginUserInfo['user_id'];
        $result = app($this->vehiclesApplyRepository)->getNewVehiclesDateWhetherConflict($param, $user_id);
        return $result;
    }
    public function getVehiclesConflict($param, $loginUserInfo) {
        $user_id = $loginUserInfo['user_id'];
        $result = app($this->vehiclesApplyRepository)->getVehiclesConflict($param, $user_id);
        return $result;
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
    public function getAllVehiclesApplyList($param) {
        $param = $this->parseParams($param);
        if(isset($param['vehicles_name']) && !empty($param['vehicles_name'])) {
            $vehiclesName = explode('(', $param['vehicles_name']);
            $param['vehicles_name'] = $vehiclesName[0];
            if(isset($vehiclesName[1])) {
                $vehiclesCode = explode(')', $vehiclesName[1]);
                $param['vehicles_code'] = $vehiclesCode[0];
            }
        }
        return $this->response(app($this->vehiclesApplyRepository), 'getAllVehiclesApplyListCount', 'getAllVehiclesApplyList', $param);
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
        $result = app($this->vehiclesRepository)->getAllVehiclesCodeList();
        return $result;
    }

    /**
     * 导出用车分析列表数据
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since 2016-12-02
     */
    public function exportVehiclesAnalysisData($params) {
        $vehiclesAnalysisData = app($this->vehiclesApplyRepository)->vehiclesApprovalList($params);
        $header = [
            'vehicles_name'             => trans('vehicles.vehicle_name'),// 车辆名称
            'vehicles_code'             => trans('vehicles.vehicles_code'),//车牌号
            'user_name'                 => trans('vehicles.vehicles_apply_user'),//申请人
            'dept_name'                 => trans('vehicles.vehicles_dept'),//所属部门
            'vehicles_apply_begin_time' => trans('vehicles.vehicles_apply_begin_time'),//开始时间
            'vehicles_apply_end_time'   => trans('vehicles.vehicles_apply_end_time'),// 结束时间
            'vehicles_apply_return_time'=> trans('vehicles.vehicles_return_time'),//归还时间
            'vehicles_apply_mileage'    => trans('vehicles.vehicles_apply_mileage'),//里程
            'vehicles_apply_oil'        => trans('vehicles.vehicles_apply_oil'),//油耗
            'vehicles_apply_status'     => trans('vehicles.vehicles_apply_status'),//状态
            'vehicles_apply_path_start' => trans('vehicles.vehicles_apply_path_start'),//出发地
            'vehicles_apply_path_end'   => trans('vehicles.vehicles_apply_path_end'),//目的地
            'vehicles_apply_reason'     => trans('vehicles.vehicles_apply_reason'),//事由
            'vehicles_apply_remark'     => trans('vehicles.vehicles_apply_remark')//备注
        ];
        foreach($vehiclesAnalysisData as $k => $v) {
            $data[$k]['vehicles_name']             = $v['vehicles_name'];
            $data[$k]['vehicles_code']             = $v['vehicles_code'];
            $data[$k]['user_name']                 = $v['user_name'];
            $data[$k]['dept_name']                 = $v['vehicles_apply_belongs_to_user_system_info']['user_system_info_belongs_to_department']['dept_name'];
            $data[$k]['vehicles_apply_begin_time'] = $v['vehicles_apply_begin_time'];
            $data[$k]['vehicles_apply_end_time']   = $v['vehicles_apply_end_time'];
            if($v['vehicles_apply_return_time'] == '0000-00-00 00:00:00') {
                $v['vehicles_apply_return_time'] = '';
            }
            $data[$k]['vehicles_apply_return_time']= $v['vehicles_apply_return_time'];
            $data[$k]['vehicles_apply_mileage']    = $v['vehicles_apply_mileage'];
            $data[$k]['vehicles_apply_oil']        = $v['vehicles_apply_oil'];
            switch ($v['vehicles_apply_status']) {
                case '1':
                    if($v['vehicles_apply_time'] == NULL || $v['vehicles_apply_time'] == '0000-00-00 00:00:00') {
                        $v['vehicles_apply_status'] = trans('vehicles.approval_pending');
                    }else{
                        $v['vehicles_apply_status'] = trans('vehicles.pending');
                    }
                    break;
                case '2':
                    $v['vehicles_apply_status'] = trans('vehicles.approved');
                    break;
                case '3':
                    $v['vehicles_apply_status'] = trans('vehicles.refused');
                    break;
                case '4':
                    $v['vehicles_apply_status'] = trans('vehicles.wait_acceptance');
                    break;
                case '5':
                    $v['vehicles_apply_status'] = trans('vehicles.acceptanced');
                    break;
                case '6':
                    $v['vehicles_apply_status'] = trans('vehicles.rejected');
                    break;
                default:
                    $v['vehicles_apply_status'] = '';
                    break;
            }
            $data[$k]['vehicles_apply_status']     = $v['vehicles_apply_status'];
            $data[$k]['vehicles_apply_path_start'] = $v['vehicles_apply_path_start'];
            $data[$k]['vehicles_apply_path_end']   = $v['vehicles_apply_path_end'];
            $data[$k]['vehicles_apply_reason']     = $v['vehicles_apply_reason'];
            $data[$k]['vehicles_apply_remark']     = $v['vehicles_apply_remark'];
        }
        return compact('header', 'data');
    }

    /**
     * 用车维护开始消息定时提醒
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since 2017-01-16
     */
    public function vehiclesMaintenanceBeginRemind($interval) {
        $currentTime = date("Y-m-d H:i");
        $start  = $currentTime.':00';
        $end    = $currentTime.':59';
        $search = ['vehicles_maintenance_begin_time' => [[$start, $end], 'between']];
        $result = app($this->vehiclesMaintenanceRepository)->vehiclesMaintenanceRemind($search);
        $message = [];
        // 获取有车辆管理菜单权限的人
        $toUser = implode(',', app($this->userMenuService)->getMenuRoleUserbyMenuId(604));
        if(!empty($result)) {
            foreach ($result as $beginRemind) {
                if(!empty($beginRemind['vehicles_code'])) {
                    $beginRemind['vehicles_name'] = $beginRemind['vehicles_name'].'('.$beginRemind['vehicles_code'].')';
                };
                $comboboxTableName = get_combobox_table_name(20);
                $message[] = [
                    'remindMark'   => 'car-start',
                    'toUser'       => $toUser,
                    'contentParam' => [
                        'carName'      => $beginRemind['vehicles_name'],
                        'maintainType' => mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_".$beginRemind['field_id']),
                        'maintainTime' => date("Y-m-d H:i", strtotime($beginRemind['vehicles_maintenance_begin_time'])).' ~ '.date("Y-m-d H:i", strtotime($beginRemind['vehicles_maintenance_end_time']))
                    ],
                    'stateParams'  => [
                        'vehicles_maintenance_id' => $beginRemind['vehicles_maintenance_id']
                    ]
                ];
            }
        }
        return $message;
    }

    /**
     * 用车维护结束消息定时提醒
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since 2017-01-16
     */
    public function vehiclesMaintenanceEndRemind($interval) {
        $currentTime = date("Y-m-d H:i");
        $start  = $currentTime.':00';
        $end    = $currentTime.':59';
        $search = [
            'vehicles_maintenance_end_time' => [[$start, $end], 'between'],
            'manual_end' => ['1', '!=']
        ];
        $result = app($this->vehiclesMaintenanceRepository)->vehiclesMaintenanceRemind($search);
        $message = [];
        // 获取有车辆管理菜单权限的人
        $toUser = implode(',', app($this->userMenuService)->getMenuRoleUserbyMenuId(604));
        $comboboxTableName = get_combobox_table_name(20);
        if(!empty($result)) {
            foreach ($result as $endRemind) {
                if(!empty($endRemind['vehicles_code'])) {
                    $endRemind['vehicles_name'] = $endRemind['vehicles_name'].'('.$endRemind['vehicles_code'].')';
                };
                $message[] = [
                    'remindMark'   => 'car-end',
                    'toUser'       => $toUser,
                    'contentParam' => [
                        'carName'         => $endRemind['vehicles_name'],
                        'maintainType'    => mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_".$endRemind['field_id']),
                        'maintainEndTime' => date("Y-m-d H:i", strtotime($endRemind['vehicles_maintenance_end_time']))
                    ],
                    'stateParams'  => [
                        'vehicles_maintenance_id' => $endRemind['vehicles_maintenance_id']
                    ]
                ];
            }
        }
        return $message;
    }

    /**
     * 获取车辆使用情况表格
     *
     * @return string
     *
     * @author miaochenchen
     *
     * @since 2017-02-06
     */
    public function getVehiclesUsageTable($param) {
        $tableList    = array();
        $vehiclesList = app($this->vehiclesRepository)->getAllVehicles($param);
        $vehiclesApplyResult = app($this->vehiclesApplyRepository)->getAllVehiclesApplyList($param);
        if($param['type'] == 'day') {
            $currentDay = $param['currentDay'];
            if(!empty($vehiclesList)) {
                foreach($vehiclesList as $vehiclesKey => $vehiclesValue) {
                    for($i=0;$i<=23;$i++) {
                        $count = "";
                        $currentTime = sprintf("%02d", $i);
                        $hourStart = $currentDay.' '.$currentTime.':00:00';
                        if($i == 23) {
                            $hourEnd = date('Y-m-d',strtotime('+1 day',strtotime($currentDay))).' 00:00:00';
                        }else{
                            $endTime = sprintf("%02d", $i + 1);
                            $hourEnd = $currentDay.' '.$endTime.':00:00';
                        }
                        $tempArray = $this->getVehiclesListArray($vehiclesApplyResult, $vehiclesValue, $hourStart, $hourEnd, $i, $tableList, $count);
                        $tableList = array_merge($tableList, $tempArray);
                    }
                }
            }
        }elseif($param['type'] == 'week') {
            $firstDay = $param['currentDay'];
            if(!empty($vehiclesList)) {
                foreach($vehiclesList as $vehiclesKey => $vehiclesValue) {
                    for($j=0;$j<=6;$j++) {
                        $count = "";
                        if($j == 0) {
                            $hourStart = $firstDay.' 00:00:00';
                            $hourEnd   = $firstDay.' 23:59:59';
                            $currentDate = $firstDay;
                        }else{
                            $timeStr = $firstDay.' +'.$j.' day';
                            $hourStart = date('Y-m-d',strtotime($timeStr)).' 00:00:00';
                            $hourEnd   = date('Y-m-d',strtotime($timeStr)).' 23:59:59';
                            $currentDate = date('Y-m-d',strtotime($timeStr));
                        }
                        $tempArray = $this->getVehiclesListArray($vehiclesApplyResult, $vehiclesValue, $hourStart, $hourEnd, $currentDate, $tableList, $count);
                        $tableList = array_merge($tableList, $tempArray);
                    }
                }
            }
        }elseif($param['type'] == 'month') {
            $firstDay      = $param['currentDay'];
            $dateArray     = explode('-', $firstDay);
            $currentMonth  = $dateArray[1];
            $currentYear   = $dateArray[0];
            $thisMonthDays = cal_days_in_month(CAL_EASTER_DEFAULT, $currentMonth, $currentYear);
            foreach($vehiclesList as $vehiclesKey => $vehiclesValue) {
                for($j=1;$j<=$thisMonthDays;$j++) {
                    $count = "";
                    $currentDay = sprintf("%02d", $j);
                    $hourStart = $currentYear.'-'.$currentMonth.'-'.$currentDay.' 00:00:00';
                    $hourEnd   = $currentYear.'-'.$currentMonth.'-'.$currentDay.' 23:59:59';
                    $tempArray = $this->getVehiclesListArray($vehiclesApplyResult, $vehiclesValue, $hourStart, $hourEnd, $j, $tableList, $count);
                    $tableList = array_merge($tableList, $tempArray);
                }
            }
        }
        return $tableList;
    }

    public function getVehiclesListArray($vehiclesApplyResult, $vehiclesValue, $start, $end, $currentFlag, $tableList, $count) {
        $vehiclesNameCode = $vehiclesValue['vehicles_name'].'('.$vehiclesValue['vehicles_code'].')';
        if(!empty($vehiclesApplyResult)) {
            foreach($vehiclesApplyResult as $applyKey => $applyValue) {
                if($applyValue['vehicles_id'] == $vehiclesValue['vehicles_id']) {
                    if(($applyValue['vehicles_apply_begin_time'] < $end) && ($applyValue['vehicles_apply_end_time'] > $start)) {
                        $count++;
                        $tableList[$vehiclesNameCode][$currentFlag]['list'][] = $applyValue;
                    }else{
                        continue;
                    }
                }else{
                    continue;
                }
            }
            $tableList[$vehiclesNameCode][$currentFlag]['count'] = $count;
        }else{
            $tableList[$vehiclesNameCode][$currentFlag]['list']  = "";
            $tableList[$vehiclesNameCode][$currentFlag]['count'] = "";
        }
        return $tableList;
    }

    /**
     * 根据车辆名称获取车辆ID
     *
     * @return string
     *
     * @author miaochenchen
     *
     * @since 2017-03-20
     */
    public function getVehiclesIdByVehiclesName($param) {
        $param = $this->parseParams($param);
        if(isset($param['vehicles_name']) && !empty($param['vehicles_name'])) {
            $vehiclesName = explode('(', $param['vehicles_name']);
            $param['vehicles_name'] = $vehiclesName[0];
            if(isset($vehiclesName[1])) {
                $vehiclesCode = explode(')', $vehiclesName[1]);
                $param['vehicles_code'] = $vehiclesCode[0];
            }
        }
        return app($this->vehiclesRepository)->getVehiclesIdByVehiclesName($param);
    }

    public function addVehiclesSort($data) {
        $updateData                           = $data;
        $updateData["vehicles_sort_order"] = isset($data["vehicles_sort_order"]) ? $data["vehicles_sort_order"]:0;
        $updateData["vehicles_sort_time"]  = date('Y-m-d H:i:s');
        // $updateData["vehicles_approvel_user"] = isset($data['member_manage']) ? $data['member_manage'] : '';
        // 协作分类的权限范围为全体的处理
        if(isset($data["member_user"]) && $data["member_user"] == 'all') {
            $updateData['member_user'] = "all";
            unset($data['member_user']);
        } elseif(isset($data["member_user"])) {
                $updateData['member_user'] = $data["member_user"];
            }else{
                $updateData['member_user'] = '';
            }
        if(isset($data["member_dept"]) && $data["member_dept"] == 'all') {
            $updateData['member_dept'] = "all";
            unset($data['member_dept']);
        } elseif(isset($data["member_dept"])) {
            $updateData['member_dept'] = $data["member_dept"];
        }else {
            $updateData['member_dept'] = '';
        }
        if(isset($data["member_role"]) && $data["member_role"] == 'all') {
            $updateData['member_role'] = "all";
            unset($data['member_role']);
        } elseif(isset($data["member_role"])) {
            $updateData['member_role'] = $data["member_role"];
        }else {
            $updateData['member_role'] = '';
        }
        $sortData              = array_intersect_key($updateData,array_flip(app($this->vehiclesSortRepository)->getTableColumns()));
        $vehiclesSortObject    = app($this->vehiclesSortRepository)->insertData($sortData);
        $sortId                = $vehiclesSortObject->vehicles_sort_id;
        $member_user           = isset($data["member_user"]) ? $data["member_user"]:"";
        $member_role           = isset($data["member_role"]) ? $data["member_role"]:"";
        $member_dept           = isset($data["member_dept"]) ? $data["member_dept"]:"";
        // 插入分类权限数据
        if (!empty($member_user)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($member_user,","))) as $v) {
                $userData[] = ['vehicles_sort_id' => $sortId, 'user_id' => $v];
            }
            app($this->vehiclesSortMemberUserRepository)->insertMultipleData($userData);
        }
        if (!empty($member_role)) {
            $roleData = [];
            foreach (array_filter(explode(',', trim($member_role,","))) as $v) {
                $roleData[] = ['vehicles_sort_id' => $sortId, 'role_id' => $v];
            }
            app($this->vehiclesSortMemberRoleRepository)->insertMultipleData($roleData);
        }
        if (!empty($member_dept)) {
            $deptData = [];
            foreach (array_filter(explode(',', trim($member_dept,","))) as $v) {
                $deptData[] = ['vehicles_sort_id' => $sortId, 'dept_id' => $v];
            }
            app($this->vehiclesSortMemberDepartmentRepository)->insertMultipleData($deptData);
        }
        return $sortId;
    }

    public function getSort($param) {
        $param      = $this->parseParams($param);
        $returnData = $this->response(app($this->vehiclesSortRepository), 'getTotal', 'getVehiclesSortListRepository', $param);
        $list       = $returnData["list"];
        if(isset($returnData["total"]) && $returnData["total"]) {
            foreach ($list as $key => $value) {
                if($value->memberDept != "all" && $value->memberUser != "all" && $value->memberRole != "all") {
                    if(count($value->sortHasManyUser)) {
                        $hasUserName = "";
                        $userCollect = $value->sortHasManyUser->toArray();

                        foreach ($userCollect as $userKey => $userItem) {
                            $userItem["has_one_user"] = is_array($userItem["has_one_user"]) ? $userItem["has_one_user"] : [];
                            if(isset($userItem["has_one_user"]) && count($userItem["has_one_user"]) && $userItem["has_one_user"]["user_name"]) {
                                $hasUserName .= $userItem["has_one_user"]["user_name"].",";
                            }
                        }
                        $hasUserName = trim($hasUserName,",");
                        if($hasUserName) {
                            $list[$key]["sortHasManyUserName"] = $hasUserName;
                        }
                    }
                    if(count($value->sortHasManyRole)) {
                        $hasRoleName = "";
                        $roleCollect = $value->sortHasManyRole->toArray();
                        foreach ($roleCollect as $roleKey => $roleItem) {
                            if(isset($roleItem["has_one_role"]) && count($roleItem["has_one_role"]) && $roleItem["has_one_role"]["role_name"]) {
                                $hasRoleName .= $roleItem["has_one_role"]["role_name"].",";
                            }
                        }
                        $hasRoleName = trim($hasRoleName,",");
                        if($hasRoleName) {
                            $list[$key]["sortHasManyRoleName"] = $hasRoleName;
                        }
                    }
                    if(count($value->sortHasManyDept)) {
                        $hasDeptName = "";
                        $deptCollect = $value->sortHasManyDept->toArray();
                        foreach ($deptCollect as $deptKey => $deptItem) {
                            if(isset($deptItem["has_one_dept"]) && count($deptItem["has_one_dept"]) && $deptItem["has_one_dept"]["dept_name"]) {
                                $hasDeptName .= $deptItem["has_one_dept"]["dept_name"].",";
                            }
                        }
                        $hasDeptName = trim($hasDeptName,",");
                        if($hasDeptName) {
                            $list[$key]["sortHasManyDeptName"] = $hasDeptName;
                        }
                    }
                }
            }
        }
        $returnData["list"] = $list;
        return $returnData;
    }

    public function getVehiclesSortDetail ($sortId) {
        if($result = app($this->vehiclesSortRepository)->vehiclesSortData($sortId)) {
            if(count($result->sortHasManyUser)){
                $sort_user = $result->sortHasManyUser->pluck("user_id");
            }
            if(count($result->sortHasManyRole)){
                $sort_role = $result->sortHasManyRole->pluck("role_id");
            }
            if(count($result->sortHasManyDept)){
                $sort_dept = $result->sortHasManyDept->pluck("dept_id");
            }
            $result = $result->toArray();

            if(isset($sort_user))
                $result["user_id"] = $sort_user;
            if(isset($sort_role))
                $result["role_id"] = $sort_role;
            if(isset($sort_dept))
                $result["dept_id"] = $sort_dept;
            return $result;
        }
        return ['code' => ['0x000003','common']];
    }

    public function editVehiclesSortDetail($data, $sortId) {
        $updateData = $data;

        // $updateData["vehicles_approvel_user"] = isset($data['member_manage']) ? $data['member_manage'] : '';
        // 协作分类的权限范围为全体的处理
        if(isset($data["member_user"]) && $data["member_user"] == 'all') {
            $updateData['member_user'] = "all";
            unset($data['member_user']);
        } else {
            $updateData['member_user'] = $data["member_user"];
        }
        if(isset($data["member_dept"]) && $data["member_dept"] == 'all') {
            $updateData['member_dept'] = "all";
            unset($data['member_dept']);
        } else {
            $updateData['member_dept'] = $data["member_dept"];
        }
        if(isset($data["member_role"]) && $data["member_role"] == 'all') {
            $updateData['member_role'] = "all";
            unset($data['member_role']);
        } else {
            $updateData['member_role'] = $data["member_role"];
        }
        $sortData = array_intersect_key($updateData,array_flip(app($this->vehiclesSortRepository)->getTableColumns()));
        app($this->vehiclesSortRepository)->updateData($sortData, ['vehicles_sort_id' => $sortId]);
        // 先删除已有协作分类权限数据
        $where = ['vehicles_sort_id' => [$sortId]];
        app($this->vehiclesSortMemberUserRepository)->deleteByWhere($where);
        app($this->vehiclesSortMemberRoleRepository)->deleteByWhere($where);
        app($this->vehiclesSortMemberDepartmentRepository)->deleteByWhere($where);
        $member_user = isset($data["member_user"]) ? $data["member_user"]:"";
        $member_role = isset($data["member_role"]) ? $data["member_role"]:"";
        $member_dept = isset($data["member_dept"]) ? $data["member_dept"]:"";
        // 插入协作分类权限数据
        if (!empty($member_user)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($member_user,","))) as $v) {
                $userData[] = ['vehicles_sort_id' => $sortId, 'user_id' => $v];
            }
            app($this->vehiclesSortMemberUserRepository)->insertMultipleData($userData);
        }
        if (!empty($member_role)) {
            $roleData = [];
            foreach (array_filter(explode(',', trim($member_role,","))) as $v) {
                $roleData[] = ['vehicles_sort_id' => $sortId, 'role_id' => $v];
            }
            app($this->vehiclesSortMemberRoleRepository)->insertMultipleData($roleData);
        }
        if (!empty($member_dept)) {
            $deptData = [];
            foreach (array_filter(explode(',', trim($member_dept,","))) as $v) {
                $deptData[] = ['vehicles_sort_id' => $sortId, 'dept_id' => $v];
            }
            app($this->vehiclesSortMemberDepartmentRepository)->insertMultipleData($deptData);
        }
        return "1";
    }

    public function deleteVehiclesSort($sortIdString) {
        foreach (explode(',', trim($sortIdString,",")) as $key=>$sortId) {
            if($sortDataObject = app($this->vehiclesSortRepository)->vehiclesSortData($sortId)) {
                // 删除协作分类权限
                $where = ['vehicles_sort_id' => [$sortId]];
                app($this->vehiclesSortMemberUserRepository)->deleteByWhere($where);
                app($this->vehiclesSortMemberRoleRepository)->deleteByWhere($where);
                app($this->vehiclesSortMemberDepartmentRepository)->deleteByWhere($where);
                app($this->vehiclesSortRepository)->deleteById($sortId);
                // if(count($sortDataObject->sortHasManySubjectList)) {
                //     $subjectList = $sortDataObject->sortHasManySubjectList;
                //     foreach ($subjectList as $key => $value) {
                //         $this->deletevehiclesSubjectRealize($value->vehicles_apply_id);
                //     }
                // }
            }
        }
        return "1";
    }

    /**
     * 获取有权限的车辆类别列表
     *
     * @return [type] [description]
     */
    function getPermessionVehiclesSort($data) {
        return app($this->vehiclesSortRepository)->getPermissionVehiclesSortList($data);
    }
    // 获取车辆名称和车牌号(自定义字段下拉框解析用)
    function getVehiclesNameCode ($param) {
        $param = $this->parseParams($param);
        $vehiclesInfo = app($this->vehiclesRepository)->getVehiclesNameCode($param);
        if (!empty($vehiclesInfo)) {
            foreach($vehiclesInfo as $key => $value) {
                $vehiclesInfo[$key]['vehicles_name_code'] = $value['vehicles_name'] . "(" . $value['vehicles_code'] . ")";
            }
        }
        return $vehiclesInfo;
    }

    /**
     * 获取车辆保险信息
     *
     * @param array $data
     *
     * @return array
     */
    public function getVehiclesInsurance($data): array
    {
        $info = app($this->vehiclesInsuranceRepository)->infoVehiclesInsurance($data['vehicles_insurance_id']);

        return $info;
    }

    /**
     * 添加车辆保险信息
     *
     * @param array $data
     *
     * @return array
     */
    public function addVehiclesInsurance($data): array
    {
        // 起始时间必须大于结束时间
        if($data['vehicles_insurance_begin_time'] > $data['vehicles_insurance_end_time']) {
            return ['code' => ['0x021030', 'vehicles']];
        }
        // 起始时间和结束时间不能为空
        if(($data['vehicles_insurance_begin_time'] || $data['vehicles_insurance_end_time']) == null) {
            return ['code' => ['0x021031', 'vehicles']];
        }

        // 根据表字段插入数据
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesInsuranceRepository)->getTableColumns()));
        $entity = app($this->vehiclesInsuranceRepository)->insertData($vehiclesData);
        $result =  ['vehicles_insurance_id' => $entity->vehicles_insurance_id];

        return $result;
    }

    /**
     * 编辑车辆保险信息
     *
     * @param array $data
     *
     * @return array
     */
    public function editVehiclesInsurance($data): array
    {

        // 起始时间必须大于结束时间
        if($data['vehicles_insurance_begin_time'] > $data['vehicles_insurance_end_time']) {
            return ['code' => ['0x021030', 'vehicles']];
        }

        // 起始时间和结束时间不能为空
        if(($data['vehicles_insurance_begin_time'] || $data['vehicles_insurance_end_time']) == null) {
            return ['code' => ['0x021031', 'vehicles']];
        }

        /** @var VehiclesInsuranceRepository $repository */
        $repository = app($this->vehiclesInsuranceRepository);
        $insuranceInfo = $repository->infoVehiclesInsurance($data['vehicles_insurance_id']);

        // 若保险信息不存在或者保险已结束均为异常
        if(!count($insuranceInfo)) {
            return ['code' => ['0x021003', 'vehicles']];
        }elseif(strtotime($insuranceInfo['vehicles_insurance_end_time']) <= date("Y-m-d 00:00:00", time())) {
            return ['code' => ['0x021007', 'vehicles']];
        }
        // 根据表字段插入数据
        $vehiclesData = array_intersect_key($data, array_flip($repository->getTableColumns()));
        $result =  $repository->updateData($vehiclesData, ['vehicles_insurance_id' => $vehiclesData['vehicles_insurance_id']]);

        return ['result' => $result];
    }

    /**
     * 删除车辆保险信息
     *
     * @param int|string $data
     *
     * @return bool
     */
    public function deleteVehiclesInsurance($vehicles_insurance_id): bool
    {
        $destroyIds = explode(',', $vehicles_insurance_id);
        /** @var VehiclesInsuranceRepository $repository */
        $repository = app($this->vehiclesInsuranceRepository);
        $where = [
            'vehicles_insurance_id' => [$destroyIds, 'in']
        ];

        return $repository->deleteByWhere($where);
    }

    /**
     * 获取车辆年检信息
     *
     * @param array $data
     *
     * @return array
     */
    public function getVehiclesAnnualInspection($data): array
    {
        $info = app($this->vehiclesAnnualInspectionRepository)->infoVehiclesAnnualInspection($data['vehicles_annual_inspection_id']);

        return $info;
    }

    /**
     * 添加车辆年检信息
     *
     * @param array $data
     *
     * @return array
     */
    public function addVehiclesAnnualInspection($data): array
    {
        // 起始时间必须大于结束时间
        if($data['vehicles_annual_inspection_begin_time'] > $data['vehicles_annual_inspection_end_time']) {
            return ['code' => ['0x021030', 'vehicles']];
        }
        // 起始时间和结束时间不能为空
        if(($data['vehicles_annual_inspection_begin_time'] || $data['vehicles_annual_inspection_end_time']) == null) {
            return ['code' => ['0x021031', 'vehicles']];
        }

        // 根据表字段插入数据
        $vehiclesData = array_intersect_key($data, array_flip(app($this->vehiclesAnnualInspectionRepository)->getTableColumns()));
        $entity = app($this->vehiclesAnnualInspectionRepository)->insertData($vehiclesData);
        $result =  ['vehicles_insurance_id' => $entity->vehicles_insurance_id];

        return $result;
    }

    /**
     * 编辑车辆年检信息
     *
     * @param array $data
     *
     * @return array
     */
    public function editVehiclesAnnualInspection($data): array
    {
        // 起始时间必须大于结束时间
        if($data['vehicles_annual_inspection_begin_time'] > $data['vehicles_annual_inspection_end_time']) {
            return ['code' => ['0x021030', 'vehicles']];
        }

        // 起始时间和结束时间不能为空
        if(($data['vehicles_annual_inspection_begin_time'] || $data['vehicles_annual_inspection_end_time']) == null) {
            return ['code' => ['0x021031', 'vehicles']];
        }

        /** @var vehiclesAnnualInspectionRepository $repository */
        $repository = app($this->vehiclesAnnualInspectionRepository);
        $insuranceInfo = $repository->infoVehiclesAnnualInspection($data['vehicles_annual_inspection_id']);

        // 若保险信息不存在或者保险已结束均为异常
        if(!count($insuranceInfo)) {
            return ['code' => ['0x021003', 'vehicles']];
        }elseif(strtotime($insuranceInfo['vehicles_annual_inspection_end_time']) <= date("Y-m-d 00:00:00", time())) {
            return ['code' => ['0x021007', 'vehicles']];
        }
        // 根据表字段插入数据
        $vehiclesData = array_intersect_key($data, array_flip($repository->getTableColumns()));
        $result =  $repository->updateData($vehiclesData, ['vehicles_annual_inspection_id' => $vehiclesData['vehicles_annual_inspection_id']]);

        return ['result' => $result];
    }

    /**
     * 删除车辆年检信息
     *
     * @param string|int $insurance
     *
     * @return bool
     */
    public function deleteVehiclesAnnualInspection($insurance)
    {
        $destroyIds = explode(',', $insurance);
        /** @var VehiclesInsuranceRepository $repository */
        $repository = app($this->vehiclesAnnualInspectionRepository);
        $where = [
            'vehicles_annual_inspection_id' => [$destroyIds, 'in']
        ];
        return $repository->deleteByWhere($where);
    }

    /**
     * 获取车辆所属分类
     *
     * @param array $param
     *
     * @return array
     */
    function getVehiclesSort($param)
    {
        $vehiclesInfo = app($this->vehiclesSortRepository)->getVehiclesSort($param);

        return $vehiclesInfo;
    }

    /**
     * 自定义字段新增车辆验证
     *
     * @param array $data 请求参数
     *
     * @return array
     */
    public function vehiclesCustomFieldsValidate($data): array
    {
//        $validator = Validator::make($data, [
//            'vehicles_code' => 'required|max:32|unique:vehicles,vehicles_code',
//        ]);
//
//        if ($validator->fails()) {
//            dd($validator->errors()->all());
//        }
//
//        if ($validator->errors()) {
//
//            $errorMessage = $validator->errors()->getMessages();
//            $code = ['code' => [$errorMessage['vehicles_code'], 'vehicles']];
//
//            return $code;
//        }
        $code = Arr::get($data, 'vehicles_code', '');


        // 车牌号 不能大于 32 个字符。
        if (mb_strlen($code) > 32) {
            return ['code' => ['0x021028', 'vehicles']];
        }

        /** @var VehiclesRepository $repository */
        $repository = app($this->vehiclesRepository);

        $vehiclesId = Arr::get($data, 'vehicles_id', '');
        // 车牌号 已经存在。
        if ($repository->isExistingVehicleCode($code, $vehiclesId)) {
            return ['code' => ['0x021027', 'vehicles']];
        }

        return [];
    }

    /**
     * 自定义字段删除车辆验证
     *
     * @param string|array $vehiclesId
     *
     * @see FormModelingService::deleteCustomData()
     *
     * @return array
     */
    public function vehiclesCustomFieldsDeleteValidate($vehiclesId): array
    {
        // 统一将车辆id参数处理为数组形式
        if (is_string($vehiclesId)) {
            $ids = explode(',', $vehiclesId);
        } elseif (is_array($vehiclesId)) {
            $ids = $vehiclesId;
        } else {
            $ids = [];
        }

        //当车辆ID被占用时 不可以删除
        /** @var VehiclesApplyRepository $applyRepository */
        $applyRepository = app($this->vehiclesApplyRepository);
        /** @var VehiclesMaintenanceRepository $maintenanceRepository */
        $maintenanceRepository = app($this->vehiclesMaintenanceRepository);
        /** @var VehiclesInsuranceRepository $insuranceRepository */
        $insuranceRepository = app($this->vehiclesInsuranceRepository);
        /** @var VehiclesAnnualInspectionRepository $annualInspectionRepository */
        $annualInspectionRepository = app($this->vehiclesAnnualInspectionRepository);

        //根据查询车辆记录 或者 车辆维护 或者 车辆保险 或者 车辆年检
        $isInApplication = $applyRepository->isInApplication($ids);
        $isInMaintenance = $maintenanceRepository->isInMaintenance($ids);
        $isInInsurance = $insuranceRepository->isInInsurance($ids);
        $isInAnnualInspection = $annualInspectionRepository->isInAnnualInspection($ids);

        if ($isInApplication || $isInMaintenance || $isInInsurance || $isInAnnualInspection) {
            // 车辆被占用，不可以被删除
            return ['code' => ['0x021004', 'vehicles']];
        }

        /**
         * 删除车辆需删除车辆相关信息
         *  1. 车辆申请记录
         *  2. 车辆维护记录
         *  3. 车辆保险记录
         *  4. 车辆年检记录
         *  5. 车辆附件相关
         */
        // 1.删除车辆申请
        $where = [
            'vehicles_id' => [$ids, 'in'],
        ];
        $applyRepository->deleteByWhere($where);

        // 2.删除车辆维护
        $where = [
            'vehicles_id' => [$ids, 'in'],
        ];
        $maintenanceRepository->deleteByWhere($where);

        // 3.删除车辆保险
        $where = [
            'vehicles_id' => [$ids, 'in'],
        ];
        $insuranceRepository->deleteByWhere($where);

        // 4.删除车辆年检
        $where = [
            'vehicles_id' => [$ids, 'in'],
        ];
        $annualInspectionRepository->deleteByWhere($where);

        // 5.删除车辆附件
        /** @var AttachmentService $attachmentService */
        $attachmentService = app($this->attachmentService);
        foreach ($vehiclesId as $id) {
            $vehiclesAttachmentData = ['entity_table' => 'vehicles', 'entity_id' => $id];
            $attachmentService->deleteAttachmentByEntityId($vehiclesAttachmentData);
            unset($vehiclesAttachmentData);
        }

        return [];
    }

    /**
     * 判断车辆是否已删除
     *
     * @param string|array $vehiclesId
     */
    public function vehiclesCustomFieldsValidateDelete($dataId): array
    {
        /** @var VehiclesRepository $repository */
        $repository = app($this->vehiclesRepository);
        $vehicles = $repository->isExistingVehicle($dataId);

        if (!$vehicles) {
            return ['code' => ['0x021029', 'vehicles']];
        }

        return [];
    }

    /**
     * 结束车辆年检
     *
     * @param string|int $vehiclesAnnualInspectionId
     */
    public function endVehiclesAnnualInspection($vehiclesAnnualInspectionId) {
        $currentTime = date("Y-m-d H:i:s", time());
        $vehiclesData = [
            "vehicles_annual_inspection_end_time" => $currentTime,
        ];

        /** @var VehiclesAnnualInspectionRepository $repository */
        $repository = app($this->vehiclesAnnualInspectionRepository);
        if($result = $repository->updateData($vehiclesData, ['vehicles_annual_inspection_id' => $vehiclesAnnualInspectionId])) {
            $vehiclesAnnualInspectionDetail = $repository->infoVehiclesAnnualInspection($vehiclesAnnualInspectionId);
            if($vehiclesAnnualInspectionDetail) {
                if(!empty($vehiclesAnnualInspectionDetail['vehicles_code'])) {
                    $vehiclesAnnualInspectionDetail['vehicles_name'] = $vehiclesAnnualInspectionDetail['vehicles_name'].'('.$vehiclesAnnualInspectionDetail['vehicles_code'].')';
                }
            }
//            $comboboxTableName = get_combobox_table_name(20);
//            $toUser = implode(app($this->userMenuService)->getMenuRoleUserbyMenuId(604), ',');
//            $sendData['remindMark']   = 'car-end';
//            $sendData['toUser']       = $toUser;
//            $sendData['contentParam'] = [
//                'carName'        => $vehiclesMaintenanceDetail['vehicles_name'],
//                'maintainType'   => mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_".$vehiclesMaintenanceDetail['field_id']),
//                'maintainEndTime'=> $currentTime
//            ];
//            $sendData['stateParams']  = ['vehicles_maintenance_id' => $vehiclesMaintenanceId];
//            Eoffice::sendMessage($sendData);
        }
        return $result;
    }

    /**
     * 获取车辆保险/年检消息配置详情
     *
     * @return array
     */
    public function getInsuranceNotifyConfig($data): array
    {
        /** @var VehiclesInsuranceMessageConfigRepository $repository */
        $repository = app($this->vehiclesInsuranceMessageConfigRepository);
        $type = $data['type'] ?? 'insurance';

        return $repository->getInsuranceNotifyConfig($type);
    }

    /**
     * 获取车辆保险/年检消息配置详情
     *
     * @return array
     */
    public function updateInsuranceNotifyConfig($data)
    {
        /** @var VehiclesInsuranceMessageConfigRepository $repository */
        $repository = app($this->vehiclesInsuranceMessageConfigRepository);

        if (empty($data['config'] || empty($data['type']))) {
            return [];
        }
        if (!in_array($data['type'], ['insurance', 'inspection'])) {
            return [];
        }

        // 若对应通知开关开启则需判断对应参数
        if ($data['type'] === 'insurance' && $data['config']['current_month_notify_switch'] == 1) {
            if ($data['config']['current_month_notify_switch'] == 1) {
                if (empty($data['config']['current_month_notify_time'])) {
                    return ['code' => ['0x021034', 'vehicles']];
                }
                if (empty($data['config']['current_month_date'])) {
                    return ['code' => ['0x021032', 'vehicles']];
                }
            }
        }
        if ($data['config']['advance_time_notify_switch'] == 1) {
            if (empty($data['config']['advance_time'])) {
                return ['code' => ['0x021035', 'vehicles']];
            }
            if (empty($data['config']['advance_notify_time'])) {
                return ['code' => ['0x021034', 'vehicles']];
            }
        }
        if ($data['config']['next_month_notify_switch'] == 1) {
            if (empty($data['config']['next_month_notify_time'])) {
                return ['code' => ['0x021034', 'vehicles']];
            }
            if (empty($data['config']['next_month_date'])) {
                return ['code' => ['0x021036', 'vehicles']];
            }
        }

        //TODO 获取需要更新的配置去除未更新和值为空的
        $repository->updateInsuranceNotifyConfig($data['config'], $data['type']);

        return [];
    }
}
