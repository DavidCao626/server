<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceRepairEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_repair';
    public $primaryKey = 'repair_id';
    protected $fillable = [
        'user_id',
        'repair_date',
        'sign_times',
        'repair_reason',
        'repair_type',
        'repair_extra'
    ];
}




