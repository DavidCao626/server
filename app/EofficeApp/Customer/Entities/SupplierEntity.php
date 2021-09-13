<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;


class SupplierEntity extends BaseEntity
{
	public $table = 'customer_supplier';

    public $primaryKey = 'supplier_id';

    public $timestamps = false;
}
