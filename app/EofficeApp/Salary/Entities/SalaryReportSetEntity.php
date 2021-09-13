<?php

namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

class SalaryReportSetEntity extends BaseEntity
{
    /** @var string 表 */
    public $table = 'salary_report_set';

    /** @var string 主键 */
    public $primaryKey = 'id';

    /** @var bool 表明模型是否应该被打上时间戳 */
    public $timestamps = false;
}
