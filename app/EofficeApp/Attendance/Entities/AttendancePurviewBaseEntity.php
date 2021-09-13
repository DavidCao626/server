<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePurviewBaseEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_purview_base';

    protected $fillable = ['group_id', 'purview_type', 'levels_purview_mark', 'department_purview_mark'];
    public $timestamps = false;
}




