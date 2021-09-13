<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceBackLeaveLogEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;

class AttendanceBackLeaveLogRepository extends BaseRepository
{
    use AttendanceTrait;

    private $defaultParams;

    public function __construct(AttendanceBackLeaveLogEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['log_id' => 'desc'];
        $this->defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => $this->orderBy,
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
            $query->with('hasOneBackLeaveRecord');
            $query->with(['hasOneLeaveRecord' => function ($query) {
                $query->withTrashed();
            }]);
        }
        return $query->get();
    }
}