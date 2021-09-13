<?php

namespace App\EofficeApp\Vacation\Entities;

use App\EofficeApp\Base\BaseEntity;

class VacationExpireRecordEntity extends BaseEntity
{
    /**
     * [$table 表名]
     *
     * @var string
     */
    public $table = 'vacation_expire_record';

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
        'created_date'
    ];
}