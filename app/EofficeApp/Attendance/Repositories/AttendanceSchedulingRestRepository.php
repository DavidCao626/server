<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSchedulingRestEntity;

/**
 * 排班资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceSchedulingRestRepository extends BaseRepository
{
    public function __construct(AttendanceSchedulingRestEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getSchedulingRestDate($schedulingId, $year)
    {
        return $this->entity->where('scheduling_id', $schedulingId)->where('year', $year)->get();
    }

    public function getMonthSchedulingRestBySchedulingId($schedulingId, $year, $month)
    {
        return $this->entity->select(['scheduling_date', 'rest_id'])->where('scheduling_id', $schedulingId)->where('year', $year)->where('month', $month)->orderBy('scheduling_date', 'asc')->get();
    }

    public function getSchedulingRestByDateScope($schedulingId, $startDate, $endDate)
    {
        return $this->entity->select(['scheduling_date', 'rest_id'])->where('scheduling_id', $schedulingId)->where('scheduling_date', '>=', $startDate)->where('scheduling_date', '<=', $endDate)->orderBy('scheduling_date', 'asc')->get();
    }

    public function getSchedulingRest($wheres)
    {
        return $this->entity->wheres($wheres)->get();
    }
}
