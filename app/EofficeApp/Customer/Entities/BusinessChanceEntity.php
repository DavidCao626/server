<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;


class BusinessChanceEntity extends BaseEntity
{
    /** @var string 业务机会表 */
	public $table = 'customer_business_chance';

    /** @var string 主键 */
    public $primaryKey = 'chance_id';

    public function customerBusinessChanceLog()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerBusinessChanceLogEntity','chance_id','chance_id');
    }


    public function businessChanceToCustomer()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerEntity','customer_id','customer_id');
    }


    public function businessChanceToUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','chance_creator');
    }

    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerBusinessChanceSubEntity','customer_business_chance_id','chance_id');
    }


    public function hasOneCustomer()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity','customer_id','customer_id');
    }
}