<?php

namespace App\EofficeApp\Product\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 产品类别Entity类
 *
 * @author 牛晓克
 *
 * @since  2017-12-12 创建
 */
class ProductTypeEntity extends BaseEntity
{
    use SoftDeletes;

    public $table = 'product_type';

    public $primaryKey = 'product_type_id';

    public $timestamps = true;

}
