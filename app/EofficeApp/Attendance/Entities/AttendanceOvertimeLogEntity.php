<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceOvertimeLogEntity extends BaseEntity
{

    protected $guarded = ['log_id'];

    public $primaryKey = 'log_id';

    public $table = 'attend_overtime_log';

    public function hasManyTime()
    {
        return $this->hasMany(AttendanceOvertimeTimeLogEntity::class, 'log_id', 'log_id');
    }
}




