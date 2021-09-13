<?php

namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 薪酬上报Entity类:提供薪酬上报实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SalaryReportEntity extends BaseEntity
{
    /** @var string 客户表 */
	public $table = 'salary_report';

    /** @var string 主键 */
    public $primaryKey = 'report_id';

    public function salaries()
    {
        return $this->hasMany(SalaryEntity::class, 'report_id', 'report_id');
    }

    public function fieldHistories()
    {
        return $this->hasMany(SalaryFieldHistoryEntity::class, 'report_id', 'report_id');
    }

    public function payDetails()
    {
        return $this->hasManyThrough(
            SalaryPayDetailEntity::class,
            SalaryEntity::class,
            'report_id',
            'salary_id',
            'report_id',
            'salary_id'
        );
    }

    public function personalDefaultHistories()
    {
        return $this->hasManyThrough(
            SalaryFieldPersonalDefaultHistoryEntity::class,
            SalaryFieldHistoryEntity::class,
            'report_id',
            'field_history_id',
            'report_id',
            'id'
        );
    }




}
