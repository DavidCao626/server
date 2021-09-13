<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceSchedulingModifyRecordEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_scheduling_modify_record';


    protected $fillable = ['user_id', 'year','month','day','modify_date','scheduling_id'];

    public $timestamps = false;
}




