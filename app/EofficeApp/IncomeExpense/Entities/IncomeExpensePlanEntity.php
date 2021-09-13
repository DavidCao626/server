<?php
namespace App\EofficeApp\IncomeExpense\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 收支方案实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-17
 */
class IncomeExpensePlanEntity extends BaseEntity
{
	use SoftDeletes;
	
	public $primaryKey		= 'plan_id';
	
	public $table 			= 'income_expense_plan';

    protected $dates = ['deleted_at'];

}
