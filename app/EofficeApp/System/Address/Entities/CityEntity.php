<?php

namespace App\EofficeApp\System\Address\Entities;

use App\EofficeApp\Base\BaseEntity;

class CityEntity extends BaseEntity
{
    /** @var string 城市表 */
    public $table = 'city';

    /** @var string 主键 */
    public $primaryKey = 'city_id';
}
