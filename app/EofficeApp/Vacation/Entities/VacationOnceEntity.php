<?php

namespace App\EofficeApp\Vacation\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacationOnceEntity extends BaseEntity
{
    /**
     * [$table 表名]
     *
     * @var string
     */
    public $table = 'vacation_once';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'vacation_id',
        'days',
        'hours',
        'outsend_days',
        'outsend_hours'
    ];
}