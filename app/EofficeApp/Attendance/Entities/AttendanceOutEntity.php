<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceOutEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_out';
    public $primaryKey = 'out_id';
    protected $fillable = ['user_id', 'out_start_time', 'out_end_time','out_days','out_hours','out_status', 'out_reason', 'out_extra'];
}




