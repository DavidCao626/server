<?php

namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\User\Entities\UserEntity;

/**
 * 薪酬Entity类:提供薪酬实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-29 创建
 */
class SalaryEntity extends BaseEntity
{
    /** @var string 客户表 */
	public $table = 'salary';

    /** @var string 主键 */
    public $primaryKey = 'salary_id';

    /** @var bool 表明模型是否应该被打上时间戳 */
    public $timestamps = true;

    /**
     * 用户薪酬和薪酬上报流程一对一
     *
     * @return object
     */
    public function salaryToSalaryReport()
    {
        return  $this->belongsTo(SalaryReportEntity::class,'report_id', 'report_id');
    }

    /**
     * 薪酬上报，上报详情一对多
     */
    public function payDetails()
    {
        return $this->hasMany(SalaryPayDetailEntity::class, 'salary_id', 'salary_id');
    }

    public function user()
    {
        return $this->belongsTo(UserEntity::class, 'user_id', 'user_id');
    }

    function hasPersonnel()
    {
        return $this->hasOne('App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity','id','user_id');
    }
}
