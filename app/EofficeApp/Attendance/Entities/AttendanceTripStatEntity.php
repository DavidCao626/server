<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceTripStatEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_trip_stat';

    protected $fillable = ['user_id', 'date', 'year', 'month', 'day', 'trip_days', 'trip_hours'];

    public $timestamps = false;
}




