<?php

namespace App\EofficeApp\XiaoE\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class XiaoESystemParamsEntity extends BaseEntity
{

    use SoftDeletes;

    public $table = 'xiaoe_system_params';

    public $primaryKey = 'id';
}
