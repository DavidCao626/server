<?php 
namespace App\EofficeApp\Address\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @公共通讯录组实体
 * 
 * @author 李志军
 */
class AddressPublicGroupEntity extends BaseEntity
{
    public $primaryKey 		= 'group_id';
    public $table 			= 'address_public_group';
	public $foreignKey		= 'primary_4';
	public function address(){
		return $this->news->hasMany('App\EofficeApp\Address\Entities\AddressEntity',$this->foreignKey);	 
	}
}
