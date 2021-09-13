<?php
/**
 * Created by PhpStorm.
 * User: yxqviver
 * Date: 2020/6/19
 * Time: 11:16
 */

namespace App\EofficeApp\Vacation\Entities;

use App\EofficeApp\Base\BaseEntity;

class VacationScaleLogEntity extends BaseEntity
{

    /**
     * [$table 表名]
     *
     * @var string
     */
    public $table = 'vacation_scale_log';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var array
     */
    protected $fillable = [
        'scale_name',
        'scale_ratio',
        'creator'
    ];
}