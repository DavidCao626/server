<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePcSignEntity extends BaseEntity
{
    /** @var string $table 定义实体表 */
    public $table = 'attend_pc_sign';

    protected $fillable = ['all_member', 'dept_id', 'role_id', 'user_id'];
}




