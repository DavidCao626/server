<?php
namespace App\EofficeApp\IncomeExpense\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 收支方案类别实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-17
 */
class IncomeExpensePlanTypeEntity extends BaseEntity
{
	public $primaryKey		= 'plan_type_id';
	
	public $table 			= 'income_expense_plan_type';
}
