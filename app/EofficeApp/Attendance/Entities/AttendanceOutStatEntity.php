<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceOutStatEntity extends BaseEntity
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_out_stat';

    protected $fillable = ['user_id', 'date', 'year', 'month', 'day', 'out_days', 'out_hours'];
    public $timestamps = false;
}




