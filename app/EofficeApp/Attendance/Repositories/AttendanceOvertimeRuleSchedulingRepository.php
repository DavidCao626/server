<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOvertimeRuleSchedulingEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;

class AttendanceOvertimeRuleSchedulingRepository extends BaseRepository
{
    use AttendanceTrait;

    public function __construct(AttendanceOvertimeRuleSchedulingEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getRuleByScheduling($schedulingId)
    {
        return $this->entity->where('scheduling_id', $schedulingId)->with('rule')->first()->rule ?? null;
    }

    public function getRuleByIds($schedulingIds)
    {
        return $this->entity->select('*')->whereIn('scheduling_id', $schedulingIds)->get();
    }
}
