<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceUserDaysHoursEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_user_days_hours';

    protected $fillable = ['year', 'month','user_id', 'days','hours'];
}




