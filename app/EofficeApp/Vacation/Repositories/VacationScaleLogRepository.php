<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2020/6/19
 * Time: 11:14
 */
namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Vacation\Entities\VacationScaleLogEntity;

class VacationScaleLogRepository extends VacationBaseRepository
{
    public function __construct(VacationScaleLogEntity $vacationScaleLogEntity)
    {

        parent::__construct($vacationScaleLogEntity);
    }

    public function getScaleLogList($params)
    {
        $defaultParams = [
            'fields' 	=> ['*'],
            'page' 		=> 0,
            'limit' 	=> config('eoffice.pagesize'),
            'order_by' 	=> ['created_at' => 'desc'],
            'search' 	=> []
        ];
        $params = array_merge($defaultParams, $params);
        return $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get();

    }

    public function getScaleLogCount($params)
    {
        $search = isset($params['search']) ? $params['search'] : [];

        return $this->entity->wheres($search)->count();
    }

}