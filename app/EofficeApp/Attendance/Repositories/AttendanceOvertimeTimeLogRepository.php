<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOvertimeTimeLogEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;

class AttendanceOvertimeTimeLogRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendanceOvertimeTimeLogEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['date' => 'desc'];
    }
}