<?php
namespace App\EofficeApp\PersonalSet\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 客户端设置实体
 * 
 * @author  李志军
 * 
 * @since 2015-10-30
 */
class ClientSetEntity extends BaseEntity
{
	public $primaryKey		= 'id';
	
	public $table 			= 'client_set';

}
