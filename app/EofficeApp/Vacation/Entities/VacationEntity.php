<?php

namespace App\EofficeApp\Vacation\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacationEntity extends BaseEntity
{
    /**
     * 启用软删除
     */
    use SoftDeletes;

    protected $primaryKey = 'vacation_id';

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * [$table 表名]
     *
     * @var string
     */
    public $table = 'vacation';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var array
     */
    protected $fillable = [
        'vacation_name',
        'is_paid',
        'paid_proportion',
        'remark',
        'enable',
        'sort',
        'is_natural_day',
        'min_leave_unit',
        'is_limit',
        'cycle',
        'cycle_point',
        'is_delay',
        'delay_days',
        'delay_unit',
        'give_method',
        'out_url',
        'days_rule_method',
        'days_rule_detail',
        'hours_rule_detail',
        'one_time_give',
        'add_user_cut',
        'once_auto_days',
        'days_rule_detail',
        'no_cycle_expire_mode',
        'no_cycle_expire_days',
        'expire_remind_time',
        'is_transform',
        'conversion_ratio'
    ];
}