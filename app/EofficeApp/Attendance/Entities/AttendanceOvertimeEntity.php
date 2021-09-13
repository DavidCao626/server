<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceOvertimeEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_overtime';
    public $primaryKey = 'overtime_id';
    protected $fillable = ['user_id', 'overtime_start_time', 'overtime_end_time', 'overtime_days', 'overtime_hours', 'overtime_to', 'overtime_status', 'overtime_reason', 'overtime_extra'];
}




