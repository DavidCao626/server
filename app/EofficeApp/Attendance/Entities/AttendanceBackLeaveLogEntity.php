<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceBackLeaveLogEntity extends BaseEntity
{

    public $table = 'attend_back_leave_log';
    public $primaryKey = 'log_id';
    protected $fillable = [
        'user_id',
        'leave_id',
        'back_leave_id',
        'vacation_id',
        'back_leave_start_time',
        'back_leave_end_time',
        'back_leave_days',
        'back_leave_hours',
        'approver_id'
    ];

    public function hasOneLeaveRecord()
    {
        return $this->hasOne(AttendanceLeaveEntity::class, 'leave_id', 'leave_id');
    }

    public function hasOneBackLeaveRecord()
    {
        return $this->hasOne(AttendanceBackLeaveEntity::class, 'back_leave_id', 'back_leave_id');
    }
}




