<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePointsEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_points';

    protected $fillable = ['point_name', 'point_address', 'point_latitude', 'point_longitude','point_radius','all_member', 'dept_id', 'role_id', 'user_id', 'allow_accuracy_deviation'];
}




