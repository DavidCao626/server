<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePurviewOwnerEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_purview_owner';

    protected $fillable = ['group_id', 'menu_id'];
    public $timestamps = false;
}




