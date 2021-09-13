<?php

namespace App\EofficeApp\System\Address\Entities;

use App\EofficeApp\Base\BaseEntity;

class ProvinceEntity extends BaseEntity
{
    /** @var string 省表 */
    public $table = 'province';

    /** @var string 主键 */
    public $primaryKey = 'province_id';
}
