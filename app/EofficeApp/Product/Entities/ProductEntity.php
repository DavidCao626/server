<?php

namespace App\EofficeApp\Product\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 产品Entity类
 *
 * @author 牛晓克
 *
 * @since  2017-12-12 创建
 */
class ProductEntity extends BaseEntity
{
    use SoftDeletes;

    public $table = 'product';

    public $primaryKey = 'product_id';

    public $timestamps = true;

    public function productToProductType()
    {
        return $this->hasOne('App\EofficeApp\Product\Entities\ProductTypeEntity', 'product_type_id', 'product_type_id');
    }

    public function productTo()
    {
        return $this->hasOne('App\EofficeApp\Product\Entities\ProductTypeEntity', 'product_type_id', 'product_type_id');
    }
}
