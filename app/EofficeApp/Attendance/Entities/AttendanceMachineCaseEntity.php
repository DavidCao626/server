<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceMachineCaseEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attendance_machine_case';

    protected $fillable = ['machine_brand', 'machine_model', 'record_table', 'sign_in', 'sign_out', 'user_field', 'sign_date', 'creatorId', 'choose_num'];
}
