<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceLeaveStatEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_leave_stat';

    protected $fillable = ['user_id', 'date', 'year', 'month', 'day', 'leave_days', 'has_money', 'leave_hours'];
    public $timestamps = false;
}




