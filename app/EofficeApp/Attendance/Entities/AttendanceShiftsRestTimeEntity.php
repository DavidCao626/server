<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceShiftsRestTimeEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_shifts_rest_time';
    
    protected $fillable = ['shift_id', 'rest_begin', 'rest_end'];
    
    public $timestamps = false;
}




