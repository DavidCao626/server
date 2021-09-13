<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceRecordsEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_records';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'record_id';
    
    protected $fillable = ['user_id', 'sign_date', 'sign_in_time','sign_in_normal','sign_out_time','sign_out_normal','lag_time', 'leave_early_time', 'must_attend_time', 'in_ip', 'in_long', 'in_lat', 'in_address', 'in_platform','out_ip','out_long','out_lat','out_address','out_platform', 'is_lag', 'is_leave_early','calibration_status', 'calibration_reason', 'shift_id','sign_times','is_offset','offset_lag_history', 'offset_early_history', 'attend_type','remark','is_repair','repair_time', 'calibration_sign','original_sign_in_time', 'original_sign_out_time'];
}




