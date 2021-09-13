<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceRepairEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;

class AttendanceRepairRepository extends BaseRepository
{
    use AttendanceTrait;

    public function __construct(AttendanceRepairEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['repair_date' => 'desc'];
    }
}