<?php 
namespace App\EofficeApp\Address\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @通讯录实体
 * 
 * @author 李志军
 */
class AddressEntity extends BaseEntity
{
    public $primaryKey 		= 'address_id';
	
    public $table 			= 'address';
}
