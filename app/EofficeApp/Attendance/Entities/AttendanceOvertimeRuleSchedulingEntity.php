<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceOvertimeRuleSchedulingEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_overtime_rule_scheduling';
    public $primaryKey = 'rule_id';
    protected $fillable = [
        'rule_id',
        'scheduling_id'
    ];

    public function rule()
    {
        return $this->belongsTo(AttendanceOvertimeRuleEntity::class,'rule_id','id');
    }
}
