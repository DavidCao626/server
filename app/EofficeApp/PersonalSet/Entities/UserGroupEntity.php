<?php
namespace App\EofficeApp\PersonalSet\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 用户组实体
 * 
 * @author  李志军
 * 
 * @since 2015-10-30
 */
class UserGroupEntity extends BaseEntity
{
	public $primaryKey		= 'group_id';
	
	public $table 			= 'user_group';

}
