<?php

namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 薪酬项目Entity类:提供薪酬项目实体。
 */
class SalaryFieldHistoryEntity extends BaseEntity
{
    /** @var string 客户表 */
	public $table = 'salary_field_history';

	public $primaryKey = 'id';


	public function personalDefaultHistories()
    {
        return $this->hasMany(SalaryFieldPersonalDefaultHistoryEntity::class, 'field_history_id', 'id');
    }



}
