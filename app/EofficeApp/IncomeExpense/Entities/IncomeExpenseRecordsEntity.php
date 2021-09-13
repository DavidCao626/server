<?php
namespace App\EofficeApp\IncomeExpense\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 收支记录实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-17
 */
class IncomeExpenseRecordsEntity extends BaseEntity
{
	public $primaryKey		= 'record_id';
	
	public $table 			= 'income_expense_records';
}
