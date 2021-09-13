<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePurviewUserEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_purview_user';

    protected $fillable = ['group_id', 'manager', 'user_id'];
    public $timestamps = false;
}




