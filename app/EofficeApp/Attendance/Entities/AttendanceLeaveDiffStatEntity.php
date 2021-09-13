<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceLeaveDiffStatEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_leave_diff_stat';

    protected $fillable = ['user_id', 'vacation_id', 'date', 'year', 'month', 'day', 'leave_days', 'has_money', 'leave_hours'];
    public $timestamps = false;
}




