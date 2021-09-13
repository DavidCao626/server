<?php
namespace App\EofficeApp\IncomeExpense\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 收支记录子表实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-17
 */
class IncomeExpenseRecordsSubEntity extends BaseEntity
{
	public $primaryKey		= 'record_id';
	
	public $table 			= 'income_expense_records_sub';
	
	public $timestamps		= false;
}
