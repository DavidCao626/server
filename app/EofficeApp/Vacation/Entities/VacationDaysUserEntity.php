<?php

namespace App\EofficeApp\Vacation\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacationDaysUserEntity extends BaseEntity
{
	/**
	 * [$table 表名]
	 * 
	 * @var string
	 */
	public $table = 'vacation_days_user';

	/**
	 * [$fillable 允许批量更新的字段]
	 * 
	 * @var array
	 */
	protected $fillable = ['user_id', 'vacation_id', 'days', 'cycle','cycle_updated_at','adjust_days','leave_days','overtime_days'];
}