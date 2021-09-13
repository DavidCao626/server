<?php

namespace App\EofficeApp\Calendar\Entities;

use App\EofficeApp\Base\BaseEntity;

class CalendarSaveFilterEntity extends BaseEntity
{
    public $table = 'calendar_save_filter';
    
    public $primaryKey = 'filter_id';

    protected $fillable = ['filter_id','user_id','save_filter'];
}
