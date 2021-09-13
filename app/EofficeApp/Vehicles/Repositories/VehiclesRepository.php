<?php

namespace App\EofficeApp\Vehicles\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Sms\Entities\SmsReceiveEntity;
use App\EofficeApp\Vehicles\Entities\VehiclesEntity;

/**
 * 车辆管理资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class VehiclesRepository extends BaseRepository {

    public function __construct(VehiclesEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取车辆信息
     *
     * @param type $id
     *
     * @return array
     *
     */
    public function infoVehicles($id) {
        $infoResult = $this->entity->leftJoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id")
                                   ->where('vehicles_id', $id)
                                   ->get()
                                   ->toArray();
        if (!empty($infoResult)) {
            return $infoResult[0];
        } else {
            return [];
        }
    }

    public function deleteVehicles($vehiclesId) {
        return $this->entity->destroy($vehiclesId);
    }
    public function getVehiclesNameCode($param=[]) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        $vehiclesInfo = $this->entity
                             ->select(['vehicles_id', 'vehicles_name', 'vehicles_code'])
                             ->wheres($param['search'])
                             ->get()
                             ->toArray();

        return $vehiclesInfo;
    }

    /**
     * 获取车辆【条件过滤】
     *
     * @param array $data
     * @return array
     */
    public function getAllVehicles($data) {
        $default = [
            'fields' => ["*"],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_id' => 'desc']
        ];
        $param = array_merge($default, array_filter($data));

        $param['fields'] = ['vehicles.*', 'vehicles_sort.*'];
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        if (isset($param['role_id']) && is_array($param['role_id'])) {
            $roleId = implode(",", $param['role_id']);
        }
        $data['type'] = isset($data['type']) ? $data['type'] : 'all';
        if($data['type']== 'day' || $data['type'] == 'week' || $data['type'] == 'month') {
            $data['type'] = 'all';
        }
        $query = $this->entity;
        if(isset($data['type'])) {
            switch ($data['type']) {
                case "free"://车辆空闲
                    $query = $query->select($param['fields'])
                     ->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
                    $tempIds = $this->getUseVehiclesId($param);
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (count($ids)) {
                        $query = $query->whereNotIn("vehicles_id", $ids);
                    }

                    $query = $query->where(function($query)use($roleId, $userId, $deptId){
                        $query = $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_user) or vehicles_sort.member_user = 'all'", [$userId])
                                        ->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_dept) or vehicles_sort.member_dept = 'all'",[$deptId]);
                        $roleId = explode(',', $roleId);
                        if(!empty($roleId)){
                            foreach($roleId as $v){
                                $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_role) or vehicles_sort.member_role = 'all'",[$v]);
                            }
                        }
                    });
                    return $query->orders($param['order_by'])
                                    ->parsePage($param['page'], $param['limit'])
                                    ->get()
                                    ->toArray();

                    break;
                case "used"://车辆使用
                    $tempIds = $this->getUseVehiclesId($param);
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case "no-maintain"://无维护
                    $tempIds = $this->getMaintenanceVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    $tempIds2 = $this->getStandbyVehiclesId();
                    $ids2 = [];
                    foreach ($tempIds2 as $id2) {
                        $ids2[] = $id2['vehicles_id'];
                    }
                    $idTemp = array_merge($ids2, $ids);
                    if (count($idTemp)) {
                        $query = $query->whereNotIn("vehicles_id", $idTemp);
                    }
                    break;
                case "standby"://待维护 //3
                    $tempIds = $this->getStandbyVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case "maintain"://维护中
                    $tempIds = $this->getMaintenanceVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case "all":
                    $query = $query->select($param['fields'])
                     ->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
                    $query = $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_user) or vehicles_sort.member_user = 'all'",[$userId])
                            ->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_dept) or vehicles_sort.member_dept = 'all'",[$deptId]);
                    $roleId = explode(',', $roleId);
                    if(!empty($roleId)){
                        foreach($roleId as $v){
                            $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_role) or vehicles_sort.member_role = 'all'",[$v]);
                        }
                    }
                    if (isset($param['search']['vehicles_name']) && !isset($param['search']['vehicles_name'][1])) {
                        $vehiclesName = explode('(', $param['search']["vehicles_name"][0]);
                        $param['search']['vehicles_name'] = [$vehiclesName[0], 'like'];
                    }
                    return  $query->wheres($param['search'])
                                    ->orders($param['order_by'])
                                    ->parsePage($param['page'], $param['limit'])
                                    ->get()
                                    ->toArray();
                    break;
                default:
                    break;
            }
        }
        $query = $query->select($param['fields'])
                     ->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
        $roleId = explode(',', $roleId);
        if (isset($param['search']['vehicles_name']) && !isset($param['search']['vehicles_name'][1])) {
            $vehiclesName = explode('(', $param['search']["vehicles_name"][0]);
            $param['search']['vehicles_name'] = [$vehiclesName[0], 'like'];
        }
        return  $query->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()
                        ->toArray();
    }

     /**
     * 获取车辆【条件过滤】(不包含权限)
     *
     * @param array $data
     * @return array
     */
    public function getAllVehiclesNoAuth($data) {
        $default = [
            'fields' => ["*"],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_id' => 'desc']
        ];
        $param = array_merge($default, array_filter($data));
        $param['fields'] = ['vehicles.*'];
        $data['type'] = isset($data['type']) ? $data['type'] : 'all';
        $query = $this->entity;
        if(isset($data['type'])) {
            switch ($data['type']) {
                case "free"://车辆空闲
                    $tempIds = $this->getUseVehiclesIdNoAuth($param);
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (count($ids)) {
                        $query = $query->whereNotIn("vehicles_id", $ids);
                    }
                    break;
                case "used"://车辆使用
                    $tempIds = $this->getUseVehiclesIdNoAuth($param);
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case "no-maintain"://无维护
                    $tempIds = $this->getMaintenanceVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    $tempIds2 = $this->getStandbyVehiclesId();
                    $ids2 = [];
                    foreach ($tempIds2 as $id2) {
                        $ids2[] = $id2['vehicles_id'];
                    }
                    $idTemp = array_merge($ids2, $ids);
                    if (count($idTemp)) {
                        $query = $query->whereNotIn("vehicles_id", $idTemp);
                    }
                    break;
                case "standby"://待维护 //3
                    $tempIds = $this->getStandbyVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case "maintain"://维护中
                    $tempIds = $this->getMaintenanceVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case 'to-insure': // 待投保
                    $tempIds = $this->getToInsureVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case 'insurance': // 保障中
                    $tempIds = $this->getInsuranceVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case 'no-insurance': // 无保险
                    $tempIds = $this->getInsuranceVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    $tempIds2 = $this->getToInsureVehiclesId();
                    $ids2 = [];
                    foreach ($tempIds2 as $id2) {
                        $ids2[] = $id2['vehicles_id'];
                    }
                    $idTemp = array_merge($ids2, $ids);
                    if (count($idTemp)) {
                        $query = $query->whereNotIn("vehicles_id", $idTemp);
                    }
                    break;
                case 'for-annual-inspection': // 待年检
                    $tempIds = $this->getToInspectAnnuallyVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case 'inspection': // 已年检
                    $tempIds = $this->getAnnualInspectionVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    if (!count($ids)) {
                        return [];
                    }
                    $query = $query->whereIn("vehicles_id", $ids);
                    break;
                case 'no-annual-inspection': // 无年检
                    $tempIds = $this->getToInspectAnnuallyVehiclesId();
                    $ids = [];
                    foreach ($tempIds as $id) {
                        $ids[] = $id['vehicles_id'];
                    }
                    $tempIds2 = $this->getAnnualInspectionVehiclesId();
                    $ids2 = [];
                    foreach ($tempIds2 as $id2) {
                        $ids2[] = $id2['vehicles_id'];
                    }
                    $idTemp = array_merge($ids2, $ids);
                    if (count($idTemp)) {
                        $query = $query->whereNotIn("vehicles_id", $idTemp);
                    }
                    break;
                default:
                    break;
            }
        }
        if (isset($param['search']['vehicles_name']) && !isset($param['search']['vehicles_name'][1])) {
            $vehiclesName = explode('(', $param['search']["vehicles_name"][0]);
            $param['search']['vehicles_name'] = [$vehiclesName[0], 'like'];
        }
        return $query->select($param['fields'])
                     ->wheres($param['search'])
                     ->orders($param['order_by'])
                     ->parsePage($param['page'], $param['limit'])
                     ->get()
                     ->toArray();
    }

    public function getAllVehiclesTotal($data) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($data));
        $param['fields'] = ['vehicles.*', 'vehicles_sort.*'];
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        if (isset($param['role_id']) && is_array($param['role_id'])) {
            $roleId = implode(",", $param['role_id']);
        }
        $data['type'] = isset($data['type']) ? $data['type'] : 'all';
        $query = $this->entity;
        switch ($data['type']) {
            case "all":
                $query = $query->select($param['fields'])
                     ->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
                    $query = $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_user) or vehicles_sort.member_user = 'all'",[$userId])
                            ->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_dept) or vehicles_sort.member_dept = 'all'",[$deptId]);
                    $roleId = explode(',', $roleId);
                    if(!empty($roleId)){
                        foreach($roleId as $v){
                            $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_role) or vehicles_sort.member_role = 'all'",[$v]);
                        }
                    }
                    return  $query->wheres($param['search'])
                                    ->count();
                break;
            case "free": //车辆空闲
                $query = $query->select($param['fields'])
                       ->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
                $tempIds = $this->getUseVehiclesId($param);
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (count($ids)) {
                    $query = $query->whereNotIn("vehicles_id", $ids);
                }
                $query = $query->where(function($query)use($roleId, $userId, $deptId){
                    $query = $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_user) or vehicles_sort.member_user = 'all'",[$userId])
                                    ->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_dept) or vehicles_sort.member_dept = 'all'",[$deptId]);
                    $roleId = explode(',', $roleId);
                    if(!empty($roleId)){
                        foreach($roleId as $v){
                            $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_role) or vehicles_sort.member_role = 'all'",[$v]);
                        }
                    }
                });
                return $query->count();
                break;
            case "used"://车辆使用
                $tempIds = $this->getUseVehiclesId($param);
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case "no-maintain": //无维护
                //not 维护状态结束的所有车辆 以及 不在维护表的所有ID
                $tempIds = $this->getMaintenanceVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                $tempIds2 = $this->getStandbyVehiclesId();
                $ids2 = [];
                foreach ($tempIds2 as $id2) {
                    $ids2[] = $id2['vehicles_id'];
                }
                $idTemp = array_merge($ids2, $ids);
                if (count($idTemp)) {
                    $query = $query->whereNotIn("vehicles_id", $idTemp);
                }
                break;
            case "standby": //待维护 //3
                $tempIds = $this->getStandbyVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case "maintain": //维护中
                $tempIds = $this->getMaintenanceVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            default:
                break;
        }
        $query = $query->select($param['fields'])->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
        $roleId = explode(',', $roleId);
         return $query
                ->wheres($param['search'])
                ->count();
    }

    public function getAllVehiclesNoAuthTotal($data) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($data));
        $param['fields'] = ['vehicles.*'];
        $data['type'] = isset($data['type']) ? $data['type'] : 'all';
        $query = $this->entity;
        switch ($data['type']) {
            case "free": //车辆空闲
                $tempIds = $this->getAllUseVehiclesId($param);
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (count($ids)) {
                    $query = $query->whereNotIn("vehicles_id", $ids);
                }
                break;
            case "used"://车辆使用
                $tempIds = $this->getAllUseVehiclesId($param);
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case "no-maintain": //无维护
                //not 维护状态结束的所有车辆 以及 不在维护表的所有ID
                $tempIds = $this->getMaintenanceVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                $tempIds2 = $this->getStandbyVehiclesId();
                $ids2 = [];
                foreach ($tempIds2 as $id2) {
                    $ids2[] = $id2['vehicles_id'];
                }
                $idTemp = array_merge($ids2, $ids);
                if (count($idTemp)) {
                    $query = $query->whereNotIn("vehicles_id", $idTemp);
                }
                break;
            case "standby": //待维护 //3
                $tempIds = $this->getStandbyVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case "maintain": //维护中
                $tempIds = $this->getMaintenanceVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case 'to-insure': // 待投保
                $tempIds = $this->getToInsureVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case 'insurance': // 保障中
                $tempIds = $this->getInsuranceVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case 'no-insurance': // 无保险
                $tempIds = $this->getInsuranceVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                $tempIds2 = $this->getToInsureVehiclesId();
                $ids2 = [];
                foreach ($tempIds2 as $id2) {
                    $ids2[] = $id2['vehicles_id'];
                }
                $idTemp = array_merge($ids2, $ids);
                if (count($idTemp)) {
                    $query = $query->whereNotIn("vehicles_id", $idTemp);
                }
                break;
            case 'for-annual-inspection': // 待年检
                $tempIds = $this->getToInspectAnnuallyVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case 'inspection': // 已年检
                $tempIds = $this->getAnnualInspectionVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                if (!count($ids)) {
                    return 0;
                }
                $query = $query->whereIn("vehicles_id", $ids);
                break;
            case 'no-annual-inspection': // 无年检
                $tempIds = $this->getToInspectAnnuallyVehiclesId();
                $ids = [];
                foreach ($tempIds as $id) {
                    $ids[] = $id['vehicles_id'];
                }
                $tempIds2 = $this->getAnnualInspectionVehiclesId();
                $ids2 = [];
                foreach ($tempIds2 as $id2) {
                    $ids2[] = $id2['vehicles_id'];
                }
                $idTemp = array_merge($ids2, $ids);
                if (count($idTemp)) {
                    $query = $query->whereNotIn("vehicles_id", $idTemp);
                }
                break;
            default:
                break;
        }
        return $query->select($param['fields'])
                     ->wheres($param['search'])
                     ->count();
    }

    // 获取使用中的车辆id, 不验证权限
    public function getAllUseVehiclesId($param) {
        $query = $this->entity->select(['vehicles.vehicles_id']);
        $query = $query->whereIn('vehicles.vehicles_id', function($query) {
            $query->select(['vehicles_apply.vehicles_id'])
                ->from('vehicles_apply')
                ->whereIn('vehicles_apply.vehicles_apply_status', [2, 4, 6]);
        });
        return $query->get()->toArray();
    }

    //获取使用中 状态的车辆ID
    public function getUseVehiclesId($param) {
        $param['fields'] = ['vehicles.*', 'vehicles_sort.*'];
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        if (isset($param['role_id']) && is_array($param['role_id'])) {
            $roleId = implode(",", $param['role_id']);
        }
        //获取使用中的车辆
        $query = $this->entity->select([ 'vehicles.vehicles_id'], $param['fields']);
        $query = $query->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
        $query = $query->leftJoin('vehicles_apply', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id');
        $query = $query->whereIn('vehicles_apply.vehicles_apply_status', [2, 4, 6]);
        $query = $query->where(function($query)use($roleId, $userId, $deptId){
            $query = $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_user) or vehicles_sort.member_user = 'all'",[$userId])
                            ->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_dept) or vehicles_sort.member_dept = 'all'",[$deptId]);
            $roleId = explode(',', $roleId);
            if(!empty($roleId)){
                foreach($roleId as $v){
                    $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_role) or vehicles_sort.member_role = 'all'",[$v]);
                }
            }
        });
        $data = $query->groupBy('vehicles.vehicles_id')
                            ->get()
                            ->toArray();
        return    $query->groupBy('vehicles.vehicles_id')
                            ->get()
                            ->toArray();
    }
    public function getUseVehiclesIdNoAuth($param) {
        $query = $this->entity->select([ 'vehicles.vehicles_id'], $param['fields']);
        $query = $query->leftJoin('vehicles_apply', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id');
        $query = $query->whereIn('vehicles_apply.vehicles_apply_status', [2, 4, 6]);
        return $query->groupBy('vehicles.vehicles_id')
                        ->get()
                        ->toArray();
    }

    //获取维护中 状态的车辆ID
    public function getMaintenanceVehiclesId() {
        $time = date("Y-m-d H:i:s", time());
        return $this->entity->select([ 'vehicles.vehicles_id'])
                            ->leftJoin('vehicles_maintenance', 'vehicles_maintenance.vehicles_id', '=', 'vehicles.vehicles_id')
                            ->where('vehicles_maintenance_begin_time', '<=', $time)
                            ->where('vehicles_maintenance_end_time', '>=', $time)
                            ->whereNull('vehicles_maintenance.deleted_at')
                            ->get()
                            ->toArray();
    }

    /**
     * 获取保障中的车辆id
     */
    public function getInsuranceVehiclesId()
    {
        $time = date("Y-m-d 00:00:00", time());
        $ids = $this->entity->select([ 'vehicles.vehicles_id'])
                            ->leftJoin('vehicles_insurance', 'vehicles_insurance.vehicles_id', '=', 'vehicles.vehicles_id')
                            ->where('vehicles_insurance_begin_time', '<=', $time)
                            ->where('vehicles_insurance_end_time', '>=', $time)
                            ->whereNull('vehicles_insurance.deleted_at')
                            ->get()
                            ->toArray();

        return $ids;
    }

    /**
     * 获取年检中的车辆id
     */
    public function getAnnualInspectionVehiclesId()
    {
        $time = date("Y-m-d 00:00:00", time());
        $ids = $this->entity->select([ 'vehicles.vehicles_id'])
            ->leftJoin('vehicles_annual_inspection', 'vehicles_annual_inspection.vehicles_id', '=', 'vehicles.vehicles_id')
            ->where('vehicles_annual_inspection_begin_time', '<=', $time)
            ->where('vehicles_annual_inspection_end_time', '>=', $time)
            ->whereNull('vehicles_annual_inspection.deleted_at')
            ->get()
            ->toArray();

        return $ids;
    }

    //获取 待维护 状态的车辆ID
    public function getStandbyVehiclesId() {
        $time = date("Y-m-d H:i:s", time());
        return $this->entity->select([ 'vehicles.vehicles_id'])
                            ->leftJoin('vehicles_maintenance', 'vehicles_maintenance.vehicles_id', '=', 'vehicles.vehicles_id')
                            ->where('vehicles_maintenance_begin_time', '>', $time)
                            ->whereNull('vehicles_maintenance.deleted_at')
                            ->get()
                            ->toArray();
    }

    /**
     * 获取待投保车辆
     */
    public function getToInsureVehiclesId()
    {
        $time = date('Y-m-d 00:00:00', time());

        $ids = $this->entity->select([ 'vehicles.vehicles_id'])
                              ->leftJoin('vehicles_insurance', 'vehicles_insurance.vehicles_id', '=', 'vehicles.vehicles_id')
                              ->where('vehicles_insurance_begin_time', '>', $time)
                              ->whereNull('vehicles_insurance.deleted_at')
                              ->get()
                              ->toArray();

        return $ids;
    }

    /**
     * 获取待年检车辆
     */
    public function getToInspectAnnuallyVehiclesId()
    {
        $time = date('Y-m-d 00:00:00', time());

        $ids = $this->entity->select([ 'vehicles.vehicles_id'])
            ->leftJoin('vehicles_annual_inspection', 'vehicles_annual_inspection.vehicles_id', '=', 'vehicles.vehicles_id')
            ->whereNull('vehicles_annual_inspection.deleted_at')
            ->where('vehicles_annual_inspection_begin_time', '>', $time)
            ->get()
            ->toArray();

        return $ids;
    }

    // 获取所有车牌号列表
    public function getAllVehiclesCodeList() {
        return $this->entity->select(['vehicles.vehicles_code'])
                            ->get()
                            ->toArray();
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
        if(isset($param['vehicles_name']) && !empty($param['vehicles_name']) && isset($param['vehicles_code']) && !empty($param['vehicles_code'])) {
            $query = $this->entity->select(['vehicles_id'])
                                ->where('vehicles_name', $param['vehicles_name'])
                                ->where('vehicles_code', $param['vehicles_code']);
            $query = $query->first();
            if($query) {
                return $query->toArray()['vehicles_id'];
            }
        }
        if(isset($param['vehicles_code']) && !empty($param['vehicles_code'])) {
            $query =$this->entity->select(['vehicles_id'])->where('vehicles_code', $param['vehicles_code']);
            $query = $query->first();
            if($query) {
                return $query->toArray()['vehicles_id'];
            }
        }
        return '';
    }

    /**
     * 判断车牌号是否已存在
     *
     * @param string $vehicleCode 车牌号
     * @param string|int $vehicleId 编辑车辆id
     *
     * @return bool
     */
    public function isExistingVehicleCode($vehicleCode, $vehicleId = ''): bool
    {
        $queryBuilder = $this->entity->where('vehicles_code', $vehicleCode);

        if ($vehicleId) {
            $queryBuilder->where('vehicles_id', '!=', $vehicleId);
        }

        $vehicle = $queryBuilder->first();

        return $vehicle ? true : false;
    }

    /**
     * 判断车辆是否存在
     *
     * @param string|int $vehiclesId
     */
    public function isExistingVehicle($vehiclesId) : bool
    {
        $queryBuilder = $this->entity->where('vehicles_id', $vehiclesId);
        $vehicle = $queryBuilder->first();

        return $vehicle ? true : false;
    }
}
