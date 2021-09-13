<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePurviewRoleEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_purview_role';

    protected $fillable = ['group_id', 'manager', 'role_id'];
    public $timestamps = false;
}




