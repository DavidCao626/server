<?php

namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

class SalaryCalculateEntity extends BaseEntity
{
    /** @property string    type_code */
    /** @property string    type_name */
    /** @property int       parent_id */
    /** @property string    type_object */
    /** @property string    type_method */

    /** @var string 表 */
	public $table = 'calculate_data';

    /** @var string 主键 */
    public $primaryKey = 'id';

    /** @var bool 表明模型是否应该被打上时间戳 */
    public $timestamps = false;
}
