<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceOvertimeStatEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_overtime_stat';

    protected $fillable = ['user_id', 'date', 'year', 'month', 'day', 'overtime_days', 'overtime_hours','to','ratio','vacation'];
    public $timestamps = false;
}




