<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceRepairLogEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_repair_log';
    public $primaryKey = 'log_id';
    protected $fillable = [
        'repair_id',
        'user_id',
        'approver_id',
        'repair_date',
        'repair_type',
        'sign_times',
        'old_sign_in_time',
        'old_sign_out_time',
        'new_sign_in_time',
        'new_sign_out_time',
    ];

    public function hasOneRecord()
    {
        return $this->hasOne(AttendanceRepairEntity::class, 'repair_id', 'repair_id');
    }
}




