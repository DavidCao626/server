<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceSchedulingEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_scheduling';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'scheduling_id';
    
    protected $fillable = ['scheduling_name',  'allow_sign_holiday','status', 'holiday_scheme_id', 'allow_sign_out_next_day'];
}




