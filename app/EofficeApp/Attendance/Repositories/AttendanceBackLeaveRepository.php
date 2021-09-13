<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceBackLeaveEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;


class AttendanceBackLeaveRepository extends BaseRepository
{
    use AttendanceTrait;

    public function __construct(AttendanceBackLeaveEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['back_leave_start_time' => 'desc'];
    }
}