<?php

namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 薪酬项目Entity类:提供薪酬项目实体。
 */
class SalaryFieldsEntity extends BaseEntity
{
	use SoftDeletes;
    /** @var string 客户表 */
	public $table = 'salary_fields';

	public $primaryKey = 'field_id';

	public $timestamps = true;

	protected $dates = ['deleted_at'];


	public function personalDefaults()
    {
        return $this->hasMany(SalaryFieldPersonalDefaultEntity::class, 'field_id', 'field_id');
    }

}
