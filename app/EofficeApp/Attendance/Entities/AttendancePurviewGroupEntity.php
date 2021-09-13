<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendancePurviewGroupEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_purview_group';
    public $primaryKey = 'group_id';
    protected $fillable = ['group_id', 'group_name', 'remark'];
}




