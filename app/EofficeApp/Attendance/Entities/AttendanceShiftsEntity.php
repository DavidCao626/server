<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceShiftsEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_shifts';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'shift_id';
    
    protected $fillable = [
        'shift_name', 
        'shift_type', 
        'attend_time',
        'middle_rest',
        'shift_status',
        'is_default',
        'creator',
        'modify_user',
        'mark_color',
        'auto_sign',
        'sign_in_limit',
        'sign_in_begin_time',
        'sign_in_end_time',
        'sign_out_limit',
        'sign_out_end_time',
        'sign_out_now_limit',
        'sign_out_now_limit_time',
        'allow_late_to_late',
        'late_to_late_time',
        'allow_early_to_early',
        'early_to_early_time',
        'allow_late',
        'allow_late_time',
        'allow_leave_early',
        'leave_early_time',
        'seriously_lag',
        'seriously_lag_time',
        'absenteeism_lag',
        'absenteeism_lag_time'
    ];
}




