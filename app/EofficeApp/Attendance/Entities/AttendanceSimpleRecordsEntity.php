<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceSimpleRecordsEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_simple_records';
    public $primaryKey = 'record_id';
    protected $fillable = ['user_id', 'sign_date', 'sign_time','year','month', 'sign_type', 'platform','ip', 'long', 'lat', 'address', 'remark'];
}




