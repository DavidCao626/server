<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePurviewDeptEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_purview_dept';

    protected $fillable = ['group_id', 'manager', 'dept_id'];
    public $timestamps = false;
}




