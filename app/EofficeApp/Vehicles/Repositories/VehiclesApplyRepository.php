<?php

namespace App\EofficeApp\Vehicles\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Vehicles\Entities\VehiclesApplyEntity;

/**
 * 用车申请资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class VehiclesApplyRepository extends BaseRepository {

    public function __construct(VehiclesApplyEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取车辆申请列表用于日历
     *
     * @param type $param
     *
     * @return type $result
     *
     * @author miaochenchen
     *
     * @since 2017-08-03
     *
     */
    public function getVehiclesApplyForCalendar($param, $flag = false) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        if(isset($param['start']) && isset($param['end'])) {
            $param['search']['vehicles_apply_begin_time'] = [$param['end'], '<'];
            $param['search']['vehicles_apply_end_time']   = [$param['start'], '>'];
        }
        if(isset($param['search']['vehicles_id'])) {
            $param['search']['vehicles_apply.vehicles_id'] = $param['search']['vehicles_id'];
            unset($param['search']['vehicles_id']);
        }
        $query = $this->entity->select(['vehicles_apply.*', 'vehicles.vehicles_code', 'vehicles.vehicles_name', 'user.user_name as vehicles_apply_apply_user_name'])
                              ->leftJoin('vehicles', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id')
                              ->leftJoin('user', 'user.user_id', '=', 'vehicles_apply.vehicles_apply_apply_user')
                              ->wheres($param['search']);
        if ($flag) {
            $query->whereNotIn("vehicles_apply.vehicles_apply_status", [3, 5]);
        };
        $result = $query->get()->toArray();
        return $result;
    }

    /**
     * 获取用户申请车辆的具体详情
     *
     * @param type $id
     *
     * @return type
     *
     * @author 喻威
     *
     * @since 2015-10-22
     *
     */
    public function infoVehiclesApply($id) {
        $result = $this->entity->select(['vehicles_apply.*', 'vehicles.*', 'user.user_name as vehicles_apply_apply_user_name'])
                               ->leftJoin('vehicles', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id')
                               ->leftJoin('user', 'user.user_id', '=', 'vehicles_apply.vehicles_apply_apply_user')
                               ->leftjoin('vehicles_sort', 'vehicles.vehicles_sort_id', '=', "vehicles_sort.vehicles_sort_id")
                               ->where('vehicles_apply_id', $id)->first();
        if(!empty($result)) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * 设置待审核申请的审批查看时间
     *
     * @param string $vehicles_apply_id
     * @return boolean
     */
    public function setVehiclesApply($vehicles_apply_id) {
        return $this->entity->where("vehicles_apply_id", $vehicles_apply_id)
                            ->update(['vehicles_apply_time' => date("Y-m_d H:i:s", time())]);
    }

    public function infoVehiclesApplyByVehiclesID($id, $flag = false) {
        $query = $this->entity->select(['vehicles_apply.*', 'vehicles.vehicles_code', 'vehicles.vehicles_name', 'user.user_name as vehicles_apply_apply_user_name'])
                              ->leftJoin('vehicles', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id')
                              ->leftJoin('user', 'user.user_id', '=', 'vehicles_apply.vehicles_apply_apply_user')
                              ->where("vehicles_apply.vehicles_id", $id);
        if ($flag) {
            $query->whereNotIn("vehicles_apply.vehicles_apply_status", [3, 5]);
        };
        $result = $query->get()->toArray();
        return $result;
    }

    /**
     * 获取当前车辆的用车申请记录
     *
     * @param array $param
     * @return type
     */
    public function infoVehiclesApplyList($param) {
        $default = [
            'fields' => ['vehicles_apply.*', 'user.user_name as vehicles_apply_apply_user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_apply_begin_time' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->select($param['fields'])
                            ->leftJoin('user', 'user.user_id', '=', 'vehicles_apply.vehicles_apply_apply_user')
                            ->where('vehicles_id', $param['vehicles_id'])
                            ->whereIn('vehicles_apply_status', [2, 5])
                            ->wheres($param['search'])
                            ->orders($param['order_by'])
                            ->parsePage($param['page'], $param['limit'])
                            ->get()
                            ->toArray();
    }

    public function infoVehiclesApplyTotal($param) {
        $default = [
            'search' => []
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->where('vehicles_id', $param['vehicles_id'])
                            ->whereIn('vehicles_apply_status', [2, 5])
                            ->wheres($param['search'])
                            ->count();
    }

    /**
     * 用车审批数目
     *
     * @param array $where
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function vehiclesApprovalTotal($param) {
        $param['returnType'] = 'count';
        return $this->vehiclesApprovalList($param);
    }

    /**
     * 用车审批列表
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function vehiclesApprovalList($param) {
        $default = [
            'fields' => ['vehicles_apply.*', 'vehicles.vehicles_name', 'vehicles.vehicles_code', 'vehicles.vehicles_space', 'user.user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_apply.vehicles_apply_id' => 'desc'],
            'returnType' => 'array'
        ];
        if(isset($param['vehicles_name']) && !empty($param['vehicles_name'])) {
            $param['search']['vehicles.vehicles_name'] = [$param['vehicles_name'], 'like'];
        }
        if(isset($param['vehicles_code']) && !empty($param['vehicles_code'])) {
            $param['search']['vehicles.vehicles_code'] = [$param['vehicles_code'], 'like'];
        }
        if(isset($param['user_id']) && $param['user_id'] && !isset($param['type'])) {
            //排除已假删除的(已拒绝的申请+已验收的申请)
            $param['search']['false_delete'] = ['2','!='];
            $param = array_merge($default, array_filter($param));
            $query = $this->entity->select($param['fields'])
                                  ->leftJoin('vehicles', "vehicles_apply.vehicles_id", '=', 'vehicles.vehicles_id')
                                  ->leftJoin('user', "vehicles_apply.vehicles_apply_apply_user", '=', 'user.user_id')
                                  ->where('vehicles_apply_approval_user', $param['user_id']);
        }else{
            $param = array_merge($default, array_filter($param));
            if(isset($param['search']['dept_id']) && $param['search']['dept_id']) {
                $deptId = $param['search']['dept_id'][0];
                unset($param['search']['dept_id']);
            }else{
                $deptId = '';
            }
            $query = $this->entity->select($param['fields'])
                                  ->leftJoin('vehicles', "vehicles_apply.vehicles_id", '=', 'vehicles.vehicles_id')
                                  ->leftJoin('user', "vehicles_apply.vehicles_apply_apply_user", '=', 'user.user_id')
                                  ->with(['vehiclesApplyBelongsToUserSystemInfo.userSystemInfoBelongsToDepartment' => function($query)
                                  {
                                      $query->select('dept_id', 'dept_name');
                                  }])
                                  ->whereHas('vehiclesApplyBelongsToUserSystemInfo.userSystemInfoBelongsToDepartment', function($query) use ($deptId)
                                  {
                                      if(isset($deptId) && $deptId) {
                                          $query->where('dept_id', $deptId);
                                      }
                                  })
                                  ->withTrashed();
        }

        // 返回值类型判断
        if($param["returnType"] == "array") {
            return $query->wheres($param['search'])
                         ->orders($param['order_by'])
                         ->parsePage($param['page'], $param['limit'])
                         ->get()
                         ->toArray();
        }elseif($param["returnType"] == "count") {
            return $query->wheres($param['search'])
                         ->orders($param['order_by'])
                         ->count();
        }
    }

    public function getAllVehiclesByUser(array $param = []) {
        $default = [
            'fields' => ['vehicles_apply.*', 'vehicles.*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_apply.vehicles_apply_id' => 'desc'],
        ];
        //排除已假删除的(已拒绝的申请+已验收的申请)
        $param['search']['false_delete'] = ['1','!='];
        $param = array_merge($default, array_filter($param));
        if(isset($param['vehicles_name']) && !empty($param['vehicles_name'])) {
            $param['search']['vehicles.vehicles_name'] = [$param['vehicles_name'], 'like'];
        }
        if(isset($param['vehicles_code']) && !empty($param['vehicles_code'])) {
            $param['search']['vehicles.vehicles_code'] = [$param['vehicles_code'], 'like'];
        }
        $query = $this->entity->select($param['fields'])
                              ->leftJoin('vehicles', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id')
                              ->where("vehicles_apply_apply_user", $param['vehicles_apply_apply_user']);
        return $query->wheres($param['search'])
                     ->orders($param['order_by'])
                     ->parsePage($param['page'], $param['limit'])
                     ->get()
                     ->toArray();
    }

    public function getAllVehiclesByUserTotal(array $param = []) {
        $default = [
            'search' => [],
        ];
        $param['search']['false_delete'] = ['1','!='];
        $param = array_merge($default, array_filter($param));
        if(isset($param['vehicles_name']) && !empty($param['vehicles_name'])) {
            $param['search']['vehicles.vehicles_name'] = [$param['vehicles_name'], 'like'];
        }
        if(isset($param['vehicles_code']) && !empty($param['vehicles_code'])) {
            $param['search']['vehicles.vehicles_code'] = [$param['vehicles_code'], 'like'];
        }
        return $this->entity->leftJoin('vehicles', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id')
                            ->wheres($param['search'])
                            ->where("vehicles_apply_apply_user", $param['vehicles_apply_apply_user'])
                            ->count();
    }

    //获取申请表中车辆的个数
    public function getVehiclesIds() {
        return $this->entity->groupBy('vehicles_id')
                            ->get()
                            ->toArray();
    }

    public function getApplyStartDate($vehicles_id) {
        return $this->entity->where('vehicles_id', $vehicles_id)
                            ->orderBy('vehicles_apply_begin_time', 'asc')
                            ->get()
                            ->toArray();
    }

    public function getRecodeByWhere($where) {
        return $this->entity->where("vehicles_id", $where["vehicles_id"])
                            ->where("vehicles_apply_id", "<>", $where["vehicles_apply_id"])
                            ->whereRaw("((vehicles_apply_begin_time >= '" . $where['vehicles_apply_begin_time'] . "' and  vehicles_apply_begin_time <= '" . $where['vehicles_apply_end_time'] . "') or  (vehicles_apply_end_time >= '" . $where['vehicles_apply_begin_time'] . "' and  vehicles_apply_end_time <= '" . $where['vehicles_apply_end_time'] . "') ) ")
                            ->orderBy('vehicles_apply.vehicles_id', 'desc')
                            ->get()->toArray();
    }

    //获取空闲的车辆列表
    public function freeStatusVehicles() {
        return $this->entity->select(['vehicles_id'])
                            ->whereIn("vehicles_apply_status", [2, 4])
                            ->groupBy('vehicles_id')
                            ->get()
                            ->toArray();
    }

    public function UsedStatusVehicles() {
        return $this->entity->select(['vehicles_id'])
                            ->whereIn("vehicles_apply_status", [2, 4])
                            ->groupBy('vehicles_id')
                            ->get()
                            ->toArray();
    }

    public function getVehiclesApplyDelete($vehiclesId) {
        return $this->entity->where("vehicles_id", $vehiclesId)
                            ->count();
    }

    /**
     * @获取冲突车辆列表
     * @param type $search
     * @return 冲突车辆列表 | array
     */
    public function getConflictVehicles($search) {
        return $this->entity->select(['vehicles_apply_id','conflict'])
                            ->wheres($search)
                            ->get();
    }

    /**
     * @获取车辆申请详情
     * @param type $vApplyId
     * @return 车辆申请详情 | array
     */
    public function showVehiclesApply($vApplyId) {
        return $this->entity->where('vehicles_apply_id', $vApplyId)
                            ->first();
    }

    /**
     * @更新车辆申请信息
     * @param type $data
     * @param type $vApplyId
     * @return boolean
     */
    public function editVehiclesApply($data, $vApplyId) {
        return $this->entity->where('vehicles_apply_id', $vApplyId)
                            ->update($data);
    }

    /**
     * @删除车辆申请信息
     * @param type $vApplyId
     * @return boolean
     */
    public function deleteVehiclesApply($vApplyId) {
        return $this->entity->destroy($vApplyId);
    }

    /**
     * 新建用车申请前判断申请时间段是否与现有用车有冲突
     * @param array
     * @return boolean
     */
    public function getNewVehiclesDateWhetherConflict($param, $userId) {
        if(empty($param['startDate']) || empty($param['endDate']) || empty($param['vehiclesId'])) {
            return '0';
        }
        $search = array();
        $search['vehicles_apply_begin_time'] = [$param['endDate'], '<'];
        $search['vehicles_apply_end_time']   = [$param['startDate'], '>'];
        // $search['vehicles_id']               = [$param['vehiclesId'], '='];
        // $search['vehicles_apply_status']     = [[1,2,4,6], 'in'];
        $query = $this->entity->where(function($query) use($search) {
            $query->wheres($search)->WhereIn('vehicles_apply_status', [1,2,4,6]);
        });
        $result = $query->whereNotIn('vehicles_apply_status', [3,5])->where('vehicles_id', $param['vehiclesId'])->get()->toArray();
        if($result) {
            return '1';
        }else{
            return '0';
        }
    }
    public function getVehiclesConflict($param, $userId) {
        if(empty($param['startDate']) || empty($param['endDate']) || empty($param['vehiclesId'])) {
            return '0';
        }
        $search = array();
        $search['vehicles_apply_begin_time'] = [$param['endDate'], '<='];
        $search['vehicles_apply_end_time']   = [$param['startDate'], '>='];
        // $search['vehicles_id']               = [$param['vehiclesId'], '='];
        // $search['vehicles_apply_status']     = [[1,2,4,6], 'in'];
        // $search['vehicles_apply_id'] = [$param['applyId'], '!='];
        $query = $this->entity->where(function($query) use($search) {
            $query->wheres($search)->WhereIn('vehicles_apply_status', [1,2,4,6]);
        });
        $result = $query->whereNotIn('vehicles_apply_status', [3,5])->where('vehicles_apply_id', '!=', $param['applyId'])->where('vehicles_id', $param['vehiclesId'])->get()->toArray();
        if($result) {
            return '1';
        }else{
            return '0';
        }
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
        $default = [
            'fields' => ['vehicles_apply.*', 'vehicles.vehicles_name', 'vehicles.vehicles_code', 'vehicles.vehicles_space', 'user.user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_apply.vehicles_apply_begin_time' => 'asc', 'vehicles_apply.vehicles_apply_end_time' => 'asc'],
            'returntype' => 'array'
        ];
        $param = array_merge($default, array_filter($param));
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        if(isset($param['start']) && isset($param['end'])) {
            $param['search']['vehicles_apply_begin_time'] = [$param['end'], '<'];
            $param['search']['vehicles_apply_end_time']   = [$param['start'], '>'];
        }
        if(isset($param['vehicles_name']) && !empty($param['vehicles_name'])) {
            $param['search']['vehicles.vehicles_name'] = [$param['vehicles_name']];
        }
        if(isset($param['vehicles_code']) && !empty($param['vehicles_code'])) {
            $param['search']['vehicles.vehicles_code'] = [$param['vehicles_code']];
        }
        $param['search']['vehicles_apply_status'] = [[1,2,4,6], 'in'];
        $query = $this->entity->select($param['fields'])
                            ->leftJoin('vehicles', "vehicles_apply.vehicles_id", '=', 'vehicles.vehicles_id')
                            ->leftJoin('user', "vehicles_apply.vehicles_apply_apply_user", '=', 'user.user_id')
                            ->leftjoin("vehicles_sort", "vehicles_sort.vehicles_sort_id", "=", "vehicles.vehicles_sort_id");
                            $roleId = explode(',', $roleId);
                                if(!empty($roleId)){
                                    foreach($roleId as $v){
                                        $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_role) or vehicles_sort.member_role = 'all'",[$v]);
                                    }
                                }
                            $query = $query->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_user) or vehicles_sort.member_user = 'all'",[$userId])
                            ->orWhereRaw("FIND_IN_SET(?, vehicles_sort.member_dept) or vehicles_sort.member_dept = 'all'",[$deptId])
                            ->wheres($param['search'])
                            ->orders($param['order_by'])
                            ->parsePage($param['page'], $param['limit']);
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->get()->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    public function getAllVehiclesApplyListCount($param) {
        $param['returntype'] = 'count';
        $param['page'] = 0;
        return $this->getAllVehiclesApplyList($param);
    }

    /**
     * 判断车辆是否处于申请中(删除用, 未验收即为申请占用中)
     *
     * @param array $vehiclesIds
     *
     * @return bool
     */
    public function isInApplication($vehiclesIds): bool
    {
        $queryBuilder = $this->entity->whereIn("vehicles_id", $vehiclesIds);
        $queryBuilder->whereNotIn("vehicles_apply.vehicles_apply_status", VehiclesApplyEntity::APPLY_STATUS_FINISHED);
        $count = $queryBuilder->count();

        return $count === 0 ? false : true;
    }

    public function getVehiclesApplyDetailForCustom($applyId) {
        $query = $this->entity->select(['vehicles_apply.vehicles_apply_id', 'vehicles.vehicles_name'])
        ->leftJoin('vehicles', 'vehicles_apply.vehicles_id', '=', 'vehicles.vehicles_id');
        if ($applyId) {
            return $query->where('vehicles_apply.vehicles_apply_id', $applyId)->get()->toArray();
        } else {
            return $query->get()->toArray();
        }
        
    }
}
