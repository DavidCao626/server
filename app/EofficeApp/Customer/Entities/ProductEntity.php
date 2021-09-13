<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 产品Entity类:提供产品实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class ProductEntity extends BaseEntity
{
    /**  @var string 产品表 */
	public $table = 'customer_product';

    /** @var string 主键 */
    public $primaryKey = 'product_id';


    public function productCreatorToUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','product_creator');
    }


    public function productToSupplier()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\SupplierEntity', 'supplier_id', 'supplier_id');
    }

    public function hasManySales()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\SalesRecordEntity', 'product_id', 'product_id');
    }
}