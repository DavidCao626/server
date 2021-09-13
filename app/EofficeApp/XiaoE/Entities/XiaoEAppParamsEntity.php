<?php

namespace App\EofficeApp\XiaoE\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class XiaoEAppParamsEntity extends BaseEntity
{

    use SoftDeletes;

    public $table = 'xiaoe_app_params';

    public $primaryKey = 'id';
}
