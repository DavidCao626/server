<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceShiftsSignTimeEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_shifts_sign_time';
    
    protected $fillable = ['shift_id', 'sign_in_time', 'sign_out_time'];
    
    public $timestamps = false;
}




