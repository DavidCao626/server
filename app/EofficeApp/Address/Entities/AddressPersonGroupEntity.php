<?php 
namespace App\EofficeApp\Address\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @个人通讯录组实体
 * 
 * @author 李志军
 */
class AddressPersonGroupEntity extends BaseEntity
{
    public $primaryKey 		= 'group_id';
    public $table 			= 'address_person_group';
	public $foreignKey		= 'primary_4';
	public function address(){
		return $this->news->hasMany('App\EofficeApp\Address\Entities\AddressEntity',$this->foreignKey);	 
	}
}
