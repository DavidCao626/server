<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLeaveEntity extends BaseEntity
{

    use SoftDeletes;
    /** @var string $table 定义实体表 */
    public $table = 'attend_leave';
    public $primaryKey = 'leave_id';
    protected $fillable = ['user_id', 'vacation_id', 'leave_start_time', 'leave_end_time', 'has_money', 'leave_days', 'leave_hours', 'leave_reason', 'leave_extra', 'leave_status'];
}




