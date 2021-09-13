<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceRepairLogEntity;

class AttendanceRepairLogRepository extends BaseRepository
{
    private $defaultParams;

    public function __construct(AttendanceRepairLogEntity $entity)
    {
        parent::__construct($entity);
        $this->defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['created_at' => 'desc'],
            'search' => []
        ];
    }

    public function getList($params, $relation = false)
    {
        $params = array_merge($this->defaultParams, $params);
        $query = $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit']);
        if ($relation) {
            $query->with('hasOneRecord');
        }
        return $query->get();
    }
}