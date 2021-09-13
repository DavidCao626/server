<?php 

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;


class SalesRecordEntity extends BaseEntity
{
    /**  @var string 销售记录表 */
	public $table = 'customer_sales_record';

    /** @var string 主键 */    
    public $primaryKey = 'sales_id';
 
    public function salesRecordToCustomer() 
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity','customer_id','customer_id');
    }
  
    public function salesRecordToProduct() 
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\ProductEntity','product_id','product_id');
    }

  
    public function salesRecordToUser() 
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','sales_creator');
    }
}