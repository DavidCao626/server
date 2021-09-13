<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceBackLeaveEntity extends BaseEntity
{

    public $table = 'attend_back_leave';
    public $primaryKey = 'back_leave_id';
    protected $fillable = [
        'user_id',
        'leave_id',
        'vacation_id',
        'back_leave_start_time',
        'back_leave_end_time',
        'back_leave_days',
        'back_leave_hours',
        'back_leave_reason',
        'back_leave_extra',
    ];
}




