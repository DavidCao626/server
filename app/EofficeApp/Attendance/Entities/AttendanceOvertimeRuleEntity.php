<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceOvertimeRuleEntity extends BaseEntity
{

    public $table = 'attend_overtime_rule';
    public $primaryKey = 'id';
    protected $fillable = [
        'rule_name',
        'method',
        //工作日
        'work_open',
        'work_after_time',
        'work_min_time',
        'work_min_unit',
        'work_limit_days',
        'work_to',
        'work_ratio',
        'work_vacation',
        //休息日
        'rest_open',
        'rest_convert',
        'rest_min_time',
        'rest_min_unit',
        'rest_limit_days',
        'rest_to',
        'rest_ratio',
        'rest_vacation',
        //节假日
        'holiday_open',
        'holiday_convert',
        'holiday_min_time',
        'holiday_min_unit',
        'holiday_limit_days',
        'holiday_to',
        'holiday_ratio',
        'holiday_vacation',
        'rest_diff',
        'rest_diff_time',
        'holiday_diff',
        'holiday_diff_time'
    ];

    public function scheduling()
    {
        return $this->hasMany(AttendanceOvertimeRuleSchedulingEntity::class, 'rule_id', 'id');
    }
}







