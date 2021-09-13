<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 供应商Entity类:提供供应商实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class CustomerSupplierEntity extends BaseEntity
{
    /** @var string 供应商表 */
	public $table = 'customer_supplier';

    /** @var string 主键 */
    public $primaryKey = 'supplier_id';

    /**
     * 供应商创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-27
     */
    public function supplierCreatorToUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','supplier_creator');
    }

    /**
     * 供应商和商品一对一(判断供应商是否有商品)
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-29
     */
    public function supplierHasProduct()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerProductEntity','supplier_id','supplier_id');
    }
}