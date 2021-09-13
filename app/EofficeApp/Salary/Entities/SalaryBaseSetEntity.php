<?php
namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

class SalaryBaseSetEntity extends BaseEntity
{
    /**
     * 薪酬基础设置表
     *
     * @var string
     */
    public $table = 'salary_base_set';
    public $timestamps = false;
}
