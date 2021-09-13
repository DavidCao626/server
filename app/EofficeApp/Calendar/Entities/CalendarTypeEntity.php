<?php

namespace App\EofficeApp\Calendar\Entities;

use App\EofficeApp\Base\BaseEntity;

class CalendarTypeEntity extends BaseEntity
{
    public $table = 'calendar_type';
    public $primaryKey = 'type_id';
    public $timestamps = false;
    protected $fillable = ['type_name','mark_color','is_default', 'sort'];
}
