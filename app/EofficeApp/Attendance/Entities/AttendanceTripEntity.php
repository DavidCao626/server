<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceTripEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_trip';
    public $primaryKey = 'trip_id';
    protected $fillable = ['user_id', 'trip_start_date', 'trip_end_date', 'trip_days', 'trip_hours', 'trip_area', 'trip_status', 'trip_reason', 'trip_extra'];
}




