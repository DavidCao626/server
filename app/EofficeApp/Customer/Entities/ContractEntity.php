<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;


class ContractEntity extends BaseEntity
{
    /** @var string 合同表 */
	public $table = 'customer_contract';

    /** @var string 主键 */
    public $primaryKey = 'contract_id';


    public function customer()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity', 'customer_id', 'customer_id');
    }

    public function supplier()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerSupplierEntity', 'supplier_id', 'supplier_id');
    }

    public function user()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'contract_creator');
    }

    public function remind()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\ContractRemindEntity', 'contract_id', 'contract_id');
    }
}