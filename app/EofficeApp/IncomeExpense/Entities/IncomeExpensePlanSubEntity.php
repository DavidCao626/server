<?php
namespace App\EofficeApp\IncomeExpense\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 收支方案子表实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-17
 */
class IncomeExpensePlanSubEntity extends BaseEntity
{
	public $primaryKey		= 'plan_id';
	
	public $table 			= 'income_expense_plan_sub';
	
	public $timestamps		= false;
}
