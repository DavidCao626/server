<?php


namespace App\EofficeApp\Vehicles\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Vehicles\Entities\VehiclesInsuranceEntity;

class VehiclesInsuranceRepository extends BaseRepository
{
    public function __construct(VehiclesInsuranceEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取车辆保险信息
     *
     * @param int $id   车辆保险ID
     *
     * @return array
     */
    public function infoVehiclesInsurance($id): array
    {
        $item = $this->entity
                     ->select(
                         [
                             'vehicles_insurance.*',
                             'vehicles.vehicles_name',
                             'vehicles.vehicles_code',
                             'vehicles.vehicles_sort_id',
                             'vehicles_sort.vehicles_sort_name',
                         ]
                     )
                    ->leftJoin('vehicles', 'vehicles_insurance.vehicles_id', '=', 'vehicles.vehicles_id')
                    ->leftJoin('vehicles_sort', 'vehicles.vehicles_sort_id', 'vehicles_sort.vehicles_sort_id')
                    ->where('vehicles_insurance_id', $id)
                    ->first();

        if($item) {
            $item = $item->toArray();
        } else {
            $item = [];
        }

        return $item;
    }

    /**
     * 获取车辆保险信息
     *
     * @param array $param
     */
    public function infoVehiclesInsuranceTotal($param)
    {
        $default = [
            'search' => []
        ];
        $param = array_merge($default, array_filter($param));

        return $this->entity->where('vehicles_id', $param['vehicles_id'])
                            ->wheres($param['search'])
                            ->count();
    }

    /**
     * 获取当前车辆的用车保险记录
     *
     * @param array $param
     * @return array
     */
    public function infoVehiclesInsuranceList($param)
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['vehicles_insurance_begin_time' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->select($param['fields'])
                            ->where('vehicles_id', $param['vehicles_id'])
                            ->wheres($param['search'])
                            ->orders($param['order_by'])
                            ->parsePage($param['page'], $param['limit'])
                            ->get()
                            ->toArray();
    }

    /**
     * 判断车辆是否处于保险中
     *
     * @param array $vehiclesIds
     *
     * @return bool
     */
    public function isInInsurance($vehiclesIds): bool
    {
        $queryBuilder = $this->entity->whereIn("vehicles_id", $vehiclesIds);

        // 车辆保险只有年月日 无时分秒
        $date = date('Y-m-d 0:0:0', time());
        $queryBuilder->where('vehicles_insurance_begin_time', '<=', $date);
        $queryBuilder->where('vehicles_insurance_end_time', '>=', $date);
        $count = $queryBuilder->count();

        return $count === 0 ? false : true;
    }
}