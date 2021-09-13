<?php

namespace App\EofficeApp\Vehicles\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Vehicles\Entities\VehiclesMaintenanceEntity;

/**
 * 车辆登记资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class VehiclesMaintenanceRepository extends BaseRepository {

    public function __construct(VehiclesMaintenanceEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取维护信息用于日历
     *
     * @param type $param
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since 2017-08-03
     */
    public function getVehiclesMaintenanceForCalendar($param) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        if(isset($param['start']) && isset($param['end'])) {
            $param['search']['vehicles_maintenance_begin_time'] = [$param['end'], '<'];
            $param['search']['vehicles_maintenance_end_time']   = [$param['start'], '>'];
        }
        if(isset($param['search']['vehicles_id'])) {
            $param['search']['vehicles_maintenance.vehicles_id'] = $param['search']['vehicles_id'];
            unset($param['search']['vehicles_id']);
        }
        $infoResult = $this->entity->select(['vehicles_maintenance.*', 'vehicles.vehicles_name', 'vehicles.vehicles_code','system_combobox_field.field_name', 'system_combobox_field.combobox_id'])
                                   ->leftJoin('vehicles', 'vehicles_maintenance.vehicles_id', '=', 'vehicles.vehicles_id')
                                   ->leftJoin('system_combobox_field', 'system_combobox_field.field_value', '=', 'vehicles_maintenance.vehicles_maintenance_type')
                                   ->where("system_combobox_field.combobox_id",'=',"20")
                                   ->wheres($param['search'])
                                   ->get();
        if(!empty($infoResult)) {
            $infoResult = $infoResult->toArray();
            foreach ($infoResult as $key => $item) {
                $comboboxTableName = get_combobox_table_name($infoResult[$key]['combobox_id']);
                $infoResult[$key]['field_name'] = mulit_trans_dynamic($comboboxTableName . ".field_name." .$infoResult[$key]['field_name']);
            }
        }
        return $infoResult;
    }

    /**
     * 获取维护信息
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function infoVehiclesMaintenance($id) {
        $infoResult = $this->entity->select(['vehicles_maintenance.*', 'vehicles.vehicles_name', 'vehicles.vehicles_code','system_combobox_field.field_name', 'vehicles.vehicles_sort_id', 'vehicles_sort.vehicles_sort_name', 'system_combobox_field.field_id', 'system_combobox_field.combobox_id'])
                                   ->leftJoin('vehicles', 'vehicles_maintenance.vehicles_id', '=', 'vehicles.vehicles_id')
                                   ->leftJoin('system_combobox_field', 'system_combobox_field.field_value', '=', 'vehicles_maintenance.vehicles_maintenance_type')
                                   ->leftJoin('vehicles_sort', 'vehicles.vehicles_sort_id', 'vehicles_sort.vehicles_sort_id')
                                   ->where("system_combobox_field.combobox_id",'=',"20")
                                   ->where('vehicles_maintenance_id', $id)
                                   ->first();
        if(!empty($infoResult)) {
            $infoResult = $infoResult->toArray();
        }
        return $infoResult;
    }

    /**
     * 获取所有的维护记录
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function getAllMaintenance($type) {
        $result = $this->entity->get()->toArray();
        return $result;
    }

    public function infoVehiclesMaintenanceByVehiclesID($id) {
        $infoResult = $this->entity->select(['vehicles_maintenance.*', 'vehicles.vehicles_code', 'vehicles.vehicles_name','field_name'])
                                   ->leftJoin('vehicles', 'vehicles_maintenance.vehicles_id', '=', 'vehicles.vehicles_id')
                                   ->leftJoin('system_combobox_field', 'vehicles_maintenance.vehicles_maintenance_type', '=', 'system_combobox_field.field_value')
                                   ->where('system_combobox_field.combobox_id','=' ,'20')
                                   ->where('vehicles_maintenance.vehicles_id', $id)
                                   ->get()
                                   ->toArray();
        return $infoResult;
    }

    /**
     * 获取当前车辆的维护记录
     *
     * @param type $param
     * @return type
     */
    public function infoVehiclesMaintenanceList($param) {
        $default = [
            'fields' => ['vehicles_maintenance.*', 'system_combobox_field.*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_maintenance_begin_time' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        array_push($param['fields'],"field_name");
        return $this->entity->select($param['fields'])
                            ->leftJoin('system_combobox_field', 'vehicles_maintenance.vehicles_maintenance_type', '=', 'system_combobox_field.field_value')
                            ->where('system_combobox_field.combobox_id','=' ,'20')
                            ->where('vehicles_id', $param['vehicles_id'])
                            ->wheres($param['search'])
                            ->orders($param['order_by'])
                            ->parsePage($param['page'], $param['limit'])
                            ->get()
                            ->toArray();
    }

    public function infoVehiclesMaintenanceTotal($param) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->where('vehicles_id', $param['vehicles_id'])
                            ->wheres($param['search'])
                            ->count();
    }

    public function getVehiclesMaintenanceDelete($vehiclesId) {
        return $this->entity->where("vehicles_id", $vehiclesId)
                            ->count();
    }

    /**
     * 获取当前申请时间内是否车辆在维护状态
     *
     * @param type $vehiclesId
     * @return type
     */
    public function getVehiclesLatestMaintenanceTime($vehiclesId, $startTime, $endTime) {
        $where = [
            'vehicles_id' => [$vehiclesId],
            'vehicles_maintenance_begin_time' => [$endTime, '<'],
            'vehicles_maintenance_end_time'   => [$startTime, '>']
        ];
        $vehiclesMaintenanceFlag = $this->entity->select(['vehicles_maintenance_id'])
                                                      ->where($where)
                                                      ->first();
        if($vehiclesMaintenanceFlag) {
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 查询需要发送用户维护消息的结果
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since 2017-01-16
     */
    public function vehiclesMaintenanceRemind($search) {
        $param['fields'] = [
            'vehicles.vehicles_name',
            'vehicles.vehicles_code',
            'vehicles_maintenance.*',
            'system_combobox_field.field_name',
            'system_combobox_field.field_id'
        ];
        $result = $this->entity
                    ->select($param['fields'])
                    ->leftJoin('vehicles', 'vehicles_maintenance.vehicles_id', '=', 'vehicles.vehicles_id')
                    ->leftJoin('system_combobox_field', 'system_combobox_field.field_value', '=', 'vehicles_maintenance.vehicles_maintenance_type')
                    ->where("system_combobox_field.combobox_id",'=',"20")
                    ->wheres($search)
                    ->get();
        if($result) {
            return $result->toArray();
        }else{
            return '';
        }
    }

    /**
     * 判断车辆是否处于维护中
     *
     * @param array $vehiclesIds
     *
     * @return bool
     */
    public function isInMaintenance($vehiclesIds): bool
    {
        $queryBuilder = $this->entity->whereIn("vehicles_id", $vehiclesIds);

        $date = date('Y-m-d H:i:s', time());
        $queryBuilder->where('vehicles_maintenance_begin_time', '<=', $date);
        $queryBuilder->where('vehicles_maintenance_end_time', '>', $date);
        $count = $queryBuilder->count();

        return $count === 0 ? false : true;
    }

    /**
     * 删除车辆维护
     */
    public function deleteMaintenance($vehiclesId)
    {
        return $this->entity->destroy($vehiclesId);
    }
}
