<?php

namespace App\EofficeApp\Vacation\Entities;

use App\EofficeApp\Base\BaseEntity;

class VacationLogEntity extends BaseEntity
{

    /**
     * [$table 表名]
     *
     * @var string
     */
    public $table = 'vacation_log';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'before',
        'after',
        'change',
        'before_hours',
        'after_hours',
        'change_hours',
        'vacation_id',
        'when',
        'reason',
        'date'
    ];
}