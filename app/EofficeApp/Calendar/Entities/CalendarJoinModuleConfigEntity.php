<?php

namespace App\EofficeApp\Calendar\Entities;

use App\EofficeApp\Base\BaseEntity;

class CalendarJoinModuleConfigEntity extends BaseEntity
{
    public $table = 'calendar_join_module_config';

    public $primaryKey = 'module_id';
    public $timestamps = false;
}
