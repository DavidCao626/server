<?php
namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceCalibrationLogEntity extends BaseEntity 
{
    public $table = 'attend_calibration_log';
    public $primaryKey = 'log_id';
    protected $fillable = ['sign_date', 'user_id', 'old_sign_in', 'old_sign_out', 'approver', 'calibration_status', 'approve_time'];
    public $timestamps = false;
}
