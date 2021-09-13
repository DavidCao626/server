<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceCommonPurviewGroupEntity extends BaseEntity 
{
    /** @var string $table 定义实体表 */
    public $table = 'attend_common_purview_group';

    protected $fillable = ['all_member', 'dept_id', 'role_id', 'user_id'];
}




