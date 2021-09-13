<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceCalibrationLogEntity;

class AttendanceCalibrationLogRepository extends BaseRepository
{
    private $defaultParams;

    public function __construct(AttendanceCalibrationLogEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['approve_time' => 'desc'];
        $this->defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => $this->orderBy,
            'search' => []
        ];
    }

    public function getList($params)
    {
        $params = array_merge($this->defaultParams, $params);
        $query = $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit']);
        return $query->get();
    }
}
