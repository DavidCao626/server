<?php

namespace App\EofficeApp\System\Address\Entities;

use App\EofficeApp\Base\BaseEntity;

class DistrictEntity extends BaseEntity
{
    /** @var string 省表 */
    public $table = 'district';

    /** @var string 主键 */
    public $primaryKey = 'district_id';
}
